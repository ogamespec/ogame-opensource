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

// Queue task type
// For some reason during the development phase, the identifiers were made strings. TODO: Change them to INT type (but this would require a clean reinstall of the Universe)
const QTYP_UNBAN = "UnbanPlayer";               // unban player
const QTYP_CHANGE_EMAIL = "ChangeEmail";        // write down a permanent mailing address
const QTYP_ALLOW_NAME = "AllowName";            // allow player name changes
const QTYP_ALLOW_ATTACKS = "AllowAttacks";      // unban player's attack ban
const QTYP_UNLOAD_ALL = "UnloadAll";            // re-login all players
const QTYP_CLEAN_DEBRIS = "CleanDebris";        // virtual debris field cleanup
const QTYP_CLEAN_PLANETS = "CleanPlanets";      // removal of destroyed planets / abandoned moons
const QTYP_CLEAN_PLAYERS = "CleanPlayers";      // deleting inactive players and players put up for deletion (1:10 server time)
const QTYP_UPDATE_STATS = "UpdateStats";        // saving old stat points
const QTYP_RECALC_POINTS = "RecalcPoints";      // recalculation of player statistics
const QTYP_RECALC_ALLY_POINTS = "RecalcAllyPoints";  // recalculation of alliance statistics
const QTYP_BUILD = "Build";                     // completion of building on the planet (sub_id - task ID in the build queue, obj_id - type of building)
const QTYP_DEMOLISH = "Demolish";               // completion of demolition on the planet (sub_id - task ID in the build queue, obj_id - type of building)
const QTYP_RESEARCH = "Research";               // research (sub_id - number of the planet where the research was launched, obj_id - type of research)
const QTYP_SHIPYARD = "Shipyard";               // shipyard task (sub_id - planet number, obj_id - construction type)
const QTYP_FLEET = "Fleet";                     // Fleet task / IPM attack (sub_id - number of record in the fleet table)
const QTYP_DEBUG = "Debug";                     // debug event
const QTYP_AI = "AI";                           // tasks for bot (sub_id - strategy number, obj_id - current block number)
const QTYP_COUPON = "Coupon";                   // Coupon crediting (the handler is located in coupon.php)

// Queue task priorities
const QUEUE_PRIO_LOWEST = 0;            // Consider it no priority
const QUEUE_PRIO_DEBUG = 9999;          // Debug event priority (AddDebugEvent)
const QUEUE_PRIO_BUILD = 20;            // Priority for buildings and construction queue
const QUEUE_PRIO_FLEET = 200;       // Priority of fleet missions. The mission type is added to this value (see FTYP_)
const QUEUE_PRIO_RECALC_ALLY_POINTS = 400;
const QUEUE_PRIO_RECALC_POINTS = 500;
const QUEUE_PRIO_UPDATE_STATS = 510;
const QUEUE_PRIO_COUPON = 520;
const QUEUE_PRIO_CLEAN_DEBRIS = 600;
const QUEUE_PRIO_CLEAN_PLANETS = 700;
const QUEUE_PRIO_RELOGIN = 777;
const QUEUE_PRIO_CLEAN_PLAYERS = 900;

// Add a task to the queue. Returns the ID of the added task.
function AddQueue ($owner_id, $type, $sub_id, $obj_id, $level, $now, $seconds, $prio=QUEUE_PRIO_LOWEST)
{
    $queue = array ( null, $owner_id, $type, $sub_id, $obj_id, $level, $now, $now+$seconds, $prio );
    $id = AddDBRow ( $queue, "queue" );
    return $id;
}

// Load task.
function LoadQueue ($task_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE task_id = $task_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Delete a task from the queue.
function RemoveQueue ($task_id)
{
    global $db_prefix;
    if ($task_id) {
        $query = "DELETE FROM ".$db_prefix."queue WHERE task_id = $task_id";
        dbquery ($query);
    }
}

// Extend the task for the number of seconds specified
function ProlongQueue ($task_id, $seconds)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."queue SET end = end + $seconds WHERE task_id = $task_id";
    dbquery ($query);
}

