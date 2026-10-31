<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the fleet core module (game/core/fleet.php).
 *
 * The module mixes pure flight math (FlightDistance, FlightTime, FlightSpeed,
 * FleetSpeed, FleetCargo*, FleetCons, FlightCons, mission names, fleet
 * listings) with database driven fleet handling (dispatch, recall, the arrival
 * handlers and the global fleet queue). Every test runs against the real game
 * schema in the in-memory SQLite database created by FixtureBuilder, so the DB
 * side effects (planets, fleet, fleetlogs, queue, messages) can be asserted
 * directly.
 *
 * The fixture universe (FixtureBuilder::createTestUniverse('en')):
 *  - PlayerOne   (1): planets 1 (1:1:4), 2 (1:1:5), 3 (1:2:4), moons 10, 11;
 *  - PlayerTwo   (2): planets 4 (1:3:4), 5 (1:3:5), 6 (1:4:4), moon 12;
 *  - PlayerThree (3): planets 7 (1:5:4), 8 (1:5:5), 9 (1:6:4), moon 13;
 *  - 14 = debris field (1:2:5, 500000 metal / 300000 crystal),
 *    15 = colonization phantom (1:1:6), 16 = deep space (1:1:16).
 * All three players belong to the same alliance, PlayerOne/PlayerTwo are
 * accepted buddies, and every officer is active for a year.
 *
 * Everything that needs the wall clock is either pinned through lastpeek /
 * explicit $when parameters or asserted as a delta.
 *
 * NOT covered on purpose:
 *  - RecycleArrive's input guards ("no recyclers" / "not a debris field") call
 *    Error(), which terminates the process;
 *  - AttackArrive / DestroyArrive / RocketAttackArrive are one-line
 *    delegations into battle.php / raketen.php that have their own test
 *    classes; ExpeditionArrive lives in expedition.php.
 */
#[RunTestsInSeparateProcesses]
class FleetCoreTest extends TestCase
{
    /** FleetColumns: PlayerOne's home planet (1:1:4). */
    private const HOME = 1;

    /** PlayerTwo's home planet (1:3:4). */
    private const ENEMY_HOME = 4;

    /** PlayerOne's second planet, "Colony A" (1:1:5). */
    private const COLONY = 2;

    /** The fixture debris field (1:2:5). */
    private const DEBRIS = 14;

    /** The fixture colonization phantom (1:1:6). */
    private const PHANTOM = 15;

    private FixtureBuilder $fixture;

    protected function setUp(): void
    {
        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');

        // loca_add() resolves the localization files relative to the game dir.
        chdir(__DIR__ . '/../game');

        global $GlobalUni, $GlobalUser, $db_prefix;
        $GlobalUni = $this->fixture->getUniData();
        $GlobalUser = LoadUser(1);
        $db_prefix = $this->fixture->getDbPrefix();

        loca_add('technames', 'en');
        loca_add('fleetorder', 'en');
        loca_add('fleetmsg', 'en');

        // Debug() (called by ColonizationArrive) reads the request environment;
        // provide it so the debug row can be written without PHP warnings in
        // the CLI test run.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        $_SERVER['REQUEST_URI'] = '/index.php?page=fleet';
    }

    // ========================================================================
    // Helpers.

    /**
     * A fleet composition with every ship type present (0 unless overridden),
     * so no fleet function ever hits an undefined array key.
     */
    private function fullFleet(array $overrides = []): array
    {
        global $fleetmap;
        $fleet = [];
        foreach ($fleetmap as $gid) {
            $fleet[$gid] = 0;
        }
        foreach ($overrides as $gid => $amount) {
            $fleet[$gid] = $amount;
        }
        return $fleet;
    }

    /**
     * Insert a complete fleet row and return its id.
     */
    private function addFleetRow(array $overrides): int
    {
        // NOTE: no array_merge() here - it renumbers the integer ship-type
        // keys (GID_F_*) and would produce a column named "0".
        $row = [
            'owner_id' => 1,
            'union_id' => 0,
            GID_RC_METAL => 0,
            GID_RC_CRYSTAL => 0,
            GID_RC_DEUTERIUM => 0,
            'fuel' => 0,
            'mission' => FTYP_SPY,
            'start_planet' => self::HOME,
            'target_planet' => self::ENEMY_HOME,
            'flight_time' => 600,
            'deploy_time' => 0,
            'ipm_amount' => 0,
            'ipm_target' => 0,
        ];
        foreach ($this->fullFleet() as $gid => $amount) {
            $row[$gid] = $amount;
        }
        foreach ($overrides as $column => $value) {
            $row[$column] = $value;
        }

        return AddDBRow($row, 'fleet');
    }

    /**
     * Add the global-queue task that belongs to a fleet row.
     */
    private function addFleetTask(int $fleetId, int $mission, int $start, int $seconds, int $ownerId = 1): int
    {
        return AddQueue($ownerId, QTYP_FLEET, $fleetId, 0, 0, $start, $seconds, QUEUE_PRIO_FLEET + $mission);
    }

    /**
     * The queue event array Queue_Fleet_End() receives from the dispatcher.
     */
    private function queueEndArgs(int $fleetId, int $taskId, int $end): array
    {
        return [
            'task_id' => $taskId,
            'owner_id' => 1,
            'type' => QTYP_FLEET,
            'sub_id' => $fleetId,
            'obj_id' => 0,
            'level' => 0,
            'start' => $end - 600,
            'end' => $end,
            'prio' => QUEUE_PRIO_FLEET,
        ];
    }

    /**
     * Read one integer column of a planet row (-1 when the planet is gone).
     */
    private function planetInt(int $planetId, string|int $column): int
    {
        global $db_prefix;
        $row = dbarray(dbquery("SELECT `$column` AS value FROM {$db_prefix}planets WHERE planet_id = $planetId"));
        return $row === false ? -1 : (int)$row['value'];
    }

    /**
     * Count rows of a table matching a raw WHERE clause.
     */
    private function countRows(string $table, string $where = '1'): int
    {
        global $db_prefix;
        $row = dbarray(dbquery("SELECT COUNT(*) AS cnt FROM {$db_prefix}{$table} WHERE $where"));
        return $row === false ? -1 : (int)$row['cnt'];
    }

    /**
     * Count the messages of a player, optionally restricted to one pm type.
     */
    private function countMessages(int $ownerId, ?int $pm = null): int
    {
        $where = "owner_id = $ownerId";
        if ($pm !== null) {
            $where .= " AND pm = $pm";
        }
        return $this->countRows('messages', $where);
    }

    /**
     * The text of the newest message of a player.
     */
    private function lastMessageText(int $ownerId): string
    {
        global $db_prefix;
        $row = dbarray(dbquery("SELECT text FROM {$db_prefix}messages WHERE owner_id = $ownerId ORDER BY msg_id DESC LIMIT 1"));
        return $row === false ? '' : (string)$row['text'];
    }

    // ========================================================================
    // FlightDistance

    public function testFlightDistanceToTheSameCoordinatesIsFive(): void
    {
        $this->assertSame(5, FlightDistance(1, 1, 4, 1, 1, 4));
    }

