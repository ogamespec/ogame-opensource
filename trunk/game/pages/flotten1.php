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

function FleetMissionText ($num)
{
    if ($num >= 200)
    {
        $desc = "<a title=\"На планете\">(Д)</a>";
        $num -= 200;
    }
    else if ($num >= 100)
    {
        $desc = "<a title=\"Возвращение к планете\">(В)</a>";
        $num -= 100;
    }
    else $desc = "<a title=\"Уход на задание\">(У)</a>";

    echo "      <a title=\"\">".loca("FLEET_ORDER_$num")."</a>\n$desc\n";
}

PageHeader ("flotten1");

$result = EnumOwnFleetQueue ( $GlobalUser['player_id'] );
$nowfleet = $rows = dbrows ($result);
$maxfleet = $GlobalUser['r108'] + 1;

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
    Флоты <?=$rows;?> / <?=$maxfleet;?>    </td>
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
<?php

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

    if ($rows)
    {
        $row = 1;
        while ($rows--)
        {
            $queue = dbarray ($result);
            $fleet = LoadFleet ($queue['sub_id']);
            $origin = GetPlanet ($fleet['start_planet']);
            $target = GetPlanet ($fleet['target_planet']);
            $target_user = LoadUser ( $target['owner_id'] );
?>
     <tr height="20">
    <th><?=$row;?></th>

    <th>
<?
    echo FleetMissionText ($fleet['mission']);
?>
    </th>
    <th> <a title="<?php
        $totalships = 0;
        foreach ( $fleetmap as $i=>$gid)
        {
            if ( $fleet["ship$gid"] > 0 ) {
                echo loca("NAME_$gid") . ": " . nicenum($fleet["ship$gid"]) . " \n";
                $totalships += $fleet["ship$gid"];
            }
        }
?>
"><?=nicenum($totalships);?></a></th>
    <th><a href="index.php?page=galaxy&galaxy=<?=$origin['g'];?>&system=<?=$origin['s'];?>&position=<?=$origin['p'];?>&session=<?=$session;?>" >[<?=$origin['g'];?>:<?=$origin['s'];?>:<?=$origin['p'];?>]</a></th>

    <th><?=date ( "D M j G:i:s", $queue['start']);?></th>
    <th><a href="index.php?page=galaxy&galaxy=<?=$target['g'];?>&system=<?=$target['s'];?>&position=<?=$target['p'];?>&session=<?=$session;?>" >[<?=$target['g'];?>:<?=$target['s'];?>:<?=$target['p'];?>]</a>    <br /><?=$target_user['oname'];?>    </th>
    <th><?=date ( "D M j G:i:s", $queue['end']);?></th>
    <th>
         <form action="index.php?page=flotten1&session=<?=$session;?>" method="POST">
    <input type="hidden" name="order_return" value="<?=$fleet['fleet_id'];?>" />
        <input type="submit" value="Отзыв" />
     </form>
            </th>
   </tr>

<?php
            $row++;
        }
    }
    else
    {
?>
   <tr height="20"> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
   </tr> 
<?php
    }
?>

  </table>


  
<form action="index.php?page=flotten2&session=<?=$session;?>" method="POST">
  <table width="519" border="0" cellpadding="0" cellspacing="1">
<?php
    if ($nowfleet >= $maxfleet)
    {
?>
         <tr height="20">
      <th colspan="4"><font color="red">Достигнута максимальная численность флота!</font></th>
   </tr>
<?php
    }
?>
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

    foreach ($fleetmap as $i=>$gid) {
        
        $amount = $aktplanet["f$gid"];
        if ($amount > 0) {
            $speed = FleetSpeed ($gid, $GlobalUser['r115'], $GlobalUser['r117'], $GlobalUser['r118']);
            $cargo = FleetCargo ($gid );
            $cons = FleetCons ( $gid, $GlobalUser['r115'], $GlobalUser['r117'], $GlobalUser['r118']);

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