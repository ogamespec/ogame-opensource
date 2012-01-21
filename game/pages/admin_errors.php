<?php

// ========================================================================================
// Ошибки.

function Admin_Errors ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    if ( method () === "POST" )
    {
        $query = "SELECT * FROM ".$db_prefix."errors ORDER BY date DESC LIMIT 50";
        $result = dbquery ($query);
        $rows = dbrows ($result);
        while ($rows--)
        {
            $msg = dbarray ( $result );
            if ( $_POST["delmes".$msg['error_id']] === "on" || $_POST['deletemessages'] === "deleteall" )
            {
                $query = "DELETE FROM ".$db_prefix."errors WHERE error_id = " . $msg['error_id'];
                dbquery ($query);
            }
        }
    }

    $query = "SELECT * FROM ".$db_prefix."errors ORDER BY date DESC LIMIT 50";
    $result = dbquery ($query);

?>

<?=AdminPanel();?>

<table class='header'><tr class='header'><td><table width="519">
<form action="index.php?page=admin&session=<?=$session;?>&mode=Errors" method="POST">
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
<tr><th colspan="4">
<select name="deletemessages">
<option value="deletemarked">Удалить выделенные сообщения</option> 
<option value="deleteall">Удалить все сообщения</option> 
</select><input type="submit" value="ok" /></th></tr>
<tr><td colspan="4"><center>     </center></td></tr>
</form>
</table>

<?php
}

?>