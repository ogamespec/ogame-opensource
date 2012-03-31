<?php

// Список пользователей и управление пользователями.

function PageAlly_MemberList ()
{
    global $session;
    global $ally;
    global $GlobalUser;
    global $AllianzenError;

    $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
    if ( ! ($myrank['rights'] & 0x008) )
    {
        $AllianzenError = "<center>\nПросмотр невозможен<br></center>";
        return;
    }

    $members = CountAllyMembers ( $ally['ally_id'] );
    $now = time ();

    $sort1 = intval ($_GET['sort1']);
    $sort2 = intval ($_GET['sort2']) ^ 1;

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<tr><td class='c' colspan='10'>список членов (Кол-во: <?=$members;?>)</td></tr>
<tr>
    <th>N</th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=1&sort2=<?=$sort2;?>">Имя</a></th>
    <th> </th><th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=2&sort2=<?=$sort2;?>">Статус</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=3&sort2=<?=$sort2;?>">Очки</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=0&sort2=<?=$sort2;?>">Координаты</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=4&sort2=<?=$sort2;?>">Вступление</a></th>
<?php
    if ( $myrank['rights'] & 0x040 ) echo "    <th><a href=\"index.php?page=allianzen&session=$session&a=4&sort1=5&sort2=".$sort2."\">Online</a></th></tr>\n";
    if ( ($myrank['rights'] & 0x040) == 0 && $sort1 == 5 ) $sort1 = 0;
?>
<?php
    $result = EnumerateAlly ($ally['ally_id'], intval ($_GET['sort1']), intval ($_GET['sort2']));
    for ($i=0; $i<$members; $i++)
    {
        $user = dbarray ($result);
        $rank = LoadRank ( $user['ally_id'], $user['allyrank'] );
        $hplanet = GetPlanet ($user['hplanetid']);
        echo "<tr>\n";
        echo "    <th>".($i+1)."</th>\n";
        echo "    <th>".$user['oname']."</th>\n";
        if ( $GlobalUser['player_id'] != $user['player_id'] ) {
            echo "    <th><a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\"><img src=\"".UserSkin()."img/m.gif\" border=0 alt=\"Написать сообщение\"></a></th>\n";
        }
        else echo "    <th></th>\n";
        echo "    <th>".$rank['name']."</th>\n";
        echo "    <th>".nicenum($user['score1'] / 1000)."</th>\n";
        echo "    <th><a href=\"index.php?page=galaxy&galaxy=".$hplanet['g']."&system=".$hplanet['s']."&position=".$hplanet['p']."&session=$session\" >[".$hplanet['g'].":".$hplanet['s'].":".$hplanet['p']."]</a></th>\n";
        echo "    <th>".date ("Y-m-d H:i:s", $user['joindate'])."</th>\n";
        if ( $myrank['rights'] & 0x040 )
        {
            $min = floor ( ($now - $user['lastclick']) / 60 );
            if ( $min < 15 ) echo "    <th><font color=lime>Да</font></th>";
            else if ( $min < 60 ) echo "    <th><font color=yellow>$min min</font></th>";
            else echo "    <th><font color=red>Нет</font></th>";
        }
        echo "</tr>\n";
    }
?>
</table>
<?php
}

