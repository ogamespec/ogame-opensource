<?php

// TODO: No HTML pages of the English version of the Buildings page were saved, so everything is translated via DeepL.

// Locales for the b_building page

$LOCA["es"]["BUILD_COMPLETE"] = "Done";
$LOCA["es"]["BUILD_NEXT"] = "Next";
$LOCA["es"]["BUILD_CANCEL"] = "Cancelar";
$LOCA["es"]["BUILD_DEMOLISH"] = "Destruir";
$LOCA["es"]["BUILD_DEQUEUE"] = "delete";
$LOCA["es"]["BUILD_LEVEL"] = "nivel #1";
$LOCA["es"]["BUILD_PRICE"] = "Cost";
$LOCA["es"]["BUILD_DURATION"] = "Tiempo";
$LOCA["es"]["BUILD_ENQUEUE"] = "In the queue for construction";
$LOCA["es"]["BUILD_QUEUE_FULL"] = "There's no space! ";
$LOCA["es"]["BUILD_BUSY"] = "In the process";
$LOCA["es"]["BUILD_BUILD"] = " build ";
$LOCA["es"]["BUILD_BUILD_LEVEL"] = "Ampliar <br> nivel #1";

// Locales for the building page (Shipyard/Defense/Research)

$LOCA["es"]["BUILD_BUILDINGS_HEAD"] = "Buildings#Gebaeude"; // Shipyard?~~???? What even does this go to? buildings.php L85 (echo loca("BUILD_BUILDINGS_HEAD") . "\n";) doesn't appear to do anything?~~
$LOCA["es"]["BUILD_DESC"] = "Descripción";
$LOCA["es"]["BUILD_AMOUNT"] = "Qty.";
$LOCA["es"]["BUILD_SHIPYARD_UNITS"] = "in stock #1";
$LOCA["es"]["BUILD_SHIPYARD_CANT"] = "impossibly";
$LOCA["es"]["BUILD_SHIPYARD_SUBMIT"] = "Build";
$LOCA["es"]["BUILD_RESEARCH_NEXT"] = "next";
$LOCA["es"]["BUILD_RESEARCH_LEVEL"] = "Research<br> level #1";
$LOCA["es"]["BUILD_RESEARCH"] = " research ";
$LOCA["es"]["BUILD_SHIPYARD_PROCESSING"] = "Now being produced";
$LOCA["es"]["BUILD_SHIPYARD_COMPLETE"] = "Tasks completed";
$LOCA["es"]["BUILD_SHIPYARD_CURRENT"] = " (produced)";
$LOCA["es"]["BUILD_SHIPYARD_QUEUE"] = "Expected tasks";
$LOCA["es"]["BUILD_SHIPYARD_TIME"] = "The entire production will take";

// Error texts for CanBuild, CanResearch method (queue.php)

$LOCA["es"]["BUILD_ERROR_UNI_FREEZE"] = "Universe on pause!";
$LOCA["es"]["BUILD_ERROR_INVALID_ID"] = "Invalid ID!";
$LOCA["es"]["BUILD_ERROR_VACATION_MODE"] = "Construction is not possible in vacation mode.";
$LOCA["es"]["BUILD_ERROR_INVALID_PLANET"] = "Wrong planet!";
$LOCA["es"]["BUILD_ERROR_INVALID_PTYPE"] = "Incorrect planet type.";
$LOCA["es"]["BUILD_ERROR_INVALID_PTYPE"] = "Incorrect planet type.";
$LOCA["es"]["BUILD_ERROR_NO_SPACE"] = "There is no space for construction on the planet.";
$LOCA["es"]["BUILD_ERROR_RESEARCH_ACTIVE"] = "Research in progress!";
$LOCA["es"]["BUILD_ERROR_SHIPYARD_ACTIVE"] = "The shipyard is still busy.";
$LOCA["es"]["BUILD_ERROR_NO_RES"] = "You don't have enough resources!";
$LOCA["es"]["BUILD_ERROR_REQUIREMENTS"] = "Necessary requirements are not met!";
$LOCA["es"]["BUILD_ERROR_CANT_DEMOLISH"] = "Lunar base and terraformer cannot be demolished.";
$LOCA["es"]["BUILD_ERROR_NO_SUCH_BUILDING"] = "You have no buildings of this type.";
$LOCA["es"]["BUILD_ERROR_RESEARCH_ALREADY"] = "Research is already underway!";
$LOCA["es"]["BUILD_ERROR_RESEARCH_LAB_BUILDING"] = "The research lab is being improved!";
$LOCA["es"]["BUILD_ERROR_RESEARCH_LAB_BUILDING"] = "The research lab is being improved!";
$LOCA["es"]["BUILD_ERROR_RESEARCH_VACATION"] = "Research is not possible in vacation mode (RO).";

$LOCA["es"]["BUILD_ERROR_SHIPYARD_BUSY"] = "Neither ships nor defenses can be built as the shipyard or nanite factory is upgraded";
$LOCA["es"]["BUILD_ERROR_VACATION"] = "Vacation mode minimum to #1";
$LOCA["es"]["BUILD_ERROR_SHIPYARD_REQUIRED"] = "¡Debes construir un hangar en este planeta para continuar!";
$LOCA["es"]["BUILD_ERROR_DOME"] = "A shield dome can only be built 1 time.";
$LOCA["es"]["BUILD_ERROR_RESLAB_BUSY"] = "Conducting research is not possible as the research lab is being upgraded.";
$LOCA["es"]["BUILD_ERROR_RESLAB_REQUIRED"] = "In order to do this, you need to build a research lab!";

// Message about canceling the queue for the Commander.

$LOCA["es"]["BUILD_MSG_DEMOLISH"] = "Demolition order.";
$LOCA["es"]["BUILD_MSG_BUILD"] = "Construction order";
$LOCA["es"]["BUILD_MSG_BODY"] = "#1 for your construction #2 of the #3rd level on #4 could not be executed.";
$LOCA["es"]["BUILD_MSG_FROM"] = "System message";
$LOCA["es"]["BUILD_MSG_SUBJ"] = "Production canceled";

?>