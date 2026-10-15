/**
 * OGame Infinity 0.84 — number helpers.
 *
 * OGame 0.84 renders every amount through `nicenum()` (game/core/utils.php):
 *
 *     number_format($number, 0, ",", ".")   // 1234567  ->  "1.234.567"
 *
 * The extension has to read those amounts back out of the page and to render its
 * own numbers in the same style, so that the analyzer panel looks like a native
 * 0.84 page.
 */
(function (root) {
    'use strict';

    /**
     * Parse a game number ("1.234.567", "25.047", "500") into an integer.
     *
     * @param {string|number} value Raw value from the page.
     * @returns {number} Parsed number, or NaN when there is no digit at all.
     */
    function parseGameNumber(value) {
        const raw = String(value === null || value === undefined ? '' : value)
            .replace(/[\s\u00a0]/g, '');
        const clean = /^\d{1,3}(?:[.,]\d{3})+$/.exec(raw);
        if (clean) return parseInt(clean[0].replace(/[.,]/g, ''), 10);
        const plain = /^\d+$/.exec(raw);
        if (plain) return parseInt(plain[0], 10);
        const digits = raw.replace(/[^\d]/g, '');
        return digits === '' ? NaN : parseInt(digits, 10);
    }

    /**
     * Format a number the way 0.84 does (dots as thousand separators).
     *
     * @param {number} value Number.
     * @returns {string} Formatted number.
     */
    function formatNumber(value) {
        if (!isFinite(value)) return '0';
        const rounded = Math.round(value);
        return String(rounded).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    /**
     * Render one resource amount as "1.234.567 Metal" like the game does.
     *
     * @param {number} value Amount.
     * @param {string} name Resource name.
     * @returns {string} Human readable amount.
     */
    function formatResource(value, name) {
        return formatNumber(value) + (name ? ' ' + name : '');
    }

    /**
     * OGame 0.84 plunder algorithm (game/core/battle.php, `Plunder`).
     *
     * At most half of every resource can be captured; the cargo space is filled
     * with metal first, then crystal, then deuterium, and what is left over is
     * redistributed to the resources that were capped by the "half" rule. The
     * extension replicates it so that a spy report can show the exact loot for a
     * given number of cargo ships.
     *
     * @param {number} cargo Total cargo capacity of the attacking fleet.
     * @param {number} metal Metal on the target.
     * @param {number} crystal Crystal on the target.
     * @param {number} deuterium Deuterium on the target.
     * @returns {{metal: number, crystal: number, deuterium: number, total: number}} Loot.
     */
    function plunder(cargo, metal, crystal, deuterium) {
        let m = Math.max(0, metal) / 2;
        let k = Math.max(0, crystal) / 2;
        let d = Math.max(0, deuterium) / 2;
        let space = Math.max(0, cargo);

        let mc = space / 3;
        if (m < mc) mc = m;
        space -= mc;
        let kc = space / 2;
        if (k < kc) kc = k;
        space -= kc;
        let dc = space;
        if (d < dc) {
            dc = d;
            space -= dc;
            m -= mc;
            const half = space / 2;
            let bonus = half;
            if (m < half) bonus = m;
            mc += bonus;
            space -= bonus;
            k -= kc;
            if (k < space) kc += k;
            else kc += space;
        }

        const out = {
            metal: Math.floor(mc),
            crystal: Math.floor(kc),
            deuterium: Math.floor(dc),
        };
        out.total = out.metal + out.crystal + out.deuterium;
        return out;
    }

    /**
     * The maximum amount that can ever be plundered from a planet: half of every
     * resource (the cap of the 0.84 `Plunder` algorithm).
     *
     * @param {number} metal Metal on the target.
     * @param {number} crystal Crystal on the target.
     * @param {number} deuterium Deuterium on the target.
     * @returns {{metal: number, crystal: number, deuterium: number, total: number}} Maximum loot.
     */
    function maxLoot(metal, crystal, deuterium) {
        const out = {
            metal: Math.floor(Math.max(0, metal) / 2),
            crystal: Math.floor(Math.max(0, crystal) / 2),
            deuterium: Math.floor(Math.max(0, deuterium) / 2),
        };
        out.total = out.metal + out.crystal + out.deuterium;
        return out;
    }

    /**
     * How many ships of the given cargo capacity are needed to carry an amount.
     *
     * @param {number} amount Amount to carry.
     * @param {number} capacity Cargo capacity of one ship.
     * @returns {number} Number of ships (0 for an empty amount).
     */
    function neededShips(amount, capacity) {
        if (!(amount > 0) || !(capacity > 0)) return 0;
        return Math.ceil(amount / capacity);
    }

    /**
     * The "standard unit" valuation used by OGame Infinity to compare mixed
     * resource amounts. Ported from the extension (`util/standardUnit.js`):
     *
     *     value = SUM over resources ( amount[i] / tradeRate[i] * tradeRate[base] )
     *
     * OGame Infinity defaults to the modern trade rates [2.5, 1.5, 1, 0]; the
     * classic rate of the 0.84 era is 3 metal : 2 crystal : 1 deuterium, which is
     * what the extension uses by default (configurable in the settings).
     *
     * @param {number[]} amount Amounts [metal, crystal, deuterium].
     * @param {number[]} [tradeRate] Trade rates, metal first.
     * @param {number} [base] Index of the resource the value is expressed in (2 = deuterium).
     * @returns {number} Value in standard units.
     */
    function standardUnit(amount, tradeRate, base) {
        const rate = tradeRate || [3, 2, 1];
        const baseIndex = base === undefined ? 2 : base;
        let sum = 0;
        for (let i = 0; i < 3; i++) {
            const r = rate[i] || 0;
            if (r <= 0) continue;
            sum += ((amount[i] || 0) / r) * rate[baseIndex];
        }
        return sum;
    }

    const api = {
        parseGameNumber: parseGameNumber,
        formatNumber: formatNumber,
        formatResource: formatResource,
        plunder: plunder,
        maxLoot: maxLoot,
        neededShips: neededShips,
        standardUnit: standardUnit,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
