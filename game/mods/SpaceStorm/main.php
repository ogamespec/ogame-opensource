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

const GID_B_REALITY_STAB = 57384;      // Reality Stabilizer Object ID

const QTYP_SPACE_STORM = "SpaceStorm";

const SPACE_STORM_PERIOD_SECONDS = 60*60;

const SPACE_STORM_CHRONO_SPY_DELAY_MIN = 1;
const SPACE_STORM_CHRONO_SPY_DELAY_MAX = 5;
const SPACE_STORM_MATTER_SIGNATURE_BASE_BONUS = 0.2;
const SPACE_STORM_QUANTUM_DRIVE_BASE_BONUS = 0.25;
const SPACE_STORM_ENERGY_COLLAPSE_BASE_PENALTY = 0.4;
const SPACE_STORM_SUBSPACE_TURB_PENALTY_MIN = 30;
const SPACE_STORM_SUBSPACE_TURB_PENALTY_MAX = 50;

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

        global $GlobalUser;
        loca_add ("space_storm", $GlobalUser['lang'], __DIR__);
    }

    // Событие завершения Космического шторма. Формируется новый шторм, согласно правилам
    public function update_queue(array &$queue) : bool {
        global $db_prefix;
        global $resourcesWithNonZeroDerivative;
        if ($queue['type'] === QTYP_SPACE_STORM) {

            $prev = $this->GetStorm ();
            $storm = $this->NewStorm ($prev);
            $this->SetStorm ($storm);

            ProlongQueue ($queue['task_id'], SPACE_STORM_PERIOD_SECONDS);

            // Для Сигнатуры Материи нужно выбрать тип ресурса, в который переносится всё производство
            $res_types = count ($resourcesWithNonZeroDerivative);
            if ($res_types) {
                $idx = mt_rand (0, $res_types - 1);
                $obj_id = $resourcesWithNonZeroDerivative[$idx];
                $query = "UPDATE ".$db_prefix."queue SET obj_id=$obj_id WHERE task_id = ".$queue['task_id'];
                dbquery ($query);
            }

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
            $planet = LoadPlanetById ( $planet_id );
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

    public function page_infos(int $id, array &$planet) : bool {
        global $GlobalUser;
        if ($id == GID_B_REALITY_STAB && $planet[GID_B_REALITY_STAB] > 0) {

            echo "<tr><th><p><center><table border=1 ><tr><td class='c'>".loca("STORM_STORM")."</td><td class='c'>".loca("NAME_".GID_B_REALITY_STAB)."</td></tr> \n";

            $storm_now = $this->GetStorm();
            $storm_mask = $planet['s'.GID_B_REALITY_STAB];
            for ($i=0; $i<SPACE_STORM_MASK_MSB; $i++) {
                if ( ($storm_mask & (1 << $i)) != 0 ) {
                    echo "<tr>";
                    echo "<th>";
                    $color = ($storm_now & (1 << $i)) != 0 ? "lime" : "red";
                    echo "<font style='color:$color'>";
                    echo loca("STORM_".$i);
                    echo "</font>";
                    echo "</th><th>".loca("STORM_STAB_".$i)."</th></tr>\n";
                }
            }

            echo "</table></center></tr></th>";
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

        // Описание штормов, если активен
        $storm_desc = "";
        if ($new_count != 0) {
            for ($i=0; $i<SPACE_STORM_MASK_MSB; $i++) {
                if ( ($storm & (1 << $i)) != 0 ) {
                    $storm_desc .= "<br/><br/><b>" . loca("STORM_" . $i) . ":</b><br/>" . loca("STORM_DESC_" . $i);
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

    private function GetStabLevelMask (int $planet_id, int &$level, int &$mask) : void {
        $level = $mask = 0;
        $planet = LoadPlanetById ($planet_id);
        if ($planet == null) return;
        if ($planet['type'] == PTYP_MOON) {
            $planet = LoadPlanet ($planet['g'], $planet['s'], $planet['p'], 1);
            if ($planet == null) return;
        }
        if ($planet['type'] != PTYP_PLANET) return;
        $level = $planet[GID_B_REALITY_STAB];
        $mask = $planet['s'.GID_B_REALITY_STAB];
    }

    private function GetStormQueue () : array|null {

        global $db_prefix;
        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_SPACE_STORM."' LIMIT 1;";
        $result = dbquery ($query);
        if ($result == null) return null;
        return dbarray ($result);
    }

    public function add_db_row(array &$row, string $tabname) : bool {

        $storm = $this->GetStorm ();

        // Если добавляется событие флота Шпионаж убывает И активен шторм хроно-шпионский сбой, то замедлить флот
        if ($tabname === 'queue' && $row['type'] === QTYP_FLEET) {

            $fleet_id = $row['sub_id'];
            $fleet_obj = LoadFleet ($fleet_id);

            if ($fleet_obj && $fleet_obj['mission'] == FTYP_SPY && ($storm & SPACE_STORM_MASK_CHRONO_SPY) != 0) {

                $delay_seconds = mt_rand (SPACE_STORM_CHRONO_SPY_DELAY_MIN, SPACE_STORM_CHRONO_SPY_DELAY_MAX) * 60;
                $row['end'] += $delay_seconds;
            }
        }

        return false;
    }

    // Отобразить (анти)бонусы Шторма на странице Флот 1 (для флотов)
    public function page_flotten1_get_bonus(array $param, array &$bonuses) : bool {

        // Эффекты шторма, которые можно отобразить на странице отправки флота
        $storm_fleet_bonus = [ 
            SPACE_STORM_MASK_SUBSPACE_TURB,
            SPACE_STORM_MASK_SUBSPACE_JUMP,
            SPACE_STORM_MASK_QUANTUM_DRIVE,
            SPACE_STORM_MASK_CHRONO_SPY,
            SPACE_STORM_MASK_COMM_BREAKDOWN,
        ];

        $this->GetStormBonuses ($storm_fleet_bonus, $bonuses);

        return false;
    }

    public function page_overview_get_bonus (array $param, array &$bonuses) : bool {

        // Эффекты шторма которые можно отобразить на странице Обзор
        $storm_overview_bonus = [
            SPACE_STORM_MASK_GRAV_DEFENSE,
            SPACE_STORM_MASK_ATTACK_REVERB,
        ];

        $this->GetStormBonuses ($storm_overview_bonus, $bonuses);

        return false;
    }

    public function page_resources_get_bonus (array $param, array &$bonuses) : bool {

        $storm = $this->GetStorm ();

        // Эффекты шторма которые можно отобразить в меню Сырьё
        $storm_resources_bonus = [];

        if ($param['rc'] == GID_RC_DEUTERIUM && $param['produce']) {
            $storm_resources_bonus[] = SPACE_STORM_MASK_QUANTUM_DRIVE;
        }
        if ($param['rc'] == GID_RC_ENERGY && $param['produce']) {
            $storm_resources_bonus[] = SPACE_STORM_MASK_ENERGY_COLLAPSE;
        }
        if (($storm & SPACE_STORM_MASK_MATTER_SIGNATURE) != 0 && $param['produce']) {

            $queue = $this->GetStormQueue ();
            if ($queue) {
                $res_id = $queue['obj_id'];
                if ($param['rc'] == $res_id) {
                    $storm_resources_bonus[] = SPACE_STORM_MASK_MATTER_SIGNATURE;    
                }
            }
        }

        $this->GetStormBonuses ($storm_resources_bonus, $bonuses);

        return false;
    }

    private function GetStormBonuses (array $storm_bonus_list, array &$bonuses) : void {

        $storm = $this->GetStorm ();

        for ($i=0; $i<SPACE_STORM_MASK_MSB; $i++) {

            $mask = 1 << $i;
            if (!in_array($mask, $storm_bonus_list, true)) continue;

            if (($storm & $mask) != 0) {

                $bonus = [];
                $bonus['color'] = "";
                $bonus['text'] = "";
                $bonus['alt'] = loca("STORM_STORM");
                $bonus['img'] = "mods/SpaceStorm/img/storm_ikon.png";
                $bonus['overlib'] = "<font color=white><b>".loca("STORM_$i") . "</b><br/>" . loca("STORM_DESC_$i") . "</font>";
                $bonus['width'] = 200;

                $bonuses[] = $bonus;
            }
        }
    }

    // Невизуальный бонус выработки ресурсов для эффектов шторма
    public function bonus_prod (array $param, array &$bonus) : bool {

        $storm = $this->GetStorm ();

        if ($param['rc'] == GID_RC_DEUTERIUM && ($storm & SPACE_STORM_MASK_QUANTUM_DRIVE) != 0) {
            $bonus[] = 1 + SPACE_STORM_QUANTUM_DRIVE_BASE_BONUS;
        }
        if ($param['rc'] == GID_RC_ENERGY && ($storm & SPACE_STORM_MASK_ENERGY_COLLAPSE) != 0) {
            $bonus[] = 1 - SPACE_STORM_ENERGY_COLLAPSE_BASE_PENALTY;
        }

        return false;
    }

    // Пост-процессинг для эффекта Сигнатура Материи (конвертирует выработку всех ресурсов в определённый тип)
    public function prod_post_process (array &$planet, array &$eco) : bool {

        global $resourcesWithNonZeroDerivative;
        $storm = $this->GetStorm ();

        if (($storm & SPACE_STORM_MASK_MATTER_SIGNATURE) != 0) {

            $queue = $this->GetStormQueue ();
            if ($queue == null) return false;
            $res_id = $queue['obj_id'];

            foreach ($resourcesWithNonZeroDerivative as $i=>$rc) {

                if ($rc != $res_id && isset($eco['net_prod'][$res_id])) {

                    $converted = $eco['net_prod'][$rc] * SPACE_STORM_MATTER_SIGNATURE_BASE_BONUS;

                    $eco['net_prod'][$res_id] += $converted;
                    $eco['balance'][$res_id] += $converted;
                    $eco['net_prod'][$rc] -= $converted;
                    $eco['balance'][$rc] -= $converted;
                }
            }
        }

        return false;
    }

    // Применить эффект Реверберации Атаки на планете.
    public function battle_post_process (array &$res) : bool {

        global $GlobalUni;
        $storm = $this->GetStorm ();

        if (($storm & SPACE_STORM_MASK_ATTACK_REVERB) == 0) return false;
        if ($res['result'] !== "awon" ) return false;

        $reverb_losses = [];
        $total_units_lost = 0;

        $rounds = count($res['rounds']);
        if ($rounds > 0) {

            $last = $res['rounds'][$rounds - 1];
            foreach ($last['attackers'] as $i=>$attacker) {
                foreach ($attacker['units'] as $gid=>$count) {
                    $after = (int)ceil($count * 0.95);
                    $res['rounds'][$rounds-1]['attackers'][$i]['units'][$gid] = $after;
                    $units_lost = $count - $after;
                    if (isset($reverb_losses[$gid])) $reverb_losses[$gid] += $units_lost;
                    else $reverb_losses[$gid] = $units_lost;
                    $total_units_lost += $units_lost;
                }
            }
        }

        if ($units_lost) {

            loca_add ( "technames", $GlobalUni['lang'] );
            loca_add ( "space_storm", $GlobalUni['lang'], __DIR__);

            $text = loca_lang ("STORM_BATTLE_REVERB_LOSS", $GlobalUni['lang']) . ": ";
            $need_comma = false;
            foreach ($reverb_losses as $gid=>$count) {
                if ($need_comma) $text .= ", ";
                $text .= $count . " " . loca_lang ("NAME_$gid", $GlobalUni['lang']);
                $need_comma = true;
            }
            $res['extra'][] = $text;
        }

        return false;
    }

    // Увеличить затраты топлива для Квантовая Нестабильность Двигателей
    public function bonus_fleet_cons (array $param, array &$bonus) : bool {

        $storm = $this->GetStorm ();

        if (($storm & SPACE_STORM_MASK_QUANTUM_DRIVE) != 0) {
            $bonus['value'] *= 2;
        }

        return false;
    }

    public function bonus_fleet_speed (array $param, array &$bonus) : bool {

        $storm = $this->GetStorm ();

        if (($storm & SPACE_STORM_MASK_SUBSPACE_TURB) != 0) {

            $penalty = mt_rand (SPACE_STORM_SUBSPACE_TURB_PENALTY_MIN, SPACE_STORM_SUBSPACE_TURB_PENALTY_MAX) / 100;
            $bonus['value'] *= 1 - $penalty;
        }

        return false;
    }

    // Запретить Транспорт для полёта на свои планеты при Провал в Связи
    public function fleet_available_missions (array $param, array &$missions) : bool {

        $storm = $this->GetStorm ();

        if (($storm & SPACE_STORM_MASK_COMM_BREAKDOWN) != 0) {

            $origin = LoadPlanet ( $param['thisgalaxy'], $param['thissystem'], $param['thisplanet'], $param['thisplanettype'] );
            if ($origin == null) return false;
            $origin_user = LoadUser ($origin['owner_id']);
            if ($origin_user == null) return false;

            $target = LoadPlanet ( $param['galaxy'], $param['system'], $param['planet'], $param['planettype'] );
            if ($target == null) return false;
            $target_user = LoadUser ($target['owner_id']);
            if ($target_user == null) return false;

            if ($target_user['player_id'] == $origin_user['player_id']) {

                $key = array_search(FTYP_TRANSPORT, $missions);
                if ($key !== false) {
                    unset ($missions[$key]);
                }
            }
        }

        return false;
    }
}

?>