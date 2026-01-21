<?php

/** @var string $DefaultLanguage */
/** @var array $Languages */

// Installation script.
// Creates all the necessary tables in the database, as well as the config.php configuration file, to access the database.
// Doesn't work if config.php file is created.

// Add error output for this early stage of the installation
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "core/core.php";

if ( !key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = $DefaultLanguage;
else $loca_lang = $_COOKIE['ogamelang'];
if ( !key_exists ( $loca_lang, $Languages ) ) $loca_lang = $DefaultLanguage;

loca_add ( "install", $loca_lang );
loca_add ( "menu", $loca_lang );

$InstallError = "<font color=gold>".loca('INSTALL_TIP')."</font>";

function uniurl () : string {
    $host = $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/game/install.php" );
    return substr ( $host, 0, $pos );
}

// TBD: Check the settings of the universe.
function CheckParameters () : bool
{
    global $InstallError;

    return true;
}

// Check if the configuration file has already been created - redirect to the main page.
if (file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=index.php' /></head><body></body></html>";
    exit ();
}

// Database tables
include "core/install_tabs.php";

// -------------------------------------------------------------------------------------------------------------------------

// Save the settings.
if ( key_exists("install", $_POST) && CheckParameters() )
{
    $now = time();

    //print_r ($_POST);

    $db_host = $_POST["db_host"];
    $db_user = $_POST["db_user"];
    $db_pass = $_POST["db_pass"];
    $db_name = $_POST["db_name"];
    $db_prefix = $_POST["db_prefix"];

    // Delete all tables and create new empty tables.
    InitDB ();

    foreach ( $tabs as $tabname => $tab )
    {
        $opt = " (";
        $first = true;
        foreach ( $tab as $row => $type )
        {
            if ( !$first ) $opt .= ", ";
            if ( $first ) $first = false;
            $opt .= "`".$row."`" . " " . $type;
        }
        $opt .= ")";

        $query = 'DROP TABLE IF EXISTS '.$db_prefix.$tabname;
        dbquery ($query, TRUE);
        $query = 'CREATE TABLE '.$db_prefix.$tabname.$opt." CHARACTER SET utf8 COLLATE utf8_general_ci";
        dbquery ($query, TRUE);
    }

    // Create the universe.
    $uni = array ( 
        'num' => $_POST["uni_num"],
        'speed' => $_POST["uni_speed"],
        'fspeed' => $_POST["uni_fspeed"],
        'galaxies' => $_POST["uni_galaxies"],
        'systems' => $_POST["uni_systems"],
        'maxusers' => $_POST["uni_maxusers"],
        'start_dm' => $_POST["start_dm"],
        'acs' => $_POST["uni_acs"],
        'fid' => $_POST["uni_fid"],
        'did' => $_POST["uni_did"],
        'rapid' => (key_exists("uni_rapid", $_POST) && $_POST["uni_rapid"]==="on"?1:0),
        'moons' => (key_exists("uni_moons", $_POST) && $_POST["uni_moons"]==="on"?1:0),
        'defrepair' => 70,
        'defrepair_delta' => 10,
        'usercount' => 1,
        'freeze' => 0,
        'news1' => '',
        'news2' => '',
        'news_until' => 0,
        'startdate' => $now,
        'battle_engine' => $_POST["uni_battle_engine"],
        'lang' => $_POST["uni_lang"],
        'ext_board' => $_POST["ext_board"],
        'ext_discord' => $_POST["ext_discord"],
        'ext_tutorial' => $_POST["ext_tutorial"],
        'ext_rules' => $_POST["ext_rules"],
        'ext_impressum' => $_POST["ext_impressum"],
        'php_battle' => (key_exists("php_battle", $_POST) && $_POST["php_battle"]==="on"?1:0),
        'force_lang' => (key_exists("force_lang", $_POST) && $_POST["force_lang"]==="on"?1:0),
        'max_werf' => intval($_POST["max_werf"]),
        'feedage' => intval($_POST["feedage"]),
        'modlist' => '',
        'hacks' => 0,
    );
    AddDBRow ($uni, "uni");

    // Create technical account "space"
    $md = md5 ( gen_trivial_password() . $_POST['db_secret'] );
    $user_space = array( 
        'player_id' => USER_SPACE, 'regdate' => $now, 'ally_id' => 0, 'joindate' => 0, 'allyrank' => 0, 'session' => "",  'private_session' => "", 
        'name' => "space", 'oname' => "space", 'name_changed' => 0, 'name_until' => 0,
        'password' => $md, 'temp_pass' => "", 'pemail' => "", 'email' => "",
        'email_changed' => 0, 'email_until' => 0, 'disable' => 0, 'disable_until' => 0, 'vacation' => 0, 'vacation_until' => 0, 'banned' => 0, 'banned_until' => 0, 'noattack' => 0, 'noattack_until' => 0,
        'lastlogin' => 0, 'lastclick' => 0, 'ip_addr' => "0.0.0.0", 'validated' => 1, 'validatemd' => "", 'hplanetid' => 1, 'admin' => 2, 'sortby' => 0, 'sortorder' => 0,
        'skin' => hostname() . "evolution/", 'useskin' => 1, 'deact_ip' => 1, 'maxspy' => 1, 'maxfleetmsg' => 3, 'lang' => $_POST["uni_lang"], 'aktplanet' => 0,
        'dm' => 0, 'dmfree' => 0, 'sniff' => 0, 'debug' => 0, 'trader' => 0, 'rate_m' => 0, 'rate_k' => 0, 'rate_d' => 0,
        'score1' => 0, 'score2' => 0, 'score3' => 0, 'place1' => 0, 'place2' => 0, 'place3' => 0,
        'oldscore1' => 0, 'oldscore2' => 0, 'oldscore3' => 0, 'oldplace1' => 0, 'oldplace2' => 0, 'oldplace3' => 0, 'scoredate' => 0,
        'flags' => USER_FLAG_DEFAULT, 'feedid' => "", 'lastfeed' => 0, 'com_until' => 0, 'adm_until' => 0, 'eng_until' => 0, 'geo_until' => 0, 'tec_until' => 0 );
    AddDBRow ($user_space, "users");

    // Create administrator account (Legor).
    $md = md5 ($_POST['admin_pass'] . $_POST['db_secret']);
    $user_legor = array(
        'player_id' => USER_LEGOR, 'regdate' => $now, 'ally_id' => 0, 'joindate' => 0, 'allyrank' => 0, 'session' => "",  'private_session' => "", 
        'name' => "legor", 'oname' => "Legor", 'name_changed' => 0, 'name_until' => 0,
        'password' => $md, 'temp_pass' => "", 'pemail' => $_POST['admin_email'], 'email' => $_POST['admin_email'],
        'email_changed' => 0, 'email_until' => 0, 'disable' => 0, 'disable_until' => 0, 'vacation' => 0, 'vacation_until' => 0, 'banned' => 0, 'banned_until' => 0, 'noattack' => 0, 'noattack_until' => 0,
        'lastlogin' => 0, 'lastclick' => 0, 'ip_addr' => "0.0.0.0", 'validated' => 1, 'validatemd' => "", 'hplanetid' => 1, 'admin' => 2, 'sortby' => 0, 'sortorder' => 0,
        'skin' => hostname() . "evolution/", 'useskin' => 1, 'deact_ip' => 1, 'maxspy' => 1, 'maxfleetmsg' => 3, 'lang' => $_POST["uni_lang"], 'aktplanet' => 1,
        'dm' => 0, 'dmfree' => 0, 'sniff' => 0, 'debug' => 0, 'trader' => 0, 'rate_m' => 0, 'rate_k' => 0, 'rate_d' => 0,
        'score1' => 0, 'score2' => 0, 'score3' => 0, 'place1' => 0, 'place2' => 0, 'place3' => 0,
        'oldscore1' => 0, 'oldscore2' => 0, 'oldscore3' => 0, 'oldplace1' => 0, 'oldplace2' => 0, 'oldplace3' => 0, 'scoredate' => 0,
        'flags' => USER_FLAG_DEFAULT, 'feedid' => "", 'lastfeed' => 0, 'com_until' => 0, 'adm_until' => 0, 'eng_until' => 0, 'geo_until' => 0, 'tec_until' => 0 );
    AddDBRow ($user_legor, "users");

    // Create the planet Arakis [1:1:2] and the moon Mond (yes, there's only one letter `r` in the planet name, it's a reference to Dune)
    $planet = array( 
        'planet_id' => 1, 'name' => "Arakis", 'type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 2, 'owner_id' => USER_LEGOR, 
        'diameter' => 12800, 'temp' => 40, 'fields' => 0, 'maxfields' => 163, 'date' => $now,
        'mprod' => 1, 'kprod' => 1, 'dprod' => 1, 'sprod' => 1, 'fprod' => 1, 'ssprod' => 1, 'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
    AddDBRow ($planet, "planets");

    $moon = array( 
        'planet_id' => 2, 'name' => "Mond", 'type' => PTYP_MOON, 'g' => 1, 's' => 1, 'p' => 2, 'owner_id' => USER_LEGOR, 
        'diameter' => 8944, 'temp' => 10, 'fields' => 0, 'maxfields' => 1, 'date' => $now,
        'mprod' => 1, 'kprod' => 1, 'dprod' => 1, 'sprod' => 1, 'fprod' => 1, 'ssprod' => 1, 'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
    AddDBRow ($moon, "planets");

    // Add default Expedition Parameters.
    $exptab = array ( 
        'chance_success' => 70,         // Success chance, else Nothing
        'depleted_min' => 25, 'depleted_med' => 50, 'depleted_max' => 75,         // Depletion counter: <=not depleted, <=small, <=medium, else strong
        'chance_depleted_min' => 25, 'chance_depleted_med' => 50, 'chance_depleted_max' => 75,         // Change of failure on depletion: 0% explicit if not depleted, 25% for small, 50% for medium, 75% for strong
        'chance_alien' => 95,         // If roll >=: Aliens
        'chance_pirates' => 85,         // else If roll >=: Pirates
        'chance_dm' => 70,         // else If roll >=: DM
        'chance_lost' => 69,         // else If roll >=: BH
        'chance_delay' => 63,         // else If roll >=: Delay
        'chance_accel' => 60,         // else If roll >=: Accel
        'chance_res' => 25,         // else If roll >=: Resources
        'chance_fleet' => 1,          // else If roll >=: Fleet
                                    // else Trader
        'dm_factor' => 3,      // DM factor
        // The old expedition was pretty dull: If top1 has < 5000000 points, then 9000, otherwise 12000. Crumbs.
        'score_cap1' => 10000, 'score_cap2' => 100000, 'score_cap3' => 1000000, 'score_cap4' => 5000000, 'score_cap5' => 25000000, 'score_cap6' => 50000000, 'score_cap7' => 75000000, 'score_cap8' => 100000000,  // >=100kk
        'limit_cap1' => 9000,  'limit_cap2' => 9000,   'limit_cap3' => 9000,    'limit_cap4' => 9000,    'limit_cap5' => 12000,    'limit_cap6' => 12000,    'limit_cap7' => 12000,    'limit_cap8' => 12000,      'limit_max' => 12000
        // Redesign settings (7.0+):
        //'score_cap1' => 10000, 'score_cap2' => 100000,  'score_cap3' => 1000000, 'score_cap4' => 5000000, 'score_cap5' => 25000000, 'score_cap6' => 50000000, 'score_cap7' => 75000000, 'score_cap8' => 100000000,  // >=100kk
        //'limit_cap1' => 84000, 'limit_cap2' => 1050000, 'limit_cap3' => 2520000, 'limit_cap4' => 3780000, 'limit_cap5' => 5040000,  'limit_cap6' => 6300000,  'limit_cap7' => 7560000,  'limit_cap8' => 8820000,    'limit_max' => 10500000 
    );
    AddDBRow ($exptab, "exptab");

    // Add default colonization parameters.
    $coltab = array ( 
        't1_a' => 50, 't1_b' => 120, 't1_c' => 72, 
        't2_a' => 50, 't2_b' => 150, 't2_c' => 120,
        't3_a' => 50, 't3_b' => 120, 't3_c' => 120,
        't4_a' => 50, 't4_b' => 120, 't4_c' => 96,
        't5_a' => 50, 't5_b' => 150, 't5_c' => 96 );
    AddDBRow ($coltab, "coltab");

    // Set up autoincrement counters.
    dbquery ( "ALTER TABLE ".$db_prefix."planets AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$db_prefix."messages AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$db_prefix."notes AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$db_prefix."buddy AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$db_prefix."ally AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$db_prefix."allyapps AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$db_prefix."debug AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$db_prefix."errors AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$db_prefix."reports AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$db_prefix."browse AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$db_prefix."fleet AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$db_prefix."union AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$db_prefix."queue AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$db_prefix."buildqueue AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$db_prefix."battledata AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$db_prefix."fleetlogs AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$db_prefix."iplogs AUTO_INCREMENT = 1;" );
    dbquery ( "INSERT INTO ".$db_prefix."botstrat VALUES ( 1, 'backup', '')" );

    // Put the universe into the Master Base.
    $mdb_enable = (key_exists ('mdb_enable', $_POST) && $_POST["mdb_enable"]==="on"?1:0);
    if ($mdb_enable)
    {
        $mdb_connect = @mysqli_connect($_POST["mdb_host"], $_POST["mdb_user"], $_POST["mdb_pass"]);
        $mdb_select = @mysqli_select_db($mdb_connect, $_POST["mdb_name"]);
        $query = "SELECT id FROM unis";
        $result = mysqli_query ( $mdb_connect, $query);
        if (!$result) {
            $query = 'CREATE TABLE unis ( id INT AUTO_INCREMENT PRIMARY KEY, num INT, dbhost TEXT, dbuser TEXT, dbpass TEXT, dbname TEXT, uniurl TEXT ) CHARACTER SET utf8 COLLATE utf8_general_ci';
            mysqli_query ( $mdb_connect, $query );
        }
        $query = "INSERT INTO unis VALUES ( NULL, ".$_POST["uni_num"].", '".$_POST["db_host"]."', '".$_POST["db_user"]."', '".$_POST["db_pass"]."', '".$_POST["db_name"]."', '".uniurl()."' );";
        mysqli_query ( $mdb_connect, $query );
    }

    // Save the configuration file.
    $file = fopen ("config.php", "wb");
    if ($file == FALSE) $InstallError = loca('INSTALL_ERROR1');
    else
    {
        fwrite ($file, "<?php\r\n");
        fwrite ($file, "// DO NOT MODIFY!\r\n");
        fwrite ($file, "$"."StartPage=\"". $_POST["startpage"] ."\";\r\n");
        fwrite ($file, "$"."db_host=\"". $_POST["db_host"] ."\";\r\n");
        fwrite ($file, "$"."db_user=\"". $_POST["db_user"] ."\";\r\n");
        fwrite ($file, "$"."db_pass=\"". $_POST["db_pass"] ."\";\r\n");
        fwrite ($file, "$"."db_name=\"". $_POST["db_name"] ."\";\r\n");
        fwrite ($file, "$"."db_prefix=\"". $_POST["db_prefix"] ."\";\r\n");
        fwrite ($file, "$"."db_secret=\"". $_POST["db_secret"] ."\";\r\n");
        fwrite ($file, "$"."mdb_enable=". $mdb_enable .";\r\n");
        fwrite ($file, "$"."mdb_host=\"". $_POST["mdb_host"] ."\";\r\n");
        fwrite ($file, "$"."mdb_user=\"". $_POST["mdb_user"] ."\";\r\n");
        fwrite ($file, "$"."mdb_pass=\"". $_POST["mdb_pass"] ."\";\r\n");
        fwrite ($file, "$"."mdb_name=\"". $_POST["mdb_name"] ."\";\r\n");
        fwrite ($file, "?>");
        fclose ($file);
        $InstallError = "<font color=lime>".loca('INSTALL_DONE')."</font>";
    }
}

$info = " <img src='img/r5.png' />";

?>

<html>
<head>
<meta http-equiv='content-type' content='text/html; charset=utf-8' />
<TITLE><?php echo loca('INSTALL_TITLE');?></TITLE>
<link rel='stylesheet' type='text/css' href='css/default.css' />
<link rel='stylesheet' type='text/css' href='css/formate.css' />
</head>

<body>

<center>
<form action='install.php' method='POST'>
<input type=hidden name='install' value='1'>
<input type=hidden name='uni_lang' value='<?php echo $loca_lang;?>'>

<img src='img/install.png'><br>

<font color=red><?php echo $InstallError;?></font>

<table class='install_form'>

<table class='install_form'>
<tr>

<td style="vertical-align: top;">
<table>
<tr><td>&nbsp;</td></tr>
<tr><td><?php echo loca('INSTALL_STARTPAGE');?></td><td><input type=text value='<?php echo rtrim(hostname(), '/');?>' class='text' name='startpage'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'><?php echo loca('INSTALL_DB');?></td></tr>
<tr><td><?php echo loca('INSTALL_DB_HOST');?></td><td><input type=text value='localhost' class='text' name='db_host'></td></tr>
<tr><td><?php echo loca('INSTALL_DB_USER');?></td><td><input type=text class='text' name='db_user'></td></tr>
<tr><td><?php echo loca('INSTALL_DB_PASS');?></td><td><input type=password class='text'  name='db_pass'></td></tr>
<tr><td><?php echo loca('INSTALL_DB_NAME');?></td><td><input type=text class='text' name='db_name'></td></tr>
<tr><td><?php echo loca('INSTALL_DB_PREFIX');?><a title='<?php echo loca('INSTALL_TIP1');?>'><?php echo $info;?></a></td><td><input type=text value='uni1_' class='text' name='db_prefix'></td></tr>
<tr><td><?php echo loca('INSTALL_DB_SECRET');?><a title='<?php echo loca('INSTALL_TIP2');?>'><?php echo $info;?></a></td><td><input type=text type=password class='text' name='db_secret'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'><?php echo loca('INSTALL_MDB');?><a title='<?php echo loca('INSTALL_MDB_TIP');?>'><?php echo $info;?></a></td></tr>
<tr><td><?php echo loca('INSTALL_MDB_ENABLE');?></td><td><input type=checkbox class='text' name='mdb_enable' checked></td></tr>
<tr><td><?php echo loca('INSTALL_MDB_HOST');?></td><td><input type=text value='localhost' class='text' name='mdb_host'></td></tr>
<tr><td><?php echo loca('INSTALL_MDB_USER');?></td><td><input type=text class='text' name='mdb_user'></td></tr>
<tr><td><?php echo loca('INSTALL_MDB_PASS');?></td><td><input type=password class='text'  name='mdb_pass'></td></tr>
<tr><td><?php echo loca('INSTALL_MDB_NAME');?></td><td><input type=text class='text' name='mdb_name'></td></tr>
</table>
</td>

<td style="vertical-align: top;">
<table>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'><?php echo loca('INSTALL_UNI');?></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_NUM');?><a title='<?php echo loca('INSTALL_TIP3');?>'><?php echo $info;?></a></td><td><input type=text value='1' class='text' name='uni_num'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_SPEED');?><a title='<?php echo loca('INSTALL_TIP4');?>'><?php echo $info;?></a></td><td><input type=text value='1' class='text' name='uni_speed'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_FLEETSPEED');?><a title='<?php echo loca('INSTALL_TIP5');?>'><?php echo $info;?></a></td><td><input type=text value='1' class='text' name='uni_fspeed'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_G');?></td><td><input type=text value='9' class='text' name='uni_galaxies'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_S');?></td><td><input type=text value='499' class='text' name='uni_systems'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_USERS');?><a title='<?php echo loca('INSTALL_TIP6');?>'><?php echo $info;?></a></td><td><input type=text value='12500' class='text' name='uni_maxusers'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_START_DM');?></td><td><input type=text value='0' class='text' name='start_dm'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_ACS');?><a title='<?php echo loca('INSTALL_TIP7');?>'><?php echo $info;?></a></td><td><input type=text value='4' class='text' name='uni_acs'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_FID');?><a title='<?php echo loca('INSTALL_TIP8');?>'><?php echo $info;?></a></td><td><input type=text value='30' class='text' name='uni_fid'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_DID');?><a title='<?php echo loca('INSTALL_TIP9');?>'><?php echo $info;?></a></td><td><input type=text value='0' class='text' name='uni_did'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_RAPID');?><a title='<?php echo loca('INSTALL_TIP10');?>'><?php echo $info;?></a></td><td><input type=checkbox class='text' name='uni_rapid' CHECKED></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_MOONS');?></td><td><input type=checkbox class='text' name='uni_moons' CHECKED></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_BATTLE');?><a title='<?php echo loca('INSTALL_TIP11');?>'><?php echo $info;?></a></td><td><input type=text value='../cgi-bin/battle' class='text' name='uni_battle_engine'></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_PHP_BATTLE');?></td><td><input type=checkbox class='text' name='php_battle' CHECKED></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_FORCE_LANG');?></td><td><input type=checkbox class='text' name='force_lang' ></td></tr>
<tr><td><?php echo loca('INSTALL_MAX_WERF');?></td><td><input type=text value='999' class='text' name='max_werf'></td></tr>
<tr><td><?php echo loca('INSTALL_FEED_AGE');?></td><td><input type=text value='60' class='text' name='feedage'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'><?php echo loca('INSTALL_EXTERNAL_LINKS');?><a title='<?php echo loca('INSTALL_EXTERNAL_LINKS_TIP');?>'><?php echo $info;?></a></td></tr>
<tr><td><?php echo loca('MENU_BOARD');?></td><td><input type=text class='text' name='ext_board'></td></tr>
<tr><td><?php echo loca('MENU_DISCORD');?></td><td><input type=text class='text' name='ext_discord'></td></tr>
<tr><td><?php echo loca('MENU_TUTORIAL');?></td><td><input type=text class='text' name='ext_tutorial'></td></tr>
<tr><td><?php echo loca('MENU_RULES');?></td><td><input type=text class='text' name='ext_rules'></td></tr>
<tr><td><?php echo loca('MENU_IMPRESSUM');?></td><td><input type=text class='text' name='ext_impressum'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'><?php echo loca('INSTALL_ADMIN');?> (Legor)</td></tr>
<tr><td><?php echo loca('INSTALL_ADMIN_EMAIL');?></td><td><input type=text class='text' name='admin_email'></td></tr>
<tr><td><?php echo loca('INSTALL_ADMIN_PASS');?></td><td><input type=password class='text' name='admin_pass'></td></tr>
<tr><td>&nbsp;</td></tr>
</table>
</td>

</tr>

<tr><td colspan=3><center><input type=submit value='<?php echo loca('INSTALL_INSTALL');?>' class='button'></center></td></tr>

</table>

</form>

</center>
</body>
</html>