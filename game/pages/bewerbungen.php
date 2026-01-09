<?php

/** @var array $GlobalUser */
/** @var string $db_array */

// List of applications to join the alliance.

$maxchars = 2000;

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "ally", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
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
    if ( $_POST['aktion'] === loca("ALLY_APPA_ACCEPT") && $show > 0 )
    {
        $app = LoadApplication ($show);
        $ally_id = $ally['ally_id'];
        $player_id = $app['player_id'];
        $newcomer = LoadUser ($player_id);

        $result = EnumerateAlly ($ally_id);        // Send out messages to alliance members and the player about the acceptance.
        $rows = dbrows ($result);
        while ($rows--)
        {
            $user = dbarray ($result);
            loca_add ("ally", $user['lang']);
            SendMessage ( $user['player_id'], 
                va(loca_lang("ALLY_MSG_FROM", $user['lang']), $ally['tag']), 
                loca_lang("ALLY_MSG_COMMON", $user['lang']), 
                va(loca_lang("ALLY_MSG_APPLY_ALLY", $user['lang']), $newcomer['oname']), MTYP_ALLY);
        }
        loca_add ("ally", $newcomer['lang']);
        SendMessage ( $player_id, 
            va(loca_lang("ALLY_MSG_FROM", $newcomer['lang']), $ally['tag']), 
            va(loca_lang("ALLY_MSG_APPLY_YES", $newcomer['lang']), $ally['tag']), 
            va(loca_lang("ALLY_MSG_APPLY_PLAYER", $newcomer['lang']), $ally['tag']), MTYP_ALLY );

        $query = "UPDATE ".$db_prefix."users SET ally_id = $ally_id, allyrank = 1, joindate = $now WHERE player_id = $player_id";
        dbquery ($query);
        RemoveApplication ( $show );
        MyGoto ("bewerbungen");
    }

    if ( $_POST['aktion'] === loca("ALLY_APPA_REJECT") && $show > 0 )
    {
        $app = LoadApplication ($show);
        $player_id = $app['player_id'];
        $newcomer = LoadUser ($player_id);
        RemoveApplication ( $show );

        // Send a rejection message.
        loca_add ("ally", $newcomer['lang']);
        $reason = loca_lang("ALLY_MSG_APPLY_NO_REASON", $newcomer['lang']);
        if ( $_POST['text'] !== "" ) $reason = $_POST['text'];
        SendMessage ( $app['player_id'], 
            va(loca_lang("ALLY_MSG_FROM", $newcomer['lang']), $ally['tag']), 
            va(loca_lang("ALLY_MSG_APPLY_NO", $newcomer['lang']), $ally['tag']),
            $reason, MTYP_ALLY );
        MyGoto ("bewerbungen");
    }
}

$result = EnumApplications ( $ally['ally_id'] );
$apps = dbrows ( $result );

if ($apps > 0 )
{
    BeginContent ();
?>
<table width=519>
<tr><td class=c colspan=2><?=va(loca("ALLY_APPA_OVERVIEW"), $ally['tag']);?></td></tr>
<?php
    if ( $show > 0 )
    {
        $app = LoadApplication ($show);
        $user = LoadUser ($app['player_id']);
?>
<tr><th colspan=2><?=va(loca("ALLY_APPA_FROM"), $user['oname']);?></th></tr>
<form action="index.php?page=bewerbungen&session=<?=$session;?>&show=<?=$show;?>&sort=<?=$sort;?>" method=POST>
<tr><th colspan=2><?=str_replace("\n", "\n<br>", stripslashes($app['text']) );?></th></tr>
<tr><td class=c colspan=2><?=loca("ALLY_APPA_ACTION");?></td></tr>
<tr><th>&#160;</th><th><input type=submit name="aktion" value="<?=loca("ALLY_APPA_ACCEPT");?>"></th></tr>
<tr><th><?=va(loca("ALLY_APPA_REASON"), "<span id=\"cntChars\">0</span>", $maxchars);?></th><th><textarea name="text" cols=40 rows=10 onkeyup="javascript:cntchar(<?=$maxchars;?>)"></textarea></th></tr>
<tr><th>&#160;</th><th><input type=submit name="aktion" value="<?=loca("ALLY_APPA_REJECT");?>"></th></tr>
<tr><td>&#160;</td></tr>
</form>
<?php
    }
?>
<tr><th colspan=2><?=va(loca("ALLY_APPA_AVAILABLE"), $apps);?></th></tr>
<tr>
    <td class=c><center><a href="index.php?page=bewerbungen&session=<?=$session;?>&show=<?=$show;?>&sort=1"><?=loca("ALLY_APPA_USER");?></a></center></td>
    <td class=c><center><a href="index.php?page=bewerbungen&session=<?=$session;?>&show=<?=$show;?>&sort=0"><?=loca("ALLY_APPA_DATE");?></a></center></th></tr>
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
<?php
    EndContent ();
}
else
{
    BeginContent ();
?>
<table width=519><tr><td class=c colspan=2><?=va(loca("ALLY_APPA_OVERVIEW"), $ally['tag']);?></td></tr><tr><th colspan=2><?=loca("ALLY_APPA_NONE");?></th></tr></table><br><br><br><br>
<?php
    EndContent ();
}

PageFooter ();
ob_end_flush ();
?>