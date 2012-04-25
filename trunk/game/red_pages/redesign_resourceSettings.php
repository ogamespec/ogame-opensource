<?php

// Redesign : Производство сырья

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], (int)$_GET['cp']);
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( &$aktplanet, $aktplanet['lastpeek'], $now );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "technames", $GlobalUser['lang'] );
loca_add ( "fleetorder", $GlobalUser['lang'] );
loca_add ( "overview", $GlobalUser['lang'] );

##############################################################################################################

include "redesign_header.php";
include "redesign_leftmenu.php";
include "redesign_planetlist.php";

$prem = PremiumStatus ($GlobalUser);
if ( $prem['geologist'] )
{
    $geologe_text = "<img border=\"0\" src=\"img/geologe_ikon.gif\" alt=\"Геолог\" onmouseover='return overlib(\"<font color=#ffffff>Геолог</font>\", WIDTH, 80);' onmouseout='return nd();' width=\"20\" height=\"20\">";
    $g_factor = 1.1;
}
else
{
    $geologe_text = "&nbsp;";
    $g_factor = 1.0;
}
if ( $prem['engineer'] )
{
    $engineer_text = "<img border=\"0\" src=\"img/ingenieur_ikon.gif\" alt=\"Инженер\" onmouseover='return overlib(\"<font color=#ffffff>Инженер</font>\", WIDTH, 80);' onmouseout='return nd();' width=\"20\" height=\"20\">";
    $e_factor = 1.1;
}
else
{
    $engineer_text = "&nbsp;";
    $e_factor = 1.0;
}

$speed = $GlobalUni['speed'];
$planet = $aktplanet;

// Производство.
$m_hourly = prod_metal ($planet['b1'], $planet['mprod']) * $planet['factor'] * $speed * $g_factor;
$k_hourly = prod_crys ($planet['b2'], $planet['kprod']) * $planet['factor'] * $speed * $g_factor;
$d_hourly = prod_deut ($planet['b3'], $planet['temp']+40, $planet['dprod']) * $planet['factor'] * $speed * $g_factor;
$s_prod = prod_solar($planet['b4'], $planet['sprod']) * $e_factor;
$f_prod = prod_fusion($planet['b12'], $GlobalUser['r113'], $planet['fprod']) * $e_factor;
$ss_prod = prod_sat($planet['temp']+40) * $planet['f212'] * $planet['ssprod'] * $e_factor;

// Потребление.
$m_cons = cons_metal ($planet['b1']) * $planet['mprod'] * $speed;
$m_cons0 = round ($m_cons * $planet['factor']);
$k_cons = cons_crys ($planet['b2']) * $planet['kprod'] * $speed;
$k_cons0 = round ($k_cons * $planet['factor']);
$d_cons = cons_deut ($planet['b3']) * $planet['dprod'] * $speed;
$d_cons0 = round ($d_cons * $planet['factor']);
$f_cons = - cons_fusion ( $planet['b12'], $planet['fprod'] ) * $speed;

$m_total = $m_hourly + (20*$speed);
$k_total = $k_hourly + (10*$speed);
$d_total = $d_hourly + $f_cons;

//print_r ( $_POST );

?>

            <!-- CONTENT AREA -->
            <div id="contentWrapper">
                                    <div id="eventboxContent" style="display: none"><img height="16" width="16" src="red_images/3f9884806436537bdec305aa26fc60.gif" /></div>
                                
