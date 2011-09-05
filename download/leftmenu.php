
<SCRIPT LANGUAGE="JavaScript"> 
function popUp(URL) {
day = new Date();
id = day.getTime();
eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=1,scrollbars=1,location=1,statusbar=1,menubar=0,resizable=1,width=120,height=60,alwaysLowered=Yes');");
}
 
</script> 
<script language=JavaScript> 
function fenster(target_url,win_name)
     {
var new_win = window.open(target_url,win_name,'resizable=yes,scrollbars=yes,menubar=no,toolbar=no,width=550,height=280,top=0,left=0');
new_win.focus();
      }
</script> 
<script language=JavaScript> 
///////////////////////////////////
function clickIE4(){
if (event.button==2){
alert(message);
return false;
}
}
 
function clickNS4(e){
if (document.layers||document.getElementById&&!document.all){
if (e.which==2||e.which==3){
alert(message);
return false;
}
}
}
 
if (document.layers){
document.captureEvents(Event.MOUSEDOWN);
document.onmousedown=clickNS4;
}
else if (document.all&&!document.getElementById){
document.onmousedown=clickIE4;
}
 
document.oncontextmenu=new Function("alert(message);return false")
</script> 
 
<script LANGUAGE="JavaScript"> 
  function hideStatus(){
    window.status='ogame';
    setTimeout("hideStatus()",10);
  }
// -->
</script> 
 
<html> 
 
 
 
 
<head> 
<title> 
Men
</title> 
 
 
<link rel="stylesheet" type="text/css" href="use/<?=$_GET['i'];?>/formate.css"> 
</head> 
<body class='style' topmargin='0' leftmargin='0' marginwidth='0' marginheight='0' > 
<center> 
Universum 777 (<a href="changelog.php?i=<?=$_GET['i'];?>" target="Hauptframe">v 0.84</a>)&nbsp;<br><p> 
  <table width="110" cellspacing="0" cellpadding="0"> 
    <tr> 
      <td><img src="use/<?=$_GET['i'];?>/gfx/ogame-produktion.jpg" width="110" height="40"></td> 
    </tr> 
 <tr> <td><div align="center"><font color="#FFFFFF"><a href="overview.php?i=<?=$_GET['i'];?>" target="Hauptframe">&Uuml;bersicht</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href='imperium.php?i=<?=$_GET['i'];?>' target='Hauptframe'>Imperium</a> </td> </tr> <tr> <td><div align="center"><a href="b_building.php?i=<?=$_GET['i'];?>" target="Hauptframe" >Geb&auml;ude </a><br> </td> </tr> <tr> <td><div align="center"><a href="resources.php?i=<?=$_GET['i'];?>" target="Hauptframe">Rohstoffe</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="buildings.php?i=<?=$_GET['i'];?>" target="Hauptframe">Forschung</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="buildings-fleet.php?i=<?=$_GET['i'];?>" target="Hauptframe">Schiffswerft</a></font></div> </td> </tr><tr><td><div align='center'><a href='flotten1.php?i=<?=$_GET['i'];?>' target='Hauptframe' >Flotte</a></div></td></tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="techtree.php?i=<?=$_GET['i'];?>" target="Hauptframe">Technik</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="galaxy.php?i=<?=$_GET['i'];?>" target="Hauptframe">Galaxie</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="buildings-def.php?i=<?=$_GET['i'];?>" target="Hauptframe">Verteidigung</a></font></div> </td> </tr> 
    <tr> 
      <td><img src="use/<?=$_GET['i'];?>/gfx/info-help.jpg" width="110" height="19"></td> 
    </tr> 
 <tr> <td><div align="center"><font color="#FFFFFF"><a href="allianzen.php?i=<?=$_GET['i'];?>" target="Hauptframe">Allianzen</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="http://board.oldogame.ru" target="_new">Forum</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="stat.php?i=<?=$_GET['i'];?>" target="Hauptframe">Statistiken</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="suche.php?i=<?=$_GET['i'];?>" target="Hauptframe">Suche</a></font></div> </td> </tr>  
    <tr> 
      <td><img src="use/<?=$_GET['i'];?>/gfx/user-menu.jpg" width="110" height="35"></td> 
    </tr> 
 <tr> <td><div align="center"><font color="#FFFFFF"><a href="messages.php?i=<?=$_GET['i'];?>" target="Hauptframe">Nachrichten</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="#" onclick="fenster('notizen.php?i=<?=$_GET['i'];?>','Bericht');" > Notizen</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="buddy.php?i=<?=$_GET['i'];?>" target="Hauptframe">Buddylist</a></font></div> </td> </tr> <tr> <td><div align="center"><font color="#FFFFFF"><a href="options.php?i=<?=$_GET['i'];?>" target="Hauptframe">Einstellungen</a></font></div> </td> </tr>  <tr> <td><div align="center"><font color="#FFFFFF"><a href="index.php" target="_top">Logout</a></font></div> </td> </tr> 
  </table> 
 
 
</font></div> 
</center> 
</body> 
</html>