    public function testFlightDistanceWithinOneSystem(): void
    {
        // 1000 + 5 per position step, in both directions.
        $this->assertSame(1005, FlightDistance(1, 1, 4, 1, 1, 5));
        $this->assertSame(1025, FlightDistance(1, 1, 4, 1, 1, 9));
        $this->assertSame(1025, FlightDistance(1, 1, 9, 1, 1, 4));
    }

    public function testFlightDistanceBetweenSystems(): void
    {
        // 2700 + 5 * 19 = 95 per system step.
        $this->assertSame(2795, FlightDistance(1, 1, 4, 1, 2, 4));
        $this->assertSame(3080, FlightDistance(1, 1, 4, 1, 5, 4));
    }

    public function testFlightDistanceBetweenGalaxies(): void
    {
        // The system and position are ignored between galaxies.
        $this->assertSame(20000, FlightDistance(1, 1, 4, 2, 1, 4));
        $this->assertSame(40000, FlightDistance(1, 1, 4, 3, 9, 12));
    }

    // ========================================================================
    // FleetSpeed / FleetCons / FleetCargo

    public function testFleetSpeedAppliesTheDriveResearchOfEachShipClass(): void
    {
        $user = LoadUser(1);            // combustion 4, impulse 3, hyperspace 2
        $planet = LoadPlanetById(self::HOME);

        $this->assertSame(7000.0, FleetSpeed(GID_F_SC, $user, $planet));          // 5000 * (1 + 0.1 * 4)
        $this->assertSame(17500.0, FleetSpeed(GID_F_LF, $user, $planet));         // 12500 * 1.4
        $this->assertSame(10500.0, FleetSpeed(GID_F_LC, $user, $planet));         // 7500 * 1.4
        $this->assertSame(16000.0, FleetSpeed(GID_F_HF, $user, $planet));         // impulse: 10000 * 1.6
        $this->assertSame(24000.0, FleetSpeed(GID_F_CRUISER, $user, $planet));    // impulse: 15000 * 1.6
        $this->assertSame(4000.0, FleetSpeed(GID_F_COLON, $user, $planet));       // impulse: 2500 * 1.6
        $this->assertSame(2800.0, FleetSpeed(GID_F_RECYCLER, $user, $planet));    // combustion: 2000 * 1.4
        $this->assertSame(140000000.0, FleetSpeed(GID_F_PROBE, $user, $planet));  // combustion
        $this->assertSame(6400.0, FleetSpeed(GID_F_BOMBER, $user, $planet));      // impulse below level 8
        $this->assertSame(16000.0, FleetSpeed(GID_F_BATTLESHIP, $user, $planet)); // hyperspace: 10000 * 1.6
        $this->assertSame(8000.0, FleetSpeed(GID_F_DESTRO, $user, $planet));      // hyperspace: 5000 * 1.6
        $this->assertSame(160.0, FleetSpeed(GID_F_DEATHSTAR, $user, $planet));    // hyperspace: 100 * 1.6
        $this->assertSame(16000.0, FleetSpeed(GID_F_BATTLECRUISER, $user, $planet));
    }

    public function testFleetSpeedSwitchesEnginesAtTheResearchThresholds(): void
    {
        $user = LoadUser(1);
        $planet = LoadPlanetById(self::HOME);

        // Small Cargo: impulse drive 5 replaces the combustion engine.
        $user[GID_R_IMPULSE_DRIVE] = 5;
        $this->assertSame(20000.0, FleetSpeed(GID_F_SC, $user, $planet));         // (5000 + 5000) * (1 + 0.2 * 5)

        // Bomber: hyperspace drive 8 replaces the impulse engine.
        $user[GID_R_HYPER_DRIVE] = 8;
        $this->assertSame(17000.0, FleetSpeed(GID_F_BOMBER, $user, $planet));     // (4000 + 1000) * (1 + 0.3 * 8)

        // Solar satellites never move.
        $this->assertSame(0.0, FleetSpeed(GID_F_SAT, $user, $planet));
    }

    public function testFlightSpeedIsTheSpeedOfTheSlowestShip(): void
    {
        $user = LoadUser(1);
        $planet = LoadPlanetById(self::HOME);

        $this->assertSame(7000, FlightSpeed($this->fullFleet([GID_F_LF => 10, GID_F_SC => 5]), $user, $planet));
        // Zero-count entries are skipped entirely.
        $this->assertSame(17500, FlightSpeed($this->fullFleet([GID_F_LF => 10, GID_F_RECYCLER => 0]), $user, $planet));
        // Solar satellites have speed 0 and are skipped as well, so the initial
        // (probe) speed is what remains for a satellite-only fleet.
        $this->assertSame(140000000, FlightSpeed($this->fullFleet([GID_F_SAT => 5]), $user, $planet));
    }

    public function testFleetConsDoublesTheSmallCargoWithImpulseDrive(): void
    {
        $user = LoadUser(1);
        $planet = LoadPlanetById(self::HOME);

        $this->assertSame(10, FleetCons(GID_F_SC, $user, $planet));
        $this->assertSame(20, FleetCons(GID_F_LF, $user, $planet));
        $this->assertSame(1, FleetCons(GID_F_DEATHSTAR, $user, $planet));
        $this->assertSame(0, FleetCons(GID_F_SAT, $user, $planet));

        $user[GID_R_IMPULSE_DRIVE] = 5;
        $this->assertSame(20, FleetCons(GID_F_SC, $user, $planet));       // 10 * 2
        $this->assertSame(20, FleetCons(GID_F_LF, $user, $planet));       // unaffected
    }

    public function testFleetCargoValues(): void
    {
        $this->assertSame(5000, FleetCargo(GID_F_SC));
        $this->assertSame(25000, FleetCargo(GID_F_LC));
        $this->assertSame(50, FleetCargo(GID_F_LF));
        $this->assertSame(20000, FleetCargo(GID_F_RECYCLER));
        $this->assertSame(1000000, FleetCargo(GID_F_DEATHSTAR));
        $this->assertSame(0, FleetCargo(GID_F_SAT));
    }

    public function testFleetCargoSummaryIgnoresProbes(): void
    {
        $this->assertSame(35000, FleetCargoSummary($this->fullFleet([GID_F_SC => 2, GID_F_LC => 1, GID_F_PROBE => 5])));
        $this->assertSame(0, FleetCargoSummary($this->fullFleet([GID_F_PROBE => 5])));
        $this->assertSame(0, FleetCargoSummary($this->fullFleet()));
    }

    // ========================================================================
    // FlightTime / FlightCons

    public function testFlightTimeFormulaAndUniverseSpeedDivisor(): void
    {
        // The callers pass the selected speed as a fraction (0.1 .. 1.0):
        // (35000 / (1.0 * 10) * sqrt(1000 * 10 / 10000) + 10) / 1 == 3510.
        $this->assertSame(3510, FlightTime(1000, 10000, 1.0, 1));
        // Half speed doubles the time: 35000 / 5 + 10 == 7010.
        $this->assertSame(7010, FlightTime(1000, 10000, 0.5, 1));
        // A universe twice as fast halves the time: round((3500 * sqrt(5) + 10) / 2).
        $this->assertSame(3918, FlightTime(3500, 7000, 1.0, 2));
    }

