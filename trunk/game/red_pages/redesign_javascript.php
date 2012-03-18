
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
<script type="text/javascript" src="red_scripts/6851aa26c22b8a502baf44898ed14f.js"></script>
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
    //var startServerTime = localTime.getTime() - (14400000) - localTime.getTimezoneOffset()*60*1000; // GMT+1+Sommerzeit - offset
    var startServerTime = serverTime;

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
        var changeSettingsToken = "1b3c22f2afb9803ad2afcc3eb7e30c0d";
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
    available: 28650.337989797,
    limit: [0, 865000],
    production: 3.7094444444444,
    valueElem: "resources_metal"
};
var resourceTickerCrystal = {
    available: 22194.369643094,
    limit: [0, 470000],
    production: 1.8977777777778,
    valueElem: "resources_crystal"
};
var resourceTickerDeuterium = {
    available: 118755.19620547,
    limit: [0, 255000],
    production: 0.95527777777778,
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
    

 
 
var planetMoveLoca = {"askTitle":"\u041f\u0435\u0440\u0435\u043c\u0435\u0441\u0442\u0438\u0442\u044c \u043f\u043b\u0430\u043d\u0435\u0442\u0443","askCancel":"\u0412\u044b \u0443\u0432\u0435\u0440\u0435\u043d\u044b \u0447\u0442\u043e \u0445\u043e\u0442\u0438\u0442\u0435 \u043e\u0442\u043c\u0435\u043d\u0438\u0442\u044c \u043f\u0435\u0440\u0435\u043c\u0435\u0449\u0435\u043d\u0438\u0435 \u044d\u0442\u043e\u0439 \u043f\u043b\u0430\u043d\u0435\u0442\u044b? \u0412\u0440\u0435\u043c\u044f \u043e\u0436\u0438\u0434\u0430\u043d\u0438\u044f \u0434\u043e \u0441\u043b\u0435\u0434\u0443\u044e\u0449\u0435\u0433\u043e \u043f\u0435\u0440\u0435\u043c\u0435\u0449\u0435\u043d\u0438\u044f \u043d\u0435 \u0438\u0437\u043c\u0435\u043d\u0438\u0442\u0441\u044f.","yes":"\u0434\u0430","no":"\u041d\u0435\u0442","success":"\u041f\u0435\u0440\u0435\u043c\u0435\u0449\u0435\u043d\u0438\u0435 \u043f\u043b\u0430\u043d\u0435\u0442\u044b \u0431\u044b\u043b\u043e \u043e\u0442\u043c\u0435\u043d\u0435\u043d\u043e.","error":"\u041e\u0448\u0438\u0431\u043e\u0447\u043a\u0430"};

$(".openPlanetrenameGiveupBox").click(function(){
    openPlanetRenameGiveupBox();
});

function openPlanetRenameGiveupBox()
{
    tb_open('index.php?page=planetlayer&session=<?=$session;?>&width=670&height=380');
}

	var textContent = new Array();

	textContent[0] = "Диаметр:";
	textContent[1] = "<?=nicenum($aktplanet['diameter']);?>км  (<span><?=$aktplanet['fields'];?></span>/<span><?=$aktplanet['maxfields'];?></span>)";
	textContent[2] = "Температура:";
	textContent[3] = "от <?=$aktplanet['temp'];?>°C до <?=($aktplanet['temp']+40);?>°C";
	textContent[4] = "Место:";
	textContent[5] = "<a  href=\"index.php?page=galaxy&session=<?=$session;?>&galaxy=<?=$aktplanet['g'];?>&system=<?=$aktplanet['s'];?>&position=<?=$aktplanet['p'];?>\" >[<?=$aktplanet['g'];?>:<?=$aktplanet['s'];?>:<?=$aktplanet['p'];?>]</a>";
	textContent[6] = "Очки:";
	textContent[7] = "<a href='index.php?page=highscore&session=<?=$session;?>'><?=nicenum(floor($GlobalUser['score1']/1000));?> (Место <?=nicenum($GlobalUser['place1']);?> из <?=nicenum($GlobalUni['usercount']);?>)</a>";

	var textDestination = new Array();

	textDestination[0] = "diameterField";
	textDestination[1] = "diameterContentField";
	textDestination[2] = "temperatureField";
	textDestination[3] = "temperatureContentField";
	textDestination[4] = "positionField";
	textDestination[5] = "positionContentField";
	textDestination[6] = "scoreField";
	textDestination[7] = "scoreContentField";

	var currentIndex = 0;
	var currentChar = 0;
	var linetwo=0;


	function type()
	{
        for (var i = 0; i < textDestination.length; i++) {
            document.getElementById(textDestination[i]).innerHTML = textContent[i];
        }
	}

function planetRenamed(data)
{
    var data = $.parseJSON(data);

    if (data["status"]) {
        $("#planetNameHeader").html(data["newName"]);
        reloadRightmenu("index.php?page=rightmenu&session=<?=$session;?>&renamed=1");
    }

    errorBoxAsArray(data["errorbox"]);
}

function planetGivenup(data)
{
    var data = $.parseJSON(data);

    if (data["status"]) {
        reloadRightmenu("index.php?page=rightmenu&session=<?=$session;?>");
    }

    errorBoxAsArray(data["errorbox"]);
}

function reloadPage()
{
    location.href = "index.php?page=overview&session=<?=$session;?>";
}


 
 
var cancelProduction_id;
var production_listid;
    
function cancelProduction(id,listid,question)
{
    cancelProduction_id = id;
    production_listid = listid;
    errorBoxDecision("Внимание",""+question+"","да","Нет",cancelProductionStart);
}

function cancelProductionStart()
{
    window.location.replace("index.php?page=overview&session=<?=$session;?>&modus=2&techid="+cancelProduction_id+"&listid="+production_listid);
    closeErrorBox();
}

 
 
var cancelResearch_id;

function cancelResearch(id,question)
{
    cancelResearch_id = id;
    errorBoxDecision("Внимание",""+question+"","да","Нет",cancelResearchStart);
}

function cancelResearchStart()
{
    window.location.replace("index.php?page=overview&session=<?=$session;?>&modus=2" + "&techid=" + cancelResearch_id);
    closeErrorBox();
}

new baulisteCountdown(getElementByIdWithCache('researchCountdown'), 52727, "index.php?page=overview&session=<?=$session;?>");

 
 
function initType() {
    type();
}

new baulisteCountdown(
    getElementByIdWithCache("moveCountdown"),
    -1332053204);


$('#planet h2 a').hover(function() {
	$('#planet h2 a img').toggleClass('hinted');
}, function() {
	$('#planet h2 a img').toggleClass('hinted');	
});


 
             


$(document).ready(function() {
    initHovers();
initTooltips();
initMouseOverImageSwitch();
initOverview();
initType();

initCluetip();
initAjaxEventbox();
});
            
             
 </script>            <!-- END JAVASCRIPT -->
