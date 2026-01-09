<?php

/** @var array $GlobalUser */

// Technologies (details).

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "techtree", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ( $GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("techtreedetails");

BeginContent ();

// **************************************************************************************
// A list of what-what-it-requires objects.

$reqs = array (

    GID_B_METAL_MINE => array (),
    GID_B_CRYS_MINE => array (),
    GID_B_DEUT_SYNTH => array (),
    GID_B_SOLAR => array (),
    GID_B_FUSION => array (GID_B_DEUT_SYNTH=>5, GID_R_ENERGY=>3),
    GID_B_ROBOTS => array (),
    GID_B_NANITES => array (GID_B_ROBOTS=>10, GID_R_COMPUTER=>10),
    GID_B_SHIPYARD => array (GID_B_ROBOTS=>2),
    GID_B_METAL_STOR => array (),
    GID_B_CRYS_STOR => array (),
    GID_B_DEUT_STOR => array (),
    GID_B_RES_LAB => array (),
    GID_B_TERRAFORMER => array (GID_B_NANITES=>1, GID_R_ENERGY=>12),
    GID_B_ALLY_DEPOT => array (),
    GID_B_MISS_SILO => array (GID_B_SHIPYARD=>1),
    GID_R_ESPIONAGE => array (GID_B_RES_LAB=>3),
    GID_R_COMPUTER => array (GID_B_RES_LAB=>1),
    GID_R_WEAPON => array (GID_B_RES_LAB=>4),
    GID_R_SHIELD => array (GID_R_ENERGY=>3, GID_B_RES_LAB=>6),
    GID_R_ARMOUR => array (GID_B_RES_LAB=>2),
    GID_R_ENERGY => array (GID_B_RES_LAB=>1),
    GID_R_HYPERSPACE => array (GID_R_ENERGY=>5, GID_R_SHIELD=>5, GID_B_RES_LAB=>7),
    GID_R_COMBUST_DRIVE => array (GID_R_ENERGY=>1),
    GID_R_IMPULSE_DRIVE => array (GID_R_ENERGY=>1, GID_B_RES_LAB=>2),
    GID_R_HYPER_DRIVE => array (GID_R_HYPERSPACE=>3),
    GID_R_LASER_TECH => array (GID_R_ENERGY=>2),
    GID_R_ION_TECH => array (GID_B_RES_LAB=>4, GID_R_LASER_TECH=>5, GID_R_ENERGY=>4),
    GID_R_PLASMA_TECH => array (GID_R_ENERGY=>8, GID_R_LASER_TECH=>10, GID_R_ION_TECH=>5),
    GID_R_IGN => array (GID_B_RES_LAB=>10, GID_R_COMPUTER=>8, GID_R_HYPERSPACE=>8),
    GID_R_EXPEDITION => array (GID_R_ESPIONAGE=>4, GID_R_IMPULSE_DRIVE=>3),
    GID_R_GRAVITON => array (GID_B_RES_LAB=>12),
    GID_F_SC => array (GID_B_SHIPYARD=>2, GID_R_COMBUST_DRIVE=>2),
    GID_F_LC => array (GID_B_SHIPYARD=>4, GID_R_COMBUST_DRIVE=>6),
    GID_F_LF => array (GID_B_SHIPYARD=>1, GID_R_COMBUST_DRIVE=>1),
    GID_F_HF => array (GID_B_SHIPYARD=>3, GID_R_ARMOUR=>2, GID_R_IMPULSE_DRIVE=>2),
    GID_F_CRUISER => array (GID_B_SHIPYARD=>5, GID_R_IMPULSE_DRIVE=>4, GID_R_ION_TECH=>2),
    GID_F_BATTLESHIP => array (GID_B_SHIPYARD=>7, GID_R_HYPER_DRIVE=>4),
    GID_F_COLON => array (GID_B_SHIPYARD=>4, GID_R_IMPULSE_DRIVE=>3),
    GID_F_RECYCLER => array (GID_B_SHIPYARD=>4, GID_R_COMBUST_DRIVE=>6, GID_R_SHIELD=>2),
    GID_F_PROBE => array (GID_B_SHIPYARD=>3, GID_R_COMBUST_DRIVE=>3, GID_R_ESPIONAGE=>2),
    GID_F_BOMBER => array (GID_R_IMPULSE_DRIVE=>6, GID_B_SHIPYARD=>8, GID_R_PLASMA_TECH=>5),
    GID_F_SAT => array (GID_B_SHIPYARD=>1),
    GID_F_DESTRO => array (GID_B_SHIPYARD=>9, GID_R_HYPER_DRIVE=>6, GID_R_HYPERSPACE=>5),
    GID_F_DEATHSTAR => array (GID_B_SHIPYARD=>12, GID_R_HYPER_DRIVE=>7, GID_R_HYPERSPACE=>6, GID_R_GRAVITON=>1),
    GID_F_BATTLECRUISER => array (GID_R_HYPERSPACE=>5, GID_R_LASER_TECH=>12, GID_R_HYPER_DRIVE=>5, GID_B_SHIPYARD=>8),
    GID_D_RL => array (GID_B_SHIPYARD=>1),
    GID_D_LL => array (GID_R_ENERGY=>1, GID_B_SHIPYARD=>2, GID_R_LASER_TECH=>3),
    GID_D_HL => array (GID_R_ENERGY=>3, GID_B_SHIPYARD=>4, GID_R_LASER_TECH=>6),
    GID_D_GAUSS => array (GID_B_SHIPYARD=>6, GID_R_ENERGY=>6, 109=>3, GID_R_SHIELD=>1),
    GID_D_ION => array (GID_B_SHIPYARD=>4, GID_R_ION_TECH=>4),
    GID_D_PLASMA => array (GID_B_SHIPYARD=>8, GID_R_PLASMA_TECH=>7),
    GID_D_SDOME => array (GID_R_SHIELD=>2, GID_B_SHIPYARD=>1),
    GID_D_LDOME => array (GID_R_SHIELD=>6, GID_B_SHIPYARD=>6),
    GID_D_ABM => array (GID_B_MISS_SILO=>2, GID_B_SHIPYARD=>1),
    GID_D_IPM => array (GID_B_MISS_SILO=>4, GID_B_SHIPYARD=>1, GID_R_IMPULSE_DRIVE=>1),
    GID_B_LUNAR_BASE => array (),
    GID_B_PHALANX => array (GID_B_LUNAR_BASE=>1),
    GID_B_JUMP_GATE => array (GID_B_LUNAR_BASE=>1, GID_R_HYPERSPACE=>7),

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
EndContent ();

PageFooter ();
ob_end_flush ();
?>