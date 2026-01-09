<?php

// Common elements of game pages (left menu, resource bar, etc.)

$pagetime = 0;

// Get a small picture of the planet.
function GetPlanetSmallImage (string $skinpath, array $planet) : string
{
    $img = array();
    if (ModsExecRefRef('get_planet_small_image', $planet, $img)) {
        return $img['path'];
    }
    if ( $planet['type'] == PTYP_MOON || $planet['type'] == PTYP_DEST_MOON ) return $skinpath."planeten/small/s_mond.jpg";
    else if ($planet['type'] == PTYP_DF) return $skinpath."planeten/debris.jpg";    
    else if ($planet['type'] < PTYP_DF )
    {
        $p = $planet['p'];
        $id = $planet['planet_id'] % 7 + 1;
        if ($p <= 3) return sprintf ( "%splaneten/small/s_trockenplanet%02d.jpg", $skinpath, $id);
        else if ($p >= 4 && $p <= 6) return sprintf ( "%splaneten/small/s_dschjungelplanet%02d.jpg", $skinpath, $id);
        else if ($p >= 7 && $p <= 9) return sprintf ( "%splaneten/small/s_normaltempplanet%02d.jpg", $skinpath, $id);
        else if ($p >= 10 && $p <= 12) return sprintf ( "%splaneten/small/s_wasserplanet%02d.jpg", $skinpath, $id);
        else if ($p >= 13 && $p <= 15) return sprintf ( "%splaneten/small/s_eisplanet%02d.jpg", $skinpath, $id);
        else return sprintf ( "%splaneten/small/s_eisplanet%02d.jpg", $skinpath, $id);
    }
    else return "img/admin_planets.png";        // Special objects of the galaxy (destroyed planets, etc.)
}

// Get a big picture of the planet.
function GetPlanetImage (string $skinpath, array $planet) : string
{
    $img = array();
    if (ModsExecRefRef('get_planet_image', $planet, $img)) {
        return $img['path'];
    }
    if ( $planet['type'] == PTYP_MOON || $planet['type'] == PTYP_DEST_MOON ) return $skinpath."planeten/mond.jpg";
    else if ($planet['type'] == PTYP_DF) return $skinpath."planeten/debris.jpg";
    else if ($planet['type'] < PTYP_DF )
    {
        $p = $planet['p'];
        $id = $planet['planet_id'] % 7 + 1;
        if ($p <= 3) return sprintf ( "%splaneten/trockenplanet%02d.jpg", $skinpath, $id);
        else if ($p >= 4 && $p <= 6) return sprintf ( "%splaneten/dschjungelplanet%02d.jpg", $skinpath, $id);
        else if ($p >= 7 && $p <= 9) return sprintf ( "%splaneten/normaltempplanet%02d.jpg", $skinpath, $id);
        else if ($p >= 10 && $p <= 12) return sprintf ( "%splaneten/wasserplanet%02d.jpg", $skinpath, $id);
        else if ($p >= 13 && $p <= 15) return sprintf ( "%splaneten/eisplanet%02d.jpg", $skinpath, $id);
        else return sprintf ( "%splaneten/eisplanet%02d.jpg", $skinpath, $id);
    }
    else return "img/admin_planets.png";        // Special objects of the galaxy (destroyed planets, etc.)
}

function UserSkin () : string
{
    global $GlobalUser;
    if ( key_exists('useskin', $GlobalUser) && $GlobalUser['useskin']) return $GlobalUser['skin'];
    else return hostname () . "evolution/";
}

