<?php

// Built in game search.

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "search", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("suche");

$SEARCH_LIMIT = 25;
$SearchResult = "";
$SearchMessage = "";
$SearchError = "";
$searchtext = "";

function search_selected ( $opt )
{
    if ( key_exists('type', $_POST) && $_POST['type'] === $opt ) return "selected";
    else return "";
}

if ( method () === "POST" )
{
    $searchtext = SecureText ( $_POST['searchtext'] );

    $text_len = mb_strlen ($searchtext, "UTF-8");
    if ($text_len && $text_len < 2) {
        $SearchError = loca ("SEARCH_ERROR_NOT_ENOUGH");
    }

    $query = "";
    if ($text_len >= 2) {

        // Purposely looking with a limit of 1 more to realize that the results are over the limit.

        if ( $_POST['type'] === "playername" ) $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '%".$searchtext."%' LIMIT " . ($SEARCH_LIMIT + 1);
        else if ( $_POST['type'] === "planetname" ) $query = "SELECT * FROM ".$db_prefix."planets WHERE name LIKE '%".$searchtext."%' LIMIT " . ($SEARCH_LIMIT + 1);
        else if ( $_POST['type'] === "allytag" ) $query = "SELECT * FROM ".$db_prefix."ally WHERE tag LIKE '%".$searchtext."%' LIMIT " . ($SEARCH_LIMIT + 1);
        else if ( $_POST['type'] === "allyname" ) $query = "SELECT * FROM ".$db_prefix."ally WHERE name LIKE '%".$searchtext."%' LIMIT " . ($SEARCH_LIMIT + 1);
    }

    $result = null;
    if ( $query !== "" ) $result = dbquery ( $query );
    if ( $result )
    {
        $rows = dbrows ( $result );

        // Display a message if there are too many results

        if ($rows > $SEARCH_LIMIT) {
            $rows = $SEARCH_LIMIT;
            if ( $_POST['type'] === "playername" || $_POST['type'] === "planetname" ) {
                $SearchMessage = va(loca("SEARCH_MAX_USERS_PLANETS"), $SEARCH_LIMIT);
            }
            else if ( $_POST['type'] === "allytag" || $_POST['type'] === "allyname" ) {
                $SearchMessage = va(loca("SEARCH_MAX_ALLY"), $SEARCH_LIMIT);
            }
        }

        // Display a message if nothing is found

        if ($rows == 0) {
            $SearchMessage = loca("SEARCH_NORESULT");
        }

        $SearchResult .= " <table width=\"519\">\n";

        if ( $_POST['type'] === "playername" || $_POST['type'] === "planetname" )
        {
            $SearchResult .= "<tr>\n";
            $SearchResult .= "<td class=\"c\">".loca("SEARCH_NAME")."</td>\n";
            $SearchResult .= "<td class=\"c\">&nbsp;</td>\n";
            $SearchResult .= "<td class=\"c\">".loca("SEARCH_ALLY")."</td>\n";
            $SearchResult .= "<td class=\"c\">".loca("SEARCH_PLANET")."</td>\n";
            $SearchResult .= "<td class=\"c\">".loca("SEARCH_COORDS")."</td>\n";
            $SearchResult .= "<td class=\"c\">".loca("SEARCH_PLACE")."</td>\n";
            $SearchResult .= "</tr>\n";
        }
        else if ( $_POST['type'] === "allytag" || $_POST['type'] === "allyname" )
        {
            $SearchResult .= "<tr>\n";
            $SearchResult .= "<td class=\"c\">".loca("SEARCH_TAG")."</td>\n";
            $SearchResult .= "<td class=\"c\">".loca("SEARCH_NAME")."</td>\n";
            $SearchResult .= "<td class=\"c\">".loca("SEARCH_MEMBERS")."</td>\n";
            $SearchResult .= "<td class=\"c\">".loca("SEARCH_POINTS")."</td>\n";
            $SearchResult .= "</tr>\n";
        }

        while ( $rows-- )
        {
            if ( $_POST['type'] === "playername" )
            {
                $user = dbarray ( $result );
                $homeplanet = GetPlanet ( intval($user['hplanetid']) );
                $ally = LoadAlly ( intval($user['ally_id']) );
                $ally_tag = "";
                if ($ally) {
                    $ally_tag = $ally['tag'];
                }
                $name = $user['oname'];
                $buttons = "<a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\" alt=\"".loca("SEARCH_MESSAGE")."\" ><img src=\"".UserSkin()."/img/m.gif\" alt=\"".loca("SEARCH_MESSAGE")."\" title=\"".loca("SEARCH_MESSAGE")."\" /></a><a href='index.php?page=buddy&session=$session&action=7&buddy_id=".$user['player_id']."' alt='".loca("SEARCH_BUDDY")."'><img src='".UserSkin()."/img/b.gif' border=0 alt='".loca("SEARCH_BUDDY")."' title='".loca("SEARCH_BUDDY")."'></a>";
                $allyurl = "ainfo.php?allyid=".$user['ally_id'];
                if ( $user['player_id'] == $GlobalUser['player_id'] ) {
                    $name = "<font color=\"lime\">$name</font>";
                    $buttons = "&nbsp;";
                    $allyurl = "index.php?page=allianzen&session=$session";
                }
                else if ( $user['ally_id'] == $GlobalUser['ally_id'] && $user['ally_id'] != 0 ) {
                    $name = "<font color=\"#87CEEB\">$name</font>";
                    $allyurl = "index.php?page=allianzen&session=$session";
                }
                $SearchResult .= "<tr>\n";
                $SearchResult .= "<th>$name</th><th>$buttons</th><th> <a href='".$allyurl."' target='_ally'>".$ally_tag."</a></th><th>".$homeplanet['name']."</th><th><a href=\"index.php?page=galaxy&no_header=1&session=$session&p1=".$homeplanet['g']."&p2=".$homeplanet['s']."&p3=".$homeplanet['p']."\">".$homeplanet['g'].":".$homeplanet['s'].":".$homeplanet['p']."</a></th><th><a href=\"index.php?page=statistics&session=$session&start=".(floor($user['place1']/100)*100+1)."\">".$user['place1']."</a></th></tr>\n";
            }
            else if ( $_POST['type'] === "planetname" )
            {
                $planet = dbarray ( $result );
                $user = LoadUser ( intval($planet['owner_id']) );
                $ally = LoadAlly ( intval($user['ally_id']) );
                $ally_tag = "";
                if ($ally) {
                    $ally_tag = $ally['tag'];
                }
                $name = $user['oname'];
                $buttons = "<a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\" alt=\"".loca("SEARCH_MESSAGE")."\"><img src=\"".UserSkin()."/img/m.gif\" alt=\"".loca("SEARCH_MESSAGE")."\" title=\"".loca("SEARCH_MESSAGE")."\" /></a><a href='index.php?page=buddy&session=$session&action=7&buddy_id=".$user['player_id']."' alt='".loca("SEARCH_BUDDY")."'><img src='".UserSkin()."/img/b.gif' border=0 alt='".loca("SEARCH_BUDDY")."' title='".loca("SEARCH_BUDDY")."'></a>";
                $allyurl = "ainfo.php?allyid=".$user['ally_id'];
                if ( $user['player_id'] == $GlobalUser['player_id'] ) {
                    $name = "<font color=\"lime\">$name</font>";
                    $buttons = "&nbsp;";
                    $allyurl = "index.php?page=allianzen&session=$session";
                }
                else if ( $user['ally_id'] == $GlobalUser['ally_id'] && $user['ally_id'] != 0 ) {
                    $name = "<font color=\"#87CEEB\">$name</font>";
                    $allyurl = "index.php?page=allianzen&session=$session";
                }
                $SearchResult .= "<tr>\n";
                $SearchResult .= "<th>$name</th><th>$buttons</th><th> <a href='".$allyurl."' target='_ally'>".$ally_tag."</a></th><th>".$planet['name']."</th><th><a href=\"index.php?page=galaxy&no_header=1&session=$session&p1=".$planet['g']."&p2=".$planet['s']."&p3=".$planet['p']."\">".$planet['g'].":".$planet['s'].":".$planet['p']."</a></th><th><a href=\"index.php?page=statistics&session=$session&start=".(floor($user['place1']/100)*100+1)."\">".$user['place1']."</a></th></tr>\n";
            }
            else if ( $_POST['type'] === "allytag" || $_POST['type'] === "allyname" )
            {
                $ally = dbarray ( $result );
                $tag = $ally['tag'];
                $allyurl = "ainfo.php?allyid=".$ally['ally_id'];
                if ( $ally['ally_id'] == $GlobalUser['ally_id'] && $ally['ally_id'] != 0 ) {
                    $tag = "<font color=\"lime\">$tag";
                    $allyurl = "index.php?page=allianzen&session=$session";
                }
                $SearchResult .= "<tr>\n";
                $SearchResult .= "<th><a href='".$allyurl."' target='_ally'>$tag</font></a></th><th>".$ally['name']."</th><th>4</th><th>0</th></tr>\n";
            }
        }

        $SearchResult .= "</table>";
    }
}

