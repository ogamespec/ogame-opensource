// The battle engine of the browser game OGame

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdint.h>
#include <time.h>
#include <math.h>
#include "battle.h"
#include "file.h"
#include "rand.h"

/*
Output data format
The output is in the format for the PHP function unserialize().

Format (after unserialize() transformation):

Array (
   'battle_seed' => Initial seed for RNG
   'peak_allocated' => How much memory did it consume during the peak
   'result' => 'awon' (The attacker won), 'dwon' (The defender won), 'draw' (Draw)

   'before' => Array (  // The fleets before the battle
            'attackers' => Array (    // attacker slots
                  [0] => Array ( weap' => 10.0, 'shld' => 11.0, 'armr' => 12.0, 'units' => Array (202=>5, 203=>6, ...) ),   // units
                  [1] => Array ( ... )
            )

            'defenders' => Array (    // defenders' slots
                  [0] => Array ( weap' => 10.0, 'shld' => 11.0, 'armr' => 12.0, 'units' => Array (202=>5, 203=>6, ..., 401=>5, 402=>44) ),   // units
                  [1] => Array ( ... )
            )

       ),
   )

   'rounds' => Array (  // Rounds
       [0] => Array (
            'ashoot' => Attack fleet fires: 988 round(s)
            'apower' => total power of 512.720.100
            'dabsorb' => The defender's shields absorb 43.724
            'dshoot' => The defending fleet fires 1.651 shot(s)
            'dpower' => total power of 428.728
            'aabsorb' => The attacker's shields absorb 355.453

            'attackers' => Array (    // attacker slots
                  [0] => Array ( 202=>5, 203=>6, ... ),   // units
                  [1] => Array ( ... )
            )

            'defenders' => Array (    // defenders' slots
                  [0] => Array ( 202=>5, 203=>6, ..., 401=>5, 402=>44 ),   // units
                  [1] => Array ( ... )
            )

       ),
       [1] => Array ( ... )   // next round
   )
)

*/

char ResultBuffer[64*1024];     // Output data buffer

int Rapidfire = 1;  // 1: enable rapidfire

TechParam UnitParam[MAX_UNIT_TYPES];
RFTab RF[MAX_UNIT_TYPES];

uint64_t peak_allocated_round;
uint64_t peak_allocated_all_rounds;

// Flatten arrays are used to quickly convert a game object ID to an ordinal.
// Problem: we have at the input object IDs of the type 202, 401, 12000.
// We flatten all IDs into an array: [0] = 202, [1] = 401, [2] = 12000.
// flatten_array is used to get the ordinal by ID (12000 -> 2)
// unflatten_array is used to get ID by ordinal (2 -> 12000)
// flatten_counter contains the total number of flattened IDs (3 in this example) - aka number of known IDs

uint8_t flatten_array[0x10000];
uint16_t unflatten_array[MAX_UNIT_TYPES];
int flatten_counter = 0;

uint8_t IdToOrd(uint16_t id) {
    return flatten_array[id];
}

uint16_t OrdToId(uint8_t ord) {
    return unflatten_array[ord];
}

int IsFlattened(uint16_t id) {
    for (int i = 0; i < flatten_counter; i++) {
        if (unflatten_array[i] == id) return 1;
    }
    return 0;
}

void FlattenId(uint16_t id) {
    if (IsFlattened(id)) return;
    flatten_array[id] = flatten_counter;
    unflatten_array[flatten_counter++] = id;
}

// ==========================================================================================

char** explode(const char delimiter, const char* str, int* count) {
    if (!str || !count) {
        return NULL;
    }

    // Counting the number of substrings
    *count = 1;
    for (const char* p = str; *p; p++) {
        if (*p == delimiter) {
            (*count)++;
        }
    }

    // Allocating memory for an array of pointers
    char** result = (char**)malloc(*count * sizeof(char*));
    if (!result) {
        return NULL;
    }

    // We allocate memory for all rows in one block
    size_t str_len = strlen(str);
    char* storage = (char*)malloc(str_len + *count); // +count for null characters
    if (!storage) {
        free(result);
        return NULL;
    }

    // Copy the original string to work with it
    strcpy(storage, str);

    // Split the string
    int index = 0;
    char* token = storage;

    for (size_t i = 0; i <= str_len; i++) {
        if (storage[i] == delimiter || storage[i] == '\0') {
            storage[i] = '\0'; // Replace the separator with a null character
            result[index++] = token;
            token = &storage[i + 1];
        }
    }

    return result;
}

