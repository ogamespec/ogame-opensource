# RSS/Atom Feed implementation for OGame v084

This implementation generally follows the original (saved snapshot examples can be found here: https://github.com/ogamespec/OldDesign/tree/main/feed ).

There are 2 feed options: RSS and Atom (selected in the settings).

There is nothing complicated in the implementation - the feed is simply a copy of game messages that are embedded in CDATA.

The feed is served by 2 scripts:
- show.php: Shows the feed in the selected format
- viewitem.php: Shows a separate feed item (essentially what is in CDATA for the show.php script)

The original implementation was perceived very negatively, because apparently it did not have a timeout for updating the feed, so it was possible to monitor messages without entering the game.

In our project, a parameter is added to the Universe settings - the RSS(Atom) update period, which works as follows:
- The Universe settings contain the variable `feedage` (in minutes, 60 by default)
- Each player has a timestamp of when he last updated the Feed (`lastfeed`)
- If the current time is less than lastfeed + feedage (the period has not ended), then no more than $MAXMSG pieces are taken from messages (50 for the commander)
- If the current time is greater than lastfeed + feedage, then lastfeed is updated with the current time (with the collection of new messages after that)

Thus, the user cannot receive updated messages more often than `feedage` minutes.

Other new fields for user record:

- feedid CHAR(32): feed id (eg 5aa28084f43ad54d9c8f7dd92f774d03)
- USER_FLAG_FEED_ENABLE flag: feed enabled
- USER_FLAG_FEED_ATOM flag: 0 - use RSS format, 1 - use Atom format
- lastfeed INT UNSIGNED

I'm not sure anyone will be interested in enabling Feed in 2025, it looks like a curious example of a previous era.