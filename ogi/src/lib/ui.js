/**
 * OGame Infinity 0.84 — UI helpers.
 *
 * The analyzer panel is rendered with the very same markup the 0.84 pages use
 * (a `<table class="header">` box with `<td class="c">` headers, `th` rows and
 * plain links), so it looks like a native game page instead of a modern overlay.
 */
(function (root) {
    'use strict';

    const numbers = typeof require === 'function' ? require('./numbers.js') : root.OGI;

    /**
     * Escape a value for HTML output (galaxy data is player controlled).
     *
     * @param {*} value Value.
     * @returns {string} Escaped text.
     */
    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Render an amount the way the game does (dots as thousand separators).
     *
     * @param {number} value Amount.
     * @returns {string} Formatted amount.
     */
    function num(value) {
        if (value === null || value === undefined || isNaN(value)) return '-';
        return numbers.formatNumber(value);
    }

    /**
     * Render a table.
     *
     * @param {string[]} headers Header cells.
     * @param {Array<Array<string>>} rows Body rows (already escaped HTML).
     * @param {object} [options] Options: className.
     * @returns {string} HTML.
     */
    function table(headers, rows, options) {
        const opt = options || {};
        const out = [];
        out.push('<table class="ogi-table' + (opt.className ? ' ' + esc(opt.className) : '') + '">');
        if (headers && headers.length) {
            out.push('<tr>');
            for (const header of headers) out.push('<th>' + esc(header) + '</th>');
            out.push('</tr>');
        }
        for (const row of rows || []) {
            out.push('<tr>');
            for (const cell of row) out.push('<td>' + cell + '</td>');
            out.push('</tr>');
        }
        out.push('</table>');
        return out.join('');
    }

    /**
     * Render the frame of the analyzer panel.
     *
     * @param {string} title Panel title.
     * @param {string} bodyHtml Inner HTML.
     * @param {object} [options] Options: id, actions (HTML).
     * @returns {string} HTML.
     */
    function panel(title, bodyHtml, options) {
        const opt = options || {};
        const out = [];
        out.push('<div class="ogi-panel" id="' + esc(opt.id || 'ogi-panel') + '">');
        out.push('<table class="header" width="519"><tr><td class="c" colspan="2">');
        out.push('<b>OGame Infinity 0.84 — ' + esc(title) + '</b>');
        out.push('</td></tr>');
        if (opt.actions) {
            out.push('<tr><td colspan="2" class="ogi-actions">' + opt.actions + '</td></tr>');
        }
        out.push('<tr><td colspan="2" class="ogi-body">' + bodyHtml + '</td></tr>');
        out.push('</table>');
        out.push('</div>');
        return out.join('');
    }

    /**
     * Render a tab bar.
     *
     * @param {Array<{id: string, title: string}>} tabs Tabs.
     * @param {string} active Active tab id.
     * @returns {string} HTML.
     */
    function tabs(tabs, active) {
        const out = ['<div class="ogi-tabs">'];
        for (const tab of tabs) {
            const isActive = tab.id === active;
            out.push('<a href="#" class="' + (isActive ? 'ogi-tab-active' : 'ogi-tab') +
                '" data-ogi-tab="' + esc(tab.id) + '">' + esc(tab.title) + '</a>');
        }
        out.push('</div>');
        return out.join('');
    }

    /**
     * Render a two column "label: value" list in the style of the game's info
     * tables.
     *
     * @param {Array<Array<string>>} rows Pairs of already rendered HTML cells.
     * @returns {string} HTML.
     */
    function keyValue(rows) {
        const out = ['<table class="ogi-kv">'];
        for (const row of rows || []) {
            out.push('<tr><td class="ogi-key">' + row[0] + '</td><td>' + row[1] + '</td></tr>');
        }
        out.push('</table>');
        return out.join('');
    }

    /**
     * Render a small colored tag (win/loss, expedition type, ...).
     *
     * @param {string} text Text.
     * @param {string} kind Style kind (good, bad, neutral, info).
     * @returns {string} HTML.
     */
    function tag(text, kind) {
        return '<span class="ogi-tag ogi-' + esc(kind || 'neutral') + '">' + esc(text) + '</span>';
    }

    const api = {
        esc: esc,
        num: num,
        table: table,
        panel: panel,
        tabs: tabs,
        keyValue: keyValue,
        tag: tag,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
