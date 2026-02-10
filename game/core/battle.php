<?php

// OGame Battle Engine frontend.

const BATTLE_RESULT_AWON = 0;       // The attacker won
const BATTLE_RESULT_DWON = 1;       // The defender won
const BATTLE_RESULT_DRAW = 2;       // Draw

// Displays the battle participant type. Stored in the `bf` variable in the source data (a/d).
// This primarily affects the output results—what to do with the participants after the battle.
const BATTLE_PTCP_FLEET = 0;        // The participant arrived on the Fleet (from the fleet table)
const BATTLE_PTCP_PLANET = 1;       // The participant arrived from Planet (id from the planets table)
const BATTLE_PTCP_VIRTUAL = 2;      // The participant is virtual ("drawn"), the ID has no meaning (example - pirates/aliens on an expedition)

// Repairing the defense.
// Multiple planetary defenders are taken into account.
function RepairDefense ( array $d, array $res, int $defrepair, int $defrepair_delta, bool $premium=true ) : array
{
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);

    $repaired = [];
    $exploded = [];
    $exploded_total = [];
    $premium_status = [];

    foreach ( $d as $i=>$defender) {
        if ($defender['pf'] != BATTLE_PTCP_PLANET) continue;     // not planet
        $exploded_total[$i] = 0;    
        $repaired[$i] = [];
        $exploded[$i] = [];
        foreach ( $defmap_norak as $n=>$gid ) {
            $repaired[$i][$gid] = 0;
            $exploded[$i][$gid] = 0;
        }
        if ( $premium) $prem = PremiumStatus ($d[$i]);
        else $prem = array();
        $premium_status[$i] = key_exists ('engineer', $prem) && $prem['engineer'];
    }

    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        // Count the blown defenses.
        $last = $res['rounds'][$rounds - 1];

        foreach ($d as $i=>$defender) {
            if ($defender['pf'] != BATTLE_PTCP_PLANET) continue;     // not planet

            foreach ( $defmap_norak as $n=>$gid )
            {
                $before = isset($d[$i]['units'][$gid]) ? $d[$i]['units'][$gid] : 0;
                $after =  isset ($last['defenders'][$i]['units'][$gid]) ? $last['defenders'][$i]['units'][$gid] : 0;
                $exploded[$i][$gid] = $before - $after;
                if ( $premium_status[$i] ) $exploded[$i][$gid] = floor ($exploded[$i][$gid] / 2);
                $exploded_total[$i] += $exploded[$i][$gid];
            }

            // Restore the defense
            if ($exploded_total[$i])
            {
                foreach ( $exploded[$i] as $gid=>$amount )
                {
                    if ( $amount < 10 )
                    {
                        for ($i=0; $i<$amount; $i++)
                        {
                            if ( mt_rand (0, 99) < $defrepair ) $repaired[$i][$gid]++;
                        }
                    }
                    else $repaired[$i][$gid] = floor ( mt_rand ($defrepair-$defrepair_delta, $defrepair+$defrepair_delta) * $amount / 100 );
                }
            }
        }
    }

    return $repaired;
}

// Capture resources.
function Plunder ( int $cargo, float $m, float $k, float $d ) : array
{
    global $transportableResources;
    $m /=2; $k /=2; $d /= 2;
    $total = $m+$k+$d;
    
    $mc = $cargo / 3;
    if ($m < $mc) $mc = $m;
    $cargo = $cargo - $mc;
    $kc = $cargo / 2;
    if ($k < $kc) $kc = $k;
    $cargo = $cargo - $kc;
    $dc = $cargo;
    if ($d < $dc)
    {
        $dc = $d;
        $cargo = $cargo - $dc;
        $m = $m - $mc;
        $half = $cargo / 2;
        $bonus = $half;
        if ($m < $half) $bonus = $m;
        $mc += $bonus;
        $cargo = $cargo - $bonus;
        $k = $k - $kc;
        if ($k < $cargo) $kc += $k;
        else $kc += $cargo;
    }

    // TODO: We are laying the groundwork for capturing custom resources added by modifications
    $res = array ();
    foreach ($transportableResources as $i=>$rc) {
        $res[$rc] = 0;
    }
    $res[GID_RC_METAL] = floor($mc);
    $res[GID_RC_CRYSTAL] = floor($kc);
    $res[GID_RC_DEUTERIUM] = floor($dc);

    return $res;
}

