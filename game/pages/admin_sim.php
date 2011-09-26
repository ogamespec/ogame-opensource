<?php

// ========================================================================================
// Боевой симулятор.

function SimBattle ( $a, $d )
{
    global  $db_host, $db_user, $db_pass, $db_name, $db_prefix;

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $unitab = LoadUniverse ();

    print_r ( $a );
    echo "<br>";
    print_r ( $d );
    echo "<br>";

    // *** Сгенерировать исходные данные

    $source .= "Rapidfire = $rf\n";
    $source .= "FID = $fid\n";
    $source .= "DID = $did\n";

    $source .= "Attackers = 1\n";
    $source .= "Defenders = 1\n";

    $source .= "Attacker0 = (".mt_rand(1,10000)." ";
    $source .= $a[0]['r109'] . " " . $a[0]['r110'] . " " . $a[0]['r111'] . " ";
    foreach ($fleetmap as $i=>$gid) $source .= $a[0]['fleet'][$gid] . " ";
    $source .= ")\n";
    $source .= "Defender0 = (".mt_rand(1,10000)." ";
    $source .= $d[0]['r109'] . " " . $d[0]['r110'] . " " . $d[0]['r111'] . " ";
    foreach ($fleetmap as $i=>$gid) $source .= $d[0]['fleet'][$gid] . " ";
    foreach ($defmap as $i=>$gid) $source .= $d[0]['defense'][$gid] . " ";
    $source .= ")\n";

    //echo $source . "<hr>";

    $battle_id = IncrementDBGlobal ("nextbattle");
    $battle = array ( $battle_id, $source, '' );
    AddDBRow ( $battle, "battledata");

    // *** Передать данные боевому движку

    $arg = "\"db_host=$db_host&db_user=$db_user&db_pass=$db_pass&db_name=$db_name&db_prefix=$db_prefix&battle_id=$battle_id\"";
    system ( $unitab['battle_engine'] . " $arg" );

    // *** Обработать выходные данные

    $query = "SELECT * FROM ".$db_prefix."battledata WHERE battle_id = $battle_id";
    $result = dbquery ($query);
    if ( $result == null ) return;
    $battle = dbarray ($result);

    $res = unserialize($battle['result']);
    //print_r ($res);
    //echo "<hr>";

    // Удалить уже ненужные боевые данные.
    $query = "DELETE FROM ".$db_prefix."battledata WHERE battle_id = $battle_id";
    dbquery ($query);

    // Создать луну
    $mooncreated = false;
    $moonchance = min ( floor ( ($res['dm'] + $res['dk']) / 100000), 20 );
    if ( mt_rand (1, 100) <= $moonchance ) {
        $mooncreated = true;
    }

    // Сгенерировать боевой доклад.
    return BattleReport ( $a, $d, $res, time(), 1234, 5678, 1, 2, 3, $moonchance, $mooncreated, true );
}

