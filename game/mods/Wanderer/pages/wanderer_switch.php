<?php

/**
 * Game mode switch of the Wanderer modification: enter / leave the wanderer
 * mode (a classic empire <-> a single wandering station).
 */

class Wanderer_switch extends Page {

    public function controller() : bool {
        global $GlobalUser, $now;

        $uid = (int)$GlobalUser['player_id'];

        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) return true;

        // Become a wandering trader.
        if ( isset ( $_POST['enter'] ) ) {
            $err = Wanderer::EnterWandererMode ( $uid, $now );
            if ( $err === '' ) {
                MyGoto ( 'wanderer_home', '&notice=1' );     // never returns
            }
            $GLOBALS['PageError'] = loca ( $err );
            return true;
        }

        // Return to the empire.
        if ( isset ( $_POST['leave'] ) ) {
            $err = Wanderer::ExitWandererMode ( $uid, $now );
            if ( $err === '' ) {
                MyGoto ( 'overview' );     // never returns
            }
            $GLOBALS['PageError'] = loca ( $err );
            return true;
        }

        return true;
    }

    public function view() : void {
        global $GlobalUser, $session, $now;

        $wanderer = Wanderer::IsWanderer ( $GlobalUser );

        echo "<table width='569'>\n";
        echo "<tr><td class='c'>".loca ( "WANDERER_SWITCH_TITLE" )."</td></tr>\n";
        echo "<tr><th><img src='mods/Wanderer/img/bg1.jpg' width='560' style='border:1px solid #666;'></th></tr>\n";
        echo "<tr><th align='left'><font size='1'>".loca ( "WANDERER_SWITCH_DESC" )."</font></th></tr>\n";

        if ( !$wanderer ) {

            echo "<tr><th align='center'><br><b>".loca ( "WANDERER_SWITCH_ENTER_HEAD" )."</b><br><br>";
            echo "<table width='80%'>";
            $rules = array (
                "WANDERER_SWITCH_RULE_1", "WANDERER_SWITCH_RULE_2", "WANDERER_SWITCH_RULE_3",
                "WANDERER_SWITCH_RULE_4", "WANDERER_SWITCH_RULE_5",
            );
            foreach ( $rules as $rule ) {
                echo "<tr><th align='left'><font size='1'>• ".loca ( $rule )."</font></th></tr>";
            }
            echo "</table><br>";
            echo "<form action='index.php?page=wanderer_switch&amp;session=".$session."' method='POST'>";
            echo "<input type='submit' name='enter' value='".loca ( "WANDERER_SWITCH_ENTER" )."'></form></th></tr>\n";
        }
        else {

            $st = Wanderer::LoadStation ( (int)$GlobalUser['player_id'] );
            $busy = "";
            if ( $st !== null && (int)$st['build_until'] > $now ) {
                $busy = loca ( "WANDERER_ERR_BUSY" )." (".Wanderer::FormatDuration ( (int)$st['build_until'] - $now ).")";
            }

            echo "<tr><th align='center'><br><b>".loca ( "WANDERER_SWITCH_LEAVE_HEAD" )."</b><br><br>";
            if ( $st !== null ) {
                echo "<font size='1'>".loca ( "WANDERER_SWITCH_STATION" ).": <b>".htmlspecialchars ( (string)$st['name'] )."</b> &nbsp; ".
                     loca ( "WANDERER_UI_SECTOR" )." ". (int)$st['g']." &nbsp; ".
                     loca ( "WANDERER_HOME_JUMPS" ).": ".(int)$st['jumps']." &nbsp; ".
                     loca ( "WANDERER_HOME_DEALS" ).": ".(int)$st['deals']."</font><br><br>";
            }
            if ( $busy !== "" ) {
                echo "<font color='#ff8080'>".$busy."</font><br><br>";
            }
            else {
                echo "<form action='index.php?page=wanderer_switch&amp;session=".$session."' method='POST'>";
                echo "<input type='submit' name='leave' value='".loca ( "WANDERER_SWITCH_LEAVE" )."'></form>";
            }
            echo "</th></tr>\n";
        }

        echo "</table>\n";
    }
}

?>
