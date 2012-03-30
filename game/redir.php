<?php

// Все ссылки из игры наружу проходят через это скрипт.
// По идее тут могут быть фильтры нежелательных сайтов.

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

