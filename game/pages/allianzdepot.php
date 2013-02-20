<?php

// Продление флота на удержании складом альянса

// Запущенная ракета снабжения по очереди заправляет флоты,
// при этом заправка дискретна потреблению флота в час.
// Ракета не может выгрузить во флот топлива меньше, чем он потребляет в указанное количество часов.

$DepotError = "";

loca_add ( "common" );
loca_add ( "menu", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( &$aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("allianzdepot");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

print_r ( $_POST );

// Запустить ракету со снабжением
if ( $DepotError === "" )
{

    // Сделать редирект на склад альянса
    MyGoto ( "infos", "&cp=$target_id&gid=34" );
}

echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n\n";

PageFooter ("", $DepotError);
ob_end_flush ();
?>