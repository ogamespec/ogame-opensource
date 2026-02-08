<?php

// Admin Area: Universe Settings.

class Admin_Uni extends Page {

    public function controller () : bool {
        global $db_prefix;
        global $GlobalUser;
        global $now;

        if ( method () === "POST" && $GlobalUser['admin'] >= 2 )
        {
            if ( key_exists ('news_upd', $_POST) )        // Update the news
            {
                if ( $_POST['news_upd'] > 0 ) UpdateNews ( $_POST['news1'], $_POST['news2'], $_POST['news_upd'] );
            }
            if ( key_exists ('news_off', $_POST) && $_POST['news_off'] === "on" )    // Remove the news
            {
                DisableNews ();
            }

            $rapid = ($_POST['rapid'] === "on") ? 1 : 0;
            $moons = ($_POST['moons'] === "on") ? 1 : 0;
            $freeze = (key_exists ('freeze', $_POST) && $_POST['freeze'] === "on") ? 1 : 0;
            $php_battle = (key_exists ('php_battle', $_POST) && $_POST['php_battle'] === "on") ? 1 : 0;
            $force_lang = (key_exists ('force_lang', $_POST) && $_POST['force_lang'] === "on") ? 1 : 0;

            SetUniParam ( 
                $_POST['speed'], 
                $_POST['fspeed'], 
                $_POST['acs'], 
                $_POST['fid'], 
                $_POST['did'], 
                $_POST['defrepair'], 
                $_POST['defrepair_delta'], 
                intval($_POST['galaxies']), 
                intval($_POST['systems']), 
                $rapid, 
                $moons, 
                $freeze, 
                $_POST['lang'],
                $_POST['battle_engine'],
                $php_battle,
                $force_lang,
                intval($_POST['start_dm']),
                intval($_POST['max_werf']),
                intval($_POST['feedage']) );

            // Set external links. If the link is empty - the menu item will be missing.

            SetExtLinks (
                $_POST['ext_board'],
                $_POST['ext_discord'],
                $_POST['ext_tutorial'],
                $_POST['ext_rules'],
                $_POST['ext_impressum'] );

            // Set the maximum number of users.

            SetMaxUsers (intval($_POST['maxusers']));

            // Enable forced VM to active players if the universe is paused.
            if ( $freeze ) {
                $days7 = $now - 7*24*60*60;
                $query = "UPDATE ".$db_prefix."users SET vacation = 1, vacation_until = ".$now." WHERE lastclick >= $days7 AND admin = 0";
                dbquery ( $query );
            }

            //print_r ( $_POST );
        }

        return true;
    }

