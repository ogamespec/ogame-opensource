<?php

$GalaxyMessage = "";
$GalaxyError = "";

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "galaxy", $GlobalUser['lang'] );
loca_add ( "espionage", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

$defmap = array ( 401, 402, 403, 404, 405, 406, 407, 408 );

function empty_row ($p)
{
    echo "<tr><th width=\"30\"><a href=\"#\" >".$p."</a></th><th width=\"30\"></th><th width=\"130\" style='white-space: nowrap;'></th><th width=\"30\" style='white-space: nowrap;'></th><th width=\"30\"></th><th width=\"150\"></th><th width=\"80\"></th><th width=\"125\" style='white-space: nowrap;'></th></tr>\n\n";
}

// Ракетная атака.
if ( method () === "POST" && isset($_POST['aktion']) )
{
    $amount = abs(intval($_POST['anz']));        // Количество ракет
    $type = abs(intval($_POST['pziel']));        // Основная цель (0-все)
    $origin = $aktplanet;
    $target = GetPlanet (intval($_GET['pdd']));
    $target_user = LoadUser ($target['owner_id']);
    $dist = abs ($origin['s'] - $target['s']);
    $ipm_radius = max (0, 5 * $GlobalUser['r117'] - 1);

    if ( $target == NULL) $GalaxyError = loca("GALAXY_RAK_NO_TARGET");

    if ( !in_array ($type, $defmap ) ) $type = 0;

    if ( $GalaxyError === "" )    // Проверить допустимые параметры
    {
        if ($amount == 0) $GalaxyError = loca("GALAXY_RAK_NO_ROCKETS");
        if ($amount > $aktplanet["d503"]) $GalaxyError = loca("GALAXY_RAK_NOT_ENOUGH");
        if ($dist > $ipm_radius) $GalaxyError = loca("GALAXY_RAK_WEAK_DRIVE");
    }

    if ( $GalaxyError === "" )        // Проверить режимы игроков
    {
        if ($GlobalUser['vacation']) $GalaxyError = loca("GALAXY_RAK_VACATION_SELF");
        else if ($target_user['vacation']) $GalaxyError = loca("GALAXY_RAK_VACATION_OTHER");
        else if ($target['owner_id'] == $GlobalUser['player_id']) $GalaxyError = loca("GALAXY_RAK_SELF");
        else if ( IsPlayerNewbie($target_user['player_id']) || IsPlayerStrong($target_user['player_id']) ) $GalaxyError = loca("GALAXY_RAK_NOOB");
    }

    if ( $GalaxyError === "" )
    {
        LaunchRockets ( $origin, $target, 30 + 60 * $dist, $amount, $type );
        $aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );    // получить свежие данные планеты после запуска МПР.
        $GalaxyMessage = va ( loca("GALAXY_RAK_LAUNCHED"), $amount );
    }
}

// Выбрать солнечную систему.
if ( key_exists ('session', $_POST)) $coord_g = intval($_POST['galaxy']);
else if ( key_exists ('galaxy', $_GET)) $coord_g = intval($_GET['galaxy']);
else if ( key_exists ('p1', $_GET)) $coord_g = intval($_GET['p1']);
else $coord_g = $aktplanet['g'];
if ( key_exists ('session', $_POST)) $coord_s = intval($_POST['system']);
else if ( key_exists ('system', $_GET)) $coord_s = intval($_GET['system']);
else if ( key_exists ('p2', $_GET)) $coord_s = intval($_GET['p2']);
else $coord_s = $aktplanet['s'];
if ( key_exists ('session', $_POST)) $coord_p = 0;
else if ( key_exists ('position', $_GET)) $coord_p = intval($_GET['position']);
else if ( key_exists ('p3', $_GET)) $coord_p = intval($_GET['p3']);
else $coord_p = $aktplanet['p'];

if ($coord_g < 1 ) $coord_g = 1;
if ($coord_g > $GlobalUni['galaxies'] ) $coord_g = $GlobalUni['galaxies'];

if ($coord_s < 1 ) $coord_s = 1;
if ($coord_s > $GlobalUni['systems'] ) $coord_s = $GlobalUni['systems'];

if ( isset($_POST['systemLeft']) )
{
    $coord_s--;
    if ( $coord_s < 1 ) $coord_s = 1;
}
else if ( isset($_POST['systemRight']) )
{
    $coord_s++;
    if ( $coord_s > $GlobalUni['systems'] ) $coord_s = $GlobalUni['systems'];
}
else if ( isset($_POST['galaxyLeft']) )
{
    $coord_g--;
    if ( $coord_g < 1 ) $coord_g = 1;
}
else if ( isset($_POST['galaxyRight']) )
{
    $coord_g++;
    if ( $coord_g > $GlobalUni['galaxies'] ) $coord_g = $GlobalUni['galaxies'];
}

$not_enough_deut = ( $aktplanet['g'] != $coord_g || $aktplanet['s'] != $coord_s) && $aktplanet['d'] < 10;

// Списать 10 дейтерия за просмотр не домашней системы (только для обычных пользователей)
if ( !$not_enough_deut && $GlobalUser['admin'] == 0 )
{
    if ( $aktplanet['g'] != $coord_g || $aktplanet['s'] != $coord_s )
    {
        AdjustResources (0, 0, 10, $aktplanet['planet_id'], '-');
        $aktplanet = GetPlanet ( $aktplanet['planet_id'] );
    }
}

