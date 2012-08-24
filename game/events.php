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

// Вернуть описание специального события для админки, или null, если это не специальное событие
function SpecialEventDescription ($type)
{
    switch ( $type )
    {
        case "WipeUniverse": return "Вайп вселенной";
        case "GlobalAttackBan": return "Фаза подготовки активна";
        case "GlobalAttackUnban": return "Фаза боевых действий активна";
    }
    return null;
}

// -----------------------------------------------------------------------------------------------------
// Обработчики специальных событий

// Список специальных событий:
//     "WipeUniverse"    -- вайп вселенной

// Вернуть 0, если событие не специальное.
function SpecialEvent ($queue )
{
    if ( $queue['type'] === "WipeUniverse" ) { Queue_WipeUniverse_End ($queue); return 1; }
    else if ( $queue['type'] === "GlobalAttackBan" ) { Queue_GlobalAttackBan_End ($queue); return 1; }
    else if ( $queue['type'] === "GlobalAttackUnban" ) { Queue_GlobalAttackUnban_End ($queue); return 1; }
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
        $when = mktime(10, 0, 0, date("m"), date("d")+7, date("y"));
        $queue = array ( null, 99999, "WipeUniverse", 0, 0, 0, $now, $when, 1000 );
        AddDBRow ( $queue, "queue" );
        
        // Включить глобальную блокировку атак на 5 часов.
        $when = $now + 5*60*60;
        $queue = array ( null, 99999, "GlobalAttackBan", 0, 0, 0, $now, $when, 1000 );
        AddDBRow ( $queue, "queue" );
    }
}
// Обработчик вайпа вселенной.
function Queue_WipeUniverse_End ($queue)
{
    WipeUniverse ();
}
// Обработчик установки глобального блока атак.
function Queue_GlobalAttackBan_End ($queue)
{
    RemoveQueue ($queue['task_id'], 0);

    // Отключить глобальную блокировку атак на 5 часов.
    $when = $queue['end'] + 5*60*60;
    $queue = array ( null, 99999, "GlobalAttackUnban", 0, 0, 0, $now, $when, 1000 );
    AddDBRow ( $queue, "queue" );
}
// Обработчик снятия глобального блока атак.
function Queue_GlobalAttackUnban_End ($queue)
{
    RemoveQueue ($queue['task_id'], 0);

    // Включить глобальную блокировку атак на 5 часов.
    $when = $queue['end'] + 5*60*60;
    $queue = array ( null, 99999, "GlobalAttackBan", 0, 0, 0, $now, $when, 1000 );
    AddDBRow ( $queue, "queue" );
}

?>