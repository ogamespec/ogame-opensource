<?php

// Admin Area: history of player and operator actions

class Admin_Userlogs extends Page {

    private string $results = "";

    public function controller () : bool {
        global $db_prefix;
        global $GlobalUser;

        // POST request processing.
        if ( method () === "POST" && $GlobalUser['admin'] >= USER_TYPE_GO )
        {
            $name = $_POST['name'];
            $type = $_POST['type'];
            $period = intval($_POST['days'])*24*60*60 + intval($_POST['hours'])*60*60;
            $arr = date_parse_from_format ( "j.n.Y", $_POST['since']);
            $since = mktime ( 0, 0, 0, $arr['month'], $arr['day'], $arr['year'] );

            // Step 1: find all users by imprecise comparison
            $users = array ();
            $query = "SELECT * FROM ".$db_prefix."users WHERE player_id > 0";
            $result = dbquery ($query);
            while ( $user = dbarray($result) ) {
                $percent = 0;
                similar_text ( mb_strtolower ($name), mb_strtolower ($user['oname']), $percent );
                if ( $percent > 75 ) $users[] = $user;
            }

            // Step 2: select the events of the specified category for the time interval
            foreach ( $users as $i=>$user ) {
                if ( $type !== "ALL" ) $tstr = "AND type = '".$type."'";
                else $tstr = "";
                $query = "SELECT * FROM ".$db_prefix."userlogs WHERE owner_id = ".$user['player_id']." AND (date >= ".$since." AND date <= ".($since+$period).") ".$tstr." ORDER BY date ASC";
                $result = dbquery ($query);
                $count = dbrows ($result);
                $this->results .= "<h2>".va(loca("ADM_USERLOG_USER_HISTORY"), $type)." ".AdminUserName($user)." ($count)</h2>\n";
                $this->results .= "<table><tr><td class=\"c\">".loca("ADM_USERLOG_DATE")."</td><td class=\"c\">".loca("ADM_USERLOG_TYPE")."</td><td class=\"c\">".loca("ADM_USERLOG_ACTION")."</td></tr>\n";
                while ($log = dbarray ($result) ) {
                    $this->results .= "<tr><td>".date ("d.m.Y H:i:s", $log['date'])."</td><td>".$log['type']."</td><td>".$log['text']."</td></tr>\n";
                }
                $this->results .= "</table>";
            }
        }
        return true;
    }

    public function view () : void {
        global $db_prefix;
        global $session;

        if ( method () === "GET" ) {
            $query = "SELECT * FROM ".$db_prefix."userlogs WHERE owner_id > 0 ORDER BY date DESC LIMIT 50";
            $result = dbquery ($query );
            echo "<h2>".loca("ADM_USERLOG_LAST_ACTIONS")."</h2>\n";
            echo "<table><tr><td class=\"c\">".loca("ADM_USERLOG_DATE")."</td><td class=\"c\">".loca("ADM_USERLOG_USER")."</td><td class=\"c\">".loca("ADM_USERLOG_CATEGORY")."</td><td class=\"c\">".loca("ADM_USERLOG_ACTION")."</td></tr>\n";
            $rows = array ();
            while ($log = dbarray ($result) ) {
                $user = LoadUser($log['owner_id']);
                $rows[] = "<tr><td>".date ("d.m.Y H:i:s", $log['date'])."</td><td>".AdminUserName($user)."</td><td>".$log['type']."</td><td>".$log['text']."</td></tr>\n";
            }
            $rows = array_reverse ($rows);
            foreach ($rows as $i=>$row) echo $row;
            echo "</table>";
        }

?>

<?=$this->results;?>

<h2><?=loca("ADM_USERLOG_HISTORY");?></h2>

<table>
<form action="index.php?page=admin&session=<?=$session;?>&mode=UserLogs" method="POST" >

<tr><td><?=loca("ADM_USERLOG_USERNAME");?></td><td><input type="text" size=20 name="name"/> <?=loca("ADM_USERLOG_APPROX");?></td></tr>
<tr><td><?=loca("ADM_USERLOG_CATEGORY");?></td><td>
<select name="type">
<option value="ALL"><?=loca("ADM_USERLOG_CAT_ALL");?></option>
<option value="BUILD"><?=loca("ADM_USERLOG_CAT_BUILD");?></option>
<option value="RESEARCH"><?=loca("ADM_USERLOG_CAT_RESEARCH");?></option>
<option value="SHIPYARD"><?=loca("ADM_USERLOG_CAT_SHIPYARD");?></option>
<option value="DEFENSE"><?=loca("ADM_USERLOG_CAT_DEFENSE");?></option>
<option value="FLEET"><?=loca("ADM_USERLOG_CAT_FLEET");?></option>
<option value="PLANET"><?=loca("ADM_USERLOG_CAT_PLANET");?></option>
<option value="SETTINGS"><?=loca("ADM_USERLOG_CAT_SETTINGS");?></option>
<option value="OPER"><?=loca("ADM_USERLOG_CAT_OPER");?></option>
</select>
</td></tr>
<tr><td><?=loca("ADM_USERLOG_PERIOD");?></td><td><input type="text" size=2 name="days" value="2"/> <?=loca("ADM_USERLOG_DAYS");?> <input type="text" size=2 name="hours"/> <?=loca("ADM_USERLOG_HR");?></td></tr>
<tr><td><?=loca("ADM_USERLOG_FROM");?></td><td><input type="text" size=20 name="since" value="<?=date("d.m.Y", time()-24*60*60);?>"/> <?=loca("ADM_USERLOG_DMY");?></td></tr>

<tr><td class="c" colspan=2> <input type="submit" value="<?=loca("ADM_USERLOG_SUBMIT");?>" /></td></tr>

</form>
</table>

<?php
    }
}

?>