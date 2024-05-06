<?php

// Creating a list of Phalanx events.
// TODO: There is some isomorphism with the Overview event module. If possible, unify the code for event list output.

function OverFleet ($fleet, $summary, $mission)
{
    $res = "<a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;";
    global $fleetmap;
    $sum = 0;
    if ( $summary ) {
        foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
        $res .= loca("EVENT_FLEET_COUNT") . ": $sum &lt;br&gt;";
    }
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet[$gid];
        if ( $amount > 0 ) $res .= loca ("NAME_$gid") . " " . nicenum($amount) . "&lt;br&gt;";
    }
    $res .= "&lt;/b&gt;&lt;/font&gt;\");' onmouseout='return nd();' class='".$mission."'>";
    return $res;
}

function TitleFleet ($fleet, $summary)
{
    global $fleetmap;
    $sum = 0;
    if ( $summary ) {
        foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
        $res = loca("EVENT_FLEET_COUNT") . ": $sum ";
    }
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet[$gid];
        if ( $amount > 0 ) $res .= loca ("NAME_$gid") . " " . nicenum($amount);
    }
    return $res;
}

function PlayerDetails ($user)
{
    return $user['oname'] . " <a href='#' onclick='showMessageMenu(".$user['player_id'].")'><img src='".UserSkin()."img/m.gif' title='".loca("EVENT_WRITE")."' alt='".loca("EVENT_WRITE")."'></a>";
}

