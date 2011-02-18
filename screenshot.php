<?php

include ('loca_startpage.php');
include ('common.php');

include ('w3c.txt');
include ('header.tpl');

function ScreenShotName ($pic)
{
    switch ($pic)
    {
        case "overview": return "Обзор";
        case "buildings": return "Постройки";
        case "shipyard": return "Верфь";
        case "empire": return "Империя";
    }
    return "";
}

?>
<link rel='stylesheet' type='text/css' href='css/styles.css' />
<link rel='stylesheet' type='text/css' href='css/about.css' />
<body> 
<p class="bildUeberschrift"><?=ScreenShotName($_GET['pic']);?></p> 
<a href="screenshots.php"><img src="<?=$_GET['path'].$_GET['pic'].".".$_GET['type']?>"></a> 
</body> 
</html> 