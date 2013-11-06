<div id="rightmenu" class="rightmenu_big">
    <div id="title">Установка Мастер-базы</div>
    <div id="content">
        <div id="contentscroll" align="justify">

<?php


require_once "db.php";

$InstallError = "";

// Структура таблиц.
// -------------------------------------------------------------------------------------------------------------------------

$tab_unis = array (          // Вселенные
    'id' => 'INT AUTO_INCREMENT PRIMARY KEY', 'num' => 'INT', 'dbhost' => 'TEXT', 'dbuser' => 'TEXT', 'dbpass' => 'TEXT', 'dbname' => 'TEXT', 'uniurl' => 'TEXT',
);

$tab_coupons = array (       // Купоны
    'id' => 'INT AUTO_INCREMENT PRIMARY KEY', 'code' => 'TEXT', 'amount' => 'INT UNSIGNED', 'used' => 'INT', 'user_uni' => 'INT', 'user_id' => 'INT', 'user_name' => 'TEXT', 
);

$tabs = array (
    'unis' => &$tab_unis,
    'coupons' => &$tab_coupons,
);

if ( $_SERVER['REQUEST_METHOD'] === "POST" ) {

    // Удалить все таблицы и создать новые пустые.
    dbconnect ($_POST["mdb_host"], $_POST["mdb_user"], $_POST["mdb_pass"], $_POST["mdb_name"]);
    dbquery ("SET NAMES 'utf8';");
    dbquery ("SET CHARACTER SET 'utf8';");
    dbquery ("SET SESSION collation_connection = 'utf8_general_ci';");

    foreach ( $tabs as $tabname => $tab )
    {
        $opt = " (";
        $first = true;
        foreach ( $tab as $row => $type ) 
        {
            if ( !$first ) $opt .= ", ";
            if ( $first ) $first = false;
            $opt .= $row . " " . $type;
        }
        $opt .= ")";

        $query = 'DROP TABLE IF EXISTS '.$tabname;
        dbquery ($query, TRUE);
        $query = 'CREATE TABLE '.$tabname.$opt." CHARACTER SET utf8 COLLATE utf8_general_ci";
        dbquery ($query, TRUE);
    }

    // Сохранить файл конфигурации.
    $file = fopen ("config.php", "wb");
    if ($file == FALSE) $InstallError = loca('INSTALL_ERROR1');
    else
    {
        fwrite ($file, "<?php\r\n");
        fwrite ($file, "// DO NOT MODIFY!\r\n");
        fwrite ($file, "$"."mdb_host=\"". $_POST["mdb_host"] ."\";\r\n");
        fwrite ($file, "$"."mdb_user=\"". $_POST["mdb_user"] ."\";\r\n");
        fwrite ($file, "$"."mdb_pass=\"". $_POST["mdb_pass"] ."\";\r\n");
        fwrite ($file, "$"."mdb_name=\"". $_POST["mdb_name"] ."\";\r\n");
        fwrite ($file, "?>");
        fclose ($file);
        $InstallError = "<font color=lime>".loca('INSTALL_DONE')."</font>";
    }

}

?>

<?php echo $InstallError;?> <br><br>

<?php echo loca('INSTALL_MDB_TIP');?>
<br>
<br>

<form action='install.php' method='POST'>

<table>
<tr><td colspan=2 class='c'><a title='<?php echo loca('INSTALL_MDB_TIP');?>'><?php echo loca('INSTALL_MDB');?></a></td></tr>
<tr><td><?php echo loca('INSTALL_MDB_HOST');?></td><td><input type=text value='localhost' class='text' name='mdb_host'></td></tr>
<tr><td><?php echo loca('INSTALL_MDB_USER');?></td><td><input type=text class='text' name='mdb_user'></td></tr>
<tr><td><?php echo loca('INSTALL_MDB_PASS');?></td><td><input type=password class='text'  name='mdb_pass'></td></tr>
<tr><td><?php echo loca('INSTALL_MDB_NAME');?></td><td><input type=text class='text' name='mdb_name'></td></tr>
<tr><td colspan=2><center><input type=submit value='<?php echo loca('INSTALL_INSTALL');?>' class='button'></center></td></tr>
</table>

</form>

</div>
    </div>
</div>
