<?php

require_once "battle_engine.php";
require_once "raketen.php";

// OGame Battle Engine frontend.

// Repairing the defense.
function RepairDefense ( array $d, array $res, int $defrepair, int $defrepair_delta, bool $premium=true ) : array
{
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);
    foreach ( $defmap_norak as $n=>$gid ) {
        $repaired[$gid] = 0;
        $exploded[$gid] = 0;
    }
    $exploded_total = 0;

    if ( $premium) $prem = PremiumStatus ($d[0]);
    else $prem = array();

    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        // Count the blown defenses.
        $last = $res['rounds'][$rounds - 1];
        foreach ( $exploded as $gid=>$amount )
        {
            $exploded[$gid] = $d[0]['defense'][$gid] - $last['defenders'][0][$gid];
            if ( key_exists ('engineer', $prem) && $prem['engineer'] ) $exploded[$gid] = floor ($exploded[$gid] / 2);
            $exploded_total += $exploded[$gid];
        }

        // Restore the defense
        if ($exploded_total)
        {
            foreach ( $exploded as $gid=>$amount )
            {
                if ( $amount < 10 )
                {
                    for ($i=0; $i<$amount; $i++)
                    {
                        if ( mt_rand (0, 99) < $defrepair ) $repaired[$gid]++;
                    }
                }
                else $repaired[$gid] = floor ( mt_rand ($defrepair-$defrepair_delta, $defrepair+$defrepair_delta) * $amount / 100 );
            }
        }
    }

    return $repaired;
}

// Capture resources.
function Plunder ( int $cargo, int $m, int $k, int $d ) : array
{
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

    $res = array ( 'cm' => floor($mc), 'ck' => floor($kc), 'cd' => floor($dc) );
    return $res;
}

