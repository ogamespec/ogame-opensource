/**
 * OGame Infinity 0.84 — galaxy page.
 *
 * Records every planet, moon, owner and activity marker the player sees while
 * browsing the galaxy. This is the data behind the "Planets & players" tab: the
 * 0.84 server keeps no history, so the extension builds it from observations, the
 * same way the original OGame Infinity builds its galaxy database.
 */
(function (root) {
    'use strict';

    const OGI = root.OGI || {};

    /**
     * Handle the galaxy page.
     *
     * @param {object} ctx Extension context.
     */
    OGI.mountGalaxy = function (ctx) {
        const pageHtml = document.documentElement ? document.documentElement.outerHTML : ctx.source;
        const parsed = OGI.parseGalaxyPage(pageHtml, { loca: ctx.loca, lang: ctx.lang });
        const now = Math.floor(Date.now() / 1000);
        let observed = 0;

        for (const planet of parsed.planets) {
            ctx.store.observePlanet({
                coords: planet.coords,
                name: planet.name,
                owner: planet.owner,
                date: now,
            });
            if (planet.owner) {
                ctx.store.observePlayer({
                    name: planet.owner,
                    date: now,
                    coords: planet.coords,
                });
            }
            if (planet.moon) {
                ctx.store.observePlanet({
                    coords: planet.moon.coords,
                    name: planet.moon.name,
                    owner: planet.owner,
                    date: now,
                    type: 'moon',
                });
            }
            if (planet.activity) {
                ctx.store.observeActivity(planet.coords.g, planet.coords.s, planet.coords.p, planet.activity, now);
            }
            observed++;
        }

        ctx.store.data().meta.lastScan = now;
        ctx.store.save();

        if (!observed) return;

        // A short note in the old style, so the player sees that the system was
        // recorded without having to open the analyzer panel.
        const note = document.createElement('div');
        note.id = 'ogi-galaxy-note';
        note.className = 'ogi-note';
        note.innerHTML = 'OGame Infinity 0.84: ' + observed + ' / ' +
            OGI.esc(ctx.i18n('TAB_PLANETS'));
        const content = document.getElementById('content') || document.body;
        content.appendChild(note);
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
