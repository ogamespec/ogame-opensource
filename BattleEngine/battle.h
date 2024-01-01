
// Для того чтобы номера объектов умещались в один байт (для экономии памяти), нумерация флота начинается от 100 (вместо 202), а обороны от 200 (вместо 401).
// To make the object numbers fit into one byte (to save memory), fleet numbering starts at 100 (instead of 202) and defense numbering starts at 200 (instead of 401).
#define FLEET_ID_BASE 100
#define DEFENSE_ID_BASE 200

typedef struct _TechParam {
    long    structure;
    long    shield;
    long    attack;
    long    cargo;  // только для флота / for the fleet only
} TechParam;

typedef struct _UnitPrice {
    long m, k, d;
} UnitPrice;

// Данные слота / Slot data
typedef struct _Slot
{
    unsigned    long fleet[14];         // Флот / Fleet
    unsigned    long def[8];            // Оборона / Defense
    int         weap, shld, armor;      // Технологии / Tech
    char        name[64];               // Имя игрока / Player Name
    int         g, s, p;                // Координаты / Coordinates
    int         id;                     // ID
} Slot;

// Данные юнита / Unit data
typedef struct _Unit {
    unsigned char slot_id;
    unsigned char obj_type;
    unsigned char exploded;
    unsigned char dummy;                // Для выравнивания структуры на 4 байта / To align the structure to 4 bytes
    long    hull, hullmax;
    long    shield, shieldmax;
} Unit;

extern TechParam fleetParam[14];
extern TechParam defenseParam[8];

typedef struct _SimParam {
    char    name[32];
    char    string[64];
    unsigned long value;
} SimParam;

// Ошибки боевого движка.
// Раньше им мало внимания уделялось, нужно накрутить проверок различных, таки это ключевая и важнейшая часть игры.
enum {
    BATTLE_ERROR_INSUFFICIENT_RESOURCES = -1,               // Недостаточно памяти
    BATTLE_ERROR_NOT_ENOUGH_CMD_LINE_PARAMS = -1000,        // Не хватает параметров командной строки
    BATTLE_ERROR_NOT_ENOUGH_ATTACKERS_OR_DEFENDERS = -2000,     // Нет атакующих или защитников
};
