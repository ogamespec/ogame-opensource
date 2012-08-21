<?php

// Специальные игровые события.

// Вызывается при логине любого игрока. Позволяет добавлять глобальные игровые события.
function SpecialEventsLoginCallback ()
{
    AddWipeUniverseEvent ();
}

// Вызывается после загрузке данных планеты, для изменения её параметров.
function SpecialEventsGetPlanetCallback ( &$planet )
{
}

// Вызывается перед отправкой флота, для изменения его параметров.
function SpecialEventsDispatchFleetCallback ( &$fleet_obj )
{
}

// -----------------------------------------------------------------------------------------------------
// Обработчики специальных событий

// Список специальных событий:
//     "WipeUniverse"    -- вайп вселенной

// Вернуть 0, если событие не специальное.
function SpecialEvent ($queue )
{
    if ( $queue['type'] === "WipeUniverse" ) Queue_WipeUniverse_End ($queue);
    else return 0;
}

// Добавить задание вайпа вселенной, если такого ещё нет (для "очень" особенных вселенных)
// Вызывается при логине любого игрока.
function AddWipeUniverseEvent ()
{
    global $db_prefix;

    $uni = LoadUniverse ();
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'WipeUniverse' ";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 && $uni['special'] )
    {
        $now = time ();
        $when = mktime(20, 0, 0, date("m"), date("d")+1, date("y"));
        $queue = array ( null, 99999, "WipeUniverse", 0, 0, 0, $now, $when, 1000 );
        AddDBRow ( $queue, "queue" );        
    }
}
// Обработчик вайпа вселенной.
function Queue_WipeUniverse_End ($queue)
{
    WipeUniverse ();
}

?>