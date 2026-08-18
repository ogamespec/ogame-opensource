<?php

// Database layer.
//
// Two interchangeable backends are available:
//  - db_mysql.php  : MySQL via mysqli (default, used by the live game).
//  - db_sqlite.php : SQLite via PDO, in-memory by default (alternate backend,
//                    used by tests so that the game code runs without a MySQL server).
//
// The backend is selected with the DB_CONNECTION environment variable
// (see phpunit.xml: DB_CONNECTION=sqlite, DB_DATABASE=:memory:).
// Anything that is not "sqlite"/"sqlite3" falls back to MySQL.

/**
 * Returns the active database backend type: "mysql" or "sqlite".
 */
function DB_ConnectionType () : string
{
    $type = strtolower(trim((string)getenv('DB_CONNECTION')));
    if ($type === 'sqlite' || $type === 'sqlite3') return 'sqlite';
    return 'mysql';
}

if ( DB_ConnectionType() === 'sqlite' ) {
    require_once __DIR__ . '/db_sqlite.php';
}
else {
    require_once __DIR__ . '/db_mysql.php';
}

// Drop all game tables and create them empty according to install_tabs.php.
// Works with both backends: the MySQL backend runs the statements as-is,
// the SQLite backend translates them (see db_sqlite.php).
function CreateDBTables () : void
{
    include __DIR__ . "/install_tabs.php";
    if ( function_exists ('ModsExecRef') ) ModsExecRef ('install_tabs_included', $tabs);

    global $db_prefix;

    foreach ( $tabs as $tabname => $tab )
    {
        $opt = " (";
        $first = true;
        foreach ( $tab as $row => $type )
        {
            if ( !$first ) $opt .= ", ";
            if ( $first ) $first = false;
            $opt .= "`".$row."`" . " " . $type;
        }
        $opt .= ")";

        dbquery ('DROP TABLE IF EXISTS '.$db_prefix.$tabname, TRUE);
        dbquery ('CREATE TABLE '.$db_prefix.$tabname.$opt." CHARACTER SET utf8 COLLATE utf8_general_ci", TRUE);
    }
}

?>
