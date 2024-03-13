<?php

// Управление флотами.

/*
fleet_id: Порядковый номер флота в таблице (INT AUTO_INCREMENT PRIMARY KEY)
owner_id: Номер пользователя, которому принадлежит флот (INT)
union_id: Номер союза, в котором летит флот (INT)
m, k, d: Перевозимый груз (металл/кристалл/дейтерий) (DOUBLE)
fuel: Загруженное топливо на полёт (дейтерий) (DOUBLE)
mission: Тип задания (INT)
start_planet: Старт (INT)
target_planet: Финиш (INT)
flight_time: Время полёта в одну сторону в секундах (INT)
deploy_time: Время удержания флота в секундах (INT)
ipm_amount: Количество межлпланетных ракет (INT)
ipm_target: id цели для межпланетных ракет, 0 - все (INT)
shipXX: количество кораблей каждого типа (INT)

Задания флота оформляются в виде события для глобальной очереди.

Отправление флота заключается в отнимании у планеты следующих полей: fXX (флот), m/k/d - ресурсы
Прибытие флота: добавляет эти поля (или опять отнимает, при атаке), а также генерирует сообщения.

Первые три страницы flottenX подготавливают параметры для страницы flottenversand, которая либо отправляет флот, либо возвращает ошибку.

Одно задание флота может по завершении порождать другое задание, например после достижения Транспорта, создается новое задание - возврат Транспорта.

В Обзоре все последующие задания "предсказываются", их нет на самом деле. В меню Флот показано описание заданий приближенное к данным базы данных.

Структура таблицы САБов:
union_id: ID союза (INT PRIMARY KEY)
fleet_id: ID головного флота САБа (исходной Атаки) (INT)
name: название союза. по умолчанию: "KV" + число (CHAR(20))
players: ID приглашенных игроков, через запятую (TEXT)

*/