// Freeing memory allocated by explode()
void free_explode_result(char** result) {
    if (!result) return;

    // all lines are stored in one memory block
    // which is the first element of the array
    if (result[0]) {
        free(result[0]);
    }
    free(result);
}

static char *longnumber (uint64_t n)
{
    static char retbuf [32];
    char *p = &retbuf [sizeof (retbuf) - 1];
    int i = 0;

    if (n == 0) return "0";
    *p = '\0';
    for (i = 0; n; i++)
    {
        *--p = '0' + n % 10;
        n /= 10;
    }
    return p;
}

TechParam* GetUnitParam(uint16_t id)
{
    uint8_t ord = IdToOrd(id);
    return &UnitParam[ord];
}

int32_t get_hullmax(uint16_t id, Slot* slot) {
    TechParam* techParam = GetUnitParam(id);
    return techParam->structure * 0.1f * (10 + slot->armor) / 10;
}

int32_t get_shieldmax(uint16_t id, Slot* slot) {
    TechParam* techParam = GetUnitParam(id);
    return techParam->shield * (10 + slot->shld) / 10;
}

int32_t get_apower(uint16_t id, Slot* slot) {
    TechParam* techParam = GetUnitParam(id);
    return techParam->attack * (10 + slot->weap) / 10;
}

// Allocate memory for units and set initial values
Unit *InitBattle (Slot *slot, int num, int objs)
{
    Unit *u;
    int slot_id = 0;
    int i, n, ucnt = 0;
    unsigned long obj;
    u = (Unit *)malloc (objs * sizeof(Unit));
    if (u == NULL) return u;
    peak_allocated_round += objs * sizeof(Unit);
    memset (u, 0, objs * sizeof(Unit));
    
    for (i=0; i<num; i++, slot_id++) {
        for (n=0; n<slot[i].unit_count; n++)
        {
            for (obj=0; obj<slot[i].unit[n].count; obj++) {
                u[ucnt].hull = get_hullmax (slot[i].unit[n].gid, &slot[i]);
                u[ucnt].obj_type = slot[i].unit[n].gid;
                u[ucnt].slot_id = slot_id;
                ucnt++;
            }
        }
    }

    return u;
}

// Shot a => b. Returns damage.
// absorbed - the accumulator of damage absorbed by shields (for the one who is attacked, i.e. for unit "b").
long UnitShoot (Unit *a, Slot* aslot, Unit *b, Slot* bslot, uint64_t *absorbed )
{
    float prc, depleted;
    long apower, adelta = 0, b_shieldmax, b_hullmax;
    apower = get_apower(a->obj_type, &aslot[a->slot_id]);

    if (b->exploded) return apower; // Already blown up.
    if (b->shield == 0) {  // No shields.
        if (apower >= b->hull) b->hull = 0;
        else b->hull -= apower;
    }
    else { // We take away from shields, and if there is enough damage, from armor as well.

        b_shieldmax = get_shieldmax(b->obj_type, &bslot[b->slot_id]);

        prc = (float)b_shieldmax * 0.01f;
        depleted = (float)floor ((float)apower / prc);
        if (b->shield < (depleted * prc)) {
            *absorbed += (uint64_t)b->shield;
            adelta = apower - b->shield;
            if (adelta >= b->hull) b->hull = 0;
            else b->hull -= adelta;
            b->shield = 0;
        }
        else {
            b->shield -= depleted * prc;
            *absorbed += (uint64_t)apower;
        }
    }

    b_hullmax = get_hullmax(b->obj_type, &bslot[b->slot_id]);

    if (b->hull <= b_hullmax * 0.7 && b->shield == 0) {    // Blow it up.
        if (MyRand (0, 99) >= ((b->hull * 100) / b_hullmax) || b->hull == 0) {
            b->exploded = 1;
        }
    }
    return apower;
}

// Clean up blown up ships and defenses. Returns the number of units blown up.
int WipeExploded (Unit **slot, int amount, int *exploded_count)
{
    Unit *src = *slot, *tmp;
    int i, p = 0, exploded = 0;
    tmp = (Unit *)malloc (sizeof(Unit) * amount);
    if (!tmp) {
        return BATTLE_ERROR_INSUFFICIENT_RESOURCES;
    }
    peak_allocated_round += sizeof(Unit) * amount;
    for (i=0; i<amount; i++) {
        if (!src[i].exploded) tmp[p++] = src[i];
        else exploded++;
    }
    free (src);
    *slot = tmp;
    *exploded_count = exploded;
    return 0;
}

