<?php

// Главная страница.

function AllyPage_Home ()
{
    global $GlobalUser;
    global $session;
    global $ally;

    $members = CountAllyMembers ( $ally['ally_id'] );
    $rank = LoadRank ( $GlobalUser['ally_id'], $GlobalUser['allyrank'] );

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<tr><td class=c colspan=2>Ваш альянс</td></tr>
<tr><th>Аббревиатура</th><th><?=$ally['tag'];?></th></tr>
<tr><th>Имя</th><th><?=$ally['name'];?></th></tr>
<tr><th>Члены</th><th><?=$members;?> (<a href="index.php?page=allianzen&session=<?=$session;?>&a=4">список членов</a>)</th></tr>
<tr><th>Ваш ранг</th><th><?=$rank['name'];?> (<a href="index.php?page=allianzen&session=<?=$session;?>&a=5">управление альянсом</a>)</th></tr>
<tr><th>Заявки</th><th><a href="index.php?page=bewerbungen&session=<?=$session;?>">1 Заявление (-я)</a></th></tr>
<tr><th>Общее сообщение</th><th><a href="index.php?page=allianzen&session=<?=$session;?>&a=17">Послать общее сообщение</a></th></tr>
<tr><th colspan=2 height=100><?=$ally['exttext'];?></th></tr>
<tr><th>Домашняя страница</th><th><a href="redir.php?url=<?=$ally['homepage'];?>" target="_blank"><?=$ally['homepage'];?></a></th></tr>
<tr><td class=c colspan=2>Внутренняя компетенция</th></tr><tr><th colspan=2 height=100><?=$ally['inttext'];?></th></tr>
</table><br>
<?php
}

?>