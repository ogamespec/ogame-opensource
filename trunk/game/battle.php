<?php

// Боевой движок OGame.
// Slow & Buggy

// Параметры скорострела.
$FleetRapid = array (
 202 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 0 ),
 203 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 0 ),
 204 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 0 ),
 205 => array ( 667, 0, 0, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 0 ),
 206 => array ( 0, 0, 833, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 0 ),
 207 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 0 ),
 208 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 0 ),
 209 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 0 ),
 210 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 ),
 211 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 0 ),
 212 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 ),
 213 => array ( 0, 0, 0, 0, 0, 0, 0, 0, 800, 0, 800, 0, 0, 500 ),
 214 => array ( 996, 996, 995, 990, 970, 966, 996, 996, 999, 960, 999, 800, 0, 933 ),
 215 => array ( 667, 667, 0, 750, 750, 857, 0, 0, 800, 0, 800, 0, 0, 0 )
);
$DefenseRapid = array (
 202 => array ( 0, 0, 0, 0, 0, 0, 0, 0 ),
 203 => array ( 0, 0, 0, 0, 0, 0, 0, 0 ),
 204 => array ( 0, 0, 0, 0, 0, 0, 0, 0 ),
 205 => array ( 0, 0, 0, 0, 0, 0, 0, 0 ),
 206 => array ( 900, 0, 0, 0, 0, 0, 0, 0 ),
 207 => array ( 0, 0, 0, 0, 0, 0, 0, 0 ),
 208 => array ( 0, 0, 0, 0, 0, 0, 0, 0 ),
 209 => array ( 0, 0, 0, 0, 0, 0, 0, 0 ),
 210 => array ( 0, 0, 0, 0, 0, 0, 0, 0 ),
 211 => array ( 955, 955, 900, 0, 900, 0, 0, 0 ),
 212 => array ( 0, 0, 0, 0, 0, 0, 0, 0 ),
 213 => array ( 0, 900, 0, 0, 0, 0, 0, 0 ),
 214 => array ( 955, 955, 999, 980, 999, 0, 0, 0 ),
 215 => array ( 0, 0, 0, 0, 0, 0, 0, 0 )
);

// Выстрел a => b. Возвращает урон.
// absorbed - накопитель поглощённого щитами урона (для того, кого атакуют, то есть для юнита "b").
// loss - накопитель потерь (стоимость юнита металл+кристалл).
function UnitShoot ($slot_obj, $a, $b, &$absorbed, &$loss, &$dm, &$dk )
{
    global $UnitParam;
    $adelta = 0;
    $apower = $UnitParam[ $a["obj_type"] ][2] * (10+$slot_obj["weap"]) / 10;

    if ($b["e"]) return $apower; // Уже взорван.
    if ($b["shield"] == 0) {  // Щитов нет.
        if ($apower >= $b["h"]) $b["h"] = 0;
        else $b["h"] -= $apower;
    }
    else { // Отнимаем от щитов, и если хватает урона, то и от брони.
        $prc = $b["shieldmax"] * 0.01;
        $depleted = floor ($apower / $prc);
        if ($b["shield"] < ($depleted * $prc)) {
            $absorbed += $b["shield"];
            $adelta = $apower - $b["shield"];
            if ($adelta >= $b["h"]) $b["h"] = 0;
            else $b["h"] -= $adelta;
            $b["shield"] = 0;
        }
        else {
            $b["shield"] -= $depleted * $prc;
            $absorbed += $apower;
        }
    }
    if ($b["h"] <= $b["hm"] * 0.7 && $b["shield"] == 0) {    // Взорвать и добавить лома.
        if (mt_rand(0, 99) >= (($b["h"] * 100) / $b["hm"]) || $b["h"] == 0) {
            $m = $k = $d = $e = 0;
            ShipyardPrice ( $b["obj_type"], &$m, &$k, &$d, &$e );
            $dm += $m * (30 / 100);
            $dk += $k * (30 / 100);
            $loss += $m + $k;
            $b["e"] = 1;
        }
    }
    return $apower;
}

// Почистить взорванные корабли и оборону. Возвращает количество взорванных единиц.
function WipeExploded ( $units, &$amount)
{
    $tab = array ();
    $exploded = 0;
    for ($i=0; $i<$amount; $i++)
    {
        if ( ! $units[$i]["e"] ) $tab[] = $units[$i];
        else $exploded++;
    }
    $units = $tab;
    $amount = $exploded;
    return $tab;
}

