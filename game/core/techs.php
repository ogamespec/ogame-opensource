<?php

// This module is a unification of what was previously scattered throughout all parts of the game (id.php, unit.php, prod.php, techtree)
// Here you will find various definitions of game object properties that can be changed by modifications.

// TODO: Fleet engine "patches" (small cargo, bomber, etc.), mod-friendly

// TODO: Fleet resource capture priority (mod-friendly plunder algo)

// Game object identifiers used commonly in code.

const GID_B_METAL_MINE = 1;     // Metal Mine
const GID_B_CRYS_MINE = 2;      // Crystal Mine
const GID_B_DEUT_SYNTH = 3;     // Deuterium Synthesizer
const GID_B_SOLAR = 4;          // Solar Plant
const GID_B_FUSION = 12;        // Fusion Reactor
const GID_B_ROBOTS = 14;        // Robotics Factory
const GID_B_NANITES = 15;       // Nanite Factory
const GID_B_SHIPYARD = 21;      // Shipyard
const GID_B_METAL_STOR = 22;    // Metal Storage
const GID_B_CRYS_STOR = 23;     // Crystal Storage
const GID_B_DEUT_STOR = 24;     // Deuterium Tank
const GID_B_RES_LAB = 31;       // Research Lab
const GID_B_TERRAFORMER = 33;   // Terraformer
const GID_B_ALLY_DEPOT = 34;    // Alliance Depot
const GID_B_LUNAR_BASE = 41;    // Lunar Base
const GID_B_PHALANX = 42;       // Sensor Phalanx
const GID_B_JUMP_GATE = 43;     // Jump Gate
const GID_B_MISS_SILO = 44;     // Missile Silo

const GID_R_ESPIONAGE = 106;    // Espionage Technology
const GID_R_COMPUTER = 108;     // Computer Technology
const GID_R_WEAPON = 109;       // Weapons Technology
const GID_R_SHIELD = 110;       // Shielding Technology
const GID_R_ARMOUR = 111;       // Armour Technology
const GID_R_ENERGY = 113;       // Energy Technology
const GID_R_HYPERSPACE = 114;   // Hyperspace Technology
const GID_R_COMBUST_DRIVE = 115;    // Combustion Drive
const GID_R_IMPULSE_DRIVE = 117;    // Impulse Drive
const GID_R_HYPER_DRIVE = 118;  // Hyperspace Drive
const GID_R_LASER_TECH = 120;   // Laser Technology   (TECH added just for the sake of symmetry with the other technologies of this group)
const GID_R_ION_TECH = 121;     // Ion Technology   (TECH added so as not to accidentally misspell GID_D_ION)
const GID_R_PLASMA_TECH = 122;  // Plasma Technology    (TECH added so as not to accidentally misspell GID_D_PLASMA)
const GID_R_IGN = 123;          // Intergalactic Research Network
const GID_R_EXPEDITION = 124;   // Expedition Technology
const GID_R_GRAVITON = 199;     // Graviton Technology

const GID_F_SC = 202;           // Small Cargo
const GID_F_LC = 203;           // Large Cargo
const GID_F_LF = 204;           // Light Fighter
const GID_F_HF = 205;           // Heavy Fighter
const GID_F_CRUISER = 206;      // Cruiser
const GID_F_BATTLESHIP = 207;   // Battleship  (It's intentionally not abbreviated to BS so as not to misspell Battlecruiser)
const GID_F_COLON = 208;        // Colony Ship
const GID_F_RECYCLER = 209;     // Recycler
const GID_F_PROBE = 210;        // Espionage Probe
const GID_F_BOMBER = 211;       // Bomber
const GID_F_SAT = 212;          // Solar Satellite
const GID_F_DESTRO = 213;       // Destroyer
const GID_F_DEATHSTAR = 214;    // Deathstar
const GID_F_BATTLECRUISER = 215;    // Battlecruiser aka Interceptor    (It's intentionally not abbreviated to BC so as not to misspell Battleship)

const GID_D_RL = 401;       // Rocket Launcher
const GID_D_LL = 402;       // Light Laser
const GID_D_HL = 403;       // Heavy Laser
const GID_D_GAUSS = 404;    // Gauss Cannon
const GID_D_ION = 405;      // Ion Cannon
const GID_D_PLASMA = 406;   // Plasma Turret
const GID_D_SDOME = 407;    // Small Shield Dome
const GID_D_LDOME = 408;    // Large Shield Dome
const GID_D_ABM = 502;      // Anti-Ballistic Missiles
const GID_D_IPM = 503;      // Interplanetary Missiles

