<?php
/**
 * @file botapi.php
 * @brief Bot API endpoints.
 * @details Exposes HTTP endpoints used to control and query the automated bot players.
 */
// Interface between bots and the engine.
// This is where all the built-in functions are located.

//------------------------------------------------------------------------------------
// Auxiliary functions

/**
 * Do nothing; used as a placeholder action for an idle bot.
 *
 * @return void
 */
function BotIdle () : void
{
}

/**
 * Check that a strategy with the specified name exists.
 *
 * @param string $name Name of the strategy to look for.
 * @return bool True if the strategy exists, false otherwise.
 */
function BotStrategyExists (string $name) : bool
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE name = '".$name."' LIMIT 1";
    $result = dbquery ($query);
    return ($result && dbrows($result) != 0);
}

/**
 * Start a new bot strategy in parallel.
 *
 * @param string $name Name of the strategy to start.
 * @return bool True if the strategy was started, false if it could not be started.
 */
function BotExec (string $name) : bool
{
    global $db_prefix, $BotID, $BotNow;
    $query = "SELECT * FROM ".$db_prefix."botstrat WHERE name = '".$name."' LIMIT 1";
    $result = dbquery ($query);
    if ($result && dbrows($result) != 0) {
        $row = dbarray ($result);
        $strat = json_decode ( $row['source'], true );
        $strat_id = $row['id'];

        foreach ( $strat['nodeDataArray'] as $i=>$arr ) {
            if ( $arr['category'] === "Start" ) {
                AddBotQueue ( $BotID, $strat_id, $arr['key'], $BotNow, 0 );
                return true;
            }
        }
        return false;
    }
    else return false;
}

/**
 * Get the value of a bot variable.
 *
 * @param string $var Name of the variable.
 * @param string|null $def_value Default value returned if the variable is not set.
 * @return string|null Value of the variable, or the default value if it is not set.
 */
function BotGetVar ( string $var, string|null $def_value=null ) : string|null
{
    global $BotID, $BotNow;
    return GetVar ( $BotID, $var, $def_value);
}

/**
 * Set the value of a bot variable.
 *
 * @param string $var Name of the variable.
 * @param string $value Value to set.
 * @return void
 */
function BotSetVar ( string $var, string $value ) : void
{
    global $BotID, $BotNow;
    SetVar ( $BotID, $var, $value );
}

//------------------------------------------------------------------------------------
// Construction/demolition of buildings, management of Resouce settings

/**
 * Check if the specified building can be built on the active planet.
 *
 * @param int $obj_id ID of the building to check.
 * @return bool True if the building can be built, false otherwise.
 */
function BotCanBuild (int $obj_id) : bool
{
    global $BotID, $BotNow;
    $user = LoadUser ($BotID);
    if ($user == null) return false;
    $aktplanet = GetUpdatePlanet ( $user['aktplanet'], $BotNow );
    if ($aktplanet == null) return false;
    $level = $aktplanet[$obj_id] + 1;
    $text = CanBuild ( $user, $aktplanet, $obj_id, $level, false );
    return ( $text === '' );
}

/**
 * Start building the specified building on the active planet.
 *
 * @param int $obj_id ID of the building to build.
 * @return int Number of seconds until the construction completes, or 0 if the building could not be started.
 */
function BotBuild (int $obj_id) : int
{
    global $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    if ($user == null) return 0;
    $aktplanet = LoadPlanetById ( $user['aktplanet'] );
    if ($aktplanet == null) return 0;
    $level = $aktplanet[$obj_id] + 1;
    $text = CanBuild ( $user, $aktplanet, $obj_id, $level, false );
    if ( $text === '' ) {
        $speed = $GlobalUni['speed'];
        $duration = floor (TechDuration ( $obj_id, $level, PROD_BUILDING_DURATION_FACTOR, $aktplanet[GID_B_ROBOTS], $aktplanet[GID_B_NANITES], $speed ));
        BuildEnque ( $user, $user['aktplanet'], $obj_id, 0, $BotNow);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        return $duration;
    }
    else return 0;
}

/**
 * Get the level of a building on the active planet.
 *
 * @param int $n ID of the building.
 * @return int Current level of the building, or 0 on error.
 */
function BotGetBuild (int $n) : int
{
    global $BotID, $BotNow;
    $bot = LoadUser ($BotID);
    if ($bot == null) return 0;
    $aktplanet = LoadPlanetById ( $bot['aktplanet'] );
    if ($aktplanet == null) return 0;
    return $aktplanet[$n];
}

/**
 * Set the resource settings of the active planet (percentages 0-100).
 *
 * @param int $last1 Production percentage for metal mines.
 * @param int $last2 Production percentage for crystal mines.
 * @param int $last3 Production percentage for deuterium mines.
 * @param int $last4 Output percentage for the solar power plant.
 * @param int $last12 Output percentage for the fusion power plant.
 * @param int $last212 Output percentage for solar satellites.
 * @return void
 */
