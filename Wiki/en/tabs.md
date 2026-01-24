# Database tables

Tables are created when installing the universe (game/install.php).

Before working with the database, the following MySQL spells must be performed:

```php
    dbquery("SET NAMES 'utf8';");
    dbquery("SET CHARACTER SET 'utf8';");
    dbquery("SET SESSION collation_connection = 'utf8_general_ci';");
```

What they mean you can google more or ask СhatGPT, but in general they are needed to customize the encoding of string variables (use the universal encoding utf8). All the rest of the game is also sharpened for utf8.

## Table prefix

A prefix is added to all tables from the config.php configuration file - $db_prefix. This is done for convenience in case there will be tables of several universes in one database.

## What to do if you want to change the format of tables

All web developers know that changing database tables is a pain in the ass. In general, it is quite painful to cut "alive", especially for such a developed project.

Procedure:
- Make all necessary modifications to columns and types in the install_tabs.php script
- Add the initial setting of autoincrements in install.php
- Add LOCK tables in db.php (LockTables)
- If it is not supposed to be a clean reinstallation of the Universe, then you need to tweak the columns on the live database (ALTER TABLE + ADD COLUMN) using phpMyAdmin or another similar tool. It is highly recommended to pause the Universe (freeze=1).

## How often do the tables change?

Well.. the project is developing more or less smoothly now. We want to do a little refactoring, so get ready for a little shuffling of tables.

After that, it is unlikely that there will be significant perturbations, because all mechanics and features of version 0.84 are practically implemented.

## Are the tables similar to the tables from the original game?

No 0.84 source code was leaked, so it's hard to say how similar the tables are to what's in the original game.

But just for the most part it all looks like a black box, so there's not much difference. For example, if a planet has a name and G:S:P coordinates, they are obviously stored in the corresponding columns of the "planets" table. And everything else will be similar.

In other words - there is no reason to worry that the tables are organized a bit "unoriginal". It doesn't make sense from the client side anyway, the main thing is that all the game mechanics are respected.

## Settings and the State of the Universe (uni)

|Column|Type|Description|
|---|---|---|
|num|INT PRIMARY KEY|The universe number will be listed in the window title bar and above the main menu in the game |
|speed|FLOAT|Acceleration affects the speed of resource production, the duration of construction and research, and the minimum duration of Vacation Mode. |
|fspeed|FLOAT|Acceleration affects the speed of flying fleets |
|galaxies|INT|Number of galaxies |
|systems|INT|Number of systems |
|maxusers|INT|Maximum number of accounts. Once this value is reached, the registration is closed until space is available. |
|acs|INT|Maximum number of invited players for a Alliance Attack (excluding yourself). The maximum number of fleets in the ACS is calculated by the formula N^2, where N is the number of participants. At N=0 the ACS is disabled. |
|fid|INT|Fleet to Debris. The specified number of percent of the fleet is dropped as debris. If 0 is specified, the FID is disabled. |
|did|INT|Defense to Debris. The specified number of percent of defense is dropped as debris. If 0 is specified, the DID is disabled. |
|rapid|INT|Rapid-fire |
|moons|INT|Moons and Death Stars |
|defrepair|INT|Defense recovery percentage (e.g. 70) |
|defrepair_delta|INT|Variation of defense recovery percentage (e.g. +/- 10) |
|usercount|INT|Number of players in the universe |
|freeze|INT|1: stop time in the universe |
|news1|TEXT|1st news headline |
|news2|TEXT|2nd news headline |
|news_until|INT UNSIGNED|End date of the news, time() |
|startdate|INT UNSIGNED|The date the universe was opened, time() |
|battle_engine|TEXT|The path to the battle engine |
|lang|CHAR(4)|The language used for the universe. Used to be a per-user setting, but then made global. |
|hacks|INT|Game hack attempt counter. Resets on relogin. |
|ext_board|TEXT|External link to the Forum. If the line is empty, the item is not shown in the menu.|
|ext_discord|TEXT|External link to Discord. If the string is empty, the item is not shown in the menu.|
|ext_tutorial|TEXT|External link to Tutorial. If the string is empty, the item is not shown in the menu.|
|ext_rules|TEXT|External link to Rules. If the line is empty, the item is not shown in the menu.|
|ext_impressum|TEXT|External link to Impressum ("About Us"). If the string is empty, the item is not shown in the menu.|
|php_battle|INT|1: Use a spare PHP battle engine (battle_engine.php) instead of a C implementation.|
|feedage|INT|the RSS(Atom) update period in minutes, 60 by default|
|modlist|TEXT|List of active modifications separated by `;` in order of activation|

