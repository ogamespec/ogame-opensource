<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Pure logic tests of the Wanderer modification (no database).
 */
class WandererTest extends TestCase
{
    private Wanderer $mod;

    protected function setUp(): void
    {
        // The file-scope globals of core.php (incl. $modlist, which the hook
        // dispatchers iterate) are not in the global symbol table when the
        // suite bootstrap is not the true top level.
        $GLOBALS['modlist'] = array();

        if (!class_exists('Wanderer')) {
            require_once dirname(__DIR__) . '/main.php';
        }
        $this->mod = new Wanderer();
    }

    public function testModIsGameMod(): void
    {
        $this->assertInstanceOf(GameMod::class, $this->mod);
    }

    // ------------------------------------------------------------------
    // Column / schema helpers
    // ------------------------------------------------------------------

    public function testColumnLists(): void
    {
        $this->assertContains('mod_mine_m', Wanderer::ModuleColumns());
        $this->assertContains('mod_engine', Wanderer::ModuleColumns());
        $this->assertCount(8, Wanderer::ModuleColumns());
        $this->assertContains('res_nav', Wanderer::ResearchColumns());
        $this->assertCount(4, Wanderer::ResearchColumns());

        $schema = Wanderer::StationSchema();
        $this->assertArrayHasKey('user_id', $schema);
        $this->assertArrayHasKey('deuterium', $schema);
        $this->assertArrayHasKey('build_until', $schema);
        foreach (Wanderer::ModuleColumns() as $col) {
            $this->assertArrayHasKey($col, $schema);
        }

        $orders = Wanderer::OrderSchema();
        $this->assertArrayHasKey('order_id', $orders);
        $this->assertArrayHasKey('give_rc', $orders);
    }

    public function testResourceMapping(): void
    {
        $this->assertSame('metal', Wanderer::ResourceColumn(GID_RC_METAL));
        $this->assertSame('crystal', Wanderer::ResourceColumn(GID_RC_CRYSTAL));
        $this->assertSame('deuterium', Wanderer::ResourceColumn(GID_RC_DEUTERIUM));
        $this->assertSame(GID_RC_METAL, Wanderer::ResourceId('metal'));
        $this->assertSame(GID_RC_CRYSTAL, Wanderer::ResourceId('crystal'));
        $this->assertSame(GID_RC_DEUTERIUM, Wanderer::ResourceId('deuterium'));
    }

    // ------------------------------------------------------------------
    // Cargo & production
    // ------------------------------------------------------------------

    public function testCargoCapacityGrows(): void
    {
        $st = $this->station();
        $this->assertSame((float)WANDERER_CARGO_BASE, Wanderer::StationCargoCap($st));
        $st['mod_cargo'] = 3;
        $this->assertGreaterThan(WANDERER_CARGO_BASE, Wanderer::StationCargoCap($st));
        $st['mod_cargo'] = 6;
        $this->assertGreaterThan(100000.0, Wanderer::StationCargoCap($st));
    }

    public function testProductionZeroWithoutMines(): void
    {
        $prod = Wanderer::StationProduction($this->station());
        $this->assertSame(0.0, $prod[GID_RC_METAL]);
        $this->assertSame(0.0, $prod[GID_RC_CRYSTAL]);
        $this->assertSame(0.0, $prod[GID_RC_DEUTERIUM]);
    }

    public function testProductionGrowsWithLevels(): void
    {
        $st = $this->station();
        $st['mod_mine_m'] = 2;
        $st['mod_mine_k'] = 1;
        $st['mod_mine_d'] = 1;
        $prod = Wanderer::StationProduction($st);

        $this->assertGreaterThan(0.0, $prod[GID_RC_METAL]);
        $this->assertGreaterThan(0.0, $prod[GID_RC_CRYSTAL]);
        $this->assertGreaterThan(0.0, $prod[GID_RC_DEUTERIUM]);

        // Metal mine production per hour is what we compute by hand:
        // base 14 * lvl 2 * 1.09^2 = 33.27.
        $this->assertEqualsWithDelta(14.0 * 2 * pow(1.09, 2), $prod[GID_RC_METAL], 0.001);
    }