function BotResourceSettings ( int $last1=100, int $last2=100, int $last3=100, int $last4=100, int $last12=100, int $last212=100 ) : void
{
    global $db_prefix, $BotID, $BotNow;
    $user = LoadUser ($BotID);
    if ($user == null) return;
    $aktplanet = LoadPlanetById ( $user['aktplanet'] );
    if ($aktplanet == null) return;

    if ( $last1 < 0 ) $last1 = 0;        // Should not be < 0.
    if ( $last2 < 0 ) $last2 = 0;
    if ( $last3 < 0 ) $last3 = 0;
    if ( $last4 < 0 ) $last4 = 0;
    if ( $last12 < 0 ) $last12 = 0;
    if ( $last212 < 0 ) $last212 = 0;

    if ( $last1 > 100 ) $last1 = 100;        // Should not be > 100.
    if ( $last2 > 100 ) $last2 = 100;
    if ( $last3 > 100 ) $last3 = 100;
    if ( $last4 > 100 ) $last4 = 100;
    if ( $last12 > 100 ) $last12 = 100;
    if ( $last212 > 100 ) $last212 = 100;

    // Make multiples of 10.
    $last1 = round ($last1 / 10) * 10 / 100;
    $last2 = round ($last2 / 10) * 10 / 100;
    $last3 = round ($last3 / 10) * 10 / 100;
    $last4 = round ($last4 / 10) * 10 / 100;
    $last12 = round ($last12 / 10) * 10 / 100;
    $last212 = round ($last212 / 10) * 10 / 100;

    $planet_id = $aktplanet['planet_id'];
    $query = "UPDATE ".$db_prefix."planets SET ";
    $query .= "prod1 = $last1, ";
    $query .= "prod2 = $last2, ";
    $query .= "prod3 = $last3, ";
    $query .= "prod4 = $last4, ";
    $query .= "prod12 = $last12, ";
    $query .= "prod212 = $last212 ";
    $query .= " WHERE planet_id = $planet_id";
    dbquery ($query);

    UpdatePlanetActivity ( $planet_id, $BotNow );
}

/**
 * Check if the current energy of the active planet is at or above the given value.
 *
 * @param int $energy Energy value to compare against.
 * @return bool True if the energy is at or above the value, false otherwise.
 */
function BotEnergyAbove (int $energy) : bool
{
    global $BotID, $BotNow;
    $user = LoadUser ($BotID);
    if ($user == null) return false;
    $aktplanet = GetUpdatePlanet ( $user['aktplanet'], $BotNow );
    if ($aktplanet == null) return false;
    $currentenergy = $aktplanet['e'];
    if ($currentenergy >= $energy){
      return true;
    } else {
      return false;
    }
}

//------------------------------------------------------------------------------------
// Fleet/defense construction (Shipyard)

/**
 * Build a number of ships or defense units in the shipyard of the active planet.
 *
 * @param int $obj_id ID of the ship or defense unit to build.
 * @param int $n Number of units to build.
 * @return int Number of seconds until the construction completes, or 0 on failure.
 */
function BotBuildFleet (int $obj_id, int $n) : int
{
    global $db_prefix, $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    if ($user == null) return 0;
    $aktplanet = LoadPlanetById ( $user['aktplanet'] );
    if ($aktplanet == null) return 0;
    $res = AddShipyard ($user['player_id'], $user['aktplanet'], $obj_id, $n, 0 );
    if ( $res ) {
        $speed = $GlobalUni['speed'];
        $now = ShipyardLatestTime ($aktplanet, $BotNow);
        $shipyard = $aktplanet[GID_B_SHIPYARD];
        $nanits = $aktplanet[GID_B_NANITES];
        $seconds = TechDuration ( $obj_id, 1, PROD_SHIPYARD_DURATION_FACTOR, $shipyard, $nanits, $speed );
        AddQueue ($user['player_id'], QTYP_SHIPYARD, $aktplanet['planet_id'], $obj_id, $n, $now, $seconds);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        return $seconds;
    }
    else return 0;
}

//------------------------------------------------------------------------------------
// Research

/**
 * Get the research level of the bot.
 *
 * @param int $n ID of the research.
 * @return int Current research level, or 0 on error.
 */
function BotGetResearch (int $n) : int
{
    global $BotID, $BotNow;
    $bot = LoadUser ($BotID);
    if ($bot == null) return 0;
    return $bot[$n];
}

/**
 * Check if the specified research can be started on the active planet.
 *
 * @param int $obj_id ID of the research to check.
 * @return bool True if the research can be started, false otherwise.
 */
function BotCanResearch (int $obj_id) : bool
{
    global $BotID, $BotNow;
    $user = LoadUser ($BotID);
    if ($user == null) return false;
    $aktplanet = GetUpdatePlanet ( $user['aktplanet'], $BotNow );
    if ($aktplanet == null) return false;
    $level = $aktplanet[$obj_id] + 1;
    $text = CanResearch ($user, $aktplanet, $obj_id, $level);
    return ($text === '' );
}

/**
 * Begin research on the active planet.
 *
 * @param int $obj_id ID of the research to start.
 * @return int Number of seconds until the research completes, or 0 if the research could not be started.
 */
function BotResearch (int $obj_id) : int
{
    global $BotID, $BotNow, $GlobalUni;
    $user = LoadUser ($BotID);
    if ($user == null) return 0;
    $aktplanet = LoadPlanetById ( $user['aktplanet'] );
    if ($aktplanet == null) return 0;
    $level = $aktplanet[$obj_id] + 1;
    $text = StartResearch ($user[player_id], $user[aktplanet], $obj_id, 0);
    if ( $text === '' ) {
        $speed = $uni['speed'];
        if ($now == 0) $now = time ();
        $reslab = ResearchNetwork ( $user['planet_id'], $obj_id );
        $prem = PremiumStatus ($user);
        if ( $prem['technocrat'] ) $r_factor = 1.1;
        else $r_factor = 1.0;
        $seconds = TechDuration ( $obj_id, $level, PROD_RESEARCH_DURATION_FACTOR, $reslab, 0, $speed * $r_factor);
        UpdatePlanetActivity ( $user['aktplanet'], $BotNow );
        return $seconds;
    }
    else return 0;
}

?>