$result = EnumOwnFleetQueue ( $GlobalUser['player_id'] );
$nowfleet = dbrows ($result);
$maxfleet = $GlobalUser['r108'] + 1;

$prem = PremiumStatus ($GlobalUser);
if ( $prem['admiral'] ) $maxfleet += 2;

PageHeader ("galaxy", true);

BeginContent ();

/***** Скрипты. *****/

?>

  <script  language="JavaScript">
  function galaxy_submit(value) {
      document.getElementById('auto').name = value;
      document.getElementById('galaxy_form').submit();
  }

  function fenster(target_url,win_name) {
  var new_win = window.open(target_url,win_name,'scrollbars=yes,menubar=no,top=0,left=0,toolbar=no,width=550,height=280,resizable=yes');
  new_win.focus();
  }


  var IE = document.all?true:false;

  function mouseX(e){
    if (IE) { // grab the x-y pos.s if browser is IE
        return event.clientX + document.body.scrollLeft;
    } else {
        return e.pageX
    }
  }
  function mouseY(e) {
    if (IE) { // grab the x-y pos.s if browser is IE
        return event.clientY + document.body.scrollTop;
    }else {
        return e.pageY;
    }
  }

  </script>
  <script language="JavaScript" src="js/tw-sack.js"></script>
  <script type="text/javascript">
  var ajax = new sack();
  var strInfo = "";

  function whenLoading(){
      //var e = document.getElementById('fleetstatus');
      //e.innerHTML = "<?php echo loca("GALAXY_AJAX_LOADING");?>";
  }

  function whenLoaded(){
      //    var e = document.getElementById('fleetstatus');
      // e.innerHTML = "<?php echo loca("GALAXY_AJAX_LOADED");?>";
  }

  function whenInteractive(){
      //var e = document.getElementById('fleetstatus');
      // e.innerHTML = "<?php echo loca("GALAXY_AJAX_INTERACT");?>";
  }

  /*
  We can overwrite functions of the sack object easily. :-)
  This function will replace the sack internal function runResponse(),
  which normally evaluates the xml return value via eval(this.response).
  */
  function whenResponse(){

      /*
      *
      *  600   OK
      *  601   no planet exists there
      *  602   no moon exists there
      *  603   player is in noob protection
      *  604   player is too strong
      *  605   player is in u-mode
      *  610   not enough espionage probes, sending x (parameter is the second return value)
      *  611   no espionage probes, nothing send
      *  612   no fleet slots free, nothing send
      *  613   not enough deuterium to send a probe
      *
      */
      // the first three digit long return value
      retVals = this.response.split(" ");
      // and the other content of the response
      // but since we only got it if we can send some but not all probes
      // theres no need to complicate things with better parsing

      // each case gets a different table entry, no language file used :P
      switch(parseInt(retVals[0])) {
          case 600:
          addToTable("done", "success");
                    changeSlots(retVals[1]);
          setShips("probes", retVals[2]);
          setShips("recyclers", retVals[3]);
          setShips("missiles", retVals[4]);
                    break;
          case 601:
          addToTable("<?php echo loca("GALAXY_ERROR_601");?>", "error");
          break;
          case 602:
          addToTable("<?php echo loca("GALAXY_ERROR_602");?>", "error");
          break;
          case 603:
          addToTable("<?php echo loca("GALAXY_ERROR_603");?>", "error");
          break;
          case 604:
          addToTable("<?php echo loca("GALAXY_ERROR_604");?>", "error");
          break;
          case 605:
          addToTable("<?php echo loca("GALAXY_ERROR_605");?>", "vacation");
          break;
          case 610:
          addToTable("<?php echo va(loca("GALAXY_ERROR_610"), "\"+retVals[1]+\"");?>", "notice");
          break;
          case 611:
          addToTable("<?php echo loca("GALAXY_ERROR_611");?>", "error");
          break;
          case 612:
          addToTable("<?php echo loca("GALAXY_ERROR_612");?>", "error");
          break;
          case 613:
          addToTable("<?php echo loca("GALAXY_ERROR_613");?>", "error");
          break;
          case 614:
          addToTable("<?php echo loca("GALAXY_ERROR_614");?>", "error");
          break;
          case 615:
          addToTable("<?php echo loca("GALAXY_ERROR_615");?>", "error");
          break;
          case 616:
          addToTable("<?php echo loca("GALAXY_ERROR_616");?>", "error");
          break;
      }
  }

  function doit(order, galaxy, system, planet, planettype, shipcount){
      strInfo = "<?=loca("GALAXY_DOIT_SEND");?>"+shipcount+"<?=loca("GALAXY_DOIT_SHIPS");?>"+(shipcount>1?"<?=loca("GALAXY_DOIT_MANY");?>":"<?=loca("GALAXY_DOIT_ONE");?>")+"<?=loca("GALAXY_DOIT_TO");?>"+galaxy+":"+system+":"+planet+"<?=loca("GALAXY_DOIT_END");?>";
      ajax.requestFile = "index.php?ajax=1&page=flottenversand&session=<?=$session;?>";

      // no longer needed, since we don't want to write the cryptic
      // response somewhere into the output html
      //ajax.element = 'fleetstatus';
      //ajax.onLoading = whenLoading;
      //ajax.onLoaded = whenLoaded;
      //ajax.onInteractive = whenInteractive;

      // added, overwrite the function runResponse with our own and
      // turn on its execute flag
      ajax.runResponse = whenResponse;
      ajax.execute = true;

      ajax.setVar("session", "<?=$session;?>");
      ajax.setVar("order", order);
      ajax.setVar("galaxy", galaxy);
      ajax.setVar("system", system);
      ajax.setVar("planet", planet);
      ajax.setVar("planettype", planettype);
      ajax.setVar("shipcount", shipcount);
      ajax.setVar("speed", 10);
      ajax.setVar("reply", "short");
      ajax.runAJAX();
  }

  /*
  * This function will manage the table we use to output up to three lines of
  * actions the user did. If there is no action, the tr with id 'fleetstatusrow'
  * will be hidden (display: none;) - if we want to output a line, its display
  * value is cleaned and therefore its visible. If there are more than 2 lines
  * we want to remove the first row to restrict the history to not more than
  * 3 entries. After using the object function of the table we fill the newly
  * created row with text. Let the browser do the parsing work. :D
  */
  function addToTable(strDataResult, strClass) {
      var e = document.getElementById('fleetstatusrow');
      var e2 = document.getElementById('fleetstatustable');
      // make the table row visible
      e.style.display = '';
      if(e2.rows.length > <?=($GlobalUser['maxfleetmsg'] - 1);?>) {
          e2.deleteRow(<?=($GlobalUser['maxfleetmsg'] - 1);?>);
      }
      var row = e2.insertRow('test');
      var td1 = document.createElement("td");
      var td1text = document.createTextNode(strInfo);
      td1.appendChild(td1text);
      var td2 = document.createElement("td");
      var span = document.createElement("span");
      var spantext = document.createTextNode(strDataResult);
      var spanclass = document.createAttribute("class");
      spanclass.nodeValue = strClass;
      span.setAttributeNode(spanclass);
      span.appendChild(spantext);
      td2.appendChild(span);
      row.appendChild(td1);
      row.appendChild(td2);

  }

  function changeSlots(slotsInUse) {
      var e = document.getElementById('slots');
      e.innerHTML = slotsInUse;
  }

  function setShips(ship, count) {
      var e = document.getElementById(ship);
      e.innerHTML = count;
  }

  function cursorevent(evt) {
      evt = (evt) ? evt : ((event) ? event : null);
      if(evt.keyCode == 37) {
          galaxy_submit('systemLeft');
      }

      if(evt.keyCode == 39) {
          galaxy_submit('systemRight');
      }

      if(evt.keyCode == 38) {
          galaxy_submit('galaxyRight');
      }

      if(evt.keyCode == 40) {
          galaxy_submit('galaxyLeft');
      }

  }
  document.onkeyup = cursorevent;
