<?php

// ========================================================================================
// Боевой симулятор.

function SimBattle ( $a, $d, $rf, $fid, $did, $debug, &$battle_result, &$aloss, &$dloss )
{
    global $db_prefix;
    global $GlobalUni;

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $unitab = LoadUniverse ();

    if ( $debug ) {
        print_r ( $a );
        echo "<br>";
        print_r ( $d );
        echo "<br><hr>";
    }

    // *** Сгенерировать исходные данные

    $source .= "Rapidfire = $rf\n";
    $source .= "FID = $fid\n";
    $source .= "DID = $did\n";

    $anum = count ($a);
    $dnum = count ($d);

    $source .= "Attackers = $anum\n";
    $source .= "Defenders = $dnum\n";

    for ( $n=0; $n<$anum; $n++)
    {
        $source .= "Attacker$n = (<Attacker$n> ".mt_rand(1,10000)." ".$a[$n]['g']." ".$a[$n]['s']." ".$a[$n]['p']." ";
        $source .= $a[$n]['r109'] . " " . $a[$n]['r110'] . " " . $a[$n]['r111'] . " ";
        foreach ($fleetmap as $i=>$gid) $source .= $a[$n]['fleet'][$gid] . " ";
        $source .= ")\n";
    }
    for ( $n=0; $n<$dnum; $n++)
    {
        $source .= "Defender$n = (<Defender$n> ".mt_rand(1,10000)." ".$d[$n]['g']." ".$d[$n]['s']." ".$d[$n]['p']." ";
        $source .= $d[$n]['r109'] . " " . $d[$n]['r110'] . " " . $d[$n]['r111'] . " ";
        foreach ($fleetmap as $i=>$gid) $source .= $d[$n]['fleet'][$gid] . " ";
        foreach ($defmap as $i=>$gid) $source .= $d[$n]['defense'][$gid] . " ";
        $source .= ")\n";
    }

    if ($debug) echo $source . "<hr>";

    $battle = array ( $battle_id, $source, '', '', time() );
    $battle_id = AddDBRow ( $battle, "battledata");

    $bf = fopen ( "battledata/battle_".$battle_id.".txt", "w" );
    fwrite ( $bf, $source );
    fclose ( $bf );

    // *** Передать данные боевому движку

    $arg = "\"battle_id=$battle_id\"";
    system ( $unitab['battle_engine'] . " $arg" );

    // *** Обработать выходные данные

    $battleres = file_get_contents ( "battleresult/battle_".$battle_id.".txt" );
    $res = unserialize($battleres);

    if ( $debug ) {
        print_r ( $battle );
        echo "<hr>";
        print_r ($res);
        echo "<hr>";
    }

    // Удалить уже ненужные боевые данные.
    $query = "DELETE FROM ".$db_prefix."battledata WHERE battle_id = $battle_id";
    dbquery ($query);

    // Восстановить оборону
    $repaired = RepairDefense ( $d, $res, $unitab['defrepair'], $unitab['defrepair_delta'], false );

    // Рассчитать общие потери
    $aloss = $dloss = 0;
    $loss = CalcLosses ( $a, $d, $res, $repaired );
    $a = $loss['a'];
    $d = $loss['d'];
    $aloss = $loss['aloss'];
    $dloss = $loss['dloss'];

    // Создать луну
    $mooncreated = false;
    $moonchance = min ( floor ( ($res['dm'] + $res['dk']) / 100000), 20 );
    if ( mt_rand (1, 100) <= $moonchance ) {
        $mooncreated = true;
    }

    if ( $res['result'] === "awon" ) $battle_result = 0;
    else if ( $res['result'] === "dwon" ) $battle_result = 1;
    else $battle_result = 2;

    // Сгенерировать боевой доклад.
    loca_add ( "battlereport", $GlobalUni['lang'] );
    loca_add ( "technames", $GlobalUni['lang'] );
    return BattleReport ( $res, time(), $aloss, $dloss, 1, 2, 3, $moonchance, $mooncreated, $repaired );
}

