<?php

// Настройки.

// Es wurden bereits 2 E-Mails an Dich geschickt. Heute ist kein weiterer E-Mail-Versand möglich, bitte versuch es morgen nochmal.

$OptionsMessage = "";
$OptionsError = "";

loca_add ( "menu", $GlobalUni['lang'] );
loca_add ( "options", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

function IsChecked ($option)
{
    global $GlobalUser;
    if ( $GlobalUser[$option] ) return "checked=checked";
    else return "";
}

function IsSelected ($option, $value)
{
    global $GlobalUser;
    if ( $GlobalUser[$option] == $value ) return "selected";
    else return "";
}

function isValidEmail($email){
	return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
}

PageHeader ("options");

$unitab = LoadUniverse ();
$speed = $unitab['speed'];

$prem = PremiumStatus ($GlobalUser);
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>
 <table width="519">

<?php

    // Выключить Режим Отпуска.
    if ( method () === "POST") {

        if ( time () >= $GlobalUser['vacation_until'] && $_POST['urlaub_aus'] === "on" && $GlobalUser['vacation'] )
        {
            $OptionsError = va ( loca("OPTIONS_MSG_VMDISABLED"), $GlobalUser['oname'] ) . "\n<br/>\n";
            $query = "UPDATE ".$db_prefix."users SET vacation=0,vacation_until=0 WHERE player_id=".intval($GlobalUser['player_id']);
            dbquery ($query);
            $GlobalUser['vacation'] = $GlobalUser['vacation_until'] = 0;
        }
    }

    // ======================================================================================
    // Аккаунт неактивирован.

    if ( $GlobalUser['validated'] == 0 ) {

        // Обработать POST-запрос.
        if ( method () === "POST") {

            $ip = $_SERVER['REMOTE_ADDR'];

            if ( key_exists ( "validate", $_POST ) ) {    // Заказать активационную ссылку.
                if ( $ip !== "127.0.0.1" ) SendChangeMail ( $GlobalUser['oname'], $GlobalUser['email'], $GlobalUser['pemail'], $GlobalUser['validatemd'] );
                $OptionsMessage = loca ("OPTIONS_MSG_VALIDATE");
            }
            else if ( $_POST['db_email'] !== $GlobalUser['email'] && $_POST['db_email'] !== "" ) {        // Сменить адрес
                $email = $_POST['db_email'];
                if ( $GlobalUser['password'] !== md5 ($_POST['db_password'] . $db_secret ) ) $OptionsError = loca ("OPTIONS_ERR_NEEDPASS");
                else if ( !isValidEmail ($email) ) $OptionsError = loca ("OPTIONS_ERR_EMAIL");
                else if ( IsEmailExist ($email)) $OptionsError = loca ("OPTIONS_ERR_EMAILUSED");

                if ( $OptionsError === "" )
                {
                    $ack = md5(time ().$db_secret);
                    $query = "UPDATE ".$db_prefix."users SET validated = 0, validatemd = '".$ack."', email = '".$email."' WHERE player_id = " . $GlobalUser['player_id'];
                    dbquery ($query);
                    AddChangeEmailEvent ($GlobalUser['player_id']);
                    if ( $ip !== "127.0.0.1" ) SendChangeMail ( $GlobalUser['oname'], $email, $GlobalUser['pemail'], $ack );
                    $GlobalUser['email'] = $email;
                    $OptionsError = loca ("OPTIONS_USER_EMAIL_TIP");
                }
            }
        }

?>

 <form action="index.php?page=options&session=<?php echo $session;?>&mode=change" method="POST" > 
        <input type="hidden" name="design"     value='on' /> 
    <tr><td class="c" colspan ="2"><?php echo loca("OPTIONS_USER");?></td></tr> 
    <tr> 
        <th><a title="<?php echo loca("Этот адрес можно в любое время изменить. Через 7 дней без изменений он станет постоянным.");?>"><?php echo loca("OPTIONS_USER_EMAIL");?></a></th> 
        <th><input type="text" name="db_email" maxlength="100" size="20" value="<?php echo $GlobalUser['email'];?>" /></th> 
    </tr> 
    <tr> 
        <th>Пароль</th> 
        <th><input type="password" name="db_password" size ="20" value="" /></th> 
    </tr> 
    <tr> 
        <th colspan=2><input type="submit" value="Используйте введённый адрес" /></th> 
    </tr> 
    </form> 
    <form action="index.php?page=options&session=<?php echo $session;?>" method="POST" > 
    <input type=hidden name="validate" value="1"> 
    <tr> 
        <th colspan=2> 
                    <p style="color:#ff0000;padding-top:10px;padding-bottom:5px;">Ваш игровой акаунт ещё не активирован. Тут Вы можете заказать письмо с активационной ссылкой.</p> 
            <input type="submit" value="Заказать активационную ссылку" /> 
        </th> 
    </tr> 
   </form> 
 </table> 

<?php
    // ======================================================================================
    // Режим отпуска включен.

    }
    else if ( $GlobalUser['vacation'] )
    {

?>

 <form action="index.php?page=options&session=<?php echo $session;?>&mode=change" method="POST" >
  <tr> <td class="c" colspan="2">Режим отпуска</td>  </tr>
  <tr>   </tr>
  <tr> <th colspan=2>   <?php echo loca("OPTIONS_MSG_VMENABLED");?><br />
     <?php echo date ("d.m.Y. H:i:s", $GlobalUser['vacation_until']);?>   </th>   </tr>
<?php
    if ( time () >= $GlobalUser['vacation_until'] )
    {
?>
     <tr>
   <th>
      отключить   </th>
   <th><input type="checkbox" name="urlaub_aus" />
   </th>
  </tr>
<?php
    }
    else
    {
?>
             <tr>
               <th><a title="<?php echo loca("OPTIONS_ACCOUNT_DEL_TIP");?>"><?php echo loca("OPTIONS_ACCOUNT_DEL");?></a></th>
               <th><input type="checkbox" name="db_deaktjava" <?php echo IsChecked("disable");?> />
      <?php
    if ($GlobalUser['disable']) echo "am: " . date ("Y-m-d H:i:s", $GlobalUser['disable_until']) . "<input type='hidden' name=loeschen_am value=".date ("Y-m-d H:i:s", $GlobalUser['disable_until']).">";
?>           </th>
              </tr>
<?php
    }
?>

     <tr>   <th colspan=2><input type="submit" value="<?php echo loca("OPTIONS_APPLY");?>" /></th>  </tr>
 </form>
 </table>

<?php
    // ======================================================================================
    // Обычное меню.

    }
    else
    {

        // Обработать POST-запрос.
        if ( method () === "POST" && !key_exists ( 'urlaub_aus', $_POST) ) {

            if ( $GlobalUser['name_changed'] == 0 && $_POST['db_character'] !== $GlobalUser['oname'] ) {        // Сменить имя.
                $forbidden = explode ( ",", "hitler, fick, adolf, legor, aleena, ogame, mainman, fishware, osama, bin laden, stalin, goebbels, drecksjude, saddam, space, ringkeeper, administration" );
                if ( IsUserExist ( $_POST['db_character'] )) $OptionsError = loca ("OPTIONS_ERR_EXISTNAME");
                else if ( !CanChangeName ($GlobalUser['player_id']) ) $OptionsError = loca ("OPTIONS_ERR_NAME_WEEK");
                else if ( mb_strlen ($_POST['db_character']) < 3 || mb_strlen ($_POST['db_character']) > 20 ) $OptionsError = loca ("OPTIONS_ERR_NAME_3_20");
                else if ( preg_match ( '/[<>()\[\]{}\\\\\/\`\"\'.,:;*+]/', $_POST['db_character'] )) $OptionsError = loca ("OPTIONS_ERR_NAME_SPECIAL");
                $lower = mb_strtolower ($_POST['db_character'], 'UTF-8');
                foreach ( $forbidden as $i=>$name) {
                    if ( $lower === $name ) $OptionsError = loca ("OPTIONS_ERR_NAME");
                }

                if ( $OptionsError === "" )
                {
                    ChangeName ( $GlobalUser['player_id'], $_POST['db_character'] );
                    $OptionsError = loca ("OPTIONS_MSG_NAME");
                    $GlobalUser['name_changed'] = 1;
                    $GlobalUser['oname'] = $_POST['db_character'] ;
                    Logout ( $GlobalUser['session'] );
                }
            }

            else if ( $_POST['newpass1'] !== "" ) {        // Сменить пароль

                if ( $_POST['newpass1'] !== $_POST['newpass2'] ) $OptionsError = loca ("OPTIONS_ERR_NEWPASS");
                else if ( !preg_match ( "/^[_a-zA-Z0-9]+$/", $_POST['newpass1'] ) ) $OptionsError = loca ("OPTIONS_ERR_PASS_SPECIAL");
                else if ( strlen ( $_POST['newpass1'] ) < 8 ) $OptionsError = loca ("OPTIONS_ERR_PASS_8");
                else if ( $GlobalUser['password'] !== md5 ($_POST['db_password'] . $db_secret ) ) $OptionsError = loca ("OPTIONS_ERR_OLDPASS");

                if ( $OptionsError === "" )
                {
                    $md5 = md5 ($_POST['newpass1'] . $db_secret );
                    $query = "UPDATE ".$db_prefix."users SET password = '".$md5."' WHERE player_id = " . intval($GlobalUser['player_id']);
                    dbquery ($query);
                    $OptionsError = loca ("OPTIONS_MSG_PASS");    // TODO: OPTIONS_MSG_UNSAFE, OPTIONS_MSG_SIMPLE
                    Logout ( $GlobalUser['session'] );
                }
            }

            else if ( $_POST['db_email'] !== $GlobalUser['pemail'] && $_POST['db_email'] !== "" ) {        // Сменить адрес
                $email = $_POST['db_email'];
                if ( $GlobalUser['password'] !== md5 ($_POST['db_password'] . $db_secret ) ) $OptionsError = loca ("OPTIONS_ERR_NEEDPASS");
                else if ( !isValidEmail ($email) ) $OptionsError = loca ("OPTIONS_ERR_EMAIL");
                else if ( IsEmailExist ($email)) $OptionsError = loca ("OPTIONS_ERR_EMAILUSED");

                if ( $OptionsError === "" )
                {
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $ack = md5(time ().$db_secret);
                    $query = "UPDATE ".$db_prefix."users SET validated = 0, validatemd = '".$ack."', email = '".$email."' WHERE player_id = " . intval($GlobalUser['player_id']);
                    dbquery ($query);
                    AddChangeEmailEvent ($GlobalUser['player_id']);
                    if ( $ip !== "127.0.0.1" ) SendChangeMail ( $GlobalUser['oname'], $email, $GlobalUser['pemail'], $ack );
                    $GlobalUser['email'] = $email;
                    $OptionsError = loca ("OPTIONS_USER_EMAIL_TIP");
                }
            }

            if ( $_POST['urlaubs_modus'] === "on" && $GlobalUser['vacation'] == 0 ) {        // Включить режим отпуска
                $vacation_min = max ( 12*60*60, (2 * 24 * 60 * 60) / $speed);    // не менее 12 часов
                $vacation_until = time() + $vacation_min;

                if ( CanEnableVacation ($GlobalUser['player_id']) ) {
                    $query = "UPDATE ".$db_prefix."users SET vacation=1,vacation_until=$vacation_until WHERE player_id=".$GlobalUser['player_id'];
                    dbquery ($query);
                    $GlobalUser['vacation'] = 1;
                    $GlobalUser['vacation_until'] = $vacation_until;
                    $query = "UPDATE ".$db_prefix."planets SET mprod = 0, kprod = 0, dprod = 0, sprod = 0, fprod = 0, ssprod = 0 WHERE owner_id = " . $GlobalUser['player_id'];
                    dbquery ($query);
                    MyGoto ( "options" );
                }
                else $OptionsError = loca ("OPTIONS_ERR_VM");
            }

            if ( $_POST['db_deaktjava'] === "on" && $GlobalUser['disable'] == 0 ) {        // Поставить аккаунт на удаление
                $disable_until = time() + (7 * 24 * 60 * 60);

                $query = "UPDATE ".$db_prefix."users SET disable=1,disable_until=$disable_until WHERE player_id=".intval($GlobalUser['player_id']);
                dbquery ($query);
                $GlobalUser['disable'] = 1;
                $GlobalUser['disable_until'] = $disable_until;
            }

            if ( !key_exists("db_deaktjava", $_POST) && $GlobalUser['disable'] ) {    // Отменить удаление аккаунта
                $query = "UPDATE ".$db_prefix."users SET disable=0,disable_until=0 WHERE player_id=".$GlobalUser['player_id'];
                dbquery ($query);
                $GlobalUser['disable'] = 0;
                $GlobalUser['disable_until'] = 0;
            }

            // Сохранить путь к скину + галочка показывать/выключить скин.
            // TODO : OPTIONS_MSG_SKIN
            ChangeSkinPath ( $GlobalUser['player_id'], $_POST['dpath'] );
            EnableSkin ( $GlobalUser['player_id'], ($_POST['design']==="on"?1:0) );

            $sortby = min ( max(0, intval($_POST['settings_sort'])), 2);
            $sortorder = min ( max(0, intval($_POST['settings_order'])), 1);
            $deactip = (int) key_exists ( 'noipcheck', $_POST );
            $maxspy = min( max (1, intval($_POST['spio_anz'])), 99);
            $maxfleetmsg = min( max (1, intval($_POST['settings_fleetactions'])), 99);
            $query = "UPDATE ".$db_prefix."users SET deact_ip=$deactip, sortby=$sortby, sortorder=$sortorder, maxspy=$maxspy, maxfleetmsg=$maxfleetmsg WHERE player_id=".intval($GlobalUser['player_id']);
            dbquery ($query);
            $GlobalUser['sortby'] = $sortby;
            $GlobalUser['sortorder'] = $sortorder;
            $GlobalUser['maxspy'] = $maxspy;
            $GlobalUser['maxfleetmsg'] = $maxfleetmsg;
            $GlobalUser['deact_ip'] = $deactip;
            $GlobalUser['skin'] = $_POST['dpath'];
            $GlobalUser['useskin'] = ($_POST['design']==="on"?1:0);
        }
?>

 <form action="index.php?page=options&session=<?php echo $session;?>&mode=change" method="POST" >
     <tr><td class="c" colspan ="2">Данные пользователя</td></tr>
<tr>
<?php
    if ( $GlobalUser['name_changed'] )
    {
?>
      <th><a title="<?php echo loca("OPTIONS_ERR_NAME_WEEK");?>"><?php echo loca("OPTIONS_USER_NAME");?></a></th>
   <th><?php echo $GlobalUser['oname'];?></th>
<?php
    }
    else
    {
?>
      <th><?php echo loca("OPTIONS_USER_NAME");?></th>
   <th><input type="text" name="db_character" size ="20" value="<?php echo $GlobalUser['oname'];?>" /><br/></th>
<?php
    }
?>

    </tr>
  <tr>
  <th><?php echo loca("OPTIONS_USER_OLDPASS");?></th>

   <th><input type="password" name="db_password" size ="20" value="" /></th>
  </tr>
  <tr>
  <th><?php echo loca("OPTIONS_USER_NEWPASS1");?></th>
   <th><input type="password" name="newpass1" size="20" maxlength="40" /></th>
  </tr>
  <tr>
  <th><?php echo loca("OPTIONS_USER_NEWPASS2");?></th>

   <th><input type="password" name="newpass2" size="20" maxlength="40" /></th>
  </tr>
  <tr>
  <th><a title="<?php echo loca("OPTIONS_USER_EMAIL_TIP");?>"><?php echo loca("OPTIONS_USER_EMAIL");?></a></th>
  <th><input type="text" name="db_email" maxlength="100" size="20" value="<?php echo $GlobalUser['email'];?>" /></th>
  </tr>
  <tr>
  <th><?php echo loca("OPTIONS_USER_PEMAIL");?></th>

   <th><?php echo $GlobalUser['pemail'];?></th>
  </tr>
   <tr><th colspan="2">
  </tr>
  <tr>
  <td class="c" colspan="2"><?php echo loca("OPTIONS_GENERAL");?></td>
  </tr>
  <tr>

   </select>
   </th>
  </tr>

   <th><?php echo loca("OPTIONS_GENERAL_ORDER");?></th>
   <th>
   <select name="settings_sort">
    <option value="0" <?php echo IsSelected("sortby", 0);?> ><?php echo loca("OPTIONS_GENERAL_ORDER1");?></option>
    <option value="1" <?php echo IsSelected("sortby", 1);?> ><?php echo loca("OPTIONS_GENERAL_ORDER2");?></option>
    <option value="2" <?php echo IsSelected("sortby", 2);?> ><?php echo loca("OPTIONS_GENERAL_ORDER3");?></option>
   </select>

   </th>
  </tr>
  <tr>
   <th><?php echo loca("OPTIONS_GENERAL_ORDERBY");?></th>
   <th>
   <select name="settings_order">
     <option value="0" <?php echo IsSelected("sortorder", 0);?>><?php echo loca("OPTIONS_GENERAL_ORDERBY1");?></option>
     <option value="1" <?php echo IsSelected("sortorder", 1);?>><?php echo loca("OPTIONS_GENERAL_ORDERBY2");?></option>

   </select>
   </th>
 </tr>

  <th><?php echo loca("OPTIONS_GENERAL_SKINPATH");?><br /> <a href="http://oldogame.ru/download/" target="_blank"><?php echo loca("OPTIONS_GENERAL_DOWNLOAD");?></a></th>
   <th><input type=text name="dpath" maxlength="80" size="40" value="<?php echo $GlobalUser['skin'];?>" /> <br />
  <?php
            // Если путь к скину пустой выдать список доступных скинов на сервере графики.
            if ( $GlobalUser['skin'] === "" ) {
    ?>
  <select name="dpath" size="1" >
   <option selected>  </option>
      <option value="http://oldogame.ru/download/use/allesnurgeklaut/">allesnurgeklaut </option>
      <option value="http://oldogame.ru/download/use/ally-cpb/">allycpb </option>
      <option value="http://oldogame.ru/download/use/asgard/">asgard </option>
      <option value="http://oldogame.ru/download/use/aurora/">aurora </option>
      <option value="http://oldogame.ru/download/use/bluedream/">bluedream </option>
      <option value="http://oldogame.ru/download/use/bluegalaxy/">bluegalaxy </option>
      <option value="http://oldogame.ru/download/use/blueplanet/">blueplanet </option>
      <option value="http://oldogame.ru/download/use/bluechaos/">bluechaos </option>
      <option value="http://oldogame.ru/download/use/bluemx/">blue-mx </option>
      <option value="http://oldogame.ru/download/use/brace/">brace </option>
      <option value="http://oldogame.ru/download/use/brotstyle/">brotstyle </option>
      <option value="http://oldogame.ru/download/use/dd/">dd </option>
      <option value="http://oldogame.ru/download/use/eclipse/">eclipse </option>
      <option value="http://oldogame.ru/download/use/empire/">empire </option>
      <option value="http://oldogame.ru/download/use/EpicBlue/">epicblue </option>
      <option value="http://oldogame.ru/download/use/evolution/">evolution </option>
      <option value="http://oldogame.ru/download/use/freakyfriday/">freakyfriday </option>
      <option value="http://oldogame.ru/download/use/g3cko/">g3cko </option>
      <option value="http://oldogame.ru/download/use/gruen/">gruen </option>
      <option value="http://oldogame.ru/download/use/infraos/">infraos </option>
      <option value="http://oldogame.ru/download/use/lambda/">lambda </option>
      <option value="http://oldogame.ru/download/use/lego/">lego </option>
      <option value="http://oldogame.ru/download/use/militaryskin/">militaryskin </option>
      <option value="http://oldogame.ru/download/use/okno/">okno </option>
      <option value="http://oldogame.ru/download/use/ovisio/">ovisio </option>
      <option value="http://oldogame.ru/download/use/ovisiofarbig/">ovisiofarbig </option>
      <option value="http://oldogame.ru/download/use/Paint/">paint </option>
      <option value="http://oldogame.ru/download/use/quadratorstyle/">quadratorstyle </option>
      <option value="http://oldogame.ru/download/use/real/">real </option>
      <option value="http://oldogame.ru/download/use/redfuturistisch/">redfuturistisch </option>
      <option value="http://oldogame.ru/download/use/redvision/">redvision </option>
      <option value="http://oldogame.ru/download/use/reloaded/">reloaded </option>
      <option value="http://oldogame.ru/download/use/shadowpato/">shadowpato </option>
      <option value="http://oldogame.ru/download/use/simpel/">simpel </option>
      <option value="http://oldogame.ru/download/use/starwars/">starwars </option>
      <option value="http://oldogame.ru/download/use/w4wooden4ce/">w4wooden4ce </option>
      <option value="http://oldogame.ru/download/use/xonic/">xonic </option>
    <?php
            }
  ?>
  </select>

   </th>
  </tr>
  <tr>
  <th><?php echo loca("OPTIONS_GENERAL_SHOWSKIN");?></th>
   <th>
    <input type="checkbox" name="design"
    <?php echo IsChecked("useskin");?> />
   </th>
  </tr>

  <tr>
    <th><a title="<?php echo loca("OPTIONS_GENERAL_DEACTIP_TIP");?>"><?php echo loca("OPTIONS_GENERAL_DEACTIP");?></a></th>
   <th><input type="checkbox" name="noipcheck"  <?php echo IsChecked("deact_ip");?>/></th>
  </tr>
  <tr>
   <td class="c" colspan="2"><?php echo loca("OPTIONS_GALAXY");?></td>
  </tr>
  <tr>

   <th><a title="<?php echo loca("OPTIONS_GALAXY_SPIES_TIP");?>"><?php echo loca("OPTIONS_GALAXY_SPIES");?></a></th>
   <th><input type="text" name="spio_anz" maxlength="2" size="2" value="<?php echo $GlobalUser['maxspy'];?>" /></th>
  </tr>
  <!--<tr>
   <th><?php echo loca("OPTIONS_GALAXY_TOOLTIPTIME");?></th>
   <th><input type="text" name="settings_tooltiptime" maxlength="2" size="2" value="0" /> сек.</th>
  </tr>-->
  <tr>
   <th><?php echo loca("OPTIONS_GALAXY_MAXMSG");?></th>
   <th><input type="text" name="settings_fleetactions" maxlength="2" size="2" value="<?php echo $GlobalUser['maxfleetmsg'];?>" /></th>
  </tr>

<?php
    if ( $prem['commander'] )    // Дополнительные настройки Командира
    {
?>
  </tr>
     <tr>
   <th><?php echo loca("OPTIONS_GALAXY_KEYS");?></th>
   <th><?php echo loca("OPTIONS_GALAXY_SHOWKEYS");?></th>
  </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/e.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_SPY");?></th>

   <th><input type="checkbox" name="settings_esp" checked='checked'/></th>
   </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/m.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_MSG");?></th>
   <th><input type="checkbox" name="settings_wri" checked='checked'/></th>
   </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/b.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_BUDDY");?></th>

   <th><input type="checkbox" name="settings_bud" checked='checked'/></th>
   </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/r.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_ROCKET");?></th>
   <th><input type="checkbox" name="settings_mis" checked='checked'/></th>
   </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/s.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_REPORT");?></th>

   <th><input type="checkbox" name="settings_rep" checked='checked'/></th>
   </tr>
      <tr>
   <td class="c" colspan="2"><?php echo loca("OPTIONS_MSG");?></td>
   <tr>
   <th><?php echo loca("OPTIONS_MSG_SORT");?></th>
  <th><input type="checkbox" name="settings_folders"  checked='checked'/></th>
</tr>

<tr>
    <td class="c" colspan="2"><font color='FF8900'>Newsfeed</font></td>
</tr>
<tr>
    <th>Активировать<input type=hidden name="feed_submit" value="1"></th>
    <th><input type="checkbox" name="feed_activated"  /></th>
</tr>
<?php
    }
?>

      
  <tr>
     <td class="c" colspan="2"><?php echo loca("OPTIONS_ACCOUNT");?></td>
  </tr>
  <tr>
     <th><a title="<?php echo loca("OPTIONS_ACCOUNT_VM_TIP");?>"><?php echo loca("OPTIONS_ACCOUNT_VM");?></a></th>
   <th>
    <input type="checkbox" name="urlaubs_modus"
     />
   </th>

  </tr>
  <tr>
   <th><a title="<?php echo loca("OPTIONS_ACCOUNT_DEL_TIP");?>"><?php echo loca("OPTIONS_ACCOUNT_DEL");?></a></th>
   <th><input type="checkbox" name="db_deaktjava"  <?php echo IsChecked("disable");?>/>
      <?php
    if ($GlobalUser['disable']) echo "am: " . date ("Y-m-d H:i:s", $GlobalUser['disable_until']);
?> </th>
  </tr>
  <tr>
   <th colspan=2><input type="submit" value="<?php echo loca("OPTIONS_APPLY");?>" /></th>

  </tr>
   
 </form>
 </table>

<?php
    }
?>

<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ($OptionsMessage, $OptionsError);
ob_end_flush ();
?>