// Calculate total losses (taking into account repaired defenses).
function CalcLosses ( array &$a, array &$d, array $res, array $repaired ) : array
{
    $aprice = $dprice = 0;

    // The cost of units before combat.
    foreach ($a as $i=>$attacker)                // Attackers
    {
        $a[$i]['points'] = 0;
        $a[$i]['fpoints'] = 0;
        foreach ( $attacker['units'] as $gid=>$amount )
        {
            if ( $amount > 0 ) {
                $cost = TechPrice ( $gid, 1 );
                $points = TechPriceInPoints($cost);
                $aprice += $points * $amount;
                $a[$i]['points'] += $points * $amount;
                if (IsFleet($gid)) $a[$i]['fpoints'] += $amount;
            }
        }
    }

    foreach ($d as $i=>$defender)            // Defenders
    {
        $d[$i]['points'] = 0;
        $d[$i]['fpoints'] = 0;
        foreach ( $defender['units'] as $gid=>$amount )
        {
            if ( $amount > 0 ) {
                $cost = TechPrice ( $gid, 1 );
                $points = TechPriceInPoints ($cost);
                $dprice += $points * $amount;
                $d[$i]['points'] += $points * $amount;
                if (IsFleet($gid)) $d[$i]['fpoints'] += $amount;
            }
        }
    }

    // The cost of units in the last round.
    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];
        $alast = $dlast = 0;

        foreach ( $last['attackers'] as $i=>$attacker )        // Attackers
        {
            foreach ( $attacker['units'] as $gid=>$amount )
            {
                if ( $amount > 0 ) {
                    $cost = TechPrice ( $gid, 1 );
                    $points = TechPriceInPoints ($cost);
                    $alast += $points * $amount;
                    $a[$i]['points'] -= $points * $amount;
                    if (IsFleet($gid)) $a[$i]['fpoints'] -= $amount;
                }
            }
        }

        foreach ( $last['defenders'] as $i=>$defender )        // Defenders
        {
            foreach ( $defender['units'] as $gid=>$amount )
            {
                if ( IsDefense($gid) && $defender['pf'] == BATTLE_PTCP_PLANET ) $amount += $repaired[$i][$gid];
                if ( $amount > 0 ) {
                    $cost = TechPrice ( $gid, 1 );
                    $points = TechPriceInPoints ($cost);
                    $dlast += $points * $amount;
                    $d[$i]['points'] -= $points * $amount;
                    if ( IsFleet($gid) ) $d[$i]['fpoints'] -= $amount;
                }
            }
        }

        $aloss = $aprice - $alast;
        $dloss = $dprice - $dlast;
    }
    else { 
        foreach ($a as $i=>$attacker) {
            $a[$i]['points'] = $a[$i]['fpoints'] = 0;
        }
        foreach ($d as $i=>$defender) {
            $d[$i]['points'] = $d[$i]['fpoints'] = 0;
        }
        $aloss = $dloss = 0;
    }

    return array ( 'aloss' => $aloss, 'dloss' => $dloss );
}

