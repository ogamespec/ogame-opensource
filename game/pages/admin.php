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

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( $GlobalUser['admin'] == 0 ) RedirectHome ();    // обычным пользователям доступ запрещен
UpdateQueue ( time () );
$session = $_GET['session'];
$mode = $_GET['mode'];

// ========================================================================================
// Главная страница.

function Admin_Home ()
{
    global $session;
?>
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
    </table>
<?php
}

// ========================================================================================
// Пользователи.

function Admin_Users ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    // Обработка POST-запроса.

    if ( key_exists("player_id", $_GET) ) {        // Информация об игроке
        $user = LoadUser ( $_GET['player_id'] );
        print_r ($user);
    }
    else {
        $query = "SELECT * FROM ".$db_prefix."users ORDER BY regdate DESC LIMIT 25";
        $result = dbquery ($query);

        echo "Новые пользователи:<br>\n";
        echo "<table>\n";
        echo "<tr><td class=c>Дата регистрации</td><td class=c>Главная планета</td><td class=c>Имя игрока</td></tr>\n";
        $rows = dbrows ($result);
        while ($rows--) 
        {
            $user = dbarray ( $result );
            $hplanet = GetPlanet ( $user['hplanetid'] );

            echo "<tr><th>".date ("Y-m-d H:i:s", $user['regdate'])."</th>";
            echo "<th>[".$hplanet['g'].":".$hplanet['s'].":".$hplanet['p']."] <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$hplanet['planet_id']."\">".$hplanet['name']."</a></th>";
            echo "<th><a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id']."\">".$user['oname']."</a></th></tr>\n";
        }
        echo "</table>\n";
    }

    // Поиск пользователей
}

// ========================================================================================
// Планеты.

