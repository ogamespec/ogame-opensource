<?php

// Верфь, Оборона и Исследования.

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "techshort", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval ($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

// Обработка POST-запросов.
if ( method () === "POST" && !$GlobalUser['vacation'] )
{
    foreach ( $_POST['fmenge'] as $gid=>$value )
    {
        $result = GetShipyardQueue ( $aktplanet['planet_id'] );    // Ограничить количество заказов на верфи.
        if ( dbrows ($result)  >= 99 ) $value = 0;

        if ( $value < 0 ) $value = 0;
        if ( $value > 0 ) {
            // Рассчитать количество (не больше, чем ресурсов на планете и не больше 999)
            if ( $value > 999 ) $value = 999;

            $res = ShipyardPrice ( $gid );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];

            if ( $aktplanet['m'] < $m || $aktplanet['k'] < $k || $aktplanet['d'] < $d ) continue;    // недостаточно ресурсов для одной единицы

            // Купола.
            if ( $gid == 407 || $gid == 408 ) $value = 1;

            // Ограничить количество ракет вместимостью шахты.
            $free_space = $aktplanet['b44'] * 10 - ($aktplanet['d502'] + 2 * $aktplanet['d503']);
            if ( $gid == 502 ) $value = min ( $free_space, $value );
            if ( $gid == 503 ) $value = min ( floor ($free_space / 2), $value );
            
            if ($m) $cm = floor ($aktplanet['m'] / $m);
            else $cm = 1000;
            if ($k) $ck = floor ($aktplanet['k'] / $k);
            else $ck = 1000;
            if ($d) $cd = floor ($aktplanet['d'] / $d);
            else $cd = 1000;
            $v = min ( $cm, min ($ck, $cd) );
            if ( $value > $v ) $value = $v;

            AddShipyard ( $GlobalUser['player_id'], $aktplanet['planet_id'], intval ($gid), intval ($value) );
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
			if ( key_exists ( 'bau', $_GET ) ) StartResearch ( $GlobalUser['player_id'], $aktplanet['planet_id'], intval ($_GET['bau']), $now );
                  $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );    // обновить состояние планеты.
		}
		else	// Ведется исследования (отменить)
		{
			if ( key_exists ( 'unbau', $_GET ) ) StopResearch ( $GlobalUser['player_id'] );
                  $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );    // обновить состояние планеты.
		}
	}
}