// Check the combat for a fast draw. If none of the units have armor damage, the combat ends in a quick draw.
int CheckFastDraw (Unit *aunits, int aobjs, Slot* aslot, Unit *dunits, int dobjs, Slot* dslot)
{
    int i;
    long hullmax;
    for (i=0; i<aobjs; i++) {
        hullmax = get_hullmax(aunits[i].obj_type, &aslot[aunits[i].slot_id]);

        if (aunits[i].hull != hullmax) return 0;
    }
    for (i=0; i<dobjs; i++) {
        hullmax = get_hullmax(dunits[i].obj_type, &dslot[dunits[i].slot_id]);

        if (dunits[i].hull != hullmax) return 0;
    }
    return 1;
}

UnitCount pseudo_slot[MAX_UNIT_TYPES];

// Generate slot result.
// If techs = 1, show techs (no need to show techs in rounds).
static char * GenSlot (char * ptr, Unit *units, int slot, int objnum, Slot *a, Slot *d, int attacker, int techs)
{
    Slot *s = attacker ? a : d;
    Unit *u;
    int i, count = 0;
    unsigned long sum = 0;

    // Collect all units in a pseudo-slot
    memset(pseudo_slot, 0, sizeof(pseudo_slot));
    for (i=0; i<objnum; i++) {
        u = &units[i];
        if (u->slot_id == slot) {

            uint8_t ord = IdToOrd(u->obj_type);
            pseudo_slot[ord].count++;
            sum++;
        }
    }

    int unique_gids = 0;
    for (uint8_t ord = 0; ord < flatten_counter; ord++) {
        int gid = OrdToId(ord);
        if (pseudo_slot[ord].count) {
            unique_gids++;
        }
    }

    int array_size = unique_gids + techs * 3;
    ptr += sprintf ( ptr, "i:%i;a:%i:{", slot, array_size);

    if ( techs ) {
        ptr += sprintf (ptr, "s:4:\"weap\";d:%f;", s[slot].weap );
        ptr += sprintf (ptr, "s:4:\"shld\";d:%f;", s[slot].shld );
        ptr += sprintf (ptr, "s:4:\"armr\";d:%f;", s[slot].armor );
    }

    for (uint8_t ord = 0; ord < flatten_counter; ord++) {
        int gid = OrdToId(ord);
        if (pseudo_slot[ord].count) {
            ptr += sprintf(ptr, "i:%i;i:%i;", gid, pseudo_slot[ord].count);
        }
    }

    ptr += sprintf ( ptr, "}" );
    return ptr;
}

static int RapidFire(int atyp, int dtyp) {

    int rf = 0;

    uint8_t aord = IdToOrd(atyp);

    RFTab* rftab = &RF[aord];
    if (rftab->count) {
        for (int i = 0; i < rftab->count; i++) {
            if (rftab->to[i].gid == dtyp && rftab->to[i].count) {
                int rnd = MyRand(1, RF_DICE);
                int cmp = RF_DICE / rftab->to[i].count;
                rf = rnd > cmp ? 1 : 0;
                break;
            }
        }
    }

    return rf;
}

