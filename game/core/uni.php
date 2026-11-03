<?php
/**
 * @file uni.php
 * @brief Universe and galaxy data.
 * @details Provides helpers for universe configuration, galaxy dimensions and planet coordinates.
 */
// Managing the parameters of the universe.

/**
 * Load the universe configuration row.
 *
 * @return mixed The universe settings as an array, or false if it does not exist.
 */
function LoadUniverse () : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."uni;";
    $result = dbquery ($query);
    return dbarray ($result);
}

/**
 * Update the news texts and set how long they stay active.
 *
 * @param string $news1 First news text.
 * @param string $news2 Second news text.
 * @param int $days Number of days the news remains active.
 * @return void
 */
function UpdateNews (string $news1, string $news2, int $days) : void
{
    global $db_prefix;
    $until = time () + $days * 24 * 60 * 60;
    // Escape the free-form texts: they are interpolated into raw SQL.
    $news1 = addslashes ($news1);
    $news2 = addslashes ($news2);
    $query = "UPDATE ".$db_prefix."uni SET news1 = '".$news1."', news2 = '".$news2."', news_until = $until";
    dbquery ($query);
}

/**
 * Disable the news by clearing its expiry timestamp.
 *
 * @return void
 */
function DisableNews () : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."uni SET news_until = 0";
    dbquery ($query);
}

/**
 * Set all universe parameters at once and reload the cached universe data.
 *
 * @param int $speed Economy speed of the universe.
 * @param int $fspeed Fleet speed of the universe.
 * @param int $acs Whether ACS attacks are allowed.
 * @param int $fid Debris percentage for fleet units.
 * @param int $did Debris percentage for defense units.
 * @param int $defrepair Whether defense repair is enabled.
 * @param int $defrepair_delta Percentage of defenses repaired after a battle.
 * @param int $galaxies Number of galaxies.
 * @param int $systems Number of systems per galaxy.
 * @param int $rapid Whether rapidfire is enabled.
 * @param int $moons Maximum number of moons per planet.
 * @param int $freeze Whether the universe is frozen (no fleet movement).
 * @param string $lang Default language of the universe.
 * @param string $battle_engine Battle engine to use.
 * @param int $php_battle Whether the PHP battle engine is used.
 * @param int $battle_max Maximum number of units on one side in battle.
 * @param int $force_lang Whether the language is forced for all players.
 * @param int $start_dm Starting Dark Matter for new players.
 * @param int $max_werf Maximum number of units in a shipyard order.
 * @param int $feedage Maximum age of the news feed in days.
 * @return void
 */
function SetUniParam (int $speed, int $fspeed, int $acs, int $fid, int $did, int $defrepair, int $defrepair_delta, int $galaxies, int $systems, int $rapid, int $moons, int $freeze, string $lang, string $battle_engine, int $php_battle, int $battle_max, int $force_lang, int $start_dm, int $max_werf, int $feedage) : void
{
    global $db_prefix;
    global $GlobalUni;

    // Escape the string parameters: they are interpolated into raw SQL.
    $lang = addslashes ($lang);
    $battle_engine = addslashes ($battle_engine);

    $query = "UPDATE ".$db_prefix."uni SET lang='".$lang."', battle_engine='".$battle_engine."', freeze=$freeze, speed=$speed, fspeed=$fspeed, acs=$acs, fid=$fid, did=$did, defrepair=$defrepair, defrepair_delta=$defrepair_delta, galaxies=$galaxies, systems=$systems, rapid=$rapid, moons=$moons, php_battle=$php_battle, battle_max=$battle_max, force_lang=$force_lang, start_dm=$start_dm, max_werf=$max_werf, feedage=$feedage";
    dbquery ($query);

    $GlobalUni = LoadUniverse ();
}

/**
 * Set external links for the menu items Forum, Discord, Tutorial, Rules and About Us.
 *
 * An empty string hides the corresponding menu item.
 *
 * @param string $ext_board URL of the forum.
 * @param string $ext_discord URL of the Discord server.
 * @param string $ext_tutorial URL of the tutorial page.
 * @param string $ext_rules URL of the rules page.
 * @param string $ext_impressum URL of the about us page.
 * @return void
 */
function SetExtLinks(string $ext_board, string $ext_discord, string $ext_tutorial, string $ext_rules, string $ext_impressum) : void
{
    global $db_prefix;
    global $GlobalUni;

    // Escape the free-form URLs: they are interpolated into raw SQL.
    $ext_board = addslashes ($ext_board);
    $ext_discord = addslashes ($ext_discord);
    $ext_tutorial = addslashes ($ext_tutorial);
    $ext_rules = addslashes ($ext_rules);
    $ext_impressum = addslashes ($ext_impressum);

    $query = "UPDATE ".$db_prefix."uni SET ext_board='".$ext_board."', ext_discord='".$ext_discord."', ext_tutorial='".$ext_tutorial."', ext_rules='".$ext_rules."', ext_impressum='".$ext_impressum."'";
    dbquery ($query);

    $GlobalUni = LoadUniverse ();
}

/**
 * Set the maximum number of registered users; administrators and operators do not count.
 *
 * @param int $maxusers New maximum number of users; ignored when not positive.
 * @return void
 */
function SetMaxUsers (int $maxusers) : void
{
    global $db_prefix;
    global $GlobalUni;

    if ($maxusers > 0) {
        $query = "UPDATE ".$db_prefix."uni SET maxusers=$maxusers";
        dbquery ($query);

        $GlobalUni = LoadUniverse ();
    }
}

/**
 * Reset the game's hack attempt counter; called during relogin.
 *
 * @return void
 */
function ResetHackCounter () : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."uni SET hacks = 0";
    dbquery ($query);
}

/**
 * Increment the game's hack attempt counter.
 *
 * @return void
 */
function IncrementHackCounter () : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."uni SET hacks = hacks + 1";
    dbquery ($query);
}

?>