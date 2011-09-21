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
        case "Build":
            $planet = GetPlanet ($sub_id);
            return "Постройка '".loca("NAME_$obj_id")."' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "Demolish":
            $planet = GetPlanet ($sub_id);
            return "Снос '".loca("NAME_$obj_id")."' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "Shipyard":
            $planet = GetPlanet ($sub_id);
            return "Задание на верфи: '".loca("NAME_$obj_id") . "' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "Research":
            $planet = GetPlanet ($sub_id);
            return "Ведется исследование '".loca("NAME_$obj_id") . "' ($level) с планеты <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "DeleteAccount": return "Удалить аккаунт";
        case "RecalcPoints": return "Пересчитать статистику";
        case "UnloadAll": return "Отгрузить всех игроков";
    }

    return "Неизвестный тип задания (type=$type, sub_id=$sub_id, obj_id=$obj_id, level=$level)";
}

function Admin_Queue ()
{
    global $session;
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue ORDER BY end ASC, prio DESC LIMIT 50";
    $result = dbquery ($query);
    $now = time ();

    echo "<table >\n";
    echo "<tr><td class=c>Время окончания</td><td class=c>Игрок</td><td class=c>Тип задания</td><td class=c>Описание</td><td class=c>Приоритет</td></tr>\n";

    $anz = $rows = dbrows ($result);
    $bxx = 1;
    while ($rows--) 
    {
        $queue = dbarray ( $result );
        $user = LoadUser ( $queue['owner_id'] );
        $player_id = $user['player_id'];
        echo "<tr><th> <table><tr><th><div id='bxx".$bxx."' title='".($queue['end'] - $now)."' star='".$queue['start']."'></th>";
        echo "<tr><th>".date ("d.m.Y H:i:s", $queue['end'])."</th></tr></table></th><th><a href=\"index.php?page=admin&session=$session&mode=Users&player_id=$player_id\">".$user['oname']."</a></th><th>".$queue['type']."</th><th>".QueueDesc($queue)."</th><th>".$queue['prio']."</th></tr>\n";
        $bxx++;
    }
    echo "<script language=javascript>anz=$anz;t();</script>\n";

    echo "</table>\n";
}

?>