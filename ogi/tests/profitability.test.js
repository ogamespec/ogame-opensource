/**
 * Tests of the profitability screen: the production formulas ported from the engine,
 * the production page parser, the calibration against the numbers the server shows and
 * the payback ranking.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const production = require('../src/analyze/production.js');
const payback = require('../src/analyze/payback.js');
const prodpage = require('../src/parsers/prodpage.js');
const panel = require('../src/lib/panel.js');
const storeModule = require('../src/lib/store.js');
const i18n = require('../src/lib/i18n.js');
const locaData = require('../src/data/loca.js');
const unitsData = require('../src/data/units.js');
const templates = require('../src/lib/templates.js');

const loca = templates.createLoca(locaData);

/**
 * Parse a golden production page.
 *
 * @param {string} lang Language.
 * @returns {object} Parsed page.
 */
function parse(lang) {
    return prodpage.parseProductionPage(helpers.readGolden(lang, 'resources_p0.html'), {
        loca: loca,
        lang: lang,
    });
}

test('production: the formulas of the engine', function () {
    // metal mine: floor(30 · L · 1.1^L) × speed; level 5 = 241
    assert.equal(production.production(1, 5).amount, 241);
    assert.equal(production.production(1, 5).energy, -81);
    // crystal mine: floor(20 · L · 1.1^L); level 3 = 79
    assert.equal(production.production(2, 3).amount, 79);
    // solar plant: floor(20 · L · 1.1^L); level 3 = 79
    assert.equal(production.production(4, 3).amount, 79);
    // deuterium depends on the temperature: 1.28 − 0.002 · (temp + 40)
    assert.equal(production.deutFactor(-20), 1.24);
    assert.equal(production.production(3, 2, { temp: 60, officer: true }).amount, 28,
        'the number the server shows for the synthesizer of the test planet');
    // fusion: floor(30 · L · (1.05 + energyTech · 0.01)^L)
    assert.equal(production.production(12, 1, { energyTech: 6 }).amount, 33);
    // officers: geologist +10 % on mines, engineer +10 % on energy
    assert.equal(production.production(1, 5, { officer: true }).amount, 265);
    assert.equal(production.production(4, 3, { engineer: true }).amount, 87);
    // no level, no production
    assert.equal(production.production(1, 0).amount, 0);
    assert.equal(production.production(12, 0).amount, 0);
});

test('production: the gain of one level', function () {
    const gain = production.gain(1, 5);
    assert.equal(gain.amount, 318 - 241);
    assert.equal(gain.energy, -107 + 81);
    assert.equal(gain.resource, 700);
});

test('production: the energy technology implied by a fusion plant', function () {
    // The engine floors the amount, so more than one technology reproduces the same
    // number; what matters is that the derived one reproduces it.
    const implied = production.implyEnergyTech(36, 1, { engineer: true });
    assert.ok(implied >= 4 && implied <= 8, "a plausible energy technology, got " + implied);
    assert.equal(production.production(12, 1, { energyTech: implied, engineer: true }).amount, 36,
        "the derived technology reproduces the number of the page");
    // Without the engineer bonus the same number means a higher technology.
    assert.ok(production.implyEnergyTech(36, 1, { engineer: false }) > 6);
    assert.equal(production.implyEnergyTech(0, 1), 0);
});

test('production page: the rows of every language', function () {
    for (const lang of ['ru', 'en', 'de']) {
        const page = parse(lang);
        assert.equal(page.rows.length, 5, lang + ': five buildings with a level');
        const metal = page.rows[0];
        assert.equal(metal.gid, 1, lang + ': metal mine');
        assert.equal(metal.level, 5, lang + ': level');
        assert.equal(metal.production[700], 265, lang + ': the production the server shows');
        assert.equal(page.rows[1].gid, 2);
        assert.equal(page.rows[1].production[701], 87);
        assert.equal(page.rows[2].production[702], 28, lang + ': deuterium');
        assert.equal(page.rows[3].production[703], 87, lang + ': solar plant energy');
        assert.equal(page.rows[4].production[703], 36, lang + ': fusion energy');
        assert.equal(page.planet, 'Home');
        assert.equal(page.officers.geologist, true, lang + ': the officer icons are read');
        assert.equal(page.officers.engineer, true);
    }
});

test('production page: the calibration reproduces the numbers of the server', function () {
    const page = parse('ru');
    const context = {
        factors: page.factors,
        energyTech: page.energyTech,
        engineer: page.officers.engineer,
    };
    for (const row of page.rows) {
        const resource = production.production(row.gid, row.level).resource;
        const shown = row.production[resource];
        const computed = payback.at(row.gid, row.level, context).amount;
        assert.ok(Math.abs(shown - computed) <= 1,
            'gid ' + row.gid + ': the server shows ' + shown + ', the extension computes ' + computed);
    }
    assert.ok(page.energyTech >= 4 && page.energyTech <= 8, 'the fusion plant reveals the energy technology');
    assert.equal(payback.at(12, 1, context).amount, 36, 'and it reproduces the number of the page');
});

