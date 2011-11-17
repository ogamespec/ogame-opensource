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
21          Атака убывает (паровоз САБ)
121       Атака возвращается (паровоз САБ)
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
        $res .= "Численность кораблей: $sum &lt;br&gt;";
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
    $mission = $fleet_entry['mission'];
    $origin = GetPlanet ( $fleet_entry['origin_id'] );
    $target = GetPlanet ( $fleet_entry['target_id'] );
    $fleet = $fleet_entry;
    $direction = $fleet_entry['dir'];
    $owner = LoadUser ( $origin['owner_id'] );

    if ( $mission == 1 ) {
        if ( $direction ) echo "<span class='flight phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Атаковать</span>";
        else echo "<span class='return phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Атаковать</span></span>";
    }
    else if ( $mission == 3 ) {
        if ( $direction ) echo "<span class='flight phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Транспорт</span>";
        else echo "<span class='return phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Транспорт</span></span>";
    }
    else if ( $mission == 4 ) {
        echo "<span class='flight phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Оставить</span>";
    }
    else if ( $mission == 6 ) {
        if ( $direction ) echo "<span class='flight phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Шпионаж</span>";
        else echo "<span class='return phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Шпионаж</span></span>";
    }
    else if ( $mission == 8 ) {
        echo "<span class='return phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Переработать</span></span>";
    }
    else echo "Unknown mission LOL $mission";
}

function GetMission ( $fleet_obj )
{
    if ( $fleet_obj['mission'] < 100 ) return $fleet_obj['mission'];
    else if ( $fleet_obj['mission'] < 200 ) return $fleet_obj['mission'] - 100;
    else return $fleet_obj['mission'] - 200;
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

        // Не показывать отправление и возврат Оставить.
        if ( $fleet_obj['mission'] == 104 ) continue;
        if ( $fleet_obj['mission'] == 4 && $fleet_obj['start_planet'] == $planet_id ) continue;

        $queue = GetFleetQueue ($fleet_obj['fleet_id']);

        // Время прибытия
        if ( $fleet_obj['mission'] < 100 && $fleet_obj['start_planet'] == $planet_id ) $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['flight_time'];
        else $task[$tasknum]['end_time'] = $queue['end'];

        // Направление.

        // Флот
        $task[$tasknum]['fleets'] = 1;
        $task[$tasknum]['fleet'][0] = array ();
        foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
        $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
        $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
        $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
        $task[$tasknum]['fleet'][0]['mission'] = GetMission ( $fleet_obj );
        if ( $fleet_obj['target_planet'] == $planet_id ) $task[$tasknum]['fleet'][0]['dir'] = 1;    // на планету
        else $task[$tasknum]['fleet'][0]['dir'] = 0;    // возврат

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
            else if ($t['direction'] == 1) echo "<tr class='flight'>\n";
            else if ($t['direction'] == 0) echo "<tr class='return'>\n";
            else if ($t['direction'] == 2) echo "<tr class='holding'>\n";
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