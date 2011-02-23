<?php

// Главный модуль, через который осуществляется доступ к другим страницам.
ob_start ();

// Проверить, если файл конфигурации отсутствует - редирект на страницу установки игры.
if ( !file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=install.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

header('Pragma:no-cache');

?>

Woooot!!!