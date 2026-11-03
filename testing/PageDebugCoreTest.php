<?php

declare(strict_types=1);

// Tests for the shared page elements (game/core/page.php) and the debug /
// error-reporting helpers (game/core/debug.php).
//
// The tests run against the real database layer with the in-memory SQLite
// backend (DB_CONNECTION=sqlite, DB_DATABASE=:memory:, see phpunit.xml and
// testing/bootstrap.php), so no MySQL server and no mocked DB functions are
// required. Pure helpers (image paths, drop lists, bonus markup) are tested
// without any rows; the functions that read the game state use the fixture
// universe built by FixtureBuilder over the real game schema.
//
// Each test method runs in a separate PHP process and starts with a fresh
// in-memory database. Process isolation matters here: the game core assigns
// global variables ($resourcemap, $storagemap, $modlist, ...) at its top level
// and only the process-isolated child loads the bootstrap at the true top
// level.
//
// Not covered, because the function never returns and therefore cannot be
// observed from inside the test process:
//  - Error()          (writes the errors row, logs the user out, prints the
//                      error page and calls exit());
//  - MyGoto()         (sends a Location header and die()s);
//  - the failure paths of SecurityCheck() and LoadJsonFirst(), which both
//    delegate to Error().
// The reachable parts of the debug module (Debug, BrowseHistory, BackTrace,
// SecurityCheck on success, LogIPAddress, GetLastRegistrationByIP, UserLog,
// Hacking, GetSQLQueryLogText) are covered completely.

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class PageDebugCoreTest extends TestCase
{
    protected function setUp(): void
    {
        // The game resolves loca files, pages/*.json and router.json relative
        // to the game directory (see testing/bootstrap.php).
        chdir(__DIR__ . '/../game');

        global $db_prefix, $db_name, $db_host, $db_user, $db_pass;
        global $UserCache, $LOCA, $loca_lang;
        global $GlobalUser, $GlobalUni, $session, $from_cron, $pagetime, $aktplanet;
        global $query_counter, $query_log;

        $db_prefix = 'test_';
        $db_name = 'test';
        $db_host = '';
        $db_user = '';
        $db_pass = '';
        $UserCache = array ();
        $LOCA = array ();
        $loca_lang = 'en';

        // Fresh in-memory schema for the tests that insert rows directly.
        InitDB();
        CreateDBTables();

        // Request context, set up the same way game/index.php sets it.
        $_GET = array ();
        $_POST = array ();
        $_REQUEST = array ();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PageDebugCoreTest/1.0';
        $_SERVER['REQUEST_URI'] = '/game/index.php?page=overview';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';
        $_SERVER['HTTPS'] = '';

        $GlobalUser = array ();
        $GlobalUni = array ('num' => 1, 'lang' => 'en');
        $session = 'test0session';
        $from_cron = false;
        $pagetime = 0;
        $aktplanet = null;
        $query_counter = 0;
        $query_log = '';
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    // Build the 3-player fixture universe (real game schema + seeded rows).
    private function buildFixture() : FixtureBuilder
    {
        $fixture = new FixtureBuilder();
        $fixture->createTestUniverse('en');
        return $fixture;
    }

    // Log a fixture player in as the global user, the way AuthUser() does.
    private function loginAs(int $playerId, array $overrides = array ()) : array
    {
        global $GlobalUser, $UserCache;
        $UserCache = array ();
        $user = LoadUser($playerId);
        $this->assertNotNull($user, "fixture player $playerId must exist");
        // array_replace (not array_merge): the GID_* keys of the user row are
        // integers and array_merge would renumber them.
        $GlobalUser = array_replace($user, $overrides);
        return $GlobalUser;
    }

    // Insert a minimal universe row (only the columns the tested code reads).
    private function addMinimalUniverse() : void
    {
        AddDBRow(array (
            'num' => 1, 'speed' => 1.0, 'fspeed' => 1.0, 'galaxies' => 1,
            'systems' => 15, 'hacks' => 0, 'lang' => 'en',
        ), 'uni');
    }

    // Capture the HTML a page function echoes.
    private function capture(callable $fn) : string
    {
        ob_start();
        try {
            $fn();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    // Fetch the first row of a query, or null when there is none.
    private function fetchRow(string $sql) : ?array
    {
        $row = dbarray(dbquery($sql));
        return $row === false ? null : $row;
    }

    // Fetch every row of a query.
    private function fetchRows(string $sql) : array
    {
        $rows = array ();
        $result = dbquery($sql);
        while ($row = dbarray($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // Count the rows of a table (optionally filtered).
    private function countRows(string $table, string $where = '1 = 1') : int
    {
        global $db_prefix;
        $row = $this->fetchRow("SELECT COUNT(*) AS cnt FROM {$db_prefix}{$table} WHERE $where");
        return (int) $row['cnt'];
    }

    // ========================================================================
    // game/core/page.php: image helpers
    // ========================================================================

    /**
     * The default building image is the skin path plus gebaeude/<id>.gif.
     */
    public function testGetObjectImageBuildsTheSkinImageTag() : void
    {
        $this->assertSame(
            "<img border='0' src=\"skin/gebaeude/1.gif\" align='top' width='120' height='120'>",
            GetObjectImage('skin/', 1)
        );
        $this->assertSame(
            "<img border='0' src=\"http://localhost/evolution/gebaeude/44.gif\" align='top' width='120' height='120'>",
            GetObjectImage('http://localhost/evolution/', 44)
        );
        // Id 0 is not special-cased: it maps to gebaeude/0.gif.
        $this->assertStringContainsString('src="skin/gebaeude/0.gif"', GetObjectImage('skin/', 0));
    }

    /**
     * The small planet image depends on the position band and on planet_id % 7.
     */
    public function testGetPlanetSmallImageForEveryPositionBand() : void
    {
        $bands = array (
            0 => 's_trockenplanet',      // below the documented range: dry planet
            1 => 's_trockenplanet',
            3 => 's_trockenplanet',
            4 => 's_dschjungelplanet',
            6 => 's_dschjungelplanet',
            7 => 's_normaltempplanet',
            9 => 's_normaltempplanet',
            10 => 's_wasserplanet',
            12 => 's_wasserplanet',
            13 => 's_eisplanet',
            15 => 's_eisplanet',
            16 => 's_eisplanet',         // above the documented range: ice planet
        );

        foreach ($bands as $p => $prefix) {
            $img = GetPlanetSmallImage('skin/', array ('type' => PTYP_PLANET, 'p' => $p, 'planet_id' => 0));
            $this->assertSame("skin/planeten/small/{$prefix}01.jpg", $img, "planet position $p");
        }

        // The image number is planet_id % 7 + 1: 5 % 7 + 1 = 6.
        $this->assertSame(
            'skin/planeten/small/s_wasserplanet06.jpg',
            GetPlanetSmallImage('skin/', array ('type' => PTYP_PLANET, 'p' => 10, 'planet_id' => 5))
        );
        // 6 % 7 + 1 = 7 (the modulo wraps around).
        $this->assertSame(
            'skin/planeten/small/s_trockenplanet07.jpg',
            GetPlanetSmallImage('skin/', array ('type' => PTYP_PLANET, 'p' => 3, 'planet_id' => 6))
        );
    }

    /**
     * Moons, debris fields and the other galaxy objects use fixed images.
     */
    public function testGetPlanetSmallImageForMoonsDebrisAndSpecialObjects() : void
    {
        $this->assertSame('skin/planeten/small/s_mond.jpg',
            GetPlanetSmallImage('skin/', array ('type' => PTYP_MOON)));
        $this->assertSame('skin/planeten/small/s_mond.jpg',
            GetPlanetSmallImage('skin/', array ('type' => PTYP_DEST_MOON)));
        $this->assertSame('skin/planeten/debris.jpg',
            GetPlanetSmallImage('skin/', array ('type' => PTYP_DF)));
        // Every other special object (destroyed planets, phantoms, ...) uses
        // the admin image, which is not skin-relative.
        $this->assertSame('img/admin_planets.png',
            GetPlanetSmallImage('skin/', array ('type' => PTYP_DEST_PLANET)));
        $this->assertSame('img/admin_planets.png',
            GetPlanetSmallImage('skin/', array ('type' => PTYP_FARSPACE)));
    }

    /**
     * The big planet image follows the same position bands without the small/
     * directory.
     */
    public function testGetPlanetImageForEveryPositionBand() : void
    {
        $bands = array (
            1 => 'trockenplanet',
            4 => 'dschjungelplanet',
            6 => 'dschjungelplanet',
            7 => 'normaltempplanet',
            9 => 'normaltempplanet',
            10 => 'wasserplanet',
            12 => 'wasserplanet',
            13 => 'eisplanet',
            15 => 'eisplanet',
            16 => 'eisplanet',
        );

        foreach ($bands as $p => $prefix) {
            $img = GetPlanetImage('skin/', array ('type' => PTYP_PLANET, 'p' => $p, 'planet_id' => 2));
            $this->assertSame("skin/planeten/{$prefix}03.jpg", $img, "planet position $p");
        }
    }

    /**
     * The big image of a moon, a debris field and a special object.
     */
    public function testGetPlanetImageForMoonsDebrisAndSpecialObjects() : void
    {
        $this->assertSame('skin/planeten/mond.jpg', GetPlanetImage('skin/', array ('type' => PTYP_MOON)));
        $this->assertSame('skin/planeten/mond.jpg', GetPlanetImage('skin/', array ('type' => PTYP_DEST_MOON)));
        $this->assertSame('skin/planeten/debris.jpg', GetPlanetImage('skin/', array ('type' => PTYP_DF)));
        $this->assertSame('img/admin_planets.png', GetPlanetImage('skin/', array ('type' => PTYP_DEST_PLANET)));
    }

    // ========================================================================
    // game/core/page.php: UserSkin
    // ========================================================================

    /**
     * A user with "use skin" enabled gets the configured skin path.
     */
    public function testUserSkinUsesTheConfiguredSkin() : void
    {
        global $GlobalUser;

        $GlobalUser = array ('useskin' => 1, 'skin' => 'http://skin.example/evolution/');
        $this->assertSame('http://skin.example/evolution/', UserSkin());
    }

    /**
     * Without "use skin" (or an empty/zero flag) the hostname + evolution/ is
     * used; the URL is derived from HTTP_HOST and SCRIPT_NAME.
     */
    public function testUserSkinFallsBackToTheHostnameEvolutionDirectory() : void
    {
        global $GlobalUser;

        // No useskin key at all.
        $GlobalUser = array ('skin' => 'http://skin.example/');
        $this->assertSame('http://localhost/evolution/', UserSkin());

        // Explicitly disabled.
        $GlobalUser = array ('useskin' => 0, 'skin' => 'http://skin.example/');
        $this->assertSame('http://localhost/evolution/', UserSkin());

        // A skin flag of 1 with an empty path returns the empty string: the
        // function has no fallback for an inconsistent user row. This
        // documents the current behaviour (see the final report).
        $GlobalUser = array ('useskin' => 1, 'skin' => '');
        $this->assertSame('', UserSkin());
    }

    // ========================================================================
    // game/core/page.php: drop list helpers
    // ========================================================================

    /**
     * DropListHasMoon returns the moon row located at the planet's coordinates.
     */
    public function testDropListHasMoonReturnsTheMoonAtTheSameCoordinates() : void
    {
        $planet = array ('type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 4, 'planet_id' => 1, 'name' => 'Home');
        $moon = array ('type' => PTYP_MOON, 'g' => 1, 's' => 1, 'p' => 4, 'planet_id' => 10, 'name' => 'Moon');
        $otherMoon = array ('type' => PTYP_MOON, 'g' => 1, 's' => 2, 'p' => 4, 'planet_id' => 11, 'name' => 'Moon B');

        $plist = array ($planet, $otherMoon, $moon);

        $this->assertSame($moon, DropListHasMoon($plist, $planet));
    }

    /**
     * No moon, a moon at other coordinates or a non-moon object all yield null.
     */
    public function testDropListHasMoonReturnsNullWithoutAMatchingMoon() : void
    {
        $planet = array ('type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 4, 'planet_id' => 1);

        // Empty list.
        $this->assertNull(DropListHasMoon(array (), $planet));

        // Only a planet at the same coordinates: planets are not moons.
        $this->assertNull(DropListHasMoon(array (
            array ('type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 4, 'planet_id' => 2),
        ), $planet));

        // Moon in the same system but at another position.
        $this->assertNull(DropListHasMoon(array (
            array ('type' => PTYP_MOON, 'g' => 1, 's' => 1, 'p' => 5, 'planet_id' => 10),
        ), $planet));

        // Moon in another system.
        $this->assertNull(DropListHasMoon(array (
            array ('type' => PTYP_MOON, 'g' => 1, 's' => 3, 'p' => 4, 'planet_id' => 10),
        ), $planet));
    }

    /**
     * ShowGalaxy renders the coordinates as a showGalaxy() link.
     */
    public function testShowGalaxyBuildsTheCoordinateLink() : void
    {
        $this->assertSame(
            '<a onclick="showGalaxy(1,2,3);" href="#">[1:2:3]</a>',
            ShowGalaxy(array ('g' => 1, 's' => 2, 'p' => 3))
        );
        $this->assertSame(
            '<a onclick="showGalaxy(9,499,15);" href="#">[9:499:15]</a>',
            ShowGalaxy(array ('g' => 9, 's' => 499, 'p' => 15))
        );
    }

    /**
     * An empty planet array is falsy, so ShowGalaxy returns an empty string.
     */
    public function testShowGalaxyReturnsAnEmptyStringForAnEmptyPlanet() : void
    {
        $this->assertSame('', ShowGalaxy(array ()));
    }

    // ========================================================================
    // game/core/page.php: officer / bonus helpers
    // ========================================================================

    /**
     * An officer that is not active (or expired) gets the grey image and the
     * "purchase" action, without a remaining-days line.
     */
    public function testGetOfficerBonusForAnInactiveOfficer() : void
    {
        global $GlobalUser;
        loca_add('common', 'en');

        $now = 1700000000;
        $GlobalUser = array ('session' => 'sess1', 'com_until' => 0);

        $res = GetOfficerBonus($now, USER_OFFICER_COMMANDER, 'commander_ikon', 'PR_COMA', null);

        $this->assertSame('index.php?page=micropayment&session=sess1', $res['href']);
        $this->assertSame(loca('HK_PAYMENT'), $res['accesskey']);
        $this->assertSame('img/commander_ikon_un.gif', $res['img']);
        $this->assertSame(loca('PR_COMA'), $res['alt']);
        $this->assertStringContainsString(loca('PR_PURCHASE'), $res['overlib']);
        $this->assertStringNotContainsString(loca('PR_RENEW'), $res['overlib']);
        // No days line: the days string is empty.
        $this->assertStringNotContainsString('days', $res['overlib']);
        // The optional info line is skipped when $loca_info is null.
        $this->assertStringNotContainsString('<font size=1 color=skyblue>', $res['overlib']);
    }

    /**
     * An active officer gets the normal image, the "renew" action and the
     * remaining days rounded UP to the next full day.
     */
    public function testGetOfficerBonusForAnActiveOfficerRoundsTheRemainingDaysUp() : void
    {
        global $GlobalUser;
        loca_add('common', 'en');

        $now = 1700000000;
        $day = 24 * 60 * 60;

        // Exactly two days left.
        $GlobalUser = array ('session' => 'sess1', 'com_until' => $now + 2 * $day);
        $res = GetOfficerBonus($now, USER_OFFICER_COMMANDER, 'commander_ikon', 'PR_COMA', null);
        $this->assertSame('img/commander_ikon.gif', $res['img']);
        $this->assertStringContainsString(va(loca('PR_ACTIVE_DAYS'), 2), $res['overlib']);
        $this->assertStringContainsString(loca('PR_RENEW'), $res['overlib']);

        // One second less than three days: ceil() rounds up to 3.
        $GlobalUser = array ('session' => 'sess1', 'adm_until' => $now + 3 * $day - 1);
        $res = GetOfficerBonus($now, USER_OFFICER_ADMIRAL, 'admiral_ikon', 'PR_ADMIRAL', 'PR_ADMIRAL_INFO');
        $this->assertSame('img/admiral_ikon.gif', $res['img']);
        $this->assertStringContainsString(va(loca('PR_ACTIVE_DAYS'), 3), $res['overlib']);
        // The optional info line is rendered when $loca_info is given.
        $this->assertStringContainsString(loca('PR_ADMIRAL_INFO'), $res['overlib']);
    }

    /**
     * The active/inactive boundary is "end <= now": an officer expiring exactly
     * now counts as inactive.
     */
    public function testGetOfficerBonusBoundaryAtExactExpiry() : void
    {
        global $GlobalUser;
        loca_add('common', 'en');

        $now = 1700000000;

        $GlobalUser = array ('session' => 's', 'tec_until' => $now);
        $this->assertSame('img/technokrat_ikon_un.gif',
            GetOfficerBonus($now, USER_OFFICER_TECHNOCRATE, 'technokrat_ikon', 'PR_TECHNO', null)['img']);

        $GlobalUser = array ('session' => 's', 'tec_until' => $now + 1);
        $this->assertSame('img/technokrat_ikon.gif',
            GetOfficerBonus($now, USER_OFFICER_TECHNOCRATE, 'technokrat_ikon', 'PR_TECHNO', null)['img']);
    }

    /**
     * GetBonusesInHeader appends the colored text and the image with its
     * overlib call for every bonus entry.
     */
    public function testGetBonusesInHeaderRendersTextAndImage() : void
    {
        $bonuses = array (
            array ('text' => 'Active', 'color' => '#00FF00', 'img' => 'img/x.gif', 'alt' => 'X', 'overlib' => 'OL', 'width' => 150),
            array ('text' => '', 'color' => '#FF0000', 'img' => 'img/y.gif', 'alt' => 'Y', 'overlib' => 'OL2', 'width' => 200),
        );

        $res = GetBonusesInHeader($bonuses);

        $this->assertStringContainsString('<b><font style="color:#00FF00;">Active</font></b>', $res);
        $this->assertStringContainsString('<img border="0" alt="X" src="img/x.gif"', $res);
        $this->assertStringContainsString('onmouseover=\'return overlib("OL", WIDTH, 150);\'', $res);
        $this->assertStringContainsString('src="img/y.gif"', $res);
        $this->assertStringContainsString('WIDTH, 200', $res);
        $this->assertStringContainsString('width="20" height="20"', $res);
        // Two images; only the entry with a non-empty text gets the <b> markup.
        $this->assertSame(2, substr_count($res, '<img'));
        $this->assertSame(1, substr_count($res, '<b>'));
    }

    /**
     * BonusList renders the five account bonuses (officers) as icon links.
     */
    public function testBonusListRendersEveryOfficerIcon() : void
    {
        global $GlobalUser, $GlobalUni;

        $this->buildFixture();
        $this->loginAs(1);
        $GlobalUni = LoadUniverse();
        loca_add('common', 'en');

        $ikons = array ('commander', 'admiral', 'ingenieur', 'geologe', 'technokrat');

        // The fixture grants every officer for a year: all five icons are the
        // active variant and every overlib string offers the renewal.
        $html = $this->capture(fn () => BonusList());

        foreach ($ikons as $ikon) {
            $this->assertStringContainsString("src='img/{$ikon}_ikon.gif'", $html);
            $this->assertStringNotContainsString("img/{$ikon}_ikon_un.gif", $html);
        }
        $this->assertSame(5, substr_count($html, '<img'));
        $this->assertSame(5, substr_count($html, 'overlib('));
        $this->assertSame(5, substr_count($html, va(loca('PR_ACTIVE_DAYS'), 365)));
        $this->assertSame(5, substr_count($html, loca('PR_RENEW')));
        // Five icon links plus the five renewal links inside the overlib text.
        $this->assertSame(10, substr_count($html, 'index.php?page=micropayment&session=' . $GlobalUser['session']));

        // With every officer expired the grey "_un" icons are used instead.
        $GlobalUser['com_until'] = 0;
        $GlobalUser['adm_until'] = 0;
        $GlobalUser['eng_until'] = 0;
        $GlobalUser['geo_until'] = 0;
        $GlobalUser['tec_until'] = 0;

        $expired = $this->capture(fn () => BonusList());

        $this->assertSame(5, substr_count($expired, '_ikon_un.gif'));
        foreach ($ikons as $ikon) {
            $this->assertStringNotContainsString("src='img/{$ikon}_ikon.gif'", $expired);
        }
        $this->assertStringContainsString(loca('PR_PURCHASE'), $expired);
        $this->assertStringNotContainsString(loca('PR_RENEW'), $expired);
    }

    /**
     * An empty bonus list renders nothing at all.
     */
    public function testGetBonusesInHeaderWithAnEmptyList() : void
    {
        $bonuses = array ();
        $this->assertSame('', GetBonusesInHeader($bonuses));
    }

    // ========================================================================
    // game/core/page.php: JSON schemas and the resource bar
    // ========================================================================

    /**
     * LoadJsonFirst decodes the resource panel schema relative to the game dir.
     */
    public function testLoadJsonFirstLoadsTheResourcePanelSchema() : void
    {
        $json = LoadJsonFirst('pages/res_panel.json');

        $this->assertIsArray($json);
        $this->assertCount(5, $json);
        $this->assertSame('images/metall.gif', $json[GID_RC_METAL]['img']);
        $this->assertSame('NAME_700', $json[GID_RC_METAL]['loca']);
        $this->assertTrue($json[GID_RC_METAL]['skin']);
        $this->assertSame(0, $json[GID_RC_ENERGY]['val2']);
        $this->assertSame('micropayment', $json[GID_RC_DM]['href']);
        $this->assertFalse($json[GID_RC_DM]['skin']);
    }

    /**
     * ResourceList fills the JSON schema from the planet data: derivative
     * resources are floored and compared with the storage cap, the others show
     * balance/net production, and the dark matter column is the $dm argument.
     */
    public function testResourceListRendersValuesColorsAndTheDarkMatterArgument() : void
    {
        global $GlobalUser;

        $GlobalUser = array ('session' => 'sess1', 'useskin' => 1, 'skin' => 'skin/');

        $planet = array (
            'balance' => array (
                GID_RC_METAL => 1500, GID_RC_CRYSTAL => 200, GID_RC_DEUTERIUM => 300,
                GID_RC_ENERGY => -50, GID_RC_DM => 0,
            ),
            'net_prod' => array (
                GID_RC_METAL => 100, GID_RC_CRYSTAL => 50, GID_RC_DEUTERIUM => 10,
                GID_RC_ENERGY => -25, GID_RC_DM => 0,
            ),
            // Stored amounts (floored by the function) and storage caps.
            GID_RC_METAL => 1500.7, GID_RC_CRYSTAL => 200.2, GID_RC_DEUTERIUM => 300.9,
            'max'.GID_RC_METAL => 1000,     // full: 1500 >= 1000 -> red
            'max'.GID_RC_CRYSTAL => 5000,   // not full -> no color
            'max'.GID_RC_DEUTERIUM => 5000, // not full -> no color
        );

        $html = $this->capture(fn () => ResourceList($planet, 5000));

        // Metal: 1500.7 -> 1500, over the cap -> red (derivative resources
        // only show the stored amount, they have no val2 in the schema).
        $this->assertStringContainsString("color='#ff0000'>1.500</font>", $html);
        // Crystal: 200.2 -> 200, capped at 5000 -> plain.
        $this->assertStringContainsString('>200</font>', $html);
        // Deuterium: 300.9 -> 300.
        $this->assertStringContainsString('>300</font>', $html);
        // Energy is not a derivative resource: negative -> red, val/val2.
        $this->assertStringContainsString("color='#ff0000'>-50</font>/-25", $html);
        // Dark matter: the $dm argument replaces the value, val2 is kept.
        $this->assertStringContainsString('>5.000</font>/0', $html);
        // Exactly two red entries (metal cap + negative energy); the schema's
        // default white color is overwritten for every resource.
        $this->assertSame(2, substr_count($html, '#ff0000'));
        $this->assertStringNotContainsString('#FFFFFF', $html);
        // Skin-relative icons for the resources, the fixed DM icon with a link.
        $this->assertStringContainsString("src='skin/images/metall.gif'", $html);
        $this->assertStringContainsString("src='skin/images/energie.gif'", $html);
        $this->assertStringContainsString("src='img/dm_klein_2.jpg'", $html);
        $this->assertStringContainsString('index.php?page=micropayment&session=sess1', $html);
    }

    /**
     * Resources missing from the planet balance are skipped instead of raising
     * a warning, and a planet without caps stays uncolored. The panel itself
     * still renders every schema entry.
     */
    public function testResourceListSkipsMissingBalancesAndHandlesMissingCaps() : void
    {
        global $GlobalUser;

        $GlobalUser = array ('session' => 's', 'useskin' => 0);

        // Only metal is present; without a max700 key the cap is PHP_INT_MAX,
        // so the value is never marked as full.
        $planet = array (
            'balance' => array (GID_RC_METAL => 42),
            'net_prod' => array (GID_RC_METAL => 7),
            GID_RC_METAL => 42,
        );

        $html = $this->capture(fn () => ResourceList($planet, 0));

        $this->assertStringContainsString('>42</font>', $html);
        $this->assertSame(0, substr_count($html, '#ff0000'));
        // All five schema entries are rendered: one icon and one name each.
        $this->assertSame(5, substr_count($html, '<img'));
        // The four resources without a balance fall back to the schema values
        // (0), and the skipped dark matter entry keeps the passed $dm argument.
        $this->assertSame(4, substr_count($html, '>0</font>'));
        $this->assertStringContainsString("src='img/dm_klein_2.jpg'", $html);
    }

    // ========================================================================
    // game/core/page.php: header, left menu, footer
    // ========================================================================

    /**
     * PlanetsDropList lists the player's planets in planet_id order, nests the
     * moon under its planet and marks the active planet as selected.
     */
    public function testPlanetsDropListListsPlanetsAndTheirMoons() : void
    {
        global $GlobalUser;

        $this->buildFixture();
        $user = $this->loginAs(1);

        // Fixture player 1: Home (1:1:4) with Moon, Colony A (1:1:5) without a
        // moon and Colony B (1:2:4) with Moon B -> 3 planets + 2 moons.
        $_GET = array ('page' => 'overview');
        $html = $this->capture(fn () => PlanetsDropList('overview'));

        $this->assertSame(5, substr_count($html, '<option'));
        $this->assertStringContainsString('index.php?page=overview&session=' . $user['session'] . '&cp=1', $html);
        // The active planet (aktplanet = the home planet) is selected.
        $this->assertSame(1, substr_count($html, " selected>"));
        $this->assertStringContainsString("&cp=1' selected>Home", $html);
        // The moon is listed right after its planet, without being selected.
        $this->assertStringContainsString("&cp=10' >Moon", $html);
        $this->assertStringContainsString('&cp=11', $html);
        $this->assertStringContainsString('[1:1:4]', $html);
        $this->assertStringContainsString('[1:2:4]', $html);

        // gid/tid/mode from the query string are passed through to the links.
        $_GET = array ('page' => 'overview', 'gid' => 5, 'tid' => 7, 'mode' => 'Galaxy');
        $withParams = $this->capture(fn () => PlanetsDropList('overview'));

        $this->assertGreaterThanOrEqual(5, substr_count($withParams, '&gid=5&tid=7&mode=Galaxy'));
        $this->assertStringContainsString("cp=3&gid=5&tid=7&mode=Galaxy' >Colony B", $withParams);
    }

    /**
     * PageHeader renders the head, the header bar (planet drop list, resources,
     * bonuses) and the left menu, and sets $pagetime.
     */
    public function testPageHeaderRendersHeaderLeftMenuAndRedirect() : void
    {
        global $GlobalUni, $GlobalUser, $aktplanet, $pagetime, $session;

        $this->buildFixture();
        $user = $this->loginAs(1);
        $session = $user['session'];

        loca_add('common', 'en');
        $GlobalUni = LoadUniverse();
        $this->assertIsArray($GlobalUni);

        $aktplanet = GetUpdatePlanet((int) $GlobalUser['aktplanet'], time());
        $this->assertIsArray($aktplanet);

        $html = $this->capture(fn () => PageHeader('overview', false, true, 'overview', 7));

        $this->assertStringContainsString('<html>', $html);
        $this->assertStringContainsString('css/default.css', $html);
        $this->assertStringContainsString('<title>Uni1 OGame</title>', $html);
        $this->assertStringContainsString('var session="' . $user['session'] . '"', $html);
        // The redirect meta tag carries the delay, the page and the session.
        $this->assertStringContainsString(
            'content="7; URL=index.php?page=overview&session=' . $user['session'] . '&redirect=1"',
            $html
        );
        // Header bar: planet selector, resource bar and bonus icons.
        $this->assertStringContainsString("id='header_top'", $html);
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString("id='resources'", $html);
        $this->assertStringContainsString('img/commander_ikon.gif', $html);
        // Left menu (the menu loca section is not loaded here, so the raw
        // localization keys are rendered).
        $this->assertStringContainsString("id='leftmenu'", $html);
        $this->assertStringContainsString('MENU_UNIVERSE', $html);
        $this->assertStringContainsString('MENU_OVERVIEW', $html);
        // Side effect: the page start timestamp is stored for PageFooter.
        $this->assertGreaterThan(0, $pagetime);
    }

    /**
     * PageHeader with $noheader and $leftmenu disabled only emits the head.
     */
    public function testPageHeaderCanSkipTheHeaderBarAndTheLeftMenu() : void
    {
        global $GlobalUser, $session;

        $this->buildFixture();
        $user = $this->loginAs(1);
        $session = $user['session'];

        $html = $this->capture(fn () => PageHeader('overview', true, false));

        $this->assertStringContainsString('<html>', $html);
        $this->assertStringContainsString('<body', $html);
        $this->assertStringNotContainsString('<select', $html);
        $this->assertStringNotContainsString("id='header_top'", $html);
        $this->assertStringNotContainsString("id='leftmenu'", $html);
        // Without a redirect page no refresh meta tag is emitted.
        $this->assertStringNotContainsString('http-equiv="refresh"', $html);
    }

    /**
     * LeftMenu hides the admin area from regular players and shows it to
     * administrators.
     */
    public function testLeftMenuShowsTheAdminEntryOnlyForAdministrators() : void
    {
        global $GlobalUser, $GlobalUni;

        $this->buildFixture();
        $this->loginAs(1);
        $GlobalUni = LoadUniverse();

        $playerHtml = $this->capture(fn () => LeftMenu());
        $this->assertStringContainsString('MENU_OVERVIEW', $playerHtml);
        $this->assertStringNotContainsString('MENU_ADMIN', $playerHtml);

        $GlobalUser['admin'] = USER_TYPE_ADMIN;
        $adminHtml = $this->capture(fn () => LeftMenu());
        $this->assertStringContainsString('MENU_ADMIN', $adminHtml);
        $this->assertStringContainsString('index.php?page=admin&session=' . $GlobalUser['session'], $adminHtml);
    }

    /**
     * The empire entry needs an active Commander, and the external link entries
     * are only rendered when the universe configures a URL for them.
     */
    public function testLeftMenuDependsOnTheCommanderAndOnConfiguredExternalLinks() : void
    {
        global $GlobalUser, $GlobalUni;

        $this->buildFixture();
        $this->loginAs(1);
        $GlobalUni = LoadUniverse();

        // The fixture has an active Commander and no external links.
        $html = $this->capture(fn () => LeftMenu());
        $this->assertStringContainsString('MENU_EMPIRE', $html);
        $this->assertStringContainsString('page=imperium&session=' . $GlobalUser['session'] . '&planettype=1', $html);
        $this->assertStringNotContainsString('MENU_BOARD', $html);
        $this->assertStringNotContainsString('MENU_DISCORD', $html);
        $this->assertStringNotContainsString('MENU_RULES', $html);
        $this->assertSame(0, substr_count($html, 'target="_blank"'));

        // Without an active Commander the empire entry disappears.
        $GlobalUser['com_until'] = 0;
        $noComa = $this->capture(fn () => LeftMenu());
        $this->assertStringNotContainsString('MENU_EMPIRE', $noComa);

        // A configured external link is rendered with its target and URL.
        $GlobalUni['ext_discord'] = 'https://discord.example/invite';
        $withLink = $this->capture(fn () => LeftMenu());
        $this->assertStringContainsString('href="https://discord.example/invite" target="_blank"', $withLink);
        // The other external entries stay hidden (their URLs are empty).
        $this->assertStringNotContainsString('MENU_BOARD', $withLink);
        $this->assertStringNotContainsString('MENU_TUTORIAL', $withLink);
    }

    /**
     * PageFooter renders the message and error boxes and, for a debugging user,
     * the page generation info plus the SQL query log.
     */
    public function testPageFooterRendersMessagesAndTheDebugBlock() : void
    {
        global $GlobalUser, $GlobalUni, $pagetime, $query_counter, $query_log;

        loca_add('debug', 'en');

        $GlobalUser = array ('player_id' => 1, 'lang' => 'en', 'session' => 'sess1', 'debug' => 1, 'validated' => 1, 'disable' => 0);
        $GlobalUni = array ('num' => 1, 'lang' => 'en');
        $pagetime = microtime(true);
        $query_counter = 3;
        $query_log = "SELECT * FROM test_users<br>\n";

        $html = $this->capture(fn () => PageFooter('Hello message', 'Some error'));

        $this->assertStringContainsString("<div id='messagebox'><center>", $html);
        $this->assertStringContainsString('Hello message', $html);
        $this->assertStringContainsString("<div id='errorbox'><center>", $html);
        $this->assertStringContainsString('Some error', $html);
        $this->assertStringContainsString("messagebox.style.display='block';", $html);
        $this->assertStringContainsString("errorbox.style.display='block';", $html);
        $this->assertStringContainsString('headerHeight = 81;', $html);
        // Debug block: page generation time + query counter + SQL log.
        $this->assertStringContainsString('Page generated in ', $html);
        $this->assertStringContainsString('Number of SQL queries: 3', $html);
        $this->assertStringContainsString('SELECT * FROM test_users', $html);
        $this->assertStringContainsString('Show SQL query log', $html);
        $this->assertStringContainsString('</body></html>', $html);
    }

    /**
     * Without a message and with debug disabled the message box stays hidden;
     * an unvalidated (or deletion-pending) account prepends an error notice.
     */
    public function testPageFooterHandlesEmptyMessageAndInactiveAccounts() : void
    {
        global $GlobalUser, $GlobalUni;

        $GlobalUser = array ('player_id' => 1, 'lang' => 'en', 'session' => 'sess1', 'debug' => 0, 'validated' => 0, 'disable' => 0);
        $GlobalUni = array ('num' => 1, 'lang' => 'en');

        $html = $this->capture(fn () => PageFooter('', 'Original error', true, 0, true));

        // No message -> the message box is not switched on (it stays hidden).
        $this->assertStringNotContainsString("messagebox.style.display='block';", $html);
        $this->assertStringContainsString("errorbox.style.display='block';", $html);
        $this->assertStringContainsString('Original error', $html);
        // The unvalidated account notice is prepended to the error text.
        $this->assertStringContainsString(va(loca('REG_NOT_ACTIVATED'), 'sess1'), $html);
        // No debug block for a non-debugging user.
        $this->assertStringNotContainsString('Show SQL query log', $html);
        // Popup + nores options.
        $this->assertStringContainsString("contentbox.style.left='0px';", $html);
        $this->assertStringContainsString("contentbox.style.width='100%';", $html);
        $this->assertStringContainsString("messagebox.style.top='0px';", $html);
        $this->assertStringContainsString('headerHeight = 0;', $html);

        // An account pending deletion gets the deletion date notice instead.
        $GlobalUser['validated'] = 1;
        $GlobalUser['disable'] = 1;
        $GlobalUser['disable_until'] = 1700000000;

        $disabledHtml = $this->capture(fn () => PageFooter('', ''));

        $this->assertStringContainsString(va(loca('REG_PENDING_DELETE'), date('Y-m-d H:i:s', 1700000000)), $disabledHtml);
        $this->assertStringNotContainsString(va(loca('REG_NOT_ACTIVATED'), 'sess1'), $disabledHtml);
    }

    /**
     * BeginContent/EndContent open and close the content area and run the mod
     * hooks in between.
     */
    public function testBeginAndEndContentWrapTheContentArea() : void
    {
        $html = $this->capture(function () : void {
            BeginContent();
            echo 'PAGE BODY';
            EndContent();
        });

        $this->assertStringContainsString('<!-- CONTENT AREA -->', $html);
        $this->assertStringContainsString("<div id='content'>", $html);
        $this->assertStringContainsString('PAGE BODY', $html);
        $this->assertStringContainsString('<!-- END CONTENT AREA -->', $html);
        $this->assertLessThan(
            strpos($html, '<!-- END CONTENT AREA -->'),
            strpos($html, 'PAGE BODY')
        );
    }

    /**
     * InvalidSessionPage stores the error row and shows its database id.
     */
    public function testInvalidSessionPageLogsTheErrorAndShowsItsId() : void
    {
        global $GlobalUser;

        $this->addMinimalUniverse();

        $GlobalUser = array ('player_id' => 42, 'lang' => 'en', 'session' => 'bogus');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.9';
        $_SERVER['HTTP_USER_AGENT'] = 'Intruder/1.0';
        $_SERVER['REQUEST_URI'] = '/game/index.php?page=overview&session=bogus';
        $before = time();

        $html = $this->capture(fn () => InvalidSessionPage());

        $row = $this->fetchRow('SELECT * FROM test_errors ORDER BY error_id DESC LIMIT 1');
        $this->assertNotNull($row);
        $this->assertSame(1, $this->countRows('errors'));
        $this->assertSame(42, (int) $row['owner_id']);
        $this->assertSame('10.0.0.9', $row['ip']);
        $this->assertSame('Intruder/1.0', $row['agent']);
        $this->assertSame('/game/index.php?page=overview&session=bogus', $row['url']);
        $this->assertSame(loca('REG_SESSION_INVALID'), $row['text']);
        $this->assertGreaterThanOrEqual($before, (int) $row['date']);
        $this->assertLessThanOrEqual(time(), (int) $row['date']);

        // The page shows the id of the stored row.
        $this->assertStringContainsString('Error-ID: ' . $row['error_id'], $html);
        $this->assertStringContainsString(loca('REG_SESSION_ERROR'), $html);
    }

    /**
     * The Page base class provides a default controller() and an empty view().
     */
    public function testPageBaseClassDefaultsToAnEmptyViewAndAShownController() : void
    {
        $page = new class extends Page {};

        $this->assertTrue($page->controller());

        $html = $this->capture(fn () => $page->view());
        $this->assertSame('', $html);
    }

    // ========================================================================
    // game/core/debug.php: Debug
    // ========================================================================

    /**
     * Debug() stores the message with the request context and escapes the
     * quotes/backticks for the HTML report.
     */
    public function testDebugStoresTheEscapedMessageWithRequestContext() : void
    {
        global $GlobalUser, $from_cron;

        $from_cron = false;
        $GlobalUser = array ('player_id' => 42);
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        $_SERVER['HTTP_USER_AGENT'] = 'DebugAgent/1.0';
        $_SERVER['REQUEST_URI'] = '/game/index.php?page=notes';
        $before = time();

        Debug('He said "hi" and \'bye\' with `tick`');

        $row = $this->fetchRow('SELECT * FROM test_debug ORDER BY error_id DESC LIMIT 1');
        $this->assertNotNull($row);
        $this->assertSame(1, $this->countRows('debug'));
        $this->assertSame(42, (int) $row['owner_id']);
        $this->assertSame('203.0.113.7', $row['ip']);
        $this->assertSame('DebugAgent/1.0', $row['agent']);
        $this->assertSame('/game/index.php?page=notes', $row['url']);
        $this->assertSame(
            'He said &quot;hi&quot; and &rsquo;bye&rsquo; with &lsquo;tick&lsquo;',
            $row['text']
        );
        $this->assertGreaterThanOrEqual($before, (int) $row['date']);
        $this->assertLessThanOrEqual(time(), (int) $row['date']);
    }

    /**
     * When the request comes from the cron scheduler the visitor fields are
     * replaced by the cron placeholders.
     */
    public function testDebugUsesTheCronContextWhenRunFromCron() : void
    {
        global $GlobalUser, $from_cron;

        $from_cron = true;
        $GlobalUser = array ('player_id' => 7);

        Debug('cron tick');

        $row = $this->fetchRow('SELECT * FROM test_debug ORDER BY error_id DESC LIMIT 1');
        $this->assertNotNull($row);
        $this->assertSame(7, (int) $row['owner_id']);
        $this->assertSame('0.0.0.0', $row['ip']);
        $this->assertSame('cron', $row['agent']);
        $this->assertSame('cron.php', $row['url']);
        $this->assertSame('cron tick', $row['text']);

        $from_cron = false;
    }

    /**
     * Debug() silently returns when no user is logged in. Note that the check
     * is a loose null comparison, so an empty user array is treated exactly
     * like null.
     */
    public function testDebugDoesNothingWithoutALoggedInUser() : void
    {
        global $GlobalUser;

        $GlobalUser = null;
        Debug('no user');
        $this->assertSame(0, $this->countRows('debug'));

        $GlobalUser = array ();
        Debug('still no user');
        $this->assertSame(0, $this->countRows('debug'));
    }

    // ========================================================================
    // game/core/debug.php: BackTrace / BrowseHistory
    // ========================================================================

    /**
     * BackTrace() lists the callers and skips frames coming from db.php.
     */
    public function testBackTraceListsFunctionsAndSkipsTheDbLayer() : void
    {
        $trace = BackTrace();

        $this->assertStringContainsString('function=BackTrace', $trace);
        $this->assertStringContainsString('function=testBackTrace', $trace);
        $this->assertStringContainsString('file=PageDebugCoreTest.php', $trace);
        $this->assertStringNotContainsString('file=db.php', $trace);
        // Each frame is indented with one more &nbsp; than the previous one.
        $this->assertGreaterThanOrEqual(3, substr_count($trace, '&nbsp;'));
        $this->assertStringContainsString('<br>', $trace);
    }

    /**
     * BrowseHistory() does nothing unless the user has sniffing enabled.
     */
    public function testBrowseHistoryWithoutSniffingStoresNothing() : void
    {
        global $GlobalUser;

        $GlobalUser = array ('player_id' => 1);
        $_GET = array ('page' => 'overview');
        BrowseHistory();
        $this->assertSame(0, $this->countRows('browse'));

        $GlobalUser = array ('player_id' => 1, 'sniff' => 0);
        BrowseHistory();
        $this->assertSame(0, $this->countRows('browse'));
    }

    /**
     * With sniffing enabled the serialized GET/POST data and the request
     * method are logged.
     */
    public function testBrowseHistoryStoresTheSerializedRequestData() : void
    {
        global $GlobalUser;

        $GlobalUser = array ('player_id' => 9, 'sniff' => 1);
        $_GET = array ('page' => 'overview', 'cp' => '5');
        $_POST = array ('fleet' => '5');
        $_SERVER['REQUEST_URI'] = '/game/index.php?page=overview&cp=5';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        BrowseHistory();

        $row = $this->fetchRow('SELECT * FROM test_browse ORDER BY log_id DESC LIMIT 1');
        $this->assertNotNull($row);
        $this->assertSame(1, $this->countRows('browse'));
        $this->assertSame(9, (int) $row['owner_id']);
        $this->assertSame('/game/index.php?page=overview&cp=5', $row['url']);
        $this->assertSame('POST', $row['method']);
        $this->assertSame(serialize($_GET), $row['getdata']);
        $this->assertSame(serialize($_POST), $row['postdata']);
    }

    // ========================================================================
    // game/core/debug.php: IP log and user log
    // ========================================================================

    /**
     * LogIPAddress() stores the IP, the user and the registration flag.
     */
    public function testLogIPAddressStoresTheRegistrationFlag() : void
    {
        $before = time();

        LogIPAddress('198.51.100.4', 7);
        LogIPAddress('198.51.100.4', 7, 1);

        $rows = $this->fetchRows('SELECT * FROM test_iplogs ORDER BY log_id ASC');
        $this->assertCount(2, $rows);
        $this->assertSame('198.51.100.4', $rows[0]['ip']);
        $this->assertSame(7, (int) $rows[0]['user_id']);
        $this->assertSame(0, (int) $rows[0]['reg']);
        $this->assertSame(1, (int) $rows[1]['reg']);
        $this->assertGreaterThanOrEqual($before, (int) $rows[0]['date']);
        $this->assertLessThanOrEqual(time(), (int) $rows[1]['date']);
    }

    /**
     * GetLastRegistrationByIP() returns 0 when the IP is unknown or has no
     * registration row.
     */
    public function testGetLastRegistrationByIPReturnsZeroWithoutARegistration() : void
    {
        // Empty table.
        $this->assertSame(0, GetLastRegistrationByIP('192.0.2.1'));

        // A visit row (reg = 0) is not a registration.
        AddDBRow(array ('ip' => '192.0.2.1', 'user_id' => 1, 'reg' => 0, 'date' => 1700000000), 'iplogs');
        $this->assertSame(0, GetLastRegistrationByIP('192.0.2.1'));

        // A registration from another IP must not leak into this one.
        AddDBRow(array ('ip' => '192.0.2.2', 'user_id' => 2, 'reg' => 1, 'date' => 1700000000), 'iplogs');
        $this->assertSame(0, GetLastRegistrationByIP('192.0.2.1'));

        // Round trip through the public logger.
        LogIPAddress('203.0.113.55', 3, 1);
        $this->assertGreaterThan(0, GetLastRegistrationByIP('203.0.113.55'));
    }

    /**
     * GetLastRegistrationByIP() returns the newest registration timestamp of
     * the given IP, ignoring newer non-registration rows.
     */
    public function testGetLastRegistrationByIPReturnsTheNewestRegistration() : void
    {
        AddDBRow(array ('ip' => '192.0.2.9', 'user_id' => 1, 'reg' => 1, 'date' => 1700000000), 'iplogs');
        AddDBRow(array ('ip' => '192.0.2.9', 'user_id' => 1, 'reg' => 1, 'date' => 1700000500), 'iplogs');
        // Newer, but only a visit.
        AddDBRow(array ('ip' => '192.0.2.9', 'user_id' => 1, 'reg' => 0, 'date' => 1700009999), 'iplogs');
        // Newer registration of a different IP.
        AddDBRow(array ('ip' => '192.0.2.8', 'user_id' => 2, 'reg' => 1, 'date' => 1900000000), 'iplogs');

        $this->assertSame(1700000500, GetLastRegistrationByIP('192.0.2.9'));
        $this->assertSame(1900000000, GetLastRegistrationByIP('192.0.2.8'));
    }

    /**
     * GetLastRegistrationByIP() interpolates its argument into the SQL string
     * without escaping it, so a quote in the address breaks out of the string
     * literal. The current behaviour is documented here: for an address that
     * does not exist the query still matches, because the injected
     * "OR '1'='1'" makes the WHERE clause true for every registration row and
     * the (unquoted) search address is ignored.
     *
     * SUSPECTED BUG (reported, not fixed): the address should be escaped (like
     * every other user-supplied string in the code base, e.g. with
     * addslashes() or a prepared statement).
     */
    public function testGetLastRegistrationByIPDoesNotEscapeTheAddress() : void
    {
        AddDBRow(array ('ip' => '198.51.100.1', 'user_id' => 1, 'reg' => 1, 'date' => 1700000123), 'iplogs');

        // The address does not exist, yet the registration of another IP is
        // returned because of the injected OR branch.
        $this->assertSame(1700000123, GetLastRegistrationByIP("192.0.2.1' OR '1'='1"));
    }

    /**
     * UserLog() stores the entry and prunes entries older than two weeks
     * (relative to the given timestamp); the two-week boundary itself survives.
     */
    public function testUserLogStoresTheEntryAndPrunesOldRows() : void
    {
        $when = 1700000000;
        $twoWeeks = 2 * 7 * 24 * 60 * 60;

        AddDBRow(array ('owner_id' => 1, 'date' => $when - $twoWeeks - 1, 'type' => 'old', 'text' => 'too old'), 'userlogs');
        AddDBRow(array ('owner_id' => 1, 'date' => $when - $twoWeeks, 'type' => 'edge', 'text' => 'exactly at the limit'), 'userlogs');
        AddDBRow(array ('owner_id' => 2, 'date' => $when - 999999999, 'type' => 'other', 'text' => 'other player, too old'), 'userlogs');

        UserLog(1, 'Login', 'Player logged in', $when);

        $rows = $this->fetchRows('SELECT * FROM test_userlogs ORDER BY id ASC');
        $this->assertCount(2, $rows);
        // The row exactly at the two-week boundary is kept.
        $this->assertSame('exactly at the limit', $rows[0]['text']);
        $this->assertSame('edge', $rows[0]['type']);
        $this->assertSame((int) ($when - $twoWeeks), (int) $rows[0]['date']);
        // The new entry.
        $this->assertSame(1, (int) $rows[1]['owner_id']);
        $this->assertSame($when, (int) $rows[1]['date']);
        $this->assertSame('Login', $rows[1]['type']);
        $this->assertSame('Player logged in', $rows[1]['text']);
        // Pruning is global: the other player's stale row is gone as well.
        $this->assertSame(0, $this->countRows('userlogs', "text = 'too old'"));
        $this->assertSame(0, $this->countRows('userlogs', 'owner_id = 2'));
    }

    /**
     * With $when = 0 UserLog() uses the current time.
     */
    public function testUserLogUsesTheCurrentTimeWhenNoTimestampIsGiven() : void
    {
        $before = time();

        UserLog(5, 'Click', 'Something happened');

        $row = $this->fetchRow('SELECT * FROM test_userlogs ORDER BY id DESC LIMIT 1');
        $this->assertNotNull($row);
        $this->assertSame(5, (int) $row['owner_id']);
        $this->assertGreaterThanOrEqual($before, (int) $row['date']);
        $this->assertLessThanOrEqual(time(), (int) $row['date']);
    }

    // ========================================================================
    // game/core/debug.php: SecurityCheck / Hacking / SQL log
    // ========================================================================

    /**
     * SecurityCheck() accepts text matching the pattern and writes nothing.
     * (The failure path calls Error(), which terminates the process and is
     * therefore not testable here.)
     */
    public function testSecurityCheckAcceptsMatchingText() : void
    {
        SecurityCheck('/^[0-9]+$/', '12345', ' for player 1');
        SecurityCheck('/^[a-z]+$/', 'abc', '');
        SecurityCheck('/^$/', '', 'empty input is valid for this pattern');

        $this->assertSame(0, $this->countRows('errors'));
        $this->assertSame(0, $this->countRows('debug'));
    }

    /**
     * Hacking() logs the GET/POST data and the method, and increments the
     * universe's tamper counter.
     */
    public function testHackingLogsTheRequestAndIncrementsTheCounter() : void
    {
        global $GlobalUser, $GlobalUni;

        $this->addMinimalUniverse();
        loca_add('debug', 'en');

        $GlobalUser = array ('player_id' => 1, 'session' => 'sess1');
        $GlobalUni = LoadUniverse();
        $_GET = array ('tid' => 3);
        $_POST = array ('fleet' => 5);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        Hacking('DEBUG_SECURITY_BREACH');

        $row = $this->fetchRow('SELECT * FROM test_debug ORDER BY error_id DESC LIMIT 1');
        $this->assertNotNull($row);
        $this->assertStringContainsString('HACKING ATTEMPT: ', $row['text']);
        $this->assertStringContainsString(loca_lang('DEBUG_SECURITY_BREACH', 'en'), $row['text']);
        $this->assertStringContainsString('GET LIST:', $row['text']);
        $this->assertStringContainsString('tid = [3]', $row['text']);
        $this->assertStringContainsString('POST LIST:', $row['text']);
        $this->assertStringContainsString('fleet = [5]', $row['text']);
        $this->assertStringContainsString('METHOD: POST', $row['text']);

        // The universe hack counter is incremented (the fixture starts at 0).
        $uni = $this->fetchRow('SELECT hacks FROM test_uni');
        $this->assertSame(1, (int) $uni['hacks']);

        // A second attempt increments the counter again.
        Hacking('DEBUG_SECURITY_BREACH');
        $uni = $this->fetchRow('SELECT hacks FROM test_uni');
        $this->assertSame(2, (int) $uni['hacks']);
    }

    /**
     * GetSQLQueryLogText() returns the SQL log popup markup with the recorded
     * queries embedded.
     */
    public function testGetSQLQueryLogTextEmbedsTheQueryLog() : void
    {
        global $query_log;

        $query_log = "SELECT * FROM test_users<br>\n";
        $html = GetSQLQueryLogText();

        $this->assertStringContainsString('Show SQL query log', $html);
        $this->assertStringContainsString('class="sql_overlay"', $html);
        $this->assertStringContainsString('class="sql_popup"', $html);
        $this->assertStringContainsString('<h2>SQL Query Log</h2>', $html);
        $this->assertStringContainsString('SELECT * FROM test_users', $html);

        // An empty log still renders the popup container.
        $query_log = '';
        $empty = GetSQLQueryLogText();
        $this->assertStringContainsString('sql_content', $empty);
        $this->assertStringNotContainsString('SELECT * FROM test_users', $empty);
    }
}
