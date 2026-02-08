<?php

// Admin Area: Battle Simulator.

class Admin_BattleSim extends Page {

    private int $max_round = BATTLE_MAX_ROUND;
    private string $BattleReport = "";
    private int $maxslot;
    private bool $rf;
    private int $fid;
    private int $did;
    private bool $debug = false;
    private int $aloss = 0;
    private int $dloss = 0;
    private int $battle_result;
    private array $a;
    private array $d;

    public function controller () : bool {

        global $GlobalUni;
        global $GlobalUser;
        global $PageError;
        global $fleetmap;
        global $defmap;
        global $rakmap;
        $defmap_norak = array_diff($defmap, $rakmap);

        $unitab = $GlobalUni;
        $this->rf = $unitab['rapid'];
        $this->fid = $unitab['fid'];
        $this->did = $unitab['did'];
        $this->maxslot = $unitab['acs'] * $unitab['acs'];

        // POST request processing.
        if ( method () === "POST" && $GlobalUser['admin'] != 0 ) {
            //print_r ( $_POST );
            //echo "<hr>";

            $max_post_params = $this->maxslot * (3 + count($fleetmap)) + $this->maxslot * (3 + count($fleetmap) + count($defmap_norak)) + 6;
            if ($max_post_params > ini_get("max_input_vars")) {
                $PageError = loca("ADM_SIM_MAX_INPUT_VARS");
            }

            // Generate a list of attackers and defenders
            $this->a = array ();
            $this->d = array ();

            $anum = intval ($_POST['anum']);
            $dnum = intval ($_POST['dnum']);

            // Attackers 
            for ($i=0; $i<$anum; $i++)
            {
                if ( $_POST["a".$i."_weap"] === "" ) $_POST["a".$i."_weap"] = 0;
                if ( $_POST["a".$i."_shld"] === "" ) $_POST["a".$i."_shld"] = 0;
                if ( $_POST["a".$i."_armor"] === "" ) $_POST["a".$i."_armor"] = 0;

                $this->a[$i][GID_R_WEAPON] = intval ($_POST["a".$i."_weap"]);
                $this->a[$i][GID_R_SHIELD] = intval ($_POST["a".$i."_shld"]);
                $this->a[$i][GID_R_ARMOUR] = intval ($_POST["a".$i."_armor"]);
                $this->a[$i]['oname'] = "Attacker$i";
                $this->a[$i]['id'] = mt_rand(1,10000);
                $this->a[$i]['pf'] = BATTLE_PTCP_VIRTUAL;
        
                $this->a[$i]['g'] = mt_rand (1, 9);
                $this->a[$i]['s'] = mt_rand (1, 499);
                $this->a[$i]['p'] = mt_rand (1, 15);

                $this->a[$i]['units'] = array ();
                foreach ( $fleetmap as $n=>$gid)
                {
                    if ( !isset($_POST["a".$i."_$gid"]) ) $_POST["a".$i."_$gid"] = 0;
                    $this->a[$i]['units'][$gid] = intval ($_POST["a".$i."_$gid"]);
                }
            }

            // Defenders
            for ($i=0; $i<$dnum; $i++)
            {
                if ( $_POST["d".$i."_weap"] === "" ) $_POST["d".$i."_weap"] = 0;
                if ( $_POST["d".$i."_shld"] === "" ) $_POST["d".$i."_shld"] = 0;
                if ( $_POST["d".$i."_armor"] === "" ) $_POST["d".$i."_armor"] = 0;

                $this->d[$i][GID_R_WEAPON] = intval ($_POST["d".$i."_weap"]);
                $this->d[$i][GID_R_SHIELD] = intval ($_POST["d".$i."_shld"]);
                $this->d[$i][GID_R_ARMOUR] = intval ($_POST["d".$i."_armor"]);
                $this->d[$i]['oname'] = "Defender$i";
                $this->d[$i]['id'] = mt_rand(1,10000);
                $this->d[$i]['pf'] = BATTLE_PTCP_VIRTUAL;
        
                $this->d[$i]['g'] = mt_rand (1, 9);
                $this->d[$i]['s'] = mt_rand (1, 499);
                $this->d[$i]['p'] = mt_rand (1, 15);

                $this->d[$i]['units'] = array ();
                foreach ( $fleetmap as $n=>$gid)
                {
                    if ( !isset($_POST["d".$i."_$gid"]) ) $_POST["d".$i."_$gid"] = 0;
                    $this->d[$i]['units'][$gid] = intval ($_POST["d".$i."_$gid"]);
                }

                foreach ( $defmap_norak as $n=>$gid)
                {
                    if ( !isset($_POST["d".$i."_$gid"]) ) $_POST["d".$i."_$gid"] = 0;
                    $this->d[$i]['units'][$gid] = intval ($_POST["d".$i."_$gid"]);
                }
            }

            // Simulate the battle
            $this->battle_result = BATTLE_RESULT_AWON;
            if ( key_exists ('debug', $_POST) && $_POST['debug'] === "on" ) $this->debug = true;
            else $this->debug = false;
            if ( $_POST['rapid'] === "on" ) $this->rf = true;
            else $this->rf = 0;
            if ( $_POST['fid'] === "" ) $this->fid = 0;
            else $this->fid = intval ($_POST['fid']);
            if ( $_POST['did'] === "" ) $this->did = 0;
            else $this->did = intval ($_POST['did']);
            if ( $_POST['max_round'] === "" ) $this->max_round = BATTLE_MAX_ROUND;
            else $this->max_round = intval ($_POST['max_round']);
            $battle_source = $_POST['battle_source'] ?? null;
            if ($battle_source === "") {
                $battle_source = null;
            }
            $this->BattleReport = $this->SimBattle ( $battle_source, $this->a, $this->d, $this->rf, $this->fid, $this->did, $this->max_round, $this->debug, $this->battle_result, $this->aloss, $this->dloss );
        }

        return true;
    }

