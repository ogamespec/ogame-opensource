<?php

// Заметки.

loca_add ( "notes", $GlobalUni['lang'] );

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

function CreateNewNote ()
{
    echo "<form action=\"?page=notizen&session=".$_GET['session']."\" method=post>\n";
    echo "<input type=hidden name=s value=1>\n";
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>".loca("NOTE_CREATE")."</td></tr>\n";
    echo "<tr><th>".loca("NOTE_PRIORITY")."</th><th><select name=u><option value=2>".loca("NOTE_PRIO_2")."</option><option value=1>".loca("NOTE_PRIO_1")."</option><option value=0>".loca("NOTE_PRIO_0")."</option></select></th></tr>\n";
    echo "<tr><th>".loca("NOTE_CREATE_SUBJ")."</th><th><input type=text name=betreff size=30 maxlength=30 value=''></th></tr>\n";
    echo "<tr><th>".loca("NOTE_CREATE_TEXT")." (<span id=\"cntChars\">0</span> / 5000 ".loca("NOTE_CHARS").")</th><th><textarea name=text cols=60 rows=10 onkeyup=\"javascript:cntchar(5000)\"></textarea></th></tr>\n";
    echo "<tr><td class=c><a href=?page=notizen&session=".$_GET['session'].">".loca("NOTE_BACK")."</a></td><td class=c><input type=submit value='".loca("NOTE_SAVE")."'></td></tr>\n";
    echo "</table></form><br><br><br><br>\n";
}

function EditNote ($note_id)
{
    global $GlobalUser;
    $note = LoadNote ( $GlobalUser['player_id'], $note_id );

    if ( $note == NULL ) {
        echo loca("NOTE_CANT_DO");
        return;
    }

    $u = array ( "", "", "");
    $u[$note['prio']] = " SELECTED";

    echo "<form action=\"?page=notizen&session=".$_GET['session']."\" method=post>\n";
    echo "<input type=hidden name=s value=2>\n";
    echo "<input type=hidden name=n value=".$note['note_id'].">\n";
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>".loca("NOTE_EDIT")."</td></tr>\n";
    echo "<tr><th>".loca("NOTE_PRIORITY")."</th><th><select name=u><option value=2".$u[2].">".loca("NOTE_PRIO_2")."</option><option value=1".$u[1].">".loca("NOTE_PRIO_1")."</option><option value=0".$u[0].">".loca("NOTE_PRIO_0")."</option></select></th></tr>\n";
    echo "<tr><th>".loca("NOTE_EDIT_SUBJ")."</th><th><input type=text name=betreff size=30 maxlength=30 value='".stripslashes($note['subj'])."'></th></tr>\n";
    echo "<tr><th>".loca("NOTE_EDIT_TEXT")." (<span id=\"cntChars\">".$note['textsize']."</span> / 5000 ".loca("NOTE_CHARS").")</th><th><textarea name=text cols=60 rows=10 onkeyup=\"javascript:cntchar(5000)\">".stripslashes($note['text'])."</textarea></th></tr>\n";
    echo "<tr><td class=c><a href=?page=notizen&session=".$_GET['session'].">".loca("NOTE_BACK")."</a></td><td class=c><input type=reset value='".loca("NOTE_RESET")."'><input type=submit value='".loca("NOTE_APPLY")."'></td></tr>\n";
    echo "</table></form><br><br><br><br>\n";
}

// Обработать POST запросы.
if ( key_exists ('s', $_POST) )    // Добавить/редактировать
{
    $title = htmlspecialchars($_POST['betreff']);
    $text = $_POST['text'];

    $title = addslashes ( $title );
    $text = addslashes ( $text );

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
    echo "<tr><td class=c colspan=4>".loca("NOTE_NOTES")."</td></tr>\n";
    echo "<tr><th colspan=4><a href=?page=notizen&session=".$_GET['session']."&a=1>".loca("NOTE_CREATE_NEW")."</a></td></tr>\n\n";

    echo "<tr>\n";
    echo "  <td class=c></td>\n";
    echo "  <td class=c>".loca("NOTE_DATE")."</td>\n";
    echo "  <td class=c>".loca("NOTE_SUBJ")."</td>\n";
    echo "  <td class=c>".loca("NOTE_SIZE")."</td>\n";
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
    else echo "<tr><th colspan=4>".loca("NOTE_NO_NOTES")."</th></tr>\n\n";

    echo "<tr><td colspan=4><input type=submit value='".loca("NOTE_DELETE")."'></td></tr>\n";
    echo "</table>\n";
    echo "</form><br><br><br><br>\n";
}

echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ("","",true,0);
ob_end_flush ();
?>