/**
 * OGame Infinity 0.84 — build pages (`page=buildings`).
 *
 * The pages of the mines, the research, the shipyard and the defence already show
 * the price of the next level. The extension adds what the original OGame Infinity
 * adds there: a level (or amount) stepper with the cost of the chosen target, the
 * resources that are still missing for it, a padlock that puts the object into the
 * resource plan of the universe, and a summary of the whole plan above the list.
 *
 * Nothing here talks to the server: the costs come from the object tables that were
 * generated from `game/core/techs.php`, the stock comes from the resource bar of the
 * page the player is looking at.
 */
(function (root) {
    'use strict';

    const OGI = root.OGI || {};

    /**
     * The row element of an object on a build page.
     *
     * @param {number} gid Object id.
     * @returns {Element|null} Table row.
     */
    function rowOf(gid) {
        const links = document.querySelectorAll('a[href*="page=infos"][href*="gid="]');
        for (let i = 0; i < links.length; i++) {
            const href = links[i].getAttribute('href') || '';
            if (!new RegExp('gid=' + gid + '(\\D|$)').test(href)) continue;
            let node = links[i];
            while (node && node.tagName !== 'TR') node = node.parentNode;
            if (node) return node;
        }
        return null;
    }

    /**
     * Handle a build page.
     *
     * @param {object} ctx Extension context.
     */
    OGI.mountBuild = function (ctx) {
        const t = ctx.i18n;
        const parsed = OGI.parseBuildPage(ctx.source, {
            loca: ctx.loca,
            lang: ctx.lang,
            unitsData: OGI.UNITS,
        });
        if (!parsed.rows.length) {
            console.info('[OGI] build page: no object rows found');
            return;
        }

        const content = document.getElementById('content') || document.body;
        const host = OGI.mountHost(content, 'ogi-plan-host');
        const stock = OGI.stockOf(ctx.store.data());

        function currentPlan() {
            return ctx.store.getPlan();
        }

        /**
         * Render the summary panel. Re-rendered after every click, because the
         * totals change with each step.
         *
         * @param {string} [status] Optional status line.
         */
        function renderSummary(status) {
            host.innerHTML = OGI.panel(t('PLAN_TITLE'),
                (status ? '<div class="ogi-status">' + OGI.esc(status) + '</div>' : '') +
                OGI.planSection(ctx.store.data(), {
                    i18n: t,
                    unitsData: OGI.UNITS,
                    stock: stock,
                    plan: currentPlan(),
                }),
                {
                    id: 'ogi-plan',
                    width: 530,
                    actions: '<a href="#" class="ogi-action" data-ogi-plan-action="clear">' +
                        OGI.esc(t('PLAN_CLEAR')) + '</a> | ' +
                        '<a href="#" class="ogi-action" data-ogi-plan-action="unlock">' +
                        OGI.esc(t('PLAN_UNLOCK_ALL')) + '</a>',
                });
            wireSummary();
        }

        /**
         * Re-render the control cell of one object.
         *
         * @param {object} row Parsed row.
         */
        function renderRow(row) {
            const cell = document.querySelector('[data-ogi-cell="' + row.gid + '"]');
            if (!cell) return;
            cell.innerHTML = OGI.buildControl(row, {
                i18n: t,
                unitsData: OGI.UNITS,
                plan: currentPlan(),
                stock: stock,
            });
            wireCell(cell, row);
        }

        /**
         * Wire the buttons inside a control cell.
         *
         * @param {Element} cell Cell element.
         * @param {object} row Parsed row.
         */
        function wireCell(cell, row) {
            const steps = cell.querySelectorAll('[data-ogi-step]');
            for (let i = 0; i < steps.length; i++) {
                steps[i].addEventListener('click', function (event) {
                    event.preventDefault();
                    const parts = (this.getAttribute('data-ogi-step') || '').split(':');
                    const gid = parseInt(parts[0], 10);
                    const delta = parseInt(parts[1], 10);
                    const plan = currentPlan();
                    const stored = plan[String(gid)] || {};
                    const byLevel = OGI.byLevel(row.kind);
                    const current = byLevel ? (row.level || 0) : (row.stock || 0);
                    const from = stored.from === undefined ? current : stored.from;
                    const target = OGI.clampTarget(stored.to === undefined ? current : stored.to, delta);
                    ctx.store.setPlanEntry(gid, {
                        from: from,
                        to: Math.max(from, target),
                        kind: row.kind,
                        name: row.name,
                        locked: stored.locked,
                    });
                    renderRow(row);
                    renderSummary();
                });
            }

            const locks = cell.querySelectorAll('[data-ogi-lock]');
            for (let i = 0; i < locks.length; i++) {
                locks[i].addEventListener('click', function (event) {
                    event.preventDefault();
                    const gid = parseInt(this.getAttribute('data-ogi-lock'), 10);
                    const plan = currentPlan();
                    const stored = plan[String(gid)] || {};
                    const byLevel = OGI.byLevel(row.kind);
                    const current = byLevel ? (row.level || 0) : (row.stock || 0);
                    const from = stored.from === undefined ? current : stored.from;
                    const to = stored.to === undefined ? current : stored.to;
                    ctx.store.setPlanEntry(gid, {
                        from: from,
                        // Locking an untouched object means "one level more".
                        to: stored.locked ? to : Math.max(to, byLevel ? from + 1 : Math.max(1, to)),
                        kind: row.kind,
                        name: row.name,
                        locked: !stored.locked,
                    });
                    renderRow(row);
                    renderSummary(stored.locked ? t('PLAN_UNLOCKED') : t('PLAN_LOCKED'));
                });
            }
        }

        /**
         * Wire the actions of the summary panel.
         */
        function wireSummary() {
            const actions = host.querySelectorAll('[data-ogi-plan-action]');
            for (let i = 0; i < actions.length; i++) {
                actions[i].addEventListener('click', function (event) {
                    event.preventDefault();
                    const action = this.getAttribute('data-ogi-plan-action');
                    if (action === 'clear') {
                        ctx.store.clearPlan();
                    } else {
                        ctx.store.unlockPlan();
                    }
                    for (const row of parsed.rows) renderRow(row);
                    renderSummary(action === 'clear' ? t('PLAN_CLEARED') : t('PLAN_UNLOCKED_ALL'));
                });
            }
        }

        // Add a control cell to every object row of the page.
        for (const row of parsed.rows) {
            const tr = rowOf(row.gid);
            if (!tr) continue;
            const cell = document.createElement('td');
            cell.className = 'ogi-build-td';
            cell.setAttribute('data-ogi-cell', String(row.gid));
            cell.innerHTML = OGI.buildControl(row, {
                i18n: t,
                unitsData: OGI.UNITS,
                plan: currentPlan(),
                stock: stock,
            });
            tr.appendChild(cell);
            wireCell(cell, row);
        }

        renderSummary();
        console.info('[OGI] build page: ' + parsed.rows.length + ' objects, mode=' + parsed.mode);
    };
})(typeof globalThis !== 'undefined' ? globalThis : this);
