<?php

// ========================================================================================
// История переходов (только за игроками, у которых включен флаг sniff).

function Admin_Browse ()
{
    global $session;
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."browse ORDER BY date DESC LIMIT 50";
    $result = dbquery ($query);

    $rows = dbrows ($result);
    while ($rows--) 
    {
        $log = dbarray ( $result );
        print_r ($log);
        echo "<br/>";
    }
}
?>