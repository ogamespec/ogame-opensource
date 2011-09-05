
 
<html> 
<head> 
<link rel="stylesheet" type="text/css" href="use/<?=$_GET['i'];?>/formate.css"> 
  <title>Rohstoffe</title> 
 
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
            <td align='center' width='85'><font color=#ff0000>3.234.880</font></td> 
            <td align='center' width='85'>6/5.020</td> 
            <td align='center'></td> 
          </tr> 
        </table> 
 
 
 
</tr> 
</table> 
</center> 
<center> 
<br></center> 
 
<br> 
<br> 
Produktionsfaktor: 1
<form action="resources.php?session=fe6d8c45c2a7" method="post"> 
<table width="500"> 
  <tr> 
    <td class="c" colspan="5"> 
    Rohstoffproduktion  auf Planet &quot;mond nit putmachen&quot;
    </td> 
  </tr> 
  <tr> 
   <th> 
   </th> 
   <th> 
    Metall
   </th> 
   <th> 
   Kristall
   </th> 
   <th> 
   Deuterium
   </th> 
   <th> 
   Energie
   </th> 
  </tr> 
  <tr> 
   <th> 
   Grundeinkommen
   </th> 
   <td class="k"> 
   20
   </td> 
   <td class="k"> 
   10
   </td> 
   <td class="k"> 
   0
   </td> 
   <td class="k"> 
   0
   </td> 
  </tr> 
    <tr> 
   <th> 
     Metallmine      (Stufe 26)
   </th> 
      <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#FFFFFF"> 
                           0/0             </th>  
    
  
    <th> <select name="last1" size="1"> 
        <option value="100" >100%
        <option value="90" >90%
        <option value="80" >80%
        <option value="70" >70%
        <option value="60" >60%
        <option value="50" >50%
        <option value="40" >40%
        <option value="30" >30%
        <option value="20" >20%
        <option value="10" >10%
        <option value="0" selected>0%
        </select> 
   </th> 
  </tr> 
   
    <tr> 
   <th> 
     Kristallmine      (Stufe 22)
   </th> 
      <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#00FF00"> 
             3581</font> 
      
     <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#FF0000"> 
                           1790/1790             </th>  
    
  
    <th> <select name="last2" size="1"> 
        <option value="100" selected>100%
        <option value="90" >90%
        <option value="80" >80%
        <option value="70" >70%
        <option value="60" >60%
        <option value="50" >50%
        <option value="40" >40%
        <option value="30" >30%
        <option value="20" >20%
        <option value="10" >10%
        <option value="0" >0%
        </select> 
   </th> 
  </tr> 
   
    <tr> 
   <th> 
     Deuteriumsynthetisierer      (Stufe 22)
   </th> 
      <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#00FF00"> 
             1840</font> 
      
     <th> 
    <font color="#FF0000"> 
                           3223/3223             </th>  
    
  
    <th> <select name="last3" size="1"> 
        <option value="100" >100%
        <option value="90" selected>90%
        <option value="80" >80%
        <option value="70" >70%
        <option value="60" >60%
        <option value="50" >50%
        <option value="40" >40%
        <option value="30" >30%
        <option value="20" >20%
        <option value="10" >10%
        <option value="0" >0%
        </select> 
   </th> 
  </tr> 
   
    <tr> 
   <th> 
     Solarkraftwerk      (Stufe 24)
   </th> 
      <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#00FF00"> 
                   4727             </th>  
    
  
    <th> <select name="last4" size="1"> 
        <option value="100" selected>100%
        <option value="90" >90%
        <option value="80" >80%
        <option value="70" >70%
        <option value="60" >60%
        <option value="50" >50%
        <option value="40" >40%
        <option value="30" >30%
        <option value="20" >20%
        <option value="10" >10%
        <option value="0" >0%
        </select> 
   </th> 
  </tr> 
   
    <tr> 
   <th> 
     Fusionskraftwerk      (Stufe 4)
   </th> 
      <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#FFFFFF"> 
             0</font> 
      
     <th> 
    <font color="#FF0000"> 
             -59</font> 
      
     <th> 
    <font color="#00FF00"> 
                   292   	     </th>  
    
  
    <th> <select name="last12" size="1"> 
        <option value="100" selected>100%
        <option value="90" >90%
        <option value="80" >80%
        <option value="70" >70%
        <option value="60" >60%
        <option value="50" >50%
        <option value="40" >40%
        <option value="30" >30%
        <option value="20" >20%
        <option value="10" >10%
        <option value="0" >0%
        </select> 
   </th> 
  </tr> 
   
    <tr> 
  <tr> 
    <th>Lagerkapazit&auml;t</th> 
    <td class="k"><font color="#00ff00">600k</font></td> 
    <td class="k"><font color="#00ff00">400k</font></td> 
    <td class="k"><font color="#00ff00">200k</font></td> 
    <td class="k"><font color="#00ff00">-</font></td> 
    <td class="k"><input type=submit name=action value="Berechne"></td> 
  </tr> 
  <tr> 
    <th colspan="5" height="4"></th> 
  </tr> 
  <tr> 
    <th>Gesamt:</th> 
 
 
    <td class="k"><font color="#00ff00">20</font></td> 
    <td class="k"><font color="#00ff00">3591</font></td> 
 
        <td class="k"><font color="#00ff00">1781</font></td> 
             <td class="k"><font color="#00ff00">4</font></td> 
      <tr> 
</table> 
</center> 
</body> 
</html> 