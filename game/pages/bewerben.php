<?php

// Applying for an alliance.

$maxchars = 6000;

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

PageHeader ("bewerben");

if ( ! $GlobalUser['validated'] ) Error ( loca("ALLY_APPU_NOT_ACTIVATED") );

$ally_id = intval($_GET['allyid']);
$ally = LoadAlly ($ally_id);

// Load a sample of the application form.
$template = "";
if ( key_exists('weiter', $_POST) && $_POST['weiter'] === loca("ALLY_APPU_TEMPLATE") || $ally['insertapp'])
{
    $template = $ally['apptext'];
    if ($template === "") $template = loca("ALLY_APPU_TEMPLATE_MISSING");
}

// Send an application
if ( key_exists('weiter', $_POST) && $_POST['weiter'] === loca("ALLY_APPU_SUBMIT") && $ally['open'] )
{
    $text = $_POST['text'];
    $text = addslashes ( $text );
    AddApplication ( $ally['ally_id'], $GlobalUser['player_id'], $text );

    BeginContent ();
?>
<h1><?=loca("ALLY_APPU_REG");?></h1>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><th colspan=2><?=loca("ALLY_APPU_SUBMITTED");?></th></tr>
<tr><th colspan=2><input type=submit value="<?=loca("ALLY_APPU_OK");?>"></th></tr>
</table></form></center><br><br><br><br>
<?php
    EndContent ();
    PageFooter ();
    ob_end_flush ();
    die();
}

if ( $ally['open'] )        // Submit an application
{
    BeginContent ();
?>
<h1><?=loca("ALLY_APPU_REG");?></h1>
<table width=519>
<form action="index.php?page=bewerben&session=<?=$session;?>&allyid=<?=$ally_id;?>" method=POST>
<tr><td class=c colspan=2><?=va(loca("ALLY_APPU_TITLE"), $ally['tag']);?></td></tr>
<tr><th><?=va(loca("ALLY_APPU_TEXT"), "<span id=\"cntChars\">0</span>", $maxchars);?></th><th><textarea name="text" cols=40 rows=10 onkeyup="javascript:cntchar(<?=$maxchars;?>)"><?=$template;?></textarea></th></tr>
<tr><th><?=loca("ALLY_APPU_HINT");?></th><th><input type=submit name="weiter" value="<?=loca("ALLY_APPU_TEMPLATE");?>"></th></tr>
<tr><th colspan=2><input type=submit name="weiter" value="<?=loca("ALLY_APPU_SUBMIT");?>"></th></tr>
</table></form></center><br><br><br><br>
<?php
    EndContent ();
}
else            // It's impossible to apply, the alliance is closed.
{
    BeginContent ();
?>
<h1><?=loca("ALLY_APPU_REG");?></h1>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><td class=c><?=va(loca("ALLY_APPU_FORBIDDEN"), $ally['tag']);?></td></tr>
<tr><th><?=loca("ALLY_APPU_CLOSED");?></th></th></tr>
<tr><th><input type=submit value="<?=loca("ALLY_APPU_BACK");?>"></th></tr></table></form></center><br><br><br><br>
<?php
    EndContent ();
}

PageFooter ();
ob_end_flush ();
?>