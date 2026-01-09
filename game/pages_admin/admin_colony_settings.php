<?php

// Admin Area: Colonization Settings

function Admin_ColonySettings () : void
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $coltab = LoadColonySettings();

    // POST request processing.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 )
    {
        $coltab = $_POST;
        SaveColonySettings ($coltab);
    }
?>

<?php AdminPanel();?>

<table >
<form action="index.php?page=admin&session=<?php echo $session;?>&mode=ColonySettings" method="POST" >
<tr><td class=c colspan=2><?=loca("ADM_COL_HEAD");?></td></tr>

<tr><th><?=loca("ADM_COL_T1");?></th><th>
    <input type="text" name="t1_a" maxlength="3" size="3" value="<?php echo $coltab['t1_a'];?>" />
    <input type="text" name="t1_b" maxlength="3" size="3" value="<?php echo $coltab['t1_b'];?>" />
    <input type="text" name="t1_c" maxlength="3" size="3" value="<?php echo $coltab['t1_c'];?>" />
</th></tr>

<tr><th><?=loca("ADM_COL_T2");?></th><th>
    <input type="text" name="t2_a" maxlength="3" size="3" value="<?php echo $coltab['t2_a'];?>" />
    <input type="text" name="t2_b" maxlength="3" size="3" value="<?php echo $coltab['t2_b'];?>" />
    <input type="text" name="t2_c" maxlength="3" size="3" value="<?php echo $coltab['t2_c'];?>" />
</th></tr>

<tr><th><?=loca("ADM_COL_T3");?></th><th>
    <input type="text" name="t3_a" maxlength="3" size="3" value="<?php echo $coltab['t3_a'];?>" />
    <input type="text" name="t3_b" maxlength="3" size="3" value="<?php echo $coltab['t3_b'];?>" />
    <input type="text" name="t3_c" maxlength="3" size="3" value="<?php echo $coltab['t3_c'];?>" />
</th></tr>

<tr><th><?=loca("ADM_COL_T4");?></th><th>
    <input type="text" name="t4_a" maxlength="3" size="3" value="<?php echo $coltab['t4_a'];?>" />
    <input type="text" name="t4_b" maxlength="3" size="3" value="<?php echo $coltab['t4_b'];?>" />
    <input type="text" name="t4_c" maxlength="3" size="3" value="<?php echo $coltab['t4_c'];?>" />
</th></tr>

<tr><th><?=loca("ADM_COL_T5");?></th><th>
    <input type="text" name="t5_a" maxlength="3" size="3" value="<?php echo $coltab['t5_a'];?>" />
    <input type="text" name="t5_b" maxlength="3" size="3" value="<?php echo $coltab['t5_b'];?>" />
    <input type="text" name="t5_c" maxlength="3" size="3" value="<?php echo $coltab['t5_c'];?>" />
</th></tr>

<tr><th colspan=2><input type="submit" value="<?=loca("ADM_COL_SUBMIT");?>" /></th></tr>

</form>
</table>

<br/>
<?=loca("ADM_COL_INFO1");?>: <pre>D = RND(a, b) * c</pre>
<?=loca("ADM_COL_INFO2");?><br/>

<?php
}
?>