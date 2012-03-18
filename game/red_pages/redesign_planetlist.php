<?php

// Redesign : список планет

// TODO : картинки планет
// TODO : крупный список планет (когда их мало)
// TODO : иконка для сноса постройки
// TODO : иконки атаки
// TODO : обрамление текущей планеты

function is_active ( $planet_id )
{
    global $aktplanet;

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

?>

            <!-- RIGHTMENU -->
            <div id="rechts">
                                
    <div id="cutty">
        <div id="myPlanets">
                <div id="countColonies">    
                    <p class="textCenter tipsStandard" title="|Количество доступных планет">
                        <span><?=count($planets);?>/<?=$maxplanets;?></span> Планеты                    </p>    
                </div>

<?php

    foreach ( $planets as $i=>$planet )
    {
?>
                                        <div class="smallplanet">
                        <a href="index.php?page=overview&session=<?=$session;?>&cp=<?=$planet['planet_id'];?>"
                           title="|&lt;B&gt;<?=$planet['name'];?> [<?=$planet['g'];?>:<?=$planet['s'];?>:<?=$planet['p'];?>]&lt;/B&gt;&lt;BR&gt;<?=nicenum($planet['diameter']);?>км (<?=$planet['fields'];?>/<?=$planet['maxfields'];?>)&lt;BR&gt;от <?=$planet['temp'];?>°C до <?=($planet['temp'] + 40);?>°C"
                           class="planetlink <?=is_active($planet['planet_id']);?> tipsStandard">
                            <img class="planetPic" src="red_images/51ec5f9a7d6e4254a19cf2ffe2937e.gif"/>
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
                                <img src="red_images/7b05c61b8fa8c483a464cb423cb02b.gif" width="16" height="16" alt="" class="icon-moon"/>
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
