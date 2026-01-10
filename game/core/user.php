<?php

// User Management.

/*
player_id: User ordinal number (INT AUTO_INCREMENT PRIMARY KEY)
regdate: Date of account registration (INT UNSIGNED)
ally_id: ID of the alliance in which the player is a member (0 - no alliance) (INT)
joindate: Date of joining the alliance (INT UNSIGNED)
allyrank: Rank of player in the alliance (INT)
session: Public session for the links (CHAR (12))
private_session: Private session for cookies (CHAR(32))
name: Lower-case user name for comparison (CHAR(20))
oname: Username original (CHAR(20))
name_changed: Is the username changed? (1 or 0) (INT)
Q name_until: When you can change the username next time (INT UNSIGNED)
password: MD5 hash of password and secret word (CHAR(32))
temp_pass: MD5 hash of the recovery password and secret word (CHAR(32))
pemail: Permanent mailing address (CHAR(50))
email: Temporary mailing address (CHAR(50))
email_changed: Temporary mailing address has been changed (INT)
Q email_until: When to replace a permanent email with a temporary one (INT UNSIGNED)
disable: The account has been put up for deletion (INT)
Q disable_until: When you can delete an account (INT UNSIGNED)
vacation: An account in vacation mode (INT)
vacation_until: When you can turn off vacation mode (INT UNSIGNED)
banned: Account blocked (INT)
Q banned_until: Blocking end time (INT UNSIGNED)
noattack: Ban on attacks (INT)
Q noattack_until: When the ban on attacks ends (INT UNSIGNED)
lastlogin: Last date logged in (INT UNSIGNED)
lastclick: Last click, to determine the player's activity (INT UNSIGNED)
ip_addr: user IP address
validated: User is activated. If the user is not activated, they are not allowed to send game messages and applications to alliances. (INT)
validatemd: Activation code (CHAR(32))
hplanetid: Home Planet ID (INT)
admin: 0 - regular player, 1 - operator, 2 - administrator (INT)
sortby: Planets sorting order: 0 - colonization order, 1 - coordinates, 2 - alphabetical order (INT)
sortorder: Order: 0 - ascending, 1 - descending (INT)
skin: Skin path (CHAR(80)). It is obtained by concatenating the path to the host and the skin name, but the length of the string is not more than 80 characters.
useskin: Show skin, if 0 - then show default skin (INT)
deact_ip: Disable IP verification (INT)
maxspy: Number of spy probes (1 by default, 0...99) (INT)
maxfleetmsg: Maximum fleet messages to the Galaxy (3 by default, 0...99, 0=1) (INT)
lang: Game interface language (CHAR(4))
aktplanet: Current selected planet. (INT)
dm: Purchased DM (INT)
dmfree: DM found on the expedition (INT)
sniff: Enable tracking of browsing history (Admin Area) (INT)
debug: Enable display of debugging information (INT)
trader: 0 - no merchant found, 1 - merchant buys metal, 2 - merchant buys crystal, 3 - merchant buys deuterium (INT)
rate_m, rate_k, rate_d: merchant exchange rates ( e.g. 3.0 : 1.8 : 0.6 ) (DOUBLE)
score1,2,3: Points for buildings, fleets, and research (BIGINT UNSIGNED, INT UNSIGNED, INT UNSIGNED )
place1,2,3: Place for buildings, fleet, research (INT)
oldscore1,2,3: Old points for buildings, fleets, and research (BIGINT UNSIGNED, INT UNSIGNED, INT UNSIGNED )
oldplace1,2,3: old place for buildings, fleet, research (INT)
scoredate: Time of saving old statistics (INT UNSIGNED)
rXXX: Research XXX level (INT DEFAULT 0)
flags: User flags. The full list is below (USER_FLAG). I didn't think of this idea right away, some variables can also be made into flags (INT UNSIGNED)
feedid: feed id (eg 5aa28084f43ad54d9c8f7dd92f774d03)  (CHAR(32))
lastfeed: last Feed update timestamp (INT UNSIGNED)
com_until: Officer expires: Commander (INT UNSIGNED)
adm_until: Officer expires: Admiral (INT UNSIGNED)
eng_until: Officer expires: Engineer (INT UNSIGNED)
geo_until: Officer expires: Geologist (INT UNSIGNED)
tec_until: Officer expires: Technocrat (INT UNSIGNED)

Q - task in the task queue is used to process this event.
*/

