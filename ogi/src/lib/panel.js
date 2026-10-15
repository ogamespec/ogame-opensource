/**
 * OGame Infinity 0.84 — analyzer panel rendering.
 *
 * Pure functions: they take the database and return HTML in the markup style of
 * the 0.84 pages (see testing/golden/en/*.html). Keeping the rendering free of DOM
 * access makes the panel testable in Node.
 */
(function (root) {
    'use strict';

    const ui = typeof require === 'function' ? require('./ui.js') : root.OGI;
    const analyze = typeof require === 'function' ? require('../analyze/index.js') : root.OGI;
    const numbers = typeof require === 'function' ? require('./numbers.js') : root.OGI;

    const TABS = [
        { id: 'spy', key: 'TAB_SPY' },
        { id: 'expeditions', key: 'TAB_EXP' },
        { id: 'combats', key: 'TAB_COMBAT' },
        { id: 'harvests', key: 'TAB_HARVEST' },
        { id: 'trades', key: 'TAB_TRADE' },
        { id: 'planets', key: 'TAB_PLANETS' },
        { id: 'totals', key: 'TAB_TOTALS' },
        { id: 'settings', key: 'TAB_SETTINGS' },
    ];

    /**
     * Format a timestamp as "DD.MM HH:MM" (the format the 0.84 pages use in their
     * message list).
     *
     * @param {number} timestamp Unix seconds.
     * @returns {string} Formatted date or "-".
     */
    function formatDate(timestamp) {
        if (!timestamp) return '-';
        const date = new Date(timestamp * 1000);
        const pad = function (value) { return String(value).padStart(2, '0'); };
        return pad(date.getDate()) + '.' + pad(date.getMonth() + 1) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    }

    /**
     * Short description of a battle result from the player's point of view.
     *
     * @param {object} combat Combat record.
     * @param {function} t Translator.
     * @returns {string} HTML.
     */
    function combatResult(combat, t) {
        if (combat.result === 'iwon') return ui.tag(t('COL_WIN'), 'good');
        if (combat.result === 'ilost') return ui.tag(t('COL_LOSS'), 'bad');
        if (combat.result === 'draw') return ui.tag(t('COL_DRAW'), 'neutral');
        return ui.tag('-', 'neutral');
    }

    /**
     * Spy report table.
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n, unitsData, cargo, limit, filter.
     * @returns {string} HTML.
     */
    function spySection(data, options) {
        const opt = options || {};
        const t = opt.i18n;
        const rows = analyze.spyTable(data, { unitsData: opt.unitsData, cargo: opt.cargo, limit: opt.limit || 100 });
        const filter = (opt.filter || '').toLowerCase();
        const out = [];
        for (const spy of rows) {
            const coords = spy.coords ? spy.coords.g + ':' + spy.coords.s + ':' + spy.coords.p : '-';
            const haystack = (coords + ' ' + (spy.planet || '') + ' ' + (spy.player || '')).toLowerCase();
            if (filter && haystack.indexOf(filter) === -1) continue;
            out.push([
                ui.esc(formatDate(spy.date)),
                ui.esc(coords),
                ui.esc(spy.planet || '-'),
                ui.esc(spy.player || '-'),
                ui.num(spy.resources.metal),
                ui.num(spy.resources.crystal),
                ui.num(spy.resources.deuterium),
                '<b>' + ui.num(spy.resources.total) + '</b>',
                ui.num(spy.maxLoot.total),
                ui.num(spy.needed.smallCargo) + ' / ' + ui.num(spy.needed.largeCargo),
                ui.num(spy.fleetValue.total),
                ui.num(spy.defenceValue.total),
            ]);
        }
        return ui.table([
            t('COL_DATE'), t('COL_COORDS'), t('COL_PLANET'), t('COL_PLAYER'),
            t('COL_METAL'), t('COL_CRYSTAL'), t('COL_DEUT'), t('COL_TOTAL'),
            t('COL_LOOT'), 'SC / LC', t('COL_FLEETVAL'), t('COL_DEFVAL'),
        ], out);
    }

    /**
     * Expedition table (per day).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n.
     * @returns {string} HTML.
     */
    function expeditionSection(data, options) {
        const t = options.i18n;
        const days = analyze.expeditionStats(data.expeditions || []);
        const rows = days.map(function (day) {
            const types = Object.keys(day.types).map(function (type) {
                return ui.esc(type) + ':' + day.types[type];
            }).join(', ');
            return [
                ui.esc(day.day),
                ui.num(day.count),
                types || '-',
                ui.num(day.found.metal),
                ui.num(day.found.crystal),
                ui.num(day.found.deuterium),
                ui.num(day.found.darkmatter),
                ui.num(day.found.ships),
                day.losses ? ui.tag(String(day.losses), 'bad') : '0',
            ];
        });
        return ui.table([
            t('COL_DAY'), t('COL_COUNT'), t('COL_TYPE'),
            t('COL_METAL'), t('COL_CRYSTAL'), t('COL_DEUT'), t('COL_DM'), t('COL_SHIPS'), t('COL_LOSS'),
        ], rows);
    }

    /**
     * Combat table (per day).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n.
     * @returns {string} HTML.
     */
    function combatSection(data, options) {
        const t = options.i18n;
        const days = analyze.combatStats(data.combats || []);
        const rows = days.map(function (day) {
            return [
                ui.esc(day.day),
                ui.num(day.count),
                day.wins ? ui.tag(String(day.wins), 'good') : '0',
                day.losses ? ui.tag(String(day.losses), 'bad') : '0',
                ui.num(day.draws),
                ui.num(day.loot.total),
                ui.num(day.ownLosses),
                ui.num(day.enemyLosses),
                ui.num(day.debris.total),
                '<b>' + ui.num(day.net) + '</b>',
            ];
        });
        return ui.table([
            t('COL_DAY'), t('COL_COUNT'), t('COL_WIN'), t('COL_LOSS'), t('COL_DRAW'),
            t('COL_LOOT'), t('COL_OWNLOSS'), t('COL_ENEMYLOSS'), t('COL_DEBRIS'), t('COL_NET'),
        ], rows);
    }

    /**
     * Recycling table (per day).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n.
     * @returns {string} HTML.
     */
    function harvestSection(data, options) {
        const t = options.i18n;
        const days = analyze.harvestStats(data.harvests || []);
        const rows = days.map(function (day) {
            return [
                ui.esc(day.day),
                ui.num(day.count),
                ui.num(day.recyclers),
                ui.num(day.metal),
                ui.num(day.crystal),
                '<b>' + ui.num(day.total) + '</b>',
            ];
        });
        return ui.table([
            t('COL_DAY'), t('COL_COUNT'), t('COL_RECYCLERS'),
            t('COL_METAL'), t('COL_CRYSTAL'), t('COL_TOTAL'),
        ], rows);
    }

    /**
     * Transport table (per day).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n.
     * @returns {string} HTML.
     */
    function tradeSection(data, options) {
        const t = options.i18n;
        const days = analyze.tradeStats(data.trades || []);
        const rows = days.map(function (day) {
            return [
                ui.esc(day.day),
                ui.num(day.count),
                ui.num(day.in.metal) + ' / ' + ui.num(day.in.crystal) + ' / ' + ui.num(day.in.deuterium),
                ui.num(day.out.metal) + ' / ' + ui.num(day.out.crystal) + ' / ' + ui.num(day.out.deuterium),
                '<b>' + ui.num(day.balance) + '</b>',
            ];
        });
        return ui.table([
            t('COL_DAY'), t('COL_COUNT'), t('COL_IN'), t('COL_OUT'), t('COL_BALANCE'),
        ], rows);
    }

    /**
     * Planets and players the extension has seen.
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n.
     * @returns {string} HTML.
     */
    function planetSection(data, options) {
        const t = options.i18n;
        const planets = data.planets || {};
        const rows = [];
        const keys = Object.keys(planets).sort(function (a, b) {
            return (planets[b].lastSeen || 0) - (planets[a].lastSeen || 0);
        });
        for (const key of keys) {
            const planet = planets[key];
            const activity = analyze.activitySummary((data.activity || {})[key]);
            const activityText = activity.observations === 0
                ? ui.esc(t('NEVER'))
                : (activity.activeNow ? ui.tag(t('ACTIVE'), 'good') : ui.esc(formatDate(activity.last)));
            rows.push([
                ui.esc(key),
                ui.esc((planet.names || []).join(', ') || '-'),
                ui.esc((planet.owners || []).join(', ') || '-'),
                ui.esc(formatDate(planet.lastSeen)),
                ui.num(planet.reports),
                activityText,
                ui.num(activity.sessions),
            ]);
        }

        const players = data.players || {};
        const playerRows = Object.keys(players).sort().map(function (name) {
            const player = players[name];
            return [
                ui.esc(name),
                ui.esc(formatDate(player.lastSeen)),
                player.points === null || player.points === undefined ? '-' : ui.num(player.points),
                ui.esc((player.coords || []).join(', ') || '-'),
            ];
        });

        return ui.table([
            t('COL_COORDS'), t('COL_NAMES'), t('COL_OWNERS'), t('COL_LASTSEEN'),
            t('COL_REPORTS'), t('COL_ACTIVITY'), t('COL_SESSIONS'),
        ], rows) + '<br>' + ui.table([t('COL_PLAYER'), t('COL_LASTSEEN'), 'Points', t('COL_COORDS')], playerRows);
    }

    /**
     * Totals of the whole database.
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n.
     * @returns {string} HTML.
     */
    function totalsSection(data, options) {
        const t = options.i18n;
        const totals = analyze.totals(data);
        const exp = totals.expeditionFound;
        return ui.keyValue([
            [ui.esc(t('TAB_SPY')), ui.num(totals.spies)],
            [ui.esc(t('TAB_PLANETS')), ui.num(totals.planets) + ' / ' + ui.num(totals.players)],
            [ui.esc(t('TAB_EXP')), ui.num(totals.expeditions)],
            [ui.esc(t('COL_METAL')) + ' / ' + ui.esc(t('COL_CRYSTAL')) + ' / ' + ui.esc(t('COL_DEUT')),
                ui.num(exp.metal) + ' / ' + ui.num(exp.crystal) + ' / ' + ui.num(exp.deuterium)],
            [ui.esc(t('COL_DM')), ui.num(exp.darkmatter)],
            [ui.esc(t('COL_SHIPS')), ui.num(exp.ships)],
            [ui.esc(t('TAB_COMBAT')), ui.num(totals.combats.count) + ' (' +
                totals.combats.wins + ' / ' + totals.combats.losses + ' / ' + totals.combats.draws + ')'],
            [ui.esc(t('COL_LOOT')), ui.num(totals.combats.loot)],
            [ui.esc(t('COL_OWNLOSS')), ui.num(totals.combats.ownLosses)],
            [ui.esc(t('COL_DEBRIS')), ui.num(totals.combats.debris)],
            [ui.esc(t('COL_NET')), ui.num(totals.combats.net)],
            [ui.esc(t('TAB_HARVEST')), ui.num(totals.harvests.total) + ' (' + ui.num(totals.harvests.count) + ')'],
            [ui.esc(t('TAB_TRADE')), ui.num(totals.trades.in) + ' / ' + ui.num(totals.trades.out) +
                ' = ' + ui.num(totals.trades.balance)],
        ]);
    }

    /**
     * One line summary of the database, shown in the collapsed panel.
     *
     * @param {object} data Store data.
     * @param {function} t Translator.
     * @returns {string} HTML.
     */
    function summaryLine(data, t) {
        return ui.esc(t('SUMMARY', {
            S: (data.spies || []).length,
            C: (data.combats || []).length,
            E: (data.expeditions || []).length,
            H: (data.harvests || []).length,
            T: (data.trades || []).length,
            P: Object.keys(data.planets || {}).length,
        }));
    }

    /**
     * Settings form: every control carries `data-ogi-setting`, which is what the
     * page wiring listens to (see src/pages/options.js).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n.
     * @returns {string} HTML.
     */
    function settingsSection(data, options) {
        const t = options.i18n;
        const settings = (typeof require === 'function'
            ? require('./settings.js').merge(require('./settings.js').DEFAULTS, data.settings || {})
            : root.OGI.merge(root.OGI.DEFAULTS, data.settings || {}));

        function checkbox(key, label) {
            return '<label class="ogi-check"><input type="checkbox" data-ogi-setting="' + ui.esc(key) + '"' +
                (settings[key] ? ' checked' : '') + ' /> ' + ui.esc(label) + '</label>';
        }

        function number(key, label, min, max) {
            return '<label class="ogi-check">' + ui.esc(label) + ': ' +
                '<input type="number" data-ogi-setting="' + ui.esc(key) + '" value="' + ui.esc(settings[key]) +
                '" min="' + ui.esc(min) + '" max="' + ui.esc(max) + '" size="4" /></label>';
        }

        function select(key, label, entries) {
            const options = entries.map(function (entry) {
                return '<option value="' + ui.esc(entry[0]) + '"' +
                    (String(settings[key]) === String(entry[0]) ? ' selected' : '') + '>' + ui.esc(entry[1]) + '</option>';
            }).join('');
            return '<label class="ogi-check">' + ui.esc(label) + ': <select data-ogi-setting="' + ui.esc(key) + '">' +
                options + '</select></label>';
        }

        const rate = settings.tradeRate;
        const out = [];
        out.push('<div class="ogi-settings">');
        out.push('<div class="ogi-settings-block">');
        out.push('<label class="ogi-check">' + ui.esc(t('SET_TRADE_RATE')) + ': ' +
            '<input type="number" step="0.1" min="0.1" max="100" data-ogi-setting="tradeRate" data-ogi-rate="0" value="' + ui.esc(rate[0]) + '" size="4" /> : ' +
            '<input type="number" step="0.1" min="0.1" max="100" data-ogi-setting="tradeRate" data-ogi-rate="1" value="' + ui.esc(rate[1]) + '" size="4" /> : ' +
            '<input type="number" step="0.1" min="0.1" max="100" data-ogi-setting="tradeRate" data-ogi-rate="2" value="' + ui.esc(rate[2]) + '" size="4" />' +
            '</label>');
        out.push(select('standardUnitBase', t('SET_UNIT_BASE'), [
            [0, t('SET_UNIT_BASE_METAL')],
            [1, t('SET_UNIT_BASE_CRYSTAL')],
            [2, t('SET_UNIT_BASE_DEUT')],
        ]));
        out.push('</div>');

        out.push('<div class="ogi-settings-block">');
        out.push(number('debrisFactor', t('SET_DEBRIS'), 0, 100));
        out.push(number('debrisFactorDef', t('SET_DEBRIS_DEF'), 0, 100));
        out.push(checkbox('deuteriumInDebris', t('SET_DEBRIS_DEUT')));
        out.push(number('historyDays', t('SET_HISTORY'), 7, 365));
        out.push('</div>');

        out.push('<div class="ogi-settings-block">');
        out.push(checkbox('autoScan', t('SET_AUTO_SCAN')));
        out.push(number('reportLimit', t('SET_REPORT_LIMIT'), 0, 200));
        out.push(checkbox('tooltips', t('SET_TOOLTIPS')));
        out.push(checkbox('highlightGalaxy', t('SET_HIGHLIGHT')));
        out.push(checkbox('highlightFavorites', t('SET_FAVORITES')));
        out.push(checkbox('notifyHostile', t('SET_NOTIFY')));
        out.push('</div>');
        out.push('</div>');
        return out.join('');
    }

    /**
     * Standalone settings panel (used by the game's Options page).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n, status.
     * @returns {string} HTML.
     */
    function settingsPanel(data, options) {
        const t = options.i18n;
        const status = options.status
            ? '<div class="ogi-status">' + ui.esc(options.status) + '</div>'
            : '';
        return ui.panel(t('SET_PAGE_TITLE'), status + settingsSection(data, options), {
            id: 'ogi-settings',
            actions: '<a href="#" class="ogi-action" data-ogi-action="export">' + ui.esc(t('SET_EXPORT')) + '</a> | ' +
                '<a href="#" class="ogi-action" data-ogi-action="reset">' + ui.esc(t('SET_RESET')) + '</a>',
        });
    }

    /**
     * Render the whole analyzer panel.
     *
     * @param {object} data Store data.
     * @param {object} options Options: lang, tab, i18n, unitsData, cargo, filter,
     *        status, collapsed.
     * @returns {{tabs: string, body: string, html: string}} Panel parts.
     */
    function renderAnalyzer(data, options) {
        const opt = options || {};
        const t = opt.i18n;
        const active = opt.tab || 'spy';
        const collapsed = !!opt.collapsed;
        const tabs = ui.tabs(TABS.map(function (tab) {
            return { id: tab.id, title: t(tab.key) };
        }), active);

        let body = '';
        const hasData = (data.spies || []).length || (data.expeditions || []).length ||
            (data.combats || []).length || (data.harvests || []).length ||
            (data.trades || []).length || Object.keys(data.planets || {}).length;
        if (!hasData) {
            body += '<div class="ogi-note">' + t('MSG_EMPTY') + '</div>';
        }

        switch (active) {
            case 'expeditions': body += expeditionSection(data, opt); break;
            case 'combats': body += combatSection(data, opt); break;
            case 'harvests': body += harvestSection(data, opt); break;
            case 'trades': body += tradeSection(data, opt); break;
            case 'planets': body += planetSection(data, opt); break;
            case 'totals': body += totalsSection(data, opt); break;
            case 'settings': body += settingsSection(data, opt); break;
            case 'spy':
            default: body += spySection(data, opt); break;
        }

        const actions = active === 'settings'
            ? [
                '<a href="#" class="ogi-action" data-ogi-action="save">' + ui.esc(t('SET_SAVE')) + '</a>',
                '<a href="#" class="ogi-action" data-ogi-action="reset">' + ui.esc(t('SET_RESET')) + '</a>',
                '<a href="#" class="ogi-action" data-ogi-action="export">' + ui.esc(t('SET_EXPORT')) + '</a>',
                '<a href="#" class="ogi-action" data-ogi-action="import">' + ui.esc(t('SET_IMPORT')) + '</a>',
            ].join(' | ')
            : [
                '<a href="#" class="ogi-action" data-ogi-action="toggle">' +
                    ui.esc(t(collapsed ? 'ACTION_EXPAND' : 'ACTION_COLLAPSE')) + '</a>',
                '<a href="#" class="ogi-action" data-ogi-action="scan">' + ui.esc(t('ACTION_SCAN')) + '</a>',
                '<a href="#" class="ogi-action" data-ogi-action="clear">' + ui.esc(t('ACTION_CLEAR')) + '</a>',
            ].join(' | ');
        const status = opt.status
            ? '<div class="ogi-status">' + ui.esc(opt.status) + '</div>'
            : '';

        return {
            tabs: tabs,
            body: body,
            summary: summaryLine(data, t),
            html: ui.panel(t('TITLE'), status + tabs + body, {
                actions: actions,
                summary: summaryLine(data, t),
                collapsed: collapsed,
                id: 'ogi-panel',
            }),
        };
    }

    const api = {
        TABS: TABS,
        formatDate: formatDate,
        combatResult: combatResult,
        summaryLine: summaryLine,
        settingsSection: settingsSection,
        settingsPanel: settingsPanel,
        renderAnalyzer: renderAnalyzer,
        sections: {
            spy: spySection,
            expeditions: expeditionSection,
            combats: combatSection,
            harvests: harvestSection,
            trades: tradeSection,
            planets: planetSection,
            totals: totalsSection,
        },
    };

    root.OGI = Object.assign(root.OGI || {}, api);
    if (typeof module === 'object' && module.exports) module.exports = api;
})(typeof globalThis !== 'undefined' ? globalThis : this);