function CalcDebris (array &$a, array &$d, array $res, array $repaired, int $fid, int $did) : void {

    global $debrisResources;
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);

    // Get a list of units before battle

    $before_a = [];
    $before_d = [];

    foreach ($a as $i=>$attacker) {
        $a[$i]['debris'] = [];
        foreach ($debrisResources as $n=>$rc) $a[$i]['debris'][$rc] = 0;
        $before_a[$i] = [];
        foreach ($fleetmap as $ii=>$gid) { 
            $before_a[$i][$gid] = isset ($res['before']['attackers'][$i]['units'][$gid]) ? $res['before']['attackers'][$i]['units'][$gid] : 0;
        }
    }
    foreach ($d as $i=>$defender) {
        $d[$i]['debris'] = [];
        foreach ($debrisResources as $n=>$rc) $d[$i]['debris'][$rc] = 0;
        $before_d[$i] = [];
        foreach ($fleetmap as $ii=>$gid) { 
            $before_d[$i][$gid] = isset ($res['before']['defenders'][$i]['units'][$gid]) ? $res['before']['defenders'][$i]['units'][$gid] : 0;
        }
        foreach ($defmap_norak as $ii=>$gid) { 
            $before_d[$i][$gid] = isset ($res['before']['defenders'][$i]['units'][$gid]) ? $res['before']['defenders'][$i]['units'][$gid] : 0;
        }
    }

    // If there are rounds, get a list of units after the battle and subtract them from the pre-battle units to determine losses. Take into account the restored defense.

    $rounds = count ( $res['rounds'] );
    if ($rounds > 0) {
        $last = $res['rounds'][$rounds - 1];

        foreach ( $last['attackers'] as $i=>$attacker )        // Attackers
        {
            foreach ( $before_a[$i] as $gid=>$initial )
            {
                if ($initial > 0) {
                    $amount = isset($attacker['units'][$gid]) ? $attacker['units'][$gid] : 0;
                    $diff = max (0, $initial - $amount);
                    $cost = TechPrice ($gid, 1);
                    $coef = IsDefense($gid) ? $did : $fid;
                    foreach ($debrisResources as $n=>$rc) {
                        $a[$i]['debris'][$rc] += intval (ceil($cost[$rc] * $diff * ((float)( $coef / 100.0)) ));
                    }
                }
            }
        }

        foreach ( $last['defenders'] as $i=>$defender )        // Defenders
        {
            foreach ( $before_d[$i] as $gid=>$initial )
            {
                if ($initial) {
                    $amount = isset($defender['units'][$gid]) ? $defender['units'][$gid] : 0;
                    if ( IsDefense($gid) && $defender['pf'] == BATTLE_PTCP_PLANET ) $amount += $repaired[$i][$gid];      // Repaired
                    $diff = max (0, $initial - $amount);
                    $cost = TechPrice ($gid, 1);
                    $coef = IsDefense($gid) ? $did : $fid;
                    foreach ($debrisResources as $n=>$rc) {
                        $d[$i]['debris'][$rc] += intval (ceil($cost[$rc] * $diff * ((float)( $coef / 100.0)) ));
                    }
                }
            }
        }
    }
}

function GetDebrisTotal (array &$a, array &$d) : array {

    global $debrisResources;

    foreach ($debrisResources as $i=>$rc) {
        $debris[$rc] = 0;
    }

    foreach ($a as $i=>$attacker) {
        foreach ($debrisResources as $ii=>$rc) {
            $debris[$rc] += $attacker['debris'][$rc];
        }
    }

    foreach ($d as $i=>$defender) {
        foreach ($debrisResources as $ii=>$rc) {
            $debris[$rc] += $defender['debris'][$rc];
        }
    }

    return $debris;
}

// Total cargo capacity of fleets in the last round.
function CargoSummaryLastRound ( array $a, array $res ) : int
{
    global $transportableResources;
    $cargo = 0;
    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];

        foreach ( $last['attackers'] as $i=>$attacker )        // Attackers
        {
            $f = LoadFleet ( $attacker['id'] );
            $total = 0;
            foreach ($transportableResources as $i=>$rc) $total += $f[$rc];
            $cargo += FleetCargoSummary ( $attacker['units'] ) - $total - $f['fuel'];
        }
    }
    else
    {
        foreach ($a as $i=>$attacker)                // Attackers
        {
            $f = LoadFleet ( $attacker['id'] );
            $total = 0;
            foreach ($transportableResources as $i=>$rc) $total += $f[$rc];
            $cargo += FleetCargoSummary ( $attacker['units'] ) - $total - $f['fuel'];
        }
    }
    return (int)max ( 0, $cargo );
}

