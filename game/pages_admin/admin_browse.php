<?php

// Admin Area: Browser history (only for players with the `sniff` flag enabled).

class Admin_Browse extends Page {

    public function controller () : bool {
        global $GlobalUser;

        // The sniffed request log may contain any player data, so viewing it
        // is restricted to full administrators.
        if ( $GlobalUser['admin'] < USER_TYPE_ADMIN ) return true;

        return true;
    }

    public function view () : void {

        global $session;
        global $db_prefix;
        global $GlobalUser;

        $max_records = 50;
        $query = "SELECT * FROM ".$db_prefix."browse ORDER BY date DESC LIMIT $max_records";
        $result = dbquery ($query);

        $rows = dbrows ($result);
        echo va(loca("ADM_BROWSE_TITLE"), $max_records) . "<br>";
        echo "<table>\n";
        while ($rows--) 
        {
            $log = dbarray ( $result );
            $user = LoadUser ( $log['owner_id'] );
            if ( $user === null ) { continue; }
?>
            <tr><td><table>
            <tr> <th> <?=htmlspecialchars($user['oname']);?> </th> <th> <?=htmlspecialchars((string)$log['url'], ENT_QUOTES);?> </th> </tr>
            <tr> <th rowspan=2>
            <?=htmlspecialchars((string)$log['method'], ENT_QUOTES);?><br>
            <?=date ("d M Y", $log['date']);?><br>
            <?=date ("H:i:s", $log['date']);?>
            </th> <th> <pre><?=htmlspecialchars(var_export(unserialize($log['getdata']), true), ENT_QUOTES);?></pre> </th> </tr>
            <tr> <th> <pre><?=htmlspecialchars(var_export(unserialize($log['postdata']), true), ENT_QUOTES);?></pre> </th> </tr>
            </table></td></tr>

<?php
        }
        echo "</table>\n";

    }
}

?>