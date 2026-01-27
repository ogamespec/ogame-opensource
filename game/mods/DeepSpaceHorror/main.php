<?php

// Deep Space Horror

// Планета - Космический монстр
const PTYP_LEVI_AMOEBA = 22849;
const PTYP_LEVI_GUARDIAN = 22850;
const PTYP_LEVI_JUGGERNAUT = 22851;

const GID_LEVI_AMOEBA = 22852;          // Planktonic Devourer
const GID_LEVI_GUARDIAN = 22853;        // Wandering Monolith
const GID_LEVI_JUGGERNAUT = 22854;      // Galactic Juggernaut 

// Подготовка к прыжку. После этого происходит перемещение левифана, специальная атака и новая подготовка.
const FTYP_LEVI_PREPARE_JUMP = 22855;

class DeepSpaceHorror extends GameMod {

    public function install() : void {

        global $db_prefix;

        LockTables ();

        $query = "ALTER TABLE ".$db_prefix."fleet ADD COLUMN `".GID_LEVI_AMOEBA."` INT DEFAULT 0;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleet ADD COLUMN `".GID_LEVI_GUARDIAN."` INT DEFAULT 0;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleet ADD COLUMN `".GID_LEVI_JUGGERNAUT."` INT DEFAULT 0;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleetlogs ADD COLUMN `".GID_LEVI_AMOEBA."` INT DEFAULT 0;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleetlogs ADD COLUMN `".GID_LEVI_GUARDIAN."` INT DEFAULT 0;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleetlogs ADD COLUMN `".GID_LEVI_JUGGERNAUT."` INT DEFAULT 0;";
        dbquery ($query);

        global $GlobalUni;
        loca_add ("leviathans", $GlobalUni['lang'], __DIR__);

        $this->CreateLeviathan (PTYP_LEVI_AMOEBA);
        $this->CreateLeviathan (PTYP_LEVI_GUARDIAN);
        $this->CreateLeviathan (PTYP_LEVI_JUGGERNAUT);

        UnlockTables ();
    }

    public function uninstall() : void {

        global $db_prefix;

        LockTables ();

        // Удалить все задания флота левиафанов и все планеты-левиафаны
        $result = EnumOwnFleetQueue (USER_SPACE);
        $rows = dbrows ($result);
        while ($rows--)
        {
            $queue = dbarray ( $result );
            $fleet_obj = LoadFleet ($queue['sub_id']);
            $origin = LoadPlanetById ($fleet_obj['start_planet']);
            if ($this->IsPlanetLeviathan($origin['type'])) {
                DeleteFleet ($fleet_obj['fleet_id']);
                RemoveQueue ($queue['task_id']);
                DestroyPlanet ($origin['planet_id']);
            }
        }

        // Remove columns
        $query = "ALTER TABLE ".$db_prefix."fleet DROP COLUMN `".GID_LEVI_AMOEBA."`;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleet DROP COLUMN `".GID_LEVI_GUARDIAN."`;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleet DROP COLUMN `".GID_LEVI_JUGGERNAUT."`;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleetlogs DROP COLUMN `".GID_LEVI_AMOEBA."`;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleetlogs DROP COLUMN `".GID_LEVI_GUARDIAN."`;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."fleetlogs DROP COLUMN `".GID_LEVI_JUGGERNAUT."`;";
        dbquery ($query);

        UnlockTables ();
    }

    public function install_tabs_included (array &$tabs) : bool {
        $tabs['fleet'][GID_LEVI_AMOEBA] = 'INT DEFAULT 0';
        $tabs['fleet'][GID_LEVI_GUARDIAN] = 'INT DEFAULT 0';
        $tabs['fleet'][GID_LEVI_JUGGERNAUT] = 'INT DEFAULT 0';
        $tabs['fleetlogs'][GID_LEVI_AMOEBA] = 'INT DEFAULT 0';
        $tabs['fleetlogs'][GID_LEVI_GUARDIAN] = 'INT DEFAULT 0';
        $tabs['fleetlogs'][GID_LEVI_JUGGERNAUT] = 'INT DEFAULT 0';
        return false;
    }

