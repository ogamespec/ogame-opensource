<?php

// Информация на постройки, флот, оборону и исследования.
// Некоторые страницы (в частности постройки) содержат дополнительные сведения или элементы управления.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet']);
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

// ***************************************************************************************

$unitab = LoadUniverse ( );
$speed = $unitab['speed'];
$drepair = $unitab['defrepair'];

function rgnum ($num)
{
    if ($num < 0) return "<font color=\"#FF0000\">".nicenum($num)."</font>";
    else if ($num > 0) return "<font color=\"#00FF00\">".nicenum($num)."</font>";
    else return nicenum($num);
}

function rapidIn ($gid, $n)
{
    return "<br/><a href=\"index.php?page=infos&session=".$_GET['session']."&gid=$gid\">".loca("NAME_$gid")."</a> одним залпом поражает <font color=\"red\">$n</font> единиц данного типа\n";
}

function rapidOut ($gid, $n)
{
    return "<br/>Одним залпом поражает: <a href=\"index.php?page=infos&session=".$_GET['session']."&gid=$gid\">".loca("NAME_$gid")."</a> - <font color=\"lime\">$n</font> единиц\n";
}

// Информация по скорострелу.
function rapid ($gid)
{
    global $RapidFire;
    $res = "";
    for ($n=202; $n<=215; $n++) if ( $RapidFire[$gid][$n] > 1 ) $res .= rapidOut ( $n, $RapidFire[$gid][$n] );
    for ($n=401; $n<=408; $n++) if ( $RapidFire[$gid][$n] > 1 ) $res .= rapidOut ( $n, $RapidFire[$gid][$n] );
    for ($n=202; $n<=215; $n++) if ( $RapidFire[$n][$gid] > 1 ) $res .= rapidIn ( $n, $RapidFire[$n][$gid] );
    for ($n=401; $n<=408; $n++) if ( $RapidFire[$n][$gid] > 1 ) $res .= rapidIn ( $n, $RapidFire[$n][$gid] );
    return $res;
}

$gid = $_GET['gid'];

PageHeader ("infos");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";
echo "<table width=\"519\">\n";