    public function testProductionBonusesApply(): void
    {
        $st = $this->station();
        $st['mod_mine_m'] = 1;
        $plain = Wanderer::StationProduction($st)[GID_RC_METAL];

        $st['mod_solar'] = 2;        // +5% each
        $with_solar = Wanderer::StationProduction($st)[GID_RC_METAL];
        $this->assertEqualsWithDelta($plain * 1.10, $with_solar, 0.001);

        $st['res_industry'] = 1;     // +4%
        $with_ind = Wanderer::StationProduction($st)[GID_RC_METAL];
        $this->assertEqualsWithDelta($plain * 1.10 * 1.04, $with_ind, 0.001);
    }

    // ------------------------------------------------------------------
    // Costs, durations, caps
    // ------------------------------------------------------------------

    public function testUpgradeCostsAndMaxLevels(): void
    {
        $st = $this->station();

        // Modules are capped by the core level (core 1 => max 2).
        $st['mod_mine_m'] = 2;
        $this->assertNull(Wanderer::UpgradeCost($st, 'mod_mine_m'));
        $st['mod_mine_m'] = 1;
        $cost = Wanderer::UpgradeCost($st, 'mod_mine_m');
        $this->assertNotNull($cost);
        $this->assertGreaterThan(0, $cost['metal']);
        $this->assertSame(0, $cost['deuterium']);

        // A higher core raises the cap.
        $st['core'] = 3;
        $st['mod_mine_m'] = 2;
        $this->assertNotNull(Wanderer::UpgradeCost($st, 'mod_mine_m'));
    }

    public function testCoreMaxLevel(): void
    {
        $st = $this->station();
        $st['core'] = WANDERER_MAX_CORE_LEVEL;
        $this->assertNull(Wanderer::UpgradeCost($st, 'core'));
        $st['core'] = WANDERER_MAX_CORE_LEVEL - 1;
        $this->assertNotNull(Wanderer::UpgradeCost($st, 'core'));
    }

    public function testResearchMaxGatedByLab(): void
    {
        $st = $this->station();
        $st['mod_lab'] = 0;
        $this->assertSame(0, Wanderer::ResearchMaxLevel($st));
        $st['mod_lab'] = WANDERER_MAX_RESEARCH_LEVEL;
        $this->assertSame(WANDERER_MAX_RESEARCH_LEVEL, Wanderer::ResearchMaxLevel($st));
        $st['mod_lab'] = 3;
        $this->assertSame(3, Wanderer::ResearchMaxLevel($st));
    }

    public function testDurationsPositive(): void
    {
        $st = $this->station();
        foreach (array_merge(array('core'), Wanderer::ModuleColumns(), Wanderer::ResearchColumns()) as $col) {
            $this->assertGreaterThan(0, Wanderer::UpgradeSeconds($st, $col), $col);
        }
        // The laboratory speeds up the research.
        $st['res_nav'] = 2;
        $without_lab = Wanderer::UpgradeSeconds($st, 'res_nav');
        $st['mod_lab'] = 2;
        $with_lab = Wanderer::UpgradeSeconds($st, 'res_nav');
        $this->assertLessThan($without_lab, $with_lab);
    }

    // ------------------------------------------------------------------
    // Jumps
    // ------------------------------------------------------------------

    public function testJumpCostGrowsWithDistance(): void
    {
        $st = $this->station();
        $c0 = Wanderer::JumpCost($st, 1, 1);
        $c1 = Wanderer::JumpCost($st, 1, 2);
        $c8 = Wanderer::JumpCost($st, 1, 9);
        $this->assertGreaterThan($c1, $c8);
        $this->assertGreaterThan(WANDERER_JUMP_BASE_DEUT, $c1);
        $this->assertSame($c1, Wanderer::JumpCost($st, 2, 1));   // symmetric
    }

