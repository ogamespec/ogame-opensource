/**
 * OGame Infinity 0.84 — the production page ("Сырьё", `page=resources`).
 *
 * The page lists every mine of the current planet with its level and the production
 * the server itself calculated, plus the energy column and the summary rows. Those
 * numbers are the ground truth for the extension: they already contain the planet
 * factor, the temperature, the officer bonuses and the economy speed of the universe,
 * so `analyze/production.js` calibrates against them and can then predict the next
 * level exactly.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;
    const production = typeof require === 'function' ? require('../analyze/production.js') : root.OGI;

    const GID_RC_METAL = 700;
    const GID_RC_CRYSTAL = 701;
    const GID_RC_DEUTERIUM = 702;
    const GID_RC_ENERGY = 703;
    const COLUMNS = [GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM, GID_RC_ENERGY];

    /**
     * Read one row of the production table.
     *
     * The label looks like „Рудник по добыче металла (уровень 5)" in any language, so
     * the level is simply the last number of the label and the name everything before
     * the bracket.
     *
     * @param {string} chunk Row HTML.
     * @param {object} ctx Context: loca, lang.
     * @returns {object|null} Row: gid, name, level, production; null when it is not a mine.
     */
    function parseRow(chunk, ctx) {
        const cells = html.cells(chunk);
        if (cells.length < 6) return null;
        const text = html.normalizeText(cells[0].text);
        const levelMatch = /(\d+)\s*\)?\s*$/.exec(text);
        const name = text.replace(/\([^)]*\)?\s*$/, '').trim();
        const gid = objectGid(name, ctx);
        if (gid === null) return null;

        const row = {
            gid: gid,
            name: name,
            level: levelMatch ? parseInt(levelMatch[1], 10) : null,
            production: {},
        };
        for (let i = 0; i < COLUMNS.length; i++) {
            const cell = cells[2 + i];
            if (!cell) continue;
            const value = numbers.parseGameNumber(String(cell.text).split('/')[0]);
            if (!isNaN(value)) row.production[COLUMNS[i]] = value;
        }
        return row;
    }

    /**
     * Object id of a localized object name.
     *
     * @param {string} name Name.
     * @param {object} ctx Context: loca, lang.
     * @returns {number|null} Object id.
     */
    function objectGid(name, ctx) {
        if (!ctx || !ctx.loca || !ctx.lang || !name) return null;
        const map = ctx.loca.nameToId(ctx.lang);
        return Object.prototype.hasOwnProperty.call(map, name) ? map[name] : null;
    }

    /**
     * Parse the production page.
     *
     * @param {string} source Page source.
     * @param {object} [ctx] Context: loca, lang, speed.
     * @returns {{rows: Array, factors: object, planet: string|null}} Parsed page.
     */
    function parseProductionPage(source, ctx) {
        const context = ctx || {};
        const rows = [];
        const chunks = String(source).split(/<tr[ >]/i);
        for (const chunk of chunks) {
            const text = html.normalizeText(chunk);
            if (!/(\d+)/.test(text)) continue;
            if (!/[Уу]ровень|level|Stufe|nivel|livello|niveau|レベル/i.test(text)) continue;
            const row = parseRow(chunk, context);
            if (!row || row.level === null) continue;
            rows.push(row);
        }

        // The server's own numbers give the exact factor of every building. The officer
        // icons are the same files in every language (img/geologe_ikon.gif,
        // img/ingenieur_ikon.gif), which is how the bonuses are detected.
        const factors = {};
        const officer = /geologe_ikon\.gif/i.test(source);
        const engineer = /ingenieur_ikon\.gif/i.test(source);
        for (const row of rows) {
            const resource = production.production(row.gid, row.level).resource;
            const shown = row.production[resource];
            if (shown === undefined) continue;
            factors[row.gid] = production.calibrate(shown, row.gid, row.level, {
                speed: context.speed || 1,
                temp: context.temp,
                officer: officer,
                engineer: engineer,
            });
        }

        const planet = /планете\s+&quot;([^&]+)&quot;|planet\s+&quot;([^&]+)&quot;/i.exec(source);
        // The fusion plant reveals the energy technology of the account.
        let energyTech = 0;
        for (const row of rows) {
            if (row.gid !== production.GID_B_FUSION) continue;
            const energyTechLevel = production.implyEnergyTech(row.production[GID_RC_ENERGY], row.level, {
                engineer: engineer,
                factor: 1,
            });
            if (energyTechLevel > energyTech) energyTech = energyTechLevel;
        }
        return {
            rows: rows,
            factors: factors,
            officers: { geologist: officer, engineer: engineer },
            energyTech: energyTech,
            planet: planet ? (planet[1] || planet[2]) : null,
        };
    }

    const api = {
        COLUMNS: COLUMNS,
        objectGid: objectGid,
        parseProductionPage: parseProductionPage,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
