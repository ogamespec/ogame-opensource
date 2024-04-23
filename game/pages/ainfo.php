<?php

// Alliance Info.

// Attempt to get a session from the referrer.
//echo $_SERVER['HTTP_REFERER'];

$uni = LoadUniverse();

if ( key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = $_COOKIE['ogamelang'];
else $loca_lang = $uni['lang'];
if ( !key_exists ( $loca_lang, $Languages ) ) $loca_lang = $DefaultLanguage;
loca_add ( "ainfo", $loca_lang );

$now = time ();
$allyid = intval($_GET['allyid']);
$ally = LoadAlly ($allyid);

if ($ally) {
    $members = CountAllyMembers ( $ally['ally_id'] );
}
else {
    $members = '-';
}

?>

<html> 
 <head> 
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel="stylesheet" type="text/css" href="<?=UserSkin();?>formate.css" />
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <link rel="stylesheet" type="text/css" href="css/combox.css">
  
  <script language="JavaScript">
function onBodyLoad() {
    window.setTimeout("reloadImages()", 100);
}

function reloadImages() {
    for (var i = 0; i < document.images.length; ++i) {
      if ((document.images[i].className == 'reloadimage') && (document.images[i].title != "")) {
        document.images[i].src = document.images[i].title;
      }
    }
}
</script>

<body onload="onBodyLoad();">
<center><table width=519>
<tr><td class=c colspan=2><?=loca("AINFO_INFO");?></td></tr><?php
    if ($ally && $ally['imglogo'] !== "") 
    {
        echo "<tr><th colspan=2><img src=\"/game/img/preload.gif\" class=\"reloadimage\" title=\"pic.php?url=".$ally['imglogo']."\"></td></tr>\n";
    }
?><tr><th><?=loca("AINFO_TAG");?></th><th><?=$ally['tag'];?><?php
    if ( $now < $ally['tag_until'] ) echo " (".loca("AINFO_PREV")." ".$ally['old_tag'].")";
?></th></tr>
<tr><th><?=loca("AINFO_NAME");?></th><th><?=$ally['name'];?><?php
    if ( $now < $ally['name_until'] ) echo " (".loca("AINFO_PREV")." ".$ally['old_name'].")";
?></th></tr>
<tr><th><?=loca("AINFO_MEMBERS");?></th><th><?=$members;?></th></tr>
<tr><th colspan=2 height=100><?=bb($ally['exttext']);?></th></tr>
<tr><th><?=loca("AINFO_HOMEPAGE");?></th><th>
<a href="redir.php?url=<?=$ally['homepage'];?>" target="_blank"><?=$ally['homepage'];?></a></th></tr>
</table></center>
