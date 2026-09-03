# Mod Developer Manual

> **Document status:** up-to-date manual on the modification subsystem
> (issue [#280](https://github.com/ogamespec/ogame-opensource/issues/280)).
> This page used to be a draft ("Mods Draft") — now it is a full manual. The
> identically named HTML manual with the Evolution skin styles lives in the
> repository's `docs/`: [mod-manual-en.html](../../docs/mod-manual-en.html).

Modifications (mods) are a way to extend and change the game **without touching
the source code of the base engine**. Everything a mod needs to know is gathered
in this manual:

- how the mod subsystem is organized and how a mod connects to the game;
- a complete hook reference (all the points where a mod can intervene in the game);
- a reference of dispatcher functions (`ModsExec*`);
- examples from real mods in the repository: `BogusMod`, `GalaxyTool`,
  `SpaceStorm`, `DeepSpaceHorror`.

Subsystem sources: `game/core/mods.php`, the mods admin page:
`game/pages_admin/admin_mods.php`.

---

## 1. Introduction

### 1.1. What is a mod

A mod is a **self-contained folder** inside `game/mods/` that contains all the
code and resources the modification needs:

```
game/mods/
├── BogusMod/          # демо-мод: новый ресурс, пункт меню, своя страница
├── GalaxyTool/        # инструмент с игровой и админ-страницей
├── SpaceStorm/        # «Космический шторм»: новая механика + новое здание
├── DeepSpaceHorror/   # «Глубокий космос»: монстры, кастомные объекты, миссии
└── Wanderer/          # «Странствующий Торговец»: режим игрока, станция, торговля
```

The mod declares a class inheriting the abstract class `GameMod` (file
`game/core/mods.php`). The core finds the mod by its folder name on its own,
loads it, and calls its methods at the right moments. The base engine does not
change — instead, calls of **hooks** are placed in it (see section 8).

The repository already contains five mods that serve as reference examples and
cover almost all capabilities of the system. Their code is the best "living"
documentation:

| Mod | What it teaches |
|---|---|
| `BogusMod` | A minimal mod: a DB column, its own resource, menu item, page, periodic event |
| `GalaxyTool` | A tool mod: its own game page and its own admin section, 6 locale languages |
| `SpaceStorm` | A new building, edits to the engine's global tables, battle/economic hooks, tests |
| `DeepSpaceHorror` | Custom galaxy objects, new units, custom fleet missions, image hooks, tests |
| `Wanderer` | A whole second game mode: per-user mode switch, custom galaxy object, own pages/economy/jumps/orders, three new engine hooks, tests |

### 1.2. How a mod gets into the game

1. The mod is placed as a folder into `game/mods/<ModName>/`. The admin page
   "finds" it by the presence of `main.php` and `manifest.json`.
2. The administrator **installs** the mod in the admin page (section *Mods*,
   `index.php?page=admin&mode=Mods`). On install:
   - the mod's name is appended to the `modlist` column of the `uni` table (a
     list of names separated by `;`, left to right — the activation order);
   - the mod's `install()` method is called (usually: it creates DB
     columns/tables and puts its own events into the queue);
3. On **every** request to the game (and in `cron.php`) the core calls
   `ModsInit()`, which for each name in `modlist` loads `main.php`, creates an
   instance of the class, and calls the mod's `init()`.
4. While running, the engine calls the hooks of the mods (see section 8). Hooks
   fire **in the activation order** of the mods; the first mod that returns
   `true` from a hook stops the chain.
5. On **removing** a mod (a button in the admin page) `uninstall()` is called —
   the mod must remove its columns/tables and events.

More on the management functions (`ModsInstall`, `ModsRemove`, `ModsMoveUp` …) —
section 12.

---

## 2. Structure of a mod folder

Required elements:

| Path | Purpose |
|---|---|
| `main.php` | The mod's main module. Defines the mod's constants and the class `<ModName> extends GameMod` |
| `manifest.json` | Metadata for the admin page (name, version, author, description, website) |

Recommended / commonly used:

| Path | Purpose |
|---|---|
| `Readme.md` | A human-readable description of the mod, instructions, changelog (see `DeepSpaceHorror/Readme.md`) |
| `img/bg.png` | Background picture (600×200) for the mod's card in the admin page |
| `img/…` | Mod pictures (icons of resources, objects, buttons) |
| `loca/<lang>_<lang>/<section>.php` | The mod's localization files (see section 6) |
| `pages/` | PHP files of the mod's game pages |
| `pages_admin/` | PHP files of the mod's admin pages |
| `testing/` | The mod's own PHPUnit test suite (see section 10) |

> IMPORTANT: folders with source code (`pages/`, `pages_admin/`, `loca/`) must
> be closed off from direct browser access — into each of them a `.htaccess`
> with the following content is placed:
>
> ```apache
> Order allow,deny
> Deny from all
> ```
>
> The `img/` folder (and other folders with static files) stays open — game
> pages refer to it. See an example in any mod of the repository.

## 3. manifest.json

The file `manifest.json` sits in the root of the mod's folder and contains its
metadata. The admin page builds the mod's card from it (function `ModsGetInfo`,
see section 12):

```json
{
  "name": "My Awesome Mod",
  "version": "1.0.0",
  "author": "YourName",
  "description": "Добавляет новые возможности для игроков",
  "website": "https://github.com/yourname/mod-name"
}
```

| Field | Purpose |
|---|---|
| `name` | Mod name (the card's title in the admin page) |
| `version` | Mod version |
| `author` | Author |
| `description` | Short description of the capabilities |
| `website` | Link to the mod's website/repository |

## 4. main.php and the GameMod class

### 4.1. Minimal skeleton

```php
<?php

class MyAwesomeMod extends GameMod {

    // Установка мода (вызывается один раз при активации в админке).
    public function install() : void {
    }

    // Удаление мода (вызывается один раз при деактивации).
    public function uninstall() : void {
    }

    // Инициализация (вызывается при каждом запросе, когда мод активен).
    public function init() : void {
    }

    // ... любые хуки из класса GameMod, которые нужны моду ...
}
```

> The class name must match the mod's folder name (the case of the first letter
> is upper): folder `MyAwesomeMod` ⇒ class `MyAwesomeMod`. That is exactly how
> `ModInitOne()` looks it up.

### 4.2. The three required lifecycle methods

