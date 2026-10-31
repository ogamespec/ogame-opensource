<?php

// Tests for the resource production module (game/core/prod.php).
//
// The module covers four areas:
//  - build conditions and costs (TechMeetRequirement, TechPrice,
//    TechPriceInPoints, TechDuration, IsEnoughResources),
//  - research lab networks (ResearchNetwork),
//  - resource production and storage (ProdBonus, ConsBonus, ProdResources,
//    SetDefaultProduction, GetUpdatePlanet, store_capacity),
//  - score values (PlanetPrice, FleetPrice).
//
// These tests run against the real database layer with the in-memory SQLite
// backend (DB_CONNECTION=sqlite, DB_DATABASE=:memory:, see phpunit.xml and
// testing/bootstrap.php). FixtureBuilder creates the real game schema and a
// 3-player test universe, so the DB-backed functions (ResearchNetwork,
// GetUpdatePlanet) are exercised with real rows. Each test method runs in a
// separate PHP process and starts with a fresh in-memory database.
//
// All expected values below were confirmed against the production code; the
// resource numbers are the exact results of the 0.84 production formulas for
// the fixture planet (Metal Mine 5, Crystal Mine 3, Deuterium Synthesizer 2,
// Solar Plant 3, Fusion Reactor 1, 8 Solar Satellites, temperature 65,
// universe speed 1).

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * A mod that adds a production factor (2.0) and a consumption factor (0.5),
 * used to cover the bonus_prod / bonus_cons hooks of prod.php.
 */
class ProdCoreTestBonusMod extends GameMod
{
    public function install() : void {}
    public function uninstall() : void {}
    public function init() : void {}

    public function bonus_prod (array $param, array &$bonus) : bool
    {
        $bonus[] = 2.0;
        return true;
    }

    public function bonus_cons (array $param, array &$bonus) : bool
    {
        $bonus[] = 0.5;
        return true;
    }
}

/**
 * A mod that freezes every planet update, used to cover the
 * skip_planet_update hook of GetUpdatePlanet().
 */
class ProdCoreTestFreezeMod extends GameMod
{
    public function install() : void {}
    public function uninstall() : void {}
    public function init() : void {}

    public function skip_planet_update (array &$planet) : bool
    {
        return true;
    }
}

/**
 * A mod that rewrites the final economy result, used to cover the
 * prod_post_process hook of ProdResources().
 */
class ProdCoreTestPostProcessMod extends GameMod
{
    public function install() : void {}
    public function uninstall() : void {}
    public function init() : void {}

    public function prod_post_process (array &$planet, array &$eco) : bool
    {
        $eco['balance'][GID_RC_METAL] = 12345;
        $planet['post_processed'] = true;
        return true;
    }
}

#[RunTestsInSeparateProcesses]
class ProdCoreTest extends TestCase
{
    private FixtureBuilder $fixture;

    protected function setUp(): void
    {
        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');

        // GetUpdatePlanet / ProdResources read the universe from $GlobalUni;
        // the mod hooks read $modlist (no mods unless a test installs one).
        global $GlobalUni, $db_prefix, $modlist;
        $GlobalUni = $this->fixture->getUniData();
        $db_prefix = $this->fixture->getDbPrefix();
        $modlist = array ();
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Build a complete planet array: all buildings/fleet/defense/resources at 0
     * and every production coefficient at 1, so ProdResources never hits an
     * undefined key.
     */
    private function makePlanet(array $overrides = array()) : array
    {
        global $buildmap, $fleetmap, $defmap, $resourcemap;

        $planet = array ('type' => PTYP_PLANET, 'temp' => 0, 'factor' => 1);
        foreach ($buildmap as $gid) $planet[$gid] = 0;
        foreach ($fleetmap as $gid) $planet[$gid] = 0;
        foreach ($defmap as $gid) $planet[$gid] = 0;
        foreach ($resourcemap as $gid) $planet[$gid] = 0;
        foreach (array ('prod'.GID_B_METAL_MINE, 'prod'.GID_B_CRYS_MINE, 'prod'.GID_B_DEUT_SYNTH,
                        'prod'.GID_B_SOLAR, 'prod'.GID_B_FUSION, 'prod'.GID_F_SAT) as $key) {
            $planet[$key] = 1;
        }

        return array_replace($planet, $overrides);
    }

    /**
     * Build a complete research/cost user array. All officers are inactive
     * (expiry timestamp 0) unless a test overrides them.
     */
    private function makeUser(array $overrides = array()) : array
    {
        global $resmap, $resourcemap;

        $user = array ();
        foreach ($resmap as $gid) $user[$gid] = 0;
        foreach ($resourcemap as $gid) $user[$gid] = 0;
        foreach (array ('com_until', 'adm_until', 'eng_until', 'geo_until', 'tec_until') as $key) {
            $user[$key] = 0;
        }

        return array_replace($user, $overrides);
    }

    /**
     * Install the ProdCoreTestBonusMod (production x2, consumption x0.5).
     */
    private function installBonusMod() : void
    {
        global $modlist;
        $modlist['prodtest'] = new ProdCoreTestBonusMod();
    }

    /**
     * Set a research lab level of another planet directly in the database.
     */
    private function setPlanetLab(int $planetId, int $level) : void
    {
        global $db_prefix;
        dbquery ("UPDATE {$db_prefix}planets SET `".GID_B_RES_LAB."` = $level WHERE planet_id = $planetId");
    }

    /**
     * Set the Intergalactic Research Network level of player 1.
     */
    private function setPlayerIgn(int $level) : void
    {
        global $db_prefix;
        dbquery ("UPDATE {$db_prefix}users SET `".GID_R_IGN."` = $level WHERE player_id = 1");
        // ResearchNetwork loads the player through the script-lifetime cache.
        InvalidateUserCache();
    }

    // ========================================================================
    // store_capacity
    // ========================================================================

    /**
     * The storage capacity follows 100000 + 50000 * ceil(1.6^level - 1).
     * Level 5 is the classic 600 000 metal storage of the early game.
     */
    public function testStoreCapacityGrowsWithTheBuildingLevel(): void
    {
        $this->assertSame (100000,  store_capacity (0));
        $this->assertSame (150000,  store_capacity (1));
        $this->assertSame (200000,  store_capacity (2));
        $this->assertSame (300000,  store_capacity (3));
        $this->assertSame (400000,  store_capacity (4));
        $this->assertSame (600000,  store_capacity (5));
        $this->assertSame (900000,  store_capacity (6));
        $this->assertSame (1400000, store_capacity (7));
    }

    /**
     * The capacity must be strictly increasing with the level.
     */
    public function testStoreCapacityIsStrictlyIncreasing(): void
    {
        $previous = 0;
        for ($lvl = 0; $lvl <= 12; $lvl++) {
            $capacity = store_capacity ($lvl);
            $this->assertGreaterThan ($previous, $capacity);
            $previous = $capacity;
        }
    }

    // ========================================================================
    // TechPrice / TechPriceInPoints
    // ========================================================================

    /**
     * At level 1 the cost equals the level-1 cost table; every resource of the
     * resource map is present in the result, even when it costs nothing.
     */
    public function testTechPriceAtLevelOneIsTheLevelOneCost(): void
    {
        $cost = TechPrice (GID_B_METAL_MINE, 1);

        $this->assertSame (array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM, GID_RC_ENERGY, GID_RC_DM),
            array_keys ($cost));
        $this->assertEquals (60, $cost[GID_RC_METAL]);
        $this->assertEquals (15, $cost[GID_RC_CRYSTAL]);
        $this->assertEquals (0, $cost[GID_RC_DEUTERIUM]);
        $this->assertEquals (0, $cost[GID_RC_ENERGY]);
        $this->assertEquals (0, $cost[GID_RC_DM]);
    }

