<?php

// Load game constants (GID_R_*, GID_B_*, GID_F_*, GID_D_*, GID_RC_*)
require_once __DIR__ . '/../game/core/techs.php';

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
                fid INTEGER DEFAULT 0,
                did INTEGER DEFAULT 0,
                rapid INTEGER DEFAULT 0,
                moons INTEGER DEFAULT 1,
                defrepair INTEGER DEFAULT 0,
                defrepair_delta INTEGER DEFAULT 0,
                usercount INTEGER DEFAULT 0,
                freeze INTEGER DEFAULT 0,
                news1 TEXT DEFAULT '',
                news2 TEXT DEFAULT '',
                news_until INTEGER DEFAULT 0,
                startdate INTEGER DEFAULT 0,
                battle_engine TEXT DEFAULT 'php',
                lang CHAR(4) DEFAULT 'en',
                hacks INTEGER DEFAULT 0,
                ext_board TEXT DEFAULT '',
                ext_discord TEXT DEFAULT '',
                ext_tutorial TEXT DEFAULT '',
                ext_rules TEXT DEFAULT '',
                ext_impressum TEXT DEFAULT '',
                php_battle INTEGER DEFAULT 1,
                battle_max INTEGER DEFAULT 1000000,
                force_lang INTEGER DEFAULT 0,
                start_dm INTEGER DEFAULT 0,
                max_werf INTEGER DEFAULT 1000,
                feedage INTEGER DEFAULT 0,
                modlist TEXT DEFAULT ''
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
                `".GID_R_ESPIONAGE."` TINYINT DEFAULT 0,
                `".GID_R_COMPUTER."` TINYINT DEFAULT 0,
                `".GID_R_WEAPON."` TINYINT DEFAULT 0,
                `".GID_R_SHIELD."` TINYINT DEFAULT 0,
                `".GID_R_ARMOUR."` TINYINT DEFAULT 0,
                `".GID_R_ENERGY."` TINYINT DEFAULT 0,
                `".GID_R_HYPERSPACE."` TINYINT DEFAULT 0,
                `".GID_R_COMBUST_DRIVE."` TINYINT DEFAULT 0,
                `".GID_R_IMPULSE_DRIVE."` TINYINT DEFAULT 0,
                `".GID_R_HYPER_DRIVE."` TINYINT DEFAULT 0,
                `".GID_R_LASER_TECH."` TINYINT DEFAULT 0,
                `".GID_R_ION_TECH."` TINYINT DEFAULT 0,
                `".GID_R_PLASMA_TECH."` TINYINT DEFAULT 0,
                `".GID_R_IGN."` TINYINT DEFAULT 0,
                `".GID_R_EXPEDITION."` TINYINT DEFAULT 0,
                `".GID_R_GRAVITON."` TINYINT DEFAULT 0,
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
                `".GID_B_METAL_MINE."` TINYINT DEFAULT 0,
                `".GID_B_CRYS_MINE."` TINYINT DEFAULT 0,
                `".GID_B_DEUT_SYNTH."` TINYINT DEFAULT 0,
                `".GID_B_SOLAR."` TINYINT DEFAULT 0,
                `".GID_B_FUSION."` TINYINT DEFAULT 0,
                `".GID_B_ROBOTS."` TINYINT DEFAULT 0,
                `".GID_B_NANITES."` TINYINT DEFAULT 0,
                `".GID_B_SHIPYARD."` TINYINT DEFAULT 0,
                `".GID_B_METAL_STOR."` TINYINT DEFAULT 0,
                `".GID_B_CRYS_STOR."` TINYINT DEFAULT 0,
                `".GID_B_DEUT_STOR."` TINYINT DEFAULT 0,
                `".GID_B_RES_LAB."` TINYINT DEFAULT 0,
                `".GID_B_TERRAFORMER."` TINYINT DEFAULT 0,
                `".GID_B_ALLY_DEPOT."` TINYINT DEFAULT 0,
                `".GID_B_LUNAR_BASE."` TINYINT DEFAULT 0,
                `".GID_B_PHALANX."` TINYINT DEFAULT 0,
                `".GID_B_JUMP_GATE."` TINYINT DEFAULT 0,
                `".GID_B_MISS_SILO."` TINYINT DEFAULT 0,
                `".GID_D_RL."` INT UNSIGNED DEFAULT 0,
                `".GID_D_LL."` INT UNSIGNED DEFAULT 0,
                `".GID_D_HL."` INT UNSIGNED DEFAULT 0,
                `".GID_D_GAUSS."` INT UNSIGNED DEFAULT 0,
                `".GID_D_ION."` INT UNSIGNED DEFAULT 0,
                `".GID_D_PLASMA."` INT UNSIGNED DEFAULT 0,
                `".GID_D_SDOME."` INT UNSIGNED DEFAULT 0,
                `".GID_D_LDOME."` INT UNSIGNED DEFAULT 0,
                `".GID_D_ABM."` INT UNSIGNED DEFAULT 0,
                `".GID_D_IPM."` INT UNSIGNED DEFAULT 0,
                `".GID_F_SC."` INT UNSIGNED DEFAULT 0,
                `".GID_F_LC."` INT UNSIGNED DEFAULT 0,
                `".GID_F_LF."` INT UNSIGNED DEFAULT 0,
                `".GID_F_HF."` INT UNSIGNED DEFAULT 0,
                `".GID_F_CRUISER."` INT UNSIGNED DEFAULT 0,
                `".GID_F_BATTLESHIP."` INT UNSIGNED DEFAULT 0,
                `".GID_F_COLON."` INT UNSIGNED DEFAULT 0,
                `".GID_F_RECYCLER."` INT UNSIGNED DEFAULT 0,
                `".GID_F_PROBE."` INT UNSIGNED DEFAULT 0,
                `".GID_F_BOMBER."` INT UNSIGNED DEFAULT 0,
                `".GID_F_SAT."` INT UNSIGNED DEFAULT 0,
                `".GID_F_DESTRO."` INT UNSIGNED DEFAULT 0,
                `".GID_F_DEATHSTAR."` INT UNSIGNED DEFAULT 0,
                `".GID_F_BATTLECRUISER."` INT UNSIGNED DEFAULT 0,
                `".GID_RC_METAL."` DOUBLE DEFAULT 0,
                `".GID_RC_CRYSTAL."` DOUBLE DEFAULT 0,
                `".GID_RC_DEUTERIUM."` DOUBLE DEFAULT 0,
                prod".GID_B_METAL_MINE." DOUBLE DEFAULT 1,
                prod".GID_B_CRYS_MINE." DOUBLE DEFAULT 1,
                prod".GID_B_DEUT_SYNTH." DOUBLE DEFAULT 1,
                prod".GID_B_SOLAR." DOUBLE DEFAULT 1,
                prod".GID_B_FUSION." DOUBLE DEFAULT 1,
                prod".GID_F_SAT." DOUBLE DEFAULT 1,
                lastpeek INT UNSIGNED DEFAULT 0,
                lastakt INT UNSIGNED DEFAULT 0,
                gate_until INT UNSIGNED DEFAULT 0,
                remove INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}coltab (
                t1_a INT UNSIGNED DEFAULT 1100,
                t1_b INT UNSIGNED DEFAULT 5000,
                t1_c INT UNSIGNED DEFAULT 1000,
                t2_a INT UNSIGNED DEFAULT 1100,
                t2_b INT UNSIGNED DEFAULT 5000,
                t2_c INT UNSIGNED DEFAULT 1000,
                t3_a INT UNSIGNED DEFAULT 1100,
                t3_b INT UNSIGNED DEFAULT 5000,
                t3_c INT UNSIGNED DEFAULT 1000,
                t4_a INT UNSIGNED DEFAULT 1100,
                t4_b INT UNSIGNED DEFAULT 5000,
                t4_c INT UNSIGNED DEFAULT 1000,
                t5_a INT UNSIGNED DEFAULT 1100,
                t5_b INT UNSIGNED DEFAULT 5000,
                t5_c INT UNSIGNED DEFAULT 1000
            )",
            "CREATE TABLE {$this->dbPrefix}queue (
                task_id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                type CHAR(20) DEFAULT 'Build',
                sub_id INT DEFAULT 0,
                obj_id INT DEFAULT 0,
                level INT DEFAULT 0,
                start INT UNSIGNED DEFAULT 0,
                end INT UNSIGNED DEFAULT 0,
                prio INT DEFAULT 0,
                freeze INT DEFAULT 0,
                frozen INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}buildqueue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                planet_id INT DEFAULT 0,
                list_id INT DEFAULT 0,
                tech_id INT DEFAULT 0,
                level INT DEFAULT 0,
                destroy INT DEFAULT 0,
                start INT UNSIGNED DEFAULT 0,
                end INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}fleet (
                fleet_id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                union_id INT DEFAULT 0,
                `".GID_RC_METAL."` DOUBLE DEFAULT 0,
                `".GID_RC_CRYSTAL."` DOUBLE DEFAULT 0,
                `".GID_RC_DEUTERIUM."` DOUBLE DEFAULT 0,
                fuel INT DEFAULT 0,
                mission INT DEFAULT 0,
                start_planet INT DEFAULT 0,
                target_planet INT DEFAULT 0,
                flight_time INT DEFAULT 0,
                deploy_time INT DEFAULT 0,
                ipm_amount INT DEFAULT 0,
                ipm_target INT DEFAULT 0,
                `".GID_F_SC."` INT UNSIGNED DEFAULT 0,
                `".GID_F_LC."` INT UNSIGNED DEFAULT 0,
                `".GID_F_LF."` INT UNSIGNED DEFAULT 0,
                `".GID_F_HF."` INT UNSIGNED DEFAULT 0,
                `".GID_F_CRUISER."` INT UNSIGNED DEFAULT 0,
                `".GID_F_BATTLESHIP."` INT UNSIGNED DEFAULT 0,
                `".GID_F_COLON."` INT UNSIGNED DEFAULT 0,
                `".GID_F_RECYCLER."` INT UNSIGNED DEFAULT 0,
                `".GID_F_PROBE."` INT UNSIGNED DEFAULT 0,
                `".GID_F_BOMBER."` INT UNSIGNED DEFAULT 0,
                `".GID_F_SAT."` INT UNSIGNED DEFAULT 0,
                `".GID_F_DESTRO."` INT UNSIGNED DEFAULT 0,
                `".GID_F_DEATHSTAR."` INT UNSIGNED DEFAULT 0,
                `".GID_F_BATTLECRUISER."` INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}messages (
                msg_id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                pm INT DEFAULT 0,
                msgfrom TEXT DEFAULT '',
                subj TEXT DEFAULT '',
                text TEXT DEFAULT '',
                shown INT DEFAULT 0,
                date INT UNSIGNED DEFAULT 0,
                planet_id INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}notes (
                note_id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                subj TEXT DEFAULT '',
                text TEXT DEFAULT '',
                textsize INT DEFAULT 0,
                prio INT DEFAULT 0,
                date INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}errors (
                error_id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                ip TEXT DEFAULT '',
                agent TEXT DEFAULT '',
                url TEXT DEFAULT '',
                text TEXT DEFAULT '',
                date INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}buddy (
                buddy_id INTEGER PRIMARY KEY AUTOINCREMENT,
                request_from INT DEFAULT 0,
                request_to INT DEFAULT 0,
                text TEXT DEFAULT '',
                accepted INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}ally (
                ally_id INTEGER PRIMARY KEY AUTOINCREMENT,
                tag TEXT DEFAULT '',
                name TEXT DEFAULT '',
                owner_id INT DEFAULT 0,
                homepage TEXT DEFAULT '',
                imglogo TEXT DEFAULT '',
                open INT DEFAULT 0,
                insertapp INT DEFAULT 0,
                exttext TEXT DEFAULT '',
                inttext TEXT DEFAULT '',
                apptext TEXT DEFAULT '',
                nextrank INT DEFAULT 0,
                old_tag TEXT DEFAULT '',
                old_name TEXT DEFAULT '',
                tag_until INT UNSIGNED DEFAULT 0,
                name_until INT UNSIGNED DEFAULT 0,
                score1 BIGINT UNSIGNED DEFAULT 0,
                score2 INT UNSIGNED DEFAULT 0,
                score3 INT UNSIGNED DEFAULT 0,
                place1 INT DEFAULT 0,
                place2 INT DEFAULT 0,
                place3 INT DEFAULT 0,
                oldscore1 BIGINT UNSIGNED DEFAULT 0,
                oldscore2 INT UNSIGNED DEFAULT 0,
                oldscore3 INT UNSIGNED DEFAULT 0,
                oldplace1 INT DEFAULT 0,
                oldplace2 INT DEFAULT 0,
                oldplace3 INT DEFAULT 0,
                scoredate INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}allyranks (
                rank_id INT PRIMARY KEY,
                ally_id INT DEFAULT 0,
                name TEXT DEFAULT '',
                rights INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}allyapps (
                app_id INTEGER PRIMARY KEY AUTOINCREMENT,
                ally_id INT DEFAULT 0,
                player_id INT DEFAULT 0,
                text TEXT DEFAULT '',
                date INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}browse (
                log_id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                url TEXT DEFAULT '',
                method TEXT DEFAULT '',
                getdata TEXT DEFAULT '',
                postdata TEXT DEFAULT '',
                date INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                msg_id INT DEFAULT 0,
                msgfrom TEXT DEFAULT '',
                subj TEXT DEFAULT '',
                text TEXT DEFAULT '',
                date INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}userlogs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                date INT UNSIGNED DEFAULT 0,
                type TEXT DEFAULT '',
                text TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}iplogs (
                log_id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip CHAR(16) DEFAULT '',
                user_id INT DEFAULT 0,
                reg INT DEFAULT 0,
                date INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}pranger (
                ban_id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_name CHAR(20) DEFAULT '',
                user_name CHAR(20) DEFAULT '',
                admin_id INT DEFAULT 0,
                user_id INT DEFAULT 0,
                ban_when INT UNSIGNED DEFAULT 0,
                ban_until INT UNSIGNED DEFAULT 0,
                reason TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}exptab (
                chance_success INT DEFAULT 0,
                depleted_min INT DEFAULT 0,
                depleted_med INT DEFAULT 0,
                depleted_max INT DEFAULT 0,
                chance_depleted_min INT DEFAULT 0,
                chance_depleted_med INT DEFAULT 0,
                chance_depleted_max INT DEFAULT 0,
                chance_alien INT DEFAULT 0,
                chance_pirates INT DEFAULT 0,
                chance_dm INT DEFAULT 0,
                chance_lost INT DEFAULT 0,
                chance_delay INT DEFAULT 0,
                chance_accel INT DEFAULT 0,
                chance_res INT DEFAULT 0,
                chance_fleet INT DEFAULT 0,
                dm_factor INT DEFAULT 0,
                score_cap1 INT DEFAULT 0,
                score_cap2 INT DEFAULT 0,
                score_cap3 INT DEFAULT 0,
                score_cap4 INT DEFAULT 0,
                score_cap5 INT DEFAULT 0,
                score_cap6 INT DEFAULT 0,
                score_cap7 INT DEFAULT 0,
                score_cap8 INT DEFAULT 0,
                limit_cap1 INT DEFAULT 0,
                limit_cap2 INT DEFAULT 0,
                limit_cap3 INT DEFAULT 0,
                limit_cap4 INT DEFAULT 0,
                limit_cap5 INT DEFAULT 0,
                limit_cap6 INT DEFAULT 0,
                limit_cap7 INT DEFAULT 0,
                limit_cap8 INT DEFAULT 0,
                limit_max INT DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}template (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                name CHAR(30) DEFAULT '',
                date INT UNSIGNED DEFAULT 0,
                `".GID_F_SC."` INT UNSIGNED DEFAULT 0,
                `".GID_F_LC."` INT UNSIGNED DEFAULT 0,
                `".GID_F_LF."` INT UNSIGNED DEFAULT 0,
                `".GID_F_HF."` INT UNSIGNED DEFAULT 0,
                `".GID_F_CRUISER."` INT UNSIGNED DEFAULT 0,
                `".GID_F_BATTLESHIP."` INT UNSIGNED DEFAULT 0,
                `".GID_F_COLON."` INT UNSIGNED DEFAULT 0,
                `".GID_F_RECYCLER."` INT UNSIGNED DEFAULT 0,
                `".GID_F_PROBE."` INT UNSIGNED DEFAULT 0,
                `".GID_F_BOMBER."` INT UNSIGNED DEFAULT 0,
                `".GID_F_SAT."` INT UNSIGNED DEFAULT 0,
                `".GID_F_DESTRO."` INT UNSIGNED DEFAULT 0,
                `".GID_F_DEATHSTAR."` INT UNSIGNED DEFAULT 0,
                `".GID_F_BATTLECRUISER."` INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}botvars (
                id INTEGER PRIMARY KEY,
                owner_id INT DEFAULT 0,
                var TEXT DEFAULT '',
                value TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}botstrat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT DEFAULT '',
                source TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}union (
                union_id INTEGER PRIMARY KEY AUTOINCREMENT,
                fleet_id INT DEFAULT 0,
                target_player INT DEFAULT 0,
                name CHAR(20) DEFAULT '',
                players TEXT DEFAULT ''
            )",
            "CREATE TABLE {$this->dbPrefix}battledata (
                battle_id INTEGER PRIMARY KEY AUTOINCREMENT,
                source TEXT DEFAULT '',
                title TEXT DEFAULT '',
                report TEXT DEFAULT '',
                date INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}fleetlogs (
                log_id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                target_id INT DEFAULT 0,
                union_id INT DEFAULT 0,
                p700 DOUBLE DEFAULT 0,
                p701 DOUBLE DEFAULT 0,
                p702 DOUBLE DEFAULT 0,
                `700` DOUBLE DEFAULT 0,
                `701` DOUBLE DEFAULT 0,
                `702` DOUBLE DEFAULT 0,
                fuel INT DEFAULT 0,
                mission INT DEFAULT 0,
                flight_time INT DEFAULT 0,
                deploy_time INT DEFAULT 0,
                start INT UNSIGNED DEFAULT 0,
                end INT UNSIGNED DEFAULT 0,
                origin_g INT DEFAULT 0,
                origin_s INT DEFAULT 0,
                origin_p INT DEFAULT 0,
                origin_type INT DEFAULT 0,
                target_g INT DEFAULT 0,
                target_s INT DEFAULT 0,
                target_p INT DEFAULT 0,
                target_type INT DEFAULT 0,
                ipm_amount INT DEFAULT 0,
                ipm_target INT DEFAULT 0,
                `1` INT UNSIGNED DEFAULT 0,
                `2` INT UNSIGNED DEFAULT 0,
                `3` INT UNSIGNED DEFAULT 0,
                `4` INT UNSIGNED DEFAULT 0,
                `5` INT UNSIGNED DEFAULT 0,
                `6` INT UNSIGNED DEFAULT 0,
                `7` INT UNSIGNED DEFAULT 0,
                `8` INT UNSIGNED DEFAULT 0,
                `9` INT UNSIGNED DEFAULT 0,
                `10` INT UNSIGNED DEFAULT 0,
                `11` INT UNSIGNED DEFAULT 0,
                `12` INT UNSIGNED DEFAULT 0,
                `13` INT UNSIGNED DEFAULT 0,
                `14` INT UNSIGNED DEFAULT 0,
                `15` INT UNSIGNED DEFAULT 0
            )",
            "CREATE TABLE {$this->dbPrefix}debug (
                error_id INTEGER PRIMARY KEY AUTOINCREMENT,
                owner_id INT DEFAULT 0,
                ip TEXT DEFAULT '',
                agent TEXT DEFAULT '',
                url TEXT DEFAULT '',
                text TEXT DEFAULT '',
                date INT UNSIGNED DEFAULT 0
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
        $this->pdo->exec("INSERT INTO {$this->dbPrefix}coltab (t1_a, t1_b, t1_c, t2_a, t2_b, t2_c, t3_a, t3_b, t3_c, t4_a, t4_b, t4_c, t5_a, t5_b, t5_c) VALUES (1100, 5000, 1000, 1100, 5000, 1000, 1100, 5000, 1000, 1100, 5000, 1000, 1100, 5000, 1000)");

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
                `".GID_R_ESPIONAGE."`, `".GID_R_COMPUTER."`, `".GID_R_WEAPON."`, `".GID_R_SHIELD."`, `".GID_R_ARMOUR."`, `".GID_R_ENERGY."`,
                `".GID_R_HYPERSPACE."`, `".GID_R_COMBUST_DRIVE."`, `".GID_R_IMPULSE_DRIVE."`, `".GID_R_HYPER_DRIVE."`,
                `".GID_R_LASER_TECH."`, `".GID_R_ION_TECH."`, `".GID_R_PLASMA_TECH."`, `".GID_R_IGN."`, `".GID_R_EXPEDITION."`, `".GID_R_GRAVITON."`
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
            ['owner_id' => 1, 'name' => 'Home', 'g' => 1, 's' => 1, 'p' => 4, 'diameter' => 12200, 'temp' => 65, 'fields' => 14, 'maxfields' => 14, GID_B_METAL_MINE => 5, GID_B_CRYS_MINE => 3, GID_B_DEUT_SYNTH => 2, GID_B_SOLAR => 3, GID_F_SAT => 1, GID_B_ROBOTS => 0, GID_B_SHIPYARD => 3, GID_B_METAL_STOR => 5, GID_B_METAL_MINE => 4, GID_B_CRYS_STOR => 5, GID_B_DEUT_STOR => 4, GID_B_MISS_SILO => 3, GID_B_ALLY_DEPOT => 1, GID_B_RES_LAB => 0, GID_B_TERRAFORMER => 0, GID_B_LUNAR_BASE => 0, GID_B_PHALANX => 0, GID_B_JUMP_GATE => 0, GID_B_LUNAR_BASE => 0],
            ['owner_id' => 1, 'name' => 'Colony A', 'g' => 1, 's' => 1, 'p' => 5, 'diameter' => 11000, 'temp' => 60, 'fields' => 12, 'maxfields' => 12, GID_B_METAL_MINE => 3, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_F_SAT => 0, GID_B_ROBOTS => 0, GID_B_SHIPYARD => 2, GID_B_METAL_STOR => 3, GID_B_METAL_MINE => 2, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 2, GID_B_MISS_SILO => 2, GID_B_ALLY_DEPOT => 0, GID_B_RES_LAB => 0, GID_B_TERRAFORMER => 0, GID_B_LUNAR_BASE => 0, GID_B_PHALANX => 0, GID_B_JUMP_GATE => 0, GID_B_LUNAR_BASE => 0],
            ['owner_id' => 1, 'name' => 'Colony B', 'g' => 1, 's' => 2, 'p' => 4, 'diameter' => 10500, 'temp' => 55, 'fields' => 10, 'maxfields' => 10, GID_B_METAL_MINE => 2, GID_B_CRYS_MINE => 1, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 1, GID_F_SAT => 0, GID_B_ROBOTS => 0, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 2, GID_B_METAL_MINE => 1, GID_B_CRYS_STOR => 2, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_RES_LAB => 0, GID_B_TERRAFORMER => 0, GID_B_LUNAR_BASE => 0, GID_B_PHALANX => 0, GID_B_JUMP_GATE => 0, GID_B_LUNAR_BASE => 0],
            // Player 2 planets
            ['owner_id' => 2, 'name' => 'Home', 'g' => 1, 's' => 3, 'p' => 4, 'diameter' => 11800, 'temp' => 62, 'fields' => 13, 'maxfields' => 13, GID_B_METAL_MINE => 4, GID_B_CRYS_MINE => 3, GID_B_DEUT_SYNTH => 2, GID_B_SOLAR => 2, GID_F_SAT => 1, GID_B_ROBOTS => 0, GID_B_SHIPYARD => 2, GID_B_METAL_STOR => 4, GID_B_METAL_MINE => 3, GID_B_CRYS_STOR => 4, GID_B_DEUT_STOR => 3, GID_B_MISS_SILO => 2, GID_B_ALLY_DEPOT => 1, GID_B_RES_LAB => 0, GID_B_TERRAFORMER => 0, GID_B_LUNAR_BASE => 0, GID_B_PHALANX => 0, GID_B_JUMP_GATE => 0, GID_B_LUNAR_BASE => 0],
            ['owner_id' => 2, 'name' => 'Colony X', 'g' => 1, 's' => 3, 'p' => 5, 'diameter' => 10800, 'temp' => 58, 'fields' => 11, 'maxfields' => 11, GID_B_METAL_MINE => 2, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_F_SAT => 0, GID_B_ROBOTS => 0, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 2, GID_B_METAL_MINE => 1, GID_B_CRYS_STOR => 2, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_RES_LAB => 0, GID_B_TERRAFORMER => 0, GID_B_LUNAR_BASE => 0, GID_B_PHALANX => 0, GID_B_JUMP_GATE => 0, GID_B_LUNAR_BASE => 0],
            ['owner_id' => 2, 'name' => 'Colony Y', 'g' => 1, 's' => 4, 'p' => 4, 'diameter' => 12500, 'temp' => 70, 'fields' => 15, 'maxfields' => 15, GID_B_METAL_MINE => 3, GID_B_CRYS_MINE => 1, GID_B_DEUT_SYNTH => 2, GID_B_SOLAR => 1, GID_F_SAT => 0, GID_B_ROBOTS => 0, GID_B_SHIPYARD => 2, GID_B_METAL_STOR => 1, GID_B_METAL_MINE => 2, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 2, GID_B_ALLY_DEPOT => 0, GID_B_RES_LAB => 0, GID_B_TERRAFORMER => 0, GID_B_LUNAR_BASE => 0, GID_B_PHALANX => 0, GID_B_JUMP_GATE => 0, GID_B_LUNAR_BASE => 0],
            // Player 3 planets
            ['owner_id' => 3, 'name' => 'Home', 'g' => 1, 's' => 5, 'p' => 4, 'diameter' => 11500, 'temp' => 55, 'fields' => 12, 'maxfields' => 12, GID_B_METAL_MINE => 3, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_F_SAT => 0, GID_B_ROBOTS => 0, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 3, GID_B_METAL_MINE => 2, GID_B_CRYS_STOR => 3, GID_B_DEUT_STOR => 2, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_RES_LAB => 0, GID_B_TERRAFORMER => 0, GID_B_LUNAR_BASE => 0, GID_B_PHALANX => 0, GID_B_JUMP_GATE => 0, GID_B_LUNAR_BASE => 0],
            ['owner_id' => 3, 'name' => 'Colony Alpha', 'g' => 1, 's' => 5, 'p' => 5, 'diameter' => 10200, 'temp' => 50, 'fields' => 9, 'maxfields' => 9, GID_B_METAL_MINE => 1, GID_B_CRYS_MINE => 1, GID_B_DEUT_SYNTH => 0, GID_B_SOLAR => 1, GID_F_SAT => 0, GID_B_ROBOTS => 0, GID_B_SHIPYARD => 0, GID_B_METAL_STOR => 1, GID_B_METAL_MINE => 0, GID_B_CRYS_STOR => 1, GID_B_DEUT_STOR => 1, GID_B_MISS_SILO => 0, GID_B_ALLY_DEPOT => 0, GID_B_RES_LAB => 0, GID_B_TERRAFORMER => 0, GID_B_LUNAR_BASE => 0, GID_B_PHALANX => 0, GID_B_JUMP_GATE => 0, GID_B_LUNAR_BASE => 0],
            ['owner_id' => 3, 'name' => 'Colony Beta', 'g' => 1, 's' => 6, 'p' => 4, 'diameter' => 12000, 'temp' => 60, 'fields' => 14, 'maxfields' => 14, GID_B_METAL_MINE => 2, GID_B_CRYS_MINE => 2, GID_B_DEUT_SYNTH => 1, GID_B_SOLAR => 2, GID_F_SAT => 1, GID_B_ROBOTS => 0, GID_B_SHIPYARD => 1, GID_B_METAL_STOR => 2, GID_B_METAL_MINE => 1, GID_B_CRYS_STOR => 2, GID_B_DEUT_STOR => 2, GID_B_MISS_SILO => 1, GID_B_ALLY_DEPOT => 0, GID_B_RES_LAB => 0, GID_B_TERRAFORMER => 0, GID_B_LUNAR_BASE => 0, GID_B_PHALANX => 0, GID_B_JUMP_GATE => 0, GID_B_LUNAR_BASE => 0],
        ];

        foreach ($planetConfigs as $pConfig) {
            $columns = [];
            foreach (array_keys($pConfig) as $k) {
                $columns[] = is_numeric($k) ? "`$k`" : "`$k`";
            }
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
            ['owner_id' => 1, 'start_planet' => 1, 'target_planet' => 4, 'mission' => 6, 'amount' => 5, 'tech_id' => GID_F_SC],  // Small Cargo
            ['owner_id' => 1, 'start_planet' => 1, 'target_planet' => 7, 'mission' => 1, 'amount' => 3, 'tech_id' => GID_F_LF], // Light Fighter
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
            ['owner_id' => 1, 'pm' => 0, 'msgfrom' => 'System', 'subj' => 'Welcome!', 'text' => 'Welcome to OGame!', 'shown' => 0, 'date' => $now - 3600],
            ['owner_id' => 1, 'pm' => 0, 'msgfrom' => 'PlayerTwo', 'subj' => 'Hello', 'text' => 'Hi there!', 'shown' => 1, 'date' => $now - 1800],
        ];

        foreach ($msgs as $mData) {
            $columns = implode(',', array_keys($mData));
            $values = array_values($mData);
            $placeholders = str_repeat('?,', count($values) - 1) . '?';
            $stmt = $this->pdo->prepare("INSERT INTO {$this->dbPrefix}messages ($columns) VALUES ($placeholders)");
            $stmt->execute($values);
        }

        // Add a note for player 1
        $noteSql = "INSERT INTO {$this->dbPrefix}notes (owner_id, subj, text, textsize, prio, date) VALUES (1, 'Test Note', 'This is a test note for PlayerOne.', 34, 0, $now)";
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
