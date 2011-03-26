// Длинное целое.
#ifndef WIN32
#include <linux/types.h>
typedef __u64 u64;
#else
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
    int         g, s, p;                // Координаты
    unsigned    char name[128];         // Имя игрока
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

// Данные по раунду.
typedef struct RoundInfo {
    Unit        *aunits, *dunits;       // Данные юнитов на конец раунда
    int         aunum, dunum;
    u64         shoots[2], spower[2], absorbed[2]; // Общая статистика по выстрелам.    
    unsigned    long memload;
} RoundInfo;

extern TechParam fleetParam[14];
extern TechParam defenseParam[8];
