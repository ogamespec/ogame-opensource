# OGame Modification System Guide

## Overview
The modification system allows extending OGame functionality without modifying core code. Mods are loaded automatically from the `game/mods/` directory.

## Mod Structure

### Required Files
```
game/mods/ModName/
    main.php          - Main mod class (required)
    install.php       - Installation script (optional)
    uninstall.php     - Uninstallation script (optional)
    config.php        - Configuration file (optional)
```

### Main Class Structure
```php
<?php
class ModName extends GameMod {
    public function install() : void {
        // Installation logic
    }
    
    public function uninstall() : void {
        // Uninstallation logic
    }
    
    public function init() : void {
        // Initialization logic
    }
    
    // Hook methods (optional)
}
```

## Hook Methods Reference

### Routing Hooks

#### route(array &$router)
Hook for adding custom routes.

**Parameters:**
- `$router` - Router array reference

**Example:**
```php
public function route(array &$router) : bool {
    $router['custom'] = 'pages/custom.php';
    return true;
}
```

#### route_admin(array &$router)
Hook for adding custom admin routes.

**Parameters:**
- `$router` - Admin router array reference

**Example:**
```php
public function route_admin(array &$router) : bool {
    $router['custom'] = 'pages_admin/admin_custom.php';
    return true;
}
```

### Queue Hooks

#### update_queue(array &$queue)
Hook for modifying queue tasks.

**Parameters:**
- `$queue` - Queue array reference

**Example:**
```php
public function update_queue(array &$queue) : bool {
    $queue['custom_task'] = 'handlers/custom.php';
    return true;
}
```

### Resource Hooks

#### add_resources(array &$json, array $aktplanet)
Add custom resources to the resource bar.

**Parameters:**
- `$json` - JSON resources array reference
- `$aktplanet` - Current planet array

**Example:**
```php
public function add_resources(array &$json, array $aktplanet) : bool {
    $json['custom_resource'] = [
        'name' => 'Custom',
        'amount' => $planet['custom_amount'],
        'max' => $planet['custom_max'],
        'icon' => 'custom.png'
    ];
    return true;
}
```

#### add_bonuses(array &$bonuses)
Add custom bonuses to the bonus bar.

**Parameters:**
- `$bonuses` - Bonuses array reference

**Example:**
```php
public function add_bonuses(array &$bonuses) : bool {
    $bonuses[] = [
        'name' => 'Custom Bonus',
        'value' => '+10%',
        'icon' => 'bonus.png'
    ];
    return true;
}
```

### Menu Hooks

#### add_menuitems(array &$json)
Add custom menu items.

**Parameters:**
- `$json` - Menu items JSON array reference

**Example:**
```php
public function add_menuitems(array &$json) : bool {
    $json[] = [
        'name' => 'Custom',
        'link' => 'index.php?page=custom',
        'icon' => 'custom.png'
    ];
    return true;
}
```

### Database Hooks

#### lock_tables(array &$tabs)
Lock custom tables during operations.

**Parameters:**
- `$tabs` - Tables array reference

**Example:**
```php
public function lock_tables(array &$tabs) : bool {
    $tabs[] = 'custom_table';
    return true;
}
```

#### install_tabs_included(array &$tabs)
Install custom database tables.

**Parameters:**
- `$tabs` - Tables array reference

**Example:**
```php
public function install_tabs_included(array &$tabs) : bool {
    $tabs[] = 'custom_table';
    return true;
}
```

#### add_db_row(array &$row, string $tabname)
Add custom fields when inserting database rows.

**Parameters:**
- `$row` - Row data array reference
- `$tabname` - Table name

**Example:**
```php
public function add_db_row(array &$row, string $tabname) : bool {
    if ($tabname == 'planets') {
        $row['custom_field'] = 0;
    }
    return true;
}
```

### Image Hooks

#### get_planet_small_image(int $type, array &$img)
Get custom small planet image path.

**Parameters:**
- `$type` - Planet type
- `$img` - Image info array reference

**Example:**
```php
public function get_planet_small_image(int $type, array &$img) : bool {
    if ($type == PTYP_CUSTOM) {
        $img['path'] = 'mods/ModName/custom_small.jpg';
        return true;
    }
    return false;
}
```

#### get_planet_image(int $type, array &$img)
Get custom big planet image path.

**Parameters:**
- `$type` - Planet type
- `$img` - Image info array reference

**Example:**
```php
public function get_planet_image(int $type, array &$img) : bool {
    if ($type == PTYP_CUSTOM) {
        $img['path'] = 'mods/ModName/custom_big.jpg';
        return true;
    }
    return false;
}
```

#### get_object_image(int $id, array &$img)
Get custom object image path.

**Parameters:**
- `$id` - Object ID
- `$img` - Image info array reference

**Example:**
```php
public function get_object_image(int $id, array &$img) : bool {
    if ($id == GID_B_CUSTOM) {
        $img['path'] = 'mods/ModName/custom.gif';
        return true;
    }
    return false;
}
```

### Content Hooks

#### begin_content()
Hook at content start (before page content).

**Example:**
```php
public function begin_content() : bool {
    echo '<div class="custom-content">';
    return true;
}
```

#### end_content()
Hook at content end (after page content).

**Example:**
```php
public function end_content() : bool {
    echo '</div>';
    return true;
}
```

### Build/Research Hooks

#### can_build(array &$info)
Check if building can be constructed.

**Parameters:**
- `$info` - Build information array reference

