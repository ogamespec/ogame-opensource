<?php

// Боевой движок OGame.

function Plunder ( $planet, $a, $res, &$cm, &$ck, &$cd )
{
}

function GenSlot ( $user, $planet, $unitmap, $fleet, $defense, $show_techs )
{
}

// Сгенерировать боевой доклад.
function BattleReport ( $a, $d, $res, $now, $aloss, $dloss, $cm, $ck, $cd, $moonchance )
{
    $amap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $dmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 401, 402, 403, 404, 405, 406, 407, 408 );

    $text = "";

    // Заголовок доклада.
    $text .= "Дата/Время: ".date ("m-d H:i:s", $now)." . Произошёл бой между следующими флотами:<br>";

    // Флоты перед боем.
//<table border=1 width=100%><tr><th><br><center>Флот атакующего Andorianin (<a href=# onclick=showGalaxy(1,260,4); >[1:260:4]</a>)<br>Вооружение: 120% Щиты: 110% Броня: 110% <table border=1><tr><th>Тип</th><th>Б. трансп.</th><th>Бомб.</th><th>Лин. Кр.</th></tr><tr><th>Кол-во.</th><th>20</th><th>40</th><th>100</th></tr><tr><th>Воор.:</th><th>11</th><th>2.200</th><th>1.540</th></tr><tr><th>Щиты</th><th>53</th><th>1.050</th><th>840</th></tr><tr><th>Броня</th><th>2.520</th><th>15.750</th><th>14.700</th></tr></table></center></th></tr></table><table border=1 width=100%><tr><th><br><center>Обороняющийся big303 (<a href=# onclick=showGalaxy(1,182,11); >[1:182:11]</a>)<br>Вооружение: 50% Щиты: 70% Броня: 50% <table border=1><tr><th>Тип</th><th>РУ</th><th>Лёг. лазер</th><th>Тяж. лазер</th><th>Гаусс</th><th>Ион</th><th>М. купол</th><th>Б. купол</th></tr><tr><th>Кол-во.</th><th>100</th><th>100</th><th>100</th><th>2</th><th>18</th><th>1</th><th>1</th></tr><tr><th>Воор.:</th><th>120</th><th>150</th><th>375</th><th>1.650</th><th>225</th><th>2</th><th>2</th></tr><tr><th>Щиты</th><th>34</th><th>43</th><th>170</th><th>340</th><th>850</th><th>3.400</th><th>17.000</th></tr><tr><th>Броня</th><th>300</th><th>300</th><th>1.200</th><th>5.250</th><th>1.200</th><th>3.000</th><th>15.000</th></tr></table></center></th></th></tr></table>

    // Раунды.
    foreach ( $res['rounds'] as $i=>$round)
    {
        $text .= "<br><center>Атакующий флот делает: ".nicenum($round['ashoot'])." выстрела(ов) общей мощностью ".nicenum($round['apower'])." по обороняющемуся. Щиты обороняющегося поглощают ".nicenum($round['dabsorb'])." мощности выстрелов";
        $text .= "<br>Обороняющийся флот делает ".nicenum($round['dshoot'])." выстрела(ов) общей мощностью ".nicenum($round['dpower'])." выстрела(ов) по атакующему. Щиты атакующего поглощают ".nicenum($round['aabsorb'])." мощности выстрелов</center>";
    }

//<table border=1 width=100%><tr><th><br><center>Флот атакующего Andorianin (<a href=# onclick=showGalaxy(1,260,4); >[1:260:4]</a>)<table border=1><tr><th>Тип</th><th>Б. трансп.</th><th>Бомб.</th><th>Лин. Кр.</th></tr><tr><th>Кол-во.</th><th>20</th><th>40</th><th>100</th></tr><tr><th>Воор.:</th><th>11</th><th>2.200</th><th>1.540</th></tr><tr><th>Щиты</th><th>53</th><th>1.050</th><th>840</th></tr><tr><th>Броня</th><th>2.520</th><th>15.750</th><th>14.700</th></tr></table></center></th></tr></table><table border=1 width=100%><tr><th><br><center>Обороняющийся big303 (<a href=# onclick=showGalaxy(1,182,11); >[1:182:11]</a>)<table border=1><tr><th>Тип</th><th>РУ</th><th>Лёг. лазер</th><th>Тяж. лазер</th><th>М. купол</th><th>Б. купол</th></tr><tr><th>Кол-во.</th><th>10</th><th>12</th><th>8</th><th>1</th><th>1</th></tr><tr><th>Воор.:</th><th>120</th><th>150</th><th>375</th><th>2</th><th>2</th></tr><tr><th>Щиты</th><th>34</th><th>43</th><th>170</th><th>3.400</th><th>17.000</th></tr><tr><th>Броня</th><th>300</th><th>300</th><th>1.200</th><th>3.000</th><th>15.000</th></tr></table></center></th></th></tr></table>