</script>

<?php

// Недостаточно дейтерия?
// Операторы и администраторы могут просматривать Галактику без затрат дейтерия.
if ( $not_enough_deut && $GlobalUser['admin'] == 0 )
{
?>
  <center>
<br />
<br />
<br />
<table width="519">
<tr height="20">
   <td class="c"><span class="error"><?php echo loca("GALAXY_DEUT_ERR");?></span></td>

</tr>
  <tr height="20">
    <th><span class="error"><?php echo loca("GALAXY_DEUT_ERR_TEXT");?></span></th>
  </tr>
</table>

<?php
}
else
{

/***** Меню выбора солнечной системы. *****/

echo "  <center>\n<form action=\"index.php?page=galaxy&no_header=1&session=".$_GET['session']."\" method=\"post\" id=\"galaxy_form\">\n";
echo "<input type=\"hidden\" name=\"session\" value=\"".$_GET['session']."\">\n";
echo "<input type=\"hidden\" id=\"auto\" value=\"dr\">\n";
echo "<table border=1 class='header' id='t1'>\n\n";
echo "<tr class='header'>\n";
echo "    <td class='header'><table class='header' id='t2'>\n";
echo "    <tr class='header'><td class=\"c\" colspan=\"3\">".loca("GALAXY_GALAXY")."</td></tr>\n";
echo "    <tr class='header'>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"galaxyLeft\" value=\"<-\" onClick=\"galaxy_submit('galaxyLeft')\"></td>\n";
echo "    <td class=\"l\"><input type=\"text\" name=\"galaxy\" value=\"".$coord_g."\" size=\"5\" maxlength=\"3\" tabindex=\"1\"></td>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"galaxyRight\" value=\"->\" onClick=\"galaxy_submit('galaxyRight')\"></td>\n";
echo "    </tr></table></td>\n\n";
echo "    <td class='header'><table class='header' id='t3'>\n";
echo "    <tr class='header'><td class=\"c\" colspan=\"3\">".loca("GALAXY_SYSTEM")."</td></tr>\n";
echo "    <tr class='header'>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"systemLeft\" value=\"<-\" onClick=\"galaxy_submit('systemLeft')\"></td>\n";
echo "    <td class=\"l\"><input type=\"text\" name=\"system\" value=\"".$coord_s."\" size=\"5\" maxlength=\"3\" tabindex=\"2\"></td>\n";
echo "    <td class=\"l\"><input type=\"button\" name=\"systemRight\" value=\"->\" onClick=\"galaxy_submit('systemRight')\"></td>\n";
echo "    </tr></table></td>\n";
echo "</tr>\n\n";
echo "<tr class='header'>\n";
echo "    <td class='header' style=\"background-color:transparent;border:0px;\" colspan=\"2\" align=\"center\"> <input type=\"submit\" value=\"".loca("GALAXY_SHOW")."\"></td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</form>\n";

/***** Форма запуска межпланетных ракет *****/

    $system_radius = abs ($aktplanet['s'] - $coord_s);
    $ipm_radius = max (0, 5 * $GlobalUser['r117'] - 1);
    $show_ipm_button = ($system_radius <= $ipm_radius) && ($aktplanet["d503"] > 0) && ($aktplanet['g'] == $coord_g);

    if ( isset($_GET['mode']) ) {

        $target = GetPlanet ( intval($_GET['pdd']) );

?>

   <form action="index.php?page=galaxy&session=<?=$session;?>&p1=<?=$coord_g;?>&p2=<?=$coord_s;?>&p3=<?=$coord_p;?>&zp=<?=intval($_GET['zp']);?>&pdd=<?=intval($_GET['pdd']);?>"  method="POST">   <tr>
   <table border="0">
    <tr>
     <td class="c" colspan="2">
      <?=loca("GALAXY_RAK_COORD");?> <a href="index.php?page=galaxy&no_header=1&session=<?=$session;?>&p1=<?=$target['g'];?>&p2=<?=$target['s'];?>&p3=<?=$target['p'];?>" >[<?=$target['g'];?>:<?=$target['s'];?>:<?=$target['p'];?>]</a>     </td>

    </tr>
    <tr>
     <td class="c">
     <?=va(loca("GALAXY_RAK_AMOUNT"), $aktplanet["d503"]);?>:     <input type="text" name="anz" size="2" maxlength="2" /></td>
    <td class="c">
    <?=loca("GALAXY_RAK_TARGET");?>:
     <select name="pziel">
      <option value="0" selected><?=loca("GALAXY_RAK_TARGET_ALL");?></option>
<?php
    foreach ($defmap as $i=>$gid)
    {
        echo "       <option value=\"$gid\">".loca("NAME_$gid")."</option>\n";
    }
?>
           </select>
    </td>
   </tr>
   <tr>
    <td class="c" colspan="2"><input type="submit" name="aktion" value="<?=loca("GALAXY_RAK_ATTACK");?>"></td>
   </tr>

  </table>
 </form>

<?php
    }

/***** Заголовок таблицы *****/

echo "<table width=\"569\">\n";
echo "<tr><td class=\"c\" colspan=\"8\">".loca("GALAXY_SYSTEM")." ".$coord_g.":".$coord_s."</td></tr>\n";
echo "<tr>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_COORD")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_PLANET")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_NAME_ACT")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_MOON")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_DF")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_PLAYER_STATUS")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_ALLY")."</td>\n";
echo "<td class=\"c\">".loca("GALAXY_HEAD_ACTIONS")."</td>\n";
echo "</tr>\n";

