<?php

// Redesign : Верфь

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

?>

            <!-- CONTENT AREA -->
            <div id="contentWrapper">
                                    <div id="eventboxContent" style="display: none"><img height="16" width="16" src="red_images/3f9884806436537bdec305aa26fc60.gif" /></div>
                                
<div id="inhalt">
    <div id="planet" style="background-image:url(red_images/ca91b221827fd9b60d4c26ca65f47f.jpg)">
	    <div id="header_text">


            	        <h2>Верфь - <?=$aktplanet['name'];?></h2>


	    </div>
    
        <form method="POST" action="index.php?page=shipyard&session=<?=$session;?>" name="form" onkeydown="sendBuildRequest(null, event);" onsubmit="return false;">
            <input type='hidden' name='token' value='442ce1dd4862fbb87f82a33782c7e352' />            <div id="detail" class="detail_screen">
                <div id="techDetailLoading"></div>
            </div>
        </form>
        
    </div>
    <div class="c-left"></div>
    <div class="c-right"></div>    
    <div id="buttonz">
        <div id="battleships">
            <h2>Боевые корабли</h2>   
            <ul id="military">
    	            <li id="button1" class="on">
    	            <div class="military204">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Лёгкий истребитель (0)" ref="204" id="details204" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Лёгкий истребитель	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button2" class="on">
    	            <div class="military205">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Тяжёлый истребитель (0)" ref="205" id="details205" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Тяжёлый истребитель	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button3" class="on">
    	            <div class="military206">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Крейсер (22)" ref="206" id="details206" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Крейсер	                                </span>
	                                22	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button4" class="on">
    	            <div class="military207">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Линкор (0)" ref="207" id="details207" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Линкор	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button5" class="disabled">
    	            <div class="military215">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Линейный крейсер (2)<br/>Недостаточно ресурсов!" ref="215" id="details215" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Линейный крейсер	                                </span>
	                                2	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button6" class="on">
    	            <div class="military211">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Бомбардировщик (0)" ref="211" id="details211" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Бомбардировщик	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button7" class="disabled">
    	            <div class="military213">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Уничтожитель (0)<br/>Недостаточно ресурсов!" ref="213" id="details213" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Уничтожитель	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button8" class="off">
    	            <div class="military214">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Звезда смерти (0)<br/>Требования не выполнены" ref="214" id="details214" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Звезда смерти	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
                                                           
            </ul>
        </div>
	    <div id="spacer"></div>  
	    <div id="civilships">
			<h3>Гражданские корабли</h3>
			<ul id="civil">
                                    	            <li id="button1" class="on">
    	            <div class="civil202">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Малый транспорт (0)" ref="202" id="details202" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Малый транспорт	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button2" class="on">
    	            <div class="civil203">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Большой транспорт (33)" ref="203" id="details203" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Большой транспорт	                                </span>
	                                33	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button3" class="on">
    	            <div class="civil208">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Колонизатор (0)" ref="208" id="details208" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Колонизатор	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button4" class="on">
    	            <div class="civil209">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Переработчик (0)" ref="209" id="details209" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Переработчик	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button5" class="on">
    	            <div class="civil210">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Шпионский зонд (0)" ref="210" id="details210" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Шпионский зонд	                                </span>
	                                0	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
        	            <li id="button6" class="on">
    	            <div class="civil212">
	                <div class="buildingimg">
	                    <a class="detail_button tipsStandard slideIn" title="|Солнечный спутник (348)" ref="212" id="details212" href="javascript:void(0);">   
	                        <span class="ecke">
	                            <span class="level">
	                                <span class="textlabel">
	                                    Солнечный спутник	                                </span>
	                                348	                            </span>
	                        </span>                   
	                    </a> 
	                </div>
	            </div> 
    	            </li> 
    	        </ul>
        </div>
    </div>
</div>
                            </div>
            <!-- END CONTENT AREA -->

<?php

include "redesign_javascript.php";
include "redesign_footer.php";

?>