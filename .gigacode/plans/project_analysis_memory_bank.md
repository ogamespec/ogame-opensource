# OGame Open Source - Project Analysis and Memory Bank Plan

## Project Overview

OGame Open Source is a revival of OGame v0.84 with original design. It's a browser-based space strategy game written in PHP with a C-based battle engine.

## Current State Analysis

### Project Structure
- Root directory contains Docker configuration, testing files, and documentation
- Main source code located in `game/` directory
- Subdirectories:
  - `game/core/` - Core engine classes (34 files)
  - `game/pages/` - User-facing pages (49 files)
  - `game/pages_admin/` - Admin pages (29 files)
  - `game/battle/` - C-based battle engine with VS2022 project
  - `game/mods/` - Modification system (4 mods included)
  - `game/loca/` - Localization files (multiple languages)

### Technology Stack
- Backend: PHP 8.x
- Database: MySQL/MariaDB
- Battle Engine: C language (compiled executable)
- Testing: PHPUnit 10.x, PHPStan 2.x
- Containerization: Docker

### Key Features
- Original OGame mechanics preserved
- Fast battle engine with fair rapidfire (C implementation)
- Modification system for extensibility
- Multi-language support (Russian, English, German)
- ACS (Alliance Combat System)
- Event queue system (CRON-less with optional CRON support)

## Memory Bank Organization Plan

### 1. Core Architecture Documentation

#### Database Layer
- **File**: `game/core/db.php`
- Key Functions:
  - `dbconnect()` - Establish MySQL connection
  - `dbquery()` - Execute queries with counter/logging
  - `dbarray()` - Fetch result row
  - `AddDBRow()` - Insert row with mod support
- Global Variables:
  - `$query_counter` - Query execution count
  - `$query_log` - Query log for debugging
  - `$db_connect` - Connection resource

#### Core Module Dependencies
**File**: `game/core/core.php`
- 39 includes forming the core engine
- Core modules: db, utils, techs, loca, bbcode, uni, prod, planet, user, msg, notes, queue, page, ally, fleet, acs, expedition, battle, battle_report, battle_engine, raketen, graviton, debug, bot, botapi, coupon, mods

#### Game Definitions
**File**: `game/core/defs.php`
- Rank masks for alliance roles
- Fleet mission types (FTYP_)
- Message types (MTYP_)
- Planet types (PTYP_)
- Queue task types (QTYP_)
- Queue priorities (QUEUE_PRIO_)
- User flags and types
- Constants: MAX_PLANET, MAX_BUILDINGS_LEVEL, RF_MAX

#### Technology Definitions
**File**: `game/core/techs.php`
- Building IDs (GID_B_)
- Research IDs (GID_R_)
- Fleet IDs (GID_F_)
- Defense IDs (GID_D_)
- Resource IDs (GID_RC_)
- Helper functions: IsBuilding(), IsResearch(), IsFleet(), IsDefense()

### 2. Modification System Architecture

#### GameMod Abstract Class
**File**: `game/core/mods.php`
- Abstract methods: install(), uninstall(), init()
- Hook methods for extending functionality
- Available hooks: route, route_admin, update_queue, add_resources, add_menuitems, lock_tables, get_planet_image, build_end, fleet_handler, prod_post_process, battle_post_process

#### Included Modifications
1. **GalaxyTool** - Galaxy scanning functionality
2. **BogusMod** - Basic mod template
3. **DeepSpaceHorror** - Extended game content
4. **SpaceStorm** - Storm events system

### 3. Battle Engine Architecture

#### C Engine
**File**: `game/battle/battle.cpp`
- Output format: PHP unserialize() compatible
- Data structures:
  - `TechParam` - Unit parameters
  - `RFTab` - Rapidfire table
  - `flatten_array` - ID to ordinal mapping
  - `unflatten_array` - Ordinal to ID mapping
- Battle rounds processing with damage calculation
- Rapidfire implementation with configurable dice (RF_DICE = 100000)

#### PHP Battle Controller
**File**: `game/core/battle.php`
- Interface between PHP and C engine
- Battle report generation
- Fleet and defense calculations

### 4. Page System Architecture

#### Page Abstract Class
**File**: `game/core/page.php`
- Abstract class with 40+ methods
- Key methods:
  - `PageHeader()` - Generate HTML header
  - `GetPlanetImage()` - Planet visualization
  - `UserSkin()` - Skin selection
  - `LeftMenu()` - Navigation menu
  - `ResourceList()` - Resource display

#### Page Categories
1. **Core Pages** (game/pages/)
   - Empire view, resources, buildings, tech tree
   - Fleet management, galaxy view
   - Alliance, messages, notes

2. **Admin Pages** (game/pages_admin/)
   - User management, universe settings
   - Battle simulation, bug reporting
   - Queue management, coupons

### 5. Event Queue System

**File**: `game/core/queue.php`
- Task types: BUILD, RESEARCH, SHIPYARD, FLEET, AI
- Priority levels from QUEUE_PRIO_LOWEST to QUEUE_PRIO_BOT
- CRON-less implementation with on-demand processing
- AI bot integration support

### 6. Testing Infrastructure

#### Unit Testing
**File**: `phpunit.xml`
- Bootstrap: vendor/autoload.php
- Test suite: testing/
- Environment: SQLite in-memory database

#### Mock Functions
**File**: `testing/mock_functions.php`
- `dbquery()` - Query logging mock
- `dbarray()` - Result fetching mock
- `LoadUser()` - User loading mock
- `AddDBRow()` - Database insert mock

### 7. Configuration and Environment

#### Environment Variables
**File**: `.env.example`
- `MYSQL_ROOT_PASSWORD` - Database root password

#### PHP Configuration
**File**: `php.ini`
- Runtime configuration settings

#### Static Analysis
**File**: `phpstan.neon`
- Level: 8 (strict)
- Paths: game/, wwwroot/
- Excluded: battle engine, CSS/JS, bbcode.php

## Project Analysis Recommendations

### Immediate Actions
1. Document database schema and table structures
2. Create class hierarchy diagrams for core classes
3. Map all modification hooks with examples
4. Document battle calculation algorithm
5. Create API documentation for mod developers

### Code Quality
1. Address PHPStan warnings (level 8 configured)
2. Expand PHPUnit test coverage
3. Add type hints throughout codebase
4. Document global variables usage patterns

### Documentation Needs
1. Installation guides (Docker and manual)
2. Development environment setup
3. Modification development tutorial
4. Database schema documentation
5. API reference for hooks and events

## Next Steps

1. Create database schema documentation
2. Document core class relationships
3. Create modification development guide
4. Document battle engine algorithm
5. Set up automated testing pipeline
