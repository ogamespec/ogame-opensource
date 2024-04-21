<?php

// Auxiliary functions for the economic part of OGame.

// Calculation of cost, build time and required conditions.

// Level 1 cost.
$initial = array (      // m, k, d, e
    // Buildings
    14 => array (400, 120, 200, 0),
    15 => array (1000000, 500000, 100000, 0),
    21 => array (400, 200, 100, 0),
    22 => array (2000, 0, 0, 0),
    23 => array (2000, 1000, 0, 0),
    24 => array (2000, 2000, 0, 0),
    31 => array (200, 400, 200, 0),
    33 => array (0, 50000, 100000, 1000),
    34 => array (20000, 40000,  0, 0),
    44 => array (20000, 20000, 1000, 0),
    // Moon
    41 => array (20000, 40000, 20000, 0),
    42 => array (20000, 40000, 20000, 0),
    43 => array (2000000, 4000000, 2000000, 0),

    // Fleet
    202 => array (2000, 2000, 0, 0),
    203 => array (6000, 6000, 0, 0),
    204 => array (3000, 1000, 0, 0),
    205 => array (6000, 4000, 0, 0),
    206 => array (20000, 7000, 2000, 0),
    207 => array (45000, 15000, 0, 0),
    208 => array (10000, 20000, 10000, 0),
    209 => array (10000, 6000, 2000, 0),
    210 => array (0, 1000, 0, 0),
    211 => array (50000, 25000, 15000, 0),
    212 => array (0, 2000, 500, 0),
    213 => array (60000, 50000, 15000, 0),
    214 => array (5000000, 4000000, 1000000, 0),
    215 => array (30000, 40000, 15000, 0),

    // Defense
    401 => array (2000, 0, 0, 0),
    402 => array (1500, 500, 0, 0),
    403 => array (6000, 2000, 0, 0),
    404 => array (20000, 15000, 2000, 0),
    405 => array (2000, 6000, 0, 0),
    406 => array (50000, 50000, 30000, 0),
    407 => array (10000, 10000, 0, 0),
    408 => array (50000, 50000, 0, 0),
    502 => array (8000, 0, 2000, 0),
    503 => array (12500, 2500, 10000, 0),

    // Research
    106 => array (200, 1000, 200, 0),
    108 => array (0, 400, 600, 0),
    109 => array (800, 200, 0, 0),
    110 => array (200, 600, 0, 0),
    111 => array (1000, 0, 0, 0),
    113 => array (0, 800, 400, 0),
    114 => array (0, 4000, 2000, 0),
    115 => array (400, 0, 600, 0),
    117 => array (2000, 4000, 600, 0),
    118 => array (10000, 20000, 6000, 0),
    120 => array (200, 100, 0, 0),
    121 => array (1000, 300, 100, 0),
    122 => array (2000, 4000, 1000, 0),
    123 => array (240000, 400000, 160000, 0),
    124 => array (4000, 8000, 4000, 0),
    199 => array (0, 0, 0, 300000),
);

function BuildMeetRequirement ( $user, $planet, $id )
{
    if ( $planet['type'] == 0 )
    {
        if ( $id == 1 || $id == 2 || $id == 3 || $id == 4 || $id == 12 || $id == 15 || $id == 31 || $id == 33 || $id == 44 ) return false;
    }
    else
    {
        if ( $id == 41 || $id == 42 || $id == 43 ) return false;
    }

    // Fusion Reactor => Deuterium Synthesizer (level 5), Energy Technology (level 3)
    // Nanite Factory => Robot Factory (level 10), Computer Technology (level 10)
    // Shipyard => Robot Factory (level 2)
    // Terraformer => Nanite Factory (level 1), Energy Technology (level 12)
    // Rocket silo => Shipyard (level 1)
    // Sensor phalanx => Lunar base (level 1)
    // JumpGate => Moonbase (level 1), Hyperspace Technology (level 7)
    if ( $id == 12 && ( $planet['b3'] < 5 || $user['r113'] < 3 ) ) return false;
    if ( $id == 15 && ( $planet['b14'] < 10 || $user['r108'] < 10 ) ) return false;
    if ( $id == 21 && ( $planet['b14'] < 2 ) ) return false;
    if ( $id == 33 && ( $planet['b15'] < 1 || $user['r113'] < 12 ) ) return false;
    if ( $id == 44 && ( $planet['b21'] < 1 ) ) return false;
    if ( $id == 42 && ( $planet['b41'] < 1 ) ) return false;
    if ( $id == 43 && ( $planet['b41'] < 1 || $user['r114'] < 7 ) ) return false;

    return true;
}

