<?php

// ИНТЕЛЛЕКТ ВЕРСИЯ 1
/*
    Для начала напишем бота, который развивается до МТ, потом до колоника, занимает 9 планет, строит там оптимальную оборону и раз в сутки свозит ресы на главку.
    На главке должна быть неплохая оборона.
    Сейв основного флота из БТ осуществляется с главной планеты на рандомную колонию с отзывом в случайное время.
    Распределение ресурсов с главной планеты через двустороннее Оставить, с отзывом в случае атаки.
    Стратегия захвата планет: по 3 планеты в 1, 2 и 3 галах на рандомных координатах. Количество полей > 200.
    Максимальный предел уровня шахт на всех планетах 32-30-30. По достижении этого значения ресы с планеты просто свозятся на главку.
*/

// Значения записей в таблице queue:
// sub_id: ID планеты
// obj_id: тип стратегии
// level: 

// Билд до МТ:
// С1 М1 М2 С2 М3 М4 С3 М5 К1 С4 К2 М6 К3 С5 К4 Д1 С6 Д2 Д3 С7 Д4 М7 С8 К5 ИЛ1 Энерго1 Реактив1 ФР1 Реактив2 ФР2 Верфь1 Верфь2 МТ ...
// Билд до колоника:
// ... К6 С9 К7 К8 К9 С10 К10 Д5 ИЛ2 ИЛ3 Шпионаж1 Шпионаж2 Шпионаж3 Импульс1 Верфь3 Импульс2 Верфь4 Импульс3 Колонизатор

// У каждой планеты бота есть своя "стратегия" развития. Тип стратегии выбирается в зависимости от развитости технологий бота и его империи.
// Возможные типы стратегий:
//    0: Бездействие
//    1: Развитие до МТ
//    2: Развитие до колоника
//    3: Экспансивная стратегия (когда количество колоний < 9) - расширение империи
//    4: Экстенсивная стратегия (когда количество колоний = max.) - увеличение уровня шахт.

// Вернуть описание стратегии бота.
function GetBotStrategy_Rev1 ($n)
{
    switch ($n)
    {
        case 1: return "Развитие до МТ";
        case 2: return "Развитие до колоника";
        case 3: return "Экспансивная стратегия";
        case 4: return "Экстенсивная стратегия";
        default: return "Неизвестная стратегия";
    }
}

// Запустить бота. Для каждой планеты бота выбирается оптимальная стратегия развития.
function StartBot_Rev1 ($player_id)
{
    $user = LoadUser ($player_id);

    // Выбор стратегии.

    AddQueue ( $player_id, 'AI', $user['hplanetid'], 1, 0, time(), 10 );    // развитие до МТ на главке
    
    Debug ( "BOT ".$user['oname']." STARTED" );
}

// -------------------------------------------------------------------------------------------------------------
// Обработчики стратегий.

function is_done ( $obj_id, $level, $user, $planet )
{
    if ( $obj_id < 100 ) return $planet["b".$obj_id] >= $level;
    else if ( $obj_id >= 100 && $obj_id < 200 ) return $user["r".$obj_id] >= $level;
    else if ( $obj_id >= 200 && $obj_id < 300 ) return $planet["f".$obj_id] >= $level;
    else if ( $obj_id >= 400 ) return $planet["d".$obj_id] >= $level;
}

// Проверить хватает ли ресурсов.
// Если не хватает - вернуть время задержки.
function enough_res ( $obj_id, $level, $planet, &$delay )
{
    global $GlobalUni;

    $m = $k = $d = $e = 0;
    if ( $obj_id < 100 ) {
        BuildPrice ( $obj_id, $level, &$m, &$k, &$d, &$e );
    }
    else if ( $obj_id >= 100 && $obj_id < 200 ) {
        ResearchPrice ( $obj_id, $level, &$m, &$k, &$d, &$e );
    }
    else if ( $obj_id >= 200 ) {
        ShipyardPrice ( $obj_id, &$m, &$k, &$d, &$e );
        $m *= $level;
        $k *= $level;
        $d *= $level;
    }

    if ( IsEnoughResources ( $planet, $m, $k, $d, $e ) ) return true;
    else
    {
        $g_factor = 1.0;
        $speed = $GlobalUni['speed'];

        $random_delta = mt_rand ( 2*60, 15*60);

        $m_hourly = prod_metal ($planet['b1'], $planet['mprod']) * $planet['factor'] * $speed * $g_factor + 20 * $speed;        // Металл
        $k_hourly = prod_crys ($planet['b2'], $planet['kprod']) * $planet['factor'] * $speed * $g_factor + 10 * $speed;        // Кристалл
        $d_hourly = prod_deut ($planet['b3'], $planet['temp']+40, $planet['dprod']) * $planet['factor'] * $speed * $g_factor;    // Дейтерий
        $d_hourly -= cons_fusion ( $planet['b12'], $planet['fprod'] ) * $speed;	// термояд

        $delay = 0 + $random_delta;

        return false;
    }
}

