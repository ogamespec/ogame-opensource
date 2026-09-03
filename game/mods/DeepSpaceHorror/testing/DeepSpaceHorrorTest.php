<?php

// Local unit tests for the Deep Space Horror modification.
//
// These tests exercise the mod's PURE logic: the custom galaxy object / image
// hooks, the mission and loot tables, and the portal coordinate movement rules
// for the three leviathans. They do not touch the database, so they run in the
// shared PHPUnit process together with the other tests in this suite.
//
// This suite lives inside the modification (game/mods/DeepSpaceHorror/testing)
// and is run with its own phpunit.xml so it does not pollute the repository's
// main test suite. The game core and the DeepSpaceHorror class are loaded by
// bootstrap.php.

use PHPUnit\Framework\TestCase;

class DeepSpaceHorrorTest extends TestCase
{
    private ?DeepSpaceHorror $mod = null;

    protected function setUp(): void
    {
        // The game core and the mod constants are loaded by the suite bootstrap.
        // The guard lets the class also be required on standalone use.
        if (!class_exists('DeepSpaceHorror')) {
            require_once __DIR__ . '/../main.php';
        }

        // These tests run in the shared PHPUnit process, where the suite
        // bootstrap is not included at the true top level, so the core's
        // file-scope globals ($modlist, $fleetmap, ...) are not visible in the
        // global symbol table. No modifications are loaded here, so expose an
        // empty mod registry for engine helpers that dispatch hooks (e.g. the
        // planet image lookup used by the galaxy overlib).
        $GLOBALS['modlist'] = array();

        $this->mod = new DeepSpaceHorror();
    }

    /**
     * Return the mod instance (asserts it was initialized by setUp).
     */
    private function mod(): DeepSpaceHorror
    {
        $this->assertInstanceOf(DeepSpaceHorror::class, $this->mod);
        return $this->mod;
    }

    /**
     * Invoke a private mod method through reflection.
     */
    private function invokePrivate(object $obj, string $method, array $args = []): mixed
    {
        $ref = new ReflectionMethod(DeepSpaceHorror::class, $method);
        $ref->setAccessible(true);
        return $ref->invoke($obj, ...$args);
    }

    /**
     * Build a DeepSpaceHorror subclass whose Rnd() returns the given values
     * in order, so the random movement branches become deterministic.
     */
    private function deterministicHorror(array $rnd): DeepSpaceHorror
    {
        $obj = new class($rnd) extends DeepSpaceHorror {
            /** @var int[] */
            private array $rndValues;
            private int $idx = 0;

            public function __construct(array $rndValues)
            {
                $this->rndValues = $rndValues;
            }

            protected function Rnd(int $min, int $max): int
            {
                $value = $this->rndValues[$this->idx] ?? $min;
                $this->idx++;
                return $value;
            }
        };
        $this->assertInstanceOf(DeepSpaceHorror::class, $obj);
        return $obj;
    }

    // ========================================================================
    // Custom object images
    // ------------------------------------------------------------------------

    public function testGetPlanetImageForLeviathanTypes(): void
    {
        $cases = array(
            PTYP_LEVI_AMOEBA => 'amoeba.jpg',
            PTYP_LEVI_GUARDIAN => 'guardian.jpg',
            PTYP_LEVI_JUGGERNAUT => 'leviathan.jpg',
            PTYP_LEVI_PORTAL => 'portal.jpg',
        );
        foreach ($cases as $type => $file) {
            $img = array();
            $res = $this->mod()->get_planet_image($type, $img);
            $this->assertTrue($res, "type $type should be handled");
            $this->assertStringContainsString('mods/DeepSpaceHorror/img/', $img['path']);
            $this->assertStringEndsWith($file, $img['path']);
        }
    }

    public function testGetPlanetImageForVanillaTypeReturnsFalse(): void
    {
        $img = array();
        $res = $this->mod()->get_planet_image(PTYP_PLANET, $img);
        $this->assertFalse($res);
        $this->assertSame(array(), $img);
    }

    public function testGetPlanetSmallImageDelegates(): void
    {
        $img = array();
        $res = $this->mod()->get_planet_small_image(PTYP_LEVI_AMOEBA, $img);
        $this->assertTrue($res);
        $this->assertStringContainsString('amoeba.jpg', $img['path']);
    }

