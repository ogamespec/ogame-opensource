<?php

// Various engine definitions, previously scattered across all modules, are now in one place.

// Rank Mask.
const ARANK_DISMISS = 0x001;    // Dismiss the alliance
const ARANK_KICK = 0x002;       // Kick a player
const ARANK_R_APPLY = 0x004;    // View applications
const ARANK_R_MEMBERS = 0x008;  // View member list
const ARANK_W_APPLY = 0x010;    // Edit applications
const ARANK_W_MEMBERS = 0x020;  // Alliance management
const ARANK_ONLINE = 0x040;     // View the "online" status in the member list
const ARANK_CIRCULAR = 0x080;   // Write a circular message
const ARANK_RIGHT_HAND = 0x100; // 'Right Hand' (required to transfer founder status)

// List of fleet mission types
const FTYP_ATTACK = 1;      // Attack
const FTYP_ACS_ATTACK = 2;  // ACS Attack (slot > 0)
const FTYP_TRANSPORT = 3;   // Transport
const FTYP_DEPLOY = 4;      // Deploy
const FTYP_ACS_HOLD = 5;    // ACS Hold
const FTYP_SPY = 6;         // Spy
const FTYP_COLONIZE = 7;    // Colonize
const FTYP_RECYCLE = 8;     // Recycle
const FTYP_DESTROY = 9;     // Destroy (moon)
const FTYP_EXPEDITION = 15; // Expedition
const FTYP_MISSILE = 20;        // Missile attack (IPMs)
const FTYP_ACS_ATTACK_HEAD = 21;    // ACS Attack head (slot = 0)
const FTYP_RETURN = 100;            // Fleet returns (add this value to any mission)
const FTYP_ORBITING = 200;          // Fleet is in orbit (add this value to any mission)
const FTYP_CUSTOM = 1000;       // If >= this value, then this is some kind of custom task (modification)

// Message types (pm)
// It so happened that in the early stages of development pm=1 meant that the message was private. When it came time to make filters for the Commander, it was decided not to create a new type column in the table, but to use pm.
const MTYP_PM = 0;              // private message
const MTYP_SPY_REPORT = 1;              // spy report
const MTYP_BATTLE_REPORT_LINK = 2;      // link to battle report AND missile attack
const MTYP_EXP = 3;             // expedition report
const MTYP_ALLY = 4;            // alliance
const MTYP_MISC = 5;            // miscellaneous
const MTYP_BATTLE_REPORT_TEXT = 6;      // battle report text

// Types of galactic objects (planets, moons, etc.)
// In addition to planet types for the database, there are also so-called "game planet types", such as those used for the Empire page (see GetPlanetType method).
const PTYP_MOON = 0;        // moon
const PTYP_PLANET = 1;      // planet; In the early stages of development for planets were reserved types for each picture (ice, desert, etc.). But after the algorithm of getting a picture from ID was cracked, there was no need in this.
const PTYP_DF = 10000;          // debris field
const PTYP_DEST_PLANET = 10001;         // destroyed planet (deleted by the player)
const PTYP_COLONY_PHANTOM = 10002;      // colonization phantom (exists for the duration of the Colonize mission)
const PTYP_DEST_MOON = 10003;           // destroyed moon (deleted by the player)
const PTYP_ABANDONED = 10004;           // abandoned colony (instead of the buggy "overlib" that was in the vanilla version)
const PTYP_FARSPACE = 20000;        // infinite distances (for expeditions)
const PTYP_CUSTOM = 20001;          // All values >= are considered custom galaxy objects added by mods

// Queue task type
// For some reason during the development phase, the identifiers were made strings. TODO: Change them to INT type (but this would require a clean reinstall of the Universe)
const QTYP_UNBAN = "UnbanPlayer";               // unban player
const QTYP_CHANGE_EMAIL = "ChangeEmail";        // write down a permanent mailing address
const QTYP_ALLOW_NAME = "AllowName";            // allow player name changes
const QTYP_ALLOW_ATTACKS = "AllowAttacks";      // unban player's attack ban
const QTYP_UNLOAD_ALL = "UnloadAll";            // re-login all players
const QTYP_CLEAN_DEBRIS = "CleanDebris";        // virtual debris field cleanup
const QTYP_CLEAN_PLANETS = "CleanPlanets";      // removal of destroyed planets / abandoned moons
const QTYP_CLEAN_PLAYERS = "CleanPlayers";      // deleting inactive players and players put up for deletion (1:10 server time)
const QTYP_UPDATE_STATS = "UpdateStats";        // saving old stat points
const QTYP_RECALC_POINTS = "RecalcPoints";      // recalculation of player statistics
const QTYP_RECALC_ALLY_POINTS = "RecalcAllyPoints";  // recalculation of alliance statistics
const QTYP_BUILD = "Build";                     // completion of building on the planet (sub_id - task ID in the build queue, obj_id - type of building)
const QTYP_DEMOLISH = "Demolish";               // completion of demolition on the planet (sub_id - task ID in the build queue, obj_id - type of building)
const QTYP_RESEARCH = "Research";               // research (sub_id - number of the planet where the research was launched, obj_id - type of research)
const QTYP_SHIPYARD = "Shipyard";               // shipyard task (sub_id - planet number, obj_id - construction type)
const QTYP_FLEET = "Fleet";                     // Fleet task / IPM attack (sub_id - number of record in the fleet table)
const QTYP_DEBUG = "Debug";                     // debug event
const QTYP_AI = "AI";                           // tasks for bot (sub_id - strategy number, obj_id - current block number)
const QTYP_COUPON = "Coupon";                   // Coupon crediting (the handler is located in coupon.php)

