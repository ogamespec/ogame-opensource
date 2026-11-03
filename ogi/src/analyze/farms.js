/**
 * OGame Infinity 0.84 — farm list (profitable targets).
 *
 * OGame Infinity sorts its spy database into a "farm list": the planets worth
 * attacking, ranked by what can be carried away, filtered by activity and by the
 * age of the report. On 0.84 the same numbers come from the spy reports the
 * extension has already collected plus the activity markers of the galaxy page.
 */
(function (root) {
    'use strict';

    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;
    const recycling = typeof require === 'function' ? require('./recycling.js') : root.OGI;

    /**
     * Coordinates as "g:s:p".
     *
     * @param {object} coords Coordinates.
     * @returns {string} Text.
     */
    function coordsText(coords) {
        if (!coords) return '';
        return coords.g + ':' + coords.s + ':' + coords.p;
    }

    /**
     * Build the farm list from the collected spy reports.
     *
     * One row per planet (the newest report wins), because the point of the list is
     * "where can I fly now", not "what did I ever see".
     *
     * @param {object} data Store data.
     * @param {object} [options] Options: unitsData, settings, now, maxAgeHours,
     *        inactiveOnly, minLoot, limit, session.
     * @returns {Array} Targets, most profitable first.
     */
    function farmList(data, options) {
        const opt = options || {};
        const now = opt.now === undefined ? Math.floor(Date.now() / 1000) : opt.now;
        const units = opt.unitsData || (typeof require === 'function' ? require('../data/units.js') : root.OGI.UNITS);
        const settings = opt.settings || { debrisFactor: 30, debrisFactorDef: 0, deuteriumInDebris: false };
        const maxAge = (opt.maxAgeHours === undefined ? 168 : opt.maxAgeHours) * 3600;
        const minLoot = opt.minLoot === undefined ? 0 : opt.minLoot;

        const byKey = {};
        for (const spy of (data && data.spies) || []) {
            if (!spy.coords) continue;
            const key = coordsText(spy.coords);
            if (!byKey[key] || (byKey[key].date || 0) < (spy.date || 0)) byKey[key] = spy;
        }

        const rows = [];
        for (const key of Object.keys(byKey)) {
            const spy = byKey[key];
            const age = Math.max(0, now - (spy.date || 0));
            if (age > maxAge) continue;

            const metal = (spy.resources && spy.resources[700]) || 0;
            const crystal = (spy.resources && spy.resources[701]) || 0;
            const deuterium = (spy.resources && spy.resources[702]) || 0;
            const loot = numbers.maxLoot(metal, crystal, deuterium);
            if (loot.total < minLoot) continue;

            const activity = (data.activity || {})[key] || [];
            const lastActivity = activity.length ? activity[activity.length - 1] : null;
            const active = !!lastActivity && (lastActivity.marker === '*' || now - lastActivity.t < 900);
            if (opt.inactiveOnly && active) continue;

            const fleetDebris = recycling.recyclingYield(spy.fleet || {}, units, settings);
            const defenceDebris = recycling.recyclingYield(spy.defence || {}, units, settings);
            const capacities = { small: (units.params[202] || {}).cargo || 5000, large: (units.params[203] || {}).cargo || 25000 };

            rows.push({
                key: key,
                coords: spy.coords,
                player: spy.player || null,
                planet: spy.planet || null,
                date: spy.date || 0,
                ageHours: Math.round(age / 3600),
                loot: loot,
                needed: {
                    smallCargo: numbers.neededShips(loot.total, capacities.small),
                    largeCargo: numbers.neededShips(loot.total, capacities.large),
                },
                fleetValue: unitsWorth(spy.fleet, units),
                defenceValue: unitsWorth(spy.defence, units),
                debris: { total: fleetDebris.total + defenceDebris.total },
                recyclers: recycling.recyclersNeeded(fleetDebris.total + defenceDebris.total, units),
                active: active,
                lastActivity: lastActivity ? lastActivity.t : null,
                sessions: activity.length,
                type: ((data.planets || {})[key] || {}).type || 'planet',
            });
        }

        rows.sort(function (a, b) {
            if (b.loot.total !== a.loot.total) return b.loot.total - a.loot.total;
            return b.ageHours - a.ageHours;
        });
        return opt.limit ? rows.slice(0, opt.limit) : rows;
    }

    /**
     * Resource value of a unit map.
     *
     * @param {object} unitsMap Amounts by unit id.
     * @param {object} unitsData Object tables.
     * @returns {number} Total cost.
     */
    function unitsWorth(unitsMap, unitsData) {
        let total = 0;
        for (const gid of Object.keys(unitsMap || {})) {
            const cost = (unitsData.costs || {})[gid];
            if (!cost) continue;
            total += ((cost.metal || 0) + (cost.crystal || 0) + (cost.deuterium || 0)) * unitsMap[gid];
        }
        return total;
    }

    /**
     * Deep links into the fleet dispatcher for a target.
     *
     * @param {object} target Farm list row.
     * @param {string} session Session id.
     * @returns {object} Links by purpose.
     */
    function targetLinks(target, session) {
        const base = 'index.php?page=flotten1&session=' + encodeURIComponent(session || '') +
            '&galaxy=' + target.coords.g + '&system=' + target.coords.s + '&planet=' + target.coords.p +
            '&planettype=' + (target.type === 'moon' ? 3 : 1);
        return {
            attack: base + '&target_mission=1',
            transport: base + '&target_mission=3',
            spy: base + '&target_mission=6',
            deploy: base + '&target_mission=4',
            recycle: base + '&target_mission=8',
        };
    }

    const api = {
        coordsText: coordsText,
        farmList: farmList,
        targetLinks: targetLinks,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
