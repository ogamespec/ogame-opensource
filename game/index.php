<?php

/** @var array $GlobalUser */

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
else {
    require_once "config.php";
}

header('Pragma:no-cache');

require_once "core/core.php";

InitDB();

$GlobalUni = LoadUniverse ();

// *****************************************************************************

// Debug strings are used in the language of the universe so the admin doesn't screw around.
// Also, all debug strings are included globally so that you don't have to bother with adding them inplace in the source code.
loca_add ( "debug", $GlobalUni['lang'] );

$result = CheckParams ($_REQUEST);
if (!$result['success']) {
    Error ("Error validating request parameters. Too smart users will be sent to the admin for a proctological examination.");
}

// Game Pages.

if ( key_exists ( 'session', $_GET ) ) {

    //
    // TBD: Private session check
    //

    //
    // Public session check
    //

    $session = $_GET['session'];
    if (AuthUser ( $session ) == false) die ();
}
else
{
    $GlobalUser = LoadUser (USER_SPACE);

    // For external reference  try to take the language from the cookies. If there is no language in cookies, try to take the language of the Universe.
    // Otherwise, use the default language.

    if ( key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = $_COOKIE['ogamelang'];
    else $loca_lang = $GlobalUni['lang'];
    if ( !key_exists ( $loca_lang, $Languages ) ) $loca_lang = $DefaultLanguage;
    $GlobalUser['lang'] = $loca_lang;
}

if ( $GlobalUni['freeze'] && $GlobalUser['admin'] == 0 ) {
    echo "<html><head><meta http-equiv='refresh' content='0;url=maintenance.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "technames", $GlobalUser['lang'] );

ModsInit();

//
// Pages router
//

// Load the routing table and allow mods to add their custom pages
$router = LoadJsonFirst ("router.json");
ModsExecRef ('route', $router);

// Previously, each page contained its own Message/Error variable; now all pages use these variables. Message/Error variables are used to call the PageFooter (the green/red text boxes).
$PageMessage = "";
$PageError = "";

$pk = false;
if (key_exists('page', $_GET)) {
    if (key_exists($_GET['page'], $router)) {
        $pk = $_GET['page'];
    }
}
if ($pk != false) {

    // Add locales required for the page
    foreach ($router[$pk]['loca'] as $i => $loca) {
        loca_add ( $loca, $GlobalUser['lang']);
    }

    $now = time();

    $external = false;
    if (key_exists('external', $router[$pk]) && !key_exists ( 'session', $_GET )) {
        $external = $router[$pk]['external'];
    }

    if (!$external && key_exists ( 'session', $_GET )) {

        if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
        $GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

        $update_queue = true;
        if ( $GlobalUser['admin'] != 0 && key_exists('admin_update_queue', $router[$pk]) ) {
            // Do not update Overview for admins
            $update_queue = $router[$pk]['admin_update_queue'];
        }
        if ($update_queue) {
            UpdateQueue ( $now );
        }
        $aktplanet = GetUpdatePlanet ( $GlobalUser['aktplanet'], $now );
        if ($aktplanet == null) {
            Error ("Can't get aktplanet");
        }
        $update_activity = true;
        if (key_exists('update_activity', $router[$pk])) {
            $update_activity = $router[$pk]['update_activity'];
        }
        if ($update_activity) {
            UpdatePlanetActivity ( $aktplanet['planet_id'] );
        }
        UpdateLastClick ( $GlobalUser['player_id'] );

        // Controls the display of the top and left menus (some pages do not have a top menu, for example, Galaxy, and some have no menu at all, for example, Notes)
        $header = true;
        if (key_exists('header', $router[$pk])) {
            $header = $router[$pk]['header'];
        }

        $menu = true;
        if (key_exists('menu', $router[$pk])) {
            $menu = $router[$pk]['menu'];
        }
    }
    else {

        $header = $menu = false;
    }

    // By default, the page does not reload itself. The exception is the fleet dispatch page (flottenversand).
    $redirect_page = "";
    if (key_exists('redirect_page', $router[$pk])) {
        $redirect_page = $router[$pk]['redirect_page'];
    }

    $redirect_sec = 0;
    if (key_exists('redirect_sec', $router[$pk])) {
        $redirect_sec = $router[$pk]['redirect_sec'];
    }

    $bare = false;
    if (key_exists('bare', $router[$pk])) {
        $bare = $router[$pk]['bare'];
    }

    $mvc = false;
    if (key_exists('mvc', $router[$pk])) {
        $mvc = $router[$pk]['mvc'];
    }

    if ($mvc) {

        // New-style

        $classFile = $router[$pk]['path'];
        if (file_exists($classFile)) {

            require_once $classFile;
            $className = ucfirst($pk);
            $inst = new $className;
            $show = $inst->controller ();

            if ($show) {
                PageHeader ($pk, !$header, $menu, $redirect_page, $redirect_sec);
                BeginContent ();
                $inst->view ();
                EndContent ();
                PageFooter ($PageMessage, $PageError, !$menu /*popup*/, $header ? 81 : 0, !$header);
            }
        }
    }
    else {

        // Old-style

        if (!$bare) {
            PageHeader ($pk, !$header, $menu, $redirect_page, $redirect_sec);
            BeginContent ();
        }

        // Pages can use the following global variables: $now, $aktplanet, $session, $PageMessage, $PageError, $GlobalUser, $GlobalUni
        include $router[$pk]['path'];

        if (!$bare) {
            EndContent ();
            PageFooter ($PageMessage, $PageError, !$menu /*popup*/, $header ? 81 : 0, !$header);
        }
    }

    ob_end_flush ();
}
else {
    RedirectHome ();
}

?>