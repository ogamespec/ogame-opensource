<?php

// Меню `Мой альянс`

$SearchResults = "";
$AllianzenError = "";

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

// Пользователь не состоит ни в каком альнсе, вывести меню для создания/поиска альянсов.
function AllyPage_NoAlly ()
{
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>Альянс</td></tr>\n";
    echo "<tr><th><a href=\"index.php?page=allianzen&session=".$_GET['session']."&a=1\">Основать собственный альянс</a></th>\n";
    echo "<th><a href=\"index.php?page=allianzen&session=".$_GET['session']."&a=2\">Искать альянсы</a></th></tr>\n";
    echo "</table><br><br><br><br><br>\n";
}

// Основать свой альянс.
function AllyPage_CreateAlly ($tag, $name)
{
    echo "<form action=\"index.php?page=allianzen&session=".$_GET['session']."&a=1&weiter=1\" method=POST>\n";
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>Основать альянс</td></tr>\n";
    echo "<tr><th>Аббревиатура альянса (3-8 знаков)</th><th><input type=text name=\"tag\" size=8 maxlength=8 value=\"$tag\"></th></tr>\n";
    echo "<tr><th>Название альянса (3-30 символов)</th><th><input type=text name=\"name\" size=20 maxlength=30 value=\"$name\"></th></tr>\n";
    echo "<tr><th colspan=2><input type=submit value=\"Основать\"></th></tr></table></form><br><br><br><br>\n";
}

// Искать альянсы.
function AllyPage_Search ($text, $results="")
{
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>Искать альянсы</td></tr>\n";
    echo "<tr><th>Искать</th><th>\n";
    echo "<form action=\"index.php?page=allianzen&session=".$_GET['session']."&a=2\" method=POST>\n";
    echo "<input type=text name=suchtext value=\"$text\"><input type=submit value=\"Искать\">\n";
    echo "</th></tr></form></table><br>\n";
    echo "$results\n";
    echo "<br><br><br>\n";
}

// Вывести таблицу результатов.
function AllyPage_SearchResult ($result)
{
    global $SearchResults;
    $SearchResults = "";
    $rows = dbrows ($result);
    if ($rows == 0) return;
    $SearchResults .= "<table width=519>\n";
    $SearchResults .= "<tr><td class=c colspan=3>Результаты поиска альянса</th></tr>\n";
    $SearchResults .= "<tr><th><center>Аббревиатура альянса</center></th><th><center>Название альянса</center></th><th><center>Количество членов</center></th></tr>\n";
    if ($rows > 30) $rows = 30;
    for ($i=0; $i<$rows; $i++)
    {
        $ally = dbarray ($result);
        $enum = EnumerateAlly ($ally['ally_id']);
        $players = dbrows ($enum);
        $SearchResults .= "<tr><th><center>[<a href=\"index.php?page=bewerben&session=".$_GET['session']."&allyid=".$ally['ally_id']."\">".$ally['tag']."</a>]</center></th>\n";
        $SearchResults .= "<th><center>".$ally['name']."</center></th>\n";
        $SearchResults .= "<th><center>".$players."</center></th></tr>\n";
    }
    $SearchResults .= "</table><br>\n";
}

// Главная страница.
function AllyPage_Home ()
{
    global $GlobalUser;
    $ally = LoadAlly ($GlobalUser['ally_id']);

    if ($ally['imglogo'] !== "") echo "<tr><th colspan=2><img src=\"img/preload.gif\" class=\"reloadimage\" title=\"pic.php?url=".$ally['imglogo']."\"></td></tr>\n";
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>Ваш альянс</td></tr>\n";
    echo "<tr><th>Аббревиатура</th><th>".$ally['tag']."</th></tr>\n";
    echo "<tr><th>Имя</th><th>".$ally['name']."</th></tr>\n";
    echo "<tr><th>Члены</th><th>7 (<a href=\"index.php?page=allianzen&session=".$_GET['session']."&a=4\">список членов</a>)</th></tr>\n";
    echo "<tr><th>Ваш ранг</th><th>Основатель (<a href=\"index.php?page=allianzen&session=".$_GET['session']."&a=5\">управление альянсом</a>)</th></tr>\n";
    echo "<tr><th>Общее сообщение</th><th><a href=\"index.php?page=allianzen&session=".$_GET['session']."&a=17\">Послать общее сообщение</a></th></tr>\n";
    echo "<tr><th colspan=2 height=100>\n";
    echo $ally['exttext']."\n";
    echo "</th></tr>\n";
    echo "<tr><th>Домашняя страница</th><th><a href=\"redir.php?url=".$ally['homepage']."\" target=\"_blank\">".$ally['homepage']."</a></th></tr>\n";
    echo "<tr><td class=c colspan=2>Внутренняя компетенция</th></tr>\n";
    echo "<tr><th colspan=2 height=100></th></tr>\n";
    echo "</table><br><br><br><br><br>\n";
}

// ***********************************************************

// Ранг содержит особые символы
// Разрешенные символы в названии ранга: [a-zA-Z0-9_-.]

// Обработать POST-запросы.
if ( $GlobalUser['ally_id'] == 0 )
{
    if ( $_GET['a'] == 1 && $_GET['weiter'] == 1 )    // Основать альянс.
    {
        if (mb_strlen ($_POST['tag'], "UTF-8")  < 3) $AllianzenError = "Аббревиатура альянса слишком коротка";
        else if (mb_strlen ($_POST['name'], "UTF-8")  < 3) $AllianzenError = "Название альянса слишком короткое";
        else if (IsAllyTagExist ($_POST['tag'])) $AllianzenError = "Альянс ".$_POST['tag']." к сожалению уже существует!";
        else
        {
            CreateAlly ($GlobalUser['player_id'], $_POST['tag'], $_POST['name']);
            {
                PageHeader ("allianzen");
                echo "<!-- CONTENT AREA -->\n";
                echo "<div id='content'>\n";
                echo "<center>\n";
                echo "<br/><p>Альянс ".$_POST['name']." [".$_POST['tag']."] успешно создан</p>\n";
                echo "<form method=\"post\" action=\"index.php?page=allianzen&session=".$_GET['session']."\">\n";
                echo "<input type=\"submit\" value=\"Да!\"/></form><br/><br/><br/><br/>\n";
                echo "</center>\n";
                echo "</div>\n";
                echo "<!-- END CONTENT AREA -->\n";
                PageFooter ();
                ob_end_flush ();
                exit ();
            }
        }
    }
    else if ( $_GET['a'] == 2 )        // Поиск альянса (не более 30 результатов)
    {
        if ( key_exists ('suchtext', $_POST) && $_POST['suchtext'] !== "" )
        {
            $result = SearchAllyTag ($_POST['suchtext']);
            AllyPage_SearchResult ($result);
        }
    }
}

// ***********************************************************

PageHeader ("allianzen");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";
echo "<script src=\"js/cntchar.js\" type=\"text/javascript\"></script><script src=\"js/win.js\" type=\"text/javascript\"></script>\n";

if ( $GlobalUser['ally_id'] == 0 )
{
    if ( key_exists ('a', $_GET) && $_GET['a'] == 1 ) AllyPage_CreateAlly ( $_POST['tag'], $_POST['name'] );
    else if ( key_exists ('a', $_GET) && $_GET['a'] == 2 ) AllyPage_Search ( $_POST['suchtext'], $SearchResults );
    else AllyPage_NoAlly ();
}
else
{

    AllyPage_Home ();

}

echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ("", $AllianzenError);
ob_end_flush ();
?>