#pragma once

#define MAX_UNIT_TYPES 256          // Maximum number of unique game object IDs for flattened arrays
#define GID_MAX 0xFFFF              // The game object ID cannot be greater than this value (does not fit in uint16_t)
#define RF_MAX 5000                 // Maximum rapidfire value (if > this value, then error)
#define RF_DICE 100000              // Number of dice faces for a rapid-fire throw (1d`RF_DICE)

typedef struct _TechParam {
    int     structure;
    int     shield;
    int     attack;
    int     cargo;
    int     speed;
    int     consumption;
} TechParam;

typedef struct _UnitCount
{
    uint16_t gid;           // Game object ID (fleet, defense, ...)
    uint32_t count;         // Quantity
} UnitCount;

// Slot data
typedef struct _Slot
{
    UnitCount*  unit;           // Slot units (ID + number of units)
    int         unit_count;     // Number of records in `unit` (NOT the number of units, but the number of unit TYPES)
    float       weap, shld, armor;      // Tech
} Slot;

#pragma pack(push, 1)

// Unit data
typedef struct _Unit {
    uint8_t     slot_id;        // Parent slot number. To access the parent slot, a pointer to the slot array is also required (passed through parameters)
    uint16_t    obj_type;       // Object Type.
    uint8_t     exploded;       // 1: The unit was blown up during a firefight
    int32_t     hull;           // Remaining armor. The maximum value is in hullmax in the parent slot
    int32_t     shield;         // The current value of shields. The maximum value is in shieldmax in the parent slot
} Unit;

typedef struct _RFTab {
    int count;              // number of targets for rapid fire (array `to`)
    UnitCount* to;          // Dynamically allocated array for rapid-fire purposes
} RFTab;

#pragma pack(pop)

extern TechParam UnitParam[MAX_UNIT_TYPES];
extern RFTab RF[MAX_UNIT_TYPES];

// Battle Engine Errors.
// Little attention has been paid to them in the past, you need to screw up various checks, it's a key and crucial part of the game.
enum {
    BATTLE_ERROR_INSUFFICIENT_RESOURCES = -1,               // Not enough memory
    BATTLE_ERROR_NOT_ENOUGH_CMD_LINE_PARAMS = -1000,        // Missing command line parameters
    BATTLE_ERROR_NOT_ENOUGH_ATTACKERS_OR_DEFENDERS = -2000,     // No attackers or defenders
    BATTLE_ERROR_INVALID_BATTLE_ID = -3000,                 // Invalid battle ID
    BATTLE_ERROR_DATA_LOAD = -4000,                         // Error loading input data
    BATTLE_ERROR_DATA_SAVE = -5000,                         // Error saving output data
    BATTLE_ERROR_RESULT_BUFFER_OVERFLOW = -6000,            // Output data accumulation buffer overflow
    BATTLE_ERROR_GID_TYPES_OVERFLOW = -7000,            // The number of unique game object IDs has exceeded the limit (> MAX_UNIT_TYPES)
    BATTLE_ERROR_GID_MAX = -7001,                       // Some unit ID is greater than the max value (does not fit into the data type)
    BATTLE_ERROR_GID_UNKNOWN = -7002,                   // The rapid-fire slots or parameters contain a unit ID for which there are no parameters in the UnitParam table
    BATTLE_ERROR_MISSING_UNIT_PARAM = -8000,            // There is no table with unit parameters
    BATTLE_ERROR_MISSING_RF_TAB = -8001,                // There is no table with the rapid-fire settings 
    BATTLE_ERROR_PARSE_UNIT_PARAM_NOT_ENOUGH = -9000,       // not enough values to parse parameters
    BATTLE_ERROR_PARSE_UNIT_PARAM_NOT_ALIGNED = -9001,      // the value is not a multiple of the number of parameters
    BATTLE_ERROR_PARSE_UNIT_PARAM_DUPLICATED = -9002,       // ID duplication
    BATTLE_ERROR_PARSE_SLOT_NOT_ENOUGH = -10000,            // not enough data for the slot
    BATTLE_ERROR_PARSE_SLOT_NOT_ALIGNED = -10001,           // misaligned values in the slot
    BATTLE_ERROR_PARSE_RF_NOT_ALIGNED = -11000,         // the rapid fire data is not aligned
    BATTLE_ERROR_PARSE_RF_NOT_ENOUGH = -11001,          // insufficient data to complete another rapid-fire entry
    BATTLE_ERROR_PARSE_RF_MALFORMED = -11002,           // The number of targets for rapid fire is greater than the number of known game objects (ID); the rapid fire value is > RF_MAX
};