    <div id="rightmenu" class="rightmenu_register"> 
        <div id="title">Присоединиться</div> 
        <div id="content"  align="justify"> 
            <div id="text1">Для игры Вам необходимо ввести <strong>игровое имя</strong>, <strong>пароль</strong> и <strong>электронный адрес</strong> и поставить галочку, чтобы принять основные положения.</div> 
            
            <div id="register_container"> 
                <form name="registerForm"  method="POST" action="" onsubmit="changeAction('register');" > 
                
                <table> 
                
                <tr> 
                    <td class="table_lable">Игровое имя:</td> 
                    <td class="table_input"><input class="eingabe"  type=text name=character size=20 onfocus="javascript:showInfo('201');javascript:pollUsername();" onblur="javascript:stopPollingUsername();"></td> 
                </tr> 
                <tr> 
                    <td class="table_lable">Электронный адрес:</td>                
                    <td class="table_input"><input class="eingabe"  type=text name=email size=20 onfocus="javascript:showInfo('202')"></td> 
                </tr> 
                                <tr> 
                    <td class="table_lable">Пароль:</td>                
                    <td class="table_input"><input class="eingabe" type="password" name=password size=20 onfocus="showInfo('205');"></td> 
                </tr> 
                                <tr><td id="uni_label">Мы советуем вселенную</td> 
                <td class="table_input"> 
                                <select name=universe size=1 class="eingabe" style="width:122px;"> 
                    <option value="uni1.ogame.ru" >1</option><option value="uni2.ogame.ru" >2</option><option value="uni3.ogame.ru" >3</option><option value="uni4.ogame.ru" >4</option><option value="uni5.ogame.ru" >5</option><option value="uni6.ogame.ru" >6</option><option value="uni7.ogame.ru" >7</option><option value="uni8.ogame.ru" >8</option><option value="uni9.ogame.ru" >9</option><option value="uni10.ogame.ru" >10</option><option value="uni11.ogame.ru" >11</option><option value="uni12.ogame.ru" >12</option><option value="uni13.ogame.ru" >13</option><option value="uni14.ogame.ru" >14</option><option value="uni15.ogame.ru" selected >15 (очень советуем!)</option>\n<option value="uni16.ogame.ru" >16</option><option value="uni17.ogame.ru" >17</option><option value="uni18.ogame.ru" >18</option>                </select> 
                <div id="uni_infos_link"><a href="unis.php">Особенности во вселенных</a></div> 
                </td></tr> 
                <tr id="agb_zeile"> 
                <td></td> 
                <td id="table_agb"> 
                    <input type=checkbox name="agb" onfocus="javascript:showInfo('204');"> 
                    Я принимаю <a class="register_agb" href="http://impressum.gameforge.de/index.php?lang=ru&art=tac&special=&&f_text=b5cfcd&f_text_hover=ffffff&f_text_h=9ebde4&f_text_hr=ffffff&f_text_hrbg=061229&f_text_hrborder=26324c&f_text_font=arial%2C+arial%2C+helvetica%2C+sans-serif&f_bg=000000">Основные положения</a>                </td> 
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
            <div id="register_submit" onclick="changeAction('register');document.registerForm.submit();">Отправить регистрацию</div> 
        </div> 
    </div> 
