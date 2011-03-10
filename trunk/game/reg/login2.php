<?php

// Проверить, если файл конфигурации отсутствует - редирект на страницу установки игры.
if ( !file_exists ("../config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=../install.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

require_once "../config.php";
require_once "../db.php";

require_once "../uni.php";
require_once "../prod.php";
require_once "../planet.php";
require_once "../user.php";

// Соединиться с базой данных
dbconnect ($db_host, $db_user, $db_pass, $db_name);
dbquery("SET NAMES 'utf8';");
dbquery("SET CHARACTER SET 'utf8';");
dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

if ( $_SERVER['REQUEST_METHOD'] === "POST" ) Login ( $_POST['login'], $_POST['pass']);
else if ($_SERVER['REQUEST_METHOD'] === "GET") Login ( $_GET['login'], $_GET['pass']);

echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage' /></head><body></body></html>";

?>