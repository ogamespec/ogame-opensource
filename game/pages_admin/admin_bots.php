<?php

// Admin Area: Bot control

function Admin_Bots ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $result = "";

    // POST request processing.
    if ( method () === "POST" && $GlobalUser['admin'] >= 2 )
    {
        if (BotStrategyExists("_start")) {
            if ( AddBot ( $_POST['name'] ) ) $result = "<font color=lime>".loca("ADM_BOTS_ADDED")."</font>";
            else $result = "<font color=red>".loca("ADM_BOTS_USER_NOT_FOUND")."</font>";
        }
        else {
            $result = "<font color=red>".loca("ADM_BOTS_NO_START")."</font>";
        }
    }

    // GET request processing.
    if ( method () === "GET" && key_exists('id', $_GET) && $GlobalUser['admin'] >= 2 )
    {
        StopBot ( intval ($_GET['id']) );
        $result = "<font color=lime>".loca("ADM_BOTS_STOPPED")."</font>";
    }

?>

<?=AdminPanel();?>

<?php
    if ( $GlobalUser['admin'] < 2) {

        echo "<font color=red>".loca("ADM_BOTS_FORBIDDEN")."</font>";
        return;
    }
?>

<center><?=$result;?></center>

<h2><?=loca("ADM_BOTS_LIST");?></h2>

<?php

    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'AI' GROUP BY owner_id";
    $result = dbquery ( $query );
    $rowss = $rows = dbrows ($result);
    if ( $rows == 0 ) echo loca("ADM_BOTS_NOT_FOUND") . "<br>";
    else {
        echo "<table>\n";
        echo "<tr><td class=c>ID</td><td class=c>".loca("ADM_BOTS_NAME")."</td><td class=c>".loca("ADM_BOTS_HOMEPLANET")."</td><td class=c>".loca("ADM_BOTS_ACTION")."</td></tr>\n";
    }
    while ($rows--) {
        $queue = dbarray ($result);
        $user = LoadUser ( $queue['owner_id'] );
        $planet = GetPlanet ( $user['hplanetid'] );
        echo "<tr>";
        echo "<td>".$user['player_id']."</td>";
        echo "<td>".AdminUserName ($user)."</td>";
        echo "<td>". AdminPlanetName ($planet['planet_id']). " " . AdminPlanetCoord($planet) . "</td>";
        echo "<td><a href=\"index.php?page=admin&session=$session&mode=Bots&action=stop&id=".$user['player_id']."\">".loca("ADM_BOTS_STOP")."</a></td>";
        echo "</tr>\n";
    }
    if ( $rowss ) echo "</table>";
?>

<h2><?=loca("ADM_BOTS_ADD");?></h2>

<form action="index.php?page=admin&session=<?=$session;?>&mode=Bots" method="POST">
<table>
<tr><td><?=loca("ADM_BOTS_NAME");?> <input type=text size=10 name="name" /> <input type=submit value="<?=loca("ADM_BOTS_SUBMIT");?>" /></td></tr>
</table>
</form>

<?php
}
?>
