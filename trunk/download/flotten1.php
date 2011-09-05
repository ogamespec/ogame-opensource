
 
<html> 
 <head> 
<link rel="stylesheet" type="text/css" href="use/<?=$_GET['i'];?>/formate.css"> 
  <script language="JavaScript" src="js/flotten.js"></script> 
 </head> 
 <body> 
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
            <td align='center' width='85'><font color=#ff0000>3.234.880</font></td> 
            <td align='center' width='85'>6/5.020</td> 
            <td align='center'></td> 
          </tr> 
        </table> 
 
 
 
</tr> 
</table> 
</center> 
<center> 
<br> 
  <table width="519" border="0" cellpadding="0" cellspacing="1"> 
   <tr height="20"> 
  <td colspan="8" class="c">Flotten (max. 11)</td> 
   </tr> 
   <tr height="20"> 
    <th>Nr.</th> 
    <th>Auftrag</th> 
    <th>Anzahl</th> 
    <th>Start</th> 
    <th>Absendezeit</th> 
    <th>Ziel</th> 
    <th>Ankunftszeit</th> 
    <th>Befehl</th>   
   </tr> 
   <tr height="20"> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
    <th>-</th> 
   </tr> 
    </table> 
 
 
  
<form action="flotten2.php?session=fe6d8c45c2a7" method="POST"> 
<table width="519" border="0" cellpadding="0" cellspacing="1"> 
       <tr height="20"> 
  <td colspan="4" class="c">Neuer Auftrag: Raumschiffe ausw&auml;hlen</td> 
   </tr> 
   <tr height="20"> 
  <th>Schiffsname</th> 
  <th>Vorhanden</th> 
<!--    <th>Gesch.</th> --> 
    <th>-</th> 
    <th>-</th> 
   </tr> 
   <tr height="20"> 
  <th colspan="2"><a style="cursor:pointer;cursor:hand" onClick="noShips();">Keine Schiffe</a></th> 
  <th colspan="2"><a style="cursor:pointer;cursor:hand" onClick="maxShips();">Alle Schiffe</a></th> 
   </tr> 
    <tr height="20"> 
    <th colspan="4"><input type="submit" value="Weiter" /></th> 
   </tr> 
</table> 
 
</form> 
 
 </body> 
</html> 