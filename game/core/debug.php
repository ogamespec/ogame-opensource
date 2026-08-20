<?php
/**
 * @file debug.php
 * @brief Debug helpers and error reporting.
 * @details Provides debugging and tracing helpers used while developing and troubleshooting the game.
 */
// Functions for debugging and errors.

/**
 * Record an error, end the user session and terminate the program.
 *
 * @param string $text Error message.
 * @return never This function always exits.
 */
function Error (string $text) : never
{
    global $GlobalUser;
    global $GlobalUni;
    global $from_cron;
    global $DefaultLanguage;
    if ( $GlobalUser == null ) {
        $GlobalUser = array ();
        $GlobalUser['player_id'] = 0;
    }

    if ( isset($GlobalUser['lang']) ) $loca_lang = $GlobalUser['lang'];
    else if ( isset($GlobalUni['lang']) ) $loca_lang = $GlobalUni['lang'];
    else $loca_lang = $DefaultLanguage;

    $text = str_replace ( "\"", "&quot;", $text );
    $text = str_replace ( "'", "&rsquo;", $text );
    $text = str_replace ( "`", "&lsquo;", $text );

    $now = time ();

    $ip = $from_cron ? '0.0.0.0' : $_SERVER['REMOTE_ADDR'];
    $agent = $from_cron ? 'cron' : $_SERVER['HTTP_USER_AGENT'];
    $url = $from_cron ? 'cron.php' : $_SERVER['REQUEST_URI'];

    $error = array ( 'owner_id' => $GlobalUser['player_id'], 'ip' => $ip, 'agent' => $agent, 'url' => $url, 'text' => $text, 'date' => $now );
    $id = AddDBRow ( $error, 'errors' );

    if ($from_cron) exit();

    Logout ( $_GET['session'] );    // End the session.

    ob_clean ();    // Undo Previous HTML.
    PageHeader ("error", true, false);

    echo "<center><font size=\"3\"><b>\n";
    echo "<br /><br />\n";
    echo "<font color=\"#FF0000\">".loca_lang("DEBUG_ERROR", $loca_lang)."</font> - $text\n";
    echo "<br /><br />\n";
    echo BackTrace() . "<br /><br />\n";
    echo loca_lang("DEBUG_ERROR_INFO1", $loca_lang) . "<br/><br/>" . loca_lang("DEBUG_ERROR_INFO2", $loca_lang) . "\n";
    echo "<br /><br />\n";
    echo "Error-ID: $id</b></font></center>\n";

    //PageFooter ();
    ob_end_flush ();
    exit ();
}

/**
 * Add a debug message to the debug log.
 *
 * @param string $message Debug message.
 */
function Debug (string $message) : void
{
    global $GlobalUser;
    global $from_cron;
    if ( $GlobalUser == null ) return;

    $message = str_replace ( "\"", "&quot;", $message );
    $message = str_replace ( "'", "&rsquo;", $message );
    $message = str_replace ( "`", "&lsquo;", $message );

    $now = time ();

    $ip = $from_cron ? '0.0.0.0' : $_SERVER['REMOTE_ADDR'];
    $agent = $from_cron ? 'cron' : $_SERVER['HTTP_USER_AGENT'];
    $url = $from_cron ? 'cron.php' : $_SERVER['REQUEST_URI'];

    $error = array ( 'owner_id' => $GlobalUser['player_id'], 'ip' => $ip, 'agent' => $agent, 'url' => $url, 'text' => $message, 'date' => $now );
    $id = AddDBRow ( $error, 'debug' );
}

/**
 * Build a formatted string of the current call trace.
 *
 * @return string HTML formatted call trace.
 */
function BackTrace () : string
{
    $bt = debug_backtrace (DEBUG_BACKTRACE_IGNORE_ARGS);

    $trace  = "";
    $sp = 0;
    foreach($bt as $k=>$v) 
    { 
        extract($v); 
        $file=substr($file,1+strrpos($file,"/")); 
        if($file=="db.php")continue; // the db object 
        $trace.=str_repeat("&nbsp;",++$sp); //spaces(++$sp); 
        $trace.="file=$file, line=$line, function=$function<br>";
    }
    return $trace;
}

/**
 * Save the user's browse history when sniffing is enabled.
 */
function BrowseHistory () : void
{
    global $GlobalUser;

    if ($GlobalUser == null) return;
    if ( isset($GlobalUser['sniff']) && $GlobalUser['sniff'] )
    {
        $getdata = serialize ( $_GET );
        $postdata = serialize ( $_POST );
        $log = array ( 'owner_id' => $GlobalUser['player_id'], 'url' => $_SERVER['REQUEST_URI'], 'method' => $_SERVER['REQUEST_METHOD'], 'getdata' => $getdata, 'postdata' => $postdata, 'date' => time() );
        AddDBRow ( $log, 'browse' );
    }
}

/**
 * Check that the text matches the pattern, raising an error on a security breach.
 *
 * @param string $match Regular expression pattern.
 * @param string $text Text to check.
 * @param string $notes Additional notes appended to the error message.
 */
function SecurityCheck ( string $match, string $text, string $notes ) : void
{
    global $GlobalUni;
    if ( !preg_match ( $match, $text ) ) Error ( loca_lang("DEBUG_SECURITY_BREACH", $GlobalUni['lang']) . $notes );
}

/**
 * Log an IP address visit in the IP log table.
 *
 * @param string $ip IP address.
 * @param int $user_id Player ID.
 * @param int $reg Whether the log entry marks a registration.
 */
