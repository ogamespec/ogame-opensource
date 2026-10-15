/**
 * OGame Infinity 0.84 — overview event list parser.
 *
 * The overview (game/pages/overview.php + overview_events.php) lists the fleets in
 * the air. Every event row carries the exact end of the flight in a non-standard
 * attribute of its countdown element, which is what makes real fleet-save timers
 * possible on 0.84:
 *
 *     <tr class='flight'>
 *     <th><div id='bxx1' title='300'star='1788259167'></div></th>
 *     <th colspan='3'><span class='flight owndeploy'>Your … from planets Home
 *       <a href="javascript:showGalaxy(1,1,4)" owndeploy>[1:1:4]</a> sent to Moon
 *       <a …>[1:1,4]</a>. Mission: <span class='class'>Deploy</span></span></th></tr>
 *
 * `star` is the unix timestamp of the arrival, `title` the seconds the game's own
 * script counts down. The mission is a class (`owndeploy`, `ownespionage`, … ;
 * enemy fleets have no `own` prefix) and the localised mission name sits in a
 * `<span class='class'>`.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;
    const texts = typeof require === 'function' ? require('../parsers/texts.js') : root.OGI;

    const DIRECTIONS = {
        flight: 'outgoing',
        return: 'returning',
        holding: 'holding',
        '': 'fleet',
    };

    // Mission classes of the overview (game/pages/overview_events.php).
    const MISSIONS = {
        attack: 'attack',
        federation: 'acs',
        transport: 'transport',
        deploy: 'deploy',
        hold: 'hold',
        espionage: 'spy',
        colony: 'colonize',
        harvest: 'recycle',
        destroy: 'destroy',
        expedition: 'expedition',
        missile: 'missile',
    };

    /**
     * Parse the cargo tooltip of an event ("Transport: Metal: 5.000 Crystal: 2.000
     * Deuterium: 0"), using the localized resource names.
     *
     * @param {string} title Tooltip text.
     * @param {object} ctx Context: loca, lang.
     * @returns {{metal: number, crystal: number, deuterium: number, total: number}|null} Cargo.
     */
    function parseCargo(title, ctx) {
        if (!title) return null;
        const text = html.normalizeText(title);
        const cargo = { metal: 0, crystal: 0, deuterium: 0, total: 0 };
        const resources = [[700, 'metal'], [701, 'crystal'], [702, 'deuterium']];
        let found = false;
        for (const resource of resources) {
            const name = ctx.loca.get(ctx.lang, 'NAME_' + resource[0]);
            if (!name) continue;
            const re = new RegExp(html.normalizeText(name) + '\\s*:?\\s*([\\d.,]+)', 'i');
            const match = re.exec(text);
            if (!match) continue;
            const value = numbers.parseGameNumber(match[1]);
            if (isNaN(value)) continue;
            cargo[resource[1]] = value;
            found = true;
        }
        if (!found) return null;
        cargo.total = cargo.metal + cargo.crystal + cargo.deuterium;
        return cargo;
    }

    /**
     * Parse the event list of the overview page.
     *
     * @param {string} source Page source.
     * @param {object} ctx Context: loca, lang.
     * @returns {{events: Array}} Fleets in the air, sorted by arrival.
     */
    function parseEvents(source, ctx) {
        const events = [];
        const rows = html.rowsEx(source);
        for (const row of rows) {
            const timer = /id\s*=\s*['"]bxx(\d+)['"][^>]*?title\s*=\s*['"]?(\d+)['"]?[^>]*?star\s*=\s*['"]?(\d+)/i.exec(row.html);
            if (!timer) continue;

            const directionClass = /class\s*=\s*['"](flight|return|holding)?['"]/i.exec(row.attrs);
            // The span carries either "<direction> <mission>" (own fleets) or just
            // "<mission>" (foreign fleets, e.g. class='attack').
            const missionMatch = /class\s*=\s*['"](?:(?:flight|return|holding)\s+)?(own)?([a-z]+)['"]/i.exec(row.html);
            const missionName = missionMatch ? missionMatch[2].toLowerCase() : null;
            const missionText = /<span\s+class\s*=\s*['"]class['"]\s*>([\s\S]*?)<\/span>/i.exec(row.html);
            const known = !!(missionName && MISSIONS[missionName]);

            const event = {
                id: parseInt(timer[1], 10),
                secondsLeft: parseInt(timer[2], 10),
                endsAt: parseInt(timer[3], 10),
                direction: DIRECTIONS[(directionClass && directionClass[1]) || ''] || 'fleet',
                own: missionMatch ? !!missionMatch[1] : true,
                missionClass: known ? missionName : null,
                mission: known ? MISSIONS[missionName] : null,
                missionText: missionText ? html.normalizeText(missionText[1]) : null,
                hostile: known && !missionMatch[1] && DIRECTIONS[(directionClass && directionClass[1]) || ''] !== 'returning',
                coords: html.coordinates(row.html),
                ships: {},
                shipCount: null,
                cargo: null,
            };

            // Foreign fleets do not show their composition, only a ship count, and
            // even that label is localized ("Number of ships" / "Численность кораблей").
            const countLabel = ctx && ctx.loca ? ctx.loca.get(ctx.lang, 'EVENT_FLEET_COUNT') : null;
            if (countLabel) {
                const countRe = new RegExp(html.normalizeText(countLabel) + '\\s*:?\\s*(\\d+)', 'i');
                const count = countRe.exec(html.normalizeText(html.decodeEntities(row.html)));
                if (count) event.shipCount = parseInt(count[1], 10);
            }
            if (event.shipCount === null) {
                const count = /Number of ships:\s*(\d+)/i.exec(html.decodeEntities(row.html));
                if (count) event.shipCount = parseInt(count[1], 10);
            }

            // Ship names and the cargo are in tooltips; the overlib payload is
            // HTML-escaped, the ship list is also repeated in a title attribute.
            const overlib = /onmouseover\s*=\s*['"][^'"]*overlib\(&quot;([\s\S]*?)&quot;/i.exec(row.html);
            let shipText = '';
            if (overlib) shipText += html.decodeEntities(overlib[1]) + ' ';
            const titles = row.html.match(/title\s*=\s*['"]([^'"]*)['"]/gi) || [];
            for (const title of titles) {
                const value = /title\s*=\s*['"]([^'"]*)['"]/i.exec(title)[1];
                shipText += html.decodeEntities(value) + ' ';
            }
            if (ctx && ctx.loca) {
                event.ships = texts.findNamedAmounts(html.normalizeText(shipText), ctx);
                for (const title of titles) {
                    const value = /title\s*=\s*['"]([^'"]*)['"]/i.exec(title)[1];
                    if (/[:]/.test(value)) {
                        const cargo = parseCargo(value, ctx);
                        if (cargo && (!event.cargo || cargo.total > event.cargo.total)) event.cargo = cargo;
                    }
                }
            }

            events.push(event);
        }

        events.sort(function (a, b) { return a.endsAt - b.endsAt; });
        return { events: events };
    }

    const api = {
        DIRECTIONS: DIRECTIONS,
        MISSIONS: MISSIONS,
        parseCargo: parseCargo,
        parseEvents: parseEvents,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
