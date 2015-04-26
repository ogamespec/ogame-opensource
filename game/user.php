<?php

// Управление пользователями.

/*
player_id: Порядковый номер пользователя (INT AUTO_INCREMENT PRIMARY KEY)
regdate: Дата регистрации аккаунта (INT UNSIGNED)
ally_id: Номер альянса в котором состоит игрок (0 - без альянса) (INT)
joindate: Дата вступления в альянс (INT UNSIGNED)
allyrank: Ранг игрока в альянсе (INT)
session: Сессия для ссылок (CHAR (12))
private_session: Приватная сессия для кукисов (CHAR(32))
name: Имя пользователя lower-case для сравнения (CHAR(20))
oname: Имя пользователя оригинальное (CHAR(20))
name_changed: Имя пользователя изменено? (1 или 0) (INT)
Q name_until: Когда можно поменять имя пользователя в следующий раз (INT UNSIGNED)
password: MD5-хеш пароля и секретного слова (CHAR(32))
temp_pass: MD5-хеш пароля для восстановления и секретного слова (CHAR(32))
pemail: Постоянный почтовый адрес (CHAR(50))
email: Временный почтовый адрес (CHAR(50))
email_changed: Временный почтовый адрес изменен (INT)
Q email_until: Когда заменить постоянный email на временный (INT UNSIGNED)
disable: Аккаунт поставлен на удаление (INT)
Q disable_until: Когда можно удалить аккаунт (INT UNSIGNED)
vacation: Аккаунт в режиме отпуска (INT)
vacation_until: Когда можно выключить режим отпуска (INT UNSIGNED)
banned: Аккаунт заблокирован (INT)
Q banned_until: Время окончания блокировки (INT UNSIGNED)
noattack: Запрет на атаки (INT)
Q noattack_until: Когда заканчивается запрет на атаки (INT UNSIGNED)
lastlogin: Последняя дата входа в игру (INT UNSIGNED)
lastclick: Последний щелчок мышкой, для определения активности игрока (INT UNSIGNED)
ip_addr: IP адрес пользователя
validated: Пользователь активирован. Если пользователь не активирован, то ему запрещено посылать игровые сообщения и заявки в альянсы. (INT)
validatemd: Код активации (CHAR(32))
hplanetid: Порядковый номер Главной планеты (INT)
admin: 0 - обычный игрок, 1 - оператор, 2 - администратор (INT)
sortby: Порядок сортировки планет: 0 - порядку колонизации, 1 - координатам, 2 - алфавиту (INT)
sortorder: Порядок: 0 - по возрастанию, 1 - по убыванию (INT)
skin: Путь для скина (CHAR(80)). Получается путем слепления пути к хосту и названием скина, но длина строки не более 80 символов.
useskin: Показывать скин, если 0 - то показывать скин по умолчанию (INT)
deact_ip: Выключить проверку IP (INT)
maxspy: Кол-во шпионских зондов (1 по умолчанию, 0...99) (INT)
maxfleetmsg: Максимальные сообщения о флоте в Галактику (3 по умолчанию, 0...99, 0=1) (INT)
aktplanet: Текущая выбранная планета. (INT)
dm: Покупная ТМ (INT)
dmfree: ТМ найденная в экспедиции (INT)
sniff: Включить слежение за историей переходов (Админка) (INT)
debug: Включить отображение отладочной информации (INT)
trader: 0 - скупщик не найден, 1 - скупщик покупает металл, 2 - скупщик покупает кристалл, 3 - скупщик покупает дейтерий (INT)
rate_m, rate_k, rate_d: курсы обмена скупщика ( например 3.0 : 1.8 : 0.6 ) (DOUBLE)
score1,2,3: Очки за постройки, флот, исследования (BIGINT UNSIGNED, INT UNSIGNED, INT UNSIGNED )
place1,2,3: Место за постройки, флот, исследования (INT)
oldscore1,2,3: Старые очки за постройки, флот, исследования (BIGINT UNSIGNED, INT UNSIGNED, INT UNSIGNED )
oldplace1,2,3: старое место за постройки, флот, исследования (INT)
scoredate: Время сохранения старой статистики (INT UNSIGNED)
rXXX: Уровень исследования XXX (INT)

Q - для обработки этого события используется задание в очереди задач.
*/

