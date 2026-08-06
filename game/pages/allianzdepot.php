<?php

// Fleet Hold extension by alliance depot

class Allianzdepot extends Page {

    public function controller () : bool {
        global $GlobalUser;
        global $aktplanet;
        global $fleetmap;

        $depot_cap = 10000 * pow ( 2, $aktplanet[GID_B_ALLY_DEPOT] );
        if ($aktplanet[GID_B_ALLY_DEPOT]) $deut_avail = min(floor($aktplanet[GID_RC_DEUTERIUM]), $depot_cap);
        else $deut_avail = 0;

        $loaded = $deut_avail;

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

            $cons = 0;
            foreach ($fleetmap as $i=>$id) {
                $amount = $fleet_obj[$id];
                if ($amount > 0) {
                    $cons += $amount * FleetCons ($id, $user, $aktplanet) / 10;
                }
            }

            if ( key_exists ( "c".$c, $_POST ) ) $hours = abs (intval ( $_POST["c".$c] ));
            else $hours = 0;
            if ( $deut_avail > 0 && $deut_avail >= ($cons*$hours) ) {
                ProlongQueue ($queue['task_id'], $hours * 3600);
                $deut_avail -= ($cons*$hours);
            }

            $c ++;
        }

        $spent = $loaded - $deut_avail;
        if ( $spent > 0 ) {
            $cost = array (GID_RC_DEUTERIUM => $spent);
            AdjustResources ( $cost, $aktplanet['planet_id'], '-' );
        }

        MyGoto ( "infos", "&gid=".GID_B_ALLY_DEPOT );
    }

    public function view () : void {
        // Never called - always redirects
    }
}
?>