// Flag mask (flags property)
const USER_FLAG_SHOW_ESPIONAGE_BUTTON = 0x1;    // 1: Display the "Espionage" icon" in the galaxy
const USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON = 0x2;       // 1: Display the "Write message" icon in the galaxy
const USER_FLAG_SHOW_BUDDY_BUTTON = 0x4;        // 1: Display the "Buddy request" icon in the galaxy
const USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON = 0x8;    // 1: Display the "Missile Attack" icon in the galaxy
const USER_FLAG_SHOW_VIEW_REPORT_BUTTON = 0x10;     // 1: Display the "View Message" icon in the galaxy
const USER_FLAG_DONT_USE_FOLDERS = 0x20;        // 1: Do not sort messages into folders in Commander mode
const USER_FLAG_PARTIAL_REPORTS = 0x40;         // 1: Show partial spy report
const USER_FLAG_FOLDER_ESPIONAGE = 0x100;           // Message Filter. 1: Show spy reports (pm=1)
const USER_FLAG_FOLDER_COMBAT = 0x200;              // Message Filter. 1: Show battle reports & missile attacks (pm=2)
const USER_FLAG_FOLDER_EXPEDITION = 0x400;          // Message Filter. 1: Show expedition results (pm=3)
const USER_FLAG_FOLDER_ALLIANCE = 0x800;            // Message Filter. 1: Show alliance messages (pm=4)
const USER_FLAG_FOLDER_PLAYER = 0x1000;             // Message Filter. 1: Show private messages (pm=0)
const USER_FLAG_FOLDER_OTHER = 0x2000;              // Message Filter. 1: Show all other messages (pm=5)
const USER_FLAG_HIDE_GO_EMAIL = 0x4000;                 // Show an in-game message icon instead of the operator's email (not all operators may like to publish their email)
const USER_FLAG_FEED_ENABLE = 0x8000;               // 1: feed enabled
const USER_FLAG_FEED_ATOM = 0x10000;                // 0 - use RSS format, 1 - use Atom format

const USER_OFFICER_COMMANDER = 1;
const USER_OFFICER_ADMIRAL = 2;
const USER_OFFICER_ENGINEER = 3;
const USER_OFFICER_GEOLOGE = 4;
const USER_OFFICER_TECHNOCRATE = 5;

// Default flags after creating a player
const USER_FLAG_DEFAULT = USER_FLAG_SHOW_ESPIONAGE_BUTTON | USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON | USER_FLAG_SHOW_BUDDY_BUTTON | USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON | USER_FLAG_SHOW_VIEW_REPORT_BUTTON;

const USER_LEGOR = 1;
const USER_SPACE = 99999;           // A technical account that owns global events as well as "nobody's" galaxy objects

const USER_NOOB_LIMIT = 5000;           // Number of points for a newbie

// Very limited cache implementation: The cache is only kept for the lifetime of the script.
// Attention! Using caches in the game introduces a significant probability of obscure errors ("Heisenbugs"), so it is NOT recommended to use persistent cache (which is kept between HTTP requests)
$UserCache = array ();

// Corrected version of date
function fixed_date ( string $fmt, int $timestamp ) : string
{
    $date = new DateTime ('@' . $timestamp);
    return $date->format ($fmt);
}

// Send a welcome email with a link to activate your account (in the language of the universe).
function SendGreetingsMail ( string $name, string $pass, string $email, string $ack) : void
{
    $unitab = LoadUniverse ();
    $uni = $unitab['num'];
    loca_add ("reg", $unitab['lang']);

    $text = va ( loca_lang("REG_GREET_MAIL_BODY", $unitab['lang']), 
        $name,
        $uni,
        hostname()."game/validate.php?ack=$ack",
        $name,
        $pass,
        $uni );
    if (!empty($unitab['ext_board'])) {
        $text .= va (loca_lang("REG_GREET_MAIL_BOARD", $unitab['lang']), $unitab['ext_board']);
    }
    if (!empty($unitab['ext_tutorial'])) {
        $text .= va (loca_lang("REG_GREET_MAIL_TUTORIAL", $unitab['lang']), $unitab['ext_tutorial']);
    }
    $text .= loca_lang ("REG_GREET_MAIL_FOOTER", $unitab['lang']);

    $domain = "";   // ru, org..
    mail_utf8 ( $email, loca_lang ("REG_GREET_MAIL_SUBJ", $unitab['lang']), $text, "From: OGame Uni $domain $uni <noreply@".$_SERVER['SERVER_NAME'].">");
}

// Send a letter confirming the change of address (in the language of the universe).
function SendChangeMail ( string $name, string $email, string $pemail, string $ack) : void
{
    $unitab = LoadUniverse ();
    $uni = $unitab['num'];
    loca_add ("reg", $unitab['lang']);
    
    $text = va (loca_lang("REG_CHANGE_MAIL_BODY", $unitab['lang']), 
        $name,
        $uni,
        $email,
        hostname()."game/validate.php?ack=$ack" );

    $domain = "";   // ru, org..
    mail_utf8 ( $pemail, loca_lang ("REG_CHANGE_MAIL_SUBJ", $unitab['lang']), $text, "From: OGame Uni $domain $uni <noreply@".$_SERVER['SERVER_NAME'].">");
}

// Send a welcome message (in the user's language)
function SendGreetingsMessage ( int $player_id) : void
{
    $unitab = LoadUniverse ();
    $user = LoadUser ($player_id);
    if ($user == null) return;
    loca_add ("reg", $user['lang']);
    loca_add ("fleetmsg", $user['lang']);
    SendMessage ( $player_id, 
        loca_lang ("FLEET_MESSAGE_FROM", $user['lang']), 
        loca_lang ("REG_GREET_MSG_SUBJ", $user['lang']), 
        bb ( va(loca_lang("REG_GREET_MSG_TEXT", $user['lang']), $unitab['ext_board'], $unitab['ext_tutorial']) ), MTYP_MISC );
}

