/**
 * Regression tests for databases written by older versions of the extension.
 *
 * The extension grew collections over time (the statistics history, favourites,
 * notes, the empire snapshot). A database made before those existed caused a
 * "Cannot read properties of undefined" crash on the statistics page of a live
 * server, so every load now goes through a migration that creates what is missing
 * and drops what is unusable.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const storeModule = require('../src/lib/store.js');
const collect = require('../src/lib/collect.js');
const analyze = require('../src/analyze/index.js');
const locaData = require('../src/data/loca.js');
const templates = require('../src/lib/templates.js');

const loca = templates.createLoca(locaData);

/**
 * Store a legacy database and open it.
 *
 * @param {object} legacy Database content.
 * @returns {object} Store.
 */
function openLegacy(legacy) {
    const storage = storeModule.memoryStorage();
    const key = storeModule.storageKey('Uni1', 'oldogame.ru');
    storage.setItem(key, typeof legacy === 'string' ? legacy : JSON.stringify(legacy));
    const store = storeModule.createStore({ universe: 'Uni1', host: 'oldogame.ru', storage: storage });
    store.load();
    return store;
}

test('migration: a database without the newer collections still works', function () {
    const store = openLegacy({
        version: 1,
        universe: 'Uni1',
        spies: [{ date: 1, coords: { g: 1, s: 2, p: 3 } }],
        players: { Alice: 'broken', Bob: { name: 'Bob', points: 10 } },
        planets: { '1:2:3': { coords: { g: 1, s: 2, p: 3 } } },
        activity: { '1:2:3': 'broken', '1:2:4': [{ t: 1, marker: '*' }] },
        settings: { tradeRate: [3, 2, 1] },
    });

    const data = store.data();
    // Missing collections exist now.
    assert.deepEqual(data.stats, {});
    assert.deepEqual(data.favorites, { planets: {}, players: {} });
    assert.deepEqual(data.notes, {});
    assert.equal(data.empire, null);
    assert.deepEqual(data.messages, {});
    assert.deepEqual(data.trades, []);
    // Unusable entries are dropped, usable ones survive.
    assert.equal('Alice' in data.players, false);
    assert.equal(data.players.Bob.points, 10);
    assert.deepEqual(data.planets['1:2:3'].names, []);
    assert.equal('1:2:3' in data.activity, false, 'a malformed activity list is dropped');
    assert.equal(data.activity['1:2:4'].length, 1, 'a well-formed activity list survives');
    assert.equal(data.spies.length, 1, 'the collected reports survive');
    assert.deepEqual(data.settings.tradeRate, [3, 2, 1]);
});

test('migration: every write works after loading a legacy database', function () {
    const store = openLegacy({ version: 1, universe: 'Uni1', players: {}, spies: [] });
    assert.doesNotThrow(function () {
        store.observeStats('Alice', { t: 1, points: 5, rank: 3 });
        store.toggleFavorite('planets', '1:2:3');
        store.toggleFavorite('players', 'Alice');
        store.setNote('1:2:3', 'farm');
        store.setEmpire({ planets: [{ id: 1, name: 'Home' }], sums: {} });
        store.observePlanet({ coords: { g: 1, s: 2, p: 3 }, name: 'Home', owner: 'Alice', date: 5 });
        store.observePlayer({ name: 'Alice', date: 5, points: 5, home: { g: 1, s: 2, p: 3 } });
        store.observeActivity(1, 2, 3, '*', 5);
    });
    assert.equal(store.data().stats.Alice.length, 1);
    assert.equal(store.isFavorite('players', 'Alice'), true);
    assert.equal(store.getNote('1:2:3'), 'farm');
    assert.equal(store.data().empire.planets.length, 1);
});

test('migration: the statistics page no longer crashes on a legacy database', function () {
    // This is the exact path that failed on oldogame.ru: the statistics handler
    // wrote into the missing `stats` collection.
    const store = openLegacy({ version: 1, universe: 'Uni1', spies: [], players: {} });
    const collector = collect.createCollector({ loca: loca, lang: 'ru', store: store, now: helpers.NOW });
    const result = collector.ingestStatisticsPage(helpers.readGolden('ru', 'statistics_p0.html'), {});
    assert.equal(result.rows, 3);
    assert.equal(store.data().stats.PlayerOne.length, 1);
    assert.equal(store.data().meta.selfName, 'PlayerOne');

    const rows = analyze.statisticsRows(require('../src/parsers/statistics.js')
        .parseStatisticsPage(helpers.readGolden('ru', 'statistics_p0.html')).rows, store.data());
    assert.equal(rows.length, 3);
    assert.equal(rows[0].economy, 50, 'the account breakdown treats the unknown fleet and research as zero');
});

test('migration: unreadable data is replaced instead of throwing', function () {
    const store = openLegacy('{ this is not json');
    assert.deepEqual(store.data().spies, []);
    assert.deepEqual(store.data().favorites.planets, {});
    assert.doesNotThrow(function () { store.observeStats('Alice', { t: 1, points: 1 }); });
});

test('migration: the host is part of the storage key', function () {
    const storage = storeModule.memoryStorage();
    const first = storeModule.createStore({ universe: 'Uni1', host: 'oldogame.ru', storage: storage });
    const second = storeModule.createStore({ universe: 'Uni1', host: 'localhost', storage: storage });
    assert.notEqual(first.key, second.key, 'two servers must not share a database');
    assert.equal(first.key, 'ogi084:oldogame.ru:Uni1');
    first.load();
    first.push('spies', { date: 1 });
    first.save();
    second.load();
    assert.equal(second.data().spies.length, 0, 'the other server sees its own (empty) database');
});
