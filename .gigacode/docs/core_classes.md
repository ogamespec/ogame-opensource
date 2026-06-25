# OGame Core Classes Documentation

## Overview
This document describes the core classes and modules that form the OGame engine foundation.

## Core Module Architecture

### Module Loading Order
The `core.php` file loads modules in the following order:

1. **defs.php** - Game definitions and constants
2. **db.php** - Database layer
3. **utils.php** - Utility functions
4. **techs.php** - Technology and object definitions
5. **loca.php** - Localization system
6. **bbcode.php** - BBCode parser
7. **uni.php** - Universe management
8. **prod.php** - Production calculations
9. **planet.php** - Planet management
10. **user.php** - User management
11. **msg.php** - Message system
12. **notes.php** - Notes management
13. **queue.php** - Event queue
14. **page.php** - Page rendering
15. **ally.php** - Alliance system
16. **allyapps.php** - Alliance applications
17. **allyranks.php** - Alliance ranks
18. **buddy.php** - Buddy system
19. **fleet.php** - Fleet management
20. **acs.php** - Alliance Combat System
21. **expedition.php** - Expedition system
22. **battle.php** - Battle controller
23. **battle_report.php** - Battle report generation
24. **expedition_battle.php** - Expedition battles
25. **battle_engine.php** - Battle engine interface
26. **raketen.php** - Missile system
27. **graviton.php** - Graviton technology
28. **debug.php** - Debug utilities
29. **bot.php** - AI bot system
30. **botapi.php** - Bot API
31. **coupon.php** - Coupon system
32. **mods.php** - Modification system

## Core Classes

### Page Class
**File**: `game/core/page.php`

#### Class Hierarchy
```
GameMod (abstract)
    |
    +-- Page (abstract)
            |
            +-- Admin_* classes (29 classes)
            +-- * classes (49 page classes)
```

#### Abstract Methods
- `PageHeader()` - Generate HTML header
- `PageFooter()` - Generate HTML footer
- `Load()` - Load page content
- `Show()` - Display page

#### Key Methods
- `GetPlanetImage()` - Get planet image by type
- `GetPlanetSmallImage()` - Get small planet image
- `UserSkin()` - Get user skin path
- `PageHeader()` - Generate HTML header with resources
- `LeftMenu()` - Generate left navigation menu
- `ResourceList()` - Display resource bar
- `PlanetsDropList()` - Generate planet dropdown

### GameMod Abstract Class
**File**: `game/core/mods.php`

#### Abstract Methods
- `install()` - Install the mod
- `uninstall()` - Uninstall the mod
- `init()` - Initialize the mod

#### Hook Methods
- `route(array &$router)` - Hook for custom routing
- `route_admin(array &$router)` - Hook for admin routing
- `update_queue(array &$queue)` - Hook for queue updates
- `add_resources(array &$json, array $aktplanet)` - Add custom resources
- `add_bonuses(array &$bonuses)` - Add custom bonuses
- `add_menuitems(array &$json)` - Add menu items
- `lock_tables(array &$tabs)` - Lock custom tables
- `install_tabs_included(array &$tabs)` - Install custom tables
- `get_planet_small_image(int $type, array &$img)` - Get custom planet image (small)
- `get_planet_image(int $type, array &$img)` - Get custom planet image (big)
- `get_object_image(int $id, array &$img)` - Get custom object image
- `begin_content()` - Hook at content start
- `end_content()` - Hook at content end
- `add_db_row(array &$row, string $tabname)` - Add custom DB fields
- `can_build(array &$info)` - Check build permissions
- `can_research(array &$info)` - Check research permissions
- `build_end(int $planet_id, array &$queue)` - Build completion hook
- `research_end(array &$queue)` - Research completion hook
- `fleet_available_missions(array $param, array &$missions)` - Add fleet missions
- `fleet_handler(array $param)` - Handle fleet mission
- `prod_post_process(array &$planet, array &$eco)` - Post-process production
- `battle_post_process(array &$res)` - Post-process battle results

### bbcode Class
**File**: `game/core/bbcode.php`

#### Class Hierarchy
```
bbcode (base class)
    |
    +-- bb_a (links)
    +-- bb_align (alignment)
    +-- bb_color (color)
    +-- bb_del (delete)
    +-- bb_email (email)
    +-- bb_font (font)
    +-- bb_hr (horizontal rule)
    +-- bb_i (italic)
    +-- bb_img (image)
    +-- bb_quote (quote)
    +-- bb_size (size)
    +-- bb_strong (bold)
    +-- bb_sub (subscript)
    +-- bb_sup (superscript)
    +-- bb_u (underline)
```

