<?php

// Меню `Друзья`

$BuddyError = "";

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval ($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( &$aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

// ***********************************************************
// Страницы меню.

// Главная страница
function Buddy_Home ()
{
    global $GlobalUser;
    global $session;
    $now = time ();

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

    $result = EnumBuddy ($GlobalUser['player_id']);
    $num = dbrows ($result);
    if ($num)
    {
        $i = 1;
        while ($num--)
        {
            $buddy = dbarray ($result);
            $user_id = $buddy['request_from'] == $GlobalUser['player_id'] ? $buddy['request_to'] : $buddy['request_from'];
            $user = LoadUser ($user_id);
            $home = GetPlanet ($user['hplanetid']);
            echo "<tr>\n";
            echo " <th width=\"20\">$i</th>\n";
            echo " <th><a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\">".$user['oname']."</a></th>\n";
            echo "  <th><a href=ainfo.php?allyid=".$user['ally_id']." target='_ally'> ";
            if ($user['ally_id'] > 0)
            {
                $ally = LoadAlly ($user['ally_id']);
                echo $ally['tag'];
                if ($user['allyrank'] == 0 ) echo "  (G)";
            }
            echo "</a></th>\n";
            echo "  <th><a href=\"index.php?page=galaxy&galaxy=".$home['g']."&system=".$home['s']."&position=".$home['p']."&session=$session\" >[".$home['g'].":".$home['s'].":".$home['p']."]</a></th>\n";
            echo " <th>\n";

            $min = floor ( ($now - $user['lastclick']) / 60 );
            if ( $min < 15 ) echo "    <font color=\"lime\">On</font>\n";
            else if ( $min < 60 ) echo "    <font color=\"yellow\">$min min</font>\n";
            else echo "    <font color=\"red\">Off</font>\n";

            echo " </th>\n";
            echo " <th><a href=\"?page=buddy&session=$session&action=8&buddy_id=".$buddy['buddy_id']."\">Удалить</a></th>\n";
            echo "</tr>\n";
            $i++;
        }
    }
    else echo " <tr><th colspan=\"6\">Друзей нет</th></tr>\n";
    
    echo "</table>\n";
    echo "<br><br><br><br>\n";
}

// Запросы (5)
function Buddy_Income ()
{
    global $GlobalUser;
    global $session;

?>
<table width="519">
 <tr>
   <td class="c" colspan="6">Запросы</td>
  </tr>

<?php
    $result = EnumIncomeBuddy ($GlobalUser['player_id']);
    $num = dbrows ($result);
    if ($num)
    {
        $i = 1;
?>
  <tr>
  <th></th>
  <th>Юзер</th>
  <th>Альянс</th>
  <th>Координаты</th>
  <th>Текст</th>
  <th></th>
 </tr>
<?php
        while ($num--)
        {
            $buddy = dbarray ($result);
            $user = LoadUser ($buddy['request_from']);
            $home = GetPlanet ($user['hplanetid']);
            echo "  <tr>\n";
            echo " <th width=\"20\">$i</th>\n";
            echo "  <th><a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\">".$user['oname']."</a></th>\n";
            if ($user['ally_id'] > 0)
            {
                $ally = LoadAlly ($user['ally_id']);
                echo "    <th><a href=index.php?page=ainfo&session=".$_GET['session']."&allyid=".$userto['ally_id']." target='_ally'> ";
                echo $ally['tag'];
                if ($user['allyrank'] == 0) echo "  (G)";
                echo "</a></th>\n";
            }
            else echo "    <th><a href=index.php?page=allianzen&session=".$_GET['session'].">  </a></th>\n";
            echo "  <th><a href=\"index.php?page=galaxy&galaxy=".$home['g']."&system=".$home['s']."&position=".$home['p']."&session=".$_GET['session']."\" >[".$home['g'].":".$home['s'].":".$home['p']."]</a></th>\n";
            echo "  <th>".$buddy['text']."</th>\n";
            echo "    <th width=\"100\"><a href=?page=buddy&session=$session&action=2&buddy_id=".$buddy['buddy_id'].">Принять</a>\n";
            echo "   <a href=?page=buddy&session=$session&action=3&buddy_id=".$buddy['buddy_id'].">Отклонить</a></th>\n";
            echo "  </tr>\n";
            $i++;
        }
    }
    else echo " <tr>   <th colspan=\"6\">Запросов нет</th>  </tr>\n";
?>

 <tr>
  <td class="c" colspan="6"><a href="?page=buddy&session=<?=$session;?>">Назад</a></td>
 </tr>
</table>
<br><br><br><br>
<?php
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
            $home = GetPlanet ($userto['hplanetid']);
            echo "  <tr>\n";
            echo " <th width=\"20\">$i</th>\n";
            echo "  <th><a href=\"index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$userto['player_id']."\">".$userto['oname']."</a></th>\n";
            if ($userto['ally_id'] > 0)
            {
                $ally = LoadAlly ($userto['ally_id']);
                echo "    <th><a href=index.php?page=ainfo&session=".$_GET['session']."&allyid=".$userto['ally_id']." target='_ally'> ";
                echo $ally['tag'];
                if ($userto['allyrank'] == 0) echo "  (G)";
                echo "</a></th>\n";
            }
            else echo "    <th><a href=index.php?page=allianzen&session=".$_GET['session'].">  </a></th>\n";
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
    $user = LoadUser ( intval ($_GET['buddy_id']) );
    echo "<form action=\"?page=buddy&session=".$_GET['session']."&action=1&buddy_id=".intval($_GET['buddy_id'])."\" method=\"POST\">\n";
    echo "<table width=\"519\">\n";
    echo " <tr>\n<td class=\"c\" colspan=\"2\">Предложение подружиться</td>\n</tr>\n";
    echo " <tr>\n<th>Игрок</th>\n<th>".$user['oname']."</th>\n</tr>\n";
    echo " <tr>\n<th>Текст предложения(<span id=\"cntChars\">0</span> / 5000 символов)</th>\n";
    echo " <th><textarea name=\"text\" cols=\"60\" rows=\"10\" onkeyup=\"javascript:cntchar(5000)\"></textarea></th>\n</tr>\n";
    echo "<tr> \n<td class=\"c\"><a href=\"?page=buddy&session=".$_GET['session']."\">Назад</a></td>\n";
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
    $to = intval ($_GET['buddy_id']);
    if ($from != $to)
    {
        $buddy_id = AddBuddy ( $from, $to, $_POST['text']);
        if ($buddy_id == 0) $BuddyError = "Предложение подружиться уже подано";
        else SendMessage ( $to, $GlobalUser['oname'], "Предложение подружиться", $_POST['text'], 0 );
    }
    Buddy_Home ();
}
else if ( key_exists ('action', $_GET) && $_GET['action'] == 2 && $_GET['buddy_id'])    // Принять запрос
{
    $buddy_id = intval ($_GET['buddy_id']);
    $buddy = LoadBuddy ($buddy_id);
    AcceptBuddy ($buddy_id);
    SendMessage ( $buddy['request_from'], "Список друзей", "Подтверждение", va("Игрок #1 внёс Вас в список друзей", $GlobalUser['oname']), 0);
    Buddy_Income ();
}
else if ( key_exists ('action', $_GET) && $_GET['action'] == 3 && $_GET['buddy_id'])    // Отклонить запрос
{
    $buddy_id = intval ($_GET['buddy_id']);
    $buddy = LoadBuddy ($buddy_id);
    RemoveBuddy ($buddy_id);
    SendMessage ( $buddy['request_from'], "Список друзей", "Предложение подружиться", va("Игрок #1 отверг Ваше предложение подружиться", $GlobalUser['oname']), 0);
    Buddy_Income ();
}
else if ( key_exists ('action', $_GET) && $_GET['action'] == 4 && $_GET['buddy_id'])    // Отозвать свой запрос.
{
    $buddy_id = intval ($_GET['buddy_id']);
    $buddy = LoadBuddy ($buddy_id);
    if ( $buddy['request_from'] == $GlobalUser['player_id'] )    // только свои
    {
        RemoveBuddy ($buddy_id);
        SendMessage ( $buddy['request_to'], "Список друзей", "Предложение подружиться", va ("Игрок #1 отозвал своё предложение подружиться", $GlobalUser['oname']), 0 );
    }
    Buddy_Outcome ();
}
else if ( key_exists ('action', $_GET) && $_GET['action'] == 5 ) Buddy_Income ();    // Чужие запросы
else if ( key_exists ('action', $_GET) && $_GET['action'] == 6 ) Buddy_Outcome ();    // Свои запросы
else if ( key_exists ('action', $_GET) && $_GET['action'] == 7 ) Buddy_Request ();    // Окно отправки заявки.
else if ( key_exists ('action', $_GET) && $_GET['action'] == 8 && $_GET['buddy_id'])    // Удалить из списка
{
    $buddy_id = intval ($_GET['buddy_id']);
    $buddy = LoadBuddy ($buddy_id);
    if ($buddy['request_from'] == $GlobalUser['player_id'] )    // только свои
    {
        RemoveBuddy ($buddy_id);
        SendMessage ( $buddy['request_to'], "Список друзей", "Подтверждение", va ("Игрок #1 удалил Ваше предложение подружиться", $GlobalUser['oname']), 0 );
    }
    if ($buddy['request_to'] == $GlobalUser['player_id'] )
    {
        RemoveBuddy ($buddy_id);
        SendMessage ( $buddy['request_from'], "Список друзей", "Подтверждение", va ("Игрок #1 удалил Ваше предложение подружиться", $GlobalUser['oname']), 0 );
    }
    Buddy_Home ();
}
else Buddy_Home ();

echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ("", $BuddyError);
ob_end_flush ();
?>