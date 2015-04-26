<?php

// Технологии (детали).

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "techtree", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ( $GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("techtreedetails");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

// **************************************************************************************
// Список объектов что-чему требуется.

$reqs = array (

    1 => array (),
    2 => array (),
    3 => array (),
    4 => array (),
    12 => array (3=>5, 113=>3),
    14 => array (),
    15 => array (14=>10, 108=>10),
    21 => array (14=>2),
    22 => array (),
    23 => array (),
    24 => array (),
    31 => array (),
    33 => array (15=>1, 113=>12),
    34 => array (),
    44 => array (21=>1),
    106 => array (31=>3),
    108 => array (31=>1),
    109 => array (31=>4),
    110 => array (113=>3, 31=>6),
    111 => array (31=>2),
    113 => array (31=>1),
    114 => array (113=>5, 110=>5, 31=>7),
    115 => array (113=>1),
    117 => array (113=>1, 31=>2),
    118 => array (114=>3),
    120 => array (113=>2),
    121 => array (31=>4, 120=>5, 113=>4),
    122 => array (113=>8, 120=>10, 121=>5),
    123 => array (31=>10, 108=>8, 114=>8),
    124 => array (106=>4, 117=>3),
    199 => array (31=>12),
    202 => array (21=>2, 115=>2),
    203 => array (21=>4, 115=>6),
    204 => array (21=>1, 115=>1),
    205 => array (21=>3, 111=>2, 117=>2),
    206 => array (21=>5, 117=>4, 121=>2),
    207 => array (21=>7, 118=>4),
    208 => array (21=>4, 117=>3),
    209 => array (21=>4, 115=>6, 110=>2),
    210 => array (21=>3, 115=>3, 106=>2),
    211 => array (117=>6, 21=>8, 122=>5),
    212 => array (21=>1),
    213 => array (21=>9, 118=>6, 114=>5),
    214 => array (21=>12, 118=>7, 114=>6, 199=>1),
    215 => array (114=>5, 120=>12, 118=>5, 21=>8),
    401 => array (21=>1),
    402 => array (113=>1, 21=>2, 120=>3),
    403 => array (113=>3, 21=>4, 120=>6),
    404 => array (21=>6, 113=>6, 109=>3, 110=>1),
    405 => array (21=>4, 121=>4),
    406 => array (21=>8, 122=>7),
    407 => array (110=>2, 21=>1),
    408 => array (110=>6, 21=>6),
    502 => array (44=>2, 21=>1),
    503 => array (44=>4, 21=>1, 117=>1),
    41 => array (),
    42 => array (41=>1),
    43 => array (41=>1, 114=>7),

);

// **************************************************************************************

$tree = array ();
$filter = array ();

$reclevel = -1;
$maxreclevel = -1;

function walk_tree ($arr, $id)
{
    global $reqs, $reclevel, $maxreclevel, $tree;
    $reclevel++;
    if ($reclevel >= $maxreclevel) $maxreclevel = $reclevel;
    if ($arr == null) { $reclevel--; return; }

    foreach ($arr as $i=>$level) {
        if ( !key_exists ($reclevel, $tree) ) $tree[$reclevel] = array ();
        $tree[$reclevel][$i] = 0;
        if ($tree[$reclevel][$i] < $level) $tree[$reclevel][$i] = $level;
    }
    foreach ($arr as $i=>$level) {
        walk_tree ( $reqs[$i], $i );
    }
    $reclevel--;
}

function MeetRequirement ( $user, $planet, $id, $level )
{
    if ($id > 100) return $user['r'.$id] >= $level;
    else return $planet['b'.$id] >= $level;
}

$id = intval($_GET['tid']);

echo "<center> \n";
echo "<table width=270> \n";
echo "<tr> \n";
echo "<td class=c align=center nowrap> \n";
echo va( loca("TECHTREE_COND_FOR"), "<a href=\"index.php?page=infos&session=$session&gid=$id\">'".loca("NAME_$id")."'</a>") . "</td> \n";
echo "</tr> \n";

walk_tree ( $reqs[$id], $id );

if ( $maxreclevel == 0 ) echo "<tr><td class=l align=center>".loca("TECHTREE_COND_NO")."</td></tr> ";

for ($i=$maxreclevel-1,$n=0; $i>=0; $i--,$n++)
{
    echo "<tr><td class=c>".($n+1)."</td></tr>";

    foreach ( $tree[$i] as $v=>$level) 
    {
        $filter[$v] = 0;
        if ($filter[$v] >= $level) continue;
        $color = "#00ff00";
        if ( !MeetRequirement ( $GlobalUser, $aktplanet, $v, $level ) ) $color = "#ff0000";

        echo "<tr>\n";
        echo "    <td class=l align=center> \n";
        echo "    <table width=\"100%\" border=0> \n";
        echo "    <tr> \n";
        echo "        <td align=left> <font color=\"$color\"> ".loca("NAME_$v")." ".va(loca("TECHTREE_LEVEL"),$level)." </font> </td> \n";
        echo "        <td align=right> <a href=\"index.php?page=techtreedetails&session=$session&tid=$v\">[i]</a> </td> \n";
        echo "    </tr> \n";
        echo "    </td> \n";
        echo "    </table> \n";
        echo "</tr>";

        if ($filter[$v] < $level) $filter[$v] = $level;
    }
}

echo "</table> \n";
echo "</center>";

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>