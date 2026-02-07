<?php

// Admin Area: user complains about abusive messages. Although Legor commands to dominate the universe, it must be done with respect.

class Admin_Reports extends Page {

    public function controller () : bool {
        return true;
    }

    public function view () : void {
    }
}

function Admin_Reports () : void
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    loca_add ( "messages", $GlobalUser['lang'] );

    if ( method () === "POST" && $GlobalUser['admin'] >= 1 )
    {
        $query = "SELECT * FROM ".$db_prefix."reports ORDER BY date DESC LIMIT 50";
        $result = dbquery ($query);
        $rows = dbrows ($result);
        while ($rows--)
        {
            $msg = dbarray ( $result );
            if ( $_POST["delmes".$msg['id']] === "on" || $_POST['deletemessages'] === "deleteall" )
            {
                $query = "DELETE FROM ".$db_prefix."reports WHERE id = " . $msg['id'];
                dbquery ($query);
            }
        }
    }

    $query = "SELECT * FROM ".$db_prefix."reports ORDER BY date DESC LIMIT 50";
    $result = dbquery ($query);

?>

<?php AdminPanel();?>

<table class='header'><tr class='header'><td><table width="519">
<form action="index.php?page=admin&session=<?=$session;?>&mode=Reports" method="POST">
<tr><td colspan="5" class="c"><?=loca("ADM_MSG_TITLE");?></td></tr>
<tr><th><?=loca("ADM_MSG_ACTION");?></th><th><?=loca("ADM_MSG_DATE");?></th><th><?=loca("ADM_MSG_FROM");?></th><th><?=loca("WRITE_MSG_USER");?></th><th><?=loca("MSG_SUBJ");?></th></tr>

<?php
    $rows = dbrows ($result);
    while ($rows--) 
    {
        $msg = dbarray ( $result );
        $user = LoadUser ($msg['owner_id']);
        $to = "<a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$msg['owner_id']."\">" . $user['oname'] . "</a> ";
        $msg['text'] = str_replace ( "{PUBLIC_SESSION}", $session, $msg['text']);
        $msg['subj'] = str_replace ( "{PUBLIC_SESSION}", $session, $msg['subj']);
        $msg['msgfrom'] = str_replace ( "{PUBLIC_SESSION}", $session, $msg['msgfrom']);
        echo "<tr><th><input type=\"checkbox\" name=\"delmes".$msg['id']."\"/></th><th>".date ("m-d H:i:s", $msg['date'])."</th><th>".$msg['msgfrom']." </th><th>$to </th><th>".$msg['subj']." </th></tr>\n";
        echo "<tr><td class=\"b\"> </td><td class=\"b\" colspan=\"4\">".$msg['text']."</td></tr>\n";
    }
?>

<tr><td class="b"> </td><td class="b" colspan="4"></td></tr>
<tr><th colspan="5" style='padding:0px 105px;'></th></tr>
<tr><th colspan="5">
<select name="deletemessages">
<option value="deletemarked"><?=loca("MSG_DELETE_MARKED");?></option> 
<option value="deleteall"><?=loca("MSG_DELETE_ALL");?></option> 
</select><input type="submit" value="<?=loca("ADM_MSG_SUBMIT");?>" /></th></tr>
<tr><td colspan="5"><center>     </center></td></tr>
</form>
</table>

<?php
}

?>