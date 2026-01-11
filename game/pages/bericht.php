<?php

/** @var array $GlobalUser */

$msg = LoadMessage ( intval($_GET['bericht']) );

?>

<html>
<HEAD>
<LINK rel="stylesheet" type="text/css" href="<?=UserSkin();?>formate.css">
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <TITLE><?=loca($msg['pm'] == MTYP_SPY_REPORT ? "SPY_REPORT" : "BATTLE_REPORT");?></TITLE>
  <script src="js/utilities.js" type="text/javascript"></script>
  <script type="text/javascript" src="js/overLib/overlib.js"></script>
  <script language="JavaScript">var session="<?=$session;?>";</script>

</HEAD>
<BODY>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<table width="99%">
   <tr>
    <td>

<?php
    $allowed = false;
    if ($msg['owner_id'] == $GlobalUser['player_id']) {
        $allowed = true;
    }
    else {
        // From the same alliance as the spy report.
        $msg_user = LoadUser ($msg['owner_id']);
        $allowed = $msg_user['ally_id'] == $GlobalUser['ally_id'] && $GlobalUser['ally_id'] != 0 && $msg['pm'] == MTYP_SPY_REPORT;
    }
    
    if ( $allowed ) echo $msg['text'];
?>
    
</td>

   </tr>
</table>
</BODY>
</html>