<?php

// Новый движок LOCA, не использующий базу данных.

// Список языков
$Languages = array ( 
    'ae' => "اللغة العربية", 
    'ar' => "Español", 
    'ba' => "Босански", 
    'bg' => "Български", 
    'cn' => "中文", 
    'cz' => "Český", 
    'de' => "Deutsch", 
    'dk' => "Dansk", 
    'en' => "English", 
    'es' => "Español", 
    'fi' => "Suomi", 
    'fr' => "Français", 
    'gr' => "Ελληνικά", 
    'hr' => "Hrvatski", 
    'hu' => "Magyar", 
    'it' => "Italiano", 
    'jp' => "日本語", 
    'lt' => "Lietuvių", 
    'lv' => "Latviešu", 
    'nl' => "Nederlandse", 
    'no' => "Norsk", 
    'pl' => "Polski", 
    'pt' => "Português", 
    'ro' => "Română", 
    'rs' => "Српски", 
    'ru' => "Русский", 
    'sk' => "Slovenčina", 
    'se' => "Svenska", 
    'tr' => "Türkçe", 
    'tw' => "臺灣話", 
    'ua' => "Українська",
);

$loca_lang = "en";        // Используемый язык. Можно менять в любое время.

$LOCA = array ();        // тут содержаться все ключи.

// Вернуть значение ключа. Возвращается последняя версия.
// Если соединение с LOCA отсутствует или такого ключа не существует, вернуть название ключа.
function loca ($key)
{
    global $LOCA, $loca_lang;
    if ( !isset ( $LOCA[$loca_lang][$key] ) ) return $key;
    else return $LOCA[$loca_lang][$key];
}

// Добавить набор языковых ключей.
function loca_add ( $section, $lang )
{
    global $LOCA;

    if ( $section === "common" ) include "loca/common.php";
    else if ( $section === "fleetorder" ) include "loca/fleetorder.php";
    else if ( $section === "menu" ) include "loca/menu.php";
    else if ( $section === "techlong" ) include "loca/techlong.php";
    else if ( $section === "technames" ) include "loca/technames.php";
    else if ( $section === "techshort" ) include "loca/techshort.php";
    else if ( $section === "techshortnames" ) include "loca/techshortnames.php";

    else if ( $section === "changelog" ) include "loca/changelog.php";
    else if ( $section === "empire" ) include "loca/empire.php";
    else if ( $section === "fleet" ) include "loca/fleet.php";
    else if ( $section === "overview" ) include "loca/overview.php";
}

?>