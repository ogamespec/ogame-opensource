<?php

// ========================================================================================
// Отладочные сообщения.

function Admin_Debug ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $message_limit = 50;

    if ( key_exists ('filter', $_REQUEST) ) $filter = $_REQUEST['filter'];
    else $filter = "";

    if ( method () === "POST" && $filter === "" )
    {
        if ( $_POST['deletemessages'] === "deleteall" )
        {
            $query = "TRUNCATE TABLE ".$db_prefix."debug;";
            dbquery ($query);
        }
        else
        {
            $query = "SELECT * FROM ".$db_prefix."debug ORDER BY date DESC, error_id DESC LIMIT " . $message_limit;
            $result = dbquery ($query);
            $rows = dbrows ($result);
            while ($rows--)
            {
                $msg = dbarray ( $result );
                if ( key_exists ("delmes".$msg['error_id'], $_POST) || $_POST['deletemessages'] === "deleteshown" )
                {
                    $query = "DELETE FROM ".$db_prefix."debug WHERE error_id = " . $msg['error_id'];
                    dbquery ($query);
                }
            }
        }
    }

    if ( $filter === "" )
    {
        $query = "SELECT * FROM ".$db_prefix."debug ORDER BY date DESC, error_id DESC LIMIT " . $message_limit;
    }
    else
    {
        $query = "SELECT * FROM ".$db_prefix."debug WHERE text LIKE '%".$filter."%' ORDER BY date DESC, error_id DESC LIMIT " . $message_limit;
    }
    $result = dbquery ($query);

?>

<?=AdminPanel();?>

<table class='header'><tr class='header'><td><table width="519">
<form action="index.php?page=admin&session=<?=$session;?>&mode=Debug" method="POST">
<tr><td colspan="4" class="c">Сообщения</td></tr>
<tr><th>Действие</th><th>Дата</th><th>От</th><th>Браузер</th></tr>

<?php
    $rows = dbrows ($result);
    while ($rows--) 
    {
        $msg = dbarray ( $result );
        $user = LoadUser ($msg['owner_id']);
        $from = "<a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$msg['owner_id']."\">" . $user['oname'] . "</a> [" . $msg['ip'] . "]";
        $msg['text'] = str_replace ( "{PUBLIC_SESSION}", $session, $msg['text']);
        echo "<tr><th><input type=\"checkbox\" name=\"delmes".$msg['error_id']."\"/></th><th>".date ("m-d H:i:s", $msg['date'])."</th><th>$from </th><th>".$msg['agent']." </th></tr>\n";
        echo "<tr><td class=\"b\"> </td><td class=\"b\" colspan=\"3\">".$msg['text']."</td></tr>\n";
    }
?>

<tr><td class="b"> </td><td class="b" colspan="3"></td></tr>
<tr><th colspan="4" style='padding:0px 105px;'></th></tr>
<tr>
<th colspan="4">
<select name="deletemessages">
<option value="deletemarked">Удалить выделенные сообщения</option> 
<option value="deleteshown">Удалить показанные сообщения</option> 
<option value="deleteall">Удалить все сообщения</option> 
</select><input type="submit" value="ok" /></th></tr>
<tr><td colspan="4"><center>     </center></td></tr>
<tr><th colspan="4">Фильтр отладочных сообщений: <input type=text name="filter" />
<input type=submit value="Показать"></th></tr>
</form>
</table>

<?php
}

?>