<?php

// Управление ботами.

require_once "botapi.php";        // API

// Глобальные переменные бота.
$BotID = 0;        // номер текущего бота
$BotNow = 0;       // время начала выполнения задания бота

// Добавить блок в очередь
function AddBotQueue ($player_id, $strat_id, $block_id, $when, $seconds)
{
    $queue = array ( '', $player_id, 'AI', $strat_id, $block_id, 0, $when, $when+$seconds, 1000 );
    return AddDBRow ( $queue, 'queue' );
}

// Интерпретатор блоков
function ExecuteBlock ($queue, $block, $childs )
{
    global $db_prefix, $BotID, $BotNow;

    $BotNow = $queue['end'];
    $BotID = $queue['owner_id'];
    $strat_id = $queue['sub_id'];

#    Debug ( "Bot trace : " . $block['category'] . "(".$block['key']."): " . $block['text'] );

    switch ( $block['category'] )
    {
        case "Start":
            $block_id = $childs[0]['to'];
            AddBotQueue ( $BotID, $strat_id, $block_id, $BotNow, 0 );
            RemoveQueue ( $queue['task_id'] );
            break;

        case "End":
            RemoveQueue ( $queue['task_id'] );    // Просто удалить блок, тем самым в очереди не остается ни одного задания AI исполняемой стратегии
            break;

        case "Label":     // Начать выполнение новой цепочки
            // Выбрать из всех потомков тот, который выходит снизу блока (fromPort="B")
            $block_id = $childs[0]['to'];
            foreach ( $childs as $i=>$child ) {
                if ( $child['fromPort'] === "B" ) {
                    $block_id = $child['to'];
                    break;
                }            
            }
            AddBotQueue ( $BotID, $strat_id, $block_id, $BotNow, 0 );
            RemoveQueue ( $queue['task_id'] );
            break;

        case "Branch":    // Переход на другую метку с указанным текстом.
            $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = $strat_id LIMIT 1";
            $result = dbquery ($query);
            if ($result) {
                $row = dbarray ($result);
                $strat = json_decode ( $row['source'], true );
                $done = false;
                foreach ( $strat['nodeDataArray'] as $i=>$arr ) {
                    if ( $arr['text'] === $block['text'] && $arr['category'] === "Label" ) {
                        AddBotQueue ( $BotID, $strat_id, $arr['key'], $BotNow, 0 );
                        $done = true;
                        break;
                    }
                }
                if (!$done) Debug ( "Не удалось найти метку перехода \"".$block['text']."\"" );
            }
            else Debug ( "Не удалось загрузить текущую стратегию при обработке перехода." );
            RemoveQueue ( $queue['task_id'] );
            break;

        case "Cond":        // Проверка условия
            $result = eval ( "return ( " . $block['text'] . " );" );
            $block_id = $block_no = 0xdeadbeef;
            $prefix = "";
            foreach ( $childs as $i=>$child ) {
                if ( strtolower ($child['text']) === "no" ) {
                    if ( $result == false ) {
#                        Debug ($block['text'] . " : ".$prefix."NO");
                        $block_id = $child['to']; break;
                    }
                    else $block_no = $child['to'];
                }
                if ( strtolower ($child['text']) === "yes" && $result == true ) {
#                    Debug ($block['text'] . " : YES");
                    $block_id = $child['to']; break;
                }
                if ( preg_match('/([0-9]{1,2}|100)%/', $child['text'], $matches) && $result == true ) {    // случайный переход
                    $prc = str_replace ( "%", "", $matches[0]);
                    $roll = mt_rand (1, 100);
                    if ( $roll <= $prc ) {
#                        Debug ($block['text'] . " : PROBABLY($roll/$prc) YES");
                        $block_id = $child['to']; break;
                    }
                    else {
                        if ( $block_no == 0xdeadbeef ) {
                            $prefix = "PROBABLY($roll/$prc) ";
                            $result = false;
                        }
                        else {
#                            Debug ($block['text'] . " : PROBABLY($roll/$prc) NO");
                            $block_id = $block_no; break;
                        }
                    }
                }    // случайный переход
            }
            if ( $block_id != 0xdeadbeef ) AddBotQueue ( $BotID, $strat_id, $block_id, $BotNow, 0 );
            else Debug ( "Не удалось выбрать условный переход." );
            RemoveQueue ( $queue['task_id'] );
            break;

        default:    // Обычный блок (квадрат), выход один.
            $sleep = eval ( $block['text'] . ";" );
            if ( $sleep == NULL ) $sleep = 0;
            $block_id = $childs[0]['to'];
            AddBotQueue ( $BotID, $strat_id, $block_id, $BotNow, $sleep );
            RemoveQueue ( $queue['task_id'] );
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
        $player_id = CreateUser ( $name, $pass, '', true );
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
    global $BotID, $BotNow;

    $BotID = $player_id;
    $BotNow = time ();

    if ( BotExec("_start") == 0 ) Debug ( "Стартовая стратегия не найдена." );
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
// Активировать парсер заданий бота.
function Queue_Bot_End ($queue)
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = ".$queue['sub_id']." LIMIT 1";
    $result = dbquery ($query);
    if ($result) {
        $row = dbarray ($result);
        $strat = json_decode ( $row['source'], true );
        $strat_id = $row['id'];

        foreach ( $strat['nodeDataArray'] as $i=>$arr ) {
            if ( $arr['key'] == $queue['obj_id'] ) {
                $block = $arr;

                $childs = array ();
                foreach ( $strat['linkDataArray'] as $i=>$arr ) {
                    if ( $arr['from'] == $block['key'] ) $childs[] = $arr;
                }

                ExecuteBlock ($queue, $block, $childs );
                break;
            }
        }

    }
    else Debug ( "Не удалось загрузить программу " . $queue['sub_id'] );
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