    public function view () : void {
        global $GlobalUni;
        global $session;
        global $now;

        //$info = "[i]";
        $info = "<img src='img/r5.png' />";

        $unitab = $GlobalUni;
?>

<table >
<form action="index.php?page=admin&session=<?php echo $session;?>&mode=Uni" method="POST" >
<tr><td class=c colspan=2><?=va(loca("ADM_UNI_SETTINGS"), $unitab['num']);?></td></tr>
<tr><th><?=loca("ADM_UNI_DATE");?></th><th><?php echo date ("Y-m-d H:i:s", $unitab['startdate']);?></th></tr>
<tr><th><?=loca("ADM_UNI_HACK_COUNTER");?> <a title="<?=loca("ADM_UNI_HACK_COUNTER_INFO");?>"><?php echo $info;?></a></th><th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Debug&filter=HACKING"><?php echo $unitab['hacks'];?> (<?=loca("ADM_UNI_HACK_CHECK");?>)</a></th></tr>
<tr><th><?=loca("ADM_UNI_USERS");?></th><th><?php echo $unitab['usercount'];?></th></tr>
<tr><th><?=loca("ADM_UNI_MAX_USERS");?></th><th><input type="text" name="maxusers" maxlength="10" size="10" value="<?php echo $unitab['maxusers'];?>" /></th></tr>
<tr><th><?=loca("ADM_UNI_START_DM");?></th><th><input type="text" name="start_dm" maxlength="10" size="10" value="<?php echo $unitab['start_dm'];?>" /></th></tr>
<tr><th><?=loca("ADM_UNI_GALAXIES");?></th><th><input type="text" name="galaxies" maxlength="3" size="3" value="<?php echo $unitab['galaxies'];?>" /></th></tr>
<tr><th><?=loca("ADM_UNI_SYSTEMS");?></th><th><input type="text" name="systems" maxlength="3" size="3" value="<?php echo $unitab['systems'];?>" /></th></tr>
<tr><th><?=loca("INSTALL_MAX_WERF");?></th><th><input type="text" name="max_werf" maxlength="9" size="9" value="<?php echo $unitab['max_werf'];?>" /></th></tr>
<tr><th><?=loca("INSTALL_FEED_AGE");?></th><th><input type="text" name="feedage" maxlength="3" size="3" value="<?php echo $unitab['feedage'];?>" /></th></tr>

  <tr>
   <th><?=loca("ADM_UNI_SPEED");?></th>
   <th>
   <select name="speed">
     <option value="1" <?php echo $this->UniIsSelected($unitab['speed'], 1);?>>1x</option>
     <option value="2" <?php echo $this->UniIsSelected($unitab['speed'], 2);?>>2x</option>
     <option value="3" <?php echo $this->UniIsSelected($unitab['speed'], 3);?>>3x</option>
     <option value="4" <?php echo $this->UniIsSelected($unitab['speed'], 4);?>>4x</option>
     <option value="5" <?php echo $this->UniIsSelected($unitab['speed'], 5);?>>5x</option>
     <option value="6" <?php echo $this->UniIsSelected($unitab['speed'], 6);?>>6x</option>
     <option value="7" <?php echo $this->UniIsSelected($unitab['speed'], 7);?>>7x</option>
     <option value="8" <?php echo $this->UniIsSelected($unitab['speed'], 8);?>>8x</option>
     <option value="9" <?php echo $this->UniIsSelected($unitab['speed'], 9);?>>9x</option>
     <option value="10" <?php echo $this->UniIsSelected($unitab['speed'], 10);?>>10x</option>
   </select>
   </th>
 </tr>

  <tr>
   <th><?=loca("ADM_UNI_FSPEED");?></th>
   <th>
   <select name="fspeed">
     <option value="1" <?php echo $this->UniIsSelected($unitab['fspeed'], 1);?>>1x</option>
     <option value="2" <?php echo $this->UniIsSelected($unitab['fspeed'], 2);?>>2x</option>
     <option value="3" <?php echo $this->UniIsSelected($unitab['fspeed'], 3);?>>3x</option>
     <option value="4" <?php echo $this->UniIsSelected($unitab['fspeed'], 4);?>>4x</option>
     <option value="5" <?php echo $this->UniIsSelected($unitab['fspeed'], 5);?>>5x</option>
     <option value="6" <?php echo $this->UniIsSelected($unitab['fspeed'], 6);?>>6x</option>
     <option value="7" <?php echo $this->UniIsSelected($unitab['fspeed'], 7);?>>7x</option>
     <option value="8" <?php echo $this->UniIsSelected($unitab['fspeed'], 8);?>>8x</option>
     <option value="9" <?php echo $this->UniIsSelected($unitab['fspeed'], 9);?>>9x</option>
     <option value="10" <?php echo $this->UniIsSelected($unitab['fspeed'], 10);?>>10x</option>
   </select>
   </th>
 </tr>

  <tr>
   <th><?=loca("ADM_UNI_FID");?></th>
   <th>
   <select name="fid">
     <option value="0" <?php echo $this->UniIsSelected($unitab['fid'], 0);?>>0%</option>
     <option value="10" <?php echo $this->UniIsSelected($unitab['fid'], 10);?>>10%</option>
     <option value="20" <?php echo $this->UniIsSelected($unitab['fid'], 20);?>>20%</option>
     <option value="30" <?php echo $this->UniIsSelected($unitab['fid'], 30);?>>30%</option>
     <option value="40" <?php echo $this->UniIsSelected($unitab['fid'], 40);?>>40%</option>
     <option value="50" <?php echo $this->UniIsSelected($unitab['fid'], 50);?>>50%</option>
     <option value="60" <?php echo $this->UniIsSelected($unitab['fid'], 60);?>>60%</option>
     <option value="70" <?php echo $this->UniIsSelected($unitab['fid'], 70);?>>70%</option>
     <option value="80" <?php echo $this->UniIsSelected($unitab['fid'], 80);?>>80%</option>
     <option value="90" <?php echo $this->UniIsSelected($unitab['fid'], 90);?>>90%</option>
     <option value="100" <?php echo $this->UniIsSelected($unitab['fid'], 100);?>>100%</option>
   </select>
   </th>
 </tr>

  <tr>
   <th><?=loca("ADM_UNI_DID");?></th>
   <th>
   <select name="did">
     <option value="0" <?php echo $this->UniIsSelected($unitab['did'], 0);?>>0%</option>
     <option value="10" <?php echo $this->UniIsSelected($unitab['did'], 10);?>>10%</option>
     <option value="20" <?php echo $this->UniIsSelected($unitab['did'], 20);?>>20%</option>
     <option value="30" <?php echo $this->UniIsSelected($unitab['did'], 30);?>>30%</option>
     <option value="40" <?php echo $this->UniIsSelected($unitab['did'], 40);?>>40%</option>
     <option value="50" <?php echo $this->UniIsSelected($unitab['did'], 50);?>>50%</option>
     <option value="60" <?php echo $this->UniIsSelected($unitab['did'], 60);?>>60%</option>
     <option value="70" <?php echo $this->UniIsSelected($unitab['did'], 70);?>>70%</option>
     <option value="80" <?php echo $this->UniIsSelected($unitab['did'], 80);?>>80%</option>
     <option value="90" <?php echo $this->UniIsSelected($unitab['did'], 90);?>>90%</option>
     <option value="100" <?php echo $this->UniIsSelected($unitab['did'], 100);?>>100%</option>
   </select>
   </th>
 </tr>

<tr><th><?=loca("ADM_UNI_DEF_REPAIR");?></th><th>
<input type="text" name="defrepair" maxlength="3" size="3" value="<?php echo $unitab['defrepair'];?>" /> +/-
<input type="text" name="defrepair_delta" maxlength="3" size="3" value="<?php echo $unitab['defrepair_delta'];?>" /> %
</th></tr>

<tr><th><?=loca("ADM_UNI_ACS_PLAYERS");?></th><th><input type="text" name="acs" maxlength="3" size="3" value="<?php echo $unitab['acs'];?>" /> (<?=va(loca("ADM_UNI_ACS_FLEETS"), $unitab['acs']*$unitab['acs']);?>)</th></tr>

<tr><th><?=loca("ADM_UNI_RAPIDFIRE");?></th><th><input type="checkbox" name="rapid"  <?php echo $this->UniIsChecked($unitab['rapid']);?> /></th></tr>
<tr><th><?=loca("ADM_UNI_MOONS");?></th><th><input type="checkbox" name="moons" <?php echo $this->UniIsChecked($unitab['moons']);?> /></th></tr>
<tr><th><?=loca("ADM_UNI_NEWS1");?></th><th><input type="text" name="news1" maxlength="99" size="20" value="<?php echo $unitab['news1'];?>" /></th></tr>
<tr><th><?=loca("ADM_UNI_NEWS2");?></th><th><input type="text" name="news2" maxlength="99" size="20" value="<?php echo $unitab['news2'];?>" /></th></tr>
<?php
    if ( $now > $unitab['news_until'] ) echo "<tr><th>".loca("ADM_UNI_NEWS_PROLONG")."</th><th><input type=\"text\" name=\"news_upd\" maxlength=\"3\" size=\"3\" value=\"0\" /> ".loca("ADM_UNI_DAYS")."</th></tr>\n";
    else echo "<tr><th>".loca("ADM_UNI_NEWS_SHOW_UNTIL")."</th><th>".date ("Y-m-d H:i:s", $unitab['news_until'])." <input type=\"checkbox\" name=\"news_off\"  /> ".loca("ADM_UNI_NEWS_REMOVE")."</th></tr>\n";
?>
<tr><th><?=loca("ADM_UNI_LANG");?></th><th>
   <select name="lang">
<?php
    global $Languages;
    foreach ( $Languages as $lang_id=>$lang_name ) {
        echo "    <option value=\"".$lang_id."\" " . $this->UniIsSelected($unitab['lang'], $lang_id)." >$lang_name</option>\n";
    }
?>
   </select>
</th></tr>
<tr><th><?php echo loca("ADM_UNI_FORCE_LANG");?></th><th><input type="checkbox" name="force_lang"  <?php echo $this->UniIsChecked($unitab['force_lang']);?> /></th></tr>

<tr><th><?php echo loca("MENU_BOARD");?></th><th><input type="text" name="ext_board" maxlength="99" size="20" value="<?php echo $unitab['ext_board'];?>" /></th></tr>
<tr><th><?php echo loca("MENU_DISCORD");?></th><th><input type="text" name="ext_discord" maxlength="99" size="20" value="<?php echo $unitab['ext_discord'];?>" /></th></tr>
<tr><th><?php echo loca("MENU_TUTORIAL");?></th><th><input type="text" name="ext_tutorial" maxlength="99" size="20" value="<?php echo $unitab['ext_tutorial'];?>" /></th></tr>
<tr><th><?php echo loca("MENU_RULES");?></th><th><input type="text" name="ext_rules" maxlength="99" size="20" value="<?php echo $unitab['ext_rules'];?>" /></th></tr>
<tr><th><?php echo loca("MENU_IMPRESSUM");?></th><th><input type="text" name="ext_impressum" maxlength="99" size="20" value="<?php echo $unitab['ext_impressum'];?>" /></th></tr>
<tr><th><?php echo loca("INSTALL_UNI_BATTLE");?></th><th><input type="text" name="battle_engine" maxlength="99" size="20" value="<?php echo $unitab['battle_engine'];?>" /></th></tr>
<tr><th><?php echo loca("INSTALL_UNI_PHP_BATTLE");?></th><th><input type="checkbox" name="php_battle"  <?php echo $this->UniIsChecked($unitab['php_battle']);?> /></th></tr>

<tr><th><?=loca("ADM_UNI_FREEZE");?> <a title="<?=loca("ADM_UNI_FREEZE_INFO");?>"><?php echo $info;?></a>
</th><th><input type="checkbox" name="freeze"  <?php echo $this->UniIsChecked($unitab['freeze']);?> /></th></tr>
<tr><th colspan=2><input type="submit" value="<?=loca("ADM_UNI_SAVE");?>" /></th></tr>

</form>
</table>

<?php

    }

    private function UniIsSelected (mixed $option, mixed $value) : string
    {
        if ( $option == $value ) return "selected";
        else return "";
    }

    private function UniIsChecked (int $option) : string
    {
        if ( $option ) return "checked";
        else return "";
    }
}

?>