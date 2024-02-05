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
function SetUniParam ($speed, $fspeed, $acs, $fid, $did, $defrepair, $defrepair_delta, $galaxies, $systems, $rapid, $moons, $freeze, $lang, $battle_engine, $php_battle)
{
    global $db_prefix;
    global $GlobalUni;
    
    $query = "UPDATE ".$db_prefix."uni SET lang='".$lang."', battle_engine='".$battle_engine."', freeze=$freeze, speed=$speed, fspeed=$fspeed, acs=$acs, fid=$fid, did=$did, defrepair=$defrepair, defrepair_delta=$defrepair_delta, galaxies=$galaxies, systems=$systems, rapid=$rapid, moons=$moons, php_battle=$php_battle";
    dbquery ($query);

    $GlobalUni = LoadUniverse ();
}

// Установить внешние ссылки для пунктов меню: Форум, Дискорд (новое, т.к. форумный формат общения становится всё менее актуальным), Туториал, Правила, О нас.
// Пустая строка скрывает пункт меню.
function SetExtLinks($ext_board, $ext_discord, $ext_tutorial, $ext_rules, $ext_impressum)
{
    global $db_prefix;
    global $GlobalUni;

    $query = "UPDATE ".$db_prefix."uni SET ext_board='".$ext_board."', ext_discord='".$ext_discord."', ext_tutorial='".$ext_tutorial."', ext_rules='".$ext_rules."', ext_impressum='".$ext_impressum."'";
    dbquery ($query);

    $GlobalUni = LoadUniverse ();
}

// Установить максимальное количество пользователей (администраторы и операторы не считаются)
function SetMaxUsers ($maxusers)
{
    global $db_prefix;
    global $GlobalUni;

    if ($maxusers > 0) {
        $query = "UPDATE ".$db_prefix."uni SET maxusers=$maxusers";
        dbquery ($query);

        $GlobalUni = LoadUniverse ();
    }
}

// Сбросить счётчик попыток взлома игры (вызывается во время релогина)
function ResetHackCounter ()
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."uni SET hacks = 0";
    dbquery ($query);  
}

// Инкрементировать счётчик попыток взлома игры.
function IncrementHackCounter ()
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."uni SET hacks = hacks + 1";
    dbquery ($query);  
}

?>