/**
 * Tests of the build pages: the row parser, the resource plan and the controls the
 * original extension shows next to every building, research, ship and defence unit.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const buildpage = require('../src/parsers/buildpage.js');
const plan = require('../src/analyze/plan.js');
const panel = require('../src/lib/panel.js');
const storeModule = require('../src/lib/store.js');
const i18n = require('../src/lib/i18n.js');
const locaData = require('../src/data/loca.js');
const unitsData = require('../src/data/units.js');
const templates = require('../src/lib/templates.js');

const loca = templates.createLoca(locaData);

/**
 * Parse a golden build page.
 *
 * @param {string} lang Language.
 * @param {string} file File name.
 * @param {string} [overrides] Extra options.
 * @returns {object} Parsed page.
 */
function parse(lang, file, overrides) {
    return buildpage.parseBuildPage(helpers.readGolden(lang, file), Object.assign({
        loca: loca,
        lang: lang,
        unitsData: unitsData,
    }, overrides || {}));
}

test('build page: the rows of every language and list', function () {
    const pages = [
        ['buildings_research_p0.html', 'research', 9, 106],
        ['buildings_shipyard_p0.html', 'ship', 14, 202],
        ['buildings_defense_p0.html', 'defence', 8, 401],
    ];
    for (const lang of ['ru', 'en', 'de']) {
        for (const [file, kind, count, firstGid] of pages) {
            const page = parse(lang, file);
            assert.equal(page.rows.length, count, lang + ' ' + file + ': rows');
            const first = page.rows[0];
            assert.equal(first.gid, firstGid, lang + ' ' + file + ': first gid');
            assert.equal(first.kind, kind, lang + ' ' + file + ': kind');
            assert.ok(first.name && first.name.length > 1, lang + ' ' + file + ': name');
            assert.ok(first.cost && first.cost[700] > 0, lang + ' ' + file + ': cost');
            assert.ok(!/^\s*:/.test(first.duration || ''), lang + ' ' + file + ': duration is trimmed');
        }
    }
});

test('build page: the current level and the stock are read', function () {
    const research = parse('ru', 'buildings_research_p0.html');
    const espionage = research.rows[0];
    assert.equal(espionage.gid, 106);
    assert.equal(espionage.level, 5, 'the level from the localized BUILD_LEVEL template');
    assert.equal(espionage.name, 'Шпионаж');
    assert.equal(espionage.cost[700], 6400, 'the price line of the page');
    assert.equal(espionage.cost[701], 32000);
    assert.equal(espionage.cost[702], 6400);

    const english = parse('en', 'buildings_research_p0.html');
    assert.equal(english.rows[0].level, 5, 'the level is recognized in English too');
    assert.equal(english.rows[0].name, 'Espionage Technology');
    assert.equal(english.rows[0].cost[700], 6400);

    const ships = parse('ru', 'buildings_shipyard_p0.html');
    assert.equal(ships.rows[0].level, null, 'ships have no level');
    assert.equal(ships.rows[0].stock, 10, 'the stock from BUILD_SHIPYARD_UNITS');

    const defence = parse('ru', 'buildings_defense_p0.html');
    assert.equal(defence.rows[0].gid, 401);
    assert.equal(defence.rows[0].stock, 5);
    assert.equal(defence.rows[0].kind, 'defence');
});

test('build page: the mode is read from the form', function () {
    assert.equal(buildpage.modeOf(helpers.readGolden('ru', 'buildings_shipyard_p0.html')), 'ship');
    assert.equal(buildpage.modeOf(helpers.readGolden('ru', 'buildings_defense_p0.html')), 'defence');
    // The research list carries no mode in its form, so the kind of the rows decides.
    assert.equal(parse('ru', 'buildings_research_p0.html').rows[0].kind, 'research');
});