function BuildPrice ( $id, $lvl )
{
    global $initial;
    switch ($id)
    {
        case 1:   // Metal Mine
            $m = floor (60 * pow(1.5, $lvl-1));
            $k = floor (15 * pow(1.5, $lvl-1));
            $d = $e = 0;
            break;
        case 2:   // Crystal Mine
            $m = floor (48 * pow(1.6, $lvl-1));
            $k = floor (24 * pow(1.6, $lvl-1));
            $d = $e = 0;
            break;
        case 3:   // Deuterium Synthesizer
            $m = floor (225 * pow(1.5, $lvl-1));
            $k = floor (75 * pow(1.5, $lvl-1));
            $d = $e = 0;
            break;
        case 4:   // Solar Plant
            $m = floor (75 * pow(1.5, $lvl-1));
            $k = floor (30 * pow(1.5, $lvl-1));
            $d = $e = 0;
            break;
        case 12:   // Fusion Reactor
            $m = floor (900 * pow(1.8, $lvl-1));
            $k = floor (360 * pow(1.8, $lvl-1));
            $d = floor (180 * pow(1.8, $lvl-1));
            $e = 0;
            break;
        default:
            $m = $initial[$id][0] * pow(2, $lvl-1);
            $k = $initial[$id][1] * pow(2, $lvl-1);
            $d = $initial[$id][2] * pow(2, $lvl-1);
            $e = $initial[$id][3] * pow(2, $lvl-1);
            break;
    }
    $res = array ( 'm' => $m, 'k' => $k, 'd' => $d, 'e' => $e );
    return $res;
}

