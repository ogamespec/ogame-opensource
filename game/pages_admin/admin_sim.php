<?php

// Admin Area: Battle Simulator.

function ParseBattleDataSource (string $source, array &$a, array &$d) : void
{
    $lines = explode("\n", $source);

    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line) || strpos($line, 'Attackers = ') === 0 || strpos($line, 'Defenders = ') === 0) {
            continue;
        }
        
        if (strpos($line, 'Attacker') === 0) {
            // Extracting the attacker's number
            $parts = explode(' = ', $line);
            $prefix = $parts[0]; // "AttackerX"
            
            // We get the number after "Attacker"
            $index = (int)substr($prefix, 8);
            
            // Processing data
            $data = $parts[1]; // "({NAME} ... )"
            $data = trim($data, '() ');
            
            // Extracting the name
            $nameEnd = strpos($data, '}');
            $name = substr($data, 1, $nameEnd - 1);
            
            // Other values
            $values = substr($data, $nameEnd + 2);
            $values = preg_split('/\s+/', trim($values));
            
            $a[$index] = [
                'oname' => $name,
                'id' => (int)$values[0],
                'g' => (int)$values[1],
                's' => (int)$values[2],
                'p' => (int)$values[3],
                GID_R_WEAPON => (int)$values[4],
                GID_R_SHIELD => (int)$values[5],
                GID_R_ARMOUR => (int)$values[6],
                'fleet' => array (
                    GID_F_SC => (int)$values[7],
                    GID_F_LC => (int)$values[8],
                    GID_F_LF => (int)$values[9],
                    GID_F_HF => (int)$values[10],
                    GID_F_CRUISER => (int)$values[11],
                    GID_F_BATTLESHIP => (int)$values[12],
                    GID_F_COLON => (int)$values[13],
                    GID_F_RECYCLER => (int)$values[14],
                    GID_F_PROBE => (int)$values[15],
                    GID_F_BOMBER => (int)$values[16],
                    GID_F_SAT => (int)$values[17],
                    GID_F_DESTRO => (int)$values[18],
                    GID_F_DEATHSTAR => (int)$values[19],
                    GID_F_BATTLECRUISER => (int)$values[20]
                )
            ];
        } elseif (strpos($line, 'Defender') === 0) {
            // Extracting the defender's number
            $parts = explode(' = ', $line);
            $prefix = $parts[0]; // "DefenderX"
            
            // We get the number after "Defender"
            $index = (int)substr($prefix, 8);
            
            // Processing data
            $data = $parts[1]; // "({NAME} ... )"
            $data = trim($data, '() ');
            
            // Extracting the name
            $nameEnd = strpos($data, '}');
            $name = substr($data, 1, $nameEnd - 1);
            
            // Other values
            $values = substr($data, $nameEnd + 2);
            $values = preg_split('/\s+/', trim($values));
            
            $d[$index] = [
                'oname' => $name,
                'id' => (int)$values[0],
                'g' => (int)$values[1],
                's' => (int)$values[2],
                'p' => (int)$values[3],
                GID_R_WEAPON => (int)$values[4],
                GID_R_SHIELD => (int)$values[5],
                GID_R_ARMOUR => (int)$values[6],
                'fleet' => array (
                    GID_F_SC => (int)$values[7],
                    GID_F_LC => (int)$values[8],
                    GID_F_LF => (int)$values[9],
                    GID_F_HF => (int)$values[10],
                    GID_F_CRUISER => (int)$values[11],
                    GID_F_BATTLESHIP => (int)$values[12],
                    GID_F_COLON => (int)$values[13],
                    GID_F_RECYCLER => (int)$values[14],
                    GID_F_PROBE => (int)$values[15],
                    GID_F_BOMBER => (int)$values[16],
                    GID_F_SAT => (int)$values[17],
                    GID_F_DESTRO => (int)$values[18],
                    GID_F_DEATHSTAR => (int)$values[19],
                    GID_F_BATTLECRUISER => (int)$values[20]
                ),
                'defense' => array (
                    GID_D_RL => (int)$values[21],
                    GID_D_LL => (int)$values[22],
                    GID_D_HL => (int)$values[23],
                    GID_D_GAUSS => (int)$values[24],
                    GID_D_ION => (int)$values[25],
                    GID_D_PLASMA => (int)$values[26],
                    GID_D_SDOME => (int)$values[27],
                    GID_D_LDOME => (int)$values[28]
                )
            ];
        }
    }
}

