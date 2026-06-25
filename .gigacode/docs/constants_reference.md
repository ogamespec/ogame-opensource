# OGame Core Constants Reference

## Overview
This document lists all core constants used in OGame Open Source. Constants are defined in `game/core/defs.php` and `game/core/techs.php`.

## Game Object Identifiers

### Building IDs (GID_B_)
Defined in `techs.php`

| Constant | Value | Name | Description |
|----------|-------|------|-------------|
| GID_B_METAL_MINE | 1 | Metal Mine | Metal production building |
| GID_B_CRYS_MINE | 2 | Crystal Mine | Crystal production building |
| GID_B_DEUT_SYNTH | 3 | Deuterium Synthesizer | Deuterium production building |
| GID_B_SOLAR | 4 | Solar Plant | Energy production |
| GID_B_FUSION | 12 | Fusion Reactor | Advanced energy production |
| GID_B_ROBOTS | 14 | Robotics Factory | Building construction |
| GID_B_NANITES | 15 | Nanite Factory | Advanced construction |
| GID_B_SHIPYARD | 21 | Shipyard | Ship construction |
| GID_B_METAL_STOR | 22 | Metal Storage | Metal storage capacity |
| GID_B_CRYS_STOR | 23 | Crystal Storage | Crystal storage capacity |
| GID_B_DEUT_STOR | 24 | Deuterium Tank | Deuterium storage capacity |
| GID_B_RES_LAB | 31 | Research Lab | Research capabilities |
| GID_B_TERRAFORMER | 33 | Terraformer | Planet climate control |
| GID_B_ALLY_DEPOT | 34 | Alliance Depot | Alliance resource storage |
| GID_B_LUNAR_BASE | 41 | Lunar Base | Moon base construction |
| GID_B_PHALANX | 42 | Sensor Phalanx | Long-range scanning |
| GID_B_JUMP_GATE | 43 | Jump Gate | Faster-than-light travel |
| GID_B_MISS_SILO | 44 | Missile Silo | Missile launch facility |

### Research IDs (GID_R_)
Defined in `techs.php`

| Constant | Value | Name | Description |
|----------|-------|------|-------------|
| GID_R_ESPIONAGE | 106 | Espionage Technology | Spy capabilities |
| GID_R_COMPUTER | 108 | Computer Technology | Computer performance |
| GID_R_WEAPON | 109 | Weapons Technology | Weapon upgrades |
| GID_R_SHIELD | 110 | Shielding Technology | Shield strength |
| GID_R_ARMOUR | 111 | Armour Technology | Hull strength |
| GID_R_ENERGY | 113 | Energy Technology | Energy efficiency |
| GID_R_HYPERSPACE | 114 | Hyperspace Technology | Hyperspace travel |
| GID_R_COMBUST_DRIVE | 115 | Combustion Drive | Basic propulsion |
| GID_R_IMPULSE_DRIVE | 117 | Impulse Drive | Advanced propulsion |
| GID_R_HYPER_DRIVE | 118 | Hyperspace Drive | FTL travel |
| GID_R_LASER_TECH | 120 | Laser Technology | Laser weapons |
| GID_R_ION_TECH | 121 | Ion Technology | Ion weapons |
| GID_R_PLASMA_TECH | 122 | Plasma Technology | Plasma weapons |
| GID_R_IGN | 123 | Intergalactic Research Network | Research sharing |
| GID_R_EXPEDITION | 124 | Expedition Technology | Expedition capabilities |
| GID_R_GRAVITON | 199 | Graviton Technology | Advanced technology |

### Fleet IDs (GID_F_)
Defined in `techs.php`

| Constant | Value | Name | Description |
|----------|-------|------|-------------|
| GID_F_SC | 202 | Small Cargo | Basic cargo ship |
| GID_F_LC | 203 | Large Cargo | Heavy cargo ship |
| GID_F_LF | 204 | Light Fighter | Fast attack ship |
| GID_F_HF | 205 | Heavy Fighter | Heavy attack ship |
| GID_F_CRUISER | 206 | Cruiser | Medium combat ship |
| GID_F_BATTLESHIP | 207 | Battleship | Heavy combat ship |
| GID_F_COLON | 208 | Colony Ship | Planet colonization |
| GID_F_RECYCLER | 209 | Recycler | Debris recycler |
| GID_F_PROBE | 210 | Espionage Probe | Spy probe |
| GID_F_BOMBER | 211 | Bomber | Heavy bomber |
| GID_F_SAT | 212 | Solar Satellite | Energy satellite |
| GID_F_DESTRO | 213 | Destroyer | Heavy destroyer |
| GID_F_DEATHSTAR | 214 | Deathstar | Superweapon |
| GID_F_BATTLECRUISER | 215 | Battlecruiser | Fast combat ship |

