<?php

// ========================================================================================
// Текущие полёты игроков, а также логи полётов

function FleetlogsMissionText ($num)
{
    if ($num >= 200)
    {
        $desc = "<a title=\"На планете\">(Д)</a>";
        $num -= 200;
    }
    else if ($num >= 100)
    {
        $desc = "<a title=\"Возвращение к планете\">(В)</a>";
        $num -= 100;
    }
    else $desc = "<a title=\"Уход на задание\">(У)</a>";

    echo "      <a title=\"\">".loca("FLEET_ORDER_$num")."</a>\n$desc\n";
}

function Admin_Fleetlogs ()
{
    global $session;
    global $db_prefix;
    
    $now = time ();

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type='Fleet' ";
    $result = dbquery ($query);
    $anz = $rows = dbrows ($result);
    $bxx = 1;

    echo "<table>\n";
    echo "<tr><td class=c>Таймер</td> <td class=c>Задание</td> <td class=c>Отправлен</td> <td class=c>Прибывает</td><td class=c>Время полёта</td> <td class=c>Старт</td> <td class=c>Цель</td> <td class=c>Флот</td> <td class=c>Груз</td> <td class=c>САБ</td> <td class=c>Приказ</td> </tr>\n";

    while ($rows--)
    {
        $queue = dbarray ( $result );
        $fleet_obj = LoadFleet ( $queue['sub_id'] );

?>

        <tr>
        <th>
<?php
    echo "<table><tr><th><div id='bxx".$bxx."' title='".($queue['end'] - $now)."' star='".$queue['start']."'> </th>";
    echo "<tr><th>".date ("d.m.Y H:i:s", $queue['end'])."</th></tr></table>";
?>
        </th>
        <th>
<?php
    echo FleetlogsMissionText ( $fleet_obj['mission'] );
?>
        </th>
        <th><?=date ("d.m.Y", $queue['start']);?> <br> <?=date ("H:i:s", $queue['start']);?></th>
        <th><?=date ("d.m.Y", $queue['end']);?> <br> <?=date ("H:i:s", $queue['end']);?></th>
        <th>
<?php
    echo $fleet_obj['flight_time'] . " сек.";
?>
        </th>
        <th>
<?php
    $planet = GetPlanet ( $fleet_obj['start_planet'] );
    $user = LoadUser ( $planet['owner_id'] );
    echo $planet['name'] . " [".$planet['g'].":".$planet['s'].":".$planet['p']."] <br>";
    echo $user['oname'];
?>
        </th>
        <th>
<?php
    $planet = GetPlanet ( $fleet_obj['target_planet'] );
    $user = LoadUser ( $planet['owner_id'] );
    echo $planet['name'] . " [".$planet['g'].":".$planet['s'].":".$planet['p']."] <br>";
    echo $user['oname'];
?>
        </th>
        <th>
<?php
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet_obj["ship".$gid];
        if ( $amount > 0 ) echo loca ("NAME_$gid") . ":" . nicenum($amount) . " ";
    }
?>
        </th>
        <th>
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
        <th>
<?php
    if ( $fleet_obj['union_id'] ) {
        echo $fleet_obj['union_id'];
    }
    else echo "-";
?>
        </th>
        <th>x</th>
        </tr>

<?php

    }
    echo "<script language=javascript>anz=$anz;t();</script>\n";

    echo "</table>\n";

}
?>