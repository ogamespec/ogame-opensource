<?php

// Common code for the Overview/Phalanx event list: fleet rendering, the
// shared table body and task helpers.

// Event list presentation styles.
const EVENT_LIST_OVERVIEW = 'overview';
const EVENT_LIST_PHALANX = 'phalanx';

function OverFleet (array $fleet, int $summary, string $mission, bool $ignore_level=false) : string
{
    global $GlobalUser;
    $level = $GlobalUser[GID_R_ESPIONAGE];
    $prem = PremiumStatus ($GlobalUser);
    if ($prem['technocrat']) $level += 2;
    
    $bonus = array();
    $bonus['level'] = $level;
    ModsExecIntRef ('bonus_technology', GID_R_ESPIONAGE, $bonus);
    $level = max ($bonus['level'], 0);

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

    $bonus = array();
    $bonus['level'] = $level;
    ModsExecIntRef ('bonus_technology', GID_R_ESPIONAGE, $bonus);
    $level = max ($bonus['level'], 0);

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
    $res = htmlspecialchars($user['oname']) . " ";

    // It is not possible to give the opportunity to write to the technical account space and admins.
    if ($user['admin'] == USER_TYPE_PLAYER) {
        $res .= "<a href='#' onclick='showMessageMenu(".$user['player_id'].")'>";
        $res .= "<img src='".UserSkin()."img/m.gif' title='".loca("EVENT_WRITE")."' alt='".loca("EVENT_WRITE")."'></a>";
    }

    return $res;
}

function PlanetFrom (array $planet, string $mission) : string
{
    $res = "";
    if ( GetPlanetType ($planet) == GAME_PTYP_PLANET ) $res .= loca("EVENT_FROM_PLANET");
    if ( $planet['type'] == PTYP_COLONY_PHANTOM || $planet['type'] == PTYP_FARSPACE ) $res = " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    else $res .= " " . $planet['name'] . " <a href=\"javascript:showGalaxy(".$planet['g'].",".$planet['s'].",".$planet['p'].")\" $mission>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a>";
    return $res;
}

function PlanetTo (array $planet, string $mission) : string
{
    $res = "";
    if ( GetPlanetType ($planet) == GAME_PTYP_PLANET ) $res .= loca("EVENT_TO_PLANET");
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
        if (isset($fleet[$rc])) {
            $sum += $fleet[$rc];
        }
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
    if ( $fleet_obj['mission'] >= FTYP_CUSTOM ) return $fleet_obj['mission'];
    else if ( $fleet_obj['mission'] < FTYP_RETURN ) return $fleet_obj['mission'];
    else if ( $fleet_obj['mission'] < FTYP_ORBITING ) return $fleet_obj['mission'] - FTYP_RETURN;
    else return $fleet_obj['mission'] - FTYP_ORBITING;
}

// ============================================================================
// Shared event list rendering.
//
// Both the Overview and the Phalanx event lists describe fleets with the same
// missions, but render them with their own CSS classes, loca strings and
// direction conventions. The functions below implement the mission-specific
// markup once; the $view argument selects the presentation.
// ============================================================================

/**
 * Builds the fleet name anchor block (OverFleet + TitleFleet tooltip).
 */
function EventFleetInfo (string $label, array $fleet, int $summary, string $css, bool $ignore_level=false) : string
{
    return va(loca($label), OverFleet($fleet, $summary, $css, $ignore_level)) .
        "</a><a href='#' title='".TitleFleet($fleet, $summary, $ignore_level)."'></a>";
}

/**
 * Builds the "alliance holds the fleet" block, where the player name is the
 * first loca-substitution and the fleet is the second one.
 */
function EventHeldFleetInfo (array $fleet, array $owner, int $summary, string $css, bool $ignore_level) : string
{
    return va(loca("EVENT_FLEET_HOLD"), PlayerDetails($owner), OverFleet($fleet, $summary, $css, $ignore_level)) .
        "</a><a href='#' title='".TitleFleet($fleet, $summary, $ignore_level)."'></a>";
}

/**
 * Renders a planet reference for an event route.
 *
 * @param string $kind One of "from", "to", "on".
 */
