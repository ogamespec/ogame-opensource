<?php

// TODO : список событий
// TODO : панель ресурсов
// TODO : офицеры
// TODO : иконка атаки
// TODO : новые сообщения
// TODO : время сервера

$ally = LoadAlly ($GlobalUser['ally_id']);

?><!DOCTYPE html>
<html> 
    <head>
        <link rel="apple-touch-icon" href="red_images/20da7e6c416e6cd5f8544a73f588e5.png"/>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="Language" content="ru"/>
<meta name="ogame-session" content="<?=$session;?>"/>
<meta name="ogame-version" content="0.8.4"/>
<meta name="ogame-timestamp" content="<?=$now;?>"/>
<meta name="ogame-universe" content="<?=hostname();?>"/>
<meta name="ogame-universe-speed" content="<?=$GlobalUni['speed'];?>"/>
<meta name="ogame-language" content="<?=$GlobalUser['lang'];?>"/>
<meta name="ogame-player-id" content="<?=$GlobalUser['player_id'];?>"/>
<meta name="ogame-player-name" content="<?=$GlobalUser['oname'];?>"/>
<meta name="ogame-alliance-id" content="<?=$GlobalUser['ally_id'];?>"/>
<meta name="ogame-alliance-name" content="<?=$ally['name'];?>"/>
<meta name="ogame-alliance-tag" content="<?=$ally['tag'];?>"/>
<meta name="ogame-planet-id" content="<?=$aktplanet['planet_id'];?>"/>
<meta name="ogame-planet-name" content="<?=$aktplanet['name'];?>"/>
<meta name="ogame-planet-coordinates" content="<?=$aktplanet['g'];?>:<?=$aktplanet['s'];?>:<?=$aktplanet['p'];?>"/>
<meta name="ogame-planet-type" content="<?php if($aktplanet['type'] == 0) { echo "moon"; } else {echo "planet"; } ?>"/>
<link rel="stylesheet" type="text/css" href="red_css/27e7d9361f521de295adc31a17f18d.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/1b3369cba8dbb3832d5f49da5c8135.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/481b926b06d344f8ca623882375d0e.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/7eafa3ef9756c2fe8c12635d21651e.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/c8523f97e5489475c70f0de24b439f.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/cc01f09f801bf7584a8a34763c4847.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/60dbdc145e88feecdc62068c72b994.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/c21561691f76f9d725b98178c4a6d3.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/0253bfa253f20cd66851113ffebb4f.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/302885bbd703a45459325c8391e240.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/218cdf0c28426e9a81613f884cc970.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/8ff2cd93e9e2018ce8861f7946545f.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/176f6cad2847ec19e2ecbbe64842ba.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/1921384a0ba9a441f76fc13b7a0090.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/24beedb66361f36186048d7b53317e.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/7f173b015787554bc6314d4554635b.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/d7b69e255750584a44ac43ad673956.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/e5c67847e9fb89adca9903db8a8489.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/52b8b331794dd3d198fa19675ce212.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/8a2d45af11edb6205c4790a24bee0f.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/12105d086592d4d1a1ccf280be61b8.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/e35f3b130340abf73454c99607be0f.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/bcf1c4fe1dd14943ed1b63ba32d60b.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/c84ccf96aa6f489345994ea278b422.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/b1ca0d9ad64aecb7ff1f91fa5f0ea7.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/0510d5ca0f0fff0dbef580642c510a.css" media="screen" />
<link rel="stylesheet" type="text/css" href="red_css/026fa98f0884b77c1d5170ca81490f.css" media="screen" />
<!--[if IE 8]>
<link rel="stylesheet" type="text/css" href="red_css/74c0a523541a9cf601066f3f27063d.css" media="screen" />
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

    <body id="<?=$_GET['page'];?>"
          class="lang-ru no-touch">
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
            <script type="text/javascript">isIE = false;</script>
            <!--[IF IE]>
                <script type="text/javascript">
                    isIE = true;
                </script>
            <![endif]-->
            
            <!-- HEADER -->
            <!-- ONET 4 POLAND -->

