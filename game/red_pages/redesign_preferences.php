<?php

// Redesign : Настройки

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

?>

            <!-- CONTENT AREA -->
            <div id="contentWrapper">
                                    <div id="eventboxContent" style="display: none"><img height="16" width="16" src="red_images/3f9884806436537bdec305aa26fc60.gif" /></div>
                                
<div id="inhalt">

    <!-- HEADER -->
    <div id="planet">


                <h2>Настройки - <?=$GlobalUser['oname'];?></h2>


    </div>
    <div class="c-left"></div>
    <div class="c-right"></div>

    <!-- CONTENT -->
    <div id="content" style="color:#fff;">
        <div class="sectioncontent">
            <div class="contentzs">
                <!-- TABS -->
                <div class="tabwrapper">
                    <ul class="tabsbelow" id="tabs-pref">
                        <li>
                            <a href="#one" id="tabUserdata" >
                                <span>Данные пользователя</span>
                            </a>
                        </li>
                        <li>
                            <a href="#two" id="tabGeneral" >
                                <span>Общие</span>
                            </a>
                        </li>
                        <li>
                            <a href="#three" id="tabRepresentation" >
                                <span>Отображение</span>
                            </a>
                        </li>
                        <li>
                            <a href="#four" id="tabExtended" >
                                <span>Дополнительно</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- BASIC FORM DATA -->
                <form method="post" name="prefs" id="prefs" action="index.php?page=preferences">
                <input type="hidden" name="mode" value="save" />
                <input type="hidden" id="selectedTab" name="selectedTab" value="0" />

               	<div class="content">

                    <!-- USERDATA -->
                    <div id="one" class="wrap">
                            <div class="fieldwrapper alt bar">
                                <label class="styled textBeefy">Имя игрока</label>
                            </div>
							<div class="group bborder">
                                <div class="fieldwrapper">
                                    <label class="styled textBeefy">Ваше игровое имя:</label>
                                    <div class="thefield"><?=$GlobalUser['oname'];?></div>
                                </div>
                                <div class="fieldwrapper">
                                    <label class="styled textBeefy">Имя нового игрока:</label>
                                    <div class="thefield">
                                    <input class="textInput w150 validate[optional,custom[noSpecialCaracters],length[3,20]]"
                                           type="text"
                                           maxlength="20"
                                           value=""
                                           size="20"
                                           id="db_character"
                                           name="db_character"/>
                                   	</div>
	                            </div>
                                <div class="fieldwrapper">
                                    <label class="styled textBeefy">Введите пароль <em>(для подтверждения)</em></label>
                                    <div class="thefield">
                                    <input class="textInput w150" type="password" value="" size="20" name="db_character_password"/>
                                    </div>
                                </div>
							  	<div class="fieldwrapper">
                                    <p>Вы можете сменить ник не чаще, чем <strong>раз в неделю</strong>.</p>
                                </div>
                             </div>							<div class="fieldwrapper alt bar">
                                <label class="styled textBeefy">
                                    Смена пароля                                </label>
                            </div>
                            <div class="group bborder" style="display:none;">
                                <div class="fieldwrapper">
                                    <label class="styled textBeefy">Введите старый пароль:</label>
                                    <div class="thefield">
                                     <input class="textInput w150" type="password" value="" size="20" name="db_password"/>
                                    </div>
                                </div>
                                <div class="fieldwrapper">
                                    <label class="styled textBeefy">Новый пароль (мин. 8 символов):</label>
                                    <div class="thefield">
                                    <input class="textInput w150 validate[optional,pwLength[8,20]]"
                                           type="password"
                                           maxlength="40"
                                           size="20"
                                           name="newpass1"
                                           id="newpass1"/>
                                    </div>
                                </div>
                                <div class="fieldwrapper">
                                    <label class="styled textBeefy">Повторить новый пароль:</label>
                                    <div class="thefield">
                                       <input class="textInput w150" type="password" maxlength="40" size="20" name="newpass2"/>
                                    </div>
                                </div>
                                <div class="pw_check">
                                    <p>Проверка пароля:</p>
                                    <div class="password-meter">
                                    	<span class="password">Ненадежный</span><span class="password">Средний</span><span class="password">Надежный</span>
                                    </div>
                                	<div id="password-meter">
                                		<span class="password weak"></span>
                                		<span class="password medium"></span>
                                		<span class="password best"></span>
                                	</div>
                                	<div class="pw_arrow">
                                		<span id="password-meter-rating-low" class="password arrow"></span>
                                		<span id="password-meter-rating-medium" class="password"></span>
                                		<span id="password-meter-rating-high" class="password"></span>
                                	</div>
                                </div>
                                <div class="password_prop">
                                    <p>Пароль должен содержать</p>
                                    <ul>
	                                    <li id="password-meter-status-length">мин. 8 символов, макс. 20 символов <img src="red_images/b1c7ef5b1164eba44e55b7f6d25d35.gif" class="status-checked" style="visibility: hidden;"></li>
	                                    <li id="password-meter-status-mixed-case">Заглавные и прописные буквы <img src="red_images/b1c7ef5b1164eba44e55b7f6d25d35.gif" class="status-checked" style="visibility: hidden;"></li>
	                                    <li id="password-meter-status-special-chars">Специальные символы (напр. !?:_., ) <img src="red_images/b1c7ef5b1164eba44e55b7f6d25d35.gif" class="status-checked" style="visibility: hidden;"></li>
	                                    <li id="password-meter-status-numbers">Цифры <img src="red_images/b1c7ef5b1164eba44e55b7f6d25d35.gif" class="status-checked" style="visibility: hidden;"></li>
									</ul>
                                </div>
                                
                                <div class="fieldwrapper" style="display:none;">
                                    <p>Ваш пароль может содержать не менее <strong>8 символов</strong>, но и не может быть длиннее <strong>20 символов</strong>.</p>
                                </div>
                            </div>                	        <div class="fieldwrapper alt bar">
            	            	<label class="styled textBeefy">Адрес</label>
        	                </div>
                            <div class="group bborder" style="display:none">
                                <div class="fieldwrapper">
                                    <label class="styled textBeefy">Текущий адрес:</label>
                                    <div class="styled"><?=$GlobalUser['pemail'];?></div>
                                </div>
                                <div class="fieldwrapper">
                                    <label class="styled textBeefy">Новый e-mail:</label>
                                    <div class="thefield">
                                    <input class="textInput w150 validate[optional,custom[email]]"
                                           type="text"
                                           value=""
                                           size="20"
                                           id="db_email"
                                           name="db_email"/>
                                    </div>
                                </div>
                                <div class="fieldwrapper">
                                    <label class="styled textBeefy">Введите пароль <em>(для подтверждения)</em>:</label>
                                    <div class="thefield">
                                    <input class="textInput w150" type="password" value="" size="20" name="db_email_password"/>
                                    </div>
                                </div>
                                <div class="fieldwrapper">
                                    <p>Вы можете менять адрес электронной почты каждые <strong>7 дней</strong>.</p>
                                </div>
	                	</div>                  </div>                    <!-- GENERAL -->
                    <div id="two" class="wrap" style="display:none;">
                        <div class="fieldwrapper alt bar">
                            <label class="styled textBeefy">
                                Шпионских зондов                            </label>
                        </div>
		        <div class="group bborder">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Кол-во шпионских зондов:</label>
                                <div class="thefield">
                                    <input type="text"  class="textInput textCenter textBeefy" value="4" size="2" maxlength="2" name="spio_anz"/>
                                </div>
                            </div>			</div>
                        <div class="fieldwrapper alt bar">
                            <label class="styled textBeefy">
                                Предупреждения                            </label>
                        </div>
		        <div class="group bborder">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Автоматическое предупреждение о нападении на игрока, который сильнее в 5 раз:</label>
                                <div class="thefield">
                                    <input type="checkbox"  name="disableOutlawWarning"/>
                                </div>
                            </div>			</div>
												<div class="fieldwrapper alt bar">
							<label class="styled textBeefy">Сообщения</label>
						</div>
						<div class="group">
                           <div class="fieldwrapper">
                                <label class="styled textBeefy">Показать полный шпионский доклад:</label>
                                <div class="thefield">
                                    <input type="checkbox"  checked  name="fullSpioReport"/>
                                </div>
                            </div>

                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Количество отображаемых на странице сообщений:</label>
                                <div class="thefield">
                                    <select name="msgResultsPerPage"  class="dropdown w150">
                                        <option value="10" >
                                            10                                        </option>
                                        <option value="25" selected>
                                            25                                        </option>
                                        <option value="50" >
                                            50                                        </option>
                                    </select>
                                </div>
                             </div>                       </div>

                                              <div class="fieldwrapper alt bar">
                            <label class="styled textBeefy">Проверка IP</label>
                        </div>
                        <div class="group">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Деактивировать проверку IP (требуется разрешение от Оператора!)</label>
                                <div class="thefield">
                                    <input type="checkbox"  name="disabled_ipcheck"/>
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <p>Проверка IP означает, что автоматически последует выгрузка, если меняется IP или двое людей с разными IP зашли под одним аккаунтом. Отключение проверки IP может быть небезопасным!</p>
                            </div>
                       </div>

						<div class="fieldwrapper alt bar">
							<label class="styled textBeefy">RSS-лента</label>
						</div>
						<div class="group">
                        	<div class="fieldwrapper">
                            	<label class="styled textBeefy">Новости</label>
                                <div class="thefield">
                                  <select name='feed' class="dropdown w150">
                                        <option value="0" selected>
											Деактивировано                                        </option>
                                        <option value="1" >
											Формат: RSS                                        </option>
                                        <option value="2" >
											Формат: Atom                                        </option>
                                    </select>
                                </div>                            </div>                        </div>	                    </div>
                    <!-- REPRESENTATION -->
                    <div id="three" class="wrap" style="display:none;">
						<div class="fieldwrapper alt bar">
							<label class="styled textBeefy">Ваши планеты</label>
						</div>
                        <div class="group bborder">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Сортировка планет по:</label>
                                <div class="thefield">
                                    <select name="settings_sort"  class="dropdown w200">
                                        <option value="0" >
                                            порядку колонизации                                        </option>
                                        <option value="1" selected>
                                            Координаты                                        </option>
                                        <option value="2" >
                                            алфавиту                                        </option>
                                        <option value="3" >
                                            Размер                                        </option>
                                        <option value="4" >
                                            Застроенная территория                                        </option>
                                    </select>
                                </div>
                             </div>                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Порядок сортировки:</label>
                                <div class="thefield">
                                 <select name="settings_order" class="dropdown w200">
                                    <option value="0" selected>
                                        по возрастанию                                    </option>
                                    <option value="1" >
                                        по убыванию                                    </option>
                                </select>
                                </div>                            </div>                        </div>                        <div class="fieldwrapper alt bar">
                            <label class="styled textBeefy">Обзор</label>
                        </div>
                        <div class="group bborder">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Отображение анимации:</label>
                                <div class="thefield">
                                    <input type="checkbox"  name="animatedSliders"/>
                                </div>
                            </div>                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Анимация Обзора:</label>
                                <div class="thefield">
                                    <input type="checkbox"  name="animatedOverview"/>
                                </div>
                            </div>                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Подсвечивать информацию о планете:</label>
                                <div class="thefield">
                                    <input type="checkbox"  name="showDetailOverlay"/>
                                </div>
                            </div>                        </div>
                        <div class="fieldwrapper alt bar">
                            <label class="styled textBeefy">Прочие</label>
                        </div>
                        <div class="group bborder">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Показать список событий:</label>
                                <div class="thefield">
                                    <select name="eventsShow" class="dropdown w200">
                                        <option value="1" selected="selected">Скрыть</option>
                                        <option value="2" >Вверху страницы</option>
                                        <option value="3" >Внизу страницы</option>
                                    </select>
                                </div>
                            </div>                            <div class="fieldwrapper">
                                <label class="styled textBeefy">Детальная активность:</label>
                                <div class="thefield">
                                    <input type="checkbox" checked  name="showActivityMinutes"/>
                                </div>
                            </div>                        <div class="fieldwrapper">
                                <label class="styled textBeefy">Редизайн:</label>
                                <div class="thefield">
                                    <input type="checkbox" checked  name="redesign"/>
                                </div>
                            </div>            </div>
                    </div>

                    <!-- EXTENDED -->
                    <div id="four" class="wrap" style="display:none;">
					<div class="fieldwrapper alt bar">
                    	<label class="styled textBeefy">режим отпуска</label>
                    </div>
                    <div class="group bborder">
    
                        <div class="fieldwrapper">
                            <p>
                                <span class="overmark">Режим отпуска не может быть включен</span>
                                (Что-то летит или строится.)
                            </p>
                        </div>

                            <div class="fieldwrapper">
                            <p>Режим отпуска предназначен для того, чтобы оберегать Вас во время длительного отсутствия. Его можно активировать только тогда, когда<em> ничего не строится</em>  (флоты, постройки или оборона) <strong>и</strong> <em>ничего не исследуется</em>, а также если Вы никуда не посылали свои флоты.<br /><br />