function EventPlanet (array $planet, string $kind, string $css) : string
{
    if ($kind == 'to') return PlanetTo($planet, $css);
    if ($kind == 'on') return PlanetOn($planet, $css);
    return PlanetFrom($planet, $css);
}

/**
 * Builds a route description (loca with planet substitutions).
 *
 * @param string $from Kind of the first planet reference.
 * @param string $to Kind of the second planet reference.
 * @param array|null $owner When given, the player details are the first
 *                          substitution.
 */
function EventRoute (string $label, array $origin, array $target, string $css, string $from='from', string $to='to', array|null $owner=null) : string
{
    $from_html = EventPlanet($origin, $from, $css);
    $to_html = EventPlanet($target, $to, $css);
    if ($owner === null) return va(loca($label), $from_html, $to_html);
    return va(loca($label), PlayerDetails($owner), $from_html, $to_html);
}

/**
 * Builds the mission text of an event row.
 */
function EventMissionText (string $label, array $fleet, string $css='', bool $cargo=false, bool $ownclass=false) : string
{
    $res = $cargo ? Cargo($fleet, $css, loca($label)) : loca($label);
    if ($ownclass) $res = "<span class='ownclass'>".$res."</span>";
    return $res;
}

/**
 * Emits one event row.
 */
function EventRow (string $span, string $fleet_info, string $route, string $mission) : void
{
    echo "<span class='".$span."'>".$fleet_info.$route.". ".loca("EVENT_MISSION").": ".$mission."</span>";
}

function FleetSpanAttack (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        if ($dir == 0) EventRow("flight phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_TO_PHALANX", $origin, $target, "phalanx_fleet", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_ATTACK", $fleet));
        else EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_ATTACK", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("flight ownattack",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownattack"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "ownattack"),
        EventMissionText("EVENT_M_ATTACK", $fleet, "ownattack", true));
    else if ($dir == 1) EventRow("return ownattack",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownattack"),
        EventRoute("EVENT_FROM_RETURN_TO", $origin, $target, "ownattack"),
        EventMissionText("EVENT_M_ATTACK", $fleet, "ownattack", true));
    else if ($dir == 0x10) EventRow("attack",
        EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "attack"),
        EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "attack", 'from', 'to', $owner ?? array()),
        EventMissionText("EVENT_M_ATTACK", $fleet));
}

function FleetSpanAcsAttack (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        if ($dir == 0) EventRow("phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_TO_PHALANX", $origin, $target, "federation", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_ACS_ATTACK", $fleet));
        else EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_ACS_ATTACK", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("federation",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownfederation"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "ownfederation"),
        EventMissionText("EVENT_M_ACS_ATTACK", $fleet, "ownfederation", true));
    else if ($dir == 1) EventRow("return ownfederation",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownfederation"),
        EventRoute("EVENT_FROM_RETURN_TO", $origin, $target, "ownfederation"),
        EventMissionText("EVENT_M_ACS_ATTACK", $fleet, "ownfederation", true));
    else if ($dir == 0x10) EventRow("federation",
        EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "federation"),
        EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "federation", 'from', 'to', $owner ?? array()),
        EventMissionText("EVENT_M_ACS_ATTACK", $fleet));
    else if ($dir == 0x20) EventRow("ownfederation",
        EventFleetInfo("EVENT_FLEET_ACS_HEAD", $fleet, 1, "ownfederation"),
        EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "ownfederation", 'from', 'to', $owner ?? array()),
        EventMissionText("EVENT_M_ACS_ATTACK", $fleet));
}

function FleetSpanTransport (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        if ($dir == 0) EventRow("flight phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_TO_PHALANX", $origin, $target, "phalanx_fleet", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_TRANSPORT", $fleet));
        else EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_TRANSPORT", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("flight owntransport",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "owntransport"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "owntransport"),
        EventMissionText("EVENT_M_TRANSPORT", $fleet, "owntransport", true));
    else if ($dir == 1) EventRow("return owntransport",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "owntransport"),
        EventRoute("EVENT_FROM_RETURN_TO", $origin, $target, "owntransport"),
        EventMissionText("EVENT_M_TRANSPORT", $fleet, "owntransport", true));
    else if ($dir == 0x10) EventRow("flight transport",
        EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "transport"),
        EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "transport", 'from', 'to', $owner ?? array()),
        EventMissionText("EVENT_M_TRANSPORT", $fleet));
}

