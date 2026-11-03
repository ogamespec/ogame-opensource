<?php

// Tests for the global event queue module (game/core/queue.php).
//
// Covered areas:
//  * the queue primitives: AddQueue / LoadQueue / RemoveQueue / ProlongQueue /
//    FreezeQueue / FlushQueue;
//  * UpdateQueue, the batch dispatcher (batch limit, end/priority ordering,
//    universe freeze, unknown task types, coupon tasks);
//  * the building queue: GetBuildQueue, CanBuild, BuildEnque, BuildDeque,
//    PropagateBuildQueue, Queue_Build_End;
//  * the shipyard queue: GetShipyardQueue, ShipyardLatestTime, AddShipyard,
//    Queue_Shipyard_End;
//  * the research queue: GetResearchQueue, CanResearch, StartResearch,
//    StopResearch, Queue_Research_End;
//  * the player events (points recalculation, name change, unban, attack ban,
//    e-mail change) and the universe events (old scores, re-login, farspace
//    cooldown, debris/planet/player cleanup, ally points, debug);
//  * the fleet queue queries: GetFleetQueue, EnumFleetQueue,
//    EnumOwnFleetQueue, EnumOwnFleetQueueSpecial, EnumPlanetFleets.
//
// The tests run against the real database layer with the in-memory SQLite
// backend and the real game schema (see testing/bootstrap.php and
// testing/FixtureBuilder.php); every test method starts with a fresh database.
//
// The module reads the wall clock in a few places. Where it does, an explicit
// $now / $when is injected, or the expectation is computed with the same
// expression the source uses (never a hard-coded "current" timestamp).

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

// The game core modules assign global variables ($fleetmap, $defmap, $rakmap,
// $initial, ...) at their top level; only the process-isolated child loads the
// bootstrap at the true top level, so the globals are available (the same
// configuration the other database-driven module tests use).
#[RunTestsInSeparateProcesses]
class QueueCoreTest extends TestCase
{
    private FixtureBuilder $fixture;
    private string $dbPrefix;
    private int $now;

