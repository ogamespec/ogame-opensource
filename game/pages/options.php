<?php

// Settings.

// Es wurden bereits 2 E-Mails an Dich geschickt. Heute ist kein weiterer E-Mail-Versand möglich, bitte versuch es morgen nochmal.

$OptionsMessage = "";
$OptionsError = "";

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "options", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

function IsChecked ($option)
{
    global $GlobalUser;
    if ( $GlobalUser[$option] ) return "checked=checked";
    else return "";
}

function IsCheckedFlag ($flag)
{
    global $GlobalUser;
    if ( $GlobalUser['flags'] & $flag ) return "checked='checked'";
    else return "";
}

function IsSelected ($option, $value)
{
    global $GlobalUser;
    if ( $GlobalUser[$option] == $value ) return "selected";
    else return "";
}

function isValidEmail($email){
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

PageHeader ("options");

$speed = $GlobalUni['speed'];
$prem = PremiumStatus ($GlobalUser);

BeginContent ();
?>

 <table width="519">

<?php

    // Disable Vacation Mode.
    if ( method () === "POST") {

        if ( time () >= $GlobalUser['vacation_until'] && key_exists('urlaub_aus', $_POST) && $_POST['urlaub_aus'] === "on" && $GlobalUser['vacation'] )
        {
            $OptionsError = va ( loca("OPTIONS_MSG_VMDISABLED"), $GlobalUser['oname'] ) . "\n<br/>\n";
            $query = "UPDATE ".$db_prefix."users SET vacation=0,vacation_until=0 WHERE player_id=".intval($GlobalUser['player_id']);
            dbquery ($query);
            $GlobalUser['vacation'] = $GlobalUser['vacation_until'] = 0;
        }
    }

    // ======================================================================================
    // The account is not activated.

    if ( $GlobalUser['validated'] == 0 ) {

        // Process POST request.
        if ( method () === "POST") {

            $ip = $_SERVER['REMOTE_ADDR'];

            if ( key_exists ( "validate", $_POST ) ) {    // Request an activation link.
                if ( !localhost($ip) ) SendChangeMail ( $GlobalUser['oname'], $GlobalUser['email'], $GlobalUser['pemail'], $GlobalUser['validatemd'] );
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
                    if ( !localhost($ip) ) SendChangeMail ( $GlobalUser['oname'], $email, $GlobalUser['pemail'], $ack );
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
        <th><a title="<?php echo loca("OPTIONS_USER_EMAIL_TIP");?>"><?php echo loca("OPTIONS_USER_EMAIL");?></a></th> 
        <th><input type="text" name="db_email" maxlength="100" size="20" value="<?php echo $GlobalUser['email'];?>" /></th> 
    </tr> 
    <tr> 
        <th><?=loca("OPTIONS_USER_PASS");?></th> 
        <th><input type="password" name="db_password" size ="20" value="" /></th> 
    </tr> 
    <tr> 
        <th colspan=2><input type="submit" value="<?=loca("OPTIONS_ACTIVATE_EMAIL");?>" /></th> 
    </tr> 
    </form> 
    <form action="index.php?page=options&session=<?php echo $session;?>" method="POST" > 
    <input type=hidden name="validate" value="1"> 
    <tr> 
        <th colspan=2> 
                    <p style="color:#ff0000;padding-top:10px;padding-bottom:5px;"><?=loca("OPTIONS_ACTIVATE_INFO");?></p> 
            <input type="submit" value="<?=loca("OPTIONS_ACTIVATE_SUBMIT");?>" /> 
        </th> 
    </tr> 
   </form> 
 </table> 

<?php
    // ======================================================================================
    // Vacation mode is enabled.

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
    // Regular menu.

    }
    else
    {

        // Process POST request.
        if ( method () === "POST" && !key_exists ( 'urlaub_aus', $_POST) ) {

            if ( $GlobalUser['name_changed'] == 0 && $_POST['db_character'] !== $GlobalUser['oname'] ) {        // Change the name.
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

            else if ( $_POST['newpass1'] !== "" ) {        // Change password

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

            else if ( $_POST['db_email'] !== $GlobalUser['pemail'] && $_POST['db_email'] !== "" ) {        // Change email address
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
                    if ( !localhost($ip) ) SendChangeMail ( $GlobalUser['oname'], $email, $GlobalUser['pemail'], $ack );
                    $GlobalUser['email'] = $email;
                    $OptionsError = loca ("OPTIONS_USER_EMAIL_TIP");
                }
            }

            if ( key_exists('urlaubs_modus', $_POST) && $_POST['urlaubs_modus'] === "on" && $GlobalUser['vacation'] == 0 ) {        // Activate vacation mode
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

            if ( key_exists('db_deaktjava', $_POST) && $_POST['db_deaktjava'] === "on" && $GlobalUser['disable'] == 0 ) {        // Set the account for deletion
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

            // Save skin path + checkbox show/disable skin.
            // TODO : OPTIONS_MSG_SKIN
            ChangeSkinPath ( $GlobalUser['player_id'], $_POST['dpath'] );
            EnableSkin ( $GlobalUser['player_id'], ($_POST['design']==="on"?1:0) );

            $lang = substr ( addslashes($_POST['lang']), 0, 2 );
            // If the admin has forbidden users to choose a language, then force set them to the Universe language.
            if ($GlobalUni['force_lang']) {
                $lang = $GlobalUni['lang'];
            }
            $sortby = min ( max(0, intval($_POST['settings_sort'])), 2);
            $sortorder = min ( max(0, intval($_POST['settings_order'])), 1);
            $deactip = (int) key_exists ( 'noipcheck', $_POST );
            $maxspy = min( max (1, intval($_POST['spio_anz'])), 99);
            $maxfleetmsg = min( max (1, intval($_POST['settings_fleetactions'])), 99);
            $query = "UPDATE ".$db_prefix."users SET deact_ip=$deactip, sortby=$sortby, sortorder=$sortorder, maxspy=$maxspy, maxfleetmsg=$maxfleetmsg, lang='".$lang."' WHERE player_id=".intval($GlobalUser['player_id']);
            dbquery ($query);
            $GlobalUser['sortby'] = $sortby;
            $GlobalUser['sortorder'] = $sortorder;
            $GlobalUser['maxspy'] = $maxspy;
            $GlobalUser['maxfleetmsg'] = $maxfleetmsg;
            $GlobalUser['lang'] = $lang;
            $GlobalUser['deact_ip'] = $deactip;
            $GlobalUser['skin'] = $_POST['dpath'];
            $GlobalUser['useskin'] = ($_POST['design']==="on"?1:0);

            // Flags -- process only with Commander enabled
            if ( $prem['commander'] ) {
                
                $flags = $GlobalUser['flags'];
                $settings_esp = (key_exists('settings_esp', $_POST) && $_POST['settings_esp']==="on");
                $settings_wri = (key_exists('settings_wri', $_POST) && $_POST['settings_wri']==="on");
                $settings_bud = (key_exists('settings_bud', $_POST) && $_POST['settings_bud']==="on");
                $settings_mis = (key_exists('settings_mis', $_POST) && $_POST['settings_mis']==="on");
                $settings_rep = (key_exists('settings_rep', $_POST) && $_POST['settings_rep']==="on");
                $settings_folders = (key_exists('settings_folders', $_POST) && $_POST['settings_folders']==="on");  // 1: don't use folders.
                if ($settings_esp) $flags |= USER_FLAG_SHOW_ESPIONAGE_BUTTON;
                else $flags &= ~USER_FLAG_SHOW_ESPIONAGE_BUTTON;
                if ($settings_wri) $flags |= USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON;
                else $flags &= ~USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON;
                if ($settings_bud) $flags |= USER_FLAG_SHOW_BUDDY_BUTTON;
                else $flags &= ~USER_FLAG_SHOW_BUDDY_BUTTON;
                if ($settings_mis) $flags |= USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON;
                else $flags &= ~USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON;
                if ($settings_rep) $flags |= USER_FLAG_SHOW_VIEW_REPORT_BUTTON;
                else $flags &= ~USER_FLAG_SHOW_VIEW_REPORT_BUTTON;
                if ($settings_folders) $flags |= USER_FLAG_DONT_USE_FOLDERS;
                else $flags &= ~USER_FLAG_DONT_USE_FOLDERS;
                if ($flags != $GlobalUser['flags']) {
                    SetUserFlags ($GlobalUser['player_id'], $flags);
                    $GlobalUser['flags'] = $flags;
                }
            }

            // Flags for the operator
            if ( $GlobalUser['admin'] == 1 ) {

                $flags = $GlobalUser['flags'];
                $hide_go_email = (key_exists('hide_go_email', $_POST) && $_POST['hide_go_email']==="on");
                if ($hide_go_email) $flags |= USER_FLAG_HIDE_GO_EMAIL;
                else $flags &= ~USER_FLAG_HIDE_GO_EMAIL;                
                if ($flags != $GlobalUser['flags']) {
                    SetUserFlags ($GlobalUser['player_id'], $flags);
                    $GlobalUser['flags'] = $flags;
                }
            }

        }
?>

 <form action="index.php?page=options&session=<?php echo $session;?>&mode=change" method="POST" >
     <tr><td class="c" colspan ="2"><?=loca("OPTIONS_USER");?></td></tr>
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

<?php
    // Language selection is only activated if the admin has allowed it in the Universe settings.
    if (!$GlobalUni['force_lang']) {
?>
   <th><?php echo loca("OPTIONS_GENERAL_LANG");?></th>
   <th>
   <select name="lang">
<?php
    foreach ( $Languages as $lang_id=>$lang_name ) {
        echo "    <option value=\"".$lang_id."\" " . IsSelected("lang", $lang_id)." >$lang_name</option>\n";
    }
?>
   </select>
   </th>
  </tr>
<?php
    }
?>

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

  <th><?php echo loca("OPTIONS_GENERAL_SKINPATH");?><br /> <a href="<?=hostname();?>download/" target="_blank"><?php echo loca("OPTIONS_GENERAL_DOWNLOAD");?></a></th>
   <th><input type=text name="dpath" maxlength="80" size="40" value="<?php echo $GlobalUser['skin'];?>" /> <br />
  <?php
            // If the skin path is empty, output a list of available skins on the graphics server.
            if ( $GlobalUser['skin'] === "" ) {
    ?>
  <select name="dpath" size="1" >
   <option selected>  </option>
      <option value="<?=hostname();?>download/use/allesnurgeklaut/">allesnurgeklaut </option>
      <option value="<?=hostname();?>download/use/ally-cpb/">allycpb </option>
      <option value="<?=hostname();?>download/use/asgard/">asgard </option>
      <option value="<?=hostname();?>download/use/aurora/">aurora </option>
      <option value="<?=hostname();?>download/use/bluedream/">bluedream </option>
      <option value="<?=hostname();?>download/use/bluegalaxy/">bluegalaxy </option>
      <option value="<?=hostname();?>download/use/blueplanet/">blueplanet </option>
      <option value="<?=hostname();?>download/use/bluechaos/">bluechaos </option>
      <option value="<?=hostname();?>download/use/bluemx/">blue-mx </option>
      <option value="<?=hostname();?>download/use/brace/">brace </option>
      <option value="<?=hostname();?>download/use/brotstyle/">brotstyle </option>
      <option value="<?=hostname();?>download/use/dd/">dd </option>
      <option value="<?=hostname();?>download/use/eclipse/">eclipse </option>
      <option value="<?=hostname();?>download/use/empire/">empire </option>
      <option value="<?=hostname();?>download/use/EpicBlue/">epicblue </option>
      <option value="<?=hostname();?>download/use/evolution/">evolution </option>
      <option value="<?=hostname();?>download/use/freakyfriday/">freakyfriday </option>
      <option value="<?=hostname();?>download/use/g3cko/">g3cko </option>
      <option value="<?=hostname();?>download/use/gruen/">gruen </option>
      <option value="<?=hostname();?>download/use/infraos/">infraos </option>
      <option value="<?=hostname();?>download/use/lambda/">lambda </option>
      <option value="<?=hostname();?>download/use/lego/">lego </option>
      <option value="<?=hostname();?>download/use/militaryskin/">militaryskin </option>
      <option value="<?=hostname();?>download/use/okno/">okno </option>
      <option value="<?=hostname();?>download/use/ovisio/">ovisio </option>
      <option value="<?=hostname();?>download/use/ovisiofarbig/">ovisiofarbig </option>
      <option value="<?=hostname();?>download/use/Paint/">paint </option>
      <option value="<?=hostname();?>download/use/quadratorstyle/">quadratorstyle </option>
      <option value="<?=hostname();?>download/use/real/">real </option>
      <option value="<?=hostname();?>download/use/redfuturistisch/">redfuturistisch </option>
      <option value="<?=hostname();?>download/use/redvision/">redvision </option>
      <option value="<?=hostname();?>download/use/reloaded/">reloaded </option>
      <option value="<?=hostname();?>download/use/shadowpato/">shadowpato </option>
      <option value="<?=hostname();?>download/use/simpel/">simpel </option>
      <option value="<?=hostname();?>download/use/starwars/">starwars </option>
      <option value="<?=hostname();?>download/use/w4wooden4ce/">w4wooden4ce </option>
      <option value="<?=hostname();?>download/use/xonic/">xonic </option>
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
    if ( $prem['commander'] )    // Additional Commander settings
    {
?>
  </tr>
     <tr>
   <th><?php echo loca("OPTIONS_GALAXY_KEYS");?></th>
   <th><?php echo loca("OPTIONS_GALAXY_SHOWKEYS");?></th>
  </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/e.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_SPY");?></th>

   <th><input type="checkbox" name="settings_esp" <?=IsCheckedFlag(USER_FLAG_SHOW_ESPIONAGE_BUTTON);?>/></th>
   </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/m.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_MSG");?></th>
   <th><input type="checkbox" name="settings_wri" <?=IsCheckedFlag(USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON);?>/></th>
   </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/b.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_BUDDY");?></th>

   <th><input type="checkbox" name="settings_bud" <?=IsCheckedFlag(USER_FLAG_SHOW_BUDDY_BUTTON);?>/></th>
   </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/r.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_ROCKET");?></th>
   <th><input type="checkbox" name="settings_mis" <?=IsCheckedFlag(USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON);?>/></th>
   </tr>
      <tr>
   <th><img src="<?php echo UserSkin();?>img/s.gif" alt="" />   <?php echo loca("OPTIONS_GALAXY_REPORT");?></th>

   <th><input type="checkbox" name="settings_rep" <?=IsCheckedFlag(USER_FLAG_SHOW_VIEW_REPORT_BUTTON);?>/></th>
   </tr>
      <tr>
   <td class="c" colspan="2"><?php echo loca("OPTIONS_MSG");?></td>
   <tr>
   <th><?php echo loca("OPTIONS_MSG_SORT");?></th>
  <th><input type="checkbox" name="settings_folders"  <?=IsCheckedFlag(USER_FLAG_DONT_USE_FOLDERS);?>/></th>
</tr>

<tr>
    <td class="c" colspan="2"><font color='FF8900'><?=loca("OPTIONS_FEED");?></font></td>
</tr>
<tr>
    <th><?=loca("OPTIONS_FEED_ACTIVATE");?><input type=hidden name="feed_submit" value="1"></th>
    <th><input type="checkbox" name="feed_activated"  /></th>
</tr>
<?php
    }
?>

<?php
    if ( $GlobalUser['admin'] == 1 )    // Additional settings visible only to operators
    {
?>
<tr>
    <td class="c" colspan="2"><?=loca("OPTIONS_OPER");?></td>
</tr>
<tr>
    <th><?=loca("OPTIONS_OPER_HIDE_EMAIL");?></th>
    <th><input type="checkbox" name="hide_go_email" <?=IsCheckedFlag(USER_FLAG_HIDE_GO_EMAIL);?>/></th>
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
<?php
EndContent ();
PageFooter ($OptionsMessage, $OptionsError);
ob_end_flush ();
?>