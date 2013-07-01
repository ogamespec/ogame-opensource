<?php

// Redesign : Обзор

// TODO : меню планеты
// TODO : ПОСТРОЙКИ
// TODO : ИССЛЕДОВАНИЯ
// TODO : ВЕРФЬ
// TODO : картинки лун

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], (int)$_GET['cp']);
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
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
        
    <div id="detailWrapper">
        <div id="header_text">
            <h2>
                                    <a href="javascript:void(0);" class="openPlanetRenameGiveupBox">
                        <p class="planetNameOverview">Обзор -</p>
                        <span id="planetNameHeader">
                            <?=$aktplanet['name'];?>                        </span>
                        <img class="hinted tooltip" title="Покинуть/переименовать" src="red_images/1f57d944fff38ee51d49c027f574ef.gif" />
                    </a>
                            </h2>
        </div>
        <div id="detail" class="detail_screen">
            <div id="techDetailLoading"></div>
        </div>
        <div id="planetdata">
                        <div class="overlay"></div>
                        <div id="planetDetails">
                <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td class="desc" >
                            <span id="diameterField"></span>
                        </td>
                        <td class="data">
                            <span id="diameterContentField"></span>
                         </td>
                    </tr>
                    <tr>
                        <td class="desc">
                            <span id="temperatureField"></span>
                        </td>
                        <td class="data">
                            <span id="temperatureContentField"></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="desc">
                            <span id="positionField"></span>
                        </td>
                        <td class="data">
                            <span id="positionContentField"></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="desc">
                            <span id="scoreField"></span></td>
                        <td class="data">
                            <span id="scoreContentField"></span>
                        </td>
                    </tr>

                </table>
            </div>

        <div id="planetOptions">
            
         <a class="dark_highlight_tablet float_right openPlanetRenameGiveupBox" href="javascript:void(0);">
             <span class="planetMoveOverviewGivUpLink">Покинуть/переименовать</span>
             <span class="planetMoveIcons settings planetMoveGiveUp icon"></span>
         </a>
        </div>
    </div>
    </div>
             
        
    </div>    <div class="c-left"></div>
    <div class="c-right"></div>
    <div id="overviewBottom">

<div class="content-box-s">
	<div class="header">
    	<h3>Постройки</h3>
    </div>
		<div class="content">
			<table cellpadding="0" cellspacing="0" class="construction active">
				<tr>
					<td colspan="2" class="idle">
                                                    <a class="tooltip js_hideTipOnMobile
                               "
                               title="В данный момент ничего не строится на этой планете. Нажмите здесь, чтобы попасть в меню Сырье."
                               href="index.php?page=resources&session=<?=$session;?>">
                               Очередь построек пуста!<br/>(К ресурсам)                            </a>
                                            </td>
				</tr>
  
			</table>
		</div>
	<div class="footer"></div>
</div>

<div class="content-box-s">
    <div class="header"><h3>Исследования</h3></div>
        <div class="content">    
            <table cellspacing="0" cellpadding="0" class="construction active">
                <tbody>
                <tr>
                    <td colspan="2" class="idle">
                                                    <a class="tooltip js_hideTipOnMobile
                               "
                               title="В данный момент ничего не исследуется. Нажмите здесь, чтобы попасть в меню Исследования."
                               href="index.php?page=research&session=<?=$session;?>">
                               Не ведется никаких исследований.<br/>(К исследованиям)                            </a>
                                            </td>
                </tr>   
                </tbody>
                </table>
        </div>
    <div class="footer"></div>
</div>
<div class="content-box-s">
    <div class="header"><h3>Верфь</h3></div>
        <div class="content">    
            <table cellspacing="0" cellpadding="0" class="construction active">
                <tbody>
                <tr>
                    <td colspan="2" class="idle">
                                                <a class="tooltip js_hideTipOnMobile
                           "
                           title="В данный момент на этой планете не строятся корабли или оборона. Нажмите здесь, чтобы попасть в меню Верфь."
                           href="index.php?page=shipyard&session=<?=$session;?>">
                           Корабли/оборона не производятся в данный момент.<br/>(К верфи)                        </a>
                        
                    </td>
                </tr>   
                </tbody>
                </table>
        </div>
    <div class="footer"></div>
</div>
<div class="clearfloat"></div>

    <div class="clearfloat"></div>
    </div><!-- #overviewBottom -->
</div>
                            </div>
            <!-- END CONTENT AREA -->

<?php

include "redesign_planetlist.php";
include "javascript/overview.php";
include "redesign_footer.php";

?>