<?php

// Load game constants (GID_R_*, GID_B_*, GID_F_*, GID_D_*, GID_RC_*, ...).
// The bootstrap (testing/bootstrap.php) already loads these at the top level;
// the require_once calls below are for standalone use of this class.
require_once __DIR__ . '/../game/core/defs.php';
require_once __DIR__ . '/../game/core/techs.php';

/**
 * FixtureBuilder creates a test universe with 3 players in the in-memory
 * database engine (game/core/db.php with the SQLite backend, DB_CONNECTION=sqlite,
 * DB_DATABASE=:memory:, see phpunit.xml).
 *
 * The real game schema is created by CreateDBTables() (from install_tabs.php),
 * so the fixture data is compatible with the real game pages. All inserts go
 * through AddDBRow(), the real DB function of the in-memory engine.
 *
 * Used for Golden Pages snapshot testing.
 */
class FixtureBuilder
{
    private $dbPrefix = 'test_';
    private $players = [];
    private $uniData = [];

    public function __construct()
    {
        // Make sure the in-memory engine is selected even when this class is
        // used outside the PHPUnit bootstrap (e.g. in a standalone script).
        if (strtolower((string)getenv('DB_CONNECTION')) !== 'sqlite') {
            putenv('DB_CONNECTION=sqlite');
            putenv('DB_DATABASE=:memory:');
            $_ENV['DB_CONNECTION'] = 'sqlite';
            $_ENV['DB_DATABASE'] = ':memory:';
        }

        // The DB layer must be loaded before InitDB()/CreateDBTables().
        if (!function_exists('dbquery')) {
            require_once __DIR__ . '/../game/core/db.php';
        }

        global $db_prefix, $db_name, $db_host, $db_user, $db_pass;
        global $UserCache, $LOCA, $loca_lang, $Languages, $DefaultLanguage, $StartPage, $from_cron;

        $db_prefix = $this->dbPrefix;
        $db_name = 'test';
        $db_host = '';
        $db_user = '';
        $db_pass = '';
        $StartPage = 'index.php';
        $from_cron = false;
        $UserCache = array ();
        // NOTE: $LOCA must NOT be reset here. The loca files are loaded with
        // include_once (loca_add in game/core/loca.php), so once a section is
        // loaded it cannot be loaded again in the same process. Resetting
        // $LOCA here would wipe the translations for every test after the
        // first one. The bootstrap (testing/bootstrap.php) already initializes
        // $LOCA/$Languages/$DefaultLanguage via game/core/loca.php.
        if (!isset($Languages)) $Languages = array ('en' => 'English');
        if (!isset($DefaultLanguage)) $DefaultLanguage = 'en';
        if (!isset($loca_lang)) $loca_lang = 'en';

        // Connect to the in-memory DB and create the real game schema.
        InitDB();
        CreateDBTables();
    }

    public function getPDO(): PDO
    {
        global $db_connect;
        return $db_connect;
    }

    public function getDbPrefix(): string
    {
        return $this->dbPrefix;
    }

    public function setDbPrefix(string $prefix): void
    {
        $this->dbPrefix = $prefix;
    }

