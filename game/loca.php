<?php

// A new LOCA engine that does not use a database.
// Original game load all loca's at every page access (they had 'english.php', 'deutch.php' and so on)
// But actually you don't need it all at same time, only some of them (except very important ones)

// Supported language list
$Languages = array ( 
#    'ae' => "اللغة العربية", 
#    'ar' => "Español", 
#    'ba' => "Босански", 
#    'bg' => "Български", 
#    'cn' => "中文", 
#    'cz' => "Český", 
    'de' => "Deutsch", 
#    'dk' => "Dansk", 
    'en' => "English", 
    'es' => "Español", 
#    'fi' => "Suomi", 
    'fr' => "Français", 
#    'gr' => "Ελληνικά", 
#    'hr' => "Hrvatski", 
#    'hu' => "Magyar", 
    'it' => "Italiano", 
    'jp' => "日本語", 
#    'lt' => "Lietuvių", 
#    'lv' => "Latviešu", 
#    'nl' => "Nederlandse", 
#    'no' => "Norsk", 
#    'pl' => "Polski", 
#    'pt' => "Português", 
#    'ro' => "Română", 
#    'rs' => "Српски", 
    'ru' => "Русский", 
#    'sk' => "Slovenčina", 
#    'se' => "Svenska", 
#    'tr' => "Türkçe", 
#    'tw' => "臺灣話", 
#    'ua' => "Українська",
);

// Default language if you have to make a choice. Since the project is already quite well internationalized - the default language is English.
$DefaultLanguage = "en";

//
// The global language is set during the creation of a user session.
//

$loca_lang = $DefaultLanguage;        // Language used. Can be changed at any time.

$LOCA = array ();        // all the keys are in here.

// This method can be used to bulk replace the original project name (OGame) with your project name (e.g. SpaceWars).
function loca_subst_ogame ($text)
{
    //return str_replace ("OGame", "SpaceWars", $text);
    return $text;
}

// Return the value of the key. The latest version is returned.
// If there is no connection to the LOCA or no such key exists, return the key name.
function loca ($key)
{
    global $LOCA, $loca_lang;
    if ( !isset ( $LOCA[$loca_lang][$key] ) ) return $key;
    else return loca_subst_ogame ($LOCA[$loca_lang][$key]);
}

// Similar to regular loca(), but the language is selected from a method parameter rather than a global variable.
// It is used when it is necessary to work simultaneously with several languages (e.g. battle reports for players with different languages).
function loca_lang ($key, $lang)
{
    global $LOCA;
    if ( !isset ( $LOCA[$lang][$key] ) ) return $key;
    else return loca_subst_ogame ($LOCA[$lang][$key]);
}

// Add a set of language keys.
function loca_add ( $section, $lang='en' )
{
    global $LOCA, $Languages;

    // Check if the language is on the list (to exclude injections)
    $found = false;
    foreach ($Languages as $i=>$name ) {
        if ( $i === $lang) { $found = true; break; }
    }
    if ( !$found ) return;

    include_once "loca/".$lang."_".$lang."/".$section.".php";
}

?>