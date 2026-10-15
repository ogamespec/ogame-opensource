/**
 * End to end tests of the pipeline: game page -> collector -> store -> analyzers
 * -> panel HTML. Everything runs against the golden snapshots, so the extension is
 * verified with the same markup the game really produces.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const locaData = require('../src/data/loca.js');
const unitsData = require('../src/data/units.js');
const templates = require('../src/lib/templates.js');
const storeModule = require('../src/lib/store.js');
const collect = require('../src/lib/collect.js');
const analyze = require('../src/analyze/index.js');
const panel = require('../src/lib/panel.js');
const i18n = require('../src/lib/i18n.js');
const numbers = require('../src/lib/numbers.js');

const loca = templates.createLoca(locaData);

/**
 * Escape a value the way the UI helpers do, for substring assertions.
 *
 * @param {string} value Text.
 * @returns {string} Escaped text.
 */
function escaped(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Create an empty store for the tests.
 *
 * @returns {object} Store.
 */
function makeStore() {
    const store = storeModule.createStore({ universe: 'Uni1', host: 'localhost', storage: storeModule.memoryStorage() });
    store.load();
    return store;
}

/**
 * Collect the golden messages page (all folders) into a store.
 *
 * @param {object} store Store.
 * @param {string} [lang] Language.
 * @returns {object} Collector.
 */
function collectMessages(store, lang) {
    const language = lang || 'en';
    const collector = collect.createCollector({ loca: loca, lang: language, store: store, now: helpers.NOW });
    collector.ingestMessagesPage(helpers.readGolden(language, 'messages_p0.html'), { pm: null, now: helpers.NOW });
    return collector;
}

test('store: message deduplication and planet/player history', function () {
    const store = makeStore();
    assert.equal(store.isNewMessage('spy', 1), true);
    store.markMessage('spy', 1, 1000);
    assert.equal(store.isNewMessage('spy', 1), false);

    store.observePlanet({ coords: { g: 1, s: 2, p: 3 }, name: 'Home', owner: 'Alice', date: 1000, report: true });
    store.observePlanet({ coords: { g: 1, s: 2, p: 3 }, name: 'New Home', owner: 'Alice', date: 2000, report: true });
    const planet = store.data().planets['1:2:3'];
    assert.deepEqual(planet.names, ['Home', 'New Home']);
    assert.equal(planet.reports, 2);
    assert.equal(planet.lastSeen, 2000);

    store.observePlayer({ name: 'Alice', date: 2000, points: 1234, coords: { g: 1, s: 2, p: 3 } });
    assert.equal(store.data().players.Alice.points, 1234);

    store.observeActivity(1, 2, 3, '*', 1000);
    store.observeActivity(1, 2, 3, '*', 1030);
    store.observeActivity(1, 2, 3, '20', 5000);
    assert.equal(store.data().activity['1:2:3'].length, 2);
});

test('collector: a messages page fills the database once', function () {
    const store = makeStore();
    const collector = collectMessages(store);

    assert.equal(store.data().spies.length, 1, 'one inlined spy report');
    assert.equal(store.data().expeditions.length, 1, 'one expedition report');
    assert.equal(store.data().combats.length, 1, 'one battle report link');
    assert.equal(store.data().planets['1:3:4'].owners[0], 'PlayerTwo');
    assert.equal(store.data().players.PlayerTwo.lastSeen, 1788258435);

    const spy = store.data().spies[0];
    assert.equal(spy.resources[700], 20000);
    assert.equal(spy.fleet[204], 8);

    const combat = store.data().combats[0];
    assert.equal(combat.result, 'iwon');
    assert.equal(combat.side, 'attacker');
    assert.deepEqual(combat.losses, { defender: 100000, attacker: 50000 });
    assert.equal(combat.loot, null, 'the message row has no loot, the report popup adds it');

    // Reading the same folder again must not duplicate anything.
    collector.ingestMessagesPage(helpers.readGolden('en', 'messages_p0.html'), { pm: null, now: helpers.NOW });
    assert.equal(store.data().spies.length, 1);
    assert.equal(store.data().combats.length, 1);
    assert.equal(store.data().expeditions.length, 1);
});

test('collector: the report popup completes the battle record', function () {
    const store = makeStore();
    const collector = collectMessages(store);
    const combat = store.data().combats[0];

    collector.ingestReportPage(helpers.readGolden('en', 'bericht_battle_p0.html'), {
        msgId: combat.msgId,
        kind: 'battle',
        date: combat.date,
    });

    const updated = store.findRecord('combats', combat.msgId);
    assert.equal(store.data().combats.length, 1, 'no duplicate record');
    assert.deepEqual(updated.loot, { metal: 2000, crystal: 1000, deuterium: 500, total: 3500 });
    assert.deepEqual(updated.debris, { metal: 100, crystal: 50 });
    assert.equal(updated.attackerUnits['204'], 10);
    assert.equal(updated.defenderUnits['401'], 3);

    // Reading the message list again must not throw the report detail away.
    collector.ingestMessagesPage(helpers.readGolden('en', 'messages_p0.html'), { pm: null, now: helpers.NOW });
    const again = store.findRecord('combats', combat.msgId);
    assert.deepEqual(again.loot, { metal: 2000, crystal: 1000, deuterium: 500, total: 3500 });
    assert.equal(again.attackerUnits['204'], 10, 'the participants survive a rescan');
});

test('collector: German and Russian pages produce the same records', function () {
    for (const lang of ['de', 'ru']) {
        const store = makeStore();
        collectMessages(store, lang);
        assert.equal(store.data().spies.length, 1, lang + ': spies');
        assert.equal(store.data().spies[0].planet, 'Home', lang + ': planet');
        assert.deepEqual(store.data().spies[0].fleet, { 202: 5, 204: 8, 210: 3 }, lang + ': fleet');
        assert.equal(store.data().combats[0].result, 'iwon', lang + ': battle result');
    }
});

test('analyzer: spy reports get loot, needed cargo and values', function () {
    const store = makeStore();
    collectMessages(store);
    const analysis = analyze.spyAnalysis(store.data().spies[0], { unitsData: unitsData });

    assert.equal(analysis.resources.total, 38000);
    assert.deepEqual(analysis.ratio, [53, 32, 16]);
    assert.deepEqual(analysis.maxLoot, { metal: 10000, crystal: 6000, deuterium: 3000, total: 19000 });
    assert.equal(analysis.needed.smallCargo, 4);
    assert.equal(analysis.needed.largeCargo, 1);
    assert.equal(analysis.needed.probe, 3800);
    assert.equal(analysis.fleetValue.total, 55000);
    assert.equal(analysis.defenceValue.total, 10000);
    assert.equal(analysis.buildings, 11);

    // The loot for a small fleet is limited by its cargo capacity (the cargo is
    // split in three equal parts and floored, as in game/core/battle.php).
    const small = analyze.spyAnalysis(store.data().spies[0], { unitsData: unitsData, cargo: 5000 });
    assert.equal(small.loot.total, 4998);
});

test('analyzer: daily sums of every message type', function () {
    const spy = null;
    const days = [
        { date: 1788259200, type: 'resources', resource: { gid: 700, amount: 10000 } },
        { date: 1788259300, type: 'darkmatter', resource: { gid: 704, amount: 200 } },
        { date: 1788259400, type: 'ships', ships: { 203: 2 } },
        { date: 1788345600, type: 'lost' },
    ];
    const expeditions = analyze.expeditionStats(days);
    assert.equal(expeditions.length, 2);
    assert.equal(expeditions[1].count, 3);
    assert.equal(expeditions[1].found.metal, 10000);
    assert.equal(expeditions[1].found.darkmatter, 200);
    assert.equal(expeditions[1].found.ships, 24000, '2 large cargo ships cost 6000 metal and 6000 crystal each');
    assert.equal(expeditions[0].losses, 1);
    assert.equal(spy, null);

    const combats = analyze.combatStats([
        { date: 1788259200, side: 'attacker', result: 'iwon', loot: { metal: 1000, crystal: 0, deuterium: 0 }, losses: { attacker: 500, defender: 2000 }, debris: { metal: 100, crystal: 50 } },
        { date: 1788259300, side: 'defender', result: 'ilost', loot: { metal: 0, crystal: 0, deuterium: 0 }, losses: { attacker: 3000, defender: 4000 } },
        { date: 1788259400, side: 'attacker', result: 'draw', losses: { attacker: 0, defender: 0 } },
    ]);
    assert.equal(combats[0].wins, 1);
    assert.equal(combats[0].losses, 1);
    assert.equal(combats[0].draws, 1);
    assert.equal(combats[0].loot.total, 1000);
    assert.equal(combats[0].ownLosses, 4500, 'attacker losses + defender losses of the attacked planet');
    assert.equal(combats[0].enemyLosses, 5000);
    assert.equal(combats[0].debris.total, 150);

    const harvests = analyze.harvestStats([
        { date: 1788259200, harvested: { metal: 1000, crystal: 500 }, recyclers: 3 },
    ]);
    assert.equal(harvests[0].total, 1500);
    assert.equal(harvests[0].recyclers, 3);

    const trades = analyze.tradeStats([
        { date: 1788259200, kind: 'transport-out', delivered: { metal: 1000, crystal: 0, deuterium: 0 } },
        { date: 1788259300, kind: 'transport-in', delivered: { metal: 0, crystal: 500, deuterium: 0 } },
    ]);
    assert.equal(trades[0].out.total, 1000);
    assert.equal(trades[0].in.total, 500);
    assert.equal(trades[0].balance, -500);
});

test('analyzer: totals and activity summary', function () {
    const store = makeStore();
    collectMessages(store);
    const totals = analyze.totals(store.data());
    assert.equal(totals.spies, 1);
    assert.equal(totals.combats.count, 1);
    assert.equal(totals.combats.wins, 1);
    assert.equal(totals.combats.ownLosses, 50000);
    assert.equal(totals.planets, 1);

    const activity = analyze.activitySummary([
        { t: 1000, marker: '*' },
        { t: 1030, marker: '*' },
        { t: 9000, marker: '30' },
    ]);
    assert.equal(activity.sessions, 2);
    assert.equal(activity.activeNow, false);
    assert.equal(analyze.activitySummary([]).observations, 0);
});

test('panel: every tab renders in every language', function () {
    const store = makeStore();
    collectMessages(store);
    for (const lang of ['en', 'ru', 'de']) {
        const t = i18n.createI18n(lang);
        for (const tab of panel.TABS) {
            const rendered = panel.renderAnalyzer(store.data(), {
                i18n: t,
                tab: tab.id,
                lang: lang,
                unitsData: unitsData,
                status: 'test',
            });
            assert.ok(rendered.html.indexOf('ogi-panel') !== -1);
            assert.ok(rendered.html.indexOf(escaped(t('TITLE'))) !== -1, lang + '/' + tab.id + ': title');
            for (const other of panel.TABS) {
                assert.ok(rendered.html.indexOf(escaped(t(other.key))) !== -1, lang + ' missing tab ' + other.key);
            }
        }
    }
});

test('panel: the spy table shows the numbers of the report', function () {
    const store = makeStore();
    collectMessages(store);
    const rendered = panel.renderAnalyzer(store.data(), {
        i18n: i18n.createI18n('en'),
        tab: 'spy',
        lang: 'en',
        unitsData: unitsData,
    });
    assert.ok(rendered.html.indexOf('1:3:4') !== -1);
    assert.ok(rendered.html.indexOf('PlayerTwo') !== -1);
    assert.ok(rendered.html.indexOf(numbers.formatNumber(38000)) !== -1, 'total resources');
    assert.ok(rendered.html.indexOf(numbers.formatNumber(19000)) !== -1, 'maximum loot');
    assert.ok(rendered.html.indexOf('SC / LC') !== -1);
});

test('panel: player controlled text is escaped', function () {
    const store = makeStore();
    store.observePlanet({
        coords: { g: 1, s: 2, p: 3 },
        name: '<script>alert(1)</script>',
        owner: '" onmouseover="evil()',
        date: helpers.NOW,
        report: true,
    });
    const rendered = panel.renderAnalyzer(store.data(), {
        i18n: i18n.createI18n('en'),
        tab: 'planets',
        lang: 'en',
    });
    assert.equal(rendered.html.indexOf('<script>'), -1);
    assert.ok(rendered.html.indexOf('&lt;script&gt;') !== -1);
});

test('panel: the collapsed panel keeps the title, the actions and the summary', function () {
    const store = makeStore();
    collectMessages(store);
    const t = i18n.createI18n('en');
    const open = panel.renderAnalyzer(store.data(), { i18n: t, tab: 'spy', lang: 'en' });
    const closed = panel.renderAnalyzer(store.data(), { i18n: t, tab: 'spy', lang: 'en', collapsed: true });

    assert.ok(open.html.indexOf('ogi-tabs') !== -1, 'the open panel shows the tabs');
    assert.equal(closed.html.indexOf('ogi-tabs'), -1, 'the collapsed panel hides the tabs');
    assert.ok(closed.html.indexOf(t('ACTION_EXPAND')) !== -1, 'the collapsed panel offers to expand');
    assert.ok(open.html.indexOf(t('ACTION_COLLAPSE')) !== -1, 'the open panel offers to collapse');
    assert.ok(closed.summary.indexOf('Spy 1') !== -1, 'the summary counts the collected reports');
    assert.ok(closed.html.indexOf('data-ogi-action="toggle"') !== -1);
});
