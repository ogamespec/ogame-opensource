<?php

// Управление флотами.

require_once "expedition.php";

/*
fleet_id: Порядковый номер флота в таблице (INT PRIMARY KEY)
owner_id: Номер пользователя, которому принадлежит флот (INT)
union_id: Номер союза, в котором летит флот (INT)
m, k, d: Перевозимый груз (металл/кристалл/дейтерий) (DOUBLE)
mission: Тип задания (INT)
start_planet: Старт (INT)
target_planet: Финиш (INT)
deploy_time: Время удержания флота в секундах (INT)
shipXX: количество кораблей каждого типа (INT)

Задания флота оформляются в виде события для глобальной очереди.

Отправление флота заключается в отнимании у планеты следующих полей: fXX (флот), m/k/d - ресурсы
Прибытие флота: добавляет эти поля (или опять отнимает, при атаке), а также генерирует сообщения.

Первые три страницы flottenX подготавливают параметры для страницы flottenversand, которая либо отправляет флот, либо возвращает ошибку.

Одно задание флота может по завершении порождать другое задание, например после достижения Транспорта, создается новое задание - возврат Транспорта.

В Обзоре все последующие задания "предсказываются", их нет на самом деле. В меню Флот показано описание заданий приближенное к данным базы данных.

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
14        Испытание убывает
114      Испытание возвращается
15        Экспедиция убывает
115      Экспедиция возвращается
215      Экспедиция на орбите
20        Ракетная атака

Структура таблицы САБов:
union_id: ID союза = fleet_id * 16 (INT PRIMARY KEY)
fleet_id: ID головного флота САБа (исходной Атаки) (INT)
name: название союза. по умолчанию: "KV" + union_id (CHAR(20))
playerXX: ID приглашенного игрока (INT), XX = 1...5
players: количество приглашенных игроков (INT, не более 5)

*/

// ==================================================================================
// Получить список доступных заданий для флота.

/*
Возможные задания:

X:0
>= X:16 (позиция становится 16, любой тип планет)          Экспедиция
пустое место X:1 ... X:15 без колона       Транспорт, Атака
пустое место X:1 ... X:15 с колоном       Транспорт, Атака, Колонизировать

своя планета                         Транспорт, Оставить
своя луна                               Транспорт, Оставить

поле обломков с рабом            Переработать
поле обломков без раба        Нет подходящих заданий

планета друга/соала              Транспорт, Атака, Держаться, Совместная атака
луна друга/соала с ЗС            Транспорт, Атака, Держаться, Совместная атака, Уничтожить
луна друга/соала без ЗС        Транспорт, Атака, Держаться, Совместная атака
(если есть спай добавить Шпионаж)
если во флоте только спай     Шпионаж

чужая планета                     Транспорт, Атака, Совместная атака
чужая луна с ЗС                     Транспорт, Атака, Совместная атака, Уничтожить
чужая луна без ЗС                Транспорт, Атака, Совместная атака
(если есть спай добавить Шпионаж)
если во флоте только спай     Шпионаж
*/

function FleetAvailableMissions ( $thisgalaxy, $thissystem, $thisplanet, $thisplanettype, $galaxy, $system, $planet, $planettype, $fleet )
{
    $missions = array ( );

    // HACK
    $missions = array ( 1, 2, 3, 4, 5, 6, 7, 8, 9, 15 );
    return $missions;

    $origin = LoadPlanet ( $thisgalaxy, $thissystem, $thisplanet, $thisplanettype );
    $target = LoadPlanet ( $galaxy, $system, $planet, $planettype );

    if ( $planet >= 16 )         // Экспедиция
    {
        $missions[0] = 15;
        return $missions;
    }

    if ( $planettype == 2)        // поле обломков.
    {
        if ( $fleet[209] > 0 ) $missions[0] = 8;    // Переработать, если во флоте есть рабы
        return $missions;
    }

    if ( $target == NULL )        // пустое место
    {
        $missions[0] = 3;        // Транспорт
        $missions[1] = 1;        // Атака
        if ( $fleet[208] > 0 ) $missions[2] = 7;    // Колонизировать, если во флоте есть колонизатор
        return $missions;
    }

    if ( $origin['owner_id'] == $target['owner_id'] )        // свои луны/планеты
    {
        $missions[0] = 3;        // Транспорт
        $missions[1] = 4;        // Оставить
        return $missions;
    }
    else
    {
        $i = 0;
        $origin_user = LoadUser ($origin['owner_id']);
        $target_user = LoadUser ($target['owner_id']);

        if ( ( $origin_user['ally_id'] == $target_user['ally_id'] && $origin_user['ally_id'] > 0 )   || IsBuddy ( $origin_user['player_id'],  $target_user['player_id']) )      // соалы или друзья
        {
            $missions[$i++] = 3;        // Транспорт
            $missions[$i++] = 1;        // Атака
            $missions[$i++] = 5;        // Держаться
            if ( $fleet[210] > 0  ) $missions[$i++] = 6;    // Шпионаж
            return $missions;
        }
        else        // все остальные
        {
            $missions[$i++] = 3;        // Транспорт
            $missions[$i++] = 1;        // Атака
            if ( $fleet[214] > 0 && GetPlanetType($target) == 3 ) $missions[$i++] = 9;    // Уничтожить
            if ( $fleet[210] > 0  ) $missions[$i++] = 6;    // Шпионаж
            return $missions;
        }
    }
}