/***** Перечислить планеты *****/

$p = 1;
$tabindex = 3;
$result = EnumPlanetsGalaxy ( $coord_g, $coord_s );
$num = $planets = dbrows ($result);

$phalanx_radius = $aktplanet['b42'] * $aktplanet['b42'] - 1;

while ($num--)
{
    $planet = dbarray ($result);
    $user = LoadUser ( $planet['owner_id']);
    $own = $user['player_id'] == $GlobalUser['player_id'];
    for ($p; $p<$planet['p']; $p++) empty_row ($p);

    $phalanx = ($system_radius <= $phalanx_radius) && 
               ($aktplanet['type'] == PTYP_MOON) && 
               ($planet['owner_id'] != $GlobalUser['player_id']) &&
               ($planet['g'] == $aktplanet['g']);

    // Коорд.
    echo "<tr>\n";
    echo "<th width=\"30\"><a href=\"#\"  tabindex=\"".($tabindex++)."\" >".$p."</a></th>\n";

    // Планета
    echo "<th width=\"30\">\n";
    if ( $planet['type'] == PTYP_PLANET )
    {
        echo "<a style=\"cursor:pointer\" onmouseover='return overlib(\"<table width=240>";
        echo "<tr><td class=c colspan=2 >".loca("GALAXY_PLANET")." ".$planet['name']." [".$planet['g'].":".$planet['s'].":".$planet['p']."]</td></tr>";
        echo "<tr><th width=80 ><img src=".GetPlanetSmallImage ( UserSkin(), $planet )." height=75 width=75 /></th>";
        echo "<th align=left >";
        if ($own)
        {
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=4 >".loca("GALAXY_FLEET_DEPLOY")."</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=3 >".loca("GALAXY_FLEET_TRANSPORT")."</a><br />";
        }
        else
        {
            echo "<a href=# onclick=doit(6,".$planet['g'].",".$planet['s'].",".$planet['p'].",1,".$GlobalUser['maxspy'].") >".loca("GALAXY_FLEET_SPY")."</a><br><br />";
            if ($phalanx) echo "<a href=# onclick=fenster(&#039;index.php?page=phalanx&session=".$_GET['session']."&scanid=".$planet['owner_id']."&spid=".$planet['planet_id']."&#039;) >".loca("GALAXY_FLEET_PHALANX")."</a><br />";
            if ( $show_ipm_button ) echo "<a href=index.php?page=galaxy&no_header=1&session=$session&mode=1&p1=".$planet['g']."&p2=".$planet['s']."&p3=".$planet['p']."&pdd=".$planet['planet_id']."&zp=".$planet['owner_id']." >".loca("GALAXY_FLEET_RAK")."</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=1 m>".loca("GALAXY_FLEET_ATTACK")."</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=5 >".loca("GALAXY_FLEET_DEFEND")."</a><br />";
            echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$planet['g']."&system=".$planet['s']."&planet=".$planet['p']."&planettype=1&target_mission=3 >".loca("GALAXY_FLEET_TRANSPORT")."</a><br />";
        }
        if ($GlobalUser['admin'] >= 2) echo "<a href=index.php?page=admin&session=$session&mode=Planets&cp=".$planet['planet_id'].">".loca("GALAXY_PLANET_ADMIN")."</a><br />";
        echo "</th></tr></table>\", STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -40 );' onmouseout=\"return nd();\">\n";
        echo "<img src=\"".GetPlanetSmallImage ( UserSkin(), $planet )."\" height=\"30\" width=\"30\"/></a>\n";
    }
    echo "</th>\n";

    $moon = LoadPlanet ( $coord_g, $coord_s, $p, 3 );
    if ( $moon ) $moon_id = $moon['planet_id'];
    else $moon_id = 0;

    // Название (активность)
    $now = time ();
    $ago15 = $now - 15 * 60;
    $ago60 = $now - 60 * 60;
    $akt = "";
    if (!$own)
    {
        $activity = $planet['lastakt'];
        if ($moon_id && $moon['lastakt'] > $planet['lastakt'] ) $activity = $moon['lastakt'];
        if ( $activity > $ago15 ) $akt = "&nbsp;(*)";
        else if ( $activity > $ago60) $akt = "&nbsp;(".floor(($now - $activity)/60)." min)";
    }
    if ( $planet['type'] == PTYP_DEST_PLANET ) $planet_name = loca("GALAXY_PLANET_DESTROYED") . $akt;
    else if ( $planet['type'] == PTYP_ABANDONED ) { $planet_name = loca("GALAXY_PLANET_ABANDONED") . $akt; $phalanx = false; }
    else $planet_name = $planet['name'].$akt;
    if ($phalanx) $planet_name = "<a href='#' onclick=fenster('index.php?page=phalanx&session=$session&scanid=".$planet['owner_id']."&spid=".$planet['planet_id']."',\"Bericht_Phalanx\") title=\"".loca("GALAXY_FLEET_PHALANX")."\">" . $planet_name . "</a>";
    echo "<th width=\"130\" style='white-space: nowrap;'>$planet_name</th>\n";

    // луна
    echo "<th width=\"30\" style='white-space: nowrap;'>\n";
    if ($moon_id)
    {
        if ($moon['type'] == PTYP_MOON)
        {
            echo "<a onmouseout=\"return nd();\" onmouseover=\"return overlib('<table width=240 ><tr>";
            echo "<td class=c colspan=2 >".loca("GALAXY_MOON")." ".$moon['name']." [".$moon['g'].":".$moon['s'].":".$moon['p']."]</td></tr>";
            echo "<tr><th width=80 ><img src=".GetPlanetSmallImage ( UserSkin(), $moon )." height=75 width=75 alt=\'".va(loca("GALAXY_MOON_TITLE_SIZE"), $moon['diameter'])."\'/></th>";
            echo "<th><table width=120 ><tr><td colspan=2 class=c >".loca("GALAXY_MOON_PROPS")."</td></tr>";
            echo "<tr><th>".loca("GALAXY_MOON_SIZE")."</td><th>".nicenum($moon['diameter'])."</td></tr>";
            echo "<tr><th>".loca("GALAXY_MOON_TEMP")."</td><th>".$moon['temp']."</td></tr>";
            echo "<tr><td colspan=2 class=c >".loca("GALAXY_MOON_ACTIONS")."</td></tr>";
            echo "<tr><th align=left colspan=2 >";
            if ($own)
            {
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=3 >".loca("GALAXY_FLEET_TRANSPORT")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=4 >".loca("GALAXY_FLEET_DEPLOY")."</a><br />";
            }
            else
            {
                //echo "<font color=#808080 >Шпионаж</font><br><br />";
                echo "<a href=# onclick=doit(6,".$moon['g'].",".$moon['s'].",".$moon['p'].",3,".$GlobalUser['maxspy'].") >".loca("GALAXY_FLEET_SPY")."</a><br><br />";
                if ( $show_ipm_button ) echo "<a href=index.php?page=galaxy&no_header=1&session=$session&mode=1&p1=".$moon['g']."&p2=".$moon['s']."&p3=".$moon['p']."&pdd=".$moon['planet_id']."&zp=".$moon['owner_id']." >".loca("GALAXY_FLEET_RAK")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=3 >".loca("GALAXY_FLEET_TRANSPORT")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=1 >".loca("GALAXY_FLEET_ATTACK")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=5 >".loca("GALAXY_FLEET_DEFEND")."</a><br />";
                echo "<a href=index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$moon['g']."&system=".$moon['s']."&planet=".$moon['p']."&planettype=3&target_mission=9 >".loca("GALAXY_FLEET_DESTROY")."</a><br />";
            }
            if ($GlobalUser['admin'] >= 2) echo "<a href=index.php?page=admin&session=$session&mode=Planets&cp=".$moon['planet_id'].">".loca("GALAXY_PLANET_ADMIN")."</a><br />";
            echo "</th></tr></table></tr></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -110 );\" style=\"cursor: pointer;\" \n";
            echo " href='#' onclick='doit(6, ".$moon['g'].", ".$moon['s'].", ".$moon['p'].", 3, ".$GlobalUser['maxspy'].")' \n";
            echo ">\n";
            echo "<img width=\"22\" height=\"22\" alt=\"".va(loca("GALAXY_MOON_TITLE_SIZE"), $moon['diameter'])."\" src=\"".GetPlanetSmallImage ( UserSkin(), $moon )."\"/></a>\n";
        }
        else echo "<div style=\"border: 2pt solid #FF0000;\"><img src=\"".GetPlanetSmallImage ( UserSkin(), $moon )."\" alt=\"".va(loca("GALAXY_MOON_TITLE_SIZE"), $moon['diameter'])."\" height=\"22\" width=\"22\" onmouseover=\"return overlib('<font color=white><b>".loca("GALAXY_MOON_DESTROYED")."</b></font>', WIDTH, 75);\" onmouseout=\"return nd();\"/></div>\n";
    }
    echo "</th>\n";

    // поле обломков (не показывать ПО < 300 единиц)
    echo "<th width=\"30\">";
    $debris = LoadPlanet ( $coord_g, $coord_s, $p, 2 );
    if ( $debris )
    {
        $harvesters = ceil ( ($debris['m'] + $debris['k']) / $UnitParam[209][3]);
        if ( ($debris['m'] + $debris['k']) >= 300 )
        {
?>
    <a style="cursor:pointer"
       onmouseover="return overlib('<table width=240 ><tr><td class=c colspan=2 ></td></tr><tr><th width=80 ><img src=<?=UserSkin();?>planeten/debris.jpg height=75 width=75 alt=T /></th><th><table><tr><td class=c colspan=2><?=loca("GALAXY_DF_RESOURCES");?></td></tr><tr><th><?=loca("GALAXY_DF_M");?></th><th><?=nicenum($debris['m']);?></th></tr><tr><th><?=loca("GALAXY_DF_K");?></th><th><?=nicenum($debris['k']);?></th></tr><tr><td class=c colspan=2><?=loca("GALAXY_DF_ACTIONS");?></tr><tr><th colspan=2 align=left ><a href=# onclick=doit(8,<?=$coord_g;?>,<?=$coord_s;?>,<?=$p;?>,2,<?=$harvesters;?>) ><?=loca("GALAXY_FLEET_RECYCLE");?></a></tr></table></th></tr></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETX, -40, OFFSETY, -40 );" onmouseout="return nd();"
href='#' onclick='doit(8, <?=$coord_g;?>, <?=$coord_s;?>, <?=$p;?>, 2, <?=$harvesters;?>)'
>
<img src="<?=UserSkin();?>planeten/debris.jpg" height="22" width="22" /></a>
<?php
        }
    }
    echo "</th>\n";

    // игрок (статус)
    // Новичек или Сильный или Обычный
    // Приоритеты Обычного: Режим отпуска -> Заблокирован -> Давно неактивен -> Неактивен -> Без статуса
    $stat = "";
    echo "<th width=\"150\">\n";
    if ( !($planet['type'] == PTYP_DEST_PLANET || $planet['type'] == PTYP_ABANDONED) )
    {
        echo "<a style=\"cursor:pointer\" onmouseover=\"return overlib('<table width=240 >";
        echo "<tr><td class=c >".va(loca("GALAXY_USER_TITLE"), $user['oname'], $user['place1'])."</td></tr>";
        echo "<th><table>";
        if (!$own)
        {
            echo "<tr><td><a href=index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$planet['owner_id']." >".loca("GALAXY_USER_MESSAGE")."</a></td></tr>";
            echo "<tr><td><a href=index.php?page=buddy&session=".$_GET['session']."&action=7&buddy_id=".$planet['owner_id']." >".loca("GALAXY_USER_BUDDY")."</a></td></tr>";
        }
        echo "<tr><td><a href=index.php?page=statistics&session=".$_GET['session']."&start=".(floor($user['place1']/100)*100+1)." >".loca("GALAXY_USER_STATS")."</a></td></tr>";
        if ($GlobalUser['admin'] >= 2) echo "<tr><td><a href=index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id'].">".loca("GALAXY_USER_ADMIN")."</a></td></tr>";
        echo "</table>";
        echo "</th></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETY, -40 );\" onmouseout=\"return nd();\">\n";
        if ( IsPlayerNewbie ( $user['player_id'] ) )
        {
            $pstat = "noob"; $stat = "<span class='noob'>".loca("GALAXY_LEGEND_NOOB")."</span>";
        }
        else if ( IsPlayerStrong ( $user['player_id'] ) )
        {
            $pstat = "strong"; $stat = "<span class='strong'>".loca("GALAXY_LEGEND_STRONG")."</span>";
        }
        else
        {
            $week = time() - 604800;
            $week4 = time() - 604800*4;
            $pstat = "normal";
            if ( $user['lastclick'] <= $week ) { $stat .= "<span class='inactive'>".loca("GALAXY_LEGEND_INACTIVE7")."</span>"; $pstat = "inactive"; }
            if ( $user['banned'] ) { if(mb_strlen($stat, "UTF-8")) $stat .= " "; $stat .= "<a href='index.php?page=pranger&session=".$_GET['session']."'><span class='banned'>".loca("GALAXY_LEGEND_BANNED")."</span></a>"; $pstat = "banned"; }
            if ( $user['lastclick'] <= $week4 ) { if(mb_strlen($stat, "UTF-8")) $stat .= " "; $stat .= "<span class='longinactive'>".loca("GALAXY_LEGEND_INACTIVE28")."</span>";  if($pstat !== "banned") $pstat = "longinactive"; }
            if ( $user['vacation'] ) { if(mb_strlen($stat, "UTF-8")) $stat .= " "; $stat .= "<span class='vacation'>".loca("GALAXY_LEGEND_VACATION")."</span>";  $pstat = "vacation"; }
        }
        echo "<span class=\"$pstat\">".$user['oname']."</span></a>\n";
        if ($pstat !== "normal") echo "($stat)\n";
    }
    echo "</th>\n";

    // Альянс
    if ($user['ally_id'] && !($planet['type'] == PTYP_DEST_PLANET || $planet['type'] == PTYP_ABANDONED) )
    {
        $ally = LoadAlly ( $user['ally_id']);
        $allytext = "<a style=\"cursor:pointer\"\n";
        $allytext .= "         onmouseover=\"return overlib('<table width=240 ><tr><td class=c >".va(loca("GALAXY_ALLY_TITLE"), $ally['tag'], $ally['place1'], CountAllyMembers($user['ally_id']))."</td></tr><th><table>";
        $allytext .= "<tr><td><a href=ainfo.php?allyid=".$ally['ally_id']." target=_ally>".loca("GALAXY_ALLY_PAGE")."</a></td></tr>";
        if ($GlobalUser['ally_id'] != $user['ally_id']) {
            $allytext .= "<tr><td><a href=index.php?page=bewerben&session=$session&allyid=".$ally['ally_id']." >".loca("GALAXY_ALLY_APPLY")."</a></td></tr>";
        }
        $allytext .= "<tr><td><a href=index.php?page=statistics&session=$session&start=".(floor($ally['place1']/100)*100+1)."&who=ally >".loca("GALAXY_ALLY_STATS")."</a></td></tr>";
        if ($ally['homepage'] !== "") {
            $allytext .= "<tr><td><a href=redir.php?url=".$ally['homepage']." target=_blank >".loca("GALAXY_ALLY_HOMEPAGE")."</td></tr>";
        }
        $allytext .= "</table></th></table>', STICKY, MOUSEOFF, DELAY, 750, CENTER, OFFSETY, -50 );\" onmouseout=\"return nd();\">\n";
        $allytext .= "   ".$ally['tag']." </a>";
    }
    else $allytext = "";
    echo "<th width=\"80\">$allytext</th>\n";

    // Действия
    echo "<th width=\"125\" style='white-space: nowrap;'>\n";
    if ( !($planet['type'] == PTYP_DEST_PLANET || $planet['type'] == PTYP_ABANDONED) && !$own)
    {
        if ($prem['commander'] && $GlobalUser['flags'] & USER_FLAG_SHOW_VIEW_REPORT_BUTTON) {

            // Если доклад есть и для планеты и для луны, то показывается 2 кнопки.

            $planet_spy_report = GetSharedSpyReport ($planet['planet_id'], $GlobalUser['player_id'], $GlobalUser['ally_id']);
            $moon_spy_report = 0;
            if ($moon_id != 0) {
                $moon_spy_report = GetSharedSpyReport ($moon_id, $GlobalUser['player_id'], $GlobalUser['ally_id']);
            }

            if ($planet_spy_report != 0) {
                echo "<a href=\"#\" onclick=\"fenster('index.php?page=bericht&session=". $_GET['session'] ."&bericht=". $planet_spy_report ."', 'Bericht_Spionage');\" ><img src=\"".UserSkin()."img/s.gif\" border=\"0\" alt=\"".loca("SPY_REPORT")."\" title=\"".loca("SPY_REPORT")."\" /></a>\n";
            }

            if ($moon_spy_report != 0) {
                echo "<a href=\"#\" onclick=\"fenster('index.php?page=bericht&session=". $_GET['session'] ."&bericht=". $moon_spy_report ."', 'Bericht_Spionage');\" ><img src=\"".UserSkin()."img/s.gif\" border=\"0\" alt=\"".loca("SPY_REPORT")."\" title=\"".loca("SPY_REPORT")."\" /></a>\n";
            }

        }
        if ($GlobalUser['flags'] & USER_FLAG_SHOW_ESPIONAGE_BUTTON) {
            echo "<a style=\"cursor:pointer\" onclick=\"javascript:doit(6, ".$planet['g'].",".$planet['s'].",".$planet['p'].", 1, ".$GlobalUser['maxspy'].");\"><img src=\"".UserSkin()."img/e.gif\" border=\"0\" alt=\"".loca("GALAXY_FLEET_SPY")."\" title=\"".loca("GALAXY_FLEET_SPY")."\" /></a>\n";
        }
        if ($GlobalUser['flags'] & USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON) {
            echo "<a href=\"index.php?page=writemessages&session=".$_GET['session']."&messageziel=".$planet['owner_id']."\"><img src=\"".UserSkin()."img/m.gif\" border=\"0\" alt=\"".loca("GALAXY_USER_MESSAGE")."\" title=\"".loca("GALAXY_USER_MESSAGE")."\" /></a>\n";
        }
        if ($GlobalUser['flags'] & USER_FLAG_SHOW_BUDDY_BUTTON) {
            echo "<a href=\"index.php?page=buddy&session=".$_GET['session']."&action=7&buddy_id=".$planet['owner_id']."\"><img src=\"".UserSkin()."img/b.gif\" border=\"0\" alt=\"".loca("GALAXY_USER_BUDDY")."\" title=\"".loca("GALAXY_USER_BUDDY")."\" /></a>\n";
        }
        if ( $show_ipm_button && $GlobalUser['flags'] & USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON ) {
            echo "<a href=\"index.php?page=galaxy&session=$session&mode=1&p1=".$planet['g']."&p2=".$planet['s']."&p3=".$planet['p']."&pdd=".$planet['planet_id']."&zp=".$planet['owner_id']."\"><img src=\"".UserSkin()."img/r.gif\" border=\"0\" alt=\"".loca("GALAXY_FLEET_RAK")."\" title=\"".loca("GALAXY_FLEET_RAK")."\" /></a>";
        }
    }
    echo "</th>\n";

    echo "</tr>\n\n";
    $p++;
}
for ($p; $p<=15; $p++) empty_row ($p);