function IsUserExist ( string $name) : bool
{
    global $db_prefix;
    $name = mb_strtolower ($name, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."'";
    $result = dbquery ($query);
    return dbrows ($result) != 0;
}

// Exclude the name $name from the search.
function IsEmailExist ( string $email, string $name="") : bool
{
    global $db_prefix;
    $name = mb_strtolower ($name, 'UTF-8');
    $email = mb_strtolower ($email, 'UTF-8');
    $query = "SELECT * FROM ".$db_prefix."users WHERE (email = '".$email."' OR pemail = '".$email."')";
    if ($name !== "") $query .= " AND name <> '".$name."'";
    $result = dbquery ($query);
    return dbrows ($result) != 0;
}

// There are no checks for correctness! This is handled by the registration procedure.
// Returns the ID of the created user.
function CreateUser ( string $name, string $pass, string $email, bool $bot=false) : int
{
    global $db_prefix, $db_secret, $Languages;
    $origname = $name;
    $name = mb_strtolower ($name, 'UTF-8');
    $email = mb_strtolower ($email, 'UTF-8');
    $md = md5 ($pass . $db_secret);
    $ack = md5(time ().$db_secret);

    // Increase the user count in the universe.
    $query = "SELECT * FROM ".$db_prefix."uni".";";
    $result = dbquery ($query);
    $unitab = dbarray ($result);
    $unitab['usercount']++;
    $query = "UPDATE ".$db_prefix."uni"." SET usercount = ".$unitab['usercount'].";";
    dbquery ($query);

    // Set the language of the registered player: if there is a selected language in cookies and the player is NOT a bot - use it when registering.
    // Otherwise, use the default language of the universe
    if ( !$bot && key_exists ( 'ogamelang', $_COOKIE ) && !$unitab['force_lang'] ) $lang = $_COOKIE['ogamelang'];
    else $lang = $unitab['lang'];
    if ( !key_exists ( $lang, $Languages ) ) $lang = $unitab['lang'];

    $ip = $_SERVER['REMOTE_ADDR'];

    $user = array( 'regdate' => time(), 'ally_id' => 0, 'joindate' => 0, 'allyrank' => 0, 'session' => "",  'private_session' => "", 'name' => $name, 'oname' => $origname, 'name_changed' => 0, 'name_until' => 0, 'password' => $md, 'temp_pass' => "", 'pemail' => $email, 'email' => $email,
                    'email_changed' => 0, 'email_until' => 0, 'disable' => 0, 'disable_until' => 0, 'vacation' => 0, 'vacation_until' => 0, 'banned' => 0, 'banned_until' => 0, 'noattack' => 0, 'noattack_until' => 0,
                    'lastlogin' => 0, 'lastclick' => 0, 'ip_addr' => $ip, 'validated' => 0, 'validatemd' => $ack, 'hplanetid' => 0, 'admin' => 0, 'sortby' => 0, 'sortorder' => 0,
                    'skin' => hostname() . "evolution/", 'useskin' => 1, 'deact_ip' => 0, 'maxspy' => 1, 'maxfleetmsg' => 3, 'lang' => $lang, 'aktplanet' => 0,
                    'dm' => 0, 'dmfree' => $unitab['start_dm'], 'sniff' => 0, 'debug' => 0, 'trader' => 0, 'rate_m' => 0, 'rate_k' => 0, 'rate_d' => 0,
                    'score1' => 0, 'score2' => 0, 'score3' => 0, 'place1' => 0, 'place2' => 0, 'place3' => 0,
                    'oldscore1' => 0, 'oldscore2' => 0, 'oldscore3' => 0, 'oldplace1' => 0, 'oldplace2' => 0, 'oldplace3' => 0, 'scoredate' => 0,
                    'flags' => USER_FLAG_DEFAULT, 'feedid' => "", 'lastfeed' => 0, 'com_until' => 0, 'adm_until' => 0, 'eng_until' => 0, 'geo_until' => 0, 'tec_until' => 0 );
    $id = AddDBRow ( $user, "users" );

    LogIPAddress ( $ip, $id, 1 );

    // Create a Home Planet.
    $homeplanet = CreateHomePlanet ($id);

    $query = "UPDATE ".$db_prefix."users SET hplanetid = $homeplanet, aktplanet = $homeplanet WHERE player_id = $id;";
    dbquery ( $query );

    // Send a welcome email and message.
    if ( !$bot ) {
        if ( !localhost($ip) ) SendGreetingsMail ( $origname, $pass, $email, $ack);
        SendGreetingsMessage ( $id);
    }

    // Delete an inactivated user after 3 days.

    SetVar ( $id, "TimeLimit", 3*365*24*60*60 );

    RecalcRanks ();

    return $id;
}

// Completely delete the player, all their planets and fleets.
// Turn back fleets flying at the player.
function RemoveUser ( int $player_id, int $when) : void
{
    global $GlobalUser, $db_prefix;

    // Administrator and space accounts cannot be deleted.
    if ($player_id == USER_LEGOR || $player_id == USER_SPACE) return;

    // Turn back all fleets flying at the player.
    $result = EnumFleetQueue ($player_id);
    $rows = dbrows ( $result );
    while ($rows--) {
        $queue = dbarray ($result);
        $fleet_obj = LoadFleet ( $queue['sub_id'] );
        if ($fleet_obj['owner_id'] != $player_id && $fleet_obj['mission'] < FTYP_RETURN ) RecallFleet ( $fleet_obj['fleet_id'], $when );
    }

    LockTables();

    // Delete all of the player's fleets
    $query = "DELETE FROM ".$db_prefix."fleet WHERE owner_id = $player_id";
    dbquery ($query);

    // Delete all tasks from the queue
    $query = "DELETE FROM ".$db_prefix."queue WHERE owner_id = $player_id";
    dbquery ($query);
    $query = "DELETE FROM ".$db_prefix."buildqueue WHERE owner_id = $player_id";
    dbquery ($query);    

    // Delete all planets other than the DF that go into space possession.
    $query = "DELETE FROM ".$db_prefix."planets WHERE owner_id = $player_id AND type <> " . PTYP_DF;
    dbquery ($query);
    $query = "UPDATE ".$db_prefix."planets SET owner_id = ".USER_SPACE." WHERE owner_id = $player_id AND type = " . PTYP_DF;
    dbquery ($query);

    // Delete a player.
    $query = "DELETE FROM ".$db_prefix."users WHERE player_id = $player_id";
    dbquery ($query);

    // Decrement the number of users.
    $query = "UPDATE ".$db_prefix."uni SET usercount = usercount - 1;";
    dbquery ($query);

    // Delete alliance applications
    $apply_id = GetUserApplication ( $player_id );
    if ( $apply_id ) RemoveApplication ($apply_id);

    // Remove from the buddy list
    $query = "DELETE FROM ".$db_prefix."buddy WHERE request_from = $player_id OR request_to = $player_id";
    dbquery ($query);

    UnlockTables ();

    RecalcRanks ();

    // If the user is deleted from the user itself - redirect to the home page so the user doesn't see the mess.
    if ($player_id == $GlobalUser['player_id']) {
        ob_clean ();
        RedirectHome ();
        exit ();
    }
}

// Activate the user.
function ValidateUser (string $code) : void
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE validatemd = '".$code."'";
    $result = dbquery ($query);
    if (dbrows ($result) == 0)
    {
        RedirectHome ();
        return;
    }
    $user = dbarray ($result);
    if (!$user['validated'])
    {    // Replace the permanent address with a temporary one after activation.
        $query = "UPDATE ".$db_prefix."users SET pemail = '".$user['email']."' WHERE player_id = ".$user['player_id'];
        dbquery ($query);
    }
    $query = "UPDATE ".$db_prefix."users SET validatemd = '', validated = 1 WHERE player_id = ".$user['player_id'];
    dbquery ($query);
    Login ( $user['oname'], "", $user['password'], 1 );
}

