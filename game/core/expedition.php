<?php

// Expeditions.
// Expedition messages are sent out in the user's language (the loca_lang method is used).

// Important! This part of the game may not be accurately implemented, because there is no source code.

// Expedition visit counter is stored as the metal value on the far space planet object.

// Expedition result (it is not needed anywhere else except for this module, so there is no need to add it to defs.php)
const EXP_NOTHING = 0;
const EXP_ALIENS = 1;
const EXP_PIRATES = 2;
const EXP_DARK_MATTER = 3;
const EXP_BLACK_HOLE = 4;
const EXP_DELAY = 5;
const EXP_ACCEL = 6;
const EXP_RESOURCES = 7;
const EXP_FLEET = 8;
const EXP_TRADER = 9;

// Count the number of active expeditions of the specified player.
function GetExpeditionsCount (int $player_id) : int
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE (mission = ".FTYP_EXPEDITION." OR mission = ".(FTYP_EXPEDITION+FTYP_RETURN)." OR mission = ".(FTYP_EXPEDITION+FTYP_ORBITING).") AND owner_id = $player_id;";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Load Expedition Settings.
function LoadExpeditionSettings () : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."exptab;";
    $result = dbquery ($query);
    return dbarray ($result);
}

function SaveExpeditionSettings (array $exptab) : void
{
    global $db_prefix;

    $query = "UPDATE ".$db_prefix."exptab SET ".
        "chance_success='".$exptab['chance_success']."', ".

        "depleted_min=".$exptab['depleted_min'].", ".
        "depleted_med=".$exptab['depleted_med'].", ".
        "depleted_max=".$exptab['depleted_max'].", ".

        "chance_depleted_min=".$exptab['chance_depleted_min'].", ".
        "chance_depleted_med=".$exptab['chance_depleted_med'].", ".
        "chance_depleted_max=".$exptab['chance_depleted_max'].", ".

        "chance_alien=".$exptab['chance_alien'].", ".
        "chance_pirates=".$exptab['chance_pirates'].", ".
        "chance_dm=".$exptab['chance_dm'].", ".
        "chance_lost=".$exptab['chance_lost'].", ".
        "chance_delay=".$exptab['chance_delay'].", ".
        "chance_accel=".$exptab['chance_accel'].", ".
        "chance_res=".$exptab['chance_res'].", ".
        "chance_fleet=".$exptab['chance_fleet'].", ".

        "dm_factor=".$exptab['dm_factor'].", ".

        "score_cap1=".$exptab['score_cap1'].", ".
        "score_cap2=".$exptab['score_cap2'].", ".
        "score_cap3=".$exptab['score_cap3'].", ".
        "score_cap4=".$exptab['score_cap4'].", ".
        "score_cap5=".$exptab['score_cap5'].", ".
        "score_cap6=".$exptab['score_cap6'].", ".
        "score_cap7=".$exptab['score_cap7'].", ".
        "score_cap8=".$exptab['score_cap8'].", ".
        "limit_cap1=".$exptab['limit_cap1'].", ".
        "limit_cap2=".$exptab['limit_cap2'].", ".
        "limit_cap3=".$exptab['limit_cap3'].", ".
        "limit_cap4=".$exptab['limit_cap4'].", ".
        "limit_cap5=".$exptab['limit_cap5'].", ".
        "limit_cap6=".$exptab['limit_cap6'].", ".
        "limit_cap7=".$exptab['limit_cap7'].", ".
        "limit_cap8=".$exptab['limit_cap8'].", ".
        "limit_max=".$exptab['limit_max'].";" ;

    dbquery ($query);
}

// Count the points of the expeditionary fleet.
function ExpPoints ( array $fleet ) : int
{
    global $fleetmap;
    $structure = 0;

    foreach ( $fleetmap as $i=>$gid )
    {
        $amount = $fleet[$gid];
        $res = ShipyardPrice ( $gid );
        $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL]; $d = $res[GID_RC_DEUTERIUM]; $e = $res[GID_RC_ENERGY];
        $structure += ($m + $k) * $amount;
    }

    return $structure / 1000;
}

