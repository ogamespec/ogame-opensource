<?php

// Скупщик.

$TraderMessage = "";
$TraderError = "";

//Недостаточно тёмной материи!<br>
//Недостаточно места в хранилищах!<br>
//Недостаточно материала для торговли!<br>

SecurityCheck ( '/[0-9a-f]{12}/', $_GET['session'], "Манипулирование публичной сессией" );
if (CheckSession ( $_GET['session'] ) == FALSE) die ();

loca_add ( "common", $GlobalUser['lang'] );
loca_add ( "menu", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], $_GET['cp']);
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $GlobalUser['aktplanet'], $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );

// Обработка POST-запросов.
if ( method () === "POST" )
{
    $dm = $GlobalUser['dm'] + $GlobalUser['dmfree'];

    if ( $GlobalUser['trader'] > 0 )
    {
        print_r ( $_POST );
    }
    else
    {
        if ( $dm < 2500 )
        {
            $not_enough = true;
            $TraderError = "Недостаточно тёмной материи!<br>";
        }
        else
        {
            $not_enough = false;
        }
    }

}

PageHeader ("trader");

function is_selected ( $a, $b )
{
    if ( $a == $b ) return "selected";
    else return "";
}

if ( $GlobalUser['trader'] > 0 )
{
    $offer_id = $GlobalUser['trader'];
    if ( $offer_id == 1) $amount = floor ($aktplanet['m']);
    else if ( $offer_id == 2) $amount = floor ($aktplanet['k']);
    else if ( $offer_id == 3) $amount = floor ($aktplanet['d']);
    $mmax = max (0, $aktplanet['mmax'] - $aktplanet['m'] );
    $kmax = max (0, $aktplanet['kmax'] - $aktplanet['k'] );
    $dmax = max (0, $aktplanet['dmax'] - $aktplanet['d'] );
    $storage = "0, " . $mmax . ", " . $kmax . ", " . $dmax;
    $factor = "0, " . $GlobalUser['rate_m'] . ", " . $GlobalUser['rate_k'] . ", " . $GlobalUser['rate_d'];

    $resname = array ( "", "Металл", "Кристалл", "Дейтерий" );

    if ( $GlobalUser['trader'] == 1 ) $ratewhat = $GlobalUser['rate_m'];
    else if ( $GlobalUser['trader'] == 2 ) $ratewhat = $GlobalUser['rate_k'];
    else if ( $GlobalUser['trader'] == 3 ) $ratewhat = $GlobalUser['rate_k'];
    else $ratewhat = 1.0;

}

?>
<!-- CONTENT AREA -->
<div id='content'>
<center>
<script>
storage      = new Array(<?=$storage;?>);
factor       = new Array(<?=$factor;?>);
offer_id     = <?=$offer_id;?>;
offer_amount = <?=$amount;?>;
offer_costs  = 0;

function number_format(number, decimals, dec_point, thousands_sep) {
  var exponent = "";
  var numberstr = number.toString ();
  var eindex = numberstr.indexOf ("e");
  if (eindex > -1)  {
    exponent = numberstr.substring (eindex);
    number = parseFloat (numberstr.substring (0, eindex));
  }
  if (decimals != null)  {
    var temp = Math.pow (10, decimals);
    number = Math.round (number * temp) / temp;
  }
  var sign = number < 0 ? "-" : "";
  var integer = (number > 0 ? Math.floor (number) : Math.abs (Math.ceil (number))).toString ();
  var fractional = number.toString ().substring (integer.length + sign.length);
  dec_point = dec_point != null ? dec_point : ".";
  fractional = decimals != null && decimals > 0 || fractional.length > 1 ? (dec_point + fractional.substring (1)) : "";
  if (decimals != null && decimals > 0)  {
  	for (i = fractional.length - 1, z = decimals; i < z; ++i)
    fractional += "0";
  }
  thousands_sep = (thousands_sep != dec_point || fractional.length == 0) ? thousands_sep : null;
  if (thousands_sep != null && thousands_sep != ""){
	for (i = integer.length - 3; i > 0; i -= 3)integer = integer.substring (0 , i) + thousands_sep + integer.substring (i);
  }
  return sign + integer + fractional + exponent;
}



function setStorage(id, value) {
	document.getElementById(id + '_storage').innerHTML = number_format(value, 0, '', '.');
}

function setValue(id, value) {
	if (id != offer_id)	document.getElementsByName(id + '_value')[0].value = number_format(value, 0, '', '.');
	else                document.getElementById(id + '_value').innerHTML   = number_format(value, 0, '', '.');
}


function getValue(id) {
	if (id != offer_id)	result = document.getElementsByName(id + '_value')[0].value.replace(/^0+/,"");
	else                result = document.getElementById(id + '_value').innerHTML.replace(/^0+/,"");
	result = parseInt(result.split('.').join(''));
	if (isNaN(result)) return 0;
	else  			   return result;
}

function calcCosts(id, amount) {
	return Math.floor(amount * factor[offer_id] / factor[id]);
}

function calcInputFromCosts(id, amount) {
	return Math.max(Math.round(amount / factor[offer_id] * factor[id]),0);
}

function displayOfferCosts() {
	setValue(offer_id, offer_costs);
}

function getFreeOfferCosts() {
	value = Math.round(offer_amount - (offer_costs));
	return value;
}

function addOfferCosts(costs) {
	offer_costs = offer_costs + costs;
}