function FleetSpanDeploy (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        EventRow("flight phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_TO_PHALANX", $origin, $target, "phalanx_fleet", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_DEPLOY", $fleet));
        return;
    }

    if ($dir == 0) EventRow("flight owndeploy",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "owndeploy"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "owndeploy"),
        EventMissionText("EVENT_M_DEPLOY", $fleet, "owndeploy", true));
    else if ($dir == 1) EventRow("return owndeploy",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "owndeploy"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "owndeploy"),
        EventMissionText("EVENT_M_DEPLOY", $fleet, "owndeploy", true));
}

function FleetSpanAcsHold (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        if ($dir == 2) EventRow("holding phalanx_fleet",
            EventHeldFleetInfo($fleet, $owner ?? array(), 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_TO_ORBIT", $origin, $target, "phalanx_fleet", 'from', 'on'),
            EventMissionText("EVENT_M_HOLD", $fleet, '', false, true));
        else if ($dir == 0) EventRow("flight phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "phalanx_fleet", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_HOLD", $fleet));
        else EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_HOLD", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("flight ownhold",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownhold"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "ownhold"),
        EventMissionText("EVENT_M_HOLD", $fleet, "ownhold", true));
    else if ($dir == 1) EventRow("return ownhold",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownhold"),
        EventRoute("EVENT_FROM_RETURN_TO", $origin, $target, "ownhold"),
        EventMissionText("EVENT_M_HOLD", $fleet, "ownhold", true));
    else if ($dir == 2) EventRow("holding ownhold",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownhold"),
        EventRoute("EVENT_HOLD_FROM_ONTO", $origin, $target, "ownhold", 'from', 'from'),
        EventMissionText("EVENT_M_HOLD", $fleet, "ownhold", true));
    else if ($dir == 0x20) EventRow("flight hold",
        EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "hold", true),
        EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "hold", 'from', 'to', $owner ?? array()),
        EventMissionText("EVENT_M_HOLD", $fleet, '', false, true));
    else if ($dir == 0x22) EventRow("holding hold",
        EventHeldFleetInfo($fleet, $owner ?? array(), 1, "hold", true),
        EventRoute("EVENT_FROM_TO_ORBIT", $origin, $target, "hold", 'from', 'from'),
        EventMissionText("EVENT_M_HOLD", $fleet));
}

function FleetSpanSpy (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        if ($dir == 0) EventRow("flight phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_TO_PHALANX", $origin, $target, "phalanx_fleet", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_SPY", $fleet));
        else EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_SPY", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("flight ownespionage",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownespionage"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "ownespionage"),
        EventMissionText("EVENT_M_SPY", $fleet, "ownespionage", true));
    else if ($dir == 1) EventRow("return ownespionage",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownespionage"),
        EventRoute("EVENT_FROM_RETURN_TO", $origin, $target, "ownespionage"),
        EventMissionText("EVENT_M_SPY", $fleet, "ownespionage", true));
    else if ($dir == 0x10) EventRow("flight espionage",
        EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "espionage"),
        EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "espionage", 'from', 'to', $owner ?? array()),
        EventMissionText("EVENT_M_SPY", $fleet));
}

function FleetSpanColonize (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_COLONY", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("flight owncolony",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "owncolony"),
        EventRoute("EVENT_COLONY_FROM_TO", $origin, $target, "owncolony"),
        EventMissionText("EVENT_M_COLONY", $fleet, "owncolony", true));
    else if ($dir == 1) EventRow("return owncolony",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "owncolony"),
        EventRoute("EVENT_COLONY_FROM_RETURN_TO", $origin, $target, "owncolony"),
        EventMissionText("EVENT_M_COLONY", $fleet, "owncolony", true));
}

