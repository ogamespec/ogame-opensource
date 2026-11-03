/**
 * Tests of the needs (resource lock) calculator.
 *
 * The 0.84 cost formula and the rebuild factors are checked against the generated
 * object tables (src/data/units.js), which are extracted from game/core/techs.php:
 * the metal mine really costs 60/15 and grows by 1.5, the espionage technology
 * costs 200/1000/200 and grows by 2, ships have flat costs.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const unitsData = require('../src/data/units.js');
const needs = require('../src/analyze/needs.js');

const GID_METAL_MINE = 1;
const GID_ESPIONAGE = 106;
const GID_SMALL_CARGO = 202;
const GID_PROBE = 210;

test('levelCost: 0.84 growth formula on real objects', function () {
    // Metal mine: 60 metal / 15 crystal, factor 1.5, floored at every level.
    assert.deepEqual(needs.levelCost(GID_METAL_MINE, 1), {
        metal: 60, crystal: 15, deuterium: 0, energy: 0, total: 75,
    });
    assert.deepEqual(needs.levelCost(GID_METAL_MINE, 2), {
        metal: 90, crystal: 22, deuterium: 0, energy: 0, total: 112,
    });
    assert.deepEqual(needs.levelCost(GID_METAL_MINE, 3), {
        metal: 135, crystal: 33, deuterium: 0, energy: 0, total: 168,
    });

    // Espionage technology: 200 / 1000 / 200, factor 2.
    assert.deepEqual(needs.levelCost(GID_ESPIONAGE, 1), {
        metal: 200, crystal: 1000, deuterium: 200, energy: 0, total: 1400,
    });
    assert.deepEqual(needs.levelCost(GID_ESPIONAGE, 2), {
        metal: 400, crystal: 2000, deuterium: 400, energy: 0, total: 2800,
    });
    assert.deepEqual(needs.levelCost(GID_ESPIONAGE, 3), {
        metal: 800, crystal: 4000, deuterium: 800, energy: 0, total: 5600,
    });
});

test('levelCost: ships and defence keep their flat cost', function () {
    // Espionage probe: 1000 crystal, no rebuild factor.
    assert.deepEqual(needs.levelCost(GID_PROBE, 1), {
        metal: 0, crystal: 1000, deuterium: 0, energy: 0, total: 1000,
    });
    assert.deepEqual(needs.levelCost(GID_PROBE, 5), {
        metal: 0, crystal: 1000, deuterium: 0, energy: 0, total: 1000,
    });
    assert.deepEqual(needs.levelCost(GID_SMALL_CARGO, 3), {
        metal: 2000, crystal: 2000, deuterium: 0, energy: 0, total: 4000,
    });
});

test('levelCost: the object tables can be replaced and absurd input is safe', function () {
    const custom = {
        costs: { 900: { metal: 10, crystal: 0, deuterium: 0, energy: 0 } },
        factor: { 900: 3 },
    };
    assert.deepEqual(needs.levelCost(900, 1, custom), {
        metal: 10, crystal: 0, deuterium: 0, energy: 0, total: 10,
    });
    // 3 ^ 999 overflows a double: the guard keeps the value finite.
    const huge = needs.levelCost(900, 100000, custom);
    assert.ok(Number.isFinite(huge.metal), 'huge levels must stay finite');
    assert.equal(huge.metal, Number.MAX_SAFE_INTEGER);
    assert.ok(Number.isFinite(huge.total));
});

test('totalCost: sums a range of levels', function () {
    // The range is "the levels above fromLevel up to toLevel", so 1 -> 3 prices
    // the levels 2 and 3: 90/22 + 135/33.
    assert.deepEqual(needs.totalCost(GID_METAL_MINE, 1, 3), {
        metal: 225, crystal: 55, deuterium: 0, energy: 0, total: 280,
    });
    // A new object (from level 0) prices the levels 1..3.
    assert.deepEqual(needs.totalCost(GID_METAL_MINE, 0, 3), {
        metal: 285, crystal: 70, deuterium: 0, energy: 0, total: 355,
    });
    // A new object (from level 0) prices the first level only.
    assert.deepEqual(needs.totalCost(GID_METAL_MINE, 0, 1), {
        metal: 60, crystal: 15, deuterium: 0, energy: 0, total: 75,
    });
    // One level in the middle of the range.
    assert.deepEqual(needs.totalCost(GID_METAL_MINE, 2, 3), {
        metal: 135, crystal: 33, deuterium: 0, energy: 0, total: 168,
    });
    // Nothing to build.
    assert.deepEqual(needs.totalCost(GID_METAL_MINE, 3, 3), {
        metal: 0, crystal: 0, deuterium: 0, energy: 0, total: 0,
    });
    assert.deepEqual(needs.totalCost(GID_METAL_MINE, 5, 2), {
        metal: 0, crystal: 0, deuterium: 0, energy: 0, total: 0,
    });
});

test('needs: enough stock means nothing is missing', function () {
    const cost = needs.levelCost(GID_METAL_MINE, 3);
    const result = needs.needs({ metal: 1000, crystal: 1000, deuterium: 1000, energy: 100 }, cost);

    assert.equal(result.affordable, true);
    assert.deepEqual([result.metal, result.crystal, result.deuterium, result.energy], [0, 0, 0, 0]);
    assert.equal(result.total, 0);
    assert.equal(result.missingTotal, 0);
    assert.equal(result.waitSeconds, 0, 'nothing to wait for');
    // Production is irrelevant when the cost is already covered.
    const withProduction = needs.needs(
        { metal: 1000, crystal: 1000, deuterium: 1000, energy: 100 },
        cost,
        { production: { metal: 3600, crystal: 3600 } }
    );
    assert.equal(withProduction.waitSeconds, 0);
});

test('needs: partial stock reports the difference', function () {
    const cost = needs.levelCost(GID_METAL_MINE, 3);
    const result = needs.needs({ metal: 100, crystal: 10 }, cost);

    assert.equal(result.affordable, false);
    assert.equal(result.metal, 35, '135 - 100');
    assert.equal(result.crystal, 23, '33 - 10');
    assert.equal(result.total, 58);
    assert.equal(result.missingTotal, result.total);
    assert.equal(result.waitSeconds, null, 'no production rate given');
    assert.equal(result.waitKnown, false);
    assert.deepEqual(result.stock, { metal: 100, crystal: 10, deuterium: 0, energy: 0 });
});

test('needs: the reserve is kept back from the usable stock', function () {
    const cost = needs.levelCost(GID_METAL_MINE, 3);
    const result = needs.needs({ metal: 200, crystal: 100 }, cost, { reserve: 100 });

    assert.deepEqual(result.stock, { metal: 100, crystal: 0, deuterium: 0, energy: 0 });
    assert.equal(result.metal, 35, '135 - (200 - 100)');
    assert.equal(result.crystal, 33, 'nothing usable was left');
    assert.equal(result.total, 68);

    // A reserve larger than the stock never turns into negative shortages.
    const overReserved = needs.needs({ metal: 200 }, cost, { reserve: 5000 });
    assert.equal(overReserved.metal, 135);
    assert.deepEqual(overReserved.stock, { metal: 0, crystal: 0, deuterium: 0, energy: 0 });
});

test('needs: the waiting time comes from the production', function () {
    const cost = needs.levelCost(GID_METAL_MINE, 3); // 135 metal, 33 crystal
    const production = { metal: 3600, crystal: 3600 };  // one unit per second each

    const result = needs.needs({}, cost, { production: production });
    assert.equal(result.waitSeconds, 135, 'the slowest resource decides');
    assert.equal(result.waitKnown, true);

    // 100 seconds of production are credited before the shortage is measured.
    const projected = needs.needs({}, cost, { production: production, seconds: 100 });
    assert.equal(projected.metal, 35, '135 - 100 produced');
    assert.equal(projected.crystal, 0);
    assert.equal(projected.waitSeconds, 35);

    // Something is missing and nothing produces it: it never arrives.
    const stuck = needs.needs({}, cost, { production: { metal: 3600 } });
    assert.equal(stuck.waitSeconds, Infinity);

    // Energy is reported as a requirement but does not inflate the resource total.
    const energy = needs.needs({}, { metal: 0, crystal: 0, deuterium: 0, energy: 500 });
    assert.equal(energy.energy, 500);
    assert.equal(energy.total, 0);
    assert.equal(energy.affordable, false);
});

test('queueNeeds: a queue of upgrades is summed up', function () {
    // Metal mine 1 -> 2 (90 / 22) plus espionage technology level 1 (200 / 1000 / 200).
    const queue = [
        { gid: GID_METAL_MINE, fromLevel: 1, toLevel: 2 },
        { gid: GID_ESPIONAGE, level: 1 },
    ];
    const result = needs.queueNeeds({ metal: 1000, crystal: 2000, deuterium: 500 }, queue, unitsData);

    assert.deepEqual(result.cost, {
        metal: 290, crystal: 1022, deuterium: 200, energy: 0, total: 1512,
    });
    assert.equal(result.needs.affordable, true);
    assert.equal(result.needs.total, 0);

    // The same queue with a shortage of crystal only.
    const short = needs.queueNeeds({ metal: 290, crystal: 1000, deuterium: 200 }, queue, unitsData, {
        reserve: 0,
        production: { crystal: 3600 },
    });
    assert.equal(short.needs.crystal, 22);
    assert.equal(short.needs.waitSeconds, 22);
    assert.equal(short.needs.affordable, false);

    // Unknown objects and invalid items contribute nothing.
    const dirty = needs.queueNeeds({}, [
        { gid: 999999, fromLevel: 0, toLevel: 10 },
        null,
        { level: 3 },
        { gid: GID_METAL_MINE, fromLevel: 0, toLevel: 1 },
    ], unitsData);
    assert.deepEqual(dirty.cost, { metal: 60, crystal: 15, deuterium: 0, energy: 0, total: 75 });
    assert.equal(dirty.needs.total, 75);
});

test('unknown and invalid input produces zeros instead of NaN', function () {
    const empty = { metal: 0, crystal: 0, deuterium: 0, energy: 0, total: 0 };
    assert.deepEqual(needs.levelCost(999999, 3), empty);
    assert.deepEqual(needs.levelCost(GID_METAL_MINE, 0), empty);
    assert.deepEqual(needs.levelCost(GID_METAL_MINE, -5), empty);
    assert.deepEqual(needs.levelCost(GID_METAL_MINE, NaN), empty);
    assert.deepEqual(needs.totalCost(999999, 0, 5), empty);
    assert.deepEqual(needs.queueNeeds({}, [], unitsData).cost, empty);

    const result = needs.needs(undefined, undefined, undefined);
    assert.equal(result.affordable, true);
    assert.equal(result.total, 0);
    for (const field of ['metal', 'crystal', 'deuterium', 'energy', 'total', 'missingTotal']) {
        assert.ok(Number.isFinite(result[field]), field + ' must be a finite number');
    }

    const broken = needs.needs({ metal: 'abc' }, { metal: undefined }, { reserve: 'x' });
    assert.ok(Number.isFinite(broken.metal));
    assert.equal(broken.metal, 0);
});