**Example:**
```php
public function can_build(array &$info) : bool {
    if ($info['gid'] == GID_B_CUSTOM) {
        $info['allowed'] = $player['custom_level'] >= 10;
    }
    return true;
}
```

#### can_research(array &$info)
Check if research can be conducted.

**Parameters:**
- `$info` - Research information array reference

**Example:**
```php
public function can_research(array &$info) : bool {
    if ($info['gid'] == GID_R_CUSTOM) {
        $info['allowed'] = $player['custom_research'] >= 5;
    }
    return true;
}
```

#### build_end(int $planet_id, array &$queue)
Hook when building construction completes.

**Parameters:**
- `$planet_id` - Planet ID
- `$queue` - Queue array reference

**Example:**
```php
public function build_end(int $planet_id, array &$queue) : bool {
    $planet = getPlanetById($planet_id);
    if ($planet['custom_building'] > 0) {
        // Custom logic
    }
    return true;
}
```

#### research_end(array &$queue)
Hook when research completes.

**Parameters:**
- `$queue` - Queue array reference

**Example:**
```php
public function research_end(array &$queue) : bool {
    // Custom logic
    return true;
}
```

### Fleet Hooks

#### fleet_available_missions(array $param, array &$missions)
Add custom fleet missions.

**Parameters:**
- `$param` - Fleet parameters
- `$missions` - Missions array reference

**Example:**
```php
public function fleet_available_missions(array $param, array &$missions) : bool {
    $missions[] = [
        'id' => FTYP_CUSTOM,
        'name' => 'Custom Mission',
        'icon' => 'custom.png'
    ];
    return true;
}
```

#### fleet_handler(array $param)
Handle custom fleet mission.

**Parameters:**
- `$param` - Fleet parameters

**Example:**
```php
public function fleet_handler(array $param) : bool {
    if ($param['mission'] == FTYP_CUSTOM) {
        // Handle custom mission
        return true;
    }
    return false;
}
```

### Production Hooks

#### prod_post_process(array &$planet, array &$eco)
Post-process production calculations.

**Parameters:**
- `$planet` - Planet data array reference
- `$eco` - Economy data array reference

**Example:**
```php
public function prod_post_process(array &$planet, array &$eco) : bool {
    if ($planet['custom_bonus'] > 0) {
        $eco['production']['metal'] *= 1.1;
    }
    return true;
}
```

### Battle Hooks

#### battle_post_process(array &$res)
Post-process battle results.

**Parameters:**
- `$res` - Battle results array reference

**Example:**
```php
public function battle_post_process(array &$res) : bool {
    if ($res['winner'] == 'awon') {
        // Custom victory logic
    }
    return true;
}
```

## Installation Process

### Manual Installation
1. Create mod directory: `game/mods/ModName/`
2. Create `main.php` with mod class
3. Upload to server
4. Mod loads automatically

### Auto-Install
1. Create `install.php` in mod directory
2. Add installation logic
3. Run `install.php` via browser or CLI

### Example Mod
```php
<?php
class ExampleMod extends GameMod {
    public function install() : void {
        // Create database table
        dbquery("CREATE TABLE IF NOT EXISTS `".db_prefix()."example` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `player_id` INT NOT NULL,
            `data` TEXT,
            PRIMARY KEY (`id`)
        )");
    }
    
    public function uninstall() : void {
        // Drop database table
        dbquery("DROP TABLE IF EXISTS `".db_prefix()."example`");
    }
    
    public function init() : void {
        // Initialize mod
    }
    
    public function route(array &$router) : bool {
        $router['example'] = 'pages/example.php';
        return true;
    }
    
    public function add_resources(array &$json, array $aktplanet) : bool {
        $json['example_resource'] = [
            'name' => 'Example',
            'amount' => $aktplanet['example_amount'],
            'max' => 1000
        ];
        return true;
    }
}
```

## Mod Configuration

### Configuration File
Create `config.php` in mod directory:
```php
<?php
$ModConfig = [
    'option1' => 'value1',
    'option2' => 'value2'
];
```

### Access Configuration
```php
global $ModConfig;
if (isset($ModConfig['option1'])) {
    // Use configuration
}
```

## Best Practices

1. **Use hooks only when necessary** - Avoid modifying core files
2. **Prefix all database tables** - Use mod name as prefix
3. **Handle errors gracefully** - Return false on errors
4. **Document all hooks** - Explain what each hook does
5. **Test thoroughly** - Test in development environment
6. **Use constants** - Define custom constants
7. **Follow naming conventions** - Use PascalCase for classes
8. **Clean up on uninstall** - Remove all mod data

## Debugging Mods

### Enable Debug Mode
```php
define('DEBUG', true);
```

### Log Issues
```php
file_put_contents('mods/ModName/debug.log', print_r($data, true), FILE_APPEND);
```

### Check Mod Loading
```php
if (class_exists('ModName')) {
    // Mod is loaded
}
```

## Common Mod Examples

### Add Custom Building
1. Define building ID in `defs.php`
2. Implement `can_build()` hook
3. Implement `build_end()` hook
4. Add building to tech tree

### Add Custom Resource
1. Define resource ID in `defs.php`
2. Implement `add_resources()` hook
3. Modify production calculations
4. Update UI templates

### Add Custom Fleet Mission
1. Define mission type in `defs.php`
2. Implement `fleet_available_missions()` hook
3. Implement `fleet_handler()` hook
4. Add mission to fleet UI
