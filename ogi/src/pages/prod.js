/**
 * OGame Infinity 0.84 — the production page ("Сырьё", `page=resources`).
 *
 * This is where the mines and their levels live and where the server prints the
 * production of every one of them, so this is where the profitability screen belongs
 * (the original extension shows it in its own window; here it sits above the table of
 * the page — and it feeds the same screen on the statistics page, which reuses the
 * calibration stored by this handler).
 */
(function (root) {
    'use strict';

    const OGI = root.OGI || {};

    /**
     * Handle the production page.
     *
     * @param {object} ctx Extension context.
     */
    OGI.mountProduction = function (ctx) {
        const t = ctx.i18n;
        const page = OGI.parseProductionPage(ctx.source, {
            loca: ctx.loca,
            lang: ctx.lang,
            speed: ctx.speed || 1,
        });

        // The levels, the factors and the energy technology of this planet are the
        // ground truth for every later calculation.
        const levels = {};
        const names = {};
        for (const row of page.rows) {
            levels[row.gid] = row.level;
            names[row.gid] = row.name;
        }
        ctx.store.setProduction({
            levels: levels,
            factors: page.factors,
            energyTech: page.energyTech,
            officers: page.officers,
            planet: page.planet,
        });

        const content = document.getElementById('content') || document.body;
        const host = OGI.mountHost(content, 'ogi-payback-host');

        function render(status, filter) {
            host.innerHTML = OGI.panel(t('PAY_TITLE'),
                (status ? '<div class="ogi-status">' + OGI.esc(status) + '</div>' : '') +
                OGI.paybackSection(ctx.store.data(), {
                    i18n: t,
                    unitsData: OGI.UNITS,
                    levels: levels,
                    names: names,
                    factors: page.factors,
                    energyTech: page.energyTech,
                    engineer: page.officers.engineer,
                    filter: filter,
                    unknown: page.unknown,
                    page: page.planet ? t('PAY_PLANET', { T: page.planet }) : null,
                }),
                { id: 'ogi-payback', width: 530 });
            wire(filter);
        }

        function wire(filter) {
            const field = host.querySelector('[data-ogi-payback="filter"]');
            if (!field) return;
            field.addEventListener('change', function () {
                const value = parseFloat(this.value);
                render(isFinite(value) && value > 0 ? t('PAY_FILTER_DONE', { N: value }) : '', isFinite(value) && value > 0 ? value : undefined);
            });
        }

        render();
        console.info('[OGI] production page: ' + page.rows.length + ' objects' +
            (page.rows.length ? ' [' + page.rows.map(function (row) { return row.gid + '@' + row.level; }).join(', ') + ']' : '') +
            ' · planet=' + page.planet + ' · energy tech=' + page.energyTech +
            ' · officers=' + (page.officers.geologist ? 'geologist' : '-') + '/' + (page.officers.engineer ? 'engineer' : '-') +
            (page.tolerant ? ' · read with the tolerant pass' : '') +
            (page.unknown && page.unknown.length ? ' · unknown names: ' + page.unknown.join(' | ') : '') +
            (page.rows.length ? '' : ' · the page does not look like the Supplies table'));
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