// ==================================================================================
// Расчёт полётов.

// Расстояние.
function FlightDistance ( $thisgalaxy, $thissystem, $thisplanet, $galaxy, $system, $planet )
{
    if ($thisgalaxy == $galaxy) {
        if ($thissystem == $system) {
            if ($planet == $thisplanet) $dist = 5;
            else $dist = abs ($planet - $thisplanet) * 5 + 1000;
        }
        else $dist = abs ($system - $thissystem) * 5 * 19 + 2700;
    }
    else $dist = abs ($galaxy - $thisgalaxy) * 20000;
    return $dist;
}

// Групповая скорость флота.
function FlightSpeed ($fleet, $combustion, $impulse, $hyper)
{
    $minspeed = FleetSpeed ( 210, $combustion, $impulse, $hyper );        // самый быстрый кораблик - ШЗ
    foreach ($fleet as $id=>$amount)
    {
        $speed = FleetSpeed ( $id, $combustion, $impulse, $hyper);
        if ( $amount == 0 || $speed == 0 ) continue;
        if ($speed < $minspeed) $minspeed = $speed;
    }
    return $minspeed;
}

// Потребление дейтерия на полёт всем флотом.
function FlightCons ($fleet, $dist, $slowest_speed, $combustion, $impulse, $hyper, $probeOnly)
{
    $cons = 0;
    foreach ($fleet as $id=>$amount)
    {
        if ($probeOnly && $id == 210) continue;
        if ($amount > 0) {
            $spd = 35000 / ( $slowest_speed - 10) * sqrt($dist * 10 / $slowest_speed);
            $basecons = $amount * FleetCons ($id, $combustion, $impulse, $hyper );
            $cons += $basecons * $dist / 35000 ;//* (($spd / 10) + 1) * (($spd / 10) + 1);
        }
    }
    $cons = round($cons) + 1;
    return $cons;
}

// Время полёта в секундах, при заданном проценте.
function FlightTime ($dist, $slowest_speed, $prc, $xspeed)
{
    return round ( (35000 / ($prc*10) * sqrt ($dist * 10 / $slowest_speed ) + 10) / $xspeed );
}

// Скорость кораблика
// 202-Р/И, 203-Р, 204-Р, 205-И, 206-И, 207-Г, 208-И, 209-Р, 210-Р, 211-И/Г, 212-Р, 213-Г, 214-Г, 215-Г
function FleetSpeed ( $id, $combustion, $impulse, $hyper)
{
    global $UnitParam;

    $baseSpeed = $UnitParam[$id][4];

    switch ($id) {
        case 202:
            if ($impulse >= 5) return ($baseSpeed + 5000) * (1 + 0.2 * $impulse);
            else return $baseSpeed * (1 + 0.1 * $combustion);
        case 211:
            if ($hyper >= 8) return ($baseSpeed + 1000) * (1 + 0.3 * $hyper);
            else return $baseSpeed * (1 + 0.2 * $impulse);            
        case 203:
        case 204:
        case 209:
        case 210:
        case 212:
            return $baseSpeed * (1 + 0.1 * $combustion);
        case 205:
        case 206:
        case 208:
            return $baseSpeed * (1 + 0.2 * $impulse);
        case 207:
        case 213:
        case 214:
        case 215:
            return $baseSpeed * (1 + 0.3 * $hyper);
        default: return $baseSpeed;
    }
}

