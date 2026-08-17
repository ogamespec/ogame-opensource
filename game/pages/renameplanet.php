<?php

// Planet rename/destroy menu.

class Renameplanet extends Page {

    private bool $show_main_menu = true;

    public function controller () : bool {
        global $GlobalUser;
        global $aktplanet;
        global $db_prefix;
        global $now;
        global $PageError;

        if ( method() === "POST" ) {
            if ( $_POST['aktion'] === loca("REN_RENAME") ) {
                RenamePlanet ( $GlobalUser['aktplanet'], $_POST['newname'] );
                $aktplanet = LoadPlanetById ( $GlobalUser['aktplanet'] );
            }
            else if ( $_POST['aktion'] === loca("REN_ABANDON_COLONY") ) {
                $this->show_main_menu = $this->PlanetDestroyMenu ();
            }
            else if ( $_POST['aktion'] === loca("REN_DELETE_PLANET") ) {
                if ( CheckPassword ( $GlobalUser['name'], $_POST['pw']) == 0 ) {
                    $PageError = "<center>\n" .
                                va (loca("REN_ERROR_PASSWORD"), "<A HREF=reg/mail.php>", "</A>", "<a\nhref=".hostname()." target='_top'>", "</a>") .
                                "<br></center>\n\n" ;
                }
                else {
                    $planet = LoadPlanetById ( intval($_POST['deleteid']) );
                    if ( $planet['owner_id'] == $GlobalUser['player_id'] ) {
                        if ( intval($_POST['deleteid']) == $GlobalUser['hplanetid'] ) $PageError = "<center>\n".loca("REN_ERROR_HOME_PLANET")."<br></center>\n";
                        else {
                            $query = "SELECT * FROM ".$db_prefix."fleet WHERE target_planet = " . intval($_POST['deleteid']) . " AND owner_id = " . $GlobalUser['player_id'];
                            $result = dbquery ( $query );
                            if ( dbrows ($result) > 0 ) $PageError = "<center>\n".loca("REN_ERROR_FLEET_INCOME")."<br></center>\n";

                            if ( $PageError === "" ) {
                                $query = "SELECT * FROM ".$db_prefix."fleet WHERE start_planet = " . intval($_POST['deleteid']);
                                $result = dbquery ( $query );
                                if ( dbrows ($result) > 0 ) $PageError = "<center>\n".loca("REN_ERROR_FLEET_OUTCOME")."<br></center>\n";
                            }

                            if ( $PageError === "" ) {
                                $when = $now + 24*3600;
                                $moon_id = PlanetHasMoon ($planet['planet_id']);
                                if ( $moon_id ) {
                                    $moon = LoadPlanetById ( $moon_id );
                                    if ( $moon['type'] == 0 ) {
                                        $query = "UPDATE ".$db_prefix."planets SET type = ".PTYP_DEST_MOON.", owner_id = ".USER_SPACE.", date = $now, remove = $when, lastakt = $now WHERE planet_id = " . $moon_id . ";";
                                        dbquery ( $query );
                                        FlushQueue ($moon_id);
                                        $pp = PlanetPrice ($moon);
                                        AdjustStats ( $moon['owner_id'], $pp['points'], $pp['fpoints'], 0, '-' );
                                        RecalcRanks ();
                                    }
                                }
                                if ($planet['type'] == 0) $query = "UPDATE ".$db_prefix."planets SET type = ".PTYP_DEST_MOON.", owner_id = ".USER_SPACE.", date = $now, remove = $when, lastakt = $now WHERE planet_id = " . $planet['planet_id'] . ";";
                                else $query = "UPDATE ".$db_prefix."planets SET type = ".PTYP_DEST_PLANET.", owner_id = ".USER_SPACE.", date = $now, remove = $when, lastakt = $now WHERE planet_id = " . $planet['planet_id'] . ";";
                                dbquery ( $query );

                                FlushQueue ($planet['planet_id']);

                                $pp = PlanetPrice ($planet);
                                AdjustStats ( $planet['owner_id'], $pp['points'], $pp['fpoints'], 0, '-' );
                                RecalcRanks ();

                                SelectPlanet ($GlobalUser['player_id'], $GlobalUser['hplanetid']);
                                MyGoto ( "renameplanet" );
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    public function view () : void {
        global $GlobalUser;
        global $aktplanet;
        global $session;

        if (!$this->show_main_menu) return;

        $name = $aktplanet['name'];
        $maxlen = 20;

        echo "<h1>".loca("REN_TITLE")."</h1>\n";
        echo "<form action=\"index.php?page=renameplanet&session=".$_GET['session']."&pl=".$aktplanet['planet_id']."\" method=\"POST\">\n";
        echo "<input type='hidden' name='page' value='renameplanet'>\n";
        echo "<center>\n";
        echo "<table width=519>\n";
        echo "  <tr>\n    <td class=\"c\" colspan=\"3\">".loca("REN_PLANET_INFO")."</td>\n  </tr>\n";
        echo "  <tr>\n    <th>".loca("REN_COORD")."</th><th>".loca("REN_NAME")."</th><th>".loca("REN_ACTIONS")."</th>\n  </tr>\n";
        echo "  <tr>\n    <th>".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."</th>\n";
        echo "    <th>".$name."</th>\n";
        echo "    <th><input type=\"submit\" name=\"aktion\" value=\"".loca("REN_ABANDON_COLONY")."\" alt=\"".loca("REN_ABANDON_COLONY")."\"></th>\n  </tr>\n";
        echo "  <tr>\n    <th>".loca("REN_RENAME")."</th>\n";
        echo "  	<th><input type=\"text\" name=\"newname\" size=\"25\" maxlength=\"".$maxlen."\"><br/></th>\n";
        echo "  <th><input type=\"submit\" name=\"aktion\" value=\"".loca("REN_RENAME")."\"></th>\n</tr>\n";
        echo "</table>\n</form>\n";
        echo "</center>\n\n";
        echo "<br><br><br><br>\n";
    }

    private function PlanetDestroyMenu () : bool {
        global $GlobalUser;
        global $aktplanet;

        echo "<h1>".loca("REN_TITLE")."</h1>\n";
        echo "<form action=\"index.php?page=renameplanet&session=".$_GET['session']."&pl=".$aktplanet['planet_id']."\" method=\"POST\">\n";
        echo "<input type='hidden' name='page' value='renameplanet'>\n";
        echo "<center>\n\n";
        echo "<table width=\"519\">\n";
        echo "<tr><td class=\"c\" colspan=\"3\">".loca("REN_WARNING")."</td></tr>\n";
        echo "<tr><th colspan=\"3\">".va(loca("REN_DELETE_INFO"), "[".$aktplanet['g'].":".$aktplanet['s'].":".$aktplanet['p']."]")."</th></tr>\n";
        echo "<tr><input type=\"hidden\" name=\"deleteid\" value =\"".$aktplanet['planet_id']."\">\n";
        echo "<th>".loca("REN_PASSWORD")."</th><th><input type=\"password\" name=\"pw\"></th>\n";
        echo "<th><input type=\"submit\" name=\"aktion\" value=\"".loca("REN_DELETE_PLANET")."\" alt=\"".loca("REN_ABANDON_COLONY")."\"></th></tr>\n";
        echo "</table>\n</form>\n</center>\n\n";
        echo "<br><br><br><br>\n";
        return false;
    }
}
?>