| Method | When it is called | What it usually does |
|---|---|---|
| `install()` | once, on mod activation (admin page) | `ALTER TABLE` — adds its own columns/tables; creates its own events in the queue; sends an announcement to the players |
| `uninstall()` | once, on mod deactivation | removes its own columns/tables, deletes its own events from the queue, cleans up after itself |
| `init()` | on every request while the mod is active | loads the mod's localization (`loca_add`), supplements the engine's global tables (`$buildmap`, `$initial`, `$UnitParam` …), registers handlers |

`install()` and `uninstall()` run in the context of the admin page. You should
work with the DB in them just like in any other game code: through `dbquery()`
and friends (section 11), and table structure changes — under
`LockTables()`/`UnlockTables()`, as in the examples below.

**Example `install()` / `uninstall()` from `BogusMod`** — adds the `tritium`
column to the `users` table and creates a periodic event:

```php
public function install() : void {
    global $db_prefix;

    LockTables();

    // Колонка для запасов трития
    $query = "ALTER TABLE ".$db_prefix."users ADD COLUMN tritium INT DEFAULT 0;";
    dbquery ($query);

    // Событие «начислить тритий» — раз в час
    $query = "SELECT * FROM ".$db_prefix."queue WHERE type = '".QTYP_ADD_TRITIUM."'";
    $result = dbquery ($query);
    if ( dbrows ($result) == 0 ) {
        AddQueue (USER_SPACE, QTYP_ADD_TRITIUM, 0, 0, 0, time(), BOGUS_MOD_TRITIUM_CREDIT_PERIOD_SECONDS);
    }

    UnlockTables();
    Debug ("BogusMod install success.");
}

public function uninstall() : void {
    global $db_prefix;

    LockTables();

    $query = "ALTER TABLE ".$db_prefix."users DROP COLUMN tritium;";
    dbquery ($query);

    $query = "DELETE FROM ".$db_prefix."queue WHERE type = '".QTYP_ADD_TRITIUM."'";
    dbquery ($query);

    UnlockTables();
    Debug ("BogusMod uninstall success.");
}
```

Rules worth following:

- schema-changing operations must be idempotent and repeatable (the admin can
  reinstall the mod);
- on activation check whether your event is already in `queue` (the example
  above), otherwise duplicates will appear on a repeated install;
- **don't delete other people's data**: `uninstall()` must remove only what the
  mod itself created;
- after mod deactivation the core "heals" the list: if a name in `modlist` has
  no `manifest.json`, the mod is removed from the list automatically (see
  `admin_mods.php`).

### 4.3. init() and the engine's global tables

The mod has access to all global variables and functions of the core
(`$GlobalUser`, `$GlobalUni`, `$db_prefix`, ...). The most important thing: in
`init()` the mod can **supplement the global tables** the game is built on.
These tables are declared in `game/core/techs.php` and `game/core/prod.php`:

| Global variable | What it holds | How the mod changes it |
|---|---|---|
| `$buildmap` | list of building IDs | `$buildmap[] = GID;` — add a building |
| `$resmap` | list of research IDs | add a research |
| `$fleetmap` | list of ship IDs | add a ship |
| `$defmap` | list of defence IDs | add a defence |
| `$rakmap` | list of missile IDs (defence built in the missile silo) | add a missile |
| `$resourcemap` | all resource IDs | add a resource |
| `$initial` | table of initial object costs (`metal`, `crystal`, `deuterium`, `energy`, `factor`) | `$initial[GID] = [GID_RC_METAL=>.., GID_RC_CRYSTAL=>.., ..., 'factor'=>N];` |
| `$UnitParam` | unit parameters: `[0]` structure, `[1]` shield, `[2]` attack, `[3]` cargo, `[4]` speed, `[5]` consumption | `$UnitParam[GID] = [structure, shield, attack, cargo, speed, consumption];` |
| `$RapidFire` | rapid fire table: `$RapidFire[$gid][$target] = amount` | add/change values |
| `$requirements` | the requirements tree of objects | `$requirements[GID] = [GID_B_RES_LAB=>3, ...];` |
| `$CanBuildTab` | which buildings/units are visible on the build page for a galaxy object type | `$CanBuildTab[PTYP_PLANET][] = GID;` |
| `$PlanetProd` | production/consumption rules per object (`prod`/`cons` closures on a resource) | add a production rule for a new resource |
| `$prodPriority` / `$resourcesWithNonZeroDerivative` | the order and the list of "production" resources | extend if a production resource is added |

Example from `SpaceStorm` (`init()`) — adding a **new building** to the game:

```php
public function init() : void {
    global $buildmap, $initial, $requirements, $CanBuildTab;

    // Добавить новое здание «Стабилизатор реальности»
    $buildmap[] = GID_B_REALITY_STAB;                                  // 57384
    $initial[GID_B_REALITY_STAB] = array (
        GID_RC_METAL    => 50000,
        GID_RC_CRYSTAL  => 125000,
        GID_RC_DEUTERIUM=> 50000,
        GID_RC_ENERGY   => 0,
        'factor'        => 3,
    );
    $requirements[GID_B_REALITY_STAB] = array (GID_B_RES_LAB => 3, GID_B_TERRAFORMER => 1);
    $CanBuildTab[PTYP_PLANET][] = GID_B_REALITY_STAB;

    global $GlobalUser;
    loca_add ("space_storm", $GlobalUser['lang'], __DIR__);
}
```

Example from `DeepSpaceHorror` (`init()`) — adding **new ships**:

```php
public function init() : void {
    global $fleetmap, $UnitParam, $RapidFire, $requirements;

    $fleetmap[] = GID_LEVI_AMOEBA;                     // новый юнит
    $UnitParam[GID_LEVI_AMOEBA] = array (250000000, 10000, 5000, 0, 100, 0);
    $RapidFire[GID_LEVI_AMOEBA] = array (GID_F_SC => 1000, GID_F_LF => 500, ...);
    $requirements[GID_LEVI_AMOEBA] = [];
    // ... аналогично для остальных юнитов ...
}
```

The steps of "adding a new building to the game" in full (based on
`SpaceStorm`):

