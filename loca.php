<?

// LOCA - Localization Engine.

$loca_connect = 0;     // Идентификатор соединения с LOCA БД.
$loca_ready = false;    // Готовность LOCA.
$loca_lang = "en";        // Используемый язык.

// Подключиться к базе локализации.
// host, user, pass, dbname: адрес сервера, пользователь/пароль и название БД для соединения.
// lang: язык, используемый для вызова loca.
// Возвращает true, если соединение с LOCA установлено, или false если нет.
function loca_init ($loca_host, $loca_user, $loca_pass, $loca_dbname, $lang)
{
    global $loca_connect, $loca_ready, $loca_lang;
    $loca_connect = @mysql_connect($loca_host, $loca_user, $loca_pass, true);
    if (!$loca_connect) return false;
    $loca_select = @mysql_select_db($loca_dbname, $loca_connect);
    if (!$loca_select) return false;
    $loca_ready = $loca_connect && $loca_select;
    $loca_lang = $lang;

    @mysql_query ("SET NAMES 'utf8';", $loca_connect);
    @mysql_query ("SET CHARACTER SET 'utf8';", $loca_connect);
    @mysql_query ("SET SESSION collation_connection = 'utf8_general_ci';", $loca_connect);
    return $loca_ready;
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

        @mysql_query ("DROP TABLE IF EXISTS loca_table;", $loca_connect);
        @mysql_query ("CREATE TABLE loca_table (loca_id INT AUTO_INCREMENT PRIMARY KEY, name CHAR(64), val TEXT, lang CHAR(4), ver INT, date INT UNSIGNED) CHARACTER SET utf8 COLLATE utf8_general_ci;", $loca_connect);
    }
}

// Вернуть значение ключа. Возвращается последняя версия.
// Если соединение с LOCA отсутствует или такого ключа не существует, вернуть название ключа.
function loca ($key)
{
    global $loca_connect, $loca_ready, $loca_lang;
    if ($loca_ready == false) return $key;

    $result = @mysql_query("select * from loca_table where name = '".$key."' and lang = '".$loca_lang."' order by ver desc;", $loca_connect);
    $rows = @mysql_num_rows($result);
    if ($rows > 0) {
        $arr = @mysql_fetch_assoc($result);
        if ($arr) return $arr['val'];
    }
    return $key;
}

function loca_AddDBRow ( $row )        // используется только внутри модуля.
{
    global $loca_connect;
    $opt = " (";
    foreach ($row as $i=>$entry)
    {
        if ($i != 0) $opt .= ", ";
        $opt .= "'".$row[$i]."'";
    }
    $opt .= ")";
    $query = "INSERT INTO loca_table VALUES".$opt;
    @mysql_query( $query, $loca_connect);
}

// Добавить новую версию ключа.
function loca_add ($key, $value)
{
    global $loca_connect, $loca_ready, $loca_lang;

    // Получить номер версии.
    $result = @mysql_query("select * from loca_table where name = '".$key."' and lang = '".$loca_lang."' order by ver desc;", $loca_connect);
    $ver = @mysql_num_rows($result);

    if ($loca_ready) {    
        $row = array ( '', $key, $value, $loca_lang, $ver, time() );
        loca_AddDBRow ( $row );
    }
}

?>