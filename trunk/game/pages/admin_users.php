<?php

// ========================================================================================
// Пользователи.

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

    $resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );
    
    $unitab = LoadUniverse ();
    $speed = $unitab['speed'];

    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 ) {
        $player_id = $_GET['player_id'];
        $action = $_GET['action'];
        $now = time();

        if ($action === "update")        // Обновить данные пользователя.
        {
            $query = "UPDATE ".$db_prefix."users SET ";

            foreach ( $resmap as $i=>$gid)
            {
                $query .= "r$gid = ".$_POST["r$gid"].", ";
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

            $query .= "sortby = '".$_POST['settings_sort']."', ";
            $query .= "sortorder = '".$_POST['settings_order']."', ";
            $query .= "skin = '".$_POST['dpath']."', ";
            $query .= "useskin = ".($_POST['design']==="on"?1:0).", ";
            $query .= "deact_ip = ".($_POST['deact_ip']==="on"?1:0).", ";
            $query .= "maxspy = '".$_POST['spio_anz']."', ";
            $query .= "maxfleetmsg = '".$_POST['settings_fleetactions']."', ";
            $query .= "lang = '".$_POST['lang']."' ";

            $query .= " WHERE player_id=$player_id;";
            dbquery ($query);
        }

        if ($action === "create_planet")        // Создать планету
        {
            $g = $_POST['g'];    if ($g === "" ) $g = 1;
            $s = $_POST['s'];    if ($s === "" ) $s = 1;
            $p = $_POST['p'];    if ($p === "" ) $p = 1;
            if ( ! HasPlanet ( $g, $s, $p ) ) CreatePlanet ($g, $s, $p, $_GET['player_id'] );
        }
    }

    // Обработка GET-запроса.
    if ( method () === "GET" && $GlobalUser['admin'] >= 2 ) {
        $player_id = $_GET['player_id'];
        $action = $_GET['action'];
        $now = time();

        if ( $action === "recalc_stats" )    // Пересчитать статистику
        {
            RecalcStats ($player_id);
            RecalcRanks ();
        }
    }

    if ( key_exists("player_id", $_GET) ) {        // Информация об игроке
        $user = LoadUser ( $_GET['player_id'] );
?>
    <table>
    <form action="index.php?page=admin&session=<?=$session;?>&mode=Users&action=update&player_id=<?=$user['player_id'];?>" method="POST" >
    <tr><td class=c><?=$user['oname'];?></td><td class=c>Настройки</td><td class=c>Исследования</td></tr>

        <th valign=top><table>
            <tr><th>ID</th><th><?=$user['player_id'];?></th></tr>
            <tr><th>Дата регистрации</th><th><?=date ("Y-m-d H:i:s", $user['regdate']);?></th></tr>
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
            <tr><th>Постоянный адрес</th><th><input type="text" name="pemail" maxlength="100" size="20" value="<?=$user['pemail'];?>" /></th></tr>
            <tr><th>Временный адрес</th><th><input type="text" name="email" maxlength="100" size="20" value="<?=$user['email'];?>" /></th></tr>
            <tr><th>Удалить игрока</th><th><input type="checkbox" name="deaktjava"  <?=IsChecked($user, "disable");?>/>
      <?php
    if ($user['disable']) echo date ("Y-m-d H:i:s", $user['disable_until']);
?></th></tr>
            <tr><th>Режим отпуска</th><th><input type="checkbox" name="vacation"  <?=IsChecked($user, "vacation");?>/>
      <?php
    if ($user['vacation']) echo date ("Y-m-d H:i:s", $user['vacation_until']);
?></th></tr>
            <tr><th>Заблокирован</th><th><input type="checkbox" name="banned"  <?=IsChecked($user, "banned");?>/>
      <?php
    if ($user['banned']) echo date ("Y-m-d H:i:s", $user['banned_until']);
?></th></tr>
            <tr><th>Бан атак</th><th><input type="checkbox" name="noattack"  <?=IsChecked($user, "noattack");?>/>
      <?php
    if ($user['noattack']) echo date ("Y-m-d H:i:s", $user['noattack_until']);
?></th></tr>
            <tr><th>Последний вход</th><th><?=date ("Y-m-d H:i:s", $user['lastlogin']);?></th></tr>
            <tr><th>Активность</th><th>
<?php
    $now = time ();
    echo date ("Y-m-d H:i:s", $user['lastclick']);
    if ( ($now - $user['lastclick']) < 60*60 ) echo " (".floor(($now - $user['lastclick'])/60)." min)";
?>
</th></tr>
            <tr><th>IP адрес</th><th><?=$user['ip_addr'];?></th></tr>
            <tr><th>Активирован</th><th><input type="checkbox" name="validated" <?=IsChecked($user, "validated");?> /></th></tr>
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
     <option value="0" <?=IsSelected($user, "admin", 0);?>>Пользователь</option>
     <option value="1" <?=IsSelected($user, "admin", 1);?>>Оператор</option>
     <option value="2" <?=IsSelected($user, "admin", 2);?>>Администратор</option>
   </select>
</th></tr>
            <tr><th>Включить слежение</th><th><input type="checkbox" name="sniff" <?=IsChecked($user, "sniff");?> /></th></tr>
            <tr><th>Отладочная информация</th><th><input type="checkbox" name="debug" <?=IsChecked($user, "debug");?> /></th></tr>
        </table></th>

        <th valign=top><table>
            <tr><th>Сортировка планет</th><th>
   <select name="settings_sort">
    <option value="0" <?=IsSelected($user, "sortby", 0);?> >порядку колонизации</option>
    <option value="1" <?=IsSelected($user, "sortby", 1);?> >координатам</option>
    <option value="2" <?=IsSelected($user, "sortby", 2);?> >алфавиту</option>
   </select>
</th></tr>
            <tr><th>Порядок сортировки</th><th>
   <select name="settings_order">
     <option value="0" <?=IsSelected($user, "sortorder", 0);?>>по возрастанию</option>
     <option value="1" <?=IsSelected($user, "sortorder", 1);?>>по убыванию</option>
   </select>
</th></tr>
            <tr><th>Скин</th><th><input type=text name="dpath" maxlength="80" size="40" value="<?=$user['skin'];?>" /></th></tr>
            <tr><th>Использовать скин</th><th><input type="checkbox" name="design" <?=IsChecked($user, "useskin");?> /></th></tr>
            <tr><th>Декативировать проверку IP</th><th><input type="checkbox" name="deact_ip" <?=IsChecked($user, "deact_ip");?> /></th></tr>
            <tr><th>Количество зондов</th><th><input type="text" name="spio_anz" maxlength="2" size="2" value="<?=$user['maxspy'];?>" /></th></tr>
            <tr><th>Количество сообщений флота</th><th><input type="text" name="settings_fleetactions" maxlength="2" size="2" value="<?=$user['maxfleetmsg'];?>" /></th></tr>
            <tr><th>Язык интерфейса</th><th>
   <select name="lang">
<?php
    global $Languages;
    foreach ( $Languages as $lang_id=>$lang_name ) {
        echo "    <option value=\"".$lang_id."\" " . IsSelected($user, "lang", $lang_id)." >$lang_name</option>\n";
    }
?>
   </select>
</th></tr>
            <tr><th colspan=2>&nbsp</th></tr>
            <tr><td class=c colspan=2>Статистика</td></tr>
            <tr><th>Очки (старые)</th><th><?=nicenum($user['oldscore1'] / 1000);?> / <?=nicenum($user['oldplace1']);?></th></tr>
            <tr><th>Флот (старые)</th><th><?=nicenum($user['oldscore2']);?> / <?=nicenum($user['oldplace2']);?></th></tr>
            <tr><th>Исследования (старые)</th><th><?=nicenum($user['oldscore3']);?> / <?=nicenum($user['oldplace3']);?></th></tr>
            <tr><th>Очки</th><th><?=nicenum($user['score1'] / 1000);?> / <?=nicenum($user['place1']);?></th></tr>
            <tr><th>Флот</th><th><?=nicenum($user['score2']);?> / <?=nicenum($user['place2']);?></th></tr>
            <tr><th>Исследования</th><th><?=nicenum($user['score3']);?> / <?=nicenum($user['place3']);?></th></tr>
            <tr><th>Дата старой статистики</th><th><?=date ("Y-m-d H:i:s", $user['scoredate']);?></th></tr>
            <tr><th colspan=2><a href="index.php?page=admin&session=<?=$session;?>&mode=Users&action=recalc_stats&player_id=<?=$user['player_id'];?>" >Пересчитать статистику</a></th></tr>
        </table></th>

        <th valign=top><table>
<?php
        foreach ( $resmap as $i=>$gid) {
            echo "<tr><th>".loca("NAME_$gid")."</th><th><input type=\"text\" size=3 name=\"r$gid\" value=\"".$user["r$gid"]."\" /></th></tr>\n";
        }
?>
        </table></th>
    <tr><th colspan=3><input type="submit" value="Сохранить" /></th></tr>
    </form>
    </table>

    <br>
    <table> 
    <form action="index.php?page=admin&session=<?=$session;?>&mode=Users&action=create_planet&player_id=<?=$user['player_id'];?>" method="POST" >
    <tr><td class=c colspan=20>Список планет</td></tr>
    <tr>
<?php
    $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".$_GET['player_id']."' ORDER BY g ASC, s ASC, p ASC, type DESC";
    $result = dbquery ($query);
    $rows = dbrows ($result);
    $counter = 0;
    while ($rows--)
    {
        $p = dbarray ($result);
?>
    <td> <img src="<?=GetPlanetSmallImage( "../evolution/", $p['type']);?>" width="32px" height="32px"></td>
    <td> <a href="index.php?page=admin&session=<?=$session;?>&mode=Planets&cp=<?=$p['planet_id'];?>"> <?=$p['name'];?> </a>
            [<a href="index.php?page=galaxy&session=<?=$session;?>&galaxy=<?=$p['g'];?>&system=<?=$p['s'];?>"><?=$p['g'];?>:<?=$p['s'];?>:<?=$p['p'];?></a>] </td>
<?
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

<?php
    }
    else {
        $query = "SELECT * FROM ".$db_prefix."users ORDER BY regdate DESC LIMIT 25";
        $result = dbquery ($query);

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
    <tr><td class=c>Активные за последние 24 часа (<?=$rows;?>)</td></tr>
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