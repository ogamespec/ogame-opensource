<?php

/** @var array $GlobalUser */
/** @var array $GlobalUni */

// Empire.

// Processing parameters.
if ( key_exists ('modus', $_GET) && !$GlobalUser['vacation'] )
{
    if ( $_GET['modus'] === 'add' ) BuildEnque ( $GlobalUser, intval ($_GET['planet']), intval ($_GET['techid']), 0 );
    else if ( $_GET['modus'] === 'destroy' ) BuildEnque ( $GlobalUser, intval ($_GET['planet']), intval ($_GET['techid']), 1 );
    else if ( $_GET['modus'] === 'remove' ) BuildDeque ( $GlobalUser, intval ($_GET['planet']), intval ($_GET['listid']) );
}

$prem = PremiumStatus ($GlobalUser);
if (!$prem['commander']) {
    MyGoto ("overview");
}

$planettype = intval($_GET['planettype']);

if ( $GlobalUni['moons'] ) {
    if ( $planettype != 1 && $planettype != 3) $planettype = 1;
}
else {
    if ( $planettype != 1 ) $planettype = 1;
}

// Load a list of planets/moons.
$plist = array ();
$moons = $num = 0;

if ( $planettype == 1 || $planettype == 3)
{
    $result = EnumPlanets ();
    $rows = dbrows ($result);
    while ($rows--)
    {
        $planet = dbarray ($result);
        if ($planet['type'] == PTYP_MOON ) $moons++;
        if ( $planettype == 1 && $planet['type'] == PTYP_MOON ) continue;
        if ( $planettype == 3 && $planet['type'] != PTYP_MOON ) continue;
        $plist[$num] = GetPlanet ($planet['planet_id']);
        ProdResources ( $plist[$num], $plist[$num]['lastpeek'], $now );
        $num ++;
    }
}

$speed = $GlobalUni['speed'];

?>

<script>t=0;</script>  

<table width="750" border="0" cellpadding="0" cellspacing="1">

<!-- ## -->
<!-- ## Tablehead -->
<!-- ## -->
        <tr height="20" valign="left">
            <td class="c" colspan="<?php echo ($num+2);?>"><?php echo loca("EMPIRE_OVERVIEW");?></td>
        </tr>

<?php
    if ( $moons && $GlobalUni['moons'] ) {
?>        
        <tr height="20">
            <th colspan="<?php echo ceil($num/2);?>"><a href="index.php?page=imperium&no_header=1&session=<?php echo $session;?>&planettype=1"><?php echo loca("EMPIRE_PLANETS");?></a></th>
            <th colspan="<?php echo ( ceil($num/2)+(1 - $num%2) );?>"><a href="index.php?page=imperium&no_header=1&session=<?php echo $session;?>&planettype=3"><?php echo loca("EMPIRE_MOONS");?></a></th>
            <th>&nbsp;</th>
        </tr>
<?php
    }
?>

<!-- ## -->
<!-- ## Planetimages -->
<!-- ## -->
        <tr height="75">        
            <th width="75"></th>            

<?php
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th style=\"padding: 20px;\">  \n";
        echo "                    <a href=\"index.php?page=overview&session=$session&cp=".$planet['planet_id']."\">\n";
        echo "                        <img src=\"".GetPlanetImage(UserSkin(), $planet)."\" width=\"75\" height=\"71\" border=\"0\">\n";
        echo "                    </a>\n";
        echo "            </th>   \n";
    }
?> 
            
            <th width="75"><?php echo loca("EMPIRE_SUM");?></th>

        </tr>

<!-- ## -->
<!-- ## Name -->
<!-- ## -->
        <tr height="20">
            <th width="75"><?php echo loca("EMPIRE_NAME");?></th>
 
<?php
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th width=\"75\" >".$planet['name']."</th>\n";
    }
?>
            <th width="75">&nbsp;</th>

        </tr>

