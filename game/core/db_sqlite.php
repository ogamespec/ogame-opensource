<?php

// Alternate in-memory database backend: SQLite via PDO.
// Included by db.php when DB_CONNECTION=sqlite (see phpunit.xml).
//
// Purpose: let the game code run without a MySQL server, e.g. inside PHPUnit
// tests. The database is in-memory by default (DB_DATABASE=:memory:), so every
// connection starts with an empty schema. Game queries are MySQL-flavoured, so
// a small translation layer converts the statements the game actually issues
// (LOCK TABLES, SHOW COLUMNS, ALTER TABLE ... AUTO_INCREMENT, SET @var := ...,
// = ANY(...), ...) into their SQLite equivalents.

global $query_counter, $query_log, $db_connect, $MDB_link;

$query_counter = 0;
$query_log = "";
$db_connect = 0;
$MDB_link = 0;

/**
 * Result wrapper for SELECT-like queries.
 * Mirrors the mysqli_result interface used by dbrows()/dbarray()/dbfree().
 */
class SQLiteDBResult
{
    /** @var array<int,array<string,mixed>> All rows of the result set. */
    public array $rows = [];

    /** @var int Current position of dbarray(). */
    public int $rowIndex = 0;

    public function __construct (array $rows = [])
    {
        $this->rows = $rows;
    }
}

function dbconnect (string $db_host, string $db_user, string $db_pass, string $db_name) : void
{
    global $query_counter, $query_log, $db_connect;

    // The PDO SQLite driver must be enabled in PHP (php-sqlite3 / pdo_sqlite).
    if ( !class_exists ('PDO') || !in_array ('sqlite', PDO::getAvailableDrivers (), true) ) {
        die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to establish connection to SQLite: the PDO SQLite driver (pdo_sqlite) is not available in PHP.<br>Install it, e.g. 'sudo apt install php-sqlite3' (Debian/Ubuntu) or enable extension=pdo_sqlite in php.ini.</b></div>");
    }

    // DB_DATABASE (e.g. ":memory:" from phpunit.xml) overrides the config database name.
    $db_file = getenv('DB_DATABASE');
    if ($db_file === false || $db_file === '') $db_file = $db_name;
    if ($db_file === '') $db_file = ':memory:';

    $db_connect = new PDO ('sqlite:' . $db_file, null, null, array (
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));

    $query_counter = 0;
    $query_log = "";
}

function dbquery (string $query, bool $mute=false) : mixed
{
    global $query_counter, $query_log, $db_connect;

    $query_counter ++;
    $query_log .= $query . "<br>\n";

    $q = trim ($query);

    try {

        // Statements that have no SQLite counterpart: silently drop them.
        if ( SQLiteIsNoop ($q) ) return true;

        // Special statements that need dedicated execution.
        if ( SQLiteIsRankUpdate ($q) ) { SQLiteExecRankUpdate ($q); return true; }
        if ( SQLiteIsSetAutoIncrement ($q) ) { SQLiteExecSetAutoIncrement ($q); return true; }
        if ( SQLiteIsTruncate ($q) ) { SQLiteExecTruncate ($q); return true; }
        if ( SQLiteIsShowColumns ($q) ) return SQLiteExecShowColumns ($q);
        if ( SQLiteIsShowTables ($q) ) return SQLiteExecShowTables ();

        // Generic translation for everything else.
        $sql = SQLiteTranslateGeneric ($q);

        $stmt = $db_connect->query ($sql);
        if ($stmt === false) return false;

        // SELECT / PRAGMA / EXPLAIN produce a result set; writes do not.
        if ( $stmt->columnCount() > 0 ) {
            $rows = $stmt->fetchAll (PDO::FETCH_ASSOC);
            return new SQLiteDBResult ($rows);
        }
        return true;
    }
    catch (PDOException $e) {
        if ( !$mute ) {
            echo "$query <br>\n";
            echo $e->getMessage ();
        }
        return false;
    }
}

function dbrows (mixed $result) : int
{
    if ( $result instanceof SQLiteDBResult ) return count ($result->rows);
    return 0;
}

function dbarray (mixed $result) : mixed
{
    if ( $result instanceof SQLiteDBResult ) {
        if ( $result->rowIndex < count ($result->rows) ) {
            return $result->rows[$result->rowIndex++];
        }
        return false;
    }
    return false;
}

function dbfree (mixed $result) : void
{
    // SQLiteDBResult needs no freeing.
}

// Connect to the database
function InitDB () : void
{
    global $db_host, $db_user, $db_pass, $db_name;
    dbconnect ($db_host, $db_user, $db_pass, $db_name);
    // SQLite is always UTF-8, so there is no SET NAMES equivalent.
}

