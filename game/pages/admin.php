<?php

// Панель администратора.
// Главная панель представляет собой типичную админскую панель с категориями.

// К админке имеют доступ только специальные пользователи: операторы и админы.

// Категории админки (GET параметр mode). К некоторым категориям операторы не могут получить доступ.
// - Контроль полётов (все)
// - История переходов (только админ)
// - Жалобы (все)
// - Баны (все)
// - Пользователи (операторы могут только смотреть и часть изменять (например отключать проверку IP), админ может изменять)
// - Планеты (операторы могут только смотреть и часть изменять (например названия планет), админ может изменять)
// - Задания (только админ)
// - Настройки Вселенной (только админ)
// - Ошибки (только админ)

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( $GlobalUser['admin'] == 0 ) RedirectHome ();    // обычным пользователям доступ запрещен

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "technames", $GlobalUser['lang'] );
loca_add ( "fleetorder", $GlobalUser['lang'] );

UpdateQueue ( time () );
$session = $_GET['session'];
$mode = $_GET['mode'];

// Админ-панель для быстрого перехода.
function AdminPanel ()
{
    global $session;
?>

<table><tr><td>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Fleetlogs"><img src="img/admin_fleetlogs.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Контроль полётов</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Browse"><img src="img/admin_browse.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>История переходов</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Reports"><img src="img/admin_report.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Жалобы</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Bans"><img src="img/admin_ban.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Баны</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Users"><img src="img/admin_users.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Пользователи</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Planets"><img src="img/admin_planets.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Планеты</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Queue"><img src="img/admin_queue.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Задания</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Uni"><img src="img/admin_uni.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Настройки Вселенной</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Errors"><img src="img/admin_error.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Ошибки</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Debug"><img src="img/admin_debug.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Отладочные сообщения</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=BattleSim"><img src="img/admin_sim.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Симулятор</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Broadcast"><img src="img/admin_broadcast.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Общее сообщение</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Expedition"><img src="<?=hostname();?>evolution/gebaeude/210.gif" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Настройки экспедиции</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Logins"><img src="img/admin_logins.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Логины</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Checksum"><img src="img/admin_checksum.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Целостность кода</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=Bots"><img src="img/admin_bots.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Управление ботами</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

<a href="index.php?page=admin&session=<?=$session;?>&mode=BattleReport"><img src="img/admin_battle.png" width='32' height='32'
onmouseover="return overlib('<center><font size=1 color=white><b>Боевые доклады</b></center>', LEFT, WIDTH, 150);" onmouseout='return nd();'></a>

</td></tr></table><br/>

<?php
}

// ========================================================================================
// Главная страница.

function Admin_Home ()
{
    global $session;
?>
    <br>
    <br>
    <br>
    <br>
    <br>

    <table width=100% border="0" cellpadding="0" cellspacing="1" align="top" class="s">
    <tr>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Fleetlogs"><img src="img/admin_fleetlogs.png"><br>Контроль полётов</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Browse"><img src="img/admin_browse.png"><br>История переходов</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Reports"><img src="img/admin_report.png"><br>Жалобы</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Bans"><img src="img/admin_ban.png"><br>Баны</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Users"><img src="img/admin_users.png"><br>Пользователи</a></th>
    </tr>
    <tr>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Planets"><img src="img/admin_planets.png"><br>Планеты</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Queue"><img src="img/admin_queue.png"><br>Задания</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Uni"><img src="img/admin_uni.png"><br>Настройки Вселенной</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Errors"><img src="img/admin_error.png"><br>Ошибки</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Debug"><img src="img/admin_debug.png"><br>Отладочные сообщения</a></th>
    </tr>
    <tr>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=BattleSim"><img src="img/admin_sim.png"><br>Симулятор</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Broadcast"><img src="img/admin_broadcast.png"><br>Общее сообщение</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Expedition"><img src="<?=hostname();?>evolution/gebaeude/210.gif"><br>Настройки экспедиции</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Logins"><img src="img/admin_logins.png"><br>Логины</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Checksum"><img src="img/admin_checksum.png"><br>Целостность кода</a></th>
    </tr>
    <tr>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=Bots"><img src="img/admin_bots.png"><br>Управление ботами</a></th>
    <th><a href="index.php?page=admin&session=<?=$session;?>&mode=BattleReport"><img src="img/admin_battle.png"><br>Боевые доклады</a></th>
    </tr>
    </table>
<?php
}

include "admin_fleetlogs.php";
include "admin_browse.php";
include "admin_reports.php";
include "admin_bans.php";
include "admin_users.php";
include "admin_planets.php";
include "admin_queue.php";
include "admin_uni.php";
include "admin_errors.php";
include "admin_debug.php";
include "admin_sim.php";
include "admin_broadcast.php";
include "admin_expedition.php";
include "admin_logins.php";
include "admin_checksum.php";
include "admin_bots.php";
include "admin_battle.php";

// ========================================================================================

PageHeader ("admin", true);

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";
echo "<table width=\"750\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n\n";

if ( $mode === "Home" ) Admin_Home ();
else if ( $mode === "Fleetlogs" ) Admin_Fleetlogs ();
else if ( $mode === "Browse" ) Admin_Browse ();
else if ( $mode === "Reports" ) Admin_Reports ();
else if ( $mode === "Bans" ) Admin_Bans ();
else if ( $mode === "Users" ) Admin_Users ();
else if ( $mode === "Planets" ) Admin_Planets ();
else if ( $mode === "Queue" ) Admin_Queue ();
else if ( $mode === "Uni" ) Admin_Uni ();
else if ( $mode === "Errors" ) Admin_Errors ();
else if ( $mode === "Debug" ) Admin_Debug ();
else if ( $mode === "BattleSim" ) Admin_BattleSim ();
else if ( $mode === "Broadcast" ) Admin_Broadcast ();
else if ( $mode === "Expedition" ) Admin_Expedition ();
else if ( $mode === "Logins" ) Admin_Logins ();
else if ( $mode === "Checksum" ) Admin_Checksum ();
else if ( $mode === "Bots" ) Admin_Bots ();
else if ( $mode === "BattleReport" ) Admin_BattleReport ();
else Admin_Home ();

echo "</table>\n";
echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ("", "", false, 0);
ob_end_flush ();
?>