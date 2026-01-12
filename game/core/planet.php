<?php

// Planets and moons management: creation/colonization, destruction, loading planets from the database, renaming.
// All other special objects in the Galaxy are also considered planets (but of a different type).

/*

Formulas for calculating the size of the moon:
Minimal = floor (1000*(10 + 3 * Chance)^0,5) km
Maximum = floor (1000*(20 + 3 * Chance)^0,5) km 

FIELDS = FLOOR ( (DIAM / 1000) ^ 2 )
*/

/*
planet_id: Ordinal number (INT AUTO_INCREMENT PRIMARY KEY)
name: Planet name CHAR(20)
type: planet type (see PTYP definition)
g,s,p: coordinates where the planet is located (INT)
owner_id: Owner user ordinal number (INT)
R diameter: The diameter of the planet (INT)
R temp: Minimum temperature (INT)
fields: Number of developed fields (INT)
R maxfields: Maximum number of fields (INT)
date: Creation date (INT UNSIGNED time)
BBB: Building level of each type (INT DEFAULT 0)
DDD: Number of defenses of each type (INT DEFAULT 0)
FFF: Number of fleet of each type (INT DEFAULT 0)
m, k, d: Metal, crystal, deuterium (DOUBLE)
mprod, kprod, dprod: Percentage of mine production of metal, crystal, deuterium ( 0...1 DOUBLE)
sprod, fprod, ssprod: Percentage of output of solar power plant, fusion and solar satellites ( 0...1 DOUBLE)
lastpeek: Time of last planet state update (INT UNSIGNED time)
lastakt: Last activity time (INT UNSIGNED time)
gate_until: JumpGate cooling time (INT UNSIGNED time)
remove: Planet deletion time (0 - do not delete). (INT UNSIGNED time)

R - random parameters

Cleaning of systems from "destroyed planets" takes place every 24 hours at 01-10 on the server.
"destroyed planet" exists for 1 day (24 hours) + the rest of the time until 01-10 server next day.

*/

// Types of galactic objects (planets, moons, etc.)
const PTYP_MOON = 0;        // moon
const PTYP_PLANET = 1;      // planet; In the early stages of development for planets were reserved types for each picture (ice, desert, etc.). But after the algorithm of getting a picture from ID was cracked, there was no need in this.
const PTYP_DF = 10000;          // debris field
const PTYP_DEST_PLANET = 10001;         // destroyed planet (deleted by the player)
const PTYP_COLONY_PHANTOM = 10002;      // colonization phantom (exists for the duration of the Colonize mission)
const PTYP_DEST_MOON = 10003;           // destroyed moon (deleted by the player)
const PTYP_ABANDONED = 10004;           // abandoned colony (instead of the buggy "overlib" that was in the vanilla version)
const PTYP_FARSPACE = 20000;        // infinite distances (for expeditions)

// In addition to planet types for the database, there are also so-called "game planet types", such as those used for the Empire page (see GetPlanetType method).

