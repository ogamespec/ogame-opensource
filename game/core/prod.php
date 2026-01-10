<?php

// Auxiliary functions for the economic part of OGame.

// Calculation of cost, build time and required conditions.

function BuildMeetRequirement ( array $user, array $planet, int $id ) : bool
{
    if ( $planet['type'] == PTYP_MOON )
    {
        if ( $id == GID_B_METAL_MINE || 
            $id == GID_B_CRYS_MINE || 
            $id == GID_B_DEUT_SYNTH || 
            $id == GID_B_SOLAR || 
            $id == GID_B_FUSION || 
            $id == GID_B_NANITES || 
            $id == GID_B_RES_LAB || 
            $id == GID_B_TERRAFORMER || 
            $id == GID_B_MISS_SILO ) return false;
    }
    else
    {
        if ( $id == GID_B_LUNAR_BASE || $id == GID_B_PHALANX || $id == GID_B_JUMP_GATE ) return false;
    }

    // Fusion Reactor => Deuterium Synthesizer (level 5), Energy Technology (level 3)
    // Nanite Factory => Robot Factory (level 10), Computer Technology (level 10)
    // Shipyard => Robot Factory (level 2)
    // Terraformer => Nanite Factory (level 1), Energy Technology (level 12)
    // Rocket silo => Shipyard (level 1)
    // Sensor phalanx => Lunar base (level 1)
    // JumpGate => Moonbase (level 1), Hyperspace Technology (level 7)
    if ( $id == GID_B_FUSION && ( $planet['b3'] < 5 || $user['r113'] < 3 ) ) return false;
    if ( $id == GID_B_NANITES && ( $planet['b14'] < 10 || $user['r108'] < 10 ) ) return false;
    if ( $id == GID_B_SHIPYARD && ( $planet['b14'] < 2 ) ) return false;
    if ( $id == GID_B_TERRAFORMER && ( $planet['b15'] < 1 || $user['r113'] < 12 ) ) return false;
    if ( $id == GID_B_MISS_SILO && ( $planet['b21'] < 1 ) ) return false;
    if ( $id == GID_B_PHALANX && ( $planet['b41'] < 1 ) ) return false;
    if ( $id == GID_B_JUMP_GATE && ( $planet['b41'] < 1 || $user['r114'] < 7 ) ) return false;

    return true;
}

function BuildPrice ( int $id, int $lvl ) : array
{
    global $initial;

    // This formula does not have a single, generally accepted name, but it is most often referred to as:
    // - Exponential growth formula with a linear factor
    // - In the context of video games, it's called the "experience formula" or "level curve"

    $factor = $initial[$id][4];
    $m = $initial[$id][0] * pow($factor, $lvl-1);
    $k = $initial[$id][1] * pow($factor, $lvl-1);
    $d = $initial[$id][2] * pow($factor, $lvl-1);
    $e = $initial[$id][3] * pow($factor, $lvl-1);

    $res = array ( 'm' => $m, 'k' => $k, 'd' => $d, 'e' => $e );
    return $res;
}

