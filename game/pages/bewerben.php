<?php

// Подача заявки в альянс.

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

PageHeader ("bewerben");

if ( ! $GlobalUser['validated'] ) Error ( "Эта функция возможна только после активации учетной записи игрока." );

$ally_id = intval($_GET['allyid']);
$ally = LoadAlly ($ally_id);

// Загрузить образец заявки.
$template = "";
if ( $_POST['weiter'] === "Образец" || $ally['insertapp'])
{
    $template = $ally['apptext'];
    if ($template === "") $template = "Управление альянса не предоставило образца";
}

// Отправить заявление
if ( $_POST['weiter'] === "Отправить" && $ally['open'] )
{
    $text = $_POST['text'];
    $text = addslashes ( $text );
    AddApplication ( $ally['ally_id'], $GlobalUser['player_id'], $text );

?>
<!-- CONTENT AREA -->
<div id='content'>
<center>
<h1>Регистрироваться</h1>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><th colspan=2>Ваше заявление сохранено. Вы получите ответ в случае принятия или отклонения.</th></tr>
<tr><th colspan=2><input type=submit value="OK"></th></tr>
</table></form></center><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->
<?php
    PageFooter ();
    ob_end_flush ();
    die();
}

if ( $ally['open'] )        // Подать заявление
{
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
<h1>Регистрироваться</h1>
<table width=519>
<form action="index.php?page=bewerben&session=<?=$session;?>&allyid=<?=$ally_id;?>" method=POST>
<tr><td class=c colspan=2>Заявка в альянс [<?=$ally['tag'];?>] написать</td></tr>
<tr><th>Сообщение (<span id="cntChars">0</span> / 6000 символов)</th><th><textarea name="text" cols=40 rows=10 onkeyup="javascript:cntchar(6000)"><?=$template;?></textarea></th></tr>
<tr><th>Маленькая помощь</th><th><input type=submit name="weiter" value="Образец"></th></tr>
<tr><th colspan=2><input type=submit name="weiter" value="Отправить"></th></tr>
</table></form></center><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
}
else            // Заявление подать невозможно, альянс закрыт
{
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
<h1>Регистрироваться</h1>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><td class=c>Подать заявку в альянс [<?=$ally['tag'];?>] невозможно</td></tr>
<tr><th>Этот альянс сейчас не принимает новых членов</th></th></tr>
<tr><th><input type=submit value="Назад"></th></tr></table></form></center><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
}

PageFooter ();
ob_end_flush ();
?>