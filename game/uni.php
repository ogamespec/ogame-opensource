<?php

// Управление параметрами вселенной.

// Загрузить Вселенную.
function LoadUniverse ()
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."uni;";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Обновить новости.
function UpdateNews ($news1, $news2, $days)
{
    global $db_prefix;
    $until = time () + $days * 24 * 60 * 60;
    $query = "UPDATE ".$db_prefix."uni SET news1 = '".$news1."', news2 = '".$news2."', news_until = $until";
    dbquery ($query);
}

// Убрать новости.
function DisableNews ()
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."uni SET news_until = 0";
    dbquery ($query);
}

// Установить параметры вселенной (все одновременно)
function SetUniParam ($speed, $fspeed, $acs, $fid, $did, $defrepair, $defrepair_delta, $galaxies, $systems, $rapid, $moons, $special)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."uni SET speed=$speed, fspeed=$fspeed, acs=$acs, fid=$fid, did=$did, defrepair=$defrepair, defrepair_delta=$defrepair_delta, galaxies=$galaxies, systems=$systems, rapid=$rapid, moons=$moons, special=$special";
    dbquery ($query);
}

// Вайпнуть вселенную.
function WipeUniverse ()
{
    global $db_prefix;

    $uni = LoadUniverse ();
    if ( !$uni['special'] ) return;    // Защита от дурака.

    // Отгрузить всех игроков
    UnloadAll ();

    // Удалить все игровые объекты
    dbquery ( "DELETE FROM ".$db_prefix."botvars;" );
    dbquery ( "DELETE FROM ".$db_prefix."template;" );
    dbquery ( "DELETE FROM ".$db_prefix."pranger;" );
    dbquery ( "DELETE FROM ".$db_prefix."battledata;" );
    dbquery ( "DELETE FROM ".$db_prefix."union;" );
    dbquery ( "DELETE FROM ".$db_prefix."fleet;" );
    dbquery ( "DELETE FROM ".$db_prefix."queue;" );
    dbquery ( "DELETE FROM ".$db_prefix."notes;" );
    dbquery ( "DELETE FROM ".$db_prefix."messages;" );
    dbquery ( "DELETE FROM ".$db_prefix."buddy;" );
    dbquery ( "DELETE FROM ".$db_prefix."allyapps;" );
    dbquery ( "DELETE FROM ".$db_prefix."allyranks;" );
    dbquery ( "DELETE FROM ".$db_prefix."ally;" );

    // Удалить все планеты, кроме Легора и space
    $query = "DELETE FROM ".$db_prefix."planets WHERE owner_id >= 100000;";
    dbquery ( $query );

    // Удалить всех игроков, кроме Легора и space
    $query = "DELETE FROM ".$db_prefix."users WHERE player_id >= 100000;";
    dbquery ( $query );
}

?>