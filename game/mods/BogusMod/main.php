<?php

const QTYP_ADD_TRITIUM = "AddTritium";

const BOGUS_MOD_TRITIUM_CREDIT_PERIOD_SECONDS = 60*60;

class BogusMod extends GameMod
{
    public function install() : void {
        global $db_prefix;

        LockTables();

        // Add a column for storing Tritium reserves
        $query = "ALTER TABLE ".$db_prefix."users ADD COLUMN tritium INT DEFAULT 0;";
        dbquery ($query);

        // Start Tritium credit event
        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_ADD_TRITIUM."'";
        $result = dbquery ($query);
        if ( dbrows ($result) == 0 ) {
            AddQueue (USER_SPACE, QTYP_ADD_TRITIUM, 0, 0, 0, time(), BOGUS_MOD_TRITIUM_CREDIT_PERIOD_SECONDS);
        }

        UnlockTables();

        Debug ("BogusMod install success.");
    }

    public function uninstall() : void {
        global $db_prefix;

        LockTables();

        // Remove Tritium column from users table
        $query = "ALTER TABLE ".$db_prefix."users DROP COLUMN tritium;";
        dbquery ($query);

        // Delete Tritium resource credit event
        $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_ADD_TRITIUM."'";
        dbquery ($query);

        UnlockTables();

        Debug ("BogusMod uninstall success.");
    }

    public function init() : void {
        global $GlobalUni;
        loca_add ("bogusmod", $GlobalUni['lang'], __DIR__);
    }

    public function route(array &$router) : bool {
        $router['tipoftheday'] = array (
            'path' => "mods/BogusMod/pages/tipoftheday.php",
            'loca' => [ "menu" ]
        );
        return false;
    }

    public function update_queue(array &$queue) : bool {
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

    public function add_resources(array &$json, array $aktplanet) : bool {

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

    public function add_menuitems(array &$json) : bool {

        array_insert_after_key ($json, "options", "tipoftheday", 
            array (
                'type' => 'internal',
                'page' => 'tipoftheday',
                'loca' => 'BOGUS_MOD_MENU_ITEM') );

        // Let other mods add their menu items
        return false;
    }

    public function install_tabs_included (array &$tabs) : bool {
        $tabs['users']['tritium'] = 'INT DEFAULT 0';
        return false;
    }
}

?>