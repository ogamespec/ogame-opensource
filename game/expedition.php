<?php

// Экспедиции.
// Сообщения экспедиции рассылаются на языке пользователя (используется метод loca_lang).

// Посчитать количество активных экспедиций у выбранного игрока.
function GetExpeditionsCount ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE (mission = 15 OR mission = 115 OR mission = 215) AND owner_id = $player_id;";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Загрузить настройки экспедиции.
function LoadExpeditionSettings ()
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."exptab;";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Посчитать очки экспедиционного флота.
function ExpPoints ( $fleet )
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $structure = 0;

    foreach ( $fleetmap as $i=>$gid )
    {
        $amount = $fleet[$gid];
        $res = ShipyardPrice ( $gid );
        $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
        $structure += ($m + $k) * $amount;
    }

    return $structure / 1000;
}

// Верхний предел экспедиционных очков.
function ExpUpperLimit ()
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users ORDER BY score1 DESC LIMIT 1";
    $result = dbquery ($query);
    if ( $result ) {
        $user = dbarray ($result);
        if ( $user['score1'] >= 5000000000 ) return 12000;
    }
    return 9000;
}

// Ничего не произошло.
function Exp_NothingHappens ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    $msg = array (
        loca_lang ("EXP_NOTHING_1", $lang),
        loca_lang ("EXP_NOTHING_2", $lang),
        loca_lang ("EXP_NOTHING_3", $lang),
        loca_lang ("EXP_NOTHING_4", $lang),
        loca_lang ("EXP_NOTHING_5", $lang),
        loca_lang ("EXP_NOTHING_6", $lang),
        loca_lang ("EXP_NOTHING_7", $lang),
        loca_lang ("EXP_NOTHING_8", $lang),
        loca_lang ("EXP_NOTHING_9", $lang),
        loca_lang ("EXP_NOTHING_10", $lang),
        loca_lang ("EXP_NOTHING_11", $lang),
        loca_lang ("EXP_NOTHING_12", $lang),
    );

    // Вернуть флот.
    // В качестве времени полёта используется время удержания.
    DispatchFleet ($fleet, $origin, $target, 115, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end']);

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// Сообщение бортового инженера (счетчик посещений)
function Logbook ($expcount, $exptab, $lang)
{
    $msg_1 = array (
        loca_lang ("EXP_NOT_DEPLETED_1", $lang),
        loca_lang ("EXP_NOT_DEPLETED_2", $lang),
    );
    $msg_2 = array (
        loca_lang ("EXP_DEPLETED_MIN_1", $lang),
        loca_lang ("EXP_DEPLETED_MIN_2", $lang),
        loca_lang ("EXP_DEPLETED_MIN_3", $lang),
    );
    $msg_3 = array (
        loca_lang ("EXP_DEPLETED_MED_1", $lang),
        loca_lang ("EXP_DEPLETED_MED_2", $lang),
        loca_lang ("EXP_DEPLETED_MED_3", $lang),
    );
    $msg_4 = array (
        loca_lang ("EXP_DEPLETED_MAX_1", $lang),
        loca_lang ("EXP_DEPLETED_MAX_2", $lang),
        loca_lang ("EXP_DEPLETED_MAX_3", $lang),
    );

    if ( $expcount <= $exptab['depleted_min'] ) {
        $n = mt_rand ( 0, count($msg_1) - 1 );
        return $msg_1[$n];
    }
    else if ( $expcount <= $exptab['depleted_med'] ) {
        $n = mt_rand ( 0, count($msg_2) - 1 );
        return $msg_2[$n];
    }
    else if ( $expcount <= $exptab['depleted_max'] ) {
        $n = mt_rand ( 0, count($msg_3) - 1 );
        return $msg_3[$n];
    }
    else {
        $n = mt_rand ( 0, count($msg_4) - 1 );
        return $msg_4[$n];
    }
}

// ------------- 
// Удачные события экспедиции

// Встреча с чужими
function Exp_BattleAliens ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    $weak = array (
        loca_lang ("EXP_ALIENS_WEAK_1", $lang),
        loca_lang ("EXP_ALIENS_WEAK_2", $lang),
        loca_lang ("EXP_ALIENS_WEAK_3", $lang),
        loca_lang ("EXP_ALIENS_WEAK_4", $lang),
    );
    $medium = array (
        loca_lang ("EXP_ALIENS_MED_1", $lang),
        loca_lang ("EXP_ALIENS_MED_2", $lang),
        loca_lang ("EXP_ALIENS_MED_3", $lang),
    );
    $strong = array (
        loca_lang ("EXP_ALIENS_STRONG_1", $lang),
        loca_lang ("EXP_ALIENS_STRONG_2", $lang),
    );

    // Определить уровень чужих
    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // сильные
        $level = 2;
        $n = mt_rand ( 0, count($strong) - 1 );
        $msg = $strong[$n];
    }
    else if ( $chance >= 90 ) {    // средние
        $level = 1;
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // слабые
        $level = 0;
        $n = mt_rand ( 0, count($weak) - 1 );
        $msg = $weak[$n];
    }

    ExpeditionBattle ( $fleet_obj['fleet_id'], 0, $level, $queue['end'] );

    return $msg;
}

