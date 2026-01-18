<?php

// Auxiliary functions for the economic part of OGame.

// Calculation of cost, build time and required conditions.

function TechMeetRequirement ( array $user, array $planet, int $id ) : bool
{
    global $CanBuildTab;
    global $buildmap, $resmap;
    global $requirements;

    // Check that the specified type of object can be built on the specified type of planet
    if (IsBuilding($id) && isset($CanBuildTab[$planet['type']])) {
        $can_build = in_array($id, $CanBuildTab[$planet['type']], true);
        if (!$can_build) return false;
    }

    // Collect building and research levels into one linear array
    $obj_levels = [];
    foreach ($buildmap as $i=>$gid) {
        $obj_levels[$gid] = $planet[$gid];
    }
    foreach ($resmap as $i=>$gid) {
        $obj_levels[$gid] = $user[$gid];
    }

    // Check the requirements for the specified object type using the requirements table (technology tree)
    if (isset($requirements[$id])) {
        foreach ($requirements[$id] as $gid=>$req_level) {
            if ($obj_levels[$gid] < $req_level) return false;
        }
    }

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

    $res = array ( GID_RC_METAL => $m, GID_RC_CRYSTAL => $k, GID_RC_DEUTERIUM => $d, GID_RC_ENERGY => $e );
    return $res;
}