// Verify password. Returns 0, or the user ID.
function CheckPassword ( string $name, string $pass, string $passmd="") : int
{
    global $db_prefix, $db_secret;
    $name = mb_strtolower ($name, 'UTF-8');
    if ($passmd === "") $md = md5 ($pass . $db_secret);
    else $md = $passmd;
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."' AND password = '".$md."'";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) return 0;
    $user = dbarray ($result);
    return $user['player_id'];
}

// Change the temporary mail address. Returns true if the address was successfully changed, or false if the address is already in use.
function ChangeEmail ( string $name, string $email) : bool
{
    global $db_prefix, $db_secret;
    $name = mb_strtolower ($name, 'UTF-8');
    $email = mb_strtolower ($email, 'UTF-8');
    if (IsEmailExist ($uni, $email, $name)) return false;
    $query = "UPDATE ".$db_prefix."users SET email = '".$email."' WHERE name = '".$name."'";
    dbquery ($query);
    $ack = ChangeActivationCode ( $name);
    $query = "SELECT * FROM ".$db_prefix."users WHERE name = '".$name."'";
    $result = dbquery ($query);
    $user = dbarray ($result);
    SendChangeMail ( $user['oname'], $email, $user['pemail'], $ack);
    return true;
}

// Change username.
function ChangeName ( int $player_id, string $name ) : void
{
    global $db_prefix;
    $lower = mb_strtolower ($name, 'UTF-8');
    $query = "UPDATE ".$db_prefix."users SET name = '".$lower."', oname = '".$name."' WHERE player_id = $player_id";
    dbquery ($query);
    AddAllowNameEvent ($player_id);
}

// Change activation code. Returns the new code.
function ChangeActivationCode ( string $name) : string
{
    global $db_prefix, $db_secret;
    $name = mb_strtolower ($name, 'UTF-8');
    $ack = md5(time ().$db_secret);
    $query = "UPDATE ".$db_prefix."users SET validatemd = '".$ack."' WHERE name = '".$name."'";
    dbquery ($query);
    return $ack;
}

// Select the current planet.
function SelectPlanet (int $player_id, int $cp) : void
{
    global $db_prefix;
    $planet = GetPlanet ($cp);
    // If the planet could not be loaded (this happens, for example, when the page with the destroyed moon is open),
    // try to load the player's home planet.
    if ($planet == null) {
        $user = LoadUser ($player_id);
        if ($user == null) {
            Error ("Error loading user.");
        }
        $cp = $user['hplanetid'];
        $planet = GetPlanet ($cp);
        if ($planet == null) {
            Error ("Error loading the current planet.");
        }
    }
    // You can't select other player's planets.
    if ($planet['owner_id'] != $player_id || $planet['type'] >= 10000 )
    {
        Hacking ( "HACK_SELECT_PLANET" );
        return;
    }
    $query = "UPDATE ".$db_prefix."users SET aktplanet = '".$cp."' WHERE player_id = '".$player_id."'";
    dbquery ($query);
    InvalidateUserCache ();
}

