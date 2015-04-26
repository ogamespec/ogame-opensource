<?php

// Сообщения (пока вариант без Командира).

loca_add ( "menu", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ( $GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

PageHeader ("messages");

// *******************************************************************

$MAXMSG = 25;        // Количество сообщений на странице.
$uni = LoadUniverse ();

DeleteExpiredMessages ( $GlobalUser['player_id'] );    // Удалить сообщения которые хранятся дольше 24 часов.

// Заголовок таблицы
echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

//echo "GET: "; print_r ($_GET); echo "<br>";
//echo "POST: "; print_r ($_POST); echo "<br>";

if ( method() === "POST" )
{
    //print_r ($_POST);

    $player_id = $GlobalUser['player_id'];

    if ( $_POST['deletemessages'] === "deleteall" ) DeleteAllMessages ( $player_id );    // Удалить все сообщения
    else
    {
        $result = EnumMessages ( $GlobalUser['player_id'], $MAXMSG);
        $num = dbrows ($result);
        while ($num--)
        {
            $msg = dbarray ($result);
            $msg_id = $msg['msg_id'];
            if ( $_POST["sneak" . $msg_id] === "on" ) {}    // Сообщить оператору
            if ( $_POST["delmes" . $msg_id] === "on" && $_POST['deletemessages'] === "deletemarked" ) DeleteMessage ( $player_id, $msg_id );    // Удалить выделенные
            if ( $_POST["delmes" . $msg_id] !== "on" && $_POST['deletemessages'] === "deletenonmarked" ) DeleteMessage ( $player_id, $msg_id );    // Удалить невыделенные
            if ( $_POST['deletemessages'] === "deleteallshown" ) DeleteMessage ( $player_id, $msg_id );    // Удалить показанные
        }
    }
}

echo "<table class='header'><tr class='header'><td><table width=\"519\">\n";
echo "<form action=\"index.php?page=messages&dsp=1&session=".$_GET['session']."\" method=\"POST\">\n";
echo "<tr><td colspan=\"4\" class=\"c\">Сообщения</td></tr>\n";
echo "<tr><th>Действие</th><th>Дата</th><th>От</th><th>Тема</th></tr>\n";

$result = EnumMessages ( $GlobalUser['player_id'], $MAXMSG);
$num = dbrows ($result);
while ($num--)
{
    $msg = dbarray ($result);
    $pm = $msg['pm'];
    if ($pm == 6) continue;    // Пропускать тексты боевых докладов.
    $msg['msgfrom'] = str_replace ( "{PUBLIC_SESSION}", $_GET['session'], $msg['msgfrom']);
    $msg['subj'] = str_replace ( "{PUBLIC_SESSION}", $_GET['session'], $msg['subj']);
    $msg['text'] = str_replace ( "{PUBLIC_SESSION}", $_GET['session'], $msg['text']);
    echo "<tr><th><input type=\"checkbox\" name=\"delmes".$msg['msg_id']."\"/></th><th>".date ("m-d H:i:s", $msg['date'])."</th><th>".$msg['msgfrom']." </th><th>".$msg['subj']." </th></tr>\n";
    echo "<tr><td class=\"b\"> </td><td class=\"b\" colspan=\"3\">".$msg['text']."</td></tr>\n";
    if ($pm == 0) echo "<tr><th colspan=\"4\"><input type=\"checkbox\" name=\"sneak".$msg['msg_id']."\"/><input type=\"submit\" value=\"Сообщить оператору\"/></th></tr>\n";
    MarkMessage ( $msg['owner_id'], $msg['msg_id'] );
}

// Низ таблицы  
echo "<tr><th colspan=\"4\" style='padding:0px 105px;'></th></tr>\n";
echo "<tr><th colspan=\"4\"><input type=\"checkbox\" name=\"fullreports\"  /> Разведданные показывать частично </th></tr>\n";
echo "<tr><th colspan=\"4\">\n";
echo "<select name=\"deletemessages\">\n";
echo "<option value=\"deletemarked\">Удалить выделенные сообщения</option> \n";
echo "<option value=\"deletenonmarked\">Удалить все невыделенные сообщения</option>\n";
echo "<option value=\"deleteallshown\">Удалить все показанные сообщения </option>\n";
echo "<option value=\"deleteall\">Удалить все сообщения</option> \n";
echo "</select><input type=\"submit\" value=\"ok\" /></th></tr>\n";
echo "<tr><td colspan=\"4\"><center>     </center></td></tr>\n";
echo "<input type=\"hidden\" name=\"messages\" value=\"1\" />\n";
echo "</form>\n";
echo "<tr><td class=\"c\" colspan=\"4\">Операторы</td></tr>\n";

    $result = EnumOperators ();
    $rows = dbrows ($result);
    while ($rows--)
    {
        $oper = dbarray ($result);
?>
                <tr>
            <th colspan="4" valign="left">
            <?=$oper['oname'];?>            <a href="mailto:<?=$oper['email'];?>?subject=<?=va("Вопрос от #1 из #2-й вселенной", $GlobalUser['oname'], $uni['num']);?>" ><img src="<?=UserSkin();?>img/m.gif" border="0" alt="Написать сообщение"></a>          </th>
        </tr>
<?php
    }

echo "</table></td></tr></table>\n";
echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>