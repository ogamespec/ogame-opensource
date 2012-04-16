<?php

// TODO : список событий
// TODO : панель ресурсов
// TODO : офицеры
// TODO : иконка атаки
// TODO : новые сообщения
// TODO : META - альянс
// TODO : время сервера

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html> 
    <head>
        <link rel="apple-touch-icon" href="red_images/20da7e6c416e6cd5f8544a73f588e5.png"/>
        <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<meta http-equiv="X-UA-Compatible" content="IE=7"/>
<meta name="ogame-session" content="<?=$session;?>"/>
<meta name="ogame-version" content="0.8.4"/>
<meta name="ogame-timestamp" content="<?=$now;?>"/>
<meta name="ogame-universe" content="<?=hostname();?>"/>
<meta name="ogame-universe-speed" content="<?=$GlobalUni['speed'];?>"/>
<meta name="ogame-language" content="<?=$GlobalUser['lang'];?>"/>
<meta name="ogame-player-id" content="<?=$GlobalUser['player_id'];?>"/>
<meta name="ogame-player-name" content="<?=$GlobalUser['oname'];?>"/>
<meta name="ogame-alliance-id" content="0"/>
<meta name="ogame-alliance-name" content="Name"/>
<meta name="ogame-alliance-tag" content="Tag"/>
<meta name="ogame-planet-id" content="<?=$aktplanet['planet_id'];?>"/>
<meta name="ogame-planet-name" content="<?=$aktplanet['name'];?>"/>
<meta name="ogame-planet-coordinates" content="<?=$aktplanet['g'];?>:<?=$aktplanet['s'];?>:<?=$aktplanet['p'];?>"/>
<meta name="ogame-planet-type" content="<?php if($aktplanet['type'] == 0) { echo "moon"; } else {echo "planet"; } ?>"/>
<link rel="stylesheet" type="text/css" href="red_css/9cba508bf487384321a92ee68144e4.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/443dc7a9c57e71bdd94be2afb5d6be.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/7f5cd54c0fdec17903f8ac4c9e1020.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/525f931477f9f060322ae4f814794b.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/53de9cb06e2659c056b84c64ffe7ef.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/4ec99f671704a6cd6fe6887f3685e2.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/1078e92959f3090d99397615abaa67.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/cc7a154f3186f1e5776efeba902bf8.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/cd69be5140eb480c6015f56700f75c.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/7265b48a83720be5745b8c249b072b.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/d2e0201b5fd0855780de262522fc8d.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/d754e4c497e93186782db737e05fc8.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/c6cc11701208612c4b2058be0d50d8.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/772b2d3c0e4346f2deb93edfcdbdf1.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/a118fd8a870308fc26713a38a52cf0.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/191b1e4950ccf07fe39a4b339877f7.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/5b59e9ae97d557f7ced89d29200cce.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/6ede3072b603ddfe67d7537b64be6d.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/a5f6217411b85b7587ac0b74065df2.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/42366c0bd29ce49bb28e55226151af.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/a50d68dac8dfc5440fe9313223c25b.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/61bfbe660c957390107a232dd055db.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/e74ebc8d70274b9e33a8b9796bf1a5.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/e9fc982e12ac3ada43f1f52da11325.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/946a36b07fab1ccadb37905a455c42.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/f4996eca461e169934abd888ee3409.css" media="screen" />
<!--[if IE 6]>
<link rel="stylesheet" type="text/css" href="red_css/80f5848e31384cb807818228389547.css" media="screen" />
<![endif]-->
<!--[if IE 7]>
<link rel="stylesheet" type="text/css" href="red_css/e75a4bb4e9dc0ce3f7f7aac6a9c699.css" media="screen" />
<![endif]-->
<!--[if IE 8]>
<link rel="stylesheet" type="text/css" href="red_css/2af7131df7c8210e7cd0bea1f3d9ff.css" media="screen" />
<![endif]-->

<?php