// Add a row to the table.
function AddDBRow ( array $row, string $tabname ) : int
{
    global $db_connect, $db_prefix;
    if ( function_exists ('ModsExecRefStr') ) ModsExecRefStr ( 'add_db_row', $row, $tabname );

    $columns = array ();
    $values = array ();
    foreach ( $row as $col => $value )
    {
        $columns[] = SQLiteQuoteIdent ($col);
        $values[] = $value;
    }
    $query = "INSERT INTO ".SQLiteQuoteIdent($db_prefix.$tabname)." (".implode (", ", $columns).") VALUES (".implode (", ", array_fill (0, count ($values), "?")).")";
    $stmt = $db_connect->prepare ($query);
    if ( $stmt === false ) return 0;
    try {
        $stmt->execute (array_values ($values));
        return (int)$db_connect->lastInsertId ();
    }
    catch (PDOException $e) {
        echo "$query <br>\n";
        echo $e->getMessage ();
        return 0;
    }
}

// ---
// The master database is a MySQL-only feature (multi-universe setup).
// In the SQLite backend it is not available, so all calls report "no connection".

function MDBConnect () : bool
{
    return false;
}

function MDBQuery (string $query) : mixed
{
    return null;
}

function MDBRows (mixed $result) : int
{
    return 0;
}

function MDBArray (mixed $result) : mixed
{
    return null;
}

// ---
// Table locking. SQLite serializes writes on its own, and LOCK TABLES is a
// MySQL syntax error, so locking is a no-op here (dbquery drops the statement).

function LockTables () : void
{
    global $db_prefix;
    $tabs = array ('users','planets','ally','allyranks','allyapps','buddy','messages','notes','errors','debug','reports','browse','queue','buildqueue','fleet','union','battledata','fleetlogs','iplogs','pranger','exptab','coltab','template','botvars','userlogs','botstrat');
    if ( function_exists ('ModsExecRef') ) ModsExecRef ('lock_tables', $tabs);
    dbquery ("LOCK TABLES ".$db_prefix."uni WRITE");
}

function UnlockTables () : void
{
    dbquery ( "UNLOCK TABLES" );
}

// ---
// Database backup (SerializeDB/DeserializeDB) in SQLite dialect.

function SerializeTable (string $name) : array
{
    global $db_connect;
    global $db_prefix;

    $table = $db_prefix . $name;
    $tab = array ();

    // Get table autoincrement value (or null, if the table has no autoincrement)
    // sqlite_sequence.seq holds the last used value; MySQL AUTO_INCREMENT is the next one.
    $auto_incr = null;
    try {
        $stmt = $db_connect->query ("SELECT seq FROM sqlite_sequence WHERE name = ".$db_connect->quote ($table));
        if ( $stmt !== false ) {
            $row = $stmt->fetch (PDO::FETCH_ASSOC);
            if ( $row ) $auto_incr = intval ($row['seq']) + 1;
        }
    }
    catch (PDOException $e) {
        // No AUTOINCREMENT tables at all: sqlite_sequence does not exist.
    }
    $tab['auto_increment'] = $auto_incr;

    // Get the list of table columns
    $tab['cols'] = array ();
    $stmt = $db_connect->query ("PRAGMA table_info(".$db_connect->quote ($table).")");
    if ( $stmt !== false ) {
        while ( $row = $stmt->fetch (PDO::FETCH_ASSOC) ) {
            $tab['cols'][] = $row['name'];
        }
    }

    // Get table rows
    $tab['values'] = array ();
    $stmt = $db_connect->query ("SELECT * FROM ".SQLiteQuoteIdent ($table));
    if ( $stmt !== false ) {
        $i = 0;
        while ( $row = $stmt->fetch (PDO::FETCH_ASSOC) ) {
            $tab['values'][$i] = array ();
            $n = 0;
            foreach ( $row as $value ) {
                $tab['values'][$i][$n++] = $value;
            }
            $i++;
        }
    }

    return $tab;
}

