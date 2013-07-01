<?php

require_once "../loca.php";

$loca_lang = $_COOKIE['ogamelang'];
if ( !key_exists ( $loca_lang, $Languages ) ) $loca_lang = 'en';
loca_add ( "galaxytool", $loca_lang );

// Format string, according to tokens from the text. Tokens are represented as #1, #2 and so on.
function va ($subject)
{
    $num_arg = func_num_args();
    $pattern = array ();
    for ($i=1; $i<$num_arg; $i++)
    {
        $pattern[$i-1] = "/#$i/";
        $replace[$i-1] = func_get_arg($i);
    }
    return preg_replace($pattern, $replace, $subject);
}

?>
<html>
 <head>
  <link rel='stylesheet' type='text/css' href='../css/default.css' />
  <link rel='stylesheet' type='text/css' href='../css/formate.css' />
  <meta http-equiv='content-type' content='text/html; charset=UTF-8' />
<link rel='stylesheet' type='text/css' href='../css/combox.css'>
<link rel='stylesheet' type='text/css' href='../../evolution/formate.css' />
<title><?=loca("GALATOOL_TITLE");?></title>
  <script language='JavaScript'>
  </script>
<script type='text/javascript' src='../js/overLib/overlib.js'></script>
</head>
<body style='overflow: hidden;' onload='onBodyLoad();' onunload='' >
<div id='overDiv' style='position:absolute; visibility:hidden; z-index:1000;'></div>

<div id='content'>

<?php

function nicenum ($number)
{
    return number_format($number,0,",",".");
}

function PlayerDetails ($player_id)
{
    global $galaxy, $stats, $ally;

    $planets = array ();
    echo "<br><br><font size=+2>".$stats[$player_id]['name'].":</font>";

    echo "<table cellpadding=0 cellspacing=0><tr>";

    echo "<td class=b>";
    echo va ( loca("GALATOOL_POINTS"), nicenum($stats[$player_id]['points'] / 1000)) ."<br>";
    echo va ( loca("GALATOOL_FLEET"), nicenum($stats[$player_id]['fpoints'])) ."<br>";
    echo va ( loca("GALATOOL_RESEARCH"), nicenum($stats[$player_id]['rpoints'])) ."<br>";
    if ( $stats[$player_id]['ally_id'] ) echo va ( loca("GALATOOL_ALLY"), $ally[$stats[$player_id]['ally_id']]['name']) ."<br>";
    echo "</td>";

    echo "<td class=b><b>".loca("GALATOOL_PLANETS")."</b>:";
    foreach ( $galaxy as $planet_id=>$planet )
    {
        if ( $planet['owner_id'] == $player_id && $planet['type'] < 10000 )
        {
            $planets[$planet_id] = array ();
            $planets[$planet_id]['name'] = $planet['name'];
            $planets[$planet_id]['g'] = $planet['g'];
            $planets[$planet_id]['s'] = $planet['s'];
            $planets[$planet_id]['p'] = $planet['p'];
            $planets[$planet_id]['type'] = $planet['type'];
        }
    }

    foreach ( $planets as $id=>$planet )
    {
        echo "<br/>" . $planet['name'];
        echo " [" . $planet['g'] . ":" . $planet['s'] . ":" . $planet['p'] . "]";
    }

    echo "</td></tr></table>";
}

// Here is a function to sort an array by the key of his sub-array
function sksort ($array, $subkey="id", $sort_ascending=false)
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
    return $array;
}

$last_update = filemtime ( 'galaxy.txt' );

echo "<br>".va ( loca("GALATOOL_DATE"), date ( "d.m.Y H:i:s", $last_update )) . "<br>";

$ally = unserialize ( file_get_contents ( 'ally_statistics.txt' ) );
$old_ally = unserialize ( file_get_contents ( 'ally_statistics_old.txt' ) );

$stats = unserialize ( file_get_contents ( 'statistics.txt' ) );
$old_stats = unserialize ( file_get_contents ( 'statistics_old.txt' ) );

$galaxy = unserialize ( file_get_contents ( 'galaxy.txt' ) );

$delta = array ();

