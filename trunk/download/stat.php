
 
<html> 
<head> 
<link rel="stylesheet" type="text/css" href="use/<?=$_GET['i'];?>/formate.css"> 
  <title>Nachrichten</title> 
</head> 
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
<br> 
 
 
<table> 
<tr><td class="c"> Statistiken</td></tr> 
<tr><th> 
Welcher<select name="wer" size="1" onchange="haha(this)"> <option value="/game/stat.php?session=fe6d8c45c2a7&von=0&typ=pkt">Spieler <option value="/game/stat.php?session=fe6d8c45c2a7&von=0&typ=pkt&wer=Ally" >Ally </select>ist bei <select name="typ" size="1" onchange="haha(this)"> <option value="/game/stat.php?session=fe6d8c45c2a7&von=0&typ=pkt" selected >Punkten <option value="/game/stat.php?session=fe6d8c45c2a7&von=0&typ=flotten"  >Flotten <option value="/game/stat.php?session=fe6d8c45c2a7&von=0&typ=forschung"  >Forschung</select>auf Platz <select name=von size=1 onchange="haha(this)" > <option value="/game/stat.php?session=fe6d8c45c2a7&von=0" selected>1-100 <option value="/game/stat.php?session=fe6d8c45c2a7&von=100" >101-200 <option value="/game/stat.php?session=fe6d8c45c2a7&von=200" >201-300 <option value="/game/stat.php?session=fe6d8c45c2a7&von=300" >301-400 <option value="/game/stat.php?session=fe6d8c45c2a7&von=400" >401-500 <option value="/game/stat.php?session=fe6d8c45c2a7&von=500" >501-600 <option value="/game/stat.php?session=fe6d8c45c2a7&von=600" >601-700 <option value="/game/stat.php?session=fe6d8c45c2a7&von=700" >701-800 <option value="/game/stat.php?session=fe6d8c45c2a7&von=800" >801-900 <option value="/game/stat.php?session=fe6d8c45c2a7&von=900" >901-1000 <option value="/game/stat.php?session=fe6d8c45c2a7&von=1000" >1001-1100 <option value="/game/stat.php?session=fe6d8c45c2a7&von=1100" >1101-1200 <option value="/game/stat.php?session=fe6d8c45c2a7&von=1200" >1201-1300 <option value="/game/stat.php?session=fe6d8c45c2a7&von=1300" >1301-1400 <option value="/game/stat.php?session=fe6d8c45c2a7&von=1400" >1401-1500 </select></th></tr> 
</table> 
<p> 
 
<table><tr><th class="c">Platz</th><th>Spieler</th><th></th><th>Allianz</th><th>Punkte</th><tr><th>1</th><th> Spieler</th><th><a href="writemessages.php?session=fe6d8c45c2a7&messageziel=100136"><img src="use/<?=$_GET['i'];?>/img/m.gif" border=0 alt="Nachricht schreiben"></a></th><th>TESTER</th><th>oo <-- unendlich</th></tr></table> 
</body> 
</html> 
 