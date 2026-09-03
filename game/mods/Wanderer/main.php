<?php
/**
 * @file main.php
 * @brief Wanderer (Rogue Trader) game modification.
 * @details The player becomes a roaming merchant: he leaves his empire
 * (it is conserved — frozen and protected from attacks) and commands a single
 * wandering station. The station produces resources in a limited way, performs
 * limited research, jumps between sectors (galaxies) of the universe and
 * trades — with the Guild of Traders (NPC exchange, prices depend on the
 * sector) and with other players (exchange orders).
 */

// game/mods/Wanderer/main.php

// --- Galaxy object type of the station (>= PTYP_CUSTOM = 20001) ---
const PTYP_WANDERER_STATION = 21001;

// --- Global periodic event: production tick + order expiry ---
const QTYP_WANDERER_TICK = "WandererTick";
const WANDERER_TICK_PERIOD_SECONDS = 60 * 60;

// --- Starting state of a new station ---
const WANDERER_START_METAL = 200.0;
const WANDERER_START_CRYSTAL = 200.0;
const WANDERER_START_DEUTERIUM = 2500.0;
const WANDERER_START_CORE_LEVEL = 1;

// --- Level caps ---
const WANDERER_MAX_CORE_LEVEL = 10;
const WANDERER_MAX_MODULE_LEVEL = 12;
const WANDERER_MAX_RESEARCH_LEVEL = 8;

// --- Station cargo (storage) ---
const WANDERER_CARGO_BASE = 20000.0;    // cargo hold at module level 0
const WANDERER_CARGO_FACTOR = 1.55;     // cargo growth per cargo-module level

// --- Jump mechanics (deuterium is the fuel) ---
const WANDERER_JUMP_BASE_DEUT = 350.0;          // base fuel of one jump
const WANDERER_JUMP_PER_GALAXY = 2.2;           // +220% fuel per galaxy of distance
const WANDERER_JUMP_COOLDOWN_BASE = 4 * 3600;   // base cooldown after a jump (4 h)
const WANDERER_JUMP_FLIGHT_BASE = 600;          // base "flight" time, seconds
const WANDERER_JUMP_FLIGHT_PER_GALAXY = 900;    // extra flight time per galaxy
const WANDERER_JUMP_COST_ENGINE_STEP = 0.07;    // engine: -7% fuel per level
const WANDERER_JUMP_COST_NAV_STEP = 0.03;       // navigation: -3% fuel per level
const WANDERER_JUMP_COOLDOWN_ENGINE_STEP = 0.08; // engine: -8% cooldown per level
const WANDERER_JUMP_COOLDOWN_NAV_STEP = 0.04;   // navigation: -4% cooldown per level
const WANDERER_JUMP_FLIGHT_ENGINE_STEP = 0.06;  // engine: -6% flight time per level
const WANDERER_JUMP_MIN_MULTIPLIER = 0.15;      // fuel/cooldown never below 15%

// --- Module build / research duration ---
const WANDERER_BUILD_BASE_SECONDS = 300;
const WANDERER_BUILD_GROWTH = 1.55;
const WANDERER_RESEARCH_BASE_SECONDS = 600;
const WANDERER_RESEARCH_GROWTH = 1.4;

// --- Production of the station mines (per level) ---
const WANDERER_PROD_METAL_BASE = 14.0;
const WANDERER_PROD_CRYSTAL_BASE = 10.0;
const WANDERER_PROD_DEUT_BASE = 5.0;
const WANDERER_PROD_GROWTH = 1.09;              // per mine level
const WANDERER_SOLAR_BONUS_STEP = 0.05;         // solar: +5% production per level
const WANDERER_INDUSTRY_BONUS_STEP = 0.04;      // industry research: +4% per level

// --- Guild of Traders (NPC exchange) ---
const WANDERER_MARKET_COMMISSION = 0.05;        // guild commission (per deal)
const WANDERER_TRADE_RESEARCH_STEP = 0.005;     // trade research: -0.5% per level
const WANDERER_MARKET_FACTOR_MIN = 0.78;        // sector price factor range
const WANDERER_MARKET_FACTOR_MAX = 1.22;

// --- Exchange orders (player to player) ---
const WANDERER_ORDER_LIFETIME_SECONDS = 72 * 3600;  // an order lives 3 days
const WANDERER_ORDER_SLOTS_BASE = 1;                // base order slots of a station
const WANDERER_CLASSIC_ORDER_MAX = 3;               // classic empire order slots

/**
 * Wanderer (Rogue Trader) modification.
 *
 * The class name matches the mod folder name (Wanderer), as required by the
 * mod subsystem (ModInitOne instantiates ucfirst($modname)).
 */
class Wanderer extends GameMod {

    // ==================================================================
    //  GameMod lifecycle
    // ==================================================================

