<?php

declare(strict_types=1);

// Unit tests for five game core modules:
//
//   game/core/techs.php         - the game object definitions (maps, level-1
//                                 costs, combat stats, requirements, rapid fire,
//                                 planet build restrictions, resource groups)
//   game/core/install_tabs.php  - the database table schemas
//   game/core/db.php            - the database layer facade (backend selection
//                                 and CreateDBTables)
//   game/core/mods.php          - the mod system (discovery, manifests,
//                                 hook dispatchers, install/remove/reorder)
//   game/core/coupon.php        - coupon handling (master database API)
//
// The tests run against the real game core loaded by testing/bootstrap.php
// with the in-memory SQLite backend (DB_CONNECTION=sqlite,
// DB_DATABASE=:memory:, see phpunit.xml), so no MySQL server and no mock DB
// functions are needed. Each test method runs in a separate PHP process:
// techs.php and mods.php assign their global tables ($buildmap, $initial,
// $UnitParam, $modlist, ...) at the top level of the file, and only the
// process-isolated child loads the bootstrap at the true top level (the same
// reason RocketAttackTest and GoldenPagesTest are process-isolated).
//
// Limitation: the master database (MDBConnect/MDBQuery/MDBArray/MDBRows) is a
// MySQL-only feature that always reports "no connection" in the SQLite
// backend, so the coupon API can only be tested on its "no master database"
// path here (see the coupon tests below).

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Test mod used to drive the ModsExec* hook dispatchers.
 *
 * Every hook records its call in the static $calls list (so the test can check
 * the call order and the short-circuit behaviour) and writes a marker into the
 * argument it receives, so the test can prove whether the argument was passed
 * by reference or by value.
 *
 * $handles tells the hook whether to report "handled" (the dispatchers stop at
 * the first mod that does).
 */
class ModsTechsInstallDbCoreTestMod extends GameMod
{
    /** @var string[] Names of all hook calls, in call order (shared by every instance). */
    public static array $calls = array ();

    private string $label;
    private bool $handles;

    public function __construct(string $label = 'mod', bool $handles = false)
    {
        $this->label = $label;
        $this->handles = $handles;
    }

    public function install() : void { self::$calls[] = $this->label . ':install'; }
    public function uninstall() : void { self::$calls[] = $this->label . ':uninstall'; }
    public function init() : void { self::$calls[] = $this->label . ':init'; }

    public function begin_content() : bool
    {
        self::$calls[] = $this->label . ':begin_content';
        return $this->handles;
    }

    public function end_content() : bool
    {
        self::$calls[] = $this->label . ':end_content';
        return false;
    }

    public function add_bonuses (array &$bonuses) : bool
    {
        self::$calls[] = $this->label . ':add_bonuses';
        $bonuses[$this->label] = true;
        return $this->handles;
    }

    public function install_tabs_included (array &$tabs) : bool
    {
        self::$calls[] = $this->label . ':install_tabs_included';
        $tabs['test_mod_' . $this->label] = array ('id' => 'INT');
        return $this->handles;
    }

    public function add_resources (array &$json, array $aktplanet) : bool
    {
        self::$calls[] = $this->label . ':add_resources';
        $json['mod_planet'] = $aktplanet['planet_id'];
        return $this->handles;
    }

    public function page_overview_get_bonus (array $param, array &$bonuses) : bool
    {
        self::$calls[] = $this->label . ':page_overview_get_bonus';
        $bonuses['mod_page'] = $param['page'];
        return $this->handles;
    }

    public function prod_post_process (array &$planet, array &$eco) : bool
    {
        self::$calls[] = $this->label . ':prod_post_process';
        $planet['mod_planet_touched'] = true;
        $eco['mod_eco_touched'] = true;
        return $this->handles;
    }

    public function bonus_technology (int $id, array &$bonus) : bool
    {
        self::$calls[] = $this->label . ':bonus_technology';
        $bonus['mod_tech'] = $id;
        return $this->handles;
    }

    public function add_db_row (array &$row, string $tabname) : bool
    {
        self::$calls[] = $this->label . ':add_db_row';
        $row['mod_table'] = $tabname;
        return $this->handles;
    }

    public function update_queue (array &$queue) : bool
    {
        self::$calls[] = $this->label . ':update_queue';
        return $this->handles;
    }

    public function fleet_handler (array $param) : bool
    {
        self::$calls[] = $this->label . ':fleet_handler';
        $param['mod_seen'] = true;
        return $this->handles;
    }

    public function skip_planet_update (array &$planet) : bool
    {
        self::$calls[] = $this->label . ':skip_planet_update';
        $planet['mod_frozen'] = true;
        return $this->handles;
    }
}

#[RunTestsInSeparateProcesses]
class ModsTechsInstallDbCoreTest extends TestCase
{
    private ?FixtureBuilder $fixture = null;
    private string $gameDir = '';

    protected function setUp(): void
    {
        // loca_add() and the mod loader resolve their paths against the game
        // directory (see testing/bootstrap.php).
        $this->gameDir = (string) realpath(__DIR__ . '/../game');
        chdir($this->gameDir);

        // The game reads its configuration from globals; the bootstrap only
        // loads the modules, so the test provides the environment.
        $GLOBALS['db_prefix'] = 'test_';
        $GLOBALS['db_name'] = 'test';
        $GLOBALS['db_host'] = '';
        $GLOBALS['db_user'] = '';
        $GLOBALS['db_pass'] = '';
        $GLOBALS['modlist'] = array ();
        $GLOBALS['GlobalUser'] = array ();
        $GLOBALS['GlobalUni'] = array ('num' => 1, 'lang' => 'en', 'modlist' => '');
        $GLOBALS['loca_lang'] = 'en';

        // Some core helpers (Debug, Error, mail_html, ...) read the request
        // context from $_SERVER. A localhost REMOTE_ADDR also keeps mail_html()
        // from calling the real mail() function.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_NAME'] = 'localhost';

        InitDB();
        CreateDBTables();
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Build the full 3-player universe fixture (real game schema) and point
     * $GlobalUni at its universe row.
     */
    private function createUniverse(): void
    {
        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');
        $GLOBALS['GlobalUni'] = $this->fixture->getUniData();
    }

    /**
     * Read the modlist column of the universe row.
     */
    private function dbModlist(): string
    {
        $result = dbquery ('SELECT modlist FROM ' . $GLOBALS['db_prefix'] . 'uni LIMIT 1');
        $row = dbarray ($result);
        return $row === false ? '' : (string) $row['modlist'];
    }

    /**
     * Count the rows of a table (optionally matching a WHERE clause).
     */
    private function countRows(string $table, string $where = '1') : int
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM $table WHERE $where");
        $row = dbarray ($result);
        return $row === false ? -1 : (int) $row['cnt'];
    }

    /**
     * Check whether a table exists in the current database.
     */
    private function tableExists(string $table): bool
    {
        $result = dbquery ('SHOW TABLES');
        while ($row = dbarray ($result)) {
            foreach ($row as $value) {
                if ((string) $value === $table) return true;
            }
        }
        return false;
    }

    /**
     * Check whether a column exists in a table.
     */
    private function columnExists(string $table, string $column): bool
    {
        $result = dbquery ('SHOW COLUMNS FROM ' . $table);
        if ($result === false) return false;
        while ($row = dbarray ($result)) {
            if (strcasecmp ((string) $row['Field'], $column) === 0) return true;
        }
        return false;
    }

    /**
     * Load the $tabs schema registry from the production install_tabs.php.
     */
    private function loadInstallTabs(): array
    {
        $tabs = array ();
        include __DIR__ . '/../game/core/install_tabs.php';
        return $tabs;
    }

