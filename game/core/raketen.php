<?php
/**
 * @file raketen.php
 * @brief Interplanetary missile attacks.
 * @details Handles missile launch, flight time, interception and the damage applied to the target planet defences.
 */
// Missile attack. It used to be in battle.php, but for convenience it was separated into its own module.
// The algorithm reproduces the original OGame 0.84 missile attack: anti-ballistic
// missiles intercept the incoming IPMs one-to-one, the surviving IPMs deal
// damage to the target's defensive structures (the primary target first, then
// the rest in order). Once every structure is destroyed the leftover damage
// hits the defender's stored missiles too (the anti-ballistic missiles that
// survived the interception first, then the interplanetary missiles). See
// RocketAttackMain(). The admin section (RakSim) and the reference simulator
// (https://battlesim.logserver.net/ru) can be used to verify.

// IPM - interplanetary missile, attacks
// ABM - anti-ballistic missile, defends

/**
 * Perform the algorithmic part of a missile attack without working with the database.
 *
 * Each anti-ballistic missile destroys exactly one incoming interplanetary
 * missile (the ABMs are consumed for that). The surviving IPMs then deal
 * damage equal to their number times the IPM attack value (raised by the
 * attacker's weapon technology by 10% per level). That damage is spent on the
 * target's defensive structures: the primary target (if any) is hit first,
 * then the remaining defenses in order, and leftover damage carries over to
 * the next defense type. Once all defensive structures are gone the leftover
 * damage destroys the defender's stored missiles too: the anti-ballistic
 * missiles that survived the interception first, then the interplanetary
 * missiles (the original 0.84 behavior, matching the reference simulator).
 * Stored missiles only exist on planets (a moon has no missile silo), so the
 * leftover damage is wasted once the structures of a moon are gone.
 *
 * @param int $amount Number of launched interplanetary missiles.
 * @param int $primary ID of the primary target defense (0 = attack everything).
 * @param bool $moon_attack True if the attack targets a moon.
 * @param array $target The target planet array; defense amounts are modified in place.
 * @param array|null $moon_planet The moon array, or null; interceptors are taken from it on a moon attack.
 * @param int $origin_user_attack Weapon technology level of the attacker.
 * @param int $target_user_armor Armor technology level of the defender.
 * @return int The number of missiles destroyed by anti-ballistic missiles.
 */
function RocketAttackMain ( int $amount, int $primary, bool $moon_attack, array &$target, array|null &$moon_planet, int $origin_user_attack, int $target_user_armor ) : int
{
    global $UnitParam;

    // Repel IPM attack by interceptors (ABMs)
    $ipm = $amount;
    $abm = $moon_attack ? ( $moon_planet === null ? 0 : $moon_planet[GID_D_ABM] ) : $target[GID_D_ABM];
    $ipm = (int)max (0, $ipm - $abm);
    $ipm_destroyed = $amount - $ipm;
    if ($moon_attack) {
        if ($moon_planet === null) $moon_planet = array ( GID_D_ABM => 0 );
        $moon_planet[GID_D_ABM] -= $ipm_destroyed;
    }
    else $target[GID_D_ABM] -= $ipm_destroyed;

    // Total damage dealt by the surviving IPMs. The attacker's weapon
    // technology raises it by 10% per level (the IPM attack value is the
    // stat used here, see $UnitParam[GID_D_IPM][2]).
    $maxdamage = $ipm * $UnitParam[GID_D_IPM][2] * (1 + $origin_user_attack / 10);

    // The damage is spent on the defensive structures first (the primary
    // target, then the rest in order). Once all of them are destroyed the
    // leftover damage hits the defender's stored missiles: on a planet attack
    // the anti-ballistic missiles were already consumed by interception (the
    // block above), and the interplanetary missiles stored in the silo are
    // destroyed last.
    global $defmap;

    // Launch an attack on the primary target first.
    if ( $primary > 0 && $ipm > 0 )
    {
        $armor = $UnitParam[$primary][0] * (1 + 0.1 * $target_user_armor) / 10;
        $count = $target[$primary];
        if ($count != 0) {
            $destroyed = (int)min ( floor ( $maxdamage / $armor ), $count );
            $target[$primary] -= $destroyed;
            $maxdamage -= $destroyed * $armor;
        }
    }

    // Spread the remaining damage over the remaining defense structures
    // in order; leftover damage carries over to the next defense type.
    if ($maxdamage > 0)
    {
        foreach ($defmap as $i=>$id)
        {
            if ($id == $primary) continue;
            $armor = $UnitParam[$id][0] * (1 + 0.1 * $target_user_armor) / 10;
            $count = $target[$id];
            if ($count != 0) {
                $destroyed = (int)min ( floor ( $maxdamage / $armor ), $count );
                $target[$id] -= $destroyed;
                $maxdamage -= $destroyed * $armor;
            }
            if ($maxdamage <= 0) break;
        }
    }

    return $ipm_destroyed;
}

