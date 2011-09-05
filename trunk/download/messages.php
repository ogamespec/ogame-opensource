
 
 
 
<html> 
 
 <head> 
  <link rel="stylesheet" type="text/css" href="use/<?=$_GET['i'];?>/formate.css"> 
 </head> 
 <script  language="JavaScript"> 
  function fenster(target_url,win_name) {
   var new_win = window.open(target_url,win_name,'resizable=yes,scrollbars=yes,menubar=no,toolbar=no,width=640,height=480,top=0,left=0');
    new_win.focus();
  }
 </script> 
 <script language="JavaScript"> 
  function fenstered(target_url,win_name) {
    var new_win = window.open(target_url,win_name,'resizable=yes,scrollbars=yes,menubar=no,toolbar=no,width=480,height=480,top=0,left=0');
    new_win.focus();
  }
 </script> 
 <body> 
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
<br>  <table width="519"> 
   <form action="messages.php?session=fe6d8c45c2a7" method="POST"> 
    <tr> 
    <td colspan="4" class="c">Nachrichten</td> 
    </tr> 
         
    <tr> 
    <th>Anzeigen</th> 
    <th colspan="2">Art</th> 
    <th>Anzahl / Ungelesen</th> 
    </tr> 
    <tr> 
       <th><input type="checkbox" name="espioopen"  /></th> 
       <th colspan="2"><a href="messages.php?i=<?=$_GET['i'];?>">Spionageberichte</a></th> 
       <th>0 / 0</th> 
    </tr> 
 
    <tr> 
     <th><input type="checkbox" name="combatopen"  /></th> 
     <th colspan="2"><a href="messages.php?i=<?=$_GET['i'];?>">Kampfberichte</a></th> 
     <th>0 / 0</th> 
    </tr> 
 
    <tr> 
     <th><input type="checkbox" name="allyopen"  /></th> 
     <th colspan="2"><a href="messages.php?i=<?=$_GET['i'];?>">Ally Nachrichten</a></th> 
     <th>0 / 0</th> 
    </tr> 
 
    <tr> 
     <th><input type="checkbox" name="useropen"  /></th> 
     <th colspan="2"><a href="messages.php?i=<?=$_GET['i'];?>">Spielernachrichten</a></th> 
     <th>0 / 0</th> 
    </tr> 
 
    <tr> 
     <th><input type="checkbox" name="generalopen"  /></th> 
     <th colspan="2"><a href="messages.php?i=<?=$_GET['i'];?>">Sonstige Nachrichten</a></th> 
     <th>0 / 0</th> 
    </tr> 
 
    
    
    
    
    
    
        <tr> 
     <th colspan="4"> 
      <input type="submit" value="ok" /> 
     </th> 
    </tr> 
        
      <input type="hidden" name="messages" value="1" /> 
   </form> 
 
      <form action="messages.php?session=fe6d8c45c2a7" method="POST"> 
    <tr height="20"> </tr> 
    <tr> 
      <td colspan="4" class="c">Adressbuch</td> 
    </tr> 
    <tr> 
      <th>Anzeigen</th> 
     <th colspan="2">Art</th> 
     <th>Anzahl</th> 
    </tr> 
    <tr> 
     <th><input type="checkbox" name="owncontactsopen" ></th> 
     <th colspan="2">Buddyliste </th> 
     <th>0</th> 
    </tr>    
    
    <tr> 
     <th><input type="checkbox" name="ownallyopen" ></th> 
     <th colspan="2">Eigene Ally</th> 
     <th>0</th> 
    </tr>    
    
    <tr> 
     <th><input type="checkbox" name="gameoperatorsopen" ></th> 
     <th colspan="2">Game Operatoren</th> 
     <th>0</th> 
    </tr> 
        <tr> 
     <th colspan="4"> 
      <input type="hidden" name="addressbook" value="1" /> 
      <input type="submit" value="ok" /> 
     </th> 
    </tr>    
   </form> 
 
   <form action="messages.php?session=fe6d8c45c2a7" method="POST"> 
    <tr height="20"> </tr> 
    <tr> 
    <td colspan="4" class="c">Notizen</td> 
    </tr> 
    <tr> 
    <th colspan="2">Anzeigen</th> 
    <th colspan="2">Anzahl</th> 
    </tr> 
    <tr> 
     <th colspan="2"><input type="checkbox" name="noticesopen" ></th> 
     <th colspan="2">0</th> 
    </tr> 
 
    
    <tr> 
     <th colspan="4"> 
      <input type="hidden" name="notices" value="1" /> 
      <input type="submit" value="ok" /> 
     </th> 
    </tr>    
 
   </form> 
      </table> 
 </center> 
 </body> 
</html> 