function FleetSpanRecycle (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_RECYCLE", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("flight ownharvest",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownharvest"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "ownharvest"),
        EventMissionText("EVENT_M_RECYCLE", $fleet, "ownharvest", true));
    else if ($dir == 1) EventRow("return ownharvest",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownharvest"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "ownharvest"),
        EventMissionText("EVENT_M_RECYCLE", $fleet, "ownharvest", true));
}

function FleetSpanDestroy (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        if ($dir == 0) EventRow("flight phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_TO_PHALANX", $origin, $target, "phalanx_fleet", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_DESTROY", $fleet));
        else EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_DESTROY", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("flight owndestroy",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "owndestroy"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "owndestroy"),
        EventMissionText("EVENT_M_DESTROY", $fleet, "owndestroy", true));
    else if ($dir == 1) EventRow("return owndestroy",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "owndestroy"),
        EventRoute("EVENT_FROM_RETURN_TO", $origin, $target, "owndestroy"),
        EventMissionText("EVENT_M_DESTROY", $fleet, "owndestroy", true));
    else if ($dir == 0x10) EventRow("flight destroy",
        EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "destroy"),
        EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "destroy", 'from', 'to', $owner ?? array()),
        EventMissionText("EVENT_M_DESTROY", $fleet));
}

function FleetSpanAcsAttackHead (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        if ($dir == 0) EventRow("phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_TO_PHALANX", $origin, $target, "attack", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_ACS_ATTACK_HEAD", $fleet));
        else EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_ACS_ATTACK_HEAD", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("attack",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownattack"),
        EventRoute("EVENT_FROM_TO", $origin, $target, "ownattack"),
        EventMissionText("EVENT_M_ACS_ATTACK_HEAD", $fleet, "ownattack", true));
    else if ($dir == 1) EventRow("return ownattack",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownattack"),
        EventRoute("EVENT_FROM_RETURN_TO", $origin, $target, "ownattack"),
        EventMissionText("EVENT_M_ACS_ATTACK_HEAD", $fleet, "ownattack", true));
    else if ($dir == 0x10) EventRow("attack",
        EventFleetInfo("EVENT_FLEET_ENEMY", $fleet, 1, "attack"),
        EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "attack", 'from', 'to', $owner ?? array()),
        EventMissionText("EVENT_M_ACS_ATTACK_HEAD", $fleet));
    else if ($dir == 0x20) EventRow("ownattack",
        EventFleetInfo("EVENT_FLEET_ACS_HEAD", $fleet, 1, "ownattack"),
        EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "ownattack", 'from', 'to', $owner ?? array()),
        EventMissionText("EVENT_M_ACS_ATTACK_HEAD", $fleet));
}

function FleetSpanExpedition (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        if ($dir == 2) EventRow("holding phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_EXPO_FROM_ONTO_PHALANX", $origin, $target, "phalanx_fleet", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_EXPO", $fleet, '', false, true));
        else if ($dir == 0) EventRow("flight phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_PLAYER_FROM_TO", $origin, $target, "phalanx_fleet", 'from', 'to', $owner ?? array()),
            EventMissionText("EVENT_M_EXPO", $fleet));
        else EventRow("return phalanx_fleet",
            EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
            EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
            EventMissionText("EVENT_M_EXPO", $fleet, '', false, true));
        return;
    }

    if ($dir == 0) EventRow("flight owntransport",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownexpedition"),
        EventRoute("EVENT_EXPO_FROM_TO", $origin, $target, "ownexpedition"),
        EventMissionText("EVENT_M_EXPO", $fleet, "ownexpedition", true));
    else if ($dir == 1)
    {
        echo "<span class='return owntransport'>".
            EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownexpedition").
            va(loca("EVENT_EXPO_RETURN_FROM_TO"), PlanetTo($target, "ownexpedition"), Cargo($fleet, "ownexpedition", loca("EVENT_M_EXPO"))).
            "</span>";
    }
    else if ($dir == 2) EventRow("holding owntransport",
        EventFleetInfo("EVENT_FLEET_OWN", $fleet, 0, "ownexpedition"),
        EventRoute("EVENT_EXPO_FROM_ONTO", $origin, $target, "ownexpedition", 'from', 'from'),
        EventMissionText("EVENT_M_EXPO", $fleet, "ownexpedition", true));
}