$UserCache = array ();
$PremiumCache = array ();

function mail_utf8($to, $subject = '(No subject)', $message = '', $header = '') {
  $header_ = 'MIME-Version: 1.0' . "\n" . 'Content-type: text/plain; charset=UTF-8' . "\n";
  mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
}

// Выслать приветственное письмо с ссылкой для активации аккаунта.
function SendGreetingsMail ( $name, $pass, $email, $ack)
{
    $unitab = LoadUniverse ();
    $uni = $unitab['num'];
    $text = "Приветствуем $name,\n\n" .
                "Вы решили создать свою империю в $uni-й вселенной ОГейма!\n\n" .
                "Нажмите на эту ссылку для активации Вашего аккаунта:\n" .
                hostname()."game/validate.php?ack=$ack\n\n" .
                "Ваши игровые данные:\n" .
                "Игровое имя: $name\n" .
                "Пароль: $pass\n" .
                "Вселенная: $uni\n\n\n" .
                "Если Вам понадобится помощь или совет других императоров, то всё это Вы сможете найти на нашем форуме (http://board.oldogame.ru).\n\n" .
                "Здесь (http://tutorial.oldogame.ru) собрана вся информация, собранная игроками и членами команды для того, чтобы помочь новичкам как можно быстрее разобраться в игре.\n\n" .
                "Желаем успехов в построении империи и удачи в предстоящих боях!\n\n" .
                "Ваша команда ОГейма";
    //echo "<pre>$text</pre><br>\n";
    mail_utf8 ( $email, "Добро пожаловать в ОГейм ", $text, "From: OGame Uni ru $uni <noreply@oldogame.ru>");
}

// Выслать письмо, подтверждающее смену адреса.
function SendChangeMail ( $name, $email, $pemail, $ack)
{
    $unitab = LoadUniverse ();
    $uni = $unitab['num'];
    $text = "Приветствуем $name,\n\n" .
               "временный адрес e-mail Вашего аккаунта в $uni-й вселенной был изменён в настройках на $email.\n" .
               "Если Вы его не измените в течение недели, то он станет постоянным.\n\n" .
               "Чтобы беспрепятственно продолжить игру, подтвердите ваш новый адрес e-mail по следующей ссылке:\n\n" .
               hostname()."game/validate.php?ack=$ack\n\n" .
               "Ваша команда OGame";
    mail_utf8 ( $pemail, "Ваш игровой электронный адрес изменён ", $text, "From: OGame Uni ru $uni <noreply@oldogame.ru>");
}

// Выслать приветственное сообщение.
function SendGreetingsMessage ( $player_id)
{
    SendMessage ( $player_id, "Командование флотом", "Добро пожаловать в ОГейм!", 
        bb ( "Добро пожаловать в [b]OGame[/b] !\n"
        . "\n"
        . "Для начала Вам необходимо развить рудники.\n"
        . "Это можно сделать в меню \"постройки\".\n"
        . "Выберите рудник по добыче металла и нажмите на \"строить\".\n"
        . "Теперь у Вас есть немного времени для ознакомления с игрой.\n"
        . "Помощь по игре Вы можете найти по этим ссылкам: \n"
        . "[url=http://tutorial.oldogame.ru/]Туториал[/url]\n"
        . "[url=http://board.oldogame.ru]Форум[/url]\n"
        . "\n"
        . "Тем временем Ваш рудник уже должен построиться.\n"
        . "Для работы рудников необходима энергия, для её получения постройте солнечную электростанцию.\n"
        . "Для этого снова зайдите в меню \"постройки\" и кликните на электростанции.\n"
        . "Для того, чтобы посмотреть, насколько далеко Вы зашли в развитии, зайдите в меню \"Технологии\".\n"
        . "Итак, Ваш победный поход по вселенной начался... Удачи!\n" ), 5 );
}