// Modify fleets and planet (add/remove resources, return attack fleets if ships remain)
function WritebackBattleResults ( array $a, array $d, array $res, array $repaired, array $captured, int $sum_cargo ) : void
{
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);
    global $db_prefix;
    global $transportableResources;

    // Combat with rounds.

    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];        

        foreach ( $last['attackers'] as $i=>$attacker )        // Attackers
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $queue = GetFleetQueue ($fleet_obj['fleet_id']);
            $origin = LoadPlanetById ( $fleet_obj['start_planet'] );
            if ($origin == null) {
                Error ("WritebackBattleResults origin null");
            }
            $target = LoadPlanetById ( $fleet_obj['target_planet'] );
            if ($target == null) {
                Error ("WritebackBattleResults target null");
            }
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker['units'][$gid];
            if ( $sum_cargo == 0) $cargo = 0;
            else $cargo = ( FleetCargoSummary ( $attacker['units'] ) - ($fleet_obj[GID_RC_METAL]+$fleet_obj[GID_RC_CRYSTAL]+$fleet_obj[GID_RC_DEUTERIUM]) - $fleet_obj['fuel'] ) / $sum_cargo;
            if ($ships > 0) {
                if ( $fleet_obj['mission'] == FTYP_DESTROY && $res['result'] === "awon" ) $result = GravitonAttack ( $fleet_obj, $attacker['units'], $queue['end'] );
                else $result = 0;
                if ( $result < 2 ) {
                    $resources = array ();
                    foreach ($transportableResources as $i=>$rc) {
                        $resources[$rc] = $fleet_obj[$rc] + $captured[$rc] * $cargo;
                    }
                    DispatchFleet ($attacker['units'], $origin, $target, $fleet_obj['mission']+FTYP_RETURN, $fleet_obj['flight_time'],
                    $resources,
                    (int)($fleet_obj['fuel'] / 2), $queue['end']);
                }
            }
        }

        foreach ( $last['defenders'] as $i=>$defender )        // Defenders
        {
            switch ($defender['pf']) {

                case BATTLE_PTCP_PLANET:        // Planet
                    AdjustResources ( $captured, $defender['id'], '-' );
                    $objects = array ();
                    foreach ( $fleetmap as $ii=>$gid ) $objects[$gid] = $defender['units'][$gid];
                    foreach ( $defmap_norak as $ii=>$gid ) {
                        $objects[$gid] = $repaired[$i][$gid];
                        $objects[$gid] += $defender['units'][$gid];
                    }
                    SetPlanetFleetDefense ( $defender['id'], $objects );
                    break;

                case BATTLE_PTCP_FLEET:     // Fleets on hold
                    $ships = 0;
                    foreach ( $fleetmap as $ii=>$gid ) $ships += $defender['units'][$gid];
                    if ( $ships > 0 ) SetFleet ( $defender['id'], $defender['units'] );
                    else {
                        $queue = GetFleetQueue ($defender['id']);
                        DeleteFleet ($defender['id']);    // delete fleet
                        RemoveQueue ( $queue['task_id'] );    // delete task
                    }
                    break;
            }
        }
    }

    // Combat with no rounds.

    else 
    {
        foreach ( $a as $i=>$attacker )            // Attackers
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $queue = GetFleetQueue ($fleet_obj['fleet_id']);
            $origin = LoadPlanetById ( $fleet_obj['start_planet'] );
            if ($origin == null) {
                Error ("WritebackBattleResults origin null");
            }
            $target = LoadPlanetById ( $fleet_obj['target_planet'] );
            if ($target == null) {
                Error ("WritebackBattleResults target null");
            }
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker['units'][$gid];
            if ( $sum_cargo == 0) $cargo = 0;
            else $cargo = ( FleetCargoSummary ( $attacker['units'] ) - ($fleet_obj[GID_RC_METAL]+$fleet_obj[GID_RC_CRYSTAL]+$fleet_obj[GID_RC_DEUTERIUM]) - $fleet_obj['fuel'] ) / $sum_cargo;
            if ($ships > 0) {
                if ( $fleet_obj['mission'] == FTYP_DESTROY && $res['result'] === "awon" ) $result = GravitonAttack ( $fleet_obj, $attacker['units'], $queue['end'] );
                else $result = 0;
                if ( $result < 2 ) {
                    $resources = array ();
                    foreach ($transportableResources as $i=>$rc) {
                        $resources[$rc] = $fleet_obj[$rc] + $captured[$rc] * $cargo;
                    }
                    DispatchFleet ($attacker['units'], $origin, $target, $fleet_obj['mission']+FTYP_RETURN, $fleet_obj['flight_time'],
                    $resources,
                    (int)($fleet_obj['fuel'] / 2), $queue['end']);
                }
            }
        }

        // Modify resources on the attacked planet if the attacker wins. Nothing else needs to be done, as this is the only possible option without rounds.

        foreach ( $d as $i=>$defender )        // Defenders
        {
            if ( $i == 0 && $res['result'] == 'awon')    // Planet
            {
                AdjustResources ( $captured, $defender['id'], '-' );
            }
        }

    }
}

