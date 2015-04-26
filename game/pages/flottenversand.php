<?php

// Отправка флота с проверкой всех параметров.
// Если флот отправлен успешно - вывести краткую информацию, иначе вывести ошибку.
// Через 3 секунды делается редирект на первую страницу отправки флота.

$BlockAttack = 0;

/*
Список типов заданий:
1 - Атака
2 - Совместная атака
3 - Транспорт
4 - Оставить
5 - Держаться
6 - Шпионаж
7 - Колонизировать
8 - Переработать
9 - Уничтожить
15 - Экспедиция
21 - Атака САБ (паровоз)
*/

$PageError = "";
$FleetError = false;
$FleetErrorText = "";

// Вывести текст ошибки отправки флота.
function FleetError ($text)
{
    global $FleetError, $FleetErrorText;
    $FleetErrorText .= "   <tr height=\"20\">\n";
    $FleetErrorText .= "   <th><span class=\"error\">$text</span></th>\n";
    $FleetErrorText .= "  </tr>\n";
    $FleetError = true;
}

// Если страница открыта через браузер, то сделать редирект на главную.
if ( method () === "GET" )
{
    RedirectHome ();
    die ();
}

$session = $_GET['session'];
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );

$unitab = LoadUniverse ();
$unispeed = $unitab['fspeed'];

// Обработать вызовы AJAX
if ( key_exists ( 'ajax', $_GET ) ) {
    if ( $_GET['ajax'] == 1) include "flottenversand_ajax.php";
}

// Нельзя отправлять флот, если предыдущий отправлен менее секунды назад.
$result = EnumOwnFleetQueueSpecial ( $GlobalUser['player_id'] );
$rows = dbrows ($result);
if ( $rows ) {
    $queue = dbarray ($result);
    if ( abs(time () - $queue['start']) < 1 ) MyGoto ( "flotten1" );
}

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "fleetorder", $GlobalUni['lang'] );
loca_add ( "fleet", $GlobalUni['lang'] );

$result = EnumOwnFleetQueue ( $GlobalUser['player_id'] );
$nowfleet = dbrows ($result);
$maxfleet = $GlobalUser['r108'] + 1;

$prem = PremiumStatus ($GlobalUser);
if ( $prem['admiral'] ) $maxfleet += 2;

$fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

// Ограничить скорость и сделать её кратной 10.
$fleetspeed = round ( abs(intval($_POST['speed']) * 10) / 10) * 10;
$fleetspeed = min ( max (10, $fleetspeed), 100 ) / 10;

// Превратить все пустые параметры в нули.

if ( !key_exists('resource1', $_POST) ) $_POST['resource1'] = 0;
if ( !key_exists('resource2', $_POST) ) $_POST['resource2'] = 0;
if ( !key_exists('resource3', $_POST) ) $_POST['resource3'] = 0;

$resource1 = min ( intval($aktplanet['m']), abs(intval($_POST['resource1'])) );
$resource2 = min ( intval($aktplanet['k']), abs(intval($_POST['resource2'])) );
$resource3 = min ( intval($aktplanet['d']), abs(intval($_POST['resource3'])) );

foreach ($fleetmap as $i=>$gid)
{
    if ( !key_exists("ship$gid", $_POST) ) $_POST["ship$gid"] = 0;
}

$order = intval($_POST['order']);
$union_id = 0;

// Список флотов.
$fleet = array ();
foreach ($fleetmap as $i=>$gid) 
{
    if ( key_exists("ship$gid", $_POST) ) $fleet[$gid] = min ( $aktplanet["f$gid"], intval($_POST["ship$gid"]) );
    else $fleet[$gid] = 0;
}
$fleet[212] = 0;        // солнечные спутники не летают.