    /**
     * Install: create the tables, add users.wanderer_mode, start the tick.
     */
    public function install() : void {
        global $db_prefix;

        // Tables are created before LockTables(): the lock list is built from
        // the tables that exist, and a missing table cannot be locked.
        dbquery ( "CREATE TABLE IF NOT EXISTS ".$db_prefix."wanderer_stations (".self::StationDdl().") CHARACTER SET utf8 COLLATE utf8_general_ci" );
        dbquery ( "CREATE TABLE IF NOT EXISTS ".$db_prefix."wanderer_orders (".self::OrderDdl().") CHARACTER SET utf8 COLLATE utf8_general_ci" );

        LockTables();

        // users.wanderer_mode: 0 = classic empire, 1 = wandering trader mode.
        $probe = dbquery ( "SELECT wanderer_mode FROM ".$db_prefix."users LIMIT 1", true );
        if ( $probe === false ) {
            dbquery ( "ALTER TABLE ".$db_prefix."users ADD COLUMN wanderer_mode INT DEFAULT 0" );
        }

        // Start the global production/order tick (idempotent).
        $result = dbquery ( "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_WANDERER_TICK."'" );
        if ( dbrows ( $result ) == 0 ) {
            AddQueue ( USER_SPACE, QTYP_WANDERER_TICK, 0, 0, 0, time (), WANDERER_TICK_PERIOD_SECONDS );
        }

        UnlockTables();

        Debug ( "Wanderer install success." );
    }

    /**
     * Uninstall: return all wanderers to the empire and drop mod data.
     */
    public function uninstall() : void {
        global $db_prefix;

        LockTables();

        // Return every wanderer to the classic empire first.
        $result = dbquery ( "SELECT player_id FROM ".$db_prefix."users WHERE wanderer_mode = 1" );
        $rows = dbrows ( $result );
        while ( $rows-- ) {
            $user = dbarray ( $result );
            self::ExitWandererMode ( intval ( $user['player_id'] ), time (), true );
        }

        // Remove the mod's own data.
        dbquery ( "DROP TABLE IF EXISTS ".$db_prefix."wanderer_orders" );
        dbquery ( "DROP TABLE IF EXISTS ".$db_prefix."wanderer_stations" );
        dbquery ( "ALTER TABLE ".$db_prefix."users DROP COLUMN wanderer_mode" );

        // Delete the global tick event.
        dbquery ( "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_WANDERER_TICK."'" );

        UnlockTables();

        Debug ( "Wanderer uninstall success." );
    }

    /**
     * Hook: install_tabs_included — schema for freshly created universes and
     * for the admin DB checks.
     */
    public function install_tabs_included (array &$tabs) : bool {
        $tabs['users']['wanderer_mode'] = 'INT DEFAULT 0';
        $tabs['wanderer_stations'] = self::StationSchema();
        $tabs['wanderer_orders'] = self::OrderSchema();
        return false;
    }

    /**
     * Hook: lock_tables — register the mod's tables in LockTables().
     */
    public function lock_tables(array &$tabs) : bool {
        $tabs[] = 'wanderer_stations';
        $tabs[] = 'wanderer_orders';
        return false;
    }

    /**
     * Hook: init — load the mod's localization on every request.
     */
    public function init() : void {
        global $GlobalUser;
        loca_add ( "wanderer", $GlobalUser['lang'], __DIR__ );
    }

    /**
     * Hook: route — the mod's pages.
     */
    public function route(array &$router) : bool {
        global $GlobalUser;

        // Station pages (no empire header: the station is not a planet).
        // Note: the 'loca' router key loads sections from game/loca/<lang>/ —
        // the mod's own texts live in the mod folder and are loaded by init().
        $station_pages = array ( 'wanderer_home', 'wanderer_mods', 'wanderer_lab', 'wanderer_nav' );
        foreach ( $station_pages as $page ) {
            $router[$page] = array (
                'path'   => "mods/Wanderer/pages/".$page.".php",
                'loca'   => array ( 'menu' ),
                'header' => false,
                'mvc'    => true,
            );
        }

        // The exchange works for every player (classic empire included), the
        // mode switch page is the entry/exit point.
        $router['wanderer_market'] = array (
            'path' => "mods/Wanderer/pages/wanderer_market.php",
            'loca' => array ( 'menu' ),
            'mvc'  => true,
        );
        $router['wanderer_switch'] = array (
            'path' => "mods/Wanderer/pages/wanderer_switch.php",
            'loca' => array ( 'menu' ),
            'mvc'  => true,
        );

        // For a wanderer, the classic pages that stay reachable (messages,
        // options, statistics, ...) are framed without the empire header:
        // there is no planet resources bar for a station.
        if ( ( $GlobalUser['wanderer_mode'] ?? 0 ) != 0 ) {
            foreach ( self::FreePages() as $page ) {
                if ( isset ( $router[$page] ) ) {
                    $router[$page]['header'] = false;
                }
            }
            $router['wanderer_market']['header'] = false;
            $router['wanderer_switch']['header'] = false;
        }

        return false;
    }

    /**
     * Hook: update_queue — the global hourly tick.
     */
    public function update_queue(array &$queue) : bool {
        if ( $queue['type'] === QTYP_WANDERER_TICK ) {
            self::OnGlobalTick ( time () );
            ProlongQueue ( intval ( $queue['task_id'] ), WANDERER_TICK_PERIOD_SECONDS );
            return true;
        }
        return false;
    }

    /**
     * Hook: add_menuitems — the left menu.
     */
    public function add_menuitems(array &$json) : bool {
        global $GlobalUser;

        if ( ( $GlobalUser['wanderer_mode'] ?? 0 ) == 0 ) {

            // Classic empire: the trader's exchange and the mode switch.
            array_insert_after_key ( $json, "trader", "wanderer_market",
                array ( 'type' => 'internal', 'page' => 'wanderer_market', 'loca' => 'WANDERER_MENU_MARKET' ) );
            array_insert_after_key ( $json, "options", "wanderer_switch",
                array ( 'type' => 'internal', 'page' => 'wanderer_switch', 'color' => 'FF8900', 'loca' => 'WANDERER_MENU_JOIN' ) );
        }
        else {

            // Wanderer: replace the whole menu with the station menu.
            $json = array (
                'wanderer_home'    => array ( 'type' => 'internal', 'page' => 'wanderer_home', 'loca' => 'WANDERER_MENU_STATION' ),
                'wanderer_mods'    => array ( 'type' => 'internal', 'page' => 'wanderer_mods', 'loca' => 'WANDERER_MENU_MODULES' ),
                'wanderer_lab'     => array ( 'type' => 'internal', 'page' => 'wanderer_lab', 'loca' => 'WANDERER_MENU_LAB' ),
                'wanderer_nav'     => array ( 'type' => 'internal', 'page' => 'wanderer_nav', 'loca' => 'WANDERER_MENU_NAV' ),
                'wanderer_market'  => array ( 'type' => 'internal', 'page' => 'wanderer_market', 'loca' => 'WANDERER_MENU_MARKET' ),
                'messages'         => array ( 'type' => 'internal', 'page' => 'messages', 'param' => '&dsp=1', 'loca' => 'MENU_MESSAGES' ),
                'options'          => array ( 'type' => 'internal', 'page' => 'options', 'loca' => 'MENU_OPTIONS' ),
                'wanderer_switch'  => array ( 'type' => 'internal', 'page' => 'wanderer_switch', 'color' => 'lime', 'loca' => 'WANDERER_MENU_LEAVE' ),
                'logout'           => array ( 'type' => 'internal', 'page' => 'logout', 'loca' => 'MENU_LOGOUT' ),
            );
        }

        return false;
    }

    /**
     * Hook: skip_planet_update — freeze the classic empire of a wanderer.
     */
    public function skip_planet_update(array &$planet) : bool {
        $owner_id = intval ( $planet['owner_id'] ?? 0 );
        if ( $owner_id <= 0 || $owner_id == USER_SPACE ) return false;
        $user = LoadUser ( $owner_id );
        if ( $user === null ) return false;
        return ( intval ( $user['wanderer_mode'] ?? 0 ) != 0 );
    }

    /**
     * Hook: page_veto — block the classic gameplay pages in wanderer mode.
     */
    public function page_veto(array $param) : bool {
        global $GlobalUser;

        if ( ( $GlobalUser['wanderer_mode'] ?? 0 ) == 0 ) return false;
        // Admins keep full access for moderation.
        if ( intval ( $GlobalUser['admin'] ?? 0 ) != 0 ) return false;

        if ( in_array ( $param['page'] ?? '', self::BlockedPages(), true ) ) {
            MyGoto ( 'wanderer_home', '&notice=2' );     // never returns
        }
        return false;
    }

    /**
     * Hook: fleet_dispatch_veto — protect the stations and the conserved
     * empires of wanderers from any fleet mission.
     */
    public function fleet_dispatch_veto(array $param) : bool {
        $target = $param['target'] ?? null;
        if ( !is_array ( $target ) ) return false;

        // The station galaxy object cannot be attacked/transported to.
        if ( intval ( $target['type'] ?? 0 ) == PTYP_WANDERER_STATION ) {
            return true;
        }

        // The conserved empire of a wanderer is untouchable while he roams.
        $owner_id = intval ( $target['owner_id'] ?? 0 );
        if ( $owner_id > 0 && $owner_id != USER_SPACE ) {
            $user = LoadUser ( $owner_id );
            if ( $user !== null && ( intval ( $user['wanderer_mode'] ?? 0 ) != 0 ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Hook: get_planet_small_image — station icon in the galaxy.
     */
    public function get_planet_small_image(int $type, array &$img) : bool {
        if ( $type == PTYP_WANDERER_STATION ) {
            $img['path'] = "mods/Wanderer/img/s1.jpg";
            return true;
        }
        return false;
    }

    /**
     * Hook: get_planet_image — big station picture.
     */
    public function get_planet_image(int $type, array &$img) : bool {
        if ( $type == PTYP_WANDERER_STATION ) {
            $img['path'] = "mods/Wanderer/img/s1.jpg";
            return true;
        }
        return false;
    }

    /**
     * Hook: page_galaxy_custom_object — station info panel in the galaxy.
     */
    public function page_galaxy_custom_object (array $planet, array &$info) : bool {
        global $GlobalUser;

        if ( intval ( $planet['type'] ?? 0 ) != PTYP_WANDERER_STATION ) return false;

        $user = LoadUser ( intval ( $planet['owner_id'] ) );
        $captain = $user !== null ? htmlspecialchars ( $user['oname'] ) : '';
        $sess = htmlspecialchars ( (string)( $GlobalUser['session'] ?? '' ) );

        $info['overlib'] = "<table width=240><tr><td class=c colspan=2>".
            loca ( "WANDERER_GALAXY_STATION" )." ".htmlspecialchars ( (string)$planet['name'] ).
            " [".intval($planet['g']).":".intval($planet['s']).":".intval($planet['p'])."]</td></tr>".
            "<tr><th width=80><img src=mods/Wanderer/img/s1.jpg height=75 width=75></th>".
            "<th align=left><font color=orange>".loca ( "WANDERER_GALAXY_CAPTAIN" )."</font> ".$captain."<br><br>".
            "<a href='index.php?page=writemessages&amp;session=".$sess."&amp;messageziel=".intval($planet['owner_id'])."'>".
            loca ( "WANDERER_GALAXY_MESSAGE" )."</a><br>".
            "<a href='index.php?page=wanderer_market&amp;session=".$sess."'>".
            loca ( "WANDERER_GALAXY_EXCHANGE" )."</a>".
            "</th></tr></table>";

        return true;
    }

    /**
     * Classic pages that stay reachable in wanderer mode.
     */
    public static function FreePages() : array {
        return array (
            'messages', 'options', 'statistics', 'suche', 'changelog', 'buddy',
            'allianzen', 'bewerben', 'bewerbungen', 'writemessages', 'infos',
            'notizen', 'bericht', 'logout', 'admin',
        );
    }

    /**
     * Classic gameplay pages blocked while wandering.
     */
    public static function BlockedPages() : array {
        return array (
            'overview', 'resources', 'buildings', 'b_building', 'flotten1',
            'flotten2', 'flotten3', 'flottenversand', 'flottenversand_ajax',
            'fleet_templates', 'galaxy', 'imperium', 'sprungtor', 'techtree',
            'techtreedetails', 'trader', 'micropayment', 'payment', 'phalanx',
            'renameplanet', 'allianzdepot',
        );
    }

    // ==================================================================
    //  Data model
    // ==================================================================

    /**
     * DDL of wanderer_stations (used by CREATE TABLE IF NOT EXISTS).
     */
    public static function StationDdl() : string {
        $parts = array ();
        foreach ( self::StationSchema() as $name => $type ) {
            $parts[] = "`".$name."` ".$type;
        }
        return implode ( ", ", $parts );
    }

    /**
     * Column schema of wanderer_stations (for install_tabs_included).
     */
    public static function StationSchema() : array {
        $cols = array (
            'user_id'   => 'INT PRIMARY KEY',
            'name'      => 'VARCHAR(20)',
            'planet_id' => 'INT DEFAULT 0',     // galaxy object row (0 = not placed)
            'g'         => 'INT DEFAULT 1',
            's'         => 'INT DEFAULT 1',
            'p'         => 'INT DEFAULT 1',
            'image'     => 'INT DEFAULT 1',     // station picture s1..s6
            'metal'     => 'DOUBLE DEFAULT 0',
            'crystal'   => 'DOUBLE DEFAULT 0',
            'deuterium' => 'DOUBLE DEFAULT 0',
            'lastprod'  => 'INT UNSIGNED DEFAULT 0',
            'core'      => 'INT DEFAULT '.WANDERER_START_CORE_LEVEL,
            'cooldown_until' => 'INT UNSIGNED DEFAULT 0',
            'jumps'     => 'INT DEFAULT 0',
            'deals'     => 'INT DEFAULT 0',
            'started'   => 'INT UNSIGNED DEFAULT 0',
            'build_type'  => 'CHAR(1) DEFAULT \'\'',
            'build_id'    => 'VARCHAR(20) DEFAULT \'\'',   // module/research column name being built
            'build_start' => 'INT UNSIGNED DEFAULT 0',
            'build_until' => 'INT UNSIGNED DEFAULT 0',
        );
        foreach ( self::ModuleColumns() as $col ) {
            $cols[$col] = 'INT DEFAULT 0';
        }
        foreach ( self::ResearchColumns() as $col ) {
            $cols[$col] = 'INT DEFAULT 0';
        }
        return $cols;
    }

    /**
     * Module columns of the station table.
     */
    public static function ModuleColumns() : array {
        return array ( 'mod_mine_m', 'mod_mine_k', 'mod_mine_d', 'mod_solar', 'mod_cargo', 'mod_engine', 'mod_lab', 'mod_hold' );
    }

    /**
     * Research columns of the station table.
     */
    public static function ResearchColumns() : array {
        return array ( 'res_nav', 'res_trade', 'res_scan', 'res_industry' );
    }

    /**
     * DDL of wanderer_orders.
     */
    public static function OrderDdl() : string {
        $parts = array ();
        foreach ( self::OrderSchema() as $name => $type ) {
            $parts[] = "`".$name."` ".$type;
        }
        return implode ( ", ", $parts );
    }

    /**
     * Column schema of wanderer_orders.
     */
    public static function OrderSchema() : array {
        return array (
            'order_id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'owner_id' => 'INT DEFAULT 0',
            'give_rc'  => 'INT DEFAULT 0',
            'give_amt' => 'DOUBLE DEFAULT 0',
            'want_rc'  => 'INT DEFAULT 0',
            'want_amt' => 'DOUBLE DEFAULT 0',
            'date'     => 'INT UNSIGNED DEFAULT 0',
            'until'    => 'INT UNSIGNED DEFAULT 0',
        );
    }

    // ==================================================================
    //  Station meta (modules / researches)
    // ==================================================================

    /**
     * Module meta: costs at level 0, cost growth, minimum core level.
     */
    public static function ModuleMeta(string $col) : ?array {
        $meta = array (
            'mod_mine_m' => array ( 'metal' => 150,  'crystal' => 80,  'deut' => 0,   'growth' => 1.55, 'core' => 1 ),
            'mod_mine_k' => array ( 'metal' => 100,  'crystal' => 120, 'deut' => 0,   'growth' => 1.55, 'core' => 1 ),
            'mod_mine_d' => array ( 'metal' => 80,   'crystal' => 60,  'deut' => 100, 'growth' => 1.55, 'core' => 1 ),
            'mod_solar'  => array ( 'metal' => 120,  'crystal' => 200, 'deut' => 0,   'growth' => 1.6,  'core' => 1 ),
            'mod_cargo'  => array ( 'metal' => 200,  'crystal' => 300, 'deut' => 0,   'growth' => 1.6,  'core' => 1 ),
            'mod_engine' => array ( 'metal' => 400,  'crystal' => 600, 'deut' => 250, 'growth' => 1.7,  'core' => 3 ),
            'mod_lab'    => array ( 'metal' => 300,  'crystal' => 500, 'deut' => 150, 'growth' => 1.65, 'core' => 2 ),
            'mod_hold'   => array ( 'metal' => 250,  'crystal' => 400, 'deut' => 100, 'growth' => 1.6,  'core' => 2 ),
        );
        return $meta[$col] ?? null;
    }

    /**
     * Research meta: costs at level 0, growth.
     */
    public static function ResearchMeta(string $col) : ?array {
        $meta = array (
            'res_nav'      => array ( 'metal' => 800,  'crystal' => 1000, 'deut' => 400, 'growth' => 1.5 ),
            'res_trade'    => array ( 'metal' => 600,  'crystal' => 800,  'deut' => 300, 'growth' => 1.5 ),
            'res_scan'     => array ( 'metal' => 1000, 'crystal' => 1500, 'deut' => 500, 'growth' => 1.5 ),
            'res_industry' => array ( 'metal' => 500,  'crystal' => 700,  'deut' => 300, 'growth' => 1.5 ),
        );
        return $meta[$col] ?? null;
    }

    /**
     * Costs (metal/crystal/deuterium) of the next upgrade of the column.
     * Null when the object is at its maximum level or unknown.
     */
    public static function UpgradeCost(array $st, string $col) : ?array {
        $lvl = intval ( $st[$col] ?? 0 );

        if ( $col === 'core' ) {
            if ( $lvl >= WANDERER_MAX_CORE_LEVEL ) return null;
            return self::CostAtLevel ( 1000, 1500, 500, 1.8, $lvl );
        }
        if ( in_array ( $col, self::ModuleColumns(), true ) ) {
            $meta = self::ModuleMeta ( $col );
            if ( $meta === null ) return null;
            if ( $lvl >= self::ModuleMaxLevel ( intval ( $st['core'] ) ) ) return null;
            return self::CostAtLevel ( $meta['metal'], $meta['crystal'], $meta['deut'], $meta['growth'], $lvl );
        }
        if ( in_array ( $col, self::ResearchColumns(), true ) ) {
            $meta = self::ResearchMeta ( $col );
            if ( $meta === null ) return null;
            if ( $lvl >= self::ResearchMaxLevel ( $st ) ) return null;
            return self::CostAtLevel ( $meta['metal'], $meta['crystal'], $meta['deut'], $meta['growth'], $lvl );
        }
        return null;
    }

    /**
     * Round a cost for the given level.
     */
    private static function CostAtLevel(float $metal, float $crystal, float $deut, float $growth, int $lvl) : array {
        return array (
            'metal'     => (int)ceil ( $metal     * pow ( $growth, $lvl ) ),
            'crystal'   => (int)ceil ( $crystal   * pow ( $growth, $lvl ) ),
            'deuterium' => (int)ceil ( $deut      * pow ( $growth, $lvl ) ),
        );
    }

    /**
     * Maximum module level for the given core level.
     */
    public static function ModuleMaxLevel(int $core) : int {
        return min ( WANDERER_MAX_MODULE_LEVEL, $core + 1 );
    }

    /**
     * Maximum research level: gated by the lab module.
     */
    public static function ResearchMaxLevel(array $st) : int {
        return min ( WANDERER_MAX_RESEARCH_LEVEL, intval ( $st['mod_lab'] ) );
    }

    /**
     * Duration (seconds) of building/upgrading the given column.
     */
    public static function UpgradeSeconds(array $st, string $col) : int {
        $lvl = intval ( $st[$col] ?? 0 );
        if ( $col === 'core' ) {
            return (int)ceil ( 600 * pow ( 1.6, $lvl ) );
        }
        if ( in_array ( $col, self::ModuleColumns(), true ) ) {
            return (int)ceil ( WANDERER_BUILD_BASE_SECONDS * pow ( WANDERER_BUILD_GROWTH, $lvl ) );
        }
        if ( in_array ( $col, self::ResearchColumns(), true ) ) {
            $lab = intval ( $st['mod_lab'] );
            $base = (int)ceil ( WANDERER_RESEARCH_BASE_SECONDS * pow ( WANDERER_RESEARCH_GROWTH, $lvl ) );
            return (int)ceil ( $base / ( 1 + 0.5 * $lab ) );
        }
        return 0;
    }

    // ==================================================================
    //  Production & cargo
    // ==================================================================

    /**
     * Hourly production of the station per resource (metal/crystal/deut).
     */
    public static function StationProduction(array $st) : array {
        $uni = self::Uni();
        $speed = $uni !== null ? max ( 0.1, (float)$uni['speed'] ) : 1.0;

        $mult = ( 1 + WANDERER_SOLAR_BONUS_STEP * intval ( $st['mod_solar'] ) )
              * ( 1 + WANDERER_INDUSTRY_BONUS_STEP * intval ( $st['res_industry'] ) );

        $m = intval ( $st['mod_mine_m'] );
        $k = intval ( $st['mod_mine_k'] );
        $d = intval ( $st['mod_mine_d'] );

        return array (
            GID_RC_METAL     => ( $m > 0 ) ? WANDERER_PROD_METAL_BASE   * $m * pow ( WANDERER_PROD_GROWTH, $m ) * $mult * $speed : 0.0,
            GID_RC_CRYSTAL   => ( $k > 0 ) ? WANDERER_PROD_CRYSTAL_BASE * $k * pow ( WANDERER_PROD_GROWTH, $k ) * $mult * $speed : 0.0,
            GID_RC_DEUTERIUM => ( $d > 0 ) ? WANDERER_PROD_DEUT_BASE    * $d * pow ( WANDERER_PROD_GROWTH, $d ) * $mult * $speed : 0.0,
        );
    }

    /**
     * Cargo capacity of the station.
     */
    public static function StationCargoCap(array $st) : float {
        $lvl = intval ( $st['mod_cargo'] );
        return floor ( WANDERER_CARGO_BASE * pow ( WANDERER_CARGO_FACTOR, $lvl ) );
    }

    /**
     * Station table column that holds the given resource.
     */
    public static function ResourceColumn(int $rc) : string {
        switch ( $rc ) {
            case GID_RC_CRYSTAL:   return 'crystal';
            case GID_RC_DEUTERIUM: return 'deuterium';
        }
        return 'metal';
    }

    /**
     * Resource id for a station resource column.
     */
    public static function ResourceId(string $col) : int {
        switch ( $col ) {
            case 'crystal':   return GID_RC_CRYSTAL;
            case 'deuterium': return GID_RC_DEUTERIUM;
        }
        return GID_RC_METAL;
    }

    // ==================================================================
    //  Tick (build completion + production accrual)
    // ==================================================================

    /**
     * Complete the station's build/research and accrue the production up to
     * $until. Works on the station array; persist with SaveStation().
     */
    public static function TickStation(array &$st, int $until) : void {
        // 1. Complete the finished upgrade.
        if ( (int)$st['build_until'] > 0 && $until >= (int)$st['build_until'] ) {
            $col = (string)$st['build_id'];
            $valid = ( $col === 'core' || in_array ( $col, self::ModuleColumns(), true ) || in_array ( $col, self::ResearchColumns(), true ) );
            if ( $valid ) {
                $st[$col] = intval ( $st[$col] ) + 1;
            }
            $st['build_type'] = '';
            $st['build_id'] = 0;
            $st['build_start'] = 0;
            $st['build_until'] = 0;
        }

        // 2. Production accrual from lastprod to $until.
        $from = (int)$st['lastprod'];
        if ( $from <= 0 ) $from = $until;
        if ( $from < $until ) {
            $prod = self::StationProduction ( $st );
            $hours = ( $until - $from ) / 3600.0;
            $cap = self::StationCargoCap ( $st );
            foreach ( array ( 'metal', 'crystal', 'deuterium' ) as $col ) {
                $st[$col] = min ( $cap, max ( 0.0, (float)$st[$col] ) + $prod[self::ResourceId ( $col )] * $hours );
            }
        }
        $st['lastprod'] = $until;
    }

    /**
     * Load the station row of the user (or null).
     */
    public static function LoadStation(int $user_id) : ?array {
        global $db_prefix;
        $result = dbquery ( "SELECT * FROM ".$db_prefix."wanderer_stations WHERE user_id = ".intval ( $user_id )." LIMIT 1" );
        if ( $result === false || dbrows ( $result ) == 0 ) return null;
        return dbarray ( $result );
    }

    /**
     * Persist the station row.
     */
    public static function SaveStation(array $st) : void {
        global $db_prefix;
        $set = array ();
        foreach ( self::StationSchema() as $col => $type ) {
            if ( $col === 'user_id' ) continue;
            if ( !array_key_exists ( $col, $st ) ) continue;
            $val = $st[$col];
            if ( is_float ( $val ) || is_int ( $val ) ) {
                $set[] = "`".$col."` = ".(float)$val;
            }
            else {
                $set[] = "`".$col."` = '".addslashes ( (string)$val )."'";
            }
        }
        if ( count ( $set ) == 0 ) return;
        dbquery ( "UPDATE ".$db_prefix."wanderer_stations SET ".implode ( ", ", $set ).
                  " WHERE user_id = ".intval ( $st['user_id'] ) );
    }

    // ==================================================================
    //  Mode switch
    // ==================================================================

    /**
     * True when the user row is in wanderer mode.
     */
    public static function IsWanderer(?array $user) : bool {
        if ( $user === null ) return false;
        return ( intval ( $user['wanderer_mode'] ?? 0 ) != 0 );
    }

    /**
     * Enter the wanderer mode. Returns '' on success or an error loca key.
     */
    public static function EnterWandererMode(int $user_id, int $when = 0) : string {
        global $db_prefix;
        if ( $when == 0 ) $when = time ();

        $user = LoadUser ( $user_id );
        if ( $user === null ) return 'WANDERER_ERR_USER';
        if ( self::IsWanderer ( $user ) ) return 'WANDERER_ERR_ALREADY';
        if ( !empty ( $user['banned'] ) ) return 'WANDERER_ERR_BANNED';
        if ( !empty ( $user['vacation'] ) ) return 'WANDERER_ERR_VACATION';

        // The empire must exist (at least one regular planet).
        $result = dbquery ( "SELECT planet_id FROM ".$db_prefix."planets WHERE owner_id = ".$user_id." AND type = ".PTYP_PLANET." LIMIT 1" );
        if ( dbrows ( $result ) == 0 ) return 'WANDERER_ERR_NO_PLANETS';

        // No fleet may fly (own or of other players heading to his planets).
        $result = dbquery ( "SELECT fleet_id FROM ".$db_prefix."fleet WHERE owner_id = ".$user_id." AND mission < ".FTYP_ORBITING." LIMIT 1" );
        if ( dbrows ( $result ) != 0 ) return 'WANDERER_ERR_OWN_FLEETS';

        $result = dbquery ( "SELECT fleet_id FROM ".$db_prefix."fleet WHERE owner_id <> ".$user_id.
            " AND mission < ".FTYP_RETURN." AND target_planet IN ".
            "( SELECT planet_id FROM ".$db_prefix."planets WHERE owner_id = ".$user_id." ) LIMIT 1" );
        if ( dbrows ( $result ) != 0 ) return 'WANDERER_ERR_INCOMING_FLEETS';

        // No pending classic tasks.
        $result = dbquery ( "SELECT task_id FROM ".$db_prefix."queue WHERE owner_id = ".$user_id.
            " AND type IN ( '".QTYP_BUILD."', '".QTYP_DEMOLISH."', '".QTYP_RESEARCH."', '".QTYP_SHIPYARD."' )".
            " AND end > ".$when." AND freeze = 0 LIMIT 1" );
        if ( dbrows ( $result ) != 0 ) return 'WANDERER_ERR_QUEUE';
        $result = dbquery ( "SELECT id FROM ".$db_prefix."buildqueue WHERE owner_id = ".$user_id." LIMIT 1" );
        if ( dbrows ( $result ) != 0 ) return 'WANDERER_ERR_QUEUE';

        // Build or resurrect the station.
        $station = self::LoadStation ( $user_id );
        $created = ( $station === null );
        if ( $created ) {
            $station = array (
                'user_id'   => $user_id,
                'name'      => self::DefaultStationName ( $user ),
                'planet_id' => 0,
                'g'         => 1, 's' => 1, 'p' => 1,
                'image'     => self::Rnd ( 1, 6 ),
                'metal'     => WANDERER_START_METAL,
                'crystal'   => WANDERER_START_CRYSTAL,
                'deuterium' => WANDERER_START_DEUTERIUM,
                'lastprod'  => $when,
                'core'      => WANDERER_START_CORE_LEVEL,
                'cooldown_until' => 0,
                'jumps'     => 0,
                'deals'     => 0,
                'started'   => $when,
                'build_type' => '', 'build_id' => 0, 'build_start' => 0, 'build_until' => 0,
            );
            foreach ( self::ModuleColumns() as $col ) $station[$col] = 0;
            foreach ( self::ResearchColumns() as $col ) $station[$col] = 0;
            AddDBRow ( $station, "wanderer_stations" );
            $station = self::LoadStation ( $user_id );
            if ( $station === null ) return 'WANDERER_ERR_PLACE';
        }

        // Place the beacon (galaxy object). A new journey always starts fresh
        // production-wise (the parked station did not produce while away).
        $error = self::PlaceStation ( $station, $when );
        if ( $error !== '' ) return $error;

        // Freeze the empire and switch the active object to the station.
        dbquery ( "UPDATE ".$db_prefix."users SET wanderer_mode = 1, aktplanet = ".intval ( $station['planet_id'] ).
                  " WHERE player_id = ".$user_id );
        InvalidateUserCache ();

        if ( $created ) {
            self::StationMessage ( $user_id, 'WANDERER_MSG_ENTER_SUBJ', 'WANDERER_MSG_ENTER_TEXT', $when, array ( (string)$station['name'] ) );
        }
        else {
            self::StationMessage ( $user_id, 'WANDERER_MSG_RESUME_SUBJ', 'WANDERER_MSG_RESUME_TEXT', $when, array ( (string)$station['name'] ) );
        }

        return '';
    }

    /**
     * Leave the wanderer mode. Returns '' on success or an error loca key.
     */
    public static function ExitWandererMode(int $user_id, int $when = 0, bool $force = false) : string {
        global $db_prefix;
        if ( $when == 0 ) $when = time ();

        $user = LoadUser ( $user_id );
        if ( $user === null ) return 'WANDERER_ERR_USER';
        if ( !self::IsWanderer ( $user ) ) return 'WANDERER_ERR_NOT_IN_MODE';

        $station = self::LoadStation ( $user_id );
        if ( $station === null ) return 'WANDERER_ERR_NO_STATION';

        // A running upgrade must finish first (unless forced by uninstall).
        if ( !$force && (int)$station['build_until'] > $when ) return 'WANDERER_ERR_BUSY';
        if ( $force ) {
            $station['build_type'] = '';
            $station['build_id'] = 0;
            $station['build_start'] = 0;
            $station['build_until'] = 0;
        }

        // Remove the station galaxy object.
        if ( intval ( $station['planet_id'] ) > 0 ) {
            dbquery ( "DELETE FROM ".$db_prefix."planets WHERE planet_id = ".intval ( $station['planet_id'] ) );
            $station['planet_id'] = 0;
        }

        // Unfreeze the empire and select the home planet.
        $home = intval ( $user['hplanetid'] );
        if ( $home <= 0 ) {
            $result = dbquery ( "SELECT planet_id FROM ".$db_prefix."planets WHERE owner_id = ".$user_id." AND type = ".PTYP_PLANET." ORDER BY planet_id ASC LIMIT 1" );
            $row = dbarray ( $result );
            if ( $row ) $home = intval ( $row['planet_id'] );
        }
        dbquery ( "UPDATE ".$db_prefix."users SET wanderer_mode = 0, aktplanet = ".$home." WHERE player_id = ".$user_id );

        self::SaveStation ( $station );
        InvalidateUserCache ();

        if ( !$force ) {
            self::StationMessage ( $user_id, 'WANDERER_MSG_LEAVE_SUBJ', 'WANDERER_MSG_LEAVE_TEXT', $when, array ( (string)$station['name'] ) );
        }

        return '';
    }

    /**
     * Place the station beacon in the galaxy.
     */
    private static function PlaceStation(array &$station, int $when) : string {
        global $db_prefix;

        $uni = self::Uni();
        if ( $uni === null ) return 'WANDERER_ERR_NO_UNI';
        $galaxies = max ( 1, intval ( $uni['galaxies'] ) );

        // Prefer the saved galaxy; fall back to a random one.
        $g = intval ( $station['g'] );
        $pos = self::FindFreePosition ( $g );
        if ( $pos === null && $galaxies > 1 ) {
            for ( $try = 0; $try < 8; $try++ ) {
                $pos = self::FindFreePosition ( self::Rnd ( 1, $galaxies ) );
                if ( $pos !== null ) break;
            }
        }
        if ( $pos === null ) return 'WANDERER_ERR_UNIVERSE_FULL';

        $station['g'] = $pos['g'];
        $station['s'] = $pos['s'];
        $station['p'] = $pos['p'];
        $station['lastprod'] = $when;   // production starts fresh with the journey

        $name = self::CleanStationName ( (string)( $station['name'] ?? '' ) );
        if ( $name === '' ) $name = 'Station';

        $beacon = array (
            'name' => $name, 'type' => PTYP_WANDERER_STATION,
            'g' => $pos['g'], 's' => $pos['s'], 'p' => $pos['p'],
            'owner_id' => intval ( $station['user_id'] ),
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => $when,
            GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
            'lastpeek' => $when, 'lastakt' => $when, 'gate_until' => 0, 'remove' => 0,
        );
        $id = AddDBRow ( $beacon, "planets" );
        if ( $id == 0 ) return 'WANDERER_ERR_PLACE';
        $station['planet_id'] = $id;

        self::SaveStation ( $station );
        return '';
    }

    /**
     * Find a free position in the given galaxy.
     */
    public static function FindFreePosition(int $galaxy) : ?array {
        $uni = self::Uni();
        if ( $uni === null ) return null;
        $systems = max ( 1, intval ( $uni['systems'] ) );
        $galaxies = max ( 1, intval ( $uni['galaxies'] ) );
        if ( $galaxy < 1 || $galaxy > $galaxies ) $galaxy = self::Rnd ( 1, $galaxies );

        for ( $attempt = 0; $attempt < 60; $attempt++ ) {
            $s = self::Rnd ( 1, $systems );
            $start = self::Rnd ( 1, 15 );
            for ( $k = 0; $k < 15; $k++ ) {
                $p = ( ( $start - 1 + $k ) % 15 ) + 1;
                if ( self::PositionFree ( $galaxy, $s, $p ) ) {
                    return array ( 'g' => $galaxy, 's' => $s, 'p' => $p );
                }
            }
        }
        return null;
    }

    /**
     * True when no galaxy object occupies the position.
     */
    public static function PositionFree(int $g, int $s, int $p) : bool {
        global $db_prefix;
        $result = dbquery ( "SELECT planet_id FROM ".$db_prefix."planets WHERE g = ".$g." AND s = ".$s." AND p = ".$p.
            " AND ( type IN ( ".PTYP_PLANET.", ".PTYP_DF.", ".PTYP_DEST_PLANET.", ".PTYP_ABANDONED." )".
            " OR type >= ".PTYP_CUSTOM." ) LIMIT 1" );
        if ( $result === false ) return false;
        return dbrows ( $result ) == 0;
    }

    // ==================================================================
    //  Station build / research queue
    // ==================================================================

    /**
     * Start building/upgrading a module, the core or a research.
     * Returns '' on success or an error loca key.
     */
    public static function StartUpgrade(int $user_id, string $col, int $when = 0) : string {
        if ( $when == 0 ) $when = time ();

        $user = LoadUser ( $user_id );
        if ( $user === null ) return 'WANDERER_ERR_USER';
        if ( !self::IsWanderer ( $user ) ) return 'WANDERER_ERR_NOT_IN_MODE';

        $station = self::LoadStation ( $user_id );
        if ( $station === null ) return 'WANDERER_ERR_NO_STATION';

        self::TickStation ( $station, $when );

        $is_research = in_array ( $col, self::ResearchColumns(), true );
        $is_module   = ( $col === 'core' ) || in_array ( $col, self::ModuleColumns(), true );
        if ( !$is_research && !$is_module ) return 'WANDERER_ERR_BUILD_ID';
        if ( (int)$station['build_until'] > $when ) return 'WANDERER_ERR_BUILD_BUSY';

        // Level caps & requirements.
        if ( $is_module ) {
            if ( $col !== 'core' ) {
                $meta = self::ModuleMeta ( $col );
                if ( $meta === null ) return 'WANDERER_ERR_BUILD_ID';
                $core_req = intval ( $meta['core'] );
                if ( intval ( $station[$col] ) >= self::ModuleMaxLevel ( intval ( $station['core'] ) ) ) {
                    return 'WANDERER_ERR_MAX_LEVEL';
                }
                if ( intval ( $station['core'] ) < $core_req ) return 'WANDERER_ERR_CORE_REQ';
            }
        }
        else {
            // The lab gates the research first (a missing lab is reported as
            // the requirement, not as the level cap).
            if ( intval ( $station['mod_lab'] ) < 1 ) return 'WANDERER_ERR_LAB_REQ';
            if ( intval ( $station[$col] ) >= self::ResearchMaxLevel ( $station ) ) return 'WANDERER_ERR_MAX_LEVEL';
        }

        $cost = self::UpgradeCost ( $station, $col );
        if ( $cost === null ) return 'WANDERER_ERR_MAX_LEVEL';

        if ( (float)$station['metal']     < $cost['metal'] ||
             (float)$station['crystal']   < $cost['crystal'] ||
             (float)$station['deuterium'] < $cost['deuterium'] ) {
            return 'WANDERER_ERR_RES';
        }

        $station['metal']     = max ( 0.0, (float)$station['metal']     - $cost['metal'] );
        $station['crystal']   = max ( 0.0, (float)$station['crystal']   - $cost['crystal'] );
        $station['deuterium'] = max ( 0.0, (float)$station['deuterium'] - $cost['deuterium'] );

        $station['build_type'] = $is_research ? 'R' : 'M';
        $station['build_id'] = $col;
        $station['build_start'] = $when;
        $station['build_until'] = $when + self::UpgradeSeconds ( $station, $col );

        self::SaveStation ( $station );
        return '';
    }

    /**
     * Rename the station (name is stored in both the station row and the
     * beacon galaxy object).
     */
    public static function RenameStation(int $user_id, string $name) : string {
        global $db_prefix;
        $station = self::LoadStation ( $user_id );
        if ( $station === null ) return 'WANDERER_ERR_NO_STATION';

        $name = self::CleanStationName ( $name );
        if ( $name === '' ) return 'WANDERER_ERR_NAME';

        $station['name'] = $name;
        self::SaveStation ( $station );

        if ( intval ( $station['planet_id'] ) > 0 ) {
            dbquery ( "UPDATE ".$db_prefix."planets SET name = '".addslashes ( $name ).
                      "' WHERE planet_id = ".intval ( $station['planet_id'] ) );
        }
        return '';
    }

    // ==================================================================
    //  Jumps
    // ==================================================================

    /**
     * Fuel cost of a jump between two galaxies.
     */
    public static function JumpCost(array $st, int $from_g, int $to_g) : int {
        $dist = abs ( $from_g - $to_g );
        $mult = max ( WANDERER_JUMP_MIN_MULTIPLIER,
            1 - WANDERER_JUMP_COST_ENGINE_STEP * intval ( $st['mod_engine'] )
              - WANDERER_JUMP_COST_NAV_STEP * intval ( $st['res_nav'] ) );
        return (int)ceil ( WANDERER_JUMP_BASE_DEUT * ( 1 + WANDERER_JUMP_PER_GALAXY * $dist ) * $mult );
    }

    /**
     * Flight time (seconds) of a jump.
     */
    public static function JumpFlightTime(array $st, int $from_g, int $to_g) : int {
        $dist = abs ( $from_g - $to_g );
        $mult = max ( WANDERER_JUMP_MIN_MULTIPLIER,
            1 - WANDERER_JUMP_FLIGHT_ENGINE_STEP * intval ( $st['mod_engine'] ) );
        return (int)ceil ( ( WANDERER_JUMP_FLIGHT_BASE + WANDERER_JUMP_FLIGHT_PER_GALAXY * $dist ) * $mult );
    }

    /**
     * Cooldown (seconds) after a jump.
     */
    public static function JumpCooldown(array $st) : int {
        $mult = max ( WANDERER_JUMP_MIN_MULTIPLIER,
            1 - WANDERER_JUMP_COOLDOWN_ENGINE_STEP * intval ( $st['mod_engine'] )
              - WANDERER_JUMP_COOLDOWN_NAV_STEP * intval ( $st['res_nav'] ) );
        return (int)ceil ( WANDERER_JUMP_COOLDOWN_BASE * $mult );
    }

    /**
     * Perform the jump. Returns '' on success or an error loca key.
     */
    public static function DoJump(int $user_id, int $target_g, int $when = 0) : string {
        if ( $when == 0 ) $when = time ();

        $user = LoadUser ( $user_id );
        if ( $user === null ) return 'WANDERER_ERR_USER';
        if ( !self::IsWanderer ( $user ) ) return 'WANDERER_ERR_NOT_IN_MODE';

        $station = self::LoadStation ( $user_id );
        if ( $station === null ) return 'WANDERER_ERR_NO_STATION';
        if ( intval ( $station['planet_id'] ) <= 0 ) return 'WANDERER_ERR_NOT_PLACED';

        self::TickStation ( $station, $when );

        $uni = self::Uni();
        if ( $uni === null ) return 'WANDERER_ERR_NO_UNI';
        $galaxies = max ( 1, intval ( $uni['galaxies'] ) );
        if ( $target_g < 1 || $target_g > $galaxies ) return 'WANDERER_ERR_SECTOR';

        if ( $when < (int)$station['cooldown_until'] ) return 'WANDERER_ERR_COOLDOWN';

        $from_g = intval ( $station['g'] );
        $cost = self::JumpCost ( $station, $from_g, $target_g );
        if ( (float)$station['deuterium'] < $cost ) return 'WANDERER_ERR_DEUT';

        $pos = self::FindFreePosition ( $target_g );
        if ( $pos === null ) return 'WANDERER_ERR_UNIVERSE_FULL';

        $station['deuterium'] = max ( 0.0, (float)$station['deuterium'] - $cost );
        $station['g'] = $pos['g'];
        $station['s'] = $pos['s'];
        $station['p'] = $pos['p'];
        $station['cooldown_until'] = $when + self::JumpFlightTime ( $station, $from_g, $target_g )
                                          + self::JumpCooldown ( $station );
        $station['jumps'] = intval ( $station['jumps'] ) + 1;

        global $db_prefix;
        dbquery ( "UPDATE ".$db_prefix."planets SET g = ".$pos['g'].", s = ".$pos['s'].", p = ".$pos['p'].
                  " WHERE planet_id = ".intval ( $station['planet_id'] ) );

        self::SaveStation ( $station );
        return '';
    }

    // ==================================================================
    //  Guild of Traders (NPC exchange)
    // ==================================================================

    /**
     * Sector price factor of a resource (galaxy dependent, slowly drifting).
     */
    public static function MarketFactor(int $galaxy, int $rc, int $when = 0) : float {
        if ( $when == 0 ) $when = time ();
        $phase = array ( GID_RC_METAL => 0.6, GID_RC_CRYSTAL => 2.4, GID_RC_DEUTERIUM => 4.2 );
        $week = 604800;
        $f = 1.0
            + 0.16 * sin ( $phase[$rc] + $galaxy * 1.7 )
            + 0.05 * sin ( $galaxy * 0.9 + ( ( $when % $week ) / $week ) * 2.0 * M_PI );
        $f = max ( WANDERER_MARKET_FACTOR_MIN, min ( WANDERER_MARKET_FACTOR_MAX, $f ) );
        return $f;
    }

    /**
     * Market value of one unit of the resource in the sector (guild units).
     */
    public static function MarketValue(int $galaxy, int $rc, int $when = 0) : float {
        $base = array ( GID_RC_METAL => 1.0, GID_RC_CRYSTAL => 2.0, GID_RC_DEUTERIUM => 3.0 );
        return $base[$rc] * self::MarketFactor ( $galaxy, $rc, $when );
    }

    /**
     * Guild commission (0..1) for the station.
     */
    public static function MarketCommission(array $st) : float {
        return max ( 0.0, WANDERER_MARKET_COMMISSION - WANDERER_TRADE_RESEARCH_STEP * intval ( $st['res_trade'] ) );
    }

    /**
     * How many units of $want_rc the guild gives for $give_amt of $give_rc.
     */
    public static function GuildQuote(int $galaxy, int $give_rc, float $give_amt, int $want_rc, array $st, int $when = 0) : float {
        if ( $give_rc == $want_rc || $give_amt <= 0 ) return 0.0;
        $v_give = self::MarketValue ( $galaxy, $give_rc, $when );
        $v_want = self::MarketValue ( $galaxy, $want_rc, $when );
        return floor ( $give_amt * $v_give / $v_want * ( 1 - self::MarketCommission ( $st ) ) );
    }

    /**
     * Exchange resources with the Guild of Traders.
     */
    public static function GuildExchange(int $user_id, int $give_rc, float $give_amt, int $want_rc, int $when = 0) : string {
        if ( $when == 0 ) $when = time ();

        $user = LoadUser ( $user_id );
        if ( $user === null ) return 'WANDERER_ERR_USER';
        if ( !self::IsWanderer ( $user ) ) return 'WANDERER_ERR_NOT_IN_MODE';

        $station = self::LoadStation ( $user_id );
        if ( $station === null ) return 'WANDERER_ERR_NO_STATION';

        self::TickStation ( $station, $when );

        $give_amt = floor ( $give_amt );
        if ( $give_amt <= 0 ) return 'WANDERER_ERR_AMOUNT';
        if ( $give_rc == $want_rc ) return 'WANDERER_ERR_SAME_RES';
        if ( !in_array ( $give_rc, array ( GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM ), true ) ) return 'WANDERER_ERR_AMOUNT';
        if ( !in_array ( $want_rc, array ( GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM ), true ) ) return 'WANDERER_ERR_AMOUNT';

        $give_col = self::ResourceColumn ( $give_rc );
        $want_col = self::ResourceColumn ( $want_rc );
        if ( (float)$station[$give_col] < $give_amt ) return 'WANDERER_ERR_RES';

        $quote = self::GuildQuote ( intval ( $station['g'] ), $give_rc, $give_amt, $want_rc, $station, $when );
        if ( $quote <= 0 ) return 'WANDERER_ERR_AMOUNT';

        $cap = self::StationCargoCap ( $station );
        if ( (float)$station[$want_col] + $quote > $cap ) return 'WANDERER_ERR_CAP';

        $station[$give_col] = max ( 0.0, (float)$station[$give_col] - $give_amt );
        $station[$want_col] = min ( $cap, (float)$station[$want_col] + $quote );
        $station['deals'] = intval ( $station['deals'] ) + 1;

        self::SaveStation ( $station );
        return '';
    }

    // ==================================================================
    //  Exchange orders (player to player)
    // ==================================================================

    /**
     * Place an order. Returns '' on success or an error loca key.
     */
    public static function PlaceOrder(int $user_id, int $give_rc, float $give_amt, int $want_rc, float $want_amt, int $when = 0) : string {
        global $db_prefix;
        if ( $when == 0 ) $when = time ();

        $give_amt = floor ( $give_amt );
        $want_amt = floor ( $want_amt );
        if ( $give_amt < 1 || $want_amt < 1 ) return 'WANDERER_ERR_AMOUNT';
        if ( !in_array ( $give_rc, array ( GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM ), true ) ) return 'WANDERER_ERR_AMOUNT';
        if ( !in_array ( $want_rc, array ( GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM ), true ) ) return 'WANDERER_ERR_AMOUNT';
        if ( $give_rc == $want_rc ) return 'WANDERER_ERR_SAME_RES';

        $user = LoadUser ( $user_id );
        if ( $user === null ) return 'WANDERER_ERR_USER';
        $wanderer = self::IsWanderer ( $user );

        // The offered resources must be present right now (no escrow: the
        // acceptance re-checks the current state of both sides).
        if ( $wanderer ) {
            $station = self::LoadStation ( $user_id );
            if ( $station === null ) return 'WANDERER_ERR_NO_STATION';
            self::TickStation ( $station, $when );
            $col = self::ResourceColumn ( $give_rc );
            if ( (float)$station[$col] < $give_amt ) return 'WANDERER_ERR_RES';
            $max_orders = WANDERER_ORDER_SLOTS_BASE + intval ( $station['mod_hold'] );
        }
        else {
            $planet = self::CurrentPlanet ( $user, $when );
            if ( $planet === null ) return 'WANDERER_ERR_NO_PLANET';
            if ( (float)$planet[$give_rc] < $give_amt ) return 'WANDERER_ERR_RES';
            $max_orders = WANDERER_CLASSIC_ORDER_MAX;
        }

        // Order slot limit.
        $result = dbquery ( "SELECT COUNT(*) AS cnt FROM ".$db_prefix."wanderer_orders WHERE owner_id = ".$user_id." AND until > ".$when );
        $row = dbarray ( $result );
        if ( intval ( $row['cnt'] ?? 0 ) >= $max_orders ) return 'WANDERER_ERR_ORDER_LIMIT';

        $order = array (
            'owner_id' => $user_id,
            'give_rc'  => $give_rc, 'give_amt' => $give_amt,
            'want_rc'  => $want_rc, 'want_amt' => $want_amt,
            'date'     => $when, 'until' => $when + WANDERER_ORDER_LIFETIME_SECONDS,
        );
        AddDBRow ( $order, "wanderer_orders" );
        return '';
    }

    /**
     * Cancel an order of the owner.
     */
    public static function CancelOrder(int $user_id, int $order_id) : string {
        global $db_prefix;
        dbquery ( "DELETE FROM ".$db_prefix."wanderer_orders WHERE order_id = ".$order_id." AND owner_id = ".$user_id );
        return '';
    }

    /**
     * Accept an order. Both sides may be wanderer stations or classic
     * planets. Returns '' on success or an error loca key.
     */
    public static function AcceptOrder(int $acceptor_id, int $order_id, int $when = 0) : string {
        global $db_prefix;
        if ( $when == 0 ) $when = time ();

        $result = dbquery ( "SELECT * FROM ".$db_prefix."wanderer_orders WHERE order_id = ".$order_id." LIMIT 1" );
        if ( dbrows ( $result ) == 0 ) return 'WANDERER_ERR_ORDER_GONE';
        $order = dbarray ( $result );

        $owner_id = intval ( $order['owner_id'] );
        if ( $owner_id == $acceptor_id ) return 'WANDERER_ERR_OWN_ORDER';
        if ( intval ( $order['until'] ) <= $when ) return 'WANDERER_ERR_ORDER_GONE';

        $give_rc = intval ( $order['give_rc'] );
        $give_amt = (float)$order['give_amt'];
        $want_rc = intval ( $order['want_rc'] );
        $want_amt = (float)$order['want_amt'];

        // Side A: the acceptor pays want_amt and receives give_amt.
        $ok_a = self::AccountPay ( $acceptor_id, $want_rc, $want_amt, $when )
             && self::AccountReceive ( $acceptor_id, $give_rc, $give_amt, $when );

        // Side B: the owner pays give_amt and receives want_amt.
        $ok_b = self::AccountPay ( $owner_id, $give_rc, $give_amt, $when )
             && self::AccountReceive ( $owner_id, $want_rc, $want_amt, $when );

        if ( !$ok_a || !$ok_b ) {
            // Roll back whatever succeeded.
            if ( $ok_a ) {
                self::AccountPay ( $acceptor_id, $give_rc, $give_amt, $when );
                self::AccountReceive ( $acceptor_id, $want_rc, $want_amt, $when );
            }
            if ( $ok_b ) {
                self::AccountPay ( $owner_id, $want_rc, $want_amt, $when );
                self::AccountReceive ( $owner_id, $give_rc, $give_amt, $when );
            }
            return 'WANDERER_ERR_DEAL';
        }

        dbquery ( "DELETE FROM ".$db_prefix."wanderer_orders WHERE order_id = ".$order_id );

        $owner = LoadUser ( $owner_id );
        if ( $owner !== null ) {
            self::BumpDeals ( $owner_id );
            self::TradeNotice ( $owner_id, 'WANDERER_MSG_ORDER_SOLD_SUBJ', 'WANDERER_MSG_ORDER_SOLD_TEXT', $when,
                array ( self::ResName ( $give_amt, $give_rc ), self::ResName ( $want_amt, $want_rc ) ) );
        }
        $acceptor = LoadUser ( $acceptor_id );
        if ( $acceptor !== null ) {
            self::BumpDeals ( $acceptor_id );
            self::TradeNotice ( $acceptor_id, 'WANDERER_MSG_ORDER_BOUGHT_SUBJ', 'WANDERER_MSG_ORDER_BOUGHT_TEXT', $when,
                array ( self::ResName ( $give_amt, $give_rc ), self::ResName ( $want_amt, $want_rc ) ) );
        }

        return '';
    }

    /**
     * Count a finished deal on a wanderer station.
     */
    private static function BumpDeals(int $user_id) : void {
        $user = LoadUser ( $user_id );
        if ( $user === null || !self::IsWanderer ( $user ) ) return;
        $st = self::LoadStation ( $user_id );
        if ( $st === null ) return;
        $st['deals'] = intval ( $st['deals'] ) + 1;
        self::SaveStation ( $st );
    }

    /**
     * Take the resource from the player's object (station or current planet).
     */
    private static function AccountPay(int $user_id, int $rc, float $amt, int $when) : bool {
        if ( $amt <= 0 ) return true;
        $user = LoadUser ( $user_id );
        if ( $user === null ) return false;

        if ( self::IsWanderer ( $user ) ) {
            $st = self::LoadStation ( $user_id );
            if ( $st === null ) return false;
            self::TickStation ( $st, $when );
            $col = self::ResourceColumn ( $rc );
            if ( (float)$st[$col] < $amt - 0.001 ) return false;
            $st[$col] = max ( 0.0, (float)$st[$col] - $amt );
            self::SaveStation ( $st );
            return true;
        }

        $planet = self::CurrentPlanet ( $user, $when );
        if ( $planet === null ) return false;
        if ( (float)$planet[$rc] < $amt - 0.001 ) return false;
        global $db_prefix;
        dbquery ( "UPDATE ".$db_prefix."planets SET `".$rc."` = ".max ( 0.0, (float)$planet[$rc] - $amt ).
                  " WHERE planet_id = ".intval ( $planet['planet_id'] ) );
        return true;
    }

    /**
     * Add the resource to the player's object if it fits (station cargo cap
     * or planet storage cap).
     */
    private static function AccountReceive(int $user_id, int $rc, float $amt, int $when) : bool {
        if ( $amt <= 0 ) return true;
        $user = LoadUser ( $user_id );
        if ( $user === null ) return false;

        if ( self::IsWanderer ( $user ) ) {
            $st = self::LoadStation ( $user_id );
            if ( $st === null ) return false;
            self::TickStation ( $st, $when );
            $col = self::ResourceColumn ( $rc );
            $cap = self::StationCargoCap ( $st );
            if ( (float)$st[$col] + $amt > $cap + 0.001 ) return false;
            $st[$col] = min ( $cap, (float)$st[$col] + $amt );
            self::SaveStation ( $st );
            return true;
        }

        $planet = self::CurrentPlanet ( $user, $when );
        if ( $planet === null ) return false;
        $cap = $planet['max'.$rc] ?? PHP_INT_MAX;
        if ( (float)$planet[$rc] + $amt > $cap + 0.001 ) return false;
        global $db_prefix;
        dbquery ( "UPDATE ".$db_prefix."planets SET `".$rc."` = ".min ( $cap, (float)$planet[$rc] + $amt ).
                  " WHERE planet_id = ".intval ( $planet['planet_id'] ) );
        return true;
    }

    /**
     * The freshly updated current planet row of a classic player.
     */
    public static function CurrentPlanet(array $user, int $when = 0) : ?array {
        if ( $when == 0 ) $when = time ();
        $id = intval ( $user['aktplanet'] ?? 0 );
        if ( $id <= 0 ) $id = intval ( $user['hplanetid'] ?? 0 );
        if ( $id <= 0 ) return null;
        $planet = GetUpdatePlanet ( $id, $when );
        if ( $planet === null ) return null;
        if ( intval ( $planet['type'] ?? 0 ) != PTYP_PLANET ) return null;
        return $planet;
    }

    /**
     * Open (unexpired) orders, newest first.
     */
    public static function OpenOrders(int $when = 0) : array {
        global $db_prefix;
        if ( $when == 0 ) $when = time ();
        $result = dbquery ( "SELECT * FROM ".$db_prefix."wanderer_orders WHERE until > ".$when." ORDER BY date DESC LIMIT 50" );
        $out = array ();
        $rows = dbrows ( $result );
        while ( $rows-- ) $out[] = dbarray ( $result );
        return $out;
    }

    /**
     * Delete stale orders.
     */
    public static function ExpireOrders(int $when) : void {
        global $db_prefix;
        dbquery ( "DELETE FROM ".$db_prefix."wanderer_orders WHERE until <= ".$when );
    }

    // ==================================================================
    //  Global tick (queue event)
    // ==================================================================

    /**
     * Hourly global tick: production of every active station, completion of
     * the builds/researches, expiry of the orders.
     */
    public static function OnGlobalTick(int $when) : void {
        global $db_prefix;

        self::ExpireOrders ( $when );

        $result = dbquery ( "SELECT player_id FROM ".$db_prefix."users WHERE wanderer_mode = 1" );
        $rows = dbrows ( $result );
        while ( $rows-- ) {
            $user = dbarray ( $result );
            self::TickUser ( intval ( $user['player_id'] ), $when );
        }
    }

    /**
     * Tick one user's station and persist it.
     */
    public static function TickUser(int $user_id, int $when) : void {
        $user = LoadUser ( $user_id );
        if ( $user === null || !self::IsWanderer ( $user ) ) return;
        $station = self::LoadStation ( $user_id );
        if ( $station === null ) return;
        self::TickStation ( $station, $when );
        self::SaveStation ( $station );
    }

    // ==================================================================
    //  Small helpers
    // ==================================================================

    /**
     * Universe row (the global cache or the database). Null when the database
     * layer is not available (e.g. in pure unit tests).
     */
    public static function Uni() : ?array {
        global $GlobalUni, $db_connect;
        if ( is_array ( $GlobalUni ) && isset ( $GlobalUni['num'] ) ) return $GlobalUni;
        if ( empty ( $db_connect ) ) return null;
        if ( function_exists ( 'LoadUniverse' ) ) {
            $uni = LoadUniverse ();
            if ( is_array ( $uni ) ) return $uni;
        }
        return null;
    }

    /**
     * Random helper (overridden in the tests for reproducibility).
     */
    protected static function Rnd(int $min, int $max) : int {
        return mt_rand ( $min, $max );
    }

    /**
     * Default station name for a player.
     */
    public static function DefaultStationName(?array $user) : string {
        $lang = 'en';
        if ( $user !== null && !empty ( $user['lang'] ) ) $lang = $user['lang'];
        loca_add ( "wanderer", $lang, __DIR__ );
        return self::CleanStationName ( loca_lang ( "WANDERER_DEFAULT_NAME", $lang ) );
    }

    /**
     * Sanitize a station name (max 20 chars, no hostile characters).
     */
    public static function CleanStationName(string $name) : string {
        $name = trim ( (string)$name );
        $name = preg_replace ( '/[;,\`<>\\\\()*"\']/', '', $name ) ?? $name;
        $name = preg_replace ( '/\s\s+/', ' ', $name ) ?? $name;
        $name = mb_substr ( $name, 0, 20, "UTF-8" );
        return trim ( $name );
    }

    /**
     * Send a station notice to the player (in his language).
     */
    private static function StationMessage(int $user_id, string $subj_key, string $text_key, int $when, array $args) : void {
        $user = LoadUser ( $user_id );
        if ( $user === null ) return;
        $lang = $user['lang'] ?? 'en';
        loca_add ( "wanderer", $lang, __DIR__ );
        $text = vsprintf ( loca_lang ( $text_key, $lang ), $args );
        SendMessage ( $user_id, loca_lang ( "WANDERER_FROM", $lang ),
            loca_lang ( $subj_key, $lang ), $text, MTYP_MISC, $when );
    }

    /**
     * Trade notice (localized separately for each side).
     */
    private static function TradeNotice(int $user_id, string $subj_key, string $text_key, int $when, array $args) : void {
        $user = LoadUser ( $user_id );
        if ( $user === null ) return;
        $lang = $user['lang'] ?? 'en';
        loca_add ( "wanderer", $lang, __DIR__ );
        $text = vsprintf ( loca_lang ( $text_key, $lang ), $args );
        SendMessage ( $user_id, loca_lang ( "WANDERER_FROM", $lang ),
            loca_lang ( $subj_key, $lang ), $text, MTYP_MISC, $when );
    }

    /**
     * "amount resource" text for the notices.
     */
    public static function ResName(float $amount, int $rc) : string {
        $lang = self::UniLang();
        $name = loca_lang ( "NAME_".$rc, $lang );
        return nicenum ( floor ( $amount ) )." ".$name;
    }

    /**
     * Remaining-time text (d h m s).
     */
    public static function FormatDuration(int $seconds) : string {
        $seconds = max ( 0, $seconds );
        $d = intdiv ( $seconds, 86400 );
        $h = intdiv ( $seconds % 86400, 3600 );
        $m = intdiv ( $seconds % 3600, 60 );
        $s = $seconds % 60;
        $res = "";
        if ( $d > 0 ) $res .= $d."d ";
        if ( $h > 0 || $d > 0 ) $res .= $h."h ";
        if ( $m > 0 || $h > 0 || $d > 0 ) $res .= $m."m ";
        $res .= $s."s";
        return $res;
    }

    /**
     * Universe language code (for name lookups in messages).
     */
    public static function UniLang() : string {
        global $GlobalUni, $GlobalUser;
        if ( is_array ( $GlobalUser ) && isset ( $GlobalUser['lang'] ) ) return $GlobalUser['lang'];
        if ( is_array ( $GlobalUni ) && isset ( $GlobalUni['lang'] ) ) return $GlobalUni['lang'];
        return 'en';
    }

    // ==================================================================
    //  UI helpers (shared by the mod pages)
    // ==================================================================

    /**
     * "123 456" number formatting.
     */
    public static function UiMoney(float $value) : string {
        return nicenum ( floor ( $value ) );
    }

    /**
     * Localized name of a station object (core / module / research column).
     */
    public static function UiObjectName(string $col) : string {
        if ( $col === 'core' ) return loca ( "WANDERER_MOD_CORE" );
        if ( in_array ( $col, self::ModuleColumns(), true ) ) {
            return loca ( "WANDERER_MOD_".strtoupper ( substr ( $col, 4 ) ) );
        }
        if ( in_array ( $col, self::ResearchColumns(), true ) ) {
            return loca ( "WANDERER_RES_".strtoupper ( substr ( $col, 4 ) ) );
        }
        return $col;
    }

    /**
     * Localized one-line effect description of a station object.
     */
    public static function UiObjectEffect(array $st, string $col) : string {
        $lvl = intval ( $st[$col] ?? 0 );
        switch ( $col ) {
            case 'core':        return loca ( "WANDERER_MOD_CORE_DESC" );
            case 'mod_mine_m':
            case 'mod_mine_k':
            case 'mod_mine_d': {
                $rc = self::ResourceId ( $col );
                $now_prod = self::StationProduction ( $st )[$rc];
                $st2 = $st;
                $st2[$col] = $lvl + 1;
                $next_prod = self::StationProduction ( $st2 )[$rc];
                return va ( loca ( "WANDERER_MOD_MINE_DESC" ), self::UiMoney ( $now_prod ), self::UiMoney ( $next_prod ) );
            }
            case 'mod_solar':   return va ( loca ( "WANDERER_MOD_SOLAR_DESC" ), round ( 100 * $lvl * WANDERER_SOLAR_BONUS_STEP ) );
            case 'mod_cargo':   return va ( loca ( "WANDERER_MOD_CARGO_DESC" ), Wanderer::UiMoney ( self::StationCargoCap ( $st ) ) );
            case 'mod_engine':  return va ( loca ( "WANDERER_MOD_ENGINE_DESC" ), round ( 100 * $lvl * WANDERER_JUMP_COST_ENGINE_STEP ), round ( 100 * $lvl * WANDERER_JUMP_COOLDOWN_ENGINE_STEP ) );
            case 'mod_lab':     return va ( loca ( "WANDERER_MOD_LAB_DESC" ), round ( 100 * $lvl * 0.5 ) );
            case 'mod_hold':    return loca ( "WANDERER_MOD_HOLD_DESC" );
            case 'res_nav':     return va ( loca ( "WANDERER_RES_NAV_DESC" ), round ( 100 * $lvl * WANDERER_JUMP_COST_NAV_STEP ), round ( 100 * $lvl * WANDERER_JUMP_COOLDOWN_NAV_STEP ) );
            case 'res_trade':   return va ( loca ( "WANDERER_RES_TRADE_DESC" ), round ( 100 * $lvl * WANDERER_TRADE_RESEARCH_STEP ) );
            case 'res_scan':    return loca ( "WANDERER_RES_SCAN_DESC" );
            case 'res_industry':return va ( loca ( "WANDERER_RES_INDUSTRY_DESC" ), round ( 100 * $lvl * WANDERER_INDUSTRY_BONUS_STEP ) );
        }
        return '';
    }

    /**
     * Open a standard content table.
     */
    public static function UiBox(string $title) : void {
        echo "<table width='569'><tr><td class='c'>".$title."</td></tr>\n";
    }

    /**
     * Close a standard content table.
     */
    public static function UiBoxEnd() : void {
        echo "</table><br>\n";
    }

    /**
     * The header strip of the station pages: name, picture, coordinates,
     * cargo and the jump cooldown.
     */
    public static function UiStationStrip(array $st) : void {
        global $GlobalUser;
        $sess = $GlobalUser['session'];

        $img = "mods/Wanderer/img/s".max ( 1, min ( 6, intval ( $st['image'] ) ) ).".jpg";

        $location = loca ( "WANDERER_UI_SECTOR" )." ".intval ( $st['g'] ).
                    " &nbsp;[".intval ( $st['g'] ).":".intval ( $st['s'] ).":".intval ( $st['p'] )."]";
        $cooldown = "";
        $now = time ();
        if ( (int)$st['cooldown_until'] > $now ) {
            $cooldown = loca ( "WANDERER_UI_COOLDOWN" )." <font color=orange>".
                        self::FormatDuration ( (int)$st['cooldown_until'] - $now )."</font>";
        }
        else {
            $cooldown = "<font color=lime>".loca ( "WANDERER_UI_READY" )."</font>";
        }

        $cap = self::StationCargoCap ( $st );

        echo "<table width='100%'><tr><td style='background-image:url(mods/Wanderer/img/bg4.jpg); background-size:cover;'>";
        echo "<table width='100%'><tr>";
        echo "<th width='120'><img src='".$img."' width='110' height='110' style='border:1px solid #666;'></th>";
        echo "<th align='left'><font size='4' color='#ffb000'><b>".htmlspecialchars ( (string)$st['name'] )."</b></font><br>";
        echo "<font size='1' color='#ddd'>".loca ( "WANDERER_UI_CAPTAIN" )." ".htmlspecialchars ( $GlobalUser['oname'] ?? '' )."</font><br>";
        echo "<font size='1'>".$location."</font><br>";
        echo "<font size='1'>".$cooldown."</font></th>";
        echo "<th width='330' align='right'><table class='header'>";
        $skin = UserSkin();
        echo "<tr class='header'><td align='center'><img src='".$skin."images/metall.gif' width='42' height='22'></td>";
        echo "<td align='center'><img src='".$skin."images/kristall.gif' width='42' height='22'></td>";
        echo "<td align='center'><img src='".$skin."images/deuterium.gif' width='42' height='22'></td></tr>";
        echo "<tr class='header'><td align='center'><font color='#fff'>".self::UiMoney ( (float)$st['metal'] )."</font></td>";
        echo "<td align='center'><font color='#fff'>".self::UiMoney ( (float)$st['crystal'] )."</font></td>";
        echo "<td align='center'><font color='#fff'>".self::UiMoney ( (float)$st['deuterium'] )."</font></td></tr>";
        echo "<tr><td colspan='3' align='center'><font size='1' color='#aaa'>".loca ( "WANDERER_UI_CARGO" ).
             " ".self::UiMoney ( (float)$st['metal'] + (float)$st['crystal'] + (float)$st['deuterium'] ).
             " / ".self::UiMoney ( $cap )."</font></td></tr>";
        echo "</table></th>";
        echo "</tr></table></td></tr></table><br>\n";
    }

    /**
     * Echo the resource icon + name pair (used in tables).
     */
    public static function UiResourceName(int $rc) : string {
        return loca ( "NAME_".$rc );
    }

    /**
     * Small progress bar HTML for the given fill fraction (0..1).
     */
    public static function UiProgress(float $fill, string $color = 'lime') : string {
        $fill = max ( 0.0, min ( 1.0, $fill ) );
        $w = (int)round ( 100 * $fill );
        return "<table width='130' cellpadding='0' cellspacing='0'><tr><td><table width='130'><tr><td style='background:#111;'>".
               "<div style='width:".$w."px;background:".$color.";height:10px;'></div></td></tr></table></td></tr></table>";
    }
}

?>
