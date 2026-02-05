<?php

/** @var array $GlobalUser */
/** @var array $GlobalUni */
/** @var array $UnitParam */
/** @var array $aktplanet */
/** @var array $defmap */
/** @var array $rakmap */
/** @var string $PageError */
/** @var string $session */

$defmap_norak = array_diff($defmap, $rakmap);

// Display custom Galaxy objects added by mods.
function ShowCustomObjects (int $p, array $custom_planets) : void {

    // Display custom Galaxy objects added by mods.
    if (count($custom_planets) > 0) {
        $width = 100 * count($custom_planets);
        echo "<th width='$width'>";
        foreach ($custom_planets as $i=>$custom_planet) {
            if ($custom_planet['p'] == $p) {

                $info = array ();
                $res = ModsExecArrRef ('page_galaxy_custom_object', $custom_planet, $info);
                if ($res) $overlib = $info['overlib'];
                else $overlib = "";

                echo "<a style='cursor:pointer' onmouseover='return overlib(\"".$overlib."\", STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -40 );' ";
                echo "onmouseout='return nd();'>";
                echo "<img src='".GetPlanetSmallImage ( UserSkin(), $custom_planet )."' height='30' width='30'/></a>\n";
            }
        }
        echo "</th>\n";
    }
}

function empty_row (int $p, array $custom_planets) : void
{
    echo "<tr><th width=\"30\"><a href=\"#\" >".$p."</a></th><th width=\"30\"></th><th width=\"130\" style='white-space: nowrap;'></th><th width=\"30\" style='white-space: nowrap;'></th><th width=\"30\"></th>";
    ShowCustomObjects ($p, $custom_planets);
    echo "<th width=\"150\"></th><th width=\"80\"></th><th width=\"125\" style='white-space: nowrap;'></th></tr>\n\n";
}

// Missile attack.
if ( method () === "POST" && isset($_POST['aktion']) )
{
    $amount = abs(intval($_POST['anz']));        // Number of missiles
    $type = abs(intval($_POST['pziel']));        // Primary target (0-all)
    $origin = $aktplanet;
    $target = LoadPlanetById (intval($_GET['pdd']));
    if ($target != null) {
        $target_user = LoadUser ($target['owner_id']);
        $dist = abs ($origin['s'] - $target['s']);
        $ipm_radius = max (0, 5 * $GlobalUser[GID_R_IMPULSE_DRIVE] - 1);

        if ( !in_array ($type, $defmap_norak ) ) $type = 0;

        if ( $PageError === "" )    // Check the permitted parameters
        {
            if ($amount == 0) $PageError = loca("GALAXY_RAK_NO_ROCKETS");
            if ($amount > $aktplanet[GID_D_IPM]) $PageError = loca("GALAXY_RAK_NOT_ENOUGH");
            if ($dist > $ipm_radius) $PageError = loca("GALAXY_RAK_WEAK_DRIVE");
        }

        if ( $PageError === "" )        // Check player modes
        {
            if ($GlobalUser['vacation']) $PageError = loca("GALAXY_RAK_VACATION_SELF");
            else if ($target_user['vacation']) $PageError = loca("GALAXY_RAK_VACATION_OTHER");
            else if ($target['owner_id'] == $GlobalUser['player_id']) $PageError = loca("GALAXY_RAK_SELF");
            else if ( IsPlayerNewbie($target_user['player_id']) || IsPlayerStrong($target_user['player_id']) ) $PageError = loca("GALAXY_RAK_NOOB");
        }

        if ( $PageError === "" )
        {
            LaunchRockets ( $origin, $target, 30 + 60 * $dist, $amount, $type );
            $aktplanet = GetUpdatePlanet ( $GlobalUser['aktplanet'], time() );    // get the latest planetary data after the IPM is launched.
            if ($aktplanet == null) {
                Error ("Can't get aktplanet");
            }
            $PageMessage = va ( loca("GALAXY_RAK_LAUNCHED"), $amount );
        }
    }
    else $PageError = loca("GALAXY_RAK_NO_TARGET");
}

// Choose a solar system.
if ( key_exists ('session', $_POST)) $coord_g = intval($_POST['galaxy']);
else if ( key_exists ('galaxy', $_GET)) $coord_g = intval($_GET['galaxy']);
else if ( key_exists ('p1', $_GET)) $coord_g = intval($_GET['p1']);
else $coord_g = $aktplanet['g'];
if ( key_exists ('session', $_POST)) $coord_s = intval($_POST['system']);
else if ( key_exists ('system', $_GET)) $coord_s = intval($_GET['system']);
else if ( key_exists ('p2', $_GET)) $coord_s = intval($_GET['p2']);
else $coord_s = $aktplanet['s'];
if ( key_exists ('session', $_POST)) $coord_p = 0;
else if ( key_exists ('position', $_GET)) $coord_p = intval($_GET['position']);
else if ( key_exists ('p3', $_GET)) $coord_p = intval($_GET['p3']);
else $coord_p = $aktplanet['p'];