/**
 * Execute a missile attack: apply damage, update defenses and statistics, and send messages to both players.
 *
 * @param int $fleet_id ID of the missile fleet.
 * @param int $planet_id ID of the target planet.
 * @param int $when Time of the attack (Unix timestamp).
 * @return void
 */
function RocketAttack ( int $fleet_id, int $planet_id, int $when ) : void
{
    $fleet = LoadFleet ($fleet_id);
    $amount = $fleet['ipm_amount'];
    $primary = $fleet['ipm_target'];
    $origin = LoadPlanetById ($fleet['start_planet']);
    $target = LoadPlanetById ($planet_id);
    $moon_attack = $target['type'] == PTYP_MOON;
    $moon_planet = null;
    if ($moon_attack) {
        // If a missile attack is made on the Moon, interceptors from the planet are involved in defense
        $moon_planet = LoadPlanet ($target['g'], $target['s'], $target['p'], 1);
        if ( $moon_planet === false ) $moon_planet = null;    // LoadPlanet returns false on an empty result.
    }
    $origin_user = LoadUser ($origin['owner_id']);
    if ($origin_user == null) return;
    $target_user = LoadUser ($target['owner_id']);
    if ($target_user == null) return;

    // Statistics update (issue #145): snapshot the defense amounts before the
    // damage so the score loss can be calculated after the attack.
    $target_before = $target;
    $moon_planet_before = $moon_planet;

    $ipm_destroyed = RocketAttackMain (
        $amount, 
        $primary, 
        $moon_attack, 
        $target, 
        $moon_planet, 
        $origin_user[GID_R_WEAPON], 
        $target_user[GID_R_ARMOUR] );

    // Write back the defense's losses.
    SetPlanetDefense ( $planet_id, $target );
    if ($moon_attack && $moon_planet !== null) {
        SetPlanetDefense ( $moon_planet['planet_id'], $moon_planet );
    }

    // Modify player statistics (issue #145). The attacker loses the score of
    // the launched missiles, and the defender loses the score of the
    // destroyed defenses (the anti-ballistic missiles consumed by
    // interception included). Before, the scores were corrected only by a
    // later full recalculation.
    $missile_points = TechPriceInPoints ( TechPrice ( GID_D_IPM, 1 ) );
    AdjustStats ( $fleet['owner_id'], $missile_points * $amount, 0, 0, '-' );

    $loss = RocketDefenseLossPoints ( $target_before, $target );
    if ( $loss > 0 ) AdjustStats ( $target['owner_id'], $loss, 0, 0, '-' );

    if ( $moon_attack && $moon_planet_before !== null && $moon_planet !== null ) {
        // On a moon attack the interceptors are taken from the co-located
        // planet, so their loss reduces that planet's owner.
        $loss = RocketDefenseLossPoints ( $moon_planet_before, $moon_planet );
        if ( $loss > 0 ) AdjustStats ( $moon_planet['owner_id'], $loss, 0, 0, '-' );
    }

    RecalcRanks ();

    // Update the activity on the planet.
    UpdatePlanetActivity ( $planet_id, $when );

    // Generate a message for the defender
    loca_add ( "raketen", $target_user['lang'] );
    loca_add ( "fleetmsg", $target_user['lang'] );
    $text = va(loca_lang("RAK_DEF_TEXT1", $target_user['lang']), $amount) . " ". $origin['name']." <a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>  ";
    $text .= loca_lang ("RAK_DEF_TEXT2", $target_user['lang']) . " " . $target['name']." <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a> !<br>";
    if ($ipm_destroyed) $text .= va(loca_lang("RAK_DEF_TEXT3", $target_user['lang']), $ipm_destroyed) . "<br>:<br>";
    $text .= GetDestroyedDefenseText ($target_user['lang'], $target, $moon_planet, $moon_attack);
    SendMessage ( $target_user['player_id'], 
        loca_lang ("FLEET_MESSAGE_FROM", $target_user['lang']), 
        loca_lang ("RAK_MSG_SUBJ", $target_user['lang']), 
        $text, MTYP_BATTLE_REPORT_LINK, $when);

    // Generate a message for the attacker: https://github.com/ogamespec/ogame-opensource/issues/61
    // The original 0.84 version did not create a message for the attacker.
    // Can be turned off at runtime, e.g. $GLOBALS['message_for_attacker'] = false;
    $message_for_attacker = (bool) ( $GLOBALS['message_for_attacker'] ?? true );
    if ($message_for_attacker) {

        loca_add ( "raketen", $origin_user['lang'] );
        loca_add ( "fleetmsg", $origin_user['lang'] );
        $text = va(loca_lang("RAK_ATT_TEXT1", $origin_user['lang']), $amount) . " " . $origin['name']." <a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a> ";
        $text .= loca_lang("RAK_ATT_TEXT2", $origin_user['lang']) . " " . $target['name']." <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a> !<br>";    
        $text .= GetDestroyedDefenseText ($origin_user['lang'], $target, $moon_planet, $moon_attack);
        SendMessage ( $origin_user['player_id'], 
            loca_lang ("FLEET_MESSAGE_FROM", $origin_user['lang']), 
            loca_lang ("RAK_MSG_SUBJ", $origin_user['lang']), 
            $text, MTYP_BATTLE_REPORT_LINK, $when);
    }
}

