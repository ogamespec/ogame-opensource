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
g,s,p: coordinates where the planet is located
owner_id: Owner user ordinal number
R diameter: The diameter of the planet
R temp: Minimum temperature
fields: Number of developed fields
R maxfields: Maximum number of fields
date: Creation date
bXX: Building level of each type
dXX: Number of defenses of each type
fXX: Number of fleet of each type
m, k, d: Metal, crystal, deuterium
mprod, kprod, dprod: Percentage of mine production of metal, crystal, deuterium ( 0...1 FLOAT)
sprod, fprod, ssprod: Percentage of output of solar power plant, fusion and solar satellites ( 0...1 FLOAT)
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
function CreatePlanet ( $g, $s, $p, $owner_id, $colony=1, $moon=0, $moonchance=0, $when=0)
{
    global $db_prefix;

    // Check to see if the place is occupied?
    if ($moon) $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND p = '".$p."' AND ( type = ".PTYP_MOON." OR type = ".PTYP_DEST_MOON." )";
    else $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND p = '".$p."' AND ( type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET." OR type = ".PTYP_ABANDONED." )";
    $result = dbquery ($query);
    if ( dbrows ($result) != 0 ) return 0;

    $user = LoadUser ($owner_id);
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
    $fields = floor (pow (($diam / 1000), 2));

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
    if ($moon) $planet = array( null, $name, $type, $g, $s, $p, $owner_id, $diam, $temp, 0, 1, $now,
                                          0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                          0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                          0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                          0, 0, 0, 1, 1, 1, 1, 1, 1, $now, $now, 0, 0 );
    else $planet = array( null, $name, $type, $g, $s, $p, $owner_id, $diam, $temp, 0, $fields, $now,
                                 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                 500, 500, 0, 1, 1, 1, 1, 1, 1, $now, $now, 0, 0 );
    $id = AddDBRow ( $planet, "planets" );

    return $id;
}

// List all planets of the user. Return the result of the SQL query.
function EnumPlanets ()
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
function EnumPlanetsGalaxy ($g, $s)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND (type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET." OR type = ".PTYP_ABANDONED.") ORDER BY p ASC";
    $result = dbquery ($query);
    return $result;
}

// Get the state of the planet (array).
function GetPlanet ( $planet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id = '".$planet_id."' LIMIT 1";
    $result = dbquery ($query);
    if ( dbrows($result) == 0 ) return NULL;
    $planet = dbarray ($result);
    $user = LoadUser ( $planet['owner_id'] );

    $prem = PremiumStatus ($user);
    if ( $prem['engineer'] ) $e_factor = 1.1;
    else $e_factor = 1.0;

    $planet['mmax'] = store_capacity ( $planet['b22'] );
    $planet['kmax'] = store_capacity ( $planet['b23'] );
    $planet['dmax'] = store_capacity ( $planet['b24'] );
    $planet['emax'] = prod_solar($planet['b4'], $planet['sprod']) * $e_factor  + 
				       prod_fusion($planet['b12'], $user['r113'], $planet['fprod']) * $e_factor  + 
					 prod_sat($planet['temp']+40) * $planet['f212'] * $planet['ssprod'] * $e_factor ;

    $planet['econs'] = ( cons_metal ($planet['b1']) * $planet['mprod'] + 
                                 cons_crys ($planet['b2']) * $planet['kprod'] + 
                                 cons_deut ($planet['b3']) * $planet['dprod'] );

    $planet['e'] = floor ( $planet['emax'] - $planet['econs'] );
    $planet['factor'] = 1;
    if ( $planet['e'] < 0 ) $planet['factor'] = max (0, 1 - abs ($planet['e']) / $planet['econs']);
    return $planet;
}

// Load planet state by specified coordinates (without pre-processing)
// Return the $planet array, or NULL.
function LoadPlanet ($g, $s, $p, $type)
{
    global $db_prefix;
    if ($type == 1) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND (type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET.") LIMIT 1;";
    else if ($type == 2) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND type=".PTYP_DF." LIMIT 1;";
    else if ($type == 3) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND (type=".PTYP_MOON." OR type=".PTYP_DEST_MOON.") LIMIT 1;";
    else return NULL;
    $result = dbquery ($query);
    if ( $result ) return dbarray ($result);
    else return NULL;
}

// Load planet state by ID
// Return the $planet array, or NULL.
function LoadPlanetById ($planet_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id=$planet_id LIMIT 1;";
    $result = dbquery ($query);
    if ( $result ) return dbarray ($result);
    else return NULL;
}

// If the planet has a moon (even destroyed), return its ID, otherwise return 0.
function PlanetHasMoon ( $planet_id )
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
function RenamePlanet ($planet_id, $name)
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
function DestroyPlanet ($planet_id)
{
    global $db_prefix;
    FlushQueue ($planet_id);
    $query = "DELETE FROM ".$db_prefix."planets WHERE planet_id = $planet_id";
    dbquery ($query);
}

// Update the activity on the planet
function UpdatePlanetActivity ( $planet_id, $t=0)
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
function HasDebris ($g, $s, $p)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = $g AND s = $s AND p = $p AND type = ".PTYP_DF.";";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) return 0;
    $debris = dbarray ($result);
    return $debris['planet_id'];
}

// Creates a new DF at the specified coordinates
function CreateDebris ($g, $s, $p, $owner_id)
{
    global $db_prefix;
    $debris_id = HasDebris ($g, $s, $p);
    if ($debris_id > 0 ) return $debris_id;
    $now = time();
    $planet = array ( null, loca("DEBRIS"), PTYP_DF, $g, $s, $p, $owner_id, 0, 0, 0, 0, $now,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, $now, $now, 0, 0 );
    $id = AddDBRow ( $planet, 'planets' );
    return $id;
}

// Collect DF with the specified capacity. The variables $harvest m/k contains the harvested DF.
function HarvestDebris ($planet_id, $cargo, $when)
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
function AddDebris ($id, $m, $k)
{
    global $db_prefix;
    $now = time ();
    $query = "UPDATE ".$db_prefix."planets SET m = m + $m, k = k + $k, lastpeek = $now WHERE planet_id = $id";
    dbquery ($query);
}

// Get a game type of planet.
function GetPlanetType ($planet)
{
    if ( $planet['type'] == PTYP_MOON || $planet['type'] == PTYP_DEST_MOON ) return 3;
    else if ( $planet['type'] == PTYP_DF) return 2;
    else return 1;
}

// Create a colonization phantom. Return ID.
function CreateColonyPhantom ($g, $s, $p, $owner_id)
{
    $planet = array( null, loca("PLANET_PHANTOM"), PTYP_COLONY_PHANTOM, $g, $s, $p, $owner_id, 0, 0, 0, 0, time(),
                             0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                             0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                             0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                             0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    $id = AddDBRow ( $planet, 'planets' );
    return $id;
}

// Add an abandoned colony.
function CreateAbandonedColony ($g, $s, $p, $when)
{
    // If there is no planet at the given coordinates, add Abandoned Colony.
    if ( !HasPlanet ( $g, $s, $p ) )
    {
        $planet = array( null, loca("PLANET_ABANDONED"), PTYP_ABANDONED, $g, $s, $p, USER_SPACE, 0, 0, 0, 0, $when,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, $when, $when, 0, $when + 24*3600 );
        $id = AddDBRow ( $planet, 'planets' );
    }
    else $id = 0;
    return $id;
}

// Check if there is already a planet at the given coordinates (for Colonization). Destroyed planets and abandoned colonies are also taken into account.
// Colonization phantoms don't count (whoever flies first)
function HasPlanet ($g, $s, $p)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND ( type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET." OR type = ".PTYP_ABANDONED." );";
    $result = dbquery ($query);
    if ( dbrows ($result) ) return 1;
    else return 0;
}

// Change the amount of resources on the planet.
function AdjustResources ($m, $k, $d, $planet_id, $sign)
{
    global $db_prefix;
    $now = time ();
    $query = "UPDATE ".$db_prefix."planets SET m=m $sign '".$m."', k=k $sign '".$k."', d=d $sign '".$d."', lastpeek = '".$now."' WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Destroy the moon, return fleets, modify player stats.
// fleet_id - ID of the fleet that destroyed the moon. The return of this fleet is controlled by the battle engine.
function DestroyMoon ($moon_id, $when, $fleet_id)
{
    global $db_prefix;

    $moon = GetPlanet ( $moon_id );
    $planet = LoadPlanet ( $moon['g'], $moon['s'], $moon['p'], 1 );
    if ( $moon == NULL || $planet == NULL ) return;

    // Return the fleets flying to the moon.
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE target_planet = $moon_id AND mission < ".FTYP_RETURN." AND fleet_id <> $fleet_id;";
    $result = dbquery ( $query );
    $rows = dbrows ($result);
    while ( $rows-- )
    {
        $fleet_obj = dbarray ( $result );
        RecallFleet ( $fleet_obj['fleet_id'], $when );
    }

    // Redirect returning and departing fleets to the planet.
    $query = "UPDATE ".$db_prefix."fleet SET start_planet = ".$planet['planet_id']." WHERE start_planet = $moon_id;";
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
function RecalcFields ($planet_id)
{
    global $db_prefix;
    global $buildmap;
    $planet = GetPlanet ($planet_id);
    $fields = 0;
    if ( $planet['type'] == PTYP_MOON || $planet['type'] == PTYP_DEST_MOON ) $maxfields = 1;    // moon
    else $maxfields = floor (pow (($planet['diameter'] / 1000), 2));    // planet
    foreach ( $buildmap as $i=>$gid ) $fields += $planet["b$gid"];
    $maxfields += 5 * $planet["b".GID_B_TERRAFORMER] + 3 * $planet["b".GID_B_LUNAR_BASE];    // terraformer and moonbase
    $query = "UPDATE ".$db_prefix."planets SET fields=$fields, maxfields=$maxfields WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Endless distances.
function CreateOuterSpace ($g, $s, $p)
{
    global $db_prefix;

    // If there is already an object there, return its ID.
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND type = ".PTYP_FARSPACE.";";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) 
    {
        $planet = array( null, loca("FAR_SPACE"), PTYP_FARSPACE, $g, $s, $p, USER_SPACE, 0, 0, 0, 0, time(),
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
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
function SetPlanetFleetDefense ( $planet_id, $objects )
{
    global $db_prefix;
    $param = array (  'd401', 'd402', 'd403', 'd404', 'd405', 'd406', 'd407', 'd408', 
                      'f202', 'f203', 'f204', 'f205', 'f206', 'f207', 'f208', 'f209', 'f210', 'f211', 'f212', 'f213', 'f214', 'f215' );
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ( $param as $i=>$p ) {
        if ( $i == 0 ) $query .= "$p=".$objects[$p];
        else $query .= ", $p=".$objects[$p];
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Set up defenses on the planet.
function SetPlanetDefense ( $planet_id, $objects )
{
    global $db_prefix;
    $param = array (  'd401', 'd402', 'd403', 'd404', 'd405', 'd406', 'd407', 'd408', 'd502', 'd503' );
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ( $param as $i=>$p ) {
        if ( $i == 0 ) $query .= "$p=".$objects[$p];
        else $query .= ", $p=".$objects[$p];
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Set up buildings on the planet.
function SetPlanetBuildings ( $planet_id, $objects )
{
    global $db_prefix;
    $param = array (  'b1', 'b2', 'b3', 'b4', 'b12', 'b14', 'b15', 'b21', 'b22', 'b23', 'b24', 'b31', 'b33', 'b34', 'b41', 'b42', 'b43', 'b44' );
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ( $param as $i=>$p ) {
        if ( $i == 0 ) $query .= "$p=".$objects[$p];
        else $query .= ", $p=".$objects[$p];
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

// Set the diameter of the planet/moon. After setting the new diameter, the planet fields are recalculated.
function SetPlanetDiameter ($planet_id, $diam)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."planets SET diameter=$diam WHERE planet_id=$planet_id;";
    dbquery ($query);
    RecalcFields($planet_id);
}

// Return the name of the planet with a link to the admin area.
function AdminPlanetName ($planet_id)
{
    global $session;
    $planet = GetPlanet ($planet_id);
    return "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet_id."\">".$planet['name']."</a>";
}

// Return planet coordinate string with a link to the galaxy
function AdminPlanetCoord ($p)
{
    global $session;
    return "[<a href=\"index.php?page=galaxy&session=$session&galaxy=".$p['g']."&system=".$p['s']."\">".$p['g'].":".$p['s'].":".$p['p']."</a>]";
}

// Create a home planet, return the ID of the created planet
function CreateHomePlanet ($player_id)
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
}

// Load colonization settings.
function LoadColonySettings ()
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."coltab;";
    $result = dbquery ($query);
    return dbarray ($result);
}

// Save the colonization settings.
function SaveColonySettings ($coltab)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."coltab SET " .
        "t1_a='".$coltab['t1_a']."', t1_b='".$coltab['t1_b']."', t1_c='".$coltab['t1_c']."', " .
        "t2_a='".$coltab['t2_a']."', t2_b='".$coltab['t2_b']."', t2_c='".$coltab['t2_c']."', " .
        "t3_a='".$coltab['t3_a']."', t3_b='".$coltab['t3_b']."', t3_c='".$coltab['t3_c']."', " .
        "t4_a='".$coltab['t4_a']."', t4_b='".$coltab['t4_b']."', t4_c='".$coltab['t4_c']."', " .
        "t5_a='".$coltab['t5_a']."', t5_b='".$coltab['t5_b']."', t5_c='".$coltab['t5_c']."'; " ;
    dbquery ($query);
}

?>