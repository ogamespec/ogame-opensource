<?php

// Creating a list of Overview events.

require_once "event_list.php";

function FleetSpanAttack (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='flight ownattack'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownattack",loca("EVENT_M_ATTACK"))."</span>";
    else if ($dir == 1) echo "<span class='return ownattack'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>". 
        va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownattack",loca("EVENT_M_ATTACK"))."</span>";
    else if ($dir == 0x10) echo "<span class='attack'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"attack"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "attack"), PlanetTo($target, "attack")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ATTACK")."</span>";
}

function FleetSpanAcsAttack (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='federation'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownfederation"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownfederation"), PlanetTo($target, "ownfederation")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownfederation",loca("EVENT_M_ACS_ATTACK"))."</span>";
    else if ($dir == 1) echo "<span class='return ownfederation'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownfederation"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownfederation"), PlanetTo($target, "ownfederation")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownfederation",loca("EVENT_M_ACS_ATTACK"))."</span>";
    else if ($dir == 0x10) echo "<span class='federation'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"federation"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "federation"), PlanetTo($target, "federation")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK")."</span>";
    else if ($dir == 0x20) echo "<span class='ownfederation'>".va(loca("EVENT_FLEET_ACS_HEAD"),OverFleet($fleet,1,"ownfederation"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "ownfederation"), PlanetTo($target, "ownfederation")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK")."</span>";
}

function FleetSpanTransport (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='flight owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owntransport"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "owntransport"), PlanetTo($target, "owntransport")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"owntransport",loca("EVENT_M_TRANSPORT"))."</span>";
    else if ($dir == 1) echo "<span class='return owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owntransport"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "owntransport"), PlanetTo($target, "owntransport")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"owntransport",loca("EVENT_M_TRANSPORT"))."</span>";
    else if ($dir == 0x10) echo "<span class='flight transport'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"transport"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "transport"), PlanetTo($target, "transport")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_TRANSPORT")."</span>";
}

function FleetSpanDeploy (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='flight owndeploy'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owndeploy"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "owndeploy"), PlanetTo($target, "owndeploy")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"owndeploy",loca("EVENT_M_DEPLOY"))."</span>";
    else if ($dir == 1) echo "<span class='return owndeploy'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owndeploy"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "owndeploy"), PlanetTo($target, "owndeploy")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"owndeploy",loca("EVENT_M_DEPLOY"))."</span>";
}

function FleetSpanAcsHold (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='flight ownhold'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownhold"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownhold"), PlanetTo($target, "ownhold")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownhold",loca("EVENT_M_HOLD"))."</span>";
    else if ($dir == 1) echo "<span class='return ownhold'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownhold"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownhold"), PlanetTo($target, "ownhold")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownhold",loca("EVENT_M_HOLD"))."</span>";
    else if ($dir == 2) echo "<span class='holding ownhold'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownhold"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_HOLD_FROM_ONTO"), PlanetFrom($origin, "ownhold"), PlanetFrom($target, "ownhold")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownhold",loca("EVENT_M_HOLD"))."</span>";
    else if ($dir == 0x20) echo "<span class='flight hold'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"hold",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "hold"), PlanetTo($target, "hold")).
        ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_HOLD")."</span></span>";
    else if ($dir == 0x22) echo "<span class='holding hold'>".va(loca("EVENT_FLEET_HOLD"),PlayerDetails($owner),OverFleet($fleet,1,"hold",true))."</a><a href='#' title='".TitleFleet($fleet,1,true)."'></a>".
        va(loca("EVENT_FROM_TO_ORBIT"), PlanetFrom($origin, "hold"), PlanetFrom($target, "hold")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_HOLD")."</span>";
}

function FleetSpanSpy (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='flight ownespionage'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownespionage"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownespionage"), PlanetTo($target, "ownespionage")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownespionage",loca("EVENT_M_SPY"))."</span>";
    else if ($dir == 1) echo "<span class='return ownespionage'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownespionage"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownespionage"), PlanetTo($target, "ownespionage")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownespionage",loca("EVENT_M_SPY"))."</span>";
    else if ($dir == 0x10) echo "<span class='flight espionage'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"espionage"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "espionage"), PlanetTo($target, "espionage")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_SPY")."</span>";
}

