<?php

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("../config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=../install.php' /></head><body></body></html>";
    exit ();
}
else {
    require_once "../config.php";
}

require_once "../core/core.php";

if ( !key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = $DefaultLanguage;
else $loca_lang = $_COOKIE['ogamelang'];

loca_add ( "reg", $loca_lang );

?>

<html>
<head>
<title><?=loca("REG_MAIL_TITLE");?></title>
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
  <h2><?=loca("REG_MAIL_SEND");?></h2>
  <?=loca("REG_MAIL_NOTE");?><table align="center">
<tr>
        <td><?=loca("REG_MAIL_EMAIL");?></td>
        <td><input type="text" name="email"></td>
</tr>
<tr>
        <td></td>
        <td><input type="submit" name="send_pass" value="<?=loca("REG_MAIL_SUBMIT");?>"></td>
</tr>
</table>
</form>
</body>
</html>