    public function testFlightConsSplitsFleetAndProbesAndAddsHoldingCosts(): void
    {
        $user = LoadUser(1);
        $planet = LoadPlanetById(self::HOME);

        // Ten small cargo over 1000 units with a 360 s flight time in a x10
        // fleet-speed universe burn 13 deuterium.
        $this->assertSame(
            ['fleet' => 13, 'probes' => 0],
            FlightCons($this->fullFleet([GID_F_SC => 10]), 1000, 360, $user, $planet, 10, 0)
        );

        // Three hours of holding add hours * amount * consumption / 10 = 30.
        $this->assertSame(
            ['fleet' => 43, 'probes' => 0],
            FlightCons($this->fullFleet([GID_F_SC => 10]), 1000, 360, $user, $planet, 10, 3)
        );

        // Spy probe fuel is accounted separately from the rest of the fleet.
        $probes = FlightCons($this->fullFleet([GID_F_PROBE => 100]), 20000, 3600, $user, $planet, 10, 0);
        $this->assertSame(0, $probes['fleet']);
        $this->assertSame(57, $probes['probes']);

        // A fleet with zero ships costs nothing.
        $this->assertSame(['fleet' => 0, 'probes' => 0], FlightCons($this->fullFleet(), 20000, 3600, $user, $planet, 10, 0));
    }

    // ========================================================================
    // GetMaxFleet

    public function testGetMaxFleetAddsTheAdmiralBonus(): void
    {
        $user = LoadUser(1);        // computer technology 6, admiral active
        $planet = LoadPlanetById(self::HOME);

        $maxfleet = $maxfleetNoBonus = -1;
        GetMaxFleet($user, $planet, $maxfleet, $maxfleetNoBonus);
        $this->assertSame(7, $maxfleetNoBonus);       // computer tech + 1
        $this->assertSame(9, $maxfleet);              // + 2 for the admiral

        $user['adm_until'] = 0;
        GetMaxFleet($user, $planet, $maxfleet, $maxfleetNoBonus);
        $this->assertSame(7, $maxfleetNoBonus);
        $this->assertSame(7, $maxfleet);
    }

    public function testGetMaxFleetWithoutAUserIsZero(): void
    {
        $maxfleet = $maxfleetNoBonus = -1;
        GetMaxFleet(null, null, $maxfleet, $maxfleetNoBonus);
        $this->assertSame(0, $maxfleet);
        $this->assertSame(0, $maxfleetNoBonus);
    }

    // ========================================================================
    // Mission names, fleet lists

    public function testGetMissionNameDebugCoversOutboundReturnOrbitAndUnknown(): void
    {
        $this->assertSame('Атака убывает', GetMissionNameDebug(FTYP_ATTACK));
        $this->assertSame('Атака возвращается', GetMissionNameDebug(FTYP_ATTACK + FTYP_RETURN));
        $this->assertSame('Совместная атака возвращается', GetMissionNameDebug(FTYP_ACS_ATTACK + FTYP_RETURN));
        $this->assertSame('Транспорт убывает', GetMissionNameDebug(FTYP_TRANSPORT));
        $this->assertSame('Экспедиция на орбите', GetMissionNameDebug(FTYP_EXPEDITION + FTYP_ORBITING));
        $this->assertSame('Ракетная атака', GetMissionNameDebug(FTYP_MISSILE));
        $this->assertSame('Неизвестно', GetMissionNameDebug(77));
        $this->assertSame('Неизвестно', GetMissionNameDebug(FTYP_CUSTOM + 5));
    }

    public function testFleetListPrintsLocalizedShipNames(): void
    {
        // The order follows $fleetmap (Small Cargo before Light Fighter).
        $this->assertSame('Small Cargo: 5 Light Fighter: 3 ', FleetList($this->fullFleet([GID_F_SC => 5, GID_F_LF => 3]), 'en'));
        $this->assertSame('', FleetList($this->fullFleet(), 'en'));
    }

    public function testDumpFleetFormatsOnlyNonZeroShips(): void
    {
        $this->assertSame('Light Fighter 3 ', DumpFleet($this->fullFleet([GID_F_LF => 3])));
        $this->assertSame('', DumpFleet($this->fullFleet()));
    }

    // ========================================================================
    // LoadFleet / SetFleet / DeleteFleet / AdjustShips

    public function testLoadFleetReturnsTheStoredRow(): void
    {
        // Fleet 1 of the fixture: PlayerOne's espionage flight to PlayerTwo.
        $fleet = LoadFleet(1);

        $this->assertIsArray($fleet);
        $this->assertSame(1, (int)$fleet['owner_id']);
        $this->assertSame(FTYP_SPY, (int)$fleet['mission']);
        $this->assertSame(self::HOME, (int)$fleet['start_planet']);
        $this->assertSame(self::ENEMY_HOME, (int)$fleet['target_planet']);
        $this->assertSame(600, (int)$fleet['flight_time']);
        $this->assertSame(5, (int)$fleet[GID_F_SC]);
    }

    public function testLoadFleetOfAMissingFleetIsFalse(): void
    {
        // The docblock promises null, but LoadFleet returns the raw dbarray()
        // result, which is false for an empty result set. The callers rely on
        // the loose "== null" comparison.
        $this->assertFalse(LoadFleet(999999));
    }

    public function testSetFleetReplacesTheWholeShipComposition(): void
    {
        SetFleet(1, $this->fullFleet([GID_F_LF => 7, GID_F_PROBE => 2]));

        $fleet = LoadFleet(1);
        $this->assertSame(0, (int)$fleet[GID_F_SC]);       // was 5 before
        $this->assertSame(7, (int)$fleet[GID_F_LF]);
        $this->assertSame(2, (int)$fleet[GID_F_PROBE]);
    }

    public function testDeleteFleetRemovesTheRow(): void
    {
        $this->assertIsArray(LoadFleet(1));

        DeleteFleet(1);

        $this->assertFalse(LoadFleet(1));
    }

    public function testAdjustShipsAddsAndSubtractsShipCounts(): void
    {
        $before = LoadPlanetById(self::HOME);
        $this->assertSame(20, (int)$before[GID_F_LF]);

        AdjustShips($this->fullFleet([GID_F_LF => 10]), self::HOME, '+');
        $this->assertSame(30, $this->planetInt(self::HOME, GID_F_LF));
        // Other ship types are left untouched.
        $this->assertSame((int)$before[GID_F_SC], $this->planetInt(self::HOME, GID_F_SC));

        AdjustShips($this->fullFleet([GID_F_LF => 10]), self::HOME, '-');
        $this->assertSame(20, $this->planetInt(self::HOME, GID_F_LF));
    }

    // ========================================================================
    // Holding fleets

    public function testGetHoldingFleetsCountCountsFlyingAndOrbitingFleets(): void
    {
        $this->addFleetRow(['mission' => FTYP_ACS_HOLD, 'owner_id' => 1, 'target_planet' => self::ENEMY_HOME]);
        $this->addFleetRow(['mission' => FTYP_ACS_HOLD + FTYP_ORBITING, 'owner_id' => 1, 'target_planet' => self::ENEMY_HOME]);
        $this->addFleetRow(['mission' => FTYP_ACS_HOLD, 'owner_id' => 2, 'target_planet' => self::COLONY]);

        $this->assertSame(2, GetHoldingFleetsCount(self::ENEMY_HOME));
        $this->assertSame(1, GetHoldingFleetsCount(self::COLONY));
        $this->assertSame(0, GetHoldingFleetsCount(6));
    }

