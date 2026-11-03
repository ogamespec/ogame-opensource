/**
 * OGame Infinity 0.84 — mounting of the injected panels.
 *
 * The 0.84 pages put their content into `<center>` inside `#content`, so every table
 * of the game sits in the middle of the content pane. The extension has to do the
 * same: a plain `<div>` inserted as a child of `#content` sticks to the left edge
 * (the content pane is absolutely positioned and much wider than the panels), which
 * looks like the panel "slid to the left" of the game's own content.
 *
 * This module keeps that in one place: `mountHost` creates `<center><div id=…></div>
 * </center>` in front of the page content and returns the inner element to render
 * into; `unmountHost` removes it again.
 */
(function (root) {
    'use strict';

    /**
     * Create (or reuse) the centered host of a panel.
     *
     * @param {Element} parent Content element (`#content`).
     * @param {string} id Host id.
     * @param {object} [options] Options: first (insert as the first child, default true).
     * @returns {Element} Host element to render into.
     */
    function mountHost(parent, id, options) {
        const opt = options || {};
        const doc = (parent && parent.ownerDocument) || root.document;
        const existing = parent && parent.querySelector ? parent.querySelector('#' + id) : null;
        if (existing) return existing;

        const wrapper = doc.createElement('center');
        wrapper.className = 'ogi-center';
        const host = doc.createElement('div');
        host.id = id;
        wrapper.appendChild(host);

        if (!parent) return host;
        if (opt.first === false || !parent.firstChild) parent.appendChild(wrapper);
        else parent.insertBefore(wrapper, parent.firstChild);
        return host;
    }

    /**
     * Remove a panel and its centering wrapper.
     *
     * @param {Element} host Host element returned by mountHost.
     */
    function unmountHost(host) {
        if (!host || !host.parentNode) return;
        const wrapper = host.parentNode;
        const center = wrapper.parentNode;
        // Detach the host as well: a panel that was closed must not keep a reference
        // to the game's DOM through its (now removed) wrapper.
        if (wrapper.removeChild) wrapper.removeChild(host);
        if (!center) return;
        center.removeChild(wrapper);
    }

    const api = {
        mountHost: mountHost,
        unmountHost: unmountHost,
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
