<?php

// Various auxiliary utilities that used to be scattered all over the place.

function method () : string {
    return $_SERVER['REQUEST_METHOD'];
}

function scriptname () : string {
    $break = explode('/', $_SERVER["SCRIPT_NAME"]);
    return $break[count($break) - 1];
}

function hostname (string $dir = "game") : string {
    if (!empty($_SERVER['HTTPS']))  { // get if request is http or https
       $encr ="https://";
    }else{
       $encr ="http://";
    }
    $host = $encr . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/$dir/" );
    return substr ( $host, 0, $pos+1 );
}

function nicenum (float|int $number) : string
{
    return number_format($number,0,",",".");
}

function RedirectHome () : void
{
    // The start page address can be found in config.php
    global $StartPage;
    echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage' /></head><body></body>";
}

// Format string, according to tokens from the text. Tokens are represented as #1, #2 and so on.
function va (string $subject) : string
{
    $num_arg = func_num_args();
    $pattern = array ();
    $replace = array ();
    for ($i=1; $i<$num_arg; $i++)
    {
        $pattern[$i-1] = "/#$i/";
        $replace[$i-1] = func_get_arg($i);
    }
    return preg_replace($pattern, $replace, $subject);
}

// Here is a function to sort an array by the key of its sub-array
function sksort (array &$array, string $subkey="id", bool $sort_ascending=false) : array
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

function mail_utf8(string $to, string $subject = '(No subject)', string $message = '', string $header = '') : void
{
    $header_ = 'MIME-Version: 1.0' . "\n" . 'Content-type: text/plain; charset=UTF-8' . "\n";
    mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
}

function localhost (string $ip) : bool
{
    return $ip === "127.0.0.1" || $ip === "::1";
}

// Cut all sorts of injections out of the string.
function SecureText ( string $text ) : string
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

/**
 * Validation rules for parameters.
 * Format: 'parameter_name' => ['type', 'max_length', 'regex_pattern']
 * 
 * Supported types: 'integer', 'string'
 * Use 'null' for no length/regex checks.
 */
$paramRules = [
    'session' => ['string', 12, '/^[a-f0-9]+$/i'],      // Hex chars (a-f, 0-9)
    'feedid' => ['string', 32, '/^[a-f0-9]+$/i'],       // Hex chars (a-f, 0-9)
    'mid' => ['integer', null, '/^\d+$/'],          // Only digits
    'page' => ['string', 20, '/^[a-z0-9_]+$/i'],    // Letters + digits + underscores
    'cp' => ['integer', null, '/^\d+$/'],          // Only digits

    // Add more parameters here...
    // https://github.com/ogamespec/ogame-opensource/blob/master/Wiki/ru/pages.md
];

/**
 * Validates input parameters against defined rules.
 * 
 * @param array $inputParams - Input data ($_GET, $_POST, etc.)
 * @return array - ['success' => bool, 'errors' => string[]]
 */
function CheckParams (array $inputParams): array {
    global $paramRules;
    $errors = [];

    foreach ($paramRules as $param => $rule) {
        // Check if parameter exists
        if (!isset($inputParams[$param])) {
            //$errors[] = "Parameter '$param' is missing";
            continue;
        }

        $value = $inputParams[$param];
        [$type, $maxLength, $regex] = $rule;

        // Type validation (using switch instead of match)
        $isValid = false;
        switch ($type) {
            case 'integer':
                $isValid = is_numeric($value) && (string)(int)$value === (string)$value;
                break;
            case 'string':
                $isValid = is_string($value);
                break;
        }

        if (!$isValid) {
            $errors[] = "Parameter '$param' must be of type $type";
            continue;
        }

        // Length check (for strings)
        if ($type === 'string' && $maxLength !== null && mb_strlen($value) > $maxLength) {
            $errors[] = "Parameter '$param' exceeds max length ($maxLength)";
        }

        // Regex validation
        if ($regex !== null && !preg_match($regex, $value)) {
            $errors[] = "Parameter '$param' has invalid format";
        }
    }

    return [
        'success' => empty($errors),
        'errors' => $errors,
    ];
}

