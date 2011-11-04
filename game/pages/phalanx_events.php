<?php

// Создание списка событий Фаланги.

/*
Типы заданий:

1          Атака убывает
101       Атака возвращается
2          Совместная атака убывает
102      Совместная атака возвращается
3         Транспорт убывает
103      Транспорт возвращается
4         Оставить убывает
104     Оставить возвращается
5         Держаться убывает
105      Держаться возвращается
205     Держаться на орбите
6         Шпионаж убывает
106      Шпионаж возвращается
7         Колонизировать убывает
107      Колонизировать возвращается
8         Переработать убывает
108     Переработать возвращается
9         Уничтожить убывает
109      Уничтожить возвращается
15        Экспедиция убывает
115      Экспедиция возвращается
215      Экспедиция на орбите
20        Ракетная атака
*/

function sksort (&$array, $subkey="id", $sort_ascending=false) 
{
    if (count($array))
        $temp_array[key($array)] = array_shift($array);

    foreach($array as $key => $val){
        $offset = 0;
        $found = false;
        foreach($temp_array as $tmp_key => $tmp_val)
        {
            if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
            {
                $temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
                                            array($key => $val),
                                            array_slice($temp_array,$offset)
                                          );
                $found = true;
            }
            $offset++;
        }
        if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
    }

    if ($sort_ascending) $array = array_reverse($temp_array);

    else $array = $temp_array;
}

function OverFleet ($fleet, $summary)
{
    $res = "&lt;font color=white&gt;&lt;b&gt;";
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $sum = 0;
    if ( $summary ) {
        foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
        $res .= "Численность кораблей: 1 &lt;br&gt;";
    }
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet[$gid];
        if ( $amount > 0 ) $res .= loca ("NAME_$gid") . " " . nicenum($amount) . "&lt;br&gt;";
    }
    $res .= "&lt;/b&gt;&lt;/font&gt;";
    return $res;
}

function TitleFleet ($fleet, $summary)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $sum = 0;
    if ( $summary ) {
        foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
        $res = "Численность кораблей: $sum ";
    }
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet[$gid];
        if ( $amount > 0 ) $res .= loca ("NAME_$gid") . " " . nicenum($amount);
    }
    return $res;
}

function PlayerDetails ($user)
{
    return $user['oname'] . " <a href='#' onclick='showMessageMenu(".$user['player_id'].")'><img src='".UserSkin()."img/m.gif' title='Написать сообщение' alt='Написать сообщение'></a>";
}

