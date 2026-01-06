<?php

// Mods support.
// https://github.com/ogamespec/ogame-opensource/blob/master/Wiki/en/mods.md

$modlist = [];

interface GameMod {
    public function install();
    public function uninstall();
    public function init();
    public function route();
}

function ModInitOne($modname)
{
    global $modlist;
    $modPath = "mods/{$modname}/";
    $mainFile = $modPath . "main.php";

    // Include the mod's main file
    if (file_exists($mainFile)) {
        require_once $mainFile;
        
        $className = ucfirst($modname);
        if (class_exists($className)) {
            $instance = new $className();
            $instance->init();
            $modlist[$modname] = $instance;
        }
    }
}

function ModsInit()
{
    global $GlobalUni;
    if (key_exists('modlist', $GlobalUni)) {
        $mods = explode (";", $GlobalUni['modlist']);
        foreach ($mods as $modname) {
            ModInitOne ($modname);
        }
    }
}

function ModsExec($method)
{
    global $modlist;
    foreach ($modlist as $instance) {
        if(method_exists($instance, $method)) {
            $res = $instance->$method();
            if ($res) {
                return true;
            }
        }
    }
    return false;    
}

function ModsList()
{
    global $GlobalUni;
    $res = array ();
    $res['available'] = [];
    $modsDir = 'mods/';
    $folders = scandir($modsDir);
    foreach ($folders as $folder) {
        if ($folder !== '.' && $folder !== '..' && is_dir($modsDir . $folder)) {
            $res['available'][] = $folder;
        }
    }
    $res['installed'] = explode (";", $GlobalUni['modlist']);
    return $res;
}

/**
 * Function for getting information about a mod
 * 
 * @param string $modname Mod folder name
 * @param string $modspath Path to the mods folder
 * @return array|null An array with mod information or null if the mod is not found.
 */
function ModsGetInfo ($modname, $modspath = 'mods/')
{
    $modPath = $modspath . $modname;
    
    // Checking the existence of the mod folder
    if (!is_dir($modPath)) {
        return null;
    }
    
    // Path to the manifest file
    $manifestPath = $modPath . '/manifest.json';
    
    // Checking the existence of the manifest file
    if (!file_exists($manifestPath)) {
        return null;
    }
    
    // Reading the contents of the file
    $manifestContent = file_get_contents($manifestPath);
    
    // Parsing JSON
    $manifestData = json_decode($manifestContent, true);
    
    if ($manifestData === null) {
        // JSON parsing error
        return null;
    }
    
    // Generate an array with mod information
    $modInfo = [
        'name' => $manifestData['name'],
        'version' => $manifestData['version'],
        'author' => $manifestData['author'],
        'description' => $manifestData['description'],
        'website' => $manifestData['website']
    ];
    $modInfo['bg_image'] = $modPath . '/img/bg.png';
    $modInfo['bg_url'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', $modInfo['bg_image']);
    
    return $modInfo;
}

?>