<?php

// Сырьё.

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();

if ( $GlobalUser['redesign'] ) { include "red_pages/redesign_resources.php"; exit(); }

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "technames", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

$prem = PremiumStatus ($GlobalUser);
if ( $prem['geologist'] )
{
    $geologe_text = "<img border=\"0\" src=\"img/geologe_ikon.gif\" alt=\"Геолог\" onmouseover='return overlib(\"<font color=#ffffff>Геолог</font>\", WIDTH, 80);' onmouseout='return nd();' width=\"20\" height=\"20\">";
    $g_factor = 1.1;
}
else
{
    $geologe_text = "&nbsp;";
    $g_factor = 1.0;
}
if ( $prem['engineer'] )
{
    $engineer_text = "<img border=\"0\" src=\"img/ingenieur_ikon.gif\" alt=\"Инженер\" onmouseover='return overlib(\"<font color=#ffffff>Инженер</font>\", WIDTH, 80);' onmouseout='return nd();' width=\"20\" height=\"20\">";
    $e_factor = 1.1;
}
else
{
    $engineer_text = "&nbsp;";
    $e_factor = 1.0;
}

// Обработка POST-запросов (в РО изменять настройки энергии нельзя)
if ( method () === "POST" && !$GlobalUser['vacation'] )
{
    $_POST['last1'] = intval($_POST['last1']);
    $_POST['last2'] = intval($_POST['last2']);
    $_POST['last3'] = intval($_POST['last3']);
    $_POST['last4'] = intval($_POST['last4']);
    $_POST['last12'] = intval($_POST['last12']);
    $_POST['last212'] = intval($_POST['last212']);

    // Проверка на неверные параметры.
    if ( $_POST['last1'] > 100 || $_POST['last2'] > 100 || $_POST['last3'] > 100 ||
         $_POST['last4'] > 100 || $_POST['last12'] > 100 || $_POST['last212'] > 100 ) Error ( "resources: Попытка установки производительности больше 100%" );

    if ( $_POST['last1'] < 0 ) $_POST['last1'] = 0;        // Не должно быть < 0.
    if ( $_POST['last2'] < 0 ) $_POST['last2'] = 0;
    if ( $_POST['last3'] < 0 ) $_POST['last3'] = 0;
    if ( $_POST['last4'] < 0 ) $_POST['last4'] = 0;
    if ( $_POST['last12'] < 0 ) $_POST['last12'] = 0;
    if ( $_POST['last212'] < 0 ) $_POST['last212'] = 0;

    // Сделать кратно 10.
    $last1 = round ($_POST['last1'] / 10) * 10 / 100;
    $last2 = round ($_POST['last2'] / 10) * 10 / 100;
    $last3 = round ($_POST['last3'] / 10) * 10 / 100;
    $last4 = round ($_POST['last4'] / 10) * 10 / 100;
    $last12 = round ($_POST['last12'] / 10) * 10 / 100;
    $last212 = round ($_POST['last212'] / 10) * 10 / 100;

    $planet_id = $aktplanet['planet_id'];
    $query = "UPDATE ".$db_prefix."planets SET mprod = $last1, kprod = $last2, dprod = $last3, sprod = $last4, fprod = $last12, ssprod = $last212 WHERE planet_id = $planet_id";
    dbquery ($query);

    $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );    // перегрузить планету.
}

PageHeader ("resources");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

// ***********************************************************************

function get_prod ($id, $planet)
{
    switch ($id)
    {
        case 1: return 100 * $planet['mprod'];
        case 2: return 100 * $planet['kprod'];
        case 3: return 100 * $planet['dprod'];
        case 4: return 100 * $planet['sprod'];
        case 12: return 100 * $planet['fprod'];
        case 212: return 100 * $planet['ssprod'];
    }
}