function PageHeader (string $page, bool $noheader=false, bool $leftmenu=true, string $redirect_page="", int $redirect_sec=0) : void
{
    global $pagetime;
    global $GlobalUser;
    global $GlobalUni;

    BrowseHistory ();

    $mtime = microtime(); 
    $mtime = explode(" ",$mtime); 
    $mtime = (float)$mtime[1] + (float)$mtime[0]; 
    $pagetime = $mtime;

    $unitab = $GlobalUni;
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
    echo "<title>".va(loca("PAGE_TITLE"), $uni)."</title>\n";
    echo "  <script src='js/utilities.js' type='text/javascript'></script>\n";
    echo "  <script language='JavaScript'>\n";
    echo "  </script>\n";
    echo "<script type='text/javascript' src='js/overLib/overlib.js'></script>\n";
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
        ResourceList ($aktplanet, 
            (int)floor($aktplanet['m']), (int)floor($aktplanet['k']), (int)floor($aktplanet['d']), 
            $aktplanet['e'], $aktplanet['emax'], 
            $GlobalUser['dm']+$GlobalUser['dmfree'], $aktplanet['mmax'], $aktplanet['kmax'], $aktplanet['dmax']);
        $coma = OficeerList ();
        echo "</tr>\n";
        echo "</table>\n";
        echo "</div><!-- END HEADER -->\n\n";
    }
    else 
    {
        $end = GetOfficerLeft ( $GlobalUser, USER_OFFICER_COMMANDER );
        $coma = $end > time ();
    }

    echo "<!-- LEFTMENU -->\n\n";
    if ($leftmenu) LeftMenu ($coma);
    echo "<!-- END LEFTMENU -->\n\n";
}

function DropListHasMoon (array $plist, array $planet) : mixed
{
    foreach ( $plist as $i=>$p )
    {
        if ( $p['type'] == PTYP_MOON ) {
            if ( $p['g'] == $planet['g'] && $p['s'] == $planet['s'] && $p['p'] == $planet['p'] ) return $p;
        }
    }
    return null;
}

function PlanetsDropList (string $page) : void
{
    global $GlobalUser;
    $sess = $GlobalUser['session'];
    $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
    $result = EnumPlanets ();

    echo "<td class='header' style='width:5;' >\n";
    echo "<table class='header'>\n";
    echo "<tr class='header'>\n";
    echo "<td class='header'><img src='".GetPlanetSmallImage(UserSkin(), $aktplanet)."' width='50' height='50'></td>\n";
    echo "<td class='header'>\n";
    echo "<table class='header'>\n";
    echo "<select size='1' onchange='haha(this)'>\n";

    $plist = array ();
    $num = dbrows ($result);
    for ($n=0; $n<$num; $n++) $plist[] = dbarray ($result);

    $gid = $tid = $mode = "";

    if (key_exists ('gid', $_GET)) $gid = "&gid=".$_GET['gid'];
    if (key_exists ('tid', $_GET)) $tid = "&tid=".$_GET['tid'];
    if (key_exists ('mode', $_GET)) $mode = "&mode=".$_GET['mode'];

    for ($n=0; $n<$num; $n++)
    {
        $planet = $plist[$n];
        if ($planet['type'] == PTYP_MOON) continue;
        $cp = $planet['planet_id'];
        $sel = "";
        if ($cp == $GlobalUser['aktplanet']) $sel = "selected";
        $g = $planet['g']; $s = $planet['s']; $p = $planet['p'];
        $name = $planet['name'];
        echo "    <option value='index.php?page=".$page."&session=$sess&cp=$cp$gid$tid$mode' $sel>$name  <a href='index.php?page=galaxy&galaxy=$g&system=$s&position=$p&session=$sess&cp=$cp$gid$tid$mode' >[$g:$s:$p]</a></option>\n";

        $moon = DropListHasMoon ($plist, $planet);
        if ($moon) {
            $planet = $moon;
            $cp = $planet['planet_id'];
            $sel = "";
            if ($cp == $GlobalUser['aktplanet']) $sel = "selected";
            $g = $planet['g']; $s = $planet['s']; $p = $planet['p'];
            $name = $planet['name'];
            echo "    <option value='index.php?page=".$page."&session=$sess&cp=$cp$gid$tid$mode' $sel>$name  <a href='index.php?page=galaxy&galaxy=$g&system=$s&position=$p&session=$sess&cp=$cp$gid$tid$mode' >[$g:$s:$p]</a></option>\n";
        }
    }

    echo "</select></table></td></tr></table></td>\n\n";
}