// The upper limit of expedition points.
function ExpUpperLimit (array $exptab) : int
{
    $user = GetTop1 ();
    if ($user) {

        $score = $user['score1'] / 1000;

        if ($score < $exptab['score_cap1']) return $exptab['limit_cap1'];
        if ($score < $exptab['score_cap2']) return $exptab['limit_cap2'];
        if ($score < $exptab['score_cap3']) return $exptab['limit_cap3'];
        if ($score < $exptab['score_cap4']) return $exptab['limit_cap4'];
        if ($score < $exptab['score_cap5']) return $exptab['limit_cap5'];
        if ($score < $exptab['score_cap6']) return $exptab['limit_cap6'];
        if ($score < $exptab['score_cap7']) return $exptab['limit_cap7'];
        if ($score < $exptab['score_cap8']) return $exptab['limit_cap8'];

        return $exptab['limit_max'];
    }
    return $exptab['limit_cap1'];
}

// Nothing happened.
function Exp_NothingHappens (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    $msg = array (
        loca_lang ("EXP_NOTHING_1", $lang),
        loca_lang ("EXP_NOTHING_2", $lang),
        loca_lang ("EXP_NOTHING_3", $lang),
        loca_lang ("EXP_NOTHING_4", $lang),
        loca_lang ("EXP_NOTHING_5", $lang),
        loca_lang ("EXP_NOTHING_6", $lang),
        loca_lang ("EXP_NOTHING_7", $lang),
        loca_lang ("EXP_NOTHING_8", $lang),
        loca_lang ("EXP_NOTHING_9", $lang),
        loca_lang ("EXP_NOTHING_10", $lang),
        loca_lang ("EXP_NOTHING_11", $lang),
        loca_lang ("EXP_NOTHING_12", $lang),
    );

    // Bring back the fleet.
    // The hold time is used as the flight time.
    DispatchFleet ($fleet, $origin, $target, (FTYP_RETURN+FTYP_EXPEDITION), $fleet_obj['deploy_time'],
        $fleet_obj[GID_RC_METAL], $fleet_obj[GID_RC_CRYSTAL], $fleet_obj[GID_RC_DEUTERIUM],
        0, $queue['end']);

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// Message from on-board engineer (visit counter)
function Logbook (int $expcount, array $exptab, string $lang) : string
{
    $msg_1 = array (
        loca_lang ("EXP_NOT_DEPLETED_1", $lang),
        loca_lang ("EXP_NOT_DEPLETED_2", $lang),
    );
    $msg_2 = array (
        loca_lang ("EXP_DEPLETED_MIN_1", $lang),
        loca_lang ("EXP_DEPLETED_MIN_2", $lang),
        loca_lang ("EXP_DEPLETED_MIN_3", $lang),
    );
    $msg_3 = array (
        loca_lang ("EXP_DEPLETED_MED_1", $lang),
        loca_lang ("EXP_DEPLETED_MED_2", $lang),
        loca_lang ("EXP_DEPLETED_MED_3", $lang),
    );
    $msg_4 = array (
        loca_lang ("EXP_DEPLETED_MAX_1", $lang),
        loca_lang ("EXP_DEPLETED_MAX_2", $lang),
        loca_lang ("EXP_DEPLETED_MAX_3", $lang),
    );

    if ( $expcount <= $exptab['depleted_min'] ) {
        $n = mt_rand ( 0, count($msg_1) - 1 );
        return $msg_1[$n];
    }
    else if ( $expcount <= $exptab['depleted_med'] ) {
        $n = mt_rand ( 0, count($msg_2) - 1 );
        return $msg_2[$n];
    }
    else if ( $expcount <= $exptab['depleted_max'] ) {
        $n = mt_rand ( 0, count($msg_3) - 1 );
        return $msg_3[$n];
    }
    else {
        $n = mt_rand ( 0, count($msg_4) - 1 );
        return $msg_4[$n];
    }
}

// ------------- 
// Successful events of the expedition

// Encountering aliens
function Exp_BattleAliens (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    $weak = array (
        loca_lang ("EXP_ALIENS_WEAK_1", $lang),
        loca_lang ("EXP_ALIENS_WEAK_2", $lang),
        loca_lang ("EXP_ALIENS_WEAK_3", $lang),
        loca_lang ("EXP_ALIENS_WEAK_4", $lang),
    );
    $medium = array (
        loca_lang ("EXP_ALIENS_MED_1", $lang),
        loca_lang ("EXP_ALIENS_MED_2", $lang),
        loca_lang ("EXP_ALIENS_MED_3", $lang),
    );
    $strong = array (
        loca_lang ("EXP_ALIENS_STRONG_1", $lang),
        loca_lang ("EXP_ALIENS_STRONG_2", $lang),
    );

    // Determine the level of alien
    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // strong
        $level = 2;
        $n = mt_rand ( 0, count($strong) - 1 );
        $msg = $strong[$n];
    }
    else if ( $chance >= 90 ) {    // medium
        $level = 1;
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // weak
        $level = 0;
        $n = mt_rand ( 0, count($weak) - 1 );
        $msg = $weak[$n];
    }

    ExpeditionBattle ( $fleet_obj['fleet_id'], false, $level, $queue['end'] );

    return $msg;
}

