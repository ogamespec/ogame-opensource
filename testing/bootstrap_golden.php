<?php

// Bootstrap for the Golden Pages snapshot tests.
//
// This is a thin wrapper around the main bootstrap (testing/bootstrap.php):
// it loads the game core with the alternate in-memory SQLite backend
// (DB_CONNECTION=sqlite, DB_DATABASE=:memory:) at the true top level, which
// is what the game pages need (their top-level code reads and assigns global
// variables).
//
// phpunit.xml points at testing/bootstrap.php directly; this file exists for
// standalone scripts that want to set up the golden test environment without
// running the whole PHPUnit bootstrap chain.

require_once __DIR__ . '/bootstrap.php';

// Create the golden snapshots directory if it doesn't exist.
$goldenDir = __DIR__ . '/golden/';
if (!is_dir($goldenDir)) {
    mkdir($goldenDir, 0755, true);
}