// Check queue tasks for completion before $until time.
function UpdateQueue ($until)
{
    global $db_prefix;
    global $GlobalUni;

    if ( $GlobalUni['freeze'] ) return;

    LockTables ();

    $query = "SELECT * FROM ".$db_prefix."queue WHERE end <= $until ORDER BY end ASC, prio DESC LIMIT " . QUEUE_BATCH;
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
                Error ( loca_lang("DEBUG_QUEUE_UNKNOWN", $GlobalUni['lang']) . $queue['type']);
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
function FlushQueue ($planet_id)
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

// ===============================================================================================================
// Buildings

// Get a construction queue for the planet.
function GetBuildQueue ( $planet_id )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buildqueue WHERE planet_id = $planet_id ORDER BY list_id ASC;";
    return dbquery ($query);
}

// Verify all conditions of build/demolition possibility
// The $enqueue parameter is used to check if the build can be added to the queue.
function CanBuild ($user, $planet, $id, $lvl, $destroy, $enqueue=false)
{
    global $GlobalUni;
    global $buildmap;

    // Cost of building
    $res = BuildPrice ( $id, $lvl );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];

    $result = GetResearchQueue ( $user['player_id'] );
    $resqueue = dbarray ($result);
    $reslab_operating = ($resqueue != null);
    $result = GetShipyardQueue ( $planet['planet_id'] );
    $shipqueue = dbarray ($result);
    $shipyard_operating = ($shipqueue != null);

    loca_add ("build", $user['lang']);

    if ( $GlobalUni['freeze'] ) return loca_lang("BUILD_ERROR_UNI_FREEZE", $user['lang']);

    // Not a building
    if ( ! in_array ( $id, $buildmap ) ) return loca_lang("BUILD_ERROR_INVALID_ID", $user['lang']);

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
    else if ( !IsEnoughResources ( $planet, $m, $k, $d, $e ) && !$enqueue ) return loca_lang("BUILD_ERROR_NO_RES", $user['lang']);

    // Check available technologies.
    else if ( !BuildMeetRequirement ( $user, $planet, $id ) ) return loca_lang("BUILD_ERROR_REQUIREMENTS", $user['lang']);

    if ( $destroy )
    {
        if ( $id == GID_B_TERRAFORMER || $id == GID_B_LUNAR_BASE ) return loca_lang("BUILD_ERROR_CANT_DEMOLISH", $user['lang']);
        else if ( $planet["b".$id] <= 0 ) return loca_lang("BUILD_ERROR_NO_SUCH_BUILDING", $user['lang']);
    }

    return "";
}

// Start the next construction
function PropagateBuildQueue ($planet_id, $from)
{
    global $db_prefix, $GlobalUni;

    $speed = $GlobalUni['speed'];

    $planet = GetPlanet ( $planet_id );
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
                $res = BuildPrice ( $id, $lvl );
                $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
                AdjustResources ( $m, $k, $d, $planet_id, '-' );

                if ( $destroy ) $BuildEvent = QTYP_DEMOLISH;
                else $BuildEvent = QTYP_BUILD;

                $duration = floor (BuildDuration ( $id, $lvl, $planet['b'.GID_B_ROBOTS], $planet['b'.GID_B_NANITES], $speed ));
                AddQueue ( $user['player_id'], $BuildEvent, $row['id'], $id, $lvl, $from, $duration, QUEUE_PRIO_BUILD );

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
function BuildEnque ( $user, $planet_id, $id, $destroy, $now=0 )
{
    global $GlobalUni;

    $speed = $GlobalUni['speed'];
    if ( $GlobalUni['freeze'] ) return "";

    $planet = GetPlanet ( $planet_id );

    $prem = PremiumStatus ($user);
    if ($prem['commander']) $maxcnt = 5;
    else $maxcnt = 1;

    if ($now == 0) $now = time ();

    // Write down the user's action, even if the user does something wrong
    if ($destroy) UserLog ( $planet['owner_id'], "BUILD", va(loca_lang("DEBUG_LOG_DEMOLISH", $GlobalUni['lang']), loca("NAME_$id"), $planet['b'.$id]-1, $planet_id)  );
    else UserLog ( $planet['owner_id'], "BUILD", va(loca_lang("DEBUG_LOG_BUILD", $GlobalUni['lang']), loca("NAME_$id"), $planet['b'.$id]+1, $planet_id)  );

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
    $nowlevel = $planet['b'.$id];
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
            $res = BuildPrice ( $id, $lvl );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            AdjustResources ( $m, $k, $d, $planet_id, '-' );
        }

        if ( $destroy ) $BuildEvent = QTYP_DEMOLISH;
        else $BuildEvent = QTYP_BUILD;

        $duration = floor (BuildDuration ( $id, $lvl, $planet['b'.GID_B_ROBOTS], $planet['b'.GID_B_NANITES], $speed ));
        $row = array ( '', $user['player_id'], $planet_id, $list_id, $id, $lvl, $destroy, $now, $now+$duration );
        $sub_id = AddDBRow ( $row, "buildqueue" );
        if ($list_id == 1) AddQueue ( $user['player_id'], $BuildEvent, $sub_id, $id, $lvl, $now, $duration, QUEUE_PRIO_BUILD );
    }

    return $text;
}

