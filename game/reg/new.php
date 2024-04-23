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

if ( !key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = $DefaultLanguage;
else $loca_lang = $_COOKIE['ogamelang'];

loca_add ( "reg", $loca_lang );

function isValidEmail($email){
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function gen_trivial_password ($len = 8)
{
    $r = '';
    for($i=0; $i<$len; $i++)
        $r .= chr(rand(0, 25) + ord('a'));
    return $r;
}

InitDB();

$uni = LoadUniverse ();
$uninum = $uni['num'];

$error = $agbclass = "";
if ( method() === "POST" )        // Register a player.
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time ();
    $last = GetLastRegistrationByIP ( $ip );

    $localhost = $ip === "127.0.0.1" || $ip === "::1";

    if ( !key_exists ( "agb", $_POST ) ) {
        $error = loca("REG_NEW_ERROR_AGB");
        $agbclass = "error";
    }

    else if ( ( $now - $last ) < 10 * 60 && !$localhost ) $error = loca("REG_NEW_ERROR_IP");
    else if ( mb_strlen ($_POST['character']) < 3 || mb_strlen ($_POST['character']) > 20 || preg_match ('/[;,<>()\`\"\']/', $_POST['character']) ) $error = va ( loca("REG_NEW_ERROR_CHARS"), $_POST['character'] );
    else if ( IsUserExist ( $_POST['character'])) $error = va ( loca("REG_NEW_ERROR_EXISTS"), $_POST['character'] ) ;
    else if ( !isValidEmail ($_POST['email']) ) $error = va ( loca("REG_NEW_ERROR_EMAIL"), $_POST['email'] ) ;
    else if ( IsEmailExist ( $_POST['email'])) $error = va ( loca("REG_NEW_ERROR_EMAIL_EXISTS"), $_POST['email'] );
    else if ( GetUsersCount() >= $uni['maxusers']) $error = va (loca("REG_NEW_ERROR_MAX_PLAYERS"), $uni['maxusers']);

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
<h1 style="font-size: 22;"><?=va(loca("REG_NEW_TITLE"), $uninum);?></h1>
<table width="704">
<tr>
<td class="c"><h3><font color="lime"><?=loca("REG_NEW_SUCCESS");?></font></h3></td>
</tr>
<tr>
<th style="text-align: left;">
<?php
    echo va(loca("REG_NEW_TEXT"),
         $_POST['character'], "Вселенная $uninum", $_POST['email'], $StartPage, $StartPage );
?>
</tr>
</table>
<div style="position:relative; width: 700px; height: 300px; color: #000000; text-align: left; border: 1px solid #415680;"><a href="http://ogame.de/portal"><img src="login.jpg" width="700" height="300" alt="" /></a>
	<div style="position:absolute; top:135px; left:170px; width:130px; height:16px;"><?=va(loca("REG_NEW_UNI"), $uninum);?></div>
	<div style="position:absolute; top:135px; left:345px; width:85px; height:16px;"><?=$_POST['character'];?></div>

	<div style="position:absolute; top:135px; left:435px; width:85px; height:16px;">********</div>

	<div style="position:absolute; top:155px; left:170px; width:92px; padding:4px; background-color:#FFFFCC;"><?=loca("REG_NEW_CHOOSE_UNI");?></div>
	<div style="position:absolute; top:155px; left:345px; width:76px; padding:4px; background-color:#FFFFCC;"><?=loca("REG_NEW_NAME");?></div>
	<div style="position:absolute; top:155px; left:440px; width:76px; padding:4px; background-color:#FFFFCC;"><?=loca("REG_NEW_PASSWORD");?></div>

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
            text = <?=loca("REG_NEW_MESSAGE_0");?>;
            textclass = "fine";
            break;
        case "101":
            text = <?=loca("REG_NEW_MESSAGE_101");?>;
            textclass = "warning";
            break;
        case "102":
            text = <?=loca("REG_NEW_MESSAGE_102");?>;
            textclass = "warning";
            break;
        case "103":
            text = <?=loca("REG_NEW_MESSAGE_103");?>;
            textclass = "warning";
            break;
        case "104":
            text = <?=loca("REG_NEW_MESSAGE_104");?>;
            textclass = "warning";
            break;
        case "105":
            text = <?=loca("REG_NEW_MESSAGE_105");?>;
            textclass = "fine";
            break;
        case "106":
            text = <?=loca("REG_NEW_MESSAGE_106");?>;
            textclass = "fine";
            break;
        case "107":
            text = <?=loca("REG_NEW_MESSAGE_107");?>;
            textclass = "warning";
            break;
        case "108":
            text = <?=loca("REG_NEW_MESSAGE_108");?>;
            textclass = "warning";
            break;
        case "109":
            text = <?=loca("REG_NEW_MESSAGE_109");?>;
            textclass = "warning";
            break;
        case "201":
            text = <?=loca("REG_NEW_MESSAGE_201");?>;
            break;
        case "202":
            text = <?=loca("REG_NEW_MESSAGE_202");?>;
            break;
        case "203":
            text = <?=loca("REG_NEW_MESSAGE_203");?>;
            break;
        case "204":
            text = <?=loca("REG_NEW_MESSAGE_204");?>;
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
<h1 style="font-size: 22;"><?=va(loca("REG_NEW_TITLE"), $uninum);?></h1>

<form id="registration" method="POST">
<?php
    if ( $error !== "" ) {
?>
<table width="700">
 <tr>
  <td class="c"><?=loca("REG_NEW_ERROR");?></td>
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
     <td class="c" colspan="2"><?=loca("REG_NEW_PLAYER_INFO");?></td>
    </tr>
    <tr>

     <th class=""><?=loca("REG_NEW_PLAYER_NAME");?></th><th><input name="character" size="20"  value="" onfocus="javascript:showInfo('201');javascript:pollUsername();" onblur="javascript:stopPollingUsername();" /></th>
    </tr>
    <tr>
     <th class=""><?=loca("REG_NEW_PLAYER_EMAIL");?></th><th><input name="email" size="20" value="" onfocus="javascript:showInfo('202');javascript:pollEmail();" onblur="javascript:stopPollingEmail();" /></th>
    </tr>
     <th class="<?=$agbclass;?>"><?=loca("REG_NEW_ACCEPT");?> <a href='#' target='_blank'><?=loca("REG_NEW_AGB");?></a></th><th><input type="checkbox" name="agb" onfocus="javascript:showInfo('204');"/></th>
    </tr>

        <tr>
     <th colspan="2" style="text-align: center;"><input type="submit" value="<?=loca("REG_NEW_SUBMIT");?>" /></th>
          <input type="hidden" name="v" value="3" /><input type="hidden" name="step" value="validate" />
          <input type="hidden" name="try" value="2" />
          <input type="hidden" name="kid" value="" />
     </tr>
   </table>
  </td>
  <td>

   <table width="320">
    <tr>
     <td class="c"><?=loca("REG_NEW_INFO");?></td>
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