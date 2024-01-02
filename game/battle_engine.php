<?php

$battle_debug = 0;

if ($battle_debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require_once "unit.php";

// Запасной боевой движок на PHP.
// Если сервер не поддерживает выполнение функции system(), то используется реализация боевого движка на PHP

// Как происходит запуск и интеграция боевого движка на PHP:
// - Движок получает на вход и выдаёт на выход данные, аналогичные боевому движку на C; Только получает и выдаёт он их в виде строк.
// - Дальше всё тривиально: парсим входные данные, производим расчёты, возвращаем результат
// - Выбор между внешним боевым движком и внутренним (на PHP) выбирается настройкой вселенной uni['php_battle']: если она равна 1, то использовать движок на PHP
// - Вызыватель (battle.php) по честному притворяется и генерит файлы, аналогично при работе с внешним движком. Это может оказаться полезным для "разбора полётов" (история логов)

/*
Работа PHP движка с технической точки зрения.
Все массивы хранятся в виде длинных строк. Доступ к элементу arr[i] осуществляется конструкцией ord($arr{$i}), запись $arr{$i} = chr(n).
Сделано это для экономии памяти - строка занимает столько-же байт, сколько и символов в ней, а ассоциативные массивы в PHP достаточно прожорливые.

Массивы-строки разделены на две одинаковые группы - атакующие и обороняющиеся. Каждая группа разделена на несколько массивов-строк, причем все слоты совмещены (для упрощения индексации случайных выстрелов, от 0 до N):
$obj = { id, id, id, ... }     -- массив юнитов, для того чтобы номера объектов умещались в один байт (для экономии памяти), нумерация флота начинается от 02 (вместо 202), а обороны от 201 (вместо 401).  (n-200)
$slot = { n, n, n, ... }       -- номер слота юнита (для САБ), это нужно для генерации боевого доклада, чтобы рассортировать потом юниты по слотам.
$explo = { 1, 0, 1, ... }      -- массив взорванных юнитов, после каждого раунда взорванные юниты удаляются и формируется новый массив $obj. TODO: данные находятся в упакованном формате 8 юнитов на 1 байт
$shld = { }                    -- щиты юнитов. перед началом каждого раунда этот массив заполняется максимальными значениями (щиты заряжаются). запакованный 4-байтовый массив-строка
$hull = { }                    -- текущее значение брони, учитывая повреждения для юнита n. запакованный 4-байтовый массив-строка

Отладка: просто откройте http://localhost/game/battle_engine.php с установленной переменной $battle_debug = 1.

*/

// Для отладки преобразовать массив-строку в читабельный формат
function hex_array_to_text ($arr)
{
    return implode(unpack("H*", $arr));
}

function get_packed_word (&$arr, $idx)
{
    return (ord($arr{4*$idx}) << 24) | 
        (ord($arr{4*$idx+1}) << 16) | 
        (ord($arr{4*$idx+2}) << 8) |
        (ord($arr{4*$idx+3}) << 0);
}

function set_packed_word (&$arr, $idx, $val)
{
    $arr{4*$idx} = chr(($val >> 24) & 0xff);
    $arr{4*$idx+1} = chr(($val >> 16) & 0xff);
    $arr{4*$idx+2} = chr(($val >> 8) & 0xff);
    $arr{4*$idx+3} = chr(($val >> 0) & 0xff);
}

// Выделить память для юнитов и установить начальные значения.
function InitBattle ($slot, $num, $objs, $attacker, &$explo_arr, &$obj_arr, &$slot_arr, &$hull_arr, &$shld_arr )
{
    global $UnitParam;

    $amap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $dmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $ucnt = 0;
    $slot_id = 0;

    for ($i=0; $i<$num; $i++) {

        foreach ( $amap as $n=>$gid ) {

            for ($obj=0; $obj<$slot[$i][$gid]; $obj++) {

                $hull = $UnitParam[$gid][0] * 0.1 * (10+$slot[$i]['armr']) / 10;
                $obj_type = $gid - 200;

                $explo_arr{$ucnt} = chr(0);
                $obj_arr{$ucnt} = chr($obj_type);
                $slot_arr{$ucnt} = chr($slot_id);
                set_packed_word ($hull_arr, $ucnt, $hull);
                set_packed_word ($shld_arr, $ucnt, 0);

                $ucnt++;
            }
        }

        if (!$attacker) {

            foreach ( $dmap as $n=>$gid ) {

                for ($obj=0; $obj<$slot[$i][$gid]; $obj++) {

                    $hull = $UnitParam[$gid][0] * 0.1 * (10+$slot[$i]['armr']) / 10;
                    $obj_type = $gid - 200;

                    $explo_arr{$ucnt} = chr(0);
                    $obj_arr{$ucnt} = chr($obj_type);
                    $slot_arr{$ucnt} = chr($slot_id);
                    set_packed_word ($hull_arr, $ucnt, $hull);
                    set_packed_word ($shld_arr, $ucnt, 0);

                    $ucnt++;
                }
            }
        }

        $slot_id++;
    }
}

function WipeExploded ($count, &$explo_arr, &$obj_arr, &$slot_arr, &$hull_arr, &$shld_arr)
{
    $exploded = 0;
    $dst = 0;

    // Новые массивы
    $explo_new = "";
    $obj_new = "";
    $slot_new = "";
    $hull_new = "";
    $shld_new = "";

    for ($i=0; $i<$count; $i++) {

        if ( ord($explo_arr{$i}) == 0 ) {

            // Если не взорван перенести в новый массив
            $explo_new{$dst} = chr(0);
            $obj_new{$dst} = $obj_arr{$i};
            $slot_new{$dst} = $slot_arr{$i};
            set_packed_word ($hull_new, $dst, get_packed_word($hull_arr, $i) );
            set_packed_word ($shld_new, $dst, get_packed_word($shld_arr, $i) );

            $dst++;
        }
        else {

            // Иначе пропустить (почистить)
            $exploded++;
        }
    }

    // Освободить старые массивы
    unset ($explo_arr);
    unset ($obj_arr);
    unset ($slot_arr);
    unset ($hull_arr);
    unset ($shld_arr);

    // Обновить исходные массивы
    $explo_arr = $explo_new;
    $obj_arr = $obj_new;
    $slot_arr = $slot_new;
    $hull_arr = $hull_new;
    $shld_arr = $shld_new;

    // TODO: Использовать remap таблицу для взорванных юнитов? Может так будет быстрее..

    return $exploded;
}

// Проверить возможность повторного выстрела. Для удобства используются оригинальные ID юнитов
function RapidFire ($atyp, $dtyp)
{
    $rapidfire = 0;

    if ( $atyp > 400 ) return 0;

    // ЗСка против ШЗ/ламп
    if ($atyp==214 && ($dtyp==210 || $dtyp==212) && mt_rand(1,10000)>8) $rapidfire = 1;
    // остальной флот против ШЗ/ламп
    else if ($atyp!=210 && ($dtyp==210 || $dtyp==212) && mt_rand(1,100)>20) $rapidfire = 1;
    // ТИ против МТ
    else if ($atyp==205 && $dtyp==202 && mt_rand(1,100)>33) $rapidfire = 1;
    // крейсер против ЛИ
    else if ($atyp==206 && $dtyp==204 && mt_rand(1,1000)>166) $rapidfire = 1;
    // крейсер против РУ
    else if ($atyp==206 && $dtyp==401 && mt_rand(1,100)>10) $rapidfire = 1;
    // бомбер против легкой обороны
    else if ($atyp==211 && ($dtyp==401 || $dtyp==402) && mt_rand(1,100)>20) $rapidfire = 1;
    // бомбер против средней обороны
    else if ($atyp==211 && ($dtyp==403 || $dtyp==405) && mt_rand(1,100)>10) $rapidfire = 1;
    // уник против ЛК
    else if ($atyp==213 && $dtyp==215 && mt_rand(1,100)>50) $rapidfire = 1;
    // уник против ЛЛ
    else if ($atyp==213 && $dtyp==402 && mt_rand(1,100)>10) $rapidfire = 1;
    // ЛК против транспорта
    else if ($atyp==215 && ($dtyp==202 || $dtyp==203) && mt_rand(1,100)>20) $rapidfire = 1;
    // ЛК против среднего флота
    else if ($atyp==215 && ($dtyp==205 || $dtyp==206) && mt_rand(1,100)>25) $rapidfire = 1;
    // ЛК против линкоров
    else if ($atyp==215 && $dtyp==207 && mt_rand(1,1000)>143) $rapidfire = 1;
    // ЗС против гражданского флота
    else if ($atyp==214 && ($dtyp==202 || $dtyp==203 || $dtyp==208 || $dtyp==209) && mt_rand(1,1000)>4) $rapidfire = 1;
    // ЗС против ЛИ
    else if ($atyp==214 && $dtyp==204 && mt_rand(1,1000)>5) $rapidfire = 1;
    // ЗС против ТИ
    else if ($atyp==214 && $dtyp==205 && mt_rand(1,1000)>10) $rapidfire = 1;
    // ЗС против крейсеров
    else if ($atyp==214 && $dtyp==206 && mt_rand(1,1000)>30) $rapidfire = 1;
    // ЗС против линкоров
    else if ($atyp==214 && $dtyp==207 && mt_rand(1,1000)>33) $rapidfire = 1;
    // ЗС против бомберов
    else if ($atyp==214 && $dtyp==211 && mt_rand(1,1000)>40) $rapidfire = 1;
    // ЗС против уников
    else if ($atyp==214 && $dtyp==213 && mt_rand(1,1000)>200) $rapidfire = 1;
    // ЗС против линеек
    else if ($atyp==214 && $dtyp==215 && mt_rand(1,1000)>66) $rapidfire = 1;
    // ЗС против легкой обороны
    else if ($atyp==214 && ($dtyp==401 || $dtyp==402) && mt_rand(1,1000)>5) $rapidfire = 1;
    // ЗС против средней обороны
    else if ($atyp==214 && ($dtyp==403 || $dtyp==405) && mt_rand(1,1000)>10) $rapidfire = 1;
    // ЗС против тяжелой обороны
    else if ($atyp==214 && $dtyp==404 && mt_rand(1,1000)>20) $rapidfire = 1;

    return $rapidfire;
}

function DoBattle (&$res)
{
    global $battle_debug;

    // Набор рабочих строк-массивов для вычислений. Массивы щитов и брони используют запаковку длинных чисел

    $obj_att = "";
    $slot_att = "";
    $explo_att = "";
    $shld_att = "";
    $hull_att = "";

    $obj_def = "";
    $slot_def = "";
    $explo_def = "";
    $shld_def = "";
    $hull_def = "";

    // Статистика по выстрелам

    $shoots = array();
    $spower = array();
    $absorbed = array();

    // Поле обломков

    $dm = $dk = 0;

    $amap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $dmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $anum = count ($res['before']['attackers']);
    $dnum = count ($res['before']['defenders']);

    // Посчитать количество юнитов до боя

    $aobjs = 0;
    $dobjs = 0;

    for ($i=0; $i<$anum; $i++) {
        foreach ( $amap as $n=>$gid ) {
            $aobjs += $res['before']['attackers'][$i][$gid];
        }
    }

    for ($i=0; $i<$dnum; $i++) {
        foreach ( $amap as $n=>$gid ) {
            $dobjs += $res['before']['defenders'][$i][$gid];
        }
        foreach ( $dmap as $n=>$gid ) {
            $dobjs += $res['before']['defenders'][$i][$gid];
        }
    }

    // Подготовить массивы боевых единиц

    InitBattle ($res['before']['attackers'], $anum, $aobjs, 1, $explo_att, $obj_att, $slot_att, $hull_att, $shld_att);
    InitBattle ($res['before']['defenders'], $dnum, $dobjs, 0, $explo_def, $obj_def, $slot_def, $hull_def, $shld_def);

    // Раунды

    $res['rounds'] = array();

    for ($round=0; $round<6; $round++) {

        if ($aobjs == 0 || $dobjs == 0) break;

        // Сбросить статистику.
        $shoots[0] = $shoots[1] = 0;
        $spower[0] = $spower[1] = 0;
        $absorbed[0] = $absorbed[1] = 0;

        // Зарядить щиты.

        // Произвести выстрелы.

        // Быстрая ничья?

        $fastdraw = false;

        // Вычистить взорванные корабли и оборону.

        $aobjs -= WipeExploded ($aobjs, $explo_att, $obj_att, $slot_att, $hull_att, $shld_att);
        $dobjs -= WipeExploded ($dobjs, $explo_def, $obj_def, $slot_def, $hull_def, $shld_def);

        // Сохранить результаты раунда

        $res['rounds'][$round] = array();
        $r = &$res['rounds'][$round];

        $r['ashoot'] = $shoots[0];
        $r['apower'] = $spower[0];
        $r['dabsorb'] = $absorbed[1];
        $r['dshoot'] = $shoots[1];
        $r['dpower'] = $spower[1];
        $r['aabsorb'] = $absorbed[0];

        $r['attackers'] = array();

        for ($slot=0; $slot<$anum; $slot++) {

            $r['attackers'][$slot]['name'] = $res['before']['attackers'][$slot]['name'];
            $r['attackers'][$slot]['id'] = $res['before']['attackers'][$slot]['id'];
            $r['attackers'][$slot]['g'] = $res['before']['attackers'][$slot]['g'];
            $r['attackers'][$slot]['s'] = $res['before']['attackers'][$slot]['s'];
            $r['attackers'][$slot]['p'] = $res['before']['attackers'][$slot]['p'];

            foreach ( $amap as $n=>$gid ) {
                $r['attackers'][$slot][$gid] = 0;
            }

            for ($i=0; $i<$aobjs; $i++) {
                $obj_id = ord($obj_att{$i}) + 200;
                $r['attackers'][$slot][$obj_id]++;
            }
        }

        $r['defenders'] = array();

        for ($slot=0; $slot<$dnum; $slot++) {

            $r['defenders'][$slot]['name'] = $res['before']['defenders'][$slot]['name'];
            $r['defenders'][$slot]['id'] = $res['before']['defenders'][$slot]['id'];
            $r['defenders'][$slot]['g'] = $res['before']['defenders'][$slot]['g'];
            $r['defenders'][$slot]['s'] = $res['before']['defenders'][$slot]['s'];
            $r['defenders'][$slot]['p'] = $res['before']['defenders'][$slot]['p'];

            foreach ( $amap as $n=>$gid ) {
                $r['defenders'][$slot][$gid] = 0;
            }
            foreach ( $dmap as $n=>$gid ) {
                $r['defenders'][$slot][$gid] = 0;
            }

            for ($i=0; $i<$dobjs; $i++) {
                $obj_id = ord($obj_def{$i}) + 200;
                $r['defenders'][$slot][$obj_id]++;
            }
        }

        if ($fastdraw) break;
    }

    // Результаты боя.

    if ($aobjs > 0 && $dobjs == 0){ // Атакующий выиграл
        $res['result'] = "awon";
    }
    else if ($dobjs > 0 && $aobjs == 0) { // Атакующий проиграл
        $res['result'] = "dwon";
    }
    else    // Ничья
    {
        $res['result'] = "draw";
    }

    $res['dm'] = $dm;
    $res['dk'] = $dk;

    // Сохранить статистику выделений памяти

    $res['peak_allocated'] = memory_get_usage();

    // Почистить память

    unset($obj_att);
    unset($slot_att);
    unset($explo_att);
    unset($shld_att);
    unset($hull_att);

    unset($obj_def);
    unset($slot_def);
    unset($explo_def);
    unset($shld_def);
    unset($hull_def);
}

function extract_text ($str, $s, $e)
{
    $start  = strpos($str, $s);
    $end    = strpos($str, $e, $start + 1);
    $length = $end - $start;
    $result = trim (substr($str, $start + 1, $length - 1));
    return $result;
}

function deserialize_slot ($str, $att)
{
    $amap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $dmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $res = array();
    $items = explode (" ", $str);

    $res['name'] = extract_text ($items[0], '{', '}');
    $res['id'] = intval ($items[1]);
    $res['g'] = intval ($items[2]);
    $res['s'] = intval ($items[3]);
    $res['p'] = intval ($items[4]);
    $res['weap'] = intval ($items[5]);
    $res['shld'] = intval ($items[6]);
    $res['armr'] = intval ($items[7]);

    foreach ( $amap as $n=>$gid ) {
        $res[$gid] = intval ($items[8+$n]);
    }
    if (!$att) {
        foreach ( $dmap as $n=>$gid ) {
            $res[$gid] = intval ($items[22+$n]);
        }
    }

    return $res;
}

// Распарсить входные данные
function ParseInput ($source, &$rf, &$fid, &$did, &$attackers, &$defenders)
{
    global $battle_debug;

    $kv = array();

    // Расщепить входные данные на строки
    $arr = explode("\n", $source);

    // Расщепить строки на пары ключ/значение
    foreach ($arr as $line) {
        if (empty($line)) {
            continue;
        }
        $pair = explode ("=", $line);
        $kv[trim($pair[0])] = trim($pair[1]);
    }

    // DEBUG
    if ($battle_debug) {
        echo "Parsed input data as key-value:<br/>";
        echo "<pre>";
        print_r ($kv);
        echo "</pre>";
    }

    // Распихать параметры куда нужно
    $rf = intval ($kv['Rapidfire']);
    $fid = intval ($kv['FID']);
    $did = intval ($kv['DID']);

    if ($did < 0) $did = 0;
    if ($fid < 0) $fid = 0;
    if ($did > 100) $did = 100;
    if ($fid > 100) $fid = 100;

    $anum = intval ($kv['Attackers']);
    $dnum = intval ($kv['Defenders']);

    for ($i=0; $i<$anum; $i++) {

        $slot_text = extract_text ($kv['Attacker' . $i], '(', ')');
        $attackers[$i] = deserialize_slot ($slot_text, true);
    }

    for ($i=0; $i<$dnum; $i++) {

        $slot_text = extract_text ($kv['Defender' . $i], '(', ')');
        $defenders[$i] = deserialize_slot ($slot_text, false);
    }
}

// На выходе массив battleresult, формат аналогичный формату боевого движка на Си.
function BattleEngine ($source)
{
    global $battle_debug;

    // Настройки боевого движка по умолчанию
    $rf = 1;
    $fid = 30;
    $did = 0;

    // Выходной результат
    $res = array ();

    // Инициализировать ДСЧ
    list($usec,$sec)=explode(" ",microtime());
    $battle_seed = (int)($sec * $usec) & 0xffffffff;
    mt_srand ($battle_seed);
    $res['battle_seed'] = $battle_seed;

    // Исходные слоты атакующих и защитников
    $res['before'] = array();
    $res['before']['attackers'] = array();
    $res['before']['defenders'] = array();

    // Разобрать входные данные
    ParseInput ($source, $rf, $fid, $did, $res['before']['attackers'], $res['before']['defenders']);
    if ($battle_debug) {
        echo "rf = $rf, fid = $fid, did = $did<br/>";
    }

    // **** НАЧАТЬ БИТВУ ****
    DoBattle ($res);

    return $res;
}

function BattleDebug()
{

$starttime = microtime(true);
$allocated_before = memory_get_usage();

?>

<html> 
 <head> 
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel="stylesheet" type="text/css" href="../evolution/formate.css" />
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <link rel="stylesheet" type="text/css" href="css/combox.css">
 </head>
<body>

<!-- CONTENT AREA -->
<div id='content'>

<?php

// DEBUG

$source = "Rapidfire = 1
FID = 70
DID = 0
Attackers = 14
Defenders = 1
Attacker0 = ({OtellO} 252298 1 2 10 13 13 13 0 0 0 0 0 0 0 0 0 0 0 0 0 4613 )
Attacker1 = ({Voskreshaya} 252302 1 6 9 13 11 14 5490 0 16379 0 0 123 0 0 0 0 0 367 0 0 )
Attacker2 = ({r2r} 252312 1 15 6 14 13 15 2055 0 0 0 0 0 0 0 0 0 0 0 0 0 )
Attacker3 = ({onelife} 252310 1 4 7 14 14 15 0 0 0 0 0 0 0 0 0 0 0 0 0 3100 )
Attacker4 = ({Voskreshaya} 252301 1 6 9 13 11 14 0 0 0 0 5020 0 0 0 0 0 0 0 0 0 )
Attacker5 = ({r2r} 252307 1 15 6 14 13 15 0 0 0 0 778 0 0 0 0 0 0 0 0 0 )
Attacker6 = ({onelife} 252309 1 4 7 14 14 15 0 0 0 0 2755 0 0 0 0 0 0 0 0 0 )
Attacker7 = ({r2r} 252305 1 15 6 14 13 15 0 0 6527 0 0 0 0 0 0 0 0 1422 0 0 )
Attacker8 = ({onelife} 252250 1 4 7 14 14 15 0 1 0 0 0 0 0 0 0 0 0 0 0 0 )
Attacker9 = ({Voskreshaya} 252300 1 6 9 13 11 14 0 0 0 0 0 0 0 0 0 0 0 0 0 1341 )
Attacker10 = ({onelife} 252308 1 4 7 14 14 15 0 0 7000 0 0 0 0 0 0 0 0 1400 0 0 )
Attacker11 = ({OtellO} 252351 1 2 10 13 13 13 0 0 0 0 4342 0 0 0 0 0 0 0 0 0 )
Attacker12 = ({onelife} 252311 1 4 7 14 14 15 2510 0 0 0 0 0 0 0 0 0 0 0 0 0 )
Attacker13 = ({r2r} 252306 1 15 6 14 13 15 0 0 0 0 0 0 0 0 0 0 0 0 0 848 )
Defender0 = ({ilk} 10336 1 14 5 14 15 15 956 927 12394 657 1268 1045 3 1587 23 14 0 898 1 2108 92 0 0 0 0 0 0 0 )
";

$res = BattleEngine ( $source );

echo "<br/><br/>Result:<br/>";
echo "<pre>";
print_r ( $res );
echo "</pre>";

$endtime = microtime(true);
$allocated_after = memory_get_usage();

printf("Page loaded in %f seconds. Allocated before %d bytes, allocated after %d bytes, unallocated %d bytes", 
    $endtime - $starttime, 
    $allocated_before, 
    $allocated_after,
    $allocated_after - $allocated_before );

?>

</div>
<!-- END CONTENT AREA -->

</body>
</html>

<?php

}   // BattleDebug()

if ($battle_debug) {
    BattleDebug();
}

?>