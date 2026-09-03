<?php

/**
 * Navigation (jumps between sectors) of the Wanderer modification.
 */

class Wanderer_nav extends Page {

    public function controller() : bool {
        global $GlobalUser, $now;

        if ( !Wanderer::IsWanderer ( $GlobalUser ) ) {
            MyGoto ( 'wanderer_switch' );     // never returns
        }
        $uid = (int)$GlobalUser['player_id'];
        Wanderer::TickUser ( $uid, $now );

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset ( $_POST['jump'] ) ) {
            $galaxy = (int)( $_POST['galaxy'] ?? 0 );
            $err = Wanderer::DoJump ( $uid, $galaxy, $now );
            if ( $err === '' ) {
                $GLOBALS['PageMessage'] = loca ( "WANDERER_MSG_JUMPED" );
            }
            else {
                $GLOBALS['PageError'] = loca ( $err );
            }
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

        Wanderer::UiStationStrip ( $st );

        $uni = Wanderer::Uni();
        $galaxies = max ( 1, intval ( $uni['galaxies'] ?? 1 ) );
        $from_g = (int)$st['g'];
        $scan = (int)$st['res_scan'];
        $in_cooldown = (int)$st['cooldown_until'] > $now;
        $deut = (float)$st['deuterium'];

        // ----- Rules ----------------------------------------------------
        Wanderer::UiBox ( loca ( "WANDERER_NAV_RULES_TITLE" ) );
        echo "<tr><th><font size='1'>".loca ( "WANDERER_NAV_RULES_TEXT" )."</font></th></tr>\n";
        Wanderer::UiBoxEnd();

        // ----- Sector table ---------------------------------------------
        Wanderer::UiBox ( loca ( "WANDERER_NAV_SECTORS" ) );

        $res_list = array ( GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM );
        echo "<tr><th><table width='100%'>\n";
        echo "<tr>";
        echo "<td class='c' width='10%'>".loca ( "WANDERER_NAV_HEAD_SECTOR" )."</td>";
        if ( $scan > 0 ) {
            echo "<td class='c' width='24%'>".loca ( "WANDERER_NAV_HEAD_PRICES" )."</td>";
        }
        else {
            echo "<td class='c' width='24%'>".loca ( "WANDERER_NAV_HEAD_PRICES_UNKNOWN" )."</td>";
        }
        echo "<td class='c' width='16%'>".loca ( "WANDERER_NAV_HEAD_FUEL" )."</td>";
        echo "<td class='c' width='20%'>".loca ( "WANDERER_NAV_HEAD_WAIT" )."</td>";
        echo "<td class='c' width='14%'>".loca ( "WANDERER_NAV_HEAD_ACTION" )."</td>";
        echo "</tr>\n";

        for ( $g = 1; $g <= $galaxies; $g++ ) {

            $here = ( $g == $from_g );
            $cost = Wanderer::JumpCost ( $st, $from_g, $g );
            $wait = Wanderer::JumpFlightTime ( $st, $from_g, $g ) + Wanderer::JumpCooldown ( $st );
            $enabled = !$in_cooldown && $deut >= $cost;

            echo "<tr><th>".loca ( "WANDERER_NAV_GALAXY" )." ".$g;
            if ( $here ) echo " <font color='lime'>(".loca ( "WANDERER_NAV_HERE" ).")</font>";
            echo "</th>";

            echo "<th>";
            if ( $scan > 0 || $here ) {
                $first = true;
                foreach ( $res_list as $rc ) {
                    $f = Wanderer::MarketFactor ( $g, $rc, $now );
                    $pct = (int)round ( $f * 100 );
                    $color = $f > 1.0 ? 'lime' : ( $f < 1.0 ? '#ff8080' : '#fff' );
                    if ( !$first ) echo " &nbsp; ";
                    $first = false;
                    echo "<font color='".$color."'>".loca ( "NAME_".$rc )." ".$pct."%</font>";
                }
            }
            else {
                echo "<font color='#777'>".loca ( "WANDERER_NAV_NO_DATA" )."</font>";
            }
            echo "</th>";

            echo "<th>".Wanderer::UiMoney ( $cost );
            if ( !$enabled && !$in_cooldown && $deut < $cost ) echo " <font color='#ff8080'>!</font>";
            echo "</th>";

            echo "<th>".Wanderer::FormatDuration ( $wait )."</th>";

            echo "<th>";
            if ( $in_cooldown ) {
                echo "<font color='#888'>".Wanderer::FormatDuration ( (int)$st['cooldown_until'] - $now )."</font>";
            }
            else if ( $deut < $cost ) {
                echo "<font color='#ff8080'>".loca ( "WANDERER_NAV_NO_FUEL" )."</font>";
            }
            else {
                echo "<form action='index.php?page=wanderer_nav&amp;session=".$session."' method='POST'>";
                echo "<input type='hidden' name='galaxy' value='".$g."'>";
                echo "<input type='submit' name='jump' value='".loca ( "WANDERER_NAV_JUMP" )."'>";
                echo "</form>";
            }
            echo "</th></tr>\n";
        }
        echo "</table></th></tr>\n";
        Wanderer::UiBoxEnd();
    }
}

?>