function IsUserExist ( $name)
{
    global $db_prefix;
    $name = mb_strtolower ($name, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."'";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Исключить из поиска имя name.
function IsEmailExist ( $email, $name="")
{
    global $db_prefix;
    $name = mb_strtolower ($name, 'UTF-8');
    $email = mb_strtolower ($email, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE (email = '".$email."' OR pemail = '".$email."')";
    if ($name !== "") $query .= " AND name <> '".$name."'";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Проверок на правильность не делается! Этим занимается процедура регистрации.
// Возвращает ID созданного пользователя.
function CreateUser ( $name, $pass, $email, $bot=false)
{
    global $db_prefix, $db_secret, $Languages;
    $origname = $name;
    $name = mb_strtolower ($name, 'UTF-8');
    $email = mb_strtolower ($email, 'UTF-8');
    $md = md5 ($pass . $db_secret);
    $ack = md5(time ().$db_secret);

    error_reporting ( E_ALL );

    // Увеличить счетчик пользователей во вселенной.
    $query = "SELECT * FROM ".$db_prefix."uni".";";
    $result = dbquery ($query);
    $unitab = dbarray ($result);
    $unitab['usercount']++;
    $query = "UPDATE ".$db_prefix."uni"." SET usercount = ".$unitab['usercount'].";";
    dbquery ($query);

    $ip = $_SERVER['REMOTE_ADDR'];

    $user = array( null, time(), 0, 0, 0, "",  "", $name, $origname, 0, 0, $md, "", $email, $email,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, $ip, 0, $ack, 0, 0, 0, 0,
                        hostname() . "evolution/", 1, 0, 1, 3, 0,
                        0, 0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    $id = AddDBRow ( $user, "users" );

    LogIPAddress ( $ip, $id, 1 );

    // Создать Главную планету.
    $homeplanet = CreateHomePlanet ($id);

    $query = "UPDATE ".$db_prefix."users SET hplanetid = $homeplanet, aktplanet = $homeplanet WHERE player_id = $id;";
    dbquery ( $query );

    // Выслать приветственное письмо и сообщение.
    if ( !$bot ) {
        if ( $ip !== "127.0.0.1" ) SendGreetingsMail ( $origname, $pass, $email, $ack);
        SendGreetingsMessage ( $id);
    }

    // Активировать Командира на 9999 дней.
    RecruitOfficer ( $id, 'CommanderOff', 9999 * 24 * 60 * 60 );

    // Удалить неактивированного пользователя через 3 дня.

    SetVar ( $id, "TimeLimit", 3*365*24*60*60 );

    RecalcRanks ();

    return $id;
}

// Полность удалить игрока, все его планеты и флоты.
// Развернуть флоты летящие на игрока.
function RemoveUser ( $player_id, $when)
{
    global $db_prefix;

    // Аккаунты администратора и space нельзя удалить.
    if ($player_id == 1 || $player_id == 99999) return;

    // Развернуть все флоты, летящие на игрока.
    $result = EnumFleetQueue ($player_id);
    $rows = dbrows ( $result );
    while ($rows--) {
        $queue = dbarray ($result);
        $fleet_obj = LoadFleet ( $queue['sub_id'] );
        if ($fleet_obj['owner_id'] != $player_id && $fleet_obj['mission'] < 100 ) RecallFleet ( $fleet_obj['fleet_id'], $when );
    }

    // Удалить все флоты игрока
    $query = "DELETE FROM ".$db_prefix."fleet WHERE owner_id = $player_id";
    dbquery ($query);

    // Удалить все задания из очереди
    $query = "DELETE FROM ".$db_prefix."queue WHERE owner_id = $player_id";
    dbquery ($query);

    // Удалить все планеты, кроме ПО, которые переходят во владения space.
    $query = "DELETE FROM ".$db_prefix."planets WHERE owner_id = $player_id AND type <> 10000";
    dbquery ($query);
    $query = "UPDATE ".$db_prefix."planets SET owner_id = 99999 WHERE owner_id = $player_id AND type = 10000";
    dbquery ($query);

    // Удалить игрока.
    $query = "DELETE FROM ".$db_prefix."users WHERE player_id = $player_id";
    dbquery ($query);

    // Уменьшить количество пользователей.
    $query = "UPDATE ".$db_prefix."uni SET usercount = usercount - 1;";
    dbquery ($query);

    // Удалить заявки в альянс
    $apply_id = GetUserApplication ( $player_id );
    if ( $apply_id ) RemoveApplication ($apply_id);

    // Удалить из списка друзей
    $query = "DELETE FROM ".$db_prefix."buddy WHERE request_from = $player_id OR request_to = $player_id";
    dbquery ($query);

    RecalcRanks ();
}

// Произвести активацию пользователя.
function ValidateUser ($code)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE validatemd = '".$code."'";
    $result = dbquery ($query);
    if (dbrows ($result) == 0)
    {
        RedirectHome ();
        return;
    }
    $user = dbarray ($result);
    if (!$user['validated'])
    {    // Заменить постоянный адрес временным после активации.
        $query = "UPDATE ".$db_prefix."users SET pemail = '".$user['email']."' WHERE player_id = ".$user['player_id'];
        dbquery ($query);
    }
    $query = "UPDATE ".$db_prefix."users SET validatemd = '', validated = 1 WHERE player_id = ".$user['player_id'];
    dbquery ($query);
    Login ( $user['oname'], "", $user['password'], 1 );
}

// Проверить пароль. Возвращает 0, или ID пользователя.
function CheckPassword ( $name, $pass, $passmd="")
{
    global $db_prefix, $db_secret;
    $name = mb_strtolower ($name, 'UTF-8');
    if ($passmd === "") $md = md5 ($pass . $db_secret);
    else $md = $passmd;
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."' AND password = '".$md."'";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) return 0;
    $user = dbarray ($result);
    return $user['player_id'];
}

// Сменить временный почтовый адрес. Возвращает 1, если адрес успешно изменен, или 0, если адрес уже используется.
function ChangeEmail ( $name, $email)
{
    global $db_prefix, $db_secret;
    $name = mb_strtolower ($name, 'UTF-8');
    $email = mb_strtolower ($email, 'UTF-8');
    if (IsEmailExist ($uni, $email, $name)) return 0;
    $query = "UPDATE ".$db_prefix."users SET email = '".$email."' WHERE name = '".$name."'";
    dbquery ($query);
    $ack = ChangeActivationCode ( $name);
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."'";
    $result = dbquery ($query);
    $user = dbarray ($result);
    SendChangeMail ( $user['oname'], $email, $user['pemail'], $ack);
    return 1;
}

// Сменить имя пользователя.
function ChangeName ( $player_id, $name )
{
    global $db_prefix;
    $lower = mb_strtolower ($name, 'UTF-8');
    $query = "UPDATE ".$db_prefix."users SET name = '".$lower."', oname = '".$name."' WHERE player_id = $player_id";
    dbquery ($query);
    AddAllowNameEvent ($player_id);
}

// Сменить код активации. Возвращает новый код.
function ChangeActivationCode ( $name)
{
    global $db_prefix, $db_secret;
    $name = mb_strtolower ($name, 'UTF-8');
    $ack = md5(time ().$db_secret);
    $query = "UPDATE ".$db_prefix."users SET validatemd = '".$ack."' WHERE name = '".$name."'";
    dbquery ($query);
    return $ack;
}

// Выбрать текущую планету.
function SelectPlanet ($player_id, $cp)
{
    global $db_prefix;
    $planet = GetPlanet ($cp);    // Нельзя выбирать чужие планеты.
    if ($planet['owner_id'] != $player_id || $planet['type'] >= 10000 )
    {
        Hacking ( "HACK_SELECT_PLANET" );
        return;
    }
    $query = "UPDATE ".$db_prefix."users SET aktplanet = '".$cp."' WHERE player_id = '".$player_id."'";
    dbquery ($query);
    InvalidateUserCache ();
}

// Получить ID текущей планеты
function GetSelectedPlanet ( $player_id )
{
    $user = LoadUser ( $player_id );
    return $user['aktplanet'];
}

// Загрузить пользователя.
function LoadUser ( $player_id)
{
    global $db_prefix, $UserCache;
    if ( isset ( $UserCache [ $player_id ] ) ) return  $UserCache [ $player_id ];
    $query = "SELECT * FROM ".$db_prefix."users WHERE player_id = '".$player_id."' LIMIT 1";
    $result = dbquery ($query);
    $user = dbarray ($result);
    $UserCache [ $player_id ] = $user;
    return $user;
}

// Обновить активность пользователя (НЕ ПЛАНЕТЫ).
function UpdateLastClick ( $player_id)
{
    global $db_prefix;
    $now = time ();
    $query = "UPDATE ".$db_prefix."users SET lastclick = $now WHERE player_id = $player_id";
    dbquery ($query);
}

// Защита для новичков.
// Новичками называют игроков, имеющих менее 5000 очков.
// На новичка могут нападать лишь те игроки, у которых не более чем в пять раз больше, и не менее чем в пять раз меньше очков.
// Новичок может напасть на более сильного игрока (как новичка, так и не новичка), если у него не более чем в пять раз больше очков.

// Защита для новичков. Проверить, является ли игрок для текущего игрока новичком.
function IsPlayerNewbie ( $player_id)
{
    global $GlobalUser;
    $user = LoadUser ( $player_id);
    $week = time() - 604800;
    if ( $user['lastclick'] <= $week || $user['vacation'] || $user['banned']) return false;
    $p1 = $GlobalUser['score1'];
    $p2 = $user['score1'];

    if ($p2 >= $p1 || $p2 >= 5000) return false;
    if ($p1 <= $p2*5) return false;
    return true;
}

// Защита для новичков. Проверить, является ли игрок для текущего игрока сильным.
function IsPlayerStrong ( $player_id)
{
    global $GlobalUser;
    $user = LoadUser ( $player_id);
    $week = time() - 604800;
    if ( $user['lastclick'] <= $week || $user['vacation'] || $user['banned']) return false;
    $p1 = $GlobalUser['score1'];
    $p2 = $user['score1'];

    if ($p1 >= $p2 || $p1 >= 5000) return false;
    if ($p2 <= $p1*5) return false;
    return true;
}

// Получить статус командиров на аккаунте.
function PremiumStatus ($user)
{
    global $PremiumCache;
    if ( isset ( $PremiumCache [ $user['player_id'] ] ) ) return  $PremiumCache [ $user['player_id'] ];

    $prem = array ();
    $qcmd = array ( 'commander' => 'CommanderOff', 'admiral' => 'AdmiralOff', 'engineer' => 'EngineerOff', 'geologist' => 'GeologeOff', 'technocrat' => 'TechnocrateOff');

    $now = time ();

    foreach ($qcmd as $i=>$cmd)
    {
        $end = GetOfficerLeft ( $user['player_id'], $cmd );
        if ($end <= $now) $d = 0;
        else $d = ($end - $now) / (60*60*24);
        $enabled = ( $d  > 0 );

        $prem[$i] = $enabled;
        $prem[$i.'_days'] = $d;
    }
    $PremiumCache[ $user['player_id'] ]  = $prem;
    return $prem;
}

// Вызывается при нажатии на "Выход" в меню.
function Logout ( $session )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE session = '".$session."'";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) return;
    $user = dbarray ($result);
    $player_id = $user['player_id'];
    $unitab = LoadUniverse ();
    $uni = $unitab['num'];
    $query = "UPDATE ".$db_prefix."users SET session = '' WHERE player_id = $player_id";
    dbquery ($query);
    setcookie ( "prsess_".$player_id."_".$uni, '');
}

// Вызывается при загрузке каждой игровой страницы.
function CheckSession ( $session )
{
    global $db_prefix, $GlobalUser, $loca_lang, $Languages, $GlobalUni;
    // Получить ID-пользователя и номер вселенной из публичной сессии.
    $query = "SELECT * FROM ".$db_prefix."users WHERE session = '".$session."'";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) { RedirectHome(); return FALSE; }
    $GlobalUser = dbarray ($result);
    $unitab = $GlobalUni;
    $uni = $unitab['num'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $prsess = $_COOKIE ['prsess_'.$GlobalUser['player_id'].'_'.$uni];
    if ( $prsess !== $GlobalUser['private_session'] ) { InvalidSessionPage (); return FALSE; }
    if ( $ip !== "127.0.0.1" && !$GlobalUser['deact_ip'] ) {
        if ( $ip !== $GlobalUser['ip_addr']) { InvalidSessionPage (); return FALSE; }
    }

    //
    // Установить глобальный язык для сессии.
    //

    $loca_lang = $GlobalUni['lang'];
    if ( !key_exists ( $loca_lang, $Languages ) ) $GlobalUni['lang'] = $loca_lang = 'en';

    return TRUE;
}

// Login - Вызывается с главной страницы, после регистрации или активации нового пользователя.
function Login ( $login, $pass, $passmd="", $from_validate=0 )
{
    global $db_prefix, $db_secret;

    $unitab = LoadUniverse ();
    $uni = $unitab['num'];

    ob_start ();

    if  ( $player_id = CheckPassword ($login, $pass, $passmd ) )
    {
        // Пользователь заблокирован?
        $user = LoadUser ( $player_id );
        if ($user['banned'])
        {
            UpdateLastClick ( $player_id );        // Обновить активность пользователя, чтобы можно было продлять удаление.
            echo "<html><head><meta http-equiv='refresh' content='0;url=".hostname()."game/reg/errorpage.php?errorcode=3&arg1=$uni&arg2=$login&arg3=".$user['banned_until']."' /></head><body></body>";
            ob_end_flush ();
            exit ();
        }

        $lastlogin = time ();
        // Создать приватную сессию.
        $prsess = md5 ( $login . $lastlogin . $db_secret);
        // Создать публичную сессию
        $sess = substr (md5 ( $prsess . sha1 ($pass) . $db_secret . $lastlogin), 0, 12);

        // Записать приватную сессию в кукисы и обновить БД.
        setcookie ( "prsess_".$player_id."_".$uni, $prsess, time()+24*60*60, "/" );
        $query = "UPDATE ".$db_prefix."users SET lastlogin = $lastlogin, session = '".$sess."', private_session = '".$prsess."' WHERE player_id = $player_id";
        dbquery ($query);

        // Записать IP-адрес.
        $ip = $_SERVER['REMOTE_ADDR'];
        $query = "UPDATE ".$db_prefix."users SET ip_addr = '".$ip."' WHERE player_id = $player_id";
        dbquery ($query);

        //echo "ID пользователя: $player_id<br>Приватная сессия: $prsess<br>Публичная сессия: $sess<br>IP-адрес: $ip";

        // Выбрать Главную планету текущей.
        $query = "SELECT * FROM ".$db_prefix."users WHERE session = '".$sess."'";
        $result = dbquery ($query);
        $user = dbarray ($result);
        SelectPlanet ($player_id, $user['hplanetid']);

        // Задание глобальной отгрузки игроков, чистки виртуальных ПО, чистки уничтоженных планет, пересчёт статистики альянсов и прочие глобальные события
        AddReloginEvent ();
        AddCleanDebrisEvent ();
        AddCleanPlanetsEvent ();
        AddCleanPlayersEvent ();
        AddRecalcAllyPointsEvent ();

        // Задание пересчёта очков игрока.
        AddUpdateStatsEvent ();
        AddRecalcPointsEvent ($player_id);

        // Редирект на Обзор Главной планеты.
        header ( "Location: ".hostname()."game/index.php?page=overview&session=".$sess."&lgn=1" );
        echo "<html><head><meta http-equiv='refresh' content='0;url=".hostname()."game/index.php?page=overview&session=".$sess."&lgn=1' /></head><body></body>";

        LogIPAddress ( $ip, $player_id );
    }
    else
    {
        header ( "Location: ".hostname()."game/reg/errorpage.php?errorcode=2&arg1=$uni&arg2=$login" );
        echo "<html><head><meta http-equiv='refresh' content='0;url=".hostname()."game/reg/errorpage.php?errorcode=2&arg1=$uni&arg2=$login' /></head><body></body>";
    }
    ob_end_flush ();
    exit ();
}

// Пересчёт статистики.
function RecalcStats ($player_id)
{
    global $db_prefix;
    $m = $k = $d = $e = 0;
    $points = $fpoints = $rpoints = 0;

    // Планеты/луны + стоящие флоты
    $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".$player_id."'";
    $result = dbquery ($query);
    $rows = dbrows ($result);
    while ($rows--) {
        $planet = dbarray ($result);
        if ( $planet['type'] >= 10000 ) continue;        // считать только планеты и луны.
        $pp = PlanetPrice ($planet);
        $points += $pp['points'];
        $fpoints += $pp['fpoints'];
    }

    // Исследования
    $resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );
    $user = LoadUser ($player_id);
    if ( $user != null )
    {
        foreach ($resmap as $i=>$gid) {
            $level = $user["r$gid"];
            $rpoints += $level;
            if ($level > 0) {
                for ( $lv = 1; $lv<=$level; $lv ++ )
                {
                    $res = ResearchPrice ( $gid, $lv );
                    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
                    $points += ($m + $k + $d);
                }
            }
        }
    }

    // Летящие флоты
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    $result = EnumOwnFleetQueue ( $player_id, 1 );
    $rows = dbrows ($result);
    while ($rows--)
    {
        $queue = dbarray ( $result );
        $fleet = LoadFleet ( $queue['sub_id'] );

        foreach ( $fleetmap as $i=>$gid ) {        // Флот
            $level = $fleet["ship$gid"];
            if ($level > 0){
                $res = ShipyardPrice ( $gid );
                $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
                $points += ($m + $k + $d) * $level;
                $fpoints += $level;
            }
        }
    
        if ( $fleet['ipm_amount'] > 0 ) {        // МПР
            $res = ShipyardPrice ( 503 );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            $points += ($m + $k + $d) * $fleet['ipm_amount'];
        }
    }

    $query = "UPDATE ".$db_prefix."users SET ";
    $query .= "score1=$points, score2=$fpoints, score3=$rpoints WHERE player_id = $player_id AND (banned <> 1 OR admin > 0);";
    dbquery ($query);
}

function AdjustStats ( $player_id, $points, $fpoints, $rpoints, $sign )
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."users SET ";
    $query .= "score1=score1 $sign '".$points."', score2=score2 $sign '".$fpoints."', score3=score3 $sign '".$rpoints."' WHERE player_id = $player_id AND banned = 0 AND admin = 0;";
    dbquery ($query);
    //Debug ( "Adjust $player_id POINT=$sign$points FLEET=$sign$fpoints RESEARCH=$sign$rpoints" );
}