// Time to build a $id level $lvl building in seconds.
function BuildDuration ( int $id, int $lvl, int $robots, int $nanits, int $speed ) : int
{
    $res = BuildPrice ( $id, $lvl );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    $secs = floor ( ( ( ($m + $k) / (2500 * (1 + $robots)) ) * pow (0.5, $nanits) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return (int)$secs;
}

function ShipyardMeetRequirement ( array $user, array $planet, int $id ) : bool
{
    if ( $id == GID_F_SC && ( $planet['b21'] < 2  || $user['r115'] < 2 ) ) return false;
    else if ( $id == GID_F_LC && ( $planet['b21'] < 4  || $user['r115'] < 6 ) ) return false;
    else if ( $id == GID_F_LF && ( $planet['b21'] < 1  || $user['r115'] < 1 ) ) return false;
    else if ( $id == GID_F_HF && ( $planet['b21'] < 3  || $user['r111'] < 2 || $user['r117'] < 2 ) ) return false;
    else if ( $id == GID_F_CRUISER && ( $planet['b21'] < 5  || $user['r117'] < 4 || $user['r121'] < 2 ) ) return false;
    else if ( $id == GID_F_BATTLESHIP && ( $planet['b21'] < 7  || $user['r118'] < 4 ) ) return false;
    else if ( $id == GID_F_COLON && ( $planet['b21'] < 4  || $user['r117'] < 3 ) ) return false;
    else if ( $id == GID_F_RECYCLER && ( $planet['b21'] < 4  || $user['r115'] < 6 || $user['r110'] < 2 ) ) return false;
    else if ( $id == GID_F_PROBE && ( $planet['b21'] < 3  || $user['r115'] < 3 || $user['r106'] < 2 ) ) return false;
    else if ( $id == GID_F_BOMBER && ( $planet['b21'] < 8  || $user['r117'] < 6 || $user['r122'] < 5 ) ) return false;
    else if ( $id == GID_F_SAT && ( $planet['b21'] < 1  ) ) return false;
    else if ( $id == GID_F_DESTRO && ( $planet['b21'] < 9  || $user['r118'] < 6 || $user['r114'] < 5 ) ) return false;
    else if ( $id == GID_F_DEATHSTAR && ( $planet['b21'] < 12 || $user['r118'] < 7 || $user['r114'] < 6 || $user['r199'] < 1 ) ) return false;
    else if ( $id == GID_F_BATTLECRUISER && ( $planet['b21'] < 8  || $user['r114'] < 5 || $user['r120'] < 12 || $user['r118'] < 5 ) ) return false;

    else if ( $id == GID_D_RL && ( $planet['b21'] < 1 ) ) return false;
    else if ( $id == GID_D_LL && ( $planet['b21'] < 2 || $user['r113'] < 1 || $user['r120'] < 3 ) ) return false;
    else if ( $id == GID_D_HL && ( $planet['b21'] < 4 || $user['r113'] < 3 || $user['r120'] < 6 ) ) return false;
    else if ( $id == GID_D_GAUSS && ( $planet['b21'] < 6 || $user['r113'] < 6 || $user['r109'] < 3 || $user['r110'] < 1 ) ) return false;
    else if ( $id == GID_D_ION && ( $planet['b21'] < 4 || $user['r121'] < 4 ) ) return false;
    else if ( $id == GID_D_PLASMA && ( $planet['b21'] < 8 || $user['r122'] < 7 ) ) return false;
    else if ( $id == GID_D_SDOME && ( $planet['b21'] < 1 || $user['r110'] < 2 ) ) return false;
    else if ( $id == GID_D_LDOME && ( $planet['b21'] < 6 || $user['r110'] < 6 ) ) return false;
    else if ( $id == GID_D_ABM && ( $planet['b21'] < 1 || $planet['b44'] < 2 ) ) return false;
    else if ( $id == GID_D_IPM && ( $planet['b21'] < 1 || $planet['b44'] < 4 || $user['r117'] < 1 ) ) return false;

    return true;
}

function ShipyardPrice ( int $id ) : array
{
    global $initial;
    $m = $initial[$id][0];
    $k = $initial[$id][1];
    $d = $initial[$id][2];
    $e = 0;
    $res = array ( 'm' => $m, 'k' => $k, 'd' => $d, 'e' => $e );
    return $res;
}

function ShipyardDuration ( int $id, int $shipyard, int $nanits, int $speed ) : int
{
    $res = ShipyardPrice ($id);
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    $secs = floor ( ( ( ($m + $k) / (2500 * (1 + $shipyard)) ) * pow (0.5, $nanits) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return (int)$secs;
}

function ResearchMeetRequirement ( array $user, array $planet, int $id ) : bool
{
    if ( $id == GID_R_ESPIONAGE && ( $planet['b31'] < 3 ) ) return false;
    else if ( $id == GID_R_COMPUTER && ( $planet['b31'] < 1 ) ) return false;
    else if ( $id == GID_R_WEAPON && ( $planet['b31'] < 4 ) ) return false;
    else if ( $id == GID_R_SHIELD && ( $user['r113'] < 3 || $planet['b31'] < 6 ) ) return false;
    else if ( $id == GID_R_ARMOUR && ( $planet['b31'] < 2 ) ) return false;
    else if ( $id == GID_R_ENERGY && ( $planet['b31'] < 1 ) ) return false;
    else if ( $id == GID_R_HYPERSPACE && ( $user['r113'] < 5 || $user['r110'] < 5 || $planet['b31'] < 7  ) ) return false;
    else if ( $id == GID_R_COMBUST_DRIVE && ( $user['r113'] < 1 || $planet['b31'] < 1 ) ) return false;
    else if ( $id == GID_R_IMPULSE_DRIVE && ( $user['r113'] < 1 || $planet['b31'] < 2  ) ) return false;
    else if ( $id == GID_R_HYPER_DRIVE && ( $user['r114'] < 3 || $planet['b31'] < 7  ) ) return false;
    else if ( $id == GID_R_LASER_TECH && ( $user['r113'] < 2 || $planet['b31'] < 1  ) ) return false;
    else if ( $id == GID_R_ION_TECH && ( $user['r120'] < 5 || $user['r113'] < 4 || $planet['b31'] < 4  ) ) return false;
    else if ( $id == GID_R_PLASMA_TECH && ( $user['r113'] < 8 || $user['r120'] < 10 || $user['r121'] < 5 || $planet['b31'] < 4 ) ) return false;
    else if ( $id == GID_R_IGN && ( $user['r108'] < 8 || $user['r114'] < 8 || $planet['b31'] < 10  ) ) return false;
    else if ( $id == GID_R_EXPEDITION && ( $user['r106'] < 4 || $user['r117'] < 3 || $planet['b31'] < 3 ) ) return false;
    else if ( $id == GID_R_GRAVITON && ( $planet['b31'] < 12 ) ) return false;

    return true;
}

function ResearchPrice ( int $id, int $lvl ) : array
{
    global $initial;

    $factor = $initial[$id][4];
    $m = $initial[$id][0] * pow($factor, $lvl-1);
    $k = $initial[$id][1] * pow($factor, $lvl-1);
    $d = $initial[$id][2] * pow($factor, $lvl-1);
    $e = $initial[$id][3] * pow($factor, $lvl-1);

    $res = array ( 'm' => $m, 'k' => $k, 'd' => $d, 'e' => $e );
    return $res;
}

function ResearchDuration ( int $id, int $lvl, int $reslab, int $speed ) : int
{
    if ( $id == GID_R_GRAVITON ) return 1;
    $res= ResearchPrice ($id, $lvl );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    $secs = floor ( ( ($m + $k) / (1000 * (1 + $reslab)) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return (int)$secs;
}

// IGN Calculation.
// Attach +IGN laboratories of maximum level to the current laboratory.
// The output is the overall level of the "virtual" lab.
function ResearchNetwork ( int $planetid, int $id ) : int
{
    global $db_prefix;
    $planet = GetPlanet ($planetid);
    if ($planet == null) return 0;
    $player_id = $planet['owner_id'];
    $user = LoadUser ($player_id);
    if ($user == null) return 0;
    $ign = $user ["r".GID_R_IGN];
    $reslab = $planet["b".GID_B_RES_LAB];
    $labs = array ();
    $labnum = 0;

    // List the player's planets (do not list moons and other special objects). Also skip planets that do not have lab.
    $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = $player_id AND type = ".PTYP_PLANET." AND b".GID_B_RES_LAB." > 0";
    $result = dbquery ($query);
    $pnum = dbrows ( $result );

    // Get all available labs sorted in descending order.
    while ($pnum--)
    {
        $p = dbarray ($result);
        if ( $p['planet_id'] == $planetid) continue;    // Skip the current planet.
        if ( ResearchMeetRequirement ( $user, $p, $id ) ) $labs[$labnum++] = $p["b".GID_B_RES_LAB];
    }
    rsort ( $labs );

    // Attach +IGN of available laboratories.
    for ($i=0; $i<$ign && $i<$labnum; $i++) $reslab += $labs[$i];
    return $reslab;
}

// Return a string of durations by days, hours, minutes, seconds.
function BuildDurationFormat ( int $seconds ) : string
{
    $res = "";
    $days = floor ($seconds / (24*3600));
    $hours = floor (intdiv($seconds, 3600) % 24);
    $mins = floor (intdiv($seconds, 60) % 60);
    $secs = round ($seconds % 60);
    if ($days) {
        $res .= "$days".loca("TIME_DAYS")." ";
    }
    if ($hours || $days) $res .= "$hours".loca("TIME_HOUR")." ";
    if ($mins || $days) $res .= "$mins".loca("TIME_MIN")." ";
    if ($secs) $res .= "$secs".loca("TIME_SEC");
    return $res;
}

function IsEnoughResources (array $planet, float $m, float $k, float $d, int $e) : bool
{
    if ( $m && $planet['m'] < $m ) return false;
    if ( $k && $planet['k'] < $k ) return false;
    if ( $d && $planet['d'] < $d ) return false;
    if ( $e && $planet['emax'] < $e ) return false;
    return true;
}

// Anything related to resource production and calculation.

// Get the size of the storages.
function store_capacity (int $lvl) : int { return 100000 + 50000 * (int)(ceil (pow (1.6, $lvl) - 1)); }

// Energy production
function prod_solar (int $lvl, float $pr) : float
{
    $prod = floor (20 * $lvl * pow (1.1, $lvl) * $pr);
    return $prod;
}
function prod_fusion (int $lvl, int $energo, float $pr) : float
{
    $prod = floor (30 * $lvl * pow (1.05 + $energo*0.01, $lvl) * $pr);
    return $prod;
}
function prod_sat (int $maxtemp) : float
{
    $prod = floor (($maxtemp / 4) + 20);
    return max (1, $prod);
}

// Mines production
function prod_metal (int $lvl, float $pr) : float { return floor (30 * $lvl * pow (1.1, $lvl) * $pr); }
function prod_crys (int $lvl, float $pr) : float { return floor (20 * $lvl * pow (1.1, $lvl) * $pr); }
function prod_deut (int $lvl, int $maxtemp, float $pr) : float { return floor ( 10 * $lvl * pow (1.1, $lvl) * $pr) * (1.28 - 0.002 * ($maxtemp)); }

// Energy consumption
function cons_metal (int $lvl) : float { return ceil (10 * $lvl * pow (1.1, $lvl)); }
function cons_crys (int $lvl) : float { return ceil (10 * $lvl * pow (1.1, $lvl)); }
function cons_deut (int $lvl) : float { return ceil (20 * $lvl * pow (1.1, $lvl)); }

// Consumption of deuterium by the fusion reactor
function cons_fusion (int $lvl, float $pr) : float { return ceil (10 * $lvl * pow (1.1, $lvl) * $pr) ; }

// Calculate resource production increase. Limit storage capacity.
// NOTE: The calculation excludes external events, such as the end of officers' actions, attack of another player, completion of building construction, etc.
function ProdResources ( array &$planet, int $time_from, int $time_to ) : void
{
    global $db_prefix, $GlobalUni;
    if ( $planet['type'] != PTYP_PLANET ) return;        // NOT a planet
    $user = LoadUser ($planet['owner_id']);
    if ($user == null) return;
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
function PlanetPrice (array $planet) : array
{
    $pp = array ();
    global $buildmap;
    global $fleetmap;
    global $defmap;

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
function FleetPrice ( array $fleet_obj ) : array
{
    global $fleetmap;
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