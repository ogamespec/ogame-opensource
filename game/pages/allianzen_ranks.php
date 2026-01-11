<?php

// User Rank Management.

// Allowed characters in the rank name: [a-zA-Z0-9_-. ] (+space).

// Each alliance has 2 predetermined ranks that you cannot do anything with: Founder (all rights) and Newbie (no rights)

function PageAlly_Ranks () : void
{
    global $GlobalUser;
    global $session;
    global $ally;
    global $PageError;

    $myrank = LoadRank ( $ally['ally_id'], $GlobalUser['allyrank'] );
    if ( ! ($myrank['rights'] & ARANK_W_MEMBERS) )
    {
        $PageError = "<center>\n".loca("ALLY_NO_WAY")."<br></center>";
        return;
    }

    if ( method() === "POST" && $_GET['a'] == 15 ) 
    {
        if ( key_exists ('newrangname', $_POST) )       // create a rank
        {
            if ( !preg_match ("/^[a-zA-Z0-9\.\_\- ]+$/", $_POST['newrangname'] ) ) $PageError = "<center>\n".loca("ALLY_RANK_ERROR_SPECIAL_CHARS")."<br></center>";
            else AddRank ( $ally['ally_id'], $_POST['newrangname'] );
        }
        else                                                              // change ranks
        {
            $result = EnumRanks ( $ally['ally_id'] );
            $rows = dbrows ($result);
            while ($rows--)
            {
                $rank = dbarray ($result);
                if ( $rank['rank_id'] == 0 || $rank['rank_id'] == 1 ) continue;    // We're not changing the Founder or the Newbie ranks.
                $mask = $rank['rights'];
                for ($i=0; $i<9; $i++)
                {
                    $mask_name = "u".$rank['rank_id']."r$i";
                    if ( key_exists($mask_name, $_POST) && $_POST[$mask_name] === "on" ) $mask |= (1 << $i);
                    else $mask &= ~(1 << $i);
                }
                SetRank ( $ally['ally_id'], $rank['rank_id'], $mask );
            }
        }
    }

    if ( method () === "GET" && $_GET['a'] == 15 )    // delete rank
    {
        $rank_id = intval($_GET['d']);
        if ( ! ($rank_id == 0 || $rank_id == 1)  )        // Founder and Newbie ranks will not be deleted.
        {
            RemoveRank ( $ally['ally_id'], $rank_id );
        }
    }

?>
<script src="js/cntchar.js" type="text/javascript"></script><script src="js/win.js" type="text/javascript"></script><br />
<a href="index.php?page=allianzen&session=<?=$session;?>&a=5"><?=loca("ALLY_BACK");?></a>
<table width="519">
 <tr>
  <td class="c" colspan="11"><?=loca("ALLY_RANK_MASK");?></td>
 </tr>
 <form action="index.php?page=allianzen&session=<?=$session;?>&a=15" method="POST">
 <tr>
  <th></th>
  <th><?=loca("ALLY_RANK_NAME");?></th>
  <th>
   <img src=img/r1.png>
  </th>
  <th>
   <img src=img/r2.png>
  </th>
  <th>
   <img src=img/r3.png>
  </th>
  <th>
   <img src=img/r4.png>
  </th>
  <th>
   <img src=img/r5.png>
  </th>
  <th>
   <img src=img/r6.png>
  </th>
  <th>
   <img src=img/r7.png>
  </th>
  <th>
   <img src=img/r8.png>
  </th>
  <th>
   <img src=img/r9.png>
  </th>
 </tr>
<?php

    $result = EnumRanks ( $ally['ally_id'] );
    $rows = dbrows ($result);
    while ($rows--)
    {
        $rank = dbarray ($result);
        if ( $rank['rank_id'] == 0 || $rank['rank_id'] == 1 ) continue;    // We don't show the Founder and the Newbie ranks.
        echo " <tr>\n";
        echo "  <th><a href=\"index.php?page=allianzen&session=$session&a=15&d=".$rank['rank_id']."\"><img src=\"".UserSkin()."pic/abort.gif\" alt=\"".loca("ALLY_RANK_DELETE")."\" border=\"0\"></a></th>\n";
        echo "  <th>&nbsp;".$rank['name']."&nbsp;</th>\n";
        for ($r=0; $r<9; $r++) {
            if ($rank['rights'] & (1 << $r))  echo "<th><input type=checkbox name=\"u".$rank['rank_id']."r$r\" checked></th>";
            else echo "<th><input type=checkbox name=\"u".$rank['rank_id']."r$r\"></th>";
        }
        echo " </tr>\n";
    }
?>
 <tr>
  <th colspan="11"><input type="submit" value="<?=loca("ALLY_RANK_SAVE");?>"></th>
 </tr>
</form>
</table>
<br /><form action="index.php?page=allianzen&session=<?=$session;?>&a=15" method=POST>
<table width=519>
<tr><td class=c colspan=2><?=loca("ALLY_RANK_ADD_TEXT");?></td></tr>
<tr><th><?=loca("ALLY_RANK_NAME");?></th><th><input type=text name="newrangname" size=20 maxlength=30></th></tr>
<tr><th colspan=2><input type=submit value="<?=loca("ALLY_RANK_ADD_SUBMIT");?>"></th></tr>
</form></table>

<br/><form action="index.php?page=allianzen&session=<?=$session;?>&a=15" method=POST>
<table width=519>
<tr><td class=c colspan=2><?=loca("ALLY_RANK_INFO");?></td></tr>
<tr><th><img src=img/r1.png></th><th><?=loca("ALLY_RANK_1");?></th></tr>
<tr><th><img src=img/r2.png></th><th><?=loca("ALLY_RANK_2");?></th></tr>
<tr><th><img src=img/r3.png></th><th><?=loca("ALLY_RANK_3");?></th></tr>
<tr><th><img src=img/r4.png></th><th><?=loca("ALLY_RANK_4");?></th></tr>
<tr><th><img src=img/r5.png></th><th><?=loca("ALLY_RANK_5");?></th></tr>
<tr><th><img src=img/r6.png></th><th><?=loca("ALLY_RANK_6");?></th></tr>
<tr><th><img src=img/r7.png></th><th><?=loca("ALLY_RANK_7");?></th></tr>
<tr><th><img src=img/r8.png></th><th><?=loca("ALLY_RANK_8");?></th></tr>
<tr><th><img src=img/r9.png></th><th><?=loca("ALLY_RANK_9");?></th></tr>
</form></table>
<?php
}

?>