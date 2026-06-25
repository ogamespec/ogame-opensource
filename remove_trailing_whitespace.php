<?php

// Удаление trailing whitespace из файла
$filePath = 'game/pages/fleet_templates.php';
$content = file_get_contents($filePath);
$content = preg_replace('/[ \t]+$/m', '', $content);
file_put_contents($filePath, $content);

echo "Trailing whitespace удалено из $filePath\n";