// Выйти из фрейма.
if ( $_GET['lgn'] == 1 )
{
    echo "<script>\n";
    echo "top.location.href=\"index.php?page=overview&session=$session\"";
    echo "</script>\n";
}

?>

        <title>Вселенная <?=$GlobalUni['num'];?> ОГейм</title>
            </head>

    <body id="<?=$_GET['page'];?>">
        <div class="contentBoxBody">
            <noscript>
                <div id="messagecenter">
                    <div id="javamessagebox">
                        <span class="overmark">
                            <strong>Пожалуйста, активируйте JavaScript для продолжения игры.</strong>
                        </span>
                    </div>
                </div>
            </noscript>
            <div id="ie_message">
                <p><img src="red_images/e621aa80dbd4746a9f4f114c8d3853.gif" height="16" width="16" />Версия вашего браузера устарела и может привести к некорректному отображению элементов игры . Пожалуйста, обновите ваш браузер до версии: <a href="http://www.microsoft.com/upgrade/">Internet Explorer</a> или <a href="http://www.mozilla-europe.org/de/firefox/">Mozilla Firefox</a></p>
            </div>
            
            <!-- HEADER -->
            <!-- ONET 4 POLAND -->

<div id="boxBG">
<div id="box">
	<a name="anchor"></a>
        <div id="info" class="header normal">
            <a href="index.php?page=overview&session=<?=$session;?>"><img src="red_images/preload.gif" id="logoLink" /></a>
	<div id="star"></div>
	<div id="star1"></div>
	<div id="star2"></div>
		<div id="clearAdvice"></div>
	   	<a class="tipsStandard"
	   	   title="|Последние изменения"
	   	   id="changelog_link"
	   	   href="index.php?page=changelog&session=<?=$session;?>">
	   	       Последние изменения	   	   </a>
        <div id="bar">
            <ul>
                <li id="playerName">
                    Игрок:
                    <span class="textBeefy">
                            <?=$GlobalUser['oname'];?>                        </span>
                                    </li>
                <li>
                                     <a class="ajax_thickbox"
                       accesskey=""
                       href="index.php?page=buddies&session=<?=$session;?>&ajax=1&height=600&width=770&TB_iframe=1">
                       Друзья</a>
                                 </li>
				<li>
				    <a href="javascript:void(0);"
				       onclick="popupWindow('index.php?page=notices&session=<?=$session;?>&notice_id=','Notice','auto','no','0','0','no','690','470','no');"
				       accesskey="">
                        Заметки</a>
                </li>
                <li>
                	<a href="index.php?page=highscore&session=<?=$session;?>" accesskey="">Статистика</a>
    (<?=$GlobalUser['place1'];?>)                </li>
                <li><a class="ajax_thickbox"
                       href="index.php?page=search&session=<?=$session;?>&ajax=1&height=600&width=770&TB_iframe=1"
                       accesskey="">Поиск</a>
                </li>
                <li><a href="index.php?page=preferences&session=<?=$session;?>" accesskey="">Настройки</a></li>
                <li><a href="http://support.oldogame.ru/" target="_blank">Служба поддержки</a></li>
                <li><a href="index.php?page=logout&session=<?=$session;?>" accesskey="">Выход</a></li>
                <li id="OGameClock"><?=date("d.m.Y", $now);?> <span><?=date("H:i:s", $now);?></span></li>
			</ul>

        </div>    	<ul id="resources">
        	<li id="metal_box" class="metal tipsTitle"
            	title="Металл:| Доступно: &lt;span class=''&gt;28.650&lt;/span&gt;&lt;br&gt;Вместимость хранилищ: &lt;span class=''&gt;865.000&lt;/span&gt;&lt;br&gt;Сейчас производится: &lt;span class='undermark'&gt;+13.354&lt;/span&gt;">
                <img src="red_images/layout/ressourcen_metall.gif" />
                    <span class="value">
                        <span id="resources_metal" class="">
                            28.650                   </span>
                   </span>
            </li>
        	<li id="crystal_box" class="crystal tipsTitle"
            	title="Кристалл:| Доступно: &lt;span class=''&gt;22.194&lt;/span&gt;&lt;br&gt;Вместимость хранилищ: &lt;span class=''&gt;470.000&lt;/span&gt;&lt;br&gt;Сейчас производится: &lt;span class='undermark'&gt;+6.832&lt;/span&gt;">
                <img src="red_images/layout/ressourcen_kristal.gif" />
                <span class="value">
                    <span id="resources_crystal" class="">
                        22.194                </span>
                </span>
            </li>
        	<li id="deuterium_box" class="deuterium tipsTitle"
            	title="Дейтерий:| Доступно: &lt;span class=''&gt;118.755&lt;/span&gt;&lt;br&gt;Вместимость хранилищ: &lt;span class=''&gt;255.000&lt;/span&gt;&lt;br&gt;Сейчас производится: &lt;span class='undermark'&gt;+3.439&lt;/span&gt;">
                <img src="red_images/layout/ressourcen_deuterium.gif" />
                <span class="value">
                	<span id="resources_deuterium" class="">
                        118.755               	</span>
               	</span>
            </li>
        	<li id="energy_box" class="energy tipsTitle"
            	title="Энергия:| Доступно: &lt;span class=''&gt;22&lt;/span&gt;&lt;br/&gt;Сейчас производится:: &lt;span class='undermark'&gt;+12.577&lt;/span&gt;&lt;br/&gt;Потребление: &lt;span class='overmark'&gt;-12.555&lt;/span&gt;">
				<img src="red_images/layout/ressourcen_energie.gif" />
                    <span class="value">
                    	<span id="resources_energy" class="">
							22						</span>
                    </span>
            </li>
        	<li id="darkmatter_box" class="darkmatter tipsTitle"
            	title="Темная Материя:| Доступно: &lt;span class=''&gt;<?=nicenum($GlobalUser['dm'] + $GlobalUser['dmfree']);?>&lt;/span&gt;&lt;br/&gt;Покупная: &lt;span class=''&gt;<?=nicenum($GlobalUser['dm']);?>&lt;/span&gt;&lt;br/&gt;Найденная: &lt;span class=''&gt;<?=nicenum($GlobalUser['dmfree']);?>&lt;/span&gt;">
				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=1">
					<img src="red_images/layout/ressourcen_DM.gif" />
				</a>
                <span class="value">
                	<span id="resources_darkmatter">
						<?=nicenum($GlobalUser['dm'] + $GlobalUser['dmfree']);?>					</span>
                </span>
            </li>
      </ul>
      	<div id="officers">
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=2" class="tipsTitle on  pic1" title="Нанять Командира|Активен ещё 62 дней.">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=3" class="tipsTitle on  pic2" title="Нанять адмирала|Активен ещё 7 дней.">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=4" class="tipsTitle   pic3" title="Нанять инженера|Сокращает вдвое потери в обороне, +10% больше энергии">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=5" class="tipsTitle on  pic4" title="Нанять геолога|Активен ещё 80 дней.">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=6" class="tipsTitle   pic5" title="Технократ|+2 уровень шпионажа для зондов, 25% меньше времени на исследования">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
        </div>
 		<div id="message-wrapper">
  		    <div>
	                <a href="index.php?page=messages&session=<?=$session;?>" id="message_alert_box_default" class="tipsStandard emptyMessage" title="|Новых сообщений: 0">
		            <span>
		                		            </span>
	        	</a>
        	</div>
			<div id="messages_collapsed" style="position:relative;">
        		<div id="eventboxFilled" style="display: none;">
	<table border="0" width="100%" id="eventtype" style="border-collapse: collapse;">
		<tr>
	        <td width="152" class="friendly col1">Собственные миссии: <span id="eventFriendly"></span></td>
	        <td width="156" class="neutral col2">Дружественные миссии: <span id="eventNeutral"></span></td>
	        <td width="152" class="hostile col3">Вражеские миссии: <span id="eventHostile"></span></td>
		</tr>
	</table>
	<table border="0" width="100%" id="eventdetails" style="border-collapse: collapse;">
		<tr id="eventClass" class="">
	    	<td width="152" class="col1"><div class="countdown" id="tempcounter" name="countdown"></div></td>
	        <td width="208" class="col2"><div class="text" id="eventContent"></div></td>
	        <td width="100" class="col3">
	            <a class="tipsStandard"
	            href="javascript:void(0);"
	            title="|Больше информации"></a>
			</td>
		</tr>
	</table>
