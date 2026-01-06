<?php

// Mods support.
// https://github.com/ogamespec/ogame-opensource/blob/master/Wiki/en/mods.md

$modlist = [];

interface GameMod {
    public function install();
    public function uninstall();
    public function init();
    public function route();
    public function update_queue($queue);
    public function add_resources(&$json, $aktplanet);
}

function ModInitOne($modname)
{
    global $modlist;
    $modPath = "mods/{$modname}/";
    $mainFile = $modPath . "main.php";

    if (!is_dir($modPath)) {
        return;
    }

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

function ModsExec($method, $args = null)
{
    global $modlist;
    foreach ($modlist as $instance) {
        if(method_exists($instance, $method)) {
            if ($args !== null) {
                $res = $instance->$method(...(array)$args);
            }
            else {
                $res = $instance->$method();
            }
            if ($res) {
                return true;
            }
        }
    }
    return false;    
}

function ModsExecRef($method, &$args)
{
    global $modlist;
    foreach ($modlist as $instance) {
        if(method_exists($instance, $method)) {
            $res = $instance->$method($args);
            if ($res) {
                return true;
            }
        }
    }
    return false;    
}

function ModsExecRefArr($method, &$args, $arr)
{
    global $modlist;
    foreach ($modlist as $instance) {
        if(method_exists($instance, $method)) {
            $res = $instance->$method($args, $arr);
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
    if (key_exists('modlist', $GlobalUni)) {
        $res['installed'] = $GlobalUni['modlist'] !== "" ? explode (";", $GlobalUni['modlist']) : [];
    }
    else {
        $res['installed'] = [];
    }
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
        'website' => $manifestData['website'],
        'folder' => $modname
    ];
    $modInfo['bg_image'] = $modPath . '/img/bg.png';
    
    return $modInfo;
}

function ModInstallOne($modname)
{
    $modPath = "mods/{$modname}/";
    $mainFile = $modPath . "main.php";

    // Include the mod's main file
    if (file_exists($mainFile)) {
        require_once $mainFile;
        
        $className = ucfirst($modname);
        if (class_exists($className)) {
            $instance = new $className();
            $instance->install();
        }
    }
}

function ModsInstall ($modname)
{
    global $GlobalUni;
    global $db_prefix;

    if (key_exists('modlist', $GlobalUni)) {
        $arr = $GlobalUni['modlist'] !== "" ? explode (";", $GlobalUni['modlist']) : [];
        $key = array_search($modname, $arr);
        if ($key === false) {
            $arr[] = $modname;
            $GlobalUni['modlist'] = implode(';', $arr);
            $query = "UPDATE ".$db_prefix."uni SET modlist = '".$GlobalUni['modlist']."'";
            dbquery ($query);
            ModInstallOne($modname);
        }
    }
}

function ModsRemove ($modname)
{
    global $GlobalUni;
    global $db_prefix;
    global $modlist;

    if (key_exists('modlist', $GlobalUni)) {
        $arr = $GlobalUni['modlist'] !== "" ? explode (";", $GlobalUni['modlist']) : [];
        $key = array_search($modname, $arr);
        if ($key !== false) {
            unset($arr[$key]);
            $arr = array_values($arr);
            $GlobalUni['modlist'] = count($arr) ? implode(';', $arr) : "";
            $query = "UPDATE ".$db_prefix."uni SET modlist = '".$GlobalUni['modlist']."'";
            dbquery ($query);
            if (key_exists($modname, $modlist)) {
                $modlist[$modname]->uninstall();
                unset($modlist[$modname]);
            }
        }
    }
}

function ModsMoveUp ($modname)
{
    global $GlobalUni;
    global $db_prefix;

    if (key_exists('modlist', $GlobalUni)) {
        $arr = $GlobalUni['modlist'] !== "" ? explode (";", $GlobalUni['modlist']) : [];
        $key = array_search($modname, $arr);
        if ($key !== false && $key > 0) {

            $temp = $arr[$key - 1];
            $arr[$key - 1] = $arr[$key];
            $arr[$key] = $temp;

            $GlobalUni['modlist'] = implode(';', $arr);
            $query = "UPDATE ".$db_prefix."uni SET modlist = '".$GlobalUni['modlist']."'";
            dbquery ($query);
        }
    }
}

function ModsMoveDown ($modname)
{
    global $GlobalUni;
    global $db_prefix;

    if (key_exists('modlist', $GlobalUni)) {
        $arr = $GlobalUni['modlist'] !== "" ? explode (";", $GlobalUni['modlist']) : [];
        $key = array_search($modname, $arr);
        if ($key !== false && $key < count($arr) - 1) {

            $temp = $arr[$key + 1];
            $arr[$key + 1] = $arr[$key];
            $arr[$key] = $temp;

            $GlobalUni['modlist'] = implode(';', $arr);
            $query = "UPDATE ".$db_prefix."uni SET modlist = '".$GlobalUni['modlist']."'";
            dbquery ($query);
        }
    }
}

?>