    public function testGetObjectImageForLeviathanUnits(): void
    {
        $cases = array(
            GID_LEVI_AMOEBA => 'amoeba.jpg',
            GID_LEVI_GUARDIAN => 'guardian.jpg',
            GID_LEVI_JUGGERNAUT => 'leviathan.jpg',
        );
        foreach ($cases as $gid => $file) {
            $img = array();
            $res = $this->mod()->get_object_image($gid, $img);
            $this->assertTrue($res, "gid $gid should be handled");
            $this->assertStringContainsString($file, $img['path']);
        }
    }

    public function testGetObjectImageForVanillaUnitReturnsFalse(): void
    {
        $img = array();
        $res = $this->mod()->get_object_image(GID_F_LF, $img);
        $this->assertFalse($res);
    }

    // ========================================================================
    // Fleet page planet type lists (target selection, spy ajax)
    // ------------------------------------------------------------------------

    public function testFleet2PlanetTypesIncludeCustomObjects(): void
    {
        $planet_types = array();
        $res = $this->mod()->page_flotten2_planet_types($planet_types);
        $this->assertFalse($res);   // the hook fills the list but does not handle the request
        foreach (array(PTYP_LEVI_PORTAL, PTYP_LEVI_AMOEBA, PTYP_LEVI_GUARDIAN, PTYP_LEVI_JUGGERNAUT) as $type) {
            $this->assertContains($type, $planet_types);
        }
    }

    public function testSpyPlanetTypesIncludeCustomObjects(): void
    {
        $planet_types = array();
        $res = $this->mod()->page_flottenversand_ajax_spy_planets($planet_types);
        $this->assertFalse($res);
        foreach (array(PTYP_LEVI_PORTAL, PTYP_LEVI_AMOEBA, PTYP_LEVI_GUARDIAN, PTYP_LEVI_JUGGERNAUT) as $type) {
            $this->assertContains($type, $planet_types);
        }
    }

    // ========================================================================
    // Galaxy custom object overlib
    // ------------------------------------------------------------------------

    public function testGalaxyCustomObjectForAmoeba(): void
    {
        global $GlobalUser;
        global $session;
        global $aktplanet;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';
        $GlobalUser = array('maxspy' => 0, 'admin' => 0);
        $session = 'test';
        $aktplanet = null;

        $planet = array('name' => 'Амёба', 'type' => PTYP_LEVI_AMOEBA, 'g' => 1, 's' => 2, 'p' => 3,
            'planet_id' => 111, 'owner_id' => USER_SPACE, 'diameter' => LEVI_DIAMETER, 'temp' => LEVI_TEMP);
        $info = array();
        $res = $this->mod()->page_galaxy_custom_object($planet, $info);
        $this->assertTrue($res);
        $this->assertArrayHasKey('overlib', $info);
        $this->assertStringContainsString('Амёба', $info['overlib']);
        $this->assertStringContainsString('[1:2:3]', $info['overlib']);
    }

    public function testGalaxyCustomObjectForPortal(): void
    {
        global $GlobalUser;
        global $session;
        global $aktplanet;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/game/index.php';
        $GlobalUser = array('maxspy' => 0, 'admin' => 0);
        $session = 'test';
        $aktplanet = null;

        $planet = array('name' => 'Portal', 'type' => PTYP_LEVI_PORTAL, 'g' => 4, 's' => 5, 'p' => 6,
            'planet_id' => 222, 'owner_id' => USER_SPACE, 'diameter' => LEVI_PORTAL_DIAMETER, 'temp' => LEVI_PORTAL_TEMP);
        $info = array();
        $res = $this->mod()->page_galaxy_custom_object($planet, $info);
        $this->assertTrue($res);
        $this->assertArrayHasKey('overlib', $info);
    }

    public function testGalaxyCustomObjectIgnoresVanillaPlanets(): void
    {
        $planet = array('name' => 'Home', 'type' => PTYP_PLANET, 'g' => 1, 's' => 1, 'p' => 1);
        $info = array();
        $res = $this->mod()->page_galaxy_custom_object($planet, $info);
        $this->assertFalse($res);
        $this->assertSame(array(), $info);
    }

    // ========================================================================
    // Loot table (resources dropped by each creature)
    // ------------------------------------------------------------------------

