<?php

// Alliance System.

// Important! This game feature involves a rich interaction with input from the user.
// You need to pay a lot of attention to the security of the input data (size and content checks).

// The ally tags can't match, but the names can.

// Alliance entries in the database (ally).
// ally_id: Alliance ordinal number (INT AUTO_INCREMENT PRIMARY KEY)
// tag: Alliance tag, 3-8 characters (CHAR(8))
// name: Alliance name, 3-30 characters (CHAR(30))
// owner_id: founder ID
// homepage: Home page URL
// imglogo: URL of the logo image
// open: 0 - applications are forbidden (alliance recruitment is closed), 1 - applications are allowed.
// insertapp: 1 - automatically insert application template, 0 - do not insert template
// exttext: External text (TEXT)
// inttext: Internal text (TEXT)
// apptext: Application text (TEXT)
// nextrank: Ordinal number of the next rank (INT)
// old_tag: The old alliance tag (CHAR(8))
// old_name: The old name of the alliance (CHAR(30))
// tag_until: When you can change the alliance tag (INT UNSIGNED)
// name_until: When you can change the alliance name (INT UNSIGNED)
// score1,2,3: Points for buildings, fleets, and research (BIGINT UNSIGNED, INT UNSIGNED, INT UNSIGNED )
// place1,2,3: Place for buildings, fleet, research (INT)
// oldscore1,2,3: Old points for buildings, fleets, and research (BIGINT UNSIGNED, INT UNSIGNED, INT UNSIGNED )
// oldplace1,2,3: old place for buildings, fleet, research (INT)
// scoredate: Time of saving old statistics (INT UNSIGNED)

// Create alliance. Returns the ID of the alliance.
function CreateAlly ($owner_id, $tag, $name)
{
    global $db_prefix;
    $tag = mb_substr ($tag, 0, 8, "UTF-8");    // Limit the length of strings
    $name = mb_substr ($name, 0, 30, "UTF-8");

    // Texts and rank names default to the language of the player who creates the alliance
    $user = LoadUser ($owner_id);
    loca_add ( "ally", $user['lang'] );

    // Add alliance.
    $ally = array( null, $tag, $name, $owner_id, "", "", 1, 0, loca_lang("ALLY_NEW_DEFAULT_TEXT", $user['lang']), "", "", 0, "", "", 0, 0,
                        0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0 );
    $id = AddDBRow ( $ally, "ally" );

    // Add Founder (0) and Newcomer (1) ranks .
    SetRank ( $id, AddRank ( $id, loca_lang("ALLY_NEW_RANK_FOUNDER", $user['lang']) ), 0x1FF );
    SetRank ( $id, AddRank ( $id, loca_lang("ALLY_NEW_RANK_NEWCOMER", $user['lang']) ), 0 );

    // Update the founder user information.
    $joindate = time ();
    $query = "UPDATE ".$db_prefix."users SET ally_id = $id, joindate = $joindate, allyrank = 0 WHERE player_id = $owner_id";
    dbquery ($query);

    return $id;
}

// Dismiss the alliance.
function DismissAlly ($ally_id)
{
    global $db_prefix;

    // Make the ally_id and ranks of all alliance players 0.
    $query = "UPDATE ".$db_prefix."users SET ally_id = 0, joindate = 0, allyrank = 0 WHERE ally_id = $ally_id";
    dbquery ($query);

    // Delete ranks from the rank table
    $query = "DELETE FROM ".$db_prefix."allyranks WHERE ally_id = $ally_id";
    dbquery ($query);

    // Delete all unprocessed applications
    $query = "DELETE FROM ".$db_prefix."allyapps WHERE ally_id = $ally_id";
    dbquery ($query);

    // Delete an entry from the alliances table.
    $query = "DELETE FROM ".$db_prefix."ally WHERE ally_id = $ally_id";
    dbquery ($query);
}