const GID_RC_METAL = 700;       // Metal
const GID_RC_CRYSTAL = 701;     // Crystal
const GID_RC_DEUTERIUM = 702;   // Deuterium
const GID_RC_ENERGY = 703;      // Energy
const GID_RC_DM = 704;          // Dark Matter

const GID_MAX = 0xffff;         // Game object ID value must not be > this value (restriction) 

function IsBuilding (int $gid) : bool
{
    global $buildmap;
    return in_array($gid, $buildmap, true);
}

function IsResearch (int $gid) : bool
{
    global $resmap;
    return in_array($gid, $resmap, true);
}

function IsFleet (int $gid) : bool
{
    global $fleetmap;
    return in_array($gid, $fleetmap, true);
}

function IsDefense (int $gid) : bool
{
    global $defmap;
    return in_array($gid, $defmap, true);
}

// Defense, but no missiles
function IsDefenseNoRak (int $gid) : bool
{
    global $defmap, $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);
    return in_array($gid, $defmap_norak, true);
}

function IsResource (int $gid) : bool
{
    global $resourcemap;
    return in_array($gid, $resourcemap, true);
}

// Arrays of objects that are very commonly used elsewhere.

$buildmap = array ( GID_B_METAL_MINE, GID_B_CRYS_MINE, GID_B_DEUT_SYNTH, GID_B_SOLAR, GID_B_FUSION, GID_B_ROBOTS, GID_B_NANITES, GID_B_SHIPYARD, GID_B_METAL_STOR, GID_B_CRYS_STOR, GID_B_DEUT_STOR, GID_B_RES_LAB, GID_B_TERRAFORMER, GID_B_ALLY_DEPOT, GID_B_LUNAR_BASE, GID_B_PHALANX, GID_B_JUMP_GATE, GID_B_MISS_SILO );
$resmap = array ( GID_R_ESPIONAGE, GID_R_COMPUTER, GID_R_WEAPON, GID_R_SHIELD, GID_R_ARMOUR, GID_R_ENERGY, GID_R_HYPERSPACE, GID_R_COMBUST_DRIVE, GID_R_IMPULSE_DRIVE, GID_R_HYPER_DRIVE, GID_R_LASER_TECH, GID_R_ION_TECH, GID_R_PLASMA_TECH, GID_R_IGN, GID_R_EXPEDITION, GID_R_GRAVITON );
$fleetmap = array ( GID_F_SC, GID_F_LC, GID_F_LF, GID_F_HF, GID_F_CRUISER, GID_F_BATTLESHIP, GID_F_COLON, GID_F_RECYCLER, GID_F_PROBE, GID_F_BOMBER, GID_F_SAT, GID_F_DESTRO, GID_F_DEATHSTAR, GID_F_BATTLECRUISER );
$defmap = array ( GID_D_RL, GID_D_LL, GID_D_HL, GID_D_GAUSS, GID_D_ION, GID_D_PLASMA, GID_D_SDOME, GID_D_LDOME, GID_D_ABM, GID_D_IPM );
$rakmap = array ( GID_D_ABM, GID_D_IPM );
$resourcemap = array ( GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM, GID_RC_ENERGY, GID_RC_DM );
$storagemap = array (GID_RC_METAL=>GID_B_METAL_STOR, GID_RC_CRYSTAL=>GID_B_CRYS_STOR, GID_RC_DEUTERIUM=>GID_B_DEUT_STOR);
// Scoring resources (usually metal, crystal, deuterium)
$scoreResources = array(GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM);
// Transported resources (usually energy and DM are not transported)
$transportableResources = array(GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM);
// Resources that convert into debris fields
$debrisResources = array (GID_RC_METAL, GID_RC_CRYSTAL);
// Resources that are constantly increasing (have a first derivative with respect to time)
$resourcesWithNonZeroDerivative = array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM);
$prodPriority = array (GID_RC_ENERGY, GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM);
$naturalProduction = array (GID_RC_METAL => 20, GID_RC_CRYSTAL => 10);

