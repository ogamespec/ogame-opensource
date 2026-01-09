<?php

$battle_debug = 0;
$exploded_counter = 0;
$already_exploded_counter = 0;

if ($battle_debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

require_once "id.php";
require_once "unit.php";
require_once "prod.php";

// Spare Battle Engine in PHP.
// If the server does not support the execution of system(), then a PHP implementation of the battle engine is used

// How the battle engine is run and integrated in PHP:
// - The engine receives as input and outputs as output data similar to a C battle engine; only it receives and outputs them as strings.
// - Then everything is trivial: we parse the input data, perform calculations, return the result
// - The choice between an external battle engine and an internal (PHP-based) one is selected by the uni['php_battle'] universe setting: if it is 1, use the PHP-based engine
// - The caller (battle.php) honestly pretends and generates files, similar to the external engine. This can be useful for "flight debugging" (log history)

/*
PHP engine operation from a technical point of view.
All arrays are stored as long strings. The arr[i] element is accessed by the ord($arr[$i]) construct, writing $arr[$i] = chr(n).
This is done to save memory - a string takes as many bytes as there are characters in it, while associative arrays in PHP are quite expensive.

The array-strings are divided into two identical groups - attackers and defenders. Each group is divided into several array-strings, with all slots combined (to simplify indexing of random shots, from 0 to N):
$obj = { id, id, id, ... }     -- array of units, in order to make the object numbers fit into one byte (to save memory), fleet numbering starts from 02 (instead of 202) and defense from 201 (instead of 401). (n-200)
$slot = { n, n, n, ... }       -- unit slot number (for ACS), this is needed to generate a battle report to sort units into slots.
$explo = { 1, 0, 1, ... }      -- array of exploded units, after each round the exploded units are deleted and a new array $obj is formed. TODO: the data is in packed format 8 units per 1 byte
$shld = { }                    -- unit shields. before the start of each round this array is filled with maximum values (shields are charged). packed 4-byte array-string
$hull = { }                    -- current armor value given the damage for unit n. packed 4-byte array-string

To debug: just open http://localhost/game/battle_engine.php with $battle_debug = 1 set.

*/

// For debugging, convert the array-string into a readable format
function hex_array_to_text ($arr)
{
    return implode(unpack("H*", $arr));
}

function get_packed_word (&$arr, $idx)
{
    return (ord($arr[4*$idx]) << 24) | 
        (ord($arr[4*$idx+1]) << 16) | 
        (ord($arr[4*$idx+2]) << 8) |
        (ord($arr[4*$idx+3]) << 0);
}

function set_packed_word (&$arr, $idx, $val)
{
    $ival = (int)$val;
    $arr[4*$idx] = chr(($ival >> 24) & 0xff);
    $arr[4*$idx+1] = chr(($ival >> 16) & 0xff);
    $arr[4*$idx+2] = chr(($ival >> 8) & 0xff);
    $arr[4*$idx+3] = chr(($ival >> 0) & 0xff);
}

// Allocate memory for units and set initial values.
function InitBattle ($slot, $num, $objs, $attacker, &$explo_arr, &$obj_arr, &$slot_arr, &$hull_arr, &$shld_arr )
{
    global $UnitParam;
    global $fleetmap;
    global $defmap_norak;

    $amap = $fleetmap;
    $dmap = $defmap_norak;

    $ucnt = 0;
    $slot_id = 0;

    for ($i=0; $i<$num; $i++) {

        foreach ( $amap as $n=>$gid ) {

            for ($obj=0; $obj<$slot[$i][$gid]; $obj++) {

                $hull = $UnitParam[$gid][0] * 0.1 * (10+$slot[$i]['armr']) / 10;
                $obj_type = $gid - 200;

                $explo_arr[$ucnt] = chr(0);
                $obj_arr[$ucnt] = chr($obj_type);
                $slot_arr[$ucnt] = chr($slot_id);
                set_packed_word ($hull_arr, $ucnt, $hull);
                set_packed_word ($shld_arr, $ucnt, 0);

                $ucnt++;
            }
        }

        if (!$attacker) {

            foreach ( $dmap as $n=>$gid ) {

                for ($obj=0; $obj<$slot[$i][$gid]; $obj++) {

                    $hull = $UnitParam[$gid][0] * 0.1 * (10+$slot[$i]['armr']) / 10;
                    $obj_type = $gid - 200;

                    $explo_arr[$ucnt] = chr(0);
                    $obj_arr[$ucnt] = chr($obj_type);
                    $slot_arr[$ucnt] = chr($slot_id);
                    set_packed_word ($hull_arr, $ucnt, $hull);
                    set_packed_word ($shld_arr, $ucnt, 0);

                    $ucnt++;
                }
            }
        }

        $slot_id++;
    }
}

// Shot a => b. Returns damage.
// absorbed - the accumulator of damage absorbed by shields (for the one who is attacked, i.e. for unit "b").
function UnitShoot ($a, $b, &$aunits, &$aslot, &$ahull, &$ashld, $attackers, &$dunits, &$dslot, &$dhull, &$dshld, &$dexplo, $defenders, &$absorbed, &$dm, &$dk, $fid, $did )
{
    global $UnitParam;
    global $exploded_counter;
    global $already_exploded_counter;

    $a_slot_id = ord($aslot[$a]);
    $a_gid = ord($aunits[$a]) + 200;

    $b_slot_id = ord($dslot[$b]);
    $b_gid = ord($dunits[$b]) + 200;

    $apower = $UnitParam[$a_gid][2] * (10 + $attackers[$a_slot_id]['weap']) / 10;

    if (ord($dexplo[$b]) !=0 ) {
        $already_exploded_counter++;
        return $apower; // Already blown up.
    }

    if (get_packed_word($dshld, $b) == 0) {  // No shields.

        $b_hull = get_packed_word($dhull, $b);
        if ($apower >= $b_hull) $b_hull = 0;
        else $b_hull -= $apower;
        set_packed_word ($dhull, $b, $b_hull);
    }
    else { // We take away from shields, and if there is enough damage, from armor as well.

        $b_shieldmax = $UnitParam[$b_gid][1] * (10 + $defenders[$b_slot_id]['shld']) / 10;
        $b_shield = get_packed_word ($dshld, $b);

        $prc = $b_shieldmax * 0.01;
        $depleted = floor ($apower / $prc);
        if ($b_shield < ($depleted * $prc)) {
            $absorbed += $b_shield;
            $adelta = $apower - $b_shield;
            $b_hull = get_packed_word($dhull, $b);
            if ($adelta >= $b_hull) {
                $b_hull = 0;
            }
            else {
                $b_hull -= $adelta;
            }
            set_packed_word ($dhull, $b, $b_hull);
            set_packed_word ($dshld, $b, 0);
        }
        else {
            set_packed_word ($dshld, $b, $b_shield - ($depleted * $prc));
            $absorbed += $apower;
        }
    }

    $b_hullmax = $UnitParam[$b_gid][0] * 0.1 * (10 + $defenders[$b_slot_id]['armr']) / 10;
    $b_hull = get_packed_word($dhull, $b);
    $b_shield = get_packed_word ($dshld, $b);

    if ($b_hull <= $b_hullmax * 0.7 && $b_shield == 0) {    // Blow it up and scrap it.

        if (mt_rand (0, 99) >= (($b_hull * 100) / $b_hullmax) || $b_hull == 0) {

            $price = ShipyardPrice ($b_gid);

            // If a defense is blown, use DID (Defense-in-Debris), if a fleet is blown, use FID (Fleet-in-Debris)
            $dm += intval (ceil($price['m'] * ((float)( ($b_gid >= 401 ? $did : $fid) / 100.0))));
            $dk += intval (ceil($price['k'] * ((float)( ($b_gid >= 401 ? $did : $fid) / 100.0))));

            $dexplo[$b] = chr(1);
            $exploded_counter++;
        }
    }

    return $apower;
}

// Clean up blown up ships and defenses. Returns the number of units blown up.
function WipeExploded ($count, &$explo_arr, &$obj_arr, &$slot_arr, &$hull_arr, &$shld_arr)
{
    $exploded = 0;
    $dst = 0;

    $ret = array();

    // New arrays
    $explo_new = "";
    $obj_new = "";
    $slot_new = "";
    $hull_new = "";
    $shld_new = "";

    for ($i=0; $i<$count; $i++) {

        if ( ord($explo_arr[$i]) == 0 ) {

            // If not exploded move to a new array
            $explo_new[$dst] = chr(0);
            $obj_new[$dst] = $obj_arr[$i];
            $slot_new[$dst] = $slot_arr[$i];
            set_packed_word ($hull_new, $dst, get_packed_word($hull_arr, $i) );
            set_packed_word ($shld_new, $dst, get_packed_word($shld_arr, $i) );

            $dst++;
        }
        else {

            // Otherwise skip (clean)
            $exploded++;
        }
    }

    // Release old arrays
    unset ($explo_arr);
    unset ($obj_arr);
    unset ($slot_arr);
    unset ($hull_arr);
    unset ($shld_arr);

    // Update source arrays
    $ret['explo_arr'] = $explo_new;
    $ret['obj_arr'] = $obj_new;
    $ret['slot_arr'] = $slot_new;
    $ret['hull_arr'] = $hull_new;
    $ret['shld_arr'] = $shld_new;
    $ret['exploded'] = $exploded;

    // TODO: Use a remap table for blown units? Maybe that would be faster..

    return $ret;
}

// Charge shields on unexploded units
function ChargeShields ($slot, $count, &$explo_arr, &$obj_arr, &$slot_arr, &$shld_arr)
{
    global $UnitParam;

    for ($i=0; $i<$count; $i++) {

        if (ord($explo_arr[$i]) != 0 ) {

            set_packed_word ($shld_arr, $i, 0);
        }
        else {

            $slot_id = ord($slot_arr[$i]);
            $gid = ord($obj_arr[$i]) + 200;
            $shield_max = $UnitParam[$gid][1] * (10 + $slot[$slot_id]['shld']) / 10;
            set_packed_word ($shld_arr, $i, $shield_max);
        }
    }
}

// Check the combat for a quick draw. If none of the units have armor damage, the combat ends in a quick draw.
function CheckFastDraw (&$aunits, &$aslot, &$ahull, $aobjs, $attackers, &$dunits, &$dslot, &$dhull, $dobjs, $defenders)
{
    global $UnitParam;

    for ($i=0; $i<$aobjs; $i++) {

        $slot_id = ord($aslot[$i]);
        $gid = ord($aunits[$i]) + 200;
        $hull_max = $UnitParam[$gid][0] * 0.1 * (10+$attackers[$slot_id]['armr']) / 10;

        if (get_packed_word($ahull, $i) != $hull_max) return false;
    }

    for ($i=0; $i<$dobjs; $i++) {

        $slot_id = ord($dslot[$i]);
        $gid = ord($dunits[$i]) + 200;
        $hull_max = $UnitParam[$gid][0] * 0.1 * (10+$defenders[$slot_id]['armr']) / 10;

        if (get_packed_word($dhull, $i) != $hull_max) return false;
    }

    return true;
}

// Check the possibility of re-firing. Original unit IDs are used for convenience
function RapidFire ($atyp, $dtyp)
{
    $rapidfire = 0;

    if ( IsDefense($atyp) ) return 0;

    // Deathstar vs Espionage Probe/Solar Satellite
    if ($atyp==GID_F_DEATHSTAR && ($dtyp==GID_F_PROBE || $dtyp==GID_F_SAT) && mt_rand(1,10000)>8) $rapidfire = 1;
    // Other units vs Espionage Probe/Solar Satellite
    else if ($atyp!=GID_F_PROBE && ($dtyp==GID_F_PROBE || $dtyp==GID_F_SAT) && mt_rand(1,100)>20) $rapidfire = 1;
    // Heavy Fighter vs Small Cargo
    else if ($atyp==GID_F_HF && $dtyp==GID_F_SC && mt_rand(1,100)>33) $rapidfire = 1;
    // Cruiser vs Light Fighter
    else if ($atyp==GID_F_CRUISER && $dtyp==GID_F_LF && mt_rand(1,1000)>166) $rapidfire = 1;
    // Cruiser vs Rocket Launcher
    else if ($atyp==GID_F_CRUISER && $dtyp==GID_D_RL && mt_rand(1,100)>10) $rapidfire = 1;
    // Bomber vs light defense
    else if ($atyp==GID_F_BOMBER && ($dtyp==GID_D_RL || $dtyp==GID_D_LL) && mt_rand(1,100)>20) $rapidfire = 1;
    // Bomber vs medium defense
    else if ($atyp==GID_F_BOMBER && ($dtyp==GID_D_HL || $dtyp==GID_D_ION) && mt_rand(1,100)>10) $rapidfire = 1;
    // Destroyer vs Battlecruiser
    else if ($atyp==GID_F_DESTRO && $dtyp==GID_F_BATTLECRUISER && mt_rand(1,100)>50) $rapidfire = 1;
    // Destroyer vs Light Laser
    else if ($atyp==GID_F_DESTRO && $dtyp==GID_D_LL && mt_rand(1,100)>10) $rapidfire = 1;
    // Battlecruiser vs transport
    else if ($atyp==GID_F_BATTLECRUISER && ($dtyp==GID_F_SC || $dtyp==GID_F_LC) && mt_rand(1,100)>20) $rapidfire = 1;
    // Battlecruiser vs medium fleet
    else if ($atyp==GID_F_BATTLECRUISER && ($dtyp==GID_F_HF || $dtyp==GID_F_CRUISER) && mt_rand(1,100)>25) $rapidfire = 1;
    // Battlecruiser vs Battleship
    else if ($atyp==GID_F_BATTLECRUISER && $dtyp==GID_F_BATTLESHIP && mt_rand(1,1000)>143) $rapidfire = 1;
    // Deathstar vs civilian fleet
    else if ($atyp==GID_F_DEATHSTAR && ($dtyp==GID_F_SC || $dtyp==GID_F_LC || $dtyp==GID_F_COLON || $dtyp==GID_F_RECYCLER) && mt_rand(1,1000)>4) $rapidfire = 1;
    // Deathstar vs Light Fighter
    else if ($atyp==GID_F_DEATHSTAR && $dtyp==GID_F_LF && mt_rand(1,1000)>5) $rapidfire = 1;
    // Deathstar vs Heavy Fighter
    else if ($atyp==GID_F_DEATHSTAR && $dtyp==GID_F_HF && mt_rand(1,1000)>10) $rapidfire = 1;
    // Deathstar vs Cruiser
    else if ($atyp==GID_F_DEATHSTAR && $dtyp==GID_F_CRUISER && mt_rand(1,1000)>30) $rapidfire = 1;
    // Deathstar vs Battleship
    else if ($atyp==GID_F_DEATHSTAR && $dtyp==GID_F_BATTLESHIP && mt_rand(1,1000)>33) $rapidfire = 1;
    // Deathstar vs Bomber
    else if ($atyp==GID_F_DEATHSTAR && $dtyp==GID_F_BOMBER && mt_rand(1,1000)>40) $rapidfire = 1;
    // Deathstar vs Destroyer
    else if ($atyp==GID_F_DEATHSTAR && $dtyp==GID_F_DESTRO && mt_rand(1,1000)>200) $rapidfire = 1;
    // Deathstar vs Battlecruiser
    else if ($atyp==GID_F_DEATHSTAR && $dtyp==GID_F_BATTLECRUISER && mt_rand(1,1000)>66) $rapidfire = 1;
    // Deathstar vs light defense
    else if ($atyp==GID_F_DEATHSTAR && ($dtyp==GID_D_RL || $dtyp==GID_D_LL) && mt_rand(1,1000)>5) $rapidfire = 1;
    // Deathstar vs medium defense
    else if ($atyp==GID_F_DEATHSTAR && ($dtyp==GID_D_HL || $dtyp==GID_D_ION) && mt_rand(1,1000)>10) $rapidfire = 1;
    // Deathstar vs heavy defense
    else if ($atyp==GID_F_DEATHSTAR && $dtyp==GID_D_GAUSS && mt_rand(1,1000)>20) $rapidfire = 1;

    return $rapidfire;
}

function DoBattle (&$res, $Rapidfire, $fid, $did)
{
    global $battle_debug;
    global $already_exploded_counter;
    global $exploded_counter;
    global $fleetmap;
    global $defmap_norak;

    // A set of working array strings for calculations. Arrays of shields and armor use packing of long numbers

    $obj_att = "";
    $slot_att = "";
    $explo_att = "";
    $shld_att = "";
    $hull_att = "";

    $obj_def = "";
    $slot_def = "";
    $explo_def = "";
    $shld_def = "";
    $hull_def = "";

    // Shot statistics

    $shoots = array();
    $spower = array();
    $absorbed = array();

    // Debris field

    $dm = $dk = 0;

    $amap = $fleetmap;
    $dmap = $defmap_norak;

    $anum = count ($res['before']['attackers']);
    $dnum = count ($res['before']['defenders']);

    // Count the number of units before the battle

    $aobjs = 0;
    $dobjs = 0;

    for ($i=0; $i<$anum; $i++) {
        foreach ( $amap as $n=>$gid ) {
            $aobjs += $res['before']['attackers'][$i][$gid];
        }
    }

    for ($i=0; $i<$dnum; $i++) {
        foreach ( $amap as $n=>$gid ) {
            $dobjs += $res['before']['defenders'][$i][$gid];
        }
        foreach ( $dmap as $n=>$gid ) {
            $dobjs += $res['before']['defenders'][$i][$gid];
        }
    }

    // Prepare arrays of units

    InitBattle ($res['before']['attackers'], $anum, $aobjs, 1, $explo_att, $obj_att, $slot_att, $hull_att, $shld_att);
    InitBattle ($res['before']['defenders'], $dnum, $dobjs, 0, $explo_def, $obj_def, $slot_def, $hull_def, $shld_def);

    // Rounds

    $res['rounds'] = array();

    for ($round=0; $round<6; $round++) {

        $already_exploded_counter = 0;

        if ($aobjs == 0 || $dobjs == 0) break;

        // Reset stats.
        $shoots[0] = $shoots[1] = 0;
        $spower[0] = $spower[1] = 0;
        $absorbed[0] = $absorbed[1] = 0;

        // Charge shields.

        ChargeShields ($res['before']['attackers'], $aobjs, $explo_att, $obj_att, $slot_att, $shld_att);
        ChargeShields ($res['before']['defenders'], $dobjs, $explo_def, $obj_def, $slot_def, $shld_def);

        $prev_dm = $dm;
        $prev_dk = $dk;
        $prev_exploded = $exploded_counter;

        // Fire shots.

        for ($slot=0; $slot<$anum; $slot++) {     // Attackers

            for ($i=0; $i<$aobjs; $i++) {
                $rapidfire = 1;

                if (ord($slot_att[$i]) == $slot) {
                    // The shot.
                    while ($rapidfire) {
                        $idx = mt_rand (0, $dobjs - 1);
                        $apower = UnitShoot ($i, $idx, 
                            $obj_att, $slot_att, $hull_att, $shld_att, $res['before']['attackers'],
                            $obj_def, $slot_def, $hull_def, $shld_def, $explo_def, $res['before']['defenders'],
                            $absorbed[1], $dm, $dk, $fid, $did );
                        $shoots[0]++;
                        $spower[0] += $apower;

                        $atyp = ord($obj_att[$i]) + 200;
                        $dtyp = ord($obj_def[$idx]) + 200;
                        $rapidfire = RapidFire ($atyp, $dtyp);

                        if ($Rapidfire == 0) $rapidfire = 0;
                    }
                }
            }
        }

        for ($slot=0; $slot<$dnum; $slot++) {     // Defenders

            for ($i=0; $i<$dobjs; $i++) {
                $rapidfire = 1;

                if (ord($slot_def[$i]) == $slot) {
                    // The shot.
                    while ($rapidfire) {
                        $idx = mt_rand (0, $aobjs - 1);
                        $apower = UnitShoot ($i, $idx,
                            $obj_def, $slot_def, $hull_def, $shld_def, $res['before']['defenders'],
                            $obj_att, $slot_att, $hull_att, $shld_att, $explo_att, $res['before']['attackers'],
                            $absorbed[0], $dm, $dk, $fid, $did );
                        $shoots[1]++;
                        $spower[1] += $apower;

                        $atyp = ord($obj_def[$i]) + 200;
                        $dtyp = ord($obj_att[$idx]) + 200;
                        $rapidfire = RapidFire ($atyp, $dtyp);

                        if ($Rapidfire == 0) $rapidfire = 0;
                    }
                }
            }
        }

        // Quick draw?

        $fastdraw = CheckFastDraw (
            $obj_att, $slot_att, $hull_att, $aobjs, $res['before']['attackers'],
            $obj_def, $slot_def, $hull_def, $dobjs, $res['before']['defenders'] );

        // Clean out the blown ships and defenses.

        $ret = WipeExploded ($aobjs, $explo_att, $obj_att, $slot_att, $hull_att, $shld_att);
        $aobjs -= $ret['exploded'];
        $explo_att = $ret['explo_arr'];
        $obj_att = $ret['obj_arr'];
        $slot_att = $ret['slot_arr'];
        $hull_att = $ret['hull_arr'];
        $shld_att = $ret['shld_arr'];

        $ret = WipeExploded ($dobjs, $explo_def, $obj_def, $slot_def, $hull_def, $shld_def);
        $dobjs -= $ret['exploded'];
        $explo_def = $ret['explo_arr'];
        $obj_def = $ret['obj_arr'];
        $slot_def = $ret['slot_arr'];
        $hull_def = $ret['hull_arr'];
        $shld_def = $ret['shld_arr'];

        // Save round results

        $res['rounds'][$round] = array();
        $r = &$res['rounds'][$round];

        $r['ashoot'] = $shoots[0];
        $r['apower'] = $spower[0];
        $r['dabsorb'] = $absorbed[1];
        $r['dshoot'] = $shoots[1];
        $r['dpower'] = $spower[1];
        $r['aabsorb'] = $absorbed[0];

        if ($battle_debug) {
            $r['dm'] = $dm - $prev_dm;
            $r['dk'] = $dk - $prev_dk;
            $r['exploded_this_round'] = $exploded_counter - $prev_exploded;
            $r['already_exploded_counter'] = $already_exploded_counter;
        }

        $r['attackers'] = array();

        for ($slot=0; $slot<$anum; $slot++) {

            $r['attackers'][$slot]['name'] = $res['before']['attackers'][$slot]['name'];
            $r['attackers'][$slot]['id'] = $res['before']['attackers'][$slot]['id'];
            $r['attackers'][$slot]['g'] = $res['before']['attackers'][$slot]['g'];
            $r['attackers'][$slot]['s'] = $res['before']['attackers'][$slot]['s'];
            $r['attackers'][$slot]['p'] = $res['before']['attackers'][$slot]['p'];

            foreach ( $amap as $n=>$gid ) {
                $r['attackers'][$slot][$gid] = 0;
            }

            for ($i=0; $i<$aobjs; $i++) {
                if (ord($slot_att[$i]) != $slot) {
                    continue;
                }
                $obj_id = ord($obj_att[$i]) + 200;
                $r['attackers'][$slot][$obj_id]++;
            }
        }

        $r['defenders'] = array();

        for ($slot=0; $slot<$dnum; $slot++) {

            $r['defenders'][$slot]['name'] = $res['before']['defenders'][$slot]['name'];
            $r['defenders'][$slot]['id'] = $res['before']['defenders'][$slot]['id'];
            $r['defenders'][$slot]['g'] = $res['before']['defenders'][$slot]['g'];
            $r['defenders'][$slot]['s'] = $res['before']['defenders'][$slot]['s'];
            $r['defenders'][$slot]['p'] = $res['before']['defenders'][$slot]['p'];

            foreach ( $amap as $n=>$gid ) {
                $r['defenders'][$slot][$gid] = 0;
            }
            foreach ( $dmap as $n=>$gid ) {
                $r['defenders'][$slot][$gid] = 0;
            }

            for ($i=0; $i<$dobjs; $i++) {
                if (ord($slot_def[$i]) != $slot) {
                    continue;
                }                
                $obj_id = ord($obj_def[$i]) + 200;
                $r['defenders'][$slot][$obj_id]++;
            }
        }

        if ($fastdraw) break;
    }

    // Battle Results.

    if ($aobjs > 0 && $dobjs == 0){ // The attacker won
        $res['result'] = "awon";
    }
    else if ($dobjs > 0 && $aobjs == 0) { // The attacker lost
        $res['result'] = "dwon";
    }
    else    // Draw
    {
        $res['result'] = "draw";
    }

    $res['dm'] = $dm;
    $res['dk'] = $dk;

    // Save memory allocation statistics

    $res['peak_allocated'] = memory_get_usage();

    // Clear memory

    unset($obj_att);
    unset($slot_att);
    unset($explo_att);
    unset($shld_att);
    unset($hull_att);

    unset($obj_def);
    unset($slot_def);
    unset($explo_def);
    unset($shld_def);
    unset($hull_def);
}

function extract_text ($str, $s, $e)
{
    $start  = strpos($str, $s);
    $end    = strpos($str, $e, $start + 1);
    $length = $end - $start;
    $result = trim (substr($str, $start + 1, $length - 1));
    return $result;
}

function deserialize_slot ($str, $att)
{
    global $fleetmap;
    global $defmap_norak;
    
    $amap = $fleetmap;
    $dmap = $defmap_norak;

    $res = array();

    // We need to cut the substring with the name, because the name may contain spaces (#119)
    $bracket_end = strpos ($str, '}');
    $name_str = substr ($str, 0, $bracket_end + 1);
    $param_str = trim (substr ($str, $bracket_end + 1));
    $items = explode (" ", $param_str);    

    $res['name'] = extract_text ($name_str, '{', '}');
    $res['id'] = intval ($items[0]);
    $res['g'] = intval ($items[1]);
    $res['s'] = intval ($items[2]);
    $res['p'] = intval ($items[3]);
    $res['weap'] = intval ($items[4]);
    $res['shld'] = intval ($items[5]);
    $res['armr'] = intval ($items[6]);

    foreach ( $amap as $n=>$gid ) {
        $res[$gid] = intval ($items[7+$n]);
    }
    if (!$att) {
        foreach ( $dmap as $n=>$gid ) {
            $res[$gid] = intval ($items[21+$n]);
        }
    }

    return $res;
}

// Parse the input data
function ParseInput ($source, &$rf, &$fid, &$did, &$attackers, &$defenders)
{
    global $battle_debug;

    $kv = array();

    // Split input data into strings
    $arr = explode("\n", $source);

    // Split strings into key/value pairs
    foreach ($arr as $line) {
        if (empty($line)) {
            continue;
        }
        $pair = explode ("=", $line);
        $kv[trim($pair[0])] = trim($pair[1]);
    }

    // DEBUG
    if ($battle_debug) {
        echo "Parsed input data as key-value:<br/>";
        echo "<pre>";
        print_r ($kv);
        echo "</pre>";
    }

    // Spread the parameters where they need to go
    $rf = intval ($kv['Rapidfire']);
    $fid = intval ($kv['FID']);
    $did = intval ($kv['DID']);

    if ($did < 0) $did = 0;
    if ($fid < 0) $fid = 0;
    if ($did > 100) $did = 100;
    if ($fid > 100) $fid = 100;

    $anum = intval ($kv['Attackers']);
    $dnum = intval ($kv['Defenders']);

    for ($i=0; $i<$anum; $i++) {

        $slot_text = extract_text ($kv['Attacker' . $i], '(', ')');
        $attackers[$i] = deserialize_slot ($slot_text, true);
    }

    for ($i=0; $i<$dnum; $i++) {

        $slot_text = extract_text ($kv['Defender' . $i], '(', ')');
        $defenders[$i] = deserialize_slot ($slot_text, false);
    }
}

// The output is an array of battleresult, the format is similar to that of the C battle engine.
function BattleEngine ($source)
{
    global $battle_debug;

    // Default battle engine settings
    $rf = 1;
    $fid = 30;
    $did = 0;

    // Output result
    $res = array ();

    // Initialize RNG
    list($usec,$sec)=explode(" ",microtime());
    $battle_seed = (int)($sec * $usec) & 0xffffffff;
    mt_srand ($battle_seed);
    $res['battle_seed'] = $battle_seed;

    // Initial slots of attackers and defenders
    $res['before'] = array();
    $res['before']['attackers'] = array();
    $res['before']['defenders'] = array();

    // Parse the input data
    ParseInput ($source, $rf, $fid, $did, $res['before']['attackers'], $res['before']['defenders']);
    if ($battle_debug) {
        echo "rf = $rf, fid = $fid, did = $did<br/>";
    }

    // **** START BATTLE ****
    DoBattle ($res, $rf, $fid, $did);

    return $res;
}

function BattleDebug()
{

    global $exploded_counter;

    $starttime = microtime(true);
    $allocated_before = memory_get_usage();

?>

<html> 
 <head> 
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel="stylesheet" type="text/css" href="../evolution/formate.css" />
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <link rel="stylesheet" type="text/css" href="css/combox.css">
 </head>
<body>

<!-- CONTENT AREA -->
<div id='content'>

<?php

// DEBUG

// Hardcode your raw battle data here.

// Memorable fight.
$source = "Rapidfire = 1
FID = 70
DID = 0
Attackers = 14
Defenders = 1
Attacker0 = ({OtellO} 252298 1 2 10 13 13 13 0 0 0 0 0 0 0 0 0 0 0 0 0 4613 )
Attacker1 = ({Voskreshaya} 252302 1 6 9 13 11 14 5490 0 16379 0 0 123 0 0 0 0 0 367 0 0 )
Attacker2 = ({r2r} 252312 1 15 6 14 13 15 2055 0 0 0 0 0 0 0 0 0 0 0 0 0 )
Attacker3 = ({onelife} 252310 1 4 7 14 14 15 0 0 0 0 0 0 0 0 0 0 0 0 0 3100 )
Attacker4 = ({Voskreshaya} 252301 1 6 9 13 11 14 0 0 0 0 5020 0 0 0 0 0 0 0 0 0 )
Attacker5 = ({r2r} 252307 1 15 6 14 13 15 0 0 0 0 778 0 0 0 0 0 0 0 0 0 )
Attacker6 = ({onelife} 252309 1 4 7 14 14 15 0 0 0 0 2755 0 0 0 0 0 0 0 0 0 )
Attacker7 = ({r2r} 252305 1 15 6 14 13 15 0 0 6527 0 0 0 0 0 0 0 0 1422 0 0 )
Attacker8 = ({onelife} 252250 1 4 7 14 14 15 0 1 0 0 0 0 0 0 0 0 0 0 0 0 )
Attacker9 = ({Voskreshaya} 252300 1 6 9 13 11 14 0 0 0 0 0 0 0 0 0 0 0 0 0 1341 )
Attacker10 = ({onelife} 252308 1 4 7 14 14 15 0 0 7000 0 0 0 0 0 0 0 0 1400 0 0 )
Attacker11 = ({OtellO} 252351 1 2 10 13 13 13 0 0 0 0 4342 0 0 0 0 0 0 0 0 0 )
Attacker12 = ({onelife} 252311 1 4 7 14 14 15 2510 0 0 0 0 0 0 0 0 0 0 0 0 0 )
Attacker13 = ({r2r} 252306 1 15 6 14 13 15 0 0 0 0 0 0 0 0 0 0 0 0 0 848 )
Defender0 = ({ilk} 10336 1 14 5 14 15 15 956 927 12394 657 1268 1045 3 1587 23 14 0 898 1 2108 92 0 0 0 0 0 0 0 )";

// Simple combat (cruisers vs. a bunch of light fighters)
/*
$source = "Rapidfire = 1
FID = 30
DID = 0
Attackers = 1
Defenders = 1
Attacker0 = ({Attacker0} 8134 4 268 9 0 0 0 0 0 0 0 333 0 0 0 0 0 0 0 0 0 )
Defender0 = ({Defender0} 3270 3 119 4 0 0 0 0 0 500 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 )";
*/

    $res = BattleEngine ( $source );

    echo "<br/><br/>Result:<br/>";
    echo "<pre>";
    print_r ( $res );
    echo "</pre>";

    $endtime = microtime(true);
    $allocated_after = memory_get_usage();

    printf("Page loaded in %f seconds. Allocated before %d bytes, allocated after %d bytes, unallocated %d bytes<br/>", 
        $endtime - $starttime, 
        $allocated_before, 
        $allocated_after,
        $allocated_after - $allocated_before );

    printf ("Exploded units: %d<br/>", $exploded_counter);

?>

</div>
<!-- END CONTENT AREA -->

</body>
</html>

<?php

}   // BattleDebug()

if ($battle_debug) {
    BattleDebug();
}

?>