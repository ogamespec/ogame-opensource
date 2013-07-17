<?php

// Интерфейс взаимодействия ботов с движком.
// Тут находятся все встроенные функции.

//------------------------------------------------------------------------------------
// Вспомогательные функции

// Ничего не делать
function BotIdle ()
{
}

// Получить уровень исследования
function BotGetResearch ($n)
{
    global $BotID, $BotNow;
    $bot = LoadUser ($BotID);
    return $bot['r'.$n];
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

// Установить выработку сырья на активной планете (числа в процентах 0-100)
function BotResourceSettings ( $met, $crys, $deut, $solar, $sats, $fusion )
{
    global $BotID, $BotNow;
}

//------------------------------------------------------------------------------------
// Строительство флота/обороны (Верфь)

//------------------------------------------------------------------------------------
// Исследования

?>