    public function testJumpEngineAndNavigationReduceCosts(): void
    {
        $plain = $this->station();
        $st = $plain;
        $st['mod_engine'] = 5;
        $st['res_nav'] = 5;
        $this->assertLessThan(
            Wanderer::JumpCost($plain, 1, 4),
            Wanderer::JumpCost($st, 1, 4)
        );
        $this->assertLessThan(
            Wanderer::JumpCooldown($plain),
            Wanderer::JumpCooldown($st)
        );
        $this->assertLessThan(
            Wanderer::JumpFlightTime($plain, 1, 4),
            Wanderer::JumpFlightTime($st, 1, 4)
        );
    }

    public function testJumpCostsNeverBelowMinimum(): void
    {
        $st = $this->station();
        $st['mod_engine'] = WANDERER_MAX_MODULE_LEVEL;
        $st['res_nav'] = WANDERER_MAX_RESEARCH_LEVEL;
        $min_mult = WANDERER_JUMP_MIN_MULTIPLIER;
        $cost = Wanderer::JumpCost($st, 1, 1);
        $this->assertGreaterThanOrEqual((int)ceil(WANDERER_JUMP_BASE_DEUT * $min_mult), $cost);
    }

    // ------------------------------------------------------------------
    // Market
    // ------------------------------------------------------------------

    public function testMarketFactorRangeAndStability(): void
    {
        foreach (array(GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM) as $rc) {
            for ($g = 1; $g <= 5; $g++) {
                $f = Wanderer::MarketFactor($g, $rc, 1700000000);
                $this->assertGreaterThanOrEqual(WANDERER_MARKET_FACTOR_MIN, $f);
                $this->assertLessThanOrEqual(WANDERER_MARKET_FACTOR_MAX, $f);
                // Same moment => deterministic value.
                $this->assertSame($f, Wanderer::MarketFactor($g, $rc, 1700000000));
            }
        }
    }

    public function testMarketBaseValues(): void
    {
        // Deut is the most valuable resource for the guild, metal the cheapest.
        $this->assertGreaterThan(
            Wanderer::MarketValue(1, GID_RC_METAL, 1700000000),
            Wanderer::MarketValue(1, GID_RC_CRYSTAL, 1700000000)
        );
        $this->assertGreaterThan(
            Wanderer::MarketValue(1, GID_RC_CRYSTAL, 1700000000),
            Wanderer::MarketValue(1, GID_RC_DEUTERIUM, 1700000000)
        );
    }

    public function testGuildQuoteAndCommission(): void
    {
        $st = $this->station();
        $when = 1700000000;

        $comm0 = Wanderer::MarketCommission($st);
        $this->assertSame(WANDERER_MARKET_COMMISSION, $comm0);

        $quote = Wanderer::GuildQuote(1, GID_RC_METAL, 1000.0, GID_RC_CRYSTAL, $st, $when);
        // 1000 metal * value(metal)/value(crystal) * (1-0.05), floored.
        $expected = floor(1000 * Wanderer::MarketValue(1, GID_RC_METAL, $when)
                          / Wanderer::MarketValue(1, GID_RC_CRYSTAL, $when) * 0.95);
        $this->assertSame($expected, $quote);
        $this->assertGreaterThan(0, $quote);

        // Trade research lowers the commission.
        $st['res_trade'] = 10;
        $this->assertSame(0.0, Wanderer::MarketCommission($st));
        $quote2 = Wanderer::GuildQuote(1, GID_RC_METAL, 1000.0, GID_RC_CRYSTAL, $st, $when);
        $this->assertGreaterThan($quote, $quote2);
    }

