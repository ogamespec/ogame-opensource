<?php

// Admin Area: logins

class Admin_Logins extends Page {

    private string $search_result = "";

    public function controller () : bool {
        global $db_prefix;

        // POST request processing.
        if ( method () === "POST" )
        {

            if ( $_POST['name'] !== '' )        // By user name
            {
                $searchtext = $_POST['name'];
                $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '".$searchtext."%' LIMIT 25";
                $result = dbquery ( $query );
                $rows = dbrows ($result);
            
                $this->search_result .= "<table>";
                while ( $rows-- )
                {
                    $user = dbarray ($result);

                    $query = "SELECT * FROM ".$db_prefix."iplogs WHERE user_id = '".intval($user['player_id'])."' AND reg = 0";
                    $result2 = dbquery ( $query );
                    $rows2 = dbrows ($result2);
                    while ($rows2--)
                    {
                        $log = dbarray ($result2);
                        $this->search_result .= "<tr><td>";
                        $this->search_result .= date ("Y-m-d H:i:s", $log['date'] );
                        $this->search_result .= " " . $log['ip'];
                        $this->search_result .= " " . AdminUserName ($user);
                        $this->search_result .= "</td></tr>";
                    }
                }
                $this->search_result .= "</table>";
            }

            if ( $_POST['id'] !== '' )        // By user ID
            {
                $query = "SELECT * FROM ".$db_prefix."iplogs WHERE user_id = '".intval($_POST['id'])."' AND reg = 0";
                $result = dbquery ( $query );
                $rows = dbrows ($result);
                $this->search_result .= "<table>";
                while ($rows--)
                {
                    $log = dbarray ($result);
                    $user = LoadUser ( $log['user_id'] );
                    $this->search_result .= "<tr><td>";
                    $this->search_result .= date ("Y-m-d H:i:s", $log['date'] );
                    $this->search_result .= " " . $log['ip'];
                    $this->search_result .= " " . AdminUserName ($user);
                    $this->search_result .= "</td></tr>";
                }
                $this->search_result .= "</table>";
            }

            if ( $_POST['ip'] !== '' )        // By IP address
            {
                $query = "SELECT * FROM ".$db_prefix."iplogs WHERE ip = '".$_POST['ip']."' AND reg = 0";
                $result = dbquery ( $query );
                $rows = dbrows ($result);
                $this->search_result .= "<table>";
                while ($rows--)
                {
                    $log = dbarray ($result);
                    $user = LoadUser ( $log['user_id'] );
                    $this->search_result .= "<tr><td>";
                    $this->search_result .= date ("Y-m-d H:i:s", $log['date'] );
                    $this->search_result .= " " . $log['ip'];
                    $this->search_result .= " " . AdminUserName ($user);
                    $this->search_result .= "</td></tr>";
                }
                $this->search_result .= "</table>";
            }

        }

        return true;
    }

    public function view () : void {
        global $session;
?>

<?=$this->search_result;?>

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
}

?>