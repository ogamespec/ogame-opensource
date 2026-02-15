<?php

/*

Global Event Queue.

The main module for managing the time line of the game.
The time line consists of time intervals between two events that affect the state of player accounts.
The events of all players are queued in a common queue. The queue is discrete - each event is synchronized on a second-by-second basis.
Checking for event completion (queue movement) is performed when players perform any actions (navigate through pages).
If two events fall on the same second, they are processed in order of priority (e.g. if Attack coincides in time with Recycle,
on the same coordinates, the Attack is processed first, and then Recycle).

Each event has a beginning (start time) and an end (end time of the event). Some events can be canceled. Canceling some events generates 
other events (e.g. canceling a fleet task generates a new fleet return task).

Main types of account events:
 - Time counters on a player's account (account deletion, etc).
 - Construction on a planet/moon
 - Research
 - Shipyard construction
 - Tasks for fleet and IPM
 - Global events for all players (re-login, cleaning of virtual debris, deletion of destroyed planets, recalculation of points 3 times a day, etc.).

Record old scores: 8:05, 16:05, 20:05 on the server

Static recalculation of player points: 0:10 by server

Virtual DF disappears on Monday at 1:10 server time if no fleets are flying to/from it and if there are 0 resources there.

Entry of the task in the database table:
task_id: unique task number (INT AUTO_INCREMENT PRIMARY KEY)
owner_id: user number to which the task belongs  (INT)
type: task type, each type has its own handler: (CHAR(20))  (see QTYP_ macro)
sub_id: additional number, different for each type of task, e.g. for construction - planet ID, for fleet task - fleet ID (INT)
obj_id: additional number, different for each type of task, e.g. for building - building ID (INT)
level: Building level / number of units ordered at the shipyard (INT)
start: task start time (INT UNSIGNED)
end: task completion time (INT UNSIGNED)
prio: event priority, used for events that end at the same time, the higher the priority, the earlier the event will be executed. (INT)
freeze: Pause task completion (INT DEFAULT 0)
frozen: Start time of task completion pause (INT UNSIGNED DEFAULT 0)

How the queue is updated:
After the next click by one of the users, each queue task is checked for completion. If the task is completed, its handler is called and the task is removed from the queue.

Build queue entry:
id: Ordinal number, starts with 1 (INT AUTO_INCREMENT PRIMARY KEY)
owner_id: user ID (INT)
planet_id: planet ID (INT)
list_id: ordinal number within the queue (INT)
tech_id: building ID (INT)
level: target level (INT)
destroy: 1 - demolish, 0 - build (INT)
start: construction start-up time (INT UNSIGNED)
end: construction completion time (INT UNSIGNED)

*/

const QUEUE_BATCH = 16;         // The event queue is not executed in its entirety, but in small portions specified in this constant (so as not to overload the server)

// Add a task to the queue. Returns the ID of the added task.
function AddQueue (int $owner_id, string $type, int $sub_id, int $obj_id, int $level, int $now, int $seconds, int $prio=QUEUE_PRIO_LOWEST) : int
{
    $queue = array ( 'owner_id' => $owner_id, 'type' => $type, 'sub_id' => $sub_id, 'obj_id' => $obj_id, 'level' => $level, 'start' => $now, 'end' => $now+$seconds, 'prio' => $prio );
    $id = AddDBRow ( $queue, "queue" );
    return $id;
}

// Load task.
function LoadQueue (int $task_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE task_id = $task_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Delete a task from the queue.
function RemoveQueue (int $task_id) : void
{
    global $db_prefix;
    if ($task_id) {
        $query = "DELETE FROM ".$db_prefix."queue WHERE task_id = $task_id";
        dbquery ($query);
    }
}

// Extend the task for the number of seconds specified
function ProlongQueue (int $task_id, int $seconds) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."queue SET end = end + $seconds WHERE task_id = $task_id";
    dbquery ($query);
}

// Check queue tasks for completion before $until time.
function UpdateQueue (int $until) : void
{
    global $db_prefix;
    global $GlobalUni;

    if ( $GlobalUni['freeze'] ) return;

    LockTables ();

    $query = "SELECT * FROM ".$db_prefix."queue WHERE end <= $until AND freeze=0 ORDER BY end ASC, prio DESC LIMIT " . QUEUE_BATCH;
    $result = dbquery ($query);

    $rows = dbrows ($result);
    while ($rows--) {
        $queue = dbarray ($result);

        switch ($queue['type']) {
            case QTYP_BUILD: Queue_Build_End ($queue); break;
            case QTYP_DEMOLISH: Queue_Build_End ($queue); break;
            case QTYP_RESEARCH: Queue_Research_End ($queue); break;
            case QTYP_SHIPYARD: Queue_Shipyard_End ($queue); break;
            case QTYP_FLEET: Queue_Fleet_End ($queue); break;
            case QTYP_UNLOAD_ALL: Queue_Relogin_End ($queue); break;
            case QTYP_CLEAN_DEBRIS: Queue_CleanDebris_End ($queue); break;
            case QTYP_CLEAN_PLANETS: Queue_CleanPlanets_End ($queue); break;
            case QTYP_CLEAN_PLAYERS: Queue_CleanPlayers_End ($queue); break;
            case QTYP_UPDATE_STATS: Queue_UpdateStats_End ($queue); break;
            case QTYP_RECALC_POINTS: Queue_RecalcPoints_End ($queue); break;
            case QTYP_RECALC_ALLY_POINTS: Queue_RecalcAllyPoints_End ($queue); break;
            case QTYP_ALLOW_NAME: Queue_AllowName_End ($queue); break;
            case QTYP_CHANGE_EMAIL: Queue_ChangeEmail_End ($queue); break;
            case QTYP_UNBAN: Queue_UnbanPlayer_End ($queue); break;
            case QTYP_ALLOW_ATTACKS: Queue_AllowAttacks_End ($queue); break;
            case QTYP_DEBUG: Queue_Debug_End ($queue); break;
            case QTYP_AI: Queue_Bot_End ($queue); break;
            case QTYP_COUPON: break;

            default:
                $res = ModsExecRef ('update_queue', $queue);
                if (!$res) {
                    RemoveQueue ( $queue['task_id'] );
                    Debug ( loca_lang("DEBUG_QUEUE_UNKNOWN", $GlobalUni['lang']) . $queue['type']);
                }
                break;
        }
    }

    UnlockTables ();

    // To send and add coupons we don't need to lock the database, otherwise a strange error "Table not locked by LOCK TABLES" occurs.
    
    $query = "SELECT * FROM ".$db_prefix."queue WHERE end <= $until AND type = '".QTYP_COUPON."' ORDER BY end ASC, prio DESC";
    $result = dbquery ($query);
    while ( $queue = dbarray ($result) ) Queue_Coupon_End ($queue);
}

