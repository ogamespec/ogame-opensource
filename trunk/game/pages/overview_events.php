<?php

// Создание списка событий Обзора.

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

function EventFleetList ($t)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $str = "";
    foreach ($fleetmap as $i=>$gid)
    {
        $amount = $t["ship$gid"];
        if ($amount >0 ) $str .= loca("NAME_$gid")." ".nicenum($amount)."&lt;br&gt;";
    }
    return $str;
}

function EventFleetResources ($t)
{
    if ( $t['m'] + $t['k'] + $t['d'] ) return "<a href='#' title='Транспорт: Металл: ".nicenum($t['m'])." Кристалл: ".nicenum($t['k'])." Дейтерий: ".nicenum($t['d'])."'></a>";
    else return "";
}

function EventFleetResources2 ($t)
{
    if ( $t['m'] + $t['k'] + $t['d'] ) return "<a href='#' title='Транспорт: Металл: ".nicenum($t['m'])." Кристалл: ".nicenum($t['k'])." Дейтерий: ".nicenum($t['d'])."'></a>";
    else return "";
}

function EventPlanetName ($name, $type)
{
    if ($type == 0) return $name . " (".loca("MOON").")";
    else return $name;
}

function EventDirectionClass ($order)
{
    if ($order < 100) return "flight";
    else if ($order < 200) return "return";
    else return "hold";
}

function EventMissionClass ($order, $owner_id)
{
    global $GlobalUser;

    switch ( $order )
    {
        case 1    :      
        case 101 :      
            if ( $GlobalUser['player_id'] == $owner_id) return "ownattack";
            else return "attack";
        case 2    :      return "Совместная атака убывает";
        case 102 :     return "Совместная атака возвращается";
        case 3    :     
        case 103 :    
            if ( $GlobalUser['player_id'] == $owner_id) return "owntransport";
            else return "transport";
        case 4    :     
        case 104 :     
            if ( $GlobalUser['player_id'] == $owner_id) return "owndeploy";
            else return "deploy";
        case 5   :      return "Держаться убывает";
        case 105 :     return "Держаться возвращается";
        case 205 :    return "Держаться на орбите";
        case 6   :     
        case 106 :     
            if ( $GlobalUser['player_id'] == $owner_id) return "ownespionage";
            else return "espionage";
        case 7    :     return "Колонизировать убывает";
        case 107 :     return "Колонизировать возвращается";
        case 8    :     return "Переработать убывает";
        case 108 :    return "Переработать возвращается";
        case 9   :      return "Уничтожить убывает";
        case 109:      return "Уничтожить возвращается";
        case 14  :      return "Испытание убывает";
        case 114:      return "Испытание возвращается";
        case 15  :      return "Экспедиция убывает";
        case 115:      return "Экспедиция возвращается";
        case 215:      return "Экспедиция на орбите";
        case 20:       return "Ракетная атака";

        default: 
            if ( $GlobalUser['player_id'] == $owner_id) return "ownunknown";
            else return "unknown";
    }
}

// Цель ракетной атаки.
function EventRakTarget ($typ)
{
    if ($typ > 0) return "Основная цель " . loca ("NAME_$typ");
    else return "";
}

