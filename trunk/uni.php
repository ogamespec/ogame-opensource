<?php

// Список вселенных.
$UniList = array (

  1 => array ( "uniurl" => "localhost/ogame-opensource", "dbhost" => "localhost", "dbuser" => "toor", "dbpass" => "qwerty", "dbname" => "ogame" ),

);

// Получения параметров вселенной.
function GetUniParam ($num)
{
    $param = $UniList[$num];

    return $param;
}

?>