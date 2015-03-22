1. You need to add **db\_buildqueue** in your universe. See table format in install.php

2. Refresh your local copy of source code from SVN

3. Upload uni.php, pages/admin\_uni.php, db.php and freeze universe.

4. Backup database in case you did something wrong.

5. Upload following script somewhere :

```
<?php

require_once "config.php";
require_once "db.php";

dbconnect ($db_host, $db_user, $db_pass, $db_name);
dbquery("SET NAMES 'utf8';");
dbquery("SET CHARACTER SET 'utf8';");
dbquery("SET SESSION collation_connection = 'utf8_general_ci';");

$query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'Build';";
$result = dbquery ($query);
while ($row = dbarray ($result) ) {
    $arr = array ( '', $row['owner_id'], $row['sub_id'], 1, $row['obj_id'], $row['level'], 0, $row['start'], $row['end'] );
    $sub_id = AddDBRow ( $arr, "buildqueue" );
    $query = "UPDATE ".$db_prefix."queue SET sub_id = $sub_id WHERE task_id = " . $row['task_id'];
    dbquery ($query);
}

$query = "SELECT * FROM ".$db_prefix."queue WHERE type = 'Demolish';";
$result = dbquery ($query);
while ($row = dbarray ($result) ) {
    $arr = array ( '', $row['owner_id'], $row['sub_id'], 1, $row['obj_id'], $row['level'], 1, $row['start'], $row['end'] );
    $sub_id = AddDBRow ( $arr, "buildqueue" );
    $query = "UPDATE ".$db_prefix."queue SET sub_id = $sub_id WHERE task_id = " . $row['task_id'];
    dbquery ($query);
}

?>
```

This script will add current buildings/demolish to the build queue and patch events.

6. Execute this script only **ONCE** <br>
(if you execute it twice, you'll get wrong planet_id's in build queue)<br>
<br>
7. Upload other files from /game/, /game/pages/, /game/reg/, /game/loca/<br>
<br>
8. Additionally you can update battle engine with recent rapidfire fix.<br>
<br>
<b>OR</b>

You can just reinstall universe =P<br>
<br>
Good luck! =)