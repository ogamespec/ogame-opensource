<?php

// Империя.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("imperium", true);

$planettype = $_GET['planettype'];

// Загрузить список планет/лун.
$plist = array ();
$num = 0;

if ( $planettype == 1 || $planettype == 3)
{
    $result = EnumPlanets ();
    $rows = dbrows ($result);
    while ($rows--)
    {
        $planet = dbarray ($result);
        if ( $planettype == 1 && $planet['type'] == 0 ) continue;
        if ( $planettype == 3 && $planet['type'] != 0 ) continue;
        $plist[$num] = GetPlanet ($planet['planet_id']);
        $num ++;    
    }
}

?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
<script>t=0;</script>  

<table width="750" border="0" cellpadding="0" cellspacing="1">

<!-- ## 
<!-- ## Tablehead 
<!-- ## -->
        <tr height="20" valign="left">
            <td class="c" colspan="10"><?=loca("EMPIRE_OVERVIEW");?></td>
        </tr>
        
        <tr height="20">
            <th colspan="4"><a href="index.php?page=imperium&no_header=1&session=<?=$session;?>&planettype=1"><?=loca("EMPIRE_PLANETS");?></a></th>
            <th colspan="5"><a href="index.php?page=imperium&no_header=1&session=<?=$session;?>&planettype=3"><?=loca("EMPIRE_MOONS");?></a></th>

            <th>&nbsp;</th>
        </tr>

<!-- ## 
<!-- ## Planetimages 
<!-- ## -->
        <tr height="75">        
            <th width="75"></th>            

<?php
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th style=\"padding: 20px;\">  \n";
        echo "                    <a href=\"index.php?page=overview&session=$session&cp=".$planet['planet_id']."\">\n";
        echo "                        <img src=\"".GetPlanetImage(UserSkin(), $planet['type'])."\" width=\"75\" height=\"71\" border=\"0\">\n";
        echo "                    </a>\n";
        echo "            </th>   \n";
    }
?> 
            
            <th width="75"><?=loca("EMPIRE_SUM");?></th>

        </tr>

<!-- ## 
<!-- ## Name 
<!-- ## -->
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_NAME");?></th>
 
<?php
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th width=\"75\" >".$planet['name']."</th>\n";
    }
?>
            <th width="75">&nbsp;</th>

        </tr>

<!-- ## 
<!-- ## Coordinates 
<!-- ## -->
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_COORD");?></th>
 
<?php
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th width=\"75\" ><a href=\"index.php?page=galaxy&galaxy=".$planet['g']."&system=".$planet['s']."&position=6&session=$session\" >[".$planet['g'].":".$planet['s'].":".$planet['p']."]</a></th>\n";
    }
?>

            <th width="75">&nbsp;</th>

        </tr>

<!-- ## 
<!-- ## Fields 
<!-- ## -->
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_FIELDS");?></th>
<?php
    foreach ( $plist as $i=>$planet )
    {
        echo "            <th width=\"75\" >".$planet['fields']."/".$planet['maxfields']."</th>\n";
    }
?>
            <th width="75">1.093&nbsp;<a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(137)</a>&nbsp;/&nbsp;2.152&nbsp;<a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(269)</a></th>

        </tr>

<!-- ## 
<!-- ## Resources-Head
<!-- ## -->
        <tr height="20">
            <td align="left" class="c" colspan="10"><?=loca("EMPIRE_RES");?></td>
        </tr>

<!-- ## 
<!-- ## Resources (without Energy)
<!-- ## -->
 
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_M");?></th>

<?php
 /*
             <th width="75" >
                <a href="index.php?page=resources&session=e29a41cbf218&cp=34375493&planettype=1">
                        109.080 
                </a>
                / 
                6.198           </th>
*/
?>
 
            <th width="75">818.606&nbsp;/&nbsp;71.350</th>
        </tr>

 
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_K");?></th>
 
