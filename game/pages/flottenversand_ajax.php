
<?php

/** @var array $GlobalUser */
/** @var array $GlobalUni */
/** @var array $aktplanet */

// Fast dispatch of fleets from the Galaxy via AJAX.

// Variables outside: aktplanet is the current planet.
// Errors are propagated by codes that are handled by JavaScript on the Galaxy page.

BrowseHistory ();

if ( $GlobalUni['freeze'] ) AjaxSendError ();    // The universe is on pause.
$unispeed = $GlobalUni['fspeed'];

function AjaxSendError (int $id=601) : void
{
    header ('Content-Type: text/html;');
    echo "$id 0 0 0 0";
    ob_end_flush ();
    die ();
}

function AjaxSendDone (int $slots, int $probes, int $recyclers, int $missiles) : void
{
    header ('Content-Type: text/html;');
    echo "600 $slots ".nicenum($probes)." ".nicenum($recyclers)." ".nicenum($missiles);
    ob_end_flush ();
    die ();
}

// Check the availability of parameters.
if (
    !key_exists ( "order", $_POST ) ||
    !key_exists ( "galaxy", $_POST ) ||
    !key_exists ( "system", $_POST ) ||
    !key_exists ( "planet", $_POST ) ||
    !key_exists ( "planettype", $_POST ) ||
    !key_exists ( "shipcount", $_POST ) 
 )  AjaxSendError ();

$order = intval($_POST['order']);
$galaxy = intval($_POST['galaxy']);
$system = intval($_POST['system']);
$planet = intval($_POST['planet']);
$planettype = intval($_POST['planettype']);
$shipcount = abs (intval($_POST['shipcount']));
$speed = 1;

// You cannot send a fleet if the previous one was sent less than a second ago.
$result = EnumOwnFleetQueueSpecial ( $GlobalUser['player_id'] );
$rows = dbrows ($result);
if ( $rows ) {
    $queue = dbarray ($result);
    if ( abs(time () - $queue['start']) < 1 ) AjaxSendError ();
}

// Check the parameters.

if ( $planettype < 1 || $planettype > 3 ) AjaxSendError ();    // wrong target
if ( ! ( $order == FTYP_SPY || $order == FTYP_RECYCLE ) ) AjaxSendError ();    // can only be sent to espionage or recycle
if ( $order == FTYP_RECYCLE && $planettype != 2 ) AjaxSendError ();    // recyclers can only be sent to the debris field
if ( $order == FTYP_SPY && ! ($planettype == 1 || $planettype == 3) )  AjaxSendError ();     // You can only spy on planets or moons
if ( $galaxy < 1 || $galaxy > $GlobalUni['galaxies'] ) AjaxSendError ();    // wrong coordinates (Galaxy)
if ( $system < 1 || $system > $GlobalUni['systems'] ) AjaxSendError ();    // wrong coordinates (System)
if ( $planet < 1 || $planet > 15 ) AjaxSendError ();    // wrong coordinates (Position)
if ( $GlobalUser['vacation'] ) AjaxSendError (605);    // user in vacation mode

// Check for available slots
$result = EnumOwnFleetQueue ( $GlobalUser['player_id'] );
$nowfleet = dbrows ($result);
$maxfleet = $GlobalUser['r108'] + 1;

$prem = PremiumStatus ($GlobalUser);
if ( $prem['admiral'] ) $maxfleet += 2;

if ( $nowfleet >= $maxfleet ) AjaxSendError (612);

$target = LoadPlanet ( $galaxy, $system, $planet, $planettype );    // load the target planet
if ( $target == NULL )
{
    if ($planettype == 1) AjaxSendError (614);        // no planet
    else if ($planettype == 3) AjaxSendError (602);    // no moon
    else AjaxSendError ();    // no debris field
}

$target_user = LoadUser ( $target['owner_id'] );

$probes = $aktplanet['f'.GID_F_PROBE];
$recyclers = $aktplanet['f'.GID_F_RECYCLER];
$missiles = $aktplanet['d'.GID_D_IPM];

if ( ( 
( $GlobalUser['ally_id'] == $target_user['ally_id'] && $GlobalUser['ally_id'] > 0 )   || 
 IsBuddy ( $GlobalUser['player_id'],  $target_user['player_id']) ) ) $BlockAttack = 0;

/* ************ ESPIONAGE ************  */