function FleetSpanMissile (int $dir, array $fleet, array|null $owner, array $origin, array $target, string $view) : void
{
    if ($view == EVENT_LIST_PHALANX)
    {
        echo "<span class='missile'>" .va(loca("EVENT_RAK"), $fleet['ipm_amount'], PlanetFrom($origin, "phalanx_fleet"), PlanetTo($target, "phalanx_fleet")) . " ";
        if ( $fleet['ipm_target'] > 0 ) echo loca("EVENT_RAK_TARGET") . " " . loca ("NAME_".$fleet['ipm_target']);
        echo "</span>";
        return;
    }

    if ($dir == 0)
    {
        echo "<span class='ownmissile'>" .va(loca("EVENT_RAK"), $fleet['ipm_amount'], PlanetFrom($origin, ""), PlanetTo($target, "own")) . " ";
    }
    else if ($dir == 0x10)
    {
        echo "<span class='missile'>" .va(loca("EVENT_RAK"), $fleet['ipm_amount'], PlanetFrom($origin, ""), PlanetTo($target, "")) . " ";
    }
    if ( $fleet['ipm_target'] > 0 ) echo loca("EVENT_RAK_TARGET") . " " . loca ("NAME_".$fleet['ipm_target']);
    echo "</span>";
}

function FleetSpanCustom (int $dir, array $fleet, array|null $owner, array $origin, array $target) : void
{
    $label = "FLEET_ORDER_".$fleet['mission'];
    if ($dir == 0) EventRow("flight phalanx_fleet",
        EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
        EventRoute("EVENT_FROM_TO_PHALANX", $origin, $target, "phalanx_fleet", 'from', 'to', $owner ?? array()),
        EventMissionText($label, $fleet));
    else EventRow("return phalanx_fleet",
        EventFleetInfo("EVENT_FLEET_FRIEND", $fleet, 1, "phalanx_fleet", true),
        EventRoute("EVENT_FROM_RETURN_TO_PHALANX", $target, $origin, "phalanx_fleet"),
        EventMissionText($label, $fleet, '', false, true));
}

function FleetSpan ( array $fleet_entry, string $view = EVENT_LIST_OVERVIEW ) : void
{
    $mission = $fleet_entry['mission'];
    $dir = $fleet_entry['dir'];
    if ($view == EVENT_LIST_OVERVIEW) $dir = $dir | ($fleet_entry['assign'] << 4);
    $origin = LoadPlanetById ( $fleet_entry['origin_id'] );
    $target = LoadPlanetById ( $fleet_entry['target_id'] );
    $fleet = $fleet_entry;
    $owner = LoadUser ( $origin['owner_id'] );

// $dir mask (Overview):
// 0x0 - own fleet departs
// 0x1 - own fleet returns
// 0x2 - own fleet holds
// 0x10 - enemy fleet departs
// 0x20 - allied fleet departs
// 0x22 - allied fleet holds

    switch ($mission) {
        case FTYP_ATTACK:
            FleetSpanAttack ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_ACS_ATTACK:
            FleetSpanAcsAttack ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_TRANSPORT:
            FleetSpanTransport ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_DEPLOY:
            FleetSpanDeploy ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_ACS_HOLD:
            FleetSpanAcsHold ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_SPY:
            FleetSpanSpy ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_COLONIZE:
            FleetSpanColonize ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_RECYCLE:
            FleetSpanRecycle ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_DESTROY:
            FleetSpanDestroy ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_ACS_ATTACK_HEAD:    // ACS Attack Head fleet (slot 0)
            FleetSpanAcsAttackHead ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_EXPEDITION:
            FleetSpanExpedition ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        case FTYP_MISSILE:
            FleetSpanMissile ($dir, $fleet, $owner, $origin, $target, $view);
            break;
        default:
            if ($view == EVENT_LIST_PHALANX) FleetSpanCustom ($dir, $fleet, $owner, $origin, $target);
            else echo loca("EVENT_MISSION")." Type:$mission, Dir:$dir, Fleet: " .TitleFleet($fleet,0). ", from " .PlanetFrom($origin, ""). " to " .PlanetTo($target, ""). ", " . Cargo ($fleet,"","Cargo");
            break;
    }
}

