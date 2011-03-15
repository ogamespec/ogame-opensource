<?php

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("galaxy");

function empty_row ($p)
{
    echo "<tr><th width=\"30\"><a href=\"#\" >".$p."</a></th><th width=\"30\"></th><th width=\"130\" style='white-space: nowrap;'></th><th width=\"30\" style='white-space: nowrap;'></th><th width=\"30\"></th><th width=\"150\"></th><th width=\"80\"></th><th width=\"125\" style='white-space: nowrap;'></th></tr>\n\n";
}

// Выбрать солнечную систему.
if ( key_exists ('session', $_POST)) $coord_g = $_POST['galaxy'];
else if ( key_exists ('galaxy', $_GET)) $coord_g = $_GET['galaxy'];
else $coord_g = $aktplanet['g'];
if ( key_exists ('session', $_POST)) $coord_s = $_POST['system'];
else if ( key_exists ('system', $_GET)) $coord_s = $_GET['system'];
else $coord_s = $aktplanet['s'];
if ( key_exists ('session', $_POST)) $coord_p = 0;
else if ( key_exists ('position', $_GET)) $coord_p = $_GET['position'];
else $coord_p = $aktplanet['p'];

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n\n";

/*
echo "$coord_g : $coord_s : $coord_p<br>";
echo "GET: ";
print_r ($_GET);
echo "<br>POST: ";
print_r ($_POST);
echo "<br>";
*/

/***** Меню выбора солнечной системы. *****/

echo "<form action=\"index.php?page=galaxy&no_header=1&session=".$_GET['session']."\" method=\"post\" id=\"galaxy_form\">\n";
echo "<input type=\"hidden\" name=\"session\" value=\"".$_GET['session']."\">\n";
echo "<input type=\"hidden\" id=\"auto\" value=\"dr\">\n";
echo "<table border=1 class='header' id='t1'>\n\n";
echo "<tr class='header'>\n";
echo "    <td class='header'><table class='header' id='t2'>\n";
echo "    <tr class='header'><td class=\"c\" colspan=\"3\">Галактика</td></tr>\n";
echo "    <tr class='header'>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"galaxyLeft\" value=\"<-\" onClick=\"galaxy_submit('galaxyLeft')\"></td>\n";
echo "    <td class=\"l\"><input type=\"text\" name=\"galaxy\" value=\"".$coord_g."\" size=\"5\" maxlength=\"3\" tabindex=\"1\"></td>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"galaxyRight\" value=\"->\" onClick=\"galaxy_submit('galaxyRight')\"></td>\n";
echo "    </tr></table></td>\n\n";
echo "    <td class='header'><table class='header' id='t3'>\n";
echo "    <tr class='header'><td class=\"c\" colspan=\"3\">Солнечная система</td></tr>\n";
echo "    <tr class='header'>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"systemLeft\" value=\"<-\" onClick=\"galaxy_submit('systemLeft')\"></td>\n";
echo "    <td class=\"l\"><input type=\"text\" name=\"system\" value=\"".$coord_s."\" size=\"5\" maxlength=\"3\" tabindex=\"2\"></td>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"systemRight\" value=\"->\" onClick=\"galaxy_submit('systemRight')\"></td>\n";
echo "    </tr></table></td>\n";
echo "</tr>\n\n";
echo "<tr class='header'>\n";
echo "    <td class='header' style=\"background-color:transparent;border:0px;\" colspan=\"2\" align=\"center\"> <input type=\"submit\" value=\"Показать\"></td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</form>\n";

/***** Заголовок таблицы *****/

echo "<table width=\"569\">\n";
echo "<tr><td class=\"c\" colspan=\"8\">Солнечная система ".$coord_g.":".$coord_s."</td></tr>\n";
echo "<tr>\n";
echo "<td class=\"c\">Коорд.</td>\n";
echo "<td class=\"c\">Планета</td>\n";
echo "<td class=\"c\">Название (активность)</td>\n";
echo "<td class=\"c\">луна</td>\n";
echo "<td class=\"c\">поле обломков</td>\n";
echo "<td class=\"c\">игрок (статус)</td>\n";
echo "<td class=\"c\">Альянс</td>\n";
echo "<td class=\"c\">Действия</td>\n";
echo "</tr>\n";

/***** Перечислить планеты *****/

$p = 1;
$tabindex = 3;
$result = EnumPlanetsGalaxy ( $coord_g, $coord_s );
$num = $planets = dbrows ($result);

