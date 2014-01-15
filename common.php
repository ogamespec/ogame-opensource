<?php

function mainmenu ($select)
{
    if ($select == 'home') echo "    <div class=\"menupoint\">" . loca("MENU_START") . "</div>\n";
    else echo "    <a href=\"home.php\">" . loca("MENU_START") . "</a>\n";
    if ($select == 'about') echo "    <div class=\"menupoint\">" . loca("MENU_ABOUT") . "</div>\n";
    else echo "    <a href=\"about.php\">" . loca("MENU_ABOUT") . "</a>\n";
    if ($select == 'preview') echo "    <div class=\"menupoint\">" . loca("MENU_PICTURES") . "</div>\n";
    else echo "    <a href=\"screenshots.php\">" . loca("MENU_PICTURES") . "</a>\n";
    if ($select == 'reg') echo '    <div class="menupoint">' . loca("MENU_REG") . "</div>\n";
    else echo "    <a href=\"register.php\">" . loca("MENU_REG") . "</a>\n";
    if ($select == 'board') echo '    <div class="menupoint">' . loca("MENU_BOARD") . "</div>\n";
    else echo "    <a href=\"" . loca("BOARDADDR") . "\" target=_top>" . loca("MENU_BOARD") . "</a>\n";
    if ($select == 'wiki') echo '    <div class="menupoint">' . loca("MENU_WIKI") . "</div>\n";
    else echo "    <a href=\"" . loca("WIKIADDR") . "\" target=_top>" . loca("MENU_WIKI") . "</a>\n";
}

