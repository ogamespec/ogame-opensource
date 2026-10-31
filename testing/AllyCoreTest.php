<?php

// Tests for the alliance core modules:
//   game/core/ally.php      alliance CRUD, tag/name changes, statistics, ranks
//   game/core/allyranks.php alliance ranks (add, edit, remove, assign)
//   game/core/allyapps.php  alliance applications
//   game/core/buddy.php     buddy list (requests, acceptance, listing)
//   game/core/acs.php       ACS (alliance combat system) unions
//
// The tests run against the real database layer with the in-memory SQLite
// backend (DB_CONNECTION=sqlite, DB_DATABASE=:memory:, see phpunit.xml and
// testing/bootstrap.php), so no MySQL server is required. FixtureBuilder
// creates the real game schema (install_tabs.php) plus a 3 player universe:
// alliance "TST" (id 1) with 3 ranks, 3 members, one open application and two
// buddy entries, plus planets, fleets and fleet queue events for the ACS tests.
//
// Each test method runs in a separate PHP process and starts with a fresh
// in-memory database. Functions that need raw SQL are asserted against the
// concrete rows they write, so the tests lock in the real contract of the
// modules (including their quirks, see the "Suspected bug" comments).

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class AllyCoreTest extends TestCase
{
    /** Fixture with the seeded 3 player universe. */
    private FixtureBuilder $fixture;

    /** Name prefix of the fixture tables (FixtureBuilder uses "test_"). */
    private string $db = 'test_';

    protected function setUp(): void
    {
        // loca_add() resolves the language files relative to the game directory.
        chdir(__DIR__ . '/../game');

        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');
        $this->db = $this->fixture->getDbPrefix();

        // The ACS invite code reads the universe settings and the acting player
        // from the globals; the rest of the modules only need the DB prefix.
        global $db_prefix, $GlobalUni, $GlobalUser, $loca_lang;
        $db_prefix = $this->db;
        $loca_lang = 'en';
        $GlobalUni = $this->fixture->getUniData();
        InvalidateUserCache();
        $GlobalUser = LoadUser(1);
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /** Read every row of a result set into an array. */
    private function fetchAll(mixed $result): array
    {
        $rows = array();
        while ($row = dbarray($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /** Fetch a single row (false when the query matches nothing). */
    private function row(string $sql): mixed
    {
        return dbarray(dbquery($sql));
    }

    /** COUNT(*) of a "SELECT COUNT(*) AS c ..." query. */
    private function countRows(string $sql): int
    {
        $row = $this->row($sql);
        return $row === false ? 0 : intval($row['c']);
    }

    /** The alliance row of the given id, or false. */
    private function ally(int $allyId): mixed
    {
        return $this->row("SELECT * FROM {$this->db}ally WHERE ally_id = $allyId");
    }

    /** The user row of the given player id, or false. */
    private function user(int $playerId): mixed
    {
        return $this->row("SELECT * FROM {$this->db}users WHERE player_id = $playerId");
    }

    /** Insert an alliance row with sensible defaults (fixture-free tests). */
    private function addAllyRecord(array $overrides = array()): int
    {
        return AddDBRow($overrides + array(
            'tag' => 'ADD', 'name' => 'Added Alliance', 'owner_id' => 1, 'nextrank' => 0,
            'open' => 1, 'insertapp' => 0,
            'score1' => 0, 'score2' => 0, 'score3' => 0,
            'place1' => 0, 'place2' => 0, 'place3' => 0,
        ), 'ally');
    }

    /**
     * Insert a fleet row (attack fleet by default). The "+" union operator is
     * used instead of array_merge() because the GID_F_* unit keys are integers
     * and array_merge() would renumber them.
     */
    private function addFleet(array $overrides = array()): int
    {
        return AddDBRow($overrides + array(
            'owner_id' => 1, 'union_id' => 0, 'mission' => FTYP_ATTACK,
            'start_planet' => 1, 'target_planet' => 4,
            'flight_time' => 100, 'deploy_time' => 0, 'fuel' => 1,
        ), 'fleet');
    }

    /**
     * The real game stores the unique player name lowercased (CreateUser does
     * mb_strtolower) and the display name in oname. AddUnionMember() lowercases
     * the input before looking the user up, so tests that invite players have to
     * mirror that layout.
     */
    private function useLowerCasePlayerNames(): void
    {
        dbquery("UPDATE {$this->db}users SET name = 'playertwo' WHERE player_id = 2");
        dbquery("UPDATE {$this->db}users SET name = 'playerthree' WHERE player_id = 3");
        InvalidateUserCache();
    }

    // ========================================================================
    // game/core/ally.php -- CreateAlly
    // ========================================================================

    /**
     * A new alliance starts open, with the localized default text and no
     * pending tag/name change.
     */
    public function testCreateAllyStoresTheDefaultAllianceRow(): void
    {
        $allyId = CreateAlly(1, 'NEW', 'New Alliance');

        $this->assertGreaterThan(0, $allyId);

        $ally = $this->ally($allyId);
        $this->assertIsArray($ally);
        $this->assertSame('NEW', $ally['tag']);
        $this->assertSame('New Alliance', $ally['name']);
        $this->assertSame(1, intval($ally['owner_id']));
        $this->assertSame(1, intval($ally['open']));
        $this->assertSame(0, intval($ally['insertapp']));
        $this->assertSame('Welcome to the alliance page', $ally['exttext']);
        $this->assertSame('', $ally['inttext']);
        $this->assertSame('', $ally['apptext']);
        // Two ranks are created (founder + newcomer), so nextrank grows to 2.
        $this->assertSame(2, intval($ally['nextrank']));
        $this->assertSame(0, intval($ally['tag_until']));
        $this->assertSame(0, intval($ally['name_until']));
        $this->assertSame(0, intval($ally['score1']));
    }

    /**
     * CreateAlly() adds the "Founder" (all rights) and "Newcomer" (no rights)
     * ranks and makes the creator the founder of the new alliance.
     */
    public function testCreateAllyAddsRanksAndMakesTheCreatorTheFounder(): void
    {
        $before = time();
        $allyId = CreateAlly(1, 'NEW', 'New Alliance');
        $after = time();

        $ranks = $this->fetchAll(dbquery("SELECT * FROM {$this->db}allyranks WHERE ally_id = $allyId ORDER BY rank_id"));
        $this->assertCount(2, $ranks);
        $this->assertSame(0, intval($ranks[0]['rank_id']));
        $this->assertSame('Founder', $ranks[0]['name']);
        $this->assertSame(0x1FF, intval($ranks[0]['rights']));
        $this->assertSame(1, intval($ranks[1]['rank_id']));
        $this->assertSame('Newcomer', $ranks[1]['name']);
        $this->assertSame(0, intval($ranks[1]['rights']));

        $user = $this->user(1);
        $this->assertSame($allyId, intval($user['ally_id']));
        $this->assertSame(0, intval($user['allyrank']));
        $this->assertGreaterThanOrEqual($before, intval($user['joindate']));
        $this->assertLessThanOrEqual($after, intval($user['joindate']));
    }

    /**
     * The tag is cut to 8 characters and the name to 30 (the column limits).
     */
    public function testCreateAllyTruncatesTagAndName(): void
    {
        $allyId = CreateAlly(1, 'ABCDEFGHIJKL', str_repeat('N', 40));

        $ally = $this->ally($allyId);
        $this->assertSame('ABCDEFGH', $ally['tag']);
        $this->assertSame(str_repeat('N', 30), $ally['name']);
    }

    /**
     * An unknown founder id creates nothing and returns 0.
     */
    public function testCreateAllyReturnsZeroForAnUnknownOwner(): void
    {
        $alliesBefore = $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}ally");

        $this->assertSame(0, CreateAlly(9999, 'NEW', 'New Alliance'));

        $this->assertSame($alliesBefore, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}ally"));
    }

    // ========================================================================
    // game/core/ally.php -- DismissAlly
    // ========================================================================

    /**
     * DismissAlly() clears the membership of every member, deletes the ranks
     * and applications of that alliance and the alliance row itself, but keeps
     * other alliances untouched.
     */
    public function testDismissAllyRemovesMembershipRanksApplicationsAndRow(): void
    {
        $other = $this->addAllyRecord(array('tag' => 'OTH', 'name' => 'Other'));

        $this->assertSame(3, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}users WHERE ally_id = 1"));
        $this->assertSame(3, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}allyranks WHERE ally_id = 1"));
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}allyapps WHERE ally_id = 1"));

        DismissAlly(1);

        $this->assertFalse($this->ally(1));
        $this->assertSame(0, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}users WHERE ally_id = 1"));
        $this->assertSame(0, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}allyranks WHERE ally_id = 1"));
        $this->assertSame(0, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}allyapps WHERE ally_id = 1"));
        $this->assertNotFalse($this->ally($other));

        $user = $this->user(2);
        $this->assertSame(0, intval($user['ally_id']));
        $this->assertSame(0, intval($user['joindate']));
        $this->assertSame(0, intval($user['allyrank']));
    }

    // ========================================================================
    // game/core/ally.php -- loading, searching, counting
    // ========================================================================

    /** LoadAlly() returns false when the alliance does not exist. */
    public function testLoadAllyReturnsFalseForAMissingAlliance(): void
    {
        $this->assertFalse(LoadAlly(9999));

        $ally = LoadAlly(1);
        $this->assertSame('TST', $ally['tag']);
    }

    /** IsAllyTagExist() matches the exact tag only. */
    public function testIsAllyTagExistMatchesTheExactTag(): void
    {
        $this->assertTrue(IsAllyTagExist('TST'));
        $this->assertFalse(IsAllyTagExist('TSTX'));
        $this->assertFalse(IsAllyTagExist('NOPE'));
    }

    /**
     * SearchAllyTag() finds partial tag matches and never returns more than the
     * 30 rows of its LIMIT clause.
     */
    public function testSearchAllyTagFindsPartialMatchesAndLimitsTheResult(): void
    {
        $this->assertSame(1, dbrows(SearchAllyTag('TS')));
        $this->assertSame(1, dbrows(SearchAllyTag('TST')));
        $this->assertSame(0, dbrows(SearchAllyTag('ZZZZ')));

        for ($i = 0; $i < 35; $i++) {
            $this->addAllyRecord(array('tag' => 'XX' . $i, 'name' => 'Bulk ' . $i));
        }

        $this->assertSame(30, dbrows(SearchAllyTag('XX')));
    }

    /** CountAllyMembers() counts the members and rejects invalid ids. */
    public function testCountAllyMembersCountsMembersAndRejectsInvalidIds(): void
    {
        $this->assertSame(3, CountAllyMembers(1));
        $this->assertSame(0, CountAllyMembers(0));
        $this->assertSame(0, CountAllyMembers(-7));
        $this->assertSame(0, CountAllyMembers(9999));
    }

    /** EnumerateAlly() returns null for a non-positive alliance id. */
    public function testEnumerateAllyReturnsNullForAnInvalidId(): void
    {
        $this->assertNull(EnumerateAlly(0));
        $this->assertNull(EnumerateAlly(-1));
    }

    /**
     * EnumerateAlly() joins the member list with the rank name and the home
     * planet coordinates.
     */
    public function testEnumerateAllyJoinsRankAndHomePlanet(): void
    {
        $rows = $this->fetchAll(EnumerateAlly(1));
        $this->assertCount(3, $rows);

        $byPlayer = array();
        foreach ($rows as $row) {
            $byPlayer[intval($row['player_id'])] = $row;
        }

        $one = $byPlayer[1];
        $this->assertSame('PlayerOne', $one['oname']);
        $this->assertSame(1, intval($one['ally_id']));
        $this->assertSame(1, intval($one['allyrank']));
        $this->assertSame(50000, intval($one['score1']));
        $this->assertSame(1, intval($one['hplanetid']));
        $this->assertSame('Founder', $one['name']);
        $this->assertSame(1, intval($one['g']));
        $this->assertSame(1, intval($one['s']));
        $this->assertSame(4, intval($one['p']));

        $two = $byPlayer[2];
        $this->assertSame(1, intval($two['g']));
        $this->assertSame(3, intval($two['s']));
        $this->assertSame(4, intval($two['p']));
    }

    /**
     * Without use_sort the result keeps the table order; with use_sort the
     * sort_by/order parameters drive the ORDER BY clause.
     */
    public function testEnumerateAllySortsMembersByName(): void
    {
        $unsorted = array();
        foreach ($this->fetchAll(EnumerateAlly(1)) as $row) {
            $unsorted[] = $row['oname'];
        }
        $this->assertSame(array('PlayerOne', 'PlayerTwo', 'PlayerThree'), $unsorted);

        $ascending = array();
        foreach ($this->fetchAll(EnumerateAlly(1, 1, 0, true)) as $row) {
            $ascending[] = $row['oname'];
        }
        $this->assertSame(array('PlayerOne', 'PlayerThree', 'PlayerTwo'), $ascending);

        $descending = array();
        foreach ($this->fetchAll(EnumerateAlly(1, 1, 1, true)) as $row) {
            $descending[] = $row['oname'];
        }
        $this->assertSame(array('PlayerTwo', 'PlayerThree', 'PlayerOne'), $descending);

        // sort_by = 0 falls back to the player id.
        $byId = array();
        foreach ($this->fetchAll(EnumerateAlly(1, 0, 0, true)) as $row) {
            $byId[] = intval($row['player_id']);
        }
        $this->assertSame(array(1, 2, 3), $byId);
    }

    /** sort_by = 3 orders by score1, sort_by = 2 by the rank id. */
    public function testEnumerateAllySortsMembersByScoreAndRank(): void
    {
        $byScore = array();
        foreach ($this->fetchAll(EnumerateAlly(1, 3, 0, true)) as $row) {
            $byScore[] = $row['oname'];
        }
        $this->assertSame(array('PlayerThree', 'PlayerTwo', 'PlayerOne'), $byScore);

        SetUserRank(1, 2);
        SetUserRank(2, 0);
        SetUserRank(3, 1);

        $byRank = array();
        foreach ($this->fetchAll(EnumerateAlly(1, 2, 0, true)) as $row) {
            $byRank[] = $row['oname'] . ':' . intval($row['allyrank']);
        }
        $this->assertSame(array('PlayerTwo:0', 'PlayerThree:1', 'PlayerOne:2'), $byRank);
    }

    /** sort_by = 4 orders by the join date, sort_by = 5 by the last click. */
    public function testEnumerateAllySortsMembersByJoinDateAndLastClick(): void
    {
        dbquery("UPDATE {$this->db}users SET joindate = 100 WHERE player_id = 1");
        dbquery("UPDATE {$this->db}users SET joindate = 200 WHERE player_id = 2");
        dbquery("UPDATE {$this->db}users SET joindate = 300 WHERE player_id = 3");

        $byJoinDate = array();
        foreach ($this->fetchAll(EnumerateAlly(1, 4, 0, true)) as $row) {
            $byJoinDate[] = $row['oname'];
        }
        $this->assertSame(array('PlayerOne', 'PlayerTwo', 'PlayerThree'), $byJoinDate);

        dbquery("UPDATE {$this->db}users SET lastclick = 500 WHERE player_id = 1");
        dbquery("UPDATE {$this->db}users SET lastclick = 300 WHERE player_id = 2");
        dbquery("UPDATE {$this->db}users SET lastclick = 100 WHERE player_id = 3");

        $byLastClick = array();
        foreach ($this->fetchAll(EnumerateAlly(1, 5, 1, true)) as $row) {
            $byLastClick[] = $row['oname'];
        }
        $this->assertSame(array('PlayerOne', 'PlayerTwo', 'PlayerThree'), $byLastClick);
    }

    // ========================================================================
    // game/core/ally.php -- tag/name/owner changes
    // ========================================================================

    /**
     * AllyChangeTag() stores the previous tag, sets the new one and blocks any
     * further change for 7 days.
     */
    public function testAllyChangeTagStoresTheOldTagAndEnforcesTheCooldown(): void
    {
        $before = time();
        $this->assertTrue(AllyChangeTag(1, 'NEW'));
        $after = time();

        $ally = $this->ally(1);
        $this->assertSame('NEW', $ally['tag']);
        $this->assertSame('TST', $ally['old_tag']);
        $this->assertGreaterThanOrEqual($before + 7 * 24 * 60 * 60, intval($ally['tag_until']));
        $this->assertLessThanOrEqual($after + 7 * 24 * 60 * 60, intval($ally['tag_until']));

        // While the cooldown runs a second change is refused.
        $this->assertFalse(AllyChangeTag(1, 'ABC'));
        $this->assertSame('NEW', $this->ally(1)['tag']);
        $this->assertSame('TST', $this->ally(1)['old_tag']);
    }

    /** An unchanged tag is refused even when the cooldown has expired. */
    public function testAllyChangeTagRejectsTheCurrentTag(): void
    {
        dbquery("UPDATE {$this->db}ally SET tag_until = 0 WHERE ally_id = 1");

        $this->assertFalse(AllyChangeTag(1, 'TST'));

        $ally = $this->ally(1);
        $this->assertSame('TST', $ally['tag']);
        $this->assertSame(0, intval($ally['tag_until']));
    }

    /**
     * AllyChangeName() has the same contract as AllyChangeTag(): the old name
     * is remembered and the 7 day cooldown is enforced.
     */
    public function testAllyChangeNameStoresTheOldNameAndEnforcesTheCooldown(): void
    {
        $before = time();
        $this->assertTrue(AllyChangeName(1, 'Better Alliance'));
        $after = time();

        $ally = $this->ally(1);
        $this->assertSame('Better Alliance', $ally['name']);
        $this->assertSame('Test Alliance', $ally['old_name']);
        $this->assertGreaterThanOrEqual($before + 7 * 24 * 60 * 60, intval($ally['name_until']));
        $this->assertLessThanOrEqual($after + 7 * 24 * 60 * 60, intval($ally['name_until']));

        $this->assertFalse(AllyChangeName(1, 'Another Name'));
        $this->assertSame('Better Alliance', $this->ally(1)['name']);

        dbquery("UPDATE {$this->db}ally SET name_until = 0 WHERE ally_id = 1");
        $this->assertFalse(AllyChangeName(1, 'Better Alliance'));
        $this->assertSame(0, intval($this->ally(1)['name_until']));
    }

    /** AllyChangeOwner() only rewrites the owner_id column. */
    public function testAllyChangeOwnerReplacesTheOwnerId(): void
    {
        AllyChangeOwner(1, 3);

        $ally = $this->ally(1);
        $this->assertSame(3, intval($ally['owner_id']));
        $this->assertSame('TST', $ally['tag']);

        // The rank of the members (and of the former founder) is not touched.
        $this->assertSame(1, intval($this->user(1)['allyrank']));
        $this->assertSame(1, intval($this->user(3)['allyrank']));
    }

    // ========================================================================
    // game/core/ally.php -- statistics
    // ========================================================================

    /**
     * RecalcAllyStats() replaces the alliance score columns with the sum of the
     * scores of the members.
     */
    public function testRecalcAllyStatsSumsTheScoresOfTheMembers(): void
    {
        $other = $this->addAllyRecord(array('tag' => 'OTH', 'name' => 'Other'));
        dbquery("UPDATE {$this->db}users SET ally_id = $other WHERE player_id = 3");

        // The fixture seeds stale alliance scores (90000/58000/38000).
        RecalcAllyStats();

        $one = $this->ally(1);
        $this->assertSame(95000, intval($one['score1']));   // 50000 + 45000
        $this->assertSame(58000, intval($one['score2']));   // 30000 + 28000
        $this->assertSame(38000, intval($one['score3']));   // 20000 + 18000

        $two = $this->ally($other);
        $this->assertSame(40000, intval($two['score1']));
        $this->assertSame(25000, intval($two['score2']));
        $this->assertSame(15000, intval($two['score3']));
    }

    /** A negative sum is clamped to zero (the columns are UNSIGNED). */
    public function testRecalcAllyStatsClampsNegativeSumsToZero(): void
    {
        dbquery("UPDATE {$this->db}users SET score1 = -100, score2 = -200, score3 = -300 WHERE ally_id = 1");

        RecalcAllyStats();

        $ally = $this->ally(1);
        $this->assertSame(0, intval($ally['score1']));
        $this->assertSame(0, intval($ally['score2']));
        $this->assertSame(0, intval($ally['score3']));
    }

    /**
     * An alliance without members produces invalid SQL.
     *
     * Suspected bug (documented, not fixed): the SUM query of a memberless
     * alliance still returns one row, with NULL sums. The NULLs are
     * interpolated into the UPDATE ("SET score1 = , score2 = , ..."), which is
     * a syntax error in both MySQL and SQLite, so dbquery() reports an error and
     * the alliance keeps its old score. In the live game this error output is
     * echoed into the page.
     */
    public function testRecalcAllyStatsFailsForAnAllianceWithoutMembers(): void
    {
        $empty = $this->addAllyRecord(array(
            'tag' => 'EMP', 'name' => 'Empty',
            'score1' => 777, 'score2' => 777, 'score3' => 777,
        ));

        ob_start();
        RecalcAllyStats();
        $output = ob_get_clean();

        $this->assertNotSame('', trim($output), 'The invalid SQL should have been reported by the DB layer.');

        // The memberless alliance keeps its old scores ...
        $ally = $this->ally($empty);
        $this->assertSame(777, intval($ally['score1']));
        $this->assertSame(777, intval($ally['score2']));
        $this->assertSame(777, intval($ally['score3']));

        // ... while the alliances with members are recalculated in the same run.
        $this->assertSame(135000, intval($this->ally(1)['score1']));
    }

    /**
     * RecalcAllyRanks() numbers the alliances 1..N for each of the three score
     * columns (points, fleet, research).
     */
    public function testRecalcAllyRanksAssignsSequentialPlacesPerScore(): void
    {
        $b = $this->addAllyRecord(array('tag' => 'B', 'name' => 'B', 'score1' => 100000, 'score2' => 10000, 'score3' => 99000));
        $c = $this->addAllyRecord(array('tag' => 'C', 'name' => 'C', 'score1' => 50000, 'score2' => 99000, 'score3' => 10000));
        dbquery("UPDATE {$this->db}ally SET score1 = 90000, score2 = 58000, score3 = 38000 WHERE ally_id = 1");

        RecalcAllyRanks();

        $one = $this->ally(1);
        $this->assertSame(2, intval($one['place1']));
        $this->assertSame(2, intval($one['place2']));
        $this->assertSame(2, intval($one['place3']));

        $second = $this->ally($b);
        $this->assertSame(1, intval($second['place1']));
        $this->assertSame(3, intval($second['place2']));
        $this->assertSame(1, intval($second['place3']));

        $third = $this->ally($c);
        $this->assertSame(3, intval($third['place1']));
        $this->assertSame(1, intval($third['place2']));
        $this->assertSame(3, intval($third['place3']));
    }

    /**
     * Suspected bug (documented, not fixed): the new tag is interpolated into
     * the UPDATE without escaping, and the return value ignores whether the
     * query succeeded. A tag containing a quote makes the UPDATE fail, yet the
     * function still reports success and the alliance keeps its old tag.
     */
    public function testAllyChangeTagReportsSuccessWhenTheUpdateFails(): void
    {
        dbquery("UPDATE {$this->db}ally SET tag_until = 0 WHERE ally_id = 1");

        ob_start();
        $result = AllyChangeTag(1, "O'Brien");
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertSame('TST', $this->ally(1)['tag']);
        $this->assertNotSame('', trim($output));
    }

    /**
     * Suspected bug (documented, not fixed): IsAllyTagExist() interpolates the
     * tag unescaped as well; the resulting SQL error only makes dbquery() return
     * false, which is reported as "the tag does not exist".
     */
    public function testIsAllyTagExistReportsMissingTagWhenTheQueryFails(): void
    {
        ob_start();
        $result = IsAllyTagExist("O'Brien");
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertNotSame('', trim($output));
    }

    // ========================================================================
    // game/core/allyranks.php
    // ========================================================================

    /**
     * AddRank() appends a rank with the next rank id, zero rights and
     * increments the alliance counter.
     */
    public function testAddRankAppendsARankAndIncrementsNextRank(): void
    {
        $rankId = AddRank(1, 'Officer');

        $this->assertSame(3, $rankId);   // the fixture alliance has nextrank = 3

        $rank = LoadRank(1, $rankId);
        $this->assertSame(3, intval($rank['rank_id']));
        $this->assertSame(1, intval($rank['ally_id']));
        $this->assertSame('Officer', $rank['name']);
        $this->assertSame(0, intval($rank['rights']));

        $this->assertSame(4, intval($this->ally(1)['nextrank']));
    }

    /** AddRank() refuses a non-positive alliance id and inserts nothing. */
    public function testAddRankRejectsAnInvalidAllianceId(): void
    {
        $before = $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}allyranks");

        $this->assertSame(0, AddRank(0, 'Officer'));
        $this->assertSame(0, AddRank(-4, 'Officer'));

        $this->assertSame($before, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}allyranks"));
    }

    /** SetRank() updates the rights of one rank of one alliance only. */
    public function testSetRankUpdatesOnlyTheGivenAllianceAndRank(): void
    {
        $rankId = AddRank(1, 'Officer');
        // nextrank = 3 makes the other alliance create the same rank id.
        $other = $this->addAllyRecord(array('tag' => 'OTH', 'name' => 'Other', 'nextrank' => 3));
        AddRank($other, 'Officer');

        SetRank(1, $rankId, ARANK_R_APPLY | ARANK_W_APPLY);
        $this->assertSame(ARANK_R_APPLY | ARANK_W_APPLY, intval(LoadRank(1, $rankId)['rights']));

        // The same rank id in another alliance keeps its rights.
        $this->assertSame(0, intval(LoadRank($other, $rankId)['rights']));

        SetRank(1, $rankId + 50, 0x1FF);
        $this->assertSame(ARANK_R_APPLY | ARANK_W_APPLY, intval(LoadRank(1, $rankId)['rights']));
    }

    /** RemoveRank() deletes that rank and LoadRank() then returns false. */
    public function testRemoveRankDeletesTheRank(): void
    {
        $rankId = AddRank(1, 'Officer');
        $this->assertIsArray(LoadRank(1, $rankId));

        RemoveRank(1, $rankId);

        $this->assertFalse(LoadRank(1, $rankId));
        $this->assertFalse(LoadRank(1, 9999));
        // The three fixture ranks (0 = founder, 1 = founder, 2 = recruiter) survive.
        $this->assertSame(3, dbrows(EnumRanks(1)));
    }

    /** EnumRanks() lists the ranks of the given alliance only. */
    public function testEnumRanksListsOnlyTheAllianceRanks(): void
    {
        $other = $this->addAllyRecord(array('tag' => 'OTH', 'name' => 'Other', 'nextrank' => 0));
        AddRank($other, 'Foreign Rank');

        $ranks = $this->fetchAll(EnumRanks(1));
        $this->assertCount(3, $ranks);

        $names = array();
        foreach ($ranks as $rank) {
            $names[] = $rank['name'];
        }
        $this->assertSame(array('Founder', 'Founder', 'Recruiter'), $names);

        $foreign = $this->fetchAll(EnumRanks($other));
        $this->assertCount(1, $foreign);
        $this->assertSame('Foreign Rank', $foreign[0]['name']);
    }

    /** SetUserRank() rewrites the allyrank column of a player. */
    public function testSetUserRankChangesTheMemberRank(): void
    {
        SetUserRank(2, 2);

        $this->assertSame(2, intval($this->user(2)['allyrank']));
        $this->assertSame(1, intval($this->user(1)['allyrank']));
    }

    /** LoadUsersWithRank() filters by alliance and rank id. */
    public function testLoadUsersWithRankFiltersByAllianceAndRank(): void
    {
        $this->assertSame(3, dbrows(LoadUsersWithRank(1, 1)));

        SetUserRank(2, 2);
        $this->assertSame(1, dbrows(LoadUsersWithRank(1, 2)));
        $this->assertSame(2, dbrows(LoadUsersWithRank(1, 1)));

        // A player of another alliance with the same rank id is not returned.
        $other = $this->addAllyRecord(array('tag' => 'OTH', 'name' => 'Other'));
        dbquery("UPDATE {$this->db}users SET ally_id = $other, allyrank = 2 WHERE player_id = 2");

        $this->assertSame(0, dbrows(LoadUsersWithRank(1, 2)));
        $this->assertSame(1, dbrows(LoadUsersWithRank($other, 2)));
    }

    // ========================================================================
    // game/core/allyapps.php
    // ========================================================================

    /** AddApplication() stores the application with the current timestamp. */
    public function testAddApplicationStoresTheApplicationWithTheCurrentDate(): void
    {
        $before = time();
        $appId = AddApplication(1, 2, 'Let me in please');
        $after = time();

        $this->assertGreaterThan(0, $appId);

        $app = LoadApplication($appId);
        $this->assertSame(1, intval($app['ally_id']));
        $this->assertSame(2, intval($app['player_id']));
        $this->assertSame('Let me in please', $app['text']);
        $this->assertGreaterThanOrEqual($before, intval($app['date']));
        $this->assertLessThanOrEqual($after, intval($app['date']));
    }

    /** A second application of the same player to the same alliance is refused. */
    public function testAddApplicationRefusesADuplicateApplication(): void
    {
        // The fixture already contains the application of player 3 to alliance 1.
        $before = $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}allyapps");

        $this->assertSame(0, AddApplication(1, 3, 'Again'));

        $this->assertSame($before, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}allyapps"));
    }

    /**
     * Documents the current contract: the text is stored as given (an empty
     * application is accepted) and the alliance id is not validated.
     */
    public function testAddApplicationAcceptsEmptyTextAndUnknownAlliance(): void
    {
        $appId = AddApplication(9999, 2, '');

        $this->assertGreaterThan(0, $appId);

        $app = LoadApplication($appId);
        $this->assertSame(9999, intval($app['ally_id']));
        $this->assertSame('', $app['text']);
    }

    /** GetUserApplication() returns the application id of the player, or 0. */
    public function testGetUserApplicationReturnsTheIdOrZero(): void
    {
        $this->assertSame(1, GetUserApplication(3));   // fixture application

        $appId = AddApplication(1, 2, 'Let me in');
        $this->assertSame($appId, GetUserApplication(2));

        $this->assertSame(0, GetUserApplication(9999));
    }

    /** EnumApplications() is scoped to the alliance. */
    public function testEnumApplicationsIsScopedToTheAlliance(): void
    {
        $other = $this->addAllyRecord(array('tag' => 'OTH', 'name' => 'Other'));
        AddApplication($other, 2, 'To the other alliance');

        $rows = $this->fetchAll(EnumApplications(1));
        $this->assertCount(1, $rows);
        $this->assertSame(3, intval($rows[0]['player_id']));
        $this->assertSame('Hello TST, I would like to join your alliance. My fleet is ready.', $rows[0]['text']);

        $foreign = $this->fetchAll(EnumApplications($other));
        $this->assertCount(1, $foreign);
        $this->assertSame(2, intval($foreign[0]['player_id']));
    }

    /** RemoveApplication() deletes exactly one application. */
    public function testRemoveApplicationDeletesOnlyThatApplication(): void
    {
        $keep = AddApplication(1, 2, 'Keep me');

        RemoveApplication(1);

        $this->assertFalse(LoadApplication(1));
        $this->assertFalse(LoadApplication(9999));
        $this->assertIsArray(LoadApplication($keep));
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}allyapps"));
    }

    // ========================================================================
    // game/core/buddy.php
    // ========================================================================

    /** AddBuddy() creates a pending request; pending players are not buddies. */
    public function testAddBuddyCreatesAPendingRequest(): void
    {
        $buddyId = AddBuddy(2, 3, 'Be my buddy');

        $this->assertGreaterThan(0, $buddyId);

        $row = LoadBuddy($buddyId);
        $this->assertSame(2, intval($row['request_from']));
        $this->assertSame(3, intval($row['request_to']));
        $this->assertSame('Be my buddy', $row['text']);
        $this->assertSame(0, intval($row['accepted']));
        $this->assertFalse(IsBuddy(2, 3));
    }

    /** The request text is cut to 5000 characters. */
    public function testAddBuddyTruncatesTheRequestText(): void
    {
        $buddyId = AddBuddy(2, 3, str_repeat('x', 6000));

        $this->assertSame(5000, mb_strlen(LoadBuddy($buddyId)['text'], 'UTF-8'));
    }

    /** An empty request text is replaced with the hard coded placeholder. */
    public function testAddBuddyStoresThePlaceholderForEmptyText(): void
    {
        $buddyId = AddBuddy(2, 3, '');

        $this->assertSame('пусто', LoadBuddy($buddyId)['text']);
    }

    /**
     * AddBuddy() refuses a duplicate request in both directions.
     */
    public function testAddBuddyRefusesADuplicatePendingRequest(): void
    {
        // The fixture has a pending request from player 3 to player 1.
        $before = $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}buddy");

        $this->assertSame(0, AddBuddy(3, 1, 'Again'));
        $this->assertSame(0, AddBuddy(1, 3, 'Reverse direction'));

        $this->assertSame($before, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}buddy"));
    }

    /** Two accepted buddies cannot send each other new requests. */
    public function testAddBuddyRefusesPlayersThatAreAlreadyBuddies(): void
    {
        // The fixture has an accepted buddy entry between players 2 and 1.
        $this->assertTrue(IsBuddy(1, 2));

        $this->assertSame(0, AddBuddy(1, 2, 'Again'));
        $this->assertSame(0, AddBuddy(2, 1, 'Again'));
    }

    /** AcceptBuddy() turns a request into a mutual buddy entry. */
    public function testAcceptBuddyTurnsARequestIntoAMutualBuddyEntry(): void
    {
        $buddyId = AddBuddy(2, 3, 'Hello');
        $this->assertFalse(IsBuddy(2, 3));

        AcceptBuddy($buddyId);

        $this->assertSame(1, intval(LoadBuddy($buddyId)['accepted']));
        $this->assertTrue(IsBuddy(2, 3));
        $this->assertTrue(IsBuddy(3, 2));
        // Player 2 is also the accepted buddy of player 1 from the fixture.
        $this->assertSame(2, dbrows(EnumBuddy(2)));
        $this->assertSame(1, dbrows(EnumBuddy(3)));
        // The accepted request no longer counts as pending.
        $this->assertSame(0, dbrows(EnumOutcomeBuddy(2)));
        $this->assertSame(0, dbrows(EnumIncomeBuddy(3)));
    }

    /** RemoveBuddy() deletes the request; LoadBuddy() returns false then. */
    public function testRemoveBuddyDeletesTheRequest(): void
    {
        RemoveBuddy(2);

        $this->assertFalse(LoadBuddy(2));
        $this->assertFalse(LoadBuddy(9999));
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}buddy"));
    }

    /**
     * The three listing functions separate outgoing pending requests, incoming
     * pending requests and accepted buddies.
     */
    public function testEnumBuddySeparatesPendingAndAcceptedEntries(): void
    {
        // Fixture: 2 -> 1 accepted, 3 -> 1 pending.
        $this->assertSame(1, dbrows(EnumIncomeBuddy(1)));
        $this->assertSame(0, dbrows(EnumOutcomeBuddy(1)));
        $this->assertSame(1, dbrows(EnumBuddy(1)));

        $this->assertSame(0, dbrows(EnumIncomeBuddy(2)));
        $this->assertSame(0, dbrows(EnumOutcomeBuddy(2)));
        $this->assertSame(1, dbrows(EnumBuddy(2)));

        $this->assertSame(0, dbrows(EnumIncomeBuddy(3)));
        $this->assertSame(1, dbrows(EnumOutcomeBuddy(3)));
        $this->assertSame(0, dbrows(EnumBuddy(3)));

        AddBuddy(2, 3, 'Pending');
        $this->assertSame(1, dbrows(EnumOutcomeBuddy(2)));
        $this->assertSame(1, dbrows(EnumIncomeBuddy(3)));
        $this->assertSame(0, dbrows(EnumBuddy(3)));
    }

    // ========================================================================
    // game/core/acs.php -- unions
    // ========================================================================

    /**
     * CreateUnion() converts the head fleet into an ACS attack head, stores the
     * target player and reuses the union on a second call.
     */
    public function testCreateUnionConvertsTheHeadFleetAndReusesTheUnion(): void
    {
        $head = LoadFleet(2);   // PlayerOne attacks PlayerTwo's home planet 4
        $this->assertSame(FTYP_ATTACK, intval($head['mission']));

        $unionId = CreateUnion(2, 'KV1');
        $this->assertGreaterThan(0, $unionId);

        $union = LoadUnion($unionId);
        $this->assertSame(2, intval($union['fleet_id']));
        $this->assertSame(2, intval($union['target_player']));
        $this->assertSame('KV1', $union['name']);
        $this->assertSame(1, intval($union['players']));
        $this->assertEquals(array(1), array_map('intval', $union['player']));

        $head = LoadFleet(2);
        $this->assertSame($unionId, intval($head['union_id']));
        $this->assertSame(FTYP_ACS_ATTACK_HEAD, intval($head['mission']));

        // The head fleet already belongs to a union: no second union is created.
        $this->assertSame($unionId, CreateUnion(2, 'Other'));
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}union"));
    }

    /** Unions can only be created for fleets on an attack mission. */
    public function testCreateUnionRejectsFleetsThatAreNotAttacks(): void
    {
        // Fleet 1 is an espionage mission, fleet 6 a recycle mission.
        $this->assertSame(FTYP_SPY, intval(LoadFleet(1)['mission']));

        $this->assertSame(0, CreateUnion(1, 'KV1'));
        $this->assertSame(0, CreateUnion(6, 'KV1'));

        $this->assertSame(0, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}union"));
        $this->assertSame(FTYP_SPY, intval(LoadFleet(1)['mission']));
    }

    /** An attack on the player's own planet cannot become an ACS union. */
    public function testCreateUnionRejectsAnAttackOnThePlayersOwnPlanet(): void
    {
        $fleetId = $this->addFleet(array('owner_id' => 1, 'target_planet' => 1));

        $this->assertSame(0, CreateUnion($fleetId, 'KV1'));

        $this->assertSame(0, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}union"));
    }

    /** A target planet that does not exist cannot become an ACS union. */
    public function testCreateUnionRejectsAnUnknownTargetPlanet(): void
    {
        $fleetId = $this->addFleet(array('owner_id' => 1, 'target_planet' => 99999));

        $this->assertSame(0, CreateUnion($fleetId, 'KV1'));

        $this->assertSame(0, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}union"));
    }

    /**
     * LoadUnion() splits the comma separated player list, counts the players and
     * returns null for an unknown union.
     */
    public function testLoadUnionSplitsThePlayerList(): void
    {
        $unionId = CreateUnion(2, 'KV1');
        dbquery("UPDATE {$this->db}union SET players = '1,2,3' WHERE union_id = $unionId");

        $union = LoadUnion($unionId);
        $this->assertSame(3, intval($union['players']));
        $this->assertEquals(array(1, 2, 3), array_map('intval', $union['player']));

        $this->assertNull(LoadUnion(9999));
    }

    /** RemoveUnion() deletes the given union only. */
    public function testRemoveUnionDeletesOnlyTheGivenUnion(): void
    {
        $first = CreateUnion(2, 'A');
        $second = CreateUnion(9, 'B');

        RemoveUnion($first);

        $this->assertNull(LoadUnion($first));
        $this->assertIsArray(LoadUnion($second));
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}union"));
    }

    /** RenameUnion() stores the new name; an unknown union is a silent no-op. */
    public function testRenameUnionStoresTheNewName(): void
    {
        $unionId = CreateUnion(2, 'KV1');

        RenameUnion($unionId, 'Joint Strike');

        $this->assertSame('Joint Strike', LoadUnion($unionId)['name']);

        RenameUnion(9999, 'Ghost');

        $this->assertNull(LoadUnion(9999));
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}union"));
    }

    // ========================================================================
    // game/core/acs.php -- members
    // ========================================================================

    /**
     * The rejection paths of AddUnionMember(): empty name, unknown user and
     * unknown union.
     */
    public function testAddUnionMemberRejectsInvalidInput(): void
    {
        $unionId = CreateUnion(2, 'KV1');

        // Empty name: explicit no-op.
        $this->assertSame('', AddUnionMember($unionId, ''));
        $this->assertSame('User not found', AddUnionMember($unionId, 'nobody'));

        // NOTE: en_en/union.php does not define ACS_UNION_NOT_FOUND (the other
        // languages do), so loca() falls back to the key name for English.
        $this->assertSame('ACS_UNION_NOT_FOUND', AddUnionMember(9999, 'PlayerTwo'));

        $this->assertSame(1, LoadUnion($unionId)['players']);
    }

    /** The universe setting "acs" + 1 is the maximum number of players. */
    public function testAddUnionMemberEnforcesTheUniverseMaximum(): void
    {
        $this->useLowerCasePlayerNames();
        $unionId = CreateUnion(2, 'KV1');   // uni.acs = 1 -> at most 2 players

        $this->assertSame('', AddUnionMember($unionId, 'playertwo'));

        $this->assertSame('A maximum of 2 players can participate!', AddUnionMember($unionId, 'playerthree'));
        $this->assertSame(2, LoadUnion($unionId)['players']);

        $this->assertSame(0, $this->countRows("SELECT COUNT(*) AS c FROM {$this->db}messages WHERE owner_id = 3"));
    }

    /** Adding the same player twice is refused. */
    public function testAddUnionMemberRefusesAPlayerThatIsAlreadyInTheUnion(): void
    {
        $this->useLowerCasePlayerNames();
        // Lift the limit above the number of test players so the duplicate check
        // is reachable (it runs after the maximum check).
        dbquery("UPDATE {$this->db}uni SET acs = 3");
        global $GlobalUni;
        $GlobalUni = LoadUniverse();
        $unionId = CreateUnion(2, 'KV1');

        $this->assertSame('', AddUnionMember($unionId, 'playertwo'));
        $this->assertSame('Such a user has already been added to the union', AddUnionMember($unionId, 'playertwo'));
        $this->assertSame(2, LoadUnion($unionId)['players']);
    }

    /**
     * A successful invite adds the player id to the union and sends the
     * localized invitation message with the target coordinates and the arrival
     * time of the head fleet.
     */
    public function testAddUnionMemberAddsThePlayerAndSendsAnInvitation(): void
    {
        $this->useLowerCasePlayerNames();
        $unionId = CreateUnion(2, 'KV1');
        $queue = GetFleetQueue(2);

        // The lookup lowercases the input name, so the display name works too.
        $this->assertSame('', AddUnionMember($unionId, 'PlayerTwo'));

        $union = LoadUnion($unionId);
        $this->assertSame(2, intval($union['players']));
        $this->assertSame(1, intval($union['player'][0]));   // head fleet owner
        $this->assertSame(2, intval($union['player'][1]));   // the invited player

        $messages = $this->fetchAll(dbquery("SELECT * FROM {$this->db}messages WHERE owner_id = 2"));
        $this->assertCount(1, $messages);
        $this->assertSame(MTYP_MISC, intval($messages[0]['pm']));
        $this->assertSame('PlayerOne', $messages[0]['msgfrom']);
        $this->assertSame('Invitation to joint attack', $messages[0]['subj']);
        $this->assertStringContainsString('PlayerOne invites you to mission KV1 against player PlayerTwo', $messages[0]['text']);
        $this->assertStringContainsString('showGalaxy(1,3,4)', $messages[0]['text']);
        $this->assertStringContainsString('[1:3:4]', $messages[0]['text']);
        $this->assertStringContainsString(
            'Fleet arrival is scheduled for ' . date('D M Y H:i:s', intval($queue['end'])),
            $messages[0]['text']
        );
    }

    /** IsPlayerInUnion() checks the split player list of a loaded union. */
    public function testIsPlayerInUnionChecksThePlayerList(): void
    {
        $unionId = CreateUnion(2, 'KV1');
        dbquery("UPDATE {$this->db}union SET players = '1,2' WHERE union_id = $unionId");

        $union = LoadUnion($unionId);
        $this->assertTrue(IsPlayerInUnion(1, $union));
        $this->assertTrue(IsPlayerInUnion(2, $union));
        $this->assertFalse(IsPlayerInUnion(3, $union));
    }

    // ========================================================================
    // game/core/acs.php -- fleets, timing and unit counts
    // ========================================================================

    /** EnumUnion() returns the player's own unions plus the targeted ones. */
    public function testEnumUnionReturnsOwnAndTargetedUnions(): void
    {
        $a = CreateUnion(2, 'A');   // players "1", target player 2
        $b = CreateUnion(9, 'B');   // players "2", target player 1
        $this->assertGreaterThan(0, $a);
        $this->assertGreaterThan(0, $b);

        $names = function (array $unions): array {
            $result = array();
            foreach ($unions as $union) {
                $result[] = $union['name'];
            }
            return $result;
        };

        // Player 1 is a member of A and the target of B.
        $this->assertSame(array('A', 'B'), $names(EnumUnion(1, 0)));
        // With the friendly flag the union that targets player 1 is hidden.
        $this->assertSame(array('A'), $names(EnumUnion(1, 1)));

        // Player 2 is the target of A and a member of B.
        $this->assertSame(array('A', 'B'), $names(EnumUnion(2, 0)));
        $this->assertSame(array('B'), $names(EnumUnion(2, 1)));

        // Player 3 is not involved in any union.
        $this->assertSame(array(), $names(EnumUnion(3, 0)));
    }

    /** EnumUnionFleets() lists the fleets that carry the union id. */
    public function testEnumUnionFleetsListsTheUnionFleets(): void
    {
        $unionId = CreateUnion(2, 'KV1');

        $this->assertSame(1, dbrows(EnumUnionFleets($unionId)));

        dbquery("UPDATE {$this->db}fleet SET union_id = $unionId WHERE fleet_id = 3");

        $fleetIds = array();
        foreach ($this->fetchAll(EnumUnionFleets($unionId)) as $fleet) {
            $fleetIds[] = intval($fleet['fleet_id']);
        }
        $this->assertSame(array(2, 3), $fleetIds);
    }

    /**
     * UpdateUnionTime() moves the other union fleets to a later time and never
     * moves them back unless force_set is given.
     */
    public function testUpdateUnionTimeMovesTheOtherFleetsForward(): void
    {
        $unionId = CreateUnion(2, 'KV1');   // head fleet 2
        $guest = $this->addFleet(array('owner_id' => 1, 'union_id' => $unionId, 'mission' => FTYP_ACS_ATTACK));
        AddQueue(1, QTYP_FLEET, $guest, 0, 0, 5000, 0, QUEUE_PRIO_FLEET + FTYP_ATTACK);

        $this->assertSame(5000, intval(GetFleetQueue($guest)['end']));

        // An earlier time does not pull the fleet back.
        $this->assertSame(5000, UpdateUnionTime($unionId, 4000, 2));
        $this->assertSame(5000, intval(GetFleetQueue($guest)['end']));

        // A later time moves every union fleet except the given one.
        $this->assertSame(7000, UpdateUnionTime($unionId, 7000, 2));
        $this->assertSame(7000, intval(GetFleetQueue($guest)['end']));
    }

    /** force_set = true moves the other union fleets back as well. */
    public function testUpdateUnionTimeForceSetMovesTheFleetsBack(): void
    {
        $unionId = CreateUnion(2, 'KV1');
        $guest = $this->addFleet(array('owner_id' => 1, 'union_id' => $unionId, 'mission' => FTYP_ACS_ATTACK));
        AddQueue(1, QTYP_FLEET, $guest, 0, 0, 5000, 0, QUEUE_PRIO_FLEET + FTYP_ATTACK);

        $this->assertSame(4000, UpdateUnionTime($unionId, 4000, 2, true));
        $this->assertSame(4000, intval(GetFleetQueue($guest)['end']));
    }

    /**
     * A union whose only fleet is the skipped one returns the passed time
     * unchanged (no other fleet is touched).
     */
    public function testUpdateUnionTimeWithOnlyTheSkippedFleetReturnsTheGivenTime(): void
    {
        $unionId = CreateUnion(2, 'KV1');

        $this->assertSame(1234, UpdateUnionTime($unionId, 1234, 2));
    }

    /** UpdateFleetTime() rewrites the queue end of a single fleet. */
    public function testUpdateFleetTimeRewritesTheQueueEnd(): void
    {
        $unionId = CreateUnion(2, 'KV1');
        $guest = $this->addFleet(array('owner_id' => 1, 'union_id' => $unionId, 'mission' => FTYP_ACS_ATTACK));
        AddQueue(1, QTYP_FLEET, $guest, 0, 0, 5000, 0, QUEUE_PRIO_FLEET + FTYP_ATTACK);

        UpdateFleetTime($guest, 4242);

        $this->assertSame(4242, intval(GetFleetQueue($guest)['end']));
    }

    /**
     * GetHoldingFleets() only returns fleets with the ACS hold mission at the
     * given planet and limits the result to uni.acs squared.
     */
    public function testGetHoldingFleetsFiltersByMissionAndUniverseLimit(): void
    {
        $holdMission = FTYP_ORBITING + FTYP_ACS_HOLD;
        $this->addFleet(array('owner_id' => 1, 'mission' => $holdMission, 'target_planet' => 1));
        $this->addFleet(array('owner_id' => 2, 'mission' => $holdMission, 'target_planet' => 1));
        $this->addFleet(array('owner_id' => 2, 'mission' => $holdMission, 'target_planet' => 4));
        // A normal attack fleet at the same planet must not show up.
        $this->addFleet(array('owner_id' => 2, 'mission' => FTYP_ATTACK, 'target_planet' => 1, 'union_id' => 0));

        // uni.acs = 1 -> LIMIT acs * acs = 1.
        $this->assertSame(1, dbrows(GetHoldingFleets(1)));
        $this->assertSame(1, dbrows(GetHoldingFleets(4)));

        dbquery("UPDATE {$this->db}uni SET acs = 2");   // LIMIT 4

        $this->assertSame(2, dbrows(GetHoldingFleets(1)));
        $this->assertSame(1, dbrows(GetHoldingFleets(4)));
        $this->assertSame(0, dbrows(GetHoldingFleets(9999)));
    }

    /** GetUnionUnitsCount() adds up the ships of every fleet of the union. */
    public function testGetUnionUnitsCountSumsTheUnionFleets(): void
    {
        $this->assertSame(0, GetUnionUnitsCount(0));

        $unionId = CreateUnion(2, 'KV1');
        // The head fleet (2) carries 10 light fighters.
        $this->assertSame(10, GetUnionUnitsCount($unionId));

        // Fleet 3 adds 5 small + 2 large cargo; its 5000/2000 resources are not
        // battle units.
        dbquery("UPDATE {$this->db}fleet SET union_id = $unionId WHERE fleet_id = 3");
        $this->assertSame(17, GetUnionUnitsCount($unionId));
    }

    /**
     * GetHoldingUnitsCount() adds the units on the planet (ships + defense
     * structures except the silo missiles) and the units of the holding fleets.
     */
    public function testGetHoldingUnitsCountAddsPlanetAndHoldingFleets(): void
    {
        // PlayerOne's home planet: 61 ships and 13 defense structures (the 2
        // anti-ballistic + 1 interplanetary missile are excluded).
        $this->assertSame(74, GetHoldingUnitsCount(1));

        $holdMission = FTYP_ORBITING + FTYP_ACS_HOLD;
        $this->addFleet(array('owner_id' => 2, 'mission' => $holdMission, 'target_planet' => 1, GID_F_LF => 7));
        $this->addFleet(array('owner_id' => 2, 'mission' => $holdMission, 'target_planet' => 1, GID_F_LF => 100));

        // uni.acs = 2 -> up to 4 holding fleets are counted.
        dbquery("UPDATE {$this->db}uni SET acs = 2");
        $this->assertSame(181, GetHoldingUnitsCount(1));

        // A moon without defenses: 2 small + 4 light fighters.
        $this->assertSame(6, GetHoldingUnitsCount(10));

        // Neither a planet nor holding fleets: nothing to count.
        $this->assertSame(0, GetHoldingUnitsCount(9999));
    }
}