## Users (users)

|Column|Type|Description|
|---|---|---|
|player_id|INT AUTO_INCREMENT PRIMARY KEY|User ordinal number, starts with 100000 | 
|regdate|INT UNSIGNED|Account registration date, time()| 
|ally_id|INT|Number of the alliance the player is a member of (0 - no alliance) | 
|joindate|INT UNSIGNED|Date of joining the alliance, time()| 
|allyrank|INT|Rank of player in the alliance | 
|session|CHAR(12)|Session for the links (public) | 
|private_session|CHAR(32)|Private session for cookies | 
|name|CHAR(20)|Lower-case user name for comparison | 
|oname|CHAR(20)|Username original | 
|name_changed|INT|Has the username been changed? (1 or 0) | 
|**Q** name_until|INT UNSIGNED|When you can change your username next time, time()| 
|password|CHAR(32)|MD5 hash of password and secret word | 
|temp_pass|CHAR(32)|MD5 hash of the recovery password and secret word | 
|pemail|CHAR(50)|Permanent mailing address | 
|email|CHAR(50)|Temporary mailing address |
|email_changed|INT|Temporary mailing address has been changed | 
|**Q** email_until|INT UNSIGNED|When to replace a permanent email with a temporary one, time()| 
|disable|INT|The account has been put up for deletion | 
|**Q** disable_until|INT UNSIGNED|When delete an account, time()| 
|vacation|INT|An account in vacation mode | 
|vacation_until|INT UNSIGNED|When you can turn off vacation mode, time()| 
|banned|INT|Account blocked | 
|**Q** banned_until|INT UNSIGNED|Ban end time, time()| 
|noattack|INT|Ban on attacks | 
|**Q** noattack_until|INT UNSIGNED|When the ban on attacks ends, time()|
|lastlogin|INT UNSIGNED|Last time of entry into the game, time() | 
|lastclick|INT UNSIGNED|Last click, to determine the player's activity, time() | 
|ip_addr|CHAR(15)|user IP address | 
|validated|INT|User is activated. If the user is not activated, they are not allowed to send game messages and applications to alliances. | 
|validatemd|CHAR(32)|Activation code | 
|hplanetid|INT|Number of the Home Planet | 
|admin|INT|0 - regular player, 1 - operator, 2 - administrator | 
|sortby|INT|Sorting order of planets: 0 - colonization order, 1 - coordinates, 2 - alphabetical order | 
|sortorder|INT|Order: 0 - ascending, 1 - descending |
|skin|CHAR(80)|Skin path (CHAR(80)). It is obtained by concatenating the path to the host and the skin name, but the length of the string is not more than 80 characters. | 
|useskin|INT|Show skin, if 0 - then show default skin | 
|deact_ip|INT|Disable IP check | 
|maxspy|INT|Number of spy probes (1 by default, 0...99) | 
|maxfleetmsg|INT|Maximum fleet messages to the Galaxy (3 by default, 0...99, 0=1) | 
|lang|CHAR(4)|Game Interface language |
|aktplanet|INT|Current selected planet |
|dm|INT UNSIGNED|Purchased DM | 
|dmfree|INT UNSIGNED|DM found on the expedition |
|sniff|INT|Enable tracking of browse history (Admin) |
|debug|INT|Enable display of debugging information |
|trader|INT|0 - no merchant found, 1 - merchant buys metal, 2 - merchant buys crystal, 3 - merchant buys deuterium | 
|rate_m|DOUBLE|merchant exchange rates ( e.g. 3.0 : 1.8 : 0.6 ) |
|rate_k|DOUBLE| |
|rate_d|DOUBLE| |
|score1,2,3|BIGINT,INT,INT|Points for buildings, fleets, and research | 
|place1,2,3|INT,INT,INT|A place for buildings, fleets, research | 
|oldscore1,2,3|BIGINT,INT,INT|Old points for buildings, fleets, and research | 
|oldplace1,2,3|INT,INT,INT|old place for buildings, fleet, research. | 
|scoredate|INT UNSIGNED|Time of saving old statistics, time()|
|XXX|INT DEFAULT 0|Research level XXX |
|flags|INT UNSIGNED|User flags. The full list is below (USER_FLAG). I didn't think of this idea right away, some variables can also be made into flags|
|feedid|CHAR(32)| feed id (eg 5aa28084f43ad54d9c8f7dd92f774d03) |
|lastfeed|INT UNSIGNED | last Feed update timestamp ()|
|com_until|INT UNSIGNED | Officer expires: Commander timestamp ()|
|adm_until|INT UNSIGNED | Officer expires: Admiral timestamp ()|
|eng_until|INT UNSIGNED | Officer expires: Engineer timestamp ()|
|geo_until|INT UNSIGNED | Officer expires: Geologist timestamp ()|
|tec_until|INT UNSIGNED | Officer expires: Technocrat timestamp ()|

