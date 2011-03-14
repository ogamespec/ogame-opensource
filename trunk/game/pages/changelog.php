<?php

// История изменений.
$changelog = array ( 
    "0.312apl10", "0.312apl2", "0.312apl3",
    "0.80", "0.81", "0.82", "0.83", "0.84"
);

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
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

    <td class="c"><?=loca("CHANGELOG_VERSION");?></td>
    <td class="c"><?=loca("CHANGELOG_DESC");?></td>
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