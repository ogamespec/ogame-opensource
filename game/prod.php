<?php

// Вспомогательные функции для экономической части OGame.

// Расчет стоимости, времени постройки и необходимых условий.

// Стоимость первого уровня.
// Постройки.
$initial[14]['m'] = 400; $initial[14]['k'] = 120; $initial[14]['d'] = 200; $initial[14]['e'] = 0;
$initial[15]['m'] = 1000000; $initial[15]['k'] = 500000; $initial[15]['d'] = 100000; $initial[15]['e'] = 0;
$initial[21]['m'] = 400; $initial[21]['k'] = 200; $initial[21]['d'] = 100; $initial[21]['e'] = 0;
$initial[22]['m'] = 2000; $initial[22]['k'] = 0; $initial[22]['d'] = 0; $initial[22]['e'] = 0;
$initial[23]['m'] = 2000; $initial[23]['k'] = 1000; $initial[23]['d'] = 0; $initial[23]['e'] = 0;
$initial[24]['m'] = 2000; $initial[24]['k'] = 2000; $initial[24]['d'] = 0; $initial[24]['e'] = 0;
$initial[31]['m'] = 200; $initial[31]['k'] = 400; $initial[31]['d'] = 200; $initial[31]['e'] = 0;
$initial[33]['m'] = 0; $initial[33]['k'] = 50000; $initial[33]['d'] = 100000; $initial[33]['e'] = 1000;
$initial[34]['m'] = 20000; $initial[34]['k'] = 40000; $initial[34]['d'] = 0; $initial[34]['e'] = 0;
$initial[44]['m'] = 20000; $initial[44]['k'] = 20000; $initial[44]['d'] = 1000; $initial[44]['e'] = 0;
// Луна
$initial[41]['m'] = 20000; $initial[41]['k'] = 40000; $initial[41]['d'] = 20000; $initial[41]['e'] = 0;
$initial[42]['m'] = 20000; $initial[42]['k'] = 40000; $initial[42]['d'] = 20000; $initial[42]['e'] = 0;
$initial[43]['m'] = 2000000; $initial[43]['k'] = 4000000; $initial[43]['d'] = 2000000; $initial[43]['e'] = 0;

// Флот
$initial[202]['m'] = 2000; $initial[202]['k'] = 2000; $initial[202]['d'] = 0;
$initial[203]['m'] = 6000; $initial[203]['k'] = 6000; $initial[203]['d'] = 0;
$initial[204]['m'] = 3000; $initial[204]['k'] = 1000; $initial[204]['d'] = 0;
$initial[205]['m'] = 6000; $initial[205]['k'] = 4000; $initial[205]['d'] = 0;
$initial[206]['m'] = 20000; $initial[206]['k'] = 7000; $initial[206]['d'] = 2000;
$initial[207]['m'] = 45000; $initial[207]['k'] = 15000; $initial[207]['d'] = 0;
$initial[208]['m'] = 10000; $initial[208]['k'] = 20000; $initial[208]['d'] = 10000;
$initial[209]['m'] = 10000; $initial[209]['k'] = 6000; $initial[209]['d'] = 2000;
$initial[210]['m'] = 0; $initial[210]['k'] = 1000; $initial[210]['d'] = 0;
$initial[211]['m'] = 50000; $initial[211]['k'] = 25000; $initial[211]['d'] = 15000;
$initial[212]['m'] = 0; $initial[212]['k'] = 2000; $initial[212]['d'] = 500;
$initial[213]['m'] = 60000; $initial[213]['k'] = 50000; $initial[213]['d'] = 15000;
$initial[214]['m'] = 5000000; $initial[214]['k'] = 4000000; $initial[214]['d'] =1000000;
$initial[215]['m'] = 30000; $initial[215]['k'] = 40000; $initial[215]['d'] = 15000;

// Оборона.
$initial[401]['m'] = 2000; $initial[401]['k'] = 0; $initial[401]['d'] = 0;
$initial[402]['m'] = 1500; $initial[402]['k'] = 500; $initial[402]['d'] = 0;
$initial[403]['m'] = 6000; $initial[403]['k'] = 2000; $initial[403]['d'] = 0;
$initial[404]['m'] = 20000; $initial[404]['k'] = 15000; $initial[404]['d'] = 2000;
$initial[405]['m'] = 2000; $initial[405]['k'] = 6000; $initial[405]['d'] = 0;
$initial[406]['m'] = 50000; $initial[406]['k'] = 50000; $initial[406]['d'] = 30000;
$initial[407]['m'] = 10000; $initial[407]['k'] = 10000; $initial[407]['d'] = 0;
$initial[408]['m'] = 50000; $initial[408]['k'] = 50000; $initial[408]['d'] = 0;
$initial[502]['m'] = 8000; $initial[502]['k'] = 0; $initial[502]['d'] = 2000;
$initial[503]['m'] = 12500; $initial[503]['k'] = 2500; $initial[503]['d'] = 10000;