// Cancel all construction tasks on a planet/moon. Called before deleting it.
function FlushQueue (int $planet_id) : void
{
    global $db_prefix;
    // Remove the queue at the shipyard
    $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_SHIPYARD."' AND sub_id = " . $planet_id;
    dbquery ( $query );
    // Delete the queue of buildings
    $result = GetBuildQueue ($planet_id);
    while ( $row = dbarray ($result) ) {
        $query = "DELETE FROM ".$db_prefix."queue WHERE (type = '".QTYP_BUILD."' OR type = '".QTYP_DEMOLISH."') AND sub_id = " . $row['id'];
        dbquery ( $query );
    }
    $query = "DELETE FROM ".$db_prefix."buildqueue WHERE planet_id = " . $planet_id;
    dbquery ( $query );
}

function FreezeQueue (int $task_id, bool $freeze, int $when=0) : void
{
    global $db_prefix;
    $queue = LoadQueue ($task_id);
    if ($queue == null) return;
    if ($when == 0) $when = time();
    if ($freeze) {
        if ($queue['freeze'] == 0) {
            // When freezing, record the freezing time and set a flag
            $query = "UPDATE ".$db_prefix."queue SET freeze=1, frozen=$when WHERE task_id = $task_id";
            dbquery ( $query );
        }
    }
    else {
        if ($queue['freeze']) {
            // When unfreezing, extend the end time of the event by the amount it was frozen and reset the flag.
            // If the unfreeze time somehow happened to be earlier than the time when the event was frozen (bug?) - do not extend the end time
            $frozen_seconds = $when - $queue['frozen'];
            if ($frozen_seconds > 0) $end = $queue['end'] + $frozen_seconds;
            else $end = $queue['end'];
            $query = "UPDATE ".$db_prefix."queue SET freeze=0, frozen=0, end=$end WHERE task_id = $task_id";
            dbquery ( $query );
        }
    }
}

// ===============================================================================================================
// Buildings

// Get a construction queue for the planet.
function GetBuildQueue ( int $planet_id ) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buildqueue WHERE planet_id = $planet_id ORDER BY list_id ASC;";
    return dbquery ($query);
}

// Verify all conditions of build/demolition possibility
// The $enqueue parameter is used to check if the build can be added to the queue.
function CanBuild (array $user, array $planet, int $id, int $lvl, bool $destroy, bool $enqueue=false) : string
{
    global $GlobalUni;
    global $buildmap;

    // Cost of building
    $cost = TechPrice ( $id, $lvl );

    $result = GetResearchQueue ( $user['player_id'] );
    $resqueue = dbarray ($result);
    $reslab_operating = ($resqueue != null);
    $result = GetShipyardQueue ( $planet['planet_id'] );
    $shipqueue = dbarray ($result);
    $shipyard_operating = ($shipqueue != null);

    loca_add ("build", $user['lang']);

    if ( $GlobalUni['freeze'] ) return loca_lang("BUILD_ERROR_UNI_FREEZE", $user['lang']);

    // Not a building
    if ( !IsBuilding($id) ) return loca_lang("BUILD_ERROR_INVALID_ID", $user['lang']);

    // You can't build in vacation mode
    else if ( $user['vacation'] ) return loca_lang("BUILD_ERROR_VACATION_MODE", $user['lang']);

    // You can't build on a foreign planet
    else if ( $planet['owner_id'] != $user['player_id'] ) return loca_lang("BUILD_ERROR_INVALID_PLANET", $user['lang']);

    // Lunar buildings can't be built on a planet, whereas planetary buildings can't be built on a moon
    else if ( $planet['type'] != PTYP_MOON && ($id == GID_B_LUNAR_BASE || $id == GID_B_PHALANX || $id == GID_B_JUMP_GATE) ) return loca_lang("BUILD_ERROR_INVALID_PTYPE", $user['lang']);
    else if ( $planet['type'] == PTYP_MOON && ( 
        $id == GID_B_METAL_MINE || 
        $id == GID_B_CRYS_MINE || 
        $id == GID_B_DEUT_SYNTH || 
        $id == GID_B_SOLAR || 
        $id == GID_B_FUSION || 
        $id == GID_B_NANITES || 
        $id == GID_B_METAL_STOR || 
        $id == GID_B_CRYS_STOR || 
        $id == GID_B_DEUT_STOR || 
        $id == GID_B_RES_LAB || 
        $id == GID_B_TERRAFORMER || 
        $id == GID_B_MISS_SILO ) ) return loca_lang("BUILD_ERROR_INVALID_PTYPE", $user['lang']);

    // Check the number of fields
    else if ( $planet['fields'] >= $planet['maxfields'] && !$destroy ) return loca_lang("BUILD_ERROR_NO_SPACE", $user['lang']);

    // Research or construction at the shipyard is underway
    else if ( $id == GID_B_RES_LAB && $reslab_operating ) return loca_lang("BUILD_ERROR_RESEARCH_ACTIVE", $user['lang']);
    else if ( ($id == GID_B_NANITES || $id == GID_B_SHIPYARD) && $shipyard_operating ) return loca_lang("BUILD_ERROR_SHIPYARD_ACTIVE", $user['lang']);

    // Check the available amount of resources on the planet
    else if ( !IsEnoughResources ( $user, $planet, $cost ) && !$enqueue ) return loca_lang("BUILD_ERROR_NO_RES", $user['lang']);

    // Check available technologies.
    else if ( !TechMeetRequirement ( $user, $planet, $id ) ) return loca_lang("BUILD_ERROR_REQUIREMENTS", $user['lang']);

    if ( $destroy )
    {
        if ( $id == GID_B_TERRAFORMER || $id == GID_B_LUNAR_BASE ) return loca_lang("BUILD_ERROR_CANT_DEMOLISH", $user['lang']);
        else if ( $planet[$id] <= 0 ) return loca_lang("BUILD_ERROR_NO_SUCH_BUILDING", $user['lang']);
    }

    if ($planet[$id] >= MAX_BUILDINGS_LEVEL) return loca_lang("BUILD_ERROR_MAX_LEVEL", $user['lang']);

    $info = array ();
    $info['id'] = $id;
    $info['level'] = $lvl;
    $info['user'] = $user;
    $info['planet'] = $planet;
    $info['destroy'] = $destroy;
    $info['enqueue'] = $enqueue;
    if (ModsExecRef ('can_build', $info)) {
        return $info['result']; 
    }

    return "";
}

