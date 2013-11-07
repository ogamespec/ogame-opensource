<?php

// ========================================================================================
// Баны.

function Admin_Bans ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    // Обработка POST-запроса.
    if ( method () === "POST" && $GlobalUser['admin'] >= 1 )
    {

        if ( $_GET['action'] === 'search' )    {        // Результаты поиска

            switch ( intval ( $_POST['searchby'] ) )
            {
                case 0 :        // Забаненных с РО
                    $query = "SELECT * FROM ".$db_prefix."users WHERE banned = 1 AND vacation = 1";
                    break;
                case 1 :        // Забаненных без РО
                    $query = "SELECT * FROM ".$db_prefix."users WHERE banned = 1 AND vacation = 0";
                    break;
                case 2 :        // Блокировка атак
                    $query = "SELECT * FROM ".$db_prefix."users WHERE noattack = 1";
                    break;
                case 3 :        // Зарегистрированных недавно (дней)
                    $when = time () - intval($_POST['text']) * 24 * 60 * 60;
                    $query = "SELECT * FROM ".$db_prefix."users WHERE regdate >= $when";
                    break;
                case 4 :        // Имя пользователя (примерное)
                    $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '".$_POST['text']."%' ";
                    break;
                case 5 :        // Тег альянса
                    $query = "SELECT ally_id FROM ".$db_prefix."ally WHERE tag LIKE '%".$_POST['text']."%' ";
                    $query = "SELECT * FROM ".$db_prefix."users WHERE ally_id = ANY ($query) ";
                    break;
                case 6 :        // Одинаковый адрес email
                    $query = "SELECT * FROM ".$db_prefix."users WHERE email = LIKE '%".$_POST['text']."%' OR pemail = LIKE '%".$_POST['text']."%' ";
                    break;
                case 7 :        // Одинаковый IP
                    $query = "SELECT * FROM ".$db_prefix."users AS t1 INNER JOIN ( 
SELECT ip_addr,COUNT(*) FROM ".$db_prefix."users GROUP BY ip_addr HAVING COUNT(*)>1) as t2 
ON t1.ip_addr = t2.ip_addr ORDER BY t1.ip_addr ASC, t1.name ASC";
                    break;
                default : $query = '';
            }

            $result = dbquery ( $query );
            $rows0 = $rows = dbrows ($result );

?>
<?php echo AdminPanel();?>

<script>

function SetClearCheckbox (status)
{
    var theForm = document.getElementById('banform');
    for (i=0,n=theForm.elements.length;i<n;i++)
    {
        if (theForm.elements[i].className.indexOf('ids') !=-1) {
            theForm.elements[i].checked = status;
        }
    }
}

</script>

<!-- Результаты поиска -->
<table>
<form id="banform" action="index.php?page=admin&session=<?php echo $session;?>&mode=Bans&action=ban" method="POST" >

<tr> <td class=c> <input type="checkbox" onclick="SetClearCheckbox(this.checked);"> ID</td> <td class=c>Имя</td> <td class=c>Главная планета</td> <td class=c>Постоянный адрес</td> <td class=c>Временный адрес</td> <td class=c>IP адрес</td> <td class=c>Дата регистрации</td> </td>
<?php

            if ( $rows == 0 ) echo "<tr><td colspan=7>Ничего не найдено. <a href=\"index.php?page=admin&session=$session&mode=Bans\">Назад</a></td></tr>";

            while ( $rows-- ) {
                $user = dbarray ( $result );
                $hp = GetPlanet ( $user['hplanetid'] );
?>
<tr> <th><input type="checkbox" name="id[<?php echo $user['player_id'];?>]" class="ids"/><?php echo $user['player_id'];?></th> 
        <th><a><?php echo AdminUserName($user);?></a></th> 
        <th><?php echo AdminPlanetCoord($hp);?> <?php echo AdminPlanetName($hp);?></th> 
        <th><a><?php echo $user['pemail'];?></a></th> 
        <th><a><?php echo $user['email'];?></a></th> 
        <th><?php echo $user['ip_addr'];?></th> 
        <th><?php echo date ("m-d-Y H:i:s", $user['regdate']);?></th> </tr>
<?php
            } // while

            if ( $rows0 > 0 )
            {
?>
<tr><td class=c colspan=7>Действия</td></tr>
<tr> 
    <td colspan=6>
        <input type="radio" name="banmode" value="0"> <font color=firebrick><b>Бан без РО</b></font>
         <input type="radio" name="banmode" value="1" checked > <font color=red><b>Бан с РО</b></font> 
         <input type="radio" name="banmode" value="2"> <font color=yellow><b>Блок атак</b></font>
         <input type="radio" name="banmode" value="3"> <font color=lime><b>Разбанить</b></font>
         <input type="radio" name="banmode" value="4"> <font color=lime><b>Разрешить атаки</b></font>
    </td>
    <td><input name="days" type="text" size="5"> дней  <input name="hours" type="text" size="3"> часов</td> 

</tr>
<tr><th colspan=6> Причина: <textarea cols=40 rows=4 name="reason"><?php echo "[url=mailto:".$GlobalUser['pemail']."]контакт[/url]";?></textarea></th><th><input type="submit" value="Отправить"></th></tr>
</form>
</table>
<?php

            }
            
            die ();
        }

        if ( $_GET['action'] === 'ban' )    {        // Забанить / разбанить

            $now = time();

            $reason = str_replace ( '\"', "&quot;", bb($_POST['reason']) );
            $reason = str_replace ( '\'', "&rsquo;", $reason );
            $reason = str_replace ( '\`', "&lsquo;", $reason );

            $seconds = intval ( $_POST['days'] ) * 24 * 60 * 60 + intval ( $_POST['hours'] ) * 60 * 60;
            foreach ( $_POST['id'] as $player_id => $checked )
            {
                $user = LoadUser ( $player_id );
                switch ( intval ( $_POST['banmode'] ) )
                {
                    case 0 :
                        // Добавить пользователя на столб позора
                        $entry = array( null, $GlobalUser['oname'], $user['oname'], $GlobalUser['player_id'], $user['player_id'], $now, $now + $seconds, $reason );
                        AddDBRow ( $entry, "pranger" );
                        BanUser ( $player_id, $seconds, 0 ); break;
                    case 1 :
                        // Добавить пользователя на столб позора
                        $entry = array( null, $GlobalUser['oname'], $user['oname'], $GlobalUser['player_id'], $user['player_id'], $now, $now + $seconds, $reason );
                        AddDBRow ( $entry, "pranger" );
                        BanUser ( $player_id, $seconds, 1 ); break;
                    case 2 :
                        // Добавить пользователя на столб позора
                        $entry = array( null, $GlobalUser['oname'], $user['oname'], $GlobalUser['player_id'], $user['player_id'], $now, $now + $seconds, $reason );
                        AddDBRow ( $entry, "pranger" );
                        BanUserAttacks ( $player_id, $seconds ); break;
                    case 3 : UnbanUser ( $player_id ); break;
                    case 4 : UnbanUserAttacks ( $player_id ); break;
                }
            }    // for

        }
    }

?>

<!-- Форма поиска -->

<?php echo AdminPanel();?>

<table>
<form action="index.php?page=admin&session=<?php echo $session;?>&mode=Bans&action=search" method="POST" >

<tr><td class="c" colspan=2>Найти пользователей</td></tr>
<tr>
    <td>
            <select name="searchby">
                <option value="0">Забаненных с РО</option>
                <option value="1">Забаненных без РО</option>
                <option value="2">Блокировка атак</option>
                <option value="3">Зарегистрированных недавно (дней)</option>
                <option value="4">Имя пользователя (примерное)</option>
                <option value="5">Тег альянса</option>
                <option value="6">Одинаковый адрес email</option>
                <option value="7">Одинаковый IP</option>
            </select>
    </td>
    <td> <input type="text" name="text" size=20></td>
</tr>
<tr><td class="c" colspan=2> <input type="submit" value="Отправить" /></td></tr>

</form>
</table>

<?php

}

?>