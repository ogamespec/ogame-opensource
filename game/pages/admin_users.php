<?php

// ========================================================================================
// Пользователи.

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

function IsChecked ($user, $option)
{
    if ( $user[$option] ) return "checked=checked";
    else return "";
}

function IsSelected ($user, $option, $value)
{
    if ( $user[$option] == $value ) return "selected";
    else return "";
}

function Admin_Users ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;
    global $FleetMissionList;

    $now = time();

    $resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );
    
    $unitab = LoadUniverse ();
    $speed = $unitab['speed'];

    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 ) {
        
        if ( key_exists('player_id', $_GET) ) $player_id = intval ($_GET['player_id']);
        else $player_id = 0;
        
        if (key_exists('action', $_GET) && $player_id) $action = $_GET['action'];
        else $action = "";

        if ($action === "update")        // Обновить данные пользователя.
        {
            $query = "UPDATE ".$db_prefix."users SET ";

            foreach ( $resmap as $i=>$gid)
            {
                $query .= "r$gid = ".intval ($_POST["r$gid"]).", ";
            }

            if ( $_POST['deaktjava'] === "on" ) {
                    $query .= "disable = 1, disable_until = " . ($now+7*24*60*60).", ";
            }
            else {
                $query .= "disable = 0, ";
            }
            if ( $_POST['vacation'] === "on" ) {
                $query .= "vacation = 1, vacation_until = " . ($now+((2*24*60*60)/ $speed)) .", ";
            }
            else $query .= "vacation = 0, ";
            if ( $_POST['banned'] !== "on" ) $query .= "banned = 0, ";
            if ( $_POST['noattack'] !== "on" ) $query .= "noattack = 0, ";

            $query .= "pemail = '".$_POST['pemail']."', ";
            $query .= "email = '".$_POST['email']."', ";
            $query .= "admin = '".$_POST['admin']."', ";
            $query .= "validated = ".($_POST['validated']==="on"?1:0).", ";
            $query .= "sniff = ".($_POST['sniff']==="on"?1:0).", ";
            $query .= "debug = ".($_POST['debug']==="on"?1:0).", ";

            $query .= "dm = '".intval ($_POST['dm'])."', ";
            $query .= "dmfree = '".intval ($_POST['dmfree'])."', ";

            $query .= "sortby = '".intval ($_POST['settings_sort'])."', ";
            $query .= "sortorder = '".intval ($_POST['settings_order'])."', ";
            $query .= "skin = '".$_POST['dpath']."', ";
            $query .= "useskin = ".($_POST['design']==="on"?1:0).", ";
            $query .= "deact_ip = ".($_POST['deact_ip']==="on"?1:0).", ";
            $query .= "maxspy = '".intval ($_POST['spio_anz'])."', ";
            $query .= "maxfleetmsg = '".intval ($_POST['settings_fleetactions'])."' ";

            $query .= " WHERE player_id=$player_id;";
            dbquery ($query);

            $qname = array ( 'CommanderOff', 'AdmiralOff', 'EngineerOff', 'GeologeOff', 'TechnocrateOff' );
            foreach ( $qname as $i=>$qcmd )
            {
                $days = intval ( $_POST[$qcmd] );
                if ( $days > 0 ) RecruitOfficer ( $player_id, $qcmd, $days * 24 * 60 * 60 );
            }
        }

        if ($action === "create_planet")        // Создать планету, остановить выработку шахт
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

    // Обработка GET-запроса.
    if ( method () === "GET" && $GlobalUser['admin'] >= 2 ) {
        
        if ( key_exists ('player_id', $_GET) ) $player_id = intval ($_GET['player_id']);
        else $player_id = 0;
        
        if ( key_exists ('action', $_GET) && $player_id ) $action = $_GET['action'];
        else $action = "";
        
        $now = time();

        if ( $action === "recalc_stats" )    // Пересчитать статистику
        {
            RecalcStats ($player_id);
            RecalcRanks ();
        }

        if ( $action === "reactivate" )     // Выслать новый пароль
        {
            ReactivateUser ( $player_id );
        }

        if ( $action === "bot_start" )    // Запустить бота
        {
            StartBot ($player_id);
        }

        if ( $action === "bot_stop" )    // Остановить бота
        {
            StopBot ($player_id);
        }
    }

    if ( key_exists("player_id", $_GET) ) {        // Информация об игроке
        InvalidateUserCache ();
        $user = LoadUser ( intval ($_GET['player_id']) );
?>

    <?php echo AdminPanel();?>

    <table>
    <form action="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=update&player_id=<?php echo $user['player_id'];?>" method="POST" >
    <tr><td class=c><?php echo AdminUserName($user);?></td><td class=c>Настройки</td><td class=c>Исследования</td></tr>

        <th valign=top><table>
            <tr><th>ID</th><th><?php echo $user['player_id'];?></th></tr>
            <tr><th>Дата регистрации</th><th><?php echo date ("Y-m-d H:i:s", $user['regdate']);?></th></tr>
            <tr><th>Альянс</th><th>
<?php
    if ($user['ally_id']) {
        $ally = LoadAlly ($user['ally_id']);
        echo "[".$ally['tag']."] ".$ally['name'];
    }
?>
</th></tr>
            <tr><th>Дата вступления</th><th>
<?php
    if ($user['ally_id']) echo date ("Y-m-d H:i:s", $user['joindate']);
?>
</th></tr>
            <tr><th>Постоянный адрес</th><th><input type="text" name="pemail" maxlength="100" size="20" value="<?php echo $user['pemail'];?>" /></th></tr>
            <tr><th>Временный адрес</th><th><input type="text" name="email" maxlength="100" size="20" value="<?php echo $user['email'];?>" /></th></tr>
            <tr><th>Удалить игрока</th><th><input type="checkbox" name="deaktjava"  <?php echo IsChecked($user, "disable");?>/>
      <?php
    if ($user['disable']) echo date ("Y-m-d H:i:s", $user['disable_until']);
?></th></tr>
            <tr><th>Режим отпуска</th><th><input type="checkbox" name="vacation"  <?php echo IsChecked($user, "vacation");?>/>
      <?php
    if ($user['vacation']) echo date ("Y-m-d H:i:s", $user['vacation_until']);
?></th></tr>
            <tr><th>Заблокирован</th><th><input type="checkbox" name="banned"  <?php echo IsChecked($user, "banned");?>/>
      <?php
    if ($user['banned']) echo date ("Y-m-d H:i:s", $user['banned_until']);
?></th></tr>
            <tr><th>Бан атак</th><th><input type="checkbox" name="noattack"  <?php echo IsChecked($user, "noattack");?>/>
      <?php
    if ($user['noattack']) echo date ("Y-m-d H:i:s", $user['noattack_until']);
?></th></tr>
            <tr><th>Последний вход</th><th><?php echo date ("Y-m-d H:i:s", $user['lastlogin']);?></th></tr>
            <tr><th>Активность</th><th>
<?php
    $now = time ();
    echo date ("Y-m-d H:i:s", $user['lastclick']);
    if ( ($now - $user['lastclick']) < 60*60 ) echo " (".floor(($now - $user['lastclick'])/60)." min)";
?>
</th></tr>
            <tr><th>IP адрес</th><th><a href="http://nic.ru/whois/?query=<?php echo $user['ip_addr'];?>" target=_blank><?php echo $user['ip_addr'];?></a></th></tr>
            <tr><th>Активирован</th><th><input type="checkbox" name="validated" <?php echo IsChecked($user, "validated");?> /> <a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=reactivate&player_id=<?php echo $user['player_id'];?>">выслать пароль</a></th></tr>
            <tr><th>Главная планета</th><th>
<?php
    $planet = GetPlanet ($user['hplanetid']);
    echo "[".$planet['g'].":".$planet['s'].":".$planet['p']."] <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a>";
?>
</th></tr>
            <tr><th>Текущая планета</th><th>
<?php
    $planet = GetPlanet ($user['aktplanet']);
    echo "[".$planet['g'].":".$planet['s'].":".$planet['p']."] <a href=\"index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id']."\">".$planet['name']."</a>";
?>
</th></tr>
            <tr><th>Права</th><th>
   <select name="admin">
     <option value="0" <?php echo IsSelected($user, "admin", 0);?>>Пользователь</option>
     <option value="1" <?php echo IsSelected($user, "admin", 1);?>>Оператор</option>
     <option value="2" <?php echo IsSelected($user, "admin", 2);?>>Администратор</option>
   </select>
</th></tr>
            <tr><th>Включить слежение</th><th><input type="checkbox" name="sniff" <?php echo IsChecked($user, "sniff");?> /></th></tr>
            <tr><th>Отладочная информация</th><th><input type="checkbox" name="debug" <?php echo IsChecked($user, "debug");?> /></th></tr>

<?php
    if ( IsBot ($user['player_id']) )
    {
?>
            <tr><th colspan=2><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=bot_stop&player_id=<?php echo $user['player_id'];?>" >[Остановить бота]</a></th></tr>
<?php
    }
    else
    {
?>
            <tr><th colspan=2><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=bot_start&player_id=<?php echo $user['player_id'];?>" >[Запустить бота]</a></th></tr>
<?php
    }
?>
        </table></th>

        <th valign=top><table>
            <tr><th>Сортировка планет</th><th>
   <select name="settings_sort">
    <option value="0" <?php echo IsSelected($user, "sortby", 0);?> >порядку колонизации</option>
    <option value="1" <?php echo IsSelected($user, "sortby", 1);?> >координатам</option>
    <option value="2" <?php echo IsSelected($user, "sortby", 2);?> >алфавиту</option>
   </select>
</th></tr>
            <tr><th>Порядок сортировки</th><th>
   <select name="settings_order">
     <option value="0" <?php echo IsSelected($user, "sortorder", 0);?>>по возрастанию</option>
     <option value="1" <?php echo IsSelected($user, "sortorder", 1);?>>по убыванию</option>
   </select>
</th></tr>
            <tr><th>Скин</th><th><input type=text name="dpath" maxlength="80" size="40" value="<?php echo $user['skin'];?>" /></th></tr>
            <tr><th>Использовать скин</th><th><input type="checkbox" name="design" <?php echo IsChecked($user, "useskin");?> /></th></tr>
            <tr><th>Декативировать проверку IP</th><th><input type="checkbox" name="deact_ip" <?php echo IsChecked($user, "deact_ip");?> /></th></tr>
            <tr><th>Количество зондов</th><th><input type="text" name="spio_anz" maxlength="2" size="2" value="<?php echo $user['maxspy'];?>" /></th></tr>
            <tr><th>Количество сообщений флота</th><th><input type="text" name="settings_fleetactions" maxlength="2" size="2" value="<?php echo $user['maxfleetmsg'];?>" /></th></tr>

            <tr><th colspan=2>&nbsp</th></tr>
            <tr><td class=c colspan=2>Статистика</td></tr>
            <tr><th>Очки (старые)</th><th><?php echo nicenum($user['oldscore1'] / 1000);?> / <?php echo nicenum($user['oldplace1']);?></th></tr>
            <tr><th>Флот (старые)</th><th><?php echo nicenum($user['oldscore2']);?> / <?php echo nicenum($user['oldplace2']);?></th></tr>
            <tr><th>Исследования (старые)</th><th><?php echo nicenum($user['oldscore3']);?> / <?php echo nicenum($user['oldplace3']);?></th></tr>
            <tr><th>Очки</th><th><?php echo nicenum($user['score1'] / 1000);?> / <?php echo nicenum($user['place1']);?></th></tr>
            <tr><th>Флот</th><th><?php echo nicenum($user['score2']);?> / <?php echo nicenum($user['place2']);?></th></tr>
            <tr><th>Исследования</th><th><?php echo nicenum($user['score3']);?> / <?php echo nicenum($user['place3']);?></th></tr>
            <tr><th>Дата старой статистики</th><th><?php echo date ("Y-m-d H:i:s", $user['scoredate']);?></th></tr>
            <tr><th colspan=2><a href="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=recalc_stats&player_id=<?php echo $user['player_id'];?>" >[Пересчитать статистику]</a></th></tr>

            <tr><th colspan=2>&nbsp</th></tr>
            <tr><td class=c colspan=2>Офицеры</td></tr>
            <tr><th colspan=2><table><tr>
<?php
    $oname = array ( 'Командир ОГейма', 'Адмирал', 'Инженер', 'Геолог', 'Технократ' );
    $odesc = array ( '', 
                             '<font size=1 color=skyblue>&amp;nbsp;Макс. кол-во флотов +2</font>', 
                             '<font size=1 color=skyblue>Сокращает вдвое потери в обороне+10% больше энергии</font>',
                             '<font size=1 color=skyblue>+10% доход от шахты</font>',
                             '<font size=1 color=skyblue>+2 уровень шпионажа, 25% меньше времени на исследования</font>' );
    $qname = array ( 'CommanderOff', 'AdmiralOff', 'EngineerOff', 'GeologeOff', 'TechnocrateOff' );
    $imgname = array ( 'commander', 'admiral', 'ingenieur', 'geologe', 'technokrat');

    $now = time ();

    foreach ( $qname as $i=>$qcmd )
    {
        $end = GetOfficerLeft ( $user['player_id'], $qname[$i] );

        $img = "";
        if ($end <= $now) {
            $img = "_un";
            $days = "";
        }
        else {
            $d = ($end - $now) / (60*60*24);
            if ( $d  > 0 ) $days = "&lt;font color=lime&gt;Активен&lt;/font&gt; ещё ".ceil($d)." д.";
        }

        echo "    <td align='center' width='35' class='header'>\n";
        echo "	<img border='0' src='img/".$imgname[$i]."_ikon".$img.".gif' width='32' height='32' alt='".$oname[$i]."'\n";
        echo "	onmouseover=\"return overlib('<center><font size=1 color=white><b>".$days."<br>".$oname[$i]."</font><br>".$odesc[$i]."<br></b></center>', LEFT, WIDTH, 150);\" onmouseout='return nd();'>\n";
        echo "    </td> <td><input type=\"text\" name=\"".$qname[$i]."\" size=\"3\" /></td>\n\n";
    }
?>
        </tr></table></th></tr>

            <tr><th colspan=2><i>Чтобы продлить офицера укажите необходимое количество дней в полях ввода</i></th></tr>

        </table></th>

        <th valign=top><table>
<?php
        foreach ( $resmap as $i=>$gid) {
            echo "<tr><th>".loca("NAME_$gid")."</th><th><input type=\"text\" size=3 name=\"r$gid\" value=\"".$user["r$gid"]."\" /></th></tr>\n";
        }
?>
        <tr><th>Найденная Тёмная Материя</th><th><input type="text" size=5 name="dmfree" value="<?php echo $user['dmfree'];?>" /></th></tr>
        <tr><th>Покупная Тёмная Материя</th><th><input type="text" size=5 name="dm" value="<?php echo $user['dm'];?>" /></th></tr>
        </table></th>
    <tr><th colspan=3><input type="submit" value="Сохранить" /></th></tr>
    </form>
    </table>

    <br>
    <table> 
    <form action="index.php?page=admin&session=<?php echo $session;?>&mode=Users&action=create_planet&player_id=<?php echo $user['player_id'];?>" method="POST" >
    <tr><td class=c colspan=20>Список планет</td></tr>
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
    <tr><td colspan=20> Координаты: <input name="g" size=2> <input name="s" size=2> <input name="p" size=2> <input type="submit" value="Создать планету"></td></tr>
    </form>
    </table>

    <br>
    <table>

<?php
        if ( $_GET['action'] === 'fleetlogs' ) {

            echo "<tr><td class=c colspan=12>Логи полётов</td></tr>\n";

            if ( $_GET['from'] == 1 ) $result = FleetlogsFromPlayer ( $user['player_id'], $FleetMissionList[$_GET['mission']] );
            else $result = FleetlogsToPlayer ( $user['player_id'], $FleetMissionList[$_GET['mission']] );

            $anz = $rows = dbrows ( $result );
            echo "<tr><td class=c>N</td> <td class=c>Таймер</td> <td class=c>Задание</td> <td class=c>Отправлен</td> <td class=c>Прибывает</td><td class=c>Время полёта</td> <td class=c>Старт</td> <td class=c>Цель</td> <td class=c>Флот</td> <td class=c>Ресурсы на планете</td> <td class=c>Груз</td> <td class=c>САБ</td> </tr>\n";
            $bxx = 1;
            while ($rows--)
            {
                $fleet_obj = dbarray ($result);

                $fleet_price = FleetPrice ( $fleet_obj );
                $points = $fleet_price['points'];
                $fpoints = $fleet_price['fpoints'];
                $style = "";
                if ( $points >= 100000000 ) {
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
    echo "<nobr>".BuildDurationFormat ($fleet_obj['flight_time']) . "</nobr><br>";
    echo "<nobr>".$fleet_obj['flight_time'] . " сек.</nobr>";
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
    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );
    foreach ($fleetmap as $i=>$gid) {
        $amount = $fleet_obj["ship".$gid];
        if ( $amount > 0 ) echo loca ("NAME_$gid") . ":" . nicenum($amount) . " ";
    }
?>
        </th>
        <th <?php echo $style;?> >
<?php
    $total = $fleet_obj['pm'] + $fleet_obj['pk'] + $fleet_obj['pd'];
    if ( $total > 0 ) {
        echo "М: " . nicenum ($fleet_obj['pm']) . "<br>" ;
        echo "К: " . nicenum ($fleet_obj['pk']) . "<br>" ;
        echo "Д: " . nicenum ($fleet_obj['pd']) ;
    }
    else echo "-";
?>
        </th>
        <th <?php echo $style;?> >
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

    <tr><td class=c colspan=3>Логи полётов</td></tr>
    <tr><td>Задание</td><td>от <?php echo $user['oname'];?></td><td>на <?php echo $user['oname'];?></td></tr>
    <tr><td>Все</td><td><?php echo LinkFleetsFrom($user,0);?></td><td><?php echo LinkFleetsTo($user,0);?></td></tr>
    <tr><td>Атака</td><td><?php echo LinkFleetsFrom($user,1);?></td><td><?php echo LinkFleetsTo($user,1);?></td></tr>
    <tr><td>Совместная атака</td><td><?php echo LinkFleetsFrom($user,2);?></td><td><?php echo LinkFleetsTo($user,2);?></td></tr>
    <tr><td>Транспорт</td><td><?php echo LinkFleetsFrom($user,3);?></td><td><?php echo LinkFleetsTo($user,3);?></td></tr>
    <tr><td>Оставить</td><td><?php echo LinkFleetsFrom($user,4);?></td><td><?php echo LinkFleetsTo($user,4);?></td></tr>
    <tr><td>Держаться</td><td><?php echo LinkFleetsFrom($user,5);?></td><td><?php echo LinkFleetsTo($user,5);?></td></tr>
    <tr><td>Шпионаж</td><td><?php echo LinkFleetsFrom($user,6);?></td><td><?php echo LinkFleetsTo($user,6);?></td></tr>
    <tr><td>Колонизировать</td><td><?php echo LinkFleetsFrom($user,7);?></td><td><?php echo LinkFleetsTo($user,7);?></td></tr>
    <tr><td>Переработать</td><td><?php echo LinkFleetsFrom($user,8);?></td><td><?php echo LinkFleetsTo($user,8);?></td></tr>
    <tr><td>Уничтожить</td><td><?php echo LinkFleetsFrom($user,9);?></td><td><?php echo LinkFleetsTo($user,9);?></td></tr>
    <tr><td>Экспедиция</td><td><?php echo LinkFleetsFrom($user,15);?></td><td><?php echo LinkFleetsTo($user,15);?></td></tr>
    <tr><td>Ракетная атака</td><td><?php echo LinkFleetsFrom($user,20);?></td><td><?php echo LinkFleetsTo($user,20);?></td></tr>
    <tr><td>Атака (САБ)</td><td><?php echo LinkFleetsFrom($user,21);?></td><td><?php echo LinkFleetsTo($user,21);?></td></tr>
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
        echo "Новые пользователи:<br>\n";
        echo "<table>\n";
        echo "<tr><td class=c>Дата регистрации</td><td class=c>Главная планета</td><td class=c>Имя игрока</td></tr>\n";
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
    <tr><td class=c>Активные за последние 24 часа (<?php echo $rows;?>)</td></tr>
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

    // Поиск пользователей
}

?>