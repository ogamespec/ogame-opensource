/**
 * Tests of the statistics: parsing the ranked table of every language, recording
 * the points history and building the account breakdown.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const statistics = require('../src/parsers/statistics.js');
const collect = require('../src/lib/collect.js');
const storeModule = require('../src/lib/store.js');
const analyze = require('../src/analyze/index.js');
const panel = require('../src/lib/panel.js');
const i18n = require('../src/lib/i18n.js');
const locaData = require('../src/data/loca.js');
const templates = require('../src/lib/templates.js');

const loca = templates.createLoca(locaData);

/**
 * Create a store for the tests.
 *
 * @returns {object} Store.
 */
function makeStore() {
    const store = storeModule.createStore({ universe: 'Uni1', host: 'localhost', storage: storeModule.memoryStorage() });
    store.load();
    return store;
}

test('statistics: the ranked table of every language', function () {
    for (const lang of ['en', 'de', 'ru']) {
        const source = helpers.readGolden(lang, 'statistics_p0.html');
        const page = statistics.parseStatisticsPage(source);
        assert.equal(page.who, 'player', lang + ': who');
        assert.equal(page.type, 'ressources', lang + ': type');
        assert.equal(page.rows.length, 3, lang + ': rows');

        const [first, second] = page.rows;
        assert.equal(first.rank, 1, lang + ': rank');
        assert.equal(first.player, 'PlayerOne', lang + ': own name');
        assert.equal(first.self, true, lang + ': the own row is marked');
        assert.equal(first.ally, 'TST', lang + ': alliance');
        assert.equal(first.points, 50, lang + ': points');
        assert.equal(first.delta.sign, '-', lang + ': rank change');
        assert.equal(first.delta.value, 1);
        assert.equal(first.rankDate, page.rows[0].rankDate, lang + ': rank date');
        assert.ok(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(first.rankDate), lang + ': date format');

        assert.equal(second.self, false, lang + ': another player');
        assert.equal(second.sameAlliance, true, lang + ': same alliance');
        assert.deepEqual(second.coords, { g: 1, s: 3, p: 4 }, lang + ': home planet');
        assert.equal(second.points, 45);
    }
});

test('statistics: reading the page fills the database', function () {
    const store = makeStore();
    const collector = collect.createCollector({ loca: loca, lang: 'en', store: store, now: helpers.NOW });
    const result = collector.ingestStatisticsPage(helpers.readGolden('en', 'statistics_p0.html'), {});

    assert.equal(result.rows, 3);
    assert.ok(result.self, 'the own row was found');
    assert.equal(result.self.player, 'PlayerOne');
    assert.equal(store.data().meta.selfName, 'PlayerOne');

    const player = store.data().players.PlayerTwo;
    assert.equal(player.points, 45);
    assert.equal(player.rank, 2);
    assert.equal(player.ally, 'TST');
    assert.deepEqual(player.home, { g: 1, s: 3, p: 4 });
    assert.deepEqual(player.coords, ['1:3:4']);

    assert.equal(store.data().stats.PlayerTwo.length, 1);
    assert.equal(store.data().stats.PlayerTwo[0].points, 45);
    assert.equal(store.data().stats.PlayerTwo[0].rank, 2);
});

test('statistics: the three point categories merge into one history entry', function () {
    const store = makeStore();
    const collector = collect.createCollector({ loca: loca, lang: 'en', store: store, now: helpers.NOW });
    const source = helpers.readGolden('en', 'statistics_p0.html');

    collector.ingestStatisticsPage(source, { type: 'ressources' });
    collector.setNow(helpers.NOW + 30);
    collector.ingestStatisticsPage(source, { type: 'fleet' });
    collector.setNow(helpers.NOW + 60);
    collector.ingestStatisticsPage(source, { type: 'research' });

    const history = store.data().stats.PlayerOne;
    assert.equal(history.length, 1, 'one visit produces one history entry');
    assert.equal(history[0].points, 50);
    assert.equal(history[0].rank, 1);
    assert.equal(history[0].fleet, 50, 'the fleet view is merged in');
    assert.equal(history[0].research, 50, 'the research view is merged in');

    // A later visit creates a new entry.
    collector.setNow(helpers.NOW + 4000);
    collector.ingestStatisticsPage(source, { type: 'ressources' });
    assert.equal(store.data().stats.PlayerOne.length, 2);
});

test('statistics: the account breakdown counts economy and defence', function () {
    const store = makeStore();
    const data = store.data();
    data.players.PlayerOne = { name: 'PlayerOne', points: 1000, rank: 7 };
    data.stats.PlayerOne = [
        { t: 100, points: 900, rank: 9, fleet: 200, research: 100 },
        { t: 200, points: 1000, rank: 7, fleet: 250, research: 150 },
    ];

    const account = analyze.accountBreakdown(data, 'PlayerOne');
    assert.equal(account.points, 1000);
    assert.equal(account.rank, 7);
    assert.equal(account.fleet, 250);
    assert.equal(account.research, 150);
    assert.equal(account.economy, 600, 'points minus fleets minus research is economy and defence');
    assert.equal(account.history.length, 2);

    const series = analyze.historySeries(data, 'PlayerOne', 60);
    assert.equal(series.length, 2, 'both entries are inside the window');

    const rows = analyze.statisticsRows([
        { rank: 7, player: 'PlayerOne', self: true, points: 1000 },
        { rank: 8, player: 'Stranger', points: 500 },
    ], data);
    assert.equal(rows[0].fleet, 250);
    assert.equal(rows[0].economy, 600);
    assert.equal(rows[1].fleet, null, 'an unknown player has no fleet value');
});

test('statistics: the panel shows the account and the ranks', function () {
    const store = makeStore();
    const collector = collect.createCollector({ loca: loca, lang: 'en', store: store, now: helpers.NOW });
    const source = helpers.readGolden('en', 'statistics_p0.html');
    collector.ingestStatisticsPage(source, { type: 'ressources' });
    collector.ingestStatisticsPage(source, { type: 'fleet' });

    const rows = analyze.statisticsRows(statistics.parseStatisticsPage(source).rows, store.data());
    const t = i18n.createI18n('ru');
    const html = panel.statsSection(store.data(), { i18n: t, rows: rows });
    assert.ok(html.indexOf('Свой аккаунт') !== -1);
    assert.ok(html.indexOf('PlayerOne') !== -1);
    assert.ok(html.indexOf('ogi-bar-fill') !== -1, 'the categories are drawn as bars');
    assert.ok(html.indexOf('Экономика + оборона') !== -1);
    assert.ok(html.indexOf('1:3:4') !== -1, 'the home planet of the other player is listed');

    // The tab renders inside the analyzer panel as well.
    const tabbed = panel.renderAnalyzer(store.data(), { i18n: t, tab: 'stats', lang: 'ru', rows: rows });
    assert.ok(tabbed.html.indexOf(t('TAB_STATS')) !== -1);
    assert.ok(tabbed.html.indexOf('data-ogi-tab="stats"') !== -1);
});

test('statistics: the alliance view is skipped instead of guessing columns', function () {
    const page = statistics.parseStatisticsPage('<html><select name="who"><option value="ally" selected>a</option></select></html>');
    assert.equal(page.who, 'ally');
    assert.deepEqual(page.rows, []);
});