PageHeader ("buildings");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

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
    $prem = PremiumStatus ($GlobalUser);

    // Проверить не строится ли Верфь или Фабрика нанитов.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['tech_id'] == 21 || $queue['tech_id'] == 15 ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>Невозможно строить ни корабли ни оборонительные сооружения, так как верфь либо фабрика нанитов усовершенствуются</font><br><br>";
    }
    if ( $GlobalUser['vacation'] ) {
        echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>";
    }
    echo "<form action=index.php?page=buildings&session=$session&mode=".$_GET['mode']." method=post>";
    echo "<table align=top><tr><td style='background-color:transparent;'>  ";
    if ( $GlobalUser['useskin'] ) echo "<table width=\"530\">\n";
    else echo "<table width=\"468\">\n";
    echo "         <tr> \n";
    echo "          <td class=l colspan=\"2\">Описание</td> \n";
    echo "          <td class=l><b>Кол-во</b></td> \n";
    echo "          </tr> \n\n";

    // Проверить есть ли Верфь на планете.
    if ( $aktplanet['b21'] ) {
        // Вывести объекты, которые можно построить на Верфи.
        foreach ( $fleetmap as $i => $id ) {
            if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) )
            {
                if ($aktplanet['f'.$id] <= 0) continue;
            }

            echo "<tr>    			";
            if ( $GlobalUser['useskin'] ) {
                echo "                <td class=l>\n";
                echo "    			<a href=index.php?page=infos&session=$session&gid=$id>\n";
                echo "    			<img border='0' src=\"".UserSkin()."gebaeude/$id.gif\" align='top' width='120' height='120'>\n";
                echo "    			</a>\n";
                echo "    			</td>\n";
                echo "        <td class=l >";
            }
            else echo "        <td class=l colspan=2>";
            echo "<a href=index.php?page=infos&session=$session&gid=$id>".loca("NAME_$id")."</a>";
            if ($aktplanet['f'.$id]) echo "</a> (в наличии ".$aktplanet['f'.$id].")";
            $res = ShipyardPrice ( $id );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            echo "<br>".loca("SHORT_$id")."<br>Стоимость:";
            if ($m) echo " Металл: <b>".nicenum($m)."</b>";
            if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
            if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
            if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
            $t = ShipyardDuration ( $id, $aktplanet['b21'], $aktplanet['b15'], $speed );
            echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k >";
            if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) echo "<font color=#FF0000>невозможно</font>";
            else if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e ) && !$busy) {
                echo "<input type=text name='fmenge[$id]' alt='".loca("NAME_$id")."' size=6 maxlength=6 value=0 tabindex=1> ";
                if ( $prem['commander'] ) {
                    $max = 999;
                    if ( $m ) $max = floor (min ($max, $aktplanet['m'] / $m));
                    if ( $k ) $max = floor (min ($max, $aktplanet['k'] / $k));
                    if ( $d ) $max = floor (min ($max, $aktplanet['d'] / $d));
                    echo "<br><a href=\"javascript:setMax($id, $max);\">(max. $max)</a>";
                }
            }
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
    $prem = PremiumStatus ($GlobalUser);

    // Проверить не строится ли Верфь или Фабрика нанитов.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['tech_id'] == 21 || $queue['tech_id'] == 15 ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>Невозможно строить ни корабли ни оборонительные сооружения, так как верфь либо фабрика нанитов усовершенствуются</font><br><br>";
    }
    if ( $GlobalUser['vacation'] ) {
        echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>";
    }
    echo "<form action=index.php?page=buildings&session=$session&mode=".$_GET['mode']." method=post>";
    echo "<table align=top><tr><td style='background-color:transparent;'>  ";
    if ( $GlobalUser['useskin'] ) echo "<table width=\"530\">\n";
    else echo "<table width=\"468\">\n";
    echo "          <tr> \n";
    echo "          <td class=l colspan=\"2\">Описание</td> \n";
    echo "          <td class=l><b>Кол-во</b></td> \n";
    echo "          </tr> \n\n";

    // Проверить есть ли Верфь на планете.
    if ( $aktplanet['b21'] ) {
        // Вывести объекты, которые можно построить на Верфи.
        foreach ( $defmap as $i => $id ) {
            if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) )
            {
                if($aktplanet['d'.$id] == 0) continue;
            }

            echo "<tr>    			";
            if ( $GlobalUser['useskin'] ) {
                echo "                <td class=l>\n";
                echo "    			<a href=index.php?page=infos&session=$session&gid=$id>\n";
                echo "    			<img border='0' src=\"".UserSkin()."gebaeude/$id.gif\" align='top' width='120' height='120'>\n";
                echo "    			</a>\n";
                echo "    			</td>\n";
                echo "        <td class=l >";
            }
            else echo "        <td class=l colspan=2>";
            echo "<a href=index.php?page=infos&session=$session&gid=$id>".loca("NAME_$id")."</a>";
            if ($aktplanet['d'.$id]) echo "</a> (в наличии ".$aktplanet['d'.$id].")";
            $res = ShipyardPrice ( $id );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            echo "<br>".loca("SHORT_$id")."<br>Стоимость:";
            if ($m) echo " Металл: <b>".nicenum($m)."</b>";
            if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
            if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
            if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
            $t = ShipyardDuration ( $id, $aktplanet['b21'], $aktplanet['b15'], $speed );
            echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k >";
            if ( !$busy ) {
                if ( ($id == 407 || $id == 408) && $aktplanet['d'.$id] > 0 ) echo "<font color=#FF0000>Щитовой купол можно строить только 1 раз.</font>";
                else if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) echo "<font color=#FF0000>невозможно</font>";
                else if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e ) ) {
                    echo "<input type=text name='fmenge[$id]' alt='".loca("NAME_$id")."' size=6 maxlength=6 value=0 tabindex=1> ";
                    if ( $prem['commander'] && !( $id == 407 || $id == 408 ) ) {
                        if ( $id == 502 ) $max = $aktplanet['b44'] * 10 - (2*$aktplanet['d503'] + $aktplanet['d502']);
                        else if ( $id == 503 ) $max = ($aktplanet['b44'] * 10 - (2*$aktplanet['d503'] + $aktplanet['d502'])) / 2;
                        else $max = 999;
                        if ( $m ) $max = floor (min ($max, $aktplanet['m'] / $m));
                        if ( $k ) $max = floor (min ($max, $aktplanet['k'] / $k));
                        if ( $d ) $max = floor (min ($max, $aktplanet['d'] / $d));
                        echo "<br><a href=\"javascript:setMax($id, $max);\">(max. $max)</a>";
                    }
                }
            }
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
    $prem = PremiumStatus ($GlobalUser);
    if ( $prem['technocrat'] ) $r_factor = 1.1;
    else $r_factor = 1.0;

    // Исследовательская лаборатория усовершенствуется хоть на одной планете ?
    $query = "SELECT * FROM ".$db_prefix."queue WHERE obj_id = 31 AND (type = 'Build' OR type = 'Demolish') AND start < $now AND owner_id = " . $GlobalUser['player_id'];
    $result = dbquery ( $query );
    $busy = ( dbrows ($result) > 0 );

    // Проверить ведется ли исследование.
    $res = GetResearchQueue ( $GlobalUser['player_id'] );
    $resq = dbarray ($res);
    $operating =  ( $resq != null );

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>Проведение исследований невозможно, так как исследовательская лаборатория усовершенствуется.</font><br /><br />";
    }
    if ( $GlobalUser['vacation'] ) {
        echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>";
    }
    echo "<table align=top><tr><td style='background-color:transparent;'>  ";
    if ( $GlobalUser['useskin'] ) echo "<table width=\"530\">\n";
    else echo "<table width=\"468\">\n";
    echo "          <tr> \n";
    echo "          <td class=l colspan=\"2\">Описание</td> \n";
    echo "          <td class=l><b>Кол-во</b></td> \n";
    echo "          </tr> \n\n";

    // Проверить есть ли лаборатория на планете.
    if ( $aktplanet['b31'] ) {
        // Вывести список доступных исследований.
        foreach ( $resmap as $i => $id ) {
            if ( ! ResearchMeetRequirement ($GlobalUser, $aktplanet, $id) ) continue;

            $reslab = ResearchNetwork ( $aktplanet['planet_id'], $id );

            $level = $GlobalUser['r'.$id]+1;
            echo "<tr>             ";
            if ( $GlobalUser['useskin'] ) {
                echo "                <td class=l>\n";
                echo "    			<a href=index.php?page=infos&session=$session&gid=$id>\n";
                echo "    			<img border='0' src=\"".UserSkin()."gebaeude/$id.gif\" align='top' width='120' height='120'>\n";
                echo "    			</a>\n";
                echo "    			</td>\n";
                echo "        <td class=l >";
            }
            else echo "        <td class=l colspan=2>";
            echo "<a href=index.php?page=infos&session=$session&gid=$id>".loca("NAME_$id")."</a>";
            if ($GlobalUser['r'.$id]) echo "</a> (уровень ".$GlobalUser['r'.$id];
            if ( $id == 106 && $prem['technocrat'] ) { 
                echo " <b><font style=\"color:lime;\">+2</font></b> <img border=\"0\" src=\"img/technokrat_ikon.gif\" alt=\"Технократ\" onmouseover=\"return overlib('<font color=white>Технократ</font>', WIDTH, 100);\" onmouseout='return nd();' width=\"20\" height=\"20\" style=\"vertical-align:middle;\"> ";
            }
            if ($GlobalUser['r'.$id]) echo ")";
            $res = ResearchPrice ( $id, $level );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            echo "<br>".loca("SHORT_$id")."<br>Стоимость:";
            if ($m) echo " Металл: <b>".nicenum($m)."</b>";
            if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
            if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
            if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
            $t = ResearchDuration ( $id, $level, $reslab, $speed * $r_factor );
            echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k>";
            if ( $operating )        // Исследование проводится
            {
                if ( $id == $resq['obj_id'] )
                {
?>
                <div id="bxx" class="z"></div>
                <script   type="text/javascript">
                v=new Date();
                var bxx=document.getElementById('bxx');
                function t(){
                    n=new Date();
                    ss=<?=($resq['end'] - time());?>;
                    s=ss-Math.round((n.getTime()-v.getTime())/1000.);
                    m=0;h=0;
                    if(s<0){
    
                        bxx.innerHTML='Окончено<br><a href=index.php?page=buildings&session=<?=$session;?>&mode=Forschung&cp=<?=$aktplanet['planet_id'];?> >дальше</a>';
                    }else{
                        if(s>59){
                            m=Math.floor(s/60);
                            s=s-m*60
                        }
                        if(m>59){
                            h=Math.floor(m/60);
                            m=m-h*60
                        }
                        if(s<10){
                            s="0"+s
                        }
                        if(m<10){
                            m="0"+m
                        }
                        bxx.innerHTML=h+":"+m+":"+s+"<br><a href=index.php?page=buildings&session=<?=$session;?>&unbau=<?=$id;?>&mode=Forschung&cp=<?=$resq['sub_id'];?>"+
                        <?php
                    if ( $aktplanet['planet_id'] == $resq['sub_id'] )  echo "\">Отменить</a>\"";   ?>                }
                    ;
                    window.setTimeout("t();",999);
                }
                window.onload=t;
                </script>
<?php
                }
                else echo " - ";
            }
            else        // Исследование не проводится.
            {
                if ($GlobalUser['r'.$id]) {
                    if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e ) ) echo " <a href=index.php?page=buildings&session=$session&mode=Forschung&bau=$id><font color=#00FF00>Исследовать<br> уровень  $level</font></a>";
                    else echo "<font color=#FF0000>Исследовать<br> уровень  $level</font>";
                }
                else {
                    if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e ) ) echo " <a href=index.php?page=buildings&session=$session&mode=Forschung&bau=$id><font color=#00FF00> исследовать </font></a>";
                    else echo "<font color=#FF0000> исследовать </font></a>";
                }
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

