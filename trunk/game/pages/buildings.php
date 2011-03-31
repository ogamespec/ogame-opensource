<?php

// Верфь, Оборона и Исследования.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

// Обработка POST-запросов.
if ( method () === "POST" && !$GlobalUser['vacation'] )
{
    foreach ( $_POST['fmenge'] as $gid=>$value )
    {
        if ( $value < 0 ) $value = 0;
        if ( $value > 0 ) {
            // Рассчитать количество (не больше, чем ресурсов на планете и не больше 999)
            if ( $value > 999 ) $value = 999;

            $m = $k = $d = $e = 0;
            ShipyardPrice ( $gid, &$m, &$k, &$d, &$e );

            if ( $aktplanet['m'] < $m || $aktplanet['k'] < $k || $aktplanet['d'] < $d ) continue;    // недостаточно ресурсов для одной единицы

            // Купола.

            if ($m) $cm = floor ($aktplanet['m'] / $m);
            else $cm = 1000;
            if ($k) $ck = floor ($aktplanet['k'] / $k);
            else $ck = 1000;
            if ($d) $cd = floor ($aktplanet['d'] / $d);
            else $cd = 1000;
            $v = min ( $cm, min ($ck, $cd) );
            if ( $value > $v ) $value = $v;

            AddShipyard ( $GlobalUser['player_id'], $aktplanet['planet_id'], $gid, $value );
            $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );    // обновить состояние планеты.
        }
    }
}

// Обработка GET-запросов.
if ( method () === "GET"  && !$GlobalUser['vacation'] )
{
	if ( $_GET['mode'] === "Forschung" ) {
		$result = GetResearchQueue ( $GlobalUser['player_id'] );
		$resqueue = dbarray ($result);
		if ( $resqueue == null )		// Исследование не ведется (запустить)
		{
			if ( key_exists ( 'bau', $_GET ) ) StartResearch ( $GlobalUser['player_id'], $aktplanet['planet_id'], $_GET['bau'] );
		}
		else	// Ведется исследования (отменить)
		{
			if ( key_exists ( 'unbau', $_GET ) ) StopResearch ( $GlobalUser['player_id'], $_GET['unbau'] );
		}
	}
}

PageHeader ("buildings");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

$result = GetShipyardQueue ($aktplanet['planet_id']);
$rows = dbrows ($result);
echo "<table>";
while ( $rows-- ) 
{
    $queue = dbarray ( $result );
    echo "<tr><td>";
    print_r ( $queue );
    echo "</td></tr>";
}
echo "</table>";

echo "<title> \n";
echo "Постройки#Gebaeude\n";
echo "</title> \n";
echo "<script type=\"text/javascript\"> \n\n";
echo "function setMax(key, number){\n";
echo "    document.getElementsByName('fmenge['+key+']')[0].value=number;\n";
echo "}\n";
echo "</script> \n";

$unitab = LoadUniverse ( );
$speed = $unitab['speed'];

// ************************************************ Верфь ************************************************ 

$fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

if ( $_GET['mode'] === "Flotte" )
{
    // Проверить не строится ли Верфь или Фабрика нанитов.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['obj_id'] == 21 || $queue['obj_id'] == 15 ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>Невозможно строить ни корабли ни оборонительные сооружения, так как верфь либо фабрика нанитов усовершенствуются</font><br><br>";
    }
    if ( $GlobalUser['vacation'] ) {
        echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>";
    }
    echo "<form action=index.php?page=buildings&session=$session&mode=".$_GET['mode']." method=post>";
    echo "<table align=top><tr><td style='background-color:transparent;'>  <table width=530>          <tr> \n";
    echo "          <td class=l colspan=\"2\">Описание</td> \n";
    echo "          <td class=l><b>Кол-во</b></td> \n";
    echo "          </tr> \n\n";

    // Проверить есть ли Верфь на планете.
    if ( $aktplanet['b21'] ) {
        // Вывести объекты, которые можно построить на Верфи.
        foreach ( $fleetmap as $i => $id ) {
            if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) continue;

            echo "<tr>    			<td class=l>\n";
            echo "    			<a href=index.php?page=infos&session=$session&gid=$id>\n";
            echo "    			<img border='0' src=\"".UserSkin()."gebaeude/$id.gif\" align='top' width='120' height='120'>\n";
            echo "    			</a>\n";
            echo "    			</td>\n";
            echo "        <td class=l><a href=index.php?page=infos&session=$session&gid=$id>".loca("NAME_$id")."</a>";
            if ($aktplanet['f'.$id]) echo "</a> (в наличии ".$aktplanet['f'.$id].")";
            $m = $k = $d = $e = 0;
            ShipyardPrice ( $id, &$m, &$k, &$d, &$e );
            echo "<br>".loca("SHORT_$id")."<br>Стоимость:";
            if ($m) echo " Металл: <b>".nicenum($m)."</b>";
            if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
            if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
            if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
            $t = ShipyardDuration ( $id, $aktplanet['b21'], $aktplanet['b15'], $speed );
            echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k >";
            if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<input type=text name='fmenge[$id]' alt='".loca("NAME_$id")."' size=6 maxlength=6 value=0 tabindex=1> ";
            echo "</td></tr>";
        }

        // Кнопка строительства.
        echo "<td class=c colspan=2 align=center><input type=submit value=\"Строить\"></td></tr>";
    }
    else {
        if (!$busy) echo "<table><tr><td class=c>Для этого необходимо построить верфь!</td></tr></table>";
    }
}


// ************************************************ Оборона ************************************************ 

$defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );

if ( $_GET['mode'] === "Verteidigung" )
{
    // Проверить не строится ли Верфь или Фабрика нанитов.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['obj_id'] == 21 || $queue['obj_id'] == 15 ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>Невозможно строить ни корабли ни оборонительные сооружения, так как верфь либо фабрика нанитов усовершенствуются</font><br><br>";
    }
    if ( $GlobalUser['vacation'] ) {
        echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>";
    }
    echo "<form action=index.php?page=buildings&session=$session&mode=".$_GET['mode']." method=post>";
    echo "<table align=top><tr><td style='background-color:transparent;'>  <table width=530>          <tr> \n";
    echo "          <td class=l colspan=\"2\">Описание</td> \n";
    echo "          <td class=l><b>Кол-во</b></td> \n";
    echo "          </tr> \n\n";

    // Проверить есть ли Верфь на планете.
    if ( $aktplanet['b21'] ) {
        // Вывести объекты, которые можно построить на Верфи.
        foreach ( $defmap as $i => $id ) {
            if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) continue;

            echo "<tr>    			<td class=l>\n";
            echo "    			<a href=index.php?page=infos&session=$session&gid=$id>\n";
            echo "    			<img border='0' src=\"".UserSkin()."gebaeude/$id.gif\" align='top' width='120' height='120'>\n";
            echo "    			</a>\n";
            echo "    			</td>\n";
            echo "        <td class=l><a href=index.php?page=infos&session=$session&gid=$id>".loca("NAME_$id")."</a>";
            if ($aktplanet['d'.$id]) echo "</a> (в наличии ".$aktplanet['d'.$id].")";
            $m = $k = $d = $e = 0;
            ShipyardPrice ( $id, &$m, &$k, &$d, &$e );
            echo "<br>".loca("SHORT_$id")."<br>Стоимость:";
            if ($m) echo " Металл: <b>".nicenum($m)."</b>";
            if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
            if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
            if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
            $t = ShipyardDuration ( $id, $aktplanet['b21'], $aktplanet['b15'], $speed );
            echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k >";
            if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<input type=text name='fmenge[$id]' alt='".loca("NAME_$id")."' size=6 maxlength=6 value=0 tabindex=1> ";
            echo "</td></tr>";
        }
    
        // Кнопка строительства.
        echo "<td class=c colspan=2 align=center><input type=submit value=\"Строить\"></td></tr>";
    }
    else {
        if (!$busy) echo "<table><tr><td class=c>Для этого необходимо построить верфь!</td></tr></table>";
    }
}

// ************************************************ Исследования ************************************************ 

$resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );

if ( $_GET['mode'] === "Forschung" )
{
    // Проверить не строится ли Исследовательская лаборатория.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['obj_id'] == 31 ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>Проведение исследований невозможно, так как исследовательская лаборатория усовершенствуется.</font><br /><br />";
    }
    if ( $GlobalUser['vacation'] ) {
        echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>";
    }
    echo "<table align=top><tr><td style='background-color:transparent;'>  <table width=530>          <tr> \n";
    echo "          <td class=l colspan=\"2\">Описание</td> \n";
    echo "          <td class=l><b>Кол-во</b></td> \n";
    echo "          </tr> \n\n";

    // Проверить есть ли лаборатория на планете.
    if ( $aktplanet['b31'] ) {
        // Вывести список доступных исследований.
        foreach ( $resmap as $i => $id ) {
            if ( !ResearchMeetRequirement ($GlobalUser, $aktplanet, $id) ) continue;

            $reslab = ResearchNetwork ( $aktplanet['planet_id'], $id );

            $level = $GlobalUser['r'.$id]+1;
            echo "<tr>             <td class=l>\n";
            echo "                <a href=index.php?page=infos&session=$session&gid=$id>\n";
            echo "                <img border='0' src=\"".UserSkin()."gebaeude/$id.gif\" align='top' width='120' height='120'>\n";
            echo "                </a>\n";
            echo "                </td>\n";
            echo "        <td class=l><a href=index.php?page=infos&session=$session&gid=$id>".loca("NAME_$id")."</a>";
            if ($GlobalUser['r'.$id]) echo "</a> (уровень ".$GlobalUser['r'.$id].")";
            $m = $k = $d = $e = 0;
            ResearchPrice ( $id, $level, &$m, &$k, &$d, &$e );
            echo "<br>".loca("SHORT_$id")."<br>Стоимость:";
            if ($m) echo " Металл: <b>".nicenum($m)."</b>";
            if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
            if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
            if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
            $t = ResearchDuration ( $id, $level, $reslab, $speed );
            echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k>";
            if ($GlobalUser['r'.$id]) {
                if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo " <a href=index.php?page=buildings&session=$session&mode=Forschung&bau=$id><font color=#00FF00>Исследовать<br> уровень  $level</font></a>";
                else echo "<font color=#FF0000>Исследовать<br> уровень  $level</font>";
            }
            else {
                if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo " <a href=index.php?page=buildings&session=$session&mode=Forschung&bau=$id><font color=#00FF00> исследовать </font></a>";
                else echo "<font color=#FF0000> исследовать </font></a>";
            }
            echo "</td></tr>";
        }
    }
    else {
        if (!$busy) echo "<table><tr><td class=c>Для этого необходимо построить исследовательскую лабораторию!</td></tr></table>";
    }
}

// ***********************************************************************

echo "</table>";
if ( $_GET['mode'] === "Verteidigung" || $_GET['mode'] === "Flotte" ) echo "</form>";
echo "</table>\n";

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>