**Q** - task in the task queue is used to process this event.

```php
// Flag mask (flags property)
const USER_FLAG_SHOW_ESPIONAGE_BUTTON = 0x1;    // 1: Display the "Espionage" icon in the galaxy
const USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON = 0x2;       // 1: Display the "Write message" icon in the galaxy
const USER_FLAG_SHOW_BUDDY_BUTTON = 0x4;        // 1: Display the "Offer to become a buddy" icon in the galaxy
const USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON = 0x8;    // 1: Display the "Missile Attack" icon in the galaxy
const USER_FLAG_SHOW_VIEW_REPORT_BUTTON = 0x10;     // 1: Display the "View Message" icon in the galaxy
const USER_FLAG_DONT_USE_FOLDERS = 0x20;        // 1: Do not sort messages into folders in Commander mode
const USER_FLAG_PARTIAL_REPORTS = 0x40;         // 1: Show spy reports partially
const USER_FLAG_FOLDER_ESPIONAGE = 0x100;           // Message Filter. 1: Show spy reports (pm=1)
const USER_FLAG_FOLDER_COMBAT = 0x200;              // Message Filter. 1: Show battle reports & missile attacks (pm=2)
const USER_FLAG_FOLDER_EXPEDITION = 0x400;          // Message Filter. 1: Show expedition results (pm=3)
const USER_FLAG_FOLDER_ALLIANCE = 0x800;            // Message Filter. 1: Show alliance messages (pm=4)
const USER_FLAG_FOLDER_PLAYER = 0x1000;             // Message Filter. 1: Show private messages (pm=0)
const USER_FLAG_FOLDER_OTHER = 0x2000;              // Message Filter. 1: Show all other messages (pm=5)
const USER_FLAG_HIDE_GO_EMAIL = 0x4000;                 // Show an in-game message icon instead of the operator's email (not all operators may like to publish their email)
const USER_FLAG_FEED_ENABLE = 0x8000;               // 1: feed enabled
const USER_FLAG_FEED_ATOM = 0x10000;                // 0 - use RSS format, 1 - use Atom format
```

## Planets (planets)

