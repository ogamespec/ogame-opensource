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

loca_add ( "common", $loca_lang, "../" );
loca_add ( "reg", $loca_lang, "../" );

function EmailExist ( $email)
{
    global $db_prefix;
    $email = mb_strtolower ($email, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE (email = '".$email."' OR pemail = '".$email."')";
    $result = dbquery ($query);
    return dbarray ($result);
}

InitDB();

$GlobalUni = LoadUniverse ();
$uninum = $GlobalUni['num'];

$pass_ok = false;
if ( method () === "POST" ) {
    $email = $_POST['email'];
    if ( isValidEmail ($email) ) {
        $user = EmailExist ( $email );
        if ( $user ) {
            $pass = gen_trivial_password ();
            $md5 = md5 ($pass . $db_secret );
            $query = "UPDATE ".$db_prefix."users SET session = '', password = '".$md5."' WHERE player_id = " . $user['player_id'];
            dbquery ($query);
            mail_utf8 ( $user['pemail'], va(loca("REG_FORGOT_SUBJ"), loca("OGAME_LOC")),
                va ( loca("REG_FORGOT_MAIL"),
                    $user['oname'],
                    $uninum,
                    $pass,
                    hostname(),
                    loca("OGAME_LOC")
                ), "From: welcome@" . $_SERVER['SERVER_NAME'] );
            $pass_ok = true;
        }
    }
}

?>

<html>
 <head>
  <title><?=va(loca("REG_FORGOT_TITLE"), loca("OGAME_INT"));?></title>
<!--  <meta http-equiv="refresh" content="5; URL=http://<?=hostname();?>"> -->
  <link rel="stylesheet" type="text/css" href="<?=hostname();?>evolution/formate.css">
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  </head>
 <body>
 <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<center>
<table width="519">
<tr>
<?php
    if ( $pass_ok ) echo "   <th><font color=\"lime\">".va(loca("REG_FORGOT_OK"), $user['oname'])."</font></th>\n";
    else echo " <th><font color=\"red\">".loca("REG_FORGOT_ERROR")."</font></th> \n";
?>
</tr>
</table>
</center>
 </body>
</html>