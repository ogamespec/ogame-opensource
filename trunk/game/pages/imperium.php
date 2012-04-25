<?php

// Империя.

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "technames", $GlobalUser['lang'] );
loca_add ( "empire", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( &$aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("imperium", true);

$planettype = intval($_GET['planettype']);

// Загрузить список планет/лун.
$plist = array ();
$num = 0;

if ( $planettype == 1 || $planettype == 3)
{
    $result = EnumPlanets ();
    $rows = dbrows ($result);
    while ($rows--)
    {
        $planet = dbarray ($result);
        if ( $planettype == 1 && $planet['type'] == 0 ) continue;
        if ( $planettype == 3 && $planet['type'] != 0 ) continue;
        $plist[$num] = GetPlanet ($planet['planet_id']);
        $num ++;
    }
}

$unitab = LoadUniverse ( );
$speed = $unitab['speed'];

$buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );
$fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
$defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );
$resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );

?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
<script>t=0;</script>  

<table width="750" border="0" cellpadding="0" cellspacing="1">

<!-- ## 
<!-- ## Tablehead 
<!-- ## -->
        <tr height="20" valign="left">
            <td class="c" colspan="<?=($num+2);?>"><?=loca("EMPIRE_OVERVIEW");?></td>
        </tr>
        
        <tr height="20">
            <th colspan="<?=ceil($num/2);?>"><a href="index.php?page=imperium&no_header=1&session=<?=$session;?>&planettype=1"><?=loca("EMPIRE_PLANETS");?></a></th>
            <th colspan="<?=( ceil($num/2)+(1 - $num%2) );?>"><a href="index.php?page=imperium&no_header=1&session=<?=$session;?>&planettype=3"><?=loca("EMPIRE_MOONS");?></a></th>
            <th>&nbsp;</th>
        </tr>

<!-- ## 
<!-- ## Planetimages 
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
            
            <th width="75"><?=loca("EMPIRE_SUM");?></th>

        </tr>

<!-- ## 
<!-- ## Name 
<!-- ## -->
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_NAME");?></th>
 
<?php
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th width=\"75\" >".$planet['name']."</th>\n";
    }
?>
            <th width="75">&nbsp;</th>

        </tr>

<!-- ## 
<!-- ## Coordinates 
<!-- ## -->
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_COORD");?></th>
 
<?php
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th width=\"75\" ><a href=\"index.php?page=galaxy&galaxy=".$planet['g']."&system=".$planet['s']."&position=6&session=$session\" >[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a></th>\n";
    }
?>

            <th width="75">&nbsp;</th>

        </tr>

<!-- ## 
<!-- ## Fields 
<!-- ## -->
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_FIELDS");?></th>
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
            <th width="75"><?=nicenum($sum_fields);?>&nbsp;<a href='#' onMouseOver="return overlib('<font color=white><?=loca("EMPIRE_AVG");?></font>');" onMouseOut="return nd();">(<?=nicenum($avg_fields);?>)</a>&nbsp;/&nbsp;<?=nicenum($sum_maxfields);?>&nbsp;<a href='#' onMouseOver="return overlib('<font color=white><?=loca("EMPIRE_AVG");?></font>');" onMouseOut="return nd();">(<?=nicenum($avg_maxfields);?>)</a></th>

        </tr>

<!-- ## 
<!-- ## Resources-Head
<!-- ## -->
        <tr height="20">
            <td align="left" class="c" colspan="<?=($num+2);?>"><?=loca("EMPIRE_RES");?></td>
        </tr>

<!-- ## 
<!-- ## Resources (without Energy)
<!-- ## -->
 
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_M");?></th>

