<?php

// Модуль для поддержки модификаций

// Получить переменную из таблицы настроек модификаций (String)
function GetModeVarStr ($var)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."mods WHERE var = '".$var."' LIMIT 1;";
    $result = dbquery ($query);
    if ( dbrows ($result) > 0 ) {
        $var = dbarray ( $result );
        return $var['value'];
    }
    return "";
}

// Получить переменную из таблицы настроек модификаций (Int)
function GetModeVarInt ($var)
{
    return intval (GetModeVarStr($var));
}

// Модифицировать созданного пользователя для режима Carnage
function ModifyUserForCarnageMode ($player_id)
{
    global $db_prefix;

    $uni = LoadUniverse ();
    $user = LoadUser ($player_id);
    $hplanetid = $user['hplanetid'];
    $hplanet = LoadPlanetById ($hplanetid);

    // Инициализировать ДСЧ

    list($usec,$sec)=explode(" ",microtime());
    $seed = (int)($sec * $usec) & 0xffffffff;
    mt_srand ($seed);

    // Модифицировать постройки на Главной планете

    SetPlanetBuildings ( $hplanetid, GetCarnageModeBuildings(false) );
    AdjustResources (30000000, 20000000, 10000000, $hplanetid, '+');
    RecalcFields ($hplanetid);

    // Добавить луну Главной планете

    $moon_id = CreatePlanet ($hplanet['g'], $hplanet['s'], $hplanet['p'], $player_id, 0, 1, mt_rand(15,20));
    SetPlanetBuildings ( $moon_id, GetCarnageModeBuildings(true) );
    SetPlanetFleetDefense ( $moon_id, GetCarnageModeFleet(GetModeVarInt('mod_carnage_fleet_size') * 1000000000 ) );
    AdjustResources (30000000, 20000000, 10000000, $moon_id, '+');
    RecalcFields ($moon_id);

    // Создать ещё 8 развитых планет с лунами

    for ($i=0; $i<8; $i++) {

        $g = mt_rand (1, $uni['galaxies']);
        $s = mt_rand (1, $uni['systems']);
        $p = mt_rand (4, 12);

        if (HasPlanet($g, $s, $p)) {
            $i--;   // повторить попытку создания колонии
            continue;
        }

        $planet_id = CreatePlanet ($g, $s, $p, $player_id, 1);

        // Нарисовать постройки

        SetPlanetBuildings ( $planet_id, GetCarnageModeBuildings(false) );
        AdjustResources (30000000, 20000000, 10000000, $planet_id, '+');
        RecalcFields ($planet_id);

        // Добавить луну

        $moon_id = CreatePlanet ($g, $s, $p, $player_id, 0, 1, mt_rand(15,20));

        // На каждой луне "нарисовать" флот и базовые постройки

        SetPlanetBuildings ( $moon_id, GetCarnageModeBuildings(true) );
        SetPlanetFleetDefense ( $moon_id, GetCarnageModeFleet(GetModeVarInt('mod_carnage_fleet_size') * 1000000000 ) );
        AdjustResources (30000000, 20000000, 10000000, $moon_id, '+');
        RecalcFields ($moon_id);
    }

    // Модифицировать исследования

    $carnage_resmap = array (
        106 => 15,
        108 => 15,
        109 => 18,
        110 => 18,
        111 => 18,
        113 => 12,
        114 => 10,
        115 => 20,
        117 => 18,
        118 => 16,
        120 => 12,
        121 => 5,
        122 => 8,
        123 => 5,
        124 => 9,
        199 => 1,
    );
    $query = "UPDATE ".$db_prefix."users SET ";
    foreach ( $carnage_resmap as $gid=>$level)
    {
        $query .= "r$gid = $level, ";
    }
    $query .= "sniff = 0 ";         // просто безопасное поле, чтобы избавиться от запятой выше
    $query .= " WHERE player_id=$player_id;";
    dbquery ($query);

    RecalcStats ($player_id);
}

// Получить постройки на планете/луне для режима Carnage
function GetCarnageModeBuildings ($moon)
{
    $objects = array();

    if ($moon) {
        $objects['b1'] = 0;
        $objects['b2'] = 0;
        $objects['b3'] = 0;
        $objects['b4'] = 0;
        $objects['b12'] = 0;
        $objects['b14'] = 0;
        $objects['b15'] = 0;
        $objects['b21'] = 0;
        $objects['b22'] = 0;
        $objects['b23'] = 0;
        $objects['b24'] = 0;
        $objects['b31'] = 0;
        $objects['b33'] = 0;
        $objects['b34'] = 0;
        $objects['b41'] = 7;
        $objects['b42'] = 7;
        $objects['b43'] = 1;
        $objects['b44'] = 0;
    }
    else {

        $objects['b1'] = 40;
        $objects['b2'] = 35;
        $objects['b3'] = 35;
        $objects['b4'] = 25;
        $objects['b12'] = 0;
        $objects['b14'] = 10;
        $objects['b15'] = 10;
        $objects['b21'] = 12;
        $objects['b22'] = 15;
        $objects['b23'] = 15;
        $objects['b24'] = 15;
        $objects['b31'] = 12;
        $objects['b33'] = 0;
        $objects['b34'] = 0;
        $objects['b41'] = 0;
        $objects['b42'] = 0;
        $objects['b43'] = 0;
        $objects['b44'] = 0;
    }

    return $objects;    
}

// Сгенерировать флот для режима Carnage, указанного количества очков (стандартных очков ресурсов aka стоимость флота)
function GetCarnageModeFleet ($points)
{
    $objects = array (
        'd401' => 0, 'd402' => 0, 'd403' => 0, 'd404' => 0, 'd405' => 0, 'd406' => 0, 'd407' => 0, 'd408' => 0, 
        'f202' => 0, 'f203' => 0, 'f204' => 0, 'f205' => 0, 'f206' => 0, 'f207' => 0, 'f208' => 0, 'f209' => 0, 'f210' => 0, 'f211' => 0, 'f212' => 0, 'f213' => 0, 'f214' => 0, 'f215' => 0 );

    $total = 0;

    while ($total < $points) {

        $id = mt_rand (202, 215);
        $price = ShipyardPrice ($id);

        // Будем добавлять флот относительно большими кусками. Не добавляем колоники, ШЗ и СС.

        switch ($id)
        {
            case 202: $count = mt_rand(4000, 5000); break;     // Малый транспорт
            case 203: $count = mt_rand(1000, 2000); break;     // Большой транспорт
            case 204: $count = mt_rand(10000, 20000); break;    // Лёгкий истребитель
            case 205: $count = mt_rand(3333, 5555); break;     // Тяжёлый истребитель
            case 206: $count = mt_rand(500, 1500); break;      // Крейсер
            case 207: $count = mt_rand(300, 900); break;      // Линкор
            case 208: $count = 0; break;        // Колонизатор
            case 209: $count = mt_rand(1000, 2000); break;     // Переработчик
            case 210: $count = 0; break;        // Шпионский зонд
            case 211: $count = mt_rand(300, 400); break;      // Бомбардировщик
            case 212: $count = 0; break;        // Солнечный спутник
            case 213: $count = mt_rand(200, 300); break;      // Уничтожитель
            case 214: $count = 1; break;        // Звезда смерти
            case 215: $count = mt_rand(300, 500); break;      // Линейный крейсер
        }

        if ($count == 0) {
            continue;
        }

        $total += ($price['m'] + $price['k'] + $price['d']) * $count;
        $objects['f'.$id] += $count;
    }

    return $objects;
}

?>