// Level 1 cost.
// Factor in the exponential growth of technology. OGame is a game of exponential.
$initial = array (
    // Buildings
    GID_B_METAL_MINE => array (GID_RC_METAL=>60, GID_RC_CRYSTAL=>15, 'factor'=>1.5),
    GID_B_CRYS_MINE => array (GID_RC_METAL=>48, GID_RC_CRYSTAL=>24, 'factor'=>1.6),
    GID_B_DEUT_SYNTH => array (GID_RC_METAL=>225, GID_RC_CRYSTAL=>75, 'factor'=>1.5),
    GID_B_SOLAR => array (GID_RC_METAL=>75, GID_RC_CRYSTAL=>30, 'factor'=>1.5),
    GID_B_FUSION => array (GID_RC_METAL=>900, GID_RC_CRYSTAL=>360, GID_RC_DEUTERIUM=>180, 'factor'=>1.8),
    GID_B_ROBOTS => array (GID_RC_METAL=>400, GID_RC_CRYSTAL=>120, GID_RC_DEUTERIUM=>200, 'factor'=>2),
    GID_B_NANITES => array (GID_RC_METAL=>1000000, GID_RC_CRYSTAL=>500000, GID_RC_DEUTERIUM=>100000, 'factor'=>2),
    GID_B_SHIPYARD => array (GID_RC_METAL=>400, GID_RC_CRYSTAL=>200, GID_RC_DEUTERIUM=>100, 'factor'=>2),
    GID_B_METAL_STOR => array (GID_RC_METAL=>2000, 'factor'=>2),
    GID_B_CRYS_STOR => array (GID_RC_METAL=>2000, GID_RC_CRYSTAL=>1000, 'factor'=>2),
    GID_B_DEUT_STOR => array (GID_RC_METAL=>2000, GID_RC_CRYSTAL=>2000, 'factor'=>2),
    GID_B_RES_LAB => array (GID_RC_METAL=>200, GID_RC_CRYSTAL=>400, GID_RC_DEUTERIUM=>200, 'factor'=>2),
    GID_B_TERRAFORMER => array (GID_RC_CRYSTAL=>50000, GID_RC_DEUTERIUM=>100000, GID_RC_ENERGY=>1000, 'factor'=>2),
    GID_B_ALLY_DEPOT => array (GID_RC_METAL=>20000, GID_RC_CRYSTAL=>40000, 'factor'=>2),
    GID_B_MISS_SILO => array (GID_RC_METAL=>20000, GID_RC_CRYSTAL=>20000, GID_RC_DEUTERIUM=>1000, 'factor'=>2),
    // Moon
    GID_B_LUNAR_BASE => array (GID_RC_METAL=>20000, GID_RC_CRYSTAL=>40000, GID_RC_DEUTERIUM=>20000, 'factor'=>2),
    GID_B_PHALANX => array (GID_RC_METAL=>20000, GID_RC_CRYSTAL=>40000, GID_RC_DEUTERIUM=>20000, 'factor'=>2),
    GID_B_JUMP_GATE => array (GID_RC_METAL=>2000000, GID_RC_CRYSTAL=>4000000, GID_RC_DEUTERIUM=>2000000, 'factor'=>2),

    // Fleet
    GID_F_SC => array (GID_RC_METAL=>2000, GID_RC_CRYSTAL=>2000, 'factor'=>0),
    GID_F_LC => array (GID_RC_METAL=>6000, GID_RC_CRYSTAL=>6000, 'factor'=>0),
    GID_F_LF => array (GID_RC_METAL=>3000, GID_RC_CRYSTAL=>1000, 'factor'=>0),
    GID_F_HF => array (GID_RC_METAL=>6000, GID_RC_CRYSTAL=>4000, 'factor'=>0),
    GID_F_CRUISER => array (GID_RC_METAL=>20000, GID_RC_CRYSTAL=>7000, GID_RC_DEUTERIUM=>2000, 'factor'=>0),
    GID_F_BATTLESHIP => array (GID_RC_METAL=>45000, GID_RC_CRYSTAL=>15000, 'factor'=>0),
    GID_F_COLON => array (GID_RC_METAL=>10000, GID_RC_CRYSTAL=>20000, GID_RC_DEUTERIUM=>10000, 'factor'=>0),
    GID_F_RECYCLER => array (GID_RC_METAL=>10000, GID_RC_CRYSTAL=>6000, GID_RC_DEUTERIUM=>2000, 'factor'=>0),
    GID_F_PROBE => array (GID_RC_CRYSTAL=>1000, 'factor'=>0),
    GID_F_BOMBER => array (GID_RC_METAL=>50000, GID_RC_CRYSTAL=>25000, GID_RC_DEUTERIUM=>15000, 'factor'=>0),
    GID_F_SAT => array (GID_RC_CRYSTAL=>2000, GID_RC_DEUTERIUM=>500, 'factor'=>0),
    GID_F_DESTRO => array (GID_RC_METAL=>60000, GID_RC_CRYSTAL=>50000, GID_RC_DEUTERIUM=>15000, 'factor'=>0),
    GID_F_DEATHSTAR => array (GID_RC_METAL=>5000000, GID_RC_CRYSTAL=>4000000, GID_RC_DEUTERIUM=>1000000, 'factor'=>0),
    GID_F_BATTLECRUISER => array (GID_RC_METAL=>30000, GID_RC_CRYSTAL=>40000, GID_RC_DEUTERIUM=>15000, 'factor'=>0),

    // Defense
    GID_D_RL => array (GID_RC_METAL=>2000, 'factor'=>0),
    GID_D_LL => array (GID_RC_METAL=>1500, GID_RC_CRYSTAL=>500, 'factor'=>0),
    GID_D_HL => array (GID_RC_METAL=>6000, GID_RC_CRYSTAL=>2000, 'factor'=>0),
    GID_D_GAUSS => array (GID_RC_METAL=>20000, GID_RC_CRYSTAL=>15000, GID_RC_DEUTERIUM=>2000, 'factor'=>0),
    GID_D_ION => array (GID_RC_METAL=>2000, GID_RC_CRYSTAL=>6000, 'factor'=>0),
    GID_D_PLASMA => array (GID_RC_METAL=>50000, GID_RC_CRYSTAL=>50000, GID_RC_DEUTERIUM=>30000, 'factor'=>0),
    GID_D_SDOME => array (GID_RC_METAL=>10000, GID_RC_CRYSTAL=>10000, 'factor'=>0),
    GID_D_LDOME => array (GID_RC_METAL=>50000, GID_RC_CRYSTAL=>50000, 'factor'=>0),
    GID_D_ABM => array (GID_RC_METAL=>8000, GID_RC_DEUTERIUM=>2000, 'factor'=>0),
    GID_D_IPM => array (GID_RC_METAL=>12500, GID_RC_CRYSTAL=>2500, GID_RC_DEUTERIUM=>10000, 'factor'=>0),

    // Research
    GID_R_ESPIONAGE => array (GID_RC_METAL=>200, GID_RC_CRYSTAL=>1000, GID_RC_DEUTERIUM=>200, 'factor'=>2),
    GID_R_COMPUTER => array (GID_RC_CRYSTAL=>400, GID_RC_DEUTERIUM=>600, 'factor'=>2),
    GID_R_WEAPON => array (GID_RC_METAL=>800, GID_RC_CRYSTAL=>200, 'factor'=>2),
    GID_R_SHIELD => array (GID_RC_METAL=>200, GID_RC_CRYSTAL=>600, 'factor'=>2),
    GID_R_ARMOUR => array (GID_RC_METAL=>1000, 'factor'=>2),
    GID_R_ENERGY => array (GID_RC_CRYSTAL=>800, GID_RC_DEUTERIUM=>400, 'factor'=>2),
    GID_R_HYPERSPACE => array (GID_RC_CRYSTAL=>4000, GID_RC_DEUTERIUM=>2000, 'factor'=>2),
    GID_R_COMBUST_DRIVE => array (GID_RC_METAL=>400, GID_RC_DEUTERIUM=>600, 'factor'=>2),
    GID_R_IMPULSE_DRIVE => array (GID_RC_METAL=>2000, GID_RC_CRYSTAL=>4000, GID_RC_DEUTERIUM=>600, 'factor'=>2),
    GID_R_HYPER_DRIVE => array (GID_RC_METAL=>10000, GID_RC_CRYSTAL=>20000, GID_RC_DEUTERIUM=>6000, 'factor'=>2),
    GID_R_LASER_TECH => array (GID_RC_METAL=>200, GID_RC_CRYSTAL=>100, 'factor'=>2),
    GID_R_ION_TECH => array (GID_RC_METAL=>1000, GID_RC_CRYSTAL=>300, GID_RC_DEUTERIUM=>100, 'factor'=>2),
    GID_R_PLASMA_TECH => array (GID_RC_METAL=>2000, GID_RC_CRYSTAL=>4000, GID_RC_DEUTERIUM=>1000, 'factor'=>2),
    GID_R_IGN => array (GID_RC_METAL=>240000, GID_RC_CRYSTAL=>400000, GID_RC_DEUTERIUM=>160000, 'factor'=>2),
    GID_R_EXPEDITION => array (GID_RC_METAL=>4000, GID_RC_CRYSTAL=>8000, GID_RC_DEUTERIUM=>4000, 'factor'=>2),
    GID_R_GRAVITON => array (GID_RC_ENERGY=>300000, 'factor'=>3),
);


