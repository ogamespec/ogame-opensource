<?php

/** @var array $GlobalUser */
/** @var array $fleetmap */

// Fleet 3: mission list output, resource loading.

$fleetmap_nosat = array_diff($fleetmap, [GID_F_SAT]);

if ( method() !== "POST" ) MyGoto ( "flotten1" );

$uni = LoadUniverse ();

$galaxy = floor ( abs ( intval($_POST['galaxy']) ) );
$system = floor ( abs ( intval ($_POST['system']) ) );
$planet = floor ( abs ( intval ($_POST['planet']) ) );

if ( $galaxy < 1 ) $galaxy = 1;
if ( $galaxy > $uni['galaxies'] ) $galaxy = $uni['galaxies'];

if ( $system < 1 ) $system = 1;
if ( $system > $uni['systems'] ) $system = $uni['systems'];

if ( $planet < 0 ) $planet = 0;
if ( $planet > 16 ) $planet = 16;

?>

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

<form action="index.php?page=flottenversand&session=<?php echo $session;?>" method="POST">

<?php
    // Target coordinates and resource data.
    echo "<input name=\"thisgalaxy\" type=\"hidden\" value=\"".intval($_POST['thisgalaxy'])."\" />\n";
    echo "<input name=\"thissystem\" type=\"hidden\" value=\"".intval($_POST['thissystem'])."\" />\n";
    echo "<input name=\"thisplanet\" type=\"hidden\" value=\"".intval($_POST['thisplanet'])."\" />\n";
    echo "<input name=\"thisplanettype\" type=\"hidden\" value=\"".intval($_POST['thisplanettype'])."\" />\n";
    echo "<input name=\"speedfactor\" type=\"hidden\" value=\"".intval($_POST['speedfactor'])."\" />\n";
    echo "<input name=\"thisresource1\" type=\"hidden\" value=\"".floor($aktplanet['m'])."\" />\n";
    echo "<input name=\"thisresource2\" type=\"hidden\" value=\"".floor($aktplanet['k'])."\" />\n";
    echo "<input name=\"thisresource3\" type=\"hidden\" value=\"".floor($aktplanet['d'])."\" />\n";
    echo "<input name=\"galaxy\" type=\"hidden\" value=\"".$galaxy."\" />\n";
    echo "<input name=\"system\" type=\"hidden\" value=\"".$system."\" />\n";
    echo "<input name=\"planet\" type=\"hidden\" value=\"".$planet."\" />\n";
    echo "<input name=\"planettype\" type=\"hidden\" value=\"".intval($_POST['planettype'])."\" />\n\n";

    // Fleet List.

    $total = 0;
    foreach ($fleetmap_nosat as $i=>$gid) 
    {
        // Limit the number of fleets to the maximum number on a planet.
        if ( key_exists("ship$gid", $_POST) ) $amount = min ( $aktplanet["f$gid"] , abs ( intval($_POST["ship$gid"]) ) );
        else $amount = 0;
        $total += $amount;

        if ( $amount > 0 ) {
            if ( key_exists("ship$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"ship$gid\" value=\"".$amount."\" />\n";
            if ( key_exists("consumption$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"consumption$gid\" value=\"".intval($_POST["consumption$gid"])."\" />\n";
            if ( key_exists("speed$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"speed$gid\" value=\"".intval($_POST["speed$gid"])."\" />\n";
            if ( key_exists("capacity$gid", $_POST) ) echo "   <input type=\"hidden\" name=\"capacity$gid\" value=\"".intval($_POST["capacity$gid"])."\" />\n";
        }
    }

    // The fleet has not been selected.
    if ( $total == 0 ) MyGoto ( "flotten1" );

    echo "<input type=\"hidden\" name=\"speed\" value=\"".intval($_POST['speed'])."\" />\n";
?>

<tr height="20" align="left">
<td class="c" colspan="2"><?php echo $galaxy;?>:<?php echo $system;?>:<?php echo $planet;?> - <?php echo loca("FLEET_PLANETTYPE_".intval($_POST['planettype']));?></td>

</tr>
<tr valign="top" align="left">
<th width="50%">
  <table width="259" border="0"  cellpadding="0" cellspacing="0" >
  <tr height="20">
  <td class="c" colspan="2"><?=loca("FLEET3_ORDER");?></td>
  </tr>

<?php
    // Display a list of available missions.

    function is_checked (int $mission) : string
    {
        if ( key_exists ( 'target_mission', $_POST ) ) {
            if ( intval($_POST['target_mission']) == $mission ) return "checked";
        }
        return "";
    }

    function is_selected (int $union_id) : string
    {
        if ( key_exists ( 'union2', $_POST ) ) {
            if ( intval($_POST['union2']) == $union_id ) return "selected";
        }
        return "";
    }

    $mission_acs = $mission_exp = $mission_hold = false;

    $fleet = array ();

    foreach ($fleetmap_nosat as $i=>$gid) 
    {
        if ( key_exists("ship$gid", $_POST) ) $fleet[$gid] = intval($_POST["ship$gid"]);
        else $fleet[$gid] = 0;
    }

    $missions = FleetAvailableMissions ( 
					intval($_POST['thisgalaxy']), intval($_POST['thissystem']), intval($_POST['thisplanet']), intval($_POST['thisplanettype']),
					$galaxy, $system, $planet, intval($_POST['planettype']), $fleet );

    if ( count ($missions) == 0 )
    {
        echo "<tr>\n";
        echo "   <th><font color=\"red\">".loca("FLEET3_NO_ORDER")."</font></th>\n";
        echo "</tr>\n";
    }
    else
    {
        foreach ($missions as $i=>$id) 
        {
            if ( $id == FTYP_ACS_ATTACK ) $mission_acs = true;
            if ( $id == FTYP_ACS_HOLD ) $mission_hold = true;
            if ( $id == FTYP_EXPEDITION ) $mission_exp = true;

            if ($id == FTYP_EXPEDITION)        // Экспедиция.
            {
                echo "    <tr height=\"20\">\n";
                echo "<th>\n";
                echo "  <input type=\"radio\" name=\"order\" value=\"".FTYP_EXPEDITION."\" checked='checked'>".loca("FLEET_ORDER_$id")."<br />\n";
                echo "  <br><font color=red>".loca("FLEET3_EXP_WARNING")."</font>   </th>\n";
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
  <td colspan="3" class="c"><?=loca("FLEET3_RESOURCES");?></td>
     </tr>
       <tr height="20">
      <th><?=loca("METAL");?></th>
      <th><a href="javascript:maxResource('1');">max</a></th>

      <th><input name="resource1" type="text" alt="<?=loca("METAL");?> <?php echo floor($aktplanet['m']);?>" size="10" onChange="calculateTransportCapacity();" /></th>
     </tr>
       <tr height="20">
      <th><?=loca("CRYSTAL");?></th>
      <th><a href="javascript:maxResource('2');">max</a></th>
      <th><input name="resource2" type="text" alt="<?=loca("CRYSTAL");?> <?php echo floor($aktplanet['k']);?>" size="10" onChange="calculateTransportCapacity();" /></th>
     </tr>
       <tr height="20">

      <th><?=loca("DEUTERIUM");?></th>
      <th><a href="javascript:maxResource('3');">max</a></th>
      <th><input name="resource3" type="text" alt="<?=loca("DEUTERIUM");?> <?php echo floor($aktplanet['d']);?>" size="10" onChange="calculateTransportCapacity();" /></th>
     </tr>
       <tr height="20">
  <th><?=loca("FLEET3_RES_LEFT");?></th>
      <th colspan="2"><div id="remainingresources">-</div></th>

     </tr>
     <tr height="20">
  <th colspan="3"><a href="javascript:maxResources()"><?=loca("FLEET3_RES_ALL");?></a></th>
     </tr>

  <tr height="20">
  <th>&nbsp; </th>
  </tr>


<?php
    // ----------------------------------------------------------------------------------------------------
    // List of battle unions (ACS)

    $unions = EnumUnion ( $GlobalUser['player_id'] );

    if ( $mission_acs && count($unions) > 0 )
    {
?>

    <tr height="20">
     <td class="c" colspan="3"><?=loca("FLEET3_ACS");?></td>
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

<?php
    }
?>

<?php
    // ----------------------------------------------------------------------------------------------------
    // Hold time

    if ( $mission_hold )
    {
?>

    <tr height="20">
     <td class="c" colspan="3"><?=loca("FLEET3_HOLD_TIME");?></td>
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
      <?=loca("FLEET3_HOLD_HOURS");?>   </th>
  </tr>

<?php
    }
?>


<?php
    // ----------------------------------------------------------------------------------------------------
    // Expedition duration time

    if ( $mission_exp && $GlobalUser['r'.GID_R_EXPEDITION] > 0 )
    {
?>

    <tr height="20">
     <td class="c" colspan="3"><?=loca("FLEET3_HOLD_TIME");?></td>
  </tr>
  <tr height="20">
   <th colspan="3">
    <select name="expeditiontime" >
<?php
    for ($i=1; $i<=$GlobalUser['r'.GID_R_EXPEDITION]; $i++)
    {
        echo "          <option value=\"$i\">$i</option>\n";
    }
?>           </select> 
      <?=loca("FLEET3_HOLD_HOURS");?>   </th>
  </tr>


<?php
    }
?>

   
    </table>
</th>
</tr>
<tr height="20" >
 <th colspan="2"><input type="submit" value="<?=loca("FLEET3_NEXT");?>" /></th>
</tr>
 </form>
</table><br><br><br><br>
