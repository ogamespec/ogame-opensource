<?php

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

// All links from the game to the outside go through this script.
// Supposedly there could be filters for undesirable websites here.

$url = $_REQUEST['url'];

?>

<HTML> 
<HEAD> 
<META HTTP-EQUIV="refresh" content="0;URL=<?=$url;?>">
<TITLE>Page has moved</TITLE> 
</HEAD> 
<BODY> 
Page has moved 
</BODY> 
</HTML> 

