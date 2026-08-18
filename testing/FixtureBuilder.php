<?php

// Load game constants (GID_R_*, GID_B_*, GID_F_*, GID_D_*, GID_RC_*, ...).
// The bootstrap (testing/bootstrap.php) already loads these at the top level;
// the require_once calls below are for standalone use (e.g. bootstrap_golden.php).
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
     */
    public function createTestUniverse(): self
    {
        global $db_prefix;
        $now = time();

        // Universe settings (must match testUniverseSettingsAreConfigured).
        AddDBRow (array (
            'num' => 1, 'speed' => 1.0, 'fspeed' => 1.0, 'galaxies' => 1, 'systems' => 15,
            'maxusers' => 1000, 'acs' => 1, 'rapid' => 0, 'moons' => 1, 'defrepair' => 0,
            'defrepair_delta' => 0, 'usercount' => 3, 'freeze' => 0, 'startdate' => $now - 86400,
            'battle_engine' => 'php', 'lang' => 'en', 'hacks' => 0, 'php_battle' => 1,
            'force_lang' => 0, 'start_dm' => 0, 'max_werf' => 1000, 'feedage' => 0,
            'news1' => '', 'news2' => '', 'news_until' => 0, 'modlist' => '',
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
            GID_R_ESPIONAGE => 5, GID_R_COMPUTER => 4, GID_R_WEAPON => 3, GID_R_SHIELD => 3,
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
                'lang' => 'en',
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
            array ('owner_id' => 1, 'name' => 'Home',     'type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 4, 'diameter' => 12200, 'temp' => 65, 'fields' => 14, 'maxfields' => 14, GID_B_METAL_MINE => 5, GID_B_CRYS_MINE => 3, GID_B_DEUT_SYNTH => 2, GID_B_SOLAR => 3, GID_B_SHIPYARD => 3, GID_B_METAL_STOR => 5, GID_B_CRYS_STOR => 5, GID_B_DEUT_STOR => 4, GID_B_MISS_SILO => 3, GID_B_ALLY_DEPOT => 1, GID_B_ROBOTS => 2, GID_B_FUSION => 1, GID_RC_METAL => 25000, GID_RC_CRYSTAL => 15000, GID_RC_DEUTERIUM => 8000, 'lastpeek' => $now - 600),
            array ('owner_id' => 1, 'name' => 'Colony A', 'type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 5, 'diameter' => 11000, 'temp' => 60, 'fields' => 12, 'maxfields' => 12, GID_B_METAL_MINE => 3, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_B_SHIPYARD => 2, GID_B_METAL_STOR => 3, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 2, GID_B_MISS_SILO => 2, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 1, GID_B_FUSION => 0, GID_RC_METAL => 8000, GID_RC_CRYSTAL => 5000, GID_RC_DEUTERIUM => 2000, 'lastpeek' => $now - 600),
            array ('owner_id' => 1, 'name' => 'Colony B', 'type' => PTYP_PLANET, 'g' => 1, 's' => 2, 'p' => 4, 'diameter' => 10500, 'temp' => 55, 'fields' => 10, 'maxfields' => 10, GID_B_METAL_MINE => 2, GID_B_CRYS_MINE => 1, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 1, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 2, GID_B_CRYS_STOR => 2, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 0, GID_B_FUSION => 0, GID_RC_METAL => 5000, GID_RC_CRYSTAL => 3000, GID_RC_DEUTERIUM => 1000, 'lastpeek' => $now - 600),
            // Player 2
            array ('owner_id' => 2, 'name' => 'Home',     'type' => PTYP_PLANET, 'g' => 1, 's' => 3, 'p' => 4, 'diameter' => 11800, 'temp' => 62, 'fields' => 13, 'maxfields' => 13, GID_B_METAL_MINE => 4, GID_B_CRYS_MINE => 3, GID_B_DEUT_SYNTH => 2, GID_B_SOLAR => 2, GID_B_SHIPYARD => 2, GID_B_METAL_STOR => 4, GID_B_CRYS_STOR => 4, GID_B_DEUT_STOR => 3, GID_B_MISS_SILO => 2, GID_B_ALLY_DEPOT => 1, GID_B_ROBOTS => 1, GID_B_FUSION => 0, GID_RC_METAL => 20000, GID_RC_CRYSTAL => 12000, GID_RC_DEUTERIUM => 6000, 'lastpeek' => $now - 600),
            array ('owner_id' => 2, 'name' => 'Colony X', 'type' => PTYP_PLANET, 'g' => 1, 's' => 3, 'p' => 5, 'diameter' => 10800, 'temp' => 58, 'fields' => 11, 'maxfields' => 11, GID_B_METAL_MINE => 2, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 2, GID_B_CRYS_STOR => 2, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 0, GID_B_FUSION => 0, GID_RC_METAL => 7000, GID_RC_CRYSTAL => 4000, GID_RC_DEUTERIUM => 1500, 'lastpeek' => $now - 600),
            array ('owner_id' => 2, 'name' => 'Colony Y', 'type' => PTYP_PLANET, 'g' => 1, 's' => 4, 'p' => 4, 'diameter' => 12500, 'temp' => 70, 'fields' => 15, 'maxfields' => 15, GID_B_METAL_MINE => 3, GID_B_CRYS_MINE => 1, GID_B_DEUT_SYNTH => 2, GID_B_SOLAR => 1, GID_B_SHIPYARD => 2, GID_B_METAL_STOR => 1, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 2, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 1, GID_B_FUSION => 1, GID_RC_METAL => 9000, GID_RC_CRYSTAL => 5000, GID_RC_DEUTERIUM => 2500, 'lastpeek' => $now - 600),
            // Player 3
            array ('owner_id' => 3, 'name' => 'Home',     'type' => PTYP_PLANET, 'g' => 1, 's' => 5, 'p' => 4, 'diameter' => 11500, 'temp' => 55, 'fields' => 12, 'maxfields' => 12, GID_B_METAL_MINE => 3, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 3, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 2, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_ROBOTS => 1, GID_B_FUSION => 0, GID_RC_METAL => 15000, GID_RC_CRYSTAL => 9000, GID_RC_DEUTERIUM => 4000, 'lastpeek' => $now - 600),
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

        // Fleets for player 1: one spy mission (Small Cargoes), one attack (Light Fighters).
        AddDBRow (array (
            'owner_id' => 1, 'mission' => FTYP_SPY, 'start_planet' => 1, 'target_planet' => 4,
            'flight_time' => 600, 'deploy_time' => 0, 'fuel' => 100, GID_F_SC => 5,
        ), 'fleet');
        AddDBRow (array (
            'owner_id' => 1, 'mission' => FTYP_ATTACK, 'start_planet' => 1, 'target_planet' => 7,
            'flight_time' => 1200, 'deploy_time' => 0, 'fuel' => 300, GID_F_LF => 10,
        ), 'fleet');

        // Messages for player 1.
        AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_MISC, 'msgfrom' => 'System', 'subj' => 'Welcome!',
            'text' => 'Welcome to OGame!', 'shown' => 0, 'date' => $now - 3600, 'planet_id' => 0,
        ), 'messages');
        AddDBRow (array (
            'owner_id' => 1, 'pm' => MTYP_PM, 'msgfrom' => 'PlayerTwo', 'subj' => 'Hello',
            'text' => 'Hi there!', 'shown' => 1, 'date' => $now - 1800, 'planet_id' => 0,
        ), 'messages');

        // A note for player 1.
        AddDBRow (array (
            'owner_id' => 1, 'subj' => 'Test Note', 'text' => 'This is a test note for PlayerOne.',
            'textsize' => mb_strlen('This is a test note for PlayerOne.', 'UTF-8'), 'prio' => 0, 'date' => $now,
        ), 'notes');

        // An alliance owned by PlayerOne (allianzen / ainfo / bewerben pages).
        $allyId = AddDBRow (array (
            'tag' => 'TST', 'name' => 'Test Alliance', 'owner_id' => 1,
            'homepage' => 'https://example.com', 'open' => 1, 'insertapp' => 1,
            'exttext' => 'External description of the Test Alliance.',
            'inttext' => 'Internal description of the Test Alliance.',
            'apptext' => 'Please introduce yourself.',
            'score1' => 90000, 'score2' => 58000, 'score3' => 38000,
            'place1' => 1, 'place2' => 1, 'place3' => 1,
        ), 'ally');

        // Add a rank and PlayerTwo/PlayerThree as members.
        AddDBRow (array ('rank_id' => 1, 'ally_id' => $allyId, 'name' => 'Founder', 'rights' => 0x1FF), 'allyranks');
        foreach (array (1 => 1, 2 => 1, 3 => 1) as $playerId => $rankId) {
            dbquery ("UPDATE {$db_prefix}users SET ally_id = $allyId, allyrank = $rankId, joindate = $now WHERE player_id = $playerId");
        }

        // PlayerOne has an active trade offer (trader page).
        dbquery ("UPDATE {$db_prefix}users SET trader = 1, rate_m = 2.4, rate_k = 2.0, rate_d = 1.0 WHERE player_id = 1");

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
}
