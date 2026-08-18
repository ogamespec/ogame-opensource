<?php

// Sending fleet with all parameters checked.

class Flottenversand extends Page {

    public function controller () : bool {
        global $GlobalUser;
        global $GlobalUni;
        global $fleetmap;
        global $transportableResources;
        global $aktplanet;
        global $db_prefix;
        global $PageError;
        global $now;

        $this->BlockAttack = 0;
        $this->FleetError = false;
        $this->FleetErrorText = "";
        $this->fleetmap = $fleetmap;
        $this->transportableResources = $transportableResources;
        $this->unispeed = $GlobalUni['fspeed'];

        // If the page is opened through a browser, make a redirect to the main page.
        if ( method () === "GET" ) {
            return false;    // Signal to not render view
        }

        // Handle AJAX requests
        if ( key_exists ( 'ajax', $_GET ) ) {
            if ( $_GET['ajax'] == 1) include "flottenversand_ajax.php";
            return true;
        }

        // You cannot send a fleet if the previous one was sent less than a second ago.
        $result = EnumOwnFleetQueueSpecial ( $GlobalUser['player_id'] );
        $rows = dbrows ($result);
        if ( $rows ) {
            $queue = dbarray ($result);
            if ( abs(time () - $queue['start']) < 1 ) {
                MyGoto ( "flotten1" );
            }
        }

        $result = EnumOwnFleetQueue ( $GlobalUser['player_id'] );
        $this->nowfleet = dbrows ($result);
        $this->maxfleet = $this->maxfleet_no_bonus = 0;
        GetMaxFleet ($GlobalUser, $aktplanet, $this->maxfleet, $this->maxfleet_no_bonus);

        // Limit the speed and make it a multiple of 10.
        $fleetspeed = round ( abs(intval($_POST['speed']) * 10) / 10) * 10;
        $fleetspeed = min ( max (10, $fleetspeed), 100 ) / 10;

        // Turn all empty parameters into zeros.

        $this->resource = array();
        foreach ($this->transportableResources as $i=>$rc) {
            if ( !key_exists('resource'.($i+1), $_POST) ) $_POST['resource'.($i+1)] = 0;
            $this->resource[$i+1] = min ( intval($aktplanet[$rc]), abs(intval($_POST['resource'.($i+1)])) );        
        }

        $this->order = intval($_POST['order']);
        $this->union_id = 0;

        // Fleet List.
        $this->fleet = array ();
        foreach ($this->fleetmap as $i=>$gid) 
        {
            $this->fleet[$gid] = 0;
            if (isset($aktplanet[$gid])) {
                if ( key_exists("ship$gid", $_POST) ) $this->fleet[$gid] = min ( $aktplanet[$gid], intval($_POST["ship$gid"]) );
            }
        }
        $this->fleet[GID_F_SAT] = 0;        // solar satellites can't fly.

        $this->origin = LoadPlanet ( intval($_POST['thisgalaxy']), intval($_POST['thissystem']), intval($_POST['thisplanet']), intval($_POST['thisplanettype']) );
        $this->target = LoadPlanet ( intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet']), intval($_POST['planettype']) );

        if ( $GlobalUni['freeze'] ) $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_FREEZE")."</span></th>\n  </tr>\n"; $this->FleetError = true;

        if (  ( $_POST['thisgalaxy'] == $_POST['galaxy'] ) &&
                ( $_POST['thissystem'] == $_POST['system'] ) &&
                ( $_POST['thisplanet'] ==  $_POST['planet'] ) &&
                ( $_POST['thisplanettype'] == $_POST['planettype'] ) 
          ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_SAME_PLANET")."</span></th>\n  </tr>\n"; $this->FleetError = true; }

        if (
             (intval($_POST['galaxy']) < 1 || intval($_POST['galaxy']) > $GlobalUni['galaxies'])  ||
             (intval($_POST['system']) < 1 || intval($_POST['system']) > $GlobalUni['systems'])  ||
             (intval($_POST['planet']) < 1 || intval($_POST['planet']) > 16)
         ) {
            $PageError = "Cheater!";
            $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_INVALID")."</span></th>\n  </tr>\n"; $this->FleetError = true;
            $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">Cheater!</span></th>\n  </tr>\n"; $this->FleetError = true;
        }

        $this->origin_user = LoadUser ( $this->origin['owner_id'] );

        if ($this->target != null) {

            $this->target_user = LoadUser ( $this->target['owner_id'] );

            if ( $this->origin_user['vacation'] ) $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_VACATION_SELF")."</span></th>\n  </tr>\n"; $this->FleetError = true;
            if ( $this->target_user['vacation'] && $this->order != FTYP_RECYCLE ) $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_VACATION_OTHER")."</span></th>\n  </tr>\n"; $this->FleetError = true;
            if ( $this->nowfleet >= $this->maxfleet ) $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_MAX_FLEET")."</span></th>\n  </tr>\n"; $this->FleetError = true;

            // DO NOT check fleet dispatch between players with the same IP only if BOTH have IP checking disabled in the settings.
            // OR if the sent is on localhost (local web server for debugging)
            if ( ! ($this->origin_user['deact_ip'] && $this->target_user['deact_ip']) && !localhost($this->origin_user['ip_addr']) )
            {
                if ( $this->origin_user['ip_addr'] === $this->target_user['ip_addr'] && $this->origin_user['player_id'] != $this->target_user['player_id'] ) $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_IP")."</span></th>\n  </tr>\n"; $this->FleetError = true;
            }
        }

        // Hold time
        $this->hold_time = 0;
        if ( $this->order == FTYP_EXPEDITION ) {
            if ( key_exists ('expeditiontime', $_POST) ) {
                $this->hold_time = floor (intval($_POST['expeditiontime']));
                if ( $this->hold_time > $GlobalUser[GID_R_EXPEDITION] ) $this->hold_time = $GlobalUser[GID_R_EXPEDITION];
                if ( $this->hold_time < 1 ) $this->hold_time = 1;
            }
            else $this->hold_time = 1;
            $this->hold_time *= 60*60;        // convert to seconds
        }
        else if ( $this->order == FTYP_ACS_HOLD ) {
            if ( key_exists ('holdingtime', $_POST) ) {
                $this->hold_time = floor (intval($_POST['holdingtime']));
                if ( $this->hold_time > 32 ) $this->hold_time = 32;
                if ( $this->hold_time < 0 ) $this->hold_time = 0;
            }
            else $this->hold_time = 0;
            $this->hold_time *= 60*60;        // convert to seconds
        }

        // Calculate distance, flight time, and deuterium costs.
        $this->dist = FlightDistance ( intval($_POST['thisgalaxy']), intval($_POST['thissystem']), intval($_POST['thisplanet']), intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet']) );
        $this->slowest_speed = FlightSpeed ( $this->fleet, $this->origin_user, $this->origin );
        $this->flighttime = FlightTime ( $this->dist, $this->slowest_speed, $fleetspeed / 10, $this->unispeed );
        $cons = FlightCons ( $this->fleet, $this->dist, $this->flighttime, $this->origin_user, $this->origin, $this->unispeed, $this->hold_time / 3600 );
        $this->cargo = $this->spycargo = $this->numships = 0;

        foreach ($this->fleet as $id=>$amount)
        {
            if ($id != GID_F_PROBE) $this->cargo += FleetCargo ($id) * $amount;        // not counting probes.
            else $this->spycargo = FleetCargo ($id) * $amount;
            $this->numships += $amount;
        }

        $space = ( ($this->cargo + $this->spycargo) - ($cons['fleet'] + $cons['probes']) ) - ($this->spycargo - $cons['probes']);

        if ( $this->origin[GID_RC_DEUTERIUM] < ($cons['fleet'] + $cons['probes']) ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_FUEL")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
        else if ( $space < 0 ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_CARGO")."</span></th>\n  </tr>\n"; $this->FleetError = true; }

        // Limit transported resources to fleet payload capacity and flight costs.
        $this->resources = array();
        foreach ($this->transportableResources as $i=>$rc) {
            if ( $space > 0 ) {
                $this->resources[$rc] = min ( $space, $this->resource[$i+1] );
                $space -= $this->resources[$rc];
            }
            else {
                $this->resources[$rc] = 0;
            }
        }

        if ($this->numships <= 0) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_NO_SHIPS")."</span></th>\n  </tr>\n"; $this->FleetError = true; }

        $this->success = !$this->FleetError;

        if (!$this->success) {
            return true;
        }

        switch ( $this->order )
        {
            case FTYP_ATTACK:        // Attack
                if ( $this->target == NULL ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_INVALID")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( ( 
                    ( $this->origin_user['ally_id'] == $this->target_user['ally_id'] && $this->origin_user['ally_id'] > 0 )   || 
                     IsBuddy ( $this->origin_user['player_id'],  $this->target_user['player_id']) ) ) $this->BlockAttack = 0;

                if ( IsPlayerNewbie ($this->target['owner_id']) || IsPlayerStrong ($this->target['owner_id']) ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_NOOB")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( $this->target['owner_id'] == $this->origin['owner_id'] ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_OWN_PLANET")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->BlockAttack) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_ATTACK_BAN_UNI")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($GlobalUser['noattack']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".va ( loca("FLEET_ERR_ATTACK_BAN_PLAYER"), date ( "d.m.Y H:i:s", $GlobalUser['noattack_util']))."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->numships > $GlobalUni['battle_max']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_BATTLE_MAX")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                break;

            case FTYP_ACS_ATTACK:        // ACS Attack
                if ( ( 
                    ( $this->origin_user['ally_id'] == $this->target_user['ally_id'] && $this->origin_user['ally_id'] > 0 )   || 
                     IsBuddy ( $this->origin_user['player_id'],  $this->target_user['player_id']) ) ) $this->BlockAttack = 0;

                if ( key_exists ('union2', $_POST) ) $this->union_id = floor (intval($_POST['union2']));
                else $this->union_id = 0;
                if ( $GlobalUni['acs'] == 0 ) $this->union_id = 0;
                $this->union = LoadUnion ($this->union_id);
                $head_queue = GetFleetQueue ( $this->union['fleet_id'] );
                $acs_flighttime = $head_queue['end'] - time();
                $enum_result = EnumUnionFleets ($this->union_id);
                $acs_fleets = dbrows ($enum_result);
                if ( ! IsPlayerInUnion ( $GlobalUser['player_id'], $this->union) || $this->union == null ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_ACS_OTHER")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( $this->target['owner_id'] == $this->origin['owner_id'] ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_OWN_PLANET")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( IsPlayerNewbie ($this->target['owner_id']) || IsPlayerStrong ($this->target['owner_id']) ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_NOOB")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( $this->flighttime > $acs_flighttime * 1.3 ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_ACS_SLOW")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->BlockAttack) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_ATTACK_BAN_UNI")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($GlobalUser['noattack']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".va ( loca("FLEET_ERR_ATTACK_BAN_PLAYER"), date ( "d.m.Y H:i:s", $GlobalUser['noattack_util']))."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($acs_fleets >= $GlobalUni['acs'] * $GlobalUni['acs']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".va (loca("FLEET_ERR_ACS_LIMIT"), $GlobalUni['acs'] * $GlobalUni['acs'])."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->numships + GetUnionUnitsCount($this->union['union_id']) > $GlobalUni['battle_max']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_BATTLE_MAX")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                break;

            case FTYP_TRANSPORT:        // Transport
                if ( $this->target == NULL ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_INVALID")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                break;

            case FTYP_DEPLOY:        // Deploy
                if ( $this->target['owner_id'] !== $GlobalUser['player_id'] ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_DEPLOY_OTHER")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                break;

            case FTYP_ACS_HOLD:        // ACS Hold
                $maxhold_fleets = $GlobalUni['acs'] * $GlobalUni['acs'];
                $maxhold_users = $GlobalUni['acs'];
                if ( IsPlayerNewbie ($this->target['owner_id']) || IsPlayerStrong ($this->target['owner_id']) ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_NOOB")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                if ( $this->target == NULL ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_INVALID")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                if ( GetHoldingFleetsCount ($this->target['planet_id']) >= $maxhold_fleets ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".va(loca("FLEET_ERR_HOLD_FLEET_LIMIT"), $maxhold_fleets)."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                if ( $this->numships + GetHoldingUnitsCount($this->target['planet_id']) > $GlobalUni['battle_max'] ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_BATTLE_MAX")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                if ( ! CanStandHold ( $this->target['planet_id'], $this->origin['owner_id'], $maxhold_users ) ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".va(loca("FLEET_ERR_HOLD_PLAYER_LIMIT"), $maxhold_users)."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                if ( ! ( ( $this->origin_user['ally_id'] == $this->target_user['ally_id'] && $this->origin_user['ally_id'] > 0 )   || IsBuddy ( $this->origin_user['player_id'],  $this->target_user['player_id']) ) ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_HOLD_ALLY")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                break;

            case FTYP_SPY:        // Espionage
                if ( $this->target == NULL ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_INVALID")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( ( 
                    ( $this->origin_user['ally_id'] == $this->target_user['ally_id'] && $this->origin_user['ally_id'] > 0 )   || 
                     IsBuddy ( $this->origin_user['player_id'],  $this->target_user['player_id']) ) ) $this->BlockAttack = 0;

                if ( $this->target['owner_id'] == $this->origin['owner_id'] ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_SPY_OWN")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( IsPlayerNewbie ($this->target['owner_id']) || IsPlayerStrong ($this->target['owner_id']) ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_SPY_NOOB")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( $this->fleet[GID_F_PROBE] == 0 ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_SPY_REQUIRED")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->BlockAttack) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_ATTACK_BAN_UNI")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($GlobalUser['noattack']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".va ( loca("FLEET_ERR_ATTACK_BAN_PLAYER"), date ( "d.m.Y H:i:s", $GlobalUser['noattack_util']))."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->numships > $GlobalUni['battle_max']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_BATTLE_MAX")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                break;

            case FTYP_COLONIZE:        // Colonize
                if ( $this->fleet[GID_F_COLON] == 0 ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_COLONY_REQUIRED")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if (HasPlanet (intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet'])) ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_COLONY_EXISTS")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else {
                    // If a colonizer is sent - add a colonization phantom.
                    $id = CreateColonyPhantom ( intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet']), USER_SPACE );
                    $this->target = LoadPlanetById ($id);
                }
                break;

            case FTYP_RECYCLE:        // Recycle
                if ( $this->fleet[GID_F_RECYCLER] == 0 ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_RECYCLE_REQUIRED")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->target['type'] != PTYP_DF ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_RECYCLE_DF")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                break;

            case FTYP_DESTROY:        // Destroy (moon)
                if ( $this->target == NULL ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_INVALID")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( ( 
                    ( $this->origin_user['ally_id'] == $this->target_user['ally_id'] && $this->origin_user['ally_id'] > 0 )   || 
                     IsBuddy ( $this->origin_user['player_id'],  $this->target_user['player_id']) ) ) $this->BlockAttack = 0;

                if ( $this->fleet[GID_F_DEATHSTAR] == 0 ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_DESTROY_REQUIRED")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->target['type'] != PTYP_MOON ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_DESTROY_MOON")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->BlockAttack) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_ATTACK_BAN_UNI")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($GlobalUser['noattack']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".va ( loca("FLEET_ERR_ATTACK_BAN_PLAYER"), date ( "d.m.Y H:i:s", $GlobalUser['noattack_util']))."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->numships > $GlobalUni['battle_max']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_BATTLE_MAX")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                break;

            case FTYP_EXPEDITION:       // Expedition
                $manned = 0;
                foreach ($this->fleet as $id=>$amount)
                {
                    if ($id != GID_F_PROBE) $manned += $amount;        // not counting probes.
                }
                $expnum = GetExpeditionsCount ( $GlobalUser['player_id'] );    // Number of expeditions
                $maxexp = floor ( sqrt ( $GlobalUser[GID_R_EXPEDITION] ) );
                if ( $expnum >= $maxexp ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_EXP_LIMIT")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( $manned == 0 ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_EXP_REQUIRED")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ( intval($_POST['planet']) != 16 ) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_EXP_INVALID")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else if ($this->numships > $GlobalUni['battle_max']) { $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_BATTLE_MAX")."</span></th>\n  </tr>\n"; $this->FleetError = true; }
                else {
                    $id = CreateOuterSpace ( intval($_POST['galaxy']), intval($_POST['system']), intval($_POST['planet']) );
                    $this->target = LoadPlanetById ($id);
                }
                break;

            default:
                $this->FleetErrorText .= "   <tr height=\"20\">\n   <th><span class=\"error\">".loca("FLEET_ERR_ORDER")."</span></th>\n  </tr>\n"; $this->FleetError = true;
                break;
        }

