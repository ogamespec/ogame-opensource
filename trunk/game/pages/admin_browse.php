<?php

// ========================================================================================
// История переходов (только за игроками, у которых включен флаг sniff).

function Admin_Browse ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $query = "SELECT * FROM ".$db_prefix."browse ORDER BY date DESC LIMIT 50";
    $result = dbquery ($query);

    AdminPanel();

    $rows = dbrows ($result);
    echo "Последняя история переходов (50 записей):<br>";
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
        </th> <th> <?=print_r( unserialize($log['getdata']) );?> </th> </tr>
        <tr> <th> <?=print_r( unserialize($log['postdata']) );?> </th> </tr>
        </table></td></tr>

<?php
    }
    echo "</table>\n";
}
?>