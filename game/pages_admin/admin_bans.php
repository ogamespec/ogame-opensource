<?php

// Admin Area: Bans

class Admin_Bans extends Page {

    private bool $search_results = false;

    private mixed $result = null;
    private int $rows0 = 0;
    private int $rows = 0;

    public function controller () : bool {
        global $db_prefix;
        global $now;
        global $session;
        global $GlobalUser;

        // POST request processing.
        if ( method () === "POST" && $GlobalUser['admin'] >= 1 )
        {

            if ( $_GET['action'] === 'search' )    {        // Search results

                switch ( intval ( $_POST['searchby'] ) )
                {
                    case 0 :        // Banned with VM
                        $query = "SELECT * FROM ".$db_prefix."users WHERE banned = 1 AND vacation = 1";
                        break;
                    case 1 :        // Banned without VM
                        $query = "SELECT * FROM ".$db_prefix."users WHERE banned = 1 AND vacation = 0";
                        break;
                    case 2 :        // Attack ban
                        $query = "SELECT * FROM ".$db_prefix."users WHERE noattack = 1";
                        break;
                    case 3 :        // Recently registered (days)
                        $when = time () - intval($_POST['text']) * 24 * 60 * 60;
                        $query = "SELECT * FROM ".$db_prefix."users WHERE regdate >= $when";
                        break;
                    case 4 :        // User name (approximate)
                        $query = "SELECT * FROM ".$db_prefix."users WHERE oname LIKE '".$_POST['text']."%' ";
                        break;
                    case 5 :        // Alliance Tag
                        $query = "SELECT ally_id FROM ".$db_prefix."ally WHERE tag LIKE '%".$_POST['text']."%' ";
                        $query = "SELECT * FROM ".$db_prefix."users WHERE ally_id = ANY ($query) ";
                        break;
                    case 6 :        // Same email address
                        $query = "SELECT * FROM ".$db_prefix."users WHERE email = LIKE '%".$_POST['text']."%' OR pemail = LIKE '%".$_POST['text']."%' ";
                        break;
                    case 7 :        // Same IP
                        $query = "SELECT * FROM ".$db_prefix."users AS t1 INNER JOIN ( 
    SELECT ip_addr,COUNT(*) FROM ".$db_prefix."users GROUP BY ip_addr HAVING COUNT(*)>1) as t2 
    ON t1.ip_addr = t2.ip_addr ORDER BY t1.ip_addr ASC, t1.name ASC";
                        break;
                    default : $query = '';
                }

                $this->result = dbquery ( $query );
                $this->rows0 = $this->rows = dbrows ($this->result );
                $this->search_results = true;
            }

            else if ( $_GET['action'] === 'ban' )    {        // Ban / unban

                $reason = str_replace ( '\"', "&quot;", bb($_POST['reason']) );
                $reason = str_replace ( '\'', "&rsquo;", $reason );
                $reason = str_replace ( '\`', "&lsquo;", $reason );

                $ids = array();
                if ( isset($_POST['id']) ) $ids = $_POST['id'];

                $seconds = intval ( $_POST['days'] ) * 24 * 60 * 60 + intval ( $_POST['hours'] ) * 60 * 60;
                foreach ( $ids as $player_id => $checked )
                {
                    $user = LoadUser ( $player_id );
                    switch ( intval ( $_POST['banmode'] ) )
                    {
                        case 0 :
                            // Add a user to the Pillar of Shame
                            $entry = array( 'admin_name' => $GlobalUser['oname'], 'user_name' => $user['oname'], 'admin_id' => $GlobalUser['player_id'], 'user_id' => $user['player_id'], 'ban_when' => $now, 'ban_until' => $now + $seconds, 'reason' => $reason );
                            AddDBRow ( $entry, "pranger" );
                            BanUser ( $player_id, $seconds, false ); break;
                        case 1 :
                            // Add a user to the Pillar of Shame
                            $entry = array( 'admin_name' => $GlobalUser['oname'], 'user_name' => $user['oname'], 'admin_id' => $GlobalUser['player_id'], 'user_id' => $user['player_id'], 'ban_when' => $now, 'ban_until' => $now + $seconds, 'reason' => $reason );
                            AddDBRow ( $entry, "pranger" );
                            BanUser ( $player_id, $seconds, true ); break;
                        case 2 :
                            // Add a user to the Pillar of Shame
                            $entry = array( 'admin_name' => $GlobalUser['oname'], 'user_name' => $user['oname'], 'admin_id' => $GlobalUser['player_id'], 'user_id' => $user['player_id'], 'ban_when' => $now, 'ban_until' => $now + $seconds, 'reason' => $reason );
                            AddDBRow ( $entry, "pranger" );
                            BanUserAttacks ( $player_id, $seconds ); break;
                        case 3 : UnbanUser ( $player_id ); break;
                        case 4 : UnbanUserAttacks ( $player_id ); break;
                    }
                }    // for

            } // ban

        }