        if ($this->FleetError) {
            return true;
        }

        // All checks have been successful, we can send the fleet out.

        // Fleet lock
        $fleetlock = "temp/fleetlock_" . $aktplanet['planet_id'];
        if ( file_exists ($fleetlock) ) {
            $fileCreationTime = filectime($fleetlock);
            if ((time() - $fileCreationTime) < 3) {
                MyGoto ( "flotten1" );
            } else {
                unlink ( $fleetlock );
            }
        }
        $f = fopen ( $fleetlock, 'w' );
        fclose ($f);

        $this->fleet_id = DispatchFleet ( $this->fleet, $this->origin, $this->target, $this->order, $this->flighttime, 
            $this->resources, 
            (int)($cons['fleet'] + $cons['probes']), time(), $this->union_id, (int)$this->hold_time );
        $this->queue = GetFleetQueue ($this->fleet_id);

        UserLog ( $aktplanet['owner_id'], "FLEET", 
            va(loca_lang("DEBUG_LOG_FLEET_SEND1", $GlobalUni['lang']), $this->fleet_id) . GetMissionNameDebug ($this->order) . " " .
            $this->origin['name'] ." [".$this->origin['g'].":".$this->origin['s'].":".$this->origin['p']."] -&gt; ".$this->target['name']." [".$this->target['g'].":".$this->target['s'].":".$this->target['p']."]<br>" .
            DumpFleet($this->fleet) . "<br>" .
            va(loca_lang("DEBUG_LOG_FLEET_SEND2", $GlobalUni['lang']), DurationFormat ($this->flighttime), DurationFormat ($this->hold_time), nicenum ($cons['fleet'] + $cons['probes']), $this->union_id) );

