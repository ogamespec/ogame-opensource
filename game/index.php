<?php

$StartPage = "http://localhost/ogame-opensource";

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

require_once "startpage.php";
require_once "config.php";
require_once "db.php";

// Соединиться с базой данных
dbconnect ($db_host, $db_user, $db_pass, $db_name);
dbquery("SET NAMES 'utf8';");
dbquery("SET CHARACTER SET 'utf8';");
dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

// *****************************************************************************
// Вспомогательные функции.

function method () { return $_SERVER['REQUEST_METHOD']; }
function scriptname () {
    $break = explode('/', $_SERVER["SCRIPT_NAME"]);
    return $break[count($break) - 1]; 
}
function hostname () {
    $host = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
    $break = explode ('/', $host);
    $break[count($break)-1] = '';
    return implode ('/', $break); 
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

    $id = IncrementDBGlobal ( $GlobalUser['uni'], 'nexterror' );
    $now = time ();

    $error = array ( $id, $GlobalUser['uni'], $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], bb($text), $now );
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

    $id = IncrementDBGlobal ( $GlobalUser['uni'], 'nexterror' );
    $now = time ();

    $error = array ( $id, $GlobalUser['uni'], $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], "DEBUG: " . bb($message), $now );
    AddDBRow ( $error, 'errors' );
}

function RedirectHome ()
{
    global $StartPage;
    echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage' /></head><body></body>";
}

// *****************************************************************************

RedirectHome ();

?>