<?php

// Создание списка событий Обзора.

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
    $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function PlanetTo ($planet, $mission)
{
    $res = "";
    if ( GetPlanetType ($planet) == 1 ) $res .= "планету";
    $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function Cargo ($m, $k, $d, $mission, $text)
{
    if ( ($m + $k + $d) != 0 ) {
        return "<a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;Транспорт: &lt;br /&gt; Металл: ".nicenum($m)."&lt;br /&gt;Кристалл: ".nicenum($k)."&lt;br /&gt;Дейтерий: ".nicenum($d)."&lt;/b&gt;&lt;/font&gt;\");' " .
                  "onmouseout='return nd();'' class='$mission'>$text</a><a href='#' title='Транспорт: Металл: ".nicenum($m)." Кристалл: ".nicenum($k)." Дейтерий: ".nicenum($d)."'></a>";
    }
    else return "<span class='class'>$text</span>";
}

function FleetSpan ( $task, $fleet_num )
{
    $mission = $task['mission'];
    $assign = $task['assign'];
    $dir = $task['dir'];
    $dir = $dir | ($assign << 4);
    $origin = GetPlanet ( $task['fleet'][$fleet_num]['origin_id'] );
    $target = GetPlanet ( $task['fleet'][$fleet_num]['target_id'] );
    $fleet = $task['fleet'][$fleet_num];
    $owner = LoadUser ( $origin['owner_id'] );
    $m = $task['fleet'][$fleet_num]['m'];
    $k = $task['fleet'][$fleet_num]['k'];
    $d = $task['fleet'][$fleet_num]['d'];

    if (0) {}
    else if ($mission == 4)            // Оставить
    {
        if ($dir == 0) echo "<span class='flight owndeploy'>Ваш <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,0)."\");' onmouseout='return nd();' class='owndeploy'>флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "owndeploy")." отправлен на ".PlanetTo($target, "owndeploy").". Задание: ".Cargo($m,$k,$d,"owndeploy","Оставить")."</span>";
        else if ($dir == 1) echo "<span class='return owndeploy'>Ваш <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,0)."\");' onmouseout='return nd();' class='owndeploy'>флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "owndeploy")." отправлен на ".PlanetTo($target, "owndeploy").". Задание: ".Cargo($m,$k,$d,"owndeploy","Оставить")."</span>";
    }
    else if ($mission == 6)            // Шпионаж
    {
        if ($dir == 0) echo "<span class='flight ownespionage'>Ваш <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,0)."\");' onmouseout='return nd();' class='ownespionage'>флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownespionage")." отправлен на ".PlanetTo($target, "ownespionage").". Задание: ".Cargo($m,$k,$d,"ownespionage","Шпионаж")."</span>";
        else if ($dir == 1) echo "<span class='return ownespionage'>Ваш <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,0)."\");' onmouseout='return nd();' class='ownespionage'>флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownespionage")." отправлен на ".PlanetTo($target, "ownespionage").". Задание: ".Cargo($m,$k,$d,"ownespionage","Шпионаж")."</span>";
        else if ($dir == 0x10) echo "<span class='flight espionage'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='espionage'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "espionage")." отправлен на ".PlanetTo($target, "espionage").". Задание: Шпионаж</span>";
    }
    else if ($mission == 8)            // Переработать
    {
        if ($dir == 0) echo "<span class='flight ownharvest'>Ваш <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,0)."\");' onmouseout='return nd();' class='ownharvest'>флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownharvest")." отправлен на ".PlanetTo($target, "ownharvest").". Задание: ".Cargo($m,$k,$d,"ownharvest","Переработать")."</span>";
        else if ($dir == 1) echo "<span class='return ownharvest'>Ваш <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,0)."\");' onmouseout='return nd();' class='ownharvest'>флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownharvest")." отправлен на ".PlanetTo($target, "ownharvest").". Задание: ".Cargo($m,$k,$d,"ownharvest","Переработать")."</span>";
    }
    else if ($mission == 9)            // Уничтожить
    {
        if ($dir == 0) echo "<span class='flight owndestroy'>Ваш <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,0)."\");' onmouseout='return nd();' class='owndestroy'>флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "owndestroy")." отправлен на ".PlanetTo($target, "owndestroy").". Задание: ".Cargo($m,$k,$d,"owndestroy","Уничтожить")."</span>";
        else if ($dir == 1) echo "<span class='return owndestroy'>Ваш <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,0)."\");' onmouseout='return nd();' class='owndestroy'>флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "owndestroy")." отправлен на ".PlanetTo($target, "owndestroy").". Задание: ".Cargo($m,$k,$d,"owndestroy","Уничтожить")."</span>";
        else if ($dir == 0x10) echo "<span class='flight destroy'>Боевой <a href='#' onmouseover='return overlib(\"".OverFleet($fleet,1)."\");' onmouseout='return nd();' class='destroy'>флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "destroy")." отправлен на ".PlanetTo($target, "destroy").". Задание: Уничтожить</span>";
    }
    else echo "Задание Тип:$mission, Dir:$dir, Флот: " .TitleFleet($fleet,0). ", с " .PlanetFrom($origin, ""). " на " .PlanetTo($target, ""). ", " . Cargo ($m, $k, $d,"","Груз");
}

