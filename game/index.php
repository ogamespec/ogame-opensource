<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

// The main module through which other pages are accessed.
ob_start ();

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

header('Pragma:no-cache');

$GlobalUser = array ();

require_once "config.php";
require_once "db.php";
require_once "utils.php";

InitDB();

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
require_once "coupon.php";
require_once "mods.php";

$GlobalUni = LoadUniverse ();

// *****************************************************************************

// Game Pages.

if ( key_exists ( 'session', $_GET ) ) {

    //
    // Private session check
    //

    //
    // Public session check
    //

    SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );

    if (CheckSession ( $_GET['session'] ) == FALSE) die ();
}
else
{
    RedirectHome ();
    die ();
}

if ( $GlobalUni['freeze'] && $GlobalUser['admin'] == 0 ) {
    echo "<html><head><meta http-equiv='refresh' content='0;url=maintenance.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "technames", $GlobalUser['lang'] );

//
// Checking GET / POST parameters for possible SQL injection
//

if ( $_GET['page'] !== "admin" )
{
    if ( stripos ($_SERVER['REQUEST_URI'], "select") != false ) Hacking ("HACK_SQL_INJECTION");
    if ( stripos ($_SERVER['REQUEST_URI'], "insert") != false ) Hacking ("HACK_SQL_INJECTION");
    if ( stripos ($_SERVER['REQUEST_URI'], "update") != false ) Hacking ("HACK_SQL_INJECTION");

    foreach ( $_GET as $i=>$value )
    {
        if (!is_string($value)) {
            continue;
        }
        
        if ( stripos ($value, "select") != false ) Hacking ("HACK_SQL_INJECTION");
        if ( stripos ($value, "insert") != false ) Hacking ("HACK_SQL_INJECTION");
        if ( stripos ($value, "update") != false ) Hacking ("HACK_SQL_INJECTION");
    }

    foreach ( $_POST as $i=>$value )
    {
        if (!is_string($value)) {
            continue;
        }

        if ( stripos ($value, "select") != false ) Hacking ("HACK_SQL_INJECTION");
        if ( stripos ($value, "insert") != false ) Hacking ("HACK_SQL_INJECTION");
        if ( stripos ($value, "update") != false ) Hacking ("HACK_SQL_INJECTION");
    }
}

//
// Vanilla OGame 0.84 pages
//

if ( $_GET['page'] === "overview" ) { include "pages/overview.php"; exit(); }
else if ( $_GET['page'] === "admin" )
{
    if ( $GlobalUser['admin'] > 0 )
    {
        include "pages_admin/admin.php";
        exit ();
    }
    else
    {
        Hacking ("HACK_ADMIN_PAGE");
        Error ( 'Попытка проникновения в админ панель.' );
    }
}
else if ( $_GET['page'] === "imperium" ) { include "pages/imperium.php"; exit (); }
else if ( $_GET['page'] === "buildings" ) { include "pages/buildings.php"; exit (); }
else if ( $_GET['page'] === "renameplanet" ) { include "pages/renameplanet.php"; exit (); }
else if ( $_GET['page'] === "b_building" ) { include "pages/b_building.php"; exit (); }
else if ( $_GET['page'] === "resources" ) { include "pages/resources.php"; exit(); }
else if ( $_GET['page'] === "infos" ) { include "pages/infos.php"; exit (); }
else if ( $_GET['page'] === "flotten1" ) { include "pages/flotten1.php"; exit (); }
else if ( $_GET['page'] === "flotten2" ) { include "pages/flotten2.php"; exit (); }
else if ( $_GET['page'] === "flotten3" ) { include "pages/flotten3.php"; exit (); }
else if ( $_GET['page'] === "flottenversand" ) { include "pages/flottenversand.php"; exit (); }
else if ( $_GET['page'] === "fleet_templates" ) { include "pages/fleet_templates.php"; exit (); }
else if ( $_GET['page'] === "techtree" ) { include "pages/techtree.php"; exit(); }
else if ( $_GET['page'] === "techtreedetails" ) { include "pages/techtreedetails.php"; exit(); }
else if ( $_GET['page'] === "galaxy" ) { include "pages/galaxy.php"; exit (); }
else if ( $_GET['page'] === "phalanx" ) { include "pages/phalanx.php"; exit (); }
else if ( $_GET['page'] === "allianzen" ) { include "pages/allianzen.php"; exit (); }
else if ( $_GET['page'] === "ainfo" ) { include "pages/ainfo.php"; exit (); }
else if ( $_GET['page'] === "bewerben" ) { include "pages/bewerben.php"; exit (); }
else if ( $_GET['page'] === "bewerbungen" ) { include "pages/bewerbungen.php"; exit (); }
else if ( $_GET['page'] === "statistics" ) { include "pages/statistics.php"; exit (); }
else if ( $_GET['page'] === "suche" ) { include "pages/suche.php"; exit (); }
else if ( $_GET['page'] === "messages" ) { include "pages/messages.php"; exit (); }
else if ( $_GET['page'] === "writemessages" ) { include "pages/writemessages.php"; exit (); }
else if ( $_GET['page'] === "notizen" ) { include "pages/notizen.php"; exit (); }
else if ( $_GET['page'] === "buddy" ) { include "pages/buddy.php"; exit (); }
else if ( $_GET['page'] === "options" ) { include "pages/options.php"; exit (); }
else if ( $_GET['page'] === "logout" ) { include "pages/logout.php"; exit (); }
else if ( $_GET['page'] === "changelog" ) { include "pages/changelog.php"; exit (); }
else if ( $_GET['page'] === "pranger" ) { include "pages/pranger.php"; exit (); }
else if ( $_GET['page'] === "bericht" ) { include "pages/bericht.php"; exit (); }
else if ( $_GET['page'] === "allianzdepot" ) { include "pages/allianzdepot.php"; exit (); }
else if ( $_GET['page'] === "sprungtor" ) { include "pages/sprungtor.php"; exit (); }
else if ( $_GET['page'] === "micropayment" ) { include "pages/micropayment.php"; exit (); }
else if ( $_GET['page'] === "payment" ) { include "pages/payment.php"; exit (); }
else if ( $_GET['page'] === "trader" ) { include "pages/trader.php"; exit (); }

RedirectHome ();

?>