<?php

/*

Глобальная очередь событий.

Главный модуль для управления временным потоком игры.
Временной поток состоит из отрезков времени, между двумя событиями, которые влияют на состояние аккаунтов игроков.
События всех игроков выстраиваются в общую очередь. Очередь дискретна - каждое событие синхронизировано посекундно.
Проверка на завершение события (движение очереди) осуществляется когда игроки совершаются какие-либо действия (переходят по страницам).
Если два события попадают на одну секунду, то они обрабатываются в порядке приоритета (например если Атака совпадает по времени с Переработать,
на тех же координатах, то вначале обрабатывается Атака, а потом Переработать).

Каждое событие имеет начало (время запуска) и конец (время завершения события). Некоторые события можно отменить. Отмена некоторых событий порождает 
другие события (например отмена задания флота порождает новое задание возврата флота).

Главные типы событий аккаунта:
 - Счётчики времени на аккаунте игрока (действие офицеров, удаление аккаунта итп.)
 - Строительство на планете/луне
 - Исследование
 - Строительство на верфи
 - Задания для флота и МПР
 - Глобальные события для всех игроков (релогин, чистка виртуального лома, удаление уничтоженных планет, пересчёт очков 3 раза в сутки итп.)

Пересчёт очков: 8:05, 16:05, 20:05 по серверу

Запись задания в таблице БД:
task_id: уникальный номер задания (INT)
owner_id: номер пользователя которому принадлежит задание  (INT)
type: тип задания, каждый тип имеет свой обработчик: (CHAR(20))
    "CommanderOff"   -- заканчивается офицер: Командир
    "AdmiralOff"     -- заканчивается офицер: Адмирал
    "EngineerOff"    -- заканчивается офицер: Инженер
    "GeologeOff"     -- заканчивается офицер: Геолог
    "TechnocrateOff" -- заканчивается офицер: Технократ
    "DeleteAccount"     -- удалить аккаунт
    "UnbanPlayer"    -- разбанить игрока
    "ChangeEmail"    -- записать постоянный почтовый адрес
    "AllowName"    -- разрешить смену имени игрока
    "AllowAttacks"    -- отменить запрет атак у игрока
    "UnloadAll"      -- сделать релогин всех игроков
    "CleanDebris"    -- чистка виртуальных полей обломков
    "CleanPlanets"   -- удаление уничтоженных планет / покинутых лун
    "UpdateStats"    -- пересчёт статистики игроков
    "Build"          -- постройка на планете (sub_id - номер планеты, obj_id - тип постройки)
    "Demolish"       -- снос на планете (sub_id - номер планеты, obj_id - тип постройки)
    "Research"       -- исследование (sub_id - номер планеты где было запущено исследование, obj_id - тип исследования)
    "Shipyard"       -- задание для верфи (sub_id - номер планеты, obj_id - тип постройки)
    "FleetXXX"        -- Задание флота / Атака МПР (sub_id - номер записи в таблице флота)
    "DecRes"         -- Списать ресурсы на планете (sub_id - номер задания постройки для определения количества ресурсов)
sub_id: дополнительный номер, разный у каждого типа задания, например для постройки - ID планеты, для задания флота - ID флота (INT)
obj_id: дополнительный номер, разный у каждого типа задания, например для постройки - ID здания (INT)
level: уровень постройки / количество заказанных единиц на верфи (INT) / секунд удержания флота
start: время начала задания (INT UNSIGNED)
end: время окончания задания (INT UNSIGNED)
prio: приоритет события, используется для событий, которые заканчиваются в одно и тоже время, чем выше приоритет, тем раньше выполнится событие (INT)

Примеры запуска заданий: 
Постройка шахты металла (3): AddQueue (player_id, "Build", planet_id, 1, 3, 241)
Заказ ракетная установка (25): AddQueue (player_id, "Shipyard", planet_id, 401, 25, 14400)

Как происходит обновление очереди:
После очередного клика одного из юзеров проверяется каждое задание очереди на завершение. Если задание завершено - вызывается его обработчик и задание
удаляется из очереди.

*/

// Добавить задание в очередь. Возвращает ID добавленного задания.
function AddQueue ($owner_id, $type, $sub_id, $obj_id, $level, $now, $seconds, $prio=0)
{
    $id = IncrementDBGlobal ('nexttask');
    $queue = array ( $id, $owner_id, $type, $sub_id, $obj_id, $level, $now, $now+$seconds, $prio );
    AddDBRow ( $queue, "queue" );
    return $id;
}

