<?php

// Сообщения.

// Про галочки напротив папок.
// Исходников HTML не сохранилось, поэтому никто толком не помнит как оно работало. Делаю так:
// - Кнопка "ок" запоминает выбранные галки, если их тыкали руками (метод POST)
// - Метод 1: Ссылка на категорию инвертирует значение галки и одновременно перезагружает сообщения с новыми настройками (метод GET)
// - Метод 2: Ссылка показывает только сообщения из выбранной категории вне зависимости от значений галочек (метод GET)
// Если вдруг вам что-то не нравится или у вас есть исходники HTML - мы открыты к обсуждению :-)

// Так как мы не знаем точного способа, выбираем метод этой переменной
$method = 2;

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "messages", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ( $GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

$prem = PremiumStatus ($GlobalUser);

PageHeader ("messages");

// *******************************************************************

$MAXMSG = $prem['commander'] ? 50 : 25;        // Количество сообщений на странице.
$uni = LoadUniverse ();
$partial_reports = ($GlobalUser['flags'] & USER_FLAG_PARTIAL_REPORTS) != 0;
$use_folders = ($GlobalUser['flags'] & USER_FLAG_DONT_USE_FOLDERS) == 0;    // инверсный смысл

// Управление папками
$folders = array (
    "espioopen" => array ( 'pm'=>MTYP_SPY_REPORT, 'flag'=>USER_FLAG_FOLDER_ESPIONAGE, 'title'=>loca("MSG_FOLDER1") ),
    "combatopen" => array ( 'pm'=>MTYP_BATTLE_REPORT_LINK, 'flag'=>USER_FLAG_FOLDER_COMBAT, 'title'=>loca("MSG_FOLDER2") ),
    "expopen" => array ( 'pm'=>MTYP_EXP, 'flag'=>USER_FLAG_FOLDER_EXPEDITION, 'title'=>loca("MSG_FOLDER3") ),
    "allyopen" => array ( 'pm'=>MTYP_ALLY, 'flag'=>USER_FLAG_FOLDER_ALLIANCE, 'title'=>loca("MSG_FOLDER4") ),
    "useropen" => array ( 'pm'=>MTYP_PM, 'flag'=>USER_FLAG_FOLDER_PLAYER, 'title'=>loca("MSG_FOLDER5") ),
    "generalopen" => array ( 'pm'=>MTYP_MISC, 'flag'=>USER_FLAG_FOLDER_OTHER, 'title'=>loca("MSG_FOLDER6") ),
);

$days = $prem['commander'] ? 7 : 1;
DeleteExpiredMessages ( $GlobalUser['player_id'], $days );    // Удалить сообщения которые хранятся дольше 24 часов (7 дней с Командиром)

// Заголовок таблицы
BeginContent ();

if ( method() === "POST" )
{
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
            if ( key_exists("sneak" . $msg_id, $_POST) && $_POST["sneak" . $msg_id] === "on" ) {}    // TODO: Сообщить оператору
            if ( key_exists("delmes" . $msg_id, $_POST) && $_POST["delmes" . $msg_id] === "on" && $_POST['deletemessages'] === "deletemarked" ) DeleteMessage ( $player_id, $msg_id );    // Удалить выделенные
            if ( !key_exists("delmes" . $msg_id, $_POST) && $_POST['deletemessages'] === "deletenonmarked" ) DeleteMessage ( $player_id, $msg_id );    // Удалить невыделенные
            if ( $_POST['deletemessages'] === "deleteallshown" ) DeleteMessage ( $player_id, $msg_id );    // Удалить показанные
        }
    }

    // Название параметра в инверсной логике
    $partial_reports = key_exists('fullreports', $_POST) && $_POST['fullreports'] === "on";

    if ($partial_reports) {
        SetUserFlags ( $GlobalUser['player_id'], $GlobalUser['flags'] | USER_FLAG_PARTIAL_REPORTS);
        $GlobalUser['flags'] |= USER_FLAG_PARTIAL_REPORTS;
    }
    else {
        SetUserFlags ( $GlobalUser['player_id'], $GlobalUser['flags'] & ~USER_FLAG_PARTIAL_REPORTS);
        $GlobalUser['flags'] &= ~USER_FLAG_PARTIAL_REPORTS;
    }

    // Фильтр папок -- обрабатывать только с включенным Командиром и включенными папками
    if ( $prem['commander'] && $use_folders ) {
        $flags = $GlobalUser['flags'];
        foreach ($folders as $i=>$folder) {
            if (key_exists($i, $_POST) && $_POST[$i] === "on") {
                $flags |= $folder['flag'];      // установить флаг
            }
            else {
                $flags &= ~$folder['flag'];     // сбросить флаг
            }
        }
        if ($flags != $GlobalUser['flags']) {
            SetUserFlags ($GlobalUser['player_id'], $flags);
            $GlobalUser['flags'] = $flags;
        }
    }
}

