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

        // Mark the rows the database knows something about: a favourite star, the
        // age of the last spy report and how many sessions were observed. The
        // markers are appended to the name cell, the game's own markup is untouched.
        const settings = OGI.read(ctx.store);
        if (settings.highlightGalaxy) {
            const rows = document.querySelectorAll('tr');
            for (let i = 0; i < rows.length; i++) {
                const nameCell = rows[i].querySelector('span.normal');
                if (!nameCell || nameCell.getAttribute('data-ogi-marked') === '1') continue;
                const coordsLink = rows[i].querySelector('a[href*="showGalaxy"], a[onclick*="showGalaxy"]');
                const coordsMatch = /(\d+):(\d+):(\d+)/.exec(coordsLink ? coordsLink.textContent : '');
                const playerName = (nameCell.textContent || '').trim();
                const key = coordsMatch ? coordsMatch[1] + ':' + coordsMatch[2] + ':' + coordsMatch[3] : null;
                const data = ctx.store.data();
                const known = (data.players || {})[playerName];
                if (!known && !(key && (data.planets || {})[key])) continue;

                const marks = [];
                if (settings.highlightFavorites &&
                    ((data.favorites || {}).players || {})[playerName]) marks.push('★');
                if (key && (data.planets || {})[key]) {
                    const spies = (data.spies || []).filter(function (spy) {
                        return spy.coords && (spy.coords.g + ':' + spy.coords.s + ':' + spy.coords.p) === key;
                    });
                    if (spies.length) {
                        const hours = Math.max(0, Math.round((now - spies[0].date) / 3600));
                        marks.push('○' + OGI.esc(hours < 48 ? hours + 'h' : Math.round(hours / 24) + 'd'));
                    }
                    const activity = OGI.activitySummary((data.activity || {})[key]);
                    if (activity.sessions) marks.push('∪' + OGI.esc(activity.sessions));
                }
                if (!marks.length) continue;

                nameCell.setAttribute('data-ogi-marked', '1');
                const mark = document.createElement('span');
                mark.className = 'ogi-galaxy-mark';
                mark.textContent = ' ' + marks.join(' ');
                nameCell.parentNode.appendChild(mark);
            }
        }

        if (settings.tooltips) OGI.attachTooltips(document, ctx);

        // A short note in the old style, so the player sees that the system was
        // recorded. It goes to the top of the content area: the galaxy table is
        // long and the content pane has its own scrollbar.
        const note = document.createElement('div');
        note.id = 'ogi-galaxy-note';
        note.className = 'ogi-note';
        note.innerHTML = 'OGame Infinity 0.84: ' + observed + ' / ' +
            OGI.esc(ctx.i18n('TAB_PLANETS'));
        const content = document.getElementById('content') || document.body;
        if (content.firstChild) content.insertBefore(note, content.firstChild);
        else content.appendChild(note);
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
