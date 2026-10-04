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
        case "battleship_1280x1024": return loca("PICS_WALLPAPERS");
        case "destroyer_1280x1024": return loca("PICS_WALLPAPERS");
    }
    return "";
}

/**
 * Map the requested picture to the file that is actually served. Only the
 * names linked from content_screenshots.tpl are known here, so the request
 * cannot select an arbitrary path or extension.
 *
 * @param string $pic
 * @return string
 */
function ScreenShotImage ($pic) : string
{
    $images = array (
        "overview" => "img/overview.JPG",
        "buildings" => "img/buildings.JPG",
        "shipyard" => "img/shipyard.JPG",
        "empire" => "img/empire.JPG",
        "battleship_1280x1024" => "img/wallpapers/battleship_1280x1024.jpg",
        "destroyer_1280x1024" => "img/wallpapers/destroyer_1280x1024.jpg",
    );
    return $images[$pic] ?? "";
}

$screenshot_pic = isset ($_GET['pic']) && is_string ($_GET['pic']) ? $_GET['pic'] : '';

?>
<link rel='stylesheet' type='text/css' href='css/styles.css' />
<link rel='stylesheet' type='text/css' href='css/about.css' />
<body> 
<p class="bildUeberschrift"><?php echo htmlspecialchars(ScreenShotName($screenshot_pic), ENT_QUOTES);?></p> 
<a href="screenshots.php"><img src="<?php echo htmlspecialchars(ScreenShotImage($screenshot_pic), ENT_QUOTES); ?>"></a> 
</body> 
</html> 