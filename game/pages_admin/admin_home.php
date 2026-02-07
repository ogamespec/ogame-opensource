<?php

// Admin Home Page.

function Admin_Home () : void
{
    global $session;
    global $admin_router;

    $items_per_row = 5;
?>
    <br>
    <br>

    <table width=100% border="0" cellpadding="0" cellspacing="1" align="top" class="s">

<?php
    $item_counter = 0;
    foreach ($admin_router as $mode=>$item) {

        if ($item_counter >= $items_per_row) {
            echo "    </tr>\n";
            $item_counter = 0;
        }
        if ($item_counter == 0) echo "    <tr>\n";

        echo "    <th><a href=\"index.php?page=admin&session=$session&mode=$mode\">";

        $skin = false;
        if (key_exists('skin', $item)) {
            $skin = $item['skin'];
        }
        $img = $skin ? UserSkin() . $item['img'] : $item['img'];

        echo "<img src=\"$img\"><br>";
        echo loca($item['loca']) . "</a></th>\n";

        $item_counter++;
    }
    echo "    </tr>\n";
?>

    </table>
<?php
}
?>