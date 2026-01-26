<?php

/** @var array $GlobalUser */
/** @var string $db_prefix */
/** @var string $aktplanet */

// Resource Settings.

$prem = PremiumStatus ($GlobalUser);
if ( $prem['geologist'] )
{
    $geologe_text = "<img border=\"0\" src=\"img/geologe_ikon.gif\" alt=\"".loca("PREM_GEOLOGE")."\" onmouseover='return overlib(\"<font color=#ffffff>".loca("PREM_GEOLOGE")."</font>\", WIDTH, 80);' onmouseout='return nd();' width=\"20\" height=\"20\">";
    $g_factor = 1.1;
}
else
{
    $geologe_text = "&nbsp;";
    $g_factor = 1.0;
}
if ( $prem['engineer'] )
{
    $engineer_text = "<img border=\"0\" src=\"img/ingenieur_ikon.gif\" alt=\"".loca("PREM_ENGINEER")."\" onmouseover='return overlib(\"<font color=#ffffff>".loca("PREM_ENGINEER")."</font>\", WIDTH, 80);' onmouseout='return nd();' width=\"20\" height=\"20\">";
    $e_factor = 1.1;
}
else
{
    $engineer_text = "&nbsp;";
    $e_factor = 1.0;
}

// POST requests processing (you cannot change energy settings in VM)
if ( method () === "POST" && !$GlobalUser['vacation'] )
{
    $exist1 = key_exists ( 'last1', $_POST ) ? true : false;
    $exist2 = key_exists ( 'last2', $_POST ) ? true : false;
    $exist3 = key_exists ( 'last3', $_POST ) ? true : false;
    $exist4 = key_exists ( 'last4', $_POST ) ? true : false;
    $exist12 = key_exists ( 'last12', $_POST ) ? true : false;
    $exist212 = key_exists ( 'last212', $_POST ) ? true : false;

    $last1 = key_exists ( 'last1', $_POST ) ? intval($_POST['last1']) : 0;
    $last2 = key_exists ( 'last2', $_POST ) ? intval($_POST['last2']) : 0;
    $last3 = key_exists ( 'last3', $_POST ) ? intval($_POST['last3']) : 0;
    $last4 = key_exists ( 'last4', $_POST ) ? intval($_POST['last4']) : 0;
    $last12 = key_exists ( 'last12', $_POST ) ? intval($_POST['last12']) : 0;
    $last212 = key_exists ( 'last212', $_POST ) ? intval($_POST['last212']) : 0;

    // Checking for incorrect parameters.
    if ( $last1 > 100 || $last2 > 100 || $last3 > 100 ||
         $last4 > 100 || $last12 > 100 || $last212 > 100 ) Error ( "resources: Attempt to set prod settings to more than 100%" );

    if ( $last1 < 0 ) $last1 = 0;        // It should not be < 0.
    if ( $last2 < 0 ) $last2 = 0;
    if ( $last3 < 0 ) $last3 = 0;
    if ( $last4 < 0 ) $last4 = 0;
    if ( $last12 < 0 ) $last12 = 0;
    if ( $last212 < 0 ) $last212 = 0;

    // Make multiples of 10.
    $last1 = round ($last1 / 10) * 10 / 100;
    $last2 = round ($last2 / 10) * 10 / 100;
    $last3 = round ($last3 / 10) * 10 / 100;
    $last4 = round ($last4 / 10) * 10 / 100;
    $last12 = round ($last12 / 10) * 10 / 100;
    $last212 = round ($last212 / 10) * 10 / 100;

    $planet_id = $aktplanet['planet_id'];
    if ( $exist1 || $exist2 || $exist3 || $exist4 || $exist12 || $exist212 ) {
        $query = "UPDATE ".$db_prefix."planets SET ";
        if ($exist1) $query .= "prod1 = $last1, ";
        if ($exist2) $query .= "prod2 = $last2, ";
        if ($exist3) $query .= "prod3 = $last3, ";
        if ($exist4) $query .= "prod4 = $last4, ";
        if ($exist12) $query .= "prod12 = $last12, ";
        if ($exist212) $query .= "prod212 = $last212, ";
        $query .= " type = type WHERE planet_id = $planet_id";
        dbquery ($query);
    }

    $aktplanet = GetUpdatePlanet ( $GlobalUser['aktplanet'], time() );    // reload the planet.
    if ($aktplanet == null) {
        Error ("Can't get aktplanet");
    }
}

// ***********************************************************************

function get_prod (int $id, array|null $planet) : float
{
    if ($planet == null) return 0;
    switch ($id)
    {
        case GID_B_METAL_MINE: return 100 * $planet['prod'.GID_B_METAL_MINE];
        case GID_B_CRYS_MINE: return 100 * $planet['prod'.GID_B_CRYS_MINE];
        case GID_B_DEUT_SYNTH: return 100 * $planet['prod'.GID_B_DEUT_SYNTH];
        case GID_B_SOLAR: return 100 * $planet['prod'.GID_B_SOLAR];
        case GID_B_FUSION: return 100 * $planet['prod'.GID_B_FUSION];
        case GID_F_SAT: return 100 * $planet['prod'.GID_F_SAT];
    }
    return 0;
}

