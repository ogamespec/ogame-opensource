<?php

// Продление флота на удержании складом альянса

// Запущенная ракета снабжения по очереди заправляет флоты,
// при этом заправка дискретна потреблению флота в час.
// Ракета не может выгрузить во флот топлива меньше, чем он потребляет в указанное количество часов.

$DepotError = "";

loca_add ( "common", $GlobalUni['lang'] );
loca_add ( "menu", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("allianzdepot");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

$fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

// Запустить ракету со снабжением

$depot_cap = 10000 * pow ( 2, $aktplanet['b34'] );
if ($aktplanet['b34']) $deut_avail = min(floor($aktplanet['d']), $depot_cap);
else $deut_avail = 0;

$loaded = $deut_avail;

// Отправить ракету поочередности к каждому флоту
$result = GetHoldingFleets ($aktplanet['planet_id']);
$rows = dbrows ($result);
$c = 1;
while ($rows--)
{
    if ( $deut_avail == 0 ) break;

    $fleet_obj = dbarray ( $result );
    $queue = GetFleetQueue ( $fleet_obj['fleet_id'] );
    $user = LoadUser ($fleet_obj['owner_id']);

    // Посчитать потребление флота в час.
    $cons = 0;
    foreach ($fleetmap as $i=>$id) {
        $amount = $fleet_obj["ship".$id];
        if ($amount > 0) { 
            $cons += $amount * FleetCons ($id, $user['r115'], $user['r117'], $user['r118']) / 10;
        }
    }

    // Заправить флот
    if ( key_exists ( "c".$c, $_POST ) ) $hours = abs (intval ( $_POST["c".$c] ));
    else $hours = 0;
    if ( $deut_avail > 0 && $deut_avail >= ($cons*$hours) ) {
        ProlongQueue ($queue['task_id'], $hours * 3600);
        $deut_avail -= ($cons*$hours);
    }

    $c ++;
}

// Модифицировать ресурсы на планете
$spent = $loaded - $deut_avail;
if ( $spent > 0 ) AdjustResources ( 0, 0, $spent, $aktplanet['planet_id'], '-' );

// Сделать редирект на склад альянса
MyGoto ( "infos", "&gid=34" );

echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n\n";

PageFooter ("", $DepotError);
ob_end_flush ();
?>