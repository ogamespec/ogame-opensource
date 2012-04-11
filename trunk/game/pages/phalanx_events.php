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

function PlanetOn ($planet, $mission)
{
    $res = "";
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

    if ( $mission == 1 ) {    // Атака
        if ( $direction ) echo "<span class='flight phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Атаковать</span>";
        else echo "<span class='return phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Атаковать</span></span>";
    }
    else if ( $mission == 2 ) {    // Совместная атака
        if ( $direction ) echo "<span class='phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "federation")." отправлен на ".PlanetTo($target, "federation").". Задание: Совместная атака</span>";
        else echo "<span class='return phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Совместная атака</span></span>";
    }
    else if ( $mission == 21 ) {    // Атака САБ
        if ( $direction ) echo "<span class='phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "attack")." отправлен на ".PlanetTo($target, "attack").". Задание: Атаковать</span>";
        else echo "<span class='return phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Атаковать</span></span>";
    }
    else if ( $mission == 3 ) {    // Транспорт
        if ( $direction ) echo "<span class='flight phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Транспорт</span>";
        else echo "<span class='return phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Транспорт</span></span>";
    }
    else if ( $mission == 4 ) {    // Оставить
        echo "<span class='flight phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Оставить</span>";
    }
    else if ( $mission == 5 ) {    // Держаться
        if ( $direction == 2 ) echo "<span class='holding phalanx_fleet'>".PlayerDetails($owner)." удерживает альянсовый <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> с ".PlanetFrom($origin, "phalanx_fleet")." на орбите ".PlanetOn($target, "phalanx_fleet").". Задание: <span class='ownclass'>Держаться</span></span>";
        else if ( $direction == 1 ) echo "<span class='flight phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Держаться</span>";
        else echo "<span class='return phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Держаться</span></span>";
    }
    else if ( $mission == 6 ) {    // Шпионаж
        if ( $direction ) echo "<span class='flight phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Шпионаж</span>";
        else echo "<span class='return phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Шпионаж</span></span>";
    }
    else if ( $mission == 7 ) {    // Колонизировать
        echo "<span class='return phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Колонизировать</span></span>";
    }
    else if ( $mission == 8 ) {    // Переработать
        echo "<span class='return phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Переработать</span></span>";
    }
    else if ( $mission == 9 ) {    // Уничтожить (хммм...)
        if ( $direction ) echo "<span class='flight phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с планеты ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Уничтожить</span>";
        else echo "<span class='return phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Уничтожить</span></span>";
    }
    else if ( $mission == 15 ) {    // Экспедиция
        if ( $direction == 2 ) echo "<span class='holding phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "phalanx_fleet")." исследует позицию ".PlanetTo($target, "phalanx_fleet").". Задание: <span class='ownclass'>Экспедиция</span></span>";
        else if ( $direction == 1 ) echo "<span class='flight phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "phalanx_fleet")." отправлен на ".PlanetTo($target, "phalanx_fleet").". Задание: Экспедиция</span>";
        else echo "<span class='return phalanx_fleet'>Мирный <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> возвратится с ".PlanetFrom($target, "phalanx_fleet")." на ".PlanetTo($origin, "phalanx_fleet").". Задание: <span class='ownclass'>Экспедиция</span></span>";
    }
    else if ($mission == 20 ) {    // Ракетная атака
        echo "<span class='missile'>Ракетная атака (".$fleet_entry['ipm_amount'].") с ".PlanetFrom($origin, "phalanx_fleet")." на ".PlanetTo($target, "phalanx_fleet")." ";
        if ( $fleet_entry['ipm_target'] > 0 ) echo "Основная цель " . loca ("NAME_".$fleet_entry['ipm_target']);
        echo "</span>";
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
    $user = LoadUser ($planet['owner_id']);
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $result = EnumPlanetFleets ( $planet_id );
    $rows = dbrows ( $result );

    $task = array ();
    $tasknum = 0;

    $unions = array ();

    while ($rows--)
    {
        $fleet_obj = dbarray ($result);
        $queue = GetFleetQueue ($fleet_obj['fleet_id']);

        // Союзные флоты собираются отдельно
        if ( $fleet_obj['union_id'] > 0 && $fleet_obj['target_planet'] == $planet_id && !$unions[ $fleet_obj['union_id'] ])
        {
            $task[$tasknum]['end_time'] = $queue['end'];

            // Флоты
            $acs_result = EnumUnionFleets ( $fleet_obj['union_id'] );
            $task[$tasknum]['fleets'] = $acs_rows = dbrows ( $acs_result );
            $f = 0;
            while ($acs_rows--)
            {
                $fleet_obj = dbarray ($acs_result);

                $task[$tasknum]['fleet'][$f] = array ();
                foreach ( $fleetmap as $id=>$gid ) $task[$tasknum]['fleet'][$f][$gid] = $fleet_obj["ship$gid"];
                $task[$tasknum]['fleet'][$f]['owner_id'] = $fleet_obj['owner_id'];
                $task[$tasknum]['fleet'][$f]['origin_id'] = $fleet_obj['start_planet'];
                $task[$tasknum]['fleet'][$f]['target_id'] = $fleet_obj['target_planet'];
                $task[$tasknum]['fleet'][$f]['mission'] = GetMission ($fleet_obj);
                $task[$tasknum]['fleet'][$f]['dir'] = 1;    // на планету
                $f++;
            }
            $unions[ $fleet_obj['union_id'] ] = 1;

            $tasknum++;
            continue;
        }

        if ( $fleet_obj['union_id'] > 0 && $fleet_obj['target_planet'] == $planet_id && $fleet_obj['mission'] != 21 ) continue;

        // Не показывать отправление и возврат Оставить.
        if ( $fleet_obj['mission'] == 104 ) continue;
        if ( $fleet_obj['mission'] == 4 && $fleet_obj['start_planet'] == $planet_id ) continue;

        // Не показывать возвращающиеся с целевой планеты флоты.
        if ( ($fleet_obj['mission'] > 100 && $fleet_obj['mission'] < 200) && $fleet_obj['target_planet'] == $planet_id ) continue;

        // Время прибытия
        if ( $fleet_obj['mission'] < 100 && $fleet_obj['start_planet'] == $planet_id ) {
            if ($fleet_obj['mission'] != 15) $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['flight_time'];
            else $task[$tasknum]['end_time'] = $queue['end'];
        }
        else $task[$tasknum]['end_time'] = $queue['end'];

        // Флот
        $task[$tasknum]['fleets'] = 1;
        $task[$tasknum]['fleet'][0] = array ();
        foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
        $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
        $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
        $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
        $task[$tasknum]['fleet'][0]['mission'] = GetMission ( $fleet_obj );
        if ( GetMission($fleet_obj) == 15 )
        {
            if ($fleet_obj['mission'] < 100) $task[$tasknum]['fleet'][0]['dir'] = 1;
            else if ($fleet_obj['mission'] < 200) $task[$tasknum]['fleet'][0]['dir'] = 0;
            else $task[$tasknum]['fleet'][0]['dir'] = 2;
        }
        else if ( GetMission($fleet_obj) == 5 )
        {
            if ($fleet_obj['mission'] < 100) $task[$tasknum]['fleet'][0]['dir'] = 1;
            else if ($fleet_obj['mission'] < 200) $task[$tasknum]['fleet'][0]['dir'] = 0;
            else $task[$tasknum]['fleet'][0]['dir'] = 2;
        }
        else
        {
            if ( $fleet_obj['target_planet'] == $planet_id ) $task[$tasknum]['fleet'][0]['dir'] = 1;    // на планету
            else $task[$tasknum]['fleet'][0]['dir'] = 0;    // возврат
        }
        if ($fleet_obj['mission'] == 20)
        {
            $task[$tasknum]['fleet'][0]['ipm_amount'] = $fleet_obj['ipm_amount'];
            $task[$tasknum]['fleet'][0]['ipm_target'] = $fleet_obj['ipm_target'];
        }

        $tasknum++;

        // Для убывающей экспедиции добавить псевдозадание удерживания.
        // Не показывать чужие флоты.
        if ( $fleet_obj['mission'] == 15 && $fleet_obj['owner_id'] == $user['player_id'] )
        {
            // Время отправления и прибытия
            $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'];

            // Флот
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 2;
            $tasknum++;
        }

        // Для прибывающего задания Держаться добавить псевдозадание удерживания.
        if ( $fleet_obj['mission'] == 5 && $fleet_obj['owner_id'] != $user['player_id'] )
        {
            // Время отправления и прибытия
            $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'];

            // Флот
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 2;
            $tasknum++;
        }

        // Для убывающих или удерживаемых экспедиций добавить псевдозадание возврата.
        if ( ($fleet_obj['mission'] == 15 || $fleet_obj['mission'] == 215) && $fleet_obj['owner_id'] == $user['player_id'] )
        {
            // Время отправления и прибытия
            if ( $fleet_obj['mission'] > 200) $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'];
            else $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'] + $fleet_obj['flight_time'];

            // Флот
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 0;
            $tasknum++;
        }

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