// Сгенерировать HTML-код слота.
// Если techs = 1, то показать технологии (в раундах технологии показывать не надо).
function GenSlot ($units, $objnum, $slot, $slot_obj, $techs)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );
    $s = array ();
    $sum = 0;

    for ($i=0; $i<$objnum; $i++)
    {
        $unit = $units[$i];
        if ( $unit["s"] != $slot ) continue;
        $s[ $unit["obj_type"] ] ++;
        $sum ++;
    }

    $text = "<th><br><center>%s ".$slot_obj['name']." (<a href=# onclick=showGalaxy(".$slot_obj['g'].",".$slot_obj['s'].",".$slot_obj['p']."); >[".$slot_obj['g'].":".$slot_obj['s'].":".$slot_obj['p']."]</a>)<br>";
    if ($sum > 0) {
        if (techs) $text .= "Вооружение: ".($slot_obj['weap']*10)."% Щиты: ".($slot_obj['shld']*10)."% Броня: ".($slot_obj['hull']*10)."% ";

        $text .= "<table border=1>";
        $text .= "<tr><th>Тип</th>";
        foreach ( $fleetmap as $i=>$gid)
        {
            if ( $s[$gid] > 0 ) $text .= "<th>".loca("NAME_$gid")."</th>";
        }
        foreach ( $defmap as $i=>$gid)
        {
            if ( $s[$gid] > 0 ) $text .= "<th>".loca("NAME_$gid")."</th>";
        }
        $text .= "</tr>";
        $text .= "<tr><th>Кол-во.</th>";
        foreach ( $fleetmap as $i=>$gid)
        {
            if ( $s[$gid] > 0 ) $text .= "<th>".nicenum($s[$gid])."</th>";
        }
        foreach ( $defmap as $i=>$gid)
        {
            if ( $s[$gid] > 0 ) $text .= "<th>".nicenum($s[$gid])."</th>";
        }
        $text .= "</tr>";
        $text .= "<tr><th>Воор.:</th>";
        foreach ( $fleetmap as $i=>$gid)
        {
            $weap = $UnitParam[ $gid ][2] * (10+$slot_obj["weap"]) / 10;
            if ( $s[$gid] > 0 ) $text .= "<th>".nicenum($weap)."</th>";
        }
        foreach ( $defmap as $i=>$gid)
        {
            $weap = $UnitParam[ $gid ][2] * (10+$slot_obj["weap"]) / 10;
            if ( $s[$gid] > 0 ) $text .= "<th>".nicenum($weap)."</th>";
        }
        $text .= "</tr>";
        $text .= "<tr><th>Щиты</th>";
        foreach ( $fleetmap as $i=>$gid)
        {
            $shld = $UnitParam[ $gid ][1] * (10+$slot_obj["shld"]) / 10;
            if ( $s[$gid] > 0 ) $text .= "<th>".nicenum($shld)."</th>";
        }
        foreach ( $defmap as $i=>$gid)
        {
            $shld = $UnitParam[ $gid ][1] * (10+$slot_obj["shld"]) / 10;
            if ( $s[$gid] > 0 ) $text .= "<th>".nicenum($shld)."</th>";
        }
        $text .= "</tr>";
        $text .= "<tr><th>Броня</th>";
        foreach ( $fleetmap as $i=>$gid)
        {
            $hull = $UnitParam[ $gid ][0] * (10+$slot_obj["hull"]) / 10;
            if ( $s[$gid] > 0 ) $text .= "<th>".nicenum($hull)."</th>";
        }
        foreach ( $defmap as $i=>$gid)
        {
            $hull = $UnitParam[ $gid ][0] * (10+$slot_obj["hull"]) / 10;
            if ( $s[$gid] > 0 ) $text .= "<th>".nicenum($hull)."</th>";
        }
        $text .= "</tr>";
        $text .= "</table>";
    }
    else $text .= "уничтожен.";
    $text .= "</center></th>";

    return $text;
}

