<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the original OGame 0.84 missile (RocketAttack) algorithm in
 * game/core/raketen.php.
 *
 * The algorithm:
 *  - each anti-ballistic missile (ABM) destroys exactly one incoming
 *    interplanetary missile (IPM);
 *  - the surviving IPMs deal damage = IPM_count * IPM_attack *
 *    (1 + weapon_tech / 10) to the target's defensive structures;
 *  - the primary target is hit first, then the rest in order, and leftover
 *    damage carries over;
 *  - the defender's stored missiles (ABM/IPM) are never a damage target.
 *
 * Reference values (weapon/armor tech = 0, IPM attack = 12000, hitpoints of a
 * defense = structure * (1 + 0.1 * armor) / 10):
 *  - a Rocket Launcher has 2000 structure -> 200 hitpoints -> 12000/200 = 60
 *    of them are destroyed by a single IPM.
 */
// The game core modules assign global variables ($UnitParam, $defmap, ...) at
// their top level; only the process-isolated child loads the bootstrap at the
// true top level, so the globals are available (same as GoldenPagesTest).
#[RunTestsInSeparateProcesses]
class RocketAttackTest extends TestCase
{
    /**
     * Build a target defense array with every defense type present (0 by
     * default) so that the algorithm never hits an undefined array key.
     */
    private function makeTarget(array $overrides = []) : array
    {
        $target = array ();
        foreach ($GLOBALS['defmap'] as $gid) {
            $target[$gid] = 0;
        }
        foreach ($overrides as $gid => $count) {
            $target[$gid] = $count;
        }

        return $target;
    }

    public function testInterceptionConsumesABMsOneToOne() : void
    {
        // 10 IPMs against 3 ABMs: 3 IPMs are intercepted, 3 ABMs consumed,
        // the remaining 7 IPMs deal 7*12000 = 84000 damage -> enough to wipe
        // out the 100 rocket launchers (hitpoints 200 each).
        $target = $this->makeTarget([ GID_D_ABM => 3, GID_D_RL => 100 ]);
        $moon = null;

        $ipm_destroyed = RocketAttackMain(10, 0, false, $target, $moon, 0, 0);

        $this->assertSame(3, $ipm_destroyed);
        $this->assertSame(0, $target[GID_D_ABM]);
        $this->assertSame(0, $target[GID_D_RL]);
    }

    public function testDamageOnPlainDefenses() : void
    {
        // A single IPM with no weapon/armor tech destroys 12000/200 = 60
        // rocket launchers, leaving 40.
        $target = $this->makeTarget([ GID_D_RL => 100 ]);
        $moon = null;

        $ipm_destroyed = RocketAttackMain(1, 0, false, $target, $moon, 0, 0);

        $this->assertSame(0, $ipm_destroyed);
        $this->assertSame(40, $target[GID_D_RL]);
    }

    public function testPrimaryTargetIsHitFirst() : void
    {
        // The primary target (Light Laser) is destroyed first (5 of them), the
        // leftover 12000 - 5*200 = 11000 damage then destroys
        // floor(11000/200) = 55 rocket launchers -> 45 remain.
        $target = $this->makeTarget([ GID_D_RL => 100, GID_D_LL => 5 ]);
        $moon = null;

        RocketAttackMain(1, GID_D_LL, false, $target, $moon, 0, 0);

        $this->assertSame(0, $target[GID_D_LL]);
        $this->assertSame(45, $target[GID_D_RL]);
    }

    public function testLeftoverDamageCarriesOver() : void
    {
        // 1 IPM destroys the single Rocket Launcher (200 hitpoints). The
        // leftover is 12000 - 200 = 11800 (the old code leaked an extra
        // damage point per unit, so it was 11799 and destroyed only 58 Light
        // Lasers instead of 59). With the correct algorithm 59 of the 100
        // Light Lasers are destroyed -> 41 remain.
        $target = $this->makeTarget([ GID_D_RL => 1, GID_D_LL => 100 ]);
        $moon = null;

        RocketAttackMain(1, 0, false, $target, $moon, 0, 0);

        $this->assertSame(0, $target[GID_D_RL]);
        $this->assertSame(41, $target[GID_D_LL]);
    }

    public function testStoredMissilesAreNotDamaged() : void
    {
        // Even with a huge leftover damage pool, the stored missiles are never
        // destroyed: ABMs are consumed only by interception and stored IPMs
        // are left untouched.
        $target = $this->makeTarget([ GID_D_ABM => 10, GID_D_IPM => 5 ]);
        $moon = null;

        $ipm_destroyed = RocketAttackMain(100, 0, false, $target, $moon, 0, 0);

        $this->assertSame(10, $ipm_destroyed);
        $this->assertSame(0, $target[GID_D_ABM]);
        $this->assertSame(5, $target[GID_D_IPM]);
    }
}
