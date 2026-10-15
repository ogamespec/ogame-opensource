/**
 * OGame Infinity 0.84 — empire page.
 *
 * The imperium page is the only page that lists all own planets with their stock,
 * production, fields and object levels; the extension records that snapshot and
 * draws it as a panel (the "Empire" tab of the analyzer shows the same data later,
 * also from the message page).
 */
(function (root) {
    'use strict';

    const OGI = root.OGI || {};

    /**
     * Handle the empire page.
     *
     * @param {object} ctx Extension context.
     */
    OGI.mountImperium = function (ctx) {
        const collector = OGI.createCollector({ loca: ctx.loca, lang: ctx.lang, store: ctx.store });
        try {
            collector.ingestImperiumPage(ctx.source);
        } catch (error) {
            console.warn('[OGI] empire: reading the page failed:', error);
        }

        const host = document.createElement('div');
        host.id = 'ogi-empire-host';
        try {
            host.innerHTML = OGI.panel(ctx.i18n('TAB_EMPIRE'),
                OGI.imperiumSection(ctx.store.data(), { i18n: ctx.i18n, loca: ctx.loca, lang: ctx.lang }),
                { id: 'ogi-empire' });
        } catch (error) {
            console.warn('[OGI] empire: building the panel failed:', error);
        }

        const content = document.getElementById('content') || document.body;
        if (content.firstChild) content.insertBefore(host, content.firstChild);
        else content.appendChild(host);
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