        if ( $this->union_id ) {
            $this->union_time = UpdateUnionTime ( $this->union_id, $this->queue['end'], $this->fleet_id );
            UpdateFleetTime ( $this->fleet_id, $this->union_time );
        }

        // Lift off.
        $this->resources[GID_RC_DEUTERIUM] += $cons['fleet'] + $cons['probes'];
        AdjustResources ( $this->resources, $this->origin['planet_id'], '-' );
        AdjustShips ( $this->fleet, $this->origin['planet_id'], '-' );

        unlink ( $fleetlock );

        return true;
    }

    public function view () : void {
        global $GlobalUser;
        global $GlobalUni;
        global $aktplanet;
        global $session;

        if ($this->FleetError) {
        ?>
          <script language="JavaScript" src="js/flotten.js"></script>
          <table width="519" border="0" cellpadding="0" cellspacing="1">

        <?php

            echo "  <tr height=\"20\">\n";
            echo "     <td class=\"c\"><span class=\"error\">".loca("FLEET_SEND_ERROR")."</span></td>\n";
            echo "  </tr>\n";
            echo $this->FleetErrorText."\n";
        }
        else {
        ?>
          <script language="JavaScript" src="js/flotten.js"></script>
          <table width="519" border="0" cellpadding="0" cellspacing="1">

           <tr height="20">
            <td class="c" colspan="2">
              <span class="success"><?=loca("FLEET_SEND_DONE");?></span>
            </td>
           </tr>
           <tr height="20">
          <th><?=loca("FLEET_SEND_MISSION");?></th><th><?php echo loca("FLEET_ORDER_".$this->order);?></th>
           </tr>
           <tr height="20">
             <th><?=loca("FLEET_SEND_DIST");?></th><th><?php echo nicenum($this->dist);?></th>
           </tr>
           <tr height="20">
              <th><?=loca("FLEET_SEND_SPEED");?></th><th><?php echo nicenum($this->slowest_speed);?></th>
           </tr>
           <tr height="20">
              <th><?=loca("FLEET_SEND_CONS");?></th><th><?php echo nicenum($cons['fleet'] + $cons['probes']);?></th>
           </tr>
           <tr height="20">
             <th><?=loca("FLEET_SEND_ORIGIN");?></th><th><a href="index.php?page=galaxy&galaxy=<?php echo intval($_POST['thisgalaxy']);?>&system=<?php echo intval($_POST['thissystem']);?>&position=<?php echo intval($_POST['thisplanet']);?>&session=<?php echo $session;?>" >[<?php echo intval($_POST['thisgalaxy']);?>:<?php echo intval($_POST['thissystem']);?>:<?php echo intval($_POST['thisplanet']);?>]</a></th>
           </tr>
           <tr height="20">
             <th><?=loca("FLEET_SEND_TARGET");?></th><th><a href="index.php?page=galaxy&galaxy=<?php echo intval($_POST['galaxy']);?>&system=<?php echo intval($_POST['system']);?>&position=<?php echo intval($_POST['planet']);?>&session=<?php echo $session;?>" >[<?php echo intval($_POST['galaxy']);?>:<?php echo intval($_POST['system']);?>:<?php echo intval($_POST['planet']);?>]</a></th>
           </tr>
           <tr height="20">
             <th><?=loca("FLEET_SEND_ARRIVE");?></th><th><?php echo date("D M j G:i:s", $this->queue['end']);?></th>
           </tr>
           <tr height="20">
             <th><?=loca("FLEET_SEND_RETURN");?></th><th><?php echo date("D M j G:i:s", $this->queue['end'] + $this->flighttime + $this->hold_time);?></th>
            </tr>
           <tr height="20">
             <td class="c" colspan="2"><?=loca("FLEET_SEND_SHIPS");?></td>
           </tr>

        <?php
            // Ship List.
            foreach ($this->fleet as $id=>$amount)
            {
                if ( $amount > 0 ) {
                    echo "      <tr height=\"20\">\n";
                    echo "     <th width=\"50%\">".loca("NAME_$id")."</th><th>".nicenum($amount)."</th>\n";
                    echo "   </tr>\n";
                }
            }

        }
        ?>

           </table>
        <br><br><br><br>
        <?php
    }

    private int $BlockAttack = 0;
    private bool $FleetError = false;
    private string $FleetErrorText = "";
    private array $fleetmap;
    private array $transportableResources;
    private float $unispeed;
    private int $nowfleet = 0;
    private int $maxfleet = 0;
    private int $maxfleet_no_bonus = 0;
    private array $resource = [];
    private int $order = 0;
    private int $union_id = 0;
    private ?array $union;
    private array $fleet = [];
    private array $origin;
    private array $target;
    private array $origin_user;
    private array $target_user;
    private int $hold_time = 0;
    private int $dist = 0;
    private int $slowest_speed = 0;
    private int $flighttime = 0;
    private array $cons;
    private int $cargo = 0;
    private int $spycargo = 0;
    private int $numships = 0;
    private array $resources = [];
    private bool $success = false;
    private int $fleet_id = 0;
    private array $queue;
    private int $union_time = 0;
}

?>