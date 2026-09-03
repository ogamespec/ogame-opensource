<?php

// Deep Space Horror

const PTYP_LEVI_PORTAL = 22848;         // Портал Бездны (точка выхода)
// Планета - Космический монстр
const PTYP_LEVI_AMOEBA = 22849;
const PTYP_LEVI_GUARDIAN = 22850;
const PTYP_LEVI_JUGGERNAUT = 22851;

const GID_LEVI_AMOEBA = 22852;          // Planktonic Devourer
const GID_LEVI_GUARDIAN = 22853;        // Wandering Monolith
const GID_LEVI_JUGGERNAUT = 22854;      // Galactic Juggernaut 

// Подготовка к прыжку. После этого происходит перемещение левифана, специальная атака и новая подготовка.
const FTYP_LEVI_PREPARE_JUMP = 22855;

const LEVI_DIAMETER = 1000;          // Диаметр левиафана
const LEVI_TEMP = 200;          // Температура левиафана

const LEVI_PORTAL_DIAMETER = 1000;          // Диаметр портала
const LEVI_PORTAL_TEMP = -200;          // Температура портала

// Очередное событие мода: возрождение убитого левиафана (тип события в таблице queue).
const QTYP_LEVI_RESPAWN = "DeepSpaceHorror";

// Задержка возрождения убитого левиафана: 24-72 реальных часа.
const LEVI_RESPAWN_MIN_SECONDS = 24 * 60 * 60;
const LEVI_RESPAWN_MAX_SECONDS = 72 * 60 * 60;

// Трофеи с убитых чудовищ (дизайн мода: Амёба роняет дейтерий, Страж - кристалл, Левиафан - металл).
const LEVI_LOOT_AMOEBA_DEUTERIUM = 2500000;
const LEVI_LOOT_GUARDIAN_CRYSTAL = 10000000;
const LEVI_LOOT_JUGGERNAUT_METAL = 40000000;

class DeepSpaceHorror extends GameMod {

    public function install() : void {

        global $db_prefix;

        LockTables ();

        // Добавить колонки для флота +3 новых юнита. Для планет НЕ надо добавлять, т.к. левиафаны никогда не садятся на планету.

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

        // Принудительно вызвать init, требуются параметры левиафанов для их респауна

        $this->init();

        global $GlobalUni;
        loca_add ("leviathans", $GlobalUni['lang'], __DIR__);

        // Респаунить левиафанов

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
            // Удалить портал (точку выхода)
            $target = LoadPlanetById ($fleet_obj['target_planet']);
            if ($target && $target['type'] == PTYP_LEVI_PORTAL) {
                DestroyPlanet ($target['planet_id']);
            }
        }

        // Удалить планеты без флотов, если вдруг мод баганул в процессе.
        $query = "DELETE FROM ".$db_prefix."planets WHERE type IN (".PTYP_LEVI_PORTAL.", ".PTYP_LEVI_AMOEBA.", ".PTYP_LEVI_GUARDIAN.", ".PTYP_LEVI_JUGGERNAUT.");";
        dbquery ($query);

        // Удалить отложенные события возрождения левиафанов.
        $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_LEVI_RESPAWN."';";
        dbquery ($query);

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
        global $fleetmap, $UnitParam, $RapidFire, $requirements;

        // Добавить новые юниты в игру. TODO: Таблицы придумывала нейросеть, скорее всего потребуют подстройки.

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

        // We'll add Leviathans to the tech tree, but they still won't be able to be built on the planet, 
        // since there are no corresponding columns in the database for the planet object.
        $requirements[GID_LEVI_AMOEBA] = [];
        $requirements[GID_LEVI_GUARDIAN] = [];
        $requirements[GID_LEVI_JUGGERNAUT] = [];

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
            case PTYP_LEVI_PORTAL:
                $img['path'] = "mods/DeepSpaceHorror/img/portal.jpg";
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

        // Создать "планеты"

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
        $origin_name = loca_lang ("PLANET_".$type, $GlobalUni['lang']);

        if ($type == PTYP_LEVI_GUARDIAN) {

            // Страж начинает с начала вселенной
            $g = $s = $p = 1;
        }
        else {

            $g = $this->Rnd (1, $GlobalUni['galaxies']);
            $s = $this->Rnd (1, $GlobalUni['systems']);
            $p = $this->Rnd (1, 15);
        }