<!-- ## -->
<!-- ## Coordinates -->
<!-- ## -->
        <tr height="20">
            <th width="75"><?php echo loca("EMPIRE_COORD");?></th>
 
<?php
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th width=\"75\" ><a href=\"index.php?page=galaxy&galaxy=".$planet['g']."&system=".$planet['s']."&position=6&session=$session\" >[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a></th>\n";
    }
?>

            <th width="75">&nbsp;</th>

        </tr>

<!-- ## -->
<!-- ## Fields -->
<!-- ## -->
        <tr height="20">
            <th width="75"><?php echo loca("EMPIRE_FIELDS");?></th>
<?php
    $sum_fields = $sum_maxfields = 0;
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th width=\"75\" >".$planet['fields']."/".$planet['maxfields']."</th>\n";
        $sum_fields += $planet['fields'];
        $sum_maxfields += $planet['maxfields'];
    }

    $avg_fields = $num ? ceil ( $sum_fields / $num ) : 0;
    $avg_maxfields = $num ? ceil ( $sum_maxfields / $num ) : 0;
?>
            <th width="75"><?php echo nicenum($sum_fields);?>&nbsp;<a href='#' onMouseOver="return overlib('<font color=white><?php echo loca("EMPIRE_AVG");?></font>');" onMouseOut="return nd();">(<?php echo nicenum($avg_fields);?>)</a>&nbsp;/&nbsp;<?php echo nicenum($sum_maxfields);?>&nbsp;<a href='#' onMouseOver="return overlib('<font color=white><?php echo loca("EMPIRE_AVG");?></font>');" onMouseOut="return nd();">(<?php echo nicenum($avg_maxfields);?>)</a></th>

        </tr>

<!-- ## -->
<!-- ## Resources-Head -->
<!-- ## -->
        <tr height="20">
            <td align="left" class="c" colspan="<?php echo ($num+2);?>"><?php echo loca("EMPIRE_RES");?></td>
        </tr>

<!-- ## -->
<!-- ## Resources (without Energy) -->
<!-- ## -->
 
        <tr height="20">
            <th width="75"><?php echo loca("NAME_".GID_RC_METAL);?></th>

<?php
    $total = 0;
    $avg_prod = 0;
    foreach ( $plist as $i=>$planet )
    {
        $res_hourly = prod_metal ($planet[GID_B_METAL_MINE], $planet['mprod']) * $planet['factor'] * $speed + 20*$speed;
        $res = floor ( $planet[GID_RC_METAL] );
        $total += $res;
        $avg_prod += $res_hourly;
        echo "             <th width=\"75\" >\n";
        echo "                <a href=\"index.php?page=resources&session=$session&cp=".$planet['planet_id']."&planettype=$planettype\">\n";
        echo "                        ".nicenum($res)." \n";
        echo "                </a>\n";
        echo "                / \n";
        echo "                ".nicenum($res_hourly)."           </th>\n";
    }
    $avg_prod = $num ? ceil ($avg_prod / $num) : 0;
?>
 
            <th width="75"><?php echo nicenum($total);?>&nbsp;/&nbsp;<?php echo nicenum($avg_prod);?></th>
        </tr>

 
        <tr height="20">
            <th width="75"><?php echo loca("NAME_".GID_RC_CRYSTAL);?></th>
 
<?php 
    $total = 0;
    $avg_prod = 0;
    foreach ( $plist as $i=>$planet )
    {
        $res_hourly = prod_crys ($planet[GID_B_CRYS_MINE], $planet['kprod']) * $planet['factor'] * $speed + 10*$speed;
        $res = floor ( $planet[GID_RC_CRYSTAL] );
        $total += $res;
        $avg_prod += $res_hourly;
        echo "             <th width=\"75\" >\n";
        echo "                <a href=\"index.php?page=resources&session=$session&cp=".$planet['planet_id']."&planettype=$planettype\">\n";
        echo "                        ".nicenum($res)." \n";
        echo "                </a>\n";
        echo "                / \n";
        echo "                ".nicenum($res_hourly)."           </th>\n";
    }
    $avg_prod = $num ? ceil ($avg_prod / $num) : 0;