### Defense IDs (GID_D_)
Defined in `techs.php`

| Constant | Value | Name | Description |
|----------|-------|------|-------------|
| GID_D_RL | 401 | Rocket Launcher | Basic defense |
| GID_D_LL | 402 | Light Laser | Light laser defense |
| GID_D_HL | 403 | Heavy Laser | Heavy laser defense |
| GID_D_GAUSS | 404 | Gauss Cannon | High damage defense |
| GID_D_ION | 405 | Ion Cannon | Ion defense |
| GID_D_PLASMA | 406 | Plasma Turret | Plasma defense |
| GID_D_SDOME | 407 | Small Shield Dome | Small shield |
| GID_D_LDOME | 408 | Large Shield Dome | Large shield |
| GID_D_ABM | 502 | Anti-Ballistic Missiles | ABM defense |
| GID_D_IPM | 503 | Interplanetary Missiles | IPM defense |

### Resource IDs (GID_RC_)
Defined in `techs.php`

| Constant | Value | Name | Description |
|----------|-------|------|-------------|
| GID_RC_METAL | 700 | Metal | Primary resource |
| GID_RC_CRYSTAL | 701 | Crystal | Secondary resource |
| GID_RC_DEUTERIUM | 702 | Deuterium | Tertiary resource |
| GID_RC_ENERGY | 703 | Energy | Energy units |
| GID_RC_DM | 704 | Dark Matter | Premium currency |

### General Constants
| Constant | Value | Description |
|----------|-------|-------------|
| GID_MAX | 0xffff | Maximum game object ID |

## Helper Functions

### IsBuilding(int $gid)
Returns true if ID is a building.

### IsResearch(int $gid)
Returns true if ID is research.

### IsFleet(int $gid)
Returns true if ID is a fleet unit.

### IsDefense(int $gid)
Returns true if ID is a defense unit.

## Planet Types (PTYP_)

### Planet Types
| Constant | Value | Description |
|----------|-------|-------------|
| PTYP_MOON | 0 | Moon |
| PTYP_PLANET | 1 | Planet |
| PTYP_DF | 10000 | Debris field |
| PTYP_DEST_PLANET | 10001 | Destroyed planet |
| PTYP_COLONY_PHANTOM | 10002 | Colonization phantom |
| PTYP_DEST_MOON | 10003 | Destroyed moon |
| PTYP_ABANDONED | 10004 | Abandoned colony |
| PTYP_FARSPACE | 20000 | Infinite distances |
| PTYP_CUSTOM | 20001 | Custom galaxy objects |

### Game Planet Types
| Constant | Value | Description |
|----------|-------|-------------|
| GAME_PTYP_PLANET | 1 | Planet display type |
| GAME_PTYP_DF | 2 | Debris display type |
| GAME_PTYP_MOON | 3 | Moon display type |

## Fleet Missions (FTYP_)

| Constant | Value | Description |
|----------|-------|-------------|
| FTYP_ATTACK | 1 | Attack mission |
| FTYP_ACS_ATTACK | 2 | ACS Attack (slot > 0) |
| FTYP_TRANSPORT | 3 | Transport mission |
| FTYP_DEPLOY | 4 | Deploy mission |
| FTYP_ACS_HOLD | 5 | ACS Hold mission |
| FTYP_SPY | 6 | Espionage mission |
| FTYP_COLONIZE | 7 | Colonize mission |
| FTYP_RECYCLE | 8 | Recycle mission |
| FTYP_DESTROY | 9 | Destroy moon mission |
| FTYP_EXPEDITION | 15 | Expedition mission |
| FTYP_MISSILE | 20 | Missile attack |
| FTYP_ACS_ATTACK_HEAD | 21 | ACS Attack head (slot = 0) |
| FTYP_RETURN | 100 | Fleet returns |
| FTYP_ORBITING | 200 | Fleet orbiting |
| FTYP_CUSTOM | 1000 | Custom modification |

## Message Types (MTYP_)

