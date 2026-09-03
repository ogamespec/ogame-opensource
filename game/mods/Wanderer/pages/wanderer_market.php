<?php

/**
 * Exchange of the Wanderer modification: the Guild of Traders (NPC, for
 * wanderers) and the exchange orders between players (for everyone).
 */

class Wanderer_market extends Page {

    public function controller() : bool {
        global $GlobalUser, $now;

        $uid = (int)$GlobalUser['player_id'];
        $wanderer = Wanderer::IsWanderer ( $GlobalUser );
        if ( $wanderer ) {
            Wanderer::TickUser ( $uid, $now );
        }

        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return true;

        // Guild of Traders exchange (wanderers only).
        if ( isset ( $_POST['exchange'] ) ) {
            if ( !$wanderer ) {
                $GLOBALS['PageError'] = loca ( "WANDERER_ERR_NOT_IN_MODE" );
                return true;
            }
            $give_rc = (int)( $_POST['give_rc'] ?? 0 );
            $want_rc = (int)( $_POST['want_rc'] ?? 0 );
            $give_amt = (float)( $_POST['give_amt'] ?? 0 );
            $err = Wanderer::GuildExchange ( $uid, $give_rc, max ( 0, $give_amt ), $want_rc, $now );
            if ( $err === '' ) $GLOBALS['PageMessage'] = loca ( "WANDERER_MSG_TRADE_OK" );
            else $GLOBALS['PageError'] = loca ( $err );
            return true;
        }

        // Place an order.
        if ( isset ( $_POST['place'] ) ) {
            $give_rc = (int)( $_POST['give_rc'] ?? 0 );
            $want_rc = (int)( $_POST['want_rc'] ?? 0 );
            $give_amt = (float)( $_POST['give_amt'] ?? 0 );
            $want_amt = (float)( $_POST['want_amt'] ?? 0 );
            $err = Wanderer::PlaceOrder ( $uid, $give_rc, max ( 0, $give_amt ), $want_rc, max ( 0, $want_amt ), $now );
            if ( $err === '' ) $GLOBALS['PageMessage'] = loca ( "WANDERER_MSG_ORDER_PLACED" );
            else $GLOBALS['PageError'] = loca ( $err );
            return true;
        }

        // Cancel / accept an order.
        if ( isset ( $_POST['cancel'] ) ) {
            $GLOBALS['PageError'] = loca ( Wanderer::CancelOrder ( $uid, (int)( $_POST['order_id'] ?? 0 ) ) );
            return true;
        }
        if ( isset ( $_POST['accept'] ) ) {
            $err = Wanderer::AcceptOrder ( $uid, (int)( $_POST['order_id'] ?? 0 ), $now );
            if ( $err === '' ) $GLOBALS['PageMessage'] = loca ( "WANDERER_MSG_ORDER_ACCEPTED" );
            else $GLOBALS['PageError'] = loca ( $err );
            return true;
        }

        return true;
    }

