<?php

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
}
else {
    echo "<html><head><meta http-equiv='refresh' content='0;url=index.php?page=pranger' /></head><body></body></html>";
}
?>