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
        }
    }

    // Обработка GET-запроса.
    if ( method () === "GET" && $GlobalUser['admin'] >= 2 )
    {
        $action = $_GET['action'];

        if ( $action === "remove_one" )
        {
        }

        if ( $action === "remove_date" )
        {
        }

    }

?>

<?=AdminPanel();?>

<?php

// Вывести список купонов.

$count = 15;
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
        echo "     <th>". ($entry['used'] ? "Да" : "Нет") ."</th>\n";
        echo "     <th>".$entry['uni_num']."</th>\n";
        echo "     <th>".$entry['user_name']."</th>\n";
        echo "     <th>Удалить</th>\n";
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
}
?>