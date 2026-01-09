<?php

/** @var string $db_prefix */

// Check if the configuration file is missing - exit
if ( !file_exists ("../game/config.php"))
{
	exit ("Game not installed");
}
else {
	require_once "../game/config.php";
}

require_once "../game/db.php";
require_once "../game/utils.php";
require_once "../game/uni.php";
require_once "../game/msg.php";
require_once "../game/user.php";

InitDB();

//print_r ($_REQUEST);

if (!key_exists('feedid', $_REQUEST)) {
	exit("No feed specified");
}
$feedid = $_REQUEST['feedid'];

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

// If it's time to update - update the user's timestamp

$now = time ();
if ($now >= $user['lastfeed'] + $uni['feedage'] * 60) {
	$user['lastfeed'] = $now;
	$query = "UPDATE ".$db_prefix."users SET lastfeed = $now WHERE player_id = $player_id";
	dbquery ($query);	
}
$lastfeed = $user['lastfeed'];

// Load all user messages not older than timestamp and no more than $MAXMSG pieces

$MAXMSG = 50;

$query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND date < $lastfeed AND pm <> ".MTYP_BATTLE_REPORT_TEXT." ORDER BY date DESC, msg_id DESC LIMIT $MAXMSG";
$result = dbquery ($query);
//print_r ($result);

	// Atom Format

	if (($user['flags'] & USER_FLAG_FEED_ATOM) != 0) {
?>
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>OGame-Nachrichten von <?=$user['oname'];?></title>
	<link href="<?=hostname("feed");?>feed/show.php?feedid=<?=$feedid;?>" rel="self" type="application/rss+xml" />
	<updated><?=date('c', $lastfeed);?></updated>
	<author>
		<name>OGame Feed Commander</name>
	</author>
	<id><?=hostname("feed");?>feed/show.php?feedid=<?=$feedid;?></id>
<?php
	$num = dbrows ($result);
	while ($num--) {
		$msg = dbarray ($result);

		echo "	<entry>\n";
		echo "		<title>". preg_replace('/<a[^>]*>(.*?)<\/a>/is', '$1', $msg['subj']) ."</title>\n";
		echo "		<link href=\"".hostname("feed")."feed/viewitem.php?mid=".$msg['msg_id']."&amp;feedid=$feedid&amp;type=i\"/>\n";
		echo "		<id>".hostname("feed")."feed/viewitem.php?mid=".$msg['msg_id']."&amp;feedid=$feedid&amp;type=i</id>\n";
		echo "		<updated>".date('c', $msg['date'])."</updated>\n";
		echo "		<content type=\"html\">\n";
		echo "			<![CDATA[\n";
		echo "				". preg_replace('/<a[^>]*>(.*?)<\/a>/is', '$1', $msg['text']) ."\n";
		echo "			]]>\n";
		echo "		</content>\n";
		echo "	</entry>\n";
	}
?>
</feed>
<?php
	}

	// RSS Format

	else {
?>
<?xml version="1.0" encoding="UTF-8"?> 
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title>OGame-Nachrichten von <?=$user['oname'];?></title>
		<link><?=hostname("feed");?>feed/show.php?feedid=<?=$feedid;?></link>
		<atom:link href="<?=hostname("feed");?>feed/show.php?feedid=<?=$feedid;?>" rel="self" type="application/rss+xml" />
		<description>Kampfberichte, Spionagereports und Systemmeldungen des OGame-Accounts von <?=$user['oname'];?></description>
		<language>de-de</language>
		<pubDate><?=date('D, d M Y H:i:s O', $lastfeed);?></pubDate>
<?php
	$num = dbrows ($result);
	while ($num--) {
		$msg = dbarray ($result);

		echo "		<item>\n";
		echo "			<title>". preg_replace('/<a[^>]*>(.*?)<\/a>/is', '$1', $msg['subj']) ."</title>\n";
		echo "			<description>\n";
		echo "				<![CDATA[\n";
		echo "					". preg_replace('/<a[^>]*>(.*?)<\/a>/is', '$1', $msg['text']) ."\n";
		echo "				]]>\n";
		echo "			</description>\n";
		echo "			<link>".hostname("feed")."feed/viewitem.php?mid=".$msg['msg_id']."&amp;feedid=$feedid&amp;type=i</link>\n";
		echo "			<author>feedcommander.noreply@".$_SERVER['SERVER_NAME']." (OGame Feed Commander)</author>\n";
		echo "			<guid isPermaLink=\"false\">".$msg['msg_id'].".$feedid.".$msg['date'].".i</guid>\n";
		echo "			<pubDate>".date('D, d M Y H:i:s O', $msg['date'])."</pubDate>\n";
		echo "		</item>\n";
	}
?>
	</channel>
</rss>
<?php
	}
?>