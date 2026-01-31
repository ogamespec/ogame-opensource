<?php

// Mods support.
// https://github.com/ogamespec/ogame-opensource/blob/master/Wiki/en/mods.md

$modlist = [];

abstract class GameMod {
    abstract public function install() : void;
    abstract public function uninstall() : void;
    abstract public function init() : void;

    // Hooks

    public function route(array &$router) : bool {
        return false;
    }

    public function update_queue(array &$queue) : bool {
        return false;
    }

    public function add_resources(array &$json, array $aktplanet) : bool {
        return false;
    }

    public function add_bonuses(array &$bonuses) : bool {
        return false;
    }

    public function add_menuitems(array &$json) : bool {
        return false;
    }

    public function lock_tables(array &$tabs) : bool {
        return false;
    }

    public function install_tabs_included(array &$tabs) : bool {
        return false;
    }

    public function get_planet_small_image(int $type, array &$img) : bool {
        return false;
    }

    public function get_planet_image(int $type, array &$img) : bool {
        return false;
    }

    public function get_object_image(int $id, array &$img) : bool {
        return false;
    }

    public function begin_content() : bool {
        return false;
    }

    public function end_content() : bool {
        return false;
    }

    public function add_db_row(array &$row, string $tabname) : bool {
        return false;
    }

    public function can_build(array &$info) : bool {
        return false;
    }

    public function can_research(array &$info) : bool {
        return false;
    }

    public function build_end(int $planet_id, array &$queue) : bool {
        return false;
    }

    public function research_end(array &$queue) : bool {
        return false;
    }

    public function fleet_available_missions (array $param, array &$missions) : bool {
        return false;
    }

    public function fleet_handler (array $param) : bool {
        return false;
    }

    // Default pages hooks (various modifications of the original content)

    public function page_buildings_get_bonus(int $id, array &$bonuses) : bool {
        return false;
    }

    public function page_infos(int $id, array &$planet) : bool {
        return false;
    }

    public function page_galaxy_custom_object (array $planet, array &$info) : bool {
        return false;
    }

    // Hooks for bonuses and changes to the original game mechanics

    public function bonus_technology (int $id, array &$bonus) : bool {
        return false;
    }
}

function ModInitOne(string $modname) : void
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

function ModsInit() : void
{
    global $GlobalUni;
    if (key_exists('modlist', $GlobalUni)) {
        $mods = explode (";", $GlobalUni['modlist']);
        foreach ($mods as $modname) {
            ModInitOne ($modname);
        }
    }
}

function ModsExec(string $method) : bool
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

function ModsExecArr(string $method, array $arr) : bool
{
    global $modlist;
    foreach ($modlist as $instance) {
        if(method_exists($instance, $method)) {
            $res = $instance->$method($arr);
            if ($res) {
                return true;
            }
        }
    }
    return false;
}

function ModsExecRef(string $method, array &$args) : bool
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

function ModsExecRefArr(string $method, array &$args, array $arr) : bool
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

function ModsExecArrRef(string $method, array $args, array &$arr) : bool
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

function ModsExecRefRef(string $method, array &$args, array &$arr) : bool
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

function ModsExecIntRef(string $method, int $val, array &$arr) : bool
{
    global $modlist;
    foreach ($modlist as $instance) {
        if(method_exists($instance, $method)) {
            $res = $instance->$method($val, $arr);
            if ($res) {
                return true;
            }
        }
    }
    return false;    
}

function ModsExecRefStr(string $method, array &$args, string $str) : bool
{
    global $modlist;
    foreach ($modlist as $instance) {
        if(method_exists($instance, $method)) {
            $res = $instance->$method($args, $str);
            if ($res) {
                return true;
            }
        }
    }
    return false;    
}

function ModsList() : array
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
function ModsGetInfo (string $modname, string $modspath = 'mods/') : array|null
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
    if (!$manifestContent) {
        return null;
    }
    
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

function ModInstallOne(string $modname) : void
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

function ModsInstall (string $modname) : void
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

function ModsRemove (string $modname) : void
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

function ModsMoveUp (string $modname) : void
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

function ModsMoveDown (string $modname) : void
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