<?php

// Various fleet messages (e.g. Transport, Recycle, etc.). There is a complete mess. The same mess was also in vanilla version 0.84
// TODO: Take messages from the current official game.

// From/Subj
$LOCA["es"]["FLEET_MESSAGE_FROM"] = "Fleet Command";
$LOCA["es"]["FLEET_MESSAGE_RETURN"] = "Return of the fleet";
$LOCA["es"]["FLEET_MESSAGE_HOLD"] = "Fleet retention";
$LOCA["es"]["FLEET_MESSAGE_INTEL"] = "Intelligence";
$LOCA["es"]["FLEET_MESSAGE_FLEET"] = "Fleet ";
$LOCA["es"]["FLEET_MESSAGE_OBSERVE"] = "Observation";
$LOCA["es"]["FLEET_MESSAGE_ARRIVE"] = "Reaching the planet";
$LOCA["es"]["FLEET_MESSAGE_TRADE"] = "Foreign fleet is delivering supplies";
$LOCA["es"]["FLEET_MESSAGE_SPY"] = "Espionage";
$LOCA["es"]["FLEET_MESSAGE_BATTLE"] = "Battle report";

$LOCA["es"]["FLEET_TRANSPORT_OWN"] = "Your fleet reaches the planet (\n#1\n) and delivers its cargo:.\n<br/>\n" .
				"#2 metal, #3 crystal and #4 deuterium.\n<br/>\n";
$LOCA["es"]["FLEET_TRANSPORT_OTHER"] = "Player #1's fleet is delivering to your planet #2\n#3\n<br/>\n" .
					"#4 metal, #5 crystal and #6 deuterium\n<br/>\n" .
                    "Before you had #7 metal, #8 crystal and #9 deuterium.\n<br/>\n" .
                    "Now you have #10 metal, #11 crystal and #12 deuterium.\n<br/>\n";

$LOCA["es"]["FLEET_RECYCLE"] = "The #1 recyclers have a total capacity of #2. " .
	"The debris field contains #3 metal and #4 crystal. " .
	"Recycled #5 metal and #6 crystal.";

$LOCA["es"]["FLEET_RETURN"] = "One of your fleets ( #1 ), sent from #2, reaches #3 #4 . ";
$LOCA["es"]["FLEET_RETURN_RES"] = "The fleet delivers #1 metal, #2 crystal and #3 deuterium<br>";

$LOCA["es"]["FLEET_DEPLOY"] = "\nOne of your fleets (#1) reached #2\n#3\n. ";
$LOCA["es"]["FLEET_DEPLOY_RES"] = "The fleet delivers #1 metal, #2 crystal and #3 deuterium\n<br/>\n";

$LOCA["es"]["FLEET_COLONIZE"] = "\nFleet reaches set coordinates\n#1\n";
$LOCA["es"]["FLEET_COLONIZE_MAX"] = ", and establishes that this planet is suitable for colonization. Shortly after the planet's exploration begins, there is a report of unrest on the main planet, as the empire becomes too large and the people move back.\n";
$LOCA["es"]["FLEET_COLONIZE_SUCCESS"] = ", finds a new planet there and immediately begins to explore it.\n";
$LOCA["es"]["FLEET_COLONIZE_FAIL"] = ", but finds no planet suitable for colonization. The settlers return in a depressed state.\n";
$LOCA["es"]["FLEET_COLONIZE_FROM"] = "Settlers";
$LOCA["es"]["FLEET_COLONIZE_SUBJ"] = "Settlers' report";

$LOCA["es"]["FLEET_SPY_OTHER"] = "\nThe foreign fleet from the planet #1\n#2\nhas been detected in the vicinity of a planet #3\n#4\n. A chance to defend against espionage: #5 %\n";

?>