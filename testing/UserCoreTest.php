<?php

// Tests for game/core/user.php: user registration, authentication helpers,
// sessions, per-player settings, statistics, bans and vacation mode.
//
// They run against the real database layer with the in-memory SQLite backend
// (DB_CONNECTION=sqlite, DB_DATABASE=:memory:, see phpunit.xml and
// testing/bootstrap.php) on top of the three-player universe built by
// FixtureBuilder (real game schema, real AddDBRow/dbquery functions). No MySQL
// server and no DB mocks are needed.
//
// Functions that terminate the process (Login, the successful branch of
// ValidateUser) or that send real e-mail (SendGreetingsMail, SendChangeMail)
// cannot be exercised from a unit test; the tests below cover everything that
// is reachable and document those limits next to the affected function.
//
// Every test method runs in its own PHP process and starts with a fresh
// in-memory database.

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class UserCoreTest extends TestCase
{
    private FixtureBuilder $fixture;

    protected function setUp(): void
    {
        // CreatePlanet() (diameter/temperature) and ReactivateUser() (recovery
        // password) use the RNG: seed it so the runs are reproducible.
        mt_srand(20240517);
        srand(20240517);

        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');

        // loca_add() resolves the language files relative to the game dir.
        chdir(__DIR__ . '/../game');

        global $GlobalUser, $GlobalUni, $db_prefix, $db_secret, $Languages;
        global $StartPage, $session, $loca_lang, $DefaultLanguage, $from_cron;

        $GlobalUni = $this->fixture->getUniData();
        $GlobalUser = null;
        $db_prefix = $this->fixture->getDbPrefix();
        // game/config.php is generated at install time and is not loaded by the
        // PHPUnit bootstrap, so provide the values the module reads.
        $db_secret = 'testsecret';
        $StartPage = 'index.php';
        $session = 'testsession';
        $from_cron = false;
        if (!isset($Languages)) $Languages = array ('en' => 'English', 'ru' => 'Русский');
        if (!isset($DefaultLanguage)) $DefaultLanguage = 'en';
        $loca_lang = 'en';

        // The web-server environment the module reads; the CLI has none.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/game/index.php';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        $_GET = array ();
        $_POST = array ();
        $_COOKIE = array ();
    }

    // ------------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------------

    private function prefix() : string
    {
        return $this->fixture->getDbPrefix();
    }

    private function fetchRow(string $sql) : array|false
    {
        return dbarray (dbquery ($sql));
    }

    private function fetchRows(string $sql) : array
    {
        $rows = array ();
        $result = dbquery ($sql);
        while ($row = dbarray ($result)) $rows[] = $row;
        return $rows;
    }

    private function fetchValue(string $sql) : mixed
    {
        $row = $this->fetchRow ($sql);
        if ($row === false) return null;
        return array_values ($row)[0];
    }

    private function countRows(string $sql) : int
    {
        return dbrows (dbquery ($sql));
    }

    // Insert a user row directly (bypassing CreateUser) and return its id.
    private function insertUser(array $overrides = array ()) : int
    {
        $row = array (
            'name' => 'testuser', 'oname' => 'TestUser', 'password' => '', 'temp_pass' => '',
            'email' => 'testuser@test.com', 'pemail' => 'testuser@test.com',
            'validated' => 1, 'validatemd' => '', 'admin' => 0, 'lang' => 'en',
            'lastlogin' => 0, 'lastclick' => time (), 'ip_addr' => '127.0.0.1',
            'vacation' => 0, 'vacation_until' => 0, 'banned' => 0, 'banned_until' => 0,
            'noattack' => 0, 'noattack_until' => 0, 'disable' => 0, 'disable_until' => 0,
            'deact_ip' => 0, 'session' => '', 'private_session' => '',
            'score1' => 0, 'score2' => 0, 'score3' => 0,
            'place1' => 0, 'place2' => 0, 'place3' => 0,
            'dm' => 0, 'dmfree' => 0, 'flags' => USER_FLAG_DEFAULT,
            'hplanetid' => 0, 'aktplanet' => 0, 'feedid' => '', 'lastfeed' => 0,
            'com_until' => 0, 'adm_until' => 0, 'eng_until' => 0, 'geo_until' => 0, 'tec_until' => 0,
            'sortby' => 0, 'sortorder' => 0, 'skin' => '', 'useskin' => 0,
            'maxspy' => 1, 'maxfleetmsg' => 3, 'sniff' => 0, 'debug' => 0, 'trader' => 0,
            'rate_m' => 0, 'rate_k' => 0, 'rate_d' => 0, 'scoredate' => 0,
        );
        return AddDBRow (array_merge ($row, $overrides), 'users');
    }

    private function userRow(int $playerId) : array|false
    {
        return $this->fetchRow ("SELECT * FROM " . $this->prefix() . "users WHERE player_id = $playerId");
    }

    private function planetRow(int $planetId) : array|false
    {
        return $this->fetchRow ("SELECT * FROM " . $this->prefix() . "planets WHERE planet_id = $planetId");
    }

    private function setUserScore(int $playerId, int $score1, int $score2 = 0, int $score3 = 0) : void
    {
        dbquery ("UPDATE " . $this->prefix() . "users SET score1 = $score1, score2 = $score2, score3 = $score3 WHERE player_id = $playerId");
        InvalidateUserCache ();
    }

    // ------------------------------------------------------------------------
    // fixed_date
    // ------------------------------------------------------------------------

    public function testFixedDateFormatsUnixTimestampsInUtc() : void
    {
        // fixed_date() uses DateTime('@ts'), which is always UTC, so the
        // expected strings do not depend on the machine timezone.
        $this->assertSame ('1970-01-01 00:00:00', fixed_date ('Y-m-d H:i:s', 0));
        $this->assertSame ('1970-01-02', fixed_date ('Y-m-d', 86400));
        $this->assertSame ('1969-12-31 23:59:59', fixed_date ('Y-m-d H:i:s', -1));
        $this->assertSame ('Thu, 01 Jan 1970 00:00:00 +0000', fixed_date ('r', 0));
    }

    // ------------------------------------------------------------------------
    // IsUserExist / IsEmailExist
    // ------------------------------------------------------------------------

    public function testIsUserExistMatchesTheLowerCasedName() : void
    {
        $this->insertUser (array ('name' => 'someplayer', 'oname' => 'SomePlayer'));

        $this->assertTrue (IsUserExist ('someplayer'));
        // The lookup name is lower-cased first (registration stores lower case).
        $this->assertTrue (IsUserExist ('SOMEPlayer'));
        $this->assertFalse (IsUserExist ('nosuchplayer'));
    }

    public function testIsEmailExistChecksBothEmailColumnsAndExcludesTheGivenName() : void
    {
        $this->insertUser (array (
            'name' => 'mailuser', 'oname' => 'MailUser',
            'email' => 'temp@test.com', 'pemail' => 'perm@test.com',
        ));

        $this->assertTrue (IsEmailExist ('temp@test.com'));         // temporary address
        $this->assertTrue (IsEmailExist ('perm@test.com'));         // permanent address
        $this->assertTrue (IsEmailExist ('TEMP@TEST.COM'));         // lower-cased before the lookup
        $this->assertFalse (IsEmailExist ('other@test.com'));
        // The player's own name is excluded, so their own address is "free".
        $this->assertFalse (IsEmailExist ('temp@test.com', 'mailuser'));
        $this->assertTrue (IsEmailExist ('temp@test.com', 'someoneelse'));
    }

    // ------------------------------------------------------------------------
    // CreateUser
    // ------------------------------------------------------------------------

    public function testCreateUserAsBotStoresAccountHomePlanetAndBookkeeping() : void
    {
        $t0 = time ();
        $id = CreateUser ('NewPlayer', 'secret', 'New@Example.COM', true);
        $t1 = time ();

        // The three fixture players occupy the ids 1..3.
        $this->assertGreaterThan (3, $id);

        $user = $this->userRow ($id);
        $this->assertIsArray ($user);
        $this->assertSame ('newplayer', $user['name']);             // stored lower case
        $this->assertSame ('NewPlayer', $user['oname']);            // original spelling kept
        $this->assertSame ('new@example.com', $user['email']);
        $this->assertSame ('new@example.com', $user['pemail']);
        $this->assertSame (md5 ('secret' . 'testsecret'), $user['password']);
        $this->assertSame (0, (int)$user['validated']);
        $this->assertMatchesRegularExpression ('/^[a-f0-9]{32}$/', $user['validatemd']);
        $this->assertSame (USER_TYPE_PLAYER, (int)$user['admin']);
        $this->assertSame ('en', $user['lang']);                    // universe language
        $this->assertSame (USER_FLAG_DEFAULT, (int)$user['flags']);
        $this->assertSame (0, (int)$user['dm']);
        $this->assertSame (0, (int)$user['dmfree']);                // universe start_dm
        $this->assertSame ('', $user['session']);
        $this->assertSame ('127.0.0.1', $user['ip_addr']);
        $this->assertGreaterThanOrEqual ($t0, (int)$user['regdate']);
        $this->assertLessThanOrEqual ($t1, (int)$user['regdate']);

        // A home planet was created and selected as the current planet.
        $planetId = (int)$user['hplanetid'];
        $this->assertGreaterThan (0, $planetId);
        $this->assertSame ($planetId, (int)$user['aktplanet']);
        $planet = $this->planetRow ($planetId);
        $this->assertIsArray ($planet);
        $this->assertSame ($id, (int)$planet['owner_id']);
        $this->assertSame (PTYP_PLANET, (int)$planet['type']);
        $this->assertSame (500, (int)$planet[GID_RC_METAL]);        // home planet start resources
        $this->assertSame (500, (int)$planet[GID_RC_CRYSTAL]);

        // Universe counter, the 3-day activation time limit and the IP log.
        $this->assertSame (4, (int)$this->fetchValue ("SELECT usercount FROM " . $this->prefix() . "uni"));
        $this->assertSame ('259200', (string)$this->fetchValue (
            "SELECT value FROM " . $this->prefix() . "botvars WHERE owner_id = $id AND var = 'TimeLimit'"));
        $this->assertSame (1, $this->countRows (
            "SELECT * FROM " . $this->prefix() . "iplogs WHERE user_id = $id AND reg = 1"));

        // A bot registration sends neither the welcome e-mail nor the greeting.
        $this->assertSame (0, $this->countRows ("SELECT * FROM " . $this->prefix() . "messages WHERE owner_id = $id"));
    }

    public function testCreateUserSendsTheInGameGreetingForLocalRegistrations() : void
    {
        // REMOTE_ADDR is 127.0.0.1 (see setUp), so the welcome e-mail is skipped
        // (there is no MTA in the test environment) while the in-game greeting
        // message is still created.
        $id = CreateUser ('GreetedOne', 'pw', 'greet@test.com', false);

        $this->assertSame (1, $this->countRows ("SELECT * FROM " . $this->prefix() . "messages WHERE owner_id = $id"));
        $msg = $this->fetchRow ("SELECT * FROM " . $this->prefix() . "messages WHERE owner_id = $id");
        $this->assertSame (MTYP_MISC, (int)$msg['pm']);
        $this->assertSame (loca_lang ('FLEET_MESSAGE_FROM', 'en'), $msg['msgfrom']);
    }

    public function testCreateUserLanguageComesFromTheCookieUnlessTheUniverseForcesIt() : void
    {
        // The cookie language is only honoured for non-bot registrations; the
        // requests come from 127.0.0.1, so no e-mail is sent (see setUp).
        $_COOKIE['ogamelang'] = 'ru';
        $id = CreateUser ('LangUser', 'pw', 'lang@test.com', false);
        $this->assertSame ('ru', $this->userRow ($id)['lang']);

        // force_lang makes the universe language win over the cookie.
        dbquery ("UPDATE " . $this->prefix() . "uni SET force_lang = 1");
        $id2 = CreateUser ('LangUser2', 'pw', 'lang2@test.com', false);
        $this->assertSame ('en', $this->userRow ($id2)['lang']);

        // An unsupported language falls back to the universe language.
        dbquery ("UPDATE " . $this->prefix() . "uni SET force_lang = 0");
        $_COOKIE['ogamelang'] = 'xx';
        $id3 = CreateUser ('LangUser3', 'pw', 'lang3@test.com', false);
        $this->assertSame ('en', $this->userRow ($id3)['lang']);
    }

    // ------------------------------------------------------------------------
    // CheckPassword / ValidateUser
    // ------------------------------------------------------------------------

    public function testCheckPasswordAcceptsTheCorrectHashAndRejectsWrongCredentials() : void
    {
        $id = $this->insertUser (array ('name' => 'checkuser', 'password' => md5 ('pw123' . 'testsecret')));

        $this->assertSame ($id, CheckPassword ('checkuser', 'pw123'));
        // The login name is lower-cased before the lookup.
        $this->assertSame ($id, CheckPassword ('CheckUser', 'pw123'));
        $this->assertSame (0, CheckPassword ('checkuser', 'wrong'));
        $this->assertSame (0, CheckPassword ('nosuchuser', 'pw123'));
        // A pre-computed hash is used as-is (the plain password is ignored).
        $this->assertSame ($id, CheckPassword ('checkuser', '', md5 ('pw123' . 'testsecret')));
        $this->assertSame (0, CheckPassword ('checkuser', '', md5 ('other' . 'testsecret')));
    }

    public function testCheckPasswordRejectsImplausibleNamesBeforeQuerying() : void
    {
        $pw = md5 ('pw' . 'testsecret');

        // Too short (< 3 characters) and too long (> 20 characters).
        $this->assertSame (0, CheckPassword ('ab', 'pw'));
        $this->assertSame (0, CheckPassword (str_repeat ('a', 21), 'pw'));

        // Characters registration forbids never reach the SQL query.
        $forbidden = array ('ab;cd', 'ab,cd', 'ab<cd', 'ab>cd', 'ab(cd', 'ab)cd', 'ab`cd', 'ab"cd', "ab'cd", 'ab\\cd');
        foreach ($forbidden as $name) {
            $this->assertSame (0, CheckPassword ($name, 'pw'), "Name [$name] must be rejected");
        }

        // The length boundaries themselves are accepted.
        $threeId = $this->insertUser (array ('name' => 'abc', 'password' => $pw));
        $this->assertSame ($threeId, CheckPassword ('abc', 'pw'));

        $twenty = str_repeat ('b', 20);
        $twentyId = $this->insertUser (array ('name' => $twenty, 'password' => $pw));
        $this->assertSame ($twentyId, CheckPassword ($twenty, 'pw'));
    }

    public function testValidateUserRejectsMalformedAndUnknownCodes() : void
    {
        $id = $this->insertUser (array (
            'name' => 'pendinguser', 'oname' => 'PendingUser',
            'validated' => 0, 'validatemd' => str_repeat ('a', 32),
        ));

        // A code that is not a 32-character hex string is rejected outright.
        ob_start ();
        ValidateUser ('not-a-code');
        $out = ob_get_clean ();
        $this->assertStringContainsString ('url=index.php', $out);   // RedirectHome()
        $this->assertSame (0, (int)$this->userRow ($id)['validated']);
        $this->assertSame (str_repeat ('a', 32), $this->userRow ($id)['validatemd']);

        // A well-formed but unknown code behaves the same.
        ob_start ();
        ValidateUser (str_repeat ('f', 32));
        $out = ob_get_clean ();
        $this->assertStringContainsString ('url=index.php', $out);
        $this->assertSame (0, (int)$this->userRow ($id)['validated']);

        // NOTE: the success path ends in Login(), which always redirects and
        // calls exit(); it cannot be exercised inside a PHPUnit process.
    }

    // ------------------------------------------------------------------------
    // LoadUser / InvalidateUserCache / UpdateLastClick / GetSelectedPlanet
    // ------------------------------------------------------------------------

    public function testLoadUserReturnsTheRowWithTheCombinedDarkMatter() : void
    {
        InvalidateUserCache ();

        $user = LoadUser (1);

        $this->assertIsArray ($user);
        $this->assertSame (1, (int)$user['player_id']);
        $this->assertSame ('PlayerOne', $user['oname']);
        $this->assertSame (1000, (int)$user['dm']);
        $this->assertSame (500, (int)$user['dmfree']);
        // The sum is exposed under the DM resource id (704).
        $this->assertSame (1500, (int)$user[GID_RC_DM]);
    }

    public function testLoadUserReturnsNullForAnUnknownPlayer() : void
    {
        $this->assertNull (LoadUser (123456));
    }

    public function testLoadUserCachesUntilTheCacheIsInvalidated() : void
    {
        InvalidateUserCache ();
        $first = LoadUser (1);
        $this->assertSame (1000, (int)$first['dm']);

        dbquery ("UPDATE " . $this->prefix() . "users SET dm = 7777 WHERE player_id = 1");

        // The script-lifetime cache still serves the old row ...
        $this->assertSame (1000, (int)LoadUser (1)['dm']);
        $this->assertSame (1000, (int)LoadUser (1)['dm']);
        // ... until it is invalidated.
        InvalidateUserCache ();
        $this->assertSame (7777, (int)LoadUser (1)['dm']);
    }

    public function testInvalidateUserCacheEmptiesTheScriptCache() : void
    {
        global $UserCache;

        InvalidateUserCache ();
        LoadUser (1);
        $this->assertArrayHasKey (1, $UserCache);

        InvalidateUserCache ();
        $this->assertSame (array (), $UserCache);
    }

    public function testUpdateLastClickStoresTheCurrentTimeForThatPlayerOnly() : void
    {
        $otherBefore = (int)$this->fetchValue ("SELECT lastclick FROM " . $this->prefix() . "users WHERE player_id = 2");

        $t0 = time ();
        UpdateLastClick (1);
        $t1 = time ();

        $lastclick = (int)$this->fetchValue ("SELECT lastclick FROM " . $this->prefix() . "users WHERE player_id = 1");
        $this->assertGreaterThanOrEqual ($t0, $lastclick);
        $this->assertLessThanOrEqual ($t1, $lastclick);
        $this->assertLessThan ($t0, $otherBefore);
        $this->assertSame ($otherBefore, (int)$this->fetchValue ("SELECT lastclick FROM " . $this->prefix() . "users WHERE player_id = 2"));
    }

    public function testGetSelectedPlanetReturnsAktplanetOrZero() : void
    {
        $user = $this->userRow (1);
        $this->assertSame ((int)$user['aktplanet'], GetSelectedPlanet (1));
        $this->assertSame (0, GetSelectedPlanet (123456));
    }

    // ------------------------------------------------------------------------
    // Newbie protection: IsPlayerNewbie / IsPlayerStrong
    // ------------------------------------------------------------------------

    public function testIsPlayerNewbieAppliesThePointRatioRules() : void
    {
        global $GlobalUser;
        $GlobalUser = array ('player_id' => 1, 'score1' => 50000);

        $target = $this->insertUser (array ('name' => 'newbietarget', 'score1' => 1000, 'lastclick' => time ()));

        $this->assertTrue (IsPlayerNewbie ($target));                       // 50000 > 1000*5

        // USER_NOOB_LIMIT points or more: no longer a newbie.
        $this->setUserScore ($target, USER_NOOB_LIMIT);
        $this->assertFalse (IsPlayerNewbie ($target));

        // More points than the current player: not a newbie.
        $this->setUserScore ($target, 60000);
        $this->assertFalse (IsPlayerNewbie ($target));

        // The current player must have more than five times as many points.
        $GlobalUser['score1'] = 4000;
        $this->setUserScore ($target, 1000);
        $this->assertFalse (IsPlayerNewbie ($target));                      // 4000 <= 1000*5

        $GlobalUser['score1'] = 5001;
        $this->assertTrue (IsPlayerNewbie ($target));                       // 5001 > 5000

        $this->assertFalse (IsPlayerNewbie (123456));                       // unknown player
    }

    public function testIsPlayerNewbieIgnoresInactiveVacationAndBannedPlayers() : void
    {
        global $GlobalUser;
        $GlobalUser = array ('player_id' => 1, 'score1' => 50000);

        $target = $this->insertUser (array (
            'name' => 'idletarget', 'score1' => 1000,
            'lastclick' => time () - 604801,                                // more than a week
        ));
        $this->assertFalse (IsPlayerNewbie ($target));

        dbquery ("UPDATE " . $this->prefix() . "users SET lastclick = " . time () . ", vacation = 1 WHERE player_id = $target");
        InvalidateUserCache ();
        $this->assertFalse (IsPlayerNewbie ($target));

        dbquery ("UPDATE " . $this->prefix() . "users SET vacation = 0, banned = 1 WHERE player_id = $target");
        InvalidateUserCache ();
        $this->assertFalse (IsPlayerNewbie ($target));

        dbquery ("UPDATE " . $this->prefix() . "users SET banned = 0 WHERE player_id = $target");
        InvalidateUserCache ();
        $this->assertTrue (IsPlayerNewbie ($target));
    }

    public function testIsPlayerStrongAppliesTheInversePointRatioRules() : void
    {
        global $GlobalUser;
        $GlobalUser = array ('player_id' => 1, 'score1' => 100);

        $target = $this->insertUser (array ('name' => 'strongtarget', 'score1' => 1000, 'lastclick' => time ()));
        $this->assertTrue (IsPlayerStrong ($target));                       // 1000 > 100*5

        // Equal points: nobody is "strong".
        $GlobalUser['score1'] = 1000;
        $this->assertFalse (IsPlayerStrong ($target));

        // The current player is out of newbie protection (>= USER_NOOB_LIMIT).
        $GlobalUser['score1'] = USER_NOOB_LIMIT;
        $this->assertFalse (IsPlayerStrong ($target));

        // Five times as many points is the boundary, not "strong".
        $GlobalUser['score1'] = 100;
        $this->setUserScore ($target, 500);
        $this->assertFalse (IsPlayerStrong ($target));                      // 500 <= 100*5
        $this->setUserScore ($target, 501);
        $this->assertTrue (IsPlayerStrong ($target));

        // Inactive, vacation and banned players are never "strong".
        dbquery ("UPDATE " . $this->prefix() . "users SET lastclick = " . (time () - 604801) . " WHERE player_id = $target");
        InvalidateUserCache ();
        $this->assertFalse (IsPlayerStrong ($target));

        $this->assertFalse (IsPlayerStrong (123456));                       // unknown player
    }

    // ------------------------------------------------------------------------
    // Officers: GetOfficerLeft / PremiumStatus / RecruitOfficer
    // ------------------------------------------------------------------------

    public function testGetOfficerLeftReturnsTheStoredExpiryPerOfficerType() : void
    {
        $user = array (
            'com_until' => 100, 'adm_until' => 200, 'eng_until' => 300,
            'geo_until' => 400, 'tec_until' => 500,
        );

        $this->assertSame (100, GetOfficerLeft ($user, USER_OFFICER_COMMANDER));
        $this->assertSame (200, GetOfficerLeft ($user, USER_OFFICER_ADMIRAL));
        $this->assertSame (300, GetOfficerLeft ($user, USER_OFFICER_ENGINEER));
        $this->assertSame (400, GetOfficerLeft ($user, USER_OFFICER_GEOLOGE));
        $this->assertSame (500, GetOfficerLeft ($user, USER_OFFICER_TECHNOCRATE));

        // A record without the column yields 0 instead of failing.
        $this->assertSame (0, GetOfficerLeft (array (), USER_OFFICER_COMMANDER));
    }

    public function testPremiumStatusReportsEnabledOfficersAndRemainingDays() : void
    {
        $now = time ();
        $user = array (
            'com_until' => $now + 2 * 86400,        // enabled, ~2 days left
            'adm_until' => $now - 1,                // expired
            'eng_until' => 0,                       // never recruited
            'geo_until' => $now + 365 * 86400,      // enabled, ~365 days left
            'tec_until' => $now,                    // expires precisely now
        );

        $prem = PremiumStatus ($user);

        $this->assertCount (10, $prem);
        $this->assertTrue ($prem['commander']);
        $this->assertEqualsWithDelta (2.0, $prem['commander_days'], 0.01);
        $this->assertFalse ($prem['admiral']);
        $this->assertSame (0, $prem['admiral_days']);
        $this->assertFalse ($prem['engineer']);
        $this->assertSame (0, $prem['engineer_days']);
        $this->assertTrue ($prem['geologist']);
        $this->assertEqualsWithDelta (365.0, $prem['geologist_days'], 0.01);
        $this->assertFalse ($prem['technocrat']);
        $this->assertSame (0, $prem['technocrat_days']);
    }

    public function testRecruitOfficerStartsAndThenExtendsTheExpiry() : void
    {
        global $GlobalUser;

        // A fresh officer runs from now for the requested number of seconds.
        dbquery ("UPDATE " . $this->prefix() . "users SET com_until = 0 WHERE player_id = 1");
        InvalidateUserCache ();
        $GlobalUser = LoadUser (1);

        $t0 = time ();
        RecruitOfficer (1, USER_OFFICER_COMMANDER, 3600);
        $until = (int)$this->fetchValue ("SELECT com_until FROM " . $this->prefix() . "users WHERE player_id = 1");
        $this->assertGreaterThanOrEqual ($t0 + 3600, $until);
        $this->assertLessThanOrEqual (time () + 3600, $until);
        // The in-memory record of the current player is kept in sync.
        $this->assertSame ($until, (int)$GlobalUser['com_until']);

        // An active officer is extended, not reset (fresh read of the row).
        InvalidateUserCache ();
        RecruitOfficer (1, USER_OFFICER_COMMANDER, 100);
        $this->assertSame ($until + 100, (int)$this->fetchValue ("SELECT com_until FROM " . $this->prefix() . "users WHERE player_id = 1"));
        $this->assertSame ($until + 100, (int)$GlobalUser['com_until']);

        // A negative duration removes the officer.
        InvalidateUserCache ();
        RecruitOfficer (1, USER_OFFICER_COMMANDER, -1);
        $this->assertSame (0, (int)$this->fetchValue ("SELECT com_until FROM " . $this->prefix() . "users WHERE player_id = 1"));
        $this->assertSame (0, (int)$GlobalUser['com_until']);
    }

    public function testRecruitOfficerReadsAStaleUserCacheWithinTheSameRequest() : void
    {
        global $GlobalUser;

        // LoadUser() caches the row, and RecruitOfficer() updates the database
        // and $GlobalUser but not $UserCache. A second call in the same request
        // therefore extends from the *cached* (pre-first-call) expiry, which can
        // shorten an officer instead of extending it.
        dbquery ("UPDATE " . $this->prefix() . "users SET com_until = 0 WHERE player_id = 1");
        InvalidateUserCache ();
        $GlobalUser = LoadUser (1);

        RecruitOfficer (1, USER_OFFICER_COMMANDER, 3600);
        $first = (int)$this->fetchValue ("SELECT com_until FROM " . $this->prefix() . "users WHERE player_id = 1");

        $t1 = time ();
        RecruitOfficer (1, USER_OFFICER_COMMANDER, 100);
        $t2 = time ();
        $second = (int)$this->fetchValue ("SELECT com_until FROM " . $this->prefix() . "users WHERE player_id = 1");

        // Current behaviour: the 100 seconds are added to the cached 0, so the
        // stored expiry moves *backwards* (instead of first + 100).
        $this->assertLessThan ($first, $second);
        $this->assertGreaterThanOrEqual ($t1 + 100, $second);
        $this->assertLessThanOrEqual ($t2 + 100, $second);
    }

    public function testRecruitOfficerDoesNotTouchOtherPlayersGlobals() : void
    {
        global $GlobalUser;
        InvalidateUserCache ();
        $GlobalUser = LoadUser (1);
        $before = (int)$GlobalUser['com_until'];

        // Player 2 already has a long-running commander, so the seconds are added.
        $old = (int)$this->fetchValue ("SELECT com_until FROM " . $this->prefix() . "users WHERE player_id = 2");
        RecruitOfficer (2, USER_OFFICER_COMMANDER, 50);

        $this->assertSame ($old + 50, (int)$this->fetchValue ("SELECT com_until FROM " . $this->prefix() . "users WHERE player_id = 2"));
        $this->assertSame ($before, (int)$GlobalUser['com_until']);

        // An unknown player is silently ignored.
        RecruitOfficer (123456, USER_OFFICER_COMMANDER, 60);
        $this->assertSame (0, $this->countRows ("SELECT * FROM " . $this->prefix() . "users WHERE player_id = 123456"));
    }

    // ------------------------------------------------------------------------
    // Sessions: Logout / AuthUser
    // ------------------------------------------------------------------------

    public function testLogoutIgnoresNullOrMalformedSessions() : void
    {
        dbquery ("UPDATE " . $this->prefix() . "users SET session = 'abc123abc123' WHERE player_id = 1");

        Logout (null);
        Logout ('');
        Logout ('not a session');
        Logout (str_repeat ('a', 13));      // longer than the 12-char session token
        Logout ('ABCDEF123456');            // upper case is rejected by the guard regex

        $this->assertSame ('abc123abc123', $this->fetchValue ("SELECT session FROM " . $this->prefix() . "users WHERE player_id = 1"));
    }

    public function testLogoutClearsTheMatchingPublicSession() : void
    {
        dbquery ("UPDATE " . $this->prefix() . "users SET session = 'abc123abc123' WHERE player_id = 1");
        dbquery ("UPDATE " . $this->prefix() . "users SET session = 'def456def456' WHERE player_id = 2");

        ob_start ();                        // Logout() calls setcookie(): keep the headers unsent
        Logout ('abc123abc123');
        ob_end_clean ();

        $this->assertSame ('', $this->fetchValue ("SELECT session FROM " . $this->prefix() . "users WHERE player_id = 1"));
        // Another player's session is untouched.
        $this->assertSame ('def456def456', $this->fetchValue ("SELECT session FROM " . $this->prefix() . "users WHERE player_id = 2"));

        // A well-formed but unknown session changes nothing.
        Logout ('ffffffffffff');
        $this->assertSame ('def456def456', $this->fetchValue ("SELECT session FROM " . $this->prefix() . "users WHERE player_id = 2"));
    }

    public function testAuthUserRejectsEmptyAndUnknownSessions() : void
    {
        global $GlobalUser;

        $GlobalUser = null;
        ob_start ();
        $this->assertFalse (AuthUser (''));
        $out = ob_get_clean ();
        $this->assertStringContainsString ('url=index.php', $out);      // RedirectHome()

        $GlobalUser = null;
        ob_start ();
        $this->assertFalse (AuthUser ('ffffffffffff'));
        $out = ob_get_clean ();
        $this->assertStringContainsString ('url=index.php', $out);
    }

    public function testAuthUserAcceptsAValidSessionWithCookieAndIp() : void
    {
        global $GlobalUser, $GlobalUni, $loca_lang;

        dbquery ("UPDATE " . $this->prefix() . "users SET session = 'abcdef123456', private_session = 'privatesession',"
            . " ip_addr = '10.0.0.5', deact_ip = 0, lang = 'en' WHERE player_id = 1");
        $_COOKIE['prsess_1_1'] = 'privatesession';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.5';

        $this->assertTrue (AuthUser ('abcdef123456'));

        $this->assertSame (1, (int)$GlobalUser['player_id']);
        $this->assertSame ('PlayerOne', $GlobalUser['oname']);
        $this->assertSame (1500, (int)$GlobalUser[GID_RC_DM]);          // dm + dmfree
        $this->assertSame ('en', $loca_lang);
        $this->assertSame (0, (int)$GlobalUni['force_lang']);
    }

    public function testAuthUserRejectsAWrongPrivateSessionAndAWrongIp() : void
    {
        global $GlobalUser;

        dbquery ("UPDATE " . $this->prefix() . "users SET session = 'abcdef123456', private_session = 'privatesession',"
            . " ip_addr = '10.0.0.5', deact_ip = 0, lang = 'en' WHERE player_id = 1");
        $_SERVER['REMOTE_ADDR'] = '10.0.0.5';

        // Wrong private cookie: the invalid-session page is rendered and an
        // error row is logged.
        $_COOKIE['prsess_1_1'] = 'wrong';
        $errors = $this->countRows ("SELECT * FROM " . $this->prefix() . "errors");
        ob_start ();
        $this->assertFalse (AuthUser ('abcdef123456'));
        $out = ob_get_clean ();
        $this->assertStringContainsString ('Error-ID:', $out);
        $this->assertSame ($errors + 1, $this->countRows ("SELECT * FROM " . $this->prefix() . "errors"));

        // Correct cookie, different IP: rejected as well.
        $_COOKIE['prsess_1_1'] = 'privatesession';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.6';
        ob_start ();
        $this->assertFalse (AuthUser ('abcdef123456'));
        ob_end_clean ();

        // deact_ip disables the IP verification.
        dbquery ("UPDATE " . $this->prefix() . "users SET deact_ip = 1 WHERE player_id = 1");
        $GlobalUser = null;
        $this->assertTrue (AuthUser ('abcdef123456'));
    }

    public function testAuthUserFallsBackToTheUniverseLanguage() : void
    {
        global $GlobalUser, $GlobalUni, $loca_lang;

        dbquery ("UPDATE " . $this->prefix() . "users SET session = 'abcdef123456', private_session = 'priv',"
            . " ip_addr = '127.0.0.1', deact_ip = 0, lang = 'xx' WHERE player_id = 1");
        $_COOKIE['prsess_1_1'] = 'priv';

        // Unknown user language -> universe language.
        $this->assertTrue (AuthUser ('abcdef123456'));
        $this->assertSame ('en', $loca_lang);

        // force_lang overwrites the user's language with the universe one.
        $GlobalUni['force_lang'] = 1;
        $GlobalUni['lang'] = 'ru';
        $GlobalUser = null;
        $this->assertTrue (AuthUser ('abcdef123456'));
        $this->assertSame ('ru', $GlobalUser['lang']);
        $this->assertSame ('ru', $loca_lang);
    }

    // ------------------------------------------------------------------------
    // Statistics: RecalcStats / AdjustStats / RecalcRanks
    // ------------------------------------------------------------------------

    public function testRecalcStatsSumsPlanetsResearchAndFleets() : void
    {
        global $resmap;

        // The fixture rows have no explicit flag values, and the game schema
        // does not default `banned`/`admin`, so set them like CreateUser does:
        // the UPDATE in RecalcStats() only matches banned = 0 (or admins).
        dbquery ("UPDATE " . $this->prefix() . "users SET banned = 0, admin = 0 WHERE player_id = 1");
        InvalidateUserCache ();
        RecalcStats (1);

        $user = $this->userRow (1);
        $this->assertIsArray ($user);

        // score3 is the number of research levels across all techs.
        $researchLevels = 0;
        foreach ($resmap as $gid) $researchLevels += (int)$user[$gid];
        $this->assertGreaterThan (0, $researchLevels);
        $this->assertSame ($researchLevels, (int)$user['score3']);

        // Buildings/planets/research/fleets and standing+flying ships.
        $this->assertGreaterThan (0, (int)$user['score1']);
        $this->assertGreaterThan (0, (int)$user['score2']);
        $this->assertGreaterThanOrEqual ((int)$user['score3'], (int)$user['score1']);
    }

    public function testRecalcStatsLeavesBannedPlayersUntouched() : void
    {
        dbquery ("UPDATE " . $this->prefix() . "users SET banned = 1, score1 = 111, score2 = 222, score3 = 333 WHERE player_id = 2");
        InvalidateUserCache ();

        RecalcStats (2);

        $user = $this->userRow (2);
        $this->assertSame (111, (int)$user['score1']);
        $this->assertSame (222, (int)$user['score2']);
        $this->assertSame (333, (int)$user['score3']);
    }

    public function testRecalcStatsSkipsPlayersWithoutAnExplicitBanFlag() : void
    {
        // The fixture users leave `banned`/`admin` unset, i.e. NULL in SQLite.
        // RecalcStats() guards with "banned <> 1 OR admin > 0", which is NULL
        // (not true) for such a row, so nothing is written. Current behaviour.
        $this->assertNull ($this->fetchValue ("SELECT banned FROM " . $this->prefix() . "users WHERE player_id = 1"));

        RecalcStats (1);

        $user = $this->userRow (1);
        $this->assertSame (50000, (int)$user['score1']);
        $this->assertSame (30000, (int)$user['score2']);
        $this->assertSame (20000, (int)$user['score3']);
    }

    public function testAdjustStatsAddsAndSubtractsPoints() : void
    {
        dbquery ("UPDATE " . $this->prefix() . "users SET banned = 0, admin = 0, score1 = 1000, score2 = 2000, score3 = 3000 WHERE player_id = 1");

        AdjustStats (1, 100, 20, 5, '+');
        $user = $this->userRow (1);
        $this->assertSame (1100, (int)$user['score1']);
        $this->assertSame (2020, (int)$user['score2']);
        $this->assertSame (3005, (int)$user['score3']);

        AdjustStats (1, 300, 1000, 3000, '-');
        $user = $this->userRow (1);
        $this->assertSame (800, (int)$user['score1']);
        $this->assertSame (1020, (int)$user['score2']);
        $this->assertSame (5, (int)$user['score3']);
    }

    public function testAdjustStatsIgnoresBannedAndAdministratorAccounts() : void
    {
        dbquery ("UPDATE " . $this->prefix() . "users SET score1 = 0, score2 = 0, score3 = 0, admin = 0, banned = 0 WHERE player_id = 1");
        dbquery ("UPDATE " . $this->prefix() . "users SET score1 = 0, score2 = 0, score3 = 0, admin = 1, banned = 0 WHERE player_id = 2");
        dbquery ("UPDATE " . $this->prefix() . "users SET score1 = 0, score2 = 0, score3 = 0, admin = 0, banned = 1 WHERE player_id = 3");

        AdjustStats (1, 500, 600, 700, '+');
        AdjustStats (2, 500, 600, 700, '+');
        AdjustStats (3, 500, 600, 700, '+');

        $this->assertSame (500, (int)$this->userRow (1)['score1']);
        $this->assertSame (0, (int)$this->userRow (2)['score1']);       // administrators are skipped
        $this->assertSame (0, (int)$this->userRow (3)['score1']);       // banned players are skipped
        $this->assertSame (0, (int)$this->userRow (2)['score3']);
        $this->assertSame (0, (int)$this->userRow (3)['score2']);
    }

    public function testRecalcRanksAssignsPlacesOrderedByScore() : void
    {
        RecalcRanks ();

        $this->assertSame (1, (int)$this->userRow (1)['place1']);       // 50000
        $this->assertSame (2, (int)$this->userRow (2)['place1']);       // 45000
        $this->assertSame (3, (int)$this->userRow (3)['place1']);       // 40000
        $this->assertSame (1, (int)$this->userRow (1)['place2']);
        $this->assertSame (2, (int)$this->userRow (2)['place2']);
        $this->assertSame (3, (int)$this->userRow (3)['place2']);
        $this->assertSame (1, (int)$this->userRow (1)['place3']);
        $this->assertSame (2, (int)$this->userRow (2)['place3']);
        $this->assertSame (3, (int)$this->userRow (3)['place3']);

        // A new top score re-orders the places.
        dbquery ("UPDATE " . $this->prefix() . "users SET score1 = 99999 WHERE player_id = 3");
        RecalcRanks ();

        $this->assertSame (1, (int)$this->userRow (3)['place1']);
        $this->assertSame (2, (int)$this->userRow (1)['place1']);
        $this->assertSame (3, (int)$this->userRow (2)['place1']);
    }

    public function testRecalcRanksResetsAdministrators() : void
    {
        dbquery ("UPDATE " . $this->prefix() . "users SET admin = 2 WHERE player_id = 1");

        RecalcRanks ();

        $admin = $this->userRow (1);
        $this->assertSame (-1, (int)$admin['score1']);
        $this->assertSame (-1, (int)$admin['score2']);
        $this->assertSame (-1, (int)$admin['score3']);
        $this->assertSame (0, (int)$admin['place1']);
        $this->assertSame (0, (int)$admin['place2']);
        $this->assertSame (0, (int)$admin['place3']);

        // The regular players keep the first two places.
        $this->assertSame (1, (int)$this->userRow (2)['place1']);
        $this->assertSame (2, (int)$this->userRow (3)['place1']);
    }

    // ------------------------------------------------------------------------
    // Player listings: GetUsersCount / EnumOperators / GetTop1
    // ------------------------------------------------------------------------

    public function testGetUsersCountCountsRegularPlayersOnly() : void
    {
        $this->assertSame (3, GetUsersCount ());

        dbquery ("UPDATE " . $this->prefix() . "users SET admin = 1 WHERE player_id = 1");
        dbquery ("UPDATE " . $this->prefix() . "users SET admin = 2 WHERE player_id = 2");

        $this->assertSame (1, GetUsersCount ());
    }

    public function testEnumOperatorsReturnsOperatorsOnly() : void
    {
        $this->assertSame (0, dbrows (EnumOperators ()));

        dbquery ("UPDATE " . $this->prefix() . "users SET admin = 1 WHERE player_id = 2");
        dbquery ("UPDATE " . $this->prefix() . "users SET admin = 2 WHERE player_id = 3");    // admin, not operator

        $result = EnumOperators ();
        $this->assertSame (1, dbrows ($result));
        $row = dbarray ($result);
        $this->assertSame (2, (int)$row['player_id']);
    }

    public function testGetTop1ReturnsTheHighestScoringPlayerOrNull() : void
    {
        $top = GetTop1 ();
        $this->assertIsArray ($top);
        $this->assertSame (1, (int)$top['player_id']);                  // 50000 points

        dbquery ("UPDATE " . $this->prefix() . "users SET score1 = 99999 WHERE player_id = 3");
        $this->assertSame (3, (int)GetTop1 ()['player_id']);

        dbquery ("DELETE FROM " . $this->prefix() . "users");
        $this->assertNull (GetTop1 ());
    }

    // ------------------------------------------------------------------------
    // Settings: SetUserFlags / ChangeSkinPath / EnableSkin
    // ------------------------------------------------------------------------

    public function testSetUserFlagsStoresTheValueForThatPlayerOnly() : void
    {
        $otherBefore = (int)$this->fetchValue ("SELECT flags FROM " . $this->prefix() . "users WHERE player_id = 2");

        SetUserFlags (1, 0x8101);

        $this->assertSame (0x8101, (int)$this->fetchValue ("SELECT flags FROM " . $this->prefix() . "users WHERE player_id = 1"));
        $this->assertSame ($otherBefore, (int)$this->fetchValue ("SELECT flags FROM " . $this->prefix() . "users WHERE player_id = 2"));
    }

    public function testChangeSkinPathAndEnableSkinUpdateThePlayerRow() : void
    {
        ChangeSkinPath (1, 'http://example.com/custom/');
        $this->assertSame ('http://example.com/custom/', $this->fetchValue ("SELECT skin FROM " . $this->prefix() . "users WHERE player_id = 1"));

        EnableSkin (1, false);
        $this->assertSame (0, (int)$this->fetchValue ("SELECT useskin FROM " . $this->prefix() . "users WHERE player_id = 1"));

        EnableSkin (1, true);
        $this->assertSame (1, (int)$this->fetchValue ("SELECT useskin FROM " . $this->prefix() . "users WHERE player_id = 1"));
    }

    // ------------------------------------------------------------------------
    // AdminUserName
    // ------------------------------------------------------------------------

    public function testAdminUserNameRendersTheLinkAndStatusMarkers() : void
    {
        global $session;
        $session = 'sess123';
        $now = time ();

        $this->assertSame ('', AdminUserName (null));

        $base = array (
            'player_id' => 7, 'oname' => 'Someone', 'lastclick' => $now,
            'vacation' => 0, 'banned' => 0, 'noattack' => 0, 'disable' => 0,
        );

        $this->assertSame (
            '<a href="index.php?page=admin&session=sess123&mode=Users&player_id=7">Someone</a>',
            AdminUserName ($base)
        );

        // Inactive for more than a week -> "i", greyed out.
        $html = AdminUserName (array_merge ($base, array ('lastclick' => $now - 8 * 86400)));
        $this->assertStringContainsString ('(i)', $html);
        $this->assertStringContainsString ('<font color=#cccccc>', $html);

        // Inactive for more than four weeks -> "iI", dark grey.
        $html = AdminUserName (array_merge ($base, array ('lastclick' => $now - 30 * 86400)));
        $this->assertStringContainsString ('(iI)', $html);
        $this->assertStringContainsString ('<font color=#999999>', $html);

        $vacation = AdminUserName (array_merge ($base, array ('vacation' => 1)));
        $this->assertStringContainsString ('(v)', $vacation);
        $this->assertStringContainsString ('<font color=skyBlue>', $vacation);

        $banned = AdminUserName (array_merge ($base, array ('banned' => 1)));
        $this->assertStringContainsString ('(b)', $banned);
        $this->assertStringContainsString ('<font color=red>', $banned);

        $noattack = AdminUserName (array_merge ($base, array ('noattack' => 1)));
        $this->assertStringContainsString ("(\u{0410})", $noattack);     // Cyrillic "А" marker
        $this->assertStringContainsString ('<font color=yellow>', $noattack);

        $disabled = AdminUserName (array_merge ($base, array ('disable' => 1)));
        $this->assertStringContainsString ('(g)', $disabled);
        $this->assertStringContainsString ('<font color=orange>', $disabled);

        // The display name is HTML-escaped.
        $xss = AdminUserName (array_merge ($base, array ('oname' => '<b>x</b>')));
        $this->assertStringContainsString ('&lt;b&gt;x&lt;/b&gt;', $xss);
        $this->assertStringNotContainsString ('<b>x</b>', $xss);
    }

    // ------------------------------------------------------------------------
    // Bans: BanUser / BanUserAttacks / UnbanUser / UnbanUserAttacks
    // ------------------------------------------------------------------------

    public function testBanUserSetsTheBanAndTheUnbanTask() : void
    {
        $t0 = time ();
        BanUser (2, 3600, false);

        $user = $this->userRow (2);
        $this->assertSame (1, (int)$user['banned']);
        $this->assertGreaterThanOrEqual ($t0 + 3600, (int)$user['banned_until']);
        $this->assertLessThanOrEqual (time () + 3600, (int)$user['banned_until']);
        $this->assertSame (0, (int)$user['score1']);                    // scores are wiped
        $this->assertSame (0, (int)$user['score2']);
        $this->assertSame (0, (int)$user['score3']);
        $this->assertSame (0, (int)$user['vacation']);                  // no vacation mode requested

        $tasks = $this->fetchRows ("SELECT * FROM " . $this->prefix() . "queue WHERE owner_id = 2 AND type = '" . QTYP_UNBAN . "'");
        $this->assertCount (1, $tasks);
        $this->assertSame ((int)$user['banned_until'], (int)$tasks[0]['end']);

        // The same ban with vacation mode also sets vacation_until.
        BanUser (3, 7200, true);
        $user = $this->userRow (3);
        $this->assertSame (1, (int)$user['banned']);
        $this->assertSame (1, (int)$user['vacation']);
        $this->assertSame ((int)$user['banned_until'], (int)$user['vacation_until']);

        // A repeated ban replaces the pending unban task instead of stacking it.
        BanUser (2, 60, false);
        $this->assertSame (1, $this->countRows ("SELECT * FROM " . $this->prefix() . "queue WHERE owner_id = 2 AND type = '" . QTYP_UNBAN . "'"));
    }

    public function testBanUserAttacksSetsTheAttackBanAndTheAllowTask() : void
    {
        $t0 = time ();
        BanUserAttacks (1, 1800);

        $user = $this->userRow (1);
        $this->assertSame (1, (int)$user['noattack']);
        $this->assertGreaterThanOrEqual ($t0 + 1800, (int)$user['noattack_until']);
        $this->assertLessThanOrEqual (time () + 1800, (int)$user['noattack_until']);
        // The attack ban does not touch the scores.
        $this->assertGreaterThan (0, (int)$user['score1']);

        $tasks = $this->fetchRows ("SELECT * FROM " . $this->prefix() . "queue WHERE owner_id = 1 AND type = '" . QTYP_ALLOW_ATTACKS . "'");
        $this->assertCount (1, $tasks);
        $this->assertSame ((int)$user['noattack_until'], (int)$tasks[0]['end']);

        // A repeated ban replaces the pending task.
        BanUserAttacks (1, 60);
        $this->assertSame (1, $this->countRows ("SELECT * FROM " . $this->prefix() . "queue WHERE owner_id = 1 AND type = '" . QTYP_ALLOW_ATTACKS . "'"));
    }

    public function testUnbanUserClearsTheBanAndRecalculatesTheStats() : void
    {
        dbquery ("UPDATE " . $this->prefix() . "users SET banned = 1, banned_until = " . (time () + 9999)
            . ", score1 = 0, score2 = 0, score3 = 0 WHERE player_id = 1");
        AddQueue (1, QTYP_UNBAN, 0, 0, 0, time (), 9999, QUEUE_PRIO_LOWEST);

        UnbanUser (1);

        $user = $this->userRow (1);
        $this->assertSame (0, (int)$user['banned']);
        $this->assertSame (0, (int)$user['banned_until']);
        // RecalcStats() ran: the player's planets/research produce real points.
        $this->assertGreaterThan (0, (int)$user['score1']);
        $this->assertGreaterThan (0, (int)$user['score3']);
        // The pending unban task was removed.
        $this->assertSame (0, $this->countRows ("SELECT * FROM " . $this->prefix() . "queue WHERE owner_id = 1 AND type = '" . QTYP_UNBAN . "'"));
    }

    public function testUnbanUserAttacksClearsTheAttackBan() : void
    {
        dbquery ("UPDATE " . $this->prefix() . "users SET noattack = 1, noattack_until = " . (time () + 9999) . " WHERE player_id = 1");
        AddQueue (1, QTYP_ALLOW_ATTACKS, 0, 0, 0, time (), 9999, QUEUE_PRIO_LOWEST);

        UnbanUserAttacks (1);

        $user = $this->userRow (1);
        $this->assertSame (0, (int)$user['noattack']);
        $this->assertSame (0, (int)$user['noattack_until']);
        $this->assertSame (0, $this->countRows ("SELECT * FROM " . $this->prefix() . "queue WHERE owner_id = 1 AND type = '" . QTYP_ALLOW_ATTACKS . "'"));
    }

    // ------------------------------------------------------------------------
    // FeedActivate / EnableVacation
    // ------------------------------------------------------------------------

    public function testFeedActivateEnablesAndDisablesTheFeed() : void
    {
        global $GlobalUser;

        dbquery ("UPDATE " . $this->prefix() . "users SET flags = " . USER_FLAG_DEFAULT . ", feedid = '', lastfeed = 999 WHERE player_id = 1");
        InvalidateUserCache ();
        $GlobalUser = LoadUser (1);

        FeedActivate (true);

        $row = $this->userRow (1);
        $this->assertSame (USER_FLAG_DEFAULT | USER_FLAG_FEED_ENABLE, (int)$row['flags']);
        $this->assertMatchesRegularExpression ('/^[0-9a-f]{32}$/', $row['feedid']);
        $this->assertSame (0, (int)$row['lastfeed']);                   // reset so the feed is re-read
        // The in-memory record of the current player is kept in sync.
        $this->assertSame ((int)$row['flags'], (int)$GlobalUser['flags']);
        $this->assertSame ($row['feedid'], $GlobalUser['feedid']);
        $enabledFeedId = $row['feedid'];

        // Enabling an already enabled feed is a no-op (the token is kept).
        FeedActivate (true);
        $this->assertSame ($enabledFeedId, $this->fetchValue ("SELECT feedid FROM " . $this->prefix() . "users WHERE player_id = 1"));

        FeedActivate (false);
        $row = $this->userRow (1);
        $this->assertSame (USER_FLAG_DEFAULT, (int)$row['flags']);
        $this->assertSame ('', $row['feedid']);

        // Disabling an already disabled feed is a no-op as well.
        dbquery ("UPDATE " . $this->prefix() . "users SET feedid = 'keepme' WHERE player_id = 1");
        FeedActivate (false);
        $this->assertSame ('keepme', $this->fetchValue ("SELECT feedid FROM " . $this->prefix() . "users WHERE player_id = 1"));
    }

    public function testFeedActivateIsBlockedByTheUniverseSetting() : void
    {
        global $GlobalUser, $GlobalUni;

        dbquery ("UPDATE " . $this->prefix() . "users SET flags = " . USER_FLAG_DEFAULT . ", feedid = '' WHERE player_id = 1");
        InvalidateUserCache ();
        $GlobalUser = LoadUser (1);
        $GlobalUni['feedage'] = -1;                                     // the feed is forbidden

        FeedActivate (true);

        $this->assertSame (USER_FLAG_DEFAULT, (int)$this->fetchValue ("SELECT flags FROM " . $this->prefix() . "users WHERE player_id = 1"));
        $this->assertSame (USER_FLAG_DEFAULT, (int)$GlobalUser['flags']);
    }

    public function testEnableVacationSetsTheFlagAndZeroesPlanetProduction() : void
    {
        global $GlobalUser, $PlanetProd;

        $this->assertNotEmpty ($PlanetProd, '$PlanetProd must be loaded by the bootstrap');

        $GlobalUser = array ('player_id' => 1, 'vacation' => 0, 'vacation_until' => 0);
        $until = time () + 3 * 86400;

        EnableVacation (1, $until, true);

        $user = $this->userRow (1);
        $this->assertSame (1, (int)$user['vacation']);
        $this->assertSame ($until, (int)$user['vacation_until']);
        $this->assertSame (1, (int)$GlobalUser['vacation']);
        $this->assertSame ($until, (int)$GlobalUser['vacation_until']);

        // Every production setting of the player's planets is forced to 0%.
        $planets = $this->fetchRows ("SELECT * FROM " . $this->prefix() . "planets WHERE owner_id = 1");
        $this->assertNotEmpty ($planets);
        foreach ($planets as $planet) {
            foreach ($PlanetProd as $gid => $rules) {
                $this->assertSame (0.0, (float)$planet['prod' . $gid],
                    'prod' . $gid . ' of planet ' . $planet['planet_id'] . ' must be 0 during vacation');
            }
        }

        // Another player's planets keep their production settings.
        $foreign = $this->fetchRow ("SELECT * FROM " . $this->prefix() . "planets WHERE owner_id = 2 AND type = " . PTYP_PLANET . " LIMIT 1");
        $this->assertSame (1.0, (float)$foreign['prod' . GID_B_METAL_MINE]);

        // Turning vacation mode off restores the flag (production stays 0).
        EnableVacation (1, 0, false);
        $user = $this->userRow (1);
        $this->assertSame (0, (int)$user['vacation']);
        $this->assertSame (0, (int)$user['vacation_until']);
        $this->assertSame (0, (int)$GlobalUser['vacation']);
        $this->assertSame (0, (int)$GlobalUser['vacation_until']);
    }

    public function testEnableVacationForAnotherPlayerDoesNotTouchTheGlobal() : void
    {
        global $GlobalUser;

        $GlobalUser = array ('player_id' => 1, 'vacation' => 0, 'vacation_until' => 0);

        EnableVacation (2, time () + 100, true);

        $this->assertSame (1, (int)$this->userRow (2)['vacation']);
        $this->assertSame (0, (int)$GlobalUser['vacation']);
    }

    // ------------------------------------------------------------------------
    // ChangeName / ChangeActivationCode / ChangeEmail
    // ------------------------------------------------------------------------

    public function testChangeNameStoresBothNameFormsAndQueuesOneRenamePermission() : void
    {
        $t0 = time ();

        ChangeName (1, 'NewHero');

        $user = $this->userRow (1);
        $this->assertSame ('newhero', $user['name']);                   // comparison name
        $this->assertSame ('NewHero', $user['oname']);                  // display name
        $this->assertSame (1, (int)$user['name_changed']);
        $this->assertGreaterThanOrEqual ($t0 + 7 * 86400, (int)$user['name_until']);
        $this->assertCount (1, $this->fetchRows (
            "SELECT * FROM " . $this->prefix() . "queue WHERE owner_id = 1 AND type = '" . QTYP_ALLOW_NAME . "'"));

        // A second rename does not queue a second permission event.
        ChangeName (1, 'NewHero2');
        $this->assertSame ('newhero2', $this->userRow (1)['name']);
        $this->assertCount (1, $this->fetchRows (
            "SELECT * FROM " . $this->prefix() . "queue WHERE owner_id = 1 AND type = '" . QTYP_ALLOW_NAME . "'"));
    }

    public function testChangeActivationCodeStoresANewRandomCode() : void
    {
        $id = $this->insertUser (array ('name' => 'codeuser', 'oname' => 'CodeUser', 'validatemd' => ''));

        $ack = ChangeActivationCode ('CodeUser');                       // the name is lower-cased

        $this->assertMatchesRegularExpression ('/^[a-f0-9]{32}$/', $ack);
        $this->assertSame ($ack, (string)$this->fetchValue ("SELECT validatemd FROM " . $this->prefix() . "users WHERE player_id = $id"));
    }

    public function testChangeEmailRefusesAnAddressThatIsAlreadyInUse() : void
    {
        $id = $this->insertUser (array (
            'name' => 'emailchange', 'oname' => 'EmailChange',
            'email' => 'mine@test.com', 'pemail' => 'mine@test.com', 'validatemd' => '',
        ));

        // player1@test.com belongs to the fixture's PlayerOne.
        $this->assertFalse (ChangeEmail ('emailchange', 'player1@test.com'));

        $user = $this->userRow ($id);
        $this->assertSame ('mine@test.com', $user['email']);
        $this->assertSame ('', $user['validatemd']);                    // no new activation code
    }

    // NOTE: the successful ChangeEmail() path finishes in SendChangeMail(),
    // i.e. in PHP mail(). Without an MTA the mail() call aborts the PHPUnit
    // process ("sh: 1: /usr/sbin/sendmail: not found"), so the success branch
    // (which would also store the new address and a new activation code) cannot
    // be covered here. ChangeActivationCode() itself is covered below.

    // ------------------------------------------------------------------------
    // SelectPlanet
    // ------------------------------------------------------------------------

    public function testSelectPlanetChangesTheCurrentPlanet() : void
    {
        global $GlobalUser;
        $GlobalUser = $this->userRow (1);

        SelectPlanet (1, 2);                                            // planet 2 belongs to player 1

        $this->assertSame (2, (int)$this->fetchValue ("SELECT aktplanet FROM " . $this->prefix() . "users WHERE player_id = 1"));
        $this->assertSame (2, GetSelectedPlanet (1));
    }

    public function testSelectPlanetFallsBackToTheHomePlanet() : void
    {
        global $GlobalUser;
        $GlobalUser = $this->userRow (1);
        $home = (int)$GlobalUser['hplanetid'];

        // A planet that no longer exists (e.g. a destroyed moon page) falls
        // back to the home planet instead of failing.
        SelectPlanet (1, 424242);

        $this->assertSame ($home, (int)$this->fetchValue ("SELECT aktplanet FROM " . $this->prefix() . "users WHERE player_id = 1"));
    }

    public function testSelectPlanetRejectsForeignAndSpecialPlanets() : void
    {
        global $GlobalUser;

        $GlobalUser = $this->userRow (1);
        $current = (int)$GlobalUser['aktplanet'];
        $hacks = (int)$this->fetchValue ("SELECT hacks FROM " . $this->prefix() . "uni");
        $debugBefore = $this->countRows ("SELECT * FROM " . $this->prefix() . "debug");

        // Planet 4 belongs to player 2: a hacking attempt is recorded.
        SelectPlanet (1, 4);

        $this->assertSame ($current, (int)$this->fetchValue ("SELECT aktplanet FROM " . $this->prefix() . "users WHERE player_id = 1"));
        $this->assertSame ($hacks + 1, (int)$this->fetchValue ("SELECT hacks FROM " . $this->prefix() . "uni"));
        $this->assertSame ($debugBefore + 1, $this->countRows ("SELECT * FROM " . $this->prefix() . "debug"));

        // A debris field owned by the player is not selectable either.
        $dfId = AddDBRow (array (
            'owner_id' => 1, 'name' => 'Debris', 'type' => PTYP_DF,
            'g' => 1, 's' => 1, 'p' => 14, 'diameter' => 0, 'temp' => 0, 'fields' => 0, 'maxfields' => 0,
        ), 'planets');

        SelectPlanet (1, $dfId);

        $this->assertNotSame ($dfId, (int)$this->fetchValue ("SELECT aktplanet FROM " . $this->prefix() . "users WHERE player_id = 1"));
        $this->assertSame ($hacks + 2, (int)$this->fetchValue ("SELECT hacks FROM " . $this->prefix() . "uni"));
    }

    // ------------------------------------------------------------------------
    // ReactivateUser
    // ------------------------------------------------------------------------

    public function testReactivateUserResetsThePasswordAndTheActivation() : void
    {
        $oldHash = md5 ('oldpw' . 'testsecret');
        dbquery ("UPDATE " . $this->prefix() . "users SET password = '$oldHash', validated = 1, validatemd = '' WHERE player_id = 1");

        // REMOTE_ADDR is 127.0.0.1, so no e-mail is sent.
        ReactivateUser (1);

        $user = $this->userRow (1);
        $this->assertSame (0, (int)$user['validated']);
        $this->assertMatchesRegularExpression ('/^[a-f0-9]{32}$/', (string)$user['password']);
        $this->assertNotSame ($oldHash, $user['password']);
        $this->assertMatchesRegularExpression ('/^[a-f0-9]{32}$/', (string)$user['validatemd']);
    }

    public function testReactivateUserIgnoresUnknownPlayers() : void
    {
        $before = $this->countRows ("SELECT * FROM " . $this->prefix() . "users");

        ReactivateUser (123456);

        $this->assertSame ($before, $this->countRows ("SELECT * FROM " . $this->prefix() . "users"));
    }

    // ------------------------------------------------------------------------
    // SendGreetingsMessage
    // ------------------------------------------------------------------------

    public function testSendGreetingsMessageStoresAMessageForThePlayer() : void
    {
        SendGreetingsMessage (2);

        $msg = $this->fetchRow ("SELECT * FROM " . $this->prefix() . "messages WHERE owner_id = 2 ORDER BY msg_id DESC LIMIT 1");
        $this->assertIsArray ($msg);
        $this->assertSame (MTYP_MISC, (int)$msg['pm']);
        $this->assertSame (loca_lang ('FLEET_MESSAGE_FROM', 'en'), $msg['msgfrom']);
    }

    public function testSendGreetingsMessageIgnoresUnknownPlayers() : void
    {
        $before = $this->countRows ("SELECT * FROM " . $this->prefix() . "messages");

        SendGreetingsMessage (123456);

        $this->assertSame ($before, $this->countRows ("SELECT * FROM " . $this->prefix() . "messages"));
    }

    // NOTE: SendGreetingsMail() and SendChangeMail() build the message with
    // loca_lang()/hostname() and hand it to mail_utf8() (PHP mail()), which
    // needs a configured MTA; they are therefore not exercised here.

    // ------------------------------------------------------------------------
    // RemoveUser / UnloadAll
    // ------------------------------------------------------------------------

    public function testRemoveUserDeletesTheAccountPlanetsFleetsAndRelations() : void
    {
        global $GlobalUser;
        $GlobalUser = array ('player_id' => 1);                         // somebody else deletes player 3

        $p = $this->prefix();
        $this->assertSame (1, $this->countRows ("SELECT * FROM {$p}users WHERE player_id = 3"));
        $this->assertGreaterThan (0, $this->countRows ("SELECT * FROM {$p}planets WHERE owner_id = 3"));
        $this->assertGreaterThan (0, $this->countRows ("SELECT * FROM {$p}fleet WHERE owner_id = 3"));
        $this->assertGreaterThan (0, $this->countRows ("SELECT * FROM {$p}allyapps WHERE player_id = 3"));
        $this->assertGreaterThan (0, $this->countRows ("SELECT * FROM {$p}buddy WHERE request_from = 3 OR request_to = 3"));

        RemoveUser (3, time ());

        $this->assertSame (0, $this->countRows ("SELECT * FROM {$p}users WHERE player_id = 3"));
        $this->assertSame (0, $this->countRows ("SELECT * FROM {$p}planets WHERE owner_id = 3"));
        $this->assertSame (0, $this->countRows ("SELECT * FROM {$p}fleet WHERE owner_id = 3"));
        $this->assertSame (0, $this->countRows ("SELECT * FROM {$p}queue WHERE owner_id = 3"));
        $this->assertSame (0, $this->countRows ("SELECT * FROM {$p}buildqueue WHERE owner_id = 3"));
        $this->assertSame (0, $this->countRows ("SELECT * FROM {$p}allyapps WHERE player_id = 3"));
        $this->assertSame (0, $this->countRows ("SELECT * FROM {$p}buddy WHERE request_from = 3 OR request_to = 3"));

        // The universe user counter is decremented, the other players survive.
        $this->assertSame (2, (int)$this->fetchValue ("SELECT usercount FROM {$p}uni"));
        $this->assertSame (1, $this->countRows ("SELECT * FROM {$p}users WHERE player_id = 1"));
        $this->assertSame (1, $this->countRows ("SELECT * FROM {$p}users WHERE player_id = 2"));
    }

    public function testRemoveUserKeepsAdministratorAndSpaceAccounts() : void
    {
        global $GlobalUser;
        $GlobalUser = array ('player_id' => 2);

        RemoveUser (USER_LEGOR, time ());                               // player 1
        RemoveUser (USER_SPACE, time ());                               // the technical space account

        $this->assertSame (1, $this->countRows ("SELECT * FROM " . $this->prefix() . "users WHERE player_id = " . USER_LEGOR));
        $this->assertSame (3, (int)$this->fetchValue ("SELECT usercount FROM " . $this->prefix() . "uni"));
    }

    public function testUnloadAllClearsEveryPublicSession() : void
    {
        global $StartPage;
        $StartPage = 'index.php';

        dbquery ("UPDATE " . $this->prefix() . "users SET session = 'aaaaaaaaaaaa'");
        $this->assertSame (3, $this->countRows ("SELECT * FROM " . $this->prefix() . "users WHERE session <> ''"));

        // UnloadAll() cleans and flushes the output buffer itself, so use two
        // nested buffers to swallow the emitted script tag.
        ob_start ();
        ob_start ();
        UnloadAll ();
        ob_end_clean ();

        $this->assertSame (0, $this->countRows ("SELECT * FROM " . $this->prefix() . "users WHERE session <> ''"));
    }

    // NOTE: RemoveUser() of the *currently logged in* player ends in
    // RedirectHome() + exit(), so only the "another player / protected account"
    // paths can be tested here.
}
