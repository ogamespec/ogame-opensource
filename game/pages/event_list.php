<?php

// Contains common code for the Overview/Phalanx event list, eventually targeting full unification.

function OverFleet (array $fleet, int $summary, string $mission, bool $ignore_level=false) : string
{
    global $GlobalUser;
    $level = $GlobalUser[GID_R_ESPIONAGE];
    $prem = PremiumStatus ($GlobalUser);
    if ($prem['technocrat']) $level += 2;
    if ( $fleet['owner_id'] == $GlobalUser['player_id'] || $ignore_level ) $level = 99;
    global $fleetmap;
    $sum = 0;
    $res = "";
    if ( $level >= 2 )
    {
        $res .= "<a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;";
        if ( $summary ) {
            foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
            $res .= loca("EVENT_FLEET_COUNT") . ": $sum &lt;br&gt;";
        }
        if ( $level >= 4 )
        {
            foreach ($fleetmap as $i=>$gid) {
                $amount = $fleet[$gid];
                if ( $amount > 0 ) {
                    $res .= loca ("NAME_$gid") . " ";
                    if ( $level >= 8 ) $res .= nicenum($amount);
                    $res .= "&lt;br&gt;";
                }
            }
        }
        $res .= "&lt;/b&gt;&lt;/font&gt;\");' onmouseout='return nd();' class='".$mission."'>";
    }
    return $res;
}

function TitleFleet (array $fleet, int $summary, bool $ignore_level=false) : string
{
    global $GlobalUser;
    $level = $GlobalUser[GID_R_ESPIONAGE];
    $prem = PremiumStatus ($GlobalUser);
    if ($prem['technocrat']) $level += 2;
    if ( $fleet['owner_id'] == $GlobalUser['player_id'] || $ignore_level ) $level = 99;
    global $fleetmap;
    $sum = 0;
    $res = "";
    if ( $level >= 2 )
    {
        if ( $summary ) {
            foreach ($fleetmap as $i=>$gid) $sum += $fleet[$gid];
            $res .= loca("EVENT_FLEET_COUNT") . ": $sum ";
        }
        if ( $level >= 4 )
        {
            foreach ($fleetmap as $i=>$gid) {
                $amount = $fleet[$gid];
                if ( $amount > 0 ) {
                    $res .= loca ("NAME_$gid") . " " ;
                    if ( $level >= 8 ) $res .= nicenum($amount);
                }
            }
        }
    }
    return $res;
}

function PlayerDetails (array $user) : string
{
    return $user['oname'] . " <a href='#' onclick='showMessageMenu(".$user['player_id'].")'><img src='".UserSkin()."img/m.gif' title='".loca("EVENT_WRITE")."' alt='".loca("EVENT_WRITE")."'></a>";
}

function PlanetFrom (array $planet, string $mission) : string
{
    $res = "";
    if ( GetPlanetType ($planet) == 1 ) $res .= loca("EVENT_FROM_PLANET");
    if ( $planet['type'] == PTYP_COLONY_PHANTOM || $planet['type'] == PTYP_FARSPACE ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function PlanetTo (array $planet, string $mission) : string
{
    $res = "";
    if ( GetPlanetType ($planet) == 1 ) $res .= loca("EVENT_TO_PLANET");
    if ( $planet['type'] == PTYP_COLONY_PHANTOM || $planet['type'] == PTYP_FARSPACE ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function PlanetOn (array $planet, string $mission) : string
{
    $res = "";
    if ( $planet['type'] == PTYP_COLONY_PHANTOM || $planet['type'] == PTYP_FARSPACE ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function Cargo (array $fleet, string $mission, string $text) : string
{
	global $transportableResources;

	$sum = 0;
	foreach ($transportableResources as $i=>$rc) {
		$sum += $fleet[$rc];
	}

	$res = "";
    if ( $sum != 0 ) {
        $res .= "<a href='#' onmouseover='return overlib(\"&lt;font color=white&gt;&lt;b&gt;".loca("EVENT_CARGO").": ";
		foreach ($transportableResources as $i=>$rc) {
			$res .= "&lt;br /&gt;".loca("NAME_".$rc).": ".nicenum($fleet[$rc]);
		}
		$res .= "&lt;/b&gt;&lt;/font&gt;\");' ";
		$res .="onmouseout='return nd();'' class='$mission'>$text</a><a href='#' title='".loca("EVENT_CARGO").":";
		foreach ($transportableResources as $i=>$rc) {
			$res .= " ".loca("NAME_".$rc).": ".nicenum($fleet[$rc]);
		}
		$res .= "'></a>";
    }
    else $res .= "<span class='class'>$text</span>";
    return $res;
}

function GetMission ( array $fleet_obj ) : int
{
    if ( $fleet_obj['mission'] < FTYP_RETURN ) return $fleet_obj['mission'];
    else if ( $fleet_obj['mission'] < FTYP_ORBITING ) return $fleet_obj['mission'] - FTYP_RETURN;
    else return $fleet_obj['mission'] - FTYP_ORBITING;
}

?>