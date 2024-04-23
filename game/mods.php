<?php

// Module for modification support
// This part of the game is completely optional, if you want to keep only vanilla version 0.84, do not pay attention to this module.

// Modifications are enabled by variables from the mods table.
// More: https://github.com/ogamespec/ogame-opensource/blob/master/Wiki/en/mods.md

// Get a variable from the modification settings table (String)
function GetModeVarStr ($var)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."mods WHERE var = '".$var."' LIMIT 1;";
    $result = dbquery ($query);
    if ( dbrows ($result) > 0 ) {
        $var = dbarray ( $result );
        return $var['value'];
    }
    return "";
}

// Get a variable from the modification settings table (Int)
function GetModeVarInt ($var)
{
    return intval (GetModeVarStr($var));
}

// Modify a created user for Carnage mode
function ModifyUserForCarnageMode ($player_id)
{
    global $db_prefix;

    // Random resources on planets and moons (min/max)
    $res_sizes = array();
    $res_sizes['m_min'] = 29000000;
    $res_sizes['m_max'] = 31000000;
    $res_sizes['k_min'] = 19000000;
    $res_sizes['k_max'] = 21000000;
    $res_sizes['d_min'] =  9000000;
    $res_sizes['d_max'] = 11000000;

    $uni = LoadUniverse ();
    $user = LoadUser ($player_id);
    $hplanetid = $user['hplanetid'];
    $hplanet = LoadPlanetById ($hplanetid);

    // Initialize RNG

    list($usec,$sec)=explode(" ",microtime());
    $seed = (int)($sec * $usec) & 0xffffffff;
    mt_srand ($seed);

    // Modify buildings on the Main Planet

    SetPlanetBuildings ( $hplanetid, GetCarnageModeBuildings(false) );
    AdjustResources (
        mt_rand($res_sizes['m_min'], $res_sizes['m_max']), 
        mt_rand($res_sizes['k_min'], $res_sizes['k_max']), 
        mt_rand($res_sizes['d_min'], $res_sizes['d_max']), 
        $hplanetid, '+');
    // We need to maximize the size of the planets
    SetPlanetDiameter ($hplanetid, 18000);

    // Add a moon to the Main Planet

    $moon_id = CreatePlanet ($hplanet['g'], $hplanet['s'], $hplanet['p'], $player_id, 0, 1, mt_rand(15,20));
    SetPlanetBuildings ( $moon_id, GetCarnageModeBuildings(true) );
    SetPlanetFleetDefense ( $moon_id, GetCarnageModeFleet(GetModeVarInt('mod_carnage_fleet_size') * 1000000000 ) );
    AdjustResources (
        mt_rand($res_sizes['m_min'], $res_sizes['m_max']), 
        mt_rand($res_sizes['k_min'], $res_sizes['k_max']), 
        mt_rand($res_sizes['d_min'], $res_sizes['d_max']), 
        $moon_id, '+');
    RecalcFields ($moon_id);

    // Create 8 more developed planets with moons

    for ($i=0; $i<8; $i++) {

        $g = mt_rand (1, $uni['galaxies']);
        $s = mt_rand (1, $uni['systems']);
        $p = mt_rand (4, 12);

        if (HasPlanet($g, $s, $p)) {
            $i--;   // to try again to establish a colony
            continue;
        }

        $planet_id = CreatePlanet ($g, $s, $p, $player_id, 1);

        // Draw the buildings

        SetPlanetBuildings ( $planet_id, GetCarnageModeBuildings(false) );
        AdjustResources (
            mt_rand($res_sizes['m_min'], $res_sizes['m_max']), 
            mt_rand($res_sizes['k_min'], $res_sizes['k_max']), 
            mt_rand($res_sizes['d_min'], $res_sizes['d_max']), 
            $planet_id, '+');
        // We need to maximize the size of the planets
        SetPlanetDiameter ($planet_id, 18000);        

        // Add the moon

        $moon_id = CreatePlanet ($g, $s, $p, $player_id, 0, 1, mt_rand(15,20));

        // On each moon, "draw" the fleet and base buildings

        SetPlanetBuildings ( $moon_id, GetCarnageModeBuildings(true) );
        SetPlanetFleetDefense ( $moon_id, GetCarnageModeFleet(GetModeVarInt('mod_carnage_fleet_size') * 1000000000 ) );
        AdjustResources (
            mt_rand($res_sizes['m_min'], $res_sizes['m_max']), 
            mt_rand($res_sizes['k_min'], $res_sizes['k_max']), 
            mt_rand($res_sizes['d_min'], $res_sizes['d_max']), 
            $moon_id, '+');
        RecalcFields ($moon_id);
    }

    // Modify the research

    $carnage_resmap = array (
        GID_R_ESPIONAGE => 15,  // Espionage Technology
        GID_R_COMPUTER => 15,  // Computer Technology
        GID_R_WEAPON => 18,  // Weapons Technology
        GID_R_SHIELD => 18,  // Shielding Technology
        GID_R_ARMOUR => 18,  // Armour Technology
        GID_R_ENERGY => 12,  // Energy Technology
        GID_R_HYPERSPACE => 10,  // Hyperspace Technology
        GID_R_COMBUST_DRIVE => 20,  // Combustion Drive
        GID_R_IMPULSE_DRIVE => 18,  // Impulse Drive
        GID_R_HYPER_DRIVE => 16,  // Hyperspace Drive
        GID_R_LASER_TECH => 12,  // Laser Technology
        GID_R_ION_TECH => 5,   // Ion Technology
        GID_R_PLASMA_TECH => 8,   // Plasma Technology
        GID_R_IGN => 5,   // Intergalactic Research Network
        GID_R_EXPEDITION => 9,   // Expedition Technology
        GID_R_GRAVITON => 1,   // Graviton Technology
    );
    $query = "UPDATE ".$db_prefix."users SET ";
    foreach ( $carnage_resmap as $gid=>$level)
    {
        $query .= "r$gid = $level, ";
    }
    $query .= "sniff = 0 ";         // just a safe field to get rid of the comma above.
    $query .= " WHERE player_id=$player_id;";
    dbquery ($query);

    InvalidateUserCache ();
    RecalcStats ($player_id);
}

