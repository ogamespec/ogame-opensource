<?php

// Database-backed unit tests for the Wanderer modification.
//
// These tests exercise the mod's DB-facing lifecycle against the in-memory
// SQLite backend: install()/uninstall() (tables, users column, tick event),
// the game-mode switch, the station build/research queue and production, the
// jumps between sectors, the Guild exchange, the player orders and the new
// engine hooks (skip_planet_update, fleet_dispatch_veto, update_queue).
//
// Each method runs in a separate PHP process (like the Space Storm and Deep
// Space Horror DB suites) so the file-scope globals of the game core never
// clash between tests. A minimal universe is built with the mod's own
// install() so the hooks have real data to read via LoadUser/LoadPlanet.

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class WandererDbTest extends TestCase
{
    private ?Wanderer $mod = null;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';

        if (!function_exists('dbquery')) {
            require_once __DIR__ . '/../../../../game/core/db.php';
        }
        if (!class_exists('Wanderer')) {
            require_once __DIR__ . '/../main.php';
        }

        $this->mod = new Wanderer();
        $this->buildMinimalDb();
    }

    private function mod(): Wanderer
    {
        $this->assertInstanceOf(Wanderer::class, $this->mod);
        return $this->mod;
    }

    /**
     * Time anchor of the tests: "now". Offsets are used everywhere, so the
     * planet/station timers (lastpeek/lastprod) always move forward.
     */
    private function t0(): int
    {
        return time();
    }

    // ========================================================================
    // Minimal in-memory universe
    // ========================================================================

    private function buildMinimalDb(): void
    {
        global $db_prefix, $db_name, $db_host, $db_user, $db_pass;
        global $Languages, $DefaultLanguage, $loca_lang, $StartPage, $from_cron, $UserCache, $LOCA;
        global $GlobalUni, $GlobalUser;

        $db_prefix = 'test_';
        $db_name = 'test';
        $db_host = '';
        $db_user = '';
        $db_pass = '';
        $StartPage = 'index.php';
        $from_cron = false;
        $UserCache = array();
        $GlobalUser = array('lang' => 'en', 'player_id' => 0, 'admin' => 0);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit';
        $_SERVER['REQUEST_URI'] = '/';

        if (!isset($Languages)) $Languages = array('en' => 'English', 'ru' => 'Русский', 'de' => 'Deutsch');
        if (!isset($DefaultLanguage)) $DefaultLanguage = 'en';
        if (!isset($loca_lang)) $loca_lang = 'en';
        if (!isset($LOCA)) $LOCA = array();

        InitDB();

        // Register the mod instance so the hook dispatchers reach it — the
        // same way ModsInit() does in the live game. install_tabs_included()
        // then participates in CreateDBTables().
        $GLOBALS['modlist'] = array('Wanderer' => $this->mod());

        CreateDBTables();

        // The mod schema: users.wanderer_mode + wanderer_stations/orders + tick event.
        $this->mod()->install();

        // Universe row (3 galaxies, 60 systems, economy speed 2).
        $now = time();
        AddDBRow(array(
            'num' => 1, 'speed' => 2.0, 'fspeed' => 1.0, 'galaxies' => 3, 'systems' => 60,
            'maxusers' => 1000, 'acs' => 1, 'fid' => 30, 'did' => 30, 'rapid' => 1, 'moons' => 1,
            'defrepair' => 0, 'defrepair_delta' => 0, 'usercount' => 3, 'freeze' => 0,
            'startdate' => $now - 86400, 'battle_engine' => 'php', 'lang' => 'en', 'hacks' => 0,
            'php_battle' => 1, 'force_lang' => 0, 'start_dm' => 0, 'max_werf' => 1000,
            'feedage' => 0, 'modlist' => '',
        ), 'uni');
        $GlobalUni = LoadUniverse();

        // The technical account (used by the tick event).
        AddDBRow(array(
            'player_id' => USER_SPACE, 'regdate' => $now, 'name' => 'space', 'oname' => 'space',
            'lang' => 'en', 'dm' => 0, 'dmfree' => 0, 'score1' => 0, 'score2' => 0, 'score3' => 0,
            'maxspy' => 0, 'hplanetid' => 0, 'flags' => 0, 'feedid' => '', 'lastfeed' => 0,
            'com_until' => 0, 'adm_until' => 0, 'eng_until' => 0, 'geo_until' => 0, 'tec_until' => 0,
        ), 'users');

        $this->addPlayer(1);
        $this->addPlayer(2);
        $this->addPlayer(3);

        // Player 1 and 2 own a home planet; player 3 owns none.
        $p1 = $this->addPlanet(1);
        $p2 = $this->addPlanet(2);
        $this->setHome(1, $p1);
        $this->setHome(2, $p2);
    }

    private function addPlayer(int $playerId): void
    {
        global $db_prefix;
        $result = dbquery("SELECT player_id FROM {$db_prefix}users WHERE player_id = $playerId");
        if (dbrows($result) > 0) return;
        $now = time();
        AddDBRow(array(
            'player_id' => $playerId, 'regdate' => $now, 'name' => "p$playerId", 'oname' => "Player$playerId",
            'lang' => 'en', 'dm' => 0, 'dmfree' => 0, 'score1' => 0, 'score2' => 0, 'score3' => 0,
            'maxspy' => 0, 'hplanetid' => 0, 'flags' => 0, 'feedid' => '', 'lastfeed' => 0,
            'com_until' => 0, 'adm_until' => 0, 'eng_until' => 0, 'geo_until' => 0, 'tec_until' => 0,
            GID_R_WEAPON => 0, GID_R_SHIELD => 0, GID_R_ARMOUR => 0,
        ), 'users');
    }

    /**
     * Add a plain player planet and return its id.
     */
    private function addPlanet(int $ownerId, array $extra = array()): int
    {
        $now = time();
        $row = array(
            'name' => "Planet$ownerId", 'type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => $ownerId,
            'owner_id' => $ownerId, 'diameter' => 12800, 'temp' => 20, 'fields' => 10, 'maxfields' => 10,
            'date' => $now,
            GID_B_METAL_STOR => 0, GID_B_CRYS_STOR => 5, GID_B_DEUT_STOR => 0,
            GID_RC_METAL => 1000.0, GID_RC_CRYSTAL => 1000.0, GID_RC_DEUTERIUM => 1000.0,
            'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0,
        ) + $extra;
        return AddDBRow($row, 'planets');
    }

    private function setHome(int $playerId, int $planetId): void
    {
        global $db_prefix;
        dbquery("UPDATE {$db_prefix}users SET hplanetid = $planetId, aktplanet = $planetId WHERE player_id = $playerId");
    }

    private function queryOne(string $sql): ?array
    {
        $result = dbquery($sql, true);
        if ($result === false) return null;
        $row = dbarray($result);
        return $row === false ? null : $row;
    }

    private function userMode(int $playerId): int
    {
        $u = LoadUser($playerId);
        return $u === null ? -1 : (int)($u['wanderer_mode'] ?? 0);
    }

    // ========================================================================
    // Install / uninstall
    // ========================================================================

    public function testInstallCreatesSchemaAndEvent(): void
    {
        global $db_prefix;

        // Both tables exist (SELECT against a missing table returns false).
        $res = dbquery("SELECT * FROM {$db_prefix}wanderer_stations LIMIT 1");
        $this->assertNotFalse($res);
        $res = dbquery("SELECT * FROM {$db_prefix}wanderer_orders LIMIT 1");
        $this->assertNotFalse($res);

        // The users column exists and the players are in the classic mode.
        $this->assertSame(0, $this->userMode(1));

        // The global tick event exists exactly once.
        $result = dbquery("SELECT task_id FROM {$db_prefix}queue WHERE type = '" . QTYP_WANDERER_TICK . "'");
        $this->assertSame(1, dbrows($result));
    }

    public function testInstallIsRepeatable(): void
    {
        global $db_prefix;
        // A second install() must not create a duplicate event or fail.
        $this->mod()->install();
        $result = dbquery("SELECT task_id FROM {$db_prefix}queue WHERE type = '" . QTYP_WANDERER_TICK . "'");
        $this->assertSame(1, dbrows($result));
    }

    public function testModeSwitchAndBeacon(): void
    {
        global $db_prefix;
        $t = $this->t0();

        // A player without planets cannot become a trader.
        $this->assertSame('WANDERER_ERR_NO_PLANETS', Wanderer::EnterWandererMode(3, $t));

        // Player 1 becomes a trader.
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));
        $this->assertSame(1, $this->userMode(1));

        $station = Wanderer::LoadStation(1);
        $this->assertNotNull($station);
        $this->assertSame('Rogue Station', $station['name']);
        $this->assertGreaterThan(0, (int)$station['planet_id']);

        // The beacon exists as a custom galaxy object of the player.
        $beacon = $this->queryOne("SELECT * FROM {$db_prefix}planets WHERE planet_id = " . (int)$station['planet_id']);
        $this->assertNotNull($beacon);
        $this->assertSame(PTYP_WANDERER_STATION, (int)$beacon['type']);
        $this->assertSame(1, (int)$beacon['owner_id']);

        // The active planet points to the station.
        $user = LoadUser(1);
        $this->assertSame((int)$station['planet_id'], (int)$user['aktplanet']);

        // Cannot enter twice.
        $this->assertSame('WANDERER_ERR_ALREADY', Wanderer::EnterWandererMode(1, $t));

        // A welcome message was sent.
        $result = dbquery("SELECT msg_id FROM {$db_prefix}messages WHERE owner_id = 1");
        $this->assertGreaterThan(0, dbrows($result));
    }

    public function testEnterBlockedByFleets(): void
    {
        global $db_prefix;
        $t = $this->t0();

        // Own flying fleet.
        AddDBRow(array(
            'owner_id' => 2, 'union_id' => 0, 'fuel' => 100, 'mission' => FTYP_ATTACK,
            'start_planet' => 0, 'target_planet' => 0, 'flight_time' => 60, 'deploy_time' => 0,
            GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0, GID_F_LF => 1,
        ), 'fleet');
        $this->assertSame('WANDERER_ERR_OWN_FLEETS', Wanderer::EnterWandererMode(2, $t));
        dbquery("DELETE FROM {$db_prefix}fleet");

        // A foreign fleet heading to his planet.
        $planet2 = (int)$this->queryOne("SELECT planet_id FROM {$db_prefix}planets WHERE owner_id = 2 LIMIT 1")['planet_id'];
        AddDBRow(array(
            'owner_id' => 1, 'union_id' => 0, 'fuel' => 100, 'mission' => FTYP_ATTACK,
            'start_planet' => 0, 'target_planet' => $planet2, 'flight_time' => 60, 'deploy_time' => 0,
            GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0, GID_F_LF => 1,
        ), 'fleet');
        $this->assertSame('WANDERER_ERR_INCOMING_FLEETS', Wanderer::EnterWandererMode(2, $t));
    }

    public function testStationBuildAndTick(): void
    {
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));
        $station = Wanderer::LoadStation(1);

        // Upgrade the ore module (cost paid from the cargo).
        $metal_before = (float)$station['metal'];
        $err = Wanderer::StartUpgrade(1, 'mod_mine_m', $t);
        $this->assertSame('', $err);

        $station = Wanderer::LoadStation(1);
        $this->assertSame('M', $station['build_type']);
        $this->assertSame('mod_mine_m', $station['build_id']);
        $this->assertLessThan($metal_before, (float)$station['metal']);
        $this->assertGreaterThan($t, (int)$station['build_until']);

        // The station is busy: no second build.
        $this->assertSame('WANDERER_ERR_BUILD_BUSY', Wanderer::StartUpgrade(1, 'mod_mine_k', $t + 10));

        // Complete the build.
        Wanderer::TickUser(1, (int)$station['build_until'] + 1);
        $station = Wanderer::LoadStation(1);
        $this->assertSame(1, (int)$station['mod_mine_m']);
        $this->assertSame('', $station['build_type']);

        // Production accrues from now on.
        $prod = Wanderer::StationProduction($station);
        $metal_before = (float)$station['metal'];
        Wanderer::TickUser(1, (int)$station['lastprod'] + 3600);
        $station = Wanderer::LoadStation(1);
        $this->assertEqualsWithDelta($metal_before + $prod[GID_RC_METAL], (float)$station['metal'], 1.0);
    }

    public function testResearchRequiresLab(): void
    {
        global $db_prefix;
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));

        // No laboratory: research impossible.
        $this->assertSame('WANDERER_ERR_LAB_REQ', Wanderer::StartUpgrade(1, 'res_nav', $t));

        // Add a lab directly (as if built earlier) and research.
        $station = Wanderer::LoadStation(1);
        $station['mod_lab'] = 1;
        $station['metal'] = 100000.0;
        $station['crystal'] = 100000.0;
        $station['deuterium'] = 100000.0;
        Wanderer::SaveStation($station);
        $err = Wanderer::StartUpgrade(1, 'res_nav', $t);
        $this->assertSame('', $err);

        $station = Wanderer::LoadStation(1);
        Wanderer::TickUser(1, (int)$station['build_until'] + 1);
        $station = Wanderer::LoadStation(1);
        $this->assertSame(1, (int)$station['res_nav']);

        // The lab level caps the research level.
        $this->assertSame('WANDERER_ERR_MAX_LEVEL', Wanderer::StartUpgrade(1, 'res_nav', $t + 100000));
    }

    public function testJumpMechanics(): void
    {
        global $db_prefix;
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));
        $station = Wanderer::LoadStation(1);
        $beacon_id = (int)$station['planet_id'];

        $from_g = (int)$station['g'];
        $target_g = $from_g == 3 ? 1 : $from_g + 1;
        $fuel_before = (float)$station['deuterium'];
        $cost = Wanderer::JumpCost($station, $from_g, $target_g);
        $this->assertLessThan($fuel_before, $cost + 1);

        $err = Wanderer::DoJump(1, $target_g, $t + 100);
        $this->assertSame('', $err);

        $station = Wanderer::LoadStation(1);
        $this->assertSame($target_g, (int)$station['g']);
        $this->assertEqualsWithDelta($fuel_before - $cost, (float)$station['deuterium'], 1.0);
        $this->assertGreaterThan($t + 100, (int)$station['cooldown_until']);
        $this->assertSame(1, (int)$station['jumps']);

        // The beacon row moved with the station.
        $beacon = $this->queryOne("SELECT g, s, p FROM {$db_prefix}planets WHERE planet_id = $beacon_id");
        $this->assertNotNull($beacon);
        $this->assertSame($target_g, (int)$beacon['g']);
        $this->assertSame((int)$station['s'], (int)$beacon['s']);
        $this->assertSame((int)$station['p'], (int)$beacon['p']);

        // Second jump during the cooldown is refused.
        $other = $target_g == 3 ? 1 : $target_g + 1;
        $this->assertSame('WANDERER_ERR_COOLDOWN', Wanderer::DoJump(1, $other, $t + 200));

        // After the cooldown, a jump without fuel is refused.
        $station = Wanderer::LoadStation(1);
        $station['deuterium'] = 0.0;
        $station['cooldown_until'] = 0;
        Wanderer::SaveStation($station);
        $this->assertSame('WANDERER_ERR_DEUT', Wanderer::DoJump(1, $other, $t + 100000));
    }

    public function testGuildExchange(): void
    {
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));
        $station = Wanderer::LoadStation(1);
        $galaxy = (int)$station['g'];

        // 100 metal -> crystal.
        $quote = Wanderer::GuildQuote($galaxy, GID_RC_METAL, 100.0, GID_RC_CRYSTAL, $station, $t);
        $this->assertGreaterThan(0, $quote);

        $metal_before = (float)$station['metal'];
        $crystal_before = (float)$station['crystal'];
        $err = Wanderer::GuildExchange(1, GID_RC_METAL, 100.0, GID_RC_CRYSTAL, $t);
        $this->assertSame('', $err);

        $station = Wanderer::LoadStation(1);
        $this->assertEqualsWithDelta($metal_before - 100, (float)$station['metal'], 0.01);
        $this->assertEqualsWithDelta($crystal_before + $quote, (float)$station['crystal'], 0.01);

        // Not enough metal.
        $this->assertSame('WANDERER_ERR_RES', Wanderer::GuildExchange(1, GID_RC_METAL, 999999.0, GID_RC_CRYSTAL, $t));

        // Cargo cap exceeded.
        $station = Wanderer::LoadStation(1);
        $cap = Wanderer::StationCargoCap($station);
        $station['crystal'] = $cap - 1;
        $station['deuterium'] = 50000.0;
        Wanderer::SaveStation($station);
        $this->assertSame('WANDERER_ERR_CAP', Wanderer::GuildExchange(1, GID_RC_DEUTERIUM, 40000.0, GID_RC_CRYSTAL, $t));
    }

    public function testOrdersBetweenStationAndClassicEmpire(): void
    {
        global $db_prefix;
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));

        // The wanderer offers crystal and wants metal.
        $station = Wanderer::LoadStation(1);
        $station['crystal'] = 5000.0;
        Wanderer::SaveStation($station);

        $err = Wanderer::PlaceOrder(1, GID_RC_CRYSTAL, 1000.0, GID_RC_METAL, 1500.0, $t);
        $this->assertSame('', $err);

        $row = $this->queryOne("SELECT order_id FROM {$db_prefix}wanderer_orders WHERE owner_id = 1");
        $order_id = (int)$row['order_id'];

        // Player 2 (classic empire) accepts the order with his planet.
        $planet2 = (int)$this->queryOne("SELECT planet_id FROM {$db_prefix}planets WHERE owner_id = 2 LIMIT 1")['planet_id'];
        dbquery("UPDATE {$db_prefix}planets SET `" . GID_RC_METAL . "` = 5000 WHERE planet_id = $planet2");
        $before = $this->queryOne("SELECT * FROM {$db_prefix}planets WHERE planet_id = $planet2");

        $err = Wanderer::AcceptOrder(2, $order_id, $t + 60);
        $this->assertSame('', $err);

        // The order is gone.
        $this->assertNull($this->queryOne("SELECT order_id FROM {$db_prefix}wanderer_orders WHERE order_id = $order_id"));

        // Player 2 paid 1500 metal and received 1000 crystal on the planet.
        // (GetUpdatePlanet may credit a tiny amount of natural production
        // between the reads, so a loose tolerance is used.)
        $after = $this->queryOne("SELECT * FROM {$db_prefix}planets WHERE planet_id = $planet2");
        $this->assertEqualsWithDelta((float)$before[GID_RC_METAL] - 1500.0, (float)$after[GID_RC_METAL], 25.0);
        $this->assertEqualsWithDelta((float)$before[GID_RC_CRYSTAL] + 1000.0, (float)$after[GID_RC_CRYSTAL], 25.0);

        // The wanderer paid 1000 crystal and received 1500 metal on the station.
        $station = Wanderer::LoadStation(1);
        $this->assertEqualsWithDelta(4000.0, (float)$station['crystal'], 0.01);
        $this->assertGreaterThanOrEqual(1500.0, (float)$station['metal']);

        // Both sides got a notice.
        $result = dbquery("SELECT msg_id FROM {$db_prefix}messages WHERE owner_id IN (1, 2) AND subj <> ''");
        $this->assertGreaterThanOrEqual(2, dbrows($result));
    }

    public function testOrderLimits(): void
    {
        global $db_prefix;
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));
        $station = Wanderer::LoadStation(1);
        $station['crystal'] = 500000.0;
        $station['metal'] = 500000.0;
        Wanderer::SaveStation($station);

        // Base slots of a station = 1.
        $this->assertSame('', Wanderer::PlaceOrder(1, GID_RC_CRYSTAL, 100.0, GID_RC_METAL, 100.0, $t));
        $this->assertSame('WANDERER_ERR_ORDER_LIMIT', Wanderer::PlaceOrder(1, GID_RC_CRYSTAL, 100.0, GID_RC_METAL, 100.0, $t + 10));

        // Cancel frees the slot.
        $row = $this->queryOne("SELECT order_id FROM {$db_prefix}wanderer_orders WHERE owner_id = 1");
        Wanderer::CancelOrder(1, (int)$row['order_id']);
        $this->assertSame('', Wanderer::PlaceOrder(1, GID_RC_CRYSTAL, 100.0, GID_RC_METAL, 100.0, $t + 20));
    }

    public function testOwnerCannotAcceptOwnOrder(): void
    {
        global $db_prefix;
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));
        $station = Wanderer::LoadStation(1);
        $station['crystal'] = 5000.0;
        Wanderer::SaveStation($station);

        Wanderer::PlaceOrder(1, GID_RC_CRYSTAL, 500.0, GID_RC_METAL, 500.0, $t);
        $row = $this->queryOne("SELECT order_id FROM {$db_prefix}wanderer_orders WHERE owner_id = 1");
        $this->assertSame('WANDERER_ERR_OWN_ORDER', Wanderer::AcceptOrder(1, (int)$row['order_id'], $t + 10));
    }

    // ========================================================================
    // Engine hooks added by the mod
    // ========================================================================

    public function testSkipPlanetUpdateFreezesTheEmpire(): void
    {
        global $db_prefix;
        $t = $this->t0();
        $planet1 = (int)$this->queryOne("SELECT planet_id FROM {$db_prefix}planets WHERE owner_id = 1 LIMIT 1")['planet_id'];

        // Before the mode switch the planet produces normally.
        $before = (float)$this->queryOne("SELECT `" . GID_RC_METAL . "` AS v FROM {$db_prefix}planets WHERE planet_id = $planet1")['v'];
        GetUpdatePlanet($planet1, $t + 3600);
        $after = (float)$this->queryOne("SELECT `" . GID_RC_METAL . "` AS v FROM {$db_prefix}planets WHERE planet_id = $planet1")['v'];
        $this->assertGreaterThan($before, $after);

        // Enter the wanderer mode: the same planet is now frozen.
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t + 7200));
        $frozen = (float)$this->queryOne("SELECT `" . GID_RC_METAL . "` AS v FROM {$db_prefix}planets WHERE planet_id = $planet1")['v'];
        GetUpdatePlanet($planet1, $t + 7200 + 3600);
        $still = (float)$this->queryOne("SELECT `" . GID_RC_METAL . "` AS v FROM {$db_prefix}planets WHERE planet_id = $planet1")['v'];
        $this->assertEqualsWithDelta($frozen, $still, 0.01);

        // The hook itself answers correctly.
        $p = array('owner_id' => 1, 'type' => PTYP_PLANET);
        $this->assertTrue($this->mod()->skip_planet_update($p));
        $p = array('owner_id' => 2, 'type' => PTYP_PLANET);
        $this->assertFalse($this->mod()->skip_planet_update($p));
        $p = array('owner_id' => USER_SPACE, 'type' => PTYP_PLANET);
        $this->assertFalse($this->mod()->skip_planet_update($p));
    }

    public function testFleetDispatchVeto(): void
    {
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));

        // A fleet cannot target the station or a wanderer's planet.
        $this->assertTrue($this->mod()->fleet_dispatch_veto(array('target' => array('type' => PTYP_WANDERER_STATION, 'owner_id' => 1))));
        $this->assertTrue($this->mod()->fleet_dispatch_veto(array('target' => array('type' => PTYP_PLANET, 'owner_id' => 1))));

        // A normal target of a classic player is fine.
        $this->assertFalse($this->mod()->fleet_dispatch_veto(array('target' => array('type' => PTYP_PLANET, 'owner_id' => 2))));

        // USER_SPACE objects (monsters etc.) are never vetoed by this rule.
        $this->assertFalse($this->mod()->fleet_dispatch_veto(array('target' => array('type' => 22849, 'owner_id' => USER_SPACE))));
    }

    public function testUpdateQueueHandlesTickEvent(): void
    {
        global $db_prefix;
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));

        $event = $this->queryOne("SELECT * FROM {$db_prefix}queue WHERE type = '" . QTYP_WANDERER_TICK . "' LIMIT 1");
        $this->assertNotNull($event);
        $end_before = (int)$event['end'];

        // The event end is before now so the tick fires.
        dbquery("UPDATE {$db_prefix}queue SET end = " . ($t - 10) . " WHERE task_id = " . (int)$event['task_id']);
        $event['end'] = $t - 10;

        $res = $this->mod()->update_queue($event);
        $this->assertTrue($res);

        $after = $this->queryOne("SELECT end FROM {$db_prefix}queue WHERE task_id = " . (int)$event['task_id']);
        $this->assertGreaterThan($t - 10, (int)$after['end']);
    }

    public function testExitModeAndUninstall(): void
    {
        global $db_prefix;
        $t = $this->t0();
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t));

        $station = Wanderer::LoadStation(1);
        $beacon_id = (int)$station['planet_id'];

        // A running build blocks the return.
        Wanderer::StartUpgrade(1, 'mod_mine_m', $t);
        $this->assertSame('WANDERER_ERR_BUSY', Wanderer::ExitWandererMode(1, $t + 10));

        // Finish the build and return.
        Wanderer::TickUser(1, $t + 10 * 3600);
        $this->assertSame('', Wanderer::ExitWandererMode(1, $t + 10 * 3600 + 1));

        $this->assertSame(0, $this->userMode(1));
        $this->assertNull($this->queryOne("SELECT planet_id FROM {$db_prefix}planets WHERE planet_id = $beacon_id"));
        $user = LoadUser(1);
        $this->assertGreaterThan(0, (int)$user['aktplanet']);

        // The station state is kept for the next journey.
        $this->assertNotNull(Wanderer::LoadStation(1));

        // Re-enter works (station is placed again).
        $this->assertSame('', Wanderer::EnterWandererMode(1, $t + 20 * 3600));
        $this->assertSame(1, $this->userMode(1));

        // Uninstall returns everyone and drops the mod data.
        $this->mod()->uninstall();
        $this->assertSame(0, $this->userMode(1));
        $this->assertNull($this->queryOne("SELECT * FROM {$db_prefix}wanderer_stations LIMIT 1"));
        $result = dbquery("SELECT task_id FROM {$db_prefix}queue WHERE type = '" . QTYP_WANDERER_TICK . "'");
        $this->assertSame(0, dbrows($result));
    }
}
