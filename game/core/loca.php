<?php
/**
 * @file loca.php
 * @brief Localization support.
 * @details Lists the available languages and provides the interface used by the localization system.
 */
// A new LOCA engine that does not use a database.
// Original game load all loca's at every page access (they had 'english.php', 'deutch.php' and so on)
// But actually you don't need it all at same time, only some of them (except very important ones)

/**
 * List of supported languages (language code => native name).
 */
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

/**
 * Default language used when no explicit choice is made; English, since the project is already well internationalized.
 */
$DefaultLanguage = "en";

//
// The global language is set during the creation of a user session.
//

/**
 * Language currently used by the localization engine; can be changed at any time.
 */
$loca_lang = $DefaultLanguage;        // Language used. Can be changed at any time.

/**
 * Storage of all loaded localization keys (language => key => value).
 */
$LOCA = array ();        // all the keys are in here.

/**
 * Return the localized value of a key, or the key name if it is not found.
 *
 * If there is no connection to the LOCA or no such key exists, the key name is returned.
 *
 * @param string $key Localization key to look up.
 * @return string The localized string, or the key name if not found.
 */
function loca (string $key) : string
{
    global $LOCA, $loca_lang;
    if ( !isset ( $LOCA[$loca_lang][$key] ) ) return $key;
    else return $LOCA[$loca_lang][$key];
}

/**
 * Return the localized value of a key in the language passed as a parameter.
 *
 * Similar to loca(), but the language is taken from a parameter rather than from the global variable; used when working with several languages at once (e.g. battle reports for players with different languages).
 *
 * @param string $key Localization key to look up.
 * @param string $lang Language code to use.
 * @return string The localized string, or the key name if not found.
 */
function loca_lang (string $key, string $lang) : string
{
    global $LOCA;
    if ( !isset ( $LOCA[$lang][$key] ) ) return $key;
    else return $LOCA[$lang][$key];
}

/**
 * Load a set of language keys for the given section.
 *
 * @param string $section Name of the localization section file to load.
 * @param string $lang Language code to load.
 * @param string $dir Optional directory prefix for the localization files.
 * @return void
 */
function loca_add ( string $section, string $lang='en', string $dir='' ) : void
{
    global $LOCA, $Languages;
    global $from_reg;

    // Check if the language is on the list (to exclude injections)
    $found = false;
    foreach ($Languages as $i=>$name ) {
        if ( $i === $lang) { $found = true; break; }
    }
    if ( !$found ) return;

    if ($from_reg) {
        $dir = "../";
    }

    $path = str_replace('\\', '/', $dir);
    if ($dir !== "" && !str_ends_with($path, '/')) {
        $path .= '/';
    }

    include_once $path."loca/".$lang."_".$lang."/".$section.".php";
}

?>