<html>
<head>
<link rel="stylesheet" type="text/css" href="http://graphics.ogame-cluster.net/download/use/evolutionformate.css">
<link rel="stylesheet" type="text/css" href="/game/css/registration.css" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<script language="JavaScript" src="/game/js/tw-sack.js"></script>
<script language="JavaScript" src="/game/js/registration.js"></script>
<script language="JavaScript">
function printMessage(code, div) {
    var textclass = "";

    if (div == null) {
        div = "statustext";
    }
    switch (code) {
        case "0":
            text = "OK";
            textclass = "fine";
            break;
        case "101":
            text = "Такое имя уже существует!";
            textclass = "warning";
            break;
        case "102":
            text = "Этот адрес уже используется!";
            textclass = "warning";
            break;
        case "103":
            text = "Имя должно содержать от 3 до 20 символов!";
            textclass = "warning";
            break;
        case "104":
            text = "Адрес недействителен!";
            textclass = "warning";
            break;
        case "105":
            text = "Имя игрока - в порядке";
            textclass = "fine";
            break;
        case "106":
            text = "Адрес - а порядке";
            textclass = "fine";
            break;
        case "107":
            text = "Адрес недействителен!";
            textclass = "warning";
            break;
        case "201":
            text = "Имя в игре: <br />Это имя Вашего персонажа в игре. В одной вселенной не может быть двух одинаковых имён.";
            break;
        case "202":
            text = "Электронный адрес: <br />На этот адрес будет выслан Ваш пароль. Если Вы введёте чужой или недйствительный адрес, то играть  Вы, соответственно, не сможете.";
            break;
        case "203":
            text = "";
            break;
        case "204":
            text = "Для того, чтобы начать игру Вы должны согласиться с Основными Положениями.";
            break;
        default:
            return;
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
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<center>
<h1 style="font-size: 22;">Огейм Вселенная 5 Регистрация</h1>

<form id="registration" method="POST">
<table width="700">
 <tr>
  <td>
   <table width="380">
    <tr>
     <td class="c" colspan="2">Данные об игроке</td>
    </tr>
    <tr>

     <th class="">Имя в игре</th><th><input name="character" size="20"  value="" onfocus="javascript:showInfo('201');javascript:pollUsername();" onblur="javascript:stopPollingUsername();" /></th>
    </tr>
    <tr>
     <th class="">Электронный адрес</th><th><input name="email" size="20" value="" onfocus="javascript:showInfo('202');javascript:pollEmail();" onblur="javascript:stopPollingEmail();" /></th>
    </tr>
     <th class="">Я соглашаюсь с <a href='http://agb.gameforge.de/index.php?lang=ru&art=tac&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000' target='_blank'>Основными Положениями</a></th><th><input type="checkbox" name="agb" onfocus="javascript:showInfo('204');"/></th>
    </tr>

        <tr>
     <th colspan="2" style="text-align: center;"><input type="submit" value="Зарегистрироваться" /></th>
          <input type="hidden" name="v" value="3" /><input type="hidden" name="step" value="validate" />
          <input type="hidden" name="try" value="2" />
          <input type="hidden" name="kid" value="" />
     </tr>
   </table>
  </td>
  <td>

   <table width="320">
    <tr>
     <td class="c">Инфо</td>
    </tr>
    <tr style="height: 93;">
     <th><p /><div id="infotext"></div><p /><div id="statustext"></div><div id="debug"></div> </th>
    </tr>
   </table>

  </td>
 </tr>
</table>
</form>
</center>
</body>
</html>