<div id="inhalt">
    <div id="planet" class="shortHeader">
        <h2><span>Производство сырья - <?=$aktplanet['name'];?></span></h2>
    </div>
    <div class="contentRS">
        <div class="headerRS"><a href="index.php?page=resources&session=<?=$session;?>" class="close_details close_ressources"></a></div>
        <div class="mainRS">
            <form method="POST" action="#">
            <input type="hidden" name="session" value="<?=$session;?>">
            <input type="hidden" name="saveSettings" value="1">
            <table cellpadding="0" cellspacing="0" class="list" style="margin-top:0px;">
                <tr>
                    <td colspan="7" id="factor">
                        <div class="secondcol">
                            <div style="width:376px; margin: 0px auto;">
                                <span class="factorkey">Коэффициент производства: <?=floor($aktplanet['factor'] * 100);?>%</span>
                                <span class="factorbutton">
                                    <input class="button188" style="" type="submit" value="Посчитать" /></span>
                                <br class="clearfloat" />
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th colspan="2"></th>
                    <th>Металл</th>
                    <th>Кристалл</th>
                    <th>Дейтерий</th>
                    <th>Энергия</th>
                    <th></th>
                </tr>
                <tr class="alt">
                    <td colspan="2" class="label">Естественное производство</td>
                                        <td class="undermark textRight tipsStandard" title="|<?=(20*$speed);?>">
                        <?=(20*$speed);?>                    </td>
                                        <td class="undermark textRight tipsStandard" title="|<?=(10*$speed);?>">
                        <?=(10*$speed);?>                    </td>
                                        <td class="normalmark textRight tipsStandard" title="|0">
                        0                    </td>
                                        <td class="normalmark textRight tipsStandard" title="|0">
                        0                    </td>
                                        <td></td>
                </tr>
                                                                <tr class="">
                    

                                        <td class="label">
                    <?=loca("NAME_1");?>                    (Уровень                    <?=$aktplanet['b1'];?>)
                    </td>

                                        <td>
                        <img class="tipsStandard" title="|+10% доход от шахты" src="red_images/3f0a246e3517a52bb81346fc6afa35.gif" width="20" height="20">                    </td>
                                        <td class="undermark tipsStandard" title="|17.274">17.274</td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="overmark tipsStandard" title="|5.235/5.235">5.235/5.235</td>
                                        <td>
                                                <select name="last1" size="1" class="undermark">
                                                    <option class="undermark" value="100" selected>100%</option>
                                                    <option class="undermark" value="90" >90%</option>
                                                    <option class="undermark" value="80" >80%</option>
                                                    <option class="undermark" value="70" >70%</option>
                                                    <option class="middlemark" value="60" >60%</option>
                                                    <option class="middlemark" value="50" >50%</option>
                                                    <option class="middlemark" value="40" >40%</option>
                                                    <option class="overmark" value="30" >30%</option>
                                                    <option class="overmark" value="20" >20%</option>
                                                    <option class="overmark" value="10" >10%</option>
                                                    <option class="overmark" value="0" >0%</option>
                                                </select>
                                            </td> 
                </tr>
                                                                <tr class="alt">
                    

                                        <td class="label">
                    <?=loca("NAME_2");?>                    (Уровень                    <?=$aktplanet['b2'];?>)
                    </td>

                                        <td>
                        <img class="tipsStandard" title="|+10% доход от шахты" src="red_images/3f0a246e3517a52bb81346fc6afa35.gif" width="20" height="20">                    </td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="undermark tipsStandard" title="|6.817">6.817</td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="overmark tipsStandard" title="|3.099/3.099">3.099/3.099</td>
                                        <td>
                                                <select name="last2" size="1" class="undermark">
                                                    <option class="undermark" value="100" selected>100%</option>
                                                    <option class="undermark" value="90" >90%</option>
                                                    <option class="undermark" value="80" >80%</option>
                                                    <option class="undermark" value="70" >70%</option>
                                                    <option class="middlemark" value="60" >60%</option>
                                                    <option class="middlemark" value="50" >50%</option>
                                                    <option class="middlemark" value="40" >40%</option>
                                                    <option class="overmark" value="30" >30%</option>
                                                    <option class="overmark" value="20" >20%</option>
                                                    <option class="overmark" value="10" >10%</option>
                                                    <option class="overmark" value="0" >0%</option>
                                                </select>
                                            </td> 
                </tr>
                                                                <tr class="">
                    

                                        <td class="label">
                    <?=loca("NAME_3");?>                    (Уровень                    <?=$aktplanet['b3'];?>)
                    </td>

                                        <td>
                        <img class="tipsStandard" title="|+10% доход от шахты" src="red_images/3f0a246e3517a52bb81346fc6afa35.gif" width="20" height="20">                    </td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="undermark tipsStandard" title="|4.758">4.758</td>
                                        <td class="overmark tipsStandard" title="|6.198/6.198">6.198/6.198</td>
                                        <td>
                                                <select name="last3" size="1" class="undermark">
                                                    <option class="undermark" value="100" selected>100%</option>
                                                    <option class="undermark" value="90" >90%</option>
                                                    <option class="undermark" value="80" >80%</option>
                                                    <option class="undermark" value="70" >70%</option>
                                                    <option class="middlemark" value="60" >60%</option>
                                                    <option class="middlemark" value="50" >50%</option>
                                                    <option class="middlemark" value="40" >40%</option>
                                                    <option class="overmark" value="30" >30%</option>
                                                    <option class="overmark" value="20" >20%</option>
                                                    <option class="overmark" value="10" >10%</option>
                                                    <option class="overmark" value="0" >0%</option>
                                                </select>
                                            </td> 
                </tr>
                                                                <tr class="alt">
                    

                                        <td class="label">
                    <?=loca("NAME_4");?>                    (Уровень                    <?=$aktplanet['b4'];?>)
                    </td>

                                        <td>
                                            </td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="undermark tipsStandard" title="|4.727">4.727</td>
                                        <td>
                                                <select name="last4" size="1" class="undermark">
                                                    <option class="undermark" value="100" selected>100%</option>
                                                    <option class="undermark" value="90" >90%</option>
                                                    <option class="undermark" value="80" >80%</option>
                                                    <option class="undermark" value="70" >70%</option>
                                                    <option class="middlemark" value="60" >60%</option>
                                                    <option class="middlemark" value="50" >50%</option>
                                                    <option class="middlemark" value="40" >40%</option>
                                                    <option class="overmark" value="30" >30%</option>
                                                    <option class="overmark" value="20" >20%</option>
                                                    <option class="overmark" value="10" >10%</option>
                                                    <option class="overmark" value="0" >0%</option>
                                                </select>
                                            </td> 
                </tr>
                                                                <tr class="">
                    

                                        <td class="label">
                    <?=loca("NAME_12");?>                    (Уровень                    <?=$aktplanet['b12'];?>)
                    </td>

                                        <td>
                                            </td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="overmark tipsStandard" title="|213">213</td>
                                        <td class="undermark tipsStandard" title="|1.109">1.109</td>
                                        <td>
                                                <select name="last12" size="1" class="undermark">
                                                    <option class="undermark" value="100" selected>100%</option>
                                                    <option class="undermark" value="90" >90%</option>
                                                    <option class="undermark" value="80" >80%</option>
                                                    <option class="undermark" value="70" >70%</option>
                                                    <option class="middlemark" value="60" >60%</option>
                                                    <option class="middlemark" value="50" >50%</option>
                                                    <option class="middlemark" value="40" >40%</option>
                                                    <option class="overmark" value="30" >30%</option>
                                                    <option class="overmark" value="20" >20%</option>
                                                    <option class="overmark" value="10" >10%</option>
                                                    <option class="overmark" value="0" >0%</option>
                                                </select>
                                            </td> 
                </tr>
                                                                <tr class="alt">
                    

                                        <td class="label">
                    <?=loca("NAME_212");?>                    (Количество:                    <?=$aktplanet['f212'];?>)
                    </td>

                                        <td>
                                            </td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="normalmark tipsStandard" title="|0">0</td>
                                        <td class="undermark tipsStandard" title="|8.700">8.700</td>
                                        <td>
                                                <select name="last212" size="1" class="undermark">
                                                    <option class="undermark" value="100" selected>100%</option>
                                                    <option class="undermark" value="90" >90%</option>
                                                    <option class="undermark" value="80" >80%</option>
                                                    <option class="undermark" value="70" >70%</option>
                                                    <option class="middlemark" value="60" >60%</option>
                                                    <option class="middlemark" value="50" >50%</option>
                                                    <option class="middlemark" value="40" >40%</option>
                                                    <option class="overmark" value="30" >30%</option>
                                                    <option class="overmark" value="20" >20%</option>
                                                    <option class="overmark" value="10" >10%</option>
                                                    <option class="overmark" value="0" >0%</option>
                                                </select>
                                            </td> 
                </tr>
                                                <tr class="alt">
                    <td colspan="2" class="label">Вместимость хранилищ</td>
                                                            <td class="normalmark left2  tipsStandard" title="|865.000">865.000</td>
                                                            <td class="normalmark left2  tipsStandard" title="|470.000">470.000</td>
                                                            <td class="normalmark left2  tipsStandard" title="|255.000">255.000</td>
                                        <td>-</td>
                    <td></td>
                </tr>
                <tr class="summary">


                                        <td colspan="2" class="label"><em>Выработка в час:</em></td>
                                        <td class="undermark tipsStandard" title="|17.304">17.304</td>
                                        <td class="undermark tipsStandard" title="|6.832">6.832</td>
                                        <td class="undermark tipsStandard" title="|4.545">4.545</td>
                                        <td class="undermark tipsStandard" title="|4">4</td>
                                        <td></td>
                </tr>
                <tr class="alt">
                    

                                        <td colspan="2" class="label"><em>Выработка в сутки:</em></td>

                                        <td class="undermark tipsStandard" title="|415.296">415.296</td>
                                        <td class="undermark tipsStandard" title="|163.968">163.968</td>
                                        <td class="undermark tipsStandard" title="|109.080">109.080</td>
                                        <td class="undermark tipsStandard" title="|4">4</td>
                                        <td></td>
                </tr>
                <tr>

                    
                                        <td colspan="2" class="label"><em>Выработка в неделю:</em></td>
                    
                                        <td class="undermark tipsStandard" title="|2.907.072">2.907М</td>
                                        <td class="undermark tipsStandard" title="|1.147.776">1.147М</td>
                                        <td class="undermark tipsStandard" title="|763.560">763.560</td>
                                        <td class="undermark tipsStandard" title="|4">4</td>
                                        <td></td>
                </tr>
            </table>
        </form>

        </div>
        <div class="footerRS"></div>
    </div>	<br class="clearfloat" />
</div>                            </div>
            <!-- END CONTENT AREA -->

<?php

include "redesign_javascript.php";
include "redesign_footer.php";

?>