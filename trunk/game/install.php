<?php

// Установочный файл.
// Создает все необходимые таблицы в базе данных, а также файл конфигурации config.php, для доступа к базе.
// Не работет, если файл config.php создан.

$InstallError = "<font color=gold>Используйте подсказки при наведении мышкой на выбранные параметры</font>";

require_once "db.php";

function hostname () {
    $host = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["SCRIPT_NAME"];
    $pos = strrpos ( $host, "/game/install.php" );
    return substr ( $host, 0, $pos+1 );
}

ob_start ();

// Проверить настройки вселенной.
function CheckParameters ()
{
    global $InstallError;

    return TRUE;
}

// Проверить, если файл конфигурации уже создан - редирект на главную страницу.
if (file_exists ("config.php"))
{
    echo "<html><head><meta http-equiv='refresh' content='0;url=index.php' /></head><body></body></html>";
    ob_end_flush ();
    exit ();
}

function gen_trivial_password ($len = 8)
{
    $r = '';
    for($i=0; $i<$len; $i++)
        $r .= chr(rand(0, 25) + ord('a'));
    return $r;
}

// Сохранить настройки.
if ( key_exists("install", $_POST) && CheckParameters() )
{
    $tabs = array ('uni','users','planets','ally','allyranks','allyapps','buddy','messages','notes','errors','debug','browse','queue','fleet','union','battledata','fleetlogs','iplogs','pranger','exptab');
    $unicols = array ('num','speed','galaxies','systems','maxusers','acs','fid','did','rapid','moons','defrepair','defrepair_delta','usercount','freeze',
                              'news1', 'news2', 'news_until', 'startdate', 'battle_engine' );
    $unitype = array ('INT','FLOAT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT',
                              'TEXT', 'TEXT', 'INT UNSIGNED', 'INT UNSIGNED', 'TEXT' );
    $usercols = array ( 'player_id', 'regdate', 'ally_id', 'joindate', 'allyrank', 'session', 'private_session', 'name', 'oname', 'name_changed', 'name_until', 'password', 'temp_pass', 'pemail', 'email',
                        'email_changed', 'email_until', 'disable', 'disable_until', 'vacation', 'vacation_until', 'banned', 'banned_until', 'noattack', 'noattack_until',
                        'lastlogin', 'lastclick', 'ip_addr', 'validated', 'validatemd', 'hplanetid', 'admin', 'sortby', 'sortorder',
                        'skin', 'useskin', 'deact_ip', 'maxspy', 'maxfleetmsg', 'lang', 'aktplanet',
                        'dm', 'dmfree', 'sniff', 'debug', 'redesign', 'trader', 'rate_m', 'rate_k', 'rate_d',
                        'score1', 'score2', 'score3', 'place1', 'place2', 'place3',
                        'oldscore1', 'oldscore2', 'oldscore3', 'oldplace1', 'oldplace2', 'oldplace3', 'scoredate',
                        'r106', 'r108', 'r109', 'r110', 'r111', 'r113', 'r114', 'r115', 'r117', 'r118', 'r120', 'r121', 'r122', 'r123', 'r124', 'r199' );
    $usertype = array (  'INT AUTO_INCREMENT PRIMARY KEY', 'INT UNSIGNED', 'INT', 'INT', 'INT UNSIGNED', 'CHAR(12)', 'CHAR(32)', 'CHAR(20)', 'CHAR(20)', 'INT', 'INT UNSIGNED', 'CHAR(32)', 'CHAR(32)', 'CHAR(50)', 'CHAR(50)',
                         'INT', 'INT UNSIGNED', 'INT', 'INT UNSIGNED', 'INT', 'INT UNSIGNED', 'INT', 'INT UNSIGNED', 'INT', 'INT UNSIGNED', 
                         'INT UNSIGNED', 'INT UNSIGNED', 'CHAR(15)', 'INT', 'CHAR(32)', 'INT', 'INT', 'INT', 'INT',
                         'CHAR(80)', 'INT', 'INT', 'INT', 'INT', 'CHAR(4)', 'INT',
                         'INT UNSIGNED', 'INT UNSIGNED', 'INT', 'INT', 'INT', 'INT', 'DOUBLE', 'DOUBLE', 'DOUBLE', 
                         'BIGINT UNSIGNED', 'INT UNSIGNED', 'INT UNSIGNED', 'INT', 'INT', 'INT', 
                         'BIGINT UNSIGNED', 'INT UNSIGNED', 'INT UNSIGNED', 'INT', 'INT', 'INT', 'INT UNSIGNED',
                         'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT' );
    $planetcols = array ( 'planet_id', 'name', 'type', 'g', 's', 'p', 'owner_id', 'diameter', 'temp', 'fields', 'maxfields', 'date', 
                          'b1', 'b2', 'b3', 'b4', 'b12', 'b14', 'b15', 'b21', 'b22', 'b23', 'b24', 'b31', 'b33', 'b34', 'b41', 'b42', 'b43', 'b44',
                          'd401', 'd402', 'd403', 'd404', 'd405', 'd406', 'd407', 'd408', 'd502', 'd503',
                          'f202', 'f203', 'f204', 'f205', 'f206', 'f207', 'f208', 'f209', 'f210', 'f211', 'f212', 'f213', 'f214', 'f215',
                          'm', 'k', 'd', 'mprod', 'kprod', 'dprod', 'sprod', 'fprod', 'ssprod', 'lastpeek', 'lastakt', 'gate_until', 'remove' );
    $planettype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'CHAR(20)', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT UNSIGNED',
                          'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 
                          'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT',
                          'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 
                          'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'INT UNSIGNED', 'INT UNSIGNED', 'INT UNSIGNED', 'INT UNSIGNED' );
    $allycols = array ( 'ally_id', 'tag', 'name', 'owner_id', 'homepage', 'imglogo', 'open', 'insertapp', 'exttext', 'inttext', 'apptext', 'nextrank', 'old_tag', 'old_name', 'tag_until', 'name_until',
                        'score1', 'score2', 'score3', 'place1', 'place2', 'place3',
                        'oldscore1', 'oldscore2', 'oldscore3', 'oldplace1', 'oldplace2', 'oldplace3', 'scoredate' );
    $allytype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'TEXT', 'TEXT', 'INT', 'TEXT', 'TEXT', 'INT', 'INT', 'TEXT', 'TEXT', 'TEXT', 'INT', 'TEXT', 'TEXT', 'INT UNSIGNED', 'INT UNSIGNED',
                        'BIGINT UNSIGNED', 'INT UNSIGNED', 'INT UNSIGNED', 'INT', 'INT', 'INT', 
                        'BIGINT UNSIGNED', 'INT UNSIGNED', 'INT UNSIGNED', 'INT', 'INT', 'INT', 'INT UNSIGNED' );
    $rankscols = array ( 'rank_id', 'ally_id', 'name', 'rights' );
    $rankstype = array ( 'INT', 'INT', 'TEXT', 'INT' );
    $appscols = array ( 'app_id', 'ally_id', 'player_id', 'text', 'date' );
    $appstype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'INT', 'TEXT', 'INT UNSIGNED' );
    $buddycols = array ( 'buddy_id', 'request_from', 'request_to', 'text', 'accepted' );
    $buddytype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'INT', 'TEXT', 'INT' );
    $messagescols = array ( 'msg_id', 'owner_id', 'pm', 'msgfrom', 'subj', 'text', 'shown', 'date' );
    $messagestype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'INT', 'TEXT', 'TEXT', 'TEXT', 'INT', 'INT UNSIGNED' );
    $notescols = array ( 'note_id', 'owner_id', 'subj', 'text', 'textsize', 'prio', 'date' );
    $notestype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'TEXT', 'TEXT', 'INT', 'INT', 'INT UNSIGNED' );
    $errorscols = array ( 'error_id', 'owner_id', 'ip', 'agent', 'url', 'text', 'date' );
    $errorstype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'TEXT', 'TEXT', 'TEXT', 'TEXT', 'INT UNSIGNED' );
    $debugcols = array ( 'error_id', 'owner_id', 'ip', 'agent', 'url', 'text', 'date' );
    $debugtype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'TEXT', 'TEXT', 'TEXT', 'TEXT', 'INT UNSIGNED' );
    $browsecols = array ( 'log_id', 'owner_id', 'url', 'method', 'getdata', 'postdata', 'date' );
    $browsetype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'TEXT', 'TEXT', 'TEXT', 'TEXT', 'INT UNSIGNED' );
    $queuecols = array ( 'task_id', 'owner_id', 'type', 'sub_id', 'obj_id', 'level', 'start', 'end', 'prio' );
    $queuetype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'CHAR(20)', 'INT', 'INT', 'INT', 'INT UNSIGNED', 'INT UNSIGNED', 'INT' );
    $fleetcols = array ( 'fleet_id', 'owner_id', 'union_id', 'm', 'k', 'd', 'fuel', 'mission', 'start_planet', 'target_planet', 'flight_time', 'deploy_time',
                         'ipm_amount', 'ipm_target', 'ship202', 'ship203', 'ship204', 'ship205', 'ship206', 'ship207', 'ship208', 'ship209', 'ship210', 'ship211', 'ship212', 'ship213', 'ship214', 'ship215' );
    $fleettype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'INT', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'INT', 'INT', 'INT', 'INT', 'INT',
                         'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT' );
    $unioncols = array ( 'union_id', 'fleet_id', 'target_player', 'name', 'players' );
    $uniontype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'INT', 'CHAR(20)', 'TEXT' );
    $battledatacols = array ( 'battle_id', 'source', 'result' );
    $battledatatype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'TEXT', 'TEXT' );
    $fleetlogscols = array ( 'log_id', 'owner_id', 'union_id', 'm', 'k', 'd', 'fuel', 'mission', 'flight_time', 'deploy_time', 'start', 'end',
                             'origin_g', 'origin_s', 'origin_p', 'origin_type', 'target_g', 'target_s', 'target_p', 'target_type', 
                             'ipm_amount', 'ipm_target', 'ship202', 'ship203', 'ship204', 'ship205', 'ship206', 'ship207', 'ship208', 'ship209', 'ship210', 'ship211', 'ship212', 'ship213', 'ship214', 'ship215' );
    $fleetlogstype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'INT', 'INT', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'INT', 'INT', 'INT', 'INT UNSIGNED', 'INT UNSIGNED',
                             'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 
                             'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT' );
    $iplogscols = array ( 'log_id', 'ip', 'user_id', 'reg', 'date' );
    $iplogstype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'CHAR(16)', 'INT', 'INT', 'INT UNSIGNED' );
    $prangercols = array ( 'ban_id', 'admin_name', 'user_name', 'ban_when', 'ban_until', 'reason' );
    $prangertype = array ( 'INT AUTO_INCREMENT PRIMARY KEY', 'CHAR(20)', 'CHAR(20)', 'INT UNSIGNED', 'INT UNSIGNED', 'TEXT' );
    $exptabcols = array ( 'chance_success', 'depleted_min', 'depleted_med', 'depleted_max', 'chance_depleted_min', 'chance_depleted_med', 'chance_depleted_max',
                          'chance_alien', 'chance_pirates', 'chance_dm', 'chance_lost', 'chance_delay', 'chance_accel', 'chance_res', 'chance_fleet' );
    $exptabtype = array ( 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT',
                          'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT' );
    $tabrows = array (&$unicols, &$usercols, &$planetcols, &$allycols, &$rankscols, &$appscols, &$buddycols, &$messagescols, &$notescols, &$errorscols, &$debugcols, &$browsecols, &$queuecols, &$fleetcols, &$unioncols, &$battledatacols, &$fleetlogscols, &$iplogscols, &$prangercols, &$exptabcols);
    $tabtypes = array (&$unitype, &$usertype, &$planettype, &$allytype, &$rankstype, &$appstype, &$buddytype, &$messagestype, &$notestype, &$errorstype, &$debugtype, &$browsetype, &$queuetype, &$fleettype, &$uniontype, &$battledatatype, &$fleetlogstype, &$iplogstype, &$prangertype, &$exptabtype);
    $now = time();

    //print_r ($_POST);

    // Удалить все таблицы и создать новые пустые.
    dbconnect ($_POST["db_host"], $_POST["db_user"], $_POST["db_pass"], $_POST["db_name"]);
    dbquery("SET NAMES 'utf8';");
    dbquery("SET CHARACTER SET 'utf8';");
    dbquery("SET SESSION collation_connection = 'utf8_general_ci';");
    foreach ($tabs as $i => $name)
    {
        $opt = " (";
        $rows = $tabrows[$i];
        $types = $tabtypes[$i];
        foreach ($rows as $row => $rowname)
        {
            if ($row != 0) $opt .= ", ";
            $opt .= $rows[$row] . " " . $types[$row];
        }
        $opt .= ")";

        $query = 'DROP TABLE IF EXISTS '.$_POST["db_prefix"].$tabs[$i];
        dbquery ($query, TRUE);
        $query = 'CREATE TABLE '.$_POST["db_prefix"].$tabs[$i].$opt." CHARACTER SET utf8 COLLATE utf8_general_ci";
        dbquery ($query, TRUE);
    }

    // Создать вселенную.
    $query = "INSERT INTO ".$_POST["db_prefix"]."uni SET ";
    $query .= "num = '".$_POST["uni_num"]."', ";
    $query .= "speed = '".$_POST["uni_speed"]."', ";
    $query .= "galaxies = '".$_POST["uni_galaxies"]."', ";
    $query .= "systems = '".$_POST["uni_systems"]."', ";
    $query .= "maxusers = '".$_POST["uni_maxusers"]."', ";
    $query .= "acs = '".$_POST["uni_acs"]."', ";
    $query .= "fid = '".$_POST["uni_fid"]."', ";
    $query .= "did = '".$_POST["uni_did"]."', ";
    $query .= "rapid = '".($_POST["uni_rapid"]==="on"?1:0)."', ";
    $query .= "moons = '".($_POST["uni_moons"]==="on"?1:0)."', ";
    $query .= "defrepair = '70', ";
    $query .= "defrepair_delta = '10', ";
    $query .= "usercount = '1', ";
    $query .= "freeze = '0', ";
    $query .= "news1 = '', ";
    $query .= "news2 = '', ";
    $query .= "news_until = '0', ";
    $query .= "startdate = '".$now."', ";
    $query .= "battle_engine = '".$_POST["uni_battle_engine"]."' ";
    //echo "<br>$query<br>";
    dbquery ($query);

    // Создать технический аккаунт "space"
    $md = md5 ( gen_trivial_password() . $_POST['db_secret'] );
    $opt = " (";
    $user = array( 99999, $now, 0, 0, 0, "",  "", "space", "space", 0, 0, $md, "", "", "",
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, "0.0.0.0", 1, "", 1, 2, 0, 0,
                        hostname() . "evolution/", 1, 1, 1, 3, 'ru', 0,
                        0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    foreach ($user as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$user[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."users VALUES".$opt;
    dbquery( $query);

    // Создать администраторский аккаунт (Legor).
    $md = md5 ($_POST['admin_pass'] . $_POST['db_secret']);
    $opt = " (";
    $user = array( 1, $now, 0, 0, 0, "",  "", "legor", "Legor", 0, 0, $md, "", $_POST['admin_email'], $_POST['admin_email'],
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, "0.0.0.0", 1, "", 1, 2, 0, 0,
                        hostname() . "evolution/", 1, 1, 1, 3, 'ru', 1,
                        1000000, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    foreach ($user as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$user[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."users VALUES".$opt;
    dbquery( $query);

    // Создать планету Arrakis [1:1:2] и луну Mond.
    $opt = " (";
    $planet = array( 1, "Arakis", 102, 1, 1, 2, 1, 12800, 40, 0, 163, $now,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           500, 500, 0, 1, 1, 1, 1, 1, 1, $now, $now, 0, 0 );
    foreach ($planet as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$planet[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."planets VALUES".$opt;
    dbquery( $query);
    $opt = " (";
    $planet = array( 2, "Mond", 0, 1, 1, 2, 1, 8944, 10, 0, 0, $now,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                           0, 0, 0, 1, 1, 1, 1, 1, 1, $now, $now, 0, 0 );
    foreach ($planet as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$planet[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."planets VALUES".$opt;
    dbquery( $query);

    // Добавить параметры экспедиции.
    $opt = " (";
    $exptab = array ( 80, 25, 50, 75, 25, 50, 75, 1, 2, 3, 4, 5, 6, 7, 8 );
    foreach ($exptab as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$exptab[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$_POST["db_prefix"]."exptab VALUES".$opt;
    dbquery( $query);

    // Установить счетчики автоинкремента.
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."planets AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."messages AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."notes AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."buddy AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."ally AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."allyapps AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."debug AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."errors AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."browse AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."fleet AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."union AUTO_INCREMENT = 10000;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."queue AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."battledata AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."fleetlogs AUTO_INCREMENT = 1;" );
    dbquery ( "ALTER TABLE ".$_POST["db_prefix"]."iplogs AUTO_INCREMENT = 1;" );

    // Сохранить файл конфигурации.
    $file = fopen ("config.php", "wb");
    if ($file == FALSE) $InstallError = "Не удалось сохранить файл конфигурации.";
    else
    {
        fwrite ($file, "<?php\r\n");
        fwrite ($file, "// Создано автоматически НЕ ИЗМЕНЯТЬ!\r\n");
        fwrite ($file, "$"."StartPage=\"". $_POST["startpage"] ."\";\r\n");
        fwrite ($file, "$"."db_host=\"". $_POST["db_host"] ."\";\r\n");
        fwrite ($file, "$"."db_user=\"". $_POST["db_user"] ."\";\r\n");
        fwrite ($file, "$"."db_pass=\"". $_POST["db_pass"] ."\";\r\n");
        fwrite ($file, "$"."db_name=\"". $_POST["db_name"] ."\";\r\n");
        fwrite ($file, "$"."db_prefix=\"". $_POST["db_prefix"] ."\";\r\n");
        fwrite ($file, "$"."db_secret=\"". $_POST["db_secret"] ."\";\r\n");
        fwrite ($file, "?>");
        fclose ($file);
        $InstallError = "<font color=lime>Установка завершена. Файл конфигурации создан.</font>";
    }
}

?>

<html>
<head>
<meta http-equiv='content-type' content='text/html; charset=utf-8' />
<TITLE>Установка OGame</TITLE>
</head>

<body style='background:#000000 url(img/space_background.jpg) no-repeat fixed right top; color: #fff;'>

<style>
td.c { background-color: #334445; }
.button { border: 1px solid; color: white; background-color: #334445; }
.text { border: 1px solid; color: white; background-color: #334445; }
#install_form { background: url(img/page_bg.png); }
</style>

<center>
<form action='install.php' method='POST'>
<input type=hidden name='install' value='1'>

<img src='img/install.png'><br>

<font color=red><?=$InstallError?></font>

<table id='install_form'
<tr><td>&nbsp;</td></tr>
<tr><td>Стартовая страница</td><td><input type=text value='http://ogame.ru' class='text' name='startpage'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'>Настройки базы данных</td></tr>
<tr><td>Хост</td><td><input type=text value='localhost' class='text' name='db_host'></td></tr>
<tr><td>Пользователь</td><td><input type=text class='text' name='db_user'></td></tr>
<tr><td>Пароль</td><td><input type=password class='text'  name='db_pass'></td></tr>
<tr><td>Название БД</td><td><input type=text class='text' name='db_name'></td></tr>
<tr><td><a title='Чтобы было легко найти все таблицы этой вселенной, задайте им общий префикс'>Префикс таблиц</a></td><td><input type=text value='uni1_' class='text' name='db_prefix'></td></tr>
<tr><td><a title='Используется при генерации паролей и сессий'>Секретное слово</a></td><td><input type=text type=password class='text' name='db_secret'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'>Настройки вселенной</td></tr>
<tr><td><a title='Номер вселенной будет указан в заголовке окна и над главным меню в игре.'>Номер вселенной</a></td><td><input type=text value='1' class='text' name='uni_num'></td></tr>
<tr><td><a title='Ускорение влияет на скорость добычи ресурсов, длительность построек и проведение исследований, скорость летящих флотов, минимальную длительность Режима Отпуска.'>Ускорение</a></td><td><input type=text value='1' class='text' name='uni_speed'></td></tr>
<tr><td>Количество галактик</td><td><input type=text value='9' class='text' name='uni_galaxies'></td></tr>
<tr><td>Количество систем</td><td><input type=text value='499' class='text' name='uni_systems'></td></tr>
<tr><td><a title='Максимальное количество аккаунтов. После достижения этого значения регистрация закрывается до тех пор, пока не освободится место.'>Максимум игроков</a></td><td><input type=text value='12500' class='text' name='uni_maxusers'></td></tr>
<tr><td><a title='Максимальное количество приглашенных игроков для Совместной атаки. Максимальное количство флотов в САБ вычисляется по формуле N*4, где N - количетсво участников. При N=0 САБ отключен.'>Участников САБ</a></td><td><input type=text value='4' class='text' name='uni_acs'></td></tr>
<tr><td><a title='Флот в Обломки. Указанное количество процентов флота выпадает в виде обломков. Если указано 0, то ФВО отключено.'>Обломки флота</a></td><td><input type=text value='30' class='text' name='uni_fid'></td></tr>
<tr><td><a title='Оборона в Обломки. Указанное количество процентов обороны выпадает в виде обломков. Если указано 0, то ОВО отключено.'>Обломки обороны</a></td><td><input type=text value='0' class='text' name='uni_did'></td></tr>
<tr><td><a title='Корабли получают возможность повторного выстрела'>Скорострел</a></td><td><input type=checkbox class='text' name='uni_rapid' CHECKED></td></tr>
<tr><td>Луны и Звезды Смерти</td><td><input type=checkbox class='text' name='uni_moons' CHECKED></td></tr>
<tr><td>Путь к боевому движку</td><td><input type=text value='../cgi-bin/battle' class='text' name='uni_battle_engine'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2 class='c'>Аккаунт администратора игры (Legor)</td></tr>
<tr><td>E-Mail</td><td><input type=text class='text' name='admin_email'></td></tr>
<tr><td>Пароль</td><td><input type=password class='text' name='admin_pass'></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td colspan=2><center><input type=submit value='Инсталлировать' class='button'></center></td></tr>
</table>

</form>

</center>
</body>
</html>