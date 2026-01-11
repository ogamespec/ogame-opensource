<?php

/** @var string $DefaultLanguage */
/** @var array $Languages */

// Alliance Info.

// TODO: HTML may differ slightly after adding the universal router (#171); we need to check how critical this is and compare it with saved copies of the original pages.

// Attempt to get a session from the referrer.
//echo $_SERVER['HTTP_REFERER'];

$allyid = intval($_GET['allyid']);
$ally = LoadAlly ($allyid);

if ($ally) {
    $members = CountAllyMembers ( $ally['ally_id'] );
}
else {
    $members = '-';
}

?>

<table width=519>
<tr><td class=c colspan=2><?=loca("AINFO_INFO");?></td></tr><?php
    if ($ally && $ally['imglogo'] !== "") 
    {
        echo "<tr><th colspan=2><img src=\"/game/img/preload.gif\" class=\"reloadimage\" title=\"pic.php?url=".$ally['imglogo']."\"></td></tr>\n";
    }
?><tr><th><?=loca("AINFO_TAG");?></th><th><?=$ally['tag'];?><?php
    if ( $now < $ally['tag_until'] ) echo " (".loca("AINFO_PREV")." ".$ally['old_tag'].")";
?></th></tr>
<tr><th><?=loca("AINFO_NAME");?></th><th><?=$ally['name'];?><?php
    if ( $now < $ally['name_until'] ) echo " (".loca("AINFO_PREV")." ".$ally['old_name'].")";
?></th></tr>
<tr><th><?=loca("AINFO_MEMBERS");?></th><th><?=$members;?></th></tr>
<tr><th colspan=2 height=100><?=bb($ally['exttext']);?></th></tr>
<tr><th><?=loca("AINFO_HOMEPAGE");?></th><th>
<a href="redir.php?url=<?=$ally['homepage'];?>" target="_blank"><?=$ally['homepage'];?></a></th></tr>
</table>