// ---

// Meet the pirates
function Exp_BattlePirates (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    $weak = array (
        loca_lang ("EXP_PIRATES_WEAK_1", $lang),
        loca_lang ("EXP_PIRATES_WEAK_2", $lang),
        loca_lang ("EXP_PIRATES_WEAK_3", $lang),
        loca_lang ("EXP_PIRATES_WEAK_4", $lang),
        loca_lang ("EXP_PIRATES_WEAK_5", $lang),
    );
    $medium = array (
        loca_lang ("EXP_PIRATES_MED_1", $lang),
        loca_lang ("EXP_PIRATES_MED_2", $lang),
        loca_lang ("EXP_PIRATES_MED_3", $lang),
    );
    $strong = array (
        loca_lang ("EXP_PIRATES_STRONG_1", $lang),
        loca_lang ("EXP_PIRATES_STRONG_2", $lang),
    );

    // Determine the level of the pirates
    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // strong
        $level = 2;
        $n = mt_rand ( 0, count($strong) - 1 );
        $msg = $strong[$n];
    }
    else if ( $chance >= 90 ) {    // medium
        $level = 1;
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // weak
        $level = 0;
        $n = mt_rand ( 0, count($weak) - 1 );
        $msg = $weak[$n];
    }

    ExpeditionBattle ( $fleet_obj['fleet_id'], true, $level, $queue['end'] );

    return $msg;
}

// ---

// Finding Dark Matter
function Exp_DarkMatterFound (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    global $db_prefix;
    $player_id = $fleet_obj['owner_id'];

    $small = array (
        loca_lang ("EXP_DMFOUND_SMALL_1", $lang),
        loca_lang ("EXP_DMFOUND_SMALL_2", $lang),
        loca_lang ("EXP_DMFOUND_SMALL_3", $lang),
        loca_lang ("EXP_DMFOUND_SMALL_4", $lang),
        loca_lang ("EXP_DMFOUND_SMALL_5", $lang),
    );
    $medium = array (
        loca_lang ("EXP_DMFOUND_MED_1", $lang),
        loca_lang ("EXP_DMFOUND_MED_2", $lang),
        loca_lang ("EXP_DMFOUND_MED_3", $lang),
    );
    $large = array (
        loca_lang ("EXP_DMFOUND_LARGE_1", $lang),
        loca_lang ("EXP_DMFOUND_LARGE_2", $lang),
    );

    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // large
        $dm = mt_rand ( 501, 2076 );
        $n = mt_rand ( 0, count($large) - 1 );
        $msg = $large[$n];
    }
    else if ( $chance >= 90 ) {    // average
        $dm = mt_rand ( 201, 500 );
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // small
        $dm = mt_rand ( 100, 200 );
        $n = mt_rand ( 0, count($small) - 1 );
        $msg = $small[$n];
    }

    $dm_factor = $exptab['dm_factor'];
    if ($dm_factor == 0) $dm_factor = 1;
    $dm *= $dm_factor;

    $msg .= va ( loca_lang("EXP_FOUND", $lang), nicenum($dm), loca_lang("NAME_".GID_RC_DM, $lang) );

    // Credit DM
    $query = "UPDATE ".$db_prefix."users SET dmfree = dmfree + ".$dm." WHERE player_id=$player_id;";
    dbquery ($query);

    // Bring back the fleet.
    // The hold time is used as the flight time.
    DispatchFleet ($fleet, $origin, $target, (FTYP_RETURN+FTYP_EXPEDITION), $fleet_obj['deploy_time'],
        $fleet_obj[GID_RC_METAL], $fleet_obj[GID_RC_CRYSTAL], $fleet_obj[GID_RC_DEUTERIUM],
        0, $queue['end']);

    return $msg;
}

