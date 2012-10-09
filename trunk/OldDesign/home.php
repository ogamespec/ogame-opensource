<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict //EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<meta name="author" content="Gameforge Productions GmbH" />
<meta http-equiv="content-type" content="text/html; charset=cp-1251" />
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
        alert('Вы не выбрали вселенную.');
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
            text = "ОК";
            textclass = "fine"; 
            break;
        case "101":
            text = "Это имя уже занято!"; 
            textclass = "warning"; 
            break;
        case "102":
            text = "Этот адрес уже используется!";
            textclass = "warning"; 
            break;
        case "103":
            text = "Ваше имя должно содержать от 3 до 20 символов!";
            textclass = "warning"; 
            break;
        case "104":
            text = "Введите действительный адрес!";
            textclass = "warning"; 
            break;
        case "105":
            text = "Ник - ОК";
            textclass = "fine"; 
            break;
        case "106":
            text = "Адрес - ОК";
            textclass = "fine"; 
            break;
        case "107":
            text = "Введите действительный адрес!";
            textclass = "warning"; 
            break;
        case "201":
            text = "Игровое имя: <br />Имя, которое Вы выбираете своему персонажу. Одно имя не может повторяться в одной вселенной.";
            break;
        case "202":
            text = "Электронный адрес: <br />Для активации аккаунта введите действительный адрес. Для активации даётся три дня, во  время которых Вы тоже сможете играть.";
            break;
        case "203":
            text = "";
            break;
        case "204":
            text = "Основные положения:<br /> Для начала игры Вы должны принять основные положения.";
            break;
        case "205":
            text = "Пароль: <br/>Пароль защищает Ваш игровой аккаунт от захода на него других людей. Никогда не давайте никому свой пароль.";
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

<a href="#pustekuchen" style="display:none;">Link Логин</a>

<div id="main">
    
<div id="login">
     <a name="pustekuchen"></a>
     <div id="login_text_1">
        <div style="position:absolute;left:160px;width:110px;">Имя</div>
        <div style="position:absolute;left:275px;width:50px;">Пароль</div>
     </div>

     <div id="login_input">
     <table cellspacing="0" cellpadding="0" border="0"><tr style="vertical-align:top;"><td style="padding-right:4px;">
         <form name="loginForm" action="" method="POST" onSubmit="changeAction('login');" target="_self" >
         <input type="hidden" name="v" value="2">
         <span>
             <select tabindex="1" name='universe' class="eingabe" style="width:144px;">
                <option value="">Вселенная...</option>
                             <option value="uni1.ogame.ru" >

                1. Вселенная</option>
                             <option value="uni2.ogame.ru" >
                2. Вселенная</option>
                             <option value="uni3.ogame.ru" >
                3. Вселенная</option>
                             <option value="uni4.ogame.ru" >
                4. Вселенная</option>

                             <option value="uni5.ogame.ru" >
                5. Вселенная</option>
                             <option value="uni6.ogame.ru" >
                6. Вселенная</option>
                             <option value="uni7.ogame.ru" >
                7. Вселенная</option>
                             <option value="uni8.ogame.ru" >

                8. Вселенная</option>
                             <option value="uni9.ogame.ru" >
                9. Вселенная</option>
                             <option value="uni10.ogame.ru" >
                10. Вселенная</option>
                             <option value="uni11.ogame.ru" >
                11. Вселенная</option>

                             <option value="uni12.ogame.ru" >
                12. Вселенная</option>
                             <option value="uni13.ogame.ru" >
                13. Вселенная</option>
                             <option value="uni14.ogame.ru" >
                14. Вселенная</option>
                             <option value="uni15.ogame.ru" >

                15. Вселенная</option>
                             <option value="uni16.ogame.ru" >
                16. Вселенная</option>
                             <option value="uni17.ogame.ru" >
                17. Вселенная</option>
                             <option value="uni18.ogame.ru" >
                18. Вселенная</option>

              
             </select>
         </span>
         <td style="padding-right:3px;">
         <span><input tabindex="2" class="eingabe" maxlength="20" name="login"   alt=Имя style="width:111px;top:0px"/></span>
         <td>
         <span><input tabindex="3" maxlength="20" type="password" class="eingabe" name="pass" style="width:113px;top:0px" alt=Пароль /></span>
         <td style="padding-top:2px;">
         <!--<span class="link" onclick="submitLogin()" style="padding-left:7px;">LOGIN</span>-->
         <input type="image" src="../img/login_button.jpg" alt="Login" class="loginButton" name="button" id="button" onmouseover="document.getElementById('button').src='../img/login_button2.jpg';" onmouseout="document.getElementById('button').src='../img/login_button.jpg';">

         </form>
     </tr></table>
     </div>
     <div id="login_text_2">
        <div style="position:absolute;text-align:right;width:439px;top:15px;"><a href="#" onclick="changeAction('getpw');">Забыли пароль?</a></div>
        <div style="position:absolute;left:12px;width:200px;top:15px;text-align:left;">Заходя в игру, я принимаю <a target="_blank" href="http://impressum.gameforge.de/index.php?lang=ru&art=tac&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000">Основные положения</a>.</div>
     </div>   
     
     <div id="copyright">

        (C) 2007 by <a target="_blank" href="http://www.gameforge.de">Gameforge Productions GmbH</a>. Все права защищены.&nbsp;&nbsp;
     </div>
     <div id="downmenu">
        <a href="regeln.html">Правила</a>&nbsp;
        <a target="_blank" href="http://impressum.gameforge.de/index.php?lang=ru&art=impress&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000">Impressum</a>&nbsp;
        <a target="_blank" href="http://impressum.gameforge.de/index.php?lang=ru&art=tac&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000">Основные положения</a>

     </div>    
</div>


<div class="products">

 </div>
<div id="gimmik_1">
</div>
<div id="gimmik_2">
</div>

<div id="mainmenu">

    <div class="menupoint">Главная</div>
    <a href="about.html">Про ОГейм</a>
    <a href="screenshots.html">Картинки</a>
    <a href="register.php">Присоединиться</a>
    <a href="http://board.ogame.ru" target="_blank">Форум</a>
</div>

<div id="rightmenu" class="rightmenu">
    <div id="title">Добро пожаловать в ОГейм</div>
    <div id="content">
        <div id="text1"><strong>ОГейм</strong> - это <strong>космическая стратегия</strong>. 
<strong>Тысячи игроков</strong> выступают <strong>одновременно</strong> против друг друга. Для игры Вам нужен всего лишь нормальный браузер.</div>

        <div id="register" class="bigbutton" onclick="document.location.href='register.php';">РЕГИСТРИРУЙТЕСЬ И ИГРАЙТЕ!</div>
        <div id="text2">Зарегистрируйтесь и откройте для себя фантастический мир ОГейм!</div>
    </div>
</div>




<script>
document.loginForm.universe.focus();
</script>

</body>

</html>