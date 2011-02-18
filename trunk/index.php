<?php
    require_once "loca_startpage.php";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="author" content="Gameforge Productions GmbH" />
<meta http-equiv="Content-Type" content="text/html; charset=<?=loca("META_CHARSET");?>" />
<meta name="keywords" content=<?=loca("META_KEYWORDS");?> />
<meta name="description" content=<?=loca("META_DESCRIPTION");?> />
<meta name="robots" content="index, follow" />
<meta name="language" content="ru" />
<meta name="distribution" content="global" />
<meta name="audience" content="all" />
<meta name="author-mail" content="info@ogame.de" />
<meta name="publisher" content="Gameforge Productions GmbH" />
<meta name="copyright" content="(c) 2007 by Gameforge Productions GmbH" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="pragma" content="no-cache" />

<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<title><?=$SERVERNAME?></title>
</head>

<frameset rows="*,0,0" frameborder="no" border="0" framespacing="0">
  <frame src="home.php" name="topFrame" scrolling="No" noresize="noresize" id="topFrame" title="topFrame" />
</frameset>
<noframes><body>
</body>
</noframes></html>

<script>
frame = document.getElementsByName['mainframe'][0];
if ( typeof( window.innerWidth ) == 'number' ){
    if (window.innerWidth <= 800){
        frame.scrollbars.visible=true;
    }
}else {
    if (document.body.clientWidth <= 800){
        frame.scrollbars.visible=true;
    }   
}
</script>