function Admin_Planets ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $SearchResult = "";

    $buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );

    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 ) {
        $cp = $_GET['cp'];
        $action = $_GET['action'];
        $now = time();

        if ($action === "update")        // Обновить данные планеты.
        {
            $param = array (  'b1', 'b2', 'b3', 'b4', 'b12', 'b14', 'b15', 'b21', 'b22', 'b23', 'b24', 'b31', 'b33', 'b34', 'b41', 'b42', 'b43', 'b44',
                                       'd401', 'd402', 'd403', 'd404', 'd405', 'd406', 'd407', 'd408', 'd502', 'd503',
                                      'f202', 'f203', 'f204', 'f205', 'f206', 'f207', 'f208', 'f209', 'f210', 'f211', 'f212', 'f213', 'f214', 'f215',
                                      'm', 'k', 'd' );

            $query = "UPDATE ".$db_prefix."planets SET lastpeek=$now, ";
            foreach ( $param as $i=>$p ) {
                if ( $i == 0 ) $query .= "$p=".$_POST[$p];
                else $query .= ", $p=".$_POST[$p];
            }
            $query .= " WHERE planet_id=$cp;";
            dbquery ($query);
        }
        else if ( $action === "search" )        // Поиск
        {
            $searchtype = $_POST['type'];
            if ( $_POST['searchtext'] === "" ) {
                $SearchResult .= "Укажите строку для поиска<br>\n";
                $searchtype = "none";
            }
            if ( $searchtype === "playername") {
                $query = "SELECT player_id FROM ".$db_prefix."users WHERE oname LIKE '".$_POST['searchtext']."%'";
                $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = ANY ($query);";
            }
            else if ( $searchtype === "planetname") {
                $query = "SELECT * FROM ".$db_prefix."planets WHERE name LIKE '".$_POST['searchtext']."%';";
            }
            else if ( $searchtype === "allytag") {
                $query = "SELECT ally_id FROM ".$db_prefix."ally WHERE tag LIKE '".$_POST['searchtext']."%'";
                $query = "SELECT player_id FROM ".$db_prefix."users WHERE ally_id <> 0 AND ally_id = ANY ($query)";
                $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = ANY ($query);";
            }
            if ($query) $result = dbquery ($query);
            $SearchResult .= "<table>\n";
            $rows = dbrows ($result);
            if ( $rows > 0 )
            {
                while ($rows--)
                {
                    $planet = dbarray ( $result );
                    $user = LoadUser ( $planet['owner_id'] );
                    $SearchResult .= "<tr><th>".date ("Y-m-d H:i:s", $planet['date'])."</th><th>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</th>";
                    $SearchResult .= "<th><a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a></th>";
                    $SearchResult .= "<th><a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id']."\">".$user['oname']."</a></th></tr>\n";
                }
            }
            else $SearchResult .= "Ничего не найдено<br>\n";
            $SearchResult .= "</table>\n";
        }
    }

    // Обработка GET-запроса.
    if ( method () === "GET" && $GlobalUser['admin'] >= 2 ) {
        $cp = $_GET['cp'];
        $action = $_GET['action'];
        $now = time();

        if ( $action === "create_moon" )    // Создать луну
        {
            $planet = GetPlanet ($cp);
            if ( $planet['type'] > 0 && $planet['type'] < 10000 )
            {
                if ( PlanetHasMoon ($cp) == 0 ) CreatePlanet ($planet['g'], $planet['s'], $planet['p'], $planet['owner_id'], 0, 1);
            }
        }
        else if ( $action === "create_debris" )    // Создать ПО
        {
            $planet = GetPlanet ($cp);
            if ( $planet['type'] > 0 && $planet['type'] < 10000 )
            {
                if ( HasDebris ($planet['g'], $planet['s'], $planet['p']) == 0 ) CreateDebris ($planet['g'], $planet['s'], $planet['p'], $planet['owner_id']);
            }
        }
    }

    if ( key_exists("cp", $_GET) ) {     // Информация о планете.
        $planet = GetPlanet ( $_GET['cp'] );
        $user = LoadUser ( $planet['owner_id'] );
        $moon_id = PlanetHasMoon ( $planet['planet_id'] );
        $debris_id = HasDebris ( $planet['g'], $planet['s'], $planet['p'] );

        echo "<table>\n";
        echo "<form action=\"index.php?page=admin&session=$session&mode=Planets&action=update&cp=".$planet['planet_id']."\" method=\"POST\" >\n";
        echo "<tr><td class=c colspan=2>Планета \"".$planet['name']."\" (<a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id']."\">".$user['oname']."</a>)</td>\n";
        echo "       <td class=c >Постройки</td> <td class=c >Флот</td> <td class=c >Оборона</td> </tr>\n";
        echo "<tr><th><img src=\"".GetPlanetImage (UserSkin(), $planet['type'])."\">";
        if ($planet['type'] == 10000 ) echo "<br>М: ".nicenum($planet['m'])."<br>К: ".nicenum($planet['k'])."<br>";
        echo "</th><th>";
        if ( $planet['type'] > 0 && $planet['type'] < 10000 )
        {
            if ($moon_id)
            {
                $moon = GetPlanet ($moon_id);
                echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$moon['planet_id']."\"><img src=\"".GetPlanetSmallImage (UserSkin(), $moon['type'])."\"><br>\n";
                echo $moon['name'] . "</a>";
            }
            else echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&action=create_moon&cp=".$planet['planet_id']."\" >Создать луну</a>\n";
            echo "<br/>\n";
            if ($debris_id)
            {
                $debris = GetPlanet ($debris_id);
                echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$debris['planet_id']."\"><img src=\"".UserSkin()."planeten/debris.jpg\"><br>\n";
                echo $debris['name'] . "</a>";
                echo "<br>М: ".nicenum($debris['m'])."<br>К: ".nicenum($debris['k'])."<br>";
            }
            else echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&action=create_debris&cp=".$planet['planet_id']."\" >Создать поле обломков</a>\n";
        }
        else
        {
            $parent = LoadPlanet ( $planet['g'], $planet['s'], $planet['p'], 1 );
            echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$parent['planet_id']."\"><img src=\"".GetPlanetSmallImage (UserSkin(), $parent['type'])."\"><br>\n";
            echo $parent['name'] . "</a>";
        }
        echo "</th>";

        echo "<th valign=top><table>\n";
        foreach ( $buildmap as $i=>$gid) {
            echo "<tr><th>".loca("NAME_$gid")."</th><th><input type=\"text\" size=3 name=\"b$gid\" value=\"".$planet["b$gid"]."\" /></th></tr>\n";
        }
        echo "</table></th>\n";

        echo "<th valign=top><table>\n";
        foreach ( $fleetmap as $i=>$gid) {
            echo "<tr><th>".loca("NAME_$gid")."</th><th><input type=\"text\" size=6 name=\"f$gid\" value=\"".$planet["f$gid"]."\" /></th></tr>\n";
        }
        echo "</table></th>\n";

        echo "<th valign=top><table>\n";
        foreach ( $defmap as $i=>$gid) {
            echo "<tr><th>".loca("NAME_$gid")."</th><th><input type=\"text\" size=6 name=\"d$gid\" value=\"".$planet["d$gid"]."\" /></th></tr>\n";
        }
        echo "</table></th>\n";

        echo "</tr>\n";

        echo "<tr><th>Дата создания</th><th>".date ("Y-m-d H:i:s", $planet['date'])."</th> </tr>\n";
        echo "<tr><th>Последняя активность</th><th>".date ("Y-m-d H:i:s", $planet['lastakt'])."</th></tr>\n";
        echo "<tr><th>Последнее обновление</th><th>".date ("Y-m-d H:i:s", $planet['lastpeek'])."</th></tr>\n";
        echo "<tr><th>Диаметр</th><th>".nicenum($planet['diameter'])." км (".$planet['fields']." из ".$planet['maxfields']." полей)</th></tr>\n";
        echo "<tr><th>Температура</th><th>от ".$planet['temp']."°C до ".($planet['temp']+40)."°C</th></tr>\n";
        echo "<tr><th>Координаты</th><th>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</th></tr>\n";

        echo "<tr><td class=c colspan=2>Ресурсы</td></tr>\n";
        echo "<tr><th>Металл</th><th><input type=\"text\" name=\"m\" value=\"".ceil($planet['m'])."\" /></th></tr>\n";
        echo "<tr><th>Кристалл</th><th><input type=\"text\" name=\"k\" value=\"".ceil($planet['k'])."\" /></th></tr>\n";
        echo "<tr><th>Дейтерий</th><th><input type=\"text\" name=\"d\" value=\"".ceil($planet['d'])."\" /></th></tr>\n";
        echo "<tr><th>Энергия</th><th>".$planet['e']." / ".$planet['emax']."</th></tr>\n";
        echo "<tr><th>Коэффициент производства</th><th>".$planet['factor']."</th></tr>\n";

        echo "<tr><th colspan=8><input type=\"submit\" value=\"Сохранить\" /></th></tr>\n";
        echo "</form>\n";
        echo "</table>\n";
    }
    else {
        $query = "SELECT * FROM ".$db_prefix."planets ORDER BY date DESC LIMIT 25";
        $result = dbquery ($query);

        echo "    </th> \n";
        echo "   </tr> \n";
        echo "</table> \n";
        echo "Новые планеты:<br>\n";
        echo "<table>\n";
        echo "<tr><td class=c>Дата создания</td><td class=c>Координаты</td><td class=c>Планета</td><td class=c>Игрок</td></tr>\n";
        $rows = dbrows ($result);
        while ($rows--) 
        {
            $planet = dbarray ( $result );
            $user = LoadUser ( $planet['owner_id'] );

            echo "<tr><th>".date ("Y-m-d H:i:s", $planet['date'])."</th><th>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</th>";
            echo "<th><a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a></th>";
            echo "<th><a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id']."\">".$user['oname']."</a></th></tr>\n";
        }
        echo "</table>\n";

?>
       </th> 
       </tr> 
    </table>
    Искать:<br>
 <form action="index.php?page=admin&session=<?=$session;?>&mode=Planets&action=search" method="post">
 <table>
  <tr>
   <th>
    <select name="type">
     <option value="playername">Имя игрока</option>
     <option value="planetname" >Имя планеты</option>
     <option value="allytag" >Аббревиатура альянса</option>
    </select>
    &nbsp;&nbsp;
    <input type="text" name="searchtext" value=""/>
    &nbsp;&nbsp;
    <input type="submit" value="Искать" />
   </th>
  </tr>
 </table>
 </form>
<?php

        if ( $SearchResult !== "" )
        {
?>
       </th> 
       </tr> 
    </table>
    Результаты поиска:<br>
    <?=$SearchResult;?>
<?php
        }
    }
}