//<br><center>Атакующий флот делает: 450 выстрела(ов) общей мощностью 880.220 по обороняющемуся. Щиты обороняющегося поглощают 23.939 мощности выстрелов
//<br>Обороняющийся флот делает 32 выстрела(ов) общей мощностью 6.002 выстрела(ов) по атакующему. Щиты атакующего поглощают 5.044 мощности выстрелов</center>
//<table border=1 width=100%><tr><th><br><center>Флот атакующего Andorianin (<a href=# onclick=showGalaxy(1,260,4); >[1:260:4]</a>)<table border=1><tr><th>Тип</th><th>Б. трансп.</th><th>Бомб.</th><th>Лин. Кр.</th></tr><tr><th>Кол-во.</th><th>20</th><th>40</th><th>100</th></tr><tr><th>Воор.:</th><th>11</th><th>2.200</th><th>1.540</th></tr><tr><th>Щиты</th><th>53</th><th>1.050</th><th>840</th></tr><tr><th>Броня</th><th>2.520</th><th>15.750</th><th>14.700</th></tr></table></center></th></tr></table><table border=1 width=100%><tr><th><br><center>Обороняющийся big303 (<a href=# onclick=showGalaxy(1,182,11); >[1:182:11]</a>)<table border=1><tr><th>Тип</th><th>Б. купол</th></tr><tr><th>Кол-во.</th><th>1</th></tr><tr><th>Воор.:</th><th>2</th></tr><tr><th>Щиты</th><th>17.000</th></tr><tr><th>Броня</th><th>15.000</th></tr></table></center></th></th></tr></table>

//<br><center>Атакующий флот делает: 160 выстрела(ов) общей мощностью 242.220 по обороняющемуся. Щиты обороняющегося поглощают 18.500 мощности выстрелов
//<br>Обороняющийся флот делает 1 выстрела(ов) общей мощностью 1 выстрела(ов) по атакующему. Щиты атакующего поглощают 1 мощности выстрелов</center>
//<table border=1 width=100%><tr><th><br><center>Флот атакующего Andorianin (<a href=# onclick=showGalaxy(1,260,4); >[1:260:4]</a>)<table border=1><tr><th>Тип</th><th>Б. трансп.</th><th>Бомб.</th><th>Лин. Кр.</th></tr><tr><th>Кол-во.</th><th>20</th><th>40</th><th>100</th></tr><tr><th>Воор.:</th><th>11</th><th>2.200</th><th>1.540</th></tr><tr><th>Щиты</th><th>53</th><th>1.050</th><th>840</th></tr><tr><th>Броня</th><th>2.520</th><th>15.750</th><th>14.700</th></tr></table></center></th></tr></table><table border=1 width=100%><tr><th><br><center>Обороняющийся big303 (<a href=# onclick=showGalaxy(1,182,11); >[1:182:11]</a>)<br>уничтожен</center></th></th></tr></table>

    // Результаты боя.
//<!--A:167658,W:167658-->
    $text .= "<p> Атакующий выиграл битву!<br>Он получает<br>".nicenum($cm)." металла, ".nicenum($ck)." кристалла и ".nicenum($cd)." дейтерия.<br>";
    $text .= "<p><br>Атакующий потерял ".nicenum($aloss)." единиц.<br>Обороняющийся потерял ".nicenum($dloss)." единиц.<br>";
    $text .= "Теперь на этих пространственных координатах находится ".nicenum($res['dm'])." металла и ".nicenum($res['dk'])." кристалла.";

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

    // Список обороняющихся
    $p = GetPlanet ( $planet_id );
    $d[0] = LoadUser ( $p['owner_id'] );
    $d[0]['fleet'] = array ();
    $d[0]['defense'] = array ();
    foreach ($fleetmap as $i=>$gid) $d[0]['fleet'][$gid] = $p["f$gid"];
    foreach ($defmap as $i=>$gid) $d[0]['defense'][$gid] = $p["d$gid"];

    $source .= "Rapidfire = $rf\n";
    $source .= "FID = $fid\n";
    $source .= "DID = $did\n";

    $source .= "Attackers = 1\n";
    $source .= "Defenders = 1\n";

    $source .= "Attacker0 = (".$f['fleet_id']." ";
    $source .= $a[0]['r109'] . " " . $a[0]['r110'] . " " . $a[0]['r111'] . " ";
    foreach ($fleetmap as $i=>$gid) $source .= $a[0]['fleet'][$gid] . " ";
    $source .= ")\n";
    $source .= "Defender0 = ($planet_id ";
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

    // Сгенерировать боевой доклад.
    $text = BattleReport ( $a, $d, $res, time(), 1234, 5678, 1, 2, 3, 1 );

    // Разослать сообщения
    foreach ( $a as $i=>$user )        // Атакующие
    {
        $bericht = SendMessage ( $user['player_id'], "Командование флотом", "Боевой доклад", $text, 6 );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">Боевой доклад [1:10:13] (A:5.000)</span></a>";
        SendMessage ( $user['player_id'], "Командование флотом", $subj, "", 2 );
    }

    foreach ( $d as $i=>$user )        // Обороняющиеся
    {
        $bericht = SendMessage ( $user['player_id'], "Командование флотом", "Боевой доклад", $text, 6 );
        MarkMessage ( $user['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$d_result[$battle_result]."\">Боевой доклад [1:10:13] (A:5.000)</span></a>";
        SendMessage ( $user['player_id'], "Командование флотом", $subj, "", 2 );
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

    SendMessage ( $target_user['player_id'], "Командование флотом", "Ракетная атака", $text, 2);
}

?>