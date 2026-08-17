<?php

// Bootstrap for Golden Pages snapshot tests
// Sets up the test environment with SQLite in-memory database

require_once __DIR__ . '/FixtureBuilder.php';
require_once __DIR__ . '/PageRenderer.php';

// Create golden snapshots directory if it doesn't exist
$goldenDir = __DIR__ . '/golden/';
if (!is_dir($goldenDir)) {
    mkdir($goldenDir, 0755, true);
}
