<?php

// Alliance applications.

// Entries of applications in the database (allyapps).
// app_id: Ordinal number of the application (INT AUTO_INCREMENT PRIMARY KEY)
// ally_id: ID of the alliance to which the application belongs
// player_id: Number of the user who sent the application 
// text: Application text (TEXT)
// date: Application date time() (INT UNSIGNED)

// Add an application to the alliance. Returns the ordinal number of the application.
function AddApplication (int $ally_id, int $player_id, string $text) : int
{
    $app = array ( 'ally_id' => $ally_id, 'player_id' => $player_id, 'text' => $text, 'date' => time() );
    $id = AddDBRow ( $app, "allyapps" );
    return $id;
}

// Delete the application.
function RemoveApplication (int $app_id) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."allyapps WHERE app_id = $app_id";
    dbquery ($query);
}

// List all applications in the alliance.
function EnumApplications (int $ally_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyapps WHERE ally_id = $ally_id";
    return dbquery ($query);
}

// Has the user already applied to the alliance? If yes - return the application ID, otherwise return 0.
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

// Load the application.
function LoadApplication (int $app_id) : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."allyapps WHERE app_id = $app_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

?>