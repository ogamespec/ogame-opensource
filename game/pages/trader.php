<?php

// Скупщик.

if (CheckSession ( $_GET['session'] ) == FALSE) die ();
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
    print_r ( $_POST );

}

PageHeader ("trader");

?>
<!-- CONTENT AREA -->
<div id='content'>
<center>
<script>
storage      = new Array();
factor       = new Array();
offer_id     = ;
offer_amount = ;
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
	
			<td class="c"align='center' >Скупщик не найден!</td>
	
			</tr>
		<tr>
			<th class="c" align='center'><br>
				Вы хотите продать				<select name="offer_id" style="color: lime;">

				  <option value="1" >Металл</option>
				  <option value="2" >Кристалл</option>
				  <option value="3" >Дейтерий</option>
				</select>		
				!				<br>
				<div id='darkmatter2'>Вызвать скупщика стоит 2500 тёмной материи.</div><br><br>

				<input type='submit' name='call_trader' value='Вызвать скупщика'>
			</th>
		</tr>
	</TABLE>	
	<br>
</form>	
	<br><br><br><br>
</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>