function FleetCargo ( $id )
{
    global $UnitParam;
    return $UnitParam[$id][3];
}

function FleetCons ($id, $combustion, $impulse, $hyper )
{
    global $UnitParam;
    if ($id == 202 && $impulse >= 5) return $UnitParam[$id][5] + 10;
    else return $UnitParam[$id][5];
}

// ==================================================================================

// Изменить количество кораблей на планете.
function AdjustShips ($fleet, $planet_id, $sign)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    global $db_prefix;

    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ($fleetmap as $i=>$gid)
    {
        if ($i > 0) $query .= ",";
        $query .= "f$gid = f$gid $sign " . $fleet[$gid] ;
    }
    $query .= " WHERE planet_id=$planet_id;";
    //echo "$query<br>";
    dbquery ($query);
}

// Отправить флот. Никаких проверок не производится. Возвращает ID флота.
function DispatchFleet ($fleet, $origin, $target, $order, $seconds, $m, $k ,$d)
{
    $now = time ();
    $prio = 0;
    $union_id = 0;
    $deploy_time = 0;

    // HACK.
    //$seconds = 20;

    // Добавить флот.
    $fleet_id = IncrementDBGlobal ('nextfleet');
    $fleet_obj = array ( $fleet_id, $origin['owner_id'], $union_id, $m, $k, $d, $order, $origin['planet_id'], $target['planet_id'], $deploy_time,
                                 0, 0, $fleet[202], $fleet[203], $fleet[204], $fleet[205], $fleet[206], $fleet[207], $fleet[208], $fleet[209], $fleet[210], $fleet[211], $fleet[212], $fleet[213], $fleet[214], $fleet[215] );
    AddDBRow ($fleet_obj, 'fleet');

    // Добавить задание в глобальную очередь событий.
    AddQueue ( $origin['owner_id'], "Fleet", $fleet_id, 0, 0, $now, $seconds, $prio );
    return $fleet_id;
}