    /**
     * The cost grows by the object's factor per level: a Crystal Mine
     * (factor 1.6) at level 3 costs 48*1.6^2 metal and 24*1.6^2 crystal, a
     * Robotics Factory (factor 2) at level 2 costs 400*2 metal, 120*2 crystal
     * and 200*2 deuterium.
     */
    public function testTechPriceAppliesTheExponentialFactor(): void
    {
        $crystalMine = TechPrice (GID_B_CRYS_MINE, 3);
        $this->assertEqualsWithDelta (122.88, $crystalMine[GID_RC_METAL], 1e-9);
        $this->assertEqualsWithDelta (61.44, $crystalMine[GID_RC_CRYSTAL], 1e-9);

        $robots = TechPrice (GID_B_ROBOTS, 2);
        $this->assertEquals (800, $robots[GID_RC_METAL]);
        $this->assertEquals (240, $robots[GID_RC_CRYSTAL]);
        $this->assertEquals (400, $robots[GID_RC_DEUTERIUM]);
    }

    /**
     * An object without an entry in the cost table costs nothing at all, but
     * still returns every resource key.
     */
    public function testTechPriceOfAnUnknownObjectIsZero(): void
    {
        $cost = TechPrice (999999, 1);

        $this->assertSame (array (GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
            GID_RC_ENERGY => 0, GID_RC_DM => 0), $cost);
    }

    /**
     * Level 0 is not a valid game level; the raw formula divides the level-1
     * cost by the factor (60/1.5 = 40 metal, 15/1.5 = 10 crystal). This test
     * documents the current behaviour of the formula at that boundary.
     */
    public function testTechPriceAtLevelZeroUsesTheNegativePower(): void
    {
        $cost = TechPrice (GID_B_METAL_MINE, 0);

        $this->assertEqualsWithDelta (40.0, $cost[GID_RC_METAL], 1e-9);
        $this->assertEqualsWithDelta (10.0, $cost[GID_RC_CRYSTAL], 1e-9);
    }

    /**
     * Only metal, crystal and deuterium are counted as score points; energy
     * and dark matter are ignored.
     */
    public function testTechPriceInPointsSumsOnlyScoringResources(): void
    {
        $cost = array (
            GID_RC_METAL => 100, GID_RC_CRYSTAL => 50, GID_RC_DEUTERIUM => 25,
            GID_RC_ENERGY => 1000, GID_RC_DM => 500,
        );

        $this->assertSame (175, TechPriceInPoints ($cost));
        $this->assertSame (0, TechPriceInPoints (array ()));
        $this->assertSame (0, TechPriceInPoints (array (999999 => 42)));
    }

    /**
     * The points are cast to int, so the fractional part of a cost is dropped
     * (Metal Mine level 3 costs 135 + 33.75 = 168.75 points -> 168).
     */
    public function testTechPriceInPointsTruncatesFractions(): void
    {
        $this->assertSame (168, TechPriceInPoints (TechPrice (GID_B_METAL_MINE, 3)));
    }

    // ========================================================================
    // TechDuration
    // ========================================================================

    /**
     * The build time is (metal+crystal)/(factor*(1+robots)) * 0.5^nanites *
     * 3600 / speed, floored. Metal Mine level 1: 75/2500*3600 = 107 s.
     */
    public function testTechDurationBuildingBaseCase(): void
    {
        $this->assertSame (107, TechDuration (GID_B_METAL_MINE, 1, PROD_BUILDING_DURATION_FACTOR, 0, 0, 1.0));
    }

    /**
     * A Robotics Factory level of 2 divides the time by 3 (Robotics Factory
     * level 1 costs 520 structure points: 520/(2500*3)*3600 = 249 s).
     */
    public function testTechDurationScalesWithTheRoboticsFactory(): void
    {
        $this->assertSame (249, TechDuration (GID_B_ROBOTS, 1, PROD_BUILDING_DURATION_FACTOR, 2, 0, 1.0));
    }

