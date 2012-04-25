<?php

// Redesign : Обзор

// TODO : меню планеты
// TODO : ПОСТРОЙКИ
// TODO : ИССЛЕДОВАНИЯ
// TODO : ВЕРФЬ
// TODO : картинки лун и планет

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], (int)$_GET['cp']);
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( &$aktplanet, $aktplanet['lastpeek'], $now );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "technames", $GlobalUser['lang'] );
loca_add ( "fleetorder", $GlobalUser['lang'] );
loca_add ( "overview", $GlobalUser['lang'] );

##############################################################################################################

include "redesign_header.php";
include "redesign_leftmenu.php";
include "redesign_planetlist.php";

function planet_header ($planet)
{
    $type = $planet['type'];
    if ( $type == 0 ) return "red_images/57a80a58c7a48b8d27316b753dd259.jpg";
    else if ($type >= 101 && $type <= 110) return "red_images/planet/header/header_dry.jpg";
    else if ($type >= 201 && $type <= 210) return "red_images/planet/header/header_jungle.jpg";
    else if ($type >= 301 && $type <= 307) return "red_images/planet/header/header_normal.jpg";
    else if ($type >= 401 && $type <= 409) return "red_images/planet/header/header_water.jpg";
    else if ($type >= 501 && $type <= 510) return "red_images/planet/header/header_ice.jpg";
}

function planet_link ($planet)
{
    $type = $planet['type'];
    if ($type >= 101 && $type <= 110) return "red_images/19826c23562f7f3ea60505d69b1593.jpg";
    else if ($type >= 201 && $type <= 210) return "red_images/81c8ca1de92bafbbe8e4cd841a11ad.jpg";
    else if ($type >= 301 && $type <= 307) return "red_images/1669b8f0ad16eaab1a73e6081e7e85.jpg";
    else if ($type >= 401 && $type <= 409) return "red_images/303e39867fbca9db429e0c6343c508.jpg";
    else if ($type >= 501 && $type <= 510) return "red_images/8c832f875f06ebc8e838dbff070856.jpg";
}

?>

            <!-- CONTENT AREA -->
            <div id="contentWrapper">
                                    <div id="eventboxContent" style="display: none"><img height="16" width="16" src="red_images/3f9884806436537bdec305aa26fc60.gif" /></div>
                                
<div id="inhalt">
    <div id="planet" style="background-image:url(<?=planet_header($aktplanet);?>);">
        <h2>
            <a href="javascript:void(0);" class="openPlanetrenameGiveupBox">
                <p class="planetNameOverview">Обзор -</p>
                <span id="planetNameHeader">
                    <?=$aktplanet['name'];?>                    </span>
                <img class="hinted tipsStandard" title="|покинуть/переименовать Планета" src="red_images/1f57d944fff38ee51d49c027f574ef.gif" />
            </a>
        </h2>
        <div id="planetdata">
                        <div class="overlay"></div>
                        <div id="planetDetails">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td class="desc" >
                            <span id="diameterField"></span>
                        </td>
                        <td class="data tipsStandard" title="|Диаметр, количество использованных и максимально доступных полей">
                            <span id="diameterContentField"></span>
                         </td>
                    </tr>
                    <tr>
                        <td class="desc">
                            <span id="temperatureField"></span>
                        </td>
                        <td class="data tipsStandard" title="|Температура">
                            <span id="temperatureContentField"></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="desc">
                            <span id="positionField"></span>
                        </td>
                        <td class="data tipsStandard" title="|Координаты">
                            <span id="positionContentField"></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="desc"><span id="scoreField"></span></td>
                        <td class="data tipsStandard" title="|Статистика">
                            <span id="scoreContentField"></span>
                        </td>
                    </tr>
                </table>
            </div>

        <div id="planetOptions">
    
         <a href="javascript:void(0);" class="tipsStandard" title="|Другие настройки планеты" onclick='openPlanetRenameGiveupBox();'>
             <span class="planetMoveOverviewGivUpLink">покинуть/переименовать</span>
             <div class="planetMoveIcons settings planetMoveGiveUp"></div>
         </a>
        </div>
    </div>

