<?php

// Главная страница.

function AllyPage_Home ()
{
    global $GlobalUser;
    global $session;

    $ally = LoadAlly ($GlobalUser['ally_id']);

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<tr><td class=c colspan=2>Ваш альянс</td></tr>
<tr><th>Аббревиатура</th><th>123</th></tr>
<tr><th>Имя</th><th>12345</th></tr>
<tr><th>Члены</th><th>1 (<a href="index.php?page=allianzen&session=<?=$session;?>&a=4">список членов</a>)</th></tr>
<tr><th>Ваш ранг</th><th>zzzz (<a href="index.php?page=allianzen&session=<?=$session;?>&a=5">управление альянсом</a>)</th></tr>
<tr><th>Общее сообщение</th><th><a href="index.php?page=allianzen&session=<?=$session;?>&a=17">Послать общее сообщение</a></th></tr>
<tr><th colspan=2 height=100>Willkommen auf der Allianzseite 2</th></tr>
<tr><th>Домашняя страница</th><th><a href="redir.php?url=http://zzzz" target="_blank">http://zzzz</a></th></tr>
<tr><td class=c colspan=2>Внутренняя компетенция</th></tr><tr><th colspan=2 height=100>zzzzz 2</th></tr>
</table><br>
<?php
}

?>