// Список типов заданий флота
const FTYP_ATTACK = 1;      // Атака
const FTYP_ACS_ATTACK = 2;  // Совместная атака
const FTYP_TRANSPORT = 3;   // Транспорт
const FTYP_DEPLOY = 4;      // Оставить
const FTYP_ACS_HOLD = 5;    // Держаться
const FTYP_SPY = 6;         // Шпионаж
const FTYP_COLONIZE = 7;    // Колонизировать
const FTYP_RECYCLE = 8;     // Переработать
const FTYP_DESTROY = 9;     // Уничтожить
const FTYP_EXPEDITION = 15; // Экспедиция
const FTYP_MISSILE = 20;        // Ракетная атака
const FTYP_ACS_ATTACK_HEAD = 21;    // Атака САБ (паровоз)
const FTYP_RETURN = 100;            // Флот возвращается (добавить это значение к любой миссии)
const FTYP_ORBITING = 200;          // Флот находится на орбите (добавить это значение к любой миссии)

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

    $uni = LoadUniverse ();
    $origin = LoadPlanet ( $thisgalaxy, $thissystem, $thisplanet, $thisplanettype );
    $target = LoadPlanet ( $galaxy, $system, $planet, $planettype );

    if ( $planet >= 16 )
    {
        $missions[0] = FTYP_EXPEDITION;
        return $missions;
    }

    if ( $planettype == 2)        // поле обломков.
    {
        if ( $fleet[209] > 0 ) $missions[0] = FTYP_RECYCLE;    // если во флоте есть рабы
        return $missions;
    }

    if ( $target == NULL )        // пустое место
    {
        $missions[0] = FTYP_TRANSPORT;
        $missions[1] = FTYP_ATTACK;
        if ( $fleet[208] > 0 ) $missions[2] = FTYP_COLONIZE;    // если во флоте есть колонизатор
        return $missions;
    }

    if ( $origin['owner_id'] == $target['owner_id'] )        // свои луны/планеты
    {
        $missions[0] = FTYP_TRANSPORT;
        $missions[1] = FTYP_DEPLOY;
        return $missions;
    }
    else
    {
        $i = 0;
        $origin_user = LoadUser ($origin['owner_id']);
        $target_user = LoadUser ($target['owner_id']);

        if ( ( $origin_user['ally_id'] == $target_user['ally_id'] && $origin_user['ally_id'] > 0 )   || IsBuddy ( $origin_user['player_id'],  $target_user['player_id']) )      // соалы или друзья
        {
            $missions[$i++] = FTYP_TRANSPORT;
            $missions[$i++] = FTYP_ATTACK;
            if ( $uni['acs'] > 0 ) $missions[$i++] = FTYP_ACS_HOLD;
            if ( $fleet[214] > 0 && GetPlanetType($target) == 3 ) $missions[$i++] = FTYP_DESTROY;
            if ( $fleet[210] > 0  ) $missions[$i++] = FTYP_SPY;
        }
        else        // все остальные
        {
            $missions[$i++] = FTYP_TRANSPORT;
            $missions[$i++] = FTYP_ATTACK;
            if ( $fleet[214] > 0 && GetPlanetType($target) == 3 ) $missions[$i++] = FTYP_DESTROY;
            if ( $fleet[210] > 0  ) $missions[$i++] = FTYP_SPY;
        }

        // Если целевая планета есть в списке совместных атак, добавить задание
        $unions = EnumUnion ( $origin_user['player_id'] );
        foreach ( $unions as $u=>$union ) {
            $fleet_obj = LoadFleet ( $union['fleet_id'] );
            $fleet_target = GetPlanet ( $fleet_obj['target_planet'] );
            if ( $fleet_target['planet_id'] == $target['planet_id'] ) {
                $missions[$i++] = FTYP_ACS_ATTACK;
                break;
            }
        }
        return $missions;
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
function FlightCons ($fleet, $dist, $flighttime, $combustion, $impulse, $hyper, $speedfactor, $hours=0)
{
    $cons = array ( 'fleet' => 0, 'probes' => 0 );
    foreach ($fleet as $id=>$amount)
    {
        if ($amount > 0) {
            $spd = 35000 / ( $flighttime * $speedfactor - 10) * sqrt($dist * 10 / FleetSpeed($id, $combustion, $impulse, $hyper ) );
            $basecons = $amount * FleetCons ($id, $combustion, $impulse, $hyper );
            $consumption = $basecons * $dist / 35000 * (($spd / 10) + 1) * (($spd / 10) + 1);
            $consumption += $hours * $amount * FleetCons ($id, $combustion, $impulse, $hyper ) / 10;    // затраты на удержание
            if ( $id == 210 ) $cons['probes'] += $consumption;
            else $cons['fleet'] += $consumption;
        }
    }
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

// Суммарная грузоподъемность флотa
function FleetCargoSummary ( $fleet )
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $cargo = 0;
    foreach ( $fleetmap as $n=>$gid )
    {
        $amount = $fleet[$gid];
        if ($gid != 210) $cargo += FleetCargo ($gid) * $amount;        // не считать зонды.
    }
    return $cargo;
}

function FleetCons ($id, $combustion, $impulse, $hyper )
{
    global $UnitParam;
    // У МТ при смене двигателя увеличивается потребление в 2 раза. У бомбардировщика НЕ увеличивается.
    if ($id == 202 && $impulse >= 5) return $UnitParam[$id][5] * 2;
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
    dbquery ($query);
}

// Отправить флот. Никаких проверок не производится. Возвращает ID флота.
function DispatchFleet ($fleet, $origin, $target, $order, $seconds, $m, $k ,$d, $cons, $when, $union_id=0, $deploy_time=0)
{
    global $db_prefix;
    $uni = LoadUniverse ( );
    if ( $uni['freeze'] ) return;

    $now = $when;
    $prio = 200 + $order;
    $flight_time = $seconds;

    // Добавить флот.
    $fleet_obj = array ( null, $origin['owner_id'], $union_id, $m, $k, $d, $cons, $order, $origin['planet_id'], $target['planet_id'], $flight_time, $deploy_time,
                         0, 0, $fleet[202], $fleet[203], $fleet[204], $fleet[205], $fleet[206], $fleet[207], $fleet[208], $fleet[209], $fleet[210], $fleet[211], $fleet[212], $fleet[213], $fleet[214], $fleet[215] );
    $fleet_id = AddDBRow ($fleet_obj, 'fleet');

    // Запись в лог
    $weeks = $now - 4 * (7 * 24 * 60 * 60);
    $query = "DELETE FROM ".$db_prefix."fleetlogs WHERE start < $weeks;";
    dbquery ($query);
    $fleetlog = array ( null, $origin['owner_id'], $target['owner_id'], $union_id, $origin['m'], $origin['k'], $origin['d'], $m, $k, $d, $cons, $order, $flight_time, $deploy_time, $now, $now+$seconds, 
                        $origin['g'], $origin['s'], $origin['p'], $origin['type'], $target['g'], $target['s'], $target['p'], $target['type'], 
                        0, 0, $fleet[202], $fleet[203], $fleet[204], $fleet[205], $fleet[206], $fleet[207], $fleet[208], $fleet[209], $fleet[210], $fleet[211], $fleet[212], $fleet[213], $fleet[214], $fleet[215] );
    AddDBRow ($fleetlog, 'fleetlogs');

    // Добавить задание в глобальную очередь событий.
    AddQueue ( $origin['owner_id'], "Fleet", $fleet_id, 0, 0, $now, $seconds, $prio );
    return $fleet_id;
}

// Отозвать флот (если это возможно)
function RecallFleet ($fleet_id, $now=0)
{
    $uni = LoadUniverse ( );
    if ( $uni['freeze'] ) return;

    if ($now == 0) $now = time ();
    $fleet_obj = LoadFleet ($fleet_id);
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $fleet = array ();
    foreach ($fleetmap as $i=>$gid) $fleet[$gid] = $fleet_obj["ship$gid"];

    // Если флот уже развернут, ничего не делать
    if ( $fleet_obj['mission'] >= FTYP_RETURN && $fleet_obj['mission'] < FTYP_ORBITING ) return;

    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );
    $queue = GetFleetQueue ($fleet_obj['fleet_id']);

    if ($fleet_obj['mission'] < FTYP_RETURN) $new_mission = $fleet_obj['mission'] + FTYP_RETURN;
    else $new_mission = $fleet_obj['mission'] - FTYP_RETURN;
    UserLog ( $fleet_obj['owner_id'], "FLEET", 
     "Отзыв флота ".$fleet_obj['fleet_id'].": " . GetMissionNameDebug ($new_mission) . " " .
     $origin['name'] ." [".$origin['g'].":".$origin['s'].":".$origin['p']."] &lt;- ".$target['name']." [".$target['g'].":".$target['s'].":".$target['p']."]<br>" .
     DumpFleet ($fleet) );

    // Для отзыва миссий с удержанием в качестве времени обратного полёта используется время удержания.
    if ($fleet_obj['mission'] < FTYP_RETURN) DispatchFleet ($fleet, $origin, $target, $fleet_obj['mission'] + FTYP_RETURN, $now-$queue['start'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $now);
    else DispatchFleet ($fleet, $origin, $target, $fleet_obj['mission'] - FTYP_RETURN, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $now);

    DeleteFleet ($fleet_obj['fleet_id']);            // удалить флот
    RemoveQueue ( $queue['task_id'] );    // удалить задание

    // Если отозван последний флот союза, то удалить союз.
    $union_id = $fleet_obj['union_id'];
    if ( $union_id && ( $fleet_obj['mission'] == FTYP_ACS_ATTACK || $fleet_obj['mission'] == FTYP_ACS_ATTACK_HEAD ) ) 
    {
        $result = EnumUnionFleets ($union_id);
        if ( dbrows ( $result ) == 0 ) RemoveUnion ( $union_id );    // удалить союз
    }
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

// Изменить флот.
function SetFleet ($fleet_id, $fleet)
{
    global $db_prefix;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $query = "UPDATE ".$db_prefix."fleet SET ";
    foreach ( $fleetmap as $i=>$gid ) {
        if ( $i == 0 ) $query .= "ship".$gid."=".$fleet[$gid];
        else $query .= ", ship".$gid."=".$fleet[$gid];
    }
    $query .= " WHERE fleet_id=$fleet_id;";
    dbquery ($query);
}

// Получить описание задания (для отладки)
function GetMissionNameDebug ($num)
{
    switch ($num)
    {
        case FTYP_ATTACK    :      return "Атака убывает";
        case (FTYP_ATTACK+FTYP_RETURN) :      return "Атака возвращается";
        case FTYP_ACS_ATTACK    :      return "Совместная атака убывает";
        case (FTYP_ACS_ATTACK+FTYP_RETURN) :     return "Совместная атака возвращается";
        case FTYP_TRANSPORT    :     return "Транспорт убывает";
        case (FTYP_TRANSPORT+FTYP_RETURN) :     return "Транспорт возвращается";
        case FTYP_DEPLOY    :     return "Оставить убывает";
        case (FTYP_DEPLOY+FTYP_RETURN) :     return "Оставить возвращается";
        case FTYP_ACS_HOLD   :      return "Держаться убывает";
        case (FTYP_ACS_HOLD+FTYP_RETURN) :     return "Держаться возвращается";
        case (FTYP_ACS_HOLD+FTYP_ORBITING) :    return "Держаться на орбите";
        case FTYP_SPY   :      return "Шпионаж убывает";
        case (FTYP_SPY+FTYP_RETURN) :     return "Шпионаж возвращается";
        case FTYP_COLONIZE    :     return "Колонизировать убывает";
        case (FTYP_COLONIZE+FTYP_RETURN) :     return "Колонизировать возвращается";
        case FTYP_RECYCLE    :     return "Переработать убывает";
        case (FTYP_RECYCLE+FTYP_RETURN) :    return "Переработать возвращается";
        case FTYP_DESTROY   :      return "Уничтожить убывает";
        case (FTYP_DESTROY+FTYP_RETURN):      return "Уничтожить возвращается";
        case 14  :      return "Испытание убывает";             // wtf ???
        case (14+FTYP_RETURN):      return "Испытание возвращается";           // wtf ???
        case FTYP_EXPEDITION  :      return "Экспедиция убывает";
        case (FTYP_EXPEDITION+FTYP_RETURN):      return "Экспедиция возвращается";
        case (FTYP_EXPEDITION+FTYP_ORBITING):      return "Экспедиция на орбите";
        case FTYP_MISSILE:       return "Ракетная атака";
        case FTYP_ACS_ATTACK_HEAD  :      return "Атака САБ убывает";
        case (FTYP_ACS_ATTACK_HEAD+FTYP_RETURN) :      return "Атака САБ возвращается";

        default: return "Неизвестно";
    }
}

// Запустить межпланетные ракеты
function LaunchRockets ( $origin, $target, $seconds, $amount, $type )
{
    global $db_prefix;
    $uni = LoadUniverse ( );
    if ( $uni['freeze'] ) return;

    if ( $amount > $origin['d503'] ) return;    // Нельзя запустить ракет больше чем имеется на планете

    $now = time ();
    $prio = 200 + FTYP_MISSILE;

    // Списать МПР с планеты.
    $origin['d503'] -= $amount;
    SetPlanetDefense ( $origin['planet_id'], $origin );

    // Добавить ракетную атаку.
    $fleet_obj = array ( null, $origin['owner_id'], 0, 0, 0, 0, 0, FTYP_MISSILE, $origin['planet_id'], $target['planet_id'], $seconds, 0,
                         $amount, $type, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    $fleet_id = AddDBRow ($fleet_obj, 'fleet');

    // Запись в лог
    $weeks = $now - 4 * (7 * 24 * 60 * 60);
    $query = "DELETE FROM ".$db_prefix."fleetlogs WHERE start < $weeks;";
    dbquery ($query);
    $fleetlog = array ( null, $origin['owner_id'], $target['owner_id'], 0, 0, 0, 0, 0, 0, 0, 0, FTYP_MISSILE, $seconds, 0, $now, $now+$seconds, 
                        $origin['g'], $origin['s'], $origin['p'], $origin['type'], $target['g'], $target['s'], $target['p'], $target['type'], 
                        $amount, $type, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    AddDBRow ($fleetlog, 'fleetlogs');

    // Добавить задание в глобальную очередь событий.
    AddQueue ( $origin['owner_id'], "Fleet", $fleet_id, 0, 0, $now, $seconds, $prio );
    return $fleet_id;
}

// ==================================================================================
// Обработка заданий флота.

function FleetList ($fleet, $lang)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $res = "";
    foreach ( $fleetmap as $i=>$gid )
    {
        if ($fleet[$gid] > 0) $res .= loca_lang("NAME_$gid", $lang) . ": " . nicenum ($fleet[$gid]) . " ";
    }
    return $res;
}

// *** Атака ***

function AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
}

// *** Транспорт ***

function TransportArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    $oldm = $target['m'];
    $oldk = $target['k'];
    $oldd = $target['d'];

    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $target['planet_id'], '+' );
    UpdatePlanetActivity ( $target['planet_id'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    DispatchFleet ($fleet, $origin, $target, FTYP_TRANSPORT+FTYP_RETURN, $fleet_obj['flight_time'], 0, 0, 0, $fleet_obj['fuel'] / 2, $queue['end']);

    $text = va(loca_lang("FLEET_TRANSPORT_OWN", $origin_user['lang']), 
            "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
            nicenum($fleet_obj['m']),
            nicenum($fleet_obj['k']),
            nicenum($fleet_obj['d']) );
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_ARRIVE", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);

    // Транспорт на чужую планету.
    if ( $origin['owner_id'] != $target['owner_id'] )
    {
        $target_user = LoadUser ( $target['owner_id'] );
        loca_add ( "fleetmsg", $target_user['lang'] );

        $text = va(loca_lang("FLEET_TRANSPORT_OTHER", $target_user['lang']),
                $origin_user['oname'],
                $target['name'],
                "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
                nicenum($fleet_obj['m']),
                nicenum($fleet_obj['k']),
                nicenum($fleet_obj['d']),
                nicenum($oldm),
                nicenum($oldk),
                nicenum($oldd),
                nicenum($oldm+$fleet_obj['m']),
                nicenum($oldk+$fleet_obj['k']),
                nicenum($oldd+$fleet_obj['d']) );
        SendMessage ( $target['owner_id'], 
            loca_lang("FLEET_MESSAGE_OBSERVE", $target_user['lang']), 
            loca_lang("FLEET_MESSAGE_TRADE", $target_user['lang']), 
            $text, MTYP_MISC, $queue['end']);
    }
}

function CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target)
{
    if ( $fleet_obj['m'] < 0 ) $fleet_obj['m'] = 0;    // Защита от отрицательных ресурсов (на всякий случай)
    if ( $fleet_obj['k'] < 0 ) $fleet_obj['k'] = 0;
    if ( $fleet_obj['d'] < 0 ) $fleet_obj['d'] = 0;

    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
    UpdatePlanetActivity ( $fleet_obj['start_planet'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_RETURN", $origin_user['lang']),
        FleetList($fleet, $origin_user['lang']),
        "<a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
        $origin['name'],
        "<a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>" );
    if ( ($fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d']) != 0 ) {
        $text .= va(loca_lang("FLEET_RETURN_RES", $origin_user['lang']), 
            nicenum($fleet_obj['m']),
            nicenum($fleet_obj['k']),
            nicenum($fleet_obj['d']) );
    }
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_RETURN", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);
}

// *** Оставить ***

function DeployArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    // Также выгрузить половину топлива
    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'] + floor ($fleet_obj['fuel'] / 2), $target['planet_id'], '+' );
    AdjustShips ( $fleet, $fleet_obj['target_planet'], '+' );
    UpdatePlanetActivity ( $target['planet_id'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_DEPLOY", $origin_user['lang']),
        FleetList($fleet, $origin_user['lang']),
        $target['name'],
        "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>" );
    $text .= va(loca_lang("FLEET_DEPLOY_RES", $origin_user['lang']),
        nicenum($fleet_obj['m']),
        nicenum($fleet_obj['k']),
        nicenum($fleet_obj['d'] + floor ($fleet_obj['fuel'] / 2)) );
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_HOLD", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);
}

// *** Держаться ***

// Посчитать количество флотов, отправленных на удержание на указанной планете (летящих и находящихся на орбите)
function GetHoldingFleetsCount ($planet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE (mission = ".FTYP_ACS_HOLD." OR mission = ".(FTYP_ACS_HOLD+FTYP_ORBITING).") AND target_planet = $planet_id;";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Проверить можно ли отправить флот игроку на удержание на планету (одновременно на планете могут удерживать свои флоты не более XX игроков)
function CanStandHold ( $planet_id, $player_id )
{
    return true;
}

function HoldingArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    // Обновить активность на планете.
    UpdatePlanetActivity ( $fleet_obj['target_planet'], $queue['end'] );

    // Запустить задание удержания на орбите.
    // Время удержания сделать временем полёта (чтобы потом его можно было использовать при возврате флота)
    DispatchFleet ($fleet, $origin, $target, FTYP_ACS_HOLD+FTYP_ORBITING, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end'], 0, $fleet_obj['flight_time']);
}

function HoldingHold ($queue, $fleet_obj, $fleet, $origin, $target)
{
    // Вернуть флот.
    // В качестве времени полёта используется время удержания.
    DispatchFleet ($fleet, $origin, $target, FTYP_ACS_HOLD+FTYP_RETURN, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end']);
}

// *** Шпионаж ***

function SpyArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    global $UnitParam;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );
    $buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );
    $resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );

    $now = $queue['end'];

    $origin_user = LoadUser ( $origin['owner_id'] );
    $target_user = LoadUser ( $target['owner_id'] );

    $origin_ships = $target_ships = $origin_cost = 0;
    foreach ( $fleetmap as $i=>$gid )
    {
        $origin_ships += $fleet_obj["ship$gid"];
        $origin_cost += $fleet_obj["ship$gid"] * $UnitParam[$gid][0];
        $target_ships += $target["f$gid"];
    }

    $origin_prem = PremiumStatus ($origin_user);
    $target_prem = PremiumStatus ($target_user);
    $origin_tech = $origin_user['r106'];
    if ($origin_prem['technocrat']) $origin_tech += 2;
    $target_tech = $target_user['r106'];
    if ($target_prem['technocrat']) $target_tech += 2;

    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "espionage", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );
    loca_add ( "fleetmsg", $target_user['lang'] );

    // Шанс на защиту от шпионажа
    $level = $origin_tech - $target_tech;
    $level = $level * abs($level) - 1 + $origin_ships;
    $cost = $origin_cost / 1000 / 400;
    $c = sqrt ( pow (2,($origin_ships-($level+1))) ) * ($cost * sqrt($target_ships)*5);
    if ($c > 2) $c = 2;
    $c = rand (0, $c*100) / 100;
    if ($c < 0) $c = 0;
    if ($c > 1) $c = 1;
    $counter = $c * 100;

    $subj = "\n<span class=\"espionagereport\">\n" .
                va(loca_lang("SPY_SUBJ", $origin_user['lang']), $target['name']) . "\n" .
                "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>\n";

    $report = "";

    // Шапка
    $report .= "<table width=400><tr><td class=c colspan=4>" .
            va(loca_lang("SPY_RESOURCES", $origin_user['lang']), $target['name']) .
            " <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a> " .
            va(loca_lang("SPY_PLAYER", $origin_user['lang']), $target_user['oname'], date ("m-d H:i:s", $now)) .
            "</td></tr>\n";
    $report .= "</div></font></TD></TR><tr><td>".loca_lang("SPY_M", $origin_user['lang'])."</td><td>".nicenum($target['m'])."</td>\n";
    $report .= "<td>".loca_lang("SPY_K", $origin_user['lang'])."</td><td>".nicenum($target['k'])."</td></tr>\n";
    $report .= "<tr><td>".loca_lang("SPY_D", $origin_user['lang'])."</td><td>".nicenum($target['d'])."</td>\n";
    $report .= "<td>".loca_lang("SPY_E", $origin_user['lang'])."</td><td>".nicenum($target['emax'])."</td></tr>\n";
    $report .= "</table>\n";

    // Активность
    $report .= "<table width=400><tr><td class=c colspan=4>     </td></tr>\n";
    $report .= "<TR><TD colspan=4><div onmouseover='return overlib(\"&lt;font color=white&gt;".loca_lang("SPY_ACTIVITY", $origin_user['lang'])."&lt;/font&gt;\", STICKY, MOUSEOFF, DELAY, 750, CENTER, WIDTH, 100, OFFSETX, -130, OFFSETY, -10);' onmouseout='return nd();'></TD></TR></table>\n";

    // Флот на удержании
    $result = GetHoldingFleets ( $target['planet_id'] );
    $holding_fleet = array ();
    foreach ( $fleetmap as $i=>$gid ) {
        $holding_fleet[$gid] = 0;
    }    
    while ( $fobj = dbarray ($result) )
    {
        foreach ( $fleetmap as $i=>$gid ) {
            $holding_fleet[$gid] += $fobj["ship$gid"];
        }
    }

    // Флот
    if ( $level > 0 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_FLEET", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $fleetmap as $i=>$gid )
        {
            $amount = $target["f$gid"] + $holding_fleet[$gid];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    // Оборона
    if ( $level > 1 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_DEFENSE", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $defmap as $i=>$gid )
        {
            $amount = $target["d$gid"];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    // Постройки
    if ( $level > 3 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_BUILDINGS", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $buildmap as $i=>$gid )
        {
            $amount = $target["b$gid"];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    // Исследования
    if ( $level > 5 ) {
        $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_RESEARCH", $origin_user['lang'])."     </td></tr>\n";
        $count = 0;
        foreach ( $resmap as $i=>$gid )
        {
            $amount = $target_user["r$gid"];
            if ($amount > 0) {
                if ( ($count % 2) == 0 ) $report .= "</tr>\n";
                $report .= "<td>".loca_lang("NAME_$gid", $origin_user['lang'])."</td><td>".nicenum($amount)."</td>\n";
                $count++;
            }
        }
        $report .= "</table>\n";
    }

    $report .= "<center>".va(loca_lang("SPY_COUNTER", $origin_user['lang']), floor($counter))."</center>\n";
    $report .= "<center><a href='#' onclick='showFleetMenu(".$target['g'].",".$target['s'].",".$target['p'].",".GetPlanetType($target).",1);'>".loca_lang("SPY_ATTACK", $origin_user['lang'])."</a></center>\n";

    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        $subj, 
        $report, MTYP_SPY_REPORT, $queue['end'], $target['planet_id']);

    // Отправить сообщение чужому игроку о шпионаже.
    $text = va(loca_lang("FLEET_SPY_OTHER", $target_user['lang']), 
            $origin['name'],
            "<a onclick=\"showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p'].");\" href=\"#\">[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>",
            $target['name'],
            "<a onclick=\"showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].");\" href=\"#\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
            $counter ) . 
            "</td>\n";      // wtf?
    SendMessage ( $target['owner_id'],
        loca_lang("FLEET_MESSAGE_OBSERVE", $target_user['lang']),
        loca_lang("FLEET_MESSAGE_SPY", $target_user['lang']),
        $text, MTYP_MISC, $queue['end']);

    // Обновить активность на чужой планете.
    UpdatePlanetActivity ( $fleet_obj['target_planet'], $queue['end'] );

    // Вернуть флот.
    if ( mt_rand (0, 100) < $counter ) StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
    else DispatchFleet ($fleet, $origin, $target, FTYP_SPY+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
}

function SpyReturn ($queue, $fleet_obj, $fleet)
{
    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
    UpdatePlanetActivity ( $fleet_obj['start_planet'], $queue['end'] );
}

// *** Колонизировать ***

function ColonizationArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    global $db_prefix;

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_COLONIZE", $origin_user['lang']), 
                "<a href=\"javascript:showGalaxy(".$target['g'].",".$target['s'].",".$target['p'].")\">[".$target['g'].":".$target['s'].":".$target['p']."]</a>" );

    if ( !HasPlanet($target['g'], $target['s'], $target['p']) )    // если место не занято, то значит колонизация успешна
    {
        // если количество планет империи больше максимума, то не основывать новую колонию.
        $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".$fleet_obj['owner_id']."' AND (type > ".PTYP_MOON." AND type < ".PTYP_DF.");";
        $result = dbquery ($query);
        $num_planets = dbrows ($result);
        if ( $num_planets >= 9 )
        {
            $text .= loca_lang("FLEET_COLONIZE_MAX", $origin_user['lang']);

            // Добавить покинутую колонию.
            $id = CreateAbandonedColony ( $target['g'], $target['s'], $target['p'], $queue['end'] );
        }
        else
        {
            $text .= loca_lang("FLEET_COLONIZE_SUCCESS", $origin_user['lang']);

            // Создать новую колонию.
            $id = CreatePlanet ( $target['g'], $target['s'], $target['p'], $fleet_obj['owner_id'], 1, 0, 0, $queue['end'] );
            Debug ( "Игроком ".$origin['owner_id']." колонизирована планета $id [".$target['g'].":".$target['s'].":".$target['p']."]");

            // Отнять от флота 1 колонизатор
            if ( $fleet[208] > 0 ) {
                $fleet[208]--;
                $met = $kris = $deut = $energy = 0;
                $cost = ShipyardPrice ( 208 );
                AdjustStats ( $origin['owner_id'], ($cost['m'] + $cost['k'] + $cost['d']), 1, 0, '-' );
                RecalcRanks ();
            }
        }

        // Вернуть флот, если что-то осталось.
        $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
        $num_ships = 0;
        foreach ($fleetmap as $i=>$gid) {
            $num_ships += $fleet[$gid];
        }
        if ($num_ships > 0) {
            if ($target['type'] == PTYP_COLONY_PHANTOM) DestroyPlanet ( $target['planet_id'] );
            $target = GetPlanet ($id);
            DispatchFleet ($fleet, $origin, $target, FTYP_COLONIZE+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
        }
        else {
            if ($target['type'] == PTYP_COLONY_PHANTOM) DestroyPlanet ( $target['planet_id'] );
        }
    }
    else
    {
        $text .= loca_lang("FLEET_COLONIZE_FAIL", $origin_user['lang']);

        // Вернуть флот.
        DispatchFleet ($fleet, $origin, $target, FTYP_COLONIZE+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
    }

    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_COLONIZE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_COLONIZE_SUBJ", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);
}

function ColonizationReturn ($queue, $fleet_obj, $fleet, $origin, $target)
{
    AdjustResources ( $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['start_planet'], '+' );
    AdjustShips ( $fleet, $fleet_obj['start_planet'], '+' );
    UpdatePlanetActivity ( $fleet_obj['start_planet'], $queue['end'] );

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $text = va(loca_lang("FLEET_RETURN", $origin_user['lang']), 
            FleetList($fleet, $origin_user['lang']),
            "<a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a>",
            $origin['name'],
            "<a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>" );
    if ( ($fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d']) != 0 ) {
        $text .= va(loca_lang("FLEET_RETURN_RES", $origin_user['lang']), 
            nicenum($fleet_obj['m']),
            nicenum($fleet_obj['k']),
            nicenum($fleet_obj['d']) );
    }
    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("FLEET_MESSAGE_RETURN", $origin_user['lang']), 
        $text, MTYP_MISC, $queue['end']);

    // Удалить фантом колонизации.
    if ($target['type'] == PTYP_COLONY_PHANTOM) DestroyPlanet ( $target['planet_id'] );
}

// *** Переработать ***

function RecycleArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    if ( $fleet[209] == 0 ) Error ( "Попытка сбора ПО без переработчиков" );
    if ( $target['type'] != PTYP_DF ) Error ( "Перерабатывать можно только поля обломков!" );

    $sum_cargo = FleetCargoSummary ( $fleet ) - ($fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d']);
    $recycler_cargo = FleetCargo (209) * $fleet[209];
    $cargo = min ($recycler_cargo, $sum_cargo);

    $harvest = HarvestDebris ( $target['planet_id'], $cargo, $queue['end'] );
    $dm = $harvest['m'];
    $dk = $harvest['k'];

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    $subj = "\n<span class=\"espionagereport\">".loca_lang("FLEET_MESSAGE_INTEL", $origin_user['lang'])."</span>\n";   
    $report = va(loca_lang("FLEET_RECYCLE", $origin_user['lang']), 
        nicenum($fleet[209]),
        nicenum($cargo),
        nicenum($target['m']),
        nicenum($target['k']),
        nicenum($dm),
        nicenum($dk) );

    // Вернуть флот.
    DispatchFleet ($fleet, $origin, $target, FTYP_RECYCLE+FTYP_RETURN, $fleet_obj['flight_time'], $fleet_obj['m'] + $dm, $fleet_obj['k'] + $dk, $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);

    SendMessage ( $fleet_obj['owner_id'], loca_lang("FLEET_MESSAGE_FLEET", $origin_user['lang']), $subj, $report, MTYP_MISC, $queue['end']);
}

// *** Уничтожить ***

function DestroyArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    StartBattle ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
}

// *** Экспедиция ***

require_once "expedition.php";

// *** Ракетная атака ***

function RocketAttackArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    RocketAttack ( $fleet_obj['fleet_id'], $fleet_obj['target_planet'], $queue['end'] );
}

function Queue_Fleet_End ($queue)
{
    global $GlobalUser;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $fleet_obj = LoadFleet ( $queue['sub_id'] );
    if ( $fleet_obj == null ) return;

    if ( $fleet_obj['m'] < 0 ) $fleet_obj['m'] = 0;
    if ( $fleet_obj['k'] < 0 ) $fleet_obj['k'] = 0;
    if ( $fleet_obj['d'] < 0 ) $fleet_obj['d'] = 0;

    $fleet = array ();
    foreach ($fleetmap as $i=>$gid) $fleet[$gid] = $fleet_obj["ship$gid"];

    // Обновить выработку ресурсов на планетах
    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );
    $target = ProdResources ( $target, $target['lastpeek'], $queue['end'] );
    $origin = ProdResources ( $origin, $origin['lastpeek'], $queue['end'] );

    switch ( $fleet_obj['mission'] )
    {
        case FTYP_ATTACK: AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ATTACK+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_ACS_ATTACK: AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_ATTACK+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_TRANSPORT: TransportArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_TRANSPORT+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_DEPLOY: DeployArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_DEPLOY+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_ACS_HOLD: HoldingArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_HOLD+FTYP_ORBITING): HoldingHold ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_HOLD+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_SPY: SpyArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_SPY+FTYP_RETURN): SpyReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_COLONIZE: ColonizationArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_COLONIZE+FTYP_RETURN): ColonizationReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_RECYCLE: RecycleArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_RECYCLE+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_DESTROY: DestroyArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_DESTROY+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_EXPEDITION: ExpeditionArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_EXPEDITION+FTYP_ORBITING): ExpeditionHold ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_EXPEDITION+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_MISSILE: RocketAttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case FTYP_ACS_ATTACK_HEAD: AttackArrive ($queue, $fleet_obj, $fleet, $origin, $target); break;
        case (FTYP_ACS_ATTACK_HEAD+FTYP_RETURN): CommonReturn ($queue, $fleet_obj, $fleet, $origin, $target); break;
        //default: Error ( "Неизвестное задание для флота: " . $fleet_obj['mission'] ); break;
    }

    if ( $fleet_obj['union_id'] && $fleet_obj['mission'] < FTYP_RETURN )    // удалить все флоты и задания союза, чтобы совместная атака больше не срабатывала
    {
        $union_id = $fleet_obj['union_id'];
        $result = EnumUnionFleets ( $union_id );
        $rows = dbrows ($result);
        while ($rows--)
        {
            $fleet_obj = dbarray ($result);
            $queue = GetFleetQueue ( $fleet_obj['fleet_id'] );
            DeleteFleet ($fleet_obj['fleet_id']);    // удалить флот
            RemoveQueue ( $queue['task_id'] );    // удалить задание
        }
        RemoveUnion ( $union_id );    // удалить союз
    }
    else
    {
        DeleteFleet ($fleet_obj['fleet_id']);    // удалить флот
        RemoveQueue ( $queue['task_id'] );    // удалить задание
    }

    $player_id = $fleet_obj['owner_id'];
    if ( $GlobalUser['player_id'] == $player_id) { 
        InvalidateUserCache ();
        $GlobalUser = LoadUser ( $player_id );    // обновить данные текущего пользователя
    }
}