int DoBattle (Slot *a, int anum, Slot *d, int dnum, unsigned long battle_seed, int max_round)
{
    long slot, i, n, aobjs = 0, dobjs = 0, idx, rounds, sum = 0;
    long apower, atyp, dtyp, rapidfire, fastdraw;
    Unit *aunits, *dunits, *unit;
    char * ptr = ResultBuffer, * res, *round_patch;
    int exploded, exploded_res;

    uint64_t shoots[2] = { 0,0 }, spower[2] = { 0,0 }, absorbed[2] = { 0,0 }; // Total shot statistics.

    // Count the number of units before battle.
    for (i=0; i<anum; i++) {
        for (n = 0; n < a[i].unit_count; n++) {
            aobjs += a[i].unit[n].count;
        }
    }
    for (i=0; i<dnum; i++) {
        for (n = 0; n < d[i].unit_count; n++) {
            dobjs += d[i].unit[n].count;
        }
    }

    // Prepare an array of units to be used.
    peak_allocated_round = 0;
    aunits = InitBattle (a, anum, aobjs);
    if (aunits == NULL) {
        return BATTLE_ERROR_INSUFFICIENT_RESOURCES;
    }
    dunits = InitBattle (d, dnum, dobjs);
    if (dunits == NULL) {
        free(aunits);
        return BATTLE_ERROR_INSUFFICIENT_RESOURCES;
    }
    peak_allocated_all_rounds = peak_allocated_round;

    ptr += sprintf (ptr, "a:5:{");

    // Fleets before the battle
    ptr += sprintf (ptr, "s:6:\"before\";a:2:{");
    ptr += sprintf ( ptr, "s:9:\"attackers\";a:%i:{", anum );
    for (slot=0; slot<anum; slot++) {
        ptr = GenSlot (ptr, aunits, slot, aobjs, a, d, 1, 1);
    }
    ptr += sprintf ( ptr, "}" );
    ptr += sprintf ( ptr, "s:9:\"defenders\";a:%i:{", dnum );
    for (slot=0; slot<dnum; slot++) {
        ptr = GenSlot (ptr, dunits, slot, dobjs, a, d, 0, 1);
    }
    ptr += sprintf ( ptr, "}" );
    ptr += sprintf ( ptr, "}" );

    round_patch = ptr + 15;
    ptr += sprintf (ptr, "s:6:\"rounds\";a:XX:{");

    if ((ptr - ResultBuffer) >= sizeof(ResultBuffer)) {
        free(aunits);
        free(dunits);
        return BATTLE_ERROR_RESULT_BUFFER_OVERFLOW;
    }

    for (rounds=0; rounds<max_round; rounds++)
    {
        if (aobjs == 0 || dobjs == 0) break;

        // Reset stats.
        shoots[0] = shoots[1] = 0;
        spower[0] = spower[1] = 0;
        absorbed[0] = absorbed[1] = 0;

        // Charge shields.
        for (i=0; i<aobjs; i++) {
            if (aunits[i].exploded) aunits[i].shield = 0;
            else aunits[i].shield = get_shieldmax(aunits[i].obj_type, &a[aunits[i].slot_id]);
        }
        for (i=0; i<dobjs; i++) {
            if (dunits[i].exploded) dunits[i].shield = 0;
            else dunits[i].shield = get_shieldmax(dunits[i].obj_type, &d[dunits[i].slot_id]);
        }

        // Fire shots.
        for (slot=0; slot<anum; slot++)     // Attackers
        {
            for (i=0; i<aobjs; i++) {
                rapidfire = 1;
                unit = &aunits[i];
                if (unit->slot_id == slot) {
                    // Shot.
                    while (rapidfire) {
                        idx = MyRand (0, dobjs-1);
                        apower = UnitShoot (unit, a, &dunits[idx], d, &absorbed[1] );
                        shoots[0]++;
                        spower[0] += apower;

                        atyp = unit->obj_type;
                        dtyp = dunits[idx].obj_type;

                        if (Rapidfire == 0) rapidfire = 0;
                        else rapidfire = RapidFire(atyp, dtyp);
                    }
                }
            }
        }
        for (slot=0; slot<dnum; slot++)     // Defenders
        {
            for (i=0; i<dobjs; i++) {
                rapidfire = 1;
                unit = &dunits[i];
                if (unit->slot_id == slot) {
                    // Shot.
                    while (rapidfire) {
                        idx = MyRand (0, aobjs-1);
                        apower = UnitShoot (unit, d, &aunits[idx], a, &absorbed[0] );
                        shoots[1]++;
                        spower[1] += apower;

                        atyp = unit->obj_type;      
                        dtyp = aunits[idx].obj_type;

                        if (Rapidfire == 0) rapidfire = 0;
                        else rapidfire = RapidFire(atyp, dtyp);
                    }
                }
            }
        }

        // Quick draw?
        fastdraw = CheckFastDraw (aunits, aobjs, a, dunits, dobjs, d);

        // Clean out the blown ships and defenses.
        peak_allocated_round = 0;
        exploded_res = WipeExploded (&aunits, aobjs, &exploded);
        if (exploded_res < 0) {
            free(aunits);
            free(dunits);
            return exploded_res;
        }
        aobjs -= exploded;
        exploded_res = WipeExploded (&dunits, dobjs, &exploded);
        if (exploded_res < 0) {
            free(aunits);
            free(dunits);
            return exploded_res;
        }
        dobjs -= exploded;
        if (peak_allocated_round > peak_allocated_all_rounds) {
            peak_allocated_all_rounds = peak_allocated_round;
        }

        // Round.
        ptr += sprintf ( ptr, "i:%i;a:8:", rounds );
        ptr += sprintf ( ptr, "{s:6:\"ashoot\";d:%s;", longnumber(shoots[0]) );
        ptr += sprintf ( ptr, "s:6:\"apower\";d:%s;", longnumber(spower[0]) ); 
        ptr += sprintf ( ptr, "s:7:\"dabsorb\";d:%s;", longnumber(absorbed[1]) );
        ptr += sprintf ( ptr, "s:6:\"dshoot\";d:%s;", longnumber(shoots[1]) );
        ptr += sprintf ( ptr, "s:6:\"dpower\";d:%s;", longnumber(spower[1]) );
        ptr += sprintf ( ptr, "s:7:\"aabsorb\";d:%s;", longnumber(absorbed[0]) );
        ptr += sprintf ( ptr, "s:9:\"attackers\";a:%i:{", anum );
        for (slot=0; slot<anum; slot++) {
            ptr = GenSlot (ptr, aunits, slot, aobjs, a, d, 1, 0);

            if ((ptr - ResultBuffer) >= sizeof(ResultBuffer)) {
                free(aunits);
                free(dunits);
                return BATTLE_ERROR_RESULT_BUFFER_OVERFLOW;
            }
        }
        ptr += sprintf ( ptr, "}" );
        ptr += sprintf ( ptr, "s:9:\"defenders\";a:%i:{", dnum );
        for (slot=0; slot<dnum; slot++) {
            ptr = GenSlot (ptr, dunits, slot, dobjs, a, d, 0, 0);

            if ((ptr - ResultBuffer) >= sizeof(ResultBuffer)) {
                free(aunits);
                free(dunits);
                return BATTLE_ERROR_RESULT_BUFFER_OVERFLOW;
            }
        }
        ptr += sprintf ( ptr, "}" );
        ptr += sprintf ( ptr, "}" );

        if (fastdraw) { rounds ++; break; }
    }

    // Up to 99 rounds
    char patch[10];
    sprintf(patch, "%02i", rounds);
    round_patch[0] = patch[0];
    round_patch[1] = patch[1];
    
    // Battle Results.
    if (aobjs > 0 && dobjs == 0){ // The attacker won
        res = "awon";
    }
    else if (dobjs > 0 && aobjs == 0) { // The attacker lost
        res = "dwon";
    }
    else    // Draw
    {
        res = "draw";
    }

    ptr += sprintf (ptr, "}s:6:\"result\";s:4:\"%s\";", res);
    ptr += sprintf (ptr, "s:11:\"battle_seed\";d:%s;", longnumber (battle_seed));
    ptr += sprintf (ptr, "s:14:\"peak_allocated\";d:%s;}", longnumber (peak_allocated_all_rounds));

    free (aunits);
    free (dunits);

    if ((ptr - ResultBuffer) >= sizeof(ResultBuffer)) {
        return BATTLE_ERROR_RESULT_BUFFER_OVERFLOW;
    }

    return 0;
}

