<?php

// Флот 2: подготавливает координаты цели

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "fleetorder", $GlobalUni['lang'] );
loca_add ( "fleet", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval ($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

if ( method() !== "POST" ) MyGoto ( "flotten1" );

PageHeader ("flotten2");
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>


  <script language="JavaScript" src="js/flotten.js"></script>
  <script language="JavaScript" src="js/ocnt.js"></script>

  <script type="text/javascript">

  function getStorageFaktor(){
    return 1  }

  </script>
  
 <!-- <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>  -->

<center>
<table width="519" border="0" cellpadding="0" cellspacing="1">
<form action="index.php?page=flotten3&session=<?php echo $session;?>" method="POST">
<?php

    if ( key_exists ( 'target_mission', $_POST ) ) {
        $target_misson = intval ($_POST['target_mission']);
?>
<input type="hidden" name="target_mission" value="<?php echo $target_misson;?>" />
<?php
    }

    if ( key_exists ( 'target_galaxy', $_POST ) ) $target_galaxy = intval ($_POST['target_galaxy']);
    else $target_galaxy = $aktplanet['g'];

    if ( key_exists ( 'target_system', $_POST ) ) $target_system = intval ($_POST['target_system']);
    else $target_system = $aktplanet['s'];

    if ( key_exists ( 'target_planet', $_POST ) ) $target_planet = intval ($_POST['target_planet']);
    else $target_planet = $aktplanet['p'];

    function planettype ($n)
    {
        if ( key_exists ( 'target_planettype', $_POST ) ) {
            if ( intval ($_POST['target_planettype']) == $n ) echo "selected";
        }
    }
?>
<input name="thisgalaxy" type="hidden" value="<?php echo $aktplanet['g'];?>" />
<input name="thissystem" type="hidden" value="<?php echo $aktplanet['s'];?>" />
<input name="thisplanet" type="hidden" value="<?php echo $aktplanet['p'];?>" />
<input name="thisplanettype" type="hidden" value="<?php echo GetPlanetType($aktplanet);?>" />
<input name="speedfactor" type="hidden" value="<?php echo $GlobalUni['fspeed'];?>" />
<input name="thisresource1" type="hidden" value="<?php echo floor($aktplanet['m']);?>" />
<input name="thisresource2" type="hidden" value="<?php echo floor($aktplanet['k']);?>" />
<input name="thisresource3" type="hidden" value="<?php echo floor($aktplanet['d']);?>" />

<?php

    // Список флотов.

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 213, 214, 215 );    // без солнечного спутника

    $total = 0;
    foreach ($fleetmap as $i=>$gid) 
    {
        // Ограничить количество флотов максимальным количеством на планете.
        if ( key_exists("ship$gid", $_POST) ) $amount = min ( $aktplanet["f$gid"] , abs (intval ($_POST["ship$gid"])) );
        else $amount = 0;
        $total += $amount;

        if ( $amount > 0 ) {
            if ( key_exists("ship$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"ship$gid\" value=\"".$amount."\" />\n";
            if ( key_exists("consumption$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"consumption$gid\" value=\"".intval ($_POST["consumption$gid"])."\" />\n";
            if ( key_exists("speed$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"speed$gid\" value=\"".intval($_POST["speed$gid"])."\" />\n";
            if ( key_exists("capacity$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"capacity$gid\" value=\"".intval ($_POST["capacity$gid"])."\" />\n";
        }
    }

    // Флот не выбран.
    if ( $total == 0 ) MyGoto ( "flotten1" );

?>


    <tr height="20">
  <td colspan="2" class="c">Отправление флота</td>
 </tr>

 <tr height="20">
  <th width="50%">Координаты цели</th>
  <th>
   <input name="galaxy" size="3" maxlength="2" onChange="shortInfo()" onKeyUp="shortInfo()" value="<?php echo $target_galaxy;?>" />
   <input name="system" size="3" maxlength="3" onChange="shortInfo()" onKeyUp="shortInfo()" value="<?php echo $target_system;?>" />
   <input name="planet" size="3" maxlength="2" onChange="shortInfo()" onKeyUp="shortInfo()" value="<?php echo $target_planet;?>" />
   <select name="planettype" onChange="shortInfo()" onKeyUp="shortInfo()">
     <option value="1" <?php echo planettype(1);?>><?php echo loca("FLEET_PLANETTYPE_1");?> </option>
  <option value="2" <?php echo planettype(2);?>><?php echo loca("FLEET_PLANETTYPE_2");?> </option>
  <option value="3" <?php echo planettype(3);?>><?php echo loca("FLEET_PLANETTYPE_3");?> </option>
   </select>
 </tr>
 <tr height="20">
  <th>Скорость</th>
  <th>

   <select name="speed" onChange="shortInfo()" onKeyUp="shortInfo()">
         <option value="10">100</option>
         <option value="9">90</option>
         <option value="8">80</option>
         <option value="7">70</option>
         <option value="6">60</option>
         <option value="5">50</option>
         <option value="4">40</option>
         <option value="3">30</option>
         <option value="2">20</option>
         <option value="1">10</option>
       </select> %
  </th>

 </tr>
 <tr height="20">
  <th>Расстояние</th><th><div id="distance">-</div></th>
 </tr>
 <tr height="20">
  <th>Продолжительность (в одну сторону)</th><th><div id="duration">-</div></th>
 </tr>
 <tr height="20">
  <th>Потребление топлива</th><th><div id="consumption">-</div></th>
 </tr>
 <tr height="20">
  <th>Максимальная скорость</th><th><div id="maxspeed">-</div></th>
 </tr>
 <tr height="20">
  <th>Грузоподъёмность</th><th><div id="storage">572.500</div></th>
 </tr>
  <tr height="20">
  <td colspan="2" class="c">Планета</td>
  </tr>

<?php

    // Список планет.
    $result = EnumPlanets ();
    $rows = dbrows ($result);
    $leftcol = true;
    while ($rows--)
    {
        $planet = dbarray ($result);
        if ( $planet['planet_id'] == $aktplanet['planet_id'] || GetPlanetType($planet) == 2 ) continue;
        if ( $leftcol ) echo "<tr height=\"20\">\n";
        echo "<th><a href=\"javascript:setTarget(".$planet['g'].",".$planet['s'].",".$planet['p'].",".GetPlanetType($planet)."); shortInfo()\">\n".$planet['name']." ".$planet['g'].":".$planet['s'].":".$planet['p']."</a></th>\n";
        if ( !$leftcol ) echo "</tr>\n";
        $leftcol ^= 1;
    }
    if ( !$leftcol ) {
        echo "     <th>&nbsp; </th>\n";
        echo "</tr>\n";
    }

?>

   </th>
  </tr>

  <tr height="20">
     <td colspan="2" class="c">Боевые союзы  </tr>

<?php

    // Список боевых союзов.
    $unions = EnumUnion ( $GlobalUser['player_id'], 1);

    $union_count = 0;
    foreach ( $unions as $i=>$union )
    {
        $fleet_obj = LoadFleet ( $union['fleet_id'] );
        if ( $fleet_obj['union_id'] == $union['union_id'] ) $union_count ++;
    }

    if ( $union_count > 0 )
    {
        echo "<input type=\"hidden\" name=\"union2\" value=\"0\" >";
        $now = time ();
        foreach ( $unions as $i=>$union )
        {
            $fleet_obj = LoadFleet ( $union['fleet_id'] );
            if ( $fleet_obj['union_id'] != $union['union_id'] ) continue;
            $queue = GetFleetQueue ( $union['fleet_id'] );
            $target = GetPlanet ( $fleet_obj['target_planet'] );
            echo "  <tr height=\"20\">";
            echo "<th><div id='bxx".($i+1)."' title='".max($queue['end']-$now, 0)."'star='".$queue['end']."'></div></th>";
            echo "<th><a href=\"javascript:setTarget(".$target['g'].",".$target['s'].",".$target['p'].",".GetPlanetType($target)."); setUnion(".$union['union_id']."); shortInfo()\">";
            echo $union['name']." [".$target['g'].":".$target['s'].":".$target['p']."]</a></th></tr>\n";
        }
        echo "<script language=javascript>anz=".$union_count.";t();</script>\n\n";
    }
    else echo " <tr height=\"20\"><th colspan=\"2\">-</th></tr>\n";
?>

<tr height="20">
 <th colspan="2">
  <input type="submit" value="Дальше" />
 </th>
</tr>

</form>
</table>

<script>
window.onload=shortInfo;
</script><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>