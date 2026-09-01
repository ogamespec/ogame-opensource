<?php

// Diverses lignes de débogage. Il est parfois agréable de les voir dans leur langue d'origine.

// debug.php

$LOCA["fr"]["DEBUG_ERROR"] = "Une erreur est survenue";
$LOCA["fr"]["DEBUG_ERROR_INFO1"] = "Arrêt d'urgence du programme.";
$LOCA["fr"]["DEBUG_ERROR_INFO2"] = "Veuillez contacter le support ou visiter le forum dans la section « Erreurs ».";
$LOCA["fr"]["DEBUG_SECURITY_BREACH"] = "Violation de sécurité : ";

// page.php

$LOCA["fr"]["DEBUG_PAGE_INFO"] = "Page générée en %f secondes<br>Nombre de requêtes SQL : %d<br>";

$LOCA["fr"]["DEBUG_MANI_SESSION"] = "Manipulation de la session publique";
$LOCA["fr"]["DEBUG_PAYMENT_MANI_COUPON"] = "Manipulation d'un code de coupon";

// Messages de débogage des tentatives de piratage.
// Affiché sous forme de rapports de débogage avec la mention HACKING ATTEMPT (tentative de piratage).

$LOCA["fr"]["HACK_ADMIN_PAGE"] = "Tentative d'ouverture du panneau d'administration par un utilisateur normal.";
$LOCA["fr"]["HACK_SELECT_PLANET"] = "Sélection d’une planète étrangère ou d’un objet spécial de la galaxie.";
$LOCA["fr"]["HACK_SQL_INJECTION"] = "Violation possible de l’injection SQL (mots-clés spécifiques trouvés dans la requête d’URI ou les paramètres GET/POST).";

// queue.php

$LOCA["fr"]["DEBUG_QUEUE_UNKNOWN"] = "file : type de tâche inconnu pour la file globale : ";
$LOCA["fr"]["DEBUG_QUEUE_CANCEL_RESEARCH_FOREIGN"] = "Impossible d'annuler la recherche -#1- du joueur #2, commencée sur une planète étrangère #3";
$LOCA["fr"]["DEBUG_QUEUE_RESEARCH_COMPLETE"] = "La recherche #1 niveau #2 de l'utilisateur #3 est terminée.";
$LOCA["fr"]["DEBUG_QUEUE_OLD_SCORE_SAVED"] = "Anciens points enregistrés, horodatage #1";
$LOCA["fr"]["DEBUG_QUEUE_CLEAN_PLANETS"] = "Nettoyage des planètes détruites (#1)";

// journaux utilisateur

$LOCA["fr"]["DEBUG_LOG_BUILD"] = "Construction de #1 #2 sur la planète #3";
$LOCA["fr"]["DEBUG_LOG_DEMOLISH"] = "Démolition de #1 #2 sur la planète #3";
$LOCA["fr"]["DEBUG_LOG_BUILD_CANCEL"] = "Annulation de la construction #1 #2, emplacement (#3) sur la planète #4";
$LOCA["fr"]["DEBUG_LOG_DEFENSE"] = "Début de la construction de la défense #1 (#2) sur la planète #3";
$LOCA["fr"]["DEBUG_LOG_SHIPYARD"] = "Début de la construction de la flotte #1 (#2) sur la planète #3";
$LOCA["fr"]["DEBUG_LOG_RESEARCH"] = "Début de la recherche #1 sur la planète #2";
$LOCA["fr"]["DEBUG_LOG_RESEARCH_CANCEL"] = "Annulation de la recherche #1 sur la planète #2";
$LOCA["fr"]["DEBUG_LOG_FLEET_SEND1"] = "Répartition de la flotte #1: ";
$LOCA["fr"]["DEBUG_LOG_FLEET_SEND2"] = "Temps de vol: #1, rétention: #2, dépenses de deutérium: #3, ACS: #4";
$LOCA["fr"]["DEBUG_LOG_FLEET_SEND_AJAX1"] = "Répartition de la flotte #1 (AJAX): ";
$LOCA["fr"]["DEBUG_LOG_FLEET_SEND_AJAX2"] = "Temps de vol: #1, dépenses de deutérium: #2";
$LOCA["fr"]["DEBUG_LOG_FLEET_RECALL"] = "Rappel de la flotte #1 : ";

?>
