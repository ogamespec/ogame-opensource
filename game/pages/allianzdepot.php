<?php

// Fleet Hold extension by alliance depot

// A launched supply missile refuels fleets in turn,
// with refueling discrete to fleet consumption per hour.
// A missile cannot unload less fuel into the fleet than it consumes in a specified number of hours.

$DepotError = "";

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("allianzdepot");

BeginContent();

$fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

// Launch a rocket with supplies

$depot_cap = 10000 * pow ( 2, $aktplanet['b'.GID_B_ALLY_DEPOT] );
if ($aktplanet['b'.GID_B_ALLY_DEPOT]) $deut_avail = min(floor($aktplanet['d']), $depot_cap);
else $deut_avail = 0;

$loaded = $deut_avail;

// Send a missile to each fleet in turn
$result = GetHoldingFleets ($aktplanet['planet_id']);
$rows = dbrows ($result);
$c = 1;
while ($rows--)
{
    if ( $deut_avail == 0 ) break;

    $fleet_obj = dbarray ( $result );
    $queue = GetFleetQueue ( $fleet_obj['fleet_id'] );
    $user = LoadUser ($fleet_obj['owner_id']);

    // Calculate fleet consumption per hour.
    $cons = 0;
    foreach ($fleetmap as $i=>$id) {
        $amount = $fleet_obj["ship".$id];
        if ($amount > 0) { 
            $cons += $amount * FleetCons ($id, $user['r'.GID_R_COMBUST_DRIVE], $user['r'.GID_R_IMPULSE_DRIVE], $user['r'.GID_R_HYPER_DRIVE]) / 10;
        }
    }

    // Refuel the fleet
    if ( key_exists ( "c".$c, $_POST ) ) $hours = abs (intval ( $_POST["c".$c] ));
    else $hours = 0;
    if ( $deut_avail > 0 && $deut_avail >= ($cons*$hours) ) {
        ProlongQueue ($queue['task_id'], $hours * 3600);
        $deut_avail -= ($cons*$hours);
    }

    $c ++;
}

// Modify the resources on the planet
$spent = $loaded - $deut_avail;
if ( $spent > 0 ) AdjustResources ( 0, 0, $spent, $aktplanet['planet_id'], '-' );

// Redirect to the alliance depot
MyGoto ( "infos", "&gid=".GID_B_ALLY_DEPOT );

EndContent ();

PageFooter ("", $DepotError);
ob_end_flush ();
?>