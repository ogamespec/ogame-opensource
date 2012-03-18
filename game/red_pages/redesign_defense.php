<?php

// Redesign : Оборона

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], (int)$_GET['cp']);
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
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
    <div id="planet" style="background-image:url(red_images/cba948ca4bc3f7fc3494c83a6ca3e4.jpg)">
        <div id='header_text'>


                        <h2>Оборона - <?=$aktplanet['name'];?></h2>



        </div>
        
        <form method="POST" action="index.php?page=defense&session=<?=$session;?>" name="form" onkeydown="sendBuildRequest(null, event);" onsubmit="return false;">
            <input type='hidden' name='token' value='014f1bfc7daf29ce8eb7b8ee0bf8c7a5' />            <div id="detail" class="detail_screen">
                <div id="techDetailLoading"></div>
            </div>
        </form>
        
    </div>
    <div class="c-left"></div>
    <div class="c-right"></div>    
    <div id="buttonz">
        <h2>Структура</h2>    
        <ul id="defensebuilding">
            <li id="defense1" class="on">
                <div class="defense401">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Ракетная установка (1.173)" ref="401" id="details401" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Ракетная установка                                </span>
                                1.173                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                <li id="defense2" class="on">
                <div class="defense402">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Лёгкий лазер (181)" ref="402" id="details402" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Лёгкий лазер                                </span>
                                181                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                <li id="defense3" class="on">
                <div class="defense403">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Тяжёлый лазер (90)" ref="403" id="details403" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Тяжёлый лазер                                </span>
                                90                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                <li id="defense4" class="on">
                <div class="defense404">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Пушка Гаусса (17)" ref="404" id="details404" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Пушка Гаусса                                </span>
                                17                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                <li id="defense5" class="on">
                <div class="defense405">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Ионное орудие (0)" ref="405" id="details405" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Ионное орудие                                </span>
                                0                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                <li id="defense6" class="disabled">
                <div class="defense406">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Плазменное орудие (20)<br/>Недостаточно ресурсов!" ref="406" id="details406" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Плазменное орудие                                </span>
                                20                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                <li id="defense7" class="disabled">
                <div class="defense407">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Малый щитовой купол (1)<br/>Щиты можно строить только один раз" ref="407" id="details407" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Малый щитовой купол                                </span>
                                1                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                <li id="defense8" class="disabled">
                <div class="defense408">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Большой щитовой купол (1)<br/>Щиты можно строить только один раз" ref="408" id="details408" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Большой щитовой купол                                </span>
                                1                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                <li id="defense9" class="disabled">
                <div class="defense502">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Ракета-перехватчик (20)<br/>Недостаточно вместительности. Усовершенствуйте ракетную шахту." ref="502" id="details502" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Ракета-перехватчик                                </span>
                                20                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                <li id="defense10" class="off">
                <div class="defense503">
                <div class="buildingimg">
                    <a class="detail_button tipsStandard slideIn" title="|Межпланетная ракета (0)<br/>Требования не выполнены" ref="503" id="details503" href="javascript:void(0);">   
                        <span class="ecke">
                            <span class="level">
                                <span class="textlabel">
                                    Межпланетная ракета                                </span>
                                0                            </span>
                        </span>                   
                    </a> 
                </div>
            </div> 
                </li> 
                                                                                                                         
       </ul>
    </div>
</div>                            </div>
            <!-- END CONTENT AREA -->

<?php

include "redesign_javascript.php";
include "redesign_footer.php";

?>