if ($gid > 200 && $gid < 300)    // Флот
{
    echo "<!-- begin fleet or defense information -->\n";
    echo "<tr><td class=\"c\" colspan=\"2\">Информация      о флоте:</td></tr>\n";
    echo "<tr><th>Название</th><th>".loca("NAME_$gid")."</th></tr>\n";
    echo "<tr><th colspan=\"2\">\n";
    echo "<table border=\"0\">\n";
    echo "<tr><td valign=\"top\"><img border=\"0\" src=\"".UserSkin()."gebaeude/$gid.gif\" width=\"120\" height=\"120\"></td>\n";
    echo "<td>".loca("LONG_$gid")."<br/>".rapid($gid)."</td>\n";
    echo "</tr></table></th></tr>\n";
    echo "<tr><th>Структура</th><th>110.000</th></tr>\n";
    echo "<tr><th>Мощность щита</th><th>500</th></tr>\n";
    echo "<tr><th>Оценка атаки</th><th>2.000</th></tr>\n";
    echo "<tr><th>Грузоподъёмность</th><th>2.000&nbsp;ед.</th></tr>\n";
    echo "<tr><th>Начальная скорость</th><th>5.000</th></tr>\n";
    echo "<tr><th>Потребление топлива (дейтерий)</th><th>1.000</th></tr>\n";
    echo "</table></th></tr></table>\n";
}
else if ($gid > 400 && $gid < 500)    // Оборона.
{
    echo "<!-- begin fleet or defense information -->\n";
    echo "<tr><td class=\"c\" colspan=\"2\">Информация      об оборонительных сооружениях:</td></tr>\n";
    echo "<tr><th>Название</th><th>".loca("NAME_$gid")."</th></tr>\n";
    echo "<tr><th colspan=\"2\">\n";
    echo "<table border=\"0\">\n";
    echo "<tr><td valign=\"top\"><img border=\"0\" src=\"".UserSkin()."gebaeude/$gid.gif\" width=\"120\" height=\"120\"></td>\n";
    echo "<td>".loca("LONG_$gid")."<br/>".rapid($gid)."</td>\n";
    echo "</tr></table></th></tr>\n";
    echo "<tr><th>Структура</th><th>2.000</th></tr>\n";
    echo "<tr><th>Мощность щита</th><th>20</th></tr>\n";
    echo "<tr><th>Оценка атаки</th><th>80</th></tr>\n";
    echo "</th></tr></table>\n";
}
else if ($gid > 100 && $gid < 200)    // Исследования.
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

    // Дополнительная информация и кнопки.

    if ($gid == 1)    // Шахта металла
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Производство в час</td><td class='c'>Разница</td><td class='c'>Энергетический баланс</td><td class='c'>Разница</td> \n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_metal ($aktplanet['b'.$gid], $aktplanet['mprod'] );
        $cons_now = -cons_metal ($aktplanet['b'.$gid]);
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_metal ($i, $aktplanet['mprod'] );
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
    else if ($gid == 2)    // Шахта кристалла
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Производство в час</td><td class='c'>Разница</td><td class='c'>Энергетический баланс</td><td class='c'>Разница</td> \n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_crys ($aktplanet['b'.$gid], $aktplanet['kprod'] );
        $cons_now = -cons_crys ($aktplanet['b'.$gid]);
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_crys ($i, $aktplanet['kprod'] );
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
    else if ($gid == 3)    // Шахта дейтерия
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Производство в час</td><td class='c'>Разница</td><td class='c'>Энергетический баланс</td><td class='c'>Разница</td> \n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_deut ($aktplanet['b'.$gid], $aktplanet['temp']+40, $aktplanet['dprod'] );
        $cons_now = -cons_deut ($aktplanet['b'.$gid]);
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_deut ($i, $aktplanet['temp']+40, $aktplanet['dprod'] );
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
    else if ($gid == 4)    // Солнечная электростанция
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Энергетический баланс</td><td class='c'>Разница</td>\n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_solar ($aktplanet['b'.$gid], $aktplanet['sprod'] );
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_solar ($i, $aktplanet['sprod'] );

            if ($i==$aktplanet['b'.$gid]) echo "<tr> <th> <font color=#FF0000>$i</font></th> ";
            else echo "<tr> <th> $i</th> ";
            echo "<th> " . nicenum($prod). "</th> ";
            echo "<th> " . rgnum($prod-$prod_now) . "</th> </tr> \n";
        }
        echo "</table></center></tr></th>";
    }
    else if ($gid == 12)    // Термоядерная электростанция
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Энергетический баланс</td><td class='c'>Разница</td><td class='c'>Потребление дейтерия</td><td class='c'>Разница</td>\n";
        $level = $aktplanet['b'.$gid]-2;
        if ($level <= 0) $level = 1;
        $prod_now = prod_fusion ($aktplanet['b'.$gid], $GlobalUser['r113'], $aktplanet['fprod'] );
        $cons_now = -cons_fusion ($aktplanet['b'.$gid], $aktplanet['fprod'] );
        for ($i=$level; $i<$level+15; $i++) {
            $prod = prod_fusion ($i, $GlobalUser['r113'], $aktplanet['fprod'] );
            $cons = -cons_fusion ($i, $aktplanet['fprod'] );

            if ($i==$aktplanet['b'.$gid]) echo "<tr> <th> <font color=#FF0000>$i</font></th> ";
            else echo "<tr> <th> $i</th> ";
            echo "<th> " . nicenum($prod). "</th> ";
            echo "<th> " . rgnum($prod-$prod_now) . "</th> \n";
            echo "<th> " . nicenum($cons). "</th> ";
            echo "<th> " . rgnum($cons-$cons_now) . "</th> </tr> \n";
        }
        echo "</table></center></tr></th>";
    }
    else if ($gid == 22 || $gid == 23 || $gid == 24 )     // Хранилища
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Вместимость</td><td class='c'>Разница</td></tr>\n";
        $level = $aktplanet['b'.$gid];
        $cap_now = store_capacity ( $aktplanet['b'.$gid] ) / 1000;
        for ($i=$level; $i<$level+15; $i++) {
            $cap = store_capacity ( $i ) / 1000;
            if ($i == $aktplanet['b'.$gid]) echo "<tr> <th> <font color=#FF0000>$i</font></th> <th>".nicenum($cap)." k</th> <th>0</th> </tr>\n";
            else echo "<tr> <th> $i</th> <th>".nicenum($cap)." k</th> <th> <font color=\"#00FF00\">".nicenum($cap-$cap_now)." k</font></th> </tr>\n";
        }
        echo "</table>";
    }

    echo "</table>\n";

    // Снос постройки.

    if ( $aktplanet['b'.$gid] ) {
        echo "<table width=519 >\n";
        echo "<tr><td class=c align=center><a href=\"index.php?page=b_building&session=$session&techid=$gid&modus=destroy&planet=".$aktplanet['planet_id']."\">Снести: ".loca("NAME_$gid")." Level ".$aktplanet['b'.$gid]." уничтожить?</a></td></tr>\n";
        $m = $k = $d = $e = 0;
        BuildPrice ( $gid, $aktplanet['b'.$gid]-2, &$m, &$k, &$d, &$e );
        echo "<br><tr><th>Необходимо ";
        if ($m) echo "металла:<b>".nicenum($m)."</b> ";
        if ($k) echo "кристалла:<b>".nicenum($k)."</b> ";
        if ($d) echo "дейтерия:<b>".nicenum($d)."</b> ";
        $t = BuildDuration ( $gid, $aktplanet['b'.$gid]-2, $aktplanet['b14'], $aktplanet['b15'], $speed );
        echo "<tr><th><br>Продолжительность сноса:  ".BuildDurationFormat ( $t )."<br></th></tr></table>\n";
    }
}

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>