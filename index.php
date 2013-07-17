<?php
    header('Pragma:no-cache');

    if ( !file_exists ("config.php")) {
        include ("install.php");
        die ();
    }
    
    include ("home.php");
?>