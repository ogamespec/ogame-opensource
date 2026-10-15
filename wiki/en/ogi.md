# Porting OGame Infinity to 0.84

> Issue: [#130 «Port OGame Infinity to 0.84»](https://github.com/ogamespec/ogame-opensource/issues/130).
> The implementation lives in [`ogi/`](../../ogi/README.md) — a browser extension,
> not a modification of the engine.

## Overview

[OGame Infinity](https://ogameinfinity.com/) (OGI) is a browser extension for the
modern OGame: it reads the game's message pages and builds a database of spy
reports, expedition results, combats, recycling operations and player activity,
then draws analyzer panels over the game.

Porting it to 0.84 means porting it to a game that has **no API at all**: no
`data-raw-*` attributes, no JSON, no element ids, no paginated message list. The
0.84 pages only contain localized prose and 2003-era HTML — which is exactly what
OGI's own pre-API code paths were written for. The port therefore keeps OGI's
approach (templates + keyword classification) and drops everything that belongs to
the modern game.

## What was ported

| OGI feature | 0.84 implementation |
|---|---|
| Spy report table (loot, ratio, needed cargo) | `parsers/spy.js`, `analyze/index.js` |
| Spy report database and target history | `lib/store.js`, `lib/collect.js` |
| Expedition analyzer (12 outcome families, size, findings) | `parsers/texts.js` |
| Fight analyzer (loot, losses, debris, net) | `parsers/battle.js` |
| Harvest (recycling) analyzer | `parsers/texts.js` |
| Trade / transport ledger | `parsers/texts.js` |
| Player / planet history ("stalk") | `parsers/galaxy.js`, `lib/store.js` |
| Activity tracking | `parsers/galaxy.js` |

## What was not ported (and why)

0.84 has no Life Forms, no player classes, no officers, no items, no dark matter
shop and none of the modern ships, so the corresponding OGI features have nothing
to read:

- Life Forms: discovery messages, artefacts, lifeform research;
- player classes (Collector / General / Explorer) and officer-gated empire views;
- items/inventory, the shop, dark matter offers;
- modern ships (Pathfinder, Reaper, Crawler) and the modern probe cargo rules;
- OGI's third-party integrations (PTRE activity heat-map, external simulators,
  `mmorpg-stat.eu` links) — those services have no 0.84 endpoint;
- purely client-side conveniences bound to the modern markup (browser
  notifications, `#messagewrapper` filters, timezone rewriting).

## Architecture

```
game pages ──► parsers/ ──► lib/collect.js ──► lib/store.js ──► analyze/ ──► lib/panel.js
 (HTML/prose)   (templates)    (dedup, merge)     (localStorage)   (sums)      (0.84 markup)
```

- **`tools/build-data.js`** extracts the data the parsers need from the game
  itself: `src/data/loca.js` (all strings of `game/loca/<lang>_<lang>/`, 7
  languages, with PHP constants such as `GID_RC_METAL` resolved) and
  `src/data/units.js` (costs and parameters from `game/core/techs.php`). No game
  string or cost is hard-coded in the extension.
- **`lib/templates.js`** turns a localization string into a regular expression
  (`"Resources on #1"` → `Resources on (…)`), which is how the extension reads
  amounts back out of a rendered report.
- **`lib/html.js`** is a tolerant scanner, because the 0.84 markup breaks its own
  rules: message bodies contain nested tables, the galaxy tooltips contain whole
  HTML documents inside attributes, and a stray apostrophe unbalances quoting (see
  the activity tooltip of a spy report). The scanner tracks quotes and recovers
  like a browser instead of a regex that stops at the first `</tr>`.
- **`lib/store.js`** keeps one database per host and universe in `localStorage`,
  with deduplication by message id and size caps.
- **`lib/panel.js`** renders the panel as plain HTML strings in the old design
  (`table.header`, `td.c`), so the analyzer looks like a native page.

Details, screenshots of the workflow and the exact formulas are in the
[extension README](../../ogi/README.md).

## Compatibility with live servers

The extension targets any server running this engine (0.84), in any of its
languages:

- the game page is recognized by the engine's own markers, **not by the title**:
  `PAGE_TITLE` is localized (`Uni1 OGame`, `Вселенная 1 ОГейм`, `ユニバース 1 OGame`,
  `Univers 1`, `OGame Universo 1`) and the report popup has no left menu — the
  universe comes from the menu when it is there and is remembered per host for the
  popup (`src/lib/detect.js`);
- all seven server languages (de, en, es, fr, it, jp, ru) are supported, because the
  parser strings come from the server's own `game/loca`;
- the game may be served inside a frame (`all_frames` is enabled) and the report
  opens in its own window;
- the message folders are read with same-origin requests carrying the session
  cookie. If a content security policy blocks them, the extension still parses the
  page that is already open — the inlined spy reports and the messages of the
  visible folder are collected anyway;
- messages added by modifications are simply not classified: only the base engine's
  message kinds are known.

On a server running a different revision of the sources, regenerate the data files
from that revision (`node ogi/tools/build-data.js`), otherwise some report strings
will not match and those reports are skipped.

## Where the controls appear

The 0.84 content pane (`#content`) is absolutely positioned with its own
scrollbar, so a panel appended to the end of a page would sit below the message
table and stay invisible. The extension therefore puts its controls at the **very
top of the content area**:

| Page | What appears | Where exactly |
|---|---|---|
| `page=messages` | The analyzer panel: actions (`Expand`/`Collapse`, `Scan message folders`, `Clear database`), a summary line, the tabs and the table of the active tab | The first element of `#content`, above the message table |
| `page=bericht` (report popup) | The maximum loot, the needed cargo ships and the fleet/defence value of the scanned planet | Above the report body |
| `page=galaxy` | A note about how many planets and moons were recorded | The first element of `#content` |

There is no toolbar button (the manifest declares no `action`): the whole UI lives
inside the game pages, and the collapsed/expanded state is remembered per universe.

## Testing

```bash
node --test "ogi/tests/**/*.test.js"     # from the repository root
```

The suite uses the repository's own golden snapshots
(`testing/golden/<lang>/*.html`, see [GoldenPagesTest](GoldenPagesTest.md)) as
fixtures, so the parsers are verified against the real rendered pages of seven
languages, and a packaging test loads every script in manifest order inside a
sandbox — the way a browser does.

## Links

- Extension: [`ogi/README.md`](../../ogi/README.md)
- Golden page tests: [`GoldenPagesTest.md`](GoldenPagesTest.md)
- Data generator: [`ogi/tools/build-data.js`](../../ogi/tools/build-data.js)
- Russian version of this page: [`ru/ogi.md`](../ru/ogi.md)