if ($coord_g < 1 ) $coord_g = 1;
if ($coord_g > $GlobalUni['galaxies'] ) $coord_g = $GlobalUni['galaxies'];

if ($coord_s < 1 ) $coord_s = 1;
if ($coord_s > $GlobalUni['systems'] ) $coord_s = $GlobalUni['systems'];

if ( isset($_POST['systemLeft']) )
{
    $coord_s--;
    if ( $coord_s < 1 ) $coord_s = 1;
}
else if ( isset($_POST['systemRight']) )
{
    $coord_s++;
    if ( $coord_s > $GlobalUni['systems'] ) $coord_s = $GlobalUni['systems'];
}
else if ( isset($_POST['galaxyLeft']) )
{
    $coord_g--;
    if ( $coord_g < 1 ) $coord_g = 1;
}
else if ( isset($_POST['galaxyRight']) )
{
    $coord_g++;
    if ( $coord_g > $GlobalUni['galaxies'] ) $coord_g = $GlobalUni['galaxies'];
}

$not_enough_deut = ( $aktplanet['g'] != $coord_g || $aktplanet['s'] != $coord_s) && $aktplanet[GID_RC_DEUTERIUM] < GALAXY_DEUTERIUM_CONS;

// Charge GALAXY_DEUTERIUM_CONS deuterium for viewing a non-home system (regular users only)
if ( !$not_enough_deut && $GlobalUser['admin'] == 0 )
{
    if ( $aktplanet['g'] != $coord_g || $aktplanet['s'] != $coord_s )
    {
        $cost = array (GID_RC_DEUTERIUM => GALAXY_DEUTERIUM_CONS);
        AdjustResources ($cost, $aktplanet['planet_id'], '-');
        $aktplanet = GetUpdatePlanet ( $aktplanet['planet_id'], time() );
        if ($aktplanet == null) {
            Error ("Can't get aktplanet");
        }
    }
}

$result = EnumOwnFleetQueue ( $GlobalUser['player_id'] );
$nowfleet = dbrows ($result);
$maxfleet = $GlobalUser[GID_R_COMPUTER] + 1;

$prem = PremiumStatus ($GlobalUser);
if ( $prem['admiral'] ) $maxfleet += 2;

/***** Scripts. *****/

include "galaxy_js.php";