</div>
<div id="eventboxLoading" class="textCenter textBeefy" style="display: block;">
	<img height="16" width="16" src="red_images/3f9884806436537bdec305aa26fc60.gif" />загрузка...</div>
<div id="eventboxBlank" class="textCenter" style="display: none;">
Нет передвижения флотов</div>			</div>
						<div id="attack_alert" style="visibility:hidden;">
                <a href="index.php?page=eventList&session=<?=$session;?>"
                    class="tipsStandard eventToggle"
                    title="|Атака!">
	                    <img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" height="13" width="25"/>
                </a>
	        </div>
	        <br class="clearfloat" />
		</div><!-- #message-wrapper -->
<?/*		<div id="helper">
            <a class="tipsStandard" 
               href="index.php?page=tutorial&session=<?=$session;?>"
               title="|Курс обучения">
            </a>
        </div> */?>
        <div id="selectedPlanetName" class="textCenter">Колония</div>
 </div><!-- Info -->

<!-- ERRORBOX -->
<div id="decisionTB" style="display:none;">
    <div id="errorBoxDecision">
        <div id="wrapper">
            <h4 id="errorBoxDecisionHead">-</h4>
            <p id="errorBoxDecisionContent">-</p>
            <div id="response">
                <div style="float:left; width:195px; height:25px;">
                    <a href="javascript:void(0);" onClick="handleErrorBoxClick('yes');return false;" class="yes"><span id="errorBoxDecisionYes">.</span></a>
                </div>
                <div style="float:left; width:195px; height:25px;">
                    <a href="javascript:void(0);" onClick="handleErrorBoxClick('no');return false;" class="no"><span id="errorBoxDecisionNo">.</span></a>
                </div>
                <br class="clearfloat" />
            </div>
        </div>    
    </div> 