function FleetSpanColonize (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='flight owncolony'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owncolony"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_COLONY_FROM_TO"), PlanetFrom($origin, "owncolony"), PlanetTo($target, "owncolony")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"owncolony",loca("EVENT_M_COLONY"))."</span>";
    else if ($dir == 1) echo "<span class='return owncolony'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owncolony"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_COLONY_FROM_RETURN_TO"), PlanetFrom($origin, "owncolony"), PlanetTo($target, "owncolony")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"owncolony",loca("EVENT_M_COLONY"))."</span>";
}

function FleetSpanRecycle (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='flight ownharvest'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownharvest"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownharvest"), PlanetTo($target, "ownharvest")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownharvest",loca("EVENT_M_RECYCLE"))."</span>";
    else if ($dir == 1) echo "<span class='return ownharvest'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownharvest"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownharvest"), PlanetTo($target, "ownharvest")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownharvest",loca("EVENT_M_RECYCLE"))."</span>";
}

function FleetSpanDestroy (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='flight owndestroy'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owndestroy"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "owndestroy"), PlanetTo($target, "owndestroy")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"owndestroy",loca("EVENT_M_DESTROY"))."</span>";
    else if ($dir == 1) echo "<span class='return owndestroy'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owndestroy"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "owndestroy"), PlanetTo($target, "owndestroy")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"owndestroy",loca("EVENT_M_DESTROY"))."</span>";
    else if ($dir == 0x10) echo "<span class='flight destroy'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"destroy"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "destroy"), PlanetTo($target, "destroy")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_DESTROY")."</span>";
}

function FleetSpanAcsAttackHead (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='attack'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownattack",loca("EVENT_M_ACS_ATTACK_HEAD"))."</span>";
    else if ($dir == 1) echo "<span class='return ownattack'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownattack",loca("EVENT_M_ACS_ATTACK_HEAD"))."</span>";
    else if ($dir == 0x10) echo "<span class='attack'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"attack"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "attack"), PlanetTo($target, "attack")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK_HEAD")."</span>";
    else if ($dir == 0x20) echo "<span class='ownattack'>".va(loca("EVENT_FLEET_ACS_HEAD"),OverFleet($fleet,1,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
        va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
        ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK_HEAD")."</span>";
}

function FleetSpanExpedition (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0) echo "<span class='flight owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownexpedition"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_EXPO_FROM_TO"), PlanetFrom($origin, "ownexpedition"), PlanetTo($target, "ownexpedition")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownexpedition",loca("EVENT_M_EXPO"))."</span>";
    else if ($dir == 1) echo "<span class='return owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownexpedition"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_EXPO_RETURN_FROM_TO"), PlanetTo($target, "ownexpedition"), Cargo($fleet,"ownexpedition",loca("EVENT_M_EXPO")))."</span>";
    else if ($dir == 2) echo "<span class='holding owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownexpedition"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
        va(loca("EVENT_EXPO_FROM_ONTO"), PlanetFrom($origin, "ownexpedition"), PlanetFrom($target, "ownexpedition")).
        ". ".loca("EVENT_MISSION").": ".Cargo($fleet,"ownexpedition",loca("EVENT_M_EXPO"))."</span>";
}

function FleetSpanMissile (int $dir, array $fleet, array $owner, array $origin, array $target) : void
{
    if ($dir == 0)
    {
        echo "<span class='ownmissile'>" .va(loca("EVENT_RAK"), $fleet['ipm_amount'], PlanetFrom($origin, ""), PlanetTo($target, "own")) . " ";
    }
    else if ($dir == 0x10)
    {
        echo "<span class='missile'>" .va(loca("EVENT_RAK"), $fleet['ipm_amount'], PlanetFrom($origin, ""), PlanetTo($target, "")) . " ";
    }
    if ( $fleet['ipm_target'] > 0 ) echo loca("EVENT_RAK_TARGET") . " " . loca ("NAME_".$fleet['ipm_target']);
    echo "</span>";    
}

