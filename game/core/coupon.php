<?php

// API for coupon management.

/*
A little bit about the system itself.

Coupons are stored in the "master database" - on the same server as the main game page (Start Page).
The master database has access to all universes through the unis table, and all universes have access to the master database through /game/config.php
(if access to the master database is enabled via the mdb_enable variable)

The reasoning behind this is that coupons can be used in any universe.

The coupon code looks something like this: 2B2D-FE3D-7D74-37C4-D26M (a combination of numbers and capital Latin letters)

Coupons are sent out automatically to all active players (active in the game for more than 7 days).
Distribution dates are set by the universe administrator (usually on New Year's Eve and other national holidays)
Coupon distribution task is added to Queue, the handler of this task is located in this module.

All DM accrued through coupons is considered paid.

*/

// Function to send an email with a coupon code (UTF-8, HTML).
function mail_html (string $to, string $subject = '(No subject)', string $message = '', string $header = '') : void {
    $ip = $_SERVER['REMOTE_ADDR'];
    if ( !localhost($ip) ) {
        $header_ = 'MIME-Version: 1.0' . "\n" . 'Content-type: text/html; charset=UTF-8' . "\n";
        mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
    }

    // Add the log to temp.
    $f = fopen ( "temp/mailto.log", "a" );
    fprintf ( $f, "To: %s\r\nSubj: %s\r\n\r\n%s\r\n", $to, $subject, $message );
    fclose ($f);
}

// ------------------------------------------------------------------

// Load coupon object by ID. Return NULL if the coupon is not found.
function LoadCoupon (int $id) : mixed
{
    if ( MDBConnect() == false) return null;

    $query = "SELECT * FROM coupons WHERE id = " . intval ($id) . " LIMIT 1";
    $result = MDBQuery ( $query );
    if ( $result ) return MDBArray ( $result );
    else return null;
}

// Send the coupon code to the specified user
function SendCoupon (array $user, string $code) : void
{
    loca_add ( "coupons", $user['lang'] );    // add the language keys of the user to whom the message is sent.

    mail_html ( $user['pemail'], 
        loca_lang("COUPON_SUBJ", $user['lang']), 
        va ( loca_lang("COUPON_MESSAGE", $user['lang']), $user['oname'], $code ), 
        "From: coupon@" . $_SERVER['SERVER_NAME'] );
}

// Check if there is such a coupon and it is not activated. Returns the coupon ID or 0 if the coupon code is incorrect or the coupon is redeemed.
function CheckCoupon (string $code) : int
{
    if ( MDBConnect() )
    {
        $query = "SELECT * FROM coupons WHERE used = 0 AND code = '".$code."' LIMIT 1";
        $result = MDBQuery ($query );
        if (MDBRows ($result) )
        {
            $coupon = MDBArray ($result);
            return $coupon['id'];
        }
        else return 0;
    }
    else return 0;
}

// List all coupons. Return the result of the SQL query. Call parameters for paginator (start, count)
function EnumCoupons (int $start, int $count) : mixed
{
    if ( MDBConnect() )
    {
        $query = "SELECT * FROM coupons ORDER BY id DESC LIMIT $start, $count";
        return MDBQuery ($query);
    }
    else return null;
}

// Number of coupons in the database
function TotalCoupons () : int
{
    if ( MDBConnect() )
    {
        $query = "SELECT COUNT(*) FROM coupons;";
        $result = MDBQuery ( $query );
        $arr = MDBArray ( $result );
        foreach ( $arr as $i=>$val) {
            return $val;
        }
    }
    else return 0;
}

// Add a coupon (DM quantity). Return the coupon code, or NULL if failure.
function AddCoupon (int $dm) : string|null
{
    global $db_secret;
    $timeout = 10;

    if ( MDBConnect() )
    {
        while ($timeout--) {
            $code = substr( chunk_split ( strtoupper( substr(base_convert(sha1(uniqid(mt_rand()) . $db_secret), 16, 36), 0, 20) ), 4, '-' ) , 0, -1);
            if ( CheckCoupon ($code) == 0 ) break;
        }
        if ( $timeout == 0 ) return null;
        $query = "INSERT INTO coupons VALUES (NULL, '".$code."', ".intval($dm).", 0, 0, 0, '' )";
        MDBQuery ($query);
        return $code;
    }
    else return null;
}

// Activate the coupon. Return true if everything is fine or false if it's a mess.
function ActivateCoupon (array $user, string $code) : bool
{
    global $GlobalUni, $db_prefix;

    if ( MDBConnect() )
    {
        $id = CheckCoupon ($code);
        if ( $id ) {
            $coupon = LoadCoupon ($id);
            $query = "UPDATE coupons SET used=1, user_uni=".$GlobalUni['num'].", user_id=".$user['player_id'].", user_name='".$user['oname']."' WHERE id = $id";    // redeem coupon
            MDBQuery ($query);
            $query = "UPDATE ".$db_prefix."users SET dm = dm + ".$coupon['amount']." WHERE player_id = " . $user['player_id'];    // add a paid DM user.
            dbquery ($query);
            return true;
        }
        else return false;
    }
    else return false;
}

// Delete coupon
function DeleteCoupon (int $id) : void
{
    if ( MDBConnect() )
    {
        $query = "DELETE FROM coupons WHERE id = " . intval ($id);
        MDBQuery ($query);
    }
}

// Coupon charging task handler.
// sub_id: Number of DM
// obj_id: (Inactive for at least ... days << 16) | (Been in the game for over ... days)
// level: Periodicity ... days
function Queue_Coupon_End (array $queue) : void
{
    global $db_prefix;

    $now = $queue['end'];
    $ip = $_SERVER['REMOTE_ADDR'];

    // Choose users according to the criteria.
    $inactive_days = ($queue['obj_id'] >> 16) & 0xffff;
    $ingame_days = $queue['obj_id'] & 0xffff;
    $query = "SELECT * FROM ".$db_prefix."users WHERE regdate < ".($now - $ingame_days * 24*60*60)." AND lastclick >= " . ($now - $inactive_days * 24*60*60);
    $result = dbquery ($query);

    while ( $user = dbarray ($result) )    // Send out messages with coupons
    {
        $code = AddCoupon ( $queue['sub_id'] );
        SendCoupon ( $user, $code );
    }

    // Extend or end a task.
    $seconds = $queue['level'] * 24 * 60 * 60;
    if ( $seconds > 0 ) ProlongQueue ( $queue['task_id'], $seconds );
    else RemoveQueue ( $queue['task_id'] );
}

?>