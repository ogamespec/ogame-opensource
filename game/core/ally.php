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
function CreateAlly (int $owner_id, string $tag, string $name) : int
{
    global $db_prefix;
    $tag = mb_substr ($tag, 0, 8, "UTF-8");    // Limit the length of strings
    $name = mb_substr ($name, 0, 30, "UTF-8");

    // Texts and rank names default to the language of the player who creates the alliance
    $user = LoadUser ($owner_id);
    loca_add ( "ally", $user['lang'] );

    // Add alliance.
    $ally = array( 'tag' => $tag, 'name' => $name, 'owner_id' => $owner_id, 'homepage' => "", 'imglogo' => "", 'open' => 1, 'insertapp' => 0, 'exttext' => loca_lang("ALLY_NEW_DEFAULT_TEXT", $user['lang']), 'inttext' => "", 'apptext' => "", 'nextrank' => 0, 'old_tag' => "", 'old_name' => "", 'tag_until' => 0, 'name_until' => 0,
                    'score1' => 0, 'score2' => 0, 'score3' => 0, 'place1' => 0, 'place2' => 0, 'place3' => 0,
                    'oldscore1' => 0, 'oldscore2' => 0, 'oldscore3' => 0, 'oldplace1' => 0, 'oldplace2' => 0, 'oldplace3' => 0, 'scoredate' => 0 );
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
function DismissAlly (int $ally_id) : void
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
function EnumerateAlly (int $ally_id, int $sort_by=0, int $order=0, bool $use_sort=false) : mixed
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
function IsAllyTagExist (string $tag) : bool
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."ally WHERE tag = '".$tag."'";
    $result = dbquery ($query);
    if (dbrows ($result)) return true;
    else return false;
}

// Load alliance.
function LoadAlly (int $ally_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."ally WHERE ally_id = $ally_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Search for alliances by tag. Returns the result of the SQL query.
function SearchAllyTag (string $tag) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."ally WHERE tag LIKE '%".$tag."%' LIMIT 30";
    $result = dbquery ($query);
    return $result;
}

// Count the number of users in the alliance.
function CountAllyMembers (int $ally_id) : int
{
    global $db_prefix;
    if ( $ally_id <= 0 ) return 0;
    $result = EnumerateAlly ($ally_id);
    return dbrows ($result);
}

// Change the alliance tag. Can be done once every 7 days.
function AllyChangeTag (int $ally_id, string $tag) : bool
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
function AllyChangeName (int $ally_id, string $name) : bool
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
function AllyChangeOwner (int $ally_id, int $owner_id) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."ally SET owner_id = " . intval($owner_id);
    dbquery ($query);
}

// Alliance points recalculation (based on player points)
function RecalcAllyStats () : void
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
            $query = "UPDATE ".$db_prefix."ally SET score1 = ".$score['sum1'].", score2 = ".$score['sum2'].", score3 = ".$score['sum3']." WHERE ally_id = " . $ally['ally_id'];
            dbquery ( $query );
        }
    }
}

// Recalculate the places of all alliances.
function RecalcAllyRanks () : void
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

?>