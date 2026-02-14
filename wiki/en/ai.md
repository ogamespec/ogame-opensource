# AI Overview

There are bots built into the game. Their intelligence varies to a greater or lesser degree as new abilities are added. At the moment bots are able to evolve up to small cargo and after its construction "hibernate".

**Bot intelligence is classified information.** This is done so that players can not predict the behavior of bots.

Externally bots do not differ from the players, they honestly when building or sending fleets ignite activity and do not take resources from nowhere. In short - bots do not "draw" anything for themselves, and honestly achieve everything by the same means as normal players.

The only difference is that bots are always virtually (in the form of events) present in the game, i.e. they have no analog of logging into the game (login). However, they also take into account the re-login and after re-login the variable "Make activity on the main page" is set and the bot artificially creates activity, imitating the login to the game.

Therefore, no game means can not determine whether the player is a bot or a human. Unless the bot will not reply to your private messages, although there are some human players who also prefer not to communicate via PMs.

# How AI Works

<a href='http://www.youtube.com/watch?feature=player_embedded&v=4DdlG0zwLAI' target='_blank'><img src='http://img.youtube.com/vi/4DdlG0zwLAI/0.jpg' width='425' height=344 /></a>

## What bots can do

- Build buildings (no building queue)
- Build fleet and shipyard defenses
- Do research
- Pick up the event list (overview)
- Send fleets and do fleetsave
- Launch IPMs
- Rename planets
- Delete small planets after a failed colonization 
- Change resource production settings
- Form alliances, but without the social aspect
- View the galaxy
- To examine statistics
- Use the phalanx
- Flip fleets with gates
- Simulate logging into the game
- Change name (from time to time)
- Turn vacation mode on and off
- Waiting, doing nothing
- Forget about the game

## How they do it

In the global event queue there is a special type of tasks - "AI", which activates the intelligence of bots, that is, the bot decides exactly how it will do its actions listed above.

Each bot planet has its own decision chain, and also a global decision chain is attached to the bot's account, which forms the bot's development line (chooses the strategy).

In general, there can be as many chains as you like.

![whc5138c41c4d579](/wiki/imgstore/whc5138c41c4d579.jpg)

# How to program bots AI

Chains of actions are called **strategies**.

The programmer creates and edits strategies, which are placed in the **botstrat** table. After startup, the strategy from this table is "compiled" and placed in the **queue** table as tasks.

That is, the strategy from the **botstat** table is a "template" (object), which is placed in the **queue** table in the form of a sequence of actions (instance).

Strategies are programmed in a specialized visual programmer.

<a href='http://www.youtube.com/watch?feature=player_embedded&v=rCzImdVfRwE' target='_blank'><img src='http://img.youtube.com/vi/rCzImdVfRwE/0.jpg' width='425' height=344 /></a>

# Graphical programmer

The environment is a visual editor of specialized graphical diagrams consisting of blocks. The graphical diagram is converted in the compilation process to the bot's job queue.

Basic Graphical Blocks:

![bots002](/wiki/imgstore/bots002.jpg)

|Block|Description|
|---|---|
|begin|Start of the circuit and, respectively, of the job queue|
|end|End of circuit|
|action|Perform some action from the set|
|condition|Random Jump. A branch to the side always means a YES path, a branch down always means a NO path. The difference from normal conditional jumps is that the YES branch can be executed with a specified probability **rnd** (1,100) percent. 100% means that the YES condition is always fulfilled (simulating a normal conditional transition), and the number is not specified in the diagram|
|branch_name|The name of the block chain (aka "skewer")|
|branch_target|Indicates a chain to go to|

Graphical diagrams are built according to a certain principle: "top-down and left-to-right":

![bots003](/wiki/imgstore/bots003.jpg)

A special construction is a chain of blocks (skewer), highlighted in the picture. The scheme as a whole is made by such chains, which perform the role of subtasks. And at the end of each chain it is indicated where to go next.

At the same time, the graphic editor draws connections between blocks by itself, approximately like this:

![resized_whc51dabba016b15](/wiki/imgstore/resized_whc51dabba016b15.jpg)

The user can attach and detach blocks from the diagram, set links between blocks, and move blocks within the diagram. When moving blocks, the graphical editor also takes care of preserving the layout of the diagram by itself.

## Execution of strategies

The blocks are executed one by one, starting with the first one (Start).

![resized_whc51dd447fe4417](/wiki/imgstore/resized_whc51dd447fe4417.jpg)

- Bot strategies can be edited on the fly without interrupting bot activity.
- The changes made to the strategy are applied to all running bots at the same time
- There is a possibility of bot self-learning, i.e. a bot can change its own strategy on the fly, without the programmer's participation. However, in this case the strategy will be the same for all bots.
- There is the possibility of branching strategies and the possibility of parallel execution of any number of strategies.

The operation of a block depends on its type and contents.

After starting the bot through the admin panel, the `_start` start strategy is executed.