// ---

// The loss of the entire fleet
function Exp_LostFleet (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    $msg = array (
        loca_lang ("EXP_LOST_1", $lang),
        loca_lang ("EXP_LOST_2", $lang),
        loca_lang ("EXP_LOST_3", $lang),
        loca_lang ("EXP_LOST_4", $lang),
    );

    // Write off points.
    $points = $fpoints = 0;
    $price = FleetPrice ( $fleet_obj );
    AdjustStats ( $fleet_obj['owner_id'], $price['points'], $price['fpoints'], 0, '-' );
    RecalcRanks ();

    // The fleet doesn't need to be returned...

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// ---

// Delayed return of the expedition
function Exp_DelayFleet (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    $msg = array (
        loca_lang ("EXP_DELAY_1", $lang),
        loca_lang ("EXP_DELAY_2", $lang),
        loca_lang ("EXP_DELAY_3", $lang),
        loca_lang ("EXP_DELAY_4", $lang),
        loca_lang ("EXP_DELAY_5", $lang),
        loca_lang ("EXP_DELAY_6", $lang),
    );

    $hold_time = $fleet_obj['flight_time'];

    $chance = mt_rand (0, 99);
    if ( $chance >= 99 )  $delay = $hold_time * 5;
    else if ( $chance >= 90 )  $delay = $hold_time * 3;
    else  $delay = $hold_time * 2;

    // Bring back the fleet.
    // The hold time is used as the flight time.
    DispatchFleet ($fleet, $origin, $target, (FTYP_RETURN+FTYP_EXPEDITION), $fleet_obj['deploy_time'] + $delay,
        $fleet_obj[GID_RC_METAL], $fleet_obj[GID_RC_CRYSTAL], $fleet_obj[GID_RC_DEUTERIUM],
        0, $queue['end']);

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// Accelerating the return of the expedition
function Exp_AccelFleet (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    $msg = array (
        loca_lang ("EXP_ACCEL_1", $lang),
        loca_lang ("EXP_ACCEL_2", $lang),
        loca_lang ("EXP_ACCEL_3", $lang),
    );

    $chance = mt_rand (0, 99);
    if ( $chance >= 99 )  $ratio = 5;
    else if ( $chance >= 90 )  $ratio = 3;
    else  $ratio = 2;

    // Bring back the fleet.
    // The hold time is used as the flight time.
    DispatchFleet ($fleet, $origin, $target, (FTYP_RETURN+FTYP_EXPEDITION), $fleet_obj['deploy_time'] / $ratio,
        $fleet_obj[GID_RC_METAL], $fleet_obj[GID_RC_CRYSTAL], $fleet_obj[GID_RC_DEUTERIUM],
        0, $queue['end']);

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// ---

// Finding resources
function Exp_ResourcesFound (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    $small = array (
        loca_lang ("EXP_RESFOUND_SMALL_1", $lang),
        loca_lang ("EXP_RESFOUND_SMALL_2", $lang),
        loca_lang ("EXP_RESFOUND_SMALL_3", $lang),
        loca_lang ("EXP_RESFOUND_SMALL_4", $lang),
    );
    $medium = array (
        loca_lang ("EXP_RESFOUND_MED_1", $lang),
        loca_lang ("EXP_RESFOUND_MED_2", $lang),
        loca_lang ("EXP_RESFOUND_MED_3", $lang),
    );
    $large = array (
        loca_lang ("EXP_RESFOUND_LARGE_1", $lang),
        loca_lang ("EXP_RESFOUND_LARGE_2", $lang),
    );
    $footer = array (
        loca_lang ("EXP_RESFOUND_LOGBOOK_1", $lang),
        loca_lang ("EXP_RESFOUND_LOGBOOK_2", $lang),
        loca_lang ("EXP_RESFOUND_LOGBOOK_3", $lang),
        loca_lang ("EXP_RESFOUND_LOGBOOK_4", $lang),
    );
    $resname = array ( loca_lang ("NAME_".GID_RC_METAL, $lang), loca_lang ("NAME_".GID_RC_CRYSTAL, $lang), loca_lang ("NAME_".GID_RC_DEUTERIUM, $lang) );

    // Calculate the type of resource found
    $type = mt_rand (0, 2);

    // Calculate deposit size
    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // large
        $roll = mt_rand (51, 100) * 2;
        $n = mt_rand ( 0, count($large) - 1 );
        $msg = $large[$n];
    }
    else if ( $chance >= 90 ) {    // average
        $roll = mt_rand (26, 50) * 2;
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // small
        $roll = mt_rand (5, 25) * 2;
        $n = mt_rand ( 0, count($small) - 1 );
        $msg = $small[$n];
    }

    if ( $type == 1) $roll /= 2;
    else if ( $type == 2) $roll /= 3;

    // Calculate the quantity of the resource found
    $points = min ( max ( 200, ExpPoints ($fleet)), ExpUpperLimit($exptab) );
    $cargo = max (0, FleetCargoSummary ($fleet) - ($fleet_obj[GID_RC_METAL] + $fleet_obj[GID_RC_CRYSTAL] + $fleet_obj[GID_RC_DEUTERIUM]));
    $amount = $roll * $points;

    // The number of resources found is reduced to the total carrying capacity of the fleet
    if ( $cargo < $amount ) {
        $amount = $cargo;
        $no_cargo = true;
    }
    else $no_cargo = false;

    $msg .= va ( loca_lang("EXP_FOUND", $lang), nicenum($amount), $resname[$type]);
    if ( $no_cargo ) {
        $n = mt_rand ( 0, count($footer) - 1 );
        $msg .= "<br><br>" . $footer[$n];
    }

    $m = $k = $d = 0;
    if ( $type == 0) $m = $amount;
    else if ( $type == 1) $k = $amount;
    else if ( $type == 2) $d = $amount;

    // Bring back the fleet.
    // The hold time is used as the flight time.
    DispatchFleet ($fleet, $origin, $target, (FTYP_RETURN+FTYP_EXPEDITION), $fleet_obj['deploy_time'], 
        $fleet_obj[GID_RC_METAL] + $m, $fleet_obj[GID_RC_CRYSTAL] + $k, $fleet_obj[GID_RC_DEUTERIUM] + $d,
        0, $queue['end']);

    return $msg;
}

// ---

// Finding ships
function Exp_FleetFound (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    global $UnitParam;
    global $fleetmap;

    $small = array (
        loca_lang ("EXP_FLEET_SMALL_1", $lang),
        loca_lang ("EXP_FLEET_SMALL_2", $lang),
        loca_lang ("EXP_FLEET_SMALL_3", $lang),
        loca_lang ("EXP_FLEET_SMALL_4", $lang),
    );
    $medium = array (
        loca_lang ("EXP_FLEET_MED_1", $lang),
        loca_lang ("EXP_FLEET_MED_2", $lang),
    );
    $large = array (
        loca_lang ("EXP_FLEET_LARGE_1", $lang),
        loca_lang ("EXP_FLEET_LARGE_2", $lang),
    );
    $footer = array (
        loca_lang ("EXP_FLEET_LOGBOOK_1", $lang),
        loca_lang ("EXP_FLEET_LOGBOOK_2", $lang),
        loca_lang ("EXP_FLEET_LOGBOOK_3", $lang),
    );

    $points = $fpoints = 0;
    $found = array ();

    // Calculate the number of fleets found
    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // large
        $roll = mt_rand (101, 200);
        $n = mt_rand ( 0, count($large) - 1 );
        $msg = $large[$n];
    }
    else if ( $chance >= 90 ) {    // average
        $roll = mt_rand (51, 100);
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // small
        $roll = mt_rand (2, 50);
        $n = mt_rand ( 0, count($small) - 1 );
        $msg = $small[$n];
    }

    // Calculate the structure of the found fleet.
    $epoints = min ( ExpPoints ($fleet), ExpUpperLimit($exptab) );
    $structure = max ( 7000, floor ($roll * $epoints / 2) );
    $no_structure = false;

    // Possible types of ships found
    if ( $fleet[GID_F_PROBE] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC );    // Espionage Probe
    if ( $fleet[GID_F_SC] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC, GID_F_LC );    // Small Cargo
    if ( $fleet[GID_F_LF] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC, GID_F_LF, GID_F_LC );    // Light Fighter
    if ( $fleet[GID_F_LC] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC, GID_F_LF, GID_F_LC, GID_F_HF );    // Large Cargo
    if ( $fleet[GID_F_HF] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC, GID_F_LF, GID_F_LC, GID_F_HF, GID_F_CRUISER );    // Heavy Fighter
    if ( $fleet[GID_F_CRUISER] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC, GID_F_LF, GID_F_LC, GID_F_HF, GID_F_CRUISER, GID_F_BATTLESHIP );    // Cruiser
    if ( $fleet[GID_F_BATTLESHIP] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC, GID_F_LF, GID_F_LC, GID_F_HF, GID_F_CRUISER, GID_F_BATTLESHIP, GID_F_BATTLECRUISER );     // Battleship
    if ( $fleet[GID_F_BATTLECRUISER] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC, GID_F_LF, GID_F_LC, GID_F_HF, GID_F_CRUISER, GID_F_BATTLESHIP, GID_F_BATTLECRUISER, GID_F_BOMBER );    // Battlecruiser
    if ( $fleet[GID_F_BOMBER] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC, GID_F_LF, GID_F_LC, GID_F_HF, GID_F_CRUISER, GID_F_BATTLESHIP, GID_F_BATTLECRUISER, GID_F_BOMBER, GID_F_DESTRO );    // Bomber
    if ( $fleet[GID_F_DESTRO] > 0 ) $found = array ( GID_F_PROBE, GID_F_SC, GID_F_LF, GID_F_LC, GID_F_HF, GID_F_CRUISER, GID_F_BATTLESHIP, GID_F_BATTLECRUISER, GID_F_BOMBER, GID_F_DESTRO );    // Destroyer

    // Make a list of ship types found, each ship type can be found with equal probability.
    $found_ids = array ();
    if ( count ($found) > 0)
    {
        shuffle ($found);
        $chance = floor(1 / count ($found) * 100);
        foreach ($found as $i=>$id)
        {
            $roll = mt_rand ( 0, 99 );
            if ($roll < $chance) $found_ids[] = $id;
        }
    }

    // Make a list of the fleet found.
    $found_fleet = array ( );
    foreach ( $found_ids as $i=>$id )
    {
        $max = floor ( $structure / $UnitParam[$id][0] );
        if ( $max > 0 ) $amount = mt_rand (1, $max);
        else $amount = 0;
        if ( $amount == 0 ) { $no_structure = true; break; }    // there wasn't enough structure for the rest of the fleet.
        $found_fleet[$id] = $amount;
        $structure -= $amount * $UnitParam[$id][0];
    }

    // Output a list of the found fleet and calculate its value.
    if ( count($found_fleet) > 0 )
    {
        $msg .= loca_lang ("EXP_FLEET_FOUND", $lang);
        foreach ( $found_fleet as $id=>$amount)
        {
            $res = ShipyardPrice ( $id );
            $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL]; $d = $res[GID_RC_DEUTERIUM]; $e = $res[GID_RC_ENERGY];
            $points += ($m + $k + $d) * $amount;
            $fpoints += $amount;
            $msg .= "<br>" . loca_lang ("NAME_$id", $lang) . " " . nicenum ($amount);
            $fleet[$id] += $amount;    // Add ships to the expeditionary fleet
        }
    }

    // Score points if at least one ship is found
    if ( $fpoints > 0 ) {
        AdjustStats ( $fleet_obj['owner_id'], $points, $fpoints, 0, '+' );
        RecalcRanks ();
    }

    if ( $no_structure ) {
        $n = mt_rand ( 0, count($footer) - 1 );
        $msg .= "<br><br>" . $footer[$n];
    }

    // Bring back the fleet.
    // The hold time is used as the flight time.
    DispatchFleet ($fleet, $origin, $target, (FTYP_RETURN+FTYP_EXPEDITION), $fleet_obj['deploy_time'],
        $fleet_obj[GID_RC_METAL], $fleet_obj[GID_RC_CRYSTAL], $fleet_obj[GID_RC_DEUTERIUM],
        0, $queue['end']);

    return $msg;
}

