<?php

declare(strict_types=1);

// Tests for three low-level modules of the game core:
//
//  - game/core/uni.php  : the universe settings row (LoadUniverse, UpdateNews,
//                         DisableNews, SetUniParam, SetExtLinks, SetMaxUsers,
//                         IncrementHackCounter, ResetHackCounter);
//  - game/core/defs.php : the game-wide constants (fleet/message/planet types,
//                         queue types and priorities, user flags, limits and
//                         factors);
//  - game/core/core.php : the core bootstrap that loads every game/core module
//                         in dependency order and declares $GlobalUser,
//                         $from_cron and $CoreVersion.
//
// The tests run against the real database layer with the in-memory SQLite
// backend (DB_CONNECTION=sqlite, DB_DATABASE=:memory:, see phpunit.xml and
// testing/bootstrap.php), so no MySQL server is required. The universe comes
// from FixtureBuilder (the standard 3-player test universe with the real game
// schema), created fresh through the real DB functions for every test.
//
// Every test method runs in its own PHP process (like NotesTest and
// GoldenPagesTest): only the process-isolated child loads the core at the true
// top level, which is where the globals declared at the top level of the core
// files ($GlobalUser, $CoreVersion, $defmap, ...) actually exist.
//
// Test-backend limitation: uni.php escapes free-form text with addslashes(),
// which produces valid literals for the production MySQL backend but has no
// meaning in SQLite (backslash is not an escape character there). The tests
// therefore avoid apostrophes, quotes and backslashes in the stored texts, and
// testUpdateNewsEscapingIsMysqlSpecificAndFailsOnSqlite documents the failure
// mode explicitly.

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class UniCoreTest extends TestCase
{
    protected function setUp(): void
    {
        // Avoid "foreach() argument must be of type array|object, null given"
        // from the ModsExec* hooks when no mod has been initialized.
        $GLOBALS['modlist'] = array ();
        // The core files resolve relative includes against the game directory.
        chdir (__DIR__ . '/../game');
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Create a fresh in-memory database with the real game schema, optionally
     * filled with the standard 3-player test universe (see FixtureBuilder).
     */
    private function createUniverse (bool $seedUniverse = true) : FixtureBuilder
    {
        $fixture = new FixtureBuilder ();
        if ( $seedUniverse ) $fixture->createTestUniverse ('en');
        return $fixture;
    }

    /**
     * Read one column of the first universe row, or null when there is none.
     */
    private function uniValue (string $column) : mixed
    {
        global $db_prefix;
        $result = dbquery ("SELECT `".$column."` AS v FROM ".$db_prefix."uni LIMIT 1");
        $row = dbarray ($result);
        return $row === false ? null : $row['v'];
    }

    /**
     * Number of rows in the universe table.
     */
    private function countUniverseRows () : int
    {
        global $db_prefix;
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM ".$db_prefix."uni");
        $row = dbarray ($result);
        return (int)$row['cnt'];
    }

    /**
     * The name check used by the registration page (reg/new.php) and by the
     * rename option (pages/options.php): any substring match against
     * FORBIDDEN_LOGINS rejects the name.
     */
    private function nameIsForbidden (string $name) : bool
    {
        $lower = mb_strtolower ($name, 'UTF-8');
        foreach ( explode (',', FORBIDDEN_LOGINS) as $forbidden ) {
            if ( strpos ($lower, $forbidden) !== false ) return true;
        }
        return false;
    }

    /**
     * Names of the functions declared at the top level of a core module file.
     * The core modules declare their functions unindented at column 0 (class
     * methods in game/core are indented), so a simple anchored pattern is used.
     *
     * @return string[] Declared function names.
     */
    private function declaredFunctions (string $file) : array
    {
        preg_match_all ('/^function\s+&?(\w+)/m', (string)file_get_contents ($file), $matches);
        return $matches[1];
    }

    // ========================================================================
    // uni.php -- LoadUniverse
    // ========================================================================

    /**
     * The universe row of the fixture universe is returned with all its
     * settings.
     */
    public function testLoadUniverseReturnsTheUniverseSettingsRow () : void
    {
        $this->createUniverse ();

        $uni = LoadUniverse ();

        $this->assertIsArray ($uni);
        $this->assertSame (1, (int)$uni['num']);
        $this->assertSame (1.0, (float)$uni['speed']);
        $this->assertSame (1.0, (float)$uni['fspeed']);
        $this->assertSame (1, (int)$uni['galaxies']);
        $this->assertSame (15, (int)$uni['systems']);
        $this->assertSame (1000, (int)$uni['maxusers']);
        $this->assertSame (0, (int)$uni['freeze']);
        $this->assertSame ('php', (string)$uni['battle_engine']);
        $this->assertSame ('en', (string)$uni['lang']);
        $this->assertSame (0, (int)$uni['hacks']);
        $this->assertSame ('Welcome to the test universe!', (string)$uni['news1']);
        $this->assertSame ('Golden pages test server.', (string)$uni['news2']);
        $this->assertArrayHasKey ('news_until', $uni);
        $this->assertArrayHasKey ('ext_board', $uni);
        $this->assertArrayHasKey ('battle_max', $uni);
    }

    /**
     * Every call starts a new query, so the function can be called repeatedly
     * (dbarray() consumes the row of one result set).
     */
    public function testLoadUniverseCanBeCalledRepeatedly () : void
    {
        $this->createUniverse ();

        $first = LoadUniverse ();
        $second = LoadUniverse ();

        $this->assertIsArray ($first);
        $this->assertSame ($first, $second);
    }

    /**
     * Without a universe row (game not installed) the function returns false.
     */
    public function testLoadUniverseReturnsFalseWhenTheUniverseIsNotInstalled () : void
    {
        $this->createUniverse (false);

        $this->assertSame (0, $this->countUniverseRows ());
        $this->assertFalse (LoadUniverse ());
    }

    // ========================================================================
    // uni.php -- UpdateNews / DisableNews
    // ========================================================================

    /**
     * UpdateNews() stores both texts and sets the expiry to now + days.
     */
    public function testUpdateNewsStoresTextsAndExpiry () : void
    {
        $this->createUniverse ();

        $before = time ();
        UpdateNews ('Maintenance on Sunday', 'New galaxy map released', 7);
        $after = time ();

        $this->assertSame ('Maintenance on Sunday', (string)$this->uniValue ('news1'));
        $this->assertSame ('New galaxy map released', (string)$this->uniValue ('news2'));

        $until = (int)$this->uniValue ('news_until');
        $this->assertGreaterThanOrEqual ($before + 7 * 86400, $until);
        $this->assertLessThanOrEqual ($after + 7 * 86400, $until);
    }

    /**
     * A day count of 0 makes the news expire immediately; multi-byte texts
     * (umlauts, the euro sign, an emoji) survive the round trip. Quotes are
     * left out on purpose: addslashes() escapes them for MySQL only, see
     * testUpdateNewsEscapingIsMysqlSpecificAndFailsOnSqlite.
     */
    public function testUpdateNewsWithZeroDaysAndUnicodeText () : void
    {
        $this->createUniverse ();

        $before = time ();
        UpdateNews ('Neuigkeiten – äöüß € 😀', 'Ankündigung für alle', 0);
        $after = time ();

        $this->assertSame ('Neuigkeiten – äöüß € 😀', (string)$this->uniValue ('news1'));
        $this->assertSame ('Ankündigung für alle', (string)$this->uniValue ('news2'));

        $until = (int)$this->uniValue ('news_until');
        $this->assertGreaterThanOrEqual ($before, $until);
        $this->assertLessThanOrEqual ($after, $until);
    }

    /**
     * UpdateNews() escapes the free-form texts with addslashes(), which is
     * MySQL escaping: '\'' is a valid literal there, but SQLite treats the
     * backslash as a normal character, so the raw UPDATE fails and the old
     * news text stays in place. This documents the behaviour of the module on
     * the SQLite test backend (the production MySQL backend round-trips the
     * apostrophe).
     */
    public function testUpdateNewsEscapingIsMysqlSpecificAndFailsOnSqlite () : void
    {
        $this->createUniverse ();
        global $db_prefix;
        dbquery ("UPDATE ".$db_prefix."uni SET news1 = 'Old news', news2 = 'Old two', news_until = 2000000000");

        // dbquery() echoes the failed statement; capture it to keep the test
        // output clean (and to prove that the UPDATE really was rejected).
        ob_start ();
        UpdateNews ("It's maintenance", "Don't panic", 1);
        $output = ob_get_clean ();

        $this->assertIsString ($output);
        $this->assertNotSame ('', $output, 'the SQLite backend should reject the addslashes()-escaped literal');
        $this->assertSame ('Old news', (string)$this->uniValue ('news1'));
        $this->assertSame ('Old two', (string)$this->uniValue ('news2'));
        $this->assertSame (2000000000, (int)$this->uniValue ('news_until'));
    }

    /**
     * DisableNews() clears the expiry timestamp but keeps both texts.
     */
    public function testDisableNewsClearsTheExpiryButKeepsTheTexts () : void
    {
        $this->createUniverse ();
        global $db_prefix;
        dbquery ("UPDATE ".$db_prefix."uni SET news1 = 'Old news', news2 = 'Old two', news_until = 2000000000");

        DisableNews ();

        $this->assertSame (0, (int)$this->uniValue ('news_until'));
        $this->assertSame ('Old news', (string)$this->uniValue ('news1'));
        $this->assertSame ('Old two', (string)$this->uniValue ('news2'));
    }

    // ========================================================================
    // uni.php -- SetUniParam
    // ========================================================================

    /**
     * SetUniParam() writes every parameter it takes and reloads the global
     * $GlobalUni cache.
     */
    public function testSetUniParamPersistsEveryParameterAndRefreshesTheCache () : void
    {
        $this->createUniverse ();

        SetUniParam (7, 9, 1, 70, 50, 1, 30, 5, 499, 1, 3, 1, 'de', 'js', 0, 123456, 1, 5000, 777, 14);

        $expected = array (
            'speed' => 7, 'fspeed' => 9, 'acs' => 1, 'fid' => 70, 'did' => 50,
            'defrepair' => 1, 'defrepair_delta' => 30, 'galaxies' => 5, 'systems' => 499,
            'rapid' => 1, 'moons' => 3, 'freeze' => 1, 'php_battle' => 0,
            'battle_max' => 123456, 'force_lang' => 1, 'start_dm' => 5000,
            'max_werf' => 777, 'feedage' => 14,
        );
        foreach ( $expected as $column => $value ) {
            $this->assertSame ($value, (int)$this->uniValue ($column), "uni.$column");
        }
        $this->assertSame ('de', (string)$this->uniValue ('lang'));
        $this->assertSame ('js', (string)$this->uniValue ('battle_engine'));

        global $GlobalUni;
        $this->assertIsArray ($GlobalUni);
        $this->assertSame (499, (int)$GlobalUni['systems']);
        $this->assertSame ('de', (string)$GlobalUni['lang']);
        $this->assertSame (123456, (int)$GlobalUni['battle_max']);
    }

    /**
     * SetUniParam() updates a fixed set of columns only: the news, the
     * external links and the counter columns stay untouched.
     */
    public function testSetUniParamLeavesNewsAndExternalLinksUntouched () : void
    {
        $this->createUniverse ();
        UpdateNews ('Keep me', 'And me too', 5);
        SetExtLinks ('https://forum.example', 'https://discord.example', 'https://tutorial.example', 'https://rules.example', 'https://about.example');
        $untilBefore = (int)$this->uniValue ('news_until');

        SetUniParam (2, 2, 0, 0, 0, 0, 0, 1, 15, 0, 1, 0, 'en', 'php', 1, BATTLE_MAX_UNITS, 0, 0, 1000, 0);

        $this->assertSame ('Keep me', (string)$this->uniValue ('news1'));
        $this->assertSame ('And me too', (string)$this->uniValue ('news2'));
        $this->assertSame ($untilBefore, (int)$this->uniValue ('news_until'));
        $this->assertSame ('https://forum.example', (string)$this->uniValue ('ext_board'));
        $this->assertSame ('https://about.example', (string)$this->uniValue ('ext_impressum'));
        $this->assertSame (BATTLE_MAX_UNITS, (int)$this->uniValue ('battle_max'));
    }

    // ========================================================================
    // uni.php -- SetExtLinks
    // ========================================================================

    /**
     * SetExtLinks() stores all five menu links (including query strings) and
     * reloads the global $GlobalUni cache.
     */
    public function testSetExtLinksStoresAllFiveLinks () : void
    {
        $this->createUniverse ();

        SetExtLinks (
            'https://board.example.com/viewforum.php?f=1&start=0',
            'https://discord.gg/abc',
            'https://wiki.example/tutorial',
            'https://wiki.example/rules',
            'https://example.com/about'
        );

        $this->assertSame ('https://board.example.com/viewforum.php?f=1&start=0', (string)$this->uniValue ('ext_board'));
        $this->assertSame ('https://discord.gg/abc', (string)$this->uniValue ('ext_discord'));
        $this->assertSame ('https://wiki.example/tutorial', (string)$this->uniValue ('ext_tutorial'));
        $this->assertSame ('https://wiki.example/rules', (string)$this->uniValue ('ext_rules'));
        $this->assertSame ('https://example.com/about', (string)$this->uniValue ('ext_impressum'));

        $this->assertSame ('https://wiki.example/rules', (string)$GLOBALS['GlobalUni']['ext_rules']);
    }

    /**
     * An empty string hides the corresponding menu item (it clears the link).
     */
    public function testSetExtLinksWithEmptyStringsClearsTheLinks () : void
    {
        $this->createUniverse ();
        SetExtLinks ('https://a.example', 'https://b.example', 'https://c.example', 'https://d.example', 'https://e.example');

        SetExtLinks ('', '', '', '', '');

        foreach ( array ('ext_board', 'ext_discord', 'ext_tutorial', 'ext_rules', 'ext_impressum') as $column ) {
            $this->assertSame ('', (string)$this->uniValue ($column), "uni.$column");
        }
        $this->assertSame ('', (string)$GLOBALS['GlobalUni']['ext_impressum']);
    }

    // ========================================================================
    // uni.php -- SetMaxUsers
    // ========================================================================

    /**
     * SetMaxUsers() writes the new limit and reloads the cached universe.
     */
    public function testSetMaxUsersUpdatesTheValueAndTheCache () : void
    {
        $this->createUniverse ();
        $this->assertSame (1000, (int)$this->uniValue ('maxusers'));

        SetMaxUsers (2500);

        $this->assertSame (2500, (int)$this->uniValue ('maxusers'));
        $this->assertSame (2500, (int)$GLOBALS['GlobalUni']['maxusers']);
    }

    /**
     * A non-positive limit is ignored completely: neither the database row nor
     * the cached universe is touched. The sentinel value in $GlobalUni proves
     * that the early return really skips the reload.
     */
    public function testSetMaxUsersIgnoresNonPositiveValues () : void
    {
        $this->createUniverse ();
        SetMaxUsers (1200);

        $GLOBALS['GlobalUni'] = array ('maxusers' => 424242);

        SetMaxUsers (0);
        $this->assertSame (1200, (int)$this->uniValue ('maxusers'));
        $this->assertSame (424242, (int)$GLOBALS['GlobalUni']['maxusers']);

        SetMaxUsers (-7);
        $this->assertSame (1200, (int)$this->uniValue ('maxusers'));
        $this->assertSame (424242, (int)$GLOBALS['GlobalUni']['maxusers']);
    }

    // ========================================================================
    // uni.php -- IncrementHackCounter / ResetHackCounter
    // ========================================================================

    /**
     * The hack attempt counter is incremented by one and reset to zero.
     */
    public function testHackCounterIncrementsAndResets () : void
    {
        $this->createUniverse ();
        $this->assertSame (0, (int)$this->uniValue ('hacks'));

        IncrementHackCounter ();
        IncrementHackCounter ();
        IncrementHackCounter ();
        $this->assertSame (3, (int)$this->uniValue ('hacks'));

        ResetHackCounter ();
        $this->assertSame (0, (int)$this->uniValue ('hacks'));

        IncrementHackCounter ();
        $this->assertSame (1, (int)$this->uniValue ('hacks'));
    }

    // ========================================================================
    // uni.php -- setters on an empty universe table
    // ========================================================================

    /**
     * All universe setters are simple UPDATEs: without a universe row they
     * affect no rows, create none and must not raise a PHP error. Only
     * SetUniParam() reacts to the missing row, by reloading its cache from the
     * empty table (so the cached universe ends up as false - see the report).
     */
    public function testUniverseSettersTolerateAMissingUniverseRow () : void
    {
        $this->createUniverse (false);
        $this->assertSame (0, $this->countUniverseRows ());

        UpdateNews ('News', 'More news', 3);
        DisableNews ();
        SetExtLinks ('https://a.example', 'https://b.example', 'https://c.example', 'https://d.example', 'https://e.example');
        SetMaxUsers (1200);
        IncrementHackCounter ();
        ResetHackCounter ();

        $this->assertSame (0, $this->countUniverseRows (), 'the setters must not create a universe row');

        SetUniParam (1, 1, 0, 0, 0, 0, 0, 1, 15, 0, 1, 0, 'en', 'php', 1, BATTLE_MAX_UNITS, 0, 0, 1000, 0);

        $this->assertFalse ($GLOBALS['GlobalUni']);
        $this->assertSame (0, $this->countUniverseRows ());
    }

    // ========================================================================
    // defs.php -- fleet mission types
    // ========================================================================

    /**
     * The fleet mission ids and the return/orbit/custom markers. A returning
     * fleet is stored as mission + FTYP_RETURN and a fleet in orbit as
     * mission + FTYP_ORBITING, so the marker ranges must not overlap.
     */
    public function testFleetMissionTypeConstants () : void
    {
        $missions = array (
            FTYP_ATTACK, FTYP_ACS_ATTACK, FTYP_TRANSPORT, FTYP_DEPLOY, FTYP_ACS_HOLD,
            FTYP_SPY, FTYP_COLONIZE, FTYP_RECYCLE, FTYP_DESTROY, FTYP_EXPEDITION,
            FTYP_MISSILE, FTYP_ACS_ATTACK_HEAD,
        );

        $this->assertSame (array (1, 2, 3, 4, 5, 6, 7, 8, 9, 15, 20, 21), $missions);
        $this->assertSame (count ($missions), count (array_unique ($missions)), 'mission ids must be unique');

        $highestMission = max ($missions);
        $this->assertGreaterThan ($highestMission, FTYP_RETURN);
        $this->assertGreaterThan ($highestMission + FTYP_RETURN, FTYP_ORBITING);
        $this->assertGreaterThan ($highestMission + FTYP_ORBITING, FTYP_CUSTOM);

        $this->assertSame (101, FTYP_ATTACK + FTYP_RETURN);
        $this->assertSame (109, FTYP_DESTROY + FTYP_RETURN);
        $this->assertSame (215, FTYP_EXPEDITION + FTYP_ORBITING);
    }

    // ========================================================================
    // defs.php -- message, planet and queue types
    // ========================================================================

    /**
     * The message types (the pm column) are the ids 0..6 and match the
     * Commander message folders.
     */
    public function testMessageTypeConstants () : void
    {
        $types = array (
            MTYP_PM, MTYP_SPY_REPORT, MTYP_BATTLE_REPORT_LINK, MTYP_EXP,
            MTYP_ALLY, MTYP_MISC, MTYP_BATTLE_REPORT_TEXT,
        );

        $this->assertSame (range (0, 6), $types);
        $this->assertSame (0, MTYP_PM);
        $this->assertSame (2, MTYP_BATTLE_REPORT_LINK, 'missile attacks share the battle report link type');
        $this->assertSame (6, MTYP_BATTLE_REPORT_TEXT, 'the battle report body has its own type');
    }

    /**
     * Planet types: 0/1 are the real objects, 10000.. the pseudo objects
     * (debris, phantoms, ...) and PTYP_FARSPACE / PTYP_CUSTOM the expedition
     * and modification range.
     */
    public function testPlanetTypeConstants () : void
    {
        $this->assertSame (0, PTYP_MOON);
        $this->assertSame (1, PTYP_PLANET);
        $this->assertSame (10000, PTYP_DF);
        $this->assertSame (10001, PTYP_DEST_PLANET);
        $this->assertSame (10002, PTYP_COLONY_PHANTOM);
        $this->assertSame (10003, PTYP_DEST_MOON);
        $this->assertSame (10004, PTYP_ABANDONED);
        $this->assertSame (20000, PTYP_FARSPACE);
        $this->assertSame (20001, PTYP_CUSTOM);

        $dbTypes = array (
            PTYP_MOON, PTYP_PLANET, PTYP_DF, PTYP_DEST_PLANET, PTYP_COLONY_PHANTOM,
            PTYP_DEST_MOON, PTYP_ABANDONED, PTYP_FARSPACE, PTYP_CUSTOM,
        );
        $this->assertSame (count ($dbTypes), count (array_unique ($dbTypes)), 'planet types must be unique');
        $this->assertLessThan (PTYP_DF, PTYP_MOON);
        $this->assertLessThan (PTYP_DF, PTYP_PLANET);
        $this->assertSame (PTYP_DF + 4, PTYP_ABANDONED, 'the pseudo object block is contiguous');
        $this->assertSame (PTYP_FARSPACE + 1, PTYP_CUSTOM);
        $this->assertSame (max ($dbTypes), PTYP_CUSTOM, 'custom objects start at the highest type');
    }

    /**
     * The three "game planet types" used for the in-game display are 1..3 and
     * collapse the database types (see GetPlanetType in planet.php).
     */
    public function testGamePlanetTypeConstants () : void
    {
        $this->assertSame (1, GAME_PTYP_PLANET);
        $this->assertSame (2, GAME_PTYP_DF);
        $this->assertSame (3, GAME_PTYP_MOON);
        $this->assertSame (array (1, 2, 3), array (GAME_PTYP_PLANET, GAME_PTYP_DF, GAME_PTYP_MOON));
    }

    /**
     * The queue task types are stored as strings, so they must be unique,
     * non-empty and free of stray whitespace.
     */
    public function testQueueTypeConstantsAreUniqueNonEmptyStrings () : void
    {
        $types = array (
            QTYP_UNBAN, QTYP_CHANGE_EMAIL, QTYP_ALLOW_NAME, QTYP_ALLOW_ATTACKS, QTYP_UNLOAD_ALL,
            QTYP_CLEAN_DEBRIS, QTYP_CLEAN_PLANETS, QTYP_CLEAN_PLAYERS, QTYP_UPDATE_STATS,
            QTYP_RECALC_POINTS, QTYP_RECALC_ALLY_POINTS, QTYP_BUILD, QTYP_DEMOLISH,
            QTYP_RESEARCH, QTYP_SHIPYARD, QTYP_FLEET, QTYP_DEBUG, QTYP_AI, QTYP_COUPON,
            QTYP_FARSPACE_COOLDOWN, QTYP_CLEAN_FARSPACE,
        );

        $this->assertCount (21, $types);
        foreach ( $types as $type ) {
            $this->assertIsString ($type);
            $this->assertNotSame ('', $type);
            $this->assertSame (trim ($type), $type);
        }
        $this->assertSame (count ($types), count (array_unique ($types)), 'queue type strings must not collide');

        $this->assertSame ('Build', QTYP_BUILD);
        $this->assertSame ('Demolish', QTYP_DEMOLISH);
        $this->assertSame ('Research', QTYP_RESEARCH);
        $this->assertSame ('Shipyard', QTYP_SHIPYARD);
        $this->assertSame ('Fleet', QTYP_FLEET);
    }

    /**
     * The queue priorities are ordered, unique and leave the debug priority at
     * the very end of the queue.
     */
    public function testQueuePriorityConstantsAreOrderedAndUnique () : void
    {
        $ordered = array (
            QUEUE_PRIO_LOWEST, QUEUE_PRIO_BUILD, QUEUE_PRIO_FLEET,
            QUEUE_PRIO_RECALC_ALLY_POINTS, QUEUE_PRIO_RECALC_POINTS, QUEUE_PRIO_UPDATE_STATS,
            QUEUE_PRIO_COUPON, QUEUE_PRIO_CLEAN_DEBRIS, QUEUE_PRIO_FARSPACE_COOLDOWN,
            QUEUE_PRIO_CLEAN_PLANETS, QUEUE_PRIO_CLEAN_FARSPACE, QUEUE_PRIO_RELOGIN,
            QUEUE_PRIO_CLEAN_PLAYERS, QUEUE_PRIO_BOT,
        );

        $this->assertSame (array (0, 20, 200, 400, 500, 510, 520, 600, 610, 700, 710, 777, 900, 1000), $ordered);
        $this->assertSame ($ordered, array_values (array_unique ($ordered)), 'queue priorities must be unique');
        $this->assertSame (9999, QUEUE_PRIO_DEBUG);
        $this->assertGreaterThan (QUEUE_PRIO_BOT, QUEUE_PRIO_DEBUG, 'debug events run after every other task');
        $this->assertSame (QUEUE_PRIO_FLEET + FTYP_ATTACK, 201, 'fleet tasks encode the mission in the priority');
    }

    // ========================================================================
    // defs.php -- user flags and types
    // ========================================================================

    /**
     * Every user flag is a single bit, so the flags can be combined with OR.
     */
    public function testUserFlagConstantsAreSingleBits () : void
    {
        $flags = array (
            USER_FLAG_SHOW_ESPIONAGE_BUTTON, USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON,
            USER_FLAG_SHOW_BUDDY_BUTTON, USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON,
            USER_FLAG_SHOW_VIEW_REPORT_BUTTON, USER_FLAG_DONT_USE_FOLDERS,
            USER_FLAG_PARTIAL_REPORTS, USER_FLAG_FOLDER_ESPIONAGE, USER_FLAG_FOLDER_COMBAT,
            USER_FLAG_FOLDER_EXPEDITION, USER_FLAG_FOLDER_ALLIANCE, USER_FLAG_FOLDER_PLAYER,
            USER_FLAG_FOLDER_OTHER, USER_FLAG_HIDE_GO_EMAIL, USER_FLAG_FEED_ENABLE,
            USER_FLAG_FEED_ATOM,
        );

        $this->assertCount (16, $flags);
        foreach ( $flags as $flag ) {
            $this->assertGreaterThan (0, $flag);
            $this->assertSame (0, $flag & ($flag - 1), "flag $flag is not a single bit");
        }
        $this->assertSame (count ($flags), count (array_unique ($flags)), 'user flags must not collide');
    }

    /**
     * A new player starts with exactly the five galaxy action buttons enabled
     * (0x1F) and without the message folders, partial reports or the feed.
     */
    public function testDefaultUserFlagsCombineTheFiveGalaxyButtons () : void
    {
        $buttons = USER_FLAG_SHOW_ESPIONAGE_BUTTON | USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON
            | USER_FLAG_SHOW_BUDDY_BUTTON | USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON
            | USER_FLAG_SHOW_VIEW_REPORT_BUTTON;

        $this->assertSame (0x1F, $buttons);
        $this->assertSame ($buttons, USER_FLAG_DEFAULT);

        $this->assertSame (0, USER_FLAG_DEFAULT & USER_FLAG_DONT_USE_FOLDERS);
        $this->assertSame (0, USER_FLAG_DEFAULT & USER_FLAG_PARTIAL_REPORTS);
        $this->assertSame (0, USER_FLAG_DEFAULT & USER_FLAG_FOLDER_ESPIONAGE);
        $this->assertSame (0, USER_FLAG_DEFAULT & USER_FLAG_HIDE_GO_EMAIL);
        $this->assertSame (0, USER_FLAG_DEFAULT & USER_FLAG_FEED_ENABLE);

        $folders = array (
            USER_FLAG_FOLDER_ESPIONAGE, USER_FLAG_FOLDER_COMBAT, USER_FLAG_FOLDER_EXPEDITION,
            USER_FLAG_FOLDER_ALLIANCE, USER_FLAG_FOLDER_PLAYER, USER_FLAG_FOLDER_OTHER,
        );
        $this->assertCount (6, array_unique ($folders), 'one folder flag per message type');
    }

    /**
     * The account types, the officer ids and the special technical accounts.
     */
    public function testUserTypeAndOfficerConstants () : void
    {
        $this->assertSame (0, USER_TYPE_PLAYER);
        $this->assertSame (1, USER_TYPE_GO);
        $this->assertSame (2, USER_TYPE_ADMIN);
        $this->assertSame (
            array (USER_TYPE_PLAYER, USER_TYPE_GO, USER_TYPE_ADMIN),
            array_unique (array (USER_TYPE_PLAYER, USER_TYPE_GO, USER_TYPE_ADMIN))
        );

        $this->assertSame (1, USER_LEGOR);
        $this->assertSame (99999, USER_SPACE, 'the technical account owns global events and empty galaxy objects');
        $this->assertGreaterThan (USER_TYPE_ADMIN, USER_SPACE);

        $officers = array (
            USER_OFFICER_COMMANDER, USER_OFFICER_ADMIRAL, USER_OFFICER_ENGINEER,
            USER_OFFICER_GEOLOGE, USER_OFFICER_TECHNOCRATE,
        );
        $this->assertSame (array (1, 2, 3, 4, 5), $officers, 'officer ids index the officer list');
    }

    // ========================================================================
    // defs.php -- limits, factors and cooldowns
    // ========================================================================

    /**
     * The game-wide limits and the duration factors used by TechDuration().
     */
    public function testLimitAndFactorConstants () : void
    {
        $this->assertSame (9, MAX_PLANET, 'home planet + 8 colonies');
        $this->assertSame (99, MAX_BUILDINGS_LEVEL);
        $this->assertSame (99, MAX_RESEARCH_LEVEL);
        $this->assertSame (99, MAX_SHIPYARD_ORDERS);

        $this->assertSame (5000, RF_MAX);
        $this->assertSame (100000, RF_DICE, 'rapid fire is thrown as 1d' . RF_DICE);
        $this->assertGreaterThan (RF_MAX, RF_DICE);
        $this->assertSame (6, BATTLE_MAX_ROUND);
        $this->assertSame (1000000, BATTLE_MAX_UNITS);

        $this->assertSame (2500, PROD_BUILDING_DURATION_FACTOR);
        $this->assertSame (2500, PROD_SHIPYARD_DURATION_FACTOR);
        $this->assertSame (1000, PROD_RESEARCH_DURATION_FACTOR);

        $this->assertSame (10, GALAXY_DEUTERIUM_CONS, 'deuterium cost of viewing the galaxy');
        $this->assertSame (300, GALAXY_PHANTOM_DEBRIS, 'smaller debris fields stay invisible');
        $this->assertSame (2500, TRADER_DM, 'cost of calling the merchant');
        $this->assertSame (5000, USER_NOOB_LIMIT);
    }

    /**
     * The expedition visit counter cools down by 3 every hour (issue #174), so
     * a farspace position can be visited three times an hour before it starts
     * to deplete.
     */
    public function testExpeditionCooldownConstants () : void
    {
        $this->assertSame (3600, EXPEDITION_COOLDOWN_PERIOD);
        $this->assertSame (3, EXPEDITION_COOLDOWN_PER_HOUR);
        $this->assertSame (1, (int)(EXPEDITION_COOLDOWN_PERIOD / 3600), 'the cooldown ticks once per hour');
    }

    /**
     * The contact address of the accessibility menu entry is a valid e-mail.
     */
    public function testBarrierefreiEmailConstant () : void
    {
        $this->assertSame ('barrierefrei@ogame.de', EMAIL_BARRIERFREI);
        $this->assertNotFalse (filter_var (EMAIL_BARRIERFREI, FILTER_VALIDATE_EMAIL));
    }

    /**
     * BATTLE_MAX_UNITS is the default of the uni.battle_max column: a universe
     * row created without that column (as FixtureBuilder does) falls back to
     * the constant from defs.php (see install_tabs.php).
     */
    public function testBattleMaxUnitsIsTheSchemaDefaultOfTheUniverseRow () : void
    {
        $this->createUniverse ();

        $uni = LoadUniverse ();

        $this->assertIsArray ($uni);
        $this->assertSame (BATTLE_MAX_UNITS, (int)$uni['battle_max']);
    }

    /**
     * FORBIDDEN_LOGINS is a comma-separated list checked with strpos() against
     * the lowercased name (reg/new.php, pages/options.php), so any substring
     * match is rejected.
     */
    public function testForbiddenLoginsListBlocksNamesBySubstring () : void
    {
        $forbidden = explode (',', FORBIDDEN_LOGINS);

        $this->assertGreaterThan (20, count ($forbidden));
        $this->assertSame (count ($forbidden), count (array_unique ($forbidden)));
        foreach ( $forbidden as $name ) {
            $this->assertNotSame ('', $name);
            $this->assertSame (trim ($name), $name, 'entries must not carry stray whitespace');
            $this->assertSame (mb_strtolower ($name, 'UTF-8'), $name, 'entries are compared against a lowercased name');
        }

        $this->assertContains ('admin', $forbidden);
        $this->assertContains ('legor', $forbidden);
        $this->assertContains ('space', $forbidden, 'the name of the technical account must be blocked');

        $this->assertTrue ($this->nameIsForbidden ('Space'));
        $this->assertTrue ($this->nameIsForbidden ('Admin'));
        $this->assertTrue ($this->nameIsForbidden ('xAdolfx'));
        $this->assertTrue ($this->nameIsForbidden ('Bingo'), 'substring matches are rejected ("bin")');
        $this->assertFalse ($this->nameIsForbidden ('Commander'));
        $this->assertFalse ($this->nameIsForbidden ('PlayerOne'));
    }

    // ========================================================================
    // core.php
    // ========================================================================

    /**
     * core.php declares the cache of the logged-in user and the cron flag.
     * Both are initialised before any page code runs.
     */
    public function testCoreBootstrapInitialisesItsGlobalState () : void
    {
        $this->assertArrayHasKey ('GlobalUser', $GLOBALS);
        $this->assertIsArray ($GLOBALS['GlobalUser']);
        $this->assertSame (array (), $GLOBALS['GlobalUser'], 'no user is logged in while the core is loading');

        $this->assertArrayHasKey ('from_cron', $GLOBALS);
        $this->assertFalse ($GLOBALS['from_cron'], 'a page request is not a cron run');
    }

    /**
     * The core version constant used by modifications for compatibility checks.
     */
    public function testCoreVersionIsDeclared () : void
    {
        $this->assertArrayHasKey ('CoreVersion', $GLOBALS, 'core.php declares the global $CoreVersion');
        $this->assertSame ('1.0.0.0', $GLOBALS['CoreVersion']);
        $this->assertMatchesRegularExpression ('/^\d+\.\d+\.\d+\.\d+$/', $GLOBALS['CoreVersion']);
    }

    /**
     * core.php loads the complete game core: every module in its require list
     * exists, contributes its functions to the running process and is required
     * exactly once.
     */
    public function testCoreLoadsEveryModuleOfTheGameCore () : void
    {
        $coreFile = __DIR__ . '/../game/core/core.php';
        $this->assertFileExists ($coreFile);

        preg_match_all ('/require_once\s+"([^"]+)";/', (string)file_get_contents ($coreFile), $matches);
        $modules = $matches[1];

        $this->assertGreaterThanOrEqual (30, count ($modules), 'core.php loads the complete game core');
        $this->assertSame (array_values (array_unique ($modules)), $modules, 'no module may be required twice');

        foreach ( $modules as $module ) {
            $path = __DIR__ . '/../game/core/' . $module;
            $this->assertFileExists ($path, "core.php requires game/core/$module");

            $functions = $this->declaredFunctions ($path);
            if ( $module === 'defs.php' ) {
                // defs.php only declares constants.
                $this->assertSame (array (), $functions);
                continue;
            }
            $this->assertNotEmpty ($functions, "game/core/$module should declare functions");
            foreach ( $functions as $function ) {
                $this->assertTrue (
                    function_exists ($function),
                    "function $function() declared in game/core/$module must be loaded by core.php"
                );
            }
        }

        // One representative function per module (defs.php has none), so a
        // module that stops contributing code is caught with a clear message.
        $representative = array (
            'db.php' => 'DB_ConnectionType',
            'utils.php' => 'nicenum',
            'techs.php' => 'IsResearch',
            'loca.php' => 'loca',
            'bbcode.php' => 'bb',
            'uni.php' => 'LoadUniverse',
            'prod.php' => 'TechPrice',
            'planet.php' => 'LoadPlanet',
            'user.php' => 'LoadUser',
            'msg.php' => 'SendMessage',
            'notes.php' => 'AddNote',
            'queue.php' => 'AddQueue',
            'page.php' => 'UserSkin',
            'ally.php' => 'CreateAlly',
            'allyapps.php' => 'AddApplication',
            'allyranks.php' => 'AddRank',
            'buddy.php' => 'AddBuddy',
            'fleet.php' => 'FlightSpeed',
            'acs.php' => 'CreateUnion',
            'expedition.php' => 'ExpPoints',
            'battle.php' => 'CalcLosses',
            'battle_report.php' => 'BattleReport',
            'expedition_battle.php' => 'ExpeditionBattle',
            'battle_engine.php' => 'hex_array_to_text',
            'raketen.php' => 'RocketAttackMain',
            'graviton.php' => 'GravitonAttack',
            'debug.php' => 'Debug',
            'bot.php' => 'AddBot',
            'botapi.php' => 'BotExec',
            'coupon.php' => 'CheckCoupon',
            'mods.php' => 'ModsInit',
        );

        $this->assertCount (count ($modules) - 1, $representative, 'every module except defs.php needs a representative');
        foreach ( $representative as $module => $function ) {
            $this->assertContains ($module, $modules, "core.php should require game/core/$module");
            $this->assertTrue (function_exists ($function), "game/core/$module should provide $function()");
        }
    }
}