// Отозвать флот (если это возможно)
function RecallFleet ($fleet_id)
{
    $fleet_obj = LoadFleet ($fleet_id);
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $fleet = array ();
    foreach ($fleetmap as $i=>$gid) $fleet[$gid] = $fleet_obj["ship$gid"];

    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );

    if ($fleet_obj['mission'] < 100) DispatchFleet ($fleet, $origin, $target, $fleet_obj['mission'] + 100, 30, $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d']);
    else DispatchFleet ($fleet, $origin, $target, $fleet_obj['mission'] - 100, 30, $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d']);

    $queue = GetFleetQueue ($fleet_obj['fleet_id']);
    DeleteFleet ($fleet_obj['fleet_id']);            // удалить флот
    RemoveQueue ( $queue['task_id'], 0 );    // удалить задание
}

// Загрузить флот
function LoadFleet ($fleet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE fleet_id = '".$fleet_id."'";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Удалить флот
function DeleteFleet ($fleet_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."fleet WHERE fleet_id = $fleet_id;";
    dbquery ($query);
}

// Получить описание задания (для отладки)
function GetMissionNameDebug ($num)
{
    switch ($num)
    {
        case 1    :      return "Атака убывает";
        case 101 :      return "Атака возвращается";
        case 2    :      return "Совместная атака убывает";
        case 102 :     return "Совместная атака возвращается";
        case 3    :     return "Транспорт убывает";
        case 103 :     return "Транспорт возвращается";
        case 4    :     return "Оставить убывает";
        case 104 :     return "Оставить возвращается";
        case 5   :      return "Держаться убывает";
        case 105 :     return "Держаться возвращается";
        case 205 :    return "Держаться на орбите";
        case 6   :      return "Шпионаж убывает";
        case 106 :     return "Шпионаж возвращается";
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

        default: return "Неизвестно";
    }
}

// Получить описание задания
function GetMissionName ($num)
{
    switch ($num)
    {
        case 1    :      return "Атака";
        case 101 :      return "Атака";
        case 2    :      return "Совместная атака";
        case 102 :     return "Совместная атака";
        case 3    :     return "Транспорт";
        case 103 :     return "Транспорт";
        case 4    :     return "Оставить";
        case 104 :     return "Оставить";
        case 5   :      return "Держаться";
        case 105 :     return "Держаться";
        case 205 :    return "Держаться";
        case 6   :      return "Шпионаж";
        case 106 :     return "Шпионаж";
        case 7    :     return "Колонизировать";
        case 107 :     return "Колонизировать";
        case 8    :     return "Переработать";
        case 108 :    return "Переработать";
        case 9   :      return "Уничтожить";
        case 109:      return "Уничтожить";
        case 15  :      return "Экспедиция";
        case 115:      return "Экспедиция";
        case 215:      return "Экспедиция";
        case 20:       return "Ракетная атака";

        default: return "Неизвестно";
    }
}

// Запустить межпланетные ракеты
function LaunchRockets ( $origin, $target, $seconds, $amount, $type )
{
    $now = time ();
    $prio = 0;

    // Добавить ракетную атаку.
    $fleet_id = IncrementDBGlobal ('nextfleet');
    $fleet_obj = array ( $fleet_id, $origin['owner_id'], 0, 0, 0, 0, 20, $origin['planet_id'], $target['planet_id'], 0,
                                 0, 0, $amount, $type, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    AddDBRow ($fleet_obj, 'fleet');

    // Добавить задание в глобальную очередь событий.
    AddQueue ( $origin['owner_id'], "Fleet", $fleet_id, 0, 0, $now, $seconds, $prio );
    return $fleet_id;
}

// ==================================================================================
// Обработка заданий флота.

function FleetList ($fleet)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $res = "";
    foreach ( $fleetmap as $i=>$gid )
    {
        if ($fleet[$gid] > 0) $res .= loca("NAME_$gid") . ": " . nicenum ($fleet[$gid]) . " ";
    }
    return $res;
}

// *** Атака ***

function AttackArrive ($queue, $fleet_obj, $fleet)
{
    StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'] );
}

// *** Транспорт ***

function TransportArrive ($queue, $fleet_obj, $fleet)
{
    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );

    $oldm = $target['m'];
    $oldk = $target['k'];
    $oldd = $target['d'];

    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $target['planet_id'], '+' );

    DispatchFleet ($fleet, $origin, $target, 103, 30, 0, 0, 0);

    $text = "Ваш флот достигает планеты (\n" .
                "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>\n" .
                ") и доставляет свой груз:.\n" .
                "<br/>\n" .
                nicenum($fleet_obj['m'])." металла, ".nicenum($fleet_obj['k'])." кристалла и ".nicenum($fleet_obj['d'])." дейтерия.\n" .
                "<br/>\n";
    SendMessage ( $fleet_obj['owner_id'], "Командование флотом", "Достижение планеты", $text, 5);

    // Транспорт на чужую планету.
    if ( $origin['owner_id'] != $target['owner_id'] )
    {
        $user = LoadUser ( $origin['owner_id'] );
        $text = "Чужой флот игрока ".$user['oname']." доставляет на Вашу планету ".PlanetName($target)."\n" .
                    "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>\n" .
                    "<br/>\n" .
                    nicenum($fleet_obj['m'])." металла, ".nicenum($fleet_obj['k'])." кристалла и ".nicenum($fleet_obj['d'])." дейтерия\n" .
                    "<br/>\n" .
                    "Прежде у Вас было ".nicenum($oldm)." металла, ".nicenum($oldk)." кристалла и ".nicenum($oldd)." дейтерия.\n" .
                    "<br/>\n" .
                    "Теперь же у Вас ".nicenum($oldm+$fleet_obj['m'])." металла, ".nicenum($oldk+$fleet_obj['k'])." кристалла и ".nicenum($oldd+$fleet_obj['d'])." дейтерия.\n" .
                    "<br/>\n";
        SendMessage ( $target['owner_id'], "Наблюдение", "Чужой флот доставляет сырьё", $text, 5);
    }
}

function CommonReturn ($queue, $fleet_obj, $fleet)
{
    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );

    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );

    $text = "Один из Ваших флотов ( ".FleetList($fleet)." ), отправленных с <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a>, " .
               "достигает ".PlanetName($origin)." <a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a> . ";
    if ( ($fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d']) != 0 ) $text .= "Флот доставляет ".nicenum($fleet_obj['m'])." металла, ".nicenum($fleet_obj['k'])." кристалла и ".nicenum($fleet_obj['d'])." дейтерия<br>";
    SendMessage ( $fleet_obj['owner_id'], "Командование флотом", "Возвращение флота", $text, 5);
}

// *** Оставить ***

function DeployArrive ($queue, $fleet_obj, $fleet)
{
    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );

    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $target['planet_id'], '+' );
    AdjustShips ( $fleet, $fleet_obj['target_planet'], '+' );

    $text = "\nОдин из Ваших флотов (".FleetList($fleet).") достиг ".PlanetName($target)."\n" .
               "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>\n" .
               ". Флот доставляет ".nicenum($fleet_obj['m'])." металла, ".nicenum($fleet_obj['k'])." кристалла и ".nicenum($fleet_obj['d'])." дейтерия\n" .
               "<br/>\n";
    SendMessage ( $fleet_obj['owner_id'], "Командование флотом", "Удержание флота", $text, 5);
}

