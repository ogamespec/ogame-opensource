<?php

include ('loca_startpage.php');
include ('common.php');

include ('w3c.txt');
include ('header.tpl');

?>
<link rel='stylesheet' type='text/css' href='css/styles.css' />
<link rel='stylesheet' type='text/css' href='css/about.css' />
<script language="JavaScript">
if (parent.frames.length == 0) {
    window.location = "/";
}
</script>
<script src="js/functions.js" type="text/javascript"></script>
<script language="JavaScript" src="js/tw-sack.js"></script>
<script language="JavaScript" src="js/registration.js"></script>
<script language="JavaScript" >
<?php include ('common.js'); ?>
</script>
</head>
<body>

<a href="#pustekuchen" style="display:none;"><?=loca("LOGIN_LINK");?></a>

<div id="main">

<?php include ('loginmenu.tpl'); ?>    

<div id="mainmenu">
<?php mainmenu ("home"); ?>
</div>

<?php include ('content_home.tpl'); ?>

<div align="right">
    <a href="http://code.google.com/p/ogame-opensource/" title="OGame Open Source on Google Code" target=_blank>
    <img src="http://code.google.com/images/googlecode_small.gif" width="88" height="35" alt="Powered by Google Code" border="0">
</a>

    <a href="http://mantis.oldogame.ru" title="Free Web Based Bug Tracker" target=_blank>
    <img src="http://mantis.oldogame.ru/images/mantis_logo_button.gif" width="88" height="35" alt="Powered by Mantis Bugtracker" border="0">
</a></div>

<script>
document.loginForm.universe.focus();
</script>

</body>

</html>