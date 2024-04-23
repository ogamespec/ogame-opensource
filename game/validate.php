<?php

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

// Account activation via link.

require_once "config.php";
require_once "db.php";
require_once "utils.php";

InitDB();

require_once "id.php";
require_once "loca.php";
require_once "bbcode.php";
require_once "uni.php";
require_once "prod.php";
require_once "planet.php";
require_once "user.php";
require_once "msg.php";
require_once "notes.php";
require_once "queue.php";
require_once "page.php";
require_once "ally.php";
require_once "unit.php";
require_once "fleet.php";
require_once "battle.php";
require_once "debug.php";

if ( key_exists("ack", $_GET) ) ValidateUser ($_GET['ack']);
else RedirectHome ();

?>