// *** Держаться ***

// *** Шпионаж ***

function SpyArrive ($queue, $fleet_obj, $fleet)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );
    $buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );
    $resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );

    $now = time();

    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    $target_user = LoadUser ( $target['owner_id'] );

    $counter_max = 0;
    $counter = 0;

    $subj = "\n<span class=\"espionagereport\">\n" .
                "Разведданные с ".PlanetName($target)."\n" .
                "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>\n";

    $report = "";

    // Шапка
    $report .= "<table width=400><tr><td class=c colspan=4>Сырьё на ".PlanetName($target)." <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a> (Игрок \'".$target_user['oname']."\')<br /> на ".date ("m-d H:i:s", $now)."</td></tr>\n";
    $report .= "</div></font></TD></TR><tr><td>металла:</td><td>".nicenum($target['m'])."</td>\n";
    $report .= "<td>кристалла:</td><td>".nicenum($target['k'])."</td></tr>\n";
    $report .= "<tr><td>дейтерия:</td><td>".nicenum($target['d'])."</td>\n";
    $report .= "<td>энергии:</td><td>".nicenum($target['emax'])."</td></tr>\n";
    $report .= "</table>\n";

    // Активность
    $report .= "<table width=400><tr><td class=c colspan=4>     </td></tr>\n";
    $report .= "<TR><TD colspan=4><div onmouseover=\'return overlib(\"&lt;font color=white&gt;Активность означает, что сканируемый игрок был активен на своей планете, либо на него был произведён вылет флота другого игрока.&lt;/font&gt;\", STICKY, MOUSEOFF, DELAY, 750, CENTER, WIDTH, 100, OFFSETX, -130, OFFSETY, -10);\' onmouseout=\'return nd();\'></TD></TR></table>\n";

    // Флот
    $report .= "<table width=400><tr><td class=c colspan=4>Флоты     </td></tr>\n";
    $count = 0;
    foreach ( $fleetmap as $i=>$gid )
    {
        $amount = $target["f$gid"];
        if ( ($count % 2) == 0 ) $report .= "</tr>\n";
        if ($amount > 0) {
            $report .= "<td>".loca("NAME_$gid")."</td><td>".nicenum($amount)."</td>\n";
            $count++;
        }
    }
    $report .= "</table>\n";

    // Оборона
    $report .= "<table width=400><tr><td class=c colspan=4>Оборона     </td></tr>\n";
    $count = 0;
    foreach ( $defmap as $i=>$gid )
    {
        $amount = $target["d$gid"];
        if ( ($count % 2) == 0 ) $report .= "</tr>\n";
        if ($amount > 0) {
            $report .= "<td>".loca("NAME_$gid")."</td><td>".nicenum($amount)."</td>\n";
            $count++;
        }
    }
    $report .= "</table>\n";

    // Постройки
    $report .= "<table width=400><tr><td class=c colspan=4>Постройки     </td></tr>\n";
    $count = 0;
    foreach ( $buildmap as $i=>$gid )
    {
        $amount = $target["b$gid"];
        if ( ($count % 2) == 0 ) $report .= "</tr>\n";
        if ($amount > 0) {
            $report .= "<td>".loca("NAME_$gid")."</td><td>".nicenum($amount)."</td>\n";
            $count++;
        }
    }
    $report .= "</table>\n";

    // Исследования
    $report .= "<table width=400><tr><td class=c colspan=4>Исследования     </td></tr>\n";
    $count = 0;
    foreach ( $resmap as $i=>$gid )
    {
        $amount = $target_user["r$gid"];
        if ( ($count % 2) == 0 ) $report .= "</tr>\n";
        if ($amount > 0) {
            $report .= "<td>".loca("NAME_$gid")."</td><td>".nicenum($amount)."</td>\n";
            $count++;
        }
    }
    $report .= "</table>\n";

    $report .= "<center> Шанс на защиту от шпионажа:$counter%</center>\n";
    $report .= "<center><a href=\'#\' onclick=\'showFleetMenu(".$target['g'].",".$target['s'].",".$target['p'].",".GetPlanetType($target).",1);\'>Атака</a></center>\n";

    SendMessage ( $fleet_obj['owner_id'], "Командование флотом", $subj, $report, 1);

    // Отправить сообщение чужому игроку о шпионаже.
    $text = "\nЧужой флот с планеты ".PlanetName($origin)."\n" .
                "<a onclick=\"showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p'].");\" href=\"#\">[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>\n" .
                "был обнаружен вблизи от планеты ".PlanetName($target)."\n" .
                "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>\n" .
                ". Шанс на защиту от шпионажа: $counter %\n" .
                "</td>\n";
    SendMessage ( $target['owner_id'], "Наблюдение", "Шпионаж", $text, 5);

    // Вернуть флот.
    DispatchFleet ($fleet, $origin, $target, 106, 30, $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d']);
}

