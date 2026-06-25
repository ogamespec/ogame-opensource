# OGame Project - File Structure Reference

## Directory Structure

```
c:\Work\ogame-opensource\
├── .dockerignore                  # Docker ignore rules
├── .env                           # Environment variables (local)
├── .env.example                   # Environment example
├── .git/                          # Git repository
├── .gitattributes                 # Git attributes
├── .gitignore                     # Git ignore rules
├── .phpunit.result.cache          # PHPUnit cache
├── Dockerfile                     # Docker build file
├── Doxyfile                       # Doxygen configuration
├── LICENSE                        # License file
├── README.md                      # English README
├── ReadmeRus.md                   # Russian README
├── compose.yaml                   # Docker Compose
├── composer.json                  # PHP dependencies
├── composer.lock                  # PHP dependencies lock
├── cronfile                       # Cron job configuration
├── download/                      # Downloadable files
│   ├── allianzen.php
│   ├── buddy.php
│   ├── buildings-def.php
│   ├── buildings-fleet.php
│   ├── buildings.php
│   ├── b_building.php
│   ├── changelog.php
│   ├── flotten1.php
│   ├── galaxy.php
│   ├── imperium.php
│   ├── index.php
│   ├── index2.php
│   ├── js/                        # JavaScript files
│   ├── leftmenu.php
│   ├── messages.php
│   ├── notizen.php
│   ├── options.php
│   ├── overview.php
│   ├── renameplanet.php
│   ├── resources.php
│   ├── stat.php
│   ├── suche.php
│   ├── techtree.php
│   └── use/                       # Utility files
├── game/                          # Main game source code
│   ├── ainfo.php                  # Alliance info page
│   ├── battle/                    # Battle engine
│   │   ├── battle.cpp             # C battle engine source
│   │   ├── battle.h               # C header file
│   │   ├── file.cpp               # File operations
│   │   ├── file.h                 # File header
│   │   ├── rand.cpp               # Random number generator
│   │   ├── rand.h                 # Random header
│   │   ├── VS2022/                # Visual Studio project
│   │   └── test/                  # Battle tests
│   │       ├── battledata/        # Battle test data
│   │       └── battleresult/      # Battle test results
│   ├─��� battledata/                # Battle data storage
│   ├── battleresult/              # Battle results storage
│   ├── core/                      # Core engine modules
│   │   ├── acs.php                # Alliance Combat System
│   │   ├── ally.php               # Alliance system
│   │   ├── allyapps.php           # Alliance applications
│   │   ├── allyranks.php          # Alliance ranks
│   │   ├── battle.php             # Battle controller
│   │   ├── battle_engine.php      # Battle engine interface
│   │   ├── battle_report.php      # Battle report generation
│   │   ├── bbcode.php             # BBCode parser
│   │   ├── bot.php                # AI bot system
│   │   ├── botapi.php             # Bot API
│   │   ├── buddy.php              # Buddy system
│   │   ├── core.php               # Core module loader
│   │   ├── coupon.php             # Coupon system
│   │   ├── db.php                 # Database layer
│   │   ├── debug.php              # Debug utilities
│   │   ├── defs.php               # Game definitions
│   │   ├── expedition.php         # Expedition system
│   │   ├── expedition_battle.php  # Expedition battles
│   │   ├── fleet.php              # Fleet management
│   │   ├── graviton.php           # Graviton technology
│   │   ├── install_tabs.php       # Table installation
│   │   ├── loca.php               # Localization system
│   │   ├── mods.php               # Modification system
│   │   ├── msg.php                # Message system
│   │   ├── notes.php              # Notes management
│   │   ├── page.php               # Page rendering
│   │   ├── planet.php             # Planet management
│   │   ├── prod.php               # Production calculations
│   │   ├── queue.php              # Event queue
│   │   ├── raketen.php            # Missile system
│   │   ├── techs.php              # Technology definitions
│   │   ├── uni.php                # Universe management
│   │   ├── user.php               # User management
│   │   └── utils.php              # Utility functions
│   ├── cron.php                   # Cron handler
│   ├── css/                       # CSS stylesheets
│   ├── img/                       # Images
│   ├── install.php                # Installation script
│   ├──JS/                         # JavaScript files
│   ├── maintenance.php            # Maintenance page
│   ├── pic.php                    # Picture handler
│   ├── pranger.php                # Pranger page
│   ├── router.json                # Router configuration
│   ├── validate.php               # Validation script
│   ├── pages/                     # User-facing pages (49 files)
│   │   ├── ainfo.php
│   │   ├── allianzdepot.php
│   │   ├── allianzen.php
│   │   ├── allianzen_circular.php
│   │   ├── allianzen_main.php
│   │   ├── allianzen_members.php
│   │   ├── allianzen_misc.php
│   │   ├── allianzen_ranks.php
│   │   ├── allianzen_settings.php
│   │   ├── b_building.php
│   │   ├── bericht.php
│   │   ├── bewerben.php
│   │   ├── bewerbungen.php
│   │   ├── buddy.php
│   │   ├── buildings.php
│   │   ├── changelog.php
│   │   ├── event_list.php
│   │   ├── fleet_templates.php
│   │   ├── flotten1.php
│   │   ├── flotten2.php
│   │   ├── flotten3.php
│   │   ├── flottenversand.php
│   │   ├── flottenversand_ajax.php
│   │   ├── galaxy.php
│   │   ├── galaxy_js.php
│   │   ├── imperium.php
│   │   ├── infos.php
│   │   ├── leftmenu.json
│   │   ├── logout.php
│   │   ├── messages.php
│   │   ├── micropayment.php
│   │   ├── notizen.php
│   │   ├── options.php
│   │   ├── overview.php
│   │   ├── overview_events.php
│   │   ├── payment.php
│   │   ├── phalanx.php
│   │   ├── phalanx_events.php
│   │   ├── pranger.php
│   │   ├── renameplanet.php
│   │   ├── res_panel.json
│   │   ├── resources.php
│   │   ├── sprungtor.php
│   │   ├── statistics.php
│   │   ├── suche.php
│   │   ├── techtree.php
│   │   ├── techtreedetails.php
│   │   ├── trader.php
│   │   └── writemessages.php
│   ├── pages_admin/               # Admin pages (29 files)
│   │   ├── admin.php
│   │   ├── admin_bans.php
│   │   ├── admin_battle.php
│   │   ├── admin_botedit.php
│   │   ├── admin_bots.php
│   │   ├── admin_broadcast.php
│   │   ├── admin_browse.php
│   │   ├── admin_checksum.php
│   │   ├── admin_colony_settings.php
│   │   ├── admin_coupons.php
│   │   ├── admin_db.php
│   │   ├── admin_debug.php
│   │   ├── admin_errors.php
│   │   ├── admin_expedition.php
│   │   ├── admin_fleetlogs.php
│   │   ├── admin_home.php
│   │   ├── admin_loca.php
│   │   ├── admin_logins.php
│   │   ├── admin_mods.php
│   │   ├── admin_panel.php
│   │   ├── admin_planets.php
│   │   ├── admin_queue.php
│   │   ├── admin_raksim.php
│   │   ├── admin_reports.php
│   │   ├── admin_router.json
│   │   ├── admin_sim.php
│   │   ├── admin_uni.php
│   │   ├── admin_userlogs.php
│   │   └── admin_users.php
│   ├── temp/                      # Temporary files
│   └── mods/                      # Modifications (4 mods)
│       ├── BogusMod/
│       │   └── main.php
│       ├── DeepSpaceHorror/
│       │   └── main.php
│       ├── GalaxyTool/
│       │   ├── main.php
│       │   └── pages_admin/
│       ├── SpaceStorm/
│       │   └── main.php
│       └── mods.json              # Mods list
├── php.ini                        # PHP configuration
├── phpstan.neon                   # PHPStan configuration
├── phpstan_report_latest.txt      # PHPStan report
├── phpunit.xml                    # PHPUnit configuration
├── testing/                       # Testing files
│   ├── HomepageTest.php
│   ├── NotesTest.php
│   ├── SimpleTest.php
│   └── mock_functions.php         # Mock functions for testing
├── vendor/                        # Composer dependencies
├── wiki/                          # Wiki documentation
│   ├── en/                        # English wiki
│   ├── de/                        # German wiki
│   ├── ru/                        # Russian wiki
│   └── imgstore/                  # Wiki images
└── wwwroot/                       # Web root (alternative)
    ├── config.php                 # Configuration file
    ├── css/                       # CSS files
    ├── js/                        # JavaScript files
    ├── img/                       # Images
    ├── evolution/                 # Evolution skin
    ├── index.php                  # Main entry point
    └── evolutions/                # Evolution skins
```

