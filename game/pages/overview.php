<?php

// Обзор.

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "fleetorder", $GlobalUni['lang'] );
loca_add ( "overview", $GlobalUni['lang'] );

$OverviewMessage = "";
$OverviewError = "";

require_once "overview_events.php";

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

$now = time();
if ($GlobalUser['admin'] == 0) UpdateQueue ( $now );    // Не обновлять Обзор для админов
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );

UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("overview");

// *******************************************************************

if ( key_exists ('lgn', $_GET) )
{

    UpdatePlanetActivity ( $aktplanet['planet_id'] );  // Обновить активность на Главной планете при входе в игру.
}

$uni = $GlobalUni;

?>

<!-- CONTENT AREA --> 
<div id='content'> 
<center> 
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
    if ( $now < $uni['news_until'])        // Показать новости?
    {
?>

<!-- _________________ComBox___________________ --> 
<div id="combox_container" > 
<a id="combox" href="http://board.oldogame.ru/" target=_blank> 
<div id="anfang"><?=$uni['news1'];?></div> 
<div id="ende"><?=$uni['news2'];?></div> 
</a> 
</div> 
<!-- _________________ComBox Ende _____________ --> 

<?php
    }

// Меню планеты
echo "<table width='519'>\n\n";
echo "<tr><td class='c' colspan='4'>\n";
if ($aktplanet['type'] == 0) $name = va ( loca ("OVERVIEW_MOON"), $aktplanet['name'], $aktplanet['g'], $aktplanet['s'], $aktplanet['p'] );
else $name = va ( loca("OVERVIEW_PLANET"), $aktplanet['name'] );

echo "<a href='index.php?page=renameplanet&session=$session&pl=".$aktplanet['planet_id']."' title='".loca("OVERVIEW_PLANET_MENU")."'>".$name."</a>     (".$GlobalUser['oname'].")\n";
echo "</td></tr>\n";

// Новые сообщения.
$num = UnreadMessages ( $GlobalUser['player_id'] );
if ($num) {
    if ($num > 1) $msgs = loca("OVERVIEW_MSGS");
    else $msgs = "";
    echo "<tr><th colspan=\"4\"><a href=\"index.php?page=messages&dsp=1&session=$session\">  ".va ( loca("OVERVIEW_NEWMSG"), $num, $msgs )."   </th></tr>\n";
}

// Время сервера и список событий.
echo "<tr><th>    ".loca("OVERVIEW_TIME")."   </th> <th colspan=3>".date ( "D M j G:i:s", $now)."</th></tr>\n";
echo "<tr><td colspan='4' class='c'>  ".loca("OVERVIEW_EVENTS")."   </td> </tr>\n\n";

EventList ();

// Показать, если у планеты есть луна.
$moonid = PlanetHasMoon ( $aktplanet['planet_id'] );
if ($moonid)
{
    $moonobj = GetPlanet ( $moonid );
    echo "<th>    ".$moonobj['name']."     <br>\n";
    echo "<a href=\"index.php?page=overview&session=$session&cp=".$moonid."\"><img src=\"".GetPlanetSmallImage ( UserSkin (), $moonobj )."\" width=\"50\" alt=\"".loca("MOON")."\" height=\"50\" ></a>\n";
    echo "</th>\n";
}
else echo "<th>\n</th>\n";

// Показать картинку планеты.
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
echo "</th>\n";

// Список планет.
echo "<th class='s'>\n";
echo "<table border='0' align='top' class='s'>\n";
$result = EnumPlanets ( $GlobalUser['player_id']);
$num = dbrows ($result);
for ($i=0; $i<$num; $i++)
{
    $planet = dbarray ($result);
    if ($planet['type'] == 0 || $planet['planet_id'] == $aktplanet['planet_id']) { $num--; $i--; continue; }
    if (($i%2) == 0) echo "<tr>\n";
    echo "<th> ".$planet['name']."<br> <a href=\"index.php?page=overview&session=$session&cp=".$planet['planet_id']."\" title=\"".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]\">";
    echo "<img src=\"".GetPlanetImage ( UserSkin (), $planet )."\" width=\"50\" height=\"50\" title=\"".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]\" ></a>\n";
    echo "<br><center>";
    {    // Вывести текущее строительство
        $qresult = GetBuildQueue ( $planet['planet_id'] );
        $cnt = dbrows ( $qresult );
        if ( $cnt > 0 ) {
            $queue = dbarray ($qresult);
            echo loca("NAME_".$queue['tech_id']) ;
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

if ( $GlobalUser['score1'] < 0 ) $score = 0;
else $score = nicenum(floor($GlobalUser['score1']/1000));

// Параметры планеты
echo "<tr><th> ".va(loca("OVERVIEW_DIAM"), nicenum($aktplanet['diameter']))."     ".va(loca("OVERVIEW_FIELDS"), $aktplanet['fields'], $aktplanet['maxfields'])."   </th></tr>\n";
echo "<tr><th> ".va ( loca("OVERVIEW_TEMP"), $aktplanet['temp'], $aktplanet['temp']+40 )."   \n";
echo "<tr><th> ".va ( loca("OVERVIEW_COORD"), "<a href=\"index.php?page=galaxy&galaxy=".$aktplanet['g']."&system=".$aktplanet['s']."&position=".$aktplanet['p']."&session=$session\" >[".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."]</a>")."\n";
echo "<tr><th> ".va( loca("OVERVIEW_RANK"),  $score,  "<a href='index.php?page=statistics&session=$session&start=".(floor($GlobalUser['place1']/100)*100+1)."'>".nicenum($GlobalUser['place1'])."</a>", nicenum($uni['usercount']) )."     \n";

echo "</table>\n<br><br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n\n";

if ( $GlobalUser['vacation']) $OverviewError = "<center>\nрежим отпуска<br></center>\n";
if ( $uni['freeze'] ) $OverviewError .= "<center>\nВселенная поставлена на паузу.<br></center>\n";

if ( $GlobalUser['admin'] > 0 ) $OverviewMessage .= "<center>".loca("OVERVIEW_ADMIN_NOTE")."<br></center>\n";

PageFooter ($OverviewMessage, $OverviewError, false);
ob_end_flush ();
?>