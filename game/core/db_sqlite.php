<?php
/**
 * @file db_sqlite.php
 * @brief SQLite database backend.
 * @details Implements the database layer on top of PDO SQLite, in-memory by default. Used by tests so the game code runs without a MySQL server.
 */
// Alternate in-memory database backend: SQLite via PDO.
// Included by db.php when DB_CONNECTION=sqlite (see phpunit.xml).
//
// Purpose: let the game code run without a MySQL server, e.g. inside PHPUnit
// tests. The database is in-memory by default (DB_DATABASE=:memory:), so every
// connection starts with an empty schema. Game queries are MySQL-flavoured, so
// a small translation layer converts the statements the game actually issues
// (LOCK TABLES, SHOW COLUMNS, ALTER TABLE ... AUTO_INCREMENT, SET @var := ...,
// = ANY(...), ...) into their SQLite equivalents.

/**
 * Shared query bookkeeping globals: $query_counter counts executed queries,
 * $query_log accumulates them, $db_connect holds the active PDO connection
 * and $MDB_link is the master database link (unused in the SQLite backend).
 */
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
    /**
     * All rows of the result set (list of row arrays).
     */
    public array $rows = [];

    /** @var int Current position of dbarray(). */
    public int $rowIndex = 0;

    /**
     * Creates a result wrapper holding the given rows.
     *
     * @param array $rows All rows of the result set.
     */
    public function __construct (array $rows = [])
    {
        $this->rows = $rows;
    }
}

/**
 * Establishes the SQLite connection through PDO.
 *
 * @param string $db_host Host name (ignored by SQLite).
 * @param string $db_user User name (ignored by SQLite).
 * @param string $db_pass Password (ignored by SQLite).
 * @param string $db_name Database file name, overridden by the DB_DATABASE environment variable.
 * @return void
 */
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

/**
 * Executes a MySQL-flavoured query, translating it to SQLite.
 *
 * @param string $query The SQL query to execute.
 * @param bool $mute Whether to suppress error output on failure.
 * @return mixed An SQLiteDBResult for SELECT-like queries, true on success, false on failure.
 */
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

/**
 * Returns the number of rows of a query result.
 *
 * @param mixed $result The query result to count.
 * @return int The number of rows.
 */
function dbrows (mixed $result) : int
{
    if ( $result instanceof SQLiteDBResult ) return count ($result->rows);
    return 0;
}

/**
 * Fetches the next row of a query result.
 *
 * @param mixed $result The query result to read.
 * @return mixed The next row as an array, or false when no rows remain.
 */
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

/**
 * Frees a query result (a no-op for SQLite results).
 *
 * @param mixed $result The query result to free.
 * @return void
 */
function dbfree (mixed $result) : void
{
    // SQLiteDBResult needs no freeing.
}

/**
 * Connects to the database using the configured credentials.
 *
 * @return void
 */
function InitDB () : void
{
    global $db_host, $db_user, $db_pass, $db_name;
    dbconnect ($db_host, $db_user, $db_pass, $db_name);
    // SQLite is always UTF-8, so there is no SET NAMES equivalent.
}

/**
 * Adds a row to a table.
 *
 * @param array $row Associative array of column names to values.
 * @param string $tabname Name of the target table.
 * @return int The id of the inserted row, or 0 on failure.
 */
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
        $stmt->execute ($values);
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

/**
 * Connects to the master database, which is not available in the SQLite backend.
 *
 * @return bool Always false.
 */
function MDBConnect () : bool
{
    return false;
}

/**
 * Runs a query on the master database (not available in the SQLite backend).
 *
 * @param string $query The SQL query to execute.
 * @return mixed Always null.
 */
function MDBQuery (string $query) : mixed
{
    return null;
}

/**
 * Returns the number of rows of a master database result (not available in the SQLite backend).
 *
 * @param mixed $result The query result to count.
 * @return int Always 0.
 */
function MDBRows (mixed $result) : int
{
    return 0;
}

/**
 * Fetches a row of a master database result (not available in the SQLite backend).
 *
 * @param mixed $result The query result to read.
 * @return mixed Always null.
 */