#### Key Methods
- `parse()` - Parse BBCode to HTML
- `encode()` - Encode HTML to BBCode
- `display()` - Display parsed BBCode

## Database Layer

### Global Variables
- `$query_counter` - Query execution counter
- `$query_log` - Query log for debugging
- `$db_connect` - MySQL connection resource

### Key Functions
- `dbconnect($db_host, $db_user, $db_pass, $db_name)` - Connect to database
- `dbquery($query, $mute)` - Execute query
- `dbarray($result)` - Fetch row from result
- `dbrows($result)` - Count rows in result
- `dbfree($result)` - Free result memory
- `InitDB()` - Initialize database connection
- `AddDBRow($row, $tabname)` - Insert row with mod support

## Utility Functions

### Time Functions
- `microtime()` - Get current time for performance measurement
- `date()` - Format timestamps

### Math Functions
- `floor()` - Round down
- `ceil()` - Round up
- `min()` - Get minimum value
- `max()` - Get maximum value

### Array Functions
- `array_merge()` - Merge arrays
- `array_map()` - Apply callback to array
- `array_filter()` - Filter array elements

## Constants Reference

### Game Object IDs
- **Buildings**: GID_B_METAL_MINE (1), GID_B_CRYS_MINE (2), etc.
- **Research**: GID_R_ESPIONAGE (106), GID_R_COMPUTER (108), etc.
- **Fleet**: GID_F_SC (202), GID_F_LC (203), etc.
- **Defense**: GID_D_RL (401), GID_D_LL (402), etc.
- **Resources**: GID_RC_METAL (700), GID_RC_CRYSTAL (701), etc.

### Planet Types
- PTYP_MOON (0)
- PTYP_PLANET (1)
- PTYP_DF (10000) - Debris field
- PTYP_DEST_PLANET (10001)
- PTYP_COLONY_PHANTOM (10002)
- PTYP_DEST_MOON (10003)
- PTYP_ABANDONED (10004)
- PTYP_FARSPACE (20000)
- PTYP_CUSTOM (20001)

### Fleet Missions
- FTYP_ATTACK (1)
- FTYP_TRANSPORT (3)
- FTYP_DEPLOY (4)
- FTYP_SPY (6)
- FTYP_COLONIZE (7)
- FTYP_RECYCLE (8)
- FTYP_DESTROY (9)
- FTYP_EXPEDITION (15)
- FTYP_MISSILE (20)

### Message Types
- MTYP_PM (0) - Private message
- MTYP_SPY_REPORT (1)
- MTYP_BATTLE_REPORT_LINK (2)
- MTYP_EXP (3)
- MTYP_ALLY (4)
- MTYP_MISC (5)
- MTYP_BATTLE_REPORT_TEXT (6)

## Queue System

### Task Types
- QTYP_BUILD - Building construction
- QTYP_RESEARCH - Research
- QTYP_SHIPYARD - Shipyard construction
- QTYP_FLEET - Fleet mission
- QTYP_ACS - ACS task
- QTYP_AI - AI bot task

### Priority Levels
- QUEUE_PRIO_LOWEST (0)
- QUEUE_PRIO_DEBUG (9999)
- QUEUE_PRIO_BUILD (20)
- QUEUE_PRIO_FLEET (200 + mission type)
- QUEUE_PRIO_RECALC_ALLY_POINTS (400)
- QUEUE_PRIO_RECALC_POINTS (500)
- QUEUE_PRIO_UPDATE_STATS (510)
- QUEUE_PRIO_COUPON (520)
- QUEUE_PRIO_CLEAN_DEBRIS (600)
- QUEUE_PRIO_CLEAN_PLANETS (700)
- QUEUE_PRIO_RELOGIN (777)
- QUEUE_PRIO_CLEAN_PLAYERS (900)
- QUEUE_PRIO_BOT (1000)

## Mod System

### Mod Installation
1. Copy mod to `game/mods/ModName/`
2. Create `main.php` with mod class extending `GameMod`
3. Implement required methods: `install()`, `uninstall()`, `init()`
4. Implement hook methods as needed

### Mod Structure
```
game/mods/ModName/
    main.php          - Mod class
    install.php       - Installation script
    uninstall.php     - Uninstallation script
    config.php        - Configuration
```

### Mod Loading
Mods are loaded from `game/mods/` directory automatically.
Each mod must implement `GameMod` abstract class.