</div>

<div id="fadeBox" class="fadeBox" style="display:none;">
  <div>
        <span id="fadeBoxStyle" class="success"></span>
    <p id="fadeBoxContent"></p>
    <br class="clearfloat" />
  </div>
</div>

<div id="notifyTB" style="display:none;">
   <div id="errorBoxNotify">
        <div id="wrapper">
            <h4 id="errorBoxNotifyHead">-</h4>
            <p id="errorBoxNotifyContent">-</p>
            <div id="response">
                <div>
                    <a href="javascript:void(0);" onClick="handleErrorBoxClick('ok');return false;" class="ok">
                        <span id="errorBoxNotifyOk">.</span>
                    </a>
                </div>
                <br class="clearfloat" />
            </div>
        </div>    
    </div> 
</div>

<div id="promotion" style="display:none;">
   <div id="promotionBox">
        <div id="wrapper">
            <h4 id="promotionBoxHead">-</h4>
            <div id="promotionBoxContent">
                <div class="fleft" id="promotionBoxText">-</div>
                <div class="" id="promotionBoxPicture">-</div>
            </div>
            
            <div id="response">
                <div style="float:left; width:195px; height:25px;">
                    <a href="javascript:void(0);" onClick="handleErrorBoxClick('yes');return false;" class="yes"><span id="promotionBoxYes">.</span></a>
                </div>
                <div style="float:left; width:195px; height:25px;">
                    <a href="javascript:void(0);" onClick="handleErrorBoxClick('no');return false;" class="no"><span id="promotionBoxNo">.</span></a>
                </div>
                <br class="clearfloat" />
            </div>
        </div>
    </div>
</div>
<!-- END ERRORBOX -->

            <!-- END HEADER -->