// Start the next construction
function PropagateBuildQueue (int $planet_id, int $from) : void
{
    global $db_prefix, $GlobalUni;

    $speed = $GlobalUni['speed'];

    $planet = LoadPlanetById ( $planet_id );
    $user = LoadUser ( $planet['owner_id'] );

    $result = GetBuildQueue ( $planet_id );
    $cnt = dbrows ( $result );
    if ($cnt > 0) {
        while ($row = dbarray ($result) )
        {
            $id = $row['tech_id'];
            $lvl = $row['level'];
            $destroy = $row['destroy'];

            $text = CanBuild ($user, $planet, $id, $lvl, $destroy);
            if ( $text === '' ) {
                // Write off resources
                $cost = TechPrice ( $id, $lvl );
                AdjustResources ( $cost, $planet_id, '-' );

                if ( $destroy ) $BuildEvent = QTYP_DEMOLISH;
                else $BuildEvent = QTYP_BUILD;

                $duration = floor (TechDuration ( $id, $lvl, PROD_BUILDING_DURATION_FACTOR, $planet[GID_B_ROBOTS], $planet[GID_B_NANITES], $speed ));
                AddQueue ( $user['player_id'], $BuildEvent, $row['id'], $id, $lvl, $from, (int)$duration, QUEUE_PRIO_BUILD );

                // Update the start and end time of construction
                $query = "UPDATE ".$db_prefix."buildqueue SET start = $from, end = ".($from+$duration)." WHERE id = " . $row['id'];
                dbquery ($query);
                break;
            }
            else {
                loca_add ("build", $user['lang']);
                loca_add ("technames", $user['lang']);

                if ( $destroy ) $pre = loca_lang("BUILD_MSG_DEMOLISH", $user['lang']);
                else $pre = loca_lang("BUILD_MSG_BUILD", $user['lang']);
                $pre = va ( loca_lang("BUILD_MSG_BODY", $user['lang']), $pre, loca_lang ("NAME_$id", $user['lang']), $lvl, $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" >[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>" );
                SendMessage ( $user['player_id'], 
                    loca_lang("BUILD_MSG_FROM", $user['lang']), 
                    loca_lang("BUILD_MSG_SUBJ", $user['lang']), 
                    $pre . "<br><br>" . $text, MTYP_MISC, $from );

                // remove a building that cannot be built from the queue
                dbquery ( "DELETE FROM ".$db_prefix."buildqueue WHERE id = " . $row['id'] );

                // Adjust the level of the following constructions.
                $query = "UPDATE ".$db_prefix."buildqueue SET level = level - 1 WHERE tech_id = ".$row['tech_id']." AND planet_id = $planet_id AND list_id > " . $row['list_id'];
                dbquery ($query);

                // Reloading the queue from the database.
                $result = GetBuildQueue ( $planet_id );
            }
        }
    }    // cnt

}

// Add a new construction/demolition to the queue. $user - is the user who starts the construction process.
function BuildEnque ( array $user, int $planet_id, int $id, int $destroy, int $now=0 ) : string
{
    global $GlobalUni;

    $speed = $GlobalUni['speed'];
    if ( $GlobalUni['freeze'] ) return "";

    $planet = LoadPlanetById ( $planet_id );

    $prem = PremiumStatus ($user);
    if ($prem['commander']) $maxcnt = 5;
    else $maxcnt = 1;

    if ($now == 0) $now = time ();

    // Write down the user's action, even if the user does something wrong
    if ($destroy) UserLog ( $planet['owner_id'], "BUILD", va(loca_lang("DEBUG_LOG_DEMOLISH", $GlobalUni['lang']), loca("NAME_$id"), $planet[$id]-1, $planet_id)  );
    else UserLog ( $planet['owner_id'], "BUILD", va(loca_lang("DEBUG_LOG_BUILD", $GlobalUni['lang']), loca("NAME_$id"), $planet[$id]+1, $planet_id)  );

    $result = GetBuildQueue ( $planet_id );
    $cnt = dbrows ( $result );
    if ( $cnt >= $maxcnt ) return "";    // The queue of buildings is full

    // Load queue. Sorted by order list_id
    $queue = array ();
    for ($i=0; $i<$cnt; $i++)
    {
        $queue[$i] = dbarray ($result);
    }

    // You can't add multiple builds to the queue in the same second.
    for ($i=0; $i<$cnt; $i++)
    {
        if ( $queue[$i]['start'] == $now ) return "";
    }    

    // Define the level to be added and the order of construction (list_id).
    $nowlevel = $planet[$id];
    $list_id = 0;
    for ($i=0; $i<$cnt; $i++)
    {
        if ( $queue[$i]['tech_id'] == $id ) $nowlevel = $queue[$i]['level'];
        if ( $queue[$i]['list_id'] > $list_id ) $list_id = $queue[$i]['list_id'];
    }
    $list_id++;

    if ($destroy) $lvl = $nowlevel - 1;
    else $lvl = $nowlevel + 1;
    if ($lvl < 0) return "";     // Unable to build/demolish a negative level

    $text = CanBuild ($user, $planet, $id, $lvl, $destroy, $list_id != 1);

    if ( $text === '' ) {

        // Write off resources for the very first construction
        if ( $list_id == 1) {
            $cost = TechPrice ( $id, $lvl );
            AdjustResources ( $cost, $planet_id, '-' );
        }

        if ( $destroy ) $BuildEvent = QTYP_DEMOLISH;
        else $BuildEvent = QTYP_BUILD;

        $duration = floor (TechDuration ( $id, $lvl, PROD_BUILDING_DURATION_FACTOR, $planet[GID_B_ROBOTS], $planet[GID_B_NANITES], $speed ));
        $row = array ( 'owner_id' => $user['player_id'], 'planet_id' => $planet_id, 'list_id' => $list_id, 'tech_id' => $id, 'level' => $lvl, 'destroy' => $destroy, 'start' => $now, 'end' => $now+$duration );
        $sub_id = AddDBRow ( $row, "buildqueue" );
        if ($list_id == 1) AddQueue ( $user['player_id'], $BuildEvent, $sub_id, $id, $lvl, $now, $duration, QUEUE_PRIO_BUILD );
    }

    return $text;
}

