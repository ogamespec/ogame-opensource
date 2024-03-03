<?php

// Админка: купоны

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
            if ( $code == NULL) $AdminError = "<font color=red>".loca("ADM_COUPON_ERROR")."</font>";
            else $AdminMessage = "<font color=lime>".va(loca("ADM_COUPON_SUCCESS"), $code)."</font>";
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
    if ( method () === "GET" && key_exists('action', $_GET) && $GlobalUser['admin'] >= 2 )
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
$from = 0;
if (key_exists('from', $_GET)) $from = intval ( $_GET['from'] );
$total = TotalCoupons ();

$result = EnumCoupons ($from, $count);
$rows = MDBRows ( $result );

?>
   <table border="0" cellpadding="2" cellspacing="1">
    <tr height="20">
     <td class="c"><?=loca("ADM_COUPON_CODE");?></td>
     <td class="c"><?=loca("DM");?></td>
     <td class="c"><?=loca("ADM_COUPON_ACTIVATED");?></td>
     <td class="c"><?=loca("ADM_COUPON_UNI");?></td>
     <td class="c"><?=loca("ADM_COUPON_NAME");?></td>
     <td class="c"><?=loca("ADM_COUPON_ACTION");?></td>
    </tr>
<?php

    while ( $rows-- )
    {
        $entry = MDBArray ( $result );
        echo "        <tr height=\"20\">\n";
        echo "     <th>".$entry['code']."</th>\n";
        echo "     <th>".nicenum($entry['amount'])."</th>\n";
        echo "     <th>". ($entry['used'] ? "<font color=red>".loca("ADM_COUPON_YES")."</font>" : "<font color=lime>".loca("ADM_COUPON_NO")."</font>") ."</th>\n";
        echo "     <th>". ($entry['used'] ? $entry['user_uni'] : '-' ) ."</th>\n";
        echo "     <th>".$entry['user_name']."</th>\n";
        echo "     <th><a href=\"index.php?page=admin&session=$session&mode=Coupons&action=remove_one&item_id=".$entry['id']."\">".loca("ADM_COUPON_DELETE")."</a></th>\n";
        echo "    </tr>\n";
    }

?>
       <tr>
   <th colspan="6">
<?php
    $url = "index.php?page=admin&session=$session&mode=Coupons&from";
    if ($from >= $count) echo "     <a href=\"".$url."=".($from-$count)."\"><< ".va(loca("ADM_COUPON_PREV"), $count)."</a>&nbsp;&nbsp;&nbsp;&nbsp;\n";
    if ($from < $total && ($from+$count < $total) ) echo "        <a href=\"".$url."=".($from+$count)."\">".va(loca("ADM_COUPON_NEXT"), $count)." >></a>\n";
?>
      </th>
   </tr>
   </table>


<table>
<tr><td class="c"><?=loca("ADM_COUPON_ADD_SINGLE");?></td></tr>
<tr><td>
<form action="index.php?page=admin&session=<?=$session;?>&mode=Coupons&action=add_one" method="POST">
<?=loca("DM");?> <input type="text" size="10" name="dm"> <input type="submit">
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
<tr><td class="c"colspan=2><?=loca("ADM_COUPON_ADD_PERIODIC");?></td></tr>
<tr><td><?=loca("ADM_COUPON_DAY");?> <input type="text" size="10" name="ddmm"></td><td><?=loca("ADM_COUPON_TIME");?> <input type="text" size="10" name="hhmm" value="10:00"></td></tr>
<tr><td><?=loca("ADM_COUPON_DM_AMOUNT");?></td><td><input type="text" size="10" name="darkmatter" value="100000"> </td></tr>
<tr><td><?=loca("ADM_COUPON_INACTIVE_DAYS");?></td><td><input type="text" size="10" name="inactive_days" value="7"> <?=loca("ADM_COUPON_DAYS");?></td></tr>
<tr><td><?=loca("ADM_COUPON_INGAME_DAYS");?></td><td><input type="text" size="10" name="ingame_days" value="365"> <?=loca("ADM_COUPON_DAYS");?></td></tr>
<tr><td><?=loca("ADM_COUPON_PERIOD");?></td><td><input type="text" size="10" name="periodic" value="365"> </td></tr>
<tr><td colspan=2><input type="submit"></td></tr>
</table>
</form>

<?php
}
?>