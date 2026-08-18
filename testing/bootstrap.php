<?php

// PHPUnit bootstrap for the OGame Open Source test suite.
//
// Loads the game core with the alternate in-memory SQLite backend
// (DB_CONNECTION=sqlite, DB_DATABASE=:memory:), so tests run without a
// MySQL server. The files are required here at the true top level: PHPUnit
// loads the bootstrap in the global scope, and the game files assign global
// variables ($Languages, $LOCA, $UserCache, ...) that the game functions
// rely on.
//
// Note: the bootstrap runs in the parent PHPUnit process and in every
// process-isolated child, so the environment is identical everywhere.

require_once __DIR__ . '/../vendor/autoload.php';

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

// Game constants (GID_*, USER_TYPE_*, ...).
require_once __DIR__ . '/../game/core/defs.php';
require_once __DIR__ . '/../game/core/techs.php';

// The DB layer: the dispatcher picks the SQLite backend.
require_once __DIR__ . '/../game/core/db.php';
require_once __DIR__ . '/../game/core/loca.php';
require_once __DIR__ . '/../game/core/user.php';
require_once __DIR__ . '/../game/core/notes.php';

// Autoloader for the Golden Pages helpers (FixtureBuilder, PageRenderer).
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
