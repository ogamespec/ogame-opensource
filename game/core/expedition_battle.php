<?php

require_once "battle_engine.php";

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
            $origin = LoadPlanetById ( $fleet_obj['start_planet'] );
            $target = LoadPlanetById ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker['units'][$gid];

            // Return the fleet, if there's anything left.
            // The hold time is used as the flight time.
            if ($ships > 0) DispatchFleet ($attacker['units'], $origin, $target, FTYP_EXPEDITION+FTYP_RETURN, $fleet_obj['deploy_time'],
                $fleet_obj,
                $fleet_obj['fuel'] / 2, $queue['end']);
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
            $target = LoadPlanetById ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker['units'][$gid];

            // Return the fleet, if there's anything left.
            // The hold time is used as the flight time.
            if ($ships > 0)  DispatchFleet ($attacker['units'], $origin, $target, FTYP_EXPEDITION+FTYP_RETURN, $fleet_obj['deploy_time'],
                $fleet_obj,
                $fleet_obj['fuel'] / 2, $queue['end']);
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
        $text .= GenSlot ( $attacker['weap'], $attacker['shld'], $attacker['armr'], $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker['units'], 1, 1, $lang );
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
            $text .= GenSlot ( 0, 0, 0, $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender['units'], 0, 0, $lang );
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
    $a[0]['units'] = array ();
    foreach ($fleetmap as $i=>$gid) $a[0]['units'][$gid] = abs($f[$gid]);
    $start_planet = LoadPlanetById ( $f['start_planet'] );
    $a[0]['g'] = $start_planet['g'];
    $a[0]['s'] = $start_planet['s'];
    $a[0]['p'] = $start_planet['p'];
    $a[0]['id'] = $fleet_id;
    $a[0]['pf'] = 0;        // fleet
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
    $d[0]['units'] = array ();
    foreach ($fleetmap as $i=>$gid) {        // Determine the composition of the pirate / alien fleet

        if ( $pirates ) {
            // Pirate Fleet, rounding down the fleet composition.
            // Normal - 30% +/- 3% of the number of ships in your fleet + 5 LFs
            // Strong - 50% +/- 5% of the number of ships in your fleet + 3 Cruisers
            // Very Strong - 80% +/- 8% of the number of ships in your fleet + 2 Battleships

            if ( $a[0]['units'][$gid] > 0 )
            {
                if ( $level == 0 ) $ratio = mt_rand ( 27, 33 ) / 100;
                else if ( $level == 1 ) $ratio = mt_rand ( 45, 55 ) / 100;
                else if ( $level == 2 ) $ratio = mt_rand ( 72, 88 ) / 100;
                $d[0]['units'][$gid] = floor ($a[0]['units'][$gid] * $ratio);
            }
            else $d[0]['units'][$gid] = 0;
        }
        else {
            // Alien fleet, rounding fleet composition up.
            // Normal - 40% +/- 4% of the number of ships in your fleet + 5 Heavy Fighters
            // Strong - 60% +/- 6% of the number of ships in your fleet + 3 Battlecruisers
            // Very Strong - 90% +/- 9% of the number of ships in your fleet + 2 Destros

            if ( $a[0]['units'][$gid] > 0 )
            {
                if ( $level == 0 ) $ratio = mt_rand ( 36, 44 ) / 100;
                else if ( $level == 1 ) $ratio = mt_rand ( 54, 66 ) / 100;
                else if ( $level == 2 ) $ratio = mt_rand ( 81, 99 ) / 100;
                $d[0]['units'][$gid] = ceil ($a[0]['units'][$gid] * $ratio);
            }
            else $d[0]['units'][$gid] = 0;
        }

    }

    if ( $pirates ) {
        if ( $level == 0 ) $d[0]['units'][GID_F_LF] += 5;
        else if ( $level == 1 ) $d[0]['units'][GID_F_CRUISER] += 3;
        else if ( $level == 2 ) $d[0]['units'][GID_F_BATTLESHIP] += 2;
    }
    else {
        if ( $level == 0 ) $d[0]['units'][GID_F_HF] += 5;
        else if ( $level == 1 ) $d[0]['units'][GID_F_BATTLECRUISER] += 3;
        else if ( $level == 2 ) $d[0]['units'][GID_F_DESTRO] += 2;
    }

    $target_planet = LoadPlanetById ( $f['target_planet'] );
    $d[0]['g'] = $target_planet['g'];
    $d[0]['s'] = $target_planet['s'];
    $d[0]['p'] = $target_planet['p'];
    $d[0]['id'] = $target_planet['planet_id'];
    $d[0]['pf'] = 1;    // planet
    $d[0]['points'] = $d[0]['fpoints'] = 0;
    $dnum++;

    $source = GenBattleSourceData ($a, $d, $rf, BATTLE_MAX_ROUND);

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

    // Determine the outcome of the battle.
    if ( $res['result'] === "awon" ) $battle_result = BATTLE_RESULT_AWON;
    else if ( $res['result'] === "dwon" ) $battle_result = BATTLE_RESULT_DWON;
    else $battle_result = BATTLE_RESULT_DRAW;

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
        if ( count($res['rounds']) <= 2 && $battle_result == BATTLE_RESULT_DWON ) $text = loca_lang("BATTLE_LOST", $user['lang']) . " <!--A:$aloss,W:$dloss-->";

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