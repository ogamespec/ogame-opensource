<?php

// Боевой движок OGame.

// Восстановление обороны.
function RepairDefense ( $d, $res, $defrepair, $defrepair_delta, $premium=true )
{
    $repaired = array ( 401=>0, 402=>0, 403=>0, 404=>0, 405=>0, 406=>0, 407=>0, 408=>0 );
    $exploded = array ( 401=>0, 402=>0, 403=>0, 404=>0, 405=>0, 406=>0, 407=>0, 408=>0 );
    $exploded_total = 0;

    if ( $premium) $prem = PremiumStatus ($d[0]);

    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        // Посчитать взорванную оборону.
        $last = $res['rounds'][$rounds - 1];
        foreach ( $exploded as $gid=>$amount )
        {
            $exploded[$gid] = $d[0]['defense'][$gid] - $last['defenders'][0][$gid];
            if ( $prem['engineer'] ) $exploded[$gid] = floor ($exploded[$gid] / 2);
            $exploded_total += $exploded[$gid];
        }

        // Восстановить оборону
        if ($exploded_total)
        {
            foreach ( $exploded as $gid=>$amount )
            {
                if ( $amount < 10 )
                {
                    for ($i=0; $i<$amount; $i++)
                    {
                        if ( mt_rand (0, 99) < $defrepair ) $repaired[$gid]++;
                    }
                }
                else $repaired[$gid] = floor ( mt_rand ($defrepair-$defrepair_delta, $defrepair+$defrepair_delta) * $amount / 100 );
            }
        }
    }

    return $repaired;
}

// Захватить ресурсы.
function Plunder ( $cargo, $m, $k, $d )
{
    $m /=2; $k /=2; $d /= 2;
    $total = $m+$k+$d;
    
    $mc = $cargo / 3;
    if ($m < $mc) $mc = $m;
    $cargo = $cargo - $mc;
    $kc = $cargo / 2;
    if ($k < $kc) $kc = $k;
    $cargo = $cargo - $kc;
    $dc = $cargo;
    if ($d < $dc)
    {
        $dc = $d;
        $cargo = $cargo - $dc;
        $m = $m - $mc;
        $half = $cargo / 2;
        $bonus = $half;
        if ($m < $half) $bonus = $m;
        $mc += $bonus;
        $cargo = $cargo - $bonus;
        $k = $k - $kc;
        if ($k < $cargo) $kc += $k;
        else $kc += $cargo;
    }

    $res = array ( 'cm' => floor($mc), 'ck' => floor($kc), 'cd' => floor($dc) );
    return $res;
}

// Рассчитать общие потери (учитывая восстановленную оборону).
function CalcLosses ( $a, $d, $res, $repaired )
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );
    $amap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $dmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 401, 402, 403, 404, 405, 406, 407, 408 );

    $aprice = $dprice = 0;

    // Стоимость юнитов до боя.
    foreach ($a as $i=>$attacker)                // Атакующие
    {
        foreach ( $fleetmap as $n=>$gid )
        {
            $amount = $attacker['fleet'][$gid];
            if ( $amount > 0 ) {
                $cost = ShipyardPrice ( $gid  );
                $aprice += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $a[$i]['points'] += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $a[$i]['fpoints'] += $amount;
            }
        }
    }

    foreach ($d as $i=>$defender)            // Обороняющиеся
    {
        foreach ( $fleetmap as $n=>$gid )
        {
            $amount = $defender['fleet'][$gid];
            if ( $amount > 0 ) {
                $cost = ShipyardPrice ( $gid );
                $dprice += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $d[$i]['points'] += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $d[$i]['fpoints'] += $amount;
            }
        }
        foreach ( $defmap as $n=>$gid )
        {
            $amount = $defender['defense'][$gid];
            if ( $amount > 0 ) {
                $cost = ShipyardPrice ( $gid );
                $dprice += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                $d[$i]['points'] += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
            }
        }
    }

    // Стоимость юнитов в последнем раунде.
    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];
        $alast = $dlast = 0;

        foreach ( $last['attackers'] as $i=>$attacker )        // Атакующие
        {
            foreach ( $amap as $n=>$gid )
            {
                $amount = $attacker[$gid];
                if ( $amount > 0 ) {
                    $cost = ShipyardPrice ( $gid );
                    $alast += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                    $a[$i]['points'] -= ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                    $a[$i]['fpoints'] -= $amount;
                }
            }
        }

        foreach ( $last['defenders'] as $i=>$defender )        // Обороняющиеся
        {
            foreach ( $dmap as $n=>$gid )
            {
                if ( $gid > 400 && $i == 0 ) $amount = $defender[$gid] + $repaired[$gid];
                else $amount = $defender[$gid];
                if ( $amount > 0 ) {
                    $cost = ShipyardPrice ( $gid );
                    $dlast += ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                    $d[$i]['points'] -= ( $cost['m'] + $cost['k'] + $cost['d'] ) * $amount;
                    if ( $gid < 400 ) $d[$i]['fpoints'] -= $amount;
                }
            }
        }

        $aloss = $aprice - $alast;
        $dloss = $dprice - $dlast;
    }
    else { 
        foreach ($a as $i=>$attacker) {
            $a[$i]['points'] = $a[$i]['fpoints'] = 0;
        }
        foreach ($d as $i=>$defender) {
            $d[$i]['points'] = $d[$i]['fpoints'] = 0;
        }
        $aloss = $dloss = 0;
    }

    return array ( 'a' => $a, 'd' => $d, 'aloss' => $aloss, 'dloss' => $dloss );
}

// Суммарная грузоподъемность флотов в последнем раунде.
function CargoSummaryLastRound ( $a, $res )
{
    $cargo = 0;
    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];

        foreach ( $last['attackers'] as $i=>$attacker )        // Атакующие
        {
            $f = LoadFleet ( $attacker['id'] );
            $cargo += FleetCargoSummary ( $attacker ) - ($f['m'] + $f['k'] + $f['d']) - $f['fuel'];
        }
    }
    else
    {
        foreach ($a as $i=>$attacker)                // Атакующие
        {
            $f = LoadFleet ( $attacker['id'] );
            $cargo += FleetCargoSummary ( $attacker['fleet'] ) - ($f['m'] + $f['k'] + $f['d']) - $f['fuel'];
        }
    }
    return max ( 0, $cargo );
}

