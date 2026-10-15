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
        { id: 'stats', key: 'TAB_STATS' },
        { id: 'needs', key: 'TAB_NEEDS' },
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
        const recycling = typeof require === 'function' ? require('../analyze/recycling.js') : root.OGI;
        const settingsLib = typeof require === 'function' ? require('./settings.js') : root.OGI;
        const settings = settingsLib.read({ data: function () { return data; } });
        const rows = analyze.spyTable(data, { unitsData: opt.unitsData, cargo: opt.cargo, limit: opt.limit || 100 });
        const filter = (opt.filter || '').toLowerCase();
        const out = [];
        for (const spy of rows) {
            const coords = spy.coords ? spy.coords.g + ':' + spy.coords.s + ':' + spy.coords.p : '-';
            const haystack = (coords + ' ' + (spy.planet || '') + ' ' + (spy.player || '')).toLowerCase();
            if (filter && haystack.indexOf(filter) === -1) continue;

            // What the scanned fleet and defence would leave behind if it were
            // destroyed — the 0.84 equivalent of OGI's recycling yield warning.
            const fleetDebris = recycling.recyclingYield(spy.fleet || {}, opt.unitsData, settings);
            const defenceDebris = recycling.recyclingYield(spy.defence || {}, opt.unitsData, settings);
            const debrisTotal = fleetDebris.total + defenceDebris.total;
            const debris = debrisTotal > 0
                ? '<br><span class="ogi-info">' + ui.esc(t('COL_DEBRIS')) + ' ' + ui.num(debrisTotal) +
                    ' (' + ui.num(recycling.recyclersNeeded(debrisTotal, opt.unitsData)) + ')</span>'
                : '';

            out.push([
                ui.esc(formatDate(spy.date)),
                ui.esc(coords),
                ui.esc(spy.planet || '-'),
                ui.esc(spy.player || '-'),
                ui.num(spy.resources.metal),
                ui.num(spy.resources.crystal),
                ui.num(spy.resources.deuterium),
                '<b>' + ui.num(spy.resources.total) + '</b>',
                ui.num(spy.maxLoot.total) + debris,
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
     * The planet and player database: everything the extension has seen, with a
     * filter, favourite marks and notes (OGI's galaxy database, kept in the browser).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n, filter, favoritesOnly.
     * @returns {string} HTML.
     */
    function planetSection(data, options) {
        const t = options.i18n;
        const planets = data.planets || {};
        const favorites = (data.favorites || {}).planets || {};
        const notes = data.notes || {};
        const filter = (options.filter || '').trim().toLowerCase();
        const favoritesOnly = !!options.favoritesOnly;
        const out = [];

        out.push('<div class="ogi-settings-block">');
        out.push('<label class="ogi-check">' + ui.esc(t('PL_FILTER')) + ': ' +
            '<input type="text" data-ogi-planet-filter="1" value="' + ui.esc(options.filter || '') + '" size="24" /> ' +
            '<label class="ogi-check ogi-inline"><input type="checkbox" data-ogi-planet-fav="1"' +
            (favoritesOnly ? ' checked' : '') + ' /> ' + ui.esc(t('PL_FAVORITES_ONLY')) + '</label></label>');
        out.push('</div>');

        const rows = [];
        const keys = Object.keys(planets).sort(function (a, b) {
            const favoriteDiff = (favorites[b] ? 1 : 0) - (favorites[a] ? 1 : 0);
            if (favoriteDiff !== 0) return favoriteDiff;
            return (planets[b].lastSeen || 0) - (planets[a].lastSeen || 0);
        });

        let shown = 0;
        for (const key of keys) {
            const planet = planets[key];
            const names = (planet.names || []).join(', ');
            const owners = (planet.owners || []).join(', ');
            const haystack = (key + ' ' + names + ' ' + owners).toLowerCase();
            if (filter && haystack.indexOf(filter) === -1) continue;
            if (favoritesOnly && !favorites[key]) continue;

            const activity = analyze.activitySummary((data.activity || {})[key]);
            const activityText = activity.observations === 0
                ? ui.esc(t('NEVER'))
                : (activity.activeNow ? ui.tag(t('ACTIVE'), 'good') : ui.esc(formatDate(activity.last)));

            const spies = (data.spies || []).filter(function (spy) {
                return spy.coords && coordsText(spy.coords) === key;
            });
            const lastSpy = spies.length ? spies[0] : null;

            rows.push([
                '<a href="#" class="ogi-action" data-ogi-fav="' + ui.esc(key) + '">' +
                    (favorites[key] ? '★' : '☆') + '</a>',
                ui.esc(key),
                ui.esc(planet.type === 'moon' ? t('PL_MOON') : t('PL_PLANET')),
                ui.esc(names || '-'),
                ui.esc(owners || '-'),
                lastSpy ? ui.esc(formatDate(lastSpy.date)) : '-',
                ui.num(planet.reports),
                activityText,
                '<input type="text" class="ogi-note-input" data-ogi-note="' + ui.esc(key) + '" value="' +
                    ui.esc(notes[key] || '') + '" size="10" />',
            ]);
            shown++;
        }

        const players = data.players || {};
        const playerFavorites = (data.favorites || {}).players || {};
        const playerRows = Object.keys(players).sort(function (a, b) {
            const favoriteDiff = (playerFavorites[b] ? 1 : 0) - (playerFavorites[a] ? 1 : 0);
            if (favoriteDiff !== 0) return favoriteDiff;
            return (players[b].points || 0) - (players[a].points || 0);
        }).filter(function (name) {
            if (favoritesOnly && !playerFavorites[name]) return false;
            if (!filter) return true;
            return name.toLowerCase().indexOf(filter) !== -1 ||
                ((players[name].coords || []).join(' ').toLowerCase().indexOf(filter) !== -1);
        }).map(function (name) {
            const player = players[name];
            const account = analyze.accountBreakdown(data, name);
            return [
                '<a href="#" class="ogi-action" data-ogi-fav="player:' + ui.esc(name) + '">' +
                    (playerFavorites[name] ? '★' : '☆') + '</a>',
                ui.esc(name),
                ui.esc(player.ally || '-'),
                account.rank === null ? '-' : ui.num(account.rank),
                player.points === null || player.points === undefined ? '-' : ui.num(player.points),
                account.fleet === null ? '-' : ui.num(account.fleet),
                account.research === null ? '-' : ui.num(account.research),
                ui.esc(player.home ? coordsText(player.home) : ((player.coords || []).join(', ') || '-')),
                ui.esc(formatDate(player.lastSeen)),
            ];
        });

        out.push(ui.table([
            '★', t('COL_COORDS'), t('PL_PLANET'), t('COL_NAMES'), t('COL_OWNERS'),
            t('TT_LAST_SPY'), t('COL_REPORTS'), t('COL_ACTIVITY'), t('PL_NOTE'),
        ], rows));
        out.push('<div class="ogi-status">' + ui.num(shown) + ' / ' + ui.num(keys.length) + '</div>');

        out.push('<br>' + ui.table([
            '★', t('COL_PLAYER'), t('COL_ALLY'), t('COL_RANK'), t('STAT_POINTS'),
            t('STAT_FLEET_POINTS'), t('STAT_RESEARCH_POINTS'), t('COL_HOME'), t('COL_LASTSEEN'),
        ], playerRows));
        return out.join('');
    }

    /**
     * Coordinates of a stored spy report as text.
     *
     * @param {object} coords Coordinates.
     * @returns {string} "g:s:p".
     */
    function coordsText(coords) {
        if (!coords) return '';
        return coords.g + ':' + coords.s + ':' + coords.p;
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
     * A proportional bar with a label, used for the point breakdown and the history.
     *
     * @param {number} value Value.
     * @param {number} max Maximum of the scale.
     * @param {string} label Text inside the bar.
     * @param {string} [className] Extra class.
     * @returns {string} HTML.
     */
    function bar(value, max, label, className) {
        const percent = max > 0 ? Math.max(0, Math.min(100, Math.round((value / max) * 100))) : 0;
        return '<div class="ogi-bar ' + ui.esc(className || '') + '">' +
            '<div class="ogi-bar-fill" style="width:' + percent + '%"></div>' +
            '<span class="ogi-bar-label">' + label + '</span></div>';
    }

    /**
     * Statistics: the own account breakdown by points plus the ranks of the last
     * parsed view (game/pages/statistics.php).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n, rows, showActions.
     * @returns {string} HTML.
     */
    function statsSection(data, options) {
        const t = options.i18n;
        const selfName = (data.meta && data.meta.selfName) || null;
        const out = [];

        if (!selfName) {
            out.push('<div class="ogi-note">' + ui.esc(t('STAT_NO_DATA')) + '</div>');
        } else {
            const account = analyze.accountBreakdown(data, selfName);
            const total = account.points === null ? 0 : account.points;
            const categories = [
                [t('STAT_ECO_DEF'), account.economy === null ? 0 : account.economy, 'ogi-eco'],
                [t('STAT_FLEET_POINTS'), account.fleet === null ? 0 : account.fleet, 'ogi-fleet'],
                [t('STAT_RESEARCH_POINTS'), account.research === null ? 0 : account.research, 'ogi-research'],
            ];
            const max = Math.max(total, categories[0][1], categories[1][1], categories[2][1], 1);

            out.push('<div class="ogi-settings-block"><b>' + ui.esc(t('STAT_OWN')) + ': ' + ui.esc(selfName) + '</b>');
            out.push(ui.keyValue([
                [ui.esc(t('STAT_POINTS')), ui.num(account.points)],
                [ui.esc(t('COL_RANK')), ui.num(account.rank)],
            ]));
            for (const entry of categories) {
                out.push(bar(entry[1], max, ui.esc(entry[0]) + ': ' + ui.num(entry[1]), entry[2]));
            }
            out.push('</div>');

            const history = analyze.historySeries(data, selfName, options.historyDays || 60).slice(-20);
            if (history.length > 1) {
                const points = history.map(function (entry) { return entry.points === undefined ? 0 : entry.points; });
                const historyMax = Math.max.apply(null, points.concat([1]));
                out.push('<div class="ogi-settings-block"><b>' + ui.esc(t('STAT_HISTORY')) + '</b>');
                for (const entry of history) {
                    out.push(bar(entry.points === undefined ? 0 : entry.points, historyMax,
                        ui.esc(formatDate(entry.t)) + ' — ' + ui.num(entry.points) +
                        (entry.rank === undefined ? '' : ' (' + ui.esc(t('COL_RANK')) + ' ' + ui.num(entry.rank) + ')'),
                        'ogi-history'));
                }
                out.push('</div>');
            }
        }

        const rows = options.rows || [];
        if (rows.length) {
            const table = rows.map(function (row) {
                const delta = row.delta
                    ? (row.delta.sign === '+' ? ui.tag('+' + row.delta.value, 'good')
                        : (row.delta.sign === '-' ? ui.tag('-' + row.delta.value, 'bad') : ui.tag('*', 'neutral')))
                    : '-';
                const name = row.self ? '<b>' + ui.esc(row.player) + '</b>'
                    : (row.sameAlliance ? '<span class="ogi-info">' + ui.esc(row.player) + '</span>' : ui.esc(row.player));
                const coords = row.coords ? row.coords.g + ':' + row.coords.s + ':' + row.coords.p : '-';
                return [
                    ui.num(row.rank) + ' ' + delta,
                    name,
                    ui.esc(row.ally || '-'),
                    ui.esc(coords),
                    ui.num(row.points),
                    ui.num(row.fleet),
                    ui.num(row.research),
                    ui.num(row.economy),
                ];
            });
            out.push(ui.table([
                t('COL_RANK'), t('COL_PLAYER'), t('COL_ALLY'), t('COL_COORDS'), t('STAT_POINTS'),
                t('STAT_FLEET_POINTS'), t('STAT_RESEARCH_POINTS'), t('STAT_ECO_DEF'),
            ], table));
        }

        return out.join('');
    }

    /**
     * Countdown text of a timestamp.
     *
     * @param {number} endsAt Unix seconds.
     * @param {number} [now] Current time (for tests).
     * @returns {string} "MM:SS" or "H:MM:SS".
     */
    function formatCountdown(endsAt, now) {
        const reference = now === undefined ? Math.floor(Date.now() / 1000) : now;
        let seconds = Math.max(0, (endsAt || 0) - reference);
        const hours = Math.floor(seconds / 3600);
        seconds -= hours * 3600;
        const minutes = Math.floor(seconds / 60);
        seconds -= minutes * 60;
        const pad = function (value) { return String(value).padStart(2, '0'); };
        return (hours > 0 ? hours + ':' : '') + pad(minutes) + ':' + pad(seconds);
    }

    /**
     * Localized name of a game object.
     *
     * @param {string|number} gid Object id.
     * @param {object} options Options: loca, lang.
     * @returns {string} Name.
     */
    function objectName(gid, options) {
        if (options && options.loca && options.lang) {
            const name = options.loca.get(options.lang, 'NAME_' + gid);
            if (name) return name;
        }
        return String(gid);
    }

    /**
     * The fleets in the air with live countdowns to their exact arrival time
     * (game/pages/overview_events.php — the `star` attribute of the timer element).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n, events, loca, lang, now.
     * @returns {string} HTML.
     */
    function fleetSection(data, options) {
        const t = options.i18n;
        const events = options.events || [];
        const now = options.now === undefined ? Math.floor(Date.now() / 1000) : options.now;
        const settings = typeof require === 'function'
            ? require('./settings.js').read({ data: function () { return data; } })
            : root.OGI.read({ data: function () { return data; } });
        const out = [];

        const hostiles = events.filter(function (event) { return event.hostile; });
        if (hostiles.length && settings.notifyHostile) {
            out.push('<div class="ogi-alert">');
            out.push('<b>' + ui.esc(t('OV_HOSTILE')) + '</b>');
            for (const event of hostiles.slice(0, 5)) {
                const route = event.coords && event.coords.length
                    ? event.coords[event.coords.length - 1].text
                    : '-';
                out.push('<div>' + ui.esc((event.missionText || event.mission || '?') + ' ' + route + ' — ' +
                    t('OV_LEFT') + ' ') +
                    '<span data-ogi-countdown="' + ui.esc(event.endsAt) + '">' +
                    ui.esc(formatCountdown(event.endsAt, now)) + '</span></div>');
            }
            out.push('</div>');
        }

        if (!events.length) {
            out.push('<div class="ogi-note">' + ui.esc(t('OV_NO_EVENTS')) + '</div>');
            return out.join('');
        }

        const rows = events.map(function (event) {
            const ships = Object.keys(event.ships || {}).map(function (gid) {
                return ui.num(event.ships[gid]) + ' ' + ui.esc(objectName(gid, options));
            }).join(', ');
            const route = (event.coords || []).map(function (coords) { return ui.esc(coords.text); }).join(' → ');
            const arrow = event.direction === 'returning' ? '◀ ' : (event.direction === 'holding' ? '● ' : '▶ ');
            return [
                '<span data-ogi-countdown="' + ui.esc(event.endsAt) + '">' +
                    ui.esc(formatCountdown(event.endsAt, now)) + '</span>',
                event.hostile ? ui.tag(event.missionText || event.mission || '?', 'bad')
                    : ui.esc(event.missionText || event.mission || '?'),
                ui.esc(arrow) + route,
                ships || (event.shipCount !== null && event.shipCount !== undefined
                    ? ui.num(event.shipCount) + ' ' + ui.esc(t('OV_UNKNOWN_FLEET'))
                    : '-'),
                event.cargo ? ui.num(event.cargo.total) : '-',
            ];
        });

        out.push(ui.table([
            t('OV_LEFT'), t('OV_MISSION'), t('OV_ROUTE'), t('OV_SHIPS'), t('COL_CARGO'),
        ], rows));
        return out.join('');
    }

    /**
     * Standalone panel with the fleets in the air (overview page).
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n, events, loca, lang, now, status.
     * @returns {string} HTML.
     */
    function fleetPanel(data, options) {
        const t = options.i18n;
        const status = options.status
            ? '<div class="ogi-status">' + ui.esc(options.status) + '</div>'
            : '';
        return ui.panel(t('OV_TITLE'), status + fleetSection(data, options), { id: 'ogi-fleets' });
    }

    /**
     * Needs calculator: how much of what is still missing for the next levels.
     *
     * The stock comes from the resource bar of the last game page the player opened
     * (parsers/resbar.js), the costs from the generated object tables
     * (src/data/units.js) and the formula from analyze/needs.js.
     *
     * @param {object} data Store data.
     * @param {object} options Options: i18n, loca, lang, unitsData.
     * @returns {string} HTML.
     */
    function needsSection(data, options) {
        const t = options.i18n;
        const settingsLib = typeof require === 'function' ? require('./settings.js') : root.OGI;
        const needsLib = typeof require === 'function' ? require('../analyze/needs.js') : root.OGI;
        const units = options.unitsData || (typeof require === 'function' ? require('../data/units.js') : root.OGI.UNITS);
        const settings = settingsLib.merge(settingsLib.DEFAULTS, data.settings || {});
        const gid = settings.needGid;
        const fromLevel = settings.needFrom;
        const toLevel = Math.max(fromLevel + 1, settings.needTo);

        function group(label, ids) {
            return '<optgroup label="' + ui.esc(label) + '">' + (ids || []).map(function (id) {
                return '<option value="' + ui.esc(id) + '"' + (id === gid ? ' selected' : '') + '>' +
                    ui.esc(objectName(id, options)) + '</option>';
            }).join('') + '</optgroup>';
        }

        const out = [];
        out.push('<div class="ogi-settings-block">');
        out.push('<label class="ogi-check">' + ui.esc(t('NEED_OBJECT')) + ': <select data-ogi-need="needGid">' +
            group(t('NEED_GROUP_BUILDINGS'), units.lists.buildings) +
            group(t('NEED_GROUP_RESEARCH'), units.lists.research) +
            group(t('NEED_GROUP_SHIPS'), units.lists.fleet) +
            group(t('NEED_GROUP_DEFENCE'), units.lists.defence) +
            '</select></label>');
        out.push('<label class="ogi-check">' + ui.esc(t('NEED_FROM')) + ': ' +
            '<input type="number" min="0" max="100" data-ogi-need="needFrom" value="' + ui.esc(fromLevel) + '" size="4" /> ' +
            ui.esc(t('NEED_TO')) + ': ' +
            '<input type="number" min="1" max="100" data-ogi-need="needTo" value="' + ui.esc(toLevel) + '" size="4" /></label>');
        out.push('</div>');

        const cost = needsLib.levelCost(gid, toLevel, units);
        const total = needsLib.totalCost(gid, fromLevel, toLevel, units);
        const stock = (data.meta && data.meta.stock) || null;
        const result = needsLib.needs(stock || {}, total, {});

        out.push(ui.keyValue([
            [ui.esc(t('NEED_COST_LEVEL')) + ' ' + ui.num(toLevel),
                ui.num(cost.metal) + ' / ' + ui.num(cost.crystal) + ' / ' + ui.num(cost.deuterium) +
                (cost.energy ? ' / ' + ui.num(cost.energy) + ' ' + ui.esc(t('NEED_ENERGY')) : '')],
            [ui.esc(t('NEED_COST_TOTAL')) + ' ' + ui.num(fromLevel) + '→' + ui.num(toLevel),
                ui.num(total.metal) + ' / ' + ui.num(total.crystal) + ' / ' + ui.num(total.deuterium)],
        ]));

        const rows = [];
        const resources = [['metal', 700], ['crystal', 701], ['deuterium', 702], ['energy', 703]];
        for (const resource of resources) {
            const needed = total[resource[0]] || 0;
            if (!needed) continue;
            const have = stock ? (stock[resource[0]] || 0) : 0;
            rows.push([
                ui.esc(objectName(resource[1], options)),
                ui.num(needed),
                stock ? ui.num(have) : ui.esc(t('NEED_UNKNOWN')),
                stock ? '<b>' + ui.num(result[resource[0]] || 0) + '</b>' : '-',
            ]);
        }
        out.push(ui.table([t('COL_RESOURCE'), t('COL_NEEDED'), t('NEED_HAVE'), t('NEED_MISSING')], rows));

        if (!stock) {
            out.push('<div class="ogi-note">' + ui.esc(t('NEED_NO_STOCK')) + '</div>');
        } else {
            out.push('<div class="ogi-note">' +
                (result.affordable ? ui.tag(t('NEED_ENOUGH'), 'good') : ui.tag(t('NEED_SHORT'), 'bad')) + ' ' +
                ui.esc(t('NEED_STOCK_AGE', { T: formatDate(data.meta.stockAt || 0) })) + '</div>');
        }
        return out.join('');
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
            case 'planets': body += planetSection(data, opt); break;            case 'totals': body += totalsSection(data, opt); break;
            case 'stats': body += statsSection(data, opt); break;
            case 'needs': body += needsSection(data, opt); break;
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
        bar: bar,
        needsSection: needsSection,
        planetSection: planetSection,
        formatCountdown: formatCountdown,
        objectName: objectName,
        fleetSection: fleetSection,
        fleetPanel: fleetPanel,
        statsSection: statsSection,
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
