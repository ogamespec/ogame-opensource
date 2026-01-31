<?php

// Generate the HTML code of a single slot.
function GenSlot ( int $weap, int $shld, int $armor, string $name, int $g, int $s, int $p, array $unitmap, array $units, bool $show_techs, bool $attack, string $lang ) : string
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
        if (isset($units[$gid])) {
            $sum += $units[$gid];
        }
    }

    if ( $sum > 0 )
    {
        $text .= "<table border=1>";

        $text .= "<tr><th>".loca_lang("BATTLE_TYPE", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if (isset($units[$gid])) {
                $n = $units[$gid];
                if ( $n > 0 ) $text .= "<th>".loca_lang("SNAME_$gid", $lang)."</th>";
            }
        }
        $text .= "</tr>";

        $text .= "<tr><th>".loca_lang("BATTLE_AMOUNT", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if (isset($units[$gid])) {
                $n = $units[$gid];
                if ( $n > 0 ) $text .= "<th>".nicenum($n)."</th>";
            }
        }
        $text .= "</tr>";

        $text .= "<tr><th>".loca_lang("BATTLE_WEAP", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if (isset($units[$gid])) {
                $n = $units[$gid];
                if ( $n > 0 ) $text .= "<th>".nicenum( $UnitParam[$gid][2] * (10 + $weap ) / 10 )."</th>";
            }
        }
        $text .= "</tr>";

        $text .= "<tr><th>".loca_lang("BATTLE_SHLD", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if (isset($units[$gid])) {
                $n = $units[$gid];
                if ( $n > 0 ) $text .= "<th>".nicenum( $UnitParam[$gid][1] * (10 + $shld ) / 10 )."</th>";
            }
        }
        $text .= "</tr>";

        $text .= "<tr><th>".loca_lang("BATTLE_ARMR", $lang)."</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if (isset($units[$gid])) {
                $n = $units[$gid];
                if ( $n > 0 ) $text .= "<th>".nicenum( $UnitParam[$gid][0] * (10 + $armor ) / 100 )."</th>";
            }
        }
        $text .= "</tr>";

        $text .= "</table>";
    }
    else $text .= "<br>" . loca_lang("BATTLE_DESTROYED", $lang);

    $text .= "</center></th>";
    return $text;
}

// Generate a battle report.
function BattleReport ( array $res, int $now, array|null $loss, array|null $captured, int $moonchance, bool $mooncreated, array|null $repaired, array|null $debris, string $lang ) : string
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
        $text .= GenSlot ( $attacker['weap'], $attacker['shld'], $attacker['armr'], $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker['units'], 1, 1, $lang );
    }
    $text .= "</tr></table>";
    $text .= "<table border=1 width=100%><tr>";
    foreach ( $res['before']['defenders'] as $i=>$defender)
    {
        $text .= GenSlot ( $defender['weap'], $defender['shld'], $defender['armr'], $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender['units'], 1, 0, $lang );
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
            $text .= GenSlot ( 0, 0, 0, $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker['units'], 0, 1, $lang );
        }
        $text .= "</tr></table>";

        $text .= "<table border=1 width=100%><tr>";        // Defenders
        foreach ( $round['defenders'] as $n=>$defender )
        {
            if ( $defender['pf'] == 1 ) $text .= GenSlot ( 0, 0, 0, $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender['units'], 0, 0, $lang );
            else $text .= GenSlot ( 0, 0, 0, $defender['name'], $defender['g'], $defender['s'], $defender['p'], $amap, $defender['units'], 0, 0, $lang );
        }
        $text .= "</tr></table>";
    }

    // Battle Results.
    // TODO: Add a loss label that is in the HTML: <!--A:167658,W:167658-->
    if ( $res['result'] === "awon" )
    {
        $text .= "<p> " . loca_lang("BATTLE_AWON", $lang);
        if ($captured) {
            $text .= "<br>" . va(loca_lang("BATTLE_PLUNDER", $lang), nicenum($captured[GID_RC_METAL]), nicenum($captured[GID_RC_CRYSTAL]), nicenum($captured[GID_RC_DEUTERIUM]));
        }
    }
    else if ( $res['result'] === "dwon" ) $text .= "<p> " . loca_lang("BATTLE_DWON", $lang);
    else if ( $res['result'] === "draw" ) $text .= "<p> " . loca_lang("BATTLE_DRAW", $lang);

    if ($loss) {
        $text .= "<br><p><br>".va(loca_lang("BATTLE_ALOSS", $lang), nicenum($loss['aloss']))."<br>" . va(loca_lang("BATTLE_DLOSS", $lang), nicenum($loss['dloss']));
    }
    if ($debris) {
        $text .= "<br>" . va(loca_lang("BATTLE_DEBRIS", $lang), nicenum($debris[GID_RC_METAL]), nicenum($debris[GID_RC_CRYSTAL]));
    }
    if ( $moonchance ) $text .= "<br>" . va(loca_lang("BATTLE_MOONCHANCE", $lang), $moonchance);
    if ( $mooncreated ) $text .= "<br>" . loca_lang("BATTLE_MOON", $lang);

    if ($repaired) {
        
        // Repairing the Defense.
        // There is an error in the output of the original battle report: the Small Shield Dome is not output in its turn, but before the Plasma Cannon.
        // To be as similar as possible to the original report, the RepairMap permutation table is used in the output of the repaired defense.
        $repairmap = array ( GID_D_RL, GID_D_LL, GID_D_HL, GID_D_GAUSS, GID_D_ION, GID_D_SDOME, GID_D_PLASMA, GID_D_LDOME );

        foreach ( $res['before']['defenders'] as $i=>$defender) {
            if ($defender['pf'] == 0) continue;     // not planet

            $repaired_num = 0;
            foreach ($repaired[$i] as $gid=>$amount) $repaired_num += $amount;
            if ( $repaired_num > 0)
            {
                $text .= "<br>";
                $need_comma = false;
                foreach ($repairmap as $i=>$gid)
                {
                    if ($repaired[$i][$gid])
                    {
                        if ( $need_comma ) $text .= ", ";
                        $text .= nicenum ($repaired[$i][$gid]) . " " . loca_lang ("NAME_$gid", $lang);
                        $need_comma = true;
                    }
                }
                if ($repaired_num > 1) {
                    $text .= loca_lang("BATTLE_REPAIRED", $lang);
                }
                else {
                    $text .= loca_lang("BATTLE_REPAIRED1", $lang);
                }
                $text .= "<br>";
            }
        }
    }

    return $text;
}

?>