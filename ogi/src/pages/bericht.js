/**
 * OGame Infinity 0.84 — report popup page.
 *
 * `page=bericht` is the window the game opens from the message list for spy and
 * battle reports. The extension reads it, stores the report and decorates the
 * popup with the values the original OGame Infinity shows for a spy report: the
 * maximum loot (the 50% cap of the 0.84 plunder algorithm), the number of cargo
 * ships needed and the value of the scanned fleet and defence.
 */
(function (root) {
    'use strict';

    const OGI = root.OGI || {};

    /**
     * Report id from the current URL.
     *
     * @returns {number|null} Message id.
     */
    function reportId() {
        const match = /[?&]bericht=(\d+)/.exec(window.location.search);
        return match ? parseInt(match[1], 10) : null;
    }

    /**
     * Kind of the report, from its title (SPY_REPORT / BATTLE_REPORT).
     *
     * @param {object} ctx Extension context.
     * @returns {string} "spy" or "battle".
     */
    function reportKind(ctx) {
        const title = document.title || '';
        const spy = ctx.loca.get(ctx.lang, 'SPY_REPORT');
        if (spy && title.indexOf(spy) !== -1) return 'spy';
        if (/spy/i.test(title)) return 'spy';
        return 'battle';
    }

    /**
     * Show a small info box at the top of the report page.
     *
     * @param {string} html Inner HTML.
     */
    function decorate(html) {
        const table = document.querySelector('table');
        const host = document.createElement('div');
        host.id = 'ogi-report-info';
        host.innerHTML = html;
        // Above the report: the popup window is small (550x280) and a report is
        // longer than it, so a box at the bottom would not be seen at all.
        if (table && table.parentNode) table.parentNode.insertBefore(host, table);
        else document.body.appendChild(host);
    }

    /**
     * Handle the report popup.
     *
     * @param {object} ctx Extension context.
     */
    OGI.mountBericht = function (ctx) {
        const id = reportId();
        const kind = reportKind(ctx);
        const collector = OGI.createCollector({ loca: ctx.loca, lang: ctx.lang, store: ctx.store });
        const now = Math.floor(Date.now() / 1000);
        const pageHtml = document.documentElement ? document.documentElement.outerHTML : ctx.source;

        if (kind === 'spy') {
            const spy = OGI.parseSpyReport(pageHtml, { loca: ctx.loca, lang: ctx.lang });
            if (!spy) return;
            spy.date = now;
            spy.msgId = id;
            collector.addSpy(spy);

            const analysis = OGI.spyAnalysis(spy, { unitsData: OGI.UNITS });
            const t = ctx.i18n;
            decorate(OGI.panel(t('TITLE'), OGI.keyValue([
                [t('COL_TOTAL'), OGI.num(analysis.resources.total)],
                [t('COL_LOOT'), OGI.num(analysis.maxLoot.total)],
                [t('COL_NEEDED'),
                    'SC ' + OGI.num(analysis.needed.smallCargo) + ' / LC ' + OGI.num(analysis.needed.largeCargo)],
                [t('COL_FLEETVAL'), OGI.num(analysis.fleetValue.total)],
                [t('COL_DEFVAL'), OGI.num(analysis.defenceValue.total)],
            ]), { id: 'ogi-report-panel' }));
            return;
        }

        collector.ingestReportPage(pageHtml, { msgId: id, kind: 'battle', date: now });
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