function SerializeDB () : string
{
    include __DIR__ . "/install_tabs.php";
    if ( function_exists ('ModsExecRef') ) ModsExecRef ('install_tabs_included', $tabs);

    $db_tabs = array ();

    foreach ( $tabs as $i => $cols ) {
        $db_tabs[$i] = SerializeTable ($i);
    }

    return json_encode ($db_tabs, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
}

function DeserExecQuery (string $query) : void
{
    dbquery ($query);
}

function DeserializeTable (string $name, array $tab) : void
{
    global $db_connect;
    global $db_prefix;

    $table = $db_prefix . $name;

    // Clean up the old rows and reset the autoincrement counter (MySQL TRUNCATE does both).
    try {
        $db_connect->exec ("DELETE FROM ".SQLiteQuoteIdent ($table));
        $db_connect->exec ("DELETE FROM sqlite_sequence WHERE name = ".$db_connect->quote ($table));
    }
    catch (PDOException $e) {
        // Table does not exist, or there are no AUTOINCREMENT tables at all.
    }

    if ( count ($tab['values']) != 0 ) {

        $columns = array ();
        foreach ( $tab['cols'] as $col ) {
            $columns[] = SQLiteQuoteIdent ($col);
        }
        $query = "INSERT INTO ".SQLiteQuoteIdent ($table)." (".implode (", ", $columns).") VALUES (".implode (", ", array_fill (0, count ($tab['cols']), "?")).")";
        $stmt = $db_connect->prepare ($query);
        if ( $stmt !== false ) {
            foreach ( $tab['values'] as $row ) {
                $stmt->execute ($row);
            }
        }
    }

    // Actualize autoincrement. The column for autoincrement in the game tables is always the first one.
    if ( $tab['auto_increment'] != null ) {
        SQLiteExecSetAutoIncrement ("ALTER TABLE ".SQLiteQuoteIdent ($table)." AUTO_INCREMENT = ".intval ($tab['auto_increment']));
    }
}

function DeserializeDB (string $text) : void
{
    $tabs = json_decode ($text, true);
    if ( !is_array ($tabs) ) return;

    foreach ( $tabs as $i => $tab ) {
        DeserializeTable ($i, $tab);
    }
}

// ============================================================================
// SQL translation helpers
// ============================================================================

function SQLiteQuoteIdent (string $ident) : string
{
    return '`' . str_replace ('`', '``', $ident) . '`';
}

/**
 * Statements that are MySQL-specific and have no SQLite equivalent.
 * They are executed successfully without doing anything.
 */
function SQLiteIsNoop (string $q) : bool
{
    $upper = strtoupper ($q);
    if ( str_starts_with ($upper, 'LOCK TABLES') ) return true;
    if ( str_starts_with ($upper, 'UNLOCK TABLES') ) return true;
    if ( str_starts_with ($upper, 'SET NAMES') ) return true;
    if ( str_starts_with ($upper, 'SET CHARACTER SET') ) return true;
    if ( str_starts_with ($upper, 'SET SESSION') ) return true;
    // SET @pos := 0;  (user variable reset, used before rank recalculation)
    if ( preg_match ('/^SET\s+@\w+\s*:=/i', $q) ) return true;
    return false;
}

/**
 * MySQL user-variable rank assignment:
 *   UPDATE t SET c = (SELECT @pos := @pos+1) ORDER BY ...
 * SQLite has no user variables, so the ranking is done in PHP instead.
 */
function SQLiteIsRankUpdate (string $q) : bool
{
    return preg_match ('/^UPDATE\s+`?\w+`?\s+SET\s+`?\w+`?\s*=\s*\(\s*SELECT\s+@\w+\s*:=\s*@\w+\s*\+\s*1\s*\)\s+ORDER\s+BY\s+/is', $q) === 1;
}

function SQLiteExecRankUpdate (string $q) : void
{
    global $db_connect;

    if ( !preg_match ('/^UPDATE\s+`?(\w+)`?\s+SET\s+`?(\w+)`?\s*=\s*\(\s*SELECT\s+@\w+\s*:=\s*@\w+\s*\+\s*1\s*\)\s+ORDER\s+BY\s+(.+?)\s*;?\s*$/is', $q, $m) ) return;

    $table = $m[1];
    $column = $m[2];
    $order = trim ($m[3]);

    // Rank by rowid so the same table can be updated without knowing its primary key.
    $stmt = $db_connect->query ("SELECT rowid FROM ".SQLiteQuoteIdent ($table)." ORDER BY ".$order);
    if ( $stmt === false ) return;
    $ids = $stmt->fetchAll (PDO::FETCH_COLUMN);

    $stmt = $db_connect->prepare ("UPDATE ".SQLiteQuoteIdent ($table)." SET ".SQLiteQuoteIdent ($column)." = ? WHERE rowid = ?");
    if ( $stmt === false ) return;
    $pos = 0;
    foreach ( $ids as $id ) {
        $pos++;
        $stmt->execute (array ($pos, $id));
    }
}

/**
 * ALTER TABLE t AUTO_INCREMENT = N;
 * In SQLite the counter lives in sqlite_sequence; update it directly.
 */
function SQLiteIsSetAutoIncrement (string $q) : bool
{
    return preg_match ('/^ALTER\s+TABLE\s+.+AUTO_INCREMENT\s*=\s*\d+/is', $q) === 1;
}

function SQLiteExecSetAutoIncrement (string $q) : void
{
    global $db_connect;

    if ( !preg_match ('/^ALTER\s+TABLE\s+`?(\w+)`?\s+AUTO_INCREMENT\s*=\s*(\d+)/is', $q, $m) ) return;

    $table = $m[1];
    $seq = max (0, intval ($m[2]) - 1);   // sqlite_sequence.seq = last used value

    try {
        $db_connect->exec ("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES (".$db_connect->quote ($table).", 0)");
        $db_connect->exec ("UPDATE sqlite_sequence SET seq = ".$seq." WHERE name = ".$db_connect->quote ($table));
    }
    catch (PDOException $e) {
        // The table has no AUTOINCREMENT column: nothing to set.
    }
}

/**
 * TRUNCATE TABLE t;
 * SQLite has no TRUNCATE; DELETE FROM does the same. MySQL TRUNCATE also
 * resets the autoincrement counter, so sqlite_sequence is cleared too.
 */
function SQLiteIsTruncate (string $q) : bool
{
    return str_starts_with (strtoupper ($q), 'TRUNCATE');
}

function SQLiteExecTruncate (string $q) : void
{
    global $db_connect;

    if ( !preg_match ('/^TRUNCATE\s+TABLE\s+`?(\w+)`?/is', $q, $m) ) return;

    $table = $m[1];
    $db_connect->exec ("DELETE FROM ".SQLiteQuoteIdent ($table));
    try {
        $db_connect->exec ("DELETE FROM sqlite_sequence WHERE name = ".$db_connect->quote ($table));
    }
    catch (PDOException $e) {
        // No AUTOINCREMENT tables at all.
    }
}

/**
 * SHOW COLUMNS FROM t;
 * Emulated with PRAGMA table_info, shaped like the MySQL result
 * (Field/Type/Null/Key/Default/Extra) so that admin_db.php keeps working.
 */
function SQLiteIsShowColumns (string $q) : bool
{
    return str_starts_with (strtoupper ($q), 'SHOW COLUMNS');
}

function SQLiteExecShowColumns (string $q) : mixed
{
    global $db_connect;

    if ( !preg_match ('/^SHOW\s+COLUMNS\s+FROM\s+`?(\w+)`?/is', $q, $m) ) return false;

    $table = $m[1];
    $stmt = $db_connect->query ("PRAGMA table_info(".$db_connect->quote ($table).")");
    if ( $stmt === false ) return false;

    $rows = array ();
    foreach ( $stmt->fetchAll (PDO::FETCH_ASSOC) as $col ) {
        $rows[] = array (
            'Field' => $col['name'],
            'Type' => $col['type'],
            'Null' => $col['notnull'] ? 'NO' : 'YES',
            'Key' => $col['pk'] ? 'PRI' : '',
            'Default' => $col['dflt_value'],
            'Extra' => $col['pk'] ? 'auto_increment' : '',
        );
    }
    return new SQLiteDBResult ($rows);
}

/**
 * SHOW TABLES;
 * Emulated via sqlite_master. The row key matches the MySQL convention
 * "Tables_in_<dbname>" that admin_db.php relies on.
 */
function SQLiteIsShowTables (string $q) : bool
{
    return str_starts_with (strtoupper ($q), 'SHOW TABLES');
}

function SQLiteExecShowTables () : mixed
{
    global $db_connect, $db_name;

    $key = 'Tables_in_' . ($db_name ?? '');
    $stmt = $db_connect->query ("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
    if ( $stmt === false ) return false;

    $rows = array ();
    foreach ( $stmt->fetchAll (PDO::FETCH_COLUMN) as $name ) {
        $rows[] = array ($key => $name);
    }
    return new SQLiteDBResult ($rows);
}

/**
 * Rewrites the remaining MySQL-isms to SQLite:
 *  - CREATE TABLE ... CHARACTER SET utf8 COLLATE utf8_general_ci  -> plain CREATE TABLE
 *  - INT AUTO_INCREMENT PRIMARY KEY                               -> INTEGER PRIMARY KEY AUTOINCREMENT
 *  - x = ANY (subquery)                                           -> x IN (subquery)
 *  - NOW()                                                        -> strftime('%s','now')
 */
function SQLiteTranslateGeneric (string $sql) : string
{
    $sql = preg_replace ('/\s+CHARACTER\s+SET\s+[A-Za-z0-9_]+\s+COLLATE\s+[A-Za-z0-9_]+/i', '', $sql) ?? $sql;
    $sql = preg_replace ('/\bINT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql) ?? $sql;
    $sql = preg_replace ('/=\s*ANY\s*\(/i', 'IN (', $sql) ?? $sql;
    $sql = preg_replace ('/\bNOW\s*\(\s*\)/i', "strftime('%s','now')", $sql) ?? $sql;
    return $sql;
}

?>