// Модифицировать флоты и планету (добавить/отнять ресурсы, развернуть атакующие флоты, если остались корабли)
function WritebackBattleResults ( $a, $d, $res, $repaired, $cm, $ck, $cd, $sum_cargo )
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );
    global $db_prefix;

    // Бой с раундами.

    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];        

        foreach ( $last['attackers'] as $i=>$attacker )        // Атакующие
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $queue = GetFleetQueue ($fleet_obj['fleet_id']);
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker[$gid];
            if ( $sum_cargo == 0) $cargo = 0;
            else $cargo = ( FleetCargoSummary ( $attacker ) - ($fleet_obj['m']+$fleet_obj['k']+$fleet_obj['d']) - $fleet_obj['fuel'] ) / $sum_cargo;
            if ($ships > 0) {
                if ( $fleet_obj['mission'] == 9 && $res['result'] === "awon" ) $result = GravitonAttack ( $fleet_obj, $attacker, $queue['end'] );
                else $result = 0;
                if ( $result < 2 ) DispatchFleet ($attacker, $origin, $target, $fleet_obj['mission']+100, $fleet_obj['flight_time'], $fleet_obj['m']+$cm * $cargo, $fleet_obj['k']+$ck * $cargo, $fleet_obj['d']+$cd * $cargo, $fleet_obj['fuel'] / 2, $queue['end']);
            }
        }

        foreach ( $last['defenders'] as $i=>$defender )        // Обороняющиеся
        {
            if ( $i == 0 )    // Планета
            {
                AdjustResources ( $cm, $ck, $cd, $defender['id'], '-' );
                $objects = array ();
                foreach ( $fleetmap as $ii=>$gid ) $objects["f$gid"] = $defender[$gid] ? $defender[$gid] : 0;
                foreach ( $defmap as $ii=>$gid ) {
                    $objects["d$gid"] = $repaired[$gid] ? $repaired[$gid] : 0;
                    $objects["d$gid"] += $defender[$gid];
                }
                SetPlanetFleetDefense ( $defender['id'], $objects );
            }
            else        // Флоты на удержании
            {
                $ships = 0;
                foreach ( $fleetmap as $ii=>$gid ) $ships += $defender[$gid];
                if ( $ships > 0 ) SetFleet ( $defender['id'], $defender );
                else {
                    $queue = GetFleetQueue ($defender['id']);
                    DeleteFleet ($defender['id']);    // удалить флот
                    RemoveQueue ( $queue['task_id'] );    // удалить задание
                }
            }
        }
    }

    // Бой без раундов.

    else 
    {
        foreach ( $a as $i=>$attacker )            // Атакующие
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $queue = GetFleetQueue ($fleet_obj['fleet_id']);
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker['fleet'][$gid];
            if ( $sum_cargo == 0) $cargo = 0;
            else $cargo = ( FleetCargoSummary ( $attacker['fleet'] ) - ($fleet_obj['m']+$fleet_obj['k']+$fleet_obj['d']) - $fleet_obj['fuel'] ) / $sum_cargo;
            if ($ships > 0) {
                if ( $fleet_obj['mission'] == 9 && $res['result'] === "awon" ) $result = GravitonAttack ( $fleet_obj, $attacker['fleet'], $queue['end'] );
                else $result = 0;
                if ( $result < 2 ) DispatchFleet ($attacker['fleet'], $origin, $target, $fleet_obj['mission']+100, $fleet_obj['flight_time'], $fleet_obj['m']+$cm * $cargo, $fleet_obj['k']+$ck * $cargo, $fleet_obj['d']+$cd * $cargo, $fleet_obj['fuel'] / 2, $queue['end']);
            }
        }

        foreach ( $d as $i=>$defender )        // Обороняющиеся
        {
            if ( $i == 0 )    // Планета
            {
                AdjustResources ( $cm, $ck, $cd, $defender['id'], '-' );
                $objects = array ();
                foreach ( $fleetmap as $ii=>$gid ) $objects["f$gid"] = $defender[$gid] ? $defender[$gid] : 0;
                foreach ( $defmap as $ii=>$gid ) {
                    $objects["d$gid"] = $repaired[$gid] ? $repaired[$gid] : 0;
                    $objects["d$gid"] += $defender[$gid];
                }
                SetPlanetFleetDefense ( $defender['id'], $objects );
            }
            else        // Флоты на удержании
            {
                $ships = 0;
                foreach ( $fleetmap as $ii=>$gid ) $ships += $defender[$gid];
                if ( $ships > 0 ) SetFleet ( $defender['id'], $defender );
                else {
                    $queue = GetFleetQueue ($defender['id']);
                    DeleteFleet ($defender['id']);    // удалить флот
                    RemoveQueue ( $queue['task_id'] );    // удалить задание
                }
            }
        }

    }
}

// Сгенерировать HTML-код одного слота.
function GenSlot ( $weap, $shld, $armor, $name, $g, $s, $p, $unitmap, $fleet, $defense, $show_techs, $attack )
{
    global $UnitParam;

    $text = "<th><br>";

    $text .= "<center>";
    if ($attack) $text .= "Флот атакующего";
    else $text .= "Обороняющийся";
    $text .= " ".$name." (<a href=# onclick=showGalaxy($g,$s,$p); >[$g:$s:$p]</a>)";
    if ($show_techs) $text .= "<br>Вооружение: ".($weap * 10)."% Щиты: ".($shld * 10)."% Броня: ".($armor * 10)."% ";

    $sum = 0;
    foreach ( $unitmap as $i=>$gid )
    {
            if ( $gid > 400 ) $sum += $defense[$gid];
            else $sum += $fleet[$gid];
    }

    if ( $sum > 0 )
    {
        $text .= "<table border=1>";

        $text .= "<tr><th>Тип</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( $gid > 400 ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".loca("SNAME_$gid")."</th>";
        }
        $text .= "</tr>";

        $text .= "<tr><th>Кол-во.</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( $gid > 400 ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".nicenum($n)."</th>";
        }
        $text .= "</tr>";

        $text .= "<tr><th>Воор.:</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( $gid > 400 ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".nicenum( $UnitParam[$gid][2] * (10 + $weap ) / 10 )."</th>";
        }
        $text .= "</tr>";

        $text .= "<tr><th>Щиты</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( $gid > 400 ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".nicenum( $UnitParam[$gid][1] * (10 + $shld ) / 10 )."</th>";
        }
        $text .= "</tr>";

        $text .= "<tr><th>Броня</th>";
        foreach ( $unitmap as $i=>$gid )
        {
            if ( $gid > 400 ) $n = $defense[$gid];
            else $n = $fleet[$gid];
            if ( $n > 0 ) $text .= "<th>".nicenum( $UnitParam[$gid][0] * (10 + $armor ) / 100 )."</th>";
        }
        $text .= "</tr>";

        $text .= "</table>";
    }
    else $text .= "<br>уничтожен";

    $text .= "</center></th>";
    return $text;
}