// Пересчитать места всех игроков.
function RecalcRanks ()
{
    global $db_prefix;

    // Специальная обработка для админов
    $query = "UPDATE ".$db_prefix."users SET score1 = -1, score2 = -1, score3 = -1 WHERE admin > 0";
    dbquery ($query);

    // Очки
    dbquery ("SET @pos := 0;");
    $query = "UPDATE ".$db_prefix."users
              SET place1 = (SELECT @pos := @pos+1)
              ORDER BY score1 DESC";
    dbquery ($query);

    // Флот
    dbquery ("SET @pos := 0;");
    $query = "UPDATE ".$db_prefix."users
              SET place2 = (SELECT @pos := @pos+1)
              ORDER BY score2 DESC";
    dbquery ($query);

    // Исследования
    dbquery ("SET @pos := 0;");
    $query = "UPDATE ".$db_prefix."users
              SET place3 = (SELECT @pos := @pos+1)
              ORDER BY score3 DESC";
    dbquery ($query);

    // Специальная обработка для админов
    $query = "UPDATE ".$db_prefix."users SET place1 = 0, place2 = 0, place3 = 0 WHERE admin > 0";
    dbquery ($query);
}

// Отгрузить всех игроков
function UnloadAll ()
{
    global $db_prefix, $StartPage;
    $query = "UPDATE ".$db_prefix."users SET session = ''";
    dbquery ($query);

    ob_clean ();
    echo "<script>document.location.href='".$StartPage."';</script>Вы долго отсутствовали 0. (Войдите снова)<br>";
    ob_end_flush ();
}