    protected function setUp(): void
    {
        // loca files are resolved relative to the game directory.
        chdir(__DIR__ . '/../game');

        // The ModsExec* hooks iterate over $modlist (no mod is installed here).
        $GLOBALS['modlist'] = array ();

        // Debug()/UserLog() read the request environment; PHPUnit does not set it.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');
        $this->dbPrefix = $this->fixture->getDbPrefix();
        $this->now = time();

        global $db_prefix, $GlobalUni, $loca_lang;
        $db_prefix = $this->dbPrefix;
        $GlobalUni = $this->fixture->getUniData();
        $loca_lang = 'en';

        // Several handlers compare $GlobalUser['player_id'] with the task owner
        // (cache invalidation) and Debug() needs a non-null user. A technical
        // "nobody" account keeps the real players out of the user cache.
        $GLOBALS['GlobalUser'] = array ('player_id' => 0, 'lang' => 'en');

        loca_add ('build', 'en');
        loca_add ('technames', 'en');
        loca_add ('debug', 'en');
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Drop the queue rows created by the fixture, so a test can count its own
     * tasks without accounting for the seeded universe.
     */
    private function clearQueue () : void
    {
        dbquery ("DELETE FROM ".$this->dbPrefix."queue");
    }

    /**
     * Read the queue rows matching the given SQL condition.
     *
     * @return array List of queue rows (task_id ascending).
     */
    private function queueRows (string $where = '1') : array
    {
        $result = dbquery ("SELECT * FROM ".$this->dbPrefix."queue WHERE $where ORDER BY task_id ASC");
        $rows = array ();
        while ( $row = dbarray ($result) ) $rows[] = $row;
        return $rows;
    }

    /**
     * Read the queue rows of an already executed query result.
     */
    private function rowsOf (mixed $result) : array
    {
        $rows = array ();
        while ( $row = dbarray ($result) ) $rows[] = $row;
        return $rows;
    }

    private function countQueue (string $where = '1') : int
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."queue WHERE $where");
        $row = dbarray ($result);
        return (int)$row['cnt'];
    }

    /**
     * Read one queue task, or false when it does not exist.
     */
    private function taskRow (int $taskId) : mixed
    {
        return LoadQueue ($taskId);
    }

    private function countBuildQueue (int $planetId) : int
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."buildqueue WHERE planet_id = $planetId");
        $row = dbarray ($result);
        return (int)$row['cnt'];
    }

    private function countMessages (int $playerId) : int
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."messages WHERE owner_id = $playerId");
        $row = dbarray ($result);
        return (int)$row['cnt'];
    }

    /**
     * Read the build queue rows of a planet (list_id ascending), as an array.
     */
    private function buildQueueRows (int $planetId) : array
    {
        return $this->rowsOf (GetBuildQueue ($planetId));
    }

    /**
     * Insert a buildqueue row directly and return its id.
     */
    private function addBuildQueueRow (int $ownerId, int $planetId, int $listId, int $techId, int $level, int $destroy, int $start, int $end) : int
    {
        return AddDBRow (array (
            'owner_id' => $ownerId, 'planet_id' => $planetId, 'list_id' => $listId, 'tech_id' => $techId,
            'level' => $level, 'destroy' => $destroy, 'start' => $start, 'end' => $end,
        ), 'buildqueue');
    }

    private function addFleet (int $ownerId, int $mission, int $startPlanet, int $targetPlanet) : int
    {
        return AddDBRow (array (
            'owner_id' => $ownerId, 'mission' => $mission, 'start_planet' => $startPlanet,
            'target_planet' => $targetPlanet, 'flight_time' => 100, 'deploy_time' => 0, 'fuel' => 0,
        ), 'fleet');
    }

    private function addUser (int $playerId, array $overrides = array ()) : int
    {
        return AddDBRow (array_merge (array (
            'player_id' => $playerId, 'name' => 'User' . $playerId, 'oname' => 'User' . $playerId, 'lang' => 'en',
            'admin' => 0, 'validated' => 1, 'regdate' => $this->now - 10 * 86400,
            'lastclick' => $this->now, 'lastlogin' => $this->now, 'dm' => 0,
            'disable' => 0, 'disable_until' => 0,
        ), $overrides), 'users');
    }

    /**
     * Read one column of a planet row.
     */
    private function planetField (int $planetId, string $column) : mixed
    {
        $result = dbquery ("SELECT `".$column."` AS v FROM ".$this->dbPrefix."planets WHERE planet_id = $planetId");
        $row = dbarray ($result);
        return $row === false ? null : $row['v'];
    }

    /**
     * Read one column of a user row.
     */
    private function userField (int $playerId, string $column) : mixed
    {
        $result = dbquery ("SELECT `".$column."` AS v FROM ".$this->dbPrefix."users WHERE player_id = $playerId");
        $row = dbarray ($result);
        return $row === false ? null : $row['v'];
    }

    /**
     * Read one column of the universe row.
     */
    private function uniField (string $column) : mixed
    {
        $result = dbquery ("SELECT `".$column."` AS v FROM ".$this->dbPrefix."uni");
        $row = dbarray ($result);
        return $row === false ? null : $row['v'];
    }

    /**
     * Read one column of an alliance row.
     */
    private function allyField (int $allyId, string $column) : mixed
    {
        $result = dbquery ("SELECT `".$column."` AS v FROM ".$this->dbPrefix."ally WHERE ally_id = $allyId");
        $row = dbarray ($result);
        return $row === false ? null : $row['v'];
    }

    private function planetExists (int $planetId) : bool
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."planets WHERE planet_id = $planetId");
        $row = dbarray ($result);
        return (int)$row['cnt'] > 0;
    }

    private function userExists (int $playerId) : bool
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."users WHERE player_id = $playerId");
        $row = dbarray ($result);
        return (int)$row['cnt'] > 0;
    }

    /**
     * Read the three resource columns of a planet as integers.
     */
    private function planetResources (int $planetId) : array
    {
        $planet = LoadPlanetById ($planetId);
        return array (
            GID_RC_METAL => (int)$planet[GID_RC_METAL],
            GID_RC_CRYSTAL => (int)$planet[GID_RC_CRYSTAL],
            GID_RC_DEUTERIUM => (int)$planet[GID_RC_DEUTERIUM],
        );
    }

    /**
     * Make a planet's resource flow reproducible: the mines, the solar plant
     * and the fusion reactor are switched off (so no production drifts) and
     * the stored amounts are pinned (a value above the storage capacity is
     * never pulled down by GetUpdatePlanet).
     */
    private function preparePlanet (int $planetId, int $metal, int $crystal, int $deuterium, ?int $maxfields = null) : void
    {
        $sets = "`".GID_RC_METAL."` = $metal, `".GID_RC_CRYSTAL."` = $crystal, `".GID_RC_DEUTERIUM."` = $deuterium, "
              . "`".GID_B_METAL_MINE."` = 0, `".GID_B_CRYS_MINE."` = 0, `".GID_B_DEUT_SYNTH."` = 0, "
              . "`".GID_B_SOLAR."` = 0, `".GID_B_FUSION."` = 0, lastpeek = ".$this->now;
        if ($maxfields !== null) $sets .= ", maxfields = $maxfields";
        dbquery ("UPDATE ".$this->dbPrefix."planets SET $sets WHERE planet_id = $planetId");
    }

    /**
     * Give a player a real, countable score: AdjustStats/RecalcStats skip
     * accounts that are banned (and the fixture leaves "banned" NULL).
     */
    private function allowStats (int $playerId) : void
    {
        dbquery ("UPDATE ".$this->dbPrefix."users SET banned = 0, admin = 0 WHERE player_id = $playerId");
        InvalidateUserCache ();
    }

    /**
     * Load the first moon of the given owner.
     */
    private function moonOf (int $ownerId) : array
    {
        $result = dbquery ("SELECT * FROM ".$this->dbPrefix."planets WHERE owner_id = $ownerId AND type = ".PTYP_MOON." ORDER BY planet_id ASC LIMIT 1");
        $row = dbarray ($result);
        $this->assertNotFalse ($row, 'the fixture must contain a moon for player ' . $ownerId);
        return $row;
    }

    // ========================================================================
    // Queue primitives
    // ========================================================================

    /**
     * AddQueue stores the whole task row and computes the end time.
     */
    public function testAddQueueStoresTheTaskRow (): void
    {
        $id = AddQueue (7, QTYP_BUILD, 3, GID_B_METAL_MINE, 5, 1000, 60);

        $this->assertGreaterThan (0, $id);

        $row = $this->taskRow ($id);
        $this->assertIsArray ($row);
        $this->assertSame (7, (int)$row['owner_id']);
        $this->assertSame (QTYP_BUILD, $row['type']);
        $this->assertSame (3, (int)$row['sub_id']);
        $this->assertSame (GID_B_METAL_MINE, (int)$row['obj_id']);
        $this->assertSame (5, (int)$row['level']);
        $this->assertSame (1000, (int)$row['start']);
        $this->assertSame (1060, (int)$row['end']);
        $this->assertSame (QUEUE_PRIO_LOWEST, (int)$row['prio']);
        $this->assertSame (0, (int)$row['freeze']);
        $this->assertSame (0, (int)$row['frozen']);

        // An explicit priority is stored as given.
        $id2 = AddQueue (7, QTYP_DEBUG, 0, 0, 0, 1000, 0, QUEUE_PRIO_DEBUG);
        $this->assertSame (QUEUE_PRIO_DEBUG, (int)$this->taskRow ($id2)['prio']);
        $this->assertSame (1000, (int)$this->taskRow ($id2)['end']);
    }

    /**
     * LoadQueue returns false for a task that does not exist.
     */
    public function testLoadQueueReturnsFalseForAnUnknownTask (): void
    {
        $this->assertFalse (LoadQueue (424242));
    }

    /**
     * RemoveQueue deletes exactly the given task; a falsy id is a no-op.
     */
    public function testRemoveQueueDeletesOnlyTheGivenTask (): void
    {
        $this->clearQueue ();
        $a = AddQueue (1, QTYP_DEBUG, 0, 0, 0, 1000, 0);
        $b = AddQueue (1, QTYP_DEBUG, 0, 0, 0, 1000, 0);

        RemoveQueue (0);      // "no task" guard, must not delete anything
        $this->assertSame (2, $this->countQueue ());

        RemoveQueue ($a);
        $this->assertFalse ($this->taskRow ($a));
        $this->assertIsArray ($this->taskRow ($b));
    }

    /**
     * ProlongQueue shifts the end time by the given amount (both signs).
     */
    public function testProlongQueueShiftsTheEndTime (): void
    {
        $id = AddQueue (1, QTYP_FLEET, 1, 0, 0, 1000, 100);
        $this->assertSame (1100, (int)$this->taskRow ($id)['end']);

        ProlongQueue ($id, 250);
        $this->assertSame (1350, (int)$this->taskRow ($id)['end']);

        ProlongQueue ($id, -50);
        $this->assertSame (1300, (int)$this->taskRow ($id)['end']);
    }

    /**
     * FreezeQueue records the freeze moment; freezing an already frozen task
     * keeps the first freeze time. An unknown task is ignored.
     */
    public function testFreezeQueueSetsTheFlagAndKeepsTheFirstFreezeTime (): void
    {
        $this->clearQueue ();
        $id = AddQueue (1, QTYP_FLEET, 1, 0, 0, 1000, 100);

        FreezeQueue ($id, true, 5000);

        $row = $this->taskRow ($id);
        $this->assertSame (1, (int)$row['freeze']);
        $this->assertSame (5000, (int)$row['frozen']);
        $this->assertSame (1100, (int)$row['end'], 'freezing must not move the end time');

        // Freezing again must not overwrite the original freeze time.
        FreezeQueue ($id, true, 9000);
        $row = $this->taskRow ($id);
        $this->assertSame (5000, (int)$row['frozen']);

        // An unknown task id is silently ignored.
        FreezeQueue (999999, true, 1234);
        $this->assertSame (1, $this->countQueue ());
    }

    /**
     * UnfreezeQueue extends the end time by the time the task spent frozen.
     */
    public function testUnfreezeQueueExtendsTheEndByTheFrozenTime (): void
    {
        $id = AddQueue (1, QTYP_FLEET, 1, 0, 0, 1000, 100);
        FreezeQueue ($id, true, 5000);

        FreezeQueue ($id, false, 5300);      // frozen for 300 s

        $row = $this->taskRow ($id);
        $this->assertSame (0, (int)$row['freeze']);
        $this->assertSame (0, (int)$row['frozen']);
        $this->assertSame (1400, (int)$row['end']);

        // An unfreeze time before the freeze time must not shorten/extend the task.
        $id2 = AddQueue (1, QTYP_FLEET, 1, 0, 0, 1000, 100);
        FreezeQueue ($id2, true, 5000);
        FreezeQueue ($id2, false, 4000);
        $row2 = $this->taskRow ($id2);
        $this->assertSame (1100, (int)$row2['end'], 'a bogus unfreeze time must keep the end time');
        $this->assertSame (0, (int)$row2['freeze']);

        // Unfreezing a task that is not frozen does nothing at all.
        $id3 = AddQueue (1, QTYP_FLEET, 1, 0, 0, 1000, 100);
        FreezeQueue ($id3, false, 8000);
        $row3 = $this->taskRow ($id3);
        $this->assertSame (1100, (int)$row3['end']);
        $this->assertSame (0, (int)$row3['frozen']);
    }

    // ========================================================================
    // UpdateQueue (the batch dispatcher)
    // ========================================================================

    /**
     * UpdateQueue runs the handlers of the tasks whose end time has passed and
     * leaves the future ones alone.
     */
    public function testUpdateQueueProcessesDueTasksAndKeepsFutureOnes (): void
    {
        $due = AddQueue (USER_SPACE, QTYP_DEBUG, 0, 0, 0, 1000, 0, QUEUE_PRIO_DEBUG);
        $future = AddQueue (USER_SPACE, QTYP_DEBUG, 0, 0, 0, 2000, 100, QUEUE_PRIO_DEBUG);

        UpdateQueue (1000);

        $this->assertFalse ($this->taskRow ($due));
        $this->assertIsArray ($this->taskRow ($future));
    }

    /**
     * At most QUEUE_BATCH tasks are executed per call, the earliest end time
     * first; tasks that share an end time are ordered by descending priority.
     */
    public function testUpdateQueueHonoursTheBatchLimitAndOrdering (): void
    {
        $this->clearQueue ();

        // 20 tasks, one second apart: only the first QUEUE_BATCH (earliest) run.
        $byEnd = array ();
        for ($i = 0; $i < 20; $i++) {
            $byEnd[$i] = AddQueue (USER_SPACE, QTYP_DEBUG, 0, 0, 0, 100 + $i, 0, QUEUE_PRIO_DEBUG);
        }

        UpdateQueue (100000);

        $this->assertSame (20 - QUEUE_BATCH, $this->countQueue ());
        for ($i = 0; $i < 20; $i++) {
            if ($i < QUEUE_BATCH) $this->assertFalse ($this->taskRow ($byEnd[$i]), "task with end " . (100 + $i) . " should have run");
            else $this->assertIsArray ($this->taskRow ($byEnd[$i]), "task with end " . (100 + $i) . " should have been kept");
        }

        // 17 tasks ending at the same second: the 16 with the highest
        // priority run, the lowest-priority one survives.
        $this->clearQueue ();
        $byPrio = array ();
        for ($p = 0; $p < 17; $p++) {
            $byPrio[$p] = AddQueue (USER_SPACE, QTYP_DEBUG, 0, 0, 0, 500, 0, $p);
        }

        UpdateQueue (500);

        $this->assertSame (1, $this->countQueue ());
        $this->assertIsArray ($this->taskRow ($byPrio[0]), 'the lowest-priority task must survive the batch');
        $this->assertFalse ($this->taskRow ($byPrio[16]));
    }

    /**
     * A task whose type has no handler is removed and written to the debug log.
     */
    public function testUpdateQueueRemovesUnknownTaskTypesAndLogsThem (): void
    {
        dbquery ("DELETE FROM ".$this->dbPrefix."debug");
        $id = AddQueue (1, 'NoSuchTaskType', 0, 0, 0, 1000, 0);

        UpdateQueue (1000);

        $this->assertFalse ($this->taskRow ($id));

        $result = dbquery ("SELECT * FROM ".$this->dbPrefix."debug");
        $log = dbarray ($result);
        $this->assertIsArray ($log);
        $this->assertSame (loca_lang ('DEBUG_QUEUE_UNKNOWN', 'en') . 'NoSuchTaskType', $log['text']);
    }

    /**
     * A frozen universe is not processed at all.
     */
    public function testUpdateQueueDoesNothingWhenTheUniverseIsFrozen (): void
    {
        global $GlobalUni;

        $id = AddQueue (USER_SPACE, QTYP_DEBUG, 0, 0, 0, 1000, 0, QUEUE_PRIO_DEBUG);
        $GlobalUni['freeze'] = 1;

        UpdateQueue (100000);

        $this->assertIsArray ($this->taskRow ($id), 'no task may run while the universe is frozen');
    }

    /**
     * Coupon tasks are processed in the second, unlocked pass of UpdateQueue.
     */
    public function testUpdateQueueHandlesCouponTasksOutsideTheLock (): void
    {
        // level = 0 (periodicity in days) makes the handler delete the task.
        // obj_id = 1000 means "in the game for 1000 days", which no fixture
        // user matches, so no coupon is actually sent.
        $id = AddQueue (USER_SPACE, QTYP_COUPON, 0, 1000, 0, 500, 0, QUEUE_PRIO_COUPON);

        UpdateQueue (1000);

        $this->assertFalse ($this->taskRow ($id));
    }

    /**
     * UpdateQueue routes a due build task to the building completion handler.
     */
    public function testUpdateQueueDispatchesCompletedBuilds (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_METAL_STOR."` = 3, fields = 12 WHERE planet_id = 2");
        $this->allowStats (1);

        $bq = $this->addBuildQueueRow (1, 2, 1, GID_B_METAL_STOR, 4, 0, 400, 1000);
        $build = AddQueue (1, QTYP_BUILD, $bq, GID_B_METAL_STOR, 4, 400, 600, QUEUE_PRIO_BUILD);

        dbquery ("UPDATE ".$this->dbPrefix."users SET name_changed = 1 WHERE player_id = 3");
        $name = AddQueue (3, QTYP_ALLOW_NAME, 0, 0, 0, 400, 600, QUEUE_PRIO_LOWEST);
        $debug = AddQueue (USER_SPACE, QTYP_DEBUG, 0, 0, 0, 400, 600, QUEUE_PRIO_DEBUG);

        UpdateQueue (1000);

        $this->assertSame (4, (int)$this->planetField (2, GID_B_METAL_STOR));
        $this->assertSame (13, (int)$this->planetField (2, 'fields'));
        $this->assertFalse ($this->taskRow ($build));
        $this->assertFalse ($this->taskRow ($name));
        $this->assertFalse ($this->taskRow ($debug));
        $this->assertSame (0, (int)$this->userField (3, 'name_changed'));
        $this->assertSame (0, $this->countBuildQueue (2));
    }

    // ========================================================================
    // FlushQueue
    // ========================================================================

    /**
     * FlushQueue removes the shipyard order, the building orders and the build
     * queue rows of the planet, but leaves unrelated tasks (fleets, research).
     */
    public function testFlushQueueRemovesShipyardAndBuildOrders (): void
    {
        $this->assertSame (1, $this->countQueue ("type = '".QTYP_SHIPYARD."' AND sub_id = 1"));
        $bqIds = array ();
        foreach ($this->buildQueueRows (1) as $row) $bqIds[] = (int)$row['id'];
        $this->assertCount (2, $bqIds);
        $this->assertSame (1, $this->countQueue ("type = '".QTYP_RESEARCH."' AND sub_id = 1"));

        FlushQueue (1);

        $this->assertSame (0, $this->countQueue ("type = '".QTYP_SHIPYARD."' AND sub_id = 1"));
        $this->assertSame (0, $this->countBuildQueue (1));
        $this->assertSame (0, $this->countQueue ("(type = '".QTYP_BUILD."' OR type = '".QTYP_DEMOLISH."') AND sub_id IN (".implode (',', $bqIds).")"));
        // Fleets and research are not touched.
        $this->assertSame (11, $this->countQueue ("type = '".QTYP_FLEET."'"));
        $this->assertSame (1, $this->countQueue ("type = '".QTYP_RESEARCH."'"));
    }

    // ========================================================================
    // Building queue
    // ========================================================================

    /**
     * GetBuildQueue returns the rows of one planet ordered by list_id.
     */
    public function testGetBuildQueueIsOrderedByListId (): void
    {
        $rows = $this->buildQueueRows (1);

        $this->assertCount (2, $rows);
        $this->assertSame (1, (int)$rows[0]['list_id']);
        $this->assertSame (GID_B_METAL_MINE, (int)$rows[0]['tech_id']);
        $this->assertSame (6, (int)$rows[0]['level']);
        $this->assertSame (2, (int)$rows[1]['list_id']);
        $this->assertSame (GID_B_CRYS_MINE, (int)$rows[1]['tech_id']);
        $this->assertSame (4, (int)$rows[1]['level']);

        $this->assertSame (0, count ($this->buildQueueRows (2)));
    }

    /**
     * CanBuild returns an empty string when the build is possible.
     */
    public function testCanBuildAcceptsAPossibleBuild (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);

        // The planet array must come from GetUpdatePlanet: it carries the
        // virtual energy resource that IsEnoughResources() checks.
        $user = LoadUser (1);
        $planet = GetUpdatePlanet (2, $this->now);

        $this->assertSame ('', CanBuild ($user, $planet, GID_B_METAL_STOR, 4, false));
        $this->assertSame ('', CanBuild ($user, $planet, GID_B_METAL_STOR, 4, false, true));
    }

    /**
     * CanBuild rejects invalid ids, wrong planet types, a full planet and a
     * construction above the level cap.
     */
    public function testCanBuildRejectsInvalidIdsPlanetTypesAndSpace (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);

        $user = LoadUser (1);
        $planet = GetUpdatePlanet (2, $this->now);
        $moon = $this->moonOf (1);

        // Not a building at all.
        $this->assertSame (loca_lang ('BUILD_ERROR_INVALID_ID', 'en'), CanBuild ($user, $planet, GID_F_LF, 1, false, true));
        // A lunar building on a planet.
        $this->assertSame (loca_lang ('BUILD_ERROR_INVALID_PTYPE', 'en'), CanBuild ($user, $planet, GID_B_LUNAR_BASE, 1, false, true));
        // A planetary building on a moon.
        $this->assertSame (loca_lang ('BUILD_ERROR_INVALID_PTYPE', 'en'), CanBuild ($user, $moon, GID_B_METAL_MINE, 6, false, true));
        // No free field (the fixture fills every planet up to maxfields).
        $this->preparePlanet (2, 1000000, 1000000, 1000000);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET fields = 12, maxfields = 12 WHERE planet_id = 2");
        $this->assertSame (loca_lang ('BUILD_ERROR_NO_SPACE', 'en'), CanBuild ($user, GetUpdatePlanet (2, $this->now), GID_B_METAL_STOR, 4, false, true));
        // Above the level cap (checked even when the resources are not).
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $this->assertSame (loca_lang ('BUILD_ERROR_MAX_LEVEL', 'en'), CanBuild ($user, GetUpdatePlanet (2, $this->now), GID_B_METAL_STOR, MAX_BUILDINGS_LEVEL + 1, false, true));
    }

    /**
     * CanBuild rejects foreign planets, vacation mode and a frozen universe;
     * demolition of a building that does not exist is refused too.
     */
    public function testCanBuildRejectsForeignVacationAndDemolitionChecks (): void
    {
        global $GlobalUni;

        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $user = LoadUser (1);
        $planet = GetUpdatePlanet (2, $this->now);

        // Someone else's planet.
        $this->assertSame (loca_lang ('BUILD_ERROR_INVALID_PLANET', 'en'), CanBuild (LoadUser (2), $planet, GID_B_METAL_STOR, 4, false, true));
        // Vacation mode.
        $vacation = $user;
        $vacation['vacation'] = 1;
        $this->assertSame (loca_lang ('BUILD_ERROR_VACATION_MODE', 'en'), CanBuild ($vacation, $planet, GID_B_METAL_STOR, 4, false, true));
        // Demolishing a building the planet does not have (planet 3 has no ally depot).
        $this->assertSame (loca_lang ('BUILD_ERROR_NO_SUCH_BUILDING', 'en'), CanBuild ($user, GetUpdatePlanet (3, $this->now), GID_B_ALLY_DEPOT, 0, true, true));
        // A frozen universe blocks everything.
        $GlobalUni['freeze'] = 1;
        $this->assertSame (loca_lang ('BUILD_ERROR_UNI_FREEZE', 'en'), CanBuild ($user, $planet, GID_B_METAL_STOR, 4, false, true));
    }

    /**
     * CanBuild checks the running research, a busy shipyard and the
     * technology requirements before touching the resources.
     */
    public function testCanBuildRejectsBusyLaboratoryShipyardAndRequirements (): void
    {
        // Planet 1 already has a research task and a shipyard order (fixture),
        // so the laboratory and the shipyard are both "operating".
        $this->preparePlanet (1, 1000000, 1000000, 1000000, 100);
        $user = LoadUser (1);
        $planet = GetUpdatePlanet (1, $this->now);

        $this->assertSame (loca_lang ('BUILD_ERROR_RESEARCH_ACTIVE', 'en'), CanBuild ($user, $planet, GID_B_RES_LAB, 5, false, true));
        $this->assertSame (loca_lang ('BUILD_ERROR_SHIPYARD_ACTIVE', 'en'), CanBuild ($user, $planet, GID_B_SHIPYARD, 4, false, true));

        // Nanites need Robotics 10 and Computer 10; planet 2 has Robotics 1.
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $this->assertSame (loca_lang ('BUILD_ERROR_REQUIREMENTS', 'en'), CanBuild ($user, GetUpdatePlanet (2, $this->now), GID_B_NANITES, 1, false, true));

        // A missing resource stops a build that is not queued yet.
        $this->preparePlanet (2, 0, 0, 0, 50);
        $this->assertSame (loca_lang ('BUILD_ERROR_NO_RES', 'en'), CanBuild ($user, GetUpdatePlanet (2, $this->now), GID_B_METAL_STOR, 4, false));
    }

    /**
     * BuildEnque starts the first slot: it charges the resources, writes the
     * buildqueue row and adds the queue event.
     */
    public function testBuildEnqueStartsTheFirstSlotAndChargesResources (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $user = LoadUser (1);
        $before = $this->planetResources (2);

        $text = BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now);

        $this->assertSame ('', $text);

        $rows = $this->buildQueueRows (2);
        $this->assertCount (1, $rows);
        $row = $rows[0];
        $this->assertSame (1, (int)$row['list_id']);
        $this->assertSame (GID_B_METAL_STOR, (int)$row['tech_id']);
        $this->assertSame (4, (int)$row['level']);          // planet level 3 -> 4
        $this->assertSame (0, (int)$row['destroy']);
        $this->assertSame ($this->now, (int)$row['start']);
        // 16000 metal structure points / (2500 * (1 + robots 1)) * 3600 = 11520 s
        $this->assertSame ($this->now + 11520, (int)$row['end']);

        $after = $this->planetResources (2);
        $cost = TechPrice (GID_B_METAL_STOR, 4);
        $this->assertSame ($before[GID_RC_METAL] - (int)$cost[GID_RC_METAL], $after[GID_RC_METAL]);
        $this->assertSame ($before[GID_RC_CRYSTAL], $after[GID_RC_CRYSTAL]);

        // The first slot also creates the completion event.
        $queue = $this->queueRows ("type = '".QTYP_BUILD."' AND sub_id = " . (int)$row['id']);
        $this->assertCount (1, $queue);
        $this->assertSame (1, (int)$queue[0]['owner_id']);
        $this->assertSame (GID_B_METAL_STOR, (int)$queue[0]['obj_id']);
        $this->assertSame (4, (int)$queue[0]['level']);
        $this->assertSame ($this->now, (int)$queue[0]['start']);
        $this->assertSame (QUEUE_PRIO_BUILD, (int)$queue[0]['prio']);
    }

    /**
     * The second slot is queued without charging resources and without an
     * event of its own (it is started when the first one completes).
     */
    public function testBuildEnqueQueuesASecondSlotWithoutCharging (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $user = LoadUser (1);

        BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now);
        $afterFirst = $this->planetResources (2);

        $text = BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now + 10);
        $this->assertSame ('', $text);

        $rows = $this->buildQueueRows (2);
        $this->assertCount (2, $rows);
        $this->assertSame (2, (int)$rows[1]['list_id']);
        $this->assertSame (5, (int)$rows[1]['level'], 'the level of the queued build must follow the first one');
        $this->assertSame ($this->now + 10, (int)$rows[1]['start']);

        $this->assertSame ($afterFirst, $this->planetResources (2), 'only the first slot is paid for');
        $this->assertSame (1, $this->countQueue ("type = '".QTYP_BUILD."' AND obj_id = ".GID_B_METAL_STOR), 'only the first slot has an event');
    }

    /**
     * Two builds cannot be added in the same second.
     */
    public function testBuildEnqueRejectsASecondBuildInTheSameSecond (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $user = LoadUser (1);

        $this->assertSame ('', BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now));
        $this->assertSame ('', BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now));

        $this->assertSame (1, $this->countBuildQueue (2));
    }

    /**
     * Without the Commander officer the build queue holds a single order.
     */
    public function testBuildEnqueRespectsTheConstructionQueueLimit (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."users SET com_until = 0 WHERE player_id = 1");
        InvalidateUserCache ();
        $user = LoadUser (1);

        $this->assertSame ('', BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now));
        $this->assertSame ('', BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now + 10));

        $this->assertSame (1, $this->countBuildQueue (2), 'a non-commander queue holds exactly one order');
    }

    /**
     * BuildEnque refuses a build the planet cannot pay for and ignores an
     * unknown planet and a frozen universe.
     */
    public function testBuildEnqueRejectsUnpayableUnknownAndFrozenBuilds (): void
    {
        global $GlobalUni;

        $this->preparePlanet (2, 0, 0, 0, 50);
        $user = LoadUser (1);

        $this->assertSame (loca_lang ('BUILD_ERROR_NO_RES', 'en'), BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now));
        $this->assertSame (0, $this->countBuildQueue (2));

        // An unknown planet is silently ignored.
        $this->assertSame ('', BuildEnque ($user, 999999, GID_B_METAL_STOR, 0, $this->now));
        $this->assertSame (0, $this->countBuildQueue (2));

        // A frozen universe does not accept orders (and reports no error).
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $GlobalUni['freeze'] = 1;
        $this->assertSame ('', BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now));
        $this->assertSame (0, $this->countBuildQueue (2));
    }

    /**
     * BuildDeque cancels the running construction, refunds the resources and
     * removes both the event and the buildqueue row.
     */
    public function testBuildDequeCancelsTheFirstSlotAndRefunds (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $user = LoadUser (1);
        BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now);
        $afterBuild = $this->planetResources (2);
        $cost = TechPrice (GID_B_METAL_STOR, 4);

        $text = BuildDeque ($user, 2, 1);

        $this->assertSame ('', $text);
        $this->assertSame (0, $this->countBuildQueue (2));
        $this->assertSame (0, $this->countQueue ("(type = '".QTYP_BUILD."' OR type = '".QTYP_DEMOLISH."') AND owner_id = 1 AND obj_id = ".GID_B_METAL_STOR));

        $afterDeque = $this->planetResources (2);
        $this->assertSame ($afterBuild[GID_RC_METAL] + (int)$cost[GID_RC_METAL], $afterDeque[GID_RC_METAL]);
    }

    /**
     * BuildDeque refuses to touch a planet that is not owned by the caller.
     */
    public function testBuildDequeRejectsAForeignPlanet (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        BuildEnque (LoadUser (1), 2, GID_B_METAL_STOR, 0, $this->now);

        $text = BuildDeque (LoadUser (2), 2, 1);

        $this->assertSame (loca_lang ('BUILD_ERROR_INVALID_PLANET', 'en'), $text);
        $this->assertSame (1, $this->countBuildQueue (2), 'a foreign player must not cancel the build');
    }

    /**
     * A queued slot without its own event is cancelled without a refund.
     */
    public function testBuildDequeCancelsASlotWithoutAnEventWithoutRefund (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $user = LoadUser (1);
        BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now);
        BuildEnque ($user, 2, GID_B_METAL_STOR, 0, $this->now + 10);
        $before = $this->planetResources (2);

        $this->assertSame ('', BuildDeque ($user, 2, 2));

        $this->assertSame ($before, $this->planetResources (2), 'the second slot was never paid for, so nothing is refunded');
        $rows = $this->buildQueueRows (2);
        $this->assertCount (1, $rows);
        $this->assertSame (1, (int)$rows[0]['list_id']);
    }

    /**
     * BUG: PropagateBuildQueue never starts the queued construction.
     *
     * It loads the planet with LoadPlanetById(), whose row carries no virtual
     * energy value (GID_RC_ENERGY). TechPrice() lists every resource of
     * $resourcemap (with 0 for the ones the building does not cost), so
     * IsEnoughResources() hits its "unknown resource type that neither the
     * player nor the planet has" branch for the zero-cost energy entry and
     * answers false - even though the very same order is accepted when the
     * planet row comes from GetUpdatePlanet() (which sets GID_RC_ENERGY).
     *
     * This test documents the current behaviour: the queued construction is
     * deleted, no event is created, no resources are written off and the owner
     * gets a "not enough resources" system message although the planet has
     * millions. (BotCoreTest documents the same root cause for BotBuild.)
     */
    public function testPropagateBuildQueueDropsTheNextBuildBecauseOfTheRawPlanetRow (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $bq = $this->addBuildQueueRow (1, 2, 1, GID_B_METAL_STOR, 4, 0, $this->now, $this->now + 10);
        $before = $this->planetResources (2);
        $messagesBefore = $this->countMessages (1);
        $from = $this->now + 60;

        // The two planet representations disagree about the energy resource.
        $raw = LoadPlanetById (2);
        $updated = GetUpdatePlanet (2, $this->now);
        $cost = TechPrice (GID_B_METAL_STOR, 4);
        $this->assertArrayNotHasKey (GID_RC_ENERGY, $raw);
        $this->assertArrayHasKey (GID_RC_ENERGY, $updated);
        $this->assertFalse (IsEnoughResources (LoadUser (1), $raw, $cost));
        $this->assertTrue (IsEnoughResources (LoadUser (1), $updated, $cost));
        $this->assertSame (loca_lang ('BUILD_ERROR_NO_RES', 'en'), CanBuild (LoadUser (1), $raw, GID_B_METAL_STOR, 4, false));
        $this->assertSame ('', CanBuild (LoadUser (1), $updated, GID_B_METAL_STOR, 4, false));

        PropagateBuildQueue (2, $from);

        // Current behaviour: the build is dropped instead of started.
        $this->assertSame (0, $this->countBuildQueue (2));
        $this->assertSame (0, $this->countQueue ("type = '".QTYP_BUILD."' AND sub_id = $bq"));
        $this->assertSame ($before, $this->planetResources (2), 'the dropped build must not charge any resources');
        $this->assertSame ($messagesBefore + 1, $this->countMessages (1));

        $msg = dbarray (dbquery ("SELECT * FROM ".$this->dbPrefix."messages WHERE owner_id = 1 ORDER BY msg_id DESC LIMIT 1"));
        $this->assertSame (loca_lang ('BUILD_MSG_SUBJ', 'en'), $msg['subj']);
        $this->assertStringContainsString (loca_lang ('BUILD_ERROR_NO_RES', 'en'), $msg['text']);
    }

    /**
     * End-to-end consequence of the bug above: when the first construction of
     * a two-slot build queue completes, the queued second slot is dropped
     * instead of started, even though the planet can easily pay for it.
     */
    public function testQueueBuildEndDropsTheQueuedSecondSlot (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_METAL_STOR."` = 3, `".GID_B_CRYS_MINE."` = 2, fields = 12 WHERE planet_id = 2");
        $this->allowStats (1);

        $first = $this->addBuildQueueRow (1, 2, 1, GID_B_METAL_STOR, 4, 0, $this->now - 600, $this->now);
        $second = $this->addBuildQueueRow (1, 2, 2, GID_B_CRYS_MINE, 3, 0, $this->now, $this->now + 600);
        $task = AddQueue (1, QTYP_BUILD, $first, GID_B_METAL_STOR, 4, $this->now - 600, 600, QUEUE_PRIO_BUILD);

        Queue_Build_End (LoadQueue ($task));

        // The first building is completed ...
        $this->assertSame (4, (int)$this->planetField (2, GID_B_METAL_STOR));
        // ... but the second slot is gone instead of being started.
        $this->assertSame (0, $this->countBuildQueue (2));
        $this->assertSame (0, $this->countQueue ("type = '".QTYP_BUILD."' AND sub_id = $second"));
        $this->assertSame (2, (int)$this->planetField (2, GID_B_CRYS_MINE), 'the second building was never built');
    }

    /**
     * Queue_Build_End completes a construction: the level and the field count
     * are updated, the rows are deleted and the score grows.
     */
    public function testQueueBuildEndCompletesAConstruction (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_METAL_STOR."` = 3, fields = 12 WHERE planet_id = 2");
        $this->allowStats (1);
        $scoreBefore = (int)$this->userField (1, 'score1');

        $bq = $this->addBuildQueueRow (1, 2, 1, GID_B_METAL_STOR, 4, 0, $this->now - 600, $this->now);
        $task = AddQueue (1, QTYP_BUILD, $bq, GID_B_METAL_STOR, 4, $this->now - 600, 600, QUEUE_PRIO_BUILD);

        Queue_Build_End (LoadQueue ($task));

        $this->assertSame (4, (int)$this->planetField (2, GID_B_METAL_STOR));
        $this->assertSame (13, (int)$this->planetField (2, 'fields'));
        $this->assertFalse ($this->taskRow ($task));
        $this->assertSame (0, $this->countBuildQueue (2));

        $points = TechPriceInPoints (TechPrice (GID_B_METAL_STOR, 4));
        $this->assertSame ($scoreBefore + $points, (int)$this->userField (1, 'score1'));
    }

    /**
     * Queue_Build_End completes a demolition: the level drops, a field is
     * freed and the points are subtracted again.
     */
    public function testQueueBuildEndCompletesADemolition (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_METAL_STOR."` = 5, fields = 20 WHERE planet_id = 2");
        $this->allowStats (1);
        $scoreBefore = (int)$this->userField (1, 'score1');

        $bq = $this->addBuildQueueRow (1, 2, 1, GID_B_METAL_STOR, 4, 1, $this->now - 600, $this->now);
        $task = AddQueue (1, QTYP_DEMOLISH, $bq, GID_B_METAL_STOR, 4, $this->now - 600, 600, QUEUE_PRIO_BUILD);

        Queue_Build_End (LoadQueue ($task));

        $this->assertSame (4, (int)$this->planetField (2, GID_B_METAL_STOR));
        $this->assertSame (19, (int)$this->planetField (2, 'fields'), 'a demolition frees a field');

        $points = TechPriceInPoints (TechPrice (GID_B_METAL_STOR, 5));
        $this->assertSame ($scoreBefore - $points, (int)$this->userField (1, 'score1'));
    }

    /**
     * Queue_Build_End only drops the event when the buildqueue row is gone.
     */
    public function testQueueBuildEndDropsATaskWithoutABuildQueueRow (): void
    {
        $fields = (int)$this->planetField (2, 'fields');
        $task = AddQueue (1, QTYP_BUILD, 987654, GID_B_METAL_MINE, 6, 1000, 100, QUEUE_PRIO_BUILD);

        Queue_Build_End (LoadQueue ($task));

        $this->assertFalse ($this->taskRow ($task));
        $this->assertSame ($fields, (int)$this->planetField (2, 'fields'));
    }

    /**
     * The foolproofing branch: a build whose target level was already reached
     * is dropped without touching the planet.
     */
    public function testQueueBuildEndFoolproofsAnAlreadyReachedLevel (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_METAL_STOR."` = 5, fields = 12 WHERE planet_id = 2");
        $this->allowStats (1);
        $scoreBefore = (int)$this->userField (1, 'score1');

        $bq = $this->addBuildQueueRow (1, 2, 1, GID_B_METAL_STOR, 4, 0, $this->now - 600, $this->now);
        $task = AddQueue (1, QTYP_BUILD, $bq, GID_B_METAL_STOR, 4, $this->now - 600, 600, QUEUE_PRIO_BUILD);

        Queue_Build_End (LoadQueue ($task));

        $this->assertFalse ($this->taskRow ($task));
        $this->assertSame (0, $this->countBuildQueue (2));
        $this->assertSame (5, (int)$this->planetField (2, GID_B_METAL_STOR));
        $this->assertSame (12, (int)$this->planetField (2, 'fields'), 'the foolproof branch must not add a field');
        $this->assertSame ($scoreBefore, (int)$this->userField (1, 'score1'), 'the foolproof branch must not change the score');
    }

    // ========================================================================
    // Shipyard queue
    // ========================================================================

    /**
     * GetShipyardQueue only lists the shipyard tasks of the given planet.
     */
    public function testGetShipyardQueueFiltersByPlanetAndType (): void
    {
        $this->assertSame (1, dbrows (GetShipyardQueue (1)));
        $this->assertSame (0, dbrows (GetShipyardQueue (2)));

        $row = dbarray (GetShipyardQueue (1));
        $this->assertSame (QTYP_SHIPYARD, $row['type']);
        $this->assertSame (1, (int)$row['sub_id']);
        $this->assertSame (GID_F_LF, (int)$row['obj_id']);
        $this->assertSame (5, (int)$row['level']);
    }

    /**
     * ShipyardLatestTime returns the injected "now" when the shipyard is idle
     * and the completion time of the last unit otherwise.
     */
    public function testShipyardLatestTimeReturnsTheCompletionOfTheLastUnit (): void
    {
        $this->assertSame (12345, ShipyardLatestTime (2, 12345));

        // start 1000, end 1100, 5 units of 100 s each -> the last one finishes
        // at 1100 + 100 * (5 - 1) = 1500.
        AddQueue (1, QTYP_SHIPYARD, 2, GID_F_LF, 5, 1000, 100, QUEUE_PRIO_BUILD);
        $this->assertSame (1500, ShipyardLatestTime (2, 999999));

        // The last task is the one with the biggest end time.
        AddQueue (1, QTYP_SHIPYARD, 2, GID_F_LF, 2, 2000, 300, QUEUE_PRIO_BUILD);
        $this->assertSame (2000 + 300 + 300 * (2 - 1), ShipyardLatestTime (2, 999999));
    }

    /**
     * AddShipyard charges the resources and appends the order to the queue.
     */
    public function testAddShipyardAddsAnOrderAndChargesResources (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $before = $this->planetResources (2);

        $ok = AddShipyard (1, 2, GID_F_LF, 2, $this->now);

        $this->assertTrue ($ok);

        $result = GetShipyardQueue (2);
        $this->assertSame (1, dbrows ($result));
        $row = dbarray ($result);
        $this->assertSame (1, (int)$row['owner_id']);
        $this->assertSame (2, (int)$row['sub_id']);
        $this->assertSame (GID_F_LF, (int)$row['obj_id']);
        $this->assertSame (2, (int)$row['level']);
        $this->assertSame ($this->now, (int)$row['start']);
        // (3000 + 1000) / (2500 * (1 + shipyard 2)) * 3600 = 1920 s per unit.
        $this->assertSame ($this->now + 1920, (int)$row['end']);
        $this->assertSame (QUEUE_PRIO_LOWEST, (int)$row['prio']);

        $after = $this->planetResources (2);
        $this->assertSame ($before[GID_RC_METAL] - 2 * 3000, $after[GID_RC_METAL]);
        $this->assertSame ($before[GID_RC_CRYSTAL] - 2 * 1000, $after[GID_RC_CRYSTAL]);

        // The next order starts after the last unit of the current one.
        $this->assertSame ($this->now + 1920 + 1920, ShipyardLatestTime (2, $this->now));
    }

    /**
     * Unknown unit types are rejected.
     */
    public function testAddShipyardRejectsUnknownUnitTypes (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);

        $this->assertFalse (AddShipyard (1, 2, 4242, 1, $this->now));
        $this->assertSame (0, $this->countQueue ("type = '".QTYP_SHIPYARD."' AND sub_id = 2"));
    }

    /**
     * A shipyard or a nanite factory under construction blocks the shipyard,
     * and so does a frozen universe.
     */
    public function testAddShipyardRejectsBusyShipyardAndFrozenUniverse (): void
    {
        global $GlobalUni;

        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        $this->addBuildQueueRow (1, 2, 1, GID_B_NANITES, 1, 0, $this->now, $this->now + 100);

        $this->assertFalse (AddShipyard (1, 2, GID_F_LF, 1, $this->now));

        dbquery ("DELETE FROM ".$this->dbPrefix."buildqueue WHERE planet_id = 2");
        $GlobalUni['freeze'] = 1;
        $this->assertFalse (AddShipyard (1, 2, GID_F_LF, 1, $this->now));
        $this->assertSame (0, $this->countQueue ("type = '".QTYP_SHIPYARD."' AND sub_id = 2"));
    }

    /**
     * Missile orders are capped by the free silo capacity, including the
     * missiles that are already under construction.
     */
    public function testAddShipyardLimitsMissilesToTheSiloCapacity (): void
    {
        // Planet 2 has a missile silo of level 2 -> 20 slots for ABMs.
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_MISS_SILO."` = 2 WHERE planet_id = 2");

        $this->assertTrue (AddShipyard (1, 2, GID_D_ABM, 50, $this->now));
        $row = dbarray (GetShipyardQueue (2));
        $this->assertSame (20, (int)$row['level'], 'the order must be capped at the silo capacity');

        // The silo is now full: another ABM order is refused.
        $this->assertFalse (AddShipyard (1, 2, GID_D_ABM, 5, $this->now + 10));

        // With a level 4 silo (40 slots) an IPM takes two slots.
        dbquery ("DELETE FROM ".$this->dbPrefix."queue WHERE type = '".QTYP_SHIPYARD."'");
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_MISS_SILO."` = 4 WHERE planet_id = 2");
        $this->assertTrue (AddShipyard (1, 2, GID_D_IPM, 50, $this->now));
        $row = dbarray (GetShipyardQueue (2));
        $this->assertSame (20, (int)$row['level'], 'floor(40 / 2) = 20 missiles fit into the silo');
    }

    /**
     * Only one shield dome may exist and only one may be under construction.
     */
    public function testAddShipyardBuildsOnlyOneShieldDome (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);

        // Asking for 5 domes still builds exactly one.
        $this->assertTrue (AddShipyard (1, 2, GID_D_SDOME, 5, $this->now));
        $row = dbarray (GetShipyardQueue (2));
        $this->assertSame (1, (int)$row['level']);

        // A second dome of the same type is refused while the first one runs.
        $this->assertFalse (AddShipyard (1, 2, GID_D_SDOME, 1, $this->now + 10));

        // A dome that already stands on the planet is not built again.
        dbquery ("DELETE FROM ".$this->dbPrefix."queue WHERE type = '".QTYP_SHIPYARD."'");
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_D_SDOME."` = 1 WHERE planet_id = 2");
        $this->assertFalse (AddShipyard (1, 2, GID_D_SDOME, 1, $this->now + 20));
    }

    /**
     * Queue_Shipyard_End builds the units that are due and keeps the remaining
     * ones in the queue.
     */
    public function testQueueShipyardEndBuildsTheDueUnits (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_F_LF."` = 5 WHERE planet_id = 2");
        $this->allowStats (1);
        $scoreBefore = (int)$this->userField (1, 'score1');
        $fleetScoreBefore = (int)$this->userField (1, 'score2');

        // 5 units, one unit every 100 seconds.
        $task = AddQueue (1, QTYP_SHIPYARD, 2, GID_F_LF, 5, 1000, 100, QUEUE_PRIO_BUILD);

        Queue_Shipyard_End (LoadQueue ($task), 1100);       // exactly one unit is due

        $this->assertSame (6, (int)$this->planetField (2, GID_F_LF));
        $row = $this->taskRow ($task);
        $this->assertIsArray ($row);
        $this->assertSame (4, (int)$row['level']);
        $this->assertSame (1100, (int)$row['start']);
        $this->assertSame (1200, (int)$row['end']);

        $points = TechPriceInPoints (TechPrice (GID_F_LF, 1));
        $this->assertSame ($scoreBefore + $points, (int)$this->userField (1, 'score1'));
        $this->assertSame ($fleetScoreBefore + 1, (int)$this->userField (1, 'score2'));
    }

    /**
     * Queue_Shipyard_End removes the task when every unit is built.
     */
    public function testQueueShipyardEndRemovesTheTaskWhenAllUnitsAreBuilt (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_F_LF."` = 5 WHERE planet_id = 2");
        $this->allowStats (1);

        $task = AddQueue (1, QTYP_SHIPYARD, 2, GID_F_LF, 5, 1000, 100, QUEUE_PRIO_BUILD);

        Queue_Shipyard_End (LoadQueue ($task), 5000);       // all 5 units are due

        $this->assertSame (10, (int)$this->planetField (2, GID_F_LF));
        $this->assertFalse ($this->taskRow ($task));
    }

    /**
     * Before the first unit is due, Queue_Shipyard_End builds nothing.
     */
    public function testQueueShipyardEndBuildsNothingBeforeTheFirstUnitIsDue (): void
    {
        $this->preparePlanet (2, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_F_LF."` = 5 WHERE planet_id = 2");
        $this->allowStats (1);

        $task = AddQueue (1, QTYP_SHIPYARD, 2, GID_F_LF, 5, 1000, 100, QUEUE_PRIO_BUILD);

        Queue_Shipyard_End (LoadQueue ($task), 1050);       // only half an interval

        $this->assertSame (5, (int)$this->planetField (2, GID_F_LF));
        $row = $this->taskRow ($task);
        $this->assertIsArray ($row);
        $this->assertSame (5, (int)$row['level']);
    }

    // ========================================================================
    // Research queue
    // ========================================================================

    /**
     * GetResearchQueue only lists the research tasks of the given owner.
     */
    public function testGetResearchQueueFiltersByOwnerAndType (): void
    {
        $this->assertSame (1, dbrows (GetResearchQueue (1)));
        $this->assertSame (0, dbrows (GetResearchQueue (2)));

        $row = dbarray (GetResearchQueue (1));
        $this->assertSame (QTYP_RESEARCH, $row['type']);
        $this->assertSame (GID_R_ENERGY, (int)$row['obj_id']);
        $this->assertSame (6, (int)$row['level']);
    }

    /**
     * CanResearch returns an empty string when the research may start.
     */
    public function testCanResearchAcceptsAPossibleResearch (): void
    {
        $this->preparePlanet (4, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_RES_LAB."` = 4 WHERE planet_id = 4");
        InvalidateUserCache ();

        $user = LoadUser (2);
        $planet = GetUpdatePlanet (4, $this->now);

        $this->assertSame ('', CanResearch ($user, $planet, GID_R_ENERGY, 5));
    }

    /**
     * A running research blocks a new one; a laboratory under construction on
     * any planet of the player blocks it too.
     */
    public function testCanResearchRejectsRunningResearchAndBusyLaboratory (): void
    {
        $this->preparePlanet (1, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_RES_LAB."` = 4 WHERE planet_id = 1");

        // Player 1 has a research task from the fixture.
        $this->assertSame (loca_lang ('BUILD_ERROR_RESEARCH_ALREADY', 'en'), CanResearch (LoadUser (1), GetUpdatePlanet (1, $this->now), GID_R_ENERGY, 7));

        // Player 2 has none, but upgrades his laboratory.
        AddQueue (2, QTYP_BUILD, 0, GID_B_RES_LAB, 5, 1000, 100, QUEUE_PRIO_BUILD);
        $this->assertSame (loca_lang ('BUILD_ERROR_RESEARCH_LAB_BUILDING', 'en'), CanResearch (LoadUser (2), GetUpdatePlanet (4, $this->now), GID_R_ENERGY, 5));
    }

    /**
     * CanResearch rejects non-research ids, vacation mode and a foreign planet.
     */
    public function testCanResearchRejectsInvalidVacationAndForeignTargets (): void
    {
        $user = LoadUser (2);
        $planet = GetUpdatePlanet (4, $this->now);

        $this->assertSame (loca_lang ('BUILD_ERROR_INVALID_ID', 'en'), CanResearch ($user, $planet, GID_B_METAL_MINE, 1));

        $vacation = $user;
        $vacation['vacation'] = 1;
        $this->assertSame (loca_lang ('BUILD_ERROR_RESEARCH_VACATION', 'en'), CanResearch ($vacation, $planet, GID_R_ENERGY, 5));

        $this->assertSame (loca_lang ('BUILD_ERROR_INVALID_PLANET', 'en'), CanResearch ($user, GetUpdatePlanet (1, $this->now), GID_R_ENERGY, 5));
    }

    /**
     * CanResearch checks the resources and the technology requirements.
     */
    public function testCanResearchRejectsMissingResourcesAndRequirements (): void
    {
        // No resources and no laboratory at all.
        $this->preparePlanet (4, 0, 0, 0, 50);
        InvalidateUserCache ();
        $this->assertSame (loca_lang ('BUILD_ERROR_NO_RES', 'en'), CanResearch (LoadUser (2), GetUpdatePlanet (4, $this->now), GID_R_ENERGY, 5));

        // Enough resources, but the shield technology needs a level 6 lab.
        $this->preparePlanet (4, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_RES_LAB."` = 4 WHERE planet_id = 4");
        $this->assertSame (loca_lang ('BUILD_ERROR_REQUIREMENTS', 'en'), CanResearch (LoadUser (2), GetUpdatePlanet (4, $this->now), GID_R_SHIELD, 4));
    }

    /**
     * StartResearch charges the resources and adds the research task.
     */
    public function testStartResearchAddsTheTaskAndChargesResources (): void
    {
        $this->preparePlanet (4, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_RES_LAB."` = 4 WHERE planet_id = 4");
        // Without the Technocrat officer the base research speed applies.
        dbquery ("UPDATE ".$this->dbPrefix."users SET tec_until = 0 WHERE player_id = 2");
        InvalidateUserCache ();
        $before = $this->planetResources (4);

        $text = StartResearch (2, 4, GID_R_ENERGY, $this->now);

        $this->assertSame ('', $text);

        $result = GetResearchQueue (2);
        $this->assertSame (1, dbrows ($result));
        $row = dbarray ($result);
        $this->assertSame (2, (int)$row['owner_id']);
        $this->assertSame (4, (int)$row['sub_id']);
        $this->assertSame (GID_R_ENERGY, (int)$row['obj_id']);
        $this->assertSame (5, (int)$row['level']);          // player level 4 -> 5
        $this->assertSame ($this->now, (int)$row['start']);
        // TechDuration uses the metal + crystal structure: (0 + 12800) /
        // (1000 * (1 + lab 4)) * 3600 = 9216 s.
        $this->assertSame ($this->now + 9216, (int)$row['end']);

        $after = $this->planetResources (4);
        $cost = TechPrice (GID_R_ENERGY, 5);
        $this->assertSame ($before[GID_RC_CRYSTAL] - (int)$cost[GID_RC_CRYSTAL], $after[GID_RC_CRYSTAL]);
        $this->assertSame ($before[GID_RC_DEUTERIUM] - (int)$cost[GID_RC_DEUTERIUM], $after[GID_RC_DEUTERIUM]);
    }

    /**
     * The Technocrat officer shortens the research time.
     */
    public function testStartResearchIsFasterWithTheTechnocratOfficer (): void
    {
        $this->preparePlanet (4, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_RES_LAB."` = 4 WHERE planet_id = 4");
        InvalidateUserCache ();

        // The fixture grants every player the Technocrat officer.
        $this->assertTrue (PremiumStatus (LoadUser (2))['technocrat']);

        $without = TechDuration (GID_R_ENERGY, 5, PROD_RESEARCH_DURATION_FACTOR, 4, 0, 1.0);
        $with = TechDuration (GID_R_ENERGY, 5, PROD_RESEARCH_DURATION_FACTOR, 4, 0, 1.0 * 1.1);
        $this->assertLessThan ($without, $with);

        $this->assertSame ('', StartResearch (2, 4, GID_R_ENERGY, $this->now));
        $row = dbarray (GetResearchQueue (2));
        $this->assertSame ($this->now + $with, (int)$row['end']);
    }

    /**
     * StartResearch reports a running research instead of queueing a second one.
     */
    public function testStartResearchFailsWhileResearchIsRunning (): void
    {
        $text = StartResearch (1, 1, GID_R_ENERGY, $this->now);

        $this->assertSame (loca_lang ('BUILD_ERROR_RESEARCH_ALREADY', 'en'), $text);
        $this->assertSame (1, dbrows (GetResearchQueue (1)));
    }

    /**
     * StopResearch refunds the research cost and removes the task.
     */
    public function testStopResearchRefundsAndRemovesTheTask (): void
    {
        $this->preparePlanet (4, 1000000, 1000000, 1000000, 50);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET `".GID_B_RES_LAB."` = 4 WHERE planet_id = 4");
        dbquery ("UPDATE ".$this->dbPrefix."users SET tec_until = 0 WHERE player_id = 2");
        InvalidateUserCache ();

        StartResearch (2, 4, GID_R_ENERGY, $this->now);
        $afterStart = $this->planetResources (4);
        $cost = TechPrice (GID_R_ENERGY, 5);

        StopResearch (2);

        $this->assertSame (0, dbrows (GetResearchQueue (2)));
        $afterStop = $this->planetResources (4);
        $this->assertSame ($afterStart[GID_RC_CRYSTAL] + (int)$cost[GID_RC_CRYSTAL], $afterStop[GID_RC_CRYSTAL]);
        $this->assertSame ($afterStart[GID_RC_DEUTERIUM] + (int)$cost[GID_RC_DEUTERIUM], $afterStop[GID_RC_DEUTERIUM]);
    }

    /**
     * Queue_Research_End stores the new level, removes the task and adds the
     * research points.
     */
    public function testQueueResearchEndCompletesTheResearch (): void
    {
        $this->preparePlanet (4, 1000000, 1000000, 1000000, 50);
        $this->allowStats (2);
        $scoreBefore = (int)$this->userField (2, 'score1');
        $researchBefore = (int)$this->userField (2, 'score3');

        $task = AddQueue (2, QTYP_RESEARCH, 4, GID_R_ENERGY, 6, $this->now - 600, 600, QUEUE_PRIO_BUILD);

        Queue_Research_End (LoadQueue ($task));

        $this->assertSame (6, (int)$this->userField (2, GID_R_ENERGY));
        $this->assertFalse ($this->taskRow ($task));

        // The research cost goes into the total score (score1), the research
        // level count into score3.
        $points = TechPriceInPoints (TechPrice (GID_R_ENERGY, 6));
        $this->assertSame ($scoreBefore + $points, (int)$this->userField (2, 'score1'));
        $this->assertSame ($researchBefore + 1, (int)$this->userField (2, 'score3'));
    }

    /**
     * When the completed research belongs to the current user, the cached
     * $GlobalUser is refreshed.
     */
    public function testQueueResearchEndRefreshesTheCachedUser (): void
    {
        $this->preparePlanet (4, 1000000, 1000000, 1000000, 50);
        $this->allowStats (2);
        $GLOBALS['GlobalUser'] = LoadUser (2);          // energy level 4

        $task = AddQueue (2, QTYP_RESEARCH, 4, GID_R_ENERGY, 7, $this->now - 600, 600, QUEUE_PRIO_BUILD);

        Queue_Research_End (LoadQueue ($task));

        $this->assertSame (7, (int)$GLOBALS['GlobalUser'][GID_R_ENERGY]);
        $this->assertSame (7, (int)$this->userField (2, GID_R_ENERGY));
    }

    // ========================================================================
    // Player events
    // ========================================================================

    /**
     * AddRecalcPointsEvent only adds one event per player.
     */
    public function testAddRecalcPointsEventIsIdempotent (): void
    {
        AddRecalcPointsEvent (7);
        AddRecalcPointsEvent (7);

        $rows = $this->queueRows ("type = '".QTYP_RECALC_POINTS."' AND owner_id = 7");
        $this->assertCount (1, $rows);
        $this->assertSame (7, (int)$rows[0]['owner_id']);
        $this->assertSame (QUEUE_PRIO_RECALC_POINTS, (int)$rows[0]['prio']);
        $this->assertSame (0, (int)$rows[0]['sub_id']);

        // The event points at 00:10 of the next day, i.e. less than a day ahead.
        $delta = (int)$rows[0]['end'] - (int)$rows[0]['start'];
        $this->assertGreaterThan (0, $delta);
        $this->assertLessThanOrEqual (25 * 3600, $delta);
    }

    /**
     * Queue_RecalcPoints_End recalcs the score of the owner and removes the task.
     */
    public function testQueueRecalcPointsEndRecalculatesTheScore (): void
    {
        $this->allowStats (1);
        dbquery ("UPDATE ".$this->dbPrefix."users SET score1 = 1000000000 WHERE player_id = 1");

        $task = AddQueue (1, QTYP_RECALC_POINTS, 0, 0, 0, 1000, 0, QUEUE_PRIO_RECALC_POINTS);

        Queue_RecalcPoints_End (LoadQueue ($task));

        $this->assertFalse ($this->taskRow ($task));

        $score = (int)$this->userField (1, 'score1');
        $this->assertGreaterThan (0, $score);
        $this->assertLessThan (1000000000, $score, 'the score must have been recalculated from the real assets');

        // Recalculating again gives the same number (the score is derived state).
        RecalcStats (1);
        $this->assertSame ($score, (int)$this->userField (1, 'score1'));
    }

    /**
     * Vacation mode is blocked by fleet/build/research/shipyard tasks, but not
     * by events such as a debug task.
     */
    public function testCanEnableVacationIgnoresNonBlockingTasks (): void
    {
        // Player 1 has fleet, build, research and shipyard tasks.
        $this->assertFalse (CanEnableVacation (1));

        dbquery ("DELETE FROM ".$this->dbPrefix."queue WHERE owner_id = 3");
        $this->assertTrue (CanEnableVacation (3));

        AddQueue (3, QTYP_DEBUG, 0, 0, 0, 1000, 0, QUEUE_PRIO_DEBUG);
        $this->assertTrue (CanEnableVacation (3), 'a debug event must not block vacation mode');

        AddQueue (3, QTYP_SHIPYARD, 3, GID_F_LF, 1, 1000, 100, QUEUE_PRIO_BUILD);
        $this->assertFalse (CanEnableVacation (3));
    }

    /**
     * AddAllowNameEvent adds one event and marks the account.
     */
    public function testAddAllowNameEventIsIdempotent (): void
    {
        AddAllowNameEvent (3);
        AddAllowNameEvent (3);

        $rows = $this->queueRows ("type = '".QTYP_ALLOW_NAME."' AND owner_id = 3");
        $this->assertCount (1, $rows);
        $this->assertSame (QUEUE_PRIO_LOWEST, (int)$rows[0]['prio']);
        $this->assertSame (7 * 24 * 3600, (int)$rows[0]['end'] - (int)$rows[0]['start']);

        $this->assertSame (1, (int)$this->userField (3, 'name_changed'));
        $this->assertSame ((int)$rows[0]['start'] + 7 * 24 * 3600, (int)$this->userField (3, 'name_until'));
    }

    /**
     * CanChangeName reflects the pending event; the handler resets the flag.
     */
    public function testCanChangeNameAndQueueAllowNameEnd (): void
    {
        $this->assertTrue (CanChangeName (3));

        AddAllowNameEvent (3);
        $this->assertFalse (CanChangeName (3));
        $row = $this->queueRows ("type = '".QTYP_ALLOW_NAME."' AND owner_id = 3")[0];

        Queue_AllowName_End ($row);

        $this->assertTrue (CanChangeName (3));
        $this->assertSame (0, (int)$this->userField (3, 'name_changed'));
        $this->assertSame (0, $this->countQueue ("type = '".QTYP_ALLOW_NAME."' AND owner_id = 3"));
    }

    /**
     * Queue_UnbanPlayer_End lifts the ban.
     */
    public function testQueueUnbanPlayerEndClearsTheBan (): void
    {
        dbquery ("UPDATE ".$this->dbPrefix."users SET banned = 1, banned_until = 12345 WHERE player_id = 3");
        $task = AddQueue (3, QTYP_UNBAN, 0, 0, 0, 1000, 0);

        Queue_UnbanPlayer_End (LoadQueue ($task));

        $this->assertSame (0, (int)$this->userField (3, 'banned'));
        $this->assertSame (0, (int)$this->userField (3, 'banned_until'));
        $this->assertFalse ($this->taskRow ($task));
    }

    /**
     * Queue_AllowAttacks_End lifts the attack ban.
     */
    public function testQueueAllowAttacksEndClearsTheAttackBan (): void
    {
        dbquery ("UPDATE ".$this->dbPrefix."users SET noattack = 1, noattack_until = 54321 WHERE player_id = 3");
        $task = AddQueue (3, QTYP_ALLOW_ATTACKS, 0, 0, 0, 1000, 0);

        Queue_AllowAttacks_End (LoadQueue ($task));

        $this->assertSame (0, (int)$this->userField (3, 'noattack'));
        $this->assertSame (0, (int)$this->userField (3, 'noattack_until'));
        $this->assertFalse ($this->taskRow ($task));
    }

    /**
     * AddChangeEmailEvent replaces a pending event and returns the new id.
     */
    public function testAddChangeEmailEventReplacesThePreviousTask (): void
    {
        $first = AddChangeEmailEvent (3);
        $second = AddChangeEmailEvent (3);

        $this->assertNotSame ($first, $second);

        $rows = $this->queueRows ("type = '".QTYP_CHANGE_EMAIL."' AND owner_id = 3");
        $this->assertCount (1, $rows);
        $this->assertSame ($second, (int)$rows[0]['task_id']);
        $this->assertSame (7 * 24 * 3600, (int)$rows[0]['end'] - (int)$rows[0]['start']);

        // The handler copies the current address into the permanent one.
        dbquery ("UPDATE ".$this->dbPrefix."users SET email = 'new@example.com', pemail = 'old@example.com' WHERE player_id = 3");
        Queue_ChangeEmail_End (LoadQueue ($second));

        $this->assertSame ('new@example.com', $this->userField (3, 'pemail'));
        $this->assertFalse ($this->taskRow ($second));
    }

    // ========================================================================
    // Universe events
    // ========================================================================

    /**
     * AddUpdateStatsEvent schedules the next slot of the day (8:05, 16:05,
     * 20:05) and never schedules it twice.
     */
    public function testAddUpdateStatsEventSchedulesTheNextSlot (): void
    {
        // A task scheduled between 8:00 and 16:00 runs at 16:05 the same day.
        $nine = mktime (9, 0, 0);
        AddUpdateStatsEvent ($nine);
        $rows = $this->queueRows ("type = '".QTYP_UPDATE_STATS."'");
        $this->assertCount (1, $rows);
        $this->assertSame (USER_SPACE, (int)$rows[0]['owner_id']);
        $this->assertSame (QUEUE_PRIO_UPDATE_STATS, (int)$rows[0]['prio']);
        $this->assertSame ($nine, (int)$rows[0]['start']);
        $this->assertSame (mktime (16, 5, 0), (int)$rows[0]['end']);

        AddUpdateStatsEvent ($nine);
        $this->assertSame (1, $this->countQueue ("type = '".QTYP_UPDATE_STATS."'"));

        // Between 16:00 and 20:00 the next slot is 20:05.
        dbquery ("DELETE FROM ".$this->dbPrefix."queue WHERE type = '".QTYP_UPDATE_STATS."'");
        $seventeen = mktime (17, 0, 0);
        AddUpdateStatsEvent ($seventeen);
        $this->assertSame (mktime (20, 5, 0), (int)$this->queueRows ("type = '".QTYP_UPDATE_STATS."'")[0]['end']);

        // Before 8:00 the next slot is 8:05 of the next day.
        dbquery ("DELETE FROM ".$this->dbPrefix."queue WHERE type = '".QTYP_UPDATE_STATS."'");
        $three = mktime (3, 0, 0);
        $day = getdate ($three);
        AddUpdateStatsEvent ($three);
        $this->assertSame (mktime (8, 5, 0, $day['mon'], $day['mday'] + 1), (int)$this->queueRows ("type = '".QTYP_UPDATE_STATS."'")[0]['end']);
    }

    /**
     * Queue_UpdateStats_End copies the current scores into the "old" columns
     * and schedules the following slot.
     */
    public function testQueueUpdateStatsEndCopiesTheScoresAndReschedules (): void
    {
        dbquery ("UPDATE ".$this->dbPrefix."users SET score1 = 111, score2 = 222, score3 = 333, place1 = 4, place2 = 5, place3 = 6 WHERE player_id = 1");

        $end = mktime (16, 5, 0);
        $task = AddQueue (USER_SPACE, QTYP_UPDATE_STATS, 0, 0, 0, $end - 3600, 3600, QUEUE_PRIO_UPDATE_STATS);

        Queue_UpdateStats_End (LoadQueue ($task));

        $this->assertSame (111, (int)$this->userField (1, 'oldscore1'));
        $this->assertSame (222, (int)$this->userField (1, 'oldscore2'));
        $this->assertSame (333, (int)$this->userField (1, 'oldscore3'));
        $this->assertSame (4, (int)$this->userField (1, 'oldplace1'));
        $this->assertSame (5, (int)$this->userField (1, 'oldplace2'));
        $this->assertSame (6, (int)$this->userField (1, 'oldplace3'));
        $this->assertSame ($end, (int)$this->userField (1, 'scoredate'));

        $this->assertFalse ($this->taskRow ($task));

        // 16:05 is followed by the 20:05 slot.
        $rows = $this->queueRows ("type = '".QTYP_UPDATE_STATS."'");
        $this->assertCount (1, $rows);
        $this->assertSame (mktime (20, 5, 0), (int)$rows[0]['end']);
    }

    /**
     * AddReloginEvent only adds one event.
     */
    public function testAddReloginEventIsIdempotent (): void
    {
        AddReloginEvent ();
        AddReloginEvent ();

        $rows = $this->queueRows ("type = '".QTYP_UNLOAD_ALL."'");
        $this->assertCount (1, $rows);
        $this->assertSame (USER_SPACE, (int)$rows[0]['owner_id']);
        $this->assertSame (QUEUE_PRIO_RELOGIN, (int)$rows[0]['prio']);

        // The event points at 03:00 of the next day.
        $delta = (int)$rows[0]['end'] - (int)$rows[0]['start'];
        $this->assertGreaterThan (0, $delta);
        $this->assertLessThanOrEqual (24 * 3600 + 120, $delta);
    }

    /**
     * Queue_Relogin_End logs everybody out and resets the hack counter.
     */
    public function testQueueReloginEndClearsTheSessions (): void
    {
        dbquery ("UPDATE ".$this->dbPrefix."users SET session = 'abcdef123456' WHERE player_id = 1");
        dbquery ("UPDATE ".$this->dbPrefix."uni SET hacks = 5");
        $task = AddQueue (USER_SPACE, QTYP_UNLOAD_ALL, 0, 0, 0, 1000, 0, QUEUE_PRIO_RELOGIN);

        // UnloadAll() writes a redirect to the output buffer; swallow it.
        $level = ob_get_level ();
        ob_start (function ($buffer) { return ''; });
        Queue_Relogin_End (LoadQueue ($task));
        while (ob_get_level () > $level) ob_end_clean ();

        $this->assertSame ('', (string)$this->userField (1, 'session'));
        $this->assertSame (0, (int)$this->uniField ('hacks'));
        $this->assertFalse ($this->taskRow ($task));
    }

    /**
     * AddFarspaceCooldownEvent only adds one event; the handler decreases the
     * visit counters and reschedules itself.
     */
    public function testAddFarspaceCooldownEventIsIdempotentAndReschedules (): void
    {
        AddFarspaceCooldownEvent ();
        AddFarspaceCooldownEvent ();

        $rows = $this->queueRows ("type = '".QTYP_FARSPACE_COOLDOWN."'");
        $this->assertCount (1, $rows);
        $this->assertSame (USER_SPACE, (int)$rows[0]['owner_id']);
        $this->assertSame (QUEUE_PRIO_FARSPACE_COOLDOWN, (int)$rows[0]['prio']);
        $this->assertSame (EXPEDITION_COOLDOWN_PERIOD, (int)$rows[0]['end'] - (int)$rows[0]['start']);

        Queue_FarspaceCooldown_End ($rows[0]);

        $this->assertFalse ($this->taskRow ((int)$rows[0]['task_id']));
        $this->assertSame (1, $this->countQueue ("type = '".QTYP_FARSPACE_COOLDOWN."'"), 'the cooldown event reschedules itself');
    }

    /**
     * The weekly debris cleanup removes only empty debris fields that no
     * recycler is flying to.
     */
    public function testCleanDebrisEventDeletesOnlyEmptyUnguardedDebris (): void
    {
        AddCleanDebrisEvent ();
        AddCleanDebrisEvent ();
        $rows = $this->queueRows ("type = '".QTYP_CLEAN_DEBRIS."'");
        $this->assertCount (1, $rows);
        $taskId = (int)$rows[0]['task_id'];

        $empty = AddDBRow (array (
            'name' => 'Debris A', 'type' => PTYP_DF, 'g' => 1, 's' => 7, 'p' => 4, 'owner_id' => USER_SPACE,
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0,
            GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0, 'date' => $this->now,
        ), 'planets');
        $guarded = AddDBRow (array (
            'name' => 'Debris B', 'type' => PTYP_DF, 'g' => 1, 's' => 8, 'p' => 4, 'owner_id' => USER_SPACE,
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0,
            GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0, 'date' => $this->now,
        ), 'planets');
        $this->addFleet (1, FTYP_RECYCLE, 1, $guarded);

        // A debris field that already holds resources is never removed (the
        // fixture one holds metal and crystal).
        $rich = AddDBRow (array (
            'name' => 'Debris C', 'type' => PTYP_DF, 'g' => 1, 's' => 9, 'p' => 4, 'owner_id' => USER_SPACE,
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0,
            GID_RC_METAL => 500, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0, 'date' => $this->now,
        ), 'planets');

        Queue_CleanDebris_End (array ('task_id' => $taskId));

        $this->assertFalse ($this->planetExists ($empty), 'an empty debris field without recyclers must be removed');
        $this->assertTrue ($this->planetExists ($guarded), 'a debris field with a recycler in flight must be kept');
        $this->assertTrue ($this->planetExists ($rich), 'a debris field with resources must be kept');
        $this->assertFalse ($this->taskRow ($taskId));
        $this->assertSame (1, $this->countQueue ("type = '".QTYP_CLEAN_DEBRIS."'"), 'the cleanup reschedules itself');
    }

    /**
     * The daily planet cleanup removes the planets marked for removal, keeps
     * the future ones and keeps a colonization phantom that is still in flight.
     */
    public function testCleanPlanetsEventDestroysMarkedPlanetsOnly (): void
    {
        AddCleanPlanetsEvent ();
        AddCleanPlanetsEvent ();
        $rows = $this->queueRows ("type = '".QTYP_CLEAN_PLANETS."'");
        $this->assertCount (1, $rows);
        $taskId = (int)$rows[0]['task_id'];
        $end = (int)$rows[0]['end'];

        $doomed = AddDBRow (array (
            'name' => 'Doomed', 'type' => PTYP_ABANDONED, 'g' => 1, 's' => 10, 'p' => 4, 'owner_id' => USER_SPACE,
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'remove' => $end - 1,
            GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0, 'date' => $this->now,
        ), 'planets');
        $survivor = AddDBRow (array (
            'name' => 'Later', 'type' => PTYP_ABANDONED, 'g' => 1, 's' => 11, 'p' => 4, 'owner_id' => USER_SPACE,
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'remove' => $end + 3600,
            GID_RC_METAL => 0, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0, 'date' => $this->now,
        ), 'planets');

        // The fixture's colonization phantom has a colonize flight on the way;
        // even marked for removal it must survive (the flight would otherwise
        // silently lose its target).
        $phantom = dbarray (dbquery ("SELECT planet_id FROM ".$this->dbPrefix."planets WHERE type = ".PTYP_COLONY_PHANTOM." LIMIT 1"));
        $this->assertIsArray ($phantom);
        dbquery ("UPDATE ".$this->dbPrefix."planets SET remove = ".($end - 1)." WHERE planet_id = " . (int)$phantom['planet_id']);

        Queue_CleanPlanets_End (array ('task_id' => $taskId, 'end' => $end));

        $this->assertFalse ($this->planetExists ($doomed), 'a planet marked for removal must be destroyed');
        $this->assertTrue ($this->planetExists ($survivor), 'a planet marked for a later removal must survive');
        $this->assertTrue ($this->planetExists ((int)$phantom['planet_id']), 'a phantom with a colonization in flight must survive');
        $this->assertFalse ($this->taskRow ($taskId));
        $this->assertSame (1, $this->countQueue ("type = '".QTYP_CLEAN_PLANETS."'"));
    }

    /**
     * The daily player cleanup removes the players set for deletion and the
     * long inactive ones, but never the ones that bought dark matter.
     */
    public function testCleanPlayersEventRemovesMarkedAndInactivePlayers (): void
    {
        AddCleanPlayersEvent ();
        AddCleanPlayersEvent ();
        $rows = $this->queueRows ("type = '".QTYP_CLEAN_PLAYERS."'");
        $this->assertCount (1, $rows);
        $taskId = (int)$rows[0]['task_id'];
        $end = (int)$rows[0]['end'];

        $marked = $this->addUser (101, array ('disable' => 1, 'disable_until' => $end - 1));
        $inactive = $this->addUser (102, array ('lastclick' => $end - 40 * 86400));
        $unvalidated = $this->addUser (103, array ('validated' => 0, 'lastclick' => 0, 'regdate' => $end - 10 * 86400));
        $buyer = $this->addUser (104, array ('dm' => 1000, 'lastclick' => $end - 40 * 86400));

        Queue_CleanPlayers_End (array ('task_id' => $taskId, 'end' => $end));

        $this->assertFalse ($this->userExists ($marked), 'a player set for deletion must be removed');
        $this->assertFalse ($this->userExists ($inactive), 'a player inactive for more than 35 days must be removed');
        $this->assertFalse ($this->userExists ($unvalidated), 'a never activated account older than 3 days must be removed');
        $this->assertTrue ($this->userExists ($buyer), 'a player with purchased dark matter must be kept');
        $this->assertTrue ($this->userExists (1), 'the fixture players must survive the cleanup');

        $this->assertFalse ($this->taskRow ($taskId));
        $this->assertSame (1, $this->countQueue ("type = '".QTYP_CLEAN_PLAYERS."'"));
    }

    /**
     * The alliance point recalculation sums the member scores.
     */
    public function testRecalcAllyPointsEvents (): void
    {
        AddRecalcAllyPointsEvent ();
        AddRecalcAllyPointsEvent ();

        $rows = $this->queueRows ("type = '".QTYP_RECALC_ALLY_POINTS."'");
        $this->assertCount (1, $rows);
        $this->assertSame (USER_SPACE, (int)$rows[0]['owner_id']);
        $this->assertSame (QUEUE_PRIO_RECALC_ALLY_POINTS, (int)$rows[0]['prio']);
        $taskId = (int)$rows[0]['task_id'];

        // The fixture alliance has the three players as members (50000 + 45000
        // + 40000 points, etc.).
        Queue_RecalcAllyPoints_End (LoadQueue ($taskId));

        $this->assertSame (135000, (int)$this->allyField (1, 'score1'));
        $this->assertSame (83000, (int)$this->allyField (1, 'score2'));
        $this->assertSame (53000, (int)$this->allyField (1, 'score3'));
        $this->assertSame (1, (int)$this->allyField (1, 'place1'));
        $this->assertFalse ($this->taskRow ($taskId));
    }

    /**
     * AddDebugEvent schedules a task relative to now; the handler removes it.
     */
    public function testAddDebugEventAndQueueDebugEnd (): void
    {
        AddDebugEvent (300);

        $rows = $this->queueRows ("type = '".QTYP_DEBUG."'");
        $this->assertCount (1, $rows);
        $this->assertSame (USER_SPACE, (int)$rows[0]['owner_id']);
        $this->assertSame (QUEUE_PRIO_DEBUG, (int)$rows[0]['prio']);
        $this->assertSame (300, (int)$rows[0]['end'] - (int)$rows[0]['start']);

        Queue_Debug_End ($rows[0]);

        $this->assertSame (0, $this->countQueue ("type = '".QTYP_DEBUG."'"));
    }

    // ========================================================================
    // Fleet queue queries
    // ========================================================================

    /**
     * GetFleetQueue returns the task of a flying fleet.
     *
     * NOTE: the docblock promises null for an unknown fleet, but the function
     * returns whatever dbarray() gives (false with the shipped backends), so
     * the "not found" contract is false, not null.
     */
    public function testGetFleetQueueReturnsTheTaskOfAFleet (): void
    {
        $row = GetFleetQueue (1);

        $this->assertIsArray ($row);
        $this->assertSame (QTYP_FLEET, $row['type']);
        $this->assertSame (1, (int)$row['sub_id']);
        $this->assertSame (1, (int)$row['owner_id']);

        $this->assertFalse (GetFleetQueue (999999));
    }

    /**
     * EnumFleetQueue lists the player's own fleets plus the enemy and friendly
     * fleets flying to their planets.
     */
    public function testEnumFleetQueueListsOwnAndIncomingFleets (): void
    {
        // Player 1 owns the 8 fixture fleets; 2 more fleets fly to his planets
        // (an attack of player 2 and a spy of player 3).
        $own = $this->rowsOf (EnumFleetQueue (1));
        $this->assertCount (10, $own);
        foreach ($own as $row) $this->assertSame (QTYP_FLEET, $row['type']);

        $owners = array ();
        foreach ($own as $row) $owners[(int)$row['owner_id']] = true;
        $this->assertArrayHasKey (2, $owners, 'an incoming enemy attack must be listed');
        $this->assertArrayHasKey (3, $owners, 'an incoming foreign spy must be listed');

        // Player 2: his own 2 fleets plus the 4 fleets flying to his planets.
        $this->assertCount (6, $this->rowsOf (EnumFleetQueue (2)));
    }

    /**
     * EnumOwnFleetQueue lists only the player's own fleets and skips flying
     * missiles unless they are requested explicitly.
     */
    public function testEnumOwnFleetQueueExcludesMissiles (): void
    {
        $ipmFleet = AddDBRow (array (
            'owner_id' => 1, 'mission' => FTYP_MISSILE, 'start_planet' => 1, 'target_planet' => 4,
            'flight_time' => 100, 'deploy_time' => 0, 'fuel' => 0, 'ipm_amount' => 2,
        ), 'fleet');
        AddQueue (1, QTYP_FLEET, $ipmFleet, 0, 0, 1000, 100, QUEUE_PRIO_FLEET + FTYP_MISSILE);

        $this->assertCount (8, $this->rowsOf (EnumOwnFleetQueue (1)), 'a flying IPM must not count as a fleet');
        $this->assertCount (9, $this->rowsOf (EnumOwnFleetQueue (1, 1)), 'with $ipm = 1 the missile is listed');

        // Ordered by end time ascending.
        $ends = array ();
        foreach ($this->rowsOf (EnumOwnFleetQueue (1, 1)) as $row) $ends[] = (int)$row['end'];
        $sorted = $ends;
        sort ($sorted);
        $this->assertSame ($sorted, $ends);
    }

    /**
     * EnumOwnFleetQueueSpecial lists the player's own fleets newest first.
     */
    public function testEnumOwnFleetQueueSpecialOrdersByStartDescending (): void
    {
        $newest = $this->addFleet (1, FTYP_SPY, 1, 4);
        $newestTask = AddQueue (1, QTYP_FLEET, $newest, 0, 0, $this->now - 10, 600, QUEUE_PRIO_FLEET + FTYP_SPY);
        $older = $this->addFleet (1, FTYP_SPY, 1, 4);
        AddQueue (1, QTYP_FLEET, $older, 0, 0, $this->now - 100, 600, QUEUE_PRIO_FLEET + FTYP_SPY);

        // A flying missile is not listed at all here.
        $missile = AddDBRow (array (
            'owner_id' => 1, 'mission' => FTYP_MISSILE, 'start_planet' => 1, 'target_planet' => 4,
            'flight_time' => 100, 'deploy_time' => 0, 'fuel' => 0,
        ), 'fleet');
        AddQueue (1, QTYP_FLEET, $missile, 0, 0, $this->now, 100, QUEUE_PRIO_FLEET + FTYP_MISSILE);

        $rows = $this->rowsOf (EnumOwnFleetQueueSpecial (1));

        $this->assertCount (10, $rows);
        $this->assertSame ($newestTask, (int)$rows[0]['task_id'], 'the most recent start time must come first');
        foreach ($rows as $row) $this->assertNotSame ($missile, (int)$row['sub_id']);
    }

    /**
     * EnumPlanetFleets lists the fleets flying from or to a planet.
     */
    public function testEnumPlanetFleetsListsStartAndTargetFleets (): void
    {
        // Planet 1: the start of the 8 player-1 fleets and the target of an
        // enemy attack and a foreign spy.
        $this->assertCount (10, $this->rowsOf (EnumPlanetFleets (1)));

        // Planet 4 (player 2's home): 2 own fleets start here, 2 enemies fly to it.
        $this->assertCount (4, $this->rowsOf (EnumPlanetFleets (4)));

        $this->assertCount (0, $this->rowsOf (EnumPlanetFleets (6)));
    }
}
