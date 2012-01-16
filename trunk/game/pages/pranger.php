<?php

// Столб позора.

$internal = key_exists ( 'session', $_GET );

if ($internal)
{
    SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
    if (CheckSession ( $_GET['session'] ) == FALSE) die ();

    loca_add ( "common", $GlobalUser['lang'] );
    loca_add ( "menu", $GlobalUser['lang'] );

    if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
    $GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
    $now = time();
    UpdateQueue ( $now );
    $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
    ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
    UpdatePlanetActivity ( $aktplanet['planet_id'] );
    UpdateLastClick ( $GlobalUser['player_id'] );
    $session = $_GET['session'];

    PageHeader ("pranger");

    echo "<!-- CONTENT AREA -->\n";
    echo "<div id='content'>\n";
    echo "<center>\n";
}
else
{
    loca_add ( "common", 'ru' );
    loca_add ( "menu", 'ru' );
}

// ************************************************************************************
?>

<html>
 <head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel='stylesheet' type='text/css' href='css/formate.css' />
  <link rel="stylesheet" type="text/css" href="<?=UserSkin();?>formate.css">


 </head>
   <body>
   <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
  <center>
   <h1>Позорный столб 20</h1>
   <p>Здесь написано кто, почему и на сколько заблокирован. 
<br />Блокировка со стороны Совета Админов и системы НЕ обсуждается. 
<br />Внимание! С автоматической темой сообщения Ваше сообщение будет обработано быстрее.</p>

   <table border="0" cellpadding="2" cellspacing="1">
    <tr height="20">
     <td class="c">Когда</td>
     <td class="c">Кто заблокировал</td>
     <td class="c">Имя игрока</td>
     <td class="c">Заблокирован до</td>
     <td class="c">Причина</td>
    </tr>
        <tr height="20">
     <th>Fri Jun 25 2010 19:51:40 </th>

          <th>
       Oceolot     </th>
     
     <th>Skywolf</th>
     <th>Sat Jun 26 2010 19:51:40</th>
     <th>Beleidigung</th>
    </tr>
       <tr>
   <th colspan="5">
        <a href="pranger.php?from=50">Следующие 50 >></a>
      </th>
   </tr>
   </table>
  </center>

 </body>
</html>

<?php

// ************************************************************************************

if ($internal)
{
    echo "<br><br><br><br>\n";
    echo "</center>\n";
    echo "</div>\n";
    echo "<!-- END CONTENT AREA -->\n";

    PageFooter ("", "");
}

ob_end_flush ();
?>