function checkValue(id) {
	if (getValue(id) < 0) {
		setValue(id, getValue(id) * -1);
	} 

	if (getValue(id) > storage[id]) {
		setValue(id, storage[id]);
	}	
	free_id     = 6 - id - offer_id; 
	offer_costs = calcCosts(free_id, getValue(free_id));
	costs = calcCosts(id, getValue(id));
	if (costs > getFreeOfferCosts()) {
		setValue(id, calcInputFromCosts(id, getFreeOfferCosts()));
		costs = calcCosts(id, getValue(id));
	}
	addOfferCosts(costs);
	displayOfferCosts();
	setStorage(id, storage[id] - getValue(id));	
}

function setMaxValue(id) {
	setValue(id, 99999999999999);
	checkValue(id);
}

</script>

<form action="index.php?page=trader&session=<?=$session;?>" name="TraderForm" method="POST">
	<TABLE class="c" width='520px'>
		<tr>

<?php
    if ( $GlobalUser['trader'] > 0 ) {

        echo "			<td class=\"c\"align='center' >".va ("Есть скупщик, которому Вы может продать #1.", $resname[$GlobalUser['trader']] ) ."</td>\n";
    }
    else echo "			<td class=\"c\"align='center' >Скупщик не найден!</td>\n";
?>	
	
			</tr>
		<tr>
			<th class="c" align='center'><br>
				Вы хотите продать				<select name="offer_id" style="color: lime;">

				  <option value="1" <?=is_selected($GlobalUser['trader'], 1);?>>Металл</option>
				  <option value="2" <?=is_selected($GlobalUser['trader'], 2);?>>Кристалл</option>
				  <option value="3" <?=is_selected($GlobalUser['trader'], 3);?>>Дейтерий</option>
				</select>		
				!				<br>
				<div id='darkmatter2'>Вызвать скупщика стоит 2500 тёмной материи.</div><br><br>

<?php
    if ( $not_enough )
    {
?>
	<a id='darkmatter2' href='index.php?page=payment&session=<?=$session;?>' style='cursor:pointer; text-align:center;width:100px;height:60px;'>
	<b><div id='darkmatter2'><img border="0" src="img/DMaterie.jpg" width="60" height="60"><br>Достать тёмную материю</a></b><br><br><br>
<?php
    }
?>

<?php
    if ( $GlobalUser['trader'] > 0 ) echo "				<input type='submit' name='call_trader' value='Вызвать другого скупщика'>\n";
    else echo "				<input type='submit' name='call_trader' value='Вызвать скупщика'>\n";
?>
			</th>
		</tr>
	</TABLE>	
	<br>
</form>	
<?php
    if ( $GlobalUser['trader'] > 0 )
    {

?>
<form action="index.php?page=trader&session=<?=$session;?>" name="TraderForm" method="POST">
    <TABLE width='520px'>
        <TR>
            <TD colspan=4 class="c" align='center'>Обменять</TD>
        </TR>
        
        <TR>
            <th></th>
            <th></th>
            <th>Свободное место хранилище</th>
            <th>Курс обмена</th>
        </TR>
        
        
        <TR>
            <th class="c" align="center" width=25% >Металл</th>
                          <th class="c" align='center' width=25% ><span id="1_value">0</span></th>
              <th class="c" align='center' width=25% >
<?php
    if ( $GlobalUser['trader'] != 1 ) echo "<span id=\"1_storage\">".nicenum($mmax)."</span>";
    else echo "---";
?>
</th>
                        
            <th class="c" align='center' width=25% >
                          <font size=3><b><?=$GlobalUser['rate_m'];?></b></font>
                        </th>           
        </TR>
        
        <TR>
            <th class="c" align="center" width=25% >Кристалл</th>
                          <th class="c" align='center' width=25% ><input type="text" size="9" name="2_value" value="0" style="text-align:right;" onkeyup='checkValue(2);'> <a href="#" onClick="setMaxValue(2);">max</a></th>
              <th class="c" align='center' width=25% >
<?php
    if ( $GlobalUser['trader'] != 2 ) echo "<span id=\"2_storage\">".nicenum($kmax)."</span>";
    else echo "---";
?>
</th>
                        
            <th class="c" align='center' width=25% >
                          <a href=# onmouseover="return overlib('<font color=white><?=va("Один #1 даёт #2 #3", $resname[$GlobalUser['trader']], round($GlobalUser['rate_k'] / $ratewhat, 2), $resname[2] );?></font>');" onmouseout="return nd();">
                          <font size=3><b><?=$GlobalUser['rate_k'];?></b></font>
                          </a>
                        </th>           
        </TR>
        
        <TR>
            <th class="c" align="center" width=25% >Дейтерий</th>
                          <th class="c" align='center' width=25% ><input type="text" size="9" name="3_value" value="0" style="text-align:right;" onkeyup='checkValue(3);'> <a href="#" onClick="setMaxValue(3);">max</a></th>
              <th class="c" align='center' width=25% >
<?php
    if ( $GlobalUser['trader'] != 3 ) echo "<span id=\"3_storage\">".nicenum($dmax)."</span>";
    else echo "---";
?>
</th>
                        
            <th class="c" align='center' width=25% >
                          <a href=# onmouseover="return overlib('<font color=white><?=va("Один #1 даёт #2 #3", $resname[$GlobalUser['trader']], round($GlobalUser['rate_d'] / $ratewhat, 2), $resname[3] );?></font>');" onmouseout="return nd();">
                          <font size=3><b><?=$GlobalUser['rate_d'];?></b></font>
                          </a>
                        </th>           
        </TR>
        
        <tr>
        <th class="c" align="center" colspan=4 ><br>Скупщик поставляет столько, сколько могут вместить ваши хранилища.       <br><br><input type=submit name='trade' value='Обменять!'>
        </th>
        </tr>
    </TABLE>
</form> 
<?php
    }
?>
	<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ($TraderMessage, $TraderError);
ob_end_flush ();
?>