function PlanetFrom ($planet, $mission)
{
    $res = "";
    if ( GetPlanetType ($planet) == 1 ) $res .= loca("EVENT_FROM_PLANET");
    if ( $planet['type'] == PTYP_COLONY_PHANTOM || $planet['type'] == PTYP_FARSPACE ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function PlanetTo ($planet, $mission)
{
    $res = "";
    if ( GetPlanetType ($planet) == 1 ) $res .= loca("EVENT_TO_PLANET");
    if ( $planet['type'] == PTYP_COLONY_PHANTOM || $planet['type'] == PTYP_FARSPACE ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function PlanetOn ($planet, $mission)
{
    $res = "";
    if ( $planet['type'] == PTYP_COLONY_PHANTOM || $planet['type'] == PTYP_FARSPACE ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function FleetSpan ( $fleet_entry )
{
    $mission = $fleet_entry['mission'];
    $origin = GetPlanet ( $fleet_entry['origin_id'] );
    $target = GetPlanet ( $fleet_entry['target_id'] );
    $fleet = $fleet_entry;
    $dir = $fleet_entry['dir'];
    $owner = LoadUser ( $origin['owner_id'] );

    if ( $mission == FTYP_ATTACK ) {    // Attack
        if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ATTACK")."</span>";
        else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_ATTACK")."</span></span>";
    }
    else if ( $mission == FTYP_ACS_ATTACK ) {    // ACS Attack
        if ( $dir == 0 ) echo "<span class='phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "federation"), PlanetTo($target, "federation")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK")."</span>";
        else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_ACS_ATTACK")."</span></span>";
    }
    else if ( $mission == FTYP_ACS_ATTACK_HEAD ) {    // ACS Attack Head fleet (slot 0)
        if ( $dir == 0 ) echo "<span class='phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "attack"), PlanetTo($target, "attack")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK_HEAD")."</span>";
        else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_ACS_ATTACK_HEAD")."</span></span>";
    }
    else if ( $mission == FTYP_TRANSPORT ) {    // Transport
        if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_TRANSPORT")."</span>";
        else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_TRANSPORT")."</span></span>";
    }
    else if ( $mission == FTYP_DEPLOY ) {    // Deploy
        echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_DEPLOY")."</span>";
    }
    else if ( $mission == FTYP_ACS_HOLD ) {    // ACS Hold
        if ( $dir == 2 ) echo "<span class='holding phalanx_fleet'>".va(loca("EVENT_FLEET_HOLD"),PlayerDetails($owner),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_TO_ORBIT"), PlanetFrom($origin, "phalanx_fleet"), PlanetOn($target, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_HOLD")."</span></span>";
        else if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_HOLD")."</span>";
        else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_HOLD")."</span></span>";
    }
    else if ( $mission == FTYP_SPY ) {    // Espionage
        if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_SPY")."</span>";
        else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_SPY")."</span></span>";
    }
    else if ( $mission == FTYP_COLONIZE ) {    // Colonize
        echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_COLONY")."</span></span>";
    }
    else if ( $mission == FTYP_RECYCLE ) {    // Recycle
        echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_RECYCLE")."</span></span>";
    }
    else if ( $mission == FTYP_DESTROY ) {    // Destroy (hmmm... how to see this on the phalanx is not clear, but we'll leave the code for unification.)
        if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_DESTROY")."</span>";
        else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_DESTROY")."</span></span>";
    }
    else if ( $mission == FTYP_EXPEDITION ) {    // Expedition
        if ( $dir == 2 ) echo "<span class='holding phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_EXPO_FROM_ONTO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_EXPO")."</span></span>";
        else if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_EXPO")."</span>";
        else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_EXPO")."</span></span>";
    }
    else if ($mission == FTYP_MISSILE ) {    // Missile attack
        echo "<span class='missile'>" .va(loca("EVENT_RAK"), $fleet_entry['ipm_amount'], PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")) . " ";
        if ( $fleet_entry['ipm_target'] > 0 ) echo loca("EVENT_RAK_TARGET") . " " . loca ("NAME_".$fleet_entry['ipm_target']);
        echo "</span>";
    }

    else echo "Unknown mission: $mission";
}

function GetMission ( $fleet_obj )
{
    if ( $fleet_obj['mission'] < FTYP_RETURN ) return $fleet_obj['mission'];
    else if ( $fleet_obj['mission'] < FTYP_ORBITING ) return $fleet_obj['mission'] - FTYP_RETURN;
    else return $fleet_obj['mission'] - FTYP_ORBITING;
}

function PhalanxEventList ($planet_id)
{
    $planet = GetPlanet ($planet_id);
    $user = LoadUser ($planet['owner_id']);
    global $fleetmap;
    $result = EnumPlanetFleets ( $planet_id );
    $rows = dbrows ( $result );

    $task = array ();
    $tasknum = 0;

    $unions = array ();

    while ($rows--)
    {
        $fleet_obj = dbarray ($result);
        $queue = GetFleetQueue ($fleet_obj['fleet_id']);

        // Union fleets are assembled separately
        if ( $fleet_obj['union_id'] > 0 && $fleet_obj['target_planet'] == $planet_id && !key_exists($fleet_obj['union_id'], $unions) )
        {
            $task[$tasknum]['end_time'] = $queue['end'];

            // Fleets
            $acs_result = EnumUnionFleets ( $fleet_obj['union_id'] );
            $task[$tasknum]['fleets'] = $acs_rows = dbrows ( $acs_result );
            $f = 0;
            while ($acs_rows--)
            {
                $fleet_obj = dbarray ($acs_result);

                $task[$tasknum]['fleet'][$f] = array ();
                foreach ( $fleetmap as $id=>$gid ) $task[$tasknum]['fleet'][$f][$gid] = $fleet_obj["ship$gid"];
                $task[$tasknum]['fleet'][$f]['owner_id'] = $fleet_obj['owner_id'];
                $task[$tasknum]['fleet'][$f]['origin_id'] = $fleet_obj['start_planet'];
                $task[$tasknum]['fleet'][$f]['target_id'] = $fleet_obj['target_planet'];
                $task[$tasknum]['fleet'][$f]['mission'] = GetMission ($fleet_obj);
                $task[$tasknum]['fleet'][$f]['dir'] = 0;    // to the planet
                $f++;
            }
            // Mark that this ACS union has been processed and ignore the rest of the fleets on further enumeration
            $unions[ $fleet_obj['union_id'] ] = 1;

            $tasknum++;
            continue;
        }

        if ( $fleet_obj['union_id'] > 0 && $fleet_obj['target_planet'] == $planet_id && $fleet_obj['mission'] != 21 ) continue;

        // Do not show departure and return for Deploy mission.
        if ( $fleet_obj['mission'] == (FTYP_RETURN+FTYP_DEPLOY) ) continue;
        if ( $fleet_obj['mission'] == FTYP_DEPLOY && $fleet_obj['start_planet'] == $planet_id ) continue;

        // Do not show fleets returning from the target planet.
        if ( ($fleet_obj['mission'] > FTYP_RETURN && $fleet_obj['mission'] < FTYP_ORBITING) && $fleet_obj['target_planet'] == $planet_id ) continue;

        // For a departing expedition, add a hold pseudo-task.
        // Don't show foreign fleets.
        if ( $fleet_obj['mission'] == FTYP_EXPEDITION && $fleet_obj['owner_id'] == $user['player_id'] )
        {
            // Departure and arrival times
            $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 2;
            $tasknum++;
        }

        // For the arriving Hold task, add a hold pseudo-hold task.
        if ( $fleet_obj['mission'] == FTYP_ACS_HOLD && $fleet_obj['owner_id'] != $user['player_id'] )
        {
            // Departure and arrival times
            $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 2;
            $tasknum++;
        }

        // Arrival time
        if ( $fleet_obj['mission'] < FTYP_RETURN && $fleet_obj['start_planet'] == $planet_id ) {
            if ($fleet_obj['mission'] != FTYP_EXPEDITION) $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['flight_time'];
            else $task[$tasknum]['end_time'] = $queue['end'];
        }
        else $task[$tasknum]['end_time'] = $queue['end'];

        // Fleet
        $task[$tasknum]['fleets'] = 1;
        $task[$tasknum]['fleet'][0] = array ();
        foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
        $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
        $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
        $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
        $task[$tasknum]['fleet'][0]['mission'] = GetMission ( $fleet_obj );
        if ( GetMission($fleet_obj) == FTYP_EXPEDITION )
        {
            if ($fleet_obj['mission'] < FTYP_RETURN) $task[$tasknum]['fleet'][0]['dir'] = 0;
            else if ($fleet_obj['mission'] < FTYP_ORBITING) $task[$tasknum]['fleet'][0]['dir'] = 1;
            else $task[$tasknum]['fleet'][0]['dir'] = 2;
        }
        else if ( GetMission($fleet_obj) == FTYP_ACS_HOLD )
        {
            if ($fleet_obj['mission'] < FTYP_RETURN) $task[$tasknum]['fleet'][0]['dir'] = 0;
            else if ($fleet_obj['mission'] < FTYP_ORBITING) $task[$tasknum]['fleet'][0]['dir'] = 1;
            else $task[$tasknum]['fleet'][0]['dir'] = 2;
        }
        else
        {
            if ( $fleet_obj['target_planet'] == $planet_id ) $task[$tasknum]['fleet'][0]['dir'] = 0;    // to the planet
            else $task[$tasknum]['fleet'][0]['dir'] = 1;    // return
        }
        if ($fleet_obj['mission'] == FTYP_MISSILE)
        {
            $task[$tasknum]['fleet'][0]['ipm_amount'] = $fleet_obj['ipm_amount'];
            $task[$tasknum]['fleet'][0]['ipm_target'] = $fleet_obj['ipm_target'];
        }

        $tasknum++;

        // For departing or holding expeditions, add a pseudo-return task.
        if ( ($fleet_obj['mission'] == FTYP_EXPEDITION || $fleet_obj['mission'] == (FTYP_ORBITING+FTYP_EXPEDITION) ) && $fleet_obj['owner_id'] == $user['player_id'] )
        {
            // Departure and arrival times
            if ( $fleet_obj['mission'] > FTYP_ORBITING) $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'];
            else $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'] + $fleet_obj['flight_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 1;
            $tasknum++;
        }

    }

    $anz = 0;
    if ($tasknum > 0)
    {
        sksort ( $task, 'end_time', true);        // Sort by time of arrival.
        $now = time ();

        foreach ($task as $i=>$t)
        {
            $seconds = max($t['end_time']-$now, 0);
            if ( $seconds <= 0 ) continue;
            if ($t['fleets'] > 1) echo "<tr class=''>\n";
            else if ($t['fleet'][0]['dir'] == 0) echo "<tr class='flight'>\n";
            else if ($t['fleet'][0]['dir'] == 1) echo "<tr class='return'>\n";
            else if ($t['fleet'][0]['dir'] == 2) echo "<tr class='holding'>\n";
            echo "<th><div id='bxx".($i+1)."' title='".$seconds."'star='".$t['end_time']."'></div></th>\n";
            echo "<th colspan='3'>";
            for ($fl=0; $fl<$t['fleets']; $fl++)
            {
                echo FleetSpan ($t['fleet'][$fl]);
                if ($t['fleets'] > 1) echo "<br /><br />";
            }
            echo "</th></tr>\n\n";
            $anz++;
        }
        if ($anz) echo "<script language=javascript>anz=".$anz.";t();</script>\n\n";
    }
}

?>