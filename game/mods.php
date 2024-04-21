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
        106 => 15,  // Espionage Technology
        108 => 15,  // Computer Technology
        109 => 18,  // Weapons Technology
        110 => 18,  // Shielding Technology
        111 => 18,  // Armour Technology
        113 => 12,  // Energy Technology
        114 => 10,  // Hyperspace Technology
        115 => 20,  // Combustion Drive
        117 => 18,  // Impulse Drive
        118 => 16,  // Hyperspace Drive
        120 => 12,  // Laser Technology
        121 => 5,   // Ion Technology
        122 => 8,   // Plasma Technology
        123 => 5,   // Intergalactic Research Network
        124 => 9,   // Expedition Technology
        199 => 1,   // Graviton Technology
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
        $objects['b1'] = 0;
        $objects['b2'] = 0;
        $objects['b3'] = 0;
        $objects['b4'] = 0;
        $objects['b12'] = 0;
        $objects['b14'] = 0;
        $objects['b15'] = 0;
        $objects['b21'] = 0;
        $objects['b22'] = 0;
        $objects['b23'] = 0;
        $objects['b24'] = 0;
        $objects['b31'] = 0;
        $objects['b33'] = 0;
        $objects['b34'] = 0;
        $objects['b41'] = 7;    // Lunar Base
        $objects['b42'] = 7;    // Sensor Phalanx
        $objects['b43'] = 1;    // Jump Gate
        $objects['b44'] = 0;
    }
    else {

        $objects['b1'] = 40;    // Metal Mine
        $objects['b2'] = 35;    // Crystal Mine
        $objects['b3'] = 35;    // Deuterium Synthesizer
        $objects['b4'] = 25;    // Solar Plant
        $objects['b12'] = 0;
        $objects['b14'] = 10;   // Robotics Factory
        $objects['b15'] = 10;   // Nanite Factory
        $objects['b21'] = 12;   // Shipyard
        $objects['b22'] = 15;   // Metal Storage
        $objects['b23'] = 15;   // Crystal Storage
        $objects['b24'] = 15;   // Deuterium Tank
        $objects['b31'] = 12;   // Research Lab
        $objects['b33'] = 0;
        $objects['b34'] = 0;
        $objects['b41'] = 0;
        $objects['b42'] = 0;
        $objects['b43'] = 0;
        $objects['b44'] = 0;
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

        $id = mt_rand (202, 215);
        $price = ShipyardPrice ($id);

        // We will add fleets in relatively large chunks. Do not add colons, spy probes and sats.

        switch ($id)
        {
            case 202: $count = mt_rand(4000, 5000); break;     // Small Cargo
            case 203: $count = mt_rand(1000, 2000); break;     // Large Cargo
            case 204: $count = mt_rand(10000, 20000); break;    // Light Fighter
            case 205: $count = mt_rand(3333, 5555); break;     // Heavy Fighter
            case 206: $count = mt_rand(500, 1500); break;      // Cruiser
            case 207: $count = mt_rand(300, 900); break;      // Battleship
            case 208: $count = 0; break;        // Colony Ship
            case 209: $count = mt_rand(1000, 2000); break;     // Recycler
            case 210: $count = 0; break;        // Espionage Probe
            case 211: $count = mt_rand(300, 400); break;      // Bomber
            case 212: $count = 0; break;        // Solar Satellite
            case 213: $count = mt_rand(200, 300); break;      // Destroyer
            case 214: $count = 1; break;        // Deathstar
            case 215: $count = mt_rand(300, 500); break;      // Battlecruiser
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