<?php

// Управление ботами.

// Интеллект ботов - секретная инфорация.
//require_once "bot_rev1.php";

// Вернуть описание стратегии бота.
function GetBotStrategy ($n)
{
    //return GetBotStrategy_Rev1 ($n);
    return "";
}

// Запустить бота. Для каждой планеты бота выбирается оптимальная стратегия развития.
function StartBot ($player_id)
{
    //if ( !IsBot ($player_id) ) StartBot_Rev1 ($player_id);
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
    //Queue_Bot_End_Rev1 ($queue);
    RemoveQueue ($queue['task_id']);
}

?>