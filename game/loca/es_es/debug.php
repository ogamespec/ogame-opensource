<?php

// Various debug strings. Sometimes it's nice to see them in the native language.

// debug.php

$LOCA["es"]["DEBUG_ERROR"] = "An error occurred";
$LOCA["es"]["DEBUG_ERROR_INFO1"] = "Emergency program termination.";
$LOCA["es"]["DEBUG_ERROR_INFO2"] = "Please contact Support or visit the forum in the \"Errors\" section.";
$LOCA["es"]["DEBUG_SECURITY_BREACH"] = "Security breach: ";

// page.php

$LOCA["es"]["DEBUG_PAGE_INFO"] = "Page generated in %f seconds<br>Number of SQL queries: %d<br>";

$LOCA["es"]["DEBUG_MANI_SESSION"] = "Manipulating the public session";
$LOCA["es"]["DEBUG_PAYMENT_MANI_COUPON"] = "Manipulating a coupon code";

// Hacking attempt debug messages.
// Shown as debug reports with HACKING ATTEMPT note

$LOCA["es"]["HACK_ADMIN_PAGE"] = "Attempting to open the admin panel by a regular user.";
$LOCA["es"]["HACK_SELECT_PLANET"] = "Selecting foreign planet or special Galaxy object.";
$LOCA["es"]["HACK_SQL_INJECTION"] = "Possible SQL-injection breach (specific keywords found in URI request or GET/POST parameters).";

// queue.php

$LOCA["es"]["DEBUG_QUEUE_UNKNOWN"] = "queue: Unknown task type for global queue: ";
$LOCA["es"]["DEBUG_QUEUE_CANCEL_RESEARCH_FOREIGN"] = "Unable to cancel research -#1-, player #2, started on an foreign planet #3";
$LOCA["es"]["DEBUG_QUEUE_RESEARCH_COMPLETE"] = "Research #1 level #2 for user #3 has been completed.";
$LOCA["es"]["DEBUG_QUEUE_OLD_SCORE_SAVED"] = "Old points saved, timestamp #1";
$LOCA["es"]["DEBUG_QUEUE_CLEAN_PLANETS"] = "Cleanup of destroyed planets (#1)";

// userlogs

$LOCA["es"]["DEBUG_LOG_BUILD"] = "Building #1 #2 on planet #3";
$LOCA["es"]["DEBUG_LOG_DEMOLISH"] = "Demolition #1 #2 on planet #3";
$LOCA["es"]["DEBUG_LOG_BUILD_CANCEL"] = "Cancel construction #1 #2, slot (#3) on planet #4";
$LOCA["es"]["DEBUG_LOG_DEFENSE"] = "Start building defense #1 (#2) on planet #3";
$LOCA["es"]["DEBUG_LOG_SHIPYARD"] = "Start building fleet #1 (#2) on planet #3";
$LOCA["es"]["DEBUG_LOG_RESEARCH"] = "Start research #1 on planet #2";
$LOCA["es"]["DEBUG_LOG_RESEARCH_CANCEL"] = "Cancel research #1 on planet #2";
$LOCA["es"]["DEBUG_LOG_FLEET_SEND1"] = "Fleet dispatch #1: ";
$LOCA["es"]["DEBUG_LOG_FLEET_SEND2"] = "Flight time: #1, holding: #2, deuterium costs: #3, ACS: #4";
$LOCA["es"]["DEBUG_LOG_FLEET_SEND_AJAX1"] = "Fleet dispatch #1 (AJAX): ";
$LOCA["es"]["DEBUG_LOG_FLEET_SEND_AJAX2"] = "Flight time: #1, deuterium costs: #2";
$LOCA["es"]["DEBUG_LOG_FLEET_RECALL"] = "Fleet Recall #1: ";

?>