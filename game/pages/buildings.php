<?php

// Shipyard, Defense, and Research.

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "techshort", $GlobalUser['lang'] );
loca_add ( "build", $GlobalUser['lang'] );
loca_add ( "premium", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval ($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

// POST request processing.
if ( method () === "POST" && !$GlobalUser['vacation'] )
{
    foreach ( $_POST['fmenge'] as $gid=>$value )
    {
        $result = GetShipyardQueue ( $aktplanet['planet_id'] );    // Limit the number of shipyard orders.
        if ( dbrows ($result)  >= 99 ) $value = 0;

        if ( $value < 0 ) $value = 0;
        if ( $value > 0 ) {
            // Calculate amount (no more than the resources on the planet and no more than `max_werf`)
            if ( $value > $GlobalUni['max_werf'] ) $value = $GlobalUni['max_werf'];

            $res = ShipyardPrice ( $gid );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];

            if ( $aktplanet['m'] < $m || $aktplanet['k'] < $k || $aktplanet['d'] < $d ) continue;    // insufficient resources for one unit

            // Shield Domes.
            if ( $gid == GID_D_SDOME || $gid == GID_D_LDOME ) $value = 1;

            // Limit the number of missiles to the capacity of the silo.
            $free_space = $aktplanet['b44'] * 10 - ($aktplanet['d502'] + 2 * $aktplanet['d503']);
            if ( $gid == GID_D_ABM ) $value = min ( $free_space, $value );
            if ( $gid == GID_D_IPM ) $value = min ( floor ($free_space / 2), $value );
            
            if ($m) $cm = floor ($aktplanet['m'] / $m);
            else $cm = 1000;
            if ($k) $ck = floor ($aktplanet['k'] / $k);
            else $ck = 1000;
            if ($d) $cd = floor ($aktplanet['d'] / $d);
            else $cd = 1000;
            $v = min ( $cm, min ($ck, $cd) );
            if ( $value > $v ) $value = $v;

            AddShipyard ( $GlobalUser['player_id'], $aktplanet['planet_id'], intval ($gid), intval ($value) );
            $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );    // update the planet's state.
        }
    }
}

// GET request processing.
if ( method () === "GET"  && !$GlobalUser['vacation'] )
{
	if ( $_GET['mode'] === "Forschung" ) {
		$result = GetResearchQueue ( $GlobalUser['player_id'] );
		$resqueue = dbarray ($result);
		if ( $resqueue == null )		// The research is not in progress (run)
		{
			if ( key_exists ( 'bau', $_GET ) ) StartResearch ( $GlobalUser['player_id'], $aktplanet['planet_id'], intval ($_GET['bau']), $now );
                  $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );    // update the planet's state.
		}
		else	// Research in progress (cancel)
		{
			if ( key_exists ( 'unbau', $_GET ) ) StopResearch ( $GlobalUser['player_id'] );
                  $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );    // update the planet's state.
		}
	}
}

PageHeader ("buildings");

BeginContent ();

echo "<title> \n";
echo loca("BUILD_BUILDINGS_HEAD") . "\n";
echo "</title> \n";
echo "<script type=\"text/javascript\"> \n\n";
echo "function setMax(key, number){\n";
echo "    document.getElementsByName('fmenge['+key+']')[0].value=number;\n";
echo "}\n";
echo "</script> \n";

$unitab = LoadUniverse ( );
$speed = $unitab['speed'];

// ************************************************ Shipyard ************************************************ 