|Column|Type|Description|
|---|---|---|
|planet_id|INT AUTO_INCREMENT PRIMARY KEY|Ordinal number, starts with 10000 | 
|name|CHAR(20)|Planet name | 
|type|INT|planet type, 0 - moon, 1 - planet, 10000 - debris field, 10001 - destroyed planet, 10002 - phantom to colonize, 10003 - destroyed moon, 10004 - abandoned colony, 20000 - farspace | 
|g|INT|coordinates where the planet is located (Galaxy) | 
|s|INT|coordinates where the planet is located (System) | 
|p|INT|coordinates where the planet is located (Position) | 
|owner_id|INT|User-owner ordinal number | 
|diameter|INT|The diameter of the planet | 
|temp|INT|Minimum temperature | 
|fields|INT|Number of developed fields | 
|maxfields|INT|Maximum number of fields | 
|date|INT UNSIGNED|Date of Creation | 
|BBB|INT DEFAULT 0|Building level BBB | 
|DDD|INT DEFAULT 0|Number of defenses DDD| 
|FFF|INT DEFAULT 0|Number of fleets of each type FFF | 
|700|DOUBLE|Metal | 
|701|DOUBLE|crystal | 
|702|DOUBLE|deuterium | 
|prod1|DOUBLE|Percentage of metal mine production (0...1)| 
|prod2|DOUBLE|Percentage of crystal mine production (0...1)| 
|prod3|DOUBLE|Percentage of deuterium mine production (0...1)| 
|prod4|DOUBLE|Percentage of output of the solar power plant (0...1)| 
|prod12|DOUBLE|Percentage of thermonuclear production (0...1)| 
|prod212|DOUBLE|Percentage of solar satellite generation (0...1)| 
|lastpeek|INT UNSIGNED|Time of last planetary status update, time() |
|lastakt|INT UNSIGNED|Time of last activity, time()| 
|gate_until|INT UNSIGNED|Gate cooling time, time()| 
|remove|INT UNSIGNED|Planet deletion time (0 - do not delete), time()|

## Alliances (ally)

|Column|Type|Description|
|---|---|---|
|ally_id|INT AUTO_INCREMENT PRIMARY KEY|Alliance ordinal number | 
|tag|TEXT|Alliance tag, 3-8 characters | 
|name|TEXT|Alliance name, 3-30 characters | 
|owner_id|INT|founder ID | 
|homepage|TEXT|Home page URL | 
|imglogo|TEXT|URL of logo picture | 
|open|INT|0 - applications are forbidden (alliance recruitment is closed), 1 - applications are allowed. | 
|insertapp|INT| | 
|exttext|TEXT|External text | 
|inttext|TEXT|Internal text | 
|apptext|TEXT|Application text | 
|nextrank|INT|Order number of the next rank | 
|old_tag|TEXT| | 
|old_name|TEXT| | 
|tag_until|INT UNSIGNED| | 
|name_until|INT UNSIGNED| |
|score1,2,3|BIGINT UNSIGNED,INT UNSIGNED,INT UNSIGNED| | 
|place1,2,3|INT,INT,INT| | 
|oldscore1,2,3|BIGINT UNSIGNED,INT UNSIGNED,INT UNSIGNED| | 
|oldplace1,2,3|INT,INT,INT| | 
|scoredate|INT UNSIGNED| |

## Alliance ranks (allyranks)

|Column|Type|Description|
|---|---|---|
|rank_id|INT|Rank number | 
|ally_id|INT|ID of the alliance to which the rank is assigned | 
|name|TEXT|Rank name | 
|rights|INT|Permissions (OR mask) |

## Alliance applications (allyapps)

|Column|Type|Description|
|---|---|---|
|app_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|ally_id|INT| | 
|player_id|INT| | 
|text|TEXT| | 
|date|INT UNSIGNED| |

## Buddies (buddy)

|Column|Type|Description|
|---|---|---|
|buddy_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|request_from|INT| | 
|request_to|INT| | 
|text|TEXT| | 
|accepted|INT| |

## Messages (messages)

