/**
 * Tests of the planet/player database (favourites, notes, filtering) and of the
 * hover tooltips.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const storeModule = require('../src/lib/store.js');
const panel = require('../src/lib/panel.js');
const tooltips = require('../src/lib/tooltips.js');
const i18n = require('../src/lib/i18n.js');
const locaData = require('../src/data/loca.js');
const templates = require('../src/lib/templates.js');

const loca = templates.createLoca(locaData);

/**
 * Build a store with a small database.
 *
 * @returns {object} Store.
 */
function makeStore() {
    const store = storeModule.createStore({ universe: 'Uni1', host: 'localhost', storage: storeModule.memoryStorage() });
    store.load();
    store.observePlanet({ coords: { g: 1, s: 2, p: 3 }, name: 'Home', owner: 'Alice', date: 1000, report: true });
    store.observePlanet({ coords: { g: 1, s: 2, p: 4 }, name: 'Colony', owner: 'Bob', date: 2000 });
    store.observePlanet({ coords: { g: 1, s: 2, p: 3 }, name: 'Moon', owner: 'Alice', date: 3000, type: 'moon' });
    store.observePlayer({ name: 'Alice', date: 3000, points: 1000, rank: 5, ally: 'TST', home: { g: 1, s: 2, p: 3 } });
    store.observePlayer({ name: 'Bob', date: 2000, points: 500, rank: 9 });
    store.observeStats('Alice', { t: 2500, points: 1000, rank: 5, fleet: 300, research: 100 });
    store.observeActivity(1, 2, 3, '*', 3500);
    store.push('spies', {
        date: 3400,
        coords: { g: 1, s: 2, p: 3 },
        planet: 'Home',
        player: 'Alice',
        resources: { 700: 1000, 701: 500, 702: 100 },
        fleet: { 204: 10 },
        defence: {},
    });
    return store;
}

test('store: favourites of planets and players', function () {
    const store = makeStore();
    assert.equal(store.isFavorite('planets', '1:2:3'), false);
    assert.equal(store.toggleFavorite('planets', '1:2:3'), true);
    assert.equal(store.isFavorite('planets', '1:2:3'), true);
    assert.equal(store.toggleFavorite('planets', '1:2:3'), false);
    assert.equal(store.isFavorite('planets', '1:2:3'), false);

    store.toggleFavorite('players', 'Alice');
    assert.equal(store.isFavorite('players', 'Alice'), true);
    assert.equal(store.data().favorites.players.Alice, true);

    // The marks survive a reload of the database.
    const raw = store.storage.getItem(store.key);
    const reloaded = storeModule.createStore({ universe: 'Uni1', host: 'localhost', storage: store.storage });
    reloaded.load();
    assert.equal(reloaded.isFavorite('players', 'Alice'), true);
    assert.ok(raw.indexOf('Alice') !== -1);
});

test('store: notes of a planet', function () {
    const store = makeStore();
    assert.equal(store.getNote('1:2:3'), '');
    store.setNote('1:2:3', '  farm, 2h offline  ');
    assert.equal(store.getNote('1:2:3'), 'farm, 2h offline', 'the text is trimmed');
    store.setNote('1:2:3', '   ');
    assert.equal(store.getNote('1:2:3'), '', 'an empty note is removed');
    assert.equal('1:2:3' in store.data().notes, false);
});

test('planets: the database table shows everything that is known', function () {
    const store = makeStore();
    store.toggleFavorite('planets', '1:2:3');
    store.setNote('1:2:4', 'minen');
    const t = i18n.createI18n('en');
    const html = panel.planetSection(store.data(), { i18n: t, loca: loca, lang: 'en' });

    assert.ok(html.indexOf('data-ogi-planet-filter') !== -1, 'the filter field exists');
    assert.ok(html.indexOf('data-ogi-fav="1:2:3"') !== -1, 'the favourite toggle of a planet exists');
    assert.ok(html.indexOf('data-ogi-fav="player:Alice"') !== -1, 'the favourite toggle of a player exists');
    assert.ok(html.indexOf('★') !== -1, 'the favourite planet is marked');
    assert.ok(html.indexOf('data-ogi-note="1:2:4"') !== -1, 'the note field exists');
    assert.ok(html.indexOf('value="minen"') !== -1, 'the stored note is rendered');
    assert.ok(html.indexOf('Home') !== -1 && html.indexOf('Colony') !== -1);
    assert.ok(html.indexOf('Alice') !== -1 && html.indexOf('TST') !== -1);
    assert.ok(html.indexOf('1:2:3') !== -1);
    assert.ok(html.indexOf('Moon') !== -1, 'moons are in the same table');
});

