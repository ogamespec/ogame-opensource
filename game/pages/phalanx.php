<?php

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

$unitab = LoadUniverse ();
$uni = $unitab['num'];

?>
<html>
 <head>
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel='stylesheet' type='text/css' href='css/formate.css' />
  <link rel="stylesheet" type="text/css" href="<?=UserSkin();?>formate.css" />

  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<link rel="stylesheet" type="text/css" href="css/combox.css">

<title>Вселенная <?=$uni;?> ОГейм</title>

  <script src="js/utilities.js" type="text/javascript"></script>
  <script language="JavaScript">
    var session = "<?=$session;?>";
  </script>
<script type="text/javascript" src="js/overLib/overlib.js"></script>
<!-- HEADER -->

</head>
<body style="scrollbars: auto;" onunload="" >
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<!-- END HEADER -->

<!-- LEFTMENU -->


<!-- END LEFTMENU -->

<!-- CONTENT AREA -->

<br>
<table width="519">
 <tr>
  <td class="c" colspan="4">
Доклад сенсора с луны на координатах <a href="javascript:showGalaxy(<?=$aktplanet['g'];?>,<?=$aktplanet['s'];?>,<?=$aktplanet['p'];?>)" >[<?=$aktplanet['g'];?>:<?=$aktplanet['s'];?>:<?=$aktplanet['p'];?>]</a> (<?=$GlobalUser['oname'];?>)  </td>

 </tr>
 <tr>
  <td colspan="4" class="c">Передвижения флота</td>
 </tr>

<?php

    $target = GetPlanet ( $_GET['spid'] );

    $outofrange = false;
    if ( $aktplanet['g'] != $target['g'] )  $outofrange = true;
    else {
        $range = $aktplanet["b42"] * $aktplanet["b42"] - 1;
        if ( abs($aktplanet['s'] - $target['s']) > $range) $outofrange = true;
    }

    if ( $GlobalUser['vacation'] )            // Игрок в режиме отпуска.
    {
        echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>";
    }
    else if ( $aktplanet["b42"] <= 0 )        // Попытка скана фаланги с планеты или с другой луны без фаланги
    {
        echo "<font color=#FF0000>Не мухлевать!</font>";
    }
    else if ( $aktplanet["d"] < 5000 )        // Недостаточно дейтерия
    {
        echo "<font color=#FF0000>Недостаточно дейтерия!</font>";
    }
    else if (                                        // Попытка читерства
        $target['owner_id'] == $GlobalUser['player_id']         ||          // скан своих планет
        $aktplanet['owner_id'] != $GlobalUser['player_id']      ||         // скан с чужой луны/планеты
        !( ( $target['type'] > 0 && $target['type'] < 10000 ) || $target['type'] == 10001 )   ||           // скан НЕ (планеты и уничтоженной планеты)
        $outofrange                                                                        // скан за пределом радиуса фаланги.
    )        
    {
        // Выписать автоматический бан без РО на час.

        echo "<font color=#FF0000>Поздравляем! Вы выиграли целый час отдыха без РО за попытку манипулирования фалангой!</font>";
    }
    else
    {
?>

<tr class=''><th><div id='bxx1' title='18'star='1233351233'></div></th>
<th colspan='3'><span class='phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib("&lt;font color=white&gt;&lt;b&gt;Численность кораблей: 145 &lt;br&gt;Бомбардировщик 45&lt;br&gt;Линейный крейсер 100&lt;br&gt;&lt;/b&gt;&lt;/font&gt;");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='Численность кораблей: 145 Бомбардировщик 45Линейный крейсер 100'></a> игрока Andorianin <a href='#' onclick='showMessageMenu(167658)'><img src='<?=UserSkin();?>img/m.gif' title='Написать сообщение' alt='Написать сообщение'></a> с  Frigid Moon (Луна) <a href="javascript:showGalaxy(1,260,4)" attack>[1:260:4]</a> отправлен на планету Andromeda <a href="javascript:showGalaxy(1,263,9)" attack>[1:263:9]</a>. Задание: Атаковать</span>

<br /><br /><span class='phalanx_fleet'>Боевой <a href='#' onmouseover='return overlib("&lt;font color=white&gt;&lt;b&gt;Численность кораблей: 1 &lt;br&gt;Шпионский зонд 1&lt;br&gt;&lt;/b&gt;&lt;/font&gt;");' onmouseout='return nd();' class='phalanx_fleet'>флот</a><a href='#' title='Численность кораблей: 1 Шпионский зонд 1'></a> игрока Andorianin <a href='#' onclick='showMessageMenu(167658)'><img src='<?=UserSkin();?>img/m.gif' title='Написать сообщение' alt='Написать сообщение'></a> с  Frigid Moon (Луна) <a href="javascript:showGalaxy(1,260,4)" federation>[1:260:4]</a> отправлен на планету Andromeda <a href="javascript:showGalaxy(1,263,9)" federation>[1:263:9]</a>. Задание: Совместная атака</span>
<br /><br /></th>
</tr>
<script language=javascript>anz=1;t();</script>

<?php

        // Списать 5000 дейтерия.
        $aktplanet['d'] -= 5000;
        $query = "UPDATE ".$db_prefix."planets SET d = '".$aktplanet['d']."', lastpeek = '".$now."' WHERE planet_id = " . $aktplanet['planet_id'];
        dbquery ($query);
    }

?></table>

<!-- END CONTENT AREA -->

 </body>
</html>
<?php
ob_end_flush ();
?>