function LoadJsonFirst (string $path) : array
{
    $json_contents = file_get_contents($path);
    if (!$json_contents) {
        Error ("Error loading JSON-first schema");
    }
    $json = json_decode($json_contents, true);
    if ($json === null) {
        Error ("Error decoding JSON-first schema");
    }
    return $json;
}

function ResourceList (array $aktplanet, int $m, int $k, int $d, int $enow, int $emax, int $dm, int $mmax, int $kmax, int $dmax) : void
{
    global $GlobalUser;
    $sess = $GlobalUser['session'];

    $mcol = $kcol = $dcol = $ecol = "";
    if ($m >= $mmax) $mcol = "#ff0000";
    if ($k >= $kmax) $kcol = "#ff0000";
    if ($d >= $dmax) $dcol = "#ff0000";
    if ($enow < 0) $ecol = "#ff0000";

    $json = LoadJsonFirst ("pages/resources.json");

    $json['metall']['val'] = $m;
    $json['metall']['color'] = $mcol;
    $json['kristall']['val'] = $k;
    $json['kristall']['color'] = $kcol;
    $json['deuterium']['val'] = $d;
    $json['deuterium']['color'] = $dcol;
    $json['dm']['val'] = $dm;
    $json['energie']['val'] = $enow;
    $json['energie']['val2'] = $emax;
    $json['energie']['color'] = $ecol;

    ModsExecRefArr ('add_resources', $json, $aktplanet);

    //print_r ($json);

    // Row 1 (Icons)
    echo "<td class='header'><table class='header' id='resources' border='0' cellspacing='0' cellpadding='0' padding-right='30' >\n";
    echo "<tr class='header'>\n";
    foreach ($json as $res) {

        echo "<td align='center' width='85' class='header'>\n";
        if (key_exists('href', $res)) {
            echo "<a href=index.php?page=".$res['href']."&session=$sess>\n";
        }
        echo "<img border='0' src='";
        if ($res['skin']) {
            echo UserSkin();
        }
        echo $res['img'] . "' width='42' height='22'";
        if (key_exists('title', $res)) {
            echo " title='".loca($res['title'])."'";
        }
        echo ">";
        if (key_exists('href', $res)) {
            echo "</a>";
        }
        echo "\n</td>\n";
    }
    echo "</tr>\n";

    // Row 2 (Names)
    echo "<tr class='header'>\n";
    foreach ($json as $res) {
        echo "    <td align='center' class='header' width='85'><i><b><font color='#ffffff'>".loca($res['loca'])."</font></b></i></td>\n";
    }
    echo "</tr>\n";

    // Row 3 (Values)
    echo "<tr class='header'>\n";
    foreach ($json as $res) {
        $col = "";
        if ($res['color'] !== "") {
            $col = "color='".$res['color']."'";
        }
        echo "    <td align='center' class='header' width='90'><font $col>".nicenum($res['val'])."</font>";
        if (key_exists('val2', $res)) {
            echo "/".nicenum($res['val2']);
        }
        echo "</td>\n";
    }
    echo "</tr>\n";    

    echo "</table></td>\n";
}

function calco (int $now, int $who) : array
{
    global $GlobalUser;
    $reply = array ();
    $end = GetOfficerLeft ( $GlobalUser, $who );
    if ($end <= $now) {
        $reply['img'] = "_un";
        $reply['days'] = '';
        $reply['action'] = loca("PR_PURCHASE");
    }
    else
    {
        $d = ($end - $now) / (60*60*24);
        if ( $d  > 0 )
        {
            $reply['days'] = va(loca("PR_ACTIVE_DAYS"), ceil($d));
            $reply['action'] = loca("PR_RENEW");
            $reply['img'] = '';
        }
    }
    return $reply;
}