/***** Низ таблицы *****/
echo "<tr><th style='height:32px;'>16</th><th colspan='7'><a href ='index.php?page=flotten1&session=".$_GET['session']."&galaxy=".$coord_g."&system=".$coord_s."&planet=16&planettype=1&target_mission=15'>".loca("GALAXY_FAR_SPACE")."</a></th></tr>\n\n";

echo "<tr><td class=\"c\" colspan=\"6\">(".va(loca("GALAXY_INFO_POPULATED"), $planets).")</td>\n";
echo "<td class=\"c\" colspan=\"2\"><a href='#' onmouseover='return overlib(\"<table><tr><td class=c colspan=2>".loca("GALAXY_LEGEND")."</td></tr>";
echo "<tr><td width=125>".loca("GALAXY_LEGEND_STRONG_LONG")."</td><td><span class=strong>".loca("GALAXY_LEGEND_STRONG")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_NOOB_LONG")."</td><td><span class=noob>".loca("GALAXY_LEGEND_NOOB")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_VACATION_LONG")."</td><td><span class=vacation>".loca("GALAXY_LEGEND_VACATION")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_BANNED_LONG")."</td><td><span class=banned>".loca("GALAXY_LEGEND_BANNED")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_INACTIVE7_LONG")."</td><td><span class=inactive>".loca("GALAXY_LEGEND_INACTIVE7")."</span></td></tr>";
echo "<tr><td>".loca("GALAXY_LEGEND_INACTIVE28_LONG")."</td><td><span class=longinactive>".loca("GALAXY_LEGEND_INACTIVE28")."</span></td></tr>";
echo "</table>\", ABOVE, WIDTH, 150, STICKY, MOUSEOFF, DELAY, 500, CENTER);' onmouseout='return nd();'>".loca("GALAXY_LEGEND")."</a></td>\n";
echo "</tr>\n";