// Not enough deuterium?
// Operators and administrators can view the Galaxy without the cost of deuterium.
if ( $not_enough_deut && $GlobalUser['admin'] == 0 )
{
?>
  <center>
<br />
<br />
<br />
<table width="519">
<tr height="20">
   <td class="c"><span class="error"><?php echo loca("GALAXY_DEUT_ERR");?></span></td>

</tr>
  <tr height="20">
    <th><span class="error"><?php echo loca("GALAXY_DEUT_ERR_TEXT");?></span></th>
  </tr>
</table>

<?php
}
else
{

/***** Solar system selection menu. *****/

echo "  <center>\n<form action=\"index.php?page=galaxy&no_header=1&session=".$_GET['session']."\" method=\"post\" id=\"galaxy_form\">\n";
echo "<input type=\"hidden\" name=\"session\" value=\"".$_GET['session']."\">\n";
echo "<input type=\"hidden\" id=\"auto\" value=\"dr\">\n";
echo "<table border=1 class='header' id='t1'>\n\n";
echo "<tr class='header'>\n";
echo "    <td class='header'><table class='header' id='t2'>\n";
echo "    <tr class='header'><td class=\"c\" colspan=\"3\">".loca("GALAXY_GALAXY")."</td></tr>\n";
echo "    <tr class='header'>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"galaxyLeft\" value=\"<-\" onClick=\"galaxy_submit('galaxyLeft')\"></td>\n";
echo "    <td class=\"l\"><input type=\"text\" name=\"galaxy\" value=\"".$coord_g."\" size=\"5\" maxlength=\"3\" tabindex=\"1\"></td>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"galaxyRight\" value=\"->\" onClick=\"galaxy_submit('galaxyRight')\"></td>\n";
echo "    </tr></table></td>\n\n";
echo "    <td class='header'><table class='header' id='t3'>\n";
echo "    <tr class='header'><td class=\"c\" colspan=\"3\">".loca("GALAXY_SYSTEM")."</td></tr>\n";
echo "    <tr class='header'>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"systemLeft\" value=\"<-\" onClick=\"galaxy_submit('systemLeft')\"></td>\n";
echo "    <td class=\"l\"><input type=\"text\" name=\"system\" value=\"".$coord_s."\" size=\"5\" maxlength=\"3\" tabindex=\"2\"></td>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"systemRight\" value=\"->\" onClick=\"galaxy_submit('systemRight')\"></td>\n";
echo "    </tr></table></td>\n";
echo "</tr>\n\n";
echo "<tr class='header'>\n";
echo "    <td class='header' style=\"background-color:transparent;border:0px;\" colspan=\"2\" align=\"center\"> <input type=\"submit\" value=\"".loca("GALAXY_SHOW")."\"></td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</form>\n";

/***** A form of interplanetary rocket launch *****/

    $system_radius = abs ($aktplanet['s'] - $coord_s);
    $ipm_radius = max (0, 5 * $GlobalUser[GID_R_IMPULSE_DRIVE] - 1);
    $show_ipm_button = ($system_radius <= $ipm_radius) && ($aktplanet[GID_D_IPM] > 0) && ($aktplanet['g'] == $coord_g);

    if ( isset($_GET['mode']) ) {

        $target = LoadPlanetById ( intval($_GET['pdd']) );

?>

   <form action="index.php?page=galaxy&session=<?=$session;?>&p1=<?=$coord_g;?>&p2=<?=$coord_s;?>&p3=<?=$coord_p;?>&zp=<?=intval($_GET['zp']);?>&pdd=<?=intval($_GET['pdd']);?>"  method="POST">   <tr>
   <table border="0">
    <tr>
     <td class="c" colspan="2">
      <?=loca("GALAXY_RAK_COORD");?> <a href="index.php?page=galaxy&no_header=1&session=<?=$session;?>&p1=<?=$target['g'];?>&p2=<?=$target['s'];?>&p3=<?=$target['p'];?>" >[<?=$target['g'];?>:<?=$target['s'];?>:<?=$target['p'];?>]</a>     </td>

    </tr>
    <tr>
     <td class="c">
     <?=va(loca("GALAXY_RAK_AMOUNT"), $aktplanet[GID_D_IPM]);?>:     <input type="text" name="anz" size="2" maxlength="2" /></td>
    <td class="c">
    <?=loca("GALAXY_RAK_TARGET");?>:
     <select name="pziel">
      <option value="0" selected><?=loca("GALAXY_RAK_TARGET_ALL");?></option>
<?php
    foreach ($defmap_norak as $i=>$gid)
    {
        echo "       <option value=\"$gid\">".loca("NAME_$gid")."</option>\n";
    }
?>
           </select>
    </td>
   </tr>
   <tr>
    <td class="c" colspan="2"><input type="submit" name="aktion" value="<?=loca("GALAXY_RAK_ATTACK");?>"></td>
   </tr>

  </table>
 </form>

<?php
    }

/***** Table header *****/

$result_custom = EnumCustomPlanetsGalaxy ($coord_g, $coord_s);
$num_custom = dbrows ($result_custom);
$has_custom = $num_custom != 0 ? 1 : 0;
$custom_planets = array ();
for ($i=0; $i<$num_custom; $i++) {
    $custom_planets[] = dbarray ($result_custom);
}

echo "<table width=\"569\">\n";
echo "<tr><td class=\"c\" colspan=\"".(8+$has_custom)."\">".loca("GALAXY_SYSTEM")." ".$coord_g.":".$coord_s."</td></tr>\n";
echo "<tr>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_COORD")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_PLANET")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_NAME_ACT")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_MOON")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_DF")."</td>\n";
if ($num_custom > 0) {
    echo "<td class=\"c\">".loca("GALAXY_HEAD_OTHER")."</td>\n";
}
echo "<td class=\"c\">".loca("GALAXY_HEAD_PLAYER_STATUS")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_ALLY")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_ACTIONS")."</td>\n";
echo "</tr>\n";

/***** Enumerate the planets *****/

$p = 1;
$tabindex = 3;
$result = EnumPlanetsGalaxy ( $coord_g, $coord_s );
$num = $planets = dbrows ($result);

