<?php
/**
 * @file planet.php
 * @brief Planet management.
 * @details Loads planet data, computes planet properties and handles planet-related state changes.
 */
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
`700`, `701`, `702`: Metal, crystal, deuterium (DOUBLE)
prod1, prod2, prod3: Percentage of mine production of metal, crystal, deuterium ( 0...1 DOUBLE DEFAULT 1)
prod4, prod12, prod212: Percentage of output of solar power plant, fusion and solar satellites ( 0...1 DOUBLE DEFAULT 1)
lastpeek: Time of last planet state update (INT UNSIGNED time)
lastakt: Last activity time (INT UNSIGNED time)
gate_until: JumpGate cooling time (INT UNSIGNED time)
remove: Planet deletion time (0 - do not delete). (INT UNSIGNED time)

R - random parameters

Cleaning of systems from "destroyed planets" takes place every 24 hours at 01-10 on the server.
"destroyed planet" exists for 1 day (24 hours) + the rest of the time until 01-10 server next day.

*/

/**
 * Create a planet (colony, home planet or moon) at the given coordinates.
 *
 * @param int $g Galaxy coordinate of the planet.
 * @param int $s System coordinate of the planet.
 * @param int $p Position coordinate of the planet.
 * @param int $owner_id ID of the planet owner.
 * @param int $colony 1 to create a colony, 0 to create the home planet.
 * @param int $moon 1 to create a moon, 0 to create a planet.
 * @param int $moonchance Chance of the moon appearing (used for the moon size).
 * @param int $when Creation time (Unix timestamp); 0 uses the current time.
 * @return int The created planet ID, or 0 if the position is occupied.
 */
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
            else if ($p <= 6) $diam = mt_rand ( $coltab['t2_a'], $coltab['t2_b'] ) * $coltab['t2_c'];
            else if ($p <= 9) $diam = mt_rand ( $coltab['t3_a'], $coltab['t3_b'] ) * $coltab['t3_c'];
            else if ($p <= 12) $diam = mt_rand ( $coltab['t4_a'], $coltab['t4_b'] ) * $coltab['t4_c'];
            else if ($p <= 15) $diam = mt_rand ( $coltab['t5_a'], $coltab['t5_b'] ) * $coltab['t5_c'];
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
    else if ($p <= 6) $temp = 30 + (rand() % 10) - 2*$p;
    else if ($p <= 9) $temp = 10 + (rand() % 10) - 2*$p;
    else if ($p <= 12) $temp = -10 + (rand() % 10) - 2*$p;
    else if ($p <= 15) $temp = -60 + (rand() % 10) - 2*$p;
    else $temp = -60 + (rand() % 10) - 2*$p;
    if ( $moon ) {
        $pl = LoadPlanet ($g, $s, $p, 1);
        if ($pl) $temp = $pl['temp'] - mt_rand (20, 30);
        else $temp -= mt_rand (20, 30);
    }

    // Add planet
    if ( $when == 0 ) $now = time();
    else $now = $when;
    $planet = array(
        'name' => $name, 'type' => $type, 'g' => $g, 's' => $s, 'p' => $p, 'owner_id' => $owner_id, 'diameter' => $diam, 'temp' => $temp, 'fields' => 0, 'maxfields' => $fields, 'date' => $now,
        GID_RC_METAL => $initial_met, GID_RC_CRYSTAL => $initial_crys, GID_RC_DEUTERIUM => 0,
        'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
    $id = AddDBRow ( $planet, "planets" );

    return $id;
}

/**
 * List all planets of the current user.
 *
 * @return mixed The result of the SQL query.
 */
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

/**
 * List all planets in the Galaxy at the given coordinates.
 *
 * @param int $g Galaxy coordinate.
 * @param int $s System coordinate.
 * @return mixed The result of the SQL query.
 */
function EnumPlanetsGalaxy (int $g, int $s) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = ".$g." AND s = ".$s." AND (type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET." OR type = ".PTYP_ABANDONED.") ORDER BY p ASC";
    $result = dbquery ($query);
    return $result;
}

/**
 * List custom galaxy objects to display on the Galaxy page.
 *
 * @param int $g Galaxy coordinate.
 * @param int $s System coordinate.
 * @return mixed The result of the SQL query.
 */