|Column|Type|Description|
|---|---|---|
|msg_id|INT AUTO_INCREMENT PRIMARY KEY|Message number| 
|owner_id|INT|User ID to which the message belongs|
|pm|INT|Message type, 0: private message (you can report to the operator), ... see below|
|msgfrom|TEXT|From whom, HTML|
|subj|TEXT|Subject, HTML, could be text, could be a link to the report|
|text|TEXT|Message text, HTML|
|shown|INT|0 is a new message, 1 is a displayed message.|
|date|INT UNSIGNED|Date of message|
|planet_id|INT|The ordinal number of the planet/moon. Used for espionage reports to display shared espionage reports in the galaxy|

Message types (pm):
- 0: private message
- 1: spy report
- 2: battle report link
- 3: expedition report
- 4: alliance
- 5: misc
- 6: battle report text

## Notes (notes)

|Column|Type|Description|
|---|---|---|
|note_id|INT AUTO_INCREMENT PRIMARY KEY|Note sequential number | 
|owner_id|INT|user ID | 
|subj|TEXT|Subject of the note | 
|text|TEXT|Text of the note | 
|textsize|INT|Note text size | 
|prio|INT|Priority (0: Not important (green), 1: So-so (yellow), 2: Important (red) ) | 
|date|INT UNSIGNED|Date the note was created/edited |

## Errors (errors)

|Column|Type|Description|
|---|---|---|
|error_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|ip|TEXT| | 
|agent|TEXT| | 
|url|TEXT| | 
|text|TEXT| | 
|date|INT UNSIGNED| |

## User reports on swearing (reports)

|Column|Type|Description|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT|Message owner (the one who reports)| 
|msg_id|INT|Original message ID |
|msgfrom|TEXT|From, HTML -- copied from original message |
|subj|TEXT|Subject, HTML, may be text, may be a link to the report -- copied from the original message |
|text|TEXT|Message text, HTML -- copied from the original message |
|date|INT UNSIGNED|Message date -- copied from original message |

## Debug messages (debug)

|Column|Type|Description|
|---|---|---|
|error_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|ip|TEXT| | 
|agent|TEXT| | 
|url|TEXT| | 
|text|TEXT| | 
|date|INT UNSIGNED| |

## Browse History (browse)

|Column|Type|Description|
|---|---|---|
|log_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|url|TEXT| | 
|method|TEXT| | 
|getdata|TEXT| | 
|postdata|TEXT| | 
|date|INT UNSIGNED| |

## Event queue (queue)

|Column|Type|Description|
|---|---|---|
|task_id|INT AUTO_INCREMENT PRIMARY KEY|unique task number | 
|owner_id|INT|user number to which the task belongs | 
|type|CHAR(20)|task type, each type has its own handler| 
|sub_id|INT|additional number, different for each type of task, e.g. for building - planet ID, for fleet task - fleet ID | 
|obj_id|INT|additional number, different for each type of task, e.g. for building - building ID | 
|level|INT|build level / number of units ordered at the shipyard | 
|start|INT UNSIGNED|task start time | 
|end|INT UNSIGNED|task end time | 
|prio|INT|event priority, used for events that end at the same time, the higher the priority, the earlier the event will be executed. |

## Queue of buildings (buildqueue)

|Column|Type|Description|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY|Sequential number, starts with 1 | 
|owner_id|INT|user ID | 
|planet_id|INT|planet ID | 
|list_id|INT|sequence number within the queue | 
|tech_id|INT|building ID | 
|level|INT|target level | 
|destroy|INT|1 - demolish, 0 - build | 
|start|INT UNSIGNED|construction start-up time | 
|end|INT UNSIGNED|construction completion time |

## Fleet (fleet)

|Column|Type|Description|
|---|---|---|
|fleet_id|INT AUTO_INCREMENT PRIMARY KEY|Order number of the fleet in the table | 
|owner_id|INT|Owner's ID of the user to whom the fleet belongs | 
|union_id|INT|The number of the alliance in which the fleet is flying | 
|700,701,702|DOUBLE,DOUBLE,DOUBLE|Cargo to be transported (metal/crystal/deuterium) | 
|fuel|INT|Loaded fuel for flight (deuterium) | 
|mission|INT|Fleet task type | 
|start_planet|INT|Start | 
|target_planet|INT|Target | 
|flight_time|INT|One-way flight time in seconds | 
|deploy_time|INT|Fleet holding time in seconds |
|ipm_amount|INT DEFAULT 0|Number of interplanetary missiles | 
|ipm_target|INT DEFAULT 0|target id for interplanetary missiles, 0 - all | 
|XXX|INT DEFAULT 0|number of ships of each type | 