// ==================================================================================

// Управление САБами.

// Создать САБ. $fleet_id - паровоз. $name - название союза.
function CreateUnion ($fleet_id, $name)
{
    global $db_prefix;

    $fleet_obj = LoadFleet ($fleet_id);

    // Проверить есть ли уже союз?
    if ( $fleet_obj['union_id'] != 0 ) return $fleet_obj['union_id'];

    // Союзы можно создавать только для убывающих атак.
    if ($fleet_obj['mission'] != 1) return 0;

    $target_planet = GetPlanet ( $fleet_obj['target_planet'] );
    $target_player = $target_planet['owner_id'];

    // Нельзя создать союз против себя самого
    if ( $target_player == $fleet_obj['owner_id'] ) return 0;

    // Добавить союз.
    $union = array ( null, $fleet_id, $target_player, $name, $fleet_obj['owner_id'] );
    $union_id = AddDBRow ($union, 'union');

    // Добавить флот в союз и изменить тип Атаки (лидер САБа показывается особым образом в списке событий)
    $query = "UPDATE ".$db_prefix."fleet SET union_id = $union_id, mission = ".FTYP_ACS_ATTACK_HEAD." WHERE fleet_id = $fleet_id";
    dbquery ($query);
    return $union_id;
}

function LoadUnion ($union_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."union WHERE union_id = $union_id";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return null;
    $union = dbarray ($result);
    $union['player'] = explode (",", $union['players'] );
    $union['players'] = count ($union['player']);
    return $union;
}

