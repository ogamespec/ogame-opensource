<?php

// Стандартные флоты.

$MAX = 13;

loca_add ( "menu", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("fleet_templates");

$temp_map = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 213, 214, 215 );    // без сс

// Вырезать из строки всякие инжекции.
function SecureText ( $text )
{
    $search = array ( "'<script[^>]*?>.*?</script>'si",  // Вырезает javaScript
                      "'<[\/\!]*?[^<>]*?>'si",           // Вырезает HTML-теги
                      "'([\r\n])[\s]+'" );             // Вырезает пробельные символы
    $replace = array ("", "", "\\1", "\\1" );
    $str = preg_replace($search, $replace, $text);
    $str = str_replace ("`", "", $str);
    $str = str_replace ("'", "", $str);
    $str = str_replace ("\"", "", $str);
    $str = str_replace ("%0", "", $str);
    return $str;
}

if ( method() === "POST" && $_POST['mode'] === "save" ) {
    $id = intval ( $_POST['template_id'] );
    $name = SecureText ( $_POST['template_name'] );
    $name = mb_substr ( $name, 0, 30 );

    $now = time ();

    if ( $id ) {    // Изменить
        $query = "SELECT * FROM ".$db_prefix."template WHERE id = $id AND owner_id = " . $GlobalUser['player_id'] . " LIMIT 1";
        $result = dbquery ( $query );
        if ( dbrows ($result) > 0 ) {
            $query = "UPDATE ".$db_prefix."template SET name='".$name."', date=$now";
            foreach ( $temp_map as $i=>$gid ) {
                $query .= ", ship$gid ='" . intval ( $_POST['ship'][$gid] ) . "' ";
            }
            $query .= " WHERE id = $id";
            dbquery ( $query );
        }
    }
    else {    // Добавить
        // Ограничить количество.
        $query = "SELECT * FROM ".$db_prefix."template WHERE owner_id = " . $GlobalUser['player_id'] ;
        $result = dbquery ( $query );
        $rows = dbrows ( $result );

        if ( $rows < $MAX )
        {
            $temp = array ( null, $GlobalUser['player_id'], $name, $now,
                intval ( $_POST['ship'][202] ), 
                intval ( $_POST['ship'][203] ), 
                intval ( $_POST['ship'][204] ), 
                intval ( $_POST['ship'][205] ), 
                intval ( $_POST['ship'][206] ), 
                intval ( $_POST['ship'][207] ), 
                intval ( $_POST['ship'][208] ), 
                intval ( $_POST['ship'][209] ), 
                intval ( $_POST['ship'][210] ), 
                intval ( $_POST['ship'][211] ), 
                0, 
                intval ( $_POST['ship'][213] ), 
                intval ( $_POST['ship'][214] ), 
                intval ( $_POST['ship'][215] ), 
            );
            AddDBRow ( $temp, 'template' );
        }
    }
}

if ( method () === "GET" && $_GET['mode'] === "delete" ) {    // Удалить
    $id = intval ( $_GET['id'] );
    $query = "SELECT * FROM ".$db_prefix."template WHERE id = $id AND owner_id = " . $GlobalUser['player_id'] . " LIMIT 1";
    $result = dbquery ( $query );
    if ( dbrows ($result) > 0 ) {
        $query = "DELETE FROM ".$db_prefix."template WHERE id = $id";
        dbquery ( $query );
    }
}

?>
<!-- CONTENT AREA -->
<div id='content'>
<center>

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
            <td class='c' colspan=4 width=517 >Стандартные флоты (макс. <?php echo $MAX;?>)</td>
        </tr>
        <tr>
            <th width=60 >#</th><th  width=267 >Название<th>Обработать</th><th>Удалить</th>
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
                <th colspan=4 align=center ><input type=button name=send value='Создать новый стандартный флот' onclick="show_input(0,'',0,0,0,0,0,0,0,0,0,0,0,0,0,0)"></th>
                </table>
        <br>
        <div id='input_field' style='visibility:hidden;'>
        <form action='index.php?page=fleet_templates&session=<?php echo $session;?>' method="POST">
        <input type="hidden" name=mode value=save >
        <table style='cellpadding=5px;' border=0>
        <tr><td class='c' colspan=2 width=517 >Создать новый стандартный флот</td></tr>
        <tr>
        <th>Название</th>
        <th><input name='template_name' size=20 >
        <input type=hidden name='template_id' size=6></th>
        </tr>
<?php
    foreach ( $temp_map as $i=>$gid ) {
        echo "                <tr>\n";
        echo "        <th>".loca("NAME_$gid")."</th>\n";
        echo "        <th><input name='ship[$gid]' size=3></th>\n";
        echo "        </tr>\n";
    }
?>
                        <th colspan=4 align=center ><input type=submit name=send value='Сохранить'></th>
        </tr>
        </form>

        </table>
        </div>
<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>