/**
 * OGame Infinity 0.84 — spy report parser.
 *
 * A 0.84 spy report (game/core/fleet.php, `SpyArrive`) is nothing but a stored
 * HTML fragment built from localization templates:
 *
 *     <table width=400><tr><td class=c colspan=4>Resources on Home <a
 *       onclick="showGalaxy(1,3,4);" href="#">[1:3:4]</a> (Player 'PlayerTwo')<br />
 *       at 09-01 10:43:57.</td></tr>
 *     <tr><td>Metal:</td><td>20.000</td><td>Cristal:</td><td>12.000</td></tr>
 *     ...
 *     <table width=400><tr><td class=c colspan=4>Fleet     </td></tr>
 *     <td>Small Cargo</td><td>5</td> ...
 *
 * The same fragment is shown in the popup (`page=bericht`) and inlined into the
 * messages page when the "show intelligence data partially" option is off, so the
 * parser accepts either source.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;
    const templates = typeof require === 'function' ? require('../lib/templates.js') : root.OGI;

    // Resource ids of the game (game/core/techs.php).
    const GID_RC_METAL = 700;
    const GID_RC_CRYSTAL = 701;
    const GID_RC_DEUTERIUM = 702;
    const GID_RC_ENERGY = 703;
    const GID_RC_DM = 704;

    /**
     * Read one "label: amount" pair of the resource block.
     *
     * @param {string} text Normalized report text.
     * @param {string|null} label Localized label, colon included.
     * @returns {number|null} Amount.
     */
    function resourceAmount(text, label) {
        if (!label) return null;
        const needle = html.normalizeText(label);
        if (needle === '') return null;
        const re = new RegExp(templates.escapeRegex(needle) + '\\s*([\\d.,]+)');
        const match = re.exec(text);
        if (!match) return null;
        const value = numbers.parseGameNumber(match[1]);
        return isNaN(value) ? null : value;
    }

    /**
     * Parse one report section (Fleet / Defence / Buildings / Research).
     *
     * @param {string} reportHtml Report source.
     * @param {string|null} header Localized section header.
     * @param {Object<string, number>} names Name to object id map.
     * @returns {Object<number|string, number>} Amounts by object id (unknown names stay strings).
     */
    function section(reportHtml, header, names) {
        const out = {};
        if (!header) return out;
        const cell = html.findCell(reportHtml, header);
        if (!cell) return out;
        const pairs = html.pairs(reportHtml.slice(cell.end));
        for (const pair of pairs) {
            const value = numbers.parseGameNumber(pair.value);
            if (isNaN(value) || value <= 0) continue;
            const id = Object.prototype.hasOwnProperty.call(names, pair.name) ? names[pair.name] : null;
            out[id === null ? pair.name : id] = value;
        }
        return out;
    }

    /**
     * Parse a spy report.
     *
     * @param {string} source Report HTML (popup page or inlined message body).
     * @param {object} ctx Context: loca, lang.
     * @returns {object|null} Parsed report or null when the source is not a spy report.
     */
    function parseSpyReport(source, ctx) {
        const loca = ctx && ctx.loca;
        const lang = (ctx && ctx.lang) || 'en';
        if (!loca) return null;

        const text = html.normalizeText(source);
        const headerText = loca.get(lang, 'SPY_RESOURCES');
        if (!headerText || text.indexOf(html.normalizeText(headerText).split('#')[0].trim()) === -1) {
            // Not a spy report at all.
            if (text.indexOf('SPY') === -1 && !/Metal|Cristal/i.test(text)) return null;
        }

        // Header cell: the first cell holding the coordinates link.
        const headerCellRe = /<td[^>]*class\s*=\s*["']?c\b[^>]*>([\s\S]*?)(?=<td\b|<\/table>)/i;
        const headerMatch = headerCellRe.exec(source);
        const headerHtml = headerMatch ? headerMatch[1] : source;
        const headerCellText = html.normalizeText(headerHtml);

        const coordsList = html.coordinates(headerHtml);
        const coords = coordsList.length ? coordsList[0] : null;

        // The planet name sits between the "Resources on" label and the
        // coordinates link / "(Player '...')" part, so both are cut off. The
        // template of SPY_RESOURCES ("Resources on #1") ends with the planet name
        // and would swallow the rest of the header if matched greedily.
        let planet = null;
        const resourcesLabel = html.normalizeText(loca.get(lang, 'SPY_RESOURCES') || '');
        // The placeholder is "#1", not "#", so split on the whole placeholder.
        const resourcesParts = resourcesLabel.split(/#\d/);
        const resourcesPrefix = resourcesParts[0].trim();
        // Some languages put the planet name at the end of the label ("#1 のリソース").
        const resourcesSuffix = resourcesParts.length > 1 ? resourcesParts[resourcesParts.length - 1].trim() : '';
        const playerLabel = html.normalizeText(loca.get(lang, 'SPY_PLAYER') || '');
        const playerPrefix = playerLabel.split(/#\d/)[0];
        let withoutCoords = headerCellText.replace(/\[[\d\s:]+\]/g, ' ').replace(/\s+/g, ' ').trim();
        if (resourcesPrefix && withoutCoords.indexOf(resourcesPrefix) === 0) {
            withoutCoords = withoutCoords.slice(resourcesPrefix.length).trim();
        }
        const playerAt = playerPrefix ? withoutCoords.indexOf(playerPrefix) : -1;
        if (playerAt > 0) withoutCoords = withoutCoords.slice(0, playerAt).trim();
        if (resourcesSuffix && withoutCoords.slice(-resourcesSuffix.length) === resourcesSuffix) {
            withoutCoords = withoutCoords.slice(0, -resourcesSuffix.length).trim();
        }
        if (withoutCoords !== '') planet = withoutCoords;

        const playerMatch = templates.matchTemplate(loca.get(lang, 'SPY_PLAYER'), headerCellText);
        const player = playerMatch && playerMatch[1] ? playerMatch[1].trim() : null;
        const dateText = playerMatch && playerMatch[2] ? playerMatch[2].trim() : null;

        const names = loca.nameToId(lang);
        const resources = {};
        resources[GID_RC_METAL] = resourceAmount(text, loca.get(lang, 'SPY_M'));
        resources[GID_RC_CRYSTAL] = resourceAmount(text, loca.get(lang, 'SPY_K'));
        resources[GID_RC_DEUTERIUM] = resourceAmount(text, loca.get(lang, 'SPY_D'));
        resources[GID_RC_ENERGY] = resourceAmount(text, loca.get(lang, 'SPY_E'));

        const counterValues = templates.matchTemplate(loca.get(lang, 'SPY_COUNTER'), text);
        const counter = counterValues && counterValues[1] ? numbers.parseGameNumber(counterValues[1]) : null;

        return {
            coords: coords,
            planet: planet,
            player: player,
            dateText: dateText,
            resources: resources,
            fleet: section(source, loca.get(lang, 'SPY_FLEET'), names),
            defence: section(source, loca.get(lang, 'SPY_DEFENSE'), names),
            buildings: section(source, loca.get(lang, 'SPY_BUILDINGS'), names),
            research: section(source, loca.get(lang, 'SPY_RESEARCH'), names),
            counter: counter === null || isNaN(counter) ? null : counter,
            dm: null,
        };
    }

    const api = {
        GID_RC_METAL: GID_RC_METAL,
        GID_RC_CRYSTAL: GID_RC_CRYSTAL,
        GID_RC_DEUTERIUM: GID_RC_DEUTERIUM,
        GID_RC_ENERGY: GID_RC_ENERGY,
        GID_RC_DM: GID_RC_DM,
        parseSpyReport: parseSpyReport,
        section: section,
        resourceAmount: resourceAmount,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
