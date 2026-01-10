<?php

$GlobalUser = $GLOBALS['GlobalUser'];
$GlobalUni = $GLOBALS['GlobalUni'];

loca_add ( "menu", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
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

PageHeader ("tipoftheday");

BeginContent ();
?>

<?=loca("BOGUS_MOD_TIP1");?>

<?php
EndContent ();
PageFooter ();
ob_end_flush ();
?>