foreach ( $stats as $id=>$user )
{
    $delta[$id] = array ();
    $delta[$id]['id'] = $id;
    $delta[$id]['name'] = $user['name'];
    $delta[$id]['delta_score'] = $user['points'] - $old_stats[$id]['points'];
    $delta[$id]['delta_fleet'] = $user['fpoints'] - $old_stats[$id]['fpoints'];
    $delta[$id]['active'] = !$user['i'] && !$user['iI'] && !$user['b'] && !$user['v'];
    $delta[$id]['i'] = $user['i'];
    $delta[$id]['iI'] = $user['iI'];
    $delta[$id]['b'] = $user['b'];
    $delta[$id]['v'] = $user['v'];
}

$delta = sksort ( $delta, 'delta_score' );

echo "<br/><font size=+2 color=lime>".loca("GALATOOL_GROW")."</font><br/>\n";
$first = true;
$count = 0;
foreach ( $delta as $id=>$user)
{
    $d = $user['delta_score'];
    if ($d > 0 && $user['active'] )
    {
        if (!$first) echo ", ";
        else $first = false;
        if ( $d < 1000 ) echo "<a href='index.php?user=".$user['id']."' title='~0'>".$user['name'];
        else echo "<a href='index.php?user=".$user['id']."' title='+".nicenum ( $d / 1000 )."'>".$user['name'];
        if ( $d > 30000000 ) echo " (+" . nicenum ( $d / 1000 ) . ")";
        echo "</a>";
        $count++;
    }
}
if ($count >= 3) echo "<br><small><i>".va(loca("GALATOOL_TOTAL"), $count)."</i></small>";

echo "<br/><br/><font size=+2 color=red>".loca("GALATOOL_FALL")."</font><br/>\n";
$first = true;
$count = 0;
foreach ( $delta as $id=>$user)
{
    $d = $user['delta_score'];
    if ($d < 0 && $user['active'] )
    {
        if (!$first) echo ", ";
        else $first = false;
        echo "<a href='index.php?user=".$user['id']."' title='-".nicenum ( abs($d) / 1000 )."'>".$user['name'];
        if ( $d < -30000000 ) echo " (-" . nicenum ( abs($d) / 1000 ) . ")";
        echo "</a>";
        $count++;
    }
}
if ($count >= 3) echo "<br><small><i>".va(loca("GALATOOL_TOTAL"), $count)."</i></small>";

echo "<br/><br/><span class='longinactive'><font size=+2>".loca("GALATOOL_INACTIVE")."</font></span><br/>\n";
$first = true;
$count = 0;
foreach ( $delta as $id=>$user)
{
    $d = $user['delta_score'];
    if ($d == 0 || !$user['active'] )
    {
        if (!$first) echo ", ";
        else $first = false;
        echo "<a href='index.php?user=".$user['id']."'>";
        if ( $user['v'] ) echo "<span class='vacation'>";
        else if ( $user['iI'] ) echo "<span class='longinactive'>";
        else if ( $user['i'] ) echo "<span class='inactive'>";
        else if ( $user['b'] ) echo "<span class='banned'>";
        echo $user['name'];
        if ( $user['v'] ) echo " (РО)</span>";
        else if ( $user['iI'] ) echo " (iI)</span>";
        else if ( $user['i'] ) echo " (i)</span>";
        else if ( $user['b'] ) echo " (з)</span>";
        echo "</a>";
        $count++;
    }
}
if ($count >= 3) echo "<br><small><i>".va(loca("GALATOOL_TOTAL"), $count)."</i></small>";

?>

<br/><br/><small><i><?=loca("GALATOOL_NOTES");?></i></small>

<?php

if ( $_SERVER['REQUEST_METHOD'] === "GET" && key_exists ( 'user', $_GET ) )
{
    $player_id = $_GET['user'];
    PlayerDetails ($player_id);
}

if ( $_SERVER['REQUEST_METHOD'] === "POST" )
{
    // Найти игрока.
    foreach ( $stats as $id=>$user)
    {
        similar_text ( mb_strtolower ($_POST['login']), mb_strtolower ($user['name']), $percent );
        if ( $percent > 75 ) PlayerDetails ($id);

        //echo $user['name'] . " = " . $percent . "<br>";
    }
}

?>

<br><br>
<form action="index.php" method="POST">
<?=loca("GALATOOL_FIND");?>
<input type=text name=login>
<input type=submit value="<?=loca("GALATOOL_SEARCH");?>">
</form>

</div>

</body>
</html>