    /**
     * Every Nanite Factory level halves the time.
     */
    public function testTechDurationIsHalvedByTheNaniteFactory(): void
    {
        $this->assertSame (53, TechDuration (GID_B_METAL_MINE, 1, PROD_BUILDING_DURATION_FACTOR, 0, 1, 1.0));
    }

    /**
     * The universe speed divides the time (107 / 1.5 = 71 s, 107 / 2 = 53 s).
     */
    public function testTechDurationIsDividedByTheUniverseSpeed(): void
    {
        $this->assertSame (71, TechDuration (GID_B_METAL_MINE, 1, PROD_BUILDING_DURATION_FACTOR, 0, 0, 1.5));
        $this->assertSame (53, TechDuration (GID_B_METAL_MINE, 1, PROD_BUILDING_DURATION_FACTOR, 0, 0, 2.0));
    }

    /**
     * Research uses the research duration factor with the lab level in place
     * of the robotics level: Energy Technology level 1 (800 crystal structure)
     * in a level-4 lab: 800/(1000*5)*3600 = 576 s.
     */
    public function testTechDurationResearchUsesTheLabLevel(): void
    {
        $this->assertSame (576, TechDuration (GID_R_ENERGY, 1, PROD_RESEARCH_DURATION_FACTOR, 4, 0, 1.0));
    }

    /**
     * The duration never drops below one second, however fast the build is.
     */
    public function testTechDurationIsNeverShorterThanOneSecond(): void
    {
        $this->assertSame (1, TechDuration (GID_B_METAL_MINE, 1, PROD_BUILDING_DURATION_FACTOR, 1000000, 0, 1.0));
        $this->assertSame (1, TechDuration (999999, 1, PROD_BUILDING_DURATION_FACTOR, 0, 0, 1.0));
    }

    /**
     * Higher levels cost exponentially more and therefore take longer
     * (Metal Mine level 3: 168.75/2500*3600 = 243 s).
     */
    public function testTechDurationGrowsWithTheLevel(): void
    {
        $this->assertSame (243, TechDuration (GID_B_METAL_MINE, 3, PROD_BUILDING_DURATION_FACTOR, 0, 0, 1.0));
        $this->assertGreaterThan (
            TechDuration (GID_B_METAL_MINE, 1, PROD_BUILDING_DURATION_FACTOR, 0, 0, 1.0),
            TechDuration (GID_B_METAL_MINE, 3, PROD_BUILDING_DURATION_FACTOR, 0, 0, 1.0)
        );
    }

    // ========================================================================
    // TechMeetRequirement
    // ========================================================================

    /**
     * The fixture home planet (planet 1) of player 1 has a research lab of 4,
     * a shipyard of 3, robotics 2 and a missile silo of 3. Weapons Technology
     * only needs a lab of 4, so it is available; Shielding Technology needs a
     * lab of 6 and Hyperspace Technology needs 7, so they are not.
     */
    public function testTechMeetRequirementChecksResearchLabLevels(): void
    {
        $user = LoadUser (1);
        $planet = LoadPlanetById (1);

        $this->assertTrue  (TechMeetRequirement ($user, $planet, GID_R_WEAPON));
        $this->assertFalse (TechMeetRequirement ($user, $planet, GID_R_SHIELD));
        $this->assertFalse (TechMeetRequirement ($user, $planet, GID_R_HYPERSPACE));
    }

    /**
     * The Gauss Cannon requires a shipyard of 6 (and Weapons Technology 3,
     * Energy Technology 6 and Shielding Technology 1); the home planet has
     * only a shipyard of 3, so it cannot be built there.
     */
    public function testTechMeetRequirementChecksBuildingRequirements(): void
    {
        $user = LoadUser (1);
        $planet = LoadPlanetById (1);

        $this->assertFalse (TechMeetRequirement ($user, $planet, GID_D_GAUSS));
        $this->assertTrue  (TechMeetRequirement ($user, $planet, GID_F_LF));
    }

    /**
     * A moon may only build the moon structures (CanBuildTab): the Lunar Base
     * and the Sensor Phalanx are available on the fixture moon (Lunar Base 5),
     * the Metal Mine is not, and the Jump Gate needs Hyperspace Technology 7
     * which player 1 does not have.
     */
    public function testTechMeetRequirementHonoursThePlanetTypeRestrictions(): void
    {
        $user = LoadUser (1);
        $moon = LoadPlanetById (10);      // player 1's first moon

        $this->assertSame (PTYP_MOON, (int)$moon['type']);
        $this->assertTrue  (TechMeetRequirement ($user, $moon, GID_B_LUNAR_BASE));
        $this->assertTrue  (TechMeetRequirement ($user, $moon, GID_B_PHALANX));
        $this->assertFalse (TechMeetRequirement ($user, $moon, GID_B_METAL_MINE));
        $this->assertFalse (TechMeetRequirement ($user, $moon, GID_B_JUMP_GATE));
    }

    /**
     * A research needs a lab on the planet where it is started: the far space
     * object (planet 16) has no research lab, so nothing can be researched
     * there.
     */
    public function testTechMeetRequirementNeedsALabOnThePlanet(): void
    {
        $user = LoadUser (1);

        $this->assertFalse (TechMeetRequirement ($user, LoadPlanetById (16), GID_R_WEAPON));
    }

    /**
     * An object that is neither a building nor listed in the requirement table
     * passes the check (the function is not a general object validator).
     */
    public function testTechMeetRequirementPassesForUnknownObjects(): void
    {
        $this->assertTrue (TechMeetRequirement (LoadUser (1), LoadPlanetById (1), 999999));
    }

