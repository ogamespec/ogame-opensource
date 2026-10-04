<?php

declare(strict_types=1);

// Tests for three small game core modules:
//
//  - game/core/notes.php : the private player notes
//    (LoadNote / AddNote / UpdateNote / DelNote / EnumNotes);
//  - game/core/loca.php  : the file based localization engine
//    (loca / loca_lang / loca_add);
//  - game/core/utils.php : assorted helpers (method, scriptname, hostname,
//    nicenum, RedirectHome, va, sksort, localhost, SecureText, CheckParams,
//    array_insert_after_key, array_insert_before_key, gen_trivial_password,
//    DurationFormat, FloatEqual, isValidEmail, ...).
//
// Infrastructure: testing/bootstrap.php loads the whole game core with the
// in-memory SQLite backend (DB_CONNECTION=sqlite, DB_DATABASE=:memory:, see
// phpunit.xml); testing/FixtureBuilder.php creates the real game schema plus a
// 3-player universe. Only the tests that need database rows call universe()
// (lazily), the loca/utils tests stay database free.
//
// Every test method runs in a separate PHP process, so each one starts with an
// empty in-memory database and an empty $LOCA table.
//
// Deliberately NOT covered:
//  - mail_utf8(): it calls PHP's mail(), which needs a working MTA (or would at
//    least emit a transport warning), so there is nothing deterministic to
//    assert here;
//  - RunBackgroundProcess(): covered on POSIX only (on Windows it shells out to
//    "start /B" and always returns 0, see its test).

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class NotesLocaUtilsCoreTest extends TestCase
{
    protected function setUp(): void
    {
        // loca_add() resolves the loca files relative to the game directory.
        chdir (__DIR__ . '/../game');

        global $db_prefix, $db_name, $db_host, $db_user, $db_pass;
        global $UserCache, $LOCA, $loca_lang, $Languages, $StartPage;

        $db_prefix = 'test_';
        $db_name = 'test';
        $db_host = '';
        $db_user = '';
        $db_pass = '';
        $UserCache = array ();
        // A fresh, empty translation table for every test.
        $LOCA = array ();
        $loca_lang = 'en';
        $StartPage = 'index.php';
        // The bootstrap already provides the real list; the fallback only makes
        // loca_add() usable if the global was lost.
        if (!isset ($Languages) || !is_array ($Languages)) {
            $Languages = array ('de' => 'Deutsch', 'en' => 'English', 'ru' => 'Русский');
        }

        // Connect the (fresh) in-memory database. The schema and the rows are
        // created by universe() in the tests that need game data.
        InitDB ();
    }

    /**
     * Build the standard 3-player test universe (real game schema + rows).
     *
     * Player 1 owns one note ("Test Note", note_id 1) and players 2 and 3 have
     * no notes at all, so both cases can be exercised.
     */
    private function universe () : FixtureBuilder
    {
        $builder = new FixtureBuilder ();
        $builder->createTestUniverse ('en');
        return $builder;
    }

    // Insert a user row and return its player id.
    private function addUser (int $id, string $name, string $lang, int $admin) : int
    {
        return AddDBRow (array (
            'player_id' => $id,
            'name' => $name,
            'oname' => $name,
            'lang' => $lang,
            'admin' => $admin,
            'validated' => 1,
        ), "users");
    }

    // Insert a note row directly (bypassing AddNote) and return its id.
    private function addNote (int $ownerId, string $subj, string $text, int $prio, int $date) : int
    {
        return AddDBRow (array (
            'owner_id' => $ownerId,
            'subj' => $subj,
            'text' => $text,
            'textsize' => mb_strlen ($text, 'UTF-8'),
            'prio' => $prio,
            'date' => $date,
        ), "notes");
    }

    // Read a note row from the database, or null if it does not exist.
    private function getNote (int $noteId) : ?array
    {
        $result = dbquery ("SELECT * FROM test_notes WHERE note_id = $noteId");
        $row = dbarray ($result);
        return $row === false ? null : $row;
    }

    // Read the most recently inserted note of a player.
    private function getLastNote (int $ownerId) : ?array
    {
        $result = dbquery ("SELECT * FROM test_notes WHERE owner_id = $ownerId ORDER BY note_id DESC LIMIT 1");
        $row = dbarray ($result);
        return $row === false ? null : $row;
    }

    private function countNotes (int $ownerId) : int
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM test_notes WHERE owner_id = $ownerId");
        $row = dbarray ($result);
        return intval ($row['cnt']);
    }

    private function countAllNotes () : int
    {
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM test_notes");
        $row = dbarray ($result);
        return intval ($row['cnt']);
    }

    // ========================================================================
    // game/core/notes.php
    // ========================================================================

    /**
     * LoadNote() returns the row when note id and owner id match.
     */
    public function testLoadNoteReturnsTheRowOfItsOwner () : void
    {
        $this->universe ();

        $note = LoadNote (1, 1);

        $this->assertIsArray ($note);
        $this->assertSame (1, intval ($note['note_id']));
        $this->assertSame (1, intval ($note['owner_id']));
        $this->assertSame ('Test Note', $note['subj']);
        $this->assertSame ('This is a test note for PlayerOne.', $note['text']);
    }

    /**
     * LoadNote() returns false for an unknown note and for a note of another
     * player (the owner id is part of the lookup).
     */
    public function testLoadNoteReturnsFalseForUnknownAndForeignNotes () : void
    {
        $this->universe ();

        $this->assertFalse (LoadNote (2, 1));      // note 1 belongs to player 1
        $this->assertFalse (LoadNote (1, 999999)); // no such note
    }

    /**
     * AddNote() stores owner, subject, text, size, priority and timestamp.
     */
    public function testAddNoteStoresAllFieldsOfTheNewNote () : void
    {
        $this->universe ();
        $before = time ();

        AddNote (2, 'Subject', 'Some note text', 1);
        $after = time ();

        $note = $this->getLastNote (2);

        $this->assertNotNull ($note);
        $this->assertSame (2, intval ($note['owner_id']));
        $this->assertSame ('Subject', $note['subj']);
        $this->assertSame ('Some note text', $note['text']);
        $this->assertSame (mb_strlen ('Some note text', 'UTF-8'), intval ($note['textsize']));
        $this->assertSame (1, intval ($note['prio']));
        $this->assertGreaterThanOrEqual ($before, intval ($note['date']));
        $this->assertLessThanOrEqual ($after, intval ($note['date']));
    }

    /**
     * An empty subject/text is replaced with the localized placeholder of the
     * note owner's language (game/loca/en_en/notes.php).
     */
    public function testAddNoteReplacesEmptySubjectAndTextWithPlaceholders () : void
    {
        $this->universe ();

        AddNote (2, '', '', 0);

        $note = $this->getLastNote (2);
        $this->assertNotNull ($note);
        $this->assertSame ('no subject', $note['subj']);
        $this->assertSame ('no text', $note['text']);
    }

    /**
     * The priority is clamped to the 0..2 range.
     */
    public function testAddNoteClampsPriorityToTheValidRange () : void
    {
        $this->universe ();

        foreach (array (-1, 0, 1, 2, 3, 99) as $prio) {
            AddNote (2, 'P', 'T', $prio);
            $expected = max (0, min (2, $prio));
            $this->assertSame ($expected, intval ($this->getLastNote (2)['prio']));
        }
    }

    /**
     * The subject is cut to 30 and the text to 5000 characters (multi-byte
     * aware), and the stored size counts characters, not bytes.
     */
    public function testAddNoteTruncatesMultibyteSubjectAndText () : void
    {
        $this->universe ();

        AddNote (2, str_repeat ('Ж', 40), str_repeat ('Ж', 6000), 0);

        $note = $this->getLastNote (2);
        $this->assertNotNull ($note);
        $this->assertSame (30, mb_strlen ($note['subj'], 'UTF-8'));
        $this->assertSame (5000, mb_strlen ($note['text'], 'UTF-8'));
        $this->assertSame (5000, intval ($note['textsize']));
    }

    /**
     * AddNote() for a player that does not exist inserts nothing (LoadUser is
     * the guard).
     */
    public function testAddNoteForAnUnknownPlayerInsertsNothing () : void
    {
        $this->universe ();
        $before = $this->countAllNotes ();

        AddNote (999, 'Subject', 'Text', 1);

        $this->assertSame ($before, $this->countAllNotes ());
    }

    /**
     * UpdateNote() replaces the values and refreshes the timestamp.
     */
    public function testUpdateNoteStoresTheNewValuesAndTimestamp () : void
    {
        $this->universe ();
        $this->addNote (2, 'Old', 'Old text', 0, time () - 3600);
        $noteId = intval ($this->getLastNote (2)['note_id']);

        $before = time ();
        UpdateNote (2, $noteId, 'Updated Subject', 'Updated Text Content', 2);
        $after = time ();

        $note = $this->getNote ($noteId);
        $this->assertNotNull ($note);
        $this->assertSame ('Updated Subject', $note['subj']);
        $this->assertSame ('Updated Text Content', $note['text']);
        $this->assertSame (20, intval ($note['textsize']));
        $this->assertSame (2, intval ($note['prio']));
        $this->assertGreaterThanOrEqual ($before, intval ($note['date']));
        $this->assertLessThanOrEqual ($after, intval ($note['date']));
    }

    /**
     * Foreign notes and unknown note ids are silently left alone.
     */
    public function testUpdateNoteLeavesForeignAndUnknownNotesUntouched () : void
    {
        $this->universe ();

        // Note 1 belongs to player 1, so player 2 may not touch it.
        UpdateNote (2, 1, 'Hacked', 'Hacked', 2);
        $note = $this->getNote (1);
        $this->assertNotNull ($note);
        $this->assertSame ('Test Note', $note['subj']);
        $this->assertSame ('This is a test note for PlayerOne.', $note['text']);

        // An unknown note id must not create a row either.
        $before = $this->countAllNotes ();
        UpdateNote (1, 999999, 'New', 'New', 0);
        $this->assertSame ($before, $this->countAllNotes ());
    }

    /**
     * UpdateNote() applies the same placeholder and priority rules as AddNote().
     */
    public function testUpdateNoteUsesPlaceholdersAndClampsPriority () : void
    {
        $this->universe ();

        UpdateNote (1, 1, '', '', 7);

        $note = $this->getNote (1);
        $this->assertNotNull ($note);
        $this->assertSame ('no subject', $note['subj']);
        $this->assertSame ('no text', $note['text']);
        $this->assertSame (2, intval ($note['prio']));
    }

    /**
     * UpdateNote() truncates multi-byte input like AddNote() does.
     */
    public function testUpdateNoteTruncatesMultibyteSubjectAndText () : void
    {
        $this->universe ();

        UpdateNote (1, 1, str_repeat ('Ж', 40), str_repeat ('Ж', 6000), 0);

        $note = $this->getNote (1);
        $this->assertNotNull ($note);
        $this->assertSame (30, mb_strlen ($note['subj'], 'UTF-8'));
        $this->assertSame (5000, mb_strlen ($note['text'], 'UTF-8'));
        $this->assertSame (5000, intval ($note['textsize']));
    }

    /**
     * UpdateNote() escapes the values with addslashes() and interpolates them
     * into raw SQL. On the SQLite backend the backslash is NOT an escape
     * character, so the escaped backslashes survive into the stored text (on
     * MySQL they are consumed by the string literal and the original text is
     * stored). The test documents the current SQLite behaviour.
     */
    public function testUpdateNoteKeepsTheAddslashesEscapesOfQuotesAndBackslashes () : void
    {
        $this->universe ();

        UpdateNote (1, 1, 'He said "hi"', 'back\slash', 1);

        $note = $this->getNote (1);
        $this->assertNotNull ($note);
        $this->assertSame ('He said \\"hi\\"', $note['subj']);
        $this->assertSame ('back\\\\slash', $note['text']);
        // textsize is computed from the escaped string ("back\\slash" = 11 chars).
        $this->assertSame (11, intval ($note['textsize']));
    }

    /**
     * SUSPECTED BUG (reported, not fixed): UpdateNote() builds raw SQL and
     * relies on addslashes() for escaping. A single quote inside the subject or
     * text therefore terminates the SQL string literal under SQLite (and any
     * standard-SQL engine), the whole UPDATE fails, dbquery() echoes the query
     * plus the SQLite error and returns false, and the note silently keeps its
     * old content -- the player's edit is lost. On MySQL the same call works
     * because backslash escapes are enabled by default, so this is a
     * backend-dependent bug (AddNote() uses AddDBRow() and is not affected).
     */
    public function testUpdateNoteWithAnApostropheFailsOnTheSqliteBackend () : void
    {
        $this->universe ();
        // Put the note into a known state with an input that needs no escaping.
        UpdateNote (1, 1, 'Before', 'Before text', 0);

        ob_start ();
        UpdateNote (1, 1, "O'Brien", "it's", 2);
        $errorOutput = (string) ob_get_clean ();

        $note = $this->getNote (1);
        $this->assertNotNull ($note);
        $this->assertSame ('Before', $note['subj']);
        $this->assertSame ('Before text', $note['text']);
        $this->assertSame (0, intval ($note['prio']));

        // The rejected statement was echoed by dbquery() (it returns false).
        $this->assertStringContainsString ("O\\'Brien", $errorOutput);
        // The table is still intact.
        $this->assertSame (1, $this->countAllNotes ());
    }

    /**
     * DelNote() deletes the player's own note and refuses foreign notes.
     */
    public function testDelNoteDeletesTheOwnNoteOnly () : void
    {
        $this->universe ();
        $this->addNote (2, 'To delete', 'Body', 0, time ());
        $noteId = intval ($this->getLastNote (2)['note_id']);

        DelNote (2, $noteId);

        $this->assertFalse (LoadNote (2, $noteId));
        $this->assertSame (0, $this->countNotes (2));

        // Note 1 belongs to player 1 and must survive player 2's delete.
        DelNote (2, 1);
        $this->assertIsArray (LoadNote (1, 1));
    }

    /**
     * EnumNotes() lists the notes newest first (ORDER BY date DESC).
     */
    public function testEnumNotesReturnsNewestFirst () : void
    {
        $this->universe ();
        $now = time ();
        $this->addNote (2, 'Oldest', 'T', 0, $now - 300);
        $this->addNote (2, 'Newest', 'T', 0, $now);
        $this->addNote (2, 'Middle', 'T', 0, $now - 150);

        $result = EnumNotes (2);
        $subjects = array ();
        while ($row = dbarray ($result)) $subjects[] = $row['subj'];

        $this->assertSame (array ('Newest', 'Middle', 'Oldest'), $subjects);
    }

    /**
     * A regular player sees at most 20 notes (LIMIT 20).
     */
    public function testEnumNotesLimitsRegularPlayersToTwentyNotes () : void
    {
        $this->universe ();
        $now = time ();
        for ($i = 0; $i < 25; $i++) {
            $this->addNote (2, 'Note ' . $i, 'T', 0, $now - $i);
        }

        $result = EnumNotes (2);

        $this->assertSame (20, dbrows ($result));
        $this->assertSame ('Note 0', dbarray ($result)['subj']);
    }

    /**
     * An administrator sees up to 150 notes (LIMIT 150).
     */
    public function testEnumNotesLimitsAdminsToAHundredFiftyNotes () : void
    {
        $this->universe ();
        $this->addUser (4, 'Admin', 'en', 1);
        $now = time ();
        for ($i = 0; $i < 160; $i++) {
            $this->addNote (4, 'Admin note ' . $i, 'T', 0, $now - $i);
        }

        $result = EnumNotes (4);

        $this->assertSame (150, dbrows ($result));
        $this->assertSame ('Admin note 0', dbarray ($result)['subj']);
        $this->assertGreaterThan (USER_TYPE_PLAYER, intval (LoadUser (4)['admin']));
    }

    // ========================================================================
    // game/core/loca.php
    // ========================================================================

    /**
     * Without a loaded translation loca() returns the key itself.
     */
    public function testLocaReturnsTheKeyWhenNoTranslationIsLoaded () : void
    {
        $this->assertSame ('SOME_MISSING_KEY', loca ('SOME_MISSING_KEY'));
    }

    /**
     * loca() reads $LOCA for the language in $loca_lang and falls back to the
     * key for unknown languages.
     */
    public function testLocaReturnsTheTranslationOfTheCurrentLanguage () : void
    {
        global $LOCA, $loca_lang;
        $LOCA['en']['GREETING'] = 'Hello';
        $LOCA['de']['GREETING'] = 'Hallo';

        $loca_lang = 'en';
        $this->assertSame ('Hello', loca ('GREETING'));

        $loca_lang = 'de';
        $this->assertSame ('Hallo', loca ('GREETING'));

        $loca_lang = 'ru';
        $this->assertSame ('GREETING', loca ('GREETING'));
    }

    /**
     * loca_lang() takes the language from the parameter, not from $loca_lang.
     */
    public function testLocaLangUsesTheExplicitLanguage () : void
    {
        global $LOCA, $loca_lang;
        $LOCA['en']['KEY'] = 'English';
        $LOCA['ru']['KEY'] = 'Русский';
        $loca_lang = 'en';

        $this->assertSame ('English', loca_lang ('KEY', 'en'));
        $this->assertSame ('Русский', loca_lang ('KEY', 'ru'));
        $this->assertSame ('KEY', loca_lang ('KEY', 'de'));
    }

    /**
     * loca_add() really includes the language file and fills $LOCA; several
     * languages of the same and of different sections can coexist.
     */
    public function testLocaAddLoadsRealLanguageSections () : void
    {
        loca_add ('common', 'en');
        $this->assertSame ('OGame', loca_lang ('OGAME_INT', 'en'));
        $this->assertSame ('d', loca_lang ('TIME_DAYS', 'en'));

        loca_add ('notes', 'ru');
        $this->assertSame ('без темы', loca_lang ('NOTE_NO_SUBJ', 'ru'));
        // The English notes section was never loaded, so the key itself is returned.
        $this->assertSame ('NOTE_NO_SUBJ', loca_lang ('NOTE_NO_SUBJ', 'en'));
    }

    /**
     * A language that is not in $Languages is ignored (injection guard).
     */
    public function testLocaAddIgnoresUnsupportedLanguages () : void
    {
        global $LOCA;

        loca_add ('common', 'xx');
        loca_add ('common', '');

        $this->assertArrayNotHasKey ('xx', $LOCA);
        $this->assertArrayNotHasKey ('', $LOCA);
    }

    /**
     * loca_add() includes sections with include_once: reloading the same
     * section does not re-run the file (and therefore does not reset values).
     */
    public function testLocaAddDoesNotReloadAnAlreadyLoadedSection () : void
    {
        global $LOCA;

        loca_add ('common', 'en');
        $this->assertSame ('OGame', loca_lang ('OGAME_INT', 'en'));

        $LOCA['en']['OGAME_INT'] = 'CHANGED';
        loca_add ('common', 'en');

        $this->assertSame ('CHANGED', loca_lang ('OGAME_INT', 'en'));
    }

    /**
     * loca_add() builds "<dir>loca/<lang>_<lang>/<section>.php" (trailing slash
     * added when missing, not doubled) and falls back to "../" for the
     * registration pages. A missing section file makes include_once emit a PHP
     * warning (it is not checked with file_exists()).
     */
    public function testLocaAddBuildsTheSectionPathAndWarnsOnMissingFiles () : void
    {
        global $LOCA, $from_reg;
        $warnings = array ();
        set_error_handler (function (int $errno, string $errstr) use (&$warnings) : bool {
            $warnings[] = $errstr;
            return true;
        });
        try {
            loca_add ('no_such_section_a', 'en');         // game/loca/en_en/...
            loca_add ('no_such_section_b', 'de', 'sub');  // sub/loca/de_de/...
            loca_add ('no_such_section_c', 'en', 'sub/'); // the slash is not doubled
            $from_reg = true;
            loca_add ('no_such_section_d', 'en');         // registration pages
            $from_reg = false;
        } finally {
            restore_error_handler ();
        }

        $errorText = implode ("\n", $warnings);
        $this->assertStringContainsString ('loca/en_en/no_such_section_a.php', $errorText);
        $this->assertStringContainsString ('sub/loca/de_de/no_such_section_b.php', $errorText);
        $this->assertStringContainsString ('sub/loca/en_en/no_such_section_c.php', $errorText);
        $this->assertStringNotContainsString ('sub//loca', $errorText);
        $this->assertStringContainsString ('../loca/en_en/no_such_section_d.php', $errorText);
        $this->assertSame (array (), $LOCA);
    }

    // ========================================================================
    // game/core/utils.php
    // ========================================================================

    public function testMethodReturnsTheRequestMethod () : void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertSame ('POST', method ());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertSame ('GET', method ());
    }

    public function testScriptnameReturnsTheLastPathSegment () : void
    {
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';
        $this->assertSame ('index.php', scriptname ());

        $_SERVER['SCRIPT_NAME'] = 'index.php';
        $this->assertSame ('index.php', scriptname ());

        $_SERVER['SCRIPT_NAME'] = '/game/';
        $this->assertSame ('', scriptname ());
    }

    public function testHostnameReturnsTheBaseUrlUpToTheGameDirectory () : void
    {
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';
        $_SERVER['HTTP_HOST'] = 'example.com';
        unset ($_SERVER['HTTPS']);

        $this->assertSame ('http://example.com/', hostname ());
        $this->assertSame ('http://example.com/', hostname ('game'));

        $_SERVER['HTTPS'] = 'on';
        $this->assertSame ('https://example.com/', hostname ());
        unset ($_SERVER['HTTPS']);
    }

    /**
     * SUSPECTED BUG: strrpos() returns false when the directory is not part of
     * the URL and false + 1 == 1, so substr($host, 0, 1) returns the first
     * character of the URL instead of a sensible fallback. The current (wrong)
     * result "h" is locked in here.
     */
    public function testHostnameWithADirectoryThatIsNotInTheUrlReturnsTheFirstCharacter () : void
    {
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';
        $_SERVER['HTTP_HOST'] = 'example.com';
        unset ($_SERVER['HTTPS']);

        $this->assertSame ('h', hostname ('no_such_directory'));
    }

    public function testNicenumFormatsNumbersWithThousandsSeparators () : void
    {
        $this->assertSame ('0', nicenum (0));
        $this->assertSame ('0', nicenum (0.4));            // no decimals
        $this->assertSame ('1.234.567', nicenum (1234567));
        $this->assertSame ('1.000.000', nicenum (1000000.0));
        $this->assertSame ('1.235', nicenum (1234.56));    // rounds to the nearest integer
        $this->assertSame ('1.000', nicenum (999.5));      // halves round away from zero
        $this->assertSame ('-1.235', nicenum (-1234.5));
    }

    public function testRedirectHomePrintsAMetaRefreshToTheStartPage () : void
    {
        global $StartPage;
        $StartPage = 'start.php';

        $this->expectOutputString ("<html><head><meta http-equiv='refresh' content='0;url=start.php' /></head><body></body>");

        RedirectHome ();
    }

    public function testVaReplacesNumberedTokens () : void
    {
        $this->assertSame ('Hello Bob, you have 5 new messages.', va ('Hello #1, you have #2 new messages.', 'Bob', 5));
        $this->assertSame ('No tokens here', va ('No tokens here'));
        $this->assertSame ('b a', va ('#2 #1', 'a', 'b'));
        $this->assertSame ('42', va ('#1', 42));       // non-string arguments are cast
        $this->assertSame ('a #2', va ('#1 #2', 'a')); // missing arguments stay untouched
    }

    /**
     * The token patterns ("/#1/", "/#2/", ...) have no word boundary, so a
     * shorter token also matches the prefix of a longer one.
     */
    public function testVaReplacesTokenPrefixesInsideLongerTokens () : void
    {
        $this->assertSame ('a0', va ('#10', 'a'));
    }

    public function testSksortSortsDescendingByDefaultAndPreservesKeys () : void
    {
        $array = array ('b' => array ('id' => 'b'), 'a' => array ('id' => 'a'), 'c' => array ('id' => 'c'));

        $returned = sksort ($array);

        $this->assertSame (array ('c', 'b', 'a'), array_keys ($array));
        $this->assertSame ($array, $returned);
        $this->assertSame ('c', $array['c']['id']);
    }

    public function testSksortSortsAscendingWhenRequested () : void
    {
        $array = array ('b' => array ('id' => 'b'), 'a' => array ('id' => 'a'), 'c' => array ('id' => 'c'));

        sksort ($array, 'id', true);

        $this->assertSame (array ('a', 'b', 'c'), array_keys ($array));
    }

    public function testSksortHandlesEmptyAndSingleElementArrays () : void
    {
        $empty = array ();
        $this->assertSame (array (), sksort ($empty));
        $this->assertSame (array (), $empty);

        $single = array ('x' => array ('id' => 'z'));
        sksort ($single);
        $this->assertSame (array ('x' => array ('id' => 'z')), $single);
    }

    public function testSksortComparesKeysCaseInsensitively () : void
    {
        $array = array ('lower' => array ('id' => 'a'), 'upper' => array ('id' => 'B'));

        sksort ($array);

        // Both sides are lowercased, so "B" (b) sorts before "a".
        $this->assertSame (array ('upper', 'lower'), array_keys ($array));
    }

    public function testSksortComparesNumericValuesNumerically () : void
    {
        $array = array ('a' => array ('id' => 10), 'b' => array ('id' => 9), 'c' => array ('id' => 2));

        sksort ($array);
        $this->assertSame (array (10, 9, 2), array_column ($array, 'id'));

        sksort ($array, 'id', true);
        $this->assertSame (array (2, 9, 10), array_column ($array, 'id'));
    }

    public function testLocalhostDetectsOnlyLoopbackAddresses () : void
    {
        $this->assertTrue (localhost ('127.0.0.1'));
        $this->assertTrue (localhost ('::1'));
        $this->assertFalse (localhost ('localhost'));
        $this->assertFalse (localhost ('127.0.0.2'));
        $this->assertFalse (localhost ('0.0.0.0'));
        $this->assertFalse (localhost ('::ffff:127.0.0.1'));
        $this->assertFalse (localhost (' 127.0.0.1'));
    }

    public function testSecureTextStripsScriptsAndHtmlTags () : void
    {
        $this->assertSame ('Hello', SecureText ("<script>alert('x')</script>Hello"));
        $this->assertSame ('bold', SecureText ('<b>bold</b>'));
        $this->assertSame ('', SecureText ('<div><script>x</script></div>'));
        $this->assertSame ('plain', SecureText ('plain'));
    }

    public function testSecureTextRemovesQuotesBackticksAndPercentZero () : void
    {
        $this->assertSame ('its a test', SecureText ('it\'s a "test"'));
        $this->assertSame ('ab', SecureText ('a`b'));
        $this->assertSame ('100', SecureText ('100%0'));
    }

    public function testSecureTextCollapsesWhitespaceAfterLineBreaks () : void
    {
        // Only the first line break of a run survives, the rest is dropped.
        $this->assertSame ("line1\nline2", SecureText ("line1\n\n\nline2"));
        $this->assertSame ("a\rb", SecureText ("a\r\n   b"));
    }

    public function testCheckParamsAcceptsValidInput () : void
    {
        $this->assertSame (
            array ('success' => true, 'errors' => array ()),
            CheckParams (array ('session' => 'abc123', 'mid' => '42', 'page' => 'overview', 'cp' => 7))
        );
        // The page pattern is case-insensitive and "cp" may be a string.
        $this->assertSame (
            array ('success' => true, 'errors' => array ()),
            CheckParams (array ('page' => 'OverView', 'cp' => '0'))
        );
    }

    public function testCheckParamsSkipsMissingParameters () : void
    {
        $this->assertSame (array ('success' => true, 'errors' => array ()), CheckParams (array ()));
        $this->assertSame (
            array ('success' => true, 'errors' => array ()),
            CheckParams (array ('page' => 'overview', 'unchecked' => '!!!'))
        );
    }

    public function testCheckParamsRejectsWrongTypes () : void
    {
        $this->assertSame (
            array ('success' => false, 'errors' => array ("Parameter 'session' must be of type string")),
            CheckParams (array ('session' => 123))
        );
        $this->assertSame (
            array ('success' => false, 'errors' => array ("Parameter 'mid' must be of type integer")),
            CheckParams (array ('mid' => '1.5'))
        );
        // "0123" is numeric, but its canonical integer form differs.
        $this->assertSame (
            array ('success' => false, 'errors' => array ("Parameter 'mid' must be of type integer")),
            CheckParams (array ('mid' => '0123'))
        );
    }

    public function testCheckParamsEnforcesLengthAndFormatRules () : void
    {
        $this->assertSame (
            array ('success' => false, 'errors' => array ("Parameter 'session' exceeds max length (12)")),
            CheckParams (array ('session' => str_repeat ('a', 13)))
        );
        $this->assertSame (
            array ('success' => false, 'errors' => array ("Parameter 'session' has invalid format")),
            CheckParams (array ('session' => 'xyz!'))
        );
        // "-5" is a valid integer, but the digit-only pattern rejects it.
        $this->assertSame (
            array ('success' => false, 'errors' => array ("Parameter 'mid' has invalid format")),
            CheckParams (array ('mid' => '-5'))
        );
        // Length and format are checked independently, both errors are reported.
        $this->assertSame (
            array ('success' => false, 'errors' => array (
                "Parameter 'session' exceeds max length (12)",
                "Parameter 'session' has invalid format",
            )),
            CheckParams (array ('session' => str_repeat ('a', 12) . '-'))
        );
    }

    public function testArrayInsertAfterKeyInsertsInOrder () : void
    {
        $array = array ('a' => 1, 'b' => 2, 'c' => 3);

        $returned = array_insert_after_key ($array, 'b', 'x', 99);

        $this->assertSame (array ('a' => 1, 'b' => 2, 'x' => 99, 'c' => 3), $array);
        $this->assertSame ($array, $returned);

        // A key that is not present appends the new element.
        $array = array ('a' => 1, 'b' => 2);
        array_insert_after_key ($array, 'zz', 'x', 9);
        $this->assertSame (array ('a' => 1, 'b' => 2, 'x' => 9), $array);

        // Inserting an existing key moves it (with the new value) to the new
        // position and drops the old entry.
        $array = array ('a' => 1, 'b' => 2, 'c' => 3);
        array_insert_after_key ($array, 'a', 'b', 9);
        $this->assertSame (array ('a' => 1, 'b' => 9, 'c' => 3), $array);

        // Integer keys are preserved as well (the $after_key parameter is typed
        // string, so an integer key has to be passed as a string).
        $array = array (0 => 'z', 1 => 'y');
        array_insert_after_key ($array, '1', 'k', 'v');
        $this->assertSame (array (0 => 'z', 1 => 'y', 'k' => 'v'), $array);
    }

    public function testArrayInsertBeforeKeyInsertsInOrder () : void
    {
        $array = array ('a' => 1, 'b' => 2, 'c' => 3);

        array_insert_before_key ($array, 'b', 'x', 99);
        $this->assertSame (array ('a' => 1, 'x' => 99, 'b' => 2, 'c' => 3), $array);

        // A key that is not present prepends the new element.
        $array = array ('a' => 1, 'b' => 2);
        array_insert_before_key ($array, 'zz', 'x', 99);
        $this->assertSame (array ('x' => 99, 'a' => 1, 'b' => 2), $array);

        // Inserting before the first key keeps the new element first.
        $array = array ('a' => 1);
        array_insert_before_key ($array, 'a', 'x', 2);
        $this->assertSame (array ('x' => 2, 'a' => 1), $array);
    }

    public function testGenTrivialPasswordBuildsShortLowercasePasswords () : void
    {
        // The generator reseeds the RNG itself (srand(microtime)), so the exact
        // value cannot be predicted; only the shape is deterministic.
        for ($i = 0; $i < 30; $i++) {
            $password = gen_trivial_password ();
            $this->assertMatchesRegularExpression ('/^[a-z0-9]+$/', $password);
            $this->assertGreaterThanOrEqual (4, strlen ($password));
            $this->assertLessThanOrEqual (16, strlen ($password));
        }
    }

    public function testDurationFormatBuildsHumanReadableDurations () : void
    {
        // TIME_DAYS/TIME_HOUR/TIME_MIN/TIME_SEC of the English common section
        // are "d"/"h"/"m"/"s".
        loca_add ('common', 'en');

        $this->assertSame ('', DurationFormat (0));
        $this->assertSame ('59s', DurationFormat (59));
        $this->assertSame ('1m ', DurationFormat (60));          // zero seconds are omitted
        $this->assertSame ('1m 1s', DurationFormat (61));
        $this->assertSame ('1h ', DurationFormat (3600));        // zero minutes are omitted ...
        $this->assertSame ('1h 1m ', DurationFormat (3660));
        $this->assertSame ('1d 0h 0m ', DurationFormat (86400)); // ... but shown once days exist
        $this->assertSame ('1d 1h 1m 1s', DurationFormat (90061));
    }

    public function testRunBackgroundProcessReturnsAPidOnPosixSystems () : void
    {
        if (strtoupper (substr (PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped ('RunBackgroundProcess() uses "start /B" on Windows and always returns 0.');
        }

        $pid = RunBackgroundProcess ('true');

        $this->assertIsInt ($pid);
        $this->assertGreaterThan (0, $pid);
    }

    public function testFloatEqualComparesWithinOneFloatEpsilon () : void
    {
        $this->assertTrue (FloatEqual (1.0, 1.0));
        $this->assertTrue (FloatEqual (0.0, -0.0));
        // The classic 0.1 + 0.2 != 0.3 difference is smaller than the machine
        // epsilon, so it counts as equal.
        $this->assertTrue (FloatEqual (0.1 + 0.2, 0.3));
        // Anything at or above one epsilon is not equal.
        $this->assertFalse (FloatEqual (1.0, 1.0 + PHP_FLOAT_EPSILON));
        $this->assertFalse (FloatEqual (1.0, 1.0000001));
    }

    public function testIsValidEmailAcceptsAndRejectsAddresses () : void
    {
        $this->assertSame ('player@example.com', isValidEmail ('player@example.com'));
        $this->assertSame ('user+tag@sub.example.co.uk', isValidEmail ('user+tag@sub.example.co.uk'));

        $this->assertFalse (isValidEmail ('not-an-email'));
        $this->assertFalse (isValidEmail ('a@b'));
        $this->assertFalse (isValidEmail ('a b@example.com'));
        $this->assertFalse (isValidEmail (''));
    }
}