// ==========================================================================================
// Battle engine initialization - get data and allocate it to arrays.

/*

Input data format.
The input data contains the initial parameters of the battle in text format. For ease of parsing in C, the values are represented in the "key = value" format.

MaxRound = 6            max number of rounds
Rapidfire = 1
RFTab = 202 2 210 5 212 5 ...           First comes the ID of the unit that is performing the rapid-fire, then the number of pairs. Then follow the pairs of values: the ID of the unit being shot and the rapid-fire value; if rapid-fire is disabled, you can omit this table.
UnitParam = 202 4000 10 5 5000 5000 10 ...  The values come in 7-value batches. The first value is the ID, then 6 unit parameters (see TechParam)
Attackers = N
Defenders = M
AttackerN = WEAP SHLD ARMR 202 MT 203 BT 204 LF 205 HF ...  First come the values of attack (float), shields (float), armor (float), then come the pairs of values of unit ID + number of units
DefenderM = WEAP SHLD ARMR 202 MT 203 BT 204 LF 205 HF ...

*/

char* extract_payload(char* lp) {

    size_t text_size = strlen(lp);
    if (text_size < 8) return NULL;
    char* text = malloc(text_size + 1);
    if (!text) return NULL;
    memset(text, 0, text_size + 1);

    char* text_ptr = text;
    while (*lp <= ' ') lp++;
    while (*lp >= ' ') *text_ptr++ = *lp++;
    *text_ptr++ = 0;
    size_t last_char = strlen(text) - 1;
    text_ptr = &text[last_char];
    while (*text_ptr <= ' ') *text_ptr-- = 0;

    return text;
}