// Cancel construction/demolition; $user - is the user who removes build slot from the queue.
function BuildDeque ( array $user, int $planet_id, int $listid ) : string
{
    global $db_prefix, $GlobalUni;

    if ( $GlobalUni['freeze'] ) return "";

    $query = "SELECT * FROM ".$db_prefix."buildqueue WHERE planet_id = $planet_id AND list_id = $listid LIMIT 1;";
    $result = dbquery ($query);
    if ( dbrows ($result) ) {
        $row = dbarray ($result);

        $id = $row['tech_id'];
        $lvl = $row['level'];
        $planet_id = $row['planet_id'];

        // Do we cancel the current one or the next one?
        $query = "SELECT * FROM ".$db_prefix."queue WHERE (type = '".QTYP_BUILD."' OR type = '".QTYP_DEMOLISH."') AND sub_id = " . $row['id'] . " LIMIT 1";
        $result = dbquery ($query);
        if ( dbrows ($result) ) {       // Cancel the current one
            $queue = dbarray ($result);
            $queue_id = $queue['task_id'];

            // Return resources
            $cost = TechPrice ( $id, $lvl );
            AdjustResources ( $cost, $planet_id, '+' );           
        }
        else $queue_id = 0;

        // Adjusting levels on the queue
        $query = "UPDATE ".$db_prefix."buildqueue SET level = level - 1 WHERE tech_id = ".$row['tech_id']." AND planet_id = $planet_id AND list_id > " . $row['list_id'];
        dbquery ($query);

        $planet = LoadPlanetById ( $planet_id );
        UserLog ( $planet['owner_id'], "BUILD", va(loca_lang("DEBUG_LOG_BUILD_CANCEL", $GlobalUni['lang']), loca("NAME_".$id), $lvl, $listid, $planet_id)  );

        // Remove event handler and construction from the queue
        RemoveQueue ( $queue_id );
        dbquery ( "DELETE FROM ".$db_prefix."buildqueue WHERE id = " . $row['id'] );

        // Start the next construction
        if ( $queue_id ) PropagateBuildQueue ($planet_id, time());
    }

    return "";
}

// Completion of construction/demolition
function Queue_Build_End (array $queue) : void
{
    global $db_prefix, $GlobalUser;

    $id = $queue['obj_id'];
    $lvl = $queue['level'];
    $query = "SELECT * FROM ".$db_prefix."buildqueue WHERE id = " . $queue['sub_id'] . " LIMIT 1";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) {
        //Error ( "Нет постройки в очереди построек!");
        RemoveQueue ( $queue['task_id'] );
        return;
    }
    $bqueue = dbarray ($result);
    $planet_id = $bqueue['planet_id'];

    // Calculate the planet's production since the last update.
    $planet = GetUpdatePlanet ( $planet_id, $queue['end'] );
    $player_id = $planet['owner_id'];

    // Foolproofing
    if ( ($queue['type'] === QTYP_BUILD && $planet[$id] >= $lvl) ||
         ($queue['type'] === QTYP_DEMOLISH && $planet[$id] <= $lvl) )
    {
        RemoveQueue ( $queue['task_id'] );
        dbquery ( "DELETE FROM ".$db_prefix."buildqueue WHERE id = " . $queue['sub_id'] );
        return;
    }

    // Number of fields on the planet.
    if ($queue['type'] === QTYP_BUILD )
    {
        $fields = "fields = fields + 1";
        // Special handling for building a Terraformer or Moonbase -- add the maximum number of fields.
        if ( $id == GID_B_TERRAFORMER ) $fields .= ", maxfields = maxfields + 5";
        if ( $id == GID_B_LUNAR_BASE ) $fields .= ", maxfields = maxfields + 3";
    }
    else $fields = "fields = fields - 1";

    // Update the level of construction and the number of fields in the database.
    $query = "UPDATE ".$db_prefix."planets SET `".($id)."` = $lvl, $fields WHERE planet_id = $planet_id";
    dbquery ($query);

    RemoveQueue ( $queue['task_id'] );
    dbquery ( "DELETE FROM ".$db_prefix."buildqueue WHERE id = " . $queue['sub_id'] );

    // Add points. Recalculate places only for large constructions.
    if ( $queue['type'] === "Build" ) {
        $res = TechPrice ( $id, $lvl );
        $points = TechPriceInPoints($res);
        AdjustStats ( $queue['owner_id'], $points, 0, 0, '+');
    }
    else {
        $res = TechPrice ( $id, $lvl+1 );
        $points = TechPriceInPoints($res);
        AdjustStats ( $queue['owner_id'], $points, 0, 0, '-');
    }
    if ( $lvl > 10 ) RecalcRanks ();

    if ( $GlobalUser['player_id'] == $player_id) {
        InvalidateUserCache ();
        $GlobalUser = LoadUser ( $player_id );    // update the current user's data
    }

    ModsExecIntRef ('build_end', $planet_id, $queue);

    // Start the next construction
    PropagateBuildQueue ($planet_id, $queue['end']);
}

// ===============================================================================================================
// Shipyard

// Get a queue of tasks at the shipyard.
function GetShipyardQueue (int $planet_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_SHIPYARD."' AND sub_id = $planet_id ORDER BY start ASC";
    return dbquery ($query);
}

// Get the end time of the last task at the shipyard, used to get the start time of a new task.
function ShipyardLatestTime (int $planet_id, int $now) : int
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_SHIPYARD."' AND sub_id = $planet_id ORDER BY end DESC";
    $result = dbquery ($query);
    if (dbrows($result) > 0) {
        $queue = dbarray ($result);
        return $queue['end'] + ($queue['end'] - $queue['start']) * ($queue['level'] - 1);
    }
    else {
        if ($now == 0) $now = time ();
        return $now;
    }
}

