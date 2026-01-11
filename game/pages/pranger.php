<?php

/** @var string $db_prefix */

// Pillar of Shame.

// TODO: HTML may differ slightly after adding the universal router (#171); we need to check how critical this is and compare it with saved copies of the original pages.

$limit = 50;    // Entries per page.
$internal = key_exists ( 'session', $_GET );

// Fixed version of the date
function MyDate ( $fmt, $timestamp )
{
    $date = new DateTime ('@' . $timestamp);
    return $date->format ($fmt);
}

// ************************************************************************************
?>

   <h1><?=va(loca("PRANGER_TITLE"), $GlobalUni['num']);?></h1>
   <p><?=loca("PRANGER_INFO");?></p>

   <table border="0" cellpadding="2" cellspacing="1">
    <tr height="20">
     <td class="c"><?=loca("PRANGER_WHEN");?></td>
     <td class="c"><?=loca("PRANGER_OPER");?></td>
     <td class="c"><?=loca("PRANGER_USER");?></td>
     <td class="c"><?=loca("PRANGER_UNTIL");?></td>
     <td class="c"><?=loca("PRANGER_REASON");?></td>
    </tr>

<?php
    $from = key_exists('from', $_GET) ? intval ( $_GET['from'] ) : 0;
    $query = "SELECT * FROM ".$db_prefix."pranger ORDER BY ban_when DESC LIMIT $from, $limit";
    $result = dbquery ($query);
    $total = $rows = dbrows ( $result );
    while ( $rows-- )
    {
        $entry = dbarray ( $result );
        echo "        <tr height=\"20\">\n";
        echo "     <th>".date("D M j Y G:i:s", $entry['ban_when'])." </th>\n\n";
        echo "          <th>\n";
        echo "       ".$entry['admin_name']."     </th>\n\n";
        echo "     <th>".$entry['user_name']."</th>\n";
        echo "     <th>".MyDate("D M j Y G:i:s", $entry['ban_until'])."</th>\n";
        echo "     <th>".$entry['reason']."</th>\n";
        echo "    </tr>\n";
    }

?>
       <tr>
   <th colspan="5">
<?php
    if ($internal) $pranger_url = "index.php?page=pranger&session=$session&from";
    else $pranger_url = "pranger.php?from";
    if ($from >= $limit) echo "     <a href=\"".$pranger_url."=".($from-$limit)."\"><< ".va(loca("PRANGER_PREV"), $limit)."</a>&nbsp;&nbsp;&nbsp;&nbsp;\n";
    if ($total >= $limit) echo "        <a href=\"".$pranger_url."=".($from+$limit)."\">".va(loca("PRANGER_NEXT"), $limit)." >></a>\n";
?>
      </th>
   </tr>
   </table>
