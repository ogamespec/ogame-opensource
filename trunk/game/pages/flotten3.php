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

PageHeader ("flotten3");
?>

<!-- CONTENT AREA -->
<div id='content'>
<center>

  <script language="JavaScript" src="js/flotten.js"></script>
  <script type="text/javascript">

  function getStorageFaktor(){
    return 1;
  }

  </script>

<!--
 <body>
 <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
-->
<center>
<table width="519" border="0" cellpadding="0" cellspacing="1">

<form action="index.php?page=flottenversand&session=3ff7ae974331" method="POST">
<input name="thisgalaxy" type="hidden" value="1" />
<input name="thissystem" type="hidden" value="260" />
<input name="thisplanet" type="hidden" value="4" />
<input name="thisplanettype" type="hidden" value="1" />
<input name="speedfactor" type="hidden" value="1" />
<input name="thisresource1" type="hidden" value="52579" />
<input name="thisresource2" type="hidden" value="15710" />
<input name="thisresource3" type="hidden" value="4725" />
<input name="galaxy" type="hidden" value="1" />
<input name="system" type="hidden" value="255" />
<input name="planet" type="hidden" value="4" />
<input name="planettype" type="hidden" value="1" />

   <input type="hidden" name="ship203" value="22" />
  <input type="hidden" name="consumption203" value="50"/>

  <input type="hidden" name="speed203" value="15000" />
  <input type="hidden" name="capacity203" value="25000" />
     <input type="hidden" name="ship215" value="30" />
  <input type="hidden" name="consumption215" value="250"/>
  <input type="hidden" name="speed215" value="34000" />
  <input type="hidden" name="capacity215" value="750" />
     <input type="hidden" name="speed" value="10" />

<tr height="20" align="left">
<td class="c" colspan="2">1:255:4 - планета</td>

</tr>
<tr valign="top" align="left">
<th width="50%">
  <table width="259" border="0"  cellpadding="0" cellspacing="0" >
  <tr height="20">
  <td class="c" colspan="2">Задание</td>
  </tr>
    <tr height="20">
<th>
  <input type="radio" name="order" value="3" >Транспорт<br />

     </th>
  </tr>
  <tr height="20">
<th>
  <input type="radio" name="order" value="4" >Оставить<br />
     </th>
  </tr>
   </table>
</th>

<th>
     <table  width="259" border="0" cellpadding="0" cellspacing="0">
     <tr height="20">
  <td colspan="3" class="c">Сырьё</td>
     </tr>
       <tr height="20">
      <th>Металл</th>
      <th><a href="javascript:maxResource('1');">max</a></th>

      <th><input name="resource1" type="text" alt="Металл 52579" size="10" onChange="calculateTransportCapacity();" /></th>
     </tr>
       <tr height="20">
      <th>Кристалл</th>
      <th><a href="javascript:maxResource('2');">max</a></th>
      <th><input name="resource2" type="text" alt="Кристалл 15710" size="10" onChange="calculateTransportCapacity();" /></th>
     </tr>
       <tr height="20">

      <th>Дейтерий</th>
      <th><a href="javascript:maxResource('3');">max</a></th>
      <th><input name="resource3" type="text" alt="Дейтерий 4725" size="10" onChange="calculateTransportCapacity();" /></th>
     </tr>
       <tr height="20">
  <th>Остаток</th>
      <th colspan="2"><div id="remainingresources">-</div></th>

     </tr>
     <tr height="20">
  <th colspan="3"><a href="javascript:maxResources()">Всё сырьё</a></th>
     </tr>

  <tr height="20">
  <th>&nbsp; </th>
  </tr>

   
    </table>
</th>
</tr>
<tr height="20" >
 <th colspan="2"><input type="submit" value="Дальше" /></th>
</tr>
 </form>
</table><br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>