// Загрузить задание.
function LoadQueue ($task_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE task_id = $task_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Удалить задание из очереди. cb=1: вызвать обработчик отмененного задания, cb=0: не вызывать.
function RemoveQueue ($task_id, $cb)
{
    global $db_prefix;

    // Выполнить обработчик отмены задания.
    if ($cb)
    {
        $queue = LoadQueue ($task_id);
        if ($queue['type'] === "Build" || $queue['type'] === "Demolish") Queue_Build_Cancel ($queue);
    }

    $query = "DELETE FROM ".$db_prefix."queue WHERE task_id = $task_id";
    dbquery ($query);
}

// Проверить задания очереди на завершение до момента времени $until.
function UpdateQueue ($until)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE end <= $until ORDER BY end ASC, prio DESC";
    $result = dbquery ($query);

    $rows = dbrows ($result);
    while ($rows--) {
        $queue = dbarray ($result);
        if ( $queue['type'] === "Build" ) Queue_Build_End ($queue);
        else if ( $queue['type'] === "Demolish" ) Queue_Build_End ($queue);
        else if ( $queue['type'] === "DecRes" ) Queue_DecRes_End ($queue);
        else if ( $queue['type'] === "Research" ) Queue_Research_End ($queue);
        else Error ( "queue: Неизвестный тип задания для глобальной очереди: " . $queue['type']);
    }
}

// ===============================================================================================================
// Постройки

// Получить прикрепленное задание списывания ресурсов.
function HasDecRes ($queue)
{
    global $db_prefix;

    $task_id = $queue['task_id'];

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'DecRes' AND sub_id = $task_id";
    $result = dbquery ($query);
    $decres = dbarray ($result);
    return ($decres==null) ? 0:1;
}

// Добавить новую постройку/снос в очередь
function BuildEnque ( $planet_id, $id, $destroy )
{
    global $db_prefix, $GlobalUser;
    $maxcnt = 5;

    $result = GetBuildQueue ( $planet_id );
    $cnt = dbrows ( $result );
    if ( $cnt >= $maxcnt ) { /*echo "Очередь построек заполнена!<br>";*/ return; }

    // Загрузить очередь. Отсортирована по времени начала событий.
    $queue = array ();
    for ($i=0; $i<$cnt; $i++)
    {
        $queue[$i] = dbarray ($result);
    }

    // Определить добавляемый уровень.
    $planet = GetPlanet ( $planet_id );
    $nowlevel = $planet['b'.$id];
    for ($i=0; $i<$cnt; $i++)
    {
        if ( $queue[$i]['obj_id'] == $id ) $nowlevel = $queue[$i]['level'];
    }

    if ($destroy) {
        $lvl = $nowlevel - 1;
        if ($lvl < 0) return;        // Невозможно снести несуществующую постройку.
    }
    else $lvl = $nowlevel + 1;

    $unitab = LoadUniverse ( $GlobalUser['uni'] );
    $speed = $unitab['speed'];

    // Только первая постройка.
    if ($cnt == 0)
    {
        // Проверить доступное количество ресурсов на планете
        $m = $k = $d = $e = 0;
        BuildPrice ( $id, $lvl, &$m, &$k, &$d, &$e );
        if ( !IsEnoughResources ( $planet, $m, $k, $d, $e ) ) { /*echo "Недостаточно ресурсов!<br>";*/ return; }

        // Проверить доступные технологии.
        if ( !BuildMeetRequirement ( $GlobalUser, $planet, $id ) ) { /*echo "Не выполнены условия для постройки!<br>";*/ return; }

        $now = time ();

        // Списать ресурсы.
        $planet['m'] -= $m;
        $planet['k'] -= $k;
        $planet['d'] -= $d;
        $query = "UPDATE ".$db_prefix."planets SET m = '".$planet['m']."', k = '".$planet['k']."', d = '".$planet['d']."', lastpeek = '".$now."' WHERE planet_id = $planet_id";
        dbquery ($query);

        // Добавить в очередь
        $type = $destroy ? "Demolish" : "Build";
        AddQueue ( $GlobalUser['player_id'], $type, $planet_id, $id, $lvl, $now, floor (BuildDuration ( $id, $lvl-1, $planet['b14'], $planet['b15'], $speed )) );
    }
    else
    {
        // Время начала = время окончания последней постройки в очереди.
        $now = $queue[$cnt-1]['end'];

        // Добавить в очередь
        $type = $destroy ? "Demolish" : "Build";
        $qid = AddQueue ( $GlobalUser['player_id'], $type, $planet_id, $id, $lvl, $now, floor (BuildDuration ( $id, $lvl-1, $planet['b14'], $planet['b15'], $speed )) );

        // Добавить событие списывания ресов (время окончания = время начала добавляемого задания).
        $q = LoadQueue ($qid);
        AddQueue ( $GlobalUser['player_id'], "DecRes", $q['task_id'], 0, 0, $q['start'], 0 );
    }
}

