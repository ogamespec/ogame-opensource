<?php

// Написать сообщение игроку.

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "messages", $GlobalUser['lang'] );

// Ограничение на количество символов.
$MAXCHARS = 2000;

if ( key_exists ('cp', $_GET)) SelectPlanet ( $GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

PageHeader ("writemessages");

function SendNotActivated ()
{
    global $GlobalUser;

    $unitab = LoadUniverse ();
    $uni = $unitab['num'];

    loca_add ("reg", $GlobalUser['lang']);

    // Частично повторяет метод Error из debug.php, но без отгрузки игрока.
    $text = loca("REG_NOT_ACTIVATED_MESSAGE");
    $now = time ();
    $error = array ( null, $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], $text, $now );
    $id = AddDBRow ( $error, 'errors' );

    echo "<html>\n";
    echo " <head>\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/default.css' />\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/formate.css' />\n";
    echo "  <link rel=\"stylesheet\" type=\"text/css\" href=\"formate.css\" />\n";
    echo "  <link rel=\"stylesheet\" type=\"text/css\" href=\"css/combox.css\">\n";
    echo "  <meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
    echo "  <title>".va(loca("PAGE_TITLE"), $uni)."</title>\n";
    echo " </head>\n\n";
    echo " <body>\n";
    echo "  <center><font size=\"3\"><b>\n";
    echo "    <br /><br />\n";
    echo "    <font color=\"#FF0000\">".loca("GLOBAL_ERROR")."</font>\n";
    echo "    <br /><br />\n";
    echo "    $text    \n";
    echo "    <br /><br />\n";
    echo "    Error-ID: $id  </b></font></center>\n\n";
    echo " </body>\n";
    echo "</html>\n\n";
}

// *******************************************************************

$user = LoadUser ( intval($_GET['messageziel']) );
$home = GetPlanet ( $user['hplanetid']);
$ownhome = GetPlanet ( $GlobalUser['hplanetid']);
$write_error = "";

// Обработать POST-запрос.
if ( key_exists ('gesendet', $_GET) )
{
    if ( $_GET['gesendet'] == 1)
    {
        // Проверить активацию аккаунта.
        if ( !$GlobalUser['validated'])
        {
            ob_clean ();
            SendNotActivated ();
            ob_end_flush ();
            exit ();
        }

        $subj = $_POST['betreff'];
        $text = $_POST['text'];
        if ($subj === "") $write_error = "<center><font color=#FF0000>".loca("WRITE_MSG_ERROR_NO_SUBJ")."</font><br/><br/></center>\n";
        else if ($text === "") $write_error .= "<center><font color=#FF0000>".loca("WRITE_MSG_ERROR_NO_BODY")."</font><br/><br/></center>\n";
        else
        {
            if ( $user['useskin'] ) $skin = $user['skin'];
            else $skin = hostname () . "evolution/";

            $text = str_replace ( '\"', "&quot;", bb($text) );
            $text = str_replace ( '\'', "&rsquo;", $text );
            $text = str_replace ( '\`', "&lsquo;", $text );

            $from = $GlobalUser['oname'] . " <a href=\"index.php?page=galaxy&galaxy=".$ownhome['g']."&system=".$ownhome['s']."&position=".$ownhome['p']."&session={PUBLIC_SESSION}\">[".$ownhome['g'].":".$ownhome['s'].":".$ownhome['p']."]</a>\n";
            $subj = $subj . " <a href=\"index.php?page=writemessages&session={PUBLIC_SESSION}&messageziel=".$GlobalUser['player_id']."&re=1&betreff=Re:".$subj."\">\n"
                       . "<img border=\"0\" alt=\"Ответить\" src=\"".$skin."img/m.gif\" /></a>\n";            
            SendMessage ( $user['player_id'], $from, $subj, $text, MTYP_PM);
            $write_error = "<center><font color=#00FF00>".loca("WRITE_MSG_SUCCESS")."</font><br/></center>\n";
        }
    }
}

BeginContent ();

echo $write_error;
echo "<center>\n";
echo "<form action=\"index.php?page=writemessages&session=".$_GET['session']."&gesendet=1&messageziel=".intval($_GET['messageziel'])."\" method=\"post\">\n";
echo "<table width=\"519\">\n\n";
echo "<tr><td class=\"c\" colspan=\"2\">".loca("WRITE_MSG_WRITE")."</td></tr>\n";
echo "<tr><th>".loca("WRITE_MSG_USER")."</th><th><input type=\"text\" name=\"to\" size=\"40\" value=\"".$user['oname']." [".$home['g'].":".$home['s'].":".$home['p']."]\" /></th></tr>\n";
echo "<tr><th>".loca("WRITE_MSG_SUBJ")."</th><th><input type=\"text\" name=\"betreff\" size=\"40\" maxlength=\"40\" value=\"".loca("WRITE_MSG_DEFAULT_SUBJ")."\" /></th></tr>\n";
echo "<tr>\n";
echo "<th>".va(loca("WRITE_MSG_CHAR_COUNT"), "<span id=\"cntChars\">0</span>", $MAXCHARS)."</th>\n";
echo "<th><textarea name=\"text\" cols=\"40\" rows=\"10\" size=\"100\" onkeyup=\"javascript:cntchar($MAXCHARS)\"></textarea></th>\n";
echo "</tr>\n";
echo "<tr><th colspan=\"2\"><input type=\"submit\" value=\"".loca("WRITE_MSG_SUBMIT")."\" /></th></tr> \n\n";

echo "</table></form>\n";
echo "<br><br><br><br>\n";

EndContent ();

PageFooter ();
ob_end_flush ();
?>