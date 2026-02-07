<?php

/** @var array $GlobalUser */

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

$admin_router = LoadJsonFirst ("pages_admin/admin_router.json");

include "admin_home.php";
include "admin_panel.php";
include "admin_fleetlogs.php";
include "admin_browse.php";
include "admin_reports.php";
include "admin_bans.php";
include "admin_users.php";
include "admin_planets.php";
include "admin_queue.php";
include "admin_uni.php";
include "admin_errors.php";
include "admin_debug.php";
include "admin_sim.php";
include "admin_broadcast.php";
include "admin_expedition.php";
include "admin_logins.php";
include "admin_checksum.php";
include "admin_bots.php";
include "admin_battle.php";
include "admin_userlogs.php";
include "admin_botedit.php";
include "admin_coupons.php";
include "admin_raksim.php";
include "admin_db.php";
include "admin_colony_settings.php";
include "admin_loca.php";
include "admin_mods.php";

// ========================================================================================

echo "<table width=\"750\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n\n";

if ( $mode === "Home" ) Admin_Home ();
else if ( $mode === "Fleetlogs" ) Admin_Fleetlogs ();
else if ( $mode === "Browse" ) Admin_Browse ();
else if ( $mode === "Reports" ) Admin_Reports ();
else if ( $mode === "Bans" ) Admin_Bans ();
else if ( $mode === "Users" ) Admin_Users ();
else if ( $mode === "Planets" ) Admin_Planets ();
else if ( $mode === "Queue" ) Admin_Queue ();
else if ( $mode === "Uni" ) Admin_Uni ();
else if ( $mode === "Errors" ) Admin_Errors ();
else if ( $mode === "Debug" ) Admin_Debug ();
else if ( $mode === "BattleSim" ) Admin_BattleSim ();
else if ( $mode === "Broadcast" ) Admin_Broadcast ();
else if ( $mode === "Expedition" ) Admin_Expedition ();
else if ( $mode === "Logins" ) Admin_Logins ();
else if ( $mode === "Checksum" ) Admin_Checksum ();
else if ( $mode === "Bots" ) Admin_Bots ();
else if ( $mode === "BattleReport" ) Admin_BattleReport ();
else if ( $mode === "UserLogs" ) Admin_UserLogs ();
else if ( $mode === "BotEdit" ) Admin_BotEdit ();
else if ( $mode === "Coupons" ) Admin_Coupons ();
else if ( $mode === "RakSim" ) Admin_RakSim ();
else if ( $mode === "DB" ) Admin_DB ();
else if ( $mode === "ColonySettings" ) Admin_ColonySettings ();
else if ( $mode === "Loca" ) Admin_Loca ();
else if ( $mode === "Mods" ) Admin_Mods ();
else Admin_Home ();

echo "</table>\n";
echo "<br><br><br><br>\n";
?>