// ---

// Finding the Merchant
function Exp_TraderFound (array $exptab, array $queue, array $fleet_obj, array $fleet, array $origin, array $target, string $lang) : string
{
    global $db_prefix;
    $player_id = $fleet_obj['owner_id'];

    $msg = array (
        loca_lang ( "EXP_TRADER_1", $lang ),
        loca_lang ( "EXP_TRADER_2", $lang ),
    );

    $user = LoadUser ( $player_id );
    if ( $user['trader'] == 0 ) $offer_id = mt_rand ( 1, 3 );
    else $offer_id = $user['trader'];
    $rate_sum = $user['rate_m'] + $user['rate_k'] + $user['rate_d'];

    // Generate trade rates.
    $rand = mt_rand (0, 99);
    if ( $rand < 10 ) {
        $rate_m = 3;
        $rate_k = 2;
        $rate_d = 1;
    }
    else if ( $rand < 20 ) {

        if ( $offer_id == 1) {
            $rate_m = 3;
            $rate_k = 1.60;
            $rate_d = 0.80;
        }

        else if ( $offer_id == 2) {
            $rate_m = 2.40;
            $rate_k = 2;
            $rate_d = 0.80;
        }

        else if ( $offer_id == 3) {
            $rate_m = 2.40;
            $rate_k = 1.60;
            $rate_d = 1;
        }

    }
    else {
        if ( $offer_id == 1) {
            $rate_m = 3;
            $rate_k = mt_rand ( 140, 200) / 100;
            $rate_d = mt_rand ( 70, 100) / 100;
        }

        else if ( $offer_id == 2) {
            $rate_m = mt_rand ( 210, 300) / 100;
            $rate_k = 2;
            $rate_d = mt_rand ( 70, 100) / 100;
        }

        else if ( $offer_id == 3) {
            $rate_m = mt_rand ( 210, 300) / 100;
            $rate_k = mt_rand ( 140, 200) / 100;
            $rate_d = 1;
        }
    }

    // Activate the Merchant.
    if ( $user['trader'] == 0 || ($rate_m + $rate_k + $rate_d) > $rate_sum ) {
        $query = "UPDATE ".$db_prefix."users SET trader = $offer_id, rate_m = $rate_m, rate_k = $rate_k, rate_d = $rate_d WHERE player_id=$player_id;";
        dbquery ($query);
    }

    // Bring back the fleet.
    // The hold time is used as the flight time.
    DispatchFleet ($fleet, $origin, $target, (FTYP_RETURN+FTYP_EXPEDITION), $fleet_obj['deploy_time'],
        $fleet_obj[GID_RC_METAL], $fleet_obj[GID_RC_CRYSTAL], $fleet_obj[GID_RC_DEUTERIUM],
        0, $queue['end']);

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// -------------

function ExpeditionArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    // Start an orbit hold task.
    // Make the hold time a flight time (so that it can be used when returning the fleet)
    DispatchFleet ($fleet, $origin, $target, (FTYP_ORBITING+FTYP_EXPEDITION), $fleet_obj['deploy_time'],
        $fleet_obj[GID_RC_METAL], $fleet_obj[GID_RC_CRYSTAL], $fleet_obj[GID_RC_DEUTERIUM],
        0, $queue['end'], 0, $fleet_obj['flight_time']);
}

