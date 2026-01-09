<?php

// Notes.

// ⚠️Important! This game feature involves a rich interaction with input from the user.
// You need to pay a lot of attention to the security of the input data (size and content checks).

// Database table entries:
// note_id: Note ordinal number (INT AUTO_INCREMENT PRIMARY KEY)
// owner_id: user ID (INT)
// subj: Subject of the note (CHAR(30))
// text: Text of the note (TEXT)
// textsize: Note text size (INT)
// prio: Priority (0: Not important (green), 1: So-so (yellow), 2: Important (red) ) (INT)
// date: Date the note was created/edited ('INT UNSIGNED')

function LoadNote ( $player_id, $note_id )
{
    global $db_prefix;    
    $query = "SELECT * FROM ".$db_prefix."notes WHERE owner_id = $player_id AND note_id = $note_id LIMIT 1";
    $result = dbquery ($query);
    return dbarray ($result);
}

function AddNote ( $player_id, $subj, $text, $prio )
{
    global $db_prefix;

    $user = LoadUser ($player_id);
    loca_add ( "notes", $user['lang'] );

    // Check the parameters.
    if ($subj === "") $subj = loca ("NOTE_NO_SUBJ");
    if ($text === "") $text = loca ("NOTE_NO_TEXT");
    $text = mb_substr ($text, 0, 5000, "UTF-8");
    $subj = mb_substr ($subj, 0, 30, "UTF-8");
    if ($prio < 0) $prio = 0;
    if ($prio > 2) $prio = 2;

    // Write a note to the database.
    $note = array( 'owner_id' => $player_id, 'subj' => $subj, 'text' => $text, 'textsize' => mb_strlen ($text, "UTF-8"), 'prio' => $prio, 'date' => time() );
    AddDBRow ( $note, "notes" );
}

function UpdateNote ( $player_id, $note_id, $subj, $text, $prio )
{
    global $db_prefix;

    // You can't touch someone else's notes
    $note = LoadNote ( $player_id, $note_id);
    if ( $note['owner_id'] != $player_id ) return;

    $user = LoadUser ($player_id);
    loca_add ( "notes", $user['lang'] );

    // Check the parameters.
    if ($subj === "") $subj = loca ("NOTE_NO_SUBJ");
    if ($text === "") $text = loca ("NOTE_NO_TEXT");
    $text = mb_substr ($text, 0, 5000, "UTF-8");
    $subj = mb_substr ($subj, 0, 30, "UTF-8");
    if ($prio < 0) $prio = 0;
    if ($prio > 2) $prio = 2;

    $query = "UPDATE ".$db_prefix."notes SET subj = '".$subj."', text = '".$text."', textsize = '".mb_strlen($text, "UTF-8")."', prio = '".$prio."', date = '".time()."' WHERE owner_id = $player_id AND note_id = $note_id";
    dbquery ($query);
}

function DelNote ( $player_id, $note_id )
{
    global $db_prefix;

    // You can't touch someone else's notes
    $note = LoadNote ( $player_id, $note_id);
    if ( $note['owner_id'] != $player_id ) return;

    $query = "DELETE FROM ".$db_prefix."notes WHERE owner_id = $player_id AND note_id = $note_id";
    dbquery ($query);
}

function EnumNotes ($player_id)
{
    global $db_prefix;

    $limit = 20;
    $user = LoadUser ($player_id);
    if ( $user['admin'] > 0 ) $limit = 150;

    $query = "SELECT * FROM ".$db_prefix."notes WHERE owner_id = $player_id ORDER BY date DESC LIMIT $limit";
    $result = dbquery ($query);
    return $result;
}

?>