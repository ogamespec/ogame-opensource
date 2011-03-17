<?php

// Меню `Друзья`

$BuddyError = "";

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

// ***********************************************************
// Страницы меню.

// Главная страница
function Buddy_Home ()
{
    echo "<table width=\"519\">\n";
    echo " <tr><td class=\"c\" colspan=\"6\">Список друзей</td></tr>\n";
    echo " <tr><th colspan=\"6\"><a href=?page=buddy&session=".$_GET['session']."&action=5>Запросы</a></th></tr>\n";
    echo " <tr><th colspan=\"6\"><a href=?page=buddy&session=".$_GET['session']."&action=6>Ваши запросы</a></th></tr>\n";
    echo " <tr>\n";
    echo "  <td class=\"c\"></td>\n";
    echo "  <td class=\"c\">Имя</td>\n";
    echo "  <td class=\"c\">Альянс</td>\n";
    echo "  <td class=\"c\">Координаты</td>\n";
    echo "  <td class=\"c\">Статус</td>\n";
    echo "  <td class=\"c\"></td>\n";
    echo " </tr>\n";
    echo " <tr><th colspan=\"6\">Друзей нет</th></tr>\n";
    echo "</table>\n";
    echo "<br><br><br><br>\n";
}

// Запросы (5)
function Buddy_Income ()
{
}

// Ваши запросы (6)
function Buddy_Outcome ()
{
    global $GlobalUser;

    echo "<table width=\"519\">\n";
    echo " <tr><td class=\"c\" colspan=\"6\">Ваши запросы</td></tr>\n";

    $result = EnumOutcomeBuddy ($GlobalUser['player_id']);
    $num = dbrows ($result);
    if ($num)
    {
        $i = 1;
        echo " <tr>\n";
        echo " <th></th>\n";
        echo " <th>Юзер</th>\n";
        echo "  <th>Альянс</th>\n";
        echo "  <th>Координаты</th>\n";
        echo "  <th>Текст</th>\n";
        echo "  <th></th>\n";
        echo " </tr>\n";
        while ($num--)
        {
            $buddy = dbarray ($result);
            $userto = LoadUser ($buddy['request_to']);
            $ally = LoadAlly ($userto['ally_id']);
            $home = GetPlanet ($userto['hplanetid']);
            echo "  <tr>\n";
            echo " <th width=\"20\">$i</th>\n";
            echo "  <th><a href=\"index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$userto['player_id']."\">".$userto['oname']."</a></th>\n";
            echo "    <th><a href=index.php?page=ainfo&session=".$_GET['session']."&allyid=".$userto['ally_id']." target='_ally'>  </a></th>\n";
            echo "  <th><a href=\"index.php?page=galaxy&galaxy=".$home['g']."&system=".$home['s']."&position=".$home['p']."&session=".$_GET['session']."\" >[".$home['g'].":".$home['s'].":".$home['p']."]</a></th>\n";
            echo "  <th>".$buddy['text']."</th>\n";
            echo "    <th width=\"100\"><a href=?page=buddy&session=".$_GET['session']."&action=4&buddy_id=".$buddy['buddy_id'].">Отозвать предложение</a></th>\n";
            echo "  </tr>\n";
            $i++;
        }
    }
    else echo " <tr>   <th colspan=\"6\">Запросов нет</th>  </tr>\n";

    echo " <tr>  <td class=\"c\" colspan=\"6\"><a href=\"?page=buddy&session=".$_GET['session']."\">Назад</a></td> </tr>\n";
    echo "</table><br><br><br><br>\n";
}

// Отправить запрос (7)
function Buddy_Request ()
{
    global $GlobalUser;
    $user = LoadUser ($_GET['buddy_id']);
    echo "<form action=\"?page=buddy&session=".$_GET['session']."&action=1&buddy_id=".$_GET['buddy_id']."\" method=\"POST\">\n";
    echo "<table width=\"519\">\n";
    echo " <tr><td class=\"c\" colspan=\"2\">Предложение подружиться</td></tr>\n";
    echo " <tr><th>Игрок</th><th>".$user['oname']."</th> </tr>\n";
    echo " <tr> <th>Текст предложения(<span id=\"cntChars\">0</span> / 5000 символов)</th>\n";
    echo " <th><textarea name=\"text\" cols=\"60\" rows=\"10\" onkeyup=\"javascript:cntchar(5000)\"></textarea></th></tr>\n";
    echo "<tr> <td class=\"c\"><a href=\"?page=buddy&session=".$_GET['session']."\">Назад</a></td>\n";
    echo " <td class=\"c\"><input type=\"submit\" value=\"Отправить\"></td></tr>\n";
    echo "</table>\n";
    echo "</form><br><br><br><br>\n";
}

// ***********************************************************

PageHeader ("buddy");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

if ( key_exists ('action', $_GET) && $_GET['action'] == 1 && $_GET['buddy_id'])    // Добавить свою заявку.
{
    $from = $GlobalUser['player_id'];
    $to = $_GET['buddy_id'];
    if ($from != $to)
    {
        $buddy_id = AddBuddy ( $from, $to, $_POST['text']);
        if ($buddy_id == 0) $BuddyError = "Предложение подружиться уже подано";
    }
    Buddy_Home ();
}
else if ( key_exists ('action', $_GET) && $_GET['action'] == 5 ) Buddy_Income ();    // Чужие запросы
else if ( key_exists ('action', $_GET) && $_GET['action'] == 6 ) Buddy_Outcome ();    // Свои запросы
else if ( key_exists ('action', $_GET) && $_GET['action'] == 7 ) Buddy_Request ();    // Окно отправки заявки.
else Buddy_Home ();

echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ("", $BuddyError);
ob_end_flush ();
?>