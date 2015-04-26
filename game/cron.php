<?php

// Optionally you can set up CRON on this file, for periodic queue updates.

if ( file_exists ("config.php"))
{
    require_once "config.php";
    require_once "db.php";

    // Соединиться с базой данных
    dbconnect ($db_host, $db_user, $db_pass, $db_name);
    dbquery("SET NAMES 'utf8';");
    dbquery("SET CHARACTER SET 'utf8';");
    dbquery("SET SESSION collation_connection = 'utf8_general_ci';");    

    require_once "loca.php";
    require_once "bbcode.php";
    require_once "uni.php";
    require_once "prod.php";
    require_once "planet.php";
    require_once "user.php";
    require_once "msg.php";
    require_once "notes.php";
    require_once "queue.php";
    require_once "page.php";
    require_once "ally.php";
    require_once "unit.php";
    require_once "fleet.php";
    require_once "battle.php";
    require_once "debug.php";
    require_once "galaxytool.php";
    require_once "bot.php";

    $GlobalUni = LoadUniverse ();

    function method () { return $_SERVER['REQUEST_METHOD']; }
    function scriptname () {
        $break = explode('/', $_SERVER["SCRIPT_NAME"]);
        return $break[count($break) - 1]; 
    }
    function hostname () {
        $host = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
        $pos = strrpos ( $host, "/game/index.php" );
        return substr ( $host, 0, $pos+1 );
    }

    function nicenum ($number)
    {
        return number_format($number,0,",",".");
    }

    function RedirectHome ()
    {
        global $StartPage;
        echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage' /></head><body></body>";
    }

    // Format string, according to tokens from the text. Tokens are represented as #1, #2 and so on.
    function va ($subject)
    {
        $num_arg = func_num_args();
        $pattern = array ();
        for ($i=1; $i<$num_arg; $i++)
        {
            $pattern[$i-1] = "/#$i/";
            $replace[$i-1] = func_get_arg($i);
        }
        return preg_replace($pattern, $replace, $subject);
    }

    $GlobalUser = LoadUser ( 99999 );        // space

    loca_add ( "common", $GlobalUni['lang'] );
    loca_add ( "technames", $GlobalUni['lang'] );

    UpdateQueue ( time() );
}

?>