// Algorithmic part of the expedition
function Expedition (int $expcount, array $exptab, int $hold_time) : int
{
    $res = EXP_NOTHING;
    $chance = mt_rand ( 0, 99 );
    if ( $chance < ($exptab['chance_success'] + $hold_time) )
    {
        if ( $expcount <= $exptab['depleted_min'] ) $chance_depleted = 0;
        else if ( $expcount <= $exptab['depleted_med'] ) $chance_depleted = $exptab['chance_depleted_min'];
        else if ( $expcount <= $exptab['depleted_max'] ) $chance_depleted = $exptab['chance_depleted_med'];
        else $chance_depleted = $exptab['chance_depleted_max'];
        
        $chance = mt_rand ( 0, 99 );
        if ($chance >= $chance_depleted)    // Successful expedition.
        {
            if ( $chance >= $exptab['chance_alien'] ) $res = EXP_ALIENS;
            else if ( $chance >= $exptab['chance_pirates'] ) $res = EXP_PIRATES;
            else if ( $chance >= $exptab['chance_dm'] ) $res = EXP_DARK_MATTER;
            else if ( $chance >= $exptab['chance_lost'] ) $res = EXP_BLACK_HOLE;
            else if ( $chance >= $exptab['chance_delay'] ) $res = EXP_DELAY;
            else if ( $chance >= $exptab['chance_accel'] ) $res = EXP_ACCEL;
            else if ( $chance >= $exptab['chance_res'] ) $res = EXP_RESOURCES;
            else if ( $chance >= $exptab['chance_fleet'] ) $res = EXP_FLEET;
            else $res = EXP_TRADER;
        }
    }
    return $res;
}