?>

            <th width="75"><?php echo nicenum($total);?>&nbsp;/&nbsp;<?php echo nicenum($avg_prod);?></th>

        </tr>
 
        <tr height="20">
            <th width="75"><?php echo loca("NAME_".GID_RC_DEUTERIUM);?></th>

<?php 
    $total = 0;
    $avg_prod = 0;
    foreach ( $plist as $i=>$planet )
    {
        $res_hourly = prod_deut ($planet[GID_B_DEUT_SYNTH], $planet['temp']+40, $planet['dprod']) * $planet['factor'] * $speed - cons_fusion ( $planet[GID_B_FUSION], $planet['fprod'] ) * $speed;
        $res = floor ( $planet[GID_RC_DEUTERIUM] );
        $total += $res;
        $avg_prod += $res_hourly;
        echo "             <th width=\"75\" >\n";
        echo "                <a href=\"index.php?page=resources&session=$session&cp=".$planet['planet_id']."&planettype=$planettype\">\n";
        echo "                        ".nicenum($res)." \n";
        echo "                </a>\n";
        echo "                / \n";
        echo "                ".nicenum($res_hourly)."           </th>\n";
    }
    $avg_prod = $num ? ceil ($avg_prod / $num) : 0;
?>
  

            <th width="75"><?php echo nicenum($total);?>&nbsp;/&nbsp;<?php echo nicenum($avg_prod);?></th>
        </tr>
        

<!-- ## -->
<!-- ## Resources-Energy -->
<!-- ## -->
        <tr height="20">
            <th width="75"><?php echo loca("NAME_".GID_RC_ENERGY);?></th>

<?php
    $sum_e = 0;
    $sum_emax = 0;
    foreach ( $plist as $i=>$planet )
    {
        $sum_e += $planet['e'];
        $sum_emax += $planet[GID_RC_ENERGY];
        echo "            <th width=\"75\" >\n";
        if ($planet['e'] < 0) echo "                <font color=\"red\">\n";
        echo "                    ".nicenum($planet['e'])." \n";
        if ($planet['e'] < 0) echo "                </font>\n";
        echo "                / \n";
        echo "                ".nicenum($planet[GID_RC_ENERGY])."           </th>\n";
    }
?>

            <th width="75"><?php echo nicenum($sum_e);?> / <?php echo nicenum($sum_emax);?> </th>
        </tr>

<!-- ## -->
<!-- ## Buildings-Head -->
<!-- ## -->

        <tr height="20">
            <td align="left" class="c" colspan="<?php echo ($num+2);?>"><?php echo loca("EMPIRE_BUILDINGS");?></td>
        </tr>
        
