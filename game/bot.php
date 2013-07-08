<?php

// Управление ботами.

// Удалить глобальное задание и связанные с ним задания бота
function RemoveBotQueue ($task_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."botqueue WHERE owner_id = " . $task_id;
    dbquery ($query);
    RemoveQueue ($task_id);
}

// Добавить блок в очередь
function AddBotQueue ($player_id, $strat_id, $block_id, $when, $seconds)
{
    global $db_prefix;
    $queue = array ( '', $player_id, 'AI', 0, 0, 0, $when, $when+$seconds, 1000 );
    $queue_id = AddDBRow ( $queue, 'queue' );
    $botqueue  = array ( '', $queue_id, $strat_id, $block_id );
    $botqueue_id = AddDBRow ( $botqueue, 'botqueue' );
    $query = "UPDATE ".$db_prefix."queue SET sub_id = $botqueue_id WHERE task_id = $queue_id;";
    dbquery ($query);
}

// Интерпретатор блоков
function ExecuteBlock ($queue, $botqueue, $block, $childs )
{
    global $db_prefix;

    $player_id = $queue['owner_id'];
    $strat_id = $botqueue['strat_id'];

    switch ( $block['category'] )
    {
        case "Start":
            $block_id = $childs[0]['to'];
            AddBotQueue ( $player_id, $strat_id, $block_id, $queue['end'], 0 );
            RemoveBotQueue ( $queue['task_id'] );
            break;

#        case "End":
#            break;

        default:
            Debug ( "Неизвестный блок : " . $block['category'] . ", текст: \"" . $block['text'] . "\", стратегия $strat_id" );
            RemoveBotQueue ( $queue['task_id'] );
            break;
    }
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
        SetVar ( $player_id, 'password', $pass );
        return true;
    }
    else return false;
}

// Запустить бота (выполнить блок Start для стратегии _start)
function StartBot ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE name = '_start' LIMIT 1";
    $result = dbquery ($query);
    if ($result) {
        $row = dbarray ($result);
        $strat = json_decode ( $row['source'], true );
        $strat_id = $row['id'];

        foreach ( $strat['nodeDataArray'] as $i=>$arr ) {
            if ( $arr['category'] === "Start" ) {
                AddBotQueue ( $player_id, $strat_id, $arr['key'], time(), 0 );
                break;
            }
        }
    }
    else Debug ( "Стартовая стратегия не найдена." );
}

// Остановить бота (просто удалить все задания AI)
function StopBot ($player_id)
{
    global $db_prefix;
    if ( IsBot ($player_id) ) 
    {
        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'AI' AND owner_id = $player_id";
        $result = dbquery ( $query );
        while ( $row = dbarray ($result) ) {
            $query = "DELETE FROM ".$db_prefix."botqueue WHERE owner_id = " . $row['task_id'];
            dbquery ($query);
        }
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
// Активировать парсер заданий бота.
function Queue_Bot_End ($queue)
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."botqueue WHERE id = " . $queue['sub_id'];
    $result = dbquery ($query);
    $botqueue = dbarray ($result);

    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = ".$botqueue['strat_id']." LIMIT 1";
    $result = dbquery ($query);
    if ($result) {
        $row = dbarray ($result);
        $strat = json_decode ( $row['source'], true );
        $strat_id = $row['id'];

        foreach ( $strat['nodeDataArray'] as $i=>$arr ) {
            if ( $arr['key'] == $botqueue['block_id'] ) {
                $block = $arr;

                $childs = array ();
                foreach ( $strat['linkDataArray'] as $i=>$arr ) {
                    if ( $arr['from'] == $block['key'] ) $childs[] = $arr;
                }

                ExecuteBlock ($queue, $botqueue, $block, $childs );
                break;
            }
        }

    }
    else Debug ( "Не удалось загрузить программу " . $botqueue['strat_id'] );

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