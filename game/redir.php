<?php

// Проверить, если файл конфигурации отсутствует - редирект на страницу установки игры.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

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

