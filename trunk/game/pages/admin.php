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

// Вернуть имя игрока со ссылкой на страницу редактирования и статусом (ишка, РО и пр.)
function AdminUserName ($user)
{
    global $session;

    $name = $user['oname'];

    $week = time() - 604800;
    $week3 = time() - 604800*3;

    $status = "";
    if ( $user['lastclick'] <= $week ) $status .= "i";
    if ( $user['lastclick'] <= $week3 ) $status .= "I";
    if ( $user['vacation'] ) $status .= "РО";
    if ( $user['banned'] ) $status .= "з";
    if ( $user['noattack'] ) $status .= "А";
    if ( $user['disable'] ) $status .= "g";
    if ( $status !== "" ) $name .= " ($status)";

    if ( $user['disable'] ) $name = "<font color=orange>$name</font>";
    else if ( $user['banned'] ) $name = "<font color=red>$name</font>";
    else if ( $user['noattack'] ) $name = "<font color=yellow>$name</font>";
    else if ( $user['vacation'] ) $name = "<font color=skyBlue>$name</font>";
    else if ( $user['lastclick'] <= $week3 ) $name = "<font color=#999999>$name</font>";
    else if ( $user['lastclick'] <= $week ) $name = "<font color=#cccccc>$name</font>";

    $name = "<a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id']."\">$name</a>";
    return $name;
}

// Вернуть название планеты со ссылкой на админку.
function AdminPlanetName ($planet)
{
    global $session;
    return "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a>";
}

// Вернуть строку координат планеты со ссылкой на галактику
function AdminPlanetCoord ($p)
{
    global $session;
    return "[<a href=\"index.php?page=galaxy&session=$session&galaxy=".$p['g']."&system=".$p['s']."\">".$p['g'].":".$p['s'].":".$p['p']."</a>]";
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

// ========================================================================================

PageHeader ("admin", true);

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";
echo "<table width=\"750\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n\n";

if ( $mode === "Home" ) Admin_Home ();
else if ( $mode === "Fleetlogs" ) Admin_Fleetlogs ();
else if ( $mode === "Browse" ) Admin_Browse ();
else if ( $mode === "Bans" ) Admin_Bans ();
else if ( $mode === "Users" ) Admin_Users ();
else if ( $mode === "Planets" ) Admin_Planets ();
else if ( $mode === "Queue" ) Admin_Queue ();
else if ( $mode === "Uni" ) Admin_Uni ();
else if ( $mode === "Errors" ) Admin_Errors ();
else if ( $mode === "Debug" ) Admin_Debug ();
else if ( $mode === "BattleSim" ) Admin_BattleSim ();
else if ( $mode === "Broadcast" ) Admin_Broadcast ();
else Admin_Home ();

echo "</table>\n";
echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ("", "", false, 0);
ob_end_flush ();
?>