function OficeerList () : bool
{
    global $GlobalUser;
    $sess = $GlobalUser['session'];
    $img = array ( 'commander' => '', 'admiral' => '', 'engineer' => '', 'geologist' => '', 'technocrat' => '' );
    $days = array ( 'commander' => '', 'admiral' => '', 'engineer' => '', 'geologist' => '', 'technocrat' => '' );
    $action = array ( 'commander' => '', 'admiral' => '', 'engineer' => '', 'geologist' => '', 'technocrat' => '' );

    $now = time ();

    $reply = calco ($now, USER_OFFICER_COMMANDER);
    $img['commander'] = $reply['img'];
    $days['commander'] = $reply['days'];
    $action['commander'] = $reply['action'];

    $reply = calco ($now, USER_OFFICER_ADMIRAL);
    $img['admiral'] = $reply['img'];
    $days['admiral'] = $reply['days'];
    $action['admiral'] = $reply['action'];

    $reply = calco ($now, USER_OFFICER_ENGINEER);
    $img['engineer'] = $reply['img'];
    $days['engineer'] = $reply['days'];
    $action['engineer'] = $reply['action'];

    $reply = calco ($now, USER_OFFICER_GEOLOGE);
    $img['geologist'] = $reply['img'];
    $days['geologist'] = $reply['days'];
    $action['geologist'] = $reply['action'];

    $reply = calco ($now, USER_OFFICER_TECHNOCRATE);
    $img['technocrat'] = $reply['img'];
    $days['technocrat'] = $reply['days'];
    $action['technocrat'] = $reply['action'];

    echo "<td class='header'>\n";
    echo "<table class='header' align=left>\n";
    echo "<tr class='header'>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/commander_ikon".$img['commander'].".gif' width='32' height='32' alt='".loca("PR_COMA")."'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['commander']."<br>".loca("PR_COMA")."</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['commander']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/admiral_ikon".$img['admiral'].".gif' width='32' height='32' alt='".loca("PR_ADMIRAL")."'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['admiral']."<br>".loca("PR_ADMIRAL")."</font><br><font size=1 color=skyblue>".loca("PR_ADMIRAL_INFO")."</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['admiral']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/ingenieur_ikon".$img['engineer'].".gif' width='32' height='32' alt='".loca("PR_ENGINEER")."'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['engineer']."<br>".loca("PR_ENGINEER")."</font><br><font size=1 color=skyblue>".loca("PR_ENGINEER_INFO")."</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['engineer']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/geologe_ikon".$img['geologist'].".gif' width='32' height='32' alt='".loca("PR_GEOLOGIST")."'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['geologist']."<br>".loca("PR_GEOLOGIST")."</font><br><font size=1 color=skyblue>".loca("PR_GEOLOGIST_INFO")."</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['geologist']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "    <td align='center' width='35' class='header'>\n";
    echo "    <a href='index.php?page=micropayment&session=$sess' accesskey='o' >\n";
    echo "	<img border='0' src='img/technokrat_ikon".$img['technocrat'].".gif' width='32' height='32' alt='".loca("PR_TECHNO")."'\n";
    echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days['technocrat']."<br>".loca("PR_TECHNO")."</font><br><font size=1 color=skyblue>".loca("PR_TECHNO_INFO")."</font><br><br><a href=index.php?page=micropayment&session=$sess><font size=1 color=lime>".$action['technocrat']."</b></font></a></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
    echo "    </a></td>\n\n";
    echo "<td align='center' class='header'></td></tr></table></td>\n\n";

    return $days['commander'] !== '';
}