// Add fleet/defense at the shipyard ($gid - unit type, $value - quantity)
function AddShipyard (int $player_id, int $planet_id, int $gid, int $value, int $now=0 ) : bool
{
    global $db_prefix, $GlobalUni;
    global $fleetmap;
    global $defmap;
    global $resourcemap;

    if ( in_array ( $gid, $defmap ) ) UserLog ( $player_id, "DEFENSE", va(loca_lang("DEBUG_LOG_DEFENSE", $GlobalUni['lang']), loca("NAME_$gid"), $value, $planet_id)  );
    else UserLog ( $player_id, "SHIPYARD", va(loca_lang("DEBUG_LOG_SHIPYARD", $GlobalUni['lang']), loca("NAME_$gid"), $value, $planet_id)  );

    $techmap = array_merge ($fleetmap, $defmap);
    if ( ! in_array ( $gid, $techmap ) ) return false;

    $uni = $GlobalUni;
    if ( $uni['freeze'] ) return false;

    // Shield domes can be built up to a maximum of 1 unit.
    if ( ($gid == GID_D_SDOME || $gid == GID_D_LDOME) && $value > 1 ) $value = 1;

    $planet = LoadPlanetById ( $planet_id );

    // If the planet already has a shield dome, we don't build it.
    if ( ($gid == GID_D_SDOME || $gid == GID_D_LDOME) && $planet[$gid] > 0 ) return false;

    // If a dome of the same type is already being built in the queue, then do not add another dome to the queue.
    // Limit the number of missiles ordered to those already under construction
    $result = GetShipyardQueue ($planet_id);
    $tasknum = dbrows ($result);
    $rak_space = $planet[GID_B_MISS_SILO] * 10 - ($planet[GID_D_ABM] + 2 * $planet[GID_D_IPM]);
    while ($tasknum--)
    {
        $queue = dbarray ( $result );
        if ( $queue['obj_id'] == GID_D_SDOME || $queue['obj_id'] == GID_D_LDOME )
        {
            if ( $queue['obj_id'] == $gid ) return false;    // is in line to build a dome of the same type.
        }
        if ( $queue['obj_id'] == GID_D_ABM || $queue['obj_id'] == GID_D_IPM )
        {
            if ( $queue['obj_id'] == GID_D_ABM ) $rak_space -= $queue['level'];
            else $rak_space -= 2 * $queue['level'];
        }
    }

    if ( $gid == GID_D_ABM ) $value = min ( $rak_space, $value );
    if ( $gid == GID_D_IPM ) $value = min ( floor ($rak_space / 2), $value );
    if ( $value <= 0 ) return false;

    $user = LoadUser ( $player_id );
    if ($user == null) return false;

    $cost = TechPrice ( $gid, 1 );
    foreach ($resourcemap as $i=>$rc) {
        if (isset($cost[$rc])) {
            $cost[$rc] *= $value;
        }
    }

    if ( IsEnoughResources ( $user, $planet, $cost ) && TechMeetRequirement ($user, $planet, $gid) ) {
        $speed = $uni['speed'];
        $now = ShipyardLatestTime ($planet_id, $now);
        $shipyard = $planet[GID_B_SHIPYARD];
        $nanits = $planet[GID_B_NANITES];
        $seconds = TechDuration ( $gid, 1, PROD_SHIPYARD_DURATION_FACTOR, $shipyard, $nanits, $speed );

        // Write off resources.
        AdjustResources ( $cost, $planet_id, '-' );

        AddQueue ($player_id, QTYP_SHIPYARD, $planet_id, $gid, $value, $now, $seconds);
        return true;
    }
    else {
        return false;
    }
}

// Finish building at the shipyard.
function Queue_Shipyard_End (array $queue, int $when=0) : void
{
    global $db_prefix, $GlobalUser;

    if ($when == 0) $now = time ();
    else $now = $when;
    $gid = $queue['obj_id'];
    $planet_id = $queue['sub_id'];
    $planet = LoadPlanetById ($planet_id);
    $player_id = $planet['owner_id'];

    // Old values
    $s = $queue['start'];
    $e = $queue['end'];
    $n = $queue['level'];
    $one = $e - $s;

    // New values
    $done =  min ($n, floor ( ($now - $s) / $one ));
    $news = $s + $done * $one;
    $newe = $e + $done * $one;

    // Add a fleet to the planet
    $query = "UPDATE ".$db_prefix."planets SET `$gid` = `$gid` + $done WHERE planet_id = $planet_id";
    dbquery ($query);

    // Add points.
    $res = TechPrice ( $gid, 1 );
    $points = TechPriceInPoints($res) * $done;
    if (IsFleet($gid)) $fpoints = $done;
    else $fpoints = 0;
    AdjustStats ( $queue['owner_id'], $points, $fpoints, 0, '+');

    // Update the task or delete it if everything is built.
    if ( $done < $n )
    {
        $query = "UPDATE ".$db_prefix."queue SET start = $news, end = $newe, level = level - $done WHERE task_id = ".$queue['task_id'];
        dbquery ($query);
        //Debug ( "На верфи [".$planet['g'].":".$planet['s'].":".$planet['p']."] ".$planet['name']." построено ".loca("NAME_$gid")." ($done), осталось достроить (".($n-$done).")" );
        if ( $one > 60 ) RecalcRanks ();
    }
    else {
        //Debug ( "На верфи [".$planet['g'].":".$planet['s'].":".$planet['p']."] ".$planet['name']." завершена постройка ".loca("NAME_$gid")." ($done)" );
        RemoveQueue ( $queue['task_id'] );
        RecalcRanks ();
    }

    if ( $GlobalUser['player_id'] == $player_id) {
        InvalidateUserCache ();
        $GlobalUser = LoadUser ( $player_id );    // update the current user's data
    }
}

// ===============================================================================================================
// Research

// Check all conditions for the possibility of starting the research
function CanResearch (array $user, array $planet, int $id, int $lvl) : string
{
    global $db_prefix, $GlobalUni;
    global $resmap;

    loca_add ("build", $user['lang']);

    if ( $GlobalUni['freeze'] ) return loca_lang("BUILD_ERROR_UNI_FREEZE", $user['lang']);

    // Is the research already in progress?
    $result = GetResearchQueue ( $user['player_id'] );
    $resq = dbarray ($result);
    if ($resq) return loca_lang("BUILD_ERROR_RESEARCH_ALREADY", $user['lang']);

    // Is the research lab being upgraded on any planet?
    $query = "SELECT * FROM ".$db_prefix."queue WHERE obj_id = ".GID_B_RES_LAB." AND (type = '".QTYP_BUILD."' OR type = '".QTYP_DEMOLISH."') AND owner_id = " . $user['player_id'];
    $result = dbquery ( $query );
    $busy = ( dbrows ($result) > 0 );
    if ( $busy ) return loca_lang("BUILD_ERROR_RESEARCH_LAB_BUILDING", $user['lang']);

    $cost = TechPrice ( $id, $lvl );

    // Not research
    if ( ! in_array ( $id, $resmap ) ) return loca_lang("BUILD_ERROR_INVALID_ID", $user['lang']);

    // You can't build in vacation mode
    else if ( $user['vacation'] ) return loca_lang("BUILD_ERROR_RESEARCH_VACATION", $user['lang']);

    // You can't research on foreign planet
    else if ( $planet['owner_id'] != $user['player_id'] ) return loca_lang("BUILD_ERROR_INVALID_PLANET", $user['lang']);

    else if ( !IsEnoughResources ( $user, $planet, $cost ) ) return loca_lang("BUILD_ERROR_NO_RES", $user['lang']);

    else if ( !TechMeetRequirement ( $user, $planet, $id ) ) return loca_lang("BUILD_ERROR_REQUIREMENTS", $user['lang']);

    if ($user[$id] >= MAX_RESEARCH_LEVEL) return loca_lang("BUILD_ERROR_MAX_LEVEL", $user['lang']);

    $info = array ();
    $info['id'] = $id;
    $info['level'] = $lvl;
    $info['user'] = $user;
    $info['planet'] = $planet;
    if (ModsExecRef ('can_research', $info)) {
        return $info['result']; 
    }

    return "";
}