// Get the ID of the current selected planet
function GetSelectedPlanet ( int $player_id ) : int
{
    $user = LoadUser ( $player_id );
    if ($user == null) return 0;
    return $user['aktplanet'];
}

// Load User.
function LoadUser ( int $player_id) : array|null
{
    global $db_prefix, $UserCache;
    if ( isset ( $UserCache [ $player_id ] ) ) return  $UserCache [ $player_id ];
    $query = "SELECT * FROM ".$db_prefix."users WHERE player_id = '".$player_id."' LIMIT 1";
    $result = dbquery ($query);
    $user = dbarray ($result);
    if (!$user) {
        return null;
    }
    $UserCache [ $player_id ] = $user;
    return $user;
}

// Update user activity (NOT PLANET activity).
function UpdateLastClick ( int $player_id) : void
{
    global $db_prefix;
    $now = time ();
    $query = "UPDATE ".$db_prefix."users SET lastclick = $now WHERE player_id = $player_id";
    dbquery ($query);
}

// Newbie Protection.
// Newbies are players with less than USER_NOOB_LIMIT points.
// A newbie can only be attacked by players who have no more than five times as many, and no less than five times as many points.
// A Newbie can attack a stronger player (both Newbie and Non-Newbie) as long as the player has no more than five times as many points.

// Newbie protection. Check if the player is a newbie for the current player.
function IsPlayerNewbie ( int $player_id) : bool
{
    global $GlobalUser;
    $user = LoadUser ( $player_id);
    if ($user == null) return false;
    $week = time() - 604800;
    if ( $user['lastclick'] <= $week || $user['vacation'] || $user['banned']) return false;
    $p1 = $GlobalUser['score1'];
    $p2 = $user['score1'];

    if ($p2 >= $p1 || $p2 >= USER_NOOB_LIMIT) return false;
    if ($p1 <= $p2*5) return false;
    return true;
}

// Newbie protection. Check if the player for the current player is a strong player.
function IsPlayerStrong ( int $player_id) : bool
{
    global $GlobalUser;
    $user = LoadUser ( $player_id);
    if ($user == null) return false;
    $week = time() - 604800;
    if ( $user['lastclick'] <= $week || $user['vacation'] || $user['banned']) return false;
    $p1 = $GlobalUser['score1'];
    $p2 = $user['score1'];

    if ($p1 >= $p2 || $p1 >= USER_NOOB_LIMIT) return false;
    if ($p2 <= $p1*5) return false;
    return true;
}

// Get the status of the commander and the rest of the officers on the account.
function PremiumStatus (array $user) : array
{
    $prem = array ();
    $qcmd = array ( USER_OFFICER_COMMANDER => 'commander', USER_OFFICER_ADMIRAL => 'admiral', USER_OFFICER_ENGINEER => 'engineer', USER_OFFICER_GEOLOGE => 'geologist', USER_OFFICER_TECHNOCRATE => 'technocrat');

    $now = time ();

    foreach ($qcmd as $i=>$cmd)
    {
        $end = GetOfficerLeft ( $user, $i );
        if ($end <= $now) $d = 0;
        else $d = ($end - $now) / (60*60*24);
        $enabled = ( $d  > 0 );

        $prem[$cmd] = $enabled;
        $prem[$cmd.'_days'] = $d;
    }
    return $prem;
}

// Get the officer's end time. $off_type - see USER_OFFICER_xxx.
function GetOfficerLeft (array $user, int $off_type) : int
{
    $qtimers = array ( USER_OFFICER_COMMANDER => 'com_until', USER_OFFICER_ADMIRAL => 'adm_until', USER_OFFICER_ENGINEER => 'eng_until', USER_OFFICER_GEOLOGE => 'geo_until', USER_OFFICER_TECHNOCRATE => 'tec_until');
    if (key_exists($qtimers[$off_type], $user)) {
        return $user[$qtimers[$off_type]];
    }
    else return 0;
}

// Extend an officer for the specified number of seconds. If the number of seconds < 0 - remove the officer.
function RecruitOfficer ( int $player_id, int $off_type, int $seconds ) : void
{
    global $db_prefix, $GlobalUser;
    $qtimers = array ( USER_OFFICER_COMMANDER => 'com_until', USER_OFFICER_ADMIRAL => 'adm_until', USER_OFFICER_ENGINEER => 'eng_until', USER_OFFICER_GEOLOGE => 'geo_until', USER_OFFICER_TECHNOCRATE => 'tec_until');

    $until = 0;
    if ($seconds < 0) {
        $until = 0;
    }
    else {
        $user = LoadUser ( $player_id );
        if ($user == null) return;
        $now = time();
        $old_until = $user[$qtimers[$off_type]];
        if ($now >= $old_until) {
            $until = $now + $seconds;
        }
        else {
            $until = $old_until + $seconds;
        }
    }
    $query = "UPDATE ".$db_prefix."users SET ".$qtimers[$off_type]." = ".$until." WHERE player_id = $player_id";
    dbquery ($query);
    if ($player_id == $GlobalUser['player_id']) {
        $GlobalUser[$qtimers[$off_type]] = $until;
    }
}

