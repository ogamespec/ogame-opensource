<?php

require_once "config.php";
require_once "db.php";
require_once "loca.php";
require_once "uni.php";

if ( !key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = "ru";
else $loca_lang = $_COOKIE['ogamelang'];

if ( !key_exists ( $loca_lang, $Languages ) ) $GlobalUni['lang'] = $loca_lang = 'en';
loca_add ( "maintain", $loca_lang );

// Format string, according to tokens from the text. Tokens are represented as #1, #2 and so on.
function va ($subject)
{
    $num_arg = func_num_args();
    $pattern = array ();
    for ($i=1; $i<$num_arg; $i++)
    {
        $pattern[$i-1] = "/#$i/";
        $replace[$i-1] = func_get_arg($i);
    }
    return preg_replace($pattern, $replace, $subject);
}

// Соединиться с базой данных
dbconnect ($db_host, $db_user, $db_pass, $db_name);
dbquery("SET NAMES 'utf8';");
dbquery("SET CHARACTER SET 'utf8';");
dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

$GlobalUni = LoadUniverse ();

if ( !$GlobalUni['freeze'] ) {
    echo "<html><head><meta http-equiv='refresh' content='0;url=$StartPage' /></head><body></body>";
    ob_end_flush ();
    die ();
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?=loca("MAINTAIN_TITLE");?></title>
    <style type="text/css" >
        <!--
html, body, div, span, applet, object, iframe,
h1, h2, h3, h4, h5, h6, p, blockquote, pre,
a, abbr, acronym, address, big, cite, code,
del, dfn, em, font, img, ins, kbd, q, s, samp,
small, strike, strong, sub, sup, tt, var,
dl, dt, dd, ol, ul, li,
fieldset, form, label, legend,
table, caption, tbody, tfoot, thead, tr, th, td {
	margin: 0;
	padding: 0;
	border: 0;
	outline: 0;
	font-weight: inherit;
	font-style: inherit;
	font-size: 100%;
	font-family: inherit;
	vertical-align: baseline;
}

body#maintenance {
  	background:#000000 url(img/maintenance-background.jpg) no-repeat;
	color: #848484;
  	font-size: 12px;
  	font-family: Verdana, Arial, SunSans-Regular, Sans-Serif;
	padding:0px 0 0;
	margin:0px 0 0;
}

#maintenance #infowrapper {
	position:absolute;
	width:315px;
	height:180px;
	left:349px;
	top:242px;
    padding-left: 20px;
    padding-right: 20px;
}

#maintenance #infowrapper h2 {
	font-weight:700;
	margin:0;
    padding-top: 3px;
	text-align: center;
	margin:0 0 40px;
}
a,
a:link,
a:visited,
a:active {
	color:#6F9FC8;;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
        -->
    </style>
</head>

<body id="maintenance">
    <div id="infowrapper">
        <h2><?=loca("MAINTAIN_HEAD");?></h2>
        <p><?=loca("MAINTAIN_INFO1");?></p>
        <p><?=loca("MAINTAIN_INFO2");?></p>
        <br/>
        <br/>
        <br/>
        <p><?=va(loca("MAINTAIN_BOARDLINK"), "http://board.oldogame.ru");?></p>
    </div>
</body>
</html>