// Fleet and Defense Parameters.
$UnitParam = array (        // structure, shield, attack, cargo capacity, speed, consumption
    GID_F_SC => array ( 4000, 10, 5, 5000, 5000, 10 ),
    GID_F_LC => array ( 12000, 25, 5, 25000, 7500, 50 ),
    GID_F_LF => array ( 4000, 10, 50, 50, 12500, 20 ),
    GID_F_HF => array ( 10000, 25, 150, 100, 10000, 75 ),
    GID_F_CRUISER => array ( 27000, 50, 400, 800, 15000, 300 ),
    GID_F_BATTLESHIP => array ( 60000, 200, 1000, 1500, 10000, 500 ),
    GID_F_COLON => array ( 30000, 100, 50, 7500, 2500, 1000 ),
    GID_F_RECYCLER => array ( 16000, 10, 1, 20000, 2000, 300 ),
    GID_F_PROBE => array ( 1000, 0, 0, 5, 100000000, 1 ),
    GID_F_BOMBER => array ( 75000, 500, 1000, 500, 4000, 1000 ),
    GID_F_SAT => array ( 2000, 1, 1, 0, 0, 0 ),
    GID_F_DESTRO => array ( 110000, 500, 2000, 2000, 5000, 1000 ),
    GID_F_DEATHSTAR => array ( 9000000, 50000, 200000, 1000000, 100, 1 ),
    GID_F_BATTLECRUISER => array ( 70000, 400, 700, 750, 10000, 250 ),

    GID_D_RL => array ( 2000, 20, 80, 0, 0, 0 ),
    GID_D_LL => array ( 2000, 25, 100, 0, 0, 0 ),
    GID_D_HL => array ( 8000, 100, 250, 0, 0, 0 ),
    GID_D_GAUSS => array ( 35000, 200, 1100, 0, 0, 0 ),
    GID_D_ION => array ( 8000, 500, 150, 0, 0, 0 ),
    GID_D_PLASMA => array ( 100000, 300, 3000, 0, 0, 0 ),
    GID_D_SDOME => array ( 20000, 2000, 1, 0, 0, 0 ),
    GID_D_LDOME => array ( 100000, 10000, 1, 0, 0, 0 ),

    GID_D_ABM => array ( 8000, 1, 1, 0, 0, 0 ),
    GID_D_IPM => array ( 15000, 1, 12000, 0, 0, 0 ),
);