<div id="boxBG">
<div id="box">
	<a name="anchor"></a>
        <div id="info" class="header normal">
            <a href="index.php?page=overview&session=<?=$session;?>"><img src="red_images/preload.gif" id="logoLink" /></a>
		<div id="clearAdvice"></div>
	   	<a id="changelog_link"
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
                    <a class="overlay"
                       accesskey=""
                       href="index.php?page=buddies&session=<?=$session;?>&action=9&ajax=1"
                            data-overlay-title="Друзья"
                            data-overlay-class="buddies"
                            >
                       Друзья</a>
                                 </li>
				<li>
				    <a href="index.php?page=notices&session=<?=$session;?>"
                       class="overlay" data-overlay-title="Мои заметки"
                       data-overlay-class="notes"
				       accesskey="">
                        Заметки</a>
                </li>
                <li>
                	<a href="index.php?page=highscore&session=<?=$session;?>" accesskey="">Статистика</a>
    (<?=$GlobalUser['place1'];?>)                </li>
                <li><a class="overlay"
                       href="index.php?page=search&session=<?=$session;?>&ajax=1"
                       data-overlay-title="Поиск"
                       accesskey="">Поиск</a>
                </li>
                <li><a href="index.php?page=preferences&session=<?=$session;?>" accesskey="">Настройки</a></li>
                <li><a href="http://support.oldogame.ru/" target="_blank">Служба поддержки</a></li>
                                    <li><a href="#" target="_blank">Чат</a></li>
                                <li><a href="index.php?page=logout&session=<?=$session;?>">Выход</a></li>
                <li class="OGameClock"><?=date("d.m.Y", $now);?> <span><?=date("H:i:s", $now);?></li>
			</ul>

        </div>    	<ul id="resources">
        	<li id="metal_box" class="metal tooltipHTML"
            	title="Металл:| &lt;table class=&quot;resourceTooltip&quot;&gt;
            &lt;tr&gt;
                &lt;th&gt;Доступно:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($aktplanet['m']);?>&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;th&gt;Вместимость хранилищ:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($aktplanet['mmax']);?>&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;th&gt;Сейчас производится:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;undermark&quot;&gt;+120&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
        &lt;/table&gt;">
                <img src="red_images/ccdb3fc0cb8f7b4fc8633f5f5eaa86.gif" />
                <span class="value">
                    <span id="resources_metal" class="">
                        516                    </span>
                </span>
            </li>
        	<li id="crystal_box" class="crystal tooltipHTML"
            	title="Кристалл:| &lt;table class=&quot;resourceTooltip&quot;&gt;
            &lt;tr&gt;
                &lt;th&gt;Доступно:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($aktplanet['k']);?>&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;th&gt;Вместимость хранилищ:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($aktplanet['kmax']);?>&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;th&gt;Сейчас производится:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;undermark&quot;&gt;+60&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
        &lt;/table&gt;">
                <img src="red_images/452d7fd11d754e0f09ec2b2350e063.gif" />
                <span class="value">
                    <span id="resources_crystal" class="">
                        508                    </span>
                </span>
            </li>
        	<li id="deuterium_box" class="deuterium tooltipHTML"
            	title="Дейтерий:| &lt;table class=&quot;resourceTooltip&quot;&gt;
            &lt;tr&gt;
                &lt;th&gt;Доступно:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($aktplanet['d']);?>&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;th&gt;Вместимость хранилищ:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($aktplanet['dmax']);?>&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;th&gt;Сейчас производится:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;overmark&quot;&gt;0&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
        &lt;/table&gt;">
                <img src="red_images/e37d45b77518ddf8bbccd5e772a395.gif" />
                <span class="value">
                    <span id="resources_deuterium" class="">
                        0                    </span>
               	</span>
            </li>
        	<li id="energy_box" class="energy tooltipHTML"
            	title="Энергия:| &lt;table class=&quot;resourceTooltip&quot;&gt;
            &lt;tr&gt;
                &lt;th&gt;Доступно:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($aktplanet['enow']);?>&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;th&gt;Сейчас производится:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;overmark&quot;&gt;<?=nicenum($aktplanet['emax']);?>&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;th&gt;Потребление:&lt;/th&gt;
                &lt;td&gt;&lt;span class=&quot;overmark&quot;&gt;0&lt;/span&gt;&lt;/td&gt;
            &lt;/tr&gt;
        &lt;/table&gt;">
                    <img src="red_images/0d68fe5d39bbb4c94a2372626ec83f.gif" />
                    <span class="value">
                    	<span id="resources_energy" class="">
                            0			</span>
                    </span>
            </li>
        	<li id="darkmatter_box" class="darkmatter dark_highlight_tablet">
                <a href="index.php?page=premium&session=<?=$session;?>&openDetail=1"
                        class="tooltipHTML "
                        title="Темная Материя:| &lt;table class=&quot;resourceTooltip&quot;&gt;
                &lt;tr&gt;
                    &lt;th&gt;Доступно:&lt;/th&gt;
                    &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($GlobalUser['dm'] + $GlobalUser['dmfree']);?>&lt;/span&gt;&lt;/td&gt;
                &lt;/tr&gt;
                &lt;tr&gt;
                    &lt;th&gt;Покупная:&lt;/th&gt;
                    &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($GlobalUser['dm']);?>&lt;/span&gt;&lt;/td&gt;
                &lt;/tr&gt;
                &lt;tr&gt;
                    &lt;th&gt;Найденная:&lt;/th&gt;
                    &lt;td&gt;&lt;span class=&quot;&quot;&gt;<?=nicenum($GlobalUser['dmfree']);?>&lt;/span&gt;&lt;/td&gt;
                &lt;/tr&gt;
            &lt;/table&gt;"
                        data-tooltip-button="Достать Тёмную материю">
                    <img src="red_images/401d1a91ff40dc7c8acfa4377d3d65.gif" />
                    <span class="value">
                        <span id="resources_darkmatter">
                            <?=nicenum($GlobalUser['dm'] + $GlobalUser['dmfree']);?>                        </span>
                    </span>
                </a>
            </li>
      </ul>
                
        

                  	<div id="officers" class="">
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=2" 
                           class="tooltipHTML   pic1 js_hideTipOnMobile" title="Нанять Командира|Очередь для строительства, обзор империи, усовершенствованный обзор галактики, фильтр сообщений, никакой рекламы* &lt;span style=&quot;font-size:10px;&quot;&gt;(*исключение: игровые события)&lt;/span&gt;">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=3" 
                           class="tooltipHTML   pic2 js_hideTipOnMobile" title="Нанять адмирала|Макс. кол-во флотов +2,
