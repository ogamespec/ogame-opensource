<?php

// Admin Area: Browser history (only for players with the `sniff` flag enabled).

class Admin_Browse extends Page {

    public function controller () : bool {
        return true;
    }

    public function view () : void {
    }
}

function Admin_Browse () : void
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $max_records = 50;
    $query = "SELECT * FROM ".$db_prefix."browse ORDER BY date DESC LIMIT $max_records";
    $result = dbquery ($query);

    AdminPanel();

    $rows = dbrows ($result);
    echo va(loca("ADM_BROWSE_TITLE"), $max_records) . "<br>";
    echo "<table>\n";
    while ($rows--) 
    {
        $log = dbarray ( $result );
        $user = LoadUser ( $log['owner_id'] );
?>
        <tr><td><table>
        <tr> <th> <?=$user['oname'];?> </th> <th> <?=$log['url'];?> </th> </tr>
        <tr> <th rowspan=2>
        <?=$log['method'];?><br>
        <?=date ("d M Y", $log['date']);?><br>
        <?=date ("H:i:s", $log['date']);?>
        </th> <th> <?php print_r( unserialize($log['getdata']) );?> </th> </tr>
        <tr> <th> <?php print_r( unserialize($log['postdata']) );?> </th> </tr>
        </table></td></tr>

<?php
    }
    echo "</table>\n";
}
?>