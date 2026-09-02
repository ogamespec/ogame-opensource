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

// Battle effects (global, applied in the battle frontend).
const SPACE_STORM_POLAR_ARMOR = 0.8;         // Polar Shield Distortion: armor -20%
const SPACE_STORM_POLAR_SHIELD = 1.3;        // Polar Shield Distortion: shields +30%
const SPACE_STORM_GRAV_SHIELD = 1.1;         // Gravitational Defense Anomaly: protective barrier +10%

// Reality Stabilizer counter-effect steps (per level).
const SPACE_STORM_POLAR_STAB_STEP = 0.03;    // Polar: reduce distortion by 3%/level
const SPACE_STORM_GRAV_STAB_STEP = 0.01;     // Grav: reduce barrier by 1%/level
const SPACE_STORM_TURB_STAB_SPEED = 0.03;    // Subspace Turbulence: +3% fleet speed/level
const SPACE_STORM_JUMP_STAB_DELTA = 0.4;     // Subspace Jump: jump chance -0.4%/level
const SPACE_STORM_JUMP_STAB_LOST = 0.08;     // Subspace Jump: lost chance -0.08%/level
const SPACE_STORM_QUANTUM_STAB_FUEL = 0.08;  // Quantum Drive: fuel penalty -8%/level
const SPACE_STORM_QUANTUM_STAB_PROD = 0.03;  // Quantum Drive: deuterium bonus +3%/level
const SPACE_STORM_CHRONO_STAB_DELAY = 0.4;   // Chrono-Spy: spy delay -0.4 min/level
const SPACE_STORM_ENERGY_STAB_PENALTY = 0.04;// Energy Collapse: energy penalty -4%/level
const SPACE_STORM_MATTER_STAB_STEP = 0.02;   // Matter Signature: conversion -2%/level
const SPACE_STORM_REVERB_STAB_STEP = 0.004;  // Attack Reverb: loss -0.4%/level

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

            // Энергетический Коллапс: при отрицательном балансе 10%/час заморозить постройку или исследование.
            $this->EnergyCollapseTick ();

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

    // Тик Энергетического Коллапса: проверяет баланс энергии планет и с шансом 10%/час
    // замораживает случайную постройку / исследование на планете с дефицитом энергии.
    // На планетах со Стабилизатором реальности постройки не отключаются.
    private function EnergyCollapseTick () : void {

        global $db_prefix, $GlobalUni;
        $storm = $this->GetStorm ();
        if (($storm & SPACE_STORM_MASK_ENERGY_COLLAPSE) == 0) return;

        $result = dbquery ("SELECT * FROM ".$db_prefix."planets WHERE type = ".PTYP_PLANET);
        if ($result == null) return;

        $rows = dbrows ($result);
        while ($rows--) {
            $planet = dbarray ($result);
            if ($planet['owner_id'] == USER_SPACE) continue;

            // Стабилизатор реальности защищает планету от отключений.
            if ($this->HasStabCounter($planet, SPACE_STORM_MASK_ENERGY_COLLAPSE)) continue;

            // Баланс энергии не хранится в БД. Вычислим его безопасно (без записи в БД)
            // с помощью ProdResources, который только заполняет переданный массив.
            $user = LoadUser ($planet['owner_id']);
            if ($user == null) continue;
            ProdResources ($GlobalUni, $user, $planet);

            $energy_gap = $planet['balance'][GID_RC_ENERGY] ?? 0;
            if ($energy_gap < 0 && mt_rand (1, 100) <= 10) {
                $this->FreezeRandomQueue ($planet['planet_id']);
            }
        }
    }

    // Заморозить случайную незамороженную постройку или исследование на планете.
    private function FreezeRandomQueue (int $planet_id) : void {

        global $db_prefix;
        $ids = [];

        // Постройки/снос: очередь строительства хранится в buildqueue (sub_id ссылается на buildqueue.id).
        $bresult = dbquery ("SELECT q.task_id FROM ".$db_prefix."queue q JOIN ".$db_prefix."buildqueue b ON q.sub_id = b.id ". 
                "WHERE q.type IN ('".QTYP_BUILD."','".QTYP_DEMOLISH."') AND b.planet_id = $planet_id AND q.freeze = 0");
        if ($bresult != null) {
            $n = dbrows ($bresult);
            while ($n--) { $row = dbarray ($bresult); $ids[] = $row['task_id']; }
        }

        // Исследование: sub_id = planet_id.
        $rresult = dbquery ("SELECT task_id FROM ".$db_prefix."queue WHERE type = '".QTYP_RESEARCH."' AND sub_id = $planet_id AND freeze = 0");
        if ($rresult != null) {
            $n = dbrows ($rresult);
            while ($n--) { $row = dbarray ($rresult); $ids[] = $row['task_id']; }
        }

        if (!count ($ids)) return;

        $task_id = $ids[mt_rand (0, count ($ids) - 1)];
        FreezeQueue ($task_id, true);
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
            while (true) {
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

    // Реальный уровень Стабилизатора реальности на планете.
    private function GetStabilizerLevel (array $planet) : int {
        return (int)($planet[GID_B_REALITY_STAB] ?? 0);
    }

    // Есть ли на планете запечатлённый контр-эффект соответствующего шторма
    // (маска штормов, при постройке которых планета получила Стабилизатор).
    private function HasStabCounter (array $planet, int $storm_mask) : bool {
        if ($this->GetStabilizerLevel($planet) <= 0) return false;
        return (($planet['s'.GID_B_REALITY_STAB] ?? 0) & $storm_mask) != 0;
    }

    private function GetStormQueue () : array|null {

        global $db_prefix;
        $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_SPACE_STORM."' LIMIT 1;";
        $result = dbquery ($query);
        if ($result == null) return null;
        return dbarray ($result);
    }

    public function add_db_row(array &$row, string $tabname) : bool {

        global $db_prefix;
        $storm = $this->GetStorm ();

        if ($tabname === 'queue' && $row['type'] === QTYP_FLEET) {

            $fleet_id = $row['sub_id'];
            $fleet_obj = LoadFleet ($fleet_id);

            // Если добавляется событие флота Шпионаж убывает И активен шторм хроно-шпионский сбой, то замедлить флот
            if ($fleet_obj && $fleet_obj['mission'] == FTYP_SPY && ($storm & SPACE_STORM_MASK_CHRONO_SPY) != 0) {

                $delay_seconds = mt_rand (SPACE_STORM_CHRONO_SPY_DELAY_MIN, SPACE_STORM_CHRONO_SPY_DELAY_MAX) * 60;

                // Стабилизатор реальности на целевой планете сокращает задержку отчётов.
                $target_planet = LoadPlanetById ($fleet_obj['target_planet']);
                if ($target_planet !== null && $this->HasStabCounter($target_planet, SPACE_STORM_MASK_CHRONO_SPY)) {
                    $level = $this->GetStabilizerLevel($target_planet);
                    $delay_seconds = max (0, $delay_seconds - (int)round(SPACE_STORM_CHRONO_STAB_DELAY * 60 * $level));
                }

                $row['end'] += $delay_seconds;
            }

            // Если флот улетает и активен эффект Прыжка, то либо с шансом 5% перебросить его либо с шансом 1% что он заблудится
            if ($fleet_obj && ($fleet_obj['mission'] <= FTYP_EXPEDITION || $fleet_obj['mission'] >= FTYP_CUSTOM) && ($storm & SPACE_STORM_MASK_SUBSPACE_JUMP) != 0) {

                /** @var bool $bool_test_jump */
                $bool_test_jump = false;
                /** @var bool $bool_test_loss */
                $bool_test_loss = false;

                // Учёт Стабилизатора реальности на стартовой планете (снижение шансов).
                $jump_chance = 5;
                $lost_chance = 1;
                $origin_planet = LoadPlanetById ($fleet_obj['start_planet']);
                if ($origin_planet !== null && $this->HasStabCounter($origin_planet, SPACE_STORM_MASK_SUBSPACE_JUMP)) {
                    $level = $this->GetStabilizerLevel($origin_planet);
                    $jump_chance = max (0, 5 - (int)round(SPACE_STORM_JUMP_STAB_DELTA * $level));
                    $lost_chance = max (0, 1 - (int)round(SPACE_STORM_JUMP_STAB_LOST * $level));
                }

                if ($bool_test_jump || mt_rand(1, 100) <= $jump_chance) {

                    $row['end'] = $row['start'];
                }
                else if ( ( $bool_test_loss || mt_rand(1, 100) <= $lost_chance) && $fleet_obj['mission'] <= FTYP_EXPEDITION) {    // кастомные флоты не умеем отзывать

                    $flight_time = mt_rand(60*60, 2*60*60);
                    $mission = $fleet_obj['mission'] + FTYP_RETURN;
                    $row['end'] = $row['start'] + $flight_time;

                    $query = "UPDATE ".$db_prefix."fleet SET flight_time = $flight_time, mission = $mission WHERE fleet_id = $fleet_id";
                    dbquery ($query);
                }
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
            SPACE_STORM_MASK_POLAR_SHIELD,
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
        $planet = $param['planet'] ?? [];

        // Базовый бонус Стабилизатора реальности: +0.5% выработки энергии за уровень.
        if ($param['rc'] == GID_RC_ENERGY) {
            $level = $this->GetStabilizerLevel($planet);
            if ($level > 0) $bonus[] = 1 + 0.005 * $level;
        }

        if ($param['rc'] == GID_RC_DEUTERIUM && ($storm & SPACE_STORM_MASK_QUANTUM_DRIVE) != 0) {
            $factor = 1 + SPACE_STORM_QUANTUM_DRIVE_BASE_BONUS;
            if ($this->HasStabCounter($planet, SPACE_STORM_MASK_QUANTUM_DRIVE)) {
                $factor += SPACE_STORM_QUANTUM_STAB_PROD * $this->GetStabilizerLevel($planet);
            }
            $bonus[] = $factor;
        }
        if ($param['rc'] == GID_RC_ENERGY && ($storm & SPACE_STORM_MASK_ENERGY_COLLAPSE) != 0) {
            $penalty = SPACE_STORM_ENERGY_COLLAPSE_BASE_PENALTY;
            if ($this->HasStabCounter($planet, SPACE_STORM_MASK_ENERGY_COLLAPSE)) {
                $penalty = max (0.0, $penalty - SPACE_STORM_ENERGY_STAB_PENALTY * $this->GetStabilizerLevel($planet));
            }
            $bonus[] = 1 - $penalty;
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

            // Стабилизатор реальности снижает процент конверсии на этой планете.
            $bonus_rate = SPACE_STORM_MATTER_SIGNATURE_BASE_BONUS;
            if ($this->HasStabCounter($planet, SPACE_STORM_MASK_MATTER_SIGNATURE)) {
                $bonus_rate = max (0.0, $bonus_rate - SPACE_STORM_MATTER_STAB_STEP * $this->GetStabilizerLevel($planet));
            }

            if ($bonus_rate > 0) {
                foreach ($resourcesWithNonZeroDerivative as $i=>$rc) {

                    if ($rc != $res_id && isset($eco['net_prod'][$res_id])) {

                        $converted = $eco['net_prod'][$rc] * $bonus_rate;

                        $eco['net_prod'][$res_id] += $converted;
                        $eco['balance'][$res_id] += $converted;
                        $eco['net_prod'][$rc] -= $converted;
                        $eco['balance'][$rc] -= $converted;
                    }
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

        // Учёт Стабилизатора реальности на защищаемой планете (снижение потерь атакующего).
        $loss_rate = 0.05;
        $planet = $this->GetBattleDefendedPlanet ($res['before']);
        if ($planet !== null && $this->HasStabCounter($planet, SPACE_STORM_MASK_ATTACK_REVERB)) {
            $level = $this->GetStabilizerLevel($planet);
            $loss_rate = max (0.0, 0.05 - SPACE_STORM_REVERB_STAB_STEP * $level);
        }

        $reverb_losses = [];
        $total_units_lost = 0;
        $units_lost = 0;

        $rounds = count($res['rounds']);
        if ($rounds > 0) {

            $last = $res['rounds'][$rounds - 1];
            foreach ($last['attackers'] as $i=>$attacker) {
                foreach ($attacker['units'] as $gid=>$count) {
                    $after = (int)ceil($count * (1 - $loss_rate));
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

    // Глобальные боевые эффекты Шторма (и их контр-эффекты Стабилизатора).
    // Вызывается из фронтенда боевого движка (GenBattleSourceData) до сериализации
    // базовых статов юнитов, поэтому меняет только данные этого боя.
    public function battle_unit_stats(array $args, array &$unit_param) : bool {

        $storm = $this->GetStorm ();
        if ($storm == 0) return false;

        $armor_factor = 1.0;
        $shield_factor = 1.0;

        // Полярное Искажение Щитов: броня -20%, щиты +30%.
        if (($storm & SPACE_STORM_MASK_POLAR_SHIELD) != 0) {
            $armor_factor *= SPACE_STORM_POLAR_ARMOR;
            $shield_factor *= SPACE_STORM_POLAR_SHIELD;
        }
        // Гравитационная Аномалия Защиты: защитный барьер +10% к щитам.
        if (($storm & SPACE_STORM_MASK_GRAV_DEFENSE) != 0) {
            $shield_factor *= SPACE_STORM_GRAV_SHIELD;
        }

        // Учёт Стабилизатора реальности на защищаемой планете.
        $planet = $this->GetBattleDefendedPlanet ($args);
        if ($planet !== null) {
            if (($storm & SPACE_STORM_MASK_POLAR_SHIELD) != 0 && $this->HasStabCounter($planet, SPACE_STORM_MASK_POLAR_SHIELD)) {
                $level = $this->GetStabilizerLevel($planet);
                $armor_factor += SPACE_STORM_POLAR_STAB_STEP * $level;
                $shield_factor -= SPACE_STORM_POLAR_STAB_STEP * $level;
            }
            if (($storm & SPACE_STORM_MASK_GRAV_DEFENSE) != 0 && $this->HasStabCounter($planet, SPACE_STORM_MASK_GRAV_DEFENSE)) {
                $level = $this->GetStabilizerLevel($planet);
                $shield_factor -= SPACE_STORM_GRAV_STAB_STEP * $level;
            }
        }

        if ($armor_factor != 1.0 || $shield_factor != 1.0) {
            foreach ($unit_param as $gid=>$p) {
                $unit_param[$gid][0] *= $armor_factor;    // броня
                $unit_param[$gid][1] *= $shield_factor;   // щиты
            }
        }

        return false;
    }

    // Найти защищаемую планету из контейнера с участниками боя.
    private function GetBattleDefendedPlanet (array $container) : ?array {
        if (!isset($container['defenders']) || !is_array($container['defenders'])) return null;
        foreach ($container['defenders'] as $defender) {
            if (($defender['pf'] ?? null) == BATTLE_PTCP_PLANET && !empty($defender['id'])) {
                $planet = LoadPlanetById ($defender['id']);
                if ($planet !== null) return $planet;
            }
        }
        return null;
    }

    // Увеличить затраты топлива для Квантовая Нестабильность Двигателей
    public function bonus_fleet_cons (array $param, array &$bonus) : bool {

        $storm = $this->GetStorm ();

        if (($storm & SPACE_STORM_MASK_QUANTUM_DRIVE) != 0) {
            $mult = 2.0;
            if ($this->HasStabCounter($param['planet'], SPACE_STORM_MASK_QUANTUM_DRIVE)) {
                $level = $this->GetStabilizerLevel($param['planet']);
                $mult = max (1.0, 2.0 - SPACE_STORM_QUANTUM_STAB_FUEL * $level);
            }
            $bonus['value'] *= $mult;
        }

        return false;
    }

    public function bonus_fleet_speed (array $param, array &$bonus) : bool {

        $storm = $this->GetStorm ();

        if (($storm & SPACE_STORM_MASK_SUBSPACE_TURB) != 0) {

            $penalty = mt_rand (SPACE_STORM_SUBSPACE_TURB_PENALTY_MIN, SPACE_STORM_SUBSPACE_TURB_PENALTY_MAX) / 100;
            $bonus['value'] *= 1 - $penalty;

            // Стабилизатор реальности ускоряет флоты, отправленные с этой планеты,
            // компенсируя часть глобального замедления (активен во время шторма).
            if (isset($param['planet']) && $this->HasStabCounter($param['planet'], SPACE_STORM_MASK_SUBSPACE_TURB)) {
                $level = $this->GetStabilizerLevel($param['planet']);
                $bonus['value'] *= 1 + SPACE_STORM_TURB_STAB_SPEED * $level;
            }
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

                // Стабилизатор реальности на стартовой планете позволяет Транспорт, несмотря на шторм.
                if (!$this->HasStabCounter($origin, SPACE_STORM_MASK_COMM_BREAKDOWN)) {
                    $key = array_search(FTYP_TRANSPORT, $missions);
                    if ($key !== false) {
                        unset ($missions[$key]);
                    }
                }
            }
        }

        return false;
    }
}

?>