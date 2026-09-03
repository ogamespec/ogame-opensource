<?php

/**
 * Read-only galaxy view for the Wanderer (Rogue Trader) mode.
 *
 * The classic galaxy page is built around a planet (deuterium fee charged to
 * the planet, fleet/spy/IPM actions) and cannot work for a station, so the
 * trader gets its own observer map: no deuterium fee, no action buttons, just
 * the systems, planets, moons, debris and the custom galaxy objects (including
 * the trader's own station).
 */

class Wanderer_galaxy extends Page {

    private int $coord_g = 0;
    private int $coord_s = 0;

    public function controller() : bool {
        global $GlobalUser, $GlobalUni, $now;

        if ( !Wanderer::IsWanderer ( $GlobalUser ) ) {
            MyGoto ( 'wanderer_switch' );     // never returns
        }
        $uid = (int)$GlobalUser['player_id'];
        Wanderer::TickUser ( $uid, $now );

        $station = Wanderer::LoadStation ( $uid );
        if ( $station === null ) {
            MyGoto ( 'wanderer_switch' );     // never returns
        }

        // Coordinates: the submitted/requested ones, defaulting to the station.
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $this->coord_g = (int)( $_POST['galaxy'] ?? 0 );
            $this->coord_s = (int)( $_POST['system'] ?? 0 );
            if ( isset ( $_POST['galaxyLeft'] ) )  $this->coord_g--;
            if ( isset ( $_POST['galaxyRight'] ) ) $this->coord_g++;
            if ( isset ( $_POST['systemLeft'] ) )  $this->coord_s--;
            if ( isset ( $_POST['systemRight'] ) ) $this->coord_s++;
        }
        else {
            if ( isset ( $_GET['galaxy'] ) ) $this->coord_g = (int)$_GET['galaxy'];
            else $this->coord_g = (int)$station['g'];
            if ( isset ( $_GET['system'] ) ) $this->coord_s = (int)$_GET['system'];
            else $this->coord_s = (int)$station['s'];
        }

        if ( $this->coord_g < 1 ) $this->coord_g = 1;
        if ( $this->coord_g > (int)$GlobalUni['galaxies'] ) $this->coord_g = (int)$GlobalUni['galaxies'];
        if ( $this->coord_s < 1 ) $this->coord_s = 1;
        if ( $this->coord_s > (int)$GlobalUni['systems'] ) $this->coord_s = (int)$GlobalUni['systems'];

