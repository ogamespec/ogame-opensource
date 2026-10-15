/**
 * Tests of the icon set and of the first-run panel.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const icons = require('../src/lib/icons.js');
const panel = require('../src/lib/panel.js');
const ui = require('../src/lib/ui.js');
const settings = require('../src/lib/settings.js');
const i18n = require('../src/lib/i18n.js');
const storeModule = require('../src/lib/store.js');
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

test('icons: every tab has an icon of its own set', function () {
    for (const tab of panel.TABS) {
        assert.ok(tab.icon, 'tab ' + tab.id + ' has no icon');
        const svg = icons.icon(tab.icon);
        assert.ok(svg.indexOf('<svg') === 0, 'tab ' + tab.id + ': not an svg');
        assert.ok(svg.indexOf('viewBox="0 0 16 16"') !== -1);
        assert.ok(svg.indexOf('</svg>') !== -1);
    }
    assert.equal(icons.icon('does-not-exist'), '', 'an unknown icon renders nothing');
});

test('icons: the resource icons follow the resource ids', function () {
    const expected = { 700: 'metal', 701: 'crystal', 702: 'deuterium', 703: 'energy', 704: 'darkmatter' };
    for (const gid of Object.keys(expected)) {
        const svg = icons.resourceIcon(parseInt(gid, 10));
        assert.ok(svg.indexOf('<svg') === 0, gid);
        assert.ok(svg.indexOf('#') !== -1, 'the resource icons carry their colour');
        assert.equal(icons.RESOURCE_ICONS[gid], expected[gid]);
    }
    // An unknown resource still renders a placeholder instead of nothing.
    assert.ok(icons.resourceIcon(999).indexOf('<svg') === 0);
});

test('icons: sizes and titles', function () {
    const small = icons.icon('spy', { size: 10 });
    assert.ok(small.indexOf('width="10"') !== -1 && small.indexOf('height="10"') !== -1);
    const titled = icons.icon('warning', { title: 'Hostile <fleet>' });
    assert.ok(titled.indexOf('<title>Hostile &lt;fleet&gt;</title>') !== -1, 'the title is escaped');
    assert.ok(titled.indexOf('role="img"') !== -1);
    const plain = icons.icon('clock');
    assert.ok(plain.indexOf('aria-hidden="true"') !== -1);
});

test('icons: the panel renders them in tabs, resources and marks', function () {
    const store = makeStore();
    store.observePlanet({ coords: { g: 1, s: 2, p: 3 }, name: 'Home', owner: 'Alice', date: 1000, report: true });
    store.push('spies', {
        date: 1000,
        coords: { g: 1, s: 2, p: 3 },
        planet: 'Home',
        player: 'Alice',
        resources: { 700: 1000, 701: 500, 702: 100 },
        fleet: {},
        defence: {},
    });
    const t = i18n.createI18n('en');
    const spy = panel.renderAnalyzer(store.data(), { i18n: t, tab: 'spy', lang: 'en' });
    assert.ok(spy.html.indexOf('ogi-icon') !== -1, 'the tabs carry icons');
    assert.ok((spy.html.match(/<svg/g) || []).length >= 8, 'several icons are rendered');

    const planets = panel.renderAnalyzer(store.data(), { i18n: t, tab: 'planets', lang: 'en' });
    assert.ok(planets.html.indexOf('favoriteOff') !== -1 || planets.html.indexOf('<svg') !== -1);

    const welcome = panel.welcomePanel(store.data(), { i18n: t });
    assert.ok(welcome.indexOf('ogi-welcome') !== -1);
    assert.ok((welcome.match(/<svg/g) || []).length >= 4, 'the welcome panel illustrates the steps');
});

test('ui: the panel header carries the logo', function () {
    const html = ui.panel('Test', 'body');
    assert.ok(html.indexOf('OGame Infinity 0.84') !== -1);
    assert.ok(html.indexOf('<svg') !== -1, 'the logo is an inline icon');
});

test('welcome: the panel explains the first steps and is hideable', function () {
    const store = makeStore();
    // A fresh universe starts with the panel enabled.
    assert.equal(settings.read(store).welcomeSeen, false);
    const t = i18n.createI18n('ru');
    const html = panel.welcomePanel(store.data(), { i18n: t });
    assert.ok(html.indexOf(t('WELCOME_TITLE')) !== -1);
    assert.ok(html.indexOf(t('WELCOME_STEP1')) !== -1);
    assert.ok(html.indexOf('data-ogi-action="welcome-scan"') !== -1, 'the scan button is there');
    assert.ok(html.indexOf('data-ogi-action="welcome-close"') !== -1, 'the hide button is there');

    settings.write(store, { welcomeSeen: true });
    assert.equal(settings.read(store).welcomeSeen, true, 'hiding it is remembered per universe');
    settings.write(store, { welcomeSeen: false });
    assert.equal(settings.read(store).welcomeSeen, false, 'the popup can bring it back');
});

test('welcome: every language has the strings', function () {
    for (const lang of ['en', 'ru', 'de']) {
        const t = i18n.createI18n(lang);
        for (const key of ['WELCOME_TITLE', 'WELCOME_INTRO', 'WELCOME_STEPS', 'WELCOME_STEP1',
            'WELCOME_STEP2', 'WELCOME_STEP3', 'WELCOME_STEP4', 'WELCOME_MORE', 'WELCOME_DISMISS']) {
            assert.notEqual(t(key), key, lang + ' is missing ' + key);
        }
    }
});
