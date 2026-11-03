<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the expedition core modules:
 *  - game/core/expedition.php        (settings, scoring, event handlers, hold)
 *  - game/core/expedition_battle.php (alien/pirate battle + fleet write-back)
 *
 * The tests run against the real game core with the in-memory SQLite backend
 * (see testing/bootstrap.php and phpunit.xml) and use FixtureBuilder to create
 * a full 3-player universe with the real game schema.
 *
 * Every test method runs in its own PHP process, so each one starts with a
 * fresh in-memory database and clean globals. All random behaviour is driven
 * through mt_srand(), so the tests are deterministic.
 *
 * Reference values used below (from game/core/techs.php, factor 0 => level 1
 * costs the base price):
 *  - structure of a ship = metal + crystal of its price;
 *  - Small Cargo 2000+2000 = 4000, Light Fighter 3000+1000 = 4000,
 *    Large Cargo 6000+6000 = 12000, Espionage Probe 0+1000 = 1000;
 *  - ExpPoints() divides the total structure by 1000.
 */
#[RunTestsInSeparateProcesses]
class ExpeditionCoreTest extends TestCase
{
    private FixtureBuilder $fixture;
    private string $dbPrefix;
    private int $playerId = 1;

    protected function setUp(): void
    {
        // The game resolves loca files and battle data files relative to game/.
        chdir(__DIR__ . '/../game');

        // No mods are initialized in the test process; the game core calls the
        // ModsExec* helpers unconditionally, so $modlist must be an array.
        $GLOBALS['modlist'] = array ();

        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');
        $this->dbPrefix = $this->fixture->getDbPrefix();

        // ExpeditionHold() loads the "common" section of the player's language;
        // the resource names NAME_<GID_RC_*> used by the found-resources and
        // dark-matter events live there.
        loca_add ('common', 'en');

        global $GlobalUni, $db_prefix;
        $GlobalUni = $this->fixture->getUniData();
        $db_prefix = $this->dbPrefix;
    }

    // ========================================================================
    // Fixture helpers
    // ========================================================================

    /**
     * Build a fleet composition array with every ship type of $fleetmap present.
     *
     * The expedition code indexes $fleet by ship id without an isset() check,
     * so the arrays handed to it must always be complete.
     */
    private function fleetArray(array $overrides = array ()) : array
    {
        global $fleetmap;
        $fleet = array ();
        foreach ( $fleetmap as $i => $gid ) $fleet[$gid] = 0;
        foreach ( $overrides as $gid => $amount ) $fleet[$gid] = $amount;
        return $fleet;
    }

    /**
     * Turn a fleet table row into a complete ship-composition array.
     */
    private function fleetFromRow(array $fleetObj) : array
    {
        global $fleetmap;
        $fleet = array ();
        foreach ( $fleetmap as $i => $gid ) $fleet[$gid] = (int)$fleetObj[$gid];
        return $fleet;
    }

    /**
     * The fleet id of the expedition fleet created by FixtureBuilder.
     */
    private function expeditionFleetId() : int
    {
        $result = dbquery ("SELECT fleet_id FROM ".$this->dbPrefix."fleet WHERE owner_id = ".$this->playerId.
            " AND mission = ".FTYP_EXPEDITION." LIMIT 1");
        $row = dbarray ($result);
        $this->assertIsArray ($row, 'the fixture must contain an outbound expedition fleet');
        return (int)$row['fleet_id'];
    }

    /**
     * Load the fixture expedition: fleet row, queue task, origin and target.
     *
     * @return array{fleet_obj: array, queue: array, origin: array, target: array}
     */
    private function expeditionContext() : array
    {
        $fleetObj = LoadFleet ($this->expeditionFleetId());
        $this->assertIsArray ($fleetObj);
        $queue = GetFleetQueue ((int)$fleetObj['fleet_id']);
        $this->assertIsArray ($queue);
        $origin = LoadPlanetById ((int)$fleetObj['start_planet']);
        $this->assertIsArray ($origin);
        $target = LoadPlanetById ((int)$fleetObj['target_planet']);
        $this->assertIsArray ($target);

        return array ($fleetObj, $queue, $origin, $target);
    }

    /**
     * The row with the highest fleet_id (the fleet most recently dispatched).
     */
    private function lastFleetRow() : array
    {
        $result = dbquery ("SELECT * FROM ".$this->dbPrefix."fleet ORDER BY fleet_id DESC LIMIT 1");
        $row = dbarray ($result);
        $this->assertIsArray ($row, 'a fleet row must have been written');
        return $row;
    }