function array_insert_after_key(array &$array, string $after_key, string $new_key, mixed $new_value) : array {
    $keys = array_keys($array);
    $index = array_search($after_key, $keys);

    if ($index === false) {
        // Key not found, append to the end
        $array[$new_key] = $new_value;
        return $array;
    }

    // Split the array into two parts
    $part1 = array_slice($array, 0, $index + 1, true);
    $part2 = array_slice($array, $index + 1, null, true);

    // Insert the new element between the two parts using the union operator (+)
    $part1[$new_key] = $new_value;
    $new_array = $part1 + $part2;
    $array = $new_array;

    return $array;
}

/**
 * Insert a new key-value pair into an array before a specified key.
 * Preserves all original keys and their order.
 *
 * @param array &$array The original array (passed by reference)
 * @param string $before_key The key before which to insert the new element
 * @param string $new_key The key for the new element
 * @param mixed $new_value The value for the new element
 * @return array The modified array
 */
function array_insert_before_key(array &$array, string $before_key, string $new_key, mixed $new_value) : array {
    // Get all keys from the original array
    $keys = array_keys($array);
    
    // Find the position of the target key
    $index = array_search($before_key, $keys);

    if ($index === false) {
        // Key not found, prepend to the beginning
        // Create new array with the new element first, then original array
        $new_array = [$new_key => $new_value] + $array;
        $array = $new_array;
        return $array;
    }

    // Split the array into two parts:
    // Part 1: elements before the target position
    // Part 2: elements from target position to the end
    $part1 = array_slice($array, 0, $index, true);
    $part2 = array_slice($array, $index, null, true);

    // Insert the new element between the two parts
    // Using union operator (+) to preserve keys
    $part1[$new_key] = $new_value;
    $new_array = $part1 + $part2;
    $array = $new_array;

    return $array;
}

function gen_trivial_password () : string
{
    $pass = "";
    $syllables = "er,in,tia,wol,fe,pre,vet,jo,nes,al,len,son,cha,ir,ler,bo,ok,tio,nar,sim,ple,bla,ten,toe,cho,co,lat,spe,ak,er,po,co,lor,pen,cil,li,ght,wh,at,the,he,ck,is,mam,bo,no,fi,ve,any,way,pol,iti,cs,ra,dio,sou,rce,sea,rch,pa,per,com,bo,sp,eak,st,fi,rst,gr,oup,boy,ea,gle,tr,ail,bi,ble,brb,pri,dee,kay,en,be,se";

    $syllable_array = explode (",", $syllables);
    srand ((double)microtime()*1000000);
    for ($count=1; $count<=4; $count++) {
        if (rand()%10 == 1) $pass .= sprintf ("%0.0f", (rand()%50)+1);
        else $pass .= sprintf ("%s", $syllable_array[rand()%62]);
    }
    return $pass;
}

// Return a string of durations by days, hours, minutes, seconds.
function DurationFormat ( int $seconds ) : string
{
    $res = "";
    $days = floor ($seconds / (24*3600));
    $hours = floor (intdiv($seconds, 3600) % 24);
    $mins = floor (intdiv($seconds, 60) % 60);
    $secs = round ($seconds % 60);
    if ($days) {
        $res .= "$days".loca("TIME_DAYS")." ";
    }
    if ($hours || $days) $res .= "$hours".loca("TIME_HOUR")." ";
    if ($mins || $days) $res .= "$mins".loca("TIME_MIN")." ";
    if ($secs) $res .= "$secs".loca("TIME_SEC");
    return $res;
}

function RunBackgroundProcess(string $command) : int {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $handle = popen("start /B " . $command, "r");
        pclose($handle);
        return 0;
    } else {
        // Linux/Unix
        exec("nohup " . $command . " > /dev/null 2>&1 & echo $!", $output);
        return (int)$output[0]; // Returning the PID
    }
}

function FloatEqual (float $a, float $b) : bool {
    return abs($a-$b) < PHP_FLOAT_EPSILON;
}

function isValidEmail(string $email) : mixed {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

?>