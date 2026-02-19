<?php

// Auxiliary functions for the economic part of the game.

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

function TechPrice ( int $id, int $lvl ) : array
{
    global $initial, $resourcemap;

    // This formula does not have a single, generally accepted name, but it is most often referred to as:
    // - Exponential growth formula with a linear factor
    // - In the context of video games, it's called the "experience formula" or "level curve"

    $res = array();
    foreach ($resourcemap as $i=>$rc) {
        if (isset($initial[$id]) && isset($initial[$id][$rc])) {
            $factor = $initial[$id]['factor'];
            $res[$rc] = $initial[$id][$rc] * pow($factor, $lvl-1);
        }
        else $res[$rc] = 0;
    }

    return $res;
}

function TechPriceInPoints (array $cost) : int
{
    global $scoreResources;
    $points = 0;
    foreach ($scoreResources as $i=>$rc) {
        if (isset($cost[$rc])) $points += $cost[$rc];
    }
    return (int)$points;
}

// Time to produce a $id level $lvl tech in seconds. b1 - robots/shipyard/reslab. b2 - nanites (0 for research). const_factor - see in defs.php
function TechDuration ( int $id, int $lvl, int $const_factor, int $b1, int $b2, float $speed ) : int
{
    $res = TechPrice ( $id, $lvl );
    $m = $res[GID_RC_METAL]; $k = $res[GID_RC_CRYSTAL];     // structure points
    $secs = floor ( ( ( ($m + $k) / ($const_factor * (1 + $b1)) ) * pow (0.5, $b2) * 60*60 ) / $speed );
    if ($secs < 1) $secs = 1;
    return (int)$secs;
}