// Get buildings on a planet/moon for Carnage mode
function GetCarnageModeBuildings ($moon)
{
    $objects = array();

    if ($moon) {
        $objects['b'.GID_B_METAL_MINE] = 0;
        $objects['b'.GID_B_CRYS_MINE] = 0;
        $objects['b'.GID_B_DEUT_SYNTH] = 0;
        $objects['b'.GID_B_SOLAR] = 0;
        $objects['b'.GID_B_FUSION] = 0;
        $objects['b'.GID_B_ROBOTS] = 0;
        $objects['b'.GID_B_NANITES] = 0;
        $objects['b'.GID_B_SHIPYARD] = 0;
        $objects['b'.GID_B_METAL_STOR] = 0;
        $objects['b'.GID_B_CRYS_STOR] = 0;
        $objects['b'.GID_B_DEUT_STOR] = 0;
        $objects['b'.GID_B_RES_LAB] = 0;
        $objects['b'.GID_B_TERRAFORMER] = 0;
        $objects['b'.GID_B_ALLY_DEPOT] = 0;
        $objects['b'.GID_B_LUNAR_BASE] = 7;    // Lunar Base
        $objects['b'.GID_B_PHALANX] = 7;    // Sensor Phalanx
        $objects['b'.GID_B_JUMP_GATE] = 1;    // Jump Gate
        $objects['b'.GID_B_MISS_SILO] = 0;
    }
    else {

        $objects['b'.GID_B_METAL_MINE] = 40;    // Metal Mine
        $objects['b'.GID_B_CRYS_MINE] = 35;    // Crystal Mine
        $objects['b'.GID_B_DEUT_SYNTH] = 35;    // Deuterium Synthesizer
        $objects['b'.GID_B_SOLAR] = 25;    // Solar Plant
        $objects['b'.GID_B_FUSION] = 0;
        $objects['b'.GID_B_ROBOTS] = 10;   // Robotics Factory
        $objects['b'.GID_B_NANITES] = 10;   // Nanite Factory
        $objects['b'.GID_B_SHIPYARD] = 12;   // Shipyard
        $objects['b'.GID_B_METAL_STOR] = 15;   // Metal Storage
        $objects['b'.GID_B_CRYS_STOR] = 15;   // Crystal Storage
        $objects['b'.GID_B_DEUT_STOR] = 15;   // Deuterium Tank
        $objects['b'.GID_B_RES_LAB] = 12;   // Research Lab
        $objects['b'.GID_B_TERRAFORMER] = 0;
        $objects['b'.GID_B_ALLY_DEPOT] = 0;
        $objects['b'.GID_B_LUNAR_BASE] = 0;
        $objects['b'.GID_B_PHALANX] = 0;
        $objects['b'.GID_B_JUMP_GATE] = 0;
        $objects['b'.GID_B_MISS_SILO] = 0;
    }

    return $objects;    
}

// Generate fleet for Carnage mode, specified number of points (standard resource points aka fleet cost)
function GetCarnageModeFleet ($points)
{
    $objects = array (
        'd401' => 0, 'd402' => 0, 'd403' => 0, 'd404' => 0, 'd405' => 0, 'd406' => 0, 'd407' => 0, 'd408' => 0, 
        'f202' => 0, 'f203' => 0, 'f204' => 0, 'f205' => 0, 'f206' => 0, 'f207' => 0, 'f208' => 0, 'f209' => 0, 'f210' => 0, 'f211' => 0, 'f212' => 0, 'f213' => 0, 'f214' => 0, 'f215' => 0 );

    $total = 0;

    while ($total < $points) {

        $id = mt_rand (GID_F_SC, GID_F_BATTLECRUISER);
        $price = ShipyardPrice ($id);

        // We will add fleets in relatively large chunks. Do not add colons, spy probes and sats.

        switch ($id)
        {
            case GID_F_SC: $count = mt_rand(4000, 5000); break;     // Small Cargo
            case GID_F_LC: $count = mt_rand(1000, 2000); break;     // Large Cargo
            case GID_F_LF: $count = mt_rand(10000, 20000); break;    // Light Fighter
            case GID_F_HF: $count = mt_rand(3333, 5555); break;     // Heavy Fighter
            case GID_F_CRUISER: $count = mt_rand(500, 1500); break;      // Cruiser
            case GID_F_BATTLESHIP: $count = mt_rand(300, 900); break;      // Battleship
            case GID_F_COLON: $count = 0; break;        // Colony Ship
            case GID_F_RECYCLER: $count = mt_rand(1000, 2000); break;     // Recycler
            case GID_F_PROBE: $count = 0; break;        // Espionage Probe
            case GID_F_BOMBER: $count = mt_rand(300, 400); break;      // Bomber
            case GID_F_SAT: $count = 0; break;        // Solar Satellite
            case GID_F_DESTRO: $count = mt_rand(200, 300); break;      // Destroyer
            case GID_F_DEATHSTAR: $count = 1; break;        // Deathstar
            case GID_F_BATTLECRUISER: $count = mt_rand(300, 500); break;      // Battlecruiser
        }

        if ($count == 0) {
            continue;
        }

        $total += ($price['m'] + $price['k'] + $price['d']) * $count;
        $objects['f'.$id] += $count;
    }

    return $objects;
}

?>