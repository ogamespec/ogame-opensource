<?php

// Активация аккаунта по ссылке.

require_once "config.php";
require_once "db.php";

function method () { return $_SERVER['REQUEST_METHOD']; }

function hostname () {
    $host = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/game/validate.php" );
    return substr ( $host, 0, $pos+1 );
}

// Соединиться с базой данных
dbconnect ($db_host, $db_user, $db_pass, $db_name);
dbquery("SET NAMES 'utf8';");
dbquery("SET CHARACTER SET 'utf8';");
dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

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

function RedirectHome ()
{
    global $StartPage;
    echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage' /></head><body></body>";
}

if ( key_exists("ack", $_GET) ) ValidateUser ($_GET['ack']);
else RedirectHome ();

?>