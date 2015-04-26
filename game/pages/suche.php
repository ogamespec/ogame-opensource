<?php

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "search", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("suche");

$SEARCH_LIMIT = 25;
$SearchResult = "";
$searchtext = "";

// Вырезать из строки всякие инжекции.
function SecureText ( $text )
{
    $search = array ( "'<script[^>]*?>.*?</script>'si",  // Вырезает javaScript
                      "'<[\/\!]*?[^<>]*?>'si",           // Вырезает HTML-теги
                      "'([\r\n])[\s]+'" );             // Вырезает пробельные символы
    $replace = array ("", "", "\\1", "\\1" );
    $str = preg_replace($search, $replace, $text);
    $str = str_replace ("`", "", $str);
    $str = str_replace ("'", "", $str);
    $str = str_replace ("\"", "", $str);
    $str = str_replace ("%0", "", $str);
    return $str;
}

function search_selected ( $opt )
{
    if ( $_POST['type'] === $opt ) return "selected";
    else return "";
}

if ( method () === "POST" )
{
    $searchtext = SecureText ( $_POST['searchtext'] );

    $query = "";
    if ( $_POST['type'] === "playername" ) $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '".$searchtext."%' LIMIT $SEARCH_LIMIT";
    else if ( $_POST['type'] === "planetname" ) $query = "SELECT * FROM ".$db_prefix."planets WHERE name LIKE '".$searchtext."%' LIMIT $SEARCH_LIMIT";
    else if ( $_POST['type'] === "allytag" ) $query = "SELECT * FROM ".$db_prefix."ally WHERE tag LIKE '".$searchtext."%' LIMIT $SEARCH_LIMIT";
    else if ( $_POST['type'] === "allyname" ) $query = "SELECT * FROM ".$db_prefix."ally WHERE name LIKE '".$searchtext."%' LIMIT $SEARCH_LIMIT";

    if ( $query !== "" ) $result = dbquery ( $query );
    if ( $result )
    {
        $rows = dbrows ( $result );

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
                $SearchResult .= "<th>$name</th><th>$buttons</th><th> <a href='".$allyurl."' target='_ally'>".$ally['tag']."</a></th><th>".$homeplanet['name']."</th><th><a href=\"index.php?page=galaxy&no_header=1&session=$session&p1=".$homeplanet['g']."&p2=".$homeplanet['s']."&p3=".$homeplanet['p']."\">".$homeplanet['g'].":".$homeplanet['s'].":".$homeplanet['p']."</a></th><th><a href=\"index.php?page=statistics&session=$session&start=".(floor($user['place1']/100)*100+1)."\">".$user['place1']."</a></th></tr>\n";
            }
            else if ( $_POST['type'] === "planetname" )
            {
                $planet = dbarray ( $result );
                $user = LoadUser ( intval($planet['owner_id']) );
                $ally = LoadAlly ( intval($user['ally_id']) );
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
                $SearchResult .= "<th>$name</th><th>$buttons</th><th> <a href='".$allyurl."' target='_ally'>".$ally['tag']."</a></th><th>".$planet['name']."</th><th><a href=\"index.php?page=galaxy&no_header=1&session=$session&p1=".$planet['g']."&p2=".$planet['s']."&p3=".$planet['p']."\">".$planet['g'].":".$planet['s'].":".$planet['p']."</a></th><th><a href=\"index.php?page=statistics&session=$session&start=".(floor($user['place1']/100)*100+1)."\">".$user['place1']."</a></th></tr>\n";
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

?>

<!-- CONTENT AREA --> 
<div id='content'> 
<center> 
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
</center> 
</div> 
<!-- END CONTENT AREA --> 

<?php
PageFooter ();
ob_end_flush ();
?>