<?php

/** @var array $GlobalUser */
/** @var array $requrements */

// Technologies (details).

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "techtree", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ( $GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
if ($aktplanet == null) {
    Error ("Can't get aktplanet");
}
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("techtreedetails");

BeginContent ();

// **************************************************************************************

$tree = array ();
$filter = array ();

$reclevel = -1;
$maxreclevel = -1;

function walk_tree (array $arr, int $id) : void
{
    global $requrements, $reclevel, $maxreclevel, $tree;
    $reclevel++;
    if ($reclevel >= $maxreclevel) $maxreclevel = $reclevel;
    if ($arr == null) { $reclevel--; return; }

    foreach ($arr as $i=>$level) {
        if ( !key_exists ($reclevel, $tree) ) $tree[$reclevel] = array ();
        $tree[$reclevel][$i] = 0;
        if ($tree[$reclevel][$i] < $level) $tree[$reclevel][$i] = $level;
    }
    foreach ($arr as $i=>$level) {
        walk_tree ( $requrements[$i], $i );
    }
    $reclevel--;
}

function MeetRequirement ( array $user, array $planet, int $id, int $level ) : bool
{
    if (IsResearch($id)) return $user['r'.$id] >= $level;
    else return $planet['b'.$id] >= $level;
}

$id = intval($_GET['tid']);

echo "<center> \n";
echo "<table width=270> \n";
echo "<tr> \n";
echo "<td class=c align=center nowrap> \n";
echo va( loca("TECHTREE_COND_FOR"), "<a href=\"index.php?page=infos&session=$session&gid=$id\">'".loca("NAME_$id")."'</a>") . "</td> \n";
echo "</tr> \n";

walk_tree ( $requrements[$id], $id );

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
EndContent ();

PageFooter ();
ob_end_flush ();
?>