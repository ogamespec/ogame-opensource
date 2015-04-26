<?php

// API для управления купонами.

/*
Немного о самой системе.

Купоны хранятся в "мастер-базе" - на том же сервере, что и главная страница игры (Start Page).
Мастер-база имеет доступ ко всем вселенным через таблицу unis, а также все вселенные имеют доступ к мастер-базе через /game/config.php
(если доступ к мастер-базе разрешен через переменную mdb_enable)

Сделано это по причине того, что купоны могут быть использованы в любой вселенной.

Код купона выглядит примерно так : 2B2D-FE3D-7D74-37C4-D26M (комбинация цифр и больших латинских букв)

Купоны рассылаются автоматически всем активным игрокам (активны в игре более 7 дней).
Даты рассылки задает администратор вселенной (обычно на Новый Год и другие национальные праздники)
Задание рассылке купонов добавляется в Queue, обработчик этого задания находится в этом модуле.

Вся ТМ, начисляемая через купоны является платной.

*/

// Функция для отправки письма с кодом купона (UTF-8, HTML).
function mail_html ($to, $subject = '(No subject)', $message = '', $header = '') {
    if ( $ip !== "127.0.0.1" ) {
        $header_ = 'MIME-Version: 1.0' . "\n" . 'Content-type: text/html; charset=UTF-8' . "\n";
        mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
    }

    // Добавить лог в temp.
    $f = fopen ( "temp/mailto.log", "a" );
    fprintf ( $f, "To: %s\r\nSubj: %s\r\n\r\n%s\r\n", $to, $subject, $message );
    fclose ($f);
}

// Link для соединения с мастер базой
$MDB_link = 0;

function MDBConnect ()
{
    global $MDB_link, $mdb_host, $mdb_user, $mdb_pass, $mdb_name, $mdb_enable;
    if (!$mdb_enable) return FALSE;
    $MDB_link = @mysql_connect ($mdb_host, $mdb_user, $mdb_pass );
    if (!$MDB_link) return FALSE;
    if ( ! @mysql_select_db ($mdb_name, $MDB_link) ) return FALSE;

    MDBQuery ("SET NAMES 'utf8';");
    MDBQuery ("SET CHARACTER SET 'utf8';");
    MDBQuery ("SET SESSION collation_connection = 'utf8_general_ci';");

    return TRUE;
}

function MDBQuery ($query)
{
    global $MDB_link;
    $result = @mysql_query ($query, $MDB_link);
    if (!$result) return NULL;
    else return $result;
}

function MDBRows ($result)
{
    $rows = @mysql_num_rows($result);
    return $rows;
}

function MDBArray ($result)
{
    $arr = @mysql_fetch_assoc($result);
    if (!$arr) return NULL;
    else return $arr;
}

// ------------------------------------------------------------------

// Загрузить объект купона по ID. Вернуть NULL, если купон не найден.
function LoadCoupon ($id)
{
    if ( MDBConnect() == FALSE) return NULL;

    $query = "SELECT * FROM coupons WHERE id = " . intval ($id) . " LIMIT 1";
    $result = MDBQuery ( $query );
    if ( $result ) return MDBArray ( $result );
    else return NULL;
}

// Отправить код купона указанному пользователю
function SendCoupon ($user, $code)
{
    global $GlobalUni;

    loca_add ( "coupons", $GlobalUni['lang'] );    // добавить языковые ключи пользователя, которому посылается сообщение.

    mail_html ( $user['pemail'], loca("COUPON_SUBJ"), va ( loca("COUPON_MESSAGE"), $user['oname'], $code ), "From: coupon@" . hostname() );
}

// Проверить есть ли такой купон и он не активирован. Возвращается ID купона или 0, если неверный код купона или купон погашен.
function CheckCoupon ($code)
{
    if ( MDBConnect() )
    {
        $query = "SELECT * FROM coupons WHERE used = 0 AND code = '".$code."' LIMIT 1";
        $result = MDBQuery ($query );
        if (MDBRows ($result) )
        {
            $coupon = MDBArray ($result);
            return $coupon['id'];
        }
        else return 0;
    }
    else return 0;
}