улучшенный отвод флотов">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=4" 
                           class="tooltipHTML   pic3 js_hideTipOnMobile" title="Нанять инженера|Сокращает вдвое потери в обороне, +10% больше энергии">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=5" 
                           class="tooltipHTML   pic4 js_hideTipOnMobile" title="Нанять геолога|+10% доход от шахты">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
      				<a href="index.php?page=premium&session=<?=$session;?>&openDetail=6" 
                           class="tooltipHTML   pic5 js_hideTipOnMobile" title="Нанять технократа|+2 уровень шпионажа для зондов, 25% меньше времени на исследования">
            	<img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" width="30" height="30"/>
            </a>
        </div>
 		<div id="message-wrapper">
  		    <div>
	                <a href="index.php?page=messages&session=<?=$session;?>" id="message_alert_box_default" class="tooltip js_hideTipOnMobile emptyMessage" title="Новых сообщений: 0">
		            <span>
		                		            </span>
	        	</a>
        	</div>
			<div id="messages_collapsed" style="position:relative;">
        		<div id="eventboxFilled" class="eventToggle" style="display: none;">
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
	            <a class="tooltipRight toggleDetailButton js_hideTipOnMobile"
	            href="javascript:void(0);"
	            title="Больше информации"></a>
			</td>
		</tr>
	</table>
</div>
<div id="eventboxLoading" class="textCenter textBeefy" style="display: block;">
	<img height="16" width="16" src="red_images/3f9884806436537bdec305aa26fc60.gif" />загрузка...</div>
<div id="eventboxBlank" class="textCenter" style="display: none;">
Нет передвижения флотов</div>			</div>
						<div id="attack_alert" style="visibility:hidden;">
                <a href="http://uni111.ogame.ru/game/index.php?page=eventList"
                    class="tooltip eventToggle"
                    title="Атака!">
	                    <img src="red_images/3e567d6f16d040326c7a0ea29a4f41.gif" height="13" width="25"/>
                </a>
	        </div>
	        <br class="clearfloat" />
		</div><!-- #message-wrapper -->

        <div id="selectedPlanetName" class="textCenter"><?=$aktplanet['name'];?></div>
 </div><!-- Info -->            <!-- END HEADER -->
