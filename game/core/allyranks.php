<?php
/**
 * @file allyranks.php
 * @brief Alliance ranks management.
 * @details Lists, creates and edits the rank titles available inside an alliance.
 */
// Ranks.

// Allowed characters in the rank name: [a-zA-Z0-9_-.]. Max. length - 30 characters
// The names may be the same.
// No more than 25 ranks per alliance.

// Rank entries in the database (allyranks).
// rank_id: Rank ordinal number (INT)
// ally_id: ID of the alliance to which the rank is assigned
// name: Rank name (CHAR(30))
// rights: Rights (OR mask)

/**
 * Add a rank with zero rights to an alliance and return the rank's ordinal number.
 *
 * @param int $ally_id ID of the alliance.
 * @param string $name Name of the rank.
 * @return int Ordinal number of the new rank.
 */
function AddRank (int $ally_id, string $name) : int
{
    global $db_prefix;
    if ($ally_id <= 0) return 0;
    $ally = LoadAlly ($ally_id);
    $rank = array ( 'rank_id' => $ally['nextrank'], 'ally_id' => $ally_id, 'name' => $name, 'rights' => 0 );
    AddDBRow ($rank, "allyranks");
    $query = "UPDATE ".$db_prefix."ally SET nextrank = nextrank + 1 WHERE ally_id = $ally_id";
    dbquery ($query);
    return $ally['nextrank'];
}

/**
 * Save the rights for a rank.
 *
 * @param int $ally_id ID of the alliance.
 * @param int $rank_id ID of the rank.
 * @param int $rights Rights mask to assign to the rank.
 * @return void
 */
function SetRank (int $ally_id, int $rank_id, int $rights) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."allyranks SET rights = $rights WHERE ally_id = $ally_id AND rank_id = $rank_id";
    dbquery ($query);
}

/**
 * Delete a rank from an alliance.
 *
 * @param int $ally_id ID of the alliance.
 * @param int $rank_id ID of the rank to delete.
 * @return void
 */
function RemoveRank (int $ally_id, int $rank_id) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."allyranks WHERE ally_id = $ally_id AND rank_id = $rank_id";
    dbquery ($query);
}

/**
 * List all ranks in the alliance.
 *
 * @param int $ally_id ID of the alliance.
 * @return mixed Result of the SQL query.
 */
function EnumRanks (int $ally_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyranks WHERE ally_id = $ally_id";
    return dbquery ($query);
}

/**
 * Assign a rank to a specific player.
 *
 * @param int $player_id ID of the player.
 * @param int $rank Rank ID to assign to the player.
 * @return void
 */
function SetUserRank (int $player_id, int $rank) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."users SET allyrank = $rank WHERE player_id = $player_id";
    dbquery ($query);
}

/**
 * Load a rank of an alliance.
 *
 * @param int $ally_id ID of the alliance.
 * @param int $rank_id ID of the rank to load.
 * @return mixed The rank row as an array, or null if it does not exist.
 */
function LoadRank (int $ally_id, int $rank_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyranks WHERE ally_id = $ally_id AND rank_id = $rank_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

/**
 * Load all alliance players holding the specified rank.
 *
 * @param int $ally_id ID of the alliance.
 * @param int $rank_id ID of the rank to look for.
 * @return mixed Result of the SQL query.
 */
function LoadUsersWithRank (int $ally_id, int $rank_id ) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE ally_id = $ally_id AND allyrank = $rank_id ";
    $result = dbquery ($query);
    return $result;
}

?>