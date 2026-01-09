<?php

// Account activation via link.

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
    exit ();
}
else {
    require_once "config.php";
}

require_once "core/core.php";

InitDB();

if ( key_exists("ack", $_GET) ) ValidateUser ($_GET['ack']);
else RedirectHome ();

?>