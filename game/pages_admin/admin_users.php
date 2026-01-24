<?php

// Admin Area: Users.

$big_fleet_points = 100000000;      // Mark large fleets with a special color.

$FleetMissionList = array (
    0 => array ( 1, 101, 2, 102, 3, 103, 4, 104, 5, 105, 205, 6, 106, 7, 107, 8, 108, 9, 109, 15, 115, 215, 20, 21, 121 ),
    1 => array ( 1, 101 ),
    2 => array ( 2, 102 ),
    3 => array ( 3, 103 ),
    4 => array ( 4, 104 ),
    5 => array ( 5, 105, 205 ),
    6 => array ( 6, 106 ), 
    7 => array ( 7, 107 ), 
    8 => array ( 8, 108 ), 
    9 => array ( 9, 109 ), 
    15 => array ( 15, 115, 215 ), 
    20 => array ( 20 ), 
    21 => array ( 21, 121 ), 
);

function LinkFleetsFrom ($user, $mission)
{
    global $session, $FleetMissionList;
    $result = FleetlogsFromPlayer ( $user['player_id'], $FleetMissionList[$mission] );
    if ( $result ) $rows = dbrows ($result);
    else $rows = 0;
    if ( $rows ) return "<a href=\"index.php?page=admin&session=".$session."&mode=Users&action=fleetlogs&player_id=".$user['player_id']."&mission=".$mission."&from=1\">".$rows."</a>";
    else return "0";
}

function LinkFleetsTo ($user, $mission)
{
    global $session, $FleetMissionList;
    $result = FleetlogsToPlayer ( $user['player_id'], $FleetMissionList[$mission] );
    if ( $result ) $rows = dbrows ($result);
    else $rows = 0;
    if ( $rows ) return "<a href=\"index.php?page=admin&session=".$session."&mode=Users&action=fleetlogs&player_id=".$user['player_id']."&mission=".$mission."&from=0\">".$rows."</a>";
    else return "0";
}

function IsChecked (array $user, string $option) : string
{
    if ( $user[$option] ) return "checked=checked";
    else return "";
}

function IsSelected (array $user, string $option, int $value) : string
{
    if ( $user[$option] == $value ) return "selected";
    else return "";
}