test('planets: the filter and the favourites-only switch', function () {
    const store = makeStore();
    store.toggleFavorite('players', 'Bob');
    const t = i18n.createI18n('ru');

    const filtered = panel.planetSection(store.data(), { i18n: t, filter: 'colony', loca: loca, lang: 'ru' });
    assert.ok(filtered.indexOf('Colony') !== -1);
    assert.equal(filtered.indexOf('>Home<'), -1, 'the other planet is filtered out');

    const byPlayer = panel.planetSection(store.data(), { i18n: t, filter: 'alice', loca: loca, lang: 'ru' });
    assert.ok(byPlayer.indexOf('1:2:3') !== -1);
    assert.equal(byPlayer.indexOf('1:2:4'), -1, 'a planet of another player is filtered out');

    const favorites = panel.planetSection(store.data(), { i18n: t, favoritesOnly: true, loca: loca, lang: 'ru' });
    assert.ok(favorites.indexOf('Bob') !== -1, 'the favourite player is listed');
    assert.equal(favorites.indexOf('Alice'), -1, 'a player without a star is not listed in the favourites view');
});

test('tooltips: what the database knows about a player', function () {
    const store = makeStore();
    const t = i18n.createI18n('en');
    const html = tooltips.playerTooltip(store.data(), 'Alice', { i18n: t });

    assert.ok(html.indexOf('Alice') !== -1);
    assert.ok(html.indexOf('>Rank<') !== -1 && html.indexOf('>5<') !== -1);
    assert.ok(html.indexOf('>Points<') !== -1 && html.indexOf('1.000') !== -1);
    assert.ok(html.indexOf('>Fleets<') !== -1 && html.indexOf('300') !== -1);
    assert.ok(html.indexOf('TST') !== -1, 'the alliance is shown');
    assert.ok(html.indexOf('1:2:3') !== -1, 'the known planets are listed');
    assert.ok(html.indexOf(t('ACTIVE')) !== -1, 'a recent activity observation is shown');
    assert.equal(tooltips.playerTooltip(store.data(), 'Nobody', { i18n: t }), '', 'an unknown player has no tooltip');
});

test('tooltips: what the database knows about a planet', function () {
    const store = makeStore();
    store.setNote('1:2:3', 'farm');
    const t = i18n.createI18n('de');
    const html = tooltips.planetTooltip(store.data(), '1:2:3', { i18n: t });

    assert.ok(html.indexOf('1:2:3') !== -1);
    assert.ok(html.indexOf('Home') !== -1 && html.indexOf('Moon') !== -1, 'both names are listed');
    assert.ok(html.indexOf('Alice') !== -1, 'the owner is shown');
    assert.ok(html.indexOf('farm') !== -1, 'the note is shown');
    assert.ok(html.indexOf('1.600') !== -1, 'the resources of the last spy report are summed (1000+500+100)');
    assert.equal(tooltips.planetTooltip(store.data(), '9:9:9', { i18n: t }), '');
});

test('tooltips: player controlled text is escaped', function () {
    const store = makeStore();
    store.observePlayer({ name: '<script>', date: 1, ally: '"><b>' });
    store.setNote('1:2:3', '<img src=x onerror=alert(1)>');
    const t = i18n.createI18n('en');
    const player = tooltips.playerTooltip(store.data(), '<script>', { i18n: t });
    const planet = tooltips.planetTooltip(store.data(), '1:2:3', { i18n: t });
    assert.equal(player.indexOf('<script>'), -1);
    assert.ok(player.indexOf('&lt;script&gt;') !== -1);
    assert.equal(planet.indexOf('<img src=x'), -1);
});
