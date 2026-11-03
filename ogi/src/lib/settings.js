/**
 * OGame Infinity 0.84 — settings.
 *
 * The original OGame Infinity keeps a large option tree in its own storage. The
 * 0.84 port keeps the subset that makes sense for an old server, in the same
 * per-universe database as the collected reports, so the settings travel with the
 * universe they belong to.
 *
 * Everything here is pure: the UI (panel tab and the Options page) reads and
 * writes through this module, which validates the values and fills in the
 * defaults.
 */
(function (root) {
    'use strict';

    /**
     * Default settings. The trade rate is the classic 3 metal : 2 crystal : 1
     * deuterium of the 0.84 era (OGame Infinity defaults to the modern 2.5:1.5:1);
     * the debris factors match the engine defaults (game/core/install_tabs.php).
     */
    const DEFAULTS = {
        tradeRate: [3, 2, 1],       // metal : crystal : deuterium
        standardUnitBase: 2,        // 0 metal, 1 crystal, 2 deuterium
        autoScan: true,             // read the message folders on the first visit
        reportLimit: 40,            // how many report popups a scan may fetch
        tooltips: true,             // player statistics on hover
        highlightGalaxy: true,      // mark known players/planets in the galaxy
        highlightFavorites: true,   // mark favourites
        notifyHostile: true,        // warn about hostile fleets on the overview
        debrisFactor: 30,           // % of the lost ships that becomes debris
        debrisFactorDef: 0,         // % of the lost defence that becomes debris
        deuteriumInDebris: false,   // does deuterium end up in the debris field
        historyDays: 60,            // how long the statistics history is kept
        needGid: 1,                 // needs calculator: last chosen object
        needFrom: 0,                // needs calculator: current level
        needTo: 1,                  // needs calculator: target level
        welcomeSeen: false,         // has the player closed the first-run panel?
    };

    const NUMERIC_RANGES = {
        standardUnitBase: [0, 2],
        reportLimit: [0, 200],
        debrisFactor: [0, 100],
        debrisFactorDef: [0, 100],
        historyDays: [7, 365],
        needGid: [1, 100000],
        needFrom: [0, 100],
        needTo: [1, 100],
    };

    /**
     * Clamp a number into a range.
     *
     * @param {*} value Raw value.
     * @param {number} min Minimum.
     * @param {number} max Maximum.
     * @param {number} fallback Value used when the input is not a number.
     * @returns {number} Valid number.
     */
    function clamp(value, min, max, fallback) {
        const number = typeof value === 'number' ? value : parseFloat(value);
        if (!isFinite(number)) return fallback;
        return Math.min(max, Math.max(min, number));
    }

    /**
     * Validate a settings patch and merge it over the given settings.
     *
     * @param {object} current Current settings.
     * @param {object} patch Changes.
     * @returns {object} New settings object.
     */
    function merge(current, patch) {
        const base = Object.assign({}, DEFAULTS, current || {});
        const out = Object.assign({}, base);
        const changes = patch || {};

        for (const key of Object.keys(changes)) {
            if (!(key in DEFAULTS)) continue;      // ignore unknown keys, never store junk
            const value = changes[key];
            if (key === 'tradeRate') {
                const rate = Array.isArray(value) ? value : String(value).split(/[^0-9.]+/);
                // A trade rate below 1 (or a broken value) makes no sense: the
                // resource of the base is always worth at least one unit.
                const numbers = [0, 1, 2].map(function (index) {
                    return clamp(rate[index], 1, 100, DEFAULTS.tradeRate[index]);
                });
                out.tradeRate = numbers;
                continue;
            }
            if (typeof DEFAULTS[key] === 'boolean') {
                out[key] = (value === true || value === 'true' || value === 'on' || value === 1 || value === '1');
                continue;
            }
            if (key in NUMERIC_RANGES) {
                const range = NUMERIC_RANGES[key];
                out[key] = Math.round(clamp(value, range[0], range[1], DEFAULTS[key]));
                continue;
            }
            out[key] = value;
        }

        // The base of the standard unit must be an index of the trade rate.
        out.standardUnitBase = Math.round(clamp(out.standardUnitBase, 0, 2, DEFAULTS.standardUnitBase));
        return out;
    }

    /**
     * Read the settings of a store (defaults are never written back implicitly).
     *
     * @param {object} store Store.
     * @returns {object} Settings.
     */
    function read(store) {
        const data = store && store.data ? store.data() : null;
        return merge(DEFAULTS, data && data.settings ? data.settings : {});
    }

    /**
     * Write a settings patch into a store.
     *
     * @param {object} store Store.
     * @param {object} patch Changes.
     * @returns {object} The stored settings.
     */
    function write(store, patch) {
        if (!store || !store.data) return merge(DEFAULTS, patch);
        const data = store.data();
        data.settings = merge(data.settings || DEFAULTS, patch);
        store.save();
        return data.settings;
    }

    /**
     * Reset the settings to the defaults.
     *
     * @param {object} store Store.
     * @returns {object} The stored settings.
     */
    function reset(store) {
        return write(store, DEFAULTS);
    }

    /**
     * Serialize the settings (used by the export button).
     *
     * @param {object} settings Settings.
     * @returns {string} JSON.
     */
    function exportJson(settings) {
        return JSON.stringify({ settings: settings }, null, 1);
    }

    /**
     * Parse an exported settings document.
     *
     * @param {string} text JSON.
     * @returns {object|null} Settings or null when the text is not usable.
     */
    function importJson(text) {
        try {
            const parsed = JSON.parse(String(text));
            if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) return null;
            let source = parsed;
            if (Object.prototype.hasOwnProperty.call(parsed, 'settings')) {
                if (!parsed.settings || typeof parsed.settings !== 'object') return null;
                source = parsed.settings;
            } else {
                // A bare object is accepted only when it really looks like settings,
                // otherwise any pasted JSON would silently reset the configuration.
                const looksLikeSettings = Object.keys(source).some(function (key) {
                    return Object.prototype.hasOwnProperty.call(DEFAULTS, key);
                });
                if (!looksLikeSettings) return null;
            }
            return merge(DEFAULTS, source);
        } catch (error) {
            return null;
        }
    }

    const api = {
        DEFAULTS: DEFAULTS,
        NUMERIC_RANGES: NUMERIC_RANGES,
        merge: merge,
        read: read,
        write: write,
        reset: reset,
        exportJson: exportJson,
        importJson: importJson,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
