<?php

// Information on buildings, fleets, defense and research.
// Some pages (particularly buildings) contain additional information or controls.

// Small transport contains additional text (yellow), to indicate the change in base speed and consumption after a engine change.
// A bomber does NOT have a change in consumption after an engine change.

$speed = $GlobalUni['speed'];
$drepair = $GlobalUni['defrepair'];

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "techlong", $GlobalUser['lang'] );
loca_add ( "jumpgate", $GlobalUser['lang'] );
loca_add ( "infos", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet']);
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

// ***************************************************************************************

function rgnum ($num)
{
    if ($num < 0) return "<font color=\"#FF0000\">".nicenum($num)."</font>";
    else if ($num > 0) return "<font color=\"#00FF00\">".nicenum($num)."</font>";
    else return nicenum($num);
}

function rapidIn ($gid, $n)
{
    return "<br/>".loca("INFO_RAPID_IN1")."<a href=\"index.php?page=infos&session=".$_GET['session']."&gid=$gid\">".loca("NAME_$gid")."</a>".va(loca("INFO_RAPID_IN2"), "<font color=\"red\">$n</font>")."\n";
}

function rapidOut ($gid, $n)
{
    return "<br/>".loca("INFO_RAPID_OUT1")."<a href=\"index.php?page=infos&session=".$_GET['session']."&gid=$gid\">".loca("NAME_$gid")."</a>".va(loca("INFO_RAPID_OUT2"), "<font color=\"lime\">$n</font>")."\n";
}

// Rapid-fire information.
function rapid ($gid)
{
    global $RapidFire;
    $res = "";
    for ($n=202; $n<=215; $n++) if ( key_exists($n, $RapidFire[$gid]) && $RapidFire[$gid][$n] > 1 ) $res .= rapidOut ( $n, $RapidFire[$gid][$n] );
    for ($n=401; $n<=408; $n++) if ( key_exists($n, $RapidFire[$gid]) && $RapidFire[$gid][$n] > 1 ) $res .= rapidOut ( $n, $RapidFire[$gid][$n] );
    for ($n=202; $n<=215; $n++) if ( key_exists($gid, $RapidFire[$n]) && $RapidFire[$n][$gid] > 1 ) $res .= rapidIn ( $n, $RapidFire[$n][$gid] );
    for ($n=401; $n<=408; $n++) if ( key_exists($gid, $RapidFire[$n]) && $RapidFire[$n][$gid] > 1 ) $res .= rapidIn ( $n, $RapidFire[$n][$gid] );
    return $res;
}

$gid = intval($_GET['gid']);

PageHeader ("infos");

BeginContent ();

echo "<table width=\"519\">\n";

if (IsFleet($gid))    // Fleet
{
    $base_speed = $UnitParam[$gid][4];
    $base_cons = $UnitParam[$gid][5];
    $base_speed2 = 0;
    $base_cons2 = 0;

    // The base values for Small Cargo and Bomber change when you change engines

    if ($gid == 202) {
        $base_speed2 = $base_speed + 5000;
        $base_cons2 = $base_cons * 2;
    }
    else if ($gid == 211) {
        $base_speed2 = $base_speed + 1000;
        // Consumption doesn't change.
    }

    echo "<!-- begin fleet or defense information -->\n";
    echo "<tr><td class=\"c\" colspan=\"2\">".loca("INFO_FLEET")."</td></tr>\n";
    echo "<tr><th>".loca("INFO_NAME")."</th><th>".loca("NAME_$gid")."</th></tr>\n";
    echo "<tr><th colspan=\"2\">\n";
    echo "<table border=\"0\">\n";
    echo "<tr><td valign=\"top\"><img border=\"0\" src=\"".UserSkin()."gebaeude/$gid.gif\" width=\"120\" height=\"120\"></td>\n";
    echo "<td>".loca("LONG_$gid")."<br/>".rapid($gid)."</td>\n";
    echo "</tr></table></th></tr>\n";
    echo "<tr><th>".loca("INFO_STRUCTURE")."</th><th>".nicenum($UnitParam[$gid][0])."</th></tr>\n";
    echo "<tr><th>".loca("INFO_SHIELD")."</th><th>".nicenum($UnitParam[$gid][1])."</th></tr>\n";
    echo "<tr><th>".loca("INFO_ATTACK")."</th><th>".nicenum($UnitParam[$gid][2])."</th></tr>\n";
    echo "<tr><th>".loca("INFO_CARGO")."</th><th>".nicenum(FleetCargo($gid)).loca("INFO_UNITS")."</th></tr>\n";
    echo "<tr><th>".loca("INFO_BASE_SPEED")."</th><th>".nicenum($base_speed);
    if ($base_speed2 != 0) {
        echo "             <font color=\"yellow\">(". nicenum($base_speed2) .")</font> \n           ";
    }
    echo "</th></tr>\n";
    echo "<tr><th>".loca("INFO_BASE_CONS")."</th><th>".nicenum($base_cons);
    if ($base_cons2 != 0) {
        echo "             <font color=\"yellow\">(". nicenum($base_cons2) .")</font> \n           ";
    }
    echo "</th></tr>\n";
    echo "</table></th></tr></table>\n";
}
else if (IsDefenseNoRak($gid))    // Defense.
{
    echo "<!-- begin fleet or defense information -->\n";
    echo "<tr><td class=\"c\" colspan=\"2\">".loca("INFO_DEFENSE")."</td></tr>\n";
    echo "<tr><th>".loca("INFO_NAME")."</th><th>".loca("NAME_$gid")."</th></tr>\n";
    echo "<tr><th colspan=\"2\">\n";
    echo "<table border=\"0\">\n";
    echo "<tr><td valign=\"top\"><img border=\"0\" src=\"".UserSkin()."gebaeude/$gid.gif\" width=\"120\" height=\"120\"></td>\n";
    echo "<td>".loca("LONG_$gid");
    if (IsDefenseShoot($gid)) {
        // For shooting defenses, output the damage repair percentage.
        echo " " . va(loca("INFO_REPAIR"), $drepair);
    }
    echo "<br/>".rapid($gid)."</td>\n";
    echo "</tr></table></th></tr>\n";
    echo "<tr><th>".loca("INFO_STRUCTURE")."</th><th>".nicenum($UnitParam[$gid][0])."</th></tr>\n";
    echo "<tr><th>".loca("INFO_SHIELD")."</th><th>".nicenum($UnitParam[$gid][1])."</th></tr>\n";
    echo "<tr><th>".loca("INFO_ATTACK")."</th><th>".nicenum($UnitParam[$gid][2])."</th></tr>\n";
    echo "</th></tr></table>\n";
}
else if (IsResearch($gid))    // Research.
{
    echo "<tr><td class=\"c\">".loca("NAME_$gid")."</td></tr>\n";
    echo "<tr><th><table>\n";
    echo "<tr><td><img border=\"0\" src=\"".UserSkin()."gebaeude/$gid.gif\" align=\"top\" width=\"120\" height=\"120\"></td>\n";
    echo "<td>".loca("LONG_$gid")."</td></tr>\n";
    echo "</table></th></tr>\n";
    echo "</table>\n";
}
else
{
    echo "<tr><td class=\"c\">".loca("NAME_$gid")."</td></tr>\n";
    echo "<tr><th><table>\n";
    echo "<tr><td><img border=\"0\" src=\"".UserSkin()."gebaeude/$gid.gif\" align=\"top\" width=\"120\" height=\"120\"></td>\n";
    echo "<td>".loca("LONG_$gid")."</td></tr>\n";
    echo "</table></th></tr>\n";

    // Additional information and buttons.

    if ($gid == GID_B_METAL_MINE)    // Metal mine
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>".loca("INFO_LEVEL")."</td><td class='c'>".loca("INFO_PROD")."</td><td class='c'>".loca("INFO_DIFF")."</td><td class='c'>".loca("INFO_ENERGY")."</td><td class='c'>".loca("INFO_DIFF")."</td> \n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_metal ($aktplanet['b'.$gid], 1 );
        $cons_now = -cons_metal ($aktplanet['b'.$gid]);
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_metal ($i, 1 ) * $speed;
            $cons = -cons_metal ($i);

            if ($i==$aktplanet['b'.$gid]) echo "<tr> <th> <font color=#FF0000>$i</font></th> ";
            else echo "<tr> <th> $i</th> ";
            echo "<th> " . nicenum($prod). "</th> ";
            echo "<th> " . rgnum($prod-$prod_now) . "</th> ";
            echo "<th> " . nicenum ($cons) . "</th> ";
            echo "<th> " . rgnum ($cons-$cons_now) ." </th> </tr> \n";
        }
        echo "</table></center></tr></th>";
    }
    else if ($gid == GID_B_CRYS_MINE)    // Crystal mine
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>".loca("INFO_LEVEL")."</td><td class='c'>".loca("INFO_PROD")."</td><td class='c'>".loca("INFO_DIFF")."</td><td class='c'>".loca("INFO_ENERGY")."</td><td class='c'>".loca("INFO_DIFF")."</td> \n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_crys ($aktplanet['b'.$gid], 1 );
        $cons_now = -cons_crys ($aktplanet['b'.$gid]);
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_crys ($i, 1 ) * $speed;
            $cons = -cons_crys ($i);

            if ($i==$aktplanet['b'.$gid]) echo "<tr> <th> <font color=#FF0000>$i</font></th> ";
            else echo "<tr> <th> $i</th> ";
            echo "<th> " . nicenum($prod). "</th> ";
            echo "<th> " . rgnum($prod-$prod_now) . "</th> ";
            echo "<th> " . nicenum ($cons) . "</th> ";
            echo "<th> " . rgnum ($cons-$cons_now) ." </th> </tr> \n";
        }
        echo "</table></center></tr></th>";
    }
    else if ($gid == GID_B_DEUT_SYNTH)    // Deuterium synthesizer
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>".loca("INFO_LEVEL")."</td><td class='c'>".loca("INFO_PROD")."</td><td class='c'>".loca("INFO_DIFF")."</td><td class='c'>".loca("INFO_ENERGY")."</td><td class='c'>".loca("INFO_DIFF")."</td> \n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_deut ($aktplanet['b'.$gid], $aktplanet['temp']+40, 1 );
        $cons_now = -cons_deut ($aktplanet['b'.$gid]);
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_deut ($i, $aktplanet['temp']+40, 1 ) * $speed;
            $cons = -cons_deut ($i);

            if ($i==$aktplanet['b'.$gid]) echo "<tr> <th> <font color=#FF0000>$i</font></th> ";
            else echo "<tr> <th> $i</th> ";
            echo "<th> " . nicenum($prod). "</th> ";
            echo "<th> " . rgnum($prod-$prod_now) . "</th> ";
            echo "<th> " . nicenum ($cons) . "</th> ";
            echo "<th> " . rgnum ($cons-$cons_now) ." </th> </tr> \n";
        }
        echo "</table></center></tr></th>";
    }
    else if ($gid == GID_B_SOLAR)    // Solar Plant
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>".loca("INFO_LEVEL")."</td><td class='c'>".loca("INFO_ENERGY")."</td><td class='c'>".loca("INFO_DIFF")."</td>\n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_solar ($aktplanet['b'.$gid], 1 );
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_solar ($i, 1 );

            if ($i==$aktplanet['b'.$gid]) echo "<tr> <th> <font color=#FF0000>$i</font></th> ";
            else echo "<tr> <th> $i</th> ";
            echo "<th> " . nicenum($prod). "</th> ";
            echo "<th> " . rgnum($prod-$prod_now) . "</th> </tr> \n";
        }
        echo "</table></center></tr></th>";
    }
    else if ($gid == GID_B_FUSION)    // Fusion Reactor
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>".loca("INFO_LEVEL")."</td><td class='c'>".loca("INFO_ENERGY")."</td><td class='c'>".loca("INFO_DIFF")."</td><td class='c'>".loca("INFO_CONS_DEUT")."</td><td class='c'>".loca("INFO_DIFF")."</td>\n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_fusion ($aktplanet['b'.$gid], $GlobalUser['r113'], 1 );
        $cons_now = -cons_fusion ($aktplanet['b'.$gid], 1 );
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_fusion ($i, $GlobalUser['r113'], 1 );
            $cons = -cons_fusion ($i, 1 ) * $speed;

            if ($i==$aktplanet['b'.$gid]) echo "<tr> <th> <font color=#FF0000>$i</font></th> ";
            else echo "<tr> <th> $i</th> ";
            echo "<th> " . nicenum($prod). "</th> ";
            echo "<th> " . rgnum($prod-$prod_now) . "</th> \n";
            echo "<th> " . nicenum($cons). "</th> ";
            echo "<th> " . rgnum($cons-$cons_now) . "</th> </tr> \n";
        }
        echo "</table></center></tr></th>";
    }
    else if ($gid == GID_B_METAL_STOR || $gid == GID_B_CRYS_STOR || $gid == GID_B_DEUT_STOR )     // Storages
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>".loca("INFO_LEVEL")."</td><td class='c'>".loca("INFO_STORAGE")."</td><td class='c'>".loca("INFO_DIFF")."</td></tr>\n";
        $level = $aktplanet['b'.$gid];
        $cap_now = store_capacity ( $aktplanet['b'.$gid] ) / 1000;
        for ($i=$level; $i<$level+15; $i++) {
            $cap = store_capacity ( $i ) / 1000;
            if ($i == $aktplanet['b'.$gid]) echo "<tr> <th> <font color=#FF0000>$i</font></th> <th>".nicenum($cap)." k</th> <th>0</th> </tr>\n";
            else echo "<tr> <th> $i</th> <th>".nicenum($cap)." k</th> <th> <font color=\"#00FF00\">".nicenum($cap-$cap_now)." k</font></th> </tr>\n";
        }
        echo "</table>";
    }
    else if ( $gid == GID_B_ALLY_DEPOT )                                    // Alliance Depot
    {
        $depot_cap = 10000 * pow ( 2, $aktplanet['b'.GID_B_ALLY_DEPOT] );
        $deut_avail = 0;
        if ($aktplanet['b34']) $deut_avail = min(floor($aktplanet['d']), $depot_cap);
?>
    </th>
   </tr>
</table>
<form action="index.php?page=allianzdepot&session=<?=$session;?>" method=post>

<table width='519'>
<td class='c' colspan='2'><?=va(loca("INFO_DEPOT_CAPACITY"), $deut_avail, $depot_cap);?></td>
<?php

    $result = GetHoldingFleets ($aktplanet['planet_id']);
    $rows = dbrows ($result);
    $c = 1;
    while ($rows--)
    {
        $fleet_obj = dbarray ( $result );
        $queue = GetFleetQueue ( $fleet_obj['fleet_id'] );
        $user = LoadUser ($fleet_obj['owner_id']);

        $load = $queue['end'] - $now;

        echo "  <tr>\n";
        echo "    <th>".va(loca("INFO_DEPOT_FLEET"), $user['oname'])."<br>";
        $cons = 0;
        foreach ($fleetmap_nosat as $i=>$id) {
            $amount = $fleet_obj["ship".$id];
            if ($amount > 0) { 
                echo loca ("NAME_".$id).":".$amount."<br>";
                $cons += $amount * FleetCons ($id, $user['r115'], $user['r117'], $user['r118']) / 10;
            }
        }
        echo "</th>\n";
        echo "    <th>\n";
        echo "      ".loca("INFO_DEPOT_SUPPLY")."<br>".va(loca("INFO_DEPOT_SECONDS"), $load)."<br>\n";
        echo "      <input tabindex='".$c."' type='text' name='c".$c."' size='5' maxlength='2' value='0' />".loca("INFO_DEPOT_HOUR")."<br>\n\n";
        echo "         ".va(loca("INFO_DEPOT_PER_HOUR"), ceil($cons))."    </th>\n";
        echo "  </tr>\n";
        $c ++;
    }

?>
  <tr><th colspan='2'><input type='submit' value='<?=loca("INFO_DEPOT_SUBMIT");?>'></th>
</table>

</form>
<?php
    }
    else if ( $gid == GID_B_MISS_SILO && $aktplanet["b".GID_B_MISS_SILO] > 0)        // Missile Silo
    {
        $rak_space = $aktplanet["b".GID_B_MISS_SILO] * 10;
        if ( key_exists ( 'aktion', $_POST) )
        {
            $amount1 = min ( $aktplanet['d'.GID_D_ABM], intval ( $_POST['ab502'] ) );
            if ( $amount1 > 0) {
                $aktplanet['d'.GID_D_ABM] -= $amount1;
                $res = ShipyardPrice ( GID_D_ABM );
                $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
                $points  = ( $m + $k + $d ) * $amount1;
                AdjustStats ( $aktplanet['owner_id'], $points, 0, 0, '-');
            }

            $amount2 = min ($aktplanet['d'.GID_D_ABM], intval ( $_POST['ab503'] ) );
            if ( $amount2 > 0) {
                $aktplanet['d'.GID_D_IPM] -= $amount2;
                $res = ShipyardPrice ( GID_D_IPM );
                $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
                $points  = ( $m + $k + $d ) * $amount2;
                AdjustStats ( $aktplanet['owner_id'], $points, 0, 0, '-');
            }

            if ( ($amount1 + $amount2) > 0 ) {
                SetPlanetDefense ( $aktplanet['planet_id'], $aktplanet );
                RecalcRanks ();
            }
        }

?>
    </th> 
   </tr> 
</table> 
<?=va(loca("INFO_SILO_INFO"), $rak_space/2, $rak_space);?><br><table border=0> 

<?php
    if ( ($aktplanet['d'.GID_D_ABM] + $aktplanet['d'.GID_D_IPM]) > 0 )  
    {
?>
<form action="index.php?page=infos&session=<?=$session;?>&gid=44"  method=post> 
<tr> 
 <td class=c><?=loca("INFO_SILO_TYPE");?></td><td class=c><?=loca("INFO_SILO_AMOUNT");?></td><td class=c><?=loca("INFO_SILO_DEMOLISH");?></td> 
 <td class=c></td></tr> 
<?php
            if ($aktplanet['d'.GID_D_ABM] > 0) 
            {
?>
<tr><td class=c><?=loca("NAME_502");?></td><td class=c><?=$aktplanet['d'.GID_D_ABM];?></td><td class=c><input type=text name="ab502" size=2 value=""></td><td class=c></td></tr>
<?php
            }
?>
<?php
            if ($aktplanet['d'.GID_D_IPM] > 0) 
            {
?>
<tr><td class=c><?=loca("NAME_503");?></td><td class=c><?=$aktplanet['d'.GID_D_IPM];?></td><td class=c><input type=text name="ab503" size=2 value=""></td><td class=c></td></tr>
<?php
            }
?>
<tr><td class=c colspan=4><input type=submit name=aktion value="<?=loca("INFO_SILO_SUBMIT");?>"></table><p></form>
<?php
        }
    }
    else if ( $gid == GID_B_PHALANX )        // Sensor Phalanx
    {
?>
<tr><th><p><center><table border=1 ><tr><td class='c'><?=loca("INFO_PHALANX_LEVEL");?></td><td class='c'><?=loca("INFO_PHALANX_RADIUS");?></td></tr>
<?php
        $level = $aktplanet['b'.$gid]-3;
        if ($level <= 0) $level = 1;
        for ($i=$level; $i<$aktplanet['b'.$gid]+5; $i++) {
            $radius = $i*$i-1;
            if ($i==$aktplanet['b'.$gid]) echo "<tr><th align=center >&nbsp;<FONT color=FF0000>$i</FONT></th><th align=center >&nbsp;$radius&nbsp;</th></tr>";
            else echo "<tr><th align=center >&nbsp;<FONT color=FFFFFF>$i</FONT></th><th align=center >&nbsp;$radius&nbsp;</th></tr>";
        }
?>
</center></table></tr></th></table> 
<?php
    }
    else if ( $gid == GID_B_JUMP_GATE && $aktplanet["b".GID_B_JUMP_GATE] > 0)        // Jump Gate
    {
        if ( $now >= $aktplanet["gate_until"] ) 
        {
?>
    </th>
   </tr>
</table>
<form action="index.php?page=sprungtor&session=<?=$session;?>" method="post">

  <input type="hidden" name="qm" value="<?=$aktplanet['planet_id'];?>" />
  <table border="1">
    <tr>
      <td><?=loca("GATE_START");?></td>
      <td><a href="index.php?page=galaxy&galaxy=<?=$aktplanet['g'];?>&system=<?=$aktplanet['s'];?>&position=<?=$aktplanet['p'];?>&session=<?=$session;?>" >[<?=$aktplanet['g'];?>:<?=$aktplanet['s'];?>:<?=$aktplanet['p'];?>]</a></td>
    </tr>
    <tr>
      <td><?=loca("GATE_TARGET");?></td>

      <td>
        <select name="zm">
<?php
    $result = EnumPlanets ();
    $rows = dbrows ($result);
    while ($rows--)
    {
        $planet = dbarray ($result);
        if ( $planet['planet_id'] == $aktplanet['planet_id'] ) continue;    // current moon
        if ( $planet["b43"] == 0 ) continue;    // no jump gate
        if ( $planet['type'] != PTYP_MOON || $now < $planet['gate_until'] ) continue;
        echo "             <option value=\"".$planet['planet_id']."\">".$planet['name']." <a href=\"index.php?page=galaxy&galaxy=".$planet['g']."&system=".$planet['s']."&position=".$planet['p']."&session=$session\" >[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a></option>\n";
    }
?>
        </select>
      </td>
    </tr>
  </table>
  <table width="519">

    <tr>
      <td class="c" colspan="2"><?=loca("GATE_HEAD");?></td>
    </tr>
<?php
    foreach ($fleetmap_revnosat as $i=>$id)
    {
        $amount = $aktplanet["f$id"];
        if ($amount != 0)
        {
            echo "    <tr>\n";
            echo "      <th><a href=\"index.php?page=infos&session=$session&gid=$id\">".loca("NAME_$id")."</a> (".va(loca("GATE_AVAIL"), nicenum($amount)).")</th>\n";
            echo "      <th><input tabindex=\"1\" type=\"text\" name=\"c$id\" size=\"7\" maxlength=\"7\" value=\"0\"></th>\n";
            echo "    </tr>\n";
        }
    }
?>
    <tr> 
      <th colspan="2"><input type="submit" value="<?=loca("GATE_JUMP");?>" /></th>
    </tr> 
  </table>
</form>
<?php
        }
        else        // The gate is not ready.
        {
            $delta = $aktplanet["gate_until"] - $now;
?>
    </th>
   </tr>
</table>
<center><font color=#FF0000><?=va(loca("GATE_NOT_READY"), date ('i\m\i\n s\s\e\c', $delta));?></font></center>
<?php
        }
    }

    echo "</table>\n";

    // Building Demolition.
    // The terraformer and moonbase cannot be demolished.
    // A missile silo can only be demolished if there are no missiles on the planet.

    if ( $gid < 200 && $aktplanet['b'.$gid] && !($gid == GID_B_TERRAFORMER || $gid == GID_B_LUNAR_BASE || $gid == GID_B_MISS_SILO) ) {
        echo "<table width=519 >\n";
        echo "<tr><td class=c align=center><a href=\"index.php?page=b_building&session=$session&techid=$gid&modus=destroy&planet=".$aktplanet['planet_id']."\">".va(loca("INFO_DEMOLISH_TITLE"), loca("NAME_$gid"), $aktplanet['b'.$gid])."</a></td></tr>\n";
        $res = BuildPrice ( $gid, $aktplanet['b'.$gid]-1 );
        $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
        echo "<br><tr><th>" . loca("INFO_DEMOLISH_RES");
        if ($m) echo loca("INFO_DEMOLISH_M") . "<b>".nicenum($m)."</b> ";
        if ($k) echo loca("INFO_DEMOLISH_K") . "<b>".nicenum($k)."</b> ";
        if ($d) echo loca("INFO_DEMOLISH_D") . "<b>".nicenum($d)."</b> ";
        $t = BuildDuration ( $gid, $aktplanet['b'.$gid]-1, $aktplanet['b'.GID_B_ROBOTS], $aktplanet['b'.GID_B_NANITES], $speed );
        echo "<tr><th><br>".loca("INFO_DEMOLISH_DURATION")."  ".BuildDurationFormat ( $t )."<br></th></tr></table>\n";
    }

    if ( $gid == GID_B_MISS_SILO && $aktplanet['b'.$gid])    // Missile Silo
    {
        $raknum = $aktplanet['d502'] + $aktplanet['d503'];
        echo "<table width=519 >\n";
        if ( $raknum == 0 ) echo "<tr><td class=c align=center><a href=\"index.php?page=b_building&session=$session&techid=$gid&modus=destroy&planet=".$aktplanet['planet_id']."\">".va(loca("INFO_DEMOLISH_TITLE"), loca("NAME_$gid"), $aktplanet['b'.$gid])."</a></td></tr>\n";
        else echo "<tr><td class=c align=center>".loca("INFO_DEMOLISH_DEFENSE")."</a></td></tr>";
        $res = BuildPrice ( $gid, $aktplanet['b'.$gid]-1 );
        $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
        echo "<br><tr><th>" . loca("INFO_DEMOLISH_RES");
        if ($m) echo loca("INFO_DEMOLISH_M") . "<b>".nicenum($m)."</b> ";
        if ($k) echo loca("INFO_DEMOLISH_K") . "<b>".nicenum($k)."</b> ";
        if ($d) echo loca("INFO_DEMOLISH_D") . "<b>".nicenum($d)."</b> ";
        $t = BuildDuration ( $gid, $aktplanet['b'.$gid]-1, $aktplanet['b'.GID_B_ROBOTS], $aktplanet['b'.GID_B_NANITES], $speed );
        echo "<tr><th><br>".loca("INFO_DEMOLISH_DURATION")."  ".BuildDurationFormat ( $t )."<br></th></tr></table>\n";
    }

}

echo "<br><br><br><br>\n";
EndContent ();

PageFooter ();
ob_end_flush ();
?>