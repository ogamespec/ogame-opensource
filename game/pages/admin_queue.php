<?php

// ========================================================================================
// Глобальная очередь событий.

function QueueDesc ( $queue )
{
    global $session;
    $type = $queue['type'];
    $sub_id = $queue['sub_id'];
    $obj_id = $queue['obj_id'];
    $level = $queue['level'];

    switch ( $type )
    {
        case "BuildStart":
            $planet = GetPlanet ($sub_id);
            return "Постройка начать '".loca("NAME_$obj_id")."' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "DemolishStart":
            $planet = GetPlanet ($sub_id);
            return "Снос начать '".loca("NAME_$obj_id")."' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "BuildEnd":
            $planet = GetPlanet ($sub_id);
            return "Постройка завершить '".loca("NAME_$obj_id")."' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "DemolishEnd":
            $planet = GetPlanet ($sub_id);
            return "Снос завершить '".loca("NAME_$obj_id")."' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "Shipyard":
            $planet = GetPlanet ($sub_id);
            return "Задание на верфи: '".loca("NAME_$obj_id") . "' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "Research":
            $planet = GetPlanet ($sub_id);
            return "Ведется исследование '".loca("NAME_$obj_id") . "' ($level) с планеты <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "UpdateStats": return "Сохранить старую статистику";
        case "RecalcPoints": return "Пересчитать статистику";
        case "RecalcAllyPoints": return "Пересчитать статистику альянсов";
        case "AllowName": return "Разрешить сменить имя";
        case "UnloadAll": return "Отгрузить всех игроков";
        case "CleanDebris": return "Чистка виртуальных ПО";
        case "CleanPlanets": return "Чистка уничтоженных планет";
        case "CleanPlayers": return "Удаление неактивных игроков и поставленных на удаление";
        case "UnbanPlayer": return "Разбанить игрока";
        case "AllowAttacks": return "Разрешить атаки";
        case "AI":
            $planet = GetPlanet ($sub_id);
            return "Задание бота с планеты " . AdminPlanetName ( $planet ) . ", стратегия: " . GetBotStrategy($obj_id);

        case "CommanderOff": return "Заканчивается офицер: Командир";
        case "AdmiralOff": return "Заканчивается офицер: Адмирал";
        case "EngineerOff": return "Заканчивается офицер: Инженер";
        case "GeologeOff": return "Заканчивается офицер: Геолог";
        case "TechnocrateOff": return "Заканчивается офицер: Технократ";

    }

    return "Неизвестный тип задания (type=$type, sub_id=$sub_id, obj_id=$obj_id, level=$level)";
}

function Admin_Queue ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    // Обработка POST-запросов.
    $player_id = 0;
    if ( method () === "POST" )
    {

        if ( key_exists ( "player", $_POST ) ) {        // Фильтр по имени игрока
            $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '".$_POST['player']."%'";
            $result = dbquery ( $query );
            if ( dbrows ($result) > 0 ) {
                $user = dbarray ($result);
                $player_id = $user['player_id'];
            }
        }

        if ( key_exists ( "order_end", $_POST ) && $GlobalUser['admin'] >= 2 ) {        // Завершить задание
            $id = intval ($_POST['order_end']);
            $now = time ();
            $query = "UPDATE ".$db_prefix."queue SET end=$now WHERE task_id=$id";
            dbquery ( $query );
        }

        if ( key_exists ( "order_remove", $_POST ) && $GlobalUser['admin'] >= 2 ) {        // Удалить задание
            RemoveQueue ( intval ($_POST['order_cancel']), 0 );
        }
    }

    if ( $player_id > 0 ) $query = "SELECT * FROM ".$db_prefix."queue WHERE type <> 'Fleet' AND owner_id=$player_id ORDER BY end ASC, prio DESC";
    else $query = "SELECT * FROM ".$db_prefix."queue WHERE type <> 'Fleet' ORDER BY end ASC, prio DESC LIMIT 50";
    $result = dbquery ($query);
    $now = time ();

    AdminPanel();

    echo "<table>\n";
    echo "<tr><td class=c>Время окончания</td><td class=c>Игрок</td><td class=c>Тип задания</td><td class=c>Описание</td><td class=c>Приоритет</td><td class=c>Управление</td></tr>\n";

    $anz = $rows = dbrows ($result);
    $bxx = 1;
    while ($rows--) 
    {
        $queue = dbarray ( $result );
        $user = LoadUser ( $queue['owner_id'] );
        $pid = $user['player_id'];
        echo "<tr><th> <table><tr><th><div id='bxx".$bxx."' title='".($queue['end'] - $now)."' star='".$queue['start']."'></th>";
        echo "<tr><th>".date ("d.m.Y H:i:s", $queue['end'])."</th></tr></table></th><th><a href=\"index.php?page=admin&session=$session&mode=Users&player_id=$pid\">".$user['oname']."</a></th><th>".$queue['type']."</th><th>".QueueDesc($queue)."</th><th>".$queue['prio']."</th>\n";
?>
    <th> 
         <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST">
    <input type="hidden" name="order_end" value="<?=$queue['task_id'];?>" />
        <input type="submit" value="Завершить" />
     </form>
         <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST" style="border: 1px solid red">
    <input type="hidden" name="order_remove" value="<?=$queue['task_id'];?>" />
        <input type="submit" value="Удалить" />
     </form>
    </th>
</tr>
<?php
        $bxx++;
    }
    echo "<script language=javascript>anz=$anz;t();</script>\n";

    echo "</table>\n";

    $playername = "";
    if ( $player_id > 0)
    {
        $user = LoadUser ( $player_id );
        $playername = $user['name'];
    }
?>

    <br/>
    <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST">
    Показать задания игрока : <input size=15 name="player" value="<?=$playername;?>">
    <input type="submit" value="Отправить">
    </form>

<?php
}

?>