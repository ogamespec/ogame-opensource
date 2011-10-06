<?php

// Флот 3: вывод списка заданий, загрузка ресурсов.

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

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

if ( method() !== "POST" )
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=index.php?page=flotten1&session=$session' /></head><body></body>";
    ob_end_flush ();
    die ();
}

PageHeader ("flotten3");
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>

  <script language="JavaScript" src="js/flotten.js"></script>
  <script type="text/javascript">

  function getStorageFaktor(){
    return 1;
  }

  </script>

<!--
 <body>
 <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
-->
<center>
<table width="519" border="0" cellpadding="0" cellspacing="1">

<form action="index.php?page=flottenversand&session=<?=$session;?>" method="POST">

<?php
    // Координаты цели и данные о ресурсах.
    echo "<input name=\"thisgalaxy\" type=\"hidden\" value=\"".$_POST['thisgalaxy']."\" />\n";
    echo "<input name=\"thissystem\" type=\"hidden\" value=\"".$_POST['thissystem']."\" />\n";
    echo "<input name=\"thisplanet\" type=\"hidden\" value=\"".$_POST['thisplanet']."\" />\n";
    echo "<input name=\"thisplanettype\" type=\"hidden\" value=\"".$_POST['thisplanettype']."\" />\n";
    echo "<input name=\"speedfactor\" type=\"hidden\" value=\"".$_POST['speedfactor']."\" />\n";
    echo "<input name=\"thisresource1\" type=\"hidden\" value=\"".floor($aktplanet['m'])."\" />\n";
    echo "<input name=\"thisresource2\" type=\"hidden\" value=\"".floor($aktplanet['k'])."\" />\n";
    echo "<input name=\"thisresource3\" type=\"hidden\" value=\"".floor($aktplanet['d'])."\" />\n";
    echo "<input name=\"galaxy\" type=\"hidden\" value=\"".$_POST['galaxy']."\" />\n";
    echo "<input name=\"system\" type=\"hidden\" value=\"".$_POST['system']."\" />\n";
    echo "<input name=\"planet\" type=\"hidden\" value=\"".$_POST['planet']."\" />\n";
    echo "<input name=\"planettype\" type=\"hidden\" value=\"".$_POST['planettype']."\" />\n\n";

    // Список флотов.
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

    $total = 0;
    foreach ($fleetmap as $i=>$gid) 
    {
        $total += $_POST["ship$gid"];
        if ( key_exists("ship$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"ship$gid\" value=\"".$_POST["ship$gid"]."\" />\n";
        if ( key_exists("consumption$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"consumption$gid\" value=\"".$_POST["consumption$gid"]."\" />\n";
        if ( key_exists("speed$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"speed$gid\" value=\"".$_POST["speed$gid"]."\" />\n";
        if ( key_exists("capacity$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"capacity$gid\" value=\"".$_POST["capacity$gid"]."\" />\n";
    }

    if ( $total == 0 )    // Флот не выбран.
    {
        ob_end_clean ();
        echo "<html><head><meta http-equiv='refresh' content='0;url=index.php?page=flotten1&session=$session' /></head><body></body>";
        die ();
    }

    echo "<input type=\"hidden\" name=\"speed\" value=\"".$_POST['speed']."\" />\n";
?>

<tr height="20" align="left">
<td class="c" colspan="2"><?=floor($_POST['galaxy']);?>:<?=floor($_POST['system']);?>:<?=floor($_POST['planet']);?> - <?=loca("FLEET_PLANETTYPE_".$_POST['planettype']);?></td>

</tr>
<tr valign="top" align="left">
<th width="50%">
  <table width="259" border="0"  cellpadding="0" cellspacing="0" >
  <tr height="20">
  <td class="c" colspan="2">Задание</td>
  </tr>

<?php
    // Отобразить список доступных заданий.

    function is_checked ($mission)
    {
        if ( key_exists ( 'target_mission', $_POST ) ) {
            if ( $_POST['target_mission'] == $mission ) return "checked";
        }
    }

    function is_selected ($union_id)
    {
        if ( key_exists ( 'union2', $_POST ) ) {
            if ( $_POST['union2'] == $union_id ) return "selected";
        }
    }

    $mission_acs = $mission_exp = $mission_hold = false;

    $fleet = array ();

    foreach ($fleetmap as $i=>$gid) 
    {
        if ( key_exists("ship$gid", $_POST) ) $fleet[$gid] = $_POST["ship$gid"];
        else $fleet[$gid] = 0;
    }

    $missions = FleetAvailableMissions ( $_POST['thisgalaxy'], $_POST['thissystem'], $_POST['thisplanet'], $_POST['thisplanettype'], $_POST['galaxy'], $_POST['system'], $_POST['planet'], $_POST['planettype'], $fleet );
    if ( $_POST['union2'] > 0 ) $missions[] = 2;    // Совместная атака.

    if ( count ($missions) == 0 )
    {
        echo "<tr>\n";
        echo "   <th><font color=\"red\">Нет подходящих заданий</font></th>\n";
        echo "</tr>\n";
    }
    else
    {
        foreach ($missions as $i=>$id) 
        {
            if ( $id == 2 ) $mission_acs = true;
            if ( $id == 5 ) $mission_hold = true;
            if ( $id == 15 ) $mission_exp = true;

            if ($id == 15)        // Экспедиция.
            {
                echo "    <tr height=\"20\">\n";
                echo "<th>\n";
                echo "  <input type=\"radio\" name=\"order\" value=\"15\" checked='checked'>".loca("FLEET_ORDER_$id")."<br />\n";
                echo "  <br><font color=red>ВНИМАНИЕ! Экспедиция - очень рискованная миссия, не предназначенная для сэйва.</font>   </th>\n";
                echo "  </tr>\n";
            }
            else
            {
                echo "    <tr height=\"20\">\n";
                echo "<th>\n";
                echo "  <input type=\"radio\" name=\"order\" value=\"$id\" ".is_checked($id).">".loca("FLEET_ORDER_$id")."<br />\n";
                echo "     </th>\n";
                echo "  </tr>\n";
            }
        }
    }
?>

   </table>
</th>

<th>
     <table  width="259" border="0" cellpadding="0" cellspacing="0">
     <tr height="20">
  <td colspan="3" class="c">Сырьё</td>
     </tr>
       <tr height="20">
      <th>Металл</th>
      <th><a href="javascript:maxResource('1');">max</a></th>

      <th><input name="resource1" type="text" alt="Металл <?=floor($aktplanet['m']);?>" size="10" onChange="calculateTransportCapacity();" /></th>
     </tr>
       <tr height="20">
      <th>Кристалл</th>
      <th><a href="javascript:maxResource('2');">max</a></th>
      <th><input name="resource2" type="text" alt="Кристалл <?=floor($aktplanet['k']);?>" size="10" onChange="calculateTransportCapacity();" /></th>
     </tr>
       <tr height="20">

      <th>Дейтерий</th>
      <th><a href="javascript:maxResource('3');">max</a></th>
      <th><input name="resource3" type="text" alt="Дейтерий <?=floor($aktplanet['d']);?>" size="10" onChange="calculateTransportCapacity();" /></th>
     </tr>
       <tr height="20">
  <th>Остаток</th>
      <th colspan="2"><div id="remainingresources">-</div></th>

     </tr>
     <tr height="20">
  <th colspan="3"><a href="javascript:maxResources()">Всё сырьё</a></th>
     </tr>

  <tr height="20">
  <th>&nbsp; </th>
  </tr>


<?php
    // ----------------------------------------------------------------------------------------------------
    // Список боевых союзов

    $unions = EnumUnion ( $GlobalUser['player_id'] );

    if ( $mission_acs && count($unions) > 0 )
    {
?>

    <tr height="20">
     <td class="c" colspan="3">Боевые союзы</td>
  </tr>
  <tr height="20">
   <th colspan="3">
    <select name="union2" >
<?php
    foreach ( $unions as $i=>$union )
    {
        echo "          <option value=\"".$union['union_id']."\" ".is_selected($union['union_id']).">".$union['name']."</option>\n";
    }
?>           </select> 
      </th>
  </tr>

  <tr height="20">
  <th>&nbsp; </th>
  </tr>

<?
    }
?>

<?php
    // ----------------------------------------------------------------------------------------------------
    // Время удержания

    if ( $mission_hold )
    {
?>

    <tr height="20">
     <td class="c" colspan="3">Время пребывания</td>
  </tr>
  <tr height="20">
   <th colspan="3">
    <select name="holdingtime" >
          <option value="0">0</option>
          <option value="1" selected>1</option>
          <option value="2">2</option>
          <option value="4">4</option>
          <option value="8">8</option>
          <option value="16">16</option>
          <option value="32">32</option>
           </select> 
      Время в часах   </th>
  </tr>

<?
    }
?>


<?php
    // ----------------------------------------------------------------------------------------------------
    // Время пребывания в экспедиции

    if ( $mission_exp && $GlobalUser['r124'] > 0 )
    {
?>

    <tr height="20">
     <td class="c" colspan="3">Время пребывания</td>
  </tr>
  <tr height="20">
   <th colspan="3">
    <select name="expeditiontime" >
<?php
    for ($i=1; $i<=$GlobalUser['r124']; $i++)
    {
        echo "          <option value=\"$i\">$i</option>\n";
    }
?>           </select> 
      Время в часах   </th>
  </tr>


<?
    }
?>

   
    </table>
</th>
</tr>
<tr height="20" >
 <th colspan="2"><input type="submit" value="Дальше" /></th>
</tr>
 </form>
</table><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>