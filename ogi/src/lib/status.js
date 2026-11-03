/**
 * OGame Infinity 0.84 — status payload.
 *
 * The extension has no server side and no API to ask "are you alive?", so the
 * content script keeps the decision it made about the page (`OGI.lastBoot`) and
 * answers the toolbar popup with it. Both the console line and the popup are built
 * from the same pure function, so what the user reads is exactly what the script
 * decided.
 */
(function (root) {
    'use strict';

    /**
     * Counts of the collected data.
     *
     * @param {object} data Store data.
     * @returns {object} Counts.
     */
    function counts(data) {
        const db = data || {};
        return {
            spies: (db.spies || []).length,
            combats: (db.combats || []).length,
            expeditions: (db.expeditions || []).length,
            harvests: (db.harvests || []).length,
            trades: (db.trades || []).length,
            planets: Object.keys(db.planets || {}).length,
            players: Object.keys(db.players || {}).length,
        };
    }

    /**
     * Build the status of the current page.
     *
     * @param {object} info Decision of the content script.
     * @returns {object} Status: the same fields plus the counters.
     */
    function buildStatus(info) {
        const source = info || {};
        return {
            version: source.version || 'dev',
            href: source.href || '',
            page: source.page || null,
            game: !!source.game,
            universe: source.universe || null,
            lang: source.lang || null,
            storage: !!source.storage,
            handler: source.handler || null,
            session: !!source.session,
            counts: source.counts || counts(null),
        };
    }

    /**
     * One line description of a status, used for the console.
     *
     * @param {object} status Status.
     * @returns {string} Text.
     */
    function describe(status) {
        return 'page=' + (status.page || '-') +
            ' · game=' + (status.game ? 'yes' : 'no') +
            ' · universe=' + (status.universe || '-') +
            ' · lang=' + (status.lang || '-') +
            ' · storage=' + (status.storage ? 'ok' : 'memory') +
            ' · handler=' + (status.handler || 'none');
    }

    const api = {
        counts: counts,
        buildStatus: buildStatus,
        describe: describe,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
