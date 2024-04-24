<?php

// Admin Area: logins

function Admin_Logins ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    AdminPanel();

    // POST request processing.
    if ( method () === "POST" )
    {

        if ( $_POST['name'] !== '' )        // By user name
        {
            $searchtext = $_POST['name'];
            $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '".$searchtext."%' LIMIT 25";
            $result = dbquery ( $query );
            $rows = dbrows ($result);
        
            echo "<table>";
            while ( $rows-- )
            {
                $user = dbarray ($result);

                $query = "SELECT * FROM ".$db_prefix."iplogs WHERE user_id = '".intval($user['player_id'])."' AND reg = 0";
                $result2 = dbquery ( $query );
                $rows2 = dbrows ($result2);
                while ($rows2--)
                {
                    $log = dbarray ($result2);
                    echo "<tr><td>";
                    echo date ("Y-m-d H:i:s", $log['date'] );
                    echo " " . $log['ip'];
                    echo " " . AdminUserName ($user);
                    echo "</td></tr>";
                }
            }
            echo "</table>";
        }

        if ( $_POST['id'] !== '' )        // By user ID
        {
            $query = "SELECT * FROM ".$db_prefix."iplogs WHERE user_id = '".intval($_POST['id'])."' AND reg = 0";
            $result = dbquery ( $query );
            $rows = dbrows ($result);
            echo "<table>";
            while ($rows--)
            {
                $log = dbarray ($result);
                $user = LoadUser ( $log['user_id'] );
                echo "<tr><td>";
                echo date ("Y-m-d H:i:s", $log['date'] );
                echo " " . $log['ip'];
                echo " " . AdminUserName ($user);
                echo "</td></tr>";
            }
            echo "</table>";
        }

        if ( $_POST['ip'] !== '' )        // By IP address
        {
            $query = "SELECT * FROM ".$db_prefix."iplogs WHERE ip = '".$_POST['ip']."' AND reg = 0";
            $result = dbquery ( $query );
            $rows = dbrows ($result);
            echo "<table>";
            while ($rows--)
            {
                $log = dbarray ($result);
                $user = LoadUser ( $log['user_id'] );
                echo "<tr><td>";
                echo date ("Y-m-d H:i:s", $log['date'] );
                echo " " . $log['ip'];
                echo " " . AdminUserName ($user);
                echo "</td></tr>";
            }
            echo "</table>";
        }

    }
?>

<form action="index.php?page=admin&session=<?=$session;?>&mode=Logins" method="POST">
<table>
<tr>
    <td class=d><?=loca("ADM_LOGINS_BY_NAME");?></td> <td> <input type=text size=20 name=name></td>
</tr>

<tr>
    <td class=d><?=loca("ADM_LOGINS_BY_ID");?></td> <td><input type=text size=20 name=id></td>
</tr>

<tr>
    <td class=d><?=loca("ADM_LOGINS_BY_IP");?></td> <td> <input type=text size=20 name=ip></td>
</tr>

<tr>   <td colspan=2 class=d><center><input type="submit" value="<?=loca("ADM_LOGINS_SUBMIT");?>"></center></td></tr>

</table>
</form>

<?php
}
?>