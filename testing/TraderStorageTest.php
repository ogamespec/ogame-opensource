<?php

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for issue #82 "Trader issue".
 *
 * When the "max" button is used on the merchant, the received resources can
 * exceed the storage capacity if the planet produced resources between the
 * page being rendered and the trade being submitted. The merchant must give
 * only what actually fits in storage (and charge only for that) instead of
 * failing with the "not enough storage space" error.
 */
#[RunTestsInSeparateProcesses]
class TraderStorageTest extends TestCase
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
     * A received resource must never be pushed above the storage capacity,
     * and the trade must succeed (not fail with "not enough storage space").
     *
     * Setup: merchant buys crystal (offer 2), so it gives metal + deuterium
     * and charges crystal. Metal is 500 000 with a 600 000 capacity, so only
     * 100 000 metal fits. A "max"-style posted value of 200 000 metal is
     * requested, which would overflow storage without the fix.
     */
    public function testMerchantReceivedResourcesDoNotExceedStorage(): void
    {
        global $db_prefix;

        $now = time();

        // PlayerOne home planet (id 1). Metal storage level 5 => 600 000 cap.
        // Zero the mines so no production changes the resources while the
        // page is rendered (deterministic test) and set the metal near the cap.
        dbquery ("UPDATE {$db_prefix}planets SET
            `".GID_RC_METAL."` = 500000,
            `".GID_RC_CRYSTAL."` = 200000,
            `".GID_RC_DEUTERIUM."` = 50000,
            `".GID_B_METAL_MINE."` = 0,
            `".GID_B_CRYS_MINE."` = 0,
            `".GID_B_DEUT_SYNTH."` = 0,
            lastpeek = {$now}
            WHERE planet_id = 1");

        // Merchant buys crystal (offer 2): it gives metal + deuterium. Rates
        // 2.4 metal : 2.0 crystal : 1.0 deuterium (the fixture defaults).
        dbquery ("UPDATE {$db_prefix}users SET trader = 2, rate_m = 2.4, rate_k = 2.0, rate_d = 1.0 WHERE player_id = 1");

        // Posting more metal than fits (free storage is 100 000) simulates the
        // "max" button filling the page-load free storage while the planet has
        // produced resources in the meantime.
        $html = (new PageRenderer($this->fixture))
            ->asPlayer(0)
            ->withPost(['trade' => 'Exchange!', '1_value' => '200000', '2_value' => '0', '3_value' => '0'])
            ->render('trader');

        $updated = LoadPlanetById(1);
        $this->assertLessThanOrEqual (600000, (int)$updated[GID_RC_METAL],
            'The merchant must not push a resource above the storage capacity (issue #82).');
        $this->assertEquals (600000, (int)$updated[GID_RC_METAL],
            'The merchant should fill the storage up to the capacity, not less.');

        $this->assertStringNotContainsString ('Not enough storage space', $html,
            'The trade must not fail with the storage error (issue #82).');

        // The trade must have been consumed (a successful exchange sets trader=0).
        $pdo = $this->fixture->getPDO();
        $stmt = $pdo->prepare ("SELECT trader FROM {$db_prefix}users WHERE player_id = 1");
        $stmt->execute();
        $this->assertEquals (0, (int)$stmt->fetchColumn(),
            'The merchant must be consumed after a successful exchange.');
    }
}