function getval(string $name) : string
{
    if ( $_POST[$name] != "" ) return "value=\"".$_POST[$name]."\" ";
}

function getval2(string $arr, string $id) : string
{
    if ( $_POST[$arr][$id] != 0 ) return "value=\"".$_POST[$arr][$id]."\" ";
    else return "";
}

function get_intval(string $id) : int
{
    if (isset($_POST[$id])) {
        return intval($_POST[$id]);
    }
    else {
        return 0;
    }
}

function SimBattle ( mixed $battle_source, array $a, array $d, int $rf, int $fid, int $did, bool $debug, int &$battle_result, int &$aloss, int &$dloss )
{
    global $db_prefix;
    global $GlobalUser;
    global $transportableResources;

    $unitab = LoadUniverse ();

    if (!is_null($battle_source)) {
        $a = array ();
        $d = array ();
        ParseBattleDataSource ($battle_source, $a, $d);
    }

    if ( $debug ) {
        print_r ( $a );
        echo "<br>";
        print_r ( $d );
        echo "<br><hr>";
    }

    // *** Generate source data

    $source = GenBattleSourceData ($a, $d, $rf, $fid, $did);

    if ($debug) echo "<pre>" . $source . "</pre><hr>";

    $battle = array ( 'source' => $source, 'title' => '', 'report' => '', 'date' => time() );
    $battle_id = AddDBRow ( $battle, "battledata");

    $bf = fopen ( "battledata/battle_".$battle_id.".txt", "w" );
    fwrite ( $bf, $source );
    fclose ( $bf );

    // *** Transfer data to the battle engine

    if ($unitab['php_battle']) {

        $battle_source = file_get_contents ( "battledata/battle_".$battle_id.".txt" );
        $res = BattleEngine ($battle_source);

        $bf = fopen ( "battleresult/battle_".$battle_id.".txt", "w" );
        fwrite ( $bf, serialize($res) );
        fclose ( $bf );
    }
    else {

        $arg = "\"battle_id=$battle_id\"";
        system ( $unitab['battle_engine'] . " $arg", $retval );
        if ($retval < 0) {
            Error (va("Ошибка в работе боевого движка: #1 #2", $retval, $battle_id));
        }        
    }

    // *** Process output data

    $battleres = file_get_contents ( "battleresult/battle_".$battle_id.".txt" );
    $res = unserialize($battleres);

    if ( $debug ) {
        print_r ( $battle );
        echo "<hr>";
        print_r ($res);
        echo "<hr>";
    }

    // Delete already unneeded battle data.
    $query = "DELETE FROM ".$db_prefix."battledata WHERE battle_id = $battle_id";
    dbquery ($query);

    // Repair the defenses
    $repaired = RepairDefense ( $d, $res, $unitab['defrepair'], $unitab['defrepair_delta'], false );

    // Calculate total losses
    $aloss = $dloss = 0;
    $loss = CalcLosses ( $a, $d, $res, $repaired );
    $a = $loss['a'];
    $d = $loss['d'];
    $aloss = $loss['aloss'];
    $dloss = $loss['dloss'];

    // Create the moon
    $mooncreated = false;
    $moonchance = min ( floor ( ($res['dm'] + $res['dk']) / 100000), 20 );
    if ( mt_rand (1, 100) <= $moonchance ) {
        $mooncreated = true;
    }

    if ( $res['result'] === "awon" ) $battle_result = 0;
    else if ( $res['result'] === "dwon" ) $battle_result = 1;
    else $battle_result = 2;

    // Generate battle report (in admin language)
    $captured = array ();
    foreach ($transportableResources as $i=>$rc) {
        $captured[$rc] = $i + 1;
    }
    return BattleReport ( $res, time(), $aloss, $dloss, $captured, $moonchance, $mooncreated, $repaired, $GlobalUser['lang'] );
}

