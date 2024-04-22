<?php

// Game Tables.
// They used to be inside install.php, but then they were separated so that you could check the integrity of the database from the admin.

// Table Structure.
// -------------------------------------------------------------------------------------------------------------------------

$tab_uni = array (        // Universe
    'num'=>'INT PRIMARY KEY','speed'=>'FLOAT','fspeed'=>'FLOAT','galaxies'=>'INT','systems'=>'INT','maxusers'=>'INT','acs'=>'INT','fid'=>'INT','did'=>'INT','rapid'=>'INT','moons'=>'INT','defrepair'=>'INT','defrepair_delta'=>'INT','usercount'=>'INT','freeze'=>'INT',
    'news1'=>'TEXT', 'news2'=>'TEXT', 'news_until'=>'INT UNSIGNED', 'startdate'=>'INT UNSIGNED', 'battle_engine'=>'TEXT', 'lang'=>'CHAR(4)', 'hacks'=>'INT',
    'ext_board'=>'TEXT', 'ext_discord'=>'TEXT', 'ext_tutorial'=>'TEXT', 'ext_rules'=>'TEXT', 'ext_impressum'=>'TEXT', 'php_battle'=>'INT', 'force_lang'=>'INT'
);

$tab_users = array (    // Users
    'player_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'regdate'=>'INT UNSIGNED', 'ally_id'=>'INT', 'joindate'=>'INT UNSIGNED', 'allyrank'=>'INT', 'session'=>'CHAR(12)', 'private_session'=>'CHAR(32)', 'name'=>'CHAR(20)', 'oname'=>'CHAR(20)', 'name_changed'=>'INT', 'name_until'=>'INT UNSIGNED', 'password'=>'CHAR(32)', 'temp_pass'=>'CHAR(32)', 'pemail'=>'CHAR(50)', 'email'=>'CHAR(50)',
    'email_changed'=>'INT', 'email_until'=>'INT UNSIGNED', 'disable'=>'INT', 'disable_until'=>'INT UNSIGNED', 'vacation'=>'INT', 'vacation_until'=>'INT UNSIGNED', 'banned'=>'INT', 'banned_until'=>'INT UNSIGNED', 'noattack'=>'INT', 'noattack_until'=>'INT UNSIGNED',
    'lastlogin'=>'INT UNSIGNED', 'lastclick'=>'INT UNSIGNED', 'ip_addr'=>'CHAR(15)', 'validated'=>'INT', 'validatemd'=>'CHAR(32)', 'hplanetid'=>'INT', 'admin'=>'INT', 'sortby'=>'INT', 'sortorder'=>'INT',
    'skin'=>'CHAR(80)', 'useskin'=>'INT', 'deact_ip'=>'INT', 'maxspy'=>'INT', 'maxfleetmsg'=>'INT', 'lang'=>'CHAR(4)', 'aktplanet'=>'INT',
    'dm'=>'INT UNSIGNED', 'dmfree'=>'INT UNSIGNED', 'sniff'=>'INT', 'debug'=>'INT', 'trader'=>'INT', 'rate_m'=>'DOUBLE', 'rate_k'=>'DOUBLE', 'rate_d'=>'DOUBLE',
    'score1'=>'BIGINT', 'score2'=>'INT', 'score3'=>'INT', 'place1'=>'INT', 'place2'=>'INT', 'place3'=>'INT',
    'oldscore1'=>'BIGINT', 'oldscore2'=>'INT', 'oldscore3'=>'INT', 'oldplace1'=>'INT', 'oldplace2'=>'INT', 'oldplace3'=>'INT', 'scoredate'=>'INT UNSIGNED',
    'r106'=>'INT', 'r108'=>'INT', 'r109'=>'INT', 'r110'=>'INT', 'r111'=>'INT', 'r113'=>'INT', 'r114'=>'INT', 'r115'=>'INT', 'r117'=>'INT', 'r118'=>'INT', 'r120'=>'INT', 'r121'=>'INT', 'r122'=>'INT', 'r123'=>'INT', 'r124'=>'INT', 'r199'=>'INT',
    'flags' => 'INT UNSIGNED'
);