// Исследования.
$initial[106]['m'] = 200; $initial[106]['k'] = 1000; $initial[106]['d'] = 200; $initial[106]['e'] = 0;
$initial[108]['m'] = 0; $initial[108]['k'] = 400; $initial[108]['d'] = 600; $initial[108]['e'] = 0;
$initial[109]['m'] = 800; $initial[109]['k'] = 200; $initial[109]['d'] = 0; $initial[109]['e'] = 0;
$initial[110]['m'] = 200; $initial[110]['k'] = 600; $initial[110]['d'] = 0; $initial[110]['e'] = 0;
$initial[111]['m'] = 1000; $initial[111]['k'] = 0; $initial[111]['d'] = 0; $initial[111]['e'] = 0;
$initial[113]['m'] = 0; $initial[113]['k'] = 800; $initial[113]['d'] = 400; $initial[113]['e'] = 0;
$initial[114]['m'] = 0; $initial[114]['k'] = 4000; $initial[114]['d'] = 2000; $initial[114]['e'] = 0;
$initial[115]['m'] = 400; $initial[115]['k'] = 0; $initial[115]['d'] = 600; $initial[115]['e'] = 0;
$initial[117]['m'] = 2000; $initial[117]['k'] = 4000; $initial[117]['d'] = 600; $initial[117]['e'] = 0;
$initial[118]['m'] = 10000; $initial[118]['k'] = 20000; $initial[118]['d'] = 6000; $initial[118]['e'] = 0;
$initial[120]['m'] = 200; $initial[120]['k'] = 100; $initial[120]['d'] = 0; $initial[120]['e'] = 0;
$initial[121]['m'] = 1000; $initial[121]['k'] = 300; $initial[121]['d'] = 100; $initial[121]['e'] = 0;
$initial[122]['m'] = 2000; $initial[122]['k'] = 4000; $initial[122]['d'] = 1000; $initial[122]['e'] = 0;
$initial[123]['m'] = 240000; $initial[123]['k'] = 400000; $initial[123]['d'] = 160000; $initial[123]['e'] = 0;
$initial[124]['m'] = 4000; $initial[124]['k'] = 8000; $initial[124]['d'] = 4000; $initial[124]['e'] = 0;
$initial[199]['m'] = 0; $initial[199]['k'] = 0; $initial[199]['d'] = 0; $initial[199]['e'] = 300000;

function BuildMeetRequirement ( $user, $planet, $id )
{
    if ( $planet['type'] == 0 )
    {
        if ( $id == 1 || $id == 2 || $id == 3 || $id == 4 || $id == 12 || $id == 15 || $id == 22 || $id == 23 || $id == 24 || $id == 31 || $id == 33 || $id == 44 ) return false;
    }
    else
    {
        if ( $id == 41 || $id == 42 || $id == 43 ) return false;
    }

    // Термоядерная электростанция => Синтезатор дейтерия (уровень 5), Энергетическая технология (уровень 3)
    // Фабрика нанитов => Фабрика роботов (уровень 10), Компьютерная технология (уровень 10)
    // Верфь => Фабрика роботов (уровень 2)
    // Терраформер => Фабрика нанитов (уровень 1), Энергетическая технология (уровень 12)
    // Ракетная шахта => Верфь (уровень 1)
    // Сенсорная фаланга => Лунная база (уровень 1)
    // Ворота => Лунная база (уровень 1), Гиперпространственная технология (уровень 7)
    if ( $id == 12 && ( $planet['b3'] < 5 || $user['r113'] < 3 ) ) return false;
    if ( $id == 15 && ( $planet['b14'] < 10 || $user['r108'] < 10 ) ) return false;
    if ( $id == 21 && ( $planet['b14'] < 2 ) ) return false;
    if ( $id == 33 && ( $planet['b15'] < 1 || $user['r113'] < 12 ) ) return false;
    if ( $id == 44 && ( $planet['b21'] < 1 ) ) return false;
    if ( $id == 42 && ( $planet['b41'] < 1 ) ) return false;
    if ( $id == 43 && ( $planet['b41'] < 1 || $user['r114'] < 7 ) ) return false;

    return true;
}

