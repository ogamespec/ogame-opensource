<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the planet / graviton / missile core modules:
 *  - game/core/planet.php   (planets, moons, debris fields, fields, phalanx)
 *  - game/core/graviton.php (Deathstar graviton attack on a moon)
 *  - game/core/raketen.php  (interplanetary missile attacks)
 *
 * They run against the real database layer with the in-memory SQLite backend
 * (DB_CONNECTION=sqlite, DB_DATABASE=:memory:, see phpunit.xml and
 * testing/bootstrap.php) and the real game schema created by CreateDBTables().
 * Every test starts from a fresh 3-player universe built by FixtureBuilder.
 *
 * Fixture layout used by the assertions below:
 *  - planets 1 (1:1:4 Home), 2 (1:1:5 Colony A), 3 (1:2:4 Colony B) belong to
 *    PlayerOne (id 1), 4 (1:3:4 Home), 5 (1:3:5), 6 (1:4:4) to PlayerTwo,
 *    7 (1:5:4 Home), 8 (1:5:5), 9 (1:6:4) to PlayerThree (id 3);
 *  - moons: 10 (1:1:4), 11 (1:2:4) PlayerOne, 12 (1:3:4) PlayerTwo,
 *    13 (1:5:4) PlayerThree;
 *  - 14 = debris field (1:2:5), 15 = colony phantom (1:1:6),
 *    16 = outer space (1:1:16).
 *
 * Not covered on purpose (they terminate the request and cannot be tested in
 * process): CreateHomePlanet()'s Error("No more planets!!!") fallback, and
 * GravitonAttack()'s Error("Only moons can be destroyed!") guard - both go
 * through Error() (game/core/debug.php), which exits the script.
 */
// The game core modules assign global variables ($defmap, $buildmap, $UnitParam,
// ...) at their top level; only the process-isolated child loads the bootstrap
// at the true top level, so the globals are available (same as RocketAttackTest).
#[RunTestsInSeparateProcesses]
class PlanetCoreTest extends TestCase
{
    private FixtureBuilder $fixture;

