<?php

/** @var array $GlobalUni */
/** @var array $GlobalUser */
/** @var array $fleetmap */
/** @var string $db_prefix */

// Fleet 1: prepares the composition of the fleet

// Parameter passing between Fleet 1,2,3 pages is done via hidden POST parameters.

function FleetMissionText (int $num) : void
{
    if ($num >= FTYP_ORBITING)
    {
        $desc = "<a title=\"".loca("FLEET1_HOLD")."\">".loca("FLEET1_HOLD_SHORT")."</a>";
        $num -= FTYP_ORBITING;
    }
    else if ($num >= FTYP_RETURN)
    {
        $desc = "<a title=\"".loca("FLEET1_RETURN")."\">".loca("FLEET1_RETURN_SHORT")."</a>";
        $num -= FTYP_RETURN;
    }
    else $desc = "<a title=\"".loca("FLEET1_FLYING")."\">".loca("FLEET1_FLYING_SHORT")."</a>";

    echo "      <a title=\"\">".loca("FLEET_ORDER_$num")."</a>\n$desc\n";
}

$union_id = 0;

// POST requests processing
if ( method () === "POST" )
{
    if ( key_exists ( 'order_return', $_POST) )         // Fleet recall.
    {
        $fleet_id = intval ($_POST['order_return']);
        $fleet_obj = LoadFleet ( $fleet_id );
        if (  ($fleet_obj['owner_id'] == $GlobalUser['player_id']) &&
              ($fleet_obj['mission'] < FTYP_RETURN || $fleet_obj['mission'] > FTYP_ORBITING )  ) 
            RecallFleet ( $fleet_id );
    }

    if ( key_exists ( 'union_name', $_POST) && $GlobalUni['acs'] > 0 ) {
        $fleet_id = intval ($_POST['flotten']);
        $union_id = CreateUnion ($fleet_id, "KV" . $fleet_id);
        RenameUnion ( $union_id, $_POST['union_name'] );    // rename
    }

    if ( key_exists ( 'user_name', $_POST) && $GlobalUni['acs'] > 0 ) { 
        $fleet_id = intval ($_POST['flotten']);
        $union_id = CreateUnion ($fleet_id, "KV" . $fleet_id);
        $PageError = AddUnionMember ( $union_id, $_POST['user_name'] );    // add player
    }
}

$result = EnumOwnFleetQueue ( $GlobalUser['player_id'] );    // Number of fleets
$nowfleet = $rows = dbrows ($result);
$maxfleet = $GlobalUser[GID_R_COMPUTER] + 1;

$prem = PremiumStatus ($GlobalUser);
if ( $prem['admiral'] ) $maxfleet += 2;

$expnum = GetExpeditionsCount ( $GlobalUser['player_id'] );    // Number of expeditions
$maxexp = floor ( sqrt ( $GlobalUser[GID_R_EXPEDITION] ) );

?>

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
    <div style="margin-top:2;margin-bottom:2;"><?=va(loca("FLEET1_FLEETS"), $rows, $maxfleet-2);?> <b><font style="color:lime;">+2</font></b> <img border="0" alt="<?=loca("PR_ADMIRAL");?>" src="img/admiral_ikon.gif" onmouseover='return overlib("&lt;font color=white &gt;<?=loca("PR_ADMIRAL");?>&lt;/font&gt;", WIDTH, 100);' onmouseout="return nd();" width="20" height="20" style="vertical-align:middle;"></div>
<?php
    }
    else
    {
?>
    <?=va(loca("FLEET1_FLEETS"), $rows, $maxfleet);?>    </td>
<?php
    }
?>
    <td align=right style='background-color:transparent;'>
      <?=va(loca("FLEET1_EXPEDITIONS"), $expnum, $maxexp);?>    
    </td>
    </tr>
    </table>
    </td>

   </tr>
     <tr height="20">
    <th><?=loca("FLEET1_HEAD1");?></th>
    <th><?=loca("FLEET1_HEAD2");?></th>
    <th><?=loca("FLEET1_HEAD3");?></th>
    <th><?=loca("FLEET1_HEAD4");?></th>

    <th><?=loca("FLEET1_HEAD5");?></th>
    <th><?=loca("FLEET1_HEAD6");?></th>
    <th><?=loca("FLEET1_HEAD7");?></th>
    <th><?=loca("FLEET1_HEAD8");?></th>
   </tr>
