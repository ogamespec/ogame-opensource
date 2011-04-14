<?php

// Функции для отладки и ошибок.

// Ошибка, аварийное завершение программы.
function Error ($text)
{
    global $GlobalUser;
    if ( !$GlobalUser ) return;

    $id = IncrementDBGlobal ( 'nexterror' );
    $now = time ();

    $error = array ( $id, $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], bb($text), $now );
    AddDBRow ( $error, 'errors' );

    Logout ( $_GET['session'] );    // Завершить сессию.

    ob_clean ();    // Отменить предыдущий HTML.
    PageHeader ("error", true, false);

    echo "<center><font size=\"3\"><b>\n";
    echo "<br /><br />\n";
    echo "<font color=\"#FF0000\">Произошла ошибка</font>\n";
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

    $id = IncrementDBGlobal ( 'nexterror' );
    $now = time ();

    $error = array ( $id, $GlobalUser['player_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_URI'], bb($message), $now );
    AddDBRow ( $error, 'debug' );
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

?>