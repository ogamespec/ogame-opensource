<?php

// User list and user management.

function PageAlly_MemberList ()
{
    global $session;
    global $ally;
    global $GlobalUser;
    global $AllianzenError;

    $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
    if ( ! ($myrank['rights'] & ARANK_R_MEMBERS) )
    {
        $AllianzenError = "<center>\n".loca("ALLY_MEMBERS_DENIED")."<br></center>";
        return;
    }

    $members = CountAllyMembers ( $ally['ally_id'] );
    $now = time ();

    $use_sort = key_exists ('sort1', $_GET) && key_exists ('sort2', $_GET);
    $sort1 = key_exists ('sort1', $_GET) ? intval ($_GET['sort1']) : 0;
    $sort2 = key_exists ('sort2', $_GET) ? intval ($_GET['sort2']) : 1;
    // To sort in reverse order, the ^1 (XOR) operation is used when outputting the table (i.e. from a value of 1 make 0 and vice versa).

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script>
<table width=519>
<tr><td class='c' colspan='10'><?=va(loca("ALLY_MEMBERS_COUNT"), $members);?></td></tr>
<tr>
    <th><?=loca("ALLY_MEMBERS_ID");?></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=1&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_NAME");?></a></th>
    <th> </th><th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=2&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_STATUS");?></a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=3&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_POINTS");?></a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=0&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_COORD");?></a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=4&sort1=4&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_JOINDATE");?></a></th>
<?php
    if ( $myrank['rights'] & ARANK_ONLINE ) echo "    <th><a href=\"index.php?page=allianzen&session=$session&a=4&sort1=5&sort2=".($sort2^1)."\">Online</a></th></tr>\n";
    if ( ($myrank['rights'] & ARANK_ONLINE) == 0 && $sort1 == 5 ) $sort1 = 0;
?>
<?php
    $result = EnumerateAlly ($ally['ally_id'], $sort1, $sort2, $use_sort);
    for ($i=0; $i<$members; $i++)
    {
        $user = dbarray ($result);
        $rank = LoadRank ( $user['ally_id'], $user['allyrank'] );
        $hplanet = GetPlanet ($user['hplanetid']);
        echo "<tr>\n";
        echo "    <th>".($i+1)."</th>\n";
        echo "    <th>".$user['oname']."</th>\n";
        if ( $GlobalUser['player_id'] != $user['player_id'] ) {
            echo "    <th><a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\"><img src=\"".UserSkin()."img/m.gif\" border=0 alt=\"".loca("ALLY_MEMBERS_WRITE_MESSAGE")."\"></a></th>\n";
        }
        else echo "    <th></th>\n";
        echo "    <th>".$rank['name']."</th>\n";
        echo "    <th>".nicenum($user['score1'] / 1000)."</th>\n";
        echo "    <th><a href=\"index.php?page=galaxy&galaxy=".$hplanet['g']."&system=".$hplanet['s']."&position=".$hplanet['p']."&session=$session\" >[".$hplanet['g'].":".$hplanet['s'].":".$hplanet['p']."]</a></th>\n";
        echo "    <th>".date ("Y-m-d H:i:s", $user['joindate'])."</th>\n";
        if ( $myrank['rights'] & ARANK_ONLINE )
        {
            $min = floor ( ($now - $user['lastclick']) / 60 );
            if ( $min < 15 ) echo "    <th><font color=lime>".loca("ALLY_MEMBERS_YES")."</font></th>";
            else if ( $min < 60 ) echo "    <th><font color=yellow>$min min</font></th>";
            else echo "    <th><font color=red>".loca("ALLY_MEMBERS_NO")."</font></th>";
        }
        echo "</tr>\n";
    }
?>
</table>
<?php
}

function PageAlly_MemberSettings ()
{
    global $db_prefix;
    global $session;
    global $ally;
    global $GlobalUser;
    global $AllianzenError;

    $selected_user = 0;
    if ( key_exists ('u', $_GET) ) $selected_user = intval($_GET['u']);

    if ( method() === "GET" && $_GET['a'] == 13 && $selected_user)        // Kick member
    {
        $leaver = LoadUser ($selected_user);

        $query = "UPDATE ".$db_prefix."users SET ally_id = 0 WHERE player_id = $selected_user";
        dbquery ($query);

        // Send out messages to alliance members about the exclusion of a member
        $result = EnumerateAlly ($ally['ally_id']);
        $rows = dbrows ($result);
        while ($rows--)
        {
            $user = dbarray ($result);
            loca_add ("ally", $user['lang']);
            SendMessage ( $user['player_id'], 
                va(loca_lang("ALLY_MSG_FROM", $user['lang']), $ally['tag']), 
                loca_lang("ALLY_MSG_COMMON", $user['lang']), 
                va(loca_lang("ALLY_MSG_KICK_TEXT", $user['lang']), $leaver['oname']), MTYP_ALLY);
        }

        // A message to the player about the exclusion.
        loca_add ("ally", $leaver['lang']);
        SendMessage ( $leaver['player_id'], 
                va(loca_lang("ALLY_MSG_FROM", $leaver['lang']), $ally['tag']), 
                va(loca_lang("ALLY_MSG_KICK_SUBJ", $leaver['lang']), $ally['tag']), 
                va(loca_lang("ALLY_MSG_YOU_KICKED", $leaver['lang']), $GlobalUser['oname'], $ally['tag']), MTYP_ALLY);
    }

    if ( method() === "POST" && $_GET['a'] == 16 && $selected_user)        // Assign a rank to a player
    {
        $newrank = intval($_POST['newrang']);
        $query = "UPDATE ".$db_prefix."users SET allyrank = $newrank WHERE player_id = $selected_user";
        dbquery ($query);
    }

    $now = time ();
    $members = CountAllyMembers ( $ally['ally_id'] );

    $use_sort = key_exists ('sort1', $_GET) && key_exists ('sort2', $_GET);
    $sort1 = key_exists ('sort1', $_GET) ? intval ($_GET['sort1']) : 0;
    $sort2 = key_exists ('sort2', $_GET) ? intval ($_GET['sort2']) : 1;
    // To sort in reverse order, the ^1 (XOR) operation is used when outputting the table (i.e. from a value of 1 make 0 and vice versa).

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script><br>
<a href="index.php?page=allianzen&session=<?=$session;?>&a=5"><?=loca("ALLY_BACK");?></a>
<table width=519>
<tr><td class='c' colspan='10'><?=va(loca("ALLY_MEMBERS_COUNT"), $members);?></td></tr>
<tr>
    <th><?=loca("ALLY_MEMBERS_ID");?></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=1&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_NAME");?></a></th>
    <th> </th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=2&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_STATUS");?></a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=3&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_POINTS");?></a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=0&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_COORD");?></a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=4&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_JOINDATE");?></a></th>
    <th><a href="index.php?page=allianzen&session=<?=$session;?>&a=7&sort1=5&sort2=<?=$sort2^1;?>"><?=loca("ALLY_MEMBERS_INACTIVE");?></a></th>
    <th><?=loca("ALLY_MEMBERS_ACTION");?></th></tr>

