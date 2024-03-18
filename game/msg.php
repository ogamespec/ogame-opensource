<?php

// Управление сообщениями.

// Запись сообщения в БД.
// msg_id: Порядковый номер сообщения (INT AUTO_INCREMENT PRIMARY KEY)
// owner_id: Порядковый номер пользователя которому принадлежит сообщение
// pm: Тип сообщения, 0: личное сообщение (можно пожаловаться оператору), ...
// msgfrom: От кого, HTML (TEXT)
// subj: Тема, HTML, может быть текст, может быть ссылка на доклад (TEXT)
// text: Текст сообщения (TEXT)
// shown: 0 - новое сообщение, 1 - показанное.
// date: Дата сообщения (INT UNSIGNED)
// planet_id: Порядковый номер планеты/луны. Используется для сообщений шпионажа, чтобы отобразить общие шпионские доклады в галактике

// Типы сообщений (pm)
// Так получилось, что на ранних стадиях разработки pm=1 означал что сообщение - личное. Когда пришла пора делать фильтры для Командира было решено не заводить новую колонку type в таблице, а использовать pm.
const MTYP_PM = 0;              // личное сообщение
const MTYP_SPY_REPORT = 1;              // шпионский доклад
const MTYP_BATTLE_REPORT_LINK = 2;      // ссылка на боевой доклад И ракетная атака
const MTYP_EXP = 3;             // сообщение из экспедиции
const MTYP_ALLY = 4;            // альянс
const MTYP_MISC = 5;            // прочие
const MTYP_BATTLE_REPORT_TEXT = 6;      // текст боевого доклада

// Всего у пользователя может быть не более 127 сообщений. Если происходит переполнение, то самое старое сообщение удаляется и добавляется новое.
// Сообщение хранится 24 часа (7 дней с Командиром)

// В личных сообщениях можно использовать BB-коды:
// Простые: b u i s sub sup hr
// Цвет: [color=ЦВЕТ][/color], размер [size=РАЗМЕР][/size], шрифт [font=FONT][/font], цитата [quote=От кого][/quote]
// URL: [url=ПУТЬ][/url], Email: [email=EMAIL][/email], Картинка [img=путь][/img]
// Выравнивание: [align=left,right,center][/align]

// Если в "от кого", теме или тексте сообщения есть слово {PUBLIC_SESSION}, то при выводе оно заменяется на текущую сессию пользователя.

// У каждого пользователя есть лимит сообщений в сутки. Выводится ошибка "Вы сегодня написали слишком много".

// Удалить все старые сообщения (вызывается из меню Сообщения)
function DeleteExpiredMessages ($player_id, $days)
{
    global $db_prefix;
    $now = time ();
    $hours = 60 * 60 * 24 * $days;

    // Не удалять сообщения администрации.
    $user = LoadUser ($player_id);
    if ($user['admin'] > 0 ) return;

    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id";
    $result = dbquery ($query);
    $num = dbrows ($result);
    while ($num--)
    {
        $msg = dbarray ($result);
        if ( ($msg['date'] + $hours) <= $now ) DeleteMessage ($player_id, $msg['msg_id']);
    }
}

// Удалить самое старое сообщение (вызывается из SendMessage)
function DeleteOldestMessage ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id ORDER BY date ASC";
    $result = dbquery ($query);
    $msg = dbarray ($result);
    DeleteMessage ( $player_id, $msg['msg_id']);
}

// Послать сообщение. Возвращает id нового сообщения. (может вызываться откуда угодно); planet_id используется для шпионских докладов.
function SendMessage ($player_id, $from, $subj, $text, $pm, $when=0, $planet_id=0)
{
    global $db_prefix;

    if ($when == 0) $when = time ();

    // Обработать параметры.
    if ($pm == 0) {
        $text = mb_substr ($text, 0, 2000, "UTF-8");
        //$text = bb ($text);
    }

    $text = addslashes($text);

    // Получить количество сообщений для пользователя.
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) >= 127 )    // Удалить самое старое сообщение и освободить место для нового.
    {
        DeleteOldestMessage ($player_id);
    }

    // Добавить сообщение.
    $msg = array( null, $player_id, $pm, $from, $subj, $text, 0, $when, $planet_id );
    $id = AddDBRow ( $msg, "messages" );

    return $id;
}

// Удалить сообщение (вызывается из меню Сообщения)
function DeleteMessage ($player_id, $msg_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."messages WHERE owner_id = $player_id AND msg_id = $msg_id";
    dbquery ($query);
}

// Загрузить последние N сообщений (вызывается из меню Сообщения).
// Не загружать текст боевых докладов
function EnumMessages ($player_id, $max)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND pm <> ".MTYP_BATTLE_REPORT_TEXT." ORDER BY date DESC, msg_id DESC LIMIT $max";
    $result = dbquery ($query);
    return $result;
}

// Получить количество непрочитанных сообщений (вызывается из Обзора)
function UnreadMessages ($player_id, $filter=false, $pm=0)
{
    global $db_prefix;

    // Добавить условие для фильтрации (используется для показа количества непрочитанных сообщений в папке)
    $filter_str = "";
    if ($filter) {
        $filter_str = "AND pm = $pm";
    }

    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND shown = 0 $filter_str";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Пометить сообщение как прочтенное (вызывается из меню Сообщения).
function MarkMessage ($player_id, $msg_id)
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."messages SET shown = 1 WHERE owner_id = $player_id AND msg_id = $msg_id";
    dbquery ($query);
}

// Загрузить сообщение.
function LoadMessage ( $msg_id )
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE msg_id = $msg_id";
    $result = dbquery ($query);
    if ( $result ) return dbarray ($result);
    else return NULL;
}

// Удалить все сообщения
function DeleteAllMessages ($player_id)
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."messages WHERE owner_id = $player_id";
    dbquery ($query);
}

// Получить msg_id общего шпионского доклада для указанной планеты. Если доклада нет - вернуть 0.
function GetSharedSpyReport ($planet_id, $player_id, $ally_id)
{
    global $db_prefix;
    if ($ally_id != 0) {
        $sub_query = "SELECT player_id FROM ".$db_prefix."users WHERE ally_id = $ally_id";
        $query = "SELECT * FROM ".$db_prefix."messages WHERE pm = 1 AND planet_id = $planet_id AND owner_id IN (".$sub_query.") ORDER BY date DESC LIMIT 1";
    }
    else {
        $query = "SELECT * FROM ".$db_prefix."messages WHERE pm = 1 AND planet_id = $planet_id AND owner_id = $player_id ORDER BY date DESC LIMIT 1";
    }
    $result = dbquery ($query);
    if ( $result ) {
        $msg = dbarray ($result);
        return $msg['msg_id'];
    }
    return 0;
}

// Вернуть количество сообщений определенного типа (используется для показа общего количества сообщений в папке)
function TotalMessages ($player_id, $pm)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND pm = $pm";
    $result = dbquery ($query);
    return dbrows ($result);
}

?>