function prod_select ($id, $planet)
{
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

function nicenum2 ($num)    // для отладки. немцы намудрили с округлением, хрен поймешь где они вызывают floor, ceil и round.
{
    return nicenum(round($num));
    //return $num;
}

function rgnum ($num)
{
    if ( $num > 0 ) return "<font color=\"#00ff00\">".nicenum2($num)."</font>";
    else return "<font color=\"#ff0000\">".nicenum2($num)."</font>";
}

$unitab = LoadUniverse ( );
$speed = $unitab['speed'];
$planet = $aktplanet;

// Производство.
$m_hourly = prod_metal ($planet['b1'], $planet['mprod']) * $planet['factor'] * $speed * $g_factor;
$k_hourly = prod_crys ($planet['b2'], $planet['kprod']) * $planet['factor'] * $speed * $g_factor;
$d_hourly = prod_deut ($planet['b3'], $planet['temp']+40, $planet['dprod']) * $planet['factor'] * $speed * $g_factor;
$s_prod = prod_solar($planet['b4'], $planet['sprod']) * $e_factor;
$f_prod = prod_fusion($planet['b12'], $GlobalUser['r113'], $planet['fprod']) * $e_factor;
$ss_prod = prod_sat($planet['temp']+40) * $planet['f212'] * $planet['ssprod'] * $e_factor;

// Потребление.
$m_cons = cons_metal ($planet['b1']) * $planet['mprod'] * $speed;
$m_cons0 = round ($m_cons * $planet['factor']);
$k_cons = cons_crys ($planet['b2']) * $planet['kprod'] * $speed;
$k_cons0 = round ($k_cons * $planet['factor']);
$d_cons = cons_deut ($planet['b3']) * $planet['dprod'] * $speed;
$d_cons0 = round ($d_cons * $planet['factor']);
$f_cons = - cons_fusion ( $planet['b12'], $planet['fprod'] ) * $speed;

$m_total = $m_hourly + (20*$speed);
$k_total = $k_hourly + (10*$speed);
$d_total = $d_hourly + $f_cons;

echo "<center> \n";
echo "<br> \n";
echo "<br> \n";
echo "Коэффициент производства: ".round($aktplanet['factor'],2)."\n";

// Не известно для чего, но это есть в оригинальной игре.
$count = 0;
$result = EnumPlanets ();
$rows = dbrows ($result);
while ($rows--)
{
    $pl = dbarray ($result);
    if ( $pl['type'] != 0 ) $count++;
}
if ( $count > 9 ) echo "<br><font color=#ff000>На этой планете наблюдаются беспорядки, так как империя слишком велика.</font>";

echo "<form action=\"index.php?page=resources&session=".$_GET['session']."\" method=\"post\" id='ressourcen'> \n";
echo "<input type=hidden name='screen' id='screen'> \n";
echo "<table width=\"550\"> \n";

echo "  <tr> \n";
echo "    <td class=\"c\" colspan=\"6\"> \n";
echo "    Производство сырья на планете &quot;".$aktplanet['name']."&quot;\n";
echo "    </td> \n";
echo "  </tr>\n";

echo "  <tr> \n";
echo "   <th colspan=\"2\"></th>    <th>Металл</th>    <th>Кристалл</th>    <th>Дейтерий</th>    <th>Энергия</th> \n";
echo "  </tr>\n";

// Естественное производство
echo "  <tr> \n";
echo "   <th colspan=\"2\">Естественное производство</th> \n";
echo "   <td class=\"k\">".(20*$speed)."</td>    <td class=\"k\">".(10*$speed)."</td>    <td class=\"k\">0</td>    <td class=\"k\">0</td> \n";
echo "  </tr>\n";

// Шахта металла
if ($aktplanet['b1']) {
    $color1 = $m_hourly ? "<font color='00FF00'>" : "";
    $color2 = $m_cons ? "<font color='FF0000'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_1")." (уровень ".$aktplanet['b1'].")</th><th>".$geologe_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        $color1".nicenum2($m_hourly)."</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        $color2".nicenum2($m_cons0)."/".nicenum2($m_cons)."</th> \n";
	prod_select (1, $planet);
	echo "  </tr>\n";
}

// Шахта кристалла
if ($aktplanet['b2']) {
    $color1 = $k_hourly ? "<font color='00FF00'>" : "";
    $color2 = $k_cons ? "<font color='FF0000'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_2")." (уровень ".$aktplanet['b2'].")</th><th>".$geologe_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        $color1".nicenum2($k_hourly)."</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        0</font>   <th> \n";
	echo "    <font color=\"#FFFFFF\">        $color2".nicenum2($k_cons0)."/".nicenum2($k_cons)."</th> \n";
	prod_select (2, $planet);
	echo "  </tr>\n";
}

