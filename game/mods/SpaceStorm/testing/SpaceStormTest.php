<?php

// Local unit tests for the Space Storm modification.
//
// These tests exercise the mod's PURE logic hooks (production, fleet and
// battle-stat modifiers) with the storm mask and per-planet stabilizer state
// supplied directly. They do not touch the database, so they run in the
// shared PHPUnit process together with the other tests in this suite.
//
// This suite lives inside the modification (game/mods/SpaceStorm/testing) and
// is run with its own phpunit.xml so it does not pollute the repository's main
// test suite. The game core and the SpaceStorm class are loaded by bootstrap.php.

use PHPUnit\Framework\TestCase;

class SpaceStormTest extends TestCase
{
    private ?SpaceStorm $mod = null;

    protected function setUp(): void
    {
        // The game core and the mod constants are loaded by the suite bootstrap.
        // The guard lets the class also be required on standalone use.
        if (!class_exists('SpaceStorm')) {
            require_once __DIR__ . '/../main.php';
        }
        $this->mod = new SpaceStorm();
    }

    /**
     * Return the mod instance (asserts it was initialized by setUp).
     */
    private function mod(): SpaceStorm
    {
        $this->assertInstanceOf(SpaceStorm::class, $this->mod);
        return $this->mod;
    }

    /**
     * Set the active storm mask. The mod reads it from $GlobalUni['storm'].
     */
    private function setStorm(int $mask): void
    {
        global $GlobalUni;
        if (!is_array($GlobalUni)) {
            $GlobalUni = array();
        }
        $GlobalUni['storm'] = $mask;
    }

    // ========================================================================
    // bonus_prod -- production modifiers
    // ------------------------------------------------------------------------

    public function testBonusProdQuantumDriveDeuterium(): void
    {
        $this->setStorm(SPACE_STORM_MASK_QUANTUM_DRIVE);
        $bonus = array();
        $this->mod()->bonus_prod(array('rc' => GID_RC_DEUTERIUM, 'planet' => array()), $bonus);

        $this->assertCount(1, $bonus);
        $this->assertEqualsWithDelta(1 + SPACE_STORM_QUANTUM_DRIVE_BASE_BONUS, $bonus[0], 1e-6);
    }

    public function testBonusProdEnergyCollapsePenalty(): void
    {
        $this->setStorm(SPACE_STORM_MASK_ENERGY_COLLAPSE);
        $bonus = array();
        $this->mod()->bonus_prod(array('rc' => GID_RC_ENERGY, 'planet' => array()), $bonus);

        $this->assertCount(1, $bonus);
        $this->assertEqualsWithDelta(1 - SPACE_STORM_ENERGY_COLLAPSE_BASE_PENALTY, $bonus[0], 1e-6);
    }

    public function testBonusProdStabilizerEnergyBonus(): void
    {
        // Even without a storm, a level-10 stabilizer gives +5% energy.
        $this->setStorm(0);
        $planet = array(
            'planet_id' => 1,
            GID_B_REALITY_STAB => 10,
            's' . GID_B_REALITY_STAB => 0,
        );
        $bonus = array();
        $this->mod()->bonus_prod(array('rc' => GID_RC_ENERGY, 'planet' => $planet), $bonus);

        $this->assertCount(1, $bonus);
        // Each bonus[] entry is a multiplicative factor: +0.5% per level => 1.05.
        $this->assertEqualsWithDelta(1 + 0.005 * 10, $bonus[0], 1e-6);
    }

    public function testBonusProdEnergyCollapseStabilizerCounter(): void
    {
        // A level-10 stabilizer imprinted with Energy Collapse fully cancels
        // the -40% penalty (0.40 - 0.04*10 = 0).
        $this->setStorm(SPACE_STORM_MASK_ENERGY_COLLAPSE);
        $planet = array(
            'planet_id' => 1,
            GID_B_REALITY_STAB => 10,
            's' . GID_B_REALITY_STAB => SPACE_STORM_MASK_ENERGY_COLLAPSE,
        );
        $bonus = array();
        $this->mod()->bonus_prod(array('rc' => GID_RC_ENERGY, 'planet' => $planet), $bonus);

        // factors: stabilizer base energy bonus (1.05) + cancelled collapse (1.0)
        $this->assertCount(2, $bonus);
        $this->assertEqualsWithDelta(1.0, $bonus[1], 1e-6);
    }