function Admin_BattleSim () : void
{
    global $session;
    global $db_prefix;
    global $GlobalUser;
    global $fleetmap;
    global $defmap;
    global $rakmap;
    $defmap_norak = array_diff($defmap, $rakmap);
    global $PageError;

    $unitab = LoadUniverse ();
    $rf = $unitab['rapid'];
    $fid = $unitab['fid'];
    $did = $unitab['did'];
    $debug = false;
    $maxslot = $unitab['acs'] * $unitab['acs'];

    $BattleReport = "";
    $aloss = $dloss = 0;

    // --------------------------------------------------------------------------------------------------------------------------
    // POST request processing.
    if ( method () === "POST" && $GlobalUser['admin'] != 0 ) {
        //print_r ( $_POST );
        //echo "<hr>";

        $max_post_params = $maxslot * (3 + count($fleetmap)) + $maxslot * (3 + count($fleetmap) + count($defmap_norak)) + 6;
        if ($max_post_params > ini_get("max_input_vars")) {
            $PageError = loca("ADM_SIM_MAX_INPUT_VARS");
        }

        // Generate a list of attackers and defenders
        $a = array ();
        $d = array ();

        $anum = intval ($_POST['anum']);
        $dnum = intval ($_POST['dnum']);

        // Attackers 
        for ($i=0; $i<$anum; $i++)
        {
            if ( $_POST["a".$i."_weap"] === "" ) $_POST["a".$i."_weap"] = 0;
            if ( $_POST["a".$i."_shld"] === "" ) $_POST["a".$i."_shld"] = 0;
            if ( $_POST["a".$i."_armor"] === "" ) $_POST["a".$i."_armor"] = 0;

            $a[$i][GID_R_WEAPON] = intval ($_POST["a".$i."_weap"]);
            $a[$i][GID_R_SHIELD] = intval ($_POST["a".$i."_shld"]);
            $a[$i][GID_R_ARMOUR] = intval ($_POST["a".$i."_armor"]);
            $a[$i]['oname'] = "Attacker$i";
            $a[$i]['id'] = mt_rand(1,10000);
    
            $a[$i]['g'] = mt_rand (1, 9);
            $a[$i]['s'] = mt_rand (1, 499);
            $a[$i]['p'] = mt_rand (1, 15);

            $a[$i]['fleet'] = array ();
            foreach ( $fleetmap as $n=>$gid)
            {
                if ( !isset($_POST["a".$i."_$gid"]) ) $_POST["a".$i."_$gid"] = 0;
                $a[$i]['fleet'][$gid] = intval ($_POST["a".$i."_$gid"]);
            }
        }

        // Defenders
        for ($i=0; $i<$dnum; $i++)
        {
            if ( $_POST["d".$i."_weap"] === "" ) $_POST["d".$i."_weap"] = 0;
            if ( $_POST["d".$i."_shld"] === "" ) $_POST["d".$i."_shld"] = 0;
            if ( $_POST["d".$i."_armor"] === "" ) $_POST["d".$i."_armor"] = 0;

            $d[$i][GID_R_WEAPON] = intval ($_POST["d".$i."_weap"]);
            $d[$i][GID_R_SHIELD] = intval ($_POST["d".$i."_shld"]);
            $d[$i][GID_R_ARMOUR] = intval ($_POST["d".$i."_armor"]);
            $d[$i]['oname'] = "Defender$i";
            $d[$i]['id'] = mt_rand(1,10000);
    
            $d[$i]['g'] = mt_rand (1, 9);
            $d[$i]['s'] = mt_rand (1, 499);
            $d[$i]['p'] = mt_rand (1, 15);

            $d[$i]['fleet'] = array ();
            foreach ( $fleetmap as $n=>$gid)
            {
                if ( !isset($_POST["d".$i."_$gid"]) ) $_POST["d".$i."_$gid"] = 0;
                $d[$i]['fleet'][$gid] = intval ($_POST["d".$i."_$gid"]);
            }

            $d[$i]['defense'] = array ();
            foreach ( $defmap_norak as $n=>$gid)
            {
                if ( !isset($_POST["d".$i."_$gid"]) ) $_POST["d".$i."_$gid"] = 0;
                $d[$i]['defense'][$gid] = intval ($_POST["d".$i."_$gid"]);
            }
        }

        // Simulate the battle
        $battle_result = 0;
        if ( key_exists ('debug', $_POST) && $_POST['debug'] === "on" ) $debug = true;
        else $debug = false;
        if ( $_POST['rapid'] === "on" ) $rf = true;
        else $rf = 0;
        if ( $_POST['fid'] === "" ) $fid = 0;
        else $fid = intval ($_POST['fid']);
        if ( $_POST['did'] === "" ) $did = 0;
        else $did = intval ($_POST['did']);
        $battle_source = $_POST['battle_source'] ?? null;
        if ($battle_source === "") {
            $battle_source = null;
        }
        $BattleReport = SimBattle ( $battle_source, $a, $d, $rf, $fid, $did, $debug, $battle_result, $aloss, $dloss );
    }

    // --------------------------------------------------------------------------------------------------------------------------
    // Simulation parameter input table.

?>

<script language="JavaScript">

var maxslot = <?=$maxslot;?>;

function toint (num)
{
    if ( typeof (num) == "undefined" ) num = 0;
    return parseInt (num);
}

// Recalculate the number of attackers and defenders.
function RecalcAttackersDefendersNum ()
{
    var anum = dnum = 1;
    var fleet = [ 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 ];
    var defense = [ 401, 402, 403, 404, 405, 406, 407, 408 ];

    for ( n=0; n<maxslot; n++ )        // Attackers
    {
        sum = 0;
        for (var i in fleet) {
            value = toint (document.getElementById ( "a"+n+"_" + fleet[i] ).value);
            if ( value ) sum += value;
        }
        if ( sum > 0 ) anum = n + 1;
    }

    for ( n=0; n<maxslot; n++ )        // Defenders
    {
        sum = 0;
        for (var i in fleet) {
            value = toint (document.getElementById ( "d"+n+"_" + fleet[i] ).value);
            if ( value ) sum += value;
        }
        for (var i in defense) {
            value = toint (document.getElementById ( "d"+n+"_" + defense[i] ).value);
            if ( value ) sum += value;
        }
        if ( sum > 0 ) dnum = n + 1;
    }

    document.getElementById ( "anum" ).value = anum;
    document.getElementById ( "dnum" ).value = dnum;
    //alert ( "Attackers: " + anum + ", Defenders: " + dnum );
}

// When changing a slot - enter data from the slot array into the input cells
function OnChangeSlot (attacker)
{
    var fleet = [ 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 ];
    var defense = [ 401, 402, 403, 404, 405, 406, 407, 408 ];

    if (attacker) {
        slot = document.simForm.aslot.value - 1;
        for (var i in fleet) {
            value = toint (document.getElementById ( "a"+slot+"_" + fleet[i] ).value);
            if (value) document.getElementById ( "a_" + fleet[i] ).value = value;
            else document.getElementById ( "a_" + fleet[i] ).value = "";
        }

        value = toint (document.getElementById ( "a"+slot+"_weap" ).value);
        if (value) document.getElementById ( "a_weap" ).value = value;
        else document.getElementById ( "a_weap" ).value = "";

        value = toint (document.getElementById ( "a"+slot+"_shld" ).value);
        if (value) document.getElementById ( "a_shld" ).value = value;
        else document.getElementById ( "a_shld" ).value = "";

        value = toint (document.getElementById ( "a"+slot+"_armor" ).value);
        if (value) document.getElementById ( "a_armor" ).value = value;
        else document.getElementById ( "a_armor" ).value = "";
    }
    else {
        slot = document.simForm.dslot.value - 1;
        for (var i in fleet) {
            value = toint (document.getElementById ( "d"+slot+"_" + fleet[i] ).value);
            if ( value ) document.getElementById ( "d_" + fleet[i] ).value = value;
            else document.getElementById ( "d_" + fleet[i] ).value = "";
        }
        for (var i in defense) {
            value = toint (document.getElementById ( "d"+slot+"_" + defense[i] ).value);
            if (value) document.getElementById ( "d_" + defense[i] ).value = value;
            else document.getElementById ( "d_" + defense[i] ).value = "";
        }

        value = toint (document.getElementById ( "d"+slot+"_weap" ).value);
        if (value) document.getElementById ( "d_weap" ).value = value;
        else document.getElementById ( "d_weap" ).value = "";

        value = toint (document.getElementById ( "d"+slot+"_shld" ).value);
        if (value) document.getElementById ( "d_shld" ).value = value;
        else document.getElementById ( "d_shld" ).value = "";

        value = toint (document.getElementById ( "d"+slot+"_armor" ).value);
        if (value) document.getElementById ( "d_armor" ).value = value;
        else document.getElementById ( "d_armor" ).value = "";
    }

    RecalcAttackersDefendersNum ();
}

// When changing a fleet/defense cell - enter data from it into the array of slots
function OnChangeValue (attacker, id)
{
    if (attacker) {
        slot = document.simForm.aslot.value - 1;
        document.getElementById ( "a"+slot+"_" + id ).value = document.getElementById ( "a_" + id ).value;
    }
    else {
        slot = document.simForm.dslot.value - 1;
        document.getElementById ( "d"+slot+"_" + id ).value = document.getElementById ( "d_" + id ).value;
    }

    RecalcAttackersDefendersNum ();
}

// When a technology cell is changed - enter data from it into the array of slots
function OnChangeTechValue (attacker)
{
    if (attacker) {
        slot = document.simForm.aslot.value - 1;
        document.getElementById ( "a"+slot+"_weap" ).value = document.getElementById ( "a_weap" ).value;
        document.getElementById ( "a"+slot+"_shld" ).value = document.getElementById ( "a_shld" ).value;
        document.getElementById ( "a"+slot+"_armor" ).value = document.getElementById ( "a_armor" ).value;
    }
    else {
        slot = document.simForm.dslot.value - 1;
        document.getElementById ( "d"+slot+"_weap" ).value = document.getElementById ( "d_weap" ).value;
        document.getElementById ( "d"+slot+"_shld" ).value = document.getElementById ( "d_shld" ).value;
        document.getElementById ( "d"+slot+"_armor" ).value = document.getElementById ( "d_armor" ).value;
    }
}

RecalcAttackersDefendersNum ();

</script>

<?=AdminPanel();?>

<table cellpadding=0 cellspacing=0>
<form name="simForm" action="index.php?page=admin&session=<?=$session;?>&mode=BattleSim" method="POST" >

<input type="hidden" id="anum" name="anum" value="1" />
<input type="hidden" id="dnum" name="dnum" value="1" />

<tr>        <td class=c><?=loca("ADM_SIM_ATTACKER");?></td>                <td class=c><?=loca("ADM_SIM_DEFENDER");?></td>  </tr>

<tr> 
<td> 
    <?=loca("ADM_SIM_WEAP");?> <input id="a_weap" size=2  onKeyUp="OnChangeTechValue(1);"  value="<?=get_intval("a0_weap");?>" > 
    <?=loca("ADM_SIM_SHIELD");?> <input id="a_shld" size=2  onKeyUp="OnChangeTechValue(1);"  value="<?=get_intval("a0_shld");?>" > 
    <?=loca("ADM_SIM_ARMOUR");?> <input id="a_armor" size=2  onKeyUp="OnChangeTechValue(1);"  value="<?=get_intval("a0_armor");?>" ></td> 
<td> 
    <?=loca("ADM_SIM_WEAP");?> <input id="d_weap" size=2  onKeyUp="OnChangeTechValue(0);"  value="<?=get_intval("d0_weap");?>" > 
    <?=loca("ADM_SIM_SHIELD");?> <input id="d_shld" size=2  onKeyUp="OnChangeTechValue(0);"  value="<?=get_intval("d0_shld");?>" > 
    <?=loca("ADM_SIM_ARMOUR");?> <input id="d_armor" size=2  onKeyUp="OnChangeTechValue(0);"  value="<?=get_intval("d0_armor");?>" ></td> 
</tr>

        <tr> <th valign=top>
        <table>
<?php

    echo "<tr><td class=c><b>".loca("ADM_SIM_FLEET")."</b></td> ";
    if ( $maxslot > 0) {
        echo "<td>".loca("ADM_SIM_SLOT")." <select name=\"aslot\" onchange=\"OnChangeSlot(1);\">\n";
        for ( $n=1; $n<=$maxslot; $n++) echo "<option value=\"$n\">$n</option>\n";
        echo "</select> </td> ";
    }
    echo " </tr>\n";
    foreach ($fleetmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input id="a_<?=$gid;?>" size=5  onKeyUp="OnChangeValue(1, <?=$gid;?>);" value="<?=get_intval("a0_$gid");?>" > </td> </tr>
<?php
    }

?>

<tr><td colspan=2> 
<table>
<tr><td class=c colspan=2><?=loca("ADM_SIM_SETTINGS");?></td></tr>
<tr><td><?=loca("ADM_SIM_DEBUG");?></td><td><input type="checkbox" name="debug" <?php if($debug) echo "checked"; ?> ></td></tr>
<tr><td><?=loca("ADM_SIM_RAPIDFIRE");?></td><td><input type="checkbox" name="rapid" <?php if($rf) echo "checked"; ?> ></td></tr>
<tr><td><?=loca("ADM_SIM_FID");?></td><td><input name="fid" size=3 value="<?=$fid;?>"> </td></tr>
<tr><td><?=loca("ADM_SIM_DID");?></td><td><input name="did" size=3 value="<?=$did;?>"></td></tr>
</table>
</td></tr>

        </table>
        </th>

        <th valign=top>
        <table>
<?php

    echo "<tr><td class=c><b>".loca("ADM_SIM_FLEET")."</b></td>";
    if ( $maxslot > 0) {
        echo "<td>".loca("ADM_SIM_SLOT")." <select name=\"dslot\" onchange=\"OnChangeSlot(0);\">\n";
        for ( $n=1; $n<=$maxslot; $n++) echo "<option value=\"$n\">$n</option>\n";
        echo "</select> </td> ";
    }
    echo "</tr>\n";
    foreach ($fleetmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input id="d_<?=$gid;?>" size=5 onKeyUp="OnChangeValue(0, <?=$gid;?>);" value="<?=get_intval("d0_$gid");?>" > </td> </tr>
<?php
    }


    echo "<tr><td class=c><b>".loca("ADM_SIM_DEFENSE")."</b></td></tr>\n";
    foreach ($defmap_norak as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input id="d_<?=$gid;?>" size=5 onKeyUp="OnChangeValue(0, <?=$gid;?>);" value="<?=get_intval("d0_$gid");?>" > </td> </tr>
<?php
    }
?>
        </table>
        </th></tr>

<tr><td colspan=2> 
<table>
<tr><td class=c colspan=2><?=loca("ADM_SIM_BATTLE_SOURCE");?></td></tr>
<tr><td><textarea id="battle_source" name="battle_source"></textarea></td></tr>
</table>
</td></tr>

<tr><td colspan=2><center><input type="submit" value="<?=loca("ADM_SIM_SUBMIT");?>"></center></td></tr>

<?php
    for ( $n=0; $n<$maxslot; $n++ )
    {
        foreach ($fleetmap as $i=>$gid) { 
            $num = get_intval("a".$n."_$gid");
            echo "<input type=\"hidden\" id=\"a".$n."_$gid\" name=\"a".$n."_$gid\" value=\"" . $num . "\"  /> \n";
        }
        foreach ($fleetmap as $i=>$gid) {
            $num = get_intval("d".$n."_$gid");
            echo "<input type=\"hidden\" id=\"d".$n."_$gid\" name=\"d".$n."_$gid\" value=\"" . $num . "\"  /> \n";
        }
        foreach ($defmap_norak as $i=>$gid) {
            $num = get_intval("d".$n."_$gid");
            echo "<input type=\"hidden\" id=\"d".$n."_$gid\" name=\"d".$n."_$gid\" value=\"" . $num . "\"  /> \n";
        }
        echo "<input type=\"hidden\" id=\"a".$n."_weap\" name=\"a".$n."_weap\" size=2 value=\""   . get_intval ("a".$n."_weap")  . "\"  /> ";
        echo "<input type=\"hidden\" id=\"a".$n."_shld\" name=\"a".$n."_shld\" size=2 value=\""   . get_intval ("a".$n."_shld")  . "\"  /> ";
        echo "<input type=\"hidden\" id=\"a".$n."_armor\" name=\"a".$n."_armor\" size=2 value=\"" . get_intval ("a".$n."_armor") . "\"  /> \n";
        echo "<input type=\"hidden\" id=\"d".$n."_weap\" name=\"d".$n."_weap\" size=2 value=\""   . get_intval ("d".$n."_weap")  . "\"  /> ";
        echo "<input type=\"hidden\" id=\"d".$n."_shld\" name=\"d".$n."_shld\" size=2 value=\""   . get_intval ("d".$n."_shld")  . "\"  /> ";
        echo "<input type=\"hidden\" id=\"d".$n."_armor\" name=\"d".$n."_armor\" size=2 value=\"" . get_intval ("d".$n."_armor") . "\"  /> \n";
    }
?>

</form>
</table>

<?php
    if ( $BattleReport !== "" ) {
        loca_add ("fleetmsg", $GlobalUser['lang']);
        $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );
        $bericht = SendMessage ( $GlobalUser['player_id'], 
            loca_lang("FLEET_MESSAGE_FROM", $GlobalUser['lang']),
            loca_lang("FLEET_MESSAGE_BATTLE", $GlobalUser['lang']),
            $BattleReport, MTYP_BATTLE_REPORT_TEXT );
        MarkMessage ( $GlobalUser['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster('index.php?page=bericht&session=$session&bericht=$bericht', 'Bericht_Kampf');\" ><span class=\"".$a_result[$battle_result]."\">" .
            loca_lang("FLEET_MESSAGE_BATTLE", $GlobalUser['lang']) .
            " [".$d[0]['g'].":".$d[0]['s'].":".$d[0]['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        echo "$subj<br>";
    }
?>

<?php

}

?>