function EnumCustomPlanetsGalaxy (int $g, int $s) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = ".$g." AND s = ".$s." AND type >= ".PTYP_CUSTOM." ORDER BY p ASC";
    $result = dbquery ($query);
    return $result;
}

/**
 * Load the planet state by the specified coordinates without pre-processing.
 *
 * @param int $g Galaxy coordinate of the planet.
 * @param int $s System coordinate of the planet.
 * @param int $p Position coordinate of the planet.
 * @param int $type Type of the object to load (1 - planet, 2 - debris field, 3 - moon, otherwise a galaxy object game type).
 * @return mixed The planet array, or null if not found.
 */
function LoadPlanet (int $g, int $s, int $p, int $type) : mixed
{
    global $db_prefix;
    if ($type == 1) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND (type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET.") LIMIT 1;";
    else if ($type == 2) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND type=".PTYP_DF." LIMIT 1;";
    else if ($type == 3) $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND (type=".PTYP_MOON." OR type=".PTYP_DEST_MOON.") LIMIT 1;";
    else {
        // Treat a galaxy object's game type as a real planet type (PTYP)
        $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND type=".$type." LIMIT 1;";
    }
    $result = dbquery ($query);
    if ( $result ) return dbarray ($result);
    else return null;
}

/**
 * Load the planet state by its ID.
 *
 * @param int $planet_id ID of the planet.
 * @return mixed The planet array, or null if not found.
 */
function LoadPlanetById (int $planet_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE planet_id=$planet_id LIMIT 1;";
    $result = dbquery ($query);
    if ( $result ) {
        if ( dbrows($result) == 0 ) return null;
        return dbarray ($result);
    }
    else return null;
}

/**
 * Return the ID of the planet's moon (even if destroyed), or 0 if there is none.
 *
 * @param int $planet_id ID of the planet.
 * @return int The moon ID, or 0 if the planet has no moon.
 */
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

/**
 * Rename the planet: the name is limited to 20 characters, forbidden characters are removed and the name cannot be left blank.
 *
 * @param int $planet_id ID of the planet to rename.
 * @param string $name The new name of the planet.
 * @return void
 */
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
    $name = trim ((string) $name);
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

/**
 * Delete the planet from the database without any checks.
 *
 * @param int $planet_id ID of the planet to destroy.
 * @return void
 */
function DestroyPlanet (int $planet_id) : void
{
    global $db_prefix;
    FlushQueue ($planet_id);
    $query = "DELETE FROM ".$db_prefix."planets WHERE planet_id = $planet_id";
    dbquery ($query);
}

/**
 * Update the last activity time of the planet.
 *
 * @param int $planet_id ID of the planet.
 * @param int $t Activity time (Unix timestamp); 0 uses the current time.
 * @return void
 */
function UpdatePlanetActivity ( int $planet_id, int $t=0) : void
{
    global $db_prefix;
    if ($t == 0) $now = time ();
    else $now = $t;
    $query = "UPDATE ".$db_prefix."planets SET lastakt = $now WHERE planet_id = $planet_id";
    dbquery ($query);
}

// Management of debris fields.
// DF loading is performed by calling LoadPlanetById. DF is deleted by calling DestroyPlanet.

/**
 * Check if there is a debris field at the given coordinates.
 *
 * @param int $g Galaxy coordinate.
 * @param int $s System coordinate.
 * @param int $p Position coordinate.
 * @return int The debris field ID, or 0 if there is none.
 */
function HasDebris (int $g, int $s, int $p) : int
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g = $g AND s = $s AND p = $p AND type = ".PTYP_DF.";";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) return 0;
    $debris = dbarray ($result);
    return $debris['planet_id'];
}

/**
 * Create a new debris field at the specified coordinates.
 *
 * @param int $g Galaxy coordinate.
 * @param int $s System coordinate.
 * @param int $p Position coordinate.
 * @param int $owner_id ID of the debris field owner.
 * @return int The ID of the created or existing debris field.
 */
