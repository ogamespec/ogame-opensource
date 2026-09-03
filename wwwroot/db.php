<?php

$query_counter = 0;
$query_log = "";
$db_connect = 0;

function dbconnect (string $db_host, string $db_user, string $db_pass, string $db_name) : void
{
    global  $query_counter, $query_log, $db_connect;
    $db_connect = @mysqli_connect($db_host, $db_user, $db_pass);
    if (!$db_connect) {
        die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to establish connection to MySQL</b><br>".mysqli_connect_errno()." : ".mysqli_connect_error()."</div>");
    }
    $db_select = @mysqli_select_db($db_connect, $db_name);
    if (!$db_select) {
        die("<div style='font-family:Verdana;font-size:11px;text-align:center;'><b>Unable to select MySQL database</b><br>".mysqli_errno($db_connect)." : ".mysqli_error($db_connect)."</div>");
    }
}

function dbquery (string $query, bool $mute=false) : mysqli_result|bool
{
    global  $query_counter, $query_log, $db_connect;
    $query_counter ++;
    $query_log .= $query . "<br>\n";
    $result = @mysqli_query($db_connect, $query);
    if (!$result && $mute==false) {
        echo "$query <br>";
        echo mysqli_error($db_connect);
        // Debug()/BackTrace() are defined in the game core, which is not
        // loaded on the start page, so log plainly instead of calling them.
        error_log (mysqli_error($db_connect) . "<br>" . $query);
        return false;
    }
    else  return $result;
}

function dbrows (mysqli_result $query) : int
{
    return (int) @mysqli_num_rows($query);
}

function dbarray (mysqli_result $query) : array|false
{
    global $db_connect;
    $result = @mysqli_fetch_assoc($query);
    if (!$result) {
        echo mysqli_error($db_connect);
        return false;
    }
    else return $result;
}

function dbfree (mysqli_result $result) : void {
    @mysqli_free_result ($result);
}