function GetDirectionAssignment ( bool $friendly, array $fleet_obj, int &$dir, int &$assign ) : void
{
    global $GlobalUser;

    if ($fleet_obj['mission'] < FTYP_RETURN) $dir = 0;      // departing
    else if ($fleet_obj['mission'] < FTYP_ORBITING) $dir = 1;     // return
    else $dir = 2;  // holding

    if ( $fleet_obj['owner_id'] == $GlobalUser['player_id'] ) $assign = 0;
    else {
        if ($friendly && (GetMission ($fleet_obj) == FTYP_ACS_HOLD || GetMission ($fleet_obj) == FTYP_ACS_ATTACK_HEAD || GetMission ($fleet_obj) == FTYP_ACS_ATTACK) ) $assign = 2;
        else $assign = 1;
    }
}

/**
 * Builds the fleet array of an event task from a fleet object.
 *
 * $with_resources copies the transported resources; otherwise the cargo is
 * zeroed (needed for the pseudo return/hold tasks).
 */
function EventFleetRow (array $fleet_obj, int $origin_id, int $target_id, int $mission, int $dir, int $assign=0, bool $with_resources=false) : array
{
    global $fleetmap;
    global $transportableResources;

    $row = array ();
    foreach ( $fleetmap as $i=>$gid ) $row[$gid] = $fleet_obj[$gid];
    $row['owner_id'] = $fleet_obj['owner_id'];
    foreach ($transportableResources as $i=>$rc) {
        $row[$rc] = $with_resources ? $fleet_obj[$rc] : 0;
    }
    if ($mission == FTYP_MISSILE && isset($fleet_obj['ipm_amount'], $fleet_obj['ipm_target']))
    {
        $row['ipm_amount'] = $fleet_obj['ipm_amount'];
        $row['ipm_target'] = $fleet_obj['ipm_target'];
    }
    $row['origin_id'] = $origin_id;
    $row['target_id'] = $target_id;
    $row['mission'] = $mission;
    $row['dir'] = $dir;
    $row['assign'] = $assign;
    return $row;
}

/**
 * Renders the shared event list table body: sorts the tasks by arrival time
 * and prints the countdown rows.
 */
function EventListOutput (array $task, string $view) : void
{
    $anz = 0;
    if (count($task) > 0)
    {
        sksort ( $task, 'end_time', true);        // Sort by time of arrival.
        $now = time ();

        // Number the countdown boxes with a dedicated running counter ($n):
        // t() in utilities.js expects exactly the ids bxx1..bxx(anz). Rows
        // whose end time already passed are skipped, so the array key ($i)
        // would leave holes and break every countdown.
        $n = 0;
        foreach ($task as $t)
        {
            $seconds = max($t['end_time']-$now, 0);
            if ( $seconds <= 0 ) continue;
            $n++;
            if ($t['fleets'] > 1) echo "<tr class=''>\n";
            else if ($t['fleet'][0]['dir'] == 0) echo "<tr class='flight'>\n";
            else if ($t['fleet'][0]['dir'] == 1) echo "<tr class='return'>\n";
            else if ($t['fleet'][0]['dir'] == 2) echo "<tr class='holding'>\n";
            echo "<th><div id='bxx".$n."' title='".$seconds."'star='".$t['end_time']."'></div></th>\n";
            echo "<th colspan='3'>";
            for ($fl=0; $fl<$t['fleets']; $fl++)
            {
                FleetSpan ($t['fleet'][$fl], $view);
                if ($t['fleets'] > 1) echo "<br /><br />";
            }
            echo "</th></tr>\n\n";
            $anz++;
        }
        if ($anz) echo "<script language=javascript>anz=".$anz.";t();</script>\n\n";
    }
}

?>
