# OGame Database Schema Documentation

## Overview
This document describes the database schema for OGame Open Source. The database stores all game data including planets, fleets, buildings, and player information.

## Database Configuration

### Connection Settings
- Host: `$db_host`
- User: `$db_user`
- Password: `$db_pass`
- Database: `$db_name`
- Prefix: `$db_prefix` (configured in config.php)

### Connection Initialization
```php
function InitDB() : void {
    global $db_host, $db_user, $db_pass, $db_name;
    dbconnect($db_host, $db_user, $db_pass, $db_name);
    dbquery("SET NAMES 'utf8';");
    dbquery("SET CHARACTER SET 'utf8';");
    dbquery("SET SESSION collation_connection = 'utf8_general_ci';");
}
```

## Core Tables

### 1. players (Players)
Stores player account information.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Player ID (primary key) |
| name | VARCHAR | Player username |
| email | VARCHAR | Player email address |
| password | VARCHAR | Password hash |
| regdate | INT | Registration timestamp |
| lastlogin | INT | Last login timestamp |
| authlevel | TINYINT | Authorization level (0=player, 1=GO, 2=admin) |
| planets | INT | Number of planets |
| points | BIGINT | Total points |
| rank | INT | Player rank |
| flags | INT | User flags bitmask |
| lang | VARCHAR | Language preference |
| active | TINYINT | Account status |
| admin_notes | TEXT | Admin notes |
| email_time | INT | Email cooldown timestamp |
| email_count | INT | Email count |
| session_id | VARCHAR | Current session ID |
| skin | VARCHAR | User skin path |
| useskin | TINYINT | Use custom skin flag |
| onlinetime | INT | Last online timestamp |
| fleet | INT | Fleet slots used |
| fleet_max | INT | Maximum fleet slots |
| research | INT | Research slots used |
| research_max | INT | Maximum research slots |

### 2. planets (Planets)
Stores planet and moon data.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Planet ID (primary key) |
| owner | INT | Owner player ID |
| name | VARCHAR | Planet name |
| galaxy | INT | Galaxy coordinate |
| system | INT | System coordinate |
| planet | INT | Planet position |
| type | TINYINT | Planet type (PTYP_) |
| last_update | INT | Last update timestamp |
| diameter | INT | Planet diameter |
| field_current | INT | Current fields used |
| field_max | INT | Maximum fields |
| temp_min | INT | Minimum temperature |
| temp_max | INT | Maximum temperature |
| metal | BIGINT | Metal resources |
| metal_per_hour | BIGINT | Metal production per hour |
| crystal | BIGINT | Crystal resources |
| crystal_per_hour | BIGINT | Crystal production per hour |
| deuterium | BIGINT | Deuterium resources |
| deuterium_per_hour | BIGINT | Deuterium production per hour |
| energy_used | INT | Energy consumption |
| energy_max | INT | Energy production |
| metal_mine | INT | Metal mine level |
| crystal_mine | INT | Crystal mine level |
| deuterium_synth | INT | Deuterium synthesizer level |
| solar_plant | INT | Solar plant level |
| fusion_reactor | INT | Fusion reactor level |
| robot_factory | INT | Robotics factory level |
| nanite_factory | INT | Nanite factory level |
| shipyard | INT | Shipyard level |
| metal_storage | INT | Metal storage level |
| crystal_storage | INT | Crystal storage level |
| deuterium_tank | INT | Deuterium tank level |
| research_lab | INT | Research lab level |
| terraformer | INT | Terraformer level |
| alliance_depot | INT | Alliance depot level |
| lunar_base | INT | Lunar base level |
| sensor_phalanx | INT | Sensor phalanx level |
| jump_gate | INT | Jump gate level |
| missile_silo | INT | Missile silo level |
| rocket_launcher | INT | Rocket launcher count |
| light_laser | INT | Light laser count |
| heavy_laser | INT | Heavy laser count |
| gauss_cannon | INT | Gauss cannon count |
| ion_cannon | INT | Ion cannon count |
| plasma_turret | INT | Plasma turret count |
| small_shield_dome | INT | Small shield dome count |
| large_shield_dome | INT | Large shield dome count |
| anti_ballistic_missiles | INT | ABM count |
| interplanetary_missiles | INT | IPM count |
| flags | INT | Planet flags |
| destroyed | INT | Destroyed timestamp (0 = active) |
| debris_field_metal | BIGINT | Debris field metal |
| debris_field_crystal | BIGINT | Debris field crystal |

### 3. fleet (Fleet)
Stores active fleet missions.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Fleet ID (primary key) |
| owner | INT | Owner player ID |
| mission | TINYINT | Mission type (FTYP_) |
| fleet_id | INT | Fleet identifier |
| start_time | INT | Mission start timestamp |
| start_galaxy | INT | Start galaxy |
| start_system | INT | Start system |
| start_planet | INT | Start planet |
| start_type | TINYINT | Start type |
| end_time | INT | Mission end timestamp |
| end_galaxy | INT | Destination galaxy |
| end_system | INT | Destination system |
| end_planet | INT | Destination planet |
| end_type | TINYINT | Destination type |
| resource_metal | BIGINT | Transported metal |
| resource_crystal | BIGINT | Transported crystal |
| resource_deuterium | BIGINT | Transported deuterium |
| resource_dark_matter | BIGINT | Transported dark matter |
| units | TEXT | JSON encoded fleet units |
| return_flight | TINYINT | Return flight flag |
| rendezvous | TINYINT | Rendezvous flag |
| target_owner | INT | Target owner (for ACS) |
| task | VARCHAR | Task description |
| slot | INT | Slot number (for ACS) |

