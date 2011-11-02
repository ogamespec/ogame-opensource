<?php

$pagetime = 0;

// Получить маленькую картинку планеты.
function GetPlanetSmallImage ($skinpath, $type)
{
    if ($type == 0) return $skinpath."planeten/small/s_mond.jpg";
    else if ($type == 10000) return $skinpath."planeten/debris.jpg";
    else
    {
        if ($type >= 101 && $type <= 110) return sprintf ( "%splaneten/small/s_trockenplanet%02d.jpg", $skinpath, $type - 100);
        else if ($type >= 201 && $type <= 210) return sprintf ( "%splaneten/small/s_dschjungelplanet%02d.jpg", $skinpath, $type - 200);
        else if ($type >= 301 && $type <= 307) return sprintf ( "%splaneten/small/s_normaltempplanet%02d.jpg", $skinpath, $type - 300);
        else if ($type >= 401 && $type <= 409) return sprintf ( "%splaneten/small/s_wasserplanet%02d.jpg", $skinpath, $type - 400);
        else if ($type >= 501 && $type <= 510) return sprintf ( "%splaneten/small/s_eisplanet%02d.jpg", $skinpath, $type - 500);
        else return "img/admin_planets.png";        // Специальные объекты галактики (уничтоженные планеты и пр.)
    }
}

// Получить большую картинку планеты.
function GetPlanetImage ($skinpath, $type)
{
    if ($type == 0) return $skinpath."planeten/mond.jpg";
    else if ($type == 10000) return $skinpath."planeten/debris.jpg";
    else
    {
        if ($type >= 101 && $type <= 110) return sprintf ( "%splaneten/trockenplanet%02d.jpg", $skinpath, $type - 100);
        else if ($type >= 201 && $type <= 210) return sprintf ( "%splaneten/dschjungelplanet%02d.jpg", $skinpath, $type - 200);
        else if ($type >= 301 && $type <= 307) return sprintf ( "%splaneten/normaltempplanet%02d.jpg", $skinpath, $type - 300);
        else if ($type >= 401 && $type <= 409) return sprintf ( "%splaneten/wasserplanet%02d.jpg", $skinpath, $type - 400);
        else if ($type >= 501 && $type <= 510) return sprintf ( "%splaneten/eisplanet%02d.jpg", $skinpath, $type - 500);
        else return "img/admin_planets.png";    // Специальные объекты галактики (уничтоженные планеты и пр.)
    }
}

function UserSkin ()
{
    global $GlobalUser;
    if ($GlobalUser['useskin']) return $GlobalUser['skin'];
    else return hostname () . "evolution/";
}

function PageHeader ($page, $noheader=false, $leftmenu=true, $redirect_page="", $redirect_sec=0)
{
    global $pagetime;
    global $GlobalUser;

    BrowseHistory ();

    $mtime = microtime(); 
    $mtime = explode(" ",$mtime); 
    $mtime = $mtime[1] + $mtime[0]; 
    $pagetime = $mtime;

    $unitab = LoadUniverse ();
    $uni = $unitab['num'];

    echo "<html>\n";
    echo " <head>\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/default.css' />\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/formate.css' />\n";
    echo "  <script language=\"JavaScript\">var session=\"".$GlobalUser['session']."\";</script>\n";
    echo "  <meta http-equiv='content-type' content='text/html; charset=UTF-8' />\n";
    if ( $redirect_page !== "" ) {
        echo "  <meta http-equiv=\"refresh\" content=\"".$redirect_sec."; URL=index.php?page=".$redirect_page."&session=".$GlobalUser['session']."&redirect=1\">\n\n";
    }
    echo "<link rel='stylesheet' type='text/css' href='css/combox.css'>\n";
    echo "<link rel='stylesheet' type='text/css' href='".UserSkin()."formate.css' />\n";
    echo "<title>Вселенная $uni ОГейм</title>\n";
    echo "  <script src='js/utilities.js' type='text/javascript'></script>\n";
    echo "  <script language='JavaScript'>\n";
    echo "  </script>\n";
    echo "<script type='text/javascript' src='js/overLib/overlib.js'></script>\n";
    echo "<script type=\"application/javascript\" src=\"js/iscroll-lite.js\"></script>\n";
    echo "<!-- HEADER -->\n\n";
    echo "<script language='JavaScript'>\n";
    echo "function onBodyLoad() {\n";
    echo "    window.setTimeout('reloadImages()', 100);\n";
    echo "}\n";
    echo "function reloadImages() {\n";
    echo "    for (var i = 0; i < document.images.length; ++i) {\n";
    echo "      if ((document.images[i].className == 'reloadimage') && (document.images[i].title != '')) {\n";
    echo "        document.images[i].src = document.images[i].title;\n";
    echo "      }\n";
    echo "    }\n";
    echo "}\n";
    echo "</script>\n";
    echo "</head>\n";

    echo "<body style='overflow: hidden;' onload='onBodyLoad();' onunload='' >\n";
    echo "<div id='overDiv' style='position:absolute; visibility:hidden; z-index:1000;'></div>\n";

    if ($noheader == false)
    {
        echo "<div id='header_top'><center>\n";
        echo "<table class='header'>\n";
        echo "<tr class='header' >\n";
        $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
        PlanetsDropList ($page);
        ResourceList ($aktplanet['m'], $aktplanet['k'], $aktplanet['d'], $aktplanet['e'], $aktplanet['emax'], $GlobalUser['dm']+$GlobalUser['dmfree'], $aktplanet['mmax'], $aktplanet['kmax'], $aktplanet['dmax']);
        OficeerList ();
        echo "</tr>\n";
        echo "</table>\n";
        echo "</div><!-- END HEADER -->\n\n";
    }

    echo "<!-- LEFTMENU -->\n\n";
    if ($leftmenu) LeftMenu ();
    echo "<!-- END LEFTMENU -->\n\n";
}