// ---

// Встреча с пиратами
function Exp_BattlePirates ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    $weak = array (
        loca_lang ("EXP_PIRATES_WEAK_1", $lang),
        loca_lang ("EXP_PIRATES_WEAK_2", $lang),
        loca_lang ("EXP_PIRATES_WEAK_3", $lang),
        loca_lang ("EXP_PIRATES_WEAK_4", $lang),
        loca_lang ("EXP_PIRATES_WEAK_5", $lang),
    );
    $medium = array (
        loca_lang ("EXP_PIRATES_MED_1", $lang),
        loca_lang ("EXP_PIRATES_MED_2", $lang),
        loca_lang ("EXP_PIRATES_MED_3", $lang),
    );
    $strong = array (
        loca_lang ("EXP_PIRATES_STRONG_1", $lang),
        loca_lang ("EXP_PIRATES_STRONG_2", $lang),
    );

    // Определить уровень пиратов
    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // сильные
        $level = 2;
        $n = mt_rand ( 0, count($strong) - 1 );
        $msg = $strong[$n];
    }
    else if ( $chance >= 90 ) {    // средние
        $level = 1;
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // слабые
        $level = 0;
        $n = mt_rand ( 0, count($weak) - 1 );
        $msg = $weak[$n];
    }

    ExpeditionBattle ( $fleet_obj['fleet_id'], 1, $level, $queue['end'] );

    return $msg;
}

// ---

// Нахождение Тёмной материи
function Exp_DarkMatterFound ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    global $db_prefix;
    $player_id = $fleet_obj['owner_id'];

    $small = array (
        loca_lang ("EXP_DMFOUND_SMALL_1", $lang),
        loca_lang ("EXP_DMFOUND_SMALL_2", $lang),
        loca_lang ("EXP_DMFOUND_SMALL_3", $lang),
        loca_lang ("EXP_DMFOUND_SMALL_4", $lang),
        loca_lang ("EXP_DMFOUND_SMALL_5", $lang),
    );
    $medium = array (
        loca_lang ("EXP_DMFOUND_MED_1", $lang),
        loca_lang ("EXP_DMFOUND_MED_2", $lang),
        loca_lang ("EXP_DMFOUND_MED_3", $lang),
    );
    $large = array (
        loca_lang ("EXP_DMFOUND_LARGE_1", $lang),
        loca_lang ("EXP_DMFOUND_LARGE_2", $lang),
    );

    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // крупное
        $dm = mt_rand ( 501, 2076 );
        $n = mt_rand ( 0, count($large) - 1 );
        $msg = $large[$n];
    }
    else if ( $chance >= 90 ) {    // среднее
        $dm = mt_rand ( 201, 500 );
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // маленькое
        $dm = mt_rand ( 100, 200 );
        $n = mt_rand ( 0, count($small) - 1 );
        $msg = $small[$n];
    }

    $dm *= 3;

    $msg .= va ( loca_lang("EXP_FOUND", $lang), nicenum($dm), loca_lang("DM", $lang) );

    // Зачислить ТМ
    $query = "UPDATE ".$db_prefix."users SET dmfree = dmfree + '".$dm."' WHERE player_id=$player_id;";
    dbquery ($query);

    // Вернуть флот.
    // В качестве времени полёта используется время удержания.
    DispatchFleet ($fleet, $origin, $target, 115, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end']);

    return $msg;
}

