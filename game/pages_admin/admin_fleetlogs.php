<?php

// Admin Area: Current flights of players, as well as flight logs

function Admin_Fleetlogs () : void
{
    global $session;
    global $db_prefix;
    global $GlobalUser;
    global $fleetmap;

    $big_fleet_points = 100000000;      // If a fleet is larger than the specified number of points, it is highlighted in a special way ("large").

    $now = time ();

    // Обработка POST-запросов.
    $player_id = 0;
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 )
    {
        if ( key_exists ( "order_2min", $_POST ) ) {        // -2 minutes before the task commences
            $id = intval ($_POST['order_2min']);
            $queue = LoadQueue ( $id );
            $fleet_obj = LoadFleet ( $queue['sub_id'] );
            if ( $fleet_obj['union_id'] ) {
                UpdateUnionTime ( $fleet_obj['union_id'], $now+2*60, 0, true );
            }
            else {
                $query = "UPDATE ".$db_prefix."queue SET end=".($now+2*60)." WHERE task_id=$id";
                dbquery ( $query );
            }
        }

        if ( key_exists ( "order_end", $_POST ) ) {        // Complete the task
            $id = intval ($_POST['order_end']);
            $queue = LoadQueue ( $id );
            $fleet_obj = LoadFleet ( $queue['sub_id'] );
            if ( $fleet_obj['union_id'] ) {
                UpdateUnionTime ( $fleet_obj['union_id'], $now, 0, true );
            }
            else {
                $query = "UPDATE ".$db_prefix."queue SET end=$now WHERE task_id=$id";
                dbquery ( $query );
            }
        }

        if ( key_exists ( "order_return", $_POST ) ) {        // Return the fleet
            $queue = LoadQueue ( intval ($_POST['order_return']) );
            RecallFleet ( $queue['sub_id'] );
        }
    }

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type='Fleet' ORDER BY end ASC";
    $result = dbquery ($query);
    $anz = $rows = dbrows ($result);
    $bxx = 1;

    AdminPanel();

    echo "<table>\n";
    echo "<tr><td class=c>N</td> <td class=c>".loca("ADM_FLOGS_TIMER")."</td> <td class=c>".loca("ADM_FLOGS_ORDER")."</td> <td class=c>".loca("ADM_FLOGS_SEND_TIME")."</td> <td class=c>".loca("ADM_FLOGS_ARRIVE_TIME")."</td><td class=c>".loca("ADM_FLOGS_FLIGHT_TIME")."</td> <td class=c>".loca("ADM_FLOGS_START")."</td> <td class=c>".loca("ADM_FLOGS_TARGET")."</td> <td class=c>".loca("ADM_FLOGS_FLEET")."</td> <td class=c>".loca("ADM_FLOGS_CARGO")."</td> <td class=c>".loca("ADM_FLOGS_FUEL")."</td> <td class=c>".loca("ADM_FLOGS_ACS")."</td> <td class=c colspan=3>".loca("ADM_FLOGS_ACTION")."</td> </tr>\n";

    while ($rows--)
    {
        $queue = dbarray ( $result );
        $fleet_obj = LoadFleet ( $queue['sub_id'] );

        $fleet_price = FleetPrice ( $fleet_obj );
        $points = $fleet_price['points'];
        $fpoints = $fleet_price['fpoints'];
        $style = "";
        if ( $points >= $big_fleet_points ) {
            if ( $fleet_obj['mission'] <= 2 ) $style = " style=\"background-color: FireBrick;\" ";
            else $style = " style=\"background-color: DarkGreen;\" ";
        }

?>

        <tr <?=$style;?> >

        <th <?=$style;?> > <?=$bxx;?> </th>

        <th <?=$style;?> >
<?php
    echo "<table><tr $style ><th $style ><div id='bxx".$bxx."' title='".($queue['end'] - $now)."' star='".$queue['start']."'> </th>";
    echo "<tr><th $style >".date ("d.m.Y H:i:s", $queue['end'])."</th></tr></table>";
?>
        </th>
        <th <?=$style;?> >
<?php
    echo FleetlogsMissionText ( $fleet_obj['mission'] );
?>
        </th>
        <th <?=$style;?> ><?=date ("d.m.Y", $queue['start']);?> <br> <?=date ("H:i:s", $queue['start']);?></th>
        <th <?=$style;?> ><?=date ("d.m.Y", $queue['end']);?> <br> <?=date ("H:i:s", $queue['end']);?></th>
        <th <?=$style;?> >
<?php
    echo "<nobr>".DurationFormat ($fleet_obj['flight_time']) . "</nobr><br>";
    echo "<nobr>".$fleet_obj['flight_time'] . " ".loca("TIME_SEC")."</nobr>";
?>
        </th>
        <th <?=$style;?> >
<?php
    $planet = LoadPlanetById ( $fleet_obj['start_planet'] );
    $user = LoadUser ( $planet['owner_id'] );
    echo AdminPlanetName($planet['planet_id']) . " " . AdminPlanetCoord($planet) . " <br>";
    echo AdminUserName($user);
?>
        </th>
        <th <?=$style;?> >
<?php
    $planet = LoadPlanetById ( $fleet_obj['target_planet'] );
    $user = LoadUser ( $planet['owner_id'] );
    echo AdminPlanetName($planet['planet_id']) . " " . AdminPlanetCoord($planet). " <br>";
    echo AdminUserName($user);
?>
        </th>
        <th <?=$style;?> >
<?php
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet_obj[$gid];
        if ( $amount > 0 ) echo loca ("NAME_$gid") . ":" . nicenum($amount) . " ";
    }
?>
        </th>
        <th <?=$style;?> >
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
        <th <?=$style;?> >
        <?=nicenum ($fleet_obj['fuel']);?>
        </th>
        <th <?=$style;?> >
<?php
    if ( $fleet_obj['union_id'] ) {
        echo $fleet_obj['union_id'];
    }
    else echo "-";
?>
        </th>

        <th <?=$style;?> >
         <form action="index.php?page=admin&session=<?=$session;?>&mode=Fleetlogs" method="POST">
    <input type="hidden" name="order_2min" value="<?=$queue['task_id'];?>" />
        <input type="submit" value="2m" />
     </form>
        </th>
        <th <?=$style;?> >
         <form action="index.php?page=admin&session=<?=$session;?>&mode=Fleetlogs" method="POST">
    <input type="hidden" name="order_end" value="<?=$queue['task_id'];?>" />
        <input type="submit" value="F" />
     </form>
        </th><th <?=$style;?> >
         <form action="index.php?page=admin&session=<?=$session;?>&mode=Fleetlogs" method="POST">
    <input type="hidden" name="order_return" value="<?=$queue['task_id'];?>" />
        <input type="submit" value="R" />
     </form>
        </th>
        </tr>

<?php

        $bxx++;

    }
    echo "<script language=javascript>anz=$anz;t();</script>\n";

    echo "</table>\n";

}
?>