function LeftMenu (bool $coma) : void
{
    global $GlobalUser;
    global $GlobalUni;
    $sess = $GlobalUser['session'];

    $unitab = $GlobalUni;
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
    echo "<a href='mailto:barrierefrei@ogame.de' title='".loca("MENU_DIS")."' style='width:1px;'></a>\n";
    echo "<p style='width:110px;'><NOBR>".loca("MENU_UNIVERSE")." ".$uni." (<a href='index.php?page=changelog&session=".$sess."'>v 0.84</a>)</NOBR></p>\n";
    echo "<table width='110' cellspacing='0' cellpadding='0'>\n";

    $json = LoadJsonFirst ("pages/leftmenu.json");

    // Admin Area
    if ($GlobalUser['admin'] == 0) {
        if (isset($json["admin"])) {
            unset ($json["admin"]);
        }
    }

    // Empire
    if (!$coma) {
        if (isset($json["imperium"])) {
            unset ($json["imperium"]);
        }
    }

    // External links
    $ext_links = array ( 'ext_board', 'ext_discord', 'ext_tutorial', 'ext_rules', 'ext_impressum');
    foreach ($ext_links as $ext_link) {
        if (isset($json[$ext_link])) {
            if (empty($unitab[$ext_link])) {
                unset ($json[$ext_link]);
            }
            else {
                $json[$ext_link]['url'] = $unitab[$ext_link];
            }
        }
    }

    ModsExecRef ('add_menuitems', $json);

    //print_r ($json);

    foreach ($json as $item) {

        switch ($item['type']) {

            case "img":
                echo " <tr>\n";
                echo "  <td><img src='";
                if ($item['skin']) {
                    echo UserSkin();
                }
                echo $item['url'] . "' width='".$item['width']."' height='".$item['height']."' /></td>\n";
                echo " </tr>\n\n";
                break;

            case "internal":
                echo " <tr>\n";
                echo "  <td>\n";
                echo "   <div align=\"center\">";
                echo "<font color=\"#FFFFFF\">";
                echo "\n";
                echo "     <a href='index.php?page=".$item['page']."&session=".$sess;
                if (key_exists('param', $item)) {
                    echo $item['param'];
                }
                echo "'";
                if (key_exists('accesskey', $item)) {
                    echo " accesskey=\"".loca($item['accesskey'])."\"";
                }
                echo ">";
                if (key_exists('color', $item)) {
                    echo "<font color='".$item['color']."'>";
                }
                echo loca($item['loca']);
                if (key_exists('color', $item)) {
                    echo "</font>";
                }
                echo "</a>";
                if (key_exists('notes', $item)) {
                    echo " <!-- ".$item['notes']." -->";
                }
                echo "\n";
                echo "    ";
                echo "</font>";
                echo "</div>\n";
                echo "  </td>\n";
                echo " </tr>\n\n";
                break;

            // Special Case for Officers (buggy menu item)
            case "internal_buggy":
                echo " <tr>\n";
                echo "    <td align=center>";
                echo "\n";
                echo "     <a ";
                echo $item['additional_style'] . "\n         ";
                echo "href='index.php?page=".$item['page']."&session=".$sess;
                if (key_exists('param', $item)) {
                    echo $item['param'];
                }
                echo "'";
                if (key_exists('accesskey', $item)) {
                    echo " accesskey=\"".loca($item['accesskey'])."\"";
                }
                echo ">";
                if (key_exists('color', $item)) {
                    echo "<font color='".$item['color']."'>";
                }
                echo "<b>" . loca($item['loca']);
                if (key_exists('color', $item)) {
                    echo "</font>";
                }
                echo "</a></b>";
                if (key_exists('notes', $item)) {
                    echo " <!-- ".$item['notes']." -->";
                }
                echo "\n";
                echo "    ";
                echo "</div>\n";
                echo "  </td>\n";
                echo " </tr>\n\n";
                break;

            case "popup":
                echo " <tr>\n";
                echo "  <td>\n";
                echo "   <div align=\"center\"><font color=\"#FFFFFF\">\n";
                echo "     <a href='#' onclick='fenster(\"index.php?page=".$item['page']."&session=".$sess;
                if (key_exists('param', $item)) {
                    echo $item['param'];
                }
                echo "\", \"".$item['title']."\");'";
                if (key_exists('accesskey', $item)) {
                    echo " accesskey=\"".loca($item['accesskey'])."\"";
                }
                echo ">".loca($item['loca'])."</a>\n";
                echo "    </font></div>\n";
                echo "  </td>\n";
                echo " </tr>\n\n";
                break;

            case "external":
                echo "  <tr> \n";
                echo "  <td> \n";
                echo "   <div align=\"center\"><font color=\"#FFFFFF\"> \n";
                echo "    <a href=\"".$item['url']."\" target=\"_blank\"";
                if (key_exists('accesskey', $item)) {
                    echo " accesskey=\"".loca($item['accesskey'])."\" ";
                }
                echo ">".loca($item['loca'])."</a>";
                if (key_exists('notes', $item)) {
                    echo " <!-- ".$item['notes']." -->";
                }
                echo "\n";
                echo "   </font></div> \n";
                echo "  </td> \n";
                echo " </tr> \n\n";
                break;
        }
    }

    echo " </table>\n";
    echo " </center>\n";
    echo "    </div>\n";
}

