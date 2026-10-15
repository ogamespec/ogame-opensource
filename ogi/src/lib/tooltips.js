/**
 * OGame Infinity 0.84 — hover tooltips.
 *
 * OGame Infinity shows what it knows about a player or a planet when the mouse
 * hovers over the name or the coordinates. On 0.84 the same data can be presented
 * from the extension's own database, because the pages themselves only link to the
 * galaxy.
 *
 * The tooltip is built here as plain HTML (in the panel style) and attached to the
 * elements of the current page by `attachTooltips`, which only touches elements
 * whose text really matches something in the database — the game's own overlibs
 * stay untouched.
 */
(function (root) {
    'use strict';

    const ui = typeof require === 'function' ? require('./ui.js') : root.OGI;
    const analyze = typeof require === 'function' ? require('../analyze/index.js') : root.OGI;

    const TOOLTIP_ID = 'ogi-tooltip';

    /**
     * Coordinates as "g:s:p".
     *
     * @param {object|string} coords Coordinates.
     * @returns {string} Text.
     */
    function coordsText(coords) {
        if (!coords) return '-';
        if (typeof coords === 'string') return coords;
        return coords.g + ':' + coords.s + ':' + coords.p;
    }

    /**
     * Everything the database knows about a player.
     *
     * @param {object} data Store data.
     * @param {string} name Player name.
     * @param {object} options Options: i18n.
     * @returns {string} HTML.
     */
    function playerTooltip(data, name, options) {
        const t = options.i18n;
        const player = (data.players || {})[name];
        if (!player) return '';

        const account = analyze.accountBreakdown(data, name);
        const planets = [];
        for (const key of Object.keys(data.planets || {})) {
            const planet = data.planets[key];
            if ((planet.owners || []).indexOf(name) === -1) continue;
            const activity = analyze.activitySummary((data.activity || {})[key]);
            planets.push({
                key: key,
                names: planet.names || [],
                reports: planet.reports || 0,
                lastSeen: planet.lastSeen || 0,
                activeNow: activity.activeNow,
            });
        }
        planets.sort(function (a, b) { return b.lastSeen - a.lastSeen; });

        const rows = [];
        rows.push([ui.esc(t('TT_PLAYER')), '<b>' + ui.esc(name) +
            (data.favorites && data.favorites.players && data.favorites.players[name] ? ' ★' : '') + '</b>']);
        if (account.rank !== null) rows.push([ui.esc(t('COL_RANK')), ui.num(account.rank)]);
        if (account.points !== null) rows.push([ui.esc(t('STAT_POINTS')), ui.num(account.points)]);
        if (account.fleet !== null) rows.push([ui.esc(t('STAT_FLEET_POINTS')), ui.num(account.fleet)]);
        if (account.research !== null) rows.push([ui.esc(t('STAT_RESEARCH_POINTS')), ui.num(account.research)]);
        if (account.economy !== null) rows.push([ui.esc(t('STAT_ECO_DEF')), ui.num(account.economy)]);
        if (player.ally) rows.push([ui.esc(t('COL_ALLY')), ui.esc(player.ally)]);
        if (player.home) rows.push([ui.esc(t('COL_HOME')), ui.esc(coordsText(player.home))]);
        rows.push([ui.esc(t('COL_LASTSEEN')), ui.esc(player.lastSeen ? new Date(player.lastSeen * 1000).toLocaleString() : t('NEVER'))]);

        let html = ui.panel(t('TT_PLAYER'), ui.keyValue(rows), { id: TOOLTIP_ID + '-panel' });
        if (planets.length) {
            html += ui.table([t('COL_COORDS'), t('COL_NAMES'), t('COL_REPORTS'), t('COL_ACTIVITY')],
                planets.slice(0, 10).map(function (planet) {
                    return [
                        ui.esc(planet.key),
                        ui.esc(planet.names.join(', ') || '-'),
                        ui.num(planet.reports),
                        planet.activeNow ? ui.tag(t('ACTIVE'), 'good') : ui.esc(t('NEVER')),
                    ];
                }));
        } else {
            html += '<div class="ogi-note">' + ui.esc(t('TT_NO_PLANETS')) + '</div>';
        }
        return html;
    }

    /**
     * Everything the database knows about a planet or a moon.
     *
     * @param {object} data Store data.
     * @param {string} key Coordinates "g:s:p".
     * @param {object} options Options: i18n.
     * @returns {string} HTML.
     */
    function planetTooltip(data, key, options) {
        const t = options.i18n;
        const planet = (data.planets || {})[key];
        if (!planet) return '';

        const activity = analyze.activitySummary((data.activity || {})[key]);
        const note = (data.notes || {})[key];
        const spies = (data.spies || []).filter(function (spy) {
            return spy.coords && coordsText(spy.coords) === key;
        });
        const lastSpy = spies.length ? spies[0] : null;

        const rows = [
            [ui.esc(t('COL_COORDS')), '<b>' + ui.esc(key) +
                (data.favorites && data.favorites.planets && data.favorites.planets[key] ? ' ★' : '') + '</b>'],
            [ui.esc(t('COL_NAMES')), ui.esc((planet.names || []).join(', ') || '-')],
            [ui.esc(t('COL_OWNERS')), ui.esc((planet.owners || []).join(', ') || '-')],
            [ui.esc(t('COL_LASTSEEN')), ui.esc(planet.lastSeen ? new Date(planet.lastSeen * 1000).toLocaleString() : t('NEVER'))],
            [ui.esc(t('COL_REPORTS')), ui.num(planet.reports || 0)],
            [ui.esc(t('COL_ACTIVITY')), activity.activeNow ? ui.tag(t('ACTIVE'), 'good') : ui.num(activity.sessions) + ' ' + ui.esc(t('COL_SESSIONS'))],
        ];
        if (lastSpy) {
            rows.push([ui.esc(t('TT_LAST_SPY')), ui.esc(new Date(lastSpy.date * 1000).toLocaleString())]);
            if (lastSpy.resources) {
                rows.push([ui.esc(t('COL_TOTAL')), ui.num((lastSpy.resources[700] || 0) + (lastSpy.resources[701] || 0) + (lastSpy.resources[702] || 0))]);
            }
        }
        if (note) rows.push([ui.esc(t('PL_NOTE')), ui.esc(note)]);

        return ui.panel(t('TT_PLANET'), ui.keyValue(rows), { id: TOOLTIP_ID + '-panel' });
    }

    /**
     * Create (once) the floating tooltip element.
     *
     * @returns {Element} Tooltip host.
     */
    function tooltipHost() {
        let host = document.getElementById(TOOLTIP_ID);
        if (host) return host;
        host = document.createElement('div');
        host.id = TOOLTIP_ID;
        host.style.display = 'none';
        document.body.appendChild(host);
        return host;
    }

    /**
     * Attach the tooltips to the elements of a page.
     *
     * Only elements whose text is a known player name or a known coordinate get a
     * tooltip, so the game's own markup and overlibs are left alone.
     *
     * @param {Element} root Root element.
     * @param {object} ctx Extension context.
     */
    function attachTooltips(root, ctx) {
        const data = ctx.store.data();
        const players = data.players || {};
        const planets = data.planets || {};
        const scope = root || document;
        const host = tooltipHost();

        function show(html, event) {
            if (!html) return;
            host.innerHTML = html;
            host.style.display = 'block';
            const width = host.offsetWidth || 340;
            const left = Math.min(window.innerWidth - width - 8, Math.max(4, (event.clientX || 0) + 14));
            host.style.left = left + 'px';
            host.style.top = Math.max(4, (event.clientY || 0) + 14) + 'px';
        }

        function hide() {
            host.style.display = 'none';
        }

        const anchors = scope.querySelectorAll('a, span.normal, td, th');
        for (let i = 0; i < anchors.length; i++) {
            const element = anchors[i];
            if (element.getAttribute('data-ogi-tooltip') === '1') continue;
            const text = (element.textContent || '').trim();
            if (!text || text.length > 40) continue;

            let html = '';
            if (planets[text]) {
                html = planetTooltip(data, text, { i18n: ctx.i18n });
            } else if (players[text]) {
                html = playerTooltip(data, text, { i18n: ctx.i18n });
            } else {
                const coords = /^\[?(\d{1,2}:\d{1,3}:\d{1,2})\]?$/.exec(text);
                if (coords && planets[coords[1]]) html = planetTooltip(data, coords[1], { i18n: ctx.i18n });
            }
            if (!html) continue;

            element.setAttribute('data-ogi-tooltip', '1');
            element.addEventListener('mousemove', function (event) { show(html, event); });
            element.addEventListener('mouseleave', hide);
        }
    }

    const api = {
        TOOLTIP_ID: TOOLTIP_ID,
        coordsText: coordsText,
        playerTooltip: playerTooltip,
        planetTooltip: planetTooltip,
        attachTooltips: attachTooltips,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
