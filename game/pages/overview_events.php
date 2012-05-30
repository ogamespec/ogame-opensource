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
21        Атака САБ убывает
121       Атака САБ возвращается
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

function OverFleet ($fleet, $summary, $mission)
{
    global $GlobalUser;
    $level = $GlobalUser['r106'];
    if ( $fleet['owner_id'] == $GlobalUser['player_id'] ) $level = 99;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $sum = 0;
    if ( $level >= 2 )
    {
        $res = "<a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;";
        if ( $summary ) {
            foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
            $res .= "Численность кораблей: $sum &lt;br&gt;";
        }
        if ( $level >= 4 )
        {
            foreach ($fleetmap as $i=>$gid) {
                $amount = $fleet[$gid];
                if ( $amount > 0 ) {
                    $res .= loca ("NAME_$gid") . " ";
                    if ( $level >= 8 ) $res .= nicenum($amount);
                    $res .= "&lt;br&gt;";
                }
            }
        }
        $res .= "&lt;/b&gt;&lt;/font&gt;\");' onmouseout='return nd();' class='".$mission."'>";
    }
    return $res;
}

function TitleFleet ($fleet, $summary)
{
    global $GlobalUser;
    $level = $GlobalUser['r106'];
    if ( $fleet['owner_id'] == $GlobalUser['player_id'] ) $level = 99;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $sum = 0;
    if ( $level >= 2 )
    {
        if ( $summary ) {
            foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
            $res = "Численность кораблей: $sum ";
        }
        if ( $level >= 4 )
        {
            foreach ($fleetmap as $i=>$gid) {
                $amount = $fleet[$gid];
                if ( $amount > 0 ) {
                    $res .= loca ("NAME_$gid") . " " ;
                    if ( $level >= 8 ) $res .= nicenum($amount);
                }
            }
        }
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

function Cargo ($m, $k, $d, $mission, $text)
{
    if ( ($m + $k + $d) != 0 ) {
        return "<a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;Транспорт: &lt;br /&gt; Металл: ".nicenum($m)."&lt;br /&gt;Кристалл: ".nicenum($k)."&lt;br /&gt;Дейтерий: ".nicenum($d)."&lt;/b&gt;&lt;/font&gt;\");' " .
                  "onmouseout='return nd();'' class='$mission'>$text</a><a href='#' title='Транспорт: Металл: ".nicenum($m)." Кристалл: ".nicenum($k)." Дейтерий: ".nicenum($d)."'></a>";
    }
    else return "<span class='class'>$text</span>";
}

function FleetSpan ( $fleet_entry )
{
    $mission = $fleet_entry['mission'];
    $assign = $fleet_entry['assign'];
    $dir = $fleet_entry['dir'];
    $dir = $dir | ($assign << 4);
    $origin = GetPlanet ( $fleet_entry['origin_id'] );
    $target = GetPlanet ( $fleet_entry['target_id'] );
    $fleet = $fleet_entry;
    $owner = LoadUser ( $origin['owner_id'] );
    $m = $fleet_entry['m'];
    $k = $fleet_entry['k'];
    $d = $fleet_entry['d'];

    if (0) {}
    else if ($mission == 1)            // Атака
    {
        if ($dir == 0) echo "<span class='flight ownattack'>Ваш ".OverFleet($fleet,0,"ownattack")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownattack")." отправлен на ".PlanetTo($target, "ownattack").". Задание: ".Cargo($m,$k,$d,"ownattack","Атаковать")."</span>";
        else if ($dir == 1) echo "<span class='return ownattack'>Ваш ".OverFleet($fleet,0,"ownattack")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с ".PlanetFrom($origin, "ownattack").", возвращается на ".PlanetTo($target, "ownattack").". Задание: ".Cargo($m,$k,$d,"ownattack","Атаковать")."</span>";
        else if ($dir == 0x10) echo "<span class='attack'>Боевой ".OverFleet($fleet,1,"attack")."флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "attack")." отправлен на ".PlanetTo($target, "attack").". Задание: Атаковать</span>";
    }
    else if ($mission == 2)            // Совместная атака
    {
        if ($dir == 0) echo "<span class='federation'>Ваш ".OverFleet($fleet,0,"ownfederation")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownfederation")." отправлен на ".PlanetTo($target, "ownfederation").". Задание: ".Cargo($m,$k,$d,"ownfederation","Совместная атака")."</span>";
        else if ($dir == 1) echo "<span class='return ownfederation'>Ваш ".OverFleet($fleet,0,"ownfederation")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с ".PlanetFrom($origin, "ownfederation").", возвращается на ".PlanetTo($target, "ownfederation").". Задание: ".Cargo($m,$k,$d,"ownfederation","Совместная атака")."</span>";
        else if ($dir == 0x10) echo "<span class='attack'>Мирный ".OverFleet($fleet,1,"attack")."флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "attack")." отправлен на ".PlanetTo($target, "attack").". Задание: Совместная атака</span>";
    }
    else if ($mission == 3)            // Транспорт
    {
        if ($dir == 0) echo "<span class='flight owntransport'>Ваш ".OverFleet($fleet,0,"owntransport")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "owntransport")." отправлен на ".PlanetTo($target, "owntransport").". Задание: ".Cargo($m,$k,$d,"owntransport","Транспорт")."</span>";
        else if ($dir == 1) echo "<span class='return owntransport'>Ваш ".OverFleet($fleet,0,"owntransport")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с ".PlanetFrom($origin, "owntransport").", возвращается на ".PlanetTo($target, "owntransport").". Задание: ".Cargo($m,$k,$d,"owntransport","Транспорт")."</span>";
        else if ($dir == 0x10) echo "<span class='flight transport'>Мирный ".OverFleet($fleet,1,"transport")."флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "transport")." отправлен на ".PlanetTo($target, "transport").". Задание: Транспорт</span>";
    }
    else if ($mission == 4)            // Оставить
    {
        if ($dir == 0) echo "<span class='flight owndeploy'>Ваш ".OverFleet($fleet,0,"owndeploy")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "owndeploy")." отправлен на ".PlanetTo($target, "owndeploy").". Задание: ".Cargo($m,$k,$d,"owndeploy","Оставить")."</span>";
        else if ($dir == 1) echo "<span class='return owndeploy'>Ваш ".OverFleet($fleet,0,"owndeploy")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "owndeploy")." отправлен на ".PlanetTo($target, "owndeploy").". Задание: ".Cargo($m,$k,$d,"owndeploy","Оставить")."</span>";
    }
    else if ($mission == 5)            // Держаться
    {
        if ($dir == 0) echo "<span class='flight ownhold'>Ваш ".OverFleet($fleet,0,"ownhold")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownhold")." отправлен на ".PlanetTo($target, "ownhold").". Задание: ".Cargo($m,$k,$d,"ownhold","Держаться")."</span>";
        else if ($dir == 1) echo "<span class='return ownhold'>Ваш ".OverFleet($fleet,0,"ownhold")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с ".PlanetFrom($origin, "ownhold").", возвращается на ".PlanetTo($target, "ownhold").". Задание: ".Cargo($m,$k,$d,"ownhold","Держаться")."</span>";
        else if ($dir == 2) echo "<span class='holding ownhold'>Ваш ".OverFleet($fleet,0,"ownhold")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с ".PlanetFrom($origin, "ownhold").", находится на орбите ".PlanetFrom($target, "ownhold").". Задание: ".Cargo($m,$k,$d,"ownhold","Держаться")."</span>";
        else if ($dir == 0x20) echo "<span class='flight hold'>Мирный ".OverFleet($fleet,1,"hold")."флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "hold")." отправлен на ".PlanetTo($target, "hold").". Задание: <span class='ownclass'>Держаться</span></span>";
        else if ($dir == 0x22) echo "<span class='holding hold'>".PlayerDetails($owner)." удерживает альянсовый ".OverFleet($fleet,1,"hold")."флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> с ".PlanetFrom($origin, "hold")." на орбите ".PlanetFrom($target, "hold").". Задание: Держаться</span>";
    }
    else if ($mission == 6)            // Шпионаж
    {
        if ($dir == 0) echo "<span class='flight ownespionage'>Ваш ".OverFleet($fleet,0,"ownespionage")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownespionage")." отправлен на ".PlanetTo($target, "ownespionage").". Задание: ".Cargo($m,$k,$d,"ownespionage","Шпионаж")."</span>";
        else if ($dir == 1) echo "<span class='return ownespionage'>Ваш ".OverFleet($fleet,0,"ownespionage")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с ".PlanetFrom($origin, "ownespionage").", возвращается на ".PlanetTo($target, "ownespionage").". Задание: ".Cargo($m,$k,$d,"ownespionage","Шпионаж")."</span>";
        else if ($dir == 0x10) echo "<span class='flight espionage'>Боевой ".OverFleet($fleet,1,"espionage")."флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "espionage")." отправлен на ".PlanetTo($target, "espionage").". Задание: Шпионаж</span>";
    }
    else if ($mission == 7)            // Колонизировать
    {
        if ($dir == 0) echo "<span class='flight owncolony'>Ваш ".OverFleet($fleet,0,"owncolony")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "owncolony")." отправлен на позицию ".PlanetTo($target, "owncolony").". Задание: ".Cargo($m,$k,$d,"owncolony","Колонизировать")."</span>";
        else if ($dir == 1) echo "<span class='return owncolony'>Ваш ".OverFleet($fleet,0,"owncolony")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с позиции ".PlanetFrom($origin, "owncolony").", возвращается на ".PlanetTo($target, "owncolony").". Задание: ".Cargo($m,$k,$d,"owncolony","Колонизировать")."</span>";
    }
    else if ($mission == 8)            // Переработать
    {
        if ($dir == 0) echo "<span class='flight ownharvest'>Ваш ".OverFleet($fleet,0,"ownharvest")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownharvest")." отправлен на ".PlanetTo($target, "ownharvest").". Задание: ".Cargo($m,$k,$d,"ownharvest","Переработать")."</span>";
        else if ($dir == 1) echo "<span class='return ownharvest'>Ваш ".OverFleet($fleet,0,"ownharvest")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownharvest")." отправлен на ".PlanetTo($target, "ownharvest").". Задание: ".Cargo($m,$k,$d,"ownharvest","Переработать")."</span>";
    }
    else if ($mission == 9)            // Уничтожить
    {
        if ($dir == 0) echo "<span class='flight owndestroy'>Ваш ".OverFleet($fleet,0,"owndestroy")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "owndestroy")." отправлен на ".PlanetTo($target, "owndestroy").". Задание: ".Cargo($m,$k,$d,"owndestroy","Уничтожить")."</span>";
        else if ($dir == 1) echo "<span class='return owndestroy'>Ваш ".OverFleet($fleet,0,"owndestroy")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с ".PlanetFrom($origin, "owndestroy").", возвращается на ".PlanetTo($target, "owndestroy").". Задание: ".Cargo($m,$k,$d,"owndestroy","Уничтожить")."</span>";
        else if ($dir == 0x10) echo "<span class='flight destroy'>Боевой ".OverFleet($fleet,1,"destroy")."флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "destroy")." отправлен на ".PlanetTo($target, "destroy").". Задание: Уничтожить</span>";
    }
    else if ($mission == 21)            // Атака (ведущий флот САБа)
    {
        if ($dir == 0) echo "<span class='attack'>Ваш ".OverFleet($fleet,0,"ownattack")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> с ".PlanetFrom($origin, "ownattack")." отправлен на ".PlanetTo($target, "ownattack").". Задание: ".Cargo($m,$k,$d,"ownattack","Атаковать")."</span>";
        else if ($dir == 1) echo "<span class='return ownattack'>Ваш ".OverFleet($fleet,0,"ownattack")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с ".PlanetFrom($origin, "ownattack").", возвращается на ".PlanetTo($target, "ownattack").". Задание: ".Cargo($m,$k,$d,"ownattack","Атаковать")."</span>";
        else if ($dir == 0x10) echo "<span class='attack'>Боевой ".OverFleet($fleet,1,"attack")."флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "attack")." отправлен на ".PlanetTo($target, "attack").". Задание: Атаковать</span>";
        else if ($dir == 0x20) echo "<span class='ownattack'>Альянсовый ".OverFleet($fleet,1,"ownattack")."флот</a><a href='#' title='".TitleFleet($fleet,1)."'></a> игрока ".PlayerDetails($owner)." с ".PlanetFrom($origin, "ownattack")." отправлен на ".PlanetTo($target, "ownattack").". Задание: Атаковать</span>";
    }
    else if ($mission == 15)            // Экспедиция
    {
        if ($dir == 0) echo "<span class='flight owntransport'>Ваш ".OverFleet($fleet,0,"ownexpedition")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> отправленный с ".PlanetFrom($origin, "ownexpedition")." достигает позиции ".PlanetTo($target, "ownexpedition").". Задание: ".Cargo($m,$k,$d,"ownexpedition","Экспедиция")."</span>";
        else if ($dir == 1) echo "<span class='return owntransport'>Ваш ".OverFleet($fleet,0,"ownexpedition")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a> возвращается на ".PlanetTo($target, "ownexpedition")." после приказа ".Cargo($m,$k,$d,"ownexpedition","Экспедиция")."</span>";
        else if ($dir == 2) echo "<span class='holding owntransport'>Ваш ".OverFleet($fleet,0,"ownexpedition")."флот</a><a href='#' title='".TitleFleet($fleet,0)."'></a>, отправленный с ".PlanetFrom($origin, "ownexpedition")." исследует позицию ".PlanetFrom($target, "ownexpedition").". Задание: ".Cargo($m,$k,$d,"ownexpedition","Экспедиция")."</span>";
    }
    else if ($mission == 20)          // Ракетная атака
    {
        if ($dir == 0)
        {
            echo "<span class='ownmissile'>Ракетная атака (".$fleet_entry['ipm_amount'].") с ".PlanetFrom($origin, "")." на ".PlanetTo($target, "own")." ";
            if ( $fleet_entry['ipm_target'] > 0 ) echo "Основная цель " . loca ("NAME_".$fleet_entry['ipm_target']);
            echo "</span>";
        }
        else if ($dir == 0x10)
        {
            echo "<span class='missile'>Ракетная атака (".$fleet_entry['ipm_amount'].") с ".PlanetFrom($origin, "")." на ".PlanetTo($target, "")." ";
            if ( $fleet_entry['ipm_target'] > 0 ) echo "Основная цель " . loca ("NAME_".$fleet_entry['ipm_target']);
            echo "</span>";
        }
    }
    else echo "Задание Тип:$mission, Dir:$dir, Флот: " .TitleFleet($fleet,0). ", с " .PlanetFrom($origin, ""). " на " .PlanetTo($target, ""). ", " . Cargo ($m, $k, $d,"","Груз");
}

