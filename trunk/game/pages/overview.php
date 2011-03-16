<?php

// Обзор.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();

$OverviewMessage = "";
$OverviewError = "";

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdateLastClick ( $GlobalUser['player_id'] );

PageHeader ("overview");

// *******************************************************************

//CreatePlanet (1, 123, 8, 1 );
//CreatePlanet ( 3, 13, 3, 1 );

$stime = time ();
if ( key_exists ('lgn', $_GET) && $_GET['lgn'] == 1 ) UpdatePlanetActivity ( $aktplanet['planet_id'] );  // Обновить активность на Главной планете при входе в игру.

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n\n";

echo "<script type=\"text/javascript\">\n";
echo "<!--\n";
echo "function t_building() {\n";
echo "	v = new Date();\n";
echo "	var bxx = document.getElementById('bxx');\n";
echo "	var timeout = 1;\n";
echo "	n=new Date();\n";
echo "	ss=pp;\n";
echo "	aa=Math.round((n.getTime()-v.getTime())/1000.);\n";
echo "	s=ss-aa;\n";
echo "	m=0;\n";
echo "	h=0;\n\n";
echo "	if (s < 0) {\n";
echo "		bxx.innerHTML='--';\n";
echo "		if ((ss + 6) >= aa) {\n";
echo "			window.setTimeout('document.location.href=\"index.php?page=overview&session='+ps+'\";', 1500);\n";
echo "		}\n";
echo "	} else {\n";
echo "		if(s>59){\n";
echo "			m=Math.floor(s/60);\n";
echo "			s=s-m*60;\n";
echo "		}\n";
echo "        if(m>59){\n";
echo "        	h=Math.floor(m/60);\n";
echo "        	m=m-h*60;\n";
echo "        }\n";
echo "        if(s<10){\n";
echo "        	s=\"0\"+s;\n";
echo "        }\n";
echo "        if(m<10){\n";
echo "        	m=\"0\"+m;\n";
echo "        }\n";
echo "		bxx.innerHTML=h+\":\"+m+\":\"+s;\n";
echo "	}\n";
echo "	pp=pp-1;\n";
echo "  	window.setTimeout(\"t_building();\", 999);\n";
echo "}\n";
echo "//-->\n";
echo "</script>\n\n\n";

// Меню планеты
echo "<table width='519'>\n\n";
echo "<tr><td class='c' colspan='4'>\n";
if ($aktplanet['type'] == 0) $name = "Луна \"".$aktplanet['name']."\" на орбите [".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."]";
else $name = "Планета \"".$aktplanet['name']."\"";
echo "<a href='index.php?page=renameplanet&session=".$_GET['session']."&pl=".$aktplanet['planet_id']."' title='Меню планеты'>".$name."</a>     (".$GlobalUser['oname'].")\n";
echo "</td></tr>\n";

// Новые сообщения.
$num = UnreadMessages ( $GlobalUser['player_id'] );
if ($num) echo "<tr><th colspan=\"4\"><a href=\"index.php?page=messages&dsp=1&session=".$_GET['session']."\">  Новых сообщений: $num   </th></tr>\n";

// Время сервера и список событий.
echo "<tr><th>    Серверное время   </th> <th colspan=3>".date ( "D M j G:i:s", $stime)."</th></tr>\n";
echo "<tr><td colspan='4' class='c'>  События   </td> </tr>\n";

// Показать, если у планеты есть луна.
$moonid = PlanetHasMoon ( $aktplanet['planet_id'] );
if ($moonid)
{
    $moonobj = GetPlanet ( $moonid );
    echo "<th>    ".$moonobj['name']." (Луна)     <br>\n";
    echo "<a href=\"index.php?page=overview&session=".$_GET['session']."&cp=".$moonid."\"><img src=\"".GetPlanetSmallImage ( UserSkin (), 0 )."\" width=\"50\" alt=\"Луна\" height=\"50\" ></a>\n";
    echo "</th>\n";
}
else echo "<th>\n</th>\n";

