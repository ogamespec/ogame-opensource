<?php

// ACS Management.

/*

The structure of the ACS table:
- union_id: Union ID (INT PRIMARY KEY)
- fleet_id: ID of the ACS's lead fleet (initial Attack = slot 0) (INT)
- name: union name. default: "KV" + number (CHAR(20))
- players: IDs of invited players, separated by commas (TEXT)

*/

// Create ACS union. $fleet_id - head fleet. $name - union name.
function CreateUnion (int $fleet_id, string $name) : int
{
    global $db_prefix;

    $fleet_obj = LoadFleet ($fleet_id);

    // Check to see if there's already an union?
    if ( $fleet_obj['union_id'] != 0 ) return $fleet_obj['union_id'];

    // Unions can only be created for departing attacks.
    if ($fleet_obj['mission'] != 1) return 0;

    $target_planet = LoadPlanetById ( $fleet_obj['target_planet'] );
    if ($target_planet == null) return 0;
    $target_player = $target_planet['owner_id'];

    // You can't create an union against yourself
    if ( $target_player == $fleet_obj['owner_id'] ) return 0;

    // Add union
    $union = array ( 'fleet_id' => $fleet_id, 'target_player' => $target_player, 'name' => $name, 'players' => $fleet_obj['owner_id'] );
    $union_id = AddDBRow ($union, 'union');

    // Add a fleet to the union and change the Attack type (the ACS head is shown in a special way in the event list)
    $query = "UPDATE ".$db_prefix."fleet SET union_id = $union_id, mission = ".FTYP_ACS_ATTACK_HEAD." WHERE fleet_id = $fleet_id";
    dbquery ($query);
    return $union_id;
}

// Load ACS union
function LoadUnion (int $union_id) : array|null
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."union WHERE union_id = $union_id";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return null;
    $union = dbarray ($result);
    $union['player'] = explode (",", $union['players'] );
    $union['players'] = count ($union['player']);
    return $union;
}

// An union is removed when the last union fleet is recalled, or the objective is reached
function RemoveUnion (int $union_id) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."union WHERE union_id = $union_id";        // delete the union record
    dbquery ($query);
}

// Rename the ACS union.
function RenameUnion (int $union_id, string $name) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."union SET name = '".$name."' WHERE union_id = " . intval ($union_id);
    dbquery ($query);
}

// Add a new member to the union.
function AddUnionMember (int $union_id, string $name) : string
{
    global $db_prefix;
    global $GlobalUni;
    global $GlobalUser;
    $union = LoadUnion ($union_id);

    // The error of adding a player to ACS union is given in the language of the current user (the one who adds players via the Fleet menu)
    loca_add ("union", $GlobalUser['lang']);

    // Empty name, do nothing.
    if ($name === "") return "";

    // Maximum number of users reached
    $max_players = $GlobalUni['acs'] + 1;
    if ( $union['players'] >= $max_players ) return va(loca("ACS_MAX_USERS"), $max_players);

    // Find a user
    $name = mb_strtolower ($name, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."' LIMIT 1";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) return loca("ACS_USER_NOT_FOUND");
    $user = dbarray ($result);

    // Check if there is already such a user in ACS union.
    for ($i=0; $i<$union['players']; $i++)
    {
        if ( $union["player"][$i] == $user['player_id'] ) return loca("ACS_ALREADY_ADDED");    // there is.
    }

    // Add the user to the ACS union and send them an invitation message.
    $union['player'][$union['players']] = $user['player_id'];
    $query = "UPDATE ".$db_prefix."union SET players = '".implode(",", $union['player'])."' WHERE union_id = $union_id";
    dbquery ($query);

    $target_player = LoadUser ( $union['target_player'] );
    $head_fleet = LoadFleet ( $union['fleet_id'] );
    $target_planet = LoadPlanetById ( $head_fleet['target_planet'] );
    $queue = GetFleetQueue ( $union['fleet_id'] );

    // The ACS invitation message is sent in the language of the invited user.
    loca_add ("union", $user['lang']);

    $text = va ( loca_lang("ACS_INVITE_TEXT1", $user['lang']),
                        $GlobalUser['oname'], 
                        $union['name'], 
                        $target_player['oname'] ) .
            va (" <a href=\"#\" onClick=showGalaxy(#1,#2,#3)><b><u>[#4:#5:#6]</u></b></a>. ",
                        $target_planet['g'], $target_planet['s'], $target_planet['p'], 
                        $target_planet['g'], $target_planet['s'], $target_planet['p'] ) .
            va ( loca_lang("ACS_INVITE_TEXT2", $user['lang']), date ( "D M Y H:i:s", $queue['end'] ) );
    SendMessage ( $user['player_id'], $GlobalUser['oname'], loca_lang("ACS_INVITE_SUBJ", $user['lang']), $text, MTYP_MISC );

    return "";
}

// List the unions the player is in, as well as the union that the player is targeting (unless the friendly flag is set).
function EnumUnion (int $player_id, int $friendly=0) : array
{
    global $db_prefix;
    $count = 0;
    $unions = array ();
    $query = "SELECT * FROM ".$db_prefix."union ";
    $result = dbquery ($query);
    $rows = dbrows ($result);
    while ($rows--)
    {
        $union = dbarray ($result);
        $union['player'] = explode (",", $union['players'] );
        $union['players'] = count ($union['player']);
        for ($i=0; $i<$union['players']; $i++) {
            if ( $union["player"][$i] == $player_id || ( $union['target_player'] == $player_id && !$friendly )) { $unions[$count++] = $union; break; }
        }
    }
    return $unions;
}

// List the Union fleets
function EnumUnionFleets (int $union_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE union_id = $union_id";
    return dbquery ( $query );
}

// Update the arrival time of all union fleets except fleet_id. Return the new arrival time of the union.
function UpdateUnionTime (int $union_id, int $end, int $fleet_id, bool $force_set=false) : int
{
    global $db_prefix;
    $result = EnumUnionFleets ($union_id);
    $rows = dbrows ($result);
    while ($rows--)
    {
        $fleet_obj = dbarray ($result);
        if ( $fleet_obj['fleet_id'] == $fleet_id ) continue;
        $queue = GetFleetQueue ( $fleet_obj['fleet_id'] );
        $union_time = $queue['end'];
        $queue_id = $queue['task_id'];
        if ( $end > $union_time || $force_set )
        {
            $union_time = $end;
            $query = "UPDATE ".$db_prefix."queue SET end = $end WHERE task_id = $queue_id";
            dbquery ($query);
        }
    }
    return $union_time;
}

// Update fleet arrival time
function UpdateFleetTime (int $fleet_id, int $when) : void
{
    global $db_prefix;
    $queue = GetFleetQueue ($fleet_id);
    $queue_id = $queue['task_id'];
    $query = "UPDATE ".$db_prefix."queue SET end = $when WHERE task_id = $queue_id";
    dbquery ($query);
}

// List the fleets on hold
function GetHoldingFleets (int $planet_id) : mixed
{
    global $db_prefix;
    $uni = LoadUniverse ();    // limit the number of fleets to the universe settings
    $max = max (0, $uni['acs'] * $uni['acs'] - 1);
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE mission = ".(FTYP_ORBITING+FTYP_ACS_HOLD)." AND target_planet = $planet_id LIMIT $max";
    $result = dbquery ($query);
    return $result;
}

function IsPlayerInUnion (int $player_id, array $union) : bool
{
    if ( $union == null ) return false;
    foreach ( $union['player'] as $i=>$pid )
    {
        if ( $pid == $player_id ) return true;
    }
    return false;
}

?>