<?php

    if ( $aktplanet['type'] == 0 )
    {
        $pl = LoadPlanet ( $aktplanet['g'], $aktplanet['s'], $aktplanet['p'], 1 );
?>
        <div id="planet_as_moon">
        	<a 	href="index.php?page=overview&session=<?=$session;?>&cp=<?=$pl['planet_id'];?>"
            	class="tipsStandard"
                title="|Перейти к Планета  ">
            	<img alt="" src="<?=planet_link($pl);?>">
            </a>
        </div>
<?php
    }
    else
    {
        $moon_id = PlanetHasMoon ( $aktplanet['planet_id'] );
        if ( $moon_id )
        {
            $moon = GetPlanet ( $moon_id )
?>
        <div id="moon">
        	<a 	href="index.php?page=overview&session=<?=$session;?>&cp=<?=$moon['planet_id'];?>"
            	class="tipsStandard"
                title="|Перейти к Луна  ">
            	<img alt="" src="red_images/17e17069847b09b3d1ef6d03729107.gif">
            </a>
        </div>
<?php
        }
    }
?>
        
    </div>    <div class="c-left"></div>
    <div class="c-right"></div>

<div class="content-box-s">
	<div class="header">
    	<h3>Постройки</h3>
    </div>
		<div class="content">
			<table cellpadding="0" cellspacing="0" class="construction">
				<tr>
					<td colspan="2" class="idle">
                    	<a class="tipsStandard" 
                    	   title="|В данный момент ничего не строится на этой планете. Нажмите здесь, чтобы попасть в меню Сырье." 
                    	   href="index.php?page=resources&session=<?=$session;?>">
                    	   Очередь построек пуста!                    	</a>
                    </td>
				</tr>
  
			</table>
		</div>
	<div class="footer"></div>
</div>

<div class="content-box-s">
	<div class="header"><h3>Исследования</h3></div>
	<div class="content">
		<table cellpadding="0" cellspacing="0" class="construction">
 
				<tr>
				<th colspan="2">Щитовая технология</th>
			</tr>
			<tr class="data">
				<td class="building" rowspan="3">
	                <a href="javascript:void(0);" 
	                	onclick="cancelResearch(110,'Вы действительно хотите отменить исследование Щитовая технология уровня 12 на планете Колония [1:150:9]?'); return false;"
	                 	class="tipsStandard" 
	                    title="|Вы действительно хотите отменить исследование Щитовая технология уровня 12 на планете Колония [1:150:9]?">
	                	<img class="queuePic" src="red_images/990b8dac9ab69451384eacee2509b3.jpg" alt="Щитовая технология" height="40" width="40">
	                </a>
					<a href="javascript:void(0);" 
	                	onclick="cancelResearch(110,'Вы действительно хотите отменить исследование Щитовая технология уровня 12 на планете Колония [1:150:9]?'); return false;"
	                 	class="tipsStandard abortNow" 
	                    title="|Вы действительно хотите отменить исследование Щитовая технология уровня 12 на планете Колония [1:150:9]?">
	                	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="15" height="15"> 
	                </a>                    
                </td>
				<td class="desc ausbau">
					Исследовать до <span class="level"> Уровень 12</span>
                </td>
			</tr>
            <tr class="data">
            	<td class="desc">Продолжительность:</td>
            </tr>
            <tr class="data">
            	<td class="desc timer">
                	<span id="researchCountdown">загрузка...</span>
                </td>
            </tr>
   
		</table>                   
	</div>
	<div class="footer"></div>
</div>

<div class="content-box-s">
    <div class="header"><h3>Верфь</h3></div>
        <div class="content">    
                <table cellspacing="0" cellpadding="0" class="construction">
                <tbody>
                <tr>
                    <td colspan="2" class="idle">
                        <a class="tipsStandard" 
                           title="|В данный момент на этой планете не строятся корабли или оборона. Нажмите здесь, чтобы попасть в меню Верфь." 
                           href="index.php?page=shipyard&session=<?=$session;?>">
                           Корабли/оборона не производятся в данный момент.                        </a>
                    </td>
                </tr>   
                </tbody>
                </table>
        </div>
    <div class="footer"></div>
</div>
<div class="clearfloat"></div>

    
    
</div>
                            </div>
            <!-- END CONTENT AREA -->

<?php

include "redesign_javascript.php";
include "redesign_footer.php";

?>