
<html> 
<head> 
<link rel="stylesheet" type="text/css" href="use/<?=$_GET['i'];?>/formate.css"> 
  <title>Einstellungen</title> 
 
</head> 
<body "> 
<center> 
 
  <center> 
  <table><tr><td> 
</td><td> 
      <center><table><tr><td><img src="use/<?=$_GET['i'];?>/planeten/small/s_dschjungelplanet05.jpg" width=50 height=50 ></td><td><table border=1 ><select  size=1> 
<option  value="/game/overview.php" selected>Planet [1:1:5]
<option  value="/game/overview.php">Mond [1:1:5]
</select> 
</table></table></td><td> 
        <table border='0' width='100%' cellspacing='0' cellpadding='0' > 
          <tr> 
            <td align='center'></td> 
            <td align='center' width='85'><img border='0' src='use/<?=$_GET['i'];?>/images/metall.gif' width='42' height='22'></td> 
            <td align='center' width='85'><img border='0' src='use/<?=$_GET['i'];?>/images/kristall.gif' width='42' height='22'></td> 
            <td align='center' width='85'><img border='0' src='use/<?=$_GET['i'];?>/images/deuterium.gif' width='42' height='22'></td> 
            <td align='center' width='85'><img border='0' src='use/<?=$_GET['i'];?>/images/energie.gif' width='42' height='22'></td> 
            <td align='center'></td> 
          </tr> 
          <tr> 
            <td align='center'><i><b>&nbsp;&nbsp;</b></i></td> 
            <td align='center' width='85'><i><b><font color='#ffffff'>Metall</font></b></i></td> 
            <td align='center' width='85'><i><b><font color='#ffffff'>Kristall</font></b></i></td> 
            <td align='center' width='85'><i><b><font color='#ffffff'>Deuterium</font></b></i></td> 
            <td align='center' width='85'><i><b><font color='#ffffff'>Energie</font></b></i></td> 
            <td align='center'><i><b>&nbsp;&nbsp;</b></i></td> 
          </tr> 
          <tr> 
            <td align='center'></td> 
            <td align='center' width='85'><font color=#ff0000>863.324</font></td> 
            <td align='center' width='85'><font color=#ff0000>1.756.330</font></td> 
            <td align='center' width='85'><font color=#ff0000>3.234.860</font></td> 
            <td align='center' width='85'>6/5.020</td> 
            <td align='center'></td> 
          </tr> 
        </table> 
 
 
 
</tr> 
</table> 
</center> 
<center> 
<br> 
  <font color="#ff0000"> 
 Dieser Account hat noch bis zum  4000-00-00  den Commanderstatus.  
 
 
<script language=JavaScript> 
function fenstered(target_url,win_name)
     {
var new_win = window.open(target_url,win_name,'resizable=yes,scrollbars=yes,menubar=no,toolbar=no,width=480,height=480,top=0,left=0');
new_win.focus();
      }
</script> 
<br> <a href="#" onclick="fenstered('werbsponsor.php?session=fe6d8c45c2a7','Bericht');" ><font color="#ff6666">Verl�ngern?</font></a> 
</font> 
  
<form action="options.php?session=fe6d8c45c2a7&mode=change" method="POST" > 
 <table width="519"> 
 
     <tr><td class="c" colspan ="2">Userdaten</td></tr> 
<tr> 
      <th>Username</th> 
   <th><input type="text" name="inp57d9b22" size ="20" value="Spieler" /></th> 
    </tr> 
  <tr> 
  <th>Altes Passwort</th> 
   <th><input type="password" name="inp1b1c381" size ="20" value="" /></th> 
  </tr> 
  <tr> 
  <th>Neues Passwort (min. 8 Zeichen)</th> 
   <th><input type="password" name="inp740caf0" size="20" maxlength="40" /></th> 
  </tr> 
  <tr> 
  <th>Neues Passwort (Wiederholung)</th> 
   <th><input type="password" name="inp4fd48c1" size="20" maxlength="40" /></th> 
  </tr> 
  <tr> 
  <th><a title="Diese Mailadresse kann jederzeit von Dir ge&auml;ndert werden. Nach 7 Tagen ohne &Auml;nderung wird diese auch als dauerhafte Adresse eingetragen.">E-Mail Adresse</a></th> 
  <th><input type="text" name="inp991e64a" maxlength="100" size="20" value="" /></th> 
  </tr> 
  <tr> 
  <th>Dauerhafte E-Mail Adresse</th> 
   <th></th> 
  </tr> 
   <tr><th colspan="2"> 
  </tr> 
  <tr> 
  <td class="c" colspan="2">Generelle Einstellungen</td> 
  </tr> 
  <th>Skin Pfad (z.b. C:/ogame/bilder/)<br /> <a href="http://80.237.203.201/download/" target="_blank">Download</a></th> 
   <th><input type=text name="inpb40f61b" maxlength="80" size="40" value="" /> <br /> 
  </select> 
   </th> 
  </tr> 
  <tr> 
  <th>Skin anzeigen</th> 
   <th> 
    <input type="checkbox" name="inpd9794c5" 
    checked=checked /> 
   </th> 
  </tr> 
  <tr> 
    <th><a title="IP-Check bedeutet, dass automatisch ein Sicherheitslogout erfolgt, wenn die IP gewechselt wird oder zwei Leute gleichzeitig unter verschiedenen IPs in einem Account eingeloggt sind. 
Den IP-Check zu deaktivieren kann ein Sicherheitsrisiko darstellen!
">IP-Check deaktivieren</a></th> 
   <th><input type="checkbox" name="inpa81c857" checked=checked /></th> 
  </tr> 
  <tr> 
   <th><a title="Anzahl der Spionagesonden, die bei jedem Scan aus dem Galaxiemen� direkt verschickt wird.">Spionagesonden Anzahl</a></th> 
   <th><input type="text" name="inpf974abb" maxlength="2" size="2" value="1" /></th> 
 
  </tr> 
   
    <tr> 
     <td class="c" colspan="2">Urlaubsmodus / Account l&ouml;schen</td> 
  </tr> 
  <tr> 
     <th><a title="Der Urlaubsmodus soll euch w&auml;hrend l&auml;ngerer Abwesenheitszeiten sch&uuml;tzen. Man kann ihn nur aktivieren, wenn nichts gebaut (Flotte, Geb�ude oder Verteidigung) und  nichts geforscht wird und auch keine eigenen Flotten unterwegs sind.
Ist er aktiviert, sch&uuml;tzt er euch vor neuen Angriffen, bereits begonnene Angriffe werden jedoch fortgesetzt. W&auml;hrend des Urlaubsmodus wird die Produktion auf Null gesetzt und muss nach Beenden des Urlaubsmodus manuell wieder auf 100% gesetzt werden. Der Urlaubsmodus dauert mindestens 2 Tage, erst danach k&ouml;nnt ihr ihn wieder deaktivieren.">Urlaubsmodus aktivieren</a></th> 
   <th> 
    <input type="checkbox" name="inpaf0b082" 
     /> 
   </th> 
 
 
  
 
  </tr> 
  <tr> 
   <th><a title="Wenn du hier ein H&auml;kchen setzt, wird dein Account nach 7 Tagen automatisch komplett gel&ouml;scht.">Account L�schen</a></th> 
   <th><input type="checkbox" name="inpf7ec05b"  /> 
   
   
   
   </th> 
  </tr> 
  <tr> 
   <th colspan=2><input type="submit" value="&Auml;nderungen Speichern" /></th> 
  </tr> 
 
 
   
 </table> 
 
 
</form> 
 
</center> 
</body> 
</html> 