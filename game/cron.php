<?php

// Optionally you can set up CRON on this file, for periodic queue updates.
// https://www.cloudways.com/blog/schedule-cron-jobs-in-php/  (Google: how to run php script via cron)

// This script must not be called from the browser (see .htaccess)

if ( file_exists ("config.php"))
{
    require_once "config.php";
    require_once "core/core.php";

    InitDB();

    $GlobalUni = LoadUniverse ();

    ModsInit();

    $GlobalUser = LoadUser ( USER_SPACE );

    loca_add ( "common", $GlobalUni['lang'] );
    loca_add ( "technames", $GlobalUni['lang'] );

    UpdateQueue ( time() );
}

?>