/**
 * OGame Infinity 0.84 — build pages (`page=buildings`).
 *
 * One page serves all four lists — mines and facilities, research, the shipyard and
 * the defence — and the mode is carried in the POST form (`&mode=Flotte`). Every
 * object is one `<tr>` with a `page=infos&gid=N` link, the localized current level
 * (`BUILD_LEVEL`, „уровень #1"), the localized price line (`BUILD_PRICE`) and the
 * build duration (`BUILD_DURATION`); the shipyard and defence rows carry the stock
 * (`BUILD_SHIPYARD_UNITS`) instead of a level.
 *
 * Knowing the current level is what makes the level stepper and the resource plan of
 * the extension possible: the costs of every further level are computed from the
 * object tables of the server (`analyze/needs.js`), not guessed.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;

    const GID_RC_METAL = 700;
    const GID_RC_CRYSTAL = 701;
    const GID_RC_DEUTERIUM = 702;
    const GID_RC_ENERGY = 703;
    const RESOURCES = [GID_RC_METAL, GID_RC_CRYSTAL, GID_RC_DEUTERIUM, GID_RC_ENERGY];

    /**
     * Turn a localized template into a regular expression.
     *
     * `"уровень #1"` becomes `/(?:уровень)\s*([\d.,]+)/`.
     *
     * @param {string} template Template with a `#1` placeholder.
     * @returns {RegExp|null} Expression.
     */
    function templatePattern(template) {
        if (!template) return null;
        const parts = String(template).split('#1');
        if (parts.length !== 2) return null;
        const escape = function (text) {
            return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/\s+/g, '\\s*');
        };
        return new RegExp(escape(html.normalizeText(parts[0])) + '\\s*([\\d.,]+)\\s*' + escape(html.normalizeText(parts[1])));
    }

    /**
     * Read the amount that follows a localized label inside a row text.
     *
     * @param {string} text Row text.
     * @param {string} label Localized label ("Стоимость", "Длительность").
     * @returns {string|null} Text after the label (up to the next label-like colon).
     */
    function afterLabel(text, label) {
        if (!label) return null;
        const index = text.indexOf(label);
        if (index === -1) return null;
        return text.slice(index + label.length);
    }

    /**
     * Read the cost line of a row.
     *
     * @param {string} text Row text.
     * @param {object} loca Localization.
     * @param {string} lang Language.
     * @returns {object|null} Costs by resource id, or null when the row has none.
     */
    function parseCost(text, loca, lang) {
        if (!loca || !lang) return null;
        const price = loca.get(lang, 'BUILD_PRICE');
        const rest = afterLabel(text, price);
        if (!rest) return null;

        const cost = {};
        let found = false;
        for (const gid of RESOURCES) {
            const name = loca.get(lang, 'NAME_' + gid);
            if (!name) continue;
            const escaped = html.normalizeText(name).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const match = new RegExp(escaped + '\\s*:?\\s*([\\d.,]+)').exec(rest);
            if (!match) continue;
            const value = numbers.parseGameNumber(match[1]);
            if (isNaN(value)) continue;
            cost[gid] = value;
            found = true;
        }
        return found ? cost : null;
    }

    /**
     * Clean the duration text of a row: the localized label ends with a colon and the
     * next cell follows after a dash, neither of which belongs to the value.
     *
     * @param {string|null} rest Text after the duration label.
     * @returns {string|null} Duration.
     */
    function cleanDuration(rest) {
        if (!rest) return null;
        let value = rest.split(/\s{2,}/)[0];
        value = value.replace(/^[\s:：–—-]+/, '').replace(/[\s–—-]+$/, '').trim();
        return value || null;
    }

    /**
     * Classify an object by its id.
     *
     * @param {number} gid Object id.
     * @param {object} unitsData Object tables.
     * @returns {string} "ship", "defence", "research" or "building".
     */
    function kindOf(gid, unitsData) {
        const units = unitsData || {};
        if (units.ships && units.ships[gid]) return 'ship';
        if (units.defence && units.defence[gid]) return 'defence';
        if (gid >= 100 && gid < 200) return 'research';
        if (gid >= 200 && gid < 400) return 'ship';
        if (gid >= 400 && gid < 600) return 'defence';
        return 'building';
    }

    /**
     * Parse a build page.
     *
     * @param {string} source Page source.
     * @param {object} [ctx] Context: loca, lang, unitsData.
     * @returns {{mode: string, rows: Array}} Rows in page order.
     */
    function parseBuildPage(source, ctx) {
        const context = ctx || {};
        const levelPattern = templatePattern(context.loca && context.lang
            ? context.loca.get(context.lang, 'BUILD_LEVEL') : null) || /(?:level|уровень)\s*:?\s*([\d.,]+)/i;
        const stockPattern = templatePattern(context.loca && context.lang
            ? context.loca.get(context.lang, 'BUILD_SHIPYARD_UNITS') : null);
        const unitPattern = /\[\s*(\d+)\s*\]/;
        const rows = [];
        const seen = {};

        // One object is one <tr>; the page mixes several nesting levels of tables, so
        // the rows are taken by splitting on the row tag itself.
        const chunks = String(source).split(/<tr[ >]/i);
        for (const chunk of chunks) {
            const gidMatch = /[?&](?:amp;)?gid=(\d+)/i.exec(chunk);
            if (!gidMatch) continue;
            const gid = parseInt(gidMatch[1], 10);
            if (seen[gid]) continue;

            const nameMatch = new RegExp('<a[^>]*gid=' + gid + '[^>]*>([^<]+)</a>', 'i').exec(chunk);
            const text = html.normalizeText(chunk);
            const levelMatch = levelPattern.exec(text);
            const stockMatch = stockPattern ? stockPattern.exec(text) : null;
            const durationRest = afterLabel(text, context.loca && context.lang
                ? context.loca.get(context.lang, 'BUILD_DURATION') : null);

            rows.push({
                gid: gid,
                name: nameMatch ? html.decodeEntities(nameMatch[1]).trim() : null,
                kind: kindOf(gid, context.unitsData),
                level: levelMatch ? numbers.parseGameNumber(levelMatch[1]) : null,
                stock: stockMatch ? numbers.parseGameNumber(stockMatch[1]) : null,
                cost: parseCost(text, context.loca, context.lang),
                duration: cleanDuration(durationRest),
                cell: unitPattern.exec(chunk) ? true : false,
            });
            seen[gid] = true;
        }

        return { mode: modeOf(source), rows: rows };
    }

    /**
     * Mode of the page (which of the four lists is open).
     *
     * @param {string} source Page source.
     * @returns {string} Mode.
     */
    function modeOf(source) {
        const form = /action=["']?index\.php\?page=buildings([^"'>]*)/i.exec(String(source));
        const query = form ? form[1] : '';
        if (/mode=Flotte/i.test(query)) return 'ship';
        if (/mode=Verteidigung/i.test(query)) return 'defence';
        if (/mode=Forschung/i.test(query)) return 'research';
        if (/mode=Gebaeude/i.test(query)) return 'building';
        return 'building';
    }

    const api = {
        GID_RC_METAL: GID_RC_METAL,
        GID_RC_CRYSTAL: GID_RC_CRYSTAL,
        GID_RC_DEUTERIUM: GID_RC_DEUTERIUM,
        GID_RC_ENERGY: GID_RC_ENERGY,
        RESOURCES: RESOURCES,
        templatePattern: templatePattern,
        kindOf: kindOf,
        modeOf: modeOf,
        parseCost: parseCost,
        parseBuildPage: parseBuildPage,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