function FleetSpan ( array $fleet_entry ) : void
{
    $mission = $fleet_entry['mission'];
    $assign = $fleet_entry['assign'];
    $dir = $fleet_entry['dir'];
    $dir = $dir | ($assign << 4);
    $origin = LoadPlanetById ( $fleet_entry['origin_id'] );
    $target = LoadPlanetById ( $fleet_entry['target_id'] );
    $fleet = $fleet_entry;
    $owner = LoadUser ( $origin['owner_id'] );

// $dir mask:
// 0x0 - own fleet departs 
// 0x1 - own fleet returns
// 0x2 - own fleet holds
// 0x10 - enemy fleet departs
// 0x20 - allied fleet departs
// 0x22 - allied fleet holds

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

function GetDirectionAssignment ( bool $friendly, array $fleet_obj, int &$dir, int &$assign ) : void
{
    global $GlobalUser;

    if ($fleet_obj['mission'] < FTYP_RETURN) $dir = 0;      // departing
    else if ($fleet_obj['mission'] < FTYP_ORBITING) $dir = 1;     // return
    else $dir = 2;  // holding

    if ( $fleet_obj['owner_id'] == $GlobalUser['player_id'] ) $assign = 0;
    else {
        if ($friendly && (GetMission ($fleet_obj) == FTYP_ACS_HOLD || GetMission ($fleet_obj) == FTYP_ACS_ATTACK_HEAD || GetMission ($fleet_obj) == FTYP_ACS_ATTACK) ) $assign = 2;
        else $assign = 1;
    }
}

function EventList () : void
{
    global $GlobalUser;
    global $fleetmap;
    global $transportableResources;

    // Single fleets
    $tasklist = EnumFleetQueue ( $GlobalUser['player_id'] );
    $rows = dbrows ($tasklist);
    $task = array ();
    $tasknum = 0;
    while ($rows--)
    {
        $queue = dbarray ($tasklist);

        $fleet_obj = LoadFleet ( $queue['sub_id'] );
        if ( $fleet_obj['union_id'] > 0 ) continue;        // Union fleets are assembled separately

        // For a departing expedition or ACS Hold, add a hold pseudo-task.
        // Don't show foreign fleets.
        if ( ($fleet_obj['mission'] == FTYP_ACS_HOLD || $fleet_obj['mission'] == FTYP_EXPEDITION) && $fleet_obj['owner_id'] == $GlobalUser['player_id'] )
        {
            // Departure and arrival times
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj[$gid];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            foreach ($transportableResources as $i=>$rc) {
                $task[$tasknum]['fleet'][0][$rc] = 0;
            }
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 2;
            $task[$tasknum]['fleet'][0]['assign'] = 0;
            $tasknum++;
        }

        // For the arriving Hold task, add a hold pseudo-hold task.
        if ( $fleet_obj['mission'] == FTYP_ACS_HOLD && $fleet_obj['owner_id'] != $GlobalUser['player_id'] )
        {
            // Departure and arrival times
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj[$gid];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            foreach ($transportableResources as $i=>$rc) {
                $task[$tasknum]['fleet'][0][$rc] = 0;
            }
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 2;
            $task[$tasknum]['fleet'][0]['assign'] = 2;
            $tasknum++;
        }

        // Departure and arrival times
        $task[$tasknum]['start_time'] = $queue['start'];
        $task[$tasknum]['end_time'] = $queue['end'];

        // Fleet
        $task[$tasknum]['fleets'] = 1;
        $task[$tasknum]['fleet'][0] = array ();
        foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj[$gid];
        $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
        foreach ($transportableResources as $i=>$rc) {
            $task[$tasknum]['fleet'][0][$rc] = $fleet_obj[$rc];
        }
        if ( $fleet_obj['mission'] < FTYP_RETURN || $fleet_obj['mission'] > FTYP_ORBITING ) {
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
        }
        else
        {
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['start_planet'];
        }
        $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
        if ($fleet_obj['mission'] == FTYP_MISSILE)
        {
            $task[$tasknum]['fleet'][0]['ipm_amount'] = $fleet_obj['ipm_amount'];
            $task[$tasknum]['fleet'][0]['ipm_target'] = $fleet_obj['ipm_target'];
        }
        $task[$tasknum]['fleet'][0]['dir'] = 0;
        $task[$tasknum]['fleet'][0]['assign'] = 0;
        GetDirectionAssignment (true, $fleet_obj, $task[$tasknum]['fleet'][0]['dir'], $task[$tasknum]['fleet'][0]['assign'] );

        $tasknum++;

        // For departing or holding fleets, add a pseudo-return task.
        // Do not show the returns of other people's fleets, Deploy mission and Missile Attack.
        if ( ($fleet_obj['mission'] < FTYP_RETURN || $fleet_obj['mission'] > FTYP_ORBITING) && $fleet_obj['owner_id'] == $GlobalUser['player_id'] && $fleet_obj['mission'] != FTYP_DEPLOY && $fleet_obj['mission'] != FTYP_MISSILE )
        {
            // Departure and arrival times
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = 2 * $queue['end'] - $queue['start'];
            if ( GetMission ($fleet_obj) == FTYP_ACS_HOLD || GetMission ($fleet_obj) == FTYP_EXPEDITION ) {
                if ( $fleet_obj['mission'] > FTYP_ORBITING) $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'];
                else $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'] + $fleet_obj['flight_time'];
            }

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj[$gid];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            foreach ($transportableResources as $i=>$rc) {
                $task[$tasknum]['fleet'][0][$rc] = 0;
            }
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 1;
            $task[$tasknum]['fleet'][0]['assign'] = 0;
            $tasknum++;
        }
    }

    // Union fleets
    $unions = EnumUnion ( $GlobalUser['player_id'] );
    foreach ( $unions as $u=>$union)
    {
        // Fleets
        $result = EnumUnionFleets ( $union['union_id'] );
        $rows = dbrows ( $result );
        $friendly = $union['target_player'] != $GlobalUser['player_id'];        // If the union is flying NOT at the player, then it is a friendly ACS attack

        if ( $rows > 0 )    // Do not show empty unions.
        {
            $task[$tasknum]['fleets'] = $rows;
            $f = 0;
            $tn = $tasknum;
            while ($rows--)
            {
                $fleet_obj = dbarray ($result);

                $queue = GetFleetQueue ($fleet_obj['fleet_id']);
                $task[$tn]['end_time'] = $queue['end'];

                // For departing or holding fleets, add a pseudo-return task.
                // Do not show the returns of other people's fleets and Deploy mission.
                if ( $fleet_obj['mission'] < FTYP_RETURN && $fleet_obj['owner_id'] == $GlobalUser['player_id'] )
                {
                    $tasknum++;

                    // Departure and arrival times
                    $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['flight_time'];

                    // Fleet
                    $task[$tasknum]['fleets'] = 1;
                    $task[$tasknum]['fleet'][0] = array ();
                    foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj[$gid];
                    $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
                    foreach ($transportableResources as $i=>$rc) {
                        $task[$tasknum]['fleet'][0][$rc] = 0;
                    }
                    $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['target_planet'];
                    $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['start_planet'];
                    $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
                    $task[$tasknum]['fleet'][0]['dir'] = 1;
                    $task[$tasknum]['fleet'][0]['assign'] = 0;
                }

                $task[$tn]['fleet'][$f] = array ();
                foreach ( $fleetmap as $id=>$gid ) $task[$tn]['fleet'][$f][$gid] = $fleet_obj[$gid];
                $task[$tn]['fleet'][$f]['owner_id'] = $fleet_obj['owner_id'];
                foreach ($transportableResources as $i=>$rc) {
                    $task[$tn]['fleet'][$f][$rc] = $fleet_obj[$rc];
                }
                $task[$tn]['fleet'][$f]['origin_id'] = $fleet_obj['start_planet'];
                $task[$tn]['fleet'][$f]['target_id'] = $fleet_obj['target_planet'];
                $task[$tn]['fleet'][$f]['mission'] = GetMission ($fleet_obj);
                $task[$tn]['fleet'][$f]['dir'] = 0;
                $task[$tn]['fleet'][$f]['assign'] = 0;
                GetDirectionAssignment ($friendly, $fleet_obj, $task[$tn]['fleet'][$f]['dir'], $task[$tn]['fleet'][$f]['assign'] );
                $f++;
            }

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