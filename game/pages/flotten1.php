<?php

// Флот 1: подготавливает состав флота

$FleetMessage = "";
$FleetError = "";

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "technames", $GlobalUser['lang'] );
loca_add ( "fleetorder", $GlobalUser['lang'] );
loca_add ( "fleet", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
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

$union_id = 0;
$uni = LoadUniverse ();

// Обработка POST-запросов
if ( method () === "POST" )
{
    if ( key_exists ( 'order_return', $_POST) )         // Отзыв флота.
    {
        $fleet_id = $_POST['order_return'];
        $fleet_obj = LoadFleet ( $fleet_id );
        if (  ($fleet_obj['owner_id'] == $GlobalUser['player_id']) &&
              ($fleet_obj['mission'] < 100 || $fleet_obj['mission'] > 200 )  ) 
            RecallFleet ( $fleet_id );
    }

    else if ( key_exists ( 'order_union', $_POST) && $uni['acs'] > 0 )     // Управление САБ.
    {
        $fleet_id = intval ($_POST['order_union']);
        $union_id = CreateUnion ($fleet_id);

        if ( key_exists ( 'union_name', $_POST) ) RenameUnion ( $union_id, $_POST['union_name'] );    // переименовать

        if ( key_exists ( 'user_name', $_POST) ) $FleetError = AddUnionMember ( $union_id, $_POST['user_name'] );    // добавить игрока
    }
}

PageHeader ("flotten1");

$result = EnumOwnFleetQueue ( $GlobalUser['player_id'] );    // Количество флотов
$nowfleet = $rows = dbrows ($result);
$maxfleet = $GlobalUser['r108'] + 1;

$prem = PremiumStatus ($GlobalUser);
if ( $prem['admiral'] ) $maxfleet += 2;

$expnum = GetExpeditionsCount ( $GlobalUser['player_id'] );    // Количество экспедиций
$maxexp = floor ( sqrt ( $GlobalUser['r124'] ) );

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
<?php
    if ($prem['admiral'])
    {
?>
    <div style="margin-top:2;margin-bottom:2;">Флоты <?=$rows;?> / <?=($maxfleet-2);?> <b><font style="color:lime;">+2</font></b> <img border="0" alt="Адмирал" src="img/admiral_ikon.gif" onmouseover='return overlib("&lt;font color=white &gt;Адмирал&lt;/font&gt;", WIDTH, 100);' onmouseout="return nd();" width="20" height="20" style="vertical-align:middle;"></div>
<?php
    }
    else
    {
?>
    Флоты <?=$rows;?> / <?=$maxfleet;?>    </td>
<?php
    }
?>
    <td align=right style='background-color:transparent;'>
      <?=$expnum;?>/<?=$maxexp;?> Экспедиции    
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
<?php
    if ( ($fleet['mission'] == 1 || $fleet['mission'] == 21) && $uni['acs'] > 0 )
    {
?>
         <form action="index.php?page=flotten1&session=<?=$session;?>" method="POST">
    <input type="hidden" name="order_union" value="<?=$fleet['fleet_id'];?>" />
        <input type="submit" value="Союз" />
     </form>
<?php
    }
?>
<?php
    if ( $fleet['mission'] < 100 || $fleet['mission'] > 200 )
    {
?>
         <form action="index.php?page=flotten1&session=<?=$session;?>" method="POST">
    <input type="hidden" name="order_return" value="<?=$fleet['fleet_id'];?>" />
        <input type="submit" value="Отзыв" />
     </form>
<?php
    }
?>
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

<?php
// ************************ Форма создания САБ атаки ************************

    if ($union_id != 0 && $uni['acs'] > 0 )
    {
        $union = LoadUnion ($union_id);
        $fleet = LoadFleet ( $union['fleet_id'] );

?>

<form action="index.php?page=flotten1&session=<?=$session;?>" method="POST">
    <input type="hidden" name="order_union" value="<?=$fleet['fleet_id'];?>" />
  <table width="519" border="0" cellpadding="0" cellspacing="1">
                    <tr><td class="c" colspan=2>Союз флотов <?=$union['name'];?></td></tr>
                    <tr><td class="c" colspan=2>Изменить название союза</td></tr>
                    <tr><th colspan=2>
<input name="union_name" type="text" value="<?=$union['name'];?>" /> <br /><input type="submit" value="OK" />
                    </th></tr>
                    <tr>
                        <td class="c">Приглашённые участники</td>
                        <td class="c">Пригласить участника</td>
                    </tr>
                    <tr>
                        <th width="50%">
                            <select size="5">
<?php
    for ($i=0; $i<=$union['players']; $i++)
    {
        $player_id = $union["player"][$i];
        //if ($player_id == $GlobalUser['player_id']) continue;    // не показывать себя в списке приглашенных
        $user = LoadUser ($player_id);
        echo "<option>".$user['oname']."</option>\n";
    }
?>
                            </select>
                        </th>
                        <td>
                            <input name="user_name" type="text" /> <br /><input type="submit" value="OK" />
                        </td>
                        <br />
                    </tr>
</table></form>
<?php
    }
?>

  
<form action="index.php?page=flotten2&session=<?=$session;?>" method="POST">
<?php
    if ( key_exists ( 'galaxy', $_GET ) ) {
        $target_galaxy = $_GET['galaxy'];

        if ( key_exists ( 'system', $_GET ) ) $target_system = $_GET['system'];
        else  $target_system = 0;

        if ( key_exists ( 'planet', $_GET ) ) $target_planet = $_GET['planet'];
        else  $target_planet = 0;

        if ( key_exists ( 'planettype', $_GET ) ) $target_planettype = $_GET['planettype'];
        else  $target_planettype = 0;

        if ( key_exists ( 'target_mission', $_GET ) ) $target_mission = $_GET['target_mission'];
        else  $target_mission = 0;
?>
     <input type="hidden" name="target_galaxy" value="<?=$target_galaxy;?>" />
   <input type="hidden" name="target_system" value="<?=$target_system;?>" />
   <input type="hidden" name="target_planet" value="<?=$target_planet;?>" />
   <input type="hidden" name="target_planettype" value="<?=$target_planettype;?>" />
   <input type="hidden" name="target_mission" value="<?=$target_mission;?>" />
<?php
    }
?>
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
PageFooter ($FleetMessage, $FleetError);
ob_end_flush ();
?>