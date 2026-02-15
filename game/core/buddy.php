<?php

// Small Alliance System (Buddies). No more than 16 buddies.

// Database entries (buddy)
// buddy_id: Ordinal number of the entry in the table (INT AUTO_INCREMENT PRIMARY KEY)
// request_from: The number of the user who sent the request
// request_to: The number of the user to whom the request was sent
// text: Request text (TEXT)
// accepted: Request verified. Users are buddies.

// Returns the request ID if a request has been sent, or 0 if a buddy request has already been submitted.
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

// Delete buddy request.
function RemoveBuddy (int $buddy_id) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."buddy WHERE buddy_id = $buddy_id";
    dbquery ($query);
}

// Accept buddy request.
function AcceptBuddy (int $buddy_id) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."buddy SET accepted = 1 WHERE buddy_id = $buddy_id";
    dbquery ($query);
}

// Load request.
function LoadBuddy (int $buddy_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE buddy_id = $buddy_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

// List all sent player requests (your own).
function EnumOutcomeBuddy (int $player_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE request_from = $player_id AND accepted = 0";
    return dbquery ($query);
}

// List all incoming requests (other people's requests).
function EnumIncomeBuddy (int $player_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE request_to = $player_id AND accepted = 0";
    return dbquery ($query);
}

// List all of the player's buddies.
function EnumBuddy (int $player_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE (request_from = $player_id OR request_to = $player_id) AND accepted = 1";
    return dbquery ($query);
}

// Check if the players are buddies.
function IsBuddy (int $player1, int $player2) : bool
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."buddy WHERE ((request_from = $player1 AND request_to = $player2) OR (request_from = $player2 AND request_to = $player1)) AND accepted = 1";
    $result = dbquery ($query);
    if ( dbrows($result)) return true;
    else return false;
}

?>