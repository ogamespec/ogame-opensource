<?php

// Функции для отладки и ошибок.

// Ошибка, аварийное завершение программы.
function Error ($text)
{
    global $GlobalUser;
    if ( !$GlobalUser ) {
        $GlobalUser = array ();
        $GlobalUser['player_id'] = 0;
    }

    $text = str_replace ( "\"", "&quot;", $text );
    $text = str_replace ( "'", "&rsquo;", $text );
    $text = str_replace ( "`", "&lsquo;", $text );

    $now = time ();

    $error = array ( null, $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], $text, $now );
    $id = AddDBRow ( $error, 'errors' );

    Logout ( $_GET['session'] );    // Завершить сессию.

    ob_clean ();    // Отменить предыдущий HTML.
    PageHeader ("error", true, false);

    echo "<center><font size=\"3\"><b>\n";
    echo "<br /><br />\n";
    echo "<font color=\"#FF0000\">Произошла ошибка</font> - $text\n";
    echo "<br /><br />\n";
    echo BackTrace() . "<br /><br />\n";
    echo "Аварийное завершение программы.<br/><br/>Обратитесь в Службу поддержки или на форум, в раздел \"Ошибки\".\n";
    echo "<br /><br />\n";
    echo "Error-ID: $id</b></font></center>\n";

    //PageFooter ();
    ob_end_flush ();
    exit ();
}

// Добавить отладочное сообщение.
function Debug ($message)
{
    global $GlobalUser;
    if ( !$GlobalUser ) return;

    $message = str_replace ( "\"", "&quot;", $message );
    $message = str_replace ( "'", "&rsquo;", $message );
    $message = str_replace ( "`", "&lsquo;", $message );

    $now = time ();

    $error = array ( null, $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], $message, $now );
    $id = AddDBRow ( $error, 'debug' );
}

// Трассировка вызовов.
function BackTrace ()
{
    $bt =  debug_backtrace () ;

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

// Сохранить историю переходов
function BrowseHistory ()
{
    global $GlobalUser;

    if ( $GlobalUser['sniff'] )
    {
        $getdata = serialize ( $_GET );
        $postdata = serialize ( $_POST );
        $log = array ( null, $GlobalUser['player_id'], $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $getdata, $postdata, time() );
        AddDBRow ( $log, 'browse' );
    }
}

// Проверка безопасности.
function SecurityCheck ( $match, $text, $notes )
{
    if ( !preg_match ( $match, $text ) ) Error ( "Нарушение безопасности: " . $notes );
}

// Добавить IP адрес в таблицу.
function LogIPAddress ( $ip, $user_id, $reg=0)
{
    $log = array ( null, $ip, $user_id, $reg, time () );
    AddDBRow ( $log, 'iplogs' );
}

// Получить последнию регистрацию с указанного IP адреса.
function GetLastRegistrationByIP ( $ip )
{
    global $db_prefix;

    $query = "SELECT * FROM ".$db_prefix."iplogs WHERE ip = '".$ip."' AND reg = 1 ORDER BY date DESC LIMIT 1";
    $result = dbquery ( $query );
    if ( $result == null ) return 0;
    else {
        $row = dbarray ( $result );
        return $row['date'];
    }
}

// Логи действия пользователей.
function UserLog ($owner_id, $type, $text, $when=0)
{
    global $db_prefix;
    if ($when == 0) $when = time ();
    $log = array ( null, $owner_id, $when, $type, $text );
    AddDBRow ( $log, 'userlogs' );
    $ago = $when - 2 * 7 * 24 * 60 * 60;
    $query = "DELETE FROM ".$db_prefix."userlogs WHERE date < $ago;";
    dbquery ($query);
}

// Записывает в базу данные игрока при попытке взлома игры.
// Админ должен периодически проверять слишком умных игроков, которые пытаются взломать игру.
function Hacking ($code)
{
    global $GlobalUni;
    loca_add ( "hackmsg", $GlobalUni['lang'] );

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

    Debug ( 'HACKING ATTEMPT: ' . loca($code) . "<br><br>" . $get . $post . $method );

    // Увеличить счетчик попыток взлома.
    // Счётчик автоматически сбрасывается после релогина.
    IncrementHackCounter ();
}

// Вернуть лог SQL запросов, если у пользователя включен показ отладочной информации.
function GetSQLQueryLogText ()
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