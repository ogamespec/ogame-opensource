<?php

// Circular Message.

function AllyPage_CircularMessage ()
{
    global $db_prefix;
    global $GlobalUser;
    global $session;
    global $ally;
    global $AllianzenError;

    // Character limit.
    $MAXCHARS = 2000;

    if ( method () === "POST" && key_exists ('r', $_POST) )
    {
        $ally_id = $ally['ally_id'];
        $myrank = LoadRank ( $ally_id, $GlobalUser['allyrank'] );
        if ( ! ($myrank['rights'] & ARANK_CIRCULAR) )
        {
            $AllianzenError = "<center>\n".loca("ALLY_NO_WAY")."<br></center>";
            return;
        }
        $rank_id = intval($_POST['r']);
        if ( $rank_id == 0 ) $query = "SELECT * FROM ".$db_prefix."users WHERE ally_id = $ally_id";
        else $query = "SELECT * FROM ".$db_prefix."users WHERE ally_id = $ally_id AND allyrank = $rank_id";
        $result = dbquery ($query);
        $rows = dbrows ( $result );
        if ( $rows )
        {
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><td class=c><?=loca("ALLY_CIRC_USERLIST");?></td></tr>
<tr><th>
<?php

            $text = str_replace ( "\"", "&quot;", bb($_POST['text']) );
            $text = str_replace ( "'", "&rsquo;", $text );
            $text = str_replace ( "`", "&lsquo;", $text );

            while ($rows--)
            {
                $user = dbarray ($result);
                loca_add ("ally", $user['lang']);
                SendMessage ( $user['player_id'], 
                                       va ( loca_lang("ALLY_MSG_FROM", $user['lang']), $ally['tag'] ),
                                       va ( loca_lang("ALLY_MSG_CIRC_SUBJ", $user['lang']), $ally['tag'] ), 
                                       va ( loca_lang("ALLY_MSG_CIRC_TEXT", $user['lang']), $GlobalUser['oname'], $text ), MTYP_ALLY );
                echo $user['oname'] . "<br>\n";
            }
?>
</th></tr>
<tr><th><input type=submit value="<?=loca("ALLY_CIRC_SUBMIT");?>"></th></tr>
</table></center></form>
<?php
        }
        else
        {
?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=17" method=POST>
<tr><td class=c><?=loca("ALLY_CIRC_ERROR");?></td></tr>
<tr><th><?=loca("ALLY_CIRC_ERROR_TEXT");?></th></tr>
<tr><th><input type=submit value="<?=loca("ALLY_CIRC_BACK");?>"></th></tr>
</table></center></form>
<?php
        }
        return;
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=17&sendmail=1" method=POST>
<tr><td class=c colspan=2><?=loca("ALLY_CIRC_HEAD");?></td></tr>
<tr><th><?=loca("ALLY_CIRC_TO");?></th><th>
<select name=r>
    <option value=0><?=loca("ALLY_CIRC_ALL");?></option>
<?php
    $result = EnumRanks ( $ally['ally_id'] );
    $rows = dbrows ($result);
    while ($rows--)
    {
        $rank = dbarray ($result);
        if ( $rank['rank_id'] == 0 || $rank['rank_id'] == 1 ) continue;    // We don't show the founder or the rookie
        echo "    <option value=".$rank['rank_id'].">".va(loca("ALLY_CIRC_RANK"), $rank['name'])."</option>\n";
    }
?>
</select></th></tr>
<tr><th><?=va(loca("ALLY_CIRC_MESSAGE"), "<span id=\"cntChars\">0</span>", $MAXCHARS);?></th><th><textarea name=text cols=60 rows=10 onkeyup="javascript:cntchar(<?=loca($MAXCHARS);?>)"></textarea></th></tr>
<tr><th colspan=2><input type=submit value="<?=loca("ALLY_CIRC_SEND");?>"></th></tr></table></center></form>
<?php
}

?>