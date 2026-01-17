<?php

// Space Storm mod.

// Active Space Storm Mask. Can have multiple effects at once.
const SPACE_STORM_MASK_NONE = 0;
const SPACE_STORM_MASK_SUBSPACE_TURB = 0x1;     // Subspace Turbulence
const SPACE_STORM_MASK_SUBSPACE_JUMP = 0x2;     // Subspace Jump
const SPACE_STORM_MASK_POLAR_SHIELD = 0x4;      // Polar Shield Distortion
const SPACE_STORM_MASK_QUANTUM_DRIVE = 0x8;     // Quantum Drive Instability
const SPACE_STORM_MASK_CHRONO_SPY = 0x10;       // Chrono-Spy Disruption
const SPACE_STORM_MASK_ENERGY_COLLAPSE = 0x20;  // Energy Collapse
const SPACE_STORM_MASK_GRAV_DEFENSE = 0x40;     // Gravitational Defense Anomaly
const SPACE_STORM_MASK_MATTER_SIGNATURE = 0x80; // Matter Signature
const SPACE_STORM_MASK_COMM_BREAKDOWN = 0x100;  // Communication Breakdown
const SPACE_STORM_MASK_ATTACK_REVERB = 0x200;   // Attack Reverberation

const GID_B_REALITY_STAB = 157384;      // Reality Stabilizer Object ID

const QTYP_SPACE_STORM = "SpaceStorm";

const SPACE_STORM_PERIOD_SECONDS = 60*60;

class SpaceStorm extends GameMod {

    public function install() : void {
        global $db_prefix;

        LockTables();

        // Add new columns
        $query = "ALTER TABLE ".$db_prefix."uni ADD COLUMN prev_storm INT DEFAULT 0;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."uni ADD COLUMN storm INT DEFAULT 0;";
        dbquery ($query);        
        $query = "ALTER TABLE ".$db_prefix."planets ADD COLUMN `".GID_B_REALITY_STAB."` INT DEFAULT 0;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."planets ADD COLUMN `s".GID_B_REALITY_STAB."` INT DEFAULT 0;";   // Storm mask
        dbquery ($query);        

        // Start Space Storm event
        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_SPACE_STORM."'";
        $result = dbquery ($query);
        if ( dbrows ($result) == 0 ) {
            AddQueue (USER_SPACE, QTYP_SPACE_STORM, 0, 0, 0, time(), SPACE_STORM_PERIOD_SECONDS);
        }

        UnlockTables();
    }

    public function uninstall() : void {
        global $db_prefix;

        LockTables();

        // Remove columns
        $query = "ALTER TABLE ".$db_prefix."uni DROP COLUMN prev_storm;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."uni DROP COLUMN storm;";
        dbquery ($query);        
        $query = "ALTER TABLE ".$db_prefix."planets DROP COLUMN `".GID_B_REALITY_STAB."`;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."planets DROP COLUMN `s".GID_B_REALITY_STAB."`;";
        dbquery ($query);

        // Delete Space Storm event
        $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_SPACE_STORM."'";
        dbquery ($query);

        UnlockTables();
    }

    public function install_tabs_included (array &$tabs) : bool {
        $tabs['uni']['prev_storm'] = 'INT DEFAULT 0';
        $tabs['uni']['storm'] = 'INT DEFAULT 0';
        $tabs['planets'][GID_B_REALITY_STAB] = 'INT DEFAULT 0';
        return false;
    }

    public function init() : void {
        global $buildmap;
        global $initial;
        global $requirements;
        global $CanBuildTab;

        // Add a new building to the game
        $buildmap[] = GID_B_REALITY_STAB;
        $initial[GID_B_REALITY_STAB] = array (50000, 125000, 50000, 0, 3.0);
        $requirements[GID_B_REALITY_STAB] = array (GID_B_RES_LAB=>3, GID_B_TERRAFORMER=>1);
        $CanBuildTab[PTYP_PLANET][] = GID_B_REALITY_STAB;

        global $GlobalUni;
        loca_add ("space_storm", $GlobalUni['lang'], __DIR__);
    }

    public function update_queue(array &$queue) : bool {
        global $db_prefix;
        if ($queue['type'] === QTYP_SPACE_STORM) {

            ProlongQueue ($queue['task_id'], SPACE_STORM_PERIOD_SECONDS);
            return true;
        }
        else {
            return false;
        }
    }

    public function get_object_image(int $id, array &$img) : bool {
        if ($id == GID_B_REALITY_STAB) {
            $img['path'] = "mods/SpaceStorm/img/reality_stab.png";
            return true;
        }
        return false;
    }    
}

?>