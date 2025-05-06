<?php

// Admin Area: Global Event Queue.

function QueueDesc ( $queue )
{
    global $session, $db_prefix;
    $type = $queue['type'];
    $sub_id = $queue['sub_id'];
    $obj_id = $queue['obj_id'];
    $level = $queue['level'];

    switch ( $type )
    {
        case QTYP_BUILD:
            $query = "SELECT * FROM ".$db_prefix."buildqueue WHERE id = " . $queue['sub_id'] . " LIMIT 1";
            $result = dbquery ($query);
            $bqueue = dbarray ($result);
            $planet_id = $bqueue['planet_id'];
            $planet = GetPlanet ($planet_id);
            return va(loca("ADM_QUEUE_TYPE_BUILD"), loca("NAME_$obj_id"), $level, AdminPlanetName ($planet['planet_id']));
        case QTYP_DEMOLISH:
            $query = "SELECT * FROM ".$db_prefix."buildqueue WHERE id = " . $queue['sub_id'] . " LIMIT 1";
            $result = dbquery ($query);
            $bqueue = dbarray ($result);
            $planet_id = $bqueue['planet_id'];
            $planet = GetPlanet ($planet_id);
            return va(loca("ADM_QUEUE_TYPE_DEMOLISH"), loca("NAME_$obj_id"), $level, AdminPlanetName ($planet['planet_id']));
        case QTYP_SHIPYARD:
            $planet = GetPlanet ($sub_id);
            return va(loca("ADM_QUEUE_TYPE_SHIPYARD"), loca("NAME_$obj_id"), $level, AdminPlanetName ($sub_id));
        case QTYP_RESEARCH:
            $planet = GetPlanet ($sub_id);
            return va(loca("ADM_QUEUE_TYPE_RESEARCH"), loca("NAME_$obj_id"), $level, AdminPlanetName ($sub_id));
        case QTYP_UPDATE_STATS: return loca("ADM_QUEUE_TYPE_UPDATE_STATS");
        case QTYP_RECALC_POINTS: return loca("ADM_QUEUE_TYPE_RECALC_POINTS");
        case QTYP_RECALC_ALLY_POINTS: return loca("ADM_QUEUE_TYPE_RECALC_ALLY_POINTS");
        case QTYP_ALLOW_NAME: return loca("ADM_QUEUE_TYPE_ALLOW_NAME");
        case QTYP_CHANGE_EMAIL: return loca("ADM_QUEUE_TYPE_CHANGE_EMAIL");
        case QTYP_UNLOAD_ALL: return loca("ADM_QUEUE_TYPE_UNLOAD_ALL");
        case QTYP_CLEAN_DEBRIS: return loca("ADM_QUEUE_TYPE_CLEAN_DEBRIS");
        case QTYP_CLEAN_PLANETS: return loca("ADM_QUEUE_TYPE_CLEAN_PLANETS");
        case QTYP_CLEAN_PLAYERS: return loca("ADM_QUEUE_TYPE_CLEAN_PLAYERS");
        case QTYP_UNBAN: return loca("ADM_QUEUE_TYPE_UNBAN_PLAYER");
        case QTYP_ALLOW_ATTACKS: return loca("ADM_QUEUE_TYPE_ALLOW_ATTACKS");
        case QTYP_AI:
            $strat_id = $queue['sub_id'];
            $block_id = $queue['obj_id'];
            $query = "SELECT * FROM ".$db_prefix."botstrat WHERE id = $strat_id LIMIT 1;";
            $result = dbquery ( $query );
            $strat = dbarray ($result);
            $source = json_decode ( $strat['source'], true );
            foreach ( $source['nodeDataArray'] as $i=>$arr ) {
                if ( $arr['key'] == $block_id ) {
                    $block_text = $arr['text'];
                    break;
                }
            }
            return va(loca("ADM_QUEUE_TYPE_AI"), $strat['name']) . " : <br>$block_text";

        case QTYP_COMMANDER_OFF: return loca("ADM_QUEUE_TYPE_COMMANDER_OFF");
        case QTYP_ADMIRAL_OFF: return loca("ADM_QUEUE_TYPE_ADMIRAL_OFF");
        case QTYP_ENGINEER_OFF: return loca("ADM_QUEUE_TYPE_ENGINEER_OFF");
        case QTYP_GEOLOGE_OFF: return loca("ADM_QUEUE_TYPE_GEOLOGE_OFF");
        case QTYP_TECHNOCRATE_OFF: return loca("ADM_QUEUE_TYPE_TECHNOCRATE_OFF");

    }

    return loca("ADM_QUEUE_TYPE_UNKNOWN") . " (type=$type, sub_id=$sub_id, obj_id=$obj_id, level=$level)";
}