// Сгенерировать боевой доклад.
function BattleReport ( $res, $now, $aloss, $dloss, $cm, $ck, $cd, $moonchance, $mooncreated, $repaired )
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );
    $amap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $dmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 401, 402, 403, 404, 405, 406, 407, 408 );

    $text = "";

    // Заголовок доклада.
    $text .= "Дата/Время: ".date ("m-d H:i:s", $now)." . Произошёл бой между следующими флотами:<br>";

    // Флоты перед боем.
    $text .= "<table border=1 width=100%><tr>";
    foreach ( $res['before']['attackers'] as $i=>$attacker)
    {
        $text .= GenSlot ( $attacker['weap'], $attacker['shld'], $attacker['armr'], $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker, null, 1, 1 );
    }
    $text .= "</tr></table>";
    $text .= "<table border=1 width=100%><tr>";
    foreach ( $res['before']['defenders'] as $i=>$defender)
    {
        $text .= GenSlot ( $defender['weap'], $defender['shld'], $defender['armr'], $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender, $defender, 1, 0 );
    }
    $text .= "</tr></table>";

    // Раунды.
    foreach ( $res['rounds'] as $i=>$round)
    {
        $text .= "<br><center>Атакующий флот делает: ".nicenum($round['ashoot'])." выстрела(ов) общей мощностью ".nicenum($round['apower'])." по обороняющемуся. Щиты обороняющегося поглощают ".nicenum($round['dabsorb'])." мощности выстрелов";
        $text .= "<br>Обороняющийся флот делает ".nicenum($round['dshoot'])." выстрела(ов) общей мощностью ".nicenum($round['dpower'])." выстрела(ов) по атакующему. Щиты атакующего поглощают ".nicenum($round['aabsorb'])." мощности выстрелов</center>";

        $text .= "<table border=1 width=100%><tr>";        // Атакующие
        foreach ( $round['attackers'] as $n=>$attacker )
        {
            $text .= GenSlot ( 0, 0, 0, $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker, null, 0, 1 );
        }
        $text .= "</tr></table>";

        $text .= "<table border=1 width=100%><tr>";        // Обороняющиеся
        foreach ( $round['defenders'] as $n=>$defender )
        {
            if ( $n == 0 ) $text .= GenSlot ( 0, 0, 0, $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender, $defender, 0, 0 );
            else $text .= GenSlot ( 0, 0, 0, $defender['name'], $defender['g'], $defender['s'], $defender['p'], $amap, $defender, null, 0, 0 );
        }
        $text .= "</tr></table>";
    }

    // Результаты боя.
//<!--A:167658,W:167658-->
    if ( $res['result'] === "awon" )
    {
        $text .= "<p> Атакующий выиграл битву!<br>Он получает<br>".nicenum($cm)." металла, ".nicenum($ck)." кристалла и ".nicenum($cd)." дейтерия.";
    }
    else if ( $res['result'] === "dwon" ) $text .= "<p> Обороняющийся выиграл битву!";
    else if ( $res['result'] === "draw" ) $text .= "<p> Бой оканчивается вничью, оба флота возвращаются на свои планеты";
    //else Error ("Неизвестный исход битвы!");
    $text .= "<br><p><br>Атакующий потерял ".nicenum($aloss)." единиц.<br>Обороняющийся потерял ".nicenum($dloss)." единиц.";
    $text .= "<br>Теперь на этих пространственных координатах находится ".nicenum($res['dm'])." металла и ".nicenum($res['dk'])." кристалла.";
    if ( $moonchance ) $text .= "<br>Шанс появления луны составил $moonchance %";
    if ( $mooncreated ) $text .= "<br>Невероятные массы свободного металла и кристалла сближаются и образуют форму некого спутника на орбите планеты. ";

    // Восстановление обороны.
    // При выводе оригинального боевого доклада есть ошибка: Малый щитовой купол выводится не в свою очередь, а перед Плазменным орудием.
    // Чтобы быть максимально похожим на оригинальный доклад, при выводе восстановленной обороны используется таблица перестановки RepairMap.
    $repairmap = array ( 401, 402, 403, 404, 405, 407, 406, 408 );
    $repaired_num = $sum = 0;
    foreach ($repaired as $gid=>$amount) $repaired_num += $amount;
    if ( $repaired_num > 0)
    {
        $text .= "<br>";
        foreach ($repairmap as $i=>$gid)
        {
            if ($repaired[$gid])
            {
                if ( $sum > 0 ) $text .= ", ";
                $text .= nicenum ($repaired[$gid]) . " " . loca ("NAME_$gid");
                $sum += $repaired[$gid];
            }
        }
        $text .= " были повреждены и находятся в ремонте.<br>";
    }

    return $text;
}