function CreateDebris (int $g, int $s, int $p, int $owner_id) : int
{
    global $db_prefix;
    $debris_id = HasDebris ($g, $s, $p);
    if ($debris_id > 0 ) return $debris_id;
    $now = time();
    $planet = array (
        'name' => loca("DEBRIS"), 'type' => PTYP_DF, 'g' => $g, 's' => $s, 'p' => $p, 'owner_id' => $owner_id, 'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => $now,
        GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
        'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
    $id = AddDBRow ( $planet, 'planets' );
    return $id;
}

/**
 * Collect resources from a debris field up to the given cargo capacity.
 *
 * @param int $planet_id ID of the debris field.
 * @param int $cargo Cargo capacity available for harvesting.
 * @param int $when Time of the harvest (Unix timestamp).
 * @return array The harvested amounts per resource type.
 */
function HarvestDebris (int $planet_id, int $cargo, int $when) : array
{
    global $db_prefix;
    global $transportableResources;
    $harvest = array ();
    $debris = LoadPlanetById ($planet_id);

    $dm = max (0, $debris[GID_RC_METAL]);
    $dk = max (0, $debris[GID_RC_CRYSTAL]);

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

    $query = "UPDATE ".$db_prefix."planets SET `".GID_RC_METAL."` = `".GID_RC_METAL."` - $m, `".GID_RC_CRYSTAL."` = `".GID_RC_CRYSTAL."` - $k, lastpeek = $when WHERE planet_id = $planet_id";
    dbquery ($query);

    foreach ($transportableResources as $i=>$rc) {
        $harvest[$rc] = 0;
    }
    $harvest[GID_RC_METAL] = $m;
    $harvest[GID_RC_CRYSTAL] = $k;
    return $harvest;
}

/**
 * Add resources to the specified debris field.
 *
 * @param int $id ID of the debris field.
 * @param int $m Amount of metal to add.
 * @param int $k Amount of crystal to add.
 * @return void
 */
function AddDebris (int $id, int $m, int $k) : void
{
    global $db_prefix;
    $now = time ();
    $query = "UPDATE ".$db_prefix."planets SET `".GID_RC_METAL."` = `".GID_RC_METAL."` + $m, `".GID_RC_CRYSTAL."` = `".GID_RC_CRYSTAL."` + $k, lastpeek = $now WHERE planet_id = $id";
    dbquery ($query);
}

/**
 * Return the game type of the given planet object.
 *
 * @param array $planet The planet array.
 * @return int The game type of the planet.
 */
function GetPlanetType (array $planet) : int
{
    if ( $planet['type'] >= PTYP_CUSTOM) return $planet['type'];
    else if ( $planet['type'] == PTYP_MOON || $planet['type'] == PTYP_DEST_MOON ) return GAME_PTYP_MOON;
    else if ( $planet['type'] == PTYP_DF) return GAME_PTYP_DF;
    else return GAME_PTYP_PLANET;
}

/**
 * Create a colonization phantom at the given coordinates.
 *
 * @param int $g Galaxy coordinate.
 * @param int $s System coordinate.
 * @param int $p Position coordinate.
 * @param int $owner_id ID of the colonizing player.
 * @return int The ID of the created phantom.
 */
function CreateColonyPhantom (int $g, int $s, int $p, int $owner_id) : int
{
    $planet = array(
        'name' => loca("PLANET_PHANTOM"), 'type' => PTYP_COLONY_PHANTOM, 'g' => $g, 's' => $s, 'p' => $p, 'owner_id' => $owner_id, 'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => time(),
        GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
        'lastpeek' => 0, 'lastakt' => 0, 'gate_until' => 0, 'remove' => 0 );
    $id = AddDBRow ( $planet, 'planets' );
    return $id;
}

/**
 * Add an abandoned colony at the given coordinates if the position is free.
 *
 * @param int $g Galaxy coordinate.
 * @param int $s System coordinate.
 * @param int $p Position coordinate.
 * @param int $when Creation time (Unix timestamp).
 * @return int The ID of the created abandoned colony, or 0 if the position is occupied.
 */
function CreateAbandonedColony (int $g, int $s, int $p, int $when) : int
{
    // If there is no planet at the given coordinates, add Abandoned Colony.
    if ( !HasPlanet ( $g, $s, $p ) )
    {
        $planet = array(
            'name' => loca("PLANET_ABANDONED"), 'type' => PTYP_ABANDONED, 'g' => $g, 's' => $s, 'p' => $p, 'owner_id' => USER_SPACE, 'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => $when,
            GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
            'lastpeek' => $when, 'lastakt' => $when, 'gate_until' => 0, 'remove' => $when + 24*3600 );
        $id = AddDBRow ( $planet, 'planets' );
    }
    else $id = 0;
    return $id;
}

/**
 * Check if a planet already exists at the given coordinates.
 *
 * @param int $g Galaxy coordinate.
 * @param int $s System coordinate.
 * @param int $p Position coordinate.
 * @return bool True if a planet (including destroyed or abandoned) exists there.
 */
function HasPlanet (int $g, int $s, int $p) : bool
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."planets WHERE g=$g AND s=$s AND p=$p AND ( type = ".PTYP_PLANET." OR type = ".PTYP_DEST_PLANET." OR type = ".PTYP_ABANDONED." );";
    $result = dbquery ($query);
    if ( dbrows ($result) ) return true;
    else return false;
}