// ========================================================================================
// Глобальная очередь событий.

function QueueDesc ( $queue )
{
    global $session;
    $type = $queue['type'];
    $sub_id = $queue['sub_id'];
    $obj_id = $queue['obj_id'];
    $level = $queue['level'];

    switch ( $type )
    {
        case "Build":
            $planet = GetPlanet ($sub_id);
            return "Постройка '".loca("NAME_$obj_id")."' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "Demolish":
            $planet = GetPlanet ($sub_id);
            return "Снос '".loca("NAME_$obj_id")."' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "Shipyard":
            $planet = GetPlanet ($sub_id);
            return "Задание на верфи: '".loca("NAME_$obj_id") . "' ($level) на планете <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=$sub_id\">" . $planet['name'] . "</a>" ;
        case "DeleteAccount": return "Удалить аккаунт";
    }

    return "Неизвестный тип задания (type=$type, sub_id=$sub_id, obj_id=$obj_id, level=$level)";
}

function Admin_Queue ()
{
    global $session;
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."queue ORDER BY end ASC, prio DESC LIMIT 50";
    $result = dbquery ($query);

    echo "<table >\n";
    echo "<tr><td class=c>Время окончания</td><td class=c>Игрок</td><td class=c>Тип задания</td><td class=c>Описание</td><td class=c>Приоритет</td></tr>\n";

    $rows = dbrows ($result);
    while ($rows--) 
    {
        $queue = dbarray ( $result );
        $user = LoadUser ( $queue['owner_id'] );
        $player_id = $user['player_id'];
        echo "<tr><th>".date ("d.m.Y H:i:s", $queue['end'])."</th><th><a href=\"index.php?page=admin&session=$session&mode=Users&player_id=$player_id\">".$user['oname']."</a></th><th>".$queue['type']."</th><th>".QueueDesc($queue)."</th><th>".$queue['prio']."</th></tr>\n";
    }

    echo "</table>\n";
}

