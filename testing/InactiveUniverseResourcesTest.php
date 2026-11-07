<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for issue #158 "Plunder + Relogin + inactive universe".
 *
 * In an inactive universe the global event queue is replayed in bulk when a
 * player logs in (UpdateQueue processes every event that elapsed while nobody
 * was online). The handlers advance the planet state to the *event* time with
 * GetUpdatePlanet($planet_id, $queue['end']), but resource adjustments used to
 * stamp lastpeek = time(). That had two consequences:
 *
 *  - all production accrued between the event and the actual relogin was
 *    silently dropped (the planet looked like it had produced nothing all
 *    night, and the plundered resources looked like they were taken twice);
 *  - a later event with an earlier timestamp then moved the planet clock
 *    backwards: GetUpdatePlanet() computed a negative interval and *subtracted*
 *    production from the stored resources.
 *
 * The planet clock (lastpeek) may only be advanced by GetUpdatePlanet(), which
 * credits the production for the elapsed interval. AdjustResources() must not
 * touch it, and GetUpdatePlanet() must never rewind it.
 */
#[RunTestsInSeparateProcesses]
class InactiveUniverseResourcesTest extends TestCase
{
    protected function setUp(): void
    {
        chdir(__DIR__ . '/../game');

        $fixture = (new FixtureBuilder())->createTestUniverse('en');

        // GetUpdatePlanet() / ProdResources() read the universe from $GlobalUni.
        global $GlobalUni, $GlobalUser, $db_prefix;
        $GlobalUni = $fixture->getUniData();
        $GlobalUser = LoadUser(1);
        $db_prefix = $fixture->getDbPrefix();

        loca_add('battlereport', 'en');
        loca_add('technames', 'en');
        loca_add('fleetmsg', 'en');
    }

    /** Read a planet row straight from the database. */
    private function planetRow(int $planetId): array
    {
        global $db_prefix;
        $result = dbquery("SELECT * FROM {$db_prefix}planets WHERE planet_id = $planetId");
        return dbarray($result);
    }

    /** Read the fleet task row of a fleet. */
    private function fleetTaskRow(int $fleetId): array
    {
        global $db_prefix;
        $result = dbquery("SELECT * FROM {$db_prefix}queue WHERE type = 'Fleet' AND sub_id = $fleetId");
        return dbarray($result);
    }

    /**
     * A plunder (AdjustResources with '-') processed long after lastpeek must
     * not discard the production of the offline night. The player returns in
     * the morning and GetUpdatePlanet() must still credit that production on
     * top of the plundered resources.
     */
    public function testPlunderDoesNotDiscardProductionAccruedWhileOffline(): void
    {
        global $db_prefix;

        $now = time();
        $offlineSince = $now - 8 * 3600;

        // The defender's planet was last updated 8 hours ago.
        dbquery("UPDATE {$db_prefix}planets SET lastpeek = $offlineSince WHERE planet_id = 4");

        $before = $this->planetRow(4);
        $storedMetal = (int)$before[GID_RC_METAL];

        // The attacker plunders 1000 metal from the planet.
        AdjustResources(array(GID_RC_METAL => 1000), 4, '-');

        // The adjustment is not a planet update: the production of the night
        // is still owed, so the planet clock must not jump to the wall clock.
        $afterPlunder = $this->planetRow(4);
        $this->assertSame($offlineSince, (int)$afterPlunder['lastpeek'],
            'AdjustResources must not move lastpeek: the elapsed production has not been credited yet.');
        $this->assertSame($storedMetal - 1000, (int)$afterPlunder[GID_RC_METAL],
            'The plunder itself must only subtract the captured amount.');

        // The player logs in: the production of the night is credited.
        $afterLogin = GetUpdatePlanet(4, $now);
        $this->assertGreaterThan($storedMetal - 1000, (int)$afterLogin[GID_RC_METAL],
            'Production accrued between lastpeek and the relogin must be credited after the plunder.');
        $this->assertSame($now, (int)$afterLogin['lastpeek']);
    }

