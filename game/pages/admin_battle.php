<?php

// Админка - боевые доклады.
// Боевые доклады хранятся 2 недели.

function Admin_BattleReport ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;
    global $GlobalUni;

?>

<?=AdminPanel();?>

<?php

    // Показать боевой доклад
    if ( key_exists ( 'bericht', $_GET ) ) {
        $query = "SELECT * FROM ".$db_prefix."battledata WHERE battle_id = " . intval ($_GET['bericht']);
        $result = dbquery ( $query );
        $row = dbarray ($result);
        ob_clean ();
        loca_add ( "battlereport", $GlobalUni['lang'] );
?>
<html>
<HEAD>
<LINK rel="stylesheet" type="text/css" href="<?=UserSkin();?>formate.css">
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <TITLE><?=loca("BATTLE_REPORT");?></TITLE>
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
        echo $row['report'];
?>
</td>
   </tr>
</table>
</BODY>
</html>
<?php
        ob_end_flush ();
        die ();
    }

    // Вывести список всех докладов
    $query = "SELECT * FROM ".$db_prefix."battledata ORDER BY date DESC";
    $result = dbquery ( $query );

    echo "<table>";
    while ( $row = dbarray ($result) ) {
        echo "<tr><td>".date("Y.m.d H:i:s", $row['date'])."</td><td>".str_replace ( "{PUBLIC_SESSION}" , $session, $row['title'])."</td></tr>";
    }
    echo "</table>";

?>

<?php
}
?>