// Cancel construction/demolition; $user - is the user who removes build slot from the queue.
function BuildDeque ( $user, $planet_id, $listid )
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
            $res = BuildPrice ( $id, $lvl );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            AdjustResources ( $m, $k, $d, $planet_id, '+' );           
        }
        else $queue_id = 0;

        // Adjusting levels on the queue
        $query = "UPDATE ".$db_prefix."buildqueue SET level = level - 1 WHERE tech_id = ".$row['tech_id']." AND planet_id = $planet_id AND list_id > " . $row['list_id'];
        dbquery ($query);

        $planet = GetPlanet ( $planet_id );
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
function Queue_Build_End ($queue)
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
    $planet = GetPlanet ( $planet_id );
    $player_id = $planet['owner_id'];
    ProdResources ( $planet, $planet['lastpeek'], $queue['end'] );

    // Foolproofing
    if ( ($queue['type'] === QTYP_BUILD && $planet["b".$id] >= $lvl) ||
         ($queue['type'] === QTYP_DEMOLISH && $planet["b".$id] <= $lvl) )
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
    $query = "UPDATE ".$db_prefix."planets SET ".('b'.$id)." = $lvl, $fields WHERE planet_id = $planet_id";
    dbquery ($query);

    RemoveQueue ( $queue['task_id'] );
    dbquery ( "DELETE FROM ".$db_prefix."buildqueue WHERE id = " . $queue['sub_id'] );

    // Add points. Recalculate places only for large constructions.
    if ( $queue['type'] === "Build" ) {
        $res = BuildPrice ( $id, $lvl );
        $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
        $points = $m + $k + $d;
        AdjustStats ( $queue['owner_id'], $points, 0, 0, '+');
    }
    else {
        $res = BuildPrice ( $id, $lvl+1 );
        $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
        $points = $m + $k + $d;
        AdjustStats ( $queue['owner_id'], $points, 0, 0, '-');
    }
    if ( $lvl > 10 ) RecalcRanks ();

    if ( $GlobalUser['player_id'] == $player_id) {
        InvalidateUserCache ();
        $GlobalUser = LoadUser ( $player_id );    // update the current user's data
    }

    // Start the next construction
    PropagateBuildQueue ($planet_id, $queue['end']);
}

// ===============================================================================================================
// Shipyard

// Get a queue of tasks at the shipyard.
function GetShipyardQueue ($planet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_SHIPYARD."' AND sub_id = $planet_id ORDER BY start ASC";
    return dbquery ($query);
}

