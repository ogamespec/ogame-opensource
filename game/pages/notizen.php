<?php

// Заметки.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

function CreateNewNote ()
{
    echo "<form action=\"?page=notizen&session=".$_GET['session']."\" method=post>\n";
    echo "<input type=hidden name=s value=1>\n";
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>Составить заметку</td></tr>\n";
    echo "<tr><th>Важность</th><th><select name=u><option value=2>Важно</option><option value=1>Так себе</option><option value=0>Неважно</option></select></th></tr>\n";
    echo "<tr><th>Тема</th><th><input type=text name=betreff size=30 maxlength=30 value=''></th></tr>\n";
    echo "<tr><th>Заметка (<span id=\"cntChars\">0</span> / 5000 символов)</th><th><textarea name=text cols=60 rows=10 onkeyup=\"javascript:cntchar(5000)\"></textarea></th></tr>\n";
    echo "<tr><td class=c><a href=?page=notizen&session=".$_GET['session'].">Назад</a></td><td class=c><input type=submit value='Сохранить'></td></tr>\n";
    echo "</table></form><br><br><br><br>\n";
}

function EditNote ($note_id)
{
    global $GlobalUser;
    $note = LoadNote ( $GlobalUser['player_id'], $note_id );

    $u = array ( "", "", "");
    $u[$note['prio']] = " SELECTED";

    echo "<form action=\"?page=notizen&session=".$_GET['session']."\" method=post>\n";
    echo "<input type=hidden name=s value=2>\n";
    echo "<input type=hidden name=n value=".$note['note_id'].">\n";
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>Редактировать заметку</td></tr>\n";
    echo "<tr><th>Важность</th><th><select name=u><option value=2".$u[2].">Важно</option><option value=1".$u[1].">Так себе</option><option value=0".$u[0].">Неважно</option></select></th></tr>\n";
    echo "<tr><th>Тема</th><th><input type=text name=betreff size=30 maxlength=30 value='".stripslashes($note['subj'])."'></th></tr>\n";
    echo "<tr><th>Заметка (<span id=\"cntChars\">".$note['textsize']."</span> / 5000 символов)</th><th><textarea name=text cols=60 rows=10 onkeyup=\"javascript:cntchar(5000)\">".stripslashes($note['text'])."</textarea></th></tr>\n";
    echo "<tr><td class=c><a href=?page=notizen&session=".$_GET['session'].">Назад</a></td><td class=c><input type=reset value='Отменить'><input type=submit value='Сохранить'></td></tr>\n";
    echo "</table></form><br><br><br><br>\n";
}

// Обработать POST запросы.
if ( key_exists ('s', $_POST) )    // Добвить/редактировать
{
    $title = htmlspecialchars($_POST['betreff']);
    $text = $_POST['text'];

    if ( !get_magic_quotes_gpc () ) {
        $title = addslashes ( $title );
        $text = addslashes ( $text );
    }

    if ( intval($_POST['s']) == 1 ) AddNote ( $GlobalUser['player_id'], $title, $text, intval($_POST['u']) );
    else if ( intval($_POST['s']) == 2 ) UpdateNote ( $GlobalUser['player_id'], intval($_POST['n']), $title, $text, intval($_POST['u']) );
}
if ( key_exists ('delmes', $_POST) )    // Удалить
{
    foreach ($_POST['delmes'] as $i => $entry) DelNote ( $GlobalUser['player_id'], intval($i) );
}

// Проверить неверные параметры.
if ( key_exists ('a', $_GET) )
{
    if ( intval($_GET['a']) < 1 || intval($_GET['a']) > 2 ) die ();
    if ( intval($_GET['a']) == 2 && !key_exists ('n', $_GET)) die ();
    if ( intval($_GET['a']) == 2 && LoadNote ( $GlobalUser['player_id'], intval($_GET['n']) ) === FALSE ) die();
}

PageHeader ("notizen", true, false);

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";
echo "<script src=\"js/cntchar.js\" type=\"text/javascript\"></script>\n";
echo "<script src=\"js/win.js\" type=\"text/javascript\"></script>\n";

if ( key_exists ('a', $_GET) )
{
    if ( intval($_GET['a']) == 1 ) CreateNewNote ();
    else if ( intval($_GET['a']) == 2 ) EditNote ( intval($_GET['n']) );
}
else
{
    echo "<form action=\"?page=notizen&session=".$_GET['session']."\" method=post>\n";
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=4>Заметки</td></tr>\n";
    echo "<tr><th colspan=4><a href=?page=notizen&session=".$_GET['session']."&a=1>Составить новую заметку</a></td></tr>\n\n";

    echo "<tr>\n";
    echo "  <td class=c></td>\n";
    echo "  <td class=c>Дата</td>\n";
    echo "  <td class=c>Тема</td>\n";
    echo "  <td class=c>Размер</td>\n";
    echo "</tr>\n\n";

    $result = EnumNotes ( $GlobalUser['player_id'] );
    $num = dbrows ($result);
    if ($num)
    {
        while ($num--)
        {
            $note = dbarray ($result);
            if ($note['prio'] == 0) $col = "lime";
            else if ($note['prio'] == 1) $col = "yellow";
            else if ($note['prio'] == 2) $col = "red";
            echo "<tr>\n";
            echo "  <th width=20><input type=checkbox name=\"delmes[".$note['note_id']."]\" value=\"y\"></th>\n";
            echo "  <th width=150>".date ("Y-m-d H:i:s", $note['date'])."</th>\n";
            echo "  <th><a href=?page=notizen&session=".$_GET['session']."&a=2&n=".$note['note_id']."><font color=$col>".stripslashes($note['subj'])."</font></a></th>\n";
            echo "  <th width=40 align=right>".$note['textsize']."</th>\n";
            echo "</tr>\n\n";
        }
    }
    else echo "<tr><th colspan=4>Сообщений нет</th></tr>\n\n";

    echo "<tr><td colspan=4><input type=submit value='Удалить'></td></tr>\n";
    echo "</table>\n";
    echo "</form><br><br><br><br>\n";
}

echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ("","",true,0);
ob_end_flush ();
?>