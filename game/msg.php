<?php

// Управление сообщениями.

// Запись сообщения в БД.
// msg_id: Порядковый номер сообщения (INT)
// owner_id: Порядковый номер пользователя которому принадлежит сообщение
// pm: Тип сообщения, 0: личное сообщение (можно пожаловаться оператору), ...
// msgfrom: От кого, HTML (TEXT)
// subj: Тема, HTML, может быть текст, может быть ссылка на доклад (TEXT)
// text: Текст сообщения (TEXT)
// shown: 0 - новое сообщение, 1 - показанное.
// date: Дата сообщения (INT UNSIGNED)

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
function SendMessage ($player_id, $from, $subj, $text, $pm)
{
    global $db_prefix;

    // Обработать параметры.
    $text = mb_substr ($text, 0, 2000, "UTF-8");
    $text = bb ($text);

    // Получить количество сообщений для пользователя.
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id";
    $result = dbquery ($query);
    if ( dbrows ($result) >= 127 )    // Удалить самое старое сообщение и освободить место для нового.
    {
        DeleteOldestMessage ($player_id);
    }

    // Получить следующий уникальный номер и увеличить его на 1 для следующего сообщения.
    $id = IncrementDBGlobal ( 'nextmsg' );

    // Добавить сообщение.
    $msg = array( $id, $player_id, $pm, $from, $subj, $text, 0, time() );
    AddDBRow ( $msg, "messages" );

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
function EnumMessages ($player_id, $max)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."messages WHERE owner_id = $player_id ORDER BY date DESC LIMIT $max";
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

?>