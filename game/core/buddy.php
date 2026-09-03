<?php
/**
 * @file buddy.php
 * @brief Buddy list management.
 * @details Adds and removes friends on the buddy list and handles buddy requests between players.
 */
// Small Alliance System (Buddies). No more than 16 buddies.

// Database entries (buddy)
// buddy_id: Ordinal number of the entry in the table (INT AUTO_INCREMENT PRIMARY KEY)
// request_from: The number of the user who sent the request
// request_to: The number of the user to whom the request was sent
// text: Request text (TEXT)
// accepted: Request verified. Users are buddies.

/**
 * Send a buddy request, returning the request ID or 0 if the request cannot be made.
 *
 * @param int $from Player ID of the sender.
 * @param int $to Player ID of the recipient.
 * @param string $text Request text.
 * @return int Buddy request ID, or 0 if a request already exists or the players are buddies.
 */
function AddBuddy (int $from, int $to, string $text) : int
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
    $buddy = array( 'request_from' => $from, 'request_to' => $to, 'text' => $text, 'accepted' => 0 );
    $id = AddDBRow ( $buddy, "buddy" );
    return $id;
}

/**
 * Delete a buddy request.
 *
 * @param int $buddy_id Buddy request ID.
 */
function RemoveBuddy (int $buddy_id) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."buddy WHERE buddy_id = $buddy_id";
    dbquery ($query);
}

/**
 * Accept a buddy request.
 *
 * @param int $buddy_id Buddy request ID.
 */
function AcceptBuddy (int $buddy_id) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."buddy SET accepted = 1 WHERE buddy_id = $buddy_id";
    dbquery ($query);
}

/**
 * Load a buddy request by its ID.
 *
 * @param int $buddy_id Buddy request ID.
 * @return mixed Buddy request data row.
 */
function LoadBuddy (int $buddy_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE buddy_id = $buddy_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

/**
 * List all buddy requests sent by the player that are still pending.
 *
 * @param int $player_id Player ID.
 * @return mixed Database result of the sent requests.
 */
function EnumOutcomeBuddy (int $player_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE request_from = $player_id AND accepted = 0";
    return dbquery ($query);
}

/**
 * List all buddy requests received by the player that are still pending.
 *
 * @param int $player_id Player ID.
 * @return mixed Database result of the incoming requests.
 */
function EnumIncomeBuddy (int $player_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE request_to = $player_id AND accepted = 0";
    return dbquery ($query);
}

/**
 * List all of the player's accepted buddies.
 *
 * @param int $player_id Player ID.
 * @return mixed Database result of the buddies.
 */
function EnumBuddy (int $player_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE (request_from = $player_id OR request_to = $player_id) AND accepted = 1";
    return dbquery ($query);
}

/**
 * Check whether two players are buddies.
 *
 * @param int $player1 Player ID.
 * @param int $player2 Player ID.
 * @return bool True if the players are buddies.
 */
function IsBuddy (int $player1, int $player2) : bool
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE ((request_from = $player1 AND request_to = $player2) OR (request_from = $player2 AND request_to = $player1)) AND accepted = 1";
    $result = dbquery ($query);
    if ( dbrows($result)) return true;
    else return false;
}

?>