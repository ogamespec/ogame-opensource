<?php

// Admin Area: Broadcast message to users.

function Admin_Broadcast () : void
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $write_error = "";

    // POST request processing.
    if ( method () === "POST" )
    {
        $cat = intval($_POST['cat']);
        $subj = $_POST['subj'];
        if ( $subj === "" ) $write_error = "<center><font color=#FF0000>".loca("ADM_BCAST_ERROR_SUBJ")."</font><br/></center>\n";
        $text = $_POST['text'];
        if ( $text === "" ) $write_error = "<center><font color=#FF0000>".loca("ADM_BCAST_ERROR_TEXT")."</font><br/></center>\n";

        if ( $write_error === "" )
        {
            $ownhome = LoadPlanetById ( $GlobalUser['hplanetid'] );

            $from = $GlobalUser['oname'] . " <a href=\"index.php?page=galaxy&galaxy=".$ownhome['g']."&system=".$ownhome['s']."&position=".$ownhome['p']."&session={PUBLIC_SESSION}\">[".$ownhome['g'].":".$ownhome['s'].":".$ownhome['p']."]</a>\n";
            $subj = $subj . " <a href=\"index.php?page=writemessages&session={PUBLIC_SESSION}&messageziel=".$GlobalUser['player_id']."&re=1&betreff=Re:".$subj."\">\n"
                        . "</a>\n";

            $usernum = BroadcastMessage ($cat, $from, $subj, $text);

            if ($usernum > 0) $write_error = "<center><font color=#00FF00>".va(loca("ADM_BCAST_SUCCESS"), $usernum)."</font><br/></center>\n";
            else $write_error = "<center><font color=#00FF00>".loca("ADM_BCAST_ERROR_USERS")."</font><br/></center>\n";
        }
    }

?>

<?php AdminPanel();?>

<?=$write_error;?>

<table>
<form action="index.php?page=admin&session=<?=$session;?>&mode=Broadcast" method="POST">

<tr><td>
<?=loca("ADM_BCAST_WHO");?> <select name="cat">
<option value="0"><?=loca("ADM_BCAST_0");?></option>
<option value="1"><?=va(loca("ADM_BCAST_1"), nicenum(USER_NOOB_LIMIT));?></option>
<option value="2"><?=loca("ADM_BCAST_2");?></option>
<option value="3"><?=loca("ADM_BCAST_3");?></option>
</select>
</td></tr>

<tr><td>
<?=loca("ADM_BCAST_SUBJ");?> <input name="subj" size=80>
</td></tr>

<tr><td>
<textarea cols='100' rows='20' name='text'></textarea>
</td></tr>

<tr><td>
<center><input type="submit" value="<?=loca("ADM_BCAST_SUBMIT");?>"></center>
</td></tr>

</form>
</table>

<?php
}

?>