function EventList ()
{
    global $GlobalUser;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

    $tasklist = EnumFleetQueue ( $GlobalUser['player_id'] );
    $rows = dbrows ($tasklist);
    $tasknum = 0;
    while ($rows--)
    {
        $queue = dbarray ($tasklist);

        $task[$tasknum]['start_time'] = $queue['start'];
        $task[$tasknum]['end_time'] = $queue['end'];
        $task[$tasknum]['prio'] = $queue['prio'];

        $fleet = LoadFleet ( $queue['sub_id'] );
        $origin = GetPlanet ( $fleet['start_planet'] );
        $target = GetPlanet ( $fleet['target_planet'] );

        $mission = $task[$tasknum]['mission'] = $fleet['mission'];
        $task[$tasknum]['owner_id'] = $fleet['owner_id'];
        $task[$tasknum]['m'] = $fleet['m'];
        $task[$tasknum]['k'] = $fleet['k'];
        $task[$tasknum]['d'] = $fleet['d'];

        $task[$tasknum]['thisgalaxy']         = $origin['g'];
        $task[$tasknum]['thissystem']        = $origin['s'];
        $task[$tasknum]['thisplanet']         = $origin['p'];
        $task[$tasknum]['thisplanettype']   = $origin['type'];
        $task[$tasknum]['thisname']          = $origin['name'];
        $task[$tasknum]['galaxy']              = $target['g'];
        $task[$tasknum]['system']             = $target['s'];
        $task[$tasknum]['planet']               = $target['p'];
        $task[$tasknum]['planettype']        = $target['type'];
        $task[$tasknum]['name']               = $target['name'];

        foreach ($fleetmap as $i=>$gid) $task[$tasknum]["ship$gid"] = $fleet["ship$gid"];

        $tasknum++;

        // Возвращается. Не показывать возврат чужих флотов.
        if ( $mission < 20 && $queue['owner_id'] == $GlobalUser['player_id'] && $mission != 4 ) 
        {
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = 2 * $queue['end'] - $queue['start'];
            $task[$tasknum]['prio'] = $queue['prio'];

            $task[$tasknum]['mission'] = $mission + 100;
            $task[$tasknum]['owner_id'] = $fleet['owner_id'];
            $task[$tasknum]['m'] = 0;
            $task[$tasknum]['k'] = 0;
            $task[$tasknum]['d'] = 0;

            $task[$tasknum]['thisgalaxy']         = $origin['g'];
            $task[$tasknum]['thissystem']        = $origin['s'];
            $task[$tasknum]['thisplanet']         = $origin['p'];
            $task[$tasknum]['thisplanettype']   = $origin['type'];
            $task[$tasknum]['thisname']          = $origin['name'];
            $task[$tasknum]['galaxy']              = $target['g'];
            $task[$tasknum]['system']             = $target['s'];
            $task[$tasknum]['planet']               = $target['p'];
            $task[$tasknum]['planettype']        = $target['type'];
            $task[$tasknum]['name']               = $target['name'];

            foreach ($fleetmap as $i=>$gid) $task[$tasknum]["ship$gid"] = $fleet["ship$gid"];

            $tasknum++;
        }
    }

    if ($tasknum > 0)
    {
        sksort ( $task, 'end_time', true);        // Сортировать по времени прибытия.

        foreach ($task as $i=>$t)
        {
            if ( $t['mission'] == 20)         // Ракетная атака.
            {
                $own = "";
                if ( $t['owner_id'] == $GlobalUser['player_id'] ) $own = "own";
                echo "<tr>\n";
                echo "<th><div id='bxx".($i+1)."' title='".max($t['end_time']-$now, 0)."'star='".$t['end_time']."'></div></th>\n";
                echo "<th colspan='3'><span class='".$own."missile'>Ракетная атака (".$t["ship202"].") с планеты ".EventPlanetName($t['thisname'], $t['thisplanettype'])." ";
                echo "<a href=\"javascript:showGalaxy(".$t['thisgalaxy'].",".$t['thissystem'].",".$t['thisplanet'].")\" >[".$t['thisgalaxy'].":".$t['thisplanet'].":".$t['thissystem']."]</a> ";
                echo "на планету ".EventPlanetName($t['name'], $t['planettype'])." ";
                echo "<a href=\"javascript:showGalaxy(".$t['galaxy'].",".$t['system'].",".$t['planet'].")\" ".$own.">[".$t['galaxy'].":".$t['system'].":".$t['planet']."]</a> ".EventRakTarget($t["ship203"])."</span>\n";
                echo "</th>\n";
                echo "</tr>\n\n";
            }
            else
            {
                $missionclass = EventMissionClass ($t['mission'], $t['owner_id']);
                echo "<tr class='".EventDirectionClass($t['mission'])."'>\n";
                echo "<th><div id='bxx".($i+1)."' title='".max($t['end_time']-$now, 0)."'star='".$t['end_time']."'></div></th>\n";
                echo "<th colspan='3'><span class='".EventDirectionClass($t['mission'])." $missionclass'>Ваш <a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;".EventFleetList($t)."&lt;/b&gt;&lt;/font&gt;\");' onmouseout='return nd();' class='$missionclass'>флот</a>";
                echo "<a href='#' title='Большой транспорт 11'></a> с планеты ".EventPlanetName($t['thisname'], $t['thisplanettype'])." <a href=\"javascript:showGalaxy(".$t['thisgalaxy'].",".$t['thissystem'].",".$t['thisplanet'].")\" $missionclass>[".$t['thisgalaxy'].":".$t['thissystem'].":".$t['thisplanet']."]</a> ";
                echo "отправлен на планету ".EventPlanetName($t['name'], $t['planettype'])." <a href=\"javascript:showGalaxy(".$t['galaxy'].",".$t['system'].",".$t['planet'].")\" $missionclass>[".$t['galaxy'].":".$t['system'].":".$t['planet']."]</a>. ";
                echo "Задание: <a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;Транспорт: &lt;br /&gt; Металл: 164.835&lt;br /&gt;Кристалл: 71.826&lt;br /&gt;Дейтерий: 25.448&lt;/b&gt;&lt;/font&gt;\");' onmouseout='return nd();'' class='$missionclass'>".GetMissionName($t['mission'])."</a>".EventFleetResources2($t)."</span>\n";
                echo "</th>\n";
                echo "</tr>\n\n";
            }
        }
        echo "<script language=javascript>anz=".$tasknum.";t();</script>\n\n";
    }
}

?>