| Constant | Value | Description |
|----------|-------|-------------|
| MTYP_PM | 0 | Private message |
| MTYP_SPY_REPORT | 1 | Spy report |
| MTYP_BATTLE_REPORT_LINK | 2 | Battle report link |
| MTYP_EXP | 3 | Expedition report |
| MTYP_ALLY | 4 | Alliance message |
| MTYP_MISC | 5 | Miscellaneous message |
| MTYP_BATTLE_REPORT_TEXT | 6 | Battle report text |

## Queue Task Types (QTYP_)

| Constant | Value | Description |
|----------|-------|-------------|
| QTYP_UNBAN | "UnbanPlayer" | Unban player |
| QTYP_CHANGE_EMAIL | "ChangeEmail" | Change email |
| QTYP_ALLOW_NAME | "AllowName" | Allow name changes |
| QTYP_ALLOW_ATTACKS | "AllowAttacks" | Allow attacks |
| QTYP_UNLOAD_ALL | "UnloadAll" | Re-login all players |
| QTYP_CLEAN_DEBRIS | "CleanDebris" | Clean debris field |
| QTYP_CLEAN_PLANETS | "CleanPlanets" | Remove destroyed planets |
| QTYP_CLEAN_PLAYERS | "CleanPlayers" | Delete inactive players |
| QTYP_UPDATE_STATS | "UpdateStats" | Save stat points |
| QTYP_RECALC_POINTS | "RecalcPoints" | Recalc player stats |
| QTYP_RECALC_ALLY_POINTS | "RecalcAllyPoints" | Recalc ally stats |
| QTYP_BUILD | "Build" | Building construction |
| QTYP_DEMOLISH | "Demolish" | Building demolition |
| QTYP_RESEARCH | "Research" | Research task |
| QTYP_SHIPYARD | "Shipyard" | Shipyard construction |
| QTYP_FLEET | "Fleet" | Fleet mission |
| QTYP_DEBUG | "Debug" | Debug event |
| QTYP_AI | "AI" | AI bot task |
| QTYP_COUPON | "Coupon" | Coupon crediting |

## Queue Priorities (QUEUE_PRIO_)

| Constant | Value | Description |
|----------|-------|-------------|
| QUEUE_PRIO_LOWEST | 0 | No priority |
| QUEUE_PRIO_DEBUG | 9999 | Debug event |
| QUEUE_PRIO_BUILD | 20 | Building priority |
| QUEUE_PRIO_FLEET | 200 | Fleet priority |
| QUEUE_PRIO_RECALC_ALLY_POINTS | 400 | Ally points recalc |
| QUEUE_PRIO_RECALC_POINTS | 500 | Player points recalc |
| QUEUE_PRIO_UPDATE_STATS | 510 | Update stats |
| QUEUE_PRIO_COUPON | 520 | Coupon processing |
| QUEUE_PRIO_CLEAN_DEBRIS | 600 | Clean debris |
| QUEUE_PRIO_CLEAN_PLANETS | 700 | Clean planets |
| QUEUE_PRIO_RELOGIN | 777 | Re-login |
| QUEUE_PRIO_CLEAN_PLAYERS | 900 | Clean players |
| QUEUE_PRIO_BOT | 1000 | AI bot |

## User Flags

### Default Flags
| Constant | Value | Description |
|----------|-------|-------------|
| USER_FLAG_SHOW_ESPIONAGE_BUTTON | 0x1 | Show espionage button |
| USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON | 0x2 | Show write message button |
| USER_FLAG_SHOW_BUDDY_BUTTON | 0x4 | Show buddy button |
| USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON | 0x8 | Show rocket attack button |
| USER_FLAG_SHOW_VIEW_REPORT_BUTTON | 0x10 | Show view report button |
| USER_FLAG_DONT_USE_FOLDERS | 0x20 | Don't use folders |
| USER_FLAG_PARTIAL_REPORTS | 0x40 | Show partial reports |
| USER_FLAG_FOLDER_ESPIONAGE | 0x100 | Show espionage folder |
| USER_FLAG_FOLDER_COMBAT | 0x200 | Show combat folder |
| USER_FLAG_FOLDER_EXPEDITION | 0x400 | Show expedition folder |
| USER_FLAG_FOLDER_ALLIANCE | 0x800 | Show alliance folder |
| USER_FLAG_FOLDER_PLAYER | 0x1000 | Show player folder |
| USER_FLAG_FOLDER_OTHER | 0x2000 | Show other folder |
| USER_FLAG_HIDE_GO_EMAIL | 0x4000 | Hide GO email |
| USER_FLAG_FEED_ENABLE | 0x8000 | Enable feed |
| USER_FLAG_FEED_ATOM | 0x10000 | Use Atom format |

