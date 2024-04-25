<?php

// Working with MySQL database.

$query_counter = 0;
$query_log = "";
$db_connect = 0;

function dbconnect ($db_host, $db_user, $db_pass, $db_name)
{
    global  $query_counter, $query_log, $db_connect;
    $db_connect = @mysqli_connect($db_host, $db_user, $db_pass);
    $db_select = @mysqli_select_db($db_connect, $db_name);
    if (!$db_connect) {
        die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to establish connection to MySQL</b></div>");
    } elseif (!$db_select) {
        die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to select MySQL database</b></div>");
    }

    $query_counter = 0;
    $query_log = "";
}

function dbquery ($query, $mute=FALSE)
{
    global  $query_counter, $query_log, $db_connect;
    $query_counter ++;
    $query_log .= $query . "<br>\n";
    $result = @mysqli_query($db_connect, $query);
    if (!$result && $mute==FALSE) {
        echo "$query <br>";
        echo mysqli_error ($db_connect);
        Debug ( mysqli_error($db_connect) . "<br>" . $query . "<br>" . BackTrace () ) ;
        return false;
    }
    else  return $result;
}

function dbrows ($query)
{
    $result = @mysqli_num_rows($query);
    return $result;
}

function dbarray ($query)
{
    global $db_connect;
    $result = @mysqli_fetch_assoc($query);
    if (!$result) {
        echo mysqli_error($db_connect);
        return false;
    }
    else return $result;
}

function dbfree ($result) {
    @mysqli_free_result ($result);
}

// Add a row to the table.
function AddDBRow ( $row, $tabname )
{
    global $db_connect, $db_prefix;
    $opt = " (";
    foreach ($row as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        if ( $row[$i] == null && $i == 0 ) $opt .= 'NULL';
        else $opt .= "'".$row[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$db_prefix."$tabname VALUES".$opt;
    dbquery( $query);
    return mysqli_insert_id ($db_connect);
}

// Table locking is critical in a multi-user environment. It is protection against simultaneous work with the database from several users.
// Think of it as analogous to multitasking lock (mutex).

function LockTables ()
{
    global $db_prefix;
    $tabs = array ('users','planets','ally','allyranks','allyapps','buddy','messages','notes','errors','debug','browse','queue','buildqueue','fleet','union','battledata','fleetlogs','iplogs','pranger','exptab','coltab','template','botvars','userlogs','botstrat','mods');
    $query = "LOCK TABLES ".$db_prefix."uni WRITE";
    foreach ( $tabs as $i=>$name ) 
    {
        $query .= ", ".$db_prefix.$name." WRITE";
    }
    dbquery ($query);
}

function UnlockTables ()
{
    dbquery ( "UNLOCK TABLES" );
}

function SerializeTable ($name)
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

function SerializeDB ()
{
    include "install_tabs.php";

    $db_tabs = array();

    foreach ($tabs as $i=>$cols) {
        $db_tabs[$i] = SerializeTable ($i);
    }

    return json_encode ($db_tabs, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
}

function DeserializeDB ($text)
{
    // TRUNCATE TABLE `uni1_battledata`;

    // INSERT INTO `uni1_battledata` (`battle_id`, `source`, `title`, `report`, `date`) VALUES
    // (1, 'Строка\nв формате utf-8', 1710690768),
    // (2, 'Строка\nв формате utf-8', 1710690769);

    // ALTER TABLE `uni1_botstrat` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
}

?>