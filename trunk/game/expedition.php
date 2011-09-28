<?php

// Экспедиции.

function ExpeditionArrive ($queue, $fleet_obj, $fleet)
{
}

function ExpeditionHold ($queue, $fleet_obj, $fleet)
{
}

function ExpeditionReturn ($queue, $fleet_obj, $fleet)
{
}

// Посчитать количество активных экспедиций у выбранного игрока.
function GetExpeditionsCount ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."fleet WHERE (mission = 15 OR mission = 115 OR mission = 215) AND owner_id = $player_id;";
    $result = dbquery ($query);
    return dbrows ($result);
}

?>