if ( $_GET['mode'] === "Flotte" )
{
    $prem = PremiumStatus ($GlobalUser);

    // Check to see if a Shipyard or Nanite Factory is under construction.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['tech_id'] == GID_B_SHIPYARD || $queue['tech_id'] == GID_B_NANITES ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>".loca("BUILD_ERROR_SHIPYARD_BUSY")."</font><br><br>";
    }
    if ( $GlobalUser['vacation'] ) {
        echo "<font color=#FF0000><center>".va(loca("BUILD_ERROR_VACATION"), date ("Y-m-d H:i:s", $GlobalUser['vacation_until']))."</center></font>";
    }
    echo "<form action=index.php?page=buildings&session=$session&mode=".$_GET['mode']." method=post>";
    echo "<table align=top><tr><td style='background-color:transparent;'>  ";
    if ( $GlobalUser['useskin'] ) echo "<table width=\"530\">\n";
    else echo "<table width=\"468\">\n";
    echo "         <tr> \n";
    echo "          <td class=l colspan=\"2\">".loca("BUILD_DESC")."</td> \n";
    echo "          <td class=l><b>".loca("BUILD_AMOUNT")."</b></td> \n";
    echo "          </tr> \n\n";

    // See if there's a shipyard on the planet.
    if ( $aktplanet['b21'] ) {
        // Output the objects that can be built in the Shipyard.
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
            if ($aktplanet['f'.$id]) echo "</a> (".va(loca("BUILD_SHIPYARD_UNITS"), $aktplanet['f'.$id]).")";
            $res = ShipyardPrice ( $id );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            echo "<br>".loca("SHORT_$id")."<br>".loca("BUILD_PRICE").":";
            if ($m) echo " ".loca("METAL").": <b>".nicenum($m)."</b>";
            if ($k) echo " ".loca("CRYSTAL").": <b>".nicenum($k)."</b>";
            if ($d) echo " ".loca("DEUTERIUM").": <b>".nicenum($d)."</b>";
            if ($e) echo " ".loca("ENERGY").": <b>".nicenum($e)."</b>";
            $t = ShipyardDuration ( $id, $aktplanet['b21'], $aktplanet['b15'], $speed );
            echo "<br>".loca("BUILD_DURATION").": ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k >";
            if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) echo "<font color=#FF0000>".loca("BUILD_SHIPYARD_CANT")."</font>";
            else if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e ) && !$busy) {
                echo "<input type=text name='fmenge[$id]' alt='".loca("NAME_$id")."' size=6 maxlength=6 value=0 tabindex=1> ";
                if ( $prem['commander'] ) {
                    $max = $GlobalUni['max_werf'];
                    if ( $m ) $max = floor (min ($max, $aktplanet['m'] / $m));
                    if ( $k ) $max = floor (min ($max, $aktplanet['k'] / $k));
                    if ( $d ) $max = floor (min ($max, $aktplanet['d'] / $d));
                    echo "<br><a href=\"javascript:setMax($id, $max);\">(max. $max)</a>";
                }
            }
            echo "</td></tr>";
        }

        // Build Button.
        echo "<td class=c colspan=2 align=center><input type=submit value=\"".loca("BUILD_SHIPYARD_SUBMIT")."\"></td></tr>";
    }
    else {
        if (!$busy) echo "<table><tr><td class=c>".loca("BUILD_ERROR_SHIPYARD_REQUIRED")."</td></tr></table>";
    }
}


// ************************************************ Defense ************************************************ 