    public function view () : void {

        global $GlobalUser;
        global $session;
        global $fleetmap;
        global $defmap;
        global $rakmap;
        $defmap_norak = array_diff($defmap, $rakmap);        

    // --------------------------------------------------------------------------------------------------------------------------
    // Simulation parameter input table.

?>

<script language="JavaScript">

var maxslot = <?=$this->maxslot;?>;

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

<table cellpadding=0 cellspacing=0>
<form name="simForm" action="index.php?page=admin&session=<?=$session;?>&mode=BattleSim" method="POST" >

<input type="hidden" id="anum" name="anum" value="1" />
<input type="hidden" id="dnum" name="dnum" value="1" />

<tr>        <td class=c><?=loca("ADM_SIM_ATTACKER");?></td>                <td class=c><?=loca("ADM_SIM_DEFENDER");?></td>  </tr>

<tr> 
<td> 
    <?=loca("ADM_SIM_WEAP");?> <input id="a_weap" size=2  onKeyUp="OnChangeTechValue(1);"  value="<?=$this->get_intval("a0_weap");?>" > 
    <?=loca("ADM_SIM_SHIELD");?> <input id="a_shld" size=2  onKeyUp="OnChangeTechValue(1);"  value="<?=$this->get_intval("a0_shld");?>" > 
    <?=loca("ADM_SIM_ARMOUR");?> <input id="a_armor" size=2  onKeyUp="OnChangeTechValue(1);"  value="<?=$this->get_intval("a0_armor");?>" ></td> 
<td> 
    <?=loca("ADM_SIM_WEAP");?> <input id="d_weap" size=2  onKeyUp="OnChangeTechValue(0);"  value="<?=$this->get_intval("d0_weap");?>" > 
    <?=loca("ADM_SIM_SHIELD");?> <input id="d_shld" size=2  onKeyUp="OnChangeTechValue(0);"  value="<?=$this->get_intval("d0_shld");?>" > 
    <?=loca("ADM_SIM_ARMOUR");?> <input id="d_armor" size=2  onKeyUp="OnChangeTechValue(0);"  value="<?=$this->get_intval("d0_armor");?>" ></td> 
</tr>

        <tr> <th valign=top>
        <table>
<?php

    echo "<tr><td class=c><b>".loca("ADM_SIM_FLEET")."</b></td> ";
    if ( $this->maxslot > 0) {
        echo "<td>".loca("ADM_SIM_SLOT")." <select name=\"aslot\" onchange=\"OnChangeSlot(1);\">\n";
        for ( $n=1; $n<=$this->maxslot; $n++) echo "<option value=\"$n\">$n</option>\n";
        echo "</select> </td> ";
    }
    echo " </tr>\n";
    foreach ($fleetmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input id="a_<?=$gid;?>" size=5  onKeyUp="OnChangeValue(1, <?=$gid;?>);" value="<?=$this->get_intval("a0_$gid");?>" > </td> </tr>
<?php
    }

?>

<tr><td colspan=2> 
<table>
<tr><td class=c colspan=2><?=loca("ADM_SIM_SETTINGS");?></td></tr>
<tr><td><?=loca("ADM_SIM_DEBUG");?></td><td><input type="checkbox" name="debug" <?php if($this->debug) echo "checked"; ?> ></td></tr>
<tr><td><?=loca("ADM_SIM_RAPIDFIRE");?></td><td><input type="checkbox" name="rapid" <?php if($this->rf) echo "checked"; ?> ></td></tr>
<tr><td><?=loca("ADM_SIM_FID");?></td><td><input name="fid" size=3 value="<?=$this->fid;?>"> </td></tr>
<tr><td><?=loca("ADM_SIM_DID");?></td><td><input name="did" size=3 value="<?=$this->did;?>"></td></tr>
<tr><td><?=loca("ADM_SIM_MAX_ROUND");?></td><td><input name="max_round" size=3 value="<?=$this->max_round;?>"></td></tr>
</table>
</td></tr>

        </table>
        </th>

        <th valign=top>
        <table>
<?php

    echo "<tr><td class=c><b>".loca("ADM_SIM_FLEET")."</b></td>";
    if ( $this->maxslot > 0) {
        echo "<td>".loca("ADM_SIM_SLOT")." <select name=\"dslot\" onchange=\"OnChangeSlot(0);\">\n";
        for ( $n=1; $n<=$this->maxslot; $n++) echo "<option value=\"$n\">$n</option>\n";
        echo "</select> </td> ";
    }
    echo "</tr>\n";
    foreach ($fleetmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input id="d_<?=$gid;?>" size=5 onKeyUp="OnChangeValue(0, <?=$gid;?>);" value="<?=$this->get_intval("d0_$gid");?>" > </td> </tr>
<?php
    }


    echo "<tr><td class=c><b>".loca("ADM_SIM_DEFENSE")."</b></td></tr>\n";
    foreach ($defmap_norak as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input id="d_<?=$gid;?>" size=5 onKeyUp="OnChangeValue(0, <?=$gid;?>);" value="<?=$this->get_intval("d0_$gid");?>" > </td> </tr>
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
        for ( $n=0; $n<$this->maxslot; $n++ )
        {
            foreach ($fleetmap as $i=>$gid) { 
                $num = $this->get_intval("a".$n."_$gid");
                echo "<input type=\"hidden\" id=\"a".$n."_$gid\" name=\"a".$n."_$gid\" value=\"" . $num . "\"  /> \n";
            }
            foreach ($fleetmap as $i=>$gid) {
                $num = $this->get_intval("d".$n."_$gid");
                echo "<input type=\"hidden\" id=\"d".$n."_$gid\" name=\"d".$n."_$gid\" value=\"" . $num . "\"  /> \n";
            }
            foreach ($defmap_norak as $i=>$gid) {
                $num = $this->get_intval("d".$n."_$gid");
                echo "<input type=\"hidden\" id=\"d".$n."_$gid\" name=\"d".$n."_$gid\" value=\"" . $num . "\"  /> \n";
            }
            echo "<input type=\"hidden\" id=\"a".$n."_weap\" name=\"a".$n."_weap\" size=2 value=\""   . $this->get_intval ("a".$n."_weap")  . "\"  /> ";
            echo "<input type=\"hidden\" id=\"a".$n."_shld\" name=\"a".$n."_shld\" size=2 value=\""   . $this->get_intval ("a".$n."_shld")  . "\"  /> ";
            echo "<input type=\"hidden\" id=\"a".$n."_armor\" name=\"a".$n."_armor\" size=2 value=\"" . $this->get_intval ("a".$n."_armor") . "\"  /> \n";
            echo "<input type=\"hidden\" id=\"d".$n."_weap\" name=\"d".$n."_weap\" size=2 value=\""   . $this->get_intval ("d".$n."_weap")  . "\"  /> ";
            echo "<input type=\"hidden\" id=\"d".$n."_shld\" name=\"d".$n."_shld\" size=2 value=\""   . $this->get_intval ("d".$n."_shld")  . "\"  /> ";
            echo "<input type=\"hidden\" id=\"d".$n."_armor\" name=\"d".$n."_armor\" size=2 value=\"" . $this->get_intval ("d".$n."_armor") . "\"  /> \n";
        }
?>

</form>
</table>

<?php
        if ( $this->BattleReport !== "" ) {
            loca_add ("fleetmsg", $GlobalUser['lang']);
            $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );
            $bericht = SendMessage ( $GlobalUser['player_id'], 
                loca_lang("FLEET_MESSAGE_FROM", $GlobalUser['lang']),
                loca_lang("FLEET_MESSAGE_BATTLE", $GlobalUser['lang']),
                $this->BattleReport, MTYP_BATTLE_REPORT_TEXT );
            MarkMessage ( $GlobalUser['player_id'], $bericht );
            $subj = "<a href=\"#\" onclick=\"fenster('index.php?page=bericht&session=$session&bericht=$bericht', 'Bericht_Kampf');\" ><span class=\"".$a_result[$this->battle_result]."\">" .
                loca_lang("FLEET_MESSAGE_BATTLE", $GlobalUser['lang']) .
                " [".$this->d[0]['g'].":".$this->d[0]['s'].":".$this->d[0]['p']."] (V:".nicenum($this->dloss).",A:".nicenum($this->aloss).")</span></a>";
            echo "$subj<br>";
        }

    }

