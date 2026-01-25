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
const SPACE_STORM_MASK_MSB = 10;            // The most significant bit for setting the storm type. The type is set as a random bit from 0 to the MSB (inclusive).

const GID_B_REALITY_STAB = 157384;      // Reality Stabilizer Object ID

const QTYP_SPACE_STORM = "SpaceStorm";

const SPACE_STORM_PERIOD_SECONDS = 60*60;

class SpaceStorm extends GameMod {

    public function install() : void {
        global $db_prefix;

        LockTables();

        // Add new columns
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

        global $GlobalUni;
        loca_add ("space_storm", $GlobalUni['lang'], __DIR__);
        BroadcastMessage (0, loca("STORM_STORM"), loca("STORM_SUBJ_ON"), loca("STORM_TEXT_ON") );

        UnlockTables();
    }

    public function uninstall() : void {
        global $db_prefix;

        LockTables();

        // Remove columns
        $query = "ALTER TABLE ".$db_prefix."uni DROP COLUMN storm;";
        dbquery ($query);        
        $query = "ALTER TABLE ".$db_prefix."planets DROP COLUMN `".GID_B_REALITY_STAB."`;";
        dbquery ($query);
        $query = "ALTER TABLE ".$db_prefix."planets DROP COLUMN `s".GID_B_REALITY_STAB."`;";
        dbquery ($query);

        // Delete Space Storm event
        $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_SPACE_STORM."'";
        dbquery ($query);

        BroadcastMessage (0, loca("STORM_STORM"), loca("STORM_SUBJ_OFF"), loca("STORM_TEXT_OFF") );

        UnlockTables();
    }

    public function install_tabs_included (array &$tabs) : bool {
        $tabs['uni']['storm'] = 'INT DEFAULT 0';
        $tabs['planets'][GID_B_REALITY_STAB] = 'INT DEFAULT 0';
        $tabs['planets']['s'.GID_B_REALITY_STAB] = 'INT DEFAULT 0';
        return false;
    }

    // Инициализировать глобальные таблицы фичами Космического шторма
    public function init() : void {
        global $buildmap;
        global $initial;
        global $requirements;
        global $CanBuildTab;

        // Add a new building to the game
        $buildmap[] = GID_B_REALITY_STAB;
        $initial[GID_B_REALITY_STAB] = array (GID_RC_METAL=>50000, GID_RC_CRYSTAL=>125000, GID_RC_DEUTERIUM=>50000, GID_RC_ENERGY=>0, 'factor'=>3);
        $requirements[GID_B_REALITY_STAB] = array (GID_B_RES_LAB=>3, GID_B_TERRAFORMER=>1);
        $CanBuildTab[PTYP_PLANET][] = GID_B_REALITY_STAB;

        global $GlobalUni;
        loca_add ("space_storm", $GlobalUni['lang'], __DIR__);
    }

    // Событие завершения Космического шторма. Формируется новый шторм, согласно правилам
    public function update_queue(array &$queue) : bool {
        global $db_prefix;
        if ($queue['type'] === QTYP_SPACE_STORM) {

            $prev = $this->GetStorm ();
            $storm = $this->NewStorm ($prev);
            $this->SetStorm ($storm);

            ProlongQueue ($queue['task_id'], SPACE_STORM_PERIOD_SECONDS);
            return true;
        }
        else {
            return false;
        }
    }

    // Вернуть картинку Стабилизатора реальности
    public function get_object_image(int $id, array &$img) : bool {
        if ($id == GID_B_REALITY_STAB) {
            $img['path'] = "mods/SpaceStorm/img/reality_stab.png";
            return true;
        }
        return false;
    }

