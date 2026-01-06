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
            $modlist[] = $instance;
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

?>