function LogIPAddress ( string $ip, int $user_id, int $reg=0) : void
{
    $log = array ( 'ip' => $ip, 'user_id' => $user_id, 'reg' => $reg, 'date' => time () );
    AddDBRow ( $log, 'iplogs' );
}

/**
 * Get the timestamp of the last registration made from the given IP address.
 *
 * @param string $ip IP address.
 * @return int Registration timestamp, or 0 if none found.
 */
function GetLastRegistrationByIP ( string $ip ) : int
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."iplogs WHERE ip = '".$ip."' AND reg = 1 ORDER BY date DESC LIMIT 1";
    $result = dbquery ( $query );
    if ( $result == null ) return 0;
    else {
        if (dbrows($result) == 0) return 0;
        $row = dbarray ( $result );
        return $row['date'];
    }
}

/**
 * Write a user action to the user log and delete entries older than two weeks.
 *
 * @param int $owner_id Player ID.
 * @param string $type Log entry type.
 * @param string $text Log entry text.
 * @param int $when Timestamp of the entry; defaults to the current time.
 */
function UserLog (int $owner_id, string $type, string $text, int $when=0) : void
{
    global $db_prefix;
    if ($when == 0) $when = time ();
    $log = array ( 'owner_id' => $owner_id, 'date' => $when, 'type' => $type, 'text' => $text );
    AddDBRow ( $log, 'userlogs' );
    $ago = $when - 2 * 7 * 24 * 60 * 60;
    $query = "DELETE FROM ".$db_prefix."userlogs WHERE date < $ago;";
    dbquery ($query);
}

/**
 * Log the player's request data when a hacking attempt is detected.
 * The admin should periodically check for players who try to hack the game.
 *
 * @param string $code Localization code of the hacking attempt message.
 */
function Hacking (string $code) : void
{
    global $GlobalUni;

    $get = "GET LIST:<br>";
    foreach ( $_GET as $i=>$value)
    {
        $get .= "&nbsp;" . $i . " = [" . $value . "]<br>";
    }
    $get .= "<br>";

    $post = "POST LIST:<br>";
    foreach ( $_POST as $i=>$value)
    {
        $post .= "&nbsp;" . $i . " = [" . $value . "]<br>";
    }
    $post .= "<br>";

    $method = "METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";

    Debug ( 'HACKING ATTEMPT: ' . loca_lang($code, $GlobalUni['lang']) . "<br><br>" . $get . $post . $method );

    // Increase the tamper attempt counter.
    // The counter is automatically reset after a relogin.
    IncrementHackCounter ();
}

/**
 * Return the SQL query log as HTML if the user has debugging information enabled.
 *
 * @return string HTML markup of the SQL query log.
 */
function GetSQLQueryLogText () : string
{
    global $query_log;

    $res = "";

    $res .= "<style>\n";
    $res .= ".sql_overlay {\n";
    $res .= "  position: fixed;\n";
    $res .= "  top: 0;\n";
    $res .= "  bottom: 0;\n";
    $res .= "  left: 0;\n";
    $res .= "  right: 0;\n";
    $res .= "  background: rgba(0, 0, 0, 0.7);\n";
    $res .= "  transition: opacity 500ms;\n";
    $res .= "  visibility: hidden;\n";
    $res .= "  opacity: 0;\n";
    $res .= "}\n";
    $res .= ".sql_overlay:target {\n";
    $res .= "  visibility: visible;\n";
    $res .= "  opacity: 1;\n";
    $res .= "}\n";
    $res .= ".sql_popup {\n";
    $res .= "  margin: 70px auto;\n";
    $res .= "  padding: 20px;\n";
    $res .= "  background: #fff;\n";
    $res .= "  border-radius: 5px;\n";
    $res .= "  width: 30%;\n";
    $res .= "  position: relative;\n";
    $res .= "  transition: all 5s ease-in-out;\n";
    $res .= "  color: black;\n";    
    $res .= "}\n";
    $res .= ".sql_popup .sql_close {\n";
    $res .= "  position: absolute;\n";
    $res .= "  top: 20px;\n";
    $res .= "  right: 30px;\n";
    $res .= "  transition: all 200ms;\n";
    $res .= "  font-size: 30px;\n";
    $res .= "  font-weight: bold;\n";
    $res .= "  text-decoration: none;\n";
    $res .= "  color: #333;\n";
    $res .= "}\n";
    $res .= ".sql_popup .sql_close:hover {\n";
    $res .= "  color: #06D85F;\n";
    $res .= "}\n";
    $res .= ".sql_popup .sql_content {\n";
    $res .= "  max-height: 60%;\n";
    $res .= "  overflow: auto;\n";
    $res .= "  text-align: left;\n";
    $res .= "}\n";
    $res .= "</style>\n";

    $res .= "<a href=\"#popup1\">Show SQL query log</a>\n";
    $res .= "<div id=\"popup1\" class=\"sql_overlay\">\n";
    $res .= "    <div class=\"sql_popup\">\n";
    $res .= "        <h2>SQL Query Log</h2>\n";
    $res .= "        <a class=\"sql_close\" href=\"#\">&times;</a>\n";
    $res .= "        <div class=\"sql_content\">\n";
    $res .= $query_log;
    $res .= "        </div>\n";
    $res .= "    </div>\n";
    $res .= "</div>\n";

    return $res;
}

?>