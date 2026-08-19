<?php

// Jump gate fleet dispatch

class Sprungtor extends Page {

    public function controller () : bool {
        global $GlobalUser;
        global $GlobalUni;
        global $aktplanet;
        global $db_prefix;
        global $fleetmap;
        global $PageError;
        global $now;

        // In the pre-MVC layout $PageError was a page-level global set by the
        // renderer; declare it here so the checks below see it.
        if (!isset($PageError)) $PageError = "";

        $fleetmap_rev = array_reverse ($fleetmap);
        $fleetmap_revnosat = array_diff ($fleetmap_rev, [GID_F_SAT]);

        if ( key_exists ( 'qm', $_POST) ) $source_id = intval($_POST['qm']);
        else $source_id = 0;
        if ( key_exists ( 'zm', $_POST) ) $target_id = intval($_POST['zm']);
        else $target_id = 0;

        $total = 0;
        foreach ( $fleetmap_revnosat as $i=>$gid)
        {
            if ( !key_exists ( "c$gid", $_POST) )  $_POST["c$gid"] = 0;
            $total += floor (abs (intval($_POST["c$gid"])));
        }

        $source = LoadPlanetById ( $source_id );
        $target = LoadPlanetById ( $target_id );

        if ( $source['type'] != PTYP_MOON ) $PageError .= "<center>\n".loca("GATE_ERR_START")."<br></center>\n";
        if ( $target['type'] != PTYP_MOON ) $PageError .= "<center>\n".loca("GATE_ERR_TARGET")."<br></center>\n";

        if ( $PageError === "" ) {
            if ( $source[GID_B_JUMP_GATE] == 0 ) $PageError .= "<center>\n".loca("GATE_ERR_START_GATE")."<br></center>\n";
            if ( $target[GID_B_JUMP_GATE] == 0 ) $PageError .= "<center>\n".loca("GATE_ERR_TARGET_GATE")."<br></center>\n";
        }

        if ( $PageError === "" ) {
            if ( ($source['owner_id'] != $GlobalUser['player_id']) || ($target['owner_id'] != $GlobalUser['player_id'])  ) $PageError .= "<center>\n".loca("GATE_ERR_MOON")."<br></center>\n";
        }

        if ( $PageError === "" ) {
            if ( $total == 0 ) $PageError .= "<center>\n".loca("GATE_ERR_SHIPS")."<br></center>\n";
        }

        if ( $PageError === "" ) {
            $fleet = array ();
            foreach ( $fleetmap_revnosat as $i=>$gid)
            {
                $amount = floor (abs(intval($_POST["c$gid"])));
                if ( $amount > $source[$gid] ) {
                    $PageError .= "<center>\n".loca("GATE_ERR_NOTENOUGH")."<br></center>\n";
                    break;
                }
                $fleet[$gid] = $amount;
            }
            $fleet[GID_F_SAT] = 0;
        }

        if ( $PageError === "" ) {
            AdjustShips ( $fleet, $source_id, '-' );
            AdjustShips ( $fleet, $target_id, '+' );

            $cooldown_time = (60*60) / $GlobalUni['fspeed'] - 1;
            $cooldown = $now + $cooldown_time;

            $now = time ();
            $query = "UPDATE ".$db_prefix."planets SET gate_until=".$cooldown." WHERE planet_id=$source_id";
            dbquery ($query);
            $query = "UPDATE ".$db_prefix."planets SET gate_until=".$cooldown." WHERE planet_id=$target_id";
            dbquery ($query);

            MyGoto ( "infos", "&cp=$target_id&gid=43" );
        }

        return true;
    }

    public function view () : void {
        // Never called - either shows error or redirects
    }
}
?>