// Get the end time of the last task at the shipyard, used to get the start time of a new task.
function ShipyardLatestTime ($planet_id, $now)
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
function AddShipyard ($player_id, $planet_id, $gid, $value, $now=0 )
{
    global $db_prefix, $GlobalUni;
    global $fleetmap;
    global $defmap;

    if ( in_array ( $gid, $defmap ) ) UserLog ( $player_id, "DEFENSE", va(loca_lang("DEBUG_LOG_DEFENSE", $GlobalUni['lang']), loca("NAME_$gid"), $value, $planet_id)  );
    else UserLog ( $player_id, "SHIPYARD", va(loca_lang("DEBUG_LOG_SHIPYARD", $GlobalUni['lang']), loca("NAME_$gid"), $value, $planet_id)  );

    $techmap = array_merge ($fleetmap, $defmap);
    if ( ! in_array ( $gid, $techmap ) ) return;

    $uni = $GlobalUni;
    if ( $uni['freeze'] ) return;

    // Shield domes can be built up to a maximum of 1 unit.
    if ( ($gid == GID_D_SDOME || $gid == GID_D_LDOME) && $value > 1 ) $value = 1;

    $planet = GetPlanet ( $planet_id );

    // If the planet already has a shield dome, we don't build it.
    if ( ($gid == GID_D_SDOME || $gid == GID_D_LDOME) && $planet["d".$gid] > 0 ) return;

    // If a dome of the same type is already being built in the queue, then do not add another dome to the queue.
    // Limit the number of missiles ordered to those already under construction
    $result = GetShipyardQueue ($planet_id);
    $tasknum = dbrows ($result);
    $rak_space = $planet['b'.GID_B_MISS_SILO] * 10 - ($planet['d'.GID_D_ABM] + 2 * $planet['d'.GID_D_IPM]);
    while ($tasknum--)
    {
        $queue = dbarray ( $result );
        if ( $queue['obj_id'] == GID_D_SDOME || $queue['obj_id'] == GID_D_LDOME )
        {
            if ( $queue['obj_id'] == $gid ) return;    // is in line to build a dome of the same type.
        }
        if ( $queue['obj_id'] == GID_D_ABM || $queue['obj_id'] == GID_D_IPM )
        {
            if ( $queue['obj_id'] == GID_D_ABM ) $rak_space -= $queue['level'];
            else $rak_space -= 2 * $queue['level'];
        }
    }

    if ( $gid == GID_D_ABM ) $value = min ( $rak_space, $value );
    if ( $gid == GID_D_IPM ) $value = min ( floor ($rak_space / 2), $value );
    if ( $value <= 0 ) return;

    $user = LoadUser ( $player_id );

    $res = ShipyardPrice ( $gid );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    $m *= $value;
    $k *= $value;
    $d *= $value;

    if ( IsEnoughResources ( $planet, $m, $k, $d, $e ) && ShipyardMeetRequirement ($user, $planet, $gid) ) {
        $speed = $uni['speed'];
        $now = ShipyardLatestTime ($planet_id, $now);
        $shipyard = $planet["b".GID_B_SHIPYARD];
        $nanits = $planet["b".GID_B_NANITES];
        $seconds = ShipyardDuration ( $gid, $shipyard, $nanits, $speed );

        // Списать ресурсы.
        AdjustResources ( $m, $k, $d, $planet_id, '-' );

        AddQueue ($player_id, QTYP_SHIPYARD, $planet_id, $gid, $value, $now, $seconds);
    }
}

// Finish building at the shipyard.
function Queue_Shipyard_End ($queue, $when=0)
{
    global $db_prefix, $GlobalUser;

    if ($when == 0) $now = time ();
    else $now = $when;
    $gid = $queue['obj_id'];
    $planet_id = $queue['sub_id'];
    $planet = GetPlanet ($planet_id);
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
    if (IsDefense($gid)) $query = "UPDATE ".$db_prefix."planets SET d$gid = d$gid + $done WHERE planet_id = $planet_id";
    else $query = "UPDATE ".$db_prefix."planets SET f$gid = f$gid + $done WHERE planet_id = $planet_id";
    dbquery ($query);

    // Add points.
    $res = ShipyardPrice ( $gid );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $enrg = $res['e'];
    $points = ($m + $k + $d) * $done;
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
function CanResearch ($user, $planet, $id, $lvl)
{
    global $db_prefix, $GlobalUni;
    global $resmap;

    {
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

        $res = ResearchPrice ( $id, $lvl );
        $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];

        // Not research
        if ( ! in_array ( $id, $resmap ) ) return loca_lang("BUILD_ERROR_INVALID_ID", $user['lang']);

        // You can't build in vacation mode
        else if ( $user['vacation'] ) return loca_lang("BUILD_ERROR_RESEARCH_VACATION", $user['lang']);

        // You can't research on foreign planet
        else if ( $planet['owner_id'] != $user['player_id'] ) return loca_lang("BUILD_ERROR_INVALID_PLANET", $user['lang']);

        else if ( !IsEnoughResources ( $planet, $m, $k, $d, $e ) ) return loca_lang("BUILD_ERROR_NO_RES", $user['lang']);

        else if ( !ResearchMeetRequirement ( $user, $planet, $id ) ) return loca_lang("BUILD_ERROR_REQUIREMENTS", $user['lang']);
    }
    return "";
}