<?php 
/*
             <th width="75" >
                <a href="index.php?page=resources&session=e29a41cbf218&cp=34375493&planettype=1">
                        41.075 
                </a>
                / 
                2.334           </th>
*/
?>

            <th width="75">268.735&nbsp;/&nbsp;28.020</th>

        </tr>
 
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_D");?></th>
 
 
             <th width="75" >
                <a href="index.php?page=resources&session=e29a41cbf218&cp=34375493&planettype=1">
                        20.490 
                </a>
                / 
                1.164           </th>

 
 
             <th width="75" >
                <a href="index.php?page=resources&session=e29a41cbf218&cp=34358852&planettype=1">
                        26.788 
                </a>
                / 
                1.543           </th>
 
 
             <th width="75" >
                <a href="index.php?page=resources&session=e29a41cbf218&cp=34339853&planettype=1">
                        27.093 
                </a>

                / 
                1.541           </th>
 
 
             <th width="75" >
                <a href="index.php?page=resources&session=e29a41cbf218&cp=34303952&planettype=1">
                        22.759 
                </a>
                / 
                409         </th>
 
 
             <th width="75" >
                <a href="index.php?page=resources&session=e29a41cbf218&cp=34344166&planettype=1">

                        21.648 
                </a>
                / 
                1.803           </th>
 
 
             <th width="75" >
                <a href="index.php?page=resources&session=e29a41cbf218&cp=34331259&planettype=1">
                        14.107 
                </a>
                / 
                804         </th>
 
 
             <th width="75" >

                <a href="index.php?page=resources&session=e29a41cbf218&cp=34338086&planettype=1">
                        15.180 
                </a>
                / 
                1.156           </th>
 
 
             <th width="75" >
                <a href="index.php?page=resources&session=e29a41cbf218&cp=34330027&planettype=1">
                        17.083 
                </a>
                / 
                1.325           </th>

            <th width="75">165.149&nbsp;/&nbsp;9.744</th>
        </tr>
        

<!-- ## 
<!-- ## Resources-Energy
<!-- ## -->
        <tr height="20">
            <th width="75"><?=loca("EMPIRE_E");?></th>
 
 
            <th width="75" >
                    192 
 
                / 
                5.417           </th>

 
 
            <th width="75" >
                <font color="red">
                    -4 
                </font>
 
                / 
                7.577           </th>
 
 
            <th width="75" >
                    374 
 
                / 
                7.565           </th>
 
 
            <th width="75" >

                <font color="red">
                    -89 
                </font>
 
                / 
                7.289           </th>
 
 
            <th width="75" >
                    288 
 
                / 
                8.997           </th>
 
 
            <th width="75" >
                    55 
 
                / 
                5.957           </th>

 
 
            <th width="75" >
                    511 
 
                / 
                6.432           </th>
 
 
            <th width="75" >
                    445 
 
                / 
                7.270           </th>
            <th width="75">1.772 / 56.504 </th>
        </tr>

<!-- ## 
<!-- ## Buildings-Head
<!-- ## -->

        <tr height="20">
            <td align="left" class="c" colspan="10"><?=loca("EMPIRE_BUILDINGS");?></td>
        </tr>
        
