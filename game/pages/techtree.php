<?php

/** @var array $GlobalUser */
/** @var array $buildmap */
/** @var array $resmap */
/** @var array $fleetmap */
/** @var array $defmap */
/** @var array $requrements */

// Technologies.

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

PageHeader ("techtree");

BeginContent ();

// **************************************************************************************
// A list of what-what-it-requires objects by category.

$req_building = array (
    "loca" => loca("TECHTREE_BUILDINGS"),
    "techs" => array_diff($buildmap, [GID_B_LUNAR_BASE, GID_B_PHALANX, GID_B_JUMP_GATE])
);

$req_research = array (
    "loca" => "TECHTREE_RESEARCH",
    "techs" => $resmap
);

$req_fleet = array (
    "loca" => "TECHTREE_FLEET",
    "techs" => $fleetmap
);

$req_defense = array (
    "loca" => "TECHTREE_DEFENSE",
    "techs" => $defmap
);

$req_special = array (
    "loca" => "TECHTREE_SPECIAL",
    "techs" => array (GID_B_LUNAR_BASE, GID_B_PHALANX, GID_B_JUMP_GATE)
);

$techtree = array ( 'building' => $req_building, 'research' => $req_research, 'fleet' => $req_fleet, 'defense' => $req_defense, 'special' => $req_special );

function MeetRequirement ( array $user, array $planet, int $id, int $level ) : bool
{
    if (IsResearch($id)) return $user['r'.$id] >= $level;
    else return $planet['b'.$id] >= $level;
}

echo "<center> \n";
echo "<table width=470> \n";

foreach ($techtree as $i => $req )
{
    echo "<tr><td class=c>".loca($req['loca'])."</td><td class=c>".loca("TECHTREE_REQUIRED")."</td></tr> \n";

    foreach ($req['techs'] as $tech) {

        if (count ($requrements[$tech]) == 0) $details = "&nbsp;";
        else $details = "<a href=\"index.php?page=techtreedetails&session=".$_GET['session']."&tid=$tech\">[i]</a>";

        echo "<tr> \n";
        echo "<td class=l> \n";
        echo "<table width=\"100%\" border=0 cellspacing=0 cellpadding=0><tr><td align=left><a href=\"index.php?page=infos&session=".$_GET['session']."&gid=$tech\">".loca("NAME_$tech")."</a> \n";
        echo "</td><td align=right>$details</td></tr></table></td> \n";

        echo "<td class=l> \n";
        foreach ($requrements[$tech] as $obj => $lvl ) {
            $ok = MeetRequirement ( $GlobalUser, $aktplanet, $obj, $lvl );
            if ($ok) $color = '#00ff00';
            else $color = '#ff0000';
            echo "<font color=\"$color\">".loca("NAME_$obj")." ".va(loca("TECHTREE_LEVEL"), $lvl)."</font><br /> \n";
        }
        echo "</td> \n";
    }

    echo "\n";
}
echo "</table> \n";

echo "<br><br><br><br>\n";
EndContent ();

PageFooter ();
ob_end_flush ();
?>