    public function testGetLeviLootMatchesDesign(): void
    {
        $this->assertSame(array(GID_RC_DEUTERIUM => 2500000), $this->invokePrivate($this->mod(), 'GetLeviLoot', array(GID_LEVI_AMOEBA)));
        $this->assertSame(array(GID_RC_CRYSTAL => 10000000), $this->invokePrivate($this->mod(), 'GetLeviLoot', array(GID_LEVI_GUARDIAN)));
        $this->assertSame(array(GID_RC_METAL => 40000000), $this->invokePrivate($this->mod(), 'GetLeviLoot', array(GID_LEVI_JUGGERNAUT)));
        $this->assertSame(array(), $this->invokePrivate($this->mod(), 'GetLeviLoot', array(GID_F_LF)));
    }

    public function testLeviTypeFromGidMapping(): void
    {
        $this->assertSame(PTYP_LEVI_AMOEBA, $this->invokePrivate($this->mod(), 'LeviTypeFromGid', array(GID_LEVI_AMOEBA)));
        $this->assertSame(PTYP_LEVI_GUARDIAN, $this->invokePrivate($this->mod(), 'LeviTypeFromGid', array(GID_LEVI_GUARDIAN)));
        $this->assertSame(PTYP_LEVI_JUGGERNAUT, $this->invokePrivate($this->mod(), 'LeviTypeFromGid', array(GID_LEVI_JUGGERNAUT)));
        $this->assertSame(0, $this->invokePrivate($this->mod(), 'LeviTypeFromGid', array(GID_F_SC)));
    }

    public function testIsPlanetLeviathan(): void
    {
        $this->assertTrue($this->invokePrivate($this->mod(), 'IsPlanetLeviathan', array(PTYP_LEVI_AMOEBA)));
        $this->assertTrue($this->invokePrivate($this->mod(), 'IsPlanetLeviathan', array(PTYP_LEVI_GUARDIAN)));
        $this->assertTrue($this->invokePrivate($this->mod(), 'IsPlanetLeviathan', array(PTYP_LEVI_JUGGERNAUT)));
        $this->assertFalse($this->invokePrivate($this->mod(), 'IsPlanetLeviathan', array(PTYP_LEVI_PORTAL)));
        $this->assertFalse($this->invokePrivate($this->mod(), 'IsPlanetLeviathan', array(PTYP_PLANET)));
    }

    // ========================================================================
    // Rnd helper
    // ------------------------------------------------------------------------

    public function testRndRespectsBounds(): void
    {
        $this->assertSame(5, $this->invokePrivate($this->mod(), 'Rnd', array(5, 5)));
        $this->assertSame(1, $this->invokePrivate($this->mod(), 'Rnd', array(1, 1)));
    }

    // ========================================================================
    // Portal coordinate movement rules (with a deterministic Rnd)
    // ------------------------------------------------------------------------

    /**
     * Amoeba: 70% of the time only the position (P) changes within 1-15.
     */
    public function testAmoebaMoveChangesOnlyPosition(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 9, 'systems' => 499);