function BuildPrice ( $id, $lvl, &$m, &$k, &$d, &$e )
{
    global $initial;
    switch ($id)
    {
        case 1:   // Шахта металла
            $m = floor (60 * pow(1.5, $lvl-1));
            $k = floor (15 * pow(1.5, $lvl-1));
            $d = $e = 0;
            break;
        case 2:   // Шахта кристалла
            $m = floor (48 * pow(1.6, $lvl-1));
            $k = floor (24 * pow(1.6, $lvl-1));
            $d = $e = 0;
            break;
        case 3:   // Шахта дейта
            $m = floor (225 * pow(1.5, $lvl-1));
            $k = floor (75 * pow(1.5, $lvl-1));
            $d = $e = 0;
            break;
        case 4:   // СЭС
            $m = floor (75 * pow(1.5, $lvl-1));
            $k = floor (30 * pow(1.5, $lvl-1));
            $d = $e = 0;
            break;
        case 12:   // Терма
            $m = floor (900 * pow(1.8, $lvl-1));
            $k = floor (360 * pow(1.8, $lvl-1));
            $d = floor (180 * pow(1.8, $lvl-1));
            $e = 0;
            break;
        default:
            $m = $initial[$id]['m'] * pow(2, $lvl-1);
            $k = $initial[$id]['k'] * pow(2, $lvl-1);
            $d = $initial[$id]['d'] * pow(2, $lvl-1);
            $e = $initial[$id]['e'] * pow(2, $lvl-1);
            break;
    }
}

// Время строительства постройки $id уровня $lvl в секундах.
function BuildDuration ( $id, $lvl, $robots, $nanits, $speed )
{
    $m = $k = $d = $e = 0;
    BuildPrice ( $id, $lvl, &$m, &$k, &$d, &$e );
    $secs = ( ( ($m + $k) / (2500 * (1 + $robots)) ) * pow (0.5, $nanits) * 60*60 ) / $speed;
    if ($secs < 1) $secs = 1;
    return $secs;
}

function ShipyardMeetRequirement ( $user, $planet, $id )
{
    if ( $id == 202 && ( $planet['b21'] < 2  || $user['r115'] < 2 ) ) return false;
    if ( $id == 203 && ( $planet['b21'] < 4  || $user['r115'] < 6 ) ) return false;
    if ( $id == 204 && ( $planet['b21'] < 1  || $user['r115'] < 1 ) ) return false;
    if ( $id == 205 && ( $planet['b21'] < 3  || $user['r111'] < 2 || $user['r117'] < 2 ) ) return false;
    if ( $id == 206 && ( $planet['b21'] < 5  || $user['r117'] < 4 || $user['r121'] < 2 ) ) return false;
    if ( $id == 207 && ( $planet['b21'] < 7  || $user['r118'] < 4 ) ) return false;
    if ( $id == 208 && ( $planet['b21'] < 4  || $user['r117'] < 3 ) ) return false;
    if ( $id == 209 && ( $planet['b21'] < 4  || $user['r115'] < 6 || $user['r110'] < 2 ) ) return false;
    if ( $id == 210 && ( $planet['b21'] < 3  || $user['r115'] < 3 || $user['r106'] < 2 ) ) return false;
    if ( $id == 211 && ( $planet['b21'] < 8  || $user['r117'] < 6 || $user['r122'] < 5 ) ) return false;
    if ( $id == 212 && ( $planet['b21'] < 1  ) ) return false;
    if ( $id == 213 && ( $planet['b21'] < 9  || $user['r118'] < 6 || $user['r114'] < 5 ) ) return false;
    if ( $id == 214 && ( $planet['b21'] < 12 || $user['r118'] < 7 || $user['r114'] < 6 || $user['r199'] < 1 ) ) return false;
    if ( $id == 215 && ( $planet['b21'] < 8  || $user['r114'] < 5 || $user['r120'] < 12 || $user['r118'] < 5 ) ) return false;

    if ( $id == 401 && ( $planet['b21'] < 1 ) ) return false;
    if ( $id == 402 && ( $planet['b21'] < 2 || $user['r113'] < 1 || $user['r120'] < 3 ) ) return false;
    if ( $id == 403 && ( $planet['b21'] < 4 || $user['r113'] < 3 || $user['r120'] < 6 ) ) return false;
    if ( $id == 404 && ( $planet['b21'] < 6 || $user['r113'] < 6 || $user['r109'] < 3 || $user['r110'] < 1 ) ) return false;
    if ( $id == 405 && ( $planet['b21'] < 4 || $user['r121'] < 4 ) ) return false;
    if ( $id == 406 && ( $planet['b21'] < 8 || $user['r122'] < 7 ) ) return false;
    if ( $id == 407 && ( $planet['b21'] < 1 || $user['r110'] < 2 ) ) return false;
    if ( $id == 408 && ( $planet['b21'] < 6 || $user['r110'] < 6 ) ) return false;
    if ( $id == 502 && ( $planet['b21'] < 1 || $planet['b44'] < 2 ) ) return false;
    if ( $id == 503 && ( $planet['b21'] < 1 || $planet['b44'] < 4 || $user['r117'] < 1 ) ) return false;

    return true;
}