        return true;
    }

    public function view() : void {
        global $GlobalUser, $GlobalUni, $session;

        $uid = (int)$GlobalUser['player_id'];
        $station = Wanderer::LoadStation ( $uid );
        if ( $station === null ) {
            MyGoto ( 'wanderer_switch' );     // never returns
        }

        $coord_g = $this->coord_g;
        $coord_s = $this->coord_s;
        $skin = UserSkin();
        $station_planet_id = (int)$station['planet_id'];

        // ----- Observer toolbar ----------------------------------------
        echo "<table width='569'><tr><td class='c' colspan='2'>".loca ( "WANDERER_GALAXY_TITLE" ).
             " &nbsp;<font size='1' color='#aaa'>(".loca ( "WANDERER_GALAXY_READONLY" ).")</font></td></tr>\n";
        echo "<tr><th><form action='index.php?page=wanderer_galaxy&amp;session=".$session."' method='POST'>";
        echo "<table width='100%'><tr>";
        echo "<td class='c' width='28%'>".loca ( "GALAXY_GALAXY" ).": ";
        echo "<input type='button' name='galaxyLeft' value='&lt;-' onClick='this.form.submit()'> ";
        echo "<input type='text' name='galaxy' size='4' maxlength='3' value='".$coord_g."'> ";
        echo "<input type='button' name='galaxyRight' value='-&gt;' onClick='this.form.submit()'></td>";
        echo "<td class='c' width='28%'>".loca ( "GALAXY_SYSTEM" ).": ";
        echo "<input type='button' name='systemLeft' value='&lt;-' onClick='this.form.submit()'> ";
        echo "<input type='text' name='system' size='5' maxlength='4' value='".$coord_s."'> ";
        echo "<input type='button' name='systemRight' value='-&gt;' onClick='this.form.submit()'></td>";
        echo "<td class='c' width='16%'><input type='submit' value='".loca ( "GALAXY_SHOW" )."'></td>";
        echo "<td class='c' width='28%' align='right'><font size='1'>".loca ( "WANDERER_NAV_GALAXY" )." ".$coord_g.
             " &nbsp;&nbsp;".loca ( "WANDERER_UI_SECTOR" )." ".$coord_g."</font></td>";
        echo "</tr></table></form></th></tr></table><br>\n";

        // ----- Sector price hint (the trader's view of a sector) --------
        if ( (int)$station['res_scan'] > 0 || $coord_g == (int)$station['g'] ) {
            echo "<table width='569'><tr><td class='c'>".loca ( "WANDERER_MARKET_FACTORS" )."</td></tr><tr><th>";
            foreach ( array ( GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM ) as $rc ) {
                $f = Wanderer::MarketFactor ( $coord_g, $rc );
                $pct = (int)round ( $f * 100 );
                $color = $f > 1.0 ? 'lime' : ( $f < 1.0 ? '#ff8080' : '#fff' );
                echo "<font color='".$color."'>".loca ( "NAME_".$rc )." ".$pct."%</font> &nbsp;&nbsp;";
            }
            echo "</th></tr></table><br>\n";
        }

        // ----- Custom galaxy objects of the system -----------------------
        $custom_result = EnumCustomPlanetsGalaxy ( $coord_g, $coord_s );
        $custom_planets = array ();
        $custom_rows = dbrows ( $custom_result );
        for ( $i = 0; $i < $custom_rows; $i++ ) $custom_planets[] = dbarray ( $custom_result );
        $has_custom = count ( $custom_planets ) > 0 ? 1 : 0;

        // ----- System table ----------------------------------------------
        $colspan = 7 + $has_custom;
        echo "<table width='569'>\n";
        echo "<tr><td class='c' colspan='".$colspan."'>".loca ( "GALAXY_SYSTEM" )." ".$coord_g.":".$coord_s."</td></tr>\n";
        echo "<tr>\n";
        echo "<td class='c'>".loca ( "GALAXY_HEAD_COORD" )."</td>\n";
        echo "<td class='c'>".loca ( "GALAXY_HEAD_PLANET" )."</td>\n";
        echo "<td class='c'>".loca ( "GALAXY_HEAD_NAME_ACT" )."</td>\n";
        echo "<td class='c'>".loca ( "GALAXY_HEAD_MOON" )."</td>\n";
        echo "<td class='c'>".loca ( "GALAXY_HEAD_DF" )."</td>\n";
        if ( $has_custom ) echo "<td class='c'>".loca ( "GALAXY_HEAD_OTHER" )."</td>\n";
        echo "<td class='c'>".loca ( "GALAXY_HEAD_PLAYER_STATUS" )."</td>\n";
        echo "<td class='c'>".loca ( "GALAXY_HEAD_ALLY" )."</td>\n";
        echo "</tr>\n";

        $planets_result = EnumPlanetsGalaxy ( $coord_g, $coord_s );
        $planets_found = 0;
        $now = time ();
        $ago15 = $now - 15 * 60;
        $ago60 = $now - 60 * 60;

        $p = 1;
        $rows = dbrows ( $planets_result );
        for ( $r = 0; $r < $rows; $r++ ) {
            $planet = dbarray ( $planets_result );
            for ( ; $p < (int)$planet['p']; $p++ ) $this->emptyRow ( $p, $custom_planets, $has_custom, $station_planet_id );

            $planet_id = (int)$planet['planet_id'];
            $is_regular = ( (int)$planet['type'] == PTYP_PLANET );
            $is_mine = ( $planet_id == $station_planet_id );
            $user = $is_regular ? LoadUser ( (int)$planet['owner_id'] ) : null;
            $user_id = (int)$planet['owner_id'];
            $user_name = $user !== null ? htmlspecialchars ( $user['oname'] ) : '';

            // Activity markers (foreign players only).
            $akt = "";
            if ( $user !== null && $user_id != $uid ) {
                $activity = (int)$planet['lastakt'];
                if ( $activity > $ago15 ) $akt = "&nbsp;<font color='lime'>*</font>";
                else if ( $activity > $ago60 ) $akt = "&nbsp;<font color='orange'>(".floor ( ( $now - $activity ) / 60 ).")</font>";
            }

            echo "<tr>\n";
            echo "<th width='30'>".$p."</th>\n";

            // Planet icon + tooltip.
            if ( $is_regular ) {
                $overlib = $this->planetOverlib ( $planet, $user_name, $is_mine );
                echo "<th width='30'>";
                echo "<a style='cursor:pointer' onmouseover='return overlib(\"".$overlib."\", STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -40 );' onmouseout='return nd();'>";
                echo "<img src='".GetPlanetSmallImage ( $skin, $planet )."' width='30' height='30'";
                if ( $is_mine ) echo " style='border:1px solid #ffb000;'";
                echo "></a></th>\n";

                echo "<th width='130' style='white-space:nowrap;'>";
                if ( $is_mine ) echo "<font color='#ffb000'><b>".htmlspecialchars ( (string)$planet['name'] )."</b></font>";
                else echo htmlspecialchars ( (string)$planet['name'] );
                echo $akt."</th>\n";
            }
            else {
                // Destroyed / abandoned object: no icon, gray name.
                echo "<th width='30'></th>";
                echo "<th width='130' style='white-space:nowrap;'><font color='#888'>".
                     htmlspecialchars ( (string)$planet['name'] )."</font></th>\n";
            }

            // Moon.
            $moon = LoadPlanet ( $coord_g, $coord_s, $p, 3 );
            echo "<th width='30'>";
            if ( $moon ) {
                $mover = $this->moonOverlib ( $moon );
                echo "<a style='cursor:pointer' onmouseover='return overlib(\"".$mover."\", STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -40 );' onmouseout='return nd();'>";
                echo "<img src='".GetPlanetSmallImage ( $skin, $moon )."' width='22' height='22'></a>";
            }
            echo "</th>\n";

            // Debris.
            echo "<th width='30'>";
            $debris = LoadPlanet ( $coord_g, $coord_s, $p, 2 );
            if ( $debris && ( (float)$debris[GID_RC_METAL] + (float)$debris[GID_RC_CRYSTAL] ) >= GALAXY_PHANTOM_DEBRIS ) {
                echo "<img src='".$skin."planeten/debris.jpg' width='22' height='22' title='".loca ( "GALAXY_DF_RESOURCES" )."'>";
            }
            echo "</th>\n";

            // Custom galaxy objects (the station and other modded objects).
            // The extra column exists only when the system has any.
            if ( $has_custom ) $this->customCell ( $p, $custom_planets, $station_planet_id );

            // Player status.
            echo "<th width='150'>";
            if ( $user !== null && $user_id != USER_SPACE ) {
                if ( $user_id == $uid ) {
                    echo "<span class='normal'><font color='#ffb000'><b>".$user_name."</b></font></span>";
                }
                else {
                    $pstat = 'normal';
                    $extra = "";
                    if ( IsPlayerNewbie ( $user_id ) ) $pstat = 'noob';
                    else if ( IsPlayerStrong ( $user_id ) ) $pstat = 'strong';
                    else {
                        $week = $now - 604800;
                        $week4 = $now - 604800 * 4;
                        if ( !empty ( $user['banned'] ) ) { $pstat = 'banned'; $extra = "(".loca ( "GALAXY_LEGEND_BANNED" ).")"; }
                        else if ( !empty ( $user['vacation'] ) ) { $pstat = 'vacation'; $extra = "(".loca ( "GALAXY_LEGEND_VACATION" ).")"; }
                        else if ( (int)$user['lastclick'] <= $week4 ) { $pstat = 'longinactive'; $extra = "(".loca ( "GALAXY_LEGEND_INACTIVE28" ).")"; }
                        else if ( (int)$user['lastclick'] <= $week ) { $pstat = 'inactive'; $extra = "(".loca ( "GALAXY_LEGEND_INACTIVE7" ).")"; }
                    }
                    echo "<span class='$pstat'>".$user_name."</span> $extra";
                }
            }
            echo "</th>\n";

            // Alliance.
            echo "<th width='80'>";
            if ( $user !== null && (int)$user['ally_id'] > 0 ) {
                $ally = LoadAlly ( (int)$user['ally_id'] );
                if ( $ally ) echo htmlspecialchars ( (string)$ally['tag'] );
            }
            echo "</th>\n";

            echo "</tr>\n";
            $p++;
            $planets_found++;
        }
        for ( ; $p <= 15; $p++ ) $this->emptyRow ( $p, $custom_planets, $has_custom, $station_planet_id );

        echo "<tr><td class='c' colspan='".$colspan."'>(".va ( loca ( "GALAXY_INFO_POPULATED" ), $planets_found ).")</td></tr>\n";
        echo "</table><br>\n";
    }

    /**
     * One empty (free) position row.
     */
    private function emptyRow(int $p, array $custom_planets, int $has_custom, int $own_station_id = 0) : void {
        echo "<tr><th width='30'>".$p."</th>";
        echo "<th width='30'></th><th width='130'></th><th width='30'></th><th width='30'></th>";
        if ( $has_custom ) $this->customCell ( $p, $custom_planets, $own_station_id );
        echo "<th width='150'></th><th width='80'></th></tr>\n";
    }

    /**
     * Custom galaxy objects cell (station icons with their hook tooltips).
     * The trader's own station is highlighted with a border and a marker.
     */
    private function customCell(int $p, array $custom_planets, int $own_station_id = 0) : void {
        $matches = array ();
        foreach ( $custom_planets as $cp ) {
            if ( (int)$cp['p'] == $p ) $matches[] = $cp;
        }
        if ( count ( $matches ) == 0 ) {
            echo "<th width='30'></th>";
            return;
        }
        echo "<th width='".( 30 * count ( $matches ) )."'>";
        foreach ( $matches as $cp ) {
            $is_own = ( (int)$cp['planet_id'] == $own_station_id && $own_station_id > 0 );
            $info = array ();
            $res = ModsExecArrRef ( 'page_galaxy_custom_object', $cp, $info );
            $overlib = $res ? $info['overlib'] : htmlspecialchars ( (string)$cp['name'] );
            if ( $is_own ) {
                $overlib .= "<br><center><font color=#ffb000><b>".loca ( "WANDERER_GALAXY_OWN" )."</b></font></center>";
            }
            echo "<a style='cursor:pointer' onmouseover='return overlib(\"".$overlib."\", STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -40 );' onmouseout='return nd();'>";
            echo "<img src='".GetPlanetSmallImage ( UserSkin(), $cp )."' width='30' height='30'";
            if ( $is_own ) echo " style='border:1px solid #ffb000;'";
            echo "></a>";
        }
        echo "</th>";
    }

    /**
     * Read-only tooltip of a planet. The content is injected into
     * onmouseover='return overlib("...")', so it must not contain quotes.
     */
    private function planetOverlib(array $planet, string $owner, bool $is_mine) : string {
        $img = GetPlanetSmallImage ( UserSkin(), $planet );
        $res = "<table width=240><tr><td class=c colspan=2>".
               loca ( "GALAXY_PLANET" )." ".htmlspecialchars ( (string)$planet['name'] ).
               " [".(int)$planet['g'].":".(int)$planet['s'].":".(int)$planet['p']."]</td></tr>";
        $res .= "<tr><th width=80><img src=".$img." height=75 width=75></th>";
        $res .= "<th align=left>".loca ( "WANDERER_GALAXY_CAPTAIN" )." ".$owner."<br>";
        $res .= $is_mine
            ? "<font size=1 color=#ffb000><b>".loca ( "WANDERER_GALAXY_OWN" )."</b></font>"
            : "<font size=1 color=#888>".loca ( "WANDERER_GALAXY_OBSERVE" )."</font>";
        $res .= "</th></tr></table>";
        return $res;
    }

    /**
     * Read-only tooltip of a moon (quote-free, see planetOverlib()).
     */
    private function moonOverlib(array $moon) : string {
        $img = GetPlanetSmallImage ( UserSkin(), $moon );
        $res = "<table width=240><tr><td class=c colspan=2>".loca ( "GALAXY_MOON" )." ".
               htmlspecialchars ( (string)$moon['name'] )." [".(int)$moon['g'].":".(int)$moon['s'].":".(int)$moon['p']."]</td></tr>";
        $res .= "<tr><th width=80><img src=".$img." height=75 width=75></th>";
        $res .= "<th align=left>".loca ( "GALAXY_MOON_SIZE" ).": ".nicenum ( (int)$moon['diameter'] )." km<br>".
                loca ( "GALAXY_MOON_TEMP" ).": ".(int)$moon['temp']."°C</th></tr></table>";
        return $res;
    }
}

?>