<?php
    $total = 0;
    $avg_prod = 0;
    foreach ( $plist as $i=>$planet )
    {
        $res_hourly = prod_metal ($planet['b1'], $planet['mprod']) * $planet['factor'] * $speed + 20*$speed;
        $res = ceil ( $planet['m'] );
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
 
            <th width="75"><?=nicenum($total);?>&nbsp;/&nbsp;<?=nicenum($avg_prod);?></th>
        </tr>

 
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_K");?></th>
 
<?php 
    $total = 0;
    $avg_prod = 0;
    foreach ( $plist as $i=>$planet )
    {
        $res_hourly = prod_crys ($planet['b2'], $planet['kprod']) * $planet['factor'] * $speed + 10*$speed;
        $res = ceil ( $planet['k'] );
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

            <th width="75"><?=nicenum($total);?>&nbsp;/&nbsp;<?=nicenum($avg_prod);?></th>

        </tr>
 
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_D");?></th>

<?php 
    $total = 0;
    $avg_prod = 0;
    foreach ( $plist as $i=>$planet )
    {
        $res_hourly = prod_deut ($planet['b3'], $planet['temp']+40, $planet['dprod']) * $planet['factor'] * $speed - cons_fusion ( $planet['b12'], $planet['fprod'] ) * $speed;
        $res = ceil ( $planet['d'] );
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
  

            <th width="75"><?=nicenum($total);?>&nbsp;/&nbsp;<?=nicenum($avg_prod);?></th>
        </tr>
        

<!-- ## 
<!-- ## Resources-Energy
<!-- ## -->
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_E");?></th>

<?php
    $sum_e = 0;
    $sum_emax = 0;
    foreach ( $plist as $i=>$planet )
    {
        $sum_e += $planet['e'];
        $sum_emax += $planet['emax'];
        echo "            <th width=\"75\" >\n";
        if ($planet['e'] < 0) echo "                <font color=\"red\">\n";
        echo "                    ".nicenum($planet['e'])." \n";
        if ($planet['e'] < 0) echo "                </font>\n";
        echo "                / \n";
        echo "                ".nicenum($planet['emax'])."           </th>\n";
    }
?>

            <th width="75"><?=nicenum($sum_e);?> / <?=nicenum($sum_emax);?> </th>
        </tr>

<!-- ## 
<!-- ## Buildings-Head
<!-- ## -->

        <tr height="20">
            <td align="left" class="c" colspan="<?=($num+2);?>"><?=loca("EMPIRE_BUILDINGS");?></td>
        </tr>
        
<!-- ## 
<!-- ## Buildings
<!-- ## -->     
<?php
    foreach ($buildmap as $i=>$gid)
    {
        $sum = 0;
        foreach ( $plist as $j=>$planet )
        {
            $sum += $planet["b$gid"];
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
                echo "            <th width=\"75\" >\n";
                echo "                <a style=\"cursor:pointer\" \n";
                echo "                   onClick=\"if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=$session&planet=".$planet['planet_id']."&cp=".$planet['planet_id']."\';t=0;',500);}\" \n";
                echo "                   onDblClick=\"clearTimeout(t);document.location.href='index.php?page=imperium&session=$session&planettype=$planettype&no_header=1&modus=add&planet=".$planet['planet_id']."&techid=$gid';t=0;\"\n";
                echo "                   title=\"Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить\">       \n";
                echo "                    <font color=\"red\">\n";
                echo "                        ".$planet["b$gid"]."                    </font>                    \n\n";
                echo "                    </font>\n";
                echo "                </a>    \n";
                echo "            </th>       \n";
            }

            echo "            <th width=\"75\">".nicenum($sum)." <a href='#' onMouseOver=\"return overlib('<font color=white>".loca("EMPIRE_AVG")."</font>');\" onMouseOut=\"return nd();\">(".round($sum/$num,2).")</a></th>\n";
            echo "        </tr>\n";
        }
    }
?>

<!-- ## 
<!-- ## Research-Head
<!-- ## -->
        <tr height="20">
            <td align="left" class="c" colspan="<?=($num+2);?>"><?=loca("EMPIRE_RESEARCH");?></td>
        </tr>

        
<!-- ## 
<!-- ## Researches
<!-- ## -->     
<?php
    foreach ($resmap as $i=>$res)
    {
        if ( $GlobalUser["r$res"] == 0 ) continue;

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
            echo "                        ".$GlobalUser["r$res"]."                      \n";
            echo "                    </font>\n";
            echo "                </a>\n\n";
            echo "            </th>\n\n";
        }

        echo "            <th width=\"75\">".$GlobalUser["r$res"]."</th>\n\n";
        echo "        </tr>\n";
    }

?>

<!-- ## 
<!-- ## Ships-Head
<!-- ## --> 
        <tr height="20">
            <td align="left" class="c" colspan="<?=($num+2);?>"><?=loca("EMPIRE_FLEET");?></td>
        </tr>
        
<!-- ## 
<!-- ## Ships
<!-- ## -->         
<?php
    foreach ($fleetmap as $i=>$fleet)
    {
        $sum = 0;
        foreach ( $plist as $j=>$planet ) $sum += $planet["f$fleet"];
        if ( $sum == 0 ) continue;

        echo "        <tr height=\"20\">\n";
        echo "            <th width=\"75\">\n";
        echo "                <a href=\"index.php?page=infos&session=$session&gid=$fleet&planettype=$planettype\">\n\n";
        echo "                    ".loca("NAME_$fleet")."                </a>\n";
        echo "            </th>\n\n";

        foreach ( $plist as $j=>$planet )
        {
            $amount = $planet["f$fleet"];
            echo "            <th width=\"75\" >\n";
            if ($amount > 0)
            {
                $m = $k = $d = $e = 0;
                ShipyardPrice ( $fleet, &$m, &$k, &$d, &$e );
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

<!-- ## 
<!-- ## Defense-Head
<!-- ## -->     
        <tr height="20">

            <td align="left" class="c" colspan="<?=($num+2);?>"><?=loca("EMPIRE_DEFENSE");?></td>
        </tr>
        
<!-- ## 
<!-- ## Defense
<!-- ## -->             
<?php
    foreach ($defmap as $i=>$def)
    {
        $sum = 0;
        foreach ( $plist as $j=>$planet ) $sum += $planet["d$def"];
        if ( $sum == 0 ) continue;

        echo "        <tr height=\"20\">\n";
        echo "            <th width=\"75\">\n";
        echo "                <a href=\"index.php?page=infos&session=$session&gid=$def&planettype=$planettype\">\n\n";
        echo "                    ".loca("NAME_$def")."                </a>\n";
        echo "            </th>\n\n";

        foreach ( $plist as $j=>$planet )
        {
            $amount = $planet["d$def"];
            echo "            <th width=\"75\" >\n";
            if ($amount > 0)
            {
                $m = $k = $d = $e = 0;
                ShipyardPrice ( $def, &$m, &$k, &$d, &$e );
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

<!-- ## 
<!-- ## Footer
<!-- ## -->     

</table>
<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ("", "", false, 0);
ob_end_flush ();
?>