function prod_select (int $id, array|null $planet) : void
{
    if ($planet == null) return;
	echo"  <th> <select name=\"last$id\" size=\"1\">\n";
    $prod = get_prod ( $id, $planet );
    for ($i=10; $i>=0; $i--)
    {
        $selected = "";
        if (10*$i == $prod) $selected = "selected";
        echo"      <option value=\"".(10*$i)."\" $selected>".(10*$i)."%</option>\n";
    }
	echo"        </select>\n";
	echo"   </th>\n";
}

function nicenum2 (float|int $num) : string    // for debugging. the Germans messed up with rounding, I don't know where they call floor, ceil and round.
{
    return nicenum(round($num));
    //return $num;
}

function rgnum (float|int $num) : string
{
    if ( $num > 0 ) return "<font color=\"#00ff00\">".nicenum2($num)."</font>";
    else return "<font color=\"#ff0000\">".nicenum2($num)."</font>";
}

$speed = $GlobalUni['speed'];
$planet = $aktplanet;

// Production.
$m_hourly = prod_metal ($planet[GID_B_METAL_MINE], $planet['prod'.GID_B_METAL_MINE]) * $planet['factor'] * $speed * $g_factor;
$k_hourly = prod_crys ($planet[GID_B_CRYS_MINE], $planet['prod'.GID_B_CRYS_MINE]) * $planet['factor'] * $speed * $g_factor;
$d_hourly = prod_deut ($planet[GID_B_DEUT_SYNTH], $planet['temp']+40, $planet['prod'.GID_B_DEUT_SYNTH]) * $planet['factor'] * $speed * $g_factor;
$s_prod = prod_solar($planet[GID_B_SOLAR], $planet['prod'.GID_B_SOLAR]) * $e_factor;
$f_prod = prod_fusion($planet[GID_B_FUSION], $GlobalUser[GID_R_ENERGY], $planet['prod'.GID_B_FUSION]) * $e_factor;
$ss_prod = prod_sat($planet['temp']+40) * $planet[GID_F_SAT] * $planet['prod'.GID_F_SAT] * $e_factor;

// Consumption.
$m_cons = cons_metal ($planet[GID_B_METAL_MINE]) * $planet['prod'.GID_B_METAL_MINE];
$m_cons0 = round ($m_cons * $planet['factor']);
$k_cons = cons_crys ($planet[GID_B_CRYS_MINE]) * $planet['prod'.GID_B_CRYS_MINE];
$k_cons0 = round ($k_cons * $planet['factor']);
$d_cons = cons_deut ($planet[GID_B_DEUT_SYNTH]) * $planet['prod'.GID_B_DEUT_SYNTH];
$d_cons0 = round ($d_cons * $planet['factor']);
$f_cons = - cons_fusion ( $planet[GID_B_FUSION], $planet['prod'.GID_B_FUSION] ) * $speed;

$m_total = $m_hourly + (20*$speed);
$k_total = $k_hourly + (10*$speed);
$d_total = $d_hourly + $f_cons;

echo "<center> \n";
echo "<br> \n";
echo "<br> \n";
echo va(loca("RES_FACTOR")." ", round($aktplanet['factor'],2))."\n";

// Not known for what, but it's in the original game.
$count = 0;
$result = EnumPlanets ();
$rows = dbrows ($result);
while ($rows--)
{
    $pl = dbarray ($result);
    if ( $pl['type'] != PTYP_MOON ) $count++;
}
if ( $count > 9 ) echo "<br><font color=#ff000>".loca("RES_INFO")."</font>";

echo "<form action=\"index.php?page=resources&session=".$_GET['session']."\" method=\"post\" id='ressourcen'> \n";
echo "<input type=hidden name='screen' id='screen'> \n";
echo "<table width=\"550\"> \n";

echo "  <tr> \n";
echo "    <td class=\"c\" colspan=\"6\"> \n";
echo "    ".loca("RES_PROD")." &quot;".$aktplanet['name']."&quot;\n";
echo "    </td> \n";
echo "  </tr>\n";

echo "  <tr> \n";
echo "   <th colspan=\"2\"></th>    <th>".loca("NAME_".GID_RC_METAL)."</th>    <th>".loca("NAME_".GID_RC_CRYSTAL)."</th>    <th>".loca("NAME_".GID_RC_DEUTERIUM)."</th>    <th>".loca("NAME_".GID_RC_ENERGY)."</th> \n";
echo "  </tr>\n";

// Natural production
echo "  <tr> \n";
echo "   <th colspan=\"2\">".loca("RES_NATURAL")."</th> \n";
echo "   <td class=\"k\">".(20*$speed)."</td>    <td class=\"k\">".(10*$speed)."</td>    <td class=\"k\">0</td>    <td class=\"k\">0</td> \n";
echo "  </tr>\n";

// Metal mine
if ($aktplanet[GID_B_METAL_MINE]) {
    $color1 = $m_hourly ? "<font color='00FF00'>" : "";
    $color2 = $m_cons ? "<font color='FF0000'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_1")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_METAL_MINE]).")</th><th>".$geologe_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        $color1".nicenum2($m_hourly)."</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        $color2".nicenum2($m_cons0)."/".nicenum2($m_cons)."</th> \n";
	prod_select (GID_B_METAL_MINE, $planet);
	echo "  </tr>\n";
}

