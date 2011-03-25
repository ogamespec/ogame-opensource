<?php

// Боевой движок OGame.

// Начать битву между атакующим fleet_id и обороняющимся planet_id.
function StartBattle ( $fleet_id, $planet_id )
{
    global  $db_host, $db_user, $db_pass, $db_name, $db_prefix;

    $unitab = LoadUniverse ();
    $fid = $unitab['fid'];
    $did = $unitab['did'];
    $rf = $unitab['rapid'];

    $arg = "\"db_host=$db_host&db_user=$db_user&db_pass=$db_pass&db_name=$db_name&db_prefix=$db_prefix&fleet_id=$fleet_id&planet_id=$planet_id&fid=$fid&did=$did&rf=$rf\"";
    ob_end_flush();
    ob_start ();
    $text = system ( "battle.exe $arg" );
    ob_end_clean ();
    ob_start ();

    $text = va ( $text, "Дата/Время:", "Произошёл бой между следующими флотами:", "Тип", "Кол-во.", "Воор.:", "Щиты", "Броня", "уничтожен" );

    echo "$text";
    die ();
}

// Ракетная атака.
function RocketAttack ( $fleet_id, $planet_id )
{
}

?>