function PlanetsDropList ($page)
{
    global $GlobalUser;
    $sess = $GlobalUser['session'];
    $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
    $result = EnumPlanets ( $GlobalUser['player_id'] );

    echo "<td class='header' style='width:5;' >\n";
    echo "<table class='header'>\n";
    echo "<tr class='header'>\n";
    echo "<td class='header'><img src='".GetPlanetSmallImage(UserSkin(), $aktplanet['type'])."' width='50' height='50'></td>\n";
    echo "<td class='header'>\n";
    echo "<table class='header'>\n";
    echo "<select size='1' onchange='haha(this)'>\n";
    
    $num = dbrows ($result);
    for ($n=0; $n<$num; $n++)
    {
        if (key_exists ('gid', $_GET)) $gid = "&gid=".$_GET['gid'];
        if (key_exists ('tid', $_GET)) $tid = "&tid=".$_GET['tid'];
        if (key_exists ('mode', $_GET)) $mode = "&mode=".$_GET['mode'];
        $planet = dbarray ($result);
        $cp = $planet['planet_id'];
        $sel = "";
        if ($cp == $GlobalUser['aktplanet']) $sel = "selected";
        $g = $planet['g']; $s = $planet['s']; $p = $planet['p'];
        $name = $planet['name'];
        echo "    <option value='index.php?page=".$page."&session=$sess&cp=$cp$gid$tid$mode' $sel>$name  <a href='index.php?page=galaxy&galaxy=$g&system=$s&position=$p&session=$sess&cp=$cp$gid$tid$mode' >[$g:$s:$p]</a></option>\n";
    }
    echo "</select></table></td></tr></table></td>\n\n";
}