// Лунная атака.
function GravitonAttack ($fleet_obj, $fleet, $when)
{
    $origin = GetPlanet ( $fleet_obj['start_planet'] );
    $target = GetPlanet ( $fleet_obj['target_planet'] );

    if ( $fleet[214] == 0 ) return;
    if ( ! ($target['type'] == 0 || $target['type'] == 10003) ) Error ( "Уничтожать можно только луны!" );

    $diam = $target['diameter'];
    $rips = $fleet[214];
    $moonchance = (100 - sqrt($diam)) * sqrt($rips);
    if ($moonchance >= 100) $moonchance = 99.9;
    $ripchance = sqrt ($diam) / 2;
    $moondes =  mt_rand(1, 999) < $moonchance * 10;
    $ripdes = mt_rand(1, 999) < $ripchance * 10;

    if ( !$ripdes && !$moondes )
    {
            $atext = va ( "Флот с #1 #2 достигает луны планеты на #3 .\n" .
                                "Структура луны не была достаточно ослаблена, флот возвращается обратно.\n" .
                                "<br>Шанс на уничтожение луны: #4 %. Шанс на уничтожение звезды смерти:#5 %;", 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( "Флот с планеты #1 #2 достигает луны Вашей планеты на #3.\n" .
                                "Лёгкие сотрясения на твоей луне указывают на неудавшуюся атаку на лунную структуру; атакующий флот, не выполнив задания, возвращается обратно на #4 #5.\n" .
                                "<br>Шанс на уничтожение луны: #6 %. Шанс на уничтожение звезды смерти:#7 %;", 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", 
                                floor ($moonchance), floor ($ripchance)
                             );
            $result  = 0;
    }

    else if ( !$ripdes && $moondes )
    {
            $atext = va ( "Флот с планеты #1 #2 достигает луны планеты на #3 .\n".
                               "Вооружение звезды смерти отстреливают на луну череду зарядов гравитонов, которые приводят к мощному сотрясению и уничтожению спутника. Все постройки на луне уничтожаются. Полный успех. Флот возвращается на родную планету бухать по этому поводу.\n".
                               "<br>Шанс на уничтожение луны: #4 %. Шанс на уничтожение звезды смерти:#5 %",
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( "Флот с планеты #1 #2 достигает луны твоей планеты на #3.\n".
                                "Всё более усиливающаяся вибрация сотрясает этот спутник. Луна начинает деформироваться и в конце концов разлетается на миллионы кусочков. Это был тяжёлый удар для Вашей империи. Флот противника возвращается обратно.\n".
                                "<br>Шанс на уничтожение луны: #4 %. Шанс на уничтожение звезды смерти:#5 %",
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                             );

            DestroyMoon ( $target['planet_id'], $when, $fleet_obj['fleet_id'] );
            $result  = 1;
    }

    else if ( $ripdes && !$moondes )
    {
            $atext = va ( "Флот с планеты #1 #2 достигает луны планеты на #3 . Звезда смерти направляет свою гравитонную пушку на спутник. Лёгкие вибрации сотрясают поверхность луны. Но что-то тут не так. Гравитонная пушка приводит звезду смерти в колебания. Начинается отдача. Звезда смерти разлетается на миллионы кусочков. Возникающая при этом ударная волна уничтожает весь Ваш флот. Доигрался...\n".
                                "<br>Шанс на уничтожение луны: #4 %. Шанс на уничтожение звезды смерти:#5 %",
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( "Флот с планеты #1 #2 достигает луны Вашей планеты  на #3.\n".
                                "Лёгкие сотрясения на твоей луне указывают на неудавшуюся атаку на лунную структуру. Неожиданно они прекращаются. Гигантский взрыв сотрясает пространство. Атакующий флот исчезает с экранов радаров. Несрастуха вышла...\n".
                                "<br>Шанс на уничтожение луны: #4 %. Шанс на уничтожение звезды смерти:#5 %",
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                             );
            $result  = 2;
    }

    else if ( $ripdes && $moondes )
    {
            $atext = va ( "Флот с планеты #1 #2  достигает луны на орбите планеты #3 . Ваша звезда смерти направляет свою гравитонную пушку на спутник. Толчки на поверхности луны всё нарастают. Луна начинает деформироваться и разрывается. Гигантские обломки летят на Ваш флот. Отступать уже поздно. Весь Ваш флот уничтожается градом обломков. Какой облом...\n".
                                "<br>Шансы на уничтожение луны: #4 %. Шансы на уничтожение звезды смерти: #5%.",
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( "Флот с планеты #1 #2 достигает луны Вашей планеты на #3.\n".
                                "Всё более усиливающиеся толчки сотрясают спутник. Луна начинает деформироваться и разрывается в конце концов на миллионы кусочков. Внезапно вражеский флот исчезает с экранов Ваших радаров. Что-то там у них не так, наверное пришибло обломками...\n".
                                "<br>Шансы на уничтожение луны: #4 %. Шансы на уничтожение звезды смерти:#5 %.",
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                             );

            DestroyMoon ( $target['planet_id'], $when, $fleet_obj['fleet_id'] );
            $result  = 3;
    }

    // Разослать сообщения.
    SendMessage ( $origin['owner_id'], "Командование флотом", "Лунная атака", $atext, 5);
    SendMessage ( $target['owner_id'], "Командование флотом", "Лунные толчки", $dtext, 5);

    return $result;
}

// Начать битву между атакующим fleet_id и обороняющимся planet_id.
function StartBattle ( $fleet_id, $planet_id, $when )
{
    global $db_prefix;
    global $GlobalUni;

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );
    $d_result = array ( 1=>"combatreport_igotattacked_iwon", 0=>"combatreport_igotattacked_ilost", 2=>"combatreport_igotattacked_draw" );

    global  $db_host, $db_user, $db_pass, $db_name, $db_prefix;
    $a = array ();
    $d = array ();

    $unitab = LoadUniverse ();
    $fid = $unitab['fid'];
    $did = $unitab['did'];
    $rf = $unitab['rapid'];

    $f = LoadFleet ( $fleet_id );

    // *** Сгенерировать исходные данные

    // Список атакующих
    $anum = 0;
    if ( $f['union_id'] == 0 )    // Одиночная атака
    {
        $a[0] = LoadUser ( $f['owner_id'] );
        $a[0]['fleet'] = array ();
        foreach ($fleetmap as $i=>$gid) $a[0]['fleet'][$gid] = abs($f["ship$gid"]);
        $start_planet = GetPlanet ( $f['start_planet'] );
        $a[0]['g'] = $start_planet['g'];
        $a[0]['s'] = $start_planet['s'];
        $a[0]['p'] = $start_planet['p'];
        $a[0]['id'] = $fleet_id;
        $a[0]['points'] = $a[0]['fpoints'] = 0;
        $anum++;
    }
    else        // Совместная атака
    {
        $result = EnumUnionFleets ( $f['union_id'] );
        $rows = dbrows ($result);
        while ($rows--)
        {
            $fleet_obj = dbarray ($result);

            $a[$anum] = LoadUser ( $fleet_obj['owner_id'] );
            $a[$anum]['fleet'] = array ();
            foreach ($fleetmap as $i=>$gid) $a[$anum]['fleet'][$gid] = abs($fleet_obj["ship$gid"]);
            $start_planet = GetPlanet ( $fleet_obj['start_planet'] );
            $a[$anum]['g'] = $start_planet['g'];
            $a[$anum]['s'] = $start_planet['s'];
            $a[$anum]['p'] = $start_planet['p'];
            $a[$anum]['id'] = $fleet_obj['fleet_id'];
            $a[$anum]['points'] = $a[$anum]['fpoints'] = 0;

            $anum++;
        }
    }

    // Список обороняющихся
    $dnum = 0;
    $p = GetPlanet ( $planet_id );
    $d[0] = LoadUser ( $p['owner_id'] );
    $d[0]['fleet'] = array ();
    $d[0]['defense'] = array ();
    foreach ($fleetmap as $i=>$gid) $d[0]['fleet'][$gid] = abs($p["f$gid"]);
    foreach ($defmap as $i=>$gid) $d[0]['defense'][$gid] = abs($p["d$gid"]);
    $d[0]['g'] = $p['g'];
    $d[0]['s'] = $p['s'];
    $d[0]['p'] = $p['p'];
    $d[0]['id'] = $planet_id;
    $d[0]['points'] = $d[0]['fpoints'] = 0;
    $dnum++;

    // Флоты на удержании
    $result = GetHoldingFleets ($planet_id);
    $rows = dbrows ($result);
    while ($rows--)
    {
        $fleet_obj = dbarray ($result);

        $d[$dnum] = LoadUser ( $fleet_obj['owner_id'] );
        $d[$dnum]['fleet'] = array ();
        $d[$dnum]['defense'] = array ();
        foreach ($fleetmap as $i=>$gid) $d[$dnum]['fleet'][$gid] = abs($fleet_obj["ship$gid"]);
        foreach ($defmap as $i=>$gid) $d[$dnum]['defense'][$gid] = 0;
        $start_planet = GetPlanet ( $fleet_obj['start_planet'] );
        $d[$dnum]['g'] = $start_planet['g'];
        $d[$dnum]['s'] = $start_planet['s'];
        $d[$dnum]['p'] = $start_planet['p'];
        $d[$dnum]['id'] = $fleet_obj['fleet_id'];
        $d[$dnum]['points'] = $d[$dnum]['fpoints'] = 0;

        $dnum++;
    }

    $source .= "Rapidfire = $rf\n";
    $source .= "FID = $fid\n";
    $source .= "DID = $did\n";

    $source .= "Attackers = ".$anum."\n";
    $source .= "Defenders = ".$dnum."\n";

    foreach ($a as $num=>$attacker)
    {
        $source .= "Attacker".$num." = (<".$attacker['oname']."> ";
        $source .= $attacker['id'] . " ";
        $source .= $attacker['g'] . " " . $attacker['s'] . " " . $attacker['p'] . " ";
        $source .= $attacker['r109'] . " " . $attacker['r110'] . " " . $attacker['r111'] . " ";
        foreach ($fleetmap as $i=>$gid) $source .= $attacker['fleet'][$gid] . " ";
        $source .= ")\n";
    }
    foreach ($d as $num=>$defender)
    {
        $source .= "Defender".$num." = (<".$defender['oname']."> ";
        $source .= $defender['id'] . " ";
        $source .= $defender['g'] . " " . $defender['s'] . " " . $defender['p'] . " ";
        $source .= $defender['r109'] . " " . $defender['r110'] . " " . $defender['r111'] . " ";
        foreach ($fleetmap as $i=>$gid) $source .= $defender['fleet'][$gid] . " ";
        foreach ($defmap as $i=>$gid) $source .= $defender['defense'][$gid] . " ";
        $source .= ")\n";
    }

    $battle = array ( null, $source, "", "", $when );
    $battle_id = AddDBRow ( $battle, "battledata" );

    $bf = fopen ( "battledata/battle_".$battle_id.".txt", "w" );
    fwrite ( $bf, $source );
    fclose ( $bf );

    // *** Передать данные боевому движку

    $arg = "\"battle_id=$battle_id\"";
    system ( $unitab['battle_engine'] . " $arg" );

    // *** Обработать выходные данные

    $battleres = file_get_contents ( "battleresult/battle_".$battle_id.".txt" );
    $res = unserialize($battleres);

    // Определить исход битвы.
    if ( $res['result'] === "awon" ) $battle_result = 0;
    else if ( $res['result'] === "dwon" ) $battle_result = 1;
    else $battle_result = 2;

    // Восстановить оборону
    $repaired = RepairDefense ( $d, $res, $unitab['defrepair'], $unitab['defrepair_delta'] );

    // Рассчитать общие потери (учитывать дейтерий и восстановленную оборону)
    $aloss = $dloss = 0;
    $loss = CalcLosses ( $a, $d, $res, $repaired );
    $a = $loss['a'];
    $d = $loss['d'];
    $aloss = $loss['aloss'];
    $dloss = $loss['dloss'];

    // Захватить ресурсы
    $cm = $ck = $cd = 0;
    if ( $battle_result == 0 )
    {
        $sum_cargo = CargoSummaryLastRound ( $a, $res );
        $captured = Plunder ( $sum_cargo, $p['m'], $p['k'], $p['d'] );
        $cm = $captured['cm']; $ck = $captured['ck']; $cd = $captured['cd'];
    }

    // Создать поле обломков.
    $debris_id = CreateDebris ( $p['g'], $p['s'], $p['p'], $p['owner_id'] );
    AddDebris ( $debris_id, $res['dm'], $res['dk'] );

    // Создать луну
    $mooncreated = false;
    $moonchance = min ( floor ( ($res['dm'] + $res['dk']) / 100000), 20 );
    if ( PlanetHasMoon ( $planet_id ) || $p['type'] == 0 || $p['type'] == 10003 ) $moonchance = 0;
    if ( mt_rand (1, 100) <= $moonchance ) {
        CreatePlanet ( $p['g'], $p['s'], $p['p'], $p['owner_id'], 0, 1, $moonchance );
        $mooncreated = true;
    }

    // Обновить активность на планете.
    $queue = GetFleetQueue ( $fleet_id );
    UpdatePlanetActivity ( $planet_id, $queue['end'] );

    // Сгенерировать боевой доклад.
    loca_add ( "battlereport", $GlobalUni['lang'] );
    loca_add ( "technames", $GlobalUni['lang'] );
    $text = BattleReport ( $res, $when, $aloss, $dloss, $cm, $ck, $cd, $moonchance, $mooncreated, $repaired );

    // Разослать сообщения
    $mailbox = array ();

    foreach ( $d as $i=>$user )        // Обороняющиеся
    {
        if ( $mailbox[ $user['player_id'] ] == true ) continue;
        $bericht = SendMessage ( $user['player_id'], "Командование флотом", "Боевой доклад", $text, 6, $when );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$d_result[$battle_result]."\">Боевой доклад [".$p['g'].":".$p['s'].":".$p['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        SendMessage ( $user['player_id'], "Командование флотом", $subj, "", 2, $when );
        $mailbox[ $user['player_id'] ] = true;
    }

    // Обновить лог боевого доклада
    $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=admin&session={PUBLIC_SESSION}&mode=BattleReport&bericht=$battle_id\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">Боевой доклад [".$p['g'].":".$p['s'].":".$p['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
    $query = "UPDATE ".$db_prefix."battledata SET title = '".$subj."', report = '".$text."' WHERE battle_id = $battle_id;";
    dbquery ( $query );

    // Если флот уничтожен за 1 или 2 раунда - не показывать лог боя для атакующих.
    if ( count($res['rounds']) <= 2 && $battle_result == 1 ) $text = "Контакт с флотом потерян. <br> Это означает, что его уничтожили первым же залпом <!--A:$aloss,W:$dloss-->";

    foreach ( $a as $i=>$user )        // Атакующие
    {
        if ( $mailbox[ $user['player_id'] ] == true ) continue;
        $bericht = SendMessage ( $user['player_id'], "Командование флотом", "Боевой доклад", $text, 6, $when );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">Боевой доклад [".$p['g'].":".$p['s'].":".$p['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        SendMessage ( $user['player_id'], "Командование флотом", $subj, "", 2, $when );
        $mailbox[ $user['player_id'] ] = true;
    }

    // Почистить старые боевые доклады
    $ago = $when - 2 * 7 * 24 * 60 * 60;
    $query = "DELETE FROM ".$db_prefix."battledata WHERE date < $ago;";
    dbquery ($query);

    // Модифицировать флоты и планету в соответствии с потерями и захваченными ресурсами
    WritebackBattleResults ( $a, $d, $res, $repaired, $cm, $ck, $cd, $sum_cargo );

    // Изменить статистику игроков
    foreach ( $a as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    foreach ( $d as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    RecalcRanks ();

    // Чистим промежуточные данные боевого движка
    unlink ( "battledata/battle_".$battle_id.".txt" );
    unlink ( "battleresult/battle_".$battle_id.".txt" );

    return $battle_result;
}

// -----------------------------------------------------------------------------------------------------------------------------------------------------------

// Модифицировать флот (после битвы с чужими/пиратами)
function WritebackBattleResultsExpedition ( $a, $d, $res )
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

    // Бой с раундами.

    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        $last = $res['rounds'][$rounds - 1];        

        foreach ( $last['attackers'] as $i=>$attacker )        // Атакующие
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $queue = GetFleetQueue ($fleet_obj['fleet_id']);
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker[$gid];

            // Вернуть флот, если что-то осталось.
            // В качестве времени полёта используется время удержания.
            if ($ships > 0) DispatchFleet ($attacker, $origin, $target, 115, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
        }

    }

    // Бой без раундов.

    else 
    {
        foreach ( $a as $i=>$attacker )            // Атакующие
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $queue = GetFleetQueue ($fleet_obj['fleet_id']);
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $ii=>$gid ) $ships += $attacker['fleet'][$gid];

            // Вернуть флот, если что-то осталось.
            // В качестве времени полёта используется время удержания.
            if ($ships > 0)  DispatchFleet ($attacker['fleet'], $origin, $target, 115, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], $fleet_obj['fuel'] / 2, $queue['end']);
        }

    }
}

// Сгенерировать боевой доклад.
function ShortBattleReport ( $res, $now )
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );
    $amap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $dmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 401, 402, 403, 404, 405, 406, 407, 408 );

    $text = "";

    // Заголовок доклада.
    $text .= "Дата/Время: ".date ("m-d H:i:s", $now)." . Произошёл бой между следующими флотами:<br>";

    // Флоты перед боем.
    $text .= "<table border=1 width=100%><tr>";
    foreach ( $res['before']['attackers'] as $i=>$attacker)
    {
        $text .= GenSlot ( $attacker['weap'], $attacker['shld'], $attacker['armr'], $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker, null, 1, 1 );
    }
    $text .= "</tr></table>";
    $text .= "<table border=1 width=100%><tr>";
    foreach ( $res['before']['defenders'] as $i=>$defender)
    {
        $user = array ();
        $user['fleet'] = array ();
        $user['defense'] = array ();
        foreach ($fleetmap as $g=>$gid) $user['fleet'][$gid] = $defender[$gid];
        foreach ($defmap as $g=>$gid) $user['defense'][$gid] = 0;
        $text .= GenSlot ( $defender['weap'], $defender['shld'], $defender['armr'], $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender, $defender, 1, 0 );
    }
    $text .= "</tr></table>";

    // Раунды.
    foreach ( $res['rounds'] as $i=>$round)
    {
        $text .= "<br><center>Атакующий флот делает: ".nicenum($round['ashoot'])." выстрела(ов) общей мощностью ".nicenum($round['apower'])." по обороняющемуся. Щиты обороняющегося поглощают ".nicenum($round['dabsorb'])." мощности выстрелов";
        $text .= "<br>Обороняющийся флот делает ".nicenum($round['dshoot'])." выстрела(ов) общей мощностью ".nicenum($round['dpower'])." выстрела(ов) по атакующему. Щиты атакующего поглощают ".nicenum($round['aabsorb'])." мощности выстрелов</center>";

        $text .= "<table border=1 width=100%><tr>";        // Атакующие
        foreach ( $round['attackers'] as $n=>$attacker )
        {
            $text .= GenSlot ( 0, 0, 0, $attacker['name'], $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker, null, 0, 1 );
        }
        $text .= "</tr></table>";

        $text .= "<table border=1 width=100%><tr>";        // Обороняющиеся
        foreach ( $round['defenders'] as $n=>$defender )
        {
            $text .= GenSlot ( 0, 0, 0, $defender['name'], $defender['g'], $defender['s'], $defender['p'], $dmap, $defender, $defender, 0, 0 );
        }
        $text .= "</tr></table>";
    }

    // Результаты боя.
//<!--A:167658,W:167658-->
    if ( $res['result'] === "awon" ) $text .= "<p> Атакующий выиграл битву!";
    else if ( $res['result'] === "dwon" ) $text .= "<p> Обороняющийся выиграл битву!";
    else if ( $res['result'] === "draw" ) $text .= "<p> Бой оканчивается вничью, оба флота возвращаются на свои планеты";

    return $text;
}