function GenBattleSourceData (array $a, array $d, int $rf, int $max_round) : string
{
    global $UnitParam;
    global $RapidFire;

    $source = "";
    $source .= "MaxRound = $max_round\n";
    $source .= "Rapidfire = $rf\n";

    if ($rf) {
        $source .= "RFTab =";
        foreach ($RapidFire as $gid=>$targets) {
            $target_num = count ($targets);
            $source .= " " . $gid . " " . $target_num;
            foreach ($targets as $target_id=>$count) {
                $source .= " " . $target_id . " " . $count;
            }
        }
        $source .= "\n";
    }

    $source .= "UnitParam =";
    foreach ($UnitParam as $gid=>$param) {
        $source .= " " . $gid;
        foreach ($param as $i=>$val) {
            $source .= " " . $val;
        }
    }
    $source .= "\n";

    $anum = count ($a);
    $dnum = count ($d);

    $source .= "Attackers = $anum\n";
    $source .= "Defenders = $dnum\n";

    foreach ($a as $num=>$attacker)
    {
        $source .= "Attacker".$num." = ";
        $source .= $attacker[GID_R_WEAPON] . " " . $attacker[GID_R_SHIELD] . " " . $attacker[GID_R_ARMOUR];
        foreach ($attacker['units'] as $gid=>$amount) $source .= " " . $gid . " " . $amount;
        $source .= "\n";
    }
    foreach ($d as $num=>$defender)
    {
        $source .= "Defender".$num." = ";
        $source .= $defender[GID_R_WEAPON] . " " . $defender[GID_R_SHIELD] . " " . $defender[GID_R_ARMOUR];
        foreach ($defender['units'] as $gid=>$amount) $source .= " " . $gid . " " . $amount;
        $source .= "\n";
    }

    return $source;
}

// Extend some properties from the initial conditions to the results of the battle outcome.
function PostProcessBattleResult (array $a, array $d, array &$res) : void {

    foreach ($res['before']['attackers'] as $i=>$attacker) {
        $res['before']['attackers'][$i]['name'] = $a[$i]['oname'];
        $res['before']['attackers'][$i]['g'] = $a[$i]['g'];
        $res['before']['attackers'][$i]['s'] = $a[$i]['s'];
        $res['before']['attackers'][$i]['p'] = $a[$i]['p'];
        $res['before']['attackers'][$i]['id'] = $a[$i]['id'];
        $res['before']['attackers'][$i]['pf'] = $a[$i]['pf'];
    }

    foreach ($res['before']['defenders'] as $i=>$defender) {
        $res['before']['defenders'][$i]['name'] = $d[$i]['oname'];
        $res['before']['defenders'][$i]['g'] = $d[$i]['g'];
        $res['before']['defenders'][$i]['s'] = $d[$i]['s'];
        $res['before']['defenders'][$i]['p'] = $d[$i]['p'];
        $res['before']['defenders'][$i]['id'] = $d[$i]['id'];
        $res['before']['defenders'][$i]['pf'] = $d[$i]['pf'];
    }

    foreach ($res['rounds'] as $n=>$round) {

        foreach ($round['attackers'] as $i=>$attacker) {
            $res['rounds'][$n]['attackers'][$i]['name'] = $a[$i]['oname'];
            $res['rounds'][$n]['attackers'][$i]['g'] = $a[$i]['g'];
            $res['rounds'][$n]['attackers'][$i]['s'] = $a[$i]['s'];
            $res['rounds'][$n]['attackers'][$i]['p'] = $a[$i]['p'];
            $res['rounds'][$n]['attackers'][$i]['id'] = $a[$i]['id'];
            $res['rounds'][$n]['attackers'][$i]['pf'] = $a[$i]['pf'];
        }

        foreach ($round['defenders'] as $i=>$defender) {
            $res['rounds'][$n]['defenders'][$i]['name'] = $d[$i]['oname'];
            $res['rounds'][$n]['defenders'][$i]['g'] = $d[$i]['g'];
            $res['rounds'][$n]['defenders'][$i]['s'] = $d[$i]['s'];
            $res['rounds'][$n]['defenders'][$i]['p'] = $d[$i]['p'];
            $res['rounds'][$n]['defenders'][$i]['id'] = $d[$i]['id'];
            $res['rounds'][$n]['defenders'][$i]['pf'] = $d[$i]['pf'];
        }
    }

    $res['extra'] = [];

    ModsExecRef ('battle_post_process', $res);
}

/**
 * @brief Executes a battle between two forces and processes the results.
 *
 * This function manages the battle execution pipeline. It first writes battle data to a file,
 * then transfers control to a battle engine (either a PHP function or an external executable backend),
 * and finally reads and post-processes the battle results.
 *
 * @param array $unitab Configuration array containing battle engine settings.
 *                      Expected keys:
 *                      - 'php_battle': bool - If true, use internal PHP battle engine.
 *                      - 'battle_engine': string - Path to external battle engine executable.
 * @param int $battle_id Unique identifier for the battle. Used for naming battle data and result files.
 * @param string $source Serialized battle data to be passed to the battle engine.
 * @param array $a Array containing data for the attacking force.
 * @param array $d Array containing data for the defending force.
 *
 * @return array The processed battle results as an associative array.
 *
 * @throws Error If the external battle engine returns a negative exit code.
 *
 * @note The battle data is serialized and stored in `battledata/battle_<id>.txt`.
 * @note The battle results are serialized and stored in `battleresult/battle_<id>.txt`.
 * @note The function assumes the existence of `PostProcessBattleResult()` for final result processing.
 *
 * @warning File paths are constructed relative to the current working directory.
 */
