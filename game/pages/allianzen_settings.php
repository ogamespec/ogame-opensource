<?php

// Управление альянсом.

function as_sel ($option, $value)
{
    if ( $option == $value) return "selected";
    else return "";
}

function PageAlly_Settings ()
{
    global $db_prefix;
    global $session;
    global $ally;
    global $GlobalUser;
    global $AllianzenError;

    // Ограничение на количество символов текстов.
    $MAXTEXT = 5000;

    $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
    if ( ! ($myrank['rights'] & ARANK_W_MEMBERS) )
    {
        $AllianzenError = "<center>\n".loca("ALLY_NO_WAY")."<br></center>";
        return;
    }

    if ( !key_exists('t', $_GET) || $_GET['t'] < 1 || $_GET['t'] > 3 ) $_GET['t'] = 1;

    if ( method () === "POST" )
    {
        if ( $_GET['a'] == 11 && $_GET['d'] == 1 )        // Изменить тексты
        {
            $ally_id = $ally['ally_id'];

            $text = str_replace ( '\"', "&quot;", $_POST['text'] );
            $text = str_replace ( '\'', "&rsquo;", $text );
            $text = str_replace ( '\`', "&lsquo;", $text );

            if ( $_GET['t'] == 2 ) $query = "UPDATE ".$db_prefix."ally SET inttext = '".$text."' WHERE ally_id = $ally_id";
            else if ( $_GET['t'] == 3 ) {
                $insertapp = intval($_POST['bewforce']) & 1;
                $query = "UPDATE ".$db_prefix."ally SET apptext = '".$text."', insertapp = $insertapp WHERE ally_id = $ally_id";
            }
            else $query = "UPDATE ".$db_prefix."ally SET exttext = '".$text."' WHERE ally_id = $ally_id";
            dbquery ($query);

            $ally = LoadAlly ($ally['ally_id']);
        }

        if ( $_GET['a'] == 11 && $_GET['d'] == 2 )        // Изменить установки
        {
            $ally_id = $ally['ally_id'];
            $query = "UPDATE ".$db_prefix."ally SET open = " . (intval($_POST['bew']) == 0 ? 1 : 0);
            $query .= ", homepage = '".$_POST['hp']."'";
            $query .= ", imglogo = '".$_POST['logo']."'";
            $query .= " WHERE ally_id = $ally_id";
            dbquery ($query);

            if ($_POST['fname'] !== "") {    // Название ранга основателя
                if ( !preg_match ("/^[a-zA-Z0-9\.\_\- ]+$/", $_POST['fname'] ) ) $AllianzenError = "<center>\n".loca("ALLY_RANK_ERROR_SPECIAL_CHARS")."<br></center>";
                else {
                    $query = "UPDATE ".$db_prefix."allyranks SET name = '".$_POST['fname']."' WHERE ally_id = $ally_id AND rank_id = 0";
                    dbquery ($query);
                }
            }
            
            $ally = LoadAlly ($ally['ally_id']);
        }
    }

    $owner = LoadRank ( $ally['ally_id'], 0 );
    $owner_name = $owner['name'];
    if ($owner_name === loca("ALLY_NEW_RANK_FOUNDER")) $owner_name = "";
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<tr><td class=c colspan=2><?=loca("ALLY_SETTINGS_HEAD");?></td></tr>
<tr><th colspan=2><a href="index.php?page=allianzen&session=<?=$session;?>&a=6"><?=loca("ALLY_SETTINGS_RANKS");?></a></th></tr>
<tr><th colspan=2><a href="index.php?page=allianzen&session=<?=$session;?>&a=7"><?=loca("ALLY_SETTINGS_MEMBERS");?></a></th></tr>
<tr><th colspan=2><a href="index.php?page=allianzen&session=<?=$session;?>&a=9"><img src="<?=UserSkin();?>pic/appwiz.gif" border=0 alt="<?=loca("ALLY_SETTINGS_CHANGE_TAG");?>"></a>&nbsp;
<a href="index.php?page=allianzen&session=<?=$session;?>&a=10"><img src="<?=UserSkin();?>pic/appwiz.gif" border=0 alt="<?=loca("ALLY_SETTINGS_CHANGE_NAME");?>"></a>
</table><br>

<form action="index.php?page=allianzen&session=<?=$session;?>&a=11&d=1&t=<?=intval($_GET['t']);?>" method=POST>
<table width=519>
<tr><td class=c colspan=3><?=loca("ALLY_SETTINGS_EDIT");?></td></tr>
<tr>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=5&t=1"><?=loca("ALLY_SETTINGS_EXTTEXT");?></a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=5&t=2"><?=loca("ALLY_SETTINGS_INTTEXT");?></a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=5&t=3"><?=loca("ALLY_SETTINGS_APPTEXT");?></a></th></tr>
<tr><td class=c colspan=3>
<?php
    if ( $_GET['t'] == 2 ) echo loca("ALLY_SETTINGS_INTTEXT_DEF");
    else if ( $_GET['t'] == 3 ) echo loca("ALLY_SETTINGS_APPTEXT_DEF");
    else echo loca("ALLY_SETTINGS_EXTTEXT_DEF");
?> (<span id="cntChars">
<?php
    if ( $_GET['t'] == 2 ) echo mb_strlen ($ally['inttext'], "UTF-8");
    else if ( $_GET['t'] == 3 ) echo mb_strlen ($ally['apptext'], "UTF-8");
    else echo mb_strlen ($ally['exttext'], "UTF-8");
?></span> / <?=va(loca("ALLY_SETTINGS_CHARS"), $MAXTEXT);?>)</td></tr>
<tr><th colspan=3><textarea name="text" cols=70 rows=15 onkeyup="javascript:cntchar(<?=$MAXTEXT;?>)">
<?php
    if ( $_GET['t'] == 2 ) echo $ally['inttext'];
    else if ( $_GET['t'] == 3 ) echo $ally['apptext'];
    else echo $ally['exttext'];
?></textarea></th></tr>
<?php
    if ( $_GET['t'] == 3)
    {
        echo "<tr><th colspan=3>".loca("ALLY_SETTINGS_APP_SAMPLE")." <select name=bewforce><option value=0";
        if ( $ally['insertapp'] == 0 ) echo " SELECTED";
        echo ">".loca("ALLY_SETTINGS_HIDE_APP")."</option><option value=1";
        if ( $ally['insertapp'] == 1 ) echo " SELECTED";
        echo ">".loca("ALLY_SETTINGS_SHOW_APP")."</option></select></th></tr>";
    }
?>
<tr><th colspan=3><input type=reset value="<?=loca("ALLY_SETTINGS_DELETE");?>"> <input type=submit value="<?=loca("ALLY_SETTINGS_SAVE");?>"></th></tr>
</table>
</form><br>

<form action="index.php?page=allianzen&session=<?=$session;?>&a=11&d=2" method=POST><table width=519>
<tr><td class=c colspan=2><?=loca("ALLY_SETTINGS_TITLE");?></td></tr>
<tr><th><?=loca("ALLY_SETTINGS_HOMEPAGE");?></th><th><input type=text name="hp" value="<?=$ally['homepage'];?>" size="70"></th></tr>
<tr><th><?=loca("ALLY_SETTINGS_LOGO");?></th><th><input type=text name="logo" value="<?=$ally['imglogo'];?>" size="70"></th></tr>
<tr><th><?=loca("ALLY_SETTINGS_APPS");?></th><th><select name=bew><option value=0 <?=as_sel($ally['open'], 1);?>><?=loca("ALLY_SETTINGS_APPS_OPEN");?></option><option value=1 <?=as_sel($ally['open'], 0);?>><?=loca("ALLY_SETTINGS_APPS_CLOSED");?></option></select></th></tr>
<tr><th><?=loca("ALLY_SETTINGS_FOUNDER");?></th><th><input type=text name=fname value="<?=$owner_name;?>" size=30></th>
<tr><th colspan=2><input type=submit value="<?=loca("ALLY_SETTINGS_SAVE");?>"></th></tr>
</table></form>

<form action="index.php?page=allianzen&session=<?=$session;?>&a=12" method=POST>
<table width=519>
<tr><td class=c><?=loca("ALLY_SETTINGS_DISMISS");?></td></tr><tr><th><input type=submit value="<?=loca("ALLY_SETTINGS_NEXT");?>"></th></tr>
</table></form>

<form action="index.php?page=allianzen&session=<?=$session;?>&a=18" method=POST>
<table width=519>
<tr><td class=c><?=loca("ALLY_SETTINGS_LEAVE");?></td></tr>
<tr><th><input type=submit value="<?=loca("ALLY_SETTINGS_NEXT");?>"></th></tr>
</table></form>
<?php
}

?>