while ($num--)
{
    $planet = dbarray ($result);
    $user = LoadUser ( $planet['owner_id']);
    $own = $user['player_id'] == $GlobalUser['player_id'];
    for ($p; $p<$planet['p']; $p++) empty_row ($p, $custom_planets);

    $phalanx = CanPhalanx ($aktplanet, $planet);

    // Coord.
    echo "<tr>\n";
    echo "<th width=\"30\"><a href=\"#\"  tabindex=\"".($tabindex++)."\" >".$p."</a></th>\n";

    // Planet
    echo "<th width=\"30\">\n";
    if ( $planet['type'] == PTYP_PLANET )
    {
        echo "<a style=\"cursor:pointer\" onmouseover='return overlib(\"<table width=240>";
        echo "<tr><td class=c colspan=2 >".loca("GALAXY_PLANET")." ".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]</td></tr>";
        echo "<tr><th width=80 ><img src=".GetPlanetSmallImage ( UserSkin(), $planet )." height=75 width=75 /></th>";
        echo "<th align=left >";
        if ($own)
        {
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=4 >".loca("GALAXY_FLEET_DEPLOY")."</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=3 >".loca("GALAXY_FLEET_TRANSPORT")."</a><br />";
        }
        else
        {
            echo "<a href=# onclick=doit(6,".$planet['g'].",".$planet['s'].",".$planet['p'].",1,".$GlobalUser['maxspy'].") >".loca("GALAXY_FLEET_SPY")."</a><br><br />";
            if ($phalanx) echo "<a href=# onclick=fenster(&#039;index.php?page=phalanx&session=".$_GET['session']."&scanid=".$planet['owner_id']."&spid=".$planet['planet_id']."&#039;) >".loca("GALAXY_FLEET_PHALANX")."</a><br />";
            if ( $show_ipm_button ) echo "<a href=index.php?page=galaxy&no_header=1&session=$session&mode=1&p1=".$planet['g']."&p2=".$planet['s']."&p3=".$planet['p']."&pdd=".$planet['planet_id']."&zp=".$planet['owner_id']." >".loca("GALAXY_FLEET_RAK")."</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=1 m>".loca("GALAXY_FLEET_ATTACK")."</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=5 >".loca("GALAXY_FLEET_DEFEND")."</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=3 >".loca("GALAXY_FLEET_TRANSPORT")."</a><br />";
        }
        if ($GlobalUser['admin'] >= 2) echo "<a href=index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id'].">".loca("GALAXY_PLANET_ADMIN")."</a><br />";
        echo "</th></tr></table>\", STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -40 );' onmouseout=\"return nd();\">\n";
        echo "<img src=\"".GetPlanetSmallImage ( UserSkin(), $planet )."\" height=\"30\" width=\"30\"/></a>\n";
    }
    echo "</th>\n";

    $moon = LoadPlanet ( $coord_g, $coord_s, $p, 3 );
    if ( $moon ) $moon_id = $moon['planet_id'];
    else $moon_id = 0;

    // Name (activity)
    $now = time ();
    $ago15 = $now - 15 * 60;
    $ago60 = $now - 60 * 60;
    $akt = "";
    if (!$own)
    {
        $activity = $planet['lastakt'];
        if ($moon_id && $moon['lastakt'] > $planet['lastakt'] ) $activity = $moon['lastakt'];
        if ( $activity > $ago15 ) $akt = "&nbsp;(*)";
        else if ( $activity > $ago60) $akt = "&nbsp;(".floor(($now - $activity)/60)." min)";
    }
    if ( $planet['type'] == PTYP_DEST_PLANET ) $planet_name = loca("PLANET_DESTROYED") . $akt;
    else if ( $planet['type'] == PTYP_ABANDONED ) { $planet_name = loca("PLANET_ABANDONED") . $akt; $phalanx = false; }
    else $planet_name = $planet['name'].$akt;
    if ($phalanx) $planet_name = "<a href='#' onclick=fenster('index.php?page=phalanx&session=$session&scanid=".$planet['owner_id']."&spid=".$planet['planet_id']."',\"Bericht_Phalanx\") title=\"".loca("GALAXY_FLEET_PHALANX")."\">" . $planet_name . "</a>";
    echo "<th width=\"130\" style='white-space: nowrap;'>$planet_name</th>\n";

    // moon
    echo "<th width=\"30\" style='white-space: nowrap;'>\n";
    if ($moon_id)
    {
        if ($moon['type'] == PTYP_MOON)
        {
            echo "<a onmouseout=\"return nd();\" onmouseover=\"return overlib('<table width=240 ><tr>";
            echo "<td class=c colspan=2 >".loca("GALAXY_MOON")." ".$moon['name']." [".$moon['g'].":".$moon['s'].":".$moon['p']."]</td></tr>";
            echo "<tr><th width=80 ><img src=".GetPlanetSmallImage ( UserSkin(), $moon )." height=75 width=75 alt=\'".va(loca("GALAXY_MOON_TITLE_SIZE"), $moon['diameter'])."\'/></th>";
            echo "<th><table width=120 ><tr><td colspan=2 class=c >".loca("GALAXY_MOON_PROPS")."</td></tr>";
            echo "<tr><th>".loca("GALAXY_MOON_SIZE")."</td><th>".nicenum($moon['diameter'])."</td></tr>";
            echo "<tr><th>".loca("GALAXY_MOON_TEMP")."</td><th>".$moon['temp']."</td></tr>";
            echo "<tr><td colspan=2 class=c >".loca("GALAXY_MOON_ACTIONS")."</td></tr>";
            echo "<tr><th align=left colspan=2 >";
            if ($own)
            {
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=3 >".loca("GALAXY_FLEET_TRANSPORT")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=4 >".loca("GALAXY_FLEET_DEPLOY")."</a><br />";
            }
            else
            {
                //echo "<font color=#808080 >Шпионаж</font><br><br />";
                echo "<a href=# onclick=doit(6,".$moon['g'].",".$moon['s'].",".$moon['p'].",3,".$GlobalUser['maxspy'].") >".loca("GALAXY_FLEET_SPY")."</a><br><br />";
                if ( $show_ipm_button ) echo "<a href=index.php?page=galaxy&no_header=1&session=$session&mode=1&p1=".$moon['g']."&p2=".$moon['s']."&p3=".$moon['p']."&pdd=".$moon['planet_id']."&zp=".$moon['owner_id']." >".loca("GALAXY_FLEET_RAK")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=3 >".loca("GALAXY_FLEET_TRANSPORT")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=1 >".loca("GALAXY_FLEET_ATTACK")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=5 >".loca("GALAXY_FLEET_DEFEND")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=9 >".loca("GALAXY_FLEET_DESTROY")."</a><br />";
            }
            if ($GlobalUser['admin'] >= 2) echo "<a href=index.php?page=admin&session=$session&mode=Planets&cp=".$moon['planet_id'].">".loca("GALAXY_PLANET_ADMIN")."</a><br />";
            echo "</th></tr></table></tr></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -110 );\" style=\"cursor: pointer;\" \n";
            echo " href='#' onclick='doit(6, ".$moon['g'].", ".$moon['s'].", ".$moon['p'].", 3, ".$GlobalUser['maxspy'].")' \n";
            echo ">\n";
            echo "<img width=\"22\" height=\"22\" alt=\"".va(loca("GALAXY_MOON_TITLE_SIZE"), $moon['diameter'])."\" src=\"".GetPlanetSmallImage ( UserSkin(), $moon )."\"/></a>\n";
        }
        else echo "<div style=\"border: 2pt solid #FF0000;\"><img src=\"".GetPlanetSmallImage ( UserSkin(), $moon )."\" alt=\"".va(loca("GALAXY_MOON_TITLE_SIZE"), $moon['diameter'])."\" height=\"22\" width=\"22\" onmouseover=\"return overlib('<font color=white><b>".loca("MOON_DESTROYED")."</b></font>', WIDTH, 75);\" onmouseout=\"return nd();\"/></div>\n";
    }
    echo "</th>\n";

    // debris field (do not show DF < GALAXY_PHANTOM_DEBRIS units)
    echo "<th width=\"30\">";
    $debris = LoadPlanet ( $coord_g, $coord_s, $p, 2 );
    if ( $debris )
    {
        $harvesters = ceil ( ($debris[GID_RC_METAL] + $debris[GID_RC_CRYSTAL]) / $UnitParam[GID_F_RECYCLER][3]);
        if ( ($debris[GID_RC_METAL] + $debris[GID_RC_CRYSTAL]) >= GALAXY_PHANTOM_DEBRIS )
        {
?>
    <a style="cursor:pointer"
       onmouseover="return overlib('<table width=240 ><tr><td class=c colspan=2 ></td></tr><tr><th width=80 ><img src=<?=UserSkin();?>planeten/debris.jpg height=75 width=75 alt=T /></th><th><table><tr><td class=c colspan=2><?=loca("GALAXY_DF_RESOURCES");?></td></tr><tr><th><?=loca("GALAXY_DF_M");?></th><th><?=nicenum($debris[GID_RC_METAL]);?></th></tr><tr><th><?=loca("GALAXY_DF_K");?></th><th><?=nicenum($debris[GID_RC_CRYSTAL]);?></th></tr><tr><td class=c colspan=2><?=loca("GALAXY_DF_ACTIONS");?></tr><tr><th colspan=2 align=left ><a href=# onclick=doit(8,<?=$coord_g;?>,<?=$coord_s;?>,<?=$p;?>,2,<?=$harvesters;?>) ><?=loca("GALAXY_FLEET_RECYCLE");?></a></tr></table></th></tr></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -40 );" onmouseout="return nd();"
href='#' onclick='doit(8, <?=$coord_g;?>, <?=$coord_s;?>, <?=$p;?>, 2, <?=$harvesters;?>)'
>
<img src="<?=UserSkin();?>planeten/debris.jpg" height="22" width="22" /></a>
<?php
        }
    }
    echo "</th>\n";

    // Display custom Galaxy objects added by mods.
    if ($num_custom > 0) {
        ShowCustomObjects ($p, $custom_planets);
    }

    // player (status)
    // Newbie or Strong or Regular
    // Priorities of Regular: Vacation Mode -> Blocked -> Long inactive -> Inactive -> No Status
    $stat = "";
    echo "<th width=\"150\">\n";
    if ( !($planet['type'] == PTYP_DEST_PLANET || $planet['type'] == PTYP_ABANDONED) )
    {
        echo "<a style=\"cursor:pointer\" onmouseover=\"return overlib('<table width=240 >";
        echo "<tr><td class=c >".va(loca("GALAXY_USER_TITLE"), $user['oname'], $user['place1'])."</td></tr>";
        echo "<th><table>";
        if (!$own)
        {
            echo "<tr><td><a href=index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$planet['owner_id']." >".loca("GALAXY_USER_MESSAGE")."</a></td></tr>";
            echo "<tr><td><a href=index.php?page=buddy&session=".$_GET['session']."&action=7&buddy_id=".$planet['owner_id']." >".loca("GALAXY_USER_BUDDY")."</a></td></tr>";
        }
        echo "<tr><td><a href=index.php?page=statistics&session=".$_GET['session']."&start=".(floor($user['place1']/100)*100+1)." >".loca("GALAXY_USER_STATS")."</a></td></tr>";
        if ($GlobalUser['admin'] >= 2) echo "<tr><td><a href=index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id'].">".loca("GALAXY_USER_ADMIN")."</a></td></tr>";
        echo "</table>";
        echo "</th></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETY, -40 );\" onmouseout=\"return nd();\">\n";
        if ( IsPlayerNewbie ( $user['player_id'] ) )
        {
            $pstat = "noob"; $stat = "<span class='noob'>".loca("GALAXY_LEGEND_NOOB")."</span>";
        }
        else if ( IsPlayerStrong ( $user['player_id'] ) )
        {
            $pstat = "strong"; $stat = "<span class='strong'>".loca("GALAXY_LEGEND_STRONG")."</span>";
        }
        else
        {
            $week = time() - 604800;
            $week4 = time() - 604800*4;
            $pstat = "normal";
            if ( $user['lastclick'] <= $week ) { $stat .= "<span class='inactive'>".loca("GALAXY_LEGEND_INACTIVE7")."</span>"; $pstat = "inactive"; }
            if ( $user['banned'] ) { if(mb_strlen($stat, "UTF-8")) $stat .= " "; $stat .= "<a href='index.php?page=pranger&session=".$_GET['session']."'><span class='banned'>".loca("GALAXY_LEGEND_BANNED")."</span></a>"; $pstat = "banned"; }
            if ( $user['lastclick'] <= $week4 ) { if(mb_strlen($stat, "UTF-8")) $stat .= " "; $stat .= "<span class='longinactive'>".loca("GALAXY_LEGEND_INACTIVE28")."</span>";  if($pstat !== "banned") $pstat = "longinactive"; }
            if ( $user['vacation'] ) { if(mb_strlen($stat, "UTF-8")) $stat .= " "; $stat .= "<span class='vacation'>".loca("GALAXY_LEGEND_VACATION")."</span>";  $pstat = "vacation"; }
        }
        echo "<span class=\"$pstat\">".$user['oname']."</span></a>\n";
        if ($pstat !== "normal") echo "($stat)\n";
    }
    echo "</th>\n";

    // Alliance
    if ($user['ally_id'] && !($planet['type'] == PTYP_DEST_PLANET || $planet['type'] == PTYP_ABANDONED) )
    {
        $ally = LoadAlly ( $user['ally_id']);
        $allytext = "<a style=\"cursor:pointer\"\n";
        $allytext .= "         onmouseover=\"return overlib('<table width=240 ><tr><td class=c >".va(loca("GALAXY_ALLY_TITLE"), $ally['tag'], $ally['place1'], CountAllyMembers($user['ally_id']))."</td></tr><th><table>";
        $allytext .= "<tr><td><a href=ainfo.php?allyid=".$ally['ally_id']." target=_ally>".loca("GALAXY_ALLY_PAGE")."</a></td></tr>";
        if ($GlobalUser['ally_id'] != $user['ally_id']) {
            $allytext .= "<tr><td><a href=index.php?page=bewerben&session=$session&allyid=".$ally['ally_id']." >".loca("GALAXY_ALLY_APPLY")."</a></td></tr>";
        }
        $allytext .= "<tr><td><a href=index.php?page=statistics&session=$session&start=".(floor($ally['place1']/100)*100+1)."&who=ally >".loca("GALAXY_ALLY_STATS")."</a></td></tr>";
        if ($ally['homepage'] !== "") {
            $allytext .= "<tr><td><a href=redir.php?url=".$ally['homepage']." target=_blank >".loca("GALAXY_ALLY_HOMEPAGE")."</td></tr>";
        }
        $allytext .= "</table></th></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETY, -50 );\" onmouseout=\"return nd();\">\n";
        $allytext .= "   ".$ally['tag']." </a>";
    }
    else $allytext = "";
    echo "<th width=\"80\">$allytext</th>\n";

    // Actions
    echo "<th width=\"125\" style='white-space: nowrap;'>\n";
    if ( !($planet['type'] == PTYP_DEST_PLANET || $planet['type'] == PTYP_ABANDONED) && !$own)
    {
        if ($prem['commander'] && $GlobalUser['flags'] & USER_FLAG_SHOW_VIEW_REPORT_BUTTON) {

            // If there is a report for both planet and moon, 2 buttons are shown.

            $planet_spy_report = GetSharedSpyReport ($planet['planet_id'], $GlobalUser['player_id'], $GlobalUser['ally_id']);
            $moon_spy_report = 0;
            if ($moon_id != 0) {
                $moon_spy_report = GetSharedSpyReport ($moon_id, $GlobalUser['player_id'], $GlobalUser['ally_id']);
            }

            if ($planet_spy_report != 0) {
                echo "<a href=\"#\" onclick=\"fenster('index.php?page=bericht&session=". $_GET['session'] ."&bericht=". $planet_spy_report ."', 'Bericht_Spionage');\" ><img src=\"".UserSkin()."img/s.gif\" border=\"0\" alt=\"".loca("SPY_REPORT")."\" title=\"".loca("SPY_REPORT")."\" /></a>\n";
            }

            if ($moon_spy_report != 0) {
                echo "<a href=\"#\" onclick=\"fenster('index.php?page=bericht&session=". $_GET['session'] ."&bericht=". $moon_spy_report ."', 'Bericht_Spionage');\" ><img src=\"".UserSkin()."img/s.gif\" border=\"0\" alt=\"".loca("SPY_REPORT")."\" title=\"".loca("SPY_REPORT")."\" /></a>\n";
            }

        }
        if ($GlobalUser['flags'] & USER_FLAG_SHOW_ESPIONAGE_BUTTON) {
            echo "<a style=\"cursor:pointer\" onclick=\"javascript:doit(6, ".$planet['g'].",".$planet['s'].",".$planet['p'].", 1, ".$GlobalUser['maxspy'].");\"><img src=\"".UserSkin()."img/e.gif\" border=\"0\" alt=\"".loca("GALAXY_FLEET_SPY")."\" title=\"".loca("GALAXY_FLEET_SPY")."\" /></a>\n";
        }
        if ($GlobalUser['flags'] & USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON) {
            echo "<a href=\"index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$planet['owner_id']."\"><img src=\"".UserSkin()."img/m.gif\" border=\"0\" alt=\"".loca("GALAXY_USER_MESSAGE")."\" title=\"".loca("GALAXY_USER_MESSAGE")."\" /></a>\n";
        }
        if ($GlobalUser['flags'] & USER_FLAG_SHOW_BUDDY_BUTTON) {
            echo "<a href=\"index.php?page=buddy&session=".$_GET['session']."&action=7&buddy_id=".$planet['owner_id']."\"><img src=\"".UserSkin()."img/b.gif\" border=\"0\" alt=\"".loca("GALAXY_USER_BUDDY")."\" title=\"".loca("GALAXY_USER_BUDDY")."\" /></a>\n";
        }
        if ( $show_ipm_button && $GlobalUser['flags'] & USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON ) {
            echo "<a href=\"index.php?page=galaxy&session=$session&mode=1&p1=".$planet['g']."&p2=".$planet['s']."&p3=".$planet['p']."&pdd=".$planet['planet_id']."&zp=".$planet['owner_id']."\"><img src=\"".UserSkin()."img/r.gif\" border=\"0\" alt=\"".loca("GALAXY_FLEET_RAK")."\" title=\"".loca("GALAXY_FLEET_RAK")."\" /></a>";
        }
    }
    echo "</th>\n";

    echo "</tr>\n\n";
    $p++;
}
for ($p; $p<=15; $p++) empty_row ($p, $custom_planets);

