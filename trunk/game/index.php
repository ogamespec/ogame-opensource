<?php

// Главный модуль, через который осуществляется доступ к другим страницам.
ob_start ();

// Проверить, если файл конфигурации отсутствует - редирект на страницу установки игры.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

header('Pragma:no-cache');

$GlobalUser = array ();

require_once "config.php";
require_once "db.php";

// Соединиться с базой данных
dbconnect ($db_host, $db_user, $db_pass, $db_name);
dbquery("SET NAMES 'utf8';");
dbquery("SET CHARACTER SET 'utf8';");
dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

require_once "loca_game.php";
require_once "bbcode.php";
require_once "uni.php";
require_once "prod.php";
require_once "planet.php";
require_once "user.php";
require_once "msg.php";
require_once "notes.php";
require_once "queue.php";
require_once "galaxy.php";
require_once "loca_game.php";
require_once "page.php";
require_once "battle.php";

// *****************************************************************************
// Вспомогательные функции.

function method () { return $_SERVER['REQUEST_METHOD']; }
function scriptname () {
    $break = explode('/', $_SERVER["SCRIPT_NAME"]);
    return $break[count($break) - 1]; 
}
function hostname () {
    $host = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/game/index.php" );
    return substr ( $host, 0, $pos+1 );
}

function nicenum ($number)
{
    return number_format($number,0,",",".");
}

// Ошибка, аварийное завершение программы.
function Error ($text)
{
    global $GlobalUser;
    if ( !$GlobalUser ) return;

    $id = IncrementDBGlobal ( 'nexterror' );
    $now = time ();

    $error = array ( $id, $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], bb($text), $now );
    AddDBRow ( $error, 'errors' );

    Logout ( $_GET['session'] );    // Завершить сессию.

    ob_clean ();    // Отменить предыдущий HTML.
    PageHeader ("error", true, false);

    echo "<center><font size=\"3\"><b>\n";
    echo "<br /><br />\n";
    echo "<font color=\"#FF0000\">Произошла ошибка</font>\n";
    echo "<br /><br />\n";
    echo "Аварийное завершение прогарммы.<br/><br/>Обратитесь в Службу поддержки или на форум, в раздел \"Ошибки\".\n";
    echo "<br /><br />\n";
    echo "Error-ID: $id</b></font></center>\n";

    PageFooter ();
    ob_end_flush ();
    exit ();
}

// Добавить отладочное сообщение.
function Debug ($message)
{
    global $GlobalUser;
    if ( !$GlobalUser ) return;

    $id = IncrementDBGlobal ( 'nexterror' );
    $now = time ();

    $error = array ( $id, $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], "DEBUG: " . bb($message), $now );
    AddDBRow ( $error, 'errors' );
}

function RedirectHome ()
{
    global $StartPage;
    echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage' /></head><body></body>";
}

// *****************************************************************************

// Игровые страницы.

if ( $_GET['page'] === "overview" ) { include "pages/overview.php"; exit(); }
else if ( $_GET['page'] === "imperium" ) { include "pages/imperium.php"; exit (); }
else if ( $_GET['page'] === "buildings" ) { include "pages/buildings.php"; exit (); }
else if ( $_GET['page'] === "renameplanet" ) { include "pages/renameplanet.php"; exit (); }
else if ( $_GET['page'] === "b_building" ) { include "pages/b_building.php"; exit (); }
else if ( $_GET['page'] === "resources" ) { include "pages/resources.php"; exit(); }
else if ( $_GET['page'] === "infos" ) { include "pages/infos.php"; exit (); }
else if ( $_GET['page'] === "techtree" ) { include "pages/techtree.php"; exit(); }
else if ( $_GET['page'] === "techtreedetails" ) { include "pages/techtreedetails.php"; exit(); }
else if ( $_GET['page'] === "messages" ) { include "pages/messages.php"; exit (); }
else if ( $_GET['page'] === "writemessages" ) { include "pages/writemessages.php"; exit (); }
else if ( $_GET['page'] === "notizen" ) { include "pages/notizen.php"; exit (); }
else if ( $_GET['page'] === "buddy" ) { include "pages/buddy.php"; exit (); }

RedirectHome ();

?>