Если он активирован, то защищает Вас от атак, однако уже начатые атаки продолжаются. Во время режима отпуска производство снижается до нуля. Режим отпуска длится минимум 2 дня, деактивировать его возможно только после этого срока.</p>
                        </div>
					</div>


					<div class="fieldwrapper alt bar">
                    	<label class="styled textBeefy">
                        	Ваш аккаунт                        </label>
                    </div>
                    <div class="group bborder">
						<div class="fieldwrapper">
							<label class="styled textBeefy">
								Удалить аккаунт							</label>
							<div class="thefield">
								<input type="checkbox" name="db_deaktjava"/>
							</div>
						</div>
						<div class="fieldwrapper">
							<p>Если поставить здесь галочку, аккаунт автоматически удалится через 7 дней.</p>
						</div>

                    </div>
				</div> <!-- END EXTENDED -->
                <input type="submit" class="button188" value="Применить настройки" onclick="onSubmit();" style="margin-bottom: 0;"/>
                </div> <!-- END CONTENT -->
                <!-- FOOTER -->
                <div class="footer"></div>
                </form>
            </div>

        </div>
    </div>
</div>                            </div>
            <!-- END CONTENT AREA -->

<?php
    include "redesign_planetlist.php";

?>

            <!-- JAVASCRIPT -->
            
            <script type="text/javascript" src="red_scripts/c7681c4bc98bd6e8aed62c6ac54c27.js"></script>
