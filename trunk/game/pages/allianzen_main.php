<?php

// Главная страница.

function AllyPage_Home ()
{
    global $GlobalUser;
    global $session;
    global $ally;

    $now = time ();
    $members = CountAllyMembers ( $ally['ally_id'] );
    $rank = LoadRank ( $GlobalUser['ally_id'], $GlobalUser['allyrank'] );

    $result = EnumApplications ( $ally['ally_id'] );
    $apps = dbrows ($result);

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<?php
    if ( $ally['imglogo'] !== "" ) 
    {
?>
<tr><th colspan=2><img src="/game/img/preload.gif" class="reloadimage" title="pic.php?url=<?=$ally['imglogo'];?>"></td></tr>
<?php
    }
?>
<table width=519>
<tr><td class=c colspan=2>Ваш альянс</td></tr>
<tr><th>Аббревиатура</th><th><?=$ally['tag'];?>
<?php
    if ( $now < $ally['tag_until'] ) echo " (бывш. ".$ally['old_tag'].")";
?>
</th></tr>
<tr><th>Имя</th><th><?=$ally['name'];?>
<?php
    if ( $now < $ally['name_until'] ) echo " (бывш. ".$ally['old_name'].")";
?>
</th></tr>
<tr><th>Члены</th><th><?=$members;?>
<?php
    if ( $rank['rights'] & 0x008 ) echo " (<a href=\"index.php?page=allianzen&session=$session&a=4\">список членов</a>)";
?>
</th></tr>
<tr><th>Ваш ранг</th><th><?=$rank['name'];?>
<?php
    if ( $rank['rights'] & 0x020 ) echo " (<a href=\"index.php?page=allianzen&session=$session&a=5\">управление альянсом</a>)";
?>
</th></tr>
<?php
    if ( $apps > 0 )
    {
?>
<tr><th>Заявки</th><th><a href="index.php?page=bewerbungen&session=<?=$session;?>"><?=$apps;?> Заявление (-я)</a></th></tr>
<?php
    }
?>
<?php
    if ( $rank['rights'] & 0x080 )
    {
?>
<tr><th>Общее сообщение</th><th><a href="index.php?page=allianzen&session=<?=$session;?>&a=17">Послать общее сообщение</a></th></tr>
<?php
    }
?>
<tr><th colspan=2 height=100><?=bb($ally['exttext']);?></th></tr>
<tr><th>Домашняя страница</th><th><a href="redir.php?url=<?=$ally['homepage'];?>" target="_blank"><?=$ally['homepage'];?></a></th></tr>
<tr><td class=c colspan=2>Внутренняя компетенция</th></tr><tr><th colspan=2 height=100><?=bb($ally['inttext']);?></th></tr>
</table><br>
<?php
    if ( $GlobalUser['allyrank'] != 0 )    // Не показывать Основателю диалог выхода из альянса.
    {
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=3" method=POST>
<tr><td class=c colspan=2>Покинуть этот альянс</td></tr><tr><th colspan=2><input type=submit value="Да!"></th></tr></table></form>
<?php
    }
}

?>