<?php

// Админка - боевые доклады.

function Admin_BattleReport ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    if ( key_exists ( 'file', $_REQUEST) )
    {
        AdminPanel();

        $battleres = file_get_contents ( $_REQUEST['file'] );
        $res = unserialize($battleres);

        loca_add ( "techshortnames", "de" );
        loca_add ( "techshortnames", "en" );
        loca_add ( "techshortnames", "ru" );
        loca_add ( "technames", "de" );
        loca_add ( "technames", "en" );
        loca_add ( "technames", "ru" );

        $text = ShortBattleReport ( $res, filemtime ($_REQUEST['file']) );
        echo $text;
        return;
    }
?>

<?=AdminPanel();?>

<?php

    $results = glob ( "battleresult/*.txt" );

    echo "<table>";
    foreach ( $results as $i=>$filename ) {
        echo "<tr><td><a href=\"index.php?page=admin&session=".$session."&mode=BattleReport&file=$filename\">$filename</a></td></tr>\n";
    }
    echo "</table>";

?>

<?php
}
?>