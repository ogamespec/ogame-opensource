/**
 * Tests of the game page detection.
 *
 * The detection decides whether the extension does anything at all on a page, and
 * it must not depend on the page title: the 0.84 title is localized ("Uni1 OGame"
 * in English and German, but "Вселенная 1 ОГейм" in Russian, "ユニバース 1 OGame"
 * in Japanese and "OGame Universo 1" in Italian), and the report popup has no left
 * menu at all.
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const detect = require('../src/lib/detect.js');
const storeModule = require('../src/lib/store.js');

test('a messages page of every language is recognized', function () {
    for (const lang of ['en', 'ru', 'de', 'fr', 'es', 'it', 'jp']) {
        const source = helpers.readGolden(lang, 'messages_p0.html');
        assert.equal(detect.looksLikeGamePage(source), true, lang + ': game page');
        assert.equal(detect.detectUniverse(source, ''), 'uni1', lang + ': universe from the left menu');
        assert.equal(detect.detectSession(source), '0000000003e9', lang + ': session');
    }
});

test('the universe is found in a localized title as well', function () {
    // Titles the engine really produces (game/loca/<lang>/common.php, PAGE_TITLE).
    const cases = [
        ['Uni1 OGame', 'uni1'],
        ['Uni12 OGame', 'uni12'],
        ['Univers 3 OGame', 'uni3'],
        ['Universo 7 OGame', 'uni7'],
        ['Вселенная 5 ОГейм', 'uni5'],
        ['ユニバース 2 OGame', 'uni2'],
        ['OGame Universo 9', 'uni9'],
        ['just a website', null],
    ];
    for (const entry of cases) {
        assert.equal(detect.detectUniverse('<html></html>', entry[0]), entry[1], entry[0]);
    }
});

test('the report popup is recognized although it has no left menu', function () {
    for (const file of ['bericht_spy_p0.html', 'bericht_battle_p0.html']) {
        const source = helpers.readGolden('en', file);
        assert.equal(detect.looksLikeGamePage(source, 'bericht'), true, file + ': game page');
        assert.equal(detect.looksLikeGamePage(source), false, file + ': needs the page name to be recognized');
        assert.equal(detect.detectUniverse(source, document_title_of(source)), null, file + ': no universe on the popup');
        assert.equal(detect.detectSession(source), '0000000003e9', file + ': session');
    }
});

/**
 * The popup title of a golden snapshot ("Spy Report" / "Battle Report").
 *
 * @param {string} source Page source.
 * @returns {string} Title text.
 */
function document_title_of(source) {
    const match = /<title>([^<]*)<\/title>/i.exec(source);
    return match ? match[1] : '';
}

test('foreign pages are rejected', function () {
    assert.equal(detect.looksLikeGamePage('<html><body>hello</body></html>'), false);
    assert.equal(detect.looksLikeGamePage(''), false);
    assert.equal(detect.looksLikeGamePage('var session="1"; // unrelated page'), false);
    // A modern OGame page has neither the 0.84 markers nor the engine's links.
    assert.equal(detect.looksLikeGamePage('<div id="messagewrapper" data-raw-messagetype="41"></div>'), false);
});

test('the universe hint bridges the report popup to the messages page', function () {
    const storage = storeModule.memoryStorage();
    detect.saveUniverseHint(storage, 'oldogame.ru', 'uni1');
    assert.equal(detect.loadUniverseHint(storage, 'oldogame.ru'), 'uni1');
    assert.equal(detect.loadUniverseHint(storage, 'other.example'), null);

    // Both pages end up in the same database, which is what makes the report of a
    // popup merge into the battle row of the message list.
    assert.equal(storeModule.storageKey('uni1', 'oldogame.ru'), storeModule.storageKey('uni1', 'oldogame.ru'));
});

test('page names are read from the URL', function () {
    assert.equal(detect.currentPage('?page=messages&dsp=1', '/game/index.php'), 'messages');
    assert.equal(detect.currentPage('?page=bericht&session=x&bericht=4', '/index.php'), 'bericht');
    assert.equal(detect.currentPage('?page=GALAXY&galaxy=1', '/index.php'), 'galaxy');
    assert.equal(detect.currentPage('', '/index.php'), 'overview');
    assert.equal(detect.currentPage('', '/'), null);
});
