/**
 * OGame Infinity 0.84 — recycling yield (debris calculator).
 *
 * A port of `CalcDebris()` from the engine (game/core/battle.php):
 *
 *     $coef = IsDefense($gid) ? $did : $fid;                  // debris percentage
 *     $cost = TechPrice ($gid, 1);                            // base cost of one unit
 *     $debris[$rc] += intval (ceil($cost[$rc] * $diff * ($coef / 100.0)));
 *
 * so the debris of a lost unit is a percentage of its **resource cost**, rounded up
 * per unit id and resource. For an L.Fighter (3000 metal, 1000 crystal) at the 0.84
 * default of 30% that is 900 metal and 300 crystal — 1200 in total, which equals 30%
 * of its structure (4000) because cost and structure happen to match for ships.
 *
 * The universe settings of 0.84 are `fid` = 30 (fleet debris) and `did` = 0
 * (defence debris, so defence produces nothing by default), and the debris field
 * holds metal and crystal only (`$debrisResources` in game/core/techs.php) unless
 * the universe enables deuterium in debris.
 *
 * Known simplification: the server adds the *repaired* defence back to the surviving
 * units before computing the difference (see `$repaired` in `CalcDebris`); a report
 * does not say which defence was rebuilt, so the extension cannot subtract it. The
 * difference only matters when `debrisFactorDef` is greater than zero.
 */
