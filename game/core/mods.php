<?php
/**
 * @file mods.php
 * @brief Modification (mod) system.
 * @details Discovers installed modifications, hooks into their entry points and executes mod-defined callbacks at the appropriate places in the game core.
 */
// Mods support.
// https://github.com/ogamespec/ogame-opensource/blob/master/wiki/ru/mods.md

/**
 * List of initialized mod instances, keyed by mod name.
 */
$modlist = [];

/**
 * Abstract base class for game modifications (mods).
 * Defines the mod lifecycle methods and all optional hooks a mod can override.
 */
abstract class GameMod {
    /**
     * Installs the mod, e.g. creates its database tables.
     */
    abstract public function install() : void;

    /**
     * Removes the mod's data and cleans up after it.
     */
    abstract public function uninstall() : void;

    /**
     * Initializes the mod instance when the game starts.
     */
    abstract public function init() : void;

    // Hooks

    /**
     * Hook: lets a mod add routes to the game router.
     *
     * @param array $router Router table, passed by reference.
     * @return bool True if the hook handled the request.
     */
    public function route(array &$router) : bool {
        return false;
    }

    /**
     * Hook: lets a mod add routes to the admin router.
     *
     * @param array $router Admin router table, passed by reference.
     * @return bool True if the hook handled the request.
     */
    public function route_admin(array &$router) : bool {
        return false;
    }

    /**
     * Hook: lets a mod modify the game update queue.
     *
     * @param array $queue Update queue, passed by reference.
     * @return bool True if the hook handled the queue.
     */
    public function update_queue(array &$queue) : bool {
        return false;
    }

    /**
     * Hook: lets a mod add resources to a planet.
     *
     * @param array $json Resource data, passed by reference.
     * @param array $aktplanet The planet data.
     * @return bool True if the hook handled resource addition.
     */
    public function add_resources(array &$json, array $aktplanet) : bool {
        return false;
    }

    /**
     * Hook: lets a mod add bonuses to the given bonus list.
     *
     * @param array $bonuses Bonus list, passed by reference.
     * @return bool True if the hook added bonuses.
     */
    public function add_bonuses(array &$bonuses) : bool {
        return false;
    }

    /**
     * Hook: lets a mod add items to a menu.
     *
     * @param array $json Menu data, passed by reference.
     * @return bool True if the hook added menu items.
     */
    public function add_menuitems(array &$json) : bool {
        return false;
    }

    /**
     * Hook: lets a mod lock additional tables.
     *
     * @param array $tabs List of tables to lock, passed by reference.
     * @return bool True if the hook locked tables.
     */
    public function lock_tables(array &$tabs) : bool {
        return false;
    }

    /**
     * Hook: lets a mod add its tables during database installation.
     *
     * @param array $tabs Table definitions, passed by reference.
     * @return bool True if the hook added tables.
     */
    public function install_tabs_included(array &$tabs) : bool {
        return false;
    }

    /**
     * Hook: supplies a custom small planet image for a planet type.
     *
     * @param int $type The planet type.
     * @param array $img Image data, passed by reference.
     * @return bool True if a custom image was supplied.
     */
    public function get_planet_small_image(int $type, array &$img) : bool {
        return false;
    }

    /**
     * Hook: supplies a custom planet image for a planet type.
     *
     * @param int $type The planet type.
     * @param array $img Image data, passed by reference.
     * @return bool True if a custom image was supplied.
     */
    public function get_planet_image(int $type, array &$img) : bool {
        return false;
    }

    /**
     * Hook: supplies a custom image for an object id.
     *
     * @param int $id The object id.
     * @param array $img Image data, passed by reference.
     * @return bool True if a custom image was supplied.
     */
    public function get_object_image(int $id, array &$img) : bool {
        return false;
    }

    /**
     * Hook: called before page content is rendered.
     *
     * @return bool True if the hook handled content beginning.
     */
    public function begin_content() : bool {
        return false;
    }

    /**
     * Hook: called after page content is rendered.
     *
     * @return bool True if the hook handled content ending.
     */
    public function end_content() : bool {
        return false;
    }

    /**
     * Hook: lets a mod add a row to a database table.
     *
     * @param array $row Row data, passed by reference.
     * @param string $tabname Name of the table.
     * @return bool True if the hook added the row.
     */
    public function add_db_row(array &$row, string $tabname) : bool {
        return false;
    }

