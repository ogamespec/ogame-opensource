<?php

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

PageHeader ("flotten2");
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>


  <script language="JavaScript" src="js/flotten.js"></script>
  <script language="JavaScript" src="js/ocnt.js"></script>

  <script type="text/javascript">

  function getStorageFaktor(){
    return 1  }

  </script>
  
 <!-- <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>  -->

<center>
<table width="519" border="0" cellpadding="0" cellspacing="1">
<form action="index.php?page=flotten3&session=3ff7ae974331" method="POST">
<input name="thisgalaxy" type="hidden" value="1" />
<input name="thissystem" type="hidden" value="260" />
<input name="thisplanet" type="hidden" value="4" />
<input name="thisplanettype" type="hidden" value="1" />
<input name="speedfactor" type="hidden" value="1" />
<input name="thisresource1" type="hidden" value="52530" />
<input name="thisresource2" type="hidden" value="15695" />
<input name="thisresource3" type="hidden" value="4721" />
   <input type="hidden" name="ship203" value="22" />
  <input type="hidden" name="consumption203" value="50" />
  <input type="hidden" name="speed203" value="15000" />

  <input type="hidden" name="capacity203" value="25000" />
     <input type="hidden" name="ship215" value="30" />
  <input type="hidden" name="consumption215" value="250" />
  <input type="hidden" name="speed215" value="34000" />
  <input type="hidden" name="capacity215" value="750" />
    <tr height="20">
  <td colspan="2" class="c">Отправление флота</td>
 </tr>

 <tr height="20">
  <th width="50%">Координаты цели</th>
  <th>
   <input name="galaxy" size="3" maxlength="2" onChange="shortInfo()" onKeyUp="shortInfo()" value="1" />
   <input name="system" size="3" maxlength="3" onChange="shortInfo()" onKeyUp="shortInfo()" value="260" />
   <input name="planet" size="3" maxlength="2" onChange="shortInfo()" onKeyUp="shortInfo()" value="4" />
   <select name="planettype" onChange="shortInfo()" onKeyUp="shortInfo()">
     <option value="1" >планета </option>

  <option value="2" >поле обломков </option>
  <option value="3" >луна </option>
   </select>
 </tr>
 <tr height="20">
  <th>Скорость</th>
  <th>

   <select name="speed" onChange="shortInfo()" onKeyUp="shortInfo()">
         <option value="10">100</option>
         <option value="9">90</option>
         <option value="8">80</option>
         <option value="7">70</option>
         <option value="6">60</option>

         <option value="5">50</option>
         <option value="4">40</option>
         <option value="3">30</option>
         <option value="2">20</option>
         <option value="1">10</option>
       </select> %
  </th>

 </tr>
 <tr height="20">
  <th>Расстояние</th><th><div id="distance">-</div></th>
 </tr>
 <tr height="20">
  <th>Продолжительность (в одну сторону)</th><th><div id="duration">-</div></th>
 </tr>

 <tr height="20">
  <th>Потребление топлива</th><th><div id="consumption">-</div></th>
 </tr>
 <tr height="20">
  <th>Максимальная скорость</th><th><div id="maxspeed">-</div></th>
 </tr>
 <tr height="20">

  <th>Грузоподъёмность</th><th><div id="storage">572.500</div></th>
 </tr>

  <tr height="20">
  <td colspan="2" class="c">Планета</td>
  </tr>
    <tr height="20">
   
   <th>

   <a href="javascript:setTarget(1,104,6,1); shortInfo()">
   Cold Plains 1:104:6</a>
    </th>


   <th>
   <a href="javascript:setTarget(1,242,13,1); shortInfo()">
   Чёрный Глаз 1:242:13</a>
    </th>

   </tr>
  <tr height="20">
   
   <th>
   <a href="javascript:setTarget(1,244,4,1); shortInfo()">
   Great Marsh 1:244:4</a>
    </th>


   <th>

   <a href="javascript:setTarget(1,255,4,1); shortInfo()">
   Lost City 1:255:4</a>
    </th>

   </tr>
  <tr height="20">
   
   <th>
   <a href="javascript:setTarget(1,255,4,3); shortInfo()">
   Lost Moon (Луна) 1:255:4</a>

    </th>


   <th>
   <a href="javascript:setTarget(1,260,4,3); shortInfo()">
   Frigid Moon (Луна) 1:260:4</a>
    </th>

   </tr>
  <tr height="20">

   
   <th>
   <a href="javascript:setTarget(1,286,5,1); shortInfo()">
   Rocky Waste 1:286:5</a>
    </th>


   <th>
   <a href="javascript:setTarget(1,286,5,3); shortInfo()">
   Rocky Moon (Луна) 1:286:5</a>

    </th>

   </tr>
  <tr height="20">
   
   <th>
   <a href="javascript:setTarget(1,335,6,1); shortInfo()">
   Far Oasis 1:335:6</a>
    </th>

   <th>
   <a href="javascript:setTarget(1,396,6,1); shortInfo()">
   Stony Field 1:396:6</a>
    </th>

   </tr>
  <tr height="20">
   
   <th>

   <a href="javascript:setTarget(1,414,4,1); shortInfo()">
   Durance of Hate 1:414:4</a>
    </th>

     <th>&nbsp; </th>
</tr>

   </th>
  </tr>

  <tr height="20">
     <td colspan="2" class="c">Боевые союзы  </tr>
 <tr height="20"><th colspan="2">-</th></tr>
<tr height="20">
 <th colspan="2">
  <input type="submit" value="Дальше" />
 </th>
</tr>

</form>
</table>

<script>
window.onload=shortInfo;
</script><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>