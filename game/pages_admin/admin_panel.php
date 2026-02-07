<?php

// Admin panel for a quick navigation.
function AdminPanel () : void
{
    global $session;
    global $admin_router;

?>

<table><tr><td>

<?php
    foreach ($admin_router as $mode=>$item) {

        $skip = false;
        if (key_exists('skip', $item)) {
            $skip = $item['skip'];
        }
        if ($skip) continue;

        $skin = false;
        if (key_exists('skin', $item)) {
            $skin = $item['skin'];
        }
        $img = $skin ? UserSkin() . $item['img'] : $item['img'];

        echo "<a href=\"index.php?page=admin&session=$session&mode=$mode\"><img src=\"$img\" width='32' height='32'";
        echo "onmouseover=\"return overlib('<center><font size=1 color=white><b>".loca($item['loca'])."</b></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'></a>\n\n";
    }
?>

</td></tr></table><br/>

<?php
}

?>