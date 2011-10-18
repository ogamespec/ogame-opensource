<?php

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

        //print_r ( $_POST);

        if ($action === "update")        // Обновить данные планеты.
        {
            $param = array (  'b1', 'b2', 'b3', 'b4', 'b12', 'b14', 'b15', 'b21', 'b22', 'b23', 'b24', 'b31', 'b33', 'b34', 'b41', 'b42', 'b43', 'b44',
                                       'd401', 'd402', 'd403', 'd404', 'd405', 'd406', 'd407', 'd408', 'd502', 'd503',
                                      'f202', 'f203', 'f204', 'f205', 'f206', 'f207', 'f208', 'f209', 'f210', 'f211', 'f212', 'f213', 'f214', 'f215',
                                      'm', 'k', 'd', 'type' );

            $query = "UPDATE ".$db_prefix."planets SET lastpeek=$now, ";
            foreach ( $param as $i=>$p ) {
                if ( $i == 0 ) $query .= "$p=".$_POST[$p];
                else $query .= ", $p=".$_POST[$p];
            }
            $query .= " WHERE planet_id=$cp;";

            if ( key_exists ( "delete_planet", $_POST ) )        // Удалить планету. Главную планету удалить нельзя.
            {
                $planet = GetPlanet ($cp);
                $user = LoadUser ($planet['owner_id']);
                if ( $user['hplanetid'] != $cp)
                {
                    DestroyPlanet ($cp);
                    $_GET['cp'] = $user['hplanetid'];        // перенаправить на главную планету.
                }
            }
            else dbquery ($query);        // Обновить данные планеты
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
                if ( PlanetHasMoon ($cp) == 0 ) CreatePlanet ($planet['g'], $planet['s'], $planet['p'], $planet['owner_id'], 0, 1, 20);
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
        else if ( $action === "cooldown_gates" )    // Остудить ворота
        {
            $planet = GetPlanet ($cp);
            if ( $planet['type'] == 0 )
            {
                $query = "UPDATE ".$db_prefix."planets SET gate_until=0 WHERE planet_id=" . $planet['planet_id'];
                dbquery ($query);
            }
        }
        else if ( $action === "warmup_gates" )    // Нагреть ворота
        {
            $planet = GetPlanet ($cp);
            if ( $planet['type'] == 0 )
            {
                $query = "UPDATE ".$db_prefix."planets SET gate_until=".($now+59*60+59)." WHERE planet_id=" . $planet['planet_id'];
                dbquery ($query);
            }
        }
        else if ( $action === "recalc_fields" )    // Пересчитать поля
        {
            RecalcFields ($cp);
        }
        else if ( $action === "random_diam" )    // Случайный диаметр (только для планет)
        {
            $planet = GetPlanet ($cp);
            if ( GetPlanetType ($planet) == 1 )
            {
                $p = $planet['p'];
                if ($p <= 3) $diam = mt_rand ( 50, 120 ) * 72;
                else if ($p >= 4 && $p <= 6) $diam = mt_rand ( 50, 150 ) * 120;
                else if ($p >= 7 && $p <= 9) $diam = mt_rand ( 50, 120 ) * 120;
                else if ($p >= 10 && $p <= 12) $diam = mt_rand ( 50, 120 ) * 96;
                else if ($p >= 13 && $p <= 15) $diam = mt_rand ( 50, 150 ) * 96;
                $query = "UPDATE ".$db_prefix."planets SET diameter=$diam WHERE planet_id=" . $planet['planet_id'];
                dbquery ($query);
            }
        }
    }

    if ( key_exists("cp", $_GET) ) {     // Информация о планете.
        $planet = GetPlanet ( $_GET['cp'] );
        $user = LoadUser ( $planet['owner_id'] );
        $moon_id = PlanetHasMoon ( $planet['planet_id'] );
        $debris_id = HasDebris ( $planet['g'], $planet['s'], $planet['p'] );
        $now = time ();

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
            echo "<br/><br/>\n";
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
            echo "<tr><th>".loca("NAME_$gid");
            if ( $gid == 43 && $planet['type'] == 0 ) {    // управление воротами.
                if ( $now >= $planet["gate_until"] ) {    // ворота готовы
                    echo " <a href=\"index.php?page=admin&session=$session&mode=Planets&action=warmup_gates&cp=".$planet['planet_id']."\" >нагреть</a>";
                }
                else {    // ворота НЕ готовы
                    $delta = $planet["gate_until"] - $now;
                    echo " " . date ('i\m s\s', $delta) . " <a href=\"index.php?page=admin&session=$session&mode=Planets&action=cooldown_gates&cp=".$planet['planet_id']."\">остудить</a>";
                }
            }
            echo "</th><th><input type=\"text\" size=3 name=\"b$gid\" value=\"".$planet["b$gid"]."\" /></th></tr>\n";
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

        echo "<tr><th>Дата создания</th><th>".date ("Y-m-d H:i:s", $planet['date'])."</th> <td colspan=10 class=c>Картинка планеты</td></tr>";

        echo "<tr><th>Последняя активность</th><th>".date ("Y-m-d H:i:s", $planet['lastakt'])."</th> <th colspan=3 rowspan=11 valign=top> \n";
        if ($planet['type'] > 0 && $planet['type'] < 10000) {    // картинки планет.
            $RockPlanets = array ( 101, 102, 103, 104, 105, 106, 107, 108, 109, 110 );
            $JunglePlanets = array ( 201, 202, 203, 204, 205, 206, 207, 208, 209, 210 );
            $NormalPlanets = array ( 301, 302, 303, 304, 305, 306, 307 );
            $WaterPlanets = array ( 401, 402, 403, 404, 405, 406, 407, 408, 409 );
            $IcePlanets = array ( 501, 502, 503, 504, 505, 506, 507, 508, 509, 510 );
            echo "<table>";
            echo "<tr><td><nobr>";
            foreach ( $RockPlanets as $i=>$id )
            {
                echo "    <input type=\"radio\" name=\"type\" value=$id ";
                if ( $id == $planet['type'] ) echo " checked ";
                echo "  >\n";
                echo "     <img src=\"".  GetPlanetSmallImage ( "../evolution/", $id ) . "\" width=\"32px\" height=\"32px\" title=\"Каменные, позиции 1-3\" > \n";
            }
            echo "</nobr></td></tr>";
            echo "<tr><td>";
            foreach ( $JunglePlanets as $i=>$id )
            {
                echo "     <input type=\"radio\" name=\"type\" value=$id ";
                if ( $id == $planet['type'] ) echo " checked ";
                echo "  >\n";
                echo "     <img src=\"".  GetPlanetSmallImage ( "../evolution/", $id ) . "\" width=\"32px\" height=\"32px\" title=\"Джунгли, позиции 4-6\" > \n";
            }
            echo "</td></tr>";
            echo "<tr><td>";
            foreach ( $NormalPlanets as $i=>$id )
            {
                echo "     <input type=\"radio\" name=\"type\" value=$id ";
                if ( $id == $planet['type'] ) echo " checked ";
                echo "  >\n";
                echo "     <img src=\"".  GetPlanetSmallImage ( "../evolution/", $id ) . "\" width=\"32px\" height=\"32px\" title=\"Нормальные, позиции 7-9\" > \n";
            }
            echo "</td></tr>";
            echo "<tr><td>";
            foreach ( $WaterPlanets as $i=>$id )
            {
                echo "     <input type=\"radio\" name=\"type\" value=$id ";
                if ( $id == $planet['type'] ) echo " checked ";
                echo "  >\n";
                echo "     <img src=\"".  GetPlanetSmallImage ( "../evolution/", $id ) . "\" width=\"32px\" height=\"32px\" title=\"Водяные, позиции 10-12\" > \n";
            }
            echo "</td></tr>";
            echo "<tr><td>";
            foreach ( $IcePlanets as $i=>$id )
            {
                echo "     <input type=\"radio\" name=\"type\" value=$id ";
                if ( $id == $planet['type'] ) echo " checked ";
                echo "  >\n";
                echo "     <img src=\"".  GetPlanetSmallImage ( "../evolution/", $id ) . "\" width=\"32px\" height=\"32px\" title=\"Ледяные, позиции 13-15\" > \n";
            }
            echo "</td></tr>";
            echo "</table>";
        }
        else echo "<input type=\"hidden\" name=\"type\" value=\"".$planet['type']."\" >\n";
        echo "</th> </tr>\n";
        echo "<tr><th>Последнее обновление</th><th>".date ("Y-m-d H:i:s", $planet['lastpeek'])."</th></tr>\n";
        echo "<tr><th>Диаметр <br><a href=\"index.php?page=admin&session=$session&mode=Planets&action=random_diam&cp=".$planet['planet_id']."\" >новый диаметр</a>  </th><th>".nicenum($planet['diameter'])." км (".$planet['fields']." из ".$planet['maxfields']." полей) ";
        echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&action=recalc_fields&cp=".$planet['planet_id']."\" >пересчитать поля</a> ";
        echo "</th></tr>\n";
        echo "<tr><th>Температура</th><th>от ".$planet['temp']."°C до ".($planet['temp']+40)."°C</th></tr>\n";
        echo "<tr><th>Координаты</th><th>[".$planet['g'].":".$planet['s'].":".$planet['p']."]</th></tr>\n";

        echo "<tr><td class=c colspan=2>Ресурсы</td></tr>\n";
        echo "<tr><th>Металл</th><th><input type=\"text\" name=\"m\" value=\"".ceil($planet['m'])."\" /></th></tr>\n";
        echo "<tr><th>Кристалл</th><th><input type=\"text\" name=\"k\" value=\"".ceil($planet['k'])."\" /></th></tr>\n";
        echo "<tr><th>Дейтерий</th><th><input type=\"text\" name=\"d\" value=\"".ceil($planet['d'])."\" /></th></tr>\n";
        echo "<tr><th>Энергия</th><th>".$planet['e']." / ".$planet['emax']."</th></tr>\n";
        echo "<tr><th>Коэффициент производства</th><th>".$planet['factor']."</th></tr>\n";

        echo "<tr><th colspan=8><input type=\"submit\" value=\"Сохранить\" />  <input type=\"submit\" name=\"delete_planet\" value=\"Удалить\" /> </th></tr>\n";
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

?>