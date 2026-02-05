<?php

// Fleet Management.

/*
fleet_id: Ordinal number of the fleet in the table (INT AUTO_INCREMENT PRIMARY KEY)
owner_id: User number to which the fleet belongs (INT)
union_id: The number of the union in which the fleet is flying (INT)
`700`, `701`, `702`: Cargo transported (metal/crystal/deuterium) (DOUBLE)
fuel: Loaded fuel for flight (deuterium) (DOUBLE)
mission: Type of mission (INT)
start_planet: Start (INT)
target_planet: Target (INT)
flight_time: One-way flight time in seconds (INT)
deploy_time: Fleet holding time in seconds (INT)
ipm_amount: Number of interplanetary missiles (INT DEFAULT 0)
ipm_target: target id for interplanetary rockets, 0 - all (INT DEFAULT 0)
XX: number of ships of each type (INT DEFAULT 0)

Fleet missions are issued as an events for the global queue.

Sending a fleet consists of taking away the following fields from the planet: fXX (fleet), m/k/d - resources.
Fleet arrival: adds these fields (or takes them away again, when attacking), and generates messages.

The first three pages of flottenX prepare the parameters for the flottenversand page, which either sends the fleet or returns an error.

One fleet task can spawn another task upon completion, e.g. after reaching Transport, a new task is created - return Transport.

In Overview, all subsequent missions are "predicted", they are not really there. The Fleet menu shows the description of the missions close to the database data.

*/

// ==================================================================================
// Get a list of available missions for the fleet.

/*
Possible assignments:

X:0
>= X:16 (position becomes 16, any type of planet)          Expedition
empty space X:1 ... X:15 without colon       Transport, Attack
empty space X:1 ... X:15 with colon       Transport, Attack, Colonize

own planet                         Transport, Deploy
own moon                               Transport, Deploy

debris field with recycler            Recycle
debris field without recycler        No suitable missions (Нет подходящих заданий)

buddy/ally planet              Transport, Attack, Hold, ACS Attack
buddy/ally moon with Deathstar            Transport, Attack, Hold, ACS Attack, Destroy
buddy/ally moon without Deathstar        Transport, Attack, Hold, ACS Attack
(if there's a Spy probe add Espionage)
if there's only a spy in the fleet     Espionage

foreign planet                     Transport, Attack, ACS Attack
foreign moon with Deathstar                     Transport, Attack, ACS Attack, Destroy
foreign moon without Deathstar                Transport, Attack, ACS Attack
(if there's a Spy probe add Espionage)
if there's only a spy in the fleet     Espionage
*/

function FleetAvailableMissionsDefault ( int $thisgalaxy, int $thissystem, int $thisplanet, int $thisplanettype, int $galaxy, int $system, int $planet, int $planettype, array $fleet ) : array
{
    $missions = array ( );

    $uni = LoadUniverse ();
    $origin = LoadPlanet ( $thisgalaxy, $thissystem, $thisplanet, $thisplanettype );
    $target = LoadPlanet ( $galaxy, $system, $planet, $planettype );

    if ( $planet >= 16 )
    {
        $missions[] = FTYP_EXPEDITION;
        return $missions;
    }

    if ( $planettype == 2)        // debris field.
    {
        if ( $fleet[GID_F_RECYCLER] > 0 ) $missions[] = FTYP_RECYCLE;    // if there are recyclers in the fleet
        return $missions;
    }

    if ( $target == null )        // empty space
    {
        $missions[] = FTYP_TRANSPORT;
        $missions[] = FTYP_ATTACK;
        if ( $fleet[GID_F_COLON] > 0 ) $missions[] = FTYP_COLONIZE;    // if there's a colonizer in the fleet
        return $missions;
    }

    if ( $origin['owner_id'] == $target['owner_id'] )        // own moons/planets
    {
        $missions[] = FTYP_TRANSPORT;
        $missions[] = FTYP_DEPLOY;
        return $missions;
    }
    else
    {
        $origin_user = LoadUser ($origin['owner_id']);
        if ($origin_user == null) {
            return $missions;
        }
        $target_user = LoadUser ($target['owner_id']);
        if ($target_user == null) {
            return $missions;
        }

        if ( ( $origin_user['ally_id'] == $target_user['ally_id'] && $origin_user['ally_id'] > 0 )   || IsBuddy ( $origin_user['player_id'],  $target_user['player_id']) )      // allies or buddies
        {
            $missions[] = FTYP_TRANSPORT;
            $missions[] = FTYP_ATTACK;
            if ( $uni['acs'] > 0 ) $missions[] = FTYP_ACS_HOLD;
            if ( $fleet[GID_F_DEATHSTAR] > 0 && GetPlanetType($target) == 3 ) $missions[] = FTYP_DESTROY;
            if ( $fleet[GID_F_PROBE] > 0  ) $missions[] = FTYP_SPY;
        }
        else        // all others
        {
            $missions[] = FTYP_TRANSPORT;
            $missions[] = FTYP_ATTACK;
            if ( $fleet[GID_F_DEATHSTAR] > 0 && GetPlanetType($target) == 3 ) $missions[] = FTYP_DESTROY;
            if ( $fleet[GID_F_PROBE] > 0  ) $missions[] = FTYP_SPY;
        }

        // If the target planet is on the ACS attack list, add the task
        $unions = EnumUnion ( $origin_user['player_id'] );
        foreach ( $unions as $u=>$union ) {
            $fleet_obj = LoadFleet ( $union['fleet_id'] );
            $fleet_target = LoadPlanetById ( $fleet_obj['target_planet'] );
            if ( $fleet_target['planet_id'] == $target['planet_id'] ) {
                $missions[] = FTYP_ACS_ATTACK;
                break;
            }
        }
        return $missions;
    }
}

