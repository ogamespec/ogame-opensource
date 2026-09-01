<?php

// TODO: No HTML pages of the English version of the Buildings page were saved, so everything is translated via DeepL.

// Locales for the b_building page

$LOCA["es"]["BUILD_COMPLETE"] = "Hecho";
$LOCA["es"]["BUILD_NEXT"] = "Siguiente";
$LOCA["es"]["BUILD_CANCEL"] = "Cancelar";
$LOCA["es"]["BUILD_DEMOLISH"] = "Destruir";
$LOCA["es"]["BUILD_DEQUEUE"] = "eliminar";
$LOCA["es"]["BUILD_LEVEL"] = "nivel #1";
$LOCA["es"]["BUILD_PRICE"] = "Costo";
$LOCA["es"]["BUILD_DURATION"] = "Tiempo";
$LOCA["es"]["BUILD_ENQUEUE"] = "En la cola de construcción";
$LOCA["es"]["BUILD_QUEUE_FULL"] = "¡No hay espacio! ";
$LOCA["es"]["BUILD_BUSY"] = "En proceso";
$LOCA["es"]["BUILD_BUILD"] = " construir ";
$LOCA["es"]["BUILD_BUILD_LEVEL"] = "Ampliar <br> nivel #1";

// Locales for the building page (Shipyard/Defense/Research)

$LOCA["es"]["BUILD_BUILDINGS_HEAD"] = "Edificios#Gebaeude"; // Shipyard?~~???? What even does this go to? buildings.php L85 (echo loca("BUILD_BUILDINGS_HEAD") . "\n";) doesn't appear to do anything?~~
$LOCA["es"]["BUILD_DESC"] = "Descripción";
$LOCA["es"]["BUILD_AMOUNT"] = "Cant.";
$LOCA["es"]["BUILD_SHIPYARD_UNITS"] = "en existencia #1";
$LOCA["es"]["BUILD_SHIPYARD_CANT"] = "imposible";
$LOCA["es"]["BUILD_SHIPYARD_SUBMIT"] = "Construir";
$LOCA["es"]["BUILD_RESEARCH_NEXT"] = "siguiente";
$LOCA["es"]["BUILD_RESEARCH_LEVEL"] = "Investigación<br> nivel #1";
$LOCA["es"]["BUILD_RESEARCH"] = " investigar ";
$LOCA["es"]["BUILD_SHIPYARD_PROCESSING"] = "En producción ahora";
$LOCA["es"]["BUILD_SHIPYARD_COMPLETE"] = "Tareas completadas";
$LOCA["es"]["BUILD_SHIPYARD_CURRENT"] = " (producido)";
$LOCA["es"]["BUILD_SHIPYARD_QUEUE"] = "Tareas pendientes";
$LOCA["es"]["BUILD_SHIPYARD_TIME"] = "Toda la producción tardará";

// Error texts for CanBuild, CanResearch method (queue.php)

$LOCA["es"]["BUILD_ERROR_UNI_FREEZE"] = "¡Universo en pausa!";
$LOCA["es"]["BUILD_ERROR_INVALID_ID"] = "¡ID no válido!";
$LOCA["es"]["BUILD_ERROR_VACATION_MODE"] = "No es posible construir en modo vacaciones.";
$LOCA["es"]["BUILD_ERROR_INVALID_PLANET"] = "¡Planeta incorrecto!";
$LOCA["es"]["BUILD_ERROR_INVALID_PTYPE"] = "Tipo de planeta incorrecto.";
$LOCA["es"]["BUILD_ERROR_INVALID_PTYPE"] = "Tipo de planeta incorrecto.";
$LOCA["es"]["BUILD_ERROR_NO_SPACE"] = "No hay espacio para construir en el planeta.";
$LOCA["es"]["BUILD_ERROR_RESEARCH_ACTIVE"] = "¡Investigación en curso!";
$LOCA["es"]["BUILD_ERROR_SHIPYARD_ACTIVE"] = "El astillero sigue ocupado.";
$LOCA["es"]["BUILD_ERROR_NO_RES"] = "¡No tienes suficientes recursos!";
$LOCA["es"]["BUILD_ERROR_REQUIREMENTS"] = "¡No se cumplen los requisitos necesarios!";
$LOCA["es"]["BUILD_ERROR_CANT_DEMOLISH"] = "La base lunar y el terraformador no se pueden demoler.";
$LOCA["es"]["BUILD_ERROR_NO_SUCH_BUILDING"] = "No tienes edificios de este tipo.";
$LOCA["es"]["BUILD_ERROR_RESEARCH_ALREADY"] = "¡La investigación ya está en marcha!";
$LOCA["es"]["BUILD_ERROR_RESEARCH_LAB_BUILDING"] = "¡El laboratorio de investigación se está mejorando!";
$LOCA["es"]["BUILD_ERROR_RESEARCH_LAB_BUILDING"] = "¡El laboratorio de investigación se está mejorando!";
$LOCA["es"]["BUILD_ERROR_RESEARCH_VACATION"] = "No es posible investigar en modo vacaciones (RO).";
$LOCA["es"]["BUILD_ERROR_MAX_LEVEL"] = "Nivel máximo alcanzado.";

$LOCA["es"]["BUILD_ERROR_SHIPYARD_BUSY"] = "No se pueden construir naves ni defensas mientras el astillero o la fábrica de nanitas se esté mejorando";
$LOCA["es"]["BUILD_ERROR_VACATION"] = "Modo vacaciones mínimo hasta #1";
$LOCA["es"]["BUILD_ERROR_SHIPYARD_REQUIRED"] = "¡Debes construir un hangar en este planeta para continuar!";
$LOCA["es"]["BUILD_ERROR_DOME"] = "La cúpula de escudo solo se puede construir 1 vez.";
$LOCA["es"]["BUILD_ERROR_RESLAB_BUSY"] = "No es posible investigar mientras el laboratorio de investigación se está mejorando.";
$LOCA["es"]["BUILD_ERROR_RESLAB_REQUIRED"] = "Para hacer esto, necesitas construir un laboratorio de investigación!";

// Message about canceling the queue for the Commander.

$LOCA["es"]["BUILD_MSG_DEMOLISH"] = "Orden de demolición.";
$LOCA["es"]["BUILD_MSG_BUILD"] = "Orden de construcción";
$LOCA["es"]["BUILD_MSG_BODY"] = "#1 para tu construcción #2 del nivel #3 en #4 no se pudo ejecutar.";
$LOCA["es"]["BUILD_MSG_FROM"] = "Mensaje del sistema";
$LOCA["es"]["BUILD_MSG_SUBJ"] = "Producción cancelada";

?>
