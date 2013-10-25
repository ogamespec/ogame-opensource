<?php

// Админка : купоны

function Admin_Coupons ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;
    global $AdminMessage, $AdminError;

    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 )
    {
        $action = $_GET['action'];

        if ( $action === "add_one" )
        {
            $code = AddCoupon ( intval ( $_POST['dm'] ) );
            if ( $code == NULL) $AdminError = "<font color=red>Ошибка добавления купона!</font>";
            else $AdminMessage = "<font color=lime>Купон добавлен : $code</font>";
        }

        if ( $action === "add_date" )
        {
            $ddmm = explode ( '.', $_POST['ddmm'] );
            $hhmm = explode ( ':', $_POST['hhmm'] );

            $now = time ();
            $end = mktime ( $hhmm[0], $hhmm[1], 0, $ddmm[1], $ddmm[0] );

            $inactive_days = intval ( $_POST['inactive_days'] );
            $ingame_days = intval ( $_POST['ingame_days'] );
            $darkmatter = intval ( $_POST['darkmatter'] );
            $periodic = intval ( $_POST['periodic'] );

            $queue = array ( null, 99999, "Coupon", $darkmatter, ($inactive_days << 16) | $ingame_days, $periodic, $now, $end, 520 );
            AddDBRow ( $queue, "queue" );
        }
    }

    // Обработка GET-запроса.
    if ( method () === "GET" && $GlobalUser['admin'] >= 2 )
    {
        $action = $_GET['action'];

        if ( $action === "remove_one" ) DeleteCoupon ( $_GET['item_id'] );

        if ( $action === "remove_date" ) RemoveQueue ( $_GET['item_id'] );

    }

?>

<?=AdminPanel();?>

<?php

// Вывести список купонов.

$count = 15;        // количество купонов на страницу
$from = intval ( $_GET['from'] );
$total = TotalCoupons ();

$result = EnumCoupons ($from, $count);
$rows = MDBRows ( $result );

?>
   <table border="0" cellpadding="2" cellspacing="1">
    <tr height="20">
     <td class="c">Код</td>
     <td class="c">Тёмная материя</td>
     <td class="c">Активирован</td>
     <td class="c">Вселенная</td>
     <td class="c">Имя игрока</td>
     <td class="c">Действие</td>
    </tr>
<?php

    while ( $rows-- )
    {
        $entry = MDBArray ( $result );
        echo "        <tr height=\"20\">\n";
        echo "     <th>".$entry['code']."</th>\n";
        echo "     <th>".nicenum($entry['amount'])."</th>\n";
        echo "     <th>". ($entry['used'] ? "<font color=red>Да</font>" : "<font color=lime>Нет</font>") ."</th>\n";
        echo "     <th>". ($entry['used'] ? $entry['user_uni'] : '-' ) ."</th>\n";
        echo "     <th>".$entry['user_name']."</th>\n";
        echo "     <th><a href=\"index.php?page=admin&session=$session&mode=Coupons&action=remove_one&item_id=".$entry['id']."\">Удалить</a></th>\n";
        echo "    </tr>\n";
    }

?>
       <tr>
   <th colspan="6">
<?php
    $url = "index.php?page=admin&session=$session&mode=Coupons&from";
    if ($from >= $count) echo "     <a href=\"".$url."=".($from-$count)."\"><< Предыдущие $count</a>&nbsp;&nbsp;&nbsp;&nbsp;\n";
    if ($from < $total && ($from+$count < $total) ) echo "        <a href=\"".$url."=".($from+$count)."\">Следующие $count >></a>\n";
?>
      </th>
   </tr>
   </table>


<table>
<tr><td class="c">Добавить один купон</td></tr>
<tr><td>
<form action="index.php?page=admin&session=<?=$session;?>&mode=Coupons&action=add_one" method="POST">
Темная материя <input type="text" size="10" name="dm"> <input type="submit">
</form>
</td></tr>
</table>

<?php

    // Вывести активные задания начисления купонов.

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'Coupon' ORDER BY end ASC";
    $result = dbquery ( $query );
    while ( $queue = dbarray ($result) ) 
    {
        print_r ( $queue );
        echo "<br>";
    }

?>

<form action="index.php?page=admin&session=<?=$session;?>&mode=Coupons&action=add_date" method="POST">
<table>
<tr><td class="c"colspan=2>Купоны по праздникам</td></tr>
<tr><td>День в формате ДД.ММ <input type="text" size="10" name="ddmm"></td><td>Время в формате ЧЧ:ММ <input type="text" size="10" name="hhmm" value="10:00"></td></tr>
<tr><td>Темной материи на купон</td><td><input type="text" size="10" name="darkmatter" value="100000"> </td></tr>
<tr><td>Отправлять игрокам неактивным не менее</td><td><input type="text" size="10" name="inactive_days" value="7"> дней</td></tr>
<tr><td>Игроки должны играть не менее</td><td><input type="text" size="10" name="ingame_days" value="365"> дней</td></tr>
<tr><td>Периодичность дней (0-без периодичности)</td><td><input type="text" size="10" name="periodic" value="365"> </td></tr>
<tr><td colspan=2><input type="submit"></td></tr>
</table>
</form>


<?php
}
?>