function ShipyardPrice ( $id, &$m, &$k, &$d, &$e )
{
    global $initial;
    $m = $initial[$id]['m'];
    $k = $initial[$id]['k'];
    $d = $initial[$id]['d'];
    $e = 0;
}

function ShipyardDuration ( $id, $shipyard, $nanits, $speed )
{
    $m = $k = $d = $e = 0;
    ShipyardPrice ($id, &$m, &$k, &$d, &$e );
    $secs = ( ( ($m + $k) / (2500 * (1 + $shipyard)) ) * pow (0.5, $nanits) * 60*60 ) / $speed;
    if ($secs < 1) $secs = 1;
    return $secs;
}

function ResearchMeetRequirement ( $user, $planet, $id )
{
    if ( $id == 110 && ( $user['r113'] < 3 ) ) return false;
    if ( $id == 114 && ( $user['r113'] < 5 || $user['r110'] < 5 ) ) return false;
    if ( $id == 115 && ( $user['r113'] < 1 ) ) return false;
    if ( $id == 117 && ( $user['r113'] < 1 ) ) return false;
    if ( $id == 118 && ( $user['r114'] < 3 ) ) return false;
    if ( $id == 120 && ( $user['r113'] < 2 ) ) return false;
    if ( $id == 121 && ( $user['r120'] < 5 || $user['r113'] < 4 ) ) return false;
    if ( $id == 122 && ( $user['r113'] < 8 || $user['r120'] < 10 || $user['r121'] < 5) ) return false;
    if ( $id == 123 && ( $user['r108'] < 8 || $user['r114'] < 8 ) ) return false;
    if ( $id == 124 && ( $user['r106'] < 4 || $user['r117'] < 3 ) ) return false;
    if ( $id == 199 && ( $planet['b31'] < 12 ) ) return false;

    return true;
}

function ResearchPrice ( $id, $lvl, &$m, &$k, &$d, &$e )
{
    global $initial;
    if ($id == 199) {
        $m = $initial[$id]['m'] * pow(3, $lvl-1);
        $k = $initial[$id]['k'] * pow(3, $lvl-1);
        $d = $initial[$id]['d'] * pow(3, $lvl-1);
        $e = $initial[$id]['e'] * pow(3, $lvl-1);
    }
    else {
        $m = $initial[$id]['m'] * pow(2, $lvl-1);
        $k = $initial[$id]['k'] * pow(2, $lvl-1);
        $d = $initial[$id]['d'] * pow(2, $lvl-1);
        $e = $initial[$id]['e'] * pow(2, $lvl-1);
    }
}

