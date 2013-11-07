<?php

// Управление планетами и лунами: создание/колонизация, уничтожение, загрузка планет из БД, переименование.

/*

Формулы для расчета размера луны:
Минимальный = floor (1000*(10 + 3 * Шанс)^0,5) km
Максимальный = floor (1000*(20 + 3 * Шанс)^0,5) km 

FIELDS = FLOOR ( (DIAM / 1000) ^ 2 )
*/

/*
planet_id: Порядковый номер (INT AUTO_INCREMENT PRIMARY KEY)
name: Название планеты CHAR(20)
type: тип планеты, если 0 - то это луна, 1 - планета, если 10000 - это поле обломков, 10001 - уничтоженная планета, 10002 - фантом для колонизации, 10003 - уничтоженная луна, 10004 - покинутая колония, 20000 - бесконечные дали
g,s,p: координаты где расположена планета
owner_id: Порядковый номер пользователя-владельца
R diameter: Диаметр планеты
R temp: Минимальная температура
fields: Количество застроенных полей
R maxfields: Максимальное количество полей
date: Дата создания
bXX: Уровень постройки
dXX: Количество оборонительных сооружений
fXX: Количество флота каждого типа
m, k, d: Металла, кристалла, дейтерия
mprod, kprod, dprod: Процент выработки шахт металла, кристалла, дейтерия ( 0...1 FLOAT)
sprod, fprod, ssprod: Процент выработки солнечной электростанции, термояда и солнечных спутников ( 0...1 FLOAT)
lastpeek: Время последнего обновления состояния планеты (INT UNSIGNED time)
lastakt: Время последней активности (INT UNSIGNED time)
gate_until: Время остывания  ворот. (INT UNSIGNED time)
remove: Время удаления планеты (0 - не удалять). (INT UNSIGNED time)

R - случайные параметры

чистка систем от "уничтоженных планет" происходит каждые сутки в 01-10 по серверу.
существует "уничтоженная планета" 1 сутки (24 часа) + остаток времени до 01-10 серверного следующего за этими сутками дня.

поля обломков - это специальный тип планеты. (type: 10000)

*/

// Создать планету. Возвращает planet_id, или 0 если позиция занята.
// colony: 1 - создать колонию, 0 - Главная планета
// moon: 1 - создать луну
// moonchance: шанс возникновения луны (для размера луны)
function CreatePlanet ( $g, $s, $p, $owner_id, $colony=1, $moon=0, $moonchance=0, $when=0)
{
    global $db_prefix;

    // Проверить не занято-ли место?
    if ($moon) $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND p = '".$p."' AND ( type = 0 OR type = 10003 )";
    else $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND p = '".$p."' AND ( ( type > 0 AND type < 10000) OR type = 10001 OR type = 10004 )";
    $result = dbquery ($query);
    if ( dbrows ($result) != 0 ) return 0;

    // Название планеты.
    if ($moon) $name = "Луна";
    else
    {
        if ($colony) $name = "Колония";
        else $name = "Главная планета";
    }

    // Тип планеты.
    if ($moon) $type = 0;
    else $type = 1;

    // Диаметр.
    if ($moon) $diam = floor ( 1000 * sqrt (mt_rand (10, 20) + 3*$moonchance)  );
    else
    {
        if ($colony)
        {
            if ($p <= 3) $diam = mt_rand ( 50, 120 ) * 72;
            else if ($p >= 4 && $p <= 6) $diam = mt_rand ( 50, 150 ) * 120;
            else if ($p >= 7 && $p <= 9) $diam = mt_rand ( 50, 120 ) * 120;
            else if ($p >= 10 && $p <= 12) $diam = mt_rand ( 50, 120 ) * 96;
            else if ($p >= 13 && $p <= 15) $diam = mt_rand ( 50, 150 ) * 96;
            else $diam = mt_rand ( 50, 150 ) * 96;
        }
        else $diam = 12800;
    }
    
    // Максимальное количество полей.
    $fields = floor (pow (($diam / 1000), 2));

    // Температура
    if ($p <= 3) $temp = 80 + (rand() % 10) - 2*$p;
    else if ($p >= 4 && $p <= 6) $temp = 30 + (rand() % 10) - 2*$p;
    else if ($p >= 7 && $p <= 9) $temp = 10 + (rand() % 10) - 2*$p;
    else if ($p >= 10 && $p <= 12) $temp = -10 + (rand() % 10) - 2*$p;
    else if ($p >= 13 && $p <= 15) $temp = -60 + (rand() % 10) - 2*$p;
    else $temp = -60 + (rand() % 10) - 2*$p;
    if ( $moon ) {
        $pl = LoadPlanet ($g, $s, $p, 1);
        if ($pl) $temp = $pl['temp'] - mt_rand (20, 30);
        else $temp -= mt_rand (20, 30);
    }

    // Добавить планету
    if ( $when == 0 ) $now = time();
    else $now = $when;
    if ($moon) $planet = array( null, $name, $type, $g, $s, $p, $owner_id, $diam, $temp, 0, 1, $now,
                                          0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                          0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                          0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                          0, 0, 0, 1, 1, 1, 1, 1, 1, $now, $now, 0, 0 );
    else $planet = array( null, $name, $type, $g, $s, $p, $owner_id, $diam, $temp, 0, $fields, $now,
                                 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                 500, 500, 0, 1, 1, 1, 1, 1, 1, $now, $now, 0, 0 );
    $id = AddDBRow ( $planet, "planets" );

    return $id;
}

