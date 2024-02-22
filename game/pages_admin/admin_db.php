<?php

// Админка: Целостность базы данных (проверка и исправление таблиц)

// Назначение этого модуля админки - хоть немного уменьшить попа-боль, когда в формат таблиц БД вносятся изменения и нужно править серверную БД "наживую" (без чистой переустановки).
// Вначале производится сравнение таблиц из install.php с тем что есть в реальной БД на данный момент.
// Затем производится обратное сравнение - что сейчас в БД, с тем что есть в таблицах install.php

// TODO: Лечить таблицы пока не умеем. Админ должен ручками лечить таблицы самостоятельно, используя phpMyAdmin или подобный инструмент.

function DiffTab ($tabname, $src, $dst)
{
    $error = false;
    $res = "";

    $res .= "<table>\n";
    $res .= "<tr><td class=\"c\" colspan=3>$tabname</td></tr>";

    foreach ($src as $key=>$value) {
        
        $res .= "<tr><td>$key</td>";
        if (key_exists($key, $dst)) {
            $res .= "<td>".$dst[$key]."</td>";
        }
        else {
            $res .= "<td><font color=red>".loca("ADM_DB_COLUMN_NOT_FOUND")."</font></td>";
            $error = true;
        }
        $res .= "<td>$value</td></tr>";

    }

    $res .= "</table>\n";

    return $error ? $res : "";
}

function Admin_DB ()
{
    global $session;
    global $db_prefix;
    global $db_name;
    global $GlobalUser;

    include "install_tabs.php";

    $text_out = "";

    // DEBUG: Сдампить таблицы игры из install
    //print_r ($tabs);
    //echo "<br/><br/>";

    // Обработка POST-запроса.
    if ( method () === "POST" )
    {
        // TODO: Пока ничего не делаем.
    }

    // Получить список таблиц

    $query = "SHOW TABLES;";
    $result = dbquery ($query);
    $db_tabs = array();
    $rows = dbrows ($result);
    while ($rows--) {

        $row = dbarray ($result);
        $tablename = $row["Tables_in_" . $db_name];
        $tablename = str_replace ($db_prefix, "", $tablename);
        $db_tabs[$tablename] = array();
    }
    dbfree ($result);

    // Получить список столбцов для каждой таблицы

    foreach ($db_tabs as $i=>$tab) {
        
        $query = "SHOW COLUMNS FROM $db_prefix$i;";
        $result = dbquery($query);

        $rows = dbrows ($result);
        while ($rows--) {

            $row = dbarray ($result);
            
            // Привести описание типа столбца по аналогии с таблицей из install
            $column = $row['Type'];
            $column = str_replace ("int(10)", "int", $column);
            $column = str_replace ("int(11)", "int", $column);
            $column = str_replace ("bigint(20)", "bigint", $column);
            if ($row['Extra'] == "auto_increment") $column .= " AUTO_INCREMENT";
            if ($row['Key'] == "PRI") $column .= " PRIMARY KEY";
            $column = strtoupper ($column);

            $db_tabs[$i][$row['Field']] = $column;
        }
        dbfree ($result);
    }

    // DEBUG: Сдампить актуальные таблицы БД
    //print_r ($db_tabs);

    // Вывести таблицы игры из install, сравнивая их формат с форматом, полученным из БД

    $text_out .= "<h2>".loca("ADM_DB_INSTALL_VS_DB")."</h2>";

    $res = "";
    foreach ($tabs as $i=>$cols) {
        
        if (key_exists($i, $db_tabs)) {
            $res .= DiffTab ($i, $tabs[$i], $db_tabs[$i]);
        }
        else {
            $res .= "<font color=red>".va(loca("ADM_DB_DB_TABLE_MISSING"), $i)."</font><br/>";
        }
    }
    if ($res == "") {
        $text_out .= "<font color=green>".loca("ADM_DB_SAME")."</font><br/>";
    }
    else $text_out .= $res;

    // Вывести таблицы из БД, сравнивая их формат с таблицами из install

    $text_out .= "<h2>".loca("ADM_DB_DB_VS_INSTALL")."</h2>";

    $res = "";
    foreach ($db_tabs as $i=>$cols) {
        
        if (key_exists($i, $tabs)) {
            $res .= DiffTab ($i, $db_tabs[$i], $tabs[$i]);
        }
        else {
            $res .= "<font color=red>".va(loca("ADM_DB_INSTALL_TABLE_MISSING"), $i)."</font><br/>";
        }
    }
    if ($res == "") {
        $text_out .= "<font color=green>".loca("ADM_DB_SAME")."</font><br/>";
    }
    else $text_out .= $res;

?>

<?=AdminPanel();?>

<?=$text_out;?>

<?php
}
?>