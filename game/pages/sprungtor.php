<?php

/** @var array $GlobalUser */
/** @var array $GlobalUni */

// Fleet jump by jump gate

$GateError = "";

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "jumpgate", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("sprungtor");

BeginContent ();

if ( key_exists ( 'qm', $_POST) ) $source_id = intval($_POST['qm']);
else $source_id = 0;
if ( key_exists ( 'zm', $_POST) ) $target_id = intval($_POST['zm']);
else $target_id = 0;

$total = 0;
foreach ( $fleetmap_revnosat as $i=>$gid)
{
    if ( !key_exists ( "c$gid", $_POST) )  $_POST["c$gid"] = 0;
    $total += floor (abs (intval($_POST["c$gid"])));
}

$source = GetPlanet ( $source_id );
$target = GetPlanet ( $target_id );

if ( $source['type'] != PTYP_MOON ) $GateError .= "<center>\n".loca("GATE_ERR_START")."<br></center>\n";
if ( $target['type'] != PTYP_MOON ) $GateError .= "<center>\n".loca("GATE_ERR_TARGET")."<br></center>\n";

if ( $GateError === "" )
{
    if ( $source["b".GID_B_JUMP_GATE] == 0 ) $GateError .= "<center>\n".loca("GATE_ERR_START_GATE")."<br></center>\n";
    if ( $target["b".GID_B_JUMP_GATE] == 0 ) $GateError .= "<center>\n".loca("GATE_ERR_TARGET_GATE")."<br></center>\n";
}

if ( $GateError === "" )
{
    if ( ($source['owner_id'] != $GlobalUser['player_id']) ||
         ($target['owner_id'] != $GlobalUser['player_id'])  ) $GateError .= "<center>\n".loca("GATE_ERR_MOON")."<br></center>\n";
}

if ( $GateError === "" )
{
    if ( $total == 0 ) $GateError .= "<center>\n".loca("GATE_ERR_SHIPS")."<br></center>\n";
}

// Prepare a fleet list for jump.
if ( $GateError === "" )
{
    $fleet = array ();
    foreach ( $fleetmap_revnosat as $i=>$gid)
    {
        $amount = floor (abs(intval($_POST["c$gid"])));
        if ( $amount > $source["f$gid"] ) 
        {
            $GateError .= "<center>\n".loca("GATE_ERR_NOTENOUGH")."<br></center>\n";
            break;
        }
        $fleet[$gid] = $amount;
    }
    $fleet[GID_F_SAT] = 0;    // solar sats.
}

// Jump
if ( $GateError === "" )
{
    // Jump the fleet
    AdjustShips ( $fleet, $source_id, '-' );
    AdjustShips ( $fleet, $target_id, '+' );

    $cooldown_time = (60*60) / $GlobalUni['fspeed'] - 1;
    $cooldown = $now + $cooldown_time;

    // Warm up the gate
    $now = time ();
    $query = "UPDATE ".$db_prefix."planets SET gate_until=".$cooldown." WHERE planet_id=$source_id";
    dbquery ($query);
    $query = "UPDATE ".$db_prefix."planets SET gate_until=".$cooldown." WHERE planet_id=$target_id";
    dbquery ($query);

    // Do a redirect to the target moon gate
    MyGoto ( "infos", "&cp=$target_id&gid=43" );
}

EndContent ();

PageFooter ("", $GateError);
ob_end_flush ();
?>