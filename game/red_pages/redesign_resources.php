<?php

// Redesign : Сырье

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
    <div id="planet" style="background-image:url(red_images/resources/ice_1_2_3_4_12_212.png)">
        <div id="header_text">


                        <h2>Сырьё - <?=$aktplanet['name'];?></h2>
            

        </div>
        <div id="slot01" class="slot">
            <a href="index.php?page=resourceSettings&session=<?=$session;?>">
				Производство сырья            </a>
        </div>

        <form method="POST" action="index.php?page=resources&session=<?=$session;?>" name="form" onkeydown="sendBuildRequest(null, event);" onsubmit="return false;">
            <input type='hidden' name='token' value='ee0ce7ff26eaac29a3a020b14ea00b24' />	        <div id="detail" class="detail_screen">
	            <div id="techDetailLoading"></div>
	        </div>
        </form>

    </div>
    <div class="c-left"></div>
    <div class="c-right"></div>
    
    
    

<div id="buttonz">
    <h2>
        Производственные постройки	</h2>
    <ul id="building">
        
        <li id="button1" class="disabled">
    	            <div class="supply1">
	                <div class="buildingimg">
        	                    <a class="detail_button tipsStandard slideIn"
	                       title="|Рудник по добыче металла<br/>Недостаточно ресурсов!"
	                       ref="1"
	                       id="details" href="javascript:void(0);">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Рудник по добыче металла 
	                               </span>
	                               30	                           </span>
	                        </span>
	                    </a>
	                </div>
	            </div>
                </li>
            
        <li id="button2" class="disabled">
    	            <div class="supply2">
	                <div class="buildingimg">
        	                    <a class="detail_button tipsStandard slideIn"
	                       title="|Рудник по добыче кристалла<br/>Недостаточно ресурсов!"
	                       ref="2"
	                       id="details" href="javascript:void(0);">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Рудник по добыче кристалла 
	                               </span>
	                               26	                           </span>
	                        </span>
	                    </a>
	                </div>
	            </div>
                </li>
            
        <li id="button3" class="disabled">
    	            <div class="supply3">
	                <div class="buildingimg">
        	                    <a class="detail_button tipsStandard slideIn"
	                       title="|Синтезатор дейтерия<br/>Недостаточно ресурсов!"
	                       ref="3"
	                       id="details" href="javascript:void(0);">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Синтезатор дейтерия 
	                               </span>
	                               26	                           </span>
	                        </span>
	                    </a>
	                </div>
	            </div>
                </li>
            
        <li id="button4" class="disabled">
    	            <div class="supply4">
	                <div class="buildingimg">
        	                    <a class="detail_button tipsStandard slideIn"
	                       title="|Солнечная электростанция<br/>Недостаточно ресурсов!"
	                       ref="4"
	                       id="details" href="javascript:void(0);">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Солнечная электростанция 
	                               </span>
	                               24	                           </span>
	                        </span>
	                    </a>
	                </div>
	            </div>
                </li>
            
        <li id="button5" class="disabled">
    	            <div class="supply12">
	                <div class="buildingimg">
        	                    <a class="detail_button tipsStandard slideIn"
	                       title="|Термоядерная электростанция<br/>Недостаточно ресурсов!"
	                       ref="12"
	                       id="details" href="javascript:void(0);">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Термоядерная электростанция 
	                               </span>
	                               9	                           </span>
	                        </span>
	                    </a>
	                </div>
	            </div>
                </li>
            
        <li id="button6" class="on">
    	            <div class="supply212">
	                <div class="buildingimg">
        	                    <a class="detail_button tipsStandard slideIn"
	                       title="|Солнечный спутник (348)"
	                       ref="212"
	                       id="details" href="javascript:void(0);">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Солнечный спутник 
	                               </span>
	                               348	                           </span>
	                        </span>
	                    </a>
	                </div>
	            </div>
                </li>
                                    </ul>
    
        <div id="resourceSettings">
            <form method="POST" action="#">
            <input type="hidden" name="session" value="<?=$session;?>">
            <input type="hidden" name="saveSettings" value="1">
            
            <table cellpadding="0" class="list" cellspacing="0"style="margin-top:0px;">
                <tr class="">

                    <td colspan="3" align="right">
                        <b>Управление энергией</b>
                    </td>

                </tr>


                                <tr class="alt">
                    <td valign="top">
                        <span class="energyManagerIcon metall"></span>
                    </td>
                    <td class="label" width="100%">
                        <b>Рудник по добыче металла</b><br/>
                        <span class="smallFont">
                            Необходимо энергии <span class="undermark">5.235</span>/5.235<br/>
                            Сейчас производится: <span class="undermark">17.274</span>
                        </span>
                    </td>

                    <td width="100%" align="right">
                        <select name="last1" size="1" class="undermark">
                                <option class="undermark"
                                    value="100" selected>100%</option>
                                <option class="undermark"
                                    value="90" >90%</option>
                                <option class="undermark"
                                    value="80" >80%</option>
                                <option class="undermark"
                                    value="70" >70%</option>
                                <option class="middlemark"
                                    value="60" >60%</option>
                                <option class="middlemark"
                                    value="50" >50%</option>
                                <option class="middlemark"
                                    value="40" >40%</option>
                                <option class="overmark"
                                    value="30" >30%</option>
                                <option class="overmark"
                                    value="20" >20%</option>
                                <option class="overmark"
                                    value="10" >10%</option>
                                <option class="overmark"
                                    value="0" >0%</option>
                            </select>
                    </td>
                </tr>
                                                <tr class="">
                    <td valign="top">
                        <span class="energyManagerIcon crystal"></span>
                    </td>
                    <td class="label" width="100%">
                        <b>Рудник по добыче кристалла</b><br/>
                        <span class="smallFont">
                            Необходимо энергии <span class="undermark">3.099</span>/3.099<br/>
                            Сейчас производится: <span class="undermark">6.817</span>
                        </span>
                    </td>

                    <td width="100%" align="right">
                        <select name="last2" size="1" class="undermark">
                                <option class="undermark"
                                    value="100" selected>100%</option>
                                <option class="undermark"
                                    value="90" >90%</option>
                                <option class="undermark"
                                    value="80" >80%</option>
                                <option class="undermark"
                                    value="70" >70%</option>
                                <option class="middlemark"
                                    value="60" >60%</option>
                                <option class="middlemark"
                                    value="50" >50%</option>
                                <option class="middlemark"
                                    value="40" >40%</option>
                                <option class="overmark"
                                    value="30" >30%</option>
                                <option class="overmark"
                                    value="20" >20%</option>
                                <option class="overmark"
                                    value="10" >10%</option>
                                <option class="overmark"
                                    value="0" >0%</option>
                            </select>
                    </td>
                </tr>
                                                <tr class="alt">
                    <td valign="top">
                        <span class="energyManagerIcon deuterium"></span>
                    </td>
                    <td class="label" width="100%">
                        <b>Синтезатор дейтерия</b><br/>
                        <span class="smallFont">
                            Необходимо энергии <span class="undermark">6.198</span>/6.198<br/>
                            Сейчас производится: <span class="undermark">4.758</span>
                        </span>
                    </td>

                    <td width="100%" align="right">
                        <select name="last3" size="1" class="undermark">
                                <option class="undermark"
                                    value="100" selected>100%</option>
                                <option class="undermark"
                                    value="90" >90%</option>
                                <option class="undermark"
                                    value="80" >80%</option>
                                <option class="undermark"
                                    value="70" >70%</option>
                                <option class="middlemark"
                                    value="60" >60%</option>
                                <option class="middlemark"
                                    value="50" >50%</option>
                                <option class="middlemark"
                                    value="40" >40%</option>
                                <option class="overmark"
                                    value="30" >30%</option>
                                <option class="overmark"
                                    value="20" >20%</option>
                                <option class="overmark"
                                    value="10" >10%</option>
                                <option class="overmark"
                                    value="0" >0%</option>
                            </select>
                    </td>
                </tr>
                
                <input type="hidden" name="last4" value="100">
                <input type="hidden" name="last12" value="100">
                <input type="hidden" name="last212" value="100">
                
                <tr>
                    <td>
                        <span class="energyManagerIcon energy"></span>
                    </td>
                    <td class="label" colspan="2">
                        <b>Энергетический баланс</b>
                                                    <span class="undermark">4</span>
                                                
                    </td>
                </tr>
                <tr class="alt">
                    <td id="factor" colspan="3" align="right">
                        <span class="factorbutton">
                            <input class="button188" style="" type="submit" value="Посчитать" />
                        </span>
                    </td>
                    <td>

                    </td>
                </tr>
            </table>
            </form>
	<br class="clearfloat" />
        </div>


        <ul id="storage">
                                        <li id="button7" class="on">
    	            <div class="supply22">
	                <div class="buildingimg">
	                    <a  class="tipsStandard slideIn"
	                        href="javascript:void(0);"
	                        title="|Хранилище металла"
	                        ref="22"
	                        id="details">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Хранилище металла	                               </span>
	                               7	                           </span>
	                        </span>
	                    </a>
                                <a class="fastBuild tipsStandard"
                           title="|Совершенствовать Хранилище металла до уровня 8"
                           href="javascript:void(0);"
                           onclick="sendBuildRequest('index.php?page=resources&session=<?=$session;?>&modus=1&type=22&menge=1&token=4b1563ef068c80dbdc516657d4b4da94');">
                            <img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif"
                                 width="22"
                                 height="14"
                                 alt="" />
                        </a>
        	                </div>
	            </div>
                </li>
                    <li id="button8" class="disabled">
    	            <div class="supply23">
	                <div class="buildingimg">
	                    <a  class="tipsStandard slideIn"
	                        href="javascript:void(0);"
	                        title="|Хранилище кристалла<br/>Недостаточно ресурсов!"
	                        ref="23"
	                        id="details">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Хранилище кристалла	                               </span>
	                               6	                           </span>
	                        </span>
	                    </a>
        	                </div>
	            </div>
                </li>
                    <li id="button9" class="disabled">
    	            <div class="supply24">
	                <div class="buildingimg">
	                    <a  class="tipsStandard slideIn"
	                        href="javascript:void(0);"
	                        title="|Ёмкость для дейтерия<br/>Недостаточно ресурсов!"
	                        ref="24"
	                        id="details">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Ёмкость для дейтерия	                               </span>
	                               5	                           </span>
	                        </span>
	                    </a>
        	                </div>
	            </div>
                </li>
                       </ul>

       <ul id="den">
                                                    <li id="button10" class="on">
    	            <div class="supply25">
	                <div class="buildingimg">
	                    <a  class="tipsStandard slideIn"
	                        href="#"
	                        title="|Укрытие для металла"
	                        ref="25"
	                        id="details">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Укрытие для металла	                               </span>
	                               0	                           </span>
	                        </span>
	                    </a>
                                <a class="fastBuild tipsStandard"
                           title="|Совершенствовать Укрытие для металла до уровня 1"
                           href="#"
                           onclick="sendBuildRequest('index.php?page=resources&session=<?=$session;?>&modus=1&type=25&menge=1&token=4b1563ef068c80dbdc516657d4b4da94');">
                            <img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif"
                                 width="22"
                                 height="14"
                                 alt="" />
                        </a>
        	                </div>
	            </div>
                </li>
                    <li id="button11" class="on">
    	            <div class="supply26">
	                <div class="buildingimg">
	                    <a  class="tipsStandard slideIn"
	                        href="#"
	                        title="|Укрытие для кристалла"
	                        ref="26"
	                        id="details">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Укрытие для кристалла	                               </span>
	                               0	                           </span>
	                        </span>
	                    </a>
                                <a class="fastBuild tipsStandard"
                           title="|Совершенствовать Укрытие для кристалла до уровня 1"
                           href="#"
                           onclick="sendBuildRequest('index.php?page=resources&session=<?=$session;?>&modus=1&type=26&menge=1&token=4b1563ef068c80dbdc516657d4b4da94');">
                            <img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif"
                                 width="22"
                                 height="14"
                                 alt="" />
                        </a>
        	                </div>
	            </div>
                </li>
                    <li id="button12" class="on">
    	            <div class="supply27">
	                <div class="buildingimg">
	                    <a  class="tipsStandard slideIn"
	                        href="#"
	                        title="|Укрытие для дейтерия"
	                        ref="27"
	                        id="details">
	                        <span class="ecke">
	                            <span class="level">
	                               <span class="textlabel">
	                                   Укрытие для дейтерия	                               </span>
	                               0	                           </span>
	                        </span>
	                    </a>
                                <a class="fastBuild tipsStandard"
                           title="|Совершенствовать Укрытие для дейтерия до уровня 1"
                           href="#"
                           onclick="sendBuildRequest('index.php?page=resources&session=<?=$session;?>&modus=1&type=27&menge=1&token=4b1563ef068c80dbdc516657d4b4da94');">
                            <img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif"
                                 width="22"
                                 height="14"
                                 alt="" />
                        </a>
        	                </div>
	            </div>
                </li>
           </ul>

    </div>

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
    <div class="header"><h3>Сейчас производится:</h3></div>
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
</div>                            </div>
            <!-- END CONTENT AREA -->

<?php

include "redesign_javascript.php";
include "redesign_footer.php";

?>