<div id="login">
     <a name="pustekuchen"></a>
     <div id="login_text_1">
        <div style="position:absolute;left:160px;width:110px;"><?=loca("LOGIN_NAME");?></div>
        <div style="position:absolute;left:275px;width:50px;"><?=loca("LOGIN_PASS");?></div>
     </div>

     <div id="login_input">
     <table cellspacing="0" cellpadding="0" border="0"><tr style="vertical-align:top;"><td style="padding-right:4px;">
         <form name="loginForm" action="" method="POST" onSubmit="changeAction('login');" target="_self" >
         <input type="hidden" name="v" value="2">
         <span>
             <select tabindex="1" name='universe' class="eingabe" style="width:144px;">
                <option value=""><?=loca("LOGIN_CHOOSE_UNI");?></option>

<?php

// Загрузить список вселенных.

?>
              
             </select>
         </span>
         <td style="padding-right:3px;">
         <span><input tabindex="2" class="eingabe" maxlength="20" name="login"   alt=Имя style="width:111px;top:0px"/></span>
         <td>
         <span><input tabindex="3" maxlength="20" type="password" class="eingabe" name="pass" style="width:113px;top:0px" alt=Пароль /></span>
         <td style="padding-top:2px;">
         <!--<span class="link" onclick="submitLogin()" style="padding-left:7px;">LOGIN</span>-->
         <input type="image" src="img/login_button.jpg" alt="Login" class="loginButton" name="button" id="button" onmouseover="document.getElementById('button').src='img/login_button2.jpg';" onmouseout="document.getElementById('button').src='img/login_button.jpg';">

         </form>
     </tr></table>
     </div>
     <div id="login_text_2">
        <div style="position:absolute;text-align:right;width:439px;top:15px;"><a href="#" onclick="changeAction('getpw');"><?=loca("LOGIN_REMIND");?></a></div>
        <div style="position:absolute;left:12px;width:200px;top:15px;text-align:left;"><?=loca("LOGIN_CONFIRM");?> <a target="_blank" href="http://impressum.gameforge.de/index.php?lang=ru&art=tac&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000"><?=loca("LOGIN_IMPRESSUM");?></a>.</div>
     </div>   
     
     <div id="copyright">

        (C) 2007 by <a target="_blank" href="http://www.gameforge.de">Gameforge Productions GmbH</a>. <?=$COPYRIGHT?>&nbsp;&nbsp;
     </div>
     <div id="downmenu">
        <a href="regeln.html"><?=loca("DOWN_RULES");?></a>&nbsp;
        <a target="_blank" href="http://impressum.gameforge.de/index.php?lang=ru&art=impress&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000"><?=loca("DOWN_IMPRINT");?></a>&nbsp;
        <a target="_blank" href="http://impressum.gameforge.de/index.php?lang=ru&art=tac&special=&&f_text=b1daf2&f_text_hover=ffffff&f_text_h=061229&f_text_hr=061229&f_text_hrbg=061229&f_text_hrborder=9EBDE4&f_text_font=arial%2C+arial%2C+arial%2C+sans-serif&f_bg=000000"><?=loca("DOWN_TAC");?></a>

     </div>    
</div>