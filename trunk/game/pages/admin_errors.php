<?php

// ========================================================================================
// Ошибки.

function Admin_Errors ()
{
    global $session;
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."errors ORDER BY date DESC";
    $result = dbquery ($query);

    $rows = dbrows ($result);
    while ($rows--) 
    {
        $error = dbarray ( $result );
        print_r ($error);
        echo "<br>";
    }
}

?>