### 4. fleet_templates (Fleet Templates)
Stores saved fleet templates.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Template ID |
| owner | INT | Owner player ID |
| name | VARCHAR | Template name |
| units | TEXT | JSON encoded units |
| default | TINYINT | Default template flag |

### 5. messages (Messages)
Stores player messages (PM, reports, etc.).

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Message ID |
| owner | INT | Recipient player ID |
| sender | INT | Sender player ID |
| type | TINYINT | Message type (MTYP_) |
| time | INT | Message timestamp |
| from_name | VARCHAR | Sender name |
| subject | VARCHAR | Message subject |
| text | TEXT | Message content |
| folder | TINYINT | Message folder |
| read | TINYINT | Read status |

### 6. buildings (Buildings Queue)
Stores building and research queue.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Queue ID |
| planet_id | INT | Planet ID |
| object_id | INT | Object type ID |
| end_time | INT | Completion timestamp |
| sub_id | INT | Subtask ID |
| priority | TINYINT | Priority level |

### 7. alliance (Alliances)
Stores alliance information.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Alliance ID |
| tag | VARCHAR | Alliance tag |
| name | VARCHAR | Alliance name |
| founder | INT | Founder player ID |
| creation_date | INT | Creation timestamp |
| external_message | TEXT | Public message |
| internal_message | TEXT | Internal message |
| photo | VARCHAR | Alliance photo URL |
| desc | TEXT | Alliance description |
| req_name | VARCHAR | Requirement name |
| req_min_points | BIGINT | Minimum points requirement |
| req_count | INT | Member count requirement |
| delete_time | INT | Deletion timestamp |
| rank1_name | VARCHAR | Rank 1 name |
| rank2_name | VARCHAR | Rank 2 name |
| rank3_name | VARCHAR | Rank 3 name |
| rank4_name | VARCHAR | Rank 4 name |
| rank5_name | VARCHAR | Rank 5 name |
| rank6_name | VARCHAR | Rank 6 name |
| rank7_name | VARCHAR | Rank 7 name |
| rank8_name | VARCHAR | Rank 8 name |
| rank9_name | VARCHAR | Rank 9 name |

### 8. alliance_members (Alliance Members)
Stores alliance membership information.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Membership ID |
| ally_id | INT | Alliance ID |
| player_id | INT | Player ID |
| rank_id | INT | Rank ID |
| apply_text | TEXT | Application text |
| join_date | INT | Join timestamp |
| status | TINYINT | Membership status |

### 9. notes (Notes)
Stores player notes.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Note ID |
| owner | INT | Owner player ID |
| subject | VARCHAR | Note subject |
| text | TEXT | Note content |
| time | INT | Creation timestamp |
| PIN | TINYINT | Pinned note flag |

### 10. buddy (Buddy Requests)
Stores buddy requests.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Request ID |
| owner | INT | Owner player ID |
| player_id | INT | Requested player ID |
| from_name | VARCHAR | Requester name |
| time | INT | Request timestamp |
| status | TINYINT | Request status |

### 11. queue (Event Queue)
Stores event queue tasks.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Queue ID |
| time | INT | Execution time |
| type | VARCHAR | Task type (QTYP_) |
| data | TEXT | JSON encoded task data |
| priority | INT | Priority level |

### 12. stats (Statistics)
Stores player statistics.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Stat ID |
| player_id | INT | Player ID |
| stat_type | TINYINT | Stat type (0=daily, 1=weekly, 2=monthly) |
| stat_point | BIGINT | Points |
| fleet_point | BIGINT | Fleet points |
| research_point | BIGINT | Research points |
| timestamp | INT | Stat timestamp |

### 13. coupons (Coupons)
Stores coupon codes.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Coupon ID |
| code | VARCHAR | Coupon code |
| type | TINYINT | Coupon type |
| value | BIGINT | Coupon value |
| used | TINYINT | Usage status |
| used_by | INT | User ID who used it |

### 14. bans (Bans)
Stores player bans.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Ban ID |
| player_id | INT | Player ID |
| admin_id | INT | Admin ID |
| reason | TEXT | Ban reason |
| start_time | INT | Ban start timestamp |
| end_time | INT | Ban end timestamp |
| type | TINYINT | Ban type |

### 15. logs (Logs)
Stores system logs.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Log ID |
| player_id | INT | Player ID |
| action | VARCHAR | Action description |
| details | TEXT | Additional details |
| time | INT | Log timestamp |
| admin_id | INT | Admin ID |

### 16. fleetlogs (Fleet Logs)
Stores fleet combat logs.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Log ID |
| fleet_id | INT | Fleet ID |
| battle_report | TEXT | Battle report data |
| time | INT | Log timestamp |

### 17. expedition (Expeditions)
Stores expedition data.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Expedition ID |
| owner | INT | Owner player ID |
| fleet_id | INT | Fleet ID |
| start_time | INT | Start timestamp |
| end_time | INT | End timestamp |
| galaxy | INT | Galaxy coordinate |
| system | INT | System coordinate |
| planet | INT | Planet position |
| type | TINYINT | Expedition type |
| status | TINYINT | Status |
| results | TEXT | Results data |

### 18. bots (Bots)
Stores AI bot configurations.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Bot ID |
| name | VARCHAR | Bot name |
| owner | INT | Owner player ID |
| strategy | TEXT | Strategy configuration |
| active | TINYINT | Active status |
| last_action | INT | Last action timestamp |

## Database Helper Functions

### dbconnect
Establishes MySQL connection.

### dbquery
Executes query and increments counter.

### dbarray
Fetches single row from result.

### dbrows
Returns number of rows in result.

### dbfree
Frees result memory.

### AddDBRow
Inserts row with mod support.

## Database Prefix Usage
All table names use `$db_prefix` prefix for multi-universe support.