<?php
    $result = EnumerateAlly ($ally['ally_id'], $sort1, $sort2, $use_sort );
    for ($i=0; $i<$members; $i++)
    {
        $user = dbarray ($result);
        $rank = LoadRank ( $user['ally_id'], $user['allyrank'] );
        $hplanet = GetPlanet ($user['hplanetid']);
        $days = floor ( ( $now - $user['lastclick'] ) / (60 * 60 * 24) );
        echo "<tr>";
        echo "<th>".($i+1)."</th>";
        echo "<th>".$user['oname']."</th>";
        if ( $GlobalUser['player_id'] != $user['player_id'] ) {
            echo "<th><a href=\"index.php?page=writemessages&session=$session&messageziel=".$user['player_id']."\"><img src=\"".UserSkin()."img/m.gif\" border=0 alt=\"".loca("ALLY_MEMBERS_WRITE_MESSAGE")."\"></a></th>";
        }
        else echo "<th></th>";
        echo "<th>".$rank['name']."</th>";
        echo "<th>".nicenum($user['score1'] / 1000)."</th>";
        echo "<th><a href=\"index.php?page=galaxy&galaxy=".$hplanet['g']."&system=".$hplanet['s']."&position=".$hplanet['p']."&session=$session\" >[".$hplanet['g'].":".$hplanet['s'].":".$hplanet['p']."]</a></th>";
        echo "<th>".date ("Y-m-d H:i:s", $user['joindate'])."</th>";
        echo "<th>".$days."d</th>";
        if ( $user['allyrank'] > 0 ) {
            echo "<th>";
            echo "<a onmouseover='return overlib(\"<font color=white>".loca("ALLY_MEMBERS_KICK")."</font>\", WIDTH, 100);' onmouseout='return nd();' alt='".loca("ALLY_MEMBERS_KICK")."' href='javascript:if(confirm(\"".va(loca("ALLY_MEMBERS_KICK_CONFIRM"), $user['oname'])."\"))document.location=\"index.php?page=allianzen&session=$session&a=13&u=".$user['player_id']."\"';>";
            echo "<img src='".UserSkin()."pic/abort.gif' alt='".loca("ALLY_MEMBERS_KICK")."' border='0' ></a>";
            echo "<a onmouseover=\"return overlib('<font color=white>".loca("ALLY_MEMBERS_SET_RANK")."</font>', WIDTH, 100);\" onmouseout='return nd();' alt='".loca("ALLY_MEMBERS_SET_RANK")."' href=\"index.php?page=allianzen&session=$session&a=7&u=".$user['player_id']."\">";
            echo "<img src=\"".UserSkin()."pic/key.gif\" alt='".loca("ALLY_MEMBERS_SET_RANK")."' border=0></a>&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "</th>";
            echo "</tr>\n";

            if ( $user['player_id'] == $selected_user )        // Output a form for assigning a rank.
            {
                $rank_result = EnumRanks ( $ally['ally_id'] );
                $rows = dbrows ($rank_result);
                echo "<form action=\"index.php?page=allianzen&session=$session&a=16&u=$selected_user\" method=POST><tr><th colspan=3>".va(loca("ALLY_MEMBERS_RANK_TO"), $user['oname'])."</th><th><select name=\"newrang\">";
                while ($rows--)
                {
                    $user_rank = dbarray ( $rank_result );
                    if ($user_rank['rank_id'] == 0) continue;
                    echo "<option value=\"".$user_rank['rank_id']."\"";
                    if ($user_rank['rank_id'] == $user['allyrank'] ) echo " SELECTED";
                    echo ">".$user_rank['name']."\n";
                }
                echo "</th><th colspan=5><input type=submit value=\"".loca("ALLY_MEMBERS_SAVE")."\"></th></tr></form>\n";
            }
        }
        else echo "<th>&nbsp;</th></tr>\n";
    }
?>

</table>
<?php
}

?>