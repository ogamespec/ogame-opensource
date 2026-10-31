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
        // Planet database state (the filter is not worth persisting).
        let planetFilter = '';
        let favoritesOnly = false;
        // Farm list filters.
        const farmFilters = { maxAgeHours: 168, inactiveOnly: false, minLoot: 0 };

        // The panel goes to the very top of the content area on purpose: the 0.84
        // content pane (#content) is absolutely positioned with its own scrollbar,
        // so anything appended to the end of the page sits below the message table
        // and is invisible until the player scrolls all the way down.
        const content = document.getElementById('content') || document.body;
        const host = OGI.mountHost(content, 'ogi-host');

        function collapsed() {
            const data = store.data();
            if (!data.meta) data.meta = {};
            return !!data.meta.analyzerCollapsed;
        }

        function render() {
            const panel = OGI.renderAnalyzer(store.data(), {
                i18n: i18n,
                tab: tab,
                lang: ctx.lang,
                status: status,
                collapsed: collapsed(),
                loca: ctx.loca,
                filter: planetFilter,
                favoritesOnly: favoritesOnly,
                session: ctx.session,
                maxAgeHours: farmFilters.maxAgeHours,
                inactiveOnly: farmFilters.inactiveOnly,
                minLoot: farmFilters.minLoot,
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
                    } else if (action === 'toggle') {
                        const data = store.data();
                        if (!data.meta) data.meta = {};
                        data.meta.analyzerCollapsed = !data.meta.analyzerCollapsed;
                        store.save();
                        render();
                    }
                });
            }
            // The settings tab carries the same form as the game's Options page.
            if (tab === 'settings') {
                OGI.wireSettingsForm(host, ctx, function (text) {
                    status = text || '';
                    render();
                });
            }
            // The needs calculator keeps its choice in the settings, so the panel
            // can be re-rendered from scratch on every change.
            if (tab === 'needs') {
                const fields = host.querySelectorAll('[data-ogi-need]');
                for (let i = 0; i < fields.length; i++) {
                    fields[i].addEventListener('change', function () {
                        const patch = {};
                        patch[this.getAttribute('data-ogi-need')] = this.value;
                        OGI.write(store, patch);
                        render();
                    });
                }
            }
            // Farm list: age, activity and loot filters.
            if (tab === 'farms') {
                const fields = host.querySelectorAll('[data-ogi-farm]');
                for (let i = 0; i < fields.length; i++) {
                    fields[i].addEventListener('change', function () {
                        const kind = this.getAttribute('data-ogi-farm');
                        if (kind === 'maxAge') farmFilters.maxAgeHours = parseInt(this.value, 10) || 168;
                        else if (kind === 'minLoot') farmFilters.minLoot = parseInt(this.value, 10) || 0;
                        else farmFilters.inactiveOnly = this.checked;
                        render();
                    });
                }
            }
            // Planet database: filter, favourites-only, favourite toggles and notes.
            if (tab === 'planets') {
                const filterField = host.querySelector('[data-ogi-planet-filter]');
                if (filterField) {
                    filterField.addEventListener('input', function () {
                        planetFilter = this.value;
                        // Re-rendering on every keystroke would steal the focus, so
                        // only the tables are rebuilt.
                        const position = this.selectionStart;
                        render();
                        const again = host.querySelector('[data-ogi-planet-filter]');
                        if (again) {
                            again.focus();
                            again.setSelectionRange(position, position);
                        }
                    });
                }
                const favOnly = host.querySelector('[data-ogi-planet-fav]');
                if (favOnly) {
                    favOnly.addEventListener('change', function () {
                        favoritesOnly = this.checked;
                        render();
                    });
                }
                const favButtons = host.querySelectorAll('[data-ogi-fav]');
                for (let i = 0; i < favButtons.length; i++) {
                    favButtons[i].addEventListener('click', function (event) {
                        event.preventDefault();
                        const value = this.getAttribute('data-ogi-fav');
                        if (value.indexOf('player:') === 0) store.toggleFavorite('players', value.slice(7));
                        else store.toggleFavorite('planets', value);
                        render();
                    });
                }
                const noteFields = host.querySelectorAll('[data-ogi-note]');
                for (let i = 0; i < noteFields.length; i++) {
                    noteFields[i].addEventListener('change', function () {
                        store.setNote(this.getAttribute('data-ogi-note'), this.value);
                    });
                }
            }
        }

        /**
         * Read the message folders, then the reports they link to.
         *
         * The page that is already open is read first, without a single request:
         * if the server (or a content security policy) blocks the extra requests,
         * the reports visible in the current folder are still collected.
         *
         * @returns {Promise<void>} Completion.
         */
        async function scan() {
            if (scanning) return;
            scanning = true;
            status = i18n('MSG_SCANNING');
            render();

            const collector = OGI.createCollector({ loca: ctx.loca, lang: ctx.lang, store: store });
            const pending = [];
            let added = 0;
            let parsed = 0;

            try {
                const result = collector.ingestMessagesPage(ctx.source, { pm: null });
                added += result.added;
                parsed += result.parsed;
                for (const report of result.reports) pending.push(report);
            } catch (error) {
                console.warn('[OGI] the open page could not be read:', error);
            }

            if (!ctx.session) {
                store.save();
                status = i18n('MSG_DONE', { N: added, T: store.data().spies.length + store.data().combats.length });
                scanning = false;
                render();
                return;
            }

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
