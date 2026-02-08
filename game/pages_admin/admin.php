<?php

/** @var array $GlobalUser */
/** @var string $PageMessage */
/** @var string $PageError */

// Admin Panel.
// The main panel is a typical admin panel with categories.

// Only special users have access to the admin area: operators (admin=1) and admins (admin=2).

// Admin categories (GET parameter `mode`). Some categories cannot be accessed by operators.
// - Flight controls (all)
// - Browse history (admin only)
// - Reports (all)
// - Bans (all)
// - Users (operators can only view and modify part of it (e.g. disable IP check), admin can modify)
// - Planets (operators can only view and modify parts (e.g. names of planets), admin can modify)
// - Queue Tasks (admin only)
// - Universe settings (admin only)
// - Errors (admin only)
// - Mods (admin only)

if ( $GlobalUser['admin'] == 0 ) RedirectHome ();    // regular users are not allowed

if ( key_exists ('mode', $_GET) ) $mode = $_GET['mode'];
else $mode = "Home";

require_once "admin_panel.php";

$admin_router = LoadJsonFirst ("pages_admin/admin_router.json");
ModsExecRef ('route_admin', $admin_router);

if (!isset($admin_router[$mode])) $mode = "Home";

$header = false;
$menu = true;

$classFile = $admin_router[$mode]['path'];
if (file_exists($classFile)) {

    require_once $classFile;
    $className = "Admin_" . $mode;
    $inst = new $className;

    $show = $inst->controller ();

    if ($show) {
        PageHeader ($mode, !$header, $menu, "", 0);
        BeginContent ();

        echo "<table width=\"750\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n\n";
        $panel = true;
        if (key_exists('panel', $admin_router[$mode])) {
            $panel = $admin_router[$mode]['panel'];
        }
        if ($panel) {
            AdminPanel();
        }
        
        $inst->view ();
     
        echo "</table>\n";
        echo "<br><br><br><br>\n";
           
        EndContent ();
        PageFooter ($PageMessage, $PageError, !$menu /*popup*/, $header ? 81 : 0, !$header);
    }
}

?>