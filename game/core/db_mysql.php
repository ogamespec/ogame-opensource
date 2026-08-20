<?php
/**
 * @file db_mysql.php
 * @brief MySQL database backend.
 * @details Implements the database layer on top of the mysqli extension. This is the default backend used by the live game.
 */
// Working with MySQL database (default backend).
// Included by db.php when DB_CONNECTION is not "sqlite".

/** Number of executed queries. */
$query_counter = 0;
/** Log of executed queries. */
$query_log = "";
/** MySQL connection handle. */
$db_connect = 0;

/**
 * Establishes a connection to the MySQL server and selects the game database.
 *
 * @param string $db_host Database server host.
 * @param string $db_user Database user name.
 * @param string $db_pass Database user password.
 * @param string $db_name Name of the database to select.
 * @return void
 */
function dbconnect (string $db_host, string $db_user, string $db_pass, string $db_name) : void
{
    global  $query_counter, $query_log, $db_connect;
    mysqli_report(MYSQLI_REPORT_OFF);
    $db_connect = @mysqli_connect($db_host, $db_user, $db_pass);
    if (!$db_connect) {
        die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to establish connection to MySQL</b></div>");
    }
    $db_select = @mysqli_select_db($db_connect, $db_name);
    if (!$db_select) {
        die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to select MySQL database</b></div>");
    }

    $query_counter = 0;
    $query_log = "";
}

/**
 * Executes a query on the game database and returns the result.
 *
 * @param string $query SQL query to execute.
 * @param bool $mute If true, suppresses the error output on failure.
 * @return mixed Query result resource or false on failure.
 */
function dbquery (string $query, bool $mute=false) : mixed
{
    global  $query_counter, $query_log, $db_connect;
    $query_counter ++;
    $query_log .= $query . "<br>\n";
    $result = @mysqli_query($db_connect, $query);
    if (!$result && $mute==false) {
        echo "$query <br>";
        echo mysqli_error ($db_connect);
        //Debug ( mysqli_error($db_connect) . "<br>" . $query . "<br>" . BackTrace () ) ;
        return false;
    }
    else return $result;
}

/**
 * Returns the number of rows in a query result.
 *
 * @param mixed $result Query result resource.
 * @return int Number of rows in the result.
 */
function dbrows (mixed $result) : int
{
    return (int) @mysqli_num_rows($result);
}

/**
 * Fetches the next row of a query result as an associative array.
 *
 * @param mixed $result Query result resource.
 * @return mixed Associative array with the row data or false when there are no more rows.
 */
function dbarray (mixed $result) : mixed
{
    global $db_connect;
    $arr = @mysqli_fetch_assoc($result);
    if (!$arr) {
        echo mysqli_error($db_connect);
        return false;
    }
    else return $arr;
}

/**
 * Frees the memory associated with a query result.
 *
 * @param mixed $result Query result resource.
 * @return void
 */
function dbfree (mixed $result) : void {
    @mysqli_free_result ($result);
}

/**
 * Connects to the database and sets the UTF-8 connection settings.
 *
 * @return void
 */
function InitDB () : void
{
    global $db_host, $db_user, $db_pass, $db_name;
    dbconnect ($db_host, $db_user, $db_pass, $db_name);
    dbquery("SET NAMES 'utf8';");
    dbquery("SET CHARACTER SET 'utf8';");
    dbquery("SET SESSION collation_connection = 'utf8_general_ci';");
}

/**
 * Adds a row to the specified table and returns the id of the inserted row.
 * The method takes into account that the table may have additional columns added by the mod that do not need to be touched.
 *
 * @param array $row Associative array of column names and values.
 * @param string $tabname Name of the table to insert into.
 * @return int Id of the inserted row.
 */
function AddDBRow ( array $row, string $tabname ) : int
{
    global $db_connect, $db_prefix;
    ModsExecRefStr ( 'add_db_row', $row, $tabname );
    $values = "(";
    $columns = "(";
    $first = true;
    foreach ($row as $col=>$value)
    {
        if (!$first) {
            $values .= ", ";
            $columns .= ", ";
        }
        $values .= "'".mysqli_real_escape_string($db_connect, (string)$value)."'";
        $columns .= "`".$col."`";
        $first = false;
    }
    $values .= ");";
    $columns .= ")";
    $query = "INSERT INTO ".$db_prefix."$tabname $columns VALUES ".$values;
    dbquery( $query);
    return (int) mysqli_insert_id ($db_connect);
}

// ---
// Working with the master database, where information common to all universes (e.g. coupons) is stored.
// The master database can be accessed from any universe

/** Link to the master database connection. */
$MDB_link = 0;

/**
 * Establishes a connection to the master database and applies UTF-8 settings.
 *
 * @return bool True on success, false if the master database is disabled or the connection fails.
 */
function MDBConnect () : bool
{
    global $MDB_link, $mdb_host, $mdb_user, $mdb_pass, $mdb_name, $mdb_enable;
    if (!$mdb_enable) return false;
    mysqli_report(MYSQLI_REPORT_OFF);
    $MDB_link = @mysqli_connect ($mdb_host, $mdb_user, $mdb_pass );
    if (!$MDB_link) return false;
    if ( ! @mysqli_select_db ($MDB_link, $mdb_name) ) return false;

    MDBQuery ("SET NAMES 'utf8';");
    MDBQuery ("SET CHARACTER SET 'utf8';");
    MDBQuery ("SET SESSION collation_connection = 'utf8_general_ci';");

    return true;
}

/**
 * Executes a query on the master database and returns the result.
 *
 * @param string $query SQL query to execute.
 * @return mixed Query result resource or null on failure.
 */
function MDBQuery (string $query) : mixed
{
    global $MDB_link;
    $result = @mysqli_query ($MDB_link, $query);
    if (!$result) return null;
    else return $result;
}

