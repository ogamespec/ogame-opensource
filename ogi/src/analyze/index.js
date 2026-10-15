/**
 * OGame Infinity 0.84 — analyzers.
 *
 * Everything here works on the records collected by the parsers and stored by
 * src/lib/store.js. The summaries are the 0.84 equivalents of the OGame Infinity
 * "message analyzer" panels: daily expedition / combat / harvest / trade sums, the
 * spy report table with loot and rentability, and the observed activity history.
 *
 * The formulas that are game rules (the plunder cap of 50% and the cargo fill
 * order) are ports of game/core/battle.php and are covered by the test suite.
 */
(function (root) {
    'use strict';

    const numbers = typeof require === 'function' ? require('../lib/numbers.js') : root.OGI;
    const units = typeof require === 'function' ? require('../data/units.js') : root.OGI.UNITS;
    
    const GID_RC_METAL = 700;
    const GID_RC_CRYSTAL = 701;
    const GID_RC_DEUTERIUM = 702;
    const GID_RC_ENERGY = 703;
    const GID_RC_DM = 704;

    // Cargo capacities of the 0.84 ships (game/core/techs.php, $UnitParam).
    const CARGO_SHIPS = {
        probe: 210,
        smallCargo: 202,
        largeCargo: 203,
        recycler: 209,
    };

    // Classic trade rate of the 0.84 era, used for the "standard unit" column.
    const TRADE_RATE = [3, 2, 1];

    /**
     * Local day key of a timestamp.
     *
     * @param {number} timestamp Unix seconds.
     * @returns {string} "YYYY-MM-DD".
     */
    function dayKey(timestamp) {
        const date = new Date((timestamp || 0) * 1000);
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return date.getFullYear() + '-' + month + '-' + day;
    }

    /**
     * Cost of one unit.
     *
     * @param {number} gid Object id.
     * @param {object} [unitsData] Object tables.
     * @returns {{metal: number, crystal: number, deuterium: number, total: number}} Cost.
     */
    function unitCost(gid, unitsData) {
        const table = (unitsData || units).costs || {};
        const cost = table[gid];
        if (!cost) return { metal: 0, crystal: 0, deuterium: 0, total: 0 };
        return {
            metal: cost.metal || 0,
            crystal: cost.crystal || 0,
            deuterium: cost.deuterium || 0,
            total: (cost.metal || 0) + (cost.crystal || 0) + (cost.deuterium || 0),
        };
    }

    /**
     * Value of a set of units ("{204: 10, 401: 3}").
     *
     * @param {Object<number, number>} amounts Amounts by object id.
     * @param {object} [unitsData] Object tables.
     * @returns {{metal: number, crystal: number, deuterium: number, total: number, standardUnit: number, count: number}} Value.
     */
    function unitsValue(amounts, unitsData) {
        const out = { metal: 0, crystal: 0, deuterium: 0, total: 0, standardUnit: 0, count: 0 };
        for (const key of Object.keys(amounts || {})) {
            const amount = amounts[key] || 0;
            if (!(amount > 0)) continue;
            const cost = unitCost(parseInt(key, 10), unitsData);
            out.metal += cost.metal * amount;
            out.crystal += cost.crystal * amount;
            out.deuterium += cost.deuterium * amount;
            out.total += cost.total * amount;
            out.count += amount;
        }
        out.standardUnit = numbers.standardUnit([out.metal, out.crystal, out.deuterium], TRADE_RATE, 2);
        return out;
    }

    /**
     * Cargo capacities of the transport ships, as the game defines them.
     *
     * @param {object} [unitsData] Object tables.
     * @returns {object} Capacities by ship role.
     */
    function cargoCapacities(unitsData) {
        const table = (unitsData || units).params || {};
        const out = {};
        for (const role of Object.keys(CARGO_SHIPS)) {
            const params = table[CARGO_SHIPS[role]];
            out[role] = params ? params.cargo : 0;
        }
        return out;
    }

    /**
     * Full analysis of a spy report: resources, loot, required cargos, fleet and
     * defence values. This is the 0.84 counterpart of the OGame Infinity spy row
     * (loot, ratio, needed ships) with the server's own plunder algorithm.
     *
     * @param {object} spy Stored spy report.
     * @param {object} [options] Options: unitsData, cargo (planned capacity), tradeRate.
     * @returns {object} Analysis.
     */
    function spyAnalysis(spy, options) {
        const opt = options || {};
        const unitsData = opt.unitsData || units;
        const resources = spy.resources || {};
        const metal = resources[GID_RC_METAL] || 0;
        const crystal = resources[GID_RC_CRYSTAL] || 0;
        const deuterium = resources[GID_RC_DEUTERIUM] || 0;
        const energy = resources[GID_RC_ENERGY] || 0;
        const total = metal + crystal + deuterium;

        const maxLoot = numbers.maxLoot(metal, crystal, deuterium);
        const capacities = cargoCapacities(unitsData);
        const needed = {};
        for (const role of Object.keys(capacities)) {
            needed[role] = numbers.neededShips(maxLoot.total, capacities[role]);
        }

        const cargo = opt.cargo === undefined ? capacities.largeCargo : opt.cargo;
        const loot = numbers.plunder(cargo, metal, crystal, deuterium);

        const ratio = total > 0
            ? [
                Math.round((metal / total) * 100),
                Math.round((crystal / total) * 100),
                Math.round((deuterium / total) * 100),
            ]
            : [0, 0, 0];

        return {
            coords: spy.coords || null,
            planet: spy.planet || null,
            player: spy.player || null,
            date: spy.date || null,
            resources: { metal: metal, crystal: crystal, deuterium: deuterium, energy: energy, total: total },
            ratio: ratio,
            maxLoot: maxLoot,
            loot: loot,
            cargo: cargo,
            needed: needed,
            capacities: capacities,
            fleet: spy.fleet || {},
            defence: spy.defence || {},
            fleetValue: unitsValue(spy.fleet || {}, unitsData),
            defenceValue: unitsValue(spy.defence || {}, unitsData),
            buildings: Object.keys(spy.buildings || {}).length,
            research: Object.keys(spy.research || {}).length,
        };
    }

    /**
     * Group records by day.
     *
     * @param {Array} records Records with a `date` field.
     * @returns {Object<string, Array>} Records by day, days descending.
     */
    function groupByDay(records) {
        const out = {};
        for (const record of records || []) {
            const key = dayKey(record.date);
            if (!out[key]) out[key] = [];
            out[key].push(record);
        }
        return out;
    }

    /**
     * Per-day expedition statistics.
     *
     * @param {Array} records Expedition records.
     * @returns {Array} Days, newest first.
     */
    function expeditionStats(records) {
        const days = groupByDay(records);
        return Object.keys(days).sort().reverse().map(function (day) {
            const list = days[day];
            const stats = {
                day: day,
                count: list.length,
                types: {},
                found: { metal: 0, crystal: 0, deuterium: 0, darkmatter: 0, ships: 0 },
                shipCount: 0,
                losses: 0,
            };
            for (const record of list) {
                stats.types[record.type] = (stats.types[record.type] || 0) + 1;
                if (record.resource && record.resource.gid === GID_RC_DM) {
                    stats.found.darkmatter += record.resource.amount;
                } else if (record.resource && record.resource.gid === GID_RC_CRYSTAL) {
                    stats.found.crystal += record.resource.amount;
                } else if (record.resource && record.resource.gid === GID_RC_DEUTERIUM) {
                    stats.found.deuterium += record.resource.amount;
                } else if (record.resource) {
                    stats.found.metal += record.resource.amount;
                }
                if (record.ships && Object.keys(record.ships).length) {
                    stats.found.ships += unitsValue(record.ships).total;
                    for (const ship of Object.keys(record.ships)) stats.shipCount += record.ships[ship];
                }
                if (record.type === 'lost') stats.losses++;
            }
            return stats;
        });
    }

    /**
     * Per-day combat statistics.
     *
     * @param {Array} records Combat records.
     * @returns {Array} Days, newest first.
     */
    function combatStats(records) {
        const days = groupByDay(records);
        return Object.keys(days).sort().reverse().map(function (day) {
            const list = days[day];
            const stats = {
                day: day,
                count: list.length,
                wins: 0,
                losses: 0,
                draws: 0,
                loot: { metal: 0, crystal: 0, deuterium: 0, total: 0 },
                ownLosses: 0,
                enemyLosses: 0,
                debris: { metal: 0, crystal: 0, total: 0 },
                net: 0,
            };
            for (const record of list) {
                if (record.result === 'iwon') stats.wins++;
                else if (record.result === 'ilost') stats.losses++;
                else if (record.result === 'draw') stats.draws++;
                const own = ownLoss(record);
                if (record.loot) {
                    stats.loot.metal += record.loot.metal || 0;
                    stats.loot.crystal += record.loot.crystal || 0;
                    stats.loot.deuterium += record.loot.deuterium || 0;
                }
                stats.ownLosses += own;
                stats.enemyLosses += enemyLoss(record);
                if (record.debris) {
                    stats.debris.metal += record.debris.metal || 0;
                    stats.debris.crystal += record.debris.crystal || 0;
                }
            }
            stats.loot.total = stats.loot.metal + stats.loot.crystal + stats.loot.deuterium;
            stats.debris.total = stats.debris.metal + stats.debris.crystal;
            stats.net = stats.loot.total - stats.ownLosses;
            return stats;
        });
    }

    /**
     * Own losses of a combat record (the attacker/defender side is encoded in the
     * CSS class of the message subject, see parsers/messages.js).
     *
     * @param {object} record Combat record.
     * @returns {number} Own losses.
     */
    function ownLoss(record) {
        const losses = record.losses || {};
        if (record.side === 'defender') return losses.defender || 0;
        return losses.attacker || 0;
    }

    /**
     * Enemy losses of a combat record.
     *
     * @param {object} record Combat record.
     * @returns {number} Enemy losses.
     */
    function enemyLoss(record) {
        const losses = record.losses || {};
        if (record.side === 'defender') return losses.attacker || 0;
        return losses.defender || 0;
    }

    /**
     * Per-day harvest (recycling) statistics.
     *
     * @param {Array} records Harvest records.
     * @returns {Array} Days, newest first.
     */
    function harvestStats(records) {
        const days = groupByDay(records);
        return Object.keys(days).sort().reverse().map(function (day) {
            const list = days[day];
            const stats = { day: day, count: list.length, metal: 0, crystal: 0, total: 0, recyclers: 0 };
            for (const record of list) {
                stats.metal += (record.harvested && record.harvested.metal) || 0;
                stats.crystal += (record.harvested && record.harvested.crystal) || 0;
                stats.recyclers += record.recyclers || 0;
            }
            stats.total = stats.metal + stats.crystal;
            return stats;
        });
    }

    /**
     * Per-day trade balance ("transport-out" is what left the empire,
     * "transport-in" what arrived from other players).
     *
     * @param {Array} records Trade records.
     * @returns {Array} Days, newest first.
     */
    function tradeStats(records) {
        const days = groupByDay(records);
        return Object.keys(days).sort().reverse().map(function (day) {
            const list = days[day];
            const stats = {
                day: day,
                count: list.length,
                out: { metal: 0, crystal: 0, deuterium: 0, total: 0 },
                in: { metal: 0, crystal: 0, deuterium: 0, total: 0 },
            };
            for (const record of list) {
                const bucket = record.kind === 'transport-in' ? stats.in : stats.out;
                bucket.metal += (record.delivered && record.delivered.metal) || 0;
                bucket.crystal += (record.delivered && record.delivered.crystal) || 0;
                bucket.deuterium += (record.delivered && record.delivered.deuterium) || 0;
            }
            stats.out.total = stats.out.metal + stats.out.crystal + stats.out.deuterium;
            stats.in.total = stats.in.metal + stats.in.crystal + stats.in.deuterium;
            stats.balance = stats.in.total - stats.out.total;
            return stats;
        });
    }

    /**
     * Total statistics over the whole database.
     *
     * @param {object} data Store data.
     * @returns {object} Totals.
     */
    function totals(data) {
        const db = data || {};
        const exp = expeditionStats(db.expeditions);
        const combats = combatStats(db.combats);
        const harvests = harvestStats(db.harvests);
        const trades = tradeStats(db.trades);

        function sum(list, pick) {
            return list.reduce(function (acc, entry) { return acc + pick(entry); }, 0);
        }

        return {
            spies: (db.spies || []).length,
            planets: Object.keys(db.planets || {}).length,
            players: Object.keys(db.players || {}).length,
            expeditions: (db.expeditions || []).length,
            expeditionFound: {
                metal: sum(exp, function (e) { return e.found.metal; }),
                crystal: sum(exp, function (e) { return e.found.crystal; }),
                deuterium: sum(exp, function (e) { return e.found.deuterium; }),
                darkmatter: sum(exp, function (e) { return e.found.darkmatter; }),
                ships: sum(exp, function (e) { return e.found.ships; }),
            },
            combats: {
                count: (db.combats || []).length,
                wins: sum(combats, function (c) { return c.wins; }),
                losses: sum(combats, function (c) { return c.losses; }),
                draws: sum(combats, function (c) { return c.draws; }),
                loot: sum(combats, function (c) { return c.loot.total; }),
                ownLosses: sum(combats, function (c) { return c.ownLosses; }),
                enemyLosses: sum(combats, function (c) { return c.enemyLosses; }),
                debris: sum(combats, function (c) { return c.debris.total; }),
                net: sum(combats, function (c) { return c.net; }),
            },
            harvests: {
                count: (db.harvests || []).length,
                metal: sum(harvests, function (h) { return h.metal; }),
                crystal: sum(harvests, function (h) { return h.crystal; }),
                total: sum(harvests, function (h) { return h.total; }),
            },
            trades: {
                count: (db.trades || []).length,
                in: sum(trades, function (t) { return t.in.total; }),
                out: sum(trades, function (t) { return t.out.total; }),
                balance: sum(trades, function (t) { return t.balance; }),
            },
        };
    }

    /**
     * Observed activity of a planet or moon (the 0.84 galaxy page marks activity
     * with "(*)" for less than 15 minutes and "(N min)" up to an hour).
     *
     * @param {Array} observations Observations of one coordinate.
     * @returns {object} Activity summary.
     */
    function activitySummary(observations) {
        const list = (observations || []).slice().sort(function (a, b) { return a.t - b.t; });
        if (!list.length) return { observations: 0, first: null, last: null, sessions: 0, activeNow: false };
        let sessions = 1;
        for (let i = 1; i < list.length; i++) {
            if (list[i].t - list[i - 1].t > 1800) sessions++;
        }
        const last = list[list.length - 1];
        return {
            observations: list.length,
            first: list[0].t,
            last: last.t,
            sessions: sessions,
            activeNow: last.marker === '*',
            markers: list.slice(-50),
        };
    }

    /**
     * The spy report table, newest first, with the analysis of every row.
     *
     * @param {object} data Store data.
     * @param {object} [options] Options: unitsData, cargo, limit.
     * @returns {Array} Analysed spy reports.
     */
    function spyTable(data, options) {
        const opt = options || {};
        const list = (data && data.spies) || [];
        const limit = opt.limit || 200;
        return list.slice(0, limit).map(function (spy) {
            return spyAnalysis(spy, opt);
        });
    }

    const api = {
        GID_RC_METAL: GID_RC_METAL,
        GID_RC_CRYSTAL: GID_RC_CRYSTAL,
        GID_RC_DEUTERIUM: GID_RC_DEUTERIUM,
        GID_RC_ENERGY: GID_RC_ENERGY,
        GID_RC_DM: GID_RC_DM,
        CARGO_SHIPS: CARGO_SHIPS,
        TRADE_RATE: TRADE_RATE,
        dayKey: dayKey,
        unitCost: unitCost,
        unitsValue: unitsValue,
        cargoCapacities: cargoCapacities,
        spyAnalysis: spyAnalysis,
        spyTable: spyTable,
        groupByDay: groupByDay,
        expeditionStats: expeditionStats,
        combatStats: combatStats,
        harvestStats: harvestStats,
        tradeStats: tradeStats,
        totals: totals,
        activitySummary: activitySummary,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
