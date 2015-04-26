<?php

// Технологии.

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

PageHeader ("techtree");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

// **************************************************************************************
// Список объектов что-чему требуется по категориям.

$req_building = array (
    "name" => loca("TECHTREE_BUILDINGS"),
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
);

$req_research = array (
    "name" => loca("TECHTREE_RESEARCH"),
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
);

$req_fleet = array (
    "name" => loca("TECHTREE_FLEET"),
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
);

$req_defense = array (
    "name" => loca("TECHTREE_DEFENSE"),
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
);

$req_special = array (
    "name" => loca("TECHTREE_SPECIAL"),
    41 => array (),
    42 => array (41=>1),
    43 => array (41=>1, 114=>7),
);

$reqs = array ( $req_building, $req_research, $req_fleet, $req_defense, $req_special );

function MeetRequirement ( $user, $planet, $id, $level )
{
    if ($id > 100) return $user['r'.$id] >= $level;
    else return $planet['b'.$id] >= $level;
}

echo "<center> \n";
echo "<table width=470> \n";

foreach ($reqs as $i => $req )
{
    foreach ($req as $c => $entry )
    {
        if ( $c === "name" ) echo "<tr><td class=c>$entry</td><td class=c>".loca("TECHTREE_REQUIRED")."</td></tr> \n";
        else
        {
            if (count ($entry) == 0) $details = "&nbsp;";
            else $details = "<a href=\"index.php?page=techtreedetails&session=".$_GET['session']."&tid=$c\">[i]</a>";

            echo "<tr> \n";
            echo "<td class=l> \n";
            echo "<table width=\"100%\" border=0 cellspacing=0 cellpadding=0><tr><td align=left><a href=\"index.php?page=infos&session=".$_GET['session']."&gid=$c\">".loca("NAME_$c")."</a> \n";
            echo "</td><td align=right>$details</td></tr></table></td> \n";

            echo "<td class=l> \n";
            foreach ($entry as $obj => $lvl ) {
                $ok = MeetRequirement ( $GlobalUser, $aktplanet, $obj, $lvl );
                if ($ok) echo "<font color=\"#00ff00\">".loca("NAME_$obj")." ".va(loca("TECHTREE_LEVEL"), $lvl)."</font><br /> \n";
                else echo "<font color=\"#ff0000\">".loca("NAME_$obj")." ".va(loca("TECHTREE_LEVEL"), $lvl)."</font><br /> \n";
            }
            echo "</td> \n";
        }
    }
    echo "\n";
}
echo "</table> \n";

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ();
ob_end_flush ();
?>