// Метод 1
// Обработка нажатия на ссылку типа сообщений для управления галочками напротив папок (Командир)
if ( method() === "GET" && $prem['commander'] && $use_folders && key_exists('pm', $_GET) && $method == 1 )
{
    $pm = intval ($_GET['pm']);
    foreach ($folders as $i=>$folder) {
        if ($folder['pm'] == $pm) {
            $flags = $GlobalUser['flags'] ^ $folder['flag'];    // инвертировать флаг (XOR)
            SetUserFlags ($GlobalUser['player_id'], $flags);
            $GlobalUser['flags'] = $flags;
            break;
        }
    }
}

echo "<table class='header'><tr class='header'><td><table width=\"519\">\n";
echo "<form action=\"index.php?page=messages&dsp=1&session=".$_GET['session']."\" method=\"POST\">\n";

if ($prem['commander'] && $use_folders) {

    echo "<tr><th colspan=\"4\">\n";
    echo "<select name=\"deletemessages\">\n";
    echo "<option value=\"deletemarked\">".loca("MSG_DELETE_MARKED")."</option> \n";
    echo "<option value=\"deletenonmarked\">".loca("MSG_DELETE_UNMARKED")."</option>\n";
    echo "<option value=\"deleteallshown\">".loca("MSG_DELETE_SHOWN")."</option>\n";
    echo "<option value=\"deleteall\">".loca("MSG_DELETE_ALL")."</option> \n";
    echo "</select><input type=\"submit\" value=\"".loca("MSG_SUBMIT")."\" /></th></tr>\n";
}

echo "<tr><td colspan=\"4\" class=\"c\">".loca("MSG_MESSAGES")."</td></tr>\n";

if ($prem['commander'] && $use_folders) {

    // Показать папки и количество сообщений для каждого типа (Всего / Непрочитанных)

    echo "<tr><th>".loca("MSG_FOLDER_SHOW")."</th><th colspan=\"2\">".loca("MSG_FOLDER_TYPE")."</th><th>".loca("MSG_FOLDER_STAT")."</th></tr>\n";
    foreach ($folders as $i=>$folder) {

        $total = TotalMessages ($GlobalUser['player_id'], $folder['pm']);
        $unread = UnreadMessages ($GlobalUser['player_id'], true, $folder['pm']);
        $checked = ($GlobalUser['flags'] & $folder['flag']) != 0 ? "CHECKED" : "";

        if (method() === "GET" && key_exists('pm', $_GET) && $method == 2) {
            $checked = $folder['pm'] == intval ($_GET['pm']) ? "CHECKED" : "";
        }

        echo "<tr> \n";
        echo "   <th><input type=\"checkbox\" name=\"".$i."\"  $checked /></th> \n";
        echo "   <th colspan=\"2\"><a href=\"index.php?page=messages&dsp=1&pm=".$folder['pm']."&session=".$_GET['session']."\">".$folder['title']."</a></th> \n";
        echo "   <th>$total / $unread</th> \n";
        echo "</tr> \n";
    }    

    echo "<tr><th colspan=\"4\"><input type=\"checkbox\" name=\"fullreports\"  " . ($partial_reports ? "CHECKED" : "") . "/>".loca("MSG_PARTIAL_ESPIONAGE")."</th></tr>\n";
}

if ($prem['commander'] && $use_folders) {
    // У командира с папками заголовок сообщений становится td class=c, чтобы его было лучше видно. Это подтверждено на видео в YouTube (https://www.youtube.com/watch?v=PXRKO16y8Q8)
    echo "<tr><td class=\"c\">".loca("MSG_ACTION")."</td><td class=\"c\">".loca("MSG_DATE")."</td><td class=\"c\">".loca("MSG_FROM")."</td><td class=\"c\">".loca("MSG_SUBJ")."</td></tr>\n";
}
else {
    echo "<tr><th>".loca("MSG_ACTION")."</th><th>".loca("MSG_DATE")."</th><th>".loca("MSG_FROM")."</th><th>".loca("MSG_SUBJ")."</th></tr>\n";
}

