<?php

// Управление пользователями.

/*
player_id: Порядковый номер пользователя (INT)
ally_id: Номер альянса в котором состоит игрок (0 - без альянса) (INT)
joindate: Дата вступления в альянс (INT UNSIGNED)
allyrank: Ранг игрока в альянсе (INT)
session: Сессия для ссылок (CHAR (12))
private_session: Приватная сессия для кукисов (CHAR(32))
name: Имя пользователя lower-case для сравнения (CHAR(20))
oname: Имя пользователя оригинальное (CHAR(20))
name_changed: Имя пользователя изменено? (1 или 0)
name_until: Когда можно поменять имя пользователя в следующий раз (INT UNSIGNED)
password: MD5-хеш пароля (CHAR(32))
pemail: Постоянный почтовый адрес (CHAR(50))
email: Временный почтовый адрес (CHAR(50))
email_changed: Временный почтовый адрес изменен
email_until: Когда заменить постоянный email на временный (INT UNSIGNED)
disable: Аккаунт поставлен на удаление
disable_until: Когда можно удалить аккаунт (INT UNSIGNED)
vacation: Аккаунт в режиме отпуска
vacation_until: Когда можно выключить режим отпуска (INT UNSIGNED)
banned: Аккаунт заблокирован
banned_until: Время окончания блокировки (INT UNSIGNED)
lastlogin: Последняя дата входа в игру (INT UNSIGNED)
lastclick: Последний щелчок мышкой, для определения активности игрока (INT UNSIGNED)
ip_addr: IP адрес пользователя
validated: Пользователь активирован. Если пользователь не активирован, то ему запрещено посылать игровые сообщения и заявки в альянсы.
validatemd: Код активации (CHAR(32))
hplanetid: Порядковый номер Главной планеты
admin: 0 - обычный игрок, 1 - оператор, 2 - супер-оператор, 3 - администратор
sortby: Порядок сортировки планет: 0 - порядку колонизации, 1 - координатам, 2 - алфавиту
sortorder: Порядок: 0 - по возрастанию, 1 - по убыванию
skin: Путь для скина (CHAR(80)). Получается путем слепления пути к хосту и названием скина, но длина строки не более 80 символов.
useskin: Показывать скин, если 0 - то показывать скин по умолчанию
deact_ip: Выключить проверку IP
maxspy: Кол-во шпионских зондов (1 по умолчанию, 0...99)
maxfleetmsg: Максимальные сообщения о флоте в Галактику (3 по умолчанию, 0...99, 0=1)
aktplanet: Текущая выбранная планета.
dm: Покупная ТМ
dmfree: ТМ найденная в экспедиции
score1,2,3: Очки за постройки, флот, исследования (INT UNSIGNED)
place1,2,3: Место за постройки, флот, исследования.
rXXX: Уровень исследования XXX
*/

function mail_utf8($to, $subject = '(No subject)', $message = '', $header = '') {
  $header_ = 'MIME-Version: 1.0' . "\r\n" . 'Content-type: text/plain; charset=UTF-8' . "\r\n";
  mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
}

// Выслать приветственное письмо с ссылкой для активации аккаунта.
function SendGreetingsMail ( $name, $pass, $email, $ack)
{
    global $Host;
    $text = "Приветствуем $name,\n\n" .
                "Вы решили создать свою империю в $uni-й вселенной ОГейма!\n\n" .
                "Нажмите на эту ссылку для активации Вашего аккаунта:\n" .
                $Host."index.php?page=validate&ack=$ack\n\n" .
                "Ваши игровые данные:\n" .
                "Игровое имя: $name\n" .
                "Пароль: $pass\n" .
                "Вселенная: $uni\n\n\n" .
                "Если Вам понадобится помощь или совет других императоров, то всё это Вы сможете найти на нашем форуме (http://board.ogame.ru).\n\n" .
                "Здесь (http://tutorial.ogame.ru) собрана вся информация, собранная игроками и членами команды для того, чтобы помочь новичкам как можно быстрее разобраться в игре.\n\n" .
                "Желаем успехов в построении империи и удачи в предстоящих боях!\n\n" .
                "Ваша команда ОГейма";
    //echo "<pre>$text</pre><br>\n";
    mail_utf8 ( $email, "Добро пожаловать в ОГейм ", $text, "From: OGame Uni ru $uni <noreply@mmogame.com>");
}

// Выслать письмо, подтверждающее смену адреса.
function SendChangeMail ( $name, $email, $pemail, $ack)
{
    global $Host;
    $text = "Приветствуем $name,\n\n" .
               "временный адрес e-mail Вашего аккаунта в $uni-й вселенной был изменён в настройках на $email.\n" .
               "Если Вы его не измените в течение недели, то он станет постоянным.\n\n" .
               "Чтобы беспрепятственно продолжить игру, подтвердите ваш новый адрес e-mail по следующей ссылке:\n\n" .
               $Host."index.php?page=validate&ack=$ack\n\n" .
               "Ваша команда OGame";
    mail_utf8 ( $pemail, "Ваш игровой электронный адрес изменён ", $text, "From: OGame Uni ru $uni <noreply@mmogame.com>");
}