function ResourceList ($m, $k, $d, $enow, $emax, $dm, $mmax, $kmax, $dmax)
{
    global $GlobalUser;
    $sess = $GlobalUser['session'];

    $prem = 1;

    $mcol = $kcol = $dcol = $ecol = "";
    if ($m >= $mmax) $mcol = "color='#ff0000'";
    if ($k >= $kmax) $kcol = "color='#ff0000'";
    if ($d >= $dmax) $dcol = "color='#ff0000'";
    if ($enow < 0) $ecol = "color='#ff0000'";

    echo "<td class='header'><table class='header' id='resources' border='0' cellspacing='0' cellpadding='0' padding-right='30' >\n";
    echo "<tr class='header'>\n";
    echo "<td align='center' width='85' class='header'>\n";
    echo "<img border='0' src='".UserSkin()."images/metall.gif' width='42' height='22'>\n</td>\n";
    echo "<td align='center' width='85' class='header'>\n";
    echo "<img border='0' src='".UserSkin()."images/kristall.gif' width='42' height='22'>\n</td>\n";
    echo "<td align='center' width='85' class='header'>\n";
    echo "<img border='0' src='".UserSkin()."images/deuterium.gif' width='42' height='22'>\n</td>\n";
    if ($prem)
    {
        echo "<td align='center' width='85' class='header'>\n";
        echo "<a href=index.php?page=micropayment&session=$sess>\n";
        echo "<img border='0' src='img/dm_klein_2.jpg' width='42' height='22' title='".loca("DM")."'></a>\n</td>\n";
    }
    echo "<td align='center' width='85' class='header'>\n";
    echo "<img border='0' src='".UserSkin()."images/energie.gif' width='42' height='22'>\n</td>\n</tr>\n";
    echo "<tr class='header'>\n";
    echo "    <td align='center' class='header' width='85'><i><b><font color='#ffffff'>".loca("METAL")."</font></b></i></td>\n";
    echo "    <td align='center' class='header' width='85'><i><b><font color='#ffffff'>".loca("CRYSTAL")."</font></b></i></td>\n";
    echo "    <td align='center' class='header' width='85'><i><b><font color='#ffffff'>".loca("DEUTERIUM")."</font></b></i></td>\n";
    if ($prem) echo "    <td align='center' class='header' width='85'><i><b><font color='#ffffff'>".loca("DM")."</font></b></i></td>\n";
    echo "    <td align='center' class='header' width='85'><i><b><font color='#ffffff'>".loca("ENERGY")."</font></b></i></td>\n";
    echo "</tr>\n";
    echo "<tr class='header'>\n";
    echo "    <td align='center' class='header' width='90'><font $mcol>".nicenum($m)."</font></td>\n";
    echo "    <td align='center' class='header' width='90'><font $kcol>".nicenum($k)."</font></td>\n";
    echo "    <td align='center' class='header' width='90'><font $dcol>".nicenum($d)."</font></td>\n";
    if ($prem) echo "    <td align='center' class='header' width='90'><font color='#FFFFFF'>".nicenum($dm)."</font></td>\n";
    echo "    <td align='center' class='header' width='90'><font $ecol>".nicenum($enow)."</font>/".nicenum($emax)."</td>\n\n";
    echo "</tr>\n";
    echo "</table></td>\n";
}

function calco (&$img, &$days, &$action, $now, $qcmd, $who)
{
    global $GlobalUser;
    //$end = $GlobalUser[$who];
    $end = GetOfficerLeft ( $GlobalUser['player_id'], $qcmd[$who] );
    if ($end <= $now) $img[$who] = "_un";
    else
    {
        $d = ($end - $now) / (60*60*24);
        if ( $d  > 0 )
        {
            $days[$who] = "&lt;font color=lime&gt;Активен&lt;/font&gt; ещё ".ceil($d)." д.";
            $action[$who] = "Продлить!";
        }
    }
}

function OficeerList ()
{
    global $GlobalUser;
    $sess = $GlobalUser['session'];
    $img = array ( 'commander' => '', 'admiral' => '', 'engineer' => '', 'geologist' => '', 'technocrat' => '');
    $days = array ( 'commander' => '', 'admiral' => '', 'engineer' => '', 'geologist' => '', 'technocrat' => '');
    $action = array ( 'commander' => 'Заказать!', 'admiral' => 'Заказать!', 'engineer' => 'Заказать!', 'geologist' => 'Заказать!', 'technocrat' => 'Заказать!');
    $qcmd = array ( 'commander' => 'CommanderOff', 'admiral' => 'AdmiralOff', 'engineer' => 'EngineerOff', 'geologist' => 'GeologeOff', 'technocrat' => 'TechnocrateOff');

    $now = time ();

    calco (&$img, &$days, &$action, $now, &$qcmd, 'commander');
    calco (&$img, &$days, &$action, $now, &$qcmd, 'admiral');
    calco (&$img, &$days, &$action, $now, &$qcmd, 'engineer');
    calco (&$img, &$days, &$action, $now, &$qcmd, 'geologist');
    calco (&$img, &$days, &$action, $now, &$qcmd, 'technocrat');

    echo "<td class='header'>\n";
    echo "<table class='header' align=left>\n";
    echo "<tr class='header'>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/commander_ikon".$img['commander'].".gif' width='32' height='32' alt='Командир ОГейма'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['commander']."<br>Командир ОГейма</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['commander']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/admiral_ikon".$img['admiral'].".gif' width='32' height='32' alt='Адмирал'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['admiral']."<br>Адмирал</font><br><font size=1 color=skyblue>&amp;nbsp;Макс. кол-во флотов +2</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['admiral']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/ingenieur_ikon".$img['engineer'].".gif' width='32' height='32' alt='Инженер'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['engineer']."<br>Инженер</font><br><font size=1 color=skyblue>Сокращает вдвое потери в обороне+10% больше энергии</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['engineer']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/geologe_ikon".$img['geologist'].".gif' width='32' height='32' alt='Геолог'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['geologist']."<br>Геолог</font><br><font size=1 color=skyblue>+10% доход от шахты</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['geologist']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/technokrat_ikon".$img['technocrat'].".gif' width='32' height='32' alt='Технократ'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['technocrat']."<br>Технократ</font><br><font size=1 color=skyblue>+2 уровень шпионажа, 25% меньше времени на исследования</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['technocrat']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "<td align='center' class='header'></td></tr></table></td>\n\n";
}