test('plan: the cost of a target level and of a batch of ships', function () {
    // Espionage 5 -> 7: levels 6 and 7 of the object tables.
    const research = plan.entryCost({ gid: 106, kind: 'research', from: 5, to: 7 }, unitsData);
    assert.ok(research.metal > 0 && research.crystal > 0 && research.deuterium > 0);
    assert.equal(plan.entryCost({ gid: 106, kind: 'research', from: 5, to: 5 }, unitsData).total, 0,
        'no step, no cost');

    // Ten small cargo ships: 2000 metal and 2000 crystal each.
    const ships = plan.entryCost({ gid: 202, kind: 'ship', from: 10, to: 20 }, unitsData);
    assert.equal(ships.metal, 20000);
    assert.equal(ships.crystal, 20000);
    assert.equal(ships.total, 40000);
});

test('plan: totals, deficits and the summary', function () {
    const stored = {
        106: { from: 5, to: 7, kind: 'research', name: 'Шпионаж', locked: true },
        202: { from: 10, to: 20, kind: 'ship', name: 'Малый транспорт', locked: true },
        1: { from: 5, to: 6, kind: 'building', name: 'Рудник', locked: false },
    };
    const entries = plan.planEntries(stored, { unitsData: unitsData });
    assert.equal(entries.length, 2, 'only the locked objects are in the summary');
    assert.ok(entries[0].cost.total >= entries[1].cost.total, 'the most expensive first');

    const totals = plan.planTotals(entries);
    assert.equal(totals.metal, entries[0].cost.metal + entries[1].cost.metal);

    const deficits = plan.planDeficits(totals, { 700: 10000, 701: 50000, 702: 0, 703: 0 });
    assert.equal(deficits.metal, Math.max(0, totals.metal - 10000));
    assert.equal(deficits.crystal, Math.max(0, totals.crystal - 50000));
    assert.equal(deficits.complete, false);
    assert.equal(deficits.missing, deficits.metal + deficits.crystal + deficits.deuterium,
        'the missing total adds up the three buildable resources');

    const summary = plan.planSummary(stored, { unitsData: unitsData, stock: { 700: 1e9, 701: 1e9, 702: 1e9, 703: 1e9 } });
    assert.equal(summary.entries.length, 2);
    assert.equal(summary.locked, 2);
    assert.equal(summary.deficits.complete, true, 'plenty of resources');

    assert.equal(plan.clampTarget(5, -10), 0, 'the target never goes below zero');
    assert.equal(plan.clampTarget(999, 10), 1000, 'and never above the maximum level');
});

test('plan: the store keeps the plan per universe', function () {
    const storage = storeModule.memoryStorage();
    const store = storeModule.createStore({ universe: 'Uni1', host: 'oldogame.ru', storage: storage });
    store.load();
    assert.deepEqual(store.getPlan(), {});

    store.setPlanEntry(106, { from: 5, to: 7, kind: 'research', name: 'Шпионаж', locked: true });
    store.setPlanEntry(202, { from: 10, to: 20, kind: 'ship', name: 'Малый транспорт', locked: true });
    assert.equal(Object.keys(store.getPlan()).length, 2);

    store.setPlanEntry(106, { to: 8 });
    assert.equal(store.getPlan()['106'].to, 8, 'a later step keeps the other fields');
    assert.equal(store.getPlan()['106'].locked, true);

    // A different universe starts with an empty plan.
    const other = storeModule.createStore({ universe: 'Uni2', host: 'oldogame.ru', storage: storage });
    other.load();
    assert.deepEqual(other.getPlan(), {});

    // And the plan survives a reload of the same universe.
    const reloaded = storeModule.createStore({ universe: 'Uni1', host: 'oldogame.ru', storage: storage });
    reloaded.load();
    assert.equal(reloaded.getPlan()['106'].to, 8);
    reloaded.unlockPlan();
    assert.equal(reloaded.getPlan()['106'].locked, false);
    assert.equal(reloaded.getPlan()['106'].to, 8, 'unlocking keeps the targets');
    reloaded.clearPlan();
    assert.deepEqual(reloaded.getPlan(), {});
    store.removePlanEntry(202);
    assert.equal(store.getPlan()['202'], undefined);
});

