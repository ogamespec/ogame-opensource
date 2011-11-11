<?php

// Проверить регистрационные данные по AJAX.

if ( key_exists ( "action", $_GET) )
{
    if ( $_GET['action'] === "check_username" ) die ( "1 0" );
    else if ( $_GET['action'] === "check_email" ) die ( "2 0" );
}

else if ( key_exists ( "action", $_POST) )
{
    if ( $_POST['action'] === "check_username" ) die ( "1 0" );
    else if ( $_POST['action'] === "check_email" ) die ( "2 0" );
}

?>