// First, the default procedure is called to obtain missions from 0.84, then the list is modified by mods and goes into the visual.
function FleetAvailableMissions ( int $thisgalaxy, int $thissystem, int $thisplanet, int $thisplanettype, int $galaxy, int $system, int $planet, int $planettype, array $fleet ) : array {

    $missions = FleetAvailableMissionsDefault ($thisgalaxy, $thissystem, $thisplanet, $thisplanettype, $galaxy, $system, $planet, $planettype, $fleet);

    $param = [];
    $param['thisgalaxy'] = $thisgalaxy;
    $param['thissystem'] = $thissystem;
    $param['thisplanet'] = $thisplanet;
    $param['thisplanettype'] = $thisplanettype;
    $param['galaxy'] = $galaxy;
    $param['system'] = $system;
    $param['planet'] = $planet;
    $param['planettype'] = $planettype;
    $param['fleet'] = $fleet;

    ModsExecArrRef ('fleet_available_missions', $param, $missions);

    return $missions;
}

// ==================================================================================
// Flight Calculation.

// Distance.
function FlightDistance ( int $thisgalaxy, int $thissystem, int $thisplanet, int $galaxy, int $system, int $planet ) : int
{
    if ($thisgalaxy == $galaxy) {
        if ($thissystem == $system) {
            if ($planet == $thisplanet) $dist = 5;
            else $dist = abs ($planet - $thisplanet) * 5 + 1000;
        }
        else $dist = abs ($system - $thissystem) * 5 * 19 + 2700;
    }
    else $dist = abs ($galaxy - $thisgalaxy) * 20000;
    return $dist;
}

// Group fleet speed.
function FlightSpeed (array $fleet, array $user, array $planet) : int
{
    $minspeed = FleetSpeed ( GID_F_PROBE, $user, $planet );        // the fastest ship is the Spy Probe.
    foreach ($fleet as $id=>$amount)
    {
        $speed = FleetSpeed ( $id, $user, $planet );
        if ( $amount == 0 || $speed == 0 ) continue;
        if ($speed < $minspeed) $minspeed = $speed;
    }
    return (int)$minspeed;
}

// Deuterium consumption per flight by the entire fleet.
function FlightCons (array $fleet, int $dist, int $flighttime, array $user, array $planet, int $speedfactor, int $hours=0) : array
{
    $cons = array ( 'fleet' => 0, 'probes' => 0 );
    foreach ($fleet as $id=>$amount)
    {
        if ($amount > 0) {
            $spd = 35000 / ( $flighttime * $speedfactor - 10) * sqrt($dist * 10 / FleetSpeed($id, $user, $planet ) );
            $basecons = $amount * FleetCons ($id, $user, $planet );
            $consumption = $basecons * $dist / 35000 * (($spd / 10) + 1) * (($spd / 10) + 1);
            $consumption += $hours * $amount * FleetCons ($id, $user, $planet ) / 10;    // holding costs
            if ( $id == GID_F_PROBE ) $cons['probes'] += (int)$consumption;
            else $cons['fleet'] += (int)$consumption;
        }
    }
    return $cons;
}

// Flight time in seconds, at a given percentage.
function FlightTime (int $dist, int $slowest_speed, float $prc, int $xspeed) : int
{
    return (int)round ( (35000 / ($prc*10) * sqrt ($dist * 10 / $slowest_speed ) + 10) / $xspeed );
}

// The speed of the ship
// 202-C/I, 203-C, 204-C, 205-I, 206-I, 207-H, 208-I, 209-C, 210-C, 211-I/H, 212-C, 213-H, 214-H, 215-H
function FleetSpeed ( int $id, array $user, array $planet) : float
{
    global $UnitParam;

    $baseSpeed = $UnitParam[$id][4];
    $combustion = $user[GID_R_COMBUST_DRIVE];
    $impulse = $user[GID_R_IMPULSE_DRIVE];
    $hyper = $user[GID_R_HYPER_DRIVE];

    switch ($id) {
        case GID_F_SC:
            if ($impulse >= 5) return ($baseSpeed + 5000) * (1 + 0.2 * $impulse);
            else return $baseSpeed * (1 + 0.1 * $combustion);
        case GID_F_BOMBER:
            if ($hyper >= 8) return ($baseSpeed + 1000) * (1 + 0.3 * $hyper);
            else return $baseSpeed * (1 + 0.2 * $impulse);            
        case GID_F_LC:
        case GID_F_LF:
        case GID_F_RECYCLER:
        case GID_F_PROBE:
        case GID_F_SAT:
            return $baseSpeed * (1 + 0.1 * $combustion);
        case GID_F_HF:
        case GID_F_CRUISER:
        case GID_F_COLON:
            return $baseSpeed * (1 + 0.2 * $impulse);
        case GID_F_BATTLESHIP:
        case GID_F_DESTRO:
        case GID_F_DEATHSTAR:
        case GID_F_BATTLECRUISER:
            return $baseSpeed * (1 + 0.3 * $hyper);
        default: return $baseSpeed;
    }
}

function FleetCargo ( int $id ) : int
{
    global $UnitParam;
    return $UnitParam[$id][3];
}

// Total carrying capacity of the fleet
function FleetCargoSummary ( array $fleet ) : int
{
    global $fleetmap;
    $cargo = 0;
    foreach ( $fleetmap as $n=>$gid )
    {
        $amount = $fleet[$gid];
        if ($gid != GID_F_PROBE) $cargo += FleetCargo ($gid) * $amount;        // not counting probes.
    }
    return $cargo;
}

function FleetCons (int $id, array $user, array $planet ) : int
{
    global $UnitParam;
    $impulse = $user[GID_R_IMPULSE_DRIVE];
    // The Small Cargo has a 2X increase in consumption when changing engines. In a bomber, it does NOT increase.
    if ($id == GID_F_SC && $impulse >= 5) $cons = $UnitParam[$id][5] * 2;
    else $cons = $UnitParam[$id][5];

    return $cons;
}

function GetMaxFleet (array|null $user, array|null $planet, int &$maxfleet, int &$maxfleet_no_bonus) : void {

    if ($user == null) {
        $maxfleet = $maxfleet_no_bonus = 0;
        return;
    }

    $maxfleet_no_bonus = $user[GID_R_COMPUTER] + 1;
    $maxfleet = $maxfleet_no_bonus;

    $prem = PremiumStatus ($user);
    if ( $prem['admiral'] ) $maxfleet += 2;

    // The maxfleet variable is passed through an array to receive the bonus.
    $param = [];
    $param['user'] = $user;
    $param['planet'] = $planet;
    $bonus = [];
    $bonus['value'] = $maxfleet;
    ModsExecArrRef('bonus_max_fleet', $param, $bonus);
    $maxfleet = max (0, $bonus['value']);
}