// Сменить путь к скину
function ChangeSkinPath ($player_id, $dpath)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."users SET skin = '".$dpath."' WHERE player_id = $player_id";
    dbquery ($query);
}

// Включить/выключить отображение скина. При выключенном скине отображается скин по умолчанию.
function EnableSkin ($player_id, $enable)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."users SET useskin = $enable WHERE player_id = $player_id";
    dbquery ($query);
}

// Выдать список операторов вселенной
function EnumOperators ()
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE admin = 1 ORDER BY player_id ASC;";
    return dbquery ($query);
}

// Повторно выслать пароль и ссылку для активации.
function ReactivateUser ($player_id)
{
    global $db_prefix, $db_secret;
    $user = LoadUser ($player_id);
    if ($user == null) return;

    $len = 8;
    $r = '';
    for($i=0; $i<$len; $i++)
        $r .= chr(rand(0, 25) + ord('a'));
    $pass = $r;
    $md = md5 ($pass . $db_secret);

    $name = $user['oname'];
    $email = $user['pemail'];
    $ack = md5(time ().$db_secret);

    $query = "UPDATE ".$db_prefix."users SET validatemd = '".$ack."', validated = 0, password = '".$md."' WHERE player_id = $player_id";
    dbquery ($query);
    if ( $_SERVER['REMOTE_ADDR'] !== "127.0.0.1" ) SendGreetingsMail ( $name, $pass, $email, $ack);
}

