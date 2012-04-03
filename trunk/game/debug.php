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

    $now = time ();

    $error = array ( null, $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], bb($text), $now );
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

    $now = time ();

    $error = array ( null, $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], bb($message), $now );
    $id = AddDBRow ( $error, 'debug' );
}

// Трассировка вызовов.
function BackTrace ()
{
    $bt =  debug_backtrace () ;

    $trace  = "";
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

?>