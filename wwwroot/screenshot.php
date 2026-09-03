<?php

include ('loca_startpage.php');
include ('common.php');

include ('w3c.txt');
include ('header.tpl');

/**
 * @param string $pic
 * @return string
 */
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
<p class="bildUeberschrift"><?php echo htmlspecialchars(ScreenShotName($_GET['pic'] ?? ''), ENT_QUOTES);?></p> 
<a href="screenshots.php"><img src="<?php echo htmlspecialchars($_GET['path'] ?? '', ENT_QUOTES) . htmlspecialchars($_GET['pic'] ?? '', ENT_QUOTES) . "." . htmlspecialchars($_GET['type'] ?? '', ENT_QUOTES); ?>"></a> 
</body> 
</html> 