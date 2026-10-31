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

// The feed delivers the messages that arrived since the previous fetch.
// $user['lastfeed'] is the previous boundary. On a poll that is at least
// feedage minutes after the last one the boundary advances to now, so the
// query below returns exactly the delta since the previous boundary. A
// reader that polls more often just sees the same window again (harmless in
// RSS, whose readers deduplicate by id).

$now = time ();
$since = intval ($user['lastfeed']);
if ( $since == 0 ) {
    // First fetch ever: deliver the whole current inbox.
    $user['lastfeed'] = $now;
    $query = "UPDATE ".$db_prefix."users SET lastfeed = $now WHERE player_id = $player_id";
    dbquery ($query);
    $since = 0;
}
else if ( $now >= $since + $uni['feedage'] * 60 ) {
    // Enough time has passed since the last delivered boundary: advance it.
    $user['lastfeed'] = $now;
    $query = "UPDATE ".$db_prefix."users SET lastfeed = $now WHERE player_id = $player_id";
    dbquery ($query);
}
// Timestamp reported as the feed refresh time (channel-level date fields).
$lastfeed = $now;

// Load all user messages not older than the previous boundary and no more
// than $MAXMSG pieces (newest first).

$MAXMSG = 50;

$query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND date >= $since AND date <= $now AND pm <> ".MTYP_BATTLE_REPORT_TEXT." ORDER BY date DESC, msg_id DESC LIMIT $MAXMSG";
$result = dbquery ($query);
//print_r ($result);

	header ("Content-Type: application/xml; charset=UTF-8");

	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

	// Atom Format

	if (($user['flags'] & USER_FLAG_FEED_ATOM) != 0) {
?>
<feed xmlns="http://www.w3.org/2005/Atom">
	<title>OGame-Nachrichten von <?=htmlspecialchars($user['oname']);?></title>
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
		echo "		<title>". htmlspecialchars(preg_replace('/<a[^>]*>(.*?)<\/a>/is', '$1', $msg['subj']), ENT_QUOTES) ."</title>\n";
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
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title>OGame-Nachrichten von <?=htmlspecialchars($user['oname']);?></title>
		<link><?=hostname("feed");?>feed/show.php?feedid=<?=$feedid;?></link>
		<atom:link href="<?=hostname("feed");?>feed/show.php?feedid=<?=$feedid;?>" rel="self" type="application/rss+xml" />
		<description>Kampfberichte, Spionagereports und Systemmeldungen des OGame-Accounts von <?=htmlspecialchars($user['oname']);?></description>
		<language>de-de</language>
		<pubDate><?=date('D, d M Y H:i:s O', $lastfeed);?></pubDate>
<?php
	$num = dbrows ($result);
	while ($num--) {
		$msg = dbarray ($result);

		echo "		<item>\n";
		echo "			<title>". htmlspecialchars(preg_replace('/<a[^>]*>(.*?)<\/a>/is', '$1', $msg['subj']), ENT_QUOTES) ."</title>\n";
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