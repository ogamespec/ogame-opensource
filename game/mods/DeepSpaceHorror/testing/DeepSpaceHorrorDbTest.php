<?php

// Database-backed unit tests for the Deep Space Horror modification.
//
// These tests exercise the mod's DB-facing lifecycle: install()/uninstall()
// (table columns, spawned leviathans, portals and their fleets), the respawn
// queue event, the post-battle defender writeback and the trophy split among
// the defenders. They run each method in a separate PHP process so they never
// clash with the repository's own test helpers, and they live in the
// modification itself (game/mods/DeepSpaceHorror/testing) with their own suite.
//
// A minimal universe is built here (uni row + USER_SPACE user + planets) with
// the Deep Space Horror columns added through the mod's own install(), so the
// hooks have real data to read via LoadPlanetById() / LoadFleet().

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class DeepSpaceHorrorDbTest extends TestCase
{
    private ?DeepSpaceHorror $mod = null;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';

        if (!function_exists('dbquery')) {
            require_once __DIR__ . '/../../../../game/core/db.php';
        }

        if (!class_exists('DeepSpaceHorror')) {
            require_once __DIR__ . '/../main.php';
        }

        $this->mod = new DeepSpaceHorror();
        $this->buildMinimalDb();
    }

    /**
     * Return the mod instance (asserts it was initialized by setUp).
     */
    private function mod(): DeepSpaceHorror
    {
        $this->assertInstanceOf(DeepSpaceHorror::class, $this->mod);
        return $this->mod;
    }

    /**
     * Invoke a private mod method through reflection.
     */
    private function invokePrivate(string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod(DeepSpaceHorror::class, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->mod(), ...$args);
    }

    // ========================================================================
    // Minimal in-memory universe
    // ------------------------------------------------------------------------

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
        $GlobalUser = array('lang' => 'en', 'player_id' => 0);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit';
        $_SERVER['REQUEST_URI'] = '/';

        if (!isset($Languages)) $Languages = array('en' => 'English', 'ru' => 'Русский', 'de' => 'Deutsch');
        if (!isset($DefaultLanguage)) $DefaultLanguage = 'en';
        if (!isset($loca_lang)) $loca_lang = 'en';
        if (!isset($LOCA)) $LOCA = array();

        InitDB();
        CreateDBTables();

        // Universe row.
        $now = time();
        AddDBRow(array(
            'num' => 1, 'speed' => 1.0, 'fspeed' => 1.0, 'galaxies' => 9, 'systems' => 499,
            'maxusers' => 1000, 'acs' => 1, 'fid' => 30, 'did' => 30, 'rapid' => 1, 'moons' => 1,
            'defrepair' => 0, 'defrepair_delta' => 0, 'usercount' => 2, 'freeze' => 0,
            'startdate' => $now - 86400, 'battle_engine' => 'php', 'lang' => 'en', 'hacks' => 0,
            'php_battle' => 1, 'force_lang' => 0, 'start_dm' => 0, 'max_werf' => 1000,
            'feedage' => 0, 'modlist' => '',
        ), 'uni');
        $GlobalUni = LoadUniverse();

        // Technical account that owns the leviathans and their fleets.
        AddDBRow(array(
            'player_id' => USER_SPACE, 'regdate' => $now, 'name' => 'space', 'oname' => 'space',
            'lang' => 'en', 'dm' => 0, 'dmfree' => 0, 'score1' => 0, 'score2' => 0, 'score3' => 0,
            'maxspy' => 0, 'hplanetid' => 0, 'flags' => 0, 'feedid' => '', 'lastfeed' => 0,
            'com_until' => 0, 'adm_until' => 0, 'eng_until' => 0, 'geo_until' => 0, 'tec_until' => 0,
        ), 'users');
    }

    private function addPlayer(int $playerId): void
    {
        global $db_prefix;
        $result = dbquery("SELECT player_id FROM {$db_prefix}users WHERE player_id = $playerId");
        if (dbrows($result) > 0) return;
        $now = time();
        AddDBRow(array(
            'player_id' => $playerId, 'regdate' => $now, 'name' => "p$playerId", 'oname' => "Player $playerId",
            'lang' => 'en', 'dm' => 0, 'dmfree' => 0, 'score1' => 0, 'score2' => 0, 'score3' => 0,
            'maxspy' => 0, 'hplanetid' => 0, 'flags' => 0, 'feedid' => '', 'lastfeed' => 0,
            'com_until' => 0, 'adm_until' => 0, 'eng_until' => 0, 'geo_until' => 0, 'tec_until' => 0,
            GID_R_WEAPON => 0, GID_R_SHIELD => 0, GID_R_ARMOUR => 0,
        ), 'users');
    }

    /**
     * Add a plain player planet (1:1:1 etc.) and return its id.
     */
    private function addPlanet(int $ownerId, array $extra = array()): int
    {
        $row = array(
            'name' => "Planet $ownerId", 'type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 1,
            'owner_id' => $ownerId, 'diameter' => 10000, 'temp' => 20, 'fields' => 10, 'maxfields' => 10,
            'date' => time(), 'lastpeek' => time(), 'lastakt' => time(),
            'gate_until' => 0, 'remove' => 0,
        ) + $extra;
        return AddDBRow($row, 'planets');
    }

    private function countPlanetsByType(int $type): int
    {
        global $db_prefix;
        $result = dbquery("SELECT planet_id FROM {$db_prefix}planets WHERE type = $type");
        return dbrows($result);
    }

    private function countFleetMissions(): int
    {
        global $db_prefix;
        $result = dbquery("SELECT fleet_id FROM {$db_prefix}fleet WHERE mission = " . FTYP_LEVI_PREPARE_JUMP);
        return dbrows($result);
    }

    private function countRespawnEvents(): int
    {
        global $db_prefix;
        $result = dbquery("SELECT task_id FROM {$db_prefix}queue WHERE type = '" . QTYP_LEVI_RESPAWN . "'");
        return dbrows($result);
    }

    // ========================================================================
    // install() / uninstall() lifecycle
    // ------------------------------------------------------------------------

    public function testInstallCreatesLeviathansPortalsAndFleets(): void
    {
        $this->mod()->install();

        // One planet per creature + one portal per creature.
        $this->assertSame(1, $this->countPlanetsByType(PTYP_LEVI_AMOEBA));
        $this->assertSame(1, $this->countPlanetsByType(PTYP_LEVI_GUARDIAN));
        $this->assertSame(1, $this->countPlanetsByType(PTYP_LEVI_JUGGERNAUT));
        $this->assertSame(3, $this->countPlanetsByType(PTYP_LEVI_PORTAL));

        // Three "prepare jump" fleets owned by the space account.
        $this->assertSame(3, $this->countFleetMissions());

        global $db_prefix;
        // The fleet table gained a column for each creature.
        $result = dbquery("SELECT * FROM {$db_prefix}fleet WHERE mission = " . FTYP_LEVI_PREPARE_JUMP . " AND `" . GID_LEVI_AMOEBA . "` > 0");
        $this->assertSame(1, dbrows($result));
        $result = dbquery("SELECT * FROM {$db_prefix}fleet WHERE mission = " . FTYP_LEVI_PREPARE_JUMP . " AND `" . GID_LEVI_GUARDIAN . "` > 0");
        $this->assertSame(1, dbrows($result));
        $result = dbquery("SELECT * FROM {$db_prefix}fleet WHERE mission = " . FTYP_LEVI_PREPARE_JUMP . " AND `" . GID_LEVI_JUGGERNAUT . "` > 0");
        $this->assertSame(1, dbrows($result));

        // No respawn events scheduled by a fresh install.
        $this->assertSame(0, $this->countRespawnEvents());
    }

    public function testUninstallRemovesEverythingAndAllowsReinstall(): void
    {
        $this->mod()->install();
        $this->mod()->uninstall();

        $this->assertSame(0, $this->countPlanetsByType(PTYP_LEVI_AMOEBA));
        $this->assertSame(0, $this->countPlanetsByType(PTYP_LEVI_GUARDIAN));
        $this->assertSame(0, $this->countPlanetsByType(PTYP_LEVI_JUGGERNAUT));
        $this->assertSame(0, $this->countPlanetsByType(PTYP_LEVI_PORTAL));
        $this->assertSame(0, $this->countFleetMissions());
        $this->assertSame(0, $this->countRespawnEvents());

        // The columns were dropped, so a second install works cleanly.
        $this->mod()->install();
        $this->assertSame(1, $this->countPlanetsByType(PTYP_LEVI_AMOEBA));
        $this->assertSame(3, $this->countFleetMissions());
        $this->mod()->uninstall();
        $this->assertSame(0, $this->countPlanetsByType(PTYP_LEVI_AMOEBA));
    }

    public function testUninstallCleansRespawnEvents(): void
    {
        $this->mod()->install();
        $this->invokePrivate('ScheduleRespawn', array(GID_LEVI_AMOEBA, time()));
        $this->assertSame(1, $this->countRespawnEvents());

        $this->mod()->uninstall();
        $this->assertSame(0, $this->countRespawnEvents());
    }

    // ========================================================================
    // Respawn scheduling
    // ------------------------------------------------------------------------

    public function testScheduleRespawnCreatesSingleEventInDelayBounds(): void
    {
        global $db_prefix;
        $this->mod()->install();

        $when = 1700000000;
        $this->invokePrivate('ScheduleRespawn', array(GID_LEVI_AMOEBA, $when));
        $this->assertSame(1, $this->countRespawnEvents());

        $result = dbquery("SELECT * FROM {$db_prefix}queue WHERE type = '" . QTYP_LEVI_RESPAWN . "'");
        $event = dbarray($result);
        $this->assertSame(USER_SPACE, (int)$event['owner_id']);
        $this->assertSame(PTYP_LEVI_AMOEBA, (int)$event['obj_id']);

        $delay = (int)$event['end'] - (int)$event['start'];
        $this->assertGreaterThanOrEqual(LEVI_RESPAWN_MIN_SECONDS, $delay);
        $this->assertLessThanOrEqual(LEVI_RESPAWN_MAX_SECONDS, $delay);

        // Scheduling again must not create a duplicate event.
        $this->invokePrivate('ScheduleRespawn', array(GID_LEVI_AMOEBA, $when + 60));
        $this->assertSame(1, $this->countRespawnEvents());
    }

    public function testUpdateQueueRespawnsKilledLeviathan(): void
    {
        global $db_prefix;
        $this->mod()->install();

        // Simulate a dead Guardian: its planet and portal are gone.
        dbquery("DELETE FROM {$db_prefix}planets WHERE type IN (" . PTYP_LEVI_GUARDIAN . ", " . PTYP_LEVI_PORTAL . ")");
        $this->assertSame(0, $this->countPlanetsByType(PTYP_LEVI_GUARDIAN));

        // The respawn event fires via the mod's update_queue hook.
        $this->invokePrivate('ScheduleRespawn', array(GID_LEVI_GUARDIAN, time() - 60));

        $result = dbquery("SELECT * FROM {$db_prefix}queue WHERE type = '" . QTYP_LEVI_RESPAWN . "'");
        $event = dbarray($result);

        $res = $this->mod()->update_queue($event);
        $this->assertTrue($res, 'the mod must acknowledge its own queue event');
        $this->assertSame(1, $this->countPlanetsByType(PTYP_LEVI_GUARDIAN));
        $this->assertSame(0, $this->countRespawnEvents());
    }

    public function testUpdateQueueKeepsLivingLeviathan(): void
    {
        global $db_prefix;
        $this->mod()->install();

        // A respawn event for a still-living creature must not duplicate it.
        $this->invokePrivate('ScheduleRespawn', array(GID_LEVI_AMOEBA, time() - 60));
        $result = dbquery("SELECT * FROM {$db_prefix}queue WHERE type = '" . QTYP_LEVI_RESPAWN . "'");
        $event = dbarray($result);

        $before = $this->countPlanetsByType(PTYP_LEVI_AMOEBA);
        $this->assertSame(1, $before);

        $res = $this->mod()->update_queue($event);
        $this->assertTrue($res);
        $this->assertSame($before, $this->countPlanetsByType(PTYP_LEVI_AMOEBA));
        $this->assertSame(0, $this->countRespawnEvents());
    }

    public function testUpdateQueueIgnoresForeignEvents(): void
    {
        $queue = array('type' => 'SomethingElse', 'task_id' => 1);
        $res = $this->mod()->update_queue($queue);
        $this->assertFalse($res);
    }

    // ========================================================================
    // Post-battle defender writeback
    // ------------------------------------------------------------------------

    public function testLeviathanWritebackAppliesSurvivorsAndRepair(): void
    {
        $this->addPlayer(1);
        $planetId = $this->addPlanet(1, array(GID_F_LF => 100, GID_D_RL => 50));

        // Fake battle result: the defenders end the last round with 40 LF and
        // 20 RL; 5 destroyed RL are repaired by RepairDefense.
        $res = array(
            'rounds' => array(
                array(
                    'attackers' => array(),
                    'defenders' => array(
                        0 => array(
                            'pf' => BATTLE_PTCP_PLANET,
                            'id' => $planetId,
                            'units' => array(GID_F_LF => 40, GID_D_RL => 20),
                        ),
                    ),
                ),
            ),
        );
        $repaired = array(0 => array(GID_D_RL => 5));

        $this->invokePrivate('LeviathanWriteback', array(array(), $res, $repaired));

        global $db_prefix;
        $result = dbquery("SELECT * FROM {$db_prefix}planets WHERE planet_id = $planetId");
        $planet = dbarray($result);
        $this->assertSame(40, (int)$planet[GID_F_LF]);
        $this->assertSame(25, (int)$planet[GID_D_RL]);      // 20 survivors + 5 repaired
        $this->assertSame(0, (int)$planet[GID_F_CRUISER]);
    }

    public function testUpdateDefenderActivityTouchesPlanets(): void
    {
        $this->addPlayer(1);
        $planetId = $this->addPlanet(1);

        $d = array(
            0 => array('pf' => BATTLE_PTCP_PLANET, 'id' => $planetId),
            1 => array('pf' => BATTLE_PTCP_FLEET, 'id' => 999),
        );
        $when = 1700000000;
        $this->invokePrivate('UpdateDefenderActivity', array($d, $when));

        global $db_prefix;
        $result = dbquery("SELECT lastakt FROM {$db_prefix}planets WHERE planet_id = $planetId");
        $row = dbarray($result);
        $this->assertSame($when, (int)$row['lastakt']);
    }

    // ========================================================================
    // End-to-end: real battle through the engine (nerfed Guardian)
    // ------------------------------------------------------------------------

    public function testGuardianKillDistributesLootAndSchedulesRespawn(): void
    {
        global $db_prefix, $UnitParam, $fleetmap;
        $this->mod()->install();

        // The Guardian starts at 1:1:1 and its first portal is at 1:1:2. Put a
        // defender planet at 1:1:3 (inside the P +- 2 blast radius).
        $this->addPlayer(1);
        $defPlanetId = $this->addPlanet(1, array(
            'g' => 1, 's' => 1, 'p' => 3,
            GID_F_LF => 20,
            GID_RC_CRYSTAL => 0,
        ));

        // Make the Guardian weak enough that 20 light fighters can kill it in a
        // couple of engine rounds (the real stats would make the test huge).
        $UnitParam[GID_LEVI_GUARDIAN] = array(1000, 0, 1, 0, 500, 0);

        // Take the Guardian's actual fleet (mission "prepare jump") and its
        // origin planet / portal from the database.
        $result = dbquery("SELECT * FROM {$db_prefix}fleet WHERE mission = " . FTYP_LEVI_PREPARE_JUMP . " AND `" . GID_LEVI_GUARDIAN . "` > 0");
        $fleetRow = dbarray($result);
        $this->assertNotNull($fleetRow, 'the guardian fleet must exist after install');

        $origin = LoadPlanetById((int)$fleetRow['start_planet']);
        $portal = LoadPlanetById((int)$fleetRow['target_planet']);
        $this->assertNotNull($origin);
        $this->assertNotNull($portal);

        $fleet = array();
        foreach ($fleetmap as $gid) {
            $fleet[$gid] = isset($fleetRow[$gid]) ? (int)$fleetRow[$gid] : 0;
        }

        $queue = array('end' => time());
        $when = (int)$queue['end'];

        // A player attack fleet heading to the Guardian's planet must be turned
        // back when the monster's corpse disappears.
        $playerFleetId = AddDBRow(array(
            'owner_id' => 1, 'union_id' => 0, 'fuel' => 0, 'mission' => FTYP_ATTACK,
            'start_planet' => $defPlanetId, 'target_planet' => (int)$origin['planet_id'],
            'flight_time' => 3600, 'deploy_time' => 0,
            GID_F_LF => 5,
        ), 'fleet');
        AddQueue(1, 'Fleet', $playerFleetId, 0, 0, $when - 1000, 2000);

        // Simulate the fleet arriving at the portal: the battle runs, the
        // defenders win, the corpse is removed and a respawn is scheduled.
        //
        // Note: the core battle report persistence embeds backslash-escaped
        // quotes into the UPDATE (MySQL syntax), so on the SQLite backend the
        // battledata row update is echoed as an error and skipped. That is a
        // pre-existing SQLite-only artifact of the report writer and does not
        // affect the mechanics under test here.
        $this->invokePrivate('LeviathanArrive', array($queue, $fleetRow, $fleet, $origin, $portal));

        // The inbound player fleet was recalled (mission flipped to "return").
        $result = dbquery("SELECT fleet_id FROM {$db_prefix}fleet WHERE owner_id = 1 AND mission = " . (FTYP_ATTACK + FTYP_RETURN));
        $this->assertSame(1, dbrows($result));

        // The monster is gone and its respawn is queued.
        $this->assertSame(0, $this->countPlanetsByType(PTYP_LEVI_GUARDIAN));
        $this->assertSame(1, $this->countRespawnEvents());
        $result = dbquery("SELECT obj_id FROM {$db_prefix}queue WHERE type = '" . QTYP_LEVI_RESPAWN . "'");
        $event = dbarray($result);
        $this->assertSame(PTYP_LEVI_GUARDIAN, (int)$event['obj_id']);

        // The defender kept its 20 light fighters (battle writeback)...
        $result = dbquery("SELECT * FROM {$db_prefix}planets WHERE planet_id = $defPlanetId");
        $planet = dbarray($result);
        $this->assertSame(20, (int)$planet[GID_F_LF]);

        // ...and received the Guardian's whole crystal trophy (sole defender).
        $this->assertEqualsWithDelta(LEVI_LOOT_GUARDIAN_CRYSTAL, (float)$planet[GID_RC_CRYSTAL], 0.001);

        // The defender got a battle report and a trophy notification.
        $result = dbquery("SELECT subj FROM {$db_prefix}messages WHERE owner_id = 1");
        $this->assertGreaterThanOrEqual(2, dbrows($result));
        $subjects = '';
        while ($row = dbarray($result)) {
            $subjects .= ' ' . (string)$row['subj'];
        }
        $this->assertStringContainsString('Trophy', $subjects);
    }

    // ========================================================================
    // Trophy split
    // ------------------------------------------------------------------------

    private function loadDefender(int $playerId, int $planetId, array $units): array
    {
        $user = LoadUser($playerId);
        $this->assertNotNull($user, "player $playerId must exist");
        $user['units'] = $units;
        $user['g'] = 1;
        $user['s'] = 1;
        $user['p'] = 1;
        $user['id'] = $planetId;
        $user['pf'] = BATTLE_PTCP_PLANET;
        $user['points'] = $user['fpoints'] = 0;
        return $user;
    }

    public function testGrantLeviathanLootSplitsByContribution(): void
    {
        $this->addPlayer(1);
        $this->addPlayer(2);
        $p1 = $this->addPlanet(1);
        $p2 = $this->addPlanet(2);

        // p1: 100 light fighters (attack 50 each => 5000), p2: 300 (=> 15000).
        // 40000000 metal split 1:3 => 10M / 30M.
        $d = array(
            0 => $this->loadDefender(1, $p1, array(GID_F_LF => 100)),
            1 => $this->loadDefender(2, $p2, array(GID_F_LF => 300)),
        );

        $portal = array('g' => 1, 's' => 1, 'p' => 1);
        $this->invokePrivate('GrantLeviathanLoot', array(array(GID_RC_METAL => 40000000), $d, $portal, time()));

        global $db_prefix;
        $result = dbquery("SELECT `" . GID_RC_METAL . "` FROM {$db_prefix}planets WHERE planet_id = $p1");
        $row = dbarray($result);
        $this->assertEqualsWithDelta(10000000, (float)$row[GID_RC_METAL], 0.001);

        $result = dbquery("SELECT `" . GID_RC_METAL . "` FROM {$db_prefix}planets WHERE planet_id = $p2");
        $row = dbarray($result);
        $this->assertEqualsWithDelta(30000000, (float)$row[GID_RC_METAL], 0.001);

        // Each participating player received a personal notification.
        $result = dbquery("SELECT msg_id FROM {$db_prefix}messages WHERE owner_id IN (1, 2)");
        $this->assertSame(2, dbrows($result));
    }

    public function testGrantLeviathanLootGivesRemainderToTopContributor(): void
    {
        $this->addPlayer(1);
        $this->addPlayer(2);
        $p1 = $this->addPlanet(1);
        $p2 = $this->addPlanet(2);

        // Equal weights (100 LF each), odd total: one unit of metal must not be lost.
        $d = array(
            0 => $this->loadDefender(1, $p1, array(GID_F_LF => 100)),
            1 => $this->loadDefender(2, $p2, array(GID_F_LF => 100)),
        );

        $portal = array('g' => 1, 's' => 1, 'p' => 1);
        $this->invokePrivate('GrantLeviathanLoot', array(array(GID_RC_METAL => 40000001), $d, $portal, time()));

        global $db_prefix;
        $total = 0;
        foreach (array($p1, $p2) as $pid) {
            $result = dbquery("SELECT `" . GID_RC_METAL . "` FROM {$db_prefix}planets WHERE planet_id = $pid");
            $row = dbarray($result);
            $total += (float)$row[GID_RC_METAL];
        }
        $this->assertEqualsWithDelta(40000001, $total, 0.001);
    }

    public function testGrantLeviathanLootSkipsEmptyDefenders(): void
    {
        $this->addPlayer(1);
        $this->addPlayer(2);
        $p1 = $this->addPlanet(1);
        $p2 = $this->addPlanet(2);

        // p2 defends without any ships/defense: it contributed nothing and gets nothing.
        $d = array(
            0 => $this->loadDefender(1, $p1, array(GID_F_LF => 100)),
            1 => $this->loadDefender(2, $p2, array()),
        );

        $portal = array('g' => 1, 's' => 1, 'p' => 1);
        $this->invokePrivate('GrantLeviathanLoot', array(array(GID_RC_METAL => 40000000), $d, $portal, time()));

        global $db_prefix;
        $result = dbquery("SELECT `" . GID_RC_METAL . "` FROM {$db_prefix}planets WHERE planet_id = $p1");
        $row = dbarray($result);
        $this->assertEqualsWithDelta(40000000, (float)$row[GID_RC_METAL], 0.001);

        $result = dbquery("SELECT `" . GID_RC_METAL . "` FROM {$db_prefix}planets WHERE planet_id = $p2");
        $row = dbarray($result);
        $this->assertEqualsWithDelta(0, (float)$row[GID_RC_METAL], 0.001);

        // Only the contributing player was notified.
        $result = dbquery("SELECT msg_id FROM {$db_prefix}messages WHERE owner_id = 1");
        $this->assertSame(1, dbrows($result));
    }
}