function Admin_BattleSim ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

    $BattleReport = "";

    // --------------------------------------------------------------------------------------------------------------------------
    // Обработка POST-запроса.
    if ( method () === "POST" ) {
        //print_r ( $_POST );
        //echo "<hr>";

        // Сформировать список атакующих и обороняющихся
        $a = array ();
        $d = array ();

        $anum = $_POST['anum'];
        $dnum = $_POST['dnum'];

        // Атакующие 
        for ($i=0; $i<$anum; $i++)
        {
            if ( $_POST["a".$i."_weap"] === "" ) $_POST["a".$i."_weap"] = 0;
            if ( $_POST["a".$i."_shld"] === "" ) $_POST["a".$i."_shld"] = 0;
            if ( $_POST["a".$i."_armor"] === "" ) $_POST["a".$i."_armor"] = 0;

            $a[$i]['r109'] = $_POST["a".$i."_weap"];
            $a[$i]['r110'] = $_POST["a".$i."_shld"];
            $a[$i]['r111'] = $_POST["a".$i."_armor"];
            $a[$i]['oname'] = "Attacker$i";
    
            $a[$i]['g'] = mt_rand (1, 9);
            $a[$i]['s'] = mt_rand (1, 499);
            $a[$i]['p'] = mt_rand (1, 15);

            $a[$i]['fleet'] = array ();
            foreach ( $fleetmap as $n=>$gid)
            {
                if ( $_POST["a$i"][$gid] === "" ) $_POST["a$i"][$gid] = 0;
                $a[$i]['fleet'][$gid] = $_POST["a$i"][$gid];
            }
        }

        // Обороняющиеся
        for ($i=0; $i<$dnum; $i++)
        {
            if ( $_POST["d".$i."_weap"] === "" ) $_POST["d".$i."_weap"] = 0;
            if ( $_POST["d".$i."_shld"] === "" ) $_POST["d".$i."_shld"] = 0;
            if ( $_POST["d".$i."_armor"] === "" ) $_POST["d".$i."_armor"] = 0;

            $d[$i]['r109'] = $_POST["d".$i."_weap"];
            $d[$i]['r110'] = $_POST["d".$i."_shld"];
            $d[$i]['r111'] = $_POST["d".$i."_armor"];
            $d[$i]['oname'] = "Defender$i";
    
            $d[$i]['g'] = mt_rand (1, 9);
            $d[$i]['s'] = mt_rand (1, 499);
            $d[$i]['p'] = mt_rand (1, 15);

            $d[$i]['fleet'] = array ();
            foreach ( $fleetmap as $n=>$gid)
            {
                if ( $_POST["d$i"][$gid] === "" ) $_POST["d$i"][$gid] = 0;
                $d[$i]['fleet'][$gid] = $_POST["d$i"][$gid];
            }

            $d[$i]['defense'] = array ();
            foreach ( $defmap as $n=>$gid)
            {
                if ( $_POST["d$i"][$gid] === "" ) $_POST["d$i"][$gid] = 0;
                $d[$i]['defense'][$gid] = $_POST["d$i"][$gid];
            }
        }

        // Симулировать битву
        $BattleReport = SimBattle ( $a, $d );
    }

    // --------------------------------------------------------------------------------------------------------------------------
    // Таблица ввода параметров симуляции.

?>

<table cellpadding=0 cellspacing=0>
<form action="index.php?page=admin&session=<?=$session;?>&mode=BattleSim" method="POST">
<input type="hidden" name="dnum" value="1" />
<input type="hidden" name="anum" value="1" />

<tr>        <td class=c>Оборояющийся</td>        <td class=c>Атакующий</td>        </tr>

<tr> 
<td> Вооружение: <input name="d0_weap" size=2> Щиты: <input name="d0_shld" size=2> Броня: <input name="d0_armor" size=2></td>   
<td> Вооружение: <input name="a0_weap" size=2> Щиты: <input name="a0_shld" size=2> Броня: <input name="a0_armor" size=2></td>   
</tr>

        <tr> <th valign=top>
        <table>
<?php

    echo "<tr><td class=c><b>Флот</b></td></tr>\n";
    foreach ($fleetmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> : </td> <td> <input name="d0[<?=$gid;?>]" size=5> </td> </tr>
<?php
    }

    echo "<tr><td class=c><b>Оборона</b></td></tr>\n";
    foreach ($defmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> : </td> <td> <input name="d0[<?=$gid;?>]" size=5> </td> </tr>
<?php
    }
?>
        </table>
        </th>

        <th valign=top>
        <table>
<?php

    echo "<tr><td class=c><b>Флот</b></td></tr>\n";
    foreach ($fleetmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> : </td> <td> <input name="a0[<?=$gid;?>]" size=5> </td> </tr>
<?php
    }
?>
        </table>
        </th></tr>

<tr><td colspan=2> 
<table>
<tr><td class=c colspan=2>Настройки</td></tr>
<tr><td>Скорострел</td><td><input type="checkbox" name="rapid"></td></tr>
<tr><td>Флот в обломки</td><td><input name="fid" size=3 value="30"> </td></tr>
<tr><td>Оборона в обломки</td><td><input name="did" size=3 value="0"></td></tr>
</table>
</td></tr>

<tr><td colspan=2><center><input type="submit" value="Начать бой"></center></td></tr>
</form>
</table>

<?=$BattleReport;?>

<?php

}

?>