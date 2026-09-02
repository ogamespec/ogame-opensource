<?php

// Bootstrap for the Deep Space Horror modification's own PHPUnit test suite.
//
// This loads the game core with the in-memory SQLite backend
// (DB_CONNECTION=sqlite, DB_DATABASE=:memory:) so the mod's DB-backed hooks can
// be tested without a MySQL server, then loads the Deep Space Horror
// modification itself (the DeepSpaceHorror class and its constants).
//
// The mod tests live here, inside the modification, because they are tied to a
// specific modification and should not pollute the repository's main test suite.

require_once __DIR__ . '/../../../../vendor/autoload.php';

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

// Game constants (GID_*, USER_TYPE_*, ...).
require_once __DIR__ . '/../../../../game/core/defs.php';
require_once __DIR__ . '/../../../../game/core/techs.php';

// The DB layer: the dispatcher picks the SQLite backend and defines CreateDBTables().
require_once __DIR__ . '/../../../../game/core/db.php';
require_once __DIR__ . '/../../../../game/core/loca.php';
require_once __DIR__ . '/../../../../game/core/user.php';
require_once __DIR__ . '/../../../../game/core/notes.php';

// The game runs with the game directory as the current working directory:
// core.php and the pages resolve relative includes (db.php, loca files,
// router.json, ...) against it.
chdir(__DIR__ . '/../../../../game');

// Load the whole game core (defines GameMod and the mod hook dispatchers).
require_once __DIR__ . '/../../../../game/core/core.php';

// Load the Deep Space Horror modification (DeepSpaceHorror class + constants).
require_once __DIR__ . '/../../../../game/mods/DeepSpaceHorror/main.php';
