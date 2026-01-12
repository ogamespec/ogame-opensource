<?php

/** @var array $GlobalUser */
/** @var array $GlobalUni */
/** @var string $aktplanet */

// Overview.

// TODO: Carefully check the generated HTML code for authenticity with the original (especially the recently discovered issue with "free" #76).

require_once "overview_events.php";

if ( key_exists ('lgn', $_GET) )
{

    UpdatePlanetActivity ( $aktplanet['planet_id'] );  // Update the activity on the Home Planet when you log into the game.
}
?>

<script type="text/javascript"> 
<!--
function t_building() {
    v = new Date();
    var bxx = document.getElementById('bxx');
    var timeout = 1;
    n=new Date();
    ss=pp;
    aa=Math.round((n.getTime()-v.getTime())/1000.);
    s=ss-aa;
    m=0;
    h=0;
    
    if (s < 0) {
        bxx.innerHTML='--';
        
        if ((ss + 6) >= aa) {
            window.setTimeout('document.location.href="index.php?page=overview&session='+ps+'";', 1500);
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
        bxx.innerHTML=h+":"+m+":"+s;
    }    
    pp=pp-1;
    window.setTimeout("t_building();", 999);
}
//--> 

</script> 

<?php
    if ( $now < $GlobalUni['news_until'])        // Show the news?
    {
        $combox_url = "";
        if (!empty($GlobalUni['ext_board'])) $combox_url = $GlobalUni['ext_board'];
        else if (!empty($GlobalUni['ext_discord'])) $combox_url = $GlobalUni['ext_discord'];
?>

<!-- _________________ComBox___________________ --> 
<div id="combox_container" > 
<a id="combox" href="<?=$combox_url;?>" target=_blank> 
<div id="anfang"><?=$GlobalUni['news1'];?></div> 
<div id="ende"><?=$GlobalUni['news2'];?></div> 
</a> 
</div> 
<!-- _________________ComBox Ende _____________ --> 

<?php
    }

// Planet menu
echo "<table width='519'>\n\n";
echo "<tr><td class='c' colspan='4'>\n";
if ($aktplanet['type'] == PTYP_MOON) $name = va ( loca ("OVERVIEW_MOON"), $aktplanet['name'], $aktplanet['g'], $aktplanet['s'], $aktplanet['p'] );
else $name = va ( loca("OVERVIEW_PLANET"), $aktplanet['name'] );

echo "<a href='index.php?page=renameplanet&session=$session&pl=".$aktplanet['planet_id']."' title='".loca("OVERVIEW_PLANET_MENU")."'>".$name."</a>     (".$GlobalUser['oname'].")\n";
echo "</td></tr>\n";

// New Messages.
$num = UnreadMessages ( $GlobalUser['player_id'] );
if ($num) {
    if ($num > 1) $msgs = loca("OVERVIEW_MSGS");
    else $msgs = "";
    echo "<tr><th colspan=\"4\"><a href=\"index.php?page=messages&dsp=1&session=$session\">  ".va ( loca("OVERVIEW_NEWMSG"), $num, $msgs )."   </th></tr>\n";
}

// Server time and list of events.
echo "<tr><th>    ".loca("OVERVIEW_TIME")."   </th> <th colspan=3>".date ( "D M j G:i:s", $now)."</th></tr>\n";
echo "<tr><td colspan='4' class='c'>  ".loca("OVERVIEW_EVENTS")."   </td> </tr>\n\n";

EventList ();

// Show if a planet has a moon.
$moonid = PlanetHasMoon ( $aktplanet['planet_id'] );
if ($moonid)
{
    $moonobj = GetPlanet ( $moonid );
    echo "<th>    ".$moonobj['name']."     <br>\n";
    echo "<a href=\"index.php?page=overview&session=$session&cp=".$moonid."\"><img src=\"".GetPlanetSmallImage ( UserSkin (), $moonobj )."\" width=\"50\" alt=\"".loca("MOON")."\" height=\"50\" ></a>\n";
    echo "</th>\n";
}
else echo "<th>\n</th>\n";

// Show a picture of the planet.
echo "<th colspan=\"2\">\n<img src=\"".GetPlanetImage ( UserSkin (), $aktplanet )."\" width=\"200\" height=\"200\">\n";

$result = GetBuildQueue ( $aktplanet['planet_id'] );
$cnt = dbrows ( $result );
if ( $cnt > 0 )
{
    $queue = dbarray ($result);
    $left = $queue['end'] - time ();
    echo "<br><center>".loca("NAME_".$queue['tech_id']) . " ";
    if ( $queue['destroy'] ) {
        $queue['level']++;
        echo "Снести";
    }
    echo " (".$queue['level'].")<div id=\"bxx\" title=\"".$queue['end']."\" class=\"z\"></div><SCRIPT language=JavaScript>\n";
    echo "pp=\"".$left."\"; ps=\"$session\"; t_building();\n";
    echo "</script></center><br>\n";
}
else {
    echo "<br><center>".loca("OVERVIEW_FREE")."</center><br>\n";
}
echo "</th>\n";

// List of planets.
echo "<th class='s'>\n";
echo "<table border='0' align='top' class='s'>\n";
$result = EnumPlanets ();
$num = dbrows ($result);
for ($i=0; $i<$num; $i++)
{
    $planet = dbarray ($result);
    if ($planet['type'] == PTYP_MOON || $planet['planet_id'] == $aktplanet['planet_id']) { $num--; $i--; continue; }
    if (($i%2) == 0) echo "<tr>\n";
    echo "<th> ".$planet['name']."<br> <a href=\"index.php?page=overview&session=$session&cp=".$planet['planet_id']."\" title=\"".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]\">";
    echo "<img src=\"".GetPlanetImage ( UserSkin (), $planet )."\" width=\"50\" height=\"50\" title=\"".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]\" ></a>\n";
    echo "<br><center>";
    {    // Display current building
        $qresult = GetBuildQueue ( $planet['planet_id'] );
        $cnt = dbrows ( $qresult );
        if ( $cnt > 0 ) {
            $queue = dbarray ($qresult);
            echo loca("NAME_".$queue['tech_id']) ;
        }
        else {
            echo loca("OVERVIEW_FREE");
        }
        dbfree ( $qresult );
    }
    echo "</center></th>\n";
    if ($i == $num-1) echo "</tr>\n\n";
    else if (($i%2) != 0) echo "</tr>\n\n";
}
dbfree ( $result );
echo "<tr></tr>\n</table>\n</th>\n\n";

