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
}

?>