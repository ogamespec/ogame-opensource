<?php

// LOCA - Localization Engine.
$LocaLang = "en";

$LOCA = array ();

// Вернуть значение ключа. Возвращается последняя версия.
// Если такого ключа не существует, вернуть название ключа.
function loca ($key)
{
    global $LOCA, $LocaLang;
    if ( gettype($LOCA[$LocaLang]) !== "array" ) return "$key";
    if ( key_exists ( $key, $LOCA[$LocaLang]) ) return $LOCA[$LocaLang][$key];
    else return "$key";
}

// Добавить новую версию ключа.
function loca_add ($key, $value)
{
    global $LOCA, $LocaLang;
    $LOCA[$LocaLang][$key] = $value;
}

?>