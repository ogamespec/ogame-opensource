<?php

// Флот 1: подготавливает состав флота

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("flotten1");
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
<script src="js/flotten.js"></script>
<!--
<body>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
-->
<center>

  <table width="519" border="0" cellpadding="0" cellspacing="1">
   <tr height="20">
  <td colspan="8" class="c">
  <table border=0 width=100%>
   <tr>

    <td style='background-color:transparent;'>
    Флоты 2 / 13    </td>
    <td align=right style='background-color:transparent;'>
      0/2 Экспедиции    
    </td>
    </tr>
    </table>
    </td>

   </tr>
     <tr height="20">
    <th>Номер</th>
    <th>Задание</th>
    <th>Численность</th>
    <th>Отправлен с</th>

    <th>Отправлен</th>
    <th>Отправлен на</th>
    <th>Прибудет</th>
    <th>Приказ</th>
   </tr>
     <tr height="20">
    <th>1</th>

    <th>
      <a title="">Переработать</a>
      <a title="Уход на задание">(У)</a>
    </th>
    <th> <a title="Малый транспорт: 440 
Большой транспорт: 397 
Лёгкий истребитель: 1.393 
Тяжёлый истребитель: 2 
Крейсер: 331 
Линкор: 125 
Переработчик: 902 
Шпионский зонд: 9 
Бомбардировщик: 72 
Уничтожитель: 152 
Звезда смерти: 1 
Линейный крейсер: 200 
">4.024</a></th>
    <th><a href="index.php?page=galaxy&galaxy=1&system=260&position=4&session=3ff7ae974331" >[1:260:4]</a></th>

    <th>Mon Nov 30 12:31:06</th>
    <th><a href="index.php?page=galaxy&galaxy=1&system=260&position=4&session=3ff7ae974331" >[1:260:4]</a>    <br />Andorianin    </th>
    <th>Mon Nov 30 13:08:33</th>
    <th>
         <form action="index.php?page=flotten1&session=3ff7ae974331" method="POST">
    <input type="hidden" name="order_return" value="29114802" />

        <input type="submit" value="Отзыв" />
     </form>
            </th>
   </tr>
   <tr height="20">
    <th>2</th>
    <th>
      <a title="">Оставить</a>

      <a title="Уход на задание">(У)</a>
    </th>
    <th> <a title="Линейный крейсер: 420 
">420</a></th>
    <th><a href="index.php?page=galaxy&galaxy=1&system=244&position=4&session=3ff7ae974331" >[1:244:4]</a></th>
    <th>Mon Nov 30 12:09:52</th>
    <th><a href="index.php?page=galaxy&galaxy=1&system=255&position=4&session=3ff7ae974331" >[1:255:4]</a>    <br />Andorianin    </th>

    <th>Mon Nov 30 22:22:15</th>
    <th>
         <form action="index.php?page=flotten1&session=3ff7ae974331" method="POST">
    <input type="hidden" name="order_return" value="29114692" />
        <input type="submit" value="Отзыв" />
     </form>
            </th>
   </tr>

  </table>


  
<form action="index.php?page=flotten2&session=<?=$session;?>" method="POST">
  <table width="519" border="0" cellpadding="0" cellspacing="1">
          <tr height="20">
  <td colspan="4" class="c">Новое задание: выбрать корабли</td>
   </tr>
   <tr height="20">

  <th>Тип корабля</th>
  <th>В наличии</th>
<!--    <th>Gesch.</th> -->
    <th>-</th>
    <th>-</th>
   </tr>

<?php

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

    foreach ($fleetmap as $i=>$gid) {
        
        $amount = $aktplanet["f$gid"];
        if ($amount > 0) {
            $speed = FleetSpeed ($gid, $GlobalUser['r115'], $GlobalUser['r117'], $GlobalUser['r118']);
            $cargo = FleetCargo ($gid);
            $cons = FleetCons ( $gid);

            echo "   <tr height=\"20\">\n";
            echo "    <th><a title=\"Скорость: $speed\">".loca("NAME_$gid")."</a></th>\n";
            echo "    <th>$amount<input type=\"hidden\" name=\"maxship$gid\" value=\"$amount\"/></th>\n";
            echo "<!--    <th>$speed -->\n";
            echo "     <input type=\"hidden\" name=\"consumption$gid\" value=\"$cons\"/>\n";
            echo "     <input type=\"hidden\" name=\"speed$gid\" value=\"$speed\" /></th>\n";
            echo "     <input type=\"hidden\" name=\"capacity$gid\" value=\"$cargo\" /></th>\n";
            if ( $speed ) {
                echo "     <th><a href=\"javascript:maxShip('ship$gid');\" >все</a> </th>\n";
                echo "     <th><input name=\"ship$gid\" size=\"10\" value=\"0\" alt=\"".loca("NAME_$gid")." $amount\"/></th>\n";
            }
            else {
                echo "     <th></th>\n";
                echo "     <th></th>\n";
            }
            echo "   </tr>\n\n";
        }
    }

?>

   <tr height="20">
  <th colspan="2"><a href="javascript:noShips();" >Обнулить</a></th>
  <th colspan="2"><a href="javascript:maxShips();" >Все корабли</a></th>

   </tr>
 
   <tr height="20">
    <th colspan="4"><input type="submit" value="Дальше" /></th>
   </tr>
<tr><th colspan=4>
</th></tr>
</form>
</table>
<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>