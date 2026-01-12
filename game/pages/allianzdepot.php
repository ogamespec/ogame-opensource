<?php

/** @var array $GlobalUser */
/** @var array $fleetmap */

// Fleet Hold extension by alliance depot

// A launched supply missile refuels fleets in turn,
// with refueling discrete to fleet consumption per hour.
// A missile cannot unload less fuel into the fleet than it consumes in a specified number of hours.

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
    if ($user == null) {
        $user = array (GID_R_COMBUST_DRIVE => 0, GID_R_IMPULSE_DRIVE => 0, GID_R_HYPER_DRIVE => 0);
    }

    // Calculate fleet consumption per hour.
    $cons = 0;
    foreach ($fleetmap as $i=>$id) {
        $amount = $fleet_obj["ship".$id];
        if ($amount > 0) { 
            $cons += $amount * FleetCons ($id, $user[GID_R_COMBUST_DRIVE], $user[GID_R_IMPULSE_DRIVE], $user[GID_R_HYPER_DRIVE]) / 10;
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
?>