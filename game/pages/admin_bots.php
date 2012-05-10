<?php


// Управление ботами

function Admin_Bots ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $result = "";

    // Обработка POST-запроса.
    if ( method () === "POST" )
    {
        if ( AddBot ( $_POST['name'] ) ) $result = "<font color=lime>Бот успешно добавлен.</font>";
        else $result = "<font color=red>Игрок с таким именем уже существует.</font>";
    }

    // Обработка GET-запроса.
    if ( method () === "GET" )
    {
        StopBot ( intval ($_GET['id']) );
        $result = "<font color=lime>Бот остановлен.</font>";
    }

?>

<?=AdminPanel();?>

<center><?=$result;?></center>

<h2>Список ботов:</h2>

<?php

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'AI' GROUP BY owner_id";
    $result = dbquery ( $query );
    $rowss = $rows = dbrows ($result);
    if ( $rows == 0 ) echo "Ботов не обнаружено<br>";
    else {
        echo "<table>\n";
        echo "<tr><td class=c>ID</td><td class=c>Имя</td><td class=c>Главная планета</td><td class=c>Действие</td></tr>\n";
    }
    while ($rows--) {
        $queue = dbarray ($result);
        $user = LoadUser ( $queue['owner_id'] );
        $planet = GetPlanet ( $user['hplanetid'] );
        echo "<tr>";
        echo "<td>".$user['player_id']."</td>";
        echo "<td>".AdminUserName ($user)."</td>";
        echo "<td>". AdminPlanetName ($planet). " " . AdminPlanetCoord($planet) . "</td>";
        echo "<td><a href=\"index.php?page=admin&session=$session&mode=Bots&action=stop&id=".$user['player_id']."\">Остановить</a></td>";
        echo "</tr>\n";
    }
    if ( $rowss ) echo "</table>";
?>

<h2>Добавить бота:</h2>

<form action="index.php?page=admin&session=<?=$session;?>&mode=Bots" method="POST">
<table>
<tr><td>Имя <input type=text size=10 name="name" /> <input type=submit value="Отправить" /></td></tr>
</table>
</form>

<?php
}
?>