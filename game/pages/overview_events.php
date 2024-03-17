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
21        Атака убывает (паровоз САБ)
121       Атака возвращается (паровоз САБ)
*/

function OverFleet ($fleet, $summary, $mission)
{
    global $GlobalUser;
    $level = $GlobalUser['r106'];
    if ( $fleet['owner_id'] == $GlobalUser['player_id'] ) $level = 99;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $sum = 0;
    $res = "";
    if ( $level >= 2 )
    {
        $res = "<a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;";
        if ( $summary ) {
            foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
            $res .= loca("EVENT_FLEET_COUNT") . ": $sum &lt;br&gt;";
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
    $res = "";
    if ( $level >= 2 )
    {
        if ( $summary ) {
            foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
            $res = loca("EVENT_FLEET_COUNT") . ": $sum ";
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
    return $user['oname'] . " <a href='#' onclick='showMessageMenu(".$user['player_id'].")'><img src='".UserSkin()."img/m.gif' title='".loca("EVENT_WRITE")."' alt='".loca("EVENT_WRITE")."'></a>";
}

function PlanetFrom ($planet, $mission)
{
    $res = "";
    if ( GetPlanetType ($planet) == 1 ) $res .= loca("EVENT_FROM_PLANET");
    if ( $planet['type'] == PTYP_COLONY_PHANTOM || $planet['type'] == PTYP_FARSPACE ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function PlanetTo ($planet, $mission)
{
    $res = "";
    if ( GetPlanetType ($planet) == 1 ) $res .= loca("EVENT_TO_PLANET");
    if ( $planet['type'] == PTYP_COLONY_PHANTOM || $planet['type'] == PTYP_FARSPACE ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function Cargo ($m, $k, $d, $mission, $text)
{
    if ( ($m + $k + $d) != 0 ) {
        return "<a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;".loca("EVENT_CARGO").": &lt;br /&gt; ".
            loca("METAL").": ".nicenum($m)."&lt;br /&gt;".
            loca("CRYSTAL").": ".nicenum($k)."&lt;br /&gt;".
            loca("DEUTERIUM").": ".nicenum($d)."&lt;/b&gt;&lt;/font&gt;\");' " .
                "onmouseout='return nd();'' class='$mission'>$text</a><a href='#' title='".loca("EVENT_CARGO").": ".
                loca("METAL").": ".nicenum($m)." ".
                loca("CRYSTAL").": ".nicenum($k)." ".
                loca("DEUTERIUM").": ".nicenum($d)."'></a>";
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
        if ($dir == 0) echo "<span class='flight ownattack'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownattack",loca("EVENT_M_ATTACK"))."</span>";
        else if ($dir == 1) echo "<span class='return ownattack'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>". 
            va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownattack",loca("EVENT_M_ATTACK"))."</span>";
        else if ($dir == 0x10) echo "<span class='attack'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"attack"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "attack"), PlanetTo($target, "attack")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ATTACK")."</span>";
    }
    else if ($mission == 2)            // Совместная атака
    {
        if ($dir == 0) echo "<span class='federation'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownfederation"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownfederation"), PlanetTo($target, "ownfederation")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownfederation",loca("EVENT_M_ACS_ATTACK"))."</span>";
        else if ($dir == 1) echo "<span class='return ownfederation'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownfederation"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownfederation"), PlanetTo($target, "ownfederation")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownfederation",loca("EVENT_M_ACS_ATTACK"))."</span>";
        else if ($dir == 0x10) echo "<span class='attack'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"attack"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "attack"), PlanetTo($target, "attack")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK")."</span>";
    }
    else if ($mission == 3)            // Транспорт
    {
        if ($dir == 0) echo "<span class='flight owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owntransport"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "owntransport"), PlanetTo($target, "owntransport")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"owntransport",loca("EVENT_M_TRANSPORT"))."</span>";
        else if ($dir == 1) echo "<span class='return owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owntransport"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "owntransport"), PlanetTo($target, "owntransport")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"owntransport",loca("EVENT_M_TRANSPORT"))."</span>";
        else if ($dir == 0x10) echo "<span class='flight transport'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"transport"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "transport"), PlanetTo($target, "transport")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_TRANSPORT")."</span>";
    }
    else if ($mission == 4)            // Оставить
    {
        if ($dir == 0) echo "<span class='flight owndeploy'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owndeploy"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "owndeploy"), PlanetTo($target, "owndeploy")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"owndeploy",loca("EVENT_M_DEPLOY"))."</span>";
        else if ($dir == 1) echo "<span class='return owndeploy'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owndeploy"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "owndeploy"), PlanetTo($target, "owndeploy")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"owndeploy",loca("EVENT_M_DEPLOY"))."</span>";
    }
    else if ($mission == 5)            // Держаться
    {
        if ($dir == 0) echo "<span class='flight ownhold'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownhold"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownhold"), PlanetTo($target, "ownhold")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownhold",loca("EVENT_M_HOLD"))."</span>";
        else if ($dir == 1) echo "<span class='return ownhold'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownhold"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownhold"), PlanetTo($target, "ownhold")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownhold",loca("EVENT_M_HOLD"))."</span>";
        else if ($dir == 2) echo "<span class='holding ownhold'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownhold"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_HOLD_FROM_ONTO"), PlanetFrom($origin, "ownhold"), PlanetFrom($target, "ownhold")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownhold",loca("EVENT_M_HOLD"))."</span>";
        else if ($dir == 0x20) echo "<span class='flight hold'>".va(loca("EVENT_FLEET_FRIEND"),OverFleet($fleet,1,"hold"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "hold"), PlanetTo($target, "hold")).
            ". ".loca("EVENT_MISSION").": <span class='ownclass'>".loca("EVENT_M_HOLD")."</span></span>";
        else if ($dir == 0x22) echo "<span class='holding hold'>".va(loca("EVENT_FLEET_HOLD"),PlayerDetails($owner),OverFleet($fleet,1,"hold"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_FROM_TO_ORBIT"), PlanetFrom($origin, "hold"), PlanetFrom($target, "hold")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_HOLD")."</span>";
    }
    else if ($mission == 6)            // Шпионаж
    {
        if ($dir == 0) echo "<span class='flight ownespionage'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownespionage"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownespionage"), PlanetTo($target, "ownespionage")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownespionage",loca("EVENT_M_SPY"))."</span>";
        else if ($dir == 1) echo "<span class='return ownespionage'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownespionage"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownespionage"), PlanetTo($target, "ownespionage")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownespionage",loca("EVENT_M_SPY"))."</span>";
        else if ($dir == 0x10) echo "<span class='flight espionage'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"espionage"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "espionage"), PlanetTo($target, "espionage")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_SPY")."</span>";
    }
    else if ($mission == 7)            // Колонизировать
    {
        if ($dir == 0) echo "<span class='flight owncolony'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owncolony"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_COLONY_FROM_TO"), PlanetFrom($origin, "owncolony"), PlanetTo($target, "owncolony")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"owncolony",loca("EVENT_M_COLONY"))."</span>";
        else if ($dir == 1) echo "<span class='return owncolony'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owncolony"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_COLONY_FROM_RETURN_TO"), PlanetFrom($origin, "owncolony"), PlanetTo($target, "owncolony")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"owncolony",loca("EVENT_M_COLONY"))."</span>";
    }
    else if ($mission == 8)            // Переработать
    {
        if ($dir == 0) echo "<span class='flight ownharvest'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownharvest"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownharvest"), PlanetTo($target, "ownharvest")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownharvest",loca("EVENT_M_RECYCLE"))."</span>";
        else if ($dir == 1) echo "<span class='return ownharvest'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownharvest"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownharvest"), PlanetTo($target, "ownharvest")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownharvest",loca("EVENT_M_RECYCLE"))."</span>";
    }
    else if ($mission == 9)            // Уничтожить
    {
        if ($dir == 0) echo "<span class='flight owndestroy'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owndestroy"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "owndestroy"), PlanetTo($target, "owndestroy")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"owndestroy",loca("EVENT_M_DESTROY"))."</span>";
        else if ($dir == 1) echo "<span class='return owndestroy'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"owndestroy"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "owndestroy"), PlanetTo($target, "owndestroy")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"owndestroy",loca("EVENT_M_DESTROY"))."</span>";
        else if ($dir == 0x10) echo "<span class='flight destroy'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"destroy"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "destroy"), PlanetTo($target, "destroy")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_DESTROY")."</span>";
    }
    else if ($mission == 21)            // Атака (ведущий флот САБа)
    {
        if ($dir == 0) echo "<span class='attack'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_TO"), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownattack",loca("EVENT_M_ACS_ATTACK_HEAD"))."</span>";
        else if ($dir == 1) echo "<span class='return ownattack'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_FROM_RETURN_TO"), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownattack",loca("EVENT_M_ACS_ATTACK_HEAD"))."</span>";
        else if ($dir == 0x10) echo "<span class='attack'>".va(loca("EVENT_FLEET_ENEMY"),OverFleet($fleet,1,"attack"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "attack"), PlanetTo($target, "attack")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK_HEAD")."</span>";
        else if ($dir == 0x20) echo "<span class='ownattack'>".va(loca("EVENT_FLEET_ACS_HEAD"),OverFleet($fleet,1,"ownattack"))."</a><a href='#' title='".TitleFleet($fleet,1)."'></a>".
            va(loca("EVENT_PLAYER_FROM_TO"), PlayerDetails($owner), PlanetFrom($origin, "ownattack"), PlanetTo($target, "ownattack")).
            ". ".loca("EVENT_MISSION").": ".loca("EVENT_M_ACS_ATTACK_HEAD")."</span>";
    }
    else if ($mission == 15)            // Экспедиция
    {
        if ($dir == 0) echo "<span class='flight owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownexpedition"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_EXPO_FROM_TO"), PlanetFrom($origin, "ownexpedition"), PlanetTo($target, "ownexpedition")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownexpedition",loca("EVENT_M_EXPO"))."</span>";
        else if ($dir == 1) echo "<span class='return owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownexpedition"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_EXPO_RETURN_FROM_TO"), PlanetTo($target, "ownexpedition"), Cargo($m,$k,$d,"ownexpedition",loca("EVENT_M_EXPO")))."</span>";
        else if ($dir == 2) echo "<span class='holding owntransport'>".va(loca("EVENT_FLEET_OWN"),OverFleet($fleet,0,"ownexpedition"))."</a><a href='#' title='".TitleFleet($fleet,0)."'></a>".
            va(loca("EVENT_EXPO_FROM_ONTO"), PlanetFrom($origin, "ownexpedition"), PlanetFrom($target, "ownexpedition")).
            ". ".loca("EVENT_MISSION").": ".Cargo($m,$k,$d,"ownexpedition",loca("EVENT_M_EXPO"))."</span>";
    }
    else if ($mission == 20)          // Ракетная атака
    {
        if ($dir == 0)
        {
            echo "<span class='ownmissile'>" .va(loca("EVENT_RAK"), $fleet_entry['ipm_amount'], PlanetFrom($origin, ""), PlanetTo($target, "own")) . " ";
        }
        else if ($dir == 0x10)
        {
            echo "<span class='missile'>" .va(loca("EVENT_RAK"), $fleet_entry['ipm_amount'], PlanetFrom($origin, ""), PlanetTo($target, "")) . " ";
        }
        if ( $fleet_entry['ipm_target'] > 0 ) echo loca("EVENT_RAK_TARGET") . " " . loca ("NAME_".$fleet_entry['ipm_target']);
        echo "</span>";
    }
    else echo loca("EVENT_MISSION")." Type:$mission, Dir:$dir, Fleet: " .TitleFleet($fleet,0). ", from " .PlanetFrom($origin, ""). " to " .PlanetTo($target, ""). ", " . Cargo ($m, $k, $d,"","Cargo");
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

    if ($fleet_obj['mission'] < 100) $dir = 0;      // убывает
    else if ($fleet_obj['mission'] < 200) $dir = 1;     // возвращается
    else $dir = 2;  // удержание

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
        GetDirectionAssignment ($fleet_obj, $task[$tasknum]['fleet'][0]['dir'], $task[$tasknum]['fleet'][0]['assign'] );

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
                GetDirectionAssignment ($fleet_obj, $task[$tn]['fleet'][$f]['dir'], $task[$tn]['fleet'][$f]['assign'] );
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
            else if ($t['fleet'][0]['dir'] == 0) echo "<tr class='flight'>\n";
            else if ($t['fleet'][0]['dir'] == 1) echo "<tr class='return'>\n";
            else if ($t['fleet'][0]['dir'] == 2) echo "<tr class='holding'>\n";
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