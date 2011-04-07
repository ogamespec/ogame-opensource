<?php

// Список заявок на вступление в альянс.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("bewerbungen");
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
<table width=519>
<tr><td class=c colspan=2>Обзор регистрации в этом альянсе [123]</td></tr>
<tr><th colspan=2>Заявление от spieler2</th></tr>
<form action="index.php?page=bewerbungen&session=<?=$session;?>&show=95343&sort=1" method=POST>
<tr><th colspan=2>xxxxx</th></tr>
<tr><td class=c colspan=2>Реакция на это заявление</td></tr>
<tr><th>&#160;</th><th><input type=submit name="aktion" value="Принять"></th></tr>
<tr><th>Причина (по желанию) <span id="cntChars">0</span> / 2000 символов</th><th><textarea name="text" cols=40 rows=10 onkeyup="javascript:cntchar(2000)"></textarea></th></tr>
<tr><th>&#160;</th><th><input type=submit name="aktion" value="Отклонить"></th></tr>
<tr><td>&#160;</td></tr>
</form>
<tr><th colspan=2>В наличии 1 заявлений. Нажмите на имя желаемого игрока, чтобы просмотреть его сообщение</th></tr>
<tr>
    <td class=c><center><a href="index.php?page=bewerbungen&session=<?=$session;?>&show=95343&sort=1">Заявитель</a></center></td>
    <td class=c><center><a href="index.php?page=bewerbungen&session=<?=$session;?>&show=95343&sort=0">Дата заявления</a></center></th></tr>
<tr>
    <th><center><a href="index.php?page=bewerbungen&session=<?=$session;?>&show=95343&sort=1">spieler2</a></center></th>
    <th><center>2011-04-07 08:10:15</center></th></tr>
</table><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>