function ExpeditionHold (array $queue, array $fleet_obj, array $fleet, array $origin, array $target) : void
{
    $exptab = LoadExpeditionSettings ();

    $hold_time = $fleet_obj['flight_time'] / 3600;

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "common", $origin_user['lang'] );
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "expedition", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    // Expedition Event.
    $expcount = $target[GID_RC_METAL];    // visit counter
    $exp_res = Expedition ($expcount, $exptab, $hold_time);

    switch ($exp_res)
    {
        case EXP_NOTHING:
            $text = Exp_NothingHappens ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        case EXP_ALIENS:
            $text = Exp_BattleAliens ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        case EXP_PIRATES:
            $text = Exp_BattlePirates ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        case EXP_DARK_MATTER:
            $text = Exp_DarkMatterFound ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        case EXP_BLACK_HOLE:
            $text = Exp_LostFleet ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        case EXP_DELAY:
            $text = Exp_DelayFleet ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        case EXP_ACCEL:
            $text = Exp_AccelFleet ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        case EXP_RESOURCES:
            $text = Exp_ResourcesFound ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        case EXP_FLEET:
            $text = Exp_FleetFound ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        case EXP_TRADER:
            $text = Exp_TraderFound ($exptab, $queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            break;
        default:
            $text = "";
            break;
    }

    // Updating the expedition's visit counter on the planet.
    AdjustResources ( 1, 0, 0, $target['planet_id'], '+' );

    // Captain's logbook
    if ( $fleet[GID_F_PROBE] > 0 ) $text .= "\n<br/>\n<br/>\n" . Logbook ( $expcount, $exptab, $origin_user['lang']);

    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']),
        va(loca_lang("EXP_MESSAGE_SUBJ", $origin_user['lang']), $target['g'], $target['s'], $target['p']),
        $text, MTYP_EXP, $queue['end']);
}

?>