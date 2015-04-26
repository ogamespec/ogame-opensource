<?php

// Строительство построек.

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "techshort", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ( $GlobalUser['player_id'], intval ($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

// Обработка параметров.
if ( key_exists ('modus', $_GET) && !$GlobalUser['vacation'] )
{
    if ( $_GET['modus'] === 'add' ) BuildEnque ( intval ($_GET['planet']), intval ($_GET['techid']), 0 );
    else if ( $_GET['modus'] === 'destroy' ) BuildEnque ( intval ($_GET['planet']), intval ($_GET['techid']), 1 );
    else if ( $_GET['modus'] === 'remove' ) BuildDeque ( intval ($_GET['planet']), intval ($_GET['listid']) );
}

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];
$prem = PremiumStatus ($GlobalUser);

$unitab = LoadUniverse ( );
$speed = $unitab['speed'];

PageHeader ("b_building");

$buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

?>
<script type="text/javascript">
<!--
function t() {
	v = new Date();
	var bxx = document.getElementById('bxx');
	var timeout = 1;
	n=new Date();
	ss=pp;
	aa=Math.round((n.getTime()-v.getTime())/1000.);
	s=ss-aa;
	m=0;
	h=0;
	
	if ((ss + 3) < aa) {
	  bxx.innerHTML="Окончено<br>"+"<a href=index.php?page=b_building&session="+ps+"&planet="+pl+">Дальше</a>";
	  
	  if ((ss + 6) >= aa) {	    
	  	window.setTimeout('document.location.href="index.php?page=b_building&session='+ps+'&planet='+pl+'";', 3500);
  	  }
	} else {
	if(s < 0) {
	    if (1) {
			bxx.innerHTML="Окончено<br>"+"<a href=index.php?page=b_building&session="+ps+"&planet="+pl+">Дальше</a>";
			window.setTimeout('document.location.href="index.php?page=b_building&session='+ps+'&planet='+pl+'";', 2000);
		} else {
			timeout = 0;
			bxx.innerHTML="Окончено<br>"+"<a href=index.php?page=b_building&session="+ps+"&planet="+pl+">Дальше</a>";
		}
	} else {
		if(s>59){
			m=Math.floor(s/60);
			s=s-m*60;
		}
        if(m>59){
        	h=Math.floor(m/60);
        	m=m-h*60;
        }
        if(s<10){
        	s="0"+s;
        }
        if(m<10){
        	m="0"+m;
        }
        
        if (1) {
        	bxx.innerHTML=h+":"+m+":"+s+"<br><a href=index.php?page=b_building&session="+ps+"&listid="+pk+"&modus="+pm+"&planet="+pl+">Отменить</a>";
    	} else {
    		bxx.innerHTML=h+":"+m+":"+s+"<br><a href=index.php?page=b_building&session="+ps+"&listid="+pk+"&modus="+pm+"&planet="+pl+">Отменить</a>";
    	}
	}    
	pp=pp-1;
	if (timeout == 1) {
    	window.setTimeout("t();", 999);
    }
    }
}
//-->
</script>

<?php

if ( $GlobalUser['vacation'] ) {
    echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>\n\n";
}

echo "<table align=top ><tr><td style='background-color:transparent;'>\n";
if ( $GlobalUser['useskin'] ) echo "<table width=\"530\">\n";
else echo "<table width=\"468\">\n";

// Проверить ведется ли исследование или нет.
$result = GetResearchQueue ( $GlobalUser['player_id'] );
$resqueue = dbarray ($result);
$reslab_operating = ($resqueue != null);

// Проверить ведется ли постройка на верфи.
$result = GetShipyardQueue ( $aktplanet['planet_id'] );
$shipqueue = dbarray ($result);
$shipyard_operating = ($shipqueue != null);

// Вывести очередь построек (если активен Командир)
$result = GetBuildQueue ( $aktplanet['planet_id'] );
$cnt = dbrows ( $result );
for ( $i=0; $i<$cnt; $i++ )
{
    $queue = dbarray ($result);
    if ($i == 0) $queue0 = $queue;
    if ( $prem['commander'] )
    {
        if ( $queue['destroy'] ) $queue['level']++;
        echo "<tr><td class=\"l\" colspan=\"2\">".($i+1).".: ".loca("NAME_".$queue['tech_id']);
        if ($queue['level'] > 0) echo " , уровень ".$queue['level'];
        if ( $queue['destroy'] ) echo "\n Снести";
        if ($i==0) {
            echo "<td class=\"k\"><div id=\"bxx\" class=\"z\"></div><SCRIPT language=JavaScript>\n";
            echo "                  pp=\"".($queue['end']-$now)."\"\n";
            echo "                  pk=\"".$queue['list_id']."\"\n";
            echo "                  pm=\"remove\"\n";
            echo "                  pl=\"".$aktplanet['planet_id']."\"\n";
            echo "                  ps=\"$session\"\n";
            echo "                  t();\n";
            echo "                  </script></tr>\n";
        }
        else {
            echo "<td class=\"k\"><font color=\"red\"><a href=\"index.php?page=b_building&session=$session&modus=remove&listid=".$queue['list_id']."&planet=".$aktplanet['planet_id']."\">удалить</a></font></td></td></tr>\n";
        }
    }
}

foreach ( $buildmap as $i => $id )
{
    $lvl = $aktplanet['b'.$id];
    if ( ! BuildMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) continue;

    echo "<tr>";

    if ( $GlobalUser['useskin'] ) {
        echo "<td class=l>";
        echo "<a href=index.php?page=infos&session=$session&gid=".$id.">";
        echo "<img border='0' src=\"".UserSkin()."gebaeude/".$id.".gif\" align='top' width='120' height='120'></a></td>";
    }

    echo "<td class=l>";
    echo "<a href=index.php?page=infos&session=$session&gid=".$id.">".loca("NAME_$id")."</a></a>";
    if ( $lvl ) echo " (уровень ".$lvl.")";
    echo "<br>". loca("SHORT_$id");
    $res = BuildPrice ( $id, $lvl+1 );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    echo "<br>Стоимость:";
    if ($m) echo " Металл: <b>".nicenum($m)."</b>";
    if ($k) echo " Кристалл: <b>".nicenum($k)."</b>";
    if ($d) echo " Дейтерий: <b>".nicenum($d)."</b>";
    if ($e) echo " Энергия: <b>".nicenum($e)."</b>";
    $t = BuildDuration ( $id, $lvl+1, $aktplanet['b14'], $aktplanet['b15'], $speed );
    echo "<br>Длительность: ".BuildDurationFormat ( $t )."<br>";

    if ( $prem['commander'] ) {
        if ( $cnt ) {
            if ( $cnt < 5) echo "<td class=k><a href=\"index.php?page=b_building&session=$session&modus=add&techid=$id&planet=".$aktplanet['planet_id']."\">В очередь на строительство</a></td>";
            else echo "<td class=k>";
        }
        else
        {
                  if ( $aktplanet['fields'] >= $aktplanet['maxfields'] ) {
                        echo "<td class=l><font color=#FF0000>Нет места! </font>";
                  }
                  else if ( $id == 31 && $reslab_operating ) {
				echo "<td class=l><font  color=#FF0000>В процессе</font> <br>";
			}
			else if ( ($id == 15 || $id == 21 ) && $shipyard_operating ) {
				echo "<td class=l><font  color=#FF0000>В процессе</font> <br>";
			}
   			else if ( $lvl == 0 )
      		{
        		if ( IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<td class=l><a href='index.php?page=b_building&session=$session&modus=add&techid=".$id."&planet=".$aktplanet['planet_id']."'><font color=#00FF00> строить </font></a>\n";
          		else echo "<td class=l><font color=#FF0000> строить </font>\n";
			}
   			else
      		{
        		if ( IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<td class=l><a href='index.php?page=b_building&session=$session&modus=add&techid=".$id."&planet=".$aktplanet['planet_id']."'><font color=#00FF00>Совершенствовать <br> до уровня  ".($lvl+1)."</font></a>\n";
          		else echo "<td class=l><font color=#FF0000>Совершенствовать <br> до уровня  ".($lvl+1)."</font>";
			}
        }
    }
    else {
        if ( $cnt ) {
            if ( $queue0['tech_id'] == $id )
            {
                $left = $queue0['end'] - time ();
                echo "<td class=k><div id=\"bxx\" class=\"z\"></div><SCRIPT language=JavaScript>pp='".$left."'; pk='1'; pm='remove'; pl='".$aktplanet['planet_id']."'; ps='".$_GET['session']."'; t();</script>\n";
            }
            else echo "<td class=k>";
        }
        else {
                  if ( $aktplanet['fields'] >= $aktplanet['maxfields'] ) {
                        echo "<td class=l><font color=#FF0000>Нет места! </font>";
                  }
			else if ( $id == 31 && $reslab_operating ) {
				echo "<td class=l><font  color=#FF0000>В процессе</font> <br>";
			}
			else if ( ($id == 15 || $id == 21 ) && $shipyard_operating ) {
				echo "<td class=l><font  color=#FF0000>В процессе</font> <br>";
			}
   			else if ( $lvl == 0 )
      		{
        		if ( IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<td class=l><a href='index.php?page=b_building&session=$session&modus=add&techid=".$id."&planet=".$aktplanet['planet_id']."'><font color=#00FF00> строить </font></a>\n";
          		else echo "<td class=l><font color=#FF0000> строить </font>\n";
			}
   			else
      		{
        		if ( IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<td class=l><a href='index.php?page=b_building&session=$session&modus=add&techid=".$id."&planet=".$aktplanet['planet_id']."'><font color=#00FF00>Совершенствовать <br> до уровня  ".($lvl+1)."</font></a>\n";
          		else echo "<td class=l><font color=#FF0000>Совершенствовать <br> до уровня  ".($lvl+1)."</font>";
			}
		}
    }
    echo "</td></tr>\n";
}

echo "  </table>\n</tr>\n</table>\n";

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>