$result = EnumMessages ( $GlobalUser['player_id'], $MAXMSG);
$num = dbrows ($result);
while ($num--)
{
    $msg = dbarray ($result);
    $pm = $msg['pm'];
    if ($pm == MTYP_BATTLE_REPORT_TEXT) continue;    // Пропускать тексты боевых докладов.
    
    // Фильтровать сообщения по типу, если активен Командир И включено использование папок в настройках.
    if ($prem['commander'] && $use_folders) {

        $skip = false;
        
        // Метод 2
        // Ссылка показывает только сообщения из выбранной категории вне зависимости от значений галочек
        if (method() === "GET" && key_exists('pm', $_GET) && $method == 2) {
            $skip = $pm != intval ($_GET['pm']);
        }
        else {
            foreach ($folders as $i=>$folder) {
                if ($folder['pm'] == $pm && ($GlobalUser['flags'] & $folder['flag']) == 0) {
                    $skip = true;
                    break;
                }
            }
        }

        if ($skip) continue;
    }

    $msg['msgfrom'] = str_replace ( "{PUBLIC_SESSION}", $_GET['session'], $msg['msgfrom']);
    $msg['subj'] = str_replace ( "{PUBLIC_SESSION}", $_GET['session'], $msg['subj']);
    $msg['text'] = str_replace ( "{PUBLIC_SESSION}", $_GET['session'], $msg['text']);
    if ($partial_reports && $pm == MTYP_SPY_REPORT) {
        // Специальная обработка для шпионских докладов, если активна галочка "показывать частично"
        $msg['subj'] = "<a href=\"#\" onclick=\"fenster('index.php?page=bericht&session=". $_GET['session'] ."&bericht=". $msg['msg_id'] ."', 'Bericht_Spionage');\" >". $msg['subj'] ."</a>";
        $msg['text'] = "";
    }    
    echo "<tr><th><input type=\"checkbox\" name=\"delmes".$msg['msg_id']."\"/></th><th>".date ("m-d H:i:s", $msg['date'])."</th><th>".$msg['msgfrom']." </th><th>".$msg['subj']." </th></tr>\n";
    if ($msg['text'] !== "") {
        echo "<tr><td class=\"b\"> </td><td class=\"b\" colspan=\"3\">".$msg['text']."</td></tr>\n";
    }
    if ($pm == MTYP_PM) echo "<tr><th colspan=\"4\"><input type=\"checkbox\" name=\"sneak".$msg['msg_id']."\"/><input type=\"submit\" value=\"".loca("MSG_REPORT")."\"/></th></tr>\n";
    MarkMessage ( $msg['owner_id'], $msg['msg_id'] );
}

// Низ таблицы  
echo "<tr><th colspan=\"4\" style='padding:0px 105px;'></th></tr>\n";

// У командира с папками эти контролы показываются в самом начале
if (! ($prem['commander'] && $use_folders)) {
    echo "<tr><th colspan=\"4\"><input type=\"checkbox\" name=\"fullreports\"  " . ($partial_reports ? "CHECKED" : "") . "/>".loca("MSG_PARTIAL_ESPIONAGE")."</th></tr>\n";
    echo "<tr><th colspan=\"4\">\n";
    echo "<select name=\"deletemessages\">\n";
    echo "<option value=\"deletemarked\">".loca("MSG_DELETE_MARKED")."</option> \n";
    echo "<option value=\"deletenonmarked\">".loca("MSG_DELETE_UNMARKED")."</option>\n";
    echo "<option value=\"deleteallshown\">".loca("MSG_DELETE_SHOWN")."</option>\n";
    echo "<option value=\"deleteall\">".loca("MSG_DELETE_ALL")."</option> \n";
    echo "</select><input type=\"submit\" value=\"".loca("MSG_SUBMIT")."\" /></th></tr>\n";
}

echo "<tr><td colspan=\"4\"><center>     </center></td></tr>\n";
echo "<input type=\"hidden\" name=\"messages\" value=\"1\" />\n";
echo "</form>\n";
echo "<tr><td class=\"c\" colspan=\"4\">".loca("MSG_OPER")."</td></tr>\n";

    // Общение с операторами предполагало использование обычной почты (mailto).
    // TODO: Сделать настройку варианта отправления обычного игрового сообщения, если оператор не хочет светить свой почтовый адрес
    $result = EnumOperators ();
    $rows = dbrows ($result);
    while ($rows--)
    {
        $oper = dbarray ($result);
?>
                <tr>
            <th colspan="4" valign="left">
            <?=$oper['oname'];?>            <a href="mailto:<?=$oper['email'];?>?subject=<?=va(loca("MSG_OPER_TEXT"), $GlobalUser['oname'], $uni['num']);?>" ><img src="<?=UserSkin();?>img/m.gif" border="0" alt="<?=loca("MSG_OPER_PM");?>"></a>          </th>
        </tr>
<?php
    }

echo "</table></td></tr></table>\n";
echo "<br><br><br><br>\n";
EndContent ();

PageFooter ();
ob_end_flush ();
?>