// Crystal mine
if ($aktplanet[GID_B_CRYS_MINE]) {
    $color1 = $k_hourly ? "<font color='00FF00'>" : "";
    $color2 = $k_cons ? "<font color='FF0000'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_2")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_CRYS_MINE]).")</th><th>".$geologe_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        $color1".nicenum2($k_hourly)."</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        $color2".nicenum2($k_cons0)."/".nicenum2($k_cons)."</th> \n";
	prod_select (GID_B_CRYS_MINE, $planet);
	echo "  </tr>\n";
}

// Deuterium synthesizer
if ($aktplanet[GID_B_DEUT_SYNTH]) {
    $color1 = $d_hourly ? "<font color='00FF00'>" : "";
    $color2 = $d_cons ? "<font color='FF0000'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_3")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_DEUT_SYNTH]).")</th><th>".$geologe_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color1".nicenum2($d_hourly)."</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color2".nicenum2($d_cons0)."/".nicenum2($d_cons)."</th>\n";
	prod_select (GID_B_DEUT_SYNTH, $planet);
	echo "  </tr>\n";
}

// Solar Plant
if ($aktplanet[GID_B_SOLAR]) {
    $color = $s_prod ? "<font color='00FF00'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_4")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_SOLAR]).")</th><th>".$engineer_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color".nicenum2($s_prod)."</th>\n";
	prod_select (GID_B_SOLAR, $planet);
	echo "  </tr>\n";
}

// Fusion Reactor
if ($aktplanet[GID_B_FUSION]) {
    $color1 = $f_cons ? "<font color='FF0000'>" : "";
    $color2 = $f_prod ? "<font color='00FF00'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_12")." (".va(loca("RES_LEVEL"), $aktplanet[GID_B_FUSION]).")</th><th>".$engineer_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color1".nicenum2($f_cons)."</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color2".nicenum2($f_prod)."</th>\n";
	prod_select (GID_B_FUSION, $planet);
	echo "  </tr>\n";
}

// Solar satellites
if ($aktplanet[GID_F_SAT]) {
    $color = $ss_prod ? "<font color='00FF00'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_212")." (".va(loca("RES_AMOUNT"), $aktplanet[GID_F_SAT]).")</th><th>".$engineer_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color".nicenum2($ss_prod)."</th>\n";
	prod_select (GID_F_SAT, $planet);
	echo "  </tr>\n";
}

// Storages
echo "    <tr>   <tr> \n";
echo "    <th colspan=\"2\">".loca("RES_CAPACITY")."</th> \n";
echo "    <td class=\"k\"><font color=\"#00ff00\">".nicenum2($planet['mmax']/1000)."k</font></td> \n";
echo "    <td class=\"k\"><font color=\"#00ff00\">".nicenum2($planet['kmax']/1000)."k</font></td> \n";
echo "    <td class=\"k\"><font color=\"#00ff00\">".nicenum2($planet['dmax']/1000)."k</font></td> \n";
echo "    <td class=\"k\"><font color=\"#00ff00\">-</font></td> \n";
echo "    <td class=\"k\"> \n";
echo "    <input type=\"submit\" name=\"action\" value=\"".loca("RES_CALCULATE")."\"></td> \n";
echo "  </tr> \n";
echo "  <tr>     <th colspan=\"6\" height=\"4\"></th>   </tr> \n";

// Total production
echo "  <tr> \n";
echo "    <th colspan=\"2\">".loca("RES_PER_HOUR")."</th> \n";
echo "    <td class=\"k\">".rgnum($m_total)."</td> \n";
echo "    <td class=\"k\">".rgnum($k_total)."</td> \n";
echo "    <td class=\"k\">".rgnum($d_total)."</td> \n";
echo "    <td class=\"k\">".rgnum($planet['e'])."</td> \n";
echo "  </tr> \n";

echo "  <tr> \n";
echo "    <th colspan=\"2\">".loca("RES_PER_DAY")."</th> \n";
echo "    <td class=\"k\">".rgnum($m_total*24)."</td> \n";
echo "    <td class=\"k\">".rgnum($k_total*24)."</td> \n";
echo "    <td class=\"k\">".rgnum($d_total*24)."</td> \n";
echo "    <td class=\"k\">".rgnum($planet['e'])."</td> \n";
echo "  </tr> \n";

echo "  <tr> \n";
echo "    <th colspan=\"2\">".loca("RES_PER_WEEK")."</th> \n";
echo "    <td class=\"k\">".rgnum($m_total*24*7)."</td> \n";
echo "    <td class=\"k\">".rgnum($k_total*24*7)."</td> \n";
echo "    <td class=\"k\">".rgnum($d_total*24*7)."</td> \n";
echo "    <td class=\"k\">".rgnum($planet['e'])."</td> \n";
echo "  </tr>\n";

echo "  </table> \n";

// ***********************************************************************

echo "<br><br><br><br>\n";
?>