// Start research on the planet (includes all checks).
function StartResearch ($player_id, $planet_id, $id, $now)
{
    global $db_prefix, $GlobalUni;
    $uni = $GlobalUni;

    $planet = GetPlanet ( $planet_id );

    UserLog ( $player_id, "RESEARCH", va(loca_lang("DEBUG_LOG_RESEARCH", $GlobalUni['lang']), loca("NAME_$id"), $planet_id)  );

    // Get a level of research.
    $user = LoadUser ( $player_id );
    $level = $user['r'.$id] + 1;

    $prem = PremiumStatus ($user);
    if ( $prem['technocrat'] ) $r_factor = 1.1;
    else $r_factor = 1.0;

    // Check conditions.
    $text = CanResearch ( $user, $planet, $id, $level );

    if ( $text === "" ) {
        $speed = $uni['speed'];
        if ($now == 0) $now = time ();
        $reslab = ResearchNetwork ( $planet['planet_id'], $id );
        $seconds = ResearchDuration ( $id, $level, $reslab, $speed * $r_factor);

        // Списать ресурсы.
        $res = ResearchPrice ( $id, $level );
        AdjustResources ( $res['m'], $res['k'], $res['d'], $planet_id, '-' );

        AddQueue ($player_id, QTYP_RESEARCH, $planet_id, $id, $level, $now, $seconds);
    }
}

// Cancel the research.
function StopResearch ($player_id)
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
    $planet = GetPlanet ( $planet_id );
    if ($planet['owner_id'] != $player_id )
    {
        Error ( va(loca_lang("DEBUG_QUEUE_CANCEL_RESEARCH_FOREIGN", $GlobalUni['lang']), 
            loca("NAME_$id"), 
            $user['oname'], 
            "[".$planet['g'].":".$planet['s'].":".$planet['p']."] " . $planet['name'] )
        );
        return;
    }
    $res = ResearchPrice ( $id, $level );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];

    // Return resources
    AdjustResources ( $m, $k, $d, $planet_id, '+' );

    RemoveQueue ( $resq['task_id'] );

    UserLog ( $player_id, "RESEARCH", va(loca_lang("DEBUG_LOG_RESEARCH_CANCEL", $GlobalUni['lang']), loca("NAME_$id"), $planet_id) );
}

// Get the current research for the account.
function GetResearchQueue ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_RESEARCH."' AND owner_id = $player_id ORDER BY start ASC";
    return dbquery ($query);
}

// Complete the research.
function Queue_Research_End ($queue)
{
    global $db_prefix, $GlobalUser, $GlobalUni;

    $id = $queue['obj_id'];
    $lvl = $queue['level'];
    $planet_id = $queue['sub_id'];
    $player_id = $queue['owner_id'];

    // Calculate the planet's production since the last update.
    $planet = GetPlanet ( $planet_id );
    ProdResources ( $planet, $planet['lastpeek'], $queue['end'] );

    // Update the research level in the database.
    $query = "UPDATE ".$db_prefix."users SET ".('r'.$id)." = $lvl WHERE player_id = $player_id";
    dbquery ($query);

    RemoveQueue ( $queue['task_id'] );

    // Добавить очки.
    $res = ResearchPrice ( $id, $lvl );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    $points = $m + $k + $d;
    AdjustStats ( $queue['owner_id'], $points, 0, 1, '+');
    RecalcRanks ();

    Debug ( va(loca_lang("DEBUG_QUEUE_RESEARCH_COMPLETE", $GlobalUni['lang']), loca("NAME_$id"), $lvl, $player_id)  );

    if ( $GlobalUser['player_id'] == $player_id) {
        InvalidateUserCache ();
        $GlobalUser = LoadUser ( $player_id );    // update the current user's data
    }
}

// ===============================================================================================================
// Player

// Add the task of recalculating a player's score if it doesn't already exist.
// Called when any player logs in.
function AddRecalcPointsEvent ($player_id)
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_RECALC_POINTS."' AND owner_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(0, 10, 0, date("m"), date("d")+1, date("y"));
        $queue = array ( null, $player_id, QTYP_RECALC_POINTS, 0, 0, 0, $now, $when, QUEUE_PRIO_RECALC_POINTS );
        AddDBRow ( $queue, "queue" );
    }
}

// Recalculate a player's points scored and his place in the statistics.
function Queue_RecalcPoints_End ($queue)
{
    RecalcStats ( $queue['owner_id'] );
    RecalcRanks ();
    RemoveQueue ( $queue['task_id'] );
}