    // ========================================================================
    // IsEnoughResources
    // ========================================================================

    /**
     * The cost is paid from the player's account (research) and from the
     * planet (buildings). An exact amount is enough; one unit less is not.
     */
    public function testIsEnoughResourcesChecksUserAndPlanetHoldings(): void
    {
        $user = array (GID_RC_METAL => 100);
        $planet = array (GID_RC_CRYSTAL => 50);

        $this->assertTrue  (IsEnoughResources ($user, $planet, array (GID_RC_METAL => 100, GID_RC_CRYSTAL => 50)));
        $this->assertFalse (IsEnoughResources ($user, $planet, array (GID_RC_METAL => 101)));
        $this->assertFalse (IsEnoughResources ($user, $planet, array (GID_RC_CRYSTAL => 51)));
        $this->assertFalse (IsEnoughResources ($user, $planet, array (GID_RC_DEUTERIUM => 1)));
    }

    /**
     * A resource that neither the player nor the planet holds makes the check
     * fail, an empty cost always passes and a non-positive cost entry is
     * ignored (it can never be underpaid).
     */
    public function testIsEnoughResourcesEdgeCases(): void
    {
        $user = array (GID_RC_METAL => 100);
        $planet = array (GID_RC_CRYSTAL => 50);

        $this->assertFalse (IsEnoughResources ($user, $planet, array (999999 => 1)));
        $this->assertTrue  (IsEnoughResources ($user, $planet, array ()));
        $this->assertTrue  (IsEnoughResources ($user, $planet, array (GID_RC_METAL => 0)));
        $this->assertTrue  (IsEnoughResources ($user, $planet, array (GID_RC_METAL => -5)));
    }

    // ========================================================================
    // ProdBonus / ConsBonus
    // ========================================================================

    /**
     * Without officers the only production bonus is the planet's production
     * coefficient; energy has no bonus at all.
     */
    public function testProdBonusWithoutOfficers(): void
    {
        $uni = $this->fixture->getUniData();
        $user = $this->makeUser ();
        $planet = $this->makePlanet (array ('factor' => 1));

        $bonus = array ();
        ProdBonus ($uni, $user, $planet, GID_RC_METAL, $bonus);
        $this->assertSame (array (1), $bonus);

        $bonus = array ();
        ProdBonus ($uni, $user, $planet, GID_RC_ENERGY, $bonus);
        $this->assertSame (array (), $bonus);

        $bonus = array ();
        ProdBonus ($uni, $user, $planet, GID_RC_DM, $bonus);
        $this->assertSame (array (), $bonus);
    }

    /**
     * The Geologist officer adds 10% to metal, crystal and deuterium, the
     * Engineer adds 10% to energy.
     */
    public function testProdBonusWithOfficers(): void
    {
        $uni = $this->fixture->getUniData();
        $planet = $this->makePlanet (array ('factor' => 2.5));
        $geologist = $this->makeUser (array ('geo_until' => time() + 86400));
        $engineer = $this->makeUser (array ('eng_until' => time() + 86400));

        $bonus = array ();
        ProdBonus ($uni, $geologist, $planet, GID_RC_METAL, $bonus);
        $this->assertSame (array (1.1, 2.5), $bonus);

        $bonus = array ();
        ProdBonus ($uni, $geologist, $planet, GID_RC_CRYSTAL, $bonus);
        $this->assertSame (array (1.1, 2.5), $bonus);

        $bonus = array ();
        ProdBonus ($uni, $geologist, $planet, GID_RC_DEUTERIUM, $bonus);
        $this->assertSame (array (1.1, 2.5), $bonus);

        $bonus = array ();
        ProdBonus ($uni, $engineer, $planet, GID_RC_ENERGY, $bonus);
        $this->assertSame (array (1.1), $bonus);

        // An expired officer adds nothing.
        $expired = $this->makeUser (array ('geo_until' => time() - 86400, 'eng_until' => 1));
        $bonus = array ();
        ProdBonus ($uni, $expired, $planet, GID_RC_METAL, $bonus);
        $this->assertSame (array (2.5), $bonus);
        $bonus = array ();
        ProdBonus ($uni, $expired, $planet, GID_RC_ENERGY, $bonus);
        $this->assertSame (array (), $bonus);
    }

    /**
     * The bonus_prod mod hook can append additional factors.
     */
    public function testProdBonusIncludesModFactors(): void
    {
        $this->installBonusMod ();

        $uni = $this->fixture->getUniData ();
        $user = $this->makeUser ();
        $planet = $this->makePlanet (array ('factor' => 1));

        $bonus = array ();
        ProdBonus ($uni, $user, $planet, GID_RC_METAL, $bonus);
        $this->assertSame (array (1, 2.0), $bonus);
    }

    /**
     * The consumption bonus list is empty without mods; the bonus_cons hook
     * can append factors.
     */
    public function testConsBonusWithoutAndWithMods(): void
    {
        $uni = $this->fixture->getUniData ();
        $user = $this->makeUser ();
        $planet = $this->makePlanet ();

        $bonus = array ();
        ConsBonus ($uni, $user, $planet, GID_RC_ENERGY, $bonus);
        $this->assertSame (array (), $bonus);

        $this->installBonusMod ();
        $bonus = array ();
        ConsBonus ($uni, $user, $planet, GID_RC_ENERGY, $bonus);
        $this->assertSame (array (0.5), $bonus);
    }

    // ========================================================================
    // ProdResources
    // ========================================================================

