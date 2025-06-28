<?php

// Various auxiliary utilities that used to be scattered all over the place.

function method () { return $_SERVER['REQUEST_METHOD']; }

function scriptname () {
    $break = explode('/', $_SERVER["SCRIPT_NAME"]);
    return $break[count($break) - 1];
}

function hostname ($dir = "game") {
    if (!empty($_SERVER['HTTPS']))  { // get if request is http or https
       $encr ="https://";
    }else{
       $encr ="http://";
    }
    $host = $encr . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/$dir/" );
    return substr ( $host, 0, $pos+1 );
}

function nicenum ($number)
{
    return number_format($number,0,",",".");
}

function RedirectHome ()
{
    // The start page address can be found in config.php
    global $StartPage;
    echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage' /></head><body></body>";
}

// Connect to the database
function InitDB ()
{
    global $db_host, $db_user, $db_pass, $db_name;
    dbconnect ($db_host, $db_user, $db_pass, $db_name);
    dbquery("SET NAMES 'utf8';");
    dbquery("SET CHARACTER SET 'utf8';");
    dbquery("SET SESSION collation_connection = 'utf8_general_ci';");
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

// Here is a function to sort an array by the key of its sub-array
function sksort (&$array, $subkey="id", $sort_ascending=false)
{
    $temp_array = array ();
    if (count($array))
        $temp_array[key($array)] = array_shift($array);

    foreach($array as $key => $val){
        $offset = 0;
        $found = false;
        foreach($temp_array as $tmp_key => $tmp_val)
        {
            if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
            {
                $temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
                                            array($key => $val),
                                            array_slice($temp_array,$offset)
                                          );
                $found = true;
            }
            $offset++;
        }
        if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
    }

    if ($sort_ascending) $array = array_reverse($temp_array);
    else $array = $temp_array;
    return $array;
}

function mail_utf8($to, $subject = '(No subject)', $message = '', $header = '')
{
    $header_ = 'MIME-Version: 1.0' . "\n" . 'Content-type: text/plain; charset=UTF-8' . "\n";
    mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
}

function localhost ($ip)
{
    return $ip === "127.0.0.1" || $ip === "::1";
}

// Cut all sorts of injections out of the string.
function SecureText ( $text )
{
    $search = array ( "'<script[^>]*?>.*?</script>'si",  // Cuts out javaScript
                      "'<[\/\!]*?[^<>]*?>'si",           // Cuts HTML tags
                      "'([\r\n])[\s]+'" );             // Cuts out whitespace characters
    $replace = array ("", "", "\\1", "\\1" );
    $str = preg_replace($search, $replace, $text);
    $str = str_replace ("`", "", $str);
    $str = str_replace ("'", "", $str);
    $str = str_replace ("\"", "", $str);
    $str = str_replace ("%0", "", $str);
    return $str;
}

?>