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

    if ( method () === "POST" )
    {
        if ( $_GET['a'] == 11 && $_GET['d'] == 1 )        // Изменить текст
        {
        }

        if ( $_GET['a'] == 11 && $_GET['d'] == 2 )        // Изменить установки
        {
            $ally_id = $ally['ally_id'];
            $query = "UPDATE ".$db_prefix."ally SET open = " . ($_POST['bew'] == 0 ? 1 : 0);
            $query .= ", homepage = '".$_POST['hp']."'";
            $query .= ", imglogo = '".$_POST['logo']."'";
            $query .= " WHERE ally_id = $ally_id";
            dbquery ($query);

            if ($_POST['fname'] !== "") {    // Название ранга основателя
                $query = "UPDATE ".$db_prefix."allyranks SET name = '".$_POST['fname']."' WHERE ally_id = $ally_id AND rank_id = 0";
                dbquery ($query);
            }
            
            $ally = LoadAlly ($ally['ally_id']);
        }
    }

    $owner = LoadRank ( $ally['ally_id'], 0 );
    $owner_name = $owner['name'];
    if ($owner_name === "Основатель") $owner_name = "";
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<tr><td class=c colspan=2>управление альянсом</td></tr>
<tr><th colspan=2><a href="index.php?page=allianzen&session=<?=$session;?>&a=6">Установить ранги</a></th></tr>
<tr><th colspan=2><a href="index.php?page=allianzen&session=<?=$session;?>&a=7">Члены альянса</a></th></tr>
<tr><th colspan=2><a href="index.php?page=allianzen&session=<?=$session;?>&a=9"><img src="<?=UserSkin();?>pic/appwiz.gif" border=0 alt="Изменить аббревиатуру альянса (только 1 раз в неделю)"></a>&nbsp;
<a href="index.php?page=allianzen&session=<?=$session;?>&a=10"><img src="<?=UserSkin();?>pic/appwiz.gif" border=0 alt="Изменить название альянса (только 1 раз в неделю)"></a>
</table><br>

<form action="index.php?page=allianzen&session=<?=$session;?>&a=11&d=1" method=POST>
<table width=519>
<tr><td class=c colspan=3>Редактировать текст</td></tr>
<tr>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=5&t=1">Внешний текст</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=5&t=2">Внутренний текст</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=5&t=3">Текст заявки</a></th></tr>
<tr><td class=c colspan=3>Внешний текст альянса (<span id="cntChars">31</span> / 5000 символов)</td></tr>
<tr><th colspan=3><textarea name="text" cols=70 rows=15 onkeyup="javascript:cntchar(5000)">Willkommen auf der Allianzseite</textarea></th></tr>
<tr><th colspan=3><input type=hidden name=t value=1><input type=reset value="Удалить"> <input type=submit value="Сохранить"></th></tr>
</table>
</form><br>

<form action="index.php?page=allianzen&session=<?=$session;?>&a=11&d=2" method=POST><table width=519>
<tr><td class=c colspan=2>Установки</td></tr>
<tr><th>Домашняя страница</th><th><input type=text name="hp" value="<?=$ally['homepage'];?>" size="70"></th></tr>
<tr><th>Логотип альянса</th><th><input type=text name="logo" value="<?=$ally['imglogo'];?>" size="70"></th></tr>
<tr><th>Заявки</th><th><select name=bew><option value=0 <?=as_sel($ally['open'], 1);?>>Возможны (альянс открыт)</option><option value=1 <?=as_sel($ally['open'], 0);?>>Невозможны (альянс закрыт)</option></select></th></tr>
<tr><th>Имя главы</th><th><input type=text name=fname value="<?=$owner_name;?>" size=30></th>
<tr><th colspan=2><input type=submit value="Сохранить"></th></tr>
</table></form>

<form action="index.php?page=allianzen&session=<?=$session;?>&a=12" method=POST>
<table width=519>
<tr><td class=c>Распустить альянс</td></tr><tr><th><input type=submit value="Дальше"></th></tr>
</table></form>

<form action="index.php?page=allianzen&session=<?=$session;?>&a=18" method=POST>
<table width=519>
<tr><td class=c>Покинуть/перенять этот альянс</td></tr>
<tr><th><input type=submit value="Дальше"></th></tr>
</table></form>
<?php
}

?>