if ( $GlobalUser['score1'] < 0 ) $score = 0;
else $score = nicenum(floor($GlobalUser['score1']/1000));

// Planet parameters
echo "<tr><th> ".va(loca("OVERVIEW_DIAM"), nicenum($aktplanet['diameter']))."     ".va(loca("OVERVIEW_FIELDS"), $aktplanet['fields'], $aktplanet['maxfields'])."   </th></tr>\n";
echo "<tr><th> ".va ( loca("OVERVIEW_TEMP"), $aktplanet['temp'], $aktplanet['temp']+40 )."   \n";
echo "<tr><th> ".va ( loca("OVERVIEW_COORD"), "<a href=\"index.php?page=galaxy&galaxy=".$aktplanet['g']."&system=".$aktplanet['s']."&position=".$aktplanet['p']."&session=$session\" >[".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."]</a>")."\n";
echo "<tr><th> ".va( loca("OVERVIEW_RANK"),  $score,  "<a href='index.php?page=statistics&session=$session&start=".(floor($GlobalUser['place1']/100)*100+1)."'>".nicenum($GlobalUser['place1'])."</a>", nicenum($GlobalUni['usercount']) )."     \n";

echo "</table>\n<br><br><br><br><br>\n";

if ( $GlobalUser['vacation']) $PageError = "<center>\n".loca("OVERVIEW_VM")."<br></center>\n";
if ( $GlobalUni['freeze'] ) $PageError .= "<center>\n".loca("OVERVIEW_UNI_FREEZE")."<br></center>\n";

if ( $GlobalUser['admin'] > 0 ) $PageMessage .= "<center>".loca("OVERVIEW_ADMIN_NOTE")."<br></center>\n";
?>