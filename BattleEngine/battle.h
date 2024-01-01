
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
    // Заранее рассчитанные параметры брони, максимального значения щитов и силы атаки для флота и обороны каждого типа.
    // Хранение заранее рассчитанных параметров немного ускорит расчёт в целом. Также раньше значения hullmax и shieldmax были внутри структуры Unit и создавали доп. нагрузку на память.
    long        hullmax_fleet[14];
    long        hullmax_def[8];
    long        shieldmax_fleet[14];
    long        shieldmax_def[8];
    long        apower_fleet[14];
    long        apower_def[8];
} Slot;

#pragma pack(push, 1)

// Данные юнита / Unit data
typedef struct _Unit {
    unsigned char slot_id;              // Номер родительского слота. Для доступа к родительскому слоту также требуется указатель на массив слотов (передаётся через параметры)
    unsigned char obj_type;             // Тип объекта. Флот начинается с FLEET_ID_BASE, оборона начинается с DEFENSE_ID_BASE.
    unsigned char exploded;             // 1: В процессе перестрелки юнит был взорван
    unsigned char dummy;                // Для выравнивания структуры на 4 байта / To align the structure to 4 bytes
    long    hull;               // Остаток брони. Максимальное значение находится в hullmax в родительском слоте
    long    shield;             // Текущее значение щитов. Максимальное значение находится в shieldmax в родительском слоте
} Unit;

#pragma pack(pop)

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
    BATTLE_ERROR_INVALID_BATTLE_ID = -3000,                 // Неверный ID битвы
    BATTLE_ERROR_DATA_LOAD = -4000,                         // Ошибка загрузки входных данных
    BATTLE_ERROR_DATA_SAVE = -5000,                         // Ошибка сохранения выходных данных
    BATTLE_ERROR_RESULT_BUFFER_OVERFLOW = -6000,            // Переполнение буфера для накопления выходных данных
};
