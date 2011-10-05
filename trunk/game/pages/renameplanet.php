<?php

// Меню планеты.

$RenameError = "";

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
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
if ( key_exists("page", $_POST) && $_POST['page'] === "renameplanet")
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
                                   "Пароль неверный.<BR><BR>  Если Вы забыли пароль, нажмите <A HREF=mail.php>сюда</A> <BR><BR>  или  попробуйте <a\n" .
                                   "href=".$Host." target='_top'> ещё раз</a> .<br></center>\n\n" ;
        }
        else
        {
            // Проверить принадлежит планета этому пользователю.
            $planet = GetPlanet ( $_POST['deleteid'] );
            if ( $planet['owner_id'] == $GlobalUser['player_id'] )
            {
                // Главную планету нельзя удалить.
                if ( $_POST['deleteid'] == $GlobalUser['hplanetid'] ) $RenameError = "<center>\nНельзя покинуть главную планету!<br></center>\n";
                else
                {
                    DestroyPlanet ( $_POST['deleteid'], 48 );
                    // Если у планеты есть луна, покинуть её тоже.
                    $moonid = PlanetHasMoon ( $_POST['deleteid'] );
                    if ( $moonid ) DestroyPlanet ( $moonid, 48 );
                    // Редирект на Главную планету.
                    SelectPlanet ($GlobalUser['player_id'], $GlobalUser['hplanetid']);
                }
            }
            else $RenameError = "<center>\nЧужие планеты нельзя удалять!<br></center>\n";
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