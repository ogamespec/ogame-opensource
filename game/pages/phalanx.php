<?php

/** @var array $GlobalUser */
/** @var array $GlobalUni */
/** @var string $db_prefix */
/** @var string $aktplanet */

$PhalanxCost = 5000;    // Amount of deuterium per phalanx scan

require_once "phalanx_events.php";

?>
<html>
 <head>
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel='stylesheet' type='text/css' href='css/formate.css' />
  <link rel="stylesheet" type="text/css" href="<?=UserSkin();?>formate.css" />

  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<link rel="stylesheet" type="text/css" href="css/combox.css">

<title><?=va(loca("PAGE_TITLE"), $GlobalUni['num'], loca("OGAME_LOC"));?></title>

  <script src="js/utilities.js" type="text/javascript"></script>
  <script language="JavaScript">
    var session = "<?=$session;?>";
  </script>
<script type="text/javascript" src="js/overLib/overlib.js"></script>
<!-- HEADER -->

</head>
<body style="scrollbars: auto;" onunload="" >
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<!-- END HEADER -->

<!-- LEFTMENU -->


<!-- END LEFTMENU -->

<!-- CONTENT AREA -->

<br>
<table width="519">
 <tr>
  <td class="c" colspan="4">
<?=loca("PHALANX_REPORT");?> <a href="javascript:showGalaxy(<?=$aktplanet['g'];?>,<?=$aktplanet['s'];?>,<?=$aktplanet['p'];?>)" >[<?=$aktplanet['g'];?>:<?=$aktplanet['s'];?>:<?=$aktplanet['p'];?>]</a> (<?=$GlobalUser['oname'];?>)  </td>

 </tr>
 <tr>
  <td colspan="4" class="c"><?=loca("PHALANX_EVENTS");?></td>
 </tr>

<?php

    $target = LoadPlanetById ( intval($_GET['spid']) );

    $outofrange = false;                    // Check the radius of the phalanx
    if ( $aktplanet['g'] != $target['g'] || $aktplanet[GID_B_PHALANX] <= 0 )  $outofrange = true;
    else {
        $range = GetPhalanxRadius($aktplanet[GID_B_PHALANX]);
        if ( abs($aktplanet['s'] - $target['s']) > $range) $outofrange = true;
    }

/*
    if ( $GlobalUser['vacation'] )            // The player is in vacation mode.
    {
        echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>";
    }
    else
*/

    if ( $aktplanet[GID_B_PHALANX] <= 0 )        // Attempting a phalanx scan from a planet or another moon without a phalanx
    {
        echo "<font color=#FF0000>".loca("PHALANX_ERR_MISSING")."</font>";
    }
    else if ( $aktplanet[GID_RC_DEUTERIUM] < $PhalanxCost )        // Not enough deuterium
    {
        echo "<font color=#FF0000>".loca("PHALANX_ERR_DEUT")."</font>";
    }
    else if (                                        // Cheating attempt

        // scan of your planets
        $target['owner_id'] == $GlobalUser['player_id']         ||
        // scan from foreign moon/planet
        $aktplanet['owner_id'] != $GlobalUser['player_id']      ||
        // scan NOT (of custom or planet or a destroyed planet)
        !( $target['type'] >= PTYP_CUSTOM || $target['type'] == PTYP_PLANET || $target['type'] == PTYP_DEST_PLANET )  ||
        // scan beyond the radius of the phalanx.
        $outofrange
    )        
    {
        // Issue an automatic ban without an VM for an hour.

        echo "<font color=#FF0000>".loca("PHALANX_ERR_CHEATER")."</font>";

        // TODO.
    }
    else
    {
        PhalanxEventList ($target['planet_id']);

        // Write off phalanx cost deuterium.
        $aktplanet[GID_RC_DEUTERIUM] -= $PhalanxCost;
        $query = "UPDATE ".$db_prefix."planets SET `".GID_RC_DEUTERIUM."` = '".$aktplanet[GID_RC_DEUTERIUM]."', lastpeek = '".$now."' WHERE planet_id = " . $aktplanet['planet_id'];
        dbquery ($query);
    }

?></table>

<!-- END CONTENT AREA -->

 </body>
</html>