    /**
     * GetUpdatePlanet() must never move the planet clock backwards. Replaying
     * a late event whose timestamp is older than lastpeek used to compute a
     * negative interval, subtract production and rewind lastpeek.
     */
    public function testGetUpdatePlanetNeverRewindsThePlanetClock(): void
    {
        global $db_prefix;

        $now = time();
        $storedMetal = 5000;
        $storedCrystal = 4000;
        $storedDeut = 3000;

        dbquery("UPDATE {$db_prefix}planets SET
            `" . GID_RC_METAL . "` = $storedMetal,
            `" . GID_RC_CRYSTAL . "` = $storedCrystal,
            `" . GID_RC_DEUTERIUM . "` = $storedDeut,
            lastpeek = $now
            WHERE planet_id = 4");

        // A queued event from 4 hours ago is processed now.
        $planet = GetUpdatePlanet(4, $now - 4 * 3600);

        $this->assertSame($now, (int)$planet['lastpeek'],
            'A late event must not rewind the planet clock.');
        $this->assertSame($storedMetal, (int)$planet[GID_RC_METAL],
            'A negative interval must not subtract production.');
        $this->assertSame($storedCrystal, (int)$planet[GID_RC_CRYSTAL]);
        $this->assertSame($storedDeut, (int)$planet[GID_RC_DEUTERIUM]);

        // ... and the rewind must not reach the database either.
        $row = $this->planetRow(4);
        $this->assertSame($now, (int)$row['lastpeek']);
        $this->assertSame($storedMetal, (int)$row[GID_RC_METAL]);
    }

    /**
     * End-to-end: the plundered cargo comes home on a fleet task that is
     * replayed hours late. The cargo is added by AdjustResources, which must
     * not stamp the origin planet with the wall-clock time: otherwise the
     * production of the remaining offline hours is lost and the player sees
     * the captured resources "removed" instead of added.
     */
    public function testLateFleetReturnAddsCargoOnTopOfTheNightProduction(): void
    {
        global $db_prefix;

        $now = time();
        $returnAt = $now - 6 * 3600;
        $offlineSince = $now - 8 * 3600;

        $storedMetal = (int)$this->planetRow(1)[GID_RC_METAL];
        $cargo = 5000;

        // A returning attack fleet carrying the plunder.
        dbquery("UPDATE {$db_prefix}fleet SET mission = " . (FTYP_ATTACK + FTYP_RETURN) . ",
            start_planet = 1, target_planet = 4, flight_time = 1200, fuel = 0,
            `" . GID_F_LF . "` = 1000, `" . GID_F_SC . "` = 0,
            `" . GID_RC_METAL . "` = $cargo, `" . GID_RC_CRYSTAL . "` = 0,
            `" . GID_RC_DEUTERIUM . "` = 0 WHERE fleet_id = 2");

        // Both planets were last updated before the night.
        dbquery("UPDATE {$db_prefix}planets SET lastpeek = $offlineSince WHERE planet_id IN (1, 4)");

        // The return was due 6 hours ago; it is still waiting in the queue.
        $task = $this->fleetTaskRow(2);
        dbquery("UPDATE {$db_prefix}queue SET start = " . ($returnAt - 1200) . ",
            end = $returnAt WHERE task_id = " . (int)$task['task_id']);
        InvalidateUserCache();

        Queue_Fleet_End(LoadQueue((int)$task['task_id']));

        $afterReturn = $this->planetRow(1);
        $this->assertLessThanOrEqual($returnAt, (int)$afterReturn['lastpeek'],
            'A late fleet return must not move the planet clock to the wall clock.');
        $this->assertGreaterThanOrEqual($storedMetal + $cargo, (int)$afterReturn[GID_RC_METAL],
            'The captured cargo must be added to the origin planet.');

        $metalAfterReturn = (int)$afterReturn[GID_RC_METAL];

        // On relogin the production of the remaining 6 hours is credited too.
        $afterLogin = GetUpdatePlanet(1, $now);
        $this->assertGreaterThan($metalAfterReturn, (int)$afterLogin[GID_RC_METAL],
            'Production between the late return and the relogin must still be credited.');
    }

    /**
     * End-to-end: a battle fleet task that is replayed hours late must not
     * stamp the defender's planet with the wall-clock time. After the battle
     * the remaining offline production must still be credited on relogin.
     */
    public function testLateBattleEventKeepsTheProductionUntilRelogin(): void
    {
        global $db_prefix;

        $now = time();
        $battleAt = $now - 6 * 3600;
        $offlineSince = $now - 8 * 3600;

        // Universe must use the PHP battle engine and no defence repair.
        dbquery("UPDATE {$db_prefix}uni SET fid = 30, did = 30, rapid = 0, defrepair = 0,
            defrepair_delta = 0, php_battle = 1, freeze = 0");

        // Attacker: fleet 2 (mission Attack) gets 1000 fighters.
        dbquery("UPDATE {$db_prefix}fleet SET `" . GID_F_LF . "` = 1000, `" . GID_F_SC . "` = 0,
            fuel = 300 WHERE fleet_id = 2");

        // Defender: planet 4 with no defences and no ships.
        dbquery("UPDATE {$db_prefix}planets SET `" . GID_D_RL . "` = 0, `" . GID_D_LL . "` = 0,
            `" . GID_D_HL . "` = 0, `" . GID_D_GAUSS . "` = 0, `" . GID_D_ION . "` = 0,
            `" . GID_D_PLASMA . "` = 0, `" . GID_D_SDOME . "` = 0, `" . GID_D_LDOME . "` = 0,
            `" . GID_F_SC . "` = 0, `" . GID_F_LF . "` = 0, lastpeek = $offlineSince
            WHERE planet_id = 4");
        dbquery("UPDATE {$db_prefix}planets SET lastpeek = $offlineSince WHERE planet_id = 1");

        // The fleet was supposed to arrive 6 hours ago, but nobody was online
        // (inactive universe), so the task is still waiting in the queue.
        $task = $this->fleetTaskRow(2);
        dbquery("UPDATE {$db_prefix}queue SET start = " . ($battleAt - 1200) . ",
            end = $battleAt WHERE task_id = " . (int)$task['task_id']);
        InvalidateUserCache();

        // StartBattle() writes intermediate battle files; clean up only ours.
        $battleDir = __DIR__ . '/../game/battledata/battle_*.txt';
        $resultDir = __DIR__ . '/../game/battleresult/battle_*.txt';
        $before = array_merge(glob($battleDir) ?: [], glob($resultDir) ?: []);

        // The battle log UPDATE uses an unescaped SQL string and echoes its
        // error; swallow it (see BattleCoreTest for the documented bug).
        ob_start();
        try {
            Queue_Fleet_End(LoadQueue((int)$task['task_id']));
        } finally {
            ob_end_clean();
            foreach (array_merge(glob($battleDir) ?: [], glob($resultDir) ?: []) as $file) {
                if (!in_array($file, $before, true)) {
                    @unlink($file);
                }
            }
        }

        $afterBattle = $this->planetRow(4);
        $this->assertLessThanOrEqual($battleAt, (int)$afterBattle['lastpeek'],
            'A late battle must not force the defender planet clock to the wall-clock time.');

        $metalAfterBattle = (int)$afterBattle[GID_RC_METAL];

        // The defender logs in 6 hours after the battle: production of those
        // hours must be credited on top of the plundered planet.
        $afterLogin = GetUpdatePlanet(4, $now);
        $this->assertGreaterThan($metalAfterBattle, (int)$afterLogin[GID_RC_METAL],
            'Production between the late battle and the relogin must still be credited.');
    }
}