// Союз удаляется при отзыве последнего флота союза, или достижении цели
function RemoveUnion ($union_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."union WHERE union_id = $union_id";        // удалить запись союза
    dbquery ($query);
}

// Переименовать САБ.
function RenameUnion ($union_id, $name)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."union SET name = '".$name."' WHERE union_id = " . intval ($union_id);
    dbquery ($query);
}

// Добавить нового участника в САБ.
function AddUnionMember ($union_id, $name)
{
    global $db_prefix;
    global $GlobalUni;
    global $GlobalUser;
    $union = LoadUnion ($union_id);

    // Ошибка добавления игрока в САБ выдаётся на языке текущего пользователя (того, кто добавляет игроков через меню Флот)
    loca_add ("union", $GlobalUser['lang']);

    // Пустое имя, ничего не делаем.
    if ($name === "") return "";

    // Достигнуто максимальное количество пользователей
    $max_players = $GlobalUni['acs'] + 1;
    if ( $union['players'] >= $max_players ) return va(loca("ACS_MAX_USERS"), $max_players);

    // Найти пользователя
    $name = mb_strtolower ($name, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."' LIMIT 1";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) return loca("ACS_USER_NOT_FOUND");
    $user = dbarray ($result);

    // Проверить есть ли уже такой пользователь в САБе.
    for ($i=0; $i<$union['players']; $i++)
    {
        if ( $union["player"][$i] == $user['player_id'] ) return loca("ACS_ALREADY_ADDED");    // есть.
    }

    // Добавить пользователя в САБ и послать ему сообщение о приглашении.
    $union['player'][$union['players']] = $user['player_id'];
    $query = "UPDATE ".$db_prefix."union SET players = '".implode(",", $union['player'])."' WHERE union_id = $union_id";
    dbquery ($query);

    $target_player = LoadUser ( $union['target_player'] );
    $head_fleet = LoadFleet ( $union['fleet_id'] );
    $target_planet = GetPlanet ( $head_fleet['target_planet'] );
    $queue = GetFleetQueue ( $union['fleet_id'] );

    // Сообщение о приглашении в САБ отправляется на языке приглашаемого пользователя.
    loca_add ("union", $user['lang']);

    $text = va ( loca_lang("ACS_INVITE_TEXT1", $user['lang']),
                        $GlobalUser['oname'], 
                        $union['name'], 
                        $target_player['oname'] ) .
            va (" <a href=\"#\" onClick=showGalaxy(#1,#2,#3)><b><u>[#4:#5:#6]</u></b></a>. ",
                        $target_planet['g'], $target_planet['s'], $target_planet['p'], 
                        $target_planet['g'], $target_planet['s'], $target_planet['p'] ) .
            va ( loca_lang("ACS_INVITE_TEXT2", $user['lang']), date ( "D M Y H:i:s", $queue['end'] ) );
    SendMessage ( $user['player_id'], $GlobalUser['oname'], loca_lang("ACS_INVITE_SUBJ", $user['lang']), $text, MTYP_MISC );

    return "";
}

