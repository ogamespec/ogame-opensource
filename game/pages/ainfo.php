<?php

// Описание альянса.

// Попытаться получить сессию из реферера.
//echo $_SERVER['HTTP_REFERER'];

$now = time ();
$allyid = intval($_GET['allyid']);
$ally = LoadAlly ($allyid);

$members = CountAllyMembers ( $ally['ally_id'] );

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
<tr><td class=c colspan=2>Информация об альянсе</td></tr><?php
    if ($ally['imglogo'] !== "") 
    {
        echo "<tr><th colspan=2><img src=\"/game/img/preload.gif\" class=\"reloadimage\" title=\"pic.php?url=".$ally['imglogo']."\"></td></tr>\n";
    }
?><tr><th>Аббревиатура:</th><th><?=$ally['tag'];?><?php
    if ( $now < $ally['tag_until'] ) echo " (бывш. ".$ally['old_tag'].")";
?></th></tr>
<tr><th>Название:</th><th><?=$ally['name'];?><?php
    if ( $now < $ally['name_until'] ) echo " (бывш. ".$ally['old_name'].")";
?></th></tr>
<tr><th>Численность:</th><th><?=$members;?></th></tr>
<tr><th colspan=2 height=100><?=bb($ally['exttext']);?></th></tr>
<tr><th>Домашняя страница</th><th>
<a href="redir.php?url=<?=$ally['homepage'];?>" target="_blank"><?=$ally['homepage'];?></a></th></tr>
</table></center>