// List all players in the alliance.
// Sorting: 0 - Coordinates, 1 - Name, 2 - Status, 3 - Points, 4 - Date Entry, 5 - Online
// Order: 0 - ascending, 1 - descending
function EnumerateAlly ($ally_id, $sort_by=0, $order=0, $use_sort=false)
{
    global $db_prefix;
    if ($ally_id <= 0) return NULL;

    $sort = "";
    if ($use_sort) {
        switch ( $sort_by ) 
        {
            case 1 : $sort = " ORDER BY oname "; break;
            case 2 : $sort = " ORDER BY allyrank "; break;
            case 3 : $sort = " ORDER BY score1 "; break;
            case 4 : $sort = " ORDER BY joindate "; break;
            case 5 : $sort = " ORDER BY lastclick "; break;
            default : $sort = " ORDER BY player_id "; break;
        }
        if ( $order ) $sort .= " DESC";
    }

    $query = "SELECT u.oname, u.ally_id, u.allyrank, u.score1, u.player_id, u.hplanetid, u.joindate, u.lastclick, u.lang, r.name, p.g, p.s, p.p " .
			 "	FROM ".$db_prefix."users u " .
			 "	LEFT  JOIN ".$db_prefix."allyranks r ON u.ally_id = r.ally_id AND u.allyrank = r.rank_id " .
			 "  LEFT  JOIN ".$db_prefix."planets p ON u.hplanetid = p.planet_id " .
			 "	WHERE u.ally_id = $ally_id " . $sort;

    $result = dbquery ($query);
    return $result;
}

// Find out if there is an alliance with the specified tag.
function IsAllyTagExist ($tag)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."ally WHERE tag = '".$tag."'";
    $result = dbquery ($query);
    if (dbrows ($result)) return true;
    else return false;
}

// Load alliance.
function LoadAlly ($ally_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."ally WHERE ally_id = $ally_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Search for alliances by tag. Returns the result of the SQL query.
function SearchAllyTag ($tag)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."ally WHERE tag LIKE '%".$tag."%' LIMIT 30";
    $result = dbquery ($query);
    return $result;
}

// Count the number of users in the alliance.
function CountAllyMembers ($ally_id)
{
    global $db_prefix;
    if ( $ally_id <= 0 ) return 0;
    $result = EnumerateAlly ($ally_id);
    return dbrows ($result);
}

// Change the alliance tag. Can be done once every 7 days.
function AllyChangeTag ($ally_id, $tag)
{
    global $db_prefix;
    $now = time ();
    $ally = LoadAlly ($ally_id);
    if ( $now < $ally['tag_until'] ) return false;    // It's still time.
    if ( $ally['tag'] === $tag ) return false;
    $until = $now + 7 * 24 * 60 * 60;
    $query = "UPDATE ".$db_prefix."ally SET old_tag = tag, tag = '".$tag."', tag_until = $until WHERE ally_id = $ally_id";
    dbquery ($query);
    return true;
}

// Change the name of the alliance. Can be done once every 7 days.
function AllyChangeName ($ally_id, $name)
{
    global $db_prefix;
    $now = time ();
    $ally = LoadAlly ($ally_id);
    if ( $now < $ally['name_until'] ) return false;    // It's still time.
    if ( $ally['name'] === $name ) return false;
    $until = $now + 7 * 24 * 60 * 60;
    $query = "UPDATE ".$db_prefix."ally SET old_name = name, name = '".$name."', name_until = $until WHERE ally_id = $ally_id";
    dbquery ($query);
    return true;
}

// Change the founder of the alliance
function AllyChangeOwner ($ally_id, $owner_id)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."ally SET owner_id = " . intval($owner_id);
    dbquery ($query);
}

