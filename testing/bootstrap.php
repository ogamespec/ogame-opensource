<?php

// PHPUnit bootstrap for Golden Pages snapshot tests
// Sets up in-memory database fixture with 3 players and mocks for page rendering

spl_autoload_register(function ($class) {
    $testDir = __DIR__ . '/';
    $files = [
        $testDir . 'FixtureBuilder.php',
        $testDir . 'PageRenderer.php',
    ];
    foreach ($files as $file) {
        if (file_exists($file) && basename($file) === $class . '.php') {
            require_once $file;
            return;
        }
    }
});

// Global state for the test universe
$testUniverse = null;
$testPlayers = [];
$testRenderedPages = [];

// Golden snapshots directory
$goldenDir = __DIR__ . '/golden/';
if (!is_dir($goldenDir)) {
    mkdir($goldenDir, 0755, true);
}

// Register shutdown function to save last rendered page HTML
register_shutdown_function(function () {
    // Save any pending output
});