// Шахта дейтерия
if ($aktplanet['b3']) {
    $color1 = $d_hourly ? "<font color='00FF00'>" : "";
    $color2 = $d_cons ? "<font color='FF0000'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_3")." (уровень ".$aktplanet['b3'].")</th><th>".$geologe_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color1".nicenum2($d_hourly)."</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color2".nicenum2($d_cons0)."/".nicenum2($d_cons)."</th>\n";
	prod_select (3, $planet);
	echo "  </tr>\n";
}

// Солнечная электростанция
if ($aktplanet['b4']) {
    $color = $s_prod ? "<font color='00FF00'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_4")." (уровень ".$aktplanet['b4'].")</th><th>".$engineer_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color".nicenum2($s_prod)."</th>\n";
	prod_select (4, $planet);
	echo "  </tr>\n";
}

// Термояд
if ($aktplanet['b12']) {
    $color1 = $f_cons ? "<font color='FF0000'>" : "";
    $color2 = $f_prod ? "<font color='00FF00'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_12")." (уровень ".$aktplanet['b12'].")</th><th>".$engineer_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color1".nicenum2($f_cons)."</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color2".nicenum2($f_prod)."</th>\n";
	prod_select (12, $planet);
	echo "  </tr>\n";
}

// Солнечные спутники
if ($aktplanet['f212']) {
    $color = $ss_prod ? "<font color='00FF00'>" : "";
	echo "  <tr> \n";
	echo "<th>".loca("NAME_212")." (кол-во ".$aktplanet['f212'].")</th><th>".$engineer_text."</th>   <th> \n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       0</font>   <th>\n";
	echo "    <font color=\"#FFFFFF\">       $color".nicenum2($ss_prod)."</th>\n";
	prod_select (212, $planet);
	echo "  </tr>\n";
}

// Хранилища
echo "    <tr>   <tr> \n";
echo "    <th colspan=\"2\">Вместимость</th> \n";
echo "    <td class=\"k\"><font color=\"#00ff00\">".nicenum2($planet['mmax']/1000)."k</font></td> \n";
echo "    <td class=\"k\"><font color=\"#00ff00\">".nicenum2($planet['kmax']/1000)."k</font></td> \n";
echo "    <td class=\"k\"><font color=\"#00ff00\">".nicenum2($planet['dmax']/1000)."k</font></td> \n";
echo "    <td class=\"k\"><font color=\"#00ff00\">-</font></td> \n";
echo "    <td class=\"k\"> \n";
echo "    <input type=\"submit\" name=\"action\" value=\"Посчитать\"></td> \n";
echo "  </tr> \n";
echo "  <tr>     <th colspan=\"6\" height=\"4\"></th>   </tr> \n";

// Общая выработка
echo "  <tr> \n";
echo "    <th colspan=\"2\">Выработка в час:</th> \n";
echo "    <td class=\"k\">".rgnum($m_total)."</td> \n";
echo "    <td class=\"k\">".rgnum($k_total)."</td> \n";
echo "    <td class=\"k\">".rgnum($d_total)."</td> \n";
echo "    <td class=\"k\">".rgnum($planet['e'])."</td> \n";
echo "  </tr> \n";

echo "  <tr> \n";
echo "    <th colspan=\"2\">Выработка в день:</th> \n";
echo "    <td class=\"k\">".rgnum($m_total*24)."</td> \n";
echo "    <td class=\"k\">".rgnum($k_total*24)."</td> \n";
echo "    <td class=\"k\">".rgnum($d_total*24)."</td> \n";
echo "    <td class=\"k\">".rgnum($planet['e'])."</td> \n";
echo "  </tr> \n";

echo "  <tr> \n";
echo "    <th colspan=\"2\">Выработка в неделю:</th> \n";
echo "    <td class=\"k\">".rgnum($m_total*24*7)."</td> \n";
echo "    <td class=\"k\">".rgnum($k_total*24*7)."</td> \n";
echo "    <td class=\"k\">".rgnum($d_total*24*7)."</td> \n";
echo "    <td class=\"k\">".rgnum($planet['e'])."</td> \n";
echo "  </tr>\n";

echo "  </table> \n";

// ***********************************************************************

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>