function MDBArray (mixed $result) : mixed
{
    return null;
}

// ---
// Table locking. SQLite serializes writes on its own, and LOCK TABLES is a
// MySQL syntax error, so locking is a no-op here (dbquery drops the statement).

/**
 * Locks game tables for writing (a no-op in SQLite, which serializes writes itself).
 *
 * @return void
 */
function LockTables () : void
{
    global $db_prefix;
    $tabs = array ('users','planets','ally','allyranks','allyapps','buddy','messages','notes','errors','debug','reports','browse','queue','buildqueue','fleet','union','battledata','fleetlogs','iplogs','pranger','exptab','coltab','template','botvars','userlogs','botstrat');
    if ( function_exists ('ModsExecRef') ) ModsExecRef ('lock_tables', $tabs);
    dbquery ("LOCK TABLES ".$db_prefix."uni WRITE");
}

/**
 * Unlocks previously locked tables (a no-op in SQLite).
 *
 * @return void
 */
function UnlockTables () : void
{
    dbquery ( "UNLOCK TABLES" );
}

// ---
// Database backup (SerializeDB/DeserializeDB) in SQLite dialect.

/**
 * Serializes a table into its autoincrement value, columns and rows.
 *
 * @param string $name Name of the table to serialize.
 * @return array The table data with auto_increment, cols and values keys.
 */
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

/**
 * Serializes all game tables into a JSON string.
 *
 * @return string The serialized database as JSON.
 */
function SerializeDB () : string
{
    $tabs = array ();
    include __DIR__ . "/install_tabs.php";
    if ( function_exists ('ModsExecRef') ) ModsExecRef ('install_tabs_included', $tabs);

    $db_tabs = array ();

    foreach ( $tabs as $i => $cols ) {
        $db_tabs[$i] = SerializeTable ($i);
    }

    return (string) json_encode ($db_tabs, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
}

/**
 * Executes a single query during database deserialization.
 *
 * @param string $query The SQL query to execute.
 * @return void
 */
function DeserExecQuery (string $query) : void
{
    dbquery ($query);
}

/**
 * Restores a table from serialized data, replacing its previous contents.
 *
 * @param string $name Name of the table to restore.
 * @param array $tab The serialized table data.
 * @return void
 */
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

/**
 * Restores all tables from a serialized JSON string.
 *
 * @param string $text The serialized database as JSON.
 * @return void
 */
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

/**
 * Quotes an identifier with backticks, escaping embedded backticks.
 *
 * @param string $ident The identifier to quote.
 * @return string The quoted identifier.
 */
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
 *   UPDATE t SET c = (SELECT \@pos := \@pos+1) ORDER BY ...
 * SQLite has no user variables, so the ranking is done in PHP instead.
 */
function SQLiteIsRankUpdate (string $q) : bool
{
    return preg_match ('/^UPDATE\s+`?\w+`?\s+SET\s+`?\w+`?\s*=\s*\(\s*SELECT\s+@\w+\s*:=\s*@\w+\s*\+\s*1\s*\)\s+ORDER\s+BY\s+/is', $q) === 1;
}

/**
 * Emulates a MySQL rank update by assigning sequential numbers in PHP.
 *
 * @param string $q The rank update query.
 * @return void
 */
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

/**
 * Updates a table autoincrement counter in sqlite_sequence.
 *
 * @param string $q The ALTER TABLE AUTO_INCREMENT query.
 * @return void
 */
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

/**
 * Emulates TRUNCATE TABLE with a DELETE and a reset of the autoincrement counter.
 *
 * @param string $q The TRUNCATE query.
 * @return void
 */
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

/**
 * Emulates SHOW COLUMNS with PRAGMA table_info, shaped like the MySQL result.
 *
 * @param string $q The SHOW COLUMNS query.
 * @return mixed An SQLiteDBResult with MySQL-shaped column metadata, or false on failure.
 */
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

/**
 * Emulates SHOW TABLES using sqlite_master.
 *
 * @return mixed An SQLiteDBResult with table names, or false on failure.
 */
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