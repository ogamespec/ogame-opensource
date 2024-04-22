<?php

// Admin Area: expedition settings.

function Admin_Expedition ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    // Обработка POST-запроса.
    if ( method () === "POST" )
    {
    }

?>

<?=AdminPanel();?>

TODO: Планируется переработка параметров экспедиции.<br><br>

<?php

    $exptab = LoadExpeditionSettings ();
    print_r ( $exptab );

}
?>