// Перечислить союзы в которых состоит игрок, а также союзы, целью которых он является (если не установлен флаг friendly).
function EnumUnion ($player_id, $friendly=0)
{
    global $db_prefix;
    $count = 0;
    $unions = array ();
    $query = "SELECT * FROM ".$db_prefix."union ";
    $result = dbquery ($query);
    $rows = dbrows ($result);
    while ($rows--)
    {
        $union = dbarray ($result);
        $union['player'] = explode (",", $union['players'] );
        $union['players'] = count ($union['player']);
        for ($i=0; $i<$union['players']; $i++) {
            if ( $union["player"][$i] == $player_id || ( $union['target_player'] == $player_id && !$friendly )) { $unions[$count++] = $union; break; }
        }
    }
    return $unions;
}

// Перечислить флоты союза
function EnumUnionFleets ($union_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE union_id = $union_id";
    return dbquery ( $query );
}

// Обновить время прибытия всех флотов союза, за исключением fleet_id. Вернуть новое время прибытия союза.
function UpdateUnionTime ($union_id, $end, $fleet_id, $force_set=false)
{
    global $db_prefix;
    $result = EnumUnionFleets ($union_id);
    $rows = dbrows ($result);
    while ($rows--)
    {
        $fleet_obj = dbarray ($result);
        if ( $fleet_obj['fleet_id'] == $fleet_id ) continue;
        $queue = GetFleetQueue ( $fleet_obj['fleet_id'] );
        $union_time = $queue['end'];
        $queue_id = $queue['task_id'];
        if ( $end > $union_time || $force_set )
        {
            $union_time = $end;
            $query = "UPDATE ".$db_prefix."queue SET end = $end WHERE task_id = $queue_id";
            dbquery ($query);
        }
    }
    return $union_time;
}

