<?php

// Админка : история действий игроков и операторов

function My_date_parse_from_format($format, $date) {
  $dMask = array(
    'H'=>'hour',
    'i'=>'minute',
    's'=>'second',
    'y'=>'year',
    'm'=>'month',
    'd'=>'day'
  );
  $format = preg_split('//', $format, -1, PREG_SPLIT_NO_EMPTY);  
  $date = preg_split('//', $date, -1, PREG_SPLIT_NO_EMPTY);  
  foreach ($date as $k => $v) {
    if ($dMask[$format[$k]]) $dt[$dMask[$format[$k]]] .= $v;
  }
  return $dt;
}

function Admin_UserLogs ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] >= 1 )
    {
        $name = $_POST['name'];
        $type = $_POST['type'];
        $period = intval($_POST['days'])*24*60*60 + intval($_POST['hours'])*60*60;
        $arr = My_date_parse_from_format ( "dd.mm.yyyy", $_POST['since']);
        $since = mktime ( 0, 0, 0, $arr['month'], $arr['day'], $arr['year'] );

        // Шаг 1 : найти всех пользователей неточным сравнением
        $users = array ();
        $query = "SELECT * FROM ".$db_prefix."users WHERE player_id > 0";
        $result = dbquery ($query);
        while ( $user = dbarray($result) ) {
            $percent = 0;
            similar_text ( mb_strtolower ($name), mb_strtolower ($user['oname']), &$percent );
            if ( $percent > 75 ) $users[] = $user;
        }

        // Шаг 2 : выбрать события указанной категории за промежуток времени
        $results = "";
        foreach ( $users as $i=>$user ) {
            if ( $type !== "ALL" ) $tstr = "AND type = '".$type."'";
            $query = "SELECT * FROM ".$db_prefix."userlogs WHERE owner_id = ".$user['player_id']." AND (date >= ".$since." AND date <= ".($since+$period).") ".$tstr." ORDER BY date ASC";
            $result = dbquery ($query);
            $count = dbrows ($result);
            $results .= "<h2>История $type игрока ".AdminUserName($user)." ($count)</h2>\n";
            $results .= "<table><tr><td class=\"c\">Дата</td><td class=\"c\">Тип</td><td class=\"c\">Действие</td></tr>\n";
            while ($log = dbarray ($result) ) {
                $results .= "<tr><td>".date ("d.m.Y H:i:s", $log['date'])."</td><td>".$log['type']."</td><td>".$log['text']."</td></tr>\n";
            }
            $results .= "</table>";
        }

    }

?>

<?=AdminPanel();?>

<?php

if ( method () === "GET" ) {
    $query = "SELECT * FROM ".$db_prefix."userlogs WHERE owner_id > 0 ORDER BY date DESC LIMIT 50";
    $result = dbquery ($query );
    echo "<h2>Последние действия игроков</h2>\n";
    echo "<table><tr><td class=\"c\">Дата</td><td class=\"c\">Игрок</td><td class=\"c\">Категория</td><td class=\"c\">Действие</td></tr>\n";
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

<?=$results;?>

<h2>История действий</h2>

<table>
<form action="index.php?page=admin&session=<?=$session;?>&mode=UserLogs" method="POST" >

<tr><td>Имя пользователя</td><td><input type="text" size=20 name="name"/> (можно примерно)</td></tr>
<tr><td>Категория</td><td>
<select name="type">
<option value="ALL">Все</option>
<option value="BUILD">Постройки / Снос</option>
<option value="RESEARCH">Исследования</option>
<option value="SHIPYARD">Постройка флота</option>
<option value="DEFENSE">Постройка обороны</option>
<option value="FLEET">Отправка флота</option>
<option value="PLANET">Настройки планеты</option>
<option value="SETTINGS">Изменение настроек аккаунта / РО</option>
<option value="OPER">Действия оператора</option>
</select>
</td></tr>
<tr><td>За период</td><td><input type="text" size=2 name="days" value="2"/> дн. <input type="text" size=2 name="hours"/> ч.</td></tr>
<tr><td>Начиная с</td><td><input type="text" size=20 name="since" value="<?=date("d.m.Y", time()-24*60*60);?>"/> ДД.ММ.ГГГ</td></tr>

<tr><td class="c" colspan=2> <input type="submit" value="Отправить" /></td></tr>

</form>
</table>

<?php    
}

?>