// Start research on the planet (includes all checks).
function StartResearch (int $player_id, int $planet_id, int $id, int $now) : string
{
    global $db_prefix, $GlobalUni;
    $uni = $GlobalUni;

    $planet = LoadPlanetById ( $planet_id );
    if ($planet == null) return "";

    UserLog ( $player_id, "RESEARCH", va(loca_lang("DEBUG_LOG_RESEARCH", $GlobalUni['lang']), loca("NAME_$id"), $planet_id)  );

    // Get a level of research.
    $user = LoadUser ( $player_id );
    if ($user == null) return "";
    $level = $user[$id] + 1;

    $prem = PremiumStatus ($user);
    if ( $prem['technocrat'] ) $r_factor = 1.1;
    else $r_factor = 1.0;

    // Check conditions.
    $text = CanResearch ( $user, $planet, $id, $level );

    if ( $text === "" ) {
        $speed = $uni['speed'];
        if ($now == 0) $now = time ();
        $reslab = ResearchNetwork ( $planet['planet_id'], $id );
        $seconds = TechDuration ( $id, $level, PROD_RESEARCH_DURATION_FACTOR, $reslab, 0, $speed * $r_factor);

        // Write off resources
        $cost = TechPrice ( $id, $level );
        AdjustResources ( $cost, $planet_id, '-' );

        AddQueue ($player_id, QTYP_RESEARCH, $planet_id, $id, $level, $now, $seconds);
    }

    return $text;
}

// Cancel the research.
function StopResearch (int $player_id) : void
{
    global $db_prefix, $GlobalUni;

    $uni = $GlobalUni;
    if ( $uni['freeze'] ) return;

    // Get a research queue.
    $result = GetResearchQueue ( $player_id);
    if ( $result == null ) return;        // The research is not in progress.
    $resq = dbarray ($result);

    $id = $resq['obj_id'];
    $planet_id = $resq['sub_id'];
    $level = $resq['level'];

    // Get the cost of the research
    $user = LoadUser ( $player_id );
    $planet = LoadPlanetById ( $planet_id );
    if ($planet['owner_id'] != $player_id )
    {
        Error ( va(loca_lang("DEBUG_QUEUE_CANCEL_RESEARCH_FOREIGN", $GlobalUni['lang']), 
            loca("NAME_$id"), 
            $user['oname'], 
            "[".$planet['g'].":".$planet['s'].":".$planet['p']."] " . $planet['name'] )
        );
        return;
    }
    $cost = TechPrice ( $id, $level );

    // Return resources
    AdjustResources ( $cost, $planet_id, '+' );

    RemoveQueue ( $resq['task_id'] );

    UserLog ( $player_id, "RESEARCH", va(loca_lang("DEBUG_LOG_RESEARCH_CANCEL", $GlobalUni['lang']), loca("NAME_$id"), $planet_id) );
}

// Get the current research for the account.
function GetResearchQueue (int $player_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_RESEARCH."' AND owner_id = $player_id ORDER BY start ASC";
    return dbquery ($query);
}

// Complete the research.
function Queue_Research_End (array $queue) : void
{
    global $db_prefix, $GlobalUser, $GlobalUni;

    $id = $queue['obj_id'];
    $lvl = $queue['level'];
    $planet_id = $queue['sub_id'];
    $player_id = $queue['owner_id'];

    // Calculate the planet's production since the last update.
    $planet = GetUpdatePlanet ( $planet_id, $queue['end'] );

    // Update the research level in the database.
    $query = "UPDATE ".$db_prefix."users SET `".$id."` = $lvl WHERE player_id = $player_id";
    dbquery ($query);

    RemoveQueue ( $queue['task_id'] );

    // Add points.
    $res = TechPrice ( $id, $lvl );
    $points = TechPriceInPoints($res);
    AdjustStats ( $queue['owner_id'], $points, 0, 1, '+');
    RecalcRanks ();

    Debug ( va(loca_lang("DEBUG_QUEUE_RESEARCH_COMPLETE", $GlobalUni['lang']), loca("NAME_$id"), $lvl, $player_id)  );

    if ( $GlobalUser['player_id'] == $player_id) {
        InvalidateUserCache ();
        $GlobalUser = LoadUser ( $player_id );    // update the current user's data
    }

    ModsExecRef ('research_end', $queue);
}

// ===============================================================================================================
// Player

// Add the task of recalculating a player's score if it doesn't already exist.
// Called when any player logs in.
function AddRecalcPointsEvent (int $player_id) : void
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_RECALC_POINTS."' AND owner_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(0, 10, 0, date("m"), date("d")+1, date("y")) - $now;
        AddQueue ($player_id, QTYP_RECALC_POINTS, 0, 0, 0, $now, $when, QUEUE_PRIO_RECALC_POINTS);
    }
}

// Recalculate a player's points scored and his place in the statistics.
function Queue_RecalcPoints_End (array $queue) : void
{
    RecalcStats ( $queue['owner_id'] );
    RecalcRanks ();
    RemoveQueue ( $queue['task_id'] );
}

// It's okay to go vaction mode or not.
function CanEnableVacation (int $player_id) : bool
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE (type = '".QTYP_BUILD."' OR type = '".QTYP_DEMOLISH."' OR type = '".QTYP_RESEARCH."' OR type = '".QTYP_SHIPYARD."' OR type = '".QTYP_FLEET."') AND owner_id = $player_id";
    $result = dbquery ( $query );
    if ( dbrows ($result) > 0 ) return false;
    else return true;
}

// Add a name change permission task.
function AddAllowNameEvent (int $player_id) : void
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_ALLOW_NAME."' AND owner_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = $now + 7 * 24 * 60 * 60;
        $id = AddQueue ($player_id, QTYP_ALLOW_NAME, 0, 0, 0, $now, $when, QUEUE_PRIO_LOWEST);
        $query = "UPDATE ".$db_prefix."users SET name_changed = 1, name_until = $when WHERE player_id = $player_id";
        dbquery ($query);
    }
}