function LeftMenu ()
{
    global $GlobalUser;
    $sess = $GlobalUser['session'];

    $prem = 0;

    $unitab = LoadUniverse ();
    $uni = $unitab['num'];

    echo "   <div id='leftmenu'>\n\n";
    echo "<script language='JavaScript'>\n";
    echo "function fenster(target_url,win_name) {\n";
    echo "  var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar=no,top=0,left=0,toolbar=no,width=550,height=280,resizable=yes');\n";
    echo "  new_win.focus();\n";
    echo "}\n";
    echo "</script>\n";
    echo "<center>\n\n";
    echo "<div id='menu'>\n";
    echo "<a href='mailto:barrierefrei@ogame.de' title='Проблемы, касающиеся игроков со слабым зрением, отправляйте на barrierefrei@ogame.de.' style='width:1px;'></a>\n";
    echo "<p style='width:110px;'><NOBR>Вселенная ".$uni." (<a href='index.php?page=changelog&session=".$sess."'>v 0.84</a>)</NOBR></p>\n";
    echo "<table width='110' cellspacing='0' cellpadding='0'>\n";
    echo " <tr>\n";
    echo "  <td><img src='".UserSkin()."gfx/ogame-produktion.jpg' width='110' height='40' /></td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align='center'><font color='#FFFFFF'>\n";
    echo "     <a href='index.php?page=overview&session=".$sess."' accesskey='o'>".loca("MENU_OVERVIEW")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    if ( $GlobalUser['admin'] > 0 ) {
        echo " <tr>\n";
        echo "  <td>\n";
        echo "   <div align='center'><font color='#FFFFFF'>\n";
        echo "     <a href='index.php?page=admin&session=".$sess."' >".loca("MENU_ADMIN")."</a>\n";
        echo "    </font></div>\n";
        echo "  </td>\n";
        echo " </tr>\n\n";
    }
    echo "  <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "     <a href='index.php?page=imperium&session=$sess&planettype=1&no_header=1' accesskey=\"r\">".loca("MENU_EMPIRE")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align='center'><font color='#FFFFFF'>\n";
    echo "     <a href='index.php?page=b_building&session=".$sess."' accesskey='z'>".loca("MENU_BUILDING")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr> \n";
    echo "  <td> \n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\"> \n";
    echo "     <a href='index.php?page=resources&session=".$sess."' accesskey=\"s\">".loca("MENU_RESOURCES")."</a> \n";
    echo "    </font></div> \n";
    echo "  </td> \n";
    echo " </tr> \n\n";
    echo " <tr> \n";
    echo "  <td> \n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\"> \n";
    echo "     <a href='index.php?page=buildings&session=".$sess."&mode=Forschung' accesskey=\"i\">".loca("MENU_RESEARCH")."</a> \n";
    echo "    </font></div> \n";
    echo "  </td> \n";
    echo " </tr> \n\n";
    echo " <tr> \n";
    echo "  <td> \n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\"> \n";
    echo "     <a href='index.php?page=buildings&session=".$sess."&mode=Flotte' accesskey=\"v\">".loca("MENU_SHIPYARD")."</a> \n";
    echo "    </font></div> \n";
    echo "  </td> \n";
    echo " </tr> \n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "     <a href='index.php?page=flotten1&session=$sess&mode=Flotte' accesskey=\"f\">".loca("MENU_FLEET")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr> \n";
    echo "  <td> \n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\"> \n";
    echo "     <a href='index.php?page=techtree&session=".$sess."' accesskey=\"t\">".loca("MENU_TECHTREE")."</a> \n";
    echo "    </font></div> \n";
    echo "  </td> \n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "     <a href='index.php?page=galaxy&session=".$sess."&no_header=1' accesskey=\"g\">".loca("MENU_GALAXY")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr> \n";
    echo "  <td> \n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\"> \n";
    echo "     <a href='index.php?page=buildings&session=".$sess."&mode=Verteidigung' accesskey=\"x\">".loca("MENU_DEFENSE")."</a> \n";
    echo "    </font></div> \n";
    echo "  </td> \n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td><img src=\"".UserSkin()."gfx/info-help.jpg\" width=\"110\" height=\"19\"></td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "     <a href='index.php?page=allianzen&session=".$sess."' accesskey=\"a\">".loca("MENU_ALLY")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo "  <tr> \n";
    echo "  <td> \n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\"> \n";
    echo "    <a href=\"http://board.oldogame.ru/\" target=\"_blank\" accesskey=\"m\" >".loca("MENU_BOARD")."</a><!-- external link to board --> \n";
    echo "   </font></div> \n";
    echo "  </td> \n";
    echo " </tr> \n\n";
    if ($prem) {
        echo "    <tr>\n";
        echo "       <td align=center>\n";
        echo "       <a id='darkmatter2' style='cursor:pointer; width:110px;'\n";
        echo "         href='index.php?page=micropayment&session=".$sess."' accesskey=\"o\"><b>".loca("MENU_PAYMENT")."</a></b>\n";
        echo "       </div>\n";
        echo "      </td>\n";
        echo "     </tr>\n\n";
    }
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "  <a href='index.php?page=statistics&session=$sess' accesskey=\"k\">".loca("MENU_STAT")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "     <a href='index.php?page=suche&session=$sess' accesskey=\"p\">".loca("MENU_SEARCH")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "    <a href=\"http://tutorial.oldogame.ru/\" target=\"_blank\" accesskey=\"^\" >".loca("MENU_TUTORIAL")."</a><!-- external link to ogame tutorial -->\n";
    echo "   </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td><img src='".UserSkin()."gfx/user-menu.jpg' width='110' height='35'></td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "     <a href='index.php?page=messages&dsp=1&session=".$sess."' accesskey=\"b\">".loca("MENU_MESSAGES")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "     <a href='#' onclick='fenster(\"index.php?page=notizen&session=".$sess."&no_header=1\", \"Notizen\");' accesskey=\"e\">".loca("MENU_NOTES")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
    echo "     <a href='index.php?page=buddy&session=".$sess."' accesskey=\"d\">".loca("MENU_BUDDY")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo "  <tr>\n";
    echo "   <td>\n";
    echo "    <div align='center'><font color='#FFFFFF'>\n";
    echo "      <a href='index.php?page=options&session=".$sess."' accesskey='n'>".loca("MENU_OPTIONS")."</a>\n";
    echo "     </font></div>\n";
    echo "   </td>\n";
    echo "  </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align='center'><font color='#FFFFFF'>\n";
    echo "     <a href='index.php?page=logout&session=".$sess."' accesskey='q'>".loca("MENU_LOGOUT")."</a>\n";
    echo "    </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align='center'><font color='#FFFFFF'>\n";
    echo "     <a href='http://board.oldogame.ru/thread.php?threadid=16' target='_blank'>".loca("MENU_RULES")."</a> <!-- external link to rules -->\n";
    echo "   </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " <tr>\n";
    echo "  <td>\n";
    echo "   <div align='center'><font color='#FFFFFF'>\n";
    echo "    <a href='http://oldogame.ru/impressum.php' target='_blank'>".loca("MENU_IMPRESSUM")."</a> <!-- external link to impressum -->\n";
    echo "   </font></div>\n";
    echo "  </td>\n";
    echo " </tr>\n\n";
    echo " </table>\n";
    echo " </center>\n";
    echo "    </div>\n";
}

