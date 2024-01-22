# JavaScript timers

Countdown timers are used extensively in the game. In doing so, the main PHP script returns only the timestamp in seconds, how much time is left until the event is over, 
and then the JavaScript script is used to output the value.

## Instances

Timer processing in different variations can be found in the following modules:
- /game/js/ocnt.json: Included in flotten2.php; used to show ACS union times. :warning: Works with slowdown, see bug below.

![ocnt_timer](/imgstore/ocnt_timer.png)

- /game/js/utilities.json: Included in most of the pages. Contains an implementation of t(). :warning: Works with slowdown, see bug below.
- /game/pages/buildings.php: Fleet and defense construction queue. The script is integrated in HTML. Kind of does not contain a bug with slowdown.
- /game/pages/b_building.php: Building construction queue. Script integrated into HTML; :warning: Works with slowdown, see bug below.
- /game/pages/overview.php: Building construction in Overview. The method is called t_building(). :warning: Works with slowdown, see bug below.

## Timer slowdown bug

The countdown timer is slower than necessary because the remaining seconds counter is decremented inside the t() method (`pp = pp - 1`), while the `setTimeout` method does not guarantee accurate generation of intervals.
Therefore, a significant lag accumulates over time.

To fix this, to know exactly how much time has passed, you should check the timestamp of the event each time comparing it with the real time (now).

Also, after returning to the tab, you need to call `t()` again to update the value of the timers:

```javascript
document.addEventListener("visibilitychange", function() {
    if (!document.hidden) {
        t();
    }
});
```

#85 

This fix is included in the set of fixed bugs of vanilla OGame 0.84
