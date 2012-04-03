<?php

// Redesign : список планет

/*
Номер картинки = (система + планета) % 10 + 1. Галактика не учитывается.

Тип картинки зависит от четности номера системы и позиции планеты. В четных системах одна последовательность, в нечетных - другая:
3*desert + 2*dry + 2*normal + 2*jungle + 2*water + 2*ice + 2*gas
3*dry + 2*normal + 2*jungle + 2*water + 2*ice + 2*gas + 2*normal

Для лун : номер = система % 5 + 1. Галактика не учитывается.
*/

// TODO : иконка для сноса постройки
// TODO : иконки атаки

$moonPictures = array (
    1 => 'd8adf683b2e709a24fa447392c96b8.gif',
    2 => '6c86116a2c1d0fc00f3b17a2b6ff49.gif',
    3 => '7b05c61b8fa8c483a464cb423cb02b.gif',
    4 => '8e0e6034049bd64e18a1804b42f179.gif',
    5 => '9c9f0a78e85bcf40c2ccfc08db5cb4.gif',
);

function planet_image ( $planet, $num_planets )
{
    $type = $planet['type'];
    $path = "red_images/planets/";

    $n = ( $planet['s'] + $planet['p'] ) % 10 + 1;
    $p = $planet['p'];

    if ( $planet['s'] % 2 )    // Четные системы.
    {
        // 3*desert + 2*dry + 2*normal + 2*jungle + 2*water + 2*ice + 2*gas

        if ( $p >= 1 && $p <= 3 ) $path .= "desert_" . $n;
        else if ( $p >= 4 && $p <= 5 ) $path .= "dry_" . $n;
        else if ( $p >= 6 && $p <= 7 ) $path .= "normal_" . $n;
        else if ( $p >= 8 && $p <= 9 ) $path .= "jungle_" . $n;
        else if ( $p >= 10 && $p <= 11 ) $path .= "water_" . $n;
        else if ( $p >= 12 && $p <= 13 ) $path .= "ice_" . $n;
        else $path .= "gas_" . $n;
    }
    else
    {
        // 3*dry + 2*normal + 2*jungle + 2*water + 2*ice + 2*gas + 2*normal

        if ( $p >= 1 && $p <= 3 ) $path .= "dry_" . $n;
        else if ( $p >= 4 && $p <= 5 ) $path .= "normal_" . $n;
        else if ( $p >= 6 && $p <= 7 ) $path .= "jungle_" . $n;
        else if ( $p >= 8 && $p <= 9 ) $path .= "water_" . $n;
        else if ( $p >= 10 && $p <= 11 ) $path .= "ice_" . $n;
        else if ( $p >= 12 && $p <= 13 ) $path .= "gas_" . $n;
        else $path .= "normal_" . $n;
    }

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
                                <img src="red_images/<?=$moonPictures[$moon['s'] % 5 + 1];?>" width="16" height="16" alt="" class="icon-moon"/>
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