function Admin_Queue ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    // POST request processing.
    $player_id = 0;
    if ( method () === "POST" )
    {

        if ( key_exists ( "player", $_POST ) ) {        // Filter by player name
            $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '".$_POST['player']."%'";
            $result = dbquery ( $query );
            if ( dbrows ($result) > 0 ) {
                $user = dbarray ($result);
                $player_id = $user['player_id'];
            }
        }

        if ( key_exists ( "order_end", $_POST ) && $GlobalUser['admin'] >= 2 ) {        // Complete the task
            $id = intval ($_POST['order_end']);
            $now = time ();
            $query = "UPDATE ".$db_prefix."queue SET end=$now WHERE task_id=$id";
            dbquery ( $query );
        }

        if ( key_exists ( "order_remove", $_POST ) && $GlobalUser['admin'] >= 2 ) {        // Delete task
            RemoveQueue ( intval ($_POST['order_remove']) );
        }
    }

    if ( $player_id > 0 ) $query = "SELECT * FROM ".$db_prefix."queue WHERE (type <> '".QTYP_FLEET."' AND type <> '".QTYP_COMMANDER_OFF."') AND owner_id=$player_id ORDER BY end ASC, prio DESC";
    else $query = "SELECT * FROM ".$db_prefix."queue WHERE (type <> '".QTYP_FLEET."' AND type <> '".QTYP_COMMANDER_OFF."') ORDER BY end ASC, prio DESC LIMIT 50";
    $result = dbquery ($query);
    $now = time ();

    AdminPanel();

    echo "<table>\n";
    echo "<tr><td class=c>".loca("ADM_QUEUE_END")."</td><td class=c>".loca("ADM_QUEUE_PLAYER")."</td><td class=c>".loca("ADM_QUEUE_TYPE")."</td><td class=c>".loca("ADM_QUEUE_DESCR")."</td><td class=c>".loca("ADM_QUEUE_PRIO")."</td><td class=c>ID</td><td class=c>".loca("ADM_QUEUE_CONTROL")."</td></tr>\n";

    $anz = $rows = dbrows ($result);
    $bxx = 1;
    while ($rows--) 
    {
        $queue = dbarray ( $result );
        $user = LoadUser ( $queue['owner_id'] );
        $pid = $user['player_id'];
        echo "<tr><th> <table><tr><th><div id='bxx".$bxx."' title='".($queue['end'] - $now)."' star='".$queue['start']."'></th>";
        echo "<tr><th>".date ("d.m.Y H:i:s", $queue['end'])."</th></tr></table></th><th><a href=\"index.php?page=admin&session=$session&mode=Users&player_id=$pid\">".$user['oname']."</a></th><th>".$queue['type']."</th><th>".QueueDesc($queue)."</th><th>".$queue['prio']."</th><th>".$queue['task_id']."</th>\n";
?>
    <th> 
         <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST">
    <input type="hidden" name="order_end" value="<?=$queue['task_id'];?>" />
        <input type="submit" value="<?=loca("ADM_QUEUE_COMPLETE");?>" />
     </form>
         <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST" style="border: 1px solid red">
    <input type="hidden" name="order_remove" value="<?=$queue['task_id'];?>" />
        <input type="submit" value="<?=loca("ADM_QUEUE_DELETE");?>" />
     </form>
    </th>
</tr>
<?php
        $bxx++;
    }
    echo "<script language=javascript>anz=$anz;t();</script>\n";

    echo "</table>\n";

    $playername = "";
    if ( $player_id > 0)
    {
        $user = LoadUser ( $player_id );
        $playername = $user['name'];
    }
?>

    <br/>
    <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST">
    <?=loca("ADM_QUEUE_LOOKUP");?> <input size=15 name="player" value="<?=$playername;?>">
    <input type="submit" value="<?=loca("ADM_QUEUE_SUBMIT");?>">
    </form>

<?php
}

?>