function SpyReturn ($queue, $fleet_obj, $fleet)
{
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
}

// *** Колонизировать ***

function ColonizationArrive ($queue, $fleet_obj, $fleet)
{
    global $db_prefix;

    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );

    $text = "\nФлот достигает заданных координат\n" . 
               "<a href=\"javascript:showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].")\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>\n";

    if ( !HasPlanet($target['g'], $target['s'], $target['p']) )    // если место не занято, то значит колонизация успешна
    {
        // если количество планет империи больше максимума, то не основывать новую колонию.
        $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".$fleet_obj['owner_id']."' AND (type > 0 AND type < 10000);";
        $result = dbquery ($query);
        $num_planets = dbrows ($result);
        if ( $num_planets >= 9 )
        {
            $text .= ", и устанавливает, что эта планета пригодна для колонизации. Вскоре после начала освоения планеты поступает сообщение о беспорядках на главной планете, так как империя становится слишком большой и люди возвращаются обратно.\n";

            // Добавить уничтоженную планету.
            AbandonPlanet ( $target['g'], $target['s'], $target['p'] );

            // Вернуть флот.
            DispatchFleet ($fleet, $origin, $target, 107, 30, $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d']);
        }
        else
        {
            $text .= ", находит там новую планету и сразу же начинает её освоение.\n";

            // Удалить фантом колонизации.
            DestroyPlanet ( $target['planet_id'] );

            // Создать новую колонию.
            $id = CreatePlanet ( $target['g'], $target['s'], $target['p'], $fleet_obj['owner_id'], 1 );
            Debug ( "Игроком ".$origin['owner_id']." колонизирована планета $id [".$target['g'].":".$target['s'].":".$target['p']."]");
        }
    }
    else
    {
        $text .= ", , но не находит там пригодной для колонизации планеты. В подавленном состоянии поселенцы возвращаются обратно.\n";

        // Вернуть флот.
        DispatchFleet ($fleet, $origin, $target, 107, 30, $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d']);
    }

    SendMessage ( $fleet_obj['owner_id'], "Поселенцы", "Доклад поселенцев", $text, 5);
}

function ColonizationReturn ($queue, $fleet_obj, $fleet)
{
    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );

    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );

    $text = "Один из Ваших флотов ( ".FleetList($fleet)." ), отправленных с <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a>, " .
               "достигает ".PlanetName($origin)." <a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a> . ";
    if ( ($fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d']) != 0 ) $text .= "Флот доставляет ".nicenum($fleet_obj['m'])." металла, ".nicenum($fleet_obj['k'])." кристалла и ".nicenum($fleet_obj['d'])." дейтерия<br>";
    SendMessage ( $fleet_obj['owner_id'], "Командование флотом", "Возвращение флота", $text, 5);

    // Удалить фантом колонизации.
    DestroyPlanet ( $target['planet_id'] );
}

// *** Переработать ***

// *** Уничтожить ***

// *** Экспедиция ***

// *** Ракетная атака ***

function RocketAttackArrive ($queue, $fleet_obj, $fleet)
{
    RocketAttack ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'] );
}

function Queue_Fleet_End ($queue)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $fleet_obj = LoadFleet ( $queue['sub_id'] );
    $fleet = array ();
    foreach ($fleetmap as $i=>$gid) $fleet[$gid] = $fleet_obj["ship$gid"];

    switch ( $fleet_obj['mission'] )
    {
        case 1: AttackArrive ($queue, $fleet_obj, $fleet); break;
        case 101: CommonReturn ($queue, $fleet_obj, $fleet); break;
        case 3: TransportArrive ($queue, $fleet_obj, $fleet); break;
        case 103: CommonReturn ($queue, $fleet_obj, $fleet); break;
        case 4: DeployArrive ($queue, $fleet_obj, $fleet); break;
        case 104: CommonReturn ($queue, $fleet_obj, $fleet); break;
        case 6: SpyArrive ($queue, $fleet_obj, $fleet); break;
        case 106: SpyReturn ($queue, $fleet_obj, $fleet); break;
        case 7: ColonizationArrive ($queue, $fleet_obj, $fleet); break;
        case 107: ColonizationReturn ($queue, $fleet_obj, $fleet); break;
        case 20: RocketAttackArrive ($queue, $fleet_obj, $fleet); break;
        //default: Error ( "Неизвестное задание для флота: " . $fleet_obj['mission'] ); break;
    }

    DeleteFleet ($fleet_obj['fleet_id']);            // удалить флот
    RemoveQueue ( $queue['task_id'], 0 );    // удалить задание
}

