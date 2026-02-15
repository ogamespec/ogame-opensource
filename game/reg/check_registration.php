<?php

// Verify registration data via AJAX.

if ( key_exists ( "action", $_REQUEST) )
{
    if ( $_REQUEST['action'] === "check_username" ) {
        die ( "1 0" );
    }
    else if ( $_REQUEST['action'] === "check_email" ) {
        die ( "2 0" );
    }
}

?>