<?php

// Список пользователей и управление пользователями.

function PageAlly_MemberList ()
{
    global $session;
    global $ally;

    $members = CountAllyMembers ( $ally['ally_id'] );

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<tr><td class='c' colspan='10'>список членов (Кол-во: <?=$members;?>)</td></tr>
<tr>
    <th>N</th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=1&sort2=1">Имя</a></th>
    <th> </th><th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=2&sort2=1">Статус</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=3&sort2=1">Очки</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=0&sort2=1">Координаты</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=4&sort2=1">Вступление</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=5&sort2=1">Online</a></th></tr>
<?php
    $result = EnumerateAlly ($ally['ally_id'], 0, 0);
    for ($i=0; $i<$members; $i++)
    {
        $user = dbarray ($result);
        $rank = LoadRank ( $user['ally_id'], $user['allyrank'] );
        $hplanet = GetPlanet ($user['hplanetid']);
        echo "<tr>\n";
        echo "    <th>".($i+1)."</th>\n";
        echo "    <th>".$user['oname']."</th>\n";
        echo "    <th><a href=\"index.php?page=writemessages&session=$session&messageziel=222942\"><img src=\"http://uni20.ogame.de/evolution/img/m.gif\" border=0 alt=\"Написать сообщение\"></a></th>\n";
        echo "    <th>".$rank['name']."</th>\n";
        echo "    <th>".nicenum($user['score1'] / 1000)."</th>\n";
        echo "    <th><a href=\"index.php?page=galaxy&galaxy=".$hplanet['g']."&system=".$hplanet['s']."&position=".$hplanet['p']."&session=$session\" >[".$hplanet['g'].":".$hplanet['s'].":".$hplanet['p']."]</a></th>\n";
        echo "    <th>".date ("Y-m-d H:i:s", $user['joindate'])."</th>\n";
        echo "    <th><font color=lime>Да</font></th></tr>\n";
    }
?>
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