    /**
     * Create the test universe with 3 players.
     *
     * @param string $lang Language code (e.g. 'en', 'de', 'ru') for the
     *                     universe and for every player. Golden Pages tests
     *                     run once per supported language (issue #268); the
     *                     game pages read the language from the uni row
     *                     ($GlobalUni['lang']) and from the users table
     *                     ($GlobalUser['lang']), so both must match.
     */
    public function createTestUniverse(string $lang = 'en'): self
    {
        global $db_prefix;
        $now = time();

        // Universe settings (must match testUniverseSettingsAreConfigured).
        AddDBRow (array (
            'num' => 1, 'speed' => 1.0, 'fspeed' => 1.0, 'galaxies' => 1, 'systems' => 15,
            'maxusers' => 1000, 'acs' => 1, 'rapid' => 0, 'moons' => 1, 'defrepair' => 0,
            'defrepair_delta' => 0, 'usercount' => 3, 'freeze' => 0, 'startdate' => $now - 86400,
            'battle_engine' => 'php', 'lang' => $lang, 'hacks' => 0, 'php_battle' => 1,
            'force_lang' => 0, 'start_dm' => 0, 'max_werf' => 1000, 'feedage' => 0,
            // News (ComBox on the Overview page) -- fixed text, active for a day.
            'news1' => 'Welcome to the test universe!', 'news2' => 'Golden pages test server.',
            'news_until' => $now + 86400, 'modlist' => '',
            'ext_board' => '', 'ext_discord' => '', 'ext_tutorial' => '', 'ext_rules' => '', 'ext_impressum' => '',
        ), 'uni');

        // Colonization settings.
        AddDBRow (array (
            't1_a' => 1100, 't1_b' => 5000, 't1_c' => 1000,
            't2_a' => 1100, 't2_b' => 5000, 't2_c' => 1000,
            't3_a' => 1100, 't3_b' => 5000, 't3_c' => 1000,
            't4_a' => 1100, 't4_b' => 5000, 't4_c' => 1000,
            't5_a' => 1100, 't5_b' => 5000, 't5_c' => 1000,
        ), 'coltab');

        // Research levels for the three players (real users table columns).
        $research = array (
            GID_R_ESPIONAGE => 5, GID_R_COMPUTER => 6, GID_R_WEAPON => 3, GID_R_SHIELD => 3,
            GID_R_ARMOUR => 4, GID_R_ENERGY => 5, GID_R_HYPERSPACE => 2, GID_R_COMBUST_DRIVE => 4,
            GID_R_IMPULSE_DRIVE => 3, GID_R_HYPER_DRIVE => 2, GID_R_LASER_TECH => 4, GID_R_ION_TECH => 2,
            GID_R_PLASMA_TECH => 1, GID_R_IGN => 0, GID_R_EXPEDITION => 2, GID_R_GRAVITON => 0,
        );

        $players = array (
            1 => array ('name' => 'PlayerOne',   'oname' => 'PlayerOne',   'score1' => 50000, 'score2' => 30000, 'score3' => 20000, 'place1' => 1, 'place2' => 5,  'place3' => 10),
            2 => array ('name' => 'PlayerTwo',   'oname' => 'PlayerTwo',   'score1' => 45000, 'score2' => 28000, 'score3' => 18000, 'place1' => 2, 'place2' => 8,  'place3' => 15),
            3 => array ('name' => 'PlayerThree', 'oname' => 'PlayerThree', 'score1' => 40000, 'score2' => 25000, 'score3' => 15000, 'place1' => 3, 'place2' => 12, 'place3' => 20),
        );

        foreach ($players as $playerId => $pData) {
            $session = str_pad(dechex($playerId + 1000), 12, '0', STR_PAD_LEFT);
            $row = array (
                'player_id' => $playerId,
                'regdate' => $now - 30 * 86400,
                'session' => $session,
                'private_session' => '',
                'name' => $pData['name'],
                'oname' => $pData['oname'],
                'email' => 'player' . $playerId . '@test.com',
                'lang' => $lang,
                'admin' => 0,
                'validated' => 1,
                'lastlogin' => $now - 3600,
                'lastclick' => $now - 60,
                'ip_addr' => '127.0.0.1',
                'score1' => $pData['score1'],
                'score2' => $pData['score2'],
                'score3' => $pData['score3'],
                'place1' => $pData['place1'],
                'place2' => $pData['place2'],
                'place3' => $pData['place3'],
                'dm' => 1000,
                'dmfree' => 500,
                // Trade rates (trader page reads them; schema has no defaults).
                'trader' => 0, 'rate_m' => 1.0, 'rate_k' => 1.0, 'rate_d' => 1.0,
                // Use the default skin so the buildings pages render the
                // building images like the original game (issue #269).
                'skin' => 'http://localhost/evolution/', 'useskin' => 1,
                // Galaxy action icons + Commander message folders + partial spy reports.
                'flags' => USER_FLAG_DEFAULT | USER_FLAG_FOLDER_ESPIONAGE | USER_FLAG_FOLDER_COMBAT
                    | USER_FLAG_FOLDER_EXPEDITION | USER_FLAG_FOLDER_ALLIANCE | USER_FLAG_FOLDER_PLAYER | USER_FLAG_FOLDER_OTHER,
                // Espionage probes sent with a single click (galaxy "spy" icon).
                'maxspy' => 5, 'maxfleetmsg' => 10,
                // Officer expiry timestamps: active (far future), so pages
                // like fleet_templates render instead of redirecting away.
                'com_until' => $now + 365 * 24 * 60 * 60,
                'adm_until' => $now + 365 * 24 * 60 * 60,
                'eng_until' => $now + 365 * 24 * 60 * 60,
                'geo_until' => $now + 365 * 24 * 60 * 60,
                'tec_until' => $now + 365 * 24 * 60 * 60,
                // Research levels: player 1 is the most advanced, player 3 the least.
                GID_R_ESPIONAGE => max(0, $research[GID_R_ESPIONAGE] - ($playerId - 1)),
                GID_R_COMPUTER => max(0, $research[GID_R_COMPUTER] - ($playerId - 1)),
                GID_R_WEAPON => max(0, $research[GID_R_WEAPON] - ($playerId - 1)),
                GID_R_SHIELD => max(0, $research[GID_R_SHIELD] - ($playerId - 1)),
                GID_R_ARMOUR => max(0, $research[GID_R_ARMOUR] - ($playerId - 1)),
                GID_R_ENERGY => max(0, $research[GID_R_ENERGY] - ($playerId - 1)),
                GID_R_HYPERSPACE => max(0, $research[GID_R_HYPERSPACE] - ($playerId - 1)),
                GID_R_COMBUST_DRIVE => max(0, $research[GID_R_COMBUST_DRIVE] - ($playerId - 1)),
                GID_R_IMPULSE_DRIVE => max(0, $research[GID_R_IMPULSE_DRIVE] - ($playerId - 1)),
                GID_R_HYPER_DRIVE => max(0, $research[GID_R_HYPER_DRIVE] - ($playerId - 1)),
                GID_R_LASER_TECH => max(0, $research[GID_R_LASER_TECH] - ($playerId - 1)),
                GID_R_ION_TECH => max(0, $research[GID_R_ION_TECH] - ($playerId - 1)),
                GID_R_PLASMA_TECH => max(0, $research[GID_R_PLASMA_TECH] - ($playerId - 1)),
                GID_R_IGN => 0,
                GID_R_EXPEDITION => max(0, $research[GID_R_EXPEDITION] - ($playerId - 1)),
                GID_R_GRAVITON => 0,
            );
            AddDBRow ($row, 'users');

            $this->players[$playerId] = array (
                'id' => $playerId,
                'name' => $pData['name'],
                'oname' => $pData['oname'],
                'session' => $session,
                'planet_id' => 0,
            );
        }

        // Planets: 3 per player (home + 2 colonies).
        $planetConfigs = array (
            // Player 1
            array ('owner_id' => 1, 'name' => 'Home',     'type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 4, 'diameter' => 12200, 'temp' => 65, 'fields' => 14, 'maxfields' => 14, GID_B_METAL_MINE => 5, GID_B_CRYS_MINE => 3, GID_B_DEUT_SYNTH => 2, GID_B_SOLAR => 3, GID_B_SHIPYARD => 3, GID_B_RES_LAB => 4, GID_B_METAL_STOR => 5, GID_B_CRYS_STOR => 5, GID_B_DEUT_STOR => 4, GID_B_MISS_SILO => 3, GID_B_ALLY_DEPOT => 1, GID_B_ROBOTS => 2, GID_B_FUSION => 1, GID_RC_METAL => 25000, GID_RC_CRYSTAL => 15000, GID_RC_DEUTERIUM => 8000, 'lastpeek' => $now - 600,
                // Fleet on the home planet (flotten1 ship list / shipyard tab / fleet dispatch).
                GID_F_SC => 10, GID_F_LC => 5, GID_F_LF => 20, GID_F_HF => 3, GID_F_CRUISER => 2, GID_F_BATTLESHIP => 1,
                GID_F_COLON => 1, GID_F_RECYCLER => 2, GID_F_PROBE => 5, GID_F_BOMBER => 1, GID_F_SAT => 8,
                GID_F_DESTRO => 1, GID_F_DEATHSTAR => 1, GID_F_BATTLECRUISER => 1,
                // Defense (Verteidigung tab / missile silo / galaxy IPM button).
                GID_D_RL => 5, GID_D_LL => 3, GID_D_HL => 2, GID_D_GAUSS => 1, GID_D_ION => 1, GID_D_SDOME => 1, GID_D_ABM => 2, GID_D_IPM => 1),
            array ('owner_id' => 1, 'name' => 'Colony A', 'type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 5, 'diameter' => 11000, 'temp' => 60, 'fields' => 12, 'maxfields' => 12, GID_B_METAL_MINE => 3, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_B_SHIPYARD => 2, GID_B_METAL_STOR => 3, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 2, GID_B_MISS_SILO => 2, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 1, GID_B_FUSION => 0, GID_RC_METAL => 8000, GID_RC_CRYSTAL => 5000, GID_RC_DEUTERIUM => 2000, 'lastpeek' => $now - 600),
            array ('owner_id' => 1, 'name' => 'Colony B', 'type' => PTYP_PLANET, 'g' => 1, 's' => 2, 'p' => 4, 'diameter' => 10500, 'temp' => 55, 'fields' => 10, 'maxfields' => 10, GID_B_METAL_MINE => 2, GID_B_CRYS_MINE => 1, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 1, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 2, GID_B_CRYS_STOR => 2, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 0, GID_B_FUSION => 0, GID_RC_METAL => 5000, GID_RC_CRYSTAL => 3000, GID_RC_DEUTERIUM => 1000, 'lastpeek' => $now - 600),
            // Player 2
            array ('owner_id' => 2, 'name' => 'Home',     'type' => PTYP_PLANET, 'g' => 1, 's' => 3, 'p' => 4, 'diameter' => 11800, 'temp' => 62, 'fields' => 13, 'maxfields' => 13, GID_B_METAL_MINE => 4, GID_B_CRYS_MINE => 3, GID_B_DEUT_SYNTH => 2, GID_B_SOLAR => 2, GID_B_SHIPYARD => 2, GID_B_METAL_STOR => 4, GID_B_CRYS_STOR => 4, GID_B_DEUT_STOR => 3, GID_B_MISS_SILO => 2, GID_B_ALLY_DEPOT => 1, GID_B_ROBOTS => 1, GID_B_FUSION => 0, GID_RC_METAL => 20000, GID_RC_CRYSTAL => 12000, GID_RC_DEUTERIUM => 6000, 'lastpeek' => $now - 600,
                GID_F_SC => 5, GID_F_LF => 8, GID_F_PROBE => 3, GID_D_RL => 3, GID_D_LL => 2),
            array ('owner_id' => 2, 'name' => 'Colony X', 'type' => PTYP_PLANET, 'g' => 1, 's' => 3, 'p' => 5, 'diameter' => 10800, 'temp' => 58, 'fields' => 11, 'maxfields' => 11, GID_B_METAL_MINE => 2, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 2, GID_B_CRYS_STOR => 2, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 0, GID_B_FUSION => 0, GID_RC_METAL => 7000, GID_RC_CRYSTAL => 4000, GID_RC_DEUTERIUM => 1500, 'lastpeek' => $now - 600),
            array ('owner_id' => 2, 'name' => 'Colony Y', 'type' => PTYP_PLANET, 'g' => 1, 's' => 4, 'p' => 4, 'diameter' => 12500, 'temp' => 70, 'fields' => 15, 'maxfields' => 15, GID_B_METAL_MINE => 3, GID_B_CRYS_MINE => 1, GID_B_DEUT_SYNTH => 2, GID_B_SOLAR => 1, GID_B_SHIPYARD => 2, GID_B_METAL_STOR => 1, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 2, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 1, GID_B_FUSION => 1, GID_RC_METAL => 9000, GID_RC_CRYSTAL => 5000, GID_RC_DEUTERIUM => 2500, 'lastpeek' => $now - 600),
            // Player 3
            array ('owner_id' => 3, 'name' => 'Home',     'type' => PTYP_PLANET, 'g' => 1, 's' => 5, 'p' => 4, 'diameter' => 11500, 'temp' => 55, 'fields' => 12, 'maxfields' => 12, GID_B_METAL_MINE => 3, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 3, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 2, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 1, GID_B_FUSION => 0, GID_RC_METAL => 15000, GID_RC_CRYSTAL => 9000, GID_RC_DEUTERIUM => 4000, 'lastpeek' => $now - 600,
                GID_F_SC => 3, GID_F_LF => 5, GID_F_PROBE => 2),
            array ('owner_id' => 3, 'name' => 'Colony Alpha', 'type' => PTYP_PLANET, 'g' => 1, 's' => 5, 'p' => 5, 'diameter' => 10200, 'temp' => 50, 'fields' => 9, 'maxfields' => 9, GID_B_METAL_MINE => 1, GID_B_CRYS_MINE => 1, GID_B_DEUT_SYNTH => 0, GID_B_SOLAR => 1, GID_B_SHIPYARD => 0, GID_B_METAL_STOR => 1, GID_B_CRYS_STOR => 1, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 0, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 0, GID_B_FUSION => 0, GID_RC_METAL => 4000, GID_RC_CRYSTAL => 2500, GID_RC_DEUTERIUM => 800, 'lastpeek' => $now - 600),
            array ('owner_id' => 3, 'name' => 'Colony Beta', 'type' => PTYP_PLANET, 'g' => 1, 's' => 6, 'p' => 4, 'diameter' => 12000, 'temp' => 60, 'fields' => 14, 'maxfields' => 14, GID_B_METAL_MINE => 2, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 2, GID_B_CRYS_STOR => 2, GID_B_DEUT_STOR => 2, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 0, GID_B_FUSION => 0, GID_RC_METAL => 6000, GID_RC_CRYSTAL => 3500, GID_RC_DEUTERIUM => 1200, 'lastpeek' => $now - 600),
        );

        foreach ($planetConfigs as $pConfig) {
            $planetId = AddDBRow ($pConfig, 'planets');

            // First planet of each owner becomes the home planet (aktplanet / hplanetid).
            foreach ($this->players as &$player) {
                if ($player['id'] === $pConfig['owner_id'] && $player['planet_id'] === 0) {
                    $player['planet_id'] = $planetId;
                    $hplanetid = $planetId;
                    dbquery ("UPDATE {$db_prefix}users SET aktplanet = $planetId, hplanetid = $hplanetid WHERE player_id = {$pConfig['owner_id']}");
                    break;
                }
            }
            unset($player);
        }

        // ====================================================================
        // Moons, debris fields, colony phantoms and outer space.
        // A moon is a planets row with type = PTYP_MOON placed at the same
        // coordinates as its planet (see game/core/planet.php LoadPlanet).
        // --------------------------------------------------------------------

        $moonConfigs = array (
            // Player 1: two moons so the jump gate has a target list.
            array ('owner_id' => 1, 'name' => 'Moon',      'type' => PTYP_MOON, 'g' => 1, 's' => 1, 'p' => 4, 'diameter' => 8800, 'temp' => -40, 'fields' => 1, 'maxfields' => 16, GID_B_LUNAR_BASE => 5, GID_B_PHALANX => 2, GID_B_JUMP_GATE => 1, GID_B_SHIPYARD => 2, GID_B_ROBOTS => 1, GID_B_METAL_STOR => 4, GID_B_CRYS_STOR => 4, GID_B_DEUT_STOR => 4, GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 100000, GID_F_SC => 2, GID_F_LF => 4, 'lastpeek' => $now - 600),
            array ('owner_id' => 1, 'name' => 'Moon B',    'type' => PTYP_MOON, 'g' => 1, 's' => 2, 'p' => 4, 'diameter' => 8300, 'temp' => -35, 'fields' => 1, 'maxfields' => 4, GID_B_LUNAR_BASE => 1, GID_B_JUMP_GATE => 1, GID_RC_DEUTERIUM => 50000, 'lastpeek' => $now - 600),
            // Player 2: moon with a sensor phalanx (phalanx scans from it).
            array ('owner_id' => 2, 'name' => 'Moon',      'type' => PTYP_MOON, 'g' => 1, 's' => 3, 'p' => 4, 'diameter' => 8500, 'temp' => -30, 'fields' => 1, 'maxfields' => 10, GID_B_LUNAR_BASE => 3, GID_B_PHALANX => 1, GID_RC_DEUTERIUM => 60000, 'lastpeek' => $now - 600),
            // Player 3: simple moon.
            array ('owner_id' => 3, 'name' => 'Moon',      'type' => PTYP_MOON, 'g' => 1, 's' => 5, 'p' => 4, 'diameter' => 8000, 'temp' => -25, 'fields' => 1, 'maxfields' => 7, GID_B_LUNAR_BASE => 2, GID_RC_DEUTERIUM => 50000, 'lastpeek' => $now - 600),
        );
        $moonIds = array ();
        foreach ($moonConfigs as $mConfig) {
            $moonIds[] = AddDBRow ($mConfig, 'planets');
        }

        // Debris field (recycle mission target; visible in the galaxy when metal+crystal >= 300).
        $dfId = AddDBRow (array (
            'name' => 'Debris', 'type' => PTYP_DF, 'g' => 1, 's' => 2, 'p' => 5, 'owner_id' => USER_SPACE,
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0,
            GID_RC_METAL => 500000, GID_RC_CRYSTAL => 300000, GID_RC_DEUTERIUM => 0, 'date' => $now,
        ), 'planets');

        // Colony phantom (colonize mission target).
        $phantomId = AddDBRow (array (
            'name' => 'Planet', 'type' => PTYP_COLONY_PHANTOM, 'g' => 1, 's' => 1, 'p' => 6, 'owner_id' => USER_SPACE,
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => $now,
        ), 'planets');

        // Outer space (expedition mission target).
        $farspaceId = AddDBRow (array (
            'name' => 'Deep Space', 'type' => PTYP_FARSPACE, 'g' => 1, 's' => 1, 'p' => 16, 'owner_id' => USER_SPACE,
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => $now,
        ), 'planets');

        // ====================================================================
        // Fleets (all missions, including the moon destroy one) + queue events.
        // The Overview "events" list is fed from the queue table (type = Fleet,
        // sub_id = fleet_id, prio = QUEUE_PRIO_FLEET + mission), see
        // game/core/queue.php EnumFleetQueue and game/pages/overview_events.php.
        // --------------------------------------------------------------------

        $fleetConfigs = array (
            // PlayerOne
            array ('owner_id' => 1, 'mission' => FTYP_SPY,       'start_planet' => 1, 'target_planet' => 4,  'flight_time' => 600,  'deploy_time' => 0, 'fuel' => 100, GID_F_SC => 5),
            array ('owner_id' => 1, 'mission' => FTYP_ATTACK,    'start_planet' => 1, 'target_planet' => 4,  'flight_time' => 1200, 'deploy_time' => 0, 'fuel' => 300, GID_F_LF => 10),
            array ('owner_id' => 1, 'mission' => FTYP_TRANSPORT, 'start_planet' => 1, 'target_planet' => 5,  'flight_time' => 900,  'deploy_time' => 0, 'fuel' => 150, GID_F_SC => 5, GID_F_LC => 2, GID_RC_METAL => 5000, GID_RC_CRYSTAL => 2000),
            array ('owner_id' => 1, 'mission' => FTYP_DESTROY,   'start_planet' => 1, 'target_planet' => $moonIds[2], 'flight_time' => 1500, 'deploy_time' => 0, 'fuel' => 2000, GID_F_DEATHSTAR => 1),
            array ('owner_id' => 1, 'mission' => FTYP_DEPLOY,    'start_planet' => 1, 'target_planet' => $moonIds[0], 'flight_time' => 300,  'deploy_time' => 0, 'fuel' => 50,  GID_F_LC => 2),
            array ('owner_id' => 1, 'mission' => FTYP_RECYCLE,   'start_planet' => 1, 'target_planet' => $dfId,      'flight_time' => 800,  'deploy_time' => 0, 'fuel' => 120, GID_F_RECYCLER => 2),
            array ('owner_id' => 1, 'mission' => FTYP_EXPEDITION,'start_planet' => 1, 'target_planet' => $farspaceId, 'flight_time' => 2000, 'deploy_time' => 3600, 'fuel' => 500, GID_F_LC => 1, GID_F_LF => 2, GID_F_PROBE => 1),
            array ('owner_id' => 1, 'mission' => FTYP_COLONIZE,  'start_planet' => 1, 'target_planet' => $phantomId,   'flight_time' => 700,  'deploy_time' => 0, 'fuel' => 200, GID_F_COLON => 1),
            // PlayerTwo: attack against PlayerOne (enemy event on PlayerOne's overview).
            array ('owner_id' => 2, 'mission' => FTYP_ATTACK,    'start_planet' => 4, 'target_planet' => 1,  'flight_time' => 1200, 'deploy_time' => 0, 'fuel' => 200, GID_F_LF => 5, GID_F_SC => 2),
            array ('owner_id' => 2, 'mission' => FTYP_TRANSPORT, 'start_planet' => 4, 'target_planet' => 5,  'flight_time' => 400,  'deploy_time' => 0, 'fuel' => 60,  GID_F_SC => 3),
            // PlayerThree: espionage against PlayerOne.
            array ('owner_id' => 3, 'mission' => FTYP_SPY,       'start_planet' => 7, 'target_planet' => 1,  'flight_time' => 1500, 'deploy_time' => 0, 'fuel' => 50,  GID_F_PROBE => 2),
        );

        $fleetQueueSeconds = array (600, 1200, 900, 1500, 300, 800, 2000, 700, 1200, 400, 1500);

        foreach ($fleetConfigs as $i => $fConfig) {
            $fleetId = AddDBRow ($fConfig, 'fleet');
            // Queue event for the fleet: departs 60 s ago, arrives in
            // flight_time - 60 s. The 60 s offset keeps the flottenversand
            // anti-spam check (abs(time() - start) < 1) from redirecting.
            AddQueue (
                $fConfig['owner_id'], QTYP_FLEET, $fleetId, 0, 0,
                $now - 60, $fleetQueueSeconds[$i] + 60, QUEUE_PRIO_FLEET + $fConfig['mission']
            );
        }

        // ====================================================================
        // Active buildings / research / shipyard queue (Overview + b_building
        // + buildings pages). A building in progress is a buildqueue row plus
        // a queue row (type = Build, sub_id = buildqueue.id). Research and
        // shipyard orders are queue rows with type = Research / Shipyard.
        // --------------------------------------------------------------------

        // Building queue on PlayerOne's home planet: Metal Mine 6, then Crystal Mine 4.
        $bq1 = AddDBRow (array (
            'owner_id' => 1, 'planet_id' => 1, 'list_id' => 1, 'tech_id' => GID_B_METAL_MINE,
            'level' => 6, 'destroy' => 0, 'start' => $now - 120, 'end' => $now + 480,
        ), 'buildqueue');
        AddQueue (1, QTYP_BUILD, $bq1, GID_B_METAL_MINE, 6, $now - 120, 600, QUEUE_PRIO_BUILD);
        $bq2 = AddDBRow (array (
            'owner_id' => 1, 'planet_id' => 1, 'list_id' => 2, 'tech_id' => GID_B_CRYS_MINE,
            'level' => 4, 'destroy' => 0, 'start' => $now + 480, 'end' => $now + 1080,
        ), 'buildqueue');
        AddQueue (1, QTYP_BUILD, $bq2, GID_B_CRYS_MINE, 4, $now + 480, 600, QUEUE_PRIO_BUILD);

        // Active research: Energy Technology 6 (sub_id = planet the lab is on).
        AddQueue (1, QTYP_RESEARCH, 1, GID_R_ENERGY, 6, $now - 100, 600, QUEUE_PRIO_BUILD);

        // Shipyard order: 5 Light Fighters (sub_id = planet id).
        AddQueue (1, QTYP_SHIPYARD, 1, GID_F_LF, 5, $now - 60, 360, QUEUE_PRIO_BUILD);

        // ====================================================================
        // Messages for player 1 (all message types; pm = MTYP_* constants).
        // --------------------------------------------------------------------

        // Existing messages: "Welcome!" (pm=5) and "Hello" (pm=0) -- msg_id 1, 2.
        AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_MISC, 'msgfrom' => 'System', 'subj' => 'Welcome!',
            'text' => 'Welcome to OGame!', 'shown' => 0, 'date' => $now - 3600, 'planet_id' => 0,
        ), 'messages');
        AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_PM, 'msgfrom' => 'PlayerTwo', 'subj' => 'Hello',
            'text' => 'Hi there!', 'shown' => 1, 'date' => $now - 1800, 'planet_id' => 0,
        ), 'messages');

        // Espionage report (pm=1). Its text is echoed raw by bericht.php.
        // The body is built the same way the real game builds it (fleet.php
        // SpyArrive), so the golden snapshots reflect the real HTML structure
        // (issue #269). Load the loca sections first: the subjects below use
        // the localized strings too.
        loca_add("espionage", $lang);
        loca_add("expedition", $lang);
        loca_add("battlereport", $lang);
        loca_add("fleetmsg", $lang);
        loca_add("technames", $lang);

        $spyTarget = LoadPlanetById(4);
        $spyTargetUser = LoadUser(2);
        // Compute the production/energy values the same way the real game does
        // (fleet.php SpyArrive receives the planet from GetUpdatePlanet, which
        // fills net_prod). ProdResources only writes into the passed array, so
        // the fixture DB is not touched.
        $uniData = LoadUniverse();
        if ($uniData !== false && $spyTarget !== null && $spyTargetUser !== null) {
            ProdResources($uniData, $spyTargetUser, $spyTarget);
        }
        $spySubj = "\n<span class=\"espionagereport\">\n" .
                va(loca_lang("SPY_SUBJ", $lang), $spyTarget['name']) . "\n" .
                ShowGalaxy($spyTarget);
        $spyReport = $this->buildSpyReportText($lang, $spyTarget, $spyTargetUser, $now);
        $spyMsgId = AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_SPY_REPORT, 'msgfrom' => loca_lang("FLEET_MESSAGE_FROM", $lang),
            'subj' => $spySubj,
            'text' => $spyReport,
            'shown' => 0, 'date' => $now - 900, 'planet_id' => 4,
        ), 'messages');

        // Battle report body (pm=6). Never shown in the list; bericht.php target.
        // Built with the real battle report generator (battle_report.php) so
        // the golden snapshot shows the real combat report HTML (issue #269).
        $battleReport = $this->buildBattleReportText($lang, $now);
        $battleMsgId = AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_BATTLE_REPORT_TEXT, 'msgfrom' => loca_lang("FLEET_MESSAGE_FROM", $lang),
            'subj' => loca_lang("FLEET_MESSAGE_BATTLE", $lang),
            'text' => $battleReport,
            'shown' => 0, 'date' => $now - 900, 'planet_id' => 1,
        ), 'messages');

        // Battle report link (pm=2) pointing at the report body above. The
        // subject uses the real battle report link class generated by
        // battle.php (issue #269: "combatreport_ididattack_iwon" etc.).
        $battleMsg = loca_lang("FLEET_MESSAGE_BATTLE", $lang);
        AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_BATTLE_REPORT_LINK, 'msgfrom' => loca_lang("FLEET_MESSAGE_FROM", $lang),
            'subj' => "<a href=\"#\" onclick=\"fenster('index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$battleMsgId', 'Bericht_Kampf');\"><span class=\"combatreport_ididattack_iwon\">$battleMsg [1:3:4] (V:100.000,A:50.000)</span></a>",
            'text' => '', 'shown' => 0, 'date' => $now - 900, 'planet_id' => 1,
        ), 'messages');

        // Expedition report (pm=3). Built with the real expedition report
        // structure (issue #269: "fleet found" reports in the messages).
        AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_EXP, 'msgfrom' => loca_lang("FLEET_MESSAGE_FROM", $lang),
            'subj' => va(loca_lang("EXP_MESSAGE_SUBJ", $lang), 1, 1, 16),
            'text' => $this->buildExpeditionReportText($lang),
            'shown' => 0, 'date' => $now - 900, 'planet_id' => 1,
        ), 'messages');

        // Alliance message (pm=4).
        AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_ALLY, 'msgfrom' => '[TST]', 'subj' => 'Alliance',
            'text' => 'PlayerThree has applied to the alliance TST.', 'shown' => 0, 'date' => $now - 900, 'planet_id' => 0,
        ), 'messages');

        // Private message (pm=0) with a reply link. The reply link image uses
        // the skin path like writemessages.php generates it (issue #269).
        AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_PM,
            'msgfrom' => 'PlayerTwo <a href="index.php?page=galaxy&galaxy=1&system=3&position=4&session={PUBLIC_SESSION}">[1:3:4]</a>',
            'subj' => 'Hello <a href="index.php?page=writemessages&session={PUBLIC_SESSION}&messageziel=2&re=1&betreff=Re:Hello"><img border="0" alt="Reply" src="http://localhost/evolution/img/m.gif" /></a>',
            'text' => 'Hi! Are you online?', 'shown' => 0, 'date' => $now - 900, 'planet_id' => 0,
        ), 'messages');

        // A note for player 1.
        AddDBRow (array (
            'owner_id' => 1, 'subj' => 'Test Note', 'text' => 'This is a test note for PlayerOne.',
            'textsize' => mb_strlen('This is a test note for PlayerOne.', 'UTF-8'), 'prio' => 0, 'date' => $now,
        ), 'notes');

        // An alliance owned by PlayerOne (allianzen / ainfo / bewerben pages).
        // nextrank = 3 matches the three ranks added below (0=Founder, 1=Founder,
        // 2=Recruiter); tag_until/name_until = 0 so change-tag/name POSTs work
        // (CreateAlly initializes the same fields, see game/core/ally.php).
        $allyId = AddDBRow (array (
            'tag' => 'TST', 'name' => 'Test Alliance', 'owner_id' => 1,
            'homepage' => 'https://example.com', 'open' => 1, 'insertapp' => 1,
            'exttext' => 'External description of the Test Alliance.',
            'inttext' => 'Internal description of the Test Alliance.',
            'apptext' => 'Please introduce yourself.',
            'nextrank' => 3, 'old_tag' => '', 'old_name' => '',
            'tag_until' => 0, 'name_until' => 0,
            'score1' => 90000, 'score2' => 58000, 'score3' => 38000,
            'place1' => 1, 'place2' => 1, 'place3' => 1,
        ), 'ally');

        // Add ranks and PlayerTwo/PlayerThree as members.
        // rank_id 0 = founder (needed by allianzen settings page), rank 1 = newbie
        // (existing), rank 2 = a real rank so the ranks page has a row to show.
        AddDBRow (array ('rank_id' => 0, 'ally_id' => $allyId, 'name' => 'Founder', 'rights' => 0x1FF), 'allyranks');
        AddDBRow (array ('rank_id' => 1, 'ally_id' => $allyId, 'name' => 'Founder', 'rights' => 0x1FF), 'allyranks');
        AddDBRow (array ('rank_id' => 2, 'ally_id' => $allyId, 'name' => 'Recruiter', 'rights' => ARANK_R_APPLY | ARANK_R_MEMBERS), 'allyranks');
        foreach (array (1 => 1, 2 => 1, 3 => 1) as $playerId => $rankId) {
            dbquery ("UPDATE {$db_prefix}users SET ally_id = $allyId, allyrank = $rankId, joindate = $now WHERE player_id = $playerId");
        }

        // PlayerThree applied to the alliance (bewerbungen page).
        AddDBRow (array (
            'ally_id' => $allyId, 'player_id' => 3,
            'text' => 'Hello TST, I would like to join your alliance. My fleet is ready.',
            'date' => $now - 3600,
        ), 'allyapps');

        // Buddies for PlayerOne: an accepted buddy (shown in the buddy list)
        // and a pending request (shown on the buddy requests page).
        AddDBRow (array (
            'request_from' => 2, 'request_to' => 1, 'text' => 'Be my buddy!', 'accepted' => 1,
        ), 'buddy');
        AddDBRow (array (
            'request_from' => 3, 'request_to' => 1, 'text' => 'Hello, add me please!', 'accepted' => 0,
        ), 'buddy');

        // A fleet template for PlayerOne (fleet_templates / flotten1 pages).
        AddDBRow (array (
            'owner_id' => 1, 'name' => 'Attack Fleet', 'date' => $now - 3600,
            GID_F_SC => 5, GID_F_LF => 10, GID_F_CRUISER => 1,
        ), 'template');

        // A ban in the Pillar of Shame (pranger page).
        AddDBRow (array (
            'admin_name' => 'GO', 'user_name' => 'BadPlayer', 'admin_id' => 1, 'user_id' => 2,
            'ban_when' => $now - 86400, 'ban_until' => $now + 86400, 'reason' => 'Fleet saving',
        ), 'pranger');

        // PlayerOne has an active trade offer (trader page).
        dbquery ("UPDATE {$db_prefix}users SET trader = 1, rate_m = 2.4, rate_k = 2.0, rate_d = 1.0 WHERE player_id = 1");

        // The realistic report builders above load users/planets into the
        // global caches (LoadUser/UserCache). Tests that mutate a user row
        // directly (e.g. XssTest sets a malicious player name) expect the
        // cache to be empty so their UPDATE is picked up by the next LoadUser
        // call. Clear the caches before handing the fixture to the test.
        InvalidateUserCache ();

        return $this;
    }

    /**
     * Get player data by index (0-based).
     */
    public function getPlayer(int $index): ?array
    {
        $playerId = $index + 1;
        return $this->players[$playerId] ?? null;
    }

    /**
     * Get all players (keyed by player_id).
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * Get universe settings.
     */
    public function getUniData(): array
    {
        global $db_prefix;
        $result = dbquery ("SELECT * FROM {$db_prefix}uni LIMIT 1");
        $row = dbarray ($result);
        return $row === false ? array () : $row;
    }

    /**
     * Get the current timestamp for the fixture.
     */
    public function getNow(): int
    {
        return time();
    }

    // ========================================================================
    // Realistic report text builders (issue #269)
    // ------------------------------------------------------------------------
    // The spy/battle/expedition report message bodies are built with the same
    // HTML structure the real game generates (fleet.php SpyArrive /
    // battle_report.php BattleReport / expedition.php), so the golden
    // snapshots reflect the real report code instead of a synthetic table.

    /**
     * Build the espionage report text the same way fleet.php SpyArrive does.
     *
     * @param string $lang Language of the report.
     * @param array $target Target planet row (LoadPlanetById).
     * @param array $targetUser Target player row (LoadUser).
     * @param int $now Report timestamp.
     * @return string Report HTML.
     */
    private function buildSpyReportText(string $lang, array $target, array $targetUser, int $now): string
    {
        global $fleetmap, $defmap, $buildmap, $resmap;

        loca_add("espionage", $lang);
        loca_add("technames", $lang);
        loca_add("fleetmsg", $lang);

        // Full espionage level (origin tech 5, no Technocrat bonus).
        $level = 5;
        $counter = 0;

        $report = "";

        // Head
        $report .= "<table width=400><tr><td class=c colspan=4>" .
                va(loca_lang("SPY_RESOURCES", $lang), $target['name']) . " " .
                ShowGalaxy($target) . " " .
                va(loca_lang("SPY_PLAYER", $lang), htmlspecialchars($targetUser['oname']), date("m-d H:i:s", $now)) .
                "</td></tr>\n";
        $report .= "</div></font></TD></TR><tr><td>".loca_lang("SPY_M", $lang)."</td><td>".nicenum($target[GID_RC_METAL])."</td>\n";
        $report .= "<td>".loca_lang("SPY_K", $lang)."</td><td>".nicenum($target[GID_RC_CRYSTAL])."</td></tr>\n";
        $report .= "<tr><td>".loca_lang("SPY_D", $lang)."</td><td>".nicenum($target[GID_RC_DEUTERIUM])."</td>\n";
        $report .= "<td>".loca_lang("SPY_E", $lang)."</td><td>".nicenum($target['net_prod'][GID_RC_ENERGY] ?? 0)."</td></tr>\n";
        $report .= "</table>\n";

        // Activity
        $report .= "<table width=400><tr><td class=c colspan=4>     </td></tr>\n";
        $report .= "<TR><TD colspan=4><div onmouseover='return overlib(\"&lt;font color=white&gt;".loca_lang("SPY_ACTIVITY", $lang)."&lt;/font&gt;\", STICKY, MOUSEOFF, DELAY, 750, CENTER, WIDTH, 100, OFFSETX, -130, OFFSETY, -10);' onmouseout='return nd();'></TD></TR></table>\n";

        // Fleet (level > 0)
        if ($level > 0) {
            $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_FLEET", $lang)."     </td></tr>\n";
            $count = 0;
            foreach ($fleetmap as $i=>$gid) {
                $amount = $target[$gid] ?? 0;
                if ($amount > 0) {
                    if (($count % 2) == 0) $report .= "</tr>\n";
                    $report .= "<td>".loca_lang("NAME_$gid", $lang)."</td><td>".nicenum($amount)."</td>\n";
                    $count++;
                }
            }
            $report .= "</table>\n";
        }

        // Defense (level > 1)
        if ($level > 1) {
            $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_DEFENSE", $lang)."     </td></tr>\n";
            $count = 0;
            foreach ($defmap as $i=>$gid) {
                $amount = $target[$gid] ?? 0;
                if ($amount > 0) {
                    if (($count % 2) == 0) $report .= "</tr>\n";
                    $report .= "<td>".loca_lang("NAME_$gid", $lang)."</td><td>".nicenum($amount)."</td>\n";
                    $count++;
                }
            }
            $report .= "</table>\n";
        }

        // Buildings (level > 3)
        if ($level > 3) {
            $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_BUILDINGS", $lang)."     </td></tr>\n";
            $count = 0;
            foreach ($buildmap as $i=>$gid) {
                $amount = $target[$gid] ?? 0;
                if ($amount > 0) {
                    if (($count % 2) == 0) $report .= "</tr>\n";
                    $report .= "<td>".loca_lang("NAME_$gid", $lang)."</td><td>".nicenum($amount)."</td>\n";
                    $count++;
                }
            }
            $report .= "</table>\n";
        }

        // Research (level > 5)
        if ($level > 5) {
            $report .= "<table width=400><tr><td class=c colspan=4>".loca_lang("SPY_RESEARCH", $lang)."     </td></tr>\n";
            $count = 0;
            foreach ($resmap as $i=>$gid) {
                $amount = $targetUser[$gid] ?? 0;
                if ($amount > 0) {
                    if (($count % 2) == 0) $report .= "</tr>\n";
                    $report .= "<td>".loca_lang("NAME_$gid", $lang)."</td><td>".nicenum($amount)."</td>\n";
                    $count++;
                }
            }
            $report .= "</table>\n";
        }

        $report .= "<center>".va(loca_lang("SPY_COUNTER", $lang), floor($counter))."</center>\n";
        $report .= "<center><a href='#' onclick='showFleetMenu(".$target['g'].",".$target['s'].",".$target['p'].",".GetPlanetType($target).",1);'>".loca_lang("SPY_ATTACK", $lang)."</a></center>\n";

        return $report;
    }

    /**
     * Build the battle report text with the real generator (BattleReport).
     *
     * Simulates a small fight: PlayerOne attacks PlayerTwo with 10 Light
     * Fighters vs 3 Rocket Launchers, attacker wins. The report structure is
     * identical to the one the game stores for real combats.
     *
     * @param string $lang Language of the report.
     * @param int $now Report timestamp.
     * @return string Report HTML.
     */
    private function buildBattleReportText(string $lang, int $now): string
    {
        loca_add("battlereport", $lang);
        loca_add("technames", $lang);
        loca_add("fleetmsg", $lang);

        // Attacker: PlayerOne (1:1:4) -- 10 Light Fighters (techs 3/3/4).
        // Defender: PlayerTwo (1:3:4) -- 3 Rocket Launchers (techs 3/3/4).
        $amap = array (GID_F_LF => 10);
        $dmap = array (GID_D_RL => 3);

        $res = array (
            'result' => 'awon',
            'before' => array (
                'attackers' => array (
                    array (
                        'name' => 'PlayerOne', 'g' => 1, 's' => 1, 'p' => 4,
                        'weap' => 3, 'shld' => 3, 'armr' => 4,
                        'pf' => BATTLE_PTCP_FLEET,
                        'units' => $amap,
                    ),
                ),
                'defenders' => array (
                    array (
                        'name' => 'PlayerTwo', 'g' => 1, 's' => 3, 'p' => 4,
                        'weap' => 3, 'shld' => 3, 'armr' => 4,
                        'pf' => BATTLE_PTCP_PLANET,
                        'units' => $dmap,
                    ),
                ),
            ),
            'rounds' => array (
                array (
                    'ashoot' => 10, 'apower' => 500, 'dabsorb' => 0,
                    'dshoot' => 3, 'dpower' => 200, 'aabsorb' => 0,
                    'attackers' => array (
                        array ('name' => 'PlayerOne', 'g' => 1, 's' => 1, 'p' => 4, 'pf' => BATTLE_PTCP_FLEET, 'units' => $amap),
                    ),
                    'defenders' => array (
                        array ('name' => 'PlayerTwo', 'g' => 1, 's' => 3, 'p' => 4, 'pf' => BATTLE_PTCP_PLANET, 'units' => array ()),
                    ),
                ),
            ),
            'extra' => array (),
        );

        $loss = array ('aloss' => 0, 'dloss' => 3000);
        $captured = array (GID_RC_METAL => 2000, GID_RC_CRYSTAL => 1000, GID_RC_DEUTERIUM => 500);
        $debris = array (GID_RC_METAL => 100, GID_RC_CRYSTAL => 50, GID_RC_DEUTERIUM => 0);
        // Repaired defense: keyed by defender index (0 = the planet), with
        // every defense gid present so the generator's repairmap loop works.
        $repaired = array (0 => array (
            GID_D_RL => 1, GID_D_LL => 0, GID_D_HL => 0, GID_D_GAUSS => 0,
            GID_D_ION => 0, GID_D_SDOME => 0, GID_D_PLASMA => 0, GID_D_LDOME => 0,
        ));

        return BattleReport ($res, $now, $loss, $captured, 0, false, $repaired, $debris, $lang);
    }

    /**
     * Build the expedition report text (fleet found by the expedition).
     *
     * @param string $lang Language of the report.
     * @return string Report HTML.
     */
    private function buildExpeditionReportText(string $lang): string
    {
        loca_add("expedition", $lang);
        loca_add("technames", $lang);
        loca_add("fleetmsg", $lang);

        // "Fleet found" event: the report describes finding ships. Use the
        // first fleet-found message template with a small cargo fleet.
        return va(loca_lang("EXP_FLEET_SMALL_1", $lang),
                "<a onclick=\"showGalaxy(1,1,16);\" href=\"#\">[1:1:16]</a>") . "\n<br/>\n<br/>\n" .
                va(loca_lang("EXP_FLEET_LOGBOOK_1", $lang), 2);
    }
}