    /**
     * Hook: lets a mod allow or forbid building an object.
     *
     * @param array $info Building information, passed by reference.
     * @return bool True if the hook made a decision.
     */
    public function can_build(array &$info) : bool {
        return false;
    }

    /**
     * Hook: lets a mod allow or forbid a research.
     *
     * @param array $info Research information, passed by reference.
     * @return bool True if the hook made a decision.
     */
    public function can_research(array &$info) : bool {
        return false;
    }

    /**
     * Hook: called when a building finishes construction.
     *
     * @param int $planet_id Id of the planet.
     * @param array $queue Build queue, passed by reference.
     * @return bool True if the hook handled the event.
     */
    public function build_end(int $planet_id, array &$queue) : bool {
        return false;
    }

    /**
     * Hook: called when a research finishes.
     *
     * @param array $queue Research queue, passed by reference.
     * @return bool True if the hook handled the event.
     */
    public function research_end(array &$queue) : bool {
        return false;
    }

    /**
     * Hook: lets a mod change the missions available to a fleet.
     *
     * @param array $param Fleet parameters.
     * @param array $missions Mission list, passed by reference.
     * @return bool True if the hook changed the missions.
     */
    public function fleet_available_missions (array $param, array &$missions) : bool {
        return false;
    }

    /**
     * Hook: lets a mod handle fleet actions.
     *
     * @param array $param Fleet parameters.
     * @return bool True if the hook handled the fleet action.
     */
    public function fleet_handler (array $param) : bool {
        return false;
    }

    /**
     * Hook: called after production is processed on a planet.
     *
     * @param array $planet Planet data, passed by reference.
     * @param array $eco Economy data, passed by reference.
     * @return bool True if the hook handled the event.
     */
    public function prod_post_process (array &$planet, array &$eco) : bool {
        return false;
    }

    /**
     * Hook: called after a battle result is processed.
     *
     * @param array $res Battle result, passed by reference.
     * @return bool True if the hook handled the event.
     */
    public function battle_post_process (array &$res) : bool {
        return false;
    }

    /**
     * Hook: lets a mod adjust the battle simulator parameters before the combat runs.
     *
     * Called once per battle from the battle frontend (GenBattleSourceData), right
     * before the unit base stats are serialized into the battle data. The first
     * argument carries the battle context (keys: attackers, defenders); the second
     * argument is the global unit-stat table (armor, shield, attack, cargo, speed,
     * consumption) passed by reference, so a mod can scale it for this battle only.
     * The change affects only the serialized battle data, not the game's global
     * unit parameters (they are restored right after the battle data is built).
     *
     * @param array $args Battle context, passed by value.
     * @param array $unit_param Unit stat table, passed by reference.
     * @return bool True if the hook handled the adjustment.
     */
    public function battle_unit_stats(array $args, array &$unit_param) : bool {
        return false;
    }

    // Default pages hooks (various modifications of the original content)

    /**
     * Hook: adds bonuses on the buildings page.
     *
     * @param int $id Building id.
     * @param array $bonuses Bonus list, passed by reference.
     * @return bool True if the hook added bonuses.
     */
    public function page_buildings_get_bonus(int $id, array &$bonuses) : bool {
        return false;
    }

    /**
     * Hook: adds bonuses on the first fleet page.
     *
     * @param array $param Fleet page parameters.
     * @param array $bonuses Bonus list, passed by reference.
     * @return bool True if the hook added bonuses.
     */
    public function page_flotten1_get_bonus(array $param, array &$bonuses) : bool {
        return false;
    }

    /**
     * Hook: changes the planet types shown on the second fleet page.
     *
     * @param array $planet_types Planet type list, passed by reference.
     * @return bool True if the hook changed the planet types.
     */
    public function page_flotten2_planet_types (array &$planet_types) : bool {
        return false;
    }

    /**
     * Hook: changes the planet types in the spy fleet AJAX handler.
     *
     * @param array $planet_types Planet type list, passed by reference.
     * @return bool True if the hook changed the planet types.
     */
    public function page_flottenversand_ajax_spy_planets (array &$planet_types) : bool {
        return false;
    }

    /**
     * Hook: lets a mod change the planet info shown on the infos page.
     *
     * @param int $id Planet id.
     * @param array $planet Planet data, passed by reference.
     * @return bool True if the hook changed the planet info.
     */
    public function page_infos(int $id, array &$planet) : bool {
        return false;
    }

