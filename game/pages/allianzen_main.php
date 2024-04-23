<?php

// Home Page.

function AllyPage_Home ()
{
    global $GlobalUser;
    global $session;
    global $ally;

    $now = time ();
    $members = CountAllyMembers ( $ally['ally_id'] );
    $rank = LoadRank ( $GlobalUser['ally_id'], $GlobalUser['allyrank'] );

    $result = EnumApplications ( $ally['ally_id'] );
    $apps = dbrows ($result);

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<?php
    if ( $ally['imglogo'] !== "" ) 
    {
?>
<tr><th colspan=2><img src="/game/img/preload.gif" class="reloadimage" title="pic.php?url=<?=$ally['imglogo'];?>"></td></tr>
<?php
    }
?>
<table width=519>
<tr><td class=c colspan=2><?=loca("ALLY_MAIN_HEAD");?></td></tr>
<tr><th><?=loca("ALLY_MAIN_TAG");?></th><th><?=$ally['tag'];?>
<?php
    if ( $now < $ally['tag_until'] ) echo " (".va(loca("ALLY_MAIN_PREV"), $ally['old_tag']).")";
?>
</th></tr>
<tr><th><?=loca("ALLY_MAIN_NAME");?></th><th><?=$ally['name'];?>
<?php
    if ( $now < $ally['name_until'] ) echo " (".va(loca("ALLY_MAIN_PREV"), $ally['old_name']).")";
?>
</th></tr>
<tr><th><?=loca("ALLY_MAIN_MEMBERS");?></th><th><?=$members;?>
<?php
    if ( $rank['rights'] & ARANK_R_MEMBERS ) echo " (<a href=\"index.php?page=allianzen&session=$session&a=4\">".loca("ALLY_MAIN_MEMBERS_LINK")."</a>)";
?>
</th></tr>
<tr><th><?=loca("ALLY_MAIN_RANK");?></th><th><?=$rank['name'];?>
<?php
    if ( $rank['rights'] & ARANK_W_MEMBERS ) echo " (<a href=\"index.php?page=allianzen&session=$session&a=5\">".loca("ALLY_MAIN_SETTINGS_LINK")."</a>)";
?>
</th></tr>
<?php
    if ( $apps > 0 )
    {
?>
<tr><th><?=loca("ALLY_MAIN_APPS");?></th><th><a href="index.php?page=bewerbungen&session=<?=$session;?>"><?=va(loca("ALLY_MAIN_APP_COUNT"), $apps);?></a></th></tr>
<?php
    }
?>
<?php
    if ( $rank['rights'] & ARANK_CIRCULAR )
    {
?>
<tr><th><?=loca("ALLY_MAIN_CIRCULAR");?></th><th><a href="index.php?page=allianzen&session=<?=$session;?>&a=17"><?=loca("ALLY_MAIN_CIRCULAR_LINK");?></a></th></tr>
<?php
    }
?>
<tr><th colspan=2 height=100><?=bb($ally['exttext']);?></th></tr>
<tr><th><?=loca("ALLY_MAIN_HOMEPAGE");?></th><th><a href="redir.php?url=<?=$ally['homepage'];?>" target="_blank"><?=$ally['homepage'];?></a></th></tr>
<tr><td class=c colspan=2><?=loca("ALLY_MAIN_INTTEXT");?></th></tr><tr><th colspan=2 height=100><?=bb($ally['inttext']);?></th></tr>
</table><br>
<?php
    if ( $GlobalUser['allyrank'] != 0 )    // Do not show the Founder the dialog of leaving the alliance.
    {
?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>&a=3" method=POST>
<tr><td class=c colspan=2><?=loca("ALLY_MAIN_LEAVE");?></td></tr><tr><th colspan=2><input type=submit value="<?=loca("ALLY_MAIN_LEAVE_SUBMIT");?>"></th></tr></table></form>
<?php
    }
}

?>