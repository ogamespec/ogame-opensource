<?php

// Столб позора.

$internal = key_exists ( 'session', $_GET );

// Исправленная версия date
function MyDate ( $fmt, $timestamp )
{
    $date = new DateTime ('@' . $timestamp);
    return $date->format ($fmt);
}

if ($internal)
{
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

    PageHeader ("pranger");

    echo "<!-- CONTENT AREA -->\n";
    echo "<div id='content'>\n";
    echo "<center>\n";
}

$uni = LoadUniverse ();

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
   <h1>Позорный столб <?=$uni['num'];?></h1>
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

<?php
    $from = intval ( $_GET['from'] );
    $query = "SELECT * FROM ".$db_prefix."pranger ORDER BY ban_when DESC LIMIT $from, 50";
    $result = dbquery ($query);
    $total = $rows = dbrows ( $result );
    while ( $rows-- )
    {
        $entry = dbarray ( $result );
        echo "        <tr height=\"20\">\n";
        echo "     <th>".date("D M j Y G:i:s", $entry['ban_when'])." </th>\n\n";
        echo "          <th>\n";
        echo "       ".$entry['admin_name']."     </th>\n\n";
        echo "     <th>".$entry['user_name']."</th>\n";
        echo "     <th>".MyDate("D M j Y G:i:s", $entry['ban_until'])."</th>\n";
        echo "     <th>".$entry['reason']."</th>\n";
        echo "    </tr>\n";
    }

?>
       <tr>
   <th colspan="5">
<?php
    if ($internal) $pranger_url = "index.php?page=pranger&session=$session&from";
    else $pranger_url = "pranger.php?from";
    if ($from >= 50) echo "     <a href=\"".$pranger_url."=".($from-50)."\"><< Предыдущие 50</a>&nbsp;&nbsp;&nbsp;&nbsp;\n";
    if ($total >= 50) echo "        <a href=\"".$pranger_url."=".($from+50)."\">Следующие 50 >></a>\n";
?>
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