    public function testBonusProdDoesNotAffectWithoutStorm(): void
    {
        // No storm, no stabilizer: no production modifiers added.
        $this->setStorm(0);
        $bonus = array();
        $this->mod()->bonus_prod(array('rc' => GID_RC_DEUTERIUM, 'planet' => array()), $bonus);
        $this->assertCount(0, $bonus);
    }

    // ========================================================================
    // bonus_fleet_cons -- fuel modifiers
    // ------------------------------------------------------------------------

    public function testBonusFleetConsQuantumDriveDoublesFuel(): void
    {
        $this->setStorm(SPACE_STORM_MASK_QUANTUM_DRIVE);
        $bonus = array('value' => 100);
        $this->mod()->bonus_fleet_cons(array('planet' => array()), $bonus);

        $this->assertSame(200.0, $bonus['value']);
    }

    public function testBonusFleetConsQuantumStabilizerCounter(): void
    {
        // Level-10 stabilizer imprinted with Quantum Drive: +2x becomes max(1, 2-0.8)=1.2.
        $this->setStorm(SPACE_STORM_MASK_QUANTUM_DRIVE);
        $planet = array(
            GID_B_REALITY_STAB => 10,
            's' . GID_B_REALITY_STAB => SPACE_STORM_MASK_QUANTUM_DRIVE,
        );
        $bonus = array('value' => 100);
        $this->mod()->bonus_fleet_cons(array('planet' => $planet), $bonus);

        $this->assertEqualsWithDelta(120.0, $bonus['value'], 1e-6);
    }

    // ========================================================================
    // bonus_fleet_speed -- speed modifiers
    // ------------------------------------------------------------------------

    public function testBonusFleetSpeedSubspaceTurbulencePenaltyBound(): void
    {
        $this->setStorm(SPACE_STORM_MASK_SUBSPACE_TURB);

        // The penalty is random in [30%, 50%]; the factor must stay in range.
        for ($i = 0; $i < 200; $i++) {
            $bonus = array('value' => 1000);
            $this->mod()->bonus_fleet_speed(array('planet' => array()), $bonus);
            $this->assertGreaterThanOrEqual(1000 * (1 - 0.50), $bonus['value']);
            $this->assertLessThanOrEqual(1000 * (1 - 0.30), $bonus['value']);
        }
    }

    public function testBonusFleetSpeedStabilizerCounter(): void
    {
        // Level-10 stabilizer imprinted with Subspace Turbulence adds +30% speed
        // to counteract the random 30-50% penalty. The resulting factor must be
        // consistently higher than the worst-case no-counter factor (0.50).
        $this->setStorm(SPACE_STORM_MASK_SUBSPACE_TURB);
        $planet = array(
            GID_B_REALITY_STAB => 10,
            's' . GID_B_REALITY_STAB => SPACE_STORM_MASK_SUBSPACE_TURB,
        );

        for ($i = 0; $i < 200; $i++) {
            $bonus = array('value' => 1000);
            $this->mod()->bonus_fleet_speed(array('planet' => $planet), $bonus);
            // penalty in [0.30,0.50] then +30% stabilizer => factor in [0.65, 0.91].
            $this->assertGreaterThanOrEqual(1000 * 0.65, $bonus['value']);
            $this->assertLessThanOrEqual(1000 * 0.91, $bonus['value']);
        }
    }

    // ========================================================================
    // battle_unit_stats -- global battle stat modifiers (frontend hook)
    // ------------------------------------------------------------------------

    public function testBattleUnitStatsNoStormIsIdentity(): void
    {
        $this->setStorm(0);
        $unit = array(
            GID_F_LF => array(4000, 10, 5, 5000, 5000, 10),
            GID_D_RL => array(2000, 20, 80, 0, 0, 0),
        );
        $args = array('attackers' => array(), 'defenders' => array());
        $this->mod()->battle_unit_stats($args, $unit);

        $this->assertSame(4000, $unit[GID_F_LF][0]);
        $this->assertSame(10, $unit[GID_F_LF][1]);
        $this->assertSame(2000, $unit[GID_D_RL][0]);
    }

