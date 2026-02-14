# Battle Engine

## Engine Design

The engine is a black box. Initial conditions (obtained from a file) are given as input:
- Combat system settings (percentage of defense and fleet to wreckage, rapid fire)
- list of attackers and defenders 

On output, the engine generates:
- battle results (written to the output file)

## Combat System

by Sanyok.

The OGame combat system is activated when enemy units are encountered near a planet or moon.
This happens mostly during an attack, but also during an espionage action in which a probe has been detected.
In this case, the units line up and start firing at each other. This happens 6 times (6 rounds).
Whoever is left with ships at the end is the winner. If there are units left on both sides at the end, the battle ends in a draw and the attacker goes home.
In each round, ships and defenses fire at each other. Each unit fires once (exception: rapid-fire) at a randomly selected target.
The firepower of ships is determined by the attack score. This power is absorbed partially or completely by shields. If there is still anything left of the combat power afterwards, it is subtracted from the ship's armor.
Shots can even hit ships with completely destroyed armor. At the end of the round, ships with no more armor left explode.
But 30% armor damage also has an explosion chance, which increases with the degree of damage.

### The strength of the shot

Each unit has a starting firing power. This can be increased by researching weapon technology by 10% per level.
For example: a heavy fighter has an attack rating of 150. Weapon tech level 10 raises this by 100%, i.e. to 300.
During combat, the attack scores of all units are added together.

### Shields

Shields are the first to take a hit, protecting the armor from damage. Only when all shields are destroyed does armor destruction begin.
Shields can be improved by researching shield technology by 10% per research level.
Shields fully regenerate after each round. Within a round, the shield is destroyed in whole cells of 1%, and the remaining attack power less than 1% is absorbed without any loss.
For example, if the strength of a shot is enough to hit 3.7% of the shield, only 3% will be destroyed and 0.7% will be absorbed. Therefore, shots with a force of less than 1%, bounce off shields without reducing their strength or damaging armor.
The probability of explosion in this case is also not calculated.
Example: a light fighter (attack 50) fires at a large dome (shield 10000). After the shot, the dome still has 10000 shield, because the shot is too weak and the shield absorbs it completely.
The heavy fighter on the other hand has a shot strength of 150. That's 1.5% of the shield, so the shot counts and takes a full 1% of the attack off the shield.
After that, the dome only has 9900 shield strength left, since 0.5% = 50 of the attack is absorbed by the shield without loss.

### Armor

Armor indicates how much damage a ship can absorb before it is destroyed. Armor points are always 10% of the structure.
They can be calculated when building a unit. For every 10 metal or crystal (deut does not count here) you get 1 armor point.
Armor strength can be increased by researching spaceship armor by 10% per level. Unfortunately, ships tend to explode already from 30% armor damage.
After combat, a combat report is compiled. The only exception: if the attacker's units were destroyed in the first 2 rounds.
Then the attacker receives only a short report. The defender always receives a report

### Order of shots

The order of shots is precisely defined, namely from left to right in the combat report according to the principle: light ships, heavy ships, light defense, heavy defense.

### Target selection

The target is chosen completely randomly. It may be that all units fire at one target, even though there are other targets, but this is unlikely.
Normally, it should be that the units with the most units get the most shots. Each ship and defensive structure has a probability of being hit equal to 1/(number of units)

### Restoring defense

Defensive structures have a 70% chance of recovery after combat.
If the number of units is small (less than 10), this probability is calculated for each structure separately.
For larger numbers, the probability is calculated for each TYPE of defense. At the same time 70% +/-10% of destroyed defenses are always restored.
With 10 rocket launchers, this is a minimum of 6 and a maximum of 8 recovered RLs. Fractional numbers are rounded normally. For each type of defense the probability is calculated separately.
So, for example, RLs and lasers are not added together.

## Rapidfire              

The term Rapid Fire refers to the ability of some ship types to fire more than the one shot per round prescribed by the combat system. 
The probability of re-shooting is limited and depends on the types of ships firing and the target of the shot. The rapid-fire data is given in terms of the percentage probability of a second shot or, as in the game, the average number of shots fired per round.
In detail, the rapid-fire works as follows:
A ship that hits a unit against which it has a rapid-fire gun has a certain probability of firing again, according to the combat system - with random target selection.
When it hits that unit again, it again "rolls a coin" and, if it is lucky, fires another shot.

## Linux Build

To compile under Linux use following command line:

```
gcc battle.c -lm -o battle
```

And put battle executable to http://uni1.yoursever.com/cgi-bin

Path to battle engine should be: ../cgi-bin/battle

## Windows Build

To compile battle engine - download LCC, it fast and free:

https://lcc-win32.services.net/
(Or search "lcc" in Google)

Copy battle.c and battle.h to C:/lcc/bin and type in command line:

```
lc battle.c -o battle.exe
```

(USE lc INSTEAD lcc)

And put battle executable to http://uni1.yoursever.com/game

Path to battle engine should be: battle.exe

## FreeBSD Build

Same as Linux.
