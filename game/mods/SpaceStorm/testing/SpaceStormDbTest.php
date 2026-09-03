<?php

// Database-backed unit tests for the Space Storm modification.
//
// These tests exercise the hooks that read planet / fleet / queue state from
// the in-memory SQLite backend. They run each method in a separate PHP process
// so they never clash with the repository's own test helpers, and they live in
// the modification itself (game/mods/SpaceStorm/testing) with their own suite.
//
// A minimal universe is built here (uni row + planets + optional fleet/queue
// rows) with the Space Storm columns added, so the hooks have real data to
// read via LoadPlanetById() / LoadFleet() / GetStormQueue().

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class SpaceStormDbTest extends TestCase
{
    private ?SpaceStorm $mod = null;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';

        if (!function_exists('dbquery')) {
            require_once __DIR__ . '/../../../../game/core/db.php';
        }

        if (!class_exists('SpaceStorm')) {
            require_once __DIR__ . '/../main.php';
        }

        $this->mod = new SpaceStorm();
        $this->buildMinimalDb();
    }

    /**
     * Return the mod instance (asserts it was initialized by setUp).
     */
    private function mod(): SpaceStorm
    {
        $this->assertInstanceOf(SpaceStorm::class, $this->mod);
        return $this->mod;
    }

    private function buildMinimalDb(): void
    {
        global $db_prefix, $db_name, $db_host, $db_user, $db_pass;
        global $Languages, $DefaultLanguage, $loca_lang, $StartPage, $from_cron, $UserCache, $LOCA;

        $db_prefix = 'test_';
        $db_name = 'test';
        $db_host = '';
        $db_user = '';
        $db_pass = '';
        $StartPage = 'index.php';
        $from_cron = false;
        $UserCache = array();

        if (!isset($Languages)) $Languages = array('en' => 'English', 'ru' => 'Русский', 'de' => 'Deutsch');
        if (!isset($DefaultLanguage)) $DefaultLanguage = 'en';
        if (!isset($loca_lang)) $loca_lang = 'en';
        if (!isset($LOCA)) $LOCA = array();

        InitDB();
        CreateDBTables();

        dbquery("ALTER TABLE {$db_prefix}uni ADD COLUMN storm INT DEFAULT 0");
        dbquery("ALTER TABLE {$db_prefix}planets ADD COLUMN `" . GID_B_REALITY_STAB . "` INT DEFAULT 0");
        dbquery("ALTER TABLE {$db_prefix}planets ADD COLUMN `s" . GID_B_REALITY_STAB . "` INT DEFAULT 0");

        // Universe row.
        $now = time();
        AddDBRow(array(
            'num' => 1, 'speed' => 1.0, 'fspeed' => 1.0, 'galaxies' => 1, 'systems' => 15,
            'maxusers' => 1000, 'acs' => 1, 'rapid' => 0, 'moons' => 1, 'defrepair' => 0,
            'defrepair_delta' => 0, 'usercount' => 1, 'freeze' => 0, 'startdate' => $now - 86400,
            'battle_engine' => 'php', 'lang' => 'en', 'hacks' => 0, 'php_battle' => 1,
            'force_lang' => 0, 'start_dm' => 0, 'max_werf' => 1000, 'feedage' => 0,
            'modlist' => '',
            'ext_board' => '', 'ext_discord' => '', 'ext_tutorial' => '', 'ext_rules' => '', 'ext_impressum' => '',
        ), 'uni');
    }

    private function setStorm(int $mask): void
    {
        global $GlobalUni;
        if (!is_array($GlobalUni)) $GlobalUni = array();
        $GlobalUni['storm'] = $mask;
        if (!isset($GlobalUni['lang'])) $GlobalUni['lang'] = 'en';
    }

    private function addPlanet(int $level = 0, int $mask = 0, array $extra = array()): int
    {
        // Use union (+) instead of array_merge: array_merge renumbers integer keys
        // (GID_B_REALITY_STAB => level would become 0 => level).
        $row = array(
            'name' => 'TestPlanet', 'type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 1,
            'owner_id' => 1, 'diameter' => 10000, 'temp' => 20, 'fields' => 10, 'maxfields' => 10,
            'date' => time(), 'lastpeek' => time(),
            GID_B_REALITY_STAB => $level,
            's' . GID_B_REALITY_STAB => $mask,
        ) + $extra;
        return AddDBRow($row, 'planets');
    }

    // ========================================================================
    // battle_post_process -- Attack Reverberation (deterministic)
    // ------------------------------------------------------------------------

    public function testBattlePostProcessAttackReverbLoss(): void
    {
        $this->setStorm(SPACE_STORM_MASK_ATTACK_REVERB);
        $planetId = $this->addPlanet(0, 0);

        $res = array(
            'result' => 'awon',
            'before' => array(
                'attackers' => array(array('id' => 1)),
                'defenders' => array(array('id' => $planetId, 'pf' => BATTLE_PTCP_PLANET)),
            ),
            'rounds' => array(
                array('attackers' => array(array('units' => array(GID_F_LF => 100))), 'defenders' => array()),
            ),
            'extra' => array(),
        );

        $this->mod()->battle_post_process($res);

        // 5% loss => ceil(100*0.95) = 95.
        $this->assertSame(95, $res['rounds'][0]['attackers'][0]['units'][GID_F_LF]);
        $this->assertNotEmpty($res['extra']);
        $this->assertStringContainsString('5', $res['extra'][0]);
    }

    public function testBattlePostProcessAttackReverbStabilizerCounter(): void
    {
        $this->setStorm(SPACE_STORM_MASK_ATTACK_REVERB);
        // Level-10 stabilizer imprinted with Attack Reverb: loss becomes 0.05-0.004*10=0.01.
        $planetId = $this->addPlanet(10, SPACE_STORM_MASK_ATTACK_REVERB);

        $res = array(
            'result' => 'awon',
            'before' => array(
                'attackers' => array(array('id' => 1)),
                'defenders' => array(array('id' => $planetId, 'pf' => BATTLE_PTCP_PLANET)),
            ),
            'rounds' => array(
                array('attackers' => array(array('units' => array(GID_F_LF => 100))), 'defenders' => array()),
            ),
            'extra' => array(),
        );

        $this->mod()->battle_post_process($res);

        // 1% loss => ceil(100*0.99) = 99.
        $this->assertSame(99, $res['rounds'][0]['attackers'][0]['units'][GID_F_LF]);
    }

    // ========================================================================
    // battle_unit_stats -- per-planet stabilizer counter (deterministic)
    // ------------------------------------------------------------------------

    public function testBattleUnitStatsPolarStabilizerCounter(): void
    {
        $this->setStorm(SPACE_STORM_MASK_POLAR_SHIELD);
        // Level-10 stabilizer imprinted with Polar Shield Distortion:
        // armor factor = 0.8 + 0.03*10 = 1.1 ; shield factor = 1.3 - 0.3 = 1.0.
        $planetId = $this->addPlanet(10, SPACE_STORM_MASK_POLAR_SHIELD);

        $unit = array(GID_F_LF => array(4000, 10, 5, 5000, 5000, 10));
        $args = array(
            'attackers' => array(),
            'defenders' => array(array('id' => $planetId, 'pf' => BATTLE_PTCP_PLANET)),
        );

        $this->mod()->battle_unit_stats($args, $unit);

        $this->assertEqualsWithDelta(4000 * 1.1, $unit[GID_F_LF][0], 1e-6);
        $this->assertEqualsWithDelta(10 * 1.0, $unit[GID_F_LF][1], 1e-6);
    }

    // ========================================================================
    // prod_post_process -- Matter Signature conversion (deterministic)
    // ------------------------------------------------------------------------

    private function addStormQueueEvent(int $objId): void
    {
        global $db_prefix;
        AddDBRow(array(
            'owner_id' => USER_SPACE, 'type' => 'SpaceStorm', 'sub_id' => 0, 'obj_id' => $objId,
            'level' => 0, 'start' => time(), 'end' => time() + 3600, 'prio' => 0,
        ), 'queue');
    }

    public function testProdPostProcessMatterSignatureConversion(): void
    {
        global $resourcesWithNonZeroDerivative;
        $this->setStorm(SPACE_STORM_MASK_MATTER_SIGNATURE);
        $this->addStormQueueEvent(GID_RC_CRYSTAL);
        $planetId = $this->addPlanet(0, 0);

        $planet = $this->loadPlanetById($planetId);
        $eco = array(
            'net_prod' => array(GID_RC_METAL => 1000, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0),
            'balance' => array(GID_RC_METAL => 1000, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0),
        );

        $this->mod()->prod_post_process($planet, $eco);

        // 20% of 1000 metal converted into crystal => 200 crystal, 800 metal.
        $this->assertEqualsWithDelta(200, $eco['net_prod'][GID_RC_CRYSTAL], 1e-6);
        $this->assertEqualsWithDelta(800, $eco['net_prod'][GID_RC_METAL], 1e-6);
        $this->assertEqualsWithDelta(200, $eco['balance'][GID_RC_CRYSTAL], 1e-6);
    }

    private function loadPlanetById(int $id): array
    {
        global $db_prefix;
        $result = dbquery("SELECT * FROM {$db_prefix}planets WHERE planet_id = $id");
        return dbarray($result);
    }

    // ========================================================================
    // FreezeRandomQueue / unfreeze -- Energy Collapse freezing (with 1 task)
    // ------------------------------------------------------------------------

    public function testFreezeRandomQueueFreezesBuild(): void
    {
        global $db_prefix;
        $this->setStorm(SPACE_STORM_MASK_ENERGY_COLLAPSE);
        $planetId = $this->addPlanet(0, 0);

        // A single building queue entry => only one task to freeze.
        $bqId = AddDBRow(array(
            'owner_id' => 1, 'planet_id' => $planetId, 'list_id' => 1, 'tech_id' => GID_B_METAL_MINE,
            'level' => 6, 'destroy' => 0, 'start' => time() - 60, 'end' => time() + 600,
        ), 'buildqueue');
        AddDBRow(array(
            'owner_id' => 1, 'type' => 'Build', 'sub_id' => $bqId, 'obj_id' => GID_B_METAL_MINE,
            'level' => 6, 'start' => time() - 60, 'end' => time() + 600, 'prio' => 0,
        ), 'queue');

        // Invoke the (private) freeze helper through reflection.
        $method = new ReflectionMethod(SpaceStorm::class, 'FreezeRandomQueue');
        $method->setAccessible(true);
        $method->invoke($this->mod(), $planetId);

        $result = dbquery("SELECT freeze FROM {$db_prefix}queue WHERE type = 'Build'");
        $row = dbarray($result);
        $this->assertSame(1, (int)$row['freeze']);
    }

    public function testEnergyCollapseAutoUnfreezeWhenInactive(): void
    {
        global $db_prefix;
        // When the Energy Collapse is not active, any frozen build/research
        // must be resumed automatically (energy is back to normal).
        $this->setStorm(0);
        $planetId = $this->addPlanet(0, 0);
        $this->addFrozenBuild($planetId);

        $method = new ReflectionMethod(SpaceStorm::class, 'EnergyCollapseTick');
        $method->setAccessible(true);
        $method->invoke($this->mod());

        $result = dbquery("SELECT freeze FROM {$db_prefix}queue WHERE type = 'Build'");
        $row = dbarray($result);
        $this->assertSame(0, (int)$row['freeze']);
    }

    private function addFrozenBuild(int $planetId): int
    {
        $bqId = AddDBRow(array(
            'owner_id' => 1, 'planet_id' => $planetId, 'list_id' => 1, 'tech_id' => GID_B_METAL_MINE,
            'level' => 6, 'destroy' => 0, 'start' => time() - 60, 'end' => time() + 600,
        ), 'buildqueue');
        return AddDBRow(array(
            'owner_id' => 1, 'type' => 'Build', 'sub_id' => $bqId, 'obj_id' => GID_B_METAL_MINE,
            'level' => 6, 'start' => time() - 60, 'end' => time() + 600, 'prio' => 0,
            'freeze' => 1, 'frozen' => time(),
        ), 'queue');
    }
}