$tab_planets = array (    // Planets
    'planet_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'name'=>'CHAR(20)', 'type'=>'INT', 'g'=>'INT', 's'=>'INT', 'p'=>'INT', 'owner_id'=>'INT', 'diameter'=>'INT', 'temp'=>'INT', 'fields'=>'INT', 'maxfields'=>'INT', 'date'=>'INT UNSIGNED',
    'b1'=>'INT', 'b2'=>'INT', 'b3'=>'INT', 'b4'=>'INT', 'b12'=>'INT', 'b14'=>'INT', 'b15'=>'INT', 'b21'=>'INT', 'b22'=>'INT', 'b23'=>'INT', 'b24'=>'INT', 'b31'=>'INT', 'b33'=>'INT', 'b34'=>'INT', 'b41'=>'INT', 'b42'=>'INT', 'b43'=>'INT', 'b44'=>'INT',
    'd401'=>'INT', 'd402'=>'INT', 'd403'=>'INT', 'd404'=>'INT', 'd405'=>'INT', 'd406'=>'INT', 'd407'=>'INT', 'd408'=>'INT', 'd502'=>'INT', 'd503'=>'INT',
    'f202'=>'INT', 'f203'=>'INT', 'f204'=>'INT', 'f205'=>'INT', 'f206'=>'INT', 'f207'=>'INT', 'f208'=>'INT', 'f209'=>'INT', 'f210'=>'INT', 'f211'=>'INT', 'f212'=>'INT', 'f213'=>'INT', 'f214'=>'INT', 'f215'=>'INT',
    'm'=>'DOUBLE', 'k'=>'DOUBLE', 'd'=>'DOUBLE', 'mprod'=>'DOUBLE', 'kprod'=>'DOUBLE', 'dprod'=>'DOUBLE', 'sprod'=>'DOUBLE', 'fprod'=>'DOUBLE', 'ssprod'=>'DOUBLE', 'lastpeek'=>'INT UNSIGNED', 'lastakt'=>'INT UNSIGNED', 'gate_until'=>'INT UNSIGNED', 'remove'=>'INT UNSIGNED'
);

$tab_ally = array (    // Alliances
    'ally_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'tag'=>'TEXT', 'name'=>'TEXT', 'owner_id'=>'INT', 'homepage'=>'TEXT', 'imglogo'=>'TEXT', 'open'=>'INT', 'insertapp'=>'INT', 'exttext'=>'TEXT', 'inttext'=>'TEXT', 'apptext'=>'TEXT', 'nextrank'=>'INT', 'old_tag'=>'TEXT', 'old_name'=>'TEXT', 'tag_until'=>'INT UNSIGNED', 'name_until'=>'INT UNSIGNED',
    'score1'=>'BIGINT UNSIGNED', 'score2'=>'INT UNSIGNED', 'score3'=>'INT UNSIGNED', 'place1'=>'INT', 'place2'=>'INT', 'place3'=>'INT',
    'oldscore1'=>'BIGINT UNSIGNED', 'oldscore2'=>'INT UNSIGNED', 'oldscore3'=>'INT UNSIGNED', 'oldplace1'=>'INT', 'oldplace2'=>'INT', 'oldplace3'=>'INT', 'scoredate'=>'INT UNSIGNED'
);

$tab_allyranks = array (    // Alliance ranks
    'rank_id'=>'INT', 'ally_id'=>'INT', 'name'=>'TEXT', 'rights'=>'INT'
);

$tab_allyapps = array (    // Alliance applications
    'app_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'ally_id'=>'INT', 'player_id'=>'INT', 'text'=>'TEXT', 'date'=>'INT UNSIGNED'
);

$tab_buddy = array (    // Buddies
    'buddy_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'request_from'=>'INT', 'request_to'=>'INT', 'text'=>'TEXT', 'accepted'=>'INT'
);

$tab_messages = array (    // Messages
    'msg_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'pm'=>'INT', 'msgfrom'=>'TEXT', 'subj'=>'TEXT', 'text'=>'TEXT', 'shown'=>'INT', 'date'=>'INT UNSIGNED', 'planet_id'=>'INT'
);

$tab_notes = array (    // Notes
    'note_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'subj'=>'TEXT', 'text'=>'TEXT', 'textsize'=>'INT', 'prio'=>'INT', 'date'=>'INT UNSIGNED'
);

