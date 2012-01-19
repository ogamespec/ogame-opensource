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
require_once "../user.php";
require_once "../queue.php";
require_once "../uni.php";
require_once "../debug.php";

function method () { return $_SERVER['REQUEST_METHOD']; }

function hostname () {
    $host = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/game/reg/fa_pass.php" );
    return substr ( $host, 0, $pos+1 );
}

// Format string, according to tokens from the text. Tokens are represented as #1, #2 and so on.
function va ($subject)
{
    $num_arg = func_num_args();
    $pattern = array ();
    for ($i=1; $i<$num_arg; $i++)
    {
        $pattern[$i-1] = "/#$i/";
        $replace[$i-1] = func_get_arg($i);
    }
    return preg_replace($pattern, $replace, $subject);
}

function gen_trivial_password ($len = 8)
{
    $r = '';
    for($i=0; $i<$len; $i++)
        $r .= chr(rand(0, 25) + ord('a'));
    return $r;
}

/*
From: welcome@ogame.ru
Subj: Пароль огейм

"О xxx, приветствуем Вас!

Попасть снова в xxx-ю вселенную Вы сможете только указав пароль xxx.

Пароли высылаются исключительно на адреса, указанные в профиле аккаунта.

Если Вы не заказывали восстановление пароля, то проигнорируйте это письмо.

Удачи,

Ваша команда ОГейм."
*/

$pass_ok = false;

?>

<html> 
 <head> 
  <title>Отправление пароля OGame</title> 
<!--  <meta http-equiv="refresh" content="5; URL=http://ogame.de"> --> 
  <link rel="stylesheet" type="text/css" href="<?=hostname();?>evolution/formate.css"> 
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
  </head> 
 <body> 
 <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div> 
<center> 
<table width="519"> 
<tr> 
<?php
    if ( $pass_ok ) echo "   <th><font color=\"lime\">".va("Пароль отправлен на #1.", "xxx")."</font></th>\n";
    else echo " <th><font color=\"red\">Постоянный адрес неверен.</font></th> \n";
?>
</tr> 
</table> 
</center> 
 </body> 
</html>