<script type="text/javascript" src="red_scripts/54324295d7d923b8b74880a353991f.js"></script>
<script type="text/javascript" src="red_scripts/2d644c4d8889e742ccf1c07c5d2f63.js"></script>
<script type="text/javascript" src="red_scripts/df241c51d6c8c6288ff546034df89f.js"></script>
<script type="text/javascript" src="red_scripts/c6b1aba14d5c338fec536a3e8275d4.js"></script>
<script type="text/javascript" src="red_scripts/34e08a357e11b77e84361f4931f1c3.js"></script>
<script type="text/javascript" src="red_scripts/c66f8e06dc6c21b3216d23d4c387e7.js"></script>
<script type="text/javascript" src="red_scripts/c18b98d9d2d81526f30441aae725f3.js"></script>
<script type="text/javascript" src="red_scripts/8d5245f529402656c74c62885b5f2d.js"></script>
<script type="text/javascript" src="red_scripts/26cd0dabbb9bf6daa569200d936b8f.js"></script>
<script type="text/javascript" src="red_scripts/d3a309ad3b73febbea5c1b819c8b36.js"></script>
<script type="text/javascript" src="red_scripts/233f58cce237fe7b4d78283d351981.js"></script>
<script type="text/javascript" src="red_scripts/34bed8a925d029abc7ee5d737fcf28.js"></script>
<script type="text/javascript" src="red_scripts/68f02f9c5afe18e85810f6bfbde8df.js"></script>
<script type="text/javascript" src="red_scripts/2bff6678b833693d96348bc9cec95c.js"></script>
<script type="text/javascript" src="red_scripts/e91b3fe0212eb21c2e876e23af8117.js"></script>
<script type="text/javascript" src="red_scripts/35febdaeb25f067a4730013837ab74.js"></script>
<script type="text/javascript" src="red_scripts/25e1f23d90e71bdef5aafa5c44de90.js"></script>
<script type="text/javascript" src="red_scripts/9d59a24a6690715d0ec9aecdcbac04.js"></script>
<script type="text/javascript" src="red_scripts/b34ddadda9849e49ce7fd14b7fc509.js"></script>
<script type="text/javascript" src="red_scripts/82eb94bd0228544ca343f20f16a328.js"></script>
<script type="text/javascript" src="red_scripts/56bb0d2daafc993b2866ccc1af86fc.js"></script>
<script type="text/javascript" src="red_scripts/d997fe2fa3204685cbe4eaa135ddb7.js"></script>
<script type="text/javascript" src="red_scripts/5ba40f697d5c52b55cbe0f432c8914.js"></script>
<script type="text/javascript" src="red_scripts/ea37528475b3999c9fc87c0b443c78.js"></script>
<script type="text/javascript" src="red_scripts/1871d857b0934efa62d52556e9f637.js"></script>
<script type="text/javascript" src="red_scripts/7c4caa0fe7949873bce17eb1da47f2.js"></script>
<script type="text/javascript" src="red_scripts/60cd95d4ce5cb91a86861f433773d1.js"></script>
<script type="text/javascript" src="red_scripts/6fc8b4608b9cc27f7d61537c09c836.js"></script>
            <script type="text/javascript">            
                
                var TB_open = 0;
                var session = '<?=$session;?>';
                var vacation = 0;
                var timerHandler = new TimerHandler();

                function redirectPremium()
                {
                    location.href = "index.php?page=premium&session=<?=$session;?>&showDarkMatter=1";
                        }



    // Monat Tag, Jahr Stunden:Minuten:Sekunden
    var serverTime = new Date(<?=date("Y", $now);?>, <?=date("m", $now);?>, <?=date("d", $now);?>, <?=date("H", $now);?>, <?=date("i", $now);?>, <?=date("s", $now);?>);
    var localTime = new Date();
    localTS = localTime.getTime();
    // Zeitdifferenz Server-Client herausrechnen
    var startServerTime = localTime.getTime() - (14400000) - localTime.getTimezoneOffset()*60*1000; // GMT+1+Sommerzeit - offset

    var LocalizationStrings = {"timeunits":{"short":{"year":"\u0433","month":"\u043c","week":"\u043d\u0435\u0434","day":"\u0434","hour":"\u0447","minute":"\u043c","second":"\u0441"}},"status":{"ready":"\u0433\u043e\u0442\u043e\u0432\u043e"},"decimalPoint":".","thousandSeperator":".","unitMega":"\u041c","unitKilo":"\u041a","unitMilliard":"\u0413","error":"\u041e\u0448\u0438\u0431\u043e\u0447\u043a\u0430","loading":"\u0437\u0430\u0433\u0440\u0443\u0437\u043a\u0430..."};

    OGConfig = new Array();
    OGConfig.sliderOn = 1;


    function initAjaxEventbox()
    {
        $.get(
        "index.php?page=fetchEventbox&session=<?=$session;?>&ajax=1",
                reloadEventbox,
                "text"
            );
            }

            function initAjaxResourcebox()
            {
                $.get(
        "index.php?page=fetchResources&session=<?=$session;?>&ajax=1",
                reloadResources,
                "text"
            );
            }
            
            
            $("#eventboxFilled").click(toggleEvents);
            $(".eventToggle").click(function() {
                toggleEvents();
                return false;
            })
            if (!$("#eventboxContent").is(":hidden")) {
                loadEvents();
            }
            
            function loadEvents() {
                $("#eventboxContent").slideDown('fast');
                if (typeof loadEvents.loaded == 'undefined') {
                    $("#eventboxContent").html('<img height="16" width="16" src="red_images/3f9884806436537bdec305aa26fc60.gif" />');
                    $.get('index.php?page=eventList&session=<?=$session;?>&ajax=1', function(response) {
                    $("#eventboxContent").html(response);
                    $("#eventHeader .close_details").click(toggleEvents);
                    loadEvents.loaded = true;
                }); 
            }
        }

        var changeSettingsLink = "index.php?page=changeSettings&session=<?=$session;?>";
        var changeSettingsToken = "47d98148190115cc21677334fcabed3c";
        function openAnnouncement() {
            tb_open_new("index.php?page=announcement&session=<?=$session;?>&ajax=1&height=600&width=770", null);
        }
            
             
 
