/**
 * OGame Infinity 0.84 — empire (imperium) page parser.
 *
 * `page=imperium` is a matrix: every section carries an HTML comment landmark and
 * every section is a set of rows with one cell per planet plus a "Sum" column.
 *
 *     <!-- ## Coordinates --> <tr><th>Coordinates</th><th>[1:1:4]</th>…<th>&nbsp;</th></tr>
 *     <!-- ## Resources (without Energy) -->
 *       <tr><th>Metal</th><th><a …>25.047</a> / 286</th>…<th>38.072 / 147</th></tr>
 *     <!-- ## Buildings -->
 *       <tr><th><a href="…&gid=1&…">Metal Mine</a></th><th>…5…</th>…</tr>
 *
 * The planet ids come from the planet image links (`&cp=<planet_id>`), the object
 * ids of the building/research/ship/defence rows from their `gid=` links, so the
 * parser does not depend on translated names.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;

    // Resource ids of the engine.
    const GID_RC_METAL = 700;
    const GID_RC_CRYSTAL = 701;
    const GID_RC_DEUTERIUM = 702;
    const GID_RC_ENERGY = 703;

    const SECTIONS = [
        'Planetimages', 'Name', 'Coordinates', 'Fields',
        'Resources (without Energy)', 'Resources-Energy',
        'Buildings', 'Research', 'Ships', 'Defense',
    ];

    /**
     * Split the page into its `<!-- ## Title -->` sections.
     *
     * @param {string} source Page source.
     * @returns {Object<string, string>} Section name to HTML.
     */
    function splitSections(source) {
        const out = {};
        const parts = String(source).split(/<!--\s*##\s*([^>]*?)\s*-->/);
        for (let i = 1; i < parts.length; i += 2) {
            const title = parts[i].trim();
            if (!title) continue;
            out[title] = (out[title] || '') + parts[i + 1];
        }
        return out;
    }

    /**
     * Read the matrix rows of a section: a label cell, one cell per planet and the
     * sum cell.
     *
     * @param {string} sectionHtml Section HTML.
     * @returns {Array<{label: string, gid: number|null, values: string[], sum: string|null}>} Rows.
     */
    function matrixRows(sectionHtml) {
        const rows = [];
        for (const row of html.rowsEx(sectionHtml)) {
            const cells = html.cells(row.html);
            if (cells.length < 3) continue;
            const labelCell = cells[0];
            if (labelCell.tag === 'td' && /colspan/i.test(labelCell.attrs)) continue;   // section header
            const gidMatch = /[?&](?:amp;)?gid=(\d+)/i.exec(labelCell.html);
            const label = labelCell.text;
            if (!label && !gidMatch) continue;
            rows.push({
                label: label,
                gid: gidMatch ? parseInt(gidMatch[1], 10) : null,
                values: cells.slice(1, cells.length - 1).map(function (cell) { return cell.text; }),
                sum: cells.length > 2 ? cells[cells.length - 1].text : null,
            });
        }
        return rows;
    }

    /**
     * First number of a cell text.
     *
     * @param {string} text Cell text.
     * @returns {number|null} Number.
     */
    function firstNumber(text) {
        const match = /(\d[\d.,]*)/.exec(String(text || ''));
        if (!match) return null;
        const value = numbers.parseGameNumber(match[1]);
        return isNaN(value) ? null : value;
    }

    /**
     * Read the "amount / production" pair of a resource cell.
     *
     * @param {string} text Cell text.
     * @returns {{amount: number, production: number|null}} Values.
     */
    function amountAndProduction(text) {
        const parts = String(text || '').split('/');
        const amount = parts.length ? numbers.parseGameNumber(parts[0]) : NaN;
        const production = parts.length > 1 ? numbers.parseGameNumber(parts[1]) : NaN;
        return {
            amount: isNaN(amount) ? 0 : amount,
            production: isNaN(production) ? null : production,
        };
    }

    /**
     * Parse the empire page.
     *
     * @param {string} source Page source.
     * @param {object} [ctx] Context: loca, lang (used to map resource names).
     * @returns {{planets: Array, sums: object}} Empire snapshot.
     */
    function parseImperiumPage(source, ctx) {
        const sections = splitSections(source);
        const context = ctx || {};

        // Planet ids, in the order of the columns.
        const ids = [];
        const idRe = /[?&](?:amp;)?cp=(\d+)/gi;
        let match;
        while ((match = idRe.exec(sections.Planetimages || '')) !== null) ids.push(parseInt(match[1], 10));

        const planets = ids.map(function (id) {
            return {
                id: id,
                name: null,
                coords: null,
                fields: { used: null, max: null },
                resources: {},
                buildings: {},
                research: {},
                ships: {},
                defence: {},
            };
        });

        function applyRow(row, push) {
            for (let i = 0; i < planets.length && i < row.values.length; i++) {
                push(planets[i], row.values[i], row);
            }
        }

        // Names and coordinates.
        const nameRows = matrixRows(sections.Name || '');
        if (nameRows.length) applyRow(nameRows[0], function (planet, value) { planet.name = value; });
        const coordsRows = matrixRows(sections.Coordinates || '');
        if (coordsRows.length) {
            applyRow(coordsRows[0], function (planet, value) {
                const coords = /\[\s*(\d+)\s*:\s*(\d+)\s*:\s*(\d+)\s*\]/.exec(html.normalizeText(value).replace(/<[^>]*>/g, ''));
                if (coords) planet.coords = { g: parseInt(coords[1], 10), s: parseInt(coords[2], 10), p: parseInt(coords[3], 10) };
            });
        }

        // Fields: "14/14".
        const fieldRows = matrixRows(sections.Fields || '');
        if (fieldRows.length) {
            applyRow(fieldRows[0], function (planet, value) {
                const parts = String(value).split('/');
                planet.fields.used = firstNumber(parts[0]);
                planet.fields.max = parts.length > 1 ? firstNumber(parts[1]) : null;
            });
        }

        // Resources: one row per resource, "amount / production".
        const resourceRows = matrixRows(sections['Resources (without Energy)'] || '');
        for (const row of resourceRows) {
            const gid = resourceGid(row, context);
            if (gid === null) continue;
            applyRow(row, function (planet, value) {
                planet.resources[gid] = amountAndProduction(value);
            });
        }
        for (const row of matrixRows(sections['Resources-Energy'] || '')) {
            applyRow(row, function (planet, value) {
                const pair = amountAndProduction(value);
                planet.resources[GID_RC_ENERGY] = { amount: pair.amount, production: pair.production };
            });
        }

        // Object matrices: buildings, research, ships, defence.
        const matrices = [
            ['Buildings', 'buildings'],
            ['Research', 'research'],
            ['Ships', 'ships'],
            ['Defense', 'defence'],
        ];
        for (const matrix of matrices) {
            for (const row of matrixRows(sections[matrix[0]] || '')) {
                const gid = row.gid !== null ? row.gid : objectGid(row.label, context);
                if (gid === null) continue;
                applyRow(row, function (planet, value) {
                    const amount = firstNumber(value);
                    if (amount !== null && amount !== 0) planet[matrix[1]][gid] = amount;
                });
            }
        }

        return { planets: planets, sums: sums(sections) };
    }

    /**
     * Resource id of a resource row.
     *
     * @param {object} row Matrix row.
     * @param {object} ctx Context: loca, lang.
     * @returns {number|null} Resource id.
     */
    function resourceGid(row, ctx) {
        if (row.gid !== null) return row.gid;
        const known = [[GID_RC_METAL, 'metal'], [GID_RC_CRYSTAL, 'crystal'], [GID_RC_DEUTERIUM, 'deuterium'], [GID_RC_ENERGY, 'energy']];
        if (ctx.loca && ctx.lang) {
            for (const entry of known) {
                const name = ctx.loca.get(ctx.lang, 'NAME_' + entry[0]);
                if (name && html.normalizeText(name) === html.normalizeText(row.label)) return entry[0];
            }
        }
        for (const entry of known) {
            if (html.normalizeText(row.label).toLowerCase().indexOf(entry[1]) === 0) return entry[0];
        }
        return null;
    }

    /**
     * Object id of a matrix row, from its localized name.
     *
     * @param {string} label Row label.
     * @param {object} ctx Context: loca, lang.
     * @returns {number|null} Object id.
     */
    function objectGid(label, ctx) {
        if (!ctx.loca || !ctx.lang) return null;
        const map = ctx.loca.nameToId(ctx.lang);
        const text = html.normalizeText(label);
        return Object.prototype.hasOwnProperty.call(map, text) ? map[text] : null;
    }

    /**
     * Read the sum column of every section.
     *
     * @param {Object<string, string>} sections Sections.
     * @returns {object} Sums.
     */
    function sums(sections) {
        const out = { resources: {}, fields: null };
        for (const row of matrixRows(sections['Resources (without Energy)'] || '')) {
            if (row.sum === null) continue;
            const parts = String(row.sum).split('/');
            const amount = numbers.parseGameNumber(parts[0]);
            const production = parts.length > 1 ? numbers.parseGameNumber(parts[1]) : NaN;
            out.resources[row.label] = {
                amount: isNaN(amount) ? 0 : amount,
                production: isNaN(production) ? null : production,
            };
        }
        const fieldRows = matrixRows(sections.Fields || '');
        if (fieldRows.length) out.fields = html.normalizeText(fieldRows[0].sum || '');
        return out;
    }

    const api = {
        GID_RC_METAL: GID_RC_METAL,
        GID_RC_CRYSTAL: GID_RC_CRYSTAL,
        GID_RC_DEUTERIUM: GID_RC_DEUTERIUM,
        GID_RC_ENERGY: GID_RC_ENERGY,
        SECTIONS: SECTIONS,
        splitSections: splitSections,
        matrixRows: matrixRows,
        parseImperiumPage: parseImperiumPage,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
