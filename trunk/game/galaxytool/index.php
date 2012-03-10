<html>
 <head>
  <link rel='stylesheet' type='text/css' href='../css/default.css' />
  <link rel='stylesheet' type='text/css' href='../css/formate.css' />
  <meta http-equiv='content-type' content='text/html; charset=UTF-8' />
<link rel='stylesheet' type='text/css' href='../css/combox.css'>
<link rel='stylesheet' type='text/css' href='../evolution/formate.css' />
<title>Информационный Центр Огейм</title>
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

function PlayerPlanets ($player_id)
{
    global $galaxy, $stats;

    $planets = array ();
    echo "<br><br><font size=+2>Планеты игрока ".$stats[$player_id]['name'].":</font>";

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
}

// Here is a function to sort an array by the key of his sub-array
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

$last_update = filemtime ( 'galaxy.txt' );

echo "<br>Данные на " . date ( "m.d.Y H:i:s", $last_update ) . "<br>";

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

sksort ( $delta, 'delta_score' );

echo "<br/><font size=+2 color=lime>Приросты за последние 7 дней:</font><br/>\n";
$first = true;
foreach ( $delta as $id=>$user)
{
    $d = $user['delta_score'];
    if ($d > 0 && $user['active'] )
    {
        if (!$first) echo ", ";
        else $first = false;
        echo "<a href='index.php?user=".$user['id']."' title='+".nicenum ( $d / 1000 )."'>".$user['name'];
        if ( $d > 30000000 ) echo " (+" . nicenum ( $d / 1000 ) . ")";
        echo "</a>";
    }
}

echo "<br/><br/><font size=+2 color=red>Спады за последние 7 дней:</font><br/>\n";
$first = true;
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
    }
}

echo "<br/><br/><span class='longinactive'><font size=+2>Неактивные и заблокированные:</font></span><br/>\n";
$first = true;
foreach ( $delta as $id=>$user)
{
    $d = $user['delta_score'];
    if ($d == 0 || !$user['active'] )
    {
        if (!$first) echo ", ";
        else $first = false;
        echo "<a href='index.php?user=".$user['id']."'>";
        if ( $user['i'] ) echo "<span class='inactive'>";
        else if ( $user['iI'] ) echo "<span class='longinactive'>";
        else if ( $user['b'] ) echo "<span class='banned'>";
        else if ( $user['v'] ) echo "<span class='vacation'>";
        echo $user['name'];
        if ( $user['i'] ) echo " (i)</span>";
        else if ( $user['iI'] ) echo " (iI)</span>";
        else if ( $user['b'] ) echo " (з)</span>";
        else if ( $user['v'] ) echo " (РО)</span>";
        echo "</a>";
    }
}

?>

<br/><br/><small><i>Если у игрока прирост/спад был более 30.000 очков, то он указывается в скобках рядом с именем.</i></small>

<?php

if ( $_SERVER['REQUEST_METHOD'] === "GET" && key_exists ( 'user', $_GET ) )
{
    $player_id = $_GET['user'];
    PlayerPlanets ($player_id);
}

if ( $_SERVER['REQUEST_METHOD'] === "POST" )
{
    // Найти игрока.
    foreach ( $stats as $id=>$user)
    {
        $percent = 0;
        similar_text ( mb_strtolower ($_POST['login']), mb_strtolower ($user['name']), &$percent );
        if ( $percent > 75 ) PlayerPlanets ($id);

        //echo $user['name'] . " = " . $percent . "<br>";
    }
}

?>

<br><br>
<form action="index.php" method="POST">
Найти все планеты игрока : 
<input type=text name=login>
<input type=submit value="Поиск">
</form>

</div>

</body>
</html>