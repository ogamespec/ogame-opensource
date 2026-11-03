/**
 * OGame Infinity 0.84 — the production page ("Сырьё", `page=resources`).
 *
 * The page lists every mine of the current planet with its level and the production
 * the server itself calculated, plus the energy column and the summary rows. Those
 * numbers are the ground truth for the extension: they already contain the planet
 * factor, the temperature, the officer bonuses and the economy speed of the universe,
 * so `analyze/production.js` calibrates against them and can then predict the next
 * level exactly.
 *
 * Servers run different revisions of the engine, so the rows are read twice: first the
 * strict way (the localized `BUILD_LEVEL` template next to the name, as the engine
 * prints it), and — only when that finds nothing — a tolerant way that takes the label
 * as "the first cell with words", reads the level from a bracket and identifies the
 * building by its production column.
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
     * Level pattern of the page, built from the localization of the server.
     *
     * `BUILD_LEVEL` is „уровень #1" (or „level #1", „Stufe #1", …), which is what the
     * engine prints next to the name in the production table.
     *
     * @param {object} ctx Context: loca, lang.
     * @returns {RegExp} Pattern.
     */
    function levelRegexp(ctx) {
        const template = ctx && ctx.loca && ctx.lang ? ctx.loca.get(ctx.lang, 'BUILD_LEVEL') : null;
        if (template) {
            const parts = String(template).split('#1');
            if (parts.length === 2) {
                const escape = function (text) {
                    return html.normalizeText(text).replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s*');
                };
                return new RegExp(escape(parts[0]) + '\\s*(\\d+)');
            }
        }
        return /(?:уровень|level|stufe|nivel|livello|niveau|レベル)\s*:?\s*(\d+)/i;
    }

    /**
     * Normalize a name for the comparison: letters and digits only, lower case.
     *
     * @param {string} name Name.
     * @returns {string} Normalized name.
     */
    function normalizeName(name) {
        return String(name).toLowerCase().replace(/[^a-zа-яё0-9]+/gi, '');
    }

    /**
     * Object id of a localized object name.
     *
     * Servers run different revisions with slightly different wordings, so the lookup
     * goes from the exact name to a normalized comparison and finally to a containment
     * match, instead of failing on a comma.
     *
     * @param {string} name Name.
     * @param {object} ctx Context: loca, lang.
     * @returns {number|null} Object id.
     */
    function objectGid(name, ctx) {
        if (!ctx || !ctx.loca || !ctx.lang || !name) return null;
        const map = ctx.loca.nameToId(ctx.lang);
        if (Object.prototype.hasOwnProperty.call(map, name)) return map[name];

        const wanted = normalizeName(name);
        if (!wanted) return null;
        let best = null;
        let bestLength = 0;
        for (const key of Object.keys(map)) {
            const candidate = normalizeName(key);
            if (!candidate) continue;
            if (candidate === wanted) return map[key];
            if (candidate.length > 4 && (wanted.indexOf(candidate) !== -1 || candidate.indexOf(wanted) !== -1)) {
                if (candidate.length > bestLength) {
                    best = map[key];
                    bestLength = candidate.length;
                }
            }
        }
        return best;
    }

    /**
     * Guess the object from the row itself when the name is not known.
     *
     * The production table keeps its columns: metal, crystal, deuterium, energy — so the
     * column that carries the number tells which mine the row is, and among the two
     * energy buildings the wording or the order decides.
     *
     * @param {Array} cells Row cells.
     * @param {string} name Row name.
     * @param {object} seen Already mapped gids.
     * @param {number} labelIndex Index of the label cell.
     * @returns {number|null} Object id.
     */
    function guessGid(cells, name, seen, labelIndex) {
        const lower = String(name).toLowerCase();
        if (/fusion|термоядер|fusi|termo/.test(lower)) return 12;
        if (/solar|солнеч|sonnen|solare|solaire|太陽/.test(lower)) return 4;
        if (/metall|металл/.test(lower)) return 1;
        if (/kristall|кристалл|crystal/.test(lower)) return 2;
        if (/deut|дейтер/.test(lower)) return 3;

        const start = labelIndex === undefined ? 0 : labelIndex;
        for (let i = start + 1; i < cells.length; i++) {
            const value = numbers.parseGameNumber(String(cells[i].text).split('/')[0]);
            if (!(value > 0)) continue;
            const gid = [1, 2, 3, 4][i - start - 1];
            if (gid === undefined) return null;
            if (gid === 4 && seen && seen[4]) return 12;
            if (!seen || !seen[gid]) return gid;
        }
        return null;
    }

    /**
     * Read the rows the strict way: the engine prints the level next to the name.
     *
     * @param {string} source Page source.
     * @param {object} ctx Context.
     * @returns {Array} Rows.
     */
    function strictRows(source, ctx) {
        const pattern = levelRegexp(ctx);
        const rows = [];
        for (const chunk of String(source).split(/<tr[ >]/i)) {
            const cells = html.cells(chunk);
            if (cells.length < 5) continue;
            const text = html.normalizeText(cells[0].text);
            // The level is normally part of the name cell, but some revisions print it in
            // a cell of its own, so the whole row is searched as well.
            const match = pattern.exec(text) || pattern.exec(html.normalizeText(chunk));
            if (!match) continue;
            const name = text.replace(/\([^)]*\)?\s*$/, '').replace(/\s*\d+\s*$/, '').trim();
            const gid = objectGid(name, ctx);
            if (gid === null || gid >= 700) continue;   // resource rows are not mines
            const row = { gid: gid, name: name, level: parseInt(match[1], 10), production: {} };
            for (let i = 0; i < COLUMNS.length; i++) {
                const cell = cells[2 + i];
                if (!cell) continue;
                const value = numbers.parseGameNumber(String(cell.text).split('/')[0]);
                if (!isNaN(value)) row.production[COLUMNS[i]] = value;
            }
            if (!row.production[COLUMNS[0]] && !row.production[COLUMNS[1]] && !row.production[COLUMNS[2]] &&
                !row.production[COLUMNS[3]]) continue;
            rows.push(row);
        }
        return rows;
    }

    /**
     * Read the rows the tolerant way, for servers whose table differs from the engine
     * this port was written against.
     *
     * @param {string} source Page source.
     * @param {object} ctx Context.
     * @returns {Array} Rows.
     */
    function tolerantRows(source, ctx) {
        const rows = [];
        const seen = {};
        const unknown = [];
        for (const chunk of String(source).split(/<tr[ >]/i)) {
            const cells = html.cells(chunk);
            if (cells.length < 5) continue;

            let labelIndex = -1;
            for (let i = 0; i < cells.length; i++) {
                if ((html.normalizeText(cells[i].text).match(/[a-zа-яё]{3,}/i) || []).length) {
                    labelIndex = i;
                    break;
                }
            }
            if (labelIndex === -1) continue;

            const label = html.normalizeText(cells[labelIndex].text);
            // The engine always prints the level word next to the name, so the tolerant
            // path insists on it too — that is what keeps unrelated tables (resource
            // summaries of other pages) from being read as mines. The level may sit in a
            // cell of its own, so the whole row is searched as well.
            const match = levelRegexp(ctx).exec(label) || levelRegexp(ctx).exec(html.normalizeText(chunk));
            const name = label.replace(/\([^)]*\)?\s*$/, '').replace(/\s*\d+\s*$/, '').trim();
            if (!name) continue;

            let gid = objectGid(name, ctx);
            if (gid === null || gid >= 700) gid = guessGid(cells, name, seen, labelIndex);
            if (gid === null) {
                if (unknown.length < 6) unknown.push(name);
                continue;
            }
            if (seen[gid]) continue;
            seen[gid] = true;

            const row = { gid: gid, name: name, level: match ? parseInt(match[1], 10) : null, production: {} };
            const collected = [];
            for (let i = labelIndex + 1; i < cells.length; i++) {
                const cell = cells[i];
                if (i === labelIndex + 1 && !/\d/.test(cell.text)) continue;      // officer icons
                const value = numbers.parseGameNumber(String(cell.text).split('/')[0]);
                if (!isNaN(value)) collected.push(value);
            }
            for (let i = 0; i < COLUMNS.length && i < collected.length; i++) {
                row.production[COLUMNS[i]] = collected[i];
            }
            // A mine produces exactly one resource, so a row that "produces" metal,
            // crystal and deuterium at once is a summary or an unrelated table.
            const produced = [GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM]
                .filter(function (gid) { return row.production[gid] > 0; }).length;
            if (produced > 1) continue;
            if (row.level !== null && (row.level < 0 || row.level > 40)) continue;
            // Without a level the row is not an object of this planet (a resource or a
            // summary line), so the tolerant path insists on one.
            if (row.level === null) continue;
            rows.push(row);
        }
        return { rows: rows, unknown: unknown };
    }

    /**
     * Parse the production page.
     *
     * @param {string} source Page source.
     * @param {object} [ctx] Context: loca, lang, speed.
     * @returns {{rows: Array, factors: object, planet: string|null, tolerant: boolean}} Page.
     */
    function parseProductionPage(source, ctx) {
        const context = ctx || {};
        let rows = strictRows(source, context);
        let tolerant = false;
        let unknown = [];
        if (!rows.length) {
            const fallback = tolerantRows(source, context);
            rows = fallback.rows;
            unknown = fallback.unknown;
            tolerant = rows.length > 0;
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

        // The fusion plant reveals the energy technology of the account.
        let energyTech = 0;
        for (const row of rows) {
            if (row.gid !== production.GID_B_FUSION) continue;
            const level = production.implyEnergyTech(row.production[GID_RC_ENERGY], row.level, {
                engineer: engineer,
                factor: 1,
            });
            if (level > energyTech) energyTech = level;
        }

        const planet = /планете\s+&quot;([^&]+)&quot;|planet\s+&quot;([^&]+)&quot;/i.exec(source);
        return {
            rows: rows,
            factors: factors,
            officers: { geologist: officer, engineer: engineer },
            energyTech: energyTech,
            planet: planet ? (planet[1] || planet[2]) : null,
            tolerant: tolerant,
            unknown: unknown,
        };
    }

    const api = {
        COLUMNS: COLUMNS,
        objectGid: objectGid,
        normalizeName: normalizeName,
        guessGid: guessGid,
        levelRegexp: levelRegexp,
        parseProductionPage: parseProductionPage,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
