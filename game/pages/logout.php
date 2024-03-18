<?php

// Выход

loca_add ( "reg", $GlobalUser['lang'] );

$aktplanet = GetPlanet ( $GlobalUser['aktplanet']);
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

Logout ( $_GET['session'] );
?>

<html>
 <head>
 <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
 <link rel="stylesheet" type="text/css" href="<?=hostname();?>evolution/formate.css" />
  <meta http-equiv="refresh" content="3;URL=index.php" />
  <title>Logout</title>

</head>

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' >
<center>
<?=loca("REG_LOGOUT");?><br />
<p>
             </p>
</center>
</body>
</html>