<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict //EN" 
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
 
<html> 
<head> 
<meta name="author" content="Gameforge Productions GmbH" /> 
<meta http-equiv="content-type" content="text/html; charset=KOI8-R" /> 
<meta name="keywords" content="OGame, Browsergame, Onlinegame, Browsergames, Browsergame, Spiel, Spiele, Onlinespiel, Onlinespiele" /> 
<meta name="description" content="OGame - Top Browsergame im Weltraum. Kommandiere deine Flotten." /> 
<meta name="robots" content="index, follow" /> 
<meta name="language" content="ru" /> 
<meta name="distribution" content="global" /> 
<meta name="audience" content="all" /> 
<meta name="author-mail" content="info@ogame.de" /> 
<meta name="publisher" content="Gameforge Productions GmbH" /> 
<meta name="copyright" content="(c) 2007 by Gameforge Productions GmbH" /> 
<meta http-equiv="expires" content="0" /> 
<meta http-equiv="pragma" content="no-cache" /> 
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" /> 
<title>OGame.ru</title> 
<link rel='stylesheet' type='text/css' href='css/styles.css' /> 
<link rel='stylesheet' type='text/css' href='css/about.css' /> 
<script language="JavaScript"> 
if (parent.frames.length == 0) {
    window.location = "/";
}
</script> 
<script src="js/functions.js" type="text/javascript"></script> 
<script language="JavaScript" src="js/tw-sack.js"></script> 
<script language="JavaScript" src="js/registration.js"></script> 
<script language="JavaScript" > 
 
function changeAction(type) {
    if(type != "register" && document.loginForm.universe.value == '') {
        alert('бШ МЕ БШАПЮКХ БЯЕКЕММСЧ.');
    }
    else {
        if(type == "login") {
            var url = "http://" + document.loginForm.universe.value + "/game/reg/login2.php";
            document.loginForm.action = url;
        }
        else if (type=="getpw") {
            var url = "http://" + document.loginForm.universe.value + "/game/reg/mail.php";
            document.loginForm.action = url;
            document.loginForm.submit();
        }
        else if(type == "register") {
            var url = "http://" + document.registerForm.universe.value + "/game/reg/newredirect.php";
            document.registerForm.action = url;
        }
    }
}
 
function printMessage(code, div) {
    var textclass = "";
    
    if (div == null) {
        div = "statustext";
    }
    switch (code) {
        case "0":
            text = "нй";
            textclass = "fine"; 
            break;
        case "101":
            text = "щРН ХЛЪ СФЕ ГЮМЪРН!"; 
            textclass = "warning"; 
            break;
        case "102":
            text = "щРНР ЮДПЕЯ СФЕ ХЯОНКЭГСЕРЯЪ!";
            textclass = "warning"; 
            break;
        case "103":
            text = "бЮЬЕ ХЛЪ ДНКФМН ЯНДЕПФЮРЭ НР 3 ДН 20 ЯХЛБНКНБ!";
            textclass = "warning"; 
            break;
        case "104":
            text = "бБЕДХРЕ ДЕИЯРБХРЕКЭМШИ ЮДПЕЯ!";
            textclass = "warning"; 
            break;
        case "105":
            text = "мХЙ - нй";
            textclass = "fine"; 
            break;
        case "106":
            text = "юДПЕЯ - нй";
            textclass = "fine"; 
            break;
        case "107":
            text = "бБЕДХРЕ ДЕИЯРБХРЕКЭМШИ ЮДПЕЯ!";
            textclass = "warning"; 
            break;
        case "201":
            text = "хЦПНБНЕ ХЛЪ: <br />хЛЪ, ЙНРНПНЕ бШ БШАХПЮЕРЕ ЯБНЕЛС ОЕПЯНМЮФС. нДМН ХЛЪ МЕ ЛНФЕР ОНБРНПЪРЭЯЪ Б НДМНИ БЯЕКЕММНИ.";
            break;
        case "202":
            text = "щКЕЙРПНММШИ ЮДПЕЯ: <br />дКЪ ЮЙРХБЮЖХХ ЮЙЙЮСМРЮ ББЕДХРЕ ДЕИЯРБХРЕКЭМШИ ЮДПЕЯ. дКЪ ЮЙРХБЮЖХХ ДЮ╦РЯЪ РПХ ДМЪ, БН  БПЕЛЪ ЙНРНПШУ бШ РНФЕ ЯЛНФЕРЕ ХЦПЮРЭ.";
            break;
        case "203":
            text = "";
            break;
        case "204":
            text = "нЯМНБМШЕ ОНКНФЕМХЪ:<br /> дКЪ МЮВЮКЮ ХЦПШ бШ ДНКФМШ ОПХМЪРЭ НЯМНБМШЕ ОНКНФЕМХЪ.";
            break;
        case "205":
            text = "оЮПНКЭ: <br/>оЮПНКЭ ГЮЫХЫЮЕР бЮЬ ХЦПНБНИ ЮЙЙЮСМР НР ГЮУНДЮ МЮ МЕЦН ДПСЦХУ КЧДЕИ. мХЙНЦДЮ МЕ ДЮБЮИРЕ МХЙНЛС ЯБНИ ОЮПНКЭ.";
            break;
        default:
            text = code;
            break;
    }
    
    if (textclass != "") {
        text = "<span class='" + textclass + "'>" + text + "</span>";
    }
    document.getElementById(div).innerHTML = text;
}
</script> 
</head> 
<body> 
<a href="#pustekuchen" style="display:none;">Link кНЦХМ</a> 
 
