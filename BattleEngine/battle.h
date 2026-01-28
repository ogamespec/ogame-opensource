#pragma once

#define MAX_UNIT_TYPES 256          // Максимальное количество уникальных ID игровых объектов для flatten массивов
#define GID_MAX 0xFFFF              // ID игрового объекта не может быть больше этого значения (не умещается в uint16_t)

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
    uint16_t gid;           // ID игрового объекта (флот, оборона, ...)
    uint32_t count;         // Количество
} UnitCount;

// Slot data
typedef struct _Slot
{
    UnitCount*  unit;           // Юниты слота (ID + количество юнитов)
    int         unit_count;     // Количество записей в unit
    float       weap, shld, armor;      // Tech
    int         id;                     // ID флота для ассоциативной привязки к слоту
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
    UnitCount from;             // ID юнита который производит скорострел и количество целей для него
    UnitCount* to;              // Динамически аллоцируемый массив для целей скорострела
} RFTab;

#pragma pack(pop)

extern TechParam UnitParam[MAX_UNIT_TYPES];
extern RFTab RF;

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
    BATTLE_ERROR_GID_TYPES_OVERFLOW = -7000,            // Количество уникальных ID игровых объектов превысило лимит (> MAX_UNIT_TYPES)
    BATTLE_ERROR_GID_MAX = -7001,                       // Какой-то ID юнита больше макс. значения (не умещается в тип данных)
    BATTLE_ERROR_GID_UNKNOWN = -7002,                   // в слотах есть ID юнита, для которого отстутствуют параметры в таблице UnitParam
    BATTLE_ERROR_MISSING_UNIT_PARAM = -8000,            // отстутсвует таблица с параметрами юнитов
    BATTLE_ERROR_MISSING_RF_TAB = -8001,                // отсутствует таблица с настройками скорострела 
    BATTLE_ERROR_PARSE_UNIT_PARAM_NOT_ENOUGH = -9000,       // не хватает значений для парсинга параметров
    BATTLE_ERROR_PARSE_UNIT_PARAM_NOT_ALIGNED = -9001,      // значение не кратно количеству параметров
    BATTLE_ERROR_PARSE_UNIT_PARAM_DUPLICATED = -9002,       // дублирование ID
    BATTLE_ERROR_PARSE_SLOT_NOT_ENOUGH = -10000,            // не хватает данных для слота
    BATTLE_ERROR_PARSE_SLOT_NOT_ALIGNED = -10001,           // не выровненные значения в слоте
};