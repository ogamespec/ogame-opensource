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

/*
Типы сообщений (pm):
0 - личное сообщение
1 - шпионский доклад
2 - ссылка на боевой доклад
3 - сообщение из экспедиции
4 - альянс
5 - прочие
6 - текст боевого доклада
*/

// Всего у пользователя может быть не более 127 сообщений. Если происходит переполнение, то самое старое сообщение удаляется и добавляется новое.
// Сообщение хранится 24 часа.

// В личных сообщениях можно использовать BB-коды:
// Простые: b u i s sub sup hr
// Цвет: [color=ЦВЕТ][/color], размер [size=РАЗМЕР][/size], шрифт [font=FONT][/font], цитата [quote=От кого][/quote]
// URL: [url=ПУТЬ][/url], Email: [email=EMAIL][/email], Картинка [img=путь][/img]
// Выравнивание: [align=left,right,center][/align]

// Если в "от кого", теме или тексте сообщения есть слово {PUBLIC_SESSION}, то при выводе оно заменяется на текущую сессию пользователя.

// У каждого пользователя есть лимит сообщений в сутки. Выводится ошибка "Вы сегодня написали слишком много".

// Удалить все старые сообщения (вызывается из меню Сообщения)
function DeleteExpiredMessages ($player_id)
{
    global $db_prefix;
    $now = time ();
    $hours24 = 60 * 60 * 24;

    // Не удалять сообщения администрации.
    $user = LoadUser ($player_id);
    if ($user['admin'] > 0 ) return;

    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id";
    $result = dbquery ($query);
    $num = dbrows ($result);
    while ($num--)
    {
        $msg = dbarray ($result);
        if ( ($msg['date'] + $hours24) <= $now ) DeleteMessage ($player_id, $msg['msg_id']);
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

// Послать сообщение. Возвращает id нового сообщения. (может вызываться откуда угодно)
function SendMessage ($player_id, $from, $subj, $text, $pm, $when=0)
{
    global $db_prefix;

    if ($when == 0) $when = time ();

    // Обработать параметры.
    if ($pm == 0) {
        $text = mb_substr ($text, 0, 2000, "UTF-8");
        //$text = bb ($text);
    }

    // Получить количество сообщений для пользователя.
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) >= 127 )    // Удалить самое старое сообщение и освободить место для нового.
    {
        DeleteOldestMessage ($player_id);
    }

    // Добавить сообщение.
    $msg = array( null, $player_id, $pm, $from, $subj, $text, 0, $when );
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
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND pm <> 6 ORDER BY date DESC, msg_id DESC LIMIT $max";
    $result = dbquery ($query);
    return $result;
}

// Получить количество непрочитанных сообщений (вызывается из Обзора)
function UnreadMessages ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id AND shown = 0";
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

?>