<div id="main"> 
    
<div id="login"> 
     <a name="pustekuchen"></a> 
     <div id="login_text_1"> 
        <div style="position:absolute;left:160px;width:110px;">хЛЪ</div> 
        <div style="position:absolute;left:275px;width:50px;">оЮПНКЭ</div> 
     </div> 
     <div id="login_input"> 
     <table cellspacing="0" cellpadding="0" border="0"><tr style="vertical-align:top;"><td style="padding-right:4px;"> 
         <form name="loginForm" action="" method="POST" onSubmit="changeAction('login');" target="_self" > 
         <input type="hidden" name="v" value="2"> 
         <span> 
             <select tabindex="1" name='universe' class="eingabe" style="width:144px;"> 
                <option value="">бЯЕКЕММЮЪ...</option> 
                             <option value="uni1.ogame.ru" > 
                1. бЯЕКЕММЮЪ</option> 
                             <option value="uni2.ogame.ru" > 
                2. бЯЕКЕММЮЪ</option> 
                             <option value="uni3.ogame.ru" > 
                3. бЯЕКЕММЮЪ</option> 
                             <option value="uni4.ogame.ru" > 
                4. бЯЕКЕММЮЪ</option> 
                             <option value="uni5.ogame.ru" > 
                5. бЯЕКЕММЮЪ</option> 
                             <option value="uni6.ogame.ru" > 
                6. бЯЕКЕММЮЪ</option> 
                             <option value="uni7.ogame.ru" > 
                7. бЯЕКЕММЮЪ</option> 
                             <option value="uni8.ogame.ru" > 
                8. бЯЕКЕММЮЪ</option> 
                             <option value="uni9.ogame.ru" > 
                9. бЯЕКЕММЮЪ</option> 
                             <option value="uni10.ogame.ru" > 
                10. бЯЕКЕММЮЪ</option> 
                             <option value="uni11.ogame.ru" > 
                11. бЯЕКЕММЮЪ</option> 
                             <option value="uni12.ogame.ru" > 
                12. бЯЕКЕММЮЪ</option> 
                             <option value="uni13.ogame.ru" > 
                13. бЯЕКЕММЮЪ</option> 
                             <option value="uni14.ogame.ru" > 
                14. бЯЕКЕММЮЪ</option> 
                             <option value="uni15.ogame.ru" > 
                15. бЯЕКЕММЮЪ</option> 
                             <option value="uni16.ogame.ru" > 
                16. бЯЕКЕММЮЪ</option> 
                             <option value="uni17.ogame.ru" > 
                17. бЯЕКЕММЮЪ</option> 
                             <option value="uni18.ogame.ru" > 
                18. бЯЕКЕММЮЪ</option> 
              
             </select> 
         </span> 
         <td style="padding-right:3px;"> 
         <span><input tabindex="2" class="eingabe" maxlength="20" name="login"   alt=хЛЪ style="width:111px;top:0px"/></span> 
         <td> 
         <span><input tabindex="3" maxlength="20" type="password" class="eingabe" name="pass" style="width:113px;top:0px" alt=оЮПНКЭ /></span> 
         <td style="padding-top:2px;"> 
         <!--<span class="link" onclick="submitLogin()" style="padding-left:7px;">LOGIN</span>--> 
         <input type="image" src="../img/login_button.jpg" alt="Login" class="loginButton" name="button" id="button" onmouseover="document.getElementById('button').src='../img/login_button2.jpg';" onmouseout="document.getElementById('button').src='../img/login_button.jpg';"> 
         </form> 
     </tr></table> 
     </div> 
     <div id="login_text_2"> 
        <div style="position:absolute;text-align:right;width:439px;top:15px;"><a href="#" onclick="changeAction('getpw');">гЮАШКХ ОЮПНКЭ?</a></div> 
        <div style="position:absolute;left:12px;width:200px;top:15px;text-align:left;">гЮУНДЪ Б ХЦПС, Ъ ОПХМХЛЮЧ <a target="_blank" href="http://impressum.gameforge.de/index.php?lang=ru&art=tac&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000">нЯМНБМШЕ ОНКНФЕМХЪ</a>.</div> 
     </div>   
     
     <div id="copyright"> 
        (C) 2007 by <a target="_blank" href="http://www.gameforge.de">Gameforge Productions GmbH</a>. бЯЕ ОПЮБЮ ГЮЫХЫЕМШ.&nbsp;&nbsp;
     </div> 
     <div id="downmenu"> 
        <a href="regeln.html">оПЮБХКЮ</a>&nbsp;
        <a target="_blank" href="http://impressum.gameforge.de/index.php?lang=ru&art=impress&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000">Impressum</a>&nbsp;
        <a target="_blank" href="http://impressum.gameforge.de/index.php?lang=ru&art=tac&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000">нЯМНБМШЕ ОНКНФЕМХЪ</a> 
     </div>    