// Can the player's name be changed.
function CanChangeName (int $player_id) : bool
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_ALLOW_NAME."' AND owner_id = $player_id";
    $result = dbquery ( $query );
    if ( dbrows ($result) > 0 ) return false;
    else return true;
}

// Allow name change.
function Queue_AllowName_End (array $queue) : void
{
    global $db_prefix;
    $player_id = $queue['owner_id'];
    $query = "UPDATE ".$db_prefix."users SET name_changed = 0 WHERE player_id = $player_id";
    dbquery ($query);
    RemoveQueue ( $queue['task_id'] );
}

// Unban a player
function Queue_UnbanPlayer_End (array $queue) : void
{
    global $db_prefix;
    $player_id = $queue['owner_id'];
    $query = "UPDATE ".$db_prefix."users SET banned = 0, banned_until = 0 WHERE player_id = $player_id";
    dbquery ($query);
    RemoveQueue ( $queue['task_id'] );
}

// Allow attacks
function Queue_AllowAttacks_End (array $queue) : void
{
    global $db_prefix;
    $player_id = $queue['owner_id'];
    $query = "UPDATE ".$db_prefix."users SET noattack = 0, noattack_until = 0 WHERE player_id = $player_id";
    dbquery ($query);
    RemoveQueue ( $queue['task_id'] );
}

// Add a permanent mail address update task
function AddChangeEmailEvent (int $player_id) : int
{
    global $db_prefix;

    $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_CHANGE_EMAIL."' AND owner_id = $player_id";
    dbquery ($query);

    $now = time ();
    $when = $now + 7 * 24 * 60 * 60;
    $id = AddQueue ($player_id, QTYP_CHANGE_EMAIL, 0, 0, 0, $now, $when, QUEUE_PRIO_LOWEST);
}

// Update permanent mail address
function Queue_ChangeEmail_End (array $queue) : void
{
    global $db_prefix;
    $player_id = $queue['owner_id'];
    $query = "UPDATE ".$db_prefix."users SET pemail = email WHERE player_id = $player_id;";
    dbquery ($query);
    RemoveQueue ( $queue['task_id'] );
}

// ===============================================================================================================
// Universe

// Add a task to save "old" statistics.
// Called when any player logs in.
function AddUpdateStatsEvent (int $now=0) : void
{
    global $db_prefix;

    if ($now == 0) $now = time ();

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_UPDATE_STATS."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $today = getdate ( $now );
        $hours = $today['hours'];
        if ( $hours >= 8 && $hours < 16 ) $when = mktime ( 16, 5, 0 ) - $now;
        else if ( $hours >= 16 && $hours < 20 ) $when = mktime ( 20, 5, 0 ) - $now;
        else $when = mktime ( 8, 5, 0, $today['mon'], $today['mday'] + 1 ) - $now;

        AddQueue (USER_SPACE, QTYP_UPDATE_STATS, 0, 0, 0, $now, $when, QUEUE_PRIO_UPDATE_STATS);
    }
}

// Save the "old" player and alliance points.
function Queue_UpdateStats_End (array $queue) : void
{
    global $db_prefix, $GlobalUni;

    $when = $queue['end'];
    $query = "UPDATE ".$db_prefix."users SET oldscore1 = score1, oldscore2 = score2, oldscore3 = score3, oldplace1 = place1, oldplace2 = place2, oldplace3 = place3, scoredate = $when;";
    dbquery ( $query ); 
    $query = "UPDATE ".$db_prefix."ally SET oldscore1 = score1, oldscore2 = score2, oldscore3 = score3, oldplace1 = place1, oldplace2 = place2, oldplace3 = place3, scoredate = $when;";
    dbquery ( $query ); 

    RemoveQueue ( $queue['task_id'] );
    AddUpdateStatsEvent ($when);
    Debug ( va(loca_lang("DEBUG_QUEUE_OLD_SCORE_SAVED", $GlobalUni['lang']), date ("H:i", $when))  );
}

// Add a player unload task if it doesn't already exist.
// Called when any player logs in.
function AddReloginEvent () : void
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_UNLOAD_ALL."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(3, 0, 0, date("m"), date("d")+1, date("y")) - $now;
        $id = AddQueue (USER_SPACE, QTYP_UNLOAD_ALL, 0, 0, 0, $now, $when, QUEUE_PRIO_RELOGIN);
    }
}

// Unload all players (so called relogin event)
function Queue_Relogin_End (array $queue) : void
{
    // Cleanup of unvisited farspaces.
    global $db_prefix;
    $query = "SELECT target_planet FROM ".$db_prefix."fleet WHERE mission = ".FTYP_EXPEDITION." OR mission = ".(FTYP_EXPEDITION+FTYP_RETURN)." OR mission = ".(FTYP_EXPEDITION+FTYP_ORBITING);
    $query = "DELETE FROM ".$db_prefix."planets WHERE type=".PTYP_FARSPACE." AND planet_id <> ALL ($query)";
    dbquery ( $query );

    UnloadAll ();
    RemoveQueue ( $queue['task_id'] );

    // Clear the game's daily hack attempt counter.
    ResetHackCounter ();
}

// Add a virtual DF cleanup task if it does not already exist.
// Called when any player logs in.
function AddCleanDebrisEvent () : void
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_CLEAN_DEBRIS."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time();
        $when = strtotime('next monday 01:10') - $now;
        $id = AddQueue (USER_SPACE, QTYP_CLEAN_DEBRIS, 0, 0, 0, $now, $when, QUEUE_PRIO_CLEAN_DEBRIS);
    }
}

// Cleanup of virtual debris fields.
function Queue_CleanDebris_End (array $queue) : void
{
    global $db_prefix;
    $query = "SELECT target_planet FROM ".$db_prefix."fleet WHERE mission = ".FTYP_RECYCLE." OR mission = ".(FTYP_RECYCLE+FTYP_RETURN);
    $query = "DELETE FROM ".$db_prefix."planets WHERE (type=".PTYP_DF." AND `".GID_RC_METAL."`=0 AND `".GID_RC_CRYSTAL."`=0) AND planet_id <> ALL ($query)";
    dbquery ( $query );
    RemoveQueue ( $queue['task_id'] );
    AddCleanDebrisEvent ();
}

// Add the task of cleaning up deleted planets and moons, if it doesn't already exist.
// Called when any player logs in.
function AddCleanPlanetsEvent () : void
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_CLEAN_PLANETS."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(1, 10, 0, date("m"), date("d")+1, date("y")) - $now;
        $id = AddQueue (USER_SPACE, QTYP_CLEAN_PLANETS, 0, 0, 0, $now, $when, QUEUE_PRIO_CLEAN_PLANETS);
    }
}