// ==================================================================================

// Управление САБами.

// Создать САБ. $fleet_id - паровоз.
function CreateUnion ($fleet_id)
{
    global $db_prefix;

    $fleet_obj = LoadFleet ($fleet_id);

    // Проверить есть ли уже союз?
    if ( $fleet_obj['union_id'] != 0 ) return $fleet_obj['union_id'];

    // Союзы можно создавать только для убывающих атак.
    if ($fleet_obj['mission'] != 1) return 0;

    // Добавить союз.
    $union_id = $fleet_id << 8;
    $union = array ( $union_id, $fleet_id, "KV$union_id", $fleet_obj['owner_id'], 0, 0, 0, 0, 1 );
    AddDBRow ($union, 'union');

    // Добавить флот в союз.
    $query = "UPDATE ".$db_prefix."fleet SET union_id = $union_id WHERE fleet_id = $fleet_id";
    dbquery ($query);
    return $union_id;
}

function LoadUnion ($union_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."union WHERE union_id = $union_id";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return NULL;
    else return dbarray ($result);
}

// Союз удаляется при отзыве, возврате или уничтожении паровоза.
function RemoveUnion ($union_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."union WHERE union_id = $union_id";
    dbquery ($query);
}

// Переименовать САБ.
function RenameUnion ($union_id, $name)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."union SET name = '".$name."' WHERE union_id = $union_id";
    dbquery ($query);
}

// Добавить нового участника в САБ.
// Можно добавлять только друзей и соалов (кроме уже добавленных)
function AddUnionMember ($union_id, $name)
{
    global $db_prefix;
    global $GlobalUser;
    $union = LoadUnion ($union_id);

    // Пустое имя, ничего не делаем.
    if ($name === "") return "";

    // Достигнуто максимальное количество пользователей
    if ( $union['players'] >= 5 ) return "Участвовать могут максимум 5 игроков!";

    // Найти пользователя
    $name = mb_strtolower ($name, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."' LIMIT 1";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) return "Пользователь не найден";
    $user = dbarray ($result);

    // Проверить есть ли уже такой пользователь в САБе.
    for ($i=1; $i<=$union['players']; $i++)
    {
        if ( $union["player$i"] == $user['player_id'] ) return "Такой пользователь уже добавлен в союз";    // есть.
    }

    // Проверить является ли пользователем другом или соалом.
    if ( ! IsBuddy  ($GlobalUser['player_id'], $user['player_id']) )
    {
        if ($user['ally_id']) 
        {
            if (  ($user['ally_id'] != $GlobalUser['ally_id']) ) return "Пользователь должен быть в списке друзей или одном альянсе";
        }
        else return "Пользователь должен быть в списке друзей или одном альянсе";
    }

    // Добавить пользователя в САБ и послать ему сообщение о приглашении.
    $n = $union['players'] + 1;
    $query = "UPDATE ".$db_prefix."union SET player$n = ".$user['player_id'].", players = players + 1 WHERE union_id = $union_id";
    dbquery ($query);
    return "";
}

?>