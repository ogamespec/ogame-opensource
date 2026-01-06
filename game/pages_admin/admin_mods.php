<?php

// Admin Area: Modifications.

function GenModPanelSource($acitive, $can_be_installed, $mod)
{
    echo "        <div class=\"mod-item\">\n";
    echo "            <span class=\"status-indicator ". ($acitive ? "" : ($can_be_installed ? "status-inactive" : "status-installed") ) ." \">" . ($acitive ? loca("ADM_MODS_STATE_ACTIVE") : ($can_be_installed ? loca("ADM_MODS_STATE_AVAILABLE") : loca("ADM_MODS_STATE_INSTALLED")) ) . "</span>\n";
    echo "            <img src=\"".$mod['bg_url']."\" alt=\"".$mod['name']."\" class=\"mod-background\">\n";
    echo "            <div class=\"mod-content\">\n";
    echo "                <div class=\"mod-title\">".$mod['name']."</div>\n";
    echo "                <div class=\"mod-description\">".$mod['description']."</div>\n";
    if ($acitive || $can_be_installed) {
    echo "                <div class=\"mod-info\">\n";
    echo "                    ".loca("ADM_MODS_INFO_VERSION").": ".$mod['version']."<br>\n";
    echo "                    ".loca("ADM_MODS_INFO_AUTHOR").": ".$mod['author']."<br>\n";
    echo "                    ".loca("ADM_MODS_INFO_WEBSITE").": <a href=\"".$mod['website']."\" style=\"color:#E6EBFB;\">".$mod['website']."</a>\n";
    echo "                </div>\n";
    }
    echo "                <div class=\"mod-actions\">\n";
    if ($acitive) {
    echo "                    <a href=\"?action=move_up&mod=demo\" class=\"mod-action-link\">".loca("ADM_MODS_OP_MOVEUP")."</a>\n";
    echo "                    <a href=\"?action=move_down&mod=demo\" class=\"mod-action-link\">".loca("ADM_MODS_OP_MOVEDOWN")."</a>\n";
    echo "                    <a href=\"?action=remove&mod=demo\" class=\"mod-action-link\">".loca("ADM_MODS_OP_REMOVE")."</a>\n";
    }
    if ($can_be_installed) {
    echo "                    <a href=\"?action=install&mod=battle_calc\" class=\"mod-action-link\">".loca("ADM_MODS_OP_INSTALL")."</a>\n";
    }
    echo "                </div>\n";
    echo "            </div>\n";
    echo "        </div>\n\n";
}

function Admin_Mods ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    AdminPanel();

    $mods = ModsList();
    //print_r ($mods);
?>



<style>
    /* Additional styles for the mod control panel */
    .mods-container {
        display: flex;
        gap: 20px;
        margin: 20px 0;
    }
    
    .mod-column {
        flex: 1;
        background-color: #344566;
        border: 1px solid #415680;
        padding: 10px;
        border-radius: 3px;
    }
    
    .mod-column h2 {
        text-align: center;
        color: #E6EBFB;
        border-bottom: 1px solid #415680;
        padding-bottom: 10px;
        margin-top: 0;
    }
    
    .mod-item {
        background-color: #2a3850;
        border: 1px solid #415680;
        margin-bottom: 15px;
        border-radius: 3px;
        overflow: hidden;
        position: relative;
        min-height: 200px;
    }
    
    .mod-background {
        width: 100%;
        height: 200px;
        object-fit: cover;
        opacity: 0.7;
    }
    
    .mod-content {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        padding: 15px;
        color: #E6EBFB;
        background-color: rgba(4, 14, 30, 0.8);
    }
    
    .mod-title {
        font-size: 18px;
        font-weight: bold;
        color: lime;
        margin-bottom: 10px;
    }
    
    .mod-description {
        margin-bottom: 10px;
        line-height: 1.4;
    }
    
    .mod-info {
        font-size: 12px;
        color: #a0a0a0;
        line-height: 1.6;
    }
    
    .mod-actions {
        position: absolute;
        bottom: 10px;
        right: 10px;
        display: flex;
        gap: 10px;
    }
    
    .mod-action-link {
        background-color: #344566;
        border: 1px solid #415680;
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 12px;
        color: #E6EBFB !important;
        text-decoration: none !important;
    }
    
    .mod-action-link:hover {
        background-color: #415680;
        color: #CDD7F8 !important;
        text-decoration: none !important;
    }
    
    .empty-message {
        text-align: center;
        color: #999;
        font-style: italic;
        padding: 20px;
    }
    
    .status-indicator {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: #33FF33; /* Bright green */
        color: #000000;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        z-index: 10;
        border: 1px solid #00FF00;
        text-shadow: 0 1px 1px rgba(255, 255, 255, 0.3);
    }

    .status-inactive {
        background-color: #FFAA00; /* Bright orange */
        color: #000000;
        border: 1px solid #FF9900;
        text-shadow: 0 1px 1px rgba(255, 255, 255, 0.3);
    }

    .status-installed {
        background-color: darkgrey; /* Grey */
        color: #000000;
        border: 1px solid #FF9900;
        text-shadow: 0 1px 1px rgba(255, 255, 255, 0.3);
    }    
</style>


<h2><?=loca("ADM_MODS_HEAD");?></h2>

<div class="mods-container">
    <div class="mod-column">
        <h3><?=loca("ADM_MODS_HEAD_ACITVE");?></h3>
        
<?php
    if (count($mods['installed'])) {
        foreach ($mods['installed'] as $modname) {
            $mod = ModsGetInfo($modname);
            if ($mod) {
                GenModPanelSource (true, false, $mod);
            }
        }
    }
    else {
        echo "<div class=\"empty-message\">".loca("ADM_MODS_NO_ACTIVE")."</div>\n";
    }
?>

    </div>
    
    <div class="mod-column">
        <h3><?=loca("ADM_MODS_HEAD_AVAILABLE");?></h3>
        

<?php
    if (count($mods['available'])) {
        foreach ($mods['available'] as $modname) {
            $mod = ModsGetInfo($modname);
            if ($mod) {
                $can_be_installed = !in_array($modname, $mods['installed']);
                GenModPanelSource (false, $can_be_installed, $mod);
            }
        }
    }
    else {
        echo "<div class=\"empty-message\">".loca("ADM_MODS_NO_AVAILABLE")."</div>\n";
    }
?>

    </div>
</div>

<div style="text-align: center; margin-top: 20px; color: #E6EBFB;">
    <p><?=loca("ADM_MODS_TOT_ACTIVE");?>: <?=count($mods['installed']);?> | <?=loca("ADM_MODS_TOT_AVAILABLE");?>: <?=count($mods['available']);?></p>
</div>


<?php
}
?>