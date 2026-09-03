<?php

// Tests for the Notes module (game/core/notes.php).
//
// These tests run against the real database layer with the in-memory SQLite
// backend (DB_CONNECTION=sqlite, DB_DATABASE=:memory:, see phpunit.xml and
// testing/bootstrap.php), so no MySQL server and no mock DB functions are
// required. The game schema is created in memory from install_tabs.php via
// CreateDBTables().
//
// Each test method runs in a separate PHP process and starts with a fresh
// in-memory database.

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class NotesTest extends TestCase
{
    private int $playerId = 1;   // regular player (admin = 0)
    private int $adminId = 2;    // administrator (admin = 1)

    protected function setUp(): void
    {
        // loca_add() resolves locale files relative to the game directory.
        chdir(__DIR__ . '/../game');

        global $db_prefix, $db_name, $db_host, $db_user, $db_pass;
        global $UserCache, $LOCA, $loca_lang;
        $db_prefix = 'test_';
        $db_name = 'test';
        $db_host = '';
        $db_user = '';
        $db_pass = '';
        $UserCache = array ();
        $LOCA = array ();
        $loca_lang = 'en';

        InitDB();
        CreateDBTables();

        // Test users: player 1 is a regular player, player 2 is an administrator.
        $this->addUser ($this->playerId, 'PlayerOne', 'en', 0);
        $this->addUser ($this->adminId, 'Admin', 'en', 1);
    }

    // Insert a user row and return the player id.
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

    /**
     * Loading an existing note returns its row.
     */
    public function testLoadNoteSuccess(): void
    {
        $noteId = $this->addNote ($this->playerId, 'Test Subject', 'Test Text', 1, 1700000000);

        $note = LoadNote ($this->playerId, $noteId);

        $this->assertIsArray ($note);
        $this->assertSame ($noteId, intval ($note['note_id']));
        $this->assertSame ($this->playerId, intval ($note['owner_id']));
        $this->assertSame ('Test Subject', $note['subj']);
        $this->assertSame ('Test Text', $note['text']);
        $this->assertSame (1, intval ($note['prio']));
    }

    /**
     * Loading a note that does not exist returns false.
     */
    public function testLoadNoteNotFound(): void
    {
        $this->assertFalse (LoadNote ($this->playerId, 999999));
    }

    /**
     * Adding a note with valid data stores the whole row correctly.
     */
    public function testAddNoteWithValidData(): void
    {
        AddNote ($this->playerId, 'Test Subject', 'Test Text Content', 1);

        $note = $this->getLastNote ($this->playerId);

        $this->assertNotNull ($note);
        $this->assertSame ($this->playerId, intval ($note['owner_id']));
        $this->assertSame ('Test Subject', $note['subj']);
        $this->assertSame ('Test Text Content', $note['text']);
        $this->assertSame (17, intval ($note['textsize']));
        $this->assertSame (1, intval ($note['prio']));
    }

    /**
     * An empty subject/text is replaced with the localized placeholder.
     */
    public function testAddNoteWithEmptySubjectAndText(): void
    {
        AddNote ($this->playerId, '', '', 0);

        $note = $this->getLastNote ($this->playerId);

        $this->assertNotNull ($note);
        $this->assertSame (loca ('NOTE_NO_SUBJ'), $note['subj']);
        $this->assertSame (loca ('NOTE_NO_TEXT'), $note['text']);
    }

    /**
     * A very long text is truncated to 5000 characters.
     */
    public function testAddNoteWithLongText(): void
    {
        $text = str_repeat ('a', 6000);

        AddNote ($this->playerId, 'Test Subject', $text, 2);

        $note = $this->getLastNote ($this->playerId);

        $this->assertNotNull ($note);
        $this->assertSame (5000, mb_strlen ($note['text'], 'UTF-8'));
        $this->assertSame (5000, intval ($note['textsize']));
    }

    /**
     * A priority outside the valid range is clamped to 0..2.
     */
    public function testAddNoteWithInvalidPriority(): void
    {
        AddNote ($this->playerId, 'Test', 'Text', -5);
        $this->assertSame (0, intval ($this->getLastNote ($this->playerId)['prio']));

        AddNote ($this->playerId, 'Test', 'Text', 5);
        $this->assertSame (2, intval ($this->getLastNote ($this->playerId)['prio']));
    }

    /**
     * Updating one's own note stores the new values.
     */
    public function testUpdateNoteSuccess(): void
    {
        $noteId = $this->addNote ($this->playerId, 'Old Subject', 'Old Text', 0, time() - 3600);

        UpdateNote ($this->playerId, $noteId, 'Updated Subject', 'Updated Text Content', 2);

        $note = $this->getNote ($noteId);
        $this->assertNotNull ($note);
        $this->assertSame ('Updated Subject', $note['subj']);
        $this->assertSame ('Updated Text Content', $note['text']);
        $this->assertSame (20, intval ($note['textsize']));
        $this->assertSame (2, intval ($note['prio']));
    }

    /**
     * Trying to update someone else's note leaves it untouched.
     */
    public function testUpdateNoteUnauthorized(): void
    {
        $noteId = $this->addNote ($this->adminId, 'Foreign Note', 'Cannot touch this', 1, time());

        UpdateNote ($this->playerId, $noteId, 'New Subject', 'New Text', 2);

        $note = $this->getNote ($noteId);
        $this->assertNotNull ($note);
        $this->assertSame ('Foreign Note', $note['subj']);
        $this->assertSame ('Cannot touch this', $note['text']);
        $this->assertSame (1, intval ($note['prio']));
    }

    /**
     * Deleting one's own note removes the row.
     */
    public function testDelNoteSuccess(): void
    {
        $this->addNote ($this->playerId, 'Note to delete', 'This will be deleted', 0, time());

        DelNote ($this->playerId, 1);

        $this->assertSame (0, $this->countNotes ($this->playerId));
    }

    /**
     * Trying to delete someone else's note leaves it untouched.
     */
    public function testDelNoteUnauthorized(): void
    {
        $this->addNote ($this->adminId, 'Protected Note', 'Cannot delete this', 2, time());

        DelNote ($this->playerId, 1);

        $this->assertSame (1, $this->countNotes ($this->adminId));
    }

    /**
     * A regular player sees at most 20 notes (LIMIT 20).
     */
    public function testEnumNotesForRegularUser(): void
    {
        for ( $i = 0; $i < 25; $i++ ) {
            $this->addNote ($this->playerId, 'Note ' . $i, 'Text', 0, time() - $i);
        }

        $result = EnumNotes ($this->playerId);

        $this->assertSame (20, dbrows ($result));
    }

    /**
     * An administrator sees up to 150 notes (LIMIT 150).
     */
    public function testEnumNotesForAdmin(): void
    {
        for ( $i = 0; $i < 25; $i++ ) {
            $this->addNote ($this->adminId, 'Note ' . $i, 'Text', 0, time() - $i);
        }

        $result = EnumNotes ($this->adminId);

        $this->assertSame (25, dbrows ($result));
    }

    /**
     * Malicious input is stored as plain data and truncated, without
     * damaging the tables (no SQL injection).
     */
    public function testSqlInjectionProtection(): void
    {
        $maliciousSubject = "Test'; DROP TABLE notes; --";
        $maliciousText = "Text'; DELETE FROM users; --";

        AddNote ($this->playerId, $maliciousSubject, $maliciousText, 1);

        $note = $this->getLastNote ($this->playerId);
        $this->assertNotNull ($note);
        $this->assertLessThanOrEqual (30, mb_strlen ($note['subj'], 'UTF-8'));
        $this->assertLessThanOrEqual (5000, mb_strlen ($note['text'], 'UTF-8'));

        // The tables must still be intact.
        $this->assertSame (1, $this->countNotes ($this->playerId));
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM test_users");
        $row = dbarray ($result);
        $this->assertSame (2, intval ($row['cnt']));
    }

    /**
     * Multi-byte strings (UTF-8) are stored and counted correctly.
     */
    public function testMultibyteStringHandling(): void
    {
        $unicodeSubject = 'Заголовок';
        $unicodeText = 'Текст заметки с различными символами: αβγδε 😀🎉';

        AddNote ($this->playerId, $unicodeSubject, $unicodeText, 1);

        $note = $this->getLastNote ($this->playerId);
        $this->assertNotNull ($note);
        $this->assertSame ($unicodeSubject, $note['subj']);
        $this->assertSame ($unicodeText, $note['text']);
        $this->assertSame (mb_strlen ($unicodeText, 'UTF-8'), intval ($note['textsize']));
    }

    /**
     * Edge values for the priority: -1 -> 0, 0 -> 0, 1 -> 1, 2 -> 2, 3 -> 2.
     */
    public function testPriorityBoundaryValues(): void
    {
        foreach ( array (-1, 0, 1, 2, 3) as $input ) {
            AddNote ($this->playerId, 'Test', 'Text', $input);
            $expected = max (0, min (2, $input));
            $this->assertSame ($expected, intval ($this->getLastNote ($this->playerId)['prio']));
        }
    }

    /**
     * Adding notes works for users of any supported language.
     */
    public function testDifferentUserLanguages(): void
    {
        $languages = array ('en', 'ru', 'de', 'fr');
        $i = 0;
        foreach ( $languages as $lang ) {
            $playerId = 10 + $i++;
            $this->addUser ($playerId, 'Player' . $playerId, $lang, 0);

            AddNote ($playerId, 'Test', 'Text', 1);

            $this->assertSame (1, $this->countNotes ($playerId));
        }
    }

    /**
     * Special characters (quotes, newlines, HTML entities) survive the round trip.
     */
    public function testSpecialCharacters(): void
    {
        $specialSubject = "Quotes 'single' \"double\"";
        $specialText = "Text with newline\nand tab\tand special chars: & < >";

        AddNote ($this->playerId, $specialSubject, $specialText, 1);

        $note = $this->getLastNote ($this->playerId);
        $this->assertNotNull ($note);
        $this->assertSame ($specialSubject, $note['subj']);
        $this->assertSame ($specialText, $note['text']);
    }

    /**
     * Notes are listed newest first (ORDER BY date DESC).
     */
    public function testNotesOrdering(): void
    {
        $now = time();
        $this->addNote ($this->playerId, 'Oldest', 'Text', 0, $now - 300);
        $this->addNote ($this->playerId, 'Newest', 'Text', 0, $now);
        $this->addNote ($this->playerId, 'Middle', 'Text', 0, $now - 150);

        $result = EnumNotes ($this->playerId);
        $subjects = array ();
        while ( $row = dbarray ($result) ) $subjects[] = $row['subj'];

        $this->assertSame (array ('Newest', 'Middle', 'Oldest'), $subjects);
    }
}
