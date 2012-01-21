<?php

// ========================================================================================
// Баны.

function Admin_Bans ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 ) {

        print_r ( $_POST );

    }

?>

<?=AdminPanel();?>

<table>

<!-- Результаты поиска -->

<form action="index.php?page=admin&session=<?=$session;?>&mode=Bans&action=ban" method="POST" >

<tr> <td class=c>ID</td> <td class=c>Имя</td> <td class=c>Главная планета</td> <td class=c>Постоянный адрес</td> <td class=c>Временный адрес</td> <td class=c>IP адрес</td> <td class=c>Дата регистрации</td> </td>

<tr> <th><input type="checkbox" name="id[1]"/>1</th> <th><a>Legor</a></th> <th>[1:1:2] <a>Arakis</a></th> <th><a>legor@legor.com</a></th> <th><a>legor@legor.com</a></th> <th>127.0.0.1</th> <th>12-21-2010 12:12:12</th> </td>

<tr><td class=c >Действия</td></tr>
<tr> <td><input type="radio" name="banmode" value="0"> <font color=firebrick><b>Бан без РО</b></font> </td> <td><input name="days0" type="text" size="5"> дней  <input name="hours0" type="text" size="3"> часов</td> </tr>
<tr> <td> <input type="radio" name="banmode" value="1" checked > <font color=red><b>Бан с РО</b></font> <td><input name="days1" type="text" size="5"> дней  <input name="hours1" type="text" size="3"> часов</td> </tr>
<tr> <td> <input type="radio" name="banmode" value="2"> <font color=yellow><b>Блок атак</b></font></td> <td><input name="days2" type="text" size="5"> дней  <input name="hours2" type="text" size="3"> часов</td> </tr>
<tr><th ><input type="submit" value="Бан"></th></tr>
</form>
</table>

<?php

}

?>