// WEAP SHLD ARMR 202 MT 203 BT 204 LF 205 HF ...
int ParseSlot(Slot* slot, char* lp)
{
    char* text = extract_payload(lp);
    if (!text) return BATTLE_ERROR_INSUFFICIENT_RESOURCES;

    int argc = 0;
    char** argv = explode(' ', text, &argc);
    free(text);

    // There must be at least 3 fields for attack/shields/armor and at least 2 more for some object and its quantity

    int pc = 0;
    if (argc < 5) return BATTLE_ERROR_PARSE_SLOT_NOT_ENOUGH;

    slot->weap = (float)atof(argv[pc++]);
    slot->shld = (float)atof(argv[pc++]);
    slot->armor = (float)atof(argv[pc++]);

    int args_left = argc - pc;
    if (args_left % 2 != 0) {
        free_explode_result(argv);
        return BATTLE_ERROR_PARSE_SLOT_NOT_ALIGNED;
    }

    slot->unit_count = args_left / 2;
    slot->unit = malloc(sizeof(UnitCount) * slot->unit_count);
    if (!slot->unit) {
        free_explode_result(argv);
        return BATTLE_ERROR_INSUFFICIENT_RESOURCES;
    }
    memset(slot->unit, 0, sizeof(UnitCount) * slot->unit_count);

    for (int i = 0; i < slot->unit_count; i++) {

        int gid = atoi(argv[pc++]);
        if (gid > GID_MAX) {
            free_explode_result(argv);
            return BATTLE_ERROR_GID_MAX;
        }
        if (!IsFlattened(gid)) {
            free_explode_result(argv);
            return BATTLE_ERROR_GID_UNKNOWN;
        }
        slot->unit[i].gid = gid;
        slot->unit[i].count = atoi(argv[pc++]);
    }

    free_explode_result(argv);
    return 0;
}

int ParseUnitParam(char* lp)
{
    char* text = extract_payload(lp);
    if (!text) return BATTLE_ERROR_INSUFFICIENT_RESOURCES;

    int argc = 0;
    char** argv = explode(' ', text, &argc);
    free(text);

    int params_per_unit = 1 + 6;
    // There must be at least 1 set of parameters
    if (argc < params_per_unit) {
        free_explode_result(argv);
        return BATTLE_ERROR_PARSE_UNIT_PARAM_NOT_ENOUGH;
    }
    // Parameters must be multiples
    if (argc % params_per_unit != 0) {
        free_explode_result(argv);
        return BATTLE_ERROR_PARSE_UNIT_PARAM_NOT_ALIGNED;
    }

    memset(UnitParam, 0, sizeof(UnitParam));

    int pc = 0;
    int num_params = argc / params_per_unit;
    for (int i = 0; i < num_params; i++) {

        int gid = atoi(argv[pc++]);
        if (flatten_counter >= MAX_UNIT_TYPES) {
            free_explode_result(argv);
            return BATTLE_ERROR_GID_TYPES_OVERFLOW;
        }
        if (gid > GID_MAX) {
            free_explode_result(argv);
            return BATTLE_ERROR_GID_MAX;
        }
        if (IsFlattened(gid)) {
            free_explode_result(argv);
            return BATTLE_ERROR_PARSE_UNIT_PARAM_DUPLICATED;
        }
        FlattenId(gid);

        TechParam* unitParam = &UnitParam[IdToOrd(gid)];
        unitParam->structure = atoi(argv[pc++]);
        unitParam->shield = atoi(argv[pc++]);
        unitParam->attack = atoi(argv[pc++]);
        unitParam->cargo = atoi(argv[pc++]);
        unitParam->speed = atoi(argv[pc++]);
        unitParam->consumption = atoi(argv[pc++]);
    }

    free_explode_result(argv);
    return 0;
}