while ($num--)
{
    $planet = dbarray ($result);
    $user = LoadUser ( $planet['owner_id']);
    $own = $user['player_id'] == $GlobalUser['player_id'];
    for ($p; $p<$planet['p']; $p++) empty_row ($p);

    // Коорд.
    echo "<tr>\n";
    echo "<th width=\"30\"><a href=\"#\"  tabindex=\"".($tabindex++)."\" >".$p."</a></th>\n";

    // Планета
    echo "<th width=\"30\">\n";
    if ( !$planet['destroyed'] )
    {
        echo "<a style=\"cursor:pointer\" onmouseover='return overlib(\"<table width=240>";
        echo "<tr><td class=c colspan=2 >Планета ".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]</td></tr>";
        echo "<tr><th width=80 ><img src=".GetPlanetSmallImage ( UserSkin(), $planet['type'] )." height=75 width=75 /></th>";
        echo "<th align=left >";
        if ($own)
        {
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=4 >Оставить</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=3 >Транспорт</a><br />";
        }
        else
        {
            echo "<a href=# onclick=doit(6,".$planet['g'].",".$planet['s'].",".$planet['p'].",1,1) >Шпионаж</a><br><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=1 m>Атака</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=5 >Удерживать</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=3 >Транспорт</a><br />";
        }
        echo "</th></tr></table>\", STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -40 );' onmouseout=\"return nd();\">\n";
        echo "<img src=\"".GetPlanetSmallImage ( UserSkin(), $planet['type'] )."\" height=\"30\" width=\"30\"/></a>\n";
    }
    echo "</th>\n";

    // Название (активность)
    $now = time ();
    $ago15 = $now - 15 * 60;
    $ago60 = $now - 60 * 60;
    $akt = "";
    if (!$own)
    {
        if ( $planet['lastakt'] > $ago15 ) $akt = "&nbsp;(*)";
        else if ( $planet['lastakt'] > $ago60) $akt = "&nbsp;(".floor(($now - $planet['lastakt'])/60)." min)";
    }
    if ( $planet['destroyed'] ) echo "<th width=\"130\" style='white-space: nowrap;'>Уничтоженная планета$akt</th>\n";
    else echo "<th width=\"130\" style='white-space: nowrap;'>".$planet['name']."$akt</th>\n";

    // луна
    echo "<th width=\"30\" style='white-space: nowrap;'>\n";
    $moon_id = PlanetHasMoon ( $planet['planet_id'] );
    if ($moon_id)
    {
        $moon = GetPlanet ( $moon_id );
        if (!$moon['destroyed'])
        {
            echo "<a onmouseout=\"return nd();\" onmouseover=\"return overlib('<table width=240 ><tr>";
            echo "<td class=c colspan=2 >Луна ".$moon['name']." [".$moon['g'].":".$moon['s'].":".$moon['p']."]</td></tr>";
            echo "<tr><th width=80 ><img src=".GetPlanetSmallImage ( UserSkin(), $moon['type'] )." height=75 width=75 alt=\'Луна (размер: ".$moon['diameter'].")\'/></th>";
            echo "<th><table width=120 ><tr><td colspan=2 class=c >Свойства</td></tr>";
            echo "<tr><th>размер:</td><th>".nicenum($moon['diameter'])."</td></tr>";
            echo "<tr><th>температура:</td><th>".$moon['temp']."</td></tr>";
            echo "<tr><td colspan=2 class=c >Действия:</td></tr>";
            echo "<tr><th align=left colspan=2 >";
            if ($own)
            {
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=3 >Транспорт</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=4 >Оставить</a><br />";
            }
            else
            {
                echo "<font color=#808080 >Шпионаж</font><br><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=3 >Транспорт</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=1 >Атака</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=5 >Удерживать</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=9 >Уничтожить</a><br />";
            }
            echo "</th></tr></table></tr></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -110 );\" style=\"cursor: pointer;\">\n";
            echo "<img width=\"22\" height=\"22\" alt=\"Луна (размер: 4358)\" src=\"".GetPlanetSmallImage ( UserSkin(), $moon['type'] )."\"/></a>\n";
        }
        else echo "<div style=\"border: 2pt solid #FF0000;\"><img src=\"".GetPlanetSmallImage ( UserSkin(), $moon['type'] )."\" alt=\"Луна (размер: ".$moon['diameter'].")\" height=\"22\" width=\"22\" onmouseover=\"return overlib('<font color=white><b>Покинута</b></font>', WIDTH, 75);\" onmouseout=\"return nd();\"/></div>\n";
    }
    echo "</th>\n";

    // поле обломков
    echo "<th width=\"30\"></th>\n";

    // игрок (статус)
    // Новичек или Сильный или Обычный
    // Приоритеты Обычного: Режим отпуска -> Заблокирован -> Давно неактивен -> Неактивен -> Без статуса
    echo "<th width=\"150\">\n";
    if ( !$planet['destroyed'] )
    {
        echo "<a style=\"cursor:pointer\" onmouseover=\"return overlib('<table width=240 >";
        echo "<tr><td class=c >Игрок ".$user['oname'].". Место в рейтинге - ".$user['place1']."</td></tr>";
        echo "<th><table>";
        if (!$own)
        {
            echo "<tr><td><a href=index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$planet['owner_id']." >Написать сообщение</a></td></tr>";
            echo "<tr><td><a href=index.php?page=buddy&session=".$_GET['session']."&action=7&buddy_id=".$planet['owner_id']." >Предложение подружиться</a></td></tr>";
        }
        echo "<tr><td><a href=index.php?page=statistics&session=".$_GET['session']."&start=".(floor($user['place1']/100)*100+1)." >Статистика</a></td></tr></table>";
        echo "</th></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETY, -40 );\" onmouseout=\"return nd();\">\n";
        if ( IsPlayerNewbie ( $user['player_id'] ) )
        {
            $pstat = "noob"; $stat = "<span class='noob'>н</span>";
        }
        else if ( IsPlayerStrong ( $user['player_id'] ) )
        {
            $pstat = "strong"; $stat = "<span class='strong'>с</span>";
        }
        else
        {
            $week = time() - 604800;
            $week3 = time() - 604800*3;
            $pstat = "normal";
            if ( $user['lastclick'] <= $week ) { $stat .= "<span class='inactive'>i</span>"; $pstat = "inactive"; }
            if ( $user['banned'] ) { if(mb_strlen($stat, "UTF-8")) $stat .= " "; $stat .= "<a href='index.php?page=pranger&session=".$_GET['session']."'><span class='banned'>з</span></a>"; $pstat = "banned"; }
            if ( $user['lastclick'] <= $week3 ) { if(mb_strlen($stat, "UTF-8")) $stat .= " "; $stat .= "<span class='longinactive'>I</span>";  if($pstat !== "banned") $pstat = "longinactive"; }
            if ( $user['vacation'] ) { if(mb_strlen($stat, "UTF-8")) $stat .= " "; $stat .= "<span class='vacation'>РО</span>";  $pstat = "vacation"; }
        }
        echo "<span class=\"$pstat\">".$user['oname']."</span></a>\n";
        if ($pstat !== "normal") echo "($stat)\n";
    }
    echo "</th>\n";

    // Альянс
    if ($user['ally_id'] && !$planet['destroyed'])
    {
        $ally = LoadAlly ( $user['ally_id']);
        $allytext = $ally['tag'];
    }
    else $allytext = "";
    echo "<th width=\"80\">$allytext</th>\n";

    // Действия
    echo "<th width=\"125\" style='white-space: nowrap;'>\n";
    if ( !$planet['destroyed'] && !$own)
    {
        echo "<a style=\"cursor:pointer\" onclick=\"javascript:doit(6, 1, 399, 4, 1, 1);\"><img src=\"".UserSkin()."img/e.gif\" border=\"0\" alt=\"Шпионаж\" title=\"Шпионаж\" /></a>\n";
        echo "<a href=\"index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$planet['owner_id']."\"><img src=\"".UserSkin()."img/m.gif\" border=\"0\" alt=\"Написать сообщение\" title=\"Написать сообщение\" /></a>\n";
        echo "<a href=\"index.php?page=buddy&session=".$_GET['session']."&action=7&buddy_id=".$planet['owner_id']."\"><img src=\"".UserSkin()."img/b.gif\" border=\"0\" alt=\"Предложение подружиться\" title=\"Предложение подружиться\" /></a>\n";
    }
    echo "</th>\n";

    echo "</tr>\n\n";
    $p++;
}
for ($p; $p<=15; $p++) empty_row ($p);

/***** Низ таблицы *****/
echo "<tr><th style='height:32px;'>16</th><th colspan='7'><a href ='#'>Бесконечные дали</a></th></tr>\n\n";

echo "<tr><td class=\"c\" colspan=\"6\">(Заселено ".$planets." планет)</td>\n";
echo "<td class=\"c\" colspan=\"2\"><a href='#' onmouseover='return overlib(\"<table><tr><td class=c colspan=2>Легенда</td></tr><tr><td width=125>сильный игрок</td><td><span class=strong>с</span></td></tr><tr><td>нуб</td><td><span class=noob>н</span></td></tr><tr><td>режим отпуска</td><td><span class=vacation>РО</span></td></tr><tr><td>заблокирован</td><td><span class=banned>з</span></td></tr><tr><td>неактивен 7 дней</td><td><span class=inactive>i</span></td></tr><tr><td>неактивен 28 дней</td><td><span class=longinactive>I</span></td></tr></table>\", ABOVE, WIDTH, 150, STICKY, MOUSEOFF, DELAY, 500, CENTER);' onmouseout='return nd();'>Легенда</a></td>\n";
echo "</tr>\n";

echo "</table>\n\n";

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n\n";

PageFooter ();
ob_end_flush ();
?>