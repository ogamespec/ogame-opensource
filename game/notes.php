<?php

// Заметки.

// Записи в таблице БД:
// note_id: Порядковый номер заметки (INT)
// owner_id: ID пользователя (INT)
// subj: Тема заметки (CHAR(30))
// text: Текст заметки (TEXT)
// textsize: Размер текста заметки (INT)
// prio: Приоритет (0: Неважно (зеленый), 1: Так себе (желтый), 2: Важно (красный) ) (INT)
// date: Дата создания/редактирования заметки ('INT UNSIGNED')

function LoadNote ( $player_id, $note_id )
{
    global $db_prefix;    
    $query = "SELECT * FROM ".$db_prefix."notes WHERE owner_id = $player_id AND note_id = $note_id";
    $result = dbquery ($query);
    return dbarray ($result);
}

function AddNote ( $player_id, $subj, $text, $prio )
{
    global $db_prefix;

    // Проверить параметры.
    if ($subj === "") $subj = "без темы";
    if ($text === "") $text = "пусто";
    $text = mb_substr ($text, 0, 5000, "UTF-8");
    $subj = mb_substr ($subj, 0, 30, "UTF-8");
    if ($prio < 0) $prio = 0;
    if ($prio > 2) $prio = 2;

    // Получить следующий уникальный номер и увеличить его на 1 для следующего пользователя.
    $id = IncrementDBGlobal ( 'nextnote' );

    // Записать заметку в БД.
    $note = array( $id, $player_id, $subj, $text, mb_strlen ($text, "UTF-8"), $prio, time() );
    AddDBRow ( $note, "notes" );
}

function UpdateNote ( $player_id, $note_id, $subj, $text, $prio )
{
    global $db_prefix;

    // Проверить параметры.
    if ($subj === "") $subj = "без темы";
    if ($text === "") $text = "пусто";
    $text = mb_substr ($text, 0, 5000, "UTF-8");
    $subj = mb_substr ($subj, 0, 30, "UTF-8");
    if ($prio < 0) $prio = 0;
    if ($prio > 2) $prio = 2;

    $query = "UPDATE ".$db_prefix."notes SET subj = '".$subj."', text = '".$text."', textsize = '".mb_strlen($text, "UTF-8")."', prio = '".$prio."', date = '".time()."' WHERE owner_id = $player_id AND note_id = $note_id";
    dbquery ($query);
}

function DelNote ( $player_id, $note_id )
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."notes WHERE owner_id = $player_id AND note_id = $note_id";
    dbquery ($query);
}

function EnumNotes ($player_id)
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."notes WHERE owner_id = $player_id";
    $result = dbquery ($query);
    return $result;
}

?>