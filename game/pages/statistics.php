<?php

// Статистика

loca_add ( "menu", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("statistics");

$start = -1;
if ( key_exists ( "start", $_REQUEST ) ) $start = intval($_REQUEST['start']);

$type = "";
if ( key_exists ( "type", $_REQUEST ) ) $type = $_REQUEST['type'];

$who = "player";
if ( key_exists ( "who", $_REQUEST ) ) $who = $_REQUEST['who'];
?>

<!-- CONTENT AREA --> 
<div id='content'> 
<center> 
<!-- begin header form --> 
<form method="post" action='index.php?page=statistics&session=<?php echo $session;?>' > 
  
  <!-- begin head table --> 
  <table width="525"> 
    <tr> 
      <td class="c">Статистика (по состоянию на: <?php echo date ("Y-m-d, H:i:s", $now);?>)</td> 
    </tr> 
    <tr> 
      <th> 
        
 
        Какой&nbsp;
          
        <select name="who"> 
          <option value="player" <?php if( $who === 'player' ) echo "selected";?>>Игрок</option> 
          <option value="ally" <?php if( $who === 'ally' ) echo "selected";?>>Альянс</option> 
        </select> 
          
        &nbsp;по&nbsp;
              
        <select name="type"> 
          <option value="ressources" <?php if ($type==="ressources") echo "selected"; ?>>Очкам</option> 
          <option value="fleet" <?php if ($type==="fleet") echo "selected"; ?>>Флотам</option> 
          <option value="research" <?php if ($type==="research") echo "selected"; ?>>Исследованиям</option> 
        </select> 
          
        &nbsp;на месте        <select name="start"> 
          <option value="-1" <?php if ( $start == -1 ) echo "selected";?>>[Собственная позиция]</option> 
<?php
    // Выпадающий список игроков/альянсов

    if ( $who === 'ally' ) {
        $query = "SELECT * FROM ".$db_prefix."ally";
        $result = dbquery ($query );
        $count = dbrows ($result);
    }
    else {
        $uni = $GlobalUni;
        $count = $uni['usercount'];
    }

    $i = 1;
    do {
        echo "          <option value=\"$i\" ";
        if ( $start == $i ) echo "selected";
        echo ">$i-".($i+99)."</option> \n";
        $i += 100;
    } while ( $i < $count );
?>
        </select> 
          
        <input type="hidden" id="sort_per_member" name="sort_per_member" value="<?php echo intval($_REQUEST['sort_per_member']);?>" /> 
        <input type=submit value="Показать"> 
      </th> 
    </tr> 
  </table> 
  <!-- end head table --> 
    
</form> 
<!-- end header form --> 

<!-- begin statistic data --> 
<?php if ( $who === 'ally' ) { ?>
<!-- begin ally -->
<table width="519">
  <tr>
    <td class ="c" width="30">Место</td>
    <td class ="c">Альянс</td>
    <td class="c">&nbsp;</td>
    <td class ="c">Числ.</td>
    <td class ="c"><a href="#" onClick="document.getElementById('sort_per_member').value=0; javascript:document.forms[0].submit();">Тыс. очков</a></td>
    <td class ="c"><a href="#" onClick="document.getElementById('sort_per_member').value=1; javascript:document.forms[0].submit();">На человека</a></td>
  </tr>
<?php } else { ?>
<!-- begin user --> 
<table width="525"> 
  <tr> 
    <td class="c" width="30">Место</td> 
    <td class="c">Игрок</td> 
    <td class="c">&nbsp;</td> 
    <td class="c">Альянс</td> 
    <td class="c">Очки</td> 
  </tr>

<?php
}

if ( $type === "" ) $type = "ressources";

if ( $who === 'ally' ) {

    RecalcAllyStats ();
    RecalcAllyRanks ();

    if ( $type === "fleet" ) $query = "SELECT * FROM ".$db_prefix."ally WHERE place2 >= $start AND place2 < ".($start+99)." ORDER BY place2;";
    else if ( $type === "research" ) $query = "SELECT * FROM ".$db_prefix."ally WHERE place3 >= $start AND place3 < ".($start+99)." ORDER BY place3;";
    else $query = "SELECT * FROM ".$db_prefix."ally WHERE place1 >= $start AND place1 < ".($start+99)." ORDER BY place1;";

    $result = dbquery ($query);
    $rows = dbrows ($result);
    while ($rows--) {
        $ally = dbarray ($result);

        if ( $type === "fleet" ) { $place = $ally['place2']; $diff = $ally['place2'] - $ally['oldplace2']; $score = $ally['score2']; }
        else if ( $type === "research" ) { $place = $ally['place3']; $diff = $ally['place3'] - $ally['oldplace3']; $score = $ally['score3']; }
        else { $place = $ally['place1']; $diff = $ally['place1'] - $ally['oldplace1']; $score = floor($ally['score1'] / 1000); }

?>
  <tr>
  
    <!-- rank -->
    <th>
      <?php echo $place;?>&nbsp;&nbsp;

<?php
        if ( $diff < 0 ) echo "      <a href='#' onmouseover='return overlib(\"<font color=lime>+".abs($diff)."</font><br/><font color=white>С ".date ("Y-m-d H:i:s", $ally['scoredate'])."\");' onmouseout='return nd();'><font color='lime'>+</font></a> \n";
        else if ( $diff > 0 ) echo "      <a href='#' onmouseover='return overlib(\"<font color=red>-".abs($diff)."</font><br/><font color=white>С ".date ("Y-m-d H:i:s", $ally['scoredate'])."\");' onmouseout='return nd();'><font color='red'>-</font></a> \n";
        else echo "      <a href='#' onmouseover='return overlib(\"<font color=87CEEB>*</font><br/><font color=white>С ".date ("Y-m-d H:i:s", $ally['scoredate'])."\");' onmouseout='return nd();'><font color='87CEEB'>*</font></a> \n";            
?>    </th>
    
    <!--  name -->
    <th>

<?php
    if ( $ally['ally_id'] == $GlobalUser['ally_id'] ) echo "      <a href=\"#\" style='color:lime;'>\n";
    else echo "      <a href=\"ainfo.php?allyid=".$ally['ally_id']."\" target='_ally'>      \n";
?>
 
      <?php echo $ally['tag'];?>    </a>
    </th>
    
    <!-- bewerben -->
    <th>
<?php
    if ( $GlobalUser['ally_id'] == 0 ) {
        echo "      <a href=\"index.php?page=bewerben&session=".$session."&allyid=".$ally['ally_id']."\">\n";
        echo "        <img src=\"".UserSkin()."/img/m.gif\" border=\"0\" alt=\"Написать сообщение\" />\n";
        echo "      </a>\n";
    }
?>      &nbsp;
    </th>
    
    <!-- amount members -->
    <th>
      <?php
    $query = "SELECT * FROM ".$db_prefix."users WHERE ally_id = " . $ally['ally_id'];
    $res = dbquery ( $query );
    $members = dbrows ( $res );
    echo "$members";
?> </th>
    
    <!-- points -->
    <th>
      <?php echo nicenum($score);?>     
      
    </th>
    
    <!-- points per member -->
    <th>
      
      <?php echo nicenum ( ceil ( $score / $members) ) ;?>
              
    </th>
    
  </tr>
  
  <tr>
<?php
    }

    echo "</table>\n";
    echo "<!-- end ally -->\n";
}

else {

    if ( $start <= 0 ) {
        if ( $type === "fleet" ) $start = (floor($GlobalUser['place2']/100)*100+1);
        else if ( $type === "research" ) $start = (floor($GlobalUser['place3']/100)*100+1);
        else $start = (floor($GlobalUser['place1']/100)*100+1);
    }

    if ( $type === "fleet" ) $query = "SELECT * FROM ".$db_prefix."users WHERE place2 >= $start AND place2 < ".($start+99)." ORDER BY place2;";
    else if ( $type === "research" ) $query = "SELECT * FROM ".$db_prefix."users WHERE place3 >= $start AND place3 < ".($start+99)." ORDER BY place3;";
    else $query = "SELECT * FROM ".$db_prefix."users WHERE place1 >= $start AND place1 < ".($start+99)." ORDER BY place1;";

    $result = dbquery ($query);
    $rows = dbrows ($result);
    while ($rows--) {
        $user = dbarray ($result);

        if ( $type === "fleet" ) { $place = $user['place2']; $diff = $user['place2'] - $user['oldplace2']; $score = $user['score2']; }
        else if ( $type === "research" ) { $place = $user['place3']; $diff = $user['place3'] - $user['oldplace3']; $score = $user['score3']; }
        else { $place = $user['place1']; $diff = $user['place1'] - $user['oldplace1']; $score = floor($user['score1'] / 1000); }

        echo "  <tr> \n";
        echo "    <!-- rank --> \n";
        echo "    <th> \n";
        echo "      $place&nbsp;&nbsp;\n\n";
        if ( $diff < 0 ) echo "      <a href='#' onmouseover='return overlib(\"<font color=lime>+".abs($diff)."</font><br/><font color=white>С ".date ("Y-m-d H:i:s", $user['scoredate'])."\");' onmouseout='return nd();'><font color='lime'>+</font></a> \n";
        else if ( $diff > 0 ) echo "      <a href='#' onmouseover='return overlib(\"<font color=red>-".abs($diff)."</font><br/><font color=white>С ".date ("Y-m-d H:i:s", $user['scoredate'])."\");' onmouseout='return nd();'><font color='red'>-</font></a> \n";
        else echo "      <a href='#' onmouseover='return overlib(\"<font color=87CEEB>*</font><br/><font color=white>С ".date ("Y-m-d H:i:s", $user['scoredate'])."\");' onmouseout='return nd();'><font color='87CEEB'>*</font></a> \n";
        echo "    </th> \n\n";

        $home = GetPlanet ( $user['hplanetid'] );
        echo "    <!-- nick --> \n";
        echo "    <th> \n";
        if ( $user['player_id'] == $GlobalUser['player_id'] ) {
            echo "<a href=\"#\" style='color:lime;'>\n";
            echo $user['oname'] . "</a>\n";
        }
        else {
            echo "       <a href=\"index.php?page=galaxy&no_header=1&session=$session&p1=".$home['g']."&p2=".$home['s']."&p3=".$home['p']."\" style='color:FFFFFF' >      \n\n";
            echo $user['oname'] . "</a> \n";
        }
        echo "    </th> \n\n";

        echo "    <!--  message-icon --> \n";
        echo "    <th> \n";
        if ( $user['player_id'] != $GlobalUser['player_id'] ) {
            echo "      <a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\"> \n";
            echo "        <img src=\"".UserSkin()."img/m.gif\" border=\"0\" alt=\"Написать сообщение\" /> \n";
            echo "      </a> \n";
        }
        echo "    &nbsp;\n";
        echo "    </th> \n\n";

        echo "    <!--  ally --> \n";
        echo "    <th> \n";
        if ( $user['ally_id'] == $GlobalUser['ally_id'] ) {
            $ally = LoadAlly ( $user['ally_id'] );
            echo " 	  <a href=\"index.php?page=allianzen&session=$session\">\n";
            echo "        ".$ally['tag']."      </a>\n";
        }
        else if ( $user['ally_id'] ) {
            $ally = LoadAlly ( $user['ally_id'] );
            echo "   	  <a href='ainfo.php?allyid=".$user['ally_id']."' target='_ally'>\n";
            echo "        ".$ally['tag']."      </a>\n";
        }
        else {
            echo "      <a href=\"index.php?page=allianzen&session=$session\"> \n";
            echo "              </a> \n";
        }
        echo "    </th> \n\n";

        echo "    <!-- points --> \n";
        echo "    <th> \n";
        echo "      ".nicenum($score)."    </th> \n\n";
        echo "  </tr> \n";
    }

    echo "</table>\n";
    echo "<!-- end user -->\n";
}
?>

<!-- end statistic data --><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>