// Перечислить все планеты пользователя. Возвратить результат SQL-запроса.
function EnumPlanets ()
{
    global $db_prefix, $GlobalUser;
    $player_id = $GlobalUser['player_id'];

    // Получить тип сортировки.
    // sortby: Порядок сортировки планет: 0 - порядку колонизации (planet_id), 1 - координатам, 2 - алфавиту
    // sortorder: Порядок: 0 - по возрастанию, 1 - по убыванию
    $asc = $GlobalUser['sortorder'] == 0 ? "ASC" : "DESC";
    if ($GlobalUser['sortby'] == 0) $order = " ORDER BY planet_id $asc, type DESC";
    else if ($GlobalUser['sortby'] == 1) $order = " ORDER BY g $asc, s $asc, p $asc, type DESC";
    else if ($GlobalUser['sortby'] == 2) $order = " ORDER BY name $asc, type DESC";
    else $order = "";

    $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".$player_id."' AND type < 10000".$order;
    $result = dbquery ($query);
    return $result;
}

// Перечислить все планеты в Галактике.
function EnumPlanetsGalaxy ($g, $s)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND ((type > 0 AND type < 10000) OR type = 10001 OR type = 10004) ORDER BY p ASC";
    $result = dbquery ($query);
    return $result;
}

// Получить состояние планеты (массив).
function GetPlanet ( $planet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id = '".$planet_id."' LIMIT 1";
    $result = dbquery ($query);
    if ( dbrows($result) == 0 ) return NULL;
    $planet = dbarray ($result);
    $user = LoadUser ( $planet['owner_id'] );

    $prem = PremiumStatus ($user);
    if ( $prem['engineer'] ) $e_factor = 1.1;
    else $e_factor = 1.0;

    $planet['mmax'] = store_capacity ( $planet['b22'] );
    $planet['kmax'] = store_capacity ( $planet['b23'] );
    $planet['dmax'] = store_capacity ( $planet['b24'] );
    $planet['emax'] = prod_solar($planet['b4'], $planet['sprod']) * $e_factor  + 
				       prod_fusion($planet['b12'], $user['r113'], $planet['fprod']) * $e_factor  + 
					 prod_sat($planet['temp']+40) * $planet['f212'] * $planet['ssprod'] * $e_factor ;

    $planet['econs'] = ( cons_metal ($planet['b1']) * $planet['mprod'] + 
                                 cons_crys ($planet['b2']) * $planet['kprod'] + 
                                 cons_deut ($planet['b3']) * $planet['dprod'] );

    $planet['e'] = floor ( $planet['emax'] - $planet['econs'] );
    $planet['factor'] = 1;
    if ( $planet['e'] < 0 ) $planet['factor'] = max (0, 1 - abs ($planet['e']) / $planet['econs']);
    return $planet;
}

// Загрузить состояние планеты по указанным координатам (без предварительной обработки)
// Вернуть массив $planet, или NULL.
function LoadPlanet ($g, $s, $p, $type)
{
    global $db_prefix;
    if ($type == 1) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND ((type > 0 AND type < 10000) OR type = 10001) LIMIT 1;";
    else if ($type == 2) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND type=10000 LIMIT 1;";
    else if ($type == 3) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND (type=0 OR type=10003) LIMIT 1;";
    else return NULL;
    $result = dbquery ($query);
    if ( $result ) return dbarray ($result);
    else return NULL;
}

