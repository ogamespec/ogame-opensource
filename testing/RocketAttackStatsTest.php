<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Tests for issue #145 "small bug on statistics update after missile attack".
 *
 * When a missile attack lands, the statistics (score1) of both players must
 * be updated immediately:
 *  - the attacker loses the resource value of the launched IPMs (25000 points
 *    per missile: 12500 metal + 2500 crystal + 10000 deuterium),
 *  - the defender loses the resource value of the destroyed defenses, of the
 *    anti-ballistic missiles consumed by interception, and of the stored
 *    missiles destroyed by the leftover damage.
 *
 * Before the fix the scores were corrected only by a later full
 * recalculation (on login / queue processing).
 *
 * Fixture layout used by the tests:
 *  - PlayerOne (id 1) owns planet 1 at 1:1:4 (the attacker, weapon tech 3);
 *  - PlayerTwo (id 2) owns planet 4 at 1:3:4 (the defender, armor tech 3) and
 *    the moon at the same coordinates.
 */
#[RunTestsInSeparateProcesses]
class RocketAttackStatsTest extends TestCase
{
    private FixtureBuilder $fixture;

    protected function setUp(): void
    {
        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');

        global $GlobalUni, $db_prefix;
        $GlobalUni = $this->fixture->getUniData();
        $db_prefix = $this->fixture->getDbPrefix();

        // Start both players with a large score so the expected losses can be
        // asserted directly (a single IPM is worth 25000 points). The fixture
        // leaves banned/admin NULL, while AdjustStats() only touches regular
        // players (banned = 0 AND admin = 0), so set them explicitly.
        dbquery ("UPDATE {$db_prefix}users SET score1 = 1000000, banned = 0, admin = 0 WHERE player_id IN (1, 2)");
        InvalidateUserCache ();
    }

    /**
     * Read the current score1 of a player straight from the database.
     */
    private function scoreOf (int $player_id) : int
    {
        InvalidateUserCache ();
        $user = LoadUser ($player_id);
        return (int)$user['score1'];
    }

    /**
     * Store the given defense amounts on a planet/moon.
     */
    private function setDefenses (int $planet_id, array $defenses) : void
    {
        $planet = LoadPlanetById ($planet_id);
        foreach ( $defenses as $gid => $count ) {
            $planet[$gid] = $count;
        }
        SetPlanetDefense ($planet_id, $planet);
    }

    /**
     * Launch $ipm_amount missiles from PlayerOne's home planet (1:1:4) at the
     * given target and resolve the attack immediately.
     */
    private function launchAndResolve (int $ipm_amount, int $target_planet_id) : void
    {
        $origin = LoadPlanetById (1);
        $origin[GID_D_IPM] = $ipm_amount;
        SetPlanetDefense (1, $origin);

        $target = LoadPlanetById ($target_planet_id);
        $fleet_id = LaunchRockets ($origin, $target, 100, $ipm_amount, 0);
        $this->assertGreaterThan (0, $fleet_id);

        RocketAttack ($fleet_id, $target_planet_id, time () + 100);
    }

    public function testStatsAfterPlanetAttackWithInterceptionAndDestruction() : void
    {
        // PlayerTwo's home planet (1:3:4): 3 rocket launchers and 2 light
        // lasers from the fixture, plus 3 ABMs that intercept 3 of the 10
        // incoming IPMs. The surviving 7 IPMs destroy every defense.
        $this->setDefenses (4, array (GID_D_ABM => 3));
        $this->assertSame (3, (int)LoadPlanetById (4)[GID_D_RL]);
        $this->assertSame (2, (int)LoadPlanetById (4)[GID_D_LL]);

        $this->launchAndResolve (10, 4);

        // Attacker: 10 spent IPMs -> 10 * 25000 = 250000 points.
        $this->assertSame (750000, $this->scoreOf (1));
        // Defender: 3 RL + 2 LL destroyed (3*2000 + 2*2000 = 10000) and
        // 3 ABMs consumed by interception (3*10000 = 30000).
        $this->assertSame (960000, $this->scoreOf (2));
    }

    public function testStatsWhenAllMissilesAreIntercepted() : void
    {
        // 3 ABMs intercept all 3 incoming IPMs: no defense is damaged, but the
        // attacker still loses the spent missiles and the defender loses the
        // consumed ABMs.
        $this->setDefenses (4, array (GID_D_ABM => 3));

        $this->launchAndResolve (3, 4);

        $this->assertSame (925000, $this->scoreOf (1));    // 3 IPMs * 25000
        $this->assertSame (970000, $this->scoreOf (2));    // 3 ABMs * 10000
    }

    public function testStatsAfterMoonAttack() : void
    {
        // PlayerTwo's moon (1:3:4) carries 5 rocket launchers; on a moon
        // attack the interceptors are taken from the planet below (planet 4,
        // 2 ABMs), not from the moon itself.
        $moon = LoadPlanet (1, 3, 4, 3);
        $this->assertIsArray ($moon);
        $moon_id = (int)$moon['planet_id'];
        $this->setDefenses ($moon_id, array (GID_D_RL => 5));
        $this->setDefenses (4, array (GID_D_ABM => 2));

        $this->launchAndResolve (5, $moon_id);

        // Attacker: 5 spent IPMs -> 5 * 25000 = 125000 points.
        $this->assertSame (875000, $this->scoreOf (1));
        // Defender (moon and planet below both belong to PlayerTwo):
        // 2 ABMs consumed by interception (2*10000 = 20000) and the moon's
        // 5 rocket launchers destroyed (5*2000 = 10000).
        $this->assertSame (970000, $this->scoreOf (2));
    }

    public function testStatsWhenLeftoverDamageDestroysStoredMissiles() : void
    {
        // PlayerTwo's planet holds 1 rocket launcher (the fixture's 2 light
        // lasers are cleared) and 2 stored IPMs. PlayerOne's single IPM
        // (weapon tech 3 -> 15600 damage) destroys the rocket launcher (260
        // hitpoints at armor tech 3) and the leftover damage then destroys
        // both stored IPMs (1950 hitpoints each).
        $this->setDefenses (4, array (GID_D_RL => 1, GID_D_LL => 0, GID_D_IPM => 2));

        $this->launchAndResolve (1, 4);

        $this->assertSame (975000, $this->scoreOf (1));    // 1 IPM * 25000
        // Defender: 1 RL (2000) + 2 stored IPMs destroyed (2*25000 = 50000).
        $this->assertSame (948000, $this->scoreOf (2));
    }
}
