<?php

/** @var string $db_prefix */

// Check if the configuration file is missing - exit
if ( !file_exists ("../config.php"))
{
	exit ("Game not installed");
}
else {
	require_once "../config.php";
}

require_once "../core/core.php";

InitDB();

//print_r ($_REQUEST);

if (!key_exists('feedid', $_REQUEST)) {
	exit("No feed specified");
}
$feedid = $_REQUEST['feedid'];

if (!key_exists('mid', $_REQUEST)) {
	exit("No message specified");
}
$msg_id = $_REQUEST['mid'];

$result = CheckParams ($_REQUEST);
if (!$result['success']) {
    exit ("Error validating request parameters. Too smart users will be sent to the admin for a proctological examination.");
}

// Check Universe settings

$uni = LoadUniverse();
if ($uni['feedage'] < 0) {
	exit();
}

// Find user with specified feedid

$query = "SELECT * FROM ".$db_prefix."users WHERE feedid = '".$feedid."' LIMIT 1";
$result = dbquery ($query);
if (dbrows($result) == 0) {
	exit("Authentifizierung fehlgeschlagen");
}
$user = dbarray ($result);
//print_r ($user);
if (($user['flags'] & USER_FLAG_FEED_ENABLE) == 0) {
	exit("Authentifizierung fehlgeschlagen");
}
$player_id = $user['player_id'];

// Load the message and check that it is not later than the conditions for displaying Feed. Also check that the user is the owner of the message.

$query = "SELECT * FROM ".$db_prefix."messages WHERE msg_id = $msg_id";
$result = dbquery ($query);
if ( !$result ) {
	exit("No message");
}
$msg = dbarray ($result);
//print_r ($msg);
if ( $user['lastfeed'] != 0 && $msg['date'] > $user['lastfeed'] ) {
	exit("The message cannot be viewed yet");
}
if ( $msg['owner_id'] != $player_id ) {
	exit();
}

$subj = preg_replace('/<a[^>]*>(.*?)<\/a>/is', '$1', $msg['subj']);
$text = preg_replace('/<a[^>]*>(.*?)<\/a>/is', '$1', $msg['text']);

?>
<html><head><title><?=$subj;?></title></head><body><h1><?=$subj;?></h1><p><?=$text;?></p><body></html>