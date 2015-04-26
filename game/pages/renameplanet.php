<?php

// Меню планеты.

$RenameError = "";

loca_add ( "menu", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

function PlanetDestroyMenu ()
{
    global $GlobalUser;

    $aktplanet = GetPlanet ( $GlobalUser['aktplanet']);
    PageHeader ("renameplanet");

    echo "<!-- CONTENT AREA -->\n";
    echo "<div id='content'>\n";
    echo "<center>\n\n";
    echo "<h1>Переименовать/покинуть планету</h1>\n";
    echo "<form action=\"index.php?page=renameplanet&session=".$_GET['session']."&pl=".$aktplanet['planet_id']."\" method=\"POST\">\n";
    echo "<input type='hidden' name='page' value='renameplanet'>\n";
    echo "<center>\n\n";
    echo "<table width=\"519\">\n";
    echo "<tr><td class=\"c\" colspan=\"3\">Вопросы на всякий случай</td></tr>\n";
    echo "<tr><th colspan=\"3\">Уничтожение планеты [".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."] подтвердить паролем</th></tr>\n";
    echo "<tr><input type=\"hidden\" name=\"deleteid\" value =\"".$aktplanet['planet_id']."\">\n";
    echo "<th>Пароль</th><th><input type=\"password\" name=\"pw\"></th>\n";
    echo "<th><input type=\"submit\" name=\"aktion\" value=\"Удалить планету!\" alt=\"Покинуть колонию\"></th></tr>\n";
    echo "</table>\n</form>\n</center>\n\n";
    echo "<br><br><br><br>\n";
    echo "</center>\n";
    echo "</div>\n";
    echo "<!-- END CONTENT AREA -->\n";

    PageFooter ();
    ob_end_flush ();
    exit ();
}

// Обработка POST-запросов.
if ( method() === "POST" )
{
    if ( $_POST['aktion'] === "Переименовать" )
    {
        RenamePlanet ( $GlobalUser['aktplanet'], $_POST['newname'] );
        $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
    }
    else if ( $_POST['aktion'] === "Покинуть колонию" )
    {
        PlanetDestroyMenu ();
    }
    else if ( $_POST['aktion'] === "Удалить планету!" )
    {
        // Проверить пароль.
        if ( CheckPassword ( $GlobalUser['name'], $_POST['pw']) == 0 )
        {
            $RenameError = "<center>\n" . 
                                   "Пароль неверный.<BR><BR>  Если Вы забыли пароль, нажмите <A HREF=reg/mail.php>сюда</A> <BR><BR>  или  попробуйте <a\n" .
                                   "href=".hostname()." target='_top'> ещё раз</a> .<br></center>\n\n" ;
        }
        else
        {
            // Проверить принадлежит планета этому пользователю.
            $planet = GetPlanet ( intval($_POST['deleteid']) );
            if ( $planet['owner_id'] == $GlobalUser['player_id'] )
            {
                // Главную планету нельзя удалить.
                if ( intval($_POST['deleteid']) == $GlobalUser['hplanetid'] ) $RenameError = "<center>\nНельзя покинуть главную планету!<br></center>\n";
                else
                {
                    $query = "SELECT * FROM ".$db_prefix."fleet WHERE target_planet = " . intval($_POST['deleteid']) . " AND owner_id = " . $GlobalUser['player_id'];
                    $result = dbquery ( $query );
                    if ( dbrows ($result) > 0 ) $RenameError = "<center>\nВаши флоты ещё на пути к этой планете!<br></center>\n";

                    if ( $RenameError === "" )
                    {
                        $query = "SELECT * FROM ".$db_prefix."fleet WHERE start_planet = " . intval($_POST['deleteid']);
                        $result = dbquery ( $query );
                        if ( dbrows ($result) > 0 ) $RenameError = "<center>\nФлоты с этой планеты ещё не вернулись!<br></center>\n";
                    }

                    if ( $RenameError === "" )
                    {
                        $when = $now + 24*3600;
                        $moon_id = PlanetHasMoon ($planet['planet_id']);
                        if ( $moon_id )
                        {
                            $moon = GetPlanet ( $moon_id );        // Удалять только целые луны.
                            if ( $moon['type'] == 0 )
                            {
                                $query = "UPDATE ".$db_prefix."planets SET type = 10003, owner_id = 99999, date = $now, remove = $when, lastakt = $now WHERE planet_id = " . $moon_id . ";";
                                dbquery ( $query );

                                // Удалить очередь на верфи (луна).
                                $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'Shipyard' AND sub_id = " . $moon_id;
                                dbquery ( $query );
                                // Удалить очередь построек (луна).
                                $result = GetBuildQueue ($moon_id);
                                while ( $row = dbarray ($result) ) {
                                    $query = "DELETE FROM ".$db_prefix."queue WHERE (type = 'Build' OR type = 'Demolish') AND sub_id = " . $row['id'];
                                    dbquery ( $query );
                                }
                                $query = "DELETE FROM ".$db_prefix."buildqueue WHERE planet_id = " . $moon_id;
                                dbquery ( $query );
                            }
                        }
                        if ($planet['type'] == 0) $query = "UPDATE ".$db_prefix."planets SET type = 10003, owner_id = 99999, date = $now, remove = $when, lastakt = $now WHERE planet_id = " . $planet['planet_id'] . ";";
                        else $query = "UPDATE ".$db_prefix."planets SET type = 10001, owner_id = 99999, date = $now, remove = $when, lastakt = $now WHERE planet_id = " . $planet['planet_id'] . ";";
                        dbquery ( $query );

                        // Удалить очередь на верфи (планета).
                        $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'Shipyard' AND sub_id = " . $planet['planet_id'];
                        dbquery ( $query );
                        // Удалить очередь построек (планета).
                        $result = GetBuildQueue ($planet['planet_id']);
                        while ( $row = dbarray ($result) ) {
                            $query = "DELETE FROM ".$db_prefix."queue WHERE (type = 'Build' OR type = 'Demolish') AND sub_id = " . $row['id'];
                            dbquery ( $query );
                        }
                        $query = "DELETE FROM ".$db_prefix."buildqueue WHERE planet_id = " . $planet['planet_id'];
                        dbquery ( $query );

                        // Редирект на Главную планету.
                        SelectPlanet ($GlobalUser['player_id'], $GlobalUser['hplanetid']);
                        MyGoto ( "renameplanet" );
                    }
                }
            }
        }
    }
}

$name = $aktplanet['name'];
$maxlen = 20;

PageHeader ("renameplanet");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";
echo "<h1>Переименовать/покинуть планету</h1>\n";
echo "<form action=\"index.php?page=renameplanet&session=".$_GET['session']."&pl=".$aktplanet['planet_id']."\" method=\"POST\">\n";
echo "<input type='hidden' name='page' value='renameplanet'>\n";
echo "<center>\n";
echo "<table width=519>\n";
echo "  <tr>\n    <td class=\"c\" colspan=\"3\">Информация о планете</td>\n  </tr>\n";
echo "  <tr>\n    <th>Координаты</th><th>Название</th><th>Функции</th>\n  </tr>\n";
echo "  <tr>\n    <th>".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."</th>\n";
echo "    <th>".$name."</th>\n";
echo "    <th><input type=\"submit\" name=\"aktion\" value=\"Покинуть колонию\" alt=\"Покинуть колонию\"></th>\n  </tr>\n";
echo "  <tr>\n    <th>Переименовать</th>\n";
echo "  	<th><input type=\"text\" name=\"newname\" size=\"25\" maxlength=\"".$maxlen."\"><br/></th>\n";
echo "  <th><input type=\"submit\" name=\"aktion\" value=\"Переименовать\"></th>\n</tr>\n";
echo "</table>\n</form>\n";
echo "</center>\n\n";
echo "<br><br><br><br>\n</center>\n</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ("", $RenameError);
ob_end_flush ();
?>