    public function testGuildQuoteSameResourceIsZero(): void
    {
        $this->assertSame(0.0, Wanderer::GuildQuote(1, GID_RC_METAL, 100.0, GID_RC_METAL, $this->station()));
    }

    // ------------------------------------------------------------------
    // Misc helpers
    // ------------------------------------------------------------------

    public function testFormatDuration(): void
    {
        $this->assertStringContainsString('s', Wanderer::FormatDuration(0));
        $this->assertStringContainsString('1h', Wanderer::FormatDuration(3600));
        $this->assertStringContainsString('2d', Wanderer::FormatDuration(2 * 86400 + 120));
        // Never negative.
        $this->assertStringContainsString('s', Wanderer::FormatDuration(-5));
    }

    public function testCleanStationName(): void
    {
        $this->assertSame('My Station', Wanderer::CleanStationName('My Station'));
        $this->assertSame('abcdef', Wanderer::CleanStationName('ab;c,<d>ef`'));
        $this->assertSame('', Wanderer::CleanStationName('   '));
        $this->assertSame(20, mb_strlen(Wanderer::CleanStationName('123456789012345678901234567890'), 'UTF-8'));
    }

    public function testStationTickCompletesBuildAndAccrues(): void
    {
        // Pure in-memory tick behavior.
        $st = $this->station();
        $st['mod_cargo'] = 2;
        $st['mod_mine_m'] = 3;
        $st['lastprod'] = 1000;
        $st['metal'] = 100.0;

        $st['build_type'] = 'M';
        $st['build_id'] = 'mod_solar';
        $st['build_start'] = 1000;
        $st['build_until'] = 3600;   // finishes at t=3600

        Wanderer::TickStation($st, 7200);

        $this->assertSame(1, (int)$st['mod_solar'], 'the module must be upgraded');
        $this->assertSame('', $st['build_type']);
        $this->assertSame(0, (int)$st['build_until']);

        // Production accrued for 7200-1000=6200 s at the level-3 mine rate.
        $hours = 6200 / 3600.0;
        $cap = Wanderer::StationCargoCap($st);
        $prod = Wanderer::StationProduction($st);
        $expected = min($cap, 100.0 + $prod[GID_RC_METAL] * $hours);
        $this->assertEqualsWithDelta($expected, (float)$st['metal'], 1.0);
        $this->assertSame(7200, (int)$st['lastprod']);
    }

    public function testStationTickSkipsUnknownBuild(): void
    {
        $st = $this->station();
        $st['build_type'] = 'M';
        $st['build_id'] = 'bogus_col';
        $st['build_until'] = 100;
        Wanderer::TickStation($st, 200);
        $this->assertSame('', $st['build_type']);
        $this->assertSame(0, (int)$st['build_until']);
    }

    public function testStationTickCapsAtCargo(): void
    {
        $st = $this->station();
        $st['mod_mine_m'] = 12;
        $st['lastprod'] = 1000;
        $st['metal'] = Wanderer::StationCargoCap($st) - 1;
        Wanderer::TickStation($st, 1000 + 86400 * 30);   // a very long pause
        $this->assertLessThanOrEqual(Wanderer::StationCargoCap($st), (float)$st['metal']);
    }

    /**
     * A minimal station array with default values.
     */
    private function station(): array
    {
        $st = array(
            'user_id' => 1,
            'name' => 'Test',
            'planet_id' => 0,
            'g' => 1, 's' => 1, 'p' => 1,
            'image' => 1,
            'metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0,
            'lastprod' => 0,
            'core' => 1,
            'cooldown_until' => 0,
            'jumps' => 0, 'deals' => 0, 'started' => 0,
            'build_type' => '', 'build_id' => 0, 'build_start' => 0, 'build_until' => 0,
        );
        foreach (Wanderer::ModuleColumns() as $col) {
            $st[$col] = 0;
        }
        foreach (Wanderer::ResearchColumns() as $col) {
            $st[$col] = 0;
        }
        return $st;
    }
}
