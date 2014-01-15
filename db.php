<?php

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
    global  $query_counter, $query_log;
    $query_counter ++;
    $query_log .= $query . "<br>\n";
    $result = @mysql_query($query);
    if (!$result && $mute==FALSE) {
        echo "$query <br>";
        echo mysql_error();
        Debug ( mysql_error() . "<br>" . $query . "<br>" . BackTrace () ) ;
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

