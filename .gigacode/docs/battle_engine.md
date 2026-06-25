# OGame Battle Engine Documentation

## Overview
OGame uses a two-part battle engine:
1. **C Engine** - Fast, compiled battle simulation
2. **PHP Controller** - Interface and report generation

## C Battle Engine

### Location
- Source: `game/battle/battle.cpp`
- Project: `game/battle/VS2022/BattleEngine.sln`
- Output: `game/battle.exe`

### Compilation
```bash
# Using Visual Studio 2022
# Build Release configuration
# Output: game/battle.exe
```

### Output Format
The C engine outputs data in PHP unserialize() compatible format.

```php
Array (
   'battle_seed' => Initial seed for RNG
   'peak_allocated' => Memory consumption during execution
   'result' => 'awon' (Attacker won), 'dwon' (Defender won), 'draw' (Draw)
   
   'before' => Array (  // Fleets before battle
        'attackers' => Array (
              [0] => Array ( 'weap' => 10.0, 'shld' => 11.0, 'armr' => 12.0, 'units' => Array (202=>5, 203=>6, ...) ),
              [1] => Array ( ... )
        )
        
        'defenders' => Array (
              [0] => Array ( 'weap' => 10.0, 'shld' => 11.0, 'armr' => 12.0, 'units' => Array (202=>5, 203=>6, ..., 401=>5, 402=>44) ),
              [1] => Array ( ... )
        )
   )
   
   'rounds' => Array (
       [0] => Array (
            'ashoot' => Attack fleet fires: 988 round(s)
            'apower' => total power of 512.720.100
            'dabsorb' => The defender's shields absorb 43.724
            'dshoot' => The defending fleet fires 1.651 shot(s)
            'dpower' => total power of 428.728
            'aabsorb' => The attacker's shields absorb 355.453
            
            'attackers' => Array (  // attacker slots
                  [0] => Array ( 202=>5, 203=>6, ... ),
                  [1] => Array ( ... )
            )
            
            'defenders' => Array (  // defenders' slots
                  [0] => Array ( 202=>5, 203=>6, ..., 401=>5, 402=>44 ),
                  [1] => Array ( ... )
            )
       ),
       [1] => Array ( ... )   // next round
   )
)
```

### Data Structures

#### TechParam
```cpp
struct TechParam {
    double weapon;
    double shielding;
    double armour;
    double capacity;
    double speed;
    double consumption;
    double build_time;
    double metal_cost;
    double crystal_cost;
    double deuterium_cost;
};
```

#### RFTab (Rapidfire Table)
```cpp
struct RFTab {
    int source_id;
    int target_id;
    int count;
};
```

#### Flattening Arrays
```cpp
uint8_t flatten_array[0x10000];     // ID -> ordinal mapping
uint16_t unflatten_array[MAX_UNIT_TYPES];  // ordinal -> ID mapping
int flatten_counter;                 // Number of known IDs
```

### Key Functions

#### IdToOrd
Converts game object ID to ordinal.
```cpp
uint8_t IdToOrd(uint16_t id) {
    return flatten_array[id];
}
```

#### OrdToId
Converts ordinal to game object ID.
```cpp
uint16_t OrdToId(uint8_t ord) {
    return unflatten_array[ord];
}
```

#### FlattenId
Adds ID to flattening arrays.
```cpp
void FlattenId(uint16_t id);
```

#### IsFlattened
Checks if ID is in flattening arrays.
```cpp
int IsFlattened(uint16_t id);
```

### Battle Algorithm

1. **Initialization**
   - Load unit parameters
   - Build rapidfire table
   - Flatten unit IDs
   - Initialize RNG with seed

2. **Battle Rounds**
   - Calculate attacker power
   - Calculate defender power
   - Determine shots fired
   - Apply damage
   - Update unit counts
   - Check for victory condition

3. **Rapidfire Implementation**
   - Check rapidfire table for each unit
   - Calculate additional shots
   - Apply to damage calculation

4. **Victory Determination**
   - Check if one side has no units
   - Calculate winner based on remaining units
   - Generate loot based on resources

## PHP Battle Controller

### Location
- `game/core/battle.php`
- `game/core/battle_engine.php`
- `game/core/battle_report.php`

### Battle Flow

1. **Prepare Battle Data**
```php
function battle($att_fleet, $def_fleet, $planet, $mission) {
    // Prepare attacker fleet
    // Prepare defender fleet
    // Prepare planet defense
    // Call C engine
    // Process results
}
```

2. **Call C Engine**
```php
// Build command line
$cmd = "battle.exe " . $seed . " " . $rapidfire;
// Execute and capture output
$output = shell_exec($cmd);
// Parse output
$results = unserialize($output);
```

3. **Process Results**
```php
// Extract battle rounds
// Calculate losses
// Calculate loot
// Generate report
```

### Battle Report Generation

#### Report Structure
```php
Array (
    'battle_seed' => Seed used
    'result' => Winner
    'rounds' => Array of rounds
    'losses' => Array of losses
    'loot' => Array of loot
    'time' => Battle timestamp
)
```

#### Report Types
- **Text Report**: `battle_report.php` generates HTML text
- **Link Report**: Stored in database for later viewing

## Unit Parameters