// Если у планеты есть луна (даже уничтоженная), возвратить её ID, иначе возвратить 0.
function PlanetHasMoon ( $planet_id )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id = '".$planet_id."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return 0;    // Планета не найдена
    $planet = dbarray ($result);
    if ( $planet['type'] == 0 || $planet['type'] == 10003 ) return 0;        // Планета сама является луной
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$planet['g']."' AND s = '".$planet['s']."' AND p = '".$planet['p']."' AND (type = 0 OR type = 10003)";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return 0;    // Луна у планеты не найдена.
    $planet = dbarray ($result);
    return $planet['planet_id'];
}

// Длина имени планеты не более 20 символов (слово (Луна) тоже учитывается)
// Из имени вырезаются следующие символы: / ' " * ( )
// Если в имени пристутствуют символы ; , < > \ ` то имя не изменяется.
// Если имя планеты пустое, она называется "планета"
// Больше одного пробела вырезается.
function RenamePlanet ($planet_id, $name)
{
    // Найти планету.
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id = '".$planet_id."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return;    // Планета не найдена
    $planet = dbarray ($result);

    // Проверить название.
    if ( $planet['type'] == 0) $name = mb_substr ($name, 0, 20-mb_strlen(" (Луна)", "UTF-8"), "UTF-8");    // Ограничить длину имени.
    else $name = mb_substr ($name, 0, 20, "UTF-8");
    $pattern = '/[;,<>\`]/';
    if (preg_match ($pattern, $name)) return;    // Запрещенные символы.
    $pattern = '/[\\\\()*\"\']/';
    $name = preg_replace ($pattern, '', $name);
    $name = trim ($name);
    if (strlen ($name) == 0) {
        if ( $planet['type'] == 0 ) $name = "Луна";
        else $name = "планета";
    }
    else
    {
        $name = preg_replace ('/\s\s+/', ' ', $name);    // Вырезать лишние пробелы.
        // Если планета -- луна, то добавить приставку.
        if ( $planet['type'] == 0 ) $name .= " (".loca("MOON").")";
    }

    // Если всё нормально - сменить имя планеты.
    $query = "UPDATE ".$db_prefix."planets SET name = '".$name."' WHERE planet_id = $planet_id";
    dbquery ($query);
}

// НИКАКИХ ПРОВЕРОК НЕ ПРОИЗВОДИТСЯ!!
function DestroyPlanet ($planet_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."planets WHERE planet_id = $planet_id";
    dbquery ($query);
}

// Обновить активность на планете
function UpdatePlanetActivity ( $planet_id, $t=0)
{
    global $db_prefix;
    if ($t == 0) $now = time ();
    else $now = $t;
    $query = "UPDATE ".$db_prefix."planets SET lastakt = $now WHERE planet_id = $planet_id";
    dbquery ($query);
}

// Управление полями обломков.
// Загрузка ПО осуществляется вызовом GetPlanet. Удаление ПО осуществляется вызовом DestroyPlanet.

// Проверяет, есть ли на данных координатах ПО. Возвращает id ПО, или 0.
function HasDebris ($g, $s, $p)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = $g AND s = $s AND p = $p AND type = 10000";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) return 0;
    $debris = dbarray ($result);
    return $debris['planet_id'];
}

// Создаёт новое ПО по указанным координатам
function CreateDebris ($g, $s, $p, $owner_id)
{
    global $db_prefix;
    $debris_id = HasDebris ($g, $s, $p);
    if ($debris_id > 0 ) return $debris_id;
    $now = time();
    $planet = array ( null, "Поле обломков", 10000, $g, $s, $p, $owner_id, 0, 0, 0, 0, $now,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, $now, $now, 0, 0 );
    $id = AddDBRow ( $planet, 'planets' );
    return $id;
}