// IGN Calculation.
// Attach +IGN laboratories of maximum level to the current laboratory.
// The output is the overall level of the "virtual" lab.
function ResearchNetwork ( int $planetid, int $id ) : int
{
    global $db_prefix;
    $planet = LoadPlanetById ($planetid);
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

function IsEnoughResources (array $user, array $planet, array $cost) : bool
{
    foreach ($cost as $rc=>$value) {
        if (isset($user[$rc])) {
            if ($value > 0 && $user[$rc] < $value) return false;
        }
        else if (isset($planet[$rc])) {
            if ($value > 0 && $planet[$rc] < $value) return false;
        }
        else return false;      // An unknown resource type that neither the player nor the planet has.
    }
    // All conditions are met, all resources are sufficient.
    return true;
}

// Anything related to resource production and calculation.

// Get the size of the storages.
function store_capacity (int $lvl) : int {
    return 100000 + 50000 * (int)(ceil (pow (1.6, $lvl) - 1));
}

$PlanetProd = [

    GID_B_METAL_MINE => [
        'prod' => [
            GID_RC_METAL => function ($uni, $user, $planet) { 
                return floor (30 * $planet[GID_B_METAL_MINE] * pow (1.1, $planet[GID_B_METAL_MINE]) * $planet['prod'.GID_B_METAL_MINE]) * $uni['speed'];
            }
        ],
        'cons' => [
            GID_RC_ENERGY => function ($uni, $user, $planet) {
                return ceil (10 * $planet[GID_B_METAL_MINE] * pow (1.1, $planet[GID_B_METAL_MINE]) * $planet['prod'.GID_B_METAL_MINE]);
            }
        ]
    ],

    GID_B_CRYS_MINE => [
        'prod' => [
            GID_RC_CRYSTAL => function ($uni, $user, $planet) { 
                return floor (20 * $planet[GID_B_CRYS_MINE] * pow (1.1, $planet[GID_B_CRYS_MINE]) * $planet['prod'.GID_B_CRYS_MINE]) * $uni['speed'];
            }
        ],
        'cons' => [
            GID_RC_ENERGY => function ($uni, $user, $planet) {
                return ceil (10 * $planet[GID_B_CRYS_MINE] * pow (1.1, $planet[GID_B_CRYS_MINE]) * $planet['prod'.GID_B_CRYS_MINE]);
            }
        ]
    ],

    GID_B_DEUT_SYNTH => [
        'prod' => [
            GID_RC_DEUTERIUM => function ($uni, $user, $planet) { 
                return floor ( 10 * $planet[GID_B_DEUT_SYNTH] * pow (1.1, $planet[GID_B_DEUT_SYNTH]) * $planet['prod'.GID_B_DEUT_SYNTH]) * (1.28 - 0.002 * ($planet['temp']+40)) * $uni['speed'];
            }
        ],
        'cons' => [
            GID_RC_ENERGY => function ($uni, $user, $planet) {
                return ceil (20 * $planet[GID_B_DEUT_SYNTH] * pow (1.1, $planet[GID_B_DEUT_SYNTH]) * $planet['prod'.GID_B_DEUT_SYNTH]);
            }
        ]
    ],

    GID_B_SOLAR => [
        'prod' => [
            GID_RC_ENERGY => function ($uni, $user, $planet) {
                return floor (20 * $planet[GID_B_SOLAR] * pow (1.1, $planet[GID_B_SOLAR]) * $planet['prod'.GID_B_SOLAR]);
            }
        ],
        'cons' => []
    ],

    GID_B_FUSION => [
        'prod' => [
            GID_RC_ENERGY => function ($uni, $user, $planet) {
                return floor (30 * $planet[GID_B_FUSION] * pow (1.05 + $user[GID_R_ENERGY]*0.01, $planet[GID_B_FUSION]) * $planet['prod'.GID_B_FUSION]);
            }
        ],
        'cons' => [
            GID_RC_DEUTERIUM => function ($uni, $user, $planet) {
                return ceil (10 * $planet[GID_B_FUSION] * pow (1.1, $planet[GID_B_FUSION]) * $planet['prod'.GID_B_FUSION]);
            }
        ]
    ],

    GID_F_SAT => [
        'prod' => [
            GID_RC_ENERGY => function ($uni, $user, $planet) {
                $prod = floor ((($planet['temp']+40) / 4) + 20);
                $prod_sat = max (1, $prod);
                return $prod_sat * $planet[GID_F_SAT] * $planet['prod'.GID_F_SAT];
            }
        ],
        'cons' => []
    ],

];

function ProdBonus (array $uni, array $user, array $planet, int $rc, array &$prod_bonus) : void {

    // A production bonus offered by the original 0.84 mechanic. The bonus is not necessarily positive.
    $prem = PremiumStatus ($user);
    switch ($rc) {

        case GID_RC_METAL:
        case GID_RC_CRYSTAL:
        case GID_RC_DEUTERIUM:
            if ( $prem['geologist'] ) $prod_bonus[] = 1.1;
            $prod_bonus[] = $planet['factor'];
            break;

        case GID_RC_ENERGY:
            if ( $prem['engineer'] ) $prod_bonus[] = 1.1;
            break;
    }

    $param = [];
    $param['uni'] = $uni;
    $param['user'] = $user;
    $param['planet'] = $planet;
    $param['rc'] = $rc;
    ModsExecArrRef ('bonus_prod', $param, $prod_bonus);
}

function ConsBonus (array $uni, array $user, array $planet, int $rc, array &$cons_bonus) : void {

    // A bonus to consumption offered by the original 0.84 mechanic. The bonus is not necessarily positive.
    // none.

    $param = [];
    $param['uni'] = $uni;
    $param['user'] = $user;
    $param['planet'] = $planet;
    $param['rc'] = $rc;
    ModsExecArrRef ('bonus_cons', $param, $cons_bonus);
}

function ProdResources (array $uni, array $user, array &$planet) : void {

    global $prodPriority, $PlanetProd;
    global $naturalProduction;

    $eco = array();

    $eco['prod'] = [];                 // Производство ресурса по каждому типу игрового объекта
    $eco['prod_with_bonus'] = [];      // Производство ресурса по каждому типу игрового объекта (с учётом бонуса)
    $eco['cons'] = [];                 // Потребление ресурса по каждому типу игрового объекта
    $eco['cons_with_bonus'] = [];      // Потребление ресурса по каждому типу игрового объекта (с учётом бонуса)
    $eco['net_prod'] = [];             // Общее производство указанного ресурса
    $eco['net_cons'] = [];             // Общее потребление указанного ресурса
    $eco['balance'] = [];              // Баланс указанного ресурса (производство - потребление)

    foreach ($prodPriority as $i=>$rc) {

        // *** PRODUCTION

        // Get production bonus
        $prod_bonus = [];
        ProdBonus ($uni, $user, $planet, $rc, $prod_bonus);
        $eco['net_prod'][$rc] = 0;

        foreach ($PlanetProd as $gid=>$rules) {
            if (isset($rules['prod'][$rc])) {
                $res = $rules['prod'][$rc] ($uni, $user, $planet);
                $eco['prod'][$gid] = $res;
                foreach ($prod_bonus as $n=>$factor) {
                    $res *= $factor;
                }
                $eco['prod_with_bonus'][$gid] = $res;
                $eco['net_prod'][$rc] += ceil ($res);
            }
        }

        // *** CONSUMPTION

        // Get consumption bonus
        $cons_bonus = [];
        ConsBonus ($uni, $user, $planet, $rc, $cons_bonus);
        $eco['net_cons'][$rc] = 0;

        foreach ($PlanetProd as $gid=>$rules) {
            if (isset($rules['cons'][$rc])) {
                $res = $rules['cons'][$rc] ($uni, $user, $planet);
                $eco['cons'][$gid] = $res;
                foreach ($cons_bonus as $n=>$factor) {
                    $res *= $factor;
                }
                $eco['cons_with_bonus'][$gid] = $res;
                $eco['net_cons'][$rc] += ceil ($res);
            }
        }

        $eco['balance'][$rc] = floor ($eco['net_prod'][$rc] - $eco['net_cons'][$rc]);

        // *** POST-PROCESSING
        // Any special actions with the planet that affect resource production (Natural production, Production coefficient)

        switch ($rc) {
            case GID_RC_METAL:
            case GID_RC_CRYSTAL:
            case GID_RC_DEUTERIUM:
                if (isset($naturalProduction[$rc])) {
                    $eco['net_prod'][$rc] += $naturalProduction[$rc] * $uni['speed'];
                }
                break;
            case GID_RC_ENERGY:
                $planet['factor'] = 1;
                if ( $eco['balance'][$rc] < 0 ) $planet['factor'] = max (0, 1 - abs ($eco['balance'][$rc]) / $eco['net_cons'][$rc]);
                break;
        }

        $eco['balance'][$rc] = floor ($eco['net_prod'][$rc] - $eco['net_cons'][$rc]);
    }

    // Mods post-processing
    ModsExecRefRef ('prod_post_process', $planet, $eco);

    $planet['prod'] = $eco['prod'];
    $planet['prod_with_bonus'] = $eco['prod_with_bonus'];
    $planet['cons'] = $eco['cons'];
    $planet['cons_with_bonus'] = $eco['cons_with_bonus'];
    $planet['net_prod'] = $eco['net_prod'];
    $planet['net_cons'] = $eco['net_cons'];
    $planet['balance'] = $eco['balance'];
}

function SetDefaultProduction (array &$planet) : void {
    global $prodPriority;

    $planet['factor'] = 0;

    $eco = array();

    $eco['prod'] = [];
    $eco['prod_with_bonus'] = [];
    $eco['cons'] = [];
    $eco['cons_with_bonus'] = [];
    $eco['net_prod'] = [];
    $eco['net_cons'] = [];
    $eco['balance'] = [];

    foreach ($prodPriority as $i=>$rc) {
        $eco['net_prod'][$rc] = 0;
        $eco['net_cons'][$rc] = 0;
        $eco['balance'][$rc] = 0;
    }

    $planet['prod'] = $eco['prod'];
    $planet['prod_with_bonus'] = $eco['prod_with_bonus'];
    $planet['cons'] = $eco['cons'];
    $planet['cons_with_bonus'] = $eco['cons_with_bonus'];
    $planet['net_prod'] = $eco['net_prod'];
    $planet['net_cons'] = $eco['net_cons'];
    $planet['balance'] = $eco['balance'];
}

// Get the state of the planet (array) and update resource production from planet's lastpeek until $time_to. Limit storage capacity.
// NOTE: The calculation excludes external events, such as the end of officers' actions, attack of another player, completion of building construction, etc.
function GetUpdatePlanet ( int $planet_id, int $time_to) : array|null
{
    global $db_prefix, $GlobalUni;
    global $storagemap;
    global $resourcesWithNonZeroDerivative;

    $planet = LoadPlanetById ($planet_id);
    if ($planet == null) return null;
    if ( $planet['type'] != PTYP_PLANET ) {
        foreach ($storagemap as $rc=>$gid) {
            $planet['max'.$rc] = 0;
        }
        SetDefaultProduction ($planet);
        return $planet;        // NOT a planet
    }
    $user = LoadUser ( $planet['owner_id'] );
    if ($user == null) return $planet;
    if ( $user['player_id'] == USER_SPACE ) return $planet;    // technical account space

    // Planet Economics
    
    ProdResources ($GlobalUni, $user, $planet);

    // Update the state of the planet

    foreach ($storagemap as $rc=>$gid) {
        $planet['max'.$rc] = store_capacity ( $planet[$gid] );
    }

    $time_from = $planet['lastpeek'];
    $diff = $time_to - $time_from;

    // Calculate resource growth (only for resources that change over time)
    $update_query = "";
    foreach ($resourcesWithNonZeroDerivative as $i=>$rc) {

        $hourly = $planet['balance'][$rc];
        $cap = isset($planet['max'.$rc]) ? $planet['max'.$rc] : PHP_INT_MAX;
        if ($planet[$rc] < $cap) {
            $planet[$rc] = min ($planet[$rc] + ($hourly * $diff) / 3600, $cap);
            $update_query .= "`".$rc."` = ".$planet[$rc].", ";
        }
    }

    $planet_id = $planet['planet_id'];
    $query = "UPDATE ".$db_prefix."planets SET $update_query lastpeek = ".$time_to." WHERE planet_id = $planet_id";
    dbquery ($query);
    $planet['lastpeek'] = $time_to;

    // TODO: Still needed for IsEnoughResources method :(
    // Set energy as a virtual resource, obtained only when calling GetUpdatePlanet. Loading a raw planet using the LoadPlanetById method will not provide energy.
    $planet[GID_RC_ENERGY] = $planet['balance'][GID_RC_ENERGY];

    return $planet;
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
                $res = TechPrice ( $gid, $lv );
                $pp['points'] += TechPriceInPoints($res);
            }
        }
    }

    foreach ( $fleetmap as $i=>$gid ) {        // Fleet
        if (isset($planet[$gid])) {
            $level = $planet[$gid];
            if ($level > 0){
                $res = TechPrice ( $gid, 1 );
                $points = TechPriceInPoints($res);
                $pp['points'] += $points * $level;
                $pp['fleet_pts'] += $points * $level;
                $pp['fpoints'] += $level;
            }
        }
    }

    foreach ( $defmap as $i=>$gid ) {        // Defense
        if (isset($planet[$gid])) {
            $level = $planet[$gid];
            if ($level > 0){
                $res = TechPrice ( $gid, 1 );
                $points = TechPriceInPoints ($res);
                $pp['points'] += $points * $level;
                $pp['defense_pts'] += $points * $level;
            }
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
            $res = TechPrice ( $gid, 1 );
            $points += TechPriceInPoints($res) * $level;
            $fpoints += $level;
        }
    }

    $price['points'] = $points;
    $price['fpoints'] = $fpoints;
    return $price;
}

?>