/* var mySlider = new MessageSlider(document.getElementById('messagebox'));

addListener(window, 'load', function() {
    if (document.getElementById('messages_container')) {
    	var inhalt = document.getElementById('messages_container');
    	var windowHeight = document.documentElement.clientHeight;
    	var contentHeight = inhalt.offsetHeight;
    	inhalt.style.height = Math.min( (windowHeight-160), (contentHeight) ) +'px';
    }
});
*/

var resourceTickerMetal = {
    available: 401864.22733041,
    limit: [0, 2920000],
    production: 4.8066666666667,
    valueElem: "resources_metal"
};
var resourceTickerCrystal = {
    available: 22713.616035833,
    limit: [0, 5355000],
    production: 2.1672222222222,
    valueElem: "resources_crystal"
};
var resourceTickerDeuterium = {
    available: 56014.172338065,
    limit: [0, 1590000],
    production: 1.1905555555556,
    valueElem: "resources_deuterium"
};

vacation = 0;
if (!vacation) {
	new resourceTicker(resourceTickerMetal);
	new resourceTicker(resourceTickerCrystal);
	new resourceTicker(resourceTickerDeuterium);
}            
 
function UhrzeitAnzeigen()
{
    var currTime = new Date(); 
    // Differenz Server und Clienttime herausrechnen, um Serverzeit zu erhalten
    currTime.setTime((<?=($now * 1000);?>-startServerTime)+ currTime.getTime()) ;
    // globale Serverzeit setzen (wird von diversen Funktionen genutzt)
    serverTime = currTime;
    Uhrzeitanzeige = getFormatedDate(currTime.getTime(), '[d].[m].[Y] <span>[H]:[i]:[s]</span>');
    if(document.getElementById)
    {
        document.getElementById("OGameClock").innerHTML = Uhrzeitanzeige
    }
    else if(document.all)
    {
        Uhrzeit.innerHTML = Uhrzeitanzeige;
    }
}
setInterval("UhrzeitAnzeigen()", 1000);

 
 
 
function initMouseOverImageSwitch() 
{
    $('img.mouseSwitch').bind(
        'mouseenter', 
        function(){
            var tempSrc = $(this).attr('src');
            $(this).attr('src', $(this).attr('rel'));
            $(this).attr('rel', tempSrc);
        }
    ).bind(
        'mouseleave', 
        function(){
            var tempSrc = $(this).attr('src');
            $(this).attr('src', $(this).attr('rel'));
            $(this).attr('rel', tempSrc);
        }
    );     
}
    

 
 

        (function($) {
        $.fn.validationEngineLanguage = function() {};
        $.validationEngineLanguage = {
            newLang: function() {
                $.validationEngineLanguage.allRules = 	{
                        "length":{
                            "regex":"none",
                            "alertText":"Недостаточно символов"},
                        "pwLength":{
                            "regex":"none",
                            "alertTextTooShort":"Введенный пароль слишком короткий (мин.  символов)",
                            "alertTextTooLong":"Введенный пароль слишком длинный. (макс. 20 символов)"},
                        "email":{
                            "regex":"/^[a-zA-Z0-9_\\.\\-]+\\@([a-zA-Z0-9\\-]+\\.)+[a-zA-Z0-9]{2,4}$/",
                            "alertText":"Необходимо ввести действительный E-Mail адрес!"},
                        "noSpecialCaracters":{
                            "regex":"/^[^+ # & , \\ ' \" ` / ; < > ( )]+$/",
                            "alertText":"Содержит недопустимые символы."}
                        }
            }
        }
    })(jQuery);