// Create planet. Returns planet_id, or 0 if the position is occupied.
// colony: 1 - create colony, 0 - Home planet
// moon: 1 - create the moon
// moonchance: chance of the moon appearing (for the size of the moon)
function CreatePlanet ( int $g, int $s, int $p, int $owner_id, int $colony=1, int $moon=0, int $moonchance=0, int $when=0) : int
{
    global $db_prefix;

    // Check to see if the place is occupied?
    if ($moon) $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND p = '".$p."' AND ( type = ".PTYP_MOON." OR type = ".PTYP_DEST_MOON." )";
    else $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND p = '".$p."' AND ( type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET." OR type = ".PTYP_ABANDONED." )";
    $result = dbquery ($query);
    if ( dbrows ($result) != 0 ) return 0;

    $user = LoadUser ($owner_id);
    if ($user == null) return 0;
    loca_add ("common", $user['lang']);

    // Name of the planet.
    if ($moon) $name = loca_lang ("MOON", $user['lang']);
    else
    {
        if ($colony) $name = loca_lang ("PLANET_COLONY", $user['lang']);
        else $name = loca_lang ("PLANET_HOME", $user['lang']);
    }

    // Planet Type.
    if ($moon) $type = PTYP_MOON;
    else $type = PTYP_PLANET;

    // Diameter.
    if ($moon) $diam = floor ( 1000 * sqrt (mt_rand (10, 20) + 3*$moonchance)  );
    else
    {
        if ($colony)
        {
            $coltab = LoadColonySettings();

            // Planets are divided into 5 Tier (T1-T5). For each Tier there are three parameters (a, b, c), for RND.

            if ($p <= 3) $diam = mt_rand ( $coltab['t1_a'], $coltab['t1_b'] ) * $coltab['t1_c'];
            else if ($p >= 4 && $p <= 6) $diam = mt_rand ( $coltab['t2_a'], $coltab['t2_b'] ) * $coltab['t2_c'];
            else if ($p >= 7 && $p <= 9) $diam = mt_rand ( $coltab['t3_a'], $coltab['t3_b'] ) * $coltab['t3_c'];
            else if ($p >= 10 && $p <= 12) $diam = mt_rand ( $coltab['t4_a'], $coltab['t4_b'] ) * $coltab['t4_c'];
            else if ($p >= 13 && $p <= 15) $diam = mt_rand ( $coltab['t5_a'], $coltab['t5_b'] ) * $coltab['t5_c'];
            else $diam = mt_rand ( $coltab['t5_a'], $coltab['t5_b'] ) * $coltab['t5_c'];
        }
        else $diam = 12800;
    }
    
    // Maximum number of fields.
    if ($moon) $fields = 1;
    else $fields = floor (pow (($diam / 1000), 2));

    // Initial resources
    if ($moon) {
        $initial_met = 0;
        $initial_crys = 0;
    }
    else {
        $initial_met = 500;
        $initial_crys = 500;
    }

    // Temperature
    if ($p <= 3) $temp = 80 + (rand() % 10) - 2*$p;
    else if ($p >= 4 && $p <= 6) $temp = 30 + (rand() % 10) - 2*$p;
    else if ($p >= 7 && $p <= 9) $temp = 10 + (rand() % 10) - 2*$p;
    else if ($p >= 10 && $p <= 12) $temp = -10 + (rand() % 10) - 2*$p;
    else if ($p >= 13 && $p <= 15) $temp = -60 + (rand() % 10) - 2*$p;
    else $temp = -60 + (rand() % 10) - 2*$p;
    if ( $moon ) {
        $pl = LoadPlanet ($g, $s, $p, 1);
        if ($pl) $temp = $pl['temp'] - mt_rand (20, 30);
        else $temp -= mt_rand (20, 30);
    }

    // Add planet
    if ( $when == 0 ) $now = time();
    else $now = $when;
    $planet = array( 'name' => $name, 'type' => $type, 'g' => $g, 's' => $s, 'p' => $p, 'owner_id' => $owner_id, 'diameter' => $diam, 'temp' => $temp, 'fields' => 0, 'maxfields' => $fields, 'date' => $now,
                     'm' => $initial_met, 'k' => $initial_crys, 'd' => 0, 'mprod' => 1, 'kprod' => 1, 'dprod' => 1, 'sprod' => 1, 'fprod' => 1, 'ssprod' => 1, 'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
    $id = AddDBRow ( $planet, "planets" );

    return $id;
}

// List all planets of the current user. Return the result of the SQL query.
function EnumPlanets () : mixed
{
    global $db_prefix, $GlobalUser;
    $player_id = $GlobalUser['player_id'];

    // Get sort type.
    // sortby: Sort order of planets: 0 - colonization order (planet_id), 1 - coordinates, 2 - alphabetical order
    // sortorder: Order: 0 - ascending, 1 - descending
    $asc = $GlobalUser['sortorder'] == 0 ? "ASC" : "DESC";
    if ($GlobalUser['sortby'] == 0) $order = " ORDER BY planet_id $asc, type DESC";
    else if ($GlobalUser['sortby'] == 1) $order = " ORDER BY g $asc, s $asc, p $asc, type DESC";
    else if ($GlobalUser['sortby'] == 2) $order = " ORDER BY name $asc, type DESC";
    else $order = "";

    $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".$player_id."' AND type < ".PTYP_DF.$order;
    $result = dbquery ($query);
    return $result;
}

// List all the planets in the Galaxy.
function EnumPlanetsGalaxy (int $g, int $s) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND (type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET." OR type = ".PTYP_ABANDONED.") ORDER BY p ASC";
    $result = dbquery ($query);
    return $result;
}

// Get the state of the planet (array).
function GetPlanet ( int $planet_id) : array|null
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id = '".$planet_id."' LIMIT 1";
    $result = dbquery ($query);
    if ( dbrows($result) == 0 ) return null;
    $planet = dbarray ($result);
    if ($planet == null) return null;
    $user = LoadUser ( $planet['owner_id'] );
    if ($user == null) return null;

    $prem = PremiumStatus ($user);
    if ( $prem['engineer'] ) $e_factor = 1.1;
    else $e_factor = 1.0;

    $planet['mmax'] = store_capacity ( $planet[GID_B_METAL_STOR] );
    $planet['kmax'] = store_capacity ( $planet[GID_B_CRYS_STOR] );
    $planet['dmax'] = store_capacity ( $planet[GID_B_DEUT_STOR] );
    $planet['emax'] = prod_solar($planet[GID_B_SOLAR], $planet['sprod']) * $e_factor  + 
                    prod_fusion($planet[GID_B_FUSION], $user[GID_R_ENERGY], $planet['fprod']) * $e_factor  + 
                    prod_sat($planet['temp']+40) * $planet[GID_F_SAT] * $planet['ssprod'] * $e_factor ;

    $planet['econs'] = ( cons_metal ($planet[GID_B_METAL_MINE]) * $planet['mprod'] + 
                        cons_crys ($planet[GID_B_CRYS_MINE]) * $planet['kprod'] + 
                        cons_deut ($planet[GID_B_DEUT_SYNTH]) * $planet['dprod'] );

    $planet['e'] = floor ( $planet['emax'] - $planet['econs'] );
    $planet['factor'] = 1;
    if ( $planet['e'] < 0 ) $planet['factor'] = max (0, 1 - abs ($planet['e']) / $planet['econs']);
    return $planet;
}

// Load planet state by specified coordinates (without pre-processing)
// Return the $planet array, or null.
function LoadPlanet (int $g, int $s, int $p, int $type) : mixed
{
    global $db_prefix;
    if ($type == 1) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND (type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET.") LIMIT 1;";
    else if ($type == 2) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND type=".PTYP_DF." LIMIT 1;";
    else if ($type == 3) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND (type=".PTYP_MOON." OR type=".PTYP_DEST_MOON.") LIMIT 1;";
    else return null;
    $result = dbquery ($query);
    if ( $result ) return dbarray ($result);
    else return null;
}

// Load planet state by ID
// Return the $planet array, or null.
function LoadPlanetById (int $planet_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id=$planet_id LIMIT 1;";
    $result = dbquery ($query);
    if ( $result ) return dbarray ($result);
    else return null;
}

// If the planet has a moon (even destroyed), return its ID, otherwise return 0.
function PlanetHasMoon ( int $planet_id ) : int
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id = '".$planet_id."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return 0;    // Planet not found
    $planet = dbarray ($result);
    if ( $planet['type'] == PTYP_MOON || $planet['type'] == PTYP_DEST_MOON ) return 0;        // The planet itself is the moon
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$planet['g']."' AND s = '".$planet['s']."' AND p = '".$planet['p']."' AND (type = ".PTYP_MOON." OR type = ".PTYP_DEST_MOON.")";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return 0;    // No moon has been found for the planet.
    $planet = dbarray ($result);
    return $planet['planet_id'];
}

// The length of the planet name is max. 20 characters (the word (Moon) is also taken into account)
// The following characters are cut out of the name: / ' " * ( )
// If there are characters in the name ; , < > \ ` then the name doesn't change.
// If the name of a planet is blank, it is called "планета"
// More than one space is cut out.
function RenamePlanet (int $planet_id, string $name) : void
{
    // Find the planet.
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id = '".$planet_id."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0) return;    // Planet not found
    $planet = dbarray ($result);

    // Check the name.
    if ( $planet['type'] == PTYP_MOON) $name = mb_substr ($name, 0, 20-mb_strlen(" (".loca("MOON").")", "UTF-8"), "UTF-8");    // Limit the length of the name.
    else $name = mb_substr ($name, 0, 20, "UTF-8");
    $pattern = '/[;,<>\`]/';
    if (preg_match ($pattern, $name)) return;    // Forbidden characters.
    $pattern = '/[\\\\()*\"\']/';
    $name = preg_replace ($pattern, '', $name);
    $name = trim ($name);
    if (strlen ($name) == 0) {
        if ( $planet['type'] == PTYP_MOON ) $name = loca("MOON");
        else $name = "планета";
    }
    else
    {
        $name = preg_replace ('/\s\s+/', ' ', $name);    // Cut out the extra spaces.
        // If the planet is the moon, add a prefix.
        if ( $planet['type'] == PTYP_MOON ) $name .= " (".loca("MOON").")";
    }

    // If all is well, change the name of the planet.
    $query = "UPDATE ".$db_prefix."planets SET name = '".$name."' WHERE planet_id = $planet_id";
    dbquery ($query);
}

// NO CHECKS ARE MADE!!!
function DestroyPlanet (int $planet_id) : void
{
    global $db_prefix;
    FlushQueue ($planet_id);
    $query = "DELETE FROM ".$db_prefix."planets WHERE planet_id = $planet_id";
    dbquery ($query);
}

// Update the activity on the planet
function UpdatePlanetActivity ( int $planet_id, int $t=0) : void
{
    global $db_prefix;
    if ($t == 0) $now = time ();
    else $now = $t;
    $query = "UPDATE ".$db_prefix."planets SET lastakt = $now WHERE planet_id = $planet_id";
    dbquery ($query);
}

// Management of debris fields.
// DF loading is performed by calling GetPlanet. DF is deleted by calling DestroyPlanet.

// Checks if there is a DF at the given coordinates. Returns DF id, or 0.
function HasDebris (int $g, int $s, int $p) : int
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = $g AND s = $s AND p = $p AND type = ".PTYP_DF.";";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) return 0;
    $debris = dbarray ($result);
    return $debris['planet_id'];
}

