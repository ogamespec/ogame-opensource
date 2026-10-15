/**
 * OGame Infinity 0.84 — battle report parser.
 *
 * The 0.84 battle report (game/core/battle_report.php, `BattleReport`) is one
 * huge single-line HTML blob without a single id or data attribute. The outcome,
 * the loot, the losses and the debris are plain localized sentences, and the
 * participants are "Attacker <name> (<a onclick=showGalaxy(1,1,4); >[1:1:4]</a>)"
 * followed by a unit table using the short names of battlereport.php
 * (SNAME_202 "S.Cargo" and so on).
 *
 * The message list already carries the result and both loss values in the
 * subject line (see parsers/messages.js); this parser adds the detail that only
 * the report itself has: loot, debris, moon chance and the fleet composition.
 */
(function (root) {
    'use strict';

    const html = typeof require === 'function' ? require('../lib/html.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;
    const templates = typeof require === 'function' ? require('../lib/templates.js') : root.OGI;

    const GID_RC_METAL = 700;
    const GID_RC_CRYSTAL = 701;
    const GID_RC_DEUTERIUM = 702;

    /**
     * Read a number out of a template match.
     *
     * @param {Object<number, string>|null} values Placeholder values.
     * @param {number} index Placeholder number.
     * @returns {number|null} Parsed number.
     */
    function value(values, index) {
        if (!values || values[index] === undefined) return null;
        const parsed = numbers.parseGameNumber(values[index]);
        return isNaN(parsed) ? null : parsed;
    }

    /**
     * Parse the unit table that follows a participant slot (the "before the
     * battle" block): "Type" row with the short unit names and "Total" row with
     * the amounts.
     *
     * @param {string} block Source of the participant slot.
     * @param {object} ctx Context: loca, lang.
     * @returns {Object<number|string, number>} Unit amounts by id.
     */
    function parseUnits(block, ctx) {
        const out = {};
        const names = ctx.loca.nameToId(ctx.lang);
        const typeLabel = html.normalizeText(ctx.loca.get(ctx.lang, 'BATTLE_TYPE') || 'Type');
        const amountLabel = html.normalizeText(ctx.loca.get(ctx.lang, 'BATTLE_AMOUNT') || 'Total');

        function rowValues(label) {
            const re = new RegExp('<th[^>]*>\\s*' + templates.escapeRegex(label) + '\\s*</th>([\\s\\S]*?)</tr>', 'i');
            const match = re.exec(block);
            if (!match) return [];
            const values = [];
            const cellRe = /<th[^>]*>([\s\S]*?)<\/th>/gi;
            let cell;
            while ((cell = cellRe.exec(match[1])) !== null) values.push(html.normalizeText(cell[1]));
            return values;
        }

        const types = rowValues(typeLabel);
        const amounts = rowValues(amountLabel);
        for (let i = 0; i < types.length && i < amounts.length; i++) {
            const amount = numbers.parseGameNumber(amounts[i]);
            if (isNaN(amount) || amount <= 0) continue;
            const name = types[i];
            const id = Object.prototype.hasOwnProperty.call(names, name) ? names[name] : null;
            out[id === null ? name : id] = amount;
        }
        return out;
    }

    /**
     * Parse the participants of a battle report.
     *
     * @param {string} source Report HTML.
     * @param {object} ctx Context: loca, lang.
     * @returns {{attackers: Array, defenders: Array}} Participants.
     */
    function parseParticipants(source, ctx) {
        const attackers = [];
        const defenders = [];
        const attackerLabel = ctx.loca.get(ctx.lang, 'BATTLE_ATTACKER') || 'Attacker';
        const defenderLabel = ctx.loca.get(ctx.lang, 'BATTLE_DEFENDER') || 'Defender';
        const re = new RegExp(
            '(' + templates.escapeRegex(html.normalizeText(attackerLabel)) + '|' +
            templates.escapeRegex(html.normalizeText(defenderLabel)) + ')\\s+([^<]*?)\\s*\\(\\s*<a[^>]*showGalaxy\\(\\s*(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)\\s*\\)',
            'gi'
        );
        let match;
        const matches = [];
        while ((match = re.exec(source)) !== null) {
            matches.push({
                side: match[1].toLowerCase() === html.normalizeText(attackerLabel).toLowerCase() ? 'attacker' : 'defender',
                name: html.decodeEntities(match[2]).trim(),
                coords: { g: parseInt(match[3], 10), s: parseInt(match[4], 10), p: parseInt(match[5], 10) },
                start: match.index,
                end: re.lastIndex,
            });
        }

        for (let i = 0; i < matches.length; i++) {
            const entry = matches[i];
            const block = source.slice(entry.end, i + 1 < matches.length ? matches[i + 1].start : source.length);
            const participant = {
                name: entry.name,
                coords: entry.coords,
                units: parseUnits(block, ctx),
            };
            if (entry.side === 'attacker') attackers.push(participant);
            else defenders.push(participant);
        }

        return { attackers: attackers, defenders: defenders };
    }

    /**
     * Parse a battle report.
     *
     * @param {string} source Report HTML.
     * @param {object} ctx Context: loca, lang.
     * @returns {object} Parsed report.
     */
    function parseBattleReport(source, ctx) {
        const loca = ctx && ctx.loca;
        const lang = (ctx && ctx.lang) || 'en';
        const text = html.normalizeText(source);

        const result = {
            dateText: null,
            result: null,
            loot: null,
            losses: null,
            debris: null,
            moonChance: null,
            moonCreated: false,
            repaired: false,
            lost: false,
            rounds: 0,
        };

        if (loca) {
            const dateValues = templates.matchTemplate(loca.get(lang, 'BATTLE_ADATE_INFO'), text);
            if (dateValues) result.dateText = dateValues[1] ? dateValues[1].trim() : null;

            if (templates.matchTemplate(loca.get(lang, 'BATTLE_AWON'), text)) result.result = 'awon';
            else if (templates.matchTemplate(loca.get(lang, 'BATTLE_DWON'), text)) result.result = 'dwon';
            else if (templates.matchTemplate(loca.get(lang, 'BATTLE_DRAW'), text)) result.result = 'draw';

            const plunder = templates.matchTemplate(loca.get(lang, 'BATTLE_PLUNDER'), text);
            if (plunder) {
                const metal = value(plunder, 1) || 0;
                const crystal = value(plunder, 2) || 0;
                const deuterium = value(plunder, 3) || 0;
                result.loot = { metal: metal, crystal: crystal, deuterium: deuterium, total: metal + crystal + deuterium };
            }

            const aLoss = value(templates.matchTemplate(loca.get(lang, 'BATTLE_ALOSS'), text), 1);
            const dLoss = value(templates.matchTemplate(loca.get(lang, 'BATTLE_DLOSS'), text), 1);
            if (aLoss !== null || dLoss !== null) {
                result.losses = { attacker: aLoss || 0, defender: dLoss || 0 };
            }

            const debris = templates.matchTemplate(loca.get(lang, 'BATTLE_DEBRIS'), text);
            if (debris) {
                result.debris = { metal: value(debris, 1) || 0, crystal: value(debris, 2) || 0 };
            }

            const moon = templates.matchTemplate(loca.get(lang, 'BATTLE_MOONCHANCE'), text);
            if (moon) result.moonChance = value(moon, 1);
            result.moonCreated = !!templates.matchTemplate(loca.get(lang, 'BATTLE_MOON'), text);
            result.repaired = !!(templates.matchTemplate(loca.get(lang, 'BATTLE_REPAIRED'), text)
                || templates.matchTemplate(loca.get(lang, 'BATTLE_REPAIRED1'), text));
            result.lost = !!templates.matchTemplate(loca.get(lang, 'BATTLE_LOST'), text);

            const shoot = html.normalizeText(loca.get(lang, 'BATTLE_ASHOT') || '').split('#')[0];
            if (shoot) {
                const re = new RegExp(templates.escapeRegex(shoot), 'g');
                const found = text.match(re);
                result.rounds = found ? found.length : 0;
            }
        }

        // A battle that ended within two rounds is reported with a shortened text
        // plus the loss values in an HTML comment: "<!--A:167658,W:167658-->"
        // (see game/core/battle.php).
        const comment = /<!--\s*A:\s*(\d+)\s*,\s*W:\s*(\d+)\s*-->/i.exec(source);
        if (comment) {
            result.comment = { attacker: parseInt(comment[1], 10), defender: parseInt(comment[2], 10) };
            if (!result.losses) {
                result.losses = { attacker: result.comment.attacker, defender: result.comment.defender };
            }
        }

        if (loca) {
            const participants = parseParticipants(source, { loca: loca, lang: lang });
            result.attackers = participants.attackers;
            result.defenders = participants.defenders;
        } else {
            result.attackers = [];
            result.defenders = [];
        }

        result.resources = { GID_RC_METAL: GID_RC_METAL, GID_RC_CRYSTAL: GID_RC_CRYSTAL, GID_RC_DEUTERIUM: GID_RC_DEUTERIUM };
        return result;
    }

    const api = {
        parseBattleReport: parseBattleReport,
        parseParticipants: parseParticipants,
        parseUnits: parseUnits,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
