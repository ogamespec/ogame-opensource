<?php

/** @var array $GlobalUser */
/** @var array $GlobalUni */
/** @var array $fleetmap */
/** @var string $aktplanet */

// Fleet 2: Prepare target coordinates

if ( method() !== "POST" ) MyGoto ( "flotten1" );

?>


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

    function planettype (int $n) : void
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

    // Fleet List.

    $total = 0;
    $cargo = 0;
    $fleetmap_nosat = array_diff($fleetmap, [GID_F_SAT]);
    foreach ($fleetmap_nosat as $i=>$gid) 
    {
        // Limit the number of fleets to the maximum number on a planet.
        if ( key_exists("ship$gid", $_POST) ) $amount = min ( $aktplanet[$gid] , abs (intval ($_POST["ship$gid"])) );
        else $amount = 0;
        $total += $amount;

        if ($gid != GID_F_PROBE) $cargo += FleetCargo ($gid) * $amount;        // not counting probes.

        if ( $amount > 0 ) {
            if ( key_exists("ship$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"ship$gid\" value=\"".$amount."\" />\n";
            if ( key_exists("consumption$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"consumption$gid\" value=\"".intval ($_POST["consumption$gid"])."\" />\n";
            if ( key_exists("speed$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"speed$gid\" value=\"".intval($_POST["speed$gid"])."\" />\n";
            if ( key_exists("capacity$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"capacity$gid\" value=\"".intval ($_POST["capacity$gid"])."\" />\n";
        }
    }

    // The fleet is not selected.
    if ( $total == 0 ) MyGoto ( "flotten1" );

?>


    <tr height="20">
  <td colspan="2" class="c"><?=loca("FLEET2_SEND_FLEET");?></td>
 </tr>

 <tr height="20">
  <th width="50%"><?=loca("FLEET2_COORD");?></th>
  <th>
   <input name="galaxy" size="3" maxlength="2" onChange="shortInfo()" onKeyUp="shortInfo()" value="<?php echo $target_galaxy;?>" />
   <input name="system" size="3" maxlength="3" onChange="shortInfo()" onKeyUp="shortInfo()" value="<?php echo $target_system;?>" />
   <input name="planet" size="3" maxlength="2" onChange="shortInfo()" onKeyUp="shortInfo()" value="<?php echo $target_planet;?>" />
   <select name="planettype" onChange="shortInfo()" onKeyUp="shortInfo()">
     <option value="1" <?php planettype(1);?>><?php echo loca("FLEET_PLANETTYPE_1");?> </option>
  <option value="2" <?php planettype(2);?>><?php echo loca("FLEET_PLANETTYPE_2");?> </option>
  <option value="3" <?php planettype(3);?>><?php echo loca("FLEET_PLANETTYPE_3");?> </option>
   </select>
 </tr>
 <tr height="20">
  <th><?=loca("FLEET2_SPEED");?></th>
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
  <th><?=loca("FLEET2_DIST");?></th><th><div id="distance">-</div></th>
 </tr>
 <tr height="20">
  <th><?=loca("FLEET2_DURATION");?></th><th><div id="duration">-</div></th>
 </tr>
 <tr height="20">
  <th><?=loca("FLEET2_CONS");?></th><th><div id="consumption">-</div></th>
 </tr>
 <tr height="20">
  <th><?=loca("FLEET2_MAX_SPEED");?></th><th><div id="maxspeed">-</div></th>
 </tr>
 <tr height="20">
  <th><?=loca("FLEET2_CARGO");?></th><th><div id="storage"><?=nicenum($cargo);?></div></th>
 </tr>
  <tr height="20">
  <td colspan="2" class="c"><?=loca("FLEET2_HEAD_PLANETS");?></td>
  </tr>

<?php

    // List of planets.
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
     <td colspan="2" class="c"><?=loca("FLEET2_HEAD_ACS");?></tr>

<?php

    // List of battle unions (ACS)
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
  <input type="submit" value="<?=loca("FLEET2_NEXT");?>" />
 </th>
</tr>

</form>
</table>

<script>
window.onload=shortInfo;
</script><br><br><br><br>
