<?php

// Статистика

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("statistics");

RecalcStats ( $GlobalUser['player_id'] );
RecalcRanks ();
?>

<!-- CONTENT AREA --> 
<div id='content'> 
<center> 
<!-- begin header form --> 
<form method="post" action='index.php?page=statistics&session=<?=$session;?>' > 
  
  <!-- begin head table --> 
  <table width="525"> 
    <tr> 
      <td class="c">Статистика (по состоянию на: 2008-12-12, 11:57:46)</td> 
    </tr> 
    <tr> 
      <th> 
        
 
        Какой&nbsp;
          
        <select name="who"> 
          <option value="player" selected>Игрок</option> 
          <option value="ally" >Альянс</option> 
        </select> 
          
        &nbsp;по&nbsp;
              
        <select name="type"> 
          <option value="ressources" >Очкам</option> 
          <option value="fleet" >Флотам</option> 
          <option value="research" >Исследованиям</option> 
        </select> 
          
        &nbsp;на месте        <select name="start"> 
          <option value="-1" >[Собственная позиция]</option> 
          <option value="1" >1-100</option> 
          <option value="101" >101-200</option> 
          <option value="201" >201-300</option> 
          <option value="301" >301-400</option> 
          <option value="401" >401-500</option> 
          <option value="501" >501-600</option> 
          <option value="601" >601-700</option> 
          <option value="701" >701-800</option> 
          <option value="801" >801-900</option> 
          <option value="901" >901-1000</option> 
          <option value="1001" >1001-1100</option> 
          <option value="1101" >1101-1200</option> 
          <option value="1201" >1201-1300</option> 
          <option value="1301" >1301-1400</option> 
          <option value="1401" >1401-1500</option> 
          <option value="1501" >1501-1600</option> 
          <option value="1601" >1601-1700</option> 
          <option value="1701" >1701-1800</option> 
          <option value="1801" >1801-1900</option> 
          <option value="1901" selected>1901-2000</option> 
          <option value="2001" >2001-2100</option> 
          <option value="2101" >2101-2200</option> 
          <option value="2201" >2201-2300</option> 
          <option value="2301" >2301-2400</option> 
          <option value="2401" >2401-2500</option> 
          <option value="2501" >2501-2600</option> 
          <option value="2601" >2601-2700</option> 
          <option value="2701" >2701-2800</option> 
          <option value="2801" >2801-2900</option> 
          <option value="2901" >2901-3000</option> 
          <option value="3001" >3001-3100</option> 
        </select> 
          
        <input type="hidden" id="sort_per_member" name="sort_per_member" value="0" /> 
        <input type=submit value="Показать"> 
      </th> 
    </tr> 
  </table> 
  <!-- end head table --> 
    
</form> 
<!-- end header form --> 

<!-- begin statistic data --> 
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

$query = "SELECT * FROM ".$db_prefix."users ORDER BY place1;";
$result = dbquery ($query);
$rows = dbrows ($result);
while ($rows--) {
    $user = dbarray ($result);
    $place = $user['place1'];
    $diff = $user['place1'] - $user['oldplace1'];

    echo "  <tr> \n";
    echo "    <!-- rank --> \n";
    echo "    <th> \n";
    echo "      $place&nbsp;&nbsp;\n\n";
    echo "      <a href='#' onmouseover='return overlib(\"<font color=87CEEB>*</font><br/><font color=white>С 2008-12-12 04:05:02\");' onmouseout='return nd();'><font color='87CEEB'>*</font></a> \n";
    echo "    </th> \n\n";

    $home = GetPlanet ( $user['hplanetid'] );
    echo "    <!-- nick --> \n";
    echo "    <th> \n";
    echo "       <a href=\"index.php?page=galaxy&no_header=1&session=$session&p1=".$home['g']."&p2=".$home['s']."&p3=".$home['p']."\" style='color:FFFFFF' >      \n\n";
    echo $user['oname'] . "</a> \n";
    echo "    </th> \n\n";

    echo "    <!--  message-icon --> \n";
    echo "    <th> \n";
    echo "      <a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\"> \n";
    echo "        <img src=\"".UserSkin()."img/m.gif\" border=\"0\" alt=\"Написать сообщение\" /> \n";
    echo "      </a> \n";
    echo "    &nbsp;\n";
    echo "    </th> \n\n";

    echo "    <!--  ally --> \n";
    echo "    <th> \n";
    echo "      <a href=\"index.php?page=allianzen&session=$session\"> \n";
    echo "              </a> \n";
    echo "    </th> \n\n";

    echo "    <!-- points --> \n";
    echo "    <th> \n";
    echo "      ".nicenum($user['score1'] / 1000)."    </th> \n\n";
    echo "  </tr> \n";
}
?>

<?php
PageFooter ();
ob_end_flush ();
?>