</div> 
 
 
<div class="products"> 
  <iframe src="http://nwlng.gameforge.de/index.php?game=ogame&country=ru" class="iframe" frameborder="0" scrolling="No"> 
  Hier ist ein IFrame
  </iframe> 
 </div> 
<div id="gimmik_1"> 
</div> 
<div id="gimmik_2"> 
</div> 
 
<div id="mainmenu"> 
    <a href="home.php">цКЮБМЮЪ</a> 
    <a href="about.html">оПН нцЕИЛ</a> 
    <a href="screenshots.html">йЮПРХМЙХ</a> 
    <div class="menupoint">оПХЯНЕДХМХРЭЯЪ</div> 
    <a href="http://board.ogame.ru" target="_blank">тНПСЛ</a> 
</div> 
 
    <div id="rightmenu" class="rightmenu_register"> 
        <div id="title">оПХЯНЕДХМХРЭЯЪ</div> 
        <div id="content"  align="justify"> 
            <div id="text1">дКЪ ХЦПШ бЮЛ МЕНАУНДХЛН ББЕЯРХ <strong>ХЦПНБНЕ ХЛЪ</strong>, <strong>ОЮПНКЭ</strong> Х <strong>ЩКЕЙРПНММШИ ЮДПЕЯ</strong> Х ОНЯРЮБХРЭ ЦЮКНВЙС, ВРНАШ ОПХМЪРЭ НЯМНБМШЕ ОНКНФЕМХЪ.</div> 
            
            <div id="register_container"> 
                <form name="registerForm"  method="POST" action="" onsubmit="changeAction('register');" > 
                
                <table> 
                
                <tr> 
                    <td class="table_lable">хЦПНБНЕ ХЛЪ:</td> 
                    <td class="table_input"><input class="eingabe"  type=text name=character size=20 onfocus="javascript:showInfo('201');javascript:pollUsername();" onblur="javascript:stopPollingUsername();"></td> 
                </tr> 
                <tr> 
                    <td class="table_lable">щКЕЙРПНММШИ ЮДПЕЯ:</td>                
                    <td class="table_input"><input class="eingabe"  type=text name=email size=20 onfocus="javascript:showInfo('202')"></td> 
                </tr> 
                                <tr> 
                    <td class="table_lable">оЮПНКЭ:</td>                
                    <td class="table_input"><input class="eingabe" type="password" name=password size=20 onfocus="showInfo('205');"></td> 
                </tr> 
                                <tr><td id="uni_label">лШ ЯНБЕРСЕЛ БЯЕКЕММСЧ</td> 
                <td class="table_input"> 
                                <select name=universe size=1 class="eingabe" style="width:122px;"> 
                    <option value="uni1.ogame.ru" >1</option><option value="uni2.ogame.ru" >2</option><option value="uni3.ogame.ru" >3</option><option value="uni4.ogame.ru" >4</option><option value="uni5.ogame.ru" >5</option><option value="uni6.ogame.ru" >6</option><option value="uni7.ogame.ru" >7</option><option value="uni8.ogame.ru" >8</option><option value="uni9.ogame.ru" >9</option><option value="uni10.ogame.ru" >10</option><option value="uni11.ogame.ru" >11</option><option value="uni12.ogame.ru" >12</option><option value="uni13.ogame.ru" >13</option><option value="uni14.ogame.ru" >14</option><option value="uni15.ogame.ru" selected >15 (НВЕМЭ ЯНБЕРСЕЛ!)</option>\n<option value="uni16.ogame.ru" >16</option><option value="uni17.ogame.ru" >17</option><option value="uni18.ogame.ru" >18</option>                </select> 
                <div id="uni_infos_link"><a href="unis.html">нЯНАЕММНЯРХ БН БЯЕКЕММШУ</a></div> 
                </td></tr> 
                <tr id="agb_zeile"> 
                <td></td> 
                <td id="table_agb"> 
                    <input type=checkbox name="agb" onfocus="javascript:showInfo('204');"> 
                    ъ ОПХМХЛЮЧ <a class="register_agb" href="http://impressum.gameforge.de/index.php?lang=ru&art=tac&special=&&f_text=b5cfcd&f_text_hover=ffffff&f_text_h=9ebde4&f_text_hr=ffffff&f_text_hrbg=061229&f_text_hrborder=26324c&f_text_font=arial%2C+arial%2C+helvetica%2C+sans-serif&f_bg=000000">нЯМНБМШЕ ОНКНФЕМХЪ</a>                </td> 
                </tr> 
                </table> 
            
                <input type="hidden" name="v" value="3" /><input type="hidden" name="step" value="validate" /> 
                <input type="hidden" name="try" value="2" /> 
                <input type="hidden" name="kid" value="" /> 
                
                                    <input type="hidden" name="errorCodeOn" value="1" />    
                                
                </form> 
            </div> 
            <div id="infotext"></div> 
            <div id="statustext"></div> 
            <div id="register_submit" onclick="changeAction('register');document.registerForm.submit();">нРОПЮБХРЭ ПЕЦХЯРПЮЖХЧ</div> 
        </div> 
    </div> 
 
<script> 
document.registerForm.character.focus();
</script> 
 
 
 
 
</body> 
</html>