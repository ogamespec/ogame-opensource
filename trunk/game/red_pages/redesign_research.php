<?php

// Redesign : Исследования

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
        
    <div id="planet" style="background-image:url(red_images/012a3f65e87e1b74571ea32eb6f5d9.jpg)">
        <div id="header_text">


                        <h2>Исследования - <?=$aktplanet['name'];?></h2>


        </div>

        <form method="POST" action="http://uni106.ogame.ru/game/index.php?page=research&session=<?=$session;?>" name="form">
            <div id="detail" class="detail_screen">
                <div id="techDetailLoading"></div>
            </div>
        </form>
        
    </div>
    <div class="c-left"></div>
    <div class="c-right"></div>
    
	<div id="buttonz" class="wrapButtons">
		<div id="wrapDrive" class="resLeft fleft IEinline">
        	<h3>Базовые исследования</h3>
            <ul id="base" class="activate">
                <li class="disabled">
	                    <div class="research113">
                            <div class="buildingimg">
                                                                <a href="javascript:void(0);"
                                    ref="113"
                                    id="details113"
                                    title="|Энергетическая технология<br/>Недостаточно ресурсов!"
                                    class="detail_button tipsStandard slideIn">
                                    <span class="ecke">
                                        <span class="level"><span class="textlabel">Энергетическая технология </span>12</span>
                                    </span>
                                </a>
                            </div>
                    </div>
	                </li>
                <li class="disabled">
	                    <div class="research120">
                            <div class="buildingimg">
                                                                <a href="javascript:void(0);"
                                    ref="120"
                                    id="details120"
                                    title="|Лазерная технология<br/>Недостаточно ресурсов!"
                                    class="detail_button tipsStandard slideIn">
                                    <span class="ecke">
                                        <span class="level"><span class="textlabel">Лазерная технология </span>12</span>
                                    </span>
                                </a>
                            </div>
                    </div>
	                </li>
                <li class="disabled">
	                    <div class="research121">
                            <div class="buildingimg">
                                                                <a href="javascript:void(0);"
                                    ref="121"
                                    id="details121"
                                    title="|Ионная технология<br/>В данный момент проводится исследование"
                                    class="detail_button tipsStandard slideIn">
                                    <span class="ecke">
                                        <span class="level"><span class="textlabel">Ионная технология </span>5</span>
                                    </span>
                                </a>
                            </div>
                    </div>
	                </li>
                <li class="disabled">
	                    <div class="research114">
                            <div class="buildingimg">
                                                                <a href="javascript:void(0);"
                                    ref="114"
                                    id="details114"
                                    title="|Гиперпространственная технология<br/>Недостаточно ресурсов!"
                                    class="detail_button tipsStandard slideIn">
                                    <span class="ecke">
                                        <span class="level"><span class="textlabel">Гиперпространственная технология </span>8</span>
                                    </span>
                                </a>
                            </div>
                    </div>
	                </li>
                <li class="disabled">
	                    <div class="research122">
                            <div class="buildingimg">
                                                                <a href="javascript:void(0);"
                                    ref="122"
                                    id="details122"
                                    title="|Плазменная технология<br/>Недостаточно ресурсов!"
                                    class="detail_button tipsStandard slideIn">
                                    <span class="ecke">
                                        <span class="level"><span class="textlabel">Плазменная технология </span>7</span>
                                    </span>
                                </a>
                            </div>
                    </div>
	                </li>
            <br class="clearfloat" />
            </ul>
        </div>    	<div id="wrapDrive" class="resRight fleft IEinline">
        	<h3>Исследования двигателей</h3>
            <ul id="base2" class="activate">
				<li class="disabled">
						<div class="research115">
						<div class="buildingimg">
                                                    							 <a href="javascript:void(0);"
                                                            ref="115"
                                                            id="details115"
                                                            title="|Реактивный двигатель<br/>Недостаточно ресурсов!"
                                                            class="detail_button tipsStandard slideIn">
                                                            <span class="ecke">
                                                                <span class="level"><span class="textlabel">Реактивный двигатель </span>11</span>
                                                            </span>
                                                        </a>
						</div>
					</div>
					</li>
				<li class="disabled">
						<div class="research117">
						<div class="buildingimg">
                                                    							 <a href="javascript:void(0);"
                                                            ref="117"
                                                            id="details117"
                                                            title="|Импульсный двигатель<br/>Недостаточно ресурсов!"
                                                            class="detail_button tipsStandard slideIn">
                                                            <span class="ecke">
                                                                <span class="level"><span class="textlabel">Импульсный двигатель </span>9</span>
                                                            </span>
                                                        </a>
						</div>
					</div>
					</li>
				<li class="disabled">
						<div class="research118">
						<div class="buildingimg">
                                                    							 <a href="javascript:void(0);"
                                                            ref="118"
                                                            id="details118"
                                                            title="|Гиперпространственный двигатель<br/>Недостаточно ресурсов!"
                                                            class="detail_button tipsStandard slideIn">
                                                            <span class="ecke">
                                                                <span class="level"><span class="textlabel">Гиперпространственный двигатель </span>6</span>
                                                            </span>
                                                        </a>
						</div>
					</div>
					</li>
            <br class="clearfloat" />
            </ul>
        </div>		<div id="wrapMilitary" class="resLeft fleft IEinline">
        	<h3>Продвинутые исследования</h3>
            <ul id="base3" class="activate">
				<li class="disabled">
						<div class="research106">
						<div class="buildingimg">
                                                                                                         <a href="javascript:void(0);"
                                                        ref="106"
                                                        id="details106"
                                                        title="|Шпионаж<br/>Недостаточно ресурсов!"
                                                        class="detail_button tipsStandard slideIn">
                                                    <span class="ecke">
                                                        <span class="level"><span class="textlabel">Шпионаж </span>11                                                            <span class="undermark">
                                                                                                                            </span>
                                                        </span>
                                                    </span>
                                                    </a>
						</div>
					</div>
					</li>
				<li class="disabled">
						<div class="research108">
						<div class="buildingimg">
                                                                                                         <a href="javascript:void(0);"
                                                        ref="108"
                                                        id="details108"
                                                        title="|Компьютерная технология<br/>Недостаточно ресурсов!"
                                                        class="detail_button tipsStandard slideIn">
                                                    <span class="ecke">
                                                        <span class="level"><span class="textlabel">Компьютерная технология </span>11                                                            <span class="undermark">
                                                                                                                            </span>
                                                        </span>
                                                    </span>
                                                    </a>
						</div>
					</div>
					</li>
				<li class="disabled">
						<div class="research124">
						<div class="buildingimg">
                                                                                                         <a href="javascript:void(0);"
                                                        ref="124"
                                                        id="details124"
                                                        title="|Астрофизика<br/>Недостаточно ресурсов!"
                                                        class="detail_button tipsStandard slideIn">
                                                    <span class="ecke">
                                                        <span class="level"><span class="textlabel">Астрофизика </span>14                                                            <span class="undermark">
                                                                                                                            </span>
                                                        </span>
                                                    </span>
                                                    </a>
						</div>
					</div>
					</li>
				<li class="disabled">
						<div class="research123">
						<div class="buildingimg">
                                                                                                         <a href="javascript:void(0);"
                                                        ref="123"
                                                        id="details123"
                                                        title="|Межгалактическая исследовательская сеть<br/>Недостаточно ресурсов!"
                                                        class="detail_button tipsStandard slideIn">
                                                    <span class="ecke">
                                                        <span class="level"><span class="textlabel">Межгалактическая исследовательская сеть </span>3                                                            <span class="undermark">
                                                                                                                            </span>
                                                        </span>
                                                    </span>
                                                    </a>
						</div>
					</div>
					</li>
				<li class="off">
						<div class="research199">
						<div class="buildingimg">
                                                                                                         <a href="javascript:void(0);"
                                                        ref="199"
                                                        id="details199"
                                                        title="|Гравитационная технология<br/>Требования не выполнены"
                                                        class="detail_button tipsStandard slideIn">
                                                    <span class="ecke">
                                                        <span class="level"><span class="textlabel">Гравитационная технология </span>0                                                            <span class="undermark">
                                                                                                                            </span>
                                                        </span>
                                                    </span>
                                                    </a>
						</div>
					</div>
					</li>
            <br class="clearfloat" />
            </ul>
        </div>    	<div id="wrapBattle" class="resRight fleft IEinline">
        	<h3>Боевые исследования</h3>
            <ul id="base4" class="activate">
				<li class="disabled">
						<div class="research109">
						<div class="buildingimg">
                                                                                                         <a href="javascript:void(0);"
                                                        ref="109"
                                                        id="details109"
                                                        title="|Оружейная технология<br/>Недостаточно ресурсов!"
                                                        class="detail_button tipsStandard slideIn">
                                                        <span class="ecke">
                                                            <span class="level"><span class="textlabel">Оружейная технология </span>12</span>
                                                        </span>
                                                    </a>
						</div>
					</div>
					</li>
				<li class="off">
			<div class="research110 tipsStandard" title="|Щитовая технология<br/>(Колония [1:110:9])">  
	<div class="buildingimg">
 
		<div class="construction">
            <div class="pusher" id="b_research110" style="height:80px;margin-bottom:-80px;">
            	<a id="timeLink" class="slideIn" href="javascript:void(0);" ref="110">
                	<span class="time" id="test" name="zeit"></span>
                </a>
            </div>      
			<a class="detail_button slideIn"
               id="details110"
               ref="110"
               href="javascript:void(0);">
				<span class="eckeoben">
					<span style="font-size:11px;" class="undermark">12</span>
				</span>
				<span class="ecke">
					<span class="level">11                                                                                    </span>
				</span>
			</a>
		</div>  		
	</div> 
</div>      

					</li>
				<li class="disabled">
						<div class="research111">
						<div class="buildingimg">
                                                                                                         <a href="javascript:void(0);"
                                                        ref="111"
                                                        id="details111"
                                                        title="|Броня космических кораблей<br/>Недостаточно ресурсов!"
                                                        class="detail_button tipsStandard slideIn">
                                                        <span class="ecke">
                                                            <span class="level"><span class="textlabel">Броня космических кораблей </span>12</span>
                                                        </span>
                                                    </a>
						</div>
					</div>
					</li>
            <br class="clearfloat" />
            </ul>
        </div>        <div class="extra_background">&nbsp;</div>
        <br class="clearfloat" />
    </div></div>
                            </div>
            <!-- END CONTENT AREA -->

<?php

include "redesign_javascript.php";
include "redesign_footer.php";

?>