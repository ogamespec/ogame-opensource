/**
 * OGame Infinity 0.84 — text message parsers.
 *
 * Expedition results, recycling reports and transport notifications of the 0.84
 * server are localized prose, not structured data:
 *
 *     game/loca/en_en/expedition.php   EXP_FOUND   = "<br>You got #1 units of #2."
 *     game/loca/en_en/fleetmsg.php     FLEET_RECYCLE = "The #1 recyclers have a total
 *                                        capacity of #2. The debris field contains
 *                                        #3 metal and #4 crystal. Recycled #5 metal
 *                                        and #6 crystal."
 *
 * The original OGame Infinity extension classified exactly these texts for the
 * pre-API OGame versions (it even ships an `expeditions.tsv` with the localized
 * outcome texts). This module does the same, using the game's own strings from
 * src/data/loca.js, so no text is hard-coded here.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;
    const templates = typeof require === 'function' ? require('../lib/templates.js') : root.OGI;

    // Resource ids (game/core/techs.php).
    const GID_RC_METAL = 700;
    const GID_RC_CRYSTAL = 701;
    const GID_RC_DEUTERIUM = 702;
    const GID_RC_DM = 704;

    /**
     * Expedition outcome families. The key prefixes come straight from
     * game/loca/<lang>_<lang>/expedition.php and game/core/expedition.php.
     */
    const EXPEDITION_FAMILIES = [
        { type: 'ships', size: 'large', prefix: 'EXP_FLEET_LARGE_' },
        { type: 'ships', size: 'medium', prefix: 'EXP_FLEET_MED_' },
        { type: 'ships', size: 'small', prefix: 'EXP_FLEET_SMALL_' },
        { type: 'resources', size: 'large', prefix: 'EXP_RESFOUND_LARGE_' },
        { type: 'resources', size: 'medium', prefix: 'EXP_RESFOUND_MED_' },
        { type: 'resources', size: 'small', prefix: 'EXP_RESFOUND_SMALL_' },
        { type: 'darkmatter', size: 'large', prefix: 'EXP_DMFOUND_LARGE_' },
        { type: 'darkmatter', size: 'medium', prefix: 'EXP_DMFOUND_MED_' },
        { type: 'darkmatter', size: 'small', prefix: 'EXP_DMFOUND_SMALL_' },
        { type: 'aliens', size: 'strong', prefix: 'EXP_ALIENS_STRONG_' },
        { type: 'aliens', size: 'medium', prefix: 'EXP_ALIENS_MED_' },
        { type: 'aliens', size: 'weak', prefix: 'EXP_ALIENS_WEAK_' },
        { type: 'pirates', size: 'strong', prefix: 'EXP_PIRATES_STRONG_' },
        { type: 'pirates', size: 'medium', prefix: 'EXP_PIRATES_MED_' },
        { type: 'pirates', size: 'weak', prefix: 'EXP_PIRATES_WEAK_' },
        { type: 'lost', size: null, prefix: 'EXP_LOST_' },
        { type: 'delay', size: null, prefix: 'EXP_DELAY_' },
        { type: 'accel', size: null, prefix: 'EXP_ACCEL_' },
        { type: 'trader', size: null, prefix: 'EXP_TRADER_' },
        { type: 'nothing', size: null, prefix: 'EXP_NOTHING_' },
    ];

    /**
     * Find the localization key whose text occurs in the message.
     *
     * @param {object} ctx Context: loca, lang.
     * @param {string} text Normalized message text.
     * @param {string} prefix Key prefix.
     * @returns {string|null} Matching key.
     */
    function findFamilyKey(ctx, text, prefix) {
        for (let i = 1; i <= 20; i++) {
            const key = prefix + i;
            const value = ctx.loca.get(ctx.lang, key);
            if (value === null || value === '') continue;
            const needle = html.normalizeText(value);
            if (needle.length >= 4 && text.indexOf(needle) !== -1) return key;
        }
        return null;
    }

    /**
     * Find the first localization key of a list that occurs in a text.
     *
     * @param {object} ctx Context: loca, lang.
     * @param {string} text Normalized message text.
     * @param {string[]} keys Keys.
     * @returns {string|null} Matching key.
     */
    function findAnyKey(ctx, text, keys) {
        for (const key of keys) {
            const value = ctx.loca.get(ctx.lang, key);
            if (value === null || value === '') continue;
            const needle = html.normalizeText(value);
            if (needle.length >= 4 && text.indexOf(needle) !== -1) return key;
        }
        return null;
    }

    /**
     * Coordinates of a message: the server renders them as a showGalaxy() link
     * (game/core/page.php, `ShowGalaxy`), older fragments may carry plain
     * "[g:s:p]" text.
     *
     * @param {string} source Message source.
     * @returns {{g: number, s: number, p: number}|null} Coordinates.
     */
    function coordsOf(source) {
        const list = html.coordinates(source);
        if (list.length) return list[0];
        const match = /\[\s*(\d+)\s*:\s*(\d+)\s*:\s*(\d+)\s*\]/.exec(html.normalizeText(source));
        if (!match) return null;
        return { g: parseInt(match[1], 10), s: parseInt(match[2], 10), p: parseInt(match[3], 10) };
    }

    /**
     * Extract "object name + amount" pairs (used for ships found by expeditions)
     * from a text fragment.
     *
     * @param {string} fragment Text fragment.
     * @param {object} ctx Context: loca, lang.
     * @returns {Object<number, number>} Amounts by object id.
     */
    function findNamedAmounts(fragment, ctx) {
        const out = {};
        const map = ctx.loca.nameToId(ctx.lang);
        const names = Object.keys(map).sort(function (a, b) { return b.length - a.length; });
        if (!names.length) return out;
        const pattern = names.map(function (name) { return templates.escapeRegex(name); }).join('|');
        const re = new RegExp('(' + pattern + ')\\s+([\\d][\\d.,]*)', 'g');
        let match;
        while ((match = re.exec(fragment)) !== null) {
            const amount = numbers.parseGameNumber(match[2]);
            if (isNaN(amount) || amount <= 0) continue;
            out[map[match[1]]] = amount;
        }
        return out;
    }

    /**
     * Parse an expedition result message.
     *
     * @param {string} source Message body (HTML).
     * @param {object} ctx Context: loca, lang.
     * @returns {object} Parsed expedition result.
     */
    function parseExpedition(source, ctx) {
        const text = html.normalizeText(source);
        const out = {
            type: 'unknown',
            size: null,
            key: null,
            resource: null,
            ships: {},
            depleted: null,
            logbook: false,
            coords: null,
        };
        const coords = html.coordinates(source);
        if (coords.length) out.coords = coords[0];

        for (const family of EXPEDITION_FAMILIES) {
            const key = findFamilyKey(ctx, text, family.prefix);
            if (!key) continue;
            out.type = family.type;
            out.size = family.size;
            out.key = key;
            break;
        }

        // "You got #1 units of #2." — the found resources (or dark matter).
        const found = templates.matchTemplate(ctx.loca.get(ctx.lang, 'EXP_FOUND'), text);
        if (found && found[1] !== undefined && found[2] !== undefined) {
            const amount = numbers.parseGameNumber(found[1]);
            const name = html.normalizeText(found[2]);
            const map = ctx.loca.nameToId(ctx.lang);
            const id = Object.prototype.hasOwnProperty.call(map, name) ? map[name] : null;
            if (!isNaN(amount)) out.resource = { gid: id, name: name, amount: amount };
        }

        // The ships found by the expedition are listed after EXP_FLEET_FOUND.
        const shipHeader = ctx.loca.get(ctx.lang, 'EXP_FLEET_FOUND');
        if (shipHeader) {
            const needle = html.normalizeText(shipHeader);
            const at = text.indexOf(needle);
            if (at !== -1) {
                const logbook = findAnyKey(ctx, text, ['EXP_FLEET_LOGBOOK_1', 'EXP_FLEET_LOGBOOK_2', 'EXP_FLEET_LOGBOOK_3']);
                let tail = text.slice(at + needle.length);
                if (logbook) {
                    const logbookText = html.normalizeText(ctx.loca.get(ctx.lang, logbook));
                    const cut = tail.indexOf(logbookText);
                    if (cut !== -1) tail = tail.slice(0, cut);
                }
                out.ships = findNamedAmounts(tail, ctx);
            }
        }

        // The captain's logbook tells how depleted the sector is.
        const depleted = findAnyKey(ctx, text, ['EXP_NOT_DEPLETED_1', 'EXP_NOT_DEPLETED_2']);
        if (depleted) out.depleted = 'none';
        else {
            const min = findAnyKey(ctx, text, ['EXP_DEPLETED_MIN_1', 'EXP_DEPLETED_MIN_2', 'EXP_DEPLETED_MIN_3']);
            const med = findAnyKey(ctx, text, ['EXP_DEPLETED_MED_1', 'EXP_DEPLETED_MED_2', 'EXP_DEPLETED_MED_3']);
            const max = findAnyKey(ctx, text, ['EXP_DEPLETED_MAX_1', 'EXP_DEPLETED_MAX_2', 'EXP_DEPLETED_MAX_3']);
            if (max) out.depleted = 'max';
            else if (med) out.depleted = 'medium';
            else if (min) out.depleted = 'min';
        }
        out.logbook = out.depleted !== null;

        return out;
    }

    /**
     * Parse the "Intelligence" recycling report.
     *
     * @param {string} source Message body (HTML).
     * @param {object} ctx Context: loca, lang.
     * @returns {object} Parsed recycling report.
     */
    function parseRecycle(source, ctx) {
        const values = templates.matchTemplate(ctx.loca.get(ctx.lang, 'FLEET_RECYCLE'), source);
        if (!values) return null;
        return {
            recyclers: numbers.parseGameNumber(values[1]),
            capacity: numbers.parseGameNumber(values[2]),
            debris: {
                metal: numbers.parseGameNumber(values[3]),
                crystal: numbers.parseGameNumber(values[4]),
            },
            harvested: {
                metal: numbers.parseGameNumber(values[5]),
                crystal: numbers.parseGameNumber(values[6]),
                deuterium: 0,
            },
        };
    }

    /**
     * Parse an own transport arrival ("Your fleet reaches the planet ... and
     * delivers its cargo").
     *
     * @param {string} source Message body (HTML).
     * @param {object} ctx Context: loca, lang.
     * @returns {object|null} Parsed transport.
     */
    function parseTransportOwn(source, ctx) {
        const values = templates.matchTemplate(ctx.loca.get(ctx.lang, 'FLEET_TRANSPORT_OWN'), source);
        if (!values) return null;
        return {
            kind: 'transport-out',
            coords: coordsOf(source),
            delivered: {
                metal: numbers.parseGameNumber(values[2]),
                crystal: numbers.parseGameNumber(values[3]),
                deuterium: numbers.parseGameNumber(values[4]),
            },
        };
    }

    /**
     * Parse a foreign transport delivery ("Player X's fleet is delivering to your
     * planet").
     *
     * @param {string} source Message body (HTML).
     * @param {object} ctx Context: loca, lang.
     * @returns {object|null} Parsed delivery.
     */
    function parseTransportOther(source, ctx) {
        const values = templates.matchTemplate(ctx.loca.get(ctx.lang, 'FLEET_TRANSPORT_OTHER'), source);
        if (!values) return null;
        return {
            kind: 'transport-in',
            player: values[1] ? html.normalizeText(values[1]) : null,
            coords: coordsOf(source),
            delivered: {
                metal: numbers.parseGameNumber(values[4]),
                crystal: numbers.parseGameNumber(values[5]),
                deuterium: numbers.parseGameNumber(values[6]),
            },
        };
    }

    /**
     * Parse a deploy ("Fleet retention") confirmation.
     *
     * @param {string} source Message body (HTML).
     * @param {object} ctx Context: loca, lang.
     * @returns {object|null} Parsed deploy.
     */
    function parseDeploy(source, ctx) {
        const header = ctx.loca.get(ctx.lang, 'FLEET_DEPLOY');
        const values = templates.matchTemplate(header, source);
        if (!values) return null;
        const out = {
            kind: 'deploy',
            coords: coordsOf(source),
            ships: {},
            delivered: null,
        };
        const normalizedHeader = html.normalizeText(header).split('#')[0];
        const normalizedSource = html.normalizeText(source);
        const shipsAt = normalizedHeader ? normalizedSource.indexOf(normalizedHeader) : -1;
        if (shipsAt !== -1) out.ships = findNamedAmounts(normalizedSource.slice(shipsAt), ctx);
        const res = templates.matchTemplate(ctx.loca.get(ctx.lang, 'FLEET_DEPLOY_RES'), source);
        if (res) {
            out.delivered = {
                metal: numbers.parseGameNumber(res[1]),
                crystal: numbers.parseGameNumber(res[2]),
                deuterium: numbers.parseGameNumber(res[3]),
            };
        }
        return out;
    }

    /**
     * Parse a fleet return confirmation.
     *
     * @param {string} source Message body (HTML).
     * @param {object} ctx Context: loca, lang.
     * @returns {object|null} Parsed return.
     */
    function parseReturn(source, ctx) {
        const values = templates.matchTemplate(ctx.loca.get(ctx.lang, 'FLEET_RETURN'), source);
        if (!values) return null;
        const out = {
            kind: 'return',
            ships: findNamedAmounts(html.normalizeText(source), ctx),
            delivered: null,
        };
        const res = templates.matchTemplate(ctx.loca.get(ctx.lang, 'FLEET_RETURN_RES'), source);
        if (res) {
            out.delivered = {
                metal: numbers.parseGameNumber(res[1]),
                crystal: numbers.parseGameNumber(res[2]),
                deuterium: numbers.parseGameNumber(res[3]),
            };
        }
        return out;
    }

    /**
     * Parse a message body according to its kind (as classified by the message
     * list parser).
     *
     * @param {string} source Message body (HTML).
     * @param {string} kind Message kind.
     * @param {object} ctx Context: loca, lang.
     * @returns {object|null} Parsed message.
     */
    function parseMessageBody(source, kind, ctx) {
        if (!source || !ctx || !ctx.loca) return null;
        switch (kind) {
            case 'expedition': return parseExpedition(source, ctx);
            case 'recycle': return parseRecycle(source, ctx);
            case 'transport-out': return parseTransportOwn(source, ctx);
            case 'transport-in': return parseTransportOther(source, ctx);
            case 'deploy': return parseDeploy(source, ctx);
            case 'return': return parseReturn(source, ctx);
            default: return null;
        }
    }

    const api = {
        GID_RC_METAL: GID_RC_METAL,
        GID_RC_CRYSTAL: GID_RC_CRYSTAL,
        GID_RC_DEUTERIUM: GID_RC_DEUTERIUM,
        GID_RC_DM: GID_RC_DM,
        EXPEDITION_FAMILIES: EXPEDITION_FAMILIES,
        findFamilyKey: findFamilyKey,
        findAnyKey: findAnyKey,
        findNamedAmounts: findNamedAmounts,
        parseExpedition: parseExpedition,
        parseRecycle: parseRecycle,
        parseTransportOwn: parseTransportOwn,
        parseTransportOther: parseTransportOther,
        parseDeploy: parseDeploy,
        parseReturn: parseReturn,
        parseMessageBody: parseMessageBody,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
