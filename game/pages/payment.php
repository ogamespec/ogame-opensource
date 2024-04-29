<?php

// Payment.

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "premium", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

$ShowActivateDlg = false;
$CouponError = "";

if ( method() === "POST" ) 
{
    $code = $_POST['couponcode'];
    if (!empty($code)) {
        SecurityCheck ( '/[\-0-9A-Z]{24}/', $code, loca_lang ("DEBUG_PAYMENT_MANI_COUPON", $GlobalUni['lang']) );
    }

    if ( $_POST['action'] === "check" )
    {
        $id = CheckCoupon ( $code );
        if ( $id ) {
            $ShowActivateDlg = true;
            $coupon = LoadCoupon ($id);
        }
        else $CouponError = loca("PAY_INVALID_CODE");

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

BeginContent ();
?>

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
<?=loca("DM");?> <?=nicenum($coupon['amount']);?> !
</big>
</td></tr>
<tr><td colspan="2"><center><input type="submit" value="<?=loca("PAY_CREDIT");?>"></center></td></tr>
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
<tr><td class="c" colspan="2"><?=loca("PAY_USE");?></td></tr>
<tr><td class="left" colspan="2"><?=loca("PAY_ENTER");?></td></tr>
<tr>
<td class="left"><?=loca("PAY_COUPON_CODE");?></td>
<td class="right"><input name="couponcode" size="30" type="text" value=""></td>
</tr>
<?php
    if ( $CouponError !== "" ) {
?>
<tr>
<td class="left"><?=loca("PAY_ERROR");?></td>
<td class="right"><?=$CouponError;?></td>
</tr>
<?php
    }
?>
<tr><td colspan="2"><center><input type="submit" value="<?=loca("PAY_CHECK");?>"></center></td></tr>
</tbody></table>
</form>

<?php
    }
?>

<?php
EndContent ();
PageFooter ();
ob_end_flush ();
?>