// Cleaning up destroyed planets.
function Queue_CleanPlanets_End (array $queue) : void
{
    global $db_prefix, $GlobalUni;

    $when = $queue['end'];
    $query = "SELECT * FROM ".$db_prefix."planets WHERE remove <= $when AND remove <> 0";
    $result = dbquery ( $query );
    $rows = dbrows ( $result );
    $count = 0;
    while ( $rows-- ) 
    {
        $planet = dbarray ( $result );
        $planet_id = $planet['planet_id'];

        // Return fleets flying to the planet being removed.
        $query = "SELECT * FROM ".$db_prefix."fleet WHERE target_planet = $planet_id AND mission < ".FTYP_RETURN.";";
        $fleet_result = dbquery ( $query );
        $fleets = dbrows ($fleet_result);
        while ( $fleets-- )
        {
            $fleet_obj = dbarray ( $fleet_result );
            RecallFleet ( $fleet_obj['fleet_id'], $when );
        }

        DestroyPlanet ( $planet_id );
        $count++;
    }

    Debug ( va(loca_lang("DEBUG_QUEUE_CLEAN_PLANETS", $GlobalUni['lang']), $count)  );
    RemoveQueue ( $queue['task_id'] );
    AddCleanPlanetsEvent ();
}

// Add the task of purging long inactive players and players put for deletion, if it doesn't already exist.
// Called when any player logs in.
function AddCleanPlayersEvent () : void
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_CLEAN_PLAYERS."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(1, 10, 0, date("m"), date("d")+1, date("y")) - $now;
        $id = AddQueue (USER_SPACE, QTYP_CLEAN_PLAYERS, 0, 0, 0, $now, $when, QUEUE_PRIO_CLEAN_PLAYERS);
    }
}

// Delete players set for deletion and long inactive players
function Queue_CleanPlayers_End (array $queue) : void
{
    global $db_prefix;

    // Deletion of players placed on deletion
    $when = $queue['end'];
    $query = "SELECT * FROM ".$db_prefix."users WHERE disable_until <= $when AND disable_until <> 0 AND admin < 1 AND disable <> 0";
    $result = dbquery ( $query );
    $rows = dbrows ( $result );
    while ($rows-- )
    {
        $user = dbarray ( $result );
        RemoveUser ( $user['player_id'], $queue['end'] );
    }

    // Remove players who have been inactive for more than 35 days. Inactive bots and players with purchased DM will not be deleted.
    $when = $queue['end'] - 35*24*60*60;
    $query = "SELECT * FROM ".$db_prefix."users WHERE lastclick < $when AND admin < 1 AND lastclick <> 0 AND dm = 0";
    $result = dbquery ( $query );
    $rows = dbrows ( $result );
    while ($rows-- )
    {
        $user = dbarray ( $result );
        if ( !IsBot ($user['player_id']) ) RemoveUser ( $user['player_id'], $queue['end'] );
    }

    RemoveQueue ( $queue['task_id'] );
    AddCleanPlayersEvent ();
}

// Add the task of recalculating a player's score if it doesn't already exist.
// Called when any player logs in.
function AddRecalcAllyPointsEvent () : void
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_RECALC_ALLY_POINTS."' ";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(0, 10, 0, date("m"), date("d")+1, date("y")) - $now;
        AddQueue (USER_SPACE, QTYP_RECALC_ALLY_POINTS, 0, 0, 0, $now, $when, QUEUE_PRIO_RECALC_ALLY_POINTS);
    }
}

// Recalculate a player's points scored and his place in the statistics.
function Queue_RecalcAllyPoints_End (array $queue) : void
{
    RecalcAllyStats ();
    RecalcAllyRanks ();
    RemoveQueue ( $queue['task_id'] );
}

// Add a debug event.
function AddDebugEvent (int $when) : void
{
    $now = time ();
    $id = AddQueue (USER_SPACE, QTYP_DEBUG, 0, 0, 0, $now, $when, QUEUE_PRIO_DEBUG);
}

// Debug Event.
function Queue_Debug_End (array $queue) : void
{
    RemoveQueue ( $queue['task_id'] );
}

// ===============================================================================================================
// Fleet

function GetFleetQueue (int $fleet_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_FLEET."' AND sub_id = $fleet_id";
    $result = dbquery ($query);
    if ($result) return dbarray ($result);
    else return null;
}

// List their own fleet tasks, as well as friendly and enemy ones.
function EnumFleetQueue (int $player_id) : mixed
{
    global $db_prefix;
    $query = "SELECT planet_id FROM ".$db_prefix."planets WHERE owner_id = $player_id AND type < ".PTYP_DF;
    $query = "SELECT fleet_id FROM ".$db_prefix."fleet WHERE target_planet = ANY ($query) AND (mission < ".FTYP_RETURN." OR mission = ".(FTYP_ACS_HOLD+FTYP_ORBITING).")";
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_FLEET."' AND (sub_id = ANY ($query) OR owner_id = $player_id)";
    $result = dbquery ($query);
    return $result;
}

// List only their own fleet tasks.
// ipm: 1 -- count also flying IPMs (for scoring purposes)
function EnumOwnFleetQueue (int $player_id, int $ipm=0) : mixed
{
    global $db_prefix;
    if ($ipm) $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_FLEET."' AND owner_id = $player_id ORDER BY end ASC, prio DESC";
    else
    {
        $query = "SELECT fleet_id FROM ".$db_prefix."fleet WHERE mission <> ".FTYP_MISSILE." AND owner_id = $player_id";
        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_FLEET."' AND sub_id = ANY ($query) ORDER BY end ASC, prio DESC";
    }
    $result = dbquery ($query);
    return $result;
}

// To verify fleet dispatch less than a second ago
function EnumOwnFleetQueueSpecial (int $player_id) : mixed
{
    global $db_prefix;
    $query = "SELECT fleet_id FROM ".$db_prefix."fleet WHERE mission < ".FTYP_MISSILE." AND owner_id = $player_id";
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_FLEET."' AND sub_id = ANY ($query) ORDER BY start DESC";
    $result = dbquery ($query);
    return $result;
}

// List the fleets flying from or to the planet.
function EnumPlanetFleets (int $planet_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE start_planet = $planet_id OR target_planet = $planet_id;";
    $result = dbquery ($query);
    return $result;
}

// The fleet task completion handler can be found in the fleet.php module

?>