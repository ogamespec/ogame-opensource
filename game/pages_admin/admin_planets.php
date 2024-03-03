<?php

// Админка: Планеты.

function Admin_Planets ()
{
    global $loca_lang, $Languages;
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $SearchResult = "";

    $buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );

    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 ) {
        $cp = intval ($_GET['cp']);
        $action = $_GET['action'];
        $now = time();

        if ($action === "update")        // Обновить данные планеты.
        {
            $param = array (  'b1', 'b2', 'b3', 'b4', 'b12', 'b14', 'b15', 'b21', 'b22', 'b23', 'b24', 'b31', 'b33', 'b34', 'b41', 'b42', 'b43', 'b44',
                                       'd401', 'd402', 'd403', 'd404', 'd405', 'd406', 'd407', 'd408', 'd502', 'd503',
                                      'f202', 'f203', 'f204', 'f205', 'f206', 'f207', 'f208', 'f209', 'f210', 'f211', 'f212', 'f213', 'f214', 'f215',
                                      'm', 'k', 'd', 'g', 's', 'p', 'diameter', 'type', 'temp', 'mprod', 'kprod', 'dprod', 'sprod', 'fprod', 'ssprod' );
            $moon_param = array ( 'g', 's', 'p' );

            $query = "UPDATE ".$db_prefix."planets SET lastpeek=$now, ";
            foreach ( $param as $i=>$p ) {
                if ( strpos ( $p, "prod") ) {
                    if (key_exists($p, $_POST)) $query .= ", $p='".$_POST[$p]."'";
                }
                else {
                    if ( $i == 0 ) $query .= "$p=".intval($_POST[$p]);
                    else $query .= ", $p=".intval($_POST[$p]);
                }
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
            else {                                        // Обновить данные планеты

                $moon_id = PlanetHasMoon ( $cp );        // Переместить луну за планетой.
                if ( $moon_id )
                {
                    $mquery = "UPDATE ".$db_prefix."planets SET lastpeek=$now, ";
                    foreach ( $moon_param as $i=>$p ) {
                        if ( $i == 0 ) $mquery .= "$p=".intval($_POST[$p]);
                        else $mquery .= ", $p=".intval($_POST[$p]);
                    }
                    $mquery .= " WHERE planet_id=$moon_id;";
                    dbquery ($mquery);
                }

                dbquery ($query);
                RecalcFields ($cp);
            }
        }
        else if ( $action === "search" )        // Поиск
        {
            $searchtype = $_POST['type'];
            if ( $_POST['searchtext'] === "" ) {
                $SearchResult .= loca("ADM_PLANET_SPECIFY_TEXT") . "<br>\n";
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
                    $SearchResult .= "<tr><th>".date ("Y-m-d H:i:s", $planet['date'])."</th><th>".AdminPlanetCoord($planet)."</th>";
                    $SearchResult .= "<th><a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a></th>";
                    $SearchResult .= "<th><a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id']."\">".$user['oname']."</a></th></tr>\n";
                }
            }
            else $SearchResult .= loca("ADM_PLANET_NOT_FOUND") . "<br>\n";
            $SearchResult .= "</table>\n";
        }
    }

    // Обработка GET-запроса.
    if ( method () === "GET" && $GlobalUser['admin'] >= 2 ) {
        if ( key_exists('cp', $_GET) ) $cp = intval ($_GET['cp']);
        else $cp = 0;
        
        if ( key_exists('action', $_GET) && $cp ) $action = $_GET['action'];
        else $action = "";
        
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
                $coltab = LoadColonySettings();
                if ($p <= 3) $diam = mt_rand ( $coltab['t1_a'], $coltab['t1_b'] ) * $coltab['t1_c'];
                else if ($p >= 4 && $p <= 6) $diam = mt_rand ( $coltab['t2_a'], $coltab['t2_b'] ) * $coltab['t2_c'];
                else if ($p >= 7 && $p <= 9) $diam = mt_rand ( $coltab['t3_a'], $coltab['t3_b'] ) * $coltab['t3_c'];
                else if ($p >= 10 && $p <= 12) $diam = mt_rand ( $coltab['t4_a'], $coltab['t4_b'] ) * $coltab['t4_c'];
                else if ($p >= 13 && $p <= 15) $diam = mt_rand ( $coltab['t5_a'], $coltab['t5_b'] ) * $coltab['t5_c'];
                $query = "UPDATE ".$db_prefix."planets SET diameter=$diam WHERE planet_id=" . $planet['planet_id'];
                dbquery ($query);
            }
        }
    }

    if ( key_exists("cp", $_GET) ) {     // Информация о планете.
        $planet = GetPlanet ( intval ($_GET['cp']) );
        if (!$planet) {
            Error ( va(loca("ADM_PLANET_ERROR_LOAD"), intval ($_GET['cp'])) );
        }
        $user = LoadUser ( $planet['owner_id'] );
        $moon_id = PlanetHasMoon ( $planet['planet_id'] );
        $debris_id = HasDebris ( $planet['g'], $planet['s'], $planet['p'] );
        $now = time ();

        // Парсер шпионских докладов.
?>
<script>

function php_str_replace(search, replace, subject) {
    // http://kevin.vanzonneveld.net
    var s = subject;
    var ra = r instanceof Array, sa = s instanceof Array;
    var f = [].concat(search);
    var r = [].concat(replace);
    var i = (s = [].concat(s)).length;
    var j = 0;
    while (j = 0, i--) {
        if (s[i]) {
            while (s[i] = (s[i]+'').split(f[j]).join(ra ? r[j] || '' : r[0]), ++j in f){};
        }
    }
    return sa ? s : s[0];
}

function spio ()
{
    global $GlobalUni;

    //
    // Перечислить все технологии для всех языков, а также ресурсы
    //

    var TechNames = {
<?php

    foreach ( $Languages as $lang => $langname ) {
        loca_add ( "common", $lang );
        loca_add ( "technames", $lang );
    }

    $old_lang = $loca_lang;
    foreach ( $Languages as $lang => $langname ) {
        $loca_lang = $lang;
        foreach ( $buildmap as $i=>$gid ) echo "\"".loca("NAME_$gid")."\": $gid, ";
        foreach ( $fleetmap as $i=>$gid ) echo "\"".loca("NAME_$gid")."\": $gid, ";
        foreach ( $defmap as $i=>$gid ) echo "\"".loca("NAME_$gid")."\": $gid, ";
    }

?>
    };
    var ResNames = {
<?php
    foreach ( $Languages as $lang => $langname ) {
        $loca_lang = $lang;        
        echo "\"".loca("METAL")."\": 'm', ";
        echo "\"".loca("CRYSTAL")."\": 'k', ";
        echo "\"".loca("DEUTERIUM")."\": 'd', ";
    }

    $loca_lang = $old_lang;
?>
    };

    var text = document.getElementById ("spiotext" ).value;
    text = php_str_replace (".", "", text);
    text = php_str_replace (":", "", text);

    for ( var name in TechNames ) {
        var id = TechNames[name];
        pos = text.indexOf ( name );
        if ( pos > 0 ) {
            obj = text.substr ( pos );
            found = obj.match ("("+name+"[\\s]+)([0-9]{1,})");
            document.getElementById ( "obj" + id ) . value = parseInt(found[2]);
        }
    }

    for ( var name in ResNames ) {
        var id = ResNames[name];
        pos = text.indexOf ( name );
        if ( pos > 0 ) {
            obj = text.substr ( pos );
            found = obj.match ("("+name+"[\\s]+)([0-9]{1,})");
            document.getElementById ( "obj" + id ) . value = parseInt(found[2]);
        }
    }

}

function reset ()
{
    var ids = [
<?php
    foreach ( $buildmap as $i=>$gid ) echo "$gid, ";
    foreach ( $fleetmap as $i=>$gid ) echo "$gid, ";
    foreach ( $defmap as $i=>$gid ) echo "$gid, ";
?>
    ];

    for ( var i in ids ) {
        document.getElementById ( "obj" + ids[i] ) . value = 0;
    }
}
</script>

<?php

        AdminPanel();

        echo "<table>\n";
        echo "<form action=\"index.php?page=admin&session=$session&mode=Planets&action=update&cp=".$planet['planet_id']."\" method=\"POST\" >\n";
        echo "<tr><td class=c colspan=2>".loca("ADM_PLANET_PLANET")." \"".$planet['name']."\" (<a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id']."\">".$user['oname']."</a>)</td>\n";
        echo "       <td class=c >".loca("ADM_PLANET_BUILDINGS")."</td> <td class=c >".loca("ADM_PLANET_FLEET")."</td> <td class=c >".loca("ADM_PLANET_DEFENSE")."</td> </tr>\n";
        echo "<tr><th><img src=\"".GetPlanetImage (UserSkin(), $planet)."\"> <br>".loca("ADM_PLANET_TYPE").": " . $planet['type'];
        $pp = PlanetPrice ( $planet );
        echo "<br>".loca("ADM_PLANET_POINTS").": " . nicenum($pp['points'] / 1000) ;
        echo "<br>".loca("ADM_PLANET_BUILDINGS").": " . nicenum( ($pp['points'] - ($pp['fleet_pts']+$pp['defense_pts']) ) / 1000) ;
        echo "<br>".loca("ADM_PLANET_FLEET").": " . nicenum($pp['fleet_pts'] / 1000) ;
        echo "<br>".loca("ADM_PLANET_DEFENSE").": " . nicenum($pp['defense_pts'] / 1000) ;
        if ($planet['type'] == 10000 ) echo "<br>М: ".nicenum($planet['m'])."<br>К: ".nicenum($planet['k'])."<br>";
        echo "</th><th>";
        if ( $planet['type'] > 0 && $planet['type'] < 10000 )
        {
            if ($moon_id)
            {
                $moon = GetPlanet ($moon_id);
                echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$moon['planet_id']."\"><img src=\"".GetPlanetSmallImage (UserSkin(), $moon)."\"><br>\n";
                echo $moon['name'] . "</a>";
            }
            else echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&action=create_moon&cp=".$planet['planet_id']."\" >".loca("ADM_PLANET_ADD_MOON")."</a>\n";
            echo "<br/><br/>\n";
            if ($debris_id)
            {
                $debris = GetPlanet ($debris_id);
                echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$debris['planet_id']."\"><img src=\"".UserSkin()."planeten/debris.jpg\"><br>\n";
                echo $debris['name'] . "</a>";
                echo "<br>М: ".nicenum($debris['m'])."<br>К: ".nicenum($debris['k'])."<br>";
            }
            else echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&action=create_debris&cp=".$planet['planet_id']."\" >".loca("ADM_PLANET_ADD_DF")."</a>\n";
        }
        else
        {
            $parent = LoadPlanet ( $planet['g'], $planet['s'], $planet['p'], 1 );
            echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$parent['planet_id']."\"><img src=\"".GetPlanetSmallImage (UserSkin(), $parent)."\"><br>\n";
            echo $parent['name'] . "</a>";
        }
?>
        <br><br><textarea rows=10 cols=10 id="spiotext"></textarea>
        <a href="#" onclick="javascript:spio();"><?=loca("ADM_PLANET_PARSE_SPY_REPORT");?></a> <br>
        <a href="#" onclick="javascript:reset();"><?=loca("ADM_PLANET_RESET_PARSER");?></a>
<?php
        echo "</th>";

        echo "<th valign=top><table>\n";
        foreach ( $buildmap as $i=>$gid) {
            echo "<tr><th>".loca("NAME_$gid");
            if ( $gid == 43 && $planet['type'] == 0 ) {    // управление воротами.
                if ( $now >= $planet["gate_until"] ) {    // ворота готовы
                    echo " <a href=\"index.php?page=admin&session=$session&mode=Planets&action=warmup_gates&cp=".$planet['planet_id']."\" >".loca("ADM_PLANET_GATE_WARMUP")."</a>";
                }
                else {    // ворота НЕ готовы
                    $delta = $planet["gate_until"] - $now;
                    echo " " . date ('i\m s\s', $delta) . " <a href=\"index.php?page=admin&session=$session&mode=Planets&action=cooldown_gates&cp=".$planet['planet_id']."\">".loca("ADM_PLANET_GATE_COOLDOWN")."</a>";
                }
            }
            echo "</th><th><nobr><input id=\"obj$gid\" type=\"text\" size=3 name=\"b$gid\" value=\"".$planet["b$gid"]."\" />";

            // управление шахтами и выработкой энергии.
            if ( $gid == 1 && $planet['type'] != 0 ) {
                echo "<select name='mprod'>\n";
                for ($prc=0; $prc<=1; $prc+=0.1) {
                    echo "<option value='$prc' ";
                    if ( $planet["mprod"] == $prc."" ) echo " selected";
                    echo ">".($prc * 100)."</option>\n";
                }
                echo "</select>\n";
            }
            if ( $gid == 2 && $planet['type'] != 0 ) {
                echo "<select name='kprod'>\n";
                for ($prc=0; $prc<=1; $prc+=0.1) {
                    echo "<option value='$prc' ";
                    if ( $planet["kprod"] == $prc."" ) echo " selected";
                    echo ">".($prc * 100)."</option>\n";
                }
                echo "</select>\n";
            }
            if ( $gid == 3 && $planet['type'] != 0 ) {
                echo "<select name='dprod'>\n";
                for ($prc=0; $prc<=1; $prc+=0.1) {
                    echo "<option value='$prc' ";
                    if ( $planet["dprod"] == $prc."" ) echo " selected";
                    echo ">".($prc * 100)."</option>\n";
                }
                echo "</select>\n";
            }
            if ( $gid == 4 && $planet['type'] != 0 ) {
                echo "<select name='sprod'>\n";
                for ($prc=0; $prc<=1; $prc+=0.1) {
                    echo "<option value='$prc' ";
                    if ( $planet["sprod"] == $prc."" ) echo " selected";
                    echo ">".($prc * 100)."</option>\n";
                }
                echo "</select>\n";
            }
            if ( $gid == 12 && $planet['type'] != 0 ) {
                echo "<select name='fprod'>\n";
                for ($prc=0; $prc<=1; $prc+=0.1) {
                    echo "<option value='$prc' ";
                    if ( $planet["fprod"] == $prc."" ) echo " selected";
                    echo ">".($prc * 100)."</option>\n";
                }
                echo "</select>\n";
            }

            echo "</nobr></th></tr>\n";
        }
        echo "</table></th>\n";

        echo "<th valign=top><table>\n";
        foreach ( $fleetmap as $i=>$gid) {
            echo "<tr><th>".loca("NAME_$gid")."</th><th><nobr><input id=\"obj$gid\" type=\"text\" size=6 name=\"f$gid\" value=\"".$planet["f$gid"]."\" />";
            if ( $gid == 212 && $planet['type'] != 0 ) {
                echo "<select name='ssprod'>\n";
                for ($prc=0; $prc<=1; $prc+=0.1) {
                    echo "<option value='$prc' ";
                    if ( $planet["ssprod"] == $prc."" ) echo " selected";
                    echo ">".($prc * 100)."</option>\n";
                }
                echo "</select>\n";
            }
            echo "</nobr></th></tr>\n";
        }
        echo "</table></th>\n";

        echo "<th valign=top><table>\n";
        foreach ( $defmap as $i=>$gid) {
            echo "<tr><th>".loca("NAME_$gid")."</th><th><input id=\"obj$gid\" type=\"text\" size=6 name=\"d$gid\" value=\"".$planet["d$gid"]."\" /></th></tr>\n";
        }
        echo "</table></th>\n";

        echo "</tr>\n";

        echo "<tr><th>".loca("ADM_PLANET_DATE_CREATE")."</th><th>".date ("Y-m-d H:i:s", $planet['date'])."</th> <td colspan=10 class=c>".loca("ADM_PLANET_QUEUE")."</td></tr>";
        echo "<tr><th>".loca("ADM_PLANET_DATE_REMOVE")."</th><th>".date ("Y-m-d H:i:s", $planet['remove'])."</th> <th colspan=3 rowspan=12 valign=top style='text-align: left;'> ";

        $query = "SELECT * FROM ".$db_prefix."buildqueue WHERE planet_id = ".$planet['planet_id']." ORDER BY list_id ASC";
        $result = dbquery ($query);
        $anz = dbrows ($result);
        echo "<table>";
        $bxx = 1; $duration = 0;
        while ( $row = dbarray ($result) ) {
            echo "<tr><td> <table><tr><th><div id='bxx".$bxx."' title='".($row['end'] - $row['start'] - ($now-($row['start'] + $duration)))."' star='".$duration."'></th>";
            echo "<tr><th>".date ("d.m.Y H:i:s", $row['end'] + $duration)."</th></tr></table></td>";
            echo "<td><img width='32px' src='".UserSkin () . "gebaeude/".$row['tech_id'].".gif'></td>";
            echo "<td><b>".loca("NAME_".$row['tech_id'])."</b><br>".va(loca("ADM_PLANET_LEVEL"), $row['level'])."</td></tr>";
            $bxx++;
            $duration += $row['end'] - $row['start'];
        }
        echo "</table>";
        echo "<script language=javascript>anz=$anz;t();</script>\n";
?>

<?php
        echo "</th> </tr>";
        echo "<tr><th>".loca("ADM_PLANET_LASTAKT")."</th><th>".date ("Y-m-d H:i:s", $planet['lastakt'])."</th>  \n";
        echo "<input type=\"hidden\" name=\"type\" value=\"".$planet['type']."\" >\n";
        echo "</th> </tr>\n";
        echo "<tr><th>".loca("ADM_PLANET_LASTUPD")."</th><th>".date ("Y-m-d H:i:s", $planet['lastpeek'])."</th></tr>\n";
        echo "<tr><th>".loca("ADM_PLANET_DIAM")." <br><a href=\"index.php?page=admin&session=$session&mode=Planets&action=random_diam&cp=".$planet['planet_id']."\" >".loca("ADM_PLANET_NEW_DIAM")."</a>  </th><th><input size=5 type=\"text\" name=\"diameter\" value=\"".$planet['diameter']."\" /> ".loca("ADM_PLANET_KM")." (".$planet['fields']." ".loca("ADM_PLANET_FIELDS_FROM")." ".$planet['maxfields']." ".loca("ADM_PLANET_FIELDS").") ";
        echo "<a href=\"index.php?page=admin&session=$session&mode=Planets&action=recalc_fields&cp=".$planet['planet_id']."\" >".loca("ADM_PLANET_RECALC_FIELDS")."</a> ";
        echo "</th></tr>\n";
        echo "<tr><th>".loca("ADM_PLANET_TEMP")."</th><th>".loca("ADM_PLANET_TEMP_FROM")." <input size=5 type=\"text\" name=\"temp\" value=\"".$planet['temp']."\" />°C ".loca("ADM_PLANET_TEMP_TO")." ".($planet['temp']+40)."°C</th></tr>\n";
        echo "<tr><th>".loca("ADM_PLANET_COORD")."</th><th>[<input type=\"text\" name=\"g\" value=\"".$planet['g']."\" size=1 />:<input type=\"text\" name=\"s\" value=\"".$planet['s']."\" size=2 />:<input type=\"text\" name=\"p\" value=\"".$planet['p']."\" size=1 />]</th></tr>\n";

        echo "<tr><td class=c colspan=2>".loca("ADM_PLANET_RESOURCES")."</td></tr>\n";
        echo "<tr><th>".loca("METAL")."</th><th><input id=\"objm\" type=\"text\" name=\"m\" value=\"".ceil($planet['m'])."\" /></th></tr>\n";
        echo "<tr><th>".loca("CRYSTAL")."</th><th><input id=\"objk\" type=\"text\" name=\"k\" value=\"".ceil($planet['k'])."\" /></th></tr>\n";
        echo "<tr><th>".loca("DEUTERIUM")."</th><th><input id=\"objd\" type=\"text\" name=\"d\" value=\"".ceil($planet['d'])."\" /></th></tr>\n";
        echo "<tr><th>".loca("ENERGY")."</th><th>".$planet['e']." / ".$planet['emax']."</th></tr>\n";
        echo "<tr><th>".loca("ADM_PLANET_FACTOR")."</th><th>".$planet['factor']."</th></tr>\n";

        echo "<tr><th colspan=8><input type=\"submit\" value=\"".loca("ADM_PLANET_SAVE")."\" />  <input type=\"submit\" name=\"delete_planet\" value=\"".loca("ADM_PLANET_REMOVE")."\" /> </th></tr>\n";
        echo "</form>\n";
        echo "</table>\n";
    }
    else {
        $query = "SELECT * FROM ".$db_prefix."planets ORDER BY date DESC LIMIT 25";
        $result = dbquery ($query);

        AdminPanel();

        echo "    </th> \n";
        echo "   </tr> \n";
        echo "</table> \n";
        echo loca("ADM_PLANET_NEW_PLANETS") . "<br>\n";
        echo "<table>\n";
        echo "<tr><td class=c>".loca("ADM_PLANET_DATE_CREATE")."</td><td class=c>".loca("ADM_PLANET_COORD")."</td><td class=c>".loca("ADM_PLANET_PLANET")."</td><td class=c>".loca("ADM_PLANET_PLAYER")."</td></tr>\n";
        $rows = dbrows ($result);
        while ($rows--) 
        {
            $planet = dbarray ( $result );
            $user = LoadUser ( $planet['owner_id'] );

            echo "<tr><th>".date ("Y-m-d H:i:s", $planet['date'])."</th><th>".AdminPlanetCoord($planet)."</th>";
            echo "<th><a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a></th>";
            echo "<th>".AdminUserName($user)."</th></tr>\n";
        }
        echo "</table>\n";

?>
       </th> 
       </tr> 
    </table>
    <?=loca("ADM_PLANET_SEARCH");?>:<br>
 <form action="index.php?page=admin&session=<?php echo $session;?>&mode=Planets&action=search" method="post">
 <table>
  <tr>
   <th>
    <select name="type">
     <option value="playername"><?=loca("ADM_PLANET_PLAYER_NAME");?></option>
     <option value="planetname" ><?=loca("ADM_PLANET_NAME");?></option>
     <option value="allytag" ><?=loca("ADM_PLANET_ALLY_TAG");?></option>
    </select>
    &nbsp;&nbsp;
    <input type="text" name="searchtext" value=""/>
    &nbsp;&nbsp;
    <input type="submit" value="<?=loca("ADM_PLANET_SEARCH");?>" />
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
    <?=loca("ADM_PLANET_SEARCH_RESULT");?><br>
    <?php echo $SearchResult;?>
<?php
        }
    }
}

?>