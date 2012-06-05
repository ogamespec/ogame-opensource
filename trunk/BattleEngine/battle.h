// Длинное целое.
#ifndef WIN32
#include <linux/types.h>
typedef __u64 u64;
#else
#include <windows.h>
typedef unsigned __int64 u64;
#endif

typedef struct TechParam {
    long    structure;
    long    shield;
    long    attack;
    long    cargo;  // только для флота.
} TechParam;

// Данные слота.
typedef struct Slot
{
    unsigned    long fleet[14];         // Флот
    unsigned    long def[8];            // Оборона
    int         weap, shld, armor;      // Технологии
    char        name[64];               // Имя игрока
    int         g, s, p;                // Координаты
    int         id;                     // ID
} Slot;

// Данные юнита.
typedef struct Unit {
    unsigned char slot_id;
    unsigned char obj_type;
    unsigned char exploded;
    unsigned char dummy;                // Для выравнивания структуры на 4 байта.
    long    hull, hullmax;
    long    shield, shieldmax;
} Unit;

extern TechParam fleetParam[14];
extern TechParam defenseParam[8];
