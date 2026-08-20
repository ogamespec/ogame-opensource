<?php
/**
 * @file allyapps.php
 * @brief Alliance application handling.
 * @details Accepts, rejects and manages player applications to join an alliance.
 */
// Alliance applications.

// Entries of applications in the database (allyapps).
// app_id: Ordinal number of the application (INT AUTO_INCREMENT PRIMARY KEY)
// ally_id: ID of the alliance to which the application belongs
// player_id: Number of the user who sent the application 
// text: Application text (TEXT)
// date: Application date time() (INT UNSIGNED)

/**
 * Add an application to the alliance and return its ordinal number.
 *
 * @param int $ally_id ID of the alliance to apply to.
 * @param int $player_id ID of the applying player.
 * @param string $text Application text.
 * @return int ID of the newly created application.
 */
function AddApplication (int $ally_id, int $player_id, string $text) : int
{
    $app = array ( 'ally_id' => $ally_id, 'player_id' => $player_id, 'text' => $text, 'date' => time() );
    $id = AddDBRow ( $app, "allyapps" );
    return $id;
}

/**
 * Delete an application.
 *
 * @param int $app_id ID of the application to delete.
 * @return void
 */
function RemoveApplication (int $app_id) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."allyapps WHERE app_id = $app_id";
    dbquery ($query);
}

/**
 * List all applications of an alliance.
 *
 * @param int $ally_id ID of the alliance.
 * @return mixed Database result set with the applications.
 */
function EnumApplications (int $ally_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyapps WHERE ally_id = $ally_id";
    return dbquery ($query);
}

/**
 * Check whether a player has already applied to an alliance.
 *
 * @param int $player_id ID of the player.
 * @return int The application ID if the player has applied, otherwise 0.
 */
function GetUserApplication (int $player_id) : int
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyapps WHERE player_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) > 0 )
    {
        $app = dbarray ($result);
        return $app['app_id'];
    }
    else return 0;
}

/**
 * Load a single application.
 *
 * @param int $app_id ID of the application.
 * @return mixed The application row as an array, or false if it does not exist.
 */
function LoadApplication (int $app_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyapps WHERE app_id = $app_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

?>