    public function testCanStandHoldLimitsTheNumberOfHoldingPlayers(): void
    {
        // An empty planet accepts any positive limit.
        $this->assertTrue(CanStandHold(self::ENEMY_HOME, 1, 1));

        $this->addFleetRow(['mission' => FTYP_ACS_HOLD, 'owner_id' => 1, 'target_planet' => self::ENEMY_HOME]);
        $this->addFleetRow(['mission' => FTYP_ACS_HOLD + FTYP_ORBITING, 'owner_id' => 1, 'target_planet' => self::ENEMY_HOME]);
        $this->addFleetRow(['mission' => FTYP_ACS_HOLD, 'owner_id' => 2, 'target_planet' => self::ENEMY_HOME]);

        // Two distinct players hold fleets there (owner 1 holds two rows).
        $this->assertFalse(CanStandHold(self::ENEMY_HOME, 3, 2));
        $this->assertTrue(CanStandHold(self::ENEMY_HOME, 3, 3));
        $this->assertFalse(CanStandHold(self::ENEMY_HOME, 1, 1));
        // A limit of 0 blocks even an empty planet (0 < 0 is false).
        $this->assertFalse(CanStandHold(self::COLONY, 1, 0));
    }

    // ========================================================================
    // DispatchFleet

    public function testDispatchFleetStoresFleetLogAndQueueTask(): void
    {
        $origin = LoadPlanetById(self::HOME);
        $target = LoadPlanetById(self::ENEMY_HOME);
        $fleet = $this->fullFleet([GID_F_LF => 5, GID_F_LC => 2]);
        $resources = [GID_RC_METAL => 5000, GID_RC_CRYSTAL => 2000, GID_RC_DEUTERIUM => 0];
        $when = 1700000000;

        $fleetId = DispatchFleet($fleet, $origin, $target, FTYP_TRANSPORT, 900, $resources, 150, $when);
        $this->assertGreaterThan(0, $fleetId);

        $row = LoadFleet($fleetId);
        $this->assertIsArray($row);
        $this->assertSame(1, (int)$row['owner_id']);
        $this->assertSame(FTYP_TRANSPORT, (int)$row['mission']);
        $this->assertSame(self::HOME, (int)$row['start_planet']);
        $this->assertSame(self::ENEMY_HOME, (int)$row['target_planet']);
        $this->assertSame(900, (int)$row['flight_time']);
        $this->assertSame(0, (int)$row['deploy_time']);
        $this->assertSame(150, (int)$row['fuel']);
        $this->assertSame(5, (int)$row[GID_F_LF]);
        $this->assertSame(2, (int)$row[GID_F_LC]);
        $this->assertSame(5000, (int)$row[GID_RC_METAL]);
        $this->assertSame(2000, (int)$row[GID_RC_CRYSTAL]);

        // The global queue receives the matching fleet task.
        $task = GetFleetQueue($fleetId);
        $this->assertIsArray($task);
        $this->assertSame(QUEUE_PRIO_FLEET + FTYP_TRANSPORT, (int)$task['prio']);
        $this->assertSame($when, (int)$task['start']);
        $this->assertSame($when + 900, (int)$task['end']);

        // The flight log records both coordinates, the cargo and the origin
        // resource levels before departure.
        $log = dbarray(dbquery("SELECT * FROM test_fleetlogs WHERE owner_id = 1 ORDER BY log_id DESC LIMIT 1"));
        $this->assertIsArray($log);
        $this->assertSame(FTYP_TRANSPORT, (int)$log['mission']);
        $this->assertSame(2, (int)$log['target_id']);
        $this->assertSame($when, (int)$log['start']);
        $this->assertSame($when + 900, (int)$log['end']);
        $this->assertSame(1, (int)$log['origin_g']);
        $this->assertSame(5000, (int)$log[GID_RC_METAL]);
        $this->assertSame((int)$origin[GID_RC_METAL], (int)$log['p' . GID_RC_METAL]);
    }

    public function testDispatchFleetKeepsUnionDeployTimeAndDropsOldLogs(): void
    {
        $origin = LoadPlanetById(self::HOME);
        $target = LoadPlanetById(10);       // own moon
        $empty = [GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0];
        $when = 1700000000;

        // A flight log older than four weeks is purged on the next dispatch.
        $oldLog = AddDBRow(['owner_id' => 1, 'target_id' => 2, 'mission' => FTYP_ATTACK, 'start' => $when - 5 * 7 * 24 * 3600, 'end' => $when], 'fleetlogs');
        $this->assertSame(1, $this->countRows('fleetlogs', "log_id = $oldLog"));

        $fleetId = DispatchFleet($fleet = $this->fullFleet([GID_F_LC => 2]), $origin, $target, FTYP_ACS_HOLD, 300, $empty, 50, $when, 7, 3600);

        $row = LoadFleet($fleetId);
        $this->assertSame(7, (int)$row['union_id']);
        $this->assertSame(3600, (int)$row['deploy_time']);
        $this->assertSame(0, $this->countRows('fleetlogs', "log_id = $oldLog"));
    }

    public function testDispatchFleetReturnsZeroWhenTheUniverseIsFrozen(): void
    {
        dbquery("UPDATE test_uni SET freeze = 1");

        $empty = [GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0];
        $fleetsBefore = $this->countRows('fleet');

        $fleetId = DispatchFleet($this->fullFleet([GID_F_LF => 1]), LoadPlanetById(self::HOME), LoadPlanetById(self::ENEMY_HOME), FTYP_TRANSPORT, 300, $empty, 10, 1700000000);

        $this->assertSame(0, $fleetId);
        $this->assertSame($fleetsBefore, $this->countRows('fleet'));
    }

    // ========================================================================
    // LaunchRockets

    public function testLaunchRocketsWritesOffMissilesAndLogsTheAttack(): void
    {
        $origin = LoadPlanetById(self::HOME);
        $origin[GID_D_IPM] = 10;
        SetPlanetDefense(self::HOME, $origin);

        $fleetId = LaunchRockets($origin, LoadPlanetById(self::ENEMY_HOME), 300, 4, GID_D_RL);
        $this->assertGreaterThan(0, $fleetId);

        // The launched missiles are gone from the origin planet.
        $this->assertSame(6, $this->planetInt(self::HOME, GID_D_IPM));

        $row = LoadFleet($fleetId);
        $this->assertIsArray($row);
        $this->assertSame(FTYP_MISSILE, (int)$row['mission']);
        $this->assertSame(self::HOME, (int)$row['start_planet']);
        $this->assertSame(self::ENEMY_HOME, (int)$row['target_planet']);
        $this->assertSame(300, (int)$row['flight_time']);
        $this->assertSame(4, (int)$row['ipm_amount']);
        $this->assertSame(GID_D_RL, (int)$row['ipm_target']);

        $task = GetFleetQueue($fleetId);
        $this->assertSame(QUEUE_PRIO_FLEET + FTYP_MISSILE, (int)$task['prio']);

        $log = dbarray(dbquery("SELECT * FROM test_fleetlogs ORDER BY log_id DESC LIMIT 1"));
        $this->assertSame(FTYP_MISSILE, (int)$log['mission']);
        $this->assertSame(4, (int)$log['ipm_amount']);
        $this->assertSame(GID_D_RL, (int)$log['ipm_target']);
    }

