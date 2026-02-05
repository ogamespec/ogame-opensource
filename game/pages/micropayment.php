<?php

/** @var array $GlobalUser */
/** @var string $session */

// Ordering officers.

// In the original, the Commander appeared first, then the other Officers. But for the sake of simplicity, we consider the Commander to be an officer as well.

// Also in our project there is no billing system (communism). Instead of payment, coupons are used, which are distributed by the administrator.

// Cost of Officers.
$price = array ( USER_OFFICER_COMMANDER => 10000, USER_OFFICER_ADMIRAL => 10000, USER_OFFICER_ENGINEER => 10000, USER_OFFICER_GEOLOGE => 10000, USER_OFFICER_TECHNOCRATE => 10000 );

function OfficerLeft ( int $type ) : string
{
    global $GlobalUser;
    $now = time ();
    $end = GetOfficerLeft ( $GlobalUser, $type );
    if ( $end <= $now ) return loca("PREM_INACTIVE");
    else
    {
        $d = ceil ( ($end - $now) / (60*60*24) );
        return va(loca("PREM_ACTIVE"), $d);
    }
}

// Process GET request.
if ( key_exists ( 'buynow', $_GET ) )
{
    $type = intval ( $_GET['type'] );
    $days = intval ( $_GET['days'] );
    if ( $days == 7 || $days == 90 )
    {
        $dm = $GlobalUser['dm'] + $GlobalUser['dmfree'];
        if ( $days == 7) $required = $price[$type];
        else if ( $days == 90) $required = $price[$type] * 10;
        if ( $dm < $required )
        {
            $PageError = loca ("PREM_NOTENOUGH") . "<br>";
        }
        else
        {
            if ( $type >= USER_OFFICER_COMMANDER && $type <= USER_OFFICER_TECHNOCRATE )
            {
                // Списать ТМ.
                if ( $GlobalUser['dm'] >= $required ) $GlobalUser['dm'] -= $required;
                else {
                    $GlobalUser['dmfree'] -= $required - $GlobalUser['dm'];
                    $GlobalUser['dm'] = 0;
                }

                $query = "UPDATE ".$db_prefix."users SET dm = '".$GlobalUser['dm']."', dmfree = '".$GlobalUser['dmfree']."' WHERE player_id = " . $GlobalUser['player_id'];
                dbquery ( $query );

                RecruitOfficer ( $GlobalUser['player_id'], $type, $days * 24 * 60 * 60 );
                
                $PageMessage = loca ("PREM_OK") . "<br>";
            }
        }
    }
}

?>