    /**
     * Hook: adds custom object info on the galaxy page.
     *
     * @param array $planet Planet data.
     * @param array $info Object info, passed by reference.
     * @return bool True if the hook added object info.
     */
    public function page_galaxy_custom_object (array $planet, array &$info) : bool {
        return false;
    }

    /**
     * Hook: adds bonuses on the overview page.
     *
     * @param array $param Overview page parameters.
     * @param array $bonuses Bonus list, passed by reference.
     * @return bool True if the hook added bonuses.
     */
    public function page_overview_get_bonus (array $param, array &$bonuses) : bool {
        return false;
    }

    /**
     * Hook: adds bonuses on the resources page.
     *
     * @param array $param Resources page parameters.
     * @param array $bonuses Bonus list, passed by reference.
     * @return bool True if the hook added bonuses.
     */
    public function page_resources_get_bonus (array $param, array &$bonuses) : bool {
        return false;
    }

    // Hooks for bonuses and changes to the original game mechanics

    /**
     * Hook: changes the bonus of a technology.
     *
     * @param int $id Technology id.
     * @param array $bonus Bonus data, passed by reference.
     * @return bool True if the hook changed the bonus.
     */
    public function bonus_technology (int $id, array &$bonus) : bool {
        return false;
    }

    /**
     * Hook: lets a mod grant extra spy protection to a planet being espionage-targeted.
     *
     * Called from the espionage arrival handler (SpyArrive) for the TARGET planet,
     * right before the report detail level is computed. The first argument carries
     * the target planet (key: planet) and the target user; the second argument is
     * an array with a single 'level' field (start value 0) that a mod may increase
     * to raise the target's effective espionage defense, hiding more of the report.
     *
     * @param array $args Target context, passed by value.
     * @param array $bonus Spy-protection level, passed by reference.
     * @return bool True if the hook granted protection.
     */
    public function spy_protection(array $args, array &$bonus) : bool {
        return false;
    }

    /**
     * Hook: changes a production bonus.
     *
     * @param array $param Production parameters.
     * @param array $bonus Bonus data, passed by reference.
     * @return bool True if the hook changed the bonus.
     */
    public function bonus_prod (array $param, array &$bonus) : bool {
        return false;
    }

    /**
     * Hook: changes a consumption bonus.
     *
     * @param array $param Consumption parameters.
     * @param array $bonus Bonus data, passed by reference.
     * @return bool True if the hook changed the bonus.
     */
    public function bonus_cons (array $param, array &$bonus) : bool {
        return false;
    }

    /**
     * Hook: changes the maximum fleet bonus.
     *
     * @param array $param Fleet parameters.
     * @param array $bonus Bonus data, passed by reference.
     * @return bool True if the hook changed the bonus.
     */
    public function bonus_max_fleet (array $param, array &$bonus) : bool {
        return false;
    }

    /**
     * Hook: changes the fleet consumption bonus.
     *
     * @param array $param Fleet parameters.
     * @param array $bonus Bonus data, passed by reference.
     * @return bool True if the hook changed the bonus.
     */
    public function bonus_fleet_cons (array $param, array &$bonus) : bool {
        return false;
    }

    /**
     * Hook: changes the fleet speed bonus.
     *
     * @param array $param Fleet parameters.
     * @param array $bonus Bonus data, passed by reference.
     * @return bool True if the hook changed the bonus.
     */
    public function bonus_fleet_speed (array $param, array &$bonus) : bool {
        return false;
    }

    /**
     * Hook: lets a mod freeze the planet update (production) of a planet.
     *
     * Called from GetUpdatePlanet() for every regular planet (PTYP_PLANET)
     * right before its production is computed and credited. The planet row is
     * passed by reference; when the hook returns true the engine treats the
     * planet as frozen — it is returned with default (zero) production and no
     * resources are credited, the same way non-planet galaxy objects are
     * handled. Used by game modes that leave the classic empire inactive.
     *
     * @param array $planet The planet row, passed by reference.
     * @return bool True when the planet update is frozen.
     */
    public function skip_planet_update(array &$planet) : bool {
        return false;
    }

    /**
     * Hook: lets a mod veto (take over) a page request.
     *
     * Called from index.php for every routed page request after the session
     * context is prepared and before the page is rendered. The parameter array
     * carries the requested page name (key: page). A mod that takes over the
     * request renders its own output (or redirects) and returns true — the
     * engine stops processing the request.
     *
     * @param array $param Request context (key: page).
     * @return bool True when the mod handled the request itself.
     */
    public function page_veto(array $param) : bool {
        return false;
    }