## Key Files Reference

### Entry Points
| File | Purpose |
|------|---------|
| `game/index.php` | Main game entry point |
| `wwwroot/index.php` | Alternative entry point |
| `game/cron.php` | Cron job handler |
| `game/maintenance.php` | Maintenance mode |

### Core Modules
| File | Purpose |
|------|---------|
| `game/core/core.php` | Module loader |
| `game/core/db.php` | Database layer |
| `game/core/defs.php` | Game definitions |
| `game/core/techs.php` | Technology definitions |
| `game/core/mods.php` | Modification system |
| `game/core/page.php` | Page rendering |
| `game/core/battle.php` | Battle controller |

### Configuration
| File | Purpose |
|------|---------|
| `composer.json` | PHP dependencies |
| `phpunit.xml` | PHPUnit config |
| `phpstan.neon` | PHPStan config |
| `php.ini` | PHP settings |
| `.env.example` | Environment example |

### Documentation
| File | Purpose |
|------|---------|
| `README.md` | English README |
| `ReadmeRus.md` | Russian README |
| `.gigacode/docs/` | Memory bank docs |

### Testing
| File | Purpose |
|------|---------|
| `testing/` | Test files |
| `testing/mock_functions.php` | Mock functions |

### Battle Engine
| File | Purpose |
|------|---------|
| `game/battle/battle.cpp` | C battle engine |
| `game/battle/battle.h` | C header |
| `game/battle.exe` | Compiled engine |