$origin = LoadPlanet ( intval($_POST['thisgalaxy']), intval($_POST['thissystem']), intval($_POST['thisplanet']), intval($_POST['thisplanettype']) );
$target = LoadPlanet ( intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet']), intval($_POST['planettype']) );

if ( $unitab['freeze'] ) FleetError ("Невозможно отправить флот, Вселенная поставлена на паузу." );

if (  ( $_POST['thisgalaxy'] == $_POST['galaxy'] ) &&
        ( $_POST['thissystem'] == $_POST['system'] ) &&
        ( $_POST['thisplanet'] ==  $_POST['planet'] ) &&
        ( $_POST['thisplanettype'] == $_POST['planettype'] ) 
  ) FleetError ( "И как ты это себе представляешь?" );

if (
     (intval($_POST['galaxy']) < 1 || intval($_POST['galaxy']) > $unitab['galaxies'])  ||
     (intval($_POST['system']) < 1 || intval($_POST['system']) > $unitab['systems'])  ||
     (intval($_POST['planet']) < 1 || intval($_POST['planet']) > 16)
 ) {
    $PageError = "Cheater!";
    FleetError ( "Планета необитаема либо должна быть колонизирована!" );
    FleetError ( "Cheater!" );
}

$origin_user = LoadUser ( $origin['owner_id'] );
$target_user = LoadUser ( $target['owner_id'] );

if ( $origin_user['vacation'] ) FleetError ( "Находясь в режиме отпуска нельзя отправлять флот!" );
if ( $target_user['vacation'] && $order != 8 ) FleetError ( "Этот игрок находится в режиме отпуска!" );
if ( $nowfleet >= $maxfleet ) FleetError ( "Достигнута максимальная численность флотов" );

//if ( $origin_user['ip_addr'] !== "127.0.0.1" )        // для локальных подключений не делать проверку на мультоводство
if ( 0 )    // проверка отменяется
{
    if ( $origin_user['ip_addr'] === $target_user['ip_addr'] && $origin_user['player_id'] != $target_user['player_id'] ) FleetError ( "Невозможно приблизиться к игроку!" );
}

// Время удержания
$hold_time = 0;
if ( $order == 15 ) {    // Экспедиция
    if ( key_exists ('expeditiontime', $_POST) ) {
        $hold_time = floor (intval($_POST['expeditiontime']));
        if ( $hold_time > $GlobalUser['r124'] ) $hold_time = $GlobalUser['r124'];
        if ( $hold_time < 1 ) $hold_time = 1;
    }
    else $hold_time = 1;
    $hold_time *= 60*60;        // перевести в секунды
}
else if ( $order == 5 ) {    // Держаться
    if ( key_exists ('holdingtime', $_POST) ) {
        $hold_time = floor (intval($_POST['holdingtime']));
        if ( $hold_time > 32 ) $hold_time = 32;
        if ( $hold_time < 0 ) $hold_time = 0;
    }
    else $hold_time = 0;
    $hold_time *= 60*60;        // перевести в секунды
}

// Рассчитать расстояние, время полёта и затраты дейтерия.
$dist = FlightDistance ( intval($_POST['thisgalaxy']), intval($_POST['thissystem']), intval($_POST['thisplanet']), intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet']) );
$slowest_speed = FlightSpeed ( $fleet, $origin_user['r115'], $origin_user['r117'], $origin_user['r118'] );
$flighttime = FlightTime ( $dist, $slowest_speed, $fleetspeed / 10, $unispeed );
$cons = FlightCons ( $fleet, $dist, $flighttime, $origin_user['r115'], $origin_user['r117'], $origin_user['r118'], $unispeed, $hold_time / 3600 );
$cargo = $spycargo = $numships = 0;

foreach ($fleet as $id=>$amount)
{
    if ($id != 210) $cargo += FleetCargo ($id) * $amount;        // не считать зонды.
    else $spycargo = FleetCargo ($id) * $amount;
    $numships += $amount;
}

$space = ( ($cargo + $spycargo) - ($cons['fleet'] + $cons['probes']) ) - ($spycargo - $cons['probes']);

if ( $origin['d'] < ($cons['fleet'] + $cons['probes']) ) FleetError ( "Недостаточно топлива!" );
else if ( $space < 0 ) FleetError ( "Недостаточно места в грузовом отсеке!" );

// Ограничить перевозимые ресурсы грузоподъемностью флота и затратами на полёт.
$cargo_m = $cargo_k = $cargo_d = 0;
if ( $space > 0 ) {
    $cargo_m = min ( $space, $resource1 );
    $space -= $cargo_m;
}
if ( $space > 0 ) {
    $cargo_k = min ( $space, $resource2 );
    $space -= $cargo_k;
}
if ( $space > 0 ) {
    $cargo_d = min ( $space, $resource3 );
    $space -= $cargo_d;
}

if ($numships <= 0) FleetError ( "Вы не выбрали корабли либо выбрали, но слишком мало!" );

switch ( $order )
{
    case '1':        // Атака
        if ( $target == NULL ) FleetError ( "Планета необитаема либо должна быть колонизирована!" );
        else if ( ( 
            ( $origin_user['ally_id'] == $target_user['ally_id'] && $origin_user['ally_id'] > 0 )   || 
             IsBuddy ( $origin_user['player_id'],  $target_user['player_id']) ) ) $BlockAttack = 0;

        if ( IsPlayerNewbie ($target['owner_id']) || IsPlayerStrong ($target['owner_id']) ) FleetError ( "Планета находится под защитой для новичков!" );
        else if ( $target['owner_id'] == $origin['owner_id'] ) FleetError ( "Невозможно напасть на собственную планету!" );
        else if ($BlockAttack) FleetError ( "Запрет на атаки" );
        else if ($GlobalUser['noattack']) FleetError ( va ( "Запрет на атаки до #1", date ( "d.m.Y H:i:s", $GlobalUser['noattack_util'])) );
        break;

    case '2':        // Совместная атака
        if ( ( 
            ( $origin_user['ally_id'] == $target_user['ally_id'] && $origin_user['ally_id'] > 0 )   || 
             IsBuddy ( $origin_user['player_id'],  $target_user['player_id']) ) ) $BlockAttack = 0;

        if ( key_exists ('union2', $_POST) ) $union_id = floor (intval($_POST['union2']));
        else $union_id = 0;
        if ( $unitab['acs'] == 0 ) $union_id = 0;
        $union = LoadUnion ($union_id);
        $head_queue = GetFleetQueue ( $union['fleet_id'] );
        $acs_flighttime = $head_queue['end'] - time();
        $enum_result = EnumUnionFleets ($union_id);
        $acs_fleets = dbrows ($enum_result);
        if ( ! IsPlayerInUnion ( $GlobalUser['player_id'], $union) || $union == null ) FleetError ( "Вы не приглашены в этот альянс" );
        else if ( $target['owner_id'] == $origin['owner_id'] ) FleetError ( "Невозможно напасть на собственную планету!" );
        else if ( IsPlayerNewbie ($target['owner_id']) || IsPlayerStrong ($target['owner_id']) ) FleetError ( "Планета находится под защитой для новичков!" );
        else if ( $flighttime > $acs_flighttime * 1.3 ) FleetError ( "Вы слишком медленны, чтобы присоединиться к этому флоту" );
        else if ($BlockAttack) FleetError ( "Запрет на атаки" );
        else if ($GlobalUser['noattack']) FleetError ( va ( "Запрет на атаки до #1", date ( "d.m.Y H:i:s", $GlobalUser['noattack_util'])) );
        else if ($acs_fleets >= $unitab['acs'] * $unitab['acs']) FleetError ( va ("Атаковать флоты (>#1 флотов нельзя)", $unitab['acs'] * $unitab['acs']) );
        break;

    case '3':        // Транспорт
        if ( $target == NULL ) FleetError ( "Планета необитаема либо должна быть колонизирована!" );
        break;

    case '4':        // Оставить
        if ( $target['owner_id'] !== $GlobalUser['player_id'] ) FleetError ( "Флоты можно располагать только на собственной планете!" );
        break;

    case '5':        // Держаться
        $maxhold_fleets = $unitab['acs'] * $unitab['acs'];
        $maxhold_users = $unitab['acs'];
        if ( $target == NULL ) FleetError ( "Планета необитаема либо должна быть колонизирована!" );
        if ( GetHoldingFleetsCount ($target['planet_id']) >= $maxhold_fleets ) FleetError ("Задерживаться могут только $maxhold_fleets Удерживать флоты!");
        if ( ! CanStandHold ( $target['planet_id'], $origin['owner_id'] ) ) FleetError ("Задерживаться могут только $maxhold_users игроков!");
        if ( ! ( ( $origin_user['ally_id'] == $target_user['ally_id'] && $origin_user['ally_id'] > 0 )   || IsBuddy ( $origin_user['player_id'],  $target_user['player_id']) ) ) FleetError ("Задерживаться можно только у друзей и коллег по альянсу!");
        break;

    case '6':        // Шпионаж
        if ( $target == NULL ) FleetError ( "Планета необитаема либо должна быть колонизирована!" );
        else if ( ( 
            ( $origin_user['ally_id'] == $target_user['ally_id'] && $origin_user['ally_id'] > 0 )   || 
             IsBuddy ( $origin_user['player_id'],  $target_user['player_id']) ) ) $BlockAttack = 0;

        if ( $target['owner_id'] == $origin['owner_id'] ) FleetError ( "Нельзя шпионить на собственной планете!" );
        else if ( IsPlayerNewbie ($target['owner_id']) || IsPlayerStrong ($target['owner_id']) ) FleetError ( "На этой планете нельзя шпионить из-за защиты для новичков!" );
        else if ( $fleet[210] == 0 ) FleetError ( "Для шпионажа необходимы шпионские зонды." );
        else if ($BlockAttack) FleetError ( "Запрет на атаки" );
        else if ($GlobalUser['noattack']) FleetError ( va ( "Запрет на атаки до #1", date ( "d.m.Y H:i:s", $GlobalUser['noattack_util'])) );
        break;

    case '7':        // Колонизировать
        if ( $fleet[208] == 0 ) FleetError ( "Для колонизации надо послать колонизаторы!" );
        else if (HasPlanet (intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet'])) ) FleetError ( "Планета уже заселена!" );
        else {
            // Если отправлен колонизатор - добавить фантом колонизации.
            $id = CreateColonyPhantom ( intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet']), 99999 );
            $target = GetPlanet ($id);
        }
        break;

    case '8':        // Переработать
        if ( $fleet[209] == 0 ) FleetError ( "Для переработки надо послать переработчик!" );
        else if ($target['type'] != 10000 ) FleetError ( "При переработке можно приближаться только к полям обломков!" );
        break;

    case '9':        // Уничтожить
        if ( $target == NULL ) FleetError ( "Планета необитаема либо должна быть колонизирована!" );
        else if ( ( 
            ( $origin_user['ally_id'] == $target_user['ally_id'] && $origin_user['ally_id'] > 0 )   || 
             IsBuddy ( $origin_user['player_id'],  $target_user['player_id']) ) ) $BlockAttack = 0;

        if ( $fleet[214] == 0 ) FleetError ( "Для уничтожения луны необходима звезда смерти." );
        else if ($target['type'] != 0 ) FleetError ( "Уничтожать можно только луны!" );
        else if ($BlockAttack) FleetError ( "Запрет на атаки" );
        else if ($GlobalUser['noattack']) FleetError ( va ( "Запрет на атаки до #1", date ( "d.m.Y H:i:s", $GlobalUser['noattack_util'])) );
        break;

    case '15':       // Экспедиция
        $manned = 0;
        foreach ($fleet as $id=>$amount)
        {
            if ($id != 210) $manned += $amount;        // не считать зонды.
        }
        $expnum = GetExpeditionsCount ( $GlobalUser['player_id'] );    // Количество экспедиций
        $maxexp = floor ( sqrt ( $GlobalUser['r124'] ) );
        if ( $expnum >= $maxexp ) FleetError ( "Слишком много одновременных экспедиций" );
        else if ( $manned == 0 ) FleetError ( "Экспедиция должна состоять как минимум из одного управляемого людьми корабля." );
        else if ( intval($_POST['planet']) != 16 ) FleetError ( "Цель экспедиции недействительна!" );
        else {
            $id = CreateOuterSpace ( intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet']) );
            $target = GetPlanet ($id);
        }
        break;

    default:
        FleetError ( "Необходимо выбрать задание!" );
        break;
}

//Ваши флоты ввязались в бой.

if ($FleetError) {

    PageHeader ("flottenversand", false, true, "flotten1", 1);

?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
  <script language="JavaScript" src="js/flotten.js"></script>
  <table width="519" border="0" cellpadding="0" cellspacing="1">

<?php

    echo "  <tr height=\"20\">\n";
    echo "     <td class=\"c\"><span class=\"error\"> Флот не удалось отправить!</span></td>\n";
    echo "  </tr>\n";
    echo "$FleetErrorText\n";
}

// Все проверки прошли удачно, можно отправлять флот.
else {

    // Fleet lock
    $fleetlock = "temp/fleetlock_" . $aktplanet['planet_id'];
    if ( file_exists ($fleetlock) ) MyGoto ( "flotten1" );
    $f = fopen ( $fleetlock, 'w' );
    fclose ($f);

    $fleet_id = DispatchFleet ( $fleet, $origin, $target, $order, $flighttime, $cargo_m, $cargo_k, $cargo_d, $cons['fleet'] + $cons['probes'], time(), $union_id, $hold_time );
    $queue = GetFleetQueue ($fleet_id);

    UserLog ( $aktplanet['owner_id'], "FLEET", 
     "Отправка флота $fleet_id: " . GetMissionNameDebug ($order) . " " .
     $origin['name'] ." [".$origin['g'].":".$origin['s'].":".$origin['p']."] -&gt; ".$target['name']." [".$target['g'].":".$target['s'].":".$target['p']."]<br>" .
     DumpFleet ($fleet) . "<br>" .
     "Время полёта: " . BuildDurationFormat ($flighttime) . ", удержание: " . BuildDurationFormat ($hold_time) . ", затраты дейтерия: " . nicenum ($cons['fleet'] + $cons['probes']) . ", союз: " . $union_id );

    if ( $union_id ) {
        $union_time = UpdateUnionTime ( $union_id, $queue['end'], $fleet_id );
        UpdateFleetTime ( $fleet_id, $union_time );
    }

    // Поднять флот с планеты.
    AdjustResources ( $cargo_m, $cargo_k, $cargo_d + $cons['fleet'] + $cons['probes'], $origin['planet_id'], '-' );
    AdjustShips ( $fleet, $origin['planet_id'], '-' );

    unlink ( $fleetlock );

//    echo "<br>";
//    print_r ( $queue);

    PageHeader ("flottenversand", false, true, "flotten1", 1);

?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
  <script language="JavaScript" src="js/flotten.js"></script>
  <table width="519" border="0" cellpadding="0" cellspacing="1">

   <tr height="20">
    <td class="c" colspan="2">
      <span class="success">Флот отправлен:</span>
    </td>
   </tr>
   <tr height="20">
  <th>Задание</th><th><?php echo loca("FLEET_ORDER_$order");?></th>
   </tr>
   <tr height="20">
     <th>Расстояние</th><th><?php echo nicenum($dist);?></th>
   </tr>
   <tr height="20">
      <th>Скорость</th><th><?php echo nicenum($slowest_speed);?></th>
   </tr>
   <tr height="20">
      <th>Потребление</th><th><?php echo nicenum($cons['fleet'] + $cons['probes']);?></th>
   </tr>
   <tr height="20">
     <th>Отправлен с</th><th><a href="index.php?page=galaxy&galaxy=<?php echo intval($_POST['thisgalaxy']);?>&system=<?php echo intval($_POST['thissystem']);?>&position=<?php echo intval($_POST['thisplanet']);?>&session=<?php echo $session;?>" >[<?php echo intval($_POST['thisgalaxy']);?>:<?php echo intval($_POST['thissystem']);?>:<?php echo intval($_POST['thisplanet']);?>]</a></th>
   </tr>
   <tr height="20">
     <th>Отправлен на</th><th><a href="index.php?page=galaxy&galaxy=<?php echo intval($_POST['galaxy']);?>&system=<?php echo intval($_POST['system']);?>&position=<?php echo intval($_POST['planet']);?>&session=<?php echo $session;?>" >[<?php echo intval($_POST['galaxy']);?>:<?php echo intval($_POST['system']);?>:<?php echo intval($_POST['planet']);?>]</a></th>
   </tr>
   <tr height="20">
     <th>Время прибытия</th><th><?php echo date("D M j G:i:s", $queue['end']);?></th>
   </tr>
   <tr height="20">
     <th>Время возврата</th><th><?php echo date("D M j G:i:s", $queue['end'] + $flighttime + $hold_time);?></th>
    </tr>
   <tr height="20">
     <td class="c" colspan="2">Корабли</td>
   </tr>

<?php
    // Список кораблей.
    foreach ($fleet as $id=>$amount)
    {
        if ( $amount > 0 ) {
            echo "      <tr height=\"20\">\n";
            echo "     <th width=\"50%\">".loca("NAME_$id")."</th><th>".nicenum($amount)."</th>\n";
            echo "   </tr>\n";
        }
    }

}
?>

   </table>
<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ("", $PageError);
ob_end_flush ();
?>