// ==================================================================================

// Alter the number of ships on a planet.
function AdjustShips (array $fleet, int $planet_id, string $sign) : void
{
    global $fleetmap;
    global $db_prefix;
    $planet = LoadPlanetById ($planet_id);

    $need_comma = false;
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ($fleetmap as $i=>$gid)
    {
        if (!isset($planet[$gid])) continue;
        if ($need_comma) $query .= ",";
        $query .= "`$gid` = `$gid` $sign " . $fleet[$gid] ;
        $need_comma = true;
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Dispatch the fleet. No checks are performed. Returns the ID of the fleet.
function DispatchFleet (array $fleet, array $origin, array $target, int $order, int $seconds, array $resources, int $cons, int $when, int $union_id=0, int $deploy_time=0) : int
{
    global $db_prefix;
    global $fleetmap;
    global $transportableResources;
    $uni = LoadUniverse ();
    if ( $uni['freeze'] ) return 0;

    $now = $when;
    $prio = QUEUE_PRIO_FLEET + $order;
    $flight_time = $seconds;

    // Add the fleet.
    $fleet_obj = array ( 'owner_id' => $origin['owner_id'], 'union_id' => $union_id,
        'fuel' => $cons, 'mission' => $order, 
        'start_planet' => $origin['planet_id'], 'target_planet' => $target['planet_id'], 'flight_time' => $flight_time, 'deploy_time' => $deploy_time );
    foreach ($transportableResources as $i=>$rc) $fleet_obj[$rc] = $resources[$rc];
    foreach ($fleetmap as $i=>$gid) $fleet_obj[$gid] = $fleet[$gid];
    $fleet_id = AddDBRow ($fleet_obj, 'fleet');

    // Log entry
    $weeks = $now - 4 * (7 * 24 * 60 * 60);
    $query = "DELETE FROM ".$db_prefix."fleetlogs WHERE start < $weeks;";
    dbquery ($query);
    $fleetlog = array ( 'owner_id' => $origin['owner_id'], 'target_id' => $target['owner_id'], 'union_id' => $union_id, 
        'fuel' => $cons, 'mission' => $order, 'flight_time' => $flight_time, 'deploy_time' => $deploy_time, 'start' => $now, 'end' => $now+$seconds, 
        'origin_g' => $origin['g'], 'origin_s' => $origin['s'], 'origin_p' => $origin['p'], 'origin_type' => $origin['type'], 
        'target_g' => $target['g'], 'target_s' => $target['s'], 'target_p' => $target['p'], 'target_type' => $target['type'] );
    foreach ($transportableResources as $i=>$rc) $fleetlog['p'.$rc] = $origin[$rc];
    foreach ($transportableResources as $i=>$rc) $fleetlog[$rc] = $resources[$rc];
    foreach ($fleetmap as $i=>$gid) $fleetlog[$gid] = $fleet[$gid];
    AddDBRow ($fleetlog, 'fleetlogs');

    // Add the task to the global event queue.
    AddQueue ( $origin['owner_id'], "Fleet", $fleet_id, 0, 0, $now, $seconds, $prio );
    return $fleet_id;
}

// Recall the fleet (if possible)
function RecallFleet (int $fleet_id, int $now=0) : void
{
    $uni = LoadUniverse ( );
    if ( $uni['freeze'] ) return;

    if ($now == 0) $now = time ();
    $fleet_obj = LoadFleet ($fleet_id);
    global $fleetmap;
    $fleet = array ();
    foreach ($fleetmap as $i=>$gid) $fleet[$gid] = $fleet_obj[$gid];

    // If the fleet is already returning, do nothing.
    if ( $fleet_obj['mission'] >= FTYP_RETURN && $fleet_obj['mission'] < FTYP_ORBITING ) return;

    $origin = LoadPlanetById ( $fleet_obj['start_planet'] );
    if ($origin == null) return;
    $target = LoadPlanetById ( $fleet_obj['target_planet'] );
    if ($target == null) return;
    $queue = GetFleetQueue ($fleet_obj['fleet_id']);

    if ($fleet_obj['mission'] < FTYP_RETURN) $new_mission = $fleet_obj['mission'] + FTYP_RETURN;
    else $new_mission = $fleet_obj['mission'] - FTYP_RETURN;
    UserLog ( $fleet_obj['owner_id'], "FLEET", 
        va(loca_lang("DEBUG_LOG_FLEET_RECALL", $uni['lang']), $fleet_obj['fleet_id']) . GetMissionNameDebug ($new_mission) . " " .
        $origin['name'] ." [".$origin['g'].":".$origin['s'].":".$origin['p']."] &lt;- ".$target['name']." [".$target['g'].":".$target['s'].":".$target['p']."]<br>" .
        DumpFleet ($fleet) );

    // For recall missions with a hold, the hold time is used as the return flight time.
    if ($fleet_obj['mission'] < FTYP_RETURN) DispatchFleet ($fleet, $origin, $target, $fleet_obj['mission'] + FTYP_RETURN, $now-$queue['start'],
        $fleet_obj,
        $fleet_obj['fuel'] / 2, $now);
    else DispatchFleet ($fleet, $origin, $target, $fleet_obj['mission'] - FTYP_RETURN, $fleet_obj['deploy_time'],
        $fleet_obj,
        $fleet_obj['fuel'] / 2, $now);

    DeleteFleet ($fleet_obj['fleet_id']);            // delete fleet
    RemoveQueue ( $queue['task_id'] );    // delete the task

    // If the last union fleet is recalled, delete the entire union.
    $union_id = $fleet_obj['union_id'];
    if ( $union_id && ( $fleet_obj['mission'] == FTYP_ACS_ATTACK || $fleet_obj['mission'] == FTYP_ACS_ATTACK_HEAD ) ) 
    {
        $result = EnumUnionFleets ($union_id);
        if ( dbrows ( $result ) == 0 ) RemoveUnion ( $union_id );    // delete union
    }
}

// Load the fleet
function LoadFleet (int $fleet_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE fleet_id = '".$fleet_id."'";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Delete the fleet
function DeleteFleet (int $fleet_id) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."fleet WHERE fleet_id = $fleet_id;";
    dbquery ($query);
}

// Modify the fleet.
function SetFleet (int $fleet_id, array $fleet) : void
{
    global $db_prefix;
    global $fleetmap;
    $query = "UPDATE ".$db_prefix."fleet SET ";
    foreach ( $fleetmap as $i=>$gid ) {
        if ( $i == 0 ) $query .= "`$gid`=".$fleet[$gid];
        else $query .= ", `$gid`=".$fleet[$gid];
    }
    $query .= " WHERE fleet_id=$fleet_id;";
    dbquery ($query);
}

// Get mission description (for debugging)
function GetMissionNameDebug (int $num) : string
{
    switch ($num)
    {
        case FTYP_ATTACK    :      return "Атака убывает";
        case (FTYP_ATTACK+FTYP_RETURN) :      return "Атака возвращается";
        case FTYP_ACS_ATTACK    :      return "Совместная атака убывает";
        case (FTYP_ACS_ATTACK+FTYP_RETURN) :     return "Совместная атака возвращается";
        case FTYP_TRANSPORT    :     return "Транспорт убывает";
        case (FTYP_TRANSPORT+FTYP_RETURN) :     return "Транспорт возвращается";
        case FTYP_DEPLOY    :     return "Оставить убывает";
        case (FTYP_DEPLOY+FTYP_RETURN) :     return "Оставить возвращается";
        case FTYP_ACS_HOLD   :      return "Держаться убывает";
        case (FTYP_ACS_HOLD+FTYP_RETURN) :     return "Держаться возвращается";
        case (FTYP_ACS_HOLD+FTYP_ORBITING) :    return "Держаться на орбите";
        case FTYP_SPY   :      return "Шпионаж убывает";
        case (FTYP_SPY+FTYP_RETURN) :     return "Шпионаж возвращается";
        case FTYP_COLONIZE    :     return "Колонизировать убывает";
        case (FTYP_COLONIZE+FTYP_RETURN) :     return "Колонизировать возвращается";
        case FTYP_RECYCLE    :     return "Переработать убывает";
        case (FTYP_RECYCLE+FTYP_RETURN) :    return "Переработать возвращается";
        case FTYP_DESTROY   :      return "Уничтожить убывает";
        case (FTYP_DESTROY+FTYP_RETURN):      return "Уничтожить возвращается";
        case FTYP_EXPEDITION  :      return "Экспедиция убывает";
        case (FTYP_EXPEDITION+FTYP_RETURN):      return "Экспедиция возвращается";
        case (FTYP_EXPEDITION+FTYP_ORBITING):      return "Экспедиция на орбите";
        case FTYP_MISSILE:       return "Ракетная атака";
        case FTYP_ACS_ATTACK_HEAD  :      return "Атака САБ убывает";
        case (FTYP_ACS_ATTACK_HEAD+FTYP_RETURN) :      return "Атака САБ возвращается";

        default: return "Неизвестно";
    }
}

// Launch interplanetary rockets
function LaunchRockets ( array $origin, array $target, int $seconds, int $amount, int $type ) : int
{
    global $db_prefix;
    $uni = LoadUniverse ( );
    if ( $uni['freeze'] ) return 0;

    if ( $amount > $origin[GID_D_IPM] ) return 0;    // You can't launch more missiles than there are rockets on the planet.

    $now = time ();
    $prio = QUEUE_PRIO_FLEET + FTYP_MISSILE;

    // Write the IPM off the planet.
    $origin['d503'] -= $amount;
    SetPlanetDefense ( $origin['planet_id'], $origin );

    // Add a missile attack.
    $fleet_obj = array ( 'owner_id' => $origin['owner_id'], 'union_id' => 0,
        'fuel' => 0, 'mission' => FTYP_MISSILE, 
        'start_planet' => $origin['planet_id'], 'target_planet' => $target['planet_id'], 'flight_time' => $seconds, 'deploy_time' => 0,
        'ipm_amount' => $amount, 'ipm_target' => $type );
    $fleet_id = AddDBRow ($fleet_obj, 'fleet');

    // Log entry
    $weeks = $now - 4 * (7 * 24 * 60 * 60);
    $query = "DELETE FROM ".$db_prefix."fleetlogs WHERE start < $weeks;";
    dbquery ($query);
    $fleetlog = array ( 'owner_id' => $origin['owner_id'], 'target_id' => $target['owner_id'], 'union_id' => 0,
        'fuel' => 0, 'mission' => FTYP_MISSILE, 'flight_time' => $seconds, 'deploy_time' => 0, 'start' => $now, 'end' => $now+$seconds, 
        'origin_g' => $origin['g'], 'origin_s' => $origin['s'], 'origin_p' => $origin['p'], 'origin_type' => $origin['type'], 
        'target_g' => $target['g'], 'target_s' => $target['s'], 'target_p' => $target['p'], 'target_type' => $target['type'], 
        'ipm_amount' => $amount, 'ipm_target' => $type );
    AddDBRow ($fleetlog, 'fleetlogs');

    // Add the task to the global event queue.
    AddQueue ( $origin['owner_id'], "Fleet", $fleet_id, 0, 0, $now, $seconds, $prio );
    return $fleet_id;
}

// ==================================================================================
// Fleet Task Processing.

function FleetList (array $fleet, string $lang) : string
{
    global $fleetmap;
    $res = "";
    foreach ( $fleetmap as $i=>$gid )
    {
        if ($fleet[$gid] > 0) $res .= loca_lang("NAME_$gid", $lang) . ": " . nicenum ($fleet[$gid]) . " ";
    }
    return $res;
}

// *** Attack ***

function AttackArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
}

// *** Transport ***

function TransportArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    global $transportableResources;

    $oldm = $target[GID_RC_METAL];
    $oldk = $target[GID_RC_CRYSTAL];
    $oldd = $target[GID_RC_DEUTERIUM];

    AdjustResources ( $fleet_obj, $target['planet_id'], '+' );
    UpdatePlanetActivity ( $target['planet_id'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    if ($origin_user == null) {
        return;
    }
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $resources = array ();
    foreach ($transportableResources as $i=>$rc) {
        $resources[$rc] = 0;
    }
    DispatchFleet ($fleet, $origin, $target, FTYP_TRANSPORT+FTYP_RETURN, $fleet_obj['flight_time'], $resources, $fleet_obj['fuel'] / 2, $queue['end']);

    $text = va(loca_lang("FLEET_TRANSPORT_OWN", $origin_user['lang']), 
            ShowGalaxy ($target),
            nicenum($fleet_obj[GID_RC_METAL]),
            nicenum($fleet_obj[GID_RC_CRYSTAL]),
            nicenum($fleet_obj[GID_RC_DEUTERIUM]) );
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_ARRIVE", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);

    // Transport to foreign planet.
    if ( $origin['owner_id'] != $target['owner_id'] )
    {
        $target_user = LoadUser ( $target['owner_id'] );
        loca_add ( "fleetmsg", $target_user['lang'] );

        $text = va(loca_lang("FLEET_TRANSPORT_OTHER", $target_user['lang']),
                $origin_user['oname'],
                $target['name'],
                ShowGalaxy ($target),
                nicenum($fleet_obj[GID_RC_METAL]),
                nicenum($fleet_obj[GID_RC_CRYSTAL]),
                nicenum($fleet_obj[GID_RC_DEUTERIUM]),
                nicenum($oldm),
                nicenum($oldk),
                nicenum($oldd),
                nicenum($oldm+$fleet_obj[GID_RC_METAL]),
                nicenum($oldk+$fleet_obj[GID_RC_CRYSTAL]),
                nicenum($oldd+$fleet_obj[GID_RC_DEUTERIUM]) );
        SendMessage ( $target['owner_id'], 
            loca_lang("FLEET_MESSAGE_OBSERVE", $target_user['lang']), 
            loca_lang("FLEET_MESSAGE_TRADE", $target_user['lang']), 
            $text, MTYP_MISC, $queue['end']);
    }
}

function CommonReturn (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    global $transportableResources;

    // Protection against negative resources (just in case)
    foreach ($transportableResources as $i=>$rc) {
        if (isset($fleet_obj[$rc])) {
            if ( $fleet_obj[$rc] < 0 ) $fleet_obj[$rc] = 0;
        }
    }

    AdjustResources ( $fleet_obj, $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
    UpdatePlanetActivity ( $fleet_obj['start_planet'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    if ($origin_user == null) {
        return;
    }
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_RETURN", $origin_user['lang']),
        FleetList($fleet, $origin_user['lang']),
        ShowGalaxy ($target),
        $origin['name'],
        ShowGalaxy ($origin) );
    if ( ($fleet_obj[GID_RC_METAL] + $fleet_obj[GID_RC_CRYSTAL] + $fleet_obj[GID_RC_DEUTERIUM]) != 0 ) {
        $text .= va(loca_lang("FLEET_RETURN_RES", $origin_user['lang']), 
            nicenum($fleet_obj[GID_RC_METAL]),
            nicenum($fleet_obj[GID_RC_CRYSTAL]),
            nicenum($fleet_obj[GID_RC_DEUTERIUM]) );
    }
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_RETURN", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);
}

// *** Deploy ***

function DeployArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    // Also unload half the fuel
    $cost = $fleet_obj;
    $cost[GID_RC_DEUTERIUM] += floor ($fleet_obj['fuel'] / 2);
    AdjustResources ( $cost, $target['planet_id'], '+' );
    AdjustShips ( $fleet, $fleet_obj['target_planet'], '+' );
    UpdatePlanetActivity ( $target['planet_id'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    if ($origin_user == null) {
        return;
    }
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_DEPLOY", $origin_user['lang']),
        FleetList($fleet, $origin_user['lang']),
        $target['name'],
        ShowGalaxy ($target) );
    $text .= va(loca_lang("FLEET_DEPLOY_RES", $origin_user['lang']),
        nicenum($fleet_obj[GID_RC_METAL]),
        nicenum($fleet_obj[GID_RC_CRYSTAL]),
        nicenum($fleet_obj[GID_RC_DEUTERIUM] + floor ($fleet_obj['fuel'] / 2)) );
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_HOLD", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);
}

// *** ACS Hold ***

// Count the number of fleets sent to hold on the specified planet (flying and in orbit)
function GetHoldingFleetsCount (int $planet_id) : int
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE (mission = ".FTYP_ACS_HOLD." OR mission = ".(FTYP_ACS_HOLD+FTYP_ORBITING).") AND target_planet = $planet_id;";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Check if it is possible to send a fleet to a player to hold on a planet (no more than `maxhold_users` players can hold their fleets on a planet at the same time)
function CanStandHold ( int $planet_id, int $player_id, int $maxhold_users ) : bool
{
    global $db_prefix;
    $query = "SELECT owner_id FROM ".$db_prefix."fleet WHERE (mission = ".FTYP_ACS_HOLD." OR mission = ".(FTYP_ACS_HOLD+FTYP_ORBITING).") AND target_planet = $planet_id;";
    $result = dbquery ($query);
    return dbrows ($result) < $maxhold_users;
}

function HoldingArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    // Update the activity on the planet.
    UpdatePlanetActivity ( $fleet_obj['target_planet'], $queue['end'] );

    // Start an orbit hold task.
    // Make the hold time a flight time (so that it can be used when returning the fleet)
    DispatchFleet ($fleet, $origin, $target, FTYP_ACS_HOLD+FTYP_ORBITING, $fleet_obj['deploy_time'], 
        $fleet_obj, 
        0, $queue['end'], 0, $fleet_obj['flight_time']);
}

function HoldingHold (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    // Return the fleet.
    // The hold time is used as the flight time.
    DispatchFleet ($fleet, $origin, $target, FTYP_ACS_HOLD+FTYP_RETURN, $fleet_obj['deploy_time'],
        $fleet_obj,
        0, $queue['end']);
}

// *** Espionage ***

function SpyArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    global $UnitParam;
    global $fleetmap;
    global $defmap;
    global $buildmap;
    global $resmap;

    $now = $queue['end'];

    $origin_user = LoadUser ( $origin['owner_id'] );
    if ($origin_user == null) return;
    $target_user = LoadUser ( $target['owner_id'] );
    if ($target_user == null) return;

    $origin_ships = $target_ships = $origin_cost = 0;
    foreach ( $fleetmap as $i=>$gid )
    {
        $origin_ships += $fleet_obj[$gid];
        $origin_cost += $fleet_obj[$gid] * $UnitParam[$gid][0];
        if (isset($target[$gid])) {
            $target_ships += $target[$gid];
        }
    }

    $origin_prem = PremiumStatus ($origin_user);
    $target_prem = PremiumStatus ($target_user);
    $origin_tech = $origin_user[GID_R_ESPIONAGE];
    if ($origin_prem['technocrat']) $origin_tech += 2;
    $target_tech = $target_user[GID_R_ESPIONAGE];
    if ($target_prem['technocrat']) $target_tech += 2;

    $bonus = array();
    $bonus['level'] = $origin_tech;
    ModsExecIntRef ('bonus_technology', GID_R_ESPIONAGE, $bonus);
    $origin_tech = max ($bonus['level'], 0);
    $bonus['level'] = $target_tech;
    ModsExecIntRef ('bonus_technology', GID_R_ESPIONAGE, $bonus);
    $target_tech = max ($bonus['level'], 0);

    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "espionage", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );
    loca_add ( "fleetmsg", $target_user['lang'] );

    // A chance at espionage protection
    $level = $origin_tech - $target_tech;
    $level = $level * abs($level) - 1 + $origin_ships;
    $cost = $origin_cost / 1000 / 400;
    $c = sqrt ( pow (2,($origin_ships-($level+1))) ) * ($cost * sqrt($target_ships)*5);
    if ($c > 2) $c = 2;
    $c = rand (0, $c*100) / 100;
    if ($c < 0) $c = 0;
    if ($c > 1) $c = 1;
    $counter = $c * 100;

    $subj = "\n<span class=\"espionagereport\">\n" .
                va(loca_lang("SPY_SUBJ", $origin_user['lang']), $target['name']) . "\n" .
                ShowGalaxy ($target);

    $report = "";

    // Head
    $report .= "<table width=400><tr><td class=c colspan=4>" .
            va(loca_lang("SPY_RESOURCES", $origin_user['lang']), $target['name']) . " " .
            ShowGalaxy ($target) . " " .
            va(loca_lang("SPY_PLAYER", $origin_user['lang']), $target_user['oname'], date ("m-d H:i:s", $now)) .
            "</td></tr>\n";
    $report .= "</div></font></TD></TR><tr><td>".loca_lang("SPY_M", $origin_user['lang'])."</td><td>".nicenum($target[GID_RC_METAL])."</td>\n";
    $report .= "<td>".loca_lang("SPY_K", $origin_user['lang'])."</td><td>".nicenum($target[GID_RC_CRYSTAL])."</td></tr>\n";
    $report .= "<tr><td>".loca_lang("SPY_D", $origin_user['lang'])."</td><td>".nicenum($target[GID_RC_DEUTERIUM])."</td>\n";
    $report .= "<td>".loca_lang("SPY_E", $origin_user['lang'])."</td><td>".nicenum($target[GID_RC_ENERGY])."</td></tr>\n";
    $report .= "</table>\n";

    // Activity
    $report .= "<table width=400><tr><td class=c colspan=4>     </td></tr>\n";
    $report .= "<TR><TD colspan=4><div onmouseover='return overlib(\"&lt;font color=white&gt;".loca_lang("SPY_ACTIVITY", $origin_user['lang'])."&lt;/font&gt;\", STICKY, MOUSEOFF, DELAY, 750, CENTER, WIDTH, 100, OFFSETX, -130, OFFSETY, -10);' onmouseout='return nd();'></TD></TR></table>\n";

    // Fleet on hold
    $result = GetHoldingFleets ( $target['planet_id'] );
    $holding_fleet = array ();
    foreach ( $fleetmap as $i=>$gid ) {
        $holding_fleet[$gid] = 0;
    }    
    while ( $fobj = dbarray ($result) )
    {
        foreach ( $fleetmap as $i=>$gid ) {
            $holding_fleet[$gid] += $fobj[$gid];
        }
    }

    // Fleet
    if ( $level > 0 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_FLEET", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $fleetmap as $i=>$gid )
        {
            $amount = 0;
            if (isset($target[$gid])) {
                $amount += $target[$gid];
            }
            $amount += $holding_fleet[$gid];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    // Defense
    if ( $level > 1 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_DEFENSE", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $defmap as $i=>$gid )
        {
            $amount = 0;
            if (isset($target[$gid])) {
                $amount += $target[$gid];
            }
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    // Buildings
    if ( $level > 3 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_BUILDINGS", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $buildmap as $i=>$gid )
        {
            $amount = $target[$gid];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    // Research
    if ( $level > 5 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_RESEARCH", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $resmap as $i=>$gid )
        {
            $amount = $target_user[$gid];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    $report .= "<center>".va(loca_lang("SPY_COUNTER", $origin_user['lang']), floor($counter))."</center>\n";
    $report .= "<center><a href='#' onclick='showFleetMenu(".$target['g'].",".$target['s'].",".$target['p'].",".GetPlanetType($target).",1);'>".loca_lang("SPY_ATTACK", $origin_user['lang'])."</a></center>\n";

    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        $subj, 
        $report, MTYP_SPY_REPORT, $queue['end'], $target['planet_id']);

    // Send a message to other player about spying.
    $text = va(loca_lang("FLEET_SPY_OTHER", $target_user['lang']), 
            $origin['name'],
            ShowGalaxy ($origin),
            $target['name'],
            ShowGalaxy ($target),
            $counter ) ;
    SendMessage ( $target['owner_id'],
        loca_lang("FLEET_MESSAGE_OBSERVE", $target_user['lang']),
        loca_lang("FLEET_MESSAGE_SPY", $target_user['lang']),
        $text, MTYP_MISC, $queue['end']);

    // Update activity on the foreign planet.
    UpdatePlanetActivity ( $fleet_obj['target_planet'], $queue['end'] );

    // Return the fleet.
    if ( mt_rand (0, 100) < $counter ) StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
    else DispatchFleet ($fleet, $origin, $target, FTYP_SPY+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj, $fleet_obj['fuel'] / 2, $queue['end']);
}

function SpyReturn (array $queue, array $fleet_obj, array $fleet) : void
{
    AdjustResources ( $fleet_obj, $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
    UpdatePlanetActivity ( $fleet_obj['start_planet'], $queue['end'] );
}

// *** Colonize ***

function ColonizationArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    global $db_prefix;

    $origin_user = LoadUser ( $origin['owner_id'] );
    if ($origin_user == null) return;
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_COLONIZE", $origin_user['lang']), 
                ShowGalaxy ($target) );

    if ( !HasPlanet($target['g'], $target['s'], $target['p']) )    // If the place is unoccupied, then colonization is successful.
    {
        // If the number of planets in the empire is greater than the maximum, then don't establish a new colony.
        $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".$fleet_obj['owner_id']."' AND (type = ".PTYP_PLANET.");";
        $result = dbquery ($query);
        $num_planets = dbrows ($result);
        if ( $num_planets >= MAX_PLANET )
        {
            $text .= loca_lang("FLEET_COLONIZE_MAX", $origin_user['lang']);

            // Add an abandoned colony.
            $id = CreateAbandonedColony ( $target['g'], $target['s'], $target['p'], $queue['end'] );
        }
        else
        {
            $text .= loca_lang("FLEET_COLONIZE_SUCCESS", $origin_user['lang']);

            // Create a new colony.
            $id = CreatePlanet ( $target['g'], $target['s'], $target['p'], $fleet_obj['owner_id'], 1, 0, 0, $queue['end'] );
            Debug ( "Player ".$origin['owner_id']." has colonized the planet $id [".$target['g'].":".$target['s'].":".$target['p']."]");

            // Take 1 colony ship away from the fleet
            if ( $fleet[GID_F_COLON] > 0 ) {
                $fleet[GID_F_COLON]--;
                $met = $kris = $deut = $energy = 0;
                $cost = TechPrice ( GID_F_COLON, 1 );
                AdjustStats ( $origin['owner_id'], TechPriceInPoints($cost), 1, 0, '-' );
                RecalcRanks ();
            }
        }

        // Return the fleet, if there's anything left.
        global $fleetmap;
        $num_ships = 0;
        foreach ($fleetmap as $i=>$gid) {
            $num_ships += $fleet[$gid];
        }
        if ($num_ships > 0) {
            if ($target['type'] == PTYP_COLONY_PHANTOM) DestroyPlanet ( $target['planet_id'] );
            $target = LoadPlanetById ($id);
            DispatchFleet ($fleet, $origin, $target, FTYP_COLONIZE+FTYP_RETURN, $fleet_obj['flight_time'], 
                $fleet_obj, 
                $fleet_obj['fuel'] / 2, $queue['end']);
        }
        else {
            if ($target['type'] == PTYP_COLONY_PHANTOM) DestroyPlanet ( $target['planet_id'] );
        }
    }
    else
    {
        $text .= loca_lang("FLEET_COLONIZE_FAIL", $origin_user['lang']);

        // Return the fleet.
        DispatchFleet ($fleet, $origin, $target, FTYP_COLONIZE+FTYP_RETURN, $fleet_obj['flight_time'],
            $fleet_obj,
            $fleet_obj['fuel'] / 2, $queue['end']);
    }

    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_COLONIZE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_COLONIZE_SUBJ", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);
}

function ColonizationReturn (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    AdjustResources ( $fleet_obj, $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
    UpdatePlanetActivity ( $fleet_obj['start_planet'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    if ($origin_user == null) return;
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_RETURN", $origin_user['lang']), 
            FleetList($fleet, $origin_user['lang']),
            ShowGalaxy ($target),
            $origin['name'],
            ShowGalaxy ($origin) );
    if ( ($fleet_obj[GID_RC_METAL] + $fleet_obj[GID_RC_CRYSTAL] + $fleet_obj[GID_RC_DEUTERIUM]) != 0 ) {
        $text .= va(loca_lang("FLEET_RETURN_RES", $origin_user['lang']), 
            nicenum($fleet_obj[GID_RC_METAL]),
            nicenum($fleet_obj[GID_RC_CRYSTAL]),
            nicenum($fleet_obj[GID_RC_DEUTERIUM]) );
    }
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_RETURN", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);

    // Delete the colonization phantom.
    if ($target['type'] == PTYP_COLONY_PHANTOM) DestroyPlanet ( $target['planet_id'] );
}

// *** Recycle ***

function RecycleArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    global $transportableResources;
    if ( $fleet[GID_F_RECYCLER] == 0 ) Error ( "Attempt to harvest DF without recyclers" );
    if ( $target['type'] != PTYP_DF ) Error ( "Only debris fields can be recycled!" );

    $res_total = 0;
    foreach ($transportableResources as $i=>$rc) {
        if (isset($fleet_obj[$rc])) {
            $res_total += $fleet_obj[$rc];
        }
    }
    $sum_cargo = FleetCargoSummary ( $fleet ) - $res_total;
    $recycler_cargo = FleetCargo (GID_F_RECYCLER) * $fleet[GID_F_RECYCLER];
    $cargo = min ($recycler_cargo, $sum_cargo);

    $harvest = HarvestDebris ( $target['planet_id'], $cargo, $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    if ($origin_user == null) return;
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $subj = "\n<span class=\"espionagereport\">".loca_lang("FLEET_MESSAGE_INTEL", $origin_user['lang'])."</span>\n";   
    $report = va(loca_lang("FLEET_RECYCLE", $origin_user['lang']), 
        nicenum($fleet[GID_F_RECYCLER]),
        nicenum($cargo),
        nicenum($target[GID_RC_METAL]),
        nicenum($target[GID_RC_CRYSTAL]),
        nicenum($harvest[GID_RC_METAL]),
        nicenum($harvest[GID_RC_CRYSTAL]) );

    // Return the fleet.
    $resources = array ();
    foreach ($transportableResources as $i=>$rc) {
        $resources[$rc] = $fleet_obj[$rc] + $harvest[$rc];
    }
    DispatchFleet ($fleet, $origin, $target, FTYP_RECYCLE+FTYP_RETURN, $fleet_obj['flight_time'], $resources, $fleet_obj['fuel'] / 2, $queue['end']);

    SendMessage ( $fleet_obj['owner_id'], loca_lang("FLEET_MESSAGE_FLEET", $origin_user['lang']), $subj, $report, MTYP_MISC, $queue['end']);
}

// *** Destroy ***

function DestroyArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
}

// *** Expedition ***

// See expedition.php

// *** Missile attack ***

function RocketAttackArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    RocketAttack ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
}

function Queue_Fleet_End (array $queue) : void
{
    global $GlobalUser;
    global $fleetmap;
    global $transportableResources;
    $fleet_obj = LoadFleet ( $queue['sub_id'] );
    if ( $fleet_obj == null ) return;

    foreach ($transportableResources as $i=>$rc) {
        if (isset($fleet_obj[$rc])) {
            if ( $fleet_obj[$rc] < 0 ) $fleet_obj[$rc] = 0;
        }
    }

    $fleet = array ();
    foreach ($fleetmap as $i=>$gid) $fleet[$gid] = $fleet_obj[$gid];

    // Update resource production on planets
    $origin = GetUpdatePlanet ( $fleet_obj['start_planet'], $queue['end'] );
    if ($origin == null) return;
    $target = GetUpdatePlanet ( $fleet_obj['target_planet'], $queue['end'] );
    if ($target == null) return;

    switch ( $fleet_obj['mission'] )
    {
        case FTYP_ATTACK: AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ATTACK+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_ACS_ATTACK: AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_ATTACK+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_TRANSPORT: TransportArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_TRANSPORT+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_DEPLOY: DeployArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_DEPLOY+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_ACS_HOLD: HoldingArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_HOLD+FTYP_ORBITING): HoldingHold ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_HOLD+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_SPY: SpyArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_SPY+FTYP_RETURN): SpyReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_COLONIZE: ColonizationArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_COLONIZE+FTYP_RETURN): ColonizationReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_RECYCLE: RecycleArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_RECYCLE+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_DESTROY: DestroyArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_DESTROY+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_EXPEDITION: ExpeditionArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_EXPEDITION+FTYP_ORBITING): ExpeditionHold ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_EXPEDITION+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_MISSILE: RocketAttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_ACS_ATTACK_HEAD: AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_ATTACK_HEAD+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;

        // Transfer control to modifications
        default:
            $param = [];
            $param['queue'] = $queue;
            $param['fleet_obj'] = $fleet_obj;
            $param['fleet'] = $fleet;
            $param['origin'] = $origin;
            $param['target'] = $target;
            ModsExecArr ('fleet_handler', $param);
            break;
    }

    if ( $fleet_obj['union_id'] && $fleet_obj['mission'] < FTYP_RETURN )    // remove all fleets and union missions so that ACS attack will no longer trigger
    {
        $union_id = $fleet_obj['union_id'];
        $result = EnumUnionFleets ( $union_id );
        $rows = dbrows ($result);
        while ($rows--)
        {
            $fleet_obj = dbarray ($result);
            $queue = GetFleetQueue ( $fleet_obj['fleet_id'] );
            DeleteFleet ($fleet_obj['fleet_id']);    // delete fleet
            RemoveQueue ( $queue['task_id'] );    // delete task
        }
        RemoveUnion ( $union_id );    // delete union
    }
    else
    {
        DeleteFleet ($fleet_obj['fleet_id']);    // delete fleet
        RemoveQueue ( $queue['task_id'] );    // delete task
    }

    $player_id = $fleet_obj['owner_id'];
    if ( $GlobalUser['player_id'] == $player_id) { 
        InvalidateUserCache ();
        $GlobalUser = LoadUser ( $player_id );    // update the current user's data
    }
}

// ==================================================================================

// Flight logs.

function FleetlogsMissionText (int $num) : void
{
    if ($num >= FTYP_CUSTOM) {
        $desc = "(Custom)";
    }
    else if ($num >= FTYP_ORBITING)
    {
        $desc = "<a title=\"На планете\">(Д)</a>";
        $num -= FTYP_ORBITING;
    }
    else if ($num >= FTYP_RETURN)
    {
        $desc = "<a title=\"Возвращение к планете\">(В)</a>";
        $num -= FTYP_RETURN;
    }
    else $desc = "<a title=\"Уход на задание\">(У)</a>";

    echo "      <a title=\"\">".loca("FLEET_ORDER_$num")."</a>\n$desc\n";
}

function FleetlogsFromPlayer (int $player_id, array|null $missions) : mixed
{
    global $db_prefix;

    $list = "";
    if ($missions != null) {
        $list .= "(";
        foreach ($missions as $i=>$num) {
            if ($i > 0) $list .= "OR ";
            $list .= "mission = $num ";
        }
        $list .= ") AND";
    }

    $query = "SELECT * FROM ".$db_prefix."fleetlogs WHERE $list owner_id = $player_id ORDER BY start ASC;";
    return dbquery ( $query );
}

function FleetlogsToPlayer (int $player_id, array|null $missions) : mixed
{
    global $db_prefix;

    $list = "";
    if ($missions != null) {
        $list = "(";
        foreach ($missions as $i=>$num) {
            if ($i > 0) $list .= "OR ";
            $list .= "mission = $num ";
        }
        $list .= ") AND";
    }

    $query = "SELECT * FROM ".$db_prefix."fleetlogs WHERE $list owner_id <> target_id AND target_id = $player_id ORDER BY start ASC;";
    return dbquery ( $query );
}

function DumpFleet (array $fleet) : string
{
    global $fleetmap;
    $result = "";
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet[$gid];
        if ( $amount != 0 ) $result .= loca ("NAME_$gid") . " " . nicenum($amount) . " ";
    }
    return $result;
}

?>