// Битва с чужими / пиратами.
// Состав флота чужих/пиратов определяется параметром level ( 0: слабые, 1: средние, 2: сильные )
function ExpeditionBattle ( $fleet_id, $pirates, $level, $when )
{
    global $db_prefix;
    global $GlobalUni;

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );

    global  $db_host, $db_user, $db_pass, $db_name, $db_prefix;
    $a = array ();
    $d = array ();

    $unitab = LoadUniverse ();
    $fid = $unitab['fid'];
    $did = $unitab['did'];
    $rf = $unitab['rapid'];

    // *** Союзные атаки не должны вступать битву. Игнорировать их.
    $f = LoadFleet ( $fleet_id );

    // *** Сгенерировать исходные данные

    // Список атакующих
    $anum = 0;
    $a[0] = LoadUser ( $f['owner_id'] );
    $a[0]['fleet'] = array ();
    foreach ($fleetmap as $i=>$gid) $a[0]['fleet'][$gid] = abs($f["ship$gid"]);
    $start_planet = GetPlanet ( $f['start_planet'] );
    $a[0]['g'] = $start_planet['g'];
    $a[0]['s'] = $start_planet['s'];
    $a[0]['p'] = $start_planet['p'];
    $a[0]['id'] = $fleet_id;
    $a[0]['points'] = $a[0]['fpoints'] = 0;
    $anum++;

    // Список обороняющихся
    $dnum = 0;
    $d[0] = LoadUser ( 99999 );
    if ( $pirates ) {
        $d[0]['oname'] = "Piraten";
        $d[0]['r109'] = max (0, $a[0]['r109'] - 3);
        $d[0]['r110'] = max (0, $a[0]['r110'] - 3);
        $d[0]['r111'] = max (0, $a[0]['r111'] - 3);
    }
    else {
        $d[0]['oname'] = "Aliens";
        $d[0]['r109'] = $a[0]['r109'] + 3;
        $d[0]['r110'] = $a[0]['r110'] + 3;
        $d[0]['r111'] = $a[0]['r111'] + 3;
    }
    $d[0]['fleet'] = array ();
    $d[0]['defense'] = array ();
    foreach ($fleetmap as $i=>$gid) {        // Определить состав флота пиратов / чужих

        if ( $pirates ) {
            // Пиратский флот, Округление состава флота вниз.
            // Нормальный - 30% +/- 3% от количества кораблей вашего флота + 5 ЛИ
            // Сильный - 50% +/- 5% от количества кораблей вашего флота + 3 Крейсера
            // Оч. Сильный - 80% +/- 8% от количества кораблей вашего флота + 2 Линка

            if ( $a[0]['fleet'][$gid] > 0 )
            {
                if ( $level == 0 ) $ratio = mt_rand ( 27, 33 ) / 100;
                else if ( $level == 1 ) $ratio = mt_rand ( 45, 55 ) / 100;
                else if ( $level == 2 ) $ratio = mt_rand ( 72, 88 ) / 100;
                $d[0]['fleet'][$gid] = floor ($a[0]['fleet'][$gid] * $ratio);
            }
            else $d[0]['fleet'][$gid] = 0;
        }
        else {
            // Флот Чужих, Округление состава флота вверх.
            // Нормальный - 40% +/- 4% от количества кораблей вашего флота + 5 ТИ
            // Сильный - 60% +/- 6% от количества кораблей вашего флота + 3 Линейки
            // Оч. Сильный - 90% +/- 9% от количества кораблей вашего флота + 2 Уника

            if ( $a[0]['fleet'][$gid] > 0 )
            {
                if ( $level == 0 ) $ratio = mt_rand ( 36, 44 ) / 100;
                else if ( $level == 1 ) $ratio = mt_rand ( 54, 66 ) / 100;
                else if ( $level == 2 ) $ratio = mt_rand ( 81, 99 ) / 100;
                $d[0]['fleet'][$gid] = ceil ($a[0]['fleet'][$gid] * $ratio);
            }
            else $d[0]['fleet'][$gid] = 0;
        }

    }

    if ( $pirates ) {
        if ( $level == 0 ) $d[0]['fleet'][204] += 5;
        else if ( $level == 1 ) $d[0]['fleet'][206] += 3;
        else if ( $level == 2 ) $d[0]['fleet'][207] += 2;
    }
    else {
        if ( $level == 0 ) $d[0]['fleet'][205] += 5;
        else if ( $level == 1 ) $d[0]['fleet'][215] += 3;
        else if ( $level == 2 ) $d[0]['fleet'][213] += 2;
    }

    foreach ($defmap as $i=>$gid) $d[0]['defense'][$gid] = 0;
    $target_planet = GetPlanet ( $f['target_planet'] );
    $d[0]['g'] = $target_planet['g'];
    $d[0]['s'] = $target_planet['s'];
    $d[0]['p'] = $target_planet['p'];
    $d[0]['id'] = $target_planet['planet_id'];
    $d[0]['points'] = $d[0]['fpoints'] = 0;
    $dnum++;

    $source .= "Rapidfire = $rf\n";
    $source .= "FID = $fid\n";
    $source .= "DID = $did\n";

    $source .= "Attackers = ".$anum."\n";
    $source .= "Defenders = ".$dnum."\n";

    foreach ($a as $num=>$attacker)
    {
        $source .= "Attacker".$num." = (<".$attacker['oname']."> ";
        $source .= $attacker['id'] . " ";
        $source .= $attacker['g'] . " " . $attacker['s'] . " " . $attacker['p'] . " ";
        $source .= $attacker['r109'] . " " . $attacker['r110'] . " " . $attacker['r111'] . " ";
        foreach ($fleetmap as $i=>$gid) $source .= $attacker['fleet'][$gid] . " ";
        $source .= ")\n";
    }
    foreach ($d as $num=>$defender)
    {
        $source .= "Defender".$num." = (<".$defender['oname']."> ";
        $source .= $defender['id'] . " ";
        $source .= $defender['g'] . " " . $defender['s'] . " " . $defender['p'] . " ";
        $source .= $defender['r109'] . " " . $defender['r110'] . " " . $defender['r111'] . " ";
        foreach ($fleetmap as $i=>$gid) $source .= $defender['fleet'][$gid] . " ";
        foreach ($defmap as $i=>$gid) $source .= $defender['defense'][$gid] . " ";
        $source .= ")\n";
    }

    $battle = array ( null, $source, "", "", $when );
    $battle_id = AddDBRow ( $battle, "battledata" );

    $bf = fopen ( "battledata/battle_".$battle_id.".txt", "w" );
    fwrite ( $bf, $source );
    fclose ( $bf );

    // *** Передать данные боевому движку

    $arg = "\"battle_id=$battle_id\"";
    system ( $unitab['battle_engine'] . " $arg" );

    // *** Обработать выходные данные

    $battleres = file_get_contents ( "battleresult/battle_".$battle_id.".txt" );
    $res = unserialize($battleres);

    // Определить исход битвы.
    if ( $res['result'] === "awon" ) $battle_result = 0;
    else if ( $res['result'] === "dwon" ) $battle_result = 1;
    else $battle_result = 2;

    // Рассчитать общие потери (учитывать дейтерий и восстановленную оборону)
    $aloss = $dloss = 0;
    $loss = CalcLosses ( $a, $d, $res, array() );
    $a = $loss['a'];
    $d = $loss['d'];
    $aloss = $loss['aloss'];
    $dloss = $loss['dloss'];

    // Сгенерировать боевой доклад.
    loca_add ( "battlereport", $GlobalUni['lang'] );
    loca_add ( "technames", $GlobalUni['lang'] );
    
    $text = ShortBattleReport ( $res, $when );

    // Разослать сообщения
    $mailbox = array ();

    // Если флот уничтожен за 1 или 2 раунда - не показывать лог боя для атакующих.
    if ( count($res['rounds']) <= 2 && $battle_result == 1 ) $text = "Контакт с флотом потерян. <br> Это означает, что его уничтожили первым же залпом <!--A:$aloss,W:$dloss-->";

    foreach ( $a as $i=>$user )        // Атакующие
    {
        if ( $mailbox[ $user['player_id'] ] == true ) continue;
        $bericht = SendMessage ( $user['player_id'], "Командование флотом", "Боевой доклад", $text, 6, $when );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">Боевой доклад [".$target_planet['g'].":".$target_planet['s'].":".$target_planet['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        SendMessage ( $user['player_id'], "Командование флотом", $subj, "", 2, $when );
        $mailbox[ $user['player_id'] ] = true;
    }

    // Обновить лог боевого доклада
    $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=admin&session={PUBLIC_SESSION}&mode=BattleReport&bericht=$battle_id\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">Боевой доклад [".$target_planet['g'].":".$target_planet['s'].":".$target_planet['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
    $query = "UPDATE ".$db_prefix."battledata SET title = '".$subj."', report = '".$text."' WHERE battle_id = $battle_id;";
    dbquery ( $query );

    // Почистить старые боевые доклады
    $ago = $when - 2 * 7 * 24 * 60 * 60;
    $query = "DELETE FROM ".$db_prefix."battledata WHERE date < $ago;";
    dbquery ($query);

    // Модифицировать флот
    WritebackBattleResultsExpedition ( $a, $d, $res );

    // Изменить статистику игроков
    foreach ( $a as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    RecalcRanks ();

    // Чистим промежуточные данные боевого движка
    unlink ( "battledata/battle_".$battle_id.".txt" );
    unlink ( "battleresult/battle_".$battle_id.".txt" );

    return $battle_result;
}

// -----------------------------------------------------------------------------------------------------------------------------------------------------------

// Ракетная атака.
function RocketAttack ( $fleet_id, $planet_id )
{
    global $UnitParam;

    $fleet = LoadFleet ($fleet_id);
    $amount = $fleet['ipm_amount'];
    $primary = $fleet['ipm_target'];
    $origin = GetPlanet ($fleet['start_planet']);
    $target = GetPlanet ($planet_id);
    $origin_user = LoadUser ($origin['owner_id']);
    $target_user = LoadUser ($target['owner_id']);

    // Отбить атаку МПР перехватчиками
    $ipm = $amount;
    $abm = $target['d502'];
    $ipm = max (0, $ipm - $abm);
    $ipm_destroyed = $amount - $ipm;
    $target['d502'] -= $ipm_destroyed;

    $maxdamage = $ipm * 12000 * (1 + $origin_user['r109'] / 10);

    // Произвести атаку первичной цели
    if ( $primary > 0 && $ipm > 0 )
    {
        $armor = $UnitParam[$primary][0] * (1 + 0.1 * $target_user['r111']) / 10;
        $count = $target["d$primary"];
        $destroyed = min ( floor ( $maxdamage / $armor ), $count );
        $target["d$primary"] -= $destroyed;
        $maxdamage -= $destroyed * $armor;
        $maxdamage -= $destroyed;
    }

    // Расчитать потери обороны, если еще остались МПР
    if ($maxdamage > 0)
    {
        $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );
        foreach ($defmap as $i=>$id)
        {
            if ($id == $primary) continue;
            $armor = $UnitParam[$id][0] * (1 + 0.1 * $target_user['r111']) / 10;
            $count = $target["d$id"];
            if ($count == 0) continue;
            $destroyed = min ( floor ( $maxdamage / $armor ), $count );
            $target["d$id"] -= $destroyed;
            $maxdamage -= $destroyed * $armor;
            $maxdamage -= $destroyed;
            if ($maxdamage <= 0) break;
        }
    }

    // Записать назад потери обороны.
    SetPlanetDefense ( $planet_id, $target );

    // Изменить статистику игроков
    RecalcRanks ();

    $text = "$amount ракетам из общего числа выпущенных ракет с планеты ".$origin['name']." <a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>  ";
    $text .= "удалось попасть на Вашу планету ".$target['name']." <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a> !<br>";
    if ($ipm_destroyed) $text .= "$ipm_destroyed ракет(-ы) было уничтожено Вашими ракетами-перехватчиками<br>:<br>";

    $defmap = array ( 503, 502, 408, 407, 406, 405, 404, 403, 402, 401 );
    $text .= "<table width=400><tr><td class=c colspan=4>Поражённая оборона</td></tr>";
    $n = 0;
    foreach ( $defmap as $i=>$gid )
    {
        if ( ($n % 2) == 0 ) $text .= "</tr>";
        if ( $target["d$gid"] ) {
            $text .= "<td>".loca("NAME_$gid")."</td><td>".nicenum($target["d$gid"])."</td>";
            $n++;
        }
    }
    $text .= "</table><br>\n";

    // Обновить активность на планете.
    $queue = GetFleetQueue ( $fleet_id );
    UpdatePlanetActivity ( $planet_id, $queue['end'] );

    SendMessage ( $target_user['player_id'], "Командование флотом", "Ракетная атака", $text, 2);
}

?>