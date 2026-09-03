<?php

// Only redirect to http(s) URLs: this script is referenced from in-game
// content ([img] bbcode, alliance logos), and an unrestricted Location header
// would be an open redirect usable for phishing.
$url = trim ((string) ($_REQUEST['url'] ?? ''));
if ( $url !== '' && preg_match ('#^https?://#i', $url) ) {
    header('Location: '.$url);
}
die ();

/*
 * Dead code: the old picture-serving implementation.
 * Kept for reference — the current behavior is a plain redirect (above).
 * Previously this script displayed pictures and scanned them for malware.

if ( !key_exists ('url', $_GET)) die ();

$extList = array();
$extList['gif'] = 'image/gif';
$extList['jpg'] = 'image/jpeg';
$extList['jpeg'] = 'image/jpeg';
$extList['png'] = 'image/png';

$imageInfo = pathinfo($_GET['url']);

if ( @getimagesize($_GET['url']) && $extList[ $imageInfo['extension']] )
{
    $contentType = 'Content-type: '.$extList[ $imageInfo['extension'] ];
    header ($contentType);
    readfile ($_GET['url']);
}
else
{
    header ('Content-type: text/html');
    echo "<font color=red><b>Графика недоступна</b></font>";
}
*/