function Admin_Users () : void
{
    global $session;
    global $db_prefix;
    global $GlobalUser;
    global $FleetMissionList;
    global $big_fleet_points;
    global $resmap;
    global $fleetmap;

    $now = time();
    
    $unitab = LoadUniverse ();
    $speed = $unitab['speed'];

    // Processing a POST request.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 ) {
        
        if ( key_exists('player_id', $_GET) ) $player_id = intval ($_GET['player_id']);
        else $player_id = 0;
        
        if (key_exists('action', $_GET) && $player_id) $action = $_GET['action'];
        else $action = "";

        if ($action === "update")        // Update user data.
        {
            $query = "UPDATE ".$db_prefix."users SET ";

            foreach ( $resmap as $i=>$gid)
            {
                $query .= "`$gid` = ".intval ($_POST["r$gid"]).", ";
            }

            if ( key_exists('deaktjava', $_POST) && $_POST['deaktjava'] === "on" ) {
                    $query .= "disable = 1, disable_until = " . ($now+7*24*60*60).", ";
            }
            else {
                $query .= "disable = 0, ";
            }
            if ( key_exists('vacation', $_POST) && $_POST['vacation'] === "on" ) {
                $query .= "vacation = 1, vacation_until = " . ($now+((2*24*60*60)/ $speed)) .", ";
            }
            else $query .= "vacation = 0, ";
            if ( key_exists('banned', $_POST) && $_POST['banned'] !== "on" ) $query .= "banned = 0, ";
            if ( key_exists('noattack', $_POST) && $_POST['noattack'] !== "on" ) $query .= "noattack = 0, ";

            $query .= "pemail = '".$_POST['pemail']."', ";
            $query .= "email = '".$_POST['email']."', ";
            $query .= "admin = ".$_POST['admin'].", ";
            $query .= "validated = ".(key_exists('validated', $_POST) && $_POST['validated']==="on"?1:0).", ";
            $query .= "sniff = ".(key_exists('sniff', $_POST) && $_POST['sniff']==="on"?1:0).", ";
            $query .= "debug = ".(key_exists('debug', $_POST) && $_POST['debug']==="on"?1:0).", ";

            $query .= "dm = ".intval ($_POST['dm']).", ";
            $query .= "dmfree = ".intval ($_POST['dmfree']).", ";

            $query .= "sortby = ".intval ($_POST['settings_sort']).", ";
            $query .= "sortorder = ".intval ($_POST['settings_order']).", ";
            $query .= "skin = '".$_POST['dpath']."', ";
            $query .= "useskin = ".($_POST['design']==="on"?1:0).", ";
            $query .= "deact_ip = ".(key_exists('deact_ip', $_POST) && $_POST['deact_ip']==="on"?1:0).", ";
            $query .= "maxspy = ".intval ($_POST['spio_anz']).", ";
            $query .= "maxfleetmsg = ".intval ($_POST['settings_fleetactions'])." ";

            $query .= " WHERE player_id=$player_id;";
            dbquery ($query);

            $qname = array ( 
                USER_OFFICER_COMMANDER => "pr_".USER_OFFICER_COMMANDER, 
                USER_OFFICER_ADMIRAL => "pr_".USER_OFFICER_ADMIRAL, 
                USER_OFFICER_ENGINEER => "pr_".USER_OFFICER_ENGINEER, 
                USER_OFFICER_GEOLOGE => "pr_".USER_OFFICER_GEOLOGE, 
                USER_OFFICER_TECHNOCRATE => "pr_".USER_OFFICER_TECHNOCRATE );
            foreach ( $qname as $i=>$qcmd )
            {
                if ($_POST[$qcmd] !== "") {
                    $days = intval ( $_POST[$qcmd] );
                    RecruitOfficer ( $player_id, $i, $days * 24 * 60 * 60 );
                }
            }
        }

        if ($action === "create_planet")        // Create a planet, stop the mines production.
        {
            $g = $_POST['g'];    if ($g === "" ) $g = 1;
            $s = $_POST['s'];    if ($s === "" ) $s = 1;
            $p = $_POST['p'];    if ($p === "" ) $p = 1;
            if ( ! HasPlanet ( $g, $s, $p ) ) { 
                $planet_id = CreatePlanet ($g, $s, $p, $_GET['player_id'] );
                $query = "UPDATE ".$db_prefix."planets SET mprod = 0, kprod = 0, dprod = 0 WHERE planet_id = " . $planet_id;
                dbquery ( $query );
            }
        }
    }

    // GET request processing.
    if ( method () === "GET" && $GlobalUser['admin'] >= 2 ) {
        
        if ( key_exists ('player_id', $_GET) ) $player_id = intval ($_GET['player_id']);
        else $player_id = 0;
        
        if ( key_exists ('action', $_GET) && $player_id ) $action = $_GET['action'];
        else $action = "";
        
        $now = time();

        if ( $action === "recalc_stats" )    // Recalculate stats
        {
            RecalcStats ($player_id);
            RecalcRanks ();
        }

        if ( $action === "reactivate" )     // Send new password
        {
            ReactivateUser ( $player_id );
        }

        if ( $action === "bot_start" )    // Start the bot
        {
            StartBot ($player_id);
        }

        if ( $action === "bot_stop" )    // Stop the bot
        {
            StopBot ($player_id);
        }
    }

    if ( key_exists("player_id", $_GET) ) {        // Player Information
        InvalidateUserCache ();
        $user = LoadUser ( intval ($_GET['player_id']) );
?>

    <?php AdminPanel();?>

    <table>
    <form action="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=update&player_id=<?php echo $user['player_id'];?>" method="POST" >
    <tr><td class=c><?php echo AdminUserName($user);?></td><td class=c><?=loca("ADM_USER_SETTINGS");?></td><td class=c><?=loca("ADM_USER_RESEARCH");?></td></tr>

        <th valign=top><table>
            <tr><th><?=loca("ADM_USER_ID");?></th><th><?php echo $user['player_id'];?></th></tr>
            <tr><th><?=loca("ADM_USER_REGDATE");?></th><th><?php echo date ("Y-m-d H:i:s", $user['regdate']);?></th></tr>
            <tr><th><?=loca("ADM_USER_ALLY");?></th><th>
<?php
    if ($user['ally_id']) {
        $ally = LoadAlly ($user['ally_id']);
        echo "[".$ally['tag']."] ".$ally['name'];
    }
?>
</th></tr>
            <tr><th><?=loca("ADM_USER_JOINDATE");?></th><th>
<?php
    if ($user['ally_id']) echo date ("Y-m-d H:i:s", $user['joindate']);
?>
</th></tr>
            <tr><th><?=loca("ADM_USER_PEMAIL");?></th><th><input type="text" name="pemail" maxlength="100" size="20" value="<?php echo $user['pemail'];?>" /></th></tr>
            <tr><th><?=loca("ADM_USER_EMAIL");?></th><th><input type="text" name="email" maxlength="100" size="20" value="<?php echo $user['email'];?>" /></th></tr>
            <tr><th><?=loca("ADM_USER_DELETE");?></th><th><input type="checkbox" name="deaktjava"  <?php echo IsChecked($user, "disable");?>/>
      <?php
    if ($user['disable']) echo date ("Y-m-d H:i:s", $user['disable_until']);
?></th></tr>
            <tr><th><?=loca("ADM_USER_VACATION");?></th><th><input type="checkbox" name="vacation"  <?php echo IsChecked($user, "vacation");?>/>
      <?php
    if ($user['vacation']) echo date ("Y-m-d H:i:s", $user['vacation_until']);
?></th></tr>
            <tr><th><?=loca("ADM_USER_BLOCKED");?></th><th><input type="checkbox" name="banned"  <?php echo IsChecked($user, "banned");?>/>
      <?php
    if ($user['banned']) echo date ("Y-m-d H:i:s", $user['banned_until']);
?></th></tr>
            <tr><th><?=loca("ADM_USER_ATTACK_BAN");?></th><th><input type="checkbox" name="noattack"  <?php echo IsChecked($user, "noattack");?>/>
      <?php
    if ($user['noattack']) echo date ("Y-m-d H:i:s", $user['noattack_until']);
?></th></tr>
            <tr><th><?=loca("ADM_USER_LAST_LOGIN");?></th><th><?php echo date ("Y-m-d H:i:s", $user['lastlogin']);?></th></tr>
            <tr><th><?=loca("ADM_USER_ACTIVITY");?></th><th>
<?php
    $now = time ();
    echo date ("Y-m-d H:i:s", $user['lastclick']);
    if ( ($now - $user['lastclick']) < 60*60 ) echo " (".floor(($now - $user['lastclick'])/60)." min)";
?>
</th></tr>
            <tr><th><?=loca("ADM_USER_IP");?></th><th><a href="http://nic.ru/whois/?query=<?php echo $user['ip_addr'];?>" target=_blank><?php echo $user['ip_addr'];?></a></th></tr>
            <tr><th><?=loca("ADM_USER_ACTIVATED");?></th><th><input type="checkbox" name="validated" <?php echo IsChecked($user, "validated");?> /> <a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=reactivate&player_id=<?php echo $user['player_id'];?>"><?=loca("ADM_USER_SEND_PASS");?></a></th></tr>
            <tr><th><?=loca("ADM_USER_HOMEPLANET");?></th><th>
<?php
    $planet = GetPlanet ($user['hplanetid']);
    echo "[".$planet['g'].":".$planet['s'].":".$planet['p']."] <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a>";
?>
</th></tr>
            <tr><th><?=loca("ADM_USER_ACTPLANET");?></th><th>
<?php
    $planet = GetPlanet ($user['aktplanet']);
    if ($planet == null) $planet = array ('g' => 0, 's' => 0, 'p' => 0, 'planet_id' => 0, 'name' => '' );
    echo "[".$planet['g'].":".$planet['s'].":".$planet['p']."] <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a>";
?>
</th></tr>
            <tr><th><?=loca("ADM_USER_LEVEL");?></th><th>
   <select name="admin">
     <option value="0" <?php echo IsSelected($user, "admin", 0);?>><?=loca("ADM_USER_LEVEL0");?></option>
     <option value="1" <?php echo IsSelected($user, "admin", 1);?>><?=loca("ADM_USER_LEVEL1");?></option>
     <option value="2" <?php echo IsSelected($user, "admin", 2);?>><?=loca("ADM_USER_LEVEL2");?></option>
   </select>
</th></tr>
            <tr><th><?=loca("ADM_USER_SNIFF");?></th><th><input type="checkbox" name="sniff" <?php echo IsChecked($user, "sniff");?> /></th></tr>
            <tr><th><?=loca("ADM_USER_DEBUG");?></th><th><input type="checkbox" name="debug" <?php echo IsChecked($user, "debug");?> /></th></tr>

<?php
    if ( IsBot ($user['player_id']) )
    {
?>
            <tr><th colspan=2><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=bot_stop&player_id=<?php echo $user['player_id'];?>" ><?=loca("ADM_USER_STOP_BOT");?></a></th></tr>
<?php
    }
    else
    {
?>
            <tr><th colspan=2><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=bot_start&player_id=<?php echo $user['player_id'];?>" ><?=loca("ADM_USER_START_BOT");?></a></th></tr>
<?php
    }
?>
        </table></th>

        <th valign=top><table>
            <tr><th><?=loca("ADM_USER_SORT_PLANET");?></th><th>
   <select name="settings_sort">
    <option value="0" <?php echo IsSelected($user, "sortby", 0);?> ><?=loca("ADM_USER_SORT_PLANET_0");?></option>
    <option value="1" <?php echo IsSelected($user, "sortby", 1);?> ><?=loca("ADM_USER_SORT_PLANET_1");?></option>
    <option value="2" <?php echo IsSelected($user, "sortby", 2);?> ><?=loca("ADM_USER_SORT_PLANET_2");?></option>
   </select>
</th></tr>
            <tr><th><?=loca("ADM_USER_SORT_ORDER");?></th><th>
   <select name="settings_order">
     <option value="0" <?php echo IsSelected($user, "sortorder", 0);?>><?=loca("ADM_USER_SORT_ORDER_0");?></option>
     <option value="1" <?php echo IsSelected($user, "sortorder", 1);?>><?=loca("ADM_USER_SORT_ORDER_1");?></option>
   </select>
</th></tr>
            <tr><th><?=loca("ADM_USER_SKIN");?></th><th><input type=text name="dpath" maxlength="80" size="40" value="<?php echo $user['skin'];?>" /></th></tr>
            <tr><th><?=loca("ADM_USER_USE_SKIN");?></th><th><input type="checkbox" name="design" <?php echo IsChecked($user, "useskin");?> /></th></tr>
            <tr><th><?=loca("ADM_USER_DEACT_IP");?></th><th><input type="checkbox" name="deact_ip" <?php echo IsChecked($user, "deact_ip");?> /></th></tr>
            <tr><th><?=loca("ADM_USER_SPY_PROBES");?></th><th><input type="text" name="spio_anz" maxlength="2" size="2" value="<?php echo $user['maxspy'];?>" /></th></tr>
            <tr><th><?=loca("ADM_USER_FLEET_MESSAGES");?></th><th><input type="text" name="settings_fleetactions" maxlength="2" size="2" value="<?php echo $user['maxfleetmsg'];?>" /></th></tr>

            <tr><th colspan=2>&nbsp</th></tr>
            <tr><td class=c colspan=2><?=loca("ADM_USER_STATS");?></td></tr>
            <tr><th><?=loca("ADM_USER_SCORE0_OLD");?></th><th><?php echo nicenum($user['oldscore1'] / 1000);?> / <?php echo nicenum($user['oldplace1']);?></th></tr>
            <tr><th><?=loca("ADM_USER_SCORE1_OLD");?></th><th><?php echo nicenum($user['oldscore2']);?> / <?php echo nicenum($user['oldplace2']);?></th></tr>
            <tr><th><?=loca("ADM_USER_SCORE2_OLD");?></th><th><?php echo nicenum($user['oldscore3']);?> / <?php echo nicenum($user['oldplace3']);?></th></tr>
            <tr><th><?=loca("ADM_USER_SCORE0");?></th><th><?php echo nicenum($user['score1'] / 1000);?> / <?php echo nicenum($user['place1']);?></th></tr>
            <tr><th><?=loca("ADM_USER_SCORE1");?></th><th><?php echo nicenum($user['score2']);?> / <?php echo nicenum($user['place2']);?></th></tr>
            <tr><th><?=loca("ADM_USER_SCORE2");?></th><th><?php echo nicenum($user['score3']);?> / <?php echo nicenum($user['place3']);?></th></tr>
            <tr><th><?=loca("ADM_USER_STATS_DATE");?></th><th><?php echo date ("Y-m-d H:i:s", $user['scoredate']);?></th></tr>
            <tr><th colspan=2><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=recalc_stats&player_id=<?php echo $user['player_id'];?>" ><?=loca("ADM_USER_RECALC_STATS");?></a></th></tr>

            <tr><th colspan=2>&nbsp</th></tr>
            <tr><td class=c colspan=2><?=loca("ADM_USER_PREM");?></td></tr>
            <tr><th colspan=2><table><tr>
<?php
    $oname = array ( loca("PR_COMA"), loca("PR_ADMIRAL"), loca("PR_ENGINEER"), loca("PR_GEOLOGIST"), loca("PR_TECHNO") );
    $odesc = array ( '', 
                             '<font size=1 color=skyblue>'.loca("PR_ADMIRAL_INFO").'</font>', 
                             '<font size=1 color=skyblue>'.loca("PR_ENGINEER_INFO").'</font>',
                             '<font size=1 color=skyblue>'.loca("PR_GEOLOGIST_INFO").'</font>',
                             '<font size=1 color=skyblue>'.loca("PR_TECHNO_INFO").'</font>' );
    $officeers = array ( USER_OFFICER_COMMANDER, USER_OFFICER_ADMIRAL, USER_OFFICER_ENGINEER, USER_OFFICER_GEOLOGE, USER_OFFICER_TECHNOCRATE );
    $imgname = array ( 'commander', 'admiral', 'ingenieur', 'geologe', 'technokrat');

    $now = time ();

    foreach ( $officeers as $i=>$qtype )
    {
        $end = GetOfficerLeft ( $user, $qtype );

        $img = "";
        if ($end <= $now) {
            $img = "_un";
            $days = "";
        }
        else {
            $d = ($end - $now) / (60*60*24);
            if ( $d  > 0 ) $days = va(loca("PR_ACTIVE_DAYS"), ceil($d));
        }

        echo "    <td align='center' width='35' class='header'>\n";
        echo "	<img border='0' src='img/".$imgname[$i]."_ikon".$img.".gif' width='32' height='32' alt='".$oname[$i]."'\n";
        echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days."<br>".$oname[$i]."</font><br>".$odesc[$i]."<br></b></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
        echo "    </td> <td><input type=\"text\" name=\"pr_".$qtype."\" size=\"3\" /></td>\n\n";
    }
?>
        </tr></table></th></tr>

            <tr><th colspan=2><i><?=loca("ADM_USER_PREM_INFO");?></i></th></tr>

        </table></th>

        <th valign=top><table>
<?php
        foreach ( $resmap as $i=>$gid) {
            echo "<tr><th>".loca("NAME_$gid")."</th><th><input type=\"text\" size=3 name=\"r$gid\" value=\"".$user[$gid]."\" /></th></tr>\n";
        }
?>
        <tr><td colspan=2>&nbsp;</td></tr>
        <tr><th><?=loca("ADM_USER_DM_FOUND");?></th><th><input type="text" size=5 name="dmfree" value="<?php echo $user['dmfree'];?>" /></th></tr>
        <tr><th><?=loca("ADM_USER_DM_BOUGHT");?></th><th><input type="text" size=5 name="dm" value="<?php echo $user['dm'];?>" /></th></tr>
        </table></th>
    <tr><th colspan=3><input type="submit" value="<?=loca("ADM_USER_SAVE");?>" /></th></tr>
    </form>
    </table>

    <br>
    <table> 
    <form action="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=create_planet&player_id=<?php echo $user['player_id'];?>" method="POST" >
    <tr><td class=c colspan=20><?=loca("ADM_USER_PLANET_LIST");?></td></tr>
    <tr>
<?php
    $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".intval ($_GET['player_id'])."' ORDER BY g ASC, s ASC, p ASC, type DESC";
    $result = dbquery ($query);
    $rows = dbrows ($result);
    $counter = 0;
    while ($rows--)
    {
        $p = dbarray ($result);
?>
    <td> <img src="<?php echo GetPlanetSmallImage( "../evolution/", $p);?>" width="32px" height="32px"></td>
    <td> <a href="index.php?page=admin&session=<?php echo $session;?>&mode=Planets&cp=<?php echo $p['planet_id'];?>"> <?php echo $p['name'];?> </a>
            [<a href="index.php?page=galaxy&session=<?php echo $session;?>&galaxy=<?php echo $p['g'];?>&system=<?php echo $p['s'];?>"><?php echo $p['g'];?>:<?php echo $p['s'];?>:<?php echo $p['p'];?></a>] </td>
<?php
        $counter++;
        if ( $counter > 9) {
            $counter = 0;
            echo "</tr>\n<tr>\n";
        }
    }
?>
    </tr>
    <tr><td colspan=20> <?=loca("ADM_USER_COORDS");?> <input name="g" size=2> <input name="s" size=2> <input name="p" size=2> <input type="submit" value="<?=loca("ADM_USER_CREATE_PLANET");?>"></td></tr>
    </form>
    </table>

    <br>
    <table>

<?php
        if ( key_exists('action', $_GET) && $_GET['action'] === 'fleetlogs' ) {

            echo "<tr><td class=c colspan=12>".loca("ADM_USER_FLEET_LOGS")."</td></tr>\n";

            if ( $_GET['from'] == 1 ) $result = FleetlogsFromPlayer ( $user['player_id'], $FleetMissionList[$_GET['mission']] );
            else $result = FleetlogsToPlayer ( $user['player_id'], $FleetMissionList[$_GET['mission']] );

            $anz = $rows = dbrows ( $result );
            echo "<tr><td class=c>N</td> <td class=c>".loca("ADM_USER_FLOG_TIMER").
                "</td> <td class=c>".loca("ADM_USER_FLOG_ORDER").
                "</td> <td class=c>".loca("ADM_USER_FLOG_SENT").
                "</td> <td class=c>".loca("ADM_USER_FLOG_ARRIVE").
                "</td><td class=c>".loca("ADM_USER_FLOG_TIME").
                "</td> <td class=c>".loca("ADM_USER_FLOG_START").
                "</td> <td class=c>".loca("ADM_USER_FLOG_TARGET").
                "</td> <td class=c>".loca("ADM_USER_FLOG_FLEET").
                "</td> <td class=c>".loca("ADM_USER_FLOG_RESOURCES").
                "</td> <td class=c>".loca("ADM_USER_FLOG_CARGO").
                "</td> <td class=c>".loca("ADM_USER_FLOG_ACS")."</td> </tr>\n";
            $bxx = 1;
            while ($rows--)
            {
                $fleet_obj = dbarray ($result);

                $fleet_price = FleetPrice ( $fleet_obj );
                $points = $fleet_price['points'];
                $fpoints = $fleet_price['fpoints'];
                $style = "";
                if ( $points >= $big_fleet_points ) {
                    if ( $fleet_obj['mission'] <= 2 ) $style = " style=\"background-color: FireBrick;\" ";
                    else $style = " style=\"background-color: DarkGreen;\" ";
                }
?>
        <tr <?php echo $style;?> >

        <th <?php echo $style;?> > <?php echo $bxx;?> </th>

        <th <?php echo $style;?> >
<?php
    echo "<table><tr $style ><th $style ><div id='bxx".$bxx."' title='".($fleet_obj['end'] - $now)."' star='".$fleet_obj['start']."'> </th>";
    echo "<tr><th $style >".date ("d.m.Y H:i:s", $fleet_obj['end'])."</th></tr></table>";
?>
        </th>
        <th <?php echo $style;?> >
<?php
    echo FleetlogsMissionText ( $fleet_obj['mission'] );
?>
        </th>
        <th <?php echo $style;?> ><?php echo date ("d.m.Y", $fleet_obj['start']);?> <br> <?php echo date ("H:i:s", $fleet_obj['start']);?></th>
        <th <?php echo $style;?> ><?php echo date ("d.m.Y", $fleet_obj['end']);?> <br> <?php echo date ("H:i:s", $fleet_obj['end']);?></th>
        <th <?php echo $style;?> >
<?php
    echo "<nobr>".DurationFormat ($fleet_obj['flight_time']) . "</nobr><br>";
    echo "<nobr>".$fleet_obj['flight_time'] . " ".loca("TIME_SEC")."</nobr>";
?>
        </th>
        <th <?php echo $style;?> >
<?php
    echo "[".$fleet_obj['origin_g'].":".$fleet_obj['origin_s'].":".$fleet_obj['origin_p']."]";
    $u = LoadUser ( $fleet_obj['owner_id'] );
    echo " <br>" . AdminUserName($u);
?>
        </th>
        <th <?php echo $style;?> >
<?php
    echo "[".$fleet_obj['target_g'].":".$fleet_obj['target_s'].":".$fleet_obj['target_p']."]";
    $u = LoadUser ( $fleet_obj['target_id'] );
    echo " <br>" . AdminUserName($u);
?>
        </th>
        <th <?php echo $style;?> >
<?php
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet_obj[$gid];
        if ( $amount > 0 ) echo loca ("NAME_$gid") . ":" . nicenum($amount) . " ";
    }
?>
        </th>
        <th <?php echo $style;?> >
<?php
    $total = $fleet_obj['p'.GID_RC_METAL] + $fleet_obj['p'.GID_RC_CRYSTAL] + $fleet_obj['p'.GID_RC_DEUTERIUM];
    if ( $total > 0 ) {
        echo loca("NAME_".GID_RC_METAL) . ": " . nicenum ($fleet_obj['p'.GID_RC_METAL]) . "<br>" ;
        echo loca("NAME_".GID_RC_CRYSTAL) . ": " . nicenum ($fleet_obj['p'.GID_RC_CRYSTAL]) . "<br>" ;
        echo loca("NAME_".GID_RC_DEUTERIUM) . ": " . nicenum ($fleet_obj['p'.GID_RC_DEUTERIUM]) ;
    }
    else echo "-";
?>
        </th>
        <th <?php echo $style;?> >
<?php
    $total = $fleet_obj[GID_RC_METAL] + $fleet_obj[GID_RC_CRYSTAL] + $fleet_obj[GID_RC_DEUTERIUM];
    if ( $total > 0 ) {
        echo loca("NAME_".GID_RC_METAL) . ": " . nicenum ($fleet_obj[GID_RC_METAL]) . "<br>" ;
        echo loca("NAME_".GID_RC_CRYSTAL) . ": " . nicenum ($fleet_obj[GID_RC_CRYSTAL]) . "<br>" ;
        echo loca("NAME_".GID_RC_DEUTERIUM) . ": " . nicenum ($fleet_obj[GID_RC_DEUTERIUM]) ;
    }
    else echo "-";
?>
        </th>
        <th <?php echo $style;?> >
<?php
    if ( $fleet_obj['union_id'] ) {
        echo $fleet_obj['union_id'];
    }
    else echo "-";
?>
        </th>

        </tr>
<?php
            $bxx ++;
            }
            echo "<script language=javascript>anz=$anz;t();</script>\n";
        }
        else
        {
?>

    <tr><td class=c colspan=3><?=loca("ADM_USER_FLEET_LOGS");?></td></tr>
    <tr><td><?=loca("ADM_USER_FLOG_ORDER");?></td><td><?=va(loca("ADM_USER_FLOG_FROM"), $user['oname']);?></td><td><?=va(loca("ADM_USER_FLOG_TO"), $user['oname']);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_0");?></td><td><?php echo LinkFleetsFrom($user,0);?></td><td><?php echo LinkFleetsTo($user,0);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_1");?></td><td><?php echo LinkFleetsFrom($user,1);?></td><td><?php echo LinkFleetsTo($user,1);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_2");?></td><td><?php echo LinkFleetsFrom($user,2);?></td><td><?php echo LinkFleetsTo($user,2);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_3");?></td><td><?php echo LinkFleetsFrom($user,3);?></td><td><?php echo LinkFleetsTo($user,3);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_4");?></td><td><?php echo LinkFleetsFrom($user,4);?></td><td><?php echo LinkFleetsTo($user,4);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_5");?></td><td><?php echo LinkFleetsFrom($user,5);?></td><td><?php echo LinkFleetsTo($user,5);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_6");?></td><td><?php echo LinkFleetsFrom($user,6);?></td><td><?php echo LinkFleetsTo($user,6);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_7");?></td><td><?php echo LinkFleetsFrom($user,7);?></td><td><?php echo LinkFleetsTo($user,7);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_8");?></td><td><?php echo LinkFleetsFrom($user,8);?></td><td><?php echo LinkFleetsTo($user,8);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_9");?></td><td><?php echo LinkFleetsFrom($user,9);?></td><td><?php echo LinkFleetsTo($user,9);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_15");?></td><td><?php echo LinkFleetsFrom($user,15);?></td><td><?php echo LinkFleetsTo($user,15);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_20");?></td><td><?php echo LinkFleetsFrom($user,20);?></td><td><?php echo LinkFleetsTo($user,20);?></td></tr>
    <tr><td><?=loca("ADM_USER_FORDER_21");?></td><td><?php echo LinkFleetsFrom($user,21);?></td><td><?php echo LinkFleetsTo($user,21);?></td></tr>
    </table>