// Alliance points recalculation (based on player points)
function RecalcAllyStats ()
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."ally ";
    $result = dbquery ( $query );
    $rows = dbrows ( $result );
    while ($rows--)
    {
        $ally = dbarray ( $result );
        $query = "SELECT SUM(score1) AS sum1, SUM(score2) AS sum2, SUM(score3) AS sum3 FROM ".$db_prefix."users WHERE ally_id = " . $ally['ally_id'];
        $res = dbquery ($query);
        if ( dbrows ($res) > 0 ) {
            $score = dbarray ( $res );
            // There should be no negative points. This can happen if someone created an alliance for an admin.
            if ($score['sum1'] < 0) $score['sum1'] = 0;
            if ($score['sum2'] < 0) $score['sum2'] = 0;
            if ($score['sum3'] < 0) $score['sum3'] = 0;
            $query = "UPDATE ".$db_prefix."ally SET score1 = '".$score['sum1']."', score2 = '".$score['sum2']."', score3 = '".$score['sum3']."' WHERE ally_id = " . $ally['ally_id'];
            dbquery ( $query );
        }
    }
}

// Recalculate the places of all alliances.
function RecalcAllyRanks ()
{
    global $db_prefix;

    // Points
    dbquery ("SET @pos := 0;");
    $query = "UPDATE ".$db_prefix."ally
              SET place1 = (SELECT @pos := @pos+1)
              ORDER BY score1 DESC";
    dbquery ($query);

    // Fleet
    dbquery ("SET @pos := 0;");
    $query = "UPDATE ".$db_prefix."ally
              SET place2 = (SELECT @pos := @pos+1)
              ORDER BY score2 DESC";
    dbquery ($query);

    // Research
    dbquery ("SET @pos := 0;");
    $query = "UPDATE ".$db_prefix."ally
              SET place3 = (SELECT @pos := @pos+1)
              ORDER BY score3 DESC";
    dbquery ($query);
}

// ****************************************************************************
// Ranks.

// Allowed characters in the rank name: [a-zA-Z0-9_-.]. Max. length - 30 characters
// The names may be the same.
// No more than 25 ranks per alliance.

// Rank Mask.
const ARANK_DISMISS = 0x001;    // Dismiss the alliance
const ARANK_KICK = 0x002;       // Kick a player
const ARANK_R_APPLY = 0x004;    // View applications
const ARANK_R_MEMBERS = 0x008;  // View member list
const ARANK_W_APPLY = 0x010;    // Edit applications
const ARANK_W_MEMBERS = 0x020;  // Alliance management
const ARANK_ONLINE = 0x040;     // View the "online" status in the member list
const ARANK_CIRCULAR = 0x080;   // Write a circular message
const ARANK_RIGHT_HAND = 0x100; // 'Right Hand' (required to transfer founder status)

// Rank entries in the database (allyranks).
// rank_id: Rank ordinal number (INT)
// ally_id: ID of the alliance to which the rank is assigned
// name: Rank name (CHAR(30))
// rights: Rights (OR mask)

// Add a rank with zero rights to an alliance. Returns the rank's ordinal number.
function AddRank ($ally_id, $name)
{
    global $db_prefix;
    if ($ally_id <= 0) return 0;
    $ally = LoadAlly ($ally_id);
    $rank = array ( $ally['nextrank'], $ally_id, $name, 0 );
    $opt = " (";
    foreach ($rank as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$rank[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$db_prefix."allyranks VALUES".$opt;
    dbquery ($query);
    $query = "UPDATE ".$db_prefix."ally SET nextrank = nextrank + 1 WHERE ally_id = $ally_id";
    dbquery ($query);
    return $ally['nextrank'];
}

// Save rights for rank.
function SetRank ($ally_id, $rank_id, $rights)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."allyranks SET rights = $rights WHERE ally_id = $ally_id AND rank_id = $rank_id";
    dbquery ($query);
}

// Delete a rank from an alliance.
function RemoveRank ($ally_id, $rank_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."allyranks WHERE ally_id = $ally_id AND rank_id = $rank_id";
    dbquery ($query);
}

// List all ranks in the alliance.
function EnumRanks ($ally_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyranks WHERE ally_id = $ally_id";
    return dbquery ($query);
}

// Assign a rank to a specific player.
function SetUserRank ($player_id, $rank)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."users SET allyrank = $rank WHERE player_id = $player_id";
    dbquery ($query);
}

