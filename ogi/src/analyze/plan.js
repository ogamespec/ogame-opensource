/**
 * OGame Infinity 0.84 — resource plan (the "resource lock" of the original).
 *
 * The build pages let the player step the level of an object and lock it into a
 * plan; the plan adds up the cost of every locked object and compares it with the
 * resources that are on the planet right now, so it answers "what do I still need
 * before I can build all of this".
 *
 * Two kinds of objects are supported:
 *   - buildings and research: a level, the cost of every level comes from the object
 *     tables of the server (`analyze/needs.js`);
 *   - ships and defence: an amount, the cost is the amount times the unit price.
 */
(function (root) {
    'use strict';

    const needs = typeof require === 'function' ? require('./needs.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;

    const RESOURCES = [700, 701, 702, 703];
    const RESOURCE_NAMES = { 700: 'metal', 701: 'crystal', 702: 'deuterium', 703: 'energy' };

    /**
     * Is an object stepped by level (a building or a research) instead of amount?
     *
     * @param {string} kind Object kind.
     * @returns {boolean} True for a level.
     */
    function byLevel(kind) {
        return kind === 'research' || kind === 'building' || kind === 'building-moon';
    }

    /**
     * Cost of one unit of an object.
     *
     * @param {number} gid Object id.
     * @param {object} unitsData Object tables.
     * @returns {{metal: number, crystal: number, deuterium: number, energy: number, total: number}} Cost.
     */
    function unitCost(gid, unitsData) {
        const cost = ((unitsData || {}).costs || {})[gid] || {};
        const metal = cost.metal || 0;
        const crystal = cost.crystal || 0;
        const deuterium = cost.deuterium || 0;
        const energy = cost.energy || 0;
        return { metal: metal, crystal: crystal, deuterium: deuterium, energy: energy, total: metal + crystal + deuterium };
    }

    /**
     * Cost of one plan entry.
     *
     * @param {object} entry Entry: gid, kind, from, to.
     * @param {object} unitsData Object tables.
     * @returns {object} Cost by resource.
     */
    function entryCost(entry, unitsData) {
        if (byLevel(entry.kind)) {
            const from = entry.from || 0;
            const to = entry.to || 0;
            if (to <= from) return { metal: 0, crystal: 0, deuterium: 0, energy: 0, total: 0 };
            return needs.totalCost(entry.gid, from, to, unitsData);
        }
        const amount = Math.max(0, (entry.to || 0) - (entry.from || 0));
        const unit = unitCost(entry.gid, unitsData);
        return {
            metal: unit.metal * amount,
            crystal: unit.crystal * amount,
            deuterium: unit.deuterium * amount,
            energy: unit.energy * amount,
            total: unit.total * amount,
        };
    }

    /**
     * Normalise a stored plan into a list of entries with their costs.
     *
     * @param {object} plan Plan: `{ "106": {from: 5, to: 7, kind: "research", locked: true} }`.
     * @param {object} [options] Options: unitsData, onlyLocked (default true).
     * @returns {Array} Entries.
     */
    function planEntries(plan, options) {
        const opt = options || {};
        const onlyLocked = opt.onlyLocked !== false;
        const entries = [];
        for (const key of Object.keys(plan || {})) {
            const stored = plan[key];
            if (!stored) continue;
            if (onlyLocked && !stored.locked) continue;
            const gid = parseInt(key, 10);
            const entry = {
                gid: gid,
                name: stored.name || null,
                kind: stored.kind || 'building',
                from: stored.from || 0,
                to: stored.to || 0,
                locked: !!stored.locked,
                byLevel: byLevel(stored.kind),
            };
            entry.cost = entryCost(entry, opt.unitsData);
            entry.steps = Math.max(0, entry.to - entry.from);
            entries.push(entry);
        }
        entries.sort(function (a, b) {
            return b.cost.total - a.cost.total;
        });
        return entries;
    }

    /**
     * Total cost of a list of entries.
     *
     * @param {Array} entries Entries.
     * @returns {{metal: number, crystal: number, deuterium: number, energy: number, total: number}} Total.
     */
    function planTotals(entries) {
        const total = { metal: 0, crystal: 0, deuterium: 0, energy: 0, total: 0 };
        for (const entry of entries || []) {
            for (const gid of RESOURCES) {
                total[RESOURCE_NAMES[gid]] += entry.cost[RESOURCE_NAMES[gid]] || 0;
            }
            total.total += entry.cost.total || 0;
        }
        return total;
    }

    /**
     * What is still missing, compared with the resources at hand.
     *
     * @param {object} totals Total cost.
     * @param {object} stock Stock: `{ 700: n, 701: n, … }` or `{metal: n, …}`.
     * @returns {{metal: number, crystal: number, deuterium: number, energy: number, missing: number, complete: boolean}} Deficits.
     */
    function planDeficits(totals, stock) {
        const source = stock || {};
        const have = {};
        for (const gid of RESOURCES) {
            const name = RESOURCE_NAMES[gid];
            have[name] = source[name] !== undefined ? source[name] : (source[gid] || 0);
        }
        const deficits = { metal: 0, crystal: 0, deuterium: 0, energy: 0, missing: 0, complete: true, stock: have };
        for (const gid of RESOURCES) {
            const name = RESOURCE_NAMES[gid];
            const missing = Math.max(0, (totals[name] || 0) - have[name]);
            deficits[name] = missing;
            if (name !== 'energy') deficits.missing += missing;
            if (missing > 0) deficits.complete = false;
        }
        return deficits;
    }

    /**
     * The whole summary of a plan.
     *
     * @param {object} plan Plan as stored.
     * @param {object} [options] Options: unitsData, stock, onlyLocked.
     * @returns {object} Summary: entries, totals, deficits, locked count.
     */
    function planSummary(plan, options) {
        const opt = options || {};
        const entries = planEntries(plan, opt);
        const totals = planTotals(entries);
        return {
            entries: entries,
            totals: totals,
            deficits: planDeficits(totals, opt.stock),
            locked: Object.keys(plan || {}).filter(function (key) {
                return plan[key] && plan[key].locked;
            }).length,
        };
    }

    /**
     * Step the target of one object, clamped to the limits of the engine.
     *
     * @param {number} value Current target.
     * @param {number} delta Change.
     * @returns {number} New target.
     */
    function clampTarget(value, delta) {
        const next = (value || 0) + delta;
        if (next < 0) return 0;
        if (next > needs.MAX_LEVEL) return needs.MAX_LEVEL;
        return next;
    }

    /**
     * Read the stock of the current planet from the parsed resource bar.
     *
     * @param {object} data Store data.
     * @returns {object} Stock by resource id.
     */
    function stockOf(data) {
        const meta = (data && data.meta) || {};
        return meta.stock || {};
    }

    const api = {
        RESOURCES: RESOURCES,
        RESOURCE_NAMES: RESOURCE_NAMES,
        byLevel: byLevel,
        unitCost: unitCost,
        entryCost: entryCost,
        planEntries: planEntries,
        planTotals: planTotals,
        planDeficits: planDeficits,
        planSummary: planSummary,
        clampTarget: clampTarget,
        stockOf: stockOf,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