// Отменить постройку/снос
function BuildDeque ( $planet_id, $listid )
{
    global $db_prefix, $GlobalUser;

    // Загрузить очередь. Отсортирована по времени начала событий.
    $result = GetBuildQueue ( $planet_id );
    $cnt = dbrows ( $result );
    $queue = array ();
    for ($i=0; $i<$cnt; $i++)
    {
        $queue[$i] = dbarray ($result);
    }

    $listid--;
    if ( $listid < 0 || $listid >= $cnt ) return;    // Невозможно удалить несуществующую очередь.

    RemoveQueue ( $queue[$listid]['task_id'], 1 );
}

// Получить очередь строительства для планеты.
function GetBuildQueue ( $planet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE (type = 'Build' OR type = 'Demolish') AND sub_id = $planet_id ORDER BY start ASC";
    return dbquery ($query);
}

// Завершить снос/строительство постройки.
function Queue_Build_End ($queue)
{
    global $db_prefix, $desc;

    $id = $queue['obj_id'];
    $lvl = $queue['level'];
    $planet_id = $queue['sub_id'];

    // Рассчитать производство планеты с момента последнего обновления.
    $planet = GetPlanet ( $planet_id );
    ProdResources ( $planet_id, $planet['lastpeek'], $queue['end'] );

    // Обновить уровень постройки в базе данных.
    $query = "UPDATE ".$db_prefix."planets SET ".('b'.$id)." = $lvl WHERE planet_id = $planet_id";
    dbquery ($query);

    RemoveQueue ( $queue['task_id'], 0 );

    if ($queue['type'] === "Build" ) Debug ( "Строительство ".$desc[$id]." уровня $lvl на планете $planet_id завершено." );
    else Debug ( "Снос ".$desc[$id]." уровня $lvl на планете $planet_id завершен." );
}

// Отменить снос/строительство постройки.
function Queue_Build_Cancel ($queue)
{
    global $db_prefix;

    $id = $queue['obj_id'];
    $lvl = $queue['level'];
    $planet_id = $queue['sub_id'];

    // Вернуть ресурсы, если это необходимо.
    if ( !HasDecRes ($queue) )
    {
        $m = $k = $d = $e = 0;
        BuildPrice ( $id, $lvl, &$m, &$k, &$d, &$e );

        $now = time ();

        $planet = GetPlanet ($planet_id);
        $planet['m'] += $m;
        $planet['k'] += $k;
        $planet['d'] += $d;
        $query = "UPDATE ".$db_prefix."planets SET m = '".$planet['m']."', k = '".$planet['k']."', d = '".$planet['d']."', lastpeek = '".$now."'  WHERE planet_id = $planet_id";
        dbquery ($query);

        Debug ( "Build_Cancel - возвращаем ресы $m $k $d" );
    }
    else Debug ( "Build_Cancel - ресы возвращать не нужно" );

    // Корректируем уровень других построек такого же типа.
}

// Списать ресурсы. Если ресурсов недостаточно или не выполнены условия - отменить задание строительства.
function Queue_DecRes_End ($queue)
{
    global $db_prefix, $GlobalUser, $desc;

    $q = LoadQueue ($queue['sub_id']);
    if ($q == null)
    {
        Debug ( "DecRes - чистим мусор после отмененного задания");

        RemoveQueue ( $queue['task_id'], 0);    // Если прикрепленной постройки уже нет - просто удалить задание.
        return;
    }

    $id = $q['obj_id'];
    $lvl = $q['level'];
    $planet_id = $q['sub_id'];

    $m = $k = $d = $e = 0;
    BuildPrice ( $id, $lvl, &$m, &$k, &$d, &$e );

    Debug ( "DecRes - списать ресы $m $k $d за " . $desc[$id] . " уровень $lvl" );

    $planet = GetPlanet ($planet_id);

    if ( IsEnoughResources ($planet, $m, $k, $d, $e) && BuildMeetRequirement ( $GlobalUser, $planet, $id ) )
    {
        $now = time ();

        // Списать ресурсы.
        $planet['m'] -= $m;
        $planet['k'] -= $k;
        $planet['d'] -= $d;
        $query = "UPDATE ".$db_prefix."planets SET m = '".$planet['m']."', k = '".$planet['k']."', d = '".$planet['d']."', lastpeek = '".$now."' WHERE planet_id = $planet_id";
        dbquery ($query);

        Debug ( "DecRes - списали $m $k $d");
    }
    else 
    {
        Debug ( "DecRes - что то не так (не хватает ресов или не выполнены условия)" );
        RemoveQueue ( $queue['sub_id'], 0 );  // отменить строительство, недостаточно ресурсов или не выполнены условия.
    }

    RemoveQueue ( $queue['task_id'], 0 );    // убрать списывание ресов
}

