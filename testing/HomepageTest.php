<?php

use PHPUnit\Framework\TestCase;

class HomepageTest extends TestCase
{
    public function testHomePageIsAccessible()
    {
        $url = 'http://localhost';
        $content = @file_get_contents($url);
        
        if ($content === false) {
            $this->fail("Failed to retrieve page content: $url");
        }
        
        $this->assertIsString($content);
        $this->assertNotEmpty($content);
        
        // Checking for the presence of a specific text
        $this->assertStringContainsString('OGame', $content);
    }
    
    public function testPageContainsSpecificElement()
    {
        $url = 'http://localhost';
        $content = file_get_contents($url);
        
        // Checking HTML elements
        $this->assertStringContainsString('<html', $content);
        $this->assertStringContainsString('<body', $content);
        $this->assertStringContainsString('</html>', $content);
    }
}

?>