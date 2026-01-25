<?php

// Missile attack. It used to be in battle.php, but for convenience it was separated into its own module.
// TODO: It is necessary to pay more attention to this feature of the game, because there are doubts about the correctness of the algorithm. To test it, you can use the admin section to simulate a missile attack.

// IPM - interplanetary missile, attacks
// ABM - anti-ballistic missile, defends

// Algorithmic part of the missile attack (without working with DB).
function RocketAttackMain ( int $amount, int $primary, bool $moon_attack, array &$target, array &$moon_planet, int $origin_user_attack, int $target_user_armor ) : int
{
    global $UnitParam;

    // Repel IPM attack by interceptors (ABMs)
    $ipm = $amount;
    $abm = $moon_attack ? $moon_planet[GID_D_ABM] : $target[GID_D_ABM];
    $ipm = (int)max (0, $ipm - $abm);
    $ipm_destroyed = $amount - $ipm;
    if ($moon_attack) $moon_planet[GID_D_ABM] -= $ipm_destroyed;
    else $target[GID_D_ABM] -= $ipm_destroyed;

    $maxdamage = $ipm * $UnitParam[503][2] * (1 + $origin_user_attack / 10);

    // Launch an attack on the primary target
    if ( $primary > 0 && $ipm > 0 )
    {
        $armor = $UnitParam[$primary][0] * (1 + 0.1 * $target_user_armor) / 10;
        $count = $target[$primary];
        if ($count != 0) {
            $destroyed = min ( floor ( $maxdamage / $armor ), $count );
            $target[$primary] -= $destroyed;
            $maxdamage -= $destroyed * $armor;
            $maxdamage -= $destroyed;
        }
    }

    // Calculate defense losses, if there are still IPMs left -- all the same, but ignore the ID of the primary target in the ID. Foreign missiles can also be bombed.
    if ($maxdamage > 0)
    {
        global $defmap;
        foreach ($defmap as $i=>$id)
        {
            if ($id == $primary) continue;
            $armor = $UnitParam[$id][0] * (1 + 0.1 * $target_user_armor) / 10;
            $count = $target[$id];
            if ($count != 0) {
                $destroyed = min ( floor ( $maxdamage / $armor ), $count );
                $target[$id] -= $destroyed;
                $maxdamage -= $destroyed * $armor;
                $maxdamage -= $destroyed;
            }
            if ($maxdamage <= 0) break;
        }
    }

    return $ipm_destroyed;
}

// Missile attack.
function RocketAttack ( int $fleet_id, int $planet_id, int $when ) : void
{
    $fleet = LoadFleet ($fleet_id);
    $amount = $fleet['ipm_amount'];
    $primary = $fleet['ipm_target'];
    $origin = LoadPlanetById ($fleet['start_planet']);
    $target = LoadPlanetById ($planet_id);
    $moon_attack = $target['type'] == 0;
    if ($moon_attack) {
        // If a missile attack is made on the Moon, interceptors from the planet are involved in defense
        $moon_planet = LoadPlanet ($target['g'], $target['s'], $target['p'], 1);
    }
    $origin_user = LoadUser ($origin['owner_id']);
    $target_user = LoadUser ($target['owner_id']);

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
    if ($moon_attack) {
        SetPlanetDefense ( $moon_planet['planet_id'], $moon_planet );
    }

    // Modify player statistics
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

    $message_for_attacker = true;

    // Generate a message for the attacker: https://github.com/ogamespec/ogame-opensource/issues/61
    // The original 0.84 version did not create a message for the attacker.
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

// Get the text for the destroyed defense
function GetDestroyedDefenseText (string $lang, array &$target, array &$moon_planet, bool $moon_attack) : string
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
            if ($moon_attack && $gid == GID_D_ABM ) {
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