    /**
     * The full production of the fixture home planet with the officers of
     * player 1 active (Geologist + Engineer, so every resource gets +10%):
     *
     *  - Solar Plant 3 = 79, Fusion Reactor 1 (energy tech 5) = 33,
     *    8 Solar Satellites (temp 65) = 368 energy,
     *  - Metal Mine 5 = 241, Crystal Mine 3 = 79, Deuterium Synthesizer 2 at
     *    temp 65 = 25.68,
     *  - energy consumption 81 + 40 + 49 = 170, deuterium consumption 11,
     *  - net production 529 energy / 286 metal (241*1.1 + 20 natural) /
     *    97 crystal (79*1.1 + 10 natural) / 29 deuterium (25.68*1.1),
     *  - balance = net production - consumption.
     */
    public function testProdResourcesWithActiveOfficers(): void
    {
        $uni = $this->fixture->getUniData ();
        $planet = LoadPlanetById (1);
        $user = LoadUser (1);

        ProdResources ($uni, $user, $planet);

        // Raw production per building.
        $this->assertEquals (79, $planet['prod'][GID_B_SOLAR]);
        $this->assertEquals (33, $planet['prod'][GID_B_FUSION]);
        $this->assertEquals (368, $planet['prod'][GID_F_SAT]);
        $this->assertEquals (241, $planet['prod'][GID_B_METAL_MINE]);
        $this->assertEquals (79, $planet['prod'][GID_B_CRYS_MINE]);
        $this->assertEqualsWithDelta (25.68, $planet['prod'][GID_B_DEUT_SYNTH], 1e-9);

        // Raw consumption per building (the fusion reactor eats deuterium).
        $this->assertEquals (81, $planet['cons'][GID_B_METAL_MINE]);
        $this->assertEquals (40, $planet['cons'][GID_B_CRYS_MINE]);
        $this->assertEquals (49, $planet['cons'][GID_B_DEUT_SYNTH]);
        $this->assertEquals (11, $planet['cons'][GID_B_FUSION]);

        // Bonuses (Geologist 1.1 for the mines, Engineer 1.1 for energy).
        $this->assertEqualsWithDelta (265.1, $planet['prod_with_bonus'][GID_B_METAL_MINE], 1e-9);
        $this->assertEqualsWithDelta (404.8, $planet['prod_with_bonus'][GID_F_SAT], 1e-9);

        // Totals.
        $this->assertEquals (529, $planet['net_prod'][GID_RC_ENERGY]);
        $this->assertEquals (286, $planet['net_prod'][GID_RC_METAL]);
        $this->assertEquals (97,  $planet['net_prod'][GID_RC_CRYSTAL]);
        $this->assertEquals (29,  $planet['net_prod'][GID_RC_DEUTERIUM]);
        $this->assertEquals (170, $planet['net_cons'][GID_RC_ENERGY]);
        $this->assertEquals (11,  $planet['net_cons'][GID_RC_DEUTERIUM]);
        $this->assertEquals (0,   $planet['net_cons'][GID_RC_METAL]);
        $this->assertEquals (0,   $planet['net_cons'][GID_RC_CRYSTAL]);
        $this->assertEquals (359, $planet['balance'][GID_RC_ENERGY]);
        $this->assertEquals (286, $planet['balance'][GID_RC_METAL]);
        $this->assertEquals (97,  $planet['balance'][GID_RC_CRYSTAL]);
        $this->assertEquals (18,  $planet['balance'][GID_RC_DEUTERIUM]);

        // Enough energy: the production coefficient stays at 1.
        $this->assertEquals (1, $planet['factor']);
    }

    /**
     * Without any officer the production has no bonus factor: the net
     * production equals ceil(raw production) plus the natural production
     * (metal +20, crystal +10 per hour, multiplied by the universe speed).
     */
    public function testProdResourcesWithoutOfficers(): void
    {
        $uni = $this->fixture->getUniData ();
        $planet = LoadPlanetById (1);
        $user = $this->makeUser (array (GID_R_ENERGY => 5));

        ProdResources ($uni, $user, $planet);

        $this->assertEquals (480, $planet['net_prod'][GID_RC_ENERGY]);
        $this->assertEquals (261, $planet['net_prod'][GID_RC_METAL]);
        $this->assertEquals (89,  $planet['net_prod'][GID_RC_CRYSTAL]);
        $this->assertEquals (26,  $planet['net_prod'][GID_RC_DEUTERIUM]);
        $this->assertEquals (310, $planet['balance'][GID_RC_ENERGY]);
        $this->assertEquals (261, $planet['balance'][GID_RC_METAL]);
        $this->assertEquals (89,  $planet['balance'][GID_RC_CRYSTAL]);
        $this->assertEquals (15,  $planet['balance'][GID_RC_DEUTERIUM]);

        // Without a bonus the "with bonus" values are the raw ones.
        $this->assertEquals ($planet['prod'], $planet['prod_with_bonus']);
    }

    /**
     * When the energy balance is negative the production coefficient drops:
     * with only a Metal Mine of 5 (81 energy consumed, nothing produced) the
     * coefficient is max(0, 1 - 81/81) = 0, so all mines stop producing and
     * only the natural production remains.
     */
    public function testProdResourcesEnergyShortageStopsTheMines(): void
    {
        $uni = $this->fixture->getUniData ();
        $planet = $this->makePlanet (array (GID_B_METAL_MINE => 5));
        $user = $this->makeUser ();

        ProdResources ($uni, $user, $planet);

        $this->assertEquals (0,   $planet['factor']);
        $this->assertEquals (81,  $planet['net_cons'][GID_RC_ENERGY]);
        $this->assertEquals (-81, $planet['balance'][GID_RC_ENERGY]);
        $this->assertEquals (0,   $planet['prod_with_bonus'][GID_B_METAL_MINE]);
        $this->assertEquals (20,  $planet['net_prod'][GID_RC_METAL]);
        $this->assertEquals (10,  $planet['net_prod'][GID_RC_CRYSTAL]);
        $this->assertEquals (20,  $planet['balance'][GID_RC_METAL]);
        $this->assertEquals (10,  $planet['balance'][GID_RC_CRYSTAL]);
        $this->assertEquals (0,   $planet['net_prod'][GID_RC_ENERGY]);
    }