### Modifications
| File | Purpose |
|------|---------|
| `game/mods/` | Mod directory |
| `game/mods/mods.json` | Mods list |

## File Naming Conventions

### Core Files
- `*.php` - PHP source files
- `*.json` - Configuration files
- `*.css` - Stylesheets
- `*.js` - JavaScript files

### Page Files
- `pages/*.php` - User pages
- `pages_admin/*.php` - Admin pages

### Core Modules
- `game/core/*.php` - Core modules
- Named by function (db, page, fleet, etc.)

### Modification Files
- `game/mods/ModName/main.php` - Mod class
- `game/mods/ModName/install.php` - Installation
- `game/mods/ModName/uninstall.php` - Uninstallation

## Directory Purposes

### game/
Main game source code directory

### game/core/
Core engine modules (34 files)

### game/pages/
User-facing pages (49 files)

### game/pages_admin/
Admin pages (29 files)

### game/battle/
C battle engine

### game/mods/
Modification system

### game/loca/
Localization files

### testing/
Unit tests and mocks

### wwwroot/
Alternative web root

### wiki/
Wiki documentation

## Configuration Files

### Root Level
- `composer.json` - PHP dependencies
- `phpunit.xml` - Testing config
- `phpstan.neon` - Static analysis
- `Dockerfile` - Docker build
- `compose.yaml` - Docker compose

### Game Level
- `router.json` - Page router
- `router_admin.json` - Admin router
- `leftmenu.json` - Menu structure
- `res_panel.json` - Resource panel

## Module Dependencies

```
core.php
├── db.php
├── utils.php
├── techs.php
├── loca.php
├── bbcode.php
├── uni.php
├── prod.php
├── planet.php
├── user.php
├── msg.php
├── notes.php
├── queue.php
├── page.php
├── ally.php
├── allyapps.php
├── allyranks.php
├── buddy.php
├── fleet.php
├── acs.php
├── expedition.php
├── battle.php
├── battle_report.php
├── expedition_battle.php
├── battle_engine.php
├── raketen.php
├── graviton.php
├── debug.php
├── bot.php
├── botapi.php
├── coupon.php
└── mods.php
```

## Import Paths

### Core Module Include Path
```php
require_once "defs.php";
require_once "db.php";
require_once "utils.php";
```

### Page Include Path
```php
require_once "../core/core.php";
```

### Mod Include Path
```php
require_once "../core/mods.php";
```

## Deployment Structure

### Development
- `game/` - Source code
- `testing/` - Tests
- `wiki/` - Documentation

### Production
- `game/` - Source code
- `wwwroot/` - Web root
- `vendor/` - Dependencies
- `game/battle.exe` - Battle engine

### Docker
- `game/` - Source code
- `/var/www/html/` - Web root
- `/app/vendor/` - Dependencies

## Notes
- All paths are relative to project root
- Core modules use relative includes
- Mod files use absolute includes
- Configuration in `config.php` (wwwroot)
- Database prefix configurable in config
