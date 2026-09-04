<?php

// $DefaultLanguage and $StartPage come from the (runtime-generated) config.php
/** @var string $DefaultLanguage */
/** @var string $StartPage */

// Check if the configuration file is missing - redirect to the game installation page.
if ( !file_exists ("../config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=../install.php' /></head><body></body></html>";
    exit ();
}
else {
    require_once "../config.php";
}

require_once "../core/core.php";

if ( !key_exists ( 'ogamelang', $_COOKIE ) ) $loca_lang = $DefaultLanguage;
else $loca_lang = $_COOKIE['ogamelang'];

loca_add ( "reg", $loca_lang, "../" );

?>

<html>
 <head>
 <center>
 <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
  <link rel='stylesheet' type='text/css' href='../css/default.css' />
  <link rel='stylesheet' type='text/css' href='../css/formate.css' />
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel='stylesheet' type='text/css' href='css/formate.css' />
 <link rel="stylesheet" type="text/css" href="<?=hostname();?>evolution/formate.css" />
 <title><?=loca("REG_ERROR");?></title>
</head>
<body class='style' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' >
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>

 <br><br>
 <table width="519">
 <tr>
   <td class="c" align="center" ><font color="red"><?=loca("REG_ERROR");?></font></td>
  </tr>
  <tr>
  <th class="errormessage"><?=va(loca("REG_ERROR_21"), htmlspecialchars((string)($_GET['arg1'] ?? ''), ENT_QUOTES), htmlspecialchars((string)($_GET['arg2'] ?? ''), ENT_QUOTES));?></th>
  </tr>
  <tr>
<?php

    switch ( $_GET['errorcode'] ?? '' )
    {
        case '2':
?>
   <th class='errormessage'><?=loca("REG_ERROR_22");?><br><?=va(loca("REG_ERROR_23"), htmlspecialchars($StartPage, ENT_QUOTES));?><br><?=loca("REG_ERROR_24");?></th>
<?php
        break;

        case '3':
?>
   <th class='errormessage'><?=va(loca("REG_ERROR_31"), htmlspecialchars((string)($_GET['arg3'] ?? ''), ENT_QUOTES));?></th>
<?php
        break;
    }

?>
  </tr>
 </table>
 </center>
</body>
</html>