// Обновить время прибытия флота
function UpdateFleetTime ($fleet_id, $when)
{
    global $db_prefix;
    $queue = GetFleetQueue ($fleet_id);
    $queue_id = $queue['task_id'];
    $query = "UPDATE ".$db_prefix."queue SET end = $when WHERE task_id = $queue_id";
    dbquery ($query);
}

// Перечислить флоты на удержании
function GetHoldingFleets ($planet_id)
{
    global $db_prefix;
    $uni = LoadUniverse ();    // ограничить количество флотов настройками вселенной
    $max = max (0, $uni['acs'] * $uni['acs'] - 1);
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE mission = ".(FTYP_ORBITING+FTYP_ACS_HOLD)." AND target_planet = $planet_id LIMIT $max";
    $result = dbquery ($query);
    return $result;
}

function IsPlayerInUnion ($player_id, $union)
{
    if ( $union == null ) return false;
    foreach ( $union['player'] as $i=>$pid )
    {
        if ( $pid == $player_id ) return true;
    }
    return false;
}

// Логи полётов.

function FleetlogsMissionText ($num)
{
    if ($num >= FTYP_ORBITING)
    {
        $desc = "<a title=\"На планете\">(Д)</a>";
        $num -= FTYP_ORBITING;
    }
    else if ($num >= FTYP_RETURN)
    {
        $desc = "<a title=\"Возвращение к планете\">(В)</a>";
        $num -= FTYP_RETURN;
    }
    else $desc = "<a title=\"Уход на задание\">(У)</a>";

    echo "      <a title=\"\">".loca("FLEET_ORDER_$num")."</a>\n$desc\n";
}

function FleetlogsFromPlayer ($player_id, $missions)
{
    global $db_prefix;

    if ( count ($missions) == 0 ) return null;

    $list = "";
    foreach ($missions as $i=>$num) {
        if ($i > 0) $list .= "OR ";
        $list .= "mission = $num ";
    }

    $query = "SELECT * FROM ".$db_prefix."fleetlogs WHERE (".$list.") AND owner_id = $player_id ORDER BY start ASC;";
    return dbquery ( $query );
}

function FleetlogsToPlayer ($player_id, $missions)
{
    global $db_prefix;

    if ( count ($missions) == 0 ) return null;

    $list = "";
    foreach ($missions as $i=>$num) {
        if ($i > 0) $list .= "OR ";
        $list .= "mission = $num ";
    }

    $query = "SELECT * FROM ".$db_prefix."fleetlogs WHERE (".$list.") AND owner_id <> target_id AND target_id = $player_id ORDER BY start ASC;";
    return dbquery ( $query );
}

function DumpFleet ($fleet)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $result = "";
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet[$gid];
        if ( $amount != 0 ) $result .= loca ("NAME_$gid") . " " . nicenum($amount) . " ";
    }
    return $result;
}

?>