// It's okay to go vaction mode or not.
function CanEnableVacation ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE (type = '".QTYP_BUILD."' OR type = '".QTYP_DEMOLISH."' OR type = '".QTYP_RESEARCH."' OR type = '".QTYP_SHIPYARD."' OR type = '".QTYP_FLEET."') AND owner_id = $player_id";
    $result = dbquery ( $query );
    if ( dbrows ($result) > 0 ) return false;
    else return true;
}

// Add a name change permission task.
function AddAllowNameEvent ($player_id)
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_ALLOW_NAME."' AND owner_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = $now + 7 * 24 * 60 * 60;
        $queue = array ( null, $player_id, QTYP_ALLOW_NAME, 0, 0, 0, $now, $when, 0 );
        $id = AddDBRow ( $queue, "queue" );
        $query = "UPDATE ".$db_prefix."users SET name_changed = 1, name_until = $when WHERE player_id = $player_id";
        dbquery ($query);
    }
}

// Can the player's name be changed.
function CanChangeName ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_ALLOW_NAME."' AND owner_id = $player_id";
    $result = dbquery ( $query );
    if ( dbrows ($result) > 0 ) return false;
    else return true;
}

// Allow name change.
function Queue_AllowName_End ($queue)
{
    global $db_prefix;
    $player_id = $queue['owner_id'];
    $query = "UPDATE ".$db_prefix."users SET name_changed = 0 WHERE player_id = $player_id";
    dbquery ($query);
    RemoveQueue ( $queue['task_id'] );
}

// Unban a player
function Queue_UnbanPlayer_End ($queue)
{
    global $db_prefix;
    $player_id = $queue['owner_id'];
    $query = "UPDATE ".$db_prefix."users SET banned = 0, banned_until = 0 WHERE player_id = $player_id";
    dbquery ($query);
    RemoveQueue ( $queue['task_id'] );
}

// Allow attacks
function Queue_AllowAttacks_End ($queue)
{
    global $db_prefix;
    $player_id = $queue['owner_id'];
    $query = "UPDATE ".$db_prefix."users SET noattack = 0, noattack_until = 0 WHERE player_id = $player_id";
    dbquery ($query);
    RemoveQueue ( $queue['task_id'] );
}

// Add a permanent mail address update task
function AddChangeEmailEvent ($player_id)
{
    global $db_prefix;

    $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_CHANGE_EMAIL."' AND owner_id = $player_id";
    dbquery ($query);

    $now = time ();
    $when = $now + 7 * 24 * 60 * 60;
    $queue = array ( null, $player_id, QTYP_CHANGE_EMAIL, 0, 0, 0, $now, $when, 0 );
    $id = AddDBRow ( $queue, "queue" );
}

// Update permanent mail address
function Queue_ChangeEmail_End ($queue)
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
function AddUpdateStatsEvent ($now=0)
{
    global $db_prefix;

    if ($now == 0) $now = time ();

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_UPDATE_STATS."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $today = getdate ( $now );
        $hours = $today['hours'];
        if ( $hours >= 8 && $hours < 16 ) $when = mktime ( 16, 5, 0 );
        else if ( $hours >= 16 && $hours < 20 ) $when = mktime ( 20, 5, 0 );
        else $when = mktime ( 8, 5, 0, $today['mon'], $today['mday'] + 1 );

        $queue = array ( null, USER_SPACE, QTYP_UPDATE_STATS, 0, 0, 0, $now, $when, QUEUE_PRIO_UPDATE_STATS );
        AddDBRow ( $queue, "queue" );
    }
}

// Save the "old" player and alliance points.
function Queue_UpdateStats_End ($queue)
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
function AddReloginEvent ()
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_UNLOAD_ALL."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(3, 0, 0, date("m"), date("d")+1, date("y"));;
        $queue = array ( null, USER_SPACE, QTYP_UNLOAD_ALL, 0, 0, 0, $now, $when, QUEUE_PRIO_RELOGIN );
        $id = AddDBRow ( $queue, "queue" );
    }
}

