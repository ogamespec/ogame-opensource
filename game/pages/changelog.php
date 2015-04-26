<?php

// История изменений.
$changelog = array ( 
    "0.312apl2", "0.312apl3", "0.312apl4", "0.312apl5", "0.312apl6", "0.312apl7", "0.312apl8", "0.312apl9", "0.312apl10", 
    "0.321a", "0.321apl1", "0.321apl3", "0.322a", "0.323a", "0.324a", "0.325a", "0.325apl1", "0.326a", "0.327a",
    "0.330a", "0.330apl1", "0.330apl2", "0.330apl3", "0.330apl4", "0.331a", "0.331apl1", "0.331apl2", 
    "0.34a", "0.35a", "0.36a", "0.371a", "0.372a", "0.373a", "0.374a", "0.375a", "0.376a", "0.377a", "0.3771a", "0.3778a", "0.3779a",
    "0.38", "0.39", "0.40", "0.41", "0.43", "0.44", "0.45", "0.46", "0.47a", "0.47b", "0.48", "0.49a", "0.49b", 
    "0.50", "0.51", "0.52", "0.53", "0.55a", "0.56a", "0.56b", "0.57a", "0.58a", "0.58b", "0.59a", 
    "0.60a", "0.61a", "0.62a", "0.63a", "0.64a", "0.65a1", "0.66a1", "0.67a", "0.68a", "0.69a", "0.69b", "0.69c",
    "0.70a", "0.70b", "0.70c", "0.70d", "0.71", "0.71b", "0.72", "0.72b", "0.72c", "0.73", "0.73a", "0.73b", "0.73c", "0.73d", "0.73e",
    "0.74", "0.74a", "0.74b", "0.74c", "0.74d", "0.74e", "0.75", "0.75a", "0.76", "0.76a", "0.76b", "0.76c", "0.77", "0.77a", "0.77b", "0.77c", "0.77d",
    "0.78", "0.78a", "0.78b", "0.78c", "0.80", "0.81", "0.82", "0.83", "0.84"
);

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "changelog", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("changelog");
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
<center>
  <table width="668">
   <tr>

    <td class="c"><?=loca('CHANGELOG_VERSION');?></td>
    <td class="c"><?=loca('CHANGELOG_DESC');?></td>
   </tr>

<?php

    $ch = array_reverse ($changelog);

    foreach ( $ch as $i=>$version )
    {
        echo "<tr>\n";
        echo "<th>$version</th>\n";
        echo "<th style=\"text-align:left\">".loca("CHANGELOG_$version")."</th>\n";
        echo "</tr>\n";
    }

?>

</table><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>