// Calculate total losses (taking into account repaired defenses).
function CalcLosses ( array $a, array $d, array $res, array $repaired ) : array
{
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);
    $amap = $fleetmap;
    $dmap = array_merge($fleetmap, $defmap_norak);

    $aprice = $dprice = 0;

    // The cost of units before combat.
    foreach ($a as $i=>$attacker)                // Attackers
    {
        $a[$i]['points'] = 0;
        $a[$i]['fpoints'] = 0;
        foreach ( $fleetmap as $n=>$gid )
        {
            $amount = $attacker['fleet'][$gid];
            if ( $amount > 0 ) {
                $cost = ShipyardPrice ( $gid  );
                $aprice += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $a[$i]['points'] += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $a[$i]['fpoints'] += $amount;
            }
        }
    }

    foreach ($d as $i=>$defender)            // Defenders
    {
        $d[$i]['points'] = 0;
        $d[$i]['fpoints'] = 0;
        foreach ( $fleetmap as $n=>$gid )
        {
            $amount = $defender['fleet'][$gid];
            if ( $amount > 0 ) {
                $cost = ShipyardPrice ( $gid );
                $dprice += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $d[$i]['points'] += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $d[$i]['fpoints'] += $amount;
            }
        }
        foreach ( $defmap_norak as $n=>$gid )
        {
            $amount = $defender['defense'][$gid];
            if ( $amount > 0 ) {
                $cost = ShipyardPrice ( $gid );
                $dprice += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $d[$i]['points'] += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
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
            foreach ( $amap as $n=>$gid )
            {
                $amount = $attacker[$gid];
                if ( $amount > 0 ) {
                    $cost = ShipyardPrice ( $gid );
                    $alast += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                    $a[$i]['points'] -= ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                    $a[$i]['fpoints'] -= $amount;
                }
            }
        }

        foreach ( $last['defenders'] as $i=>$defender )        // Defenders
        {
            foreach ( $dmap as $n=>$gid )
            {
                if ( IsDefense($gid) && $i == 0 ) $amount = $defender[$gid] + $repaired[$gid];
                else $amount = $defender[$gid];
                if ( $amount > 0 ) {
                    $cost = ShipyardPrice ( $gid );
                    $dlast += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                    $d[$i]['points'] -= ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
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

    return array ( 'a' => $a, 'd' => $d, 'aloss' => $aloss, 'dloss' => $dloss );
}

// Total cargo capacity of fleets in the last round.
function CargoSummaryLastRound ( array $a, array $res ) : int
{
    $cargo = 0;
    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];

        foreach ( $last['attackers'] as $i=>$attacker )        // Attackers
        {
            $f = LoadFleet ( $attacker['id'] );
            $cargo += FleetCargoSummary ( $attacker ) - ($f['m'] + $f['k'] + $f['d']) - $f['fuel'];
        }
    }
    else
    {
        foreach ($a as $i=>$attacker)                // Attackers
        {
            $f = LoadFleet ( $attacker['id'] );
            $cargo += FleetCargoSummary ( $attacker['fleet'] ) - ($f['m'] + $f['k'] + $f['d']) - $f['fuel'];
        }
    }
    return (int)max ( 0, $cargo );
}

// Modify fleets and planet (add/remove resources, return attack fleets if ships remain)
function WritebackBattleResults ( array $a, array $d, array $res, array $repaired, int $cm, int $ck, int $cd, int $sum_cargo ) : void
{
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);
    global $db_prefix;

    // Combat with rounds.

    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];        

        foreach ( $last['attackers'] as $i=>$attacker )        // Attackers
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $queue = GetFleetQueue ($fleet_obj['fleet_id']);
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            if ($origin == null) {
                Error ("WritebackBattleResults origin null");
            }
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            if ($target == null) {
                Error ("WritebackBattleResults target null");
            }
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker[$gid];
            if ( $sum_cargo == 0) $cargo = 0;
            else $cargo = ( FleetCargoSummary ( $attacker ) - ($fleet_obj['m']+$fleet_obj['k']+$fleet_obj['d']) - $fleet_obj['fuel'] ) / $sum_cargo;
            if ($ships > 0) {
                if ( $fleet_obj['mission'] == FTYP_DESTROY && $res['result'] === "awon" ) $result = GravitonAttack ( $fleet_obj, $attacker, $queue['end'] );
                else $result = 0;
                if ( $result < 2 ) DispatchFleet ($attacker, $origin, $target, $fleet_obj['mission']+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m']+$cm * $cargo, $fleet_obj['k']+$ck * $cargo, $fleet_obj['d']+$cd * $cargo, $fleet_obj['fuel'] / 2, $queue['end']);
            }
        }

        foreach ( $last['defenders'] as $i=>$defender )        // Defenders
        {
            if ( $i == 0 )    // Planet
            {
                AdjustResources ( $cm, $ck, $cd, $defender['id'], '-' );
                $objects = array ();
                foreach ( $fleetmap as $ii=>$gid ) $objects[$gid] = $defender[$gid] ? $defender[$gid] : 0;
                foreach ( $defmap_norak as $ii=>$gid ) {
                    $objects[$gid] = $repaired[$gid] ? $repaired[$gid] : 0;
                    $objects[$gid] += $defender[$gid];
                }
                SetPlanetFleetDefense ( $defender['id'], $objects );
            }
            else        // Fleets on hold
            {
                $ships = 0;
                foreach ( $fleetmap as $ii=>$gid ) $ships += $defender[$gid];
                if ( $ships > 0 ) SetFleet ( $defender['id'], $defender );
                else {
                    $queue = GetFleetQueue ($defender['id']);
                    DeleteFleet ($defender['id']);    // delete fleet
                    RemoveQueue ( $queue['task_id'] );    // delete task
                }
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
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            if ($origin == null) {
                Error ("WritebackBattleResults origin null");
            }
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            if ($target == null) {
                Error ("WritebackBattleResults target null");
            }
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker['fleet'][$gid];
            if ( $sum_cargo == 0) $cargo = 0;
            else $cargo = ( FleetCargoSummary ( $attacker['fleet'] ) - ($fleet_obj['m']+$fleet_obj['k']+$fleet_obj['d']) - $fleet_obj['fuel'] ) / $sum_cargo;
            if ($ships > 0) {
                if ( $fleet_obj['mission'] == FTYP_DESTROY && $res['result'] === "awon" ) $result = GravitonAttack ( $fleet_obj, $attacker['fleet'], $queue['end'] );
                else $result = 0;
                if ( $result < 2 ) DispatchFleet ($attacker['fleet'], $origin, $target, $fleet_obj['mission']+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m']+$cm * $cargo, $fleet_obj['k']+$ck * $cargo, $fleet_obj['d']+$cd * $cargo, $fleet_obj['fuel'] / 2, $queue['end']);
            }
        }

        // Modify resources on the attacked planet if the attacker wins. Nothing else needs to be done, as this is the only possible option without rounds.

        foreach ( $d as $i=>$defender )        // Defenders
        {
            if ( $i == 0 && $res['result'] == 'awon')    // Planet
            {
                AdjustResources ( $cm, $ck, $cd, $defender['id'], '-' );
            }
        }

    }
}

// Generate the HTML code of a single slot.
function GenSlot ( int $weap, int $shld, int $armor, string $name, int $g, int $s, int $p, array $unitmap, array $fleet, array|null $defense, bool $show_techs, bool $attack, string $lang ) : string
{
    global $UnitParam;

    $text = "<th><br>";

    $text .= "<center>";
    if ($attack) $text .= loca_lang("BATTLE_ATTACKER", $lang);
    else $text .= loca_lang("BATTLE_DEFENDER", $lang);
    $text .= " ".$name." (<a href=# onclick=showGalaxy($g,$s,$p); >[$g:$s:$p]</a>)";
    if ($show_techs) $text .= "<br>".loca_lang("BATTLE_ATTACK", $lang)." ".($weap * 10)."% ".loca_lang("BATTLE_SHIELD", $lang)." ".($shld * 10)."% ".loca_lang("BATTLE_ARMOR", $lang)." ".($armor * 10)."% ";

    $sum = 0;
    foreach ( $unitmap as $i=>$gid )
    {
            if ( IsDefense($gid) ) $sum += $defense[$gid];
            else $sum += $fleet[$gid];
    }

    if ( $sum > 0 )
    {
        $text .= "<table border=1>";

        $text .= "<tr><th>".loca_lang("BATTLE_TYPE", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( IsDefense($gid) ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".loca_lang("SNAME_$gid", $lang)."</th>";
        }
        $text .= "</tr>";

        $text .= "<tr><th>".loca_lang("BATTLE_AMOUNT", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( IsDefense($gid) ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".nicenum($n)."</th>";
        }
        $text .= "</tr>";

        $text .= "<tr><th>".loca_lang("BATTLE_WEAP", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( IsDefense($gid) ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".nicenum( $UnitParam[$gid][2] * (10 + $weap ) / 10 )."</th>";
        }
        $text .= "</tr>";

        $text .= "<tr><th>".loca_lang("BATTLE_SHLD", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( IsDefense($gid) ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".nicenum( $UnitParam[$gid][1] * (10 + $shld ) / 10 )."</th>";
        }
        $text .= "</tr>";

        $text .= "<tr><th>".loca_lang("BATTLE_ARMR", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( IsDefense($gid) ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".nicenum( $UnitParam[$gid][0] * (10 + $armor ) / 100 )."</th>";
        }
        $text .= "</tr>";

        $text .= "</table>";
    }
    else $text .= "<br>" . loca_lang("BATTLE_DESTROYED", $lang);

    $text .= "</center></th>";
    return $text;
}

// Generate a battle report.
function BattleReport ( array $res, int $now, int $aloss, int $dloss, int $cm, int $ck, int $cd, int $moonchance, bool $mooncreated, array $repaired, string $lang ) : string
{
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);
    $amap = $fleetmap;
    $dmap = array_merge ($fleetmap, $defmap_norak);

    loca_add ( "battlereport", $lang );
    loca_add ( "technames", $lang );

    $text = "";

    // Title of the report.
    // In vanilla 0.84 the header of the battle report was slightly different. For example, in en it says "At" for the attacker and "On" for the defender.
    // We will not engage in such perversions. We consider all battle reports to be from the attacker.
    $text .= va(loca_lang("BATTLE_ADATE_INFO", $lang), date ("m-d H:i:s", $now)) . ":<br>";

    // Fleets before the battle.
    $text .= "<table border=1 width=100%><tr>";
    foreach ( $res['before']['attackers'] as $i=>$attacker)
    {
        $text .= GenSlot ( $attacker['weap'], $attacker['shld'], $attacker['armr'], $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker, null, 1, 1, $lang );
    }
    $text .= "</tr></table>";
    $text .= "<table border=1 width=100%><tr>";
    foreach ( $res['before']['defenders'] as $i=>$defender)
    {
        $text .= GenSlot ( $defender['weap'], $defender['shld'], $defender['armr'], $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender, $defender, 1, 0, $lang );
    }
    $text .= "</tr></table>";

    // Rounds.
    foreach ( $res['rounds'] as $i=>$round)
    {
        $text .= "<br><center>";
        $text .= va (loca_lang("BATTLE_ASHOT", $lang), nicenum($round['ashoot']), nicenum($round['apower']), nicenum($round['dabsorb']) );
        $text .= "<br>";
        $text .= va (loca_lang("BATTLE_DSHOT", $lang), nicenum($round['dshoot']), nicenum($round['dpower']), nicenum($round['aabsorb']) );
        $text .= "</center>";

        $text .= "<table border=1 width=100%><tr>";        // Attackers
        foreach ( $round['attackers'] as $n=>$attacker )
        {
            $text .= GenSlot ( 0, 0, 0, $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker, null, 0, 1, $lang );
        }
        $text .= "</tr></table>";

        $text .= "<table border=1 width=100%><tr>";        // Defenders
        foreach ( $round['defenders'] as $n=>$defender )
        {
            if ( $n == 0 ) $text .= GenSlot ( 0, 0, 0, $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender, $defender, 0, 0, $lang );
            else $text .= GenSlot ( 0, 0, 0, $defender['name'], $defender['g'], $defender['s'], $defender['p'], $amap, $defender, null, 0, 0, $lang );
        }
        $text .= "</tr></table>";
    }

    // Battle Results.
    // TODO: Add a loss label that is in the HTML: <!--A:167658,W:167658-->
    if ( $res['result'] === "awon" )
    {
        $text .= "<p> ".loca_lang("BATTLE_AWON", $lang)."<br>" . va(loca_lang("BATTLE_PLUNDER", $lang), nicenum($cm), nicenum($ck), nicenum($cd));
    }
    else if ( $res['result'] === "dwon" ) $text .= "<p> " . loca_lang("BATTLE_DWON", $lang);
    else if ( $res['result'] === "draw" ) $text .= "<p> " . loca_lang("BATTLE_DRAW", $lang);
    //else Error ("Неизвестный исход битвы!");
    $text .= "<br><p><br>".va(loca_lang("BATTLE_ALOSS", $lang), nicenum($aloss))."<br>" . va(loca_lang("BATTLE_DLOSS", $lang), nicenum($dloss));
    $text .= "<br>" . va(loca_lang("BATTLE_DEBRIS", $lang), nicenum($res['dm']), nicenum($res['dk']));
    if ( $moonchance ) $text .= "<br>" . va(loca_lang("BATTLE_MOONCHANCE", $lang), $moonchance);
    if ( $mooncreated ) $text .= "<br>" . loca_lang("BATTLE_MOON", $lang);

    // Repairing the Defense.
    // There is an error in the output of the original battle report: the Small Shield Dome is not output in its turn, but before the Plasma Cannon.
    // To be as similar as possible to the original report, the RepairMap permutation table is used in the output of the repaired defense.
    $repairmap = array ( GID_D_RL, GID_D_LL, GID_D_HL, GID_D_GAUSS, GID_D_ION, GID_D_SDOME, GID_D_PLASMA, GID_D_LDOME );
    $repaired_num = $sum = 0;
    foreach ($repaired as $gid=>$amount) $repaired_num += $amount;
    if ( $repaired_num > 0)
    {
        $text .= "<br>";
        foreach ($repairmap as $i=>$gid)
        {
            if ($repaired[$gid])
            {
                if ( $sum > 0 ) $text .= ", ";
                $text .= nicenum ($repaired[$gid]) . " " . loca_lang ("NAME_$gid", $lang);
                $sum += $repaired[$gid];
            }
        }
        if ($sum > 1) {
            $text .= loca_lang("BATTLE_REPAIRED", $lang);
        }
        else {
            $text .= loca_lang("BATTLE_REPAIRED1", $lang);
        }
        $text .= "<br>";
    }

    return $text;
}

// Moon attack.
// Returns the result encoded in 2 bits: bit0 - the moon is destroyed, bit1 - Deathstar exploded with the whole fleet
function GravitonAttack (array $fleet_obj, array $fleet, int $when) : int
{
    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );

    if ( $fleet[GID_F_DEATHSTAR] == 0 ) return 0;
    if ( ! ($target['type'] == PTYP_MOON || $target['type'] == PTYP_DEST_MOON) ) Error ( "Уничтожать можно только луны!" );

    $diam = $target['diameter'];
    $rips = $fleet[GID_F_DEATHSTAR];
    $moonchance = (100 - sqrt($diam)) * sqrt($rips);
    if ($moonchance >= 100) $moonchance = 99.9;
    $ripchance = sqrt ($diam) / 2;
    $moondes =  mt_rand(1, 999) < $moonchance * 10;
    $ripdes = mt_rand(1, 999) < $ripchance * 10;

    $origin_user = LoadUser ($origin['owner_id']);
    $target_user = LoadUser ($target['owner_id']);
    loca_add ( "graviton", $origin_user['lang'] );
    loca_add ( "graviton", $target_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );
    loca_add ( "fleetmsg", $target_user['lang'] );

    if ( !$ripdes && !$moondes )
    {
            $atext = va ( loca_lang("GRAVITON_ATK_00", $origin_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( loca_lang("GRAVITON_DEF_00", $target_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", 
                                floor ($moonchance), floor ($ripchance)
                             );
            $result  = 0;
    }

    else if ( !$ripdes && $moondes )
    {
            $atext = va ( loca_lang("GRAVITON_ATK_01", $origin_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( loca_lang("GRAVITON_DEF_01", $target_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                             );

            DestroyMoon ( $target['planet_id'], $when, $fleet_obj['fleet_id'] );
            $result  = 1;
    }

    else if ( $ripdes && !$moondes )
    {
            $atext = va ( loca_lang("GRAVITON_ATK_10", $origin_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( loca_lang("GRAVITON_DEF_10", $target_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                             );
            $result  = 2;
    }

    else if ( $ripdes && $moondes )
    {
            $atext = va ( loca_lang("GRAVITON_ATK_11", $origin_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( loca_lang("GRAVITON_DEF_11", $target_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                             );

            DestroyMoon ( $target['planet_id'], $when, $fleet_obj['fleet_id'] );
            $result  = 3;
    }

    // Recalculate stats if a fleet was blown up by a failed graviton attack
    if ($result >= 2) {

        $price = FleetPrice ( $fleet_obj );
        AdjustStats ( $fleet_obj['owner_id'], $price['points'], $price['fpoints'], 0, '-' );
        RecalcRanks ();
    }

    // Send out messages.
    SendMessage ( $origin['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("GRAVITON_ATK_SUBJ", $origin_user['lang']),
        $atext, MTYP_MISC, $when);
    SendMessage ( $target['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $target_user['lang']),
        loca_lang("GRAVITON_DEF_SUBJ", $target_user['lang']),
        $dtext, MTYP_MISC, $when);

    return $result;
}

function GenBattleSourceData (array $a, array $d, int $rf, int $fid, int $did) : string
{
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);

    $source = "";
    $source .= "Rapidfire = $rf\n";
    $source .= "FID = $fid\n";
    $source .= "DID = $did\n";

    $anum = count ($a);
    $dnum = count ($d);

    $source .= "Attackers = $anum\n";
    $source .= "Defenders = $dnum\n";

    foreach ($a as $num=>$attacker)
    {
        $source .= "Attacker".$num." = ({".$attacker['oname']."} ";
        $source .= $attacker['id'] . " ";
        $source .= $attacker['g'] . " " . $attacker['s'] . " " . $attacker['p'] . " ";
        $source .= $attacker[GID_R_WEAPON] . " " . $attacker[GID_R_SHIELD] . " " . $attacker[GID_R_ARMOUR] . " ";
        foreach ($fleetmap as $i=>$gid) $source .= $attacker['fleet'][$gid] . " ";
        $source .= ")\n";
    }
    foreach ($d as $num=>$defender)
    {
        $source .= "Defender".$num." = ({".$defender['oname']."} ";
        $source .= $defender['id'] . " ";
        $source .= $defender['g'] . " " . $defender['s'] . " " . $defender['p'] . " ";
        $source .= $defender[GID_R_WEAPON] . " " . $defender[GID_R_SHIELD] . " " . $defender[GID_R_ARMOUR] . " ";
        foreach ($fleetmap as $i=>$gid) $source .= $defender['fleet'][$gid] . " ";
        foreach ($defmap_norak as $i=>$gid) $source .= $defender['defense'][$gid] . " ";
        $source .= ")\n";
    }

    return $source;
}

// Start a battle between attacking fleet_id and defending planet_id.
function StartBattle ( int $fleet_id, int $planet_id, int $when ) : int
{
    global $db_prefix;
    global $GlobalUni;
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);

    $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );
    $d_result = array ( 1=>"combatreport_igotattacked_iwon", 0=>"combatreport_igotattacked_ilost", 2=>"combatreport_igotattacked_draw" );

    global  $db_host, $db_user, $db_pass, $db_name, $db_prefix;
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
        $a[0]['fleet'] = array ();
        foreach ($fleetmap as $i=>$gid) $a[0]['fleet'][$gid] = abs($f[$gid]);
        $start_planet = GetPlanet ( $f['start_planet'] );
        $a[0]['g'] = $start_planet['g'];
        $a[0]['s'] = $start_planet['s'];
        $a[0]['p'] = $start_planet['p'];
        $a[0]['id'] = $fleet_id;
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
            $a[$anum]['fleet'] = array ();
            foreach ($fleetmap as $i=>$gid) $a[$anum]['fleet'][$gid] = abs($fleet_obj[$gid]);
            $start_planet = GetPlanet ( $fleet_obj['start_planet'] );
            $a[$anum]['g'] = $start_planet['g'];
            $a[$anum]['s'] = $start_planet['s'];
            $a[$anum]['p'] = $start_planet['p'];
            $a[$anum]['id'] = $fleet_obj['fleet_id'];
            $a[$anum]['points'] = $a[$anum]['fpoints'] = 0;

            $anum++;
        }
    }

    // List of defenders
    $dnum = 0;
    $p = GetPlanet ( $planet_id );
    $d[0] = LoadUser ( $p['owner_id'] );
    $d[0]['fleet'] = array ();
    $d[0]['defense'] = array ();
    foreach ($fleetmap as $i=>$gid) $d[0]['fleet'][$gid] = abs($p[$gid]);
    foreach ($defmap_norak as $i=>$gid) $d[0]['defense'][$gid] = abs($p[$gid]);
    $d[0]['g'] = $p['g'];
    $d[0]['s'] = $p['s'];
    $d[0]['p'] = $p['p'];
    $d[0]['id'] = $planet_id;
    $d[0]['points'] = $d[0]['fpoints'] = 0;
    $dnum++;

    // Fleets on hold (ACS)
    $result = GetHoldingFleets ($planet_id);
    $rows = dbrows ($result);
    while ($rows--)
    {
        $fleet_obj = dbarray ($result);

        $d[$dnum] = LoadUser ( $fleet_obj['owner_id'] );
        $d[$dnum]['fleet'] = array ();
        $d[$dnum]['defense'] = array ();
        foreach ($fleetmap as $i=>$gid) $d[$dnum]['fleet'][$gid] = abs($fleet_obj[$gid]);
        foreach ($defmap_norak as $i=>$gid) $d[$dnum]['defense'][$gid] = 0;
        $start_planet = GetPlanet ( $fleet_obj['start_planet'] );
        $d[$dnum]['g'] = $start_planet['g'];
        $d[$dnum]['s'] = $start_planet['s'];
        $d[$dnum]['p'] = $start_planet['p'];
        $d[$dnum]['id'] = $fleet_obj['fleet_id'];
        $d[$dnum]['points'] = $d[$dnum]['fpoints'] = 0;

        $dnum++;
    }

    $source = GenBattleSourceData ($a, $d, $rf, $fid, $did);

    $battle = array ( 'source' => $source, 'title' => "", 'report' => "", 'date' => $when );
    $battle_id = AddDBRow ( $battle, "battledata" );

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

        $arg = "\"battle_id=$battle_id\"";
        system ( $unitab['battle_engine'] . " $arg", $retval );
        if ($retval < 0) {
            Error (va("Ошибка в работе боевого движка: #1 #2", $retval, $battle_id));
        }
    }

    // *** Process output data

    $battleres = file_get_contents ( "battleresult/battle_".$battle_id.".txt" );
    $res = unserialize($battleres);

    // Determine the outcome of the battle.
    if ( $res['result'] === "awon" ) $battle_result = 0;
    else if ( $res['result'] === "dwon" ) $battle_result = 1;
    else $battle_result = 2;

    // Restore the defense
    $repaired = RepairDefense ( $d, $res, $unitab['defrepair'], $unitab['defrepair_delta'] );

    // Calculate total losses (account for deuterium and repaired defenses)
    $aloss = $dloss = 0;
    $loss = CalcLosses ( $a, $d, $res, $repaired );
    $a = $loss['a'];
    $d = $loss['d'];
    $aloss = $loss['aloss'];
    $dloss = $loss['dloss'];

    // Capture resources
    $cm = $ck = $cd = $sum_cargo = 0;
    if ( $battle_result == 0 )
    {
        $sum_cargo = CargoSummaryLastRound ( $a, $res );
        $captured = Plunder ( $sum_cargo, $p['m'], $p['k'], $p['d'] );
        $cm = $captured['cm']; $ck = $captured['ck']; $cd = $captured['cd'];
    }

    // Create a debris field.
    $debris_id = CreateDebris ( $p['g'], $p['s'], $p['p'], $p['owner_id'] );
    AddDebris ( $debris_id, $res['dm'], $res['dk'] );

    // Create the moon
    $mooncreated = false;
    $moonchance = min ( floor ( ($res['dm'] + $res['dk']) / 100000), 20 );
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
    $text = BattleReport ( $res, $when, $aloss, $dloss, $cm, $ck, $cd, $moonchance, $mooncreated, $repaired, $GlobalUni['lang'] );
    $battle_text[$GlobalUni['lang']] = $text;

    // Send out messages, mailbox is used to avoid sending multiple messages to ACS players.
    $mailbox = array ();

    foreach ( $d as $i=>$user )        // Defenders
    {
        // Generate a battle report in the user's language if it is not in the cache
        if (key_exists($user['lang'], $battle_text)) $text = $battle_text[$user['lang']];
        else {
            $text = BattleReport ( $res, $when, $aloss, $dloss, $cm, $ck, $cd, $moonchance, $mooncreated, $repaired, $user['lang'] );
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
            $text = BattleReport ( $res, $when, $aloss, $dloss, $cm, $ck, $cd, $moonchance, $mooncreated, $repaired, $user['lang'] );
            $battle_text[$user['lang']] = $text;
        }

        // If fleet is destroyed in 1 or 2 rounds - do not show battle log for attackers.
        if ( count($res['rounds']) <= 2 && $battle_result == 1 ) $text = loca_lang("BATTLE_LOST", $user['lang']) . " <!--A:$aloss,W:$dloss-->";

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
    WritebackBattleResults ( $a, $d, $res, $repaired, $cm, $ck, $cd, $sum_cargo );

    // Change player statistics
    foreach ( $a as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    foreach ( $d as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    RecalcRanks ();

    // Cleaning up the battle engine's intermediate data
    unlink ( "battledata/battle_".$battle_id.".txt" );
    unlink ( "battleresult/battle_".$battle_id.".txt" );

    return $battle_result;
}

// -----------------------------------------------------------------------------------------------------------------------------------------------------------

// Modify the fleet (after a battle with aliens/pirates)
function WritebackBattleResultsExpedition ( array $a, array $d, array $res ) : void
{
    global $fleetmap;

    // Combat with rounds.

    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];        

        foreach ( $last['attackers'] as $i=>$attacker )        // Attackers
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $queue = GetFleetQueue ($fleet_obj['fleet_id']);
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker[$gid];

            // Return the fleet, if there's anything left.
            // The hold time is used as the flight time.
            if ($ships > 0) DispatchFleet ($attacker, $origin, $target, FTYP_EXPEDITION+FTYP_RETURN, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
        }

    }

    // Combat with no rounds.

    else 
    {
        foreach ( $a as $i=>$attacker )            // Attackers
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $queue = GetFleetQueue ($fleet_obj['fleet_id']);
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker['fleet'][$gid];

            // Return the fleet, if there's anything left.
            // The hold time is used as the flight time.
            if ($ships > 0)  DispatchFleet ($attacker['fleet'], $origin, $target, FTYP_EXPEDITION+FTYP_RETURN, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
        }

    }
}

// Generate short battle report.
function ShortBattleReport ( array $res, int $now, string $lang ) : string
{
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);
    $amap = $fleetmap;
    $dmap = array_merge($fleetmap, $defmap_norak);

    loca_add ( "battlereport", $lang );
    loca_add ( "technames", $lang );

    $text = "";

    // Title of the report.
    // In vanilla 0.84 the header of the battle report was slightly different. For example, in en it says "At" for the attacker and "On" for the defender.
    // We will not engage in such perversions. We consider all battle reports to be from the attacker.  
    $text .= va(loca_lang("BATTLE_ADATE_INFO", $lang), date ("m-d H:i:s", $now)) . ":<br>";

    // Fleets before the battle.
    $text .= "<table border=1 width=100%><tr>";
    foreach ( $res['before']['attackers'] as $i=>$attacker)
    {
        $text .= GenSlot ( $attacker['weap'], $attacker['shld'], $attacker['armr'], $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker, null, 1, 1, $lang );
    }
    $text .= "</tr></table>";
    $text .= "<table border=1 width=100%><tr>";
    foreach ( $res['before']['defenders'] as $i=>$defender)
    {
        $user = array ();
        $user['fleet'] = array ();
        $user['defense'] = array ();
        foreach ($fleetmap as $g=>$gid) $user['fleet'][$gid] = $defender[$gid];
        foreach ($defmap as $g=>$gid) $user['defense'][$gid] = 0;
        $text .= GenSlot ( $defender['weap'], $defender['shld'], $defender['armr'], $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender, $defender, 1, 0, $lang );
    }
    $text .= "</tr></table>";

    // Раунды.
    foreach ( $res['rounds'] as $i=>$round)
    {
        $text .= "<br><center>";
        $text .= va (loca_lang("BATTLE_ASHOT", $lang), nicenum($round['ashoot']), nicenum($round['apower']), nicenum($round['dabsorb']) );
        $text .= "<br>";
        $text .= va (loca_lang("BATTLE_DSHOT", $lang), nicenum($round['dshoot']), nicenum($round['dpower']), nicenum($round['aabsorb']) );
        $text .= "</center>";

        $text .= "<table border=1 width=100%><tr>";        // Attackers
        foreach ( $round['attackers'] as $n=>$attacker )
        {
            $text .= GenSlot ( 0, 0, 0, $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker, null, 0, 1, $lang );
        }
        $text .= "</tr></table>";

        $text .= "<table border=1 width=100%><tr>";        // Defenders
        foreach ( $round['defenders'] as $n=>$defender )
        {
            $text .= GenSlot ( 0, 0, 0, $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender, $defender, 0, 0, $lang );
        }
        $text .= "</tr></table>";
    }

    // Battle Results.
    // TODO: Add a loss label that is in the HTML: <!--A:167658,W:167658-->
    if ( $res['result'] === "awon" ) $text .= "<p> " . loca_lang("BATTLE_AWON", $lang);
    else if ( $res['result'] === "dwon" ) $text .= "<p> " . loca_lang("BATTLE_DWON", $lang);
    else if ( $res['result'] === "draw" ) $text .= "<p> " . loca_lang("BATTLE_DRAW", $lang);

    return $text;
}

// Battle with Aliens/Pirates.
// The composition of the Alien/Pirate fleet is determined by the level parameter ( 0: weak, 1: medium, 2: strong )
function ExpeditionBattle ( int $fleet_id, bool $pirates, int $level, int $when ) : int
{
    global $db_prefix;
    global $GlobalUni;
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);

    $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );

    global  $db_host, $db_user, $db_pass, $db_name, $db_prefix;
    $a = array ();
    $d = array ();

    $unitab = LoadUniverse ();
    $fid = $unitab['fid'];
    $did = $unitab['did'];
    $rf = $unitab['rapid'];

    // *** Union attacks should not enter the battle. Ignore them.
    $f = LoadFleet ( $fleet_id );

    // *** Generate source data

    // List of attackers
    $anum = 0;
    $a[0] = LoadUser ( $f['owner_id'] );
    $a[0]['fleet'] = array ();
    foreach ($fleetmap as $i=>$gid) $a[0]['fleet'][$gid] = abs($f[$gid]);
    $start_planet = GetPlanet ( $f['start_planet'] );
    $a[0]['g'] = $start_planet['g'];
    $a[0]['s'] = $start_planet['s'];
    $a[0]['p'] = $start_planet['p'];
    $a[0]['id'] = $fleet_id;
    $a[0]['points'] = $a[0]['fpoints'] = 0;
    $anum++;

    // List of defenders
    $dnum = 0;
    $d[0] = LoadUser ( USER_SPACE );
    if ( $pirates ) {
        $d[0]['oname'] = "Piraten";
        $d[0][GID_R_WEAPON] = max (0, $a[0][GID_R_WEAPON] - 3);
        $d[0][GID_R_SHIELD] = max (0, $a[0][GID_R_SHIELD] - 3);
        $d[0][GID_R_ARMOUR] = max (0, $a[0][GID_R_ARMOUR] - 3);
    }
    else {
        $d[0]['oname'] = "Aliens";
        $d[0][GID_R_WEAPON] = $a[0][GID_R_WEAPON] + 3;
        $d[0][GID_R_SHIELD] = $a[0][GID_R_SHIELD] + 3;
        $d[0][GID_R_ARMOUR] = $a[0][GID_R_ARMOUR] + 3;
    }
    $d[0]['fleet'] = array ();
    $d[0]['defense'] = array ();
    foreach ($fleetmap as $i=>$gid) {        // Determine the composition of the pirate / alien fleet

        if ( $pirates ) {
            // Pirate Fleet, rounding down the fleet composition.
            // Normal - 30% +/- 3% of the number of ships in your fleet + 5 LFs
            // Strong - 50% +/- 5% of the number of ships in your fleet + 3 Cruisers
            // Very Strong - 80% +/- 8% of the number of ships in your fleet + 2 Battleships

            if ( $a[0]['fleet'][$gid] > 0 )
            {
                if ( $level == 0 ) $ratio = mt_rand ( 27, 33 ) / 100;
                else if ( $level == 1 ) $ratio = mt_rand ( 45, 55 ) / 100;
                else if ( $level == 2 ) $ratio = mt_rand ( 72, 88 ) / 100;
                $d[0]['fleet'][$gid] = floor ($a[0]['fleet'][$gid] * $ratio);
            }
            else $d[0]['fleet'][$gid] = 0;
        }
        else {
            // Alien fleet, rounding fleet composition up.
            // Normal - 40% +/- 4% of the number of ships in your fleet + 5 Heavy Fighters
            // Strong - 60% +/- 6% of the number of ships in your fleet + 3 Battlecruisers
            // Very Strong - 90% +/- 9% of the number of ships in your fleet + 2 Destros

            if ( $a[0]['fleet'][$gid] > 0 )
            {
                if ( $level == 0 ) $ratio = mt_rand ( 36, 44 ) / 100;
                else if ( $level == 1 ) $ratio = mt_rand ( 54, 66 ) / 100;
                else if ( $level == 2 ) $ratio = mt_rand ( 81, 99 ) / 100;
                $d[0]['fleet'][$gid] = ceil ($a[0]['fleet'][$gid] * $ratio);
            }
            else $d[0]['fleet'][$gid] = 0;
        }

    }

    if ( $pirates ) {
        if ( $level == 0 ) $d[0]['fleet'][GID_F_LF] += 5;
        else if ( $level == 1 ) $d[0]['fleet'][GID_F_CRUISER] += 3;
        else if ( $level == 2 ) $d[0]['fleet'][GID_F_BATTLESHIP] += 2;
    }
    else {
        if ( $level == 0 ) $d[0]['fleet'][GID_F_HF] += 5;
        else if ( $level == 1 ) $d[0]['fleet'][GID_F_BATTLECRUISER] += 3;
        else if ( $level == 2 ) $d[0]['fleet'][GID_F_DESTRO] += 2;
    }

    foreach ($defmap_norak as $i=>$gid) $d[0]['defense'][$gid] = 0;
    $target_planet = GetPlanet ( $f['target_planet'] );
    $d[0]['g'] = $target_planet['g'];
    $d[0]['s'] = $target_planet['s'];
    $d[0]['p'] = $target_planet['p'];
    $d[0]['id'] = $target_planet['planet_id'];
    $d[0]['points'] = $d[0]['fpoints'] = 0;
    $dnum++;

    $source = GenBattleSourceData ($a, $d, $rf, $fid, $did);

    $battle = array ( 'source' => $source, 'title' => "", 'report' => "", 'date' => $when );
    $battle_id = AddDBRow ( $battle, "battledata" );

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

        $arg = "\"battle_id=$battle_id\"";
        system ( $unitab['battle_engine'] . " $arg", $retval );
        if ($retval < 0) {
            Error (va("Ошибка в работе боевого движка: #1 #2", $retval, $battle_id));
        }
    }

    // *** Process output data

    $battleres = file_get_contents ( "battleresult/battle_".$battle_id.".txt" );
    $res = unserialize($battleres);

    // Determine the outcome of the battle.
    if ( $res['result'] === "awon" ) $battle_result = 0;
    else if ( $res['result'] === "dwon" ) $battle_result = 1;
    else $battle_result = 2;

    // Calculate total losses (account for deuterium and repaired defenses)
    $aloss = $dloss = 0;
    $repaired = array ( GID_D_RL=>0, GID_D_LL=>0, GID_D_HL=>0, GID_D_GAUSS=>0, GID_D_ION=>0, GID_D_PLASMA=>0, GID_D_SDOME=>0, GID_D_LDOME=>0 );
    $loss = CalcLosses ( $a, $d, $res, $repaired );
    $a = $loss['a'];
    $d = $loss['d'];
    $aloss = $loss['aloss'];
    $dloss = $loss['dloss'];

    // This array contains a cache of generated battle reports for each language.
    $battle_text = array();

    // Generate a battle report in the universe language (for log history)
    $text = ShortBattleReport ( $res, $when, $GlobalUni['lang'] );
    $battle_text[$GlobalUni['lang']] = $text;

    // Send out messages
    $mailbox = array ();

    foreach ( $a as $i=>$user )        // Attackers
    {
        // Generate a battle report in the user's language if it is not in the cache
        if (key_exists($user['lang'], $battle_text)) $text = $battle_text[$user['lang']];
        else {
            $text = ShortBattleReport ( $res, $when, $user['lang'] );
            $battle_text[$user['lang']] = $text;
        }

        // If fleet is destroyed in 1 or 2 rounds - do not show battle log for attackers.
        if ( count($res['rounds']) <= 2 && $battle_result == 1 ) $text = loca_lang("BATTLE_LOST", $user['lang']) . " <!--A:$aloss,W:$dloss-->";

        loca_add ( "fleetmsg", $user['lang'] );

        if ( key_exists($user['player_id'], $mailbox) ) continue;
        $bericht = SendMessage ( $user['player_id'], loca_lang("FLEET_MESSAGE_FROM", $user['lang']), loca_lang("FLEET_MESSAGE_BATTLE", $user['lang']), $text, MTYP_BATTLE_REPORT_TEXT, $when );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">" .
            loca_lang("FLEET_MESSAGE_BATTLE", $user['lang']) . 
            " [".$target_planet['g'].":".$target_planet['s'].":".$target_planet['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        SendMessage ( $user['player_id'], loca_lang("FLEET_MESSAGE_FROM", $user['lang']), $subj, "", MTYP_BATTLE_REPORT_LINK, $when );
        $mailbox[ $user['player_id'] ] = true;
    }

    // Update the battle report log
    loca_add ( "fleetmsg", $GlobalUni['lang'] );
    $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=admin&session={PUBLIC_SESSION}&mode=BattleReport&bericht=$battle_id\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">" .
        loca_lang("FLEET_MESSAGE_BATTLE", $GlobalUni['lang']) . 
        " [".$target_planet['g'].":".$target_planet['s'].":".$target_planet['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
    $query = "UPDATE ".$db_prefix."battledata SET title = '".$subj."', report = '".$text."' WHERE battle_id = $battle_id;";
    dbquery ( $query );

    // Clean up old battle reports
    $ago = $when - 2 * 7 * 24 * 60 * 60;
    $query = "DELETE FROM ".$db_prefix."battledata WHERE date < $ago;";
    dbquery ($query);

    // Modify the fleet
    WritebackBattleResultsExpedition ( $a, $d, $res );

    // Modify player statistics
    foreach ( $a as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    RecalcRanks ();

    // Cleaning up the battle engine's intermediate data
    unlink ( "battledata/battle_".$battle_id.".txt" );
    unlink ( "battleresult/battle_".$battle_id.".txt" );

    return $battle_result;
}

?>