function Admin_BattleSim ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $unitab = LoadUniverse ();
    $rf = $unitab['rapid'];
    $fid = $unitab['fid'];
    $did = $unitab['did'];
    $debug = false;
    $maxslot = $unitab['acs'] * $unitab['acs'];

    $BattleReport = "";
    $aloss = $dloss = 0;

    // --------------------------------------------------------------------------------------------------------------------------
    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2) {
        //print_r ( $_POST );
        //echo "<hr>";

        // Сформировать список атакующих и обороняющихся
        $a = array ();
        $d = array ();

        $anum = intval ($_POST['anum']);
        $dnum = intval ($_POST['dnum']);

        // Атакующие 
        for ($i=0; $i<$anum; $i++)
        {
            if ( $_POST["a".$i."_weap"] === "" ) $_POST["a".$i."_weap"] = 0;
            if ( $_POST["a".$i."_shld"] === "" ) $_POST["a".$i."_shld"] = 0;
            if ( $_POST["a".$i."_armor"] === "" ) $_POST["a".$i."_armor"] = 0;

            $a[$i]['r109'] = intval ($_POST["a".$i."_weap"]);
            $a[$i]['r110'] = intval ($_POST["a".$i."_shld"]);
            $a[$i]['r111'] = intval ($_POST["a".$i."_armor"]);
            $a[$i]['oname'] = "Attacker$i";
    
            $a[$i]['g'] = mt_rand (1, 9);
            $a[$i]['s'] = mt_rand (1, 499);
            $a[$i]['p'] = mt_rand (1, 15);

            $a[$i]['fleet'] = array ();
            foreach ( $fleetmap as $n=>$gid)
            {
                if ( $_POST["a".$i."_$gid"] === "" ) $_POST["a".$i."_$gid"] = 0;
                $a[$i]['fleet'][$gid] = intval ($_POST["a".$i."_$gid"]);
            }
        }

        // Обороняющиеся
        for ($i=0; $i<$dnum; $i++)
        {
            if ( $_POST["d".$i."_weap"] === "" ) $_POST["d".$i."_weap"] = 0;
            if ( $_POST["d".$i."_shld"] === "" ) $_POST["d".$i."_shld"] = 0;
            if ( $_POST["d".$i."_armor"] === "" ) $_POST["d".$i."_armor"] = 0;

            $d[$i]['r109'] = intval ($_POST["d".$i."_weap"]);
            $d[$i]['r110'] = intval ($_POST["d".$i."_shld"]);
            $d[$i]['r111'] = intval ($_POST["d".$i."_armor"]);
            $d[$i]['oname'] = "Defender$i";
    
            $d[$i]['g'] = mt_rand (1, 9);
            $d[$i]['s'] = mt_rand (1, 499);
            $d[$i]['p'] = mt_rand (1, 15);

            $d[$i]['fleet'] = array ();
            foreach ( $fleetmap as $n=>$gid)
            {
                if ( $_POST["d".$i."_$gid"] === "" ) $_POST["d".$i."_$gid"] = 0;
                $d[$i]['fleet'][$gid] = intval ($_POST["d".$i."_$gid"]);
            }

            $d[$i]['defense'] = array ();
            foreach ( $defmap as $n=>$gid)
            {
                if ( $_POST["d".$i."_$gid"] === "" ) $_POST["d".$i."_$gid"] = 0;
                $d[$i]['defense'][$gid] = intval ($_POST["d".$i."_$gid"]);
            }
        }

        // Симулировать битву
        $battle_result = 0;
        if ( $_POST['debug'] === "on" ) $debug = true;
        else $debug = false;
        if ( $_POST['rapid'] === "on" ) $rf = true;
        else $rf = 0;
        if ( $_POST['fid'] === "" ) $fid = 0;
        else $fid = intval ($_POST['fid']);
        if ( $_POST['did'] === "" ) $did = 0;
        else $did = intval ($_POST['did']);
        $BattleReport = SimBattle ( $a, $d, $rf, $fid, $did, $debug, &$battle_result, &$aloss, &$dloss );
    }

    // --------------------------------------------------------------------------------------------------------------------------
    // Таблица ввода параметров симуляции.

    function getval($name)
    {
        if ( $_POST[$name] != "" ) return "value=\"".$_POST[$name]."\" ";
    }

    function getval2($arr, $id)
    {
        if ( $_POST[$arr][$id] != 0 ) return "value=\"".$_POST[$arr][$id]."\" ";
        else return "";
    }

?>

<script language="JavaScript">

var maxslot = <?=$maxslot;?>;

function toint (num)
{
    if ( typeof (num) == "undefined" ) num = 0;
    return parseInt (num);
}

// Пересчитать количество атакующих и обороняющихся.
function RecalcAttackersDefendersNum ()
{
    var anum = dnum = 1;
    var fleet = [ 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 ];
    var defense = [ 401, 402, 403, 404, 405, 406, 407, 408 ];

    for ( n=0; n<maxslot; n++ )        // Атакующие
    {
        sum = 0;
        for (var i in fleet) {
            value = toint (document.getElementById ( "a"+n+"_" + fleet[i] ).value);
            if ( value ) sum += value;
        }
        if ( sum > 0 ) anum = n + 1;
    }

    for ( n=0; n<maxslot; n++ )        // Обороняющиеся
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
    //alert ( "Атакующих : " + anum + ", Обороняющихся : " + dnum );
}

// При смене слота - занести данные из массива слотов в ячейки ввода
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
    }
}

// При изменении ячейки - внести данные из неё в массив слотов
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

RecalcAttackersDefendersNum ();

</script>

<?=AdminPanel();?>

<table cellpadding=0 cellspacing=0>
<form name="simForm" action="index.php?page=admin&session=<?=$session;?>&mode=BattleSim" method="POST" >

