/**
 * OGame Infinity 0.84 — statistics page.
 *
 * The game's Statistics page (`page=statistics`) shows one block of 100 ranks at a
 * time and three different point categories; the extension reads it, records the
 * history of every player it sees and injects its own panel with the account
 * breakdown, the points history and the rank table.
 *
 * The other blocks and categories are fetched on demand with a POST, the same way
 * the page's own form submits itself (`who`, `type`, `start`, `sort_per_member`).
 */
(function (root) {
    'use strict';

    const OGI = root.OGI || {};

    const VIEWS = ['ressources', 'fleet', 'research'];

    /**
     * Fetch a page of the game.
     *
     * @param {string} url URL.
     * @param {object} [options] Options: method, body.
     * @returns {Promise<string>} Page source.
     */
    function fetchText(url, options) {
        const opt = options || {};
        return fetch(url, {
            method: opt.method || 'GET',
            credentials: 'same-origin',
            headers: opt.body ? { 'Content-Type': 'application/x-www-form-urlencoded' } : undefined,
            body: opt.body,
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status + ' for ' + url);
            return response.text();
        });
    }

    /**
     * Handle the statistics page.
     *
     * @param {object} ctx Extension context.
     */
    OGI.mountStatistics = function (ctx) {
        const i18n = ctx.i18n;
        const store = ctx.store;
        const collector = OGI.createCollector({ loca: ctx.loca, lang: ctx.lang, store: store });
        let status = '';
        let rows = [];
        let busy = false;

        const host = document.createElement('div');
        host.id = 'ogi-stats-host';
        const content = document.getElementById('content') || document.body;
        if (content.firstChild) content.insertBefore(host, content.firstChild);
        else content.appendChild(host);

        // The page that is already open needs no request at all.
        const parsed = collector.ingestStatisticsPage(ctx.source, {});
        rows = OGI.statisticsRows(OGI.parseStatisticsPage(ctx.source, {}).rows, store.data());

        function render() {
            const actions = [
                '<a href="#" class="ogi-action" data-ogi-action="own">' + OGI.esc(i18n('STAT_UPDATE_OWN')) + '</a>',
                '<a href="#" class="ogi-action" data-ogi-action="top">' + OGI.esc(i18n('STAT_UPDATE_TOP')) + '</a>',
            ].join(' | ');
            host.innerHTML = OGI.panel(i18n('TAB_STATS'),
                (status ? '<div class="ogi-status">' + OGI.esc(status) + '</div>' : '') +
                OGI.statsSection(store.data(), { i18n: i18n, rows: rows, historyDays: OGI.read(store).historyDays }),
                { id: 'ogi-stats', actions: actions });
            wire();
        }

        function wire() {
            const actions = host.querySelectorAll('[data-ogi-action]');
            for (let i = 0; i < actions.length; i++) {
                actions[i].addEventListener('click', function (event) {
                    event.preventDefault();
                    const action = this.getAttribute('data-ogi-action');
                    load(action === 'top' ? 1 : -1);
                });
            }
        }

        /**
         * Read one block of ranks in all three categories.
         *
         * @param {number} start Block start (-1 = the own position).
         * @returns {Promise<void>} Completion.
         */
        async function load(start) {
            if (busy || !ctx.session) return;
            busy = true;
            status = i18n('STAT_LOADING');
            render();

            let count = 0;
            let selfRows = null;
            for (const type of VIEWS) {
                const body = 'who=player&type=' + type + '&start=' + start + '&sort_per_member=0';
                const url = 'index.php?page=statistics&session=' + encodeURIComponent(ctx.session);
                try {
                    const pageHtml = await fetchText(url, { method: 'POST', body: body });
                    const result = collector.ingestStatisticsPage(pageHtml, { who: 'player', type: type, start: start });
                    count += result.rows;
                    if (type === 'ressources') {
                        selfRows = OGI.statisticsRows(OGI.parseStatisticsPage(pageHtml, { type: type }).rows, store.data());
                    }
                } catch (error) {
                    console.warn('[OGI] statistics view ' + type + ' failed:', error);
                }
                await new Promise(function (resolve) { setTimeout(resolve, 200); });
            }

            if (selfRows) rows = selfRows;
            status = i18n('STAT_UPDATED', { N: count });
            busy = false;
            render();
        }

        render();
        if (!parsed.rows && ctx.session) load(-1);
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
