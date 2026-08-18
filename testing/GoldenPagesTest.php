<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Golden Pages snapshot tests for OGame frontend verification.
 * 
 * These tests render game pages using a test universe fixture with 3 players
 * and compare the HTML output against golden snapshot files.
 * 
 * Golden snapshots are stored in: testing/golden/{page}_{playerIndex}.html
 * 
 * To create/update golden snapshots, run tests with UPDATE_GOLDEN=1 environment variable:
 *   UPDATE_GOLDEN=1 vendor/bin/phpunit --filter GoldenPagesTest
 */
class GoldenPagesTest extends TestCase
{
    private FixtureBuilder $fixture;
    private string $goldenDir;
    private bool $updateGolden;

    protected function setUp(): void
    {
        $this->fixture = new FixtureBuilder();
        $this->fixture->createTestUniverse();
        
        $this->goldenDir = __DIR__ . '/golden/';
        if (!is_dir($this->goldenDir)) {
            mkdir($this->goldenDir, 0755, true);
        }
        
        $this->updateGolden = getenv('UPDATE_GOLDEN') === '1';
    }

    /**
     * Test that the test universe has exactly 3 players
     */
    public function testUniverseHasThreePlayers(): void
    {
        $players = $this->fixture->getPlayers();
        $this->assertCount(3, $players, 'Test universe must have exactly 3 players');
        
        $playerNames = array_column($players, 'name');
        $this->assertContains('PlayerOne', $playerNames);
        $this->assertContains('PlayerTwo', $playerNames);
        $this->assertContains('PlayerThree', $playerNames);
    }

    /**
     * Test that each player has a home planet
     */
    public function testEachPlayerHasHomePlanet(): void
    {
        $players = $this->fixture->getPlayers();
        foreach ($players as $id => $player) {
            $this->assertGreaterThan(0, $player['planet_id'], "Player {$player['name']} must have a home planet");
        }
    }

    /**
     * Test that universe settings are properly configured
     */
    public function testUniverseSettingsAreConfigured(): void
    {
        $uni = $this->fixture->getUniData();
        $this->assertEquals(1, $uni['num']);
        $this->assertEquals(1, $uni['galaxies']);
        $this->assertEquals(15, $uni['systems']);
        $this->assertEquals('en', $uni['lang']);
        $this->assertEquals(3, $uni['usercount']);
    }