if ( $_GET['mode'] === "Verteidigung" )
{
    $prem = PremiumStatus ($GlobalUser);

    // Check to see if a Shipyard or Nanite Factory is under construction.
    $result = GetBuildQueue ( $aktplanet['planet_id'] );
    $queue = dbarray ( $result );
    $busy = ( $queue['tech_id'] == GID_B_SHIPYARD || $queue['tech_id'] == GID_B_NANITES ) ;

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>".loca("BUILD_ERROR_SHIPYARD_BUSY")."</font><br><br>";
    }
    if ( $GlobalUser['vacation'] ) {
        echo "<font color=#FF0000><center>".va(loca("BUILD_ERROR_VACATION"), date ("Y-m-d H:i:s", $GlobalUser['vacation_until']))."</center></font>";
    }
    echo "<form action=index.php?page=buildings&session=$session&mode=".$_GET['mode']." method=post>";
    echo "<table align=top><tr><td style='background-color:transparent;'>  ";
    if ( $GlobalUser['useskin'] ) echo "<table width=\"530\">\n";
    else echo "<table width=\"468\">\n";
    echo "          <tr> \n";
    echo "          <td class=l colspan=\"2\">".loca("BUILD_DESC")."</td> \n";
    echo "          <td class=l><b>".loca("BUILD_AMOUNT")."</b></td> \n";
    echo "          </tr> \n\n";

    // See if there's a shipyard on the planet.
    if ( $aktplanet['b21'] ) {
        // Output the objects that can be built in the Shipyard.
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
            if ($aktplanet['d'.$id]) echo "</a> (".va(loca("BUILD_SHIPYARD_UNITS"), $aktplanet['d'.$id]).")";
            $res = ShipyardPrice ( $id );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            echo "<br>".loca("SHORT_$id")."<br>".loca("BUILD_PRICE").":";
            if ($m) echo " ".loca("METAL").": <b>".nicenum($m)."</b>";
            if ($k) echo " ".loca("CRYSTAL").": <b>".nicenum($k)."</b>";
            if ($d) echo " ".loca("DEUTERIUM").": <b>".nicenum($d)."</b>";
            if ($e) echo " ".loca("ENERGY").": <b>".nicenum($e)."</b>";
            $t = ShipyardDuration ( $id, $aktplanet['b21'], $aktplanet['b15'], $speed );
            echo "<br>".loca("BUILD_DURATION").": ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k >";
            if ( !$busy ) {
                if ( ($id == GID_D_SDOME || $id == GID_D_LDOME) && $aktplanet['d'.$id] > 0 ) echo "<font color=#FF0000>".loca("BUILD_ERROR_DOME")."</font>";
                else if ( !ShipyardMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) echo "<font color=#FF0000>".loca("BUILD_SHIPYARD_CANT")."</font>";
                else if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e ) ) {
                    echo "<input type=text name='fmenge[$id]' alt='".loca("NAME_$id")."' size=6 maxlength=6 value=0 tabindex=1> ";
                    if ( $prem['commander'] && !( $id == GID_D_SDOME || $id == GID_D_LDOME ) ) {
                        if ( $id == GID_D_ABM ) $max = $aktplanet['b44'] * 10 - (2*$aktplanet['d503'] + $aktplanet['d502']);
                        else if ( $id == GID_D_IPM ) $max = ($aktplanet['b44'] * 10 - (2*$aktplanet['d503'] + $aktplanet['d502'])) / 2;
                        else $max = $GlobalUni['max_werf'];
                        if ( $m ) $max = floor (min ($max, $aktplanet['m'] / $m));
                        if ( $k ) $max = floor (min ($max, $aktplanet['k'] / $k));
                        if ( $d ) $max = floor (min ($max, $aktplanet['d'] / $d));
                        echo "<br><a href=\"javascript:setMax($id, $max);\">(max. $max)</a>";
                    }
                }
            }
            echo "</td></tr>";
        }
    
        // Build Button.
        echo "<td class=c colspan=2 align=center><input type=submit value=\"".loca("BUILD_SHIPYARD_SUBMIT")."\"></td></tr>";
    }
    else {
        if (!$busy) echo "<table><tr><td class=c>".loca("BUILD_ERROR_SHIPYARD_REQUIRED")."</td></tr></table>";
    }
}

// ************************************************ Research ************************************************ 

