<?php

// Creating a list of Phalanx events.

require_once "event_list.php";

function PhalanxEventList (int $planet_id) : void
{
    $planet = LoadPlanetById ($planet_id);
    /** @var array $user */
    $user = LoadUser ($planet['owner_id']);
    $result = EnumPlanetFleets ( $planet_id );
    $rows = dbrows ( $result );

    $task = array ();
    $tasknum = 0;

    $unions = array ();

    while ($rows--)
    {
        $fleet_obj = dbarray ($result);
        $queue = GetFleetQueue ($fleet_obj['fleet_id']);

        // Union fleets are assembled separately
        if ( $fleet_obj['union_id'] > 0 && $fleet_obj['target_planet'] == $planet_id && !key_exists($fleet_obj['union_id'], $unions) )
        {
            $task[$tasknum]['end_time'] = $queue['end'];

            // Fleets
            $acs_result = EnumUnionFleets ( $fleet_obj['union_id'] );
            $task[$tasknum]['fleets'] = $acs_rows = dbrows ( $acs_result );
            $f = 0;
            while ($acs_rows--)
            {
                $fleet_obj = dbarray ($acs_result);

                $task[$tasknum]['fleet'][$f] = EventFleetRow ($fleet_obj, $fleet_obj['start_planet'], $fleet_obj['target_planet'], GetMission ($fleet_obj), 0);
                $f++;
            }
            // Mark that this ACS union has been processed and ignore the rest of the fleets on further enumeration
            $unions[ $fleet_obj['union_id'] ] = 1;

            $tasknum++;
            continue;
        }

        if ( $fleet_obj['union_id'] > 0 && $fleet_obj['target_planet'] == $planet_id && $fleet_obj['mission'] != FTYP_ACS_ATTACK_HEAD ) continue;

        // Do not show departure and return for Deploy mission.
        if ( $fleet_obj['mission'] == (FTYP_RETURN+FTYP_DEPLOY) ) continue;
        if ( $fleet_obj['mission'] == FTYP_DEPLOY && $fleet_obj['start_planet'] == $planet_id ) continue;

        // Do not show fleets returning from the target planet.
        if ( ($fleet_obj['mission'] > FTYP_RETURN && $fleet_obj['mission'] < FTYP_ORBITING) && $fleet_obj['target_planet'] == $planet_id ) continue;

        // For a departing expedition, add a hold pseudo-task.
        // Don't show foreign fleets.
        if ( $fleet_obj['mission'] == FTYP_EXPEDITION && $fleet_obj['owner_id'] == $user['player_id'] )
        {
            // Departure and arrival times
            $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = EventFleetRow ($fleet_obj, $fleet_obj['start_planet'], $fleet_obj['target_planet'], GetMission ($fleet_obj), 2);
            $tasknum++;
        }

        // For the arriving Hold task, add a hold pseudo-hold task.
        if ( $fleet_obj['mission'] == FTYP_ACS_HOLD && $fleet_obj['owner_id'] != $user['player_id'] )
        {
            // Departure and arrival times
            $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = EventFleetRow ($fleet_obj, $fleet_obj['start_planet'], $fleet_obj['target_planet'], GetMission ($fleet_obj), 2);
            $tasknum++;
        }

        // Arrival time
        if ( $fleet_obj['mission'] < FTYP_RETURN && $fleet_obj['start_planet'] == $planet_id ) {
            if ($fleet_obj['mission'] != FTYP_EXPEDITION) $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['flight_time'];
            else $task[$tasknum]['end_time'] = $queue['end'];
        }
        else $task[$tasknum]['end_time'] = $queue['end'];

        // Fleet
        if ( GetMission($fleet_obj) == FTYP_EXPEDITION || GetMission($fleet_obj) == FTYP_ACS_HOLD )
        {
            if ($fleet_obj['mission'] < FTYP_RETURN) $dir = 0;
            else if ($fleet_obj['mission'] < FTYP_ORBITING) $dir = 1;
            else $dir = 2;
        }
        else
        {
            if ( $fleet_obj['target_planet'] == $planet_id ) $dir = 0;    // to the planet
            else $dir = 1;    // return
        }

        $task[$tasknum]['fleets'] = 1;
        $task[$tasknum]['fleet'][0] = EventFleetRow ($fleet_obj, $fleet_obj['start_planet'], $fleet_obj['target_planet'], GetMission ( $fleet_obj ), $dir);

        $tasknum++;

        // For departing or holding expeditions, add a pseudo-return task.
        if ( ($fleet_obj['mission'] == FTYP_EXPEDITION || $fleet_obj['mission'] == (FTYP_ORBITING+FTYP_EXPEDITION) ) && $fleet_obj['owner_id'] == $user['player_id'] )
        {
            // Departure and arrival times
            if ( $fleet_obj['mission'] > FTYP_ORBITING) $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'];
            else $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['deploy_time'] + $fleet_obj['flight_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = EventFleetRow ($fleet_obj, $fleet_obj['start_planet'], $fleet_obj['target_planet'], GetMission ($fleet_obj), 1);
            $tasknum++;
        }

    }

    EventListOutput ($task, EVENT_LIST_PHALANX);
}

?>
