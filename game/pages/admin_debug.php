<?php

// ========================================================================================
// Отладочные сообщения.

function Admin_Debug ()
{
    global $session;
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."debug ORDER BY date DESC";
    $result = dbquery ($query);

    $rows = dbrows ($result);
    while ($rows--) 
    {
        $msg = dbarray ( $result );
        print_r ($msg);
        echo "<br>";
    }
}

?>