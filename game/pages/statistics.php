<?php

// Статистика

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("statistics");

?>

<div id='content'>
<center>

<?php

RecalcStats ( $GlobalUser['player_id'] );

RecalcRanks ();

$query = "SELECT * FROM ".$db_prefix."users ORDER BY place1;";
$result = dbquery ($query);
$rows = dbrows ($result);
while ($rows--){
    print_r ( dbarray ($result) );
    echo "<br/>";
}

PageFooter ();
ob_end_flush ();
?>