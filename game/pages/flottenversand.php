<?php

// Отправка флота с проверкой всех параметров.
// Если флот отправлен успешно - вывести краткую информацию, иначе вывести ошибку.
// Через 3 секунды делается редирект на первую страницу отправки флота.

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
*/

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

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
$session = $_GET['session'];

PageHeader ("flottenversand");

?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
  <script language="JavaScript" src="js/flotten.js"></script>
  <table width="519" border="0" cellpadding="0" cellspacing="1">

<?php

$order = $_POST['order'];

$origin = LoadPlanet ( $_POST['thisgalaxy'], $_POST['thissystem'], $_POST['thisplanet'], $_POST['thisplanettype'] );
$target = LoadPlanet ( $_POST['galaxy'], $_POST['system'], $_POST['planet'], $_POST['planettype'] );

if ($origin['planet_id'] == $target['planet_id'] ) FleetError ( "И как ты это себе представляешь?" );

$origin_user = LoadUser ( $origin['owner_id'] );
$target_user = LoadUser ( $target['owner_id'] );

if ( $origin_user['vacation'] ) FleetError ( "Находясь в режиме отпуска нельзя отправлять флот!" );
if ( $target_user['vacation'] ) FleetError ( "Этот игрок находится в режиме отпуска!" );

//Достигнута максимальная численность флотов

if ( $origin_user['ip_addr'] !== "127.0.0.1" )        // для локальных подключений не делать проверку на мультоводство
{
    if ( $origin_user['ip_addr'] === $target_user['ip_addr'] && $origin_user['player_id'] != $target_user['player_id'] ) FleetError ( "Невозможно приблизиться к игроку!" );
}

//Недостаточно места в грузовом отсеке!
//Недостаточно топлива!

//if (!colony) Планета необитаема либо должна быть колонизирована!

//if (nofleet) Вы не выбрали корабли либо выбрали, но слишком мало!

switch ( $order )
{
    case '1':        // Атака
//Планета находится под защитой для новичков!
//Невозможно напасть на собственную планету!
//Запрет на атаки до #1
        break;

    case '2':        // Совместная атака
//Планета находится под защитой для новичков
//Вы не приглашены в этот альянс
//Атаковать флоты (?)
//Вы слишком медленны, чтобы присоединиться к этому флоту
//Запрет на атаки до #1
        break;

    case '3':        // Транспорт
        break;

    case '4':        // Оставить
//Флоты можно располагать только на собственной планете!
        break;

    case '5':        // Держаться
//Задерживаться можно только у друзей и коллег по альянсу!
//Задерживаться могут только XX игроков!
//Задерживаться могут только XX Удерживать флоты!
        break;

    case '6':        // Шпионаж
//На этой планете нельзя шпионить из-за защиты для новичков!
//Нельзя шпионить на собственной планете!
        break;

    case '7':        // Колонизировать
//Планета уже заселена!
//Для колонизации надо послать колонизаторы!
        break;

    case '8':        // Переработать
//При переработке можно приближаться только к полям обломков!
//Для переработки надо послать переработчик!
        break;

    case '9':        // Уничтожить
//Уничтожать можно только луны!
//Для уничтожения луны необходима звезда смерти.
        break;

    case '14':        // Испытательный полёт
        beak;

    case '15':       // Экспедиция
//Цель экспедиции недействительна!
        break;

    default:
//Необходимо выбрать задание!
        break;
}

//Ваши флоты ввязались в бой.

if ($FleetError) {
    echo "  <tr height=\"20\">\n";
    echo "     <td class=\"c\"><span class=\"error\"> Флот не удалось отправить!</span></td>\n";
    echo "  </tr>\n";
    echo "$FleetErrorText\n";
}

// Все проверки прошли удачно, можно отправлять флот.
else {

    print_r ( $_POST);

?>

   <tr height="20">
    <td class="c" colspan="2">
      <span class="success">Флот отправлен:</span>
    </td>
   </tr>
   <tr height="20">
  <th>Задание</th><th><?=loca("FLEET_ORDER_$order");?></th>
   </tr>
   <tr height="20">
     <th>Расстояние</th><th>5</th>
   </tr>
   <tr height="20">
      <th>Скорость</th><th>3.600</th>
   </tr>
   <tr height="20">
      <th>Потребление</th><th>2</th>
   </tr>
   <tr height="20">
     <th>Отправлен с</th><th><a href="index.php?page=galaxy&galaxy=<?=$_POST['thisgalaxy'];?>&system=<?=$_POST['thissystem'];?>&position=<?=$_POST['thisplanet'];?>&session=<?=$session;?>" >[<?=$_POST['thisgalaxy'];?>:<?=$_POST['thissystem'];?>:<?=$_POST['thisplanet'];?>]</a></th>
   </tr>
   <tr height="20">
     <th>Отправлен на</th><th><a href="index.php?page=galaxy&galaxy=<?=$_POST['galaxy'];?>&system=<?=$_POST['system'];?>&position=<?=$_POST['planet'];?>&session=<?=$session;?>" >[<?=$_POST['galaxy'];?>:<?=$_POST['system'];?>:<?=$_POST['planet'];?>]</a></th>
   </tr>
   <tr height="20">
     <th>Время прибытия</th><th>Thu Mar 17 10:53:01</th>
   </tr>
   <tr height="20">
     <th>Время возврата</th><th>Thu Mar 17 11:00:03</th>
    </tr>
   <tr height="20">
     <td class="c" colspan="2">Корабли</td>
   </tr>
      <tr height="20">
     <th width="50%">Kleiner Transporter</th><th>10</th>
   </tr>
      <tr height="20">
     <th width="50%">Großer Transporter</th><th>55</th>
   </tr>
      <tr height="20">
     <th width="50%">Recycler</th><th>1</th>
   </tr>

<?php
}
?>

   </table>
<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>