$tab_errors = array (    // Errors
    'error_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'ip'=>'TEXT', 'agent'=>'TEXT', 'url'=>'TEXT', 'text'=>'TEXT', 'date'=>'INT UNSIGNED'
);

$tab_debug = array (    // Debug messages
    'error_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'ip'=>'TEXT', 'agent'=>'TEXT', 'url'=>'TEXT', 'text'=>'TEXT', 'date'=>'INT UNSIGNED'
);

$tab_browse = array (    // Browser history
    'log_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'url'=>'TEXT', 'method'=>'TEXT', 'getdata'=>'TEXT', 'postdata'=>'TEXT', 'date'=>'INT UNSIGNED'
);

$tab_queue = array (    // Event queue
    'task_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'type'=>'CHAR(20)', 'sub_id'=>'INT', 'obj_id'=>'INT', 'level'=>'INT', 'start'=>'INT UNSIGNED', 'end'=>'INT UNSIGNED', 'prio'=>'INT'
);

$tab_buildqueue = array (    // Build queue
    'id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'planet_id'=>'INT', 'list_id'=>'INT', 'tech_id'=>'INT', 'level'=>'INT', 'destroy'=>'INT', 'start'=>'INT UNSIGNED', 'end'=>'INT UNSIGNED',
);

$tab_fleet = array (    // Fleet
    'fleet_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'union_id'=>'INT', 'm'=>'DOUBLE', 'k'=>'DOUBLE', 'd'=>'DOUBLE', 'fuel'=>'INT', 'mission'=>'INT', 'start_planet'=>'INT', 'target_planet'=>'INT', 'flight_time'=>'INT', 'deploy_time'=>'INT',
    'ipm_amount'=>'INT', 'ipm_target'=>'INT', 'ship202'=>'INT', 'ship203'=>'INT', 'ship204'=>'INT', 'ship205'=>'INT', 'ship206'=>'INT', 'ship207'=>'INT', 'ship208'=>'INT', 'ship209'=>'INT', 'ship210'=>'INT', 'ship211'=>'INT', 'ship212'=>'INT', 'ship213'=>'INT', 'ship214'=>'INT', 'ship215'=>'INT'
);

$tab_union = array (    // ACS
    'union_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'fleet_id'=>'INT', 'target_player'=>'INT', 'name'=>'CHAR(20)', 'players'=>'TEXT'
);

$tab_battledata = array (    // Data for the battle engine (deprecated)
    'battle_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'source'=>'TEXT', 'title' => 'TEXT', 'report' => 'TEXT', 'date'=>'INT UNSIGNED'
);

$tab_fleetlogs = array (    // Flight logs
    'log_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'target_id'=>'INT', 'union_id'=>'INT', 'pm'=>'DOUBLE', 'pk'=>'DOUBLE', 'pd'=>'DOUBLE', 'm'=>'DOUBLE', 'k'=>'DOUBLE', 'd'=>'DOUBLE', 'fuel'=>'INT', 'mission'=>'INT', 'flight_time'=>'INT', 'deploy_time'=>'INT', 'start'=>'INT UNSIGNED', 'end'=>'INT UNSIGNED',
    'origin_g'=>'INT', 'origin_s'=>'INT', 'origin_p'=>'INT', 'origin_type'=>'INT', 'target_g'=>'INT', 'target_s'=>'INT', 'target_p'=>'INT', 'target_type'=>'INT',
    'ipm_amount'=>'INT', 'ipm_target'=>'INT', 'ship202'=>'INT', 'ship203'=>'INT', 'ship204'=>'INT', 'ship205'=>'INT', 'ship206'=>'INT', 'ship207'=>'INT', 'ship208'=>'INT', 'ship209'=>'INT', 'ship210'=>'INT', 'ship211'=>'INT', 'ship212'=>'INT', 'ship213'=>'INT', 'ship214'=>'INT', 'ship215'=>'INT'
);

$tab_iplogs = array (    // IP Logs
    'log_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'ip'=>'CHAR(16)', 'user_id'=>'INT', 'reg'=>'INT', 'date'=>'INT UNSIGNED'
);