function GetMission ( $fleet_obj )
{
    if ( $fleet_obj['mission'] < 100 ) return $fleet_obj['mission'];
    else if ( $fleet_obj['mission'] < 200 ) return $fleet_obj['mission'] - 100;
    else return $fleet_obj['mission'] - 200;
}

function GetDirectionAssignment ( $fleet_obj, &$dir, &$assign )
{
    global $GlobalUser;

    if ($fleet_obj['mission'] < 100) $dir = 0;
    else if ($fleet_obj['mission'] < 200) $dir = 1;
    else $dir = 2;

    if ( $fleet_obj['owner_id'] == $GlobalUser['player_id'] ) $assign = 0;
    else {
        if (GetMission ($fleet_obj) == 5 ) $assign = 2;
        else $assign = 1;
    }
}

function EventList ()
{
    global $GlobalUser;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

    // Одиночные флоты
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

        $fleet_obj = LoadFleet ( $queue['sub_id'] );
        if ( $fleet_obj['union_id'] > 0 ) continue;        // Союзные флоты собираются отдельно

        // Флот
        $task[$tasknum]['fleets'] = 1;
        $task[$tasknum]['fleet'][0] = array ();
        foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
        $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
        $task[$tasknum]['fleet'][0]['m'] = $fleet_obj['m'];
        $task[$tasknum]['fleet'][0]['k'] = $fleet_obj['k'];
        $task[$tasknum]['fleet'][0]['d'] = $fleet_obj['d'];
        if ( $fleet_obj['mission'] < 100 || $fleet_obj['mission'] > 200 ) {
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
        }
        else
        {
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['start_planet'];
        }
        $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
        if ($fleet_obj['mission'] == 20)
        {
            $task[$tasknum]['fleet'][0]['ipm_amount'] = $fleet_obj['ipm_amount'];
            $task[$tasknum]['fleet'][0]['ipm_target'] = $fleet_obj['ipm_target'];
        }
        GetDirectionAssignment ($fleet_obj, &$task[$tasknum]['fleet'][0]['dir'], &$task[$tasknum]['fleet'][0]['assign'] );

        $tasknum++;

        // Для убывающей экспедиции или держаться добавить псевдозадание удерживания.
        // Не показывать чужие флоты.
        if ( ($fleet_obj['mission'] == 5 || $fleet_obj['mission'] == 15) && $fleet_obj['owner_id'] == $GlobalUser['player_id'] )
        {
            // Время отправления и прибытия
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'];

            // Флот
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            $task[$tasknum]['fleet'][0]['m'] = $task[$tasknum]['fleet'][0]['k'] = $task[$tasknum]['fleet'][0]['d'] = 0;
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 2;
            $task[$tasknum]['fleet'][0]['assign'] = 0;
            $tasknum++;
        }

        // Для прибывающего задания Держаться добавить псевдозадание удерживания.
        if ( $fleet_obj['mission'] == 5 && $fleet_obj['owner_id'] != $GlobalUser['player_id'] )
        {
            // Время отправления и прибытия
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'];

            // Флот
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            $task[$tasknum]['fleet'][0]['m'] = $task[$tasknum]['fleet'][0]['k'] = $task[$tasknum]['fleet'][0]['d'] = 0;
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 2;
            $task[$tasknum]['fleet'][0]['assign'] = 2;
            $tasknum++;
        }

        // Для убывающих или удерживаемых флотов добавить псевдозадание возврата.
        // Не показывать возвраты чужих флотов, задание Оставить и Ракетную атаку.
        if ( ($fleet_obj['mission'] < 100 || $fleet_obj['mission'] > 200) && $fleet_obj['owner_id'] == $GlobalUser['player_id'] && $fleet_obj['mission'] != 4 && $fleet_obj['mission'] != 20 )
        {
            // Время отправления и прибытия
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = 2 * $queue['end'] - $queue['start'];
            if ( GetMission ($fleet_obj) == 5 || GetMission ($fleet_obj) == 15 ) {
                if ( $fleet_obj['mission'] > 200) $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'];
                else $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'] + $fleet_obj['flight_time'];
            }

            // Флот
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = array ();
            foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
            $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
            $task[$tasknum]['fleet'][0]['m'] = $task[$tasknum]['fleet'][0]['k'] = $task[$tasknum]['fleet'][0]['d'] = 0;
            $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['target_planet'];
            $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['start_planet'];
            $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
            $task[$tasknum]['fleet'][0]['dir'] = 1;
            $task[$tasknum]['fleet'][0]['assign'] = 0;
            $tasknum++;
        }
    }

    // Союзные флоты
    $unions = EnumUnion ( $GlobalUser['player_id'] );
    foreach ( $unions as $u=>$union)
    {
        // Флоты
        $result = EnumUnionFleets ( $union['union_id'] );
        $rows = dbrows ( $result );

        if ( $rows > 0 )    // Не показывать пустые союзы.
        {
            $task[$tasknum]['fleets'] = $rows;
            $f = 0;
            $tn = $tasknum;
            while ($rows--)
            {
                $fleet_obj = dbarray ($result);

                $queue = GetFleetQueue ($fleet_obj['fleet_id']);
                $task[$tn]['end_time'] = $queue['end'];

                // Для убывающих или удерживаемых флотов добавить псевдозадание возврата.
                // Не показывать возвраты чужих флотов и задание Оставить.
                if ( $fleet_obj['mission'] < 100 && $fleet_obj['owner_id'] == $GlobalUser['player_id'] )
                {
                    $tasknum++;

                    // Время отправления и прибытия
                    $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['flight_time'];

                    // Флот
                    $task[$tasknum]['fleets'] = 1;
                    $task[$tasknum]['fleet'][0] = array ();
                    foreach ( $fleetmap as $i=>$gid ) $task[$tasknum]['fleet'][0][$gid] = $fleet_obj["ship$gid"];
                    $task[$tasknum]['fleet'][0]['owner_id'] = $fleet_obj['owner_id'];
                    $task[$tasknum]['fleet'][0]['m'] = $task[$tasknum]['fleet'][0]['k'] = $task[$tasknum]['fleet'][0]['d'] = 0;
                    $task[$tasknum]['fleet'][0]['origin_id'] = $fleet_obj['target_planet'];
                    $task[$tasknum]['fleet'][0]['target_id'] = $fleet_obj['start_planet'];
                    $task[$tasknum]['fleet'][0]['mission'] = GetMission ($fleet_obj);
                    $task[$tasknum]['fleet'][0]['dir'] = 1;
                    $task[$tasknum]['fleet'][0]['assign'] = 0;
                }

                $task[$tn]['fleet'][$f] = array ();
                foreach ( $fleetmap as $id=>$gid ) $task[$tn]['fleet'][$f][$gid] = $fleet_obj["ship$gid"];
                $task[$tn]['fleet'][$f]['owner_id'] = $fleet_obj['owner_id'];
                $task[$tn]['fleet'][$f]['m'] = $fleet_obj['m'];
                $task[$tn]['fleet'][$f]['k'] = $fleet_obj['k'];
                $task[$tn]['fleet'][$f]['d'] = $fleet_obj['d'];
                $task[$tn]['fleet'][$f]['origin_id'] = $fleet_obj['start_planet'];
                $task[$tn]['fleet'][$f]['target_id'] = $fleet_obj['target_planet'];
                $task[$tn]['fleet'][$f]['mission'] = GetMission ($fleet_obj);
                GetDirectionAssignment ($fleet_obj, &$task[$tn]['fleet'][$f]['dir'], &$task[$tn]['fleet'][$f]['assign'] );
                $f++;
            }

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