<?php
/**
 * @file coupon.php
 * @brief Coupon code handling.
 * @details Validates and redeems coupon codes that grant resources or other rewards to players.
 */
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

/**
 * Sends an email with a coupon code as HTML (UTF-8) and logs it to a temporary file.
 *
 * @param string $to Recipient email address.
 * @param string $subject Email subject.
 * @param string $message Email message body.
 * @param string $header Additional email headers.
 * @return void
 */
function mail_html (string $to, string $subject = '(No subject)', string $message = '', string $header = '') : void {
    $ip = $_SERVER['REMOTE_ADDR'];
    if ( !localhost($ip) ) {
        $header_ = 'MIME-Version: 1.0' . "\n" . 'Content-type: text/html; charset=UTF-8' . "\n";
        mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $message, $header_ . $header);
    }

    // Add the log to temp.
    $f = fopen ( "temp/mailto.log", "a" );
    if ($f !== false) {
        fprintf ( $f, "To: %s\r\nSubj: %s\r\n\r\n%s\r\n", $to, $subject, $message );
        fclose ($f);
    }
}

// ------------------------------------------------------------------

/**
 * Loads a coupon by its ID from the master database.
 *
 * @param int $id Coupon ID.
 * @return mixed Coupon data as an associative array, or null if the coupon is not found.
 */
function LoadCoupon (int $id) : mixed
{
    if ( MDBConnect() == false) return null;

    $query = "SELECT * FROM coupons WHERE id = " . intval ($id) . " LIMIT 1";
    $result = MDBQuery ( $query );
    if ( $result ) return MDBArray ( $result );
    else return null;
}

/**
 * Sends the coupon code to the specified user by email.
 *
 * @param array $user User data array.
 * @param string $code Coupon code to send.
 * @return void
 */
function SendCoupon (array $user, string $code) : void
{
    loca_add ( "coupons", $user['lang'] );    // add the language keys of the user to whom the message is sent.

    mail_html ( $user['pemail'], 
        loca_lang("COUPON_SUBJ", $user['lang']), 
        va ( loca_lang("COUPON_MESSAGE", $user['lang']), $user['oname'], $code ), 
        "From: coupon@" . $_SERVER['SERVER_NAME'] );
}

/**
 * Checks if a coupon with the given code exists and is not yet redeemed.
 *
 * @param string $code Coupon code to check.
 * @return int Coupon ID, or 0 if the code is incorrect or the coupon is already redeemed.
 */
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

/**
 * Lists all coupons ordered by ID in descending order.
 *
 * @param int $start Offset for the paginator.
 * @param int $count Number of coupons to return.
 * @return mixed Query result resource, or null if the master database is unavailable.
 */
function EnumCoupons (int $start, int $count) : mixed
{
    if ( MDBConnect() )
    {
        $query = "SELECT * FROM coupons ORDER BY id DESC LIMIT $start, $count";
        return MDBQuery ($query);
    }
    else return null;
}

/**
 * Returns the total number of coupons in the database.
 *
 * @return int Number of coupons.
 */
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
    return 0;
}

/**
 * Generates a unique coupon code and adds a coupon with the given DM amount.
 *
 * @param int $dm Amount of DM granted by the coupon.
 * @return string|null Generated coupon code, or null on failure.
 */
function AddCoupon (int $dm) : string|null
{
    global $db_secret;
    $timeout = 10;

    if ( MDBConnect() )
    {
        while ($timeout--) {
            $code = substr( chunk_split ( strtoupper( substr(base_convert(sha1(uniqid((string)mt_rand()) . $db_secret), 16, 36), 0, 20) ), 4, '-' ) , 0, -1);
            if ( CheckCoupon ($code) == 0 ) break;
        }
        if ( $timeout == 0 ) return null;
        $query = "INSERT INTO coupons VALUES (NULL, '".$code."', ".intval($dm).", 0, 0, 0, '' )";
        MDBQuery ($query);
        return $code;
    }
    else return null;
}

/**
 * Redeems the coupon for the user, marking it as used and adding the DM amount.
 *
 * @param array $user User data array.
 * @param string $code Coupon code to activate.
 * @return bool True on success, false if the coupon is invalid or the master database is unavailable.
 */
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

/**
 * Deletes a coupon from the master database by its ID.
 *
 * @param int $id Coupon ID.
 * @return void
 */
function DeleteCoupon (int $id) : void
{
    if ( MDBConnect() )
    {
        $query = "DELETE FROM coupons WHERE id = " . intval ($id);
        MDBQuery ($query);
    }
}

/**
 * Handler for the coupon distribution queue task.
 * Sends coupons to all users matching the criteria: sub_id holds the number of DM,
 * obj_id is (inactive for at least N days << 16) | (in the game for over M days),
 * level is the task periodicity in days.
 *
 * @param array $queue Queue task data.
 * @return void
 */
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
        if ($code === null) continue;
        SendCoupon ( $user, $code );
    }

    // Extend or end a task.
    $seconds = $queue['level'] * 24 * 60 * 60;
    if ( $seconds > 0 ) ProlongQueue ( $queue['task_id'], $seconds );
    else RemoveQueue ( $queue['task_id'] );
}

?>