// Дополнительная информация (Командир).
// Текст набирается в переменную extra_info и если строка не пустая - выводится дополнительная строка таблицы.

$extra_info = "";
$sep = "&nbsp;&nbsp;&nbsp;&nbsp;";
$sep_required = false;

if ($prem['commander'] && $aktplanet["f210"] > 0) {
    $extra_info .= "<span id=\"probes\">".nicenum($aktplanet["f210"])."</span>".loca("GALAXY_INFO_SPY_PROBES");
    $sep_required = true;
}
if ($prem['commander'] && $aktplanet["f209"] > 0) {
    if ($sep_required) $extra_info .= $sep;
    $extra_info .= "<span id=\"recyclers\">".nicenum($aktplanet["f209"])."</span>".loca("GALAXY_INFO_RECYCLERS");
    $sep_required = true;
}
if ($prem['commander'] && $aktplanet["d503"] > 0) {
    if ($sep_required) $extra_info .= $sep;
    $extra_info .= "<span id=\"missiles\">".nicenum($aktplanet["d503"])."</span>".loca("GALAXY_INFO_IPM");
}
// Дейтерий показывается только для лун (даже без Командира)
if ($aktplanet['type'] == PTYP_MOON) {
    $extra_info .= loca("GALAXY_INFO_DEUTERIUM") . nicenum($aktplanet["d"]);
}
if ($prem['commander']) {
    $extra_info .= $sep . "<span id='slots'>".$nowfleet."</span>&nbsp;".va(loca("GALAXY_INFO_SLOTS"), $maxfleet);
}

if ($extra_info !== "") {
?>
<tr>
<td class="c" colspan="8">
<?=$extra_info;?></td>
</tr>
<?php
}   // extra_info
?>
<tr style="display: none;" id="fleetstatusrow"><th colspan="8"><!--<div id="fleetstatus"></div>-->
<table style="font-weight: bold;" width=100% id="fleetstatustable">
<!-- will be filled with content later on while processing ajax replys -->
</table>
</th>
</tr>

<?php
echo "</table>\n\n";

}    // Недостаточно дейтерия

echo "<br><br><br><br>\n";
EndContent ();

PageFooter ($GalaxyMessage, $GalaxyError, false, 0, true);
ob_end_flush ();
?>