// Начать битву между атакующим fleet_id и обороняющимся planet_id.
function StartBattle ( $fleet_id, $planet_id )
{
    global $UnitParam, $FleetRapid, $DefenseRapid;
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $now = time ();
    $text = "<table width=\"99%\">\n   <tr>\n    <td>\n\nДата/Время: ".date ("m-d H:i:s", $now)." . Произошёл бой между следующими флотами:<br>";

    $aloss = $dloss = 0;    // Общие потери.
    $dm = $dk = 0;        // Поле обломков.

    // **** Инициализировать нападающих. **** 

    $aslot = array ();
    $anum = 1;
    $aunits = array ();
    $aunum = 0;

    $slot_id = 0;
    $fleet_obj = LoadFleet ($fleet_id);
    $user = LoadUser ($fleet_obj['owner_id']);
    $origin = GetPlanet ( $fleet_obj['start_planet'] );

    $aslot[$slot_id]['name'] = $user['oname'];
    $aslot[$slot_id]['g'] = $origin['g'];
    $aslot[$slot_id]['s'] = $origin['s'];
    $aslot[$slot_id]['p'] = $origin['p'];
    $aslot[$slot_id]['weap'] = $user['r109'];
    $aslot[$slot_id]['shld'] = $user['r110'];
    $aslot[$slot_id]['hull'] = $user['r111'];

    foreach ( $fleetmap as $i=>$gid)
    {
        $amount = $fleet_obj["ship$gid"];
        $hull = $UnitParam[$gid][0] * 0.1 * (10+$aslot[$slot_id]['hull']) / 10;
        $unit = array ( "hull" => $hull, "hm" => $hull, "obj_type" => $gid, "s" => $slot_id, "e" => 0 );
        for ($i=0; $i<$amount; $i++) $aunits[] = $unit;
        $aunum += $amount;
    }

    // ****  Инициализировать обороняющихся. **** 

    $dslot = array ();
    $dnum = 1;
    $dunits = array ();
    $dunum = 0;

    $slot_id = 0;
    $planet = GetPlanet ($planet_id);
    $user = LoadUser ($planet['owner_id']);

    $dslot[$slot_id]['name'] = $user['oname'];
    $dslot[$slot_id]['g'] = $planet['g'];
    $dslot[$slot_id]['s'] = $planet['s'];
    $dslot[$slot_id]['p'] = $planet['p'];
    $dslot[$slot_id]['weap'] = $user['r109'];
    $dslot[$slot_id]['shld'] = $user['r110'];
    $dslot[$slot_id]['hull'] = $user['r111'];

    foreach ( $fleetmap as $i=>$gid)
    {
        $amount = $planet["f$gid"];
        $hull = $UnitParam[$gid][0] * 0.1 * (10+$dslot[$slot_id]['hull']) / 10;
        $unit = array ( "hull" => $hull, "hm" => $hull, "obj_type" => $gid, "s" => $slot_id, "e" => 0 );
        for ($i=0; $i<$amount; $i++) $dunits[] = $unit;
        $dunum += $amount;
    }
    foreach ( $defmap as $i=>$gid)
    {
        $amount = $planet["d$gid"];
        $hull = $UnitParam[$gid][0] * 0.1 * (10+$dslot[$slot_id]['hull']) / 10;
        $unit = array ( "hull" => $hull, "hm" => $hull, "obj_type" => $gid, "s" => $slot_id, "e" => 0 );
        for ($i=0; $i<$amount; $i++) $dunits[] = $unit;
        $dunum += $amount;
    }

    // ****  Флоты перед боем. ****  

    $text .= "<table border=1 width=100%><tr>";
    $text .= GenSlot ($aunits, $aunum, 0, $aslot[0], true);
    $text .= "</tr></table>";
    $text .= "<table border=1 width=100%><tr>";
    $text .= GenSlot ($dunits, $dunum, 0, $dslot[0], true);
    $text .= "</tr></table>";

    // ****  Начать бой. **** 

    for ($round=0; $round<6; $round++)
    {
        if ( $aunum == 0 || $dunum == 0 ) break;

        // Сбросить статистику.
        $ashoots = $dshoots = $apower = $dpower = $aabsorbed = $dabsorbed = 0;

        // Зарядить щиты.
        for ($i=0; $i<$aunum; $i++) {
            if ( $aunits[$i]["e"] ) $aunits[$i]["shield"] = $aunits[$i]["shieldmax"] = 0;
            else $aunits[$i]["shield"] = $aunits[$i]["shieldmax"] = $UnitParam[ $aunits[$i]["obj_type"] ][1] * (10+$aslot[$aunits[$i]["s"]]['shld']) / 10;
        }
        for ($i=0; $i<$dunum; $i++) {
            if ( $dunits[$i]["e"] ) $dunits[$i]["shield"] = $dunits[$i]["shieldmax"] = 0;
            else $dunits[$i]["shield"] = $dunits[$i]["shieldmax"] = $UnitParam[ $dunits[$i]["obj_type"] ][1] * (10+$dslot[$dunits[$i]["s"]]['shld']) / 10;
        }

        // Произвести выстрелы - Атакующие
        for ($slot=0; $slot<$anum; $slot++)
        {
            for ($i=0; $i<$aunum; $i++) {
                $rapidfire = 1;
                $unit = $aunits[$i];
                if ($unit["s"] == $slot) {
                    // Выстрел.
                    while ($rapidfire) {
                        $idx = mt_rand(0, $dunum-1);
                        $apower += UnitShoot ($aslot[$slot], $unit, $dunits[$idx], &$dabsorbed, &$dloss, &$dm, &$dk);
                        $ashoots++;
                        if ($unit["obj_type"] < 400) { // Только флот обладает стрельбой очередями.
                            if ($dunits[$idx]["obj_type"] < 400) $rapidchance = $FleetRapid[$unit["obj_type"]][$dunits[$idx]["obj_type"]-202];
                            else $rapidchance = $DefenseRapid[$unit["obj_type"]][$dunits[$idx]["obj_type"]-401];
                            $rapidfire = mt_rand(0, 999) < $rapidchance;
                        }
                        else $rapidfire = 0;
                        //if (Rapidfire == 0) $rapidfire = 0;
                    }
                }
            }
        }

        // Произвести выстрелы - Обороняющиеся
        for ($slot=0; $slot<$dnum; $slot++)
        {
            for ($i=0; $i<$dunum; $i++) {
                $rapidfire = 1;
                $unit = $dunits[$i];
                if ($unit["s"] == $slot) {
                    // Выстрел.
                    while ($rapidfire) {
                        $idx = mt_rand(0, $aunum-1);
                        $dpower += UnitShoot ($dslot[$slot], $unit, $aunits[$idx], &$aabsorbed, &$aloss, &$dm, &$dk);
                        $dshoots++;
                        if ($unit["obj_type"] < 400) { // Только флот обладает стрельбой очередями.
                            if ($aunits[$idx]["obj_type"] < 400) $rapidchance = $FleetRapid[$unit["obj_type"]][$aunits[$idx]["obj_type"]-202];
                            else $rapidchance = $DefenseRapid[$unit["obj_type"]][$aunits[$idx]["obj_type"]-401];
                            $rapidfire = mt_rand(0, 999) < $rapidchance;
                        }
                        else $rapidfire = 0;
                        //if (Rapidfire == 0) $rapidfire = 0;
                    }
                }
            }
        }

        $text .= "<br><center>";
        $text .= "Атакующий флот делает: ".nicenum($ashoots)." выстрела(ов) общей мощностью ".nicenum($apower)." по обороняющемуся. Щиты обороняющегося поглощают ".nicenum($dabsorbed)." мощности выстрелов.";
        $text .= "<br>";
        $text .= "Обороняющийся флот делает ".nicenum($dshoots)." выстрела(ов) общей мощностью ".nicenum($dpower)."  выстрела(ов) по атакующему. Щиты атакующего поглощают ".nicenum($aabsorbed)." мощности выстрелов.";
        $text .= "</center>";

        // Вычистить взорванные корабли и оборону.
        $aunits = WipeExploded ($aunits, &$aunum);
        $dunits = WipeExploded ($dunits, &$dunum);

        $text .= "<table border=1 width=100%><tr>";
        $text .= GenSlot ($aunits, $aunum, 0, $aslot[0], false);
        $text .= "</tr></table>";
        $text .= "<table border=1 width=100%%><tr>";
        $text .= GenSlot ($dunits, $dunum, 0, $dslot[0], false);
        $text .= "</tr></table>";
    }

    $moonchance = min ( 20, ($dm + $dk) / 100000 );

    $text .= "    </td>\n\n   </tr>\n</table>\n";

    // Развернуть флоты атакующих.
    //DispatchFleet ($fleet, $origin, $target, 101, 30, $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d']);

    // Выслать всем боевые доклады.
    SendMessage ( $fleet_obj['owner_id'], "Атака", "Атака", "ATT " . $text, 5);
    SendMessage ( $planet['owner_id'], "Атака", "Атака", "DEF " . $text, 5);
}

// Ракетная атака.
function RocketAttack ( $fleet_id, $planet_id )
{
}

?>