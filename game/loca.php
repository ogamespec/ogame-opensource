<?php

// Новый движок LOCA, не использующий базу данных.
// Original game load all loca's at every page access (they had 'english.php', 'deutch.php' and so on)
// But actually you don't need it all at same time, only some of them (except very important ones)

// Список языков
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
#    'es' => "Español", 
#    'fi' => "Suomi", 
#    'fr' => "Français", 
#    'gr' => "Ελληνικά", 
#    'hr' => "Hrvatski", 
#    'hu' => "Magyar", 
#    'it' => "Italiano", 
#    'jp' => "日本語", 
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

//
// Глобальный язык устанавливается во время создании сессии пользователя.
//

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
function loca_add ( $section, $lang='en' )
{
    global $LOCA, $Languages;

    // Проверить есть ли язык в списке (для исключения инъекций)
    $found = false;
    foreach ($Languages as $i=>$name ) {
        if ( $i === $lang) { $found = true; break; }
    }
    if ( !$found ) return;

    include_once "loca/".$lang."_".$lang."/".$section.".php";
}

?>