<!-- ## -->
<!-- ## Buildings -->
<!-- ## -->     
<?php
    foreach ($buildmap as $i=>$gid)
    {
        $sum = 0;
        foreach ( $plist as $j=>$planet )
        {
            $sum += $planet[$gid];
        }
        if ( $sum > 0)
        {
            echo "        <tr height=\"20\">\n";
            echo "            <th width=\"75\">\n";
            echo "                <a href=\"index.php?page=infos&session=$session&gid=$gid&planettype=$planettype\">\n";
            echo "                    ".loca("NAME_$gid")."                </a>\n";
            echo "            </th>           \n";

            foreach ( $plist as $j=>$planet )
            {

                // Load the planet's build queue.
                $bqueue = array ();
                $result = GetBuildQueue ( $planet['planet_id'] );
                while ( $row = dbarray ($result) ) {
                    $bqueue[] = $row;
                }

                echo "            <th width=\"75\" >\n";

                if ( $planet[$gid] > 0 ) {
                    echo "                <a style=\"cursor:pointer\" \n";
                    echo "                   onClick=\"if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=$session&planet=".$planet['planet_id']."&cp=".$planet['planet_id']."\';t=0;',500);}\" \n";
                    echo "                   onDblClick=\"clearTimeout(t);document.location.href='index.php?page=imperium&session=$session&planettype=$planettype&no_header=1&modus=add&planet=".$planet['planet_id']."&techid=$gid';t=0;\"\n";
                    echo "                   title=\"".loca("EMPIRE_ACTION")."\">       \n";

                    if ( CanBuild ($GlobalUser, $planet, $gid, $planet[$gid]+1, 0) === "" ) {
                        echo "                    <font color=\"lime\">\n";
                    }
                    else {
                        echo "                    <font color=\"red\">\n";
                    }

                    echo "                        ".$planet[$gid]."                    </font>                    \n\n";
                    echo "                    </font>\n";
                    echo "                </a>    \n";

                    // Build queue.
                    foreach ( $bqueue as $i=>$row ) {
                        if ( $row['tech_id'] != $gid || $row['planet_id'] != $planet['planet_id'] ) continue;
                        if ( $i == 0 ) {
                            echo "                    <a href=\"index.php?page=imperium&session=$session&planettype=$planettype&no_header=1&planet=".$planet['planet_id']."&modus=remove&listid=".$row['list_id']."\">\n";
                            echo "                        <font color=\"magenta\">".$row['level']."</font>\n";
                            echo "                    </a>\n\n";
                        }
                        else {
                            echo "                    <font color=\"sandybrown\">(<a href=\"index.php?page=imperium&session=$session&planettype=$planettype&no_header=1&planet=".$planet['planet_id']."&modus=remove&listid=".$row['list_id']."\"><font color=\"sandybrown\">".$row['level']."</font></a>)</font>\n\n";
                        }
                    }
                }
                else echo "<font color=\"white\">-</font>\n";

                echo "            </th>       \n";
            }

            echo "            <th width=\"75\">".nicenum($sum)." <a href='#' onMouseOver=\"return overlib('<font color=white>".loca("EMPIRE_AVG")."</font>');\" onMouseOut=\"return nd();\">(".round($sum/$num,2).")</a></th>\n";
            echo "        </tr>\n";
        }
    }
?>

<!-- ## -->
<!-- ## Research-Head -->
<!-- ## -->
        <tr height="20">
            <td align="left" class="c" colspan="<?php echo ($num+2);?>"><?php echo loca("EMPIRE_RESEARCH");?></td>
        </tr>

        
<!-- ## -->
<!-- ## Researches -->
<!-- ## -->     
<?php
    foreach ($resmap as $i=>$res)
    {
        if ( $GlobalUser[$res] == 0 ) continue;

        echo "        <tr height=\"20\">\n";
        echo "            <th width=\"75\">\n";
        echo "                <a href=\"index.php?page=infos&session=$session&gid=$res&planettype=$planettype\">\n";
        echo "                    ".loca("NAME_$res")."             </a>\n";
        echo "            </th>\n\n\n";

        foreach ( $plist as $j=>$planet )
        {
            echo "            <th width=\"75\" >\n\n";
            echo "                <a href=\"index.php?page=buildings&session=$session&cp=".$planet['planet_id']."&mode=Forschung&planettype=$planettype\">\n";
            echo "                    <font color =\"lime\">\n\n";
            echo "                        ".$GlobalUser[$res]."                      \n";
            echo "                    </font>\n";
            echo "                </a>\n\n";
            echo "            </th>\n\n";
        }

        echo "            <th width=\"75\">".$GlobalUser[$res]."</th>\n\n";
        echo "        </tr>\n";
    }

?>

<!-- ## -->
<!-- ## Ships-Head -->
<!-- ## --> 
        <tr height="20">
            <td align="left" class="c" colspan="<?php echo ($num+2);?>"><?php echo loca("EMPIRE_FLEET");?></td>
        </tr>
        
