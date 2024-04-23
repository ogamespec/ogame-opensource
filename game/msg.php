<?php

// Message Management.

// ⚠️Important! This game feature involves a rich interaction with input from the user.
// You need to pay a lot of attention to the security of the input data (size and content checks).

// Entry of the message into the database.
// msg_id: Message ordinal number (INT AUTO_INCREMENT PRIMARY KEY)
// owner_id: Ordinal number of the user to whom the message belongs
// pm: Message type, 0: private message (you can report a complaint to the operator), ...
// msgfrom: From whom, HTML (TEXT)
// subj: Subject, HTML, could be text, could be a link to the report (TEXT)
// text: Text of message (TEXT)
// shown: 0 is a new message, 1 is a displayed message.
// date: Date of message (INT UNSIGNED)
// planet_id: The ordinal number of the planet/moon. Used for espionage reports to display shared espionage reports in the galaxy

// Message types (pm)
// It so happened that in the early stages of development pm=1 meant that the message was private. When it came time to make filters for the Commander, it was decided not to create a new type column in the table, but to use pm.
const MTYP_PM = 0;              // private message
const MTYP_SPY_REPORT = 1;              // spy report
const MTYP_BATTLE_REPORT_LINK = 2;      // link to battle report AND missile attack
const MTYP_EXP = 3;             // expedition report
const MTYP_ALLY = 4;            // alliance
const MTYP_MISC = 5;            // miscellaneous
const MTYP_BATTLE_REPORT_TEXT = 6;      // battle report text

// A user can have a maximum of 127 messages in total. If an overflow occurs, the oldest message is deleted and a new one is added.
// The message is kept for 24 hours (7 days with the Commander)

// BB codes can be used in private messages:
// Simple: b u i s sub sup hr
// Color: [color=COLOR][/color], size [size=SIZE][/size], font [font=FONT][/font], quotation [quote=From whom][/quote]
// URL: [url=PATH][/url], Email: [email=EMAIL][/email], Picture [img=path][/img]
// Alignment: [align=left,right,center][/align]

// If there is the word {PUBLIC_SESSION} in the "from whom", subject, or message text, it is replaced with the user's current session in the output.

// Each user has a post limit per day. The error "You have written too much today" is displayed.

// Delete all old messages (called from the Messages menu)
function DeleteExpiredMessages ($player_id, $days)
{
    global $db_prefix;
    $now = time ();
    $hours = 60 * 60 * 24 * $days;

    // Не удалять сообщения администрации.
    $user = LoadUser ($player_id);
    if ($user['admin'] > 0 ) return;

    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id";
    $result = dbquery ($query);
    $num = dbrows ($result);
    while ($num--)
    {
        $msg = dbarray ($result);
        if ( ($msg['date'] + $hours) <= $now ) DeleteMessage ($player_id, $msg['msg_id']);
    }
}

// Delete the oldest message (called from SendMessage)
function DeleteOldestMessage ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id ORDER BY date ASC";
    $result = dbquery ($query);
    $msg = dbarray ($result);
    DeleteMessage ( $player_id, $msg['msg_id']);
}

// Send Message. Returns the id of a new message. (can be called from anywhere); planet_id is used for spy reports.
function SendMessage ($player_id, $from, $subj, $text, $pm, $when=0, $planet_id=0)
{
    global $db_prefix;

    if ($when == 0) $when = time ();

    // Handle parameters.
    if ($pm == 0) {
        $text = mb_substr ($text, 0, 2000, "UTF-8");
        //$text = bb ($text);
    }

    $text = addslashes($text);

    // Get the number of messages for the user.
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) >= 127 )    // Delete the oldest message and make room for a new one.
    {
        DeleteOldestMessage ($player_id);
    }

    // Add message.
    $msg = array( null, $player_id, $pm, $from, $subj, $text, 0, $when, $planet_id );
    $id = AddDBRow ( $msg, "messages" );

    return $id;
}

// Delete message (called from the Messages menu)
function DeleteMessage ($player_id, $msg_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."messages WHERE owner_id = $player_id AND msg_id = $msg_id";
    dbquery ($query);
}

// Load the last N messages (called from the Messages menu).
// Do not load the text of battle reports
function EnumMessages ($player_id, $max)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND pm <> ".MTYP_BATTLE_REPORT_TEXT." ORDER BY date DESC, msg_id DESC LIMIT $max";
    $result = dbquery ($query);
    return $result;
}

// Get the number of unread messages (called from Overview)
function UnreadMessages ($player_id, $filter=false, $pm=0)
{
    global $db_prefix;

    // Add a condition for filtering (used to show the number of unread messages in a folder)
    $filter_str = "";
    if ($filter) {
        $filter_str = "AND pm = $pm";
    }

    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND shown = 0 $filter_str";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Mark a message as read (called from the Messages menu).
function MarkMessage ($player_id, $msg_id)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."messages SET shown = 1 WHERE owner_id = $player_id AND msg_id = $msg_id";
    dbquery ($query);
}

// Load the message.
function LoadMessage ( $msg_id )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE msg_id = $msg_id";
    $result = dbquery ($query);
    if ( $result ) return dbarray ($result);
    else return NULL;
}

// Delete all messages
function DeleteAllMessages ($player_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."messages WHERE owner_id = $player_id";
    dbquery ($query);
}

// Get msg_id of the shared spy report for the specified planet. If there is no report, return 0.
function GetSharedSpyReport ($planet_id, $player_id, $ally_id)
{
    global $db_prefix;
    if ($ally_id != 0) {
        $sub_query = "SELECT player_id FROM ".$db_prefix."users WHERE ally_id = $ally_id";
        $query = "SELECT * FROM ".$db_prefix."messages WHERE pm = 1 AND planet_id = $planet_id AND owner_id IN (".$sub_query.") ORDER BY date DESC LIMIT 1";
    }
    else {
        $query = "SELECT * FROM ".$db_prefix."messages WHERE pm = 1 AND planet_id = $planet_id AND owner_id = $player_id ORDER BY date DESC LIMIT 1";
    }
    $result = dbquery ($query);
    if ( $result ) {
        $msg = dbarray ($result);
        return $msg['msg_id'];
    }
    return 0;
}

// Return the number of messages of a certain type (used to show the total number of messages in a folder)
function TotalMessages ($player_id, $pm)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND pm = $pm";
    $result = dbquery ($query);
    return dbrows ($result);
}

?>