<?php

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
    exit ();
}
else {
    require_once "config.php";
}

header('Pragma:no-cache');

require_once "core/core.php";

InitDB();

$GlobalUni = LoadUniverse ();

ModsInit();

include "pages/ainfo.php";
?>