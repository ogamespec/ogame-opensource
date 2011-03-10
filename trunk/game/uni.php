<?php

// Управление параметрами вселенной.

// Загрузить Вселенную.
function LoadUniverse ()
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."uni;";
    $result = dbquery ($query);
    return dbarray ($result);
}

?>