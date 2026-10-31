<?php

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
}
else {
    $from = intval ( $_GET['from'] ?? 0 );
    echo "<html><head><meta http-equiv='refresh' content='0;url=index.php?page=pranger&from=".$from."' /></head><body></body></html>";
}
?>