// Вернуть длительность задания или 0, в случае ошибки.
function build ( $obj_id, $level, $user, $planet, $queue )
{
    global $GlobalUni;
    $speed = $GlobalUni['speed'];
    $robots = $planet['b14'];
    $nanits = $planet['b15'];

    if ( $obj_id < 100 ) {
        if ( BuildMeetRequirement ( $user, $planet, $obj_id ) )
        {
            BuildEnque ( $planet['planet_id'], $obj_id, 0, $queue['end'] );
            UpdatePlanetActivity ( $planet['planet_id'], $queue['end'] );
            return BuildDuration ( $obj_id, $level, $robots, $nanits, $speed ) + 1;
        }
        else return 0;
    }
    else if ( $obj_id >= 100 && $obj_id < 200 ) {
        if ( ResearchMeetRequirement ( $user, $planet, $obj_id ) )
        {
            StartResearch ( $user['player_id'], $planet['planet_id'], $obj_id, $queue['end'] );
            UpdatePlanetActivity ( $planet['planet_id'], $queue['end'] );
            return 10;
        }
        else return 0;
    }
    else if ( $obj_id >= 200 ) {
        if ( ShipyardMeetRequirement ( $user, $planet, $obj_id ) )
        {
            AddShipyard ($user['player_id'], $planet['planet_id'], $obj_id, $level, $queue['end'] );
            UpdatePlanetActivity ( $planet['planet_id'], $queue['end'] );
            return 10;
        }
        else return 0;
    }
}

function Think_SmallCargo ($queue)
{
    $user = LoadUser ($queue['owner_id']);
    $planet = GetPlanet ($queue['sub_id']);

    ProdResources ( &$planet, $planet['lastpeek'], $queue['end'] );

    $SmallCargoQueue = array (
        array (4=>1), array (1=>1), array (1=>2), array (4=>2), array (1=>3), array (1=>4), array (4=>3), array (1=>5), array (2=>1), array (4=>4), 
        array (2=>2), array (1=>6), array (2=>3), array (4=>5), array (2=>4), array (3=>1), array (4=>6), array (3=>2), array (3=>3), array (4=>7), 
        array (3=>4), array (1=>7), array (4=>8), array (2=>5), array (31=>1), 
        array (113=>1), array (115=>1), array (14=>1), array (115=>2), array (14=>2), array (21=>1), array (21=>2), array (202=>1), 
    );

    foreach ( $SmallCargoQueue as $i=>$step )
    {
        foreach ( $step as $obj_id=>$level)
        {
            if ( ! is_done ($obj_id, $level, $user, $planet) )
            {
                // Проверить хватает ли ресурсов. Если не хватает - подождать.
                $delay = 0;
                if ( ! enough_res ( $obj_id, $level, $planet, &$delay ) )
                {
                    Debug ( "Не хватает ресурсов в стратегии Малый Транспорт (".loca("NAME_".$obj_id)." ур. ".$level.")" );
                    ProlongQueue ( $queue['task_id'], $delay );
                    return;
                }

                // Ресурсы есть - строим постройку.
                $duration = build ( $obj_id, $level, $user, $planet, $queue );
                if ( $duration == 0 )
                {
                    Debug ( "Ошибка очереди заданий бота в стратегии Малый Транспорт (".loca("NAME_".$obj_id)." ур. ".$level.")" );
                    RemoveQueue ($queue['task_id'], 0);
                    return;
                }

                // Ждём когда достроится.
                ProlongQueue ( $queue['task_id'], $duration );
                return;
            }
        }
    }

    RemoveQueue ($queue['task_id'], 0);
}

// -------------------------------------------------------------------------------------------------------------

function Queue_Bot_End_Rev1 ($queue)
{
    UpdateLastClick ( $queue['owner_id'] );

    switch ( $queue['obj_id'] )
    {
        case 1:
            Think_SmallCargo ($queue);
            break;

        default:
            RemoveQueue ($queue['task_id'], 0);
    }
}

?>