// Called when you click on "Exit" in the menu.
function Logout ( string $session ) : void
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE session = '".$session."'";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) return;
    $user = dbarray ($result);
    $player_id = $user['player_id'];
    $unitab = LoadUniverse ();
    $uni = $unitab['num'];
    $query = "UPDATE ".$db_prefix."users SET session = '' WHERE player_id = $player_id";
    dbquery ($query);
    setcookie ( "prsess_".$player_id."_".$uni, '');
}

// Authenticate user. Called on every game page is loaded.
function AuthUser ( string $session ) : bool
{
    global $db_prefix, $GlobalUser, $loca_lang, $Languages, $GlobalUni, $DefaultLanguage;
    // Get the user ID and universe number from a public session.
    $query = "SELECT * FROM ".$db_prefix."users WHERE session = '".$session."'";
    $result = dbquery ($query);
    if (dbrows ($result) == 0) { RedirectHome(); return false; }
    $GlobalUser = dbarray ($result);
    $unitab = $GlobalUni;
    $uni = $unitab['num'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $cookie_name = 'prsess_'.$GlobalUser['player_id'].'_'.$uni;
    $prsess = "";

    // If admin forbid users to choose language in Settings, force to use Universe language
    if ($GlobalUni['force_lang']) {
        $GlobalUser['lang'] = $GlobalUni['lang'];
    }

    if (key_exists($cookie_name, $_COOKIE)) {
        $prsess = $_COOKIE [$cookie_name];
    }
    if ( $prsess !== $GlobalUser['private_session'] ) { InvalidSessionPage (); return false; }
    if ( !localhost($ip) && !$GlobalUser['deact_ip'] ) {
        if ( $ip !== $GlobalUser['ip_addr']) { InvalidSessionPage (); return false; }
    }

    // Set global language for session: user language -> universe language(if error) -> default language(if error)

    $loca_lang = $GlobalUser['lang'];
    if ( !key_exists ( $loca_lang, $Languages ) ) $loca_lang = $GlobalUni['lang'];
    if ( !key_exists ( $loca_lang, $Languages ) ) $loca_lang = $DefaultLanguage;

    return true;
}

// Login - Called from the home page, after registering or activating a new user.
function Login ( string $login, string $pass, string $passmd="" ) : never
{
    global $db_prefix, $db_secret;

    $unitab = LoadUniverse ();
    $uni = $unitab['num'];

    ob_start ();

    if  ( $player_id = CheckPassword ($login, $pass, $passmd ) )
    {
        // Is the user blocked?
        $user = LoadUser ( $player_id );
        if ($user['banned'])
        {
            UpdateLastClick ( $player_id );        // Update the user's activity so that you can extend the deletion.
            echo "<html><head><meta http-equiv='refresh' content='0;url=".hostname()."game/reg/errorpage.php?errorcode=3&arg1=$uni&arg2=$login&arg3=". fixed_date( "D M j Y G:i:s", $user['banned_until'] ) ."' /></head><body></body>";
            ob_end_flush ();
            exit ();
        }

        $lastlogin = time ();
        // Create a private session.
        $prsess = md5 ( $login . $lastlogin . $db_secret);
        // Create a public session
        $sess = substr (md5 ( $prsess . sha1 ($pass) . $db_secret . $lastlogin), 0, 12);

        // Write the private session to cookies and update the database.
        setcookie ( "prsess_".$player_id."_".$uni, $prsess, time()+24*60*60, "/" );
        $query = "UPDATE ".$db_prefix."users SET lastlogin = $lastlogin, session = '".$sess."', private_session = '".$prsess."' WHERE player_id = $player_id";
        dbquery ($query);

        // Write down the IP address.
        $ip = $_SERVER['REMOTE_ADDR'];
        $query = "UPDATE ".$db_prefix."users SET ip_addr = '".$ip."' WHERE player_id = $player_id";
        dbquery ($query);

        // Select the Home Planet as the current planet.
        $query = "SELECT * FROM ".$db_prefix."users WHERE session = '".$sess."'";
        $result = dbquery ($query);
        $user = dbarray ($result);
        SelectPlanet ($player_id, $user['hplanetid']);

        // Setting events for player unload, virtual DF cleanup, cleanup of destroyed planets, recalculation of alliance stats, and other global events
        AddReloginEvent ();
        AddCleanDebrisEvent ();
        AddCleanPlanetsEvent ();
        AddCleanPlayersEvent ();
        AddRecalcAllyPointsEvent ();

        // Player score recalculation task.
        AddUpdateStatsEvent ();
        AddRecalcPointsEvent ($player_id);

        // Redirect to Home Planet Overview.
        header ( "Location: ".hostname()."game/index.php?page=overview&session=".$sess."&lgn=1" );
        echo "<html><head><meta http-equiv='refresh' content='0;url=".hostname()."game/index.php?page=overview&session=".$sess."&lgn=1' /></head><body></body>";

        LogIPAddress ( $ip, $player_id );
    }
    else
    {
        header ( "Location: ".hostname()."game/reg/errorpage.php?errorcode=2&arg1=$uni&arg2=$login" );
        echo "<html><head><meta http-equiv='refresh' content='0;url=".hostname()."game/reg/errorpage.php?errorcode=2&arg1=$uni&arg2=$login' /></head><body></body>";
    }
    ob_end_flush ();
    exit ();
}

// Recalculation of stats.
function RecalcStats (int $player_id) : void
{
    global $db_prefix;
    $m = $k = $d = $e = 0;
    $points = $fpoints = $rpoints = 0;

    // Planets/moons + standing fleets
    $query = "SELECT * FROM ".$db_prefix."planets WHERE owner_id = '".$player_id."'";
    $result = dbquery ($query);
    $rows = dbrows ($result);
    while ($rows--) {
        $planet = dbarray ($result);
        if ( $planet['type'] >= PTYP_DF ) continue;        // only count planets and moons.
        $pp = PlanetPrice ($planet);
        $points += $pp['points'];
        $fpoints += $pp['fpoints'];
    }

    // Research
    global $resmap;
    $user = LoadUser ($player_id);
    if ( $user != null )
    {
        foreach ($resmap as $i=>$gid) {
            $level = $user["r$gid"];
            $rpoints += $level;
            if ($level > 0) {
                for ( $lv = 1; $lv<=$level; $lv ++ )
                {
                    $res = ResearchPrice ( $gid, $lv );
                    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
                    $points += ($m + $k + $d);
                }
            }
        }
    }

    // Flying fleets
    global $fleetmap;
    $result = EnumOwnFleetQueue ( $player_id, 1 );
    $rows = dbrows ($result);
    while ($rows--)
    {
        $queue = dbarray ( $result );
        $fleet = LoadFleet ( $queue['sub_id'] );

        foreach ( $fleetmap as $i=>$gid ) {        // Fleet
            $level = $fleet["ship$gid"];
            if ($level > 0){
                $res = ShipyardPrice ( $gid );
                $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
                $points += ($m + $k + $d) * $level;
                $fpoints += $level;
            }
        }
    
        if ( $fleet['ipm_amount'] > 0 ) {        // IPM
            $res = ShipyardPrice ( GID_D_IPM );
            $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
            $points += ($m + $k + $d) * $fleet['ipm_amount'];
        }
    }

    $query = "UPDATE ".$db_prefix."users SET ";
    $query .= "score1=$points, score2=$fpoints, score3=$rpoints WHERE player_id = $player_id AND (banned <> 1 OR admin > 0);";
    dbquery ($query);
}

function AdjustStats ( int $player_id, int $points, int $fpoints, int $rpoints, string $sign ) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."users SET ";
    $query .= "score1=score1 $sign '".$points."', score2=score2 $sign '".$fpoints."', score3=score3 $sign '".$rpoints."' WHERE player_id = $player_id AND banned = 0 AND admin = 0;";
    dbquery ($query);
}

