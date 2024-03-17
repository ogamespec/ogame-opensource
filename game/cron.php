<?php

// Optionally you can set up CRON on this file, for periodic queue updates.

if ( file_exists ("config.php"))
{
    require_once "config.php";
    require_once "db.php";
    require_once "utils.php";

    InitDB();

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
    require_once "galaxytool.php";
    require_once "bot.php";

    $GlobalUni = LoadUniverse ();

    $GlobalUser = LoadUser ( USER_SPACE );

    loca_add ( "common", $GlobalUni['lang'] );
    loca_add ( "technames", $GlobalUni['lang'] );

    UpdateQueue ( time() );
}

?>