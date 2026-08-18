<?php

use PHPUnit\Framework\TestCase;

// Homepage smoke tests.
//
// These tests need a running web server on http://localhost (the game
// directory must be served). If the server is not reachable, the tests are
// skipped instead of failing, so the rest of the suite can still run on
// machines without a web server (e.g. during unit testing in CI).

class HomepageTest extends TestCase
{
    /** @var string|null Homepage content fetched once in setUp(). */
    private ?string $pageContent = null;

    protected function setUp(): void
    {
        $url = 'http://localhost';
        $content = @file_get_contents($url);

        if ($content === false) {
            $this->markTestSkipped("Game server is not available at $url - start the web server to run these tests.");
        }

        $this->pageContent = $content;
    }

    public function testHomePageIsAccessible(): void
    {
        $this->assertIsString($this->pageContent);
        $this->assertNotEmpty($this->pageContent);

        // Checking for the presence of a specific text.
        $this->assertStringContainsString('OGame', $this->pageContent);
    }

    public function testPageContainsSpecificElement(): void
    {
        // Checking HTML elements.
        $this->assertStringContainsString('<html', $this->pageContent);
        $this->assertStringContainsString('<body', $this->pageContent);
        $this->assertStringContainsString('</html>', $this->pageContent);
    }
}

?>