// ===============================================================================================================
// Верфь

// Получить очередь заданий на верфи.
function GetShipyardQueue ($planet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'Shipyard' AND sub_id = $planet_id ORDER BY start ASC";
    return dbquery ($query);
}

// ===============================================================================================================
// Исследования

// Начать исследование на планете (включает в себя все проверки).
function StartResearch ($player_id, $planet_id, $id)
{
	global $db_prefix;
	
	// Требования к уровню лаборатории для запуска исследования.
	$RequireLab = array ( 106=>3, 108=>1, 109=>4, 110=>6, 111=>2, 113=>1, 114=>7, 115=>1, 117=>2, 118=>7, 120=>1, 121=>4, 122=>4, 123=>10, 124=>3, 199=>12 );

	Debug ("Запустить исследование $id на планете $planet_id игрока $player_id" );

	// Исследование уже ведется?
	$result = GetResearchQueue ( $player_id);
	$resq = dbarray ($result);
	if ($resq) return;

	// Получить уровень исследования.
	$user = LoadUser ( $player_id );
	$level = $user['r'.$id] + 1;

	// Проверить условия.
	$planet = GetPlanet ( $planet_id );
	$m = $k = $d = $e = 0;
	ResearchPrice ( $id, $level, &$m, &$k, &$d, &$e );

	if ( IsEnoughResources ( $planet, $m, $k, $d, $e ) && ResearchMeetRequirement ( $user, $planet, $id ) && $planet['b31'] >= $RequireLab[$id] ) {
    	$unitab = LoadUniverse ( $user['uni'] );
    	$speed = $unitab['speed'];
		$now = time ();
		$reslab = ResearchNetwork ( $planet['planet_id'], $id );
		$seconds = ResearchDuration ( $id, $level, $reslab, $speed);

        // Списать ресурсы.
        $planet['m'] -= $m;
        $planet['k'] -= $k;
        $planet['d'] -= $d;
        $query = "UPDATE ".$db_prefix."planets SET m = '".$planet['m']."', k = '".$planet['k']."', d = '".$planet['d']."', lastpeek = '".$now."' WHERE planet_id = $planet_id";
        dbquery ($query);

		//echo "--------------------- Запустить исследование $id на планете $planet_id игрока $player_id, уровень $level, продолжительность $seconds" ;
		AddQueue ($player_id, "Research", $planet_id, $id, $level, $now, $seconds);
	}
}

// Отменить исследование.
function StopResearch ($player_id, $id)
{
	global $db_prefix;

	Debug ( "Отменить исследование $id у игрока $player_id" );
}

// Получить текущее исследование для аккаунта.
function GetResearchQueue ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'Research' AND owner_id = $player_id ORDER BY start ASC";
    return dbquery ($query);
}

// Закончить исследование.
function Queue_Research_End ($queue)
{
	global $db_prefix, $desc;

    $id = $queue['obj_id'];
    $lvl = $queue['level'];
    $planet_id = $queue['sub_id'];
	$player_id = $queue['owner_id'];

    // Рассчитать производство планеты с момента последнего обновления.
    $planet = GetPlanet ( $planet_id );
    ProdResources ( $planet_id, $planet['lastpeek'], $queue['end'] );

    // Обновить уровень исследования в базе данных.
    $query = "UPDATE ".$db_prefix."users SET ".('r'.$id)." = $lvl WHERE player_id = $player_id";
    dbquery ($query);

    RemoveQueue ( $queue['task_id'], 0 );

    Debug ( "Исследование ".$desc[$id]." уровня $lvl для пользователя $player_id завершено." );
}

// ===============================================================================================================
// Игрок

// Получить время окончания офицера. $off - символьное обозначения задания очереди, связанного с офицерами.
function GetOfficerLeft ($player_id, $off)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".$off."' AND owner_id = $player_id ORDER BY end ASC";
    $result = dbquery ($query);
    if ( $result )
    {
        $queue = dbarray ($result);
        return $queue['end'];
    }
    else return 0;
}

// ===============================================================================================================
// Вселенная

?>