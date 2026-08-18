<?php

// Tests for the alternate in-memory database backend (SQLite via PDO).
//
// The backend is activated by the DB_CONNECTION=sqlite environment variable
// (see phpunit.xml) and lets the game code run without a MySQL server.
//
// These tests run in a separate PHP process: the other tests in this folder
// define mock dbquery()/dbarray()/AddDBRow() functions that would clash with
// the real database layer loaded here.

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class DbSqliteTest extends TestCase
{
    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';

        // Game constants (GID_*) used by the table definitions.
        require_once __DIR__ . '/../game/core/defs.php';
        require_once __DIR__ . '/../game/core/techs.php';

        // The DB layer: the dispatcher picks the SQLite backend.
        require_once __DIR__ . '/../game/core/db.php';

        global $db_prefix, $db_name, $db_host, $db_user, $db_pass;
        $db_prefix = 'test_';
        $db_name = 'test';
        $db_host = '';
        $db_user = '';
        $db_pass = '';

        InitDB();
        CreateDBTables();
    }

    private function insertUser (string $name, int $score1 = 0) : int
    {
        return AddDBRow (array (
            'name' => $name,
            'oname' => $name,
            'score1' => $score1,
            'score2' => 0,
            'score3' => 0,
            'place1' => 0,
            'place2' => 0,
            'place3' => 0,
            'admin' => 0,
        ), "users");
    }

    public function testBackendIsSqlite(): void
    {
        global $db_connect;

        $this->assertSame ('sqlite', DB_ConnectionType ());
        $this->assertInstanceOf (PDO::class, $db_connect);
    }

    public function testTablesAreCreated(): void
    {
        $result = dbquery ("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name");
        $this->assertNotFalse ($result);

        $names = array ();
        while ( $row = dbarray ($result) ) $names[] = $row['name'];

        foreach ( array ('test_uni', 'test_users', 'test_planets', 'test_queue', 'test_notes', 'test_messages') as $table ) {
            $this->assertContains ($table, $names, "Table $table should exist after CreateDBTables()");
        }
    }

    public function testAddDBRowReturnsAutoincrementId(): void
    {
        $id1 = $this->insertUser ('Alpha');
        $id2 = $this->insertUser ('Beta');

        $this->assertSame (1, $id1);
        $this->assertSame (2, $id2);
    }

    public function testSelectRowsAndIteration(): void
    {
        $this->insertUser ('One', 100);
        $this->insertUser ('Two', 200);

        $result = dbquery ("SELECT * FROM test_users ORDER BY score1 ASC");
        $this->assertSame (2, dbrows ($result));

        $first = dbarray ($result);
        $this->assertSame ('One', $first['name']);
        $this->assertSame (100, intval ($first['score1']));

        $second = dbarray ($result);
        $this->assertSame ('Two', $second['name']);

        $this->assertFalse (dbarray ($result), "dbarray() must return false at the end of the result set");
    }

    public function testUpdateAndDelete(): void
    {
        $id = $this->insertUser ('Updatable', 100);

        $this->assertTrue (dbquery ("UPDATE test_users SET score1 = 500 WHERE player_id = $id"));
        $result = dbquery ("SELECT score1 FROM test_users WHERE player_id = $id");
        $row = dbarray ($result);
        $this->assertSame (500, intval ($row['score1']));

        $this->assertTrue (dbquery ("DELETE FROM test_users WHERE player_id = $id"));
        $result = dbquery ("SELECT COUNT(*) AS cnt FROM test_users WHERE player_id = $id");
        $row = dbarray ($result);
        $this->assertSame (0, intval ($row['cnt']));
    }

    public function testMysqlOnlyStatementsAreIgnored(): void
    {
        // These statements are MySQL-specific; in SQLite mode they must succeed as no-ops.
        $this->assertTrue (dbquery ("SET NAMES 'utf8';"));
        $this->assertTrue (dbquery ("SET CHARACTER SET 'utf8';"));
        $this->assertTrue (dbquery ("SET SESSION collation_connection = 'utf8_general_ci';"));
        $this->assertTrue (dbquery ("LOCK TABLES test_uni WRITE, test_users WRITE"));
        $this->assertTrue (dbquery ("UNLOCK TABLES"));
        $this->assertTrue (dbquery ("SET @pos := 0;"));

        LockTables ();
        UnlockTables ();
        $this->addToAssertionCount (1);
    }

    public function testAlterTableAutoIncrement(): void
    {
        AddDBRow (array ('owner_id' => 1, 'subj' => 'a', 'text' => 'b'), "notes");

        dbquery ("ALTER TABLE test_notes AUTO_INCREMENT = 10000;");

        $id = AddDBRow (array ('owner_id' => 1, 'subj' => 'c', 'text' => 'd'), "notes");
        $this->assertGreaterThanOrEqual (10000, $id);
    }

    public function testTruncateTable(): void
    {
        $this->insertUser ('One');
        $this->insertUser ('Two');

        dbquery ("TRUNCATE TABLE test_users;");

        $result = dbquery ("SELECT COUNT(*) AS cnt FROM test_users");
        $row = dbarray ($result);
        $this->assertSame (0, intval ($row['cnt']));

        // MySQL TRUNCATE resets the autoincrement counter: the next id is 1 again.
        $id = $this->insertUser ('AfterTruncate');
        $this->assertSame (1, $id);
    }

    public function testRankUpdateTranslation(): void
    {
        // MySQL user-variable ranking used by RecalcRanks()/RecalcAllyRanks().
        $a = $this->insertUser ('Low', 10);
        $b = $this->insertUser ('High', 30);
        $c = $this->insertUser ('Mid', 20);

        dbquery ("SET @pos := 0;");
        dbquery ("UPDATE test_users
                  SET place1 = (SELECT @pos := @pos+1)
                  ORDER BY score1 DESC");

        $places = array ();
        $result = dbquery ("SELECT player_id, place1 FROM test_users");
        while ( $row = dbarray ($result) ) $places[$row['player_id']] = intval ($row['place1']);

        $this->assertSame (1, $places[$b], "Highest score must get place 1");
        $this->assertSame (2, $places[$c]);
        $this->assertSame (3, $places[$a], "Lowest score must get place 3");
    }

    public function testAnySubqueryTranslation(): void
    {
        // MySQL "= ANY (SELECT ...)" is rewritten to "IN (SELECT ...)".
        $this->insertUser ('One', 100);
        $this->insertUser ('Two', 200);

        $sub = "SELECT player_id FROM test_users WHERE score1 > 150";
        $query = "SELECT * FROM test_users WHERE player_id = ANY ($sub)";
        $result = dbquery ($query);

        $this->assertSame (1, dbrows ($result));
        $row = dbarray ($result);
        $this->assertSame ('Two', $row['name']);
    }

    public function testShowColumnsEmulation(): void
    {
        $result = dbquery ("SHOW COLUMNS FROM test_users;");
        $this->assertNotFalse ($result);

        $fields = array ();
        while ( $row = dbarray ($result) ) {
            $this->assertArrayHasKey ('Field', $row);
            $this->assertArrayHasKey ('Type', $row);
            $this->assertArrayHasKey ('Key', $row);
            $this->assertArrayHasKey ('Extra', $row);
            $fields[] = $row['Field'];
        }

        $this->assertContains ('player_id', $fields);
        $this->assertContains ('name', $fields);
    }

    public function testShowTablesEmulation(): void
    {
        global $db_name;
        $result = dbquery ("SHOW TABLES;");
        $this->assertNotFalse ($result);

        $tables = array ();
        while ( $row = dbarray ($result) ) {
            $this->assertArrayHasKey ('Tables_in_' . $db_name, $row);
            $tables[] = $row['Tables_in_' . $db_name];
        }

        $this->assertContains ('test_users', $tables);
    }

    public function testSerializeDeserializeRoundTrip(): void
    {
        $this->insertUser ('One', 100);
        $this->insertUser ('Two', 200);
        AddDBRow (array ('owner_id' => 1, 'subj' => 'Note', 'text' => 'Text'), "notes");

        $dump = SerializeDB ();

        // Wipe everything and restore from the dump.
        dbquery ("DROP TABLE test_users;");
        dbquery ("DROP TABLE test_notes;");
        CreateDBTables ();

        DeserializeDB ($dump);

        $result = dbquery ("SELECT name, score1 FROM test_users ORDER BY player_id ASC");
        $rows = array ();
        while ( $row = dbarray ($result) ) $rows[] = $row;
        $this->assertCount (2, $rows);
        $this->assertSame ('One', $rows[0]['name']);
        $this->assertSame (100, intval ($rows[0]['score1']));
        $this->assertSame ('Two', $rows[1]['name']);

        $result = dbquery ("SELECT subj FROM test_notes");
        $row = dbarray ($result);
        $this->assertSame ('Note', $row['subj']);
    }

    public function testErrorHandling(): void
    {
        // Invalid SQL with mute=true must return false without echoing anything.
        ob_start ();
        $result = dbquery ("SELECT * FROM no_such_table", true);
        $output = ob_get_clean ();

        $this->assertFalse ($result);
        $this->assertSame ('', $output);
    }

    public function testMasterDatabaseIsUnavailable(): void
    {
        $this->assertFalse (MDBConnect ());
        $this->assertNull (MDBQuery ("SELECT 1"));
        $this->assertSame (0, MDBRows (null));
        $this->assertNull (MDBArray (null));
    }
}