/**
 * Change the amount of resources on the planet.
 *
 * @param array $cost Resource amounts to add or subtract.
 * @param int $planet_id ID of the planet.
 * @param string $sign Sign of the operation: "+" to add, "-" to subtract.
 * @return void
 */
function AdjustResources (array $cost, int $planet_id, string $sign) : void
{
    global $db_prefix;
    global $resourcemap;
    $planet = LoadPlanetById ($planet_id);
    if ($planet === null) return;
    $now = time ();
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ($resourcemap as $i=>$rc) {
        if (isset($cost[$rc]) && $cost[$rc] && isset($planet[$rc])) {
            // Apply the addition/subtraction in PHP and clamp the result so the
            // stored amount can never become negative (issue #117). The old SQL
            // form (`col` = `col` - X) could drive a resource below zero when
            // the planet had less than the cost (e.g. when the queue deduction
            // happens after resources were spent elsewhere).
            $amount = $cost[$rc];
            if ($sign === '-') $amount = -$amount;
            $value = max (0, $planet[$rc] + $amount);
            $query .= "`".$rc."`=".$value.", ";
        }
    }
    $query .= "lastpeek = ".$now." WHERE planet_id=$planet_id;";
    dbquery ($query);
}

/**
 * Destroy the moon: recall foreign fleets, redirect own fleets, update statistics and delete the moon.
 *
 * @param int $moon_id ID of the moon to destroy.
 * @param int $when Time of the destruction (Unix timestamp).
 * @param int $fleet_id ID of the fleet that destroyed the moon; its return is controlled by the battle engine.
 * @return void
 */
function DestroyMoon (int $moon_id, int $when, int $fleet_id) : void
{
    global $db_prefix;

    $moon = LoadPlanetById ( $moon_id );
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

/**
 * Recalculate the used and maximum fields of the planet.
 *
 * @param int $planet_id ID of the planet.
 * @return void
 */
function RecalcFields (int $planet_id) : void
{
    global $db_prefix;
    global $buildmap;
    $planet = LoadPlanetById ($planet_id);
    $fields = 0;
    if ( $planet['type'] == PTYP_MOON || $planet['type'] == PTYP_DEST_MOON ) $maxfields = 1;    // moon
    else $maxfields = floor (pow (($planet['diameter'] / 1000), 2));    // planet
    foreach ( $buildmap as $i=>$gid ) $fields += $planet[$gid];
    $maxfields += 5 * $planet[GID_B_TERRAFORMER] + 3 * $planet[GID_B_LUNAR_BASE];    // terraformer and moonbase
    $query = "UPDATE ".$db_prefix."planets SET fields=$fields, maxfields=$maxfields WHERE planet_id=$planet_id;";
    dbquery ($query);
}

/**
 * Create an outer space object at the given coordinates, or return the ID of the existing one.
 *
 * @param int $g Galaxy coordinate.
 * @param int $s System coordinate.
 * @param int $p Position coordinate.
 * @return int The ID of the outer space object.
 */
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
            GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0, 
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

/**
 * Set the fleet and defense amounts on the planet.
 *
 * @param int $planet_id ID of the planet.
 * @param array $objects Amounts of each fleet and defense type.
 * @return void
 */
function SetPlanetFleetDefense ( int $planet_id, array $objects ) : void
{
    global $db_prefix;
    global $defmap;
    global $fleetmap;
    global $rakmap;
    $param = array_merge ( array_diff($defmap, $rakmap), $fleetmap);
    $query = "UPDATE ".$db_prefix."planets SET ";
    foreach ( $param as $i=>$p ) {
        if ( $i == 0 ) $query .= "`$p`=".$objects[$p];
        else $query .= ", `$p`=".$objects[$p];
    }
    $query .= " WHERE planet_id=$planet_id;";
    dbquery ($query);
}

/**
 * Set the defense amounts on the planet.
 *
 * @param int $planet_id ID of the planet.
 * @param array $objects Amounts of each defense type.
 * @return void
 */
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

/**
 * Set the building levels on the planet.
 *
 * @param int $planet_id ID of the planet.
 * @param array $objects Levels of each building type.
 * @return void
 */
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

/**
 * Set the diameter of the planet or moon and recalculate the planet fields.
 *
 * @param int $planet_id ID of the planet.
 * @param int $diam The new diameter.
 * @return void
 */
function SetPlanetDiameter (int $planet_id, int $diam) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."planets SET diameter=$diam WHERE planet_id=$planet_id;";
    dbquery ($query);
    RecalcFields($planet_id);
}

