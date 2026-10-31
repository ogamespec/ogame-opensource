/**
 * OGame Infinity 0.84 — needs (resource lock) calculator.
 *
 * Port of the OGame Infinity "needs" feature to 0.84. In the extension the player
 * marks the resources a planet still has to keep free for a planned construction;
 * here the calculator answers the same question from the other side: given the
 * stock of a planet, what is still missing for a building, a research or a whole
 * queue of upgrades, and how long the production needs to fill the gap.
 *
 * The costs come from the generated object tables (src/data/units.js), which
 * ogi/tools/build-data.js extracts from game/core/techs.php, so the 0.84 cost
 * formula is applied to the real level 1 costs and rebuild factors:
 *
 *     cost(level) = initialCost * factor ^ (level - 1)      (floored, as the game does)
 *
 * Example (metal mine, gid 1, 60 metal / 15 crystal, factor 1.5):
 * level 1 -> 60 / 15, level 2 -> 90 / 22, level 3 -> 135 / 33.
 *
 * Nothing here reads the game pages: the module is pure, which is what lets the
 * test suite check the formula against the real object tables.
 */
(function (root) {
    'use strict';

    const units = typeof require === 'function' ? require('../data/units.js') : root.OGI.UNITS;

    // Highest level the calculator evaluates. The 0.84 engine allows absurd levels
    // in theory, but factor ^ 1000 is already ~1e300 and larger exponents overflow a
    // double; the guard below keeps the result finite.
    const MAX_LEVEL = 1000;

    /**
     * Multiply a base cost and floor it, without ever returning Infinity or NaN.
     *
     * @param {number} value Base amount.
     * @param {number} multiplier Growth multiplier.
     * @returns {number} Floored amount.
     */
    function scale(value, multiplier) {
        const scaled = (value || 0) * multiplier;
        if (!isFinite(scaled)) return Number.MAX_SAFE_INTEGER;
        return Math.floor(scaled);
    }

    /**
     * Normalize a level into the range the calculator supports.
     *
     * @param {*} level Level.
     * @returns {number} Integer level between 0 and MAX_LEVEL.
     */
    function safeLevel(level) {
        const number = Number(level);
        if (!isFinite(number) || number <= 0) return 0;
        return Math.min(Math.floor(number), MAX_LEVEL);
    }

    /**
     * Rebuild factor of an object (0 for ships and defence, which have flat costs).
     *
     * @param {number} gid Object id.
     * @param {object} [unitsData] Object tables.
     * @returns {number} Factor, 0 when the cost does not grow.
     */
    function factorOf(gid, unitsData) {
        const data = unitsData || units || {};
        const factors = data.factor || {};
        const factor = Number(factors[gid]);
        return isFinite(factor) && factor > 0 ? factor : 0;
    }

    /**
     * Cost of one level of a building or research, with the 0.84 growth formula
     * `initial * factor ^ (level - 1)`, floored. Objects without a rebuild factor
     * (ships, defence, missiles) keep their flat cost.
     *
     * Unknown object ids and levels below 1 produce zeros instead of NaN.
     *
     * @param {number} gid Object id (game/core/techs.php).
     * @param {number} level Level to price (1 is the first level).
     * @param {object} [unitsData] Object tables to use instead of the bundled ones.
     * @returns {{metal: number, crystal: number, deuterium: number, energy: number, total: number}}
     *          Cost of that level; `total` is metal + crystal + deuterium (energy is
     *          a requirement, not a resource value, exactly as in analyze/index.js).
     */
    function levelCost(gid, level, unitsData) {
        const data = unitsData || units || {};
        const base = (data.costs || {})[gid];
        const wanted = safeLevel(level);
        const empty = { metal: 0, crystal: 0, deuterium: 0, energy: 0, total: 0 };
        if (!base || wanted === 0) return empty;

        const factor = factorOf(gid, data);
        const multiplier = factor > 0 ? Math.pow(factor, wanted - 1) : 1;
        const cost = {
            metal: scale(base.metal, multiplier),
            crystal: scale(base.crystal, multiplier),
            deuterium: scale(base.deuterium, multiplier),
            energy: scale(base.energy, multiplier),
        };
        cost.total = cost.metal + cost.crystal + cost.deuterium;
        return cost;
    }

    /**
     * Cost of a range of levels: everything from `fromLevel + 1` up to `toLevel`
     * (a queue of upgrades is valued with this).
     *
     * @param {number} gid Object id.
     * @param {number} fromLevel Level the object has now (0 for a new object).
     * @param {number} toLevel Level to reach.
     * @param {object} [unitsData] Object tables.
     * @returns {{metal: number, crystal: number, deuterium: number, energy: number, total: number}}
     *          Summed cost; zeros when `toLevel` is not above `fromLevel`.
     */
    function totalCost(gid, fromLevel, toLevel, unitsData) {
        const from = safeLevel(fromLevel);
        const to = safeLevel(toLevel);
        const sum = { metal: 0, crystal: 0, deuterium: 0, energy: 0, total: 0 };
        for (let level = from + 1; level <= to; level++) {
            const cost = levelCost(gid, level, unitsData);
            sum.metal += cost.metal;
            sum.crystal += cost.crystal;
            sum.deuterium += cost.deuterium;
            sum.energy += cost.energy;
        }
        sum.total = sum.metal + sum.crystal + sum.deuterium;
        return sum;
    }

    /**
     * What is still missing for a cost, given the stock of the planet.
     *
     * `reserve` (default 0) is the part of the stock the player wants to keep (the
     * original OGame Infinity resource lock): it is subtracted from the usable
     * stock. `production` (per hour, per resource) together with `seconds` projects
     * the stock forward before the shortage is measured; the residual shortage is
     * then converted into the additional waiting time:
     *
     *     waitSeconds(resource) = ceil(missing / production per second)
     *     waitSeconds           = max over the resources, 0 when everything is affordable
     *
     * @param {{metal?: number, crystal?: number, deuterium?: number, energy?: number}} stock Stock of the planet.
     * @param {{metal?: number, crystal?: number, deuterium?: number, energy?: number}} cost Cost to cover.
     * @param {object} [options] Options: `reserve` (kept back, default 0),
     *        `production` (per hour), `seconds` (time already credited by the
     *        production, default 0).
     * @returns {{metal: number, crystal: number, deuterium: number, energy: number,
     *            total: number, missingTotal: number, affordable: boolean,
     *            stock: object, waitSeconds: number|null, waitKnown: boolean}}
     *          Missing amounts (`total` and `missingTotal` are the same value; the
     *          second name is kept for callers that spell it out), the usable stock,
     *          and the waiting time (`null` when no production rate was given and
     *          there is something missing).
     */
    function needs(stock, cost, options) {
        const opt = options || {};
        const have = stock || {};
        const want = cost || {};
        const reserve = Number(opt.reserve) > 0 ? Number(opt.reserve) : 0;
        const production = opt.production || null;
        const seconds = Number(opt.seconds) > 0 ? Number(opt.seconds) : 0;
        const resources = ['metal', 'crystal', 'deuterium', 'energy'];

        const usable = {};
        const missing = {};
        let total = 0;
        let affordable = true;
        let waitSeconds = 0;
        let waitKnown = !!production;

        for (const resource of resources) {
            let available = (Number(have[resource]) || 0) - reserve;
            if (production) {
                const perHour = Number(production[resource]) || 0;
                available += (perHour / 3600) * seconds;
            }
            if (available < 0) available = 0;
            usable[resource] = available;

            const gap = (Number(want[resource]) || 0) - available;
            missing[resource] = gap > 0 ? gap : 0;
            if (missing[resource] > 0) affordable = false;
            if (resource !== 'energy') total += missing[resource];
        }

        if (!affordable) {
            for (const resource of resources) {
                if (missing[resource] <= 0) continue;
                if (!production) continue;
                const perSecond = (Number(production[resource]) || 0) / 3600;
                if (perSecond > 0) {
                    const secondsLeft = Math.ceil(missing[resource] / perSecond);
                    if (secondsLeft > waitSeconds) waitSeconds = secondsLeft;
                } else {
                    // Something is missing and nothing is produced: never.
                    waitSeconds = Infinity;
                }
            }
        }

        return {
            metal: missing.metal,
            crystal: missing.crystal,
            deuterium: missing.deuterium,
            energy: missing.energy,
            total: total,
            missingTotal: total,
            affordable: affordable,
            stock: usable,
            waitSeconds: affordable ? 0 : (waitKnown ? waitSeconds : null),
            waitKnown: waitKnown,
        };
    }

    /**
     * Cost and shortage of a whole build queue.
     *
     * Every item is `{gid, fromLevel, toLevel}`; the shorter `{gid, level}` form
     * means "build that level next" (from `level - 1`). Unknown objects and invalid
     * items contribute nothing.
     *
     * @param {object} stock Stock of the planet.
     * @param {Array<{gid: number, level?: number, fromLevel?: number, toLevel?: number}>} items Queue items.
     * @param {object} [unitsData] Object tables.
     * @param {object} [options] Options of `needs` (reserve, production, seconds).
     * @returns {{cost: {metal: number, crystal: number, deuterium: number, energy: number, total: number},
     *            needs: object}} Summed cost and the shortage of the whole queue.
     */
    function queueNeeds(stock, items, unitsData, options) {
        const list = Array.isArray(items) ? items : [];
        const cost = { metal: 0, crystal: 0, deuterium: 0, energy: 0, total: 0 };

        for (const item of list) {
            if (!item || item.gid === undefined || item.gid === null) continue;
            const to = item.toLevel === undefined ? item.level : item.toLevel;
            const from = item.toLevel === undefined
                ? (item.level === undefined ? 0 : safeLevel(item.level) - 1)
                : item.fromLevel;
            const itemCost = totalCost(item.gid, from === undefined ? 0 : from, to, unitsData);
            cost.metal += itemCost.metal;
            cost.crystal += itemCost.crystal;
            cost.deuterium += itemCost.deuterium;
            cost.energy += itemCost.energy;
        }
        cost.total = cost.metal + cost.crystal + cost.deuterium;

        return { cost: cost, needs: needs(stock, cost, options) };
    }

    const api = {
        MAX_LEVEL: MAX_LEVEL,
        levelCost: levelCost,
        totalCost: totalCost,
        needs: needs,
        queueNeeds: queueNeeds,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