function ResearchDuration ( $id, $lvl, $reslab, $speed )
{
    if ( $id == 199 ) return 1;
    $m = $k = $d = $e = 0;
    ResearchPrice ($id, $lvl, &$m, &$k, &$d, &$e );
    $secs = floor ( ( ($m + $k) / (1000 * (1 + $reslab)) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return $secs;
}

// Возвратить строку длительности по дням, часам, минутам, секундам.
function BuildDurationFormat ( $seconds )
{
    $res = "";
    $days = floor ($seconds / (24*3600));
    $hours = floor ($seconds / 3600 % 24);
    $mins = floor ($seconds  / 60 % 60);
    $secs = round ( $seconds / 1 % 60);
    if ($days) {
        //if ($days==1) $res .= "$days"."день ";
        //else $res .= "$days"."дн. ";
        $res .= "$days"."дн. ";
    }
    if ($hours || $days) $res .= "$hours"."ч ";
    if ($mins || $days) $res .= "$mins"."мин ";
    if ($secs) $res .= "$secs"."сек";
    return $res;
}

function IsEnoughResources ($planet, $m, $k, $d, $e)
{
    if ( $m && $planet['m'] < $m ) return false;
    if ( $k && $planet['k'] < $k ) return false;
    if ( $d && $planet['d'] < $d ) return false;
    if ( $e && $planet['e'] < $e ) return false;
    return true;
}

// Всё что связано с добычей и подсчетом ресурсов.

// Получить размер хранилищ.
function store_capacity ($lvl) { return 100000 + 50000 * (ceil (pow (1.6, $lvl) - 1)); }

// Выработка энергии
function prod_solar ($lvl, $pr)
{
    $prod = floor (20 * $lvl * pow (1.1, $lvl) * $pr);
    return $prod;
}
function prod_fusion ($lvl, $energo, $pr)
{
    $prod = floor (30 * $lvl * pow (1.05 + $energo*0.01, $lvl) * $pr);
    return $prod;
}
function prod_sat ($maxtemp)
{
    $prod = floor (($maxtemp / 4) + 20);
    if ($prod > 50) $prod = 50;
    return $prod;
}

// Выработка шахт
function prod_metal ($lvl, $pr) { return floor (30 * $lvl * pow (1.1, $lvl) * $pr); }
function prod_crys ($lvl, $pr) { return floor (20 * $lvl * pow (1.1, $lvl) * $pr); }
function prod_deut ($lvl, $maxtemp, $pr) { return floor ( 10 * $lvl * pow (1.1, $lvl) * $pr) * (1.28 - 0.002 * ($maxtemp)); }

// Потребление энергии
function cons_metal ($lvl) { return ceil (10 * $lvl * pow (1.1, $lvl)); }
function cons_crys ($lvl) { return ceil (10 * $lvl * pow (1.1, $lvl)); }
function cons_deut ($lvl) { return ceil (20 * $lvl * pow (1.1, $lvl)); }

// Потребление дейта термоядом
function cons_fusion ($lvl, $pr) { return ceil (10 * $lvl * pow (1.1, $lvl) * $pr) ; }

// Расчитать прирост ресурсов. Ограничить емкостью хранилищ.
// ВНИМАНИЕ: Из расчета исключаются внешние события, типа окончания действия офицеров, атаки другого игрока, завершение постройки здания итп.
function ProdResources ( $planet_id, $time_from, $time_to )
{
    global $db_prefix;
    $planet = GetPlanet ( $planet_id );
    if ( $planet['type'] == 0) return;        // луна
    $diff = $time_to - $time_from;

    $unitab = LoadUniverse ( );
    $speed = $unitab['speed'];

    $hourly = prod_metal ($planet['b1'], $planet['mprod']) * $planet['factor'] * $speed + 20 * $speed;        // Металл
    if ( $planet['m'] < $planet['mmax'] ) {
        $planet['m'] += ($hourly * $diff) / 3600;
        if ( $planet['m'] >= $planet['mmax'] ) $planet['m'] = $planet['mmax'];
    }

    $hourly = prod_crys ($planet['b2'], $planet['kprod']) * $planet['factor'] * $speed + 10 * $speed;        // Кристалл
    if ( $planet['k'] < $planet['kmax'] ) {
        $planet['k'] += ($hourly * $diff) / 3600;
        if ( $planet['k'] >= $planet['kmax'] ) $planet['k'] = $planet['kmax'];
    }

    $hourly = prod_deut ($planet['b3'], $planet['temp']+40, $planet['dprod']) * $planet['factor'] * $speed;    // Дейтерий
	$hourly -= cons_fusion ( $planet['b12'], $planet['fprod'] ) * $speed;	// термояд
    if ( $planet['d'] < $planet['dmax'] ) {
        $planet['d'] += ($hourly * $diff) / 3600;
        if ( $planet['d'] >= $planet['dmax'] ) $planet['d'] = $planet['dmax'];
    }

    $query = "UPDATE ".$db_prefix."planets SET m = '".$planet['m']."', k = '".$planet['k']."', d = '".$planet['d']."', lastpeek = '".$time_to."' WHERE planet_id = $planet_id";
    dbquery ($query);
}

?>