<!-- ## -->
<!-- ## Ships -->
<!-- ## -->         
<?php
    foreach ($fleetmap as $i=>$fleet)
    {
        $sum = 0;
        foreach ( $plist as $j=>$planet ) $sum += $planet[$fleet];
        if ( $sum == 0 ) continue;

        echo "        <tr height=\"20\">\n";
        echo "            <th width=\"75\">\n";
        echo "                <a href=\"index.php?page=infos&session=$session&gid=$fleet&planettype=$planettype\">\n\n";
        echo "                    ".loca("NAME_$fleet")."                </a>\n";
        echo "            </th>\n\n";

        foreach ( $plist as $j=>$planet )
        {
            $amount = $planet[$fleet];
            echo "            <th width=\"75\" >\n";
            if ($amount > 0)
            {
                $cost = ShipyardPrice ( $fleet );
                $m = $cost[GID_RC_METAL];
                $k = $cost[GID_RC_CRYSTAL];
                $d = $cost[GID_RC_DEUTERIUM];
                $e = $cost[GID_RC_ENERGY];
                $meet = IsEnoughResources ( $planet, $m, $k, $d, $e );
                $color = $meet ? "lime" : "red";

                echo "                <a href=\"index.php?page=buildings&session=$session&cp=".$planet['planet_id']."&mode=Flotte&planettype=$planettype\">\n";
                echo "                    <font color =\"$color\">\n";
                echo "                        ".nicenum($amount)."                  </font>\n";
                echo "                </a>    \n";
            }
            else echo "                <font color=\"white\">-</font>\n";
        }

        echo "            <th width=\"75\">".nicenum($sum)."</th>\n\n";
        echo "            </th>\n\n";
        echo "       </tr>\n";
    }

?>

<!-- ## -->
<!-- ## Defense-Head -->
<!-- ## -->     
        <tr height="20">

            <td align="left" class="c" colspan="<?php echo ($num+2);?>"><?php echo loca("EMPIRE_DEFENSE");?></td>
        </tr>
        
<!-- ## -->
<!-- ## Defense -->
<!-- ## -->             
<?php
    foreach ($defmap as $i=>$def)
    {
        $sum = 0;
        foreach ( $plist as $j=>$planet ) $sum += $planet[$def];
        if ( $sum == 0 ) continue;

        echo "        <tr height=\"20\">\n";
        echo "            <th width=\"75\">\n";
        echo "                <a href=\"index.php?page=infos&session=$session&gid=$def&planettype=$planettype\">\n\n";
        echo "                    ".loca("NAME_$def")."                </a>\n";
        echo "            </th>\n\n";

        foreach ( $plist as $j=>$planet )
        {
            $amount = $planet[$def];
            echo "            <th width=\"75\" >\n";
            if ($amount > 0)
            {
                $cost = ShipyardPrice ( $def );
                $m = $cost[GID_RC_METAL];
                $k = $cost[GID_RC_CRYSTAL];
                $d = $cost[GID_RC_DEUTERIUM];
                $e = $cost[GID_RC_ENERGY];
                $meet = IsEnoughResources ( $planet, $m, $k, $d, $e );
                $color = $meet ? "lime" : "red";

                echo "                <a href=\"index.php?page=buildings&session=$session&cp=".$planet['planet_id']."&mode=Verteidigung&planettype=$planettype\">\n";
                echo "                    <font color =\"$color\">\n";
                echo "                        ".nicenum($amount)."                  </font>\n";
                echo "                </a>    \n";
            }
            else echo "                <font color=\"white\">-</font>\n";
        }

        echo "            <th width=\"75\">".nicenum($sum)."</th>\n\n";
        echo "            </th>\n\n";

        echo "       </tr>\n";
    }

?>

<!-- ## -->
<!-- ## Footer -->
<!-- ## -->     

</table>
<br><br><br><br>