// Очистить кеш игроков.
function InvalidateUserCache ()
{
    global $UserCache;
    $UserCache = array ();
}

// Вернуть имя игрока со ссылкой на страницу редактирования и статусом (ишка, РО и пр.)
function AdminUserName ($user)
{
    global $session;

    $name = $user['oname'];

    $week = time() - 604800;
    $week3 = time() - 604800*3;

    $status = "";
    if ( $user['lastclick'] <= $week ) $status .= "i";
    if ( $user['lastclick'] <= $week3 ) $status .= "I";
    if ( $user['vacation'] ) $status .= "РО";
    if ( $user['banned'] ) $status .= "з";
    if ( $user['noattack'] ) $status .= "А";
    if ( $user['disable'] ) $status .= "g";
    if ( $status !== "" ) $name .= " ($status)";

    if ( $user['disable'] ) $name = "<font color=orange>$name</font>";
    else if ( $user['banned'] ) $name = "<font color=red>$name</font>";
    else if ( $user['noattack'] ) $name = "<font color=yellow>$name</font>";
    else if ( $user['vacation'] ) $name = "<font color=skyBlue>$name</font>";
    else if ( $user['lastclick'] <= $week3 ) $name = "<font color=#999999>$name</font>";
    else if ( $user['lastclick'] <= $week ) $name = "<font color=#cccccc>$name</font>";

    $name = "<a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id']."\">$name</a>";
    return $name;
}