    protected function setUp(): void
    {
        // loca_add() resolves locale files relative to the game directory.
        chdir(__DIR__ . '/../game');

        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');

        global $GlobalUni, $db_prefix, $session, $loca_lang;
        $GlobalUni = $this->fixture->getUniData();
        $db_prefix = $this->fixture->getDbPrefix();
        $loca_lang = 'en';
        $session = 'testsession';

        // Sections localized by the tested modules. loca_add() uses
        // include_once, so repeating a call is harmless.
        loca_add('common', 'en');
        loca_add('technames', 'en');
        loca_add('raketen', 'en');
        loca_add('fleetmsg', 'en');
        loca_add('graviton', 'en');

        // AdjustStats() only touches regular players (banned = 0 AND admin = 0),
        // while the fixture leaves those columns NULL.
        dbquery("UPDATE {$db_prefix}users SET score1 = 1000000, score2 = 100000, score3 = 100000, banned = 0, admin = 0");
        InvalidateUserCache();
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /** Defense amounts with every defense type present (0 unless overridden). */
    private function defenses(array $overrides = []) : array
    {
        $target = array ();
        foreach ($GLOBALS['defmap'] as $gid) {
            $target[$gid] = 0;
        }
        foreach ($overrides as $gid => $count) {
            $target[$gid] = $count;
        }

        return $target;
    }

    /** Building levels with every building type present (0 unless overridden). */
    private function buildings(array $overrides = []) : array
    {
        $levels = array ();
        foreach ($GLOBALS['buildmap'] as $gid) {
            $levels[$gid] = 0;
        }
        foreach ($overrides as $gid => $level) {
            $levels[$gid] = $level;
        }

        return $levels;
    }

    /** Ship amounts with every ship type present (0 unless overridden). */
    private function ships(array $overrides = []) : array
    {
        $fleet = array ();
        foreach ($GLOBALS['fleetmap'] as $gid) {
            $fleet[$gid] = 0;
        }
        foreach ($overrides as $gid => $count) {
            $fleet[$gid] = $count;
        }

        return $fleet;
    }

    /** Read a single column of a planets row. */
    private function planetField(int $planetId, string $field) : mixed
    {
        $row = dbarray(dbquery('SELECT `' . $field . '` FROM test_planets WHERE planet_id = ' . $planetId));
        return $row === false ? null : $row[$field];
    }

    /** Read a single column of a fleet row. */
    private function fleetField(int $fleetId, string $field) : mixed
    {
        $row = dbarray(dbquery('SELECT `' . $field . '` FROM test_fleet WHERE fleet_id = ' . $fleetId));
        return $row === false ? null : $row[$field];
    }

    private function planetName(int $planetId) : string
    {
        return (string) $this->planetField($planetId, 'name');
    }

    /** Run a "SELECT COUNT(*) AS cnt" query and return the count. */
    private function countRows(string $sql) : int
    {
        $row = dbarray(dbquery($sql));
        return $row === false ? 0 : (int) $row['cnt'];
    }

    /** Drain a query result into the list of planet ids it contains. */
    private function planetIds(mixed $result) : array
    {
        $ids = array ();
        while ($row = dbarray($result)) {
            $ids[] = (int) $row['planet_id'];
        }

        return $ids;
    }

    private function scoreOf(int $playerId) : int
    {
        InvalidateUserCache();
        return (int) LoadUser($playerId)['score1'];
    }

    private function fleetScoreOf(int $playerId) : int
    {
        InvalidateUserCache();
        return (int) LoadUser($playerId)['score2'];
    }

    /** Number of messages of the given type a player received at the given time. */
    private function messageCount(int $playerId, int $when, int $pm) : int
    {
        return $this->countRows(
            "SELECT COUNT(*) AS cnt FROM test_messages WHERE owner_id = $playerId AND pm = $pm AND date = $when"
        );
    }

    // ========================================================================
    // CreatePlanet
    // ========================================================================

    /**
     * A home planet uses the fixed diameter 12800, starts with 500/500
     * resources, has no built fields and gets its temperature from the
     * position tier (with a seeded rand() offset).
     */
    public function testCreateHomePlanetUsesTheFixedDiameterAndInitialResources() : void
    {
        $when = 1700000000;

        // Position 4 uses the second temperature tier: 30 + rand()%10 - 2*p.
        mt_srand(20240101);
        $expectedTemp = 30 + (mt_rand() % 10) - 2 * 4;
        mt_srand(20240101);

        $id = CreatePlanet(1, 9, 4, 1, 0, 0, 0, $when);
        $this->assertGreaterThan(0, $id);

        $planet = LoadPlanetById($id);
        $this->assertSame(PTYP_PLANET, (int) $planet['type']);
        $this->assertSame('Homeplanet', $planet['name']);
        $this->assertSame(12800, (int) $planet['diameter']);
        $this->assertSame(163, (int) $planet['maxfields']);    // floor((12800/1000)^2)
        $this->assertSame(0, (int) $planet['fields']);
        $this->assertSame(500, (int) $planet[GID_RC_METAL]);
        $this->assertSame(500, (int) $planet[GID_RC_CRYSTAL]);
        $this->assertSame(0, (int) $planet[GID_RC_DEUTERIUM]);
        $this->assertSame($expectedTemp, (int) $planet['temp']);
        $this->assertSame($when, (int) $planet['date']);
        $this->assertSame($when, (int) $planet['lastpeek']);
        $this->assertSame($when, (int) $planet['lastakt']);
        $this->assertSame(1, (int) $planet['owner_id']);
        $this->assertSame(1, (int) $planet['g']);
        $this->assertSame(9, (int) $planet['s']);
        $this->assertSame(4, (int) $planet['p']);
    }

    /**
     * A colony takes its diameter from the colonization settings of its
     * position tier (a..b random value times c).
     */
    public function testCreateColonyDiameterFollowsTheTierSettings() : void
    {
        $when = 1700000000;

        // Pin tier 2 (positions 4..6) to one single diameter: 3000 * 100.
        $settings = LoadColonySettings();
        $settings['t2_a'] = 3000;
        $settings['t2_b'] = 3000;
        $settings['t2_c'] = 100;
        SaveColonySettings($settings);

        $id = CreatePlanet(1, 9, 5, 1, 1, 0, 0, $when);
        $colony = LoadPlanetById($id);
        $this->assertSame('Colony', $colony['name']);
        $this->assertSame(300000, (int) $colony['diameter']);
        $this->assertSame(90000, (int) $colony['maxfields']);   // floor((300000/1000)^2)
        $this->assertSame(500, (int) $colony[GID_RC_METAL]);
        $this->assertSame(500, (int) $colony[GID_RC_CRYSTAL]);
        // Position 5 -> second temperature tier: 30 + rand()%10 - 2*5.
        $this->assertGreaterThanOrEqual(20, (int) $colony['temp']);
        $this->assertLessThanOrEqual(29, (int) $colony['temp']);

        // Position 1 uses tier 1, which is still 1100..5000 * 1000.
        $id2 = CreatePlanet(1, 9, 1, 1, 1, 0, 0, $when);
        $diam = (int) LoadPlanetById($id2)['diameter'];
        $this->assertGreaterThanOrEqual(1100000, $diam);
        $this->assertLessThanOrEqual(5000000, $diam);
        $this->assertSame(0, $diam % 1000);
    }

    /**
     * Moons have one field, no starting resources and a size that grows with
     * the moon chance: floor(1000 * sqrt(rand(10,20) + 3*chance)). The
     * temperature is derived from the planet below.
     */
    public function testCreateMoonDiameterTemperatureAndFields() : void
    {
        $when = 1700000000;

        // Planet 2 (1:1:5, temp 60) has no moon yet.
        $moon = LoadPlanetById(CreatePlanet(1, 1, 5, 1, 1, 1, 0, $when));
        $this->assertSame(PTYP_MOON, (int) $moon['type']);
        $this->assertSame('Moon', $moon['name']);
        $this->assertSame(0, (int) $moon['fields']);        // nothing built yet
        $this->assertSame(1, (int) $moon['maxfields']);
        $this->assertSame(0, (int) $moon[GID_RC_METAL]);
        $this->assertSame(0, (int) $moon[GID_RC_CRYSTAL]);
        $this->assertSame(0, (int) $moon[GID_RC_DEUTERIUM]);
        $this->assertSame($when, (int) $moon['date']);
        $this->assertGreaterThanOrEqual(3162, (int) $moon['diameter']);     // 1000*sqrt(10)
        $this->assertLessThanOrEqual(4472, (int) $moon['diameter']);        // 1000*sqrt(20)
        $this->assertGreaterThanOrEqual(30, (int) $moon['temp']);           // 60 - 30
        $this->assertLessThanOrEqual(40, (int) $moon['temp']);              // 60 - 20

        // Moon chance 10 raises the random range to rand(10,20) + 30.
        $moon2 = LoadPlanetById(CreatePlanet(1, 3, 5, 1, 1, 1, 10, $when));
        $this->assertGreaterThanOrEqual(6324, (int) $moon2['diameter']);    // 1000*sqrt(40)
        $this->assertLessThanOrEqual(7071, (int) $moon2['diameter']);       // 1000*sqrt(50)
        $this->assertGreaterThanOrEqual(28, (int) $moon2['temp']);          // 58 - 30
        $this->assertLessThanOrEqual(38, (int) $moon2['temp']);             // 58 - 20
    }

    /**
     * Occupied coordinates are refused; colony phantoms and debris fields do
     * not block a planet (only planets, destroyed planets and abandoned
     * colonies are checked), and an unknown owner is refused.
     */
    public function testCreatePlanetRefusesOccupiedCoordinates() : void
    {
        $when = 1700000000;

        // PlayerOne's home planet and its moon already occupy 1:1:4.
        $this->assertSame(0, CreatePlanet(1, 1, 4, 1, 1, 0, 0, $when));
        $this->assertSame(0, CreatePlanet(1, 1, 4, 1, 1, 1, 0, $when));
        // Colony A is at 1:1:5.
        $this->assertSame(0, CreatePlanet(1, 1, 5, 2, 1, 0, 0, $when));
        // The colony phantom (1:1:6) and the debris field (1:2:5) do not block.
        $this->assertGreaterThan(0, CreatePlanet(1, 1, 6, 1, 0, 0, 0, $when));
        $this->assertGreaterThan(0, CreatePlanet(1, 2, 5, 1, 0, 0, 0, $when));
        // Unknown owner.
        $this->assertSame(0, CreatePlanet(1, 9, 4, 999999, 0, 0, 0, $when));
    }

    /**
     * The home planet is placed at the first free position with 4 <= p <= 12.
     */
    public function testCreateHomePlanetFindsAFreePosition() : void
    {
        $playerId = 50;
        AddDBRow(array (
            'player_id' => $playerId, 'name' => 'Newbie', 'oname' => 'Newbie',
            'lang' => 'en', 'admin' => 0, 'validated' => 1,
        ), 'users');
        InvalidateUserCache();

        $before = $this->countRows('SELECT COUNT(*) AS cnt FROM test_planets');

        $id = CreateHomePlanet($playerId);

        $this->assertGreaterThan(0, $id);
        $planet = LoadPlanetById($id);
        $this->assertSame($playerId, (int) $planet['owner_id']);
        $this->assertSame(PTYP_PLANET, (int) $planet['type']);
        $this->assertSame('Homeplanet', $planet['name']);
        $this->assertSame(12800, (int) $planet['diameter']);
        $this->assertSame(1, (int) $planet['g']);
        $this->assertGreaterThanOrEqual(4, (int) $planet['p']);
        $this->assertLessThanOrEqual(12, (int) $planet['p']);
        $this->assertSame($before + 1, $this->countRows('SELECT COUNT(*) AS cnt FROM test_planets'));

        // Suspected bug (documented, not fixed here): CreateHomePlanet()
        // excludes PTYP_COLONY_PHANTOM from its occupancy query, so the first
        // free position can be one that already carries a colonization
        // phantom. In the fixture that is 1:1:6 (the phantom lives there), and
        // CreatePlanet() happily builds the home planet on top of it.
        $this->assertSame(1, (int) $planet['s']);
        $this->assertSame(6, (int) $planet['p']);
        $this->assertNotNull(LoadPlanet(1, 1, 6, PTYP_COLONY_PHANTOM));
    }

    // ========================================================================
    // EnumPlanets / EnumPlanetsGalaxy / EnumCustomPlanetsGalaxy
    // ========================================================================

    /**
     * EnumPlanets lists the planets and moons of the current player (no debris
     * fields) and applies the requested sort criterion.
     */
    public function testEnumPlanetsOrdersBySelectedCriterion() : void
    {
        $GLOBALS['GlobalUser'] = array ('player_id' => 1, 'sortby' => 0, 'sortorder' => 0);

        // 3 planets + 2 moons, the debris field (type >= PTYP_DF) is excluded.
        $result = EnumPlanets();
        $this->assertSame(5, dbrows($result));
        $this->assertSame(array (1, 2, 3, 10, 11), $this->planetIds($result));

        // By coordinates: 1:1:4 (planet before moon), 1:1:5, 1:2:4 (planet before moon).
        $GLOBALS['GlobalUser']['sortby'] = 1;
        $this->assertSame(array (1, 10, 2, 3, 11), $this->planetIds(EnumPlanets()));

        // Alphabetically: Colony A, Colony B, Home, Moon, Moon B.
        $GLOBALS['GlobalUser']['sortby'] = 2;
        $this->assertSame(array (2, 3, 1, 10, 11), $this->planetIds(EnumPlanets()));
    }

    /**
     * Descending order is honoured, and an unknown sort criterion means "no
     * ORDER BY" while the row set stays the same.
     */
    public function testEnumPlanetsDescendingOrderAndUnknownSort() : void
    {
        $GLOBALS['GlobalUser'] = array ('player_id' => 1, 'sortby' => 0, 'sortorder' => 1);
        $this->assertSame(array (11, 10, 3, 2, 1), $this->planetIds(EnumPlanets()));

        $GLOBALS['GlobalUser']['sortby'] = 99;
        $this->assertSame(5, dbrows(EnumPlanets()));
    }

    /**
     * EnumPlanetsGalaxy returns only planets of a system, EnumCustomPlanetsGalaxy
     * only custom galaxy objects (PTYP_CUSTOM and above, not PTYP_FARSPACE).
     */
    public function testEnumPlanetsGalaxyAndCustomObjects() : void
    {
        $this->assertSame(array (1, 2), $this->planetIds(EnumPlanetsGalaxy(1, 1)));
        $this->assertSame(array (3), $this->planetIds(EnumPlanetsGalaxy(1, 2)));

        // The fixture has no custom objects; outer space (20000) is below PTYP_CUSTOM.
        $this->assertSame(0, dbrows(EnumCustomPlanetsGalaxy(1, 1)));

        $customId = AddDBRow(array (
            'name' => 'Wormhole', 'type' => PTYP_CUSTOM + 4, 'g' => 1, 's' => 1, 'p' => 7,
            'owner_id' => USER_SPACE, 'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0,
            'date' => 1700000000, GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
        ), 'planets');

        $this->assertSame(array ($customId), $this->planetIds(EnumCustomPlanetsGalaxy(1, 1)));
        $this->assertSame(2, dbrows(EnumPlanetsGalaxy(1, 1)));
    }

    // ========================================================================
    // LoadPlanet / LoadPlanetById / PlanetHasMoon
    // ========================================================================

    /**
     * LoadPlanet selects by coordinate and object type: 1 = planet, 2 = debris
     * field, 3 = moon, anything else = the game type of a custom object.
     */
    public function testLoadPlanetByCoordinateAndType() : void
    {
        $this->assertSame(1, (int) LoadPlanet(1, 1, 4, 1)['planet_id']);
        $this->assertSame(2, (int) LoadPlanet(1, 1, 5, 1)['planet_id']);

        $debrisId = HasDebris(1, 2, 5);
        $this->assertSame($debrisId, (int) LoadPlanet(1, 2, 5, 2)['planet_id']);

        $moonId = PlanetHasMoon(1);
        $this->assertSame($moonId, (int) LoadPlanet(1, 1, 4, 3)['planet_id']);

        $outerId = (int) LoadPlanet(1, 1, 16, PTYP_FARSPACE)['planet_id'];
        $this->assertSame(PTYP_FARSPACE, (int) LoadPlanet(1, 1, 16, PTYP_FARSPACE)['type']);
        $this->assertGreaterThan(0, $outerId);

        // A planet is not a moon; there is no moon at 1:1:5.
        $this->assertFalse(LoadPlanet(1, 1, 5, 3));
        // Nothing at all at these coordinates (LoadPlanet returns false, not null).
        $this->assertFalse(LoadPlanet(9, 9, 9, 1));
    }

    /**
     * LoadPlanetById loads any object type by id and returns null for a
     * missing row.
     */
    public function testLoadPlanetByIdReturnsNullForMissingRow() : void
    {
        $moon = LoadPlanetById(12);
        $this->assertIsArray($moon);
        $this->assertSame(PTYP_MOON, (int) $moon['type']);
        $this->assertSame(2, (int) $moon['owner_id']);

        $debris = LoadPlanetById(14);
        $this->assertSame(PTYP_DF, (int) $debris['type']);

        $this->assertNull(LoadPlanetById(999999));
    }

    /**
     * PlanetHasMoon returns the id of the co-located moon (destroyed moons
     * included), 0 for a planet without a moon, 0 for a moon itself and 0 for
     * an unknown planet.
     */
    public function testPlanetHasMoon() : void
    {
        $this->assertSame(10, PlanetHasMoon(1));
        $this->assertSame(11, PlanetHasMoon(3));
        $this->assertSame(0, PlanetHasMoon(2));        // colony without a moon
        $this->assertSame(0, PlanetHasMoon(12));       // the moon itself
        $this->assertSame(0, PlanetHasMoon(999999));   // unknown planet

        // A destroyed moon still counts as the planet's moon.
        $destroyed = AddDBRow(array (
            'name' => 'Abandoned', 'type' => PTYP_DEST_MOON, 'g' => 1, 's' => 1, 'p' => 5,
            'owner_id' => 1, 'diameter' => 8000, 'temp' => -30, 'fields' => 0, 'maxfields' => 0,
            'date' => 1700000000, GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
            'lastpeek' => 0, 'lastakt' => 0, 'gate_until' => 0, 'remove' => 0,
        ), 'planets');
        $this->assertSame($destroyed, PlanetHasMoon(2));
    }

    // ========================================================================
    // RenamePlanet
    // ========================================================================

    /**
     * RenamePlanet truncates to 20 characters, strips forbidden characters
     * instead of rejecting them, collapses repeated whitespace and trims.
     */
    public function testRenamePlanetSanitizesAndTruncatesTheName() : void
    {
        RenamePlanet(1, str_repeat('A', 30));
        $this->assertSame(str_repeat('A', 20), $this->planetName(1));

        // Quotes, backslashes, brackets and asterisks are removed.
        RenamePlanet(1, 'He said "hi" (really)*');
        $this->assertSame('He said hi really', $this->planetName(1));

        // Repeated whitespace is collapsed (the name below is short enough not
        // to be truncated).
        RenamePlanet(1, 'Lots    of   space');
        $this->assertSame('Lots of space', $this->planetName(1));

        // Current behaviour: the 20 character limit is applied before the name
        // is trimmed, so leading blanks eat into the limit.
        RenamePlanet(1, '   ABCDEFGHIJKLMNOPQRST');
        $this->assertSame('ABCDEFGHIJKLMNOPQ', $this->planetName(1));
    }

    /**
     * A name containing a forbidden character (; , < > `) is rejected and the
     * old name is kept - this also blocks SQL injection through the name.
     */
    public function testRenamePlanetRejectsForbiddenCharacters() : void
    {
        foreach (array ('Bad;Name', 'Bad<Name>', 'Bad`Name', 'Bad,Name', "Bad'; DROP TABLE test_planets; --") as $bad) {
            RenamePlanet(2, $bad);
            $this->assertSame('Colony A', $this->planetName(2));
        }
        // The planets table is still intact.
        $this->assertSame(16, $this->countRows('SELECT COUNT(*) AS cnt FROM test_planets'));
    }

    /**
     * An empty (or whitespace-only) name falls back to the default name.
     */
    public function testRenamePlanetFallsBackToDefaultNameWhenEmpty() : void
    {
        RenamePlanet(1, "\"'*()\\");
        $this->assertSame('планета', $this->planetName(1));

        RenamePlanet(2, '     ');
        $this->assertSame('планета', $this->planetName(2));
    }

    /**
     * A moon name is limited to 13 characters (20 minus " (Moon)") and gets
     * the suffix; the empty fallback keeps the bare localized name.
     */
    public function testRenameMoonAddsSuffixAndUsesAShorterLimit() : void
    {
        RenamePlanet(12, 'Base');
        $this->assertSame('Base (Moon)', $this->planetName(12));

        RenamePlanet(12, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $this->assertSame('ABCDEFGHIJKLM (Moon)', $this->planetName(12));

        RenamePlanet(12, '"*()');
        $this->assertSame('Moon', $this->planetName(12));
    }

    /**
     * Renaming an unknown planet is a no-op.
     */
    public function testRenamePlanetWithUnknownIdDoesNothing() : void
    {
        RenamePlanet(999999, 'Nope');
        $this->assertSame('Home', $this->planetName(1));
    }

    // ========================================================================
    // DestroyPlanet / UpdatePlanetActivity
    // ========================================================================

    /**
     * DestroyPlanet removes the row and flushes the planet's construction queue.
     */
    public function testDestroyPlanetRemovesRowAndFlushesQueue() : void
    {
        AddDBRow(array (
            'owner_id' => 1, 'planet_id' => 3, 'list_id' => 1, 'tech_id' => GID_B_METAL_MINE,
            'level' => 4, 'destroy' => 0, 'start' => 1700000000, 'end' => 1700000600,
        ), 'buildqueue');
        $this->assertSame(1, $this->countRows('SELECT COUNT(*) AS cnt FROM test_buildqueue WHERE planet_id = 3'));

        DestroyPlanet(3);

        $this->assertNull(LoadPlanetById(3));
        $this->assertSame(0, $this->countRows('SELECT COUNT(*) AS cnt FROM test_buildqueue WHERE planet_id = 3'));
        // The queue of the other planets is untouched.
        $this->assertSame(2, $this->countRows('SELECT COUNT(*) AS cnt FROM test_buildqueue WHERE planet_id = 1'));
    }

    /**
     * UpdatePlanetActivity writes the given timestamp, or the current time
     * when it is 0.
     */
    public function testUpdatePlanetActivityWritesTheTimestamp() : void
    {
        $when = 1600000000;
        UpdatePlanetActivity(1, $when);
        $this->assertSame($when, (int) $this->planetField(1, 'lastakt'));

        $before = time();
        UpdatePlanetActivity(1);
        $this->assertEqualsWithDelta($before, (int) $this->planetField(1, 'lastakt'), 5);
    }

    // ========================================================================
    // Debris fields
    // ========================================================================

    /**
     * HasDebris finds the field at the given coordinates, CreateDebris reuses
     * an existing field and creates an empty one otherwise.
     */
    public function testCreateDebrisIsIdempotentPerCoordinate() : void
    {
        $this->assertSame(0, HasDebris(1, 1, 1));

        $fieldId = HasDebris(1, 2, 5);
        $this->assertSame(14, $fieldId);
        $field = LoadPlanetById($fieldId);
        $this->assertSame(PTYP_DF, (int) $field['type']);
        $this->assertSame(500000, (int) $field[GID_RC_METAL]);
        $this->assertSame(300000, (int) $field[GID_RC_CRYSTAL]);

        // Existing field: the id is returned, no second row is created.
        $this->assertSame($fieldId, CreateDebris(1, 2, 5, 1));
        $this->assertSame(16, $this->countRows('SELECT COUNT(*) AS cnt FROM test_planets'));

        // New field: empty, owned by the given player, named by loca("DEBRIS").
        $newId = CreateDebris(1, 7, 1, 2);
        $this->assertNotSame($fieldId, $newId);
        $new = LoadPlanetById($newId);
        $this->assertSame(PTYP_DF, (int) $new['type']);
        $this->assertSame('Debris Field', $new['name']);
        $this->assertSame(2, (int) $new['owner_id']);
        $this->assertSame(0, (int) $new[GID_RC_METAL]);
        $this->assertSame($newId, HasDebris(1, 7, 1));
    }

    /**
     * HarvestDebris splits the cargo between metal and crystal (half each
     * first) and returns one entry per transportable resource.
     */
    public function testHarvestDebrisSplitsTheCargoBetweenTheResources() : void
    {
        $when = 1700000000;

        $harvest = HarvestDebris(14, 1000, $when);

        $this->assertSame(
            array (GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM),
            array_keys($harvest)
        );
        $this->assertSame(500, (int) $harvest[GID_RC_METAL]);
        $this->assertSame(500, (int) $harvest[GID_RC_CRYSTAL]);
        $this->assertSame(0, (int) $harvest[GID_RC_DEUTERIUM]);

        $field = LoadPlanetById(14);
        $this->assertSame(499500, (int) $field[GID_RC_METAL]);
        $this->assertSame(299500, (int) $field[GID_RC_CRYSTAL]);
        $this->assertSame($when, (int) $field['lastpeek']);
    }

    /**
     * When the cargo is larger than the field, everything is harvested and the
     * leftover cargo is spent on the remaining metal.
     */
    public function testHarvestDebrisIsCappedByTheAvailableResources() : void
    {
        $when = 1700000000;

        // Cargo 700000: 350000 metal, then all 300000 crystal, then the
        // remaining 50000 cargo back into metal -> 400000 metal + 300000 crystal.
        $harvest = HarvestDebris(14, 700000, $when);

        $this->assertSame(400000, (int) $harvest[GID_RC_METAL]);
        $this->assertSame(300000, (int) $harvest[GID_RC_CRYSTAL]);
        $this->assertSame(0, (int) $harvest[GID_RC_DEUTERIUM]);

        $field = LoadPlanetById(14);
        $this->assertSame(100000, (int) $field[GID_RC_METAL]);
        $this->assertSame(0, (int) $field[GID_RC_CRYSTAL]);
    }

    /**
     * AddDebris adds metal and crystal to the field.
     */
    public function testAddDebrisIncreasesTheStoredResources() : void
    {
        AddDebris(14, 1000, 500);

        $field = LoadPlanetById(14);
        $this->assertSame(501000, (int) $field[GID_RC_METAL]);
        $this->assertSame(300500, (int) $field[GID_RC_CRYSTAL]);
        $this->assertSame(0, (int) $field[GID_RC_DEUTERIUM]);
    }

    // ========================================================================
    // GetPlanetType / HasPlanet / abandoned colonies / phantoms / outer space
    // ========================================================================

    /**
     * GetPlanetType maps the database types to the game types used by the
     * galaxy and passes custom object types through unchanged.
     */
    public function testGetPlanetTypeMapping() : void
    {
        $cases = array (
            array (PTYP_PLANET, GAME_PTYP_PLANET),
            array (PTYP_DEST_PLANET, GAME_PTYP_PLANET),
            array (PTYP_COLONY_PHANTOM, GAME_PTYP_PLANET),
            array (PTYP_ABANDONED, GAME_PTYP_PLANET),
            array (PTYP_FARSPACE, GAME_PTYP_PLANET),
            array (PTYP_MOON, GAME_PTYP_MOON),
            array (PTYP_DEST_MOON, GAME_PTYP_MOON),
            array (PTYP_DF, GAME_PTYP_DF),
        );
        foreach ($cases as $case) {
            $this->assertSame($case[1], GetPlanetType(array ('type' => $case[0])));
        }

        $this->assertSame(PTYP_CUSTOM, GetPlanetType(array ('type' => PTYP_CUSTOM)));
        $this->assertSame(PTYP_CUSTOM + 7, GetPlanetType(array ('type' => PTYP_CUSTOM + 7)));
    }

    /**
     * HasPlanet only sees planets, destroyed planets and abandoned colonies -
     * not moons, debris fields, phantoms or outer space objects.
     */
    public function testHasPlanetIgnoresNonPlanetObjects() : void
    {
        $this->assertTrue(HasPlanet(1, 1, 4));
        $this->assertTrue(HasPlanet(1, 3, 4));
        $this->assertFalse(HasPlanet(1, 1, 6));     // colony phantom
        $this->assertFalse(HasPlanet(1, 2, 5));     // debris field
        $this->assertFalse(HasPlanet(1, 1, 16));    // outer space
        $this->assertFalse(HasPlanet(1, 1, 1));

        $this->assertGreaterThan(0, CreateAbandonedColony(1, 1, 7, 1700000000));
        $this->assertTrue(HasPlanet(1, 1, 7));
        // An occupied position is refused.
        $this->assertSame(0, CreateAbandonedColony(1, 1, 7, 1700000000));
        $this->assertSame(0, CreateAbandonedColony(1, 1, 4, 1700000000));
    }

    /**
     * CreateAbandonedColony creates a technical USER_SPACE object that removes
     * itself a day later; CreateColonyPhantom does the same for colonization
     * (without an occupancy check).
     */
    public function testCreateAbandonedColonyAndColonyPhantom() : void
    {
        $when = 1700000000;

        $abandonedId = CreateAbandonedColony(1, 12, 4, $when);
        $this->assertGreaterThan(0, $abandonedId);
        $abandoned = LoadPlanetById($abandonedId);
        $this->assertSame(PTYP_ABANDONED, (int) $abandoned['type']);
        $this->assertSame('Planet abandoned', $abandoned['name']);
        $this->assertSame(USER_SPACE, (int) $abandoned['owner_id']);
        $this->assertSame($when, (int) $abandoned['date']);
        $this->assertSame($when + 24 * 3600, (int) $abandoned['remove']);

        $before = time();
        $phantomId = CreateColonyPhantom(1, 11, 4, 3);
        $phantom = LoadPlanetById($phantomId);
        $this->assertSame(PTYP_COLONY_PHANTOM, (int) $phantom['type']);
        $this->assertSame('Planet', $phantom['name']);
        $this->assertSame(3, (int) $phantom['owner_id']);
        $this->assertEqualsWithDelta($before + 24 * 3600, (int) $phantom['remove'], 5);
    }

    /**
     * CreateOuterSpace reuses the object at the coordinates and creates it
     * otherwise (owned by USER_SPACE, type PTYP_FARSPACE).
     */
    public function testCreateOuterSpaceIsIdempotentPerCoordinate() : void
    {
        $existing = (int) LoadPlanet(1, 1, 16, PTYP_FARSPACE)['planet_id'];
        $this->assertSame($existing, CreateOuterSpace(1, 1, 16));

        $newId = CreateOuterSpace(1, 10, 1);
        $this->assertGreaterThan(0, $newId);
        $this->assertSame($newId, CreateOuterSpace(1, 10, 1));

        $outer = LoadPlanetById($newId);
        $this->assertSame(PTYP_FARSPACE, (int) $outer['type']);
        $this->assertSame('Far space', $outer['name']);
        $this->assertSame(USER_SPACE, (int) $outer['owner_id']);
    }

    // ========================================================================
    // AdjustResources / setters / RecalcFields
    // ========================================================================

    /**
     * AdjustResources adds and subtracts quantities and clamps the stored
     * amount at zero; an unknown planet id is ignored.
     */
    public function testAdjustResourcesAddsSubtractsAndClamps() : void
    {
        $this->assertSame(25000, (int) $this->planetField(1, (string) GID_RC_METAL));

        AdjustResources(array (GID_RC_METAL => 1000), 1, '+');
        $this->assertSame(26000, (int) $this->planetField(1, (string) GID_RC_METAL));

        AdjustResources(array (GID_RC_METAL => 5000, GID_RC_CRYSTAL => 5000), 1, '-');
        $this->assertSame(21000, (int) $this->planetField(1, (string) GID_RC_METAL));
        $this->assertSame(10000, (int) $this->planetField(1, (string) GID_RC_CRYSTAL));

        // Subtracting more than the planet holds never stores a negative amount.
        AdjustResources(array (GID_RC_METAL => 999999), 1, '-');
        $this->assertSame(0, (int) $this->planetField(1, (string) GID_RC_METAL));

        // Unknown planet: silently ignored.
        AdjustResources(array (GID_RC_METAL => 5), 999999, '-');
        $this->assertEqualsWithDelta(time(), (int) $this->planetField(1, 'lastpeek'), 5);
    }

    /**
     * SetPlanetDefense writes every defense column, SetPlanetBuildings every
     * building column.
     */
    public function testSetPlanetDefenseAndBuildingsWriteEveryColumn() : void
    {
        SetPlanetDefense(4, $this->defenses(array (GID_D_RL => 9, GID_D_IPM => 4)));
        $planet = LoadPlanetById(4);
        $this->assertSame(9, (int) $planet[GID_D_RL]);
        $this->assertSame(0, (int) $planet[GID_D_LL]);
        $this->assertSame(0, (int) $planet[GID_D_ABM]);
        $this->assertSame(4, (int) $planet[GID_D_IPM]);

        SetPlanetBuildings(1, $this->buildings(array (GID_B_METAL_MINE => 7, GID_B_TERRAFORMER => 3)));
        $planet = LoadPlanetById(1);
        $this->assertSame(7, (int) $planet[GID_B_METAL_MINE]);
        $this->assertSame(3, (int) $planet[GID_B_TERRAFORMER]);
        $this->assertSame(0, (int) $planet[GID_B_NANITES]);
        $this->assertSame(0, (int) $planet[GID_B_CRYS_MINE]);
    }

    /**
     * SetPlanetFleetDefense writes the fleet and the shield/planetary defenses
     * but deliberately never touches the stored missiles (ABM/IPM): they are
     * not part of the parameter list ($rakmap is subtracted).
     */
    public function testSetPlanetFleetDefenseSkipsStoredMissiles() : void
    {
        // "+" keeps the numeric keys of both maps (array_merge would renumber them).
        $objects = $this->defenses(array (GID_D_RL => 7)) + $this->ships(array (GID_F_LF => 3));

        SetPlanetFleetDefense(1, $objects);

        $planet = LoadPlanetById(1);
        $this->assertSame(7, (int) $planet[GID_D_RL]);
        $this->assertSame(0, (int) $planet[GID_D_LL]);      // present in the list -> reset
        $this->assertSame(3, (int) $planet[GID_F_LF]);
        $this->assertSame(0, (int) $planet[GID_F_SC]);      // missing in the list -> 0
        $this->assertSame(2, (int) $planet[GID_D_ABM]);     // untouched
        $this->assertSame(1, (int) $planet[GID_D_IPM]);     // untouched
    }

    /**
     * SetPlanetDiameter stores the new diameter and recalculates the fields.
     */
    public function testSetPlanetDiameterRecalculatesFields() : void
    {
        SetPlanetDiameter(2, 20000);

        $planet = LoadPlanetById(2);
        $this->assertSame(20000, (int) $planet['diameter']);
        $this->assertSame(400, (int) $planet['maxfields']);   // floor(20^2)
        $this->assertSame(21, (int) $planet['fields']);       // sum of the building levels
    }

    /**
     * RecalcFields counts the used fields as the sum of all building levels;
     * the maximum is the diameter square (planets) or one plus 3 per moon base
     * (moons), plus 5 per terraformer.
     */
    public function testRecalcFieldsForPlanetAndMoon() : void
    {
        RecalcFields(1);
        $this->assertSame(41, (int) $this->planetField(1, 'fields'));
        $this->assertSame(148, (int) $this->planetField(1, 'maxfields'));    // floor((12200/1000)^2)

        RecalcFields(10);
        $this->assertSame(23, (int) $this->planetField(10, 'fields'));
        $this->assertSame(16, (int) $this->planetField(10, 'maxfields'));    // 1 + 3 * moon base 5

        // A terraformer adds 5 fields per level.
        dbquery('UPDATE test_planets SET `' . GID_B_TERRAFORMER . '` = 2 WHERE planet_id = 1');
        RecalcFields(1);
        $this->assertSame(43, (int) $this->planetField(1, 'fields'));
        $this->assertSame(158, (int) $this->planetField(1, 'maxfields'));
    }

    // ========================================================================
    // DestroyMoon
    // ========================================================================

    /**
     * DestroyMoon deletes the moon, redirects the fleets of the owner to the
     * planet below, subtracts the moon price from the owner's score and
     * selects the planet below as the current planet.
     */
    public function testDestroyMoonRedirectsFleetsAndUpdatesStats() : void
    {
        $when = 1700000000;

        // The owner is currently looking at Colony Beta (planet 9).
        dbquery('UPDATE test_users SET aktplanet = 9 WHERE player_id = 3');
        InvalidateUserCache();

        // A fleet of the moon owner heading to the moon, and one leaving the moon.
        $arriving = AddDBRow(array (
            'owner_id' => 3, 'mission' => FTYP_DEPLOY, 'start_planet' => 7, 'target_planet' => 13,
            'flight_time' => 100, 'deploy_time' => 0, 'fuel' => 10,
        ), 'fleet');
        $leaving = AddDBRow(array (
            'owner_id' => 3, 'mission' => FTYP_DEPLOY, 'start_planet' => 13, 'target_planet' => 7,
            'flight_time' => 100, 'deploy_time' => 0, 'fuel' => 10,
        ), 'fleet');
        $this->assertGreaterThan(0, $arriving);
        $this->assertGreaterThan(0, $leaving);

        $expected = PlanetPrice(LoadPlanetById(13));

        DestroyMoon(13, $when, 0);

        $this->assertNull(LoadPlanetById(13));
        $this->assertNotNull(LoadPlanetById(7));
        $this->assertSame(7, (int) $this->fleetField($arriving, 'target_planet'));
        $this->assertSame(7, (int) $this->fleetField($leaving, 'start_planet'));
        $this->assertSame(1000000 - $expected['points'], $this->scoreOf(3));
        $this->assertSame(7, GetSelectedPlanet(3));
        // Foreign fleets are not affected (PlayerOne's fleet to moon 10).
        $this->assertSame(10, (int) $this->fleetField(5, 'target_planet'));
    }

    /**
     * DestroyMoon bails out when the moon does not exist or when there is no
     * planet below it (the moon survives in that case).
     */
    public function testDestroyMoonReturnsEarlyWithoutAPlanetBelow() : void
    {
        // Unknown moon id.
        DestroyMoon(999999, 1700000000, 0);

        $orphanId = AddDBRow(array (
            'name' => 'Orphan Moon', 'type' => PTYP_MOON, 'g' => 1, 's' => 12, 'p' => 4,
            'owner_id' => 3, 'diameter' => 8000, 'temp' => -30, 'fields' => 1, 'maxfields' => 1,
            'date' => 1700000000, GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
            'lastpeek' => 0, 'lastakt' => 0, 'gate_until' => 0, 'remove' => 0,
        ), 'planets');

        DestroyMoon($orphanId, 1700000000, 0);

        $this->assertNotNull(LoadPlanetById($orphanId));
    }

    // ========================================================================
    // Admin helpers / colony settings / phalanx
    // ========================================================================

    /**
     * AdminPlanetName and AdminPlanetCoord build the admin links and handle
     * a null planet.
     */
    public function testAdminPlanetNameAndCoord() : void
    {
        $this->assertSame('', AdminPlanetName(null));
        $this->assertSame('[::]', AdminPlanetCoord(null));

        $planet = LoadPlanetById(4);
        $this->assertSame(
            '<a href="index.php?page=admin&session=testsession&mode=Planets&cp=4">Home</a>',
            AdminPlanetName($planet)
        );
        $this->assertSame(
            '[<a href="index.php?page=galaxy&session=testsession&galaxy=1&system=3">1:3:4</a>]',
            AdminPlanetCoord($planet)
        );
    }

    /**
     * LoadColonySettings returns the stored settings and SaveColonySettings
     * writes all 15 position tier values.
     */
    public function testColonySettingsRoundTrip() : void
    {
        $settings = array ();
        foreach (array (1, 2, 3, 4, 5) as $tier) {
            $settings["t{$tier}_a"] = 1000 + $tier;
            $settings["t{$tier}_b"] = 2000 + $tier;
            $settings["t{$tier}_c"] = 300 + $tier;
        }

        SaveColonySettings($settings);

        $loaded = LoadColonySettings();
        foreach ($settings as $key => $value) {
            $this->assertSame($value, (int) $loaded[$key], "coltab.$key");
        }
    }

    /**
     * The phalanx radius is level^2 - 1 and CanPhalanx additionally requires a
     * moon, another owner and the same galaxy.
     */
    public function testPhalanxRadiusAndCanPhalanx() : void
    {
        $this->assertSame(-1, GetPhalanxRadius(0));
        $this->assertSame(0, GetPhalanxRadius(1));
        $this->assertSame(3, GetPhalanxRadius(2));
        $this->assertSame(24, GetPhalanxRadius(5));

        $moon = LoadPlanetById(10);        // PlayerOne's moon (1:1:4), phalanx level 2
        $this->assertSame(2, (int) $moon[GID_B_PHALANX]);

        $this->assertFalse(CanPhalanx(null, LoadPlanetById(4)));
        $this->assertFalse(CanPhalanx($moon, null));

        $this->assertTrue(CanPhalanx($moon, LoadPlanetById(4)));       // 1:3:4, |1-3| = 2 <= 3
        $this->assertFalse(CanPhalanx($moon, LoadPlanetById(7)));      // 1:5:4, |1-5| = 4 > 3
        $this->assertFalse(CanPhalanx($moon, LoadPlanetById(1)));      // own planet
        $this->assertFalse(CanPhalanx(LoadPlanetById(1), LoadPlanetById(4)));  // origin is no moon
        $this->assertFalse(CanPhalanx($moon, array (                     // another galaxy
            'type' => PTYP_PLANET, 'g' => 2, 's' => 1, 'p' => 4, 'owner_id' => 2,
        )));
    }

    // ========================================================================
    // raketen.php - RocketAttackMain (pure algorithm)
    // ========================================================================

    /**
     * No missiles means no damage at all; the return value is the number of
     * intercepted missiles (the ABMs consumed), and every interceptor covers
     * exactly one incoming missile.
     */
    public function testRocketAttackMainWithoutMissilesAndWithInterceptors() : void
    {
        $target = $this->defenses(array (GID_D_ABM => 3, GID_D_RL => 100));
        $moon = null;

        $this->assertSame(0, RocketAttackMain(0, 0, false, $target, $moon, 0, 0));
        $this->assertSame(3, (int) $target[GID_D_ABM]);
        $this->assertSame(100, (int) $target[GID_D_RL]);

        // All 3 interceptors are consumed, so 97 missiles get through and wipe
        // out the rocket launchers; the return value counts the interceptions.
        $this->assertSame(3, RocketAttackMain(100, 0, false, $target, $moon, 0, 0));
        $this->assertSame(0, (int) $target[GID_D_ABM]);
        $this->assertSame(0, (int) $target[GID_D_RL]);
    }

    /**
     * The attacker's weapon technology raises the damage by 10% per level, the
     * defender's armor technology raises the hitpoints by 10% per level.
     */
    public function testRocketAttackMainAppliesWeaponAndArmorTechnology() : void
    {
        // Weapon tech 10: 1 IPM deals 12000 * 2 = 24000 damage -> 120 launchers.
        $target = $this->defenses(array (GID_D_RL => 200));
        $moon = null;
        RocketAttackMain(1, 0, false, $target, $moon, 10, 0);
        $this->assertSame(80, (int) $target[GID_D_RL]);

        // Armor tech 10: a launcher has 2000 * 2 / 10 = 400 hitpoints.
        $target = $this->defenses(array (GID_D_RL => 200));
        $moon = null;
        RocketAttackMain(1, 0, false, $target, $moon, 0, 10);
        $this->assertSame(170, (int) $target[GID_D_RL]);
    }

    /**
     * An empty primary target is skipped and the damage is distributed over
     * the remaining defenses.
     */
    public function testRocketAttackMainSkipsAnEmptyPrimaryTarget() : void
    {
        $target = $this->defenses(array (GID_D_RL => 100, GID_D_LL => 0));
        $moon = null;

        RocketAttackMain(1, GID_D_LL, false, $target, $moon, 0, 0);

        $this->assertSame(40, (int) $target[GID_D_RL]);
    }

    /**
     * On a moon attack the interceptors come from the co-located planet, not
     * from the moon, and intercepting them does not damage the moon's own
     * stored missiles as long as structures survive.
     */
    public function testRocketAttackMainMoonAttackUsesThePlanetInterceptors() : void
    {
        $target = $this->defenses(array (GID_D_RL => 100, GID_D_ABM => 5));
        $moon = $this->defenses(array (GID_D_ABM => 2));

        $destroyed = RocketAttackMain(3, 0, true, $target, $moon, 0, 0);

        $this->assertSame(2, $destroyed);
        $this->assertSame(0, (int) $moon[GID_D_ABM]);
        $this->assertSame(40, (int) $target[GID_D_RL]);
        $this->assertSame(5, (int) $target[GID_D_ABM]);
    }

    /**
     * A null moon planet means there is nothing to intercept, and the function
     * normalizes it to an empty defense array.
     */
    public function testRocketAttackMainWithNullMoonPlanet() : void
    {
        $target = $this->defenses(array (GID_D_RL => 100));
        $moon = null;

        $destroyed = RocketAttackMain(1, 0, true, $target, $moon, 0, 0);

        $this->assertSame(0, $destroyed);
        $this->assertSame(array (GID_D_ABM => 0), $moon);
        $this->assertSame(40, (int) $target[GID_D_RL]);
    }

    /**
     * RocketDefenseLossPoints only counts destroyed units (a grown amount is
     * never a negative loss) and ignores unknown keys.
     */
    public function testRocketDefenseLossPointsCountsOnlyDestroyedUnits() : void
    {
        $before = $this->defenses(array (GID_D_RL => 2, GID_D_ABM => 3));
        $after = $this->defenses(array (GID_D_RL => 2, GID_D_ABM => 1));

        $this->assertSame(2 * 10000, RocketDefenseLossPoints($before, $after));
        $this->assertSame(0, RocketDefenseLossPoints($after, $before));
        $this->assertSame(0, RocketDefenseLossPoints(array ('bogus' => 5), $this->defenses()));
    }

    /**
     * GetDestroyedDefenseText lists the remaining defenses; on a moon attack
     * the interceptor count is taken from the planet below.
     */
    public function testGetDestroyedDefenseTextUsesThePlanetInterceptors() : void
    {
        $target = $this->defenses(array (GID_D_RL => 4, GID_D_ABM => 5));
        $moon = null;
        $text = GetDestroyedDefenseText('en', $target, $moon, false);

        $this->assertStringStartsWith('<table', $text);
        $this->assertStringEndsWith("</table><br>\n", $text);
        $this->assertStringContainsString(
            '<td>' . loca_lang('NAME_' . GID_D_RL, 'en') . '</td><td>4</td>',
            $text
        );
        $this->assertStringContainsString(
            '<td>' . loca_lang('NAME_' . GID_D_ABM, 'en') . '</td><td>5</td>',
            $text
        );

        $moon = $this->defenses(array (GID_D_ABM => 2));
        $text = GetDestroyedDefenseText('en', $target, $moon, true);
        $this->assertStringContainsString(
            '<td>' . loca_lang('NAME_' . GID_D_ABM, 'en') . '</td><td>2</td>',
            $text
        );
        $this->assertStringNotContainsString(
            '<td>' . loca_lang('NAME_' . GID_D_ABM, 'en') . '</td><td>5</td>',
            $text
        );
    }

    // ========================================================================
    // raketen.php - RocketAttack (full attack)
    // ========================================================================

    /**
     * A full missile attack writes the losses back to the planet, updates the
     * planet activity, both players' scores and sends a message to each player.
     */
    public function testRocketAttackResolvesDamageStatsAndMessages() : void
    {
        $when = 1700000000;

        $origin = LoadPlanetById(1);
        $origin[GID_D_IPM] = 2;
        SetPlanetDefense(1, $origin);

        $fleet_id = LaunchRockets($origin, LoadPlanetById(4), 100, 2, 0);
        $this->assertGreaterThan(0, $fleet_id);

        RocketAttack($fleet_id, 4, $when);

        // 2 IPMs at weapon tech 3: 2 * 12000 * 1.3 = 31200 damage, enough to
        // destroy all 3 rocket launchers and 2 light lasers of the target.
        $target = LoadPlanetById(4);
        $this->assertSame(0, (int) $target[GID_D_RL]);
        $this->assertSame(0, (int) $target[GID_D_LL]);
        $this->assertSame(0, (int) $target[GID_D_ABM]);

        // The launched missiles were written off the origin planet.
        $this->assertSame(0, (int) $this->planetField(1, (string) GID_D_IPM));

        // The planet activity is updated with the resolution time.
        $this->assertSame($when, (int) $this->planetField(4, 'lastakt'));

        // One message for the defender and one for the attacker.
        $this->assertSame(1, $this->messageCount(2, $when, MTYP_BATTLE_REPORT_LINK));
        $this->assertSame(1, $this->messageCount(1, $when, MTYP_BATTLE_REPORT_LINK));

        // Attacker: 2 missiles * 25000 points; defender: 3 RL + 2 LL * 2000.
        $this->assertSame(1000000 - 50000, $this->scoreOf(1));
        $this->assertSame(1000000 - 10000, $this->scoreOf(2));
    }

    /**
     * A missile attack on a moon takes the interceptors from the planet below
     * and writes the losses of both objects back to the database.
     */
    public function testRocketAttackOnAMoonUsesThePlanetInterceptors() : void
    {
        $when = 1700000000;

        // PlayerTwo's moon 12 (1:3:4) carries 5 rocket launchers; the planet
        // below (4) carries 2 interceptors and keeps its own 3 RL + 2 LL.
        SetPlanetDefense(12, $this->defenses(array (GID_D_RL => 5)));
        $planet = LoadPlanetById(4);
        $planet[GID_D_ABM] = 2;
        SetPlanetDefense(4, $planet);

        $origin = LoadPlanetById(1);
        $origin[GID_D_IPM] = 3;
        SetPlanetDefense(1, $origin);

        $fleet_id = LaunchRockets($origin, LoadPlanetById(12), 100, 3, 0);
        $this->assertGreaterThan(0, $fleet_id);

        RocketAttack($fleet_id, 12, $when);

        // 2 of the 3 IPMs are intercepted by the planet, the surviving one
        // (12000 * 1.3 = 15600 damage at armor tech 3) destroys the 5 launchers.
        $moon = LoadPlanetById(12);
        $this->assertSame(0, (int) $moon[GID_D_RL]);
        $planet = LoadPlanetById(4);
        $this->assertSame(0, (int) $planet[GID_D_ABM]);
        $this->assertSame(3, (int) $planet[GID_D_RL]);
        $this->assertSame(2, (int) $planet[GID_D_LL]);
        $this->assertSame($when, (int) $this->planetField(12, 'lastakt'));

        $this->assertSame(1, $this->messageCount(2, $when, MTYP_BATTLE_REPORT_LINK));
        $this->assertSame(1, $this->messageCount(1, $when, MTYP_BATTLE_REPORT_LINK));

        // Attacker: 3 missiles * 25000 points. Defender: 2 ABM * 10000
        // (interception) + 5 RL * 2000 (the moon's losses) = 30000.
        $this->assertSame(1000000 - 75000, $this->scoreOf(1));
        $this->assertSame(1000000 - 30000, $this->scoreOf(2));
    }

    /**
     * The attacker message can be switched off at runtime (issue #61: the
     * original 0.84 game had no attacker message).
     */
    public function testRocketAttackCanSuppressTheAttackerMessage() : void
    {
        $when = 1700000000;
        $GLOBALS['message_for_attacker'] = false;

        $origin = LoadPlanetById(1);
        $origin[GID_D_IPM] = 1;
        SetPlanetDefense(1, $origin);

        $fleet_id = LaunchRockets($origin, LoadPlanetById(4), 100, 1, 0);
        $this->assertGreaterThan(0, $fleet_id);

        RocketAttack($fleet_id, 4, $when);

        $this->assertSame(1, $this->messageCount(2, $when, MTYP_BATTLE_REPORT_LINK));
        $this->assertSame(0, $this->messageCount(1, $when, MTYP_BATTLE_REPORT_LINK));
    }

    // ========================================================================
    // graviton.php
    // ========================================================================

    /**
     * GravitonAttack returns 0 for unknown planets, for a fleet without a
     * Deathstar and when the involved players cannot be loaded.
     */
    public function testGravitonAttackReturnsZeroForUnreachableTargets() : void
    {
        $when = 1700000000;
        $fleet = $this->ships(array (GID_F_DEATHSTAR => 1));

        $this->assertSame(0, GravitonAttack(array (
            'owner_id' => 1, 'fleet_id' => 0, 'start_planet' => 999999, 'target_planet' => 12,
        ), $fleet, $when));

        $this->assertSame(0, GravitonAttack(array (
            'owner_id' => 1, 'fleet_id' => 0, 'start_planet' => 1, 'target_planet' => 999999,
        ), $fleet, $when));

        $this->assertSame(0, GravitonAttack(array (
            'owner_id' => 1, 'fleet_id' => 0, 'start_planet' => 1, 'target_planet' => 12,
        ), $this->ships(), $when));

        // Planets whose owner does not exist.
        $planetId = AddDBRow(array (
            'name' => 'Ghost', 'type' => PTYP_PLANET, 'g' => 1, 's' => 14, 'p' => 4,
            'owner_id' => 999999, 'diameter' => 12000, 'temp' => 50, 'fields' => 0, 'maxfields' => 144,
            'date' => $when, GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
        ), 'planets');
        $ghostMoonId = AddDBRow(array (
            'name' => 'Ghost Moon', 'type' => PTYP_MOON, 'g' => 1, 's' => 14, 'p' => 4,
            'owner_id' => 999999, 'diameter' => 8000, 'temp' => -30, 'fields' => 1, 'maxfields' => 1,
            'date' => $when, GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
        ), 'planets');

        $this->assertSame(0, GravitonAttack(array (
            'owner_id' => 999999, 'fleet_id' => 0, 'start_planet' => $planetId, 'target_planet' => $ghostMoonId,
        ), $fleet, $when));
    }

    /**
     * Moon destroyed, fleet survives (GRAVI_MOON_DESTR): the moon is deleted,
     * the planet below becomes the current planet, the owner loses the moon
     * price and both players get a message.
     */
    public function testGravitonAttackDestroysTheMoonOnly() : void
    {
        $when = 1700000000;

        // Diameter 1 -> destruction chance 99 (990/999) and explosion chance 0.5.
        dbquery('UPDATE test_planets SET diameter = 1, `' . GID_F_LF . '` = 4 WHERE planet_id = 13');
        dbquery('UPDATE test_users SET aktplanet = 9 WHERE player_id = 3');
        InvalidateUserCache();

        $expected = PlanetPrice(LoadPlanetById(13));

        mt_srand(1);    // mt_rand(1,999) -> 734 (moon destroyed), 303 (fleet survives)

        $result = GravitonAttack(array (
            'owner_id' => 1, 'fleet_id' => 0, 'start_planet' => 1, 'target_planet' => 13,
        ), $this->ships(array (GID_F_DEATHSTAR => 1)), $when);

        $this->assertSame(GRAVI_MOON_DESTR, $result);
        $this->assertSame(1, $result);
        $this->assertNull(LoadPlanetById(13));
        $this->assertNotNull(LoadPlanetById(7));
        $this->assertSame(1000000 - $expected['points'], $this->scoreOf(3));
        $this->assertSame(100000 - $expected['fpoints'], $this->fleetScoreOf(3));
        $this->assertSame(1000000, $this->scoreOf(1));       // the Deathstar fleet survived
        $this->assertSame(7, GetSelectedPlanet(3));          // the planet below is selected

        $this->assertSame(1, $this->messageCount(1, $when, MTYP_MISC));
        $this->assertSame(1, $this->messageCount(3, $when, MTYP_MISC));
    }

    /**
     * Moon and fleet destroyed (GRAVI_MOON_DESTR | GRAVI_FLEET_DESTR): the
     * attacker additionally loses the price of the whole fleet.
     */
    public function testGravitonAttackDestroysTheMoonAndTheFleet() : void
    {
        $when = 1700000000;

        dbquery('UPDATE test_planets SET diameter = 1 WHERE planet_id = 13');
        InvalidateUserCache();

        $expectedMoon = PlanetPrice(LoadPlanetById(13));

        mt_srand(65);   // mt_rand(1,999) -> 340 (moon destroyed), 2 (fleet explodes)

        $result = GravitonAttack(array (
            'owner_id' => 1, 'fleet_id' => 0, 'start_planet' => 1, 'target_planet' => 13,
        ), $this->ships(array (GID_F_DEATHSTAR => 1)), $when);

        $this->assertSame(GRAVI_MOON_DESTR | GRAVI_FLEET_DESTR, $result);
        $this->assertSame(3, $result);
        $this->assertNull(LoadPlanetById(13));
        $this->assertSame(1000000 - $expectedMoon['points'], $this->scoreOf(3));
        // A Death Star costs 5M metal + 4M crystal + 1M deuterium = 10M points.
        $this->assertSame(1000000 - 10000000, $this->scoreOf(1));
        $this->assertSame(100000 - 1, $this->fleetScoreOf(1));
        $this->assertSame(1, $this->messageCount(1, $when, MTYP_MISC));
        $this->assertSame(1, $this->messageCount(3, $when, MTYP_MISC));
    }

    /**
     * Nothing happens (result 0): the moon survives and no score changes.
     */
    public function testGravitonAttackWithoutAnyEffect() : void
    {
        $when = 1700000000;

        // Diameter 10000 -> moon chance 0, explosion chance 50 (500/999).
        dbquery('UPDATE test_planets SET diameter = 10000 WHERE planet_id = 12');
        InvalidateUserCache();

        mt_srand(4);    // mt_rand(1,999) -> 50 (no moon damage), 706 (no explosion)

        $result = GravitonAttack(array (
            'owner_id' => 1, 'fleet_id' => 0, 'start_planet' => 1, 'target_planet' => 12,
        ), $this->ships(array (GID_F_DEATHSTAR => 1)), $when);

        $this->assertSame(0, $result);
        $this->assertNotNull(LoadPlanetById(12));
        $this->assertSame(1000000, $this->scoreOf(1));
        $this->assertSame(1000000, $this->scoreOf(2));
        $this->assertSame(1, $this->messageCount(1, $when, MTYP_MISC));
        $this->assertSame(1, $this->messageCount(2, $when, MTYP_MISC));
    }

    /**
     * Only the fleet explodes (GRAVI_FLEET_DESTR): the moon survives but the
     * attacker loses the Deathstar fleet.
     */
    public function testGravitonAttackOnlyTheFleetExplodes() : void
    {
        $when = 1700000000;

        dbquery('UPDATE test_planets SET diameter = 10000 WHERE planet_id = 12');
        InvalidateUserCache();

        mt_srand(1);    // mt_rand(1,999) -> 734 (no moon damage), 303 (fleet explodes)

        $result = GravitonAttack(array (
            'owner_id' => 1, 'fleet_id' => 0, 'start_planet' => 1, 'target_planet' => 12,
        ), $this->ships(array (GID_F_DEATHSTAR => 1)), $when);

        $this->assertSame(GRAVI_FLEET_DESTR, $result);
        $this->assertSame(2, $result);
        $this->assertNotNull(LoadPlanetById(12));
        $this->assertSame(1000000 - 10000000, $this->scoreOf(1));
        $this->assertSame(100000 - 1, $this->fleetScoreOf(1));
        $this->assertSame(1000000, $this->scoreOf(2));
    }
}