// Выслать приветственное сообщение.
function SendGreetingsMessage ( $player_id)
{
    SendMessage ( $player_id, "Командование флотом", "Добро пожаловать в ОГейм!", 
        "Добро пожаловать в [b]OGame[/b] !\n"
        . "\n"
        . "Для начала Вам необходимо развить рудники.\n"
        . "Это можно сделать в меню \"постройки\".\n"
        . "Выберите рудник по добыче металла и нажмите на \"строить\".\n"
        . "Теперь у Вас есть немного времени для ознакомления с игрой.\n"
        . "Помощь по игре Вы можете найти по этим ссылкам: \n"
        . "[url=http://tutorial.ogame.ru/]Туториал (англ.)[/url]\n"
        . "[url=http://board.ogame.ru]Форум[/url]\n"
        . "Также Вы можете обратиться на канал службы поддержки:\n"
        . "http://www.onlinegamesnet.net/javaChat.php\n"
        . "\n"
        . "Тем временем Ваш рудник уже должен построиться.\n"
        . "Для работы рудников необходима энергия, для её получения постройте солнечную электростанцию.\n"
        . "Для этого снова зайдите в меню \"постройки\" и кликните на электростанции.\n"
        . "Для того, чтобы посмотреть, насколько далеко Вы зашли в развитии, зайдите в меню \"Технологии\".\n"
        . "Итак, Ваш победный поход по вселенной начался... Удачи!\n", 1 );
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
function CreateUser ( $name, $pass, $email)
{
    global $db_prefix, $db_secret;
    $origname = $name;
    $name = mb_strtolower ($name, 'UTF-8');
    $email = mb_strtolower ($email, 'UTF-8');
    $md = md5 ($pass . $db_secret);
    $ack = md5(time ().$db_secret);

    // Получить следующий уникальный номер и увеличить его на 1 для следующего пользователя.
    $query = "SELECT * FROM ".$db_prefix."uni".";";
    $result = dbquery ($query);
    $unitab = dbarray ($result);
    $id = $unitab['nextuser']++;
    $unitab['usercount']++;
    $query = "UPDATE ".$db_prefix."uni"." SET nextuser = ".$unitab['nextuser'].", usercount = ".$unitab['usercount'].";";
    dbquery ($query);

    // Создать Главную планету.
    // 1. g = s = 1, p = 4.
    // 2. Если p >= 12: s = s + 1, p = 4. Если s == 500: g = g + 1, s = 1. (Перейти на следующую систему/галактику)
    // 3. Если позиция не занята - записать [g:s:p] как Главную планету новому пользователю.
    // 4. p = p + 1 или 2. Перейти на 2.
    $g = $s = 1; $p = 3;
    while (1)
    {
        $p += rand (1, 2);
        if ( $p >= 12) {
            $s++; $p = 4;
            if ($s == 500) { $g++; $s = 1; }
        }
        $query = "SELECT * FROM ".$db_prefix."planets WHERE g = '".$g."' AND s = '".$s."' AND p = '".$p."' AND type <> 0";
        $result = dbquery ($query);
        if (dbrows ($result) == 0) break;
    }
    $homeplanet = CreatePlanet ( $g, $s, $p, $id, 0);

    $user = array( $id, 0, 0, 0, "",  "", $name, $origname, 0, 0, $md, $email, $email, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "0.0.0.0", 0, $ack, $homeplanet, 0, 0, 0, "evolution/", 1, 0, 1, 3, $homeplanet,
                        0, 0, 
                        0, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    AddDBRow ( $user, "users" );

    // Выслать приветственное письмо и сообщение.
    SendGreetingsMail ( $origname, $pass, $email, $ack);
    SendGreetingsMessage ( $id);

    // Удалить неактивированного пользователя через 3 дня.

    return $id;
}

function RemoveUser ( $player_id)
{
    global $db_prefix;

    // Аккаунт администратора нельзя удалить.
    if ($player_id == 1) return;

    $query = "DELETE FROM ".$db_prefix."users"." WHERE player_id = '".$player_id."'";
    dbquery ($query);

    // Уменьшить количество пользователей.
    $query = "SELECT * FROM ".$db_prefix."uni".";";
    $result = dbquery ($query);
    $unitab = dbarray ($result);
    $unitab['usercount']--;
    if ($unitab['usercount'] < 0) $unitab['usercount'] = 0;
    $query = "UPDATE ".$db_prefix."uni"." SET usercount = ".$unitab['usercount'].";";
    dbquery ($query);
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
    {    // Заменить постонный адрес временным после активации.
        $query = "UPDATE ".$db_prefix."users SET pemail = '".$user['email']."' WHERE player_id = ".$user['player_id'];
        dbquery ($query);
    }
    $query = "UPDATE ".$db_prefix."users SET validatemd = '', validated = 1 WHERE player_id = ".$user['player_id'];
    dbquery ($query);
    Login ( $user['oname'], "", $user['password'] );
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
    global $db_prefix, $GlobalUser;
    $planet = GetPlanet ($cp);    // Нельзя выбирать чужие планеты.
    if ($planet['owner_id'] != $player_id) return;
    $query = "UPDATE ".$db_prefix."users SET aktplanet = '".$cp."' WHERE player_id = '".$player_id."'";
    dbquery ($query);
    $GlobalUser['aktplanet'] = $cp;
}

// Загрузить пользователя.
function LoadUser ( $player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE player_id = '".$player_id."'";
    $result = dbquery ($query);
    return dbarray ($result);
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
    return $prem;
}

?>