    public function testBattleUnitStatsPolarShieldDistortion(): void
    {
        $this->setStorm(SPACE_STORM_MASK_POLAR_SHIELD);
        $unit = array(
            GID_F_LF => array(4000, 10, 5, 5000, 5000, 10),
            GID_D_RL => array(2000, 20, 80, 0, 0, 0),
        );
        $args = array('attackers' => array(), 'defenders' => array());
        $this->mod()->battle_unit_stats($args, $unit);

        // armor -20%, shields +30% (attack is untouched).
        $this->assertEqualsWithDelta(4000 * SPACE_STORM_POLAR_ARMOR, $unit[GID_F_LF][0], 1e-6);
        $this->assertEqualsWithDelta(10 * SPACE_STORM_POLAR_SHIELD, $unit[GID_F_LF][1], 1e-6);
        $this->assertEqualsWithDelta(2000 * SPACE_STORM_POLAR_ARMOR, $unit[GID_D_RL][0], 1e-6);
        $this->assertEqualsWithDelta(20 * SPACE_STORM_POLAR_SHIELD, $unit[GID_D_RL][1], 1e-6);
    }

    public function testBattleUnitStatsGravDefenseShieldBoost(): void
    {
        $this->setStorm(SPACE_STORM_MASK_GRAV_DEFENSE);
        $unit = array(
            GID_F_LF => array(4000, 10, 5, 5000, 5000, 10),
        );
        $args = array('attackers' => array(), 'defenders' => array());
        $this->mod()->battle_unit_stats($args, $unit);

        // Only shields are boosted (armor unchanged).
        $this->assertEqualsWithDelta(4000, $unit[GID_F_LF][0], 1e-6);
        $this->assertEqualsWithDelta(10 * SPACE_STORM_GRAV_SHIELD, $unit[GID_F_LF][1], 1e-6);
    }

    public function testBattleUnitStatsBothStormsStack(): void
    {
        $this->setStorm(SPACE_STORM_MASK_POLAR_SHIELD | SPACE_STORM_MASK_GRAV_DEFENSE);
        $unit = array(
            GID_F_LF => array(4000, 10, 5, 5000, 5000, 10),
        );
        $args = array('attackers' => array(), 'defenders' => array());
        $this->mod()->battle_unit_stats($args, $unit);

        $this->assertEqualsWithDelta(4000 * SPACE_STORM_POLAR_ARMOR, $unit[GID_F_LF][0], 1e-6);
        $this->assertEqualsWithDelta(10 * SPACE_STORM_POLAR_SHIELD * SPACE_STORM_GRAV_SHIELD, $unit[GID_F_LF][1], 1e-6);
    }

    // ========================================================================
    // spy_protection -- spy protection from the Reality Stabilizer
    // ------------------------------------------------------------------------

    public function testSpyProtectionStabilizerCounter(): void
    {
        $this->setStorm(SPACE_STORM_MASK_CHRONO_SPY);
        $planet = array(
            'planet_id' => 1,
            GID_B_REALITY_STAB => 10,
            's' . GID_B_REALITY_STAB => SPACE_STORM_MASK_CHRONO_SPY,
        );
        $bonus = array('level' => 0);
        $this->mod()->spy_protection(array('planet' => $planet, 'target_user' => array()), $bonus);

        // Level 10 stabilizer imprinted with Chrono-Spy => +5 spy protection (1 per 2 lvls).
        $this->assertSame(5, $bonus['level']);
    }

    public function testSpyProtectionWithoutStormIsNone(): void
    {
        $this->setStorm(0);
        $planet = array(
            'planet_id' => 1,
            GID_B_REALITY_STAB => 10,
            's' . GID_B_REALITY_STAB => SPACE_STORM_MASK_CHRONO_SPY,
        );
        $bonus = array('level' => 0);
        $this->mod()->spy_protection(array('planet' => $planet, 'target_user' => array()), $bonus);
        $this->assertSame(0, $bonus['level']);
    }

    // ========================================================================
    // NewStorm -- storm generation probabilities (deterministic rule)
    // ------------------------------------------------------------------------

    public function testCountStormBits(): void
    {
        $this->assertSame(0, $this->countBits(0));
        $this->assertSame(1, $this->countBits(SPACE_STORM_MASK_SUBSPACE_TURB));
        $mask = SPACE_STORM_MASK_SUBSPACE_TURB | SPACE_STORM_MASK_ENERGY_COLLAPSE | SPACE_STORM_MASK_ATTACK_REVERB;
        $this->assertSame(3, $this->countBits($mask));
    }

    // Count set bits up to the storm's MSB (mirrors the mod's internal rule).
    private function countBits(int $storm): int
    {
        $count = 0;
        for ($i = 0; $i < SPACE_STORM_MASK_MSB; $i++) {
            if (($storm & (1 << $i)) != 0) $count++;
        }
        return $count;
    }
}