// Recalculate the places of all players.
function RecalcRanks () : void
{
    global $db_prefix;

    // Special processing for admins
    $query = "UPDATE ".$db_prefix."users SET score1 = -1, score2 = -1, score3 = -1 WHERE admin > 0";
    dbquery ($query);

    // Points
    dbquery ("SET @pos := 0;");
    $query = "UPDATE ".$db_prefix."users
              SET place1 = (SELECT @pos := @pos+1)
              ORDER BY score1 DESC";
    dbquery ($query);

    // Fleet
    dbquery ("SET @pos := 0;");
    $query = "UPDATE ".$db_prefix."users
              SET place2 = (SELECT @pos := @pos+1)
              ORDER BY score2 DESC";
    dbquery ($query);

    // Research
    dbquery ("SET @pos := 0;");
    $query = "UPDATE ".$db_prefix."users
              SET place3 = (SELECT @pos := @pos+1)
              ORDER BY score3 DESC";
    dbquery ($query);

    // Special processing for admins
    $query = "UPDATE ".$db_prefix."users SET place1 = 0, place2 = 0, place3 = 0 WHERE admin > 0";
    dbquery ($query);
}

// Log out all the players
function UnloadAll () : void
{
    global $db_prefix, $StartPage;
    $query = "UPDATE ".$db_prefix."users SET session = ''";
    dbquery ($query);

    ob_clean ();
    echo "<script>document.location.href='".$StartPage."';</script>Вы долго отсутствовали 0. (Войдите снова)<br>";
    ob_end_flush ();
}

// Change skin path
function ChangeSkinPath (int $player_id, string $dpath) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."users SET skin = '".$dpath."' WHERE player_id = $player_id";
    dbquery ($query);
}

// Enable/disable skin display. When the skin is disabled, the default skin is displayed.
function EnableSkin (int $player_id, int $enable) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."users SET useskin = $enable WHERE player_id = $player_id";
    dbquery ($query);
}

// Get a list of operators in the universe
function EnumOperators () : mixed
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE admin = 1 ORDER BY player_id ASC;";
    return dbquery ($query);
}

// Resend the password and activation link.
function ReactivateUser (int $player_id) : void
{
    global $db_prefix, $db_secret;
    $user = LoadUser ($player_id);
    if ($user == null) return;

    $len = 8;
    $r = '';
    for($i=0; $i<$len; $i++)
        $r .= chr(rand(0, 25) + ord('a'));
    $pass = $r;
    $md = md5 ($pass . $db_secret);

    $name = $user['oname'];
    $email = $user['pemail'];
    $ack = md5(time ().$db_secret);

    $query = "UPDATE ".$db_prefix."users SET validatemd = '".$ack."', validated = 0, password = '".$md."' WHERE player_id = $player_id";
    dbquery ($query);
    if ( !localhost($_SERVER['REMOTE_ADDR']) ) SendGreetingsMail ( $name, $pass, $email, $ack);
}

