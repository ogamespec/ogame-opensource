<?php

// Управление ботами.

$dummy_bot = false;

// Интеллект ботов - секретная инфорация.
if ( !$dummy_bot) require_once "bot_rev1.php";

// Вернуть описание стратегии бота.
function GetBotStrategy ($n)
{
    global $dummy_bot;
    if ( $dummy_bot ) return "";
    else return GetBotStrategy_Rev1 ($n);
}

// Добавить бота.
function AddBot ($name)
{
    global $db_prefix;

    // Сгенерировать пароль.
    $len = 8;
    $r = '';
    for($i=0; $i<$len; $i++)
        $r .= chr(rand(0, 25) + ord('a'));
    $pass = $r;

    if ( !IsUserExist ($name) ) {
        $player_id = CreateUser ( $name, $pass, '', 'en', true );
        $query = "UPDATE ".$db_prefix."users SET validatemd = '', validated = 1 WHERE player_id = " . $player_id;
        dbquery ($query);
        StartBot ( $player_id );
        SetVar ( $player_id, 'pass', $pass );
        return true;
    }
    else return false;
}

// Запустить бота. Для каждой планеты бота выбирается оптимальная стратегия развития.
function StartBot ($player_id)
{
    global $dummy_bot;
    if (!$dummy_bot)
    {
        if ( !IsBot ($player_id) ) StartBot_Rev1 ($player_id);
    }
}

// Остановить бота (просто удалить все задания AI)
function StopBot ($player_id)
{
    global $db_prefix;
    if ( IsBot ($player_id) ) 
    {
        $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'AI' AND owner_id = $player_id";
        dbquery ($query);
    }
}

// Проверить является ли игрок ботом.
function IsBot ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'AI' AND owner_id = $player_id";
    $result = dbquery ($query);
    return ( dbrows ($result) > 0 ) ;
}

// Событие завершения заданий для бота. Вызывается из queue.php
function Queue_Bot_End ($queue)
{
    if ( $dummy_bot ) RemoveQueue ($queue['task_id']);
    else Queue_Bot_End_Rev1 ($queue);
}

// Переменные бота​.

function GetVar ( $owner_id, $var, $def_value=null )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."botvars WHERE var = '".$var."' AND owner_id = $owner_id LIMIT 1;";
    $result = dbquery ($query);
    if ( dbrows ($result) > 0 ) {
        $var = dbarray ( $result );
        return $var['value'];
    }
    else
    {
        $var = array ( '', $owner_id, $var, $def_value );
        AddDBRow ( $var, 'botvars' );
        return $def_value;
    }
}

function SetVar ( $owner_id, $var, $value )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."botvars WHERE var = '".$var."' AND owner_id = $owner_id LIMIT 1;";
    $result = dbquery ($query);
    if ( dbrows ($result) > 0 ) {
        $query = "UPDATE ".$db_prefix."botvars SET value = '".$value."' WHERE var = '".$var."' AND owner_id = $owner_id;";
        dbquery ($query);
    }
    else
    {
        $var = array ( '', $owner_id, $var, $value );
        AddDBRow ( $var, 'botvars' );
    }
}

?>