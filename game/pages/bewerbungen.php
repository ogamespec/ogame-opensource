<?php

// Список заявок на вступление в альянс.

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

PageHeader ("bewerbungen");

$ally = LoadAlly ( $GlobalUser['ally_id'] );

$show = 0;
if ( key_exists ( 'show', $_GET ) ) $show = intval($_GET['show']);
$sort = 1;
if ( key_exists ( 'sort', $_GET ) ) $sort = intval($_GET['sort']) & 1;

if ( method () === "POST" )
{
    if ( $_POST['aktion'] === "Принять" && $show > 0 )
    {
        $app = LoadApplication ($show);
        $ally_id = $ally['ally_id'];
        $player_id = $app['player_id'];
        $newcomer = LoadUser ($player_id);

        $result = EnumerateAlly ($ally_id);        // Разослать сообщения членам альянса и игроку о принятии.
        $rows = dbrows ($result);
        while ($rows--)
        {
            $user = dbarray ($result);
            SendMessage ( $user['player_id'], va("Альянс [#1]", $ally['tag']), "Общее сообщение", va("Игрок #1 был принят в наш альянс.", $newcomer['oname']), 0);
        }
        SendMessage ( $player_id, va("Альянс [#1]", $ally['tag']), va("Регистрация [#1] принята", $ally['tag']), va("Сердечно поздравляем, Вы теперь член альянса [#1]", $ally['tag']), 0 );

        $query = "UPDATE ".$db_prefix."users SET ally_id = $ally_id, allyrank = 1, joindate = $now WHERE player_id = $player_id";
        dbquery ($query);
        RemoveApplication ( $show );
    }

    if ( $_POST['aktion'] === "Отклонить" && $show > 0 )
    {
        $app = LoadApplication ($show);
        RemoveApplication ( $show );

        // Выслать сообщение об отказе.
        $reason = "-причина не указана-";
        if ( $_POST['text'] !== "" ) $reason = $_POST['text'];
        SendMessage ( $app['player_id'], va("Альянс [#1]", $ally['tag']), va("Регистрация [#1] отклонена", $ally['tag']), $reason, 0 );
    }
}

$result = EnumApplications ( $ally['ally_id'] );
$apps = dbrows ( $result );

if ($apps > 0 )
{
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
<table width=519>
<tr><td class=c colspan=2>Обзор регистрации в этом альянсе [<?=$ally['tag'];?>]</td></tr>
<?php
    if ( $show > 0 )
    {
        $app = LoadApplication ($show);
        $user = LoadUser ($app['player_id']);
?>
<tr><th colspan=2>Заявление от <?=$user['oname'];?></th></tr>
<form action="index.php?page=bewerbungen&session=<?=$session;?>&show=<?=$show;?>&sort=<?=$sort;?>" method=POST>
<tr><th colspan=2><?=str_replace("\n", "\n<br>", stripslashes($app['text']) );?></th></tr>
<tr><td class=c colspan=2>Реакция на это заявление</td></tr>
<tr><th>&#160;</th><th><input type=submit name="aktion" value="Принять"></th></tr>
<tr><th>Причина (по желанию) <span id="cntChars">0</span> / 2000 символов</th><th><textarea name="text" cols=40 rows=10 onkeyup="javascript:cntchar(2000)"></textarea></th></tr>
<tr><th>&#160;</th><th><input type=submit name="aktion" value="Отклонить"></th></tr>
<tr><td>&#160;</td></tr>
</form>
<?php
    }
?>
<tr><th colspan=2>В наличии <?=$apps;?> заявлений. Нажмите на имя желаемого игрока, чтобы просмотреть его сообщение</th></tr>
<tr>
    <td class=c><center><a href="index.php?page=bewerbungen&session=<?=$session;?>&show=<?=$show;?>&sort=1">Заявитель</a></center></td>
    <td class=c><center><a href="index.php?page=bewerbungen&session=<?=$session;?>&show=<?=$show;?>&sort=0">Дата заявления</a></center></th></tr>
<tr>
<?php
    while ($apps--)
    {
        $app = dbarray ($result);
        $user = LoadUser ($app['player_id']);
        echo "    <th><center><a href=\"index.php?page=bewerbungen&session=$session&show=".$app['app_id']."&sort=$sort\">".$user['oname']."</a></center></th>\n";
        echo "    <th><center>".date ("Y-m-d H:i:s", $app['date'])."</center></th></tr>\n";
    }
?>
</table><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
}
else
{
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
<table width=519><tr><td class=c colspan=2>Обзор регистрации в этом альянсе [<?=$ally['tag'];?>]</td></tr><tr><th colspan=2>Больше заявлений нет</th></tr></table><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
}

PageFooter ();
ob_end_flush ();
?>