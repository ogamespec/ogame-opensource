/**
 * OGame Infinity 0.84 — production formulas of the engine.
 *
 * Ported from `game/core/prod.php` ($PlanetProd), so the numbers the extension
 * computes are the numbers the server uses:
 *
 *   metal mine    floor(30 · L · 1.1^L · f) × speed
 *   crystal mine  floor(20 · L · 1.1^L · f) × speed
 *   deuterium     floor(10 · L · 1.1^L · f) × (1.28 − 0.002 · (temp + 40)) × speed
 *   solar plant   floor(20 · L · 1.1^L · f)
 *   fusion plant  floor(30 · L · (1.05 + energyTech · 0.01)^L · f)
 *
 * with `f` being the planet's own production factor for that building (1 on a normal
 * planet, changed by some events) and `speed` the economy speed of the universe.
 * Energy consumption: ceil(10 · L · 1.1^L · f) for the metal and crystal mines,
 * ceil(20 · …) for the deuterium synthesiser, ceil(10 · …) deuterium for fusion.
 *
 * When the server itself shows a production value (the "Сырьё" page does), the factor
 * can be recovered from it with `calibrate`, which is exact even when the planet
 * temperature is unknown.
 */
(function (root) {
    'use strict';

    const GID_B_METAL_MINE = 1;
    const GID_B_CRYS_MINE = 2;
    const GID_B_DEUT_SYNTH = 3;
    const GID_B_SOLAR = 4;
    const GID_B_FUSION = 12;
    const GID_R_ENERGY = 113;

    const MINES = [GID_B_METAL_MINE, GID_B_CRYS_MINE, GID_B_DEUT_SYNTH];
    const POWER = [GID_B_SOLAR, GID_B_FUSION];

    // Officer bonuses of the 0.84 engine (see ProdBonus/ConsBonus in prod.php).
    const OFFICERS = { geologist: 1.1, engineer: 1.1 };

    /**
     * Base term `k · L · 1.1^L · f`.
     *
     * @param {number} k Constant of the formula.
     * @param {number} level Building level.
     * @param {number} factor Planet production factor.
     * @returns {number} Value.
     */
    function base(k, level, factor) {
        if (!(level > 0)) return 0;
        return k * level * Math.pow(1.1, level) * (factor === undefined ? 1 : factor);
    }

    /**
     * Deuterium temperature factor of the engine.
     *
     * @param {number} temp Planet temperature (maximum).
     * @returns {number} Factor.
     */
    function deutFactor(temp) {
        const value = 1.28 - 0.002 * ((temp === undefined || temp === null ? 0 : temp) + 40);
        return value > 0 ? value : 0;
    }

    /**
     * Production of one building per hour.
     *
     * @param {number} gid Building id.
     * @param {number} level Level.
     * @param {object} [options] Options: factor, speed, temp, energyTech, officer.
     * @returns {{gid: number, resource: number|null, amount: number, energy: number, deuterium: number}} Production.
     */
    function production(gid, level, options) {
        const opt = options || {};
        const factor = opt.factor === undefined ? 1 : opt.factor;
        const speed = opt.speed === undefined ? 1 : opt.speed;
        const officer = opt.officer ? OFFICERS.geologist : 1.0;
        const result = { gid: gid, resource: null, amount: 0, energy: 0, deuterium: 0 };

        if (gid === GID_B_METAL_MINE) {
            result.resource = 700;
            result.amount = Math.floor(base(30, level, factor) * officer) * speed;
            result.energy = -Math.ceil(base(10, level, factor));
        } else if (gid === GID_B_CRYS_MINE) {
            result.resource = 701;
            result.amount = Math.floor(base(20, level, factor) * officer) * speed;
            result.energy = -Math.ceil(base(10, level, factor));
        } else if (gid === GID_B_DEUT_SYNTH) {
            result.resource = 702;
            result.amount = Math.floor(base(10, level, factor) * officer * deutFactor(opt.temp)) * speed;
            result.energy = -Math.ceil(base(20, level, factor));
        } else if (gid === GID_B_SOLAR) {
            result.resource = 703;
            result.amount = Math.floor(base(20, level, factor) * (opt.engineer ? OFFICERS.engineer : 1));
        } else if (gid === GID_B_FUSION) {
            const energyTech = opt.energyTech || 0;
            result.resource = 703;
            result.amount = level > 0
                ? Math.floor(30 * level * Math.pow(1.05 + energyTech * 0.01, level) * factor *
                    (opt.engineer ? OFFICERS.engineer : 1))
                : 0;
            result.deuterium = -Math.ceil(base(10, level, factor));
        }
        return result;
    }

    /**
     * The gain of one more level: production of level + 1 minus production of level.
     *
     * @param {number} gid Building id.
     * @param {number} level Current level.
     * @param {object} [options] Options as in production().
     * @returns {{resource: number|null, amount: number, energy: number, deuterium: number}} Gain per hour.
     */
    function gain(gid, level, options) {
        const before = production(gid, level, options);
        const after = production(gid, level + 1, options);
        return {
            resource: after.resource,
            amount: after.amount - before.amount,
            energy: after.energy - before.energy,
            deuterium: after.deuterium - before.deuterium,
        };
    }

    /**
     * Recover the production factor of a planet from a value the server itself shows.
     *
     * @param {number} shown Production per hour shown by the page.
     * @param {number} gid Building id.
     * @param {number} level Level of that building.
     * @param {object} [options] Options: speed, temp, energyTech.
     * @returns {number} Factor (1 when it cannot be recovered).
     */
    function calibrate(shown, gid, level, options) {
        if (!(shown > 0) || !(level > 0)) return 1;
        const reference = production(gid, level, Object.assign({}, options, { factor: 1, officer: false, engineer: false }));
        // Only the produced amount counts: for the fusion plant the deuterium is a
        // consumption, not a production.
        const baseValue = reference.amount;
        if (!(baseValue > 0)) return 1;
        const factor = shown / baseValue;
        return isFinite(factor) && factor > 0 ? factor : 1;
    }

    /**
     * Energy technology implied by the production a fusion plant shows.
     *
     * `production = floor(30 · L · (1.05 + E · 0.01)^L · f)`, so the server's own number
     * reveals E even when the research page was never opened.
     *
     * @param {number} shown Energy produced per hour.
     * @param {number} level Fusion plant level.
     * @param {object} [options] Options: factor, engineer.
     * @returns {number} Energy technology level (0 when it cannot be derived).
     */
    function implyEnergyTech(shown, level, options) {
        if (!(shown > 0) || !(level > 0)) return 0;
        const opt = options || {};
        const factor = (opt.factor === undefined ? 1 : opt.factor) * (opt.engineer ? OFFICERS.engineer : 1);

        // The server floors the number, so the exact technology is found by trying the
        // plausible ones and keeping the first that reproduces what is on the screen.
        for (let tech = 0; tech <= 50; tech++) {
            const value = Math.floor(30 * level * Math.pow(1.05 + tech * 0.01, level) * factor);
            if (value === shown) return tech;
        }

        const perLevel = shown / (30 * level * factor);
        if (!(perLevel > 0)) return 0;
        const base = Math.pow(perLevel, 1 / level);
        const tech = (base - 1.05) / 0.01;
        return isFinite(tech) && tech > 0 ? Math.round(tech) : 0;
    }

    const api = {
        GID_B_METAL_MINE: GID_B_METAL_MINE,
        GID_B_CRYS_MINE: GID_B_CRYS_MINE,
        GID_B_DEUT_SYNTH: GID_B_DEUT_SYNTH,
        GID_B_SOLAR: GID_B_SOLAR,
        GID_B_FUSION: GID_B_FUSION,
        GID_R_ENERGY: GID_R_ENERGY,
        MINES: MINES,
        POWER: POWER,
        OFFICERS: OFFICERS,
        deutFactor: deutFactor,
        production: production,
        gain: gain,
        calibrate: calibrate,
        implyEnergyTech: implyEnergyTech,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
