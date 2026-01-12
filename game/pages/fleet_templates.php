<?php

/** @var array $GlobalUser */
/** @var string $db_prefix */
/** @var array $fleetmap */

// Fleet templates.

// You are able to create standard fleets. The maximum of standard fleets is your research level "Computer Technology" plus one
// https://board.en.ogame.gameforge.com/index.php?thread/195023-version-0-74e/
$MAX = $GlobalUser[GID_R_COMPUTER] + 1;

$prem = PremiumStatus ($GlobalUser);
if (!$prem['commander']) {
    MyGoto ("overview");
}

$fleetmap_nosat = array_diff($fleetmap, [GID_F_SAT]);

if ( method() === "POST" && key_exists('mode', $_POST) && $_POST['mode'] === "save" ) {
    $id = intval ( $_POST['template_id'] );
    $name = SecureText ( $_POST['template_name'] );
    $name = mb_substr ( $name, 0, 30 );

    $now = time ();

    if ( $id ) {    // Change
        $query = "SELECT * FROM ".$db_prefix."template WHERE id = $id AND owner_id = " . $GlobalUser['player_id'] . " LIMIT 1";
        $result = dbquery ( $query );
        if ( dbrows ($result) > 0 ) {
            $query = "UPDATE ".$db_prefix."template SET name='".$name."', date=$now";
            foreach ( $fleetmap_nosat as $i=>$gid ) {
                $query .= ", ship$gid ='" . intval ( $_POST['ship'][$gid] ) . "' ";
            }
            $query .= " WHERE id = $id";
            dbquery ( $query );
        }
    }
    else {    // Add
        // Limit the amount.
        $query = "SELECT * FROM ".$db_prefix."template WHERE owner_id = " . $GlobalUser['player_id'] ;
        $result = dbquery ( $query );
        $rows = dbrows ( $result );

        if ( $rows < $MAX )
        {
            $temp = array ( 'owner_id' => $GlobalUser['player_id'], 'name' => $name, 'date' => $now,
                'ship202' => intval ( $_POST['ship'][202] ), 
                'ship203' => intval ( $_POST['ship'][203] ), 
                'ship204' => intval ( $_POST['ship'][204] ), 
                'ship205' => intval ( $_POST['ship'][205] ), 
                'ship206' => intval ( $_POST['ship'][206] ), 
                'ship207' => intval ( $_POST['ship'][207] ), 
                'ship208' => intval ( $_POST['ship'][208] ), 
                'ship209' => intval ( $_POST['ship'][209] ), 
                'ship210' => intval ( $_POST['ship'][210] ), 
                'ship211' => intval ( $_POST['ship'][211] ), 
                'ship212' => 0, 
                'ship213' => intval ( $_POST['ship'][213] ), 
                'ship214' => intval ( $_POST['ship'][214] ), 
                'ship215' => intval ( $_POST['ship'][215] ), 
            );
            AddDBRow ( $temp, 'template' );
        }
    }
}

if ( method () === "GET" && key_exists('mode', $_GET) && $_GET['mode'] === "delete" ) {    // Delete
    $id = intval ( $_GET['id'] );
    $query = "SELECT * FROM ".$db_prefix."template WHERE id = $id AND owner_id = " . $GlobalUser['player_id'] . " LIMIT 1";
    $result = dbquery ( $query );
    if ( dbrows ($result) > 0 ) {
        $query = "DELETE FROM ".$db_prefix."template WHERE id = $id";
        dbquery ( $query );
    }
}

?>

    <script type="text/javascript">

    function show_input(id,name,s16,s17,s18,s19,s20,s21,s22,s23,s24,s25,s26,s27,s28,s29){

        document.getElementById('input_field').style.visibility="visible";
        document.getElementsByName('template_id')[0].value=id;
        document.getElementsByName('template_name')[0].value=name;
        document.getElementsByName('ship[202]')[0].value=s16;
        document.getElementsByName('ship[203]')[0].value=s17;
        document.getElementsByName('ship[204]')[0].value=s18;
        document.getElementsByName('ship[205]')[0].value=s19;
        document.getElementsByName('ship[206]')[0].value=s20;
        document.getElementsByName('ship[207]')[0].value=s21;
        document.getElementsByName('ship[208]')[0].value=s22;
        document.getElementsByName('ship[209]')[0].value=s23;
        document.getElementsByName('ship[210]')[0].value=s24;
        document.getElementsByName('ship[211]')[0].value=s25;
        document.getElementsByName('ship[213]')[0].value=s27;
        document.getElementsByName('ship[214]')[0].value=s28;
        document.getElementsByName('ship[215]')[0].value=s29;
    }

    </script>

    <body>
    <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
        <center>
        <table style='cellpadding=5px;' border=0>
        <tr>
            <td class='c' colspan=4 width=517 ><?=va(loca("FLEET_TEMP_TITLE_MAX"), $MAX);?></td>
        </tr>
        <tr>
            <th width=60 >#</th><th  width=267 ><?=loca("FLEET_TEMP_NAME");?><th><?=loca("FLEET_TEMP_UPDATE");?></th><th><?=loca("FLEET_TEMP_DELETE");?></th>
        </tr>
