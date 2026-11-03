<?php

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
    exit ();
}

// All links from the game to the outside go through this script.
// Supposedly there could be filters for undesirable websites here.

$url = trim ((string) ($_REQUEST['url'] ?? ''));

// Only allow http/https links, so the redirect cannot be abused as an open
// proxy or javascript: vector.
if ( $url !== '' && !preg_match ('#^https?://#i', $url) ) {
    $url = '';
}
?>

<HTML> 
<HEAD> 
<META HTTP-EQUIV="refresh" content="0;URL=<?=htmlspecialchars($url, ENT_QUOTES);?>">
<TITLE>Page has moved</TITLE> 
</HEAD> 
<BODY> 
Page has moved 
</BODY> 
</HTML> 