function ExecuteBattle (array $unitab, int $battle_id, string $source, array $a, array $d) : array {

    $bf = fopen ( "battledata/battle_".$battle_id.".txt", "w" );
    fwrite ( $bf, $source );
    fclose ( $bf );

    // *** Transfer data to the battle engine

    if ($unitab['php_battle']) {

        $battle_source = file_get_contents ( "battledata/battle_".$battle_id.".txt" );
        $res = BattleEngine ($battle_source);

        $bf = fopen ( "battleresult/battle_".$battle_id.".txt", "w" );
        fwrite ( $bf, serialize($res) );
        fclose ( $bf );
    }
    else {

        $arg = "$battle_id 0";
        system ( $unitab['battle_engine'] . " $arg", $retval );
        if ($retval < 0) {
            Error (va("An error occurred in the battle engine: #1 #2", $retval, $battle_id));
        }
    }

    // *** Process output data

    $battleres = file_get_contents ( "battleresult/battle_".$battle_id.".txt" );
    $res = unserialize($battleres);
    PostProcessBattleResult ($a, $d, $res);

    return $res;
}

// Start a battle between attacking fleet_id and defending planet_id.
function StartBattle ( int $fleet_id, int $planet_id, int $when ) : int
{
    global $db_prefix;
    global $GlobalUni;
    global $fleetmap;
    global $defmap;
    global $rakmap;
    global $transportableResources;
    $defmap_norak = array_diff($defmap, $rakmap);

    $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );
    $d_result = array ( 1=>"combatreport_igotattacked_iwon", 0=>"combatreport_igotattacked_ilost", 2=>"combatreport_igotattacked_draw" );

    $a = array ();
    $d = array ();

    $unitab = LoadUniverse ();
    $fid = $unitab['fid'];
    $did = $unitab['did'];
    $rf = $unitab['rapid'];

    $f = LoadFleet ( $fleet_id );

    // *** Generate source data

    // List of attackers
    $anum = 0;
    if ( $f['union_id'] == 0 )    // Single attack
    {
        $a[0] = LoadUser ( $f['owner_id'] );
        $a[0]['units'] = array ();
        foreach ($fleetmap as $i=>$gid) $a[0]['units'][$gid] = abs($f[$gid]);
        $start_planet = LoadPlanetById ( $f['start_planet'] );
        $a[0]['g'] = $start_planet['g'];
        $a[0]['s'] = $start_planet['s'];
        $a[0]['p'] = $start_planet['p'];
        $a[0]['id'] = $fleet_id;
        $a[0]['pf'] = BATTLE_PTCP_FLEET;    // fleet
        $a[0]['points'] = $a[0]['fpoints'] = 0;
        $anum++;
    }
    else        // Cooperative attack (ACS)
    {
        $result = EnumUnionFleets ( $f['union_id'] );
        $rows = dbrows ($result);
        while ($rows--)
        {
            $fleet_obj = dbarray ($result);

            $a[$anum] = LoadUser ( $fleet_obj['owner_id'] );
            $a[$anum]['units'] = array ();
            foreach ($fleetmap as $i=>$gid) $a[$anum]['units'][$gid] = abs($fleet_obj[$gid]);
            $start_planet = LoadPlanetById ( $fleet_obj['start_planet'] );
            $a[$anum]['g'] = $start_planet['g'];
            $a[$anum]['s'] = $start_planet['s'];
            $a[$anum]['p'] = $start_planet['p'];
            $a[$anum]['id'] = $fleet_obj['fleet_id'];
            $a[$anum]['pf'] = BATTLE_PTCP_FLEET;    // fleet  
            $a[$anum]['points'] = $a[$anum]['fpoints'] = 0;

            $anum++;
        }
    }

    // List of defenders
    $dnum = 0;
    $p = LoadPlanetById ( $planet_id );
    $d[0] = LoadUser ( $p['owner_id'] );
    $d[0]['units'] = array ();
    foreach ($fleetmap as $i=>$gid) {
        if (isset($p[$gid])) {
            $d[0]['units'][$gid] = abs($p[$gid]);
        }
    }
    foreach ($defmap_norak as $i=>$gid) {
        if (isset($p[$gid])) {
            $d[0]['units'][$gid] = abs($p[$gid]);
        }
    }
    $d[0]['g'] = $p['g'];
    $d[0]['s'] = $p['s'];
    $d[0]['p'] = $p['p'];
    $d[0]['id'] = $planet_id;
    $d[0]['pf'] = BATTLE_PTCP_PLANET;    // planet
    $d[0]['points'] = $d[0]['fpoints'] = 0;
    $dnum++;

    // Fleets on hold (ACS)
    $result = GetHoldingFleets ($planet_id);
    $rows = dbrows ($result);
    while ($rows--)
    {
        $fleet_obj = dbarray ($result);

        $d[$dnum] = LoadUser ( $fleet_obj['owner_id'] );
        $d[$dnum]['units'] = array ();
        foreach ($fleetmap as $i=>$gid) $d[$dnum]['units'][$gid] = abs($fleet_obj[$gid]);
        $start_planet = LoadPlanetById ( $fleet_obj['start_planet'] );
        $d[$dnum]['g'] = $start_planet['g'];
        $d[$dnum]['s'] = $start_planet['s'];
        $d[$dnum]['p'] = $start_planet['p'];
        $d[$dnum]['id'] = $fleet_obj['fleet_id'];
        $d[$dnum]['pf'] = BATTLE_PTCP_FLEET;    // fleet  
        $d[$dnum]['points'] = $d[$dnum]['fpoints'] = 0;

        $dnum++;
    }

    $source = GenBattleSourceData ($a, $d, $rf, BATTLE_MAX_ROUND);

    $battle = array ( 'source' => $source, 'title' => "", 'report' => "", 'date' => $when );
    $battle_id = AddDBRow ( $battle, "battledata" );

    $res = ExecuteBattle ($unitab, $battle_id, $source, $a, $d);

    // Determine the outcome of the battle.
    if ( $res['result'] === "awon" ) $battle_result = BATTLE_RESULT_AWON;
    else if ( $res['result'] === "dwon" ) $battle_result = BATTLE_RESULT_DWON;
    else $battle_result = BATTLE_RESULT_DRAW;

    // Restore the defense
    $repaired = RepairDefense ( $d, $res, $unitab['defrepair'], $unitab['defrepair_delta'] );

    // Calculate total losses (account for deuterium and repaired defenses)
    $loss = CalcLosses ( $a, $d, $res, $repaired );
    $aloss = $loss['aloss'];
    $dloss = $loss['dloss'];

    // Calc debris drop
    CalcDebris ( $a, $d, $res, $repaired, $fid, $did );
    $debris = GetDebrisTotal ($a, $d);

    // Capture resources
    $captured = array ();
    $sum_cargo = 0;
    if ( $battle_result == BATTLE_RESULT_AWON )
    {
        $sum_cargo = CargoSummaryLastRound ( $a, $res );
        $captured = Plunder ( $sum_cargo, $p[GID_RC_METAL], $p[GID_RC_CRYSTAL], $p[GID_RC_DEUTERIUM] );
    }
    else {
        foreach ($transportableResources as $i=>$rc) {
            $captured[$rc] = 0;
        }
    }

    // Create a debris field.
    $debris_id = CreateDebris ( $p['g'], $p['s'], $p['p'], $p['owner_id'] );
    AddDebris ( $debris_id, $debris[GID_RC_METAL], $debris[GID_RC_CRYSTAL] );

    // Create the moon
    $mooncreated = false;
    $moonchance = min ( floor ( ($debris[GID_RC_METAL] + $debris[GID_RC_CRYSTAL]) / 100000), 20 );
    if ( PlanetHasMoon ( $planet_id ) || $p['type'] == PTYP_MOON || $p['type'] == PTYP_DEST_MOON ) $moonchance = 0;
    if ( mt_rand (1, 100) <= $moonchance ) {
        CreatePlanet ( $p['g'], $p['s'], $p['p'], $p['owner_id'], 0, 1, $moonchance );
        $mooncreated = true;
    }

    // Update the activity on the planet.
    $queue = GetFleetQueue ( $fleet_id );
    UpdatePlanetActivity ( $planet_id, $queue['end'] );

    // This array contains a cache of generated battle reports for each language.
    $battle_text = array();

    // Generate a battle report in the universe language (for log history)
    $text = BattleReport ( $res, $when, $loss, $captured, $moonchance, $mooncreated, $repaired, $debris, $GlobalUni['lang'] );
    $battle_text[$GlobalUni['lang']] = $text;

    // Send out messages, mailbox is used to avoid sending multiple messages to ACS players.
    $mailbox = array ();

    foreach ( $d as $i=>$user )        // Defenders
    {
        // Generate a battle report in the user's language if it is not in the cache
        if (key_exists($user['lang'], $battle_text)) $text = $battle_text[$user['lang']];
        else {
            $text = BattleReport ( $res, $when, $loss, $captured, $moonchance, $mooncreated, $repaired, $debris, $user['lang'] );
            $battle_text[$user['lang']] = $text;
        }

        loca_add ( "fleetmsg", $user['lang'] );

        if ( key_exists($user['player_id'], $mailbox) ) continue;
        $bericht = SendMessage ( $user['player_id'], loca_lang("FLEET_MESSAGE_FROM", $user['lang']), loca_lang("FLEET_MESSAGE_BATTLE", $user['lang']), $text, MTYP_BATTLE_REPORT_TEXT, $when );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$d_result[$battle_result]."\">" .
            loca_lang("FLEET_MESSAGE_BATTLE", $user['lang']) .
            " [".$p['g'].":".$p['s'].":".$p['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        SendMessage ( $user['player_id'], loca_lang("FLEET_MESSAGE_FROM", $user['lang']), $subj, "", MTYP_BATTLE_REPORT_LINK, $when );
        $mailbox[ $user['player_id'] ] = true;
    }

    // Update the battle report log (use the universe language battle report)
    loca_add ( "fleetmsg", $GlobalUni['lang'] );
    $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=admin&session={PUBLIC_SESSION}&mode=BattleReport&bericht=$battle_id\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">" .
        loca_lang("FLEET_MESSAGE_BATTLE", $GlobalUni['lang']) .
        " [".$p['g'].":".$p['s'].":".$p['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
    $query = "UPDATE ".$db_prefix."battledata SET title = '".$subj."', report = '".$battle_text[$GlobalUni['lang']]."' WHERE battle_id = $battle_id;";
    dbquery ( $query );

    foreach ( $a as $i=>$user )        // Attackers
    {
        // Generate a battle report in the user's language if it is not in the cache
        if (key_exists($user['lang'], $battle_text)) $text = $battle_text[$user['lang']];
        else {
            $text = BattleReport ( $res, $when, $loss, $captured, $moonchance, $mooncreated, $repaired, $debris, $user['lang'] );
            $battle_text[$user['lang']] = $text;
        }

        // If fleet is destroyed in 1 or 2 rounds - do not show battle log for attackers.
        if ( count($res['rounds']) <= 2 && $battle_result == BATTLE_RESULT_DWON ) $text = loca_lang("BATTLE_LOST", $user['lang']) . " <!--A:$aloss,W:$dloss-->";

        loca_add ( "fleetmsg", $user['lang'] );

        if ( key_exists($user['player_id'], $mailbox) ) continue;
        $bericht = SendMessage ( $user['player_id'], loca_lang("FLEET_MESSAGE_FROM", $user['lang']), loca_lang("FLEET_MESSAGE_BATTLE", $user['lang']), $text, MTYP_BATTLE_REPORT_TEXT, $when );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">" .
            loca_lang("FLEET_MESSAGE_BATTLE", $user['lang']) .
            " [".$p['g'].":".$p['s'].":".$p['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        SendMessage ( $user['player_id'], loca_lang("FLEET_MESSAGE_FROM", $user['lang']), $subj, "", MTYP_BATTLE_REPORT_LINK, $when );
        $mailbox[ $user['player_id'] ] = true;
    }

    // Clean up old battle reports
    $ago = $when - 2 * 7 * 24 * 60 * 60;
    $query = "DELETE FROM ".$db_prefix."battledata WHERE date < $ago;";
    dbquery ($query);

    // Modify fleets and planet according to losses and captured resources
    WritebackBattleResults ( $a, $d, $res, $repaired, $captured, $sum_cargo );

    // Change player statistics
    foreach ( $a as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    foreach ( $d as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    RecalcRanks ();

    // Cleaning up the battle engine's intermediate data
    unlink ( "battledata/battle_".$battle_id.".txt" );
    unlink ( "battleresult/battle_".$battle_id.".txt" );

    return $battle_result;
}

?>