    private function ParseBattleDataSource (string $source, array &$a, array &$d) : void
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
                $values = preg_split('/\s+/', trim($parts[1]));

                $a[$index]['oname'] = "Attacker$index";
                $a[$index]['id'] = mt_rand(1,10000);
                $a[$index]['pf'] = BATTLE_PTCP_VIRTUAL;
                $a[$index]['g'] = mt_rand (1, 9);
                $a[$index]['s'] = mt_rand (1, 499);
                $a[$index]['p'] = mt_rand (1, 15);

                $pc = 0;
                $a[$index][GID_R_WEAPON] = (float)$values[$pc++];
                $a[$index][GID_R_SHIELD] = (float)$values[$pc++];
                $a[$index][GID_R_ARMOUR] = (float)$values[$pc++];

                $num_gids = count($values) - $pc;
                if ($num_gids % 2 != 0) continue;
                $num_gids /= 2;

                for ($i=0; $i<$num_gids; $i++) {
                    $gid = (int)$values[$pc++];
                    $count = (int)$values[$pc++];
                    $a[$index]['units'][$gid] = $count;
                }

            } elseif (strpos($line, 'Defender') === 0) {
                // Extracting the defender's number
                $parts = explode(' = ', $line);
                $prefix = $parts[0]; // "DefenderX"
                
                // We get the number after "Defender"
                $index = (int)substr($prefix, 8);
                
                // Processing data
                $values = preg_split('/\s+/', trim($parts[1]));
                
                $d[$index]['oname'] = "Defender$index";
                $d[$index]['id'] = mt_rand(1,10000);
                $d[$index]['pf'] = BATTLE_PTCP_VIRTUAL;
                $d[$index]['g'] = mt_rand (1, 9);
                $d[$index]['s'] = mt_rand (1, 499);
                $d[$index]['p'] = mt_rand (1, 15);

                $pc = 0;
                $d[$index][GID_R_WEAPON] = (float)$values[$pc++];
                $d[$index][GID_R_SHIELD] = (float)$values[$pc++];
                $d[$index][GID_R_ARMOUR] = (float)$values[$pc++];

                $num_gids = count($values) - $pc;
                if ($num_gids % 2 != 0) continue;
                $num_gids /= 2;

                for ($i=0; $i<$num_gids; $i++) {
                    $gid = (int)$values[$pc++];
                    $count = (int)$values[$pc++];
                    $d[$index]['units'][$gid] = $count;
                }

            }
        }
    }

    private function get_intval(string $id) : int
    {
        if (isset($_POST[$id])) {
            return intval($_POST[$id]);
        }
        else {
            return 0;
        }
    }

    private function SimBattle ( mixed $battle_source, array $a, array $d, int $rf, int $fid, int $did, int $max_round, bool $debug, int &$battle_result, int &$aloss, int &$dloss ) : string
    {
        global $db_prefix;
        global $GlobalUni;
        global $GlobalUser;
        global $transportableResources;

        $unitab = $GlobalUni;

        if (!is_null($battle_source)) {
            $a = array ();
            $d = array ();
            $this->ParseBattleDataSource ($battle_source, $a, $d);
        }

        if ( $debug ) {
            print_r ( $a );
            echo "<br>";
            print_r ( $d );
            echo "<br><hr>";
        }

        // *** Generate source data

        $source = GenBattleSourceData ($a, $d, $rf, $max_round);

        if ($debug) echo "<pre>" . $source . "</pre><hr>";

        $battle = array ( 'source' => $source, 'title' => '', 'report' => '', 'date' => time() );
        $battle_id = AddDBRow ( $battle, "battledata");

        $res = ExecuteBattle ($unitab, $battle_id, $source, $a, $d);

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
        $loss = CalcLosses ( $a, $d, $res, $repaired );
        $aloss = $loss['aloss'];
        $dloss = $loss['dloss'];

        // Calc debris drop
        CalcDebris ( $a, $d, $res, $repaired, $fid, $did );
        $debris = GetDebrisTotal ($a, $d);
        $debris_total = 0;
        foreach ($debris as $rc=>$amount) {
            $debris_total += $amount;
        }

        // Create the moon
        $mooncreated = false;
        $moonchance = min ( floor ( $debris_total / 100000), 20 );
        if ( mt_rand (1, 100) <= $moonchance ) {
            $mooncreated = true;
        }

        if ( $res['result'] === "awon" ) $battle_result = BATTLE_RESULT_AWON;
        else if ( $res['result'] === "dwon" ) $battle_result = BATTLE_RESULT_DWON;
        else $battle_result = BATTLE_RESULT_DRAW;

        // Generate battle report (in admin language)
        $captured = array ();
        foreach ($transportableResources as $i=>$rc) {
            $captured[$rc] = $i + 1;
        }
        return BattleReport ( $res, time(), $loss, $captured, $moonchance, $mooncreated, $repaired, $debris, $GlobalUser['lang'] );
    }
}

?>