    /**
     * The bonus_prod mod hook multiplies every production: metal 241*1*2 =
     * 482 (+20 natural = 502) and energy (79+33+368)*2 = 960.
     */
    public function testProdResourcesAppliesModProductionBonus(): void
    {
        $this->installBonusMod ();

        $uni = $this->fixture->getUniData ();
        $planet = LoadPlanetById (1);
        $user = $this->makeUser (array (GID_R_ENERGY => 5));

        ProdResources ($uni, $user, $planet);

        $this->assertEquals (502, $planet['net_prod'][GID_RC_METAL]);
        $this->assertEquals (960, $planet['net_prod'][GID_RC_ENERGY]);
        $this->assertEqualsWithDelta (482.0, $planet['prod_with_bonus'][GID_B_METAL_MINE], 1e-9);
    }

    /**
     * The bonus_cons mod hook multiplies every consumption: the energy
     * consumption 81 + 40 + 49 is halved and rounded up per building
     * (41 + 20 + 25 = 86), and the deuterium consumption 11 becomes 6. The
     * same mod also doubles the production (960), so the balance is
     * 960 - 86 = 874.
     */
    public function testProdResourcesAppliesModConsumptionBonus(): void
    {
        $this->installBonusMod ();

        $uni = $this->fixture->getUniData ();
        $planet = LoadPlanetById (1);
        $user = $this->makeUser (array (GID_R_ENERGY => 5));

        ProdResources ($uni, $user, $planet);

        $this->assertEquals (86, $planet['net_cons'][GID_RC_ENERGY]);
        $this->assertEquals (6,  $planet['net_cons'][GID_RC_DEUTERIUM]);
        $this->assertEquals (874, $planet['balance'][GID_RC_ENERGY]);
    }

    /**
     * The prod_post_process mod hook receives the planet and the finished
     * economy array by reference and runs right before the result is written
     * into the planet.
     */
    public function testProdResourcesRunsThePostProcessModHook(): void
    {
        global $modlist;

        $modlist['post'] = new ProdCoreTestPostProcessMod ();

        $uni = $this->fixture->getUniData ();
        $planet = $this->makePlanet (array (GID_B_METAL_MINE => 1));
        $user = $this->makeUser ();

        ProdResources ($uni, $user, $planet);

        $this->assertSame (12345, $planet['balance'][GID_RC_METAL]);
        $this->assertTrue ($planet['post_processed']);
    }

    /**
     * ProdResources writes every result field into the planet array. Even a
     * planet without any building gets an entry per production rule (the
     * closures are executed and return 0.0).
     */
    public function testProdResourcesWritesAllResultFields(): void
    {
        $uni = $this->fixture->getUniData ();
        $planet = $this->makePlanet ();
        $user = $this->makeUser ();

        ProdResources ($uni, $user, $planet);

        foreach (array ('prod', 'prod_with_bonus', 'cons', 'cons_with_bonus', 'net_prod', 'net_cons',
                        'balance', 'factor') as $field) {
            $this->assertArrayHasKey ($field, $planet);
        }

        // The energy producers are evaluated first ($prodPriority), then the
        // metal/crystal/deuterium mines.
        $this->assertSame (array (GID_B_SOLAR, GID_B_FUSION, GID_F_SAT, GID_B_METAL_MINE,
            GID_B_CRYS_MINE, GID_B_DEUT_SYNTH), array_keys ($planet['prod']));
        $this->assertSame (array (GID_B_METAL_MINE, GID_B_CRYS_MINE, GID_B_DEUT_SYNTH, GID_B_FUSION),
            array_keys ($planet['cons']));
        foreach ($planet['prod'] as $gid => $value) $this->assertEquals (0, $value);
        foreach ($planet['cons'] as $gid => $value) $this->assertEquals (0, $value);

        foreach ($GLOBALS['prodPriority'] as $rc) {
            $this->assertArrayHasKey ($rc, $planet['net_prod']);
            $this->assertArrayHasKey ($rc, $planet['net_cons']);
            $this->assertArrayHasKey ($rc, $planet['balance']);
        }
    }

    /**
     * SetDefaultProduction resets the coefficient and all production arrays;
     * only the four priority resources keep zeroed entries.
     */
    public function testSetDefaultProductionResetsEverything(): void
    {
        global $prodPriority;

        $planet = LoadPlanetById (1);
        SetDefaultProduction ($planet);

        $this->assertSame (0, $planet['factor']);
        $this->assertSame (array (), $planet['prod']);
        $this->assertSame (array (), $planet['prod_with_bonus']);
        $this->assertSame (array (), $planet['cons']);
        $this->assertSame (array (), $planet['cons_with_bonus']);

        $this->assertSame (count ($prodPriority), count ($planet['net_prod']));
        foreach ($prodPriority as $rc) {
            $this->assertSame (0, $planet['net_prod'][$rc]);
            $this->assertSame (0, $planet['net_cons'][$rc]);
            $this->assertSame (0, $planet['balance'][$rc]);
        }
    }

    // ========================================================================
    // GetUpdatePlanet
    // ========================================================================

    /**
     * An unknown planet id returns null.
     */
    public function testGetUpdatePlanetReturnsNullForUnknownPlanet(): void
    {
        $this->assertNull (GetUpdatePlanet (999999, time ()));
    }

