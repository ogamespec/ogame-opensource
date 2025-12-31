# Global Event Queue

In short, it's a large table inside the game that tells what players should complete and when.

The main module for managing the time line of the game. The program module is located in the file `game/queue.php`
The time line consists of time intervals between two events that affect the state of player accounts.
The events of all players are queued in a common queue. The queue is discrete - each event is synchronized on a second-by-second basis.
Checking for event completion (queue movement) is performed when players perform any actions (navigate through pages).
If two events fall on the same second, they are processed in order of priority (e.g. if Attack coincides in time with Recycle,
on the same coordinates, the Attack is processed first, and then Recycle).

Each event has a beginning (start time) and an end (end time of the event). Some events can be canceled. Canceling some events generates 
other events (e.g. canceling a fleet task generates a new fleet return task).

Main types of account events:
 - Time counters on a player's account (officers exiration, account deletion, etc).
 - Construction on a planet/moon
 - Research
 - Shipyard construction
 - Tasks for fleet and IPM
 - Global events for all players (re-login, cleaning of virtual debris fields, deletion of destroyed planets, recalculation of points 3 times a day, etc.).

Recording of old scores: 8:05, 16:05, 20:05 on the server

Static recalculation of player points: 0:10 on the server

Virtual DF disappears on Monday at 1:10 server time if no fleets fly to/from it and if it has 0 resources.

Task Types:
- CommanderOff: Officer expires: Commander
- AdmiralOff: Officer expires: Admiral.
- EngineerOff: Officer expires: Engineer
- GeologeOff: Officer expires: Geologist.
- TechnocrateOff: Officer expires: Technocrat.
- DeleteAccount: delete account
- UnbanPlayer: unban a player.
- ChangeEmail: record a permanent e-mail address.
- AllowName: allow to change player's name.
- AllowAttacks: unban a player's attacks
- UnloadAll: re-login all players
- CleanDebris: clean virtual debris fields
- CleanPlanets: remove destroyed planets / abandoned moons
- CleanPlayers: deleting inactive players and players set for deletion (1:10)
- UpdateStats: save old stat points
- RecalcPoints: recalculate player statistics
- RecalcAllyPoints: recalculation of alliance statistics
- Build: completion of construction on the planet (sub_id - ID of the task in the build queue, obj_id - type of construction)
- Demolish: completion of demolition on a planet (sub_id - task ID in the build queue, obj_id - type of construction)
- Research: research (sub_id - number of planet where research was started, obj_id - type of research)
- Shipyard: Shipyard job (sub_id - planet number, obj_id - construction type) :warning: This is a special event that requires special handling (#148)
- Fleet: Fleet Assignment / IPM Attack (sub_id - entry number in the fleet table)
- Debug: debug event
- AI: tasks for bot (sub_id - strategy number, obj_id - current block number)
- Coupon: crediting coupons (handler is located in coupon.php)

How the queue is updated:
After another click by one of the users, each queue job is checked for completion. If the task is completed, its handler is called and the task is removed from the queue.

## CRON

If you want, you can assign a cron.php script to run periodically, so that the queue moves on its own, without player action.
But in a crowded universe, you don't need to do that at all.
