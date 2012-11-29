<?php

// Заказ офицеров.

$MicropaymentMessage = "";
$MicropaymentError = "";

loca_add ( "common" );
loca_add ( "menu", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( &$aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

function OfficerLeft ( $qcmd )
{
    global $GlobalUser;
    $now = time ();
    $end = GetOfficerLeft ( $GlobalUser['player_id'], $qcmd );
    if ( $end <= $now ) return "<font color=red>Неактивный</font>";
    else
    {
        $d = ceil ( ($end - $now) / (60*60*24) );
        return "<strong><font color=lime>Активен</font> ещё $d д.</strong>";
    }
}

// Обработать GET-запрос.
if ( key_exists ( 'buynow', $_GET ) )
{
    $qcmd = array ( 1 => "CommanderOff", 2 => "AdmiralOff", 3 => "EngineerOff", 4 => "GeologeOff", 5 => "TechnocrateOff" );
    $type = intval ( $_GET['type'] );
    $days = intval ( $_GET['days'] );
    if ( $days == 7 || $days == 90 )
    {
        $dm = $GlobalUser['dm'] + $GlobalUser['dmfree'];
        if ( $days == 90) $required = 100000;
        else if ( $days == 7) $required = 10000;
        if ( $dm < $required )
        {
            $MicropaymentError = "Недостаточно тёмной материи!<br>";
        }
        else
        {
            if ( $type >= 1 && $type <= 5 )
            {
                // Списать ТМ.
                if ( $GlobalUser['dm'] >= $required ) $GlobalUser['dm'] -= $required;
                else {
                    $GlobalUser['dmfree'] -= $required - $GlobalUser['dm'];
                    $GlobalUser['dm'] = 0;
                }

                $query = "UPDATE ".$db_prefix."users SET dm = '".$GlobalUser['dm']."', dmfree = '".$GlobalUser['dmfree']."' WHERE player_id = " . $GlobalUser['player_id'];
                dbquery ( $query );

                RecruitOfficer ( $GlobalUser['player_id'], $qcmd[$type], $days * 24 * 60 * 60 );
                
                $MicropaymentMessage = "Продление прошло успешно!<br>";
            }
        }
    }
}

PageHeader ("micropayment");

echo "<!-- CONTENT AREA -->\n";
echo "<div id='content'>\n";
echo "<center>\n";

// ************************************************************************************
?>

<center>

          <div id="header" style="background-image:url('img/kasino_600x120.jpg'); width:600px;height:120px;">
            <div id="headtext1" style="position:relative; top:25px; left:-160px;font-size:18px;font-weight:bold; color:f3d2b1;">Мудрому владыке ...</div>
            <div id="headtext2" style="position:relative;float:right;top:23px;left:-240px;font-size:13px;font-weight:bold;color:#c2f1fd;">... нужны умные <b><font size=4>советники.</font></b></div>
         </div>

                <table width=600>

                <tr>
                      <td colspan="3" class="c">Тёмная материя</td>

                </tr>
                <tr>
                    <td class=l><img border='0' src="img/DMaterie.jpg" align='top' width='120' height='120'>
                    </td>
                    <td class=l><strong>Тёмная материя</strong><br>
                            Тёмная материя - это такая субстанция, которая может храниться только в течение нескольких стандартных лет с большими затратами. Из неё можно добывать невероятные массы энергии. Способ её добычи очень сложен и опасен, поэтому она очень высоко ценится.                         <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/dm_klein_1.jpg" width="32" height="32" style="vertical-align:middle;"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;">При помощи этой субстанции можно нанять офицеров и командиров.</strong></td></tr></table>

                            </div></td>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                                                <a id='darkmatter2' href='index.php?page=payment&session=<?=$session;?>' style='cursor:pointer; text-align:center;width:100px;height:60px;'><br>
                            <b><div id='darkmatter2'>Достать тёмную материю</a></b>
                        </DIV></td>
                    </tr>


                    <tr>

                      <td colspan="3" class="c">Офицеры</td>
                    </tr>
                    <tr>

                                                <td class=l rowspan="2"><img border='0' src="img/commander_stern_gross.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b>Командир ОГейма</b>(<b>
                        <?=OfficerLeft("CommanderOff");?></b>)<br>

                            Ранг командира неоднократно оправдал себя в современном ведении боя. Благодаря упрощённой структуре Ваши приказы могут исполняться быстрее, что позволит Вам сохранять контроль над всей Вашей империей! Вы сможете развивать стратегии, позволяющей всегда быть на шаг впереди противника.                         <div style="margin:4px 4px;">
                            <table>
                                <tr>
                                <td><img src="img/commander_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="Командир ОГейма">
                                </td>
                                <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;">Очередь для строительства, обзор империи, усовершенствованный обзор галактики, фильтр сообщений, никакой рекламы</strong>
                                </td>

                                </tr>
                            </table>
                            </div>
                        </td>
                                                    <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                                <a href='index.php?page=micropayment&buynow=1&type=1&days=90&session=<?=$session;?>' >
                                <b>3 месяца/месяцев за                              <br><font color=lime>всего 100.000</font>
                                <br>Тёмная материя</b></a>

                            </td>
                            </tr>
                        <tr>
                            <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                                <a href='index.php?page=micropayment&buynow=1&type=1&days=7&session=<?=$session;?>' >
                                <b>1 неделя за                              <br><font color=lime>10.000</font>
                                <br>Тёмная материя</b></a>

                            </td>
                        </tr>
                        



                    <tr>
                      <td colspan="3" class="c" style='height:4px;'></td>
                    </tr>



                    <tr>
                                                <td class=l rowspan="2"><img border='0' src="img/ogame_admiral.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b>Адмирал</b>(<b>
                        <?=OfficerLeft("AdmiralOff");?>)<br>
                            Адмирал - это испытанный войной ветеран и гениальный стратег. Даже в самых горячих боях он не теряет обзора и поддерживает контакт с подчинёнными ему адмиралами. Мудрый правитель может полностью положиться на него в бою и тем самым использовать для боя больше кораблей.<br>
                            <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/admiral_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="Адмирал"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;">&nbsp;Макс. кол-во флотов +2</strong></td></tr></table>

                            </div>
                                                <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=2&days=90&session=<?=$session;?>' >
                            <b>3 месяца/месяцев за                          <br><font color=lime>всего 100.000</font>
                            <br>Тёмная материя</b></a>
                        </td>
                    </tr>

                    <tr>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=2&days=7&session=<?=$session;?>' >
                            <b>1 неделя за                          <br><font color=lime>10.000</font>
                            <br>Тёмная материя</b></a>
                        </td>
                    </tr>

                    
                    <tr>
                      <td colspan="3" class="c" style='height:4px;'></td>
                    </tr>

                    <tr>
                                                <td class=l rowspan="2"><img border='0' src="img/ogame_ingenieur.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b>Инженер</b>(<b>
                        <?=OfficerLeft("EngineerOff");?></b>)<br>

                            Инженер - это специалист по управлению энергией. В мирное время он повышает уровень энергетических сетей на колониях. В случае нападения он снабжает энергетические системы планетарных защит и предотвращает перегрузки, что ведёт к значительно меньшей степени потерь в бою.<br>
                            <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/ingenieur_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="Инженер"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;">Сокращает вдвое потери в обороне+10% больше энергии</strong></td></tr></table>
                            </div>
                                                <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=3&days=90&session=<?=$session;?>' >
                            <b>3 месяца/месяцев за                          <br><font color=lime>всего 100.000</font>

                            <br>Тёмная материя</b></a>
                        </td>
                    </tr>
                    <tr>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=3&days=7&session=<?=$session;?>' >
                            <b>1 неделя за                          <br><font color=lime>10.000</font>

                            <br>Тёмная материя</b></a>
                        </td>
                    </tr>
                        
                    <tr>
                      <td colspan="3" class="c" style='height:4px;'></td>
                    </tr>

                    <tr>
                                                <td class=l rowspan="2"><img border='0' src="img/ogame_geologe.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b>Геолог</b>(<b>
                        <?=OfficerLeft("GeologeOff");?></b>)<br>
                            Геолог - это признанный эксперт в астроминералогии и -кристаллографии. Со своей командой металлургов и химиков он поддерживает межпланетные правительства при разработке новых источников ресурсов и оптимизации их очистки.<br>
                            <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/geologe_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="Геолог"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;">+10% доход от шахты</strong></td></tr></table>

                            </div>
                                                <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=4&days=90&session=<?=$session;?>' >
                            <b>3 месяца/месяцев за                          <br><font color=lime>всего 100.000</font>
                            <br>Тёмная материя</b></a>
                        </td>
                    </tr>

                    <tr>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=4&days=7&session=<?=$session;?>' >
                            <b>1 неделя за                          <br><font color=lime>10.000</font>
                            <br>Тёмная материя</b></a>
                        </td>
                    </tr>

                        

                    <tr>
                      <td colspan="3" class="c" style='height:4px;'></td>
                    </tr>

                    <tr>
                                                <td class=l rowspan="2"><img border='0' src="img/ogame_technokrat.jpg" align='top' width='120' height='120'></td>

                        <td class=l rowspan="2"><b>Технократ</b>(<b>
                        <?=OfficerLeft("TechnocrateOff");?></b>)<br>

                            Гильдия технократов - это гениальные учёные, и их всегда можно найти там, где заканчивается грань технически возможного. Их код никогда не сможет разгадать ни один нормальный человек, и одним своим присутствием они вдохновляют учёных империи.<br>
                            <div style="margin:4px 4px;">
                            <table><tr><td><img src="img/technokrat_ikon.gif" width="32" height="32" style="vertical-align:middle;" alt="Технократ"></td>
                            <td style='background-color:transparent;'><strong style="color:skyblue; vertical-align:middle;">+2 уровень шпионажа, 25% меньше времени на исследования</strong></td></tr></table>
                            </div>
                                            <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=5&days=90&session=<?=$session;?>' >
                            <b>3 месяца/месяцев за                          <br><font color=lime>всего 100.000</font>

                            <br>Тёмная материя</b></a>
                        </td>
                    </tr>

                    <tr>
                        <td class=l style="width:90px;text-align:center; vertical-align:middle;">
                            <a href='index.php?page=micropayment&buynow=1&type=5&days=7&session=<?=$session;?>' >
                            <b>1 неделя за                          <br><font color=lime>10.000</font>

                            <br>Тёмная материя</b></a>
                        </td>
                    </tr>
                        

          </table>
          <br>

<?php

// ************************************************************************************

echo "<br><br><br><br>\n";
echo "</center>\n";
echo "</div>\n";
echo "<!-- END CONTENT AREA -->\n";

PageFooter ($MicropaymentMessage, $MicropaymentError);
ob_end_flush ();
?>