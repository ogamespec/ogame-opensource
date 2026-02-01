<?php

// Admin Area: source code checksum.

function Admin_Checksum () : void
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $engine_md = unserialize ( file_get_contents ('temp/engine.md5') );
    $page_md = unserialize ( file_get_contents ('temp/page.md5') );
    $page_admin_md = unserialize ( file_get_contents ('temp/page_admin.md5') );
    $reg_md = unserialize ( file_get_contents ('temp/reg.md5') );

    $engine_files = array (
        'ainfo.php', 
        'core/acs.php', 
        'core/ally.php', 
        'core/battle.php', 
        'core/battle_engine.php', 
        'core/battle_report.php', 
        'core/bbcode.php', 
        'core/bot.php', 
        'core/botapi.php', 
        'core/core.php', 
        'core/coupon.php', 
        'core/db.php', 
        'core/defs.php', 
        'core/debug.php', 
        'core/expedition.php', 
        'core/expedition_battle.php', 
        'core/fleet.php', 
        'core/galaxytool.php', 
        'core/graviton.php', 
        'index.php', 
        'install.php', 
        'core/install_tabs.php', 
        'core/loca.php', 
        'maintenance.php', 
        'core/mods.php',
        'core/msg.php', 
        'core/notes.php', 
        'core/page.php', 
        'pic.php', 
        'core/planet.php', 
        'pranger.php', 
        'core/prod.php', 
        'core/queue.php', 
        'core/raketen.php', 
        'redir.php', 
        'core/techs.php', 
        'core/uni.php', 
        'core/user.php', 
        'core/utils.php', 
        'validate.php', 
        '../feed/show.php', 
        '../feed/viewitem.php', 
    );

    $page_admin_files = array (
        'pages_admin/admin.php', 
        'pages_admin/admin_bans.php', 
        'pages_admin/admin_battle.php', 
        'pages_admin/admin_botedit.php', 
        'pages_admin/admin_bots.php', 
        'pages_admin/admin_broadcast.php', 
        'pages_admin/admin_browse.php', 
        'pages_admin/admin_checksum.php', 
        'pages_admin/admin_colony_settings.php', 
        'pages_admin/admin_coupons.php', 
        'pages_admin/admin_db.php', 
        'pages_admin/admin_debug.php', 
        'pages_admin/admin_errors.php', 
        'pages_admin/admin_expedition.php', 
        'pages_admin/admin_fleetlogs.php', 
        'pages_admin/admin_loca.php', 
        'pages_admin/admin_logins.php', 
        'pages_admin/admin_mods.php', 
        'pages_admin/admin_planets.php', 
        'pages_admin/admin_queue.php', 
        'pages_admin/admin_raksim.php', 
        'pages_admin/admin_reports.php', 
        'pages_admin/admin_sim.php', 
        'pages_admin/admin_uni.php', 
        'pages_admin/admin_userlogs.php', 
        'pages_admin/admin_users.php', 
    );

    $page_files = array (
        'pages/ainfo.php', 
        'pages/allianzdepot.php', 
        'pages/allianzen.php', 
        'pages/allianzen_circular.php', 
        'pages/allianzen_main.php', 
        'pages/allianzen_members.php', 
        'pages/allianzen_misc.php', 
        'pages/allianzen_ranks.php', 
        'pages/allianzen_settings.php', 
        'pages/b_building.php', 
        'pages/bericht.php', 
        'pages/bewerben.php', 
        'pages/bewerbungen.php', 
        'pages/buddy.php', 
        'pages/buildings.php', 
        'pages/changelog.php', 
        'pages/event_list.php', 
        'pages/fleet_templates.php', 
        'pages/flotten1.php', 
        'pages/flotten2.php', 
        'pages/flotten3.php', 
        'pages/flottenversand.php', 
        'pages/flottenversand_ajax.php', 
        'pages/galaxy.php', 
        'pages/imperium.php', 
        'pages/infos.php', 
        'pages/logout.php', 
        'pages/messages.php', 
        'pages/micropayment.php', 
        'pages/notizen.php', 
        'pages/options.php', 
        'pages/overview.php', 
        'pages/overview_events.php', 
        'pages/payment.php', 
        'pages/phalanx.php', 
        'pages/phalanx_events.php', 
        'pages/pranger.php', 
        'pages/renameplanet.php', 
        'pages/resources.php', 
        'pages/sprungtor.php', 
        'pages/statistics.php', 
        'pages/suche.php', 
        'pages/techtree.php', 
        'pages/techtreedetails.php', 
        'pages/trader.php', 
        'pages/writemessages.php', 
    );

    $reg_files = array (
        'reg/check_registration.php', 
        'reg/errorpage.php', 
        'reg/fa_pass.php', 
        'reg/login.php', 
        'reg/login2.php', 
        'reg/mail.php', 
        'reg/new.php', 
        'reg/newredirect.php', 
    );

    if ( method () === "POST" ) {    // Сохранить контрольные суммы файлов
        foreach ( $engine_files as $i=>$filename ) {
            $md = md5_file($filename) ;
            $engine_md[$filename] = $md;
        }
        foreach ( $page_admin_files as $i=>$filename ) {
            $md = md5_file($filename) ;
            $page_admin_md[$filename] = $md;
        }
        foreach ( $page_files as $i=>$filename ) {
            $md = md5_file($filename) ;
            $page_md[$filename] = $md;
        }
        foreach ( $reg_files as $i=>$filename ) {
            $md = md5_file($filename) ;
            $reg_md[$filename] = $md;
        }
        file_put_contents ( 'temp/engine.md5', serialize ( $engine_md ) );
        file_put_contents ( 'temp/page_admin.md5', serialize ( $page_admin_md ) );
        file_put_contents ( 'temp/page.md5', serialize ( $page_md ) );
        file_put_contents ( 'temp/reg.md5', serialize ( $reg_md ) );
    }

