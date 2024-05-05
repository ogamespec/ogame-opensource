<?php

// Installation script.
// Creates all the necessary tables in the database, as well as the config.php configuration file, to access the database.
// Doesn't work if config.php file is created.

// Add error output for this early stage of the installation
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "db.php";
require_once "utils.php";
require_once "loca.php";
require_once "id.php";
require_once "user.php";
require_once "planet.php";

if ( !key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = $DefaultLanguage;
else $loca_lang = $_COOKIE['ogamelang'];
if ( !key_exists ( $loca_lang, $Languages ) ) $loca_lang = $DefaultLanguage;

loca_add ( "install", $loca_lang );
loca_add ( "menu", $loca_lang );
loca_add ( "mods", $loca_lang );

$InstallError = "<font color=gold>".loca('INSTALL_TIP')."</font>";

function uniurl () {
    $host = $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/game/install.php" );
    return substr ( $host, 0, $pos );
}

ob_start ();

// Check the settings of the universe.
function CheckParameters ()
{
    global $InstallError;

    return TRUE;
}

// Check if the configuration file has already been created - redirect to the main page.
if (file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=index.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

function gen_trivial_password ($len = 8)
{
    $r = '';
    for($i=0; $i<$len; $i++)
        $r .= chr(rand(0, 25) + ord('a'));
    return $r;
}

// Database tables
include "install_tabs.php";

// -------------------------------------------------------------------------------------------------------------------------

// Save the settings.
if ( key_exists("install", $_POST) && CheckParameters() )
{
    $now = time();

    //print_r ($_POST);

    // Delete all tables and create new empty tables.
    dbconnect ($_POST["db_host"], $_POST["db_user"], $_POST["db_pass"], $_POST["db_name"]);
    dbquery("SET NAMES 'utf8';");
    dbquery("SET CHARACTER SET 'utf8';");
    dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

    foreach ( $tabs as $tabname => $tab )
    {
        $opt = " (";
        $first = true;
        foreach ( $tab as $row => $type )
        {
            if ( !$first ) $opt .= ", ";
            if ( $first ) $first = false;
            $opt .= $row . " " . $type;
        }
        $opt .= ")";

        $query = 'DROP TABLE IF EXISTS '.$_POST["db_prefix"].$tabname;
        dbquery ($query, TRUE);
        $query = 'CREATE TABLE '.$_POST["db_prefix"].$tabname.$opt." CHARACTER SET utf8 COLLATE utf8_general_ci";
        dbquery ($query, TRUE);
    }

    // Create the universe.
    $query = "INSERT INTO ".$_POST["db_prefix"]."uni SET ";
    $query .= "num = '".$_POST["uni_num"]."', ";
    $query .= "speed = '".$_POST["uni_speed"]."', ";
    $query .= "fspeed = '".$_POST["uni_fspeed"]."', ";
    $query .= "galaxies = '".$_POST["uni_galaxies"]."', ";
    $query .= "systems = '".$_POST["uni_systems"]."', ";
    $query .= "maxusers = '".$_POST["uni_maxusers"]."', ";
    $query .= "start_dm = '".$_POST["start_dm"]."', ";
    $query .= "acs = '".$_POST["uni_acs"]."', ";
    $query .= "fid = '".$_POST["uni_fid"]."', ";
    $query .= "did = '".$_POST["uni_did"]."', ";
    $query .= "rapid = '".(key_exists("uni_rapid", $_POST) && $_POST["uni_rapid"]==="on"?1:0)."', ";
    $query .= "moons = '".(key_exists("uni_moons", $_POST) && $_POST["uni_moons"]==="on"?1:0)."', ";
    $query .= "defrepair = '70', ";
    $query .= "defrepair_delta = '10', ";
    $query .= "usercount = '1', ";
    $query .= "freeze = '0', ";
    $query .= "news1 = '', ";
    $query .= "news2 = '', ";
    $query .= "news_until = '0', ";
    $query .= "startdate = '".$now."', ";
    $query .= "battle_engine = '".$_POST["uni_battle_engine"]."', ";
    $query .= "lang = '".$_POST["uni_lang"]."', ";
    $query .= "ext_board = '".$_POST["ext_board"]."', ";
    $query .= "ext_discord = '".$_POST["ext_discord"]."', ";
    $query .= "ext_tutorial = '".$_POST["ext_tutorial"]."', ";
    $query .= "ext_rules = '".$_POST["ext_rules"]."', ";
    $query .= "ext_impressum = '".$_POST["ext_impressum"]."', ";
    $query .= "php_battle = '".(key_exists("php_battle", $_POST) && $_POST["php_battle"]==="on"?1:0)."', ";
    $query .= "force_lang = '".(key_exists("force_lang", $_POST) && $_POST["force_lang"]==="on"?1:0)."', ";
    $query .= "hacks = '0'; ";
    //echo "<br>$query<br>";
    dbquery ($query);

    // Create technical account "space"
    $md = md5 ( gen_trivial_password() . $_POST['db_secret'] );
    $opt = " (";
    $user = array( USER_SPACE, $now, 0, 0, 0, "",  "", "space", "space", 0, 0, $md, "", "", "",
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, "0.0.0.0", 1, "", 1, 2, 0, 0,
                        hostname() . "evolution/", 1, 1, 1, 3, $_POST["uni_lang"], 0,
                        0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        USER_FLAG_DEFAULT );
    foreach ($user as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$user[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."users VALUES".$opt;
    dbquery( $query);

    // Create administrator account (Legor).
    $md = md5 ($_POST['admin_pass'] . $_POST['db_secret']);
    $opt = " (";
    $user = array( USER_LEGOR, $now, 0, 0, 0, "",  "", "legor", "Legor", 0, 0, $md, "", $_POST['admin_email'], $_POST['admin_email'],
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, "0.0.0.0", 1, "", 1, 2, 0, 0,
                        hostname() . "evolution/", 1, 1, 1, 3, $_POST["uni_lang"], 1,
                        0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        USER_FLAG_DEFAULT );
    foreach ($user as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$user[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."users VALUES".$opt;
    dbquery( $query);

    // Create the planet Arakis [1:1:2] and the moon Mond (yes, there's only one letter `r` in the planet name, it's a reference to Dune)
    $opt = " (";
    $planet = array( 1, "Arakis", PTYP_PLANET, 1, 1, 2, USER_LEGOR, 12800, 40, 0, 163, $now,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           500, 500, 0, 1, 1, 1, 1, 1, 1, $now, $now, 0, 0 );
    foreach ($planet as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$planet[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."planets VALUES".$opt;
    dbquery( $query);
    $opt = " (";
    $planet = array( 2, "Mond", PTYP_MOON, 1, 1, 2, USER_LEGOR, 8944, 10, 0, 1, $now,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           0, 0, 0, 1, 1, 1, 1, 1, 1, $now, $now, 0, 0 );
    foreach ($planet as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$planet[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."planets VALUES".$opt;
    dbquery( $query);

    // Add default Expedition Parameters.
    $opt = " (";
    $exptab = array ( 
        70,         // Success chance, else Nothing
        25, 50, 75,         // Depletion counter: <=not depleted, <=small, <=medium, else strong
        25, 50, 75,         // Change of failure on depletion: 0% explicit if not depleted, 25% for small, 50% for medium, 75% for strong
        95,         // If roll >=: Aliens
        85,         // else If roll >=: Pirates
        70,         // else If roll >=: DM
        69,         // else If roll >=: BH
        63,         // else If roll >=: Delay
        60,         // else If roll >=: Accel
        25,         // else If roll >=: Resources
        1,          // else If roll >=: Fleet
                    // else Trader
        3,      // DM factor
        // The old expedition was pretty dull: If top1 has < 5000000 points, then 9000, otherwise 12000. Crumbs.
        10000, 100000, 1000000, 5000000, 25000000, 50000000, 75000000, 100000000,  // >=100kk
        9000,  9000,   9000,    9000,    12000,    12000,    12000,    12000,      12000
        // Redesign settings (7.0+):
        //10000, 100000,  1000000, 5000000, 25000000, 50000000, 75000000, 100000000,  // >=100kk
        //84000, 1050000, 2520000, 3780000, 5040000,  6300000,  7560000,  8820000,    10500000 
    );

    foreach ($exptab as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$exptab[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."exptab VALUES".$opt;
    dbquery( $query);

    // Add default colonization parameters.
    $opt = " (";
    $coltab = array ( 50, 120, 72, 50, 150, 120, 50, 120, 120, 50, 120, 96, 50, 150, 96 );
    foreach ($coltab as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$coltab[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."coltab VALUES".$opt;
    dbquery( $query);

    // Set up autoincrement counters.
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."planets AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."messages AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."notes AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."buddy AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."ally AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."allyapps AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."debug AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."errors AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."browse AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."fleet AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."union AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."queue AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."buildqueue AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."battledata AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."fleetlogs AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."iplogs AUTO_INCREMENT = 1;" );
    dbquery ( "INSERT INTO ".$_POST["db_prefix"]."botstrat VALUES ( 1, 'backup', '')" );

    // Modifications
    $query = "INSERT INTO ".$_POST["db_prefix"]."mods SET var = 'mod_carnage', value = '".(key_exists ('mod_carnage', $_POST) && $_POST["mod_carnage"]==="on"?1:0)."'; ";
    dbquery ($query);
    $query = "INSERT INTO ".$_POST["db_prefix"]."mods SET var = 'mod_carnage_fleet_size', value = '".$_POST["mod_carnage_fleet_size"]."'; ";
    dbquery ($query);
    $query = "INSERT INTO ".$_POST["db_prefix"]."mods SET var = 'mod_endgame', value = '".(key_exists ('mod_endgame', $_POST) && $_POST["mod_endgame"]==="on"?1:0)."'; ";
    dbquery ($query);
    $query = "INSERT INTO ".$_POST["db_prefix"]."mods SET var = 'mod_endgame_days', value = '".$_POST["mod_endgame_days"]."'; ";
    dbquery ($query);

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
</head>

<body>

<style>
body { background:#000000 url(img/background.jpg) right top; background-repeat: no-repeat; overflow: hidden; background-color: #040e1e; color: #fff; }
td.c { background-color: #334445; }
.button { border: 1px solid; color: white; background-color: #334445; }
.text { border: 1px solid; color: white; background-color: #334445; }
.install_form { background: url(img/page_bg.png); }
</style>

<center>
<form action='install.php' method='POST'>
<input type=hidden name='install' value='1'>
<input type=hidden name='uni_lang' value='<?php echo $loca_lang;?>'>

<img src='img/install.png'><br>

<font color=red><?php echo $InstallError;?></font>

<table class='install_form'>

<table class='install_form'>
<tr>

<td valign=top>
<table>
<tr><td>&nbsp;</td></tr>
<tr><td><?php echo loca('INSTALL_STARTPAGE');?></td><td><input type=text value='http://ogame.ru' class='text' name='startpage'></td></tr>
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

<td valign=top>
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
<tr><td><?php echo loca('INSTALL_UNI_PHP_BATTLE');?></td><td><input type=checkbox class='text' name='php_battle' ></td></tr>
<tr><td><?php echo loca('INSTALL_UNI_FORCE_LANG');?></td><td><input type=checkbox class='text' name='force_lang' ></td></tr>
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

<td valign=top>
<table>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'><?php echo loca('MODS');?></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td><?php echo loca('MODS_CARNAGE');?></td><td><input type=checkbox class='text' name='mod_carnage'></td></tr>
<tr><td><?php echo loca('MODS_CARNAGE_FLEET_SIZE');?></td><td><input type=text value='2' class='text' name='mod_carnage_fleet_size'></td></tr>
<tr><td><?php echo loca('MODS_ENDGAME');?></td><td><input type=checkbox class='text' name='mod_endgame'></td></tr>
<tr><td><?php echo loca('MODS_ENDGAME_DAYS');?></td><td><input type=text value='30' class='text' name='mod_endgame_days'></td></tr>
</table>
</td>

</tr>

<tr><td colspan=3><center><input type=submit value='<?php echo loca('INSTALL_INSTALL');?>' class='button'></center></td></tr>

</table>

</form>

</center>
</body>
</html>