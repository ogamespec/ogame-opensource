<?php

// Админка: симулятор ракетной атаки. Используется для верификации и отладки алгоритмической части ракетной атаки.

function Admin_RakSim ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;
    global $GlobalUni;

    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );
    $def = array ();

    foreach ($defmap as $i=>$gid) {
        $def[$gid] = 0;
    }

    loca_add ( "galaxy", $GlobalUni['lang'] );

    $a_weap = 0;
    $d_armor = 0;

    $anz = 0;
    $pziel = 0;

    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] != 0 ) {
        //print_r ( $_POST );
        //echo "<hr>";

        if ( key_exists ( 'a_weap', $_POST) ) $a_weap = intval ($_POST['a_weap']);
        if ( key_exists ( 'd_armor', $_POST) ) $d_armor = intval ($_POST['d_armor']);

        if ( key_exists ( 'anz', $_POST) ) $anz = intval ($_POST['anz']);
        if ( key_exists ( 'pziel', $_POST) ) $pziel = intval ($_POST['pziel']);

        $target = array();
        $moon_planet = array();     // не используется

        foreach ($defmap as $i=>$gid) {
            if ( key_exists ( 'd_'.$gid, $_POST) ) {
                $def[$gid] = intval ($_POST['d_'.$gid]);
            }
            $target["d".$gid] = $def[$gid];
        }

        $ipm_destroyed = RocketAttackMain (
            $anz,
            $pziel,
            false,
            $target,
            $moon_planet,
            $a_weap,
            $d_armor );

        foreach ($defmap as $i=>$gid) {
            $def[$gid] = $target["d".$gid];
        }
    }
?>

<?=AdminPanel();?>

<table cellpadding=0 cellspacing=0>
<form name="simForm" action="index.php?page=admin&session=<?=$session;?>&mode=RakSim" method="POST" >

<tr>        <td class=c>Атакующий</td>                <td class=c>Обороняющийся</td>  </tr>

<tr> 
<td> 
    Вооружение: <input type="text" name="a_weap" size=2 value="<?=$a_weap;?>"> 
<td> 
    Броня: <input type="text" name="d_armor" size=2 value="<?=$d_armor;?>"></td> 
</tr>


        <tr> <th valign=top>
        <table>

<tr><td colspan=2> 
<table>
<tr><td class=c colspan=2>Настройки</td></tr>

<tr><td>
<?=loca("NAME_503");?>:     <input type="text" name="anz" size="2" maxlength="2" value="<?=$anz;?>"/></td></tr>

    <tr><td>
    <?=loca("GALAXY_RAK_TARGET");?>:
     <select name="pziel">
      <option value="0" <?php if($pziel == 0) echo "selected";?> ><?=loca("GALAXY_RAK_TARGET_ALL");?></option>
<?php
    foreach ($defmap as $i=>$gid)
    {
        if ($gid > 500) {
            // Не нужно учитывать ракетную оборону.
            break;
        }
        echo "       <option value=\"$gid\" ";
        if($pziel == $gid) echo "selected";
        echo ">".loca("NAME_$gid")."</option>\n";
    }
?>
           </select>
    </td></tr>

</table>
</td></tr>

        </table>
        </th>



        <th valign=top>
        <table>

<?php

    echo "<tr><td class=c colspan=2><b>Оборона</b></td></tr>\n";
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );
    foreach ($defmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input name="d_<?=$gid;?>" size=5 value=<?=$def[$gid];?>> </td> </tr>
<?php
    }
?>
        </table>
        </th></tr>            


<tr><td colspan=2><center><input type="submit" value="Ракетная атака"></center></td></tr>
</form>
</table>

<?php
} 		// Admin_RakSim
?>