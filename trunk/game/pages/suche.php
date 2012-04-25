<?php

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( &$aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("suche");

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
    if ( $_POST['type'] === "playername" ) $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '".$searchtext."%' LIMIT 25";
    else if ( $_POST['type'] === "planetname" ) $query = "SELECT * FROM ".$db_prefix."planets WHERE name LIKE '".$searchtext."%' LIMIT 25";
    else if ( $_POST['type'] === "allytag" ) $query = "SELECT * FROM ".$db_prefix."ally WHERE tag LIKE '".$searchtext."%' LIMIT 25";
    else if ( $_POST['type'] === "allyname" ) $query = "SELECT * FROM ".$db_prefix."ally WHERE name LIKE '".$searchtext."%' LIMIT 25";

    if ( $query !== "" ) $result = dbquery ( $query );
    if ( $result )
    {
        $rows = dbrows ( $result );

        $SearchResult .= " <table width=\"519\">\n";

        if ( $_POST['type'] === "playername" )
        {
            $SearchResult .= "<tr>\n";
            $SearchResult .= "<td class=\"c\">Имя/Название</td>\n";
            $SearchResult .= "<td class=\"c\">&nbsp;</td>\n";
            $SearchResult .= "<td class=\"c\">Альянс</td>\n";
            $SearchResult .= "<td class=\"c\">Планета</td>\n";
            $SearchResult .= "<td class=\"c\">Координаты</td>\n";
            $SearchResult .= "<td class=\"c\">Очки</td>\n";
            $SearchResult .= "</tr>\n";
        }

        else if ( $_POST['type'] === "planetname" )
        {
            $SearchResult .= "<tr>\n";
            $SearchResult .= "<td class=\"c\">Имя/Название</td>\n";
            $SearchResult .= "<td class=\"c\">&nbsp;</td>\n";
            $SearchResult .= "<td class=\"c\">Альянс</td>\n";
            $SearchResult .= "<td class=\"c\">Планета</td>\n";
            $SearchResult .= "<td class=\"c\">Координаты</td>\n";
            $SearchResult .= "<td class=\"c\">Очки</td>\n";
            $SearchResult .= "</tr>\n";
        }

        else if ( $_POST['type'] === "allytag" || $_POST['type'] === "allyname" )
        {
            $SearchResult .= "<tr>\n";
            $SearchResult .= "<td class=\"c\">Аббревиатура</td>\n";
            $SearchResult .= "<td class=\"c\">Имя/Название</td>\n";
            $SearchResult .= "<td class=\"c\">Члены</td>\n";
            $SearchResult .= "<td class=\"c\">Очки</td>\n";
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
                $buttons = "<a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\" alt=\"Послать сообщение\" ><img src=\"".UserSkin()."/img/m.gif\" alt=\"Послать сообщение\" title=\"Послать сообщение\" /></a><a href='index.php?page=buddy&session=$session&action=7&buddy_id=".$user['player_id']."' alt='Предложение подружиться'><img src='".UserSkin()."/img/b.gif' border=0 alt='Предложение подружиться' title='Предложение подружиться'></a>";
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
                $buttons = "<a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\" alt=\"Послать сообщение\"><img src=\"".UserSkin()."/img/m.gif\" alt=\"Послать сообщение\" title=\"Послать сообщение\" /></a><a href='index.php?page=buddy&session=$session&action=7&buddy_id=".$user['player_id']."' alt='Предложение подружиться'><img src='".UserSkin()."/img/b.gif' border=0 alt='Предложение подружиться' title='Предложение подружиться'></a>";
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
 <form action="index.php?page=suche&session=<?=$session;?>" method="post"> 
 <table width="519"> 
  <tr> 
   <td class="c">Поиск</td> 
  </tr> 
  <tr> 
   <th> 
    <select name="type"> 
     <option value="playername" <?=search_selected("playername");?>>Имя игрока</option> 
     <option value="planetname" <?=search_selected("planetname");?>>Название планеты</option> 
     <option value="allytag" <?=search_selected("allytag");?>>Аббревиатура альянса</option> 
     <option value="allyname" <?=search_selected("allyname");?>>Название альянса</option> 
    </select> 
    &nbsp;&nbsp;
    <input type="text" name="searchtext" value="<?=$searchtext;?>"/> 
    &nbsp;&nbsp;
    <input type="submit" value="Искать" /> 
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