// Забанить игрока.
function BanUser ($player_id, $seconds, $vmode)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'UnbanPlayer' AND owner_id = $player_id";
    dbquery ($query);
    $now = time ();
    $when = $now + $seconds;
    $queue = array ( null, $player_id, "UnbanPlayer", 0, 0, 0, $now, $when, 0 );
    $id = AddDBRow ( $queue, "queue" );
    $query = "UPDATE ".$db_prefix."users SET score1 = 0, score2 = 0, score3 = 0, banned = 1, banned_until = $when";
    if ( $vmode ) $query .= ", vacation = 1, vacation_until = $when";
    $query .= " WHERE player_id = $player_id";
    dbquery ($query);
    RecalcRanks ();
}

// Запретить атаки.
function BanUserAttacks ($player_id, $seconds)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'AllowAttacks' AND owner_id = $player_id";
    dbquery ($query);
    $now = time ();
    $when = $now + $seconds;
    $queue = array ( null, $player_id, "AllowAttacks", 0, 0, 0, $now, $when, 0 );
    $id = AddDBRow ( $queue, "queue" );
    $query = "UPDATE ".$db_prefix."users SET noattack = 1, noattack_until = $when WHERE player_id = $player_id";
    dbquery ($query);
}

// Разбанить игрока
function UnbanUser ($player_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'UnbanPlayer' AND owner_id = $player_id";
    dbquery ($query);
    $query = "UPDATE ".$db_prefix."users SET banned = 0, banned_until = 0 WHERE player_id = $player_id";
    dbquery ($query);
    RecalcStats ($player_id);
    RecalcRanks ();
}

// Разрешить атаки
function UnbanUserAttacks ($player_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'AllowAttacks' AND owner_id = $player_id";
    dbquery ($query);
    $query = "UPDATE ".$db_prefix."users SET noattack = 0, noattack_until = 0 WHERE player_id = $player_id";
    dbquery ($query);
}

?>