    public function init() : void {
        global $fleetmap, $UnitParam, $RapidFire;

        $fleetmap[] = GID_LEVI_AMOEBA;
        $UnitParam[GID_LEVI_AMOEBA] = array ( 250000000, 10000, 5000, 0, 100, 0 );
        $RapidFire[GID_LEVI_AMOEBA] = array (
            GID_F_SC => 1000,
            GID_F_LC => 1000,
            GID_F_LF => 500,
            GID_F_HF => 300,
            GID_F_RECYCLER => 1000,
            GID_F_PROBE => 5000,
            GID_F_SAT => 5000,
            GID_D_RL => 500,
            GID_D_LL => 200,
            GID_D_ABM => 100,
        );

        $fleetmap[] = GID_LEVI_GUARDIAN;
        $UnitParam[GID_LEVI_GUARDIAN] = array ( 600000000, 100000, 50000, 0, 500, 0 );
        $RapidFire[GID_LEVI_GUARDIAN] = array (
            GID_F_BATTLESHIP => 100,
            GID_F_BOMBER => 50,
            GID_F_DESTRO => 30,
            GID_F_BATTLECRUISER => 75,
            GID_F_CRUISER => 150,
            GID_D_GAUSS => 30,
            GID_D_ION => 100,
            GID_D_PLASMA => 10,
            GID_D_SDOME => 5,
        );

        $fleetmap[] = GID_LEVI_JUGGERNAUT;
        $UnitParam[GID_LEVI_JUGGERNAUT] = array ( 1200000000, 500000, 250000, 0, 50, 0 );
        $RapidFire[GID_LEVI_JUGGERNAUT] = array (
            // Почти по всем кораблям
            GID_F_LF => 500,
            GID_F_HF => 400,
            GID_F_CRUISER => 200,
            GID_F_BATTLESHIP => 150,
            GID_F_BOMBER => 100,
            GID_F_DESTRO => 75,
            GID_F_BATTLECRUISER => 125,
            // Против Звезды Смерти - ключевой боец против него
            GID_F_DEATHSTAR => 5,
            // Против обороны
            GID_D_HL => 300,
            GID_D_GAUSS => 100,
            GID_D_ION => 250,
            GID_D_PLASMA => 50,
            GID_D_SDOME => 20,
            GID_D_LDOME => 5,
        );

        global $GlobalUser;
        loca_add ("leviathans", $GlobalUser['lang'], __DIR__);
    }

    public function get_planet_small_image(int $type, array &$img) : bool {
        return $this->get_planet_image ($type, $img);
    }

    public function get_planet_image(int $type, array &$img) : bool {
        switch ($type) {
            case PTYP_LEVI_AMOEBA:
                $img['path'] = "mods/DeepSpaceHorror/img/amoeba.jpg";
                return true;
            case PTYP_LEVI_GUARDIAN:
                $img['path'] = "mods/DeepSpaceHorror/img/guardian.jpg";
                return true;
            case PTYP_LEVI_JUGGERNAUT:
                $img['path'] = "mods/DeepSpaceHorror/img/leviathan.jpg";
                return true;
        }
        return false;
    }

    public function get_object_image(int $id, array &$img) : bool {
        switch ($id) {
            case GID_LEVI_AMOEBA:
                $img['path'] = "mods/DeepSpaceHorror/img/amoeba.jpg";
                return true;
            case GID_LEVI_GUARDIAN:
                $img['path'] = "mods/DeepSpaceHorror/img/guardian.jpg";
                return true;
            case GID_LEVI_JUGGERNAUT:
                $img['path'] = "mods/DeepSpaceHorror/img/leviathan.jpg";
                return true;
        }
        return false;
    }

    private function CreateLeviathan (int $type) : int {

        global $GlobalUni;
        global $transportableResources;

        // Создать планету

        $gid = 0;
        switch ($type) {
            case PTYP_LEVI_AMOEBA:
                $gid = GID_LEVI_AMOEBA;
                break;
            case PTYP_LEVI_GUARDIAN:
                $gid = GID_LEVI_GUARDIAN;
                break;
            case PTYP_LEVI_JUGGERNAUT:
                $gid = GID_LEVI_JUGGERNAUT;
                break;
        }
        if ($gid == 0) return 0;

        $now = time();
        $name = loca ("PLANET_".$gid, $GlobalUni['lang']);

        $g = mt_rand (1, $GlobalUni['galaxies']);
        $s = mt_rand (1, $GlobalUni['systems']);
        $p = mt_rand (1, 15);

        $diam = 1000;
        $temp = 200;
        $fields = 1;

        $planet = array(
            'name' => $name, 'type' => $type, 'g' => $g, 's' => $s, 'p' => $p, 
            'owner_id' => USER_SPACE, 'diameter' => $diam, 'temp' => $temp, 'fields' => 0, 'maxfields' => $fields, 'date' => $now,
            'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
        $id = AddDBRow ( $planet, "planets" );
        $planet = LoadPlanetById ($id);         // reload

        // Запустить "флот"

        $seconds = 10000;
        $cons = 0;

        global $fleetmap;
        $fleet = array ();
        foreach ($fleetmap as $i=>$ship_id) {
            $fleet[$ship_id] = 0;
        } 
        $fleet[$gid] = 1;

        $resources = array ();
        foreach ($transportableResources as $i=>$rc) {
            $resources[$rc] = 0;
        }
        DispatchFleet ($fleet, $planet, $planet, FTYP_LEVI_PREPARE_JUMP, $seconds, $resources, $cons, $now);

        return $id;
    }

    private function IsPlanetLeviathan (int $type) : bool {
        switch ($type) {
            case PTYP_LEVI_AMOEBA:
            case PTYP_LEVI_GUARDIAN:
            case PTYP_LEVI_JUGGERNAUT:
                return true;
            default:
                break;
        }
        return false;
    }
}

?>