if ( $order == FTYP_SPY )
{
    $amount = min ($aktplanet["f210"], $shipcount);

    if ( $target['owner_id'] == $GlobalUser['player_id'] ) AjaxSendError ();    // Own planet
    if ( $GlobalUser['noattack'] || $BlockAttack ) AjaxSendError ();    // Attack ban
    if ( $target_user['admin'] > 0 ) AjaxSendError ();    // the administration can't be scanned.
    if ( IsPlayerNewbie ($target_user['player_id']) ) AjaxSendError (603);    // newbie protection
    if ( IsPlayerStrong ($target_user['player_id']) ) AjaxSendError (604);    // strong protection
    if ( $target_user['vacation'] ) AjaxSendError (605);    // user in vacation mode
    if ( $amount == 0 ) AjaxSendError (611);    // there are no ships to send
    // DO NOT check fleet dispatch between players with the same IP only if BOTH have IP checking disabled in the settings.
    // OR if the sent is on localhost (local web server for debugging)   
    if ( ! ($GlobalUser['deact_ip'] && $target_user['deact_ip']) && !localhost($GlobalUser['ip_addr']) ) {
        if ( $target_user['ip_addr'] === $GlobalUser['ip_addr'] ) AjaxSendError (616);    // multialarm
    }

    // Form a fleet.
    $fleet = array ();
    foreach ( $fleetmap as $i=>$gid ) {
        if ( $gid == GID_F_PROBE ) $fleet[$gid] = $amount;
        else $fleet[$gid] = 0;
    }
    $cargo = FleetCargo (GID_F_PROBE) * $amount;
    $probes -= $amount;
}

/* ************ RECYCLE ************  */

if ( $order == FTYP_RECYCLE )
{
    $amount = min ($aktplanet["f209"], $shipcount);

    if ( $amount == 0 ) AjaxSendError (611);    // no ships to send

    // Form a fleet.
    $fleet = array ();
    foreach ( $fleetmap as $i=>$gid ) {
        if ( $gid == GID_F_RECYCLER ) $fleet[$gid] = $amount;
        else $fleet[$gid] = 0;
    }
    $cargo = FleetCargo (GID_F_RECYCLER) * $amount;
    $recyclers -= $amount;
}

// Calculate distance, flight time, and deuterium costs.
$dist = FlightDistance ( $aktplanet['g'], $aktplanet['s'], $aktplanet['p'], $galaxy, $system, $planet );
$slowest_speed = FlightSpeed ( $fleet, $GlobalUser['r115'], $GlobalUser['r117'], $GlobalUser['r118'] );
$flighttime = FlightTime ( $dist, $slowest_speed, $speed, $unispeed );
$arr = FlightCons ( $fleet, $dist, $flighttime, $GlobalUser['r115'], $GlobalUser['r117'], $GlobalUser['r118'], $unispeed );
$cons = $arr['fleet'] + $arr['probes'];

if ( $aktplanet['d'] < $cons ) AjaxSendError (613);        // not enough deut to fly.
if ( $cargo < $cons ) AjaxSendError (615);        // there's no room in the cargo hold for deuterium.

// Fleet lock
$fleetlock = "temp/fleetlock_" . $aktplanet['planet_id'];
if ( file_exists ($fleetlock) ) AjaxSendError ();
$f = fopen ( $fleetlock, 'w' );
fclose ($f);

// Send in the fleet.
$fleet_id = DispatchFleet ( $fleet, $aktplanet, $target, $order, $flighttime, 0, 0, 0, $cons, time(), 0 );

UserLog ( $aktplanet['owner_id'], "FLEET", 
    va(loca_lang("DEBUG_LOG_FLEET_SEND_AJAX1", $GlobalUni['lang']), $fleet_id) . GetMissionNameDebug ($order) . " " .
    $aktplanet['name'] ." [".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."] -&gt; ".$target['name']." [".$target['g'].":".$target['s'].":".$target['p']."]<br>" .
    DumpFleet ($fleet) . "<br>" .
    va(loca_lang("DEBUG_LOG_FLEET_SEND_AJAX2", $GlobalUni['lang']), BuildDurationFormat ($flighttime), nicenum ($cons)) );

// Get the fleet off the planet.
AdjustResources ( 0, 0, $cons, $aktplanet['planet_id'], '-' );
AdjustShips ( $fleet, $aktplanet['planet_id'], '-' );
UpdatePlanetActivity ($aktplanet['planet_id']);

unlink ( $fleetlock );

AjaxSendDone ( $nowfleet+1, $probes, $recyclers, $missiles );
?>