function PageFooter (string $msg="", string $error="", bool $popup=false, int $headerH=81, bool $nores=false) : void
{
    global $pagetime;
    global $GlobalUser;
    global $GlobalUni;
    global $query_counter;

    loca_add ("reg", $GlobalUser['lang']);

    if ( $GlobalUser['debug'] )
    {
        $mtime = microtime(); 
        $mtime = explode(" ",$mtime); 
        $mtime = (float)$mtime[1] + (float)$mtime[0];
        $endtime = $mtime;
        $msg = sprintf ( loca_lang("DEBUG_PAGE_INFO", $GlobalUni['lang']), $endtime-$pagetime, $query_counter) . GetSQLQueryLogText() . $msg;
    }

    if ( !$GlobalUser['validated']) $error = "<center> \n".va(loca("REG_NOT_ACTIVATED"), $GlobalUser['session'])."<br></center>\n" . $error;
    else if ( $GlobalUser['disable']) $error = "<center>\n".va(loca("REG_PENDING_DELETE"), date ("Y-m-d H:i:s", $GlobalUser['disable_until']))."<br></center>\n" . $error;

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
    if ($nores) echo "messagebox.style.top='0px';\n";
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
    echo "</body></html>\n";
}

function InvalidSessionPage () : void
{
    global $GlobalUser;

    $unitab = LoadUniverse ();
    $uni = $unitab['num'];

    loca_add ("common", $GlobalUser['lang']);
    loca_add ("reg", $GlobalUser['lang']);

    $error = array ( 'owner_id' => $GlobalUser['player_id'], 'ip' => $_SERVER['REMOTE_ADDR'], 'agent' => $_SERVER['HTTP_USER_AGENT'], 'url' => $_SERVER['REQUEST_URI'], 'text' => loca("REG_SESSION_INVALID"), 'date' => time() );
    $id = AddDBRow ( $error, 'errors' );

    echo "<html> <head>\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/default.css' />\n";
    echo "  <link rel='stylesheet' type='text/css' href='css/formate.css' />\n";
    echo "  <meta http-equiv='content-type' content='text/html; charset=UTF-8' />\n";
    echo "  <title>".va(loca_lang("PAGE_TITLE", $GlobalUser['lang']), $uni)."</title>\n";
    echo " </head>\n";
    echo " <body>\n";
    echo "  <center><font size='3'><b>    <br /><br />\n";
    echo "    <font color='#FF0000'>".loca_lang("REG_SESSION_ERROR", $GlobalUser['lang'])."</font>\n";
    echo loca_lang("REG_SESSION_ERROR_BODY", $GlobalUser['lang']);
    echo "    Error-ID: ".$id."  </b></font></center> </body></html>\n";
}

function MyGoto (string $page, string $param="") : void
{
    global $GlobalUser;
    ob_end_clean ();
    $url = "index.php?page=$page&session=".$GlobalUser['session'].$param;
    @header( 'Location: ' . $url);
    die ( "<html><head><meta http-equiv='refresh' content='0;url=$url' /></head><body></body></html>" );
}

function BeginContent () : void
{
    echo "<!-- CONTENT AREA -->\n";
    echo "<div id='content'>\n";
    echo "<center>\n";
    ModsExec ('begin_content');
}

function EndContent () : void
{
    ModsExec ('end_content');
    echo "</center>\n";
    echo "</div>\n";
    echo "<!-- END CONTENT AREA -->\n\n";
}

?>