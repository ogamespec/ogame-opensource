<?php

// Оплата.

loca_add ( "menu", $GlobalUni['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

$ShowActivateDlg = false;
$CouponError = "";

if ( method() === "POST" ) 
{
    $code = $_POST['couponcode'];
    SecurityCheck ( '/[\-0-9A-Z]{24}/', $code, "Манипулирование кодом купона" );

    if ( $_POST['action'] === "check" )
    {
        $id = CheckCoupon ( $code );
        if ( $id ) {
            $ShowActivateDlg = true;
            $coupon = LoadCoupon ($id);
        }
        else $CouponError = "Неверный код или купон уже погашен";

        //Код более не действителен.
        //Неверный код
    }

    else if ( $_POST['action'] === "activate" )
    {
        ActivateCoupon ( $GlobalUser, $code );
        MyGoto ( "micropayment" );
    }

}

PageHeader ("payment");

?>

<!-- CONTENT AREA -->
<div id='content'>
<center>

<?php

    if ( $ShowActivateDlg )
    {
?>

<form action="" method="POST" accept-charset="text/plain; charset=utf-8">
<input type="hidden" name="action" value="activate">
<input type="hidden" name="couponcode" value="<?=$coupon['code'];?>">
<table class="ordertable">
<tbody>
<tr><td class="c" colspan="2">
<big>
Тёмная материя <?=nicenum($coupon['amount']);?> !
</big>
</td></tr>
<tr><td colspan="2"><center><input type="submit" value="Зачислить!"></center></td></tr>
</tbody></table>
</form>

<?php
    }
    else
    {
?>

<form action="" method="POST" accept-charset="text/plain; charset=utf-8">
<input type="hidden" name="action" value="check">
<table class="ordertable">
<tbody>
<tr><td class="c" colspan="2">Использовать купон.</td></tr>
<tr><td class="left" colspan="2">Введите ваш код купона здесь.</td></tr>
<tr>
<td class="left">Код купона:</td>
<td class="right"><input name="couponcode" size="30" type="text" value=""></td>
</tr>
<?php
    if ( $CouponError !== "" ) {
?>
<tr>
<td class="left">Ошибка купона:</td>
<td class="right"><?=$CouponError;?></td>
</tr>
<?php
    }
?>
<tr><td colspan="2"><center><input type="submit" value="Проверить купон."></center></td></tr>
</tbody></table>
</form>

<?php
    }
?>

</center>
</div>
<!-- END CONTENT AREA -->

<?php
PageFooter ();
ob_end_flush ();
?>