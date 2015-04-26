<?php

// Админка : контрольная сумма исходников.

function Admin_Checksum ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $engine_md = unserialize ( file_get_contents ('temp/engine.md5') );
    $page_md = unserialize ( file_get_contents ('temp/page.md5') );
    $reg_md = unserialize ( file_get_contents ('temp/reg.md5') );

    $engine_files = array (
        'ainfo.php', 
        'ally.php', 
        'api.php', 
        'battle.php', 
        'battle_engine.php', 
        'bbcode.php', 
        'bot.php', 
        'botapi.php', 
        'cache.php', 
        'coupon.php', 
        'db.php', 
        'debug.php', 
        'expedition.php', 
        'fleet.php', 
        'galaxytool.php', 
        'index.php', 
        'install.php', 
        'loca.php', 
        'maintenance.php', 
        'msg.php', 
        'notes.php', 
        'page.php', 
        'pic.php', 
        'planet.php', 
        'pranger.php', 
        'prod.php', 
        'queue.php', 
        'redir.php', 
        'uni.php', 
        'unit.php', 
        'user.php', 
        'validate.php', 
    );

    $page_files = array (
        'pages/admin.php', 
        'pages/admin_bans.php', 
        'pages/admin_battle.php', 
        'pages/admin_botedit.php', 
        'pages/admin_bots.php', 
        'pages/admin_broadcast.php', 
        'pages/admin_browse.php', 
        'pages/admin_checksum.php', 
        'pages/admin_coupons.php', 
        'pages/admin_debug.php', 
        'pages/admin_errors.php', 
        'pages/admin_expedition.php', 
        'pages/admin_fleetlogs.php', 
        'pages/admin_logins.php', 
        'pages/admin_planets.php', 
        'pages/admin_queue.php', 
        'pages/admin_reports.php', 
        'pages/admin_sim.php', 
        'pages/admin_uni.php', 
        'pages/admin_userlogs.php', 
        'pages/admin_users.php', 
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
        foreach ( $page_files as $i=>$filename ) {
            $md = md5_file($filename) ;
            $page_md[$filename] = $md;
        }
        foreach ( $reg_files as $i=>$filename ) {
            $md = md5_file($filename) ;
            $reg_md[$filename] = $md;
        }
        file_put_contents ( 'temp/engine.md5', serialize ( $engine_md ) );
        file_put_contents ( 'temp/page.md5', serialize ( $page_md ) );
        file_put_contents ( 'temp/reg.md5', serialize ( $reg_md ) );
    }

?>

<?=AdminPanel();?>

<h2>Движок</h2>

<table width="519">
<tr><td class=c>Путь к файлу</td><td class=c>Контрольная сумма</td><td class=c>Статус</td></tr>
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

<h2>Игровые страницы</h2>

<table width="519">
<tr><td class=c>Путь к файлу</td><td class=c>Контрольная сумма</td><td class=c>Статус</td></tr>
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

<h2>Система регистрации</h2>

<table width="519">
<tr><td class=c>Путь к файлу</td><td class=c>Контрольная сумма</td><td class=c>Статус</td></tr>
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
<input type=submit value="Зафиксировать контрольные суммы">
</form>

<?php    
}

?>
