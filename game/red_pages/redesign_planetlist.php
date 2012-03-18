<?php

// Redesign : список планет

// TODO : картинки планет
// TODO : картинка луны
// TODO : крупный список планет (когда их мало)
// TODO : иконка для сноса постройки
// TODO : иконки атаки

function planet_image ( $planet, $num_planets )
{
    $type = $planet['type'];
    $path = "red_images/planets/";
    if ($type >= 101 && $type <= 110) $path .= "dry_" . ($type - 100);
    else if ($type >= 201 && $type <= 210) $path .= "jungle_" . ($type - 200);
    else if ($type >= 301 && $type <= 307) $path .= "normal_" . ($type - 300);
    else if ($type >= 401 && $type <= 409) $path .= "water_" . ($type - 400);
    else if ($type >= 501 && $type <= 510) $path .= "ice_" . ($type - 500);
    if ( $num_planets <= 4 ) $path .= "_3";
    else $path .= "_1";
    $path .= ".gif";
    return $path;
}

function is_active ( $planet, $aktplanet )
{
    if ( $planet['g'] == $aktplanet['g'] && $planet['s'] == $aktplanet['s'] && $planet['p'] == $aktplanet['p'] ) return "active";
    return "";
}

function get_moon ( $g, $s, $p, $moons )
{
    foreach ( $moons as $i=>$moon )
    {
        if ( $moon['g'] == $g && $moon['s'] == $s && $moon['p'] == $p ) return $moon;
    }
    return null;
}

function get_building ( $planet_id )
{
    $result = GetBuildQueue ( $planet_id );
    if ( dbrows ( $result ) > 0 )
    {
        $queue = dbarray ($result);
        return $queue;
    }
    else return null;
}

    $maxplanets = 9;

    $result = EnumPlanets ();

    $planets = array ();
    $moons = array ();

    $rows = dbrows ( $result );
    while ($rows--)
    {
        $planet = dbarray ( $result );
        if ( $planet['type'] == 0 ) $moons[] = $planet;
        else $planets[] = $planet;
    }
    $num_planets = count ( $planets );

?>

            <!-- RIGHTMENU -->
            <div id="rechts">

<?php
    if ( $num_planets <= 4 )
    {
?>
    <div id="norm">
        <div id="myWorlds">                                
<?php
    }
    else
    {
?>
     <div id="cutty">
        <div id="myPlanets">
<?php
    }
?>
                <div id="countColonies">    
                    <p class="textCenter tipsStandard" title="|Количество доступных планет">
                        <span><?=$num_planets;?>/<?=$maxplanets;?></span> Планеты                    </p>    
                </div>

<?php

    foreach ( $planets as $i=>$planet )
    {
?>
                                                            
                                        <div class="smallplanet">
                        <a href="index.php?page=overview&session=<?=$session;?>&cp=<?=$planet['planet_id'];?>"
                           title="|&lt;B&gt;<?=$planet['name'];?> [<?=$planet['g'];?>:<?=$planet['s'];?>:<?=$planet['p'];?>]&lt;/B&gt;&lt;BR&gt;<?=nicenum($planet['diameter']);?>км (<?=$planet['fields'];?>/<?=$planet['maxfields'];?>)&lt;BR&gt;от <?=$planet['temp'];?>°C до <?=($planet['temp'] + 40);?>°C"
                           class="planetlink <?=is_active($planet, $aktplanet);?> tipsStandard">
                            <img class="planetPic" src="<?=planet_image($planet, $num_planets);?>"/>
                            <span class="planet-name"><?=$planet['name'];?></span>
                            <span class="planet-koords">[<?=$planet['g'];?>:<?=$planet['s'];?>:<?=$planet['p'];?>]</span>
                        </a>
<?php
    $moon = get_moon ( $planet['g'], $planet['s'], $planet['p'], $moons );
    if ( $moon )
    {
?>
                                                                                <a class="moonlink tipsStandard"
                               href="index.php?page=overview&session=<?=$session;?>&cp=<?=$moon['planet_id'];?>"
                               title="|<B>Перейти на <?=$moon['name'];?></B>">
                                <img src="red_images/8e0e6034049bd64e18a1804b42f179.gif" width="16" height="16" alt="" class="icon-moon"/>
                            </a>
<?php
    }

    $queue = get_building ( $planet['planet_id'] );
    if ( $queue )
    {
?>
            <a class="constructionIcon tipsStandard" href="index.php?page=overview&session=<?=$session;?>&cp=<?=$planet['planet_id'];?>" title="|<?=loca("NAME_".$queue['obj_id']);?>">
                    <img src="red_images/8d5601022c4f91d22c90802ee65393.gif" height="12" width="12" />
                    </a>
<?php
    }

    if ( $moon )
    {
        $queue = get_building ( $moon['planet_id'] );
        if ( $queue )
        {
?>
                                                            <a class="constructionIcon moon tipsStandard" href="index.php?page=overview&session=<?=$session;?>&cp=<?=$moon['planet_id'];?>" title="|<?=loca("NAME_".$queue['obj_id']);?>">
                                                                    <img src="red_images/8d5601022c4f91d22c90802ee65393.gif" height="12" width="12" />
                                                                    </a>
<?php
        }
    }
?>
                                                            
                            </div>
<?php
    }

?>
                                                                                                                                                        </div>
        </div>
            </div>
<!-- END RIGHTMENU -->