/***** Bottom of table *****/
echo "<tr><th style='height:32px;'>16</th><th colspan='".(7+$has_custom)."'><a href ='index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$coord_g."&system=".$coord_s."&planet=16&planettype=1&target_mission=15'>".loca("FAR_SPACE")."</a></th></tr>\n\n";

echo "<tr><td class=\"c\" colspan=\"".(6+$has_custom)."\">(".va(loca("GALAXY_INFO_POPULATED"), $planets).")</td>\n";
echo "<td class=\"c\" colspan=\"2\"><a href='#' onmouseover='return overlib(\"<table><tr><td class=c colspan=2>".loca("GALAXY_LEGEND")."</td></tr>";
echo "<tr><td width=125>".loca("GALAXY_LEGEND_STRONG_LONG")."</td><td><span class=strong>".loca("GALAXY_LEGEND_STRONG")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_NOOB_LONG")."</td><td><span class=noob>".loca("GALAXY_LEGEND_NOOB")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_VACATION_LONG")."</td><td><span class=vacation>".loca("GALAXY_LEGEND_VACATION")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_BANNED_LONG")."</td><td><span class=banned>".loca("GALAXY_LEGEND_BANNED")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_INACTIVE7_LONG")."</td><td><span class=inactive>".loca("GALAXY_LEGEND_INACTIVE7")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_INACTIVE28_LONG")."</td><td><span class=longinactive>".loca("GALAXY_LEGEND_INACTIVE28")."</span></td></tr>";
echo "</table>\", ABOVE, WIDTH, 150, STICKY, MOUSEOFF, DELAY, 500, CENTER);' onmouseout='return nd();'>".loca("GALAXY_LEGEND")."</a></td>\n";
echo "</tr>\n";