/**
 * Calculate the score loss (in points) of the defenses destroyed by a missile attack.
 *
 * The loss is the resource cost of every defense unit whose amount decreased
 * between the two snapshots of a planet or moon: the structures destroyed by
 * the missile damage, the anti-ballistic missiles consumed by interception,
 * and the stored missiles destroyed by the leftover damage once every
 * structure is gone.
 *
 * @param array $before Defense amounts before the attack.
 * @param array $after Defense amounts after the attack.
 * @return int The loss in score points.
 */
function RocketDefenseLossPoints (array $before, array $after) : int
{
    global $defmap;
    $loss = 0;
    foreach ( $defmap as $i=>$gid )
    {
        $destroyed = ( $before[$gid] ?? 0 ) - ( $after[$gid] ?? 0 );
        if ( $destroyed <= 0 ) continue;
        $res = TechPrice ( $gid, 1 );
        $loss += TechPriceInPoints ( $res ) * $destroyed;
    }
    return $loss;
}

/**
 * Build the HTML text describing the destroyed defenses.
 *
 * @param string $lang Language code for localization.
 * @param array $target The target planet array with the remaining defense amounts.
 * @param array|null $moon_planet The moon array, or null.
 * @param bool $moon_attack True if the attack targeted a moon.
 * @return string The HTML table text of the destroyed defense.
 */
function GetDestroyedDefenseText (string $lang, array &$target, array|null &$moon_planet, bool $moon_attack) : string
{
    loca_add ( "raketen", $lang );
    loca_add ( "technames", $lang );

    global $defmap;
    $defmap_rev = array_reverse ($defmap);         // the defenses are being pulled backwards for some unknown reason.
    $deftext = "<table width=400><tr><td class=c colspan=4>".loca_lang("RAK_TITLE", $lang)."</td></tr>";
    $n = 0;
    foreach ( $defmap_rev as $i=>$gid )
    {
        if ( ($n % 2) == 0 ) $deftext .= "</tr>";
        if ( $target[$gid] ) {

            $count = $target[$gid];
            // Consider the defense of the moon by interceptors from the planet.
            if ($moon_attack && $gid == GID_D_ABM && $moon_planet !== null) {
                $count = $moon_planet[GID_D_ABM];
            }

            $deftext .= "<td>".loca_lang("NAME_$gid", $lang)."</td><td>".nicenum($count)."</td>";
            $n++;
        }
    }
    $deftext .= "</table><br>\n";

    return $deftext;
}

?>