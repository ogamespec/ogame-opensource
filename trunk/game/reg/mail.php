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

require_once "../bbcode.php";
require_once "../msg.php";
require_once "../prod.php";
require_once "../planet.php";
require_once "../bot.php";
require_once "../user.php";
require_once "../queue.php";
require_once "../uni.php";
require_once "../debug.php";

function method () { return $_SERVER['REQUEST_METHOD']; }

function hostname () {
    $host = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/game/reg/mail.php" );
    return substr ( $host, 0, $pos+1 );
}

?>

<html> 
<head> 
<title>Восстановление пароля Огейм</title> 
<link rel="stylesheet" type="text/css" href="<?=hostname();?>evolution/formate.css"> 
  <link rel='stylesheet' type='text/css' href='/game/css/default.css' /> 
  <link rel='stylesheet' type='text/css' href='/game/css/formate.css' /> 
<meta http-equiv="content" type="text/html; charset=UTF-8" /> 
</head> 
<body> 
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div> 
<div class="mybody"> 
<form action="fa_pass.php" method="post"> 
<div align="center"> 
  <h2>Послать пароль</h2> 
  Введите действительный в игре адрес.<table align="center"> 
<tr> 
        <td>E-Mail:</td> 
        <td><input type="text" name="email"></td> 
</tr> 
<tr> 
        <td></td> 
        <td><input type="submit" name="send_pass" value="Отправить данные"></td> 
</tr> 
</table> 
</form> 
</body> 
</html>
