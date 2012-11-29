<?php

// LOCA - Localization Engine.
$LocaLang = "en";

$LOCA = array ();

// Вернуть значение ключа. Возвращается последняя версия.
// Если такого ключа не существует, вернуть название ключа.
function loca ($key)
{
    global $LOCA, $LocaLang;
    if ( key_exists ( $key, $LOCA[$LocaLang]) ) return $LOCA[$LocaLang][$key];
    else return "$LOCA";
}

// Добавить новую версию ключа.
function loca_add ($key, $value)
{
    global $LOCA, $LocaLang;
    $LOCA[$LocaLang][$key] = $value;
}

?>