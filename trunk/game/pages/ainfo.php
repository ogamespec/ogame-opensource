<?php

// Описание альянса.

// Попытаться получить сессию из реферера.
//echo $_SERVER['HTTP_REFERER'];

$allyid = $_GET['allyid'];

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
<center><table width=519><tr><td class=c colspan=2>Allianzinformationen</td></tr><tr><th colspan=2><img src="/game/img/preload.gif" class="reloadimage" title="pic.php?url=ALLY_LOGO"></td></tr><tr><th>Tag</th><th>ALLY_TAG</th></tr><tr><th>Name</th><th>ALLY_NAME</th></tr>
<tr><th>Mitglieder</th><th>2</th></tr>
<tr><th colspan=2 height=100>EXTERNAL_TEXT</th></tr>
<tr><th>Homepage</th><th>
<a href="redir.php?url=HOMEPAGE" target="_blank">HOMEPAGE</a></th></tr>
</table></center>