/**
 * Return the planet name wrapped in a link to the admin area.
 *
 * @param array|null $planet The planet array, or null.
 * @return string The linked planet name, or an empty string if the planet is null.
 */
function AdminPlanetName (array|null $planet) : string
{
    global $session;
    if ($planet == null) return "";
    $planet_id = $planet['planet_id'];
    return "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet_id."\">".$planet['name']."</a>";
}

/**
 * Return the planet coordinates as a string with a link to the galaxy.
 *
 * @param array|null $p The planet array, or null.
 * @return string The linked coordinate string, or "[::]" if the planet is null.
 */
function AdminPlanetCoord (array|null $p) : string
{
    global $session;
    if ($p == null) return "[::]";
    return "[<a href=\"index.php?page=galaxy&session=$session&galaxy=".$p['g']."&system=".$p['s']."\">".$p['g'].":".$p['s'].":".$p['p']."</a>]";
}

/**
 * Create a home planet for the player at a free position.
 *
 * @param int $player_id ID of the player.
 * @return int The ID of the created home planet.
 */
function CreateHomePlanet (int $player_id) : int
{
    global $db_prefix;
    $ss = 15;
    $uni = LoadUniverse ();

    $ppg = $ss * $uni['systems'];        // number of systems in the galaxy

    $sg = 1;        // starting galaxy for registration
    $planet = array ();
    $i = 0;
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
        $g = (int)floor ( $d / $ppg ) + 1;
        $dd = $d - ($g - 1) * $ppg;
        $s = (int)floor ($dd/$ss) + 1;
        $p = (int)$dd % $ss + 1;

        if ( !$planet[(int)floor($d)] && $g>=1 && $p>3 && $p<13 ) {
            return CreatePlanet ( $g, $s, $p, $player_id, 0);
        }
        $d += 1.3;
    }

    Error ( "No more planets!!!" );
}

/**
 * Load the colonization settings table.
 *
 * @return mixed The first row of the colonization settings table.
 */
function LoadColonySettings () : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."coltab;";
    $result = dbquery ($query);
    return dbarray ($result);
}

/**
 * Save the colonization settings to the database.
 *
 * @param array $coltab The colonization settings array.
 * @return void
 */
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

/**
 * Calculate the phalanx scanning radius for the given phalanx level.
 *
 * @param int $level Level of the phalanx building.
 * @return int The phalanx radius in systems.
 */
function GetPhalanxRadius (int $level) : int {
    
    return $level * $level - 1;
}

/**
 * Check whether a phalanx scan can be performed from the origin moon on the target.
 *
 * @param array|null $origin The origin planet (moon) array, or null.
 * @param array|null $target The target planet array, or null.
 * @return bool True if the target is within the phalanx radius and belongs to another player.
 */
function CanPhalanx (array|null $origin, array|null $target) : bool {
    
    if ($origin == null || $target == null) return false;

    $system_radius = abs ($origin['s'] - $target['s']);
    $phalanx_radius = GetPhalanxRadius ($origin[GID_B_PHALANX]);

    return ($system_radius <= $phalanx_radius) && 
        ($origin['type'] == PTYP_MOON) && 
        ($target['owner_id'] != $origin['owner_id']) &&
        ($target['g'] == $origin['g']);
}

?>