<?php
    for ( $n=0; $n<$maxslot; $n++ )
    {
        foreach ($fleetmap as $i=>$gid) echo "<input type=\"hidden\" id=\"a".$n."_$gid\" name=\"a".$n."_$gid\" value=\"0\"> \n";
        foreach ($fleetmap as $i=>$gid) echo "<input type=\"hidden\" id=\"d".$n."_$gid\" name=\"d".$n."_$gid\" value=\"0\"> \n";
        foreach ($defmap as $i=>$gid) echo "<input type=\"hidden\" id=\"d".$n."_$gid\" name=\"d".$n."_$gid\" value=\"0\"> \n";
        echo "<input type=\"hidden\" id=\"a".$n."_weap\" name=\"a".$n."_weap\" size=2 > <input type=\"hidden\" id=\"a".$n."_shld\" name=\"a".$n."_shld\" size=2 > <input type=\"hidden\" id=\"a".$n."_armor\" name=\"a".$n."_armor\" size=2 > \n";
        echo "<input type=\"hidden\" id=\"d".$n."_weap\" name=\"d".$n."_weap\" size=2 > <input type=\"hidden\" id=\"d".$n."_shld\" name=\"d".$n."_shld\" size=2 > <input type=\"hidden\" id=\"d".$n."_armor\" name=\"d".$n."_armor\" size=2 > \n";
    }
?>

<input type="hidden" id="anum" name="anum" value="1" />
<input type="hidden" id="anum" name="dnum" value="1" />

<tr>        <td class=c>Атакующий</td>                <td class=c>Оборояющийся</td>  </tr>

<tr> 
<td> Вооружение: <input id="a_weap" size=2 > Щиты: <input id="a_shld" size=2 > Броня: <input id="a_armor" size=2 ></td>   
<td> Вооружение: <input id="d_weap" size=2 > Щиты: <input id="d_shld" size=2 > Броня: <input id="d_armor" size=2 ></td>   
</tr>

        <tr> <th valign=top>
        <table>
<?php

    echo "<tr><td class=c><b>Флот</b></td> ";
    if ( $maxslot > 0) {
        echo "<td>Слот: <select name=\"aslot\" onchange=\"OnChangeSlot(1);\">\n";
        for ( $n=1; $n<=$maxslot; $n++) echo "<option value=\"$n\">$n</option>\n";
        echo "</select> </td> ";
    }
    echo " </tr>\n";
    foreach ($fleetmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input id="a_<?=$gid;?>" size=5  onKeyUp="OnChangeValue(1, <?=$gid;?>);"> </td> </tr>
<?php
    }

?>

<tr><td colspan=2> 
<table>
<tr><td class=c colspan=2>Настройки</td></tr>
<tr><td>Отладочная информация</td><td><input type="checkbox" name="debug" <? if($debug) echo "checked"; ?> ></td></tr>
<tr><td>Скорострел</td><td><input type="checkbox" name="rapid" <? if($rf) echo "checked"; ?> ></td></tr>
<tr><td>Флот в обломки</td><td><input name="fid" size=3 value="<?=$fid;?>"> </td></tr>
<tr><td>Оборона в обломки</td><td><input name="did" size=3 value="<?=$did;?>"></td></tr>
</table>
</td></tr>

        </table>
        </th>

        <th valign=top>
        <table>
<?php

    echo "<tr><td class=c><b>Флот</b></td>";
    if ( $maxslot > 0) {
        echo "<td>Слот: <select name=\"dslot\" onchange=\"OnChangeSlot(0);\">\n";
        for ( $n=1; $n<=$maxslot; $n++) echo "<option value=\"$n\">$n</option>\n";
        echo "</select> </td> ";
    }
    echo "</tr>\n";
    foreach ($fleetmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input id="d_<?=$gid;?>" size=5 onKeyUp="OnChangeValue(0, <?=$gid;?>);"> </td> </tr>
<?php
    }


    echo "<tr><td class=c><b>Оборона</b></td></tr>\n";
    foreach ($defmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input id="d_<?=$gid;?>" size=5 onKeyUp="OnChangeValue(0, <?=$gid;?>);"> </td> </tr>
<?php
    }
?>
        </table>
        </th></tr>

<tr><td colspan=2><center><input type="submit" value="Начать бой"></center></td></tr>
</form>
</table>

<?php
    if ( $BattleReport !== "" ) {
        $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );
        $bericht = SendMessage ( $GlobalUser['player_id'], "Командование флотом", "Боевой доклад", $BattleReport, 6 );
        MarkMessage ( $GlobalUser['player_id'], $bericht );
        $subj = "<a href=\"#\" onclick=\"fenster('index.php?page=bericht&session=$session&bericht=$bericht', 'Bericht_Kampf');\" ><span class=\"".$a_result[$battle_result]."\">Боевой доклад [".$d[0]['g'].":".$d[0]['s'].":".$d[0]['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        echo "$subj<br>";
    }
?>

<?php

}

?>