    // Вывести картинку Космиического шторма в бонусную панель
    public function add_bonuses (array &$bonuses) : bool {

        global $db_prefix;

        // Получить тип шторма и таймстамп его окончания

        $storm = $this->GetStorm();

        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_SPACE_STORM."'";
        $result = dbquery ($query);
        $end = 0;
        if ($result != null) {
            $event = dbarray ($result);
            $end = $event['end'];
        }
        else {
            $storm = 0;
        }

        // Вернуть описание бонуса

        $storm_bonus = [];

        $img_fix = $storm == 0 ? "_un" : "";
        $storm_bonus['img'] = "mods/SpaceStorm/img/storm_ikon$img_fix.png";
        $storm_bonus['alt'] = loca ("STORM_STORM");

        $overlib = "";

        if ($storm != 0) {

            $now = time();
            $d = ($end - $now) / (60*60*24);
            if ($d < 1) {
                $hr = ($end - $now) / (60*60);
                $active = va(loca("PR_ACTIVE_HOURS"), ceil($hr));
            }
            else $active = va(loca("PR_ACTIVE_DAYS"), ceil($d));

            $overlib .= "<center><font size=1 color=white><b>".$active."<br>".loca ("STORM_STORM")."</font><br>";
            
            // Типы и описание шторма
            for ($i=0; $i<SPACE_STORM_MASK_MSB; $i++) {
                if ( ($storm & (1 << $i)) != 0 ) {
                    $overlib .= "<font size=1 color=skyblue>";
                    $overlib .= loca("STORM_" . $i);
                    $overlib .= "</font><br>";
                }
            }
        }
        else {

            $overlib .= "<center><font size=1 color=white><b>" . loca("STORM_NONE");
        }

        $overlib .= "</b></font></center>";
        $storm_bonus['overlib'] = $overlib;

        array_insert_before_key ($bonuses, 'commander', 'storm', $storm_bonus);
        return false;
    }

    // Проверка на возможность строительства Стабилизатора реальности (можно только во время шторма)
    public function can_build(array &$info) : bool {
        $storm = $this->GetStorm();
        if ($info['id'] == GID_B_REALITY_STAB && $storm == 0) {
            $info['result'] = loca ("STORM_REQUIRED");
            return true;
        }
        return false;
    }

    // Событие завершения строительства Стабилизатора реальности сопровождается установкой маски текущего шторма.
    // При сносе - маска наоборот сбрасывается.
    public function build_end(int $planet_id, array &$queue) : bool {
        global $db_prefix;
        $id = $queue['obj_id'];
        $storm = $this->GetStorm();
        if ($id == GID_B_REALITY_STAB && $storm != 0) {
            $demolish = $queue['type'] === QTYP_DEMOLISH;
            $planet = GetPlanet ( $planet_id );
            $mask = $planet['s'.GID_B_REALITY_STAB];
            if ($demolish) $mask &= ~$storm;
            else $mask |= $storm;
            $query = "UPDATE ".$db_prefix."planets SET `s".(GID_B_REALITY_STAB)."` = $mask WHERE planet_id = $planet_id";
            dbquery ($query);
        } 
        return false;
    }

    // Отобразить бонус Космического шторма для страницы Исследования (-2 шпионаж для Хроно-шпионский сбой)
    public function page_buildings_get_bonus(int $id, array &$bonuses) : bool {
        $storm = $this->GetStorm();
        if ($id == GID_R_ESPIONAGE && ($storm & SPACE_STORM_MASK_CHRONO_SPY) != 0) {
            $bonus = [];
            $bonus['value'] = "-2";
            $bonus['color'] = "red";
            $bonus['img'] = "mods/SpaceStorm/img/storm_ikon.png";
            $bonus['alt'] = loca("STORM_STORM");
            $bonus['descr'] = "<b>".loca("STORM_4") . "</b><br/>" . loca("STORM_DESC_4");
            $bonus['overlib_width'] = 200;

            $bonuses[] = $bonus;
        }
        return false;
    }