1. In `install()`: `ALTER TABLE planets ADD COLUMN \`<GID>\` INT DEFAULT 0;`
   (the building's level is stored in the planet column named after the GID).
2. In `install_tabs_included()`: declare the same column in the schema
   (section 8.10), so DB checks/serialization don't complain.
3. In `init()`: `$buildmap[]`, `$initial[]`, `$requirements[]`, `$CanBuildTab[]`.
4. In `init()`: `loca_add()` and localized names `NAME_<GID>` / descriptions
   `LONG_<GID>` (section 6).
5. `get_object_image()` — return the building picture (section 8.5).
6. If needed — the hooks `can_build`, `build_end`, `page_infos`,
   `page_buildings_get_bonus`, etc.

New **resources** (like `tritium` in `BogusMod`) are added a bit differently —
they are not planet columns with production but counters (e.g., on the account)
plus an item in the resource panel via the `add_resources` hook (section 8.3).

---

## 5. Mod pages

### 5.1. The game router

The game router is the JSON file `game/router.json`. On every request
`game/index.php` loads it and lets mods add their own pages:

```php
$router = LoadJsonFirst ("router.json");
ModsExecRef ('route', $router);
```

A router entry (for a mod page) may contain the following keys:

| Key | Purpose |
|---|---|
| `path` | path to the page's PHP file relative to `game/` (required) |
| `loca` | the list of locale sections to load for the page (required) |
| `external` | `true` — guests without a session may view the page (like `ainfo`) |
| `menu` | `false` — don't draw the left menu |
| `header` | `false` — don't draw the top panel (resources and planets) |
| `bare` | `true` — without any `PageHeader`/`EndContent` framing at all |
| `mvc` | `true` — the page is a `Page` class (controller/view), not an include file |
| `admin_update_queue` | `false` — don't update the queue for admins (like `overview`) |
| `update_activity` | `false` — don't update the player's activity |
| `redirect_page`, `redirect_sec` | auto-redirect to another page after N seconds |

If `menu`/`header` are not given — for a request with a session they count as
`true`, i.e. the page gets the standard game framing (left menu + resource
panel), which is built in `PageHeader`.

### 5.2. Registering your page (hook `route`)

Example from `BogusMod` — the "Tip of the Day" page:

```php
public function route(array &$router) : bool {
    $router['tipoftheday'] = array (
        'path' => "mods/BogusMod/pages/tipoftheday.php",
        'loca' => [ "menu" ]
    );
    return false;   // не останавливаем цепочку — другие моды тоже могут добавить страницы
}
```

After that the page is available at `index.php?page=tipoftheday&session=...`,
and a menu item for it is added by the `add_menuitems` hook (section 8.2).

### 5.3. Classic (include) page file

The page file is simply included by the engine (`include $router[$pk]['path'];`).
It has access to the global variables: `$now`, `$aktplanet`, `$session`,
`$PageMessage`, `$PageError`, `$GlobalUser`, `$GlobalUni`, as well as any other
core globals. Output goes straight into the stream (echo/HTML). Example — the
whole page file of BogusMod:

```php
<?=loca("BOGUS_MOD_TIP1");?>
```

The page may freely work with the DB, send messages, etc., like an ordinary core
page.

### 5.4. MVC pages (class Page)

Modern core pages are classes inheriting the abstract `Page`
(`game/core/page.php`):

```php
abstract class Page {
    public function controller () : bool { return true; }   // логика запроса
    public function view () : void { }                      // вывод
}
```

A router entry for an MVC page is marked `"mvc": true`, and the class name is
`ucfirst($pk)` (the page name with a capital letter). A mod can make its own MVC
page exactly the same way: put the class into its own file and write the path
into the router.

### 5.5. Admin pages (hook `route_admin`)

The admin page (`index.php?page=admin&mode=<Section>`) has its own router
`game/pages_admin/admin_router.json`. The mod adds its own sections with the
hook:

```php
public function route_admin(array &$router) : bool {
    $router['MyTool'] = array (
        'path' => "mods/MyAwesomeMod/pages_admin/admin_mytool.php",
        'img'  => "mods/MyAwesomeMod/img/tool.png",   // иконка в меню админки
        'loca' => "ADM_MENU_MYTOOL",
    );
    return false;
}
```

The class in the admin page file must be named `Admin_<Mode>`, where the mode is
the key in the router (`Admin_MyTool`), and inherit `Page`. See `GalaxyTool`:
`pages_admin/admin_galaxytool.php` + `route_admin` in `main.php`.

> Note: only operators/admins have access to the admin page, and the pages of
> modes with `"panel" => false` (like `Home`) don't show the admin menu.

---

## 6. Mod localization

The core loads localization in sections via `loca_add($section, $lang, $dir)`.
A section is a file `loca/<lang>_<lang>/<section>.php` relative to `$dir` (by
default — the root of `game/`). For a mod, `__DIR__` is passed as the third
parameter, so the files live in the mod's folder:

```php
public function init() : void {
    global $GlobalUni;
    loca_add ("bogusmod", $GlobalUni['lang'], __DIR__);
}
```

…will pick up the file `game/mods/BogusMod/loca/ru_ru/bogusmod.php` (for the
language `ru`).

The format of a locale file is the same as the core's (`game/loca/ru_ru/…`):

```php
<?php

$LOCA["ru"]["BOGUS_MOD_TRITIUM"]    = "Тритий";
$LOCA["ru"]["BOGUS_MOD_MENU_ITEM"]  = "Совет дня";
$LOCA["ru"]["BOGUS_MOD_TIP1"]       = "Мойте руки перед едой.";
```

Getting a string — the functions `loca($key)` (the current language) and
`loca_lang($key, $lang)` (for a specific language, e.g., when sending messages
to players with a different language).

Conventions for keys:

- `NAME_<GID>` — the short name of a game object (building/unit/resource);
  `LONG_<GID>` — the long description. If the mod adds objects — these keys are
  used by the pages automatically.
- `MENU_*` — menu items; `ADM_MENU_*` — admin menu items; the keys of a mod's
  pages can be named freely, but it's better to use the mod prefix
  (`BOGUS_MOD_*`, `STORM_*`, `LEVI_*`) so as not to collide with the core and
  other mods.

Ready-made languages of a mod: `ru_ru`, `en_en`, and, if desired — all
languages from the `$Languages` list (`game/core/loca.php`). `GalaxyTool` is
localized into 6 languages: `de_de`, `en_en`, `es_es`, `fr_fr`, `it_it`,
`ru_ru`.

---

## 7. Mod events in the queue

### 7.1. The event queue

All time-based logic of the game is built on a common event queue (table
`queue`, module `game/core/queue.php`). An event is a row with the fields
`owner_id`, `type`, `sub_id`, `obj_id`, `level`, `start`, `end`, `prio`,
`freeze`/`frozen`.

The queue API for mods:

```php
AddQueue (int $owner_id, string $type, int $sub_id, int $obj_id, int $level,
          int $now, int $seconds, int $prio = QUEUE_PRIO_LOWEST) : int   // id задачи
RemoveQueue (int $task_id) : void
ProlongQueue (int $task_id, int $seconds) : void    // продлить задачу (для периодических)
```

Queue processing (`UpdateQueue`) is launched on every player action and from
`cron.php`. Reaching an event with an unknown `type` (no handler in the core),
the engine calls the hook:

```php
default:
    $res = ModsExecRef ('update_queue', $queue);
    if (!$res) {
        RemoveQueue ( $queue['task_id'] );
        Debug ( loca_lang("DEBUG_QUEUE_UNKNOWN", $GlobalUni['lang']) . $queue['type']);
    }
    break;
```

### 7.2. Custom mod events

The mod introduces its own `type` — a string constant — and handles it in the
`update_queue` hook. If the mod "recognized" the event, it must either delete it
(`RemoveQueue`) or prolong it (`ProlongQueue`) — then the event becomes
periodic. If the hook returned `false` for all mods — the event is deleted with
a diagnostics entry in the log.

Example from `BogusMod` — crediting tritium once an hour:

```php
public function update_queue(array &$queue) : bool {
    global $db_prefix;
    if ($queue['type'] === QTYP_ADD_TRITIUM) {          // "AddTritium"

        // Начислить тритий всем и продлить событие ещё на час
        $query = "UPDATE ".$db_prefix."users SET tritium = tritium + 1;";
        dbquery ( $query );
        ProlongQueue ($queue['task_id'], BOGUS_MOD_TRITIUM_CREDIT_PERIOD_SECONDS);
        return true;     // событие обработано
    }
    else {
        return false;    // не наше — пропустить к следующим модам
    }
}
```

Universe-wide events (not tied to a player) are created on the technical account
`USER_SPACE` (99999) — both the core and the mods do that (see `install()`
above). The priority of periodic background events can be left at
`QUEUE_PRIO_LOWEST`.

More complex examples:

- `SpaceStorm` — the hourly `"SpaceStorm"` event: generates a new storm,
  prolongs itself, applies effects on every tick (see `update_queue` in
  `SpaceStorm/main.php`);
- `DeepSpaceHorror` — delayed monster respawn: the `"DeepSpaceHorror"` event
  with a 24–72 hour timer recreates the leviathan when it was killed.

---

## 8. Hooks

### 8.1. Common mechanism

Hooks are methods of the `GameMod` class that the core calls from different
places in the game so that a mod can change the behavior of the base engine. The
declarations of all hooks with parameter descriptions are in
`game/core/mods.php` — that is the canonical source.

A hook is called **for all active mods in the activation order** until some mod
returns `true`:

- `false` — "continue": the mod either did nothing, or did something but allows
  other mods to fire too (that's how items/resources/bonuses are usually added —
  so that several mods could add them);
- `true` — "stop": the hook is handled, the other mods won't get this call
  (that's how custom queue events, permissions, etc. are usually handled).

The dispatchers (functions `ModsExec*`) differ in call signatures — the number
and types of parameters (by value / by reference / int + reference, etc.). The
full list — in the "Hook dispatchers" section below.

Reference parameters (`&$...`) are "output" data: the mod changes their contents
and the core sees the change. For example, the hook
`add_menuitems(array &$json)` receives a reference to the left menu items array —
the mod adds its own item straight into `$json`.

### 8.2. Menu, resource panel and bonuses

The game builds these elements from JSON schemas ("JSON-first"):
`leftmenu.json`, `res_panel.json` — plus data from the DB. Mods get access at
the assembly stage.

**add_menuitems — left menu items.** Called from `page.php` (`LeftMenu`) after
loading `pages/leftmenu.json`. The mod adds an item by inserting an element into
the array (conveniently — with the `array_insert_after_key` function from
`utils.php`):

```php
public function add_menuitems(array &$json) : bool {

    array_insert_after_key ($json, "options", "tipoftheday",
        array (
            'type' => 'internal',        // внутренняя страница игры
            'page' => 'tipoftheday',     // имя страницы из роутера
            'loca' => 'BOGUS_MOD_MENU_ITEM',   // ключ локали пункта
        ) );

    return false;   // другие моды тоже могут добавить пункты
}
```

Allowed item types (see `LeftMenu` in `page.php`): `img` (picture), `internal`
(page link), `internal_buggy` (special case of officers), `popup` (window),
`external` (external link). Useful keys: `param` (extra GET parameters of the
link), `accesskey` (access key), `color`, `notes`.

**add_resources — resource panel.** Called from `page.php` (`ResourceList`). The
second parameter is the active planet. The mod adds its own resource at the end
of the panel (icon, name, value, color):

```php
public function add_resources(array &$json, array $aktplanet) : bool {

    global $GlobalUser;

    array_insert_after_key ($json, (string)GID_RC_DM, (string)GID_RC_TRITIUM,
        array (
            'skin'  => false,                          // картинка НЕ из скина
            'img'   => "mods/BogusMod/img/tritium.png",// путь к иконке мода
            'loca'  => "BOGUS_MOD_TRITIUM",            // ключ локали имени
            'val'   => $GlobalUser['tritium'],         // значение (можно из планеты)
            'color' => '',
        ) );

    return false;
}
```

Resource panel entries (see `res_panel.json`) contain: `skin` (take the picture
from the skin), `img`, `loca`, `val`, `val2` (for energy —
production/consumption), `color`, optionally `href` and `title`.

**add_bonuses — the bonuses panel in the header.** Called from `page.php`
(`BonusList`). Initially the panel holds the officers; the mod adds its own
bonus icons:

```php
public function add_bonuses (array &$bonuses) : bool {
    $bonuses['storm'] = array (
        'href'     => "",                     // ссылка (или пусто)
        'img'      => "mods/SpaceStorm/img/storm_ikon.png",
        'alt'      => loca("STORM_STORM"),
        'overlib'  => "<b>".loca("STORM_STORM")."</b><br>".loca("STORM_DESC"),
    );
    return false;
}
```

Bonus entry format: `href`, `img`, `alt`, `overlib` (the tooltip text),
optionally `accesskey`.

### 8.3. Your own content on every page

**begin_content / end_content.** Called from `page.php` (`BeginContent` /
`EndContent`) — before and after the content of every framed page. The mod may
simply `echo` its HTML; the current page can be determined the usual way —
`$_GET['page']`:

```php
public function begin_content() : bool {
    if ( ($_GET['page'] ?? '') === 'overview' ) {
        echo "<div style='color:lime'>Добро пожаловать!</div>";
    }
    return false;
}
```

### 8.4. Hooks of specific pages (page_*)

The list of such hooks (all of them are described in the `GameMod` class):

| Hook | Page | What it allows |
|---|---|---|
| `page_buildings_get_bonus(int $id, array &$bonuses)` | `buildings`, `b_building` | show extra bonuses/effects of an object |
| `page_flotten1_get_bonus(array $param, array &$bonuses)` | `flotten1` | extra bonuses on the fleet's first page |
| `page_flotten2_planet_types(array &$planet_types)` | `flotten2` | change the target types (planet/moon/debris/…) |
| `page_flottenversand_ajax_spy_planets(array &$planet_types)` | `flottenversand_ajax` | the same for AJAX espionage |
| `page_galaxy_custom_object(array $planet, array &$info)` | `galaxy` | show a custom galaxy object: fill `$info['overlib']` (an HTML tooltip) and return `true` |
| `page_infos(int $id, array &$planet)` | `infos` | extra info/actions for an object |
| `page_overview_get_bonus(array $param, array &$bonuses)` | `overview` | extra bonuses on the planet overview |
| `page_resources_get_bonus(array $param, array &$bonuses)` | `resources` | extra bonuses on the "Resources" page |

Example from `SpaceStorm` — on the building info page show which storm effects
it compensates (`page_infos` simply outputs HTML), and mark the bonus on the
research page (`page_buildings_get_bonus`):

```php
public function page_buildings_get_bonus(int $id, array &$bonuses) : bool {
    $storm = $this->GetStorm();
    if ($id == GID_R_ESPIONAGE && ($storm & SPACE_STORM_MASK_CHRONO_SPY) != 0) {
        $bonuses[] = array (
            'value' => "-2",
            'color' => "red",
            'img'   => "mods/SpaceStorm/img/storm_ikon.png",
            'alt'   => loca("STORM_STORM"),
            'descr' => "<b>".loca("STORM_4")."</b><br/>".loca("STORM_DESC_4"),
            'overlib_width' => 200,
        );
    }
    return false;
}
```

### 8.5. Images of game objects

The game gets object pictures from the skin by ID. Mods that add their own
objects (buildings, units, planet objects) intercept the picture request and
return their own path — by writing it into `$img['path']` and returning `true`:

| Hook | Where it is used | When it fires |
|---|---|---|
| `get_object_image(int $id, array &$img)` | `GetObjectImage()` — a 120×120 object picture (buildings pages, info, tech tree) | for any ID |
| `get_planet_small_image(int $type, array &$img)` | `GetPlanetSmallImage()` — a small planet picture (30–50px) | by the galaxy object type |
| `get_planet_image(int $type, array &$img)` | `GetPlanetImage()` — a big planet picture | by the galaxy object type |

Example from `DeepSpaceHorror`:

```php
public function get_planet_small_image(int $type, array &$img) : bool {
    if ($type == PTYP_LEVI_AMOEBA || $type == PTYP_LEVI_GUARDIAN || $type == PTYP_LEVI_JUGGERNAUT) {
        $img['path'] = "mods/DeepSpaceHorror/img/".$this->GetLeviathanName($type).".jpg";
        return true;
    }
    return false;
}
```

Galaxy object types are described in `game/core/defs.php`: planet `PTYP_PLANET`,
moon `PTYP_MOON`, debris `PTYP_DF`, etc.; **all types >= `PTYP_CUSTOM` (20001)
are reserved for mod objects** (in `DeepSpaceHorror` the leviathans are
22848…22851).

### 8.6. Economy and production

**bonus_prod / bonus_cons.** Called from `game/core/prod.php`
(`ProdBonus`/`ConsBonus`) for every production resource on a planet
recalculation. Input: `$param` with the keys `uni`, `user`, `planet`, `rc`.
Output: a list of **multipliers** (by reference) — the base bonuses are
accumulated into an array, and the mod appends its own multiplier at the end:

```php
public function bonus_prod (array $param, array &$bonus) : bool {
    $planet = $param['planet'];
    // Стабилизатор реальности: +3% к добыче дейтерия за уровень
    if ($param['rc'] == GID_RC_DEUTERIUM && $planet[GID_B_REALITY_STAB] > 0) {
        $bonus[] = 1 + 0.03 * $planet[GID_B_REALITY_STAB];
    }
    return false;
}
```

**prod_post_process.** Called from `prod.php` (`ProdResources`) after all
balances are calculated. By reference, the planet and the economic summary
`$eco` are passed, with the keys `prod`, `prod_with_bonus`, `cons`,
`cons_with_bonus`, `net_prod`, `net_cons`, `balance` (per resource). Here the
mod can move a part of the production into another resource, freeze the energy,
etc. (example — the "Matter Signature" effect in `SpaceStorm`).

### 8.7. Buildings, researches and the queue

**can_build / can_research.** Called at the end of the `CanBuild`/`CanResearch`
checks (`game/core/queue.php`) — **after** all standard core checks. Input —
`$info` with the keys `id`, `level`, `user`, `planet` (and `destroy`/`enqueue`
for buildings). If the mod wants to **forbid** — it writes an error string into
`$info['result']` (usually a locale key) and returns `true`; to allow — it
returns `false`:

```php
public function can_build(array &$info) : bool {
    $storm = $this->GetStorm();
    if ($info['id'] == GID_B_REALITY_STAB && $storm == 0) {
        $info['result'] = loca ("STORM_REQUIRED");  // «можно строить только во время шторма»
        return true;
    }
    return false;
}
```

**build_end / research_end.** Called on completion of construction/demolition
(buildings) and research (see `Queue_Build_End`/`Queue_Research_End` in
`queue.php`). `build_end(int $planet_id, array &$queue)` receives the planet ID
and the event row (in `$queue['obj_id']` — the object, in `$queue['type']` —
`"Build"`/`"Demolish"`). Handy for granting bonuses, setting masks, starting
chains.

**update_queue — mod events.** Described in section 7.

### 8.8. Fleet, espionage and technologies

The hooks of this group are called from `game/core/fleet.php`.

| Hook | Meaning |
|---|---|
| `fleet_available_missions(array $param, array &$missions)` | change the list of available fleet missions (in `$missions` — the constants `FTYP_*`). Input `$param`: the from/to coordinates (`thisgalaxy`, `thissystem`, `thisplanet`, `thisplanettype`, `galaxy`, `system`, `planet`, `planettype`) and the fleet composition `fleet` |
| `bonus_fleet_speed(array $param, array &$bonus)` | change the ship's speed: `$bonus['value']` holds the current speed; the mod overwrites the value |
| `bonus_fleet_cons(array $param, array &$bonus)` | the same for the ship's deuterium consumption |
| `bonus_max_fleet(array $param, array &$bonus)` | the same for the player's maximum number of fleets |
| `bonus_technology(int $id, array &$bonus)` | modify the "level" of a technology. Used for espionage: `$bonus['level']` — the effective espionage level (see `SpyArrive`) |
| `spy_protection(array $args, array &$bonus)` | give the target planet protection against espionage: `$args['planet']`/`$args['target_user']`; increase `$bonus['level']` |
| `fleet_handler(array $param)` | handle a **custom fleet mission** (see below) |

Custom missions. When a fleet has a mission the core doesn't know,
`Queue_Fleet_End` hands control over to the mods:

```php
default:
    $param = [];
    $param['queue'] = $queue;        // строка события очереди
    $param['fleet_obj'] = $fleet_obj;// строка флота
    $param['fleet'] = $fleet;        // состав (по кораблям)
    $param['origin'] = $origin;      // планета отправления
    $param['target'] = $target;      // планета цели
    ModsExecArr ('fleet_handler', $param);
    break;
```

The base missions occupy the range `FTYP_ATTACK`…`FTYP_ACS_ATTACK_HEAD` (1–21);
mods add `FTYP_RETURN` (100) to their mission for the "return" flight.
Definition: in `defs.php` there is `FTYP_CUSTOM = 1000` — missions from this
value on are considered custom. Example: `DeepSpaceHorror` uses the mission
`FTYP_LEVI_PREPARE_JUMP = 22855` and handles both the arrival and the return in
`fleet_handler` (the mod dispatches the phases itself by
`fleet_obj['mission']`).

> Note: `fleet_handler` is the only dispatcher that passes an array by value
> (`ModsExecArr`), and after the call the core, as with ordinary missions,
> deletes the fleet row and the queue event. If your mission should continue
> (e.g., the fleet should return), the mod creates a new fleet and event itself
> (`DispatchFleet`/`AddQueue`). A handled mission must be "claimed" by returning
> `true`.

### 8.9. Battle

Both hooks are called from `game/core/battle.php`.

**battle_unit_stats(array $args, array &$unit_param)** — called once per battle
from the battle frontend (`GenBattleSourceData`) — before serializing the data
for the battle engine. `$args` contains `attackers` and `defenders`
(participants with technologies and composition); the second parameter is the
global unit parameters table `$UnitParam` **by reference**
(structure/shield/attack/cargo/speed/consumption). The mod can scale the
characteristics for this particular battle:

```php
public function battle_unit_stats(array $args, array &$unit_param) : bool {
    // Полярное искажение: броня -20%, щиты +30% для всех
    foreach ($unit_param as $gid => $p) {
        $unit_param[$gid][0] = (int)round($p[0] * SPACE_STORM_POLAR_ARMOR);
        $unit_param[$gid][1] = (int)round($p[1] * SPACE_STORM_POLAR_SHIELD);
    }
    return false;
}
```

> The changes apply only to the data of that particular battle: right after
> serialization the core restores the original `$UnitParam`.

**battle_post_process(array &$res)** — called after the battle from
`PostProcessBattleResult`, when the result is already enriched with the
participants' names/coordinates. `$res` contains `before`
(attackers/defenders), `rounds` (by rounds, with losses), `result`, etc., and
the mod can fill `$res['extra']` (its contents get into the report) and perform
its own actions after the battle — for example, apply the losses to custom units
(`DeepSpaceHorror`), add an "attack echo" (`SpaceStorm`).

### 8.10. The mod database

The hooks of this group are called from the DB layer (`db.php`, `db_mysql.php`,
`db_sqlite.php`).

| Hook | When | What it does |
|---|---|---|
| `install_tabs_included(array &$tabs)` | on DB creation (`CreateDBTables`), DB check/serialization in the admin page | the mod appends its columns/tables to the `$tabs` schema (see below) |
| `add_db_row(array &$row, string $tabname)` | on every row insert via `AddDBRow` | the mod can add its own fields to the row being inserted (e.g., its custom fields to a fleet row) |
| `lock_tables(array &$tabs)` | `LockTables()` before a series of queries | the mod adds its own tables to the list of locked ones |

**install_tabs_included.** The game's DB schema is described in
`game/core/install_tabs.php`: the array `$tabs['<table>']['<column>'] = '<SQL type>'`.
The mod must declare there everything it adds in `install()` — then DB creation,
integrity checks and serialization will know about the mod's columns:

```php
public function install_tabs_included (array &$tabs) : bool {
    $tabs['users']['tritium'] = 'INT DEFAULT 0';
    return false;
}
```

**Rule:** the columns added in `install()` via `ALTER TABLE` and the columns in
`install_tabs_included()` must match in name and type.

**Direct DB work.** Mods have access to the same functions as the core:
`dbquery()`, `dbrows()`, `dbarray()`, `dbfree()`; `AddDBRow($row, $tabname)` —
row insert with automatic ID substitution (it is exactly this one that triggers
the `add_db_row` hook); locks — `LockTables()`/`UnlockTables()`. The table
prefix is the global `$db_prefix` (always add it to table names!). The game
supports two DB backends — MySQL (`db_mysql.php`) and SQLite for tests
(`db_sqlite.php`); the API is the same.

---

## 9. Extended scenarios

### 9.1. Custom galaxy objects (based on DeepSpaceHorror)

"Leviathans" are planet objects of special types that belong to the technical
account `USER_SPACE` and appear at ordinary galaxy coordinates. Such objects are
ordinary rows of the `planets` table with `type >= PTYP_CUSTOM`; the galaxy
shows them in a separate column (see `EnumCustomPlanetsGalaxy` and
`ShowCustomObjects` in the core). The mod:

1. Introduces its own type constants (>= `PTYP_CUSTOM`):
   ```php
   const PTYP_LEVI_AMOEBA = 22849;   // тип объекта-монстра
   const PTYP_LEVI_PORTAL = 22848;   // точка выхода
   const GID_LEVI_AMOEBA = 22852;    // «корабль»-монстр (колонка во fleet)
   const FTYP_LEVI_PREPARE_JUMP = 22855;  // кастомная миссия
   const QTYP_LEVI_RESPAWN = "DeepSpaceHorror";  // кастомный тип события
   ```
2. In `install()`: adds columns to `fleet`/`fleetlogs` for its own units;
   calls `init()`; creates the objects (`CreateLeviathan`) — planets of type
   `PTYP_LEVI_*` and a `USER_SPACE` fleet with the mission
   `FTYP_LEVI_PREPARE_JUMP`.
3. In `init()`: `$fleetmap[]`, `$UnitParam[]`, `$RapidFire[]`,
   `$requirements[]` (section 4.3); `loca_add`.
4. The image hooks `get_planet_*_image`/`get_object_image` — pictures of the
   monsters.
5. `page_galaxy_custom_object` — show the monster in the galaxy (overlib), and
   `page_flotten2_planet_types` / `page_flottenversand_ajax_spy_planets` —
   allow sending a fleet/espionage at it.
6. `fleet_handler` — handle a player's arrival at the monster: start the battle
   (`GenBattleSourceData`/`ExecuteBattle`), distribute the loot, apply the
   losses; handle the return.
7. `update_queue` — the event `QTYP_LEVI_RESPAWN` respawns the killed monster
   after 24–72 hours.
8. In `uninstall()` — delete its own objects, fleets, events and columns.

### 9.2. A tool mod with a game and an admin page (based on GalaxyTool)

`GalaxyTool` — a "galaxy tool": shows statistics on the galaxy. Interesting
bits:

- its own player page (`pages/galaxytool.php`), registered by the `route` hook
  with full HTML and menu;
- its own admin section `Admin_GalaxyTool` (page `pages_admin/`), added by the
  `route_admin` hook with a menu icon;
- the column `galaxytool_update` in the `uni` table and the weekly
  `"GalaxyTool"` event, which rebuilds the galaxy snapshots;
- localization into 6 languages.

This is a good pattern if the mod is a standalone tool rather than a "mechanics
edit".

### 9.3. Summary: how to add to the game

| I want to add | What I do |
|---|---|
| A counter resource | a DB column (`users`…), `add_resources` for the panel, an event for crediting |
| A building | a column in `planets` + `install_tabs_included`, `$buildmap`/`$initial`/`$requirements`/`$CanBuildTab` in `init()`, `NAME_`/`LONG_` locales, `get_object_image`, page hooks |
| A ship/defence | columns in `fleet` (+`fleetlogs` if needed), `$fleetmap`/`$UnitParam`/`$RapidFire`/`$requirements` |
| A galaxy object | a type >= `PTYP_CUSTOM`, a `USER_SPACE` planet, `get_planet_*_image`, `page_galaxy_custom_object` |
| A player page | a file in `pages/`, an entry in `route`, a menu item in `add_menuitems`, locales |
| An admin section | a `Admin_<Mode>` class file, an entry in `route_admin` |
| A periodic action | an event via `AddQueue` (on install) + `update_queue` + `ProlongQueue` |
| A mechanics edit | the needed hook from section 8 |

---

## 10. Testing mods

Mods can (and should) carry their own PHPUnit suite in the `testing/` folder —
next to the mod's code, without polluting the repository's common suite. That's
how it's done in `SpaceStorm` and `DeepSpaceHorror`.

Structure:

```
game/mods/<Name>/testing/
├── phpunit.xml          # конфигурация набора
├── bootstrap.php        # подключение ядра и мода
├── <Name>Test.php       # «чистые» тесты логики (без БД)
└── <Name>DbTest.php     # тесты с in-memory SQLite
```

Key points of `bootstrap.php` (see `SpaceStorm/testing/bootstrap.php`):

- `vendor/autoload.php` is included, then the game core with **in-memory
  SQLite** (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) — MySQL is not
  needed;
- the working directory is changed to `game/` (`chdir`), because the core and
  pages resolve relative includes;
- `game/core/core.php` is included (it declares `GameMod` and the dispatchers),
  then the mod's `main.php`.

Running the suite:

```bash
vendor/bin/phpunit -c game/mods/SpaceStorm/testing/phpunit.xml
```

Tests that work with the DB build a minimal universe through real functions
(`CreateDBTables()`, `AddDBRow()`, ...) — without schema mocks. Some mods move
the "randomness" into protected methods (e.g., `Rnd()` in `DeepSpaceHorror`)
and override it in tests for reproducibility.

---

## 11. Publication and maintenance

- **Readme.md inside the mod** — mandatory for a decent mod: what the mod does,
  how to install it, how it works, which hooks it uses. A pattern —
  `DeepSpaceHorror/Readme.md` (rewritten in issue #279 as a readable mod
  document: description, rules, hooks table, test run command).
- **Versions.** The version comes from `manifest.json`; when changing the DB
  schema, think through a migration for already-installed universes (a repeated
  `install()` or a separate script).
- **Deactivation.** The admin uninstalls the mod in the admin page;
  `uninstall()` must leave the game in its original state (delete its own
  columns, events, objects).
- **Compatibility.** A mod runs on the engine base 0.84; the core version —
  `$CoreVersion` (`game/core/core.php`) — for compatibility checks.

---

## 12. The mods admin page and management functions

### 12.1. The admin page

The *Mods* admin section (`index.php?page=admin&mode=Mods`,
`game/pages_admin/admin_mods.php`):

![admin_mods](/wiki/imgstore/admin_mods.png)

- On the left — installed mods (in the activation order), on the right —
  available ones (sitting in `game/mods/`). A mod's card is the mod's `img/bg.png`
  background picture, name, description, version, author, website.
- An installed mod can be moved up/down (the activation order of hooks!) or
  removed; an available one — installed.
- If `manifest.json` disappeared for a name from the `modlist`, the core removes
  it from the list by itself.

### 12.2. Management functions (game/core/mods.php)

| Function | Purpose |
|---|---|
| `ModsInit()` | initialize all mods from `modlist` (called by the core on every request and in cron) |
| `ModInitOne($modname)` | load one mod by name (include `main.php`, `new <Name>`, `init()`) |
| `ModInstallOne($modname)` | load the mod and call `install()` (without adding it to `modlist`) |
| `ModsInstall($modname)` | add the mod to `modlist` (uni) and install it |
| `ModsRemove($modname)` | remove from `modlist` and call `uninstall()` |
| `ModsMoveUp($modname)` / `ModsMoveDown($modname)` | change the activation order |
| `ModsList()` | return `['available' => [...], 'installed' => [...]]` |
| `ModsGetInfo($modname)` | metadata from `manifest.json` (+`folder`, `bg_image`) or `null` |

### 12.3. Hook dispatchers

All dispatchers walk `$modlist` in the activation order and stop at the first
`true`:

| Function | Hook parameters |
|---|---|
| `ModsExec($method)` | no parameters |
| `ModsExecArr($method, $arr)` | array by value |
| `ModsExecRef($method, &$arr)` | array by reference |
| `ModsExecRefArr($method, &$arr, $arr2)` | reference + value |
| `ModsExecArrRef($method, $arr, &$arr2)` | value + reference |
| `ModsExecRefRef($method, &$arr, &$arr2)` | two references |
| `ModsExecIntRef($method, $val, &$arr)` | int + reference |
| `ModsExecRefStr($method, &$arr, $str)` | reference + string |

---

## 13. Appendix: summary table of hooks

The full list of hook methods of the `GameMod` class (see `game/core/mods.php`),
their call sites in the core and their meaning:

| Hook | Call in the core | Meaning |
|---|---|---|
| `route` | `index.php` | add pages to the game router |
| `route_admin` | `pages_admin/admin.php` | add sections to the admin router |
| `update_queue` | `queue.php` (`UpdateQueue`, default) | handle a custom queue event |
| `add_resources` | `page.php` (`ResourceList`) | add a resource to the resource panel |
| `add_menuitems` | `page.php` (`LeftMenu`) | add items to the left menu |
| `add_bonuses` | `page.php` (`BonusList`) | add bonuses to the header |
| `lock_tables` | `db_mysql.php`/`db_sqlite.php` (`LockTables`) | add tables to the lock |
| `install_tabs_included` | `db.php`/`db_mysql.php`/`db_sqlite.php`/`admin_db.php` | extend the DB schema |
| `get_planet_small_image` | `page.php` (`GetPlanetSmallImage`) | small planet picture |
| `get_planet_image` | `page.php` (`GetPlanetImage`) | planet picture |
| `get_object_image` | `page.php` (`GetObjectImage`) | 120×120 object picture |
| `begin_content` | `page.php` (`BeginContent`) | HTML before the page content |
| `end_content` | `page.php` (`EndContent`) | HTML after the page content |
| `add_db_row` | `db_mysql.php`/`db_sqlite.php` (`AddDBRow`) | add fields to the inserted row |
| `can_build` | `queue.php` (`CanBuild`) | forbid/allow construction |
| `can_research` | `queue.php` (`CanResearch`) | forbid/allow research |
| `build_end` | `queue.php` (`Queue_Build_End`) | completion of construction/demolition |
| `research_end` | `queue.php` (`Queue_Research_End`) | completion of research |
| `fleet_available_missions` | `fleet.php` (`FleetAvailableMissions`) | list of available missions |
| `fleet_handler` | `fleet.php` (`Queue_Fleet_End`, default) | custom fleet mission |
| `prod_post_process` | `prod.php` (`ProdResources`) | production post-processing |
| `battle_post_process` | `battle.php` (`PostProcessBattleResult`) | battle post-processing |
| `battle_unit_stats` | `battle.php` (`GenBattleSourceData`) | unit parameters for a particular battle |
| `page_buildings_get_bonus` | `buildings.php`, `b_building.php` | object bonuses on the buildings pages |
| `page_flotten1_get_bonus` | `flotten1.php` | bonuses of the fleet's first page |
| `page_flotten2_planet_types` | `flotten2.php` | fleet target types |
| `page_flottenversand_ajax_spy_planets` | `flottenversand_ajax.php` | target types in AJAX espionage |
| `page_infos` | `infos.php` | extra info/actions of an object |
| `page_galaxy_custom_object` | `galaxy.php` | custom galaxy object |
| `page_overview_get_bonus` | `overview.php` | bonuses of the planet overview |
| `page_resources_get_bonus` | `resources.php` | bonuses of the "Resources" page |
| `bonus_technology` | `fleet.php` (`SpyArrive`), `event_list.php` | modify a technology level |
| `spy_protection` | `fleet.php` (`SpyArrive`) | spy protection of the target planet |
| `bonus_prod` | `prod.php` (`ProdBonus`) | production multiplier |
| `bonus_cons` | `prod.php` (`ConsBonus`) | consumption multiplier |
| `bonus_max_fleet` | `fleet.php` (`GetMaxFleet`) | maximum fleets |
| `bonus_fleet_cons` | `fleet.php` (`FleetCons`) | ship fuel consumption |
| `bonus_fleet_speed` | `fleet.php` (`FleetSpeed`) | ship speed |
| `skip_planet_update` | `prod.php` (`GetUpdatePlanet`) | freeze a planet: credit no production (empire conservation, the Wanderer game mode) |
| `page_veto` | `index.php` (before the page is rendered) | take over a page request entirely (blocking classic sections in another game mode) |
| `fleet_dispatch_veto` | `fleet.php` (`DispatchFleet`, outbound missions) | forbid dispatching a new fleet (protection of mod objects — the station, wanderer planets) |

> The `skip_planet_update`, `page_veto` and `fleet_dispatch_veto` hooks were
> added for the `Wanderer` (Rogue Trader) mod; they fire through the standard
> `ModsExecRef`/`ModsExecArr` dispatchers and change nothing when the mod is
> not installed.

## Links

- Sources of the mod subsystem: [`game/core/mods.php`](../../game/core/mods.php)
- Hooks and their declarations: the class `GameMod` in `game/core/mods.php`
- Reference mods: `game/mods/{BogusMod,GalaxyTool,SpaceStorm,DeepSpaceHorror}`
- HTML manual with the Evolution skin: [`docs/mod-manual-en.html`](../../docs/mod-manual-en.html)
- Russian version of this page: [`ru/mods.md`](../ru/mods.md)