<?php

    if ($rows)
    {
        $row = 1;
        while ($rows--)
        {
            $queue = dbarray ($result);
            $fleet = LoadFleet ($queue['sub_id']);
            $origin = LoadPlanetById ($fleet['start_planet']);
            if ($origin == null) $origin = array ('g' => 0, 's' => 0, 'p' => 0);
            $target = LoadPlanetById ($fleet['target_planet']);
            if ($target == null) { 
                $target = array ('g' => 0, 's' => 0, 'p' => 0, 'type' => PTYP_ABANDONED);
                $target_user = array ( 'oname' => 'space' );
            }
            else {
                $target_user = LoadUser ( $target['owner_id'] );
            }
?>
     <tr height="20">
    <th><?php echo $row;?></th>

    <th>
<?php
    FleetMissionText ($fleet['mission']);
?>
    </th>
    <th> <a title="<?php
        $totalships = 0;
        foreach ( $fleetmap as $i=>$gid)
        {
            if ( $fleet[$gid] > 0 ) {
                echo loca("NAME_$gid") . ": " . nicenum($fleet[$gid]) . " \n";
                $totalships += $fleet[$gid];
            }
        }
?>
"><?php echo nicenum($totalships);?></a></th>
    <th><a href="index.php?page=galaxy&galaxy=<?php echo $origin['g'];?>&system=<?php echo $origin['s'];?>&position=<?php echo $origin['p'];?>&session=<?php echo $session;?>" >[<?php echo $origin['g'];?>:<?php echo $origin['s'];?>:<?php echo $origin['p'];?>]</a></th>

    <th><?php echo date ( "D M j G:i:s", $queue['start']);?></th>
    <th><a href="index.php?page=galaxy&galaxy=<?php echo $target['g'];?>&system=<?php echo $target['s'];?>&position=<?php echo $target['p'];?>&session=<?php echo $session;?>" >[<?php echo $target['g'];?>:<?php echo $target['s'];?>:<?php echo $target['p'];?>]</a><?php
    if ( ! ($target['type'] == PTYP_COLONY_PHANTOM || $target['type'] == PTYP_FARSPACE || $target['type'] == PTYP_ABANDONED ) ) echo "   <br />" . $target_user['oname'];
?>    </th>
    <th><?php echo date ( "D M j G:i:s", $queue['end']);?></th>
    <th>
<?php
    if ( ($fleet['mission'] == FTYP_ATTACK || $fleet['mission'] == FTYP_ACS_ATTACK_HEAD) && $GlobalUni['acs'] > 0 )
    {
?>
         <form action="index.php?page=flotten1&session=<?php echo $session;?>" method="POST">
    <input type="hidden" name="order_union" value="<?php echo $fleet['fleet_id'];?>" />
        <input type="submit" value="<?=loca("FLEET1_BUTTON_ACS");?>" />
     </form>
<?php
    }
?>
<?php
    if ( $fleet['mission'] < FTYP_RETURN || $fleet['mission'] > FTYP_ORBITING )
    {
?>
         <form action="index.php?page=flotten1&session=<?php echo $session;?>" method="POST">
    <input type="hidden" name="order_return" value="<?php echo $fleet['fleet_id'];?>" />
        <input type="submit" value="<?=loca("FLEET1_BUTTON_RECALL");?>" />
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
// ************************ ACS attack creation form ************************

    if ( key_exists ( 'order_union', $_POST) && $GlobalUni['acs'] > 0 )
    {
        $fleet = LoadFleet ( intval ($_POST['order_union']) );
        if ( $fleet['union_id'] ) $union = LoadUnion ( $fleet['union_id'] ); 
        else {
            $union = array ();
            $union['name'] = "KV" . $fleet['fleet_id'];
            $union["player"][] = $GlobalUser['player_id'];
        }

?>

<form action="index.php?page=flotten1&session=<?php echo $session;?>" method="POST">
    <input type="hidden" name="flotten" value="<?php echo $fleet['fleet_id'];?>" />
  <table width="519" border="0" cellpadding="0" cellspacing="1">
                    <tr><td class="c" colspan=2><?=va(loca("FLEET1_ACS_NAME"), $union['name']);?></td></tr>
                    <tr><td class="c" colspan=2><?=loca("FLEET1_ACS_TITLE");?></td></tr>
                    <tr><th colspan=2>
<input name="union_name" type="text" value="<?php echo $union['name'];?>" /> <br /><input type="submit" value="OK" />
                    </th></tr>
                    <tr>
                        <td class="c"><?=loca("FLEET1_ACS_PLAYERS");?></td>
                        <td class="c"><?=loca("FLEET1_ACS_INVITE");?></td>
                    </tr>
                    <tr>
                        <th width="50%">
                            <select size="5">
<?php
    for ($i=0; $i<=$union['players']; $i++)
    {
        $player_id = $union["player"][$i];
        //if ($player_id == $GlobalUser['player_id']) continue;    // keep yourself off the invitation list
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

  
<form action="index.php?page=flotten2&session=<?php echo $session;?>" method="POST">
<?php
    if ( key_exists ( 'galaxy', $_GET ) ) {
        $target_galaxy = intval ($_GET['galaxy']);

        if ( key_exists ( 'system', $_GET ) ) $target_system = intval ($_GET['system']);
        else  $target_system = 0;

        if ( key_exists ( 'planet', $_GET ) ) $target_planet = intval ($_GET['planet']);
        else  $target_planet = 0;

        if ( key_exists ( 'planettype', $_GET ) ) $target_planettype = intval ($_GET['planettype']);
        else  $target_planettype = 0;

        if ( key_exists ( 'target_mission', $_GET ) ) $target_mission = intval ($_GET['target_mission']);
        else  $target_mission = 0;
?>
     <input type="hidden" name="target_galaxy" value="<?php echo $target_galaxy;?>" />
   <input type="hidden" name="target_system" value="<?php echo $target_system;?>" />
   <input type="hidden" name="target_planet" value="<?php echo $target_planet;?>" />
   <input type="hidden" name="target_planettype" value="<?php echo $target_planettype;?>" />
   <input type="hidden" name="target_mission" value="<?php echo $target_mission;?>" />
<?php
    }
?>
  <table width="519" border="0" cellpadding="0" cellspacing="1">
<?php
    if ($nowfleet >= $maxfleet)
    {
?>
         <tr height="20">
      <th colspan="4"><font color="red"><?=loca("FLEET1_ERROR_MAX");?></font></th>
   </tr>
<?php
    }
?>
       <tr height="20">
  <td colspan="4" class="c"><?=loca("FLEET1_TITLE_CHOOSE");?></td>
   </tr>
   <tr height="20">

  <th><?=loca("FLEET1_TYPE");?></th>
  <th><?=loca("FLEET1_AMOUNT");?></th>
<!--    <th>Gesch.</th> -->
    <th>-</th>
    <th>-</th>
   </tr>

<?php

    foreach ($fleetmap as $i=>$gid) {
        
        $amount = $aktplanet[$gid];
        if ($amount > 0) {
            $speed = FleetSpeed ($gid, $GlobalUser[GID_R_COMBUST_DRIVE], $GlobalUser[GID_R_IMPULSE_DRIVE], $GlobalUser[GID_R_HYPER_DRIVE]);
            $cargo = FleetCargo ($gid );
            $cons = FleetCons ( $gid, $GlobalUser[GID_R_COMBUST_DRIVE], $GlobalUser[GID_R_IMPULSE_DRIVE], $GlobalUser[GID_R_HYPER_DRIVE]);

            echo "   <tr height=\"20\">\n";
            echo "    <th><a title=\"".loca("FLEET1_SPEED").": $speed\">".loca("NAME_$gid")."</a></th>\n";
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
  <th colspan="2"><a href="javascript:noShips();" ><?=loca("FLEET1_CLEAR");?></a></th>
  <th colspan="2"><a href="javascript:maxShips();" ><?=loca("FLEET1_ALL_SHIPS");?></a></th>
   </tr>

<?php
    if ( $prem['commander'] )        // Standard fleets (templates)
    {
        $temp_map = array_diff($fleetmap, [GID_F_SAT]);    // without solar sat

        echo "      <tr height=\"20\">\n";
        echo "      <td colspan=\"4\" class=\"c\"><u><a href=\"index.php?page=fleet_templates&session=$session\">".loca("FLEET1_TEMPLATE")."</a></u></td>\n";
        echo "      </tr>\n";

        $query = "SELECT * FROM ".$db_prefix."template WHERE owner_id = ".$GlobalUser['player_id']." ORDER BY date DESC";
        $result = dbquery ( $query );
        $rows = dbrows ( $result );
        $count = 0;
        while ( $rows-- )
        {
            if ( $count == 0 ) echo "                  <tr height=\"20\" >\n";
            $temp = dbarray ( $result );

            echo "       <th colspan=2>\n";
            echo "       <a href=\"javascript:setShips(";
            foreach ( $temp_map as $i=>$gid ) {
                if ( $i ) echo ",";
                echo $temp[$gid];
            }
            echo ");\">\n";
            echo "       ".$temp['name']."</a>\n";
            echo "        </th>\n";

            $count++;
            if ( $count == 2 ) {
                echo "           </tr>\n";
                $count = 0;
            }
        }
    }
?>
 
   <tr height="20">
    <th colspan="4"><input type="submit" value="<?=loca("FLEET1_NEXT");?>" /></th>
   </tr>
<tr><th colspan=4>
</th></tr>
</form>
</table>
<br><br><br><br>
