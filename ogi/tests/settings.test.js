/**
 * Tests of the settings (validation, storage, export/import) and of the settings
 * form rendering.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const settings = require('../src/lib/settings.js');
const panel = require('../src/lib/panel.js');
const i18n = require('../src/lib/i18n.js');
const storeModule = require('../src/lib/store.js');

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

test('settings: defaults cover the whole feature set', function () {
    const store = makeStore();
    const current = settings.read(store);
    assert.deepEqual(current.tradeRate, [3, 2, 1]);
    assert.equal(current.standardUnitBase, 2);
    assert.equal(current.autoScan, true);
    assert.equal(current.tooltips, true);
    assert.equal(current.highlightGalaxy, true);
    assert.equal(current.notifyHostile, true);
    assert.equal(current.debrisFactor, 30);
    assert.equal(current.debrisFactorDef, 0);
    assert.equal(current.deuteriumInDebris, false);
    assert.equal(current.historyDays, 60);
});

test('settings: values are validated, junk is dropped', function () {
    const merged = settings.merge(settings.DEFAULTS, {
        tradeRate: ['4', 'x', '-1'],
        standardUnitBase: 99,
        debrisFactor: 1000,
        debrisFactorDef: -5,
        reportLimit: '7',
        historyDays: '1',
        autoScan: 'on',
        tooltipss: true,
        evil: 'DROP TABLE users',
    });
    assert.deepEqual(merged.tradeRate, [4, 2, 1], 'a broken rate falls back to the default of its position');
    assert.equal(merged.standardUnitBase, 2, 'the base index is clamped to the trade rate');
    assert.equal(merged.debrisFactor, 100);
    assert.equal(merged.debrisFactorDef, 0);
    assert.equal(merged.reportLimit, 7, 'numeric strings are accepted');
    assert.equal(merged.historyDays, 7, 'clamped to the minimum');
    assert.equal(merged.autoScan, true, 'form values are understood');
    assert.equal('evil' in merged, false, 'unknown keys are never stored');
    assert.equal('tooltipss' in merged, false);
});

test('settings: writing and resetting goes through the store', function () {
    const store = makeStore();
    settings.write(store, { tradeRate: [2.5, 1.5, 1], standardUnitBase: 0, notifyHostile: false, debrisFactor: 70 });
    const stored = store.data().settings;
    assert.deepEqual(stored.tradeRate, [2.5, 1.5, 1]);
    assert.equal(stored.standardUnitBase, 0);
    assert.equal(stored.notifyHostile, false);
    assert.equal(stored.debrisFactor, 70);

    settings.reset(store);
    assert.deepEqual(settings.read(store).tradeRate, [3, 2, 1]);
    assert.equal(settings.read(store).debrisFactor, 30);
});

test('settings: export and import round trip', function () {
    const store = makeStore();
    settings.write(store, { tradeRate: [3, 2, 1], standardUnitBase: 1, tooltips: false });
    const exported = settings.exportJson(settings.read(store));
    assert.ok(exported.indexOf('standardUnitBase') !== -1);

    const imported = settings.importJson(exported);
    assert.equal(imported.standardUnitBase, 1);
    assert.equal(imported.tooltips, false);

    // Garbage and unrelated documents are refused instead of breaking the store.
    assert.equal(settings.importJson('not json'), null);
    assert.equal(settings.importJson('123'), null);
    assert.equal(settings.importJson('[1,2,3]'), null);
    assert.equal(settings.importJson('{"settings": null}'), null);
    assert.equal(settings.importJson('{"other": 1}'), null, 'an unknown document is refused, not applied');
    assert.equal(settings.importJson('{"autoScan": false}').autoScan, false, 'a bare settings object is accepted');
    assert.equal(settings.importJson('{"autoScan": false}').tradeRate[0], 3, 'missing keys fall back to the defaults');
});

test('panel: the settings form carries every control', function () {
    const store = makeStore();
    const html = panel.settingsSection(store.data(), { i18n: i18n.createI18n('ru') });
    for (const key of [
        'tradeRate', 'standardUnitBase', 'debrisFactor', 'debrisFactorDef', 'deuteriumInDebris',
        'historyDays', 'autoScan', 'reportLimit', 'tooltips', 'highlightGalaxy', 'highlightFavorites',
        'notifyHostile',
    ]) {
        assert.ok(html.indexOf('data-ogi-setting="' + key + '"') !== -1, 'missing control ' + key);
    }
    assert.ok(html.indexOf('data-ogi-rate="0"') !== -1, 'the trade rate has three fields');
    assert.ok(html.indexOf('data-ogi-rate="2"') !== -1);
    assert.ok(html.indexOf('Металл') === -1 && html.indexOf('металл') !== -1, 'the labels are localized');
});

test('panel: the settings tab and the standalone panel render', function () {
    const store = makeStore();
    const t = i18n.createI18n('en');
    const tabbed = panel.renderAnalyzer(store.data(), { i18n: t, tab: 'settings', lang: 'en' });
    assert.ok(tabbed.html.indexOf('data-ogi-setting="autoScan"') !== -1);
    assert.ok(tabbed.html.indexOf('data-ogi-action="save"') !== -1);
    assert.ok(tabbed.html.indexOf(t('TAB_SETTINGS')) !== -1);

    const standalone = panel.settingsPanel(store.data(), { i18n: t, status: 'ok' });
    assert.ok(standalone.indexOf('id="ogi-settings"') !== -1);
    assert.ok(standalone.indexOf('data-ogi-action="export"') !== -1);
    assert.ok(standalone.indexOf('data-ogi-action="reset"') !== -1);
});

test('settings: the buy-in form values match what the pages write', function () {
    // The Options page and the analyzer tab both use data-ogi-setting attributes;
    // this is the contract between the markup and the wiring.
    const store = makeStore();
    const html = panel.settingsSection(store.data(), { i18n: i18n.createI18n('en') });
    const keys = [];
    const re = /data-ogi-setting="([a-zA-Z]+)"/g;
    let match;
    while ((match = re.exec(html)) !== null) keys.push(match[1]);
    const unique = Array.from(new Set(keys));
    for (const key of unique) {
        assert.ok(key in settings.DEFAULTS, 'unknown setting in the markup: ' + key);
    }
});
