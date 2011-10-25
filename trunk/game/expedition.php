<?php

// Экспедиции.

function ExpeditionArrive ($queue, $fleet_obj, $fleet, $origin, $target)
{

    // Запустить задание удержания на орбите.
    // Время удержания сделать временем полёта (чтобы потом его можно было использовать при возврате флота)
    DispatchFleet ($fleet, $origin, $target, 215, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end'], 0, $fleet_obj['flight_time']);
}

function ExpeditionHold ($queue, $fleet_obj, $fleet, $origin, $target)
{
    // Событие экспедиции.

    $text = "Экспедиция не принесла ничего особого, кроме какой-то странной зверушки с неизвестной болотной  планеты.";
    SendMessage ( $fleet_obj['owner_id'], "Командование флотом", "Результат экспедиции [".$target['g'].":".$target['s'].":".$target['p']."]", $text, 3);

    // Вернуть флот.
    // В качестве времени полёта используется время удержания.
    DispatchFleet ($fleet, $origin, $target, 115, $fleet_obj['deploy_time'], $fleet_obj['m'], $fleet_obj['k'], $fleet_obj['d'], 0, $queue['end']);
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