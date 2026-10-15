/**
 * Tests of the generated data and the low level helpers: the game data extracted
 * from game/loca and game/core/techs.php, the template matching and the number
 * handling (including the port of the server's plunder algorithm).
 */
'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');

const helpers = require('./helpers.js');
const locaData = require('../src/data/loca.js');
const unitsData = require('../src/data/units.js');
const numbers = require('../src/lib/numbers.js');
const html = require('../src/lib/html.js');
const templates = require('../src/lib/templates.js');

const loca = templates.createLoca(locaData);

test('locale data covers every language the game supports', function () {
    for (const lang of ['de', 'en', 'es', 'fr', 'it', 'jp', 'ru']) {
        assert.ok(locaData.languages[lang], 'missing language ' + lang);
        assert.ok(Object.keys(locaData.languages[lang]).length > 300, lang + ' has too few strings');
    }
});

test('locale data contains the report templates and object names', function () {
    const en = locaData.languages.en;
    assert.equal(en.SPY_M, 'Metal:');
    assert.equal(en.NAME_204, 'Light Fighter');
    assert.equal(en.NAME_700, 'Metal');
    assert.equal(en.EXP_FOUND, '<br>You got #1 units of #2.');
    assert.equal(en.GALAXY_MOON, 'Moon');
    assert.ok(locaData.languages.ru.EXP_FOUND.indexOf('#1 #2') !== -1, 'russian template differs by design');
    assert.equal(locaData.languages.de.NAME_204, 'Leichter Jäger');
});

test('object tables describe the 0.84 ships and defence', function () {
    assert.deepEqual(unitsData.costs[202], { metal: 2000, crystal: 2000, deuterium: 0, energy: 0 });
    assert.equal(unitsData.params[210].cargo, 5);
    assert.equal(unitsData.params[203].cargo, 25000);
    assert.equal(unitsData.params[209].structure, 16000);
    assert.equal(unitsData.lists.fleet.length, 14);
    assert.equal(unitsData.lists.defence.length, 10);
    assert.deepEqual(unitsData.lists.missiles, [502, 503]);
    assert.equal(unitsData.factor[1], 1.5);
});

test('game numbers are parsed the way nicenum() writes them', function () {
    assert.equal(numbers.parseGameNumber('1.234.567'), 1234567);
    assert.equal(numbers.parseGameNumber('25.047'), 25047);
    assert.equal(numbers.parseGameNumber('500'), 500);
    assert.equal(numbers.parseGameNumber(''), NaN);
    assert.equal(numbers.formatNumber(1234567), '1.234.567');
});

test('plunder reproduces the server algorithm (game/core/battle.php)', function () {
    // Half of everything, cargo large enough for the full 50%.
    assert.deepEqual(numbers.plunder(100000, 20000, 12000, 6000), {
        metal: 10000, crystal: 6000, deuterium: 3000, total: 19000,
    });
    // Cargo is filled metal first, then crystal, then deuterium.
    const small = numbers.plunder(2000, 20000, 12000, 6000);
    assert.equal(small.metal, 666);
    assert.equal(small.crystal, 666);
    assert.equal(small.deuterium, 666);
    assert.equal(small.total, 1998);
    // Empty planet: nothing can be captured.
    assert.equal(numbers.plunder(5000, 0, 0, 0).total, 0);
});

test('maxLoot caps every resource at half', function () {
    assert.deepEqual(numbers.maxLoot(20000, 12000, 6000), {
        metal: 10000, crystal: 6000, deuterium: 3000, total: 19000,
    });
});

test('standard unit values resources with the classic 3:2:1 rate', function () {
    // 3000 metal / 3 * 1 = 1000 (deuterium base).
    assert.equal(numbers.standardUnit([3000, 0, 0], [3, 2, 1], 2), 1000);
    assert.equal(numbers.standardUnit([0, 2000, 0], [3, 2, 1], 2), 1000);
    assert.equal(numbers.standardUnit([0, 0, 1000], [3, 2, 1], 2), 1000);
});

test('templates turn localization strings into capture groups', function () {
    const values = templates.matchTemplate('Resources on #1', 'Resources on Home [1:3:4]');
    assert.equal(values[1], 'Home [1:3:4]');

    const player = templates.matchTemplate(loca.get('en', 'SPY_PLAYER'),
        'Resources on Home <a onclick="showGalaxy(1,3,4);" href="#">[1:3:4]</a> (Player \'PlayerTwo\')<br /> at 09-01 10:43:57.');
    assert.equal(player[1], 'PlayerTwo');
    assert.equal(player[2], '09-01 10:43:57');

    // The placeholders are mapped by their number, not by their order.
    const jp = templates.matchTemplate(loca.get('jp', 'SPY_PLAYER'), "Home のリソース (プレイヤー 'PlayerTwo') 09-01 11:55:32 にて。");
    assert.equal(jp[1], 'PlayerTwo');
    assert.equal(jp[2], '09-01 11:55:32');
});

test('language detection picks the language of the page', function () {
    for (const lang of ['en', 'ru', 'de', 'fr', 'es', 'it', 'jp']) {
        const source = helpers.readGolden(lang, 'messages_p0.html');
        assert.equal(loca.detectLanguage(source).lang, lang);
    }
});

test('language detection uses word boundaries and the page title', function () {
    // "Univers" (fr) must not match the "Universo" (es/it) or "Universum" (de) of
    // another language.
    assert.equal(templates.containsMarker('Universo 1 OGame', 'Univers'), false);
    assert.equal(templates.containsMarker('Univers 1 OGame', 'Univers'), true);
    assert.equal(templates.containsMarker('Universum 1 OGame', 'Univers'), false);

    // A page with almost no text of its own (a report popup) is still detected
    // through the localized menu label it carries in the title.
    const sparse = '<html><head><title>Вселенная 1 ОГейм</title></head><body>' +
        '<script>var session="abc";</script></body></html>';
    assert.equal(loca.detectLanguage(sparse).lang, 'ru');
});

test('html helpers tolerate the broken 0.84 markup', function () {
    // A row that contains a whole tooltip document in an attribute: the quoted
    // </tr> must not end the row.
    const source = "<table><tr><th onmouseover='return overlib(\"<tr></tr>\")'>A</th><th>B</th></tr></table>";
    const rows = html.rowsEx(source);
    assert.equal(rows.length, 1);
    const cells = html.cells(rows[0].html);
    assert.deepEqual(cells.map(function (cell) { return cell.text; }), ['A', 'B']);

    // A cell that contains nested tables ends at its own </td>.
    const nested = '<td class="b" colspan="3"><table><tr><td>x</td></tr></table>tail</td><td>next</td>';
    const start = nested.indexOf('>') + 1;
    assert.equal(html.cellContent(nested, start).indexOf('tail') !== -1, true);
    assert.equal(html.cellContent(nested, start).indexOf('next'), -1);
});
