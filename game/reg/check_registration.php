<?php

// Verify registration data via AJAX.

// Check if the configuration file is missing - nothing can be checked without it.
if ( !file_exists ("../config.php"))
{
    die ();
}
else {
    require_once "../config.php";
}

require_once "../core/core.php";

InitDB();

if ( key_exists ( "action", $_REQUEST) )
{
    if ( $_REQUEST['action'] === "check_username" ) {
        $name = (string) ( $_REQUEST['username'] ?? "" );
        if ( mb_strlen ($name) < 3 || mb_strlen ($name) > 20 || preg_match ('/[;,<>()\`\"\']/', $name) ) die ( "1 103" );
        if ( IsUserExist ( $name ) ) die ( "1 101" );
        die ( "1 0" );
    }
    else if ( $_REQUEST['action'] === "check_email" ) {
        $email = (string) ( $_REQUEST['email'] ?? "" );
        if ( !isValidEmail ($email) ) die ( "2 104" );
        if ( IsEmailExist ($email) ) die ( "2 102" );
        die ( "2 0" );
    }
}

?>