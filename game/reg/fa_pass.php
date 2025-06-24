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
require_once "../debug.php";
require_once "../loca.php";

if ( !key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = $DefaultLanguage;
else $loca_lang = $_COOKIE['ogamelang'];

loca_add ( "reg", $loca_lang );

function gen_trivial_password ()
{
    $pass = "";
    $syllables = "er,in,tia,wol,fe,pre,vet,jo,nes,al,len,son,cha,ir,ler,bo,ok,tio,nar,sim,ple,bla,ten,toe,cho,co,lat,spe,ak,er,po,co,lor,pen,cil,li,ght,wh,at,the,he,ck,is,mam,bo,no,fi,ve,any,way,pol,iti,cs,ra,dio,sou,rce,sea,rch,pa,per,com,bo,sp,eak,st,fi,rst,gr,oup,boy,ea,gle,tr,ail,bi,ble,brb,pri,dee,kay,en,be,se";

    $syllable_array = explode (",", $syllables);
    srand ((double)microtime()*1000000);
    for ($count=1; $count<=4; $count++) {
        if (rand()%10 == 1) $pass .= sprintf ("%0.0f", (rand()%50)+1);
        else $pass .= sprintf ("%s", $syllable_array[rand()%62]);
    }
    return $pass;
}

function isValidEmail($email){
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function EmailExist ( $email)
{
    global $db_prefix;
    $email = mb_strtolower ($email, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE (email = '".$email."' OR pemail = '".$email."')";
    $result = dbquery ($query);
    return dbarray ($result);
}

InitDB();

$uni = LoadUniverse ();
$uninum = $uni['num'];

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
            mail_utf8 ( $user['pemail'], loca("REG_FORGOT_SUBJ"),
                va ( loca("REG_FORGOT_MAIL"),
                    $user['oname'],
                    $uninum,
                    $pass,
                    "" . hostname()
                ), "From: welcome@" . $_SERVER['SERVER_NAME'] );
            $pass_ok = true;
        }
    }
}

?>

<html>
 <head>
  <title><?=loca("REG_FORGOT_TITLE");?></title>
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