function PageAlly_MemberSettings ()
{
    global $db_prefix;
    global $session;
    global $ally;
    global $GlobalUser;
    global $AllianzenError;

    $selected_user = 0;
    if ( key_exists ('u', $_GET) ) $selected_user = intval($_GET['u']);

    if ( method() === "GET" && $_GET['a'] == 13 && $selected_user)        // Выгнать игрока
    {
        $leaver = LoadUser ($selected_user);

        $query = "UPDATE ".$db_prefix."users SET ally_id = 0 WHERE player_id = $selected_user";
        dbquery ($query);

        // Разослать сообщения членам альянса об исключении игрока
        $result = EnumerateAlly ($ally['ally_id']);
        $rows = dbrows ($result);
        while ($rows--)
        {
            $user = dbarray ($result);
            SendMessage ( $user['player_id'], va("Альянс [#1]", $ally['tag']), "Общее сообщение", va("Игрок #1 исключён из альянса.", $leaver['oname']), 0);
        }

        // Сообщение игроку об исключении.
        SendMessage ( $leaver['player_id'], 
                               va("Альянс [#1]", $ally['tag']), 
                               va("Членство в альянсе [#1] окончено", $ally['tag']), 
                               va("Игрок #1 исключает Вас из альянса [#2] .<br>Теперь Вы можете зарегистрироваться снова", $GlobalUser['oname'], $ally['tag']), 0);
    }

    if ( method() === "POST" && $_GET['a'] == 16 && $selected_user)        // Назначить ранг игроку
    {
        $newrank = intval($_POST['newrang']);
        $query = "UPDATE ".$db_prefix."users SET allyrank = $newrank WHERE player_id = $selected_user";
        dbquery ($query);
    }

    $now = time ();
    $members = CountAllyMembers ( $ally['ally_id'] );

    $sort1 = intval ($_GET['sort1']);
    $sort2 = intval ($_GET['sort2']) ^ 1;

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script><br>
<a href="index.php?page=allianzen&session=<?=$session;?>&a=5">Назад к обзору</a>
<table width=519>
<tr><td class='c' colspan='10'>Список членов (кол-во: <?=$members;?>)</td></tr>
<tr>
    <th>N</th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=1&sort2=<?=$sort2;?>">Имя</a></th>
    <th> </th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=2&sort2=<?=$sort2;?>">Статус</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=3&sort2=<?=$sort2;?>">Очки</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=0&sort2=<?=$sort2;?>">Координаты</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=4&sort2=<?=$sort2;?>">Вступление</a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=5&sort2=<?=$sort2;?>">Неактивный</a></th>
    <th>Функция</th></tr>

<?php
    $result = EnumerateAlly ($ally['ally_id'], intval ($_GET['sort1']), intval ($_GET['sort2']));
    for ($i=0; $i<$members; $i++)
    {
        $user = dbarray ($result);
        $rank = LoadRank ( $user['ally_id'], $user['allyrank'] );
        $hplanet = GetPlanet ($user['hplanetid']);
        $days = floor ( ( $now - $user['lastclick'] ) / (60 * 60 * 24) );
        echo "<tr>";
        echo "<th>".($i+1)."</th>";
        echo "<th>".$user['oname']."</th>";
        if ( $GlobalUser['player_id'] != $user['player_id'] ) {
            echo "<th><a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\"><img src=\"".UserSkin()."img/m.gif\" border=0 alt=\"Написать сообщение\"></a></th>";
        }
        else echo "<th></th>";
        echo "<th>".$rank['name']."</th>";
        echo "<th>".nicenum($user['score1'] / 1000)."</th>";
        echo "<th><a href=\"index.php?page=galaxy&galaxy=".$hplanet['g']."&system=".$hplanet['s']."&position=".$hplanet['p']."&session=$session\" >[".$hplanet['g'].":".$hplanet['s'].":".$hplanet['p']."]</a></th>";
        echo "<th>".date ("Y-m-d H:i:s", $user['joindate'])."</th>";
        echo "<th>".$days."d</th>";
        if ( $user['allyrank'] > 0 ) {
            echo "<th>";
            echo "<a onmouseover='return overlib(\"<font color=white>Выгнать игрока</font>\", WIDTH, 100);' onmouseout='return nd();' alt='Выгнать игрока' href='javascript:if(confirm(\"Вы уверены, что игрок ".$user['oname']." должен покинуть альянс?\"))document.location=\"index.php?page=allianzen&session=$session&a=13&u=".$user['player_id']."\"';>";
            echo "<img src='".UserSkin()."pic/abort.gif' alt='Выгнать игрока' border='0' ></a>";
            echo "<a onmouseover=\"return overlib('<font color=white>Назначить ранг</font>', WIDTH, 100);\" onmouseout='return nd();' alt='Назначить ранг' href=\"index.php?page=allianzen&session=$session&a=7&u=".$user['player_id']."\">";
            echo "<img src=\"".UserSkin()."pic/key.gif\" alt='Назначить ранг' border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "</th>";
            echo "</tr>\n";

            if ( $user['player_id'] == $selected_user )        // Вывести форму для задания ранга.
            {
                $rank_result = EnumRanks ( $ally['ally_id'] );
                $rows = dbrows ($rank_result);
                echo "<form action=\"index.php?page=allianzen&session=$session&a=16&u=$selected_user\" method=POST><tr><th colspan=3>Ранг для ".$user['oname'].":</th><th><select name=\"newrang\">";
                while ($rows--)
                {
                    $user_rank = dbarray ( $rank_result );
                    if ($user_rank['rank_id'] == 0) continue;
                    echo "<option value=\"".$user_rank['rank_id']."\"";
                    if ($user_rank['rank_id'] == $user['allyrank'] ) echo " SELECTED";
                    echo ">".$user_rank['name']."\n";
                }
                echo "</th><th colspan=5><input type=submit value=\"Сохранить\"></th></tr></form>\n";
            }
        }
        else echo "<th>&nbsp;</th></tr>\n";
    }
?>

</table>
<?php
}

?>