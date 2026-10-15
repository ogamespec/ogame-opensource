/**
 * Tests of the overview event list (fleet timers) and of the needs calculator UI.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const overview = require('../src/parsers/overview.js');
const resbar = require('../src/parsers/resbar.js');
const needs = require('../src/analyze/needs.js');
const panel = require('../src/lib/panel.js');
const settings = require('../src/lib/settings.js');
const i18n = require('../src/lib/i18n.js');
const locaData = require('../src/data/loca.js');
const unitsData = require('../src/data/units.js');
const storeModule = require('../src/lib/store.js');
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

test('overview: every fleet event of every language', function () {
    for (const lang of ['en', 'ru', 'de']) {
        const source = helpers.readGolden(lang, 'overview_p0.html');
        const parsed = overview.parseEvents(source, { loca: loca, lang: lang });
        assert.equal(parsed.events.length, 18, lang + ': events');

        const first = parsed.events[0];
        assert.equal(first.secondsLeft, 300, lang + ': countdown');
        assert.ok(first.endsAt > 1700000000, lang + ': exact arrival time');
        assert.equal(first.direction, 'outgoing', lang + ': direction');
        assert.equal(first.mission, 'deploy', lang + ': mission');
        assert.deepEqual(first.ships, { 203: 2 }, lang + ': ships');
        assert.equal(first.hostile, false, lang + ': own fleet');

        const hostile = parsed.events.filter(function (event) { return event.hostile; });
        assert.equal(hostile.length, 2, lang + ': hostile fleets are recognised');
        assert.equal(hostile[0].mission, 'attack', lang + ': incoming attack');
        assert.equal(hostile[0].shipCount, 7, lang + ': the enemy fleet size comes from the tooltip');
        assert.equal(hostile[1].mission, 'spy', lang + ': incoming espionage');

        const transport = parsed.events.find(function (event) { return event.mission === 'transport' && event.cargo; });
        assert.ok(transport, lang + ': a transport with cargo exists');
        assert.equal(transport.cargo.total, 7000, lang + ': cargo total');

        // The events are sorted by arrival.
        for (let i = 1; i < parsed.events.length; i++) {
            assert.ok(parsed.events[i].endsAt >= parsed.events[i - 1].endsAt, lang + ': sorted by arrival');
        }
    }
});

test('overview: the fleet table shows countdowns and warns about hostiles', function () {
    const store = makeStore();
    const parsed = overview.parseEvents(helpers.readGolden('ru', 'overview_p0.html'), { loca: loca, lang: 'ru' });
    const t = i18n.createI18n('ru');
    const now = 1788259100;
    const html = panel.fleetSection(store.data(), { i18n: t, events: parsed.events, loca: loca, lang: 'ru', now: now });

    assert.ok(html.indexOf('Враждебный флот!') !== -1, 'a hostile warning is rendered');
    assert.ok(html.indexOf('data-ogi-countdown=') !== -1, 'the countdowns are live');
    const transport = parsed.events.find(function (event) { return event.mission === 'transport' && event.cargo; });
    assert.ok(html.indexOf(panel.formatCountdown(transport.endsAt, now)) !== -1,
        'the countdown is formatted from the exact arrival time');
    assert.ok(html.indexOf('Малый транспорт') !== -1, 'ships are named in the game language');
    assert.ok(html.indexOf('1:3:5') !== -1, 'the route is shown');
});

test('overview: the countdown formatter', function () {
    assert.equal(panel.formatCountdown(1000 + 65, 1000), '01:05');
    assert.equal(panel.formatCountdown(1000 + 3725, 1000), '1:02:05');
    assert.equal(panel.formatCountdown(500, 1000), '00:00', 'an arrived fleet shows zero');
});

test('resource bar: the stock of the current page', function () {
    for (const lang of ['en', 'ru', 'de', 'jp']) {
        const stock = resbar.parseResourceBar(helpers.readGolden(lang, 'overview_p0.html'));
        assert.deepEqual(stock, {
            metal: 25047, crystal: 15016, deuterium: 8003, darkmatter: 1500, energy: 359, energyMax: 529,
        }, lang);
    }
    assert.equal(resbar.parseResourceBar('<html><body>no resources here</body></html>'), null);
});

test('needs: the panel values an object against the known stock', function () {
    const store = makeStore();
    store.data().meta.stock = { metal: 100, crystal: 50, deuterium: 0, energy: 500 };
    store.data().meta.stockAt = helpers.NOW;
    settings.write(store, { needGid: 1, needFrom: 0, needTo: 2 });

    const t = i18n.createI18n('en');
    const html = panel.needsSection(store.data(), { i18n: t, loca: loca, lang: 'en', unitsData: unitsData });

    // Metal mine: level 1 costs 60/15, level 2 costs 90/22 — 150/37 in total.
    assert.ok(html.indexOf('data-ogi-need="needGid"') !== -1);
    assert.ok(html.indexOf('data-ogi-need="needTo"') !== -1);
    assert.ok(html.indexOf('150') !== -1, 'the total cost of the range is shown');
    assert.ok(html.indexOf('100') !== -1, 'the stock is shown');
    assert.ok(html.indexOf('Resources missing') !== -1, 'the stock is not enough for the range');

    // With plenty of resources the panel says so.
    store.data().meta.stock = { metal: 100000, crystal: 100000, deuterium: 100000, energy: 100000 };
    const rich = panel.needsSection(store.data(), { i18n: t, loca: loca, lang: 'en', unitsData: unitsData });
    assert.ok(rich.indexOf('Enough resources') !== -1);
    assert.ok(rich.indexOf('Stock of') !== -1, 'the age of the stock is shown');
});

test('needs: without a known stock the panel asks for a game page', function () {
    const store = makeStore();
    const t = i18n.createI18n('de');
    const html = panel.needsSection(store.data(), { i18n: t, loca: loca, lang: 'de', unitsData: unitsData });
    assert.ok(html.indexOf('Bestand unbekannt') !== -1);
    assert.equal(needs.levelCost(1, 1, unitsData).metal, 60);
});

test('needs: the tab is part of the analyzer', function () {
    const store = makeStore();
    const t = i18n.createI18n('en');
    const rendered = panel.renderAnalyzer(store.data(), { i18n: t, tab: 'needs', lang: 'en', unitsData: unitsData });
    assert.ok(rendered.html.indexOf('data-ogi-tab="needs"') !== -1);
    assert.ok(rendered.html.indexOf(t('NEED_OBJECT')) !== -1);
});