// ========================================================================================
// Настройки Вселенной.

function Admin_Uni ()
{
    global $session;
    $unitab = LoadUniverse ();

    print_r ($unitab);

    echo "<table >\n";
    echo "<form action=\"index.php?page=admin&session=$session&mode=Uni\" method=\"POST\" >\n";
    echo "<tr><td class=c colspan=2>Настройки Вселенной ".$unitab['num']."</td></tr>\n";
    echo "<tr><th>Дата открытия</th><th>".date ("Y-m-d H:i:s", $unitab['startdate'])."</th></tr>\n";
    echo "<tr><th>Количество игроков</th><th>".$unitab['usercount']."</th></tr>\n";
    echo "<tr><th>Максимальное количество игроков</th><th><input type=\"text\" name=\"maxusers\" maxlength=\"10\" size=\"10\" value=\"".$unitab['maxusers']."\" /></th></tr>\n";
    echo "<tr><th>Количество галактик</th><th>".$unitab['galaxies']."</th></tr>\n";
    echo "<tr><th>Количество систем в галактике</th><th>".$unitab['systems']."</th></tr>\n";
    echo "<tr><th>Скорострел</th><th><input type=\"checkbox\" name=\"rapid\"  checked=checked /></th></tr>\n";
    echo "<tr><th>Луны и Звёзды Смерти</th><th><input type=\"checkbox\" name=\"moons\"  checked=checked /></th></tr>\n";
    echo "<tr><th colspan=2><input type=\"submit\" value=\"Сохранить\" /></th></tr>\n";
    echo "</form>\n";
    echo "</table>\n";
}

// ========================================================================================
// Ошибки.

function Admin_Errors ()
{
    global $session;
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."errors ORDER BY date DESC";
    $result = dbquery ($query);

    $rows = dbrows ($result);
    while ($rows--) 
    {
        $error = dbarray ( $result );
        print_r ($error);
        echo "<br>";
    }
}

// ========================================================================================
// Отладочные сообщения.

function Admin_Debug ()
{
    global $session;
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."debug ORDER BY date DESC";
    $result = dbquery ($query);

    $rows = dbrows ($result);
    while ($rows--) 
    {
        $msg = dbarray ( $result );
        print_r ($msg);
        echo "<br>";
    }
}

// ========================================================================================

PageHeader ("admin", true);

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";
echo "<table width=\"750\" border=\"0\" cellpadding=\"0\" cellspacing=\"1\">\n\n";

if ( $mode === "Home" ) Admin_Home ();
else if ( $mode === "Users" ) Admin_Users ();
else if ( $mode === "Planets" ) Admin_Planets ();
else if ( $mode === "Queue" ) Admin_Queue ();
else if ( $mode === "Uni" ) Admin_Uni ();
else if ( $mode === "Errors" ) Admin_Errors ();
else if ( $mode === "Debug" ) Admin_Debug ();
else Admin_Home ();

echo "</table>\n";
echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ("", "", false, 81);
ob_end_flush ();
?>