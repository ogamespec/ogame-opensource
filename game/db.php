<?php

// Работа за базой данных MySQL.

function dbconnect ($db_host, $db_user, $db_pass, $db_name)
{
    $db_connect = @mysql_connect($db_host, $db_user, $db_pass);
    $db_select = @mysql_select_db($db_name);
    if (!$db_connect) {
        die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to establish connection to MySQL</b><br>".mysql_errno()." : ".mysql_error()."</div>");
    } elseif (!$db_select) {
        die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to select MySQL database</b><br>".mysql_errno()." : ".mysql_error()."</div>");
    }
}

function dbquery ($query, $mute=FALSE)
{
    $result = @mysql_query($query);
    if (!$result && $mute==FALSE) {
        echo "$query <br>";
        echo mysql_error();
        return false;
    }
    else  return $result;
}

function dbrows ($query)
{
    $result = @mysql_num_rows($query);
    return $result;
}

function dbarray ($query)
{
    $result = @mysql_fetch_assoc($query);
    if (!$result) {
        echo mysql_error();
        return false;
    }
    else return $result;
}

function dbfree ($result) {
    @mysql_free_result ($result);
}

// Увеличить глобальный счетчик вселенной и возвратить его последнее значение.
function IncrementDBGlobal ( $name)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."uni".";";
    $result = dbquery ($query);
    $unitab = dbarray ($result);
    $id = $unitab[$name]++;
    $query = "UPDATE ".$db_prefix."uni"." SET $name = ".$unitab[$name].";";
    dbquery ($query);
    return $id;
}

// Добавить строку в таблицу.
function AddDBRow ( $row, $tabname )
{
    global $db_prefix;
    $opt = " (";
    foreach ($row as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$row[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$db_prefix."$tabname VALUES".$opt;
    dbquery( $query);
    return mysql_insert_id ();
}

function LockTables ()
{
    global $db_prefix;
    $tabs = array ('uni','users','planets','ally','allyranks','allyapps','buddy','messages','notes','errors','debug','browse','queue','fleet','union','battledata','fleetlogs');
    $query = "LOCK TABLES loca_projects WRITE, loca_table WRITE";
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