// Queue task priorities
const QUEUE_PRIO_LOWEST = 0;            // Consider it no priority
const QUEUE_PRIO_DEBUG = 9999;          // Debug event priority (AddDebugEvent)
const QUEUE_PRIO_BUILD = 20;            // Priority for buildings and construction queue
const QUEUE_PRIO_FLEET = 200;       // Priority of fleet missions. The mission type is added to this value (see FTYP_)
const QUEUE_PRIO_RECALC_ALLY_POINTS = 400;
const QUEUE_PRIO_RECALC_POINTS = 500;
const QUEUE_PRIO_UPDATE_STATS = 510;
const QUEUE_PRIO_COUPON = 520;
const QUEUE_PRIO_CLEAN_DEBRIS = 600;
const QUEUE_PRIO_CLEAN_PLANETS = 700;
const QUEUE_PRIO_RELOGIN = 777;
const QUEUE_PRIO_CLEAN_PLAYERS = 900;
const QUEUE_PRIO_BOT = 1000;

// Flag mask (flags property)
const USER_FLAG_SHOW_ESPIONAGE_BUTTON = 0x1;    // 1: Display the "Espionage" icon" in the galaxy
const USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON = 0x2;       // 1: Display the "Write message" icon in the galaxy
const USER_FLAG_SHOW_BUDDY_BUTTON = 0x4;        // 1: Display the "Buddy request" icon in the galaxy
const USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON = 0x8;    // 1: Display the "Missile Attack" icon in the galaxy
const USER_FLAG_SHOW_VIEW_REPORT_BUTTON = 0x10;     // 1: Display the "View Message" icon in the galaxy
const USER_FLAG_DONT_USE_FOLDERS = 0x20;        // 1: Do not sort messages into folders in Commander mode
const USER_FLAG_PARTIAL_REPORTS = 0x40;         // 1: Show partial spy report
const USER_FLAG_FOLDER_ESPIONAGE = 0x100;           // Message Filter. 1: Show spy reports (pm=1)
const USER_FLAG_FOLDER_COMBAT = 0x200;              // Message Filter. 1: Show battle reports & missile attacks (pm=2)
const USER_FLAG_FOLDER_EXPEDITION = 0x400;          // Message Filter. 1: Show expedition results (pm=3)
const USER_FLAG_FOLDER_ALLIANCE = 0x800;            // Message Filter. 1: Show alliance messages (pm=4)
const USER_FLAG_FOLDER_PLAYER = 0x1000;             // Message Filter. 1: Show private messages (pm=0)
const USER_FLAG_FOLDER_OTHER = 0x2000;              // Message Filter. 1: Show all other messages (pm=5)
const USER_FLAG_HIDE_GO_EMAIL = 0x4000;                 // Show an in-game message icon instead of the operator's email (not all operators may like to publish their email)
const USER_FLAG_FEED_ENABLE = 0x8000;               // 1: feed enabled
const USER_FLAG_FEED_ATOM = 0x10000;                // 0 - use RSS format, 1 - use Atom format

const USER_OFFICER_COMMANDER = 1;
const USER_OFFICER_ADMIRAL = 2;
const USER_OFFICER_ENGINEER = 3;
const USER_OFFICER_GEOLOGE = 4;
const USER_OFFICER_TECHNOCRATE = 5;

// Default flags after creating a player
const USER_FLAG_DEFAULT = USER_FLAG_SHOW_ESPIONAGE_BUTTON | USER_FLAG_SHOW_WRITE_MESSAGE_BUTTON | USER_FLAG_SHOW_BUDDY_BUTTON | USER_FLAG_SHOW_ROCKET_ATTACK_BUTTON | USER_FLAG_SHOW_VIEW_REPORT_BUTTON;

const USER_LEGOR = 1;
const USER_SPACE = 99999;           // A technical account that owns global events as well as "nobody's" galaxy objects

const USER_NOOB_LIMIT = 5000;           // Number of points for a newbie

// for TechDuration method ($const_factor)
const PROD_BUILDING_DURATION_FACTOR = 2500;
const PROD_SHIPYARD_DURATION_FACTOR = 2500;
const PROD_RESEARCH_DURATION_FACTOR = 1000;

const GALAXY_DEUTERIUM_CONS = 10;           // Deuterium consumption for viewing the Galaxy
const GALAXY_PHANTOM_DEBRIS = 300;          // If the total resource value is < the specified value, then the debris field is not visible in the Galaxy.

const TRADER_DM = 2500;             // Cost of calling a Merchant

const MAX_PLANET = 9;           // Maximum number of planets a player can own (home + colonies), not greater (<= this value)

const RF_MAX = 2000;            // Maximum rapidfire value (if > this value, then error)
const RF_DICE = 10000;          // Number of dice faces for a rapid-fire throw (1d`RF_DICE)

?>