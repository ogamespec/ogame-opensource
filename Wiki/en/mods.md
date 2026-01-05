# Mods Draft

The initial experiment with hardcoding the base source code didn't go so well. It all ended up being custom and hacky, polluting the source code and the database as well.
In #159, all modification experiments were scrapped to make way for a more thoughtful implementation. This post will collect everything related to the new mod implementation—ideas, descriptions, etc.

## Mods folder

All mods are stored as subdirectories under `/game/mods`. A mod's folder is self-contained, containing all the code and resources needed to support the mod.

## Base part

The base engine is version 0.84. All modifications expand or modify 0.84.

## Installing mods

Mods are activated through the admin section (`pages_admin/admin_mods.php`). The admin can enable and disable mods, as well as set the order in which they are activated. Mods are assigned in the order they are activated.

## Impact of modifications

Modifications affect the base game as follows:
- When a mod is activated, new columns or even new tables are added to the game's tables. All new columns are added to the end of the original tables. When a mod is deactivated, the columns and tables are deleted. You can use the mod's install.sql / uninstall.sql for these purposes.
- New CSS and JavaScript images are contained in the mod's folder. Skins are not related to mods in any way, as they only change the base game.
- The mod constructor. Before switching to the page handler, control is transferred to the mod constructor (`ctor`), which modifies tables from the base game (for example, the cost table). A list of modifiable game tables will follow.
- A modification can both add its own pages to the game and modify existing ones. At this point, you will need to separate the page backend from the frontend. The backend should be written in JSON so that mods can modify the content, and the frontend simply renders what the base game and mods generate. Example: left menu items; table rows in the Resource Settings menu.
- The battle engine is now being made mod-independent; all input tables for calculations will be passed through the battle engine frontend.
- The algorithmic part contains hooks for passing to mods to change the behavior of basic algorithms (e.g., what to do after a battle).
- New tasks for the event queue. Mods can implement their own events, and handlers are located in mods.
- Mods have access to the entire core game model (planet.php, user.php, and other core modules).

## manifest.json

Contains a basic description of the modification, such as:

```json
{
  "name": "My Awesome Mod",
  "version": "1.0.0",
  "author": "YourName",
  "description": "Добавляет новые возможности для игроков",
  "website": "https://github.com/yourname/mod-name"
}
```