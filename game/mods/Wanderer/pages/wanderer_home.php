<?php

/**
 * Station overview (dashboard) of the Wanderer modification.
 */

class Wanderer_home extends Page {

    public function controller() : bool {
        global $GlobalUser, $now;

        if ( !Wanderer::IsWanderer ( $GlobalUser ) ) {
            MyGoto ( 'wanderer_switch' );     // never returns
        }
        $uid = (int)$GlobalUser['player_id'];
        Wanderer::TickUser ( $uid, $now );

        // Rename the station.
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset ( $_POST['rename'] ) ) {
            $err = Wanderer::RenameStation ( $uid, (string)( $_POST['newname'] ?? '' ) );
            if ( $err === '' ) {
                MyGoto ( 'wanderer_home' );   // never returns
            }
            $GLOBALS['PageError'] = loca ( $err );
        }
        return true;
    }

    public function view() : void {
        global $GlobalUser, $session, $now;

        $uid = (int)$GlobalUser['player_id'];
        $st = Wanderer::LoadStation ( $uid );
        if ( $st === null ) {
            MyGoto ( 'wanderer_switch' );     // never returns
        }

        // Notices after redirects.
        if ( isset ( $_GET['notice'] ) ) {
            $notice = (int)$_GET['notice'];
            if ( $notice == 1 ) $GLOBALS['PageMessage'] = loca ( "WANDERER_NOTICE_ENTERED" );
            else if ( $notice == 2 ) $GLOBALS['PageMessage'] = loca ( "WANDERER_NOTICE_BLOCKED" );
        }

        Wanderer::UiStationStrip ( $st );

        // ----- Production & cargo -------------------------------------
        $prod = Wanderer::StationProduction ( $st );
        $cap = Wanderer::StationCargoCap ( $st );
        $busy = "";
        if ( (int)$st['build_until'] > $now ) {
            $busy = loca ( "WANDERER_HOME_BUSY" )." ".Wanderer::FormatDuration ( (int)$st['build_until'] - $now ).
                    " (<a href='index.php?page=wanderer_mods&amp;session=".$session."'>".loca ( "WANDERER_HOME_TO_MODS" )."</a>)";
        }

        Wanderer::UiBox ( loca ( "WANDERER_HOME_PRODUCTION" ) );
        echo "<tr><th><table width='100%'>\n";
        echo "<tr><td class='c' width='20%'>".Wanderer::UiResourceName ( GID_RC_METAL )."</td>";
        echo "<td class='c' width='20%'>".Wanderer::UiResourceName ( GID_RC_CRYSTAL )."</td>";
        echo "<td class='c' width='20%'>".Wanderer::UiResourceName ( GID_RC_DEUTERIUM )."</td>";
        echo "<td class='c' width='40%'>".loca ( "WANDERER_HOME_STORAGE" )."</td></tr>\n";
        echo "<tr><th>".Wanderer::UiMoney ( $prod[GID_RC_METAL] )."/h</th>";
        echo "<th>".Wanderer::UiMoney ( $prod[GID_RC_CRYSTAL] )."/h</th>";
        echo "<th>".Wanderer::UiMoney ( $prod[GID_RC_DEUTERIUM] )."/h</th>";
        $filled = (float)$st['metal'] + (float)$st['crystal'] + (float)$st['deuterium'];
        echo "<th>".Wanderer::UiMoney ( $filled )." / ".Wanderer::UiMoney ( $cap )."</th></tr>\n";
        echo "</table></th></tr>\n";
        Wanderer::UiBoxEnd();

        // ----- Modules quick view -------------------------------------
        Wanderer::UiBox ( loca ( "WANDERER_HOME_MODULES" ) );
        echo "<tr><th><table width='100%'>\n";
        $mods = array (
            'mod_mine_m', 'mod_mine_k', 'mod_mine_d', 'mod_solar', 'mod_cargo', 'mod_engine', 'mod_lab', 'mod_hold',
        );
        echo "<tr>";
        foreach ( $mods as $col ) {
            echo "<td class='c' width='12%'><a href='index.php?page=wanderer_mods&amp;session=".$session."'>".
                 Wanderer::UiObjectName ( $col )."</a></td>";
        }
        echo "</tr><tr>";
        $core_lvl = (int)$st['core'];
        foreach ( $mods as $col ) {
            $lvl = (int)$st[$col];
            $max = Wanderer::ModuleMaxLevel ( $core_lvl );
            $fill = $max > 0 ? $lvl / $max : 0;
            echo "<th>".$lvl."<br>".Wanderer::UiProgress ( $fill, '#00aaff' )."</th>";
        }
        echo "</tr></table></th></tr>\n";
        Wanderer::UiBoxEnd();

        // ----- Station log --------------------------------------------
        Wanderer::UiBox ( loca ( "WANDERER_HOME_STATS" ) );
        echo "<tr><th><table width='100%'><tr>";
        echo "<td class='c'>".loca ( "WANDERER_HOME_CORE" )."</td>";
        echo "<td class='c'>".loca ( "WANDERER_HOME_JUMPS" )."</td>";
        echo "<td class='c'>".loca ( "WANDERER_HOME_DEALS" )."</td>";
        echo "<td class='c'>".loca ( "WANDERER_HOME_STARTED" )."</td></tr><tr>";
        echo "<th>".(int)$st['core']."</th>";
        echo "<th>".(int)$st['jumps']."</th>";
        echo "<th>".(int)$st['deals']."</th>";
        echo "<th>".date ( "d.m.Y H:i", (int)$st['started'] )."</th>";
        echo "</tr></table></th></tr>\n";
        Wanderer::UiBoxEnd();

        // ----- Current construction -----------------------------------
        if ( (int)$st['build_until'] > $now ) {
            Wanderer::UiBox ( loca ( "WANDERER_HOME_BUILDING" ) );
            $name = Wanderer::UiObjectName ( (string)$st['build_id'] );
            echo "<tr><th>".$name." — ".$busy."</th></tr>\n";
            Wanderer::UiBoxEnd();
        }
    }
}

?>
