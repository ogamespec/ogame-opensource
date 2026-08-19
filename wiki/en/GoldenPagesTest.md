# GoldenPagesTest

### Overview

`GoldenPagesTest` is a PHPUnit test class that performs snapshot testing (golden file testing) for OGame frontend pages. It renders each game page using a simulated test universe with 3 players and compares the generated HTML output against stored golden snapshot files.

The fixture universe is rich on purpose (issue #256 "Maximum golden test coverage of all game pages"): every player has moons, in-flight fleet missions of every type (attack, spy, transport, deploy, recycle, colonize, expedition and **destroy-on-moon**), active building/research/shipyard queues, messages of every type (private, spy report, battle report, expedition, alliance, misc), an alliance with ranks and applications, buddies, fleet templates, bans, debris fields and outer-space targets. Because of this the golden snapshots exercise the real page code paths instead of empty shells.

### How It Works

1. **Test Universe**: A fixture builder creates an in-memory SQLite database with 3 players (PlayerOne, PlayerTwo, PlayerThree), each having 3 planets plus moons, fleet data with queue events, active queues, messages, and notes.
2. **Page Rendering**: The `PageRenderer` class simulates the game's `index.php` entry point, loading all necessary core modules, locale files, and page files. It captures the rendered HTML output. POST-only pages (flotten2, flotten3, flottenversand, sprungtor) are rendered with `withPost()` which sets `$_POST` and switches the request method to POST. Since issue #258 ("Add more pages with POST request in GoldenPages"), **every page that handles `method() === "POST"`** is additionally rendered with `withPost()` (POST golden snapshots with the `*_post_*` suffix), so a page that looks fine on GET but breaks when the player interacts (POST) is caught by the snapshot comparison.
3. **Snapshot Comparison**: The generated HTML is compared against golden snapshot files stored in `testing/golden/`. Dynamic content (timestamps, countdowns, IDs, session tokens) is normalized before comparison.

### Golden Snapshot Files

Golden snapshots are stored in `testing/golden/` with the naming convention:

```
testing/golden/{page_name}_{playerIndex}.html
```

For example:
- `overview_p0.html` — Overview page for PlayerOne
- `overview_p1.html` — Overview page for PlayerTwo
- `buildings_shipyard_p0.html` — Buildings page (Shipyard tab) for PlayerOne
- `buildings_shipyard_post_p0.html` — same page rendered through its POST form (issue #258)

POST snapshots are named `{page}_{variant}_post_p{index}.html`; the POST-only
fleet pages from issue #256 (flotten2/flotten3/flottenversand/sprungtor) keep
their plain names because their snapshots already render the POST flow.

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
| `testPlayersHaveMoons` | Verifies every player has at least one moon |
| `testFleetQueueEventsExist` | Verifies fleet movements have queue events (drive the Overview events) |
| `testActiveBuildAndResearchQueue` | Verifies build/research/shipyard queues are populated |
| `testAllMessageTypesExist` | Verifies all MTYP_* message types exist for PlayerOne |
| `testOverviewPagePlayerOne/Two/Three` | Tests overview page rendering for each player |
| `testOverviewShowsFleetEvents` | Verifies overview shows own + enemy fleet events (incl. destroy-on-moon) |
| `testOverviewShowsMoonAndBuildQueue` | Verifies the moon and the active building are shown |
| `testOverviewMoonView` | Tests the overview of the moon itself (cp=moon) |
| `testBuildingsPageShipyardPlayerOne` | Tests buildings page (Shipyard tab) with ships and an active shipyard order |
| `testBuildingsPageDefensePlayerOne` | Tests buildings page (Defense tab) |
| `testBuildingsPageResearchPlayerOne` | Tests buildings page (Research tab) with an active research |
| `testBuildingPagePlayerOne` | Tests the b_building page (build queue interface) |
| `testInfosPage*` | Tests infos page for Metal/Crystal/Deuterium mines, Solar, Fusion, Storage, Missile Silo, Alliance Depot, Sensor Phalanx (on a moon), Jump Gate (on a moon), Small Cargo, Light Fighter, Deathstar, Rocket Launcher, Plasma Turret, Espionage, Weapons and a plain building (Robotics Factory) |
| `testMessagesPagePlayerOne` | Tests messages page with all folders enabled |
| `testMessages*FolderPlayerOne` | Tests each message folder (spy/combat/expedition/alliance/private) |
| `testBerichtBattleReportPlayerOne` | Tests the battle report viewer |
| `testBerichtSpyReportPlayerOne` | Tests the spy report viewer |
| `testWriteMessagesPagePlayerOne` | Tests write messages page |
| `testNotesPagePlayerOne` | Tests notes page |
| `testFleetPage1PlayerOne` | Tests fleet page 1 (fleet list + ship selection) |
| `testFleetPage2PlayerOne` | Tests fleet page 2 (POST: coordinates and fleet summary) |
| `testFleetPage3PlayerOne` | Tests fleet page 3 (POST: mission list and resources) |
| `testFleetDispatchAttackPlayerOne` | Tests fleet dispatch (POST: attack mission) |
| `testFleetTemplatesPagePlayerOne` | Tests fleet templates page |
| `testGalaxyPagePlayerOne` | Tests galaxy page with moons |
| `testGalaxyPageEnemySystem` | Tests galaxy page of the enemy system (moon + debris field) |
| `testGalaxyPageFromMoon` | Tests galaxy page opened from a moon |
| `testPhalanxScanPlayerOne` | Tests a sensor phalanx scan with detected fleets |
| `testPhalanxScanNoFleets` | Tests a phalanx scan without fleets at the target |
| `testSprungtorPagePlayerOne` | Tests the jump gate page (POST error state) |
| `testImperiumPagePlayerOne` | Tests imperium (empire) page, planets view |
| `testImperiumPageMoonsPlayerOne` | Tests imperium page, moons view |
| `testTechtreePagePlayerOne` | Tests techtree page |
| `testTechtreeDetailsPagePlayerOne` | Tests techtree details page (Deathstar tree) |
| `testAllianzenPagePlayerOne` | Tests alliance home page |
| `testAllianzenMembersPagePlayerOne` | Tests alliance member list (a=4) |
| `testAllianzenRanksPagePlayerOne` | Tests alliance ranks page (a=6) |
| `testAllianzenSettingsPagePlayerOne` | Tests alliance settings page (a=5) |
| `testAllianzenMemberSettingsPagePlayerOne` | Tests alliance member settings page (a=7) |
| `testBewerbungenPagePlayerOne` | Tests alliance applications list |
| `testBewerbungenDetailPagePlayerOne` | Tests alliance application detail |
| `testBewerbenPagePlayerOne` | Tests apply-to-alliance page |
| `testBuddyPagePlayerOne` | Tests buddy list (accepted buddy) |
| `testBuddyRequestsPagePlayerOne` | Tests incoming buddy requests (action=5) |
| `testStatisticsPagePlayerOne` | Tests statistics page |
| `testOptionsPagePlayerOne` | Tests options page |
| `testChangelogPagePlayerOne` | Tests changelog page |
| `testResourcesPagePlayerOne` | Tests resources page |
| `testTraderPagePlayerOne` | Tests trader page (active trade offer) |
| `testMicropaymentPagePlayerOne` | Tests micropayment page |
| `testPaymentPagePlayerOne` | Tests payment page (coupon form) |
| `testPrangerPage` | Tests pranger (external) page with a ban |
| `testAinfoPage` | Tests ainfo (external) page |
| `testSuchePagePlayerOne` | Tests the search page |
| `testRenamePlanetPagePlayerOne` | Tests the planet menu page |
| `testLogoutPagePlayerOne` | Tests the logout page |
| `testAdminPage` | Tests the admin panel home page |
| `testPlayerTwoPlanetOverview` | Tests planet switching for PlayerTwo |
| `testPlayerThreePlanetOverview` | Tests planet switching for PlayerThree |
| `testOverviewPageDeterministic` | Verifies page rendering is deterministic |
| `testDifferentPlayersHaveDifferentContent` | Verifies different players have different page content |
| `testResourcesPageShowsCorrectResources` | Verifies resources page shows correct values |
| `testAvailablePagesCanBeListed` | Verifies pages can be listed from router.json |
| `testEveryRouterPageHasGoldenCoverage` | Verifies every router.json page has at least one golden snapshot |
| `testFixturePlanetCounts` | Verifies correct number of planets per player |
| `testFleetDataForPlayerOne` | Verifies fleet data exists for PlayerOne |
| `testMessagesForPlayerOne` | Verifies messages exist for PlayerOne |
| `testNotesForPlayerOne` | Verifies notes exist for PlayerOne |

The only router.json page without a snapshot is `allianzdepot`: the page unconditionally
redirects to `infos` (`MyGoto()` → `die()`) before producing output, so it cannot be
rendered in-process. Its UI (the alliance depot supply form) is covered by the
`infos_ally_depot` snapshot instead.

### POST request tests (issue #258)

Every game page that handles `method() === "POST"` gets a POST golden snapshot
(`{page}*_post_p{index}.html`). The tests submit the same POST payloads the real
forms send, so the page's POST handler runs against the real database:

| Test Method | POST Action Under Test |
|-------------|------------------------|
| `testFleet1RecallPostPlayerOne` | flotten1: recall an in-flight attack fleet (`order_return`) |
| `testBuildingsShipyardBuildPostPlayerOne` | buildings (Shipyard tab): build 2 Light Fighters (`fmenge[LF]`) |
| `testBuildingsDefenseBuildPostPlayerOne` | buildings (Defense tab): build 2 Rocket Launchers (`fmenge[RL]`) |
| `testResourcesPostPlayerOne` | resources: set production of every facility to 100% (`last{gid}`) |
| `testMessagesDeleteAllPostPlayerOne` | messages: delete all messages (`deletemessages=deleteall`) |
| `testOptionsPostPlayerOne` | options: save the settings form |
| `testPaymentCheckPostPlayerOne` | payment: check an unknown coupon code (`action=check`) |
| `testRenamePlanetPostPlayerOne` | renameplanet: rename the current planet (`aktion=Rename`) |
| `testSuchePlayerPostPlayerOne` | suche: search for a player name (`type=playername`) |
| `testSucheAllyPostPlayerOne` | suche: search for an alliance tag (`type=allytag`) |
| `testTraderCallPostPlayerOne` | trader: call the merchant with not enough DM (error state) |
| `testTraderExchangePostPlayerOne` | trader: zero-value exchange request (POST branch) |
| `testGalaxyNavigatePostPlayerOne` | galaxy: system-selection form (POST session/galaxy/system) |
| `testGalaxyRocketPostPlayerOne` | galaxy: launch an interplanetary missile (`aktion`/`anz`/`pziel`) |
| `testFleetTemplatesSavePostPlayerOne` | fleet_templates: save a new fleet template (`mode=save`) |
| `testBewerbenSubmitPostPlayerTwo` | bewerben: submit an alliance application (`weiter=Submit`) |
| `testAllianzenSettingsTextPostPlayerOne` | allianzen a=11&d=1: save the alliance text |
| `testAllianzenSettingsOptionsPostPlayerOne` | allianzen a=11&d=2: save open/homepage/logo/founder-name |
| `testAllianzenRanksCreatePostPlayerOne` | allianzen a=15: create a new rank (`newrangname`) |
| `testAllianzenMemberRankPostPlayerOne` | allianzen a=16&u=2: assign a rank to a member (`newrang`) |
| `testAllianzenCircularPostPlayerOne` | allianzen a=17: send a circular message |
| `testAllianzenChangeTagPostPlayerOne` | allianzen a=9: change the alliance tag (`newtag`) |
| `testAllianzenChangeNamePostPlayerOne` | allianzen a=10: change the alliance name (`newname`) |
| `testAllianzenDismissPostPlayerOne` | allianzen a=12: dismiss the alliance |
| `testAllianzenTakeoverPostPlayerOne` | allianzen a=18: transfer founder status (`s=1&uid=2`) |
| `testEveryPostPageHasGoldenCoverage` | coverage: every `method() === "POST"` page has a POST snapshot |

POST actions that **always redirect** (`MyGoto()` → `die()`) before any page
output cannot be snapshotted in the process-isolated renderer: `bewerbungen`
(accept/reject an application) and `payment` (activate a coupon) are documented
in `testEveryPostPageHasGoldenCoverage()` instead. The `admin` POST handlers
require admin rights and are skipped for the regular fixture players.

### HTML Normalization

Before comparison, HTML is normalized to handle dynamic content:

| Pattern | Replacement |
|---------|-------------|
| Timestamps (`YYYY-MM-DD HH:MM:SS`) | `DATE_TIME` |
| Statistics timestamps (`YYYY-MM-DD, HH:MM:SS`) | `DATE_TIME` |
| Overview timestamps (`Tue Aug 18 15:38:36`, single-digit hour too) | `DATE_TIME` |
| Timestamps (`Tue Aug 18 15:38:36 2026`, `Tue Aug 18 2026 15:38:36`) | `DATE_TIME` |
| Timestamps (`DD.MM.YYYY HH:MM:SS`, `MM-DD HH:MM:SS`) | `DATE_TIME` |
| Numeric timestamps (`10+ digits`) | `TIMESTAMP` |
| Countdowns (`pp="480"`, `ss=500;`, `g = 60;`, event `title='540'star=...`) | `SECONDS` |
| Floating point numbers | `FLOAT` |
| `planet_id=X` / `player_id=X` / `fleet_id=X` / `cp=X` / `spid=X` | `ID` |
| `session=XXXX` | `session=SESSION` |
| `lastpeek=X` | `lastpeek=TIMESTAMP` |
| Multiple whitespace | Single space |

### Architecture

```
testing/
├── GoldenPagesTest.php      # Main test class (each test in a separate process)
├── PageRenderer.php          # Renders real game pages through the real DB layer
├── FixtureBuilder.php        # Builds the test universe via the in-memory engine (mysqlite)
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
- Every page that handles `method() === "POST"` is rendered with
  `PageRenderer::withPost()` (issue #258), not just the POST-only fleet pages
  (flotten2/flotten3/flottenversand/sprungtor). POST pages whose handler always
  redirects (`MyGoto()` → `die()`) terminate the child test process, so they
  cannot be rendered at all — they are documented in
  `testEveryPostPageHasGoldenCoverage()`.
- The fixture alliance row sets `nextrank`/`tag_until`/`name_until`/`old_tag`/
  `old_name` like `CreateAlly()` does (game/core/ally.php): `AddRank()` (allianzen
  ranks POST) returns `$ally['nextrank']`, and the change-tag/name checks compare
  `$now < $ally['tag_until']` / `$now < $ally['name_until']`.
- The fixture fleet queue events use `start = now - 60 s` so that the
  flottenversand anti-spam check (`abs(time() - start) < 1`) does not redirect.
- `game/pages/flotten1.php` excludes debris fields from the target-owner column
  (a null-user guard), which the recycle-mission snapshot surfaced.