// ---

// Потеря всего флота
function Exp_LostFleet ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    $msg = array (
        loca_lang ("EXP_LOST_1", $lang),
        loca_lang ("EXP_LOST_2", $lang),
        loca_lang ("EXP_LOST_3", $lang),
        loca_lang ("EXP_LOST_4", $lang),
    );

    // Списать очки.
    $points = $fpoints = 0;
    $price = FleetPrice ( $fleet_obj );
    AdjustStats ( $fleet_obj['owner_id'], $price['points'], $price['fpoints'], 0, '-' );
    RecalcRanks ();

    // Флот возвращать не нужно...

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// ---

// Задержка возврата экспедиции
function Exp_DelayFleet ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    $msg = array (
        loca_lang ("EXP_DELAY_1", $lang),
        loca_lang ("EXP_DELAY_2", $lang),
        loca_lang ("EXP_DELAY_3", $lang),
        loca_lang ("EXP_DELAY_4", $lang),
        loca_lang ("EXP_DELAY_5", $lang),
        loca_lang ("EXP_DELAY_6", $lang),
    );

    $hold_time = $fleet_obj['flight_time'];

    $chance = mt_rand (0, 99);
    if ( $chance >= 99 )  $delay = $hold_time * 5;
    else if ( $chance >= 90 )  $delay = $hold_time * 3;
    else  $delay = $hold_time * 2;

    // Вернуть флот.
    // В качестве времени полёта используется время удержания.
    DispatchFleet ($fleet, $origin, $target, 115, $fleet_obj['deploy_time'] + $delay, $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end']);

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// Ускорение возврата экспедиции
function Exp_AccelFleet ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    $msg = array (
        loca_lang ("EXP_ACCEL_1", $lang),
        loca_lang ("EXP_ACCEL_2", $lang),
        loca_lang ("EXP_ACCEL_3", $lang),
    );

    $chance = mt_rand (0, 99);
    if ( $chance >= 99 )  $ratio = 5;
    else if ( $chance >= 90 )  $ratio = 3;
    else  $ratio = 2;

    // Вернуть флот.
    // В качестве времени полёта используется время удержания.
    DispatchFleet ($fleet, $origin, $target, 115, $fleet_obj['deploy_time'] / $ratio, $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end']);

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// ---

// Нахождение ресурсов
function Exp_ResourcesFound ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    $small = array (
        loca_lang ("EXP_RESFOUND_SMALL_1", $lang),
        loca_lang ("EXP_RESFOUND_SMALL_2", $lang),
        loca_lang ("EXP_RESFOUND_SMALL_3", $lang),
        loca_lang ("EXP_RESFOUND_SMALL_4", $lang),
    );
    $medium = array (
        loca_lang ("EXP_RESFOUND_MED_1", $lang),
        loca_lang ("EXP_RESFOUND_MED_2", $lang),
        loca_lang ("EXP_RESFOUND_MED_3", $lang),
    );
    $large = array (
        loca_lang ("EXP_RESFOUND_LARGE_1", $lang),
        loca_lang ("EXP_RESFOUND_LARGE_2", $lang),
    );
    $footer = array (
        loca_lang ("EXP_RESFOUND_LOGBOOK_1", $lang),
        loca_lang ("EXP_RESFOUND_LOGBOOK_2", $lang),
        loca_lang ("EXP_RESFOUND_LOGBOOK_3", $lang),
        loca_lang ("EXP_RESFOUND_LOGBOOK_4", $lang),
    );
    $resname = array ( loca_lang ("METAL", $lang), loca_lang ("CRYSTAL", $lang), loca_lang ("DEUTERIUM", $lang) );

    // Рассчитать тип найденного ресурса
    $type = mt_rand (0, 2);

    // Рассчитать тип месторождения
    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // крупное
        $roll = mt_rand (51, 100) * 2;
        $n = mt_rand ( 0, count($large) - 1 );
        $msg = $large[$n];
    }
    else if ( $chance >= 90 ) {    // среднее
        $roll = mt_rand (26, 50) * 2;
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // маленькое
        $roll = mt_rand (5, 25) * 2;
        $n = mt_rand ( 0, count($small) - 1 );
        $msg = $small[$n];
    }

    if ( $type == 1) $roll /= 2;
    else if ( $type == 2) $roll /= 3;

    // Рассчитать количество найденного ресурса
    $points = min ( max ( 200, ExpPoints ($fleet)), ExpUpperLimit() );
    $cargo = max (0, FleetCargoSummary ($fleet) - ($fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d']));
    $amount = $roll * $points;

    // Количество найденных ресурсов уменьшается до общей грузоподъемности флота
    if ( $cargo < $amount ) {
        $amount = $cargo;
        $no_cargo = true;
    }
    else $no_cargo = false;

    $msg .= va ( loca_lang("EXP_FOUND", $lang), nicenum($amount), $resname[$type]);
    if ( $no_cargo ) {
        $n = mt_rand ( 0, count($footer) - 1 );
        $msg .= "<br><br>" . $footer[$n];
    }

    $m = $k = $d = 0;
    if ( $type == 0) $m = $amount;
    else if ( $type == 1) $k = $amount;
    else if ( $type == 2) $d = $amount;

    // Вернуть флот.
    // В качестве времени полёта используется время удержания.
    DispatchFleet ($fleet, $origin, $target, 115, $fleet_obj['deploy_time'], $fleet_obj['m'] + $m, $fleet_obj['k'] + $k, $fleet_obj['d'] + $d, 0, $queue['end']);

    return $msg;
}

// ---

// Нахождение кораблей
function Exp_FleetFound ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    global $UnitParam;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

    $small = array (
        loca_lang ("EXP_FLEET_SMALL_1", $lang),
        loca_lang ("EXP_FLEET_SMALL_2", $lang),
        loca_lang ("EXP_FLEET_SMALL_3", $lang),
        loca_lang ("EXP_FLEET_SMALL_4", $lang),
    );
    $medium = array (
        loca_lang ("EXP_FLEET_MED_1", $lang),
        loca_lang ("EXP_FLEET_MED_2", $lang),
    );
    $large = array (
        loca_lang ("EXP_FLEET_LARGE_1", $lang),
        loca_lang ("EXP_FLEET_LARGE_2", $lang),
    );
    $footer = array (
        loca_lang ("EXP_FLEET_LOGBOOK_1", $lang),
        loca_lang ("EXP_FLEET_LOGBOOK_2", $lang),
        loca_lang ("EXP_FLEET_LOGBOOK_3", $lang),
    );

    $points = $fpoints = 0;
    $found = array ();

    // Рассчитать количество найденного флота
    $chance = mt_rand (0, 99);
    if ( $chance >= 99 ) {        // крупный
        $roll = mt_rand (101, 200);
        $n = mt_rand ( 0, count($large) - 1 );
        $msg = $large[$n];
    }
    else if ( $chance >= 90 ) {    // средний
        $roll = mt_rand (51, 100);
        $n = mt_rand ( 0, count($medium) - 1 );
        $msg = $medium[$n];
    }
    else {    // маленький
        $roll = mt_rand (2, 50);
        $n = mt_rand ( 0, count($small) - 1 );
        $msg = $small[$n];
    }

    // Рассчитать структуру найденного флота.
    $epoints = min ( ExpPoints ($fleet), ExpUpperLimit() );
    $structure = max ( 7000, floor ($roll * $epoints / 2) );
    $no_structure = false;

    // Возможные типы найденных кораблей
    if ( $fleet[210] > 0 ) $found = array ( 210, 202 );    // шпик
    if ( $fleet[202] > 0 ) $found = array ( 210, 202, 203 );    // мт
    if ( $fleet[204] > 0 ) $found = array ( 210, 202, 204, 203 );    // ли
    if ( $fleet[203] > 0 ) $found = array ( 210, 202, 204, 203, 205 );    // бт
    if ( $fleet[205] > 0 ) $found = array ( 210, 202, 204, 203, 205, 206 );    // ти
    if ( $fleet[206] > 0 ) $found = array ( 210, 202, 204, 203, 205, 206, 207 );    // крейсер
    if ( $fleet[207] > 0 ) $found = array ( 210, 202, 204, 203, 205, 206, 207, 215 );     // линкор
    if ( $fleet[215] > 0 ) $found = array ( 210, 202, 204, 203, 205, 206, 207, 215, 211 );    // линейка
    if ( $fleet[211] > 0 ) $found = array ( 210, 202, 204, 203, 205, 206, 207, 215, 211, 213 );    // бомбер
    if ( $fleet[213] > 0 ) $found = array ( 210, 202, 204, 203, 205, 206, 207, 215, 211, 213 );    // уник

    // Составить список найденных типов кораблей, каждый тип корабля может быть найден с равной вероятностью.
    $found_ids = array ();
    if ( count ($found) > 0)
    {
        shuffle ($found);
        $chance = floor(1 / count ($found) * 100);
        foreach ($found as $i=>$id)
        {
            $roll = mt_rand ( 0, 99 );
            if ($roll < $chance) $found_ids[] = $id;
        }
    }

    // Соcтавить список найденного флота.
    $found_fleet = array ( );
    foreach ( $found_ids as $i=>$id )
    {
        $max = floor ( $structure / $UnitParam[$id][0] );
        if ( $max > 0 ) $amount = mt_rand (1, $max);
        else $amount = 0;
        if ( $amount == 0 ) { $no_structure = true; break; }    // не хватило структуры для остального флота
        $found_fleet[$id] = $amount;
        $structure -= $amount * $UnitParam[$id][0];
    }

    // Вывести список найденного флота и посчитать его стоимость.
    if ( count($found_fleet) > 0 )
    {
        $msg .= loca_lang ("EXP_FLEET_FOUND", $lang);
        foreach ( $found_fleet as $id=>$amount)
        {
            $res = ShipyardPrice ( $id );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            $points += ($m + $k + $d) * $amount;
            $fpoints += $amount;
            $msg .= "<br>" . loca_lang ("NAME_$id", $lang) . " " . nicenum ($amount);
            $fleet[$id] += $amount;    // Добавить корабли к экспедиционному флоту
        }
    }

    // Зачислить очки, если найден хотя бы один корабль
    if ( $fpoints > 0 ) {
        AdjustStats ( $fleet_obj['owner_id'], $points, $fpoints, 0, '+' );
        RecalcRanks ();
    }

    if ( $no_structure ) {
        $n = mt_rand ( 0, count($footer) - 1 );
        $msg .= "<br><br>" . $footer[$n];
    }

    // Вернуть флот.
    // В качестве времени полёта используется время удержания.
    DispatchFleet ($fleet, $origin, $target, 115, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end']);

    return $msg;
}

// ---

// Нахождение Скупщика
function Exp_TraderFound ($queue, $fleet_obj, $fleet, $origin, $target, $lang)
{
    global $db_prefix;
    $player_id = $fleet_obj['owner_id'];

    $msg = array (
        loca_lang ( "EXP_TRADER_1", $lang ),
        loca_lang ( "EXP_TRADER_2", $lang ),
    );

    $user = LoadUser ( $player_id );
    if ( $user['trader'] == 0 ) $offer_id = mt_rand ( 1, 3 );
    else $offer_id = $user['trader'];
    $rate_sum = $user['rate_m'] + $user['rate_k'] + $user['rate_d'];

    // Сгенерировать курсы.
    $rand = mt_rand (0, 99);
    if ( $rand < 10 ) {
        $rate_m = 3;
        $rate_k = 2;
        $rate_d = 1;
    }
    else if ( $rand < 20 ) {

        if ( $offer_id == 1) {
            $rate_m = 3;
            $rate_k = 1.60;
            $rate_d = 0.80;
        }

        else if ( $offer_id == 2) {
            $rate_m = 2.40;
            $rate_k = 2;
            $rate_d = 0.80;
        }

        else if ( $offer_id == 3) {
            $rate_m = 2.40;
            $rate_k = 1.60;
            $rate_d = 1;
        }

    }
    else {
        if ( $offer_id == 1) {
            $rate_m = 3;
            $rate_k = mt_rand ( 140, 200) / 100;
            $rate_d = mt_rand ( 70, 100) / 100;
        }

        else if ( $offer_id == 2) {
            $rate_m = mt_rand ( 210, 300) / 100;
            $rate_k = 2;
            $rate_d = mt_rand ( 70, 100) / 100;
        }

        else if ( $offer_id == 3) {
            $rate_m = mt_rand ( 210, 300) / 100;
            $rate_k = mt_rand ( 140, 200) / 100;
            $rate_d = 1;
        }
    }

    // Зачислить Скупщика.
    if ( $user['trader'] == 0 || ($rate_m + $rate_k + $rate_d) > $rate_sum ) {
        $query = "UPDATE ".$db_prefix."users SET trader = $offer_id, rate_m = '$rate_m', rate_k = '$rate_k', rate_d = '$rate_d' WHERE player_id=$player_id;";
        dbquery ($query);
    }

    // Вернуть флот.
    // В качестве времени полёта используется время удержания.
    DispatchFleet ($fleet, $origin, $target, 115, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end']);

    $n = mt_rand ( 0, count($msg) - 1 );
    return $msg[$n];
}

// -------------

function ExpeditionArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{
    // Запустить задание удержания на орбите.
    // Время удержания сделать временем полёта (чтобы потом его можно было использовать при возврате флота)
    DispatchFleet ($fleet, $origin, $target, 215, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end'], 0, $fleet_obj['flight_time']);
}

function ExpeditionHold ($queue, $fleet_obj, $fleet, $origin, $target)
{
    $exptab = LoadExpeditionSettings ();

    $hold_time = $fleet_obj['flight_time'] / 3600;

    $origin_user = LoadUser ( $origin['owner_id'] );
    loca_add ( "common", $origin_user['lang'] );
    loca_add ( "technames", $origin_user['lang'] );
    loca_add ( "expedition", $origin_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );

    // Событие экспедиции.
    $chance = mt_rand ( 0, 99 );
    $expcount = $target['m'];    // счётчик посещений
    if ( $chance < ($exptab['chance_success'] + $hold_time) )
    {
        if ( $expcount <= $exptab['depleted_min'] ) $chance_depleted = 0;
        else if ( $expcount <= $exptab['depleted_med'] ) $chance_depleted = $exptab['chance_depleted_min'];
        else if ( $expcount <= $exptab['depleted_max'] ) $chance_depleted = $exptab['chance_depleted_med'];
        else $chance_depleted = $exptab['chance_depleted_max'];
        
        $chance = mt_rand ( 0, 99 );
        if ($chance >= $chance_depleted)    // Удачная экспедиция.
        {
            if ( $chance >= $exptab['chance_alien'] ) $text = Exp_BattleAliens ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            else if ( $chance >= $exptab['chance_pirates'] ) $text = Exp_BattlePirates ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            else if ( $chance >= $exptab['chance_dm'] ) $text = Exp_DarkMatterFound ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            else if ( $chance >= $exptab['chance_lost'] ) $text = Exp_LostFleet ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            else if ( $chance >= $exptab['chance_lost'] ) $text = Exp_NothingHappens ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            else if ( $chance >= $exptab['chance_delay'] ) $text = Exp_DelayFleet ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            else if ( $chance >= $exptab['chance_accel'] ) $text = Exp_AccelFleet ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            else if ( $chance >= $exptab['chance_res'] ) $text = Exp_ResourcesFound ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            else if ( $chance >= $exptab['chance_fleet'] ) $text = Exp_FleetFound ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
            else $text = Exp_TraderFound ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
        }
        else $text = Exp_NothingHappens ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);
    }
    else $text = Exp_NothingHappens ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);

    // DEBUG
    //$text = Exp_FleetFound ($queue, $fleet_obj, $fleet, $origin, $target, $origin_user['lang']);

    // Обновляем счётчик посещений экспедиции на планете.
    AdjustResources ( 1, 0, 0, $target['planet_id'], '+' );

    // Бортовой журнал, дополнение связиста
    if ( $fleet[210] > 0 ) $text .= "\n<br/>\n<br/>\n" . Logbook ( $expcount, $exptab, $origin_user['lang']);

    SendMessage ( $fleet_obj['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']),
        va(loca_lang("EXP_MESSAGE_SUBJ", $origin_user['lang']), $target['g'], $target['s'], $target['p']),
        $text, 3, $queue['end']);
}

?>