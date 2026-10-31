/**
 * OGame Infinity 0.84 — profitability ("Рентабельность").
 *
 * The tab of the original extension that answers "what should I build next": for every
 * mine and power plant it computes the value of the next level (through the trade rate
 * of the settings) against the production that level adds, and sorts by payback time.
 *
 * The production is calibrated against the numbers the server itself shows on the
 * „Сырьё" page, so the planet factor, the temperature, the officer bonuses and the
 * economy speed of the universe are all included exactly.
 *
 * Known simplifications (the same ones the original documents):
 *   - the energy supply of a mine is not added to its cost (only reported);
 *   - buildings that do not produce (storage, shipyard, …) are not part of the ranking;
 *   - the 0.84 engine has no crawlers and no life forms, so there is no crawler bonus.
 */
(function (root) {
    'use strict';

    const needs = typeof require === 'function' ? require('./needs.js') : root.OGI;
    const production = typeof require === 'function' ? require('./production.js') : root.OGI;

    // Default trade rate of the original extension: metal : crystal : deuterium.
    const DEFAULT_RATE = [3, 2, 1];
    const DEFAULT_ENERGY_VALUE = 0;

    /**
     * Value of a resource basket in "deuterium units" through the trade rate.
     *
     * @param {object} amounts Amounts: `{700: n, 701: n, 702: n, 703: n}` or `{metal, crystal, deuterium}`.
     * @param {Array<number>} [rate] Trade rate.
     * @returns {number} Value.
     */
    function value(amounts, rate) {
        const r = rate && rate.length === 3 ? rate : DEFAULT_RATE;
        const source = amounts || {};
        const metal = source[700] !== undefined ? source[700] : (source.metal || 0);
        const crystal = source[701] !== undefined ? source[701] : (source.crystal || 0);
        const deuterium = source[702] !== undefined ? source[702] : (source.deuterium || 0);
        return metal * r[0] + crystal * r[1] + deuterium * r[2];
    }

    /**
     * Production of one building at a level, calibrated against the server page.
     *
     * @param {number} gid Building id.
     * @param {number} level Level.
     * @param {object} [context] Context: factors, energyTech, engineer, temp.
     * @returns {object} Production (see analyze/production.js).
     */
    function at(gid, level, context) {
        const ctx = context || {};
        if (gid === production.GID_B_FUSION) {
            return production.production(gid, level, {
                energyTech: ctx.energyTech || 0,
                engineer: ctx.engineer !== false,
                factor: 1,
            });
        }
        return production.production(gid, level, { factor: (ctx.factors || {})[gid] || 1 });
    }

    /**
     * One recommendation: what the next level of a building costs, what it produces and
     * how long it takes to pay for itself.
     *
     * @param {object} entry Entry: gid, name, level.
     * @param {object} [options] Options: rate, factors, energyTech, engineer, unitsData.
     * @returns {object} Recommendation.
     */
    function recommend(entry, options) {
        const opt = options || {};
        const rate = opt.rate || DEFAULT_RATE;
        const level = entry.level || 0;
        const before = at(entry.gid, level, opt);
        const after = at(entry.gid, level + 1, opt);
        const cost = needs.levelCost(entry.gid, level + 1, opt.unitsData);

        const gain = {
            resource: after.resource,
            amount: after.amount - before.amount,
            energy: after.energy - before.energy,
            deuterium: after.deuterium - before.deuterium,
        };
        const gainBasket = {};
        if (gain.resource === 700) gainBasket[700] = gain.amount;
        else if (gain.resource === 701) gainBasket[701] = gain.amount;
        else if (gain.resource === 702) gainBasket[702] = gain.amount;
        const gainValue = value(gainBasket, rate);
        const costValue = value(cost, rate);

        return {
            gid: entry.gid,
            name: entry.name || null,
            level: level,
            nextLevel: level + 1,
            cost: cost,
            costValue: costValue,
            gain: gain,
            gainValue: gainValue,
            energyBalance: (after.energy || 0) - (before.energy || 0),
            // Hours until the extra production has paid for the level.
            payback: gainValue > 0 ? costValue / gainValue : null,
            paybackDays: gainValue > 0 ? costValue / gainValue / 24 : null,
        };
    }

    /**
     * Rank a set of buildings by payback time.
     *
     * @param {object} objects Levels by building id (`{1: 5, 2: 3, …}`).
     * @param {object} [options] Options: rate, factors, energyTech, engineer, unitsData,
     *        names (gid -> name), filter (payback limit in days), resource (700/701/702/703),
     *        limit.
     * @returns {Array} Recommendations, best first.
     */
    function recommendations(objects, options) {
        const opt = options || {};
        const levels = objects || {};
        const gids = production.MINES.concat(production.POWER);
        const rows = [];
        for (const gid of gids) {
            const level = levels[gid];
            if (!(level > 0)) continue;
            const entry = recommend({
                gid: gid,
                name: (opt.names || {})[gid] || null,
                level: level,
            }, opt);
            if (opt.resource && entry.gain.resource !== opt.resource) continue;
            if (opt.filter && entry.paybackDays !== null && entry.paybackDays > opt.filter) continue;
            if (entry.payback === null) continue;
            rows.push(entry);
        }
        rows.sort(function (a, b) {
            if (a.payback === b.payback) return a.gid - b.gid;
            return a.payback - b.payback;
        });
        return opt.limit ? rows.slice(0, opt.limit) : rows;
    }

    const api = {
        DEFAULT_RATE: DEFAULT_RATE,
        DEFAULT_ENERGY_VALUE: DEFAULT_ENERGY_VALUE,
        value: value,
        at: at,
        recommend: recommend,
        recommendations: recommendations,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
