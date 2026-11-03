/**
 * OGame Infinity 0.84 — resource bar parser.
 *
 * Every framed page carries the player's resources in the header
 * (game/core/page.php, `ResourceList`): a `table#resources` whose columns are
 * metal, crystal, deuterium, dark matter and energy, with the values as plain
 * `<font>25.047</font>` text and the energy as `359/529` (production/consumption).
 *
 * The extension needs the current stock for the needs calculator, so it reads it
 * from the page it is already on instead of asking the server for anything.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;

    /**
     * Parse the resource bar of a page.
     *
     * @param {string} source Page source.
     * @returns {{metal: number, crystal: number, deuterium: number, darkmatter: number,
     *            energy: number, energyMax: number}|null} Resources or null.
     */
    function parseResourceBar(source) {
        const table = /<table[^>]*id\s*=\s*['"]resources['"][\s\S]*?<\/table>/i.exec(source);
        if (!table) return null;

        // The table has a label row ("Metal", "Crystal", …) and a value row, so
        // only the numeric <font> contents are kept.
        const values = [];
        const re = /<font[^>]*>([\s\S]*?)<\/font>/gi;
        let match;
        while ((match = re.exec(table[0])) !== null) {
            const text = html.normalizeText(match[1]);
            if (!/\d/.test(text)) continue;
            values.push(text);
        }
        if (values.length < 3) return null;

        // Energy is rendered as "359</font>/529" (production / consumption), so the
        // maximum is read from the raw markup instead of the font contents.
        const energy = /<font[^>]*>\s*([\d.,]+)\s*<\/font>\s*\/\s*([\d.,]+)/.exec(table[0]);
        const out = {
            metal: numbers.parseGameNumber(values[0]),
            crystal: numbers.parseGameNumber(values[1]),
            deuterium: numbers.parseGameNumber(values[2]),
            darkmatter: values.length > 3 ? numbers.parseGameNumber(values[3]) : 0,
            energy: energy ? numbers.parseGameNumber(energy[1]) : numbers.parseGameNumber(values[4]),
            energyMax: energy ? numbers.parseGameNumber(energy[2]) : null,
        };
        for (const key of Object.keys(out)) {
            if (out[key] !== null && isNaN(out[key])) out[key] = 0;
        }
        return out;
    }

    const api = { parseResourceBar: parseResourceBar };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
