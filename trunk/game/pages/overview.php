<?php

// Обзор.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();

$OverviewMessage = "";
$OverviewError = "";

function sksort (&$array, $subkey="id", $sort_ascending=false) 
{
    if (count($array))
        $temp_array[key($array)] = array_shift($array);

    foreach($array as $key => $val){
        $offset = 0;
        $found = false;
        foreach($temp_array as $tmp_key => $tmp_val)
        {
            if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
            {
                $temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
                                            array($key => $val),
                                            array_slice($temp_array,$offset)
                                          );
                $found = true;
            }
            $offset++;
        }
        if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
    }

    if ($sort_ascending) $array = array_reverse($temp_array);

    else $array = $temp_array;
}

function EventFleetList ($t)
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $str = "";
    foreach ($fleetmap as $i=>$gid)
    {
        $amount = $t["ship$gid"];
        if ($amount >0 ) $str .= loca("NAME_$gid")." ".nicenum($amount)."&lt;br&gt;";
    }
    return $str;
}

function EventFleetResources ($t)
{
    if ( $t['m'] + $t['k'] + $t['d'] ) return "<a href='#' title='Транспорт: Металл: ".nicenum($t['m'])." Кристалл: ".nicenum($t['k'])." Дейтерий: ".nicenum($t['d'])."'></a>";
    else return "";
}

function EventFleetResources2 ($t)
{
    if ( $t['m'] + $t['k'] + $t['d'] ) return "<a href='#' title='Транспорт: Металл: ".nicenum($t['m'])." Кристалл: ".nicenum($t['k'])." Дейтерий: ".nicenum($t['d'])."'></a>";
    else return "";
}

function EventPlanetName ($name, $type)
{
    if ($type == 0) return $name . " (".loca("MOON").")";
    else return $name;
}

function EventDirectionClass ($order)
{
    if ($order < 100) return "flight";
    else if ($order < 200) return "return";
    else return "hold";
}

function EventMissionClass ($order, $owner_id)
{
    global $GlobalUser;

    switch ( $order )
    {
        case 1    :      
        case 101 :      
            if ( $GlobalUser['player_id'] == $owner_id) return "ownattack";
            else return "attack";
        case 2    :      return "Совместная атака убывает";
        case 102 :     return "Совместная атака возвращается";
        case 3    :     
        case 103 :    
            if ( $GlobalUser['player_id'] == $owner_id) return "owntransport";
            else return "transport";
        case 4    :     
        case 104 :     
            if ( $GlobalUser['player_id'] == $owner_id) return "owndeploy";
            else return "deploy";
        case 5   :      return "Держаться убывает";
        case 105 :     return "Держаться возвращается";
        case 205 :    return "Держаться на орбите";
        case 6   :     
        case 106 :     
            if ( $GlobalUser['player_id'] == $owner_id) return "ownespionage";
            else return "espionage";
        case 7    :     return "Колонизировать убывает";
        case 107 :     return "Колонизировать возвращается";
        case 8    :     return "Переработать убывает";
        case 108 :    return "Переработать возвращается";
        case 9   :      return "Уничтожить убывает";
        case 109:      return "Уничтожить возвращается";
        case 14  :      return "Испытание убывает";
        case 114:      return "Испытание возвращается";
        case 15  :      return "Экспедиция убывает";
        case 115:      return "Экспедиция возвращается";
        case 215:      return "Экспедиция на орбите";
        case 20:       return "Ракетная атака";

        default: 
            if ( $GlobalUser['player_id'] == $owner_id) return "ownunknown";
            else return "unknown";
    }
}

// Цель ракетной атаки.
function EventRakTarget ($typ)
{
    if ($typ > 0) return "Основная цель " . loca ("NAME_$typ");
    else return "";
}

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

$fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

PageHeader ("overview");

// *******************************************************************

//CreatePlanet (1, 123, 8, 1 );
//CreatePlanet ( 3, 13, 3, 1 );

if ( key_exists ('lgn', $_GET) && $_GET['lgn'] == 1 ) UpdatePlanetActivity ( $aktplanet['planet_id'] );  // Обновить активность на Главной планете при входе в игру.

$uni = LoadUniverse ( );

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

<?php
// Выйти из фрейма.
if ( $_GET['lgn'] == 1 )
{
    echo "top.location=\"index.php?page=overview&session=$session\"";
}
?>

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

//StartBattle ( 10000, 10000 );
//die ();

// Меню планеты
echo "<table width='519'>\n\n";
echo "<tr><td class='c' colspan='4'>\n";
if ($aktplanet['type'] == 0) $name = va ( loca ("OVERVIEW_MOON"), $aktplanet['name'], $aktplanet['g'], $aktplanet['s'], $aktplanet['p'] );
else $name = va ( loca("OVERVIEW_PLANET"), $aktplanet['name'] );

