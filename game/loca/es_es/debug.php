<?php

// Various debug strings. Sometimes it's nice to see them in the native language.

// debug.php

$LOCA["es"]["DEBUG_ERROR"] = "Se ha producido un error";
$LOCA["es"]["DEBUG_ERROR_INFO1"] = "Terminación de emergencia del programa.";
$LOCA["es"]["DEBUG_ERROR_INFO2"] = "Póngase en contacto con el soporte o visite el foro en la sección \"Errores\".";
$LOCA["es"]["DEBUG_SECURITY_BREACH"] = "Violación de seguridad: ";

// page.php

$LOCA["es"]["DEBUG_PAGE_INFO"] = "Página generada en %f segundos<br>Número de consultas SQL: %d<br>";

$LOCA["es"]["DEBUG_MANI_SESSION"] = "Manipulación de la sesión pública";
$LOCA["es"]["DEBUG_PAYMENT_MANI_COUPON"] = "Manipulación de un código de cupón";

// Hacking attempt debug messages.
// Shown as debug reports with HACKING ATTEMPT note

$LOCA["es"]["HACK_ADMIN_PAGE"] = "Intento de abrir el panel de administración por parte de un usuario normal.";
$LOCA["es"]["HACK_SELECT_PLANET"] = "Selección de un planeta ajeno o un objeto especial de la galaxia.";
$LOCA["es"]["HACK_SQL_INJECTION"] = "Posible violación por inyección SQL (se han encontrado palabras clave específicas en la petición URI o en los parámetros GET/POST).";

// queue.php

$LOCA["es"]["DEBUG_QUEUE_UNKNOWN"] = "cola: Tipo de tarea desconocido para la cola global: ";
$LOCA["es"]["DEBUG_QUEUE_CANCEL_RESEARCH_FOREIGN"] = "No se puede cancelar la investigación -#1- del jugador #2, iniciada en un planeta ajeno #3";
$LOCA["es"]["DEBUG_QUEUE_RESEARCH_COMPLETE"] = "La investigación #1 de nivel #2 para el usuario #3 se ha completado.";
$LOCA["es"]["DEBUG_QUEUE_OLD_SCORE_SAVED"] = "Puntos antiguos guardados, marca de tiempo #1";
$LOCA["es"]["DEBUG_QUEUE_CLEAN_PLANETS"] = "Limpieza de planetas destruidos (#1)";

// userlogs

$LOCA["es"]["DEBUG_LOG_BUILD"] = "Construcción de #1 #2 en el planeta #3";
$LOCA["es"]["DEBUG_LOG_DEMOLISH"] = "Demolición de #1 #2 en el planeta #3";
$LOCA["es"]["DEBUG_LOG_BUILD_CANCEL"] = "Cancelación de la construcción de #1 #2, ranura (#3) en el planeta #4";
$LOCA["es"]["DEBUG_LOG_DEFENSE"] = "Inicio de la construcción de la defensa #1 (#2) en el planeta #3";
$LOCA["es"]["DEBUG_LOG_SHIPYARD"] = "Inicio de la construcción de la flota #1 (#2) en el planeta #3";
$LOCA["es"]["DEBUG_LOG_RESEARCH"] = "Inicio de la investigación #1 en el planeta #2";
$LOCA["es"]["DEBUG_LOG_RESEARCH_CANCEL"] = "Cancelación de la investigación #1 en el planeta #2";
$LOCA["es"]["DEBUG_LOG_FLEET_SEND1"] = "Envío de flota #1: ";
$LOCA["es"]["DEBUG_LOG_FLEET_SEND2"] = "Tiempo de vuelo: #1, espera: #2, coste de deuterio: #3, ACS: #4";
$LOCA["es"]["DEBUG_LOG_FLEET_SEND_AJAX1"] = "Envío de flota #1 (AJAX): ";
$LOCA["es"]["DEBUG_LOG_FLEET_SEND_AJAX2"] = "Tiempo de vuelo: #1, coste de deuterio: #2";
$LOCA["es"]["DEBUG_LOG_FLEET_RECALL"] = "Retirada de flota #1: ";

?>