// Добавить союзный флот в САБ.
function AddACSFleet ($task, $tasknum, $fleet)
{
}

function GetMissionDirectionAssignment ( $fleet, &$mission, &$dir, &$assign )
{
    global $GlobalUser;
    $mission = 6;

    if ($fleet['order'] < 100) $dir = 0;
    else if ($fleet['order'] < 200) $dir = 1;
    else $dir = 2;
    
    $assign = 0;    
}

function EventList ()
{
    global $GlobalUser;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

    $tasklist = EnumFleetQueue ( $GlobalUser['player_id'] );
    $rows = dbrows ($tasklist);
    $task = array ();
    $tasknum = 0;
    while ($rows--)
    {
        $queue = dbarray ($tasklist);

        // Время отправления и прибытия
        $task[$tasknum]['start_time'] = $queue['start'];
        $task[$tasknum]['end_time'] = $queue['end'];

        $fleet = LoadFleet ( $queue['sub_id'] );
        $origin = GetPlanet ( $fleet['start_planet'] );
        $target = GetPlanet ( $fleet['target_planet'] );

        // Миссия, направление, принадлежность
        GetMissionDirectionAssignment ( $fleet, &$task[$tasknum]['mission'], &$task[$tasknum]['dir'], &$task[$tasknum]['assign'] );

        // Флоты
        $task[$tasknum]['fleets'] = 1;
        $task[$tasknum]['fleet'][0] = array ();
        foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = 0;
        $task[$tasknum]['fleet'][0][214] = 1;
        $task[$tasknum]['fleet'][0]['owner_id'] = 1;
        $task[$tasknum]['fleet'][0]['m'] = 100;
        $task[$tasknum]['fleet'][0]['k'] = 200;
        $task[$tasknum]['fleet'][0]['d'] = 300;
        $task[$tasknum]['fleet'][0]['origin_id'] = 1;
        $task[$tasknum]['fleet'][0]['target_id'] = 10092;

        $tasknum++;
    }

    if ($tasknum > 0)
    {
        sksort ( $task, 'end_time', true);        // Сортировать по времени прибытия.
        $now = time ();

        foreach ($task as $i=>$t)
        {
            if ($t['fleets'] > 1) echo "<tr class=''>\n";
            else if ($t['dir'] == 0) echo "<tr class='flight'>\n";
            else if ($t['dir'] == 1) echo "<tr class='return'>\n";
            else if ($t['dir'] == 2) echo "<tr class='holding'>\n";
            echo "<th><div id='bxx".($i+1)."' title='".max($t['end_time']-$now, 0)."'star='".$t['end_time']."'></div></th>\n";
            echo "<th colspan='3'>";
            for ($fl=0; $fl<$t['fleets']; $fl++)
            {
                echo FleetSpan ($t, $fl);
                if ($t['fleets'] > 1) echo "<br /><br />";
            }
            echo "</th></tr>\n\n";
        }
        echo "<script language=javascript>anz=".$tasknum.";t();</script>\n\n";
    }
}

?>