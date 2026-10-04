<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the battle core of OGame 0.84:
 *
 *  - game/core/battle_report.php  -- GenSlot() and BattleReport().
 *  - game/core/battle.php         -- Plunder(), RepairDefense(), CalcLosses(),
 *                                    CalcDebris(), GetDebrisTotal(),
 *                                    CargoSummaryLastRound(), GenBattleSourceData(),
 *                                    PostProcessBattleResult() and the full
 *                                    StartBattle() pipeline.
 *  - game/core/battle_engine.php  -- the packed array-string helpers, the battle
 *                                    input parsers, UnitShoot(), WipeExploded(),
 *                                    ChargeShields(), CheckFastDraw(), RapidFire(),
 *                                    DoBattle() and BattleEngine().
 *
 * Everything runs against the in-memory SQLite backend (testing/bootstrap.php),
 * so the tests are self-contained. Randomness is seeded with mt_srand() and the
 * assertions on random values use either ranges or a mirror of the documented
 * formula. Functions that end the process through Error() (it calls exit()) or
 * that need the external C battle engine binary cannot be covered: see the
 * comments at the deserialize_slot()/ParseRFTable() tests and ExecuteBattle().
 */
#[RunTestsInSeparateProcesses]
class BattleCoreTest extends TestCase
{
    // ========================================================================
    // Setup and helpers
    // ========================================================================

    protected function setUp(): void
    {
        // loca_add() resolves the localization files relative to the game directory.
        chdir(__DIR__ . '/../game');
        loca_add('battlereport', 'en');
        loca_add('technames', 'en');
        loca_add('fleetmsg', 'en');

        // The battle engine is random; make every test reproducible.
        mt_srand(20240521);

        // Globals owned by battle_engine.php.
        $GLOBALS['exploded_counter'] = 0;
        $GLOBALS['already_exploded_counter'] = 0;
        // The engine works on its own copy of the parameters parsed from the
        // battle source data; the direct-call tests use the real game table.
        $GLOBALS['UnitParamLocal'] = $GLOBALS['UnitParam'];
        $GLOBALS['RapidFireLocal'] = $GLOBALS['RapidFire'];
    }

    /** Pack a list of unit ids into the engine's 2-byte array-string format. */
    private function packHalf(array $values): string
    {
        $str = str_repeat("\0", 2 * count($values));
        foreach ($values as $i => $value) {
            set_packed_half($str, $i, $value);
        }
        return $str;
    }

    /** Pack a list of 32-bit values into the engine's 4-byte array-string format. */
    private function packWord(array $values): string
    {
        $str = str_repeat("\0", 4 * count($values));
        foreach ($values as $i => $value) {
            set_packed_word($str, $i, $value);
        }
        return $str;
    }

