<?php

// LOCA - Localization Engine.

$loca_connect = 0;     // Идентификатор соединения с LOCA БД.
$loca_ready = false;    // Готовность LOCA.
$loca_lang = "en";        // Используемый язык.
$loca_project = -1;     // Используемый проект.

// Подключиться к базе локализации.
// host, user, pass, dbname: адрес сервера, пользователь/пароль и название БД для соединения.
// lang: язык, используемый для вызова loca.
// proj: название проекта, например "OGame Startpage".
// Возвращает true, если соединение с LOCA установлено, или false если нет.
function loca_init ($loca_host, $loca_user, $loca_pass, $loca_dbname, $proj)
{
    global $loca_connect, $loca_ready, $loca_lang, $loca_project;
    $loca_connect = @mysql_connect($loca_host, $loca_user, $loca_pass, true);
    if (!$loca_connect) return false;
    $loca_select = @mysql_select_db($loca_dbname, $loca_connect);
    if (!$loca_select) return false;
    $loca_ready = $loca_connect && $loca_select;

    @mysql_query ("SET NAMES 'utf8';", $loca_connect);
    @mysql_query ("SET CHARACTER SET 'utf8';", $loca_connect);
    @mysql_query ("SET SESSION collation_connection = 'utf8_general_ci';", $loca_connect);

    // Выбрать проект.
    $loca_project = loca_project_id ($proj);
    if ($loca_project == -1) {
        return false;
    }

    return $loca_ready;
}

// Закрыть соединение с LOCA.
function loca_close ()
{
    global $loca_connect, $loca_ready;
    if ($loca_ready) {
        @mysql_close($loca_connect);
        $loca_ready = false;
    }
}

// Сбросить/создать таблицу локализации.
function loca_reset ($loca_host, $loca_user, $loca_pass, $loca_dbname)
{
    $loca_connect = @mysql_connect($loca_host, $loca_user, $loca_pass, true);
    $loca_select = @mysql_select_db($loca_dbname, $loca_connect);

    if ($loca_connect && $loca_select) {
        @mysql_query ("SET NAMES 'utf8';", $loca_connect);
        @mysql_query ("SET CHARACTER SET 'utf8';", $loca_connect);
        @mysql_query ("SET SESSION collation_connection = 'utf8_general_ci';", $loca_connect);

        // Таблица ключей
        @mysql_query ("DROP TABLE IF EXISTS loca_table;", $loca_connect);
        @mysql_query ("CREATE TABLE loca_table (loca_id INT AUTO_INCREMENT PRIMARY KEY, name CHAR(64), val TEXT, lang CHAR(4), proj INT, ver INT, date INT UNSIGNED) CHARACTER SET utf8 COLLATE utf8_general_ci;", $loca_connect);

        // Таблица проектов
        @mysql_query ("DROP TABLE IF EXISTS loca_projects;", $loca_connect);
        @mysql_query ("CREATE TABLE loca_projects (project_id INT AUTO_INCREMENT PRIMARY KEY, name CHAR(64), date INT UNSIGNED) CHARACTER SET utf8 COLLATE utf8_general_ci;", $loca_connect);
    }
}

// Вернуть значение ключа. Возвращается последняя версия.
// Если соединение с LOCA отсутствует или такого ключа не существует, вернуть название ключа.
function loca ($key)
{
    global $loca_connect, $loca_ready, $loca_lang, $loca_project;
    if ($loca_ready == false) return $key;

    $result = @mysql_query("select * from loca_table where name = '".$key."' and proj = $loca_project and lang = '".$loca_lang."' order by ver desc;", $loca_connect);
    $rows = @mysql_num_rows($result);
    if ($rows > 0) {
        $arr = @mysql_fetch_assoc($result);
        if ($arr) return $arr['val'];
    }
    return $key;
}

function loca_AddDBRow ( $row, $table )        // используется только внутри модуля.
{
    global $loca_connect;
    $opt = " (";
    foreach ($row as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$row[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO $table VALUES".$opt;
    @mysql_query( $query, $loca_connect);
}

// Добавить новую версию ключа.
function loca_add ($key, $value)
{
    global $loca_connect, $loca_ready, $loca_lang, $loca_project;

    // Получить номер версии.
    $result = @mysql_query("select * from loca_table where name = '".$key."' and proj = $loca_project and lang = '".$loca_lang."';", $loca_connect);
    $ver = @mysql_num_rows($result);

    if ($loca_ready) {    
        $row = array ( '', $key, $value, $loca_lang, $loca_project, $ver, time() );
        loca_AddDBRow ( $row, "loca_table" );
    }
}

/////// Проекты LOCA.

// Добавить проект.
function loca_add_project ($name)
{
    global $loca_connect, $loca_ready;

    $id = loca_project_id ($name);

    if ($loca_ready && $id == -1) {    
        $row = array ( '', $name, time() );
        loca_AddDBRow ( $row, "loca_projects" );
    }
}

// Получить ID проекта. Вернуть -1, если такого проекта не существует.
function loca_project_id ($name)
{
    global $loca_connect, $loca_ready;

    if ($loca_ready) {
        $result = @mysql_query("select * from loca_projects where name = '".$name."';", $loca_connect);
        $rows = @mysql_num_rows($result);
        if ($rows > 0) {
            $row = @mysql_fetch_assoc($result);
            if ($row) return $row['project_id'];
        }
    }
    return -1;
}

?>