// Creates a new DF at the specified coordinates
function CreateDebris (int $g, int $s, int $p, int $owner_id) : int
{
    global $db_prefix;
    $debris_id = HasDebris ($g, $s, $p);
    if ($debris_id > 0 ) return $debris_id;
    $now = time();
    $planet = array ( 'name' => loca("DEBRIS"), 'type' => PTYP_DF, 'g' => $g, 's' => $s, 'p' => $p, 'owner_id' => $owner_id, 'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => $now,
                      'm' => 0, 'k' => 0, 'd' => 0, 'mprod' => 0, 'kprod' => 0, 'dprod' => 0, 'sprod' => 0, 'fprod' => 0, 'ssprod' => 0, 'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
    $id = AddDBRow ( $planet, 'planets' );
    return $id;
}

// Collect DF with the specified capacity. The variables $harvest m/k contains the harvested DF.
function HarvestDebris (int $planet_id, int $cargo, int $when) : array
{
    global $db_prefix;
    $harvest = array ();
    $debris = GetPlanet ($planet_id);

    $dm = $debris['m'];
    $dk = $debris['k'];

    $m = $cargo / 2;
    if ( floor($dm) < $m) $m = $dm;
    $cargo -= $m;
    $k = $cargo;
    if ( floor($dk) < $k) $k = $dk;
    $cargo -= $k;
    if ( $cargo < 0 ) $cargo = 0;
    $m2 = $cargo;
    if ( floor ( $dm-$m) < $m2 ) $m2 = $dm - $m;
    $m += $m2;

    $query = "UPDATE ".$db_prefix."planets SET m = m - $m, k = k - $k, lastpeek = $when WHERE planet_id = $planet_id";
    dbquery ($query);

    $harvest['m'] = $m;
    $harvest['k'] = $k;
    return $harvest;
}

// Pour scrap into the specified DF
function AddDebris (int $id, int $m, int $k) : void
{
    global $db_prefix;
    $now = time ();
    $query = "UPDATE ".$db_prefix."planets SET m = m + $m, k = k + $k, lastpeek = $now WHERE planet_id = $id";
    dbquery ($query);
}

// Get a game type of planet.
function GetPlanetType (array $planet) : int
{
    if ( $planet['type'] == PTYP_MOON || $planet['type'] == PTYP_DEST_MOON ) return 3;
    else if ( $planet['type'] == PTYP_DF) return 2;
    else return 1;
}

// Create a colonization phantom. Return ID.
function CreateColonyPhantom (int $g, int $s, int $p, int $owner_id) : int
{
    $planet = array( 'name' => loca("PLANET_PHANTOM"), 'type' => PTYP_COLONY_PHANTOM, 'g' => $g, 's' => $s, 'p' => $p, 'owner_id' => $owner_id, 'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => time(),
                     'm' => 0, 'k' => 0, 'd' => 0, 'mprod' => 0, 'kprod' => 0, 'dprod' => 0, 'sprod' => 0, 'fprod' => 0, 'ssprod' => 0, 'lastpeek' => 0, 'lastakt' => 0, 'gate_until' => 0, 'remove' => 0 );
    $id = AddDBRow ( $planet, 'planets' );
    return $id;
}

// Add an abandoned colony.
function CreateAbandonedColony (int $g, int $s, int $p, int $when) : int
{
    // If there is no planet at the given coordinates, add Abandoned Colony.
    if ( !HasPlanet ( $g, $s, $p ) )
    {
        $planet = array( 'name' => loca("PLANET_ABANDONED"), 'type' => PTYP_ABANDONED, 'g' => $g, 's' => $s, 'p' => $p, 'owner_id' => USER_SPACE, 'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => $when,
                         'm' => 0, 'k' => 0, 'd' => 0, 'mprod' => 0, 'kprod' => 0, 'dprod' => 0, 'sprod' => 0, 'fprod' => 0, 'ssprod' => 0, 'lastpeek' => $when, 'lastakt' => $when, 'gate_until' => 0, 'remove' => $when + 24*3600 );
        $id = AddDBRow ( $planet, 'planets' );
    }
    else $id = 0;
    return $id;
}

// Check if there is already a planet at the given coordinates (for Colonization). Destroyed planets and abandoned colonies are also taken into account.
// Colonization phantoms don't count (whoever flies first)
function HasPlanet (int $g, int $s, int $p) : bool
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND ( type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET." OR type = ".PTYP_ABANDONED." );";
    $result = dbquery ($query);
    if ( dbrows ($result) ) return true;
    else return false;
}

// Change the amount of resources on the planet.
function AdjustResources (float|int $m, float|int $k, float|int $d, int $planet_id, string $sign) : void
{
    global $db_prefix;
    $now = time ();
    $query = "UPDATE ".$db_prefix."planets SET m=m $sign ".$m.", k=k $sign ".$k.", d=d $sign ".$d.", lastpeek = ".$now." WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Destroy the moon, return fleets, modify player stats.
// fleet_id - ID of the fleet that destroyed the moon. The return of this fleet is controlled by the battle engine.
function DestroyMoon (int $moon_id, int $when, int $fleet_id) : void
{
    global $db_prefix;

    $moon = GetPlanet ( $moon_id );
    $planet = LoadPlanet ( $moon['g'], $moon['s'], $moon['p'], 1 );
    if ( $moon == null || $planet == null ) return;

    // Recall foreign fleets flying to the destroyed moon (except for the fleet flying to destroy the moon - its return is controlled by the caller)
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE owner_id <> ".$planet['owner_id']." AND target_planet = $moon_id AND fleet_id <> $fleet_id;";
    $result = dbquery ( $query );
    $rows = dbrows ($result);
    while ( $rows-- )
    {
        $fleet_obj = dbarray ( $result );
        RecallFleet ( $fleet_obj['fleet_id'], $when );
    }

    // Redirect own returning and departing fleets to the planet.
    $query = "UPDATE ".$db_prefix."fleet SET start_planet = ".$planet['planet_id']." WHERE start_planet = $moon_id;";
    dbquery ( $query );
    $query = "UPDATE ".$db_prefix."fleet SET target_planet = ".$planet['planet_id']." WHERE owner_id = ".$planet['owner_id']." AND target_planet = $moon_id;";
    dbquery ( $query );    

    // Modify player statistics
    $pp = PlanetPrice ($moon);
    AdjustStats ( $moon['owner_id'], $pp['points'], $pp['fpoints'], 0, '-' );
    RecalcRanks ();

    // Everything else is destroyed forever
    DestroyPlanet ( $moon_id );

    // Make the current planet the planet under the destroyed moon.
    SelectPlanet ( $planet['owner_id'], $planet['planet_id'] );
}

// Recalculate fields.
function RecalcFields (int $planet_id) : void
{
    global $db_prefix;
    global $buildmap;
    $planet = GetPlanet ($planet_id);
    $fields = 0;
    if ( $planet['type'] == PTYP_MOON || $planet['type'] == PTYP_DEST_MOON ) $maxfields = 1;    // moon
    else $maxfields = floor (pow (($planet['diameter'] / 1000), 2));    // planet
    foreach ( $buildmap as $i=>$gid ) $fields += $planet[$gid];
    $maxfields += 5 * $planet[GID_B_TERRAFORMER] + 3 * $planet[GID_B_LUNAR_BASE];    // terraformer and moonbase
    $query = "UPDATE ".$db_prefix."planets SET fields=$fields, maxfields=$maxfields WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Endless distances.
function CreateOuterSpace (int $g, int $s, int $p) : int
{
    global $db_prefix;

    // If there is already an object there, return its ID.
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND type = ".PTYP_FARSPACE.";";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) 
    {
        $planet = array( 'name' => loca("FAR_SPACE"), 'type' => PTYP_FARSPACE, 'g' => $g, 's' => $s, 'p' => $p, 'owner_id' => USER_SPACE, 
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => time(),
            'm' => 0, 'k' => 0, 'd' => 0, 'mprod' => 0, 'kprod' => 0, 'dprod' => 0, 'sprod' => 0, 'fprod' => 0, 'ssprod' => 0, 
            'lastpeek' => 0, 'lastakt' => 0, 'gate_until' => 0, 'remove' => 0 );
        $id = AddDBRow ( $planet, 'planets' );
    }
    else
    {
        $planet = dbarray ($result);
        $id = $planet['planet_id'];
    }
    return $id;
}

// Set up a fleet and defenses on the planet.
function SetPlanetFleetDefense ( int $planet_id, array $objects ) : void
{
    global $db_prefix;
    global $defmap;
    global $fleetmap;
    $param = array_merge ( array_diff($defmap, [GID_D_ABM, GID_D_IPM]), $fleetmap);
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ( $param as $i=>$p ) {
        if ( $i == 0 ) $query .= "`$p`=".$objects[$p];
        else $query .= ", `$p`=".$objects[$p];
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Set up defenses on the planet.
function SetPlanetDefense ( int $planet_id, array $objects ) : void
{
    global $db_prefix;
    global $defmap;
    $param = $defmap;
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ( $param as $i=>$p ) {
        if ( $i == 0 ) $query .= "`$p`=".$objects[$p];
        else $query .= ", `$p`=".$objects[$p];
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Set up buildings on the planet.
function SetPlanetBuildings ( int $planet_id, array $objects ) : void
{
    global $db_prefix;
    global $buildmap;
    $param = $buildmap;
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ( $param as $i=>$p ) {
        if ( $i == 0 ) $query .= "`$p`=".$objects[$p];
        else $query .= ", `$p`=".$objects[$p];
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Set the diameter of the planet/moon. After setting the new diameter, the planet fields are recalculated.
function SetPlanetDiameter (int $planet_id, int $diam) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."planets SET diameter=$diam WHERE planet_id=$planet_id;";
    dbquery ($query);
    RecalcFields($planet_id);
}

// Return the name of the planet with a link to the admin area.
function AdminPlanetName (int $planet_id) : string
{
    global $session;
    $planet = GetPlanet ($planet_id);
    return "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet_id."\">".$planet['name']."</a>";
}

// Return planet coordinate string with a link to the galaxy
function AdminPlanetCoord (array $p) : string
{
    global $session;
    return "[<a href=\"index.php?page=galaxy&session=$session&galaxy=".$p['g']."&system=".$p['s']."\">".$p['g'].":".$p['s'].":".$p['p']."</a>]";
}

// Create a home planet, return the ID of the created planet
function CreateHomePlanet (int $player_id) : int
{
    global $db_prefix;
    $ss = 15;
    $uni = LoadUniverse ();

    $ppg = $ss * $uni['systems'];        // number of systems in the galaxy

    $sg = 1;        // starting galaxy for registration
    $planet = array ();
    for ( $i=0; $i<($sg-1)*$ppg; $i++) $planet[$i] = 1;
    for ( $i; $i<$uni['galaxies']*$ppg; $i++) $planet[$i] = 0;

    $query = "SELECT * FROM ".$db_prefix."planets WHERE g >= $sg AND p <= $ss AND type <> ".PTYP_COLONY_PHANTOM." ORDER BY g, s, p";
    $result = dbquery ($query);
    $rows = dbrows ( $result );
    while ($rows--)
    {
        $destination = dbarray ($result);
        $d = ( ($destination['g'] - 1) * $ppg ) + ($destination['s'] - 1) * $ss + $destination['p'] - 1;
        $planet[$d] = 1;
    }

    $d = ($sg - 1) * $ppg;
    while ($d < $ppg*9) 
    {
        $g = floor ( $d / $ppg ) + 1;
        $dd = $d - ($g - 1) * $ppg;
        $s = floor ($dd/$ss) + 1;
        $p = $dd % $ss + 1;

        if ( !$planet[floor($d)] && $g>=1 && $p>3 && $p<13 ) {
            return CreatePlanet ( $g, $s, $p, $player_id, 0);
        }
        $d += 1.3;
    }

    Error ( "No more planets!!!" );
    return 0;
}

// Load colonization settings.
function LoadColonySettings () : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."coltab;";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Save the colonization settings.
function SaveColonySettings (array $coltab) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."coltab SET " .
        "t1_a=".$coltab['t1_a'].", t1_b=".$coltab['t1_b'].", t1_c=".$coltab['t1_c'].", " .
        "t2_a=".$coltab['t2_a'].", t2_b=".$coltab['t2_b'].", t2_c=".$coltab['t2_c'].", " .
        "t3_a=".$coltab['t3_a'].", t3_b=".$coltab['t3_b'].", t3_c=".$coltab['t3_c'].", " .
        "t4_a=".$coltab['t4_a'].", t4_b=".$coltab['t4_b'].", t4_c=".$coltab['t4_c'].", " .
        "t5_a=".$coltab['t5_a'].", t5_b=".$coltab['t5_b'].", t5_c=".$coltab['t5_c']."; " ;
    dbquery ($query);
}

?>