// Time to build a $id level $lvl building in seconds.
function BuildDuration ( int $id, int $lvl, int $robots, int $nanits, int $speed ) : int
{
    $res = BuildPrice ( $id, $lvl );
    $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL]; $d = $res[GID_RC_DEUTERIUM]; $e = $res[GID_RC_ENERGY];
    $secs = floor ( ( ( ($m + $k) / (2500 * (1 + $robots)) ) * pow (0.5, $nanits) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return (int)$secs;
}

function ShipyardPrice ( int $id ) : array
{
    global $initial;
    $m = $initial[$id][0];
    $k = $initial[$id][1];
    $d = $initial[$id][2];
    $e = 0;
    $res = array ( GID_RC_METAL => $m, GID_RC_CRYSTAL => $k, GID_RC_DEUTERIUM => $d, GID_RC_ENERGY => $e );
    return $res;
}

function ShipyardDuration ( int $id, int $shipyard, int $nanits, int $speed ) : int
{
    $res = ShipyardPrice ($id);
    $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL]; $d = $res[GID_RC_DEUTERIUM]; $e = $res[GID_RC_ENERGY];
    $secs = floor ( ( ( ($m + $k) / (2500 * (1 + $shipyard)) ) * pow (0.5, $nanits) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return (int)$secs;
}

function ResearchPrice ( int $id, int $lvl ) : array
{
    global $initial;

    $factor = $initial[$id][4];
    $m = $initial[$id][0] * pow($factor, $lvl-1);
    $k = $initial[$id][1] * pow($factor, $lvl-1);
    $d = $initial[$id][2] * pow($factor, $lvl-1);
    $e = $initial[$id][3] * pow($factor, $lvl-1);

    $res = array ( GID_RC_METAL => $m, GID_RC_CRYSTAL => $k, GID_RC_DEUTERIUM => $d, GID_RC_ENERGY => $e );
    return $res;
}

function ResearchDuration ( int $id, int $lvl, int $reslab, int $speed ) : int
{
    if ( $id == GID_R_GRAVITON ) return 1;
    $res= ResearchPrice ($id, $lvl );
    $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL]; $d = $res[GID_RC_DEUTERIUM]; $e = $res[GID_RC_ENERGY];
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
    $ign = $user [GID_R_IGN];
    $reslab = $planet[GID_B_RES_LAB];
    $labs = array ();
    $labnum = 0;

    // List the player's planets (do not list moons and other special objects). Also skip planets that do not have lab.
    $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = $player_id AND type = ".PTYP_PLANET." AND `".GID_B_RES_LAB."` > 0";
    $result = dbquery ($query);
    $pnum = dbrows ( $result );

    // Get all available labs sorted in descending order.
    while ($pnum--)
    {
        $p = dbarray ($result);
        if ( $p['planet_id'] == $planetid) continue;    // Skip the current planet.
        if ( TechMeetRequirement ( $user, $p, $id ) ) $labs[$labnum++] = $p[GID_B_RES_LAB];
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
    if ( $m && $planet[GID_RC_METAL] < $m ) return false;
    if ( $k && $planet[GID_RC_CRYSTAL] < $k ) return false;
    if ( $d && $planet[GID_RC_DEUTERIUM] < $d ) return false;
    if ( $e && $planet[GID_RC_ENERGY] < $e ) return false;
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

    $hourly = prod_metal ($planet[GID_B_METAL_MINE], $planet['mprod']) * $planet['factor'] * $speed * $g_factor + 20 * $speed;        // Metal
    if ( $planet[GID_RC_METAL] < $planet['mmax'] ) {
        $planet[GID_RC_METAL] += ($hourly * $diff) / 3600;
        if ( $planet[GID_RC_METAL] >= $planet['mmax'] ) $planet[GID_RC_METAL] = $planet['mmax'];
    }

    $hourly = prod_crys ($planet[GID_B_CRYS_MINE], $planet['kprod']) * $planet['factor'] * $speed * $g_factor + 10 * $speed;        // Crystal
    if ( $planet[GID_RC_CRYSTAL] < $planet['kmax'] ) {
        $planet[GID_RC_CRYSTAL] += ($hourly * $diff) / 3600;
        if ( $planet[GID_RC_CRYSTAL] >= $planet['kmax'] ) $planet[GID_RC_CRYSTAL] = $planet['kmax'];
    }

    $hourly = prod_deut ($planet[GID_B_DEUT_SYNTH], $planet['temp']+40, $planet['dprod']) * $planet['factor'] * $speed * $g_factor;    // Deuterium
    $hourly -= cons_fusion ( $planet[GID_B_FUSION], $planet['fprod'] ) * $speed;	// fusion
    if ( $planet[GID_RC_DEUTERIUM] < $planet['dmax'] ) {
        $planet[GID_RC_DEUTERIUM] += ($hourly * $diff) / 3600;
        if ( $planet[GID_RC_DEUTERIUM] >= $planet['dmax'] ) $planet[GID_RC_DEUTERIUM] = $planet['dmax'];
    }

    $planet_id = $planet['planet_id'];
    $query = "UPDATE ".$db_prefix."planets SET `".GID_RC_METAL."` = ".$planet[GID_RC_METAL].", `".GID_RC_CRYSTAL."` = ".$planet[GID_RC_CRYSTAL].", `".GID_RC_DEUTERIUM."` = ".$planet[GID_RC_DEUTERIUM].", lastpeek = ".$time_to." WHERE planet_id = $planet_id";
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
        $level = $planet[$gid];
        if ($level > 0){
            for ( $lv = 1; $lv<=$level; $lv ++ )
            {
                $res = BuildPrice ( $gid, $lv );
                $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL]; $d = $res[GID_RC_DEUTERIUM]; $e = $res[GID_RC_ENERGY];
                $pp['points'] += ($m + $k + $d);
            }
        }
    }

    foreach ( $fleetmap as $i=>$gid ) {        // Fleet
        $level = $planet[$gid];
        if ($level > 0){
            $res = ShipyardPrice ( $gid);
            $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL]; $d = $res[GID_RC_DEUTERIUM]; $e = $res[GID_RC_ENERGY];
            $pp['points'] += ($m + $k + $d) * $level;
            $pp['fleet_pts'] += ($m + $k + $d) * $level;
            $pp['fpoints'] += $level;
        }
    }

    foreach ( $defmap as $i=>$gid ) {        // Defense
        $level = $planet[$gid];
        if ($level > 0){
            $res = ShipyardPrice ( $gid );
            $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL]; $d = $res[GID_RC_DEUTERIUM]; $e = $res[GID_RC_ENERGY];
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
        $level = $fleet_obj[$gid];
        if ($level > 0){
            $res = ShipyardPrice ( $gid );
            $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL]; $d = $res[GID_RC_DEUTERIUM]; $e = $res[GID_RC_ENERGY];
            $points += ($m + $k + $d) * $level;
            $fpoints += $level;
        }
    }

    $price['points'] = $points;
    $price['fpoints'] = $fpoints;
    return $price;
}

?>