if ( $_GET['mode'] === "Verteidigung" || $_GET['mode'] === "Flotte" )
{
    $result = GetShipyardQueue ($aktplanet['planet_id']);
    $rows = dbrows ($result);
    if ($rows)
    {
        $first = true;
        $c = "";
        $b = "";
        $a = "";
        $total_time = 0;
        while ($rows--)
        {
            $queue = dbarray ($result);
            if ( $first ) {
                $g = $now - $queue['start'];
                $first = false;
            }
            $c .= ($queue['end'] - $queue['start']) . ",";
            $b .= "\"".loca("NAME_".$queue['obj_id'])."\",";
            $a .= "\"".$queue['level']."\",";
            $total_time += ($queue['end'] - $queue['start']) * $queue['level'];
        }
        $total_time -= $g;
?>

      <br>Сейчас производится: <div id="bx" class="z"></div>

<!-- JAVASCRIPT -->
<script  type="text/javascript">
v = new Date();
p = 0;
g = <?=$g;?>;
s = 0;
hs = 0;
of = 1;
c = new Array(<?=$c;?>"");
b = new Array(<?=$b;?>"");
a = new Array(<?=$a;?>"");
aa = "Задания выполнены";


function t() {
    if (hs == 0) {
        xd();
        hs = 1;
    }
    n = new Date();
    s = c[p]-g-Math.round((n.getTime()-v.getTime())/1000.);
    s = Math.round(s);
    m = 0;
    h = 0;
    if (s < 0) {
        a[p]--;
        xd();
        if (a[p] <= 0) {
            p++;
            xd();
        }
        g = 0;
        v = new Date();
        s=0;
    }
    if (s > 59) {
        m = Math.floor(s / 60);
        s = s - m * 60;
    }
    if (m > 59) {
        h = Math.floor(m / 60);
        m = m - h * 60;
    }
    if (s < 10) {
        s = "0" + s;
    }
    if (m < 10) {
        m = "0" + m;
    }
    if (p > b.length - 2) {
        document.getElementById("bx").innerHTML=aa ;
    } else {
        document.getElementById("bx").innerHTML=b[p]+" "+h+":"+m+":"+s;
    }
    window.setTimeout("t();", 200);
}




function xd() {
    while (document.Atr.auftr.length > 0) {
        document.Atr.auftr.options[document.Atr.auftr.length-1] = null;
    }
    if (p > b.length - 2) {
        document.Atr.auftr.options[document.Atr.auftr.length] = new Option(aa);
    }
    for (iv = p; iv <= b.length - 2; iv++) {
        if (a[iv] < 2) {
            ae=" ";
        }else{
            ae=" ";
        }
        if (iv == p) {
            act = " (производится)";
        }else{
            act = "";
        }
        document.Atr.auftr.options[document.Atr.auftr.length] = new Option(a[iv]+ae+" \""+b[iv]+"\""+act, iv + of);
    }
}

window.onload = t;
</script>
<!-- JAVASCRIPT ENDE-->


<br>
<form name="Atr" method="get" action="index.php?page=buildings">
<input type="hidden" name="session" value="<?=$session;?>">
<input type="hidden" name="mode" value="Flotte">
<table width="530">

 <tr>
    <td class="c" >Ожидаемые поручения</td>
 </tr>
 <tr>
  <th ><select name="auftr" size="10"></select></th>
   </tr>
 <tr>
  <td class="c" ></td>

 </tr>
</table>
</form>
Всё производство займёт

  <?=BuildDurationFormat ($total_time); ?><br>
<?php
    }
}

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>