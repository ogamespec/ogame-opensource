<?php

require_once "db.php";

$UniList = array ();

if ( file_exists ("config.php")) {

    require_once "config.php";

    // Соединиться с базой данных
    dbconnect ($mdb_host, $mdb_user, $mdb_pass, $mdb_name);
    dbquery("SET NAMES 'utf8';");
    dbquery("SET CHARACTER SET 'utf8';");
    dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

    $query = "SELECT * FROM unis ORDER BY num ASC";
    $result = dbquery ($query);
    while ( $row = dbarray ( $result ) ) {
        $UniList[ $row['num'] ] = $row;
    }
}

