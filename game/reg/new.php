<?php

// Проверить, если файл конфигурации отсутствует - редирект на страницу установки игры.
if ( !file_exists ("../config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=../install.php' /></head><body></body></html>";
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
require_once "../loca.php";

function method () { return $_SERVER['REQUEST_METHOD']; }

function hostname () {
    $host = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/game/reg/new.php" );
    return substr ( $host, 0, $pos+1 );
}

function isValidEmail($email){
	return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
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

// Соединиться с базой данных
dbconnect ($db_host, $db_user, $db_pass, $db_name);
dbquery("SET NAMES 'utf8';");
dbquery("SET CHARACTER SET 'utf8';");
dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

$uni = LoadUniverse ();
$uninum = $uni['num'];

$error = $agbclass = "";
if ( method() === "POST" )        // Зарегистрировать игрока.
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time ();
    $last = GetLastRegistrationByIP ( $ip );

    if ( !key_exists ( "agb", $_POST ) ) {
        $error = "Для того, чтобы начать игру Вы должны принять Основные Положения!";
        $agbclass = "error";
    }

    else if ( ( $now - $last ) < 10 * 60 && $ip !== "127.0.0.1" ) $error = "Регистрация с одного айпи не чаще одного раза за 10 минут!";
    else if ( mb_strlen ($_POST['character']) < 3 || mb_strlen ($_POST['character']) > 20 || preg_match ('/[;,<>()\`\"\']/', $_POST['character']) ) $error = va ( "Имя #1 содержит недопустимые символы или слишком мало/много символов!", $_POST['character'] );
    else if ( IsUserExist ( $_POST['character'])) $error = va ( "Имя #1 уже существует", $_POST['character'] ) ;
    else if ( !isValidEmail ($_POST['email']) ) $error = va ( "Адрес #1 недействителен!", $_POST['email'] ) ;
    else if ( IsEmailExist ( $_POST['email'])) $error = va ( "Адрес #1 уже существует!", $_POST['email'] );

    if ( $error === "" )
    {
        $password = gen_trivial_password ();
        CreateUser ( $_POST['character'], $password, $_POST['email'] );

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="<?=hostname();?>evolution/formate.css">
<link rel="stylesheet" type="text/css" href="<?=hostname();?>game/css/registration.css" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>
<body >
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<center>
<h1 style="font-size: 22;">Огейм Вселенная <?=$uninum;?> Регистрация</h1>
<table width="704">
<tr>
<td class="c"><h3><font color="lime">Регистрация прошла удачно!</font></h3></td>
</tr>
<tr>
<th style="text-align: left;">
<?php
    echo va("Поздравляем, <span class='fine'>#1</span>!<br /><br />Вы удачно прошли регистрацию в Огейм (<span class='fine'>#2</span>). <br />\n".
            "Скоро Вы получите на адрес <span class='fine'>#3</span> письмо с паролем и некоторыми важными ссылками.<br />\n".
            "Для того, чтобы играть, Вы должны войти через <a href='".$StartPage."'>главную страницу</a>.<br />\n".
            "На последующей картинке Вы увидите, как это правильно сделать.<br /><br />\n" .
            "<center><a href='#4' style='text-decoration: underline;font-size: large;'>Вперёд!</a></center><br /><br /> \n" .
            "Удачи<br /> \n" .
            "Ваша команда ОГейм</th>", 
         $_POST['character'], "Вселенная $uninum", $_POST['email'], $StartPage );
?>
</tr>
</table>
<div style="position:relative; width: 700px; height: 300px; color: #000000; text-align: left; border: 1px solid #415680;"><a href="http://ogame.de/portal"><img src="login.jpg" width="700" height="300" alt="" /></a>
	<div style="position:absolute; top:135px; left:170px; width:130px; height:16px;">Вселенная <?=$uninum;?></div>
	<div style="position:absolute; top:135px; left:345px; width:85px; height:16px;"><?=$_POST['character'];?></div>

	<div style="position:absolute; top:135px; left:435px; width:85px; height:16px;">********</div>

	<div style="position:absolute; top:155px; left:170px; width:92px; padding:4px; background-color:#FFFFCC;">Выберите вселенную</div>
	<div style="position:absolute; top:155px; left:345px; width:76px; padding:4px; background-color:#FFFFCC;">Введите имя</div>
	<div style="position:absolute; top:155px; left:440px; width:76px; padding:4px; background-color:#FFFFCC;">И присланный пароль!</div>

</div>
</center>
</body>
</html>
<?php
        die ();
    }
}

?>

<html>
<head>
<link rel="stylesheet" type="text/css" href="<?=hostname();?>evolution/formate.css">
<link rel="stylesheet" type="text/css" href="<?=hostname();?>game/css/registration.css" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<script language="JavaScript" src="<?=hostname();?>game/js/tw-sack.js"></script>
<script language="JavaScript" src="<?=hostname();?>game/js/registration.js"></script>
<script language="JavaScript">
function printMessage(code, div) {
    var textclass = "";

    if (div == null) {
        div = "statustext";
    }
    switch (code) {
        case "0":
            text = "OK";
            textclass = "fine";
            break;
        case "101":
            text = "Такое имя уже существует!";
            textclass = "warning";
            break;
        case "102":
            text = "Этот адрес уже используется!";
            textclass = "warning";
            break;
        case "103":
            text = "Имя должно содержать от 3 до 20 символов!";
            textclass = "warning";
            break;
        case "104":
            text = "Адрес недействителен!";
            textclass = "warning";
            break;
        case "105":
            text = "Имя игрока - в порядке";
            textclass = "fine";
            break;
        case "106":
            text = "Адрес - а порядке";
            textclass = "fine";
            break;
        case "107":
            text = "Адрес недействителен!";
            textclass = "warning";
            break;
        case "108":
            text = "Регистрация с одного айпи не чаще одного раза за 10 минут!";
            textclass = "warning";
            break;
        case "201":
            text = "Имя в игре: <br />Это имя Вашего персонажа в игре. В одной вселенной не может быть двух одинаковых имён.";
            break;
        case "202":
            text = "Электронный адрес: <br />На этот адрес будет выслан Ваш пароль. Если Вы введёте чужой или недйствительный адрес, то играть  Вы, соответственно, не сможете.";
            break;
        case "203":
            text = "";
            break;
        case "204":
            text = "Для того, чтобы начать игру Вы должны согласиться с Основными Положениями.";
            break;
        default:
            return;
            break;
    }

    if (textclass != "") {
        text = "<span class='" + textclass + "'>" + text + "</span>";
    }
    document.getElementById(div).innerHTML = text;
}
</script>
</head>
<body>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<center>
<h1 style="font-size: 22;">Огейм Вселенная <?=$uninum;?> Регистрация</h1>

<form id="registration" method="POST">
<?php
    if ( $error !== "" ) {
?>
<table width="700">
 <tr>
  <td class="c">Ошибка</td>
 </tr>
 <tr>
  <th class="warning"><?=$error;?></th>
 </tr>
</table>
<?php
    }
?>
<table width="700">
 <tr>
  <td>
   <table width="380">
    <tr>
     <td class="c" colspan="2">Данные об игроке</td>
    </tr>
    <tr>

     <th class="">Имя в игре</th><th><input name="character" size="20"  value="" onfocus="javascript:showInfo('201');javascript:pollUsername();" onblur="javascript:stopPollingUsername();" /></th>
    </tr>
    <tr>
     <th class="">Электронный адрес</th><th><input name="email" size="20" value="" onfocus="javascript:showInfo('202');javascript:pollEmail();" onblur="javascript:stopPollingEmail();" /></th>
    </tr>
     <th class="<?=$agbclass;?>">Я соглашаюсь с <a href='#' target='_blank'>Основными Положениями</a></th><th><input type="checkbox" name="agb" onfocus="javascript:showInfo('204');"/></th>
    </tr>

        <tr>
     <th colspan="2" style="text-align: center;"><input type="submit" value="Зарегистрироваться" /></th>
          <input type="hidden" name="v" value="3" /><input type="hidden" name="step" value="validate" />
          <input type="hidden" name="try" value="2" />
          <input type="hidden" name="kid" value="" />
     </tr>
   </table>
  </td>
  <td>

   <table width="320">
    <tr>
     <td class="c">Инфо</td>
    </tr>
    <tr style="height: 93;">
     <th><p /><div id="infotext"></div><p /><div id="statustext"></div><div id="debug"></div> </th>
    </tr>
   </table>

  </td>
 </tr>
</table>
</form>
</center>
</body>
</html>