    public function testLaunchRocketsAcceptsExactlyTheAvailableMissiles(): void
    {
        $origin = LoadPlanetById(self::HOME);
        $origin[GID_D_IPM] = 3;
        SetPlanetDefense(self::HOME, $origin);

        $fleetId = LaunchRockets($origin, LoadPlanetById(self::ENEMY_HOME), 300, 3, 0);

        $this->assertGreaterThan(0, $fleetId);
        $this->assertSame(0, $this->planetInt(self::HOME, GID_D_IPM));
    }

    public function testLaunchRocketsRejectsMoreMissilesThanAvailable(): void
    {
        $origin = LoadPlanetById(self::HOME);
        $origin[GID_D_IPM] = 3;
        SetPlanetDefense(self::HOME, $origin);
        $fleetsBefore = $this->countRows('fleet');

        $this->assertSame(0, LaunchRockets($origin, LoadPlanetById(self::ENEMY_HOME), 300, 4, 0));

        // Nothing was written off and no attack was queued.
        $this->assertSame(3, $this->planetInt(self::HOME, GID_D_IPM));
        $this->assertSame($fleetsBefore, $this->countRows('fleet'));
    }

    public function testLaunchRocketsRejectsTargetsInAnotherGalaxy(): void
    {
        $origin = LoadPlanetById(self::HOME);
        $origin[GID_D_IPM] = 5;
        SetPlanetDefense(self::HOME, $origin);

        // Missiles cannot leave their galaxy; the check happens before any
        // database write, so a synthetic target row is enough here.
        $foreign = ['g' => 2, 's' => 1, 'p' => 4, 'planet_id' => self::ENEMY_HOME, 'owner_id' => 2, 'type' => PTYP_PLANET];

        $this->assertSame(0, LaunchRockets($origin, $foreign, 300, 1, 0));
        $this->assertSame(5, $this->planetInt(self::HOME, GID_D_IPM));
    }

    public function testLaunchRocketsReturnsZeroWhenTheUniverseIsFrozen(): void
    {
        $origin = LoadPlanetById(self::HOME);
        $origin[GID_D_IPM] = 5;
        SetPlanetDefense(self::HOME, $origin);
        dbquery("UPDATE test_uni SET freeze = 1");

        $this->assertSame(0, LaunchRockets($origin, LoadPlanetById(self::ENEMY_HOME), 300, 1, 0));
        $this->assertSame(5, $this->planetInt(self::HOME, GID_D_IPM));
    }

    // ========================================================================
    // RecallFleet

    public function testRecallFleetDispatchesAReturnFlightAndRemovesTheOriginal(): void
    {
        $task = GetFleetQueue(1);
        $this->assertIsArray($task);
        $now = (int)$task['start'] + 300;      // 300 s already flown

        RecallFleet(1, $now);

        // The original fleet and its task are gone.
        $this->assertFalse(LoadFleet(1));
        $this->assertFalse(GetFleetQueue(1));

        // The return flight carries the same ships back, with half the fuel
        // and the time already flown as its flight time.
        $row = dbarray(dbquery("SELECT * FROM test_fleet WHERE owner_id = 1 AND mission = " . (FTYP_SPY + FTYP_RETURN) . " ORDER BY fleet_id DESC LIMIT 1"));
        $this->assertIsArray($row);
        $this->assertSame(self::HOME, (int)$row['start_planet']);
        $this->assertSame(self::ENEMY_HOME, (int)$row['target_planet']);
        $this->assertSame(5, (int)$row[GID_F_SC]);
        $this->assertSame(50, (int)$row['fuel']);                 // 100 / 2
        $this->assertSame(300, (int)$row['flight_time']);

        $returnTask = GetFleetQueue((int)$row['fleet_id']);
        $this->assertSame($now, (int)$returnTask['start']);
    }

    public function testRecallFleetIgnoresFleetsThatAreAlreadyReturning(): void
    {
        dbquery("UPDATE test_fleet SET mission = " . (FTYP_SPY + FTYP_RETURN) . " WHERE fleet_id = 1");
        $fleetsBefore = $this->countRows('fleet');

        RecallFleet(1, 1700000000);

        $row = LoadFleet(1);
        $this->assertIsArray($row);
        $this->assertSame(FTYP_SPY + FTYP_RETURN, (int)$row['mission']);
        $this->assertSame($fleetsBefore, $this->countRows('fleet'));
    }

    public function testRecallFleetDoesNothingWhenTheUniverseIsFrozen(): void
    {
        dbquery("UPDATE test_uni SET freeze = 1");
        $fleetsBefore = $this->countRows('fleet');

        RecallFleet(1, 1700000000);

        $this->assertIsArray(LoadFleet(1));
        $this->assertSame($fleetsBefore, $this->countRows('fleet'));
    }

    // ========================================================================
    // Queue_Fleet_End - dispatch and defensive paths.

    public function testQueueFleetEndRemovesTheTaskWhenTheFleetIsGone(): void
    {
        $taskId = AddQueue(1, QTYP_FLEET, 999999, 0, 0, 1700000000, 600, QUEUE_PRIO_FLEET);

        Queue_Fleet_End($this->queueEndArgs(999999, $taskId, 1700000600));

        $this->assertSame(0, $this->countRows('queue', "task_id = $taskId"));
    }