/**
 * Returns the number of rows in a master database query result.
 *
 * @param mixed $result Query result resource.
 * @return int Number of rows in the result.
 */
function MDBRows (mixed $result) : int
{
    return (int) @mysqli_num_rows($result);
}

/**
 * Fetches the next row of a master database query result as an associative array.
 *
 * @param mixed $result Query result resource.
 * @return mixed Associative array with the row data or null when there are no more rows.
 */
function MDBArray (mixed $result) : mixed
{
    $arr = @mysqli_fetch_assoc($result);
    if (!$arr) return null;
    else return $arr;
}


/**
 * Locks all game tables for writing to protect against simultaneous access from several users.
 *
 * @return void
 */
function LockTables () : void
{
    global $db_prefix;
    $tabs = array ('users','planets','ally','allyranks','allyapps','buddy','messages','notes','errors','debug','reports','browse','queue','buildqueue','fleet','union','battledata','fleetlogs','iplogs','pranger','exptab','coltab','template','botvars','userlogs','botstrat');
    ModsExecRef ('lock_tables', $tabs);
    $query = "LOCK TABLES ".$db_prefix."uni WRITE";
    foreach ( $tabs as $i=>$name ) 
    {
        $query .= ", ".$db_prefix.$name." WRITE";
    }
    dbquery ($query);
}

/**
 * Unlocks all previously locked tables.
 *
 * @return void
 */
function UnlockTables () : void
{
    dbquery ( "UNLOCK TABLES" );
}

/**
 * Serializes a table into an array with its autoincrement value, column list and row data.
 *
 * @param string $name Name of the table to serialize.
 * @return array Serialized table data.
 */
function SerializeTable (string $name) : array
{
    global $db_name;
    global $db_prefix;

    $tab = array();

    // Get table autoincrement value (or null, if the table has no autoincrement)
    $query = "SELECT `AUTO_INCREMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".$db_name."' AND TABLE_NAME = '".$db_prefix.$name."';";
    $res = dbquery ($query);
    $arr = dbarray($res);
    $auto_incr = empty($arr['AUTO_INCREMENT']) ? null : intval($arr['AUTO_INCREMENT']);
    $tab['auto_increment'] = $auto_incr;

    // Get the list of table columns
    $query = "SHOW COLUMNS FROM $db_prefix$name;";
    $res = dbquery($query);
    $rows = dbrows ($res);
    $tab['cols'] = array();
    $i = 0;
    while ($rows--) {
        $arr = dbarray($res);
        $tab['cols'][$i++] = $arr['Field'];
    }

    // Get table rows
    $tab['values'] = array();
    $query = "SELECT * FROM ".$db_prefix.$name;
    $res = dbquery ($query);
    $rows = dbrows($res);
    $i = 0;
    while ($rows--) {
        $arr = dbarray($res);
        $tab['values'][$i] = array();
        $n = 0;
        foreach ($arr as $j=>$value) {
            $tab['values'][$i][$n++] = $value;
        }
        $i++;
    }

    return $tab;
}

/**
 * Serializes all game tables into a JSON string.
 *
 * @return string JSON representation of the whole database.
 */
function SerializeDB () : string
{
    $tabs = array();
    include __DIR__ . "/install_tabs.php";
    ModsExecRef ('install_tabs_included', $tabs);

    $db_tabs = array();

    foreach ($tabs as $i=>$cols) {
        $db_tabs[$i] = SerializeTable ($i);
    }

    return (string) json_encode ($db_tabs, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
}

/**
 * Executes a single query during database deserialization.
 *
 * @param string $query SQL query to execute.
 * @return void
 */
function DeserExecQuery (string $query) : void
{
    //echo $query . "\n";
    dbquery ($query);
}

/**
 * Restores a table from its serialized data, replacing the existing rows.
 *
 * @param string $name Name of the table to restore.
 * @param array $tab Serialized table data.
 * @return void
 */
function DeserializeTable (string $name, array $tab) : void
{
    global $db_prefix;
    global $db_connect;

    // Clean up the old rows
    $query = "TRUNCATE TABLE `".$db_prefix.$name."`;";
    DeserExecQuery ($query);

    if (count($tab['values']) != 0) {

        $query = "INSERT INTO `".$db_prefix.$name."` (";
        $first = true;
        foreach ($tab['cols'] as $col) {
            if (!$first) $query .= ", ";
            $query .= "`".$col."`";
            if ($first) $first = false;
        }
        $query .= ") VALUES\n";

        $first = true;
        foreach ($tab['values'] as $row) {
            if (!$first) $query .= ",\n";
            $query .= "(";
            $first_val = true;
            foreach ($row as $value) {
                if (!$first_val) $query .= ", ";
                $query .= "\"".mysqli_escape_string($db_connect, $value)."\"";
                if ($first_val) $first_val = false;
            }
            $query .= ")";
            if ($first) $first = false;
        }
        $query .= ";";
        DeserExecQuery ($query);
    }

    // Actualize autoincrement. The column for autoincrement in the game tables is always the first one.
    if ($tab['auto_increment'] != null) {
        $query = "ALTER TABLE `".$db_prefix.$name."` MODIFY `".$tab['cols'][0]."` INT AUTO_INCREMENT, AUTO_INCREMENT=".$tab['auto_increment'].";";
        DeserExecQuery ($query);
    }
}

/**
 * Restores the whole database from a JSON serialized string.
 *
 * @param string $text JSON string with serialized tables.
 * @return void
 */
function DeserializeDB (string $text) : void
{
    $tabs = json_decode ($text, true);

    foreach ($tabs as $i=>$tab) {
        DeserializeTable ($i, $tab);
    }
}

?>