int SetRapidfire(int enable, char* rftab) {
    Rapidfire = enable & 1;
    memset(&RF, 0, sizeof(RFTab));
    if (Rapidfire) {
        // Setup rapidfire table

        char* text = extract_payload(rftab);
        if (!text) return BATTLE_ERROR_INSUFFICIENT_RESOURCES;

        int argc = 0;
        char** argv = explode(' ', text, &argc);
        free(text);

        // The entries come in pairs.
        if (argc % 2 != 0) {
            free_explode_result(argv);
            return BATTLE_ERROR_PARSE_RF_NOT_ALIGNED;
        }

        int pc = 0;
        int args_left = argc;

        while (args_left) {

            int gid = atoi(argv[pc++]); args_left--;
            if (gid > GID_MAX) {
                free_explode_result(argv);
                return BATTLE_ERROR_GID_MAX;
            }
            if (!IsFlattened(gid)) {
                free_explode_result(argv);
                return BATTLE_ERROR_GID_UNKNOWN;
            }
            int num_targets = atoi(argv[pc++]); args_left--;
            if (num_targets > flatten_counter) {
                free_explode_result(argv);
                return BATTLE_ERROR_PARSE_RF_MALFORMED;
            }
            if (args_left < (num_targets * 2)) {
                free_explode_result(argv);
                return BATTLE_ERROR_PARSE_RF_NOT_ENOUGH;
            }
            int ord = IdToOrd(gid);

            RF[ord].count = num_targets;

            if (num_targets != 0) {
                RF[ord].to = malloc(sizeof(UnitCount) * num_targets);
                if (!RF[ord].to) {
                    free_explode_result(argv);
                    return BATTLE_ERROR_INSUFFICIENT_RESOURCES;
                }

                for (int i = 0; i < num_targets; i++) {

                    gid = atoi(argv[pc++]); args_left--;
                    if (gid > GID_MAX) {
                        free_explode_result(argv);
                        return BATTLE_ERROR_GID_MAX;
                    }
                    if (!IsFlattened(gid)) {
                        free_explode_result(argv);
                        return BATTLE_ERROR_GID_UNKNOWN;
                    }
                    RF[ord].to[i].gid = gid;
                    int count = atoi(argv[pc++]); args_left--;
                    if (count > RF_MAX) {
                        free_explode_result(argv);
                        return BATTLE_ERROR_PARSE_RF_MALFORMED;
                    }
                    RF[ord].to[i].count = count;
                }
            }
        }

        free_explode_result(argv);
    }
    return 0;
}

void DumpRFTab() {

    printf("$RapidFire = array (\n");
    for (int i = 0; i < flatten_counter; i++) {

        int gid = OrdToId(i);
        printf("  %i => array ( ", gid);
        RFTab* rf = &RF[i];
        for (int t = 0; t < rf->count; t++) {
            printf("%i => %i, ", rf->to[t].gid, rf->to[t].count);
        }
        printf("),\n");

    }
    printf(")\n");
}

