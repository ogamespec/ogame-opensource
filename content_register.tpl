    <div id="rightmenu" class="rightmenu_register"> 
        <div id="title"><?=loca("JOIN_TITLE");?></div> 
        <div id="content"  align="justify"> 
            <div id="text1"><?=loca("JOIN_HEAD");?></div> 
            
            <div id="register_container"> 
                <form name="registerForm"  method="POST" action="" onsubmit="changeAction('register');" > 
                
                <table> 
                
                <tr> 
                    <td class="table_lable"><?=loca("JOIN_NAME");?></td> 
                    <td class="table_input"><input class="eingabe"  type=text name=character size=20 onfocus="javascript:showInfo('201');javascript:pollUsername();" onblur="javascript:stopPollingUsername();"></td> 
                </tr> 
                <tr> 
                    <td class="table_lable"><?=loca("JOIN_EMAIL");?></td>                
                    <td class="table_input"><input class="eingabe"  type=text name=email size=20 onfocus="javascript:showInfo('202')"></td> 
                </tr> 
                                <tr> 
                    <td class="table_lable"><?=loca("JOIN_PASS");?></td>                
                    <td class="table_input"><input class="eingabe" type="password" name=password size=20 onfocus="showInfo('205');"></td> 
                </tr> 
                                <tr><td id="uni_label"><?=loca("JOIN_ADVICE");?></td> 
                <td class="table_input"> 
                                <select name=universe size=1 class="eingabe" style="width:122px;"> 

<?php
    require_once "uni.php";

    foreach ( $UniList as $i=>$val) {
        echo "<option value=\"".$val['uniurl']."\" ";
        if ($i == 3) echo "selected";
        echo ">$i";
        if ($i == 3) echo " (".loca("JOIN_TIP").")";
        echo "</option>\n";
    }
?>

</select> 
                <div id="uni_infos_link"><a href="unis.php"><?=loca("JOIN_UNIS");?></a></div> 
                </td></tr> 
                <tr id="agb_zeile"> 
                <td></td> 
                <td id="table_agb"> 
                    <input type=checkbox name="agb" onfocus="javascript:showInfo('204');"> 
                    <?=loca("JOIN_IACCEPT");?> <a class="register_agb" href="#"><?=loca("JOIN_TAC");?></a>                </td> 
                </tr> 
                </table> 
            
                <input type="hidden" name="v" value="3" /><input type="hidden" name="step" value="validate" /> 
                <input type="hidden" name="try" value="2" /> 
                <input type="hidden" name="kid" value="" /> 
                <input type="hidden" name="lang" value="<?=$_COOKIE['ogamelang'];?>" /> 
                
                                    <input type="hidden" name="errorCodeOn" value="1" />    
                                
                </form> 
            </div> 
            <div id="infotext"></div> 
            <div id="statustext"></div> 
            <div id="register_submit" onclick="changeAction('register');document.registerForm.submit();"><?=loca("JOIN_REGISTER");?></div> 
        </div> 
    </div> 