echo "<a href='index.php?page=renameplanet&session=$session&pl=".$aktplanet['planet_id']."' title='".loca("OVERVIEW_PLANET_MENU")."'>".$name."</a>     (".$GlobalUser['oname'].")\n";
echo "</td></tr>\n";

// Новые сообщения.
$num = UnreadMessages ( $GlobalUser['player_id'] );
if ($num) echo "<tr><th colspan=\"4\"><a href=\"index.php?page=messages&dsp=1&session=$session\">  ".va ( loca("OVERVIEW_NEWMSG"), $num )."   </th></tr>\n";

// Время сервера и список событий.
echo "<tr><th>    ".loca("OVERVIEW_TIME")."   </th> <th colspan=3>".date ( "D M j G:i:s", $now)."</th></tr>\n";
echo "<tr><td colspan='4' class='c'>  ".loca("OVERVIEW_EVENTS")."   </td> </tr>\n\n";

$tasklist = EnumFleetQueue ( $GlobalUser['player_id'] );
$rows = dbrows ($tasklist);
$tasknum = 0;
while ($rows--)
{
    $queue = dbarray ($tasklist);

    $task[$tasknum]['start_time'] = $queue['start'];
    $task[$tasknum]['end_time'] = $queue['end'];
    $task[$tasknum]['prio'] = $queue['prio'];

    $fleet = LoadFleet ( $queue['sub_id'] );
    $origin = GetPlanet ( $fleet['start_planet'] );
    $target = GetPlanet ( $fleet['target_planet'] );

    $mission = $task[$tasknum]['mission'] = $fleet['mission'];
    $task[$tasknum]['owner_id'] = $fleet['owner_id'];
    $task[$tasknum]['m'] = $fleet['m'];
    $task[$tasknum]['k'] = $fleet['k'];
    $task[$tasknum]['d'] = $fleet['d'];

    $task[$tasknum]['thisgalaxy']         = $origin['g'];
    $task[$tasknum]['thissystem']        = $origin['s'];
    $task[$tasknum]['thisplanet']         = $origin['p'];
    $task[$tasknum]['thisplanettype']   = $origin['type'];
    $task[$tasknum]['thisname']          = $origin['name'];
    $task[$tasknum]['galaxy']              = $target['g'];
    $task[$tasknum]['system']             = $target['s'];
    $task[$tasknum]['planet']               = $target['p'];
    $task[$tasknum]['planettype']        = $target['type'];
    $task[$tasknum]['name']               = $target['name'];

    foreach ($fleetmap as $i=>$gid) $task[$tasknum]["ship$gid"] = $fleet["ship$gid"];

    $tasknum++;

    // Возвращается. Не показывать возврат чужих флотов.
    if ( $mission < 20 && $queue['owner_id'] == $GlobalUser['player_id'] && $mission != 4 ) 
    {
        $task[$tasknum]['start_time'] = $queue['end'];
        $task[$tasknum]['end_time'] = 2 * $queue['end'] - $queue['start'];
        $task[$tasknum]['prio'] = $queue['prio'];

        $task[$tasknum]['mission'] = $mission + 100;
        $task[$tasknum]['owner_id'] = $fleet['owner_id'];
        $task[$tasknum]['m'] = 0;
        $task[$tasknum]['k'] = 0;
        $task[$tasknum]['d'] = 0;

        $task[$tasknum]['thisgalaxy']         = $origin['g'];
        $task[$tasknum]['thissystem']        = $origin['s'];
        $task[$tasknum]['thisplanet']         = $origin['p'];
        $task[$tasknum]['thisplanettype']   = $origin['type'];
        $task[$tasknum]['thisname']          = $origin['name'];
        $task[$tasknum]['galaxy']              = $target['g'];
        $task[$tasknum]['system']             = $target['s'];
        $task[$tasknum]['planet']               = $target['p'];
        $task[$tasknum]['planettype']        = $target['type'];
        $task[$tasknum]['name']               = $target['name'];

        foreach ($fleetmap as $i=>$gid) $task[$tasknum]["ship$gid"] = $fleet["ship$gid"];

        $tasknum++;
    }
}

