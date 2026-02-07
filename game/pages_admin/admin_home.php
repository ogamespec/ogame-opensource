<?php

// ========================================================================================
// Home Page.

function Admin_Home () : void
{
    global $session;
?>
    <br>
    <br>

    <table width=100% border="0" cellpadding="0" cellspacing="1" align="top" class="s">
    <tr>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Fleetlogs"><img src="img/admin_fleetlogs.png"><br><?php echo loca("ADM_MENU_FLEETLOGS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Browse"><img src="img/admin_browse.png"><br><?php echo loca("ADM_MENU_BROWSE");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Reports"><img src="img/admin_report.png"><br><?php echo loca("ADM_MENU_REPORTS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Bans"><img src="img/admin_ban.png"><br><?php echo loca("ADM_MENU_BANS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users"><img src="img/admin_users.png"><br><?php echo loca("ADM_MENU_USERS");?></a></th>
    </tr>
    <tr>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Planets"><img src="img/admin_planets.png"><br><?php echo loca("ADM_MENU_PLANETS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Queue"><img src="img/admin_queue.png"><br><?php echo loca("ADM_MENU_QUEUE");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Uni"><img src="img/admin_uni.png"><br><?php echo loca("ADM_MENU_UNI");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Errors"><img src="img/admin_error.png"><br><?php echo loca("ADM_MENU_ERRORS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Debug"><img src="img/admin_debug.png"><br><?php echo loca("ADM_MENU_DEBUG");?></a></th>
    </tr>
    <tr>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=BattleSim"><img src="img/admin_sim.png"><br><?php echo loca("ADM_MENU_BATTLESIM");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Broadcast"><img src="img/admin_broadcast.png"><br><?php echo loca("ADM_MENU_BROADCAST");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Expedition"><img src="<?php echo hostname();?>evolution/gebaeude/210.gif"><br><?php echo loca("ADM_MENU_EXPEDITION");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Logins"><img src="img/admin_logins.png"><br><?php echo loca("ADM_MENU_LOGINS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Checksum"><img src="img/admin_checksum.png"><br><?php echo loca("ADM_MENU_CHECKSUM");?></a></th>
    </tr>
    <tr>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Bots"><img src="img/admin_bots.png"><br><?php echo loca("ADM_MENU_BOTS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=BattleReport"><img src="img/admin_battle.png"><br><?php echo loca("ADM_MENU_BATTLELOGS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=UserLogs"><img src="img/admin_userlogs.png"><br><?php echo loca("ADM_MENU_USERLOGS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=BotEdit"><img src="img/admin_botedit.png"><br><?php echo loca("ADM_MENU_BOTEDIT");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Coupons"><img src="img/admin_coupons.png"><br><?php echo loca("ADM_MENU_COUPONS");?></a></th>
    </tr>
    <tr>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=RakSim"><img src="img/admin_raksim.png"><br><?php echo loca("ADM_MENU_RAKSIM");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=DB"><img src="img/admin_db.png"><br><?php echo loca("ADM_MENU_DB");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=ColonySettings"><img src="img/admin_colony_settings.png"><br><?php echo loca("ADM_MENU_COLONY_SETTINGS");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Loca"><img src="img/admin_loca.png"><br><?php echo loca("ADM_MENU_LOCA");?></a></th>
    <th><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Mods"><img src="img/admin_mods.png"><br><?php echo loca("ADM_MENU_MODS");?></a></th>
    </tr>
    </table>
<?php
}
?>