function initFormValidation()
{
    $("#prefs").validationEngine({
        validationEventTriggers:"keyup blur",
        promptPosition: "centerRight",
		inlineValidation: true
	});
}

function initPreferencesTabs()
{
    $(".tabsbelow").tabs();
    
    $(".tabsbelow").tabs('select', 0);
}

function onSubmit()
{
    var tabs = $('.tabsbelow').tabs();
    $('#selectedTab').val(tabs.data('selected.tabs'));

    var openGroup = $('.tabsbelow').tabs();
    $('#selectedTab').val(tabs.data('selected.tabs'));
}

function redirectLogout()
{
	location.href = "<?=$StartPage;?>";
}

$(document).ready(function() {

    $.validationEngineLanguage.newLang();
    
    // Generell immer die erste Gruppe öffnen
    // WICHTIG ZUM DURCHKLICKEN DER TABS
    $('div.wrap > div.group').hide();
	$('div.wrap:eq(0) > div.group:eq(0)').show();
	$('div.wrap:eq(1) > div.group:eq(0)').show();
	$('div.wrap:eq(2) > div.group:eq(0)').show();
	$('div.wrap:eq(3) > div.group:eq(0)').show();

    // Im aktiven Tab aber die richtige Auswahl öffnen
    $('div.wrap:eq(0) > div.group').hide();
    $('div.wrap:eq(0) > div.group:eq(0)').show();

	$('div.wrap > div.bar').click(function() {
		$(this).next('div.group:hidden').slideDown('fast')
		.siblings('div.group:visible').slideUp('fast');
        $.validationEngine.closePrompt('.formError');
	});

	$('#one .bar').hover(function() {
		$(this).addClass('bar-hover');
		}, function() {
			$(this).removeClass('bar-hover');
	});
	$('#two .bar').hover(function() {
		$(this).addClass('bar-hover');
		}, function() {
			$(this).removeClass('bar-hover');
	});
	$('#three .bar').hover(function() {
		$(this).addClass('bar-hover');
		}, function() {
			$(this).removeClass('bar-hover');
	});
	$('#four .bar').hover(function() {
		$(this).addClass('bar-hover');
		}, function() {
			$(this).removeClass('bar-hover');
	});

    $('#newpass1').bind('keyup', function() {    
        var value  = $(this).val();
        var length = value.length;

        var hasSpecialChars = value.match(/[^A-Za-z\d]/);
        var hasNumbers      = value.match(/\d/);
        var hasMixedCase    = value.match(/[a-z]/) && value.match(/[A-Z]/);

        var score     = 0;
        var maxScore  = 4;
        var fulfilled = {
            'length':        false,
            'mixed-case':    false,
            'special-chars': false,
            'numbers':       false
        };
        
        if (length >= 8 && length <= 20) {
            fulfilled['length'] = true;
            score++;
        }
        
        if (hasMixedCase) {
            fulfilled['mixed-case'] = true;
            score++;
        }

        if (hasNumbers) {
            fulfilled['numbers'] = true;
            score++;
        }

        if (hasSpecialChars) {
            fulfilled['special-chars'] = true;
            score++;
        }

        for (var name in fulfilled) {
            var isFulfilled = fulfilled[name];
            var element     = $('#password-meter-status-' + name);

            element.find('img.status-checked').css('visibility', isFulfilled ? 'visible' : 'hidden');
        }

        var rating = Math.floor(score / maxScore * 2);
        var levels = new Array('low', 'medium', 'high');

        for (var i in levels) {
            if (i != rating) {
                $('#password-meter-rating-' + levels[i]).removeClass('arrow');
            } else {
                $('#password-meter-rating-' + levels[i]).addClass('arrow');
            }
        }
    });
});

 
             


$(document).ready(function() {
    initHovers();
initTooltips();
initMouseOverImageSwitch();
initPreferencesTabs();
initFormValidation();

initCluetip();
initAjaxEventbox();
});
             
 </script>            <!-- END JAVASCRIPT -->

<?php

include "redesign_footer.php";

?>