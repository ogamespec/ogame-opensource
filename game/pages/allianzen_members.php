<?php

// Список пользователей и управление пользователями.

function PageAlly_MemberList ()
{
    global $session;

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<tr><td class='c' colspan='10'>список членов (Кол-во: 2)</td></tr>
<tr>
    <th>N</th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=1&sort2=1">Имя</a></th>
    <th> </th><th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=2&sort2=1">Статус</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=3&sort2=1">Очки</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=0&sort2=1">Координаты</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=4&sort2=1">Вступление</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=5&sort2=1">Online</a></th></tr>
<tr>
    <th>1</th>
    <th>spieler2</th>
    <th><a href="index.php?page=writemessages&session=<?=$session;?>&messageziel=222942"><img src="http://uni20.ogame.de/evolution/img/m.gif" border=0 alt="Написать сообщение"></a></th>
    <th>Neuling</th>
    <th>0</th>
    <th><a href="index.php?page=galaxy&galaxy=1&system=36&position=7&session=<?=$session;?>" >[1:36:7]</a></th>
    <th>2011-04-07 08:45:51</th>
    <th><font color=lime>Да</font></th></tr>
<tr>
    <th>2</th>
    <th>spieler1</th>
    <th></th>
    <th>zzzz</th>
    <th>0</th>
    <th><a href="index.php?page=galaxy&galaxy=1&system=29&position=5&session=<?=$session;?>" >[1:29:5]</a></th>
    <th>2011-04-07 08:01:43</th>
    <th><font color=lime>Да</font></th></tr>
</table>
<?php
}

function PageAlly_MemberSettings ()
{
    global $session;

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script><br>
<a href="index.php?page=allianzen&session=<?=$session;?>&a=5">Назад к обзору</a>
<table width=519>
<tr><td class='c' colspan='10'>Список членов (кол-во: 1)</td></tr>
<tr>
    <th>N</th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=1&sort2=1">Имя</a></th>
    <th> </th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=2&sort2=1">Статус</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=3&sort2=1">Очки</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=0&sort2=1">Координаты</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=4&sort2=1">Вступление</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=5&sort2=1">Неактивный</a></th>
    <th>Функция</th></tr>
<tr>
    <th>1</th>
    <th>spieler1</th>
    <th></th>
    <th>zzzz</th>
    <th>0</th>
    <th><a href="index.php?page=galaxy&galaxy=1&system=29&position=5&session=<?=$session;?>" >[1:29:5]</a></th>
    <th>2011-04-07 08:01:43</th>
    <th>0d</th>
    <th>&nbsp;</th></tr>
</table>
<?php
}

?>