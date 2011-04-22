<?php

// Боевой движок OGame.

// Начать битву между атакующим fleet_id и обороняющимся planet_id.
function StartBattle ( $fleet_id, $planet_id )
{
    global  $db_host, $db_user, $db_pass, $db_name, $db_prefix;
    $a = array ();
    $d = array ();

    $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );
    $d_result = array ( 1=>"combatreport_igotattacked_iwon", 0=>"combatreport_igotattacked_ilost", 2=>"combatreport_igotattacked_draw" );

    $unitab = LoadUniverse ();
    $fid = $unitab['fid'];
    $did = $unitab['did'];
    $rf = $unitab['rapid'];

    $arg = "\"db_host=$db_host&db_user=$db_user&db_pass=$db_pass&db_name=$db_name&db_prefix=$db_prefix&fleet_id=$fleet_id&planet_id=$planet_id&fid=$fid&did=$did&rf=$rf\"";
    ob_end_flush();
    ob_start ();
    $text = system ( "../cgi-bin/battle $arg" );
    ob_end_clean ();
    ob_start ();

    $text = va ( $text, "Дата/Время:", "Произошёл бой между следующими флотами:", "Тип", "Кол-во.", "Воор.:", "Щиты", "Броня", "уничтожен" );

    // Список атакующих
    $f = LoadFleet ( $fleet_id );
    $a[0] = LoadUser ( $f['owner_id'] );

    // Список обороняющихся
    $p = GetPlanet ( $planet_id );
    $d[0] = LoadUser ( $p['owner_id'] );

    // Определить исход битвы.
    $battle_result = 2;

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