<?php

// Game object identifiers used commonly in code.
// I don't know why this module wasn't there before...

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

function IsBuilding (int $gid) : bool
{
    return $gid >= GID_B_METAL_MINE && $gid <= GID_B_MISS_SILO;
}

function IsResearch (int $gid) : bool
{
    return $gid >= GID_R_ESPIONAGE && $gid <= GID_R_GRAVITON;
}

function IsFleet (int $gid) : bool
{
    return $gid >= GID_F_SC && $gid <= GID_F_BATTLECRUISER;
}

function IsDefense (int $gid) : bool
{
    return $gid >= GID_D_RL && $gid <= GID_D_IPM;
}

// Defense, but no missiles
function IsDefenseNoRak (int $gid) : bool
{
    return $gid >= GID_D_RL && $gid <= GID_D_LDOME;
}

// Shooting defenses
function IsDefenseShoot (int $gid) : bool
{
    return $gid >= GID_D_RL && $gid <= GID_D_PLASMA;
}

// Arrays of objects that are very commonly used elsewhere.

$buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );
$resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );
$fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
$fleetmap_nosat = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 213, 214, 215 );    // without a solar satellite  (flottenversand)
$fleetmap_revnosat = array ( 215, 214, 213, 211, 210, 209, 208, 207, 206, 205, 204, 203, 202 );     // reverse order, without a solar satellite   (JumpGate)
$defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );
$defmap_norak = array ( 401, 402, 403, 404, 405, 406, 407, 408 );           // without missiles

?>