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

    // Инициализировать ДСЧ

    list($usec,$sec)=explode(" ",microtime());
    $seed = (int)($sec * $usec) & 0xffffffff;
    mt_srand ($seed);

    // Модифицировать постройки на Главной планете

    SetPlanetBuildings ( $hplanetid, GetCarnageModeBuildings(false) );
    AdjustResources (30000000, 20000000, 10000000, $hplanetid, '+');
    RecalcFields ($hplanetid);

    // Создать ещё 8 развитых планет с лунами

    for ($i=0; $i<8; $i++) {

        $g = mt_rand (1, $uni['galaxies']);
        $s = mt_rand (1, $uni['systems']);
        $p = mt_rand (1, 15);

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
        SetPlanetFleetDefense ( $moon_id, GetCarnageModeFleet(GetModeVarInt('mod_carnage_fleet_size') * 1000000 ) );
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
        115 => 18,
        117 => 16,
        118 => 14,
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
}

// Получить постройки на планете/луне для режима Carnage
function GetCarnageModeBuildings ($moon)
{
    if ($moon) {
        $objects = array ( 
            'b1' >= 40,
            'b2' >= 35,
            'b3' >= 35,
            'b4' >= 25,
            'b12' >= 0,
            'b14' >= 10,
            'b15' >= 7,
            'b21' >= 12,
            'b22' >= 15,
            'b23' >= 15,
            'b24' >= 15,
            'b31' >= 12,
            'b33' >= 0,
            'b34' >= 0,
            'b41' >= 0,
            'b42' >= 0,
            'b43' >= 0,
            'b44' >= 0,
        );
        return $objects;
    }
    else {
        $objects = array ( 
            'b1' >= 0,
            'b2' >= 0,
            'b3' >= 0,
            'b4' >= 0,
            'b12' >= 0,
            'b14' >= 0,
            'b15' >= 0,
            'b21' >= 0,
            'b22' >= 0,
            'b23' >= 0,
            'b24' >= 0,
            'b31' >= 0,
            'b33' >= 0,
            'b34' >= 0,
            'b41' >= 7,
            'b42' >= 7,
            'b43' >= 1,
            'b44' >= 0,
        );
        return $objects;
    }
}

// Сгенерировать флот для режима Carnage, указанного количества очков (стандартных очков ресурсов aka стоимость флота)
function GetCarnageModeFleet ($points)
{
    $objects = array (
        'd401' => 0, 'd402' => 0, 'd403' => 0, 'd404' => 0, 'd405' => 0, 'd406' => 0, 'd407' => 0, 'd408' => 0, 
        'f202' => 0, 'f203' => 0, 'f204' => 0, 'f205' => 0, 'f206' => 0, 'f207' => 0, 'f208' => 0, 'f209' => 0, 'f210' => 0, 'f211' => 0, 'f212' => 0, 'f213' => 0, 'f214' => 0, 'f215' => 0 );

    return $objects;
}

?>