// Rapid-fire settings.
$RapidFire = array (
    GID_F_SC => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_LC => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_LF => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_HF => array ( GID_F_SC => 3, GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_CRUISER => array ( GID_F_LF => 6, GID_F_PROBE => 5, GID_F_SAT => 5, GID_D_RL => 10 ),
    GID_F_BATTLESHIP => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_COLON => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_RECYCLER => array ( GID_F_PROBE => 5, GID_F_SAT => 5 ),
    GID_F_PROBE => array ( ),
    GID_F_BOMBER => array ( GID_F_PROBE => 5, GID_F_SAT => 5, GID_D_RL => 20, GID_D_LL => 20, GID_D_HL => 10, GID_D_ION => 10 ),
    GID_F_SAT => array ( ),
    GID_F_DESTRO => array ( GID_F_PROBE => 5, GID_F_SAT => 5, GID_F_BATTLECRUISER => 2, GID_D_LL => 10 ),
    GID_F_DEATHSTAR => array ( GID_F_SC => 250, GID_F_LC => 250, GID_F_LF => 200, GID_F_HF => 100, GID_F_CRUISER => 33, GID_F_BATTLESHIP => 30, 
        GID_F_COLON => 250, GID_F_RECYCLER => 250, GID_F_PROBE => 1250, GID_F_BOMBER => 25, GID_F_SAT => 1250, GID_F_DESTRO => 5, GID_F_BATTLECRUISER => 15, 
        GID_D_RL => 200, GID_D_LL => 200, GID_D_HL => 100, GID_D_GAUSS => 50, GID_D_ION => 100 ),
    GID_F_BATTLECRUISER => array ( GID_F_SC => 3, GID_F_LC => 3, GID_F_HF => 4, GID_F_CRUISER => 4, GID_F_BATTLESHIP => 7, GID_F_PROBE => 5, GID_F_SAT => 5 ),
    // The defense doesn't feature rapid-fire
    GID_D_RL => array ( ),
    GID_D_LL => array ( ),
    GID_D_HL => array ( ),
    GID_D_GAUSS => array ( ),
    GID_D_ION => array ( ),
    GID_D_PLASMA => array ( ),
    GID_D_SDOME => array ( ),
    GID_D_LDOME => array ( ),
);

// A list of what-what-it-requires objects.
$requirements = array (

    GID_B_METAL_MINE => array (),
    GID_B_CRYS_MINE => array (),
    GID_B_DEUT_SYNTH => array (),
    GID_B_SOLAR => array (),
    GID_B_FUSION => array (GID_B_DEUT_SYNTH=>5, GID_R_ENERGY=>3),
    GID_B_ROBOTS => array (),
    GID_B_NANITES => array (GID_B_ROBOTS=>10, GID_R_COMPUTER=>10),
    GID_B_SHIPYARD => array (GID_B_ROBOTS=>2),
    GID_B_METAL_STOR => array (),
    GID_B_CRYS_STOR => array (),
    GID_B_DEUT_STOR => array (),
    GID_B_RES_LAB => array (),
    GID_B_TERRAFORMER => array (GID_B_NANITES=>1, GID_R_ENERGY=>12),
    GID_B_ALLY_DEPOT => array (),
    GID_B_MISS_SILO => array (GID_B_SHIPYARD=>1),
    GID_R_ESPIONAGE => array (GID_B_RES_LAB=>3),
    GID_R_COMPUTER => array (GID_B_RES_LAB=>1),
    GID_R_WEAPON => array (GID_B_RES_LAB=>4),
    GID_R_SHIELD => array (GID_R_ENERGY=>3, GID_B_RES_LAB=>6),
    GID_R_ARMOUR => array (GID_B_RES_LAB=>2),
    GID_R_ENERGY => array (GID_B_RES_LAB=>1),
    GID_R_HYPERSPACE => array (GID_R_ENERGY=>5, GID_R_SHIELD=>5, GID_B_RES_LAB=>7),
    GID_R_COMBUST_DRIVE => array (GID_R_ENERGY=>1),
    GID_R_IMPULSE_DRIVE => array (GID_R_ENERGY=>1, GID_B_RES_LAB=>2),
    GID_R_HYPER_DRIVE => array (GID_R_HYPERSPACE=>3),
    GID_R_LASER_TECH => array (GID_R_ENERGY=>2),
    GID_R_ION_TECH => array (GID_B_RES_LAB=>4, GID_R_LASER_TECH=>5, GID_R_ENERGY=>4),
    GID_R_PLASMA_TECH => array (GID_R_ENERGY=>8, GID_R_LASER_TECH=>10, GID_R_ION_TECH=>5),
    GID_R_IGN => array (GID_B_RES_LAB=>10, GID_R_COMPUTER=>8, GID_R_HYPERSPACE=>8),
    GID_R_EXPEDITION => array (GID_R_ESPIONAGE=>4, GID_R_IMPULSE_DRIVE=>3),
    GID_R_GRAVITON => array (GID_B_RES_LAB=>12),
    GID_F_SC => array (GID_B_SHIPYARD=>2, GID_R_COMBUST_DRIVE=>2),
    GID_F_LC => array (GID_B_SHIPYARD=>4, GID_R_COMBUST_DRIVE=>6),
    GID_F_LF => array (GID_B_SHIPYARD=>1, GID_R_COMBUST_DRIVE=>1),
    GID_F_HF => array (GID_B_SHIPYARD=>3, GID_R_ARMOUR=>2, GID_R_IMPULSE_DRIVE=>2),
    GID_F_CRUISER => array (GID_B_SHIPYARD=>5, GID_R_IMPULSE_DRIVE=>4, GID_R_ION_TECH=>2),
    GID_F_BATTLESHIP => array (GID_B_SHIPYARD=>7, GID_R_HYPER_DRIVE=>4),
    GID_F_COLON => array (GID_B_SHIPYARD=>4, GID_R_IMPULSE_DRIVE=>3),
    GID_F_RECYCLER => array (GID_B_SHIPYARD=>4, GID_R_COMBUST_DRIVE=>6, GID_R_SHIELD=>2),
    GID_F_PROBE => array (GID_B_SHIPYARD=>3, GID_R_COMBUST_DRIVE=>3, GID_R_ESPIONAGE=>2),
    GID_F_BOMBER => array (GID_R_IMPULSE_DRIVE=>6, GID_B_SHIPYARD=>8, GID_R_PLASMA_TECH=>5),
    GID_F_SAT => array (GID_B_SHIPYARD=>1),
    GID_F_DESTRO => array (GID_B_SHIPYARD=>9, GID_R_HYPER_DRIVE=>6, GID_R_HYPERSPACE=>5),
    GID_F_DEATHSTAR => array (GID_B_SHIPYARD=>12, GID_R_HYPER_DRIVE=>7, GID_R_HYPERSPACE=>6, GID_R_GRAVITON=>1),
    GID_F_BATTLECRUISER => array (GID_R_HYPERSPACE=>5, GID_R_LASER_TECH=>12, GID_R_HYPER_DRIVE=>5, GID_B_SHIPYARD=>8),
    GID_D_RL => array (GID_B_SHIPYARD=>1),
    GID_D_LL => array (GID_R_ENERGY=>1, GID_B_SHIPYARD=>2, GID_R_LASER_TECH=>3),
    GID_D_HL => array (GID_R_ENERGY=>3, GID_B_SHIPYARD=>4, GID_R_LASER_TECH=>6),
    GID_D_GAUSS => array (GID_B_SHIPYARD=>6, GID_R_ENERGY=>6, 109=>3, GID_R_SHIELD=>1),
    GID_D_ION => array (GID_B_SHIPYARD=>4, GID_R_ION_TECH=>4),
    GID_D_PLASMA => array (GID_B_SHIPYARD=>8, GID_R_PLASMA_TECH=>7),
    GID_D_SDOME => array (GID_R_SHIELD=>2, GID_B_SHIPYARD=>1),
    GID_D_LDOME => array (GID_R_SHIELD=>6, GID_B_SHIPYARD=>6),
    GID_D_ABM => array (GID_B_MISS_SILO=>2, GID_B_SHIPYARD=>1),
    GID_D_IPM => array (GID_B_MISS_SILO=>4, GID_B_SHIPYARD=>1, GID_R_IMPULSE_DRIVE=>1),
    GID_B_LUNAR_BASE => array (),
    GID_B_PHALANX => array (GID_B_LUNAR_BASE=>1),
    GID_B_JUMP_GATE => array (GID_B_LUNAR_BASE=>1, GID_R_HYPERSPACE=>7),

);

// An array that defines which buildings can be built for the specified planet type.
$CanBuildTab = array (

    PTYP_MOON => array ( GID_B_ROBOTS, GID_B_SHIPYARD, GID_B_METAL_STOR, GID_B_CRYS_STOR, GID_B_DEUT_STOR, GID_B_LUNAR_BASE, GID_B_PHALANX, GID_B_JUMP_GATE),
    PTYP_PLANET => array ( GID_B_METAL_MINE, GID_B_CRYS_MINE, GID_B_DEUT_SYNTH, GID_B_SOLAR, GID_B_FUSION, GID_B_ROBOTS, GID_B_NANITES, GID_B_SHIPYARD, GID_B_METAL_STOR, GID_B_CRYS_STOR, GID_B_DEUT_STOR, GID_B_RES_LAB, GID_B_TERRAFORMER, GID_B_ALLY_DEPOT, GID_B_MISS_SILO ),
    PTYP_DF => array (),
    PTYP_DEST_PLANET => array (),
    PTYP_COLONY_PHANTOM => array (),
    PTYP_DEST_MOON => array (),
    PTYP_ABANDONED => array (),
    PTYP_FARSPACE => array (),
);

?>