<center>

          <div id="header" style="background-image:url('img/kasino_600x120.jpg'); width:600px;height:120px;">
            <div id="headtext1" style="position:relative; top:25px; left:-160px;font-size:18px;font-weight:bold; color:f3d2b1;"><?php echo loca("PREM_HEAD1");?></div>
            <div id="headtext2" style="position:relative;float:right;top:23px;left:-240px;font-size:13px;font-weight:bold;color:#c2f1fd;"><?php echo loca("PREM_HEAD2");?></div>
         </div>

                <table width=600>

                <tr>
                      <td colspan="3" class="c"><?php echo loca("NAME_".GID_RC_DM);?></td>

                </tr>
                <tr>
                    <td class=l><img border='0' src="img/DMaterie.jpg" align='top' width='120' height='120'>
                    </td>
                    <td class=l><strong><?php echo loca("NAME_".GID_RC_DM);?></strong><br>
                            <?php echo loca("PREM_DM_INFO");?>                         <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/dm_klein_1.jpg" width="32" height="32" style="vertical-align:middle;"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;"><?php echo loca("PREM_DM_NOTE");?></strong></td></tr></table>

                            </div></td>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                                                <a id='darkmatter2' href='index.php?page=payment&session=<?php echo $session;?>' style='cursor:pointer; text-align:center;width:100px;height:60px;'><br>
                            <b><div id='darkmatter2'><?php echo loca("PREM_DM_GET");?></a></b>
                        </DIV></td>
                    </tr>


                    <tr>

                      <td colspan="3" class="c"><?php echo loca("PREM_OFICEERS");?></td>
                    </tr>
                    <tr>

                                                <td class=l rowspan="2"><img border='0' src="img/commander_stern_gross.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b><?php echo loca("PREM_COMMANDER");?></b>(<b>
                        <?php echo OfficerLeft(USER_OFFICER_COMMANDER);?></b>)<br>
                            <?php echo loca("PREM_COMMANDER_INFO");?>                         <div style="margin:4px 4px;">
                            <table>
                                <tr>
                                <td><img src="img/commander_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="<?php echo loca("PREM_COMMANDER");?>">
                                </td>
                                <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;"><?php echo loca("PREM_COMMANDER_NOTE");?></strong>
                                </td>

                                </tr>
                            </table>
                            </div>
                        </td>
                                                    <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                                <a href='index.php?page=micropayment&buynow=1&type=1&days=90&session=<?php echo $session;?>' >
                                <b><?php echo loca("PREM_3MONTH");?>                              <br><font color=lime><?php echo loca("PREM_TOTAL");?> <?php echo nicenum($price[1]*10);?></font>
                                <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>

                            </td>
                            </tr>
                        <tr>
                            <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                                <a href='index.php?page=micropayment&buynow=1&type=1&days=7&session=<?php echo $session;?>' >
                                <b><?php echo loca("PREM_WEEK");?>                              <br><font color=lime><?php echo nicenum($price[1]);?></font>
                                <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>

                            </td>
                        </tr>
                        



                    <tr>
                      <td colspan="3" class="c" style='height:4px;'></td>
                    </tr>



                    <tr>
                                                <td class=l rowspan="2"><img border='0' src="img/ogame_admiral.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b><?php echo loca("PREM_ADMIRAL");?></b>(<b>
                        <?php echo OfficerLeft(USER_OFFICER_ADMIRAL);?>)<br>
                            <?php echo loca("PREM_ADMIRAL_INFO");?><br>
                            <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/admiral_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="<?php echo loca("PREM_ADMIRAL");?>"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;"><?php echo loca("PREM_ADMIRAL_NOTE");?></strong></td></tr></table>

                            </div>
                                                <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=2&days=90&session=<?php echo $session;?>' >
                            <b><?php echo loca("PREM_3MONTH");?>                          <br><font color=lime><?php echo loca("PREM_TOTAL");?> <?php echo nicenum($price[2]*10);?></font>
                            <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>
                        </td>
                    </tr>

                    <tr>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=2&days=7&session=<?php echo $session;?>' >
                            <b><?php echo loca("PREM_WEEK");?>                          <br><font color=lime><?php echo nicenum($price[2]);?></font>
                            <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>
                        </td>
                    </tr>

                    
                    <tr>
                      <td colspan="3" class="c" style='height:4px;'></td>
                    </tr>

                    <tr>
                                                <td class=l rowspan="2"><img border='0' src="img/ogame_ingenieur.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b><?php echo loca("PREM_ENGINEER");?></b>(<b>
                        <?php echo OfficerLeft(USER_OFFICER_ENGINEER);?></b>)<br>
                            <?php echo loca("PREM_ENGINEER_INFO");?><br>
                            <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/ingenieur_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="<?php echo loca("PREM_ENGINEER");?>"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;"><?php echo loca("PREM_ENGINEER_NOTE");?></strong></td></tr></table>
                            </div>
                                                <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=3&days=90&session=<?php echo $session;?>' >
                            <b><?php echo loca("PREM_3MONTH");?>                          <br><font color=lime><?php echo loca("PREM_TOTAL");?> <?php echo nicenum($price[3]*10);?></font>

                            <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>
                        </td>
                    </tr>
                    <tr>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=3&days=7&session=<?php echo $session;?>' >
                            <b><?php echo loca("PREM_WEEK");?>                          <br><font color=lime><?php echo nicenum($price[3]);?></font>

                            <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>
                        </td>
                    </tr>
                        
                    <tr>
                      <td colspan="3" class="c" style='height:4px;'></td>
                    </tr>

                    <tr>
                                                <td class=l rowspan="2"><img border='0' src="img/ogame_geologe.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b><?php echo loca("PREM_GEOLOGE");?></b>(<b>
                        <?php echo OfficerLeft(USER_OFFICER_GEOLOGE);?></b>)<br>
                            <?php echo loca("PREM_GEOLOGE_INFO");?><br>
                            <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/geologe_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="<?php echo loca("PREM_GEOLOGE");?>"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;"><?php echo loca("PREM_GEOLOGE_NOTE");?></strong></td></tr></table>

                            </div>
                                                <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=4&days=90&session=<?php echo $session;?>' >
                            <b><?php echo loca("PREM_3MONTH");?>                          <br><font color=lime><?php echo loca("PREM_TOTAL");?> <?php echo nicenum($price[4]*10);?></font>
                            <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>
                        </td>
                    </tr>

                    <tr>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=4&days=7&session=<?php echo $session;?>' >
                            <b><?php echo loca("PREM_WEEK");?>                          <br><font color=lime><?php echo nicenum($price[4]);?></font>
                            <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>
                        </td>
                    </tr>

                        

                    <tr>
                      <td colspan="3" class="c" style='height:4px;'></td>
                    </tr>

                    <tr>
                                                <td class=l rowspan="2"><img border='0' src="img/ogame_technokrat.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b><?php echo loca("PREM_TECHNOCRATE");?></b>(<b>
                        <?php echo OfficerLeft(USER_OFFICER_TECHNOCRATE);?></b>)<br>
                            <?php echo loca("PREM_TECHNOCRATE_INFO");?><br>
                            <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/technokrat_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="<?php echo loca("PREM_TECHNOCRATE");?>"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;"><?php echo loca("PREM_TECHNOCRATE_NOTE");?></strong></td></tr></table>
                            </div>
                                            <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=5&days=90&session=<?php echo $session;?>' >
                            <b><?php echo loca("PREM_3MONTH");?>                          <br><font color=lime><?php echo loca("PREM_TOTAL");?> <?php echo nicenum($price[5]*10);?></font>

                            <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>
                        </td>
                    </tr>

                    <tr>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=5&days=7&session=<?php echo $session;?>' >
                            <b><?php echo loca("PREM_WEEK");?>                          <br><font color=lime><?php echo nicenum($price[5]);?></font>

                            <br><?php echo loca("NAME_".GID_RC_DM);?></b></a>
                        </td>
                    </tr>
                        

          </table>
          <br>

<?php

echo "<br><br><br><br>\n";
?>