int StartBattle (char *text, int battle_id, unsigned long battle_seed)
{
    char filename[1024];
    Slot *a = NULL, *d = NULL;
    int rf, i, res = -1;
    int anum = 0, dnum = 0, max_round = 6;
    char *ptr, line[0x1000], buf[64], *lp, *rftab, *uparam;

    ptr = strstr (text, "Rapidfire");       // Rapid-fire
    if ( ptr ) {
        ptr = strstr ( ptr, "=" ) + 1;
        rf = atoi (ptr);
    }
    else rf = 1;

    ptr = strstr(text, "MaxRound");       // Max rounds
    if (ptr) {
        ptr = strstr(ptr, "=") + 1;
        max_round = atoi(ptr);
    }
    else max_round = 6;

    ptr = strstr(text, "RFTab");       // Rapid-fire Table
    if (ptr) {
        ptr = strstr(ptr, "=") + 1;
        rftab = ptr;
        while (*rftab == ' ') rftab++;
    }
    else rftab = NULL;

    if (rf && !rftab) return BATTLE_ERROR_MISSING_RF_TAB;

    ptr = strstr(text, "UnitParam");       // UnitParam Table
    if (ptr) {
        ptr = strstr(ptr, "=") + 1;
        uparam = ptr;
        while (*uparam == ' ') uparam++;
    }
    else return BATTLE_ERROR_MISSING_UNIT_PARAM;

    res = ParseUnitParam(uparam);
    if (res < 0) {
        goto exit_with_result;
    }

    ptr = strstr (text, "Attackers");        // Number of attackers
    if ( ptr ) {
        ptr = strstr ( ptr, "=" ) + 1;
        anum = atoi (ptr);
    }
    else anum = 0;
    ptr = strstr (text, "Defenders");        // Number of defenders
    if ( ptr ) {
        ptr = strstr ( ptr, "=" ) + 1;
        dnum = atoi (ptr);
    }
    else dnum = 0;

    if ( anum == 0 || dnum == 0) return BATTLE_ERROR_NOT_ENOUGH_ATTACKERS_OR_DEFENDERS;

    a = (Slot *)malloc ( anum * sizeof (Slot) );    // Allocate memory to the slots.
    if (!a) {
        return BATTLE_ERROR_INSUFFICIENT_RESOURCES;
    }
    memset ( a, 0, anum * sizeof (Slot) );
    d = (Slot *)malloc ( dnum * sizeof (Slot) );
    if (!d) {
        free (a);
        return BATTLE_ERROR_INSUFFICIENT_RESOURCES;
    }
    memset ( d, 0, dnum * sizeof (Slot) );

    // Attackers.
    for (i=0; i<anum; i++)
    {
        sprintf ( buf, "Attacker%i", i );
        ptr = strstr (text, buf);
        if ( ptr ) {
            lp = line;
            ptr = strstr ( ptr, "=" ) + 1;
            while ( *ptr >= ' ' ) *lp++ = *ptr++;
            *lp++ = 0;
        }

        res = ParseSlot(&a[i], line);
        if (res < 0) {
            goto exit_with_result;
        }
    }

    // Defenders.
    for (i=0; i<dnum; i++)
    {
        sprintf ( buf, "Defender%i", i );
        ptr = strstr (text, buf);
        if ( ptr ) {
            lp = line;
            ptr = strstr ( ptr, "=" ) + 1;
            while ( *ptr >= ' ' ) *lp++ = *ptr++;
            *lp++ = 0;
        }

        res = ParseSlot(&d[i], line);
        if (res < 0) {
            goto exit_with_result;
        }
    }

    // Battle engine settings
    res = SetRapidfire ( rf, rftab );
    if (res < 0) {
        goto exit_with_result;
    }
    //DumpRFTab();
    
    // **** START BATTLE ****
    peak_allocated_round = 0;
    peak_allocated_all_rounds = 0;
    res = DoBattle ( a, anum, d, dnum, battle_seed, max_round);

exit_with_result:

    if (a) {
        for (i = 0; i < anum; i++) {
            if (a[i].unit) {
                free(a[i].unit);
            }
        }
        free(a);
    }

    if (d) {
        for (i = 0; i < dnum; i++) {
            if (d[i].unit) {
                free(d[i].unit);
            }
        }
        free(d);
    }

    for (int i = 0; i < flatten_counter; i++) {
        if (RF[i].to) {
            free(RF[i].to);
        }
    }

    // Write down the results
    if ( res >= 0 )
    {
        sprintf ( filename, "battleresult/battle_%i.txt", battle_id );
        if (FileSave(filename, ResultBuffer, (unsigned long)strlen(ResultBuffer)) < 0) {
            return BATTLE_ERROR_DATA_SAVE;
        }
    }

    return res;
}

int main(int argc, char **argv)
{
    int res = 0;
    char filename[1024];
    char *battle_data;
    unsigned long battle_seed = 0;

    if ( argc < 3 ) return BATTLE_ERROR_NOT_ENOUGH_CMD_LINE_PARAMS;

    // Load the source file and select the source data
    {
        int battle_id = atoi(argv[1]);

        if (battle_id <= 0) {
            return BATTLE_ERROR_INVALID_BATTLE_ID;
        }

        // Initialize RNG
        battle_seed = atoi(argv[2]);
        if (battle_seed == 0) {
            battle_seed = (unsigned long)time(NULL);
        }
        MySrand(battle_seed);

        sprintf ( filename, "battledata/battle_%i.txt", battle_id );
        battle_data = FileLoad ( filename, NULL, "rt" );
        if (!battle_data) {
            return BATTLE_ERROR_DATA_LOAD;
        }

        // Parse the raw data into binary format and start the battle
        res = StartBattle ( battle_data, battle_id, battle_seed);

        free(battle_data);
    }

    return res;
}