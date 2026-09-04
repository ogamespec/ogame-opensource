<?php

// $UniList is populated by uni.php (required below)
/** @var array $UniList */
$UniList = array ();

if ( !file_exists ("config.php")) {
    include ("install.php");
    die ();
}

include ('loca_startpage.php');
include ('common.php');

include ('w3c.txt');
include ('header.tpl');

?>
<link rel='stylesheet' type='text/css' href='css/styles.css' />
<link rel='stylesheet' type='text/css' href='css/about.css' />
<script src="js/functions.js" type="text/javascript"></script>
<script language="JavaScript" src="js/tw-sack.js"></script>
<script language="JavaScript" src="js/registration.js"></script>
<script language="JavaScript" >
<?php include ('common.js'); ?>
</script>
</head>
<body>

<a href="#pustekuchen" style="display:none;"><?php echo loca("LOGIN_LINK");?></a>

<div id="main">

<?php include ('products.php'); ?>

<?php include ('loginmenu.tpl'); ?>    

<div id="mainmenu">
<?php mainmenu ("reg"); ?>
</div>

<?php include ('content_register.tpl'); ?>

<script>
<?php

    require_once "uni.php";

    // The values below are echoed into JavaScript string literals, so they
    // must be encoded for the JS context (json_encode produces a safely
    // quoted string literal).
    if ( key_exists("linkuni", $_GET) && isset($UniList[$_GET['linkuni']]['uniurl']) ) echo "registerForm.universe.value = ".json_encode((string)$UniList[$_GET['linkuni']]['uniurl']).";\n";
    if ( key_exists("errorCode", $_GET) )
    {
        echo "document.registerForm.character.value = ".json_encode((string)($_GET['character'] ?? '')).";\n";
        echo "document.registerForm.email.value = ".json_encode((string)($_GET['email'] ?? '')).";\n";
        echo "document.registerForm.universe.value = ".json_encode((string)($_GET['universe'] ?? '')).";\n";
        if ( !$_GET['agb'] ) echo "showInfo (\"204\");\n";
        if ( $_GET['errorCode'] ) echo "printMessage (".json_encode((string)$_GET['errorCode']).");\n";
    }
    else echo "document.registerForm.character.focus();\n";
?>
</script>

</body>

</html>