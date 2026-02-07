<?php

// Admin panel for a quick navigation.
function AdminPanel () : void
{
    global $session;
?>

<table><tr><td>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Fleetlogs"><img src="img/admin_fleetlogs.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_FLEETLOGS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Browse"><img src="img/admin_browse.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_BROWSE");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Reports"><img src="img/admin_report.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_REPORTS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Bans"><img src="img/admin_ban.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_BANS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users"><img src="img/admin_users.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_USERS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Planets"><img src="img/admin_planets.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_PLANETS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Queue"><img src="img/admin_queue.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_QUEUE");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Uni"><img src="img/admin_uni.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_UNI");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Errors"><img src="img/admin_error.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_ERRORS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Debug"><img src="img/admin_debug.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_DEBUG");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=BattleSim"><img src="img/admin_sim.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_BATTLESIM");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Broadcast"><img src="img/admin_broadcast.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_BROADCAST");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Expedition"><img src="<?php echo hostname();?>evolution/gebaeude/210.gif" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_EXPEDITION");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Logins"><img src="img/admin_logins.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_LOGINS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Checksum"><img src="img/admin_checksum.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_CHECKSUM");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Bots"><img src="img/admin_bots.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_BOTS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=BattleReport"><img src="img/admin_battle.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_BATTLELOGS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=UserLogs"><img src="img/admin_userlogs.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_USERLOGS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=BotEdit"><img src="img/admin_botedit.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_BOTEDIT");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Coupons"><img src="img/admin_coupons.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_COUPONS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=RakSim"><img src="img/admin_raksim.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_RAKSIM");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=DB"><img src="img/admin_db.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_DB");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=ColonySettings"><img src="img/admin_colony_settings.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_COLONY_SETTINGS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Loca"><img src="img/admin_loca.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_LOCA");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?php echo $session;?>&mode=Mods"><img src="img/admin_mods.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b><?php echo loca("ADM_MENU_MODS");?></b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

</td></tr></table><br/>

<?php
}

?>