function PageFooter ($msg="", $error="", $popup=false, $headerH=81)
{
    global $pagetime;
    global $GlobalUser;

    if ( $GlobalUser['debug'] )
    {
        $mtime = microtime(); 
        $mtime = explode(" ",$mtime); 
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $msg = sprintf ( "Страница сгенерирована за %f секунд<br>", $endtime-$pagetime) . $msg;
    }

    if ( !$GlobalUser['validated']) $error = "<center> \nВаш игровой аккаунт ещё не активирован. Зайдите в <a href=index.php?page=options&session=".$GlobalUser['session'].">Настройки</a>, введите электронный адрес и получите на него активационную ссылку.<br></center>\n" . $error;
    else if ( $GlobalUser['disable']) $error = "<center>\nВаш аккаунт был поставлен на удаление. Дата удаления: ".date ("Y-m-d H:i:s", $GlobalUser['disable_until'])."<br></center>\n" . $error;

    $msgdisplay = "";
    if ($msg !== "") $msgdisplay = "messagebox.style.display='block';\n";
    $errdisplay = "";
    if ($error !== "") $errdisplay = "errorbox.style.display='block';\n";

    echo "\n\n<script>\n";
    echo "messageboxHeight=0;\n";
    echo "errorboxHeight=0;\n";
    echo "contentbox = document.getElementById('content');\n";
    echo "</script>\n";
    echo "<div id='messagebox'><center>".$msg."</center></div>\n";
    echo "<div id='errorbox'><center>".$error."</center></div>\n";
    echo "<script>\n";
    if ($popup) echo "messagebox.style.top='0px';\n";
    echo "headerHeight = $headerH;\n";
    if ($popup)
    {
        echo "contentbox.style.left='0px';\n";
        echo "contentbox.style.width='100%';\n";
    }
    echo $msgdisplay . $errdisplay;
    echo "errorbox.style.top=parseInt(headerHeight+messagebox.offsetHeight+5)+'px';\n";
    echo "contentbox.style.top=parseInt(headerHeight+errorbox.offsetHeight+messagebox.offsetHeight+10)+'px';\n";
    echo "if (navigator.appName=='Netscape'){if (window.innerWidth<1020){document.body.scroll='no';}   contentbox.style.height=parseInt(window.innerHeight)-messagebox.offsetHeight-errorbox.offsetHeight-headerHeight-20;\n";
    echo "if(document.getElementById('resources')) {   document.getElementById('resources').style.width=(window.innerWidth*0.4);}}\n";
    echo " else {\n";
    echo "if (document.body.offsetWidth<1020){document.body.scroll='no';}   contentbox.style.height=parseInt(document.body.offsetHeight)-messagebox.offsetHeight-headerHeight-errorbox.offsetHeight-20;document.getElementById('resources').style.width=(document.body.offsetWidth*0.4);\n";
    echo "}for (var i = 0; i < document.links.length; ++i) {\n";
    echo "  if (document.links[i].href.search(/.*redir\.php\?url=.*/) != -1) {\n";
    echo "    document.links[i].target = '_blank';\n";
    echo "  }\n";
    echo "}\n";
    echo "</script>\n";
    echo "<style>\n";
    echo ".layer {\n";
    echo "    z-index:999999999;\n";
    echo "    position:absolute;\n";
    echo "    left: 0;\n";
    echo "    right: 0;\n";
    echo "    top: 100px;\n";
    echo "    margin-left: auto;\n";
    echo "    margin-right: auto;\n";
    echo "    width: 757px; \n";
    echo "    height: 475px; \n";
    echo "    background-color: #040e1e;\n";
    echo "    border: 3px double orange;\n";
    echo "    padding: 0;\n";
    echo "    opacity: .90;\n";
    echo "}\n";
    echo "</style>\n";

/*
    if ( $popup == false ) {
?>
<script type="text/javascript">
var myScroll = new iScroll('content');
</script>
<?php
    }
*/

    echo "</body></html>\n";
}