    // Применить бонус хроношпиоского сбоя в местах, где получается Шпионаж
    public function bonus_technology (int $id, array &$bonus) : bool {
        $storm = $this->GetStorm();
        if ($id == GID_R_ESPIONAGE && ($storm & SPACE_STORM_MASK_CHRONO_SPY) != 0) {
            $bonus['level'] -= 2;
        }
        return false;
    }

    private function NewStorm (int $prev_storm) : int {

        // Посчитать количество эффектов предыдущего шторма
        $count = $this->CountStormBits($prev_storm);

        // Если не было шторма (0 эффектов): 75% что будет слабый шторм (1 эффект)
        // Если был слабый шторм (1 эффект): 50% что будет средний шторм (2 эффекта), иначе - шторм пропадает (0 эффектов)
        // Если был средний шторм (2 эффекта): 25% что будет сильный шторм (3 эффекта), иначе: [50% что будет слабый шторм (1 эффект) или шторм пропадёт (0 эффектов)]
        // Если был сильный шторм (3 эффекта): 75% что шторм пропадёт, иначе: [25% что шторм ослабнет до слабого (1 эффект) или шторм пропадёт (0 эффектов)]

        $new_count = 0;         // default

        switch ($count) {

            case 0:
                if (mt_rand(1,100) <= 75) $new_count = 1;
                break;

            case 1:
                if (mt_rand(1,100) <= 50) $new_count = 2;
                break;

            case 2:
                if (mt_rand(1,100) <= 25) {
                    $new_count = 3;
                }
                else {
                    if (mt_rand(1,100) <= 50) $new_count = 1;
                }
                break;

            case 3:
            default:
                if (mt_rand(1,100) <= 75) {
                    $new_count = 0;
                }
                else {
                    if (mt_rand(1,100) <= 25) $new_count = 1;
                }
                break;
        }

        // Установить `new_count` новых штормов (установить случайные биты)

        $storm = 0;

        for ($n=0; $n<$new_count; $n++) {

            $mask = 0;
            while ($mask == 0) {
                $bitnum = mt_rand(0, SPACE_STORM_MASK_MSB-1);
                if ( ($storm & (1 << $bitnum)) == 0) {
                    $mask = 1 << $bitnum;
                    break;
                }
            }

            $storm |= $mask;
        }

        Debug ("prev_storm: $prev_storm ($count bits), new storm: $storm ($new_count bits)" );

        // Описание штормов, если активен (bb-код)
        $storm_desc = "";
        if ($new_count != 0) {
            for ($i=0; $i<SPACE_STORM_MASK_MSB; $i++) {
                if ( ($storm & (1 << $i)) != 0 ) {
                    $storm_desc .= "\n\n[b]" . loca("STORM_" . $i) . ":[/b]\n" . loca("STORM_DESC_" . $i);
                }
            }
        }

        if ($new_count == 0) {
            BroadcastMessage (0, loca("STORM_STORM"), loca("STORM_SUBJ_0"), loca("STORM_TEXT_0") );
        }
        else {
            if ($new_count > $count) BroadcastMessage (0, loca("STORM_STORM"), loca("STORM_SUBJ_INC"), loca("STORM_TEXT_INC") . $storm_desc );
            else BroadcastMessage (0, loca("STORM_STORM"), loca("STORM_SUBJ_DEC"), loca("STORM_TEXT_DEC") . $storm_desc );
        }

        return $storm;
    }

    private function GetStorm () : int {
        global $GlobalUni;
        return $GlobalUni['storm'];
    }

    private function SetStorm(int $storm) : void {

        global $db_prefix, $GlobalUni;
        $query = "UPDATE ".$db_prefix."uni SET storm = $storm;";
        dbquery ($query);
        $GlobalUni['storm'] = $storm;
    }

    private function CountStormBits (int $storm) : int {

        $count = 0;
        for ($i=0; $i<SPACE_STORM_MASK_MSB; $i++) {
            if ( ($storm & (1 << $i)) != 0) {
                $count++;
            }
        }
        return $count;
    }
}

?>