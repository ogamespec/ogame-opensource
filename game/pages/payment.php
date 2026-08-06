<?php

// Payment / coupon activation.

class Payment extends Page {

    private bool $ShowActivateDlg = false;
    private string $CouponError = "";
    private array $coupon = [];

    public function controller () : bool {
        global $GlobalUser;
        global $GlobalUni;

        if ( method() === "POST" ) {
            $code = $_POST['couponcode'];
            if (!empty($code)) {
                SecurityCheck ( '/[\-0-9A-Z]{24}/', $code, loca_lang ("DEBUG_PAYMENT_MANI_COUPON", $GlobalUni['lang']) );
            }

            if ( $_POST['action'] === "check" ) {
                $id = CheckCoupon ( $code );
                if ( $id ) {
                    $this->ShowActivateDlg = true;
                    $this->coupon = LoadCoupon ($id);
                }
                else $this->CouponError = loca("PAY_INVALID_CODE");
            }
            else if ( $_POST['action'] === "activate" ) {
                ActivateCoupon ( $GlobalUser, $code );
                MyGoto ( "micropayment" );
            }
        }

        return true;
    }

    public function view () : void {
        global $session;

        if ( $this->ShowActivateDlg ) {
            ?>
            <form action="" method="POST" accept-charset="text/plain; charset=utf-8">
            <input type="hidden" name="action" value="activate">
            <input type="hidden" name="couponcode" value="<?=$this->coupon['code'];?>">
            <table class="ordertable">
            <tbody>
            <tr><td class="c" colspan="2">
            <big>
            <?=loca("NAME_".GID_RC_DM);?> <?=nicenum($this->coupon['amount']);?> !
            </big>
            </td></tr>
            <tr><td colspan="2"><center><input type="submit" value="<?=loca("PAY_CREDIT");?>"></center></td></tr>
            </tbody></table>
            </form>
            <?php
        }
        else {
            ?>
            <form action="" method="POST" accept-charset="text/plain; charset=utf-8">
            <input type="hidden" name="action" value="check">
            <table class="ordertable">
            <tbody>
            <tr><td class="c" colspan="2"><?=loca("PAY_USE");?></td></tr>
            <tr><td colspan="2"><?=loca("PAY_ENTER");?></td></tr>
            <tr>
            <td class="left"><?=loca("PAY_COUPON_CODE");?></td>
            <td class="right"><input name="couponcode" size="30" type="text" value=""></td>
            </tr>
            <?php
            if ( $this->CouponError !== "" ) {
                ?>
                <tr>
                <td class="left"><?=loca("PAY_ERROR");?></td>
                <td class="right"><?=$this->CouponError;?></td>
                </tr>
                <?php
            }
            ?>
            <tr><td colspan="2"><center><input type="submit" value="<?=loca("PAY_CHECK");?>"></center></td></tr>
            </tbody></table>
            </form>
            <?php
        }
    }
}
?>