    public function testQueueFleetEndDropsTheFlightWhenTheOriginIsGone(): void
    {
        $fleetId = $this->addFleetRow(['mission' => FTYP_TRANSPORT, 'start_planet' => 999999, 'target_planet' => self::ENEMY_HOME]);
        $taskId = $this->addFleetTask($fleetId, FTYP_TRANSPORT, 1700000000, 600);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, 1700000600));

        $this->assertFalse(LoadFleet($fleetId));
        $this->assertSame(0, $this->countRows('queue', "task_id = $taskId"));
    }

    public function testQueueFleetEndDropsAnOutboundFlightWhenTheTargetIsGone(): void
    {
        $fleetId = $this->addFleetRow(['mission' => FTYP_TRANSPORT, 'start_planet' => self::HOME, 'target_planet' => 999999]);
        $taskId = $this->addFleetTask($fleetId, FTYP_TRANSPORT, 1700000000, 600);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, 1700000600));

        $this->assertFalse(LoadFleet($fleetId));
        $this->assertSame(0, $this->countRows('queue', "task_id = $taskId"));
    }

    public function testQueueFleetEndLandsAReturnFlightAtItsOriginWhenTheTargetIsGone(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_SPY + FTYP_RETURN,
            'start_planet' => self::HOME,
            'target_planet' => 999999,
            GID_F_SC => 5,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_SPY + FTYP_RETURN, 1700000000, 600);
        $end = 1700000600;
        dbquery("UPDATE test_planets SET lastpeek = $end");
        $shipsBefore = $this->planetInt(self::HOME, GID_F_SC);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        // The ships are restored instead of the event being dropped.
        $this->assertSame($shipsBefore + 5, $this->planetInt(self::HOME, GID_F_SC));
        $this->assertFalse(LoadFleet($fleetId));
        $this->assertSame(0, $this->countRows('queue', "task_id = $taskId"));
    }

    // ========================================================================
    // Queue_Fleet_End - arrival handlers.

    public function testQueueFleetEndSpyReturnRestoresTheShips(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_SPY + FTYP_RETURN,
            'start_planet' => self::HOME,
            'target_planet' => self::ENEMY_HOME,
            'fuel' => 100,
            GID_F_SC => 5,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_SPY + FTYP_RETURN, 1700000000, 600);
        $end = 1700000600;
        dbquery("UPDATE test_planets SET lastpeek = $end");
        $shipsBefore = $this->planetInt(self::HOME, GID_F_SC);
        $messagesBefore = $this->countMessages(1);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        $this->assertSame($shipsBefore + 5, $this->planetInt(self::HOME, GID_F_SC));
        // SpyReturn is silent.
        $this->assertSame($messagesBefore, $this->countMessages(1));
        $this->assertFalse(LoadFleet($fleetId));
        $this->assertSame(0, $this->countRows('queue', "task_id = $taskId"));
    }

    public function testQueueFleetEndCommonReturnRestoresShipsCargoAndReports(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_DEPLOY + FTYP_RETURN,
            'start_planet' => self::HOME,
            'target_planet' => self::COLONY,
            'fuel' => 100,
            GID_F_LF => 5,
            GID_RC_METAL => 1000,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_DEPLOY + FTYP_RETURN, 1700000000, 300);
        $end = 1700000300;
        dbquery("UPDATE test_planets SET lastpeek = $end");
        $shipsBefore = $this->planetInt(self::HOME, GID_F_LF);
        $metalBefore = $this->planetInt(self::HOME, GID_RC_METAL);
        $messagesBefore = $this->countMessages(1);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        $this->assertSame($shipsBefore + 5, $this->planetInt(self::HOME, GID_F_LF));
        $this->assertSame($metalBefore + 1000, $this->planetInt(self::HOME, GID_RC_METAL));
        $this->assertSame($messagesBefore + 1, $this->countMessages(1));
        $this->assertStringContainsString('Light Fighter: 5', $this->lastMessageText(1));
        $this->assertStringContainsString('1.000 metal', $this->lastMessageText(1));
        $this->assertFalse(LoadFleet($fleetId));
    }

    public function testQueueFleetEndDeployUnloadsShipsCargoAndHalfTheFuel(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_DEPLOY,
            'start_planet' => self::HOME,
            'target_planet' => self::COLONY,
            'flight_time' => 300,
            'fuel' => 100,
            GID_F_LC => 3,
            GID_RC_DEUTERIUM => 400,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_DEPLOY, 1700000000, 300);
        $end = 1700000300;
        dbquery("UPDATE test_planets SET lastpeek = $end");
        $deuteriumBefore = $this->planetInt(self::COLONY, GID_RC_DEUTERIUM);
        $messagesBefore = $this->countMessages(1);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        // The ships land on the target, the cargo and half the fuel are unloaded.
        $this->assertSame(3, $this->planetInt(self::COLONY, GID_F_LC));
        $this->assertSame($deuteriumBefore + 400 + 50, $this->planetInt(self::COLONY, GID_RC_DEUTERIUM));
        $this->assertSame($end, $this->planetInt(self::COLONY, 'lastakt'));
        $this->assertSame($messagesBefore + 1, $this->countMessages(1));
        $this->assertFalse(LoadFleet($fleetId));
        $this->assertSame(0, $this->countRows('queue', "task_id = $taskId"));
    }

    public function testQueueFleetEndTransportDeliversCargoAndNotifiesBothPlayers(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_TRANSPORT,
            'start_planet' => self::HOME,
            'target_planet' => self::ENEMY_HOME,
            'flight_time' => 600,
            'fuel' => 300,
            GID_F_SC => 5,
            GID_RC_METAL => 5000,
            GID_RC_CRYSTAL => 2000,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_TRANSPORT, 1700000000, 600);
        $end = 1700000600;
        dbquery("UPDATE test_planets SET lastpeek = $end");
        $metalBefore = $this->planetInt(self::ENEMY_HOME, GID_RC_METAL);
        $crystalBefore = $this->planetInt(self::ENEMY_HOME, GID_RC_CRYSTAL);
        $ownerMessages = $this->countMessages(1);
        $targetMessages = $this->countMessages(2);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        // The cargo reaches the foreign planet.
        $this->assertSame($metalBefore + 5000, $this->planetInt(self::ENEMY_HOME, GID_RC_METAL));
        $this->assertSame($crystalBefore + 2000, $this->planetInt(self::ENEMY_HOME, GID_RC_CRYSTAL));

        // Both players are notified.
        $this->assertSame($ownerMessages + 1, $this->countMessages(1));
        $this->assertSame($targetMessages + 1, $this->countMessages(2));

        // The transport itself returns empty with half the fuel.
        $return = dbarray(dbquery("SELECT * FROM test_fleet WHERE mission = " . (FTYP_TRANSPORT + FTYP_RETURN) . " ORDER BY fleet_id DESC LIMIT 1"));
        $this->assertIsArray($return);
        $this->assertSame(5, (int)$return[GID_F_SC]);
        $this->assertSame(0, (int)$return[GID_RC_METAL]);
        $this->assertSame(150, (int)$return['fuel']);

        $this->assertFalse(LoadFleet($fleetId));
    }

    public function testQueueFleetEndRecycleHarvestsTheDebrisField(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_RECYCLE,
            'start_planet' => self::HOME,
            'target_planet' => self::DEBRIS,
            'flight_time' => 800,
            'fuel' => 120,
            GID_F_RECYCLER => 2,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_RECYCLE, 1700000000, 800);
        $end = 1700000800;
        dbquery("UPDATE test_planets SET lastpeek = $end");
        $messagesBefore = $this->countMessages(1);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        // Two recyclers carry 40000 units: 20000 metal and 20000 crystal are
        // taken out of the debris field.
        $this->assertSame(480000, $this->planetInt(self::DEBRIS, GID_RC_METAL));
        $this->assertSame(280000, $this->planetInt(self::DEBRIS, GID_RC_CRYSTAL));

        $return = dbarray(dbquery("SELECT * FROM test_fleet WHERE mission = " . (FTYP_RECYCLE + FTYP_RETURN) . " ORDER BY fleet_id DESC LIMIT 1"));
        $this->assertIsArray($return);
        $this->assertSame(2, (int)$return[GID_F_RECYCLER]);
        $this->assertSame(20000, (int)$return[GID_RC_METAL]);
        $this->assertSame(20000, (int)$return[GID_RC_CRYSTAL]);
        $this->assertSame($messagesBefore + 1, $this->countMessages(1));

        $this->assertFalse(LoadFleet($fleetId));
    }

    public function testQueueFleetEndColonizationReturnDestroysThePhantom(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_COLONIZE + FTYP_RETURN,
            'start_planet' => self::HOME,
            'target_planet' => self::PHANTOM,
            'flight_time' => 700,
            'fuel' => 200,
            GID_F_COLON => 1,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_COLONIZE + FTYP_RETURN, 1700000000, 700);
        $end = 1700000700;
        dbquery("UPDATE test_planets SET lastpeek = $end");
        $colonizersBefore = $this->planetInt(self::HOME, GID_F_COLON);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        $this->assertSame($colonizersBefore + 1, $this->planetInt(self::HOME, GID_F_COLON));
        // The colonization phantom is removed.
        $this->assertNull(LoadPlanetById(self::PHANTOM));
        $this->assertFalse(LoadFleet($fleetId));
    }

    public function testQueueFleetEndColonizationArriveFoundsAColonyAndConsumesTheColonizer(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_COLONIZE,
            'start_planet' => self::HOME,
            'target_planet' => self::PHANTOM,
            'flight_time' => 700,
            'fuel' => 200,
            GID_F_COLON => 1,
            GID_RC_METAL => 1000,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_COLONIZE, 1700000000, 700);
        $end = 1700000700;
        dbquery("UPDATE test_planets SET lastpeek = $end");
        $planetsBefore = $this->countRows('planets');
        $colonizersBefore = $this->planetInt(self::HOME, GID_F_COLON);
        $messagesBefore = $this->countMessages(1);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        // A new colony appears at 1:1:6, seeded with the fleet's cargo on top
        // of the 500/500 start resources.
        $colony = dbarray(dbquery("SELECT * FROM test_planets WHERE g = 1 AND s = 1 AND p = 6 AND type = " . PTYP_PLANET));
        $this->assertIsArray($colony);
        $this->assertSame(1, (int)$colony['owner_id']);
        $this->assertSame(1500, (int)$colony[GID_RC_METAL]);
        $this->assertSame(500, (int)$colony[GID_RC_CRYSTAL]);
        $this->assertSame($planetsBefore, $this->countRows('planets'));   // phantom gone, colony added

        // The colonizer is consumed and the rest of the fleet (none here) is
        // not returned.
        $this->assertSame($colonizersBefore, $this->planetInt(self::HOME, GID_F_COLON));
        $this->assertSame(0, $this->countRows('fleet', "owner_id = 1 AND mission = " . (FTYP_COLONIZE + FTYP_RETURN)));
        $this->assertSame($messagesBefore + 1, $this->countMessages(1));
        // The successful colonization is written to the debug log.
        $this->assertSame(1, $this->countRows('debug', 'owner_id = 1'));
        $this->assertFalse(LoadFleet($fleetId));
        $this->assertSame(0, $this->countRows('queue', "task_id = $taskId"));
    }

    public function testQueueFleetEndHoldingArriveStartsTheOrbitHold(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_ACS_HOLD,
            'start_planet' => self::HOME,
            'target_planet' => self::ENEMY_HOME,
            'flight_time' => 300,
            'deploy_time' => 3600,
            GID_F_LF => 5,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_ACS_HOLD, 1700000000, 300);
        $end = 1700000300;
        dbquery("UPDATE test_planets SET lastpeek = $end");

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        // The hold time becomes the flight time of the orbiting fleet and the
        // former flight time becomes its hold time. Holding is free.
        $orbit = dbarray(dbquery("SELECT * FROM test_fleet WHERE mission = " . (FTYP_ACS_HOLD + FTYP_ORBITING) . " ORDER BY fleet_id DESC LIMIT 1"));
        $this->assertIsArray($orbit);
        $this->assertSame(5, (int)$orbit[GID_F_LF]);
        $this->assertSame(3600, (int)$orbit['flight_time']);
        $this->assertSame(300, (int)$orbit['deploy_time']);
        $this->assertSame(0, (int)$orbit['fuel']);
        $this->assertSame($end, $this->planetInt(self::ENEMY_HOME, 'lastakt'));
        $this->assertFalse(LoadFleet($fleetId));
    }

    public function testQueueFleetEndHoldingHoldReturnsTheFleetAfterTheHoldTime(): void
    {
        $fleetId = $this->addFleetRow([
            'mission' => FTYP_ACS_HOLD + FTYP_ORBITING,
            'start_planet' => self::HOME,
            'target_planet' => self::ENEMY_HOME,
            'flight_time' => 3600,
            'deploy_time' => 300,
            GID_F_LF => 5,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_ACS_HOLD + FTYP_ORBITING, 1700000000, 3600);
        $end = 1700003600;

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        // The hold time is used as the flight time of the way home.
        $return = dbarray(dbquery("SELECT * FROM test_fleet WHERE mission = " . (FTYP_ACS_HOLD + FTYP_RETURN) . " ORDER BY fleet_id DESC LIMIT 1"));
        $this->assertIsArray($return);
        $this->assertSame(5, (int)$return[GID_F_LF]);
        $this->assertSame(300, (int)$return['flight_time']);
        $this->assertFalse(LoadFleet($fleetId));
    }

    public function testQueueFleetEndSpyArriveFilesReportAndReturnsTheProbe(): void
    {
        // The espionage counter is randomized; seed the generator so the spies
        // stay undetected (rand() gives counter 1, mt_rand(0, 100) gives 25)
        // and the probes fly home instead of starting a battle.
        mt_srand(1);

        $fleetId = $this->addFleetRow([
            'mission' => FTYP_SPY,
            'start_planet' => self::HOME,
            'target_planet' => self::ENEMY_HOME,
            'flight_time' => 600,
            'fuel' => 100,
            GID_F_PROBE => 1,
        ]);
        $taskId = $this->addFleetTask($fleetId, FTYP_SPY, 1700000000, 600);
        $end = 1700000600;
        dbquery("UPDATE test_planets SET lastpeek = $end");
        $reportsBefore = $this->countMessages(1, MTYP_SPY_REPORT);
        $alertsBefore = $this->countMessages(2);

        Queue_Fleet_End($this->queueEndArgs($fleetId, $taskId, $end));

        // The spy report goes to the attacker, the alarm to the target.
        $this->assertSame($reportsBefore + 1, $this->countMessages(1, MTYP_SPY_REPORT));
        $this->assertSame($alertsBefore + 1, $this->countMessages(2));

        $report = $this->lastMessageText(1);
        $this->assertStringContainsString('[1:3:4]', $report);
        $this->assertStringContainsString('Chance for spy counter', $report);
        $this->assertStringContainsString('Small Cargo', $report);

        $subject = dbarray(dbquery("SELECT subj FROM test_messages WHERE owner_id = 1 ORDER BY msg_id DESC LIMIT 1"));
        $this->assertIsArray($subject);
        $this->assertStringContainsString('espionagereport', (string)$subject['subj']);

        // The probe returns to its origin planet.
        $return = dbarray(dbquery("SELECT * FROM test_fleet WHERE owner_id = 1 AND mission = " . (FTYP_SPY + FTYP_RETURN) . " ORDER BY fleet_id DESC LIMIT 1"));
        $this->assertIsArray($return);
        $this->assertSame(1, (int)$return[GID_F_PROBE]);
        $this->assertSame(self::HOME, (int)$return['start_planet']);
        $this->assertFalse(LoadFleet($fleetId));
    }

    // ========================================================================
    // Fleetlogs

    public function testFleetlogsFromPlayerFiltersByMission(): void
    {
        $origin = LoadPlanetById(self::HOME);
        $target = LoadPlanetById(self::ENEMY_HOME);
        $empty = [GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0];

        DispatchFleet($this->fullFleet([GID_F_LF => 1]), $origin, $target, FTYP_ATTACK, 600, $empty, 10, 1700000000);
        DispatchFleet($this->fullFleet([GID_F_SC => 1]), $origin, $target, FTYP_TRANSPORT, 600, $empty, 10, 1700000100);

        // The fixture itself does not create flight logs.
        $this->assertSame(2, dbrows(FleetlogsFromPlayer(1, null)));
        $this->assertSame(1, dbrows(FleetlogsFromPlayer(1, [FTYP_TRANSPORT])));
        $this->assertSame(2, dbrows(FleetlogsFromPlayer(1, [FTYP_ATTACK, FTYP_TRANSPORT])));
        $this->assertSame(0, dbrows(FleetlogsFromPlayer(2, null)));
    }

    public function testFleetlogsToPlayerExcludesOwnFlights(): void
    {
        $origin = LoadPlanetById(self::HOME);
        $empty = [GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0];

        DispatchFleet($this->fullFleet([GID_F_LF => 1]), $origin, LoadPlanetById(self::ENEMY_HOME), FTYP_ATTACK, 600, $empty, 10, 1700000000);
        DispatchFleet($this->fullFleet([GID_F_SC => 1]), $origin, LoadPlanetById(self::COLONY), FTYP_TRANSPORT, 600, $empty, 10, 1700000100);

        $this->assertSame(1, dbrows(FleetlogsToPlayer(2, null)));
        $this->assertSame(1, dbrows(FleetlogsToPlayer(2, [FTYP_ATTACK])));
        $this->assertSame(0, dbrows(FleetlogsToPlayer(2, [FTYP_TRANSPORT])));
        // Flights to one's own planet are not incoming flights.
        $this->assertSame(0, dbrows(FleetlogsToPlayer(1, null)));
    }

    public function testFleetlogsMissionTextMarksDirectionAndMission(): void
    {
        ob_start();
        FleetlogsMissionText(FTYP_ATTACK);
        $outbound = (string)ob_get_clean();
        $this->assertStringContainsString('Attack', $outbound);
        $this->assertStringContainsString('(У)', $outbound);

        ob_start();
        FleetlogsMissionText(FTYP_ATTACK + FTYP_RETURN);
        $returning = (string)ob_get_clean();
        $this->assertStringContainsString('Attack', $returning);
        $this->assertStringContainsString('(В)', $returning);

        ob_start();
        FleetlogsMissionText(FTYP_ACS_HOLD + FTYP_ORBITING);
        $orbiting = (string)ob_get_clean();
        $this->assertStringContainsString('Defend', $orbiting);
        $this->assertStringContainsString('(Д)', $orbiting);

        // Custom missions are not resolved to a localization key.
        ob_start();
        FleetlogsMissionText(FTYP_CUSTOM + 5);
        $custom = (string)ob_get_clean();
        $this->assertStringContainsString('(Custom)', $custom);
        $this->assertStringContainsString('FLEET_ORDER_1005', $custom);
    }

    // ========================================================================
    // FleetAvailableMissions

    public function testAvailableMissionsForAnExpeditionTarget(): void
    {
        $missions = FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 1, 16, GAME_PTYP_PLANET, $this->fullFleet());
        $this->assertSame([FTYP_EXPEDITION], $missions);
    }

    public function testAvailableMissionsForADebrisFieldRequireRecyclers(): void
    {
        $this->assertSame([], FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 2, 5, GAME_PTYP_DF, $this->fullFleet()));
        $this->assertSame(
            [FTYP_RECYCLE],
            FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 2, 5, GAME_PTYP_DF, $this->fullFleet([GID_F_RECYCLER => 2]))
        );
    }

    public function testAvailableMissionsForEmptySpace(): void
    {
        // 1:15:1 is empty: transport and attack, plus colonize with a colony ship.
        $this->assertSame(
            [FTYP_TRANSPORT, FTYP_ATTACK],
            FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 15, 1, GAME_PTYP_PLANET, $this->fullFleet())
        );
        $this->assertSame(
            [FTYP_TRANSPORT, FTYP_ATTACK, FTYP_COLONIZE],
            FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 15, 1, GAME_PTYP_PLANET, $this->fullFleet([GID_F_COLON => 1]))
        );
    }

    public function testAvailableMissionsForAnOwnPlanet(): void
    {
        // PlayerOne's own colony at 1:1:5: only transport and deploy.
        $this->assertSame(
            [FTYP_TRANSPORT, FTYP_DEPLOY],
            FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 1, 5, GAME_PTYP_PLANET, $this->fullFleet())
        );
    }

    public function testAvailableMissionsForAnAllyPlanetAddAcsHold(): void
    {
        // PlayerOne and PlayerTwo are allies and accepted buddies, so the
        // friendly branch applies; the ACS hold is offered because acs = 1.
        $this->assertSame(
            [FTYP_TRANSPORT, FTYP_ATTACK, FTYP_ACS_HOLD, FTYP_SPY],
            FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 3, 4, GAME_PTYP_PLANET, $this->fullFleet([GID_F_PROBE => 1]))
        );
    }

    public function testAvailableMissionsForAForeignMoonWithADeathstar(): void
    {
        // 1:3:4 also holds PlayerTwo's moon; a Deathstar unlocks the destroy
        // mission, a probe unlocks espionage.
        $this->assertSame(
            [FTYP_TRANSPORT, FTYP_ATTACK, FTYP_ACS_HOLD, FTYP_DESTROY, FTYP_SPY],
            FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 3, 4, GAME_PTYP_MOON, $this->fullFleet([GID_F_DEATHSTAR => 1, GID_F_PROBE => 1]))
        );
    }

    public function testAvailableMissionsForANonAllyPlanetWithoutAcsHold(): void
    {
        // Leave the alliance: 1:5:4 (PlayerThree) is then a plain foreign
        // planet with no accepted buddy request, so the hostile branch applies
        // and no joint attack hold is offered.
        dbquery("UPDATE test_users SET ally_id = 0 WHERE player_id = 3");
        InvalidateUserCache();

        $this->assertSame(
            [FTYP_TRANSPORT, FTYP_ATTACK, FTYP_SPY],
            FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 5, 4, GAME_PTYP_PLANET, $this->fullFleet([GID_F_PROBE => 1]))
        );
    }

    public function testAvailableMissionsAddTheAcsAttackWhenAUnionFleetTargetsTheSamePlanet(): void
    {
        // A union whose fleet is already flying to PlayerTwo's home planet
        // turns that planet into a joint attack target.
        $acsFleet = $this->addFleetRow(['owner_id' => 1, 'union_id' => 1, 'mission' => FTYP_ACS_ATTACK, 'target_planet' => self::ENEMY_HOME]);
        AddDBRow(['fleet_id' => $acsFleet, 'target_player' => 2, 'name' => 'ACS', 'players' => '1'], 'union');

        $missions = FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 3, 4, GAME_PTYP_PLANET, $this->fullFleet([GID_F_PROBE => 1]));

        $this->assertSame([FTYP_TRANSPORT, FTYP_ATTACK, FTYP_ACS_HOLD, FTYP_SPY, FTYP_ACS_ATTACK], $missions);
    }

    public function testAvailableMissionsWrapperDelegatesToTheDefaultList(): void
    {
        $fleet = $this->fullFleet([GID_F_PROBE => 1]);
        $expected = FleetAvailableMissionsDefault(1, 1, 4, GAME_PTYP_PLANET, 1, 3, 4, GAME_PTYP_PLANET, $fleet);
        $actual = FleetAvailableMissions(1, 1, 4, GAME_PTYP_PLANET, 1, 3, 4, GAME_PTYP_PLANET, $fleet);

        $this->assertSame($expected, $actual);
    }
}
