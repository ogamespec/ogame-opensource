
// To make the object numbers fit into one byte (to save memory), fleet numbering starts at 100 (instead of 202) and defense numbering starts at 200 (instead of 401).
#define FLEET_ID_BASE 100
#define DEFENSE_ID_BASE 200

typedef struct _TechParam {
    long    structure;
    long    shield;
    long    attack;
    long    cargo;  // for the fleet only
} TechParam;

typedef struct _UnitPrice {
    long m, k, d;
} UnitPrice;

// Slot data
typedef struct _Slot
{
    unsigned    long fleet[14];         // Fleet
    unsigned    long def[8];            // Defense
    int         weap, shld, armor;      // Tech
    char        name[64];               // Player Name
    int         g, s, p;                // Coordinates
    int         id;                     // ID
    // Pre-calculated armor, shield max, and attack power parameters for fleet and defense of each type.
    // Storing pre-calculated parameters will speed up the overall calculation a bit. Also previously hullmax and shieldmax values were inside the Unit structure and created additional memory load.
    long        hullmax_fleet[14];
    long        hullmax_def[8];
    long        shieldmax_fleet[14];
    long        shieldmax_def[8];
    long        apower_fleet[14];
    long        apower_def[8];
} Slot;

#pragma pack(push, 1)

// Unit data
typedef struct _Unit {
    unsigned char slot_id;              // Parent slot number. To access the parent slot, a pointer to the slot array is also required (passed through parameters)
    unsigned char obj_type;             // Object Type. Fleet starts with FLEET_ID_BASE, defense starts with DEFENSE_ID_BASE.
    unsigned char exploded;             // 1: The unit was blown up during a firefight
    unsigned char dummy;                // To align the structure to 4 bytes
    long    hull;               // Remaining armor. The maximum value is in hullmax in the parent slot
    long    shield;             // The current value of shields. The maximum value is in shieldmax in the parent slot
} Unit;

#pragma pack(pop)

extern TechParam fleetParam[14];
extern TechParam defenseParam[8];

typedef struct _SimParam {
    char    name[32];
    char    string[64];
    unsigned long value;
} SimParam;

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
};