// Перечислить все купоны. Вернуть result SQL-запроса. Параметры вызова для пагинатора (start, count)
function EnumCoupons ($start, $count)
{
    if ( MDBConnect() )
    {
        $query = "SELECT * FROM coupons ORDER BY id DESC LIMIT $start, $count";
        return MDBQuery ($query);
    }
    else return NULL;
}

// Количество купонов в базе
function TotalCoupons ()
{
    if ( MDBConnect() )
    {
        $query = "SELECT COUNT(*) FROM coupons;";
        $result = MDBQuery ( $query );
        $arr = MDBArray ( $result );
        foreach ( $arr as $i=>$val) {
            return $val;
        }
    }
    else return 0;
}

// Добавить купон (количество DM). Вернуть код купона, или NULL если неудача.
function AddCoupon ($dm)
{
    global $db_secret;
    $timeout = 10;

    if ( MDBConnect() )
    {
        while ($timeout--) {
            $code = substr( chunk_split ( strtoupper( substr(base_convert(sha1(uniqid(mt_rand()) . $db_secret), 16, 36), 0, 20) ), 4, '-' ) , 0, -1);
            if ( CheckCoupon ($code) == 0 ) break;
        }
        if ( $timeout == 0 ) return NULL;
        $query = "INSERT INTO coupons VALUES (NULL, '".$code."', ".intval($dm).", 0, 0, 0, '' )";
        MDBQuery ($query);
        return $code;
    }
    else return NULL;
}

// Активировать купон. Вернуть TRUE, если всё хорошо или FALSE, если какая-то фигня.
function ActivateCoupon ($user, $code)
{
    global $GlobalUni, $db_prefix;

    if ( MDBConnect() )
    {
        $id = CheckCoupon ($code);
        if ( $id ) {
            $coupon = LoadCoupon ($id);
            $query = "UPDATE coupons SET used=1, user_uni='".$GlobalUni['num']."', user_id='".$user['player_id']."', user_name='".$user['oname']."' WHERE id = $id";    // погасить купон
            MDBQuery ($query);
            $query = "UPDATE ".$db_prefix."users SET dm = dm + ".$coupon['amount']." WHERE player_id = " . $user['player_id'];    // добавить пользователю платной ТМ.
            dbquery ($query);
            return TRUE;
        }
        else return FALSE;
    }
    else return FALSE;
}

// Удалить купон
function DeleteCoupon ($id)
{
    if ( MDBConnect() )
    {
        $query = "DELETE FROM coupons WHERE id = " . intval ($id);
        MDBQuery ($query);
    }
}

// Обработчик задания начисления купонов.
// sub_id : Количество ТМ
// obj_id : (Неактивен не менее ... дней << 16) | (Находится в игре более ... дней)
// level : Периодичность ... дней
function Queue_Coupon_End ($queue)
{
    global $db_prefix;

    $now = $queue['end'];
    $ip = $_SERVER['REMOTE_ADDR'];

    // Выбрать пользователей согласно критериям.
    $inactive_days = ($queue['obj_id'] >> 16) & 0xffff;
    $ingame_days = $queue['obj_id'] & 0xffff;
    $query = "SELECT * FROM ".$db_prefix."users WHERE regdate < ".($now - $ingame_days * 24*60*60)." AND lastclick >= " . ($now - $inactive_days * 24*60*60);
    $result = dbquery ($query);

    while ( $user = dbarray ($result) )    // Разослать сообщения с купонами
    {
        $code = AddCoupon ( $queue['sub_id'] );
        SendCoupon ( $user, $code );
    }

    // Продлить или завершить задание.
    $seconds = $queue['level'] * 24 * 60 * 60;
    if ( $seconds > 0 ) ProlongQueue ( $queue['task_id'], $seconds );
    else RemoveQueue ( $queue['task_id'] );
}

?>