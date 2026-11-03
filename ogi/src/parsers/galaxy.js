/**
 * OGame Infinity 0.84 — galaxy page parser.
 *
 * The galaxy page (game/pages/galaxy.php) is the only 0.84 page that exposes
 * foreign planets without an espionage report: the planet and moon names come from
 * the overlib tooltips ("Planet Home [1:1:4]", "Moon Moon [1:1:4]"), the owner
 * from a `<span class="normal">` cell, the debris field from the recycle button
 * (`doit(8, g, s, p, 2, <harvesters>)`) and the activity from the marker the
 * server appends to the planet name: "(*)" for less than 15 minutes, "(N min)" up
 * to an hour.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;

    /**
     * Parse the activity marker the server appends to a planet name.
     *
     * @param {string} text Cell text.
     * @returns {{marker: string|null, name: string, minutes: number|null}} Activity and clean name.
     */
    function parseActivity(text) {
        const clean = html.normalizeText(text);
        const active = /\(\s*\*\s*\)/.exec(clean);
        if (active) {
            return { marker: '*', minutes: 0, name: clean.replace(/\(\s*\*\s*\)/, '').trim() };
        }
        const ago = /\(\s*(\d+)\s*min\s*\)/i.exec(clean);
        if (ago) {
            return { marker: ago[1], minutes: parseInt(ago[1], 10), name: clean.replace(/\(\s*\d+\s*min\s*\)/i, '').trim() };
        }
        return { marker: null, minutes: null, name: clean };
    }

    /**
     * Parse a galaxy page.
     *
     * @param {string} pageHtml Page source.
     * @param {object} ctx Context: loca, lang.
     * @returns {{planets: Array, players: Array}} Observed objects.
     */
    function parseGalaxyPage(pageHtml, ctx) {
        const out = { planets: [], players: [] };
        const rows = html.rowsEx(pageHtml) || [];
        const templates = typeof require === 'function' ? require('../lib/templates.js') : root.OGI;
        const loca = ctx && ctx.loca;
        const lang = (ctx && ctx.lang) || 'en';
        const planetLabel = loca ? (loca.get(lang, 'GALAXY_PLANET') || 'Planet') : 'Planet';
        const moonLabel = loca ? (loca.get(lang, 'GALAXY_MOON') || 'Moon') : 'Moon';

        /**
         * Find "<label> <name> [g:s:p]" inside the overlib header cell of a tooltip.
         *
         * @param {string} row Row source.
         * @param {string} label Localized label.
         * @returns {{name: string, coords: object}|null} Match.
         */
        function findTooltip(row, label) {
            const re = new RegExp(
                'class\\s*=\\s*c[^>]*>\\s*' + templates.escapeRegex(html.normalizeText(label)) +
                '\\s+([^<\\[]*?)\\s*\\[\\s*(\\d+)\\s*:\\s*(\\d+)\\s*:\\s*(\\d+)\\s*\\]'
            );
            const match = re.exec(row);
            if (!match) return null;
            return {
                name: html.decodeEntities(match[1]).trim(),
                coords: { g: parseInt(match[2], 10), s: parseInt(match[3], 10), p: parseInt(match[4], 10) },
            };
        }

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i].html;
            const planetInfo = findTooltip(row, planetLabel);
            if (!planetInfo) continue;

            const entry = {
                coords: planetInfo.coords,
                type: 'planet',
                name: planetInfo.name,
                owner: null,
                activity: null,
                debrisHarvesters: null,
                moon: null,
            };

            // The activity marker belongs to the planet name cell; it applies to
            // the planet and to its moon.
            const cells = html.cells(row);
            for (const cell of cells) {
                if (cell.text.indexOf(entry.name) === 0) {
                    const activity = parseActivity(cell.text);
                    if (activity.marker) entry.activity = activity.marker;
                }
            }

            const owner = /<span class="normal">([^<]*)<\/span>/i.exec(row);
            if (owner) entry.owner = html.decodeEntities(owner[1]).trim();

            const recycle = /doit\(\s*8\s*,\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*2\s*,\s*(\d+)\s*\)/i.exec(row);
            if (recycle) entry.debrisHarvesters = parseInt(recycle[1], 10);

            const moonInfo = findTooltip(row, moonLabel);
            if (moonInfo && moonInfo.coords.p === planetInfo.coords.p) {
                entry.moon = { coords: moonInfo.coords, name: moonInfo.name };
            }

            out.planets.push(entry);
            if (entry.owner) out.players.push(entry.owner);
        }

        return out;
    }

    const api = {
        parseActivity: parseActivity,
        parseGalaxyPage: parseGalaxyPage,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