// Собрать ПО указанной грузоподъёмностью. В переменные $harvest m/k попадает собранное ПО.
function HarvestDebris ($planet_id, $cargo, $when)
{
    global $db_prefix;
    $harvest = array ();
    $debris = GetPlanet ($planet_id);

    $dm = $debris['m'];
    $dk = $debris['k'];

    $m = $cargo / 2;
    if ( floor($dm) < $m) $m = $dm;
    $cargo -= $m;
    $k = $cargo;
    if ( floor($dk) < $k) $k = $dk;
    $cargo -= $k;
    if ( $cargo < 0 ) $cargo = 0;
    $m2 = $cargo;
    if ( floor ( $dm-$m) < $m2 ) $m2 = $dm - $m;
    $m += $m2;

    $query = "UPDATE ".$db_prefix."planets SET m = m - $m, k = k - $k, lastpeek = $when WHERE planet_id = $planet_id";
    dbquery ($query);

    $harvest['m'] = $m;
    $harvest['k'] = $k;
    return $harvest;
}

// Насыпать лома в указанное ПО
function AddDebris ($id, $m, $k)
{
    global $db_prefix;
    $now = time ();
    $query = "UPDATE ".$db_prefix."planets SET m = m + $m, k = k + $k, lastpeek = $now WHERE planet_id = $id";
    dbquery ($query);
}

// Получить игровой тип планеты.
function GetPlanetType ($planet)
{
    if ( $planet['type'] == 0 || $planet['type'] == 10003 ) return 3;
    else if ( $planet['type'] == 10000) return 2;
    else return 1;
}

// Создать фантом колонизации. Вернуть ID.
function CreateColonyPhantom ($g, $s, $p, $owner_id)
{
    $planet = array( null, "Planet", 10002, $g, $s, $p, $owner_id, 0, 0, 0, 0, time(),
                             0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                             0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                             0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                             0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    $id = AddDBRow ( $planet, 'planets' );
    return $id;
}

// Добавить покинутую колонию.
function CreateAbandonedColony ($g, $s, $p, $when)
{
    // Если на заданных координатах нет планеты, то добавить Покинутую колонию.
    if ( !HasPlanet ( $g, $s, $p ) )
    {
        $planet = array( null, "Покинутая колония", 10004, $g, $s, $p, 99999, 0, 0, 0, 0, $when,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, $when, $when, 0, $when + 24*3600 );
        $id = AddDBRow ( $planet, 'planets' );
    }
    else $id = 0;
    return $id;
}

// Проверить есть ли уже планета на заданных координатах (для Колонизации). Учитываются также уничтоженные планеты и покинутые колонии.
// Фантомы колонизации не учитываются (кто первый долетит)
function HasPlanet ($g, $s, $p)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND ( ( type > 0 AND type < 10000) OR type = 10001 OR type = 10004 );";
    $result = dbquery ($query);
    if ( dbrows ($result) ) return 1;
    else return 0;
}

