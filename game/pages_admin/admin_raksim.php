<?php

// Admin Area: missile attack simulator. Used for verification and debugging of the algorithmic part of the missile attack.

function Admin_RakSim () : void
{
    global $session;
    global $db_prefix;
    global $GlobalUser;
    global $GlobalUni;
    global $defmap;

    $def = array ();

    foreach ($defmap as $i=>$gid) {
        $def[$gid] = 0;
    }

    loca_add ( "galaxy", $GlobalUser['lang'] );

    $a_weap = 0;
    $d_armor = 0;

    $anz = 0;
    $pziel = 0;

    // POST request processing.
    if ( method () === "POST" && $GlobalUser['admin'] != 0 ) {
        //print_r ( $_POST );
        //echo "<hr>";

        if ( key_exists ( 'a_weap', $_POST) ) $a_weap = intval ($_POST['a_weap']);
        if ( key_exists ( 'd_armor', $_POST) ) $d_armor = intval ($_POST['d_armor']);

        if ( key_exists ( 'anz', $_POST) ) $anz = intval ($_POST['anz']);
        if ( key_exists ( 'pziel', $_POST) ) $pziel = intval ($_POST['pziel']);

        $target = array();
        $moon_planet = array();     // not used

        foreach ($defmap as $i=>$gid) {
            if ( key_exists ( 'd_'.$gid, $_POST) ) {
                $def[$gid] = intval ($_POST['d_'.$gid]);
            }
            $target[$gid] = $def[$gid];
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
            $def[$gid] = $target[$gid];
        }
    }
?>

<?php AdminPanel();?>

<table cellpadding=0 cellspacing=0>
<form name="simForm" action="index.php?page=admin&session=<?=$session;?>&mode=RakSim" method="POST" >

<tr>        <td class=c><?=loca("ADM_RAKSIM_ATTACKER");?></td>                <td class=c><?=loca("ADM_RAKSIM_DEFENDER");?></td>  </tr>

<tr> 
<td> 
    <?=loca("ADM_RAKSIM_WEAP");?> <input type="text" name="a_weap" size=2 value="<?=$a_weap;?>"> 
<td> 
    <?=loca("ADM_RAKSIM_ARMOUR");?> <input type="text" name="d_armor" size=2 value="<?=$d_armor;?>"></td> 
</tr>


        <tr> <th valign=top>
        <table>

<tr><td colspan=2> 
<table>
<tr><td class=c colspan=2><?=loca("ADM_RAKSIM_SETTINGS");?></td></tr>

<tr><td>
<?=loca("NAME_503");?>:     <input type="text" name="anz" size="2" maxlength="2" value="<?=$anz;?>"/></td></tr>

    <tr><td>
    <?=loca("GALAXY_RAK_TARGET");?>:
     <select name="pziel">
      <option value="0" <?php if($pziel == 0) echo "selected";?> ><?=loca("GALAXY_RAK_TARGET_ALL");?></option>
<?php
    foreach ($defmap as $i=>$gid)
    {
        if (!IsDefenseNoRak($gid)) {
            // No need to consider missile defenses.
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

    echo "<tr><td class=c colspan=2><b>".loca("ADM_RAKSIM_DEFENSE")."</b></td></tr>\n";
    foreach ($defmap as $i=>$gid)
    {
?>
           <tr><td> <?=loca("NAME_$gid");?> </td> <td> <input name="d_<?=$gid;?>" size=5 value=<?=$def[$gid];?>> </td> </tr>
<?php
    }
?>
        </table>
        </th></tr>            


<tr><td colspan=2><center><input type="submit" value="<?=loca("ADM_RAKSIM_SUBMIT");?>"></center></td></tr>
</form>
</table>

<?php
} 		// Admin_RakSim
?>