    /** Unpack $count 2-byte values of an array-string. */
    private function unpackHalf(string $str, int $count): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = get_packed_half($str, $i);
        }
        return $out;
    }

    /** Unpack $count 4-byte values of an array-string. */
    private function unpackWord(string $str, int $count): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = get_packed_word($str, $i);
        }
        return $out;
    }

    /** One battle participant as the engine and the post-processing see it. */
    private function force(
        array $units, int $weap = 0, int $shld = 0, int $armr = 0,
        int $pf = BATTLE_PTCP_FLEET, int $id = 1, string $oname = 'Player',
        int $g = 1, int $s = 1, int $p = 4
    ): array {
        return [
            'units' => $units, 'weap' => $weap, 'shld' => $shld, 'armr' => $armr,
            'pf' => $pf, 'id' => $id, 'oname' => $oname, 'g' => $g, 's' => $s, 'p' => $p,
        ];
    }

    /** A $res array without rounds, as BattleEngine() returns it before DoBattle(). */
    private function battleRes(
        array $attackerUnits, array $defenderUnits,
        int $weap = 0, int $shld = 0, int $armr = 0,
        int $dweap = 0, int $dshld = 0, int $darmr = 0
    ): array {
        return [
            'before' => [
                'attackers' => [$this->force($attackerUnits, $weap, $shld, $armr)],
                'defenders' => [$this->force($defenderUnits, $dweap, $dshld, $darmr, BATTLE_PTCP_PLANET)],
            ],
        ];
    }

    /** Append a single (last) round to a $res built by battleRes(). */
    private function withLastRound(array $res, array $attackerUnits, array $defenderUnits, int $defenderPf = BATTLE_PTCP_PLANET): array
    {
        $res['rounds'] = [[
            'attackers' => [['units' => $attackerUnits]],
            'defenders' => [['units' => $defenderUnits, 'pf' => $defenderPf]],
        ]];
        return $res;
    }

    /** A planet defender carrying the given (non-missile) defenses. */
    private function planetDefender(array $defenses): array
    {
        $units = [];
        foreach (array_diff($GLOBALS['defmap'], $GLOBALS['rakmap']) as $gid) {
            $units[$gid] = $defenses[$gid] ?? 0;
        }
        return $this->force($units, 0, 0, 0, BATTLE_PTCP_PLANET);
    }

    /**
     * A repair map as RepairDefense() produces it: one entry per defender with
     * every non-missile defense id set (CalcLosses/CalcDebris read them all).
     */
    private function noRepairs(): array
    {
        $defender = [];
        foreach (array_diff($GLOBALS['defmap'], $GLOBALS['rakmap']) as $gid) {
            $defender[$gid] = 0;
        }
        return [0 => $defender];
    }

    /** One fleet slot as GenBattleSourceData() expects it. */
    private function sourceForce(int $gid, int $amount, int $weap, int $shld, int $armr): array
    {
        return [
            'units' => [$gid => $amount],
            GID_R_WEAPON => $weap, GID_R_SHIELD => $shld, GID_R_ARMOUR => $armr,
        ];
    }

    /** A full battle result structure for BattleReport(). */
    private function battleReportRes(string $result, array $rounds): array
    {
        return [
            'result' => $result,
            'before' => [
                'attackers' => [[
                    'name' => 'PlayerOne', 'g' => 1, 's' => 1, 'p' => 4,
                    'weap' => 3, 'shld' => 3, 'armr' => 4,
                    'pf' => BATTLE_PTCP_FLEET, 'units' => [GID_F_LF => 10],
                ]],
                'defenders' => [[
                    'name' => 'PlayerTwo', 'g' => 1, 's' => 3, 'p' => 4,
                    'weap' => 5, 'shld' => 2, 'armr' => 1,
                    'pf' => BATTLE_PTCP_PLANET, 'units' => [GID_D_RL => 3],
                ]],
            ],
            'rounds' => $rounds,
        ];
    }

    /** A single combat round for BattleReport(). */
    private function oneRound(array $attackerUnits, array $defenderUnits): array
    {
        return [
            'ashoot' => 10, 'apower' => 500, 'dabsorb' => 0,
            'dshoot' => 3, 'dpower' => 200, 'aabsorb' => 0,
            'attackers' => [['name' => 'PlayerOne', 'g' => 1, 's' => 1, 'p' => 4, 'pf' => BATTLE_PTCP_FLEET, 'units' => $attackerUnits]],
            'defenders' => [['name' => 'PlayerTwo', 'g' => 1, 's' => 3, 'p' => 4, 'pf' => BATTLE_PTCP_PLANET, 'units' => $defenderUnits]],
        ];
    }

    // ========================================================================
    // battle_report.php -- GenSlot()
    // ========================================================================

    public function testGenSlotRendersAttackerWithAmountsAndTechnologyValues(): void
    {
        $text = GenSlot(3, 3, 4, 'PlayerOne', 1, 1, 4, $GLOBALS['fleetmap'], [GID_F_LF => 10], true, true, 'en');

        $this->assertStringContainsString(loca_lang('BATTLE_ATTACKER', 'en'), $text);
        $this->assertStringContainsString('PlayerOne', $text);
        $this->assertStringContainsString('showGalaxy(1,1,4)', $text);
        $this->assertStringContainsString('[1:1:4]', $text);
        // Technology levels are rendered as percentages (level * 10).
        $this->assertStringContainsString('Weapons: 30%', $text);
        $this->assertStringContainsString('Shields: 30%', $text);
        $this->assertStringContainsString('Armour: 40%', $text);
        // Light fighter (204): structure 4000, shield 10, attack 50.
        $this->assertStringContainsString('<th>' . loca_lang('SNAME_204', 'en') . '</th>', $text);
        $this->assertStringContainsString('<th>10</th>', $text);      // amount
        $this->assertStringContainsString('<th>65</th>', $text);      // 50 * (10 + 3) / 10
        $this->assertStringContainsString('<th>13</th>', $text);      // 10 * (10 + 3) / 10
        $this->assertStringContainsString('<th>560</th>', $text);     // 4000 * (10 + 4) / 100
    }

    public function testGenSlotRendersDefenderEscapesTheNameAndHidesTechs(): void
    {
        $text = GenSlot(0, 0, 0, '<b>Evil</b>', 1, 3, 4, $GLOBALS['fleetmap'], [GID_F_LF => 5], false, false, 'en');

        $this->assertStringContainsString(loca_lang('BATTLE_DEFENDER', 'en'), $text);
        $this->assertStringNotContainsString(loca_lang('BATTLE_ATTACKER', 'en'), $text);
        $this->assertStringContainsString('&lt;b&gt;Evil&lt;/b&gt;', $text);
        $this->assertStringNotContainsString('<b>Evil</b>', $text);
        $this->assertStringNotContainsString('Weapons:', $text);
        $this->assertStringContainsString('[1:3:4]', $text);
    }

    public function testGenSlotReportsDestroyedWhenNoUnitIsLeft(): void
    {
        $text = GenSlot(0, 0, 0, 'PlayerTwo', 1, 3, 4, $GLOBALS['fleetmap'], [GID_F_LF => 0, GID_F_SC => 0], false, false, 'en');

        $this->assertStringContainsString(loca_lang('BATTLE_DESTROYED', 'en'), $text);
        $this->assertStringNotContainsString('<table border=1>', $text);

        // Units that are missing from the map are treated like zero amounts.
        $empty = GenSlot(0, 0, 0, 'PlayerTwo', 1, 3, 4, $GLOBALS['fleetmap'], [], false, false, 'en');
        $this->assertStringContainsString(loca_lang('BATTLE_DESTROYED', 'en'), $empty);
    }

    public function testGenSlotSkipsZeroAmountUnitTypes(): void
    {
        $text = GenSlot(0, 0, 0, 'PlayerOne', 1, 1, 4, $GLOBALS['fleetmap'], [GID_F_LF => 2, GID_F_SC => 0], false, true, 'en');

        $this->assertStringContainsString(loca_lang('SNAME_204', 'en'), $text);
        $this->assertStringNotContainsString(loca_lang('SNAME_202', 'en'), $text);
    }

    // ========================================================================
    // battle_report.php -- BattleReport()
    // ========================================================================

    public function testBattleReportAWonIncludesPlunderAndLosses(): void
    {
        $res = $this->battleReportRes('awon', [$this->oneRound([GID_F_LF => 10], [])]);

        $text = BattleReport($res, 1700000000,
            ['aloss' => 5000, 'dloss' => 3000],
            [GID_RC_METAL => 2000, GID_RC_CRYSTAL => 1000, GID_RC_DEUTERIUM => 500],
            0, false, null, null, 'en');

        $this->assertStringContainsString(loca_lang('BATTLE_AWON', 'en'), $text);
        $this->assertStringContainsString(va(loca_lang('BATTLE_PLUNDER', 'en'), '2.000', '1.000', '500'), $text);
        $this->assertStringContainsString(va(loca_lang('BATTLE_ALOSS', 'en'), '5.000'), $text);
        $this->assertStringContainsString(va(loca_lang('BATTLE_DLOSS', 'en'), '3.000'), $text);
        // Both forces before the battle, with their technologies.
        $this->assertStringContainsString(loca_lang('BATTLE_ATTACKER', 'en') . ' PlayerOne', $text);
        $this->assertStringContainsString(loca_lang('BATTLE_DEFENDER', 'en') . ' PlayerTwo', $text);
        // Round statistics.
        $this->assertStringContainsString(va(loca_lang('BATTLE_ASHOT', 'en'), '10', '500', '0'), $text);
        $this->assertStringContainsString(va(loca_lang('BATTLE_DSHOT', 'en'), '3', '200', '0'), $text);
        // The defender lost everything in the only round.
        $this->assertStringContainsString(loca_lang('BATTLE_DESTROYED', 'en'), $text);
    }

    public function testBattleReportDWonAndDrawNeverShowPlunder(): void
    {
        $res = $this->battleReportRes('dwon', []);
        $text = BattleReport($res, 1700000000, null,
            [GID_RC_METAL => 1, GID_RC_CRYSTAL => 1, GID_RC_DEUTERIUM => 1], 0, false, null, null, 'en');
        $this->assertStringContainsString(loca_lang('BATTLE_DWON', 'en'), $text);
        $this->assertStringNotContainsString(loca_lang('BATTLE_PLUNDER', 'en'), $text);
        $this->assertStringContainsString('the following fleets met in battle', $text);

        $res = $this->battleReportRes('draw', []);
        $text = BattleReport($res, 1700000000, null, null, 0, false, null, null, 'en');
        $this->assertStringContainsString(loca_lang('BATTLE_DRAW', 'en'), $text);
    }

    public function testBattleReportDebrisMoonChanceAndModMessages(): void
    {
        $res = $this->battleReportRes('awon', []);
        $res['extra'] = ['Mod message one', 'Mod message two'];

        $text = BattleReport($res, 1700000000, null, null, 5, true, null,
            [GID_RC_METAL => 100000, GID_RC_CRYSTAL => 50000], 'en');

        $this->assertStringContainsString(va(loca_lang('BATTLE_MOONCHANCE', 'en'), 5), $text);
        $this->assertStringContainsString(loca_lang('BATTLE_MOON', 'en'), $text);
        $this->assertStringContainsString(va(loca_lang('BATTLE_DEBRIS', 'en'), '100.000', '50.000'), $text);
        $this->assertStringContainsString('<br>Mod message one', $text);
        $this->assertStringContainsString('<br>Mod message two', $text);
    }

    public function testBattleReportWithoutLossesDebrisOrMoonSkipsThoseBlocks(): void
    {
        $res = $this->battleReportRes('awon', []);
        $text = BattleReport($res, 1700000000, null, null, 0, false, null, null, 'en');

        $this->assertStringNotContainsString(loca_lang('BATTLE_DEBRIS', 'en'), $text);
        $this->assertStringNotContainsString(loca_lang('BATTLE_MOONCHANCE', 'en'), $text);
        $this->assertStringNotContainsString(loca_lang('BATTLE_MOON', 'en'), $text);
        $this->assertStringNotContainsString(loca_lang('BATTLE_ALOSS', 'en'), $text);
        $this->assertStringNotContainsString(loca_lang('BATTLE_PLUNDER', 'en'), $text);
    }

    public function testBattleReportListsRepairedDefenseInRepairmapOrder(): void
    {
        $res = $this->battleReportRes('dwon', []);
        $repaired = [0 => [
            GID_D_RL => 0, GID_D_LL => 0, GID_D_HL => 0, GID_D_GAUSS => 0,
            GID_D_ION => 0, GID_D_SDOME => 1, GID_D_PLASMA => 1, GID_D_LDOME => 0,
        ]];

        $text = BattleReport($res, 1700000000, null, null, 0, false, $repaired, null, 'en');

        // The repair permutation table prints the Small Shield Dome before the
        // Plasma Turret, exactly like the original 0.84 battle report.
        $this->assertStringContainsString(
            '1 ' . loca_lang('NAME_407', 'en') . ', 1 ' . loca_lang('NAME_406', 'en'),
            $text);
        $this->assertStringContainsString(loca_lang('BATTLE_REPAIRED', 'en'), $text);
        $this->assertSame(1, substr_count($text, loca_lang('BATTLE_REPAIRED', 'en')));
    }

    public function testBattleReportSkipsTheRepairBlockForFleetDefenders(): void
    {
        $res = $this->battleReportRes('dwon', []);
        $repaired = [0 => [
            GID_D_RL => 1, GID_D_LL => 0, GID_D_HL => 0, GID_D_GAUSS => 0,
            GID_D_ION => 0, GID_D_SDOME => 0, GID_D_PLASMA => 0, GID_D_LDOME => 0,
        ]];

        // A fleet on hold (pf = 0) is not a planet and never repairs defense.
        $res['before']['defenders'][0]['pf'] = BATTLE_PTCP_FLEET;
        $text = BattleReport($res, 1700000000, null, null, 0, false, $repaired, null, 'en');
        $this->assertStringNotContainsString(loca_lang('BATTLE_REPAIRED', 'en'), $text);

        // The same losses on a planet produce the (singular) repair line.
        $res['before']['defenders'][0]['pf'] = BATTLE_PTCP_PLANET;
        $text = BattleReport($res, 1700000000, null, null, 0, false, $repaired, null, 'en');
        $this->assertStringContainsString('1 ' . loca_lang('NAME_401', 'en'), $text);
        $this->assertStringContainsString(loca_lang('BATTLE_REPAIRED1', 'en'), $text);
    }

    public function testFrenchBattleReportContainsApostrophes(): void
    {
        // battle.php writes the report into the battledata table with a raw,
        // unescaped SQL string: "UPDATE ... SET report = '".$text."'".
        // The French localization contains single quotes, so that write is not
        // SQL-safe (see the reported issue). This test documents that the
        // generated text really carries them.
        $res = $this->battleReportRes('awon', []);
        $text = BattleReport($res, 1700000000, null, null, 0, false, null, null, 'fr');

        $this->assertStringContainsString(loca_lang('BATTLE_AWON', 'fr'), $text);
        $this->assertStringContainsString("'", $text);
    }

    // ========================================================================
    // battle.php -- Plunder()
    // ========================================================================

    public function testPlunderTakesHalfOfEveryStoredResourceWhenCargoIsPlentiful(): void
    {
        $captured = Plunder(1000000000, 1000, 1000, 1000);

        $this->assertEquals(500.0, $captured[GID_RC_METAL]);
        $this->assertEquals(500.0, $captured[GID_RC_CRYSTAL]);
        $this->assertEquals(500.0, $captured[GID_RC_DEUTERIUM]);
        $this->assertSame([GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM], array_keys($captured));
    }

    public function testPlunderSplitsASmallCargoEvenly(): void
    {
        $captured = Plunder(300, 1000, 1000, 1000);

        $this->assertEquals(100.0, $captured[GID_RC_METAL]);
        $this->assertEquals(100.0, $captured[GID_RC_CRYSTAL]);
        $this->assertEquals(100.0, $captured[GID_RC_DEUTERIUM]);
    }

    public function testPlunderRedistributesFreeCargoToMetalAndCrystal(): void
    {
        // No deuterium at all: the capacity left over is offered to metal and
        // then to crystal. Metal has 500 (half of 1000) available and receives
        // 100 + 100; the last 100 points of capacity stay unused because the
        // algorithm redistributes only once.
        $captured = Plunder(300, 1000, 0, 0);

        $this->assertEquals(200.0, $captured[GID_RC_METAL]);
        $this->assertEquals(0.0, $captured[GID_RC_CRYSTAL]);
        $this->assertEquals(0.0, $captured[GID_RC_DEUTERIUM]);
    }

    public function testPlunderLeavesCapacityUnusedWhenOnlyCrystalIsLeft(): void
    {
        $captured = Plunder(1000, 0, 1000, 0);

        $this->assertEquals(0.0, $captured[GID_RC_METAL]);
        $this->assertEquals(500.0, $captured[GID_RC_CRYSTAL]);
        $this->assertEquals(0.0, $captured[GID_RC_DEUTERIUM]);
    }

    public function testPlunderWithoutCargoOrResourcesCapturesNothing(): void
    {
        foreach (Plunder(0, 100000, 100000, 100000) as $amount) {
            $this->assertEquals(0.0, $amount);
        }
        foreach (Plunder(1000, 0, 0, 0) as $amount) {
            $this->assertEquals(0.0, $amount);
        }
    }

    public function testPlunderNeverReturnsNegativeAmounts(): void
    {
        // Corrupted (negative) planet values are clamped before the split.
        $captured = Plunder(1000, -500, -500, -500);

        foreach ($captured as $amount) {
            $this->assertGreaterThanOrEqual(0, $amount);
        }
    }

    public function testPlunderFloorsFractionalResources(): void
    {
        // A single unit of each resource is halved to 0.5 and then floored,
        // so nothing at all can be captured.
        $captured = Plunder(100, 1, 1, 1);

        $this->assertEquals(0.0, $captured[GID_RC_METAL]);
        $this->assertEquals(0.0, $captured[GID_RC_CRYSTAL]);
        $this->assertEquals(0.0, $captured[GID_RC_DEUTERIUM]);
    }

    // ========================================================================
    // battle.php -- RepairDefense()
    // ========================================================================

    public function testRepairDefenseRestoresEveryExplodedUnitAtOneHundredPercent(): void
    {
        $d = [0 => $this->planetDefender([GID_D_RL => 10])];
        $res = ['rounds' => [['defenders' => [0 => ['units' => [GID_D_RL => 4], 'pf' => BATTLE_PTCP_PLANET]]]]];

        $repaired = RepairDefense($d, $res, 100, 0, false);

        // 6 rocket launchers exploded and every per-unit die roll succeeds.
        $this->assertSame(6, $repaired[0][GID_D_RL]);
        // The result contains every non-missile defense type, but no missiles.
        $this->assertCount(8, $repaired[0]);
        $this->assertArrayNotHasKey(GID_D_ABM, $repaired[0]);
        $this->assertArrayNotHasKey(GID_D_IPM, $repaired[0]);
    }

    public function testRepairDefenseRestoresNothingAtZeroPercent(): void
    {
        $d = [0 => $this->planetDefender([GID_D_RL => 10, GID_D_LL => 5])];
        $res = ['rounds' => [['defenders' => [0 => ['units' => [GID_D_RL => 4, GID_D_LL => 2], 'pf' => BATTLE_PTCP_PLANET]]]]];

        $repaired = RepairDefense($d, $res, 0, 0, false);

        $this->assertSame(0, $repaired[0][GID_D_RL]);
        $this->assertSame(0, $repaired[0][GID_D_LL]);
    }

    public function testRepairDefenseBulkFormulaStaysInsideTheDeltaRange(): void
    {
        // 60 exploded launchers (>= 10) use the bulk formula
        // floor(mt_rand(40, 60) * 60 / 100) -> 24 .. 36 units.
        mt_srand(99);
        $d = [0 => $this->planetDefender([GID_D_RL => 100])];
        $res = ['rounds' => [['defenders' => [0 => ['units' => [GID_D_RL => 40], 'pf' => BATTLE_PTCP_PLANET]]]]];

        $repaired = RepairDefense($d, $res, 50, 10, false);

        $this->assertGreaterThanOrEqual(24, $repaired[0][GID_D_RL]);
        $this->assertLessThanOrEqual(36, $repaired[0][GID_D_RL]);
    }

    public function testRepairDefenseEngineerHalvesTheExplodedDefense(): void
    {
        $d = [0 => array_merge(
            $this->planetDefender([GID_D_RL => 100]),
            ['eng_until' => time() + 86400]
        )];
        $res = ['rounds' => [['defenders' => [0 => ['units' => [GID_D_RL => 40], 'pf' => BATTLE_PTCP_PLANET]]]]];

        // The engineer halves the 60 losses to 30 before the bulk roll, so only
        // 12 .. 18 of the 60 exploded launchers come back.
        $repaired = RepairDefense($d, $res, 50, 10, true);
        $this->assertGreaterThanOrEqual(12, $repaired[0][GID_D_RL]);
        $this->assertLessThanOrEqual(18, $repaired[0][GID_D_RL]);

        // At 100 % without deviation the engineer line repairs exactly the 30
        // halved losses, while the same defender without an engineer repairs
        // all 60.
        $noEngineer = [0 => $this->planetDefender([GID_D_RL => 100])];
        $this->assertEquals(30.0, RepairDefense($d, $res, 100, 0, true)[0][GID_D_RL]);
        $this->assertEquals(60.0, RepairDefense($noEngineer, $res, 100, 0, true)[0][GID_D_RL]);
    }

    public function testRepairDefenseSkipsNonPlanetDefenders(): void
    {
        $d = [0 => $this->force([GID_D_RL => 10], 0, 0, 0, BATTLE_PTCP_FLEET)];
        $res = ['rounds' => [['defenders' => [0 => ['units' => [GID_D_RL => 0], 'pf' => BATTLE_PTCP_FLEET]]]]];

        $this->assertSame([], RepairDefense($d, $res, 100, 0, false));
    }

    public function testRepairDefenseWithoutRoundsReturnsZeroes(): void
    {
        $d = [0 => $this->planetDefender([GID_D_RL => 10])];

        $repaired = RepairDefense($d, ['rounds' => []], 100, 0, false);

        $this->assertCount(8, $repaired[0]);
        foreach ($repaired[0] as $amount) {
            $this->assertSame(0, $amount);
        }
    }

    // ========================================================================
    // battle.php -- CalcLosses()
    // ========================================================================

    public function testCalcLossesCountsTheDestroyedDefense(): void
    {
        $a = [$this->force([GID_F_LF => 10])];
        $d = [$this->force([GID_D_RL => 3], 0, 0, 0, BATTLE_PTCP_PLANET)];
        $res = $this->withLastRound($this->battleRes([GID_F_LF => 10], [GID_D_RL => 3]), [GID_F_LF => 10], []);

        $loss = CalcLosses($a, $d, $res, [0 => [GID_D_RL => 0]]);

        $this->assertSame(0, $loss['aloss']);
        $this->assertSame(3 * 2000, $loss['dloss']);        // rocket launcher = 2000 points
        // The per-participant scoring values are recalculated in place.
        $this->assertSame(0, $a[0]['points']);              // all 10 fighters survived
        $this->assertSame(0, $a[0]['fpoints']);
        $this->assertSame(3 * 2000, $d[0]['points']);
        $this->assertSame(0, $d[0]['fpoints']);             // defense is not a fleet
    }

    public function testCalcLossesCountsTheLostAttackers(): void
    {
        $a = [$this->force([GID_F_LF => 10])];
        $d = [$this->force([], 0, 0, 0, BATTLE_PTCP_PLANET)];
        $res = $this->withLastRound($this->battleRes([GID_F_LF => 10], []), [GID_F_LF => 4], []);

        $loss = CalcLosses($a, $d, $res, $this->noRepairs());

        // 6 light fighters at 3000 metal + 1000 crystal = 4000 points each.
        $this->assertSame(6 * 4000, $loss['aloss']);
        $this->assertSame(0, $loss['dloss']);
        // 'points' is the loss of the participant (AdjustStats subtracts it).
        $this->assertSame(6 * 4000, $a[0]['points']);
        $this->assertSame(6, $a[0]['fpoints']);             // 6 fighters lost
    }

    public function testCalcLossesTakesTheRepairedDefenseIntoAccount(): void
    {
        $a = [$this->force([GID_F_LF => 1])];
        $d = [$this->force([GID_D_RL => 3], 0, 0, 0, BATTLE_PTCP_PLANET)];
        $res = $this->withLastRound($this->battleRes([GID_F_LF => 1], [GID_D_RL => 3]), [GID_F_LF => 1], [GID_D_RL => 1]);

        // Two of the three launchers were destroyed but both are repaired, so
        // the defender keeps 1 + 2 = 3 launchers and loses nothing.
        $loss = CalcLosses($a, $d, $res, [0 => [GID_D_RL => 2]]);

        $this->assertSame(0, $loss['dloss']);
        $this->assertSame(0, $d[0]['points']);
    }

    public function testCalcLossesCountsDefensePointsButNotFleetPointsForFleetsOnHold(): void
    {
        $a = [$this->force([GID_F_LF => 1])];
        $d = [$this->force([GID_D_RL => 4, GID_F_LF => 3], 0, 0, 0, BATTLE_PTCP_FLEET)];
        $res = $this->withLastRound($this->battleRes([GID_F_LF => 1], [GID_D_RL => 4, GID_F_LF => 3]), [GID_F_LF => 1], [], BATTLE_PTCP_FLEET);

        $loss = CalcLosses($a, $d, $res, [0 => [GID_D_RL => 0]]);

        $this->assertSame(4 * 2000 + 3 * 4000, $loss['dloss']);
        $this->assertSame(3, $d[0]['fpoints']);             // only the three fighters
    }

    public function testCalcLossesWithoutRoundsReportsNoLoss(): void
    {
        $a = [$this->force([GID_F_LF => 10])];
        $d = [$this->force([GID_D_RL => 3], 0, 0, 0, BATTLE_PTCP_PLANET)];

        $loss = CalcLosses($a, $d, ['rounds' => []], []);

        $this->assertSame(0, $loss['aloss']);
        $this->assertSame(0, $loss['dloss']);
        $this->assertSame(0, $a[0]['points']);
        $this->assertSame(0, $a[0]['fpoints']);
        $this->assertSame(0, $d[0]['points']);
        $this->assertSame(0, $d[0]['fpoints']);
    }

    // ========================================================================
    // battle.php -- CalcDebris() and GetDebrisTotal()
    // ========================================================================

    public function testCalcDebrisFromLostAttackerShips(): void
    {
        $a = [$this->force([GID_F_LF => 10])];
        $d = [$this->force([GID_D_RL => 3], 0, 0, 0, BATTLE_PTCP_PLANET)];
        $res = $this->withLastRound($this->battleRes([GID_F_LF => 10], [GID_D_RL => 3]), [GID_F_LF => 4], [GID_D_RL => 3]);

        CalcDebris($a, $d, $res, $this->noRepairs(), 30, 30);

        // 6 light fighters at 3000 metal / 1000 crystal with a 30 % debris rate.
        $this->assertSame(5400, $a[0]['debris'][GID_RC_METAL]);
        $this->assertSame(1800, $a[0]['debris'][GID_RC_CRYSTAL]);
        $this->assertSame(0, $d[0]['debris'][GID_RC_METAL]);

        $total = GetDebrisTotal($a, $d);
        $this->assertSame(5400, $total[GID_RC_METAL]);
        $this->assertSame(1800, $total[GID_RC_CRYSTAL]);
    }

    public function testCalcDebrisFromLostDefenseIsReducedByTheRepairedUnits(): void
    {
        $a = [$this->force([GID_F_LF => 1])];
        $d = [$this->force([GID_D_RL => 10], 0, 0, 0, BATTLE_PTCP_PLANET)];
        $res = $this->withLastRound($this->battleRes([GID_F_LF => 1], [GID_D_RL => 10]), [GID_F_LF => 1], [GID_D_RL => 4]);

        // 6 launchers are gone but 2 of them are repaired, so only 4 count.
        CalcDebris($a, $d, $res, [0 => [GID_D_RL => 2]], 0, 30);

        $this->assertSame(0, $a[0]['debris'][GID_RC_METAL]);
        $this->assertSame(2400, $d[0]['debris'][GID_RC_METAL]);
        $this->assertSame(0, $d[0]['debris'][GID_RC_CRYSTAL]);
    }

    public function testCalcDebrisWithoutRoundsProducesNoDebris(): void
    {
        $a = [$this->force([GID_F_LF => 10])];
        $d = [$this->force([GID_D_RL => 3], 0, 0, 0, BATTLE_PTCP_PLANET)];
        $res = $this->battleRes([GID_F_LF => 10], [GID_D_RL => 3]);
        $res['rounds'] = [];

        CalcDebris($a, $d, $res, [], 30, 30);

        $this->assertSame(0, $a[0]['debris'][GID_RC_METAL]);
        $this->assertSame(0, $d[0]['debris'][GID_RC_METAL]);
        $this->assertSame([GID_RC_METAL => 0, GID_RC_CRYSTAL => 0], GetDebrisTotal($a, $d));
    }

    public function testGetDebrisTotalSumsEveryParticipant(): void
    {
        $a = [
            0 => ['debris' => [GID_RC_METAL => 100, GID_RC_CRYSTAL => 10]],
            1 => ['debris' => [GID_RC_METAL => 200, GID_RC_CRYSTAL => 20]],
        ];
        $d = [0 => ['debris' => [GID_RC_METAL => 300, GID_RC_CRYSTAL => 30]]];

        $total = GetDebrisTotal($a, $d);

        $this->assertSame(600, $total[GID_RC_METAL]);
        $this->assertSame(60, $total[GID_RC_CRYSTAL]);

        $noAttackers = [];
        $noDefenders = [];
        $this->assertSame([GID_RC_METAL => 0, GID_RC_CRYSTAL => 0], GetDebrisTotal($noAttackers, $noDefenders));
    }

    // ========================================================================
    // battle.php -- CargoSummaryLastRound()
    // ========================================================================

    public function testCargoSummaryLastRoundUsesTheSurvivingAttackers(): void
    {
        $fixture = new FixtureBuilder();
        $fleetId = AddDBRow([
            'owner_id' => 1, 'mission' => FTYP_ATTACK, 'start_planet' => 1, 'target_planet' => 4,
            'flight_time' => 600, 'deploy_time' => 0, 'fuel' => 100,
            GID_RC_METAL => 100, GID_RC_CRYSTAL => 50, GID_RC_DEUTERIUM => 20,
            GID_F_SC => 10,
        ], 'fleet');

        $res = ['rounds' => [['attackers' => [['id' => $fleetId, 'units' => [GID_F_SC => 10]]]]]];

        // 10 small cargo * 5000 cargo = 50000, minus 170 loaded and 100 fuel.
        $this->assertSame(49730, CargoSummaryLastRound([], $res));
        // Without any round the attackers passed in are used instead.
        $this->assertSame(49730, CargoSummaryLastRound(
            [['id' => $fleetId, 'units' => [GID_F_SC => 10]]], ['rounds' => []]));
    }

    public function testCargoSummaryLastRoundNeverReturnsNegativeCargo(): void
    {
        $fixture = new FixtureBuilder();
        $fleetId = AddDBRow([
            'owner_id' => 1, 'mission' => FTYP_ATTACK, 'start_planet' => 1, 'target_planet' => 4,
            'flight_time' => 600, 'deploy_time' => 0, 'fuel' => 99999,
            GID_F_SC => 10,
        ], 'fleet');

        $res = ['rounds' => [['attackers' => [['id' => $fleetId, 'units' => [GID_F_SC => 10]]]]]];

        $this->assertSame(0, CargoSummaryLastRound([], $res));
    }

    // ========================================================================
    // battle.php -- GenBattleSourceData() and PostProcessBattleResult()
    // ========================================================================

    public function testGenBattleSourceDataWithoutRapidfireOmitsTheTable(): void
    {
        $source = GenBattleSourceData(
            [0 => $this->sourceForce(GID_F_LF, 10, 3, 3, 4)],
            [0 => $this->sourceForce(GID_D_RL, 3, 5, 2, 1)],
            0, 6);

        $lines = explode("\n", $source);
        $this->assertSame('MaxRound = 6', $lines[0]);
        $this->assertSame('Rapidfire = 0', $lines[1]);
        $this->assertStringStartsWith('UnitParam =', $lines[2]);
        $this->assertStringNotContainsString('RFTab', $source);
        $this->assertStringContainsString('Attackers = 1', $source);
        $this->assertStringContainsString('Defenders = 1', $source);
        // Weapon, shield and armour technologies followed by unit id / amount.
        $this->assertStringContainsString('Attacker0 = 3 3 4 204 10', $source);
        $this->assertStringContainsString('Defender0 = 5 2 1 401 3', $source);
    }

    public function testGenBattleSourceDataWithRapidfireWritesTheWholeTable(): void
    {
        $source = GenBattleSourceData(
            [0 => $this->sourceForce(GID_F_LF, 10, 3, 3, 4)],
            [0 => $this->sourceForce(GID_D_RL, 3, 5, 2, 1)],
            1, 4);

        // Small cargo rapid-fires at probes and satellites.
        $this->assertStringStartsWith("MaxRound = 4\nRapidfire = 1\nRFTab = 202 2 210 5 212 5", $source);
        // Each unit parameter block is the gid followed by its six stats.
        $this->assertStringContainsString('UnitParam = 202 4000 10 5 5000 5000 10 203 12000 25 5 25000 7500 50', $source);
        $this->assertStringContainsString(' 401 2000 20 80 0 0 0', $source);
    }

    public function testGenBattleSourceDataLeavesTheGlobalUnitParametersAlone(): void
    {
        $before = $GLOBALS['UnitParam'];

        GenBattleSourceData(
            [0 => $this->sourceForce(GID_F_LF, 10, 3, 3, 4)],
            [0 => $this->sourceForce(GID_D_RL, 3, 5, 2, 1)],
            1, 6);

        $this->assertSame($before, $GLOBALS['UnitParam']);
    }

    public function testPostProcessBattleResultCopiesParticipantMetadataIntoEveryRound(): void
    {
        $a = [$this->force([GID_F_LF => 10], 3, 3, 4, BATTLE_PTCP_FLEET, 77, 'Attacker', 1, 1, 4)];
        $d = [$this->force([GID_D_RL => 3], 5, 2, 1, BATTLE_PTCP_PLANET, 88, 'Defender', 1, 3, 4)];
        $res = [
            'before' => [
                'attackers' => [['units' => [GID_F_LF => 10]]],
                'defenders' => [['units' => [GID_D_RL => 3]]],
            ],
            'rounds' => [[
                'attackers' => [['units' => [GID_F_LF => 10]]],
                'defenders' => [['units' => [GID_D_RL => 1]]],
            ]],
        ];

        PostProcessBattleResult($a, $d, $res);

        $this->assertSame('Attacker', $res['before']['attackers'][0]['name']);
        $this->assertSame(77, $res['before']['attackers'][0]['id']);
        $this->assertSame(BATTLE_PTCP_FLEET, $res['before']['attackers'][0]['pf']);
        $this->assertSame('Defender', $res['before']['defenders'][0]['name']);
        $this->assertSame(88, $res['before']['defenders'][0]['id']);
        $this->assertSame(BATTLE_PTCP_PLANET, $res['before']['defenders'][0]['pf']);
        $this->assertSame('Attacker', $res['rounds'][0]['attackers'][0]['name']);
        $this->assertSame(4, $res['rounds'][0]['attackers'][0]['p']);
        $this->assertSame('Defender', $res['rounds'][0]['defenders'][0]['name']);
        $this->assertSame(3, $res['rounds'][0]['defenders'][0]['s']);
        $this->assertSame(BATTLE_PTCP_PLANET, $res['rounds'][0]['defenders'][0]['pf']);
        $this->assertSame([], $res['extra']);
    }

    // ========================================================================
    // battle_engine.php -- packed array-string helpers
    // ========================================================================

    public function testPackedWordIsBigEndianAndRoundTrips(): void
    {
        $arr = str_repeat("\0", 12);
        set_packed_word($arr, 0, 0x01020304);
        set_packed_word($arr, 1, 1);
        set_packed_word($arr, 2, 4294967295);

        $this->assertSame("\x01\x02\x03\x04", substr($arr, 0, 4));
        $this->assertSame([0x01020304, 1, 4294967295], $this->unpackWord($arr, 3));
    }

    public function testPackedWordTruncatesToTheIntegerPart(): void
    {
        $arr = str_repeat("\0", 4);
        set_packed_word($arr, 0, 150.9);

        $this->assertSame(150, get_packed_word($arr, 0));
    }

    public function testPackedHalfWrapsAtSixteenBits(): void
    {
        $arr = str_repeat("\0", 4);
        set_packed_half($arr, 0, 0x0201);
        set_packed_half($arr, 1, 70000);

        $this->assertSame("\x02\x01", substr($arr, 0, 2));
        $this->assertSame([0x0201, 70000 & 0xffff], $this->unpackHalf($arr, 2));
        $this->assertSame(4464, get_packed_half($arr, 1));
    }

    public function testHexArrayToTextFormatsThePackedString(): void
    {
        $this->assertSame('01abff', hex_array_to_text("\x01\xab\xff"));
        $this->assertSame('', hex_array_to_text(''));
    }

    // ========================================================================
    // battle_engine.php -- InitBattle(), ChargeShields(), WipeExploded(),
    // CheckFastDraw()
    // ========================================================================

    public function testInitBattlePacksUnitsWithSlotAndArmourAdjustedHull(): void
    {
        $slot = [
            0 => ['armr' => 0, 'units' => [GID_F_LF => 2]],
            1 => ['armr' => 4, 'units' => [GID_D_RL => 1]],
        ];
        $explo = $obj = $slots = $hull = $shld = '';

        InitBattle($slot, 2, 3, $explo, $obj, $slots, $hull, $shld);

        $this->assertSame("\0\0\0", $explo);
        $this->assertSame([GID_F_LF, GID_F_LF, GID_D_RL], $this->unpackHalf($obj, 3));
        $this->assertSame([0, 0, 1], [ord($slots[0]), ord($slots[1]), ord($slots[2])]);
        // Light fighter: 4000 * 0.1 * (10 + 0) / 10 = 400 hull.
        // Rocket launcher: 2000 * 0.1 * (10 + 4) / 10 = 280 hull.
        $this->assertSame([400, 400, 280], $this->unpackWord($hull, 3));
        $this->assertSame([0, 0, 0], $this->unpackWord($shld, 3));
    }

    public function testChargeShieldsSetsTheMaximumAndZeroesExplodedUnits(): void
    {
        $slot = [
            0 => ['shld' => 0, 'units' => []],
            1 => ['shld' => 4, 'units' => []],
        ];
        $obj = $this->packHalf([GID_F_LF, GID_D_RL]);
        $slots = "\0\1";
        $explo = "\0\0";
        $shld = $this->packWord([0, 0]);

        ChargeShields($slot, 2, $explo, $obj, $slots, $shld);
        // Light fighter: 10, rocket launcher: 20 * (10 + 4) / 10 = 28.
        $this->assertSame([10, 28], $this->unpackWord($shld, 2));

        $explo = chr(1) . "\0";
        $shld = $this->packWord([7, 0]);
        ChargeShields($slot, 2, $explo, $obj, $slots, $shld);
        $this->assertSame([0, 28], $this->unpackWord($shld, 2));
    }

    public function testWipeExplodedCompactsTheArraysAndCountsTheDead(): void
    {
        $obj = $this->packHalf([GID_F_LF, GID_F_SC, GID_D_RL]);
        $slots = "\0\1\2";
        $hull = $this->packWord([100, 200, 300]);
        $shld = $this->packWord([1, 2, 3]);
        $explo = chr(0) . chr(1) . chr(0);

        $ret = WipeExploded(3, $explo, $obj, $slots, $hull, $shld);

        $this->assertSame(1, $ret['exploded']);
        $this->assertSame([GID_F_LF, GID_D_RL], $this->unpackHalf($ret['obj_arr'], 2));
        $this->assertSame([100, 300], $this->unpackWord($ret['hull_arr'], 2));
        $this->assertSame([1, 3], $this->unpackWord($ret['shld_arr'], 2));
        $this->assertSame([0, 2], [ord($ret['slot_arr'][0]), ord($ret['slot_arr'][1])]);
        $this->assertSame("\0\0", $ret['explo_arr']);
    }

    public function testWipeExplodedOfAnEmptyForceReturnsEmptyArrays(): void
    {
        $obj = $slots = $hull = $shld = $explo = '';

        $ret = WipeExploded(0, $explo, $obj, $slots, $hull, $shld);

        $this->assertSame(0, $ret['exploded']);
        $this->assertSame('', $ret['obj_arr']);
        $this->assertSame('', $ret['hull_arr']);
        $this->assertSame('', $ret['explo_arr']);
    }

    public function testCheckFastDrawIsTrueOnlyWhileNothingIsDamaged(): void
    {
        $attackers = [['armr' => 0]];
        $defenders = [['armr' => 4]];
        $aunits = $this->packHalf([GID_F_LF]);
        $aslot = "\0";
        $dunits = $this->packHalf([GID_D_RL]);
        $dslot = "\0";

        $ahull = $this->packWord([400]);      // light fighter at full hull
        $dhull = $this->packWord([280]);      // rocket launcher at full hull
        $this->assertTrue(CheckFastDraw($aunits, $aslot, $ahull, 1, $attackers, $dunits, $dslot, $dhull, 1, $defenders));

        $dhull = $this->packWord([279]);
        $this->assertFalse(CheckFastDraw($aunits, $aslot, $ahull, 1, $attackers, $dunits, $dslot, $dhull, 1, $defenders));

        $ahull = $this->packWord([399]);
        $dhull = $this->packWord([280]);
        $this->assertFalse(CheckFastDraw($aunits, $aslot, $ahull, 1, $attackers, $dunits, $dslot, $dhull, 1, $defenders));
    }

    // ========================================================================
    // battle_engine.php -- RapidFire() and UnitShoot()
    // ========================================================================

    public function testRapidFireLooksUpTheTableAndUsesTheDocumentedOdds(): void
    {
        $GLOBALS['RapidFireLocal'] = [
            GID_F_CRUISER => [GID_F_LF => 6],
            GID_F_LF => [GID_F_PROBE => 0],
        ];

        // Unknown shooter, or a target that is not in the shooter's table.
        $this->assertSame(0, RapidFire(GID_F_PROBE, GID_F_LF));
        $this->assertSame(0, RapidFire(GID_F_CRUISER, GID_F_PROBE));
        // A zero chance entry never refires.
        $this->assertSame(0, RapidFire(GID_F_LF, GID_F_PROBE));

        // 1d100000 > 100000 / count is the documented refire test.
        mt_srand(777);
        $expected = mt_rand(1, RF_DICE) > RF_DICE / 6 ? 1 : 0;
        mt_srand(777);
        $this->assertSame($expected, RapidFire(GID_F_CRUISER, GID_F_LF));

        // A chance above 100 % always refires.
        $GLOBALS['RapidFireLocal'] = [GID_F_DEATHSTAR => [GID_F_LF => RF_DICE * 2]];
        $this->assertSame(1, RapidFire(GID_F_DEATHSTAR, GID_F_LF));
    }

    public function testUnitShootSubtractsDamageFromTheHullWhenThereIsNoShield(): void
    {
        $aunits = $this->packHalf([GID_F_LF]);
        $aslot = "\0";
        $ahull = $this->packWord([400]);
        $ashld = $this->packWord([0]);
        $dunits = $this->packHalf([GID_D_RL]);
        $dslot = "\0";
        $dhull = $this->packWord([200]);
        $dshld = $this->packWord([0]);
        $dexplo = "\0";
        $attackers = [['weap' => 0]];
        $defenders = [['shld' => 0, 'armr' => 0]];
        $absorbed = 0;

        $power = UnitShoot(0, 0, $aunits, $aslot, $ahull, $ashld, $attackers,
            $dunits, $dslot, $dhull, $dshld, $dexplo, $defenders, $absorbed);

        $this->assertEquals(50, $power);                     // light fighter attack
        $this->assertSame(150, get_packed_word($dhull, 0));  // 200 - 50
        $this->assertSame(0, $absorbed);
        $this->assertSame(0, ord($dexplo[0]));
        $this->assertSame(0, $GLOBALS['exploded_counter']);
    }

    public function testUnitShootAbsorbsTheDamageInTheShieldFirst(): void
    {
        // A small shield dome (2000 shield) absorbs a light fighter shot (50)
        // completely: floor(50 / 20) = 2 percent of the shield are taken away.
        $aunits = $this->packHalf([GID_F_LF]);
        $aslot = "\0";
        $ahull = $this->packWord([400]);
        $ashld = $this->packWord([0]);
        $dunits = $this->packHalf([GID_D_SDOME]);
        $dslot = "\0";
        $dhull = $this->packWord([2000]);
        $dshld = $this->packWord([2000]);
        $dexplo = "\0";
        $attackers = [['weap' => 0]];
        $defenders = [['shld' => 0, 'armr' => 0]];
        $absorbed = 0;

        $power = UnitShoot(0, 0, $aunits, $aslot, $ahull, $ashld, $attackers,
            $dunits, $dslot, $dhull, $dshld, $dexplo, $defenders, $absorbed);

        $this->assertEquals(50, $power);
        $this->assertSame(2000, get_packed_word($dhull, 0));
        $this->assertSame(1960, get_packed_word($dshld, 0));
        $this->assertEquals(50, $absorbed);
    }

    public function testUnitShootBreaksThroughADepletedShieldIntoTheHull(): void
    {
        // Rocket launcher: 200 hull, 20 shield. The 50 damage shot absorbs the
        // remaining 20 shield points and puts the other 30 into the hull.
        $aunits = $this->packHalf([GID_F_LF]);
        $aslot = "\0";
        $ahull = $this->packWord([400]);
        $ashld = $this->packWord([0]);
        $dunits = $this->packHalf([GID_D_RL]);
        $dslot = "\0";
        $dhull = $this->packWord([200]);
        $dshld = $this->packWord([20]);
        $dexplo = "\0";
        $attackers = [['weap' => 0]];
        $defenders = [['shld' => 0, 'armr' => 0]];
        $absorbed = 0;

        UnitShoot(0, 0, $aunits, $aslot, $ahull, $ashld, $attackers,
            $dunits, $dslot, $dhull, $dshld, $dexplo, $defenders, $absorbed);

        $this->assertSame(170, get_packed_word($dhull, 0));
        $this->assertSame(0, get_packed_word($dshld, 0));
        $this->assertEquals(20, $absorbed);
        $this->assertSame(0, ord($dexplo[0]));
    }

    public function testUnitShootMarksADestroyedUnitAsExploded(): void
    {
        $aunits = $this->packHalf([GID_F_LF]);
        $aslot = "\0";
        $ahull = $this->packWord([400]);
        $ashld = $this->packWord([0]);
        $dunits = $this->packHalf([GID_D_RL]);
        $dslot = "\0";
        $dhull = $this->packWord([40]);
        $dshld = $this->packWord([0]);
        $dexplo = "\0";
        $attackers = [['weap' => 0]];
        $defenders = [['shld' => 0, 'armr' => 0]];
        $absorbed = 0;

        $power = UnitShoot(0, 0, $aunits, $aslot, $ahull, $ashld, $attackers,
            $dunits, $dslot, $dhull, $dshld, $dexplo, $defenders, $absorbed);

        $this->assertEquals(50, $power);
        $this->assertSame(0, get_packed_word($dhull, 0));   // clamped at zero
        $this->assertSame(1, ord($dexplo[0]));
        $this->assertSame(1, $GLOBALS['exploded_counter']);
    }

    public function testUnitShootOnAnAlreadyExplodedTargetDealsNoDamage(): void
    {
        $aunits = $this->packHalf([GID_F_LF]);
        $aslot = "\0";
        $ahull = $this->packWord([400]);
        $ashld = $this->packWord([0]);
        $dunits = $this->packHalf([GID_D_RL]);
        $dslot = "\0";
        $dhull = $this->packWord([150]);
        $dshld = $this->packWord([0]);
        $dexplo = chr(1);
        $attackers = [['weap' => 0]];
        $defenders = [['shld' => 0, 'armr' => 0]];
        $absorbed = 0;
        $GLOBALS['already_exploded_counter'] = 0;

        $power = UnitShoot(0, 0, $aunits, $aslot, $ahull, $ashld, $attackers,
            $dunits, $dslot, $dhull, $dshld, $dexplo, $defenders, $absorbed);

        $this->assertEquals(50, $power);                    // the shot is counted
        $this->assertSame(150, get_packed_word($dhull, 0)); // but nothing happens
        $this->assertSame(0, $GLOBALS['exploded_counter']);
        $this->assertSame(1, $GLOBALS['already_exploded_counter']);
    }

    // ========================================================================
    // battle_engine.php -- input parsers
    // ========================================================================

    public function testDeserializeSlotParsesTechnologiesAndUnits(): void
    {
        $GLOBALS['UnitParamLocal'] = $GLOBALS['UnitParam'];

        $slot = deserialize_slot('10.0 13.0 13.0 202 5 204 3');

        $this->assertSame(10.0, $slot['weap']);
        $this->assertSame(13.0, $slot['shld']);
        $this->assertSame(13.0, $slot['armr']);
        $this->assertSame([GID_F_SC => 5, GID_F_LF => 3], $slot['units']);

        // Note: an empty string, an odd number of unit tokens or an unknown gid
        // reach Error(), which writes an error row and calls exit(); those paths
        // cannot be asserted inside a PHPUnit test.
        $single = deserialize_slot('0 0 0 401 1');
        $this->assertSame([GID_D_RL => 1], $single['units']);
    }

    public function testParseUnitParamReadsSixValuesPerUnit(): void
    {
        $params = ParseUnitParam('202 4000 10 5 5000 5000 10 401 2000 20 80 0 0 0');

        $this->assertCount(2, $params);
        $this->assertSame([4000, 10, 5, 5000, 5000, 10], $params[GID_F_SC]);
        $this->assertSame([2000, 20, 80, 0, 0, 0], $params[GID_D_RL]);
    }

    public function testParseRFTableDropsEntriesWithZeroTargets(): void
    {
        $GLOBALS['UnitParamLocal'] = $GLOBALS['UnitParam'];

        $table = ParseRFTable('202 2 210 5 212 5 210 0');

        $this->assertSame([GID_F_SC => [GID_F_PROBE => 5, GID_F_SAT => 5]], $table);
        $this->assertArrayNotHasKey(GID_F_PROBE, $table);
    }

    public function testParseInputFillsTheRoundLimitAndBothForces(): void
    {
        $source = "MaxRound = 4\n"
            . "Rapidfire = 1\n"
            . "RFTab = 202 1 210 5\n"
            . "UnitParam = 202 4000 10 5 5000 5000 10 210 1000 0 0 5 100000000 1\n"
            . "Attackers = 1\n"
            . "Defenders = 1\n"
            . "Attacker0 = 3 4 5 202 7\n"
            . "Defender0 = 0 0 0 210 2\n";

        $rf = -1;
        $maxRound = -1;
        $attackers = [];
        $defenders = [];

        ParseInput($source, $rf, $maxRound, $attackers, $defenders);

        $this->assertSame(1, $rf);
        $this->assertSame(4, $maxRound);
        $this->assertSame(3.0, $attackers[0]['weap']);
        $this->assertSame(4.0, $attackers[0]['shld']);
        $this->assertSame(5.0, $attackers[0]['armr']);
        $this->assertSame([GID_F_SC => 7], $attackers[0]['units']);
        $this->assertSame([GID_F_PROBE => 2], $defenders[0]['units']);
        // ParseInput exports the parsed unit parameter table for the engine.
        $this->assertSame([4000, 10, 5, 5000, 5000, 10], $GLOBALS['UnitParamLocal'][GID_F_SC]);
    }

    // ========================================================================
    // battle_engine.php -- DoBattle() and BattleEngine()
    // ========================================================================

    public function testDoBattleEndsInAQuickDrawWhenNobodyCanDealDamage(): void
    {
        // Espionage probes have neither attack nor shield, so not a single
        // point of damage is dealt and the engine stops after the first round.
        $res = ['before' => [
            'attackers' => [$this->force([GID_F_PROBE => 3])],
            'defenders' => [$this->force([GID_F_PROBE => 2], 0, 0, 0, BATTLE_PTCP_PLANET)],
        ]];

        mt_srand(4242);
        DoBattle($res, 0, 6);

        $this->assertSame('draw', $res['result']);
        $this->assertCount(1, $res['rounds']);
        $this->assertSame(3, array_sum($res['rounds'][0]['attackers'][0]['units']));
        $this->assertSame(2, array_sum($res['rounds'][0]['defenders'][0]['units']));
        $this->assertSame(3, $res['rounds'][0]['ashoot']);
        $this->assertSame(2, $res['rounds'][0]['dshoot']);
        $this->assertSame(0, $res['rounds'][0]['apower']);
        $this->assertSame(0, $res['rounds'][0]['dpower']);
        $this->assertArrayHasKey('peak_allocated', $res);
    }

    public function testDoBattleWipesOutASingleDefenderInTheFirstRound(): void
    {
        $res = ['before' => [
            'attackers' => [$this->force([GID_F_LF => 100])],
            'defenders' => [$this->force([GID_D_RL => 1], 0, 0, 0, BATTLE_PTCP_PLANET)],
        ]];

        mt_srand(1);
        DoBattle($res, 0, 6);

        $this->assertSame('awon', $res['result']);
        $this->assertCount(1, $res['rounds']);
        // Every light fighter fired exactly once (rapid fire is switched off).
        $this->assertSame(100, $res['rounds'][0]['ashoot']);
        $this->assertSame(100 * 50, $res['rounds'][0]['apower']);
        // The single rocket launcher still returned fire before it died.
        $this->assertSame(1, $res['rounds'][0]['dshoot']);
        $this->assertSame(80, $res['rounds'][0]['dpower']);
        $this->assertSame([], $res['rounds'][0]['defenders'][0]['units']);
        $this->assertSame(100, $res['rounds'][0]['attackers'][0]['units'][GID_F_LF]);
    }

    public function testDoBattleStopsAtTheConfiguredMaximumNumberOfRounds(): void
    {
        // Neither side can destroy the other within a few rounds: the light
        // fighter only scratches the large cargo, the large cargo cannot get
        // through the fighter's shield. The engine must stop at the limit.
        $before = [
            'attackers' => [$this->force([GID_F_LF => 1])],
            'defenders' => [$this->force([GID_F_LC => 1], 0, 0, 0, BATTLE_PTCP_PLANET)],
        ];

        $res = ['before' => $before];
        mt_srand(11);
        DoBattle($res, 0, 2);
        $this->assertSame('draw', $res['result']);
        $this->assertCount(2, $res['rounds']);
        // One shot per side per round, no unit is destroyed.
        $this->assertSame(1, $res['rounds'][0]['ashoot']);
        $this->assertSame(1, $res['rounds'][1]['ashoot']);
        $this->assertSame(1, $res['rounds'][0]['dshoot']);
        $this->assertSame(1, $res['rounds'][1]['dshoot']);
        $this->assertSame(1, array_sum($res['rounds'][1]['attackers'][0]['units']));
        $this->assertSame(1, array_sum($res['rounds'][1]['defenders'][0]['units']));

        $res = ['before' => $before];
        mt_srand(11);
        DoBattle($res, 0, 1);
        $this->assertSame('draw', $res['result']);
        $this->assertCount(1, $res['rounds']);
    }

    public function testBattleEngineRunsAWholeBattleFromTheSerializedSource(): void
    {
        // 100 light fighters against 5 rocket launchers is a clear attacker win;
        // the battle itself is seeded from microtime(), so only structural
        // properties and the (overwhelming) outcome are asserted.
        $source = GenBattleSourceData(
            [0 => $this->sourceForce(GID_F_LF, 100, 3, 3, 4)],
            [0 => $this->sourceForce(GID_D_RL, 5, 5, 2, 1)],
            1, 6);

        $res = BattleEngine($source);

        $this->assertIsInt($res['battle_seed']);
        $this->assertSame([GID_F_LF => 100], $res['before']['attackers'][0]['units']);
        $this->assertSame([GID_D_RL => 5], $res['before']['defenders'][0]['units']);
        $this->assertSame(3.0, $res['before']['attackers'][0]['weap']);
        $this->assertSame(5.0, $res['before']['defenders'][0]['weap']);
        $this->assertSame(1.0, $res['before']['defenders'][0]['armr']);
        $this->assertArrayHasKey('peak_allocated', $res);
        $this->assertGreaterThanOrEqual(1, count($res['rounds']));
        $this->assertLessThanOrEqual(6, count($res['rounds']));
        $this->assertSame('awon', $res['result']);
        $this->assertSame([], $res['rounds'][count($res['rounds']) - 1]['defenders'][0]['units']);
    }

    // ========================================================================
    // battle.php -- StartBattle() end to end
    // ========================================================================

    /** Read a planet row straight from the database. */
    private function planetRow(int $planetId): array
    {
        global $db_prefix;
        $result = dbquery("SELECT * FROM {$db_prefix}planets WHERE planet_id = $planetId");
        return dbarray($result);
    }

    /** Read the most recently created fleet row. */
    private function lastFleetRow(): array
    {
        global $db_prefix;
        $result = dbquery("SELECT * FROM {$db_prefix}fleet ORDER BY fleet_id DESC LIMIT 1");
        return dbarray($result);
    }

    /** Read the most recent battle log row. */
    private function lastBattleRow(): array
    {
        global $db_prefix;
        $result = dbquery("SELECT * FROM {$db_prefix}battledata ORDER BY battle_id DESC LIMIT 1");
        return dbarray($result);
    }

    /** Count the messages of a player, optionally only of one type. */
    private function countMessages(int $ownerId, int $pm = -1): int
    {
        global $db_prefix;
        $filter = $pm >= 0 ? " AND pm = $pm" : '';
        $result = dbquery("SELECT COUNT(*) AS cnt FROM {$db_prefix}messages WHERE owner_id = $ownerId" . $filter);
        $row = dbarray($result);
        return (int)$row['cnt'];
    }

    public function testStartBattleResolvesAnAttackAndWritesEverythingBack(): void
    {
        $fixture = (new FixtureBuilder())->createTestUniverse('en');
        global $GlobalUni, $db_prefix;
        $GlobalUni = $fixture->getUniData();
        $db_prefix = $fixture->getDbPrefix();

        // The universe must define the debris percentages and the PHP engine.
        dbquery("UPDATE {$db_prefix}uni SET fid = 30, did = 30, rapid = 0, defrepair = 0,
            defrepair_delta = 0, php_battle = 1, freeze = 0");

        // Attacker: fleet 2 (mission Attack, 1:1:4 -> 1:3:4) gets 1000 fighters.
        dbquery("UPDATE {$db_prefix}fleet SET `" . GID_F_LF . "` = 1000, `" . GID_F_SC . "` = 0,
            fuel = 300 WHERE fleet_id = 2");
        // Defender: planet 4 (1:3:4) gets all defenses and ships cleared. The
        // fixture's three espionage probes remain: they have no attack at all,
        // so they cannot damage the attacker but do make the defender return
        // fire for one round instead of losing before the first round.
        dbquery("UPDATE {$db_prefix}planets SET `" . GID_D_RL . "` = 0, `" . GID_D_LL . "` = 0,
            `" . GID_D_HL . "` = 0, `" . GID_D_GAUSS . "` = 0, `" . GID_D_ION . "` = 0,
            `" . GID_D_PLASMA . "` = 0, `" . GID_D_SDOME . "` = 0, `" . GID_D_LDOME . "` = 0,
            `" . GID_F_SC . "` = 0, `" . GID_F_LF . "` = 0 WHERE planet_id = 4");
        InvalidateUserCache();

        // StartBattle() writes its intermediate files next to the game; keep
        // track of what is there so that only its own leftovers are removed.
        $battleDir = __DIR__ . '/../game/battledata/battle_*.txt';
        $resultDir = __DIR__ . '/../game/battleresult/battle_*.txt';
        $before = array_merge(glob($battleDir) ?: [], glob($resultDir) ?: []);

        // StartBattle() stores the battle log with a raw, unescaped SQL string.
        // The generated title always contains single quotes
        // (onclick="fenster('index.php?...')"), so that UPDATE always fails and
        // dbquery() echoes the query plus the SQL error. Swallow that output so
        // the test stays quiet; the failure itself is asserted below.
        ob_start();
        try {
            $result = StartBattle(2, 4, time());
        } finally {
            ob_end_clean();
            foreach (array_merge(glob($battleDir) ?: [], glob($resultDir) ?: []) as $file) {
                if (!in_array($file, $before, true)) {
                    @unlink($file);
                }
            }
        }

        $this->assertSame(BATTLE_RESULT_AWON, $result);

        // The undefended planet is plundered: it keeps 20000-10000 metal,
        // 12000-6000 crystal and 6000-3000 deuterium (the full half, because the
        // 1000 fighters carry 49700 free cargo units).
        $planet = $this->planetRow(4);
        $this->assertEquals(10000, $planet[GID_RC_METAL]);
        $this->assertEquals(6000, $planet[GID_RC_CRYSTAL]);
        $this->assertEquals(3000, $planet[GID_RC_DEUTERIUM]);
        $this->assertEquals(0, $planet[GID_D_RL]);

        // The survivors fly home (mission + FTYP_RETURN) carrying the loot.
        $returnFlight = $this->lastFleetRow();
        $this->assertSame(FTYP_ATTACK + FTYP_RETURN, (int)$returnFlight['mission']);
        $this->assertSame(1000, (int)$returnFlight[GID_F_LF]);
        $this->assertEquals(10000, $returnFlight[GID_RC_METAL]);
        $this->assertEquals(6000, $returnFlight[GID_RC_CRYSTAL]);
        $this->assertEquals(3000, $returnFlight[GID_RC_DEUTERIUM]);

        // The defender receives the report body plus the report link.
        $this->assertSame(1, $this->countMessages(2, MTYP_BATTLE_REPORT_TEXT));
        $this->assertSame(1, $this->countMessages(2, MTYP_BATTLE_REPORT_LINK));

        // SUSPECTED BUG (documented, not fixed): the battle log row keeps an
        // empty title and report. battle.php interpolates the generated title
        // and report into a raw SQL string without addslashes()/quoting:
        //     $query = "UPDATE ... SET title = '".$subj."', report = '".$text."' ...";
        // $subj contains single quotes (onclick="fenster('index.php?...')"), so
        // the statement is always a syntax error and the admin battle log is
        // never persisted (and report text of localizations with apostrophes,
        // e.g. French "L'attaquant", has the same problem).
        $battle = $this->lastBattleRow();
        $this->assertNotFalse($battle);
        $this->assertGreaterThan(0, strlen((string)$battle['source']));
        $this->assertSame('', (string)$battle['title']);
        $this->assertSame('', (string)$battle['report']);
    }
}