    private function fleetRowCount() : int
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."fleet");
        $row = dbarray ($result);
        return (int)$row['cnt'];
    }

    private function fleetQueue(int $fleetId) : array
    {
        $result = dbquery ("SELECT * FROM ".$this->dbPrefix."queue WHERE type = '".QTYP_FLEET."' AND sub_id = $fleetId");
        $row = dbarray ($result);
        $this->assertIsArray ($row, "fleet $fleetId must have a queue task");
        return $row;
    }

    private function userRow(int $playerId) : array
    {
        $result = dbquery ("SELECT * FROM ".$this->dbPrefix."users WHERE player_id = $playerId");
        $row = dbarray ($result);
        $this->assertIsArray ($row);
        return $row;
    }

    private function planetRow(int $planetId) : array
    {
        $result = dbquery ("SELECT * FROM ".$this->dbPrefix."planets WHERE planet_id = $planetId");
        $row = dbarray ($result);
        $this->assertIsArray ($row);
        return $row;
    }

    /**
     * The users table has no DEFAULT for `banned`, so the fixture leaves it
     * NULL and AdjustStats() ("... AND banned = 0 ...") would update nothing.
     */
    private function makePlayerRanked(int $playerId = 1) : void
    {
        dbquery ("UPDATE ".$this->dbPrefix."users SET banned = 0 WHERE player_id = $playerId");
        InvalidateUserCache ();
    }

    /**
     * Insert the technical USER_SPACE account exactly like game/install.php:
     * ExpeditionBattle loads it as the pirate/alien defender.
     */
    private function addSpaceUser() : void
    {
        AddDBRow (array (
            'player_id' => USER_SPACE, 'name' => 'space', 'oname' => 'space', 'lang' => 'en',
            'admin' => 2, 'validated' => 1, 'banned' => 0, 'trader' => 0,
            'rate_m' => 0, 'rate_k' => 0, 'rate_d' => 0,
            'score1' => 0, 'score2' => 0, 'score3' => 0,
        ), 'users');
        InvalidateUserCache ();
    }

    /**
     * The default expedition settings written by game/install.php, as a
     * local array with integer values (LoadExpeditionSettings returns the
     * SQLite column values as strings).
     */
    private function expeditionSettings(array $overrides = array ()) : array
    {
        return array_merge (array (
            'chance_success' => 70,
            'depleted_min' => 25, 'depleted_med' => 50, 'depleted_max' => 75,
            'chance_depleted_min' => 25, 'chance_depleted_med' => 50, 'chance_depleted_max' => 75,
            'chance_alien' => 95, 'chance_pirates' => 85, 'chance_dm' => 70, 'chance_lost' => 69,
            'chance_delay' => 63, 'chance_accel' => 60, 'chance_res' => 25, 'chance_fleet' => 1,
            'dm_factor' => 3,
            'score_cap1' => 10000, 'score_cap2' => 100000, 'score_cap3' => 1000000, 'score_cap4' => 5000000,
            'score_cap5' => 25000000, 'score_cap6' => 50000000, 'score_cap7' => 75000000, 'score_cap8' => 100000000,
            'limit_cap1' => 9000, 'limit_cap2' => 9000, 'limit_cap3' => 9000, 'limit_cap4' => 9000,
            'limit_cap5' => 12000, 'limit_cap6' => 12000, 'limit_cap7' => 12000, 'limit_cap8' => 12000,
            'limit_max' => 12000,
        ), $overrides);
    }

    /**
     * Insert the expedition settings row into the database and return them.
     */
    private function seedExpeditionSettings(array $overrides = array ()) : array
    {
        $settings = $this->expeditionSettings($overrides);
        AddDBRow ($settings, 'exptab');
        return $settings;
    }

    /**
     * The localized strings LOCA_PREFIX1 .. LOCA_PREFIX$count for English.
     */
    private function locaSet(string $prefix, int $count) : array
    {
        $set = array ();
        for ( $i = 1; $i <= $count; $i++ ) $set[] = loca_lang ($prefix.$i, 'en');
        return $set;
    }

    /**
     * Replicate the single RNG draw that picks the 2x/3x/5x multiplier in
     * Exp_DelayFleet() and Exp_AccelFleet(), so the seeded run is predictable.
     */
    private function expectedTravelRatio(int $seed) : int
    {
        mt_srand ($seed);
        $chance = mt_rand (0, 99);
        if ( $chance >= 99 ) return 5;
        if ( $chance >= 90 ) return 3;
        return 2;
    }

    /**
     * Find one seed per documented travel multiplier (2x, 3x, 5x).
     *
     * @return array<int, int> ratio => seed
     */
    private function seedsForEveryTravelRatio() : array
    {
        $seeds = array ();
        for ( $seed = 1; $seed <= 5000 && count ($seeds) < 3; $seed++ ) {
            $ratio = $this->expectedTravelRatio ($seed);
            if ( !isset ($seeds[$ratio]) ) $seeds[$ratio] = $seed;
        }
        ksort ($seeds);

        $this->assertSame (array (2, 3, 5), array_keys ($seeds),
            'the 2x, 3x and 5x branches must all be reachable');

        return $seeds;
    }

    // ========================================================================
    // GetExpeditionsCount
    // ========================================================================

    public function testGetExpeditionsCountCountsTheOutboundExpedition() : void
    {
        // FixtureBuilder gives PlayerOne exactly one expedition fleet; the
        // other fleets of the player use different missions.
        $this->assertSame (1, GetExpeditionsCount ($this->playerId));
        $this->assertSame (0, GetExpeditionsCount (2), 'PlayerTwo has no expedition');
        $this->assertSame (0, GetExpeditionsCount (999999), 'An unknown player has no expedition');
    }

    public function testGetExpeditionsCountCountsReturningAndOrbitingFleets() : void
    {
        $fleetId = $this->expeditionFleetId();

        // A returning expedition (FTYP_EXPEDITION + FTYP_RETURN) still counts.
        dbquery ("UPDATE ".$this->dbPrefix."fleet SET mission = ".(FTYP_EXPEDITION + FTYP_RETURN)." WHERE fleet_id = $fleetId");
        $this->assertSame (1, GetExpeditionsCount ($this->playerId));

        // So does an expedition holding in orbit (FTYP_EXPEDITION + FTYP_ORBITING).
        dbquery ("UPDATE ".$this->dbPrefix."fleet SET mission = ".(FTYP_EXPEDITION + FTYP_ORBITING)." WHERE fleet_id = $fleetId");
        $this->assertSame (1, GetExpeditionsCount ($this->playerId));

        // Any other mission is not an expedition.
        dbquery ("UPDATE ".$this->dbPrefix."fleet SET mission = ".FTYP_ATTACK." WHERE fleet_id = $fleetId");
        $this->assertSame (0, GetExpeditionsCount ($this->playerId));
    }

    // ========================================================================
    // LoadExpeditionSettings / SaveExpeditionSettings
    // ========================================================================

    public function testLoadExpeditionSettingsReturnsFalseWhenUnconfigured() : void
    {
        // FixtureBuilder does not insert the exptab row (install.php does).
        $this->assertFalse (LoadExpeditionSettings ());
    }

    public function testLoadExpeditionSettingsReturnsTheStoredRow() : void
    {
        $this->seedExpeditionSettings(array ('chance_success' => 42, 'dm_factor' => 7, 'limit_max' => 12345));

        $row = LoadExpeditionSettings ();

        $this->assertIsArray ($row);
        $this->assertSame (42, (int)$row['chance_success']);
        $this->assertSame (7, (int)$row['dm_factor']);
        $this->assertSame (12345, (int)$row['limit_max']);
        $this->assertSame (25, (int)$row['depleted_min']);
    }

    public function testSaveExpeditionSettingsPersistsEveryField() : void
    {
        $this->seedExpeditionSettings();

        $settings = $this->expeditionSettings();
        $i = 0;
        foreach ( $settings as $key => $value ) {
            $settings[$key] = 100 + $i++;      // every field gets a distinct value
        }

        SaveExpeditionSettings ($settings);

        $stored = LoadExpeditionSettings ();
        $this->assertIsArray ($stored);

        // Exactly one settings row must exist: SaveExpeditionSettings is an UPDATE.
        $count = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."exptab");
        $this->assertSame (1, (int)dbarray ($count)['cnt']);

        foreach ( $settings as $key => $value ) {
            $this->assertSame ($value, (int)$stored[$key], "field $key must be saved");
        }
    }

    // ========================================================================
    // ExpPoints
    // ========================================================================

    public function testExpPointsSumsMetalAndCrystalPerShip() : void
    {
        // Small Cargo 2000+2000 = 4000 -> 4 points.
        $this->assertSame (4, ExpPoints ($this->fleetArray(array (GID_F_SC => 1))));
        // Ten Small Cargo -> 40000/1000 = 40.
        $this->assertSame (40, ExpPoints ($this->fleetArray(array (GID_F_SC => 10))));
        // Espionage Probe has no metal cost: 1000 crystal -> 1 point.
        $this->assertSame (1, ExpPoints ($this->fleetArray(array (GID_F_PROBE => 1))));
        // A Death Star is 5000000+4000000 -> 9000 points.
        $this->assertSame (9000, ExpPoints ($this->fleetArray(array (GID_F_DEATHSTAR => 1))));
    }

    public function testExpPointsIgnoresDeuteriumAndEmptyFleets() : void
    {
        $this->assertSame (0, ExpPoints ($this->fleetArray()));

        // A Cruiser costs 20000 metal + 7000 crystal + 2000 deuterium; the
        // deuterium part must not count towards the expedition points.
        $this->assertSame (27, ExpPoints ($this->fleetArray(array (GID_F_CRUISER => 1))));
    }

    public function testExpPointsIsAdditiveOverShipTypes() : void
    {
        // Large Cargo (12) + 2 x Light Fighter (4 each) + Probe (1) = 21.
        $fleet = $this->fleetArray(array (GID_F_LC => 1, GID_F_LF => 2, GID_F_PROBE => 1));
        $this->assertSame (21, ExpPoints ($fleet));

        // This is exactly the fixture expedition fleet: 12000+8000+1000 = 21000.
        list ($fleetObj) = $this->expeditionContext();
        $this->assertSame (21, ExpPoints ($this->fleetFromRow($fleetObj)));
    }

    // ========================================================================
    // ExpUpperLimit
    // ========================================================================

    public function testExpUpperLimitUsesTheFirstCapForSmallUniverses() : void
    {
        // The fixture top-1 player has score1 = 50000 -> 50 points, which is
        // below score_cap1 (10000), so limit_cap1 is returned.
        $exptab = $this->expeditionSettings();
        $this->assertSame (9000, ExpUpperLimit ($exptab));
    }

    public function testExpUpperLimitWalksTheCapsUp() : void
    {
        // score = 50: below cap2 but not below cap1 -> limit_cap2.
        $exptab = $this->expeditionSettings(array ('score_cap1' => 10, 'limit_cap1' => 111, 'limit_cap2' => 222));
        $this->assertSame (222, ExpUpperLimit ($exptab));

        // score = 50 is below none of the caps -> limit_max.
        $exptab = $this->expeditionSettings(array (
            'score_cap1' => 1, 'score_cap2' => 1, 'score_cap3' => 1, 'score_cap4' => 1,
            'score_cap5' => 1, 'score_cap6' => 1, 'score_cap7' => 1, 'score_cap8' => 1,
            'limit_max' => 777,
        ));
        $this->assertSame (777, ExpUpperLimit ($exptab));
    }

    public function testExpUpperLimitIsExactAtTheCapBoundary() : void
    {
        // score1 / 1000 == score_cap1 exactly: the comparison is "<", so the
        // next cap must win.
        dbquery ("UPDATE ".$this->dbPrefix."users SET score1 = 50000 WHERE player_id = 1");

        $exptab = $this->expeditionSettings(array ('score_cap1' => 50, 'limit_cap1' => 111, 'limit_cap2' => 222));
        $this->assertSame (222, ExpUpperLimit ($exptab));

        $exptab = $this->expeditionSettings(array ('score_cap1' => 51, 'limit_cap1' => 111, 'limit_cap2' => 222));
        $this->assertSame (111, ExpUpperLimit ($exptab));
    }

    public function testExpUpperLimitWithoutAnyPlayerFallsBackToTheFirstCap() : void
    {
        dbquery ("DELETE FROM ".$this->dbPrefix."users");

        $exptab = $this->expeditionSettings(array ('limit_cap1' => 4711));
        $this->assertSame (4711, ExpUpperLimit ($exptab));
    }

    // ========================================================================
    // Logbook
    // ========================================================================

    public function testLogbookUsesTheTierOfTheVisitCounter() : void
    {
        // Guard against a silently empty loca section: the strings must be real.
        $this->assertNotSame ('EXP_NOT_DEPLETED_1', loca_lang ('EXP_NOT_DEPLETED_1', 'en'));

        $exptab = $this->expeditionSettings();       // 25 / 50 / 75

        $tiers = array (
            array (0,    array ('EXP_NOT_DEPLETED_1', 'EXP_NOT_DEPLETED_2')),
            array (25,   array ('EXP_NOT_DEPLETED_1', 'EXP_NOT_DEPLETED_2')),
            array (26,   array ('EXP_DEPLETED_MIN_1', 'EXP_DEPLETED_MIN_2', 'EXP_DEPLETED_MIN_3')),
            array (50,   array ('EXP_DEPLETED_MIN_1', 'EXP_DEPLETED_MIN_2', 'EXP_DEPLETED_MIN_3')),
            array (51,   array ('EXP_DEPLETED_MED_1', 'EXP_DEPLETED_MED_2', 'EXP_DEPLETED_MED_3')),
            array (75,   array ('EXP_DEPLETED_MED_1', 'EXP_DEPLETED_MED_2', 'EXP_DEPLETED_MED_3')),
            array (76,   array ('EXP_DEPLETED_MAX_1', 'EXP_DEPLETED_MAX_2', 'EXP_DEPLETED_MAX_3')),
            array (9999, array ('EXP_DEPLETED_MAX_1', 'EXP_DEPLETED_MAX_2', 'EXP_DEPLETED_MAX_3')),
        );

        foreach ( $tiers as $tier ) {
            list ($expcount, $keys) = $tier;
            mt_srand (20240607);

            $msg = Logbook ($expcount, $exptab, 'en');

            $expected = array ();
            foreach ( $keys as $key ) $expected[] = loca_lang ($key, 'en');
            $this->assertContains ($msg, $expected, "visit counter $expcount must use the ".$keys[0]." tier");
        }
    }

    public function testLogbookPicksDifferentMessagesForDifferentSeeds() : void
    {
        $exptab = $this->expeditionSettings();
        $set = $this->locaSet('EXP_NOT_DEPLETED_', 2);

        // Both "not depleted" entries must be reachable: the message index is
        // mt_rand(0, 1) on top of the seeded stream.
        $seen = array ();
        for ( $seed = 1; $seed <= 40; $seed++ ) {
            mt_srand ($seed);
            $msg = Logbook (0, $exptab, 'en');
            $this->assertContains ($msg, $set);
            $seen[$msg] = true;
        }

        $this->assertCount (2, $seen, 'both not-depleted logbook entries must be reachable');
    }

    // ========================================================================
    // Expedition (event roll)
    // ========================================================================

    public function testExpeditionReturnsNothingWhenTheSuccessRollFails() : void
    {
        // chance_success = 0 and hold_time = 0: the first roll can never be < 0.
        $exptab = $this->expeditionSettings(array ('chance_success' => 0));

        for ( $i = 0; $i < 10; $i++ ) {
            $this->assertSame (EXP_NOTHING, Expedition (0, $exptab, 0));
        }
    }

    public function testExpeditionSelectsEveryEventByItsThreshold() : void
    {
        // Walk the descending threshold ladder of the event selection. Setting
        // every threshold above the wanted event to 100 (unreachable roll) and
        // the wanted one to 0 makes the outcome independent of the RNG.
        $ladder = array (
            EXP_ALIENS     => 'chance_alien',
            EXP_PIRATES    => 'chance_pirates',
            EXP_DARK_MATTER => 'chance_dm',
            EXP_BLACK_HOLE => 'chance_lost',
            EXP_DELAY      => 'chance_delay',
            EXP_ACCEL      => 'chance_accel',
            EXP_RESOURCES  => 'chance_res',
            EXP_FLEET      => 'chance_fleet',
        );

        $success = array (
            'chance_success' => 100,
            'chance_depleted_min' => 100, 'chance_depleted_med' => 100, 'chance_depleted_max' => 100,
        );

        foreach ( $ladder as $expected => $thresholdKey ) {
            $overrides = $success;
            foreach ( $ladder as $event => $key ) {
                if ( $event === $expected ) {
                    $overrides[$key] = 0;
                    break;
                }
                $overrides[$key] = 100;
            }

            $this->assertSame ($expected, Expedition (0, $this->expeditionSettings($overrides), 0),
                "event ".$expected." must be selected by ".$thresholdKey);
        }

        // Nothing reaches the bottom of the ladder -> the trader.
        $overrides = $success;
        foreach ( $ladder as $key ) $overrides[$key] = 100;
        $this->assertSame (EXP_TRADER, Expedition (0, $this->expeditionSettings($overrides), 0));
    }

    public function testExpeditionDepletedCounterAddsAFailureChance() : void
    {
        // chance_depleted_min = 100 makes every roll fail the depleted check.
        $exptab = $this->expeditionSettings(array (
            'chance_success' => 100,
            'depleted_min' => 5, 'depleted_med' => 10, 'depleted_max' => 15,
            'chance_depleted_min' => 100, 'chance_depleted_med' => 100, 'chance_depleted_max' => 100,
            'chance_alien' => 0,
        ));

        // Not depleted yet: chance_depleted is 0, so the event always happens.
        $this->assertSame (EXP_ALIENS, Expedition (5, $exptab, 0));

        // Depleted (small): every roll is < 100 -> nothing happens.
        for ( $i = 0; $i < 10; $i++ ) {
            $this->assertSame (EXP_NOTHING, Expedition (6, $exptab, 0));
        }
        $this->assertSame (EXP_NOTHING, Expedition (11, $exptab, 0));
        $this->assertSame (EXP_NOTHING, Expedition (100, $exptab, 0));
    }

    public function testExpeditionHoldTimeAndSuccessChanceAddUp() : void
    {
        // chance_success = 50 and a 60 hour hold: any first roll < 110 succeeds,
        // which is always true, so the event is always selected.
        $exptab = $this->expeditionSettings(array ('chance_success' => 50, 'chance_alien' => 0));
        $this->assertSame (EXP_ALIENS, Expedition (0, $exptab, 60));

        // hold_time = 0 with the same settings succeeds only on a roll < 50.
        mt_srand (20240607);
        $first = mt_rand (0, 99);
        mt_srand (20240607);
        $this->assertSame ($first < 50 ? EXP_ALIENS : EXP_NOTHING, Expedition (0, $exptab, 0));
    }

    // ========================================================================
    // Expedition event handlers
    // ========================================================================

    public function testExpNothingHappensSendsTheFleetBack() : void
    {
        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();
        $seed = 20240607;

        mt_srand ($seed);
        $msg = Exp_NothingHappens ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        // The message is one of the 12 localized "nothing happens" texts; the
        // message index is the only RNG draw of the function.
        mt_srand ($seed);
        $n = mt_rand (0, 11);
        $this->assertSame (loca_lang ('EXP_NOTHING_'.($n + 1), 'en'), $msg);

        // The hold time is used as the flight time of the return trip.
        $new = $this->lastFleetRow();
        $this->assertSame (FTYP_RETURN + FTYP_EXPEDITION, (int)$new['mission']);
        $this->assertSame ((int)$origin['planet_id'], (int)$new['start_planet']);
        $this->assertSame ((int)$target['planet_id'], (int)$new['target_planet']);
        $this->assertSame ((int)$fleetObj['deploy_time'], (int)$new['flight_time']);
        $this->assertSame (0, (int)$new['fuel']);
        foreach ( $fleet as $gid => $amount ) {
            $this->assertSame ($amount, (int)$new[$gid], "ship $gid must be carried home");
        }

        // The return flight starts when the expedition hold ends.
        $q = $this->fleetQueue ((int)$new['fleet_id']);
        $this->assertSame ((int)$queue['end'], (int)$q['start']);
        $this->assertSame ((int)$queue['end'] + (int)$fleetObj['deploy_time'], (int)$q['end']);
    }

    public function testExpDarkMatterFoundCreditsDarkMatter() : void
    {
        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings(array ('dm_factor' => 3));
        $seed = 20240607;

        // Replicate the RNG sequence: chance -> amount -> message index.
        mt_srand ($seed);
        $chance = mt_rand (0, 99);
        if ( $chance >= 99 ) { $dm = mt_rand (501, 2076); mt_rand (0, 1); }
        else if ( $chance >= 90 ) { $dm = mt_rand (201, 500); mt_rand (0, 2); }
        else { $dm = mt_rand (100, 200); mt_rand (0, 4); }
        $expected = $dm * 3;

        $before = (int)$this->userRow($this->playerId)['dmfree'];

        mt_srand ($seed);
        $msg = Exp_DarkMatterFound ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        $this->assertSame ($before + $expected, (int)$this->userRow($this->playerId)['dmfree']);
        $this->assertStringContainsString (
            va (loca_lang ('EXP_FOUND', 'en'), nicenum ($expected), loca_lang ('NAME_'.GID_RC_DM, 'en')),
            $msg,
            'the report must state how much dark matter was found');
        $this->assertStringContainsString (nicenum ($expected), $msg);

        // The fleet is sent home as well.
        $new = $this->lastFleetRow();
        $this->assertSame (FTYP_RETURN + FTYP_EXPEDITION, (int)$new['mission']);
    }

    public function testExpDarkMatterFoundTreatsAZeroFactorAsOne() : void
    {
        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings(array ('dm_factor' => 0));
        $seed = 424242;

        mt_srand ($seed);
        $chance = mt_rand (0, 99);
        if ( $chance >= 99 ) { $dm = mt_rand (501, 2076); mt_rand (0, 1); }
        else if ( $chance >= 90 ) { $dm = mt_rand (201, 500); mt_rand (0, 2); }
        else { $dm = mt_rand (100, 200); mt_rand (0, 4); }

        $before = (int)$this->userRow($this->playerId)['dmfree'];

        mt_srand ($seed);
        Exp_DarkMatterFound ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        // A dm_factor of 0 means "no factor configured" and behaves like 1.
        $this->assertSame ($before + $dm, (int)$this->userRow($this->playerId)['dmfree']);
    }

    public function testExpLostFleetWritesOffTheFleetPoints() : void
    {
        $this->makePlayerRanked();
        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();

        // The fixture expedition fleet is 1 Large Cargo + 2 Light Fighters +
        // 1 Espionage Probe: 12000 + 2*4000 + 1000 = 21000 points, 4 ships.
        $this->assertSame (21000, FleetPrice ($fleetObj)['points']);

        $before = $this->userRow($this->playerId);
        $fleetCountBefore = $this->fleetRowCount();

        $msg = Exp_LostFleet ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        $this->assertContains ($msg, $this->locaSet('EXP_LOST_', 4));

        $after = $this->userRow($this->playerId);
        $this->assertSame ((int)$before['score1'] - 21000, (int)$after['score1']);
        $this->assertSame ((int)$before['score2'] - 4, (int)$after['score2']);
        $this->assertSame ((int)$before['score3'], (int)$after['score3'], 'research points are untouched');

        // The lost fleet is not returned; the caller (Queue_Fleet_End) deletes
        // the outgoing fleet row.
        $this->assertSame ($fleetCountBefore, $this->fleetRowCount(), 'no return fleet may be dispatched');
    }

    public function testExpNoPointLossForAnUnrankedPlayer() : void
    {
        // AdjustStats() only updates players with banned = 0; leaving the
        // column NULL (as the fixture does) must not change anything.
        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();

        $before = $this->userRow($this->playerId);

        Exp_LostFleet ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        $after = $this->userRow($this->playerId);
        $this->assertSame ((int)$before['score1'], (int)$after['score1']);
        $this->assertSame ((int)$before['score2'], (int)$after['score2']);
    }

    public function testExpDelayFleetMultipliesTheReturnTime() : void
    {
        $exptab = $this->expeditionSettings();

        // Every documented multiplier (2x, 3x, 5x) must be exercised. The delay
        // is the only RNG draw, so seeds can be searched offline (the replay in
        // expectedTravelRatio() has no side effects).
        $seeds = $this->seedsForEveryTravelRatio();

        foreach ( $seeds as $ratio => $seed ) {
            list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
            $fleet = $this->fleetFromRow($fleetObj);

            mt_srand ($seed);
            $msg = Exp_DelayFleet ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

            $this->assertContains ($msg, $this->locaSet('EXP_DELAY_', 6));

            $new = $this->lastFleetRow();
            $this->assertSame (FTYP_RETURN + FTYP_EXPEDITION, (int)$new['mission']);
            // delay = flight_time (the hold time) * ratio, added to the flight.
            $this->assertSame ((int)$fleetObj['deploy_time'] + $ratio * (int)$fleetObj['flight_time'],
                (int)$new['flight_time'], "seed $seed must delay the flight $ratio times");
            $q = $this->fleetQueue ((int)$new['fleet_id']);
            $this->assertSame ((int)$queue['end'], (int)$q['start']);
        }
    }

    public function testExpAccelFleetDividesTheReturnTime() : void
    {
        $exptab = $this->expeditionSettings();

        $seeds = $this->seedsForEveryTravelRatio();

        foreach ( $seeds as $ratio => $seed ) {
            list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
            $fleet = $this->fleetFromRow($fleetObj);

            mt_srand ($seed);
            $msg = Exp_AccelFleet ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

            $this->assertContains ($msg, $this->locaSet('EXP_ACCEL_', 3));

            $new = $this->lastFleetRow();
            $this->assertSame (FTYP_RETURN + FTYP_EXPEDITION, (int)$new['mission']);
            $this->assertSame (intdiv ((int)$fleetObj['deploy_time'], $ratio), (int)$new['flight_time'],
                "seed $seed must shorten the flight $ratio times");
        }
    }

    public function testExpResourcesFoundLoadsExactlyOneResourceType() : void
    {
        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();
        $seed = 20240607;

        // Replicate the RNG sequence: type -> chance -> roll -> message index.
        mt_srand ($seed);
        $type = mt_rand (0, 2);
        $chance = mt_rand (0, 99);
        if ( $chance >= 99 ) { $roll = mt_rand (51, 100) * 2; mt_rand (0, 1); }
        else if ( $chance >= 90 ) { $roll = mt_rand (26, 50) * 2; mt_rand (0, 2); }
        else { $roll = mt_rand (5, 25) * 2; mt_rand (0, 3); }
        if ( $type == 1 ) $roll /= 2;
        else if ( $type == 2 ) $roll /= 3;

        $points = min (max (200, ExpPoints ($fleet)), ExpUpperLimit ($exptab));
        $cargo = max (0, FleetCargoSummary ($fleet)
            - ((int)$fleetObj[GID_RC_METAL] + (int)$fleetObj[GID_RC_CRYSTAL] + (int)$fleetObj[GID_RC_DEUTERIUM]));
        $amount = $roll * $points;
        $clamped = false;
        if ( $cargo < $amount ) {
            $amount = $cargo;
            $clamped = true;
        }

        $resource = array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM)[$type];

        mt_srand ($seed);
        $msg = Exp_ResourcesFound ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        $new = $this->lastFleetRow();
        $this->assertSame (FTYP_RETURN + FTYP_EXPEDITION, (int)$new['mission']);
        foreach ( array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM) as $rc ) {
            $expected = (int)$fleetObj[$rc] + ($rc === $resource ? $amount : 0);
            $this->assertEqualsWithDelta ($expected, (float)$new[$rc], 1e-6,
                "resource $rc of the returning fleet");
        }

        $this->assertStringContainsString (
            va (loca_lang ('EXP_FOUND', 'en'), nicenum ($amount), loca_lang ('NAME_'.$resource, 'en')),
            $msg,
            'the report must name the found resource and its amount');
        if ( $clamped ) {
            $this->assertStringContainsString ('<br><br>', $msg, 'the cargo-full footer must be appended');
        }
    }

    public function testExpResourcesFoundWithoutCargoFindsNothing() : void
    {
        // A probe-only fleet has no cargo capacity at all, so the found amount
        // is clamped to zero and the "no cargo" footer is appended.
        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetArray(array (GID_F_PROBE => 1));
        $exptab = $this->expeditionSettings();

        $this->assertSame (0, FleetCargoSummary ($fleet));

        // The resource type is the first RNG draw of the function.
        $seed = 20240607;
        mt_srand ($seed);
        $type = mt_rand (0, 2);
        $resource = array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM)[$type];

        mt_srand ($seed);
        $msg = Exp_ResourcesFound ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        $this->assertStringContainsString ('<br><br>', $msg, 'the cargo-full footer must be appended');
        $this->assertStringContainsString (
            va (loca_lang ('EXP_FOUND', 'en'), nicenum (0), loca_lang ('NAME_'.$resource, 'en')),
            $msg,
            'a fleet without cargo capacity must find nothing');

        $new = $this->lastFleetRow();
        foreach ( array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM) as $rc ) {
            $this->assertSame ((int)$fleetObj[$rc], (int)$new[$rc]);
        }
    }

    public function testExpFleetFoundAddsSalvagedShipsToTheReturningFleet() : void
    {
        $this->makePlayerRanked();
        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();

        // The fixture fleet is 1 Large Cargo + 2 Light Fighters + 1 Probe, so
        // the salvage pool is the chain {Probe, Small Cargo, Light Fighter,
        // Large Cargo, Heavy Fighter}; no other ship may ever appear.
        $pool = array (GID_F_PROBE, GID_F_SC, GID_F_LF, GID_F_LC, GID_F_HF);

        $salvaged = 0;
        for ( $seed = 1; $seed <= 30; $seed++ ) {
            $before = $this->userRow($this->playerId);

            mt_srand ($seed);
            $msg = Exp_FleetFound ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

            $new = $this->lastFleetRow();
            $this->assertSame (FTYP_RETURN + FTYP_EXPEDITION, (int)$new['mission']);
            $this->assertSame ((int)$queue['end'], (int)$this->fleetQueue((int)$new['fleet_id'])['start']);

            $added = 0;
            $points = 0;
            foreach ( $fleet as $gid => $amount ) {
                $newAmount = (int)$new[$gid];
                $this->assertGreaterThanOrEqual ($amount, $newAmount, "ship $gid may only be added");
                $extra = $newAmount - $amount;
                if ( $extra > 0 ) {
                    $this->assertContains ($gid, $pool, "ship $gid must be part of the salvage pool");
                    $added += $extra;
                    $points += TechPriceInPoints (TechPrice ($gid, 1)) * $extra;
                }
            }

            // Score: +points for the found ships, +ship count for fleet points.
            $after = $this->userRow($this->playerId);
            $this->assertSame ((int)$before['score1'] + $points, (int)$after['score1'], "seed $seed");
            $this->assertSame ((int)$before['score2'] + $added, (int)$after['score2'], "seed $seed");

            if ( $added > 0 ) {
                $salvaged++;
                $this->assertStringContainsString (loca_lang ('EXP_FLEET_FOUND', 'en'), $msg, "seed $seed");
            }
        }

        $this->assertGreaterThan (0, $salvaged, 'at least one of the seeded runs must salvage ships');
    }

    public function testExpTraderFoundKeepsTheBetterExistingOffer() : void
    {
        // An existing 3/3/3 offer has a rate sum of 9; none of the generated
        // offers (at most 6.0) can beat it, so the user row must not change.
        dbquery ("UPDATE ".$this->dbPrefix."users SET trader = 3, rate_m = 3.0, rate_k = 3.0, rate_d = 3.0
            WHERE player_id = ".$this->playerId);
        InvalidateUserCache ();

        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();

        mt_srand (20240607);
        $msg = Exp_TraderFound ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        $this->assertContains ($msg, $this->locaSet('EXP_TRADER_', 2));

        $user = $this->userRow($this->playerId);
        $this->assertSame (3, (int)$user['trader']);
        $this->assertEqualsWithDelta (3.0, (float)$user['rate_m'], 1e-9);
        $this->assertEqualsWithDelta (3.0, (float)$user['rate_k'], 1e-9);
        $this->assertEqualsWithDelta (3.0, (float)$user['rate_d'], 1e-9);

        $this->assertSame (FTYP_RETURN + FTYP_EXPEDITION, (int)$this->lastFleetRow()['mission']);
    }

    public function testExpTraderFoundReplacesAWorseExistingOffer() : void
    {
        // A 0.1/0.1/0.1 offer is always beaten: offer 3 generates at least
        // 2.1 metal and 1.4 crystal for a fixed deuterium rate of 1.0.
        dbquery ("UPDATE ".$this->dbPrefix."users SET trader = 3, rate_m = 0.1, rate_k = 0.1, rate_d = 0.1
            WHERE player_id = ".$this->playerId);
        InvalidateUserCache ();

        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();

        mt_srand (99);
        Exp_TraderFound ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        $user = $this->userRow($this->playerId);
        $this->assertSame (3, (int)$user['trader'], 'the existing offer id is kept');
        $this->assertGreaterThanOrEqual (2.1, (float)$user['rate_m']);
        $this->assertLessThanOrEqual (3.0, (float)$user['rate_m']);
        $this->assertGreaterThanOrEqual (1.4, (float)$user['rate_k']);
        $this->assertLessThanOrEqual (2.0, (float)$user['rate_k']);
        $this->assertEqualsWithDelta (1.0, (float)$user['rate_d'], 1e-9);
    }

    public function testExpTraderFoundActivatesATraderWhenNoneIsActive() : void
    {
        dbquery ("UPDATE ".$this->dbPrefix."users SET trader = 0, rate_m = 0, rate_k = 0, rate_d = 0
            WHERE player_id = ".$this->playerId);
        InvalidateUserCache ();

        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();

        mt_srand (20240607);
        Exp_TraderFound ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en');

        $user = $this->userRow($this->playerId);
        $this->assertContains ((int)$user['trader'], array (1, 2, 3), 'a random offer must be activated');
        $this->assertGreaterThan (0.0, (float)$user['rate_m']);
        $this->assertGreaterThan (0.0, (float)$user['rate_k']);
        $this->assertGreaterThan (0.0, (float)$user['rate_d']);
    }

    // ========================================================================
    // ExpeditionArrive / ExpeditionHold
    // ========================================================================

    public function testExpeditionArriveStartsTheOrbitHold() : void
    {
        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);

        ExpeditionArrive ($queue, $fleetObj, $fleet, $origin, $target);

        $new = $this->lastFleetRow();
        $this->assertSame (FTYP_ORBITING + FTYP_EXPEDITION, (int)$new['mission']);
        // The hold time travels in the flight_time column of the orbiting fleet.
        $this->assertSame ((int)$fleetObj['deploy_time'], (int)$new['flight_time']);
        $this->assertSame ((int)$fleetObj['flight_time'], (int)$new['deploy_time']);
        $this->assertSame ((int)$origin['planet_id'], (int)$new['start_planet']);
        $this->assertSame ((int)$target['planet_id'], (int)$new['target_planet']);
        $this->assertSame (0, (int)$new['fuel']);
        foreach ( $fleet as $gid => $amount ) {
            $this->assertSame ($amount, (int)$new[$gid]);
        }

        $q = $this->fleetQueue ((int)$new['fleet_id']);
        $this->assertSame ((int)$queue['end'], (int)$q['start']);
        $this->assertSame ((int)$queue['end'] + (int)$fleetObj['deploy_time'], (int)$q['end']);
    }

    public function testExpeditionHoldReportsTheResultAndCountsTheVisit() : void
    {
        // chance_success = 0 guarantees the EXP_NOTHING branch. The hold time
        // is flight_time / 3600; a zero flight time keeps that an exact integer
        // (a fractional hold time is truncated by the int parameter of
        // Expedition(), see the report note).
        $this->seedExpeditionSettings(array ('chance_success' => 0));

        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleetObj['flight_time'] = 0;
        $fleet = $this->fleetFromRow($fleetObj);
        $metalBefore = (int)$target[GID_RC_METAL];

        ExpeditionHold ($queue, $fleetObj, $fleet, $origin, $target);

        // The visit counter lives in the metal column of the outer space object.
        $this->assertSame ($metalBefore + 1, (int)$this->planetRow((int)$target['planet_id'])[GID_RC_METAL]);

        // A return fleet is on its way home.
        $new = $this->lastFleetRow();
        $this->assertSame (FTYP_RETURN + FTYP_EXPEDITION, (int)$new['mission']);
        $this->assertSame ((int)$fleetObj['deploy_time'], (int)$new['flight_time']);

        // The expedition report was delivered as a game message.
        $result = dbquery ("SELECT * FROM ".$this->dbPrefix."messages WHERE owner_id = ".$this->playerId.
            " AND pm = ".MTYP_EXP. " ORDER BY msg_id DESC LIMIT 1");
        $message = dbarray ($result);
        $this->assertIsArray ($message);
        $this->assertSame (
            va (loca_lang ('EXP_MESSAGE_SUBJ', 'en'), (int)$target['g'], (int)$target['s'], (int)$target['p']),
            $message['subj']);
        $this->assertSame ((int)$queue['end'], (int)$message['date']);

        // The text is the "nothing happens" message plus the captain's logbook
        // (the expedition carries an espionage probe).
        $nothing = $this->locaSet('EXP_NOTHING_', 12);
        $found = false;
        foreach ( $nothing as $text ) {
            if ( str_contains ($message['text'], $text) ) $found = true;
        }
        $this->assertTrue ($found, 'the report must contain one of the EXP_NOTHING texts');

        $logbook = array_merge ($this->locaSet('EXP_NOT_DEPLETED_', 2));
        $found = false;
        foreach ( $logbook as $text ) {
            if ( str_contains ($message['text'], $text) ) $found = true;
        }
        $this->assertTrue ($found, 'the captain\'s logbook must be appended for probe fleets');
    }

    public function testExpeditionHoldWithoutProbesSkipsTheLogbook() : void
    {
        $this->seedExpeditionSettings(array ('chance_success' => 0));

        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleetObj['flight_time'] = 0;
        // A probe-free, cargo-free fleet: the probe check must skip the logbook.
        $fleet = $this->fleetArray();

        ExpeditionHold ($queue, $fleetObj, $fleet, $origin, $target);

        $result = dbquery ("SELECT * FROM ".$this->dbPrefix."messages WHERE owner_id = ".$this->playerId.
            " AND pm = ".MTYP_EXP." ORDER BY msg_id DESC LIMIT 1");
        $message = dbarray ($result);
        $this->assertIsArray ($message);

        foreach ( array_merge ($this->locaSet('EXP_NOT_DEPLETED_', 2), $this->locaSet('EXP_DEPLETED_MIN_', 3),
            $this->locaSet('EXP_DEPLETED_MED_', 3), $this->locaSet('EXP_DEPLETED_MAX_', 3)) as $text ) {
            $this->assertStringNotContainsString ($text, $message['text']);
        }

        // The visit counter is still bumped.
        $this->assertSame ((int)$target[GID_RC_METAL] + 1,
            (int)$this->planetRow((int)$target['planet_id'])[GID_RC_METAL]);
    }

    public function testExpBattleAliensStartsABattleAndReportsIt() : void
    {
        $this->makePlayerRanked();
        $this->addSpaceUser();

        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();

        // FixtureBuilder already stores one battle report of each kind.
        $textBefore = $this->countMessages (MTYP_BATTLE_REPORT_TEXT);
        $linkBefore = $this->countMessages (MTYP_BATTLE_REPORT_LINK);

        // The level is drawn from mt_rand(0, 99); a fixed seed keeps it stable.
        mt_srand (4321);
        $probe = mt_rand (0, 99);
        $level = $probe >= 99 ? 2 : ($probe >= 90 ? 1 : 0);

        mt_srand (4321);
        $msg = $this->battleSilently (fn () => Exp_BattleAliens ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en'));

        $expected = array ();
        foreach ( array ('EXP_ALIENS_WEAK_', 'EXP_ALIENS_MED_', 'EXP_ALIENS_STRONG_') as $i => $prefix ) {
            if ( $i === $level ) {
                $expected = $this->locaSet ($prefix, array (4, 3, 2)[$i]);
            }
        }
        $this->assertContains ($msg, $expected);

        // The battle was run and stored (ExpeditionBattle writes one row).
        $battle = $this->lastBattleDataRow();
        $this->assertSame ((int)$queue['end'], (int)$battle['date']);
        $this->assertNotSame ('', $battle['source'], 'the battle source data must be stored');

        // The attacker received a battle report body and a report link.
        $this->assertSame ($textBefore + 1, $this->countMessages (MTYP_BATTLE_REPORT_TEXT));
        $this->assertSame ($linkBefore + 1, $this->countMessages (MTYP_BATTLE_REPORT_LINK));
        $link = $this->lastMessage (MTYP_BATTLE_REPORT_LINK);
        $this->assertSame ((int)$queue['end'], (int)$link['date']);
        $this->assertMatchesRegularExpression ('/combatreport_ididattack_(iwon|ilost|draw)/', $link['subj']);
        $this->assertStringContainsString ('[1:1:16]', $link['subj']);
    }

    public function testExpBattlePiratesStartsABattleAndReportsIt() : void
    {
        $this->makePlayerRanked();
        $this->addSpaceUser();

        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $fleet = $this->fleetFromRow($fleetObj);
        $exptab = $this->expeditionSettings();

        // Same level draw as Exp_BattleAliens(), but the pirate message set.
        $textBefore = $this->countMessages (MTYP_BATTLE_REPORT_TEXT);
        $linkBefore = $this->countMessages (MTYP_BATTLE_REPORT_LINK);

        mt_srand (777);
        $probe = mt_rand (0, 99);
        $level = $probe >= 99 ? 2 : ($probe >= 90 ? 1 : 0);

        mt_srand (777);
        $msg = $this->battleSilently (fn () => Exp_BattlePirates ($exptab, $queue, $fleetObj, $fleet, $origin, $target, 'en'));

        $expected = array ();
        foreach ( array ('EXP_PIRATES_WEAK_', 'EXP_PIRATES_MED_', 'EXP_PIRATES_STRONG_') as $i => $prefix ) {
            if ( $i === $level ) {
                $expected = $this->locaSet ($prefix, array (5, 3, 2)[$i]);
            }
        }
        $this->assertContains ($msg, $expected);

        $battle = $this->lastBattleDataRow();
        $this->assertSame ((int)$queue['end'], (int)$battle['date']);
        $this->assertNotSame ('', $battle['source'], 'the battle source data must be stored');

        // The attacker gets the report body and the report link.
        $this->assertSame ($textBefore + 1, $this->countMessages (MTYP_BATTLE_REPORT_TEXT));
        $this->assertSame ($linkBefore + 1, $this->countMessages (MTYP_BATTLE_REPORT_LINK));
        $this->assertMatchesRegularExpression ('/combatreport_ididattack_(iwon|ilost|draw)/',
            $this->lastMessage (MTYP_BATTLE_REPORT_LINK)['subj']);
    }

    // ========================================================================
    // expedition_battle.php: WritebackBattleResultsExpedition
    // ========================================================================
    public function testWritebackWithRoundsReturnsTheSurvivingFleet() : void
    {
        list ($fleetObj, $queue) = $this->expeditionContext();
        $fleetId = (int)$fleetObj['fleet_id'];
        $units = $this->fleetArray(array (GID_F_LC => 1, GID_F_LF => 1));

        $a = array (0 => array ('id' => $fleetId, 'units' => $units));
        $d = array ();
        $res = array ('rounds' => array (
            array ('attackers' => array (array ('id' => $fleetId, 'units' => $units)), 'defenders' => array ()),
        ));

        WritebackBattleResultsExpedition ($a, $d, $res);

        $new = $this->lastFleetRow();
        $this->assertNotSame ($fleetId, (int)$new['fleet_id'], 'a new return fleet must be created');
        $this->assertSame (FTYP_EXPEDITION + FTYP_RETURN, (int)$new['mission']);
        $this->assertSame ((int)$fleetObj['deploy_time'], (int)$new['flight_time']);
        $this->assertSame (intdiv ((int)$fleetObj['fuel'], 2), (int)$new['fuel']);
        $this->assertSame ((int)$fleetObj['start_planet'], (int)$new['start_planet']);
        $this->assertSame ((int)$fleetObj['target_planet'], (int)$new['target_planet']);
        foreach ( $units as $gid => $amount ) {
            $this->assertSame ($amount, (int)$new[$gid]);
        }

        $q = $this->fleetQueue ((int)$new['fleet_id']);
        $this->assertSame ((int)$queue['end'], (int)$q['start']);
    }

    public function testWritebackWithoutRoundsUsesTheAttackerList() : void
    {
        list ($fleetObj, $queue) = $this->expeditionContext();
        $fleetId = (int)$fleetObj['fleet_id'];
        $units = $this->fleetArray(array (GID_F_PROBE => 3));

        $a = array (0 => array ('id' => $fleetId, 'units' => $units));
        $res = array ('rounds' => array ());

        WritebackBattleResultsExpedition ($a, array (), $res);

        $new = $this->lastFleetRow();
        $this->assertSame (FTYP_EXPEDITION + FTYP_RETURN, (int)$new['mission']);
        $this->assertSame (3, (int)$new[GID_F_PROBE]);
        $this->assertSame (0, (int)$new[GID_F_LC]);
        $this->assertSame (intdiv ((int)$fleetObj['fuel'], 2), (int)$new['fuel']);
        $this->assertSame ((int)$queue['end'], (int)$this->fleetQueue((int)$new['fleet_id'])['start']);
    }

    public function testWritebackSkipsAFullyDestroyedFleet() : void
    {
        list ($fleetObj) = $this->expeditionContext();
        $fleetId = (int)$fleetObj['fleet_id'];
        $countBefore = $this->fleetRowCount();

        $a = array (0 => array ('id' => $fleetId, 'units' => $this->fleetArray()));

        WritebackBattleResultsExpedition ($a, array (), array ('rounds' => array ()));

        $this->assertSame ($countBefore, $this->fleetRowCount(), 'a destroyed fleet must not be returned');
    }

    // ========================================================================
    // expedition_battle.php: ExpeditionBattle
    // ========================================================================

    public function testExpeditionBattlePiratesGiveTheDefenderWorseTechnology() : void
    {
        $this->makePlayerRanked();
        $this->addSpaceUser();

        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $when = 1700000000;
        $fleetId = $this->addExpeditionFleet (array (GID_F_LF => 300),
            (int)$fleetObj['start_planet'], (int)$fleetObj['target_planet'], $when - 3600);

        mt_srand (20240607);
        $result = $this->battleSilently (fn () => ExpeditionBattle ($fleetId, true, 0, $when));

        $this->assertSame (BATTLE_RESULT_AWON, $result,
            '300 Light Fighters must beat a weak pirate fleet built from 30% of them');

        $battle = $this->lastBattleDataRow();
        $this->assertSame ($when, (int)$battle['date']);

        // PlayerOne has weapons tech 3, the pirates fight with 3 levels less
        // (clamped at zero), so their slot shows "Weapons: 0%".
        $text = $this->lastMessage (MTYP_BATTLE_REPORT_TEXT)['text'];
        $this->assertStringContainsString ('Piraten', $text);
        $this->assertStringContainsString (loca_lang ('BATTLE_ATTACK', 'en').' 0%', $text);

        // The surviving ships are on their way home.
        $returning = $this->returnFleetRow();
        $this->assertSame (FTYP_EXPEDITION + FTYP_RETURN, (int)$returning['mission']);
    }

    public function testExpeditionBattleAliensGetThreeExtraTechnologyLevels() : void
    {
        $this->makePlayerRanked();
        $this->addSpaceUser();

        list ($fleetObj, $queue, $origin, $target) = $this->expeditionContext();
        $when = 1700000000;
        $fleetId = $this->addExpeditionFleet (array (GID_F_LF => 300),
            (int)$fleetObj['start_planet'], (int)$fleetObj['target_planet'], $when - 3600);

        mt_srand (20240607);
        $result = $this->battleSilently (fn () => ExpeditionBattle ($fleetId, false, 0, $when));

        $this->assertSame (BATTLE_RESULT_AWON, $result,
            '300 Light Fighters must beat a weak alien fleet built from 40% of them');

        $battle = $this->lastBattleDataRow();
        $this->assertSame ($when, (int)$battle['date']);

        // PlayerOne has weapons tech 3, the aliens get +3 -> 6 -> "60%".
        $text = $this->lastMessage (MTYP_BATTLE_REPORT_TEXT)['text'];
        $this->assertStringContainsString ('Aliens', $text);
        $this->assertStringContainsString (loca_lang ('BATTLE_ATTACK', 'en').' 60%', $text);

        // The battle data / result temp files must be cleaned up again.
        $this->assertFileDoesNotExist ('battledata/battle_'.(int)$battle['battle_id'].'.txt');
        $this->assertFileDoesNotExist ('battleresult/battle_'.(int)$battle['battle_id'].'.txt');
    }

    public function testExpeditionBattleOnlyKeepsTheLastTwoWeeksOfReports() : void
    {
        $this->makePlayerRanked();
        $this->addSpaceUser();

        // An old battle report row must be deleted by the cleanup query.
        AddDBRow (array ('date' => 1000, 'source' => '', 'title' => 'old', 'report' => 'old'), 'battledata');

        list ($fleetObj, $queue) = $this->expeditionContext();
        $when = 1700000000;

        mt_srand (20240607);
        $this->battleSilently (fn () => ExpeditionBattle ((int)$fleetObj['fleet_id'], true, 0, $when));

        $result = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."battledata WHERE date = 1000");
        $this->assertSame (0, (int)dbarray ($result)['cnt'], 'reports older than two weeks must be deleted');
    }

    /**
     * The final log write of ExpeditionBattle() interpolates the battle report
     * HTML, the localized title and the battle id into a single-quoted SQL
     * string without escaping it:
     *
     *   UPDATE battledata SET title = '...', report = '...' WHERE battle_id = N
     *
     * The title additionally contains literal backslash-escaped quotes
     * (onclick=\"fenster(\'index.php...), a MySQL-only escape that the SQLite
     * test backend rejects with a syntax error. The statement therefore never
     * runs here and the log row keeps its empty title/report; the battle
     * engine data (date, source) is unaffected.
     *
     * See the report note: because a French battle report contains apostrophes
     * ("L'attaquant a gagné la bataille!"), the same UNESCAPED interpolation is
     * a real problem on the production MySQL backend for French universes.
     * This test only pins the current behaviour of the SQLite backend.
     */
    public function testExpeditionBattleLogUpdateIsRejectedByTheSqliteBackend() : void
    {
        $this->makePlayerRanked();
        $this->addSpaceUser();

        list ($fleetObj, $queue) = $this->expeditionContext();
        $when = 1700000000;

        mt_srand (20240607);
        $this->battleSilently (fn () => ExpeditionBattle ((int)$fleetObj['fleet_id'], true, 0, $when));

        $battle = $this->lastBattleDataRow();
        $this->assertSame ('', $battle['title'], 'the raw UPDATE is rejected by SQLite');
        $this->assertSame ('', $battle['report']);
        $this->assertNotSame ('', $battle['source']);

        // The report body itself is delivered safely through AddDBRow().
        $this->assertNotSame ('', $this->lastMessage (MTYP_BATTLE_REPORT_TEXT)['text']);
    }

    // ========================================================================
    // Test-only helpers for the battle module
    // ========================================================================

    /**
     * Insert an outbound expedition fleet (plus its queue task) and return its id.
     */
    private function addExpeditionFleet(array $ships, int $startPlanet, int $targetPlanet, int $when) : int
    {
        $row = array (
            'owner_id' => $this->playerId, 'mission' => FTYP_EXPEDITION,
            'start_planet' => $startPlanet, 'target_planet' => $targetPlanet,
            'flight_time' => 3600, 'deploy_time' => 3600, 'fuel' => 500,
        );
        foreach ( $ships as $gid => $amount ) $row[$gid] = $amount;

        $fleetId = AddDBRow ($row, 'fleet');
        AddQueue ($this->playerId, QTYP_FLEET, $fleetId, 0, 0, $when, 3600, QUEUE_PRIO_FLEET + FTYP_EXPEDITION);

        return $fleetId;
    }

    /**
     * The newest row of the battle data table.
     */
    private function lastBattleDataRow() : array
    {
        $battle = dbarray (dbquery ("SELECT * FROM ".$this->dbPrefix."battledata ORDER BY battle_id DESC LIMIT 1"));
        $this->assertIsArray ($battle, 'a battle data row must have been written');
        return $battle;
    }

    /**
     * The newest returning expedition fleet (ignoring the outgoing fleets).
     */
    private function returnFleetRow() : array
    {
        $row = dbarray (dbquery ("SELECT * FROM ".$this->dbPrefix."fleet WHERE mission = ".
            (FTYP_EXPEDITION + FTYP_RETURN)." ORDER BY fleet_id DESC LIMIT 1"));
        $this->assertIsArray ($row, 'a returning expedition fleet must exist');
        return $row;
    }

    /**
     * The newest message of the given type for the test player.
     */
    private function lastMessage(int $pm) : array
    {
        $row = dbarray (dbquery ("SELECT * FROM ".$this->dbPrefix."messages WHERE owner_id = ".$this->playerId.
            " AND pm = $pm ORDER BY msg_id DESC LIMIT 1"));
        $this->assertIsArray ($row, "a message of type $pm must exist");
        return $row;
    }

    /**
     * Run a battle callable and swallow the query dump the SQLite backend
     * echoes for the rejected battledata log UPDATE (see
     * testExpeditionBattleLogUpdateIsRejectedByTheSqliteBackend).
     */
    private function battleSilently(callable $fn) : mixed
    {
        ob_start ();
        try {
            return $fn ();
        }
        finally {
            ob_end_clean ();
        }
    }

    private function countMessages(int $pm) : int
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."messages WHERE owner_id = ".$this->playerId.
            " AND pm = $pm");
        return (int)dbarray ($result)['cnt'];
    }
}