        return true;
    } // controller

    public function view () : void {
        global $session;
        global $GlobalUser;

        if (!$this->search_results) {

?>

<!-- Search form -->

<table>
<form action="index.php?page=admin&session=<?php echo $session;?>&mode=Bans&action=search" method="POST" >

<tr><td class="c" colspan=2><?=loca("ADM_BANS_FIND");?></td></tr>
<tr>
    <td>
            <select name="searchby">
                <option value="0"><?=loca("ADM_BANS_FIND_0");?></option>
                <option value="1"><?=loca("ADM_BANS_FIND_1");?></option>
                <option value="2"><?=loca("ADM_BANS_FIND_2");?></option>
                <option value="3"><?=loca("ADM_BANS_FIND_3");?></option>
                <option value="4"><?=loca("ADM_BANS_FIND_4");?></option>
                <option value="5"><?=loca("ADM_BANS_FIND_5");?></option>
                <option value="6"><?=loca("ADM_BANS_FIND_6");?></option>
                <option value="7"><?=loca("ADM_BANS_FIND_7");?></option>
            </select>
    </td>
    <td> <input type="text" name="text" size=20></td>
</tr>
<tr><td class="c" colspan=2> <input type="submit" value="<?=loca("ADM_BANS_SUBMIT");?>" /></td></tr>

</form>
</table>

<?php
        } else {
?>

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

<!-- Search results -->
<table>
<form id="banform" action="index.php?page=admin&session=<?php echo $session;?>&mode=Bans&action=ban" method="POST" >

<tr> <td class=c> <input type="checkbox" onclick="SetClearCheckbox(this.checked);"> <?=loca("ADM_BANS_ID");?></td> <td class=c><?=loca("ADM_BANS_NAME");?></td> <td class=c><?=loca("ADM_BANS_HOMEPLANET");?></td> <td class=c><?=loca("ADM_BANS_PEMAIL");?></td> <td class=c><?=loca("ADM_BANS_EMAIL");?></td> <td class=c><?=loca("ADM_BANS_IP");?></td> <td class=c><?=loca("ADM_BANS_REGDATE");?></td> </td>
<?php

            if ( $this->rows == 0 ) echo "<tr><td colspan=7>".loca("ADM_BANS_NOT_FOUND")." <a href=\"index.php?page=admin&session=$session&mode=Bans\">".loca("ADM_BANS_BACK")."</a></td></tr>";

            while ( $this->rows-- ) {
                $user = dbarray ( $this->result );
                $hp = LoadPlanetById ( $user['hplanetid'] );
?>
<tr> <th><input type="checkbox" name="id[<?php echo $user['player_id'];?>]" class="ids"/><?php echo $user['player_id'];?></th> 
        <th><a><?php echo AdminUserName($user);?></a></th> 
        <th><?php echo AdminPlanetCoord($hp);?> <?php echo AdminPlanetName($hp['planet_id']);?></th> 
        <th><a><?php echo $user['pemail'];?></a></th> 
        <th><a><?php echo $user['email'];?></a></th> 
        <th><?php echo $user['ip_addr'];?></th> 
        <th><?php echo date ("m-d-Y H:i:s", $user['regdate']);?></th> </tr>
<?php
            } // while

            if ( $this->rows0 > 0 )
            {
?>
<tr><td class=c colspan=7><?=loca("ADM_BANS_ACTIONS");?></td></tr>
<tr> 
    <td colspan=6>
        <input type="radio" name="banmode" value="0"> <font color=firebrick><b><?=loca("ADM_BANS_BAN_WITHOUT_VACATION");?></b></font>
         <input type="radio" name="banmode" value="1" checked > <font color=red><b><?=loca("ADM_BANS_BAN_WITH_VACATION");?></b></font> 
         <input type="radio" name="banmode" value="2"> <font color=yellow><b><?=loca("ADM_BANS_BAN_ATTACKS");?></b></font>
         <input type="radio" name="banmode" value="3"> <font color=lime><b><?=loca("ADM_BANS_UNBAN");?></b></font>
         <input type="radio" name="banmode" value="4"> <font color=lime><b><?=loca("ADM_BANS_UNBAN_ATTACKS");?></b></font>
    </td>
    <td><input name="days" type="text" size="5"> <?=loca("ADM_BANS_DAYS");?>  <input name="hours" type="text" size="3"> <?=loca("ADM_BANS_HOURS");?></td> 

</tr>
<tr><th colspan=6> <?=loca("ADM_BANS_REASON");?> <textarea cols=40 rows=4 name="reason"><?php echo "[url=mailto:".$GlobalUser['pemail']."]".loca("ADM_BANS_CONTACT")."[/url]";?></textarea></th><th><input type="submit" value="<?=loca("ADM_BANS_SUBMIT");?>"></th></tr>
</form>
</table>
<?php
            } // if rows0

        } // search_results

    } // view
}

?>