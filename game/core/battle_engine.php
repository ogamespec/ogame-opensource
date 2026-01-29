<?php

$battle_debug = 0;
$exploded_counter = 0;
$already_exploded_counter = 0;

// Local versions of arrays for calculations within the battle engine. We do NOT use external arrays; we parse battledata.
$RapidFireLocal = array ();
$UnitParamLocal = array ();

if (!defined('GID_MAX')) {
    define ('GID_MAX', 0xffff);     // Game object ID value must not be > this value (restriction) 
}
if (!defined('RF_MAX')) {
    define ('RF_MAX', 5000);        // Maximum rapidfire value (if > this value, then error)
}
if (!defined('RF_DICE')) {
    define ('RF_DICE', 100000);      // Number of dice faces for a rapid-fire throw (1d`RF_DICE)
}

if ($battle_debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Spare Battle Engine backend in PHP.
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
$obj = { id, id, id, ... }     -- array of units, packed 2-byte array-string
$slot = { n, n, n, ... }       -- unit slot number (for ACS), this is needed to generate a battle report to sort units into slots.
$explo = { 1, 0, 1, ... }      -- array of exploded units, after each round the exploded units are deleted and a new array $obj is formed. TODO: the data is in packed format 8 units per 1 byte
$shld = { }                    -- unit shields. before the start of each round this array is filled with maximum values (shields are charged). packed 4-byte array-string
$hull = { }                    -- current armor value given the damage for unit n. packed 4-byte array-string

To debug: just open http://localhost/game/battle_engine.php with $battle_debug = 1 set.

*/

// For debugging, convert the array-string into a readable format
function hex_array_to_text (string $arr) : string
{
    return implode(unpack("H*", $arr));
}

function get_packed_word (string &$arr, int $idx) : int
{
    return (ord($arr[4*$idx]) << 24) | 
        (ord($arr[4*$idx+1]) << 16) | 
        (ord($arr[4*$idx+2]) << 8) |
        (ord($arr[4*$idx+3]) << 0);
}

function set_packed_word (string &$arr, int $idx, float|int $val) : void
{
    $ival = (int)$val;
    $arr[4*$idx] = chr(($ival >> 24) & 0xff);
    $arr[4*$idx+1] = chr(($ival >> 16) & 0xff);
    $arr[4*$idx+2] = chr(($ival >> 8) & 0xff);
    $arr[4*$idx+3] = chr(($ival >> 0) & 0xff);
}

function get_packed_half (string &$arr, int $idx) : int
{
    return (ord($arr[2*$idx]) << 8) |
        (ord($arr[2*$idx+1]) << 0);
}

function set_packed_half (string &$arr, int $idx, int $val) : void
{
    $ival = (int)$val;
    $arr[2*$idx] = chr(($ival >> 8) & 0xff);
    $arr[2*$idx+1] = chr(($ival >> 0) & 0xff);
}

// Allocate memory for units and set initial values.
function InitBattle (array $slot, int $num, int $objs, string &$explo_arr, string &$obj_arr, string &$slot_arr, string &$hull_arr, string &$shld_arr ) : void
{
    global $UnitParamLocal;

    $ucnt = 0;
    $slot_id = 0;

    for ($i=0; $i<$num; $i++) {

        foreach ( $slot[$i]['units'] as $gid=>$amount ) {

            $hull = $UnitParamLocal[$gid][0] * 0.1 * (10+$slot[$i]['armr']) / 10;
            
            for ($obj=0; $obj<$amount; $obj++) {
                $explo_arr[$ucnt] = chr(0);
                set_packed_half ($obj_arr, $ucnt, $gid);
                $slot_arr[$ucnt] = chr($slot_id);
                set_packed_word ($hull_arr, $ucnt, $hull);
                set_packed_word ($shld_arr, $ucnt, 0);

                $ucnt++;
            }
        }

        $slot_id++;
    }
}

// Shot a => b. Returns damage.
// absorbed - the accumulator of damage absorbed by shields (for the one who is attacked, i.e. for unit "b").
function UnitShoot (
    int $a, int $b, 
    string &$aunits, string &$aslot, string &$ahull, string &$ashld, array $attackers, 
    string &$dunits, string &$dslot, string &$dhull, string &$dshld, string &$dexplo, array $defenders, 
    float|int &$absorbed ) : float|int
{
    global $UnitParamLocal;
    global $exploded_counter;
    global $already_exploded_counter;

    $a_slot_id = ord($aslot[$a]);
    $a_gid = get_packed_half ($aunits, $a);

    $b_slot_id = ord($dslot[$b]);
    $b_gid = get_packed_half ($dunits, $b);

    $apower = $UnitParamLocal[$a_gid][2] * (10 + $attackers[$a_slot_id]['weap']) / 10;

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

        $b_shieldmax = $UnitParamLocal[$b_gid][1] * (10 + $defenders[$b_slot_id]['shld']) / 10;
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

    $b_hullmax = $UnitParamLocal[$b_gid][0] * 0.1 * (10 + $defenders[$b_slot_id]['armr']) / 10;
    $b_hull = get_packed_word($dhull, $b);
    $b_shield = get_packed_word ($dshld, $b);

    if ($b_hull <= $b_hullmax * 0.7 && $b_shield == 0) {    // Blow it up and scrap it.

        if (mt_rand (0, 99) >= (($b_hull * 100) / $b_hullmax) || $b_hull == 0) {

            $dexplo[$b] = chr(1);
            $exploded_counter++;
        }
    }

    return $apower;
}

// Clean up blown up ships and defenses. Returns the number of units blown up.
function WipeExploded (int $count, string &$explo_arr, string &$obj_arr, string &$slot_arr, string &$hull_arr, string &$shld_arr) : array
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
            set_packed_half ($obj_new, $dst, get_packed_half($obj_arr, $i) );
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
function ChargeShields (array $slot, int $count, string &$explo_arr, string &$obj_arr, string &$slot_arr, string &$shld_arr) : void
{
    global $UnitParamLocal;

    for ($i=0; $i<$count; $i++) {

        if (ord($explo_arr[$i]) != 0 ) {

            set_packed_word ($shld_arr, $i, 0);
        }
        else {

            $slot_id = ord($slot_arr[$i]);
            $gid = get_packed_half ($obj_arr, $i);
            $shield_max = $UnitParamLocal[$gid][1] * (10 + $slot[$slot_id]['shld']) / 10;
            set_packed_word ($shld_arr, $i, $shield_max);
        }
    }
}

// Check the combat for a quick draw. If none of the units have armor damage, the combat ends in a quick draw.
function CheckFastDraw (
    string &$aunits, string &$aslot, string &$ahull, int $aobjs, array $attackers, 
    string &$dunits, string &$dslot, string &$dhull, int $dobjs, array $defenders) : bool
{
    global $UnitParamLocal;

    for ($i=0; $i<$aobjs; $i++) {

        $slot_id = ord($aslot[$i]);
        $gid = get_packed_half ($aunits, $i);
        $hull_max = $UnitParamLocal[$gid][0] * 0.1 * (10+$attackers[$slot_id]['armr']) / 10;

        if (get_packed_word($ahull, $i) != $hull_max) return false;
    }

    for ($i=0; $i<$dobjs; $i++) {

        $slot_id = ord($dslot[$i]);
        $gid = get_packed_half ($dunits, $i);
        $hull_max = $UnitParamLocal[$gid][0] * 0.1 * (10+$defenders[$slot_id]['armr']) / 10;

        if (get_packed_word($dhull, $i) != $hull_max) return false;
    }

    return true;
}

// Check the possibility of re-firing. Original unit IDs are used for convenience
function RapidFire (int $atyp, int $dtyp) : int
{
    global $RapidFireLocal;
    $rapidfire = 0;

    if (isset($RapidFireLocal[$atyp])) {
        foreach ($RapidFireLocal[$atyp] as $gid=>$count) {
            if ($gid == $dtyp && $count) {
                $rnd = mt_rand (1, RF_DICE);
                $cmp = RF_DICE / $count;
                $rapidfire = $rnd > $cmp ? 1 : 0;
                break;
            }
        }
    }

    return $rapidfire;
}

function DoBattle (array &$res, int $Rapidfire, int $max_round) : void
{
    global $battle_debug;
    global $already_exploded_counter;
    global $exploded_counter;

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

    $anum = count ($res['before']['attackers']);
    $dnum = count ($res['before']['defenders']);

    // Count the number of units before the battle

    $aobjs = 0;
    $dobjs = 0;

    for ($i=0; $i<$anum; $i++) {
        foreach ($res['before']['attackers'][$i]['units'] as $gid=>$amount) {
            $aobjs += $amount;
        }
    }

    for ($i=0; $i<$dnum; $i++) {
        foreach ($res['before']['defenders'][$i]['units'] as $gid=>$amount) {
            $dobjs += $amount;
        }
    }

    // Prepare arrays of units

    InitBattle ($res['before']['attackers'], $anum, $aobjs, $explo_att, $obj_att, $slot_att, $hull_att, $shld_att);
    InitBattle ($res['before']['defenders'], $dnum, $dobjs, $explo_def, $obj_def, $slot_def, $hull_def, $shld_def);

    // Rounds

    $res['rounds'] = array();

    for ($round=0; $round<$max_round; $round++) {

        $already_exploded_counter = 0;

        if ($aobjs == 0 || $dobjs == 0) break;

        // Reset stats.
        $shoots[0] = $shoots[1] = 0;
        $spower[0] = $spower[1] = 0;
        $absorbed[0] = $absorbed[1] = 0;

        // Charge shields.

        ChargeShields ($res['before']['attackers'], $aobjs, $explo_att, $obj_att, $slot_att, $shld_att);
        ChargeShields ($res['before']['defenders'], $dobjs, $explo_def, $obj_def, $slot_def, $shld_def);

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
                            $absorbed[1] );
                        $shoots[0]++;
                        $spower[0] += $apower;

                        $atyp = get_packed_half ($obj_att, $i);
                        $dtyp = get_packed_half ($obj_def, $idx);
                        if ($Rapidfire == 0) $rapidfire = 0;
                        else $rapidfire = RapidFire ($atyp, $dtyp);
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
                            $absorbed[0] );
                        $shoots[1]++;
                        $spower[1] += $apower;

                        $atyp = get_packed_half ($obj_def, $i);
                        $dtyp = get_packed_half ($obj_att, $idx);
                        if ($Rapidfire == 0) $rapidfire = 0;
                        else $rapidfire = RapidFire ($atyp, $dtyp);
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
            $r['exploded_this_round'] = $exploded_counter - $prev_exploded;
            $r['already_exploded_counter'] = $already_exploded_counter;
        }

        $r['attackers'] = array();

        for ($slot=0; $slot<$anum; $slot++) {

            $r['attackers'][$slot]['units'] = array();
            for ($i=0; $i<$aobjs; $i++) {
                if (ord($slot_att[$i]) != $slot) {
                    continue;
                }
                $obj_id = get_packed_half ($obj_att, $i);
                if (isset($r['attackers'][$slot]['units'][$obj_id])) {
                    $r['attackers'][$slot]['units'][$obj_id]++;
                }
                else {
                    $r['attackers'][$slot]['units'][$obj_id] = 1;
                }
            }
        }

        $r['defenders'] = array();

        for ($slot=0; $slot<$dnum; $slot++) {

            $r['defenders'][$slot]['units'] = array ();
            for ($i=0; $i<$dobjs; $i++) {
                if (ord($slot_def[$i]) != $slot) {
                    continue;
                }                
                $obj_id = get_packed_half ($obj_def, $i);
                if (isset($r['defenders'][$slot]['units'][$obj_id])) {
                    $r['defenders'][$slot]['units'][$obj_id]++;
                }
                else {
                    $r['defenders'][$slot]['units'][$obj_id] = 1;
                }
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

function deserialize_slot (string $str) : array
{
    global $UnitParamLocal; 
    $res = array();

    if ($str === "") Error ("BATTLE_ERROR_INSUFFICIENT_RESOURCES");
    $items = explode (" ", trim($str));    
    if (count($items) < 5) Error ("BATTLE_ERROR_PARSE_SLOT_NOT_ENOUGH");

    $pc = 0;
    $res['weap'] = (float) ($items[$pc++]);
    $res['shld'] = (float) ($items[$pc++]);
    $res['armr'] = (float) ($items[$pc++]);

    $left_items = count ($items) - $pc;
    if ($left_items % 2 != 0) Error ("BATTLE_ERROR_PARSE_SLOT_NOT_ALIGNED");
    $gids = $left_items / 2;

    $res['units'] = array ();
    for ($i=0; $i<$gids; $i++) {
        $gid = intval ($items[$pc++]);
        if ($gid > GID_MAX) {
            Error ("BATTLE_ERROR_GID_MAX");
        }
        if (!isset($UnitParamLocal[$gid])) {
            Error ("BATTLE_ERROR_GID_UNKNOWN");
        }
        $count = intval ($items[$pc++]);
        $res['units'][$gid] = $count;
    }

    return $res;
}

function ParseRFTable (string $text) : array {

    global $UnitParamLocal;
    $RapidFire = array ();

    if ($text === "") Error ("BATTLE_ERROR_INSUFFICIENT_RESOURCES");

    $v = explode (' ', $text);
    $c = count ($v);

    // The entries come in pairs.
    if ($c % 2 != 0) {
        Error ("BATTLE_ERROR_PARSE_RF_NOT_ALIGNED");
    }

    $pc = 0;
    $args_left = $c;

    while ($args_left) {

        $source_gid = intval ($v[$pc++]); $args_left--;
        if ($source_gid > GID_MAX) {
            Error ("BATTLE_ERROR_GID_MAX");
        }
        if (!isset($UnitParamLocal[$source_gid])) {
            Error ("BATTLE_ERROR_GID_UNKNOWN");
        }
        $num_targets = intval ($v[$pc++]); $args_left--;
        if ($num_targets > count($UnitParamLocal)) {
            Error ("BATTLE_ERROR_PARSE_RF_MALFORMED");
        }
        if ($args_left < ($num_targets * 2)) {
            Error ("BATTLE_ERROR_PARSE_RF_NOT_ENOUGH");
        }

        if ($num_targets != 0) {
            $to = array();

            for ($i = 0; $i < $num_targets; $i++) {

                $gid = intval ($v[$pc++]); $args_left--;
                if ($gid > GID_MAX) {
                    Error ("BATTLE_ERROR_GID_MAX");
                }
                if (!isset($UnitParamLocal[$gid])) {
                    Error ("BATTLE_ERROR_GID_UNKNOWN");
                }
                $count = intval ($v[$pc++]); $args_left--;
                if ($count > RF_MAX) {
                    Error ("BATTLE_ERROR_PARSE_RF_MALFORMED ($source_gid: $gid => $count)");
                }
                $to[$gid] = $count;
            }

            $RapidFire[$source_gid] = $to;
        }
    }

    return $RapidFire;
}

function ParseUnitParam (string $text) : array {

    $UnitParam = array ();
    if ($text === "") Error ("BATTLE_ERROR_INSUFFICIENT_RESOURCES");

    $v = explode (' ', $text);
    $c = count ($v);

    $params_per_unit = 1 + 6;
    // There must be at least 1 set of parameters
    if ($c < $params_per_unit) {
        Error ("BATTLE_ERROR_PARSE_UNIT_PARAM_NOT_ENOUGH");
    }
    // Parameters must be multiples
    if ($c % $params_per_unit != 0) {
        Error ("BATTLE_ERROR_PARSE_UNIT_PARAM_NOT_ALIGNED");
    }

    $pc = 0;
    $num_params = $c / $params_per_unit;
    for ($i = 0; $i < $num_params; $i++) {

        $gid = intval ($v[$pc++]);
        if ($gid > GID_MAX) {
            Error ("BATTLE_ERROR_GID_MAX");
        }
        if ( isset($UnitParam[$gid])) {
            Error ("BATTLE_ERROR_PARSE_UNIT_PARAM_DUPLICATED");
        }

        $unitParam = array();

        $unitParam[] = intval ($v[$pc++]);
        $unitParam[] = intval ($v[$pc++]);
        $unitParam[] = intval ($v[$pc++]);
        $unitParam[] = intval ($v[$pc++]);
        $unitParam[] = intval ($v[$pc++]);
        $unitParam[] = intval ($v[$pc++]);

        $UnitParam[$gid] = $unitParam;
    }

    return $UnitParam;
}

// Parse the input data
function ParseInput (string $source, int &$rf, int &$max_round, array &$attackers, array &$defenders) : void
{
    global $battle_debug;
    global $RapidFireLocal;
    global $UnitParamLocal;

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
    $max_round = intval ($kv['MaxRound']);
    $UnitParamLocal = ParseUnitParam ($kv['UnitParam']);
    if ($rf) {
        $RapidFireLocal = ParseRFTable ($kv['RFTab']);
    }

    if ($battle_debug) {
        echo "<pre>";
        echo "RapidFireLocal = "; print_r ($RapidFireLocal);
        echo "UnitParamLocal = "; print_r ($UnitParamLocal);
        echo "</pre>";
    }

    $anum = intval ($kv['Attackers']);
    $dnum = intval ($kv['Defenders']);

    for ($i=0; $i<$anum; $i++) {

        $attackers[$i] = deserialize_slot ($kv['Attacker' . $i]);
    }

    for ($i=0; $i<$dnum; $i++) {

        $defenders[$i] = deserialize_slot ($kv['Defender' . $i]);
    }
}

// The output is an array of battleresult, the format is similar to that of the C battle engine.
function BattleEngine (string $source) : array
{
    global $battle_debug;

    // Default battle engine settings
    $rf = 1;
    $max_round = 6;

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
    ParseInput ($source, $rf, $max_round, $res['before']['attackers'], $res['before']['defenders']);

    // **** START BATTLE ****
    DoBattle ($res, $rf, $max_round);

    return $res;
}

function BattleDebug() : void
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
$source = "MaxRound = 6
Rapidfire = 1
RFTab = 202 2 210 5 212 5 203 2 210 5 212 5 204 2 210 5 212 5 205 3 202 3 210 5 212 5 206 4 204 6 210 5 212 5 401 10 207 2 210 5 212 5 208 2 210 5 212 5 209 2 210 5 212 5 210 0 211 6 210 5 212 5 401 20 402 20 403 10 405 10 212 0 213 4 210 5 212 5 215 2 402 10 214 18 202 250 203 250 204 200 205 100 206 33 207 30 208 250 209 250 210 1250 211 25 212 1250 213 5 215 15 401 200 402 200 403 100 404 50 405 100 215 7 202 3 203 3 205 4 206 4 207 7 210 5 212 5 401 0 402 0 403 0 404 0 405 0 406 0 407 0 408 0
UnitParam = 202 4000 10 5 5000 5000 10 203 12000 25 5 25000 7500 50 204 4000 10 50 50 12500 20 205 10000 25 150 100 10000 75 206 27000 50 400 800 15000 300 207 60000 200 1000 1500 10000 500 208 30000 100 50 7500 2500 1000 209 16000 10 1 20000 2000 300 210 1000 0 0 5 100000000 1 211 75000 500 1000 500 4000 1000 212 2000 1 1 0 0 0 213 110000 500 2000 2000 5000 1000 214 9000000 50000 200000 1000000 100 1 215 70000 400 700 750 10000 250 401 2000 20 80 0 0 0 402 2000 25 100 0 0 0 403 8000 100 250 0 0 0 404 35000 200 1100 0 0 0 405 8000 500 150 0 0 0 406 100000 300 3000 0 0 0 407 20000 2000 1 0 0 0 408 100000 10000 1 0 0 0 502 8000 1 1 0 0 0 503 15000 1 12000 0 0 0
Attackers = 14
Defenders = 1
Attacker0 = 10.0 13.0 13.0 215 4613
Attacker1 = 13.0 11.0 14.0 202 5490 204 16379 207 123 213 367
Attacker2 = 14.0 13.0 15.0 202 2055
Attacker3 = 14.0 14.0 15.0 215 3100
Attacker4 = 13.0 11.0 14.0 206 5020
Attacker5 = 14.0 13.0 15.0 206 778
Attacker6 = 14.0 14.0 15.0 206 2755
Attacker7 = 14.0 13.0 15.0 204 6527 213 1422
Attacker8 = 14.0 14.0 15.0 203 1
Attacker9 = 13.0 11.0 14.0 215 1341
Attacker10 = 14.0 14.0 15.0 204 7000 213 1400
Attacker11 = 13.0 13.0 13.0 206 4342
Attacker12 = 14.0 14.0 15.0 202 2510
Attacker13 = 14.0 13.0 15.0 215 848
Defender0 = 14.0 15.0 15.0 202 956 203 927 204 12394 205 657 206 1268 207 1045 208 3 209 1587 210 23 211 14 213 898 214 1 215 2108 401 92";

// Simple combat (cruisers vs. a bunch of light fighters)
/*
$source = "MaxRound = 6
Rapidfire = 1
RFTab = 202 2 210 5 212 5 203 2 210 5 212 5 204 2 210 5 212 5 205 3 202 3 210 5 212 5 206 4 204 6 210 5 212 5 401 10 207 2 210 5 212 5 208 2 210 5 212 5 209 2 210 5 212 5 210 0 211 6 210 5 212 5 401 20 402 20 403 10 405 10 212 0 213 4 210 5 212 5 215 2 402 10 214 18 202 250 203 250 204 200 205 100 206 33 207 30 208 250 209 250 210 1250 211 25 212 1250 213 5 215 15 401 200 402 200 403 100 404 50 405 100 215 7 202 3 203 3 205 4 206 4 207 7 210 5 212 5 401 0 402 0 403 0 404 0 405 0 406 0 407 0 408 0
UnitParam = 202 4000 10 5 5000 5000 10 203 12000 25 5 25000 7500 50 204 4000 10 50 50 12500 20 205 10000 25 150 100 10000 75 206 27000 50 400 800 15000 300 207 60000 200 1000 1500 10000 500 208 30000 100 50 7500 2500 1000 209 16000 10 1 20000 2000 300 210 1000 0 0 5 100000000 1 211 75000 500 1000 500 4000 1000 212 2000 1 1 0 0 0 213 110000 500 2000 2000 5000 1000 214 9000000 50000 200000 1000000 100 1 215 70000 400 700 750 10000 250 401 2000 20 80 0 0 0 402 2000 25 100 0 0 0 403 8000 100 250 0 0 0 404 35000 200 1100 0 0 0 405 8000 500 150 0 0 0 406 100000 300 3000 0 0 0 407 20000 2000 1 0 0 0 408 100000 10000 1 0 0 0 502 8000 1 1 0 0 0 503 15000 1 12000 0 0 0
Attackers = 1
Defenders = 1
Attacker0 = 0.0 0.0 0.0 206 333
Defender0 = 0.0 0.0 0.0 204 500";
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