<!-- ## 
<!-- ## Buildings
<!-- ## -->     
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=1&planettype=1">
                    Рудник по добыче металла                </a>

            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=1';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        23                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=1';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        26                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=1';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        25                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=1';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        29                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=1';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        27                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=1';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        25                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=1';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        24                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=1';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        25                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">204 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(25.5)</a></th>

        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=2&planettype=1">
                    Рудник по добыче кристалла                </a>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=2';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        19                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=2';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        22                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=2';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        22                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=2';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        23                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=2';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        23                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=2';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        22                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=2';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        21                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=2';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        22                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">174 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(21.75)</a></th>
        </tr>
        <tr height="20">

            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=3&planettype=1">
                    Синтезатор дейтерия                </a>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=3';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        18                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=3';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        20                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=3';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        20                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=3';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        22                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=3';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        21                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=3';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        18                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=3';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        18                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=3';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        19                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">156 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(19.5)</a></th>
        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=4&planettype=1">

                    Солнечная электростанция                </a>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=4';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        25                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=4';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        25                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=4';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        26                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=4';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        27                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=4';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        26                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=4';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        25                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=4';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        25                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=4';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        26                    </font>                    
       
                    </font>

                </a>    
            </th>           
    
            <th width="75">205 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(25.63)</a></th>
        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=14&planettype=1">
                    Фабрика роботов                </a>

            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=14';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        10                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=14';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        10                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=14';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        10                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=14';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        10                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=14';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        10                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=14';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        10                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=14';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        10                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=14';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        10                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">80 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(10)</a></th>

        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=15&planettype=1">
                    Фабрика нанитов                </a>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=15';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=15';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=15';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=15';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        4                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=15';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=15';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=15';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">10 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(1.43)</a></th>
        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=21&planettype=1">

                    Верфь                </a>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=21';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        8                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=21';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        8                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=21';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        9                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=21';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        10                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=21';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        9                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=21';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        9                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=21';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        8                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=21';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        9                    </font>                    
       
                    </font>

                </a>    
            </th>           
    
            <th width="75">70 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(8.75)</a></th>
        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=22&planettype=1">
                    Хранилище металла                </a>

            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=22';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        6                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=22';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        8                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=22';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        6                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=22';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        10                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=22';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        6                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=22';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        4                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=22';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">

                        4                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=22';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        8                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">52 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(6.5)</a></th>

        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=23&planettype=1">
                    Хранилище кристалла                </a>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=23';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">

                        4                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=23';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        5                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=23';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        3                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=23';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        6                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=23';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        2                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=23';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">

                        3                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=23';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        3                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=23';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        5                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">31 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(3.88)</a></th>
        </tr>
        <tr height="20">

            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=24&planettype=1">
                    Ёмкость для дейтерия                </a>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=24';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        3                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=24';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        3                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=24';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">

                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=24';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        4                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=24';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=24';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        2                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=24';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color ="lime">
                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=24';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        2                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">17 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(2.13)</a></th>
        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=31&planettype=1">

                    Исследовательская лаборатория                </a>
            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=31';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        9                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=31';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        12                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=31';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        9                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=31';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        9                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=31';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        7                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=31';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        11                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">57 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(9.5)</a></th>
        </tr>
        <tr height="20">

            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=33&planettype=1">
                    Терраформер                </a>
            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
                <font color="white">-</font>

            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=33';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        3                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

                <font color="white">-</font>
            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >

                <font color="white">-</font>
            </th>           
    
            <th width="75">3 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(3)</a></th>
        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=34&planettype=1">
                    Склад альянса                </a>

            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
                <font color="white">-</font>

            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=34';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
                <font color="white">-</font>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=34';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        1                    </font>                    
       
                    </font>
                </a>    
            </th>           
    
            <th width="75">2 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(1)</a></th>
        </tr>
        <tr height="20">
            <th width="75">
                <a href="index.php?page=infos&session=e29a41cbf218&gid=44&planettype=1">

                    Ракетная шахта                </a>
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34375493&cp=34375493\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34375493&techid=44';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        3                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34358852&cp=34358852\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34358852&techid=44';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        4                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34339853&cp=34339853\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34339853&techid=44';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        5                    </font>                    
       
                    </font>

                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34303952&cp=34303952\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34303952&techid=44';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        6                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34344166&cp=34344166\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34344166&techid=44';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">

                        4                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34331259&cp=34331259\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34331259&techid=44';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        3                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >

 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34338086&cp=34338086\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34338086&techid=44';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        3                    </font>                    
       
                    </font>
                </a>    
            </th>           
 
            <th width="75" >
 
                <a style="cursor:pointer" 
                   onClick="if(t==0){t=setTimeout('document.location.href=\'index.php?page=b_building&session=e29a41cbf218&planet=34330027&cp=34330027\';t=0;',500);}" 
                   onDblClick="clearTimeout(t);document.location.href='index.php?page=imperium&session=e29a41cbf218&planettype=1&no_header=1&modus=add&planet=34330027&techid=44';t=0;"
                   title="Щёлкнуть 1 раз: обзор, постройки, 2 раза: строить">       
                    <font color="red">
                        4                    </font>                    
       
                    </font>

                </a>    
            </th>           
    
            <th width="75">32 <a href='#' onMouseOver="return overlib('<font color=white>В среднем по планете</font>');" onMouseOut="return nd();">(4)</a></th>
        </tr>

<!-- ## 
<!-- ## Research-Head
<!-- ## -->
        <tr height="20">
            <td align="left" class="c" colspan="10"><?=loca("EMPIRE_RESEARCH");?></td>
        </tr>

        
