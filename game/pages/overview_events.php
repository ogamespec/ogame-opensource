<?php

// Creating a list of Overview events.

require_once "event_list.php";

function EventList () : void
{
    global $GlobalUser;

    // Single fleets
    $tasklist = EnumFleetQueue ( $GlobalUser['player_id'] );
    $rows = dbrows ($tasklist);
    $task = array ();
    $tasknum = 0;
    while ($rows--)
    {
        $queue = dbarray ($tasklist);

        $fleet_obj = LoadFleet ( $queue['sub_id'] );
        if ( $fleet_obj['union_id'] > 0 ) continue;        // Union fleets are assembled separately

        // For a departing expedition or ACS Hold, add a hold pseudo-task.
        // Don't show foreign fleets.
        if ( ($fleet_obj['mission'] == FTYP_ACS_HOLD || $fleet_obj['mission'] == FTYP_EXPEDITION) && $fleet_obj['owner_id'] == $GlobalUser['player_id'] )
        {
            // Departure and arrival times
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = EventFleetRow ($fleet_obj, $fleet_obj['start_planet'], $fleet_obj['target_planet'], GetMission ($fleet_obj), 2, 0);
            $tasknum++;
        }

        // For the arriving Hold task, add a hold pseudo-hold task.
        if ( $fleet_obj['mission'] == FTYP_ACS_HOLD && $fleet_obj['owner_id'] != $GlobalUser['player_id'] )
        {
            // Departure and arrival times
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'];

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = EventFleetRow ($fleet_obj, $fleet_obj['start_planet'], $fleet_obj['target_planet'], GetMission ($fleet_obj), 2, 2);
            $tasknum++;
        }

        // Departure and arrival times
        $task[$tasknum]['start_time'] = $queue['start'];
        $task[$tasknum]['end_time'] = $queue['end'];

        // Fleet
        $task[$tasknum]['fleets'] = 1;
        if ( $fleet_obj['mission'] < FTYP_RETURN || $fleet_obj['mission'] > FTYP_ORBITING ) {
            $origin_id = $fleet_obj['start_planet'];
            $target_id = $fleet_obj['target_planet'];
        }
        else
        {
            $origin_id = $fleet_obj['target_planet'];
            $target_id = $fleet_obj['start_planet'];
        }
        $task[$tasknum]['fleet'][0] = EventFleetRow ($fleet_obj, $origin_id, $target_id, GetMission ($fleet_obj), 0, 0, true);
        GetDirectionAssignment (true, $fleet_obj, $task[$tasknum]['fleet'][0]['dir'], $task[$tasknum]['fleet'][0]['assign'] );

        $tasknum++;

        // For departing or holding fleets, add a pseudo-return task.
        // Do not show the returns of other people's fleets, Deploy mission and Missile Attack.
        if ( ($fleet_obj['mission'] < FTYP_RETURN || $fleet_obj['mission'] > FTYP_ORBITING) && $fleet_obj['owner_id'] == $GlobalUser['player_id'] && $fleet_obj['mission'] != FTYP_DEPLOY && $fleet_obj['mission'] != FTYP_MISSILE )
        {
            // Departure and arrival times
            $task[$tasknum]['start_time'] = $queue['end'];
            $task[$tasknum]['end_time'] = 2 * $queue['end'] - $queue['start'];
            if ( GetMission ($fleet_obj) == FTYP_ACS_HOLD || GetMission ($fleet_obj) == FTYP_EXPEDITION ) {
                if ( $fleet_obj['mission'] > FTYP_ORBITING) $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'];
                else $task[$tasknum]['end_time'] = $task[$tasknum]['start_time'] + $fleet_obj['deploy_time'] + $fleet_obj['flight_time'];
            }

            // Fleet
            $task[$tasknum]['fleets'] = 1;
            $task[$tasknum]['fleet'][0] = EventFleetRow ($fleet_obj, $fleet_obj['target_planet'], $fleet_obj['start_planet'], GetMission ($fleet_obj), 1, 0);
            $tasknum++;
        }
    }

    // Union fleets
    $unions = EnumUnion ( $GlobalUser['player_id'] );
    foreach ( $unions as $u=>$union)
    {
        // Fleets
        $result = EnumUnionFleets ( $union['union_id'] );
        $rows = dbrows ( $result );
        $friendly = $union['target_player'] != $GlobalUser['player_id'];        // If the union is flying NOT at the player, then it is a friendly ACS attack

        if ( $rows > 0 )    // Do not show empty unions.
        {
            $task[$tasknum]['fleets'] = $rows;
            $f = 0;
            $tn = $tasknum;
            while ($rows--)
            {
                $fleet_obj = dbarray ($result);

                $queue = GetFleetQueue ($fleet_obj['fleet_id']);
                $task[$tn]['end_time'] = $queue['end'];

                // For departing or holding fleets, add a pseudo-return task.
                // Do not show the returns of other people's fleets and Deploy mission.
                if ( $fleet_obj['mission'] < FTYP_RETURN && $fleet_obj['owner_id'] == $GlobalUser['player_id'] )
                {
                    $tasknum++;

                    // Departure and arrival times
                    $task[$tasknum]['end_time'] = $queue['end'] + $fleet_obj['flight_time'];

                    // Fleet
                    $task[$tasknum]['fleets'] = 1;
                    $task[$tasknum]['fleet'][0] = EventFleetRow ($fleet_obj, $fleet_obj['target_planet'], $fleet_obj['start_planet'], GetMission ($fleet_obj), 1, 0);
                }

                // Fleet
                $task[$tn]['fleet'][$f] = EventFleetRow ($fleet_obj, $fleet_obj['start_planet'], $fleet_obj['target_planet'], GetMission ($fleet_obj), 0, 0, true);
                GetDirectionAssignment ($friendly, $fleet_obj, $task[$tn]['fleet'][$f]['dir'], $task[$tn]['fleet'][$f]['assign'] );
                $f++;
            }

            $tasknum++;
        }
    }

    EventListOutput ($task, EVENT_LIST_OVERVIEW);
}

?>