// Load Rank.
function LoadRank ($ally_id, $rank_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyranks WHERE ally_id = $ally_id AND rank_id = $rank_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Load all alliance players with the specified rank
function LoadUsersWithRank ($ally_id, $rank_id )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE ally_id = $ally_id AND allyrank = $rank_id ";
    $result = dbquery ($query);
    return $result;
}

// ****************************************************************************
// Alliance applications.

// Entries of applications in the database (allyapps).
// app_id: Ordinal number of the application (INT AUTO_INCREMENT PRIMARY KEY)
// ally_id: ID of the alliance to which the application belongs
// player_id: Number of the user who sent the application 
// text: Application text (TEXT)
// date: Application date time() (INT UNSIGNED)

// Add an application to the alliance. Returns the ordinal number of the application.
function AddApplication ($ally_id, $player_id, $text)
{
    $app = array ( null, $ally_id, $player_id, $text, time() );
    $id = AddDBRow ( $app, "allyapps" );
    return $id;
}

// Delete the application.
function RemoveApplication ($app_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."allyapps WHERE app_id = $app_id";
    dbquery ($query);
}

// List all applications in the alliance.
function EnumApplications ($ally_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyapps WHERE ally_id = $ally_id";
    return dbquery ($query);
}

// Has the user already applied to the alliance? If yes - return the application ID, otherwise return 0.
function GetUserApplication ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyapps WHERE player_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) > 0 )
    {
        $app = dbarray ($result);
        return $app['app_id'];
    }
    else return 0;
}

// Load the application.
function LoadApplication ($app_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyapps WHERE app_id = $app_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

// ****************************************************************************

// Small Alliance System (Buddies). No more than 16 buddies.

// Database entries (buddy)
// buddy_id: Ordinal number of the entry in the table (INT AUTO_INCREMENT PRIMARY KEY)
// request_from: The number of the user who sent the request
// request_to: The number of the user to whom the request was sent
// text: Request text (TEXT)
// accepted: Request verified. Users are buddies.

// Returns the request ID if a request has been sent, or 0 if a buddy request has already been submitted.
function AddBuddy ($from, $to, $text)
{
    global $db_prefix;
    $text = mb_substr ($text, 0, 5000, "UTF-8");    // Limit the length of the strings
    if ($text === "") $text = "пусто";

    // Check applications awaiting confirmation.
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE ((request_from = $from AND request_to = $to) OR (request_from = $to AND request_to = $from)) AND accepted = 0";
    $result = dbquery ($query);
    if ( dbrows($result) ) return 0;

    // Are the users already buddies?
    if ( IsBuddy ($from, $to) ) return 0;

    // Add a request.
    $buddy = array( null, $from, $to, $text, 0 );
    $id = AddDBRow ( $buddy, "buddy" );
    return $id;
}

// Delete buddy request.
function RemoveBuddy ($buddy_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."buddy WHERE buddy_id = $buddy_id";
    dbquery ($query);
}

// Accept buddy request.
function AcceptBuddy ($buddy_id)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."buddy SET accepted = 1 WHERE buddy_id = $buddy_id";
    dbquery ($query);
}

// Load request.
function LoadBuddy ($buddy_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE buddy_id = $buddy_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

// List all sent player requests (your own).
function EnumOutcomeBuddy ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE request_from = $player_id AND accepted = 0";
    return dbquery ($query);
}

// List all incoming requests (other people's requests).
function EnumIncomeBuddy ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE request_to = $player_id AND accepted = 0";
    return dbquery ($query);
}

// List all of the player's buddies.
function EnumBuddy ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE (request_from = $player_id OR request_to = $player_id) AND accepted = 1";
    return dbquery ($query);
}

// Check if the players are buddies.
function IsBuddy ($player1, $player2)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE ((request_from = $player1 AND request_to = $player2) OR (request_from = $player2 AND request_to = $player1)) AND accepted = 1";
    $result = dbquery ($query);
    if ( dbrows($result)) return true;
    else return false;
}

?>