<?php

// Integrated Galaxytool.

const GALATOOL_BASE_DIR_NAME = "temp";

const QTYP_GALAXY_TOOL = "GalaxyTool";
const GALAXY_TOOL_PERIOD_DAYS = 7;
const GALAXY_TOOL_PERIOD_SECONDS = GALAXY_TOOL_PERIOD_DAYS * 24 * 60 * 60;

class GalaxyTool extends GameMod {

    public function install() : void {
        global $db_prefix;

        LockTables();

        // Add a column to the Universe settings where the update period will be stored
        $query = "ALTER TABLE ".$db_prefix."uni ADD COLUMN galaxytool_update INT DEFAULT ".GALAXY_TOOL_PERIOD_DAYS.";";
        dbquery ($query);

        // Start GalaxyTool update event
        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_GALAXY_TOOL."'";
        $result = dbquery ($query);
        if ( dbrows ($result) == 0 ) {
            AddQueue (USER_SPACE, QTYP_GALAXY_TOOL, 0, 0, 0, time(), GALAXY_TOOL_PERIOD_SECONDS);
        }

        UnlockTables();
    }

    public function uninstall() : void {
        global $db_prefix;

        LockTables();

        // Remove update settings column from Universe table
        $query = "ALTER TABLE ".$db_prefix."uni DROP COLUMN galaxytool_update;";
        dbquery ($query);

        // Delete GalaxyTool update event
        $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_GALAXY_TOOL."'";
        dbquery ($query);

        UnlockTables();
    }

    public function init() : void {

        global $GlobalUser;
        loca_add ("galaxytool", $GlobalUser['lang'], __DIR__);
    }

    public function install_tabs_included (array &$tabs) : bool {
        $tabs['uni']['galaxytool_update'] = 'INT DEFAULT '.GALAXY_TOOL_PERIOD_DAYS;
        return false;
    }

    public function route(array &$router) : bool {
        $router['galaxytool'] = array (
            'path' => "mods/GalaxyTool/pages/galaxytool.php",
            'loca' => [],
            'bare' => true
        );
        return false;
    }

    public function update_queue(array &$queue) : bool {
        global $db_prefix;
        if ($queue['type'] === QTYP_GALAXY_TOOL) {

            $this->GalaxyToolUpdate ();

            ProlongQueue ($queue['task_id'], GALAXY_TOOL_PERIOD_SECONDS);
            return true;
        }
        else {
            return false;
        }
    }

    public function add_menuitems(array &$json) : bool {

        array_insert_after_key ($json, "options", "galaxytool", 
            array (
                'type' => 'popup',
                'page' => 'galaxytool',
                'loca' => 'GALATOOL_TITLE',
                'title' => 'GalaxyTool'
            ) );

        // Let other mods add their menu items
        return false;
    }

    // Updating the embedded galaxytool.
    // It is updated every week (by default)

    private function GalaxyToolUpdateGalaxy () : void
    {
        global $db_prefix;

        $list = array ();
        $query = "SELECT * FROM ".$db_prefix."planets WHERE type < ".PTYP_FARSPACE." AND type <> ".PTYP_COLONY_PHANTOM." ORDER BY planet_id ASC";
        $result = dbquery ( $query );
        $rows = dbrows ( $result );
        while ($rows--)
        {
            $planet = dbarray ( $result );
            if ( $planet['type'] == PTYP_DF && (($planet[GID_RC_METAL] + $planet[GID_RC_CRYSTAL]) < GALAXY_PHANTOM_DEBRIS) ) continue;
            $list[ $planet['planet_id'] ] = array ();
            $list[ $planet['planet_id'] ]['g'] = $planet['g'];
            $list[ $planet['planet_id'] ]['s'] = $planet['s'];
            $list[ $planet['planet_id'] ]['p'] = $planet['p'];
            $list[ $planet['planet_id'] ]['type'] = $planet['type'];
            $list[ $planet['planet_id'] ]['name'] = $planet['name'];
            $list[ $planet['planet_id'] ]['owner_id'] = $planet['owner_id'];
        }

        $text = serialize ( $list );
        $f = fopen ( GALATOOL_BASE_DIR_NAME . "/galaxy.txt", "w" );
        fwrite ( $f, $text );
        fclose ( $f );
    }

