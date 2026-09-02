<?php

// Tests for the farspace expedition mechanics (issue #174):
//  - the expedition visit counter (stored as the farspace object's metal)
//    cools down by 3 every hour via Queue_FarspaceCooldown_End;
//  - farspace objects with no fleet flying to or from them are deleted once a
//    week via Queue_CleanFarspace_End (which also protects objects that a
//    returning expedition is leaving).
//
// The tests run against the in-memory SQLite backend (see phpunit.xml) and use
// the real game queue handlers from game/core/queue.php, loaded through the
// testing/bootstrap.php core bootstrap.

use PHPUnit\Framework\TestCase;

class FarspaceTest extends TestCase
{
    private FixtureBuilder $fixture;
    private string $dbPrefix;

    protected function setUp(): void
    {
        // Avoid "foreach() argument must be of type array|object, null given"
        // from ModsExecRef* when no mod has been initialized in this test.
        $GLOBALS['modlist'] = array ();
        $this->fixture = new FixtureBuilder();
        $this->dbPrefix = $this->fixture->getDbPrefix();
    }

    /**
     * Insert a farspace object at the given coordinates and return its id.
     */
    private function addFarspace(string $name, int $g, int $s, int $metal): int
    {
        $now = time ();
        return AddDBRow (array (
            'name' => $name, 'type' => PTYP_FARSPACE, 'g' => $g, 's' => $s, 'p' => 16, 'owner_id' => USER_SPACE,
            'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0, 'date' => $now,
            GID_RC_METAL => $metal, GID_RC_CRYSTAL => 0, GID_RC_DEUTERIUM => 0,
        ), 'planets');
    }

    /**
     * Insert an expedition fleet record.
     */
    private function addFleet(int $mission, int $start, int $target): int
    {
        return AddDBRow (array (
            'owner_id' => 1, 'mission' => $mission, 'start_planet' => $start, 'target_planet' => $target,
            'flight_time' => 100, 'deploy_time' => 100, 'fuel' => 0,
        ), 'fleet');
    }

    private function farspaceMetal(int $planetId): int
    {
        $res = dbquery ("SELECT `".GID_RC_METAL."` AS metal FROM ".$this->dbPrefix."planets WHERE planet_id = $planetId");
        $row = dbarray ($res);
        $this->assertNotFalse ($row, "Farspace planet $planetId should exist");
        return (int)$row['metal'];
    }

    private function farspaceExists(int $planetId): bool
    {
        $res = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."planets WHERE planet_id = $planetId");
        $row = dbarray ($res);
        return (int)$row['cnt'] > 0;
    }

    public function testFarspaceCooldownDecreasesCounterByThree(): void
    {
        $a = $this->addFarspace ('A', 1, 1, 5);
        $b = $this->addFarspace ('B', 1, 2, 2);
        $c = $this->addFarspace ('C', 1, 3, 0);

        $queue = array ('task_id' => 0);
        Queue_FarspaceCooldown_End ($queue);

        $this->assertSame (2, $this->farspaceMetal ($a), "5 visits should cool down to 2");
        $this->assertSame (0, $this->farspaceMetal ($b), "2 visits should cool down to 0 (clamped)");
        $this->assertSame (0, $this->farspaceMetal ($c), "0 visits should stay 0 (clamped)");
    }

    public function testFarspaceCooldownSchedulesItself(): void
    {
        $this->addFarspace ('A', 1, 1, 0);

        Queue_FarspaceCooldown_End (array ('task_id' => 0));

        $res = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."queue WHERE type = '".QTYP_FARSPACE_COOLDOWN."'");
        $row = dbarray ($res);
        $this->assertSame (1, (int)$row['cnt'], "The cooldown event must be re-scheduled for the next hour");
    }

    public function testFarspaceCleanupDeletesOnlyUnvisited(): void
    {
        // A: target of an outbound expedition (flying TO it).
        $a = $this->addFarspace ('A', 1, 1, 0);
        // B: start_planet of a returning expedition (flying FROM it).
        $b = $this->addFarspace ('B', 1, 2, 0);
        // C: no fleet is flying to or from it.
        $c = $this->addFarspace ('C', 1, 3, 0);
        // D: target of an orbiting expedition (flying TO it).
        $d = $this->addFarspace ('D', 1, 4, 0);

        $this->addFleet (FTYP_EXPEDITION, 999, $a);
        $this->addFleet (FTYP_EXPEDITION + FTYP_RETURN, $b, 999);
        $this->addFleet (FTYP_EXPEDITION + FTYP_ORBITING, 999, $d);

        Queue_CleanFarspace_End (array ('task_id' => 0));

        $this->assertTrue ($this->farspaceExists ($a), "Farspace that is the target of an expedition must be kept");
        $this->assertTrue ($this->farspaceExists ($b), "Farspace that a returning expedition is leaving must be kept");
        $this->assertFalse ($this->farspaceExists ($c), "Farspace with no fleet to or from it must be deleted");
        $this->assertTrue ($this->farspaceExists ($d), "Farspace with an orbiting expedition must be kept");
    }

    public function testFarspaceCleanupSchedulesItself(): void
    {
        $this->addFarspace ('A', 1, 1, 0);

        Queue_CleanFarspace_End (array ('task_id' => 0));

        $res = dbquery ("SELECT COUNT(*) AS cnt FROM ".$this->dbPrefix."queue WHERE type = '".QTYP_CLEAN_FARSPACE."'");
        $row = dbarray ($res);
        $this->assertSame (1, (int)$row['cnt'], "The cleanup event must be re-scheduled for the next week");
    }
}
