<?php

// Боевой движок OGame.

// Восстановление обороны.
function RepairDefense ( $d, $res, $defrepair, $defrepair_delta )
{
    $repaired = array ( 401=>0, 402=>0, 403=>0, 404=>0, 405=>0, 406=>0, 407=>0, 408=>0 );
    $exploded = array ( 401=>0, 402=>0, 403=>0, 404=>0, 405=>0, 406=>0, 407=>0, 408=>0 );
    $exploded_total = 0;

    $rounds = count ( $res['rounds'] );
    if ( $rounds > 0 ) 
    {
        // Посчитать взорванную оборону.
        $last = $res['rounds'][$rounds - 1];
        foreach ( $exploded as $gid=>$amount )
        {
            $exploded[$gid] = $d[0]['defense'][$gid] - $last['defenders'][0][$gid];
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
function Plunder ( $cargo, $m, $k, $d, &$cm, &$ck, &$cd )
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

    $cm = floor($mc); $ck = floor($kc); $cd = floor($dc);
}

// Рассчитать общие потери (учитывая восстановленную оборону).
function CalcLosses ( $a, $d, $res, $repaired, &$aloss, &$dloss )
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );
    $amap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $dmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 401, 402, 403, 404, 405, 406, 407, 408 );

    $met = $kris = $deut = $energy = 0;
    $aprice = $dprice = 0;

    // Стоимость юнитов до боя.
    foreach ($a as $i=>$attacker)                // Атакующие
    {
        foreach ( $fleetmap as $n=>$gid )
        {
            $amount = $attacker['fleet'][$gid];
            if ( $amount > 0 ) {
                ShipyardPrice ( $gid, &$met, &$kris, &$deut, &$energy );
                $aprice += ( $met + $kris + $deut ) * $amount;
            }
        }
    }

    foreach ($d as $i=>$defender)            // Обороняющиеся
    {
        foreach ( $fleetmap as $n=>$gid )
        {
            $amount = $defender['fleet'][$gid];
            if ( $amount > 0 ) {
                ShipyardPrice ( $gid, &$met, &$kris, &$deut, &$energy );
                $dprice += ( $met + $kris + $deut ) * $amount;
            }
        }
        foreach ( $defmap as $n=>$gid )
        {
            $amount = $defender['defense'][$gid];
            if ( $amount > 0 ) {
                ShipyardPrice ( $gid, &$met, &$kris, &$deut, &$energy );
                $dprice += ( $met + $kris + $deut ) * $amount;
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
                    ShipyardPrice ( $gid, &$met, &$kris, &$deut, &$energy );
                    $alast += ( $met + $kris + $deut ) * $amount;
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
                    ShipyardPrice ( $gid, &$met, &$kris, &$deut, &$energy );
                    $dlast += ( $met + $kris + $deut ) * $amount;
                }
            }
        }

        $aloss = $aprice - $alast;
        $dloss = $dprice - $dlast;
    }
    else $aloss = $dloss = 0;
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
    return $cargo;
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
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $i=>$gid ) $ships += $attacker[$gid];
            $cargo = ( FleetCargoSummary ( $attacker ) - ($fleet_obj['m']+$fleet_obj['k']+$fleet_obj['d']) - $fleet_obj['fuel'] ) / $sum_cargo;
            if ($ships > 0) DispatchFleet ($attacker, $origin, $target, $fleet_obj['mission']+100, 30, $cm * $cargo, $ck * $cargo, $cd * $cargo, $fleet_obj['fuel'] / 2);
        }

        foreach ( $last['defenders'] as $i=>$defender )        // Обороняющиеся
        {
            if ( $i == 0 )    // Планета
            {
                AdjustResources ( $cm, $ck, $cd, $defender['id'], '-' );
            }
            else        // Флоты на удержании
            {
            }
        }
    }

    // Бой без раундов.

    else 
    {
        foreach ( $a as $i=>$attacker )            // Атакующие
        {
            $fleet_obj = LoadFleet ( $attacker['id'] );
            $origin = GetPlanet ( $fleet_obj['start_planet'] );
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            $ships = 0;
            foreach ( $fleetmap as $i=>$gid ) $ships += $attacker['fleet'][$gid];
            $cargo = ( FleetCargoSummary ( $attacker['fleet'] ) - ($fleet_obj['m']+$fleet_obj['k']+$fleet_obj['d']) - $fleet_obj['fuel'] ) / $sum_cargo;
            if ($ships > 0) DispatchFleet ($attacker['fleet'], $origin, $target, $fleet_obj['mission']+100, 30, $cm * $cargo, $ck * $cargo, $cd * $cargo, $fleet_obj['fuel'] / 2);
        }

    }
}

