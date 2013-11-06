<?php

include ('loca_startpage.php');
include ('common.php');

include ('w3c.txt');
include ('header.tpl');

function ScreenShotName ($pic)
{
    switch ($pic)
    {
        case "overview": return loca("PICS_WALL1");
        case "buildings": return loca("PICS_WALL2");
        case "shipyard": return loca("PICS_WALL3");
        case "empire": return loca("PICS_WALL4");
    }
    return "";
}

?>
<link rel='stylesheet' type='text/css' href='css/styles.css' />
<link rel='stylesheet' type='text/css' href='css/about.css' />
<body> 
<p class="bildUeberschrift"><?php echo ScreenShotName($_GET['pic']);?></p> 
<a href="screenshots.php"><img src="<?php echo $_GET['path'].$_GET['pic'].".".$_GET['type']; ?>"></a> 
</body> 
</html> 