    private function GalaxyToolUpdateStats () : void
    {
        global $db_prefix;

        $week = time() - 604800;
        $week3 = time() - 604800*3;

        $list = array ();
        $query = "SELECT * FROM ".$db_prefix."users WHERE place1 < 1000 AND admin = 0 ORDER BY player_id ASC";    // only players from the top 1000 and not admins
        $result = dbquery ( $query );
        $rows = dbrows ( $result );
        while ($rows--)
        {
            $user = dbarray ( $result );
            $list[ $user['player_id'] ] = array ();
            $list[ $user['player_id'] ]['name'] = $user['oname'];
            $list[ $user['player_id'] ]['i'] = $user['lastclick'] <= $week ? 1 : 0;
            $list[ $user['player_id'] ]['b'] = $user['banned'] ? 1 : 0;
            $list[ $user['player_id'] ]['iI'] = $user['lastclick'] <= $week3 ? 1 : 0;
            $list[ $user['player_id'] ]['v'] = $user['vacation'] ? 1 : 0;
            $list[ $user['player_id'] ]['points'] = $user['score1'];
            $list[ $user['player_id'] ]['fpoints'] = $user['score2'];
            $list[ $user['player_id'] ]['rpoints'] = $user['score3'];
            $list[ $user['player_id'] ]['ally_id'] = $user['ally_id'];
        }

        $text = serialize ( $list );
        $f = fopen ( GALATOOL_BASE_DIR_NAME . "/statistics.txt", "w" );
        fwrite ( $f, $text );
        fclose ( $f );
    }

    private function GalaxyToolUpdateAllyStats () : void
    {
        global $db_prefix;

        $list = array ();
        $query = "SELECT * FROM ".$db_prefix."ally ORDER BY ally_id ASC";
        $result = dbquery ( $query );
        $rows = dbrows ( $result );
        while ($rows--)
        {
            $ally = dbarray ( $result );
            $list[ $ally['ally_id'] ] = array ();
            $list[ $ally['ally_id'] ]['name'] = $ally['tag'];
        }

        $text = serialize ( $list );
        $f = fopen ( GALATOOL_BASE_DIR_NAME . "/ally_statistics.txt", "w" );
        fwrite ( $f, $text );
        fclose ( $f );
    }

    private function GalaxyToolReplaceOldStats () : void
    {
        if ( file_exists(GALATOOL_BASE_DIR_NAME . '/statistics.txt') ) $current = file_get_contents( GALATOOL_BASE_DIR_NAME . '/statistics.txt' );
        else $current = array ();
        file_put_contents( GALATOOL_BASE_DIR_NAME . '/statistics_old.txt' , $current);
    }

    private function GalaxyToolReplaceOldAllyStats () : void
    {
        if ( file_exists(GALATOOL_BASE_DIR_NAME . '/ally_statistics.txt')) $current = file_get_contents( GALATOOL_BASE_DIR_NAME . '/ally_statistics.txt' );
        else $current = array ();
        file_put_contents( GALATOOL_BASE_DIR_NAME . '/ally_statistics_old.txt' , $current);
    }

    private function GalaxyToolUpdate () : void
    {
        $this->GalaxyToolUpdateGalaxy ();
        $this->GalaxyToolReplaceOldStats ();
        $this->GalaxyToolReplaceOldAllyStats ();
        $this->GalaxyToolUpdateStats ();
        $this->GalaxyToolUpdateAllyStats ();
    }

    public function route_admin(array &$router) : bool {
        $item = [];
        $item['path'] = "mods/GalaxyTool/pages_admin/admin_galaxytool.php";
        $item['img'] = "mods/GalaxyTool/img/admin_galaxytool.png";
        $item['loca'] = "GALATOOL_TITLE";
        $router['GalaxyTool'] = $item;
        return false;
    }
}

?>