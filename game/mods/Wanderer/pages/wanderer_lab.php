<?php

/**
 * Station laboratory (limited research) of the Wanderer modification.
 */

class Wanderer_lab extends Page {

    public function controller() : bool {
        global $GlobalUser, $now;

        if ( !Wanderer::IsWanderer ( $GlobalUser ) ) {
            MyGoto ( 'wanderer_switch' );     // never returns
        }
        $uid = (int)$GlobalUser['player_id'];
        Wanderer::TickUser ( $uid, $now );

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset ( $_POST['research'] ) ) {
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

        if ( (int)$st['build_until'] > $now ) {
            Wanderer::UiBox ( loca ( "WANDERER_BUILDING" ) );
            echo "<tr><th>".Wanderer::UiObjectName ( (string)$st['build_id'] )." — ".
                 loca ( "WANDERER_LEFT" )." ".Wanderer::FormatDuration ( (int)$st['build_until'] - $now )."</th></tr>\n";
            Wanderer::UiBoxEnd();
        }

        // The lab is required for any research.
        if ( (int)$st['mod_lab'] < 1 ) {
            Wanderer::UiBox ( loca ( "WANDERER_LAB_NEEDED" ) );
            echo "<tr><th>".loca ( "WANDERER_LAB_NEEDED_TEXT" )."</th></tr>\n";
            Wanderer::UiBoxEnd();
        }

        $items = Wanderer::ResearchColumns();
        foreach ( $items as $col ) {
            $lvl = (int)$st[$col];
            $max = Wanderer::ResearchMaxLevel ( $st );
            $cost = Wanderer::UpgradeCost ( $st, $col );
            $busy_now = ( (int)$st['build_until'] > $now );
            $ready = $busy_now || $cost === null || (int)$st['mod_lab'] < 1;

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
                else if ( (int)$st['mod_lab'] < 1 ) echo "<font color='#888'>".loca ( "WANDERER_LAB_REQ" )."</font>";
                else echo "<font color='#888'>—</font>";
            }
            else {
                echo "<form action='index.php?page=wanderer_lab&amp;session=".$session."' method='POST'>";
                echo "<input type='hidden' name='col' value='".$col."'>";
                echo "<input type='submit' name='research' value='".loca ( "WANDERER_RESEARCH" )."'>";
                echo "</form>";
            }
            echo "</td></tr></table></th></tr>\n";
            Wanderer::UiBoxEnd();
        }
    }
}

?>
