<?php

const QTYP_ADD_TRITIUM = "AddTritium";

const BOGUS_MOD_TRITIUM_CREDIT_PERIOD_SECONDS = 60*60;

class BogusMod implements GameMod
{
    public function install() {
        global $db_prefix;

        // Add a column for storing Tritium reserves
        $query = "ALTER TABLE ".$db_prefix."users ADD COLUMN tritium INT DEFAULT 0;";
        dbquery ($query);

        // Start Tritium credit event
        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_ADD_TRITIUM."'";
        $result = dbquery ($query);
        if ( dbrows ($result) == 0 ) {
            AddQueue (USER_SPACE, QTYP_ADD_TRITIUM, 0, 0, 0, time(), BOGUS_MOD_TRITIUM_CREDIT_PERIOD_SECONDS);
        }

        Debug ("BogusMod install success.");
    }

    public function uninstall() {
        global $db_prefix;

        // Remove Tritium сolumn from users table
        $query = "ALTER TABLE ".$db_prefix."users DROP COLUMN tritium;";
        dbquery ($query);

        // Delete Tritium resource credit event
        $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_ADD_TRITIUM."'";
        dbquery ($query);

        Debug ("BogusMod uninstall success.");
    }

    public function init() {
        global $GlobalUni;
        loca_add ("bogusmod", $GlobalUni['lang'], __DIR__);
    }

    public function route() {
        if ( $_GET['page'] === "tipoftheday" ) {
            include __DIR__ . "/pages/tipoftheday.php";
            return true;
        }
        return false;
    }

    public function update_queue($queue) {
        global $db_prefix;
        if ($queue['type'] === QTYP_ADD_TRITIUM) {

            // Add Tritium and extend the queue task
            $query = "UPDATE ".$db_prefix."users SET tritium = tritium + 1;";
            dbquery ( $query );

            ProlongQueue ($queue['task_id'], BOGUS_MOD_TRITIUM_CREDIT_PERIOD_SECONDS);
            return true;
        }
        else {
            return false;
        }
    }

    public function add_resources(&$json, $aktplanet) {

        global $GlobalUser;

        array_insert_after_key ($json, "dm", "tritium", 
            array (
                'skin' => false,
                'img' => "mods/BogusMod/img/tritium.png",
                'loca' => "BOGUS_MOD_TRITIUM",
                'val' => $GlobalUser['tritium'],
                'color' => '') );

        // Let other mods add their resources
        return false;
    }

    public function add_menuitems(&$json) {

        array_insert_after_key ($json, "options", "tipoftheday", 
            array (
                'type' => 'internal',
                'page' => 'tipoftheday',
                'loca' => 'BOGUS_MOD_MENU_ITEM') );

        // Let other mods add their menu items
        return false;
    }

    public function lock_tables(&$tabs) {
        return false;
    }

    public function install_tabs_included (&$tabs) {
        $tabs['users']['tritium'] = 'INT DEFAULT 0';
        return false;
    }

    public function get_planet_small_image(&$planet, $img) {
        return false;
    }

    public function get_planet_image(&$planet, $img) {
        return false;
    }

    public function begin_content() {
        return false;
    }

    public function end_content() {
        return false;
    }
}

?>