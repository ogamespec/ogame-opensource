<?php

// ========================================================================================
// Текущие полёты игроков, а также логи полётов

function Admin_Fleetlogs ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $now = time ();

    // Обработка POST-запросов.
    $player_id = 0;
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 )
    {
        if ( key_exists ( "order_2min", $_POST ) ) {        // -2 минуты до оконачания задания
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

        if ( key_exists ( "order_end", $_POST ) ) {        // Завершить задание
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

        if ( key_exists ( "order_return", $_POST ) ) {        // Развернуть флот
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
    echo "<tr><td class=c>N</td> <td class=c>Таймер</td> <td class=c>Задание</td> <td class=c>Отправлен</td> <td class=c>Прибывает</td><td class=c>Время полёта</td> <td class=c>Старт</td> <td class=c>Цель</td> <td class=c>Флот</td> <td class=c>Груз</td> <td class=c>Топливо</td> <td class=c>САБ</td> <td class=c colspan=3>Приказ</td> </tr>\n";

    while ($rows--)
    {
        $queue = dbarray ( $result );
        $fleet_obj = LoadFleet ( $queue['sub_id'] );

        $fleet_price = FleetPrice ( $fleet_obj );
        $points = $fleet_price['points'];
        $fpoints = $fleet_price['fpoints'];
        $style = "";
        if ( $points >= 100000000 ) {
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
    echo "<nobr>".BuildDurationFormat ($fleet_obj['flight_time']) . "</nobr><br>";
    echo "<nobr>".$fleet_obj['flight_time'] . " сек.</nobr>";
?>
        </th>
        <th <?=$style;?> >
<?php
    $planet = GetPlanet ( $fleet_obj['start_planet'] );
    $user = LoadUser ( $planet['owner_id'] );
    echo AdminPlanetName($planet) . " " . AdminPlanetCoord($planet) . " <br>";
    echo AdminUserName($user);
?>
        </th>
        <th <?=$style;?> >
<?php
    $planet = GetPlanet ( $fleet_obj['target_planet'] );
    $user = LoadUser ( $planet['owner_id'] );
    echo AdminPlanetName($planet) . " " . AdminPlanetCoord($planet). " <br>";
    echo AdminUserName($user);
?>
        </th>
        <th <?=$style;?> >
<?php
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet_obj["ship".$gid];
        if ( $amount > 0 ) echo loca ("NAME_$gid") . ":" . nicenum($amount) . " ";
    }
?>
        </th>
        <th <?=$style;?> >
<?php
    $total = $fleet_obj['m'] + $fleet_obj['k'] + $fleet_obj['d'];
    if ( $total > 0 ) {
        echo "М: " . nicenum ($fleet_obj['m']) . "<br>" ;
        echo "К: " . nicenum ($fleet_obj['k']) . "<br>" ;
        echo "Д: " . nicenum ($fleet_obj['d']) ;
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