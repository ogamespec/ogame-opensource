<?php

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("../config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=../install.php' /></head><body></body></html>";
    exit ();
}

require_once "../config.php";
require_once "../db.php";
require_once "../utils.php";

require_once "../id.php";
require_once "../bbcode.php";
require_once "../msg.php";
require_once "../prod.php";
require_once "../planet.php";
require_once "../bot.php";
require_once "../user.php";
require_once "../queue.php";
require_once "../uni.php";
require_once "../mods.php";
require_once "../debug.php";
require_once "../loca.php";

InitDB();

// Verify registration information.

function isValidEmail($email){
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

$uni = LoadUniverse ();

if ( $_SERVER['REQUEST_METHOD'] === "POST" )
{
    if ( $_POST['agb'] !== 'on' ) $AGB = 0;
    else $AGB = 1;

    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time ();
    $last = GetLastRegistrationByIP ( $ip );

    $localhost = $ip === "127.0.0.1" || $ip === "::1";

    if ( ( $now - $last ) < 10 * 60 && !$localhost ) $RegError = 108;
    else if ( strlen ($_POST['password']) < 8 ) $RegError = 107;
    else if ( mb_strlen ($_POST['character']) < 3 || mb_strlen ($_POST['character']) > 20 || preg_match ('/[;,<>()\`\"\']/', $_POST['character']) ) $RegError = 103;
    else if ( IsUserExist ( $_POST['character'])) $RegError = 101;
    else if ( !isValidEmail ($_POST['email']) ) $RegError = 104;
    else if ( IsEmailExist ( $_POST['email'])) $RegError = 102;
    else if ( GetUsersCount() >= $uni['maxusers']) $RegError = 109;
    else $RegError = 0;

    // If all parameters are correct - create a new user and log in to the game.
    if ($RegError == 0 && $AGB)
    {
        CreateUser ( $_POST['character'], $_POST['password'], $_POST['email'] );
        Login ( $_POST['character'], $_POST['password'] );
        exit ();
    }

    echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage/register.php?errorCode=$RegError&agb=$AGB&character=".$_POST['character']."&email=".$_POST['email']."&universe=".$_POST['universe']."' /></head><body></body></html>";
    exit ();
}

// Open new.php
echo "<html><head><meta http-equiv='refresh' content='0;url=new.php' /></head><body></body></html>";

?>