    /**
     * One hour of production is credited to the planet (metal 25000 + 286,
     * crystal 15000 + 97, deuterium 8000 + 18), the storage caps are filled
     * from the storage buildings, the energy balance is exposed as a virtual
     * energy resource and both the planet row and its lastpeek are persisted.
     */
    public function testGetUpdatePlanetCreditsProductionSinceLastPeek(): void
    {
        $planet = LoadPlanetById (1);
        $to = (int)$planet['lastpeek'] + 3600;

        $result = GetUpdatePlanet (1, $to);

        $this->assertSame (25286, (int)$result[GID_RC_METAL]);
        $this->assertSame (15097, (int)$result[GID_RC_CRYSTAL]);
        $this->assertSame (8018,  (int)$result[GID_RC_DEUTERIUM]);
        $this->assertSame (359,   (int)$result[GID_RC_ENERGY]);      // virtual energy = energy balance
        $this->assertSame ($to,   (int)$result['lastpeek']);
        $this->assertSame (600000, (int)$result['max'.GID_RC_METAL]);    // metal storage 5
        $this->assertSame (600000, (int)$result['max'.GID_RC_CRYSTAL]);  // crystal storage 5
        $this->assertSame (400000, (int)$result['max'.GID_RC_DEUTERIUM]); // deuterium storage 4

        // The same values must be in the database now.
        $reloaded = LoadPlanetById (1);
        $this->assertSame (25286, (int)$reloaded[GID_RC_METAL]);
        $this->assertSame (15097, (int)$reloaded[GID_RC_CRYSTAL]);
        $this->assertSame (8018,  (int)$reloaded[GID_RC_DEUTERIUM]);
        $this->assertSame ($to,   (int)$reloaded['lastpeek']);
    }

    /**
     * Resource growth is capped by the storage capacity: 599 000 metal with a
     * 600 000 capacity grows by 24 h * 286/h = 6864 and is clamped to
     * 600 000.
     */
    public function testGetUpdatePlanetCapsResourcesAtStorageCapacity(): void
    {
        global $db_prefix;
        $now = time ();

        dbquery ("UPDATE {$db_prefix}planets SET `".GID_RC_METAL."` = 599000, lastpeek = ".($now - 86400)." WHERE planet_id = 1");

        $result = GetUpdatePlanet (1, $now);

        $this->assertSame (600000, (int)$result[GID_RC_METAL]);
        $this->assertSame (600000, (int)$result['max'.GID_RC_METAL]);
        $this->assertSame (600000, (int)LoadPlanetById (1)[GID_RC_METAL]);
    }

    /**
     * A non-planet galaxy object (debris field) gets default (zero)
     * production, no storage capacities and no resources credited; its
     * lastpeek is left untouched.
     */
    public function testGetUpdatePlanetOnDebrisFieldChangesNothing(): void
    {
        $result = GetUpdatePlanet (14, time ());

        $this->assertSame (PTYP_DF, (int)$result['type']);
        $this->assertSame (0, $result['factor']);
        $this->assertSame (array (), $result['prod']);
        $this->assertSame (0, $result['net_prod'][GID_RC_METAL]);
        $this->assertSame (0, (int)$result['max'.GID_RC_METAL]);
        $this->assertEquals (500000, (int)$result[GID_RC_METAL]);      // not credited

        $reloaded = LoadPlanetById (14);
        $this->assertNull ($reloaded['lastpeek']);
        $this->assertEquals (500000, (int)$reloaded[GID_RC_METAL]);
    }

    /**
     * A regular planet whose owner does not exist is returned unchanged (no
     * production, no storage caps, no write).
     */
    public function testGetUpdatePlanetWithMissingOwnerIsReturnedUnchanged(): void
    {
        global $db_prefix;

        dbquery ("UPDATE {$db_prefix}planets SET owner_id = 999 WHERE planet_id = 3");
        $before = LoadPlanetById (3);

        $result = GetUpdatePlanet (3, (int)$before['lastpeek'] + 3600);

        $this->assertSame ((int)$before[GID_RC_METAL], (int)$result[GID_RC_METAL]);
        $this->assertArrayNotHasKey ('max'.GID_RC_METAL, $result);

        $after = LoadPlanetById (3);
        $this->assertSame ((int)$before['lastpeek'], (int)$after['lastpeek']);
        $this->assertSame ((int)$before[GID_RC_METAL], (int)$after[GID_RC_METAL]);
    }

    /**
     * The technical space account (USER_SPACE) never gets its planets updated
     * either: the row is returned as it was loaded.
     */
    public function testGetUpdatePlanetSkipsTheTechnicalSpaceAccount(): void
    {
        global $db_prefix;

        AddDBRow (array ('player_id' => USER_SPACE, 'name' => 'Space', 'oname' => 'Space'), 'users');
        dbquery ("UPDATE {$db_prefix}planets SET owner_id = ".USER_SPACE." WHERE planet_id = 3");
        InvalidateUserCache ();

        $before = LoadPlanetById (3);
        $result = GetUpdatePlanet (3, (int)$before['lastpeek'] + 3600);

        $this->assertSame ((int)$before[GID_RC_METAL], (int)$result[GID_RC_METAL]);
        $this->assertArrayNotHasKey ('max'.GID_RC_METAL, $result);
        $this->assertSame ((int)$before['lastpeek'], (int)LoadPlanetById (3)['lastpeek']);
    }

    /**
     * The skip_planet_update mod hook freezes a planet: it is returned with
     * default production and nothing is written to the database.
     */
    public function testGetUpdatePlanetIsFrozenByTheModHook(): void
    {
        global $modlist, $db_prefix;

        $modlist['freeze'] = new ProdCoreTestFreezeMod ();
        $before = LoadPlanetById (1);

        $result = GetUpdatePlanet (1, (int)$before['lastpeek'] + 3600);

        $this->assertSame (0, $result['factor']);
        $this->assertSame (array (), $result['prod']);
        $this->assertSame (0, $result['net_prod'][GID_RC_METAL]);
        $this->assertSame (0, (int)$result['max'.GID_RC_METAL]);

        $after = LoadPlanetById (1);
        $this->assertSame ((int)$before['lastpeek'], (int)$after['lastpeek']);
        $this->assertSame ((int)$before[GID_RC_METAL], (int)$after[GID_RC_METAL]);
    }