BeginContent ();
?>
 <!-- begin search header --> 
 <form action="index.php?page=suche&session=<?php echo $session;?>" method="post"> 
 <table width="519"> 
  <tr> 
   <td class="c"><?php echo loca("SEARCH_SEARCH");?></td> 
  </tr> 
  <tr> 
   <th> 
    <select name="type"> 
     <option value="playername" <?php echo search_selected("playername");?>><?php echo loca("SEARCH_SEL_USER");?></option> 
     <option value="planetname" <?php echo search_selected("planetname");?>><?php echo loca("SEARCH_SEL_PLANET");?></option> 
     <option value="allytag" <?php echo search_selected("allytag");?>><?php echo loca("SEARCH_SEL_TAG");?></option> 
     <option value="allyname" <?php echo search_selected("allyname");?>><?php echo loca("SEARCH_SEL_ALLY");?></option> 
    </select> 
    &nbsp;&nbsp;
    <input type="text" name="searchtext" value="<?php echo $searchtext;?>"/> 
    &nbsp;&nbsp;
    <input type="submit" value="<?php echo loca("SEARCH_BUTTON");?>" /> 
   </th> 
  </tr> 
 </table> 
 </form> 
 <!-- end search header --> 
  <!-- begin search results --> 
<?php
    echo "$SearchResult";
?>
 <!-- end search results --> 
<br><br><br><br> 
<?php
EndContent ();
PageFooter ($SearchMessage, $SearchError);
ob_end_flush ();
?>