<?php
        }
?>

<?php
    }
    else {
        $query = "SELECT * FROM ".$db_prefix."users ORDER BY regdate DESC LIMIT 25";
        $result = dbquery ($query);

        AdminPanel();

        echo "    </th> \n";
        echo "   </tr> \n";
        echo "</table> \n";
        echo loca("ADM_USER_NEW") . "<br>\n";
        echo "<table>\n";
        echo "<tr><td class=c>".loca("ADM_USER_REGDATE")."</td><td class=c>".loca("ADM_USER_HOMEPLANET")."</td><td class=c>".loca("ADM_USER_NAME")."</td></tr>\n";
        $rows = dbrows ($result);
        while ($rows--) 
        {
            $user = dbarray ( $result );
            $hplanet = GetPlanet ( $user['hplanetid'] );

            echo "<tr><th>".date ("Y-m-d H:i:s", $user['regdate'])."</th>";
            echo "<th>[".$hplanet['g'].":".$hplanet['s'].":".$hplanet['p']."] <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$hplanet['planet_id']."\">".$hplanet['name']."</a></th>";
            echo "<th>".AdminUserName($user)."</th></tr>\n";
        }
        echo "</table>\n";

?>

    <br>
    <table>
<?php
        $when = time () - 24 * 60 * 60;
        $query = "SELECT * FROM ".$db_prefix."users WHERE lastclick >= $when ORDER BY oname ASC";
        $result = dbquery ($query);
        $rows = dbrows ($result);
?>
    <tr><td class=c><?=va(loca("ADM_USER_ACTIVE_RECENTLY"), $rows);?></td></tr>
    <tr><td>
<?php
        $first = true;
        while ($rows--) 
        {
            $user = dbarray ( $result );
            if ( $first ) $first = false;
            else echo ", ";
            echo AdminUserName($user);
        }
?>
    </td></tr>
    </table>

<?php

    }

    // User Search
}

?>