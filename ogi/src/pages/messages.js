/**
 * OGame Infinity 0.84 — messages page.
 *
 * Injects the analyzer panel below the message table (messages.php keeps its rows
 * inside `#content`), reads the six message folders through same-origin requests
 * and collects the report popups that the list only links to.
 *
 * The panel is plain HTML in the 0.84 markup, no framework, so a page from 2003
 * keeps working.
 */
(function (root) {
    'use strict';

    const OGI = root.OGI || {};

    /**
     * Fetch a page of the game (same origin, same session).
     *
     * @param {string} url URL.
     * @returns {Promise<string>} Page source.
     */
    function fetchText(url) {
        return fetch(url, { credentials: 'same-origin' }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status + ' for ' + url);
            return response.text();
        });
    }

    /**
     * Attach the analyzer panel to the messages page.
     *
     * @param {object} ctx Extension context.
     */
    OGI.mountMessages = function (ctx) {
        const i18n = ctx.i18n;
        const store = ctx.store;
        let tab = 'spy';
        let status = '';
        let scanning = false;

        const host = document.createElement('div');
        host.id = 'ogi-host';
        const content = document.getElementById('content') || document.body;
        content.appendChild(host);

        function render() {
            const panel = OGI.renderAnalyzer(store.data(), {
                i18n: i18n,
                tab: tab,
                lang: ctx.lang,
                status: status,
            });
            host.innerHTML = panel.html;
            wire();
        }

        function wire() {
            const tabs = host.querySelectorAll('[data-ogi-tab]');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].addEventListener('click', function (event) {
                    event.preventDefault();
                    tab = this.getAttribute('data-ogi-tab');
                    render();
                });
            }
            const actions = host.querySelectorAll('[data-ogi-action]');
            for (let i = 0; i < actions.length; i++) {
                actions[i].addEventListener('click', function (event) {
                    event.preventDefault();
                    const action = this.getAttribute('data-ogi-action');
                    if (action === 'scan') scan();
                    else if (action === 'clear') {
                        store.reset();
                        status = i18n('MSG_CLEARED');
                        render();
                    }
                });
            }
        }

        /**
         * Read the message folders, then the reports they link to.
         *
         * @returns {Promise<void>} Completion.
         */
        async function scan() {
            if (scanning) return;
            scanning = true;
            status = i18n('MSG_SCANNING');
            render();

            if (!ctx.session) {
                status = i18n('MSG_NO_SESSION');
                scanning = false;
                render();
                return;
            }

            const collector = OGI.createCollector({ loca: ctx.loca, lang: ctx.lang, store: store });
            const pending = [];
            let added = 0;
            let parsed = 0;

            for (const pm of OGI.FOLDERS) {
                const url = 'index.php?page=messages&dsp=1&pm=' + pm + '&session=' + encodeURIComponent(ctx.session);
                try {
                    const pageHtml = await fetchText(url);
                    const result = collector.ingestMessagesPage(pageHtml, { pm: pm });
                    added += result.added;
                    parsed += result.parsed;
                    for (const report of result.reports) pending.push(report);
                } catch (error) {
                    console.warn('[OGI] folder ' + pm + ' failed:', error);
                }
                await new Promise(function (resolve) { setTimeout(resolve, 150); });
            }

            // Battle reports and partially hidden spy reports are only available
            // in their own popup page; fetch at most 40 of them per scan to keep
            // the server load low.
            let fetched = 0;
            for (const report of pending) {
                if (fetched >= 40) break;
                const url = 'index.php?page=bericht&session=' + encodeURIComponent(ctx.session) +
                    '&bericht=' + report.reportId;
                try {
                    const pageHtml = await fetchText(url);
                    collector.ingestReportPage(pageHtml, {
                        msgId: report.msgId,
                        kind: report.kind,
                        date: report.date,
                    });
                    fetched++;
                } catch (error) {
                    console.warn('[OGI] report ' + report.reportId + ' failed:', error);
                }
                await new Promise(function (resolve) { setTimeout(resolve, 250); });
            }

            store.save();
            status = i18n('MSG_DONE', { N: added, T: store.data().spies.length + store.data().combats.length });
            scanning = false;
            render();
        }

        render();

        // First visit: collect quietly in the background when the database is
        // still empty, so the panel has something to show.
        const data = store.data();
        const hasData = data.spies.length || data.expeditions.length || data.combats.length;
        if (!hasData && ctx.session) scan();
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
