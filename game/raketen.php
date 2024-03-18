<?php

// Ракетная атака. Раньше находилась в battle.php, но для удобства отделена в свой модуль.
// TODO: Нужно уделить больше внимания этой фиче игры, т.к. есть сомнения в корректности работы алгоритма. Для проверки можно использовать раздел админки для симуляции ракетной атаки.

// Алгоритмическая часть ракетной атаки (без работы с БД).
function RocketAttackMain ( $amount, $primary, $moon_attack, &$target, &$moon_planet, $origin_user_attack, $target_user_armor )
{
    global $UnitParam;

    // Отбить атаку МПР перехватчиками
    $ipm = $amount;
    $abm = $moon_attack ? $moon_planet['d502'] : $target['d502'];
    $ipm = max (0, $ipm - $abm);
    $ipm_destroyed = $amount - $ipm;
    if ($moon_attack) $moon_planet['d502'] -= $ipm_destroyed;
    else $target['d502'] -= $ipm_destroyed;

    $maxdamage = $ipm * $UnitParam[503][2] * (1 + $origin_user_attack / 10);

    // Произвести атаку первичной цели
    if ( $primary > 0 && $ipm > 0 )
    {
        $armor = $UnitParam[$primary][0] * (1 + 0.1 * $target_user_armor) / 10;
        $count = $target["d$primary"];
        if ($count != 0) {
            $destroyed = min ( floor ( $maxdamage / $armor ), $count );
            $target["d$primary"] -= $destroyed;
            $maxdamage -= $destroyed * $armor;
            $maxdamage -= $destroyed;
        }
    }

    // Расчитать потери обороны, если еще остались МПР -- всё то же самое, но в ID не учитывать ID первичной цели. Чужие ракеты также можно бомбить.
    if ($maxdamage > 0)
    {
        $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );
        foreach ($defmap as $i=>$id)
        {
            if ($id == $primary) continue;
            $armor = $UnitParam[$id][0] * (1 + 0.1 * $target_user_armor) / 10;
            $count = $target["d$id"];
            if ($count != 0) {
                $destroyed = min ( floor ( $maxdamage / $armor ), $count );
                $target["d$id"] -= $destroyed;
                $maxdamage -= $destroyed * $armor;
                $maxdamage -= $destroyed;
            }
            if ($maxdamage <= 0) break;
        }
    }

    return $ipm_destroyed;
}

// Ракетная атака.
function RocketAttack ( $fleet_id, $planet_id, $when )
{
    $fleet = LoadFleet ($fleet_id);
    $amount = $fleet['ipm_amount'];
    $primary = $fleet['ipm_target'];
    $origin = GetPlanet ($fleet['start_planet']);
    $target = GetPlanet ($planet_id);
    $moon_attack = $target['type'] == 0;
    if ($moon_attack) {
        // Если ракетная атака производится на Луну, то перехватчики с планеты участвуют в защите
        $moon_planet = LoadPlanet ($target['g'], $target['s'], $target['p'], 1);
    }
    $origin_user = LoadUser ($origin['owner_id']);
    $target_user = LoadUser ($target['owner_id']);

    $ipm_destroyed = RocketAttackMain (
        $amount, 
        $primary, 
        $moon_attack, 
        $target, 
        $moon_planet, 
        $origin_user['r109'], 
        $target_user['r111'] );

    // Записать назад потери обороны.
    SetPlanetDefense ( $planet_id, $target );
    if ($moon_attack) {
        SetPlanetDefense ( $moon_planet['planet_id'], $moon_planet );
    }

    // Изменить статистику игроков
    RecalcRanks ();

    // Обновить активность на планете.
    UpdatePlanetActivity ( $planet_id, $when );

    // Сформировать сообщение для защитника
    loca_add ( "raketen", $target_user['lang'] );
    loca_add ( "fleetmsg", $target_user['lang'] );
    $text = va(loca_lang("RAK_DEF_TEXT1", $target_user['lang']), $amount) . " ". $origin['name']." <a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a>  ";
    $text .= loca_lang ("RAK_DEF_TEXT2", $target_user['lang']) . " " . $target['name']." <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a> !<br>";
    if ($ipm_destroyed) $text .= va(loca_lang("RAK_DEF_TEXT3", $target_user['lang']), $ipm_destroyed) . "<br>:<br>";
    $text .= GetDestroyedDefenseText ($target_user['lang'], $target, $moon_planet, $moon_attack);
    SendMessage ( $target_user['player_id'], 
        loca_lang ("FLEET_MESSAGE_FROM", $target_user['lang']), 
        loca_lang ("RAK_MSG_SUBJ", $target_user['lang']), 
        $text, MTYP_BATTLE_REPORT_LINK, $when);

    $message_for_attacker = true;

    // Сформировать сообщение для атакующего: https://github.com/ogamespec/ogame-opensource/issues/61
    // Оригинальная версия 0.84 не создавала сообщения для атакующего.
    if ($message_for_attacker) {

        loca_add ( "raketen", $origin_user['lang'] );
        loca_add ( "fleetmsg", $origin_user['lang'] );
        $text = va(loca_lang("RAK_ATT_TEXT1", $origin_user['lang']), $amount) . " " . $origin['name']." <a href=# onclick=showGalaxy(".$origin['g'].",".$origin['s'].",".$origin['p']."); >[".$origin['g'].":".$origin['s'].":".$origin['p']."]</a> ";
        $text .= loca_lang("RAK_ATT_TEXT2", $origin_user['lang']) . " " . $target['name']." <a href=# onclick=showGalaxy(".$target['g'].",".$target['s'].",".$target['p']."); >[".$target['g'].":".$target['s'].":".$target['p']."]</a> !<br>";    
        $text .= GetDestroyedDefenseText ($origin_user['lang'], $target, $moon_planet, $moon_attack);
        SendMessage ( $origin_user['player_id'], 
            loca_lang ("FLEET_MESSAGE_FROM", $origin_user['lang']), 
            loca_lang ("RAK_MSG_SUBJ", $origin_user['lang']), 
            $text, MTYP_BATTLE_REPORT_LINK, $when);
    }
}

// Получить текст для уничтоженной обороны
function GetDestroyedDefenseText ($lang, &$target, &$moon_planet, $moon_attack)
{
    loca_add ( "raketen", $lang );
    loca_add ( "technames", $lang );

    $defmap = array ( 503, 502, 408, 407, 406, 405, 404, 403, 402, 401 );       // оборона выводится задом-наперёд по неизвестной причине.
    $deftext = "<table width=400><tr><td class=c colspan=4>".loca_lang("RAK_TITLE", $lang)."</td></tr>";
    $n = 0;
    foreach ( $defmap as $i=>$gid )
    {
        if ( ($n % 2) == 0 ) $deftext .= "</tr>";
        if ( $target["d$gid"] ) {

            $count = $target["d$gid"];
            // Учесть оборону луны перехватчиками с планеты
            if ($moon_attack && $gid == 502 ) {
                $count = $moon_planet["d502"];
            }

            $deftext .= "<td>".loca_lang("NAME_$gid", $lang)."</td><td>".nicenum($count)."</td>";
            $n++;
        }
    }
    $deftext .= "</table><br>\n";

    return $deftext;
}

?>