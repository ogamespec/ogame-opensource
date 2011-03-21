<?php

// Управление флотами.

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
        if ( $id == 0 || $speed == 0 ) continue;
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
    return round ( (35000 / ($prc/10) * sqrt ($dist * 10 / $slowest_speed ) + 10) / $xspeed );
}

// Скорость кораблика
// 202-Р/И, 203-Р, 204-Р, 205-И, 206-И, 207-Г, 208-И, 209-Р, 210-Р, 211-И/Г, 212-Р, 213-Г, 214-Г, 215-Г
function FleetSpeed ( $id, $combustion, $impulse, $hyper)
{
    global $FleetParam;

    $baseSpeed = $FleetParam[$id][4];

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
    global $FleetParam;
    return $FleetParam[$id][3];
}

function FleetCons ($id, $combustion, $impulse, $hyper )
{
    global $FleetParam;
    if ($id == 202 && $impulse >= 5) return $FleetParam[$id][5] + 10;
    else return $FleetParam[$id][5];
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
function DispatchFleet ($fleet, $origin, $target, $order, $seconds)
{
    $now = time ();
    $prio = 0;
    $union_id = 0;
    $m = $k = $d = 0;
    $deploy_time = 0;

    // HACK.
    if ( $order == 6 ) $seconds = 30;

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
}

// Загрузить флот
function LoadFleet ($fleet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE fleet_id = '".$fleet_id."'";
    $result = dbquery ($query);
    return dbarray ($result);
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

        default: return "Неизвестно";
    }
}

// ==================================================================================
// Обработка заданий флота.

// Шпионаж.
function SpyArrive ($queue, $fleet_obj, $fleet)
{
    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );
    DispatchFleet ($fleet, $origin, $target, 106, 30);

    SendMessage ( $fleet_obj['owner_id'], "Управление флотом", "Разведданные", "Наши шпионы не нашли ничего нового.", 0);
    //Debug ("Шпионаж 1");
}

function SpyReturn ($queue, $fleet_obj, $fleet)
{
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
}

function Queue_Fleet_End ($queue)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $fleet_obj = LoadFleet ( $queue['sub_id'] );
    $fleet = array ();
    foreach ($fleetmap as $i=>$gid) $fleet[$gid] = $fleet_obj["ship$gid"];

    switch ( $fleet_obj['mission'] )
    {
        case 6: SpyArrive ($queue, $fleet_obj, $fleet); break;
        case 106: SpyReturn ($queue, $fleet_obj, $fleet); break;
    }

    RemoveQueue ( $queue['task_id'], 0 );
}

?>