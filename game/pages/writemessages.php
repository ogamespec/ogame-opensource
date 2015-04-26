<?php

// Написать сообщение игроку.

loca_add ( "menu", $GlobalUni['lang'] );

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

    echo "<html>\n";
    echo " <head>\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/default.css' />\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/formate.css' />\n";
    echo "  <link rel=\"stylesheet\" type=\"text/css\" href=\"formate.css\" />\n";
    echo "  <link rel=\"stylesheet\" type=\"text/css\" href=\"css/combox.css\">\n";
    echo "  <meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
    echo "  <title>Вселенная $uni ОГейм</title>\n";
    echo " </head>\n\n";
    echo " <body>\n";
    echo "  <center><font size=\"3\"><b>\n";
    echo "    <br /><br />\n";
    echo "    <font color=\"#FF0000\">Произошла ошбка</font>\n";
    echo "    <br /><br />\n";
    echo "    Эта функция доступна только после активации аккаунта.    \n";
    echo "    <br /><br />\n";
    echo "    Error-ID: 123456  </b></font></center>\n\n";
    echo " </body>\n";
    echo "</html>\n\n";
}

// *******************************************************************

$user = LoadUser ( intval($_GET['messageziel']) );
$home = GetPlanet ( $user['hplanetid']);
$ownhome = GetPlanet ( $GlobalUser['hplanetid']);

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
        if ($subj === "") $write_error = "<center><font color=#FF0000>Не хватает темы</font><br/><br/></center>\n";
        else if ($text === "") $write_error .= "<center><font color=#FF0000>А где же сообщение?</font><br/><br/></center>\n";
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
            SendMessage ( $user['player_id'], $from, $subj, $text, 0);
            $write_error = "<center><font color=#00FF00>Сообщение отправлено</font><br/></center>\n";
        }
    }
}

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";
echo $write_error;
echo "<center>\n";
echo "<form action=\"index.php?page=writemessages&session=".$_GET['session']."&gesendet=1&messageziel=".intval($_GET['messageziel'])."\" method=\"post\">\n";
echo "<table width=\"519\">\n\n";

//echo "GET: "; print_r ($_GET); echo "<br>";
//echo "POST: "; print_r ($_POST); echo "<br>";

echo "<tr><td class=\"c\" colspan=\"2\">Написать сообщение</td></tr>\n";
echo "<tr><th>Получатель</th><th><input type=\"text\" name=\"to\" size=\"40\" value=\"".$user['oname']." [".$home['g'].":".$home['s'].":".$home['p']."]\" /></th></tr>\n";
echo "<tr><th>Тема</th><th><input type=\"text\" name=\"betreff\" size=\"40\" maxlength=\"40\" value=\"Без темы\" /></th></tr>\n";
echo "<tr>\n";
echo "<th> Сообщение(<span id=\"cntChars\">0</span> / 2000 символов) </th>\n";
echo "<th><textarea name=\"text\" cols=\"40\" rows=\"10\" size=\"100\" onkeyup=\"javascript:cntchar(2000)\"></textarea></th>\n";
echo "</tr>\n";
echo "<tr><th colspan=\"2\"><input type=\"submit\" value=\"Отправить\" /></th></tr> \n\n";

echo "</table></form>\n";
echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>