// Additional information (Commander).
// The text is typed into the extra_info variable and if the string is not empty - an additional row of the table is printed.

$extra_info = "";
$sep = "&nbsp;&nbsp;&nbsp;&nbsp;";
$sep_required = false;

if ($prem['commander'] && $aktplanet[GID_F_PROBE] > 0) {
    $extra_info .= "<span id=\"probes\">".nicenum($aktplanet[GID_F_PROBE])."</span>".loca("GALAXY_INFO_SPY_PROBES");
    $sep_required = true;
}
if ($prem['commander'] && $aktplanet[GID_F_RECYCLER] > 0) {
    if ($sep_required) $extra_info .= $sep;
    $extra_info .= "<span id=\"recyclers\">".nicenum($aktplanet[GID_F_RECYCLER])."</span>".loca("GALAXY_INFO_RECYCLERS");
    $sep_required = true;
}
if ($prem['commander'] && $aktplanet[GID_D_IPM] > 0) {
    if ($sep_required) $extra_info .= $sep;
    $extra_info .= "<span id=\"missiles\">".nicenum($aktplanet[GID_D_IPM])."</span>".loca("GALAXY_INFO_IPM");
}
// Deuterium is only shown for moons (even without Commander)
if ($aktplanet['type'] == PTYP_MOON) {
    $extra_info .= loca("GALAXY_INFO_DEUTERIUM") . nicenum($aktplanet[GID_RC_DEUTERIUM]);
}
if ($prem['commander']) {
    $extra_info .= $sep . "<span id='slots'>".$nowfleet."</span>&nbsp;".va(loca("GALAXY_INFO_SLOTS"), $maxfleet);
}

if ($extra_info !== "") {
?>
<tr>
<td class="c" colspan="<?=(8+$has_custom);?>">
<?=$extra_info;?></td>
</tr>
<?php
}   // extra_info
?>
<tr style="display: none;" id="fleetstatusrow"><th colspan="<?=(8+$has_custom);?>"><!--<div id="fleetstatus"></div>-->
<table style="font-weight: bold;" width=100% id="fleetstatustable">
<!-- will be filled with content later on while processing ajax replys -->
</table>
</th>
</tr>

<?php
echo "</table>\n\n";

}    // Not enough deuterium

echo "<br><br><br><br>\n";
?>