        $mod = $this->deterministicHorror(array(50, 11));   // 50 <= 70 => P-only; P = 11
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_AMOEBA, array('g' => 3, 's' => 7, 'p' => 4)));
        $this->assertSame(array('g' => 3, 's' => 7, 'p' => 11), $coords);
    }

    /**
     * Amoeba: the rare branch may change the galaxy (5%) and the system (25%).
     */
    public function testAmoebaMoveRareGalaxyAndSystemChange(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 9, 'systems' => 499);

        // 90 (>70) -> else branch; 3 (<=5) => galaxy changes by +1; 10 (<=25) => system changes by -3; 8 => P = 8.
        $mod = $this->deterministicHorror(array(90, 3, 1, 10, -3, 8));
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_AMOEBA, array('g' => 3, 's' => 100, 'p' => 5)));
        $this->assertSame(array('g' => 4, 's' => 97, 'p' => 8), $coords);
    }

    /**
     * Amoeba: the galaxy delta is clamped to the universe bounds.
     */
    public function testAmoebaMoveClampsGalaxyAtEdge(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 3, 'systems' => 499);

        // 90 (>70); 5 (<=5) => galaxy delta -1 from g=1 -> clamped to 1; 90 (>25) => system unchanged; 4 => P = 4.
        $mod = $this->deterministicHorror(array(90, 5, -1, 90, 4));
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_AMOEBA, array('g' => 1, 's' => 200, 'p' => 7)));
        $this->assertSame(array('g' => 1, 's' => 200, 'p' => 4), $coords);
    }

    /**
     * Guardian: moves one position forward, from the edge of the system it
     * crosses into the next system and reverses the travel direction.
     */
    public function testGuardianMoveWalksPositionByPosition(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 9, 'systems' => 499);

        $mod = $this->deterministicHorror(array());
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_GUARDIAN, array('g' => 2, 's' => 300, 'p' => 5)));
        $this->assertSame(array('g' => 2, 's' => 300, 'p' => 6), $coords);
    }

    public function testGuardianMoveEntersNextSystem(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 9, 'systems' => 499);

        // g=1 (odd -> moving backwards), p=15 -> next position wraps to p=1,
        // system drops below 1 -> galaxy 2 starts moving forward from s=1.
        $mod = $this->deterministicHorror(array());
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_GUARDIAN, array('g' => 1, 's' => 1, 'p' => 15)));
        $this->assertSame(array('g' => 2, 's' => 1, 'p' => 1), $coords);
    }

    public function testGuardianMoveReversesDirectionInEvenGalaxy(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 9, 'systems' => 499);

        // g=2 (even -> moving forward), p=15 at the last system -> galaxy 3
        // (odd) starts moving backwards from the last system s=499.
        $mod = $this->deterministicHorror(array());
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_GUARDIAN, array('g' => 2, 's' => 499, 'p' => 15)));
        $this->assertSame(array('g' => 3, 's' => 499, 'p' => 1), $coords);
    }

    public function testGuardianMoveWrapsGalaxies(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 3, 'systems' => 499);

        // g=3 (odd -> moving backwards), first system: crossing s=0 wraps the
        // galaxy to 1 and the guardian keeps moving backwards from s=499.
        $mod = $this->deterministicHorror(array());
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_GUARDIAN, array('g' => 3, 's' => 1, 'p' => 15)));
        $this->assertSame(array('g' => 1, 's' => 499, 'p' => 1), $coords);
    }

    /**
     * Guardian: moving backwards from the last system it stays inside the same
     * galaxy (no premature wrap) until it reaches the first system.
     */
    public function testGuardianMoveBackwardsInsideGalaxy(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 3, 'systems' => 499);

        $mod = $this->deterministicHorror(array());
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_GUARDIAN, array('g' => 3, 's' => 499, 'p' => 15)));
        $this->assertSame(array('g' => 3, 's' => 498, 'p' => 1), $coords);
    }

    /**
     * Juggernaut: 40% of the time it stays in its galaxy and jumps to a random
     * system near the galaxy center.
     */
    public function testJuggernautMoveStaysInGalaxy(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 9, 'systems' => 400);

        // 90 (>60) => stays in g=3; system roll 150 (center 200 +- 100); position 9.
        $mod = $this->deterministicHorror(array(90, 150, 9));
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_JUGGERNAUT, array('g' => 3, 's' => 100, 'p' => 5)));
        $this->assertSame(array('g' => 3, 's' => 150, 'p' => 9), $coords);
    }

    /**
     * Juggernaut: 60% of the time it jumps into a random galaxy.
     */
    public function testJuggernautMoveJumpsToRandomGalaxy(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 9, 'systems' => 400);

        // 50 (<=60) => galaxy jump; roll 7 => g=7; system roll 250; position 12.
        $mod = $this->deterministicHorror(array(50, 7, 250, 12));
        $coords = $this->invokePrivate($mod, 'DeterminePortalCoords', array(GID_LEVI_JUGGERNAUT, array('g' => 3, 's' => 100, 'p' => 5)));
        $this->assertSame(array('g' => 7, 's' => 250, 'p' => 12), $coords);
    }

    /**
     * Statistical sanity: repeated real-random moves always produce valid
     * universe coordinates.
     */
    public function testPortalCoordsAlwaysStayInUniverse(): void
    {
        global $GlobalUni;
        $GlobalUni = array('galaxies' => 5, 'systems' => 300);

        for ($i = 0; $i < 300; $i++) {
            foreach (array(GID_LEVI_AMOEBA, GID_LEVI_GUARDIAN, GID_LEVI_JUGGERNAUT) as $gid) {
                $coords = $this->invokePrivate($this->mod(), 'DeterminePortalCoords', array($gid, array('g' => 3, 's' => 150, 'p' => 8)));
                $this->assertGreaterThanOrEqual(1, $coords['g']);
                $this->assertLessThanOrEqual(5, $coords['g']);
                $this->assertGreaterThanOrEqual(1, $coords['s']);
                $this->assertLessThanOrEqual(300, $coords['s']);
                $this->assertGreaterThanOrEqual(1, $coords['p']);
                $this->assertLessThanOrEqual(15, $coords['p']);
            }
        }
    }
}
