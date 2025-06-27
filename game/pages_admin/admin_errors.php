<?php

// Admin Area: Errors.
// Errors occur when something very bad happens to the game during the execution of a request. In most cases, the error will result in the player being unloaded.

function Admin_Errors ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    loca_add ( "messages", $GlobalUser['lang'] );

    if ( method () === "POST" && $GlobalUser['admin'] >= 2 )
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
<tr><td colspan="4" class="c"><?=loca("ADM_MSG_TITLE");?></td></tr>
<tr><th><?=loca("ADM_MSG_ACTION");?></th><th><?=loca("ADM_MSG_DATE");?></th><th><?=loca("ADM_MSG_FROM");?></th><th><?=loca("ADM_MSG_BROWSER");?></th></tr>

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
<option value="deletemarked"><?=loca("MSG_DELETE_MARKED");?></option> 
<option value="deleteall"><?=loca("MSG_DELETE_ALL");?></option> 
</select><input type="submit" value="<?=loca("ADM_MSG_SUBMIT");?>" /></th></tr>
<tr><td colspan="4"><center>     </center></td></tr>
</form>
</table>

<?php
}

?>