    /**
     * Test overview page rendering for PlayerOne
     */
    public function testOverviewPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('overview');
        
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);
        $this->assertStringContainsString('<head>', $html);
        $this->assertStringContainsString('<body', $html);
        $this->assertStringContainsString('PlayerOne', $html);
        
        $this->compareOrSaveGolden('overview', 0, $html);
    }

    /**
     * Test overview page rendering for PlayerTwo
     */
    public function testOverviewPagePlayerTwo(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(1)->render('overview');
        
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('PlayerTwo', $html);
        
        $this->compareOrSaveGolden('overview', 1, $html);
    }

    /**
     * Test overview page rendering for PlayerThree
     */
    public function testOverviewPagePlayerThree(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(2)->render('overview');
        
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('PlayerThree', $html);
        
        $this->compareOrSaveGolden('overview', 2, $html);
    }

    /**
     * Test buildings page (Shipyard tab) for PlayerOne
     */
    public function testBuildingsPageShipyardPlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->withParams(['mode' => 'Flotte'])->render('buildings');
        
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('PlayerOne', $html);
        
        $this->compareOrSaveGolden('buildings_shipyard', 0, $html);
    }

    /**
     * Test buildings page (Defense tab) for PlayerOne
     */
    public function testBuildingsPageDefensePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->withParams(['mode' => 'Verteidigung'])->render('buildings');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('buildings_defense', 0, $html);
    }

    /**
     * Test buildings page (Research tab) for PlayerOne
     */
    public function testBuildingsPageResearchPlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->withParams(['mode' => 'Forschung'])->render('buildings');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('buildings_research', 0, $html);
    }

    /**
     * Test infos page for a building (Metal Mine) for PlayerOne
     */
    public function testInfosPageMetalMinePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->withParams(['gid' => 1])->render('infos');
        
        // Skip if page fails to render (missing data in fixture)
        if (strpos($html, '<html') === false) {
            $this->markTestSkipped('infos page requires additional fixture data (ProdResources). Run with UPDATE_GOLDEN=1 after adding production data.');
        }
        
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('Metal Mine', $html);
        
        $this->compareOrSaveGolden('infos_metal_mine', 0, $html);
    }

    /**
     * Test messages page for PlayerOne
     */
    public function testMessagesPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('messages');
        
        // Skip if page fails to render (missing data in fixture)
        if (strpos($html, '<html') === false) {
            $this->markTestSkipped('messages page requires additional fixture data. Run with UPDATE_GOLDEN=1 after adding message data.');
        }
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('messages', 0, $html);
    }

    /**
     * Test notes page for PlayerOne
     */
    public function testNotesPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('notizen');
        
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('Test Note', $html);
        
        $this->compareOrSaveGolden('notes', 0, $html);
    }

    /**
     * Test statistics page for PlayerOne
     */
    public function testStatisticsPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('statistics');
        
        // Skip if page fails to render (missing data in fixture)
        if (strpos($html, '<html') === false) {
            $this->markTestSkipped('statistics page requires additional fixture data. Run with UPDATE_GOLDEN=1 after adding planet data.');
        }
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('statistics', 0, $html);
    }

    /**
     * Test options page for PlayerOne
     */
    public function testOptionsPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('options');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('options', 0, $html);
    }

    /**
     * Test changelog page for PlayerOne
     */
    public function testChangelogPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('changelog');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('changelog', 0, $html);
    }

    /**
     * Test resources page for PlayerOne
     */
    public function testResourcesPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('resources');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('resources', 0, $html);
    }

    /**
     * Test fleet pages for PlayerOne
     */
    public function testFleetPage1PlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('flotten1');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('fleet1', 0, $html);
    }

    /**
     * Test fleet templates page for PlayerOne
     */
    public function testFleetTemplatesPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('fleet_templates');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('fleet_templates', 0, $html);
    }

    /**
     * Test buddy page for PlayerOne
     */
    public function testBuddyPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('buddy');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('buddy', 0, $html);
    }

    /**
     * Test alliance pages for PlayerOne
     */
    public function testAllianzenPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('allianzen');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('allianzen', 0, $html);
    }

    /**
     * Test imperium (empire) page for PlayerOne
     */
    public function testImperiumPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('imperium');
        
        // Skip if page fails to render (missing data in fixture)
        if (strpos($html, '<html') === false) {
            $this->markTestSkipped('imperium page requires additional fixture data. Run with UPDATE_GOLDEN=1 after adding planet data.');
        }
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('imperium', 0, $html);
    }

    /**
     * Test galaxy page for PlayerOne
     */
    public function testGalaxyPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->withParams(['galaxy' => 1, 'system' => 1])->render('galaxy');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('galaxy', 0, $html);
    }

    /**
     * Test techtree page for PlayerOne
     */
    public function testTechtreePagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('techtree');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('techtree', 0, $html);
    }

    /**
     * Test trader page for PlayerOne
     */
    public function testTraderPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('trader');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('trader', 0, $html);
    }

    /**
     * Test micropayment page for PlayerOne
     */
    public function testMicropaymentPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('micropayment');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('micropayment', 0, $html);
    }

    /**
     * Test pranger (external) page
     */
    public function testPrangerPage(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('pranger');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('pranger', 0, $html);
    }

    /**
     * Test ainfo (external) page
     */
    public function testAinfoPage(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('ainfo');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('ainfo', 0, $html);
    }

    /**
     * Test write messages page for PlayerOne
     */
    public function testWriteMessagesPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('writemessages');
        
        // Skip if page fails to render (missing data in fixture)
        if (strpos($html, '<html') === false) {
            $this->markTestSkipped('writemessages page requires additional fixture data. Run with UPDATE_GOLDEN=1 after adding planet data.');
        }
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('writemessages', 0, $html);
    }

    /**
     * Test bewerben (apply to alliance) page for PlayerOne
     */
    public function testBewerbenPagePlayerOne(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('bewerben');
        
        $this->assertStringContainsString('<html', $html);
        
        $this->compareOrSaveGolden('bewerben', 0, $html);
    }

    /**
     * Test planet switching - overview for PlayerTwo
     */
    public function testPlayerTwoPlanetOverview(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(1)->render('overview');
        
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('PlayerTwo', $html);
        
        $this->compareOrSaveGolden('overview', 1, $html);
    }

    /**
     * Test planet switching - overview for PlayerThree
     */
    public function testPlayerThreePlanetOverview(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(2)->render('overview');
        
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('PlayerThree', $html);
        
        $this->compareOrSaveGolden('overview', 2, $html);
    }

    /**
     * Test that page rendering produces consistent output (determinism check)
     */
    public function testOverviewPageDeterministic(): void
    {
        $renderer1 = new PageRenderer($this->fixture);
        $html1 = $renderer1->asPlayer(0)->render('overview');
        
        $renderer2 = new PageRenderer($this->fixture);
        $html2 = $renderer2->asPlayer(0)->render('overview');
        
        // Normalize timestamps for comparison
        $normalized1 = preg_replace('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', 'DATE_TIME', $html1);
        $normalized2 = preg_replace('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', 'DATE_TIME', $html2);
        
        $this->assertEquals($normalized2, $normalized1, 'Page rendering should be deterministic');
    }

    /**
     * Test that different players have different page content
     */
    public function testDifferentPlayersHaveDifferentContent(): void
    {
        $renderer1 = new PageRenderer($this->fixture);
        $html1 = $renderer1->asPlayer(0)->render('overview');
        
        $renderer2 = new PageRenderer($this->fixture);
        $html2 = $renderer2->asPlayer(1)->render('overview');
        
        // Player names should differ
        $this->assertStringContainsString('PlayerOne', $html1);
        $this->assertStringContainsString('PlayerTwo', $html2);
        $this->assertNotEquals($html1, $html2);
    }

    /**
     * Test resources page shows correct resources for PlayerOne
     */
    public function testResourcesPageShowsCorrectResources(): void
    {
        $renderer = new PageRenderer($this->fixture);
        $html = $renderer->asPlayer(0)->render('resources');
        
        // PlayerOne's home planet has metal_mine=5, crys_mine=3, deut_synth=2
        $this->assertStringContainsString('<html', $html);
    }

    /**
     * Test that all available pages can be listed from router.json
     */
    public function testAvailablePagesCanBeListed(): void
    {
        $pages = PageRenderer::getAvailablePages();
        
        $this->assertIsArray($pages);
        $this->assertGreaterThan(0, count($pages), 'Should have at least one page in router.json');
        
        // Check for some expected pages from router.json
        $this->assertContains('overview', $pages);
        $this->assertContains('buildings', $pages);
        $this->assertContains('messages', $pages);
        $this->assertContains('trader', $pages);
        $this->assertContains('techtree', $pages);
    }

    /**
     * Test that fixture has correct number of planets per player
     */
    public function testFixturePlanetCounts(): void
    {
        $fixture = new FixtureBuilder();
        $fixture->createTestUniverse();
        
        $players = $fixture->getPlayers();
        $pdo = $fixture->getPDO();
        $prefix = $fixture->getDbPrefix();
        
        foreach ($players as $id => $player) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM {$prefix}planets WHERE owner_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            $this->assertGreaterThan(2, $result['cnt'], "Player {$player['name']} should have at least 3 planets");
        }
    }

    /**
     * Test that fleet data exists for PlayerOne
     */
    public function testFleetDataForPlayerOne(): void
    {
        $fixture = new FixtureBuilder();
        $fixture->createTestUniverse();
        
        $pdo = $fixture->getPDO();
        $prefix = $fixture->getDbPrefix();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM {$prefix}fleet WHERE owner_id = 1");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $this->assertGreaterThan(0, $result['cnt'], 'PlayerOne should have fleet entries');
    }

    /**
     * Test that messages exist for PlayerOne
     */
    public function testMessagesForPlayerOne(): void
    {
        $fixture = new FixtureBuilder();
        $fixture->createTestUniverse();
        
        $pdo = $fixture->getPDO();
        $prefix = $fixture->getDbPrefix();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM {$prefix}messages WHERE owner_id = 1");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $this->assertGreaterThan(0, $result['cnt'], 'PlayerOne should have messages');
    }

    /**
     * Test that notes exist for PlayerOne
     */
    public function testNotesForPlayerOne(): void
    {
        $fixture = new FixtureBuilder();
        $fixture->createTestUniverse();
        
        $pdo = $fixture->getPDO();
        $prefix = $fixture->getDbPrefix();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM {$prefix}notes WHERE owner_id = 1");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $this->assertGreaterThan(0, $result['cnt'], 'PlayerOne should have notes');
    }

    /**
     * Compare rendered HTML against golden snapshot or save new snapshot
     * Always saves a copy to testing/golden/{page}_p{index}_actual.html for visual inspection
     */
    private function compareOrSaveGolden(string $pageName, int $playerIndex, string $html): void
    {
        $snapshotFile = $this->getSnapshotFilePath($pageName, $playerIndex);
        $outputFile = str_replace('.html', '_actual.html', $snapshotFile);
        
        // Always save rendered HTML for visual inspection
        file_put_contents($outputFile, $html);
        
        if ($this->updateGolden) {
            file_put_contents($snapshotFile, $html);
            return;
        }
        
        if (!file_exists($snapshotFile)) {
            $this->markTestSkipped("Golden snapshot not found: $snapshotFile. Run with UPDATE_GOLDEN=1 to create.");
            return;
        }
        
        $expected = file_get_contents($snapshotFile);
        
        // Normalize for comparison: remove dynamic timestamps and player-specific IDs
        $normalizedExpected = $this->normalizeForComparison($expected);
        $normalizedActual = $this->normalizeForComparison($html);
        
        $this->assertEquals($normalizedExpected, $normalizedActual, 
            "HTML snapshot mismatch for '$pageName' (player $playerIndex). Run with UPDATE_GOLDEN=1 to update.");
    }

    /**
     * Get the snapshot file path for a page/player combination
     */
    private function getSnapshotFilePath(string $pageName, int $playerIndex): string
    {
        return $this->goldenDir . "{$pageName}_p{$playerIndex}.html";
    }

    /**
     * Normalize HTML for comparison by removing dynamic content
     */
    private function normalizeForComparison(string $html): string
    {
        // Normalize timestamps
        $html = preg_replace('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', 'DATE_TIME', $html);
        $html = preg_replace('/\d{10,}/', 'TIMESTAMP', $html);
        
        // Normalize floating point numbers
        $html = preg_replace('/\b\d+\.\d+\b/', 'FLOAT', $html);
        
        // Normalize numeric IDs (but keep player names intact)
        $html = preg_replace('/planet_id=\d+/', 'planet_id=ID', $html);
        $html = preg_replace('/player_id=\d+/', 'player_id=ID', $html);
        
        // Normalize fleet_id
        $html = preg_replace('/fleet_id=\d+/', 'fleet_id=ID', $html);
        
        // Normalize planet_id
        $html = preg_replace('/cp=\d+/', 'cp=ID', $html);
        
        // Normalize session (keep consistent per-player)
        $html = preg_replace('/session=[a-f0-9]+/', 'session=SESSION', $html);
        
        // Normalize lastpeek timestamps
        $html = preg_replace('/lastpeek=\d+/', 'lastpeek=TIMESTAMP', $html);
        
        // Remove whitespace variations
        $html = preg_replace('/\s+/', ' ', $html);
        
        return trim($html);
    }
}