if ( $_GET['mode'] === "Forschung" )
{
    $prem = PremiumStatus ($GlobalUser);
    if ( $prem['technocrat'] ) $r_factor = 1.1;
    else $r_factor = 1.0;

    // Is the research lab being upgraded on any planet?
    $query = "SELECT * FROM ".$db_prefix."queue WHERE obj_id = ".GID_B_RES_LAB." AND (type = 'Build' OR type = 'Demolish') AND start < $now AND owner_id = " . $GlobalUser['player_id'];
    $result = dbquery ( $query );
    $busy = ( dbrows ($result) > 0 );

    // Check to see if the research is in progress.
    $res = GetResearchQueue ( $GlobalUser['player_id'] );
    $resq = dbarray ($res);
    $operating =  ( $resq != null );

    if ( $busy ) {
        echo "<br><br><font color=#FF0000>".loca("BUILD_ERROR_RESLAB_BUSY")."</font><br /><br />";
    }
    if ( $GlobalUser['vacation'] ) {
        echo "<font color=#FF0000><center>".va(loca("BUILD_ERROR_VACATION"), date ("Y-m-d H:i:s", $GlobalUser['vacation_until']))."</center></font>";
    }
    echo "<table align=top><tr><td style='background-color:transparent;'>  ";
    if ( $GlobalUser['useskin'] ) echo "<table width=\"530\">\n";
    else echo "<table width=\"468\">\n";
    echo "          <tr> \n";
    echo "          <td class=l colspan=\"2\">".loca("BUILD_DESC")."</td> \n";
    echo "          <td class=l><b>".loca("BUILD_AMOUNT")."</b></td> \n";
    echo "          </tr> \n\n";

    // See if there's a lab on the planet.
    if ( $aktplanet['b31'] ) {
        // Display a list of available research
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
            if ($GlobalUser['r'.$id]) echo "</a> (" . va(loca("BUILD_LEVEL"), $GlobalUser['r'.$id]);
            if ( $id == GID_R_ESPIONAGE && $prem['technocrat'] ) { 
                echo " <b><font style=\"color:lime;\">+2</font></b> <img border=\"0\" src=\"img/technokrat_ikon.gif\" alt=\"".loca("PREM_TECHNOCRATE")."\" onmouseover=\"return overlib('<font color=white>".loca("PREM_TECHNOCRATE")."</font>', WIDTH, 100);\" onmouseout='return nd();' width=\"20\" height=\"20\" style=\"vertical-align:middle;\"> ";
            }
            if ($GlobalUser['r'.$id]) echo ")";
            $res = ResearchPrice ( $id, $level );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            echo "<br>".loca("SHORT_$id")."<br>".loca("BUILD_PRICE").":";
            if ($m) echo " ".loca("METAL").": <b>".nicenum($m)."</b>";
            if ($k) echo " ".loca("CRYSTAL").": <b>".nicenum($k)."</b>";
            if ($d) echo " ".loca("DEUTERIUM").": <b>".nicenum($d)."</b>";
            if ($e) echo " ".loca("ENERGY").": <b>".nicenum($e)."</b>";
            $t = ResearchDuration ( $id, $level, $reslab, $speed * $r_factor );
            echo "<br>".loca("BUILD_DURATION").": ".BuildDurationFormat ( $t )."<br></th>";
            echo "<td class=k>";
            if ( $operating )        // The research is in progress
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
    
                        bxx.innerHTML='<?=loca("BUILD_COMPLETE");?><br><a href=index.php?page=buildings&session=<?=$session;?>&mode=Forschung&cp=<?=$aktplanet['planet_id'];?> ><?=loca("BUILD_RESEARCH_NEXT");?></a>';
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
                    if ( $aktplanet['planet_id'] == $resq['sub_id'] )  echo "\">".loca("BUILD_CANCEL")."</a>\"";   ?>                }
                    ;
                    window.setTimeout("t();",999);
                }
                window.onload=t;
                </script>
<?php
                }
                else echo " - ";
            }
            else        // The research is not in progress.
            {
                if ($GlobalUser['r'.$id]) {
                    if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e ) ) echo " <a href=index.php?page=buildings&session=$session&mode=Forschung&bau=$id><font color=#00FF00>".va(loca("BUILD_RESEARCH_LEVEL"), $level)."</font></a>";
                    else echo "<font color=#FF0000>".va(loca("BUILD_RESEARCH_LEVEL"), $level)."</font>";
                }
                else {
                    if (IsEnoughResources ( $aktplanet, $m, $k, $d, $e ) ) echo " <a href=index.php?page=buildings&session=$session&mode=Forschung&bau=$id><font color=#00FF00>".loca("BUILD_RESEARCH")."</font></a>";
                    else echo "<font color=#FF0000>".loca("BUILD_RESEARCH")."</font></a>";
                }
            }
            echo "</td></tr>";
        }
    }
    else {
        if (!$busy) echo "<table><tr><td class=c>".loca("BUILD_ERROR_RESLAB_REQUIRED")."</td></tr></table>";
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
aa = "<?=loca("BUILD_SHIPYARD_COMPLETE");?>";


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
            act = "<?=loca("BUILD_SHIPYARD_CURRENT");?>";
        }else{
            act = "";
        }
        document.Atr.auftr.options[document.Atr.auftr.length] = new Option(a[iv]+ae+" \""+b[iv]+"\""+act, iv + of);
    }
}

window.onload = t;
document.addEventListener("visibilitychange", function() {
    if (!document.hidden) {
        t();
    }
});
</script>
<!-- JAVASCRIPT ENDE-->


<br>
<form name="Atr" method="get" action="index.php?page=buildings">
<input type="hidden" name="session" value="<?=$session;?>">
<input type="hidden" name="mode" value="Flotte">
<table width="530">

 <tr>
    <td class="c" ><?=loca("BUILD_SHIPYARD_QUEUE");?></td>
 </tr>
 <tr>
  <th ><select name="auftr" size="10"></select></th>
   </tr>
 <tr>
  <td class="c" ></td>

 </tr>
</table>
</form>
<?=loca("BUILD_SHIPYARD_TIME");?>

  <?=BuildDurationFormat ($total_time); ?><br>
<?php
    }
}

echo "<br><br><br><br>\n";
EndContent();

PageFooter ();
ob_end_flush ();
?>