<!-- ## 
<!-- ## Researches
<!-- ## -->     
<?php

    $resmap = array ( 106, 108, 109, 110, 111, 113, 114, 115, 117, 118, 120, 121, 122, 123, 124, 199 );

    foreach ($resmap as $i=>$res)
    {
        if ( $GlobalUser["r$res"] == 0 ) continue;

        echo "        <tr height=\"20\">\n";
        echo "            <th width=\"75\">\n";
        echo "                <a href=\"index.php?page=infos&session=$session&gid=$res&planettype=$planettype\">\n";
        echo "                    ".loca("NAME_$res")."             </a>\n";
        echo "            </th>\n\n\n";

        foreach ( $plist as $i=>$planet )
        {
            echo "            <th width=\"75\" >\n\n";
            echo "                <a href=\"index.php?page=buildings&session=$session&cp=".$planet['planet_id']."&mode=Forschung&planettype=$planettype\">\n";
            echo "                    <font color =\"lime\">\n\n";
            echo "                        ".$GlobalUser["r$res"]."                      \n";
            echo "                    </font>\n";
            echo "                </a>\n\n";
            echo "            </th>\n\n";
        }

        echo "            <th width=\"75\">12</th>\n\n";
        echo "        </tr>\n";
    }

?>

<!-- ## 
<!-- ## Ships-Head
<!-- ## --> 
        <tr height="20">
            <td align="left" class="c" colspan="10"><?=loca("EMPIRE_FLEET");?></td>
        </tr>
        
<!-- ## 
<!-- ## Ships
<!-- ## -->         
<?php

    $fleetmap = array ( 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215 );

    foreach ($fleetmap as $i=>$fleet)
    {
        echo "        <tr height=\"20\">\n";
        echo "            <th width=\"75\">\n";
        echo "                <a href=\"index.php?page=infos&session=$session&gid=$fleet&planettype=$planettype\">\n\n";
        echo "                    ".loca("NAME_$fleet")."                </a>\n";
        echo "            </th>\n\n";

        foreach ( $plist as $i=>$planet )
        {
            $amount = $planet["f$fleet"];
            echo "            <th width=\"75\" >\n";
            if ($amount > 0)
            {
                $m = $k = $d = $e = 0;
                ShipyardPrice ( $fleet, &$m, &$k, &$d, &$e );
                $meet = IsEnoughResources ( $planet, $m, $k, $d, $e );
                $color = $meet ? "lime" : "red";

                echo "                <a href=\"index.php?page=buildings&session=$session&cp=".$planet['planet_id']."&mode=Flotte&planettype=$planettype\">\n";
                echo "                    <font color =\"$color\">\n";
                echo "                        37                  </font>\n";
                echo "                </a>    \n";
            }
            else echo "                <font color=\"white\">-</font>\n";

            echo "            <th width=\"75\">76</th>\n\n";
            echo "            </th>\n\n";
        }

        echo "       </tr>\n";
    }

?>

<!-- ## 
<!-- ## Defense-Head
<!-- ## -->     
        <tr height="20">

            <td align="left" class="c" colspan="10"><?=loca("EMPIRE_DEFENSE");?></td>
        </tr>
        
<!-- ## 
<!-- ## Defense
<!-- ## -->             
<?php

    $defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408, 502, 503 );

    foreach ($defmap as $i=>$def)
    {
        echo "        <tr height=\"20\">\n";
        echo "            <th width=\"75\">\n";
        echo "                <a href=\"index.php?page=infos&session=$session&gid=$def&planettype=$planettype\">\n\n";
        echo "                    ".loca("NAME_$def")."                </a>\n";
        echo "            </th>\n\n";

        foreach ( $plist as $i=>$planet )
        {
            $amount = $planet["d$def"];
            echo "            <th width=\"75\" >\n";
            if ($amount > 0)
            {
                $m = $k = $d = $e = 0;
                ShipyardPrice ( $def, &$m, &$k, &$d, &$e );
                $meet = IsEnoughResources ( $planet, $m, $k, $d, $e );
                $color = $meet ? "lime" : "red";

                echo "                <a href=\"index.php?page=buildings&session=$session&cp=".$planet['planet_id']."&mode=Verteidigung&planettype=$planettype\">\n";
                echo "                    <font color =\"$color\">\n";
                echo "                        37                  </font>\n";
                echo "                </a>    \n";
            }
            else echo "                <font color=\"white\">-</font>\n";

            echo "            <th width=\"75\">76</th>\n\n";
            echo "            </th>\n\n";
        }

        echo "       </tr>\n";
    }

?>

<!-- ## 
<!-- ## Footer
<!-- ## -->     

</table>
<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ("", "", false, 0);
ob_end_flush ();
?>