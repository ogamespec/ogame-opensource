<?php

// Creating a list of Phalanx events.
// TODO: There is some isomorphism with the Overview event module. If possible, unify the code for event list output.

require_once "event_list.php";

function FleetSpanAttack (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ATTACK")."</span>";
    else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_ATTACK")."</span></span>";
}

function FleetSpanAcsAttack (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ( $dir == 0 ) echo "<span class='phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "federation"), PlanetTo($target, "federation")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK")."</span>";
    else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_ACS_ATTACK")."</span></span>";
}

function FleetSpanTransport (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_TRANSPORT")."</span>";
    else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_TRANSPORT")."</span></span>";
}

function FleetSpanDeploy (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_DEPLOY")."</span>";
}

function FleetSpanAcsHold (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ( $dir == 2 ) echo "<span class='holding phalanx_fleet'>".va(loca("EVENT_FLEET_HOLD"),PlayerDetails($owner),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_TO_ORBIT"), PlanetFrom($origin, "phalanx_fleet"), PlanetOn($target, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_HOLD")."</span></span>";
    else if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_HOLD")."</span>";
    else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_HOLD")."</span></span>";
}

function FleetSpanSpy (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_SPY")."</span>";
    else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_SPY")."</span></span>";
}

function FleetSpanColonize (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_COLONY")."</span></span>";
}

function FleetSpanRecycle (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_RECYCLE")."</span></span>";
}

function FleetSpanDestroy (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_DESTROY")."</span>";
    else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_DESTROY")."</span></span>";
}

function FleetSpanAcsAttackHead (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ( $dir == 0 ) echo "<span class='phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_TO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "attack"), PlanetTo($target, "attack")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK_HEAD")."</span>";
    else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_ACS_ATTACK_HEAD")."</span></span>";
}

function FleetSpanExpedition (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ( $dir == 2 ) echo "<span class='holding phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_EXPO_FROM_ONTO_PHALANX"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_EXPO")."</span></span>";
    else if ( $dir == 0 ) echo "<span class='flight phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_EXPO")."</span>";
    else echo "<span class='return phalanx_fleet'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"phalanx_fleet",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO_PHALANX"), PlanetFrom($target, "phalanx_fleet"), PlanetTo($origin, "phalanx_fleet")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_EXPO")."</span></span>";
}

function FleetSpanMissile (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    echo "<span class='missile'>" .va(loca("EVENT_RAK"), $fleet['ipm_amount'], PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")) . " ";
    if ( $fleet['ipm_target'] > 0 ) echo loca("EVENT_RAK_TARGET") . " " . loca ("NAME_".$fleet['ipm_target']);
    echo "</span>";
}

function FleetSpan ( array $fleet_entry ) : void
{
    $mission = $fleet_entry['mission'];
    $origin = GetPlanet ( $fleet_entry['origin_id'] );
    $target = GetPlanet ( $fleet_entry['target_id'] );
    $fleet = $fleet_entry;
    $dir = $fleet_entry['dir'];
    $owner = LoadUser ( $origin['owner_id'] );

    switch ($mission) {
        case FTYP_ATTACK:
            FleetSpanAttack ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_ACS_ATTACK:
            FleetSpanAcsAttack ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_TRANSPORT:
            FleetSpanTransport ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_DEPLOY:
            FleetSpanDeploy ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_ACS_HOLD:
            FleetSpanAcsHold ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_SPY:
            FleetSpanSpy ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_COLONIZE:
            FleetSpanColonize ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_RECYCLE:
            FleetSpanRecycle ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_DESTROY:
            FleetSpanDestroy ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_ACS_ATTACK_HEAD:    // ACS Attack Head fleet (slot 0)
            FleetSpanAcsAttackHead ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_EXPEDITION:
            FleetSpanExpedition ($dir, $fleet, $owner, $origin, $target);
            break;
        case FTYP_MISSILE:
            FleetSpanMissile ($dir, $fleet, $owner, $origin, $target);
            break;
        default:
            echo loca("EVENT_MISSION")." Type:$mission, Dir:$dir, Fleet: " .TitleFleet($fleet,0). ", from " .PlanetFrom($origin, ""). " to " .PlanetTo($target, ""). ", " . Cargo ($fleet,"","Cargo");
            break;
    }
}

function PhalanxEventList (int $planet_id) : void
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
                foreach ( $fleetmap as $id=>$gid ) $task[$tasknum]['fleet'][$f][$gid] = $fleet_obj[$gid];
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
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj[$gid];
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
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj[$gid];
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
        foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj[$gid];
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
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj[$gid];
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