function PlanetFrom ($planet, $mission)
{
    $res = "";
    if ( GetPlanetType ($planet) == 1 ) $res .= "планеты";
    if ( $planet['type'] == 10002 || $planet['type'] == 20000 ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function PlanetTo ($planet, $mission)
{
    $res = "";
    if ( GetPlanetType ($planet) == 1 ) $res .= "планету";
    if ( $planet['type'] == 10002 || $planet['type'] == 20000 ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function FleetSpan ( $fleet_entry )
{
/*
    $mission = $fleet_entry['mission'];
    $assign = $fleet_entry['assign'];
    $dir = $fleet_entry['dir'];
    $dir = $dir | ($assign << 4);
    $origin = GetPlanet ( $fleet_entry['origin_id'] );
    $target = GetPlanet ( $fleet_entry['target_id'] );
    $fleet = $fleet_entry;
    $owner = LoadUser ( $origin['owner_id'] );

    if (0) {}

//<span class='flight phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib("&lt;font color=white&gt;&lt;b&gt;Численность кораблей: 9 &lt;br&gt;Большой транспорт 9&lt;br&gt;&lt;/b&gt;&lt;/font&gt;");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='Численность кораблей: 9 Большой транспорт 9'></a> игрока ALLIOT <a href='#' onclick='showMessageMenu(169773)'><img src='http://localhost/evolution/img/m.gif' title='Написать сообщение' alt='Написать сообщение'></a> с планеты Io <a href="javascript:showGalaxy(1,457,4)" phalanx_fleet>[1:457:4]</a> отправлен на планету Jupiter <a href="javascript:showGalaxy(1,274,12)" phalanx_fleet>[1:274:12]</a>. Задание: Транспорт</span>
//<span class='flight phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib("&lt;font color=white&gt;&lt;b&gt;Численность кораблей: 2 &lt;br&gt;Большой транспорт 2&lt;br&gt;&lt;/b&gt;&lt;/font&gt;");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='Численность кораблей: 2 Большой транспорт 2'></a> игрока Miles <a href='#' onclick='showMessageMenu(171908)'><img src='http://localhost/evolution/img/m.gif' title='Написать сообщение' alt='Написать сообщение'></a> с планеты Oli <a href="javascript:showGalaxy(1,245,6)" phalanx_fleet>[1:245:6]</a> отправлен на планету Andromeda <a href="javascript:showGalaxy(1,263,9)" phalanx_fleet>[1:263:9]</a>. Задание: Транспорт</span>
//<span class='return phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib("&lt;font color=white&gt;&lt;b&gt;Численность кораблей: 7 &lt;br&gt;Переработчик 7&lt;br&gt;&lt;/b&gt;&lt;/font&gt;");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='Численность кораблей: 7 Переработчик 7'></a> возвратится с  Поле обломков <a href="javascript:showGalaxy(1,256,7)" phalanx_fleet>[1:256:7]</a> на планету Jupiter <a href="javascript:showGalaxy(1,274,12)" phalanx_fleet>[1:274:12]</a>. Задание: <span class='ownclass'>Переработать</span></span>
//<span class='return phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib("&lt;font color=white&gt;&lt;b&gt;Численность кораблей: 22 &lt;br&gt;Большой транспорт 2&lt;br&gt;Бомбардировщик 20&lt;br&gt;&lt;/b&gt;&lt;/font&gt;");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='Численность кораблей: 22 Большой транспорт 2Бомбардировщик 20'></a> возвратится с планеты Колония <a href="javascript:showGalaxy(1,263,11)" phalanx_fleet>[1:263:11]</a> на планету Jupiter <a href="javascript:showGalaxy(1,274,12)" phalanx_fleet>[1:274:12]</a>. Задание: <span class='ownclass'>Атаковать</span></span>
//<span class='flight phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib("&lt;font color=white&gt;&lt;b&gt;Численность кораблей: 3 &lt;br&gt;Шпионский зонд 3&lt;br&gt;&lt;/b&gt;&lt;/font&gt;");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='Численность кораблей: 3 Шпионский зонд 3'></a> игрока badi <a href='#' onclick='showMessageMenu(169771)'><img src='http://localhost/flot/img/m.gif' title='Написать сообщение' alt='Написать сообщение'></a> с планеты Zavod ELOK <a href="javascript:showGalaxy(1,271,8)" phalanx_fleet>[1:271:8]</a> отправлен на планету Колония <a href="javascript:showGalaxy(1,260,6)" phalanx_fleet>[1:260:6]</a>. Задание: Шпионаж</span>

    else echo "Задание Тип:$mission, Dir:$dir, Флот: " .TitleFleet($fleet,0). ", с " .PlanetFrom($origin, ""). " на " .PlanetTo($target, "") ;
*/
}

function PhalanxEventList ($planet_id)
{
    $planet = GetPlanet ($planet_id);
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $result = EnumPlanetFleets ( $planet_id );
    $rows = dbrows ( $result );

    $task = array ();
    $tasknum = 0;

    while ($rows--)
    {
        $fleet_obj = dbarray ($result);

        if ( $fleet_obj['union_id'] > 0 ) continue;        // Союзные флоты собираются отдельно

        $queue = GetFleetQueue ($fleet_obj['fleet_id']);

        // Время отправления и прибытия
        $task[$tasknum]['start_time'] = $queue['start'];
        if ( $fleet_obj['mission'] < 100 && $fleet_obj['start_planet'] == $planet_id ) $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['flight_time'];
        else $task[$tasknum]['end_time'] = $queue['end'];

        // Флот
        $task[$tasknum]['fleets'] = 1;
        $task[$tasknum]['fleet'][0] = array ();
        foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
        $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
        $task[$tasknum]['fleet'][0]['m'] = $fleet_obj['m'];
        $task[$tasknum]['fleet'][0]['k'] = $fleet_obj['k'];
        $task[$tasknum]['fleet'][0]['d'] = $fleet_obj['d'];
        $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
        $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
        $task[$tasknum]['fleet'][0]['mission'] = $fleet_obj['mission'];

        $tasknum++;
    }

    $anz = 0;
    if ($tasknum > 0)
    {
        sksort ( $task, 'end_time', true);        // Сортировать по времени прибытия.
        $now = time ();

        foreach ($task as $i=>$t)
        {
            $seconds = max($t['end_time']-$now, 0);
            if ( $seconds <= 0 ) continue;
            if ($t['fleets'] > 1) echo "<tr class=''>\n";
            else if ($t['dir'] == 0) echo "<tr class='flight'>\n";
            else if ($t['dir'] == 1) echo "<tr class='return'>\n";
            else if ($t['dir'] == 2) echo "<tr class='holding'>\n";
            echo "<th><div id='bxx".($i+1)."' title='".$seconds."'star='".$t['end_time']."'></div></th>\n";
            echo "<th colspan='3'>";
            for ($fl=0; $fl<$t['fleets']; $fl++)
            {
                echo FleetSpan ($t['fleet'][$fl]);
                if ($t['fleets'] > 1) echo "<br /><br />";
            }
            echo "</th></tr>\n\n";
            $anz++;
        }
        if ($anz) echo "<script language=javascript>anz=".$anz.";t();</script>\n\n";
    }
}

?>