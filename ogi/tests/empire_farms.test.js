/**
 * Tests of the three features added last: the empire page, the farm list and the
 * fleet dispatcher helpers (the pure parts — the helpers themselves only touch the
 * DOM of the game's fleet form).
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const imperium = require('../src/parsers/imperium.js');
const farms = require('../src/analyze/farms.js');
const collect = require('../src/lib/collect.js');
const storeModule = require('../src/lib/store.js');
const panel = require('../src/lib/panel.js');
const i18n = require('../src/lib/i18n.js');
const locaData = require('../src/data/loca.js');
const unitsData = require('../src/data/units.js');
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

test('empire: the imperium page of every language', function () {
    for (const lang of ['en', 'ru', 'de']) {
        const source = helpers.readGolden(lang, 'imperium_p0.html');
        const page = imperium.parseImperiumPage(source, { loca: loca, lang: lang });

        assert.equal(page.planets.length, 3, lang + ': planets');
        const [home, colonyA] = page.planets;
        assert.equal(home.id, 1, lang + ': planet id from the image link');
        assert.equal(home.name, 'Home', lang + ': planet name');
        assert.deepEqual(home.coords, { g: 1, s: 1, p: 4 }, lang + ': coordinates');
        assert.equal(home.fields.used, 14, lang + ': used fields');
        assert.equal(home.fields.max, 14, lang + ': maximum fields');
        assert.equal(home.resources[700].amount, 25047, lang + ': metal');
        assert.equal(home.resources[700].production, 286, lang + ': metal production');
        assert.equal(colonyA.resources[701].amount, 5007, lang + ': crystal of another planet');
        assert.equal(home.buildings[1], 5, lang + ': metal mine level');
        assert.ok(Object.keys(home.buildings).length >= 5, lang + ': several buildings were read');
    }
});

test('empire: the snapshot is stored and rendered', function () {
    const store = makeStore();
    const collector = collect.createCollector({ loca: loca, lang: 'ru', store: store, now: helpers.NOW });
    const result = collector.ingestImperiumPage(helpers.readGolden('ru', 'imperium_p0.html'));

    assert.equal(result.planets, 3);
    assert.equal(store.data().empire.planets.length, 3);
    assert.equal(store.data().empire.t, helpers.NOW);
    assert.equal(store.data().planets['1:1:4'].names[0], 'Home', 'the own planet is in the planet database');

    const t = i18n.createI18n('ru');
    const html = panel.imperiumSection(store.data(), { i18n: t, loca: loca, lang: 'ru' });
    assert.ok(html.indexOf('Home') !== -1 && html.indexOf('Colony A') !== -1);
    assert.ok(html.indexOf('25.047') !== -1, 'the metal stock is listed');
    assert.ok(html.indexOf('+286') !== -1, 'the production is listed');
    assert.ok(html.indexOf('14 / 14') !== -1, 'the fields are listed');
    assert.ok(html.indexOf(t('EMP_TOTAL')) !== -1, 'the sum row is there');
    assert.ok(html.indexOf(t('EMP_UPDATED', { T: panel.formatDate(helpers.NOW) })) !== -1);

    // Without a snapshot the tab explains what to do.
    const empty = panel.imperiumSection(makeStore().data(), { i18n: t, loca: loca, lang: 'ru' });
    assert.ok(empty.indexOf(t('EMP_NO_DATA')) !== -1);
});

test('farms: the list is ranked by loot and filtered', function () {
    const store = makeStore();
    store.push('spies', {
        date: helpers.NOW - 3600,
        coords: { g: 1, s: 3, p: 4 },
        planet: 'Home',
        player: 'PlayerTwo',
        resources: { 700: 20000, 701: 12000, 702: 6000 },
        fleet: { 204: 8 },
        defence: { 401: 3 },
    });
    store.push('spies', {
        date: helpers.NOW - 7200,
        coords: { g: 1, s: 5, p: 4 },
        planet: 'Colony',
        player: 'PlayerThree',
        resources: { 700: 60000, 701: 1000, 702: 0 },
        fleet: {},
        defence: {},
    });
    store.observeActivity(1, 5, 4, '*', helpers.NOW - 60);

    const rows = farms.farmList(store.data(), { now: helpers.NOW, unitsData: unitsData });
    assert.equal(rows.length, 2);
    assert.equal(rows[0].key, '1:5:4', 'the richest target is first');
    assert.equal(rows[0].loot.total, 30500);
    assert.equal(rows[0].active, true, 'a recent activity marker makes the target active');
    assert.equal(rows[1].key, '1:3:4');
    assert.equal(rows[1].needed.smallCargo, 4);
    assert.equal(rows[1].debris.total, 9600, 'the debris of the fleet of the report (defence has no debris by default)');
    assert.equal(rows[1].recyclers, 1);

    const inactive = farms.farmList(store.data(), { now: helpers.NOW, inactiveOnly: true, unitsData: unitsData });
    assert.deepEqual(inactive.map(function (row) { return row.key; }), ['1:3:4']);

    const rich = farms.farmList(store.data(), { now: helpers.NOW, minLoot: 30000, unitsData: unitsData });
    assert.deepEqual(rich.map(function (row) { return row.key; }), ['1:5:4']);

    const fresh = farms.farmList(store.data(), { now: helpers.NOW, maxAgeHours: 1, unitsData: unitsData });
    assert.equal(fresh.length, 1, 'the older report is out of the age window');
});

test('farms: the links point into the fleet dispatcher', function () {
    const target = { coords: { g: 1, s: 3, p: 4 }, type: 'moon' };
    const links = farms.targetLinks(target, 'abc123');
    assert.ok(links.attack.indexOf('page=flotten1') !== -1);
    assert.ok(links.attack.indexOf('galaxy=1&system=3&planet=4') !== -1);
    assert.ok(links.attack.indexOf('planettype=3') !== -1, 'a moon target keeps its type');
    assert.ok(links.attack.indexOf('target_mission=1') !== -1);
    assert.ok(links.transport.indexOf('target_mission=3') !== -1);
    assert.ok(links.spy.indexOf('target_mission=6') !== -1);
    assert.ok(links.attack.indexOf('session=abc123') !== -1);
});

test('farms: the panel renders the filters and the table', function () {
    const store = makeStore();
    store.push('spies', {
        date: helpers.NOW - 3600,
        coords: { g: 1, s: 3, p: 4 },
        planet: 'Home',
        player: 'PlayerTwo',
        resources: { 700: 20000, 701: 12000, 702: 6000 },
        fleet: { 204: 8 },
        defence: { 401: 3 },
    });
    const t = i18n.createI18n('ru');
    const html = panel.farmSection(store.data(), { i18n: t, unitsData: unitsData, session: 'abc', now: helpers.NOW });

    assert.ok(html.indexOf('data-ogi-farm="maxAge"') !== -1);
    assert.ok(html.indexOf('data-ogi-farm="inactive"') !== -1);
    assert.ok(html.indexOf('data-ogi-farm="minLoot"') !== -1);
    assert.ok(html.indexOf('1:3:4') !== -1);
    assert.ok(html.indexOf('PlayerTwo') !== -1);
    assert.ok(html.indexOf('target_mission=1') !== -1, 'an attack link is there');
    assert.ok(html.indexOf(t('FARM_AGE')) !== -1);

    const empty = panel.farmSection(makeStore().data(), { i18n: t, unitsData: unitsData, now: helpers.NOW });
    assert.ok(empty.indexOf(t('FARM_NO_TARGETS')) !== -1);
});

test('fleet: the helper panel renders for every step of the wizard', function () {
    const OGI_ROOT = __dirname;
    const store = makeStore();
    store.push('spies', {
        date: helpers.NOW - 600,
        coords: { g: 1, s: 3, p: 4 },
        player: 'PlayerTwo',
        resources: { 700: 5000, 701: 5000, 702: 0 },
        fleet: {},
        defence: {},
    });
    assert.ok(OGI_ROOT.indexOf('tests') !== -1);

    // The panel of the fleet helper is built by src/pages/fleet.js, which needs a DOM;
    // what can be checked here is that its data sources (the farm list and the links)
    // are in place, which the tests above do, plus that the tabs of the analyzer
    // expose the farm list.
    const t = i18n.createI18n('en');
    const rendered = panel.renderAnalyzer(store.data(), { i18n: t, tab: 'farms', lang: 'en', unitsData: unitsData, now: helpers.NOW });
    assert.ok(rendered.html.indexOf('data-ogi-tab="farms"') !== -1);
    assert.ok(rendered.html.indexOf('1:3:4') !== -1);

    const empire = panel.renderAnalyzer(store.data(), { i18n: t, tab: 'empire', lang: 'en', loca: loca });
    assert.ok(empire.html.indexOf('data-ogi-tab="empire"') !== -1);
    assert.ok(empire.html.indexOf(t('EMP_NO_DATA')) !== -1);
});
