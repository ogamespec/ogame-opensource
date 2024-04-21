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

?>