// Изменить количество ресурсов на планете.
function AdjustResources ($m, $k, $d, $planet_id, $sign)
{
    global $db_prefix;
    $now = time ();
    $query = "UPDATE ".$db_prefix."planets SET m=m $sign '".$m."', k=k $sign '".$k."', d=d $sign '".$d."', lastpeek = '".$now."' WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Уничтожить луну, развернуть флоты, модифицировать статистику игрока.
// fleet_id - ID флота уничтожившего луну. Разворот этого флота контролируется боевым движком.
function DestroyMoon ($moon_id, $when, $fleet_id)
{
    global $db_prefix;

    $moon = GetPlanet ( $moon_id );
    $planet = LoadPlanet ( $moon['g'], $moon['s'], $moon['p'], 1 );
    if ( $moon == NULL || $planet == NULL ) return;

    // Развернуть флоты летящие на луну
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE target_planet = $moon_id AND mission < 100 AND fleet_id <> $fleet_id;";
    $result = dbquery ( $query );
    $rows = dbrows ($result);
    while ( $rows-- )
    {
        $fleet_obj = dbarray ( $result );
        RecallFleet ( $fleet_obj['fleet_id'], $when );
    }

    // Перенаправить возвращающиеся и улетающие флоты на планету.
    $query = "UPDATE ".$db_prefix."fleet SET start_planet = ".$planet['planet_id']." WHERE start_planet = $moon_id;";
    dbquery ( $query );

    // Всё остальное уничтожается безвозвратно
    DestroyPlanet ( $moon_id );

    // Сделать текущей планетой - планету под уничтоженной луной
    SelectPlanet ( $planet['owner_id'], $planet['planet_id'] );
}

// Пересчитать поля.
function RecalcFields ($planet_id)
{
    global $db_prefix;
    $buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );
    $planet = GetPlanet ($planet_id);
    $fields = 0;
    if ( $planet['type'] == 0 || $planet['type'] == 10003 ) $maxfields = 1;    // луна
    else $maxfields = floor (pow (($planet['diameter'] / 1000), 2));    // планета
    foreach ( $buildmap as $i=>$gid ) $fields += $planet["b$gid"];
    $maxfields += 5 * $planet["b33"] + 3 * $planet["b41"];    // терраформер и ЛБ
    $query = "UPDATE ".$db_prefix."planets SET fields=$fields, maxfields=$maxfields WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Бесконечные дали.
function CreateOuterSpace ($g, $s, $p)
{
    global $db_prefix;

    // Если там уже есть объект, вернуть его ID.
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND type = 20000;";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) 
    {
        $planet = array( null, "Бесконечные дали", 20000, $g, $s, $p, 99999, 0, 0, 0, 0, time(),
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
        $id = AddDBRow ( $planet, 'planets' );
    }
    else
    {
        $planet = dbarray ($result);
        $id = $planet['planet_id'];
    }
    return $id;
}

// Установить флот и оборону на планете.
function SetPlanetFleetDefense ( $planet_id, $objects )
{
    global $db_prefix;
    $param = array (  'd401', 'd402', 'd403', 'd404', 'd405', 'd406', 'd407', 'd408', 
                      'f202', 'f203', 'f204', 'f205', 'f206', 'f207', 'f208', 'f209', 'f210', 'f211', 'f212', 'f213', 'f214', 'f215' );
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ( $param as $i=>$p ) {
        if ( $i == 0 ) $query .= "$p=".$objects[$p];
        else $query .= ", $p=".$objects[$p];
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Установить оборону на планете.
function SetPlanetDefense ( $planet_id, $objects )
{
    global $db_prefix;
    $param = array (  'd401', 'd402', 'd403', 'd404', 'd405', 'd406', 'd407', 'd408', 'd502', 'd503' );
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ( $param as $i=>$p ) {
        if ( $i == 0 ) $query .= "$p=".$objects[$p];
        else $query .= ", $p=".$objects[$p];
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Вернуть название планеты со ссылкой на админку.
function AdminPlanetName ($planet)
{
    global $session;
    return "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a>";
}

// Вернуть строку координат планеты со ссылкой на галактику
function AdminPlanetCoord ($p)
{
    global $session;
    return "[<a href=\"index.php?page=galaxy&session=$session&galaxy=".$p['g']."&system=".$p['s']."\">".$p['g'].":".$p['s'].":".$p['p']."</a>]";
}

// Создать главную планету, вернуть ID созданной планеты
function CreateHomePlanet ($player_id)
{
    global $db_prefix;
    $ss = 15;
    $uni = LoadUniverse ();

    $ppg = $ss * $uni['systems'];        // количество планет в галактике

    $sg = 1;        // стартовая галактика для регистрации
    $planet = array ();
    for ( $i=0; $i<($sg-1)*$ppg; $i++) $planet[$i] = 1;
    for ( $i; $i<$uni['galaxies']*$ppg; $i++) $planet[$i] = 0;

    $query = "SELECT * FROM ".$db_prefix."planets WHERE g >= $sg AND p <= $ss AND type <> 10002 ORDER BY g, s, p";
    $result = dbquery ($query);
    $rows = dbrows ( $result );
    while ($rows--)
    {
        $destination = dbarray ($result);
        $d = ( ($destination['g'] - 1) * $ppg ) + ($destination['s'] - 1) * $ss + $destination['p'] - 1;
        $planet[$d] = 1;
    }

    $d = ($sg - 1) * $ppg;
    while ($d < $ppg*9) 
    {
        $g = floor ( $d / $ppg ) + 1;
        $dd = $d - ($g - 1) * $ppg;
        $s = floor ($dd/$ss) + 1;
        $p = $dd % $ss + 1;

        if ( !$planet[floor($d)] && $g>=1 && $p>3 && $p<13 ) {
            return CreatePlanet ( $g, $s, $p, $player_id, 0);
        }
        $d += 1.3;
    }

    Error ( "No more planets!!!" );
}

?>