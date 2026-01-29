<?php

const GRAVI_MOON_DESTR = 1;
const GRAVI_FLEET_DESTR = 2;

// Moon attack.
// Returns the result encoded in 2 bits: bit0 - the moon is destroyed, bit1 - Deathstar exploded with the whole fleet
function GravitonAttack (array $fleet_obj, array $fleet, int $when) : int
{
    $origin = LoadPlanetById ( $fleet_obj['start_planet'] );
    $target = LoadPlanetById ( $fleet_obj['target_planet'] );

    if ( $fleet[GID_F_DEATHSTAR] == 0 ) return 0;
    if ( ! ($target['type'] == PTYP_MOON || $target['type'] == PTYP_DEST_MOON) ) Error ( "Only moons can be destroyed!" );

    $diam = $target['diameter'];
    $rips = $fleet[GID_F_DEATHSTAR];
    $moonchance = (100 - sqrt($diam)) * sqrt($rips);
    if ($moonchance >= 100) $moonchance = 99.9;
    $ripchance = sqrt ($diam) / 2;
    $moondes =  mt_rand(1, 999) < $moonchance * 10;
    $ripdes = mt_rand(1, 999) < $ripchance * 10;

    $origin_user = LoadUser ($origin['owner_id']);
    $target_user = LoadUser ($target['owner_id']);
    loca_add ( "graviton", $origin_user['lang'] );
    loca_add ( "graviton", $target_user['lang'] );
    loca_add ( "fleetmsg", $origin_user['lang'] );
    loca_add ( "fleetmsg", $target_user['lang'] );

    if ( !$ripdes && !$moondes )
    {
            $atext = va ( loca_lang("GRAVITON_ATK_00", $origin_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( loca_lang("GRAVITON_DEF_00", $target_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", 
                                floor ($moonchance), floor ($ripchance)
                             );
            $result  = 0;
    }

    else if ( !$ripdes && $moondes )
    {
            $atext = va ( loca_lang("GRAVITON_ATK_01", $origin_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( loca_lang("GRAVITON_DEF_01", $target_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                             );

            DestroyMoon ( $target['planet_id'], $when, $fleet_obj['fleet_id'] );
            $result  = GRAVI_MOON_DESTR;
    }

    else if ( $ripdes && !$moondes )
    {
            $atext = va ( loca_lang("GRAVITON_ATK_10", $origin_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( loca_lang("GRAVITON_DEF_10", $target_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                             );
            $result  = GRAVI_FLEET_DESTR;
    }

    else if ( $ripdes && $moondes )
    {
            $atext = va ( loca_lang("GRAVITON_ATK_11", $origin_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                            );
            $dtext = va ( loca_lang("GRAVITON_DEF_11", $target_user['lang']), 
                                $origin['name'], "[".$origin['g'].":".$origin['s'].":".$origin['p']."]", "[".$target['g'].":".$target['s'].":".$target['p']."]",
                                floor ($moonchance), floor ($ripchance)
                             );

            DestroyMoon ( $target['planet_id'], $when, $fleet_obj['fleet_id'] );
            $result  = GRAVI_MOON_DESTR | GRAVI_FLEET_DESTR;
    }

    // Recalculate stats if a fleet was blown up by a failed graviton attack
    if (($result & GRAVI_FLEET_DESTR) != 0) {

        $price = FleetPrice ( $fleet_obj );
        AdjustStats ( $fleet_obj['owner_id'], $price['points'], $price['fpoints'], 0, '-' );
        RecalcRanks ();
    }

    // Send out messages.
    SendMessage ( $origin['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $origin_user['lang']), 
        loca_lang("GRAVITON_ATK_SUBJ", $origin_user['lang']),
        $atext, MTYP_MISC, $when);
    SendMessage ( $target['owner_id'], 
        loca_lang("FLEET_MESSAGE_FROM", $target_user['lang']),
        loca_lang("GRAVITON_DEF_SUBJ", $target_user['lang']),
        $dtext, MTYP_MISC, $when);

    return $result;
}

?>