test('payback: the ranking puts the fastest payback first', function () {
    const page = parse('ru');
    const levels = {};
    const names = {};
    for (const row of page.rows) {
        levels[row.gid] = row.level;
        names[row.gid] = row.name;
    }
    const rows = payback.recommendations(levels, {
        factors: page.factors,
        energyTech: page.energyTech,
        engineer: page.officers.engineer,
        unitsData: unitsData,
        names: names,
    });

    assert.equal(rows.length, 3, 'the three mines: power plants produce nothing tradeable');
    assert.deepEqual(rows.map(function (row) { return row.gid; }), [1, 2, 3], 'metal, crystal, deuterium');
    assert.ok(rows[0].payback < rows[1].payback && rows[1].payback < rows[2].payback);
    assert.equal(rows[0].level, 5);
    assert.equal(rows[0].nextLevel, 6);
    assert.equal(rows[0].cost.metal, 455, 'the price of level 6 from the object tables');
    assert.ok(rows[0].gain.amount > 0, 'the level adds production');
    assert.ok(rows[0].energyBalance < 0, 'and it needs more energy');

    // The trade rate changes the order of the ranking, not the production.
    const deutHeavy = payback.recommendations(levels, {
        rate: [1, 1, 4],
        factors: page.factors,
        energyTech: page.energyTech,
        unitsData: unitsData,
    });
    const better = payback.recommendations(levels, {
        rate: [3, 2, 1],
        factors: page.factors,
        energyTech: page.energyTech,
        unitsData: unitsData,
    });
    assert.equal(deutHeavy[0].gid, 1, 'metal is still the cheapest to improve');
    assert.notEqual(deutHeavy[2].payback, better[2].payback, 'the rate changes the payback of deuterium');

    // The filter and the value function.
    const short = payback.recommendations(levels, {
        factors: page.factors,
        energyTech: page.energyTech,
        unitsData: unitsData,
        filter: 0.3,
    });
    assert.deepEqual(short.map(function (row) { return row.gid; }), [1], 'only the metal mine pays back that fast');
    assert.equal(payback.value({ 700: 1000, 701: 1000, 702: 1000 }, [3, 2, 1]), 6000);
    assert.equal(payback.value({ 700: 0, 701: 0, 702: 0 }), 0);
});

test('payback: the store keeps the production snapshot', function () {
    const store = storeModule.createStore({ universe: 'Uni1', host: 'localhost', storage: storeModule.memoryStorage() });
    store.load();
    assert.equal(store.getProduction(), null);

    const page = parse('ru');
    const levels = {};
    for (const row of page.rows) levels[row.gid] = row.level;
    store.setProduction({
        levels: levels,
        factors: page.factors,
        energyTech: page.energyTech,
        officers: page.officers,
        planet: page.planet,
    });

    const saved = store.getProduction();
    assert.equal(saved.levels[1], 5);
    assert.ok(saved.energyTech >= 4 && saved.energyTech <= 8, "the implied energy technology");
    assert.equal(payback.at(12, 1, {
        factors: saved.factors,
        energyTech: saved.energyTech,
        engineer: true,
    }).amount, 36, "the snapshot predicts the fusion plant correctly");
    assert.equal(saved.planet, 'Home');
    assert.ok(saved.factors[1] > 1, 'the officer bonus is part of the recovered factor');

    // And a second store of the same universe reads the same storage.
    const storage = storeModule.memoryStorage();
    const first = storeModule.createStore({ universe: 'Uni2', host: 'localhost', storage: storage });
    first.load();
    first.setProduction({ levels: { 1: 7 }, factors: {}, energyTech: 0, officers: {}, planet: 'Home' });
    const reloaded = storeModule.createStore({ universe: 'Uni2', host: 'localhost', storage: storage });
    reloaded.load();
    assert.equal(reloaded.getProduction().levels[1], 7, 'the snapshot survives a reload');
});

test('payback: the panel renders the tiles and the explanation', function () {
    const t = i18n.createI18n('ru');
    const page = parse('ru');
    const levels = {};
    const names = {};
    for (const row of page.rows) {
        levels[row.gid] = row.level;
        names[row.gid] = row.name;
    }
    const html = panel.paybackSection({}, {
        i18n: t,
        unitsData: unitsData,
        levels: levels,
        names: names,
        factors: page.factors,
        energyTech: page.energyTech,
        engineer: page.officers.engineer,
        page: t('PAY_PLANET', { T: 'Home' }),
    });

    assert.ok(html.indexOf('class="ogi-tiles"') !== -1, 'the tile grid is there');
    assert.ok((html.match(/<img src="gebaeude\//g) || []).length >= 3, 'the game images are used');
    assert.ok(html.indexOf(t('PAY_PAYBACK')) !== -1);
    assert.ok(html.indexOf(t('PAY_RATE')) !== -1 && html.indexOf('×3') !== -1, 'the trade rate is shown');
    assert.ok(html.indexOf(t('PAY_NOTE')) !== -1, 'the assumptions are explained');
    assert.ok(html.indexOf(t('PAY_POWER')) !== -1, 'the power plants are listed apart');
    assert.ok(html.indexOf('Рудник по добыче металла') !== -1);

    // Without any data the panel says what to do.
    const empty = panel.paybackSection({}, { i18n: t, unitsData: unitsData, levels: {} });
    assert.ok(empty.indexOf(t('PAY_NO_DATA')) !== -1);

    // With a filter that excludes everything.
    const filtered = panel.paybackSection({}, {
        i18n: t, unitsData: unitsData, levels: levels, factors: page.factors,
        energyTech: page.energyTech, filter: 0.01,
    });
    assert.ok(filtered.indexOf(t('PAY_NO_RESULT')) !== -1);
});