if ($tasknum > 0)
{
    sksort ( $task, 'end_time', true);        // Сортировать по времени прибытия.

    foreach ($task as $i=>$t)
    {
        if ( $t['mission'] == 20)         // Ракетная атака.
        {
            $own = "";
            if ( $t['owner_id'] == $GlobalUser['player_id'] ) $own = "own";
            echo "<tr>\n";
            echo "<th><div id='bxx".($i+1)."' title='".max($t['end_time']-$now, 0)."'star='".$t['end_time']."'></div></th>\n";
            echo "<th colspan='3'><span class='".$own."missile'>Ракетная атака (".$t["ship202"].") с планеты ".EventPlanetName($t['thisname'], $t['thisplanettype'])." ";
            echo "<a href=\"javascript:showGalaxy(".$t['thisgalaxy'].",".$t['thissystem'].",".$t['thisplanet'].")\" >[".$t['thisgalaxy'].":".$t['thisplanet'].":".$t['thissystem']."]</a> ";
            echo "на планету ".EventPlanetName($t['name'], $t['planettype'])." ";
            echo "<a href=\"javascript:showGalaxy(".$t['galaxy'].",".$t['system'].",".$t['planet'].")\" ".$own.">[".$t['galaxy'].":".$t['system'].":".$t['planet']."]</a> ".EventRakTarget($t["ship203"])."</span>\n";
            echo "</th>\n";
            echo "</tr>\n\n";
        }
        else
        {
            $mssionclass = EventMissionClass ($t['mission'], $t['owner_id']);
            echo "<tr class='".EventDirectionClass($t['mission'])."'>\n";
            echo "<th><div id='bxx".($i+1)."' title='".max($t['end_time']-$now, 0)."'star='".$t['end_time']."'></div></th>\n";
            echo "<th colspan='3'><span class='".EventDirectionClass($t['mission'])." $mssionclass'>Ваш <a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;".EventFleetList($t)."&lt;/b&gt;&lt;/font&gt;\");' onmouseout='return nd();' class='$mssionclass'>флот</a>";
            echo "<a href='#' title='Большой транспорт 11'></a> с планеты ".EventPlanetName($t['thisname'], $t['thisplanettype'])." <a href=\"javascript:showGalaxy(".$t['thisgalaxy'].",".$t['thissystem'].",".$t['thisplanet'].")\" $mssionclass>[".$t['thisgalaxy'].":".$t['thissystem'].":".$t['thisplanet']."]</a> ";
            echo "отправлен на планету ".EventPlanetName($t['name'], $t['planettype'])." <a href=\"javascript:showGalaxy(".$t['galaxy'].",".$t['system'].",".$t['planet'].")\" $mssionclass>[".$t['galaxy'].":".$t['system'].":".$t['planet']."]</a>. ";
            echo "Задание: <a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;Транспорт: &lt;br /&gt; Металл: 164.835&lt;br /&gt;Кристалл: 71.826&lt;br /&gt;Дейтерий: 25.448&lt;/b&gt;&lt;/font&gt;\");' onmouseout='return nd();'' class='$mssionclass'>".GetMissionName($t['mission'])."</a>".EventFleetResources2($t)."</span>\n";
            echo "</th>\n";
            echo "</tr>\n\n";
        }
    }
    echo "<script language=javascript>anz=".$tasknum.";t();</script>\n\n";
}

// Показать, если у планеты есть луна.
$moonid = PlanetHasMoon ( $aktplanet['planet_id'] );
if ($moonid)
{
    $moonobj = GetPlanet ( $moonid );
    echo "<th>    ".$moonobj['name']." (".loca("MOON").")     <br>\n";
    echo "<a href=\"index.php?page=overview&session=$session&cp=".$moonid."\"><img src=\"".GetPlanetSmallImage ( UserSkin (), 0 )."\" width=\"50\" alt=\"".loca("MOON")."\" height=\"50\" ></a>\n";
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
    if ($planet['type'] == 0 || $planet['planet_id'] == $aktplanet['planet_id'] || $planet['destroyed']) { $num--; $i--; continue; }
    if (($i%2) == 0) echo "<tr>\n";
    echo "<th> ".$planet['name']."<br> <a href=\"index.php?page=overview&session=$session&cp=".$planet['planet_id']."\" title=\"".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]\">";
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
echo "<tr><th> ".va(loca("OVERVIEW_DIAM"), nicenum($aktplanet['diameter']))."     ".va(loca("OVERVIEW_FIELDS"), $aktplanet['fields'], $aktplanet['maxfields'])."   </th></tr>\n";
echo "<tr><th> ".va ( loca("OVERVIEW_TEMP"), $aktplanet['temp'], $aktplanet['temp']+40 )."   \n";
echo "<tr><th> ".va ( loca("OVERVIEW_COORD"), "<a href=\"index.php?page=galaxy&galaxy=".$aktplanet['g']."&system=".$aktplanet['s']."&position=".$aktplanet['p']."&session=$session\" >[".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."]</a>")."\n";
echo "<tr><th> ".va( loca("OVERVIEW_RANK"),  nicenum(floor($GlobalUser['score1']/1000)),  "<a href='index.php?page=statistics&session=$session&start=".(floor($GlobalUser['place1']/100)*100+1)."'>".nicenum($GlobalUser['place1'])."</a>", nicenum($uni['usercount']) )."     \n";

echo "</table>\n<br><br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n\n";

if ( $GlobalUser['vacation']) $OverviewError = "<center>\nрежим отпуска<br></center>\n";

PageFooter ($OverviewMessage, $OverviewError, false);
ob_end_flush ();
?>