### Default Flags
| Constant | Value | Description |
|----------|-------|-------------|
| USER_FLAG_DEFAULT | All flags | Default user flags |

### Officer IDs
| Constant | Value | Description |
|----------|-------|-------------|
| USER_OFFICER_COMMANDER | 1 | Commander |
| USER_OFFICER_ADMIRAL | 2 | Admiral |
| USER_OFFICER_ENGINEER | 3 | Engineer |
| USER_OFFICER_GEOLOGE | 4 | Geologue |
| USER_OFFICER_TECHNOCRATE | 5 | Technocrate |

### User Types
| Constant | Value | Description |
|----------|-------|-------------|
| USER_TYPE_PLAYER | 0 | Regular player |
| USER_TYPE_GO | 1 | Game operator |
| USER_TYPE_ADMIN | 2 | Administrator |

### Special User IDs
| Constant | Value | Description |
|----------|-------|-------------|
| USER_LEGOR | 1 | Legor's account |
| USER_SPACE | 99999 | Technical account |

## User Constants

| Constant | Value | Description |
|----------|-------|-------------|
| USER_NOOB_LIMIT | 5000 | Newbie protection points |

## Production Constants

| Constant | Value | Description |
|----------|-------|-------------|
| PROD_BUILDING_DURATION_FACTOR | 2500 | Building duration factor |
| PROD_SHIPYARD_DURATION_FACTOR | 2500 | Shipyard duration factor |
| PROD_RESEARCH_DURATION_FACTOR | 1000 | Research duration factor |

## Galaxy Constants

| Constant | Value | Description |
|----------|-------|-------------|
| GALAXY_DEUTERIUM_CONS | 10 | Deuterium per galaxy view |
| GALAXY_PHANTOM_DEBRIS | 300 | Minimum debris visibility |

## Trader Constants

| Constant | Value | Description |
|----------|-------|-------------|
| TRADER_DM | 2500 | Merchant call cost |

## Game Limits

| Constant | Value | Description |
|----------|-------|-------------|
| MAX_PLANET | 9 | Maximum planets per player |
| MAX_BUILDINGS_LEVEL | 99 | Maximum building level |
| MAX_RESEARCH_LEVEL | 99 | Maximum research level |
| MAX_SHIPYARD_ORDERS | 99 | Maximum shipyard orders |

## Rapidfire Constants

| Constant | Value | Description |
|----------|-------|-------------|
| RF_MAX | 5000 | Maximum rapidfire value |
| RF_DICE | 100000 | Number of dice faces |

## Rank Masks

| Constant | Value | Description |
|----------|-------|-------------|
| ARANK_DISMISS | 0x001 | Dismiss alliance |
| ARANK_KICK | 0x002 | Kick player |
| ARANK_R_APPLY | 0x004 | View applications |
| ARANK_R_MEMBERS | 0x008 | View member list |
| ARANK_W_APPLY | 0x010 | Edit applications |
| ARANK_W_MEMBERS | 0x020 | Alliance management |
| ARANK_ONLINE | 0x040 | View online status |
| ARANK_CIRCULAR | 0x080 | Write circular |
| ARANK_RIGHT_HAND | 0x100 | 'Right Hand' status |

## Core Version

| Constant | Value | Description |
|----------|-------|-------------|
| CoreVersion | "1.0.0.0" | Core version number |

## Time Constants

| Constant | Value | Description |
|----------|-------|-------------|
| $pagetime | 0 | Page loading time |

## Global Variables

| Variable | Description |
|----------|-------------|
| $GlobalUser | Current user data |
| $GlobalUni | Universe data |
| $aktplanet | Current planet data |
| $session | Session ID |
| $db_connect | Database connection |
| $query_counter | Query counter |
| $query_log | Query log |

## Usage Examples

### Checking Building IDs
```php
if (IsBuilding(GID_B_METAL_MINE)) {
    echo "This is a building";
}
```

### Checking Fleet IDs
```php
if (IsFleet(GID_F_LC)) {
    echo "This is a fleet unit";
}
```

### Using Planet Types
```php
if ($planet['type'] == PTYP_PLANET) {
    echo "This is a planet";
}
```

### Using Fleet Missions
```php
if ($mission == FTYP_TRANSPORT) {
    echo "Transport mission";
}
```

### Using Message Types
```php
if ($message['type'] == MTYP_SPY_REPORT) {
    echo "Spy report";
}
```
