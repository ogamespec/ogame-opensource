<?php

/**
 * Station modules (limited building) of the Wanderer modification.
 */

class Wanderer_mods extends Page {

    public function controller() : bool {
        global $GlobalUser, $now;

        if ( !Wanderer::IsWanderer ( $GlobalUser ) ) {
            MyGoto ( 'wanderer_switch' );     // never returns
        }
        $uid = (int)$GlobalUser['player_id'];
        Wanderer::TickUser ( $uid, $now );

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset ( $_POST['upgrade'] ) ) {
            $col = (string)$_POST['col'];
            if ( !preg_match ( '/^[a-z_]+$/', $col ) ) {
                $GLOBALS['PageError'] = loca ( "WANDERER_ERR_BUILD_ID" );
            }
            else {
                $err = Wanderer::StartUpgrade ( $uid, $col, $now );
                if ( $err === '' ) $GLOBALS['PageMessage'] = loca ( "WANDERER_MSG_STARTED" );
                else $GLOBALS['PageError'] = loca ( $err );
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

        // ----- Busy indicator ------------------------------------------
        if ( (int)$st['build_until'] > $now ) {
            Wanderer::UiBox ( loca ( "WANDERER_BUILDING" ) );
            echo "<tr><th>".Wanderer::UiObjectName ( (string)$st['build_id'] )." — ".
                 loca ( "WANDERER_LEFT" )." ".Wanderer::FormatDuration ( (int)$st['build_until'] - $now )."</th></tr>\n";
            Wanderer::UiBoxEnd();
        }

        // ----- Core + modules ------------------------------------------
        $items = array_merge ( array ( 'core' ), Wanderer::ModuleColumns() );
        foreach ( $items as $col ) {
            $lvl = (int)$st[$col];
            $max = $col === 'core' ? WANDERER_MAX_CORE_LEVEL : Wanderer::ModuleMaxLevel ( (int)$st['core'] );
            $cost = Wanderer::UpgradeCost ( $st, $col );
            $busy_now = ( (int)$st['build_until'] > $now );
            $ready = $busy_now || $cost === null;

            Wanderer::UiBox ( Wanderer::UiObjectName ( $col ) );
            echo "<tr><th><table width='100%'><tr>\n";
            echo "<td align='left' width='45%'><font size='1'>".Wanderer::UiObjectEffect ( $st, $col )."</font></td>\n";
            echo "<td align='center' width='15%'><b>".$lvl."</b> / ".$max."</td>\n";
            echo "<td align='center' width='30%'>";
            if ( $cost === null ) {
                echo "<font color='#ff8080'>".loca ( "WANDERER_MAX" )."</font>";
            }
            else {
                echo loca ( "WANDERER_COST" ).": ";
                echo Wanderer::UiMoney ( $cost['metal'] )." / ".Wanderer::UiMoney ( $cost['crystal'] )." / ".Wanderer::UiMoney ( $cost['deuterium'] );
                echo "<br><font size='1'>".loca ( "WANDERER_TIME" )." ".Wanderer::FormatDuration ( Wanderer::UpgradeSeconds ( $st, $col ) )."</font>";
            }
            echo "</td>\n";
            echo "<td align='center' width='10%'>";
            if ( $ready ) {
                if ( $busy_now ) echo "<font color='#888'>".loca ( "WANDERER_BUSY" )."</font>";
                else echo "<font color='#888'>—</font>";
            }
            else {
                echo "<form action='index.php?page=wanderer_mods&amp;session=".$session."' method='POST'>";
                echo "<input type='hidden' name='col' value='".$col."'>";
                echo "<input type='submit' name='upgrade' value='".loca ( "WANDERER_UPGRADE" )."'>";
                echo "</form>";
            }
            echo "</td></tr></table></th></tr>\n";
            Wanderer::UiBoxEnd();
        }
    }
}

?>