    public function view() : void {
        global $GlobalUser, $session, $now;

        $uid = (int)$GlobalUser['player_id'];
        $wanderer = Wanderer::IsWanderer ( $GlobalUser );
        $st = null;
        if ( $wanderer ) {
            $st = Wanderer::LoadStation ( $uid );
            if ( $st === null ) {
                MyGoto ( 'wanderer_switch' );     // never returns
            }
            Wanderer::UiStationStrip ( $st );
        }

        $res_names = array (
            GID_RC_METAL     => Wanderer::UiResourceName ( GID_RC_METAL ),
            GID_RC_CRYSTAL   => Wanderer::UiResourceName ( GID_RC_CRYSTAL ),
            GID_RC_DEUTERIUM => Wanderer::UiResourceName ( GID_RC_DEUTERIUM ),
        );

        // ----- Guild of Traders ----------------------------------------
        if ( $wanderer ) {
            $galaxy = (int)$st['g'];
            $comm = Wanderer::MarketCommission ( $st );

            Wanderer::UiBox ( loca ( "WANDERER_MARKET_GUILD" )." — ".loca ( "WANDERER_NAV_GALAXY" )." ".$galaxy );
            echo "<tr><th><table width='100%'>\n";

            // Current sector factors.
            echo "<tr><td class='c' colspan='7'>".loca ( "WANDERER_MARKET_FACTORS" )."</td></tr><tr>";
            foreach ( array ( GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM ) as $rc ) {
                $f = Wanderer::MarketFactor ( $galaxy, $rc, $now );
                $pct = (int)round ( $f * 100 );
                $color = $f > 1.0 ? 'lime' : ( $f < 1.0 ? '#ff8080' : '#fff' );
                echo "<td class='c'>".$res_names[$rc]."</td>";
            }
            echo "<td class='c'>".loca ( "WANDERER_MARKET_COMMISSION" )."</td></tr><tr>";
            foreach ( array ( GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM ) as $rc ) {
                $f = Wanderer::MarketFactor ( $galaxy, $rc, $now );
                $pct = (int)round ( $f * 100 );
                $color = $f > 1.0 ? 'lime' : ( $f < 1.0 ? '#ff8080' : '#fff' );
                echo "<th><font color='".$color."'>".$pct."%</font></th>";
            }
            echo "<th>".round ( $comm * 100, 1 )."%</th></tr>\n";

            // Exchange rows: give -> want.
            $pairs = array (
                array ( GID_RC_METAL, GID_RC_CRYSTAL ),
                array ( GID_RC_METAL, GID_RC_DEUTERIUM ),
                array ( GID_RC_CRYSTAL, GID_RC_METAL ),
                array ( GID_RC_CRYSTAL, GID_RC_DEUTERIUM ),
                array ( GID_RC_DEUTERIUM, GID_RC_METAL ),
                array ( GID_RC_DEUTERIUM, GID_RC_CRYSTAL ),
            );
            echo "<tr><td class='c' colspan='7'>".loca ( "WANDERER_MARKET_EXCHANGE" )."</td></tr>\n";
            foreach ( $pairs as $pair ) {
                $give_rc = $pair[0];
                $want_rc = $pair[1];
                $v_give = Wanderer::MarketValue ( $galaxy, $give_rc, $now );
                $v_want = Wanderer::MarketValue ( $galaxy, $want_rc, $now );
                $rate = $v_give / $v_want * ( 1 - $comm );

                echo "<tr><th colspan='3' align='right'>".loca ( "WANDERER_MARKET_GIVE" )." ".
                     $res_names[$give_rc]." &rarr; ".loca ( "WANDERER_MARKET_GET" )." ".
                     $res_names[$want_rc]."<br><font size='1'>1 = ".round ( $rate, 3 )."</font></th>";
                echo "<th colspan='4' align='left'>";
                echo "<form action='index.php?page=wanderer_market&amp;session=".$session."' method='POST'>";
                echo "<input type='hidden' name='give_rc' value='".$give_rc."'>";
                echo "<input type='hidden' name='want_rc' value='".$want_rc."'>";
                echo "<input type='text' name='give_amt' size='10' value='0'> ";
                echo "<input type='submit' name='exchange' value='".loca ( "WANDERER_MARKET_TRADE" )."'>";
                echo "</form>";
                echo "</th></tr>\n";
            }
            echo "</table></th></tr>\n";
            Wanderer::UiBoxEnd();
        }

        // ----- Place an order ------------------------------------------
        Wanderer::UiBox ( loca ( "WANDERER_MARKET_PLACE_TITLE" ) );
        echo "<tr><th><table width='100%'><tr>";
        echo "<td class='c'>".loca ( "WANDERER_MARKET_I_GIVE" )."</td>";
        echo "<td class='c'>".loca ( "WANDERER_MARKET_I_WANT" )."</td>";
        echo "<td class='c'>&nbsp;</td></tr><tr>";
        echo "<form action='index.php?page=wanderer_market&amp;session=".$session."' method='POST'>";
        echo "<th><select name='give_rc'>";
        foreach ( $res_names as $rc => $name ) echo "<option value='".$rc."'>".$name."</option>";
        echo "</select> <input type='text' name='give_amt' size='10' value='0'></th>";
        echo "<th><select name='want_rc'>";
        foreach ( $res_names as $rc => $name ) echo "<option value='".$rc."'>".$name."</option>";
        echo "</select> <input type='text' name='want_amt' size='10' value='0'></th>";
        echo "<th><input type='submit' name='place' value='".loca ( "WANDERER_MARKET_PLACE" )."'></th>";
        echo "</form></tr></table></th></tr>\n";
        Wanderer::UiBoxEnd();

        // ----- Open orders ---------------------------------------------
        Wanderer::UiBox ( loca ( "WANDERER_MARKET_ORDERS" ) );
        $orders = Wanderer::OpenOrders ( $now );
        echo "<tr><th><table width='100%'>\n";
        echo "<tr>";
        echo "<td class='c' width='22%'>".loca ( "WANDERER_MARKET_ORDER_SELLER" )."</td>";
        echo "<td class='c' width='24%'>".loca ( "WANDERER_MARKET_ORDER_OFFER" )."</td>";
        echo "<td class='c' width='24%'>".loca ( "WANDERER_MARKET_ORDER_WANT" )."</td>";
        echo "<td class='c' width='15%'>".loca ( "WANDERER_MARKET_ORDER_EXPIRES" )."</td>";
        echo "<td class='c' width='15%'>".loca ( "WANDERER_MARKET_ORDER_ACTION" )."</td>";
        echo "</tr>\n";

        if ( count ( $orders ) == 0 ) {
            echo "<tr><th colspan='5'>".loca ( "WANDERER_MARKET_NO_ORDERS" )."</th></tr>\n";
        }

        foreach ( $orders as $o ) {
            $owner = LoadUser ( intval ( $o['owner_id'] ) );
            $owner_name = $owner !== null ? htmlspecialchars ( $owner['oname'] ) : '?';
            $own_order = ( intval ( $o['owner_id'] ) == $uid );
            $give = Wanderer::UiMoney ( (float)$o['give_amt'] )." ".$res_names[ (int)$o['give_rc'] ];
            $want = Wanderer::UiMoney ( (float)$o['want_amt'] )." ".$res_names[ (int)$o['want_rc'] ];

            echo "<tr>";
            echo "<th>".$owner_name;
            if ( $owner !== null && Wanderer::IsWanderer ( $owner ) ) {
                echo " <font color='#ffb000' title='".loca ( "WANDERER_GALAXY_STATION" )."'>(W)</font>";
            }
            echo "</th>";
            echo "<th>".$give."</th>";
            echo "<th>".$want."</th>";
            echo "<th>".Wanderer::FormatDuration ( (int)$o['until'] - $now )."</th>";
            echo "<th>";
            if ( $own_order ) {
                echo "<form action='index.php?page=wanderer_market&amp;session=".$session."' method='POST'>";
                echo "<input type='hidden' name='order_id' value='".intval ( $o['order_id'] )."'>";
                echo "<input type='submit' name='cancel' value='".loca ( "WANDERER_MARKET_CANCEL" )."'>";
                echo "</form>";
            }
            else {
                echo "<form action='index.php?page=wanderer_market&amp;session=".$session."' method='POST'>";
                echo "<input type='hidden' name='order_id' value='".intval ( $o['order_id'] )."'>";
                echo "<input type='submit' name='accept' value='".loca ( "WANDERER_MARKET_ACCEPT" )."'>";
                echo "</form>";
            }
            echo "</th></tr>\n";
        }
        echo "</table></th></tr>\n";
        Wanderer::UiBoxEnd();
    }
}

?>