    // ========================================================================
    // ResearchNetwork
    // ========================================================================

    /**
     * Without an Intergalactic Research Network level the virtual lab is just
     * the lab of the researching planet (planet 1 has a lab of 4).
     */
    public function testResearchNetworkWithoutNetworkReturnsThePlanetLab(): void
    {
        $this->assertSame (4, ResearchNetwork (1, GID_R_WEAPON));
    }

    /**
     * An unknown planet returns 0.
     */
    public function testResearchNetworkReturnsZeroForUnknownPlanet(): void
    {
        $this->assertSame (0, ResearchNetwork (999999, GID_R_WEAPON));
    }

    /**
     * Every IGN level attaches one more laboratory: with a lab of 6 on planet
     * 2 and a lab of 2 on planet 3, IGN 1 gives 4 + 6 = 10 and IGN 2 gives
     * 4 + 6 + 2 = 12 (Weapons Technology level 3 is already researched, so its
     * lab-4 requirement does not filter these planets out for Armour
     * Technology, which only needs a lab of 2).
     */
    public function testResearchNetworkAttachesOneLabPerIgnLevel(): void
    {
        $this->setPlanetLab (2, 6);
        $this->setPlanetLab (3, 2);

        $this->setPlayerIgn (1);
        $this->assertSame (10, ResearchNetwork (1, GID_R_ARMOUR));

        $this->setPlayerIgn (2);
        $this->assertSame (12, ResearchNetwork (1, GID_R_ARMOUR));
    }

    /**
     * The attached laboratories are sorted by level, highest first, so the
     * best lab is always used: with lab 2 on planet 2 and lab 6 on planet 3,
     * IGN 1 attaches the 6 (4 + 6 = 10), not the 2.
     */
    public function testResearchNetworkPrefersTheHighestLaboratories(): void
    {
        $this->setPlanetLab (2, 2);
        $this->setPlanetLab (3, 6);

        $this->setPlayerIgn (1);
        $this->assertSame (10, ResearchNetwork (1, GID_R_ARMOUR));
    }

    /**
     * A laboratory only counts when its planet meets the requirements of the
     * research: Weapons Technology needs a lab of 4, so planet 3 (lab 2) is
     * skipped even with an IGN level of 2, while Armour Technology (lab 2)
     * counts both.
     */
    public function testResearchNetworkSkipsLabsBelowTheRequirement(): void
    {
        $this->setPlanetLab (2, 6);
        $this->setPlanetLab (3, 2);

        $this->setPlayerIgn (2);
        $this->assertSame (10, ResearchNetwork (1, GID_R_WEAPON));
        $this->assertSame (12, ResearchNetwork (1, GID_R_ARMOUR));
    }

    /**
     * Moons are never part of the research network, even with a research lab
     * level set on them.
     */
    public function testResearchNetworkIgnoresMoons(): void
    {
        $this->setPlanetLab (2, 2);
        $this->setPlanetLab (3, 6);
        $this->setPlanetLab (10, 9);      // player 1's moon

        $this->setPlayerIgn (2);

        $this->assertSame (12, ResearchNetwork (1, GID_R_ARMOUR));
    }

    // ========================================================================
    // PlanetPrice / FleetPrice
    // ========================================================================

    /**
     * PlanetPrice sums the cost of every building level plus the fleet and
     * defense value (21 600 for a Robotics Factory at level 2, 4000 for 10
     * Light Fighters, 4000 for 2 Rocket Launchers).
     */
    public function testPlanetPriceCountsBuildingsFleetAndDefense(): void
    {
        $planet = $this->makePlanet (array (
            GID_B_METAL_MINE => 1,       // 60 + 15 = 75 points
            GID_B_ROBOTS => 2,           // 720 + 1440 = 2160 points
            GID_F_LF => 10,              // 4000 each
            GID_D_RL => 2,               // 2000 each
        ));

        $price = PlanetPrice ($planet);

        $this->assertSame (2235 + 40000 + 4000, $price['points']);
        $this->assertSame (40000, $price['fleet_pts']);
        $this->assertSame (4000, $price['defense_pts']);
        $this->assertSame (10, $price['fpoints']);
    }

    /**
     * An empty planet is worth nothing.
     */
    public function testPlanetPriceOfAnEmptyPlanetIsZero(): void
    {
        $price = PlanetPrice ($this->makePlanet ());

        $this->assertSame (0, $price['points']);
        $this->assertSame (0, $price['fpoints']);
        $this->assertSame (0, $price['fleet_pts']);
        $this->assertSame (0, $price['defense_pts']);
    }

    /**
     * FleetPrice counts the level-1 cost of each ship and the number of ships
     * (10 Light Fighters = 40 000 points, 2 Small Cargo = 8000 points).
     */
    public function testFleetPriceCountsShipsAndUnits(): void
    {
        $fleet = $this->makeFleet (array (GID_F_LF => 10, GID_F_SC => 2));

        $price = FleetPrice ($fleet);

        $this->assertSame (48000, $price['points']);
        $this->assertSame (12, $price['fpoints']);
    }

    /**
     * An empty fleet is worth nothing.
     */
    public function testFleetPriceOfAnEmptyFleetIsZero(): void
    {
        $price = FleetPrice ($this->makeFleet ());

        $this->assertSame (0, $price['points']);
        $this->assertSame (0, $price['fpoints']);
    }

    /**
     * Build a fleet array with every ship id present.
     */
    private function makeFleet(array $overrides = array()) : array
    {
        global $fleetmap;

        $fleet = array ();
        foreach ($fleetmap as $gid) $fleet[$gid] = 0;

        return array_replace ($fleet, $overrides);
    }
}