## ACS (union)

|Column|Type|Description|
|---|---|---|
|union_id|INT AUTO_INCREMENT PRIMARY KEY|Union ID | 
|fleet_id|INT|ID of the ACS's lead fleet (initial Attack) | 
|target_player|INT| | 
|name|CHAR(20)|union name. default: "KV" + number | 
|players|TEXT|IDs of invited players, separated by commas |

## Data for the battle engine (battledata)

:warning: deprecated.

|Column|Type|Description|
|---|---|---|
|battle_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|source|TEXT| | 
|title' => 'TEXT| | 
|report' => 'TEXT| | 
|date|INT UNSIGNED| |

## Flight logs (fleetlogs)

|Column|Type|Description|
|---|---|---|
|log_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|target_id|INT| | 
|union_id|INT| | 
|p700|DOUBLE| | 
|p701|DOUBLE| | 
|p702|DOUBLE| | 
|700|DOUBLE| | 
|701|DOUBLE| | 
|702|DOUBLE| | 
|fuel|INT| | 
|mission|INT| | 
|flight_time|INT| | 
|deploy_time|INT| | 
|start|INT UNSIGNED| | 
|end|INT UNSIGNED| |
|origin_g|INT| | 
|origin_s|INT| | 
|origin_p|INT| | 
|origin_type|INT| | 
|target_g|INT| | 
|target_s|INT| | 
|target_p|INT| | 
|target_type|INT| | 
|ipm_amount|INT DEFAULT 0| | 
|ipm_target|INT DEFAULT 0| | 
|XXX|INT DEFAULT 0| | 

## IP Logs (iplogs)

|Column|Type|Description|
|---|---|---|
|log_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|ip|CHAR(16)| | 
|user_id|INT| | 
|reg|INT| | 
|date|INT UNSIGNED| |

## Pillar of Shame (pranger)

|Column|Type|Description|
|---|---|---|
|ban_id|INT AUTO_INCREMENT PRIMARY KEY| | 
|admin_name|CHAR(20)| | 
|user_name|CHAR(20)| | 
|admin_id|INT| | 
|user_id|INT| | 
|ban_when|INT UNSIGNED| | 
|ban_until|INT UNSIGNED| | 
|reason|TEXT| |

## Expedition settings (exptab)

|Column|Type|Description|
|---|---|---|
|chance_success|INT| | 
|depleted_min|INT| | 
|depleted_med|INT| | 
|depleted_max|INT| | 
|chance_depleted_min|INT| | 
|chance_depleted_med|INT| | 
|chance_depleted_max|INT| |
|chance_alien|INT| | 
|chance_pirates|INT| | 
|chance_dm|INT| | 
|chance_lost|INT| | 
|chance_delay|INT| | 
|chance_accel|INT| | 
|chance_res|INT| | 
|chance_fleet|INT| |

## Standard fleets (template)

|Column|Type|Description|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| | 
|owner_id|INT| | 
|name|CHAR(30)| | 
|date|INT UNSIGNED| |
|XXX|INT DEFAULT 0| | 

## Bot variables (botvars)

|Column|Type|Description|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| |
|owner_id|INT| |
|var|TEXT| |
|value|TEXT| |

## Logs of user and operator actions (userlogs)

|Column|Type|Description|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| |
|owner_id|INT| |
|date|INT UNSIGNED| |
|type|TEXT| |
|text|TEXT| |

## Bot strategies (botstrat)

|Column|Type|Description|
|---|---|---|
|id|INT AUTO_INCREMENT PRIMARY KEY| |
|name|TEXT| |
|source|TEXT| |