// Показать картинку планеты.
echo "<th colspan=\"2\">\n<img src=\"".GetPlanetImage ( UserSkin (), $aktplanet['type'] )."\" width=\"200\" height=\"200\">\n";

$result = GetBuildQueue ( $aktplanet['planet_id'] );
$cnt = dbrows ( $result );
if ( $cnt > 0 )
{
    $queue = dbarray ($result);
    $left = $queue['end'] - time ();
    echo "<br><center>".loca("NAME_".$queue['obj_id']) . " ".$queue['type']." (".$queue['level'].")<div id=\"bxx\" title=\"".$queue['end']."\" class=\"z\"></div><SCRIPT language=JavaScript>\n";
    echo "pp=\"".$left."\"; ps=\"".$_GET['session']."\"; t_building();\n";
    echo "</script></center><br>\n";
}
echo "</th>\n";

// Список планет.
echo "<th class='s'>\n";
echo "<table border='0' align='top' class='s'>\n";
$result = EnumPlanets ( $GlobalUser['player_id']);
$num = dbrows ($result);
for ($i=0; $i<$num; $i++)
{
    $planet = dbarray ($result);
    if ($planet['type'] == 0 || $planet['planet_id'] == $aktplanet['planet_id'] || $planet['destroyed']) { $num--; $i--; continue; }
    if (($i%2) == 0) echo "<tr>\n";
    echo "<th> ".$planet['name']."<br> <a href=\"index.php?page=overview&session=".$_GET['session']."&cp=".$planet['planet_id']."\" title=\"".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]\">";
    echo "<img src=\"".GetPlanetImage ( UserSkin (), $planet['type'] )."\" width=\"50\" height=\"50\" title=\"".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]\" ></a>\n";
    echo "<br><center>";
    {    // Вывести текущее строительство
        $qresult = GetBuildQueue ( $planet['planet_id'] );
        $cnt = dbrows ( $qresult );
        if ( $cnt > 0 ) {
            $queue = dbarray ($qresult);
            echo loca("NAME_".$queue['obj_id']) . $queue['type'];
        }
        else echo "";
        dbfree ( $qresult );
    }
    echo "</center></th>\n";
    if ($i == $num-1) echo "</tr>\n\n";
    else if (($i%2) != 0) echo "</tr>\n\n";
    dbfree ( $planet );
}
echo "<tr></tr>\n</table>\n</th>\n\n";

// Параметры планеты
$uni = LoadUniverse ( );
echo "<tr><th> Диаметр </th><th colspan=3>".nicenum($aktplanet['diameter'])." км     (застроенная территория: <a title=\"застроенная территория\">".$aktplanet['fields']." </a> из <a title=\"максимально доступная для застройки территория\">".$aktplanet['maxfields']." </a> полей)   </th></tr>\n";
echo "<tr><th> Температура </th> <th colspan=3> от ".$aktplanet['temp']."°C до ".($aktplanet['temp']+40)."°C</th> </tr>   \n";
echo "<tr><th> Координаты</th><th colspan=3><a href=\"index.php?page=galaxy&galaxy=".$aktplanet['g']."&system=".$aktplanet['s']."&position=".$aktplanet['p']."&session=".$_GET['session']."\" >[".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."]</a></th></tr>\n";
echo "<tr><th> Очки</th><th colspan=3>".nicenum(floor($GlobalUser['score1']/1000))." (место <a href='index.php?page=statistics&session=".$_GET['session']."&start=".(floor($GlobalUser['place1']/100)*100+1)."'>".nicenum($GlobalUser['place1'])."</a> из ".nicenum($uni['usercount']).")</th></tr>     \n";

echo "</table>\n<br><br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n\n";

PageFooter ($OverviewMessage, $OverviewError, false);
ob_end_flush ();
?>