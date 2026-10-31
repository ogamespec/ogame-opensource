<?php

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
}
else {
    // allyid is an integer alliance id; anything else must not be echoed.
    $allyid = isset($_GET['allyid']) ? intval($_GET['allyid']) : 0;
    echo "<html><head><meta http-equiv='refresh' content='0;url=index.php?page=ainfo&allyid=".$allyid."' /></head><body></body></html>";
}
?>