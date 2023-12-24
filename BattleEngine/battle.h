
typedef struct TechParam {
    long    structure;
    long    shield;
    long    attack;
    long    cargo;  // только для флота / for the fleet only
} TechParam;

// Данные слота / Slot data
typedef struct Slot
{
    unsigned    long fleet[14];         // Флот / Fleet
    unsigned    long def[8];            // Оборона / Defense
    int         weap, shld, armor;      // Технологии / Tech
    char        name[64];               // Имя игрока / Player Name
    int         g, s, p;                // Координаты / Coordinates
    int         id;                     // ID
} Slot;

// Данные юнита / Unit data
typedef struct Unit {
    unsigned char slot_id;
    unsigned char obj_type;
    unsigned char exploded;
    unsigned char dummy;                // Для выравнивания структуры на 4 байта / To align the structure to 4 bytes
    long    hull, hullmax;
    long    shield, shieldmax;
} Unit;

extern TechParam fleetParam[14];
extern TechParam defenseParam[8];