<?php
    $query = "SELECT * FROM ".$db_prefix."template WHERE owner_id = ".$GlobalUser['player_id']." ORDER BY date DESC LIMIT $MAX";
    $result = dbquery ( $query );
    $rows = dbrows ( $result );
    $count = 1;
    while ( $rows-- )
    {
        $temp = dbarray ( $result );
?>
                <tr>
            <th><?php echo $count;?></th><th width=160 ><a href=# onclick="show_input(<?php echo $temp['id'];?>,'<?php echo $temp['name'];?>',
            <?php echo $temp['ship202'];?>,<?php echo $temp['ship203'];?>,<?php echo $temp['ship204'];?>,<?php echo $temp['ship205'];?>,<?php echo $temp['ship206'];?>,
            <?php echo $temp['ship207'];?>,<?php echo $temp['ship208'];?>,<?php echo $temp['ship209'];?>,<?php echo $temp['ship210'];?>,<?php echo $temp['ship211'];?>,
            <?php echo $temp['ship212'];?>,<?php echo $temp['ship213'];?>,<?php echo $temp['ship214'];?>,<?php echo $temp['ship215'];?>);"><?php echo $temp['name'];?></a></th>
            <th width=80 ><a href=# onclick="show_input(<?php echo $temp['id'];?>,'<?php echo $temp['name'];?>',
            <?php echo $temp['ship202'];?>,<?php echo $temp['ship203'];?>,<?php echo $temp['ship204'];?>,<?php echo $temp['ship205'];?>,<?php echo $temp['ship206'];?>,
            <?php echo $temp['ship207'];?>,<?php echo $temp['ship208'];?>,<?php echo $temp['ship209'];?>,<?php echo $temp['ship210'];?>,<?php echo $temp['ship211'];?>,
            <?php echo $temp['ship212'];?>,<?php echo $temp['ship213'];?>,<?php echo $temp['ship214'];?>,<?php echo $temp['ship215'];?>);">O</a></th>
            <th width=80 ><a href=index.php?page=fleet_templates&session=<?php echo $session;?>&mode=delete&id=<?php echo $temp['id'];?> >X</a></th>
        </tr>
<?php
        $count++;
    }
?>
                <th colspan=4 align=center ><input type=button name=send value='<?=loca("FLEET_TEMP_CREATE");?>' onclick="show_input(0,'',0,0,0,0,0,0,0,0,0,0,0,0,0,0)"></th>
                </table>
        <br>
        <div id='input_field' style='visibility:hidden;'>
        <form action='index.php?page=fleet_templates&session=<?php echo $session;?>' method="POST">
        <input type="hidden" name=mode value=save >
        <table style='cellpadding=5px;' border=0>
        <tr><td class='c' colspan=2 width=517 ><?=loca("FLEET_TEMP_CREATE");?></td></tr>
        <tr>
        <th><?=loca("FLEET_TEMP_NAME");?></th>
        <th><input name='template_name' size=20 >
        <input type=hidden name='template_id' size=6></th>
        </tr>
<?php
    foreach ( $fleetmap_nosat as $i=>$gid ) {
        echo "                <tr>\n";
        echo "        <th>".loca("NAME_$gid")."</th>\n";
        echo "        <th><input name='ship[$gid]' size=3></th>\n";
        echo "        </tr>\n";
    }
?>
                        <th colspan=4 align=center ><input type=submit name=send value='<?=loca("FLEET_TEMP_SAVE");?>'></th>
        </tr>
        </form>

        </table>
        </div>
<br><br><br><br>
