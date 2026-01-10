<?php

/** @var array $GlobalUser */
/** @var string $db_prefix */

// Planet Menu.

// Sneakily hidden in the Overview - you have to click on the name of the planet.

$RenameError = "";

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "renameplanet", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
if ($aktplanet == null) {
    Error ("Can't get aktplanet");
}
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

function PlanetDestroyMenu () : never
{
    global $GlobalUser;

    $aktplanet = GetPlanet ( $GlobalUser['aktplanet']);
    PageHeader ("renameplanet");

    BeginContent ();

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

    EndContent ();

    PageFooter ();
    ob_end_flush ();
    exit ();
}

// POST request processing.
if ( method() === "POST" )
{
    if ( $_POST['aktion'] === loca("REN_RENAME") )
    {
        RenamePlanet ( $GlobalUser['aktplanet'], $_POST['newname'] );
        $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
    }
    else if ( $_POST['aktion'] === loca("REN_ABANDON_COLONY") )
    {
        PlanetDestroyMenu ();
    }
    else if ( $_POST['aktion'] === loca("REN_DELETE_PLANET") )
    {
        // Check the password.
        if ( CheckPassword ( $GlobalUser['name'], $_POST['pw']) == 0 )
        {
            $RenameError = "<center>\n" . 
                        va (loca("REN_ERROR_PASSWORD"), "<A HREF=reg/mail.php>", "</A>", "<a\nhref=".hostname()." target='_top'>", "</a>") .
                        "<br></center>\n\n" ;
        }
        else
        {
            // Check if the planet belongs to this user.
            $planet = GetPlanet ( intval($_POST['deleteid']) );
            if ( $planet['owner_id'] == $GlobalUser['player_id'] )
            {
                // The home planet cannot be deleted.
                if ( intval($_POST['deleteid']) == $GlobalUser['hplanetid'] ) $RenameError = "<center>\n".loca("REN_ERROR_HOME_PLANET")."<br></center>\n";
                else
                {
                    $query = "SELECT * FROM ".$db_prefix."fleet WHERE target_planet = " . intval($_POST['deleteid']) . " AND owner_id = " . $GlobalUser['player_id'];
                    $result = dbquery ( $query );
                    if ( dbrows ($result) > 0 ) $RenameError = "<center>\n".loca("REN_ERROR_FLEET_INCOME")."<br></center>\n";

                    if ( $RenameError === "" )
                    {
                        $query = "SELECT * FROM ".$db_prefix."fleet WHERE start_planet = " . intval($_POST['deleteid']);
                        $result = dbquery ( $query );
                        if ( dbrows ($result) > 0 ) $RenameError = "<center>\n".loca("REN_ERROR_FLEET_OUTCOME")."<br></center>\n";
                    }

                    if ( $RenameError === "" )
                    {
                        $when = $now + 24*3600;
                        $moon_id = PlanetHasMoon ($planet['planet_id']);
                        if ( $moon_id )
                        {
                            $moon = GetPlanet ( $moon_id );        // Delete only not yet destroyed moons
                            if ( $moon['type'] == 0 )
                            {
                                $query = "UPDATE ".$db_prefix."planets SET type = ".PTYP_DEST_MOON.", owner_id = ".USER_SPACE.", date = $now, remove = $when, lastakt = $now WHERE planet_id = " . $moon_id . ";";
                                dbquery ( $query );

                                // Delete the queue on the moon
                                FlushQueue ($moon_id);

                                // Modify player stats (after moon deletion)
                                $pp = PlanetPrice ($moon);
                                AdjustStats ( $moon['owner_id'], $pp['points'], $pp['fpoints'], 0, '-' );
                                RecalcRanks ();
                            }
                        }
                        if ($planet['type'] == 0) $query = "UPDATE ".$db_prefix."planets SET type = ".PTYP_DEST_MOON.", owner_id = ".USER_SPACE.", date = $now, remove = $when, lastakt = $now WHERE planet_id = " . $planet['planet_id'] . ";";
                        else $query = "UPDATE ".$db_prefix."planets SET type = ".PTYP_DEST_PLANET.", owner_id = ".USER_SPACE.", date = $now, remove = $when, lastakt = $now WHERE planet_id = " . $planet['planet_id'] . ";";
                        dbquery ( $query );

                        // Delete the queue on the planet
                        FlushQueue ($planet['planet_id']);

                        // Modify player stats (after planet removal)
                        $pp = PlanetPrice ($planet);
                        AdjustStats ( $planet['owner_id'], $pp['points'], $pp['fpoints'], 0, '-' );
                        RecalcRanks ();

                        // Redirect to Home Planet.
                        SelectPlanet ($GlobalUser['player_id'], $GlobalUser['hplanetid']);
                        MyGoto ( "renameplanet" );
                    }
                }
            }
        }
    }
}

$name = $aktplanet['name'];
$maxlen = 20;

PageHeader ("renameplanet");

BeginContent ();

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

EndContent ();

PageFooter ("", $RenameError);
ob_end_flush ();
?>