function InvalidSessionPage ()
{
    global $GlobalUser;

    $unitab = LoadUniverse ();
    $uni = $unitab['num'];

    echo "<html> <head>\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/default.css' />\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/formate.css' />\n";
    echo "  <meta http-equiv='content-type' content='text/html; charset=UTF-8' />\n";
    echo "  <title>Вселенная $uni ОГейм</title>\n";
    echo " </head>\n";
    echo " <body>\n";
    echo "  <center><font size='3'><b>    <br /><br />\n";
    echo "    <font color='#FF0000'>Произошла ошибка</font>\n";
    echo "    <br /><br />\n";
    echo "    Сессия недействительна.<br/><br/>Это может быть вызвано несколькими причинами: \n";
    echo "<br>- Вы несколько раз зашли в один и тот же аккаунт; \n";
    echo "<br>- Ваш ай-пи адрес изменился с момента последнего входа; \n";
    echo "<br>- Вы пользуетесь интернетом через AOL или прокси. Отключите проверку ай-пи в меню \"Настройки\" в Вашем аккаунте.    \n";
    echo "    <br /><br />\n";
    echo "    Error-ID: MD5  </b></font></center> </body></html>\n";
}

function Goto ($page, $param="")
{
    global $GlobalUser;
    ob_end_clean ();
    $url = "index.php?page=$page&session=".$GlobalUser['session'].$param;
    @header( 'Location: ' . $url);
    die ( "<html><head><meta http-equiv='refresh' content='0;url=$url' /></head><body></body></html>" );
}

?>