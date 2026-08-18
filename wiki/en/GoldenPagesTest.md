# GoldenPagesTest

### Overview

`GoldenPagesTest` is a PHPUnit test class that performs snapshot testing (golden file testing) for OGame frontend pages. It renders each game page using a simulated test universe with 3 players and compares the generated HTML output against stored golden snapshot files.

### How It Works

1. **Test Universe**: A fixture builder creates an in-memory SQLite database with 3 players (PlayerOne, PlayerTwo, PlayerThree), each having 3 planets, fleet data, messages, and notes.
2. **Page Rendering**: The `PageRenderer` class simulates the game's `index.php` entry point, loading all necessary core modules, locale files, and page files. It captures the rendered HTML output.
3. **Snapshot Comparison**: The generated HTML is compared against golden snapshot files stored in `testing/golden/`. Dynamic content (timestamps, IDs, session tokens) is normalized before comparison.

### Golden Snapshot Files

Golden snapshots are stored in `testing/golden/` with the naming convention:

```
testing/golden/{page_name}_{playerIndex}.html
```

For example:
- `overview_p0.html` — Overview page for PlayerOne
- `overview_p1.html` — Overview page for PlayerTwo
- `buildings_shipyard_p0.html` — Buildings page (Shipyard tab) for PlayerOne

### Running the Tests

```bash
# Run all golden page tests
vendor/bin/phpunit --testsuite "Golden Pages"

# Run a specific test
vendor/bin/phpunit --filter testOverviewPagePlayerOne

# Run tests and skip golden comparison (output only)
# The HTML will be printed to stdout but not compared
```

### Updating Golden Snapshots

When page layouts change, golden snapshots need to be regenerated:

```bash
UPDATE_GOLDEN=1 vendor/bin/phpunit --testsuite "Golden Pages"
```

This will overwrite all existing golden snapshot files with the newly rendered HTML.

### Test Structure

| Test Method | Description |
|-------------|-------------|
| `testUniverseHasThreePlayers` | Verifies the test universe has exactly 3 players |
| `testEachPlayerHasHomePlanet` | Verifies each player has a home planet |
| `testUniverseSettingsAreConfigured` | Verifies universe settings (num, galaxies, systems, lang) |
| `testOverviewPagePlayerOne` | Tests overview page rendering for PlayerOne |
| `testOverviewPagePlayerTwo` | Tests overview page rendering for PlayerTwo |
| `testOverviewPagePlayerThree` | Tests overview page rendering for PlayerThree |
| `testBuildingsPageShipyardPlayerOne` | Tests buildings page (Shipyard tab) |
| `testBuildingsPageDefensePlayerOne` | Tests buildings page (Defense tab) |
| `testBuildingsPageResearchPlayerOne` | Tests buildings page (Research tab) |
| `testInfosPageMetalMinePlayerOne` | Tests infos page for Metal Mine |
| `testMessagesPagePlayerOne` | Tests messages page |
| `testNotesPagePlayerOne` | Tests notes page |
| `testStatisticsPagePlayerOne` | Tests statistics page |
| `testOptionsPagePlayerOne` | Tests options page |
| `testChangelogPagePlayerOne` | Tests changelog page |
| `testResourcesPagePlayerOne` | Tests resources page |
| `testFleetPage1PlayerOne` | Tests fleet page |
| `testFleetTemplatesPagePlayerOne` | Tests fleet templates page |
| `testBuddyPagePlayerOne` | Tests buddy page |
| `testAllianzenPagePlayerOne` | Tests alliance page |
| `testImperiumPagePlayerOne` | Tests imperium (empire) page |
| `testGalaxyPagePlayerOne` | Tests galaxy page |
| `testTechtreePagePlayerOne` | Tests techtree page |
| `testTraderPagePlayerOne` | Tests trader page |
| `testMicropaymentPagePlayerOne` | Tests micropayment page |
| `testPrangerPage` | Tests pranger (external) page |
| `testAinfoPage` | Tests ainfo (external) page |
| `testWriteMessagesPagePlayerOne` | Tests write messages page |
| `testBewerbenPagePlayerOne` | Tests bewerben (apply to alliance) page |
| `testPlayerTwoPlanetOverview` | Tests planet switching for PlayerTwo |
| `testPlayerThreePlanetOverview` | Tests planet switching for PlayerThree |
| `testOverviewPageDeterministic` | Verifies page rendering is deterministic |
| `testDifferentPlayersHaveDifferentContent` | Verifies different players have different page content |
| `testResourcesPageShowsCorrectResources` | Verifies resources page shows correct values |
| `testAvailablePagesCanBeListed` | Verifies pages can be listed from router.json |
| `testFixturePlanetCounts` | Verifies correct number of planets per player |
| `testFleetDataForPlayerOne` | Verifies fleet data exists for PlayerOne |
| `testMessagesForPlayerOne` | Verifies messages exist for PlayerOne |
| `testNotesForPlayerOne` | Verifies notes exist for PlayerOne |

### HTML Normalization

Before comparison, HTML is normalized to handle dynamic content:

| Pattern | Replacement |
|---------|-------------|
| Timestamps (`YYYY-MM-DD HH:MM:SS`) | `DATE_TIME` |
| Statistics timestamps (`YYYY-MM-DD, HH:MM:SS`) | `DATE_TIME` |
| Overview timestamps (`Tue Aug 18 15:38:36`) | `DATE_TIME` |
| Timestamps (`DD.MM.YYYY HH:MM:SS`, `MM-DD HH:MM:SS`) | `DATE_TIME` |
| Numeric timestamps (`10+ digits`) | `TIMESTAMP` |
| Floating point numbers | `FLOAT` |
| `planet_id=X` | `planet_id=ID` |
| `player_id=X` | `player_id=ID` |
| `fleet_id=X` | `fleet_id=ID` |
| `cp=X` | `cp=ID` |
| `session=XXXX` | `session=SESSION` |
| `lastpeek=X` | `lastpeek=TIMESTAMP` |
| Multiple whitespace | Single space |

### Architecture

```
testing/
├── GoldenPagesTest.php      # Main test class (each test in a separate process)
├── PageRenderer.php          # Renders real game pages through the real DB layer
├── FixtureBuilder.php        # Builds the test universe via the in-memory engine (SQLite)
├── bootstrap.php             # PHPUnit bootstrap: loads the game core with the SQLite backend
└── golden/                   # Golden snapshots
    ├── .gitignore
    ├── overview_p0.html
    ├── overview_p1.html
    └── ...
```

### Notes

- The tests use the **real in-memory DB engine** from master
  (`game/core/db.php` + `game/core/db_sqlite.php`, `DB_CONNECTION=sqlite`).
  The schema is created from `install_tabs.php` via `CreateDBTables()` and the
  data is inserted with `AddDBRow()` — no hand-rolled schema, no mock DB.
- Each test runs in a separate PHP process (`#[RunTestsInSeparateProcesses]`,
  like NotesTest/DbSqliteTest): only the process-isolated child template loads
  the PHPUnit bootstrap at the true top level, where the game core declares
  the global variables the pages rely on.
- `PageRenderer` repeats the `game/index.php` boot flow (LoadUniverse, AuthUser,
  router from `router.json`, MVC/classic pages).
