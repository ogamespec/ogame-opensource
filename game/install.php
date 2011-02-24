<?php

// Установочный файл.
// Создает все необходимые таблицы в базе данных, а также файл конфигурации config.php, для доступа к базе.
// Не работет, если файл config.php создан.

$InstallError = "<font color=gold>Используйте подсказки при наведении мышкой на выбранные параметры</font>";

require_once "db.php";

$db_prefix = "";

// Увеличить глобальный счетчик вселенной и возвратить его последнее значение.
function IncrementDBGlobal ( $name)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."uni;";
    $result = dbquery ($query);
    $unitab = dbarray ($result);
    $id = $unitab[$name]++;
    $query = "UPDATE ".$db_prefix."uni"." SET $name = ".$unitab[$name].";";
    dbquery ($query);
    return $id;
}

// Добавить строку в таблицу.
function AddDBRow ( $row, $tabname )
{
    global $db_prefix;
    $opt = " (";
    foreach ($row as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$row[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO ".$db_prefix."$tabname VALUES".$opt;
    dbquery( $query);
}

ob_start ();

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

// Сохранить настройки.
if ( key_exists("install", $_POST) && CheckParameters() )
{
    $tabs = array ('uni','users','planets','ally','allyranks','allyapps','buddy','messages','notes','errors','queue');
    $unicols = array ('name','speed','galaxies','systems','maxusers','acs','fid','did','rapid','moons','nextuser','usercount','nextplanet','nextally','nextmsg','nextnote','nextbuddy','nexterror','nexttask','startdate');
    $unitype = array ('TEXT','FLOAT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT','INT UNSIGNED');
    $usercols = array ( 'player_id', 'ally_id', 'joindate', 'allyrank', 'session', 'private_session', 'name', 'oname', 'name_changed', 'name_until', 'password', 'pemail', 'email',
                               'email_changed', 'email_until', 'vacation', 'vacation_until', 
                               'lastlogin', 'lastclick', 'ip_addr', 'validated', 'validatemd', 'hplanetid', 'admin', 'sortby', 'sortorder',
                               'skin', 'useskin', 'deact_ip', 'maxspy', 'maxfleetmsg', 'aktplanet',
                               'dm', 'dmfree', 
                               'score1', 'score2', 'score3', 'place1', 'place2', 'place3',
                               'r106', 'r108', 'r109', 'r110', 'r111', 'r113', 'r114', 'r115', 'r117', 'r118', 'r120', 'r121', 'r122', 'r123', 'r124', 'r199' );
    $usertype = array (  'INT PRIMARY KEY', 'INT', 'INT', 'INT UNSIGNED', 'CHAR(12)', 'CHAR(32)', 'CHAR(20)', 'CHAR(20)', 'INT', 'INT UNSIGNED', 'CHAR(32)', 'CHAR(50)', 'CHAR(50)',
                                'INT', 'INT UNSIGNED', 'INT', 'INT UNSIGNED', 
                                'INT UNSIGNED', 'INT UNSIGNED', 'CHAR(15)', 'INT', 'CHAR(32)', 'INT', 'INT', 'INT', 'INT', 'CHAR(80)', 'INT', 'INT', 'INT', 'INT', 'INT',
                                'INT UNSIGNED', 'INT UNSIGNED',
                                'INT UNSIGNED', 'INT UNSIGNED', 'INT UNSIGNED', 'INT', 'INT', 'INT', 
                                'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT' );
    $planetcols = array ( 'planet_id', 'name', 'type', 'g', 's', 'p', 'owner_id', 'diameter', 'temp', 'fields', 'maxfields', 'date', 
                                'b1', 'b2', 'b3', 'b4', 'b12', 'b14', 'b15', 'b21', 'b22', 'b23', 'b24', 'b31', 'b33', 'b34', 'b41', 'b42', 'b43', 'b44',
                                'd401', 'd402', 'd403', 'd404', 'd405', 'd406', 'd407', 'd408', 'd502', 'd503',
                                'f202', 'f203', 'f204', 'f205', 'f206', 'f207', 'f208', 'f209', 'f210', 'f211', 'f212', 'f213', 'f214', 'f215',
                                'm', 'k', 'd', 'mprod', 'kprod', 'dprod', 'sprod', 'fprod', 'ssprod', 'lastpeek', 'lastakt', 'debris', 'dm', 'dk' );
    $planettype = array ( 'INT PRIMARY KEY', 'CHAR(20)', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT UNSIGNED',
                                 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 
                                 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT',
                                 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 
                                 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'DOUBLE', 'INT UNSIGNED', 'INT UNSIGNED', 'INT', 'DOUBLE', 'DOUBLE' );
    $allycols = array ( 'ally_id', 'tag', 'name', 'owner_id', 'homepage', 'imglogo', 'open', 'exttext', 'inttext', 'apptext', 'nextrank', 'nextapp' );
    $allytype = array ( 'INT PRIMARY KEY', 'TEXT', 'TEXT', 'INT', 'TEXT', 'TEXT', 'INT', 'TEXT', 'TEXT', 'TEXT', 'INT', 'INT' );
    $rankscols = array ( 'rank_id', 'ally_id', 'name', 'rights' );
    $rankstype = array ( 'INT PRIMARY KEY', 'INT', 'TEXT', 'INT' );
    $appscols = array ( 'app_id', 'ally_id', 'player_id', 'text' );
    $appstype = array ( 'INT PRIMARY KEY', 'INT', 'INT', 'TEXT' );
    $buddycols = array ( 'buddy_id', 'request_from', 'request_to', 'text', 'accepted' );
    $buddytype = array ( 'INT PRIMARY KEY', 'INT', 'INT', 'TEXT', 'INT' );
    $messagescols = array ( 'msg_id', 'owner_id', 'pm', 'msgfrom', 'subj', 'text', 'shown', 'date' );
    $messagestype = array ( 'INT PRIMARY KEY', 'INT', 'INT', 'TEXT', 'TEXT', 'TEXT', 'INT', 'INT UNSIGNED' );
    $notescols = array ( 'note_id', 'owner_id', 'subj', 'text', 'textsize', 'prio', 'date' );
    $notestype = array ( 'INT PRIMARY KEY', 'INT', 'TEXT', 'TEXT', 'INT', 'INT', 'INT UNSIGNED' );
    $errorscols = array ( 'error_id', 'owner_id', 'ip', 'agent', 'url', 'text', 'date' );
    $errorstype = array ( 'INT PRIMARY KEY', 'INT', 'TEXT', 'TEXT', 'TEXT', 'TEXT', 'INT UNSIGNED' );
    $queuecols = array ( 'task_id', 'owner_id', 'type', 'sub_id', 'obj_id', 'level', 'start', 'end' );
    $queuetype = array ( 'INT PRIMARY KEY', 'INT', 'CHAR(20)', 'INT', 'INT', 'INT', 'INT UNSIGNED', 'INT UNSIGNED' );
    $tabrows = array (&$unicols, &$usercols, &$planetcols, &$allycols, &$rankscols, &$appscols, &$buddycols, &$messagescols, &$notescols, &$errorscols, &$queuecols);
    $tabtypes = array (&$unitype, &$usertype, &$planettype, &$allytype, &$rankstype, &$appstype, &$buddytype, &$messagestype, &$notestype, &$errorstype, &$queuetype);
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
    $query .= "name = '".$_POST["uni_name"]."', ";
    $query .= "speed = '".$_POST["uni_speed"]."', ";
    $query .= "galaxies = '".$_POST["uni_galaxies"]."', ";
    $query .= "systems = '".$_POST["uni_systems"]."', ";
    $query .= "maxusers = '".$_POST["uni_maxusers"]."', ";
    $query .= "acs = '".$_POST["uni_acs"]."', ";
    $query .= "fid = '".$_POST["uni_fid"]."', ";
    $query .= "did = '".$_POST["uni_did"]."', ";
    $query .= "rapid = '".($_POST["uni_rapid"]==="on"?1:0)."', ";
    $query .= "moons = '".($_POST["uni_moons"]==="on"?1:0)."', ";
    $query .= "nextuser = '100000', ";
    $query .= "usercount = '1', ";
    $query .= "nextplanet = '10000', ";
    $query .= "nextally = '1', ";
    $query .= "nextmsg = '10000', ";
    $query .= "nextnote = '1', ";
    $query .= "nextbuddy = '1', ";
    $query .= "nexterror = '10000', ";
    $query .= "nexttask = '1', ";
    $query .= "startdate = '".$now."' ";
    //echo "<br>$query<br>";
    dbquery ($query);

    // Создать администраторский аккаунт (Legor).

    // Создать планету Arrakis [1:1:2] и луну.

    // Сохранить файл конфигурации.
    $file = fopen ("config.php", "wb");
    if ($file == FALSE) $InstallError = "Не удалось сохранить файл конфигурации.";
    else
    {
        fwrite ($file, "<?php\r\n");
        fwrite ($file, "// Создано автоматически НЕ ИЗМЕНЯТЬ!\r\n");
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

<table id='install_form'>
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
<tr><td><a title='Название будет указано в заголовке окна и над главным меню в игре.'>Название вселенной</a></td><td><input type=text value='Вселенная 1' class='text' name='uni_name'></td></tr>
<tr><td><a title='Ускорение влияет на скорость добычи ресурсов, длительность построек и проведение исследований, скорость летящих флотов, минимальную длительность Режима Отпуска.'>Ускорение</a></td><td><input type=text value='1' class='text' name='uni_speed'></td></tr>
<tr><td>Количество галактик</td><td><input type=text value='9' class='text' name='uni_galaxies'></td></tr>
<tr><td>Количество систем</td><td><input type=text value='499' class='text' name='uni_systems'></td></tr>
<tr><td><a title='Максимальное количество аккаунтов. После достижения этого значения регистрация закрывается до тех пор, пока не освободится место.'>Максимум игроков</a></td><td><input type=text value='12500' class='text' name='uni_maxusers'></td></tr>
<tr><td><a title='Максимальное количество приглашенных игроков для Совместной атаки. Максимальное количство флотов в САБ вычисляется по формуле N*4, где N - количетсво участников. При N=0 САБ отключен.'>Участников САБ</a></td><td><input type=text value='4' class='text' name='uni_acs'></td></tr>
<tr><td><a title='Флот в Обломки. Указанное количество процентов флота выпадает в виде обломков. Если указано 0, то ФВО отключено.'>Обломки флота</a></td><td><input type=text value='30' class='text' name='uni_fid'></td></tr>
<tr><td><a title='Оборона в Обломки. Указанное количество процентов обороны выпадает в виде обломков. Если указано 0, то ОВО отключено.'>Обломки обороны</a></td><td><input type=text value='0' class='text' name='uni_did'></td></tr>
<tr><td><a title='Корабли получают возможность повторного выстрела'>Скорострел</a></td><td><input type=checkbox class='text' name='uni_rapid' CHECKED></td></tr>
<tr><td>Луны и Звезды Смерти</td><td><input type=checkbox class='text' name='uni_moons' CHECKED></td></tr>
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
