<?php

// LOCA - Localization Engine.
$LocaLang = "en";

$LOCA = array ();

// Return the value of the key. The latest version is returned.
// If no such key exists, return the name of the key.
function loca ($key)
{
    global $LOCA, $LocaLang;
    if ( gettype($LOCA[$LocaLang]) !== "array" ) return "$key";
    if ( key_exists ( $key, $LOCA[$LocaLang]) ) return $LOCA[$LocaLang][$key];
    else return "$key";
}

// Add new version of the key.
function loca_add ($key, $value)
{
    global $LOCA, $LocaLang;
    $LOCA[$LocaLang][$key] = $value;
}

?>