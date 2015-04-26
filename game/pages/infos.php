<?php

// Информация на постройки, флот, оборону и исследования. 
// Некоторые страницы (в частности постройки) содержат дополнительные сведения или элементы управления.

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "techlong", $GlobalUni['lang'] );
loca_add ( "jumpgate", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet']);
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

$fleetmap = array ( 215, 214, 213, 211, 210, 209, 208, 207, 206, 205, 204, 203, 202 );

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

$gid = intval($_GET['gid']);

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
    echo "<tr><th>Структура</th><th>".nicenum($UnitParam[$gid][0])."</th></tr>\n";
    echo "<tr><th>Мощность щита</th><th>".nicenum($UnitParam[$gid][1])."</th></tr>\n";
    echo "<tr><th>Оценка атаки</th><th>".nicenum($UnitParam[$gid][2])."</th></tr>\n";
    echo "<tr><th>Грузоподъёмность</th><th>".nicenum(FleetCargo($gid))."&nbsp;ед.</th></tr>\n";
    echo "<tr><th>Начальная скорость</th><th>".nicenum(FleetSpeed($gid, $GlobalUser['r115'], $GlobalUser['r117'], $GlobalUser['r118']))."</th></tr>\n";
    echo "<tr><th>Потребление топлива (дейтерий)</th><th>".nicenum(FleetCons($gid, $GlobalUser['r115'], $GlobalUser['r117'], $GlobalUser['r118']))."</th></tr>\n";
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
    echo "<tr><th>Структура</th><th>".nicenum($UnitParam[$gid][0])."</th></tr>\n";
    echo "<tr><th>Мощность щита</th><th>".nicenum($UnitParam[$gid][1])."</th></tr>\n";
    echo "<tr><th>Оценка атаки</th><th>".nicenum($UnitParam[$gid][2])."</th></tr>\n";
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
    else if ($gid == 2)    // Шахта кристалла
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Производство в час</td><td class='c'>Разница</td><td class='c'>Энергетический баланс</td><td class='c'>Разница</td> \n";
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
    else if ($gid == 3)    // Шахта дейтерия
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Производство в час</td><td class='c'>Разница</td><td class='c'>Энергетический баланс</td><td class='c'>Разница</td> \n";
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
    else if ($gid == 4)    // Солнечная электростанция
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Энергетический баланс</td><td class='c'>Разница</td>\n";
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
    else if ($gid == 12)    // Термоядерная электростанция
    {
        echo "<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>Энергетический баланс</td><td class='c'>Разница</td><td class='c'>Потребление дейтерия</td><td class='c'>Разница</td>\n";
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
    else if ( $gid == 34 )                                    // Склад альянса
    {
        $depot_cap = 10000 * pow ( 2, $aktplanet['b34'] );
        if ($aktplanet['b34']) $deut_avail = min(floor($aktplanet['d']), $depot_cap);
?>
    </th>
   </tr>
</table>
<form action="index.php?page=allianzdepot&session=<?=$session;?>" method=post>

<table width='519'>
<td class='c' colspan='2'>Вместимость: <?=$deut_avail;?>/<?=$depot_cap;?></td>
<?php

    $fmap = array_reverse ($fleetmap);

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
        echo "    <th>Флот ".$user['oname'].":<br>";
        $cons = 0;
        foreach ($fmap as $i=>$id) {
            $amount = $fleet_obj["ship".$id];
            if ($amount > 0) { 
                echo loca ("NAME_".$id).":".$amount."<br>";
                $cons += $amount * FleetCons ($id, $user['r115'], $user['r117'], $user['r118']) / 10;
            }
        }
        echo "</th>\n";
        echo "    <th>\n";
        echo "      зарядка<br>$load сек<br>\n";
        echo "      <input tabindex='".$c."' type='text' name='c".$c."' size='5' maxlength='2' value='0' />ч<br>\n\n";
        echo "         Стоимость ".ceil($cons)." / ч    </th>\n";
        echo "  </tr>\n";
        $c ++;
    }

?>
  <tr><th colspan='2'><input type='submit' value='Запустить ракету со снабжением'></th>
</table>

</form>
<?php
    }
    else if ( $gid == 44 && $aktplanet["b44"] > 0)        // Ракетная шахта
    {
        $rak_space = $aktplanet["b44"] * 10;
        if ( key_exists ( 'aktion', $_POST) )
        {
            $amount1 = min ( $aktplanet['d502'], intval ( $_POST['ab502'] ) );
            if ( $amount1 > 0) {
                $aktplanet['d502'] -= $amount1;
                $res = ShipyardPrice ( 502 );
                $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
                $points  = ( $m + $k + $d ) * $amount1;
                AdjustStats ( $aktplanet['owner_id'], $points, 0, 0, '-');
            }

            $amount2 = min ($aktplanet['d503'], intval ( $_POST['ab503'] ) );
            if ( $amount2 > 0) {
                $aktplanet['d503'] -= $amount2;
                $res = ShipyardPrice ( 503 );
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
Ваше хранилище может вмещать <?=$rak_space/2;?> межпланетных ракет или <?=$rak_space;?> ракет-перехватчиков.<br><table border=0> 

<?php
    if ( ($aktplanet['d502'] + $aktplanet['d503']) > 0 )  
    {
?>
<form action="index.php?page=infos&session=<?=$session;?>&gid=44"  method=post> 
<tr> 
 <td class=c>Тип</td><td class=c>Кол-во</td><td class=c>Снести</td> 
 <td class=c></td></tr> 
<?php
            if ($aktplanet['d502'] > 0) 
            {
?>
<tr><td class=c><?=loca("NAME_502");?></td><td class=c><?=$aktplanet['d502'];?></td><td class=c><input type=text name="ab502" size=2 value=""></td><td class=c></td></tr>
<?php
            }
?>
<?php
            if ($aktplanet['d503'] > 0) 
            {
?>
<tr><td class=c><?=loca("NAME_503");?></td><td class=c><?=$aktplanet['d503'];?></td><td class=c><input type=text name="ab503" size=2 value=""></td><td class=c></td></tr>
<?php
            }
?>
<tr><td class=c colspan=4><input type=submit name=aktion value="Выполнить"></table><p></form>
<?php
        }
    }
    else if ( $gid == 42 )        // Сенсорная фаланга
    {
?>
<tr><th><p><center><table border=1 ><tr><td class='c'>Уровень</td><td class='c'>радиус действия</td></tr>
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
    else if ( $gid == 43 && $aktplanet["b43"] > 0)        // Ворота
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
      <td>Луна-отправитель</td>
      <td><a href="index.php?page=galaxy&galaxy=<?=$aktplanet['g'];?>&system=<?=$aktplanet['s'];?>&position=<?=$aktplanet['p'];?>&session=<?=$session;?>" >[<?=$aktplanet['g'];?>:<?=$aktplanet['s'];?>:<?=$aktplanet['p'];?>]</a></td>
    </tr>
    <tr>
      <td>Целевая луна:</td>

      <td>
        <select name="zm">
<?php
    $result = EnumPlanets ();
    $rows = dbrows ($result);
    while ($rows--)
    {
        $planet = dbarray ($result);
        if ( $planet['planet_id'] == $aktplanet['planet_id'] ) continue;    // текущая луна
        if ( $planet["b43"] == 0 ) continue;    // нет ворот
        if ( $planet['type'] != 0 || $now < $planet['gate_until'] ) continue;
        echo "             <option value=\"".$planet['planet_id']."\">".$planet['name']." <a href=\"index.php?page=galaxy&galaxy=".$planet['g']."&system=".$planet['s']."&position=".$planet['p']."&session=$session\" >[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a></option>\n";
    }
?>
        </select>
      </td>
    </tr>
  </table>
  <table width="519">

    <tr>
      <td class="c" colspan="2">Использовать ворота: выбрать корабли</td>
    </tr>
<?php
    foreach ($fleetmap as $i=>$id)
    {
        $amount = $aktplanet["f$id"];
        if ($amount != 0)
        {
            echo "    <tr>\n";
            echo "      <th><a href=\"index.php?page=infos&session=$session&gid=$id\">".loca("NAME_$id")."</a> (".nicenum($amount)." в наличии)</th>\n";
            echo "      <th><input tabindex=\"1\" type=\"text\" name=\"c$id\" size=\"7\" maxlength=\"7\" value=\"0\"></th>\n";
            echo "    </tr>\n";
        }
    }
?>
    <tr> 
      <th colspan="2"><input type="submit" value="Выполнить прыжок" /></th>
    </tr> 
  </table>
</form>
<?php
        }
        else        // Ворота не готовы.
        {
            $delta = $aktplanet["gate_until"] - $now;
?>
    </th>
   </tr>
</table>
<center><font color=#FF0000>Ворота не готовы!<br>Следующий прыжок можно будет провести только через <?=date ('i\m\i\n s\s\e\c', $delta);?></font></center>
<?php
        }
    }

    echo "</table>\n";

    // Снос постройки.
    // Терраформер и лунную базу снести нельзя.
    // Ракетную шахту можно снести только если на планете нет ракет.

    if ( $aktplanet['b'.$gid] && !($gid == 33 || $gid == 41 || $gid == 44) ) {
        echo "<table width=519 >\n";
        echo "<tr><td class=c align=center><a href=\"index.php?page=b_building&session=$session&techid=$gid&modus=destroy&planet=".$aktplanet['planet_id']."\">Снести: ".loca("NAME_$gid")." Level ".$aktplanet['b'.$gid]." уничтожить?</a></td></tr>\n";
        $res = BuildPrice ( $gid, $aktplanet['b'.$gid]-1 );
        $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
        echo "<br><tr><th>Необходимо ";
        if ($m) echo "металла:<b>".nicenum($m)."</b> ";
        if ($k) echo "кристалла:<b>".nicenum($k)."</b> ";
        if ($d) echo "дейтерия:<b>".nicenum($d)."</b> ";
        $t = BuildDuration ( $gid, $aktplanet['b'.$gid]-1, $aktplanet['b14'], $aktplanet['b15'], $speed );
        echo "<tr><th><br>Продолжительность сноса:  ".BuildDurationFormat ( $t )."<br></th></tr></table>\n";
    }

    if ( $gid == 44 && $aktplanet['b'.$gid])    // Ракетная шахта
    {
        $raknum = $aktplanet['d502'] + $aktplanet['d503'];
        echo "<table width=519 >\n";
        if ( $raknum == 0 ) echo "<tr><td class=c align=center><a href=\"index.php?page=b_building&session=$session&techid=$gid&modus=destroy&planet=".$aktplanet['planet_id']."\">Снести: ".loca("NAME_$gid")." Level ".$aktplanet['b'.$gid]." уничтожить?</a></td></tr>\n";
        else echo "<tr><td class=c align=center>об оборонительных сооружениях</a></td></tr>";
        $res = BuildPrice ( $gid, $aktplanet['b'.$gid]-1 );
        $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
        echo "<br><tr><th>Необходимо ";
        if ($m) echo "металла:<b>".nicenum($m)."</b> ";
        if ($k) echo "кристалла:<b>".nicenum($k)."</b> ";
        if ($d) echo "дейтерия:<b>".nicenum($d)."</b> ";
        $t = BuildDuration ( $gid, $aktplanet['b'.$gid]-1, $aktplanet['b14'], $aktplanet['b15'], $speed );
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