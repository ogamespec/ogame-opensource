<?php

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for issue #117 "Negative resources when playing on admin".
 *
 * A planet's stored resources (metal, crystal, deuterium) must never be
 * negative: 
 *  - the resource production update (GetUpdatePlanet) must not drive a
 *    resource below zero when the planet's balance is negative,
 *  - a cost deduction (AdjustResources) must not write a negative amount when
 *    the planet holds less than the cost,
 *  - a battle plunder (Plunder) must never report a negative captured amount,
 *    even if the defender planet already holds a corrupted negative value.
 */
#[RunTestsInSeparateProcesses]
class NegativeResourcesTest extends TestCase
{
    private FixtureBuilder $fixture;

    protected function setUp(): void
    {
        $this->fixture = (new FixtureBuilder())->createTestUniverse('en');

        // GetUpdatePlanet / ProdResources read the universe from $GlobalUni.
        global $GlobalUni, $db_prefix;
        $GlobalUni = $this->fixture->getUniData();
        $db_prefix = $this->fixture->getDbPrefix();
    }

    /**
     * Plunder must never return negative captured amounts, even when the
     * defender planet already stores a negative (corrupted) resource value.
     * This is the exact symptom reported in the issue (a battle report
     * showing "captured -104.879 metal -11.693 crystal").
     */
    public function testPlunderNeverReturnsNegativeCaptures(): void
    {
        // Defender holds a corrupted negative metal/crystal plus some deut.
        $captured = Plunder (1000, -104879, -11693, 2101);

        $this->assertGreaterThanOrEqual (0, $captured[GID_RC_METAL],
            'Plundered metal must never be negative.');
        $this->assertGreaterThanOrEqual (0, $captured[GID_RC_CRYSTAL],
            'Plundered crystal must never be negative.');
        $this->assertGreaterThanOrEqual (0, $captured[GID_RC_DEUTERIUM],
            'Plundered deuterium must never be negative.');

        // There is nothing to plunder on the negative resources, so they must be 0.
        $this->assertEquals (0, $captured[GID_RC_METAL]);
        $this->assertEquals (0, $captured[GID_RC_CRYSTAL]);
    }

    /**
     * AdjustResources must clamp the stored amount at zero: subtracting more
     * than the planet holds must leave 0 resources, not a negative value.
     */
    public function testAdjustResourcesClampsAtZero(): void
    {
        global $db_prefix;

        // Planet 1 is PlayerOne's home planet; the fixture starts it with
        // GID_RC_METAL = 25000.
        $planet = LoadPlanetById (1);
        $this->assertGreaterThanOrEqual (0, $planet[GID_RC_METAL]);

        // Subtract far more than the planet holds.
        $cost = array (GID_RC_METAL => 1000000, GID_RC_CRYSTAL => 1000000, GID_RC_DEUTERIUM => 1000000);
        AdjustResources ($cost, 1, '-');

        $updated = LoadPlanetById (1);
        $this->assertEquals (0, (int)$updated[GID_RC_METAL],
            'Metal must be clamped at zero, not go negative.');
        $this->assertEquals (0, (int)$updated[GID_RC_CRYSTAL],
            'Crystal must be clamped at zero, not go negative.');
        $this->assertEquals (0, (int)$updated[GID_RC_DEUTERIUM],
            'Deuterium must be clamped at zero, not go negative.');
    }

    /**
     * GetUpdatePlanet must not drive a stored resource below zero when the
     * resource balance is negative (deuterium consumed by a fusion reactor
     * with no deuterium synthesizer).
     */
    public function testGetUpdatePlanetClampsNegativeBalance(): void
    {
        global $db_prefix;

        // Reuse planet 3 (PlayerOne's Colony B) and turn it into a fusion
        // consumer without a deuterium synthesizer, so its deuterium balance
        // is strongly negative over the elapsed time.
        $now = time ();
        dbquery ("UPDATE {$db_prefix}planets SET
            `".GID_B_DEUT_SYNTH."` = 0,
            `".GID_B_FUSION."` = 8,
            `".GID_B_DEUT_STOR."` = 6,
            `".GID_RC_DEUTERIUM."` = 0,
            lastpeek = " . ($now - 7200) . "
            WHERE planet_id = 3");

        // Running production over 2 hours would put deuterium at about -344
        // without the clamp; with the fix it must stay at/above zero.
        GetUpdatePlanet (3, $now);

        $updated = LoadPlanetById (3);
        $this->assertGreaterThanOrEqual (0, $updated[GID_RC_DEUTERIUM],
            'Stored deuterium must never be driven below zero by production.');
        $this->assertEquals (0, (int)$updated[GID_RC_DEUTERIUM],
            'A negative balance must clamp deuterium at zero.');
    }
}