// Unload all players (so called relogin event)
function Queue_Relogin_End ($queue)
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
function AddCleanDebrisEvent ()
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_CLEAN_DEBRIS."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $week = mktime(0, 0, 0, date('m'), date('d')-date('w'), date('Y')) + 24 * 60 * 60;
        $when = $week + 7 * 24 * 60 * 60 + 10 * 60;
        $queue = array ( null, USER_SPACE, QTYP_CLEAN_DEBRIS, 0, 0, 0, $now, $when, QUEUE_PRIO_CLEAN_DEBRIS );
        $id = AddDBRow ( $queue, "queue" );
    }
}

// Cleanup of virtual debris fields.
function Queue_CleanDebris_End ($queue)
{
    global $db_prefix;
    $query = "SELECT target_planet FROM ".$db_prefix."fleet WHERE mission = ".FTYP_RECYCLE." OR mission = ".(FTYP_RECYCLE+FTYP_RETURN);
    $query = "DELETE FROM ".$db_prefix."planets WHERE (type=".PTYP_DF." AND m=0 AND k=0) AND planet_id <> ALL ($query)";
    dbquery ( $query );
    RemoveQueue ( $queue['task_id'] );
    AddCleanDebrisEvent ();
    GalaxyToolUpdate ();
}

// Add the task of cleaning up deleted planets and moons, if it doesn't already exist.
// Called when any player logs in.
function AddCleanPlanetsEvent ()
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_CLEAN_PLANETS."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(1, 10, 0, date("m"), date("d")+1, date("y"));
        $queue = array ( null, USER_SPACE, QTYP_CLEAN_PLANETS, 0, 0, 0, $now, $when, QUEUE_PRIO_CLEAN_PLANETS );
        $id = AddDBRow ( $queue, "queue" );
    }
}

// Cleaning up destroyed planets.
function Queue_CleanPlanets_End ($queue)
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
function AddCleanPlayersEvent ()
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_CLEAN_PLAYERS."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(1, 10, 0, date("m"), date("d")+1, date("y"));
        $queue = array ( null, USER_SPACE, QTYP_CLEAN_PLAYERS, 0, 0, 0, $now, $when, QUEUE_PRIO_CLEAN_PLAYERS );
        $id = AddDBRow ( $queue, "queue" );
    }
}

// Delete players set for deletion and long inactive players
function Queue_CleanPlayers_End ($queue)
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
function AddRecalcAllyPointsEvent ()
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_RECALC_ALLY_POINTS."' ";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 )
    {
        $now = time ();
        $when = mktime(0, 10, 0, date("m"), date("d")+1, date("y"));
        $queue = array ( null, USER_SPACE, QTYP_RECALC_ALLY_POINTS, 0, 0, 0, $now, $when, QUEUE_PRIO_RECALC_ALLY_POINTS );
        AddDBRow ( $queue, "queue" );
    }
}

// Recalculate a player's points scored and his place in the statistics.
function Queue_RecalcAllyPoints_End ($queue)
{
    RecalcAllyStats ();
    RecalcAllyRanks ();
    RemoveQueue ( $queue['task_id'] );
}

// Add a debug event.
function AddDebugEvent ($when)
{
    $now = time ();
    $queue = array ( null, USER_SPACE, QTYP_DEBUG, 0, 0, 0, $now, $when, QUEUE_PRIO_DEBUG );
    $id = AddDBRow ( $queue, "queue" );
}
// Debug Event.
function Queue_Debug_End ($queue)
{
    RemoveQueue ( $queue['task_id'] );
}

// ===============================================================================================================
// Fleet

function GetFleetQueue ($fleet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_FLEET."' AND sub_id = $fleet_id";
    $result = dbquery ($query);
    if ($result) return dbarray ($result);
    else return NULL;
}

// List their own fleet tasks, as well as friendly and enemy ones.
function EnumFleetQueue ($player_id)
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
function EnumOwnFleetQueue ($player_id, $ipm=0)
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
function EnumOwnFleetQueueSpecial ($player_id)
{
    global $db_prefix;
    $query = "SELECT fleet_id FROM ".$db_prefix."fleet WHERE mission < ".FTYP_MISSILE." AND owner_id = $player_id";
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_FLEET."' AND sub_id = ANY ($query) ORDER BY start DESC";
    $result = dbquery ($query);
    return $result;
}

// List the fleets flying from or to the planet.
function EnumPlanetFleets ($planet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE start_planet = $planet_id OR target_planet = $planet_id;";
    $result = dbquery ($query);
    return $result;
}

// The fleet task completion handler can be found in the fleet.php module

?>