// Time to build a $id level $lvl building in seconds.
function BuildDuration ( $id, $lvl, $robots, $nanits, $speed )
{
    $res = BuildPrice ( $id, $lvl );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    $secs = floor ( ( ( ($m + $k) / (2500 * (1 + $robots)) ) * pow (0.5, $nanits) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return $secs;
}

function ShipyardMeetRequirement ( $user, $planet, $id )
{
    if ( $id == 202 && ( $planet['b21'] < 2  || $user['r115'] < 2 ) ) return false;
    else if ( $id == 203 && ( $planet['b21'] < 4  || $user['r115'] < 6 ) ) return false;
    else if ( $id == 204 && ( $planet['b21'] < 1  || $user['r115'] < 1 ) ) return false;
    else if ( $id == 205 && ( $planet['b21'] < 3  || $user['r111'] < 2 || $user['r117'] < 2 ) ) return false;
    else if ( $id == 206 && ( $planet['b21'] < 5  || $user['r117'] < 4 || $user['r121'] < 2 ) ) return false;
    else if ( $id == 207 && ( $planet['b21'] < 7  || $user['r118'] < 4 ) ) return false;
    else if ( $id == 208 && ( $planet['b21'] < 4  || $user['r117'] < 3 ) ) return false;
    else if ( $id == 209 && ( $planet['b21'] < 4  || $user['r115'] < 6 || $user['r110'] < 2 ) ) return false;
    else if ( $id == 210 && ( $planet['b21'] < 3  || $user['r115'] < 3 || $user['r106'] < 2 ) ) return false;
    else if ( $id == 211 && ( $planet['b21'] < 8  || $user['r117'] < 6 || $user['r122'] < 5 ) ) return false;
    else if ( $id == 212 && ( $planet['b21'] < 1  ) ) return false;
    else if ( $id == 213 && ( $planet['b21'] < 9  || $user['r118'] < 6 || $user['r114'] < 5 ) ) return false;
    else if ( $id == 214 && ( $planet['b21'] < 12 || $user['r118'] < 7 || $user['r114'] < 6 || $user['r199'] < 1 ) ) return false;
    else if ( $id == 215 && ( $planet['b21'] < 8  || $user['r114'] < 5 || $user['r120'] < 12 || $user['r118'] < 5 ) ) return false;

    else if ( $id == 401 && ( $planet['b21'] < 1 ) ) return false;
    else if ( $id == 402 && ( $planet['b21'] < 2 || $user['r113'] < 1 || $user['r120'] < 3 ) ) return false;
    else if ( $id == 403 && ( $planet['b21'] < 4 || $user['r113'] < 3 || $user['r120'] < 6 ) ) return false;
    else if ( $id == 404 && ( $planet['b21'] < 6 || $user['r113'] < 6 || $user['r109'] < 3 || $user['r110'] < 1 ) ) return false;
    else if ( $id == 405 && ( $planet['b21'] < 4 || $user['r121'] < 4 ) ) return false;
    else if ( $id == 406 && ( $planet['b21'] < 8 || $user['r122'] < 7 ) ) return false;
    else if ( $id == 407 && ( $planet['b21'] < 1 || $user['r110'] < 2 ) ) return false;
    else if ( $id == 408 && ( $planet['b21'] < 6 || $user['r110'] < 6 ) ) return false;
    else if ( $id == 502 && ( $planet['b21'] < 1 || $planet['b44'] < 2 ) ) return false;
    else if ( $id == 503 && ( $planet['b21'] < 1 || $planet['b44'] < 4 || $user['r117'] < 1 ) ) return false;

    return true;
}

function ShipyardPrice ( $id )
{
    global $initial;
    $m = $initial[$id][0];
    $k = $initial[$id][1];
    $d = $initial[$id][2];
    $e = 0;
    $res = array ( 'm' => $m, 'k' => $k, 'd' => $d, 'e' => $e );
    return $res;
}

function ShipyardDuration ( $id, $shipyard, $nanits, $speed )
{
    $res = ShipyardPrice ($id);
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    $secs = floor ( ( ( ($m + $k) / (2500 * (1 + $shipyard)) ) * pow (0.5, $nanits) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return $secs;
}

function ResearchMeetRequirement ( $user, $planet, $id )
{
    if ( $id == 106 && ( $planet['b31'] < 3 ) ) return false;
    else if ( $id == 108 && ( $planet['b31'] < 1 ) ) return false;
    else if ( $id == 109 && ( $planet['b31'] < 4 ) ) return false;
    else if ( $id == 110 && ( $user['r113'] < 3 || $planet['b31'] < 6 ) ) return false;
    else if ( $id == 111 && ( $planet['b31'] < 2 ) ) return false;
    else if ( $id == 113 && ( $planet['b31'] < 1 ) ) return false;
    else if ( $id == 114 && ( $user['r113'] < 5 || $user['r110'] < 5 || $planet['b31'] < 7  ) ) return false;
    else if ( $id == 115 && ( $user['r113'] < 1 || $planet['b31'] < 1 ) ) return false;
    else if ( $id == 117 && ( $user['r113'] < 1 || $planet['b31'] < 2  ) ) return false;
    else if ( $id == 118 && ( $user['r114'] < 3 || $planet['b31'] < 7  ) ) return false;
    else if ( $id == 120 && ( $user['r113'] < 2 || $planet['b31'] < 1  ) ) return false;
    else if ( $id == 121 && ( $user['r120'] < 5 || $user['r113'] < 4 || $planet['b31'] < 4  ) ) return false;
    else if ( $id == 122 && ( $user['r113'] < 8 || $user['r120'] < 10 || $user['r121'] < 5 || $planet['b31'] < 4 ) ) return false;
    else if ( $id == 123 && ( $user['r108'] < 8 || $user['r114'] < 8 || $planet['b31'] < 10  ) ) return false;
    else if ( $id == 124 && ( $user['r106'] < 4 || $user['r117'] < 3 || $planet['b31'] < 3 ) ) return false;
    else if ( $id == 199 && ( $planet['b31'] < 12 ) ) return false;

    return true;
}

function ResearchPrice ( $id, $lvl )
{
    global $initial;
    if ($id == 199) {
        $m = $k = $d = 0;
        $e = $initial[$id][3] * pow(3, $lvl-1);
    }
    else {
        $m = $initial[$id][0] * pow(2, $lvl-1);
        $k = $initial[$id][1] * pow(2, $lvl-1);
        $d = $initial[$id][2] * pow(2, $lvl-1);
        $e = $initial[$id][3] * pow(2, $lvl-1);
    }
    $res = array ( 'm' => $m, 'k' => $k, 'd' => $d, 'e' => $e );
    return $res;
}

function ResearchDuration ( $id, $lvl, $reslab, $speed )
{
    if ( $id == 199 ) return 1;
    $res= ResearchPrice ($id, $lvl );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    $secs = floor ( ( ($m + $k) / (1000 * (1 + $reslab)) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return $secs;
}

// IGN Calculation.
// Attach +IGN laboratories of maximum level to the current laboratory.
// The output is the overall level of the "virtual" lab.
function ResearchNetwork ( $planetid, $id )
{
    global $db_prefix;
    $planet = GetPlanet ($planetid);
    $player_id = $planet['owner_id'];
    $user = LoadUser ($player_id);
    $ign = $user ["r123"];
    $reslab = $planet["b31"];
    $labs = array ();
    $labnum = 0;

    // List the player's planets (do not list moons and other special objects). Also skip planets that do not have lab.
    $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = $player_id AND type < 10000 AND type > 0 AND b31 > 0";
    $result = dbquery ($query);
    $pnum = dbrows ( $result );

    // Get all available labs sorted in descending order.
    while ($pnum--)
    {
        $p = dbarray ($result);
        if ( $p['planet_id'] == $planetid) continue;    // Skip the current planet.
        if ( ResearchMeetRequirement ( $user, $p, $id ) ) $labs[$labnum++] = $p["b31"];
    }
    rsort ( $labs );

    // Attach +IGN of available laboratories.
    for ($i=0; $i<$ign && $i<$labnum; $i++) $reslab += $labs[$i];
    return $reslab;
}

// Return a string of durations by days, hours, minutes, seconds.
function BuildDurationFormat ( $seconds )
{
    $res = "";
    $days = floor ($seconds / (24*3600));
    $hours = floor ($seconds / 3600 % 24);
    $mins = floor ($seconds  / 60 % 60);
    $secs = round ( $seconds / 1 % 60);
    if ($days) {
        $res .= "$days".loca("TIME_DAYS")." ";
    }
    if ($hours || $days) $res .= "$hours".loca("TIME_HOUR")." ";
    if ($mins || $days) $res .= "$mins".loca("TIME_MIN")." ";
    if ($secs) $res .= "$secs".loca("TIME_SEC");
    return $res;
}

function IsEnoughResources ($planet, $m, $k, $d, $e)
{
    if ( $m && $planet['m'] < $m ) return false;
    if ( $k && $planet['k'] < $k ) return false;
    if ( $d && $planet['d'] < $d ) return false;
    if ( $e && $planet['emax'] < $e ) return false;
    return true;
}

// Anything related to resource production and calculation.

// Get the size of the storages.
function store_capacity ($lvl) { return 100000 + 50000 * (ceil (pow (1.6, $lvl) - 1)); }

// Energy production
function prod_solar ($lvl, $pr)
{
    $prod = floor (20 * $lvl * pow (1.1, $lvl) * $pr);
    return $prod;
}
function prod_fusion ($lvl, $energo, $pr)
{
    $prod = floor (30 * $lvl * pow (1.05 + $energo*0.01, $lvl) * $pr);
    return $prod;
}
function prod_sat ($maxtemp)
{
    $prod = floor (($maxtemp / 4) + 20);
    return max (1, $prod);
}

// Mines production
function prod_metal ($lvl, $pr) { return floor (30 * $lvl * pow (1.1, $lvl) * $pr); }
function prod_crys ($lvl, $pr) { return floor (20 * $lvl * pow (1.1, $lvl) * $pr); }
function prod_deut ($lvl, $maxtemp, $pr) { return floor ( 10 * $lvl * pow (1.1, $lvl) * $pr) * (1.28 - 0.002 * ($maxtemp)); }

// Energy consumption
function cons_metal ($lvl) { return ceil (10 * $lvl * pow (1.1, $lvl)); }
function cons_crys ($lvl) { return ceil (10 * $lvl * pow (1.1, $lvl)); }
function cons_deut ($lvl) { return ceil (20 * $lvl * pow (1.1, $lvl)); }

// Consumption of deuterium by the fusion reactor
function cons_fusion ($lvl, $pr) { return ceil (10 * $lvl * pow (1.1, $lvl) * $pr) ; }

// Calculate resource production increase. Limit storage capacity.
// NOTE: The calculation excludes external events, such as the end of officers' actions, attack of another player, completion of building construction, etc.
function ProdResources ( &$planet, $time_from, $time_to )
{
    global $db_prefix, $GlobalUni;
    if ( $planet['type'] != PTYP_PLANET ) return;        // NOT a planet
    $user = LoadUser ($planet['owner_id']);
    if ( $user['player_id'] == USER_SPACE ) return;    // technical account space
    $diff = $time_to - $time_from;

    $speed = $GlobalUni['speed'];

    $prem = PremiumStatus ($user);
    if ( $prem['geologist'] ) $g_factor = 1.1;
    else $g_factor = 1.0;

    $hourly = prod_metal ($planet['b1'], $planet['mprod']) * $planet['factor'] * $speed * $g_factor + 20 * $speed;        // Metal
    if ( $planet['m'] < $planet['mmax'] ) {
        $planet['m'] += ($hourly * $diff) / 3600;
        if ( $planet['m'] >= $planet['mmax'] ) $planet['m'] = $planet['mmax'];
    }

    $hourly = prod_crys ($planet['b2'], $planet['kprod']) * $planet['factor'] * $speed * $g_factor + 10 * $speed;        // Crystal
    if ( $planet['k'] < $planet['kmax'] ) {
        $planet['k'] += ($hourly * $diff) / 3600;
        if ( $planet['k'] >= $planet['kmax'] ) $planet['k'] = $planet['kmax'];
    }

    $hourly = prod_deut ($planet['b3'], $planet['temp']+40, $planet['dprod']) * $planet['factor'] * $speed * $g_factor;    // Deuterium
    $hourly -= cons_fusion ( $planet['b12'], $planet['fprod'] ) * $speed;	// термояд
    if ( $planet['d'] < $planet['dmax'] ) {
        $planet['d'] += ($hourly * $diff) / 3600;
        if ( $planet['d'] >= $planet['dmax'] ) $planet['d'] = $planet['dmax'];
    }

    $planet_id = $planet['planet_id'];
    $query = "UPDATE ".$db_prefix."planets SET m = '".$planet['m']."', k = '".$planet['k']."', d = '".$planet['d']."', lastpeek = '".$time_to."' WHERE planet_id = $planet_id";
    dbquery ($query);
    $planet['lastpeek'] = $time_to;
}

// The cost of the planet in points.
function PlanetPrice ($planet)
{
    $pp = array ();
    $buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );

    $m = $k = $d = $e = 0;
    $pp['points'] = $pp['fpoints'] = $pp['fleet_pts'] = $pp['defense_pts'] = 0;

    foreach ( $buildmap as $i=>$gid ) {        // Buildings
        $level = $planet["b$gid"];
        if ($level > 0){
            for ( $lv = 1; $lv<=$level; $lv ++ )
            {
                $res = BuildPrice ( $gid, $lv );
                $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
                $pp['points'] += ($m + $k + $d);
            }
        }
    }

    foreach ( $fleetmap as $i=>$gid ) {        // Fleet
        $level = $planet["f$gid"];
        if ($level > 0){
            $res = ShipyardPrice ( $gid);
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            $pp['points'] += ($m + $k + $d) * $level;
            $pp['fleet_pts'] += ($m + $k + $d) * $level;
            $pp['fpoints'] += $level;
        }
    }

    foreach ( $defmap as $i=>$gid ) {        // Defense
        $level = $planet["d$gid"];
        if ($level > 0){
            $res = ShipyardPrice ( $gid );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            $pp['points'] += ($m + $k + $d) * $level;
            $pp['defense_pts'] += ($m + $k + $d) * $level;
        }
    }

    return $pp;
}

// Fleet cost
function FleetPrice ( $fleet_obj )
{
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $m = $k = $d = $e = 0;
    $points = $fpoints = 0;
    $price = array ();

    foreach ( $fleetmap as $i=>$gid ) {        // Fleet
        $level = $fleet_obj["ship$gid"];
        if ($level > 0){
            $res = ShipyardPrice ( $gid );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            $points += ($m + $k + $d) * $level;
            $fpoints += $level;
        }
    }

    $price['points'] = $points;
    $price['fpoints'] = $fpoints;
    return $price;
}

?>