    /**
     * Run a callback with the working directory set to a scratch directory
     * that has a temp/ subdirectory, so mail_html() appends its mail log there
     * instead of inside the repository (game/temp/mailto.log). The scratch
     * directory is removed afterwards; the content of the mail log is
     * returned.
     */
    private function withScratchMailDir(callable $callback): string
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ogame-mail-html-' . getmypid();
        $temp = $base . DIRECTORY_SEPARATOR . 'temp';
        $logFile = $temp . DIRECTORY_SEPARATOR . 'mailto.log';

        if (!is_dir ($temp)) mkdir ($temp, 0777, true);
        if (is_file ($logFile)) unlink ($logFile);

        $previous = getcwd();
        chdir ($base);
        try {
            $callback();
        } finally {
            chdir ($previous);
        }

        $log = is_file ($logFile) ? (string) file_get_contents ($logFile) : '';

        if (is_file ($logFile)) unlink ($logFile);
        if (is_dir ($temp)) rmdir ($temp);
        if (is_dir ($base)) rmdir ($base);

        return $log;
    }

    // ========================================================================
    // game/core/techs.php
    // ========================================================================

    /**
     * The object type predicates must classify each id into exactly its group.
     */
    public function testObjectTypePredicatesClassifyEveryObjectKind(): void
    {
        $this->assertTrue (IsBuilding (GID_B_METAL_MINE));
        $this->assertTrue (IsBuilding (GID_B_MISS_SILO));
        $this->assertFalse (IsBuilding (GID_R_ENERGY));
        $this->assertFalse (IsBuilding (GID_F_SC));

        $this->assertTrue (IsResearch (GID_R_ESPIONAGE));
        $this->assertTrue (IsResearch (GID_R_GRAVITON));
        $this->assertFalse (IsResearch (GID_B_METAL_MINE));
        $this->assertFalse (IsResearch (GID_F_SC));

        $this->assertTrue (IsFleet (GID_F_PROBE));
        $this->assertTrue (IsFleet (GID_F_DEATHSTAR));
        $this->assertFalse (IsFleet (GID_D_RL));

        $this->assertTrue (IsDefense (GID_D_PLASMA));
        $this->assertTrue (IsDefense (GID_D_IPM));
        $this->assertFalse (IsDefense (GID_F_LF));

        $this->assertTrue (IsResource (GID_RC_METAL));
        $this->assertTrue (IsResource (GID_RC_DM));
        $this->assertFalse (IsResource (GID_B_SOLAR));

        // Ids outside every map belong to no group.
        $this->assertFalse (IsBuilding (GID_MAX));
        $this->assertFalse (IsFleet (0));
        $this->assertFalse (IsDefense (-1));
        $this->assertFalse (IsResource (GID_R_GRAVITON));
    }

    /**
     * IsDefenseNoRak() is IsDefense() without the two missile types.
     */
    public function testIsDefenseNoRakExcludesOnlyTheTwoMissileTypes(): void
    {
        $this->assertFalse (IsDefenseNoRak (GID_D_ABM));
        $this->assertFalse (IsDefenseNoRak (GID_D_IPM));
        $this->assertTrue (IsDefenseNoRak (GID_D_RL));
        $this->assertTrue (IsDefenseNoRak (GID_D_LDOME));
        $this->assertFalse (IsDefenseNoRak (GID_F_LF));

        $this->assertSame (array (GID_D_ABM, GID_D_IPM), $GLOBALS['rakmap']);
        $this->assertSame (
            array (GID_D_RL, GID_D_LL, GID_D_HL, GID_D_GAUSS, GID_D_ION, GID_D_PLASMA, GID_D_SDOME, GID_D_LDOME),
            array_values (array_diff ($GLOBALS['defmap'], $GLOBALS['rakmap']))
        );
    }

    /**
     * The object maps list exactly the canonical ids of each group.
     */
    public function testObjectMapsListTheExpectedIds(): void
    {
        $this->assertSame (array (
            GID_B_METAL_MINE, GID_B_CRYS_MINE, GID_B_DEUT_SYNTH, GID_B_SOLAR, GID_B_FUSION,
            GID_B_ROBOTS, GID_B_NANITES, GID_B_SHIPYARD, GID_B_METAL_STOR, GID_B_CRYS_STOR,
            GID_B_DEUT_STOR, GID_B_RES_LAB, GID_B_TERRAFORMER, GID_B_ALLY_DEPOT,
            GID_B_LUNAR_BASE, GID_B_PHALANX, GID_B_JUMP_GATE, GID_B_MISS_SILO,
        ), $GLOBALS['buildmap']);

        $this->assertSame (array (
            GID_R_ESPIONAGE, GID_R_COMPUTER, GID_R_WEAPON, GID_R_SHIELD, GID_R_ARMOUR, GID_R_ENERGY,
            GID_R_HYPERSPACE, GID_R_COMBUST_DRIVE, GID_R_IMPULSE_DRIVE, GID_R_HYPER_DRIVE,
            GID_R_LASER_TECH, GID_R_ION_TECH, GID_R_PLASMA_TECH, GID_R_IGN, GID_R_EXPEDITION,
            GID_R_GRAVITON,
        ), $GLOBALS['resmap']);

        $this->assertSame (array (
            GID_F_SC, GID_F_LC, GID_F_LF, GID_F_HF, GID_F_CRUISER, GID_F_BATTLESHIP, GID_F_COLON,
            GID_F_RECYCLER, GID_F_PROBE, GID_F_BOMBER, GID_F_SAT, GID_F_DESTRO, GID_F_DEATHSTAR,
            GID_F_BATTLECRUISER,
        ), $GLOBALS['fleetmap']);

        $this->assertSame (array (
            GID_D_RL, GID_D_LL, GID_D_HL, GID_D_GAUSS, GID_D_ION, GID_D_PLASMA, GID_D_SDOME,
            GID_D_LDOME, GID_D_ABM, GID_D_IPM,
        ), $GLOBALS['defmap']);

        $this->assertSame (array (
            GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM, GID_RC_ENERGY, GID_RC_DM,
        ), $GLOBALS['resourcemap']);

        $this->assertCount (18, $GLOBALS['buildmap']);
        $this->assertCount (16, $GLOBALS['resmap']);
        $this->assertCount (14, $GLOBALS['fleetmap']);
        $this->assertCount (10, $GLOBALS['defmap']);
        $this->assertCount (5, $GLOBALS['resourcemap']);
    }

    /**
     * Every object id is unique across all groups and fits into the id limit.
     */
    public function testObjectIdsAreUniqueAndBelowTheIdLimit(): void
    {
        $this->assertSame (0xffff, GID_MAX);

        $ids = array_merge (
            $GLOBALS['buildmap'], $GLOBALS['resmap'], $GLOBALS['fleetmap'],
            $GLOBALS['defmap'], $GLOBALS['resourcemap']
        );

        $this->assertSame ($ids, array_values (array_unique ($ids)), 'Object ids must be unique across all maps');
        foreach ($ids as $id) {
            $this->assertGreaterThanOrEqual (1, $id);
            $this->assertLessThanOrEqual (GID_MAX, $id);
        }
    }

    /**
     * The combat parameters are the reference OGame values.
     */
    public function testUnitParametersMatchTheReferenceCombatStats(): void
    {
        $unit = $GLOBALS['UnitParam'];

        // structure, shield, attack, cargo capacity, speed, consumption
        $this->assertSame (array (4000, 10, 50, 50, 12500, 20), $unit[GID_F_LF]);
        $this->assertSame (array (4000, 10, 5, 5000, 5000, 10), $unit[GID_F_SC]);
        $this->assertSame (array (9000000, 50000, 200000, 1000000, 100, 1), $unit[GID_F_DEATHSTAR]);
        $this->assertSame (array (15000, 1, 12000, 0, 0, 0), $unit[GID_D_IPM]);
        $this->assertSame (array (2000, 20, 80, 0, 0, 0), $unit[GID_D_RL]);
        $this->assertSame (array (20000, 2000, 1, 0, 0, 0), $unit[GID_D_SDOME]);

        // Every ship and every defense structure has combat parameters (and
        // nothing else does).
        $this->assertSame (array_merge ($GLOBALS['fleetmap'], $GLOBALS['defmap']), array_keys ($unit));
        foreach ($unit as $gid => $stats) {
            $this->assertCount (6, $stats, "UnitParam[$gid] must hold 6 stats");
            foreach ($stats as $stat) $this->assertIsInt ($stat);
        }
    }

    /**
     * Level-1 costs and the exponential growth factors.
     */
    public function testInitialCostsAndGrowthFactors(): void
    {
        $initial = $GLOBALS['initial'];

        $this->assertSame (array (GID_RC_METAL => 60, GID_RC_CRYSTAL => 15, 'factor' => 1.5), $initial[GID_B_METAL_MINE]);
        $this->assertSame (array (GID_RC_METAL => 48, GID_RC_CRYSTAL => 24, 'factor' => 1.6), $initial[GID_B_CRYS_MINE]);
        $this->assertSame (array (GID_RC_METAL => 200, GID_RC_CRYSTAL => 1000, GID_RC_DEUTERIUM => 200, 'factor' => 2), $initial[GID_R_ESPIONAGE]);
        // Graviton Technology is the only entry that grows by a factor of 3.
        $this->assertSame (array (GID_RC_ENERGY => 300000, 'factor' => 3), $initial[GID_R_GRAVITON]);

        // Ships and defense structures cost the same at every level.
        foreach (array_merge ($GLOBALS['fleetmap'], $GLOBALS['defmap']) as $gid) {
            $this->assertSame (0, $initial[$gid]['factor'], "initial[$gid] must not grow");
        }

        // Every game object has a level-1 price, and every price has a factor
        // and at least one positive resource cost.
        $objects = array_merge ($GLOBALS['buildmap'], $GLOBALS['resmap'], $GLOBALS['fleetmap'], $GLOBALS['defmap']);
        $this->assertEqualsCanonicalizing ($objects, array_keys ($initial));
        foreach ($initial as $gid => $cost) {
            $this->assertArrayHasKey ('factor', $cost, "initial[$gid] must define its growth factor");
            $this->assertGreaterThan (count ($cost) - 2, count ($cost), "initial[$gid] must cost at least one resource");
            foreach ($cost as $res => $value) {
                if ($res === 'factor') continue;
                $this->assertContains ($res, $GLOBALS['resourcemap'], "initial[$gid] costs unknown resource $res");
                $this->assertGreaterThan (0, $value);
            }
        }
    }

    /**
     * The requirement tree (what must be built/researched before what).
     */
    public function testRequirementsMatchTheReferenceTechnologyTree(): void
    {
        $req = $GLOBALS['requirements'];

        $this->assertSame (array (GID_B_ROBOTS => 10, GID_R_COMPUTER => 10), $req[GID_B_NANITES]);
        $this->assertSame (
            array (GID_B_SHIPYARD => 12, GID_R_HYPER_DRIVE => 7, GID_R_HYPERSPACE => 6, GID_R_GRAVITON => 1),
            $req[GID_F_DEATHSTAR]
        );
        $this->assertSame (array (GID_B_SHIPYARD => 8, GID_R_PLASMA_TECH => 7), $req[GID_D_PLASMA]);
        // The Gauss cannon requirement spells Weapon Technology as the literal
        // 109 instead of the GID_R_WEAPON constant; the values are identical.
        $this->assertSame (3, $req[GID_D_GAUSS][109]);
        $this->assertSame (GID_R_WEAPON, 109);
        // The four starter buildings need nothing.
        foreach (array (GID_B_METAL_MINE, GID_B_CRYS_MINE, GID_B_DEUT_SYNTH, GID_B_SOLAR) as $gid) {
            $this->assertSame (array (), $req[$gid]);
        }

        // Every buildable object has a requirement entry ...
        $objects = array_merge ($GLOBALS['buildmap'], $GLOBALS['resmap'], $GLOBALS['fleetmap'], $GLOBALS['defmap']);
        foreach ($objects as $gid) {
            $this->assertArrayHasKey ($gid, $req, "requirements[$gid] is missing");
        }
        // ... and every requirement points at a real building/research with a
        // positive level.
        $known = array_merge ($GLOBALS['buildmap'], $GLOBALS['resmap']);
        foreach ($req as $gid => $deps) {
            foreach ($deps as $dep => $level) {
                $this->assertContains ($dep, $known, "requirements[$gid] requires the unknown object $dep");
                $this->assertGreaterThan (0, $level);
            }
        }
    }

    /**
     * The rapid-fire table.
     */
    public function testRapidFireTableMatchesTheReferenceValues(): void
    {
        $rf = $GLOBALS['RapidFire'];

        $this->assertSame (
            array (GID_F_PROBE => 5, GID_F_SAT => 5, GID_F_BATTLECRUISER => 2, GID_D_LL => 10),
            $rf[GID_F_DESTRO]
        );
        $this->assertSame (array (GID_F_LF => 6, GID_F_PROBE => 5, GID_F_SAT => 5, GID_D_RL => 10), $rf[GID_F_CRUISER]);
        $this->assertSame (250, $rf[GID_F_DEATHSTAR][GID_F_SC]);
        $this->assertSame (1250, $rf[GID_F_DEATHSTAR][GID_F_PROBE]);
        $this->assertSame (200, $rf[GID_F_DEATHSTAR][GID_D_RL]);
        $this->assertSame (20, $rf[GID_F_BOMBER][GID_D_LL]);
        $this->assertSame (3, $rf[GID_F_HF][GID_F_SC]);

        // Probes and satellites never fire rapidly, and neither does the
        // defense (the original 0.84 rule).
        $this->assertSame (array (), $rf[GID_F_PROBE]);
        $this->assertSame (array (), $rf[GID_F_SAT]);
        foreach ($GLOBALS['defmap'] as $gid) {
            if (!array_key_exists ($gid, $rf)) continue;
            $this->assertSame (array (), $rf[$gid], "Defense object $gid must not have rapid fire");
        }

        // Every rapid-fire entry targets a real ship or defense object.
        $known = array_merge ($GLOBALS['fleetmap'], $GLOBALS['defmap']);
        foreach ($rf as $gid => $targets) {
            $this->assertContains ($gid, $known, "RapidFire[$gid] is not a ship or defense object");
            foreach ($targets as $target => $chance) {
                $this->assertContains ($target, $known, "RapidFire[$gid] targets the unknown object $target");
                $this->assertGreaterThan (0, $chance);
            }
        }
    }

    /**
     * Which buildings may be erected on which planet type.
     */
    public function testCanBuildTabRestrictsBuildingsByPlanetType(): void
    {
        $tab = $GLOBALS['CanBuildTab'];

        $this->assertSame (array (
            GID_B_ROBOTS, GID_B_SHIPYARD, GID_B_METAL_STOR, GID_B_CRYS_STOR, GID_B_DEUT_STOR,
            GID_B_LUNAR_BASE, GID_B_PHALANX, GID_B_JUMP_GATE,
        ), $tab[PTYP_MOON]);

        // A planet can build every building except the three lunar ones; the
        // Robotics Factory, the Shipyard and the storages exist on both.
        $this->assertSame (
            array_values (array_diff ($GLOBALS['buildmap'], array (GID_B_LUNAR_BASE, GID_B_PHALANX, GID_B_JUMP_GATE))),
            $tab[PTYP_PLANET]
        );
        foreach (array (GID_B_ROBOTS, GID_B_SHIPYARD, GID_B_METAL_STOR) as $gid) {
            $this->assertContains ($gid, $tab[PTYP_PLANET]);
            $this->assertContains ($gid, $tab[PTYP_MOON]);
        }

        // Galaxies objects that are not planets/moons can build nothing.
        foreach (array (PTYP_DF, PTYP_DEST_PLANET, PTYP_COLONY_PHANTOM, PTYP_DEST_MOON, PTYP_ABANDONED, PTYP_FARSPACE) as $type) {
            $this->assertSame (array (), $tab[$type], "Planet type $type must not allow buildings");
        }

        // Everything listed for a planet type is a real building.
        foreach ($tab as $type => $gids) {
            foreach ($gids as $gid) {
                $this->assertContains ($gid, $GLOBALS['buildmap'], "Planet type $type lists the non-building $gid");
            }
        }
    }

    /**
     * The resource grouping tables used by scoring, transport, debris, etc.
     */
    public function testResourceGroupingTables(): void
    {
        $this->assertSame (array (
            GID_RC_METAL => GID_B_METAL_STOR,
            GID_RC_CRYSTAL => GID_B_CRYS_STOR,
            GID_RC_DEUTERIUM => GID_B_DEUT_STOR,
        ), $GLOBALS['storagemap']);

        $this->assertSame (array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM), $GLOBALS['scoreResources']);
        $this->assertSame (array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM), $GLOBALS['transportableResources']);
        $this->assertSame (array (GID_RC_METAL, GID_RC_CRYSTAL), $GLOBALS['debrisResources']);
        $this->assertSame (array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM), $GLOBALS['resourcesWithNonZeroDerivative']);
        $this->assertSame (array (GID_RC_ENERGY, GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM), $GLOBALS['prodPriority']);
        $this->assertSame (array (GID_RC_METAL => 20, GID_RC_CRYSTAL => 10), $GLOBALS['naturalProduction']);

        // Energy and dark matter are never scored, transported or recycled.
        foreach (array (GID_RC_ENERGY, GID_RC_DM) as $gid) {
            $this->assertNotContains ($gid, $GLOBALS['scoreResources']);
            $this->assertNotContains ($gid, $GLOBALS['transportableResources']);
            $this->assertNotContains ($gid, $GLOBALS['debrisResources']);
            $this->assertNotContains ($gid, $GLOBALS['resourcesWithNonZeroDerivative']);
        }

        // Every transportable resource has a storage building.
        foreach ($GLOBALS['transportableResources'] as $gid) {
            $this->assertArrayHasKey ($gid, $GLOBALS['storagemap']);
            $this->assertContains ($GLOBALS['storagemap'][$gid], $GLOBALS['buildmap']);
        }
    }

    // ========================================================================
    // game/core/install_tabs.php
    // ========================================================================

    /**
     * The schema registry declares every game table with at least one column.
     */
    public function testInstallTabsRegistryDeclaresEveryGameTable(): void
    {
        $tabs = $this->loadInstallTabs();

        $this->assertGreaterThanOrEqual (27, count ($tabs));
        foreach (array (
            'uni', 'users', 'planets', 'ally', 'allyranks', 'allyapps', 'buddy', 'messages', 'notes',
            'errors', 'debug', 'reports', 'browse', 'queue', 'buildqueue', 'fleet', 'union',
            'battledata', 'fleetlogs', 'iplogs', 'pranger', 'exptab', 'coltab', 'template',
            'botvars', 'userlogs', 'botstrat',
        ) as $table) {
            $this->assertArrayHasKey ($table, $tabs, "The schema registry must declare the $table table");
        }

        foreach ($tabs as $name => $columns) {
            $this->assertIsArray ($columns, "The schema of $name must be an array");
            $this->assertNotEmpty ($columns, "The schema of $name must declare at least one column");
            foreach ($columns as $column => $type) {
                $this->assertIsString ($type, "The type of $name.$column must be a string");
                $this->assertNotSame ('', trim ($type), "The type of $name.$column must not be empty");
            }
        }
    }

    /**
     * The column types of the core tables (the contract the game code relies on).
     */
    public function testInstallTabsColumnTypesOfTheCoreTables(): void
    {
        $tabs = $this->loadInstallTabs();

        $this->assertSame ('INT PRIMARY KEY', $tabs['uni']['num']);
        $this->assertSame ('TEXT', $tabs['uni']['modlist']);
        $this->assertSame ('INT UNSIGNED DEFAULT ' . BATTLE_MAX_UNITS, $tabs['uni']['battle_max']);
        $this->assertSame ('CHAR(4)', $tabs['uni']['lang']);

        $this->assertSame ('INT AUTO_INCREMENT PRIMARY KEY', $tabs['users']['player_id']);
        $this->assertSame ('TINYINT DEFAULT 0', $tabs['users'][GID_R_ESPIONAGE]);
        $this->assertSame ('INT UNSIGNED', $tabs['users']['dm']);
        $this->assertSame ('INT UNSIGNED', $tabs['users']['flags']);
        $this->assertSame ('CHAR(12)', $tabs['users']['session']);

        $this->assertSame ('INT AUTO_INCREMENT PRIMARY KEY', $tabs['planets']['planet_id']);
        $this->assertSame ('DOUBLE DEFAULT 0', $tabs['planets'][GID_RC_METAL]);
        $this->assertSame ('INT UNSIGNED DEFAULT 0', $tabs['planets'][GID_D_RL]);
        $this->assertSame ('INT UNSIGNED DEFAULT 0', $tabs['planets'][GID_F_DEATHSTAR]);
        $this->assertSame ('DOUBLE DEFAULT 1', $tabs['planets']['prod' . GID_B_METAL_MINE]);

        $this->assertSame ('INT AUTO_INCREMENT PRIMARY KEY', $tabs['queue']['task_id']);
        $this->assertSame ('INT DEFAULT 0', $tabs['queue']['freeze']);
        $this->assertSame ('INT UNSIGNED DEFAULT 0', $tabs['queue']['frozen']);

        $this->assertSame ('INT', $tabs['fleet']['fuel']);
        $this->assertSame ('INT DEFAULT 0', $tabs['fleet']['ipm_amount']);
        $this->assertSame ('DOUBLE DEFAULT 0', $tabs['fleet'][GID_RC_METAL]);
        $this->assertSame ('DOUBLE DEFAULT 0', $tabs['fleetlogs']['p' . GID_RC_METAL]);

        $this->assertSame ('CHAR(30)', $tabs['template']['name']);
        $this->assertSame ('INT AUTO_INCREMENT PRIMARY KEY', $tabs['notes']['note_id']);
        $this->assertSame ('TEXT', $tabs['messages']['text']);
    }

    /**
     * The schema must be able to store every game object the code uses.
     */
    public function testSchemaCoversEveryGameObjectId(): void
    {
        $tabs = $this->loadInstallTabs();

        foreach ($GLOBALS['resmap'] as $gid) {
            $this->assertArrayHasKey ($gid, $tabs['users'], "The users table misses the research column $gid");
        }
        foreach (array_merge ($GLOBALS['buildmap'], $GLOBALS['fleetmap'], $GLOBALS['defmap']) as $gid) {
            $this->assertArrayHasKey ($gid, $tabs['planets'], "The planets table misses the object column $gid");
        }
        foreach ($GLOBALS['fleetmap'] as $gid) {
            $this->assertArrayHasKey ($gid, $tabs['fleet'], "The fleet table misses the ship column $gid");
            $this->assertArrayHasKey ($gid, $tabs['fleetlogs'], "The fleetlogs table misses the ship column $gid");
            $this->assertArrayHasKey ($gid, $tabs['template'], "The template table misses the ship column $gid");
        }
        foreach (array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM) as $gid) {
            $this->assertArrayHasKey ($gid, $tabs['planets']);
            $this->assertArrayHasKey ($gid, $tabs['fleet']);
            $this->assertArrayHasKey ($gid, $tabs['fleetlogs']);
            $this->assertArrayHasKey ('p' . $gid, $tabs['fleetlogs']);
        }
    }

    // ========================================================================
    // game/core/db.php
    // ========================================================================

    /**
     * DB_ConnectionType() maps the DB_CONNECTION environment variable onto a
     * backend name; anything that is not sqlite/sqlite3 falls back to MySQL.
     */
    public function testDbConnectionTypeSelectsTheBackendFromTheEnvironment(): void
    {
        $this->assertSame ('sqlite', DB_ConnectionType ());

        putenv ('DB_CONNECTION=mysql');
        $this->assertSame ('mysql', DB_ConnectionType ());

        putenv ('DB_CONNECTION=SQLite');        // case-insensitive
        $this->assertSame ('sqlite', DB_ConnectionType ());

        putenv ('DB_CONNECTION= sqlite3 ');     // trimmed, sqlite3 is an alias
        $this->assertSame ('sqlite', DB_ConnectionType ());

        putenv ('DB_CONNECTION=');              // empty -> MySQL
        $this->assertSame ('mysql', DB_ConnectionType ());

        putenv ('DB_CONNECTION');               // unset -> MySQL
        $this->assertSame ('mysql', DB_ConnectionType ());

        putenv ('DB_CONNECTION=sqlite');
        $this->assertSame ('sqlite', DB_ConnectionType ());
    }

    /**
     * CreateDBTables() must create every table declared in install_tabs.php
     * with exactly the declared columns, in the declared order.
     */
    public function testCreateDbTablesCreatesEveryRegisteredTableWithEveryColumn(): void
    {
        $tabs = $this->loadInstallTabs();
        $prefix = $GLOBALS['db_prefix'];

        CreateDBTables();

        foreach ($tabs as $name => $columns) {
            $result = dbquery ('SHOW COLUMNS FROM ' . $prefix . $name);
            $this->assertNotFalse ($result, "db.php must create the $name table");

            $actual = array ();
            while ($row = dbarray ($result)) $actual[] = (string) $row['Field'];

            $this->assertSame (array_map ('strval', array_keys ($columns)), $actual,
                "The $name table must have exactly the columns declared in install_tabs.php");
        }
    }

    /**
     * CreateDBTables() drops the previous contents and restarts autoincrement.
     */
    public function testCreateDbTablesDropsExistingDataAndRestartsAutoIncrement(): void
    {
        $this->assertSame (1, AddDBRow (array ('owner_id' => 1, 'subj' => 'keep', 'text' => 'me'), 'notes'));
        $this->assertSame (2, AddDBRow (array ('owner_id' => 1, 'subj' => 'keep', 'text' => 'me too'), 'notes'));

        CreateDBTables();

        $this->assertSame (0, $this->countRows ($GLOBALS['db_prefix'] . 'notes'));
        $this->assertSame (1, AddDBRow (array ('owner_id' => 1, 'subj' => 'fresh', 'text' => 'row'), 'notes'),
            'CreateDBTables() recreates the tables, so the autoincrement restarts');
    }

    /**
     * CreateDBTables() creates the tables under the configured table prefix.
     */
    public function testCreateDbTablesUsesTheConfiguredTablePrefix(): void
    {
        $tabs = $this->loadInstallTabs();
        $GLOBALS['db_prefix'] = 'alt_';

        CreateDBTables();

        foreach (array_keys ($tabs) as $name) {
            $this->assertTrue ($this->tableExists ('alt_' . $name), "The alt_$name table is missing");
        }
        $this->assertSame (0, $this->countRows ('alt_users'));
        // The tables created by setUp() under the regular prefix are untouched.
        $this->assertTrue ($this->tableExists ('test_users'));

        $GLOBALS['db_prefix'] = 'test_';
    }

    /**
     * Tables whose first column is not a primary key must still accept the
     * rows the game inserts (allyranks, exptab, coltab, botstrat).
     */
    public function testSchemaTablesWithoutPrimaryKeyAcceptRows(): void
    {
        AddDBRow (array ('rank_id' => 0, 'ally_id' => 1, 'name' => 'Founder', 'rights' => 511), 'allyranks');
        AddDBRow (array ('chance_success' => 50, 'chance_pirates' => 10), 'exptab');
        AddDBRow (array ('t1_a' => 1100, 't1_b' => 5000, 't1_c' => 1000), 'coltab');
        AddDBRow (array ('name' => 'Bot', 'source' => 'return;'), 'botstrat');

        $this->assertSame (1, $this->countRows ('test_allyranks'));
        $this->assertSame (1, $this->countRows ('test_exptab'));
        $this->assertSame (1, $this->countRows ('test_coltab'));
        $this->assertSame (1, $this->countRows ('test_botstrat'));

        $row = dbarray (dbquery ('SELECT name, rights FROM test_allyranks'));
        $this->assertSame ('Founder', $row['name']);
        $this->assertSame (511, (int) $row['rights']);

        // The unset columns stay NULL, not 0.
        $row = dbarray (dbquery ('SELECT chance_success, chance_res FROM test_exptab'));
        $this->assertSame (50, (int) $row['chance_success']);
        $this->assertNull ($row['chance_res']);
    }

    // ========================================================================
    // game/core/coupon.php
    // ========================================================================

    /**
     * Without the master database every coupon lookup reports "not found".
     */
    public function testCheckCouponReturnsZeroWhenTheMasterDatabaseIsUnavailable(): void
    {
        $this->assertFalse (MDBConnect());
        $this->assertSame (0, CheckCoupon ('2B2D-FE3D-7D74-37C4-D26M'));
        $this->assertSame (0, CheckCoupon ("' OR '1'='1"));
        $this->assertSame (0, CheckCoupon (''));
    }

    /**
     * LoadCoupon()/EnumCoupons() return null without the master database.
     */
    public function testLoadCouponAndEnumCouponsReturnNullWithoutTheMasterDatabase(): void
    {
        $this->assertNull (LoadCoupon (1));
        $this->assertNull (LoadCoupon (0));
        $this->assertNull (LoadCoupon (-1));
        $this->assertNull (EnumCoupons (0, 15));
        $this->assertNull (EnumCoupons (15, 15));
    }

    /**
     * TotalCoupons() reports 0 and AddCoupon() generates nothing when the
     * master database is unavailable.
     */
    public function testTotalCouponsAndAddCouponReportNoMasterDatabase(): void
    {
        $this->assertSame (0, TotalCoupons());
        $this->assertNull (AddCoupon (100));
        $this->assertNull (AddCoupon (0));
        $this->assertNull (AddCoupon (-50));
    }

    /**
     * DeleteCoupon() is a silent no-op without the master database.
     */
    public function testDeleteCouponIsANoOpWithoutTheMasterDatabase(): void
    {
        DeleteCoupon (1);
        DeleteCoupon (-5);

        // No game table was touched and no "coupons" table was created.
        $this->assertSame (0, $this->countRows ('test_notes'));
        $this->assertFalse ($this->tableExists ('coupons'));
    }

    /**
     * ActivateCoupon() fails and leaves the player's dark matter untouched when
     * the master database (which holds the coupon) is unavailable.
     */
    public function testActivateCouponFailsAndLeavesDarkMatterUntouched(): void
    {
        $this->createUniverse();
        $user = dbarray (dbquery ('SELECT * FROM test_users WHERE player_id = 1'));
        $this->assertIsArray ($user);
        $this->assertSame (1000, (int) $user['dm']);

        $this->assertFalse (ActivateCoupon ($user, '2B2D-FE3D-7D74-37C4-D26M'));

        $row = dbarray (dbquery ('SELECT dm FROM test_users WHERE player_id = 1'));
        $this->assertSame (1000, (int) $row['dm'], 'No coupon could be redeemed, so no DM may be credited');
    }

    /**
     * Queue_Coupon_End() removes the distribution task when its periodicity
     * (level) is 0.
     */
    public function testQueueCouponEndRemovesTheTaskWhenThePeriodIsZero(): void
    {
        $this->createUniverse();
        $now = time();

        // sub_id = dark matter per coupon, obj_id = (inactive days << 16) | in-game days.
        $taskId = AddQueue (USER_SPACE, QTYP_COUPON, 100, (7 << 16) | 1, 0, $now, 3600, QUEUE_PRIO_COUPON);
        $queue = LoadQueue ($taskId);
        $this->assertIsArray ($queue);
        $this->assertSame (0, (int) $queue['level']);

        Queue_Coupon_End ($queue);

        $this->assertFalse (LoadQueue ($taskId), 'A coupon task with level 0 must be removed');
    }

    /**
     * Queue_Coupon_End() prolongs the distribution task by level days when the
     * periodicity is positive.
     */
    public function testQueueCouponEndProlongsTheTaskWhenThePeriodIsPositive(): void
    {
        $this->createUniverse();
        $now = time();

        $taskId = AddQueue (USER_SPACE, QTYP_COUPON, 100, (7 << 16) | 1, 3, $now, 3600, QUEUE_PRIO_COUPON);
        $before = LoadQueue ($taskId);
        $this->assertIsArray ($before);

        Queue_Coupon_End ($before);

        $after = LoadQueue ($taskId);
        $this->assertIsArray ($after);
        $this->assertSame ((int) $before['end'] + 3 * 24 * 60 * 60, (int) $after['end']);
    }

    /**
     * The distribution task grants nothing without the master database: no
     * coupon can be generated and therefore no player is mailed a code.
     */
    public function testQueueCouponEndSendsNothingWithoutTheMasterDatabase(): void
    {
        $this->createUniverse();
        $logPath = $this->gameDir . '/temp/mailto.log';
        $logExisted = is_file ($logPath);

        $taskId = AddQueue (USER_SPACE, QTYP_COUPON, 100, (7 << 16) | 1, 0, time(), 3600, QUEUE_PRIO_COUPON);
        Queue_Coupon_End (LoadQueue ($taskId));

        $row = dbarray (dbquery ('SELECT SUM(dm) AS total FROM test_users'));
        $this->assertSame (3000, (int) $row['total'], 'Coupons must not credit any DM without the master database');
        $this->assertSame ($logExisted, is_file ($logPath), 'No coupon mail may be written without the master database');
    }

    /**
     * mail_html() logs the message to temp/mailto.log (relative to the current
     * working directory) and skips the real mail() call for localhost
     * requests. It is executed in a scratch directory so that the repository's
     * game/temp/ directory stays untouched.
     */
    public function testMailHtmlWritesTheHtmlMailLog(): void
    {
        $log = $this->withScratchMailDir (function () : void {
            mail_html ('player1@test.com', 'Present to you', 'Dear PlayerOne', 'From: coupon@localhost');
        });

        $this->assertSame (
            "To: player1@test.com\r\nSubj: Present to you\r\n\r\nDear PlayerOne\r\n",
            $log
        );
    }

    /**
     * SendCoupon() cannot be called here: it would append to
     * game/temp/mailto.log inside the repository. Verify the localized
     * template it composes instead.
     */
    public function testCouponLocalizationStringsUsedBySendCoupon(): void
    {
        loca_add ('coupons', 'en');

        $this->assertSame ('Present to you', loca_lang ('COUPON_SUBJ', 'en'));
        $this->assertSame (
            'Dear PlayerOne, you have present : 2B2D-FE3D-7D74-37C4-D26M',
            va (loca_lang ('COUPON_MESSAGE', 'en'), 'PlayerOne', '2B2D-FE3D-7D74-37C4-D26M')
        );
    }

    // ========================================================================
    // game/core/mods.php
    // ========================================================================

    /**
     * ModsList() reports the mod folders on disk and the mods enabled in the
     * universe row.
     */
    public function testModsListReturnsAvailableFoldersAndInstalledMods(): void
    {
        $GLOBALS['GlobalUni'] = array ('num' => 1, 'lang' => 'en', 'modlist' => 'BogusMod;GalaxyTool');

        $list = ModsList();

        $this->assertSame (array ('available', 'installed'), array_keys ($list));
        $this->assertSame (array ('BogusMod', 'GalaxyTool'), $list['installed']);

        foreach (array ('BogusMod', 'DeepSpaceHorror', 'GalaxyTool', 'SpaceStorm', 'Wanderer') as $folder) {
            $this->assertContains ($folder, $list['available']);
        }
        // Only real directories are listed (files and the dot entries are not).
        $this->assertNotContains ('.', $list['available']);
        $this->assertNotContains ('..', $list['available']);
        $this->assertNotContains ('.gitignore', $list['available']);
        $sorted = $list['available'];
        sort ($sorted);
        $this->assertSame ($sorted, $list['available'], 'Available mods are listed in scandir() order');
    }

    /**
     * An empty or missing modlist means no mod is installed.
     */
    public function testModsListTreatsAMissingOrEmptyModlistAsEmpty(): void
    {
        $GLOBALS['GlobalUni'] = array ('num' => 1, 'lang' => 'en', 'modlist' => '');
        $this->assertSame (array (), ModsList()['installed']);

        $GLOBALS['GlobalUni'] = array ('num' => 1, 'lang' => 'en');
        $this->assertSame (array (), ModsList()['installed']);
    }

    /**
     * ModsGetInfo() reads the mod's manifest.json.
     */
    public function testModsGetInfoReadsTheModManifest(): void
    {
        $info = ModsGetInfo ('BogusMod');

        $this->assertIsArray ($info);
        $this->assertSame ('Bogus Modification', $info['name']);
        $this->assertSame ('1.0.0', $info['version']);
        $this->assertSame ('ogamespec', $info['author']);
        $this->assertSame ('A simple modification to demonstrate the capabilities', $info['description']);
        $this->assertSame ('https://github.com/ogamespec/ogame-opensource', $info['website']);
        $this->assertSame ('BogusMod', $info['folder']);
        $this->assertSame ('mods/BogusMod/img/bg.png', $info['bg_image']);

        // The mods path is a parameter; the explicit default yields the same result.
        $this->assertSame ($info, ModsGetInfo ('BogusMod', 'mods/'));
    }

    /**
     * ModsGetInfo() returns null for anything that is not a mod folder with a
     * readable manifest.json.
     */
    public function testModsGetInfoReturnsNullForUnknownFoldersOrMissingManifests(): void
    {
        $this->assertNull (ModsGetInfo ('NoSuchMod'));
        // A folder inside a mod without a manifest.
        $this->assertNull (ModsGetInfo ('img', 'mods/BogusMod/'));
        // A plain file is not a directory.
        $this->assertNull (ModsGetInfo ('manifest.json', 'mods/BogusMod/'));
        // A game core directory has no manifest.json.
        $this->assertNull (ModsGetInfo ('core', ''));
    }

    /**
     * ModsInit() instantiates every mod of the universe's modlist and calls
     * its init().
     */
    public function testModsInitLoadsEveryModOfTheUniverseModlist(): void
    {
        // BogusMod ships a ru_ru localization file only, so the ru language is
        // used here (an en universe would make loca_add() raise a PHP warning).
        $GLOBALS['GlobalUni'] = array ('num' => 1, 'lang' => 'ru', 'modlist' => 'BogusMod');

        ModsInit();

        $this->assertArrayHasKey ('BogusMod', $GLOBALS['modlist']);
        $this->assertInstanceOf (BogusMod::class, $GLOBALS['modlist']['BogusMod']);
        // BogusMod::init() loads its localization section.
        $this->assertSame ('Совет дня', loca_lang ('BOGUS_MOD_MENU_ITEM', 'ru'));
    }

    /**
     * ModsInit() ignores an empty modlist and unknown mod names.
     */
    public function testModsInitIgnoresAnEmptyModlistAndUnknownMods(): void
    {
        $GLOBALS['GlobalUni'] = array ('num' => 1, 'lang' => 'en', 'modlist' => '');
        ModsInit();
        $this->assertSame (array (), $GLOBALS['modlist']);

        $GLOBALS['GlobalUni']['modlist'] = 'NoSuchMod;';
        ModsInit();
        $this->assertSame (array (), $GLOBALS['modlist']);
    }

    /**
     * ModsExec() calls a parameterless hook on every mod and stops at the first
     * mod that reports "handled".
     */
    public function testModsExecCallsParameterlessHooksAndStopsAtTheFirstHandler(): void
    {
        $GLOBALS['modlist'] = array (
            'first' => new ModsTechsInstallDbCoreTestMod ('first', true),
            'second' => new ModsTechsInstallDbCoreTestMod ('second', true),
        );
        ModsTechsInstallDbCoreTestMod::$calls = array ();

        $this->assertTrue (ModsExec ('begin_content'));
        $this->assertSame (array ('first:begin_content'), ModsTechsInstallDbCoreTestMod::$calls,
            'ModsExec() must stop at the first mod that handles the hook');

        // ModsExec() can also be used for the lifecycle methods; they return
        // void, so ModsExec() reports false even though both mods ran.
        ModsTechsInstallDbCoreTestMod::$calls = array ();
        $this->assertFalse (ModsExec ('install'));
        $this->assertSame (array ('first:install', 'second:install'), ModsTechsInstallDbCoreTestMod::$calls);
    }

    /**
     * ModsExec() returns false when no mod handles the hook, and a hook no mod
     * implements is simply skipped.
     */
    public function testModsExecReturnsFalseWhenNoModHandlesTheHook(): void
    {
        $GLOBALS['modlist'] = array (
            'first' => new ModsTechsInstallDbCoreTestMod ('first', false),
            'second' => new ModsTechsInstallDbCoreTestMod ('second', false),
        );
        ModsTechsInstallDbCoreTestMod::$calls = array ();

        $this->assertFalse (ModsExec ('end_content'));
        $this->assertSame (array ('first:end_content', 'second:end_content'), ModsTechsInstallDbCoreTestMod::$calls);

        $this->assertFalse (ModsExec ('no_such_hook'));
        $this->assertSame (array ('first:end_content', 'second:end_content'), ModsTechsInstallDbCoreTestMod::$calls);

        // No mod loaded at all.
        $GLOBALS['modlist'] = array ();
        $this->assertFalse (ModsExec ('end_content'));
    }

    /**
     * ModsExecRef() passes its array argument by reference and honours the
     * return value.
     */
    public function testModsExecRefPassesTheArrayByReference(): void
    {
        $GLOBALS['modlist'] = array ('mod' => new ModsTechsInstallDbCoreTestMod ('mod'));
        ModsTechsInstallDbCoreTestMod::$calls = array ();
        $bonuses = array ('base' => 1);

        $this->assertFalse (ModsExecRef ('add_bonuses', $bonuses));
        $this->assertSame (array ('base' => 1, 'mod' => true), $bonuses);
        $this->assertSame (array ('mod:add_bonuses'), ModsTechsInstallDbCoreTestMod::$calls);

        // The schema hook of db.php uses the same dispatcher.
        $tabs = array ('users' => array ('player_id' => 'INT'));
        $this->assertFalse (ModsExecRef ('install_tabs_included', $tabs));
        $this->assertSame (
            array ('users' => array ('player_id' => 'INT'), 'test_mod_mod' => array ('id' => 'INT')),
            $tabs
        );

        // A handling mod takes over the chain.
        $GLOBALS['modlist'] = array ('mod' => new ModsTechsInstallDbCoreTestMod ('mod', true));
        ModsTechsInstallDbCoreTestMod::$calls = array ();
        $this->assertTrue (ModsExecRef ('skip_planet_update', $tabs));
        $this->assertSame (array ('mod:skip_planet_update'), ModsTechsInstallDbCoreTestMod::$calls);
    }

    /**
     * ModsExecArr() calls a hook with a single array argument passed by value.
     */
    public function testModsExecArrCallsHooksWithAValueArray(): void
    {
        $GLOBALS['modlist'] = array ('mod' => new ModsTechsInstallDbCoreTestMod ('mod'));
        ModsTechsInstallDbCoreTestMod::$calls = array ();

        $this->assertFalse (ModsExecArr ('fleet_handler', array ('mission' => FTYP_ATTACK)));
        $this->assertSame (array ('mod:fleet_handler'), ModsTechsInstallDbCoreTestMod::$calls);
    }

    /**
     * ModsExecRefArr() passes a reference array and a value array.
     */
    public function testModsExecRefArrPassesTheReferenceAndTheValue(): void
    {
        $GLOBALS['modlist'] = array ('mod' => new ModsTechsInstallDbCoreTestMod ('mod'));
        ModsTechsInstallDbCoreTestMod::$calls = array ();
        $json = array ('resource' => 1);
        $planet = array ('planet_id' => 42);

        $this->assertFalse (ModsExecRefArr ('add_resources', $json, $planet));

        $this->assertSame (array ('resource' => 1, 'mod_planet' => 42), $json);
        $this->assertSame (array ('planet_id' => 42), $planet, 'The planet array is passed by value');
        $this->assertSame (array ('mod:add_resources'), ModsTechsInstallDbCoreTestMod::$calls);
    }

    /**
     * ModsExecArrRef() passes a value array and a reference array.
     */
    public function testModsExecArrRefPassesTheValueAndTheReference(): void
    {
        $GLOBALS['modlist'] = array ('mod' => new ModsTechsInstallDbCoreTestMod ('mod'));
        ModsTechsInstallDbCoreTestMod::$calls = array ();
        $param = array ('page' => 'overview');
        $bonuses = array ();

        $this->assertFalse (ModsExecArrRef ('page_overview_get_bonus', $param, $bonuses));

        $this->assertSame (array ('mod_page' => 'overview'), $bonuses);
        $this->assertSame (array ('page' => 'overview'), $param, 'The parameter array is passed by value');
        $this->assertSame (array ('mod:page_overview_get_bonus'), ModsTechsInstallDbCoreTestMod::$calls);
    }

    /**
     * ModsExecRefRef() passes both array arguments by reference.
     */
    public function testModsExecRefRefPassesBothArraysByReference(): void
    {
        $GLOBALS['modlist'] = array ('mod' => new ModsTechsInstallDbCoreTestMod ('mod'));
        ModsTechsInstallDbCoreTestMod::$calls = array ();
        $planet = array ('planet_id' => 7);
        $eco = array ('metal' => 1);

        $this->assertFalse (ModsExecRefRef ('prod_post_process', $planet, $eco));

        $this->assertSame (array ('planet_id' => 7, 'mod_planet_touched' => true), $planet);
        $this->assertSame (array ('metal' => 1, 'mod_eco_touched' => true), $eco);
        $this->assertSame (array ('mod:prod_post_process'), ModsTechsInstallDbCoreTestMod::$calls);
    }

    /**
     * ModsExecIntRef() passes an integer and a reference array.
     */
    public function testModsExecIntRefPassesTheIntegerAndTheReference(): void
    {
        $GLOBALS['modlist'] = array ('mod' => new ModsTechsInstallDbCoreTestMod ('mod'));
        ModsTechsInstallDbCoreTestMod::$calls = array ();
        $bonus = array ();

        $this->assertFalse (ModsExecIntRef ('bonus_technology', GID_R_ENERGY, $bonus));

        $this->assertSame (array ('mod_tech' => GID_R_ENERGY), $bonus);
        $this->assertSame (array ('mod:bonus_technology'), ModsTechsInstallDbCoreTestMod::$calls);
    }

    /**
     * ModsExecRefStr() passes a reference array and a string (used by
     * AddDBRow() for the add_db_row hook).
     */
    public function testModsExecRefStrPassesTheTableName(): void
    {
        $GLOBALS['modlist'] = array ('mod' => new ModsTechsInstallDbCoreTestMod ('mod'));
        ModsTechsInstallDbCoreTestMod::$calls = array ();
        $row = array ('owner_id' => 1);

        $this->assertFalse (ModsExecRefStr ('add_db_row', $row, 'notes'));

        $this->assertSame (array ('owner_id' => 1, 'mod_table' => 'notes'), $row);
        $this->assertSame (array ('mod:add_db_row'), ModsTechsInstallDbCoreTestMod::$calls);
    }

    /**
     * ModsInstall() records the mod in the universe row and runs its
     * install() method.
     */
    public function testModsInstallAddsTheModAndRunsItsInstallCode(): void
    {
        $this->createUniverse();
        $this->assertFalse ($this->columnExists ('test_users', 'tritium'));

        ModsInstall ('BogusMod');

        $this->assertSame ('BogusMod', $GLOBALS['GlobalUni']['modlist']);
        $this->assertSame ('BogusMod', $this->dbModlist(), 'The universe row must store the new modlist');
        $this->assertTrue ($this->columnExists ('test_users', 'tritium'), 'BogusMod::install() adds the tritium column');
        $this->assertSame (1, $this->countRows ('test_queue', "type = 'AddTritium'"),
            'BogusMod::install() enqueues the tritium credit event');
    }

    /**
     * Installing an already installed mod does nothing.
     */
    public function testModsInstallDoesNotInstallTheSameModTwice(): void
    {
        $this->createUniverse();

        ModsInstall ('BogusMod');
        ModsInstall ('BogusMod');

        $this->assertSame ('BogusMod', $GLOBALS['GlobalUni']['modlist']);
        $this->assertSame ('BogusMod', $this->dbModlist());
        $this->assertSame (1, $this->countRows ('test_queue', "type = 'AddTritium'"),
            'The second ModsInstall() must not run install() again');
    }

    /**
     * Current behaviour: ModsInstall() does not verify that the mod folder (and
     * its manifest) exists, so an unknown name is stored in the modlist. The
     * admin page heals such entries (see admin_mods.php "Heal DB"). This test
     * documents the behaviour; it is reported as a robustness issue.
     */
    public function testModsInstallAcceptsAModWithoutAFolder(): void
    {
        $this->createUniverse();

        ModsInstall ('TotallyUnknownMod');

        $this->assertSame ('TotallyUnknownMod', $GLOBALS['GlobalUni']['modlist']);
        $this->assertSame ('TotallyUnknownMod', $this->dbModlist());
        $this->assertNull (ModsGetInfo ('TotallyUnknownMod'));
        $this->assertArrayNotHasKey ('TotallyUnknownMod', $GLOBALS['modlist']);
    }

    /**
     * ModsRemove() clears the modlist entry and calls uninstall() on the loaded
     * mod instance.
     */
    public function testModsRemoveUninstallsTheLoadedMod(): void
    {
        $this->createUniverse();
        ModsInstall ('BogusMod');
        $this->assertTrue ($this->columnExists ('test_users', 'tritium'));

        // ModsRemove() only calls uninstall() for mods that are loaded. The
        // universe language is ru because BogusMod ships a ru_ru loca file only.
        $GLOBALS['GlobalUni']['lang'] = 'ru';
        ModInitOne ('BogusMod');
        $this->assertArrayHasKey ('BogusMod', $GLOBALS['modlist']);

        ModsRemove ('BogusMod');

        $this->assertSame ('', $GLOBALS['GlobalUni']['modlist']);
        $this->assertSame ('', $this->dbModlist());
        $this->assertArrayNotHasKey ('BogusMod', $GLOBALS['modlist']);
        $this->assertFalse ($this->columnExists ('test_users', 'tritium'), 'BogusMod::uninstall() drops the tritium column');
        $this->assertSame (0, $this->countRows ('test_queue', "type = 'AddTritium'"));
    }

    /**
     * ModsRemove() ignores mods that are not in the modlist.
     */
    public function testModsRemoveIgnoresModsThatAreNotInstalled(): void
    {
        $this->createUniverse();
        $GLOBALS['GlobalUni']['modlist'] = 'BogusMod';
        $before = $this->dbModlist();

        ModsRemove ('GalaxyTool');

        $this->assertSame ('BogusMod', $GLOBALS['GlobalUni']['modlist']);
        $this->assertSame ($before, $this->dbModlist(), 'The universe row must be left untouched');
    }

    /**
     * ModsMoveUp()/ModsMoveDown() reorder the modlist and store it.
     */
    public function testModsMoveUpAndDownReorderTheModlist(): void
    {
        $this->createUniverse();
        $GLOBALS['GlobalUni']['modlist'] = 'BogusMod;GalaxyTool;SpaceStorm';

        ModsMoveUp ('SpaceStorm');
        $this->assertSame ('BogusMod;SpaceStorm;GalaxyTool', $GLOBALS['GlobalUni']['modlist']);
        $this->assertSame ('BogusMod;SpaceStorm;GalaxyTool', $this->dbModlist());

        ModsMoveDown ('BogusMod');
        $this->assertSame ('SpaceStorm;BogusMod;GalaxyTool', $GLOBALS['GlobalUni']['modlist']);
        $this->assertSame ('SpaceStorm;BogusMod;GalaxyTool', $this->dbModlist());
    }

    /**
     * Moving the first mod up, the last mod down, or an unknown mod anywhere
     * keeps the order.
     */
    public function testModsMoveAtTheEdgesOfTheListKeepsTheOrder(): void
    {
        $this->createUniverse();
        $GLOBALS['GlobalUni']['modlist'] = 'BogusMod;GalaxyTool';
        $before = $this->dbModlist();

        ModsMoveUp ('BogusMod');        // already first
        ModsMoveDown ('GalaxyTool');    // already last
        ModsMoveUp ('NoSuchMod');
        ModsMoveDown ('NoSuchMod');

        $this->assertSame ('BogusMod;GalaxyTool', $GLOBALS['GlobalUni']['modlist']);
        $this->assertSame ($before, $this->dbModlist(), 'Nothing was reordered, so the universe row is unchanged');
    }

    /**
     * db.php runs the install_tabs_included hook while building the schema, so
     * an enabled mod can add its columns (and tables) to the database.
     */
    public function testCreateDbTablesRunsTheModSchemaHook(): void
    {
        $this->createUniverse();
        // The universe language is ru because BogusMod ships a ru_ru loca file
        // only (loca_add() would warn for en).
        $GLOBALS['GlobalUni']['lang'] = 'ru';
        $GLOBALS['GlobalUni']['modlist'] = 'BogusMod';

        ModsInit();
        $this->assertInstanceOf (BogusMod::class, $GLOBALS['modlist']['BogusMod']);

        CreateDBTables();

        $this->assertTrue ($this->columnExists ('test_users', 'tritium'),
            'CreateDBTables() must call ModsExecRef(install_tabs_included) so a mod can extend the schema');

        // The same hook can add a whole new table.
        $GLOBALS['modlist'] = array ('testmod' => new ModsTechsInstallDbCoreTestMod ('testmod'));
        CreateDBTables();

        $this->assertTrue ($this->tableExists ('test_test_mod_testmod'));
        $this->assertFalse ($this->columnExists ('test_users', 'tritium'),
            'The replacement modlist must not keep the columns of the removed mod');
    }
}
