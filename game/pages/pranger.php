<?php

// Столб позора.

$limit = 50;    // Записей на одну страницу.

$uni = LoadUniverse();
$internal = key_exists ( 'session', $_GET );

// Исправленная версия date
function MyDate ( $fmt, $timestamp )
{
    $date = new DateTime ('@' . $timestamp);
    return $date->format ($fmt);
}

if ($internal)
{
    loca_add ( "menu", $GlobalUser['lang'] );
    loca_add ( "pranger", $GlobalUser['lang'] );

    if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
    $GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
    $now = time();
    UpdateQueue ( $now );
    $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
    ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
    UpdatePlanetActivity ( $aktplanet['planet_id'] );
    UpdateLastClick ( $GlobalUser['player_id'] );
    $session = $_GET['session'];

    PageHeader ("pranger");

    BeginContent ();
}
else {

    // Для внешнего обращения к Столбу Позора попробовать взять язык из кукисов. Если в кукисах нет - попробовать взять язык Вселенной.
    // Иначе использовать язык по умолчанию.

    if ( key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = $_COOKIE['ogamelang'];
    else $loca_lang = $uni['lang'];
    if ( !key_exists ( $loca_lang, $Languages ) ) $loca_lang = $DefaultLanguage;
    
    loca_add ( "pranger", $loca_lang );
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
   <h1><?=va(loca("PRANGER_TITLE"), $uni['num']);?></h1>
   <p><?=loca("PRANGER_INFO");?></p>

   <table border="0" cellpadding="2" cellspacing="1">
    <tr height="20">
     <td class="c"><?=loca("PRANGER_WHEN");?></td>
     <td class="c"><?=loca("PRANGER_OPER");?></td>
     <td class="c"><?=loca("PRANGER_USER");?></td>
     <td class="c"><?=loca("PRANGER_UNTIL");?></td>
     <td class="c"><?=loca("PRANGER_REASON");?></td>
    </tr>

<?php
    $from = key_exists('from', $_GET) ? intval ( $_GET['from'] ) : 0;
    $query = "SELECT * FROM ".$db_prefix."pranger ORDER BY ban_when DESC LIMIT $from, $limit";
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
    if ($from >= $limit) echo "     <a href=\"".$pranger_url."=".($from-$limit)."\"><< ".va(loca("PRANGER_PREV"), $limit)."</a>&nbsp;&nbsp;&nbsp;&nbsp;\n";
    if ($total >= $limit) echo "        <a href=\"".$pranger_url."=".($from+$limit)."\">".va(loca("PRANGER_NEXT"), $limit)." >></a>\n";
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
    EndContent ();

    PageFooter ("", "");
}

ob_end_flush ();
?>