(function (root) {
    'use strict';

    const units = typeof require === 'function' ? require('../data/units.js') : root.OGI.UNITS;

    // Resource ids of the game (game/core/techs.php).
    const GID_RC_METAL = 700;
    const GID_RC_CRYSTAL = 701;
    const GID_RC_DEUTERIUM = 702;

    // Cargo capacity of one recycler (game/core/techs.php, $UnitParam).
    const GID_F_RECYCLER = 209;
    const RECYCLER_CARGO = 20000;

    // Defaults of a 0.84 universe (game/install.php: uni_fid 30, uni_did 0).
    const DEFAULTS = {
        debrisFactor: 30,
        debrisFactorDef: 0,
        deuteriumInDebris: false,
    };

    /**
     * Merge the options with the 0.84 defaults.
     *
     * @param {object} [options] Options: debrisFactor, debrisFactorDef, deuteriumInDebris.
     * @returns {{debrisFactor: number, debrisFactorDef: number, deuteriumInDebris: boolean}} Options.
     */
    function normalizeOptions(options) {
        const opt = options || {};
        const factor = function (value, fallback) {
            const number = Number(value);
            return isFinite(number) && number >= 0 ? number : fallback;
        };
        return {
            debrisFactor: factor(opt.debrisFactor, DEFAULTS.debrisFactor),
            debrisFactorDef: factor(opt.debrisFactorDef, DEFAULTS.debrisFactorDef),
            deuteriumInDebris: !!opt.deuteriumInDebris,
        };
    }

    /**
     * Is the object a defence structure (including missiles)?
     *
     * @param {number} gid Object id.
     * @param {object} unitsData Object tables.
     * @returns {boolean} True for defence.
     */
    function isDefence(gid, unitsData) {
        const list = (unitsData || units).lists || {};
        return (list.defence || []).indexOf(gid) !== -1;
    }

    /**
     * Resources of a value: a plain number, or an object with a total or with
     * metal/crystal/deuterium fields.
     *
     * @param {number|object} value Value.
     * @returns {number} Amount.
     */
    function amountOf(value) {
        if (typeof value === 'number') return isFinite(value) ? value : 0;
        if (!value || typeof value !== 'object') return 0;
        if (typeof value.total === 'number') return value.total;
        return (value.metal || 0) + (value.crystal || 0) + (value.deuterium || 0);
    }

    /**
     * Debris produced by one set of destroyed units of a single participant.
     *
     * The rounding follows the server exactly: the debris is accumulated per unit id
     * and per resource with `ceil()`, so a mixed composition can differ by a few
     * units from 30% of the total cost.
     *
     * @param {Object<number|string, number>} destroyedUnits Destroyed amounts by object id.
     * @param {object} [unitsData] Object tables (costs and the defence list).
     * @param {object} [options] Options: debrisFactor, debrisFactorDef, deuteriumInDebris.
     * @returns {{metal: number, crystal: number, deuterium: number, total: number}} Debris.
     */
    function recyclingYield(destroyedUnits, unitsData, options) {
        const table = unitsData || units;
        const opt = normalizeOptions(options);
        const out = { metal: 0, crystal: 0, deuterium: 0 };

        for (const key of Object.keys(destroyedUnits || {})) {
            const amount = destroyedUnits[key];
            if (!(amount > 0)) continue;
            const gid = parseInt(key, 10);
            const cost = (table.costs || {})[gid];
            // A unit the object tables do not know produces no debris, exactly like
            // an object without an entry in the engine's $initial table.
            if (!cost) continue;
            const factor = isDefence(gid, table) ? opt.debrisFactorDef : opt.debrisFactor;
            if (!(factor > 0)) continue;

            out.metal += Math.ceil((cost.metal || 0) * amount * (factor / 100));
            out.crystal += Math.ceil((cost.crystal || 0) * amount * (factor / 100));
            if (opt.deuteriumInDebris) {
                out.deuterium += Math.ceil((cost.deuterium || 0) * amount * (factor / 100));
            }
        }

        out.total = out.metal + out.crystal + out.deuterium;
        return out;
    }

    /**
     * Debris of a whole parsed battle report: one yield per side and the field both
     * sides leave behind (the debris of the attackers and of the defenders lands in
     * the same field).
     *
     * @param {object} report Report of `parseBattleReport` (its `destroyed` field is used).
     * @param {object} [unitsData] Object tables.
     * @param {object} [options] Options: debrisFactor, debrisFactorDef, deuteriumInDebris.
     * @returns {{attackers: object, defenders: object, total: object}} Debris per side and in total.
     */
    function battleRecycling(report, unitsData, options) {
        const destroyed = (report && report.destroyed) || { attackers: [], defenders: [] };

        const side = function (participants) {
            // Per participant, as in CalcDebris(): the rounding is applied before the
            // participants are summed up.
            const sum = { metal: 0, crystal: 0, deuterium: 0 };
            for (const participant of participants || []) {
                const yield_ = recyclingYield(participant.units || {}, unitsData, options);
                sum.metal += yield_.metal;
                sum.crystal += yield_.crystal;
                sum.deuterium += yield_.deuterium;
            }
            sum.total = sum.metal + sum.crystal + sum.deuterium;
            return sum;
        };

        const attackers = side(destroyed.attackers);
        const defenders = side(destroyed.defenders);
        const total = {
            metal: attackers.metal + defenders.metal,
            crystal: attackers.crystal + defenders.crystal,
            deuterium: attackers.deuterium + defenders.deuterium,
        };
        total.total = total.metal + total.crystal + total.deuterium;
        return { attackers: attackers, defenders: defenders, total: total };
    }

    /**
     * How many recyclers are needed to collect an amount of debris.
     *
     * @param {number|object} amount Amount: a number or {metal, crystal, deuterium}.
     * @param {object} [unitsData] Object tables (the recycler cargo is read from them).
     * @returns {number} Number of recyclers (0 for an empty field).
     */
    function recyclersNeeded(amount, unitsData) {
        const table = unitsData || units;
        const params = (table.params || {})[GID_F_RECYCLER];
        const capacity = params && params.cargo ? params.cargo : RECYCLER_CARGO;
        const total = amountOf(amount);
        if (!(total > 0)) return 0;
        return Math.ceil(total / capacity);
    }

    /**
     * Net resource result of a battle for the attacker:
     *
     *     net = loot + ownYield - ownLosses
     *
     * where `loot` is what the fleet brought home, `ownYield` is the debris of the
     * attacker's own losses (real only once recyclers have collected it, and only if
     * nobody else gets there first) and `ownLosses` is the resource value of the
     * attacker's losses as the report counts them. The defender's debris is not
     * included — it belongs to whoever recycles the field, see `battleRecycling()`.
     *
     * @param {number|object} loot Loot taken from the target.
     * @param {number|object} ownYield Debris produced by the attacker's own losses.
     * @param {number|object} ownLosses Attacker losses.
     * @returns {{loot: number, ownYield: number, ownLosses: number, net: number, profitable: boolean}} Result.
     */
    function profit(loot, ownYield, ownLosses) {
        const taken = amountOf(loot);
        const debris = amountOf(ownYield);
        const lost = amountOf(ownLosses);
        const net = taken + debris - lost;
        return {
            loot: taken,
            ownYield: debris,
            ownLosses: lost,
            net: net,
            profitable: net > 0,
        };
    }

    const api = {
        DEFAULTS: DEFAULTS,
        GID_RC_METAL: GID_RC_METAL,
        GID_RC_CRYSTAL: GID_RC_CRYSTAL,
        GID_RC_DEUTERIUM: GID_RC_DEUTERIUM,
        RECYCLER_CARGO: RECYCLER_CARGO,
        normalizeOptions: normalizeOptions,
        isDefence: isDefence,
        recyclingYield: recyclingYield,
        battleRecycling: battleRecycling,
        recyclersNeeded: recyclersNeeded,
        profit: profit,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