$tab_pranger = array (    // Pillar of Shame
    'ban_id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'admin_name'=>'CHAR(20)', 'user_name'=>'CHAR(20)', 'admin_id'=>'INT', 'user_id'=>'INT', 'ban_when'=>'INT UNSIGNED', 'ban_until'=>'INT UNSIGNED', 'reason'=>'TEXT'
);

$tab_exptab = array (    // Expedition settings (can be changed in admin)
    'chance_success'=>'INT', 'depleted_min'=>'INT', 'depleted_med'=>'INT', 'depleted_max'=>'INT', 'chance_depleted_min'=>'INT', 'chance_depleted_med'=>'INT', 'chance_depleted_max'=>'INT',
    'chance_alien'=>'INT', 'chance_pirates'=>'INT', 'chance_dm'=>'INT', 'chance_lost'=>'INT', 'chance_delay'=>'INT', 'chance_accel'=>'INT', 'chance_res'=>'INT', 'chance_fleet'=>'INT'
);

// After discussions in Discord we haven't come to a consensus on what parameters should be written in planets.php for new colonies.
// And since there is no consensus, the programmer will always find a way out by adding a setting :-)
$tab_coltab = array (    // Colonization settings (can be changed in admin)
    't1_a'=>'INT UNSIGNED', 't1_b'=>'INT UNSIGNED', 't1_c'=>'INT UNSIGNED',
    't2_a'=>'INT UNSIGNED', 't2_b'=>'INT UNSIGNED', 't2_c'=>'INT UNSIGNED',
    't3_a'=>'INT UNSIGNED', 't3_b'=>'INT UNSIGNED', 't3_c'=>'INT UNSIGNED',
    't4_a'=>'INT UNSIGNED', 't4_b'=>'INT UNSIGNED', 't4_c'=>'INT UNSIGNED',
    't5_a'=>'INT UNSIGNED', 't5_b'=>'INT UNSIGNED', 't5_c'=>'INT UNSIGNED',
);

$tab_template = array (    // Fleet templates
    'id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'name'=>'CHAR(30)', 'date'=>'INT UNSIGNED',
    'ship202'=>'INT', 'ship203'=>'INT', 'ship204'=>'INT', 'ship205'=>'INT', 'ship206'=>'INT', 'ship207'=>'INT', 'ship208'=>'INT', 'ship209'=>'INT', 'ship210'=>'INT', 'ship211'=>'INT', 'ship212'=>'INT', 'ship213'=>'INT', 'ship214'=>'INT', 'ship215'=>'INT',
);

$tab_botvars = array (    // Bot variables
    'id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'var'=>'TEXT', 'value'=>'TEXT'
);

$tab_userlogs = array (    // Logs of user (and operator) actions. Triggered when a user presses something
    'id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'owner_id'=>'INT', 'date'=>'INT UNSIGNED', 'type'=>'TEXT', 'text'=>'TEXT',
);

$tab_botstrat = array (    // Bot strategies
    'id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'name'=>'TEXT', 'source'=>'TEXT',
);

$tab_mods = array (    // Modifications
    'id'=>'INT AUTO_INCREMENT PRIMARY KEY', 'var'=>'TEXT', 'value'=>'TEXT'
);

$tabs = array (
    'uni' => &$tab_uni,
    'users' => &$tab_users,
    'planets' => &$tab_planets,
    'ally' => &$tab_ally,
    'allyranks' => &$tab_allyranks,
    'allyapps' => &$tab_allyapps,
    'buddy' => &$tab_buddy,
    'messages' => &$tab_messages,
    'notes' => &$tab_notes,
    'errors' => &$tab_errors,
    'debug' => &$tab_debug,
    'browse' => &$tab_browse,
    'queue' => &$tab_queue,
    'buildqueue' => &$tab_buildqueue,
    'fleet' => &$tab_fleet,
    'union' => &$tab_union,
    'battledata' => &$tab_battledata,
    'fleetlogs' => &$tab_fleetlogs,
    'iplogs' => &$tab_iplogs,
    'pranger' => &$tab_pranger,
    'exptab' => &$tab_exptab,
    'coltab' => &$tab_coltab,
    'template' => &$tab_template,
    'botvars' => &$tab_botvars,
    'userlogs' => &$tab_userlogs,
    'botstrat' => &$tab_botstrat,
    'mods' => &$tab_mods,
);

?>