<?php

// Admin Area: Global Event Queue.

class Admin_Queue extends Page {

    private int $player_id = 0;
    private mixed $result;

    public function controller () : bool {
        global $db_prefix;
        global $GlobalUser;
        global $now;

        // POST request processing.
        if ( method () === "POST" )
        {

            if ( key_exists ( "player", $_POST ) ) {        // Filter by player name
                $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '".$_POST['player']."%'";
                $result = dbquery ( $query );
                if ( dbrows ($result) > 0 ) {
                    $user = dbarray ($result);
                    $this->player_id = $user['player_id'];
                }
            }

            if ( key_exists ( "order_end", $_POST ) && $GlobalUser['admin'] >= USER_TYPE_ADMIN ) {        // Complete the task
                $id = intval ($_POST['order_end']);
                $query = "UPDATE ".$db_prefix."queue SET end=$now WHERE task_id=$id";
                dbquery ( $query );
            }

            if ( key_exists ( "order_remove", $_POST ) && $GlobalUser['admin'] >= USER_TYPE_ADMIN ) {        // Delete task
                RemoveQueue ( intval ($_POST['order_remove']) );
            }

            if ( key_exists ( "order_freeze", $_POST ) && $GlobalUser['admin'] >= USER_TYPE_ADMIN ) {        // Freeze task
                FreezeQueue ( intval ($_POST['order_freeze']), true );
            }

            if ( key_exists ( "order_unfreeze", $_POST ) && $GlobalUser['admin'] >= USER_TYPE_ADMIN ) {        // UnFreeze task
                FreezeQueue ( intval ($_POST['order_unfreeze']), false );
            }

            if ( key_exists ( "order_cron", $_POST ) && $GlobalUser['admin'] >= USER_TYPE_ADMIN ) {        // Check CRON
                $saved_player_id = $GlobalUser['player_id'];
                include "cron.php";
                $GlobalUser = LoadUser ($saved_player_id);  // reload original admin
            }
        }

        if ( $this->player_id > 0 ) $query = "SELECT * FROM ".$db_prefix."queue WHERE (type <> '".QTYP_FLEET."') AND owner_id=$this->player_id ORDER BY end ASC, prio DESC";
        else $query = "SELECT * FROM ".$db_prefix."queue WHERE (type <> '".QTYP_FLEET."') ORDER BY end ASC, prio DESC LIMIT 50";
        $this->result = dbquery ($query);

        return true;
    }