    /**
     * Hook: lets a mod veto the dispatch of a new fleet.
     *
     * Called from DispatchFleet() for outbound missions (mission < FTYP_RETURN)
     * right before the fleet is written to the database. The parameter array
     * carries the fleet composition (key: fleet), the origin and target planet
     * rows (keys: origin, target), the mission (key: mission) and the dispatch
     * parameters (seconds, resources, cons, when). Return fleets and custom
     * missions are never vetoed. A vetoed dispatch is aborted and returns 0.
     *
     * @param array $param Dispatch context.
     * @return bool True to veto the dispatch.
     */
    public function fleet_dispatch_veto(array $param) : bool {
        return false;
    }
}

/**
 * Loads a single mod by name: includes its main file and initializes its instance.
 *
 * @param string $modname Name of the mod folder.
 */
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
            /** @var GameMod $instance */
            $instance = new $className();
            $instance->init();
            $modlist[$modname] = $instance;
        }
    }
}

/**
 * Initializes all mods listed in the universe's modlist.
 */
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

/**
 * Calls a parameterless hook method on every loaded mod until one returns true.
 *
 * @param string $method Name of the hook method to call.
 * @return bool True if any mod's hook returned true.
 */
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

/**
 * Calls a hook method with one array argument on every loaded mod until one returns true.
 *
 * @param string $method Name of the hook method to call.
 * @param array $arr Argument passed to the hook.
 * @return bool True if any mod's hook returned true.
 */
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

/**
 * Calls a hook method with one array argument passed by reference on every loaded mod until one returns true.
 *
 * @param string $method Name of the hook method to call.
 * @param array $args Argument passed to the hook by reference.
 * @return bool True if any mod's hook returned true.
 */
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

/**
 * Calls a hook method with a reference and a value array argument on every loaded mod until one returns true.
 *
 * @param string $method Name of the hook method to call.
 * @param array $args First argument passed by reference.
 * @param array $arr Second argument passed by value.
 * @return bool True if any mod's hook returned true.
 */
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

/**
 * Calls a hook method with a value and a reference array argument on every loaded mod until one returns true.
 *
 * @param string $method Name of the hook method to call.
 * @param array $args First argument passed by value.
 * @param array $arr Second argument passed by reference.
 * @return bool True if any mod's hook returned true.
 */
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

/**
 * Calls a hook method with two array arguments passed by reference on every loaded mod until one returns true.
 *
 * @param string $method Name of the hook method to call.
 * @param array $args First argument passed by reference.
 * @param array $arr Second argument passed by reference.
 * @return bool True if any mod's hook returned true.
 */
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

/**
 * Calls a hook method with an integer and a reference array argument on every loaded mod until one returns true.
 *
 * @param string $method Name of the hook method to call.
 * @param int $val Integer argument passed to the hook.
 * @param array $arr Array argument passed by reference.
 * @return bool True if any mod's hook returned true.
 */
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

/**
 * Calls a hook method with a reference array and a string argument on every loaded mod until one returns true.
 *
 * @param string $method Name of the hook method to call.
 * @param array $args Array argument passed by reference.
 * @param string $str String argument passed to the hook.
 * @return bool True if any mod's hook returned true.
 */
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

/**
 * Returns the lists of available and installed mods.
 *
 * @return array Array with 'available' and 'installed' mod name lists.
 */
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

/**
 * Loads a single mod by name and calls its install() method.
 *
 * @param string $modname Name of the mod folder.
 */
function ModInstallOne(string $modname) : void
{
    $modPath = "mods/{$modname}/";
    $mainFile = $modPath . "main.php";

    // Include the mod's main file
    if (file_exists($mainFile)) {
        require_once $mainFile;
        
        $className = ucfirst($modname);
        if (class_exists($className)) {
            /** @var GameMod $instance */
            $instance = new $className();
            $instance->install();
        }
    }
}

/**
 * Adds a mod to the universe's modlist and installs it.
 *
 * @param string $modname Name of the mod folder.
 */
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

/**
 * Removes a mod from the universe's modlist and uninstalls it.
 *
 * @param string $modname Name of the mod folder.
 */
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

/**
 * Moves a mod one position up in the universe's modlist order.
 *
 * @param string $modname Name of the mod folder.
 */
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

/**
 * Moves a mod one position down in the universe's modlist order.
 *
 * @param string $modname Name of the mod folder.
 */
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