?>

<?php AdminPanel();?>

<h2><?=loca("ADM_CSUM_ENGINE");?></h2>

<table width="519">
<tr><td class=c><?=loca("ADM_CSUM_PATH");?></td><td class=c><?=loca("ADM_CSUM_DIGEST");?></td><td class=c><?=loca("ADM_CSUM_STATUS");?></td></tr>
<?php
    foreach ( $engine_files as $i=>$filename ) {
        $md = md5_file($filename) ;
        echo "<tr><td>$filename</td><td>$md</td>";

        if ( key_exists($filename, $engine_md) )
        {
            if ( $engine_md[$filename] === $md ) echo "<td><font color=lime><b>OK</b></font></td>";
            else echo "<td><font color=red><b>BAD</b></font></td>";
        }
        else echo "<td><font color=red><b>UNVERSIONED</b></font></td>";
        echo "</tr>";
    }
?>
</table>

<h2><?=loca("ADM_CSUM_ADMIN");?></h2>

<table width="519">
<tr><td class=c><?=loca("ADM_CSUM_PATH");?></td><td class=c><?=loca("ADM_CSUM_DIGEST");?></td><td class=c><?=loca("ADM_CSUM_STATUS");?></td></tr>
<?php
    foreach ( $page_admin_files as $i=>$filename ) {
        $md = md5_file($filename) ;
        echo "<tr><td>$filename</td><td>$md</td>";
        if ( $page_admin_md[$filename] === $md ) echo "<td><font color=lime><b>OK</b></font></td>";
        else echo "<td><font color=red><b>BAD</b></font></td>";
        echo "</tr>";
    }
?>
</table>

<h2><?=loca("ADM_CSUM_PAGES");?></h2>

<table width="519">
<tr><td class=c><?=loca("ADM_CSUM_PATH");?></td><td class=c><?=loca("ADM_CSUM_DIGEST");?></td><td class=c><?=loca("ADM_CSUM_STATUS");?></td></tr>
<?php
    foreach ( $page_files as $i=>$filename ) {
        $md = md5_file($filename) ;
        echo "<tr><td>$filename</td><td>$md</td>";
        if ( $page_md[$filename] === $md ) echo "<td><font color=lime><b>OK</b></font></td>";
        else echo "<td><font color=red><b>BAD</b></font></td>";
        echo "</tr>";
    }
?>
</table>

<h2><?=loca("ADM_CSUM_REG");?></h2>

<table width="519">
<tr><td class=c><?=loca("ADM_CSUM_PATH");?></td><td class=c><?=loca("ADM_CSUM_DIGEST");?></td><td class=c><?=loca("ADM_CSUM_STATUS");?></td></tr>
<?php
    foreach ( $reg_files as $i=>$filename ) {
        $md = md5_file($filename) ;
        echo "<tr><td>$filename</td><td>$md</td>";
        if ( $reg_md[$filename] === $md ) echo "<td><font color=lime><b>OK</b></font></td>";
        else echo "<td><font color=red><b>BAD</b></font></td>";
        echo "</tr>";
    }
?>
</table>

<br/>

<form action="index.php?page=admin&session=<?=$session;?>&mode=Checksum" method="POST">
<input type=submit value="<?=loca("ADM_CSUM_FIX");?>">
</form>

<?php    
}

?>