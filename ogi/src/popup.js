/**
 * OGame Infinity 0.84 — toolbar popup.
 *
 * Answers the question the game pages cannot: "is the extension loaded at all,
 * which version, and what did it decide about the page in this tab?".
 *
 * The popup runs in the extension's own context, so it cannot read the game page's
 * database; it asks the content script of the active tab for the status the script
 * already computed (see lib/status.js).
 */
(function () {
    'use strict';

    const statusHost = document.getElementById('ogi-popup-status');
    const noteHost = document.getElementById('ogi-popup-note');
    const refresh = document.getElementById('ogi-popup-refresh');
    const welcome = document.getElementById('ogi-popup-welcome');

    /**
     * The extension API of the current browser.
     *
     * @returns {object} chrome or browser.
     */
    function api() {
        return typeof browser !== 'undefined' && browser.runtime ? browser : chrome;
    }

    /**
     * Promise wrapper around the callback/promise APIs of Chrome and Firefox.
     *
     * @param {Function} call Function receiving a callback.
     * @returns {Promise<*>} Result.
     */
    function promisify(call) {
        const impl = api();
        if (impl === (typeof browser !== 'undefined' ? browser : null)) {
            return call();
        }
        return new Promise(function (resolve, reject) {
            call(function (result) {
                const error = chrome.runtime.lastError;
                if (error) reject(new Error(error.message));
                else resolve(result);
            });
        });
    }

    /**
     * Render one "label: value" row.
     *
     * @param {string} label Label.
     * @param {string} value Value.
     * @returns {HTMLElement} Row.
     */
    function row(label, value) {
        const element = document.createElement('div');
        element.className = 'ogi-popup-row';
        const left = document.createElement('span');
        left.textContent = label;
        const right = document.createElement('span');
        right.textContent = value;
        element.appendChild(left);
        element.appendChild(right);
        return element;
    }

    /**
     * Render the status of the active tab.
     *
     * @param {object} status Status payload.
     * @param {string} version Extension version.
     */
    function render(status, version) {
        statusHost.textContent = '';
        statusHost.appendChild(row('Version', status.version || version));
        statusHost.appendChild(row('Game page', status.game ? 'yes' : 'no'));
        statusHost.appendChild(row('Page', status.page || '—'));
        statusHost.appendChild(row('Universe', status.universe || '—'));
        statusHost.appendChild(row('Language', status.lang || '—'));
        statusHost.appendChild(row('Storage', status.storage ? 'ok' : 'in memory'));
        statusHost.appendChild(row('Panel', status.handler || 'none'));
        statusHost.appendChild(row('Session', status.session ? 'found' : 'missing'));

        const counts = status.counts || {};
        statusHost.appendChild(row('Spy reports', String(counts.spies === undefined ? 0 : counts.spies)));
        statusHost.appendChild(row('Combats', String(counts.combats === undefined ? 0 : counts.combats)));
        statusHost.appendChild(row('Expeditions', String(counts.expeditions === undefined ? 0 : counts.expeditions)));

        if (!status.game) {
            noteHost.textContent = 'This tab is not an OGame 0.84 page. ' +
                'The panel appears on the game pages Messages, report popups and Galaxy.';
        } else if (!status.handler) {
            noteHost.textContent = 'This is a game page, but the extension has no panel for it. ' +
                'Open Messages to see the analyzer.';
        } else {
            noteHost.textContent = 'Panel: ' + status.handler + '. It is drawn at the top of the content area.';
        }
    }

    /**
     * Ask the content script of the active tab for its status.
     *
     * @returns {Promise<void>} Completion.
     */
    function load() {
        const version = api().runtime.getManifest().version;
        statusHost.textContent = '…';
        noteHost.textContent = '';

        return promisify(function (done) {
            return api().tabs.query({ active: true, currentWindow: true }, done);
        }).then(function (tabs) {
            const tab = tabs && tabs[0];
            if (!tab || tab.id === undefined) throw new Error('no active tab');
            return promisify(function (done) {
                return api().tabs.sendMessage(tab.id, { type: 'ogi-status' }, done);
            });
        }).then(function (status) {
            if (!status || typeof status !== 'object') throw new Error('no answer');
            render(status, version);
        }).catch(function () {
            statusHost.textContent = '';
            statusHost.appendChild(row('Version', version));
            statusHost.appendChild(row('Content script', 'not running'));
            noteHost.textContent = 'The extension is not injected into this tab. ' +
                'Reload the extension after changing its files, then refresh the page. ' +
                'Game pages over file:// need "Allow access to file URLs".';
        });
    }

    refresh.addEventListener('click', function () { load(); });

    // Ask the content script of the active tab to show the first-run panel again
    // (the panel and its settings live with the page, not with the popup).
    welcome.addEventListener('click', function () {
        promisify(function (done) {
            return api().tabs.query({ active: true, currentWindow: true }, done);
        }).then(function (tabs) {
            const tab = tabs && tabs[0];
            if (!tab || tab.id === undefined) throw new Error('no active tab');
            return promisify(function (done) {
                return api().tabs.sendMessage(tab.id, { type: 'ogi-welcome' }, done);
            });
        }).then(function () {
            noteHost.textContent = 'The panel is back on the game page.';
        }).catch(function () {
            noteHost.textContent = 'Open a game page first: the panel is drawn inside the game.';
        });
    });

    load();
})();