### Fleet Units
| ID | Name | Weapon | Shield | Armor | Capacity | Speed | Consumption |
|----|------|--------|--------|-------|----------|-------|-------------|
| 202 | Small Cargo | 50 | 100 | 2000 | 5000 | 7500 | 20 |
| 203 | Large Cargo | 150 | 250 | 8000 | 25000 | 5000 | 50 |
| 204 | Light Fighter | 150 | 50 | 400 | 500 | 12500 | 15 |
| 205 | Heavy Fighter | 350 | 100 | 1000 | 800 | 10000 | 30 |
| 206 | Cruiser | 800 | 400 | 5000 | 10000 | 8000 | 100 |
| 207 | Battleship | 1200 | 600 | 15000 | 20000 | 5000 | 150 |
| 208 | Colony Ship | 0 | 1000 | 10000 | 7500 | 3750 | 100 |
| 209 | Recycler | 0 | 200 | 2000 | 20000 | 3750 | 40 |
| 210 | Espionage Probe | 0 | 10 | 100 | 100 | 125000 | 1 |
| 211 | Bomber | 2500 | 1000 | 5000 | 10000 | 2500 | 750 |
| 212 | Solar Satellite | 0 | 100 | 2000 | 0 | 0 | 0 |
| 213 | Destroyer | 2000 | 1000 | 20000 | 50000 | 4000 | 500 |
| 214 | Deathstar | 50000 | 10000 | 100000 | 1000000 | 1000 | 10000 |
| 215 | Battlecruiser | 1200 | 600 | 8000 | 12000 | 6000 | 200 |

### Defense Units
| ID | Name | Weapon | Shield | Armor |
|----|------|--------|--------|-------|
| 401 | Rocket Launcher | 80 | 100 | 200 |
| 402 | Light Laser | 250 | 100 | 200 |
| 403 | Heavy Laser | 700 | 200 | 600 |
| 404 | Gauss Cannon | 2000 | 1000 | 4000 |
| 405 | Ion Cannon | 300 | 100 | 200 |
| 406 | Plasma Turret | 4000 | 1000 | 2000 |
| 407 | Small Shield Dome | 0 | 10000 | 1000 |
| 408 | Large Shield Dome | 0 | 50000 | 5000 |
| 502 | ABM | 0 | 0 | 0 |
| 503 | IPM | 0 | 0 | 0 |

## Rapidfire System

### Configuration
- Maximum rapidfire: 5000
- Dice faces: 100000 (RF_DICE)
- Format: `source_id -> target_id: count`

### Example
```php
// Light fighter rapidfires against espionage probe
$RF[204][210] = 5;  // 5x rapidfire
```

### Calculation
1. Check rapidfire table for attacker unit
2. Check if defender unit matches
3. Roll dice (1d100000)
4. If roll <= rapidfire, additional shot fires
5. Repeat for each defender unit

## Battle Parameters

### Attack Fleet
```php
Array (
    'fleet' => Array of fleet slots
    'units' => Array of unit counts
    'weap' => Total weapon tech
    'shld' => Total shield tech
    'armr' => Total armour tech
)
```

### Defense Fleet
```php
Array (
    'fleet' => Array of fleet slots
    'units' => Array of unit counts
    'weap' => Total weapon tech
    'shld' => Total shield tech
    'armr' => Total armour tech
)
```

### Planet Defense
```php
Array (
    'units' => Array of defense counts
    'weap' => Weapon tech level
    'shld' => Shield tech level
    'armr' => Armour tech level
)
```

## Testing Battle Engine

### C Engine Tests
```bash
# Run battle.exe with test data
battle.exe 12345 1 < battle_test.txt
```

### PHP Tests
```php
// Test battle simulation
function testBattle() {
    $att_fleet = [
        'fleet' => [[202 => 10]],
        'weap' => 1, 'shld' => 1, 'armr' => 1
    ];
    $def_fleet = [
        'fleet' => [[203 => 5]],
        'weap' => 1, 'shld' => 1, 'armr' => 1
    ];
    $planet = ['weap' => 1, 'shld' => 1, 'armr' => 1];
    
    $result = battle($att_fleet, $def_fleet, $planet, FTYP_ATTACK);
    
    // Verify results
    $this->assertNotNull($result);
}
```

## Performance Optimization

### Memory Management
- Peak memory tracking
- Efficient data structures
- Flattening arrays for fast lookups

### Speed Optimization
- Compiled C engine
- Minimal PHP overhead
- Efficient string parsing

### Scalability
- Supports unlimited fleet slots
- Handles large unit counts
- Efficient rapidfire calculations

## Troubleshooting

### Common Issues

1. **Battle Engine Not Found**
   - Ensure `battle.exe` exists in `game/battle/`
   - Check file permissions
   - Verify architecture (32/64 bit)

2. **Incorrect Results**
   - Check unit parameters
   - Verify rapidfire table
   - Confirm tech levels

3. **Performance Issues**
   - Optimize fleet sizes
   - Reduce rapidfire values
   - Use efficient data structures

### Debugging
```php
// Enable debug mode
define('DEBUG_BATTLE', true);

// Log battle data
file_put_contents('battle_debug.log', print_r($data, true), FILE_APPEND);
```

## Future Enhancements

### Planned Features
1. Configurable battle parameters
2. Custom battle rules
3. Battle simulation mode
4. Battle history tracking
5. Advanced reporting

### Extensibility
- Hook system for custom rules
- Mod support for new units
- Database-driven parameters