    public function view () : void {
        global $session;
        global $now;

    echo "<table>\n";
    echo "<tr><td class=c>".loca("ADM_QUEUE_END")."</td><td class=c>".loca("ADM_QUEUE_PLAYER")."</td><td class=c>".loca("ADM_QUEUE_TYPE")."</td><td class=c>".loca("ADM_QUEUE_DESCR")."</td><td class=c>".loca("ADM_QUEUE_PRIO")."</td><td class=c>ID</td><td class=c>".loca("ADM_QUEUE_CONTROL")."</td></tr>\n";

    $anz = $rows = dbrows ($this->result);
    $bxx = 1;
    while ($rows--) 
    {
        $queue = dbarray ( $this->result );
        $user = LoadUser ( $queue['owner_id'] );
        $pid = $user['player_id'];
        $freeze_seconds = $queue['freeze'] ? max (0, $now - $queue['frozen']) : 0;
        echo "<tr><th> <table><tr><th><div id='bxx".$bxx."' title='".($queue['end'] - $now + $freeze_seconds)."' star='".$queue['start']."'></th>";
        echo "<tr><th>".date ("d.m.Y H:i:s", $queue['end'])."</th></tr></table></th><th><a href=\"index.php?page=admin&session=$session&mode=Users&player_id=$pid\">".$user['oname']."</a></th><th>".$queue['type']."</th><th>".$this->QueueDesc($queue).$this->QueueFrozenDesc($queue)."</th><th>".$queue['prio']."</th><th>".$queue['task_id']."</th>\n";

        if ($queue['freeze']) {
            $freeze_loca = "ADM_QUEUE_UNFREEZE";
            $freeze_order = "unfreeze";
        }
        else {
            $freeze_loca = "ADM_QUEUE_FREEZE";
            $freeze_order = "freeze";
        }
?>

<style>
.compact-buttons {
    white-space: nowrap;
}

.compact-buttons form {
    display: inline-block;
    margin: 0 1px;
}

.btn-compact {
    padding: 2px 2px !important;
    font-size: 12px !important;
    margin: 0;
    line-height: 1.2;
    height: auto;
}

.btn-delete {
    border: 1px solid red;
}

.delete-form {
    display: inline-block;
}
</style>

<th class="compact-buttons"> 
    <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST">
        <input type="hidden" name="order_end" value="<?=$queue['task_id'];?>" />
        <input type="submit" class="btn-compact" value="<?=loca("ADM_QUEUE_COMPLETE");?>" />
    </form>
    <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST">
        <input type="hidden" name="order_<?=$freeze_order;?>" value="<?=$queue['task_id'];?>" />
        <input type="submit" class="btn-compact" value="<?=loca($freeze_loca);?>" />
    </form>
    <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST" class="delete-form">
        <input type="hidden" name="order_remove" value="<?=$queue['task_id'];?>" />
        <input type="submit" class="btn-compact btn-delete" value="<?=loca("ADM_QUEUE_DELETE");?>" />
    </form>
</th>

</tr>
<?php
        $bxx++;
    }
    echo "<script language=javascript>anz=$anz;t();</script>\n";

    echo "</table>\n";

    $playername = "";
    if ( $this->player_id > 0)
    {
        $user = LoadUser ( $this->player_id );
        if ($user) {
            $playername = $user['name'];
        }
    }
?>

    <br/>
    <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST">
    <?=loca("ADM_QUEUE_LOOKUP");?> <input size=15 name="player" value="<?=$playername;?>">
    <input type="submit" value="<?=loca("ADM_QUEUE_SUBMIT");?>">
    </form>

    <form action="index.php?page=admin&session=<?=$session;?>&mode=Queue" method="POST">
        <input type="hidden" name="order_cron" value="1" />
        <input type="submit" value="<?=loca("ADM_QUEUE_CRON");?>" />
    </form>

<?php
    }

    private function QueueDesc ( array $queue ) : string
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
                $planet = LoadPlanetById ($planet_id);
                return va(loca("ADM_QUEUE_TYPE_BUILD"), loca("NAME_$obj_id"), $level, AdminPlanetName ($planet));
            case QTYP_DEMOLISH:
                $query = "SELECT * FROM ".$db_prefix."buildqueue WHERE id = " . $queue['sub_id'] . " LIMIT 1";
                $result = dbquery ($query);
                $bqueue = dbarray ($result);
                $planet_id = $bqueue['planet_id'];
                $planet = LoadPlanetById ($planet_id);
                return va(loca("ADM_QUEUE_TYPE_DEMOLISH"), loca("NAME_$obj_id"), $level, AdminPlanetName ($planet));
            case QTYP_SHIPYARD:
                $planet = LoadPlanetById ($sub_id);
                return va(loca("ADM_QUEUE_TYPE_SHIPYARD"), loca("NAME_$obj_id"), $level, AdminPlanetName ($planet));
            case QTYP_RESEARCH:
                $planet = LoadPlanetById ($sub_id);
                return va(loca("ADM_QUEUE_TYPE_RESEARCH"), loca("NAME_$obj_id"), $level, AdminPlanetName ($planet));
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
        }

        return loca("ADM_QUEUE_TYPE_UNKNOWN") . " (type=$type, sub_id=$sub_id, obj_id=$obj_id, level=$level)";
    }

    private function QueueFrozenDesc (array $queue) : string {

        if ($queue['freeze']) {
            $frozen_seconds = time() - $queue['frozen'];
            return " (".loca("ADM_QUEUE_FROZEN")." " . $frozen_seconds . ")";
        }
        else return "";
    }
}

?>