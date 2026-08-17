<?php

/**
 * FixtureBuilder creates a test universe with 3 players in an in-memory SQLite database.
 * Used for Golden Pages snapshot testing.
 */
class FixtureBuilder
{
    private $pdo;
    private $dbPrefix = 'test_';
    private $players = [];
    private $uniData = [];

    public function __construct()
    {
        $this->pdo = new PDO(
            'sqlite::memory:',
            null,
            null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $this->setupSchema();
    }

    private function setupSchema(): void
    {
        $tables = [
            "CREATE TABLE {$this->dbPrefix}uni (
                num INTEGER PRIMARY KEY,
                speed REAL DEFAULT 1.0,
                fspeed REAL DEFAULT 1.0,
                galaxies INTEGER DEFAULT 1,
                systems INTEGER DEFAULT 15,
                maxusers INTEGER DEFAULT 1000,
                acs INTEGER DEFAULT 1,
                lang CHAR(4) DEFAULT 'en',
                battle_engine TEXT DEFAULT 'php',
                php_battle INTEGER DEFAULT 1,
                moons INTEGER DEFAULT 1,
                rapid INTEGER DEFAULT 0,
                startdate INTEGER DEFAULT 0,
                ext_board TEXT DEFAULT '',
                ext_discord TEXT DEFAULT '',
                ext_tutorial TEXT DEFAULT '',
                ext_rules TEXT DEFAULT '',
                ext_impressum TEXT DEFAULT '',
                news1 TEXT DEFAULT '',
                news2 TEXT DEFAULT '',
                news_until INTEGER DEFAULT 0,
                freeze INTEGER DEFAULT 0,
                defrepair INTEGER DEFAULT 0,
                defrepair_delta INTEGER DEFAULT 0,
                fid INTEGER DEFAULT 0,
                did INTEGER DEFAULT 0,
                battle_max INTEGER DEFAULT 1000000,
                force_lang INTEGER DEFAULT 0,
                start_dm INTEGER DEFAULT 0,
                max_werf INTEGER DEFAULT 1000,
                feedage INTEGER DEFAULT 0,
                hacks INTEGER DEFAULT 0,
                usercount INTEGER DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}users (
                player_id INTEGER PRIMARY KEY AUTOINCREMENT,
                regdate INT UNSIGNED DEFAULT 0,
                ally_id INT DEFAULT 0,
                joindate INT UNSIGNED DEFAULT 0,
                allyrank INT DEFAULT 0,
                session CHAR(12) DEFAULT '',
                private_session CHAR(32) DEFAULT '',
                name CHAR(20) DEFAULT '',
                oname CHAR(20) DEFAULT '',
                name_changed INT DEFAULT 0,
                name_until INT UNSIGNED DEFAULT 0,
                password CHAR(32) DEFAULT '',
                temp_pass CHAR(32) DEFAULT '',
                pemail CHAR(50) DEFAULT '',
                email CHAR(50) DEFAULT '',
                email_changed INT DEFAULT 0,
                email_until INT UNSIGNED DEFAULT 0,
                disable INT DEFAULT 0,
                disable_until INT UNSIGNED DEFAULT 0,
                vacation INT DEFAULT 0,
                vacation_until INT UNSIGNED DEFAULT 0,
                banned INT DEFAULT 0,
                banned_until INT UNSIGNED DEFAULT 0,
                noattack INT DEFAULT 0,
                noattack_until INT UNSIGNED DEFAULT 0,
                lastlogin INT UNSIGNED DEFAULT 0,
                lastclick INT UNSIGNED DEFAULT 0,
                ip_addr CHAR(15) DEFAULT '',
                validated INT DEFAULT 0,
                validatemd CHAR(32) DEFAULT '',
                hplanetid INT DEFAULT 0,
                admin INT DEFAULT 0,
                sortby INT DEFAULT 0,
                sortorder INT DEFAULT 0,
                skin CHAR(80) DEFAULT '',
                useskin INT DEFAULT 0,
                deact_ip INT DEFAULT 0,
                maxspy INT DEFAULT 0,
                maxfleetmsg INT DEFAULT 0,
                lang CHAR(4) DEFAULT 'en',
                aktplanet INT DEFAULT 0,
                dm INT UNSIGNED DEFAULT 0,
                dmfree INT UNSIGNED DEFAULT 0,
                sniff INT DEFAULT 0,
                debug INT DEFAULT 0,
                trader INT DEFAULT 0,
                rate_m DOUBLE DEFAULT 1.0,
                rate_k DOUBLE DEFAULT 1.0,
                rate_d DOUBLE DEFAULT 1.0,
                score1 BIGINT DEFAULT 0,
                score2 INT DEFAULT 0,
                score3 INT DEFAULT 0,
                place1 INT DEFAULT 0,
                place2 INT DEFAULT 0,
                place3 INT DEFAULT 0,
                oldscore1 BIGINT DEFAULT 0,
                oldscore2 INT DEFAULT 0,
                oldscore3 INT DEFAULT 0,
                oldplace1 INT DEFAULT 0,
                oldplace2 INT DEFAULT 0,
                oldplace3 INT DEFAULT 0,
                scoredate INT UNSIGNED DEFAULT 0,
                GID_R_ESPIONAGE TINYINT DEFAULT 0,
                GID_R_COMPUTER TINYINT DEFAULT 0,
                GID_R_WEAPON TINYINT DEFAULT 0,
                GID_R_SHIELD TINYINT DEFAULT 0,
                GID_R_ARMOUR TINYINT DEFAULT 0,
                GID_R_ENERGY TINYINT DEFAULT 0,
                GID_R_HYPERSPACE TINYINT DEFAULT 0,
                GID_R_COMBUST_DRIVE TINYINT DEFAULT 0,
                GID_R_IMPULSE_DRIVE TINYINT DEFAULT 0,
                GID_R_HYPER_DRIVE TINYINT DEFAULT 0,
                GID_R_LASER_TECH TINYINT DEFAULT 0,
                GID_R_ION_TECH TINYINT DEFAULT 0,
                GID_R_PLASMA_TECH TINYINT DEFAULT 0,
                GID_R_IGN TINYINT DEFAULT 0,
                GID_R_EXPEDITION TINYINT DEFAULT 0,
                GID_R_GRAVITON TINYINT DEFAULT 0,
                flags INT UNSIGNED DEFAULT 0,
                feedid CHAR(32) DEFAULT '',
                lastfeed INT UNSIGNED DEFAULT 0,
                com_until INT UNSIGNED DEFAULT 0,
                adm_until INT UNSIGNED DEFAULT 0,
                eng_until INT UNSIGNED DEFAULT 0,
                geo_until INT UNSIGNED DEFAULT 0,
                tec_until INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}planets (
                planet_id INTEGER PRIMARY KEY AUTOINCREMENT,
                name CHAR(20) DEFAULT '',
                type INT DEFAULT 1,
                g INT DEFAULT 1,
                s INT DEFAULT 1,
                p INT DEFAULT 1,
                owner_id INT DEFAULT 0,
                diameter INT DEFAULT 12000,
                temp INT DEFAULT 30,
                fields INT DEFAULT 0,
                maxfields INT DEFAULT 100,
                date INT UNSIGNED DEFAULT 0,
                `1` INT DEFAULT 0,
                `2` INT DEFAULT 0,
                `3` INT DEFAULT 0,
                `4` INT DEFAULT 0,
                `12` INT DEFAULT 0,
                `14` INT DEFAULT 0,
                `15` INT DEFAULT 0,
                `21` INT DEFAULT 0,
                `22` INT DEFAULT 0,
                `23` INT DEFAULT 0,
                `24` INT DEFAULT 0,
                `31` INT DEFAULT 0,
                `33` INT DEFAULT 0,
                `34` INT DEFAULT 0,
                `41` INT DEFAULT 0,
                `42` INT DEFAULT 0,
                `43` INT DEFAULT 0,
                `44` INT DEFAULT 0,
                `401` INT DEFAULT 0,
                `402` INT DEFAULT 0,
                `403` INT DEFAULT 0,
                `404` INT DEFAULT 0,
                `405` INT DEFAULT 0,
                `406` INT DEFAULT 0,
                `407` INT DEFAULT 0,
                `408` INT DEFAULT 0,
                `502` INT DEFAULT 0,
                `503` INT DEFAULT 0,
                `202` INT DEFAULT 0,
                `203` INT DEFAULT 0,
                `204` INT DEFAULT 0,
                `205` INT DEFAULT 0,
                `206` INT DEFAULT 0,
                `207` INT DEFAULT 0,
                `208` INT DEFAULT 0,
                `209` INT DEFAULT 0,
                `210` INT DEFAULT 0,
                `211` INT DEFAULT 0,
                `212` INT DEFAULT 0,
                `213` INT DEFAULT 0,
                `214` INT DEFAULT 0,
                `215` INT DEFAULT 0,
                `700` DOUBLE DEFAULT 0,
                `701` DOUBLE DEFAULT 0,
                `702` DOUBLE DEFAULT 0,
                lastpeek INT UNSIGNED DEFAULT 0,
                lastakt INT UNSIGNED DEFAULT 0,
                gate_until INT UNSIGNED DEFAULT 0,
                remove INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}coltab (
                id INTEGER PRIMARY KEY,
                t1_a INT DEFAULT 1100,
                t1_b INT DEFAULT 5000,
                t1_c INT DEFAULT 1000,
                t2_a INT DEFAULT 1100,
                t2_b INT DEFAULT 5000,
                t2_c INT DEFAULT 1000,
                t3_a INT DEFAULT 1100,
                t3_b INT DEFAULT 5000,
                t3_c INT DEFAULT 1000,
                t4_a INT DEFAULT 1100,
                t4_b INT DEFAULT 5000,
                t4_c INT DEFAULT 1000,
                t5_a INT DEFAULT 1100,
                t5_b INT DEFAULT 5000,
                t5_c INT DEFAULT 1000
            )",
            "CREATE TABLE {$this->dbPrefix}queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                obj_id INT DEFAULT 0,
                sub_id INT DEFAULT 0,
                type CHAR(10) DEFAULT 'Build',
                tech_id INT DEFAULT 0,
                start INT UNSIGNED DEFAULT 0,
                end INT UNSIGNED DEFAULT 0,
                freeze INT DEFAULT 0,
                frozen INT UNSIGNED DEFAULT 0,
                level INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}buildqueue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                planet_id INT DEFAULT 0,
                obj_id INT DEFAULT 0,
                tech_id INT DEFAULT 0,
                start INT UNSIGNED DEFAULT 0,
                end INT UNSIGNED DEFAULT 0,
                level INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}fleet (
                fleet_id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                start_planet INT DEFAULT 0,
                target_planet INT DEFAULT 0,
                start_galaxy INT DEFAULT 0,
                start_system INT DEFAULT 0,
                start_pos INT DEFAULT 0,
                target_galaxy INT DEFAULT 0,
                target_system INT DEFAULT 0,
                target_pos INT DEFAULT 0,
                start_time INT UNSIGNED DEFAULT 0,
                return_time INT UNSIGNED DEFAULT 0,
                end_time INT UNSIGNED DEFAULT 0,
                mission INT DEFAULT 0,
                amount INT DEFAULT 0,
                mission_status INT DEFAULT 0,
                tech_id INT DEFAULT 0,
                metal DOUBLE DEFAULT 0,
                crystal DOUBLE DEFAULT 0,
                deuterium DOUBLE DEFAULT 0,
                speed INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                time INT UNSIGNED DEFAULT 0,
                category INT DEFAULT 0,
                type INT DEFAULT 0,
                from_name CHAR(20) DEFAULT '',
                from_player_id INT DEFAULT 0,
                to_name CHAR(20) DEFAULT '',
                to_player_id INT DEFAULT 0,
                subject TEXT DEFAULT '',
                text TEXT DEFAULT '',
                read INT DEFAULT 0,
                deleted_from INT DEFAULT 0,
                deleted_to INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                time INT UNSIGNED DEFAULT 0,
                subj TEXT DEFAULT '',
                text TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}errors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                ip TEXT DEFAULT '',
                agent TEXT DEFAULT '',
                url TEXT DEFAULT '',
                text TEXT DEFAULT '',
                date INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}buddy (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                buddy_id INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}ally (
                ally_id INTEGER PRIMARY KEY AUTOINCREMENT,
                name CHAR(50) DEFAULT '',
                tag CHAR(10) DEFAULT '',
                founder_id INT DEFAULT 0,
                founded INT UNSIGNED DEFAULT 0,
                description TEXT DEFAULT '',
                logo TEXT DEFAULT '',
                public INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}allyranks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ally_id INT DEFAULT 0,
                player_id INT DEFAULT 0,
                rank INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}allyapps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ally_id INT DEFAULT 0,
                player_id INT DEFAULT 0,
                time INT UNSIGNED DEFAULT 0,
                status INT DEFAULT 0,
                text TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}browse (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                time INT UNSIGNED DEFAULT 0,
                page CHAR(30) DEFAULT '',
                params TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                time INT UNSIGNED DEFAULT 0,
                text TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}userlogs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_id INT DEFAULT 0,
                time INT UNSIGNED DEFAULT 0,
                action TEXT DEFAULT '',
                details TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}iplogs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_id INT DEFAULT 0,
                ip TEXT DEFAULT '',
                time INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}pranger (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_id INT DEFAULT 0,
                name CHAR(20) DEFAULT '',
                reason TEXT DEFAULT '',
                date INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}exptab (
                id INTEGER PRIMARY KEY,
                name TEXT DEFAULT '',
                description TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}template (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                name CHAR(30) DEFAULT '',
                fleet_data TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}botvars (
                id INTEGER PRIMARY KEY,
                var_name CHAR(50) DEFAULT '',
                var_value TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}botstrat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_id INT DEFAULT 0,
                strat_data TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}union (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fleet_id INT DEFAULT 0,
                union_id INT DEFAULT 0,
                owner_id INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}battledata (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                battle_id INT DEFAULT 0,
                data TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}fleetlogs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fleet_id INT DEFAULT 0,
                time INT UNSIGNED DEFAULT 0,
                event TEXT DEFAULT '',
                details TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}debug (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                time INT UNSIGNED DEFAULT 0,
                message TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}coupons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code CHAR(32) DEFAULT '',
                owner_id INT DEFAULT 0,
                value INT DEFAULT 0,
                used INT DEFAULT 0,
                date INT UNSIGNED DEFAULT 0
            )"
        ];

        foreach ($tables as $sql) {
            $this->pdo->exec($sql);
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function getDbPrefix(): string
    {
        return $this->dbPrefix;
    }

    public function setDbPrefix(string $prefix): void
    {
        $this->dbPrefix = $prefix;
    }

    /**
     * Create the test universe with 3 players
     */
    public function createTestUniverse(): self
    {
        $now = time();

        // Insert universe settings
        $this->pdo->exec("INSERT INTO {$this->dbPrefix}uni (num, speed, fspeed, galaxies, systems, maxusers, acs, lang, moons, start_dm, max_werf, usercount) VALUES (1, 1.0, 1.0, 1, 15, 1000, 1, 'en', 1, 0, 1000, 3)");

        // Insert colony settings
        $this->pdo->exec("INSERT INTO {$this->dbPrefix}coltab (id) VALUES (1)");

        // Create 3 players
        $players = [
            [
                'name' => 'PlayerOne',
                'oname' => 'P1',
                'email' => 'player1@test.com',
                'lang' => 'en',
                'admin' => 0,
                'validated' => 1,
                'score1' => 50000,
                'score2' => 30000,
                'score3' => 20000,
                'place1' => 1,
                'place2' => 5,
                'place3' => 10,
                'aktplanet' => 1,
                // Research levels
                'GID_R_ESPIONAGE' => 5,
                'GID_R_OBSERVATORY' => 4,
                'GID_R_RESEARCH_ENTANGLING' => 3,
                'GID_R_TECHNOLOGY_LAUNCHER' => 2,
                'GID_R_WARP_DRIVE' => 3,
                'GID_R_HYPER_DRIVE' => 2,
                'GID_R_PLASMA' => 1,
                'GID_R_LASER' => 4,
                'GID_R_JAMMING' => 2,
                'GID_R_COM' => 5,
                'GID_R_ASTROPHY' => 1,
                'GID_R_GRAVITON' => 0,
            ],
            [
                'name' => 'PlayerTwo',
                'oname' => 'P2',
                'email' => 'player2@test.com',
                'lang' => 'en',
                'admin' => 0,
                'validated' => 1,
                'score1' => 45000,
                'score2' => 28000,
                'score3' => 18000,
                'place1' => 2,
                'place2' => 8,
                'place3' => 15,
                'aktplanet' => 4,
                'GID_R_ESPIONAGE' => 4,
                'GID_R_OBSERVATORY' => 3,
                'GID_R_RESEARCH_ENTANGLING' => 2,
                'GID_R_TECHNOLOGY_LAUNCHER' => 3,
                'GID_R_WARP_DRIVE' => 2,
                'GID_R_HYPER_DRIVE' => 1,
                'GID_R_PLASMA' => 2,
                'GID_R_LASER' => 3,
                'GID_R_JAMMING' => 1,
                'GID_R_COM' => 4,
                'GID_R_ASTROPHY' => 0,
                'GID_R_GRAVITON' => 0,
            ],
            [
                'name' => 'PlayerThree',
                'oname' => 'P3',
                'email' => 'player3@test.com',
                'lang' => 'en',
                'admin' => 0,
                'validated' => 1,
                'score1' => 40000,
                'score2' => 25000,
                'score3' => 15000,
                'place1' => 3,
                'place2' => 12,
                'place3' => 20,
                'aktplanet' => 7,
                'GID_R_ESPIONAGE' => 3,
                'GID_R_OBSERVATORY' => 2,
                'GID_R_RESEARCH_ENTANGLING' => 1,
                'GID_R_TECHNOLOGY_LAUNCHER' => 1,
                'GID_R_WARP_DRIVE' => 1,
                'GID_R_HYPER_DRIVE' => 0,
                'GID_R_PLASMA' => 0,
                'GID_R_LASER' => 2,
                'GID_R_JAMMING' => 0,
                'GID_R_COM' => 3,
                'GID_R_ASTROPHY' => 0,
                'GID_R_GRAVITON' => 0,
            ],
        ];

        foreach ($players as $i => $pData) {
            $session = str_pad(dechex($i + 1000), 12, '0', STR_PAD_LEFT);
            $playerId = $i + 1;

            $sql = "INSERT INTO {$this->dbPrefix}users (
                player_id, regdate, session, name, oname, email, lang, admin, validated, aktplanet,
                score1, score2, score3, place1, place2, place3, lastlogin, lastclick,
                GID_R_ESPIONAGE, GID_R_COMPUTER, GID_R_WEAPON, GID_R_SHIELD, GID_R_ARMOUR, GID_R_ENERGY,
                GID_R_HYPERSPACE, GID_R_COMBUST_DRIVE, GID_R_IMPULSE_DRIVE, GID_R_HYPER_DRIVE,
                GID_R_LASER_TECH, GID_R_ION_TECH, GID_R_PLASMA_TECH, GID_R_IGN, GID_R_EXPEDITION, GID_R_GRAVITON
            ) VALUES (
                $playerId, $now, '$session', '{$pData['name']}', '{$pData['oname']}', '{$pData['email']}', 
                '{$pData['lang']}', {$pData['admin']}, {$pData['validated']}, {$pData['aktplanet']},
                {$pData['score1']}, {$pData['score2']}, {$pData['score3']}, {$pData['place1']}, 
                {$pData['place2']}, {$pData['place3']}, $now, $now,
                {$pData['GID_R_ESPIONAGE']}, 0, 0, 0, 0, 0,
                0, 0, 0, {$pData['GID_R_HYPER_DRIVE']},
                0, 0, 0, 0, 0, 0
            )";
            $this->pdo->exec($sql);

            $this->players[$playerId] = [
                'id' => $playerId,
                'name' => $pData['name'],
                'oname' => $pData['oname'],
                'session' => $session,
                'planet_id' => 0,
            ];
        }

        // Create planets for each player
        $planetConfigs = [
            // Player 1 planets
            ['owner_id' => 1, 'name' => 'Home', 'g' => 1, 's' => 1, 'p' => 4, 'diameter' => 12200, 'temp' => 65, 'fields' => 14, 'maxfields' => 14, '1' => 5, '2' => 3, '3' => 2, '4' => 3, '212' => 1, '12' => 0, '14' => 3, '31' => 5, '21' => 4, '22' => 5, '23' => 4, '24' => 3, '44' => 1, '42' => 0, '43' => 0, '15' => 0],
            ['owner_id' => 1, 'name' => 'Colony A', 'g' => 1, 's' => 1, 'p' => 5, 'diameter' => 11000, 'temp' => 60, 'fields' => 12, 'maxfields' => 12, '1' => 3, '2' => 2, '3' => 1, '4' => 2, '212' => 0, '12' => 0, '14' => 2, '31' => 3, '21' => 2, '22' => 3, '23' => 2, '24' => 2, '44' => 0, '42' => 0, '43' => 0, '15' => 0],
            ['owner_id' => 1, 'name' => 'Colony B', 'g' => 1, 's' => 2, 'p' => 4, 'diameter' => 10500, 'temp' => 55, 'fields' => 10, 'maxfields' => 10, '1' => 2, '2' => 1, '3' => 1, '4' => 1, '212' => 0, '12' => 0, '14' => 1, '31' => 2, '21' => 1, '22' => 2, '23' => 1, '24' => 1, '44' => 0, '42' => 0, '43' => 0, '15' => 0],
            // Player 2 planets
            ['owner_id' => 2, 'name' => 'Home', 'g' => 1, 's' => 3, 'p' => 4, 'diameter' => 11800, 'temp' => 62, 'fields' => 13, 'maxfields' => 13, '1' => 4, '2' => 3, '3' => 2, '4' => 2, '212' => 1, '12' => 0, '14' => 2, '31' => 4, '21' => 3, '22' => 4, '23' => 3, '24' => 2, '44' => 1, '42' => 0, '43' => 0, '15' => 0],
            ['owner_id' => 2, 'name' => 'Colony X', 'g' => 1, 's' => 3, 'p' => 5, 'diameter' => 10800, 'temp' => 58, 'fields' => 11, 'maxfields' => 11, '1' => 2, '2' => 2, '3' => 1, '4' => 2, '212' => 0, '12' => 0, '14' => 1, '31' => 2, '21' => 1, '22' => 2, '23' => 1, '24' => 1, '44' => 0, '42' => 0, '43' => 0, '15' => 0],
            ['owner_id' => 2, 'name' => 'Colony Y', 'g' => 1, 's' => 4, 'p' => 4, 'diameter' => 12500, 'temp' => 70, 'fields' => 15, 'maxfields' => 15, '1' => 3, '2' => 1, '3' => 2, '4' => 1, '212' => 0, '12' => 0, '14' => 2, '31' => 1, '21' => 2, '22' => 3, '23' => 1, '24' => 2, '44' => 0, '42' => 0, '43' => 0, '15' => 0],
            // Player 3 planets
            ['owner_id' => 3, 'name' => 'Home', 'g' => 1, 's' => 5, 'p' => 4, 'diameter' => 11500, 'temp' => 55, 'fields' => 12, 'maxfields' => 12, '1' => 3, '2' => 2, '3' => 1, '4' => 2, '212' => 0, '12' => 0, '14' => 1, '31' => 3, '21' => 2, '22' => 3, '23' => 2, '24' => 1, '44' => 0, '42' => 0, '43' => 0, '15' => 0],
            ['owner_id' => 3, 'name' => 'Colony Alpha', 'g' => 1, 's' => 5, 'p' => 5, 'diameter' => 10200, 'temp' => 50, 'fields' => 9, 'maxfields' => 9, '1' => 1, '2' => 1, '3' => 0, '4' => 1, '212' => 0, '12' => 0, '14' => 0, '31' => 1, '21' => 0, '22' => 1, '23' => 1, '24' => 0, '44' => 0, '42' => 0, '43' => 0, '15' => 0],
            ['owner_id' => 3, 'name' => 'Colony Beta', 'g' => 1, 's' => 6, 'p' => 4, 'diameter' => 12000, 'temp' => 60, 'fields' => 14, 'maxfields' => 14, '1' => 2, '2' => 2, '3' => 1, '4' => 2, '212' => 1, '12' => 0, '14' => 1, '31' => 2, '21' => 1, '22' => 2, '23' => 2, '24' => 1, '44' => 0, '42' => 0, '43' => 0, '15' => 0],
        ];

        foreach ($planetConfigs as $pConfig) {
            $columns = array_map(fn($k) => "`$k`", array_keys($pConfig));
            $columnsStr = implode(',', $columns);
            $values = array_values($pConfig);
            $placeholders = str_repeat('?,', count($values) - 1) . '?';

            $stmt = $this->pdo->prepare("INSERT INTO {$this->dbPrefix}planets ($columnsStr) VALUES ($placeholders)");
            $stmt->execute($values);
            $planetId = (int)$this->pdo->lastInsertId();

            // Update aktplanet for the owner
            $this->pdo->exec("UPDATE {$this->dbPrefix}users SET aktplanet = $planetId WHERE player_id = {$pConfig['owner_id']}");

            // Track planet_id for each player
            foreach ($this->players as &$player) {
                if ($player['id'] === $pConfig['owner_id'] && $player['planet_id'] === 0) {
                    $player['planet_id'] = $planetId;
                    break;
                }
            }
            unset($player);
        }

        // Add some fleet to player 1
        $fleetData = [
            ['owner_id' => 1, 'start_planet' => 1, 'target_planet' => 4, 'mission' => 6, 'amount' => 5, 'tech_id' => 1],  // Small Cargo
            ['owner_id' => 1, 'start_planet' => 1, 'target_planet' => 7, 'mission' => 1, 'amount' => 3, 'tech_id' => 15], // Light Fighter
        ];

        foreach ($fleetData as $fData) {
            $columns = implode(',', array_keys($fData));
            $values = array_values($fData);
            $placeholders = str_repeat('?,', count($values) - 1) . '?';
            $stmt = $this->pdo->prepare("INSERT INTO {$this->dbPrefix}fleet ($columns) VALUES ($placeholders)");
            $stmt->execute($values);
        }

        // Add some messages for player 1
        $msgs = [
            ['owner_id' => 1, 'time' => $now - 3600, 'category' => 0, 'type' => 0, 'from_name' => 'System', 'subject' => 'Welcome!', 'text' => 'Welcome to OGame!', 'read' => 0],
            ['owner_id' => 1, 'time' => $now - 1800, 'category' => 0, 'type' => 0, 'from_name' => 'PlayerTwo', 'subject' => 'Hello', 'text' => 'Hi there!', 'read' => 1],
        ];

        foreach ($msgs as $mData) {
            $columns = implode(',', array_keys($mData));
            $values = array_values($mData);
            $placeholders = str_repeat('?,', count($values) - 1) . '?';
            $stmt = $this->pdo->prepare("INSERT INTO {$this->dbPrefix}messages ($columns) VALUES ($placeholders)");
            $stmt->execute($values);
        }

        // Add a note for player 1
        $noteSql = "INSERT INTO {$this->dbPrefix}notes (owner_id, time, subj, text) VALUES (1, $now, 'Test Note', 'This is a test note for PlayerOne.')";
        $this->pdo->exec($noteSql);

        return $this;
    }

    /**
     * Get player data by index (0-based)
     */
    public function getPlayer(int $index): array
    {
        $playerId = $index + 1;
        return $this->players[$playerId] ?? null;
    }

    /**
     * Get all players
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * Get universe settings
     */
    public function getUniData(): array
    {
        $result = $this->pdo->query("SELECT * FROM {$this->dbPrefix}uni")->fetch();
        return $result;
    }

    /**
     * Get the current timestamp for the fixture
     */
    public function getNow(): int
    {
        return time();
    }
}