        $origin = array(
            'name' => $origin_name, 'type' => $type, 'g' => $g, 's' => $s, 'p' => $p, 
            'owner_id' => USER_SPACE, 'diameter' => LEVI_DIAMETER, 'temp' => LEVI_TEMP, 'fields' => 0, 'maxfields' => 0, 'date' => $now,
            'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
        $id = AddDBRow ( $origin, "planets" );
        $origin = LoadPlanetById ($id);         // reload

        // Портал (точка выхода)

        $coords = $this->DeterminePortalCoords ($gid, $origin);

        $target_name = loca_lang ("PLANET_".PTYP_LEVI_PORTAL, $GlobalUni['lang']);

        $target = array(
            'name' => $target_name, 'type' => PTYP_LEVI_PORTAL, 'g' => $coords['g'], 's' => $coords['s'], 'p' => $coords['p'], 
            'owner_id' => USER_SPACE, 'diameter' => LEVI_PORTAL_DIAMETER, 'temp' => LEVI_PORTAL_TEMP, 'fields' => 0, 'maxfields' => 0, 'date' => $now,
            'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
        $id = AddDBRow ( $target, "planets" );
        $target = LoadPlanetById ($id);         // reload

        // Запустить "флот"
        return $this->DispatchLeviathan ($gid, $origin, $target, $now, 1);
    }

    private function DeterminePortalCoords (int $gid, array $origin) : array {

        global $GlobalUni;

        $coords = [];

        switch ($gid) {
                // При обновлении координат с вероятностью 70% меняет только Позицию (P) в пределах 1-15, с вероятностью 25% меняет Систему (S) в пределах ±5 от текущей, 
                // и с вероятностью 5% меняет Галактику (G) на ±1
                case GID_LEVI_AMOEBA:
                    $coords['g'] = $origin['g'];
                    $coords['s'] = $origin['s'];
                    if ($this->Rnd(1, 100) <= 70) {
                        $coords['p'] = $this->Rnd (1, 15);
                    }
                    else {
                        // Каждый вызов Rnd() - независимый бросок; phpstan ошибочно
                        // мемоизирует вызовы с одинаковыми аргументами.
                        if ($this->Rnd(1, 100) <= 5) { // @phpstan-ignore smallerOrEqual.alwaysFalse
                            $coords['g'] = $origin['g'] + $this->Rnd (-1, +1);
                            $coords['g'] = max (1, min($coords['g'], $GlobalUni['galaxies']));
                        }
                        if ($this->Rnd(1, 100) <= 25) { // @phpstan-ignore smallerOrEqual.alwaysFalse
                            $coords['s'] = $origin['s'] + $this->Rnd (-5, +5);
                            $coords['s'] = max (1, min($coords['s'], $GlobalUni['systems']));
                        }
                        $coords['p'] = $this->Rnd (1, 15);
                    }
                    break;
                // Движется по спирали. Начинает с края галактики (например, G=1, S=1, P=1). 
                // Сначала проходит все Позиции (P) в системе, затем переходит на следующую Систему (S).
                // Дойдя до конца галактики (напр., S=499), увеличивает Галактику (G) на 1 и начинает движение в обратном направлении по системам (с 499 до 1).
                case GID_LEVI_GUARDIAN:
                    $retrograde = $origin['g'] % 2 != 0;    // было в обратном направлении?
                    $coords['g'] = $origin['g'];
                    $coords['s'] = $origin['s'];
                    $coords['p'] = $origin['p'] + 1;
                    if ($coords['p'] > 15) {
                        $coords['p'] = 1;
                        $coords['s'] += $retrograde ? -1 : +1;
                    }
                    if ($coords['s'] > $GlobalUni['systems'] || $coords['s'] < 1) {
                        $coords['g']++;
                        if ($coords['g'] > $GlobalUni['galaxies']) {
                            $coords['g'] = 1;
                        }
                        $retrograde = $coords['g'] % 2 != 0;    // стало в обратном направлении?
                        $coords['s'] = $retrograde ? $GlobalUni['systems'] : 1;
                    }
                    break;
                // При обновлении координат с вероятностью 60% совершает "прыжок" в случайную Галактику (G) в пределах вселенной. 
                // Оказавшись в галактике, он выбирает случайную Систему (S) в её центре (например, в диапазоне 100-400) и случайную Позицию (P)
                case GID_LEVI_JUGGERNAUT:
                    $coords['g'] = $origin['g'];
                    if ($this->Rnd(1, 100) <= 60) {
                        $coords['g'] = $this->Rnd (1, $GlobalUni['galaxies']);
                    }
                    $delta = (int)($GlobalUni['systems'] / 4);
                    $center = (int)($GlobalUni['systems'] / 2);
                    $coords['s'] = $this->Rnd ($center - $delta, $center + $delta);
                    $coords['s'] = max (1, min($coords['s'], $GlobalUni['systems']));
                    $coords['p'] = $this->Rnd (1, 15);
                    break;
        }

        return $coords;
    }

    private function DispatchLeviathan (int $gid, array $origin, array $target, int $when, int $count) : int {

        global $GlobalUni;
        global $fleetmap;
        global $transportableResources;

        $fleet = array ();
        foreach ($fleetmap as $i=>$ship_id) {
            $fleet[$ship_id] = 0;
        } 
        $fleet[$gid] = $count;

        // Выбрать уровни двигателей
        // Уровни двигаталей левиафанов зависят от топ1 игрока.

        $top1 = GetTop1 ();
        if ($top1 == null) {
            $top1 = LoadUser (USER_SPACE);
        }
        if ($top1 == null) {
            return 0;
        }

        switch ($gid) {
            case GID_LEVI_AMOEBA:
                $top1[GID_R_COMBUST_DRIVE] = max (0, $top1[GID_R_COMBUST_DRIVE] - 2);
                $top1[GID_R_IMPULSE_DRIVE] = max (0, $top1[GID_R_IMPULSE_DRIVE] - 2);
                $top1[GID_R_HYPER_DRIVE] = max (0, $top1[GID_R_HYPER_DRIVE] - 2);
                break;
            case GID_LEVI_GUARDIAN:
                $top1[GID_R_COMBUST_DRIVE] = max (0, $top1[GID_R_COMBUST_DRIVE] - 1);
                $top1[GID_R_IMPULSE_DRIVE] = max (0, $top1[GID_R_IMPULSE_DRIVE] - 1);
                $top1[GID_R_HYPER_DRIVE] = max (0, $top1[GID_R_HYPER_DRIVE] - 1);
                break;
            case GID_LEVI_JUGGERNAUT:
                break;
        }

        $dist = FlightDistance ($origin['g'], $origin['s'], $origin['p'], $target['g'], $target['s'], $target['p']);
        $speed = FlightSpeed ($fleet, $top1, $origin);
        $seconds = FlightTime ($dist, $speed, 1.0, $GlobalUni['fspeed']);
        $cons = 0;

        $resources = array ();
        foreach ($transportableResources as $i=>$rc) {
            $resources[$rc] = 0;
        }
        $id = DispatchFleet ($fleet, $origin, $target, FTYP_LEVI_PREPARE_JUMP, $seconds, $resources, $cons, $when);

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

    /**
     * Возвращает случайное целое число из диапазона [min, max] включительно.
     *
     * Вынесено в отдельный метод, чтобы юнит-тесты могли подменить его
     * детерминированной реализацией и проверять ветки случайных алгоритмов.
     */
    protected function Rnd (int $min, int $max) : int {
        return mt_rand ($min, $max);
    }

    /**
     * Сопоставляет идентификатор юнита-левиафана (GID) типу объекта галактики (PTYP).
     */
    private function LeviTypeFromGid (int $gid) : int {
        switch ($gid) {
            case GID_LEVI_AMOEBA: return PTYP_LEVI_AMOEBA;
            case GID_LEVI_GUARDIAN: return PTYP_LEVI_GUARDIAN;
            case GID_LEVI_JUGGERNAUT: return PTYP_LEVI_JUGGERNAUT;
        }
        return 0;
    }

    /**
     * Проверяет, существует ли в данный момент левиафан указанного типа.
     */
    private function LeviathanExists (int $type) : bool {
        global $db_prefix;
        $result = dbquery ("SELECT planet_id FROM ".$db_prefix."planets WHERE type = $type LIMIT 1;");
        return dbrows ($result) > 0;
    }

    /**
     * Трофей с убитого левиафана: ресурс и его количество (дизайн мода).
     */
    private function GetLeviLoot (int $gid) : array {
        switch ($gid) {
            case GID_LEVI_AMOEBA: return array ( GID_RC_DEUTERIUM => LEVI_LOOT_AMOEBA_DEUTERIUM );
            case GID_LEVI_GUARDIAN: return array ( GID_RC_CRYSTAL => LEVI_LOOT_GUARDIAN_CRYSTAL );
            case GID_LEVI_JUGGERNAUT: return array ( GID_RC_METAL => LEVI_LOOT_JUGGERNAUT_METAL );
        }
        return array ();
    }

    /**
     * Планирует возрождение убитого левиафана через 24-72 реальных часа.
     *
     * Событие одноразовое: когда оно сработает, мод создаст нового левиафана
     * в случайной точке вселенной (см. update_queue).
     */
    private function ScheduleRespawn (int $gid, int $when) : void {
        global $db_prefix;
        $type = $this->LeviTypeFromGid ($gid);
        if ($type == 0) return;

        // Не плодить дубликаты событий, если убийство обработалось повторно.
        $result = dbquery ("SELECT task_id FROM ".$db_prefix."queue WHERE type = '".QTYP_LEVI_RESPAWN."' AND obj_id = $type LIMIT 1;");
        if (dbrows ($result) > 0) return;

        AddQueue (USER_SPACE, QTYP_LEVI_RESPAWN, 0, $type, 0, $when, $this->Rnd (LEVI_RESPAWN_MIN_SECONDS, LEVI_RESPAWN_MAX_SECONDS));
    }

    /**
     * Обработчик события возрождения: создаёт нового левиафана нужного типа,
     * если его ещё нет во вселенной.
     */
    private function RespawnLeviathan (array $queue) : void {
        $type = (int)$queue['obj_id'];
        if (!$this->IsPlanetLeviathan($type)) return;
        if (!$this->LeviathanExists($type)) {
            $this->CreateLeviathan ($type);
        }
        RemoveQueue ($queue['task_id']);
    }

    /**
     * Хук очереди: мод обрабатывает собственные события (возрождение левиафанов).
     */
    public function update_queue (array &$queue) : bool {
        if ($queue['type'] === QTYP_LEVI_RESPAWN) {
            $this->RespawnLeviathan ($queue);
            return true;
        }
        return false;
    }

    public function page_flotten2_planet_types (array &$planet_types) : bool {
        $planet_types[] = PTYP_LEVI_AMOEBA;
        $planet_types[] = PTYP_LEVI_GUARDIAN;
        $planet_types[] = PTYP_LEVI_JUGGERNAUT;
        $planet_types[] = PTYP_LEVI_PORTAL;
        return false;
    }

    public function page_flottenversand_ajax_spy_planets (array &$planet_types) : bool {
        $planet_types[] = PTYP_LEVI_AMOEBA;
        $planet_types[] = PTYP_LEVI_GUARDIAN;
        $planet_types[] = PTYP_LEVI_JUGGERNAUT;
        $planet_types[] = PTYP_LEVI_PORTAL;
        return false;
    }

    public function page_galaxy_custom_object (array $planet, array &$info) : bool {

        if ($this->IsPlanetLeviathan($planet['type']) || $planet['type'] == PTYP_LEVI_PORTAL) {

            $info['overlib'] = $this->GetLeviathanOverlib ($planet);
            return true;
        }
        return false;
    }

    private function GetLeviathanOverlib (array $planet) : string {

        global $GlobalUser;
        global $session;
        global $aktplanet;

        $phalanx = CanPhalanx ($aktplanet, $planet);
        $ptyp = $planet['type'];

        $res = "";
        $res .= "<table width=240 ><tr>";
        $res .= "<td class=c colspan=2 >".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]</td></tr>";
        $res .= "<tr><th width=80 ><img src=".GetPlanetSmallImage ( UserSkin(), $planet )." height=75 width=75 /></th>";
        $res .= "<th><table width=120 ><tr><td colspan=2 class=c >".loca("GALAXY_LEVI_PROPS")."</td></tr>";
        $res .= "<tr><th>".loca("GALAXY_LEVI_SIZE")."</td><th>".nicenum($planet['diameter'])."</td></tr>";
        $res .= "<tr><th>".loca("GALAXY_LEVI_TEMP")."</td><th>".$planet['temp']."</td></tr>";
        $res .= "<tr><td colspan=2 class=c >".loca("GALAXY_LEVI_ACTIONS")."</td></tr>";
        $res .= "<tr><th align=left colspan=2 >";
        $res .= "<a href=# onclick=doit(6,".$planet['g'].",".$planet['s'].",".$planet['p'].",$ptyp,".$GlobalUser['maxspy'].") >".loca("GALAXY_FLEET_SPY")."</a><br><br />";
        if ($phalanx) $res .= "<a href=# onclick=fenster(&#039;index.php?page=phalanx&session=$session&scanid=".$planet['owner_id']."&spid=".$planet['planet_id']."&#039;) >".loca("GALAXY_FLEET_PHALANX")."</a><br />";
        $res .= "<a href=index.php?page=flotten1&session=$session&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=$ptyp&target_mission=3 >".loca("GALAXY_FLEET_TRANSPORT")."</a><br />";
        $res .= "<a href=index.php?page=flotten1&session=$session&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=$ptyp&target_mission=1 >".loca("GALAXY_FLEET_ATTACK")."</a><br />";
        if ($GlobalUser['admin'] >= 2) $res .= "<a href=index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id'].">".loca("GALAXY_PLANET_ADMIN")."</a><br />";
        $res .= "</th></tr></table></tr></table>";

        return $res;
    }

    public function fleet_handler (array $param) : bool {
        $fleet_obj = $param['fleet_obj'];
        if ($fleet_obj['mission'] == FTYP_LEVI_PREPARE_JUMP) {
            $this->LeviathanArrive ($param['queue'], $param['fleet_obj'], $param['fleet'], $param['origin'], $param['target']);
            return true;
        }
        return false;
    }

    /**
     * Разворачивает флоты игроков, летящие к планете-объекту, который сейчас
     * будет удалён (портал или труп чудовища), чтобы они не осиротели.
     * Флот самого чудовища (задание FTYP_LEVI_PREPARE_JUMP) не затрагивается.
     */
    private function RecallIncomingFleets (int $planet_id, int $when) : void {
        global $db_prefix;
        $result = dbquery ("SELECT fleet_id FROM ".$db_prefix."fleet WHERE target_planet = $planet_id AND mission < ".FTYP_RETURN.";");
        $rows = dbrows ($result);
        while ($rows--) {
            $fleet_obj = dbarray ($result);
            RecallFleet ((int)$fleet_obj['fleet_id'], $when);
        }
    }

    private function LeviathanArrive (array $queue, array $fleet_obj, array $fleet, array $origin, array $old_portal) : void {

        global $db_prefix;
        global $GlobalUni;

        $now = $queue['end'];

        // Определить какой левиафан прилетел
        
        $gid = 0;
        if ($fleet[GID_LEVI_AMOEBA] != 0) $gid = GID_LEVI_AMOEBA;
        else if ($fleet[GID_LEVI_GUARDIAN] != 0) $gid = GID_LEVI_GUARDIAN;
        else if ($fleet[GID_LEVI_JUGGERNAUT] != 0) $gid = GID_LEVI_JUGGERNAUT;
        if ($gid == 0) return;

        // Удалить портал (точку выхода); флоты игроков, летящие к нему, разворачиваем

        $this->RecallIncomingFleets ($old_portal['planet_id'], $now);
        DestroyPlanet ($old_portal['planet_id']);

        // Переместить планету левиафана
        
        $query = "UPDATE ".$db_prefix."planets SET `g`=".$old_portal['g'].", `s`=".$old_portal['s'].", `p`=".$old_portal['p']." WHERE planet_id = ".$origin['planet_id'].";";
        dbquery ($query);

        // Начать битву

        $battle_result = $this->LeviathanBattle ($gid, $fleet_obj['fleet_id'], $fleet, $old_portal, $now);

        // Левиафан уничтожен: убрать труп и запланировать возрождение через 24-72 часа.
        // Возрождение позволяет сохранить динамику игры (дизайн мода).

        if ($battle_result == BATTLE_RESULT_DWON) {
            $this->RecallIncomingFleets ($origin['planet_id'], $now);
            DestroyPlanet ($origin['planet_id']);
            $this->ScheduleRespawn ($gid, $now);
            return;
        }

        // Создать новый портал (точку выхода для следующего прыжка)

        $coords = $this->DeterminePortalCoords ($gid, $old_portal);

        $name = loca_lang ("PLANET_".PTYP_LEVI_PORTAL, $GlobalUni['lang']);

        $new_portal = array(
            'name' => $name, 'type' => PTYP_LEVI_PORTAL, 'g' => $coords['g'], 's' => $coords['s'], 'p' => $coords['p'], 
            'owner_id' => USER_SPACE, 'diameter' => LEVI_PORTAL_DIAMETER, 'temp' => LEVI_PORTAL_TEMP, 'fields' => 0, 'maxfields' => 0, 'date' => $now,
            'lastpeek' => $now, 'lastakt' => $now, 'gate_until' => 0, 'remove' => 0 );
        $id = AddDBRow ( $new_portal, "planets" );
        $new_portal = LoadPlanetById ($id);         // reload

        // Запустить флот

        $this->DispatchLeviathan ($gid, $origin, $new_portal, $queue['end'], 1);
    }

    private function LeviathanBattle (int $levi_gid, int $fleet_id, array $fleet, array $old_portal, int $when) : int {

        global $db_prefix;
        global $GlobalUni;
        global $fleetmap;
        global $defmap;
        global $rakmap;
        global $transportableResources;
        $defmap_norak = array_diff($defmap, $rakmap);

        Debug ( "LeviathanBattle" );

        $unitab = LoadUniverse ();
        $fid = $unitab['fid'];
        $did = $unitab['did'];
        $rf = $unitab['rapid'];

        // Определить списки участников

        $a = [];
        $d = [];
        $anum = 0;
        $dnum = 0;

        $a[0] = LoadUser ($old_portal['owner_id']);
        $a[0]['units'] = array ();
        foreach ($fleetmap as $i=>$gid) $a[0]['units'][$gid] = abs($fleet[$gid]);
        $a[0]['g'] = $old_portal['g'];
        $a[0]['s'] = $old_portal['s'];
        $a[0]['p'] = $old_portal['p'];
        $a[0]['id'] = $fleet_id;
        $a[0]['pf'] = BATTLE_PTCP_FLEET;    // fleet
        $a[0]['points'] = $a[0]['fpoints'] = 0;
        $anum++;

        // Выбрать радиус поражения
        switch ($levi_gid) {
            case GID_LEVI_AMOEBA:
                $delta = 1;
                break;
            case GID_LEVI_GUARDIAN:
                $delta = 2;
                break;
            case GID_LEVI_JUGGERNAUT:
                $delta = 3;
                break;
            default:
                $delta = 0;
                break;
        }

        $p_min = max (1, $old_portal['p'] - $delta);
        $p_max = min (15, $old_portal['p'] + $delta);

        $result = EnumPlanetsGalaxy ($old_portal['g'], $old_portal['s']);
        $rows = dbrows ($result);
        while ($rows--) {

            $planet = dbarray ($result);
            // Пропустить особенные планеты
            if ($planet['type'] != PTYP_PLANET) continue;
            // Пропустить планеты не в радиусе поражения
            if ($planet['p'] < $p_min || $planet['p'] > $p_max) continue;

            // Иначе добавить планету и союзные флоты 
            $p = $planet;
            $planet_id = $planet['planet_id'];
            $d[$dnum] = LoadUser ( $p['owner_id'] );
            $d[$dnum]['units'] = array ();
            foreach ($fleetmap as $i=>$gid) {
                if (isset($p[$gid])) {
                    $d[$dnum]['units'][$gid] = abs($p[$gid]);
                }
            }
            foreach ($defmap_norak as $i=>$gid) {
                if (isset($p[$gid])) {
                    $d[$dnum]['units'][$gid] = abs($p[$gid]);
                }
            }
            $d[$dnum]['g'] = $p['g'];
            $d[$dnum]['s'] = $p['s'];
            $d[$dnum]['p'] = $p['p'];
            $d[$dnum]['id'] = $planet_id;
            $d[$dnum]['pf'] = BATTLE_PTCP_PLANET;    // planet
            $d[$dnum]['points'] = $d[$dnum]['fpoints'] = 0;
            $dnum++;

            // Fleets on hold (ACS)
            $acs_result = GetHoldingFleets ($planet_id);
            $acs_rows = dbrows ($acs_result);
            while ($acs_rows--)
            {
                $fleet_obj = dbarray ($acs_result);

                $d[$dnum] = LoadUser ( $fleet_obj['owner_id'] );
                $d[$dnum]['units'] = array ();
                foreach ($fleetmap as $i=>$gid) $d[$dnum]['units'][$gid] = abs($fleet_obj[$gid]);
                $start_planet = LoadPlanetById ( $fleet_obj['start_planet'] );
                $d[$dnum]['g'] = $start_planet['g'];
                $d[$dnum]['s'] = $start_planet['s'];
                $d[$dnum]['p'] = $start_planet['p'];
                $d[$dnum]['id'] = $fleet_obj['fleet_id'];
                $d[$dnum]['pf'] = BATTLE_PTCP_FLEET;    // fleet  
                $d[$dnum]['points'] = $d[$dnum]['fpoints'] = 0;

                $dnum++;
            }
        }

        if ($dnum == 0) return BATTLE_RESULT_AWON;

        // Начать битву

        $max_round = BATTLE_MAX_ROUND;
        switch ($levi_gid) {
            case GID_LEVI_AMOEBA:
                $max_round += 1;
                break;
            case GID_LEVI_GUARDIAN:
                $max_round += 2;
                break;
            case GID_LEVI_JUGGERNAUT:
                $max_round += 3;
                break;
        }

        $source = GenBattleSourceData ($a, $d, $rf, $max_round);

        $battle = array ( 'source' => $source, 'title' => "", 'report' => "", 'date' => $when );
        $battle_id = AddDBRow ( $battle, "battledata" );

        $res = ExecuteBattle ($unitab, $battle_id, $source, $a, $d);

        // Обработать результаты

        // Determine the outcome of the battle.
        if ( $res['result'] === "awon" ) $battle_result = BATTLE_RESULT_AWON;
        else if ( $res['result'] === "dwon" ) $battle_result = BATTLE_RESULT_DWON;
        else $battle_result = BATTLE_RESULT_DRAW;

        // Restore the defense
        $repaired = RepairDefense ( $d, $res, $unitab['defrepair'], $unitab['defrepair_delta'] );

        // Calculate total losses (account for deuterium and repaired defenses)
        $loss = CalcLosses ( $a, $d, $res, $repaired );
        $aloss = $loss['aloss'];
        $dloss = $loss['dloss'];

        // Применить результат боя: списать потери с планет и флотов защитников,
        // восстановить оборону, отметить активность и обновить статистику игроков.
        $this->LeviathanWriteback ($d, $res, $repaired);
        $this->UpdateDefenderActivity ($d, $when);
        foreach ( $d as $i=>$user ) {
            if (isset($user['player_id'])) {
                AdjustStats ( $user['player_id'], $user['points'], $user['fpoints'], 0, '-' );
            }
        }
        RecalcRanks ();

        // Если чудовище уничтожено - поделить трофей между участниками обороны
        // пропорционально нанесённому урону (дизайн мода).
        if ($battle_result == BATTLE_RESULT_DWON) {
            $loot = $this->GetLeviLoot ($levi_gid);
            $this->GrantLeviathanLoot ($loot, $d, $old_portal, $when);
        }

        // У чудовища нет ресурсов для грабежа, а обломки для лун не создаются:
        // трофей с убитого чудовища распределяется напрямую (см. выше).
        $captured = null;
        $moonchance = 0;
        $mooncreated = false;
        $debris = null;

        // This array contains a cache of generated battle reports for each language.
        $battle_text = array();

        // Generate a battle report in the universe language (for log history)
        $text = BattleReport ( $res, $when, $loss, $captured, $moonchance, $mooncreated, $repaired, $debris, $GlobalUni['lang'] );
        $battle_text[$GlobalUni['lang']] = $text;

        // Send out messages, mailbox is used to avoid sending multiple messages to ACS players.
        $mailbox = array ();
        $a_result = array ( 0=>"combatreport_ididattack_iwon", 1=>"combatreport_ididattack_ilost", 2=>"combatreport_ididattack_draw" );
        $d_result = array ( 1=>"combatreport_igotattacked_iwon", 0=>"combatreport_igotattacked_ilost", 2=>"combatreport_igotattacked_draw" );

        foreach ( $d as $i=>$user )        // Defenders
        {
            // Generate a battle report in the user's language if it is not in the cache
            if (key_exists($user['lang'], $battle_text)) $text = $battle_text[$user['lang']];
            else {
                $text = BattleReport ( $res, $when, $loss, $captured, $moonchance, $mooncreated, $repaired, $debris, $user['lang'] );
                $battle_text[$user['lang']] = $text;
            }

            loca_add ( "fleetmsg", $user['lang'] );

            if ( key_exists($user['player_id'], $mailbox) ) continue;
            $bericht = SendMessage ( $user['player_id'], loca_lang("FLEET_MESSAGE_FROM", $user['lang']), loca_lang("FLEET_MESSAGE_BATTLE", $user['lang']), $text, MTYP_BATTLE_REPORT_TEXT, $when );
            MarkMessage ( $user['player_id'], $bericht );
            $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=bericht&session={PUBLIC_SESSION}&bericht=$bericht\', \'Bericht_Kampf\');\" ><span class=\"".$d_result[$battle_result]."\">" .
                loca_lang("FLEET_MESSAGE_BATTLE", $user['lang']) .
                " [".$user['g'].":".$user['s'].":".$user['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
            SendMessage ( $user['player_id'], loca_lang("FLEET_MESSAGE_FROM", $user['lang']), $subj, "", MTYP_BATTLE_REPORT_LINK, $when );
            $mailbox[ $user['player_id'] ] = true;
        }

        // Update the battle report log (use the universe language battle report)
        loca_add ( "fleetmsg", $GlobalUni['lang'] );
        $subj = "<a href=\"#\" onclick=\"fenster(\'index.php?page=admin&session={PUBLIC_SESSION}&mode=BattleReport&bericht=$battle_id\', \'Bericht_Kampf\');\" ><span class=\"".$a_result[$battle_result]."\">" .
            loca_lang("FLEET_MESSAGE_BATTLE", $GlobalUni['lang']) .
            " [".$old_portal['g'].":".$old_portal['s'].":".$old_portal['p']."] (V:".nicenum($dloss).",A:".nicenum($aloss).")</span></a>";
        $query = "UPDATE ".$db_prefix."battledata SET title = '".$subj."', report = '".$battle_text[$GlobalUni['lang']]."' WHERE battle_id = $battle_id;";
        dbquery ( $query );

        // При поражении левиафана разослать всем игрокам броадкаст об этом событии

        if ($battle_result == BATTLE_RESULT_DWON) {
            
            BroadcastMessage (0, 
                loca_lang ("FLEET_MESSAGE_FROM", $GlobalUni['lang']), 
                $subj, 
                va(loca_lang("LEVI_DEFEAT_MSG_".$levi_gid, $GlobalUni['lang']), ShowGalaxy ($old_portal)) );
        }

        // Clean up old battle reports
        $ago = $when - 2 * 7 * 24 * 60 * 60;
        $query = "DELETE FROM ".$db_prefix."battledata WHERE date < $ago;";
        dbquery ($query);



        // Cleaning up the battle engine's intermediate data
        unlink ( "battledata/battle_".$battle_id.".txt" );
        unlink ( "battleresult/battle_".$battle_id.".txt" );

        return $battle_result;
    }

    /**
     * Списывает потери защитников по итогам боя с чудовищем.
     *
     * Обновляет корабли и (восстановленную) оборону планет, а также флоты в
     * ожидании (ACS): уцелевшие остаются на орбите, уничтоженные удаляются.
     * Атакующий флот чудовища здесь не обрабатывается - его удаляет движок
     * очереди после возврата из хука fleet_handler.
     */
    private function LeviathanWriteback (array $d, array $res, array $repaired) : void {

        global $fleetmap;
        global $defmap;
        global $rakmap;
        $defmap_norak = array_diff($defmap, $rakmap);

        $rounds = count ( $res['rounds'] );
        if ( $rounds == 0 ) return;

        $last = $res['rounds'][$rounds - 1];

        foreach ( $last['defenders'] as $i=>$defender )
        {
            switch ($defender['pf']) {

                case BATTLE_PTCP_PLANET:        // Planet
                    $objects = array ();
                    foreach ( $fleetmap as $ii=>$gid ) {
                        $objects[$gid] = isset($defender['units'][$gid]) ? $defender['units'][$gid] : 0;
                    }
                    foreach ( $defmap_norak as $ii=>$gid ) {
                        $objects[$gid] = isset($repaired[$i][$gid]) ? $repaired[$i][$gid] : 0;
                        $objects[$gid] += isset($defender['units'][$gid]) ? $defender['units'][$gid] : 0;
                    }
                    SetPlanetFleetDefense ( $defender['id'], $objects );
                    break;

                case BATTLE_PTCP_FLEET:     // Fleets on hold (ACS)
                    $ships = 0;
                    foreach ( $fleetmap as $ii=>$gid ) {
                        if (isset($defender['units'][$gid])) {
                            $ships += $defender['units'][$gid];
                        }
                    }
                    if ( $ships > 0 ) SetFleet ( $defender['id'], $defender['units'] );
                    else {
                        $queue = GetFleetQueue ($defender['id']);
                        if ($queue) {
                            DeleteFleet ($defender['id']);    // delete fleet
                            RemoveQueue ( $queue['task_id'] );    // delete task
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Отмечает активность планет, участвовавших в обороне.
     */
    private function UpdateDefenderActivity (array $d, int $when) : void {
        foreach ( $d as $i=>$user ) {
            if ( isset($user['pf']) && $user['pf'] == BATTLE_PTCP_PLANET ) {
                UpdatePlanetActivity ( $user['id'], $when );
            }
        }
    }

    /**
     * "Вклад" участника обороны: суммарная атака его кораблей и обороны с учётом
     * уровня оружейной технологии. Используется как прокси для нанесённого урона
     * при дележе трофея (прямого учёта урона по участникам движок не даёт).
     */
    private function LeviathanParticipantWeight (array $user) : float {
        global $UnitParam;
        $weap = isset($user[GID_R_WEAPON]) ? max (0, (int)$user[GID_R_WEAPON]) : 0;
        $attack = 0.0;
        if (isset($user['units'])) {
            foreach ( $user['units'] as $gid=>$amount ) {
                if ($amount <= 0) continue;
                if (!isset($UnitParam[$gid])) continue;
                $attack += $amount * $UnitParam[$gid][2];
            }
        }
        return $attack * (1.0 + 0.1 * $weap);
    }

    /**
     * Планета, на которую участник обороны получает свою долю трофея.
     *
     * Для планеты - это она сама; для флота в ожидании (ACS) - планета вылета
     * флота владельца.
     */
    private function LeviathanLootTargetPlanet (array $user) : int {
        if (!isset($user['id'])) return 0;
        if (isset($user['pf']) && $user['pf'] == BATTLE_PTCP_PLANET) return (int)$user['id'];
        $fleet_obj = LoadFleet ( (int)$user['id'] );
        if ($fleet_obj == null) return 0;
        return (int)$fleet_obj['start_planet'];
    }

    /**
     * Делит трофей убитого чудовища между участниками обороны пропорционально
     * их вкладу и доставляет доли на планеты. Остаток от деления достаётся
     * участнику с наибольшим вкладом, чтобы ресурсы не терялись при округлении.
     */
    private function GrantLeviathanLoot (array $loot, array $d, array $portal, int $when) : void {
        if (count($loot) == 0) return;

        $weight = array ();
        $total = 0.0;
        foreach ( $d as $i=>$user ) {
            $w = $this->LeviathanParticipantWeight ($user);
            $weight[$i] = $w;
            $total += $w;
        }
        if ($total <= 0) return;

        $best = -1;
        foreach ($weight as $i=>$w) {
            if ($w > 0 && ($best == -1 || $w > $weight[$best])) $best = $i;
        }

        $granted = array ();
        foreach ( $d as $i=>$user ) {
            if ($weight[$i] <= 0) continue;
            $share = array ();
            foreach ( $loot as $rc=>$amount ) {
                $share[$rc] = (int) floor ( $amount * $weight[$i] / $total );
            }
            $granted[$i] = $share;
        }

        // Остаток от деления - самому активному участнику.
        foreach ( $loot as $rc=>$amount ) {
            $given = 0;
            foreach ($granted as $share) {
                if (isset($share[$rc])) $given += $share[$rc];
            }
            if ($best >= 0 && isset($granted[$best])) {
                $granted[$best][$rc] += $amount - $given;
            }
        }

        $mailbox = array ();    // одно сообщение на игрока
        foreach ( $granted as $i=>$share ) {
            $planet_id = $this->LeviathanLootTargetPlanet ($d[$i]);
            if ($planet_id == 0) continue;

            $has = false;
            foreach ($share as $rc=>$amount) {
                if ($amount > 0) { $has = true; break; }
            }
            if (!$has) continue;

            AdjustResources ( $share, $planet_id, '+' );

            $player_id = isset($d[$i]['player_id']) ? $d[$i]['player_id'] : 0;
            $lang = isset($d[$i]['lang']) ? $d[$i]['lang'] : 'en';
            if ($player_id == 0) continue;
            if ( key_exists($player_id, $mailbox) ) continue;
            $mailbox[$player_id] = true;

            $parts = array ();
            foreach ($share as $rc=>$amount) {
                if ($amount > 0) $parts[] = loca_lang("NAME_".$rc, $lang) . " " . nicenum($amount);
            }
            loca_add ( "leviathans", $lang, __DIR__ );
            $text = va ( loca_lang ("LEVI_LOOT_TEXT", $lang), ShowGalaxy ($portal), implode (", ", $parts) );
            SendMessage ( $player_id, loca_lang ("FLEET_MESSAGE_FROM", $lang), loca_lang ("LEVI_LOOT_SUBJ", $lang), $text, MTYP_MISC, $when );
        }
    }
}

?>