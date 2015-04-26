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
function SetUniParam ($speed, $fspeed, $acs, $fid, $did, $defrepair, $defrepair_delta, $galaxies, $systems, $rapid, $moons, $freeze, $lang)
{
    global $db_prefix;
    global $GlobalUni;
    
    $query = "UPDATE ".$db_prefix."uni SET lang='".$lang."', freeze=$freeze, speed=$speed, fspeed=$fspeed, acs=$acs, fid=$fid, did=$did, defrepair=$defrepair, defrepair_delta=$defrepair_delta, galaxies=$galaxies, systems=$systems, rapid=$rapid, moons=$moons";
    dbquery ($query);

    $GlobalUni = LoadUniverse ();
}

function ResetHackCounter ()
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."uni SET hacks = 0";
    dbquery ($query);  
}

function IncrementHackCounter ()
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."uni SET hacks = hacks + 1";
    dbquery ($query);  
}

?>