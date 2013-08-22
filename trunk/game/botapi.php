<?php

// Интерфейс взаимодействия ботов с движком.
// Тут находятся все встроенные функции.

//------------------------------------------------------------------------------------
// Вспомогательные функции

// Ничего не делать
function BotIdle ()
{
}

// Параллельно запустить новую стратегию бота. Вернуть 1, если ОК или 0, если не удалось запустить стратегию.
function BotExec ($name)
{
    global $db_prefix, $BotID, $BotNow;
    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE name = '".$name."' LIMIT 1";
    $result = dbquery ($query);
    if ($result) {
        $row = dbarray ($result);
        $strat = json_decode ( $row['source'], true );
        $strat_id = $row['id'];

        foreach ( $strat['nodeDataArray'] as $i=>$arr ) {
            if ( $arr['category'] === "Start" ) {
                AddBotQueue ( $BotID, $strat_id, $arr['key'], $BotNow, 0 );
                return 1;
            }
        }
        return 0;
    }
    else return 0;
}

// Переменные бота​.

function BotGetVar ( $var, $def_value=null )
{
    global $BotID, $BotNow;
    return GetVar ( $BotID, $var, $def_value);
}

function BotSetVar ( $var, $value )
{
    global $BotID, $BotNow;
    SetVar ( $BotID, $var, $value );
}

//------------------------------------------------------------------------------------
// Строительство/снос построек, управление Сырьём

// Проверить - можем ли мы строить указанную постройку на активной планете (1-да, 0-нет)
function BotCanBuild ($obj_id)
{
    global $BotID, $BotNow;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $BotNow );
    $level = $aktplanet['b'.$obj_id] + 1;
    $text = CanBuild ( $user, $aktplanet, $obj_id, $level, 0 );
    return ( $text === '' );
}

// Начать постройку на активной планете.
// Вернуть 0, если недостаточно условий или ресурсов для начала постройки. Вернуть количество секунд, которые нужно подождать пока завершится строительство.
function BotBuild ($obj_id)
{
    global $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $level = $aktplanet['b'.$obj_id] + 1;
    $text = CanBuild ( $user, $aktplanet, $obj_id, $level, 0 );
    if ( $text === '' ) {
        $speed = $GlobalUni['speed'];
        $duration = floor (BuildDuration ( $obj_id, $level, $aktplanet['b14'], $aktplanet['b15'], $speed ));
        BuildEnque ( $user['aktplanet'], $obj_id, 0, $BotNow);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        return $duration;
    }
    else return 0;
}

// Получить уровень постройки
function BotGetBuild ($n)
{
    global $BotID, $BotNow;
    $bot = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $bot['aktplanet'] );
    return $aktplanet['b'.$n];
}

// Установить выработку сырья на активной планете (числа в процентах 0-100)
function BotResourceSettings ( $last1=100, $last2=100, $last3=100, $last4=100, $last12=100, $last212=100 )
{
    global $db_prefix, $BotID, $BotNow;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );

    if ( $last1 < 0 ) $last1 = 0;        // Не должно быть < 0.
    if ( $last2 < 0 ) $last2 = 0;
    if ( $last3 < 0 ) $last3 = 0;
    if ( $last4 < 0 ) $last4 = 0;
    if ( $last12 < 0 ) $last12 = 0;
    if ( $last212 < 0 ) $last212 = 0;

    if ( $last1 > 100 ) $last1 = 100;        // Не должно быть > 100.
    if ( $last2 > 100 ) $last2 = 100;
    if ( $last3 > 100 ) $last3 = 100;
    if ( $last4 > 100 ) $last4 = 100;
    if ( $last12 > 100 ) $last12 = 100;
    if ( $last212 > 100 ) $last212 = 100;

    // Сделать кратно 10.
    $last1 = round ($last1 / 10) * 10 / 100;
    $last2 = round ($last2 / 10) * 10 / 100;
    $last3 = round ($last3 / 10) * 10 / 100;
    $last4 = round ($last4 / 10) * 10 / 100;
    $last12 = round ($last12 / 10) * 10 / 100;
    $last212 = round ($last212 / 10) * 10 / 100;

    $planet_id = $aktplanet['planet_id'];
    $query = "UPDATE ".$db_prefix."planets SET ";
    $query .= "mprod = $last1, ";
    $query .= "kprod = $last2, ";
    $query .= "dprod = $last3, ";
    $query .= "sprod = $last4, ";
    $query .= "fprod = $last12, ";
    $query .= "ssprod = $last212 ";
    $query .= " WHERE planet_id = $planet_id";
    dbquery ($query);

    UpdatePlanetActivity ( $planet_id, $BotNow );
}

//------------------------------------------------------------------------------------
// Строительство флота/обороны (Верфь)

function BotBuildFleet ($obj_id, $n)
{
    global $db_prefix, $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $text = AddShipyard ($user['player_id'], $user['aktplanet'], $obj_id, $n, 0 );
    if ( $text === '' ) {
        $speed = $GlobalUni['speed'];
        $now = ShipyardLatestTime ($aktplanet, $BotNow);
        $shipyard = $aktplanet["b21"];
        $nanits = $aktplanet["b15"];
        $seconds = ShipyardDuration ( $obj_id, $shipyard, $nanits, $speed );
        AddQueue ($user['player_id'], "Shipyard", $aktplanet['planet_id'], $obj_id, $n, $now, $seconds);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        return $seconds;
    }
    else return 0;
}

//------------------------------------------------------------------------------------
// Исследования

// Получить уровень исследования
function BotGetResearch ($n)
{
    global $BotID, $BotNow;
    $bot = LoadUser ($BotID);
    return $bot['r'.$n];
}

// Проверить - можем ли мы начать исследование на главной планете (1-да, 0-нет)
function BotCanResearch ($obj_id)
{
    global $BotID, $BotNow;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $BotNow );
    $level = $aktplanet['r'.$obj_id] + 1;
    $text = CanResearch ($user, $aktplanet, $obj_id, $level);
    return ($text === '' );
}

// Начать исследование на главной планете.
// Вернуть 0, если недостаточно условий или ресурсов для начала исследования. Вернуть количество секунд, которые нужно подождать пока завершится исследование.
function BotResearch ($obj_id)
{
    global $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    $aktplanet = GetPlanet ( $user['aktplanet'] );
    $level = $aktplanet['r'.$obj_id] + 1;
    $text = StartResearch ($user[player_id], $user[aktplanet], $obj_id, 0);
    if ( $text === '' ) {
        $speed = $uni['speed'];
        if ($now == 0) $now = time ();
        $reslab = ResearchNetwork ( $user['planet_id'], $obj_id );
        $prem = PremiumStatus ($user);
        if ( $prem['technocrat'] ) $r_factor = 1.1;
        else $r_factor = 1.0;
        $seconds = ResearchDuration ( $obj_id, $level, $reslab, $speed * $r_factor);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        return $seconds;
    }
    else return 0;
}

?>