// Сгенерировать HTML-код одного слота.
function GenSlot ( $user, $g, $s, $p, $unitmap, $fleet, $defense, $show_techs, $attack )
{
    global $UnitParam;

    $text = "<th><br>";

    $weap = $user["r109"];
    $shld = $user["r110"];
    $armor = $user["r111"];

    $text .= "<center>";
    if ($attack) $text .= "Флот атакующего";
    else $text .= "Обороняющийся";
    $text .= " ".$user['oname']." (<a href=# onclick=showGalaxy($g,$s,$p); >[$g:$s:$p]</a>)";
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
function BattleReport ( $a, $d, $res, $now, $aloss, $dloss, $cm, $ck, $cd, $moonchance, $mooncreated, $repaired, $fakeids=false )
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
    foreach ($a as $i=>$attacker)
    {
        $text .= GenSlot ( $attacker, $attacker['g'], $attacker['s'], $attacker['p'], $amap, $attacker['fleet'], null, 1, 1 );
    }
    $text .= "</tr></table>";
    $text .= "<table border=1 width=100%><tr>";
    foreach ($d as $i=>$defender)
    {
        $text .= GenSlot ( $defender, $defender['g'], $defender['s'], $defender['p'], $dmap, $defender['fleet'], $defender['defense'], 1, 0 );
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
            if ( $fakeids ) {
                $user = array ();
                $start_planet['g'] = mt_rand (1, 9);
                $start_planet['s'] = mt_rand (1, 499);
                $start_planet['p'] = mt_rand (1, 15);
            }
            else {
                $f = LoadFleet ( $attacker['id'] );
                $user = LoadUser ( $f['owner_id'] );
                $start_planet = GetPlanet ( $f['start_planet'] );
            }
            $user['fleet'] = array ();
            foreach ($fleetmap as $g=>$gid) $user['fleet'][$gid] = $attacker[$gid];
            $text .= GenSlot ( $user, $start_planet['g'], $start_planet['s'], $start_planet['p'], $amap, $user['fleet'], null, 0, 1 );
        }
        $text .= "</tr></table>";

        $text .= "<table border=1 width=100%><tr>";        // Обороняющиеся
        foreach ( $round['defenders'] as $n=>$defender )
        {
            if ( $n == 0 )
            {
                if ( $fakeids ) {
                    $user = array ();
                    $user['g'] = mt_rand (1, 9);
                    $user['s'] = mt_rand (1, 499);
                    $user['p'] = mt_rand (1, 15);
                }
                else {
                    $p = GetPlanet ( $defender['id'] );
                    $user = LoadUser ( $p['owner_id'] );
                }
                $user['fleet'] = array ();
                $user['defense'] = array ();
                foreach ($fleetmap as $g=>$gid) $user['fleet'][$gid] = $defender[$gid];
                foreach ($defmap as $g=>$gid) $user['defense'][$gid] = $defender[$gid];
                $text .= GenSlot ( $user, $p['g'], $p['s'], $p['p'], $dmap, $user['fleet'], $user['defense'], 0, 0 );
            }
            else
            {
                if ( $fakeids ) {
                    $user = array ();
                    $start_planet['g'] = mt_rand (1, 9);
                    $start_planet['s'] = mt_rand (1, 499);
                    $start_planet['p'] = mt_rand (1, 15);
                }
                else {
                    $f = LoadFleet ( $defender['id'] );
                    $user = LoadUser ( $f['owner_id'] );
                    $start_planet = GetPlanet ( $f['start_planet'] );
                }
                $user['fleet'] = array ();
                foreach ($fleetmap as $g=>$gid) $user['fleet'][$gid] = $defender[$gid];
                $text .= GenSlot ( $user, $start_planet['g'], $start_planet['s'], $start_planet['p'], $amap, $user['fleet'], null, 0, 0 );
            }
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

// Начать битву между атакующим fleet_id и обороняющимся planet_id.
function StartBattle ( $fleet_id, $planet_id )
{
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

    // *** Сгенерировать исходные данные

    // Список атакующих
    $f = LoadFleet ( $fleet_id );
    $a[0] = LoadUser ( $f['owner_id'] );
    $a[0]['fleet'] = array ();
    foreach ($fleetmap as $i=>$gid) $a[0]['fleet'][$gid] = $f["ship$gid"];
    $start_planet = GetPlanet ( $f['start_planet'] );
    $a[0]['g'] = $start_planet['g'];
    $a[0]['s'] = $start_planet['s'];
    $a[0]['p'] = $start_planet['p'];
    $a[0]['id'] = $fleet_id;
    $a[0]['points'] = $a[0]['fpoints'] = 0;

    // Список обороняющихся
    $p = GetPlanet ( $planet_id );
    $d[0] = LoadUser ( $p['owner_id'] );
    $d[0]['fleet'] = array ();
    $d[0]['defense'] = array ();
    foreach ($fleetmap as $i=>$gid) $d[0]['fleet'][$gid] = $p["f$gid"];
    foreach ($defmap as $i=>$gid) $d[0]['defense'][$gid] = $p["d$gid"];
    $d[0]['g'] = $p['g'];
    $d[0]['s'] = $p['s'];
    $d[0]['p'] = $p['p'];
    $d[0]['id'] = $planet_id;
    $d[0]['points'] = $d[0]['fpoints'] = 0;

    $source .= "Rapidfire = $rf\n";
    $source .= "FID = $fid\n";
    $source .= "DID = $did\n";

    $source .= "Attackers = 1\n";
    $source .= "Defenders = 1\n";

    $source .= "Attacker0 = (".$a[0]['id']." ";
    $source .= $a[0]['r109'] . " " . $a[0]['r110'] . " " . $a[0]['r111'] . " ";
    foreach ($fleetmap as $i=>$gid) $source .= $a[0]['fleet'][$gid] . " ";
    $source .= ")\n";
    $source .= "Defender0 = (".$d[0]['id']." ";
    $source .= $d[0]['r109'] . " " . $d[0]['r110'] . " " . $d[0]['r111'] . " ";
    foreach ($fleetmap as $i=>$gid) $source .= $d[0]['fleet'][$gid] . " ";
    foreach ($defmap as $i=>$gid) $source .= $d[0]['defense'][$gid] . " ";
    $source .= ")\n";

    $battle_id = IncrementDBGlobal ("nextbattle");
    $battle = array ( $battle_id, $source, '' );
    AddDBRow ( $battle, "battledata");

    // *** Передать данные боевому движку

    $arg = "\"db_host=$db_host&db_user=$db_user&db_pass=$db_pass&db_name=$db_name&db_prefix=$db_prefix&battle_id=$battle_id\"";
    system ( $unitab['battle_engine'] . " $arg" );

    // *** Обработать выходные данные

    $query = "SELECT * FROM ".$db_prefix."battledata WHERE battle_id = $battle_id";
    $result = dbquery ($query);
    if ( $result == null ) return;
    $battle = dbarray ($result);

    $res = unserialize($battle['result']);

    // Удалить уже ненужные боевые данные.
    $query = "DELETE FROM ".$db_prefix."battledata WHERE battle_id = $battle_id";
    dbquery ($query);

    // Определить исход битвы.
    if ( $res['result'] === "awon" ) $battle_result = 0;
    else if ( $res['result'] === "dwon" ) $battle_result = 1;
    else $battle_result = 2;

    // Восстановить оборону
    $repaired = RepairDefense ( $d, $res, $unitab['defrepair'], $unitab['defrepair_delta'] );

    // Рассчитать общие потери (учитывать дейтерий и восстановленную оборону)
    $aloss = $dloss = 0;
    CalcLosses ( $a, $d, $res, $repaired, &$aloss, &$dloss );

    // Захватить ресурсы
    $cm = $ck = $cd = 0;
    if ( $battle_result == 0 )
    {
        $sum_cargo = CargoSummaryLastRound ( $a, $res );
        Plunder ( $sum_cargo, $p['m'], $p['k'], $p['d'], &$cm, &$ck, &$cd );
    }

    // Модифицировать флоты и планету в соответствии с потерями и захваченными ресурсами
    WritebackBattleResults ( $a, $d, $res, $repaired, $cm, $ck, $cd, $sum_cargo );

    // Изменить статистику игроков
    //foreach ( $a as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    //foreach ( $d as $i=>$user ) AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
    RecalcRanks ();

    // Создать поле обломков.
    $debris_id = CreateDebris ( $p['g'], $p['s'], $p['p'], $p['owner_id'] );
    AddDebris ( $debris_id, $res['dm'], $res['dk'] );

    // Создать луну
    $mooncreated = false;
    $moonchance = min ( floor ( ($res['dm'] + $res['dk']) / 100000), 20 );
    if ( PlanetHasMoon ( $planet_id ) ) $moonchance = 0;
    if ( mt_rand (1, 100) <= $moonchance ) {
        CreatePlanet ( $p['g'], $p['s'], $p['p'], $p['owner_id'], 0, 1, $moonchance );
        $mooncreated = true;
    }

    // Обновить активность на планете.
    UpdatePlanetActivity ( $planet_id );

    // Сгенерировать боевой доклад.
    $text = BattleReport ( $a, $d, $res, time(), $aloss, $dloss, $cm, $ck, $cd, $moonchance, $mooncreated, $repaired );

    // Разослать сообщения
    $mailbox = array ();

    foreach ( $a as $i=>$user )        // Атакующие
    {
        if ( $mailbox[ $user['player_id'] ] == true ) continue;
        $bericht = SendMessage ( $user['player_id'], "Командование флотом", "Боевой доклад", $text, 6 );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">Боевой доклад [".$p['g'].":".$p['s'].":".$p['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        SendMessage ( $user['player_id'], "Командование флотом", $subj, "", 2 );
        $mailbox[ $user['player_id'] ] = true;
    }

    foreach ( $d as $i=>$user )        // Обороняющиеся
    {
        if ( $mailbox[ $user['player_id'] ] == true ) continue;
        $bericht = SendMessage ( $user['player_id'], "Командование флотом", "Боевой доклад", $text, 6 );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$d_result[$battle_result]."\">Боевой доклад [".$p['g'].":".$p['s'].":".$p['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        SendMessage ( $user['player_id'], "Командование флотом", $subj, "", 2 );
        $mailbox[ $user['player_id'] ] = true;
    }
}

// Ракетная атака.
function RocketAttack ( $fleet_id, $planet_id )
{
    global $UnitParam;

    $fleet = LoadFleet ($fleet_id);
    $amount = $fleet['ship202'];
    $primary = $fleet['ship203'];
    if ($primary == 0) $primary = 401;
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

    // Расчитать потери обороны, если еще остались МПР
    if ($ipm > 0)
    {
        $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );
        $maxdamage = $ipm * 12000 * (1 + $origin_user['r109'] / 10);
        foreach ($defmap as $i=>$gid)
        {
            if ($gid == 401) $id = $primary;
            else if ($gid <= $primary) $id = $gid - 1;
            else $id = $gid;
            $armor = $UnitParam[$id][0] * 0.1 * (10+$target_user['r111']) / 10;
            $count = $target["d$id"];
            $damage = $maxdamage - $armor * $count;
            $destroyed = 0;
            if ($damage > 0) {
                $destroyed = $count;
                $target["d$id"] = 0;
            }
            else {
                $destroyed = floor ( $maxdamage / $armor );
                $target["d$id"] -= $destroyed;
            }
            $maxdamage -= $destroyed * $armor;
            if ($maxdamage <= 0) break;
        }
    }

    $text = "$amount ракетам из общего числа выпущенных ракет с планеты ".PlanetName($origin)." <a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>  ";
    $text .= "удалось попасть на Вашу планету ".PlanetName($target)." <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a> !<br>";
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
    UpdatePlanetActivity ( $planet_id );

    SendMessage ( $target_user['player_id'], "Командование флотом", "Ракетная атака", $text, 2);
}

?>