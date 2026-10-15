# OGame Infinity 0.84

A browser extension that ports [OGame Infinity](https://ogameinfinity.com/) (the
`ogame-infinity/web-extension` OGame extension) to **OGame 0.84** — the server
this repository contains. It is a client-side port: nothing in `game/` is
touched, the extension reads the pages the game already renders.

Issue: [#130 «Port OGame Infinity to 0.84»](https://github.com/ogamespec/ogame-opensource/issues/130).

---

## What it does

The 0.84 server keeps no history and no statistics: messages are localized prose
and HTML tables, there is no API, and the message list is not even paginated.
The extension builds the database the original OGame Infinity builds for the live
game, from the pages the player opens:

| Feature (ported from OGI) | 0.84 data source |
|---|---|
| **Spy report database** with loot, maximum loot, required cargo ships and fleet/defence value | `page=bericht&bericht=<id>` popups and the reports inlined into the message list |
| **Expedition analyzer** — outcome, size and the found resources / dark matter / ships, summed per day | `MTYP_EXP` messages (`game/loca/<lang>/expedition.php` templates) |
| **Combat analyzer** — wins/losses/draws, loot, own and enemy losses, debris, net result per day | battle report links in the message list plus the report popup |
| **Recycling analyzer** — recycled metal/crystal per day | the "Intelligence" messages (`FLEET_RECYCLE`) |
| **Transport ledger** — what left and what arrived, per day | transport arrival and delivery messages (`FLEET_TRANSPORT_*`) |
| **Planet and player history** — names, owners, moons, report counts | spy reports and the galaxy page |
| **Activity tracking** — `(*)` (under 15 min) and `(n min)` markers over time | the galaxy page (`game/pages/galaxy.php`) |

The panel is rendered in the markup of the old design (`table.header`, `td.c`,
bare links), so it looks like a native 0.84 page instead of a modern overlay.

## Where the controls are

The 0.84 content pane (`#content`) is absolutely positioned with its own
scrollbar, so a panel appended to the end of a page would sit below the message
table and stay invisible. The extension therefore puts its controls at the **very
top of the content area**:

| Page | What appears | Where exactly |
|---|---|---|
| Toolbar button (any tab) | A popup with the extension version, the state of the current tab (game page, page name, universe, language, storage, panel) and the collected report counts | The browser toolbar |
| `page=messages` | The analyzer panel: actions (`Expand`/`Collapse`, `Scan message folders`, `Clear database`), a one-line summary, the tabs (Spy reports, Expeditions, Combats, Recycling, Transport, Planets & players, Totals) and the table of the active tab | The first element of `#content`, above the message table |
| `page=bericht` (report popup) | An info box with the maximum loot, the needed cargo ships and the fleet/defence value of the scanned planet | Above the report body, inside the popup window |
| `page=galaxy` | A one-line note about how many planets/moons were recorded | The first element of `#content` |

The rest of the UI lives inside the game pages; on any other page the toolbar
popup is the only surface, and it reports `Game page: no`. The panel state
(collapsed or not) is remembered per universe.

On the messages page the panel scans the six message folders on the first visit;
`Scan message folders` repeats it at any time, and `Collapse` shrinks the panel to
the title, the actions and the summary line.

## What is deliberately **not** ported

OGame Infinity targets the modern game. Everything that does not exist in 0.84 is
left out — porting it would mean inventing content the server cannot serve:

- **Life Forms** (discovery messages, artefacts, lifeform tech) — no such concept in 0.84;
- **player classes** (Collector / General / Explorer), **officers-gated** empire views;
- **items / inventory**, the **dark matter shop**;
- **modern ships** (Pathfinder, Reaper, Crawler) and the modern probe cargo rules;
- third-party services of the original extension (**PTRE**, external simulators,
  the `mmorpg-stat.eu` links) — they have no 0.84 endpoint;
- client-side QoL that depends on the modern message API (`data-raw-*`
  attributes, `#messagewrapper`, browser notifications).

## Installation

The extension is a plain unpacked Manifest V3 extension, no build step:

1. **Chrome / Edge / Brave**: `chrome://extensions` → *Developer mode* → *Load
   unpacked* → select this folder (`ogi/`).
2. **Firefox**: `about:debugging#/runtime/this-firefox` → *Load Temporary
   Add-on* → select `ogi/manifest.json`.

Then open the game, go to **Messages** and use the panel at the top of the page
(*Scan message folders*). The data stays in the browser (`localStorage`, one
database per game host and universe).

The toolbar button of the extension opens a small popup with the version, the
state of the current tab (game page or not, page name, universe, language,
storage, panel) and the number of collected reports. It works even when the page
itself is not injected, so it answers "is the extension running at all?" without
the console.

> The manifest declares `<all_urls>` so the extension works on any 0.84 server;
> the content script does nothing unless the page looks like a 0.84 game page.
> If you prefer, narrow `content_scripts.matches` in `manifest.json` to your own
> server before loading it.

### Which servers it works on

Any server running this codebase (0.84), in any of its languages:

- the game page is recognized by the engine's own markers, **not by the page
  title** — `PAGE_TITLE` is localized (`Uni1 OGame`, `Вселенная 1 ОГейм`,
  `ユニバース 1 OGame`, `Univers 1`, `OGame Universo 1`), and the report popup has
  no left menu at all: the universe is taken from the left menu when present and
  remembered per host for the popup (see `src/lib/detect.js`);
- all seven languages of the server (de, en, es, fr, it, jp, ru) are supported by
  the parsers, because the strings come from the server's own `game/loca`;
- the game may be served inside a frame (`all_frames`), and the report popup is a
  separate window;
- the message folders are read with same-origin requests carrying the session
  cookie. If a content security policy blocks them, the extension still reads the
  page that is already open — the inlined spy reports and the messages of the
  visible folder are collected anyway;
- messages added by modifications are simply not classified: the extension only
  knows the message kinds of the base engine.

If a server runs an **older or newer revision** of the sources, regenerate the
data files from that revision (`node ogi/tools/build-data.js`); otherwise some
report strings may not match and the affected reports are skipped.

### Known console messages that are not the extension's

`[Violation] Permissions policy violation: unload is not allowed in this document.`

This comes from the engine itself: every framed 0.84 page sets an `unload`
handler in its body tag (`game/core/page.php`: `<body … onunload='' >`, the same
in `game/pages/phalanx.php` and the GalaxyTool). Modern Chrome reports the
deprecated `unload` permission as a policy violation. The extension registers no
`unload`/`beforeunload` handlers at all, and the message appears with the
extension disabled as well.

If a message names a different policy (`storage-access`, for example), it means
the browser denied the extension's `localStorage`: the store then probes the
storage and falls back to an in-memory database, so the panel keeps working but
the collected reports are not persisted between pages. That happens in sandboxed
frames and with "block all cookies" enabled.

### Troubleshooting: an empty console

Every page logs exactly one line, so "nothing in the console" is never ambiguous:

```
[OGI 1.0.0] https://oldogame.ru/…/index.php?page=messages&session=… · page=messages · game=yes · universe=uni1 · lang=ru · storage=ok · handler=messages
```

- `game=no` (printed with `console.debug`, so switch the DevTools log level to
  *Verbose* to see it) — the page is not a 0.84 game page, nothing is done;
- `game=yes` and `handler=none` — the extension works, but this page has no UI
  (only `messages`, `bericht` and `galaxy` have one);
- `handler=messages` — the panel is in the document; if it is not visible, the
  panel is collapsed, or the page was served from the cache — reload it;
- `storage=memory` — the browser denied `localStorage`; the panel works but does
  not remember anything between pages;
- **no `[OGI]` line at all** — the content script was never injected:
  - reload the extension in `chrome://extensions` / `about:debugging` after any
    change to its files (unpacked extensions do not reload themselves);
  - *Details → Site access* must not be limited to "on click" for the game host;
  - a page opened as `file://…` requires the "Allow access to file URLs" toggle —
    for a local server use `http://localhost/…` instead;
  - the game must be open in this browser profile.

The quickest check is the toolbar popup: it shows the version and, in the
*Content script* row, either the state of the page or `not running`. A stale
build is recognizable by the version number, and in the extension's console
context by `typeof OGI.looksLikeGamePage` — a loaded extension older than the
current sources has `OGI` defined but no such function (and no `OGI.lastBoot`), so
reload the extension and press F5 on the page.

The definitive check is the extension's own console context: in DevTools open the
context dropdown next to the console prompt and pick **OGame Infinity 0.84**, then
type `OGI.lastBoot`. It shows what the script decided for the current page:

```js
OGI.lastBoot
// {version: "1.0.0", href: "…", page: "messages", game: true, universe: "uni1",
//  lang: "ru", storage: true, handler: "messages"}
```

If `OGI` is undefined in that context, the script did not run at all.

## How it works

```
ogi/
├── manifest.json              # MV3, classic content scripts (no bundler)
├── src/
│   ├── data/loca.js           # GENERATED: all game strings (7 languages)
│   ├── data/units.js          # GENERATED: costs, cargo, object lists
│   ├── lib/
│   │   ├── numbers.js         # nicenum parsing/formatting, port of the server plunder algorithm
│   │   ├── html.js            # tolerant HTML scanning (0.84 markup is broken by design)
│   │   ├── templates.js       # localization strings -> regular expressions
│   │   ├── store.js           # per-universe database (localStorage)
│   │   ├── collect.js         # game page -> database records
│   │   ├── panel.js           # analyzer panel (pure HTML strings)
│   │   ├── ui.js              # 0.84 style tables, tabs, escaping
│   │   └── i18n.js            # panel strings (en / ru / de)
│   ├── parsers/               # messages, spy report, battle report, text messages, galaxy
│   ├── analyze/index.js       # loot, needed cargos, daily sums, activity history
│   ├── pages/                 # messages / bericht / galaxy handlers
│   └── content.js             # entry point
├── tools/build-data.js        # regenerates src/data/*.js from the game sources
└── tests/                     # node --test (34 tests)
```

### Parsing instead of an API

The 0.84 pages carry **no `data-*` attributes at all**, no JSON and no element
ids for messages. The extension therefore reconstructs the data the way the
server built the page:

- **localization templates** — the generated `src/data/loca.js` holds the very
  strings the server used (`SPY_M` = `"Metal:"`, `EXP_FOUND` = `"<br>You got #1
  units of #2."`, `FLEET_RECYCLE`, …). `lib/templates.js` turns them back into
  regular expressions, exactly like OGI's pre-API parsers did;
- **object names** — spy reports print localized names ("Small Cargo"), so the
  extension maps them back to the game ids (202, 203, …) with the same locale
  data (`NAME_<gid>` / `SNAME_<gid>`);
- **anchors that are language independent** — `showGalaxy(g,s,p)` calls for
  coordinates, `name="delmes<id>"` for message ids, `combatreport_*_iwon` classes
  for battle outcomes, `(V:x,A:y)` for both sides' losses, `bericht=<id>` for the
  report popup;
- **folder type** — the message list itself does not say what a message is; the
  extension reads the six folders (`pm=0…5`) through same-origin requests, so the
  type of every row is known.

The markup is famously broken (rows without `<tr>`, an apostrophe inside a
single-quoted `onmouseover`, tags that never close). `lib/html.js` therefore
contains a quote-aware tag scanner that recovers the way a browser does instead of
regular expressions that silently stop early.

### Formulas taken from the server

`lib/numbers.js` ports `Plunder()` from `game/core/battle.php`: at most half of
every resource can be captured, the cargo is filled metal → crystal → deuterium
and the remainder is redistributed. The spy table shows the real maximum loot and
the cargo ships needed for it (probe 5, small cargo 5000, large cargo 25000,
recycler 20000 — `$UnitParam` of `game/core/techs.php`).

## Regenerating the game data

`src/data/loca.js` and `src/data/units.js` are generated from the game sources
(locales, constants and object tables) — never edit them by hand:

```bash
node ogi/tools/build-data.js
```

Run this after changing `game/loca/**` or `game/core/techs.php`; the extension
supports exactly the languages the server ships (de, en, es, fr, it, jp, ru).

## Tests

```bash
# from the repository root
node --test "ogi/tests/**/*.test.js"

# or from this folder
cd ogi && node --test
```

34 tests, no dependencies (Node's built-in test runner). They check the parsers
against the project's own golden snapshots (`testing/golden/<lang>/…`) for seven
languages, the analyzers, the storage, the panel rendering (including HTML
escaping of player controlled names) and the packaging — the last one loads all
scripts in manifest order in a sandbox, which is how a browser loads them.

## Limitations

- **Message history is limited by the server**: the 0.84 message list stops after
  25 rows (50 with the Commander) and there is no pagination, so the extension can
  only see what the game still stores. Battle reports are deleted by the server
  after two weeks (`game/core/battle.php`).
- **The spy report does not contain the activity value** (only a tooltip
  explaining it); activity comes from the galaxy page.
- **Recycling messages carry no coordinates**, so a harvest entry cannot be
  attributed to a specific debris field — the recycling tab aggregates per day.
- **Loss values are resource values** as the server computes them
  (`TechPriceInPoints` = metal + crystal + deuterium), not points.
- The extension stores data per browser and per host; it is not shared between
  devices (the original OGI uploads to PTRE, which does not exist for 0.84).

## License / attribution

The gameplay data, the localization strings and the page formats belong to the
OGame 0.84 server in this repository (see the root `README.md` and `LICENSE`).
This extension is a non-commercial port of the feature set of
[OGame Infinity](https://github.com/ogame-infinity/web-extension).