// Clear the player cache.
function InvalidateUserCache () : void
{
    global $UserCache;
    $UserCache = array ();
}

// Return player's name with a link to the edit page and status (inactive, VM, etc).
function AdminUserName (array $user) : string
{
    global $session;

    $name = $user['oname'];

    $week = time() - 604800;
    $week4 = time() - 604800*4;

    $status = "";
    if ( $user['lastclick'] <= $week ) $status .= "i";
    if ( $user['lastclick'] <= $week4 ) $status .= "I";
    if ( $user['vacation'] ) $status .= "v";
    if ( $user['banned'] ) $status .= "b";
    if ( $user['noattack'] ) $status .= "А";
    if ( $user['disable'] ) $status .= "g";
    if ( $status !== "" ) $name .= " ($status)";

    if ( $user['disable'] ) $name = "<font color=orange>$name</font>";
    else if ( $user['banned'] ) $name = "<font color=red>$name</font>";
    else if ( $user['noattack'] ) $name = "<font color=yellow>$name</font>";
    else if ( $user['vacation'] ) $name = "<font color=skyBlue>$name</font>";
    else if ( $user['lastclick'] <= $week4 ) $name = "<font color=#999999>$name</font>";
    else if ( $user['lastclick'] <= $week ) $name = "<font color=#cccccc>$name</font>";

    $name = "<a href=\"index.php?page=admin&session=$session&mode=Users&player_id=".$user['player_id']."\">$name</a>";
    return $name;
}

// Ban the player.
function BanUser (int $player_id, int $seconds, bool $vmode) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_UNBAN."' AND owner_id = $player_id";
    dbquery ($query);
    $now = time ();
    $when = $now + $seconds;
    $id = AddQueue ($player_id, QTYP_UNBAN, 0, 0, 0, $now, $when, QUEUE_PRIO_LOWEST);
    $query = "UPDATE ".$db_prefix."users SET score1 = 0, score2 = 0, score3 = 0, banned = 1, banned_until = $when";
    if ( $vmode ) $query .= ", vacation = 1, vacation_until = $when";
    $query .= " WHERE player_id = $player_id";
    dbquery ($query);
    RecalcRanks ();
}

// Ban attacks.
function BanUserAttacks (int $player_id, int $seconds) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_ALLOW_ATTACKS."' AND owner_id = $player_id";
    dbquery ($query);
    $now = time ();
    $when = $now + $seconds;
    $id = AddQueue ($player_id, QTYP_ALLOW_ATTACKS, 0, 0, 0, $now, $when, QUEUE_PRIO_LOWEST);
    $query = "UPDATE ".$db_prefix."users SET noattack = 1, noattack_until = $when WHERE player_id = $player_id";
    dbquery ($query);
}

// Unban a player
function UnbanUser (int $player_id) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'UnbanPlayer' AND owner_id = $player_id";
    dbquery ($query);
    $query = "UPDATE ".$db_prefix."users SET banned = 0, banned_until = 0 WHERE player_id = $player_id";
    dbquery ($query);
    RecalcStats ($player_id);
    RecalcRanks ();
}

// Allow attacks
function UnbanUserAttacks (int $player_id) : void
{
    global $db_prefix;
    $query = "DELETE FROM ".$db_prefix."queue WHERE type = 'AllowAttacks' AND owner_id = $player_id";
    dbquery ($query);
    $query = "UPDATE ".$db_prefix."users SET noattack = 0, noattack_until = 0 WHERE player_id = $player_id";
    dbquery ($query);
}

// Set user flags
function SetUserFlags (int $player_id, int $flags) : void
{
    global $db_prefix;
    $query = "UPDATE ".$db_prefix."users SET flags = $flags WHERE player_id = $player_id";
    dbquery ($query);
}

// Get the number of players (administrators and operators do not count)
function GetUsersCount() : int
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users WHERE admin = 0;";
    $result = dbquery ($query);
    return dbrows ($result);
}

// Get a top1 player for expedition calculations.
function GetTop1 () : array|null
{
    global $db_prefix;
    $query = "SELECT * FROM ".$db_prefix."users ORDER BY score1 DESC LIMIT 1";
    $result = dbquery ($query);
    if ( dbrows($result) ) {
        return dbarray ($result);
    }
    return null;
}

function FeedActivate (bool $enable) : void
{
    global $GlobalUser, $db_prefix, $GlobalUni;
    $player_id = $GlobalUser['player_id'];
    $feedid = "";

    if ($GlobalUni['feedage'] < 0 && $enable) {
        return;             // Feed is prohibited by universe settings
    }
    if (($GlobalUser['flags'] & USER_FLAG_FEED_ENABLE) != 0 && $enable) {
        return;
    }
    if (($GlobalUser['flags'] & USER_FLAG_FEED_ENABLE) == 0 && !$enable) {
        return;
    }

    if ($enable) {
        $GlobalUser['flags'] |= USER_FLAG_FEED_ENABLE;
        $feedid = bin2hex(random_bytes(16));
    }
    else {
        $GlobalUser['flags'] &= ~USER_FLAG_FEED_ENABLE;
    }

    $query = "UPDATE ".$db_prefix."users SET flags = ".$GlobalUser['flags'].", lastfeed = 0, feedid = '".$feedid."' WHERE player_id = $player_id";
    dbquery ($query);
    $GlobalUser['feedid'] = $feedid;
}

?>