test('plan: the per-row control shows the target, the cost, the deficit and the lock', function () {
    const t = i18n.createI18n('ru');
    const row = { gid: 1, name: 'Рудник по добыче металла', kind: 'building', level: 5, stock: null };
    const html = panel.buildControl(row, {
        i18n: t,
        unitsData: unitsData,
        plan: { 1: { from: 5, to: 7, kind: 'building', name: row.name, locked: true } },
        stock: { 700: 100, 701: 100, 702: 0, 703: 0 },
    });

    assert.ok(html.indexOf('data-ogi-step="1:-1"') !== -1, 'the stepper down');
    assert.ok(html.indexOf('data-ogi-step="1:10"') !== -1, 'the stepper up');
    assert.ok(html.indexOf('class="ogi-step-target">7<') !== -1, 'the chosen target');
    assert.ok(html.indexOf('data-ogi-lock="1"') !== -1, 'the padlock');
    assert.ok(html.indexOf(t('PLAN_UNLOCK')) !== -1, 'a locked object offers to unlock');
    assert.ok(html.indexOf(t('PLAN_MISSING')) !== -1, 'the deficit is shown');
    assert.ok(html.indexOf('ogi-icon') !== -1, 'the icons are rendered');

    // An untouched object shows the price of the next level instead.
    const plain = panel.buildControl(row, { i18n: t, unitsData: unitsData, plan: {}, stock: {} });
    assert.ok(plain.indexOf(t('PLAN_NEXT_LEVEL')) !== -1);
    assert.ok(plain.indexOf(t('PLAN_LOCK')) !== -1);
    assert.equal(plain.indexOf(t('PLAN_MISSING')), -1);
});

test('plan: the summary panel lists the locked objects and the totals', function () {
    const t = i18n.createI18n('ru');
    const data = {
        plan: {
            106: { from: 5, to: 7, kind: 'research', name: 'Шпионаж', locked: true },
            202: { from: 10, to: 20, kind: 'ship', name: 'Малый транспорт', locked: true },
        },
        meta: { stock: { 700: 1000, 701: 1000, 702: 0, 703: 0 } },
    };
    const html = panel.planSection(data, { i18n: t, unitsData: unitsData });
    assert.ok(html.indexOf('Шпионаж') !== -1);
    assert.ok(html.indexOf('Малый транспорт') !== -1);
    assert.ok(html.indexOf(t('PLAN_TOTAL')) !== -1);
    assert.ok(html.indexOf(t('PLAN_STOCK')) !== -1);
    assert.ok(html.indexOf(t('PLAN_MISSING')) !== -1);
    assert.ok(html.indexOf(t('PLAN_INCOMPLETE', { N: '1' }).slice(0, 10)) !== -1, 'the status line is there');
    assert.ok(html.indexOf('5 → <b>7</b>') !== -1, 'the level range');

    const empty = panel.planSection({ plan: {}, meta: {} }, { i18n: t, unitsData: unitsData });
    assert.ok(empty.indexOf(t('PLAN_EMPTY')) !== -1);

    // Everything is affordable: the panel says so.
    const rich = panel.planSection({
        plan: { 106: { from: 5, to: 6, kind: 'research', name: 'Шпионаж', locked: true } },
        meta: { stock: { 700: 1e9, 701: 1e9, 702: 1e9, 703: 1e9 } },
    }, { i18n: t, unitsData: unitsData });
    assert.ok(rich.indexOf(t('PLAN_COMPLETE')) !== -1);
});

test('plan: the costs of the plan match the price line of the page', function () {
    // The first research row displays what level 6 costs; the plan must compute the
    // very same numbers from the object tables.
    const page = parse('ru', 'buildings_research_p0.html');
    const row = page.rows[0];
    const cost